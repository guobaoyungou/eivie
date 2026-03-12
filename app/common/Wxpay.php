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

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

/**
 * wxpay_type文档https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwKTcT9kNuQa0MtULMT3?scode=AHMAHgcfAA042sx4xUAT4AYwbFACw
 * 微信支付 v2统一下单文档 https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1
 */
class Wxpay
{
	//创建微支付 微信公众号
	public static function build_mp($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$openid=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$member = Db::name('member')->where('id',$mid)->find();
		$package = array();
		$appinfo = \app\common\System::appinfo($aid,'mp');
		$appid = $appinfo['appid'];
        if($member){
            $openid = $member[platform.'openid'];
        }

		$isbusinesspay = false;
		if($bid > 0){
			$business = Db::name('business')->where('id',$bid)->find();
			if($business['sxpay_mno']){
				$rs = \app\custom\Sxpay::build_mp($aid,$bid,$mid,$title,$ordernum,$price,$tablename);
				return $rs;
			}
			$bset = Db::name('business_sysset')->where('aid', $aid)->find();
			if($bset['wxfw_status'] == 1){//商户-系统设置 微信服务商开启
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$package['appid'] = $bset['wxfw_appid'];
					$package['mch_id'] = $bset['wxfw_mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $bset['wxfw_mchkey'];

					$chouchengmoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$chouchengmoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					if($bset['commission_kouchu'] == 1){
						$commission = self::getcommission($tablename,$ordernum);
					}else{
						$commission = 0;
						}

					$chouchengmoney = $chouchengmoney + $commission;
					if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}
					$package['attach'] = $aid.':'.$tablename.':mp:'.$bid;
				}
			}
            elseif($bset['wxfw_status'] == 2){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
					if(!$dbwxpayset){
						return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
					}
					$package['appid'] = $dbwxpayset['appid'];
					$package['mch_id'] = $dbwxpayset['mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $dbwxpayset['mchkey'];

					$feemoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$feemoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					$chouchengmoney = 0;
					$admindata = Db::name('admin')->where('id',$aid)->find();
					if($admindata['chouchengset']==0){ //默认抽成
						if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
							if($dbwxpayset['chouchengset'] == 1){
								//$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
								$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
								if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
									$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
								}
							}else{
								$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
							}
						}
					}elseif($admindata['chouchengset']==1){ //按比例抽成
						//$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
						$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
						if($chouchengmoney < floatval($admindata['chouchengmin'])){
							$chouchengmoney = floatval($admindata['chouchengmin']);
						}
					}elseif($admindata['chouchengset']==2){ //按固定金额抽成
						$chouchengmoney = floatval($admindata['chouchengmoney']);
					}

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}else{
						$chouchengmoney = 0;
						//if($business['feepercent'] > 0){
						//	$chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
						//}
						$chouchengmoney = $chouchengmoney + $feemoney;

						if($bset['commission_kouchu'] == 1){
							$commission = self::getcommission($tablename,$ordernum);
						}else{
							$commission = 0;
							}
						$chouchengmoney = $chouchengmoney + $commission;
						if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

						if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
							$package['profit_sharing'] = 'Y';
						}
					}
					$package['attach'] = $aid.':'.$tablename.':mp:'.$bid;
				}
			}
		}
		if(!$isbusinesspay){
			if($appinfo['wxpay_type']==0){//0普通模式
                $package['appid'] = $appid;
				$package['mch_id'] = $appinfo['wxpay_mchid'];
				$package['openid'] = $openid;
				$mchkey = $appinfo['wxpay_mchkey'];
			}
            elseif($appinfo['wxpay_type']==1){//1服务商模式
                $dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				if(!$dbwxpayset){
					return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
				}
				$package['appid'] = $dbwxpayset['appid'];
				$package['sub_appid'] = $appid;
				$package['sub_openid'] = $openid;
				$package['mch_id'] = $dbwxpayset['mchid'];
				$package['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];

				$chouchengmoney = 0;
				$admindata = Db::name('admin')->where('id',$aid)->find();
				if($admindata['chouchengset']==0){ //默认抽成
					if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
						if($dbwxpayset['chouchengset'] == 1){
							$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
							if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
								$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
							}
						}else{
							$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
						}
					}
				}
                elseif($admindata['chouchengset']==1){ //按比例抽成
					$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
					if($chouchengmoney < floatval($admindata['chouchengmin'])){
						$chouchengmoney = floatval($admindata['chouchengmin']);
					}
				}
                elseif($admindata['chouchengset']==2){ //按固定金额抽成
					$chouchengmoney = floatval($admindata['chouchengmoney']);
				}
				if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
					$package['profit_sharing'] = 'Y';
				}
			}
            elseif($appinfo['wxpay_type']==3){//随行付
				$rs = \app\custom\Sxpay::build_mp($aid,$bid,$mid,$title,$ordernum,$price,$tablename);
				//\think\facade\Log::write($rs);
				return $rs;
			}
            elseif($appinfo['wxpay_type']==4){
			    $huifu_fenzhang_custom= getcustom('pay_huifu_fenzhang');
                }
            elseif($appinfo['wxpay_type']==6){
                }
            elseif($appinfo['wxpay_type'] == 10){
				}
			$package['attach'] = $aid.':'.$tablename.':mp';
		}

        $package['nonce_str'] = random(8);
		$package['body'] = mb_substr($title,0,42);
		$package['out_trade_no'] = $ordernum;
		$package['total_fee'] = bcmul($price, 100, 0);
		//$package['spbill_create_ip'] = CLIENT_IP;
		//$package['time_start'] = date('YmdHis', TIMESTAMP);
		//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
		$package['notify_url'] = $notify_url;
		$package['trade_type'] = 'JSAPI';
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
		//dump($price);
		//dump($package);
		//dump($dat);
		//dump($mchkey);
        //文档 https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1
		$response = request_post('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
		
		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->return_msg)];
		} 
		if (strval($xml->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->err_code_des)];
		}
		$prepayid = $xml->prepay_id;
		$wOpt = [];
		if(platform=='wx'){
			$wOpt['appId'] = $appid;
		}else{
			$wOpt['appId'] = $package['appid'];
		}
		$wOpt['timeStamp'] = time()."";
		$wOpt['nonceStr'] = random(8);
		$wOpt['package'] = 'prepay_id=' . $prepayid;
		$wOpt['signType'] = 'MD5';
		ksort($wOpt, SORT_STRING);
		foreach ($wOpt as $key => $v) {
			$string .= "{$key}={$v}&";
		}
		$string .= "key=".$mchkey;
		$wOpt['paySign'] = strtoupper(md5($string));
		return ['status'=>1,'data'=>$wOpt];
	}
	//创建微支付 微信小程序 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
	//微信小程序 v3统一下单文档 https://pay.weixin.qq.com/doc/v3/merchant/4012791897
    //wxpay_type 枚举值汇总 https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwKTcT9kNuQa0MtULMT3
	public static function build_wx($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$openid=''){
        $title = removeEmoj(htmlspecialchars(stripslashes($title)));
        $title = str_replace("\t", " ", $title);
        $title = str_replace('/',' ',$title);
        $title = mb_substr($title,0,42);
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$time = time();
		$member = Db::name('member')->where('id',$mid)->find();
		$package = array();
		$appinfo = \app\common\System::appinfo($aid,'wx');
		$appid = $appinfo['appid'];
		if($member){
            $openid = $member[platform.'openid'];
        }
		
		if($tablename == 'shop'){
			$order_detail = [];
			$order = Db::name('shop_order')->where('aid', $aid)->where('ordernum',$ordernum)->find();
			if($order['fromwxvideo'] == 1){
				if(!$order['wxvideo_order_id']){
					$rs = \app\common\Wxvideo::createorder($order['id']);
					if($rs['status'] == 0){
						return $rs;
					}
				}
				//生成支付参数
				$url = 'https://api.weixin.qq.com/shop/order/getpaymentparams?access_token='.\app\common\Wechat::access_token($aid,'wx');
				$rs = curl_post($url,jsonEncode(['out_order_id'=>strval($order['id']),'openid'=>$openid]));
				$rs = json_decode($rs,true);
				if($rs['errcode'] == 0 && $rs['payment_params']){
					$wOpt = $rs['payment_params'];
					return ['status'=>1,'data'=>$wOpt,'fromwxvideo'=>1];
				}else{
					return ['status'=>0,'msg'=>\app\common\Wechat::geterror($rs)];
				}
			}
		}

		if($appinfo['wxpay_type'] == 2){//二级商户模式
			$url = 'https://api.weixin.qq.com/shop/pay/createorder?access_token='.\app\common\Wechat::access_token($aid,'wx');
			$postdata = [];
			$postdata['openid'] = $openid;
			$postdata['combine_trade_no'] = 'P'.$ordernum;
			$postdata['sub_orders'] = [['mchid'=>$appinfo['wxpay_sub_mchid2'],'amount'=>intval(bcmul($price, 100,0)),'trade_no'=>$ordernum,'description'=>$title]];
			$rs = curl_post($url,jsonEncode($postdata));
			$rs = json_decode($rs,true);
			//Log::write($rs);
			if($rs['errcode'] == 0 && $rs['payment_params']){
				$wOpt = $rs['payment_params'];
				return ['status'=>1,'data'=>$wOpt,'wxpay_type'=>2];
			}else{
				return ['status'=>0,'msg'=>\app\common\Wechat::geterror($rs)];
			}
		}

		$isbusinesspay = false;
		if($bid > 0){
			$business = Db::name('business')->where('id',$bid)->find();
			if($business['sxpay_mno']){
				$rs = \app\custom\Sxpay::build_wx($aid,$bid,$mid,$title,$ordernum,$price,$tablename);
				return $rs;
			}
			$bset = Db::name('business_sysset')->where('aid', $aid)->find();
			if($bset['wxfw_status'] == 1){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$package['appid'] = $bset['wxfw_appid'];
					$package['mch_id'] = $bset['wxfw_mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $bset['wxfw_mchkey'];

					$chouchengmoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$chouchengmoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					if($bset['commission_kouchu'] == 1){
						$commission = self::getcommission($tablename,$ordernum);
					}else{
						$commission = 0;
						}

					$chouchengmoney = $chouchengmoney + $commission;
					if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}
					$package['attach'] = $aid.':'.$tablename.':wx:'.$bid;
				}
			}elseif($bset['wxfw_status'] == 2){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
					if(!$dbwxpayset){
						return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
					}
					$package['appid'] = $dbwxpayset['appid'];
					$package['mch_id'] = $dbwxpayset['mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $dbwxpayset['mchkey'];

					$feemoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$feemoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					$chouchengmoney = 0;
					$admindata = Db::name('admin')->where('id',$aid)->find();
					if($admindata['chouchengset']==0){ //默认抽成
						if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
							if($dbwxpayset['chouchengset'] == 1){
								//$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
								$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
								if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
									$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
								}
							}else{
								$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
							}
						}
					}elseif($admindata['chouchengset']==1){ //按比例抽成
						//$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
						$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
						if($chouchengmoney < floatval($admindata['chouchengmin'])){
							$chouchengmoney = floatval($admindata['chouchengmin']);
						}
					}elseif($admindata['chouchengset']==2){ //按固定金额抽成
						$chouchengmoney = floatval($admindata['chouchengmoney']);
					}

					if($chouchengmoney > 0 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}else{
						$chouchengmoney = 0;
						//if($business['feepercent'] > 0){
						//	$chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
						//}
						$chouchengmoney = $chouchengmoney + $feemoney;

						if($bset['commission_kouchu'] == 1){
							$commission = self::getcommission($tablename,$ordernum);
						}else{
							$commission = 0;
							}

						$chouchengmoney = $chouchengmoney + $commission;
						if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

						if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
							$package['profit_sharing'] = 'Y';
						}
					}
					$package['attach'] = $aid.':'.$tablename.':wx:'.$bid;
				}
			}
		}

		if(!$isbusinesspay){
			if($appinfo['wxpay_type']==0){
				$package['appid'] = $appid;
				$package['mch_id'] = $appinfo['wxpay_mchid'];
				$package['openid'] = $openid;
				$mchkey = $appinfo['wxpay_mchkey'];
			}elseif($appinfo['wxpay_type']==3){//随行付
				$rs = \app\custom\Sxpay::build_wx($aid,$bid,$mid,$title,$ordernum,$price,$tablename);
				return $rs;
			}
            elseif($appinfo['wxpay_type']==1){//服务商模式
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				if(!$dbwxpayset){
					return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
				}
				$package['appid'] = $dbwxpayset['appid'];
				$package['sub_appid'] = $appid;
				$package['sub_openid'] = $openid;
				$package['mch_id'] = $dbwxpayset['mchid'];
				$package['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];

				$chouchengmoney = 0;
				$admindata = Db::name('admin')->where('id',$aid)->find();
				if($admindata['chouchengset']==0){ //默认抽成
					if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
						if($dbwxpayset['chouchengset'] == 1){
							$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
							if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
								$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
							}
						}else{
							$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
						}
					}
				}elseif($admindata['chouchengset']==1){ //按比例抽成
					$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
					if($chouchengmoney < floatval($admindata['chouchengmin'])){
						$chouchengmoney = floatval($admindata['chouchengmin']);
					}
				}elseif($admindata['chouchengset']==2){ //按固定金额抽成
					$chouchengmoney = floatval($admindata['chouchengmoney']);
				}
				if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
					$package['profit_sharing'] = 'Y';
				}
			}
            elseif($appinfo['wxpay_type']==4){//汇付斗拱
			    $huifu_fenzhang_custom= getcustom('pay_huifu_fenzhang');
			    $pay_huifu_preorder_embedded= getcustom('pay_huifu_preorder_embedded',$aid);
                }
            elseif($appinfo['wxpay_type']==6){
                }
            elseif($appinfo['wxpay_type']==8) {
                //b2b模式 https://developers.weixin.qq.com/miniprogram/dev/api/payment/wx.requestCommonPayment.html
                $package['appid'] = $appid;
                $package['mch_id'] = $appinfo['wxpay_b2b_mchid'];
                $package['openid'] = $openid;
                $mchkey = $appinfo['wxpay_b2b_mchkey'];
            }
            elseif($appinfo['wxpay_type']==10){
				}

            $package['attach'] = $aid.':'.$tablename.':wx';
		}

        // 海外版
        $package['nonce_str'] = random(8);
		$package['body'] = $title;
		$package['out_trade_no'] = $ordernum;
		$package['total_fee'] = bcmul($price, 100, 0);
		//$package['spbill_create_ip'] = CLIENT_IP;
		//$package['time_start'] = date('YmdHis', TIMESTAMP);
		//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
		$package['notify_url'] = $notify_url;
		$package['trade_type'] = 'JSAPI';
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);

        if($appinfo['wxpay_type']==8) {
            //b2b模式 https://developers.weixin.qq.com/miniprogram/dev/api/payment/wx.requestCommonPayment.html
            //https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/industry/B2b_store_assistant.html#_3-3-%E6%94%AF%E4%BB%98%E4%B8%8E%E9%80%80%E6%AC%BE

            //生成支付交易流水
            $pay_transaction = \app\common\Common::createPayTransaction($aid,$ordernum,$tablename);
            if(!$pay_transaction){
                return ['status'=>0,'data'=>'生成交易流水失败'];
            }
            $ordernum = $pay_transaction['transaction_num'];

            $signData = [
                'mchid' => $package['mch_id'],
                'out_trade_no' => $ordernum,
                'description' => $package['body'],
                'amount' => [
                    'order_amount'=> intval($package['total_fee']),//必须是整型！！！
                    'currency'=>'CNY'
                ],
                'attach'=>$package['attach'],
                'env'=>0//0	生产环境/现网环境,1沙箱环境/测试环境
            ];
            $b2bData['signData'] = $signData;
            $b2bData['signDatajson'] = jsonEncode($signData);
            //用户态签名 signature = to_hex(hmac_sha256(sessionKey,signData))
            $sessionKey = $member['session_key'];
            $appkey = $appinfo['wxpay_b2b_appkey'];
            $b2bData['signature'] = self::b2b_signature(jsonEncode($b2bData['signData']), $sessionKey);
            //appKey	可通过小程序MP查看：门店助手 -> 支付管理 -> 商户号管理查看详情->基本配置中的沙箱AppKey和现网AppKey。注意：记得根据env值选择不同AppKey，env = 0对应现网AppKey，env = 1对应沙箱AppKey	获得方式相同，服务器API只用现网AppKey，不区分环境
            $b2bData['paySig'] = self::b2b_pay_sig('requestCommonPayment',jsonEncode($b2bData['signData']), $appkey);
            return ['status'=>1,'data'=>$b2bData];
        }
		//dump($price);
		//dump($package);
		//dump($dat);
		//dump($mchkey);
        //文档 https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1
		$response = request_post('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);

		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

		if (strval($xml->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->return_msg)];
		} 
		if (strval($xml->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->err_code_des)];
		}
		$prepayid = strval($xml->prepay_id);
		$wOpt = [];
		if(platform=='wx'){
			$wOpt['appId'] = $appid;
		}else{
			$wOpt['appId'] = $package['appid'];
		}
		$wOpt['timeStamp'] = $time."";
		$wOpt['nonceStr'] = random(8);
		$wOpt['package'] = 'prepay_id=' . $prepayid;
		$wOpt['signType'] = 'MD5';
		ksort($wOpt, SORT_STRING);
		foreach ($wOpt as $key => $v) {
			$string .= "{$key}={$v}&";
		}
		$string .= "key=".$mchkey;
		$wOpt['paySign'] = strtoupper(md5($string));
		
		return ['status'=>1,'data'=>$wOpt];
	}
	//创建微支付参数H5
	public static function build_h5($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$set = Db::name('admin_set')->where('aid', $aid)->find();
		$package = array();

		$appinfo = \app\common\System::appinfo($aid,'h5');
		$appid = $appinfo['appid'];

        //生成支付交易流水
        $pay_transaction = \app\common\Common::createPayTransaction($aid,$ordernum,$tablename);
        if(!$pay_transaction){
            return ['status'=>0,'data'=>'生成交易流水失败'];
        }
        $ordernum = $pay_transaction['transaction_num'];

		$isbusinesspay = false;
		if($bid > 0){
			$bset = Db::name('business_sysset')->where('aid', $aid)->find();
            $business = Db::name('business')->where('id',$bid)->find();
			if($bset['wxfw_status'] == 1){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$package['appid'] = $bset['wxfw_appid'];
					$package['mch_id'] = $bset['wxfw_mchid'];
					if($appid){
						$package['sub_appid'] = $appid;
					}
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $bset['wxfw_mchkey'];

					$chouchengmoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$chouchengmoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					if($bset['commission_kouchu'] == 1){
						$commission = self::getcommission($tablename,$ordernum);
					}else{
						$commission = 0;
						}

					$chouchengmoney = $chouchengmoney + $commission;
					if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}
					$package['attach'] = $aid.':'.$tablename.':h5:'.$bid;
				}
			}elseif($bset['wxfw_status'] == 2){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
					if(!$dbwxpayset){
						return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
					}
					$package['appid'] = $dbwxpayset['appid'];
					$package['mch_id'] = $dbwxpayset['mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $dbwxpayset['mchkey'];

					$feemoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$feemoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					$chouchengmoney = 0;
					$admindata = Db::name('admin')->where('id',$aid)->find();
					if($admindata['chouchengset']==0){ //默认抽成
						if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
							if($dbwxpayset['chouchengset'] == 1){
								//$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
								$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
								if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
									$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
								}
							}else{
								$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
							}
						}
					}elseif($admindata['chouchengset']==1){ //按比例抽成
						//$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
						$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
						if($chouchengmoney < floatval($admindata['chouchengmin'])){
							$chouchengmoney = floatval($admindata['chouchengmin']);
						}
					}elseif($admindata['chouchengset']==2){ //按固定金额抽成
						$chouchengmoney = floatval($admindata['chouchengmoney']);
					}

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}else{
						$chouchengmoney = 0;
						//if($business['feepercent'] > 0){
						//	$chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
						//}
						$chouchengmoney = $chouchengmoney + $feemoney;

						if($bset['commission_kouchu'] == 1){
							$commission = self::getcommission($tablename,$ordernum);
						}else{
							$commission = 0;
							}

						$chouchengmoney = $chouchengmoney + $commission;
						if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

						if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
							$package['profit_sharing'] = 'Y';
						}
					}
					$package['attach'] = $aid.':'.$tablename.':h5:'.$bid;
				}
			}
		}

		if(!$isbusinesspay){
			if($appinfo['wxpay_type']==0){//0普通模式
				$package['appid'] = $appid;
				$package['mch_id'] = $appinfo['wxpay_mchid'];
				$mchkey = $appinfo['wxpay_mchkey'];
			}elseif($appinfo['wxpay_type']==1){//1服务商模式
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				if(!$dbwxpayset){
					return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
				}
				$package['appid'] = $dbwxpayset['appid'];
				if($appid){
					$package['sub_appid'] = $appid;
				}
				$package['mch_id'] =$dbwxpayset['mchid'];
				$package['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];
				
				$chouchengmoney = 0;
				$admindata = Db::name('admin')->where('id',$aid)->find();
				if($admindata['chouchengset']==0){ //默认抽成
					if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
						if($dbwxpayset['chouchengset'] == 1){
							$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
							if($chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
								$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
							}
						}else{
							$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
						}
					}
				}elseif($admindata['chouchengset']==1){ //按比例抽成
					$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
					if($chouchengmoney < floatval($admindata['chouchengmin'])){
						$chouchengmoney = floatval($admindata['chouchengmin']);
					}
				}elseif($admindata['chouchengset']==2){ //按固定金额抽成
					$chouchengmoney = floatval($admindata['chouchengmoney']);
				}
				if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
					$package['profit_sharing'] = 'Y';
				}
			}elseif($appinfo['wxpay_type']==4){
                }elseif($appinfo['wxpay_type']==6){
                }elseif($appinfo['wxpay_type']==10){
				}
			$package['attach'] = $aid.':'.$tablename.':h5';
		}
        $package['nonce_str'] = random(8);
		$package['body'] = mb_substr($title,0,42);
		$package['out_trade_no'] = $ordernum;
		$package['total_fee'] = bcmul($price, 100, 0);
		$package['spbill_create_ip'] = request()->ip();
		//$package['time_start'] = date('YmdHis', TIMESTAMP);
		//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
		$package['notify_url'] = $notify_url;
		$package['trade_type'] = 'MWEB';
		$package['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "'.PRE_URL.'","wap_name": "'.$set['name'].'"}}';
		//dump($package);
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
		//var_dump($string1);
		//var_dump($package);
		//var_dump($dat);
		$response = request_post('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
		
		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->return_msg)];
		} 
		if (strval($xml->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->err_code_des)];
		}
		//var_dump($xml);
		//var_dump($xml->mweb_url.'');
		//$prepayid = $xml->prepay_id;
		$wOpt = [];
		$wOpt['app_id'] = $set['ttpayid'];
		$wOpt['sign_type'] = 'MD5';
		$wOpt['out_order_no'] = $ordernum;
		$wOpt['merchant_id'] = $set['ttmchid'];
		$wOpt['timestamp'] = time()."";
		$wOpt['product_code'] = 'pay';
		$wOpt['payment_type'] = 'direct';
		$wOpt['total_amount'] = $price * 100;
		$wOpt['trade_type'] = 'H5';
		$wOpt['uid'] = $mid;
		$wOpt['version'] = '2.0';
		$wOpt['currency'] = 'CNY';
		$wOpt['subject'] = mb_substr($title,0,42);
		$wOpt['body'] = mb_substr($title,0,42);
		$wOpt['trade_time'] = time()."";
		$wOpt['valid_time'] = '300';
		$wOpt['notify_url'] = $notify_url;
		$wOpt['wx_url'] = strval($xml->mweb_url);
		$wOpt['wx_type'] = 'MWEB';
		//$wOpt['alipay_url'] = '';
		ksort($wOpt, SORT_STRING);
		foreach ($wOpt as $key => $v) {
			$string .= "{$key}={$v}&";
		} 
		$string = rtrim($string,'&');
		$string .= "".$set['ttpaysecret'];
		$wOpt['sign'] = md5($string);
		return ['status'=>1,'data'=>$wOpt];
	}
    /**
     * 微信收款码-采用微信支付的 Native 支付功能
     * 定制标记：wxpay_native_h5
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwO14tf6QNQ3iTUu4oO7?scode=AHMAHgcfAA0Gz10OFWAeYAOQYKALU
     * @author: liud
     * @time: 2024/12/2 下午6:09
     */
    public static function build_pay_native_h5($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$pay_platform='h5'){
        if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
        $set = Db::name('admin_set')->where('aid', $aid)->find();
        $package = array();

        $appinfo = \app\common\System::appinfo($aid,$pay_platform);
        $appid = $appinfo['wxpay_appid'] ?: $appinfo['appid'];

        //生成支付交易流水
        $pay_transaction = \app\common\Common::createPayTransaction($aid,$ordernum,$tablename);
        if(!$pay_transaction){
            return ['status'=>0,'data'=>'生成交易流水失败'];
        }
        $ordernum = $pay_transaction['transaction_num'];

        $package['body'] = mb_substr($title,0,42);
        $package['total_fee'] = bcmul($price, 100, 0);
        $package['spbill_create_ip'] = request()->ip();
        $package['notify_url'] = $notify_url;
        $package['trade_type'] = 'NATIVE';
        $package['product_id'] = $ordernum;

        ksort($package, SORT_STRING);
        $string1 = '';

        $isbusinesspay = false;
        if($bid > 0){
            $bset = Db::name('business_sysset')->where('aid', $aid)->find();
            $business = Db::name('business')->where('id',$bid)->find();
            if($business['wxpayst']==1 && $business['wxpay_submchid']){
                $isbusinesspay = true;
                //微信付款码默认读取0普通支付模式配置
                $package['appid'] = $bset['wxfw_appid'];
                $package['mch_id'] = $bset['wxfw_mchid'];
                if($appid){
                    $package['sub_appid'] = $appid;
                }
                $package['sub_mch_id'] = $business['wxpay_submchid'];
                $mchkey = $bset['wxfw_mchkey'];

                $chouchengmoney = 0;
                $countprice = $price;//重新赋值，用于计算使用
                if($business['feepercent'] > 0){
                    if(false){}else{
                        $chouchengmoney = floatval($business['feepercent']) * 0.01 * $countprice;
                    }
                    }

                if($bset['commission_kouchu'] == 1){
                    $commission = self::getcommission($tablename,$ordernum);
                }else{
                    $commission = 0;
                    }

                $chouchengmoney = $chouchengmoney + $commission;
                if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

                if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
                    $package['profit_sharing'] = 'Y';
                }
                $package['attach'] = $aid.':'.$tablename.':h5:'.$bid;
            }
        }

        if(!$isbusinesspay){
            //微信付款码默认读取0普通支付模式配置
            $package['appid'] = $appid;
            $package['mch_id'] = $appinfo['wxpay_mchid'];
            $mchkey = $appinfo['wxpay_mchkey'];
            $package['attach'] = $aid.':'.$tablename.':h5';
        }

        $package['nonce_str'] = random(8);
        $package['body'] = mb_substr($title,0,42);
        $package['out_trade_no'] = $ordernum;
        $package['total_fee'] = bcmul($price, 100, 0);
        $package['spbill_create_ip'] = request()->ip();
        $package['notify_url'] = $notify_url;
        $package['trade_type'] = 'NATIVE';
        $package['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "'.PRE_URL.'","wap_name": "'.$set['name'].'"}}';
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key=".$mchkey;
        $package['sign'] = strtoupper(md5($string1));
        $dat = array2xml($package);

        $response = request_post('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);

        $xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (strval($xml->return_code) == 'FAIL') {
            return ['status'=>0,'msg'=>strval($xml->return_msg)];
        }
        if (strval($xml->result_code) == 'FAIL') {
            return ['status'=>0,'msg'=>strval($xml->err_code_des)];
        }
        $wOpt = [];
        $wOpt['pay_wx_qrcode_url'] = createqrcode($xml->code_url);
        return ['status'=>1,'data'=>$wOpt];
    }

    /**
     * 微信支付V3 Native下单（PC端扫码支付）
     * 使用V3接口 POST /v3/pay/transactions/native
     * @param int $aid 应用ID
     * @param int $bid 商户ID
     * @param int $mid 会员ID
     * @param string $title 订单标题
     * @param string $ordernum 订单号
     * @param float $price 价格（元）
     * @param string $tablename 表名
     * @param string $notify_url 回调地址
     * @return array
     */
    public static function build_native_v3($aid, $bid, $mid, $title, $ordernum, $price, $tablename, $notify_url = ''){
        $title = removeEmoj(htmlspecialchars(stripslashes($title)));
        $title = str_replace("\t", " ", $title);
        $title = str_replace('/',' ',$title);
        $title = mb_substr($title, 0, 42);
        if(!$notify_url) $notify_url = PRE_URL.'/notify.php';

        // 读取PC端支付配置
        $appinfo = \app\common\System::appinfo($aid, 'pc');
        if(empty($appinfo) || empty($appinfo['wxpay_mchid'])){
            return ['status'=>0,'msg'=>'PC端微信支付未配置商户号'];
        }
        if(empty($appinfo['wxpay_mchkey_v3'])){
            return ['status'=>0,'msg'=>'PC端微信支付未配置APIv3密钥'];
        }
        if(empty($appinfo['wxpay_apiclient_key'])){
            return ['status'=>0,'msg'=>'PC端微信支付未配置商户API私钥'];
        }
        if(empty($appinfo['wxpay_serial_no'])){
            return ['status'=>0,'msg'=>'PC端微信支付未配置商户证书序列号'];
        }
        $wxpay_appid = $appinfo['wxpay_appid'] ?: $appinfo['appid'];
        if(empty($wxpay_appid)){
            return ['status'=>0,'msg'=>'PC端微信支付未配置AppID'];
        }

        // 生成支付交易流水
        $pay_transaction = \app\common\Common::createPayTransaction($aid, $ordernum, $tablename);
        if(!$pay_transaction){
            return ['status'=>0,'msg'=>'生成交易流水失败'];
        }
        $ordernum = $pay_transaction['transaction_num'];

        try {
            // 加载商户API私钥
            $merchantPrivateKey = file_get_contents(ROOT_PATH . $appinfo['wxpay_apiclient_key']);
            if(empty($merchantPrivateKey)){
                return ['status'=>0,'msg'=>'无法读取商户API私钥文件'];
            }
            $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKey, Rsa::KEY_TYPE_PRIVATE);

            // 根据sign_type构造V3客户端实例
            $certs = [];
            $serial = '';
            if($appinfo['sign_type'] == 1){
                // 使用微信支付公钥
                if(empty($appinfo['public_key_id']) || empty($appinfo['public_key_pem'])){
                    return ['status'=>0,'msg'=>'PC端微信支付未配置支付公钥'];
                }
                $publicKeyContent = file_get_contents(ROOT_PATH . $appinfo['public_key_pem']);
                if(empty($publicKeyContent)){
                    return ['status'=>0,'msg'=>'无法读取微信支付公钥文件'];
                }
                $platformPublicKeyInstance = Rsa::from($publicKeyContent, Rsa::KEY_TYPE_PUBLIC);
                $certs[$appinfo['public_key_id']] = $platformPublicKeyInstance;
                $serial = $appinfo['public_key_id'];
            } else {
                // 使用平台证书
                if(empty($appinfo['wxpay_wechatpay_pem'])){
                    return ['status'=>0,'msg'=>'PC端微信支付未配置平台证书'];
                }
                $platCertContent = file_get_contents(ROOT_PATH . $appinfo['wxpay_wechatpay_pem']);
                if(empty($platCertContent)){
                    return ['status'=>0,'msg'=>'无法读取微信支付平台证书文件'];
                }
                $platformPublicKeyInstance = Rsa::from($platCertContent, Rsa::KEY_TYPE_PUBLIC);
                if(!empty($appinfo['wxpay_plate_serialno'])){
                    $platformCertificateSerial = $appinfo['wxpay_plate_serialno'];
                } else {
                    $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platCertContent);
                }
                $certs[$platformCertificateSerial] = $platformPublicKeyInstance;
                $serial = $platformCertificateSerial;
            }

            // 构造APIv3客户端实例
            $instance = Builder::factory([
                'mchid'      => $appinfo['wxpay_mchid'],
                'serial'     => $appinfo['wxpay_serial_no'],
                'privateKey' => $merchantPrivateKeyInstance,
                'Wechatpay-Serial' => $serial,
                'certs'      => $certs,
            ]);

            // attach 参数：aid:tablename:pc:bid
            $attach = $aid . ':' . $tablename . ':pc:' . $bid;

            // 构建V3 Native下单请求参数
            $params = [
                'appid'        => $wxpay_appid,
                'mchid'        => $appinfo['wxpay_mchid'],
                'description'  => $title,
                'out_trade_no' => $ordernum,
                'notify_url'   => $notify_url,
                'attach'       => $attach,
                'amount'       => [
                    'total'    => intval(bcmul($price, 100, 0)),
                    'currency' => 'CNY',
                ],
            ];

            Log::write(['pc_wxpay_v3_native_request' => $params], 'info');

            // 发起V3 Native下单请求
            $resp = $instance->chain('v3/pay/transactions/native')->post(['json' => $params]);
            $statusCode = $resp->getStatusCode();
            $body = json_decode($resp->getBody(), true);

            Log::write(['pc_wxpay_v3_native_response' => ['status_code' => $statusCode, 'body' => $body]], 'info');

            if($statusCode == 200 && !empty($body['code_url'])){
                // 将code_url生成二维码图片
                $qrcodeUrl = createqrcode($body['code_url']);
                return ['status'=>1,'data'=>['pay_wx_qrcode_url'=>$qrcodeUrl]];
            } else {
                $errMsg = $body['message'] ?? '微信V3 Native下单失败';
                return ['status'=>0,'msg'=>$errMsg];
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if($e->hasResponse()){
                $errBody = json_decode($e->getResponse()->getBody(), true);
                Log::write(['pc_wxpay_v3_native_error' => $errBody], 'error');
                return ['status'=>0,'msg'=>$errBody['message'] ?? '微信V3请求异常'];
            }
            Log::write(['pc_wxpay_v3_native_exception' => $e->getMessage()], 'error');
            return ['status'=>0,'msg'=>'微信V3请求异常：'.$e->getMessage()];
        } catch (\Exception $e) {
            Log::write(['pc_wxpay_v3_native_exception' => $e->getMessage()], 'error');
            return ['status'=>0,'msg'=>'微信V3支付异常：'.$e->getMessage()];
        }
    }

	//创建微支付参数H5 QQ小程序
	public static function build_qq($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$set = Db::name('admin_set')->where('aid', $aid)->find();
		$package = array();

		$appinfo = \app\common\System::appinfo($aid,'qq');
		$appid = $appinfo['wxpay_appid'];

		$isbusinesspay = false;
		if($bid > 0){
			$bset = Db::name('business_sysset')->where('aid', $aid)->find();
            $business = Db::name('business')->where('id',$bid)->find();
			if($bset['wxfw_status'] == 1){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$package['appid'] = $bset['wxfw_appid'];
					$package['mch_id'] = $bset['wxfw_mchid'];
					if($appid){
						$package['sub_appid'] = $appid;
					}
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $bset['wxfw_mchkey'];

					$chouchengmoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$chouchengmoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}
					$package['attach'] = $aid.':'.$tablename.':qq:'.$bid;
				}
			}elseif($bset['wxfw_status'] == 2){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
					if(!$dbwxpayset){
						return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
					}
					$package['appid'] = $dbwxpayset['appid'];
					$package['mch_id'] = $dbwxpayset['mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $dbwxpayset['mchkey'];

					$feemoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$feemoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					$chouchengmoney = 0;
					$admindata = Db::name('admin')->where('id',$aid)->find();
					if($admindata['chouchengset']==0){ //默认抽成
						if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
							if($dbwxpayset['chouchengset'] == 1){
								//$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
								$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
								if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
									$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
								}
							}else{
								$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
							}
						}
					}elseif($admindata['chouchengset']==1){ //按比例抽成
						//$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
						$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
						if($chouchengmoney < floatval($admindata['chouchengmin'])){
							$chouchengmoney = floatval($admindata['chouchengmin']);
						}
					}elseif($admindata['chouchengset']==2){ //按固定金额抽成
						$chouchengmoney = floatval($admindata['chouchengmoney']);
					}

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}else{
						$chouchengmoney = 0;
						//if($business['feepercent'] > 0){
						//	$chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
						//}
						$chouchengmoney = $chouchengmoney + $feemoney;

						if($bset['commission_kouchu'] == 1){
							$commission = self::getcommission($tablename,$ordernum);
						}else{
							$commission = 0;
							}

						$chouchengmoney = $chouchengmoney + $commission;
						if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

						if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
							$package['profit_sharing'] = 'Y';
						}
					}
					$package['attach'] = $aid.':'.$tablename.':qq:'.$bid;
				}
			}
		}

		if(!$isbusinesspay){
			if($appinfo['wxpay_type']==0){
				$package['appid'] = $appid;
				$package['mch_id'] = $appinfo['wxpay_mchid'];
				$mchkey = $appinfo['wxpay_mchkey'];
			}else{
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				if(!$dbwxpayset){
					return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
				}
				$package['appid'] = $dbwxpayset['appid'];
				if($appid){
					$package['sub_appid'] = $appid;
				}
				$package['mch_id'] =$dbwxpayset['mchid'];
				$package['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];
				
				$chouchengmoney = 0;
				$admindata = Db::name('admin')->where('id',$aid)->find();
				if($admindata['chouchengset']==0){ //默认抽成
					if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
						if($dbwxpayset['chouchengset'] == 1){
							$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
							if($chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
								$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
							}
						}else{
							$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
						}
					}
				}elseif($admindata['chouchengset']==1){ //按比例抽成
					$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
					if($chouchengmoney < floatval($admindata['chouchengmin'])){
						$chouchengmoney = floatval($admindata['chouchengmin']);
					}
				}elseif($admindata['chouchengset']==2){ //按固定金额抽成
					$chouchengmoney = floatval($admindata['chouchengmoney']);
				}
				if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
					$package['profit_sharing'] = 'Y';
				}
			}
			$package['attach'] = $aid.':'.$tablename.':qq';
		}
        $package['nonce_str'] = random(8);
		$package['body'] = mb_substr($title,0,42);
		$package['out_trade_no'] = $ordernum;
		$package['total_fee'] = bcmul($price, 100, 0);
		$package['spbill_create_ip'] = request()->ip();
		//$package['time_start'] = date('YmdHis', TIMESTAMP);
		//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
		$package['notify_url'] = 'https://api.q.qq.com/wxpay/notify';
		$package['trade_type'] = 'MWEB';
		$package['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "'.PRE_URL.'","wap_name": "'.$set['name'].'"}}';
		//dump($package);
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
		//var_dump($string1);
		//var_dump($package);
		//var_dump($dat);
		$response = request_post('https://api.q.qq.com/wxpay/unifiedorder?appid='.$appinfo['appid'].'&access_token='.\app\common\Qq::access_token($aid).'&real_notify_url='.urlencode($notify_url), $dat);
		//var_dump($response);
		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->return_msg)];
		} 
		if (strval($xml->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->err_code_des)];
		}
		//var_dump($xml);
		//var_dump($xml->mweb_url.'');
		//$prepayid = $xml->prepay_id;
		$wOpt = [];
		$wOpt['wx_url'] = strval($xml->mweb_url);
		$wOpt['referer'] = PRE_URL;
		return ['status'=>1,'data'=>$wOpt];
	}
	public static function build_app($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$package = array();
		$appinfo = \app\common\System::appinfo($aid,'app');
		$appid = $appinfo['appid'];
		
		$isbusinesspay = false;
		if($bid > 0){
			$bset = Db::name('business_sysset')->where('aid', $aid)->find();
            $business = Db::name('business')->where('id',$bid)->find();
			if($bset['wxfw_status'] == 1){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$package['appid'] = $bset['wxfw_appid'];
					$package['mch_id'] = $bset['wxfw_mchid'];
					if($appid){
						$package['sub_appid'] = $appid;
					}
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $bset['wxfw_mchkey'];

					$chouchengmoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$chouchengmoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}
					$package['attach'] = $aid.':'.$tablename.':app:'.$bid;
				}
			}elseif($bset['wxfw_status'] == 2){
				if($business['wxpayst']==1 && $business['wxpay_submchid']){
					$isbusinesspay = true;
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
					if(!$dbwxpayset){
						return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
					}
					$package['appid'] = $dbwxpayset['appid'];
					$package['mch_id'] = $dbwxpayset['mchid'];
					$package['sub_appid'] = $appid;
					$package['sub_openid'] = $openid;
					$package['sub_mch_id'] = $business['wxpay_submchid'];
					$mchkey = $dbwxpayset['mchkey'];

					$feemoney = 0;
					$countprice = $price;//重新赋值，用于计算使用
					if($business['feepercent'] > 0){
						if(false){}else{
							$feemoney = floatval($business['feepercent']) * 0.01 * $countprice;
						}
						}

					$chouchengmoney = 0;
					$admindata = Db::name('admin')->where('id',$aid)->find();
					if($admindata['chouchengset']==0){ //默认抽成
						if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
							if($dbwxpayset['chouchengset'] == 1){
								//$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
								$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
								if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
									$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
								}
							}else{
								$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
							}
						}
					}elseif($admindata['chouchengset']==1){ //按比例抽成
						//$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
						$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
						if($chouchengmoney < floatval($admindata['chouchengmin'])){
							$chouchengmoney = floatval($admindata['chouchengmin']);
						}
					}elseif($admindata['chouchengset']==2){ //按固定金额抽成
						$chouchengmoney = floatval($admindata['chouchengmoney']);
					}

					if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
						$package['profit_sharing'] = 'Y';
					}else{
						$chouchengmoney = 0;
						//if($business['feepercent'] > 0){
						//	$chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
						//}
						$chouchengmoney = $chouchengmoney + $feemoney;

						if($bset['commission_kouchu'] == 1){
							$commission = self::getcommission($tablename,$ordernum);
						}else{
							$commission = 0;
							}

						$chouchengmoney = $chouchengmoney + $commission;
						if($chouchengmoney > $price*0.3) return ['status'=>0,'msg'=>'分账金额过大'];

						if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
							$package['profit_sharing'] = 'Y';
						}
					}
					$package['attach'] = $aid.':'.$tablename.':app:'.$bid;
				}
			}
		}
		if(!$isbusinesspay){
           if($appinfo['wxpay_type']==0){
				$package['appid'] = $appid;
				$package['mch_id'] = $appinfo['wxpay_mchid'];
				$mchkey = $appinfo['wxpay_mchkey'];
			}else{
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				if(!$dbwxpayset){
					return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
				}
				$package['appid'] = $dbwxpayset['appid'];
				if($appid){
					$package['sub_appid'] = $appid;
				}
				$package['mch_id'] =$dbwxpayset['mchid'];
				$package['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];
				
				$chouchengmoney = 0;
				$admindata = Db::name('admin')->where('id',$aid)->find();
				if($admindata['chouchengset']==0){ //默认抽成
					if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
						if($dbwxpayset['chouchengset'] == 1){
							$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $price;
							if($chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
								$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
							}
						}else{
							$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
						}
					}
				}elseif($admindata['chouchengset']==1){ //按比例抽成
					$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $price;
					if($chouchengmoney < floatval($admindata['chouchengmin'])){
						$chouchengmoney = floatval($admindata['chouchengmin']);
					}
				}elseif($admindata['chouchengset']==2){ //按固定金额抽成
					$chouchengmoney = floatval($admindata['chouchengmoney']);
				}
				if($chouchengmoney >= 0.01 && $price*0.3 >= $chouchengmoney){ //需要分账
					$package['profit_sharing'] = 'Y';
				}
			}
			$package['attach'] = $aid.':'.$tablename.':app';
		}
        $package['nonce_str'] = random(8);
		$package['body'] = mb_substr($title,0,42);
		$package['out_trade_no'] = $ordernum;
		$package['total_fee'] = bcmul($price, 100, 0);
		$package['spbill_create_ip'] = request()->ip();
		//$package['time_start'] = date('YmdHis', TIMESTAMP);
		//$package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
		$package['notify_url'] = $notify_url;
		$package['trade_type'] = 'APP';
		//dump($package);
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
		//var_dump($string1);
		//var_dump($package);
		//var_dump($dat);
		$response = request_post('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
		
		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->return_msg)];
		} 
		if (strval($xml->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->err_code_des)];
		}
		//var_dump($xml);
		//var_dump($xml->mweb_url.'');
		$prepayid = strval($xml->prepay_id);
		$wOpt = [];
		$wOpt['appid'] = $appid;
		if($appinfo['wxpay_type']==0){
			$wOpt['partnerid'] = $appinfo['wxpay_mchid'];
		}else{
			$wOpt['partnerid'] = $appinfo['wxpay_sub_mchid'];
		}
		$wOpt['prepayid'] = $prepayid;
		$wOpt['package'] = 'Sign=WXPay';
		$wOpt['noncestr'] = random(8);
		$wOpt['timestamp'] = time()."";
		//$wOpt['signType'] = 'MD5';
		ksort($wOpt, SORT_STRING);
		foreach ($wOpt as $key => $v) {
			$string .= "{$key}={$v}&";
		}
		$string .= "key=".$mchkey;
		$wOpt['sign'] = strtoupper(md5($string));
		return ['status'=>1,'data'=>$wOpt];
	}
	//关闭订单
	public static function closeorder($aid,$ordernum,$platform){
		$appinfo = \app\common\System::appinfo($aid,$platform);
		$appid = $appinfo['appid'];

		$package = [];
		$package['appid'] = $appid;
		$package['mch_id'] = $appinfo['wxpay_mchid'];
		$mchkey = $appinfo['wxpay_mchkey'];
		$package['out_trade_no'] = $ordernum;
		$package['nonce_str'] = random(8);
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v){
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$package['sign'] = strtoupper(md5($string1));
		$dat = array2xml($package);
		$response = request_post('https://api.mch.weixin.qq.com/pay/closeorder', $dat);
		
		$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($xml->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->return_msg)];
		} 
		if (strval($xml->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($xml->err_code_des)];
		}
		return ['status'=>1,'msg'=>'操作成功'];
	}
	//微信退款
	public static function refund($aid,$platform,$ordernum,$totalprice,$refundmoney,$refund_desc='退款',$bid=0,$payorder=[],$refundOrder=[],$otherparams=[]){
		if(!$refund_desc) $refund_desc = '退款';
        //检查支付流水表是否存在当前已支付的订单
        if($pay_transaction = Db::name('pay_transaction')->where(['aid'=>$aid,'ordernum'=>$ordernum,'type'=>$payorder['type'],'status'=>1])->order('id desc')->find()){
            //如果有数据取流水单号发起退款
            $ordernum = $pay_transaction['transaction_num'];
        }
        if($platform =='cashdesk' || $platform =='restaurant_cashdesk'){
            $appinfo = Db::name('admin_setapp_'.$platform)->where('aid', $aid)->where('bid',0)->find();
            if($bid > 0){
                $bappinfo = Db::name('admin_setapp_'.$platform)->where('aid', $aid)->where('bid',$bid)->find();
                if($platform =='cashdesk') {
                    $restaurant_sysset = Db::name('business_sysset')->where('aid', $aid)->find();
                }else{
                    $restaurant_sysset = Db::name('restaurant_admin_set')->where('aid', $aid)->find();
                }
                if(!$restaurant_sysset || $restaurant_sysset['business_cashdesk_wxpay_type'] == 0){
                    return ['status'=>0,'msg'=>'微信收款已禁用'];
                }
                //1:服务商
                if($restaurant_sysset['business_cashdesk_wxpay_type'] == 1){
                    $appinfo['sub_mch_id'] = $bappinfo['wxpay_sub_mchid'];
                    $appinfo['wxpay_type'] = 1;
                    $appinfo['wxpay_sub_mchid'] = $bappinfo['wxpay_sub_mchid'];
                }
                //3：独立收款
                if($restaurant_sysset['business_cashdesk_wxpay_type'] == 3){
                    $appinfo['wxpay_type'] = $bappinfo['wxpay_type'];
                    $appinfo['appid'] = $bappinfo['appid'];
                    $appinfo['wxpay_sub_mchid'] = $bappinfo['wxpay_sub_mchid'];
                    if($bappinfo['wxpay_type']==0){
                        $appinfo['mch_id'] = $bappinfo['wxpay_mchid'];
                        $appinfo['wxpay_mchid'] = $bappinfo['wxpay_mchid'];
                        $appinfo['wxpay_mchkey'] = $bappinfo['wxpay_mchkey'];
                        $appinfo['wxpay_apiclient_cert'] = $bappinfo['wxpay_apiclient_cert'];
                        $appinfo['wxpay_apiclient_key'] = $bappinfo['wxpay_apiclient_key'];
                    }else{
                        $appinfo['sub_mch_id'] = $bappinfo['wxpay_sub_mchid'];
                    }
                }
            }
        }else{
            $appinfo = \app\common\System::appinfo($aid,$platform);
        }
		if($platform == 'qq') $appinfo['appid'] = $appinfo['wxpay_appid'];
        //wxpay_type枚举值汇总 https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwKTcT9kNuQa0MtULMT3
		if($appinfo['wxpay_type'] == 2){
            //二级商户模式 申请退款 https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/ministore/wxafunds/API/order/refunds_order.html
			$paylog = Db::name('wxpay_log')->where('aid', $aid)->where('ordernum',$ordernum)->find();
			$url = 'https://api.weixin.qq.com/shop/pay/refundorder?access_token='.\app\common\Wechat::access_token($aid,'wx');
			$postdata = [];
			$postdata['openid'] = $paylog['openid'];
			$postdata['mchid'] = $appinfo['wxpay_sub_mchid2'];
			$postdata['trade_no'] = $ordernum;
			$postdata['transaction_id'] = $paylog['transaction_id'];
			$postdata['refund_no'] = date('YmdHis').rand(1000,9999);
			$postdata['total_amount'] = bcmul($totalprice, 100, 0);
			$postdata['refund_amount'] = bcmul($refundmoney, 100, 0);
			$rs = curl_post($url,jsonEncode($postdata));
			$rs = json_decode($rs,true);
			if($rs['errcode'] == 0){
				//记录
				$data = [];
				$data['aid'] = $aid;
				$data['mch_id'] = $postdata['mchid'];
				$data['ordernum'] = $ordernum;
				$data['out_refund_no'] = $postdata['refund_no'];
				$data['totalprice'] = $totalprice;
				$data['refundmoney'] = $refundmoney;
				$data['createtime'] = date('Y-m-d H:i:s');
				$data['status'] = 1;
				$data['remark'] = $refund_desc;
				Db::name('wxrefund_log')->insert($data);
				if($paylog){
					Db::name('wxpay_log')->where('id',$paylog['id'])->inc('refund_money',$refundmoney)->update();
				}
				return ['status'=>1,'msg'=>'退款成功'];
			}else{
				return ['status'=>0,'msg'=>\app\common\Wechat::geterror($rs)];
			}
		}
        elseif($appinfo['wxpay_type'] == 8){
            //b2b支付模式 退款 https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/industry/B2b_store_assistant.html#_3-3-%E6%94%AF%E4%BB%98%E4%B8%8E%E9%80%80%E6%AC%BE
            $paylog = Db::name('wxpay_log')->where('aid', $aid)->where('ordernum',$ordernum)->find();
            $url = 'https://api.weixin.qq.com/retail/B2b/refund?access_token='.\app\common\Wechat::access_token($aid,'wx');
            $postdata = [];
            $postdata['mchid'] = $paylog['mch_id'];
            $postdata['out_trade_no'] = $ordernum;
            $postdata['order_id'] = $paylog['transaction_id'];
            $postdata['out_refund_no'] = (string) date('YmdHis').rand(1000,9999);
            $postdata['refund_amount'] = intval(bcmul($refundmoney, 100, 0));
            $postdata['refund_from'] = 1;//退款来源，枚举值 1：人工客服退款 2：用户自己退款 3：其他
//            $postdata['refund_reason'] = 0;//退款原因，枚举值 0：暂无描述 1：产品问题 2：售后问题 3：意愿问题 4：价格问题 5：其他原因
            $appkey = $appinfo['wxpay_b2b_appkey'];//服务端只能用现网appkey!!!，前端哪个都行，对应env即可
            $pay_sig = self::b2b_pay_sig('/retail/B2b/refund',jsonEncode($postdata), $appkey);
            $rs = curl_post($url.'&pay_sig='.$pay_sig, jsonEncode($postdata));
            $rs = json_decode($rs,true);

            if($rs['errcode'] == 0){
                //记录
                $data = [];
                $data['aid'] = $aid;
                $data['mch_id'] = $postdata['mchid'];
                $data['ordernum'] = $ordernum;
                $data['out_refund_no'] = $postdata['out_refund_no'];
                $data['totalprice'] = $totalprice;
                $data['refundmoney'] = $refundmoney;
                $data['createtime'] = date('Y-m-d H:i:s');
                $data['status'] = 1;
                $data['remark'] = $refund_desc;
                Db::name('wxrefund_log')->insert($data);
                if($paylog){
                    Db::name('wxpay_log')->where('id',$paylog['id'])->inc('refund_money',$refundmoney)->update();
                }
                return ['status'=>1,'msg'=>'退款成功'];
            }else{
                return ['status'=>0,'msg'=>\app\common\Wechat::geterror($rs)];
            }
        }
        elseif($appinfo['wxpay_type']==5){
            }
		elseif($appinfo['wxpay_type']==10){
			}
		//是否有分账 分账回退
		$paylog = Db::name('wxpay_log')->where('aid', $aid)->where('ordernum',$ordernum)->find();
		if($paylog && ($paylog['fenzhangmoney'] > 0 || $paylog['fenzhangmoney2'] > 0) && $paylog['isfenzhang'] == 1){
			if($paylog['bid'] == 0){
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
			}else{
				$bset = Db::name('business_sysset')->where('aid', $aid)->find();
				if($bset['wxfw_status']==2){
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
				}else{
					$dbwxpayset = [
						'mchname'=>$bset['wxfw_mchname'],
						'appid'=>$bset['wxfw_appid'],
						'mchid'=>$bset['wxfw_mchid'],
						'mchkey'=>$bset['wxfw_mchkey'],
						'apiclient_cert'=>$bset['wxfw_apiclient_cert'],
						'apiclient_key'=>$bset['wxfw_apiclient_key'],
					];
				}
			}
			$mchkey = $dbwxpayset['mchkey'];
			$sslcert = ROOT_PATH.$dbwxpayset['apiclient_cert'];
			$sslkey = ROOT_PATH.$dbwxpayset['apiclient_key'];
            if(empty($sslcert) || $sslcert == ROOT_PATH || empty($sslkey) || $sslkey == ROOT_PATH){
                return ['status'=>0,'msg'=>'请配置支付证书和密钥'];
            }
			if($paylog['fenzhangmoney'] > 0){
				$pars = array();
				$pars['mch_id'] = $dbwxpayset['mchid'];
				$pars['sub_mch_id'] = $paylog['sub_mchid'];
				$pars['appid'] = $dbwxpayset['appid'];
				$pars['nonce_str'] = random(32);
				$pars['out_order_no'] = $paylog['fz_ordernum'];
				$pars['out_return_no'] = 'R'.date('YmdHis').rand(1000,9999);

				$admin = Db::name('admin')->where('id',$aid)->find();
				if($admin['choucheng_receivertype'] == 0){
					$pars['return_account_type'] = 'MERCHANT_ID';
					$pars['return_account'] = $dbwxpayset['mchid'];
				}elseif($admin['choucheng_receivertype'] == 1){
					$pars['return_account_type'] = 'MERCHANT_ID';
					$pars['return_account'] = $admin['choucheng_receivertype1_account'];
				}elseif($admin['choucheng_receivertype'] == 2){
					if($admin['choucheng_receivertype2_openidtype'] == 0){
						$pars['return_account_type'] = 'PERSONAL_OPENID';
						$pars['return_account'] = $admin['choucheng_receivertype2_account'];
					}else{
						$pars['return_account_type'] = 'PERSONAL_SUB_OPENID';
						$pars['return_account'] = $admin['choucheng_receivertype2_account'];
						if($paylog['platform'] == 'wx'){
							$pars['return_account'] = $admin['choucheng_receivertype2_accountwx'];
						}else{
							$pars['return_account'] = $admin['choucheng_receivertype2_account'];
						}
					}
				}

				//$pars['return_account_type'] = 'MERCHANT_ID';
				//$pars['return_account'] = $dbwxpayset['mchid'];
				$pars['return_amount'] = intval(bcmul($paylog['fenzhangmoney'],100,0));
				$pars['description'] = $refund_desc;
				//$pars['sign_type'] = 'MD5';
				ksort($pars, SORT_STRING);
				$string1 = '';
				foreach ($pars as $k => $v) {
					$string1 .= "{$k}={$v}&";
				}
				$string1 .= "key=" . $mchkey;
				//$pars['sign'] = strtoupper(md5($string1));
				$pars['sign'] = strtoupper(hash_hmac("sha256",$string1 ,$mchkey));
				$xml = array2xml($pars);

				$client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
				$response = $client->request('POST',"https://api.mch.weixin.qq.com/secapi/pay/profitsharingreturn",['body'=>$xml,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
				$info = $response->getBody()->getContents();

				$resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
				//var_dump($resp);
				//\think\facade\Log::write($pars);
				//\think\facade\Log::write($resp);
				if($resp['return_code'] == 'SUCCESS' && $resp['result']=='SUCCESS'){
					Db::name('wxpay_log')->where('aid', $aid)->where('ordernum',$ordernum)->update(['isfenzhang'=>3]);
				}
			}
			if($paylog['fenzhangmoney2'] > 0){
				$pars = array();
				$pars['mch_id'] = $dbwxpayset['mchid'];
				$pars['sub_mch_id'] = $paylog['sub_mchid'];
				$pars['appid'] = $dbwxpayset['appid'];
				$pars['nonce_str'] = random(32);
				$pars['out_order_no'] = $paylog['fz_ordernum'];
				$pars['out_return_no'] = 'R'.date('YmdHis').rand(1000,9999);
				$pars['return_account_type'] = 'MERCHANT_ID';
				$pars['return_account'] = $bset['wxfw2_mchid'];
				$pars['return_amount'] = bcmul($paylog['fenzhangmoney2'], 100, 0);
				$pars['description'] = $refund_desc;
				//$pars['sign_type'] = 'MD5';
				ksort($pars, SORT_STRING);
				$string1 = '';
				foreach ($pars as $k => $v) {
					$string1 .= "{$k}={$v}&";
				}
				$string1 .= "key=" . $mchkey;
				//$pars['sign'] = strtoupper(md5($string1));
				$pars['sign'] = strtoupper(hash_hmac("sha256",$string1 ,$mchkey));
				$xml = array2xml($pars);

				$client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
				$response = $client->request('POST',"https://api.mch.weixin.qq.com/secapi/pay/profitsharingreturn",['body'=>$xml,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
				$info = $response->getBody()->getContents();

				$resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
				//\think\facade\Log::write($pars);
				//\think\facade\Log::write($resp);
				if($resp['return_code'] == 'SUCCESS' && $resp['result']=='SUCCESS'){
					Db::name('wxpay_log')->where('aid', $aid)->where('ordernum',$ordernum)->update(['isfenzhang'=>3]);
				}
			}
		}
		$pars = array();
		if($paylog['bid'] > 0){
			$bset = Db::name('business_sysset')->where('aid', $aid)->find();
			if($bset['wxfw_status']==2){
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
			}else{
				$dbwxpayset = [
					'mchname'=>$bset['wxfw_mchname'],
					'appid'=>$bset['wxfw_appid'],
					'mchid'=>$bset['wxfw_mchid'],
					'mchkey'=>$bset['wxfw_mchkey'],
					'apiclient_cert'=>$bset['wxfw_apiclient_cert'],
					'apiclient_key'=>$bset['wxfw_apiclient_key'],
				];
			}
			$pars['appid'] = $dbwxpayset['appid'];
			$pars['sub_appid'] = $appinfo['appid'];
			$pars['mch_id'] = $dbwxpayset['mchid'];
			$pars['sub_mch_id'] = $paylog['sub_mchid'];
			$mchkey = $dbwxpayset['mchkey'];
			$sslcert = ROOT_PATH.$dbwxpayset['apiclient_cert'];
			$sslkey = ROOT_PATH.$dbwxpayset['apiclient_key'];
		}
        else{
            if($appinfo['wxpay_type']==0){
				$pars['appid'] = $appinfo['appid'];
				$pars['mch_id'] = $appinfo['wxpay_mchid'];
				$mchkey = $appinfo['wxpay_mchkey'];
				$sslcert = ROOT_PATH.$appinfo['wxpay_apiclient_cert'];
				$sslkey = ROOT_PATH.$appinfo['wxpay_apiclient_key'];
				
				/*
				if($appinfo['wxpay_serial_no']){
					if(!$ordernum) $ordernum = date('ymdHis') .$aid. rand(1000, 9999);
					$params = [];
					$params['out_trade_no'] = $ordernum;
					$params['out_refund_no'] = $ordernum. '_' . rand(1000, 9999);
					$params['reason'] = $refund_desc;
					$params['amount'] = [
						'refund'=>$refundmoney * 100,
						'total'=>$totalprice * 100,
						'currency'=>'CNY',
					];
					$rs = self::request_v3('https://api.mch.weixin.qq.com/v3/refund/domestic/refunds','POST',$appinfo['wxpay_mchid'],$appinfo['wxpay_serial_no'],$sslkey,$params);
					return $rs;
				}
				*/
			}elseif($appinfo['wxpay_type']==3){
				if(!$payorder){
                    $payorder = Db::name('payorder')->where('ordernum',$ordernum)->where('status',1)->where('aid', $aid)->find();
                }
                if($payorder){
                    //检查支付流水表是否存在当前已支付的订单
                    $pay_transaction = Db::name('pay_transaction')->where(['aid'=>$aid,'ordernum'=>$ordernum,'type'=>$payorder['type'],'status'=>1])->order('id desc')->find();
                    if($pay_transaction){
                        //如果有数据取流水单号发起退款
                        $ordernum = $pay_transaction['transaction_num'];
                    }
                }
				$rs = \app\custom\Sxpay::refund($aid,$platform,$ordernum,$totalprice,$refundmoney,$refund_desc,$bid);
				return $rs;
            }elseif($appinfo['wxpay_type']==4){
                }elseif($appinfo['wxpay_type']==6){
                }else{
                $dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
                $dbwxpayset = json_decode($dbwxpayset,true);
			    if($bid > 0 && ($platform =='cashdesk' || $platform =='restaurant_cashdesk')){
                    $bset = Db::name('business_sysset')->where('aid', $aid)->find();
                    if($bset['wxfw_status']==2){
                        $dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
                        $dbwxpayset = json_decode($dbwxpayset,true);
                    }else{
                        $dbwxpayset = [
                            'mchname'=>$bset['wxfw_mchname'],
                            'appid'=>$bset['wxfw_appid'],
                            'mchid'=>$bset['wxfw_mchid'],
                            'mchkey'=>$bset['wxfw_mchkey'],
                            'apiclient_cert'=>$bset['wxfw_apiclient_cert'],
                            'apiclient_key'=>$bset['wxfw_apiclient_key'],
                        ];
                    }
                }
				$pars['appid'] = $dbwxpayset['appid'];
				$pars['sub_appid'] = $appinfo['appid'];
				$pars['mch_id'] = $dbwxpayset['mchid'];
				$pars['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];
				$sslcert = ROOT_PATH.$dbwxpayset['apiclient_cert'];
				$sslkey = ROOT_PATH.$dbwxpayset['apiclient_key'];
			}
		}
		// 海外版
        if(empty($sslcert) || $sslcert == ROOT_PATH || empty($sslkey) || $sslkey == ROOT_PATH){
            return ['status'=>0,'msg'=>'请配置支付证书和密钥'];
        }
		$pars['nonce_str'] = random(32);
		$pars['out_trade_no'] = $ordernum;
		$pars['out_refund_no'] = $ordernum. '_' . rand(1000, 9999);
		$pars['total_fee'] = bcmul($totalprice, 100, 0);
		$pars['refund_fee'] = bcmul($refundmoney, 100, 0);
		$pars['refund_desc'] = $refund_desc;
		ksort($pars, SORT_STRING);
		$string1 = '';
		foreach ($pars as $k => $v) {
			$string1 .= "{$k}={$v}&";
		} 
		$string1 .= "key=" . $mchkey;
		$pars['sign'] = strtoupper(md5($string1));
		$xml = array2xml($pars);
		
        //小程序支付 申请退款 https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_4
		$client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
		$response = $client->request('POST',"https://api.mch.weixin.qq.com/secapi/pay/refund",['body'=>$xml,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
		$info = $response->getBody()->getContents();

		$resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
		if($resp['return_code'] == 'SUCCESS' && $resp['result_code']=='SUCCESS'){
			//记录
			$data = [];
			$data['aid'] = $aid;
			$data['mch_id'] = $pars['mch_id'];
			$data['ordernum'] = $ordernum;
			$data['out_refund_no'] = $pars['out_refund_no'];
			$data['totalprice'] = $totalprice;
			$data['refundmoney'] = $refundmoney;
			$data['createtime'] = date('Y-m-d H:i:s');
			$data['status'] = 1;
			$data['remark'] = $refund_desc;
			Db::name('wxrefund_log')->insert($data);
			if($paylog){
				Db::name('wxpay_log')->where('id',$paylog['id'])->inc('refund_money',$refundmoney)->update();
			}
			return ['status'=>1,'msg'=>'退款成功','resp'=>$resp];
		}else{
            \think\facade\Log::error([
                'file'=>__FILE__,
                'line'=>__LINE__,
                'pars'=>json_encode($pars),
                'resp'=>$resp
            ]);
			$msg = '未知错误';
			if ($resp['return_code'] == 'FAIL') {
				$msg = $resp['return_msg'];
			} 
			if ($resp['result_code'] == 'FAIL') {
				$msg = $resp['err_code_des'];
			}
			//记录
			$data = [];
			$data['aid'] = $aid;
			$data['mch_id'] = $pars['mch_id'];
			$data['ordernum'] = $ordernum;
			$data['out_refund_no'] = $pars['out_refund_no'];
			$data['totalprice'] = $totalprice;
			$data['refundmoney'] = $refundmoney;
			$data['createtime'] = date('Y-m-d H:i:s');
			$data['status'] = 2;
			$data['remark'] = $refund_desc;
			$data['errmsg'] = $msg;
			Db::name('wxrefund_log')->insert($data);
			return ['status'=>0,'msg'=>$msg,'resp'=>$resp];
		}
	}
	//发红包 https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon_xcx.php?chapter=18_4&index=1
	public static function sendredpackage($aid,$mid,$platform,$money,$act_name='微信红包',$send_name='微信红包',$wishing='恭喜发财',$remark='微信红包',$scene_id='',$log=[]){
		if(!$aid || !$mid || !$money) return ['status'=>0,'msg'=>'参数错误'];
		
		if($platform == 'wx'){
			$openid = Db::name('member')->where('id',$mid)->value('wxopenid');
			$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendminiprogramhb';
			$appinfo = \app\common\System::appinfo($aid,'wx');
		}else{
			$openid = Db::name('member')->where('id',$mid)->value('mpopenid');
			$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
			$appinfo = \app\common\System::appinfo($aid,'mp');
		}

		$package = array();
		$package['wxappid'] = $appinfo['appid'];
		$package['mch_id'] = $appinfo['wxpay_mchid'];
		$mchkey = $appinfo['wxpay_mchkey'];
		$sslcert = ROOT_PATH.$appinfo['wxpay_apiclient_cert'];
		$sslkey = ROOT_PATH.$appinfo['wxpay_apiclient_key'];
		//dump($sslkey);
		$ordernum = $log['ordernum'] ?? date('ymdHis').$aid.rand(1000, 9999);//单号相同为再次发放
		$package['mch_billno'] = $ordernum;
		$package['send_name'] = $send_name;	//商户名称 红包发送者名称
		$package['re_openid'] = $openid;	//用户openid
		$package['total_amount'] = bcmul($money, 100, 0);	//付款金额
		$package['total_num'] = 1;	//红包发放总人数
		$package['wishing'] = $wishing;//红包祝福语
		//$package['client_ip'] = '127.0.0.1';
		$package['act_name'] = mb_substr($act_name,0,30);//活动名称
		$package['remark'] = $remark;  //备注信息
		if($scene_id){
			$package['scene_id'] = $scene_id;  //场景id
		}
		if($platform == 'wx'){
			$package['notify_way'] = 'MINI_PROGRAM_JSAPI';
		}
		$nonce_str = '';
		$str = '1234567890abcdefghijklmnopqrstuvwxyz';
		for($i=0;$i<30;$i++){
			$j=rand(0,35);
			$nonce_str .= $str[$j];
		}
		$package['nonce_str'] = $nonce_str;//随机字符串，不长于32位
		ksort($package, SORT_STRING);
		$string1 = '';
		foreach ($package as $key => $v) {
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key={$mchkey}";
		$package['sign'] = strtoupper(md5($string1));
		$xml = array2xml($package);
		//dump($package);

		$client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
		$response = $client->request('POST',$url,['body'=>$xml,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
		$info = $response->getBody()->getContents();

		$resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
		//dump($resp);die;
		if($resp['return_code'] == 'SUCCESS' && $resp['result_code']=='SUCCESS'){
			//记录
			$data = [];
			$data['aid'] = $aid;
			$data['openid'] = $openid;
			$data['money'] = $money;
			$data['appid'] = $appinfo['appid'];
			$data['mchid'] = $appinfo['wxpay_mchid'];
			$data['ordernum'] = $ordernum;
			$data['createtime'] = date('Y-m-d H:i:s');
			$data['status'] = 1;
			$data['remark'] = '发送成功';
            $data['platform'] = $platform;
			Db::name('sendredpack_log')->insert($data);
			return ['status'=>1,'msg'=>'发送成功','resp'=>$resp];
		}else{
			$msg = '未知错误';
			if ($resp['return_code'] == 'FAIL') {
				$msg = $resp['return_msg'];
			} 
			if ($resp['result_code'] == 'FAIL') {
				$msg = $resp['err_code_des'];
			}
			//记录
			$data = [];
			$data['aid'] = $aid;
			$data['openid'] = $openid;
			$data['money'] = $money;
			$data['appid'] = $appinfo['appid'];
			$data['mchid'] = $appinfo['wxpay_mchid'];
			$data['ordernum'] = $ordernum;
			$data['createtime'] = date('Y-m-d H:i:s');
			$data['status'] = 2;
			$data['remark'] = $msg;
            $data['platform'] = $platform;
			Db::name('sendredpack_log')->insert($data);
			return ['status'=>0,'msg'=>$msg,'resp'=>$resp];
		}
	}
    //查询红包信息 https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon_xcx.php?chapter=18_6&index=5
    public static function gethbinfo($log){
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';
        if($log['platform']){
            $platform = $log['platform'];
        }else{
            $admin_setapp_wx = Db::name('admin_setapp_wx')->where('aid',$log['aid'])->where('wxpay_mchid',$log['mchid'])->find();
            if($admin_setapp_wx){
                $platform = 'wx';
            }else{
                $platform = 'mp';
            }
        }

        $appinfo = \app\common\System::appinfo($log['aid'],$platform);
        $mchkey = $appinfo['wxpay_mchkey'];
        $sslcert = ROOT_PATH.$appinfo['wxpay_apiclient_cert'];
        $sslkey = ROOT_PATH.$appinfo['wxpay_apiclient_key'];

        $package = array();
        $package['appid'] = $log['appid'];
        $package['mch_id'] = $log['mchid'];
        $package['mch_billno'] = $log['ordernum'];
        $package['bill_type'] = 'MCHT';
        $nonce_str = '';
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        for($i=0;$i<30;$i++){
            $j=rand(0,35);
            $nonce_str .= $str[$j];
        }
        $package['nonce_str'] = $nonce_str;//随机字符串，不长于32位
        ksort($package, SORT_STRING);
        $string1 = '';
        foreach ($package as $key => $v) {
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $string1 .= "key={$mchkey}";
        $package['sign'] = strtoupper(md5($string1));
        $xml = array2xml($package);
        //dump($package);

        $client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
        $response = $client->request('POST',$url,['body'=>$xml,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
        $info = $response->getBody()->getContents();

        $resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
        return $resp;
    }

	//企业付款到零钱，商家转账到零钱需要证书
	public static function transfers($aid,$mid,$money,$ordernum='',$platform='wx',$desc='打款'){
//		$set = Db::name('admin_set')->where('aid', $aid)->find();
		if(!$platform){
			$openid = Db::name('member')->where('id',$mid)->value('mpopenid');
			if(!$openid){
				$platform = 'wx';
			}else{
				$platform = 'mp';
			}
		}
		if($platform == 'wx'){ //小程序
			$openid = Db::name('member')->where('id',$mid)->value('wxopenid');
			$appinfo = \app\common\System::appinfo($aid,'wx');
            if(empty($appinfo)) {
                $openid = Db::name('member')->where('id',$mid)->value('mpopenid');
                $appinfo = \app\common\System::appinfo($aid,'mp');
            }
		}else{ //公众号网页
			$openid = Db::name('member')->where('id',$mid)->value('mpopenid');
			$appinfo = \app\common\System::appinfo($aid,'mp');
            if(empty($appinfo)) {
                $openid = Db::name('member')->where('id',$mid)->value('wxopenid');
                $appinfo = \app\common\System::appinfo($aid,'wx');
            }
		}
		if(!$openid) return ['status'=>0,'msg'=>'未查找到'.t('会员').'openid'];
        if(empty($appinfo)) return ['status'=>0,'msg'=>'请先配置微信公众号或者微信小程序支付'];
        if(!$ordernum) $ordernum = date('ymdHis') .$aid. rand(1000, 9999);
        if(empty($appinfo['wxpay_apiclient_key'])) return ['status'=>0,'msg'=>'请在微信支付设置中设置证书密钥'];
        $sslcert = ROOT_PATH.$appinfo['wxpay_apiclient_cert'];//PEM证书
		$sslkey = ROOT_PATH.$appinfo['wxpay_apiclient_key'];//证书密钥
        if(!file_exists($sslkey)) return ['status'=>0,'msg'=>'证书密钥文件不存在，请在微信支付设置中设置证书密钥'];

        if($appinfo['wxpay_serial_no']){
            //证书序列号 商家转账到零钱 介绍 https://pay.weixin.qq.com/docs/merchant/products/batch-transfer-to-balance/introduction.html,api文档 https://pay.weixin.qq.com/docs/merchant/apis/batch-transfer-to-balance/transfer-batch/initiate-batch-transfer.html
            if(empty($appinfo['wxpay_serial_no'])) return ['status'=>0,'msg'=>'请在微信支付设置中设置证书序列号'];
			$params = [];
			$params['appid'] = $appinfo['appid'];
			$params['out_batch_no'] = (string) $ordernum;
			$params['batch_name'] = $desc;
			$params['batch_remark'] = $desc;
			$params['total_amount'] = intval(bcmul($money,100,0));
			$params['total_num'] = 1;
			$params['transfer_detail_list'] = [[
				'out_detail_no'=>$ordernum,
				'transfer_amount'=>intval(bcmul($money,100,0)),
				'transfer_remark'=>$desc,
				'openid'=>$openid,
			]];
            $params['notify_url'] = PRE_URL.'/notify.php';
            Log::write([
                'file'=>__FILE__.__LINE__,
                'params'=>jsonEncode($params)
            ]);
			$rs = self::request_v3('https://api.mch.weixin.qq.com/v3/transfer/batches','POST',$appinfo['wxpay_mchid'],$appinfo['wxpay_serial_no'],$sslkey,$params);
			//$rs = self::request_v3('https://api.mch.weixin.qq.com/v3/certificates','GET',$appinfo['wxpay_mchid'],$appinfo['wxpay_serial_no'],$sslkey);
            Log::write([
                'rs'=>jsonEncode($rs)
            ]);
            //todo 存在二次审核情况 需要优化并处理回调
            if($rs['status'] == 1){
				$rs['msg'] = '打款成功';
			}
			return $rs;
		}

        if(empty($appinfo['wxpay_apiclient_cert'])) return ['status'=>0,'msg'=>'请在微信支付设置中设置PEM证书'];
        if(!file_exists($sslcert)) return ['status'=>0,'msg'=>'PEM证书文件不存在，请在微信支付设置中设置PEM证书'];

		$pars = array();
		$pars['mch_appid'] = $appinfo['appid'];
		$pars['mchid'] = $appinfo['wxpay_mchid'];
		$pars['nonce_str'] = random(32);
		$pars['partner_trade_no'] = $ordernum;

		$pars['openid'] = $openid;
		$pars['check_name'] = 'NO_CHECK';
		$pars['amount'] = intval(bcmul($money,100,0));
		$pars['desc'] = $desc;
		$pars['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];
		ksort($pars, SORT_STRING);
		$string1 = '';
		foreach ($pars as $k => $v) {
			$string1 .= "{$k}={$v}&";
		} 
		$string1 .= "key=" . $appinfo['wxpay_mchkey'];
		$pars['sign'] = strtoupper(md5($string1));
		$xml = array2xml($pars);

        //企业付款至用户零钱 https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_1
		$client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
		$response = $client->request('POST','https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers',['body'=>$xml,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
		$info = $response->getBody()->getContents();

		$resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));

		if($resp['return_code'] == 'SUCCESS' && $resp['result_code']=='SUCCESS'){
			return ['status'=>1,'msg'=>'打款成功','resp'=>$resp];
		}else{
            Log::write([
                'file'=>__FILE__.__LINE__,
                'transfers'=>$pars,
                'resp' => $resp
            ]);
			$msg = '未知错误';
			if ($resp['return_code'] == 'FAIL') {
				$msg = $resp['return_msg'];
			} 
			if ($resp['result_code'] == 'FAIL') {
				$msg = $resp['err_code_des'];
			}
			return ['status'=>0,'msg'=>$msg,'resp'=>$resp];
		}
	}
	//v3接口请求
	public static function request_v3($url,$method,$mchid,$serial_no,$sslkey,$params=[]){
        if(empty($sslkey)) return ['status'=>0,'msg'=>'请在微信支付设置中设置证书密钥'];
        if(empty($serial_no)) return ['status'=>0,'msg'=>'请在微信支付设置中设置证书序列号'];
		if($method == 'POST'){
			$body = jsonEncode($params);
		}else{
			$body = '';
		}
		$timestamp = time();
		$nonce = random(16);
		$url_parts = parse_url($url);
		$canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
		$message = $method."\n".
			$canonical_url."\n".
			$timestamp."\n".
			$nonce."\n".
			$body."\n";
		$mch_private_key = file_get_contents($sslkey);
		//var_dump($message);
		//var_dump($mch_private_key);
		openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
		$sign = base64_encode($raw_sign);
		$schema = 'WECHATPAY2-SHA256-RSA2048';
		$token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',$mchid, $nonce, $timestamp, $serial_no, $sign);
		$headers = [];
		$headers[] = "Content-Type: application/json";
		$headers[] = "Accept: application/json";
		$headers[] = "Authorization: {$schema} {$token}";
        $headers[] = 'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36';
		//$headers[] = "Wechatpay-Serial: ";

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url);
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		if($method == 'POST'){
			curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, $body);
		}
		curl_setopt ( $ch, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		$info = curl_exec ( $ch );
		if (curl_errno ( $ch )) {
			return ['status'=>0,'msg'=>curl_error($ch)];
		}
		curl_close ( $ch );
		$resp = json_decode($info,true);
		//var_dump($url);
		//var_dump($headers);
		if(!$resp){
            Log::write([
                'file'=>__FILE__.__LINE__,
                'params'=>$params,
                'resp' => $info
            ]);
			return ['status'=>0,'msg'=>'未知错误','info'=>$info];
		}elseif($resp['code']){
            Log::write([
                'file'=>__FILE__.__LINE__,
                'params'=>$params,
                'resp' => $info
            ]);
			return ['status'=>0,'msg'=>$resp['message'],'resp'=>$resp,'params'=>$params];
		}else{
			return ['status'=>1,'msg'=>'请求成功','resp'=>$resp];
		}
	}
	//获取订单的佣金
	public static function getcommission($type,$ordernum){
		$payorder = Db::name('payorder')->where('type',$type)->where('ordernum',$ordernum)->find();
		\think\facade\Log::write($payorder);
		$totalcommission = Db::name('member_commission_record')->where('type',$type)->where('orderid',$payorder['orderid'])->sum('commission');
		\think\facade\Log::write($totalcommission);
		if(!$totalcommission || $totalcommission <=0) $totalcommission = 0;
		return $totalcommission;
	}

    //单独获取拼团团中团订单的佣金
    public static function getcommission_teaminteam($type,$ordernum){
        }

    /**
     * pay_sig  支付签名算法 B2B
     * @param  string  $uri  当前请求的API的uri部分
     * @param  string  $post_body  http POST的数据包体
     * @param  string  $appkey  对应环境的AppKey 可通过小程序MP查看：门店助手 -> 支付管理 -> 商户号管理查看详情->基本配置中的沙箱AppKey和现网AppKey。注意：记得根据env值选择不同AppKey，env = 0对应现网AppKey，env = 1对应沙箱AppKey    获得方式相同，服务器API只用现网AppKey，不区分环境
     * @return string 支付请求签名pay_sig
     */
    private function b2b_pay_sig($uri, $post_body, $appkey) {
        $need_sign_msg = $uri . '&' . $post_body;
        return hash_hmac('sha256', $need_sign_msg, $appkey);
    }
    /**
     * 用户登录态signature签名算法
     *
     * @param string $post_body http POST的数据包体
     * @param string $session_key 当前用户有效的session_key
     * @return string 用户登录态签名signature
     */
    private function b2b_signature($post_body, $session_key) {
        return hash_hmac('sha256', $post_body, $session_key);
    }

    //查询订单待分账金额 文档 https://pay.weixin.qq.com/wiki/doc/api/allocation_sl.php?chapter=25_10&index=7
    public static function fenzhangQuery($mchid,$mchkey,$transaction_id)
    {
        $pars = [];
        $pars['mch_id'] = $mchid;
        $pars['nonce_str'] = random(32);
        $pars['transaction_id'] = $transaction_id;
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach ($pars as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key=" . $mchkey;
        $pars['sign'] = strtoupper(hash_hmac("sha256",$string1 ,$mchkey));
        $dat = array2xml($pars);
        $client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
        $response = $client->request('POST',"https://api.mch.weixin.qq.com/pay/profitsharingorderamountquery",['body'=>$dat]);
        $info = $response->getBody()->getContents();

        $resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
        //Log::write($resp);
        if($resp['return_code'] == 'SUCCESS' && $resp['result_code']=='SUCCESS'){
            $msg = '订单剩余待分金额：'.$resp['unsplit_amount'].'分';
            return ['status'=>1,'msg'=>$msg,'resp'=>$resp];
        }else{
            $msg = '未知错误';
            if ($resp['return_code'] == 'FAIL') {
                $msg = $resp['return_msg'];
            }
            if ($resp['result_code'] == 'FAIL') {
                $msg = $resp['err_code_des'];
            }
            return ['status'=>0,'msg'=>$msg,'resp'=>$resp];
        }
    }

    //获取商家转账失败原因
    public static function transfer_fail_reason_msg($reason){
        $reason_arr = [
            "ACCOUNT_FROZEN" => "该用户账户被冻结",
            "ACCOUNT_NOT_EXIST" => "该用户账户不存在",
            "BANK_CARD_ACCOUNT_ABNORMAL" => "银行卡已被销户、冻结、作废、挂失等致无法入账",
            "BANK_CARD_BANK_INFO_WRONG" => "登记的银行名称或分支行信息有误",
            "BANK_CARD_CARD_INFO_WRONG" => "银行卡户名或卡号有误",
            "BANK_CARD_COLLECTIONS_ABOVE_QUOTA" => "银行卡属二/三类卡，达到收款限额无法入账",
            "BANK_CARD_PARAM_ERROR" => "用户收款卡错误，请核实信息",
            "BANK_CARD_STATUS_ABNORMAL" => "银行卡状态异常，无法入账",
            "BLOCK_B2C_USERLIMITAMOUNT_BSRULE_MONTH" => "超出用户月转账收款限额，本月不支持继续向该用户付款",
            "BLOCK_B2C_USERLIMITAMOUNT_MONTH" => "用户账户存在风险收款受限，本月不支持继续向该用户付款",
            "DAY_RECEIVED_COUNT_EXCEED" => "超过用户日收款次数，核实产品设置是否准确",
            "DAY_RECEIVED_QUOTA_EXCEED" => "超过用户日收款额度，核实产品设置是否准确",
            "EXCEEDED_ESTIMATED_AMOUNT" => "转账金额超过预约金额范围，请检查",
            "ID_CARD_NOT_CORRECT" => "收款人身份证校验不通过，请核实信息",
            "MERCHANT_REJECT" => "商户员工（转账验密人）已驳回转账",
            "MERCHANT_NOT_CONFIRM" => "商户员工（转账验密人）超时未验密",
            "NAME_NOT_CORRECT" => "收款人姓名校验不通过，请核实信息",
            "OPENID_INVALID" => "OpenID格式错误或者不属于商家公众账号",
            "OTHER_FAIL_REASON_TYPE" => "其它失败原因",
            "OVERDUE_CLOSE" => "超过系统重试期，系统自动关闭",
            "PAYEE_ACCOUNT_ABNORMAL" => "用户账户收款异常，请联系用户完善其在微信支付的身份信息以继续收款",
            "PAYER_ACCOUNT_ABNORMAL" => "商户账户付款受限，可前往商户平台获取解除功能限制指引",
            "PRODUCT_AUTH_CHECK_FAIL" => "未开通该权限或权限被冻结，请核实产品权限状态",
            "REALNAME_ACCOUNT_RECEIVED_QUOTA_EXCEED" => "用户账户收款受限，请引导用户在微信支付查看详情",
            "REAL_NAME_CHECK_FAIL" => "收款人未实名认证，需要用户完成微信实名认证",
            "RECEIVE_ACCOUNT_NOT_CONFIGURE" => "请前往商户平台-商家转账到零钱-前往功能-转账场景中添加",
            "RESERVATION_INFO_NOT_MATCH" => "转账信息，如用户OpenID等参数，与预约时传入的信息不一致，请检查",
            "RESERVATION_SCENE_NOT_MATCH" => "该预约单的转账场景与发起转账时传入的不同，请检查",
            "RESERVATION_STATE_INVALID" => "预约转账单状态异常，请检查",
            "TRANSFER_QUOTA_EXCEED" => "超过用户单笔收款额度，核实产品设置是否准确",
            "TRANSFER_REMARK_SET_FAIL" => "转账备注设置失败， 请调整后重新再试",
            "TRANSFER_RISK" => "该笔转账可能存在风险，已被微信拦截",
            "TRANSFER_SCENE_INVALID" => "你尚未获取该转账场景，请确认转账场景ID是否正确",
            "TRANSFER_SCENE_UNAVAILABLE" => "该转账场景暂不可用，请确认转账场景ID是否正确"
        ];
        if(isset($reason_arr[$reason])){
            return $reason_arr[$reason].'('.$reason.')';
        }else{
            return $reason;
        }
    }
	
}
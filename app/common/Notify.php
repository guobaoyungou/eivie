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
// | 微支付通知
// +----------------------------------------------------------------------
namespace app\common;
use think\facade\Db;
use think\facade\Log;

use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Util\PemUtil;
use WeChatPay\Formatter;

class Notify
{
	public $member;
	public $givescore=0;

    //如果回调地址支持增加参数就要增加参数paytype=xxx，不支持的情况才依赖其他参数进行判断
	public function index(){
		if($_POST['taskId']){
			$this->kuaidi100();die;
		}
		if($_POST['passback_params'] && $_POST['trade_status']){
			$this->alipay();die;
		}
		if($_POST['returnData'] && $_POST['dealId']){
			$this->baidupay();die;
		}
        if($_POST['mchntUuid'] && $_POST['mid'] && $_POST['invoiceAmount']){
            $this->yunshanfu();die;
        }
        $xml = file_get_contents('php://input');
		if($_SERVER['QUERY_STRING'] && (strpos($_SERVER['QUERY_STRING'],'%3Ayunpay') > 0 || strpos($_SERVER['QUERY_STRING'],'usicd%3DWXMP') > 0)){ //云收银
			$this->yunpay();die;
		}
        if($_SERVER['QUERY_STRING'] && strpos($_SERVER['QUERY_STRING'],'paytype=ysepay') !== false){ //银盛支付
            $this->ysepay();die;
        }
		if($xml && strpos($xml,'%3Aqmpay') > 0){
			$this->qmpay();die;
		}
        if($_SERVER['QUERY_STRING'] && strpos($_SERVER['QUERY_STRING'],'paytype=sxpay') !== false){ //随行付 新的方式
            $this->sxpay();die;
        }
		if($xml && (strpos($xml,':sxpaymp:') > 0 || strpos($xml,':sxpaywx:') > 0 || strpos($xml,':sxpayalipay:') > 0 || strpos($xml,':sxalih5:') > 0 )){
			$this->sxpay();die;
		}
		if($xml && (strpos($xml,'"applicationId":') > 0 && strpos($xml,'"taskType":') > 0)){
			$this->sxaudit();die;
		}
		if($xml && (strpos($xml,':fbpaymp:') > 0 || strpos($xml,':fbpaywx:') > 0 || strpos($xml,':fbpayali:') > 0)){
			$this->fbpay();die;
		}
        if($xml && (strpos($xml,'resp_desc=') == 0 && strpos($xml,'resp_code=') > 0 && strpos($xml,'sign=') > 0 && strpos($xml,'resp_data=') > 0)){
            $this->huifupay();die;
        }
        if($xml && strpos($xml,'allinpay') > 0 && (strpos($xml,'bizContent') > 0 || strpos($xml,'bizUserId') > 0 )){
            $this->allinpayYunst();die;
        }
		if($xml && strpos($xml,'bizData') > 0 && strpos($_SERVER['QUERY_STRING'],'paytype=qilin') !== false){
			$this->qilinshuzi();die;
		}
		if($xml && strpos($xml,'appId') >= 0 && strpos($xml,'bizContent') > 0 && strpos($xml,'name') > 0 && strpos($xml,'requestId') > 0){
            $this->zhiyoubao();die;
        }
         if(getcustom('alipay_fenzhang')){
             $auth_param= input('param.');
//             \think\facade\Log::write([
//                 'file' => __FILE__,
//                 'line' => __LINE__,
//                 'alipay_fenzhang' => $auth_param,
//                 '$_GET' => $_GET,
//                 '$_POST' =>$_POST
//             ]);
             if($auth_param['source'] =='alipay_app_auth' && $auth_param['app_auth_code'] && $auth_param['app_id']){
                 $aid = 0;
                 $bid = 0;
                 $appid = '';
                 if($auth_param['state']){
                     $state = base64_decode($auth_param['state']);
                     $e_state = explode(':',$state);
                     $aid = $e_state[0];
                     $bid = $e_state[1];
                     $appid = $e_state[2];//商户的appid
                 }
                 \app\common\Alipay::appauthcodeToAppauthtoken($aid,$bid,$appid,$auth_param);die;
             }
         }
         // 微信小程序支付海外版
        if(getcustom('wxpay_global') && $xml && $_GET['aid'] && $_GET['wxpaytype'] == 'global'){
            $this->wxpayAbroad($xml);die;
        }
		$ttpost = json_decode($xml,true);
//        \think\facade\Log::write($ttpost);
		if($ttpost && $ttpost['msg_signature'] && $ttpost['type']=='payment'){
			$this->ttpay($ttpost);die;
		}
		
        if($xml && strpos($xml,'prod_mode') > 0 && strpos($xml,'type') > 0 && strpos($xml,'object') && strpos($xml,'data') > 0){
            $this->adapay();die;
        }

        // 微信支付V3回调检测（JSON格式 + Wechatpay-Signature头）
        $header = request()->header();
        if($xml && !empty($header['wechatpay-signature']) && !empty($header['wechatpay-serial'])){
            $jsonBody = json_decode($xml, true);
            if($jsonBody && isset($jsonBody['resource'])){
                $this->wxpayV3Notify($xml, $header);die;
            }
        }
		
		if(!$xml) die('fail');
		libxml_disable_entity_loader(true);
		$msg = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (empty($msg)) {
			exit('fail');
		} 
		if ($msg['result_code'] != 'SUCCESS' || $msg['return_code'] != 'SUCCESS') {
			exit('fail');
		}
		//Log::write($msg);
		$attach = explode(':',$msg['attach']);
		$aid = intval($attach[0]);
		define('aid',$aid);
		$tablename = $attach[1];
		$platform = $attach[2];
		$appinfo = \app\common\System::appinfo($aid,$platform);
		if (!empty($appinfo)) {
			ksort($msg);
			$string1 = '';
			foreach ($msg as $k => $v) {
				if ($v != '' && $k != 'sign') {
					$string1 .= "{$k}={$v}&";
				}
			}
            //0普通模式，1服务商模式，2二级商户模式，3随行付
			if($appinfo['wxpay_type'] == 1){
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				$mchkey = $dbwxpayset['mchkey'];
			}else{
				$mchkey = $appinfo['wxpay_mchkey'];
			}
			if($attach[3]){
				$bid = $attach[3];
				$bset = Db::name('business_sysset')->where('aid',$aid)->find();
				if($bset['wxfw_status'] == 1){
					$mchkey = $bset['wxfw_mchkey'];
				}elseif($bset['wxfw_status'] == 2){//使用平台服务商
					$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
					$dbwxpayset = json_decode($dbwxpayset,true);
					$mchkey = $dbwxpayset['mchkey'];
				}
			}else{
				$bid = 0;
			}
			$sign = strtoupper(md5($string1 . "key={$mchkey}"));
			if($sign == $msg['sign']){
                $trade_no = explode('D', $msg['out_trade_no']);
                $payorder = Db::name('payorder')->where(['aid'=>$aid,'type'=>$tablename,'ordernum'=>$trade_no[0]])->find();

				if($bid){
                    Db::name('payorder')->where(['aid'=>$aid,'type'=>$tablename,'id'=>$payorder['id']])->update(['isbusinesspay'=>1]);
                    if($trade_no[1]){
                        //说明是交易流水
                        Db::name('pay_transaction')->where(['aid'=>$aid,'type'=>$tablename,'payorderid'=>$payorder['id']])->update(['isbusinesspay'=>1]);
                    }
				}

                $paymoney = $msg['total_fee']*0.01;
                //记录
                $data = array();
                $data['aid'] = aid;
                $data['mid'] = $payorder['mid'];
                $data['openid'] = $msg['openid'];
                $data['tablename'] = $tablename;
                $data['givescore'] = $this->givescore;
                $data['ordernum'] = $msg['out_trade_no'];
                $data['mch_id'] = $msg['mch_id'];
                $data['transaction_id'] = $msg['transaction_id'];
                $data['total_fee'] = $paymoney;
                $data['createtime'] = time();
                $data['sub_mchid'] = $msg['sub_mch_id'];
//                $data['fenzhangmoney'] = $chouchengmoney;
//                $data['fenzhangmoney2'] = $chouchengmoney2;
                $data['platform'] = $platform;
                $data['bid'] = $bid;

                //查询是否已存在
                $wxpay_log = Db::name('wxpay_log')->where('transaction_id',$data['transaction_id'])->where('ordernum',$data['ordernum'])->where('mid',$data['mid'])->where('aid',aid)->field('id')->find();
                if($wxpay_log){
                	$paylogid = $wxpay_log['id'];
                	Log::write('wxpay_log重复');
                	Log::write($paylogid);
                	Log::write($data);
                }else{
                	$paylogid = Db::name('wxpay_log')->insertGetId($data);
                }

                $wxpay_typeid = 0;
                if(getcustom('wxpay_native_h5') && $msg['trade_type'] == 'NATIVE'){
                    //微信收款码
                    $wxpay_typeid = 7;
                }
				$rs = $this->setorder($tablename,$msg['out_trade_no'],$msg['transaction_id'],$msg['total_fee'],'微信支付',2,$wxpay_typeid);
				if($rs['status'] == 1){
					$chouchengmoney = 0;
					$chouchengmoney2 = 0;
                    //多商户订单
					if($bid){
                        $business = Db::name('business')->where('id',$bid)->find();
                        //使用平台服务商
						if($bset['wxfw_status'] == 2){
							$paymoney = $msg['total_fee']*0.01;
							$feemoney = 0;

							$countpaymoney = $paymoney;//重新赋值，用于计算使用
							if(getcustom('business_toaccount_type')){
								//商城商品实际到账方式，差额
	                        	$toaccountcha = \app\custom\NotifyCustom::businessToaccountType($aid,$bid,$tablename,$msg['out_trade_no'],$countpaymoney);
	                        	if($toaccountcha>0){
	                        		$countpaymoney -= $toaccountcha;
	                        		$countpaymoney = $countpaymoney>=0?$countpaymoney:0;
	                        	}
		                    }

							if($business['feepercent'] > 0){
								if(getcustom('business_deduct_cost')){
									$paymoney2 = \app\custom\NotifyCustom::deduct_cost($aid,$bid,$tablename,$msg,$countpaymoney);
									$feemoney = floatval($business['feepercent']) * 0.01 * $paymoney2;
								}else{
									$feemoney = floatval($business['feepercent']) * 0.01 * $countpaymoney;
								}
								if(getcustom('business_fee_type')){
									$paymoney3 = \app\custom\NotifyCustom::business_fee_type_money($aid,$bid,$tablename,$msg,$countpaymoney);
									$feemoney = floatval($business['feepercent']) * 0.01 * $paymoney3;
								}
							}

							$admindata = Db::name('admin')->where('id',aid)->find();
							if($admindata['chouchengset']==0){ //默认抽成
								if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
									if($dbwxpayset['chouchengset'] == 1){
										//$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $paymoney;
										$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
										if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
											$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
										}
									}else{
										$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
									}
								}
							}elseif($admindata['chouchengset']==1){ //按比例抽成
								//$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $paymoney;
								$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
								if($chouchengmoney < floatval($admindata['chouchengmin'])){
									$chouchengmoney = floatval($admindata['chouchengmin']);
								}
							}elseif($admindata['chouchengset']==2){ //按固定金额抽成
								$chouchengmoney = floatval($admindata['chouchengmoney']);
							}
							//die;

							$business_feepercent = $business['feepercent'];
							if(getcustom('business_toaccount_type')){
								//商城商品实际到账方式，差额
		                        if($toaccountcha>0){
		                        	$chouchengmoney += $toaccountcha;
		                        }
		                    }
		                    if(getcustom('member_dedamount')){
								//查询商家抵扣让利部分，若开启抽成依赖抵扣金，则需要重置抽成金额
	                        	$resrl = \app\custom\NotifyCustom::deal_paymoneyGivemoney($aid,$bid,$tablename,$msg['out_trade_no'],$countpaymoney);
	                        	if($resrl && $resrl['status'] == 1){
		                    		$chouchengmoney = $resrl['chouchengmoney'];
		                    		$business_feepercent = 0;
		                    	}
		                    }
							if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
								$chouchengmoney = intval(bcmul($chouchengmoney,100,2))/100;//向下取整，防止超过分账比例
							}else{
								$chouchengmoney = 0;
							}
							if($business_feepercent > 0){
								$chouchengmoney2 = $feemoney;
								if($bset['commission_kouchu'] == 1){
									$commission = Wxpay::getcommission($tablename,$msg['out_trade_no']);
								}else{
									$commission = 0;
									if(getcustom('yx_collage_team_in_team')){
										//单独获取拼团团中团订单的佣金
										$commission = Wxpay::getcommission_teaminteam($tablename,$msg['out_trade_no']);
									}
								}
								$chouchengmoney2 = $chouchengmoney2 + $commission;
								if($chouchengmoney2 >= 0.01 && $paymoney*0.3 >= $chouchengmoney2){
									$chouchengmoney2 = intval(bcmul($chouchengmoney2,100,2))/100;
								}else{
									$chouchengmoney2 = 0;
								}
							}
						}else{

							$paymoney = $msg['total_fee']*0.01;
							$countpaymoney = $paymoney;//重新赋值，用于计算使用
							if(getcustom('business_toaccount_type')){
								//商城商品实际到账方式，差额
	                        	$toaccountcha = \app\custom\NotifyCustom::businessToaccountType($aid,$bid,$tablename,$msg['out_trade_no'],$countpaymoney);
	                        	if($toaccountcha>0){
	                        		$countpaymoney -= $toaccountcha;
	                        		$countpaymoney = $countpaymoney>=0?$countpaymoney:0;
	                        	}
		                    }

                            //使用多商户配置的服务商或者关闭
							if($business['feepercent'] > 0){
								if(getcustom('business_deduct_cost')){
									$paymoney2 = \app\custom\NotifyCustom::deduct_cost($aid,$bid,$tablename,$msg,$countpaymoney);
									$chouchengmoney = floatval($business['feepercent']) * 0.01 * $paymoney2;
								}else{
									$chouchengmoney = floatval($business['feepercent']) * 0.01 * $countpaymoney;
								}
								if(getcustom('business_fee_type')){
									$paymoney3 = \app\custom\NotifyCustom::business_fee_type_money($aid,$bid,$tablename,$msg,$countpaymoney);
									$chouchengmoney = floatval($business['feepercent']) * 0.01 * $paymoney3;
								}

								if($bset['commission_kouchu'] == 1){
									$commission = Wxpay::getcommission($tablename,$msg['out_trade_no']);
								}else{
									$commission = 0;
									if(getcustom('yx_collage_team_in_team')){
										//单独获取拼团团中团订单的佣金
										$commission = Wxpay::getcommission_teaminteam($tablename,$msg['out_trade_no']);
									}
								}
								$chouchengmoney = $chouchengmoney + $commission;
							}

							if(getcustom('business_toaccount_type')){
								//商城商品实际到账方式，差额
		                        if($toaccountcha>0){
		                        	$chouchengmoney += $toaccountcha;
		                        }
		                    }
		                    if(getcustom('member_dedamount')){
								//查询商家抵扣让利部分，若开启抽成依赖抵扣金，则需要重置抽成金额
	                        	$resrl = \app\custom\NotifyCustom::deal_paymoneyGivemoney($aid,$bid,$tablename,$msg['out_trade_no'],$countpaymoney);
	                        	if($resrl && $resrl['status'] == 1){
		                    		$chouchengmoney = $resrl['chouchengmoney'];
		                    	}
		                    }
							if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
								$chouchengmoney = intval(bcmul($chouchengmoney,100,2))/100;//向下取整，防止超过分账比例
							}else{
								$chouchengmoney = 0;
							}

							if(getcustom('business_more_account')){
								$wxpays =  json_decode($business['wxpay_submchid_text'],true);	
								$subpays = [];
								foreach($wxpays as $sub){
									$subamount = $sub['feepercent']*$msg['total_fee']*0.01;
									if($subamount >= 0.01 && $msg['total_fee']*0.3 >= $subamount){
										$subamount = intval($subamount);
									}else{
										$subamount = 0;
									}
									$subpays[] = ['submchid'=>$sub['submchid'],'amount'=>$subamount,'subname'=>$sub['subname']];
								}
								$wxpay_submchid_text = jsonEncode($subpays);
							}

						}
						//扣除返现比例
						$queue_feepercent_type = 0;
						$queue_feepercent_allmoney = 0;
						$has_yx_queue_free_collage = 0;
						if(getcustom('yx_queue_free_collage')){
							$has_yx_queue_free_collage = 1;
						}
		   
						if(getcustom('yx_queue_free')){
							$queue_free_set = Db::name('queue_free_set')->where('aid',$aid)->where('bid',0)->find();
								$b_queue_free_set = Db::name('queue_free_set')->where('aid',$aid)->where('bid',$bid)->find();
								$queue_free_set['order_types'] = explode(',',$queue_free_set['order_types']);
								
							if($tablename == 'maidan'){
								if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('maidan',$queue_free_set['order_types'])){
									if($queue_free_set['feepercent_type'] == 1){
										$queue_feepercent_type = 1;
									}
								}
								$order = Db::name('maidan_order')->where('aid', $aid)->where('bid', $bid)->where('id', $payorder['orderid'])->find();								
								if($queue_feepercent_type == 1 && $paymoney >0  && $b_queue_free_set['rate_back'] > 0){
									$chouchengmoney = $chouchengmoney + $paymoney * $b_queue_free_set['rate_back'] * 0.01;
					
								}
							}elseif($tablename == 'shop'){
								$oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$payorder['orderid'])->select()->toArray();
								if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('shop',$queue_free_set['order_types'])){
									if($queue_free_set['feepercent_type'] == 1){
										$queue_feepercent_type = 1;
									}
								}
								foreach($oglist as $og){
									$product = Db::name('shop_product')->where('id',$og['proid'])->where('aid',$aid)->where('bid',$bid)->find();           
									if($product['queue_free_status'] == 1){
										$queue_feepercent_allmoney += $og['real_totalprice'];
									}
								}
								if($queue_feepercent_type == 1 && $paymoney >0 && $queue_feepercent_allmoney > 0 && $b_queue_free_set['rate_back'] > 0){
									$chouchengmoney = $chouchengmoney + $queue_feepercent_allmoney * $b_queue_free_set['rate_back'] * 0.01;
								}
							}elseif($tablename == 'collage' && $has_yx_queue_free_collage){
								$order = Db::name('collage_order')->where('aid', $aid)->where('bid', $bid)->where('id', $payorder['orderid'])->find();
								if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('collage',$queue_free_set['order_types'])){
									if($queue_free_set['feepercent_type'] == 1){
										$queue_feepercent_type = 1;
										$product = Db::name('collage_product')->where('id',$order['proid'])->field('queue_free_status,queue_free_rate_back')->find();
										if($product['queue_free_status'] == 1 && $product['queue_free_rate_back']>=0){
											$b_queue_free_set['rate_back'] =  $product['queue_free_rate_back'];
										}
									}
								}								                        
								if($queue_feepercent_type == 1 && $paymoney > 0 && $b_queue_free_set['rate_back'] > 0){
									$chouchengmoney = $chouchengmoney + $paymoney * $b_queue_free_set['rate_back'] * 0.01;
								}
							}

						}
						$sub_mchid = $business['wxpay_submchid'];
					}else{
						//平台订单 服务商分账
						$chouchengmoney = 0;
						if($appinfo['wxpay_type'] == 1){
							$paymoney = $msg['total_fee']*0.01;
							$admindata = Db::name('admin')->where('id',aid)->find();
							if($admindata['chouchengset']==0){ //默认抽成
								if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
									if($dbwxpayset['chouchengset'] == 1){
										$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $paymoney;
										if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
											$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
										}
									}else{
										$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
									}
								}
							}elseif($admindata['chouchengset']==1){ //按比例抽成
								$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $paymoney;
								if($chouchengmoney < floatval($admindata['chouchengmin'])){
									$chouchengmoney = floatval($admindata['chouchengmin']);
								}
							}elseif($admindata['chouchengset']==2){ //按固定金额抽成
								$chouchengmoney = floatval($admindata['chouchengmoney']);
							}

							if(getcustom('product_fenzhangmoney') && $payorder['type'] == 'shop'){
								$oglist = Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$payorder['orderid'])->select()->toArray();
								$issetfzmoney = false;
								$fzmoney = 0;
								foreach($oglist as $og){
									$product = Db::name('shop_product')->where('id',$og['proid'])->find();
									if($product && $product['product_fenzhangmoney']!=-1){
										$issetfzmoney = true;
										$fzmoney += $product['product_fenzhangmoney'] * $og['num'];
									}
								}
								if($issetfzmoney){
									$chouchengmoney = $fzmoney;
								}
							}

							//die;
							if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
								$chouchengmoney = intval(bcmul($chouchengmoney,100,2))/100;//向下取整，防止超过分账比例
							}else{
								$chouchengmoney = 0;
							}
						}
						$sub_mchid = ($appinfo['wxpay_type'] == 1 ? $appinfo['wxpay_sub_mchid'] : '');
					}

                    //记录
                    $data = array();
                    $data['fenzhangmoney'] = $chouchengmoney;
                    $data['fenzhangmoney2'] = $chouchengmoney2;
                    $data['sub_mchid'] = $sub_mchid;
					if(getcustom('business_more_account')){
					     $data['wxpay_submchid_text'] = $wxpay_submchid_text;
					}
                    Db::name('wxpay_log')->where('id',$paylogid)->update($data);

					\app\common\Member::uplv(aid,mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\common\Wxpay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'],$paymoney,$rs['msg'],$payorder['bid'],$payorder);
                    //die(array2xml(['return_code'=>'FAIL','return_msg'=>'payorder_cancel']));
                    Log::write('wxpay fail','error');
                    exit('fail');
                }
                Log::write($data);
                Log::write($rs);
//                die(array2xml(['return_code'=>'SUCCESS','return_msg'=>'ok']));
				exit('success');
			}else{
                Log::write('wxpay check sign error line:'.__LINE__);
            }
		}
	}
	//微信小程序支付海外版
	private function wxpayAbroad($inBody){

		$aid = $_GET['aid'];
		if(!$aid){
			return;
		}
		define('aid',$aid);
		// $tablename = $_GET['tablename'];
		$appinfo = \app\common\System::appinfo($aid,'wx');
		$header = request()->header();
		// writeLog('微信回调进入'.$aid,'notify_v3');
		// writeLog('回调head数据'.json_encode($header),'notify_v3');
		// writeLog('开始 inbody'.$inBody,'notify_v3');

		// 解密
		$inWechatpaySignature = $header['wechatpay-signature'];
        $inWechatpayTimestamp = $header['wechatpay-timestamp'];
        $inWechatpaySerial = $header['wechatpay-serial'];
        $inWechatpayNonce = $header['wechatpay-nonce'];
        if (!$inWechatpaySignature or !$inWechatpayTimestamp or !$inWechatpaySerial or !$inWechatpayNonce) {
            header("Location:/404.html");
            exit;
        }
        $apiv3Key = $appinfo['wxpay_mchkey_global'];
        $wechatpayCertificate = ROOT_PATH.$appinfo['wxpay_wechatpay_pem_global'];
        $wechatpayCertificate = file_get_contents($wechatpayCertificate);
		$platformPublicKeyInstance = Rsa::from($wechatpayCertificate, Rsa::KEY_TYPE_PUBLIC);
		
		
        // writeLog('签名字符串'.Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),'notify_v3');
        // writeLog('签名'.$inWechatpaySignature,'notify_v3');

		$timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
		$verifiedStatus = Rsa::verify(
		    Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
		    $inWechatpaySignature,
		    $platformPublicKeyInstance
		);
		if ($timeOffsetStatus && $verifiedStatus) {
		    $inBodyArray = (array)json_decode($inBody, true);
		    ['resource' => [
		        'ciphertext'      => $ciphertext,
		        'nonce'           => $nonce,
		        'associated_data' => $aad
		    ]] = $inBodyArray;
		    $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
		    $inBodyResourceArray = (array)json_decode($inBodyResource, true);
		    $resourceData = $inBodyResourceArray;

		    // writeLog('解密'.json_encode($resourceData),'notify_v3');
		    // 处理支付结果
		    $outTradeNo = $resourceData['out_trade_no'];
		    $status = $resourceData['trade_state'];
		    

		    if ($status === 'SUCCESS') {

		    	$trade_no = explode('D', $outTradeNo);
                $payorder = Db::name('payorder')->where(['aid'=>$aid,'ordernum'=>$trade_no[0]])->find();

		        // 支付成功，更新订单状态
		        // echo json_encode(['code' => 'SUCCESS', 'message' => 'OK']);
		    	
				$total_fee = $resourceData['amount']['total']*0.01;
				//记录
				$data = array();
				$data['aid'] = $aid;
				$data['mid'] = $payorder['mid'];
				$data['openid'] = $resourceData['payer']['openid']??'';
				$data['tablename'] = $payorder['type'];
				$data['givescore'] = $this->givescore;
				$data['ordernum'] = $outTradeNo;
				$data['mch_id'] = $resourceData['mchid'];
				$data['transaction_id'] = $resourceData['transaction_id'];
				$data['total_fee'] = $total_fee;
				$data['createtime'] = time();
				$data['platform'] = 'wx';
				$paylogid = Db::name('wxpay_log')->insertGetId($data);

				$rs = $this->setorder($payorder['type'],$outTradeNo,$resourceData['transaction_id'],$resourceData['amount']['total'],'微信海外付',2,9);
				if($rs['status'] == 1){
					\app\common\Member::uplv($aid,$payorder['mid']);
				}
				
	            //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\common\Wxpay::refund($aid,'wx',$payorder['ordernum'],$payorder['money'],$total_fee,$rs['msg'],$payorder['bid'],$payorder);
                    //die(array2xml(['return_code'=>'FAIL','return_msg'=>'payorder_cancel']));
                    Log::write('wxpay fail','error');
                    exit('fail');
                }
                Log::write($data);
                Log::write($rs);
//                die(array2xml(['return_code'=>'SUCCESS','return_msg'=>'ok']));
				exit('success');
		    } else {
		        // 支付失败
		        // echo json_encode(['code' => 'FAIL', 'message' => '支付失败']);
		        Log::write('支付失败 line:'.__LINE__);
		    }

		}else{
			// 签名验证失败
		    // echo json_encode(['code' => 'FAIL', 'message' => '签名验证失败']);
		    Log::write('签名验证失败 line:'.__LINE__);
		}

	}

	//百度支付
	private function baidupay(){
		$msg = $_POST;
		$returnData = json_decode($msg['returnData'],true);
		$attach = explode(':',$returnData['params']);
		$aid = intval($attach[0]);
		define('aid',$aid);
		$tablename = $attach[1];
		$baiduapp = \app\common\System::appinfo($aid,'baidu');
		$result = \app\common\RSASign::checkSign($msg,$baiduapp['pay_publickey']);
		if($result){
			if($msg['status'] == 2){
				$rs = $this->setorder($tablename,$msg['tpOrderId'],$msg['orderId'],$msg['payMoney'],'百度支付',11);
                $paymoney = $msg['payMoney']*0.01;
				if($rs['status'] == 1){
					//记录
					$data = array();
					$data['aid'] = aid;
					$data['mid'] = mid;
					$data['openid'] = '';
					$data['tablename'] = $tablename;
					$data['givescore'] = $this->givescore;
					$data['ordernum'] = $msg['tpOrderId'];
					$data['mch_id'] = $baiduapp['pay_appid'];
					$data['transaction_id'] = $msg['orderId'];
					$data['total_fee'] = $paymoney;
					$data['createtime'] = time();
					$data['userId'] = $msg['userId'];
					Db::name('baidupay_log')->insert($data);
					\app\common\Member::uplv(aid,mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\common\Baidupay::refund($aid,$payorder['mid'],$payorder['ordernum'],$payorder['paynum'],$payorder['money'],$paymoney,$payorder['platform'],$rs['msg']);
                    exit('fail');
                }

				$ret = [];
				$ret['errno'] = 0;
				$ret['msg']   = 'success';
				$ret['data']  = ['isConsumed'=>2];
				exit(json_encode($ret));
			}
		}else{
            Log::write('baidupay check sign error line:'.__LINE__,'error');
			exit('fail');
		}
	}

	//支付宝支付
	private function alipay(){
		$msg = $_POST;
		$attach = explode(':',urldecode($msg['passback_params']));
		$aid = intval($attach[0]);
		define('aid',$aid);
		$tablename = $attach[1];
		$platform = $attach[2];
		$appinfo = \app\common\System::appinfo($aid,$platform);
		if($platform == 'alipay'){
			$appinfo['ali_publickey'] = $appinfo['publickey'];
		}
		$paytype = '支付宝支付';
		if($attach[3] && $attach[3]!=1){
			$appinfo['ali_publickey'] = $appinfo['ali_publickey'.$attach[3]];
			$paytype = $appinfo['alipayname'.$attach[3]];
		}
        if(getcustom('alipay_fenzhang')) {
            $bid = $attach[4];
            if ($bid > 0) {
                $business = Db::name('business')->where('id', $bid)->find();
                $alifw_status = Db::name('business_sysset')->where('aid',$aid)->value('alifw_status');
                if($alifw_status ==1 && $business['alipayst']==1 ){
                    $syset = Db::name('sysset')->where('name','alipayisv')->find();
                    $isv_sysset = json_decode($syset['value'],true);//第三方应用配置
                    $appinfo['ali_publickey'] = $isv_sysset['publickey'];
                }
            }
        }
		//Log::write($msg);
		//Log::write($appinfo);
		require_once(ROOT_PATH.'/extend/aop/AopClient.php');
		$aop = new \AopClient();
		$aop->alipayrsaPublicKey = $appinfo['ali_publickey'];
		$result = $aop->rsaCheckV1($msg,$appinfo['ali_publickey'],$msg['sign_type']);
		if($result){
			if($msg['trade_status'] == 'TRADE_FINISHED' || $msg['trade_status'] == 'TRADE_SUCCESS'){
				$rs = $this->setorder($tablename,$msg['out_trade_no'],$msg['trade_no'],$msg['total_amount']*100,$paytype,3);
				if($rs['status'] == 1){
                    $chouchengmoney = 0;
				    if(getcustom('alipay_fenzhang')){
                        if($bid>0){
                            $business = Db::name('business')->where('id',$bid)->find();
                            $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                            $feepercent = $bset['default_rate'];
                            if($business['feepercent'])$feepercent = $business['feepercent'];
                          
                            if($bset['alifw_status']==1 && $feepercent > 0){
                                if($business['alipayst']==1 ){
                                    Db::name('payorder')->where(['aid'=>$aid,'type'=>$tablename,'ordernum'=>$msg['out_trade_no']])->update(['isbusinesspay'=>1]);
                                    $paymoney = $msg['total_amount'];
                                    if(false){}else{
                                        $chouchengmoney = floatval($feepercent) * 0.01 * $paymoney;
                                    }
                                    if($bset['commission_kouchu'] == 1){
                                        $commission = Wxpay::getcommission($tablename,$msg['out_trade_no']);
                                    }else{
                                        $commission = 0;
                                    }
                                    $chouchengmoney = $chouchengmoney + $commission;
                                    if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
                                        $chouchengmoney = intval(bcmul($chouchengmoney,100,2))/100;//向下取整，防止超过分账比例
                                    }
                                }
                            }
                        }
                    }
					//记录
					$data = array();
					$data['aid'] = aid;
					$data['mid'] = mid;
					$data['openid'] = '';
					$data['tablename'] = $tablename;
					$data['givescore'] = $this->givescore;
					$data['ordernum'] = $msg['out_trade_no'];
					$data['mch_id'] = $msg['app_id'];
					$data['transaction_id'] = $msg['trade_no'];
					$data['total_fee'] = $msg['total_amount'];//单位 元
					$data['createtime'] = time();
                    $data['fenzhangmoney'] = $chouchengmoney;
					Db::name('alipay_log')->insert($data);
					\app\common\Member::uplv(aid,mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\common\Alipay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'], $msg['total_amount'],$rs['msg'],$payorder['bid']);
                    exit('fail');
                }
				exit('success');	
			}
		}else{
            Log::write('alipay check sign error line:'.__LINE__,'error');
			exit('fail');
		}
	}

	//头条支付
	public function ttpay($post){
		//Log::write($post);
		$msg = json_decode($post['msg'],true);
		$extra = json_decode($msg['cp_extra'],true);
		$attach = explode(':',$extra['param']);
		$aid = intval($attach[0]);
		define('aid',$aid);
		$tablename = $attach[1];
		$toutiaoapp = \app\common\System::appinfo($aid,'toutiao');
		$post['token'] = $toutiaoapp['pay_token'];

		$signdata = [];
		$signdata[] = $toutiaoapp['pay_token'];
		$signdata[] = $post['timestamp'];
		$signdata[] = $post['nonce'];
		$signdata[] = $post['msg'];
		sort($signdata,2);
		$signstr = implode('',$signdata);
		$sign = sha1($signstr);
		if($sign == $post['msg_signature']){
			$rs = $this->setorder($tablename,$msg['cp_orderno'],$post['channel_no'],$extra['total_amount'],'抖音小程序支付',12);
            $paymoney = $extra['total_amount'] * 0.01;
			if($rs['status'] == 1){
				//记录
				$data = array();
				$data['aid'] = aid;
				$data['mid'] = mid;
				$data['openid'] = '';
				$data['tablename'] = $tablename;
				$data['givescore'] = $this->givescore;
				$data['ordernum'] = $msg['out_trade_no'];
				$data['mch_id'] = '';
				$data['transaction_id'] = '';
				$data['total_fee'] = $extra['total_amount'];
				$data['createtime'] = time();
				Db::name('toutiaopay_log')->insert($data);
				\app\common\Member::uplv(aid,mid);
			}
            //退款
            if($rs['status'] == 2){
                $payorder = $rs['payorder'];
                \app\common\Ttpay::refund($aid,$payorder['ordernum'],$payorder['money'],$paymoney,$rs['msg']);
                exit('fail');
            }
			exit(json_encode(['err_no'=>0,'err_tips'=>'success']));	
		}else{
            Log::write('ttpay check sign error line:'.__LINE__,'error');
			exit('fail');
		}
	}
	
	//云收银
	private function yunpay(){
		$querystring = urldecode($_SERVER['QUERY_STRING']);
		parse_str($querystring,$querydata);
		//Log::write($querydata);
		if(!$querydata['attach']){
			$payorder = Db::name('payorder')->where('ordernum',$querydata['orderNum'])->find();
			if($querydata['busicd'] == 'WXMP'){
				$aid = intval($payorder['aid']);
				$tablename = $payorder['type'];
				$platform = 'wx';
			}
		}else{
			$attach = explode(':',$querydata['attach']);
			$aid = intval($attach[0]);
			$tablename = $attach[1];
			$platform = $attach[2];
		}
		define('aid',$aid);
		$appinfo = \app\common\System::appinfo($aid,$platform);

		ksort($querydata);
		$string1 = '';
		foreach ($querydata as $k => $v) {
			if ($v != '' && $k != 'sign') {
				$string1 .= "{$k}={$v}&";
			}
		}
		$string1 = trim($string1,'&');
		$string1 .= $appinfo['yun_mchkey'];
		$sign = hash("sha256",$string1);
		//Log::write($sign);
		//Log::write($querydata['sign']);
		if($sign == $querydata['sign']){
			if($querydata['respcd'] == '00'){
				Db::name('payorder')->where('aid',aid)->where('ordernum',$querydata['orderNum'])->update(['platform'=>$platform]);
				$rs = $this->setorder($tablename,$querydata['orderNum'],$querydata['channelOrderNum'],intval($querydata['txamt']),'在线支付',22);
                $paymoney = intval($querydata['txamt'])*0.01;
                if($rs['status'] == 1){
					//记录
					$data = array();
					$data['aid'] = aid;
					$data['mid'] = mid;
					$data['openid'] = '';
					$data['tablename'] = $tablename;
					$data['givescore'] = $this->givescore;
					$data['ordernum'] = $querydata['orderNum'];
					$data['mch_id'] = $appinfo['pay_appid'];
					$data['transaction_id'] = $querydata['channelOrderNum'];
					$data['total_fee'] = $paymoney;
					$data['createtime'] = time();
					Db::name('wxpay_log')->insert($data);
					\app\common\Member::uplv(aid,mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\common\Yunpay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'],$paymoney,$rs['msg']);
                    exit('fail');
                }
				exit('success');
			}
		}else{
            Log::write('yunpay check sign error line:'.__LINE__,'error');
			exit('fail');
		}
	}

    //随行付
	private function sxpay(){
		$postdata = json_decode(file_get_contents('php://input'),true);
        Log::write(__FILE__.__LINE__);
		Log::write($postdata);
		$attach = explode(':',$postdata['extend']);//代付'22:daifu:sxpaymp:3e3647f1bd558834d6e90a8cd3a14154',
		$aid = intval($attach[0]);
		$tablename = $attach[1];
		$platform = $attach[2];

		//处理platform，是否有其他参数拼接
		$platformArr = explode('_',$platform);
		$platform = $platformArr[0];
		$platform2= $platformArr[1]??'';
		if($platform == 'sxpaymp')     $platform = 'mp';
		if($platform == 'sxpaywx')     $platform = 'wx';
		if($platform == 'sxpayalipay') $platform = 'alipay';
		if($platform == 'sxpayapp')    $platform = 'app';
		if($platform == 'sxalih5' || $platform == 'sxpayh5') $platform = 'h5';

		$appinfo = \app\common\System::appinfo($aid,$platform);
		//随行付付款码支付
		if($platform2 == 'native'){
			$appinfo['sxpay_mno']    = $appinfo['native_sxpay_mno'];
			$appinfo['sxpay_mchkey'] = $appinfo['native_sxpay_mchkey'];
		}else{
			if($platform == 'h5'){
				$appinfo['sxpay_mno']    = $appinfo['alisxpay_mno'];
				$appinfo['sxpay_mchkey'] = $appinfo['alisxpay_mchkey'];
			}
		}

		$isbusinesspay = 0;
		if($attach[4]){
			$bid = intval($attach[4]);
			$business = Db::name('business')->where('id',$bid)->find();
			$appinfo['sxpay_mno'] = $business['sxpay_mno'];
			$appinfo['sxpay_mchkey'] = $business['sxpay_mchkey'];
			$isbusinesspay = 1;
		}

		//if($appinfo['sxpay_mno'] == '399220401616754') die;
        //收货通知
        if($postdata['origOrdNo'] && $postdata['tranFlg'] == 'P' && $postdata['bizCode'] == '0000'){
            Log::write(__FILE__.__LINE__);
            Log::write('sxpay fenzhang');
            //随行付异步分账
            \app\custom\Sxpay::fz($postdata['origOrdNo'],$postdata['origUuid']);
            die('{"code":"success","msg":"成功"}');
        }

		$md5sign = $attach[3];
		define('aid',$aid);
		if($md5sign == md5($tablename.$postdata['ordNo'].$appinfo['sxpay_mchkey'])){
			$trade_no = explode('D', $postdata['ordNo']);
			$ordernum = $trade_no[0];
			if($postdata['bizCode'] == '0000'){
				$payorder = Db::name('payorder')->where(['aid'=>$aid,'type'=>$tablename,'ordernum'=>$ordernum])->find();
				Db::name('payorder')->where('id',$payorder['id'])->update(['platform'=>$platform,'isbusinesspay'=>$isbusinesspay]);
				if($trade_no[1]){
		            //说明是交易流水
		            Db::name('pay_transaction')->where(['aid'=>$aid,'type'=>$tablename,'transaction_num'=>$postdata['ordNo']])->update(['status'=>1,'isbusinesspay'=>$isbusinesspay]);
		        }
				$paytype = '微信支付';
                $paytypeid = 2;
				if($platform=='alipay'){
                    $paytype = '支付宝支付';
                    $paytypeid = 3;
                }
                if($platform2 == 'native'){
                	$paytype = '聚合支付';
                	$paytypeid = 124;
                }
                
				$mid = $payorder['mid']?:0;
                //记录
                $data = array();
                $data['aid'] = aid;
                $data['mid'] = $mid;
                $data['openid'] = $postdata['uuid'];
                $data['tablename'] = $tablename;
                $data['givescore'] = $this->givescore;
                $data['ordernum'] = $ordernum;
                $data['mch_id'] = $postdata['mno'];
                $data['transaction_id'] = $postdata['transactionId'];
                $data['total_fee'] = $postdata['amt'];
                $data['platform'] = $platform;
                $data['createtime'] = time();
                Db::name('wxpay_log')->insert($data);

                Db::name('sxpay_paylog')->where('ordNo',$postdata['ordNo'])->update([
                    'transaction_id'=>$postdata['transactionId'],
                    'notify_data'=>json_encode($postdata),
                    'status'=>1
                ]);

                //若是随行付聚合码付款，则查询是否已付款成功，若付款成功，且paynum和回调通知不一样，给直接退款
                if($platform2 == 'native' && $payorder['status'] == 1 && $payorder['paynum'] != $postdata['transactionId']){
                    //随行付退款
                    $rs = \app\custom\Sxpay::refund($aid,$platform,$postdata['ordNo'],$postdata['amt'],$postdata['amt'],'重复支付退款',$payorder['bid'],$paytype);
                }

				$rs = $this->setorder($tablename,$postdata['ordNo'],$postdata['transactionId'],intval(bcmul($postdata['amt'],100)),$paytype,$paytypeid);
				if($rs['status'] == 1){
					\app\common\Member::uplv(aid,$mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\custom\Sxpay::refund($aid,$platform,$postdata['ordNo'],$payorder['money'],$postdata['amt'],$rs['msg'],$payorder['bid']);
                    exit('fail');
                }
				die('{"code":"success","msg":"成功"}');
			}
		}else{
            Log::write(__FILE__.__LINE__);
            Log::write('sxpay error:加密校验不通过');
        }
	}

	private function fbpay(){
		$postdata = json_decode(file_get_contents('php://input'),true);
		$attach = explode(':',$postdata['attach']);
		$aid = intval($attach[0]);
		$tablename = $attach[1];
		$platform = $attach[2];
		if($platform == 'fbpaymp') $platform = 'mp';
		if($platform == 'fbpaywx') $platform = 'wx';
		if($platform == 'fbpayali') $platform = 'alipay';
		$appinfo = \app\common\System::appinfo($aid,$platform);
		$md5sign = $attach[3];
		define('aid',$aid);

		ksort($postdata);
		$string1 = '';
		foreach ($postdata as $k => $v) {
			if ($v != '' && $k != 'sign') {
				$string1 .= "{$k}={$v}&";
			}
		}
		$string1 = trim($string1,'&');
		$string1 .= $appinfo['fbpay_appsecret'];
		$sign = strtoupper(md5($string1));
		if($sign == $postdata['sign']){
			if($postdata['result_code'] == '200'){
				Db::name('payorder')->where('aid',aid)->where('ordernum',$postdata['merchant_order_sn'])->update(['platform'=>$platform]);
				$rs = $this->setorder($tablename,$postdata['merchant_order_sn'],$postdata['order_sn'],intval(bcmul($postdata['fee'],100,2)),($platform=='alipay'?'支付宝支付':'微信支付'),2);
				if($rs['status'] == 1){
					//记录
					$data = array();
					$data['aid'] = aid;
					$data['mid'] = mid;
					$data['openid'] = $postdata['user_id'];
					$data['tablename'] = $tablename;
					$data['givescore'] = $this->givescore;
					$data['ordernum'] = $postdata['merchant_order_sn'];
					$data['mch_id'] = $appinfo['fbpay_appid'];
					$data['transaction_id'] = $postdata['order_sn'];
					$data['total_fee'] = $postdata['fee'];
					$data['createtime'] = time();
					if($platform=='alipay'){
						Db::name('alipay_log')->insert($data);
					}else{
						Db::name('wxpay_log')->insert($data);
					}
					\app\common\Member::uplv(aid,mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\custom\Fbpay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'],$postdata['fee'],$rs['msg']);
                    exit('fail');
                }
				die('success');
			}
		}else{
            Log::write('fbpay check sign error line:'.__LINE__,'error');
        }
	}

    private function huifupay(){
        $post = file_get_contents('php://input');
        //$post = 'resp_desc=%E4%BA%A4%E6%98%93%E6%88%90%E5%8A%9F%5B000%5D&resp_code=00000000&sign=MwtpQ7PT6bs8OOn7GDHs3txH8uQlZT7uYq%2Bq5jtpaZEGQXd2x6ma%2B5ak8DH%2BSNC6%2FUQA%2BCmMBtCSE%2FSGWE%2F3FtmBcGpp96iBfX6qZHjKFEIGrYer2nUj0y7gATSMT%2FIrg77d%2FkvMVG3%2BY4zZ0L39581rG6l6q8Wo6C4wXi5cLp0MJ1aKdt41f%2FQN5e1%2BjopMpwAThZCLb%2FvJmW2MyL3RTB2SG5WXNGdOoBX9H2xaRBCb%2BXhYskdvDPpzAUJyGjFLTK1oUWK8202gaQ%2F5EJFd%2Bsx0JCDu81dDdzWuJhlRZCpzF%2B1nuFJ7CjHj5cHhgSGnzKLOK2hRr%2Fu1ptd7jw2DgQ%3D%3D&resp_data=%7B%22acct_date%22%3A%2220231111%22%2C%22acct_id%22%3A%22A27364363%22%2C%22acct_split_bunch%22%3A%7B%22acct_infos%22%3A%5B%7B%22acct_date%22%3A%2220231111%22%2C%22acct_id%22%3A%22A27364363%22%2C%22div_amt%22%3A%220.01%22%2C%22huifu_id%22%3A%226666000139980345%22%7D%5D%2C%22fee_acct_date%22%3A%2220231111%22%2C%22fee_acct_id%22%3A%22A27364363%22%2C%22fee_amt%22%3A%220.00%22%2C%22fee_huifu_id%22%3A%226666000139980345%22%7D%2C%22acct_stat%22%3A%22S%22%2C%22atu_sub_mer_id%22%3A%22597291375%22%2C%22avoid_sms_flag%22%3A%22%22%2C%22bagent_id%22%3A%226666000139397368%22%2C%22bank_code%22%3A%22SUCCESS%22%2C%22bank_desc%22%3A%22%E4%BA%A4%E6%98%93%E6%88%90%E5%8A%9F%22%2C%22bank_message%22%3A%22%E4%BA%A4%E6%98%93%E6%88%90%E5%8A%9F%22%2C%22bank_order_no%22%3A%224200001975202311113249051625%22%2C%22bank_seq_id%22%3A%221T1495%22%2C%22bank_type%22%3A%22OTHERS%22%2C%22base_acct_id%22%3A%22A27364363%22%2C%22batch_id%22%3A%22231111%22%2C%22channel_type%22%3A%22U%22%2C%22charge_flags%22%3A%22758_0%22%2C%22combinedpay_data%22%3A%5B%5D%2C%22combinedpay_fee_amt%22%3A%220.00%22%2C%22debit_type%22%3A%220%22%2C%22delay_acct_flag%22%3A%22N%22%2C%22div_flag%22%3A%220%22%2C%22end_time%22%3A%2220231111231450%22%2C%22fee_amount%22%3A%220.00%22%2C%22fee_amt%22%3A%220.00%22%2C%22fee_flag%22%3A2%2C%22fee_formula_infos%22%3A%5B%7B%22fee_formula%22%3A%22AMT*0.003%22%2C%22fee_type%22%3A%22TRANS_FEE%22%7D%5D%2C%22fee_rec_type%22%3A%221%22%2C%22fee_type%22%3A%22INNER%22%2C%22gate_id%22%3A%22VT%22%2C%22hf_seq_id%22%3A%22002900TOP1B231111231436P810ac139c7f00000%22%2C%22huifu_id%22%3A%226666000139980345%22%2C%22is_delay_acct%22%3A%220%22%2C%22is_div%22%3A%220%22%2C%22mer_name%22%3A%22%E5%9B%BD%E7%9B%88%E6%B3%B0%E5%92%8C%28%E5%8C%97%E4%BA%AC%29%E6%8E%A7%E8%82%A1%E6%9C%89%E9%99%90%E5%85%AC%E5%8F%B8%22%2C%22mer_ord_id%22%3A%22231111231430537821T1495%22%2C%22mypaytsf_discount%22%3A%220.00%22%2C%22need_big_object%22%3Afalse%2C%22notify_type%22%3A2%2C%22org_auth_no%22%3A%22%22%2C%22org_huifu_seq_id%22%3A%22%22%2C%22org_trans_date%22%3A%22%22%2C%22out_ord_id%22%3A%224200001975202311113249051625%22%2C%22out_trans_id%22%3A%224200001975202311113249051625%22%2C%22party_order_id%22%3A%2203232311118367694710658%22%2C%22pay_amt%22%3A%220.01%22%2C%22pay_scene%22%3A%2202%22%2C%22posp_seq_id%22%3A%2203232311118367694710658%22%2C%22product_id%22%3A%22PAYUN%22%2C%22ref_no%22%3A%222314361T1495%22%2C%22remark%22%3A%2261%253Ashop%253Awx%22%2C%22req_date%22%3A%2220231111%22%2C%22req_seq_id%22%3A%22231111231430537821T1495%22%2C%22resp_code%22%3A%2200000000%22%2C%22resp_desc%22%3A%22%E4%BA%A4%E6%98%93%E6%88%90%E5%8A%9F%22%2C%22risk_check_data%22%3A%7B%22ip_addr%22%3A%2239.76.56.159%22%7D%2C%22risk_check_info%22%3A%7B%22client_ip%22%3A%2239.76.56.159%22%7D%2C%22settlement_amt%22%3A%220.01%22%2C%22sub_resp_code%22%3A%2200000000%22%2C%22sub_resp_desc%22%3A%22%E4%BA%A4%E6%98%93%E6%88%90%E5%8A%9F%22%2C%22subsidy_stat%22%3A%22I%22%2C%22sys_id%22%3A%226666000139980345%22%2C%22trade_type%22%3A%22T_MINIAPP%22%2C%22trans_amt%22%3A%220.01%22%2C%22trans_date%22%3A%2220231111%22%2C%22trans_fee_allowance_info%22%3A%7B%22actual_fee_amt%22%3A%220.00%22%2C%22allowance_fee_amt%22%3A%220.00%22%2C%22allowance_type%22%3A%220%22%2C%22receivable_fee_amt%22%3A%220.00%22%7D%2C%22trans_stat%22%3A%22S%22%2C%22trans_time%22%3A%22231436%22%2C%22trans_type%22%3A%22T_MINIAPP%22%2C%22ts_encash_detail%22%3A%5B%5D%2C%22wx_response%22%3A%7B%22bank_type%22%3A%22OTHERS%22%2C%22coupon_fee%22%3A%220.00%22%2C%22openid%22%3A%22o8jhot6LBCkb2DLS3KhMOx6W0joM%22%2C%22sub_appid%22%3A%22wxb07130f2d20b010a%22%2C%22sub_openid%22%3A%22o2qmV69SoMHAZfUQIrkHMysCmryo%22%7D%7D';
        parse_str($post,$msg);
        $msg['resp_desc'] = urldecode($msg['resp_desc']);
        $msg['resp_code'] = urldecode($msg['resp_code']);
//        $msg['sign'] = urldecode($msg['sign']);
        $resp_data = urldecode($msg['resp_data']);
        
        Log::write('huifupay:');
        Log::write($msg);
        $resp_data = json_decode($resp_data,true);

        $attach = explode(':',urldecode($resp_data['remark']));
        $aid = intval($attach[0]);
        define('aid',$aid);
        $tablename = $attach[1];
        $platform = $attach[2];
        $bid = $attach[3];
        $appinfo = \app\common\System::appinfo($aid,$platform);
        $req_seq_idarr = explode('T',$resp_data['req_seq_id']);
        $ordernum =$req_seq_idarr[0];
        //出现回调2次，使用第一种的异步处理逻辑
        //1). 交易异步情况
        // notify_type='1'，trans_stat='F' 时，不推送账务异步
        // notify_type='1'，trans_stat='S' 时，会推送账务异步（使用该模式）
        // 2). 账务异步情况：
        // notify_type='2'，trans_stat='S'，acct_stat='S' 表示交易成功-入账成功
        // notify_type='2'，trans_stat='S'，acct_stat='F' 表示交易成功-入账失败（非正常情况，可联系汇付技术人员确认排查）
        $notify_type = $resp_data['notify_type'];
        if(!in_array($notify_type,[1,2])){  //1：通道通知（支付宝和微信返回 ），2：账务通知（快捷支付返回）
            exit("RECV_ORD_ID_" . $resp_data['req_seq_id']);
        }
        //Log::write($msg);
        if(getcustom('pay_huifu_fenzhang')){
            if($bid > 0  && in_array($tablename,['shop','maidan'])){
                $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
                //开启独立收款 使用服务商的信息
                if($business['huifu_business_status'] ==1){
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
                        $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
                    }
                    Db::name('payorder')->where('aid',$aid)->where('ordernum',$ordernum)->update(['platform'=>$platform,'isbusinesspay'=>1]);
                }
            }
        }
        Log::write($appinfo);

        require_once ROOT_PATH . "vendor/huifurepo/dg-php-sdk/BsPaySdk/init.php";
        $result = \BsPaySdk\core\BsPayTools::verifySign($msg['sign'], $msg['resp_data'], $appinfo['huifu_public_key']);
        if($result == 1){
            if($resp_data['resp_code'] == '00000000'){
                $paytype = '微信支付';
                $paytypeid = 2;
                if($attach[5]=='alipay'){
                    $paytype = '支付宝支付';
                    $paytypeid = 3;
                }
                if(getcustom('pay_huifu_quickpay')){
                    if($attach[4] && $attach[4] =='quickpay'){ //汇付斗拱快捷支付
                        $paytype = '汇付斗拱快捷支付';
                        $paytypeid = 63;
                    }
                }
                $huifu_total_fee  =$resp_data['trans_amt'] *100;
                $rs = $this->setorder($tablename,$ordernum,$resp_data['hf_seq_id'],$huifu_total_fee,$paytype,$paytypeid);
                if($rs['status'] == 1){
                    //记录
                    $data = array();
                    $data['is_div'] = $resp_data['is_div'];
                    $data['acct_split_bunch'] = json_encode($resp_data['acct_split_bunch']);
                    $data['notify_data'] = json_encode($resp_data);
                    $data['pay_status'] = 1;
                    if(getcustom('pay_huifu_preorder_embedded')){
                        $hufulog = Db::name('huifu_log')->where('aid',aid)->where('req_seq_id',$resp_data['req_seq_id'])->field('preorder_resdata,hf_seq_id')->find();
                        if($hufulog['preorder_resdata'] && !$hufulog['hf_seq_id']){
                            $data['hf_seq_id'] = $resp_data['hf_seq_id'];
                        }
                    }
                    Db::name('huifu_log')->where('aid',aid)->where('req_seq_id',$resp_data['req_seq_id'])->update($data);
                    \app\common\Member::uplv(aid,mid);
                }
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    $huifu = new \app\custom\Huifu([],$payorder['aid'],$payorder['bid'],$payorder['mid'],$rs['msg'],$payorder['ordernum'],$payorder['money']);
                    $huifu->refund($resp_data['trans_amt'],$payorder);
                    exit('fail');
                }
                exit("RECV_ORD_ID_" . $resp_data['req_seq_id']);
            }
        }else{
            Log::write('huifupay check sign error line:'.__LINE__,'error');
            exit('fail');
        }
    }

    //银盛支付 https://gateway-doc.ysepay.com/gatewayDocs/summary/N0000384/N0000483/N0000488/I0000330.html
    private function ysepay()
    {
        $post = file_get_contents('php://input');

        $resMap = json_decode($post, true);
        $bizContent = json_decode($resMap['bizContent'], true);
        if($_SERVER['QUERY_STRING'] && strpos($_SERVER['QUERY_STRING'],'a=refund') !== false){ //退款
            $refundlog = Db::name('ysepay_refund_log')->where('ordernum',$bizContent['orderId'])->find();
            $aid = intval($refundlog['aid']);
            define('aid',$aid);
            $tablename = $refundlog['tablename'];
            $platform = $refundlog['platform'];
            $bid = $refundlog['bid'];
            $appinfo = \app\common\System::appinfo($aid,$platform);
            require_once(ROOT_PATH.'/extend/ysepay/YsfApiService.php');
            $YsfApiService = new \YsfApiService($aid,$bid,0,$platform);
            if($YsfApiService->notifyCheckSign($post)){
                Log::write('ysepay CheckSign line:'.__LINE__);
                //todo 退款回调
                Db::startTrans();
                try {
                    //记录
                    $data = array();
                    $data['notify_data'] = json_encode($bizContent);
                    $data['tradeSn'] = $bizContent['tradeSn'];
                    $data['status'] = 1;
                    Db::name('ysepay_refund_log')->where('aid',aid)->where('refundOrderId',$bizContent['orderId'])->update($data);
                    Db::commit();
                    echo 'success';exit;
                }catch (\Exception $e) {
                    Db::rollback();
                    Log::write('ysepay notify error line:'.__LINE__.' msg:'.$e->getMessage(),'error');
                    exit('fail');
                }
            }
            Log::write('ysepay check sign error line:'.__LINE__,'error');
            exit('fail');

        }else{
            //备注不可用时可使用ysepay_log表数据
            $attach = explode(':',$bizContent['remark']);
            $aid = intval($attach[0]);
            define('aid',$aid);
            $tablename = $attach[1];
            $platform = $attach[2];
            $bid = $attach[3];
            $appinfo = \app\common\System::appinfo($aid,$platform);

            require_once(ROOT_PATH.'/extend/ysepay/YsfApiService.php');
            $YsfApiService = new \YsfApiService($aid,$bid,0,$platform);
            if($YsfApiService->notifyCheckSign($post)){
                Log::write('ysepay CheckSign line:'.__LINE__);
                Db::startTrans();
                try {
                    $paytype = '微信支付';
                    $paytypeid = 2;
                    if($platform=='alipay'){
                        $paytype = '支付宝支付';
                        $paytypeid = 3;
                    }
                    $total_fee = bcmul($bizContent['amount'], 100, 0);
                    $rs = $this->setorder($tablename,$bizContent['orderId'],$bizContent['tradeSn'],$total_fee,$paytype,$paytypeid);
                    if($rs['status'] == 1){
                        //记录
                        $data = array();
                        $data['notify_data'] = json_encode($bizContent);
                        $data['tradeSn'] = $bizContent['tradeSn'];
                        $data['pay_status'] = 1;
                        Db::name('ysepay_log')->where('aid',aid)->where('ordernum',$bizContent['orderId'])->update($data);
                        Db::commit();
                        \app\common\Member::uplv(aid,mid);
                        Log::write('ysepay return success line:'.__LINE__);
                        echo 'success';exit;
                    }else{
                        Log::write('ysepay setorder error line:'.__LINE__.' msg:'.$rs['msg'],'error');
                        Db::rollback();
                    }
                }catch (\Exception $e) {
                    Db::rollback();
                    Log::write('ysepay notify error line:'.__LINE__.' msg:'.$e->getMessage(),'error');
                    exit('fail');
                }
            }
            Log::write('ysepay check sign error line:'.__LINE__,'error');
            exit('fail');
        }
    }

	private function qmpay(){
		//Log::write('qmpay---------------------------');
		$querystring = urldecode(file_get_contents('php://input'));
		parse_str($querystring,$querydata);
		//Log::write($querydata);
		
		$merOrderId = str_replace('11UM','',$querydata['merOrderId']);
		
		if(!$querydata['attachedData']){
			$payorder = Db::name('payorder')->where('ordernum',$merOrderId)->find();
			$aid = intval($payorder['aid']);
			$tablename = $payorder['type'];
			$platform = 'h5';
		}else{
			$attach = explode(':',$querydata['attachedData']);
			$aid = intval($attach[0]);
			$tablename = $attach[1];
			$platform = $attach[2];
		}
		define('aid',$aid);
		$appinfo = \app\common\System::appinfo($aid,$platform);

		ksort($querydata);
		$string1 = '';
		foreach ($querydata as $k => $v) {
			if ($v != '' && $k != 'sign') {
				$string1 .= "{$k}={$v}&";
			}
		}
		$config = include(ROOT_PATH.'config.php');
		$config = $config['qmpay'];

		$string1 = trim($string1,'&');
		//$string1 .= '47ace12ae3b348fe93ab46cee97c6fde';//$appinfo['yun_mchkey'];
		$string1 .= $config['md5key'];
		$sign = strtoupper(hash("sha256",$string1));
		//$sign = strtoupper(md5($string1));
		//Log::write($string1);
		//Log::write('-----1');
		//Log::write($sign);
		//Log::write('-----2');
		//Log::write($querydata['sign']);
		if($sign == $querydata['sign']){
			if($querydata['status'] == 'TRADE_SUCCESS'){
				Db::name('payorder')->where('aid',aid)->where('ordernum',$merOrderId)->update(['platform'=>$platform]);
				$rs = $this->setorder($tablename,$merOrderId,$querydata['targetOrderId'],intval($querydata['totalAmount']),'支付宝支付',23);
				if($rs['status'] == 1){
					//记录
					$data = array();
					$data['aid'] = aid;
					$data['mid'] = mid;
					$data['openid'] = '';
					$data['tablename'] = $tablename;
					$data['givescore'] = $this->givescore;
					$data['ordernum'] = $merOrderId;
					$data['mch_id'] = $appinfo['pay_appid'];
					$data['transaction_id'] = $querydata['targetOrderId'];
					$data['total_fee'] = intval($querydata['totalAmount'])*0.01;
					$data['createtime'] = time();
					Db::name('wxpay_log')->insert($data);
					\app\common\Member::uplv(aid,mid);
				}
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\common\Qmpay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'],intval($querydata['totalAmount'])*0.01,$rs['msg']);
                    exit('fail');
                }
				exit('success');
			}
		}else{
            Log::write('qmpay check sign error line:'.__LINE__,'error');
			exit('fail');
		}
	}

	//adapay
    public function adapay(){
    	if(getcustom('pay_adapay')){
	        \think\facade\Log::write('汇付天下回调：'.json_encode($_POST,JSON_UNESCAPED_UNICODE));
            \think\facade\Log::write('汇付天下回调：'.$_POST['data']);
	        \think\facade\Log::write('汇付天下sing：'.$_POST['sign']);
	        $post = json_decode($_POST['data'],1);
	        $payorder = Db::name('payorder')->where('ordernum',$post['order_no'])->find();
	        $aid = intval($payorder['aid']);
	        define('aid',$aid);
	        $tablename = $payorder['type'];
	        $platform = $payorder['platform'];
	        $pay_channel = $post['pay_channel'];
	        //require_once(ROOT_PATH.'/extend/adapay/AdapaySdk/init.php');
	        $appinfo = \app\common\System::appinfo($aid,$platform);
	        $config_object = [
	            "api_key_live" => $appinfo['adapay_api_key_live'],
	            "rsa_private_key" => $appinfo['adapay_rsa_private_key']
	        ];
	        if($pay_channel == 'alipay_wap'){
	               $paytypeid = 3;
	        }elseif ($pay_channel=='union_online') {
	            $paytypeid = 61;
	        }
	        $paytype = '汇付天下支付';
	        \AdaPay\AdaPay::init($config_object, "live", true);
	        $post_data_str = json_encode($post,JSON_UNESCAPED_UNICODE);
	        $post_sign_str = isset($_POST['sign']) ? $_POST['sign']: '';
	        $adapay_tools = new \AdaPaySdk\AdapayTools();
	        $sign_flag = $adapay_tools->verifySign($post_data_str, $post_sign_str);
	        if ($sign_flag){
	            if($post['status'] == 'failed'){
                    exit('fail');
                }
	            $rs = $this->setorder($tablename,$post['order_no'],$post['id'],$post['pay_amt'],$paytype,$paytypeid);
	            if($rs['status'] == 1){
	                //记录
	                $data = array();
	                $data['aid'] = aid;
	                $data['mid'] = mid;
	                $data['openid'] = '';
	                $data['pay_channel'] = $pay_channel;
	                $data['tablename'] = $tablename;
	                $data['givescore'] = $this->givescore;
	                $data['ordernum'] = $post['order_no'];
	                $data['mch_id'] = $_POST['app_id'];
	                $data['transaction_id'] = $post['id'];
	                $data['total_fee'] = $post['pay_amt'];
	                $data['createtime'] = time();
	                Db::name('adapay_log')->insert($data);
	                \app\common\Member::uplv(aid,mid);
                    exit('success');
	            }
                //退款
                if($rs['status'] == 2){
                    $payorder = $rs['payorder'];
                    \app\custom\AdapayPay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'],$post['pay_amt'],$rs['msg']);
                    exit('fail');
                }
                exit('success');
	        }else{
                Log::write('adapay check sign error line:'.__LINE__,'error');
	            exit('fail');
	        }
	    }
    }

	//麒麟数字
	public function qilinshuzi(){
		if(getcustom('pay_qilinshuzi')) {
			$input = file_get_contents('php://input');
			if (empty($input)) exit('fail');
			\think\facade\Log::write([
				'title'  => '麒麟回调',
				'notify' => $input,
			]);

			$params   = json_decode($input, true);
			$bizData  = json_decode($params['bizData'], true);
			//进件回调
			if(strpos($_SERVER['QUERY_STRING'],'type=jinjian') > 0){
				//进件编号
				$apply_number = $bizData['subApplyId'] ?? '';
				if(empty($apply_number)) exit('fail');

				$jjInfo = Db::name('qilinshuzi_jinjian')->where('apply_number',$apply_number)->find();
				if(empty($jjInfo)) exit('fail');

				//验签
				$appinfo = \app\common\System::appinfo($jjInfo['aid']);
				\app\custom\QilinshuziPay::init($appinfo);
				$checkSign = \app\custom\QilinshuziPay::requestDecode($params);
				if (!$checkSign) {
					\think\facade\Log::write([
						'title'  => '麒麟进件回调验签失败',
						'notify' => $input,
					]);
					exit('fail');
				}

				$qilin_id = $bizData['applyId'] ?? '';
				$qilin_mdid = $bizData['mchStore']['storeId'] ?? '';
				$jjUpdate = [];
				$jjUpdate['info'] = json_encode($bizData);
				$jjUpdate['status'] = $bizData['state'] ?? -1;
				$jjUpdate['qilin_id'] = $qilin_id;
				if($bizData['state'] == 2){
					//进件成功
					$jjUpdate['qilin_mdid'] = $qilin_mdid;
					//绑定商户号
					Db::name('business')->where('aid',$jjInfo['aid'])->where('id',$jjInfo['bid'])->update(['qilin_id'=>$qilin_id,'qilin_mdid'=>$qilin_mdid]);
				}
				Db::name('qilinshuzi_jinjian')->where('id',$jjInfo['id'])->update($jjUpdate);
				exit('SUCCESS');
			}

			$ordernum = $bizData['mchOrderNo'];
			//查询支付订单
			$log = Db::name('qilinshuzi_log')->where('ordernum', $ordernum)->find();
			if (empty($log)) exit('fail');

			$aid       = intval($log['aid']);
			$platform  = $log['platform'];
			$tablename = $log['tablename'];
			define('aid', $aid);

			//验签
			$appinfo = \app\common\System::appinfo($aid, $platform);
			\app\custom\QilinshuziPay::init($appinfo);
			$checkSign = \app\custom\QilinshuziPay::requestDecode($params);
			if (!$checkSign) {
				\think\facade\Log::write([
					'title'  => '麒麟回调验签失败',
					'notify' => $input,
				]);
				exit('fail');
			}

			switch ($bizData['msgType']) {
				case 'sft.trade.notify':    //支付通知
					$paytype = '微信支付';
					$paytypeid = 2;
					if($bizData['payType'] == 'ALIPAY'){
						$paytype = '支付宝支付';
						$paytypeid = 3;
					}

					$trade_no = explode('D', $bizData['mchOrderNo']);
					$ordernum = $trade_no[0];
					$payorder = Db::name('payorder')->where('ordernum', $ordernum)->find();
					if ($payorder['bid'] > 0) {
						Db::name('payorder')->where(['aid' => $aid, 'type' => $tablename, 'id' => $payorder['id']])->update(['isbusinesspay' => 1]);
					}
					$rs = $this->setorder($tablename, $ordernum, $bizData['payOrderId'], $bizData['amount'], $paytype, $paytypeid);
					if (empty($rs) || $rs['status'] != 1) {
						\think\facade\Log::write([
							'file' => __FILE__ . ' L' . __LINE__,
							'rs'   => $rs
						]);
						exit('fail');
					}
					//记录回调参数
					Db::name('qilinshuzi_log')->where('id', $log['id'])->update(['notify_data' => $input,'pay_status'=>1]);
                    if($payorder['type'] != 'shop'){
                        $payorder['ordernum'] = $bizData['mchOrderNo'];
                        \app\custom\QilinshuziPay::orderFenzhang($payorder);
                    }
					exit('SUCCESS');
				case 'sft.division.notify': //分账通知
					//分账通知发送两次 一次状态为4：分账已受理（渠道侧受理） 第二次：1：分账成功
					if ($bizData['state'] != 1) exit('fail');
					Db::name('qilinshuzi_log')->where('id', $log['id'])->update(['fenzhang_status' => 1]);
					exit('SUCCESS');
				case 'sft.refund.notify':    //退款通知
					if ($bizData['state'] != 'SUCCESS') exit('fail');
					$refundMoney = $bizData['refundAmt'] / 100; //分转元
					Db::name('qilinshuzi_log')->where('id', $log['id'])->update(['refund_money' => $refundMoney, 'refund_status' => 1, 'refund_time' => time()]);
					exit('SUCCESS');
				default:
					exit('fail');
			}
		}
	}

    /**
     * 微信支付V3回调处理
     * @param string $inBody 原始请求体JSON
     * @param array $header 请求头
     */
    private function wxpayV3Notify($inBody, $header){
        Log::write(['wxpayV3Notify start', 'body' => $inBody], 'info');

        // 先从JSON中提取attach信息（未加密的summary或从resource中尝试）
        $inBodyArray = json_decode($inBody, true);
        if(!$inBodyArray || !isset($inBodyArray['resource'])){
            Log::write('wxpayV3Notify: invalid body', 'error');
            echo json_encode(['code' => 'FAIL', 'message' => 'invalid body']); return;
        }

        // 解密resource获取业务数据（需要先确定aid和platform来获取apiv3key）
        // V3回调的summary中不含attach，需要先尝试从attach或数据库获取
        // 策略：先用resource.associated_data判断，或遍历可能的配置
        // 由于PC端V3回调只有一个来源(admin_setapp_pc)，先尝试该配置
        $resource = $inBodyArray['resource'];
        $ciphertext = $resource['ciphertext'] ?? '';
        $nonce = $resource['nonce'] ?? '';
        $aad = $resource['associated_data'] ?? '';

        // 尝试从admin_setapp_pc获取V3密钥解密
        $pcConfigs = Db::name('admin_setapp_pc')->where('wxpay', 1)->select()->toArray();
        $decryptedData = null;
        $matchedConfig = null;

        foreach($pcConfigs as $pcConf){
            if(empty($pcConf['wxpay_mchkey_v3'])) continue;
            try {
                $apiv3Key = $pcConf['wxpay_mchkey_v3'];
                $decrypted = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
                $decryptedData = json_decode($decrypted, true);
                if($decryptedData && isset($decryptedData['out_trade_no'])){
                    $matchedConfig = $pcConf;
                    break;
                }
            } catch(\Exception $e){
                continue;
            }
        }

        if(!$decryptedData || !$matchedConfig){
            Log::write('wxpayV3Notify: decrypt failed or no matching config', 'error');
            echo json_encode(['code' => 'FAIL', 'message' => 'decrypt failed']); return;
        }

        Log::write(['wxpayV3Notify decrypted', $decryptedData], 'info');

        // 验签
        $inWechatpaySignature = $header['wechatpay-signature'] ?? '';
        $inWechatpayTimestamp = $header['wechatpay-timestamp'] ?? '';
        $inWechatpaySerial = $header['wechatpay-serial'] ?? '';
        $inWechatpayNonce = $header['wechatpay-nonce'] ?? '';

        if(!$inWechatpaySignature || !$inWechatpayTimestamp || !$inWechatpaySerial || !$inWechatpayNonce){
            Log::write('wxpayV3Notify: missing signature headers', 'error');
            echo json_encode(['code' => 'FAIL', 'message' => 'missing headers']); return;
        }

        // 根据sign_type获取公钥验签
        try {
            if($matchedConfig['sign_type'] == 0){
                // 平台证书
                $platPem = file_get_contents(ROOT_PATH . $matchedConfig['wxpay_wechatpay_pem']);
                $publicKey = Rsa::from($platPem, Rsa::KEY_TYPE_PUBLIC);
            } else {
                // 支付公钥
                $pubKeyPem = file_get_contents($matchedConfig['public_key_pem']);
                $publicKey = Rsa::from($pubKeyPem, Rsa::KEY_TYPE_PUBLIC);
            }

            $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
            $verifiedStatus = Rsa::verify(
                Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
                $inWechatpaySignature,
                $publicKey
            );

            if(!$timeOffsetStatus || !$verifiedStatus){
                Log::write('wxpayV3Notify: signature verify failed', 'error');
                echo json_encode(['code' => 'FAIL', 'message' => 'verify failed']); return;
            }
        } catch(\Exception $e){
            Log::write(['wxpayV3Notify: verify exception', $e->getMessage()], 'error');
            echo json_encode(['code' => 'FAIL', 'message' => 'verify exception']); return;
        }

        // 检查交易状态
        if(($decryptedData['trade_state'] ?? '') !== 'SUCCESS'){
            Log::write(['wxpayV3Notify: trade_state not SUCCESS', $decryptedData['trade_state'] ?? ''], 'info');
            echo json_encode(['code' => 'SUCCESS', 'message' => '成功']); return;
        }

        // 解析attach: aid:tablename:platform:bid
        $attach = explode(':', $decryptedData['attach'] ?? '');
        $aid = intval($attach[0] ?? 0);
        $tablename = $attach[1] ?? '';
        $platform = $attach[2] ?? 'pc';
        $bid = intval($attach[3] ?? 0);

        if(!$aid || !$tablename){
            Log::write('wxpayV3Notify: invalid attach', 'error');
            echo json_encode(['code' => 'FAIL', 'message' => 'invalid attach']); return;
        }

        define('aid', $aid);

        $out_trade_no = $decryptedData['out_trade_no'] ?? '';
        $transaction_id = $decryptedData['transaction_id'] ?? '';
        $total_fee = intval($decryptedData['amount']['total'] ?? 0); // 单位：分

        // 更新交易流水
        $trade_no = explode('D', $out_trade_no);
        $payorder = Db::name('payorder')->where(['aid' => $aid, 'type' => $tablename, 'ordernum' => $trade_no[0]])->find();

        if($bid){
            Db::name('payorder')->where('id', $payorder['id'])->update(['isbusinesspay' => 1]);
            if(isset($trade_no[1]) && $trade_no[1]){
                Db::name('pay_transaction')->where(['aid' => $aid, 'type' => $tablename, 'payorderid' => $payorder['id']])->update(['isbusinesspay' => 1]);
            }
        }

        // 记录支付日志
        $paymoney = $total_fee * 0.01;
        $data = [
            'aid' => $aid,
            'mid' => $payorder['mid'] ?? 0,
            'openid' => $decryptedData['payer']['openid'] ?? '',
            'tablename' => $tablename,
            'givescore' => $this->givescore,
            'ordernum' => $out_trade_no,
            'mch_id' => $decryptedData['mchid'] ?? '',
            'transaction_id' => $transaction_id,
            'total_fee' => $paymoney,
            'createtime' => time(),
            'platform' => $platform,
            'bid' => $bid,
        ];

        $wxpay_log = Db::name('wxpay_log')->where('transaction_id', $transaction_id)->where('ordernum', $out_trade_no)->where('aid', $aid)->field('id')->find();
        if($wxpay_log){
            Log::write('wxpayV3Notify: wxpay_log duplicate');
        } else {
            Db::name('wxpay_log')->insert($data);
        }

        // 设置订单支付状态
        $rs = $this->setorder($tablename, $out_trade_no, $transaction_id, $total_fee, '微信支付', 2, 8);
        if($rs['status'] == 1){
            \app\common\Member::uplv($aid, $payorder['mid'] ?? 0);
        }

        // 退款处理
        if($rs['status'] == 2){
            $refundPayorder = $rs['payorder'];
            \app\common\Wxpay::refund_v3($aid, $platform, $refundPayorder['ordernum'], $refundPayorder['money'], $paymoney, $rs['msg']);
        }

        // V3回调成功响应（JSON格式）
        echo json_encode(['code' => 'SUCCESS', 'message' => '成功']);
        return;
    }

    /**
     * @param $tablename
     * @param $out_trade_no 内部订单号
     * @param $transaction_id 第三方交易号
     * @param $total_fee 金额，整数位，单位分 100是1块钱
     * @param $paytype
     * @param $paytypeid
     * @return array|null status 0已支付，1正常，2退款
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function setorder($tablename,$out_trade_no,$transaction_id,$total_fee,$paytype,$paytypeid,$wxpay_typeid=''){
        /******防止并发回调 start******/
        //后面的流程加事务容易造成死锁，所以这里单独一个记录表处理
        Db::startTrans();
        $exit = Db::name('payorder_log')->where('ordernum',$out_trade_no)->lock(true)->find();
        if($exit){
            Log::write([
                'file'=>__FILE__.__FUNCTION__.__LINE__,
                'payorder_log中已记录',
                '$total_fee'=>$total_fee,
                '$out_trade_no'=>$out_trade_no
            ]);
            return ['status'=>0,'msg'=>'订单已记录过了'];
        }else{
            //lock锁并不是100%能锁住，把ordernum改成唯一索引再次防止订单重复 20251013
            try {
                $payorder_log = [
                    'ordernum'=>$out_trade_no,
                ];
                Db::name('payorder_log')->insert($payorder_log);
            } catch (\Exception $e) {
                // 判断是否为重复键错误
                if (strpos($e->getMessage(), 'Duplicate entry') !== false ||
                    strpos($e->getMessage(), '1062') !== false) {

                    Log::write([
                        'file' => __FILE__ . ' ' . __FUNCTION__ . ' ' . __LINE__,
                        'payorder_log中已记录(唯一索引)',
                        '$total_fee' => $total_fee,
                        '$out_trade_no' => $out_trade_no
                    ]);
                    return ['status' => 0, 'msg' => '订单已记录过了'];
                }

                // 其他异常
                Log::write([
                    'file' => __FILE__ . ' ' . __FUNCTION__ . ' ' . __LINE__,
                    '插入异常' => $e->getMessage(),
                    '$out_trade_no' => $out_trade_no
                ]);
                return ['status' => 0, 'msg' => '系统错误'];
            }

        }
        Db::commit();
        /******防止并发回调 end******/
        $trade_no = explode('D', $out_trade_no);
        $payorder = Db::name('payorder')->where(['aid'=>aid,'type'=>$tablename,'ordernum'=>$trade_no[0]])->find();
		if($payorder['status']!=0) return ['status'=>0,'msg'=>'订单已支付'];
        if($payorder['money']*100 != $total_fee){
            Log::write([
               'file'=>__FILE__.__FUNCTION__.__LINE__,
               '金额不一致',
               '$total_fee'=>$total_fee,
               '$payorder'=>jsonEncode($payorder)
            ]);
//            //金额不一致 退款
//		    return ['status'=>2,'msg'=>'支付金额和订单金额不一致','payorder'=>$payorder];
        }
//        if($payorder['status'] == 2){
//            //支付单取消
//            return ['status'=>2,'msg'=>'订单已修改，请重新发起支付','payorder'=>$payorder];
//        }
		if($payorder['score'] > 0){
			//$rs = \app\common\Member::addscore(aid,$payorder['mid'],-$payorder['score'],'支付订单，订单号：'.$out_trade_no);
			if($payorder['bid']==0 && $payorder['type'] != 'shop_hb'){
				$rs = \app\common\Member::addscore(aid,$payorder['mid'],-$payorder['score'],'支付订单，订单号: '.$payorder['ordernum']);
			}else{
				$business_selfscore = 0;
				if(getcustom('business_selfscore')){
					$bset = Db::name('business_sysset')->where('aid',aid)->find();
					if($bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
						$business_selfscore = 1;
					}
				}
				if($business_selfscore == 0){
					$rs = \app\common\Member::addscore(aid,$payorder['mid'],-$payorder['score'],'支付订单,订单号: '.$payorder['ordernum'],'',$payorder['bid']);
					if(getcustom('business_selfscore') && $bset['business_selfscore'] == 1){
						$nickname = Db::name('member')->where('id',$payorder['mid'])->value('nickname');
						\app\common\Business::addscore(aid,$payorder['bid'],$payorder['score'],t('用户').$nickname.'花费'.t('积分'));
					}
				}else{
					if($payorder['type'] != 'shop_hb'){
						$rs = \app\common\Business::addmemberscore(aid,$payorder['bid'],$payorder['mid'],-$payorder['score'],'支付订单,订单号: '.$payorder['ordernum'],1);
					}else{
						$subpayorderlist = Db::name('payorder')->where('aid',aid)->where('type','shop')->where('ordernum','like',$payorder['ordernum'].'_%')->select()->toArray();
						foreach($subpayorderlist as $subpayorder){
							if($subpayorder['score'] == 0) continue;
							if($subpayorder['bid'] == 0){
								$rs = \app\common\Member::addscore(aid,$payorder['mid'],-$subpayorder['score'],'支付订单,订单号: '.$subpayorder['ordernum']);
							}elseif($subpayorder['bid'] != 0){
								$rs = \app\common\Business::addmemberscore(aid,$subpayorder['bid'],$payorder['mid'],-$subpayorder['score'],'支付订单,订单号: '.$subpayorder['ordernum'],1);
							}
						}
					}
				}
			}

			if($rs['status'] == 0){
				$order = $payorder;
				$order['totalprice'] = $order['money'];
				$order['paytypeid']  = $paytypeid;

				$params = [];
                if(getcustom('pay_money_combine')){
                    $combinestatus = false;
                    //暂只支持 $payorder['type'] == 'shop'
                    if($payorder['type'] == 'shop'){
                        $combinestatus = true;
                        $combinepaytype = 'shop';
                    }
                    if(getcustom('pay_money_combine_maidan')){
                        if($payorder['type'] == 'maidan'){
                            $combinestatus = true;
                            $combinepaytype = 'maidan';
                        }
                    }
                    if($combinestatus){
                    	//如果是组合支付，退款需要判断余额、微信、支付宝退款部分
                        $combine_order = Db::name($combinepaytype.'_order')->where('id',$payorder['orderid'])->field('id,aid,bid,mid,ordernum,status,combine_money,combine_wxpay,combine_alipay,refund_combine_money,refund_combine_money,refund_combine_wxpay,refund_combine_alipay')->find();
                        if($combine_order && $combine_order['status'] == 0 && $combine_order['combine_money']>0 && ($combine_order['combine_wxpay']>0 || $combine_order['combine_alipay']>0)){
                            //refund_combine 1 走shop_refund_order 退款;2 走shop_order 退款 3 走shop_order 退款并清空组合支付数据(需未支付)
                            $params = [
                                'refund_combine'=> 3,
                                'refund_order'  => $combine_order
                            ];
                            $order = [];
                            $order = $combine_order;
                            $order['totalprice'] = $payorder['money'];
                            $order['paytypeid']  = $paytypeid;
                        }
                    }
                }

				$rs = \app\common\Order::refund($order,$order['money'],'积分扣除失败退款',$params);
				Log::write($rs);
				return ['status'=>0,'msg'=>'已退款'];
			}
		}
        if(getcustom('freeze_money')){
            //冻结资金支付
            if($payorder['total_freezemoney_price']>0){
                $remark = '支付订单,订单号: '.$payorder['ordernum'];
                $res_freezemoney = \app\common\Member::addfreezemoney(aid,$payorder['mid'],-$payorder['total_freezemoney_price'],'支付订单，订单号: '.$payorder['ordernum'],$payorder['ordernum']);
                if($res_freezemoney['status']!=1){
                    if($rs['status'] == 0){
                        $order = $payorder;
                        $order['totalprice'] = $order['money'];
                        $order['paytypeid']  = $paytypeid;
                        $params = [];

                        $rs = \app\common\Order::refund($order,$order['money'],'冻结资金扣除失败退款',$params);
                        Log::write($rs);
                        return ['status'=>0,'msg'=>'已退款'];
                    }
                }
            }
        }
		$rs = \app\model\Payorder::payorder($payorder['id'],$paytype,$paytypeid,$transaction_id,$wxpay_typeid,$out_trade_no);
		if($rs['status']==0) return $rs;
		define('mid',$payorder['mid']);

		$set = Db::name('admin_set')->where('aid',aid)->find();
        if(getcustom('business_score_duli_set')){//如果商户单独设置了赠送积分规则
            $business_duli = Db::name('business')->where('aid',aid)->where('id',$payorder['bid'])->field('scorein_money,scorein_score,scorecz_money,scorecz_score')->find();
            if(!is_null($business_duli['scorein_money']) && !is_null($business_duli['scorein_score'])){
                $set['scorein_money'] = $business_duli['scorein_money'];
                $set['scorein_score'] = $business_duli['scorein_score'];
            }
            if(!is_null($business_duli['scorecz_money']) && !is_null($business_duli['scorecz_score'])){
                $set['scorecz_money'] = $business_duli['scorecz_money'];
                $set['scorecz_score'] = $business_duli['scorecz_score'];
            }
        }
        $iszs = true;
        if(getcustom('score_stacking_give_set') && $set['score_stacking_give_set'] == 2){
            $iszs = false;
        }
        if(getcustom('maidan_give_score')){
            //商户买单送积分 独立设置
            if($tablename =='maidan' && $payorder['bid'] > 0){
                $business_maidan_scorein = Db::name('business')->where('aid',$payorder['aid'])->where('id',$payorder['bid'])->field('maidan_give_score_status,maidan_give_score_money,maidan_give_score_givenum')->find();
                //开启时，重置设置
                if($business_maidan_scorein['maidan_give_score_status'] ==1){
                    $set['scorein_money'] = $business_maidan_scorein['maidan_give_score_money'];
                    $set['scorein_score'] = $business_maidan_scorein['maidan_give_score_givenum'];
                }elseif ($business_maidan_scorein['maidan_give_score_status'] ==0){
                    //如果设置关闭 直接设置成0，不赠送了
                    $set['scorein_money'] = 0 ;
                    $set['scorein_score'] = 0;
                }
            }
        }
		//消费送积分
		if($tablename != 'recharge' && $set['scorein_money']>0 && $set['scorein_score']>0 && $iszs){
            $givescore = floor($total_fee * 0.01 / $set['scorein_money']) * $set['scorein_score'];
            if (getcustom('shop_alone_give_score') && $set['maidan_give_score'] == 2 && $payorder['type'] == 'maidan'){
                $payMoney = Db::name('maidan_order')->where('id', $payorder['orderid'])->value('money');
                if ($payMoney <= 0) {
                    $givescore = 0;
                }else {
                    $givescore = $set['scorein_score'] * ($payorder['money'] / $payMoney);
                }
            }

			$res = \app\common\Member::addscore(aid,mid,$givescore,'消费送'.t('积分'));
			if($res && $res['status'] == 1){
				//记录消费赠送积分记录
				\app\common\Member::scoreinlog(aid,0,mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$total_fee);
			}
		}
		if(getcustom('business_moneypay')){ //多商户设置的消费送积分
			if($payorder['bid'] > 0 && $tablename != 'shop'){
				$bset = Db::name('business_sysset')->where('aid',aid)->find();
				$givescore = floor($total_fee*0.01 / $bset['scorein_money']) * $bset['scorein_score'];
				$res = \app\common\Member::addscore(aid,mid,$givescore,'消费送'.t('积分'));
				if($res && $res['status'] == 1){
					//记录消费赠送积分记录
					\app\common\Member::scoreinlog(aid,$payorder['bid'],mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$total_fee);
				}
			}
		}
		//充值送积分
		if($tablename == 'recharge' && $set['scorecz_money']>0 && $set['scorecz_score']>0){
			$givescore = floor($total_fee*0.01 / $set['scorecz_money']) * $set['scorecz_score'];
			\app\common\Member::addscore(aid,mid,$givescore,'充值送'.t('积分'));
		}
		$this->givescore = $givescore;

        if(getcustom('gold_bean')){
            if($tablename =='maidan' && $payorder['bid'] > 0){
                $business = Db::name('business')->where('aid',$payorder['aid'])->where('id',$payorder['aid'])->field('maidan_give_gold_bean_status,maidan_give_gold_bean_money,maidan_give_gold_bean_givenum')->find();
                if($business['maidan_give_gold_bean_status']){
                    $give_gold_bean = floor($payorder['paymoney']/$business['maidan_give_gold_bean_money']) *  $business['maidan_give_gold_bean_givenum'];
                    if($give_gold_bean > 0){
                        //$aid,$mid,$gold_bean,$remark,$channel='',$bid=0,$frommid=0,$addtotal=1,$params = []
                        $res = \app\common\Member::addgoldbean($payorder['aid'],$payorder['mid'],$give_gold_bean,'买单消费送'.t('金豆').'，订单号：'.$payorder['ordernum']);
                        if($res && $res['status'] == 1){
                            //记录消费赠送金豆记录
                            \app\common\Member::goldbeaninlog(aid,0,mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$give_gold_bean);
                        }
                    }
                }
            }
        }
		//Db::commit();
		return ['status'=>1,'msg'=>''];
	}


	//快递100
	private function kuaidi100(){
		$msg = $_POST;
		$param = json_decode($msg['param'],true);
		//Log::write($param);
		if($param['data']['orderId']){
			 $data = [];
			 $data['courierName'] = $param['data']['courierName'];
			 $data['courierMobile'] = $param['data']['courierMobile'];
			 $data['kuaidinum'] = ['kuaidinum'];
			 $data['status'] = $param['data']['status'];
			 Db::name('express_order')->where(['orderId'=>$param['data']['orderId']])->update($data);
			return ['status'=>1,'msg'=>''];
		}
	}	

	//随行付审核
	private function sxaudit(){
		//\think\facade\Log::write(file_get_contents('php://input'));
		$postdata = json_decode(file_get_contents('php://input'),true);
		\think\facade\Log::write($postdata);
		$updata = [];
		if($postdata['taskType'] == '01'){ //修改单
			$updata['taskStatus_edit'] = $postdata['taskStatus'];
			$updata['suggestion_edit'] = $postdata['suggestion'];
		}else{ //入驻单
			$updata['taskStatus'] = $postdata['taskStatus'];
			$updata['suggestion'] = $postdata['suggestion'];
			$repoInfo = $postdata['repoInfo'];
			if($repoInfo){
				$submchid = '';
				$zfbmchid = '';
				foreach($repoInfo as $v){
					if($v['childNoType'] == 'WX'){
						$submchid = $v['childNo'];
					}
					if($v['childNoType'] == 'ZFB'){
						$zfbmchid = $v['childNo'];
					}
				}
				if($submchid){
					$updata['submchid'] = $submchid;
				}
				if($zfbmchid){
					$updata['zfbmchid'] = $zfbmchid;
				}
			}
			if($postdata['taskStatus'] == 1){
				$info = Db::name('sxpay_income')->where('business_code',$postdata['mno'])->find();
				if($info['taskStatus'] != 1){
					$reqData = [];
					$reqData['mno'] = $postdata['mno'];
					$reqData['mecAuthority'] = '01';
					$rs = \app\custom\Sxpay::merchantSetup($info['aid'],$reqData,$info['mchkey']);
				}
			}
		}
		if($postdata['isEspecial'] == '01' || $postdata['isEspecial'] === '00'){ //复核单
			$updata['isEspecial'] = $postdata['isEspecial'];
			$updata['suggestion2'] = $postdata['suggestion'];
			$updata['specialMerFlagEndTime'] = $postdata['specialMerFlagEndTime'];
		}
		Db::name('sxpay_income')->where('business_code',$postdata['mno'])->update($updata);
		die('{"code": "success","msg": "成功"}');
	}

    //云闪付
    private function yunshanfu(){
        $chinaumsSubsidy = getcustom('product_chinaums_subsidy');
        if(getcustom('pay_chinaums')){
            $param = array_map(function($value){
                return urldecode($value);
            }, $_POST);
            \think\facade\Log::write($param);

            $payOrderNum = $param['srcReverse'];
            if (!$payOrderNum) die('FAILED');
            $payorder = Db::name('payorder')->where(['ordernum' => $payOrderNum])->find();
            if (!$payorder) die('FAILED');

            $yunshanfu = new \app\custom\YunshanfuWxPay($payorder['aid']);
            $sign = $yunshanfu->makeSign($param);
//            \think\facade\Log::write($sign);
            $notifySign = array_key_exists('sign', $param) ? $param['sign'] : '';
            if (strcmp($sign, $notifySign) == 0) {
                $status = $param['status'];
                if ($status == 'TRADE_SUCCESS') {
                    //国补上报
                    if($chinaumsSubsidy){
                        $subsidy = new \app\custom\ChinaumsSubsidy($payorder['aid']);
                        $subsidy->payOrder($param,$payorder);
                    }
                    \app\model\Payorder::payorder($payorder['id'], '云闪付支付', 122, $param['merOrderId']);
                }
                die('SUCCESS');
            }
            die('FAILED');
        }
    }

    private function allinpayYunst(){
        if(getcustom('pay_allinpay')){
            $paramStr = @file_get_contents('php://input');
            if(strpos($paramStr,'&')!== false && strpos($paramStr,'=')!== false){
                parse_str($paramStr, $params);
            }else{
                $params = json_decode($paramStr,true);
            }
            //通联支付 云商通
            Log::write(__FILE__.__LINE__);
            Log::write($paramStr);
            Log::write($params);

            $postdata = $params['bizContent'] && !empty($params['bizContent'])?json_decode($params['bizContent'],true):[];
            if(!$postdata) die('{"status":"fail","msg":"参数存在空"}');

            $aid = $tablename = $platform = $bid = $opttype = '';
            if($postdata['extendInfo'] && !empty($postdata['extendInfo'])){
                $attach = explode(':',$postdata['extendInfo']);
                $aid = intval($attach[0]);
                $tablename = $attach[1];
                $platform  = $attach[2]??'';
                $bid       = $attach[3]??0;
                $opttype   = $attach[4]??'';
            }

            //检验请求
            $process = \app\custom\AllinpayYunst::process($aid,$params,$opttype);
            if(!$process) die('{"status":"fail","msg":"参数不符合要求"}');

            if($platform == 'allinpaymp')     $platform = 'mp';
            if($platform == 'allinpaywx')     $platform = 'wx';
            if($platform == 'allinpayalipay') $platform = 'alipay';
            if($platform == 'allinpayh5' || $platform == 'allinpayaliwxh5' || $platform == 'allinpayalialipayh5')  $platform = 'h5';

            if($postdata['status'] == 'OK'){
            	//判断是否是流水号
            	$trade_no = explode('D', $postdata['bizOrderNo']);
				$ordernum = $trade_no[0];
                if($params['notifyType'] == 'allinpay.yunst.orderService.pay'){
                	if($opttype == 'pay'){
                		$appinfo = \app\common\System::appinfo($aid,$platform);
	                    if($platform == 'h5'){
	                        $appinfo['appid'] = $appinfo['ali_appid'];
	                    }
	                    $isbusinesspay = 0;
	                    // if($attach[4]){
	                    //     $bid = intval($attach[4]);
	                    //     $isbusinesspay = 1;
	                    // }
	                    define('aid',$aid);
	                    $where = [];
	                    $where[] = ['aid','=',aid];
	                    $where[] = ['ordernum','=',$ordernum];;
	                    if($tablename){
	                        $where[] = ['type','=',$tablename];
	                    }
	                    $payorder = Db::name('payorder')->where($where)->find();
	                    if($payorder && $payorder['status'] == 0){
	                        Db::name('payorder')->where('id',$payorder['id'])->update(['platform'=>$platform,'isbusinesspay'=>$isbusinesspay]);
	                        if($trade_no[1]){
					            //说明是交易流水
					            $where2 = '1=1';
			                    if($tablename) $where2 = 'type = "'.$tablename.'"';
					            Db::name('pay_transaction')->where(['aid'=>$aid,'transaction_num'=>$postdata['bizOrderNo']])->where($where2)->update(['status'=>1,'isbusinesspay'=>$isbusinesspay]);
					        }
	                        $paytype = '微信支付';
	                        $paytypeid = 2;
	                        if($platform=='alipay'){
	                            $paytype = '支付宝支付';
	                            $paytypeid = 3;
	                        }
	                        $mid = $payorder['mid']?:0;
	                        //记录
                            $data = array();
                            $data['aid'] = aid;
                            $data['mid'] = $mid;
                            $data['openid']    = $postdata['acct'];
                            $data['tablename'] = $tablename;
                            $data['givescore'] = $this->givescore??0;
                            $data['ordernum']  = $postdata['bizOrderNo'];
                            $data['mch_id']    = $postdata['cusid'];
                            $data['transaction_id'] = $postdata['chnltrxid'];
                            $data['total_fee']  = $postdata['amount']/100;
                            $data['createtime'] = time();
                            if($paytypeid == 2){
                            	$data['platform'] = 'wx';
                                $data['id'] = Db::name('wxpay_log')->insertGetId($data);
                            }else{
                                $data['id'] = Db::name('alipay_log')->insertGetId($data);
                            }
	                        $rs = $this->setorder($tablename,$postdata['bizOrderNo'],$postdata['chnltrxid'],$postdata['amount'],$paytype,$paytypeid);
	                        if($rs['status'] == 1){

	                            //记录2
	                            $data2 = array();
	                            $data2['aid'] = aid;
	                            $data2['mid'] = $mid;
	                            $data2['tablename']  = $tablename;
	                            $data2['appId']      = $params['appId']??'';
	                            $data2['amount']     = $postdata['amount'];
	                            $data2['orderNo']    = $postdata['orderNo']??'';
	                            $data2['termrefnum'] = $postdata['termrefnum']??'';
	                            $data2['extendInfo'] = $postdata['extendInfo']??'';
	                            $data2['accttype']   = $postdata['accttype']??'';
	                            $data2['isPreConsume']= $postdata['isPreConsume']??'';
	                            $data2['chnltrxid']   = $postdata['chnltrxid']??'';
	                            $data2['payInterfaceOutTradeNo'] = $postdata['payInterfaceOutTradeNo']??'';
	                            $data2['buyerBizUserId']= $postdata['buyerBizUserId']??'';
	                            $data2['termauthno']    = $postdata['termauthno']??'';
	                            $data2['cusid']      = $postdata['cusid']??'';
	                            $data2['payInterfacetrxcode']= $postdata['payInterfacetrxcode']??'';
	                            $data2['channelExtendInfo']  = $postdata['channelExtendInfo'] && !empty($postdata['channelExtendInfo'])?json_encode($postdata['channelExtendInfo']):'';
	                            $data2['acct']       = $postdata['acct']??'';
	                            $data2['bizOrderNo'] = $postdata['bizOrderNo']??'';
	                            $data2['type']       = 'pay';
	                            $data2['createtime'] = time();
	                            Db::name('member_allinpay_yunst_order')->insert($data2);

	                            \app\common\Member::uplv(aid,$mid);
	                        }else{
	                            Log::write([
	                               'file'=>__FILE__.__FUNCTION__.__LINE__,
	                               'rs'=>$rs,
	                            ]);
	                        }
	                        //退款
	                        if($rs['status'] == 2){
	                            $payorder = $rs['payorder'];
	                            //通联支付 云商通
	                            $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',$payorder['mid'])->where('aid',aid)->where('memberType',3)->find();
	                            if($yunstuser){
	                                $refund_ordernum = $payorder['ordernum'].'_'.aid.'_'.rand(10000,99999);
	                                //通联支付
	                                $params = [
	                                    'aid'=>aid,'bid'=>$payorder['bid'],'mid'=>$payorder['mid'],'platform'=>$platform,'paytype'=>$platform,
	                                    'refund_desc'=>$rs['msg'],'tablename'=>$tablename,'paylog'=>$data,'totalprice'=>$data['total_fee'],'refundmoney'=>$data['total_fee'],'ordernum'=>$payorder['ordernum'],
	                                    'bizOrderNo'=>$refund_ordernum,'oriBizOrderNo'=>$postdata['bizOrderNo'],'bizUserId'=>$yunstuser['bizUserId'],'amount'=>$postdata['amount'],
	                                ];
	                                $rs = \app\custom\AllinpayYunst::refund($params);
	                            }
	                           //die('{"status":"fail","msg":"支付失败，已退款"}');
	                        }
	                    }
                	}else{
                		\app\custom\AllinpayYunst::deal_notify2($params,$postdata,$opttype);
                	}
                }
                die('{"status":"OK","msg":"成功"}');
            }else{
            	\app\custom\AllinpayYunst::deal_notify($params,$postdata);
            	die('{"status":"OK","msg":"成功"}');
            }
        }
    }

    /**
     * 微信小程序 消息推送支付，非普通微信支付
     * @param $aid
     * @param $postObj
     * @param $openid
     * @param $mchid
     * @param $out_trade_no
     * @param $transaction_id
     * @param $order_amount 订单总需支付金额，也即是真正下单总金额，单位为分
     * @param $wxpay_typeid 微信支付类型
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wxpayWxPush($aid,$postObj,$openid,$mchid,$out_trade_no,$transaction_id,$order_amount,$wxpay_typeid = 0)
    {
        $trade_no = explode('D', $out_trade_no);
        $ordernum = $trade_no[0];
        $payorder = Db::name('payorder')->where(['aid'=>$aid,'ordernum'=>$ordernum])->find();
        $tablename = $payorder['type'];
        if($trade_no[1]){
            //说明是交易流水
            Db::name('pay_transaction')->where(['aid'=>$aid,'type'=>$tablename,'payorderid'=>$payorder['id']])->update(['status'=>1]);
        }
        if(is_null($order_amount)){
            $paymoney = $payorder['money'];
            $order_amount = bcmul($payorder['money'],100,0);
        }
        else
            $paymoney = $order_amount*0.01;
        //记录
        $data = array();
        $data['aid'] = aid;
        $data['mid'] = $payorder['mid'];
        $data['openid'] = $openid;
        $data['tablename'] = $tablename;
        $data['givescore'] = $this->givescore;
        $data['ordernum'] = $out_trade_no;
        $data['mch_id'] = $mchid;
        $data['transaction_id'] = $transaction_id;
        $data['total_fee'] = $paymoney;
        $data['createtime'] = time();
//                $data['fenzhangmoney'] = $chouchengmoney;
//                $data['fenzhangmoney2'] = $chouchengmoney2;
//                $data['sub_mchid'] = $sub_mchid;
        $data['platform'] = 'wx';
        $data['bid'] = $payorder['bid'];
        $paylogid = Db::name('wxpay_log')->insertGetId($data);
//        $wxpay_typeid = 0;//微信支付类型

        $rs = $this->setorder($tablename,$ordernum,$transaction_id,$order_amount,'微信支付',2,$wxpay_typeid);
        if($rs['status'] == 1){
           \app\common\Member::uplv(aid,$payorder['mid']);
            return ['status'=>1,'msg'=>'支付成功'];
        }
        //退款
        if($rs['status'] == 2){
//            $payorder = $rs['payorder'];
//            \app\common\Wxpay::refund($aid,$payorder['platform'],$payorder['ordernum'],$payorder['money'],$paymoney,$rs['msg'],$payorder['bid'],$payorder);
//            return ['status'=>2,'msg'=>'退款'];
        }
        return $rs;
    }

    private function zhiyoubao(){
        if(getcustom('extend_zhiyoubao_theater')){
            $paramStr = @file_get_contents('php://input');

            if(strpos($paramStr,'&')!== false && strpos($paramStr,'=')!== false){
                parse_str($paramStr, $params);
            }else{
                $params = json_decode($paramStr,true);
            }
            //智游宝
            Log::write(__FILE__.__LINE__);
            Log::write($paramStr);
            Log::write($params);

            $appId = $params['appId']??'';
            if(!$appId){
                $data = ["code"=> "10001","msg"=> "appId参数不存在","data"=> "fail"];
                die(json_encode($data,JSON_UNESCAPED_UNICODE));
            }
            $name = $params['name']??'';
            if(!$name){
                $data = ["code"=> "10001","msg"=> "name参数不存在","data"=> "fail"];
                die(json_encode($data,JSON_UNESCAPED_UNICODE));
            }
            $postdata = $params['bizContent'] && !empty($params['bizContent'])?json_decode($params['bizContent'],true):[];
            if(!$postdata || !$postdata['content']){
                $data = ["code"=> "10001","msg"=> "bizContent参数不存在","data"=> "fail"];
                die(json_encode($data,JSON_UNESCAPED_UNICODE));
            }
            \app\custom\Zhiyoubao::deal_notify($appId,$name,$params,$postdata);
            $data = ["code"=> "10000","msg"=> "success","data"=> "success"];
            die(json_encode($data,JSON_UNESCAPED_UNICODE));
        }
    }
}
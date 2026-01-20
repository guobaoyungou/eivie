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
class Baidupay
{
	//百度小程序支付
	public static function build($aid,$mid,$title,$ordernum,$price,$tablename,$notify_url=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$baiduapp = \app\common\System::appinfo($aid,'baidu');

		$data = [];
		$data['appKey'] = $baiduapp['pay_appkey'];
		$data['dealId'] = $baiduapp['pay_dealId'];
		$data['tpOrderId'] = $ordernum;
		$data['totalAmount'] = intval(bcmul($price,100,2)).'';
		$data['rsaSign'] = \app\common\RSASign::sign($data,$baiduapp['pay_privatekey']);
		$data['dealTitle'] = $title;
		$data['signFieldsRange'] = 1;
		//$data['bizInfo'] = jsonEncode(['returnData'=>jsonEncode(['params'=>$aid.':'.$tablename])]);
		$bizInfoArr = [
            "tpData" => [
                "appKey" => $data['appKey'],
                "dealId" => $data['dealId'],
                "tpOrderId" => $data['tpOrderId'],
                "totalAmount" => $data['totalAmount'],
                "returnData"=> [
                    "params"=> $aid.':'.$tablename
                ]
            ],
        ];
        $data['bizInfo'] = json_encode($bizInfoArr);
		return ['status'=>1,'orderInfo'=>$data];
	}

	public static function refund($aid,$mid,$ordernum,$paynum,$totalprice,$refundmoney,$platform='wx',$refund_desc='退款'){
		$baiduapp = \app\common\System::appinfo($aid,'baidu');

		$url = 'https://openapi.baidu.com/rest/2.0/smartapp/pay/paymentservice/applyOrderRefund';
		$data = [];
		$data['access_token'] = self::access_token($aid);
		$data['applyRefundMoney'] = intval(bcmul($refundmoney,100));
		$data['bizRefundBatchId'] = date('YmdHis');
		$data['isSkipAudit'] = 1;
		$data['orderId'] = intval($paynum);
		$data['refundReason'] = $refund_desc;
		$data['refundType'] = 1;
		$data['tpOrderId'] = $ordernum;
		$data['userId'] = Db::name('baidupay_log')->where('aid',$aid)->where('ordernum',$ordernum)->value('userId');
		$data['pmAppKey'] = $baiduapp['pay_appkey'];
		$rs = request_post($url,$data);
		$rs = json_decode($rs,true);
		if($rs['errno'] != 0){
			return ['status'=>0,'msg'=>$rs['msg'],'rsdata'=>$rs];
		}
		return ['status'=>1,'msg'=>'退款发起成功'];
	}

	public static function access_token($aid){
		$baiduapp = \app\common\System::appinfo($aid,'baidu');
		if($baiduapp['access_token'] && $baiduapp['expires_time'] > time()){
			return $baiduapp['access_token'];
		}else{
			$url = 'https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id='.$baiduapp['appkey'].'&client_secret='.$baiduapp['appsecret'].'&scope=smartapp_snsapi_base';
			$res = json_decode(request_get($url));
			$access_token = $res->access_token;
			if($access_token) {
				Db::name('admin_setapp_baidu')->where('aid',$aid)->update(['access_token'=>$access_token,'expires_time'=>time()+$res->expires_in]);
				return $access_token;
			}else{
				return ['status'=>0,'msg'=>$res->error_description];
			}
		}
	}
}
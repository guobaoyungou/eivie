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
use pay\wechatpay\WxPayV3;
use think\facade\Db;
use think\facade\Log;
class NotifyV3
{
	public $member;
	public $givescore=0;
	//商家转账回调
	public function transfer(){
        $params = file_get_contents('php://input');
        $headers = request()->header();
        writeLog('微信回调进入','notify_v3');
        writeLog('回调head数据'.json_encode($headers),'notify_v3');
        writeLog('回调body数据'.json_encode($params),'notify_v3');
        $paysdk = new WxPayV3();
        $response = $paysdk->notify_wx($headers,$params);
        writeLog('回调验证'.json_encode($response),'notify');
        if(!empty($response['trade_state']) && $response['trade_state'] === 'SUCCESS'){

        }else{
            exit('fail');
        }
	}
}
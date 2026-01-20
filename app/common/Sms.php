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

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

// 导入 SMS 的 client
use TencentCloud\Sms\V20190711\SmsClient;
// 导入要请求接口对应的 Request 类
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
// 导入可选配置类
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use think\facade\Queue;

class Sms
{
	//发送短信 $templateCode 模板编号 $templateParam 参数 如:['code'=>589887]
	public static function send($aid=0,$tel,$templateCode,$templateParam=[]){
		$smsset = db('admin_set_sms')->where('aid',$aid)->find();
        $admin = db('admin')->where('id',$aid)->find();
        $smsset_admin = [];
        if(false){}else{
            if($smsset['status']!=1) return ['status'=>0,'msg'=>'短信功能未开启'];
            if(!$smsset['accesskey'] || !$smsset['accesssecret']){
                return ['status'=>0,'msg'=>'短信参数未配置'];
            }
            if($smsset['smstype'] == 2 && !$smsset['sdkappid']){//腾讯云短信
                return ['status'=>0,'msg'=>'短信应用AppID未配置'];
            }
            $signName = $smsset['sign_name'];
            if(strpos($templateCode,'tmpl_') === 0){
                if($smsset[$templateCode.'_st'] !=1 )  return ['status'=>0,'msg'=>'该短信模板未开启'];
                //判断是否是腾讯云字符长度限制模板
                if($smsset['smstype'] == 2 && $smsset['code_length'] == 1){
                    $oldtemplateCode = $templateCode;
                    $templateCode =  $templateCode.'_txy';
                    $templateCode = $smsset[$templateCode] ?: $smsset[$oldtemplateCode];
                }else{
                    $templateCode = $smsset[$templateCode];
                }
            }
        }
		if(!$signName) return ['status'=>0,'msg'=>'短信签名未配置'];
		if($smsset['smstype'] == 1){ //阿里云短信
			$rs = self::alisms($aid,$smsset['accesskey'],$smsset['accesssecret'],$signName,$templateCode,$tel,$templateParam);
		}elseif($smsset['smstype'] == 2){//腾讯云短信
            //判断是否开启变量长度限制
            if($smsset['code_length'] == 1){
                //处理数据长度限制 6字符
                $templateParam = self::resetTemplateData($templateParam);
            }
            elseif($smsset['code_length'] == 2 && $templateCode != 'tmpl_smscode'){
                //不含变量
                $templateParam = [];
            }
			$rs = self::tencentsms($aid,$smsset['accesskey'],$smsset['accesssecret'],$smsset['sdkappid'],$signName,$templateCode,$tel,$templateParam);
		}elseif($smsset['smstype'] == 4){//定制短信通道
		       self::customChannelSms($aid,$smsset['accesskey'],$smsset['accesssecret'],$signName,$templateCode,$tel,$templateParam);
        }
        $rs['status'] = 1;
        return $rs;
	}

	public static function alisms($aid,$accessKey,$accessSecret,$signName,$templateID,$tel,$templateParam){
		$paramdata = ['PhoneNumbers'=>$tel,'SignName'=>$signName,'TemplateCode'=>$templateID,'TemplateParam'=>$templateParam ? jsonEncode($templateParam) : []];
		AlibabaCloud::accessKeyClient($accessKey,$accessSecret)->regionId('cn-hangzhou')->asDefaultClient();
		try{
			$result = AlibabaCloud::rpc()->product('Dysmsapi')->scheme('https')->version('2017-05-25')->action('SendSms')->method('POST')->host('dysmsapi.aliyuncs.com')->options(['query'=>$paramdata])->request();
			$rs = $result->toArray();
			if($rs['Code'] == 'OK'){
				$data = [];
				$data['aid'] = $aid;
				$data['PhoneNumbers'] = $tel;
				$data['SignName'] = $signName;
				$data['TemplateCode'] = $templateID;
				$data['ip'] = request()->ip();
				$data['createtime'] = time();
				$data['createdate'] = date('Ymd');
				$data['biz_id'] = $rs['data']['BizId'];
				$data['TemplateParam'] = $paramdata['TemplateParam'];
				Db::name('smslog')->insert($data);
				return ['status'=>1,'data'=>$rs,'msg'=>'操作成功'];
			}else{
				return ['status'=>0,'data'=>$rs,'msg'=>$rs['Message']];
			}
		} catch (ClientException $e) {
			return ['status'=>0,'msg'=>$e->getErrorMessage()];
		} catch (ServerException $e) {
			return ['status'=>0,'msg'=>$e->getErrorMessage()];
		}
	}
	public static function tencentsms($aid,$accessKey,$accessSecret,$sdkAppid,$signName,$templateID,$tel,$templateParam){
		try {
			if(is_array($tel)){
				$tels = [];
				foreach($tel as $v){
					$tels[] = '+86'.$v;
				}
			}else{
				$tels = ['+86'.$tel];
			}
			$cred = new Credential($accessKey,$accessSecret);
			$httpProfile = new HttpProfile();
			$httpProfile->setReqMethod("GET");  // POST 请求（默认为 POST 请求）
			$httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒（默认60秒）
			$httpProfile->setEndpoint("sms.tencentcloudapi.com");  // 指定接入地域域名（默认就近接入）
			// 实例化一个 client 选项，可选，无特殊需求时可以跳过
			$clientProfile = new ClientProfile();
			$clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法（默认为 HmacSHA256）
			$clientProfile->setHttpProfile($httpProfile);
			$client = new SmsClient($cred,"ap-shanghai",$clientProfile);
			// 实例化一个 sms 发送短信请求对象，每个接口都会对应一个 request 对象。
			$req = new SendSmsRequest();
			/* 填充请求参数，这里 request 对象的成员变量即对应接口的入参
			* 您可以通过官网接口文档或跳转到 request 对象的定义处查看请求参数的定义
			* 基本类型的设置:
			* 帮助链接：
			* 短信控制台：https://console.cloud.tencent.com/smsv2
			* sms helper：https://cloud.tencent.com/document/product/382/3773 */
			/* 短信应用 ID: 在 [短信控制台] 添加应用后生成的实际 SDKAppID，例如1400006666 */
			$req->SmsSdkAppid = $sdkAppid;
			/* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，可登录 [短信控制台] 查看签名信息 */
			$req->Sign = $signName;
			/* 短信码号扩展号: 默认未开通，如需开通请联系 [sms helper] */
			//$req->ExtendCode = "0";
			/* 下发手机号码，采用 e.164 标准，+[国家或地区码][手机号]
			* 例如+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
			$req->PhoneNumberSet = $tels;
			/* 国际/港澳台短信 senderid: 国内短信填空，默认未开通，如需开通请联系 [sms helper] */
			$req->SenderId = "";
			/* 用户的 session 内容: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
			$req->SessionContext = "";
			/* 模板 ID: 必须填写已审核通过的模板 ID。可登录 [短信控制台] 查看模板 ID */
			$req->TemplateID = $templateID;
			/* 模板参数: 若无模板参数，则设置为空*/
			if($templateParam){
				$req->TemplateParamSet = array_values($templateParam);
			}else{
				$req->TemplateParamSet = [];
			}
			// 通过 client 对象调用 SendSms 方法发起请求。注意请求方法名与请求对象是对应的
			$resp = $client->SendSms($req);
			// 输出 JSON 格式的字符串回包
			if($resp->SendStatusSet[0]->Code=='Ok'){
				$data = [];
				$data['aid'] = $aid;
				$data['PhoneNumbers'] = is_array($tel) ? implode(',',$tel) : $tel;
				$data['SignName'] = $signName;
				$data['TemplateCode'] = $templateID;
				$data['ip'] = request()->ip();
				$data['createtime'] = time();
				$data['createdate'] = date('Ymd');
				$data['biz_id'] = '';
				if($req->TemplateParamSet)
				$data['TemplateParam'] = json_encode($req->TemplateParamSet);
				Db::name('smslog')->insert($data);
				return ['status'=>1,'msg'=>'发送成功'];
			}else{
				return ['status'=>0,'msg'=>$resp->SendStatusSet[0]->Message];
			}
		}catch(TencentCloudSDKException $e) {
			return ['status'=>0,'msg'=>'发送失败','error'=>$e->getMessage()];
		}
	}
	//短信定制通道 
    //$signName例如：点大网络

    public static function customChannelSms($aid,$username,$password,$signName,$templateID,$tel,$templateParam){
	   
        if(false){}else{
            //默认公开版,使用马到成功中转站
            $admin_name = Db::name('admin_set')->where('aid',$aid)->value('name');
            $url = 'https://sms.maijiaxin.cn/?s=/ApiSms/sendsms';

            $params = [
                'admin_name' => $admin_name,
                'username' => $username,
                'password' => $password,
                'tel' => $tel,
                'templateID' => $templateID,//模板id
                'signName' => '【'.$signName.'】', //签名，例如：点大网络
                'templateParam' => jsonEncode($templateParam),
                'ip' => request()->ip(),
                'request_url' =>PRE_URL,
                
            ];
           $rs= curl_post($url,$params);
            $rs = json_decode($rs,true); 
           if($rs['code'] == '200'){
                $data = [];
                $data['aid'] = $aid;
                $data['PhoneNumbers'] = $tel;
                $data['SignName'] = $signName;
                $data['TemplateCode'] = $templateID;
                $data['ip'] = request()->ip();
                $data['createtime'] = time();
                $data['createdate'] = date('Ymd');
                $data['biz_id'] = $rs['data']['BizId'];
                $data['TemplateParam'] = json_encode($templateParam);
                Db::name('smslog')->insert($data);
                return ['status'=>1,'data'=>$rs,'msg'=>'操作成功'];
            }else{
                return ['status'=>0,'data'=>$rs,'msg'=>$rs['Message']];
            }
        }
    }

    //处理模板数据
    private static function resetTemplateData($param){
        //订单号截取后六位
        $backParamField = ['ordernum','express_no'];
        //金额类型
        $moneyField = ['money','real_money','sy_money','givemoney'];
        //日期类型
        $dateField = ['date'];
        //时间类型
        $timeField = ['time','begintime'];
        //时间范围
        $timeRangeField = ['time_range'];
        //正则表达式
        $patterns = [
            // YYYY年MM月DD HH:MM:SS;  YYYY-MM-DD HH:MM:SS;  YYYY/MM/DD HH:MM:SS
            '/^(\d{4})年(0?[1-9]|1[0-2])月(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}):(\d{1,2}):(\d{1,2})$/',
            '/^(\d{4})-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}):(\d{1,2}):(\d{1,2})$/',
            '/^(\d{4})\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}):(\d{1,2}):(\d{1,2})$/',

            // YYYY年MM月DD HH:MM;  YYYY-MM-DD HH:MM;  YYYY/MM/DD HH:MM
            '/^(\d{4})年(0?[1-9]|1[0-2])月(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}):(\d{1,2})$/',
            '/^(\d{4})-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}):(\d{1,2})$/',
            '/^(\d{4})\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}):(\d{1,2})$/',

            // YYYY年MM月DD;  YYYY-MM-DD;  YYYY/MM/DD
            '/^(\d{4})年(0?[1-9]|1[0-2])月(0?[1-9]|[12][0-9]|3[01])$/',
            '/^(\d{4})-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01])$/',
            '/^(\d{4})\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])$/',

            // YYYY年MM月DD HH:MM~HH:MM;  YYYY-MM-DD HH:MM~HH:MM;  YYYY/MM/DD HH:MM~HH:MM
            '/^(\d{4})年(0?[1-9]|1[0-2])月(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}:\d{1,2})~(\d{1,2}:\d{1,2})$/',
            '/^(\d{4})-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}:\d{1,2})~(\d{1,2}:\d{1,2})$/',
            '/^(\d{4})/(0?[1-9]|1[0-2])/(0?[1-9]|[12][0-9]|3[01]) (\d{1,2}:\d{1,2})~(\d{1,2}:\d{1,2})$/',
        ];

        foreach ($param as $key => &$val){
            if(in_array($key,$backParamField)){
                //截取后六位
                $val = substr($val, -6);
            }else if(in_array($key,$moneyField)){
                //处理金额类型
                $money = trim($val);
                if (strlen($money) > 6) {
                    //判断是否带有小数
                    if (strpos($money, '.') !== false) {
                        list($int, $decimal) = explode('.', $money);
                        //判断小数是否是0
                        if($decimal > 0){
                            $param[$key] = substr($money, 0, 6);
                            $param[$key.'_1'] = substr($money, 6);
                        }else{
                            //整数部分长度超过六位
                            if(strlen($int) > 6){
                                $param[$key] = substr($int, 0, 6);
                                $param[$key.'_1'] = substr($int, 6);
                            }else{
                                $param[$key] = $int;
                                $param[$key.'_1'] = '';
                            }
                        }
                    }else{
                        //不存在小数
                        $param[$key] = substr($money, 0, 6);
                        $param[$key.'_1'] = substr($money, 6);
                    }
                }else{
                    //不超过六个字符
                    $param[$key] = $money;
                    $param[$key.'_1'] = '';
                }
            }else if(in_array($key,$dateField)){
                //处理日期类型
                $date = trim($val);
                unset($param[$key]);
                //时间戳类型
                if (is_numeric($date)) {
                    $param[$key.'_year'] = date('Y',$date);
                    $param[$key.'_month'] = date('m',$date);
                    $param[$key.'_day'] = date('d',$date);
                } else {
                    //默认值
                    $param[$key.'_year']  = '';
                    $param[$key.'_month'] =  '';
                    $param[$key.'_day']   =  '';
                    //匹配
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $date, $matches)) {
                            $param[$key.'_year'] = $matches[1];
                            $param[$key.'_month'] = $matches[2];
                            $param[$key.'_day'] = $matches[3];
                            break;
                        }
                    }
                }
            }else if(in_array($key,$timeField)){
                //处理时间类型
                unset($param[$key]);
                //匹配
                foreach ($patterns as $item => $pattern) {
                    if (preg_match($pattern, $val, $matches)) {
                        if($item <= 5){
                            //YYYY年MM月DD HH:MM:SS;  YYYY-MM-DD HH:MM:SS;  YYYY/MM/DD HH:MM:SS
                            //YYYY年MM月DD HH:MM;  YYYY-MM-DD HH:MM;  YYYY/MM/DD HH:MM
                            $param[$key.'_year'] = $matches[1];
                            $param[$key.'_month'] = $matches[2];
                            $param[$key.'_day'] = $matches[3];
                            $param[$key.'_hour'] = $matches[4];
                            $param[$key.'_minute'] = $matches[5];
                            $param[$key.'_second'] = isset($matches[6]) ? $matches[6] : 00;
                        }elseif ($item <= 8 && $item >= 6){
                            //YYYY年MM月DD;  YYYY-MM-DD;  YYYY/MM/DD
                            $param[$key.'_year'] = $matches[1];
                            $param[$key.'_month'] = $matches[2];
                            $param[$key.'_day'] = $matches[3];
                        }elseif ($item <= 11 && $item >= 9){
                            //YYYY年MM月DD HH:MM~HH:MM;  YYYY-MM-DD HH:MM~HH:MM;  YYYY/MM/DD HH:MM~HH:MM
                            $param[$key.'_year'] = $matches[1];
                            $param[$key.'_month'] = $matches[2];
                            $param[$key.'_day'] = $matches[3];
                            $param[$key.'_hour'] = $matches[4];
                            $param[$key.'_hour_2'] = $matches[5];
                        }
                        break;
                    }
                }
            }else if(in_array($key,$timeRangeField)){
                //处理时间范围类型
                unset($param[$key]);
                $param[$key.'_year'] = '';
                $param[$key.'_month'] = '';
                $param[$key.'_day'] = '';
                $param[$key.'_hour'] = '';
                $param[$key.'_hour_2'] = '';

                foreach ($patterns as $item => $pattern) {
                    if (preg_match($pattern, $val, $matches)) {
                        //YYYY年MM月DD HH:MM~HH:MM;  YYYY-MM-DD HH:MM~HH:MM;  YYYY/MM/DD HH:MM~HH:MM
                        $param[$key.'_year'] = $matches[1];
                        $param[$key.'_month'] = $matches[2];
                        $param[$key.'_day'] = $matches[3];
                        $param[$key.'_hour'] = $matches[4];
                        $param[$key.'_hour_2'] = $matches[5];
                        break;
                    }
                }
            }else{
                //截取前六位
                $val = mb_substr($val, 0, 6);
            }
        }

        //重新排列顺序
        $resetParam = [];
        foreach ($param as $index => $value) {
            $resetParam[$index] = $value;
            //金额类型
            if (in_array($index, $moneyField) && isset($param[$index . '_1'])) {
                $resetParam[$index . '_1'] = $param[$index . '_1'];
            }
        }
        return $resetParam;
    }
}
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
// | 公众号支付设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Formatter;

class Wxpay extends Common
{
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
	//公众号支付设置
    public function set(){
		if(request()->isPost()){
			$info = input('post.info/a');
			$rs = Db::name('admin_setapp_wx')->where('aid',aid)->find();
			$info['wxpay_mchid'] = trim($info['wxpay_mchid']);
			$info['wxpay_mchkey'] = trim($info['wxpay_mchkey']);
			$info['wxpay_sub_mchid'] = trim($info['wxpay_sub_mchid']);
			$info['wxpay_apiclient_cert'] = str_replace(PRE_URL.'/','',$info['wxpay_apiclient_cert']);
			$info['wxpay_apiclient_key'] = str_replace(PRE_URL.'/','',$info['wxpay_apiclient_key']);
            $info['wxpay_wechatpay_pem'] = str_replace(PRE_URL.'/','',$info['wxpay_wechatpay_pem']);
			if(!empty($info['wxpay_apiclient_cert']) && substr($info['wxpay_apiclient_cert'], -4) != '.pem'){
				return json(['status'=>0,'msg'=>'PEM证书格式错误']);
			}
			if(!empty($info['wxpay_apiclient_key']) && substr($info['wxpay_apiclient_key'], -4) != '.pem'){
				return json(['status'=>0,'msg'=>'证书密钥格式错误']);
			}
            if(!empty($info['wxpay_wechatpay_pem']) && substr($info['wxpay_wechatpay_pem'], -4) != '.pem'){
                return json(['status'=>0,'msg'=>'平台证书格式错误']);
            }
            //支付公钥
            $info['sign_type'] = $info['sign_type'];
            $info['public_key_id'] = trim($info['public_key_id']);
            $info['public_key_pem'] = str_replace(PRE_URL.'/','',$info['public_key_pem']);

			$info['sxpay_mno'] = trim($info['sxpay_mno']);
            $info['transfer_scene_id'] = trim($info['transfer_scene_id']);
            $info['transfer_scene_type'] = trim($info['transfer_scene_type']);
            $info['transfer_scene_content'] = trim($info['transfer_scene_content']);
            Db::name('admin_setapp_wx')->where('aid',aid)->update($info);
			\app\common\System::plog('微信小程序支付设置');
			return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
		}
		$info = Db::name('admin_setapp_wx')->where('aid',aid)->find();
		if(!$info) Db::name('admin_setapp_wx')->insert(['aid'=>aid]);
		View::assign('info',$info);
        //随行付进件状态
        $incomeStatus = \app\custom\Sxpay::getIncomeStatus(aid);
        View::assign('incomeStatus',$incomeStatus);

        $currencys= $this->currency();
        View::assign('currencys',$currencys);
         //服务商模式支付开关，默认开启
        $wxpay_fws_status = 1;
        View::assign('wxpay_fws_status',$wxpay_fws_status);
        //随行付支付开关，默认开启
        $sxpay_status = 1;
        View::assign('sxpay_status',$sxpay_status);
		return View::fetch();
	}
	// 海外版支持货币类型 对接直连模式该接入模式目前仅在香港及英国开放
	public function currency(){
		$arr = [
			'HKD'=>	'港币',
            'GBP'=> '英镑',
			// 'SGD'=>	'新加坡元',
			// 'MYR'=>	'马来西亚林吉特',
			// 'THB'=>	'泰铢',
			// 'JPY'=>	'日元',
			// 'AUD'=>	'澳元',
			// 'CAD'=>	'加元',
			// 'EUR'=>	'欧元',
			// 'USD'=>	'美元',
		];
		return $arr;
	}
	
	//下载海外微信V3支付用的平台证书
    public function download_pem(){
    	$appinfo = \app\common\System::appinfo(aid,'wx');
    	
    	$merchantId = $appinfo['wxpay_mchid_global'];
        $mchkey_global = $appinfo['wxpay_mchkey_global'];
        $merchantPrivateKeyFilePath = ROOT_PATH.$appinfo['wxpay_apiclient_key_global'];
        $merchantPrivateKeyInstance = PemUtil::loadPrivateKey($merchantPrivateKeyFilePath); // 私钥文件路径
        $merchantCertificateSerial = $appinfo['wxpay_serial_no_global'];
        $platformCertificateFilePath = ROOT_PATH.$appinfo['wxpay_wechatpay_pem_global'];
        $plate_pem = file_get_contents( $platformCertificateFilePath);
        $platformPublicKeyInstance = Rsa::from($plate_pem, Rsa::KEY_TYPE_PUBLIC);
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($plate_pem);

        // init a API V3 instance
        $instance = Builder::factory([
            'mchid'      => $merchantId,
            'serial'     => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'base_uri'   => 'https://apihk.mch.weixin.qq.com', // 强制香港节点
            'certs'      => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]); 

        $resp = $instance->chain('v3/global/certificates')->get();
        $cfData = json_decode($resp->getBody(), true);
        if($cfData){
            $content = $this->decryptToString($cfData['encrypt_certificate']['associated_data'], $cfData['encrypt_certificate']['nonce'], $cfData['encrypt_certificate']['ciphertext'],$mchkey_global);
            $cert_path = ROOT_PATH.'upload/'.aid.'/wechatkey';
            if (!is_dir($cert_path)) {
                mkdir($cert_path, 0755, true);
            }
            $cert_path .= "/{$merchantId}.pem";
            file_put_contents($cert_path, $content);
            $new_file = str_replace(ROOT_PATH, '', $cert_path);
            return ['status'=>1,'msg'=>'下载成功','data'=>$new_file];
        }

    }
    /**
     * Decrypt AEAD_AES_256_GCM ciphertext(官方案例-已改造)
     * @param string $associatedData AES GCM additional authentication data
     * @param string $nonceStr       AES GCM nonce
     * @param string $ciphertext     AES GCM cipher text
     * @return string|bool      Decrypted string on success or FALSE on failure
     */
    private function decryptToString($associatedData, $nonceStr, $ciphertext,$mchkey_global)
    {
        $auth_tag_length_byte = 16;

        $ciphertext = \base64_decode($ciphertext);
        if (strlen($ciphertext) <= $auth_tag_length_byte) {
            return false;
        }
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && \sodium_crypto_aead_aes256gcm_is_available()) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $mchkey_global);
        }
        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') && \Sodium\crypto_aead_aes256gcm_is_available()) {
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $mchkey_global);
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext   = substr($ciphertext, 0, -$auth_tag_length_byte);
            $authTag = substr($ciphertext, -$auth_tag_length_byte);
            return \openssl_decrypt($ctext, 'aes-256-gcm', $mchkey_global, \OPENSSL_RAW_DATA, $nonceStr,$authTag, $associatedData);
        }

        throw new \Exception('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }

}
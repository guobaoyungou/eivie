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
// 通用签名工具，基于openssl扩展，提供使用私钥生成签名和使用公钥验证签名的接口
class RSASign
{

    /**
     * @desc 使用私钥生成签名字符串
     * @param array $assocArr 入参数组
     * @param string $rsaPriKeyStr 私钥原始字符串，不含PEM格式前后缀
     * @return string 签名结果字符串
     * @throws Exception
     */
    public static function sign(array $assocArr, $rsaPriKeyStr)
    {
        $sign = '';
        if (empty($rsaPriKeyStr) || empty($assocArr)) {
            return $sign;
        }

        if (!function_exists('openssl_pkey_get_private') || !function_exists('openssl_sign')) {
            throw new Exception("openssl扩展不存在");
        }

        $rsaPriKeyPem = self::convertRSAKeyStr2Pem($rsaPriKeyStr, 1);
		//var_dump($rsaPriKeyPem);

        $priKey = openssl_pkey_get_private($rsaPriKeyPem);

        if (isset($assocArr['sign'])) {
            unset($assocArr['sign']);
        }
        // 参数按字典顺序排序
        ksort($assocArr); 

        $parts = array();
        foreach ($assocArr as $k => $v) {
            $parts[] = $k . '=' . $v;
        }
        $str = implode('&', $parts);
        openssl_sign($str, $sign, $priKey);
        openssl_free_key($priKey);

        return base64_encode($sign);
    }

    /**
     * @desc 使用公钥校验签名
     * @param array $assocArr 入参数据，签名属性名固定为rsaSign
     * @param string $rsaPubKeyStr 公钥原始字符串，不含PEM格式前后缀
     * @return bool true 验签通过|false 验签不通过
     * @throws Exception
     */
    public static function checkSign(array $assocArr, $rsaPubKeyStr)
    {
        if (!isset($assocArr['rsaSign']) || empty($assocArr) || empty($rsaPubKeyStr)) {
            return false;
        }

        if (!function_exists('openssl_pkey_get_public') || !function_exists('openssl_verify')) {
            throw new Exception("openssl扩展不存在");
        }

        $sign = $assocArr['rsaSign'];
        unset($assocArr['rsaSign']);

        if (empty($assocArr)) {
            return false;
        }
        // 参数按字典顺序排序
        ksort($assocArr);
        $parts = array();
        foreach ($assocArr as $k => $v) {
            $parts[] = $k . '=' . $v;
        }
        $str = implode('&', $parts);

        $sign = base64_decode($sign);
        $rsaPubKeyPem = self::convertRSAKeyStr2Pem($rsaPubKeyStr);
        $pubKey = openssl_pkey_get_public($rsaPubKeyPem);

        $result = (bool)openssl_verify($str, $sign, $pubKey);
        openssl_free_key($pubKey);

        return $result;
    }


    /**
     * @desc 将密钥由字符串（不换行）转为PEM格式
     * @param string $rsaKeyStr 原始密钥字符串
     * @param int $keyType 0 公钥|1 私钥，默认0
     * @return string PEM格式密钥
     * @throws Exception
     */
    public static function convertRSAKeyStr2Pem($rsaKeyStr, $keyType = 0)
    {

        $pemWidth = 64;
        $rsaKeyPem = '';
		if($keyType==1){
			$begin = '-----BEGIN RSA ';
			$end = '-----END RSA ';
		}else{
			$begin = '-----BEGIN ';
			$end = '-----END ';
		}
        $key = ' KEY-----';
        $type = $keyType ? 'PRIVATE' : 'PUBLIC';

        $keyPrefix = $begin . $type . $key;
        $keySuffix = $end . $type . $key;

        $rsaKeyPem .= $keyPrefix . "\n";
        $rsaKeyPem .= wordwrap($rsaKeyStr, $pemWidth, "\n", true) . "\n";
        $rsaKeyPem .= $keySuffix;
        if (!function_exists('openssl_pkey_get_public') || !function_exists('openssl_pkey_get_private')) {
            return false;
        }

        if ($keyType == 0 && false == openssl_pkey_get_public($rsaKeyPem)) {
            return false;
        }

        if ($keyType == 1 && false == openssl_pkey_get_private($rsaKeyPem)) {
            return false;
        }

        return $rsaKeyPem;
    }

}


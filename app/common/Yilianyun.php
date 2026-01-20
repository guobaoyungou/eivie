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

/**
 * Created by PhpStorm.
 * User: Gold
 * Date: 2024/7/18
 * Time: 11:23
 */

namespace app\common;


class Yilianyun
{
    // 开放平台平台公钥   RSA加密
    /**
     * Notes: 验证签名
     * @param $data
     * @param $privateKey
     */
    public static function verifySignature($data,$publicKey, $signature)
    {
        return openssl_verify($data, $signature,$publicKey, OPENSSL_ALGO_SHA256);
    }

    /**
     * Notes: 解密
     * @param $string
     * @param $key
     * @param $iv
     * @param $tag
     * @param $additionalData
     * @return false|string
     */
    public static function decode($string, $key='', $iv, $tag, $additionalData = 'transaction')
    {
        $decrypted = openssl_decrypt($string, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $additionalData);
        $decrypted = base64_decode($decrypted);
        return $decrypted;
    }

    /**
     * Notes: 根据url获取参数
     * @param string $message
     * @return array
     */
    public static function getPathParams($url){
        $ex_url = explode('?',$url);
        $params = explode('&',$ex_url[1]);
        $qqData = [];
        foreach ($params as $k => $v) {
            //将参数再次分割
            $str = explode('=',$v);
            //参数赋值
            $qqData[$str[0]] = $str[1];
        }
        return  $qqData;
    }
    /**
     * Notes: 返回处理
     * @param string $message
     * @return array
     */
    public static function result($message = '')
    {
        return [
            'message' => 'ok',
            'dataMsg' => $message,
        ];
    }

}
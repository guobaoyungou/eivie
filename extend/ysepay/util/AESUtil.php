<?php
//custom_file(pay_ysepay)

class AESUtil
{
    /**
     *  与java等的aes/ecb/pcks5加密一样效果
     * @param $data
     * @param $key
     * @return string
     */
    public static function encrypt($data, $key)
    {
        $encData = openssl_encrypt($data, 'aes-128-ecb', $key, OPENSSL_PKCS1_PADDING);

        return base64_encode($encData);

    }

    /**
     * 可以解密java等的aes/ecb/pcks5加密的内容
     * @param $data
     * @param $key
     * @author Farmer
     */
    public static function decrypt($data, $key)
    {
        return openssl_decrypt($data, 'aes-128-ecb', $key, OPENSSL_PKCS1_PADDING);
    }

}
<?php
//custom_file(pay_ysepay)

class StrUtil
{
    /**
     * 随机字符串
     */
    public static function randomStr($length = 16)
    {
        //字符组合
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str) - 1;
        $randStr = '';
        for ($i = 0; $i < $length; $i++) {
            $randStr .= $str[mt_rand(0, $len)];
        }
        return $randStr;
    }

}
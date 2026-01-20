<?php
//custom_file(pay_ysepay)

class SignUtil
{
    /**
     * map 转换成签名字符串，map的key做字典升序
     * @param $map array 签名参数
     * @return string
     */
    public static function map2SortedSignStr($map)
    {
        $array_keys = array_keys($map);
        sort($array_keys);
        $result = '';
        foreach ($array_keys as $key) {
            if ($key == 'sign') {
                continue;
            }
            if (!empty($result)) {
                $result .= '&';
            }
            $result .= $key . '=' . $map[$key];
        }
        return $result;
    }
}
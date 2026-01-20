<?php
//custom_file(pay_ysepay)
class LogUtil
{
    public static function debug($msg)
    {
        if (defined('DEBUG_MODE') and DEBUG_MODE) {
            if (is_array($msg)) {
                $msg = json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            echo "<pre>$msg</pre>";
        }
    }
}
<?php
/**
 * 现场活动大屏幕入口页面
 * PHP version 5.4+
 * 
 * @category Index
 * 
 * @package Index
 * 
 * */
define('IA_ROOT', str_replace("\\", '/', dirname(__FILE__)));
if (!file_exists(IA_ROOT . '/data/install.lock')) {
    header('location: ./install.php');
    exit;
}
header('location:/frame.php');

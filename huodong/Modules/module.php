<?php
/**
 * 模块页入口
 * PHP version 5.4+
 * 
 * @category Wall
 * 
 * @package Shake
 * 
 * */
@header("Content-type: text/html; charset=utf-8");
//站点目录,结尾不带斜杠
define('BASEPATH', str_replace(DIRECTORY_SEPARATOR.'Modules', '', dirname(__FILE__)));
//模块目录,结尾不带斜杠
define('MODULE_PATH', dirname(__FILE__));
//models目录
define("MODEL_PATH", BASEPATH.DIRECTORY_SEPARATOR."models");
//引入smarty
require_once BASEPATH.DIRECTORY_SEPARATOR.'smarty'.DIRECTORY_SEPARATOR.'Smarty.class.php';
//引入数据库链接
require_once BASEPATH.DIRECTORY_SEPARATOR.'common'.DIRECTORY_SEPARATOR.'db.class.php';
// echo $_SERVER["HTTP_X_REQUESTED_WITH"];


function modules_autoloader($className){
    $class=explode('\\', $className);
    $str=implode(DIRECTORY_SEPARATOR, $class);
    $filepath=BASEPATH.DIRECTORY_SEPARATOR.$str.'.php';
    if(file_exists($filepath)){
        require $filepath;
    }else{

    }
}
spl_autoload_register('modules_autoloader');

$params=$_GET;
$module=null;
//模块目录
if (!isset($params['m'])) {
    echo '参数错误';
    return;
}
$m=ucfirst($params['m']);
if (isset($params['c'])) {
    $modulepath=str_replace(DIRECTORY_SEPARATOR.'module.php', '', __FILE__);// '';
    $classname=ucfirst($params['c']);
    $filepath = $modulepath.DIRECTORY_SEPARATOR.$m.
        DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.$classname.'.php';
    if (!file_exists($filepath)) {
        
        echo '不存在此模块'; 
        return;
    }
    include_once $filepath;
    if (!class_exists($classname)) {
        echo '不存在此模块';
        return;
    }
    $module=new $classname();
}
if (isset($params['a'])) {
    if (!method_exists($classname, $params['a'])) {
        echo '不存在此功能';
        return;
    }
    $method=strval($params['a']);
    $module->$method();
}
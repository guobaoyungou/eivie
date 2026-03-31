<?php
/**
 * 摇大奖模块配置
 * PHP version 5.4+
 * 
 * @category Ydj
 * 
 * @package Ydj
 * 
 * */

$config=array(
    "admin"=>array('menu'=>array(
        "name"=>"摇大奖设置","submenu"=>array(
            array(
                "name"=>"轮次设置", 
                "link"=>"/Modules/module.php?m=ydj&c=admin&a=index"
            ),
            // array("name"=>"模板设置", "link"=>"/Modules/module.php?m=ydj&c=admin&a=themes")
        )
    )),
    "front"=>array("menu"=>array(
        "name"=>"摇大奖","link"=>"/Modules/module.php?m=ydj&c=front&a=index","icon"=>"/wall/themes/meepo/assets/images/icon/ico002-.png","shortcut"=>"ctrl+y"
    )),
    "mobile"=>array("menu"=>array("name"=>"摇大奖","link"=>"/Modules/module.php?m=ydj&c=mobile&a=index","icon"=>""))
);
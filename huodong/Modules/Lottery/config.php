<?php
/**
 * 摇大奖模块配置
 * PHP version 5.5+
 * 
 * @category Lottery
 * 
 * @package Lottery
 *
 * */

$config=[
    "admin"=>[
        'menu'=>[
            "name"=>"抽奖管理",
            "link"=>"/Modules/module.php?m=lottery&c=admin&a=index"
        ]
    ],
    "front"=>[
        "menu"=>[
            "name"=>"抽奖",
            "link"=>"/Modules/module.php?m=lottery&c=front&a=index","icon"=>"/wall/themes/meepo/assets/images/icon/lottery.png","shortcut"=>"ctrl+l"
        ]
    ]
];
<?php
/**
 * 摇大奖模块配置
 * PHP version 5.5+
 * 
 * @category ShakeGame
 * 
 * @package ShakeGame
 * 
 * */

$config=[
    "admin"=>[
        'menu'=>[
        "name"=>"游戏设置",
        "link"=>"/Modules/module.php?m=game&c=admin&a=index"
        ]
    ],
    "front"=>[
        "menu"=>[
            "name"=>"游戏","link"=>"/Modules/module.php?m=game&c=front&a=index",
            "icon"=>"/wall/themes/meepo/assets/images/icon/game.png",
            "shortcut"=>"ctrl+x"
        ]
    ],
    "mobile"=>[
        "menu"=>[
            "name"=>"游戏",
            "link"=>"/Modules/module.php?m=game&c=mobile&a=index",
            "icon"=>""
        ]
    ]
];
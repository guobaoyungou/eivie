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

namespace app\model;
use think\facade\Db;
class SystemSet
{
    //固定头部检索更新为组件
    static function initLocationPage(){
        //设置了定位模式的
        }
    static function locationpage(){
        return [
            "id"=>"M1680489055223628282",
            "temp"=>"location",
            "params"=> [
                "style"=> "1",
                "bgcolor"=> "#FFFFFF",
                "borderradius"=> 0,
                "bordercolor"=> "#F4F4F4",
                "color"=> "#333333",
                "showlevel"=> "2",
                "showsearch"=> "1",
                "bid"=> "0",
                "margin_x"=> 0,
                "margin_y"=> 0,
                "padding_x"=> 10,
                "padding_y"=> 5,
                "quanxian"=> [
                    "all"=> true
                ],
                "platform"=> [
                    "all"=> true
                ],
                "mendian"=> [
                    "all"=> true
                ],
                "mendian_sort"=> "sort",
                "showicon"=>"1",
                "hrefurl"=> "/pages/shop/search",
                "hrefname"=> "基础功能>商品搜索",
                "placeholder"=> "输入关键字搜索商品"
            ],
            "data"=> [
                [
                    "id"=> "L0000000000001",
                    "imgurl"=>PRE_URL."/static/img/cart_64.png",
                    "hrefurl"=> "/pages/shop/cart",
                    "hrefname"=> "基础功能>购物车"
                ],
                [
                    "id"=> "L0000000000002",
                    "imgurl"=> PRE_URL."/static/img/message_64.png",
                    "hrefurl"=> "/pages/kefu/index",
                    "hrefname"=> "基础功能>在线咨询"
                ]
            ],
            "other"=>"",
            "content"=> ""
        ];
    }
}
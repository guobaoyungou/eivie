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
class Score
{
    /*
     * $module 模块，artice,luntan,shortvideo,channels_reservation_live
     * $id 记录id
     * $type 赠送类型 read 阅读赠送，add发布赠送【审核通过】
     * 短视频奖励已更换为ShortVideoPlayAward统一处理
     * channels_reservation_live  预约直播奖励已更换为ApiWxChannelsLive(controller)处理
     */
	static function extGiveScore($aid,$mid,$module,$id,$type){
        return true;
	}
}
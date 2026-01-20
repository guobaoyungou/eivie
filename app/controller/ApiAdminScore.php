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

namespace app\controller;
use think\facade\Db;
class ApiAdminScore extends ApiAdmin
{
    public function initialize(){
        parent::initialize();
        if(bid > 0){
            $bset = Db::name('business_sysset')->where('aid',aid)->find();
            if($bset['business_selfscore'] != 1){
                echojson(['status'=>-4,'msg'=>'无权限操作']);
            }
        }
    }

    //商户个人互转积分
    public function businessMemberTransfer()
    {
        }

}
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

declare (strict_types = 1);

namespace app\validate;
use think\Validate;

class MapmarkOrder extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name' => 'require',
        'shop_type' => 'require',
        'shop_tel' => 'require',
        'shop_time'  => 'require',
        'address' => 'require',
        'mobile' => 'require',
        'license_img' => 'require',
        'shop_img' => 'require',
    ];
    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name' => '请填写标注名称',
        'shop_type' => '请填写经营类型',
        'shop_tel' => '请填写营业电话',
        'shop_time'  => '请填写营业时间',
        'address' => '请填写详细经营地址',
        'mobile' => '请填写联系电话',
        'license_img' => '请上传营业执照',
        'shop_img' => '请上传门面照片',

    ];
}
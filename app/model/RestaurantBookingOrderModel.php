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

use think\Model;

class RestaurantBookingOrderModel extends Model
{

    protected $name = 'restaurant_booking_order';
    protected $autoWriteTimestamp = true;

    public function getList($where = [], $page = 1, $limit = 15, $order = '')
    {
        $order = $order ? $order : 'id desc';
        $page = $page ? intval($page) : 1;
        $limit = $limit ? intval($limit) : 15;

        $model = $this;
        if ($where) {
            $model = $model->where($where);
        }
        $count = $model->count();
        if ($limit != 'all') {
            $model = $model->page($page, $limit);
        }

        $data = $model->with(['orderGoods', 'table'])->order($order)->select();

        return ['count' => $count, 'list' => $data];

    }

    public function orderGoods()
    {
        return $this->hasMany(RestaurantBookingOrderGoodsModel::class, 'order_id', 'id');
    }

    public function table()
    {
        return $this->hasOne(RestaurantTableModel::class, 'id', 'table_id');
    }

}
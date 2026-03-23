<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-订单模型
 * Class AiTravelPhotoOrder
 * @package app\model
 */
class AiTravelPhotoOrder extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_order';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段取出后的默认时间格式
    protected $dateFormat = false;
    
    // 创建时间字段
    protected $createTime = 'create_time';
    
    // 更新时间字段
    protected $updateTime = 'update_time';
    
    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'aid' => 'integer',
        'qrcode_id' => 'integer',
        'portrait_id' => 'integer',
        'uid' => 'integer',
        'bid' => 'integer',
        'mdid' => 'integer',
        'buy_type' => 'integer',
        'package_id' => 'integer',
        'total_price' => 'float',
        'discount_amount' => 'float',
        'actual_amount' => 'float',
        'status' => 'integer',
        'refund_status' => 'integer',
        'refund_amount' => 'float',
        'refund_time' => 'integer',
        'pay_time' => 'integer',
        'complete_time' => 'integer',
        'close_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
        'selected_count' => 'integer',
        'download_count' => 'integer',
        'download_limit' => 'integer',
    ];
    
    // 状态常量
    const STATUS_UNPAID = 0;        // 待支付
    const STATUS_PAID = 1;          // 已支付
    const STATUS_COMPLETED = 2;     // 已完成
    const STATUS_CLOSED = 3;        // 已关闭
    const STATUS_REFUNDED = 4;      // 已退款
    
    // 购买类型常量
    const BUY_TYPE_SINGLE = 1;      // 单张购买
    const BUY_TYPE_PACKAGE = 2;     // 套餐购买
    
    // 退款状态常量
    const REFUND_STATUS_NONE = 0;       // 无
    const REFUND_STATUS_APPLYING = 1;   // 申请中
    const REFUND_STATUS_REFUNDED = 2;   // 已退款
    const REFUND_STATUS_REJECTED = 3;   // 已驳回
    
    // 支付方式常量
    const PAY_TYPE_WXPAY = 'wxpay';
    const PAY_TYPE_ALIPAY = 'alipay';
    const PAY_TYPE_BALANCE = 'balance';
    
    /**
     * 关联二维码
     */
    public function qrcode()
    {
        return $this->belongsTo(AiTravelPhotoQrcode::class, 'qrcode_id', 'id');
    }
    
    /**
     * 关联人像
     */
    public function portrait()
    {
        return $this->belongsTo(AiTravelPhotoPortrait::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联套餐
     */
    public function package()
    {
        return $this->belongsTo(AiTravelPhotoPackage::class, 'package_id', 'id');
    }
    
    /**
     * 关联订单商品
     */
    public function goods()
    {
        return $this->hasMany(AiTravelPhotoOrderGoods::class, 'order_id', 'id');
    }
    
    /**
     * 关联用户相册
     */
    public function albums()
    {
        return $this->hasMany(AiTravelPhotoUserAlbum::class, 'order_id', 'id');
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_UNPAID => '待支付',
            self::STATUS_PAID => '已支付',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CLOSED => '已关闭',
            self::STATUS_REFUNDED => '已退款',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取购买类型文本
     */
    public function getBuyTypeTextAttr($value, $data)
    {
        $buyType = [
            self::BUY_TYPE_SINGLE => '单张购买',
            self::BUY_TYPE_PACKAGE => '套餐购买',
        ];
        return $buyType[$data['buy_type']] ?? '未知';
    }
    
    /**
     * 获取退款状态文本
     */
    public function getRefundStatusTextAttr($value, $data)
    {
        $refundStatus = [
            self::REFUND_STATUS_NONE => '无',
            self::REFUND_STATUS_APPLYING => '申请中',
            self::REFUND_STATUS_REFUNDED => '已退款',
            self::REFUND_STATUS_REJECTED => '已驳回',
        ];
        return $refundStatus[$data['refund_status']] ?? '无';
    }
    
    /**
     * 判断订单是否已超时（30分钟未支付）
     */
    public function isTimeout()
    {
        if ($this->status != self::STATUS_UNPAID) {
            return false;
        }
        return time() - $this->create_time > 1800; // 30分钟
    }
    
    /**
     * 搜索器：按订单号搜索
     */
    public function searchOrderNoAttr($query, $value)
    {
        if ($value) {
            $query->where('order_no', $value);
        }
    }
    
    /**
     * 搜索器：按用户ID搜索
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            $query->where('uid', $value);
        }
    }
    
    /**
     * 搜索器：按商家ID搜索
     */
    public function searchBidAttr($query, $value)
    {
        if ($value) {
            $query->where('bid', $value);
        }
    }
    
    /**
     * 搜索器：按状态搜索
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：按支付时间范围搜索
     */
    public function searchPayTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('pay_time', $value[0], $value[1]);
        }
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        return 'AITP' . date('Ymd') . mt_rand(10000000, 99999999);
    }
}

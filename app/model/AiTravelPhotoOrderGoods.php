<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-订单商品模型
 * Class AiTravelPhotoOrderGoods
 * @package app\model
 */
class AiTravelPhotoOrderGoods extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_order_goods';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    
    // 时间字段取出后的默认时间格式
    protected $dateFormat = false;
    
    // 创建时间字段
    protected $createTime = 'create_time';
    
    // 更新时间字段
    protected $updateTime = false;
    
    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'aid' => 'integer',
        'order_id' => 'integer',
        'result_id' => 'integer',
        'type' => 'integer',
        'price' => 'float',
        'num' => 'integer',
        'total_price' => 'float',
        'status' => 'integer',
        'create_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_NORMAL = 1;        // 正常
    const STATUS_REFUNDED = 2;      // 已退款
    
    // 类型常量
    const TYPE_IMAGE = 1;           // 图片
    const TYPE_VIDEO = 2;           // 视频
    
    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(AiTravelPhotoOrder::class, 'order_id', 'id');
    }
    
    /**
     * 关联结果
     */
    public function result()
    {
        return $this->belongsTo(AiTravelPhotoResult::class, 'result_id', 'id');
    }
    
    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $type = [
            self::TYPE_IMAGE => '图片',
            self::TYPE_VIDEO => '视频',
        ];
        return $type[$data['type']] ?? '未知';
    }
    
    /**
     * 搜索器：按订单ID搜索
     */
    public function searchOrderIdAttr($query, $value)
    {
        if ($value) {
            $query->where('order_id', $value);
        }
    }
    
    /**
     * 搜索器：按结果ID搜索
     */
    public function searchResultIdAttr($query, $value)
    {
        if ($value) {
            $query->where('result_id', $value);
        }
    }
}

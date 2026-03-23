<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-套餐模型
 * Class AiTravelPhotoPackage
 * @package app\model
 */
class AiTravelPhotoPackage extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_package';
    
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
        'bid' => 'integer',
        'price' => 'float',
        'original_price' => 'float',
        'num' => 'integer',
        'video_num' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'is_recommend' => 'integer',
        'valid_days' => 'integer',
        'sale_count' => 'integer',
        'stock' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
        'unit_price' => 'float',
        'is_default' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用
    
    // 标签常量
    const TAG_RECOMMEND = 'recommend';  // 推荐
    const TAG_HOT = 'hot';              // 热门
    const TAG_LIMITED = 'limited';      // 限时
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(AiTravelPhotoOrder::class, 'package_id', 'id');
    }
    
    /**
     * 获取额外服务（JSON转数组）
     */
    public function getExtraServicesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 设置额外服务（数组转JSON）
     */
    public function setExtraServicesAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取折扣率
     */
    public function getDiscountRateAttr($value, $data)
    {
        $originalPrice = $data['original_price'] ?? 0;
        if ($originalPrice == 0) {
            return 0;
        }
        $price = $data['price'] ?? 0;
        return round($price / $originalPrice * 10, 1);
    }
    
    /**
     * 判断是否有库存
     */
    public function hasStock()
    {
        if ($this->stock == -1) {
            return true; // 不限库存
        }
        return $this->stock > 0;
    }
    
    /**
     * 判断是否在有效期内
     */
    public function isValid()
    {
        $now = time();
        if ($this->start_time > 0 && $now < $this->start_time) {
            return false;
        }
        if ($this->end_time > 0 && $now > $this->end_time) {
            return false;
        }
        return true;
    }
    
    /**
     * 搜索器：按商家ID搜索
     */
    public function searchBidAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
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
     * 搜索器：按是否推荐搜索
     */
    public function searchIsRecommendAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_recommend', $value);
        }
    }
}

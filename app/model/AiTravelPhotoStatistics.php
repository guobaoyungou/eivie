<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-统计模型
 * Class AiTravelPhotoStatistics
 * @package app\model
 */
class AiTravelPhotoStatistics extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_statistics';
    
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
        'mdid' => 'integer',
        'upload_count' => 'integer',
        'generation_count' => 'integer',
        'video_count' => 'integer',
        'success_count' => 'integer',
        'fail_count' => 'integer',
        'order_count' => 'integer',
        'order_amount' => 'float',
        'paid_count' => 'integer',
        'paid_amount' => 'float',
        'refund_count' => 'integer',
        'refund_amount' => 'float',
        'scan_count' => 'integer',
        'unique_scan_count' => 'integer',
        'conversion_rate' => 'float',
        'avg_order_amount' => 'float',
        'cost_tokens' => 'integer',
        'cost_amount' => 'float',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
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
     * 搜索器：按统计日期搜索
     */
    public function searchStatDateAttr($query, $value)
    {
        if ($value) {
            $query->where('stat_date', $value);
        }
    }
    
    /**
     * 搜索器：按日期范围搜索
     */
    public function searchDateRangeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetween('stat_date', [$value[0], $value[1]]);
        }
    }
    
    /**
     * 计算转化率
     */
    public function calculateConversionRate()
    {
        if ($this->unique_scan_count == 0) {
            $this->conversion_rate = 0;
        } else {
            $this->conversion_rate = round($this->paid_count / $this->unique_scan_count * 100, 2);
        }
        return $this->conversion_rate;
    }
    
    /**
     * 计算客单价
     */
    public function calculateAvgOrderAmount()
    {
        if ($this->paid_count == 0) {
            $this->avg_order_amount = 0;
        } else {
            $this->avg_order_amount = round($this->paid_amount / $this->paid_count, 2);
        }
        return $this->avg_order_amount;
    }
}

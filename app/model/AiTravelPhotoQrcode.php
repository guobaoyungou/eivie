<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-二维码模型
 * Class AiTravelPhotoQrcode
 * @package app\model
 */
class AiTravelPhotoQrcode extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_qrcode';
    
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
        'portrait_id' => 'integer',
        'bid' => 'integer',
        'scan_count' => 'integer',
        'unique_scan_count' => 'integer',
        'order_count' => 'integer',
        'order_amount' => 'float',
        'first_scan_time' => 'integer',
        'last_scan_time' => 'integer',
        'status' => 'integer',
        'expire_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
        'qrcode_type' => 'integer',
    ];
    
    // 状态常量
    const STATUS_INVALID = 0;   // 失效
    const STATUS_VALID = 1;     // 有效
    
    // 二维码类型常量
    const QRCODE_TYPE_PICK = 1;    // 选片页二维码
    const QRCODE_TYPE_MP = 2;      // 公众号二维码
    
    /**
     * 关联人像
     */
    public function portrait()
    {
        return $this->belongsTo(AiTravelPhotoPortrait::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(AiTravelPhotoOrder::class, 'qrcode_id', 'id');
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_INVALID => '失效',
            self::STATUS_VALID => '有效',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取转化率
     */
    public function getConversionRateAttr($value, $data)
    {
        $uniqueScanCount = $data['unique_scan_count'] ?? 0;
        if ($uniqueScanCount == 0) {
            return 0;
        }
        $orderCount = $data['order_count'] ?? 0;
        return round($orderCount / $uniqueScanCount * 100, 2);
    }
    
    /**
     * 判断是否已过期
     */
    public function isExpired()
    {
        if ($this->expire_time == 0) {
            return false;
        }
        return time() > $this->expire_time;
    }
    
    /**
     * 搜索器：按人像ID搜索
     */
    public function searchPortraitIdAttr($query, $value)
    {
        if ($value) {
            $query->where('portrait_id', $value);
        }
    }
    
    /**
     * 搜索器：按二维码搜索
     */
    public function searchQrcodeAttr($query, $value)
    {
        if ($value) {
            $query->where('qrcode', $value);
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
}

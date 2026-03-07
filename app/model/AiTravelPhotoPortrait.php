<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-人像模型
 * Class AiTravelPhotoPortrait
 * @package app\model
 */
class AiTravelPhotoPortrait extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_portrait';
    
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
        'uid' => 'integer',
        'bid' => 'integer',
        'mdid' => 'integer',
        'device_id' => 'integer',
        'type' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'shoot_time' => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_NORMAL = 1;    // 正常
    const STATUS_DELETED = 2;   // 已删除
    
    // 上传类型常量
    const TYPE_BUSINESS = 1;    // 商家上传
    const TYPE_USER = 2;        // 用户上传
    
    /**
     * 关联设备
     */
    public function device()
    {
        return $this->belongsTo(AiTravelPhotoDevice::class, 'device_id', 'id');
    }
    
    /**
     * 关联生成记录
     */
    public function generations()
    {
        return $this->hasMany(AiTravelPhotoGeneration::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联二维码
     */
    public function qrcodes()
    {
        return $this->hasMany(AiTravelPhotoQrcode::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联结果
     */
    public function results()
    {
        return $this->hasMany(AiTravelPhotoResult::class, 'portrait_id', 'id');
    }
    
    /**
     * 获取EXIF数据（JSON转数组）
     */
    public function getExifDataAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 设置EXIF数据（数组转JSON）
     */
    public function setExifDataAttr($value)
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
            self::STATUS_NORMAL => '正常',
            self::STATUS_DELETED => '已删除',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取上传类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $type = [
            self::TYPE_BUSINESS => '商家上传',
            self::TYPE_USER => '用户上传',
        ];
        return $type[$data['type']] ?? '未知';
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
     * 搜索器：按用户ID搜索
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            $query->where('uid', $value);
        }
    }
    
    /**
     * 搜索器：按设备ID搜索
     */
    public function searchDeviceIdAttr($query, $value)
    {
        if ($value) {
            $query->where('device_id', $value);
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
     * 搜索器：按MD5搜索
     */
    public function searchMd5Attr($query, $value)
    {
        if ($value) {
            $query->where('md5', $value);
        }
    }
    
    /**
     * 搜索器：按创建时间范围搜索
     */
    public function searchCreateTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('create_time', $value[0], $value[1]);
        }
    }
}

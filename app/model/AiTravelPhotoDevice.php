<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-设备模型
 * Class AiTravelPhotoDevice
 * @package app\model
 */
class AiTravelPhotoDevice extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_device';
    
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
        'status' => 'integer',
        'upload_count' => 'integer',
        'success_count' => 'integer',
        'fail_count' => 'integer',
        'last_upload_time' => 'integer',
        'last_online_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_OFFLINE = 0;   // 离线
    const STATUS_ONLINE = 1;    // 在线
    const STATUS_ABNORMAL = 2;  // 异常
    
    /**
     * 关联人像
     */
    public function portraits()
    {
        return $this->hasMany(AiTravelPhotoPortrait::class, 'device_id', 'id');
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_OFFLINE => '离线',
            self::STATUS_ONLINE => '在线',
            self::STATUS_ABNORMAL => '异常',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取成功率
     */
    public function getSuccessRateAttr($value, $data)
    {
        $uploadCount = $data['upload_count'] ?? 0;
        if ($uploadCount == 0) {
            return 0;
        }
        $successCount = $data['success_count'] ?? 0;
        return round($successCount / $uploadCount * 100, 2);
    }
    
    /**
     * 判断设备是否在线（5分钟内有心跳）
     */
    public function isOnline()
    {
        if ($this->last_online_time == 0) {
            return false;
        }
        return time() - $this->last_online_time < 300; // 5分钟
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
     * 生成设备Token
     * @return string 64位MD5字符串
     */
    public static function generateDeviceToken(): string
    {
        // uniqid() 的第一个参数必须是字符串类型
        return md5(uniqid((string)mt_rand(), true));
    }
}

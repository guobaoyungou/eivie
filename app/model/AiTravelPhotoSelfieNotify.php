<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-自拍通知请求模型
 * Class AiTravelPhotoSelfieNotify
 * @package app\model
 */
class AiTravelPhotoSelfieNotify extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_selfie_notify';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = false;
    
    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'aid' => 'integer',
        'bid' => 'integer',
        'mdid' => 'integer',
        'portrait_id' => 'integer',
        'uid' => 'integer',
        'notify_type' => 'integer',
        'notify_status' => 'integer',
        'create_time' => 'integer',
        'notify_time' => 'integer',
    ];
    
    // 通知方式常量
    const NOTIFY_TYPE_KEFU = 1;     // 客服消息
    const NOTIFY_TYPE_TEMPLATE = 2; // 模板消息
    
    // 通知状态常量
    const STATUS_PENDING = 0;   // 待通知
    const STATUS_SENT = 1;      // 已通知
    const STATUS_FAILED = 2;    // 通知失败
    
    /**
     * 关联人像
     */
    public function portrait()
    {
        return $this->belongsTo(AiTravelPhotoPortrait::class, 'portrait_id', 'id');
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
     * 搜索器：按openid搜索
     */
    public function searchOpenidAttr($query, $value)
    {
        if ($value) {
            $query->where('openid', $value);
        }
    }
    
    /**
     * 搜索器：按通知状态搜索
     */
    public function searchNotifyStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('notify_status', $value);
        }
    }
}

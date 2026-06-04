<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-合成模板活动二维码模型
 * Class AiTravelPhotoSynthesisActivity
 * @package app\model
 */
class AiTravelPhotoSynthesisActivity extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_synthesis_activity';

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
        'template_id' => 'integer',
        'scan_count' => 'integer',
        'unique_scan_count' => 'integer',
        'gen_count' => 'integer',
        'order_count' => 'integer',
        'total_amount' => 'float',
        'price' => 'float',
        'status' => 'integer',
        'expire_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用

    /**
     * 关联合成模板
     */
    public function template()
    {
        return $this->belongsTo(AiTravelPhotoSynthesisTemplate::class, 'template_id', 'id');
    }

    /**
     * 关联用户照片生成记录
     */
    public function userPhotos()
    {
        return $this->hasMany(AiTravelPhotoSynthesisUserPhoto::class, 'activity_id', 'id');
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
     * 判断活动是否已过期
     */
    public function isExpired(): bool
    {
        if ($this->expire_time == 0) {
            return false;
        }
        return time() > $this->expire_time;
    }

    /**
     * 搜索器：按商户ID搜索
     */
    public function searchAidAttr($query, $value)
    {
        if ($value) {
            $query->where('aid', $value);
        }
    }

    /**
     * 搜索器：按门店ID搜索
     */
    public function searchBidAttr($query, $value)
    {
        if ($value) {
            $query->where('bid', $value);
        }
    }

    /**
     * 搜索器：按模板ID搜索
     */
    public function searchTemplateIdAttr($query, $value)
    {
        if ($value) {
            $query->where('template_id', $value);
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
     * 搜索器：按二维码token搜索
     */
    public function searchQrcodeTokenAttr($query, $value)
    {
        if ($value) {
            $query->where('qrcode_token', $value);
        }
    }
}

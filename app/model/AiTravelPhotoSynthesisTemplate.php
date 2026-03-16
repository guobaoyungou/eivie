<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-合成模板模型
 * Class AiTravelPhotoSynthesisTemplate
 * @package app\model
 */
class AiTravelPhotoSynthesisTemplate extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_synthesis_template';

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
        'model_id' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_NORMAL = 1;    // 正常

    /**
     * 获取图片数组（JSON转数组）
     */
    public function getImagesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置图片数组（数组转JSON）
     */
    public function setImagesAttr($value)
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
        ];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 搜索器：按商家ID搜索
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
     * 搜索器：按状态搜索
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }
}

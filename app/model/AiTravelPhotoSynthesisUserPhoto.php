<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-合成活动用户照片生成记录模型
 * Class AiTravelPhotoSynthesisUserPhoto
 * @package app\model
 */
class AiTravelPhotoSynthesisUserPhoto extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_synthesis_user_photo';

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
        'activity_id' => 'integer',
        'uid' => 'integer',
        'tag_is_multi' => 'integer',
        'tag_status' => 'integer',
        'result_id' => 'integer',
        'gen_status' => 'integer',
        'order_id' => 'integer',
        'paid' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    // 标签状态常量
    const TAG_STATUS_PENDING = 0;    // 待识别
    const TAG_STATUS_PROCESSING = 1; // 识别中
    const TAG_STATUS_COMPLETED = 2;  // 已完成
    const TAG_STATUS_FAILED = 3;     // 失败

    // 生成状态常量
    const GEN_STATUS_PENDING = 0;    // 待生成
    const GEN_STATUS_PROCESSING = 1; // 生成中
    const GEN_STATUS_COMPLETED = 2;  // 已完成
    const GEN_STATUS_FAILED = 3;     // 失败

    // 支付状态常量
    const PAID_NO = 0;   // 未支付
    const PAID_YES = 1;  // 已支付

    /**
     * 关联活动
     */
    public function activity()
    {
        return $this->belongsTo(AiTravelPhotoSynthesisActivity::class, 'activity_id', 'id');
    }

    /**
     * 获取标签JSON
     */
    public function getTagRawAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置标签JSON
     */
    public function setTagRawAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    /**
     * 获取标签状态文本
     */
    public function getTagStatusTextAttr($value, $data)
    {
        $status = [
            self::TAG_STATUS_PENDING => '待识别',
            self::TAG_STATUS_PROCESSING => '识别中',
            self::TAG_STATUS_COMPLETED => '已完成',
            self::TAG_STATUS_FAILED => '失败',
        ];
        return $status[$data['tag_status']] ?? '未知';
    }

    /**
     * 获取生成状态文本
     */
    public function getGenStatusTextAttr($value, $data)
    {
        $status = [
            self::GEN_STATUS_PENDING => '待生成',
            self::GEN_STATUS_PROCESSING => '生成中',
            self::GEN_STATUS_COMPLETED => '已完成',
            self::GEN_STATUS_FAILED => '失败',
        ];
        return $status[$data['gen_status']] ?? '未知';
    }

    /**
     * 搜索器：按活动ID搜索
     */
    public function searchActivityIdAttr($query, $value)
    {
        if ($value) {
            $query->where('activity_id', $value);
        }
    }

    /**
     * 搜索器：按OpenID搜索
     */
    public function searchOpenidAttr($query, $value)
    {
        if ($value) {
            $query->where('openid', $value);
        }
    }

    /**
     * 搜索器：按生成状态搜索
     */
    public function searchGenStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('gen_status', $value);
        }
    }
}

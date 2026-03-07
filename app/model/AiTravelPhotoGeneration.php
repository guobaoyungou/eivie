<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-生成记录模型
 * Class AiTravelPhotoGeneration
 * @package app\model
 */
class AiTravelPhotoGeneration extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_generation';
    
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
        'scene_id' => 'integer',
        'uid' => 'integer',
        'bid' => 'integer',
        'mdid' => 'integer',
        'type' => 'integer',
        'generation_type' => 'integer',
        'scene_type' => 'integer',
        'status' => 'integer',
        'retry_count' => 'integer',
        'cost_time' => 'integer',
        'cost_tokens' => 'integer',
        'cost_amount' => 'float',
        'queue_time' => 'integer',
        'start_time' => 'integer',
        'finish_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_PENDING = 0;       // 待处理
    const STATUS_PROCESSING = 1;    // 处理中
    const STATUS_SUCCESS = 2;       // 成功
    const STATUS_FAILED = 3;        // 失败
    const STATUS_CANCELLED = 4;     // 已取消
    
    // 生成类型常量
    const TYPE_AUTO = 1;            // 商家自动
    const TYPE_MANUAL = 2;          // 用户手动
    
    // 生成方式常量
    const GENERATION_TYPE_IMAGE = 1;    // 图生图
    const GENERATION_TYPE_MULTI = 2;    // 多镜头
    const GENERATION_TYPE_VIDEO = 3;    // 图生视频
    
    // 模型类型常量
    const MODEL_TYPE_ALIYUN_TONGYI = 'aliyun_tongyi';
    const MODEL_TYPE_KLING_AI = 'kling_ai';
    
    /**
     * 关联人像
     */
    public function portrait()
    {
        return $this->belongsTo(AiTravelPhotoPortrait::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联场景
     */
    public function scene()
    {
        return $this->belongsTo(AiTravelPhotoScene::class, 'scene_id', 'id');
    }
    
    /**
     * 关联结果
     */
    public function results()
    {
        return $this->hasMany(AiTravelPhotoResult::class, 'generation_id', 'id');
    }
    
    /**
     * 获取模型参数（JSON转数组）
     */
    public function getModelParamsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 设置模型参数（数组转JSON）
     */
    public function setModelParamsAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }
    
    /**
     * 获取场景类型文本
     */
    public function getSceneTypeTextAttr($value, $data)
    {
        return AiTravelPhotoScene::getSceneTypeText($data['scene_type'] ?? 0);
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取生成方式文本
     */
    public function getGenerationTypeTextAttr($value, $data)
    {
        $type = [
            self::GENERATION_TYPE_IMAGE => '图生图',
            self::GENERATION_TYPE_MULTI => '多镜头',
            self::GENERATION_TYPE_VIDEO => '图生视频',
        ];
        return $type[$data['generation_type']] ?? '未知';
    }
    
    /**
     * 搜索器：按场景类型搜索
     */
    public function searchSceneTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('scene_type', $value);
        }
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
     * 搜索器：按场景ID搜索
     */
    public function searchSceneIdAttr($query, $value)
    {
        if ($value) {
            $query->where('scene_id', $value);
        }
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
     * 搜索器：按状态搜索
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：按生成方式搜索
     */
    public function searchGenerationTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('generation_type', $value);
        }
    }
    
    /**
     * 搜索器：按任务ID搜索
     */
    public function searchTaskIdAttr($query, $value)
    {
        if ($value) {
            $query->where('task_id', $value);
        }
    }
    
    /**
     * 判断是否可以重试
     */
    public function canRetry()
    {
        return $this->status == self::STATUS_FAILED && $this->retry_count < 3;
    }
}

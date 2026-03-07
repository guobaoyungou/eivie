<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-AI模型配置模型
 * Class AiTravelPhotoModel
 * @package app\model
 */
class AiTravelPhotoModel extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_model';
    
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
        'category_id' => 'integer',
        'timeout' => 'integer',
        'max_retry' => 'integer',
        'cost_per_image' => 'float',
        'cost_per_video' => 'float',
        'cost_per_token' => 'float',
        'status' => 'integer',
        'is_default' => 'integer',
        'sort' => 'integer',
        'use_count' => 'integer',
        'success_count' => 'integer',
        'fail_count' => 'integer',
        'avg_time' => 'integer',
        'total_cost' => 'float',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用
    
    // 模型类型常量
    const MODEL_TYPE_ALIYUN_TONGYI = 'aliyun_tongyi';      // 阿里百炼通义万相
    const MODEL_TYPE_KLING_AI = 'kling_ai';                // 可灵AI
    
    /**
     * 关联场景
     */
    public function scenes()
    {
        return $this->hasMany(AiTravelPhotoScene::class, 'model_id', 'id');
    }
    
    /**
     * 获取API示例（JSON转数组）
     */
    public function getApiExampleAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 设置API示例（数组转JSON）
     */
    public function setApiExampleAttr($value)
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
     * 获取成功率
     */
    public function getSuccessRateAttr($value, $data)
    {
        $useCount = $data['use_count'] ?? 0;
        if ($useCount == 0) {
            return 0;
        }
        $successCount = $data['success_count'] ?? 0;
        return round($successCount / $useCount * 100, 2);
    }
    
    /**
     * 搜索器：按模型类型搜索
     */
    public function searchModelTypeAttr($query, $value)
    {
        if ($value) {
            $query->where('model_type', $value);
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
     * 搜索器：按是否默认搜索
     */
    public function searchIsDefaultAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_default', $value);
        }
    }
    
    /**
     * 获取模型类型列表
     */
    public static function getModelTypeList()
    {
        return [
            self::MODEL_TYPE_ALIYUN_TONGYI => '阿里百炼通义万相',
            self::MODEL_TYPE_KLING_AI => '可灵AI',
        ];
    }
}

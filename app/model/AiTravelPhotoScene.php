<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-场景模型
 * Class AiTravelPhotoScene
 * @package app\model
 */
class AiTravelPhotoScene extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_scene';
    
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
        'scene_type' => 'integer',
        'model_id' => 'integer',
        'api_config_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'is_public' => 'integer',
        'is_recommend' => 'integer',
        'use_count' => 'integer',
        'success_count' => 'integer',
        'fail_count' => 'integer',
        'avg_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用
    
    // 场景类型常量
    const SCENE_TYPE_IMAGE_SINGLE = 1;      // 图生图-单图编辑
    const SCENE_TYPE_IMAGE_MULTI = 2;       // 图生图-多图融合
    const SCENE_TYPE_VIDEO_FIRST = 3;       // 视频生成-首帧
    const SCENE_TYPE_VIDEO_FIRST_LAST = 4;  // 视频生成-首尾帧
    const SCENE_TYPE_VIDEO_EFFECT = 5;      // 视频生成-特效
    const SCENE_TYPE_VIDEO_REFERENCE = 6;   // 视频生成-参考生成
    
    // 分类常量
    const CATEGORY_SCENERY = '风景';
    const CATEGORY_PORTRAIT = '人物';
    const CATEGORY_CREATIVE = '创意';
    const CATEGORY_FESTIVAL = '节日';
    const CATEGORY_ANCIENT = '古风';
    const CATEGORY_MODERN = '现代';
    
    /**
     * 关联AI模型配置
     */
    public function aiModel()
    {
        return $this->belongsTo(AiTravelPhotoModel::class, 'model_id', 'id');
    }
    
    /**
     * 关联API配置
     */
    public function apiConfig()
    {
        return $this->belongsTo(AiApiConfig::class, 'api_config_id', 'id');
    }
    
    /**
     * 关联生成记录
     */
    public function generations()
    {
        return $this->hasMany(AiTravelPhotoGeneration::class, 'scene_id', 'id');
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
        return self::getSceneTypeText($data['scene_type'] ?? 1);
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
     * 搜索器：按场景类型搜索
     */
    public function searchSceneTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('scene_type', $value);
        }
    }
    
    /**
     * 搜索器：按商家ID搜索
     */
    public function searchBidAttr($query, $value)
    {
        if ($value !== null && $value !== '') {
            $query->where('bid', $value);
        }
    }
    
    /**
     * 搜索器：按分类搜索
     */
    public function searchCategoryAttr($query, $value)
    {
        if ($value) {
            $query->where('category', $value);
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
     * 搜索器：按是否公共搜索
     */
    public function searchIsPublicAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_public', $value);
        }
    }
    
    /**
     * 搜索器：按是否推荐搜索
     */
    public function searchIsRecommendAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_recommend', $value);
        }
    }
    
    /**
     * 搜索器：按门店ID搜索
     */
    public function searchMdidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('mdid', $value);
        }
    }
    
    /**
     * 搜索器：按场景名称搜索
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->whereLike('name', '%' . $value . '%');
        }
    }
    
    /**
     * 获取场景类型列表
     */
    public static function getSceneTypeList()
    {
        return [
            self::SCENE_TYPE_IMAGE_SINGLE => '图生图-单图编辑',
            self::SCENE_TYPE_IMAGE_MULTI => '图生图-多图融合',
            self::SCENE_TYPE_VIDEO_FIRST => '视频生成-首帧',
            self::SCENE_TYPE_VIDEO_FIRST_LAST => '视频生成-首尾帧',
            self::SCENE_TYPE_VIDEO_EFFECT => '视频生成-特效',
            self::SCENE_TYPE_VIDEO_REFERENCE => '视频生成-参考生成',
        ];
    }
    
    /**
     * 获取场景类型文本
     */
    public static function getSceneTypeText($type)
    {
        $list = self::getSceneTypeList();
        return $list[$type] ?? '未知类型';
    }
    
    /**
     * 获取分类列表
     */
    public static function getCategoryList()
    {
        return [
            self::CATEGORY_SCENERY => '风景',
            self::CATEGORY_PORTRAIT => '人物',
            self::CATEGORY_CREATIVE => '创意',
            self::CATEGORY_FESTIVAL => '节日',
            self::CATEGORY_ANCIENT => '古风',
            self::CATEGORY_MODERN => '现代',
        ];
    }
    
    /**
     * 判断是否为视频类型场景
     */
    public function isVideoScene()
    {
        return in_array($this->scene_type, [
            self::SCENE_TYPE_VIDEO_FIRST,
            self::SCENE_TYPE_VIDEO_FIRST_LAST,
            self::SCENE_TYPE_VIDEO_EFFECT,
            self::SCENE_TYPE_VIDEO_REFERENCE,
        ]);
    }
}

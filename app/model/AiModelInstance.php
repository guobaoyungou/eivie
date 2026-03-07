<?php

namespace app\model;

use think\Model;

/**
 * AI模型实例模型
 * Class AiModelInstance
 * @package app\model
 */
class AiModelInstance extends Model
{
    // 设置表名
    protected $name = 'ai_model_instance';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'aid' => 'integer',
        'is_system' => 'integer',
        'is_active' => 'integer',
        'sort' => 'integer',
        'cost_per_call' => 'float',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // JSON字段
    protected $json = ['capability_tags'];
    protected $jsonAssoc = true;
    
    /**
     * 关联参数定义
     */
    public function parameters()
    {
        return $this->hasMany(AiModelParameter::class, 'model_id', 'id')->order('sort', 'asc');
    }
    
    /**
     * 关联响应定义
     */
    public function responses()
    {
        return $this->hasMany(AiModelResponse::class, 'model_id', 'id');
    }
    
    /**
     * 关联定价配置
     */
    public function pricings()
    {
        return $this->hasMany(AiModelPricing::class, 'model_id', 'id');
    }
    
    /**
     * 关联模型分类
     */
    public function category()
    {
        return $this->hasOne(\app\model\AiModelCategory::class, 'category_code', 'category_code');
    }
    
    /**
     * 获取激活状态文本
     */
    public function getIsActiveTextAttr($value, $data)
    {
        $status = [0 => '停用', 1 => '启用'];
        return $status[$data['is_active']] ?? '未知';
    }
    
    /**
     * 获取系统预置文本
     */
    public function getIsSystemTextAttr($value, $data)
    {
        $status = [0 => '自定义', 1 => '系统预置'];
        return $status[$data['is_system']] ?? '未知';
    }
    
    /**
     * 计费模式文本
     */
    public function getBillingModeTextAttr($value, $data)
    {
        $modes = [
            'fixed' => '固定价格',
            'token' => '按Token计费',
            'duration' => '按时长计费'
        ];
        return $modes[$data['billing_mode']] ?? '未知';
    }
    
    /**
     * 搜索器：模型代码
     */
    public function searchModelCodeAttr($query, $value)
    {
        if ($value) {
            $query->where('model_code', 'like', "%{$value}%");
        }
    }
    
    /**
     * 搜索器：模型名称
     */
    public function searchModelNameAttr($query, $value)
    {
        if ($value) {
            $query->where('model_name', 'like', "%{$value}%");
        }
    }
    
    /**
     * 搜索器：分类代码
     */
    public function searchCategoryCodeAttr($query, $value)
    {
        if ($value) {
            $query->where('category_code', $value);
        }
    }
    
    /**
     * 搜索器：服务提供商
     */
    public function searchProviderAttr($query, $value)
    {
        if ($value) {
            $query->where('provider', $value);
        }
    }
    
    /**
     * 搜索器：激活状态
     */
    public function searchIsActiveAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_active', $value);
        }
    }
    
    /**
     * 搜索器：平台ID
     */
    public function searchAidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('aid', $value);
        }
    }
    
    /**
     * 获取模型配置（含参数、响应、定价）
     */
    public static function getFullConfig($modelCode, $aid = 0)
    {
        $model = self::where('model_code', $modelCode)
            ->where('is_active', 1)
            ->find();
            
        if (!$model) {
            return null;
        }
        
        // 加载关联数据
        $model->parameters;
        $model->responses;
        
        // 加载定价配置（优先级：商家级 > 平台级 > 系统默认）
        $pricing = AiModelPricing::where('model_id', $model->id)
            ->where('aid', $aid)
            ->where('is_active', 1)
            ->find();
            
        if (!$pricing) {
            // 如果没有商家级定价，使用系统默认
            $pricing = AiModelPricing::where('model_id', $model->id)
                ->where('aid', 0)
                ->where('is_active', 1)
                ->find();
        }
        
        $model->pricing = $pricing;
        
        return $model;
    }
    
    /**
     * 获取可用的服务提供商列表
     */
    public static function getProviderList()
    {
        return [
            'aliyun' => '阿里云',
            'baidu' => '百度智能云',
            'tencent' => '腾讯云',
            'openai' => 'OpenAI',
            'custom' => '自定义'
        ];
    }
    
    /**
     * 获取计费模式列表
     */
    public static function getBillingModeList()
    {
        return [
            'fixed' => '固定价格',
            'token' => '按Token计费',
            'duration' => '按时长计费'
        ];
    }
    
    /**
     * 获取成本计量单位列表
     */
    public static function getCostUnitList()
    {
        return [
            'per_call' => '每次调用',
            'per_image' => '每张图片',
            'per_video' => '每个视频',
            'per_token' => '每个Token'
        ];
    }
}

<?php

namespace app\model;

use think\Model;

/**
 * AI模型定价配置模型
 * Class AiModelPricing
 * @package app\model
 */
class AiModelPricing extends Model
{
    // 设置表名
    protected $name = 'ai_model_pricing';
    
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
        'model_id' => 'integer',
        'aid' => 'integer',
        'bid' => 'integer',
        'cost_price' => 'float',
        'platform_price' => 'float',
        'merchant_price' => 'float',
        'platform_profit_rate' => 'float',
        'merchant_profit_rate' => 'float',
        'min_price' => 'float',
        'max_price' => 'float',
        'is_active' => 'integer',
        'effective_time' => 'integer',
        'expire_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    /**
     * 关联模型实例
     */
    public function modelInstance()
    {
        return $this->belongsTo(AiModelInstance::class, 'model_id', 'id');
    }
    
    /**
     * 获取激活状态文本
     */
    public function getIsActiveTextAttr($value, $data)
    {
        return $data['is_active'] ? '生效' : '失效';
    }
    
    /**
     * 获取定价类型文本
     */
    public function getPriceTypeTextAttr($value, $data)
    {
        $types = [
            'image' => '图片',
            'video' => '视频',
            'token' => 'Token',
            'call' => '调用'
        ];
        return $types[$data['price_type']] ?? '未知';
    }
    
    /**
     * 自动计算平台利润率
     */
    public function setPlatformPriceAttr($value, $data)
    {
        // 如果设置了平台售价，自动计算利润率
        if (isset($data['cost_price']) && $data['cost_price'] > 0) {
            $this->data['platform_profit_rate'] = (($value - $data['cost_price']) / $data['cost_price']) * 100;
        }
        return $value;
    }
    
    /**
     * 自动计算商家利润率
     */
    public function setMerchantPriceAttr($value, $data)
    {
        // 如果设置了商家售价，自动计算利润率
        if (isset($data['platform_price']) && $data['platform_price'] > 0) {
            $this->data['merchant_profit_rate'] = (($value - $data['platform_price']) / $data['platform_price']) * 100;
        }
        return $value;
    }
    
    /**
     * 搜索器：模型ID
     */
    public function searchModelIdAttr($query, $value)
    {
        if ($value) {
            $query->where('model_id', $value);
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
     * 搜索器：商家ID
     */
    public function searchBidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('bid', $value);
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
     * 搜索器：生效时间范围
     */
    public function searchEffectiveTimeAttr($query, $value)
    {
        if ($value) {
            $now = time();
            $query->where('effective_time', '<=', $now)
                  ->where(function($q) use ($now) {
                      $q->where('expire_time', '>=', $now)->whereOr('expire_time', 0);
                  });
        }
    }
    
    /**
     * 获取有效的定价配置
     * @param int $modelId 模型ID
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @return AiModelPricing|null
     */
    public static function getEffectivePricing($modelId, $aid = 0, $bid = 0)
    {
        $now = time();
        
        // 优先级：商家级 > 平台级 > 系统默认
        if ($bid > 0) {
            $pricing = self::where('model_id', $modelId)
                ->where('aid', $aid)
                ->where('bid', $bid)
                ->where('is_active', 1)
                ->where('effective_time', '<=', $now)
                ->where(function($query) use ($now) {
                    $query->where('expire_time', '>=', $now)->whereOr('expire_time', 0);
                })
                ->find();
            if ($pricing) return $pricing;
        }
        
        // 平台级定价
        if ($aid > 0) {
            $pricing = self::where('model_id', $modelId)
                ->where('aid', $aid)
                ->where('bid', 0)
                ->where('is_active', 1)
                ->where('effective_time', '<=', $now)
                ->where(function($query) use ($now) {
                    $query->where('expire_time', '>=', $now)->whereOr('expire_time', 0);
                })
                ->find();
            if ($pricing) return $pricing;
        }
        
        // 系统默认定价
        return self::where('model_id', $modelId)
            ->where('aid', 0)
            ->where('bid', 0)
            ->where('is_active', 1)
            ->where('effective_time', '<=', $now)
            ->where(function($query) use ($now) {
                $query->where('expire_time', '>=', $now)->whereOr('expire_time', 0);
            })
            ->find();
    }
    
    /**
     * 校验价格合法性
     */
    public function validatePrice()
    {
        $errors = [];
        
        // 成本价必须大于0
        if ($this->cost_price <= 0) {
            $errors[] = '成本价必须大于0';
        }
        
        // 平台售价必须大于等于成本价
        if ($this->platform_price < $this->cost_price) {
            $errors[] = '平台售价不能低于成本价';
        }
        
        // 商家售价校验
        if ($this->min_price > 0 && $this->merchant_price < $this->min_price) {
            $errors[] = "商家售价不能低于最低售价{$this->min_price}元";
        }
        
        if ($this->max_price > 0 && $this->merchant_price > $this->max_price) {
            $errors[] = "商家售价不能超过最高售价{$this->max_price}元";
        }
        
        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
    
    /**
     * 获取定价类型列表
     */
    public static function getPriceTypeList()
    {
        return [
            'image' => '图片',
            'video' => '视频',
            'token' => 'Token',
            'call' => '调用'
        ];
    }
    
    /**
     * 获取货币列表
     */
    public static function getCurrencyList()
    {
        return [
            'CNY' => '人民币',
            'USD' => '美元',
            'EUR' => '欧元'
        ];
    }
}

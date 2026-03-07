<?php

namespace app\model;

use think\Model;

/**
 * API计费规则模型
 * Class ApiPricing
 * @package app\model
 */
class ApiPricing extends Model
{
    // 设置表名
    protected $name = 'api_pricing';
    
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
        'api_config_id' => 'integer',
        'cost_per_unit' => 'float',
        'price_per_unit' => 'float',
        'min_charge' => 'float',
        'free_quota' => 'integer',
        'is_active' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // JSON字段
    protected $json = ['tier_pricing_json'];
    protected $jsonAssoc = true;
    
    // 计费模式常量
    const BILLING_FIXED = 'fixed';       // 固定计费
    const BILLING_TOKEN = 'token';       // Token计费
    const BILLING_DURATION = 'duration'; // 时长计费
    const BILLING_IMAGE = 'image';       // 图片计费
    
    // 计费单位常量
    const UNIT_PER_CALL = 'per_call';     // 每次调用
    const UNIT_PER_TOKEN = 'per_token';   // 每Token
    const UNIT_PER_MINUTE = 'per_minute'; // 每分钟
    const UNIT_PER_IMAGE = 'per_image';   // 每张图片
    
    /**
     * 关联API配置
     */
    public function apiConfig()
    {
        return $this->hasOne(ApiConfig::class, 'id', 'api_config_id');
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
     * 获取计费模式文本
     */
    public function getBillingModeTextAttr($value, $data)
    {
        $modes = [
            self::BILLING_FIXED => '固定计费',
            self::BILLING_TOKEN => 'Token计费',
            self::BILLING_DURATION => '时长计费',
            self::BILLING_IMAGE => '图片计费'
        ];
        return $modes[$data['billing_mode']] ?? '未知';
    }
    
    /**
     * 获取计费单位文本
     */
    public function getUnitTypeTextAttr($value, $data)
    {
        $units = [
            self::UNIT_PER_CALL => '每次调用',
            self::UNIT_PER_TOKEN => '每Token',
            self::UNIT_PER_MINUTE => '每分钟',
            self::UNIT_PER_IMAGE => '每张图片'
        ];
        return $units[$data['unit_type']] ?? '未知';
    }
    
    /**
     * 获取利润率
     */
    public function getProfitRateAttr($value, $data)
    {
        if ($data['cost_per_unit'] <= 0) {
            return 0;
        }
        
        $profit = $data['price_per_unit'] - $data['cost_per_unit'];
        return round(($profit / $data['cost_per_unit']) * 100, 2);
    }
    
    /**
     * 搜索器：API配置ID
     */
    public function searchApiConfigIdAttr($query, $value)
    {
        if ($value) {
            $query->where('api_config_id', $value);
        }
    }
    
    /**
     * 搜索器：计费模式
     */
    public function searchBillingModeAttr($query, $value)
    {
        if ($value) {
            $query->where('billing_mode', $value);
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
     * 计算费用
     * @param float $units 消耗单位数
     * @param array $context 上下文信息（如调用时间、用户信息等）
     * @return array ['amount' => 费用金额, 'details' => 计费详情]
     */
    public function calculateCharge($units, $context = [])
    {
        $amount = 0;
        $details = [];
        
        // 检查免费额度
        if ($this->free_quota > 0) {
            $usedQuota = $this->getUsedQuota($context);
            $remainingQuota = max(0, $this->free_quota - $usedQuota);
            
            if ($remainingQuota > 0) {
                $freeUnits = min($units, $remainingQuota);
                $units -= $freeUnits;
                
                $details['free_units'] = $freeUnits;
                $details['remaining_quota'] = $remainingQuota - $freeUnits;
            }
        }
        
        // 如果还有需要计费的单位
        if ($units > 0) {
            // 检查是否有阶梯定价
            if ($this->tier_pricing_json && is_array($this->tier_pricing_json)) {
                $amount = $this->calculateTierPrice($units);
                $details['pricing_type'] = 'tier';
            } else {
                $amount = $units * $this->price_per_unit;
                $details['pricing_type'] = 'standard';
            }
            
            // 应用最低收费
            if ($this->min_charge > 0 && $amount < $this->min_charge) {
                $details['min_charge_applied'] = true;
                $amount = $this->min_charge;
            }
        }
        
        $details['units'] = $units;
        $details['unit_price'] = $this->price_per_unit;
        $details['final_amount'] = $amount;
        
        return [
            'amount' => round($amount, 4),
            'details' => $details
        ];
    }
    
    /**
     * 计算阶梯定价
     */
    private function calculateTierPrice($units)
    {
        $amount = 0;
        $tiers = $this->tier_pricing_json;
        
        // 按阶梯范围排序
        usort($tiers, function($a, $b) {
            return $a['min_units'] - $b['min_units'];
        });
        
        foreach ($tiers as $tier) {
            $minUnits = $tier['min_units'] ?? 0;
            $maxUnits = $tier['max_units'] ?? PHP_INT_MAX;
            $price = $tier['price'] ?? $this->price_per_unit;
            
            if ($units > $minUnits) {
                $tierUnits = min($units - $minUnits, $maxUnits - $minUnits);
                $amount += $tierUnits * $price;
            }
        }
        
        return $amount;
    }
    
    /**
     * 获取已使用的免费额度
     */
    private function getUsedQuota($context)
    {
        if (!isset($context['caller_uid']) || !isset($context['api_config_id'])) {
            return 0;
        }
        
        $startTime = strtotime('today');
        $endTime = strtotime('tomorrow');
        
        $used = ApiCallLog::where('api_config_id', $context['api_config_id'])
            ->where('caller_uid', $context['caller_uid'])
            ->where('call_time', '>=', $startTime)
            ->where('call_time', '<', $endTime)
            ->where('is_success', 1)
            ->sum('consumed_units');
            
        return floatval($used);
    }
    
    /**
     * 获取剩余免费额度
     */
    public function getRemainingQuota($apiConfigId, $callerUid)
    {
        if ($this->free_quota <= 0) {
            return 0;
        }
        
        $context = [
            'api_config_id' => $apiConfigId,
            'caller_uid' => $callerUid
        ];
        
        $usedQuota = $this->getUsedQuota($context);
        return max(0, $this->free_quota - $usedQuota);
    }
    
    /**
     * 从AI模型实例继承定价配置
     */
    public static function inheritFromModel($apiConfigId, $modelId)
    {
        $model = \app\model\AiModelInstance::find($modelId);
        if (!$model) {
            return null;
        }
        
        // 查找模型的定价配置
        $modelPricing = \app\model\AiModelPricing::where('model_id', $modelId)
            ->where('is_active', 1)
            ->order('aid', 'desc')
            ->find();
        
        if (!$modelPricing) {
            // 使用模型的默认计费配置
            $pricing = new self();
            $pricing->api_config_id = $apiConfigId;
            $pricing->billing_mode = $model->billing_mode ?? self::BILLING_FIXED;
            $pricing->cost_per_unit = $model->cost_per_call ?? 0;
            $pricing->price_per_unit = $model->cost_per_call ?? 0;
            $pricing->unit_type = self::UNIT_PER_CALL;
            $pricing->is_active = 1;
        } else {
            // 继承模型定价配置
            $pricing = new self();
            $pricing->api_config_id = $apiConfigId;
            $pricing->billing_mode = $modelPricing->billing_mode;
            $pricing->cost_per_unit = $modelPricing->cost_per_unit;
            $pricing->price_per_unit = $modelPricing->price_per_unit;
            $pricing->unit_type = $modelPricing->unit_type;
            $pricing->min_charge = $modelPricing->min_charge ?? 0;
            $pricing->free_quota = $modelPricing->free_quota ?? 0;
            $pricing->tier_pricing_json = $modelPricing->tier_pricing_json ?? null;
            $pricing->is_active = 1;
        }
        
        return $pricing;
    }
}

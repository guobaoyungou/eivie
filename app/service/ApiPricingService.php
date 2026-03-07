<?php

namespace app\service;

use app\model\ApiPricing;
use app\model\ApiCallLog;
use think\facade\Log;

/**
 * API计费核算服务
 * Class ApiPricingService
 * @package app\service
 */
class ApiPricingService
{
    /**
     * 计算API调用费用
     * 
     * @param int $apiConfigId API配置ID
     * @param float $units 消耗单位数
     * @param array $context 上下文信息
     * @return array ['amount' => 费用, 'details' => 详情]
     */
    public function calculateCharge($apiConfigId, $units, $context = [])
    {
        // 获取计费规则
        $pricing = ApiPricing::where('api_config_id', $apiConfigId)
            ->where('is_active', 1)
            ->find();
        
        if (!$pricing) {
            return [
                'amount' => 0,
                'details' => [
                    'error' => '未配置计费规则',
                    'billing_mode' => 'free'
                ]
            ];
        }
        
        // 添加API配置ID到上下文
        $context['api_config_id'] = $apiConfigId;
        
        // 调用模型的计费方法
        return $pricing->calculateCharge($units, $context);
    }
    
    /**
     * 根据响应数据计算消耗单位
     * 
     * @param string $billingMode 计费模式
     * @param array $responseData 响应数据
     * @return float 消耗单位数
     */
    public function calculateUnits($billingMode, $responseData)
    {
        switch ($billingMode) {
            case ApiPricing::BILLING_FIXED:
                // 固定计费，每次调用算1个单位
                return 1.0;
                
            case ApiPricing::BILLING_TOKEN:
                // Token计费，从响应中提取token数
                return $this->extractTokenCount($responseData);
                
            case ApiPricing::BILLING_DURATION:
                // 时长计费，从响应中提取时长(分钟)
                return $this->extractDuration($responseData);
                
            case ApiPricing::BILLING_IMAGE:
                // 图片计费，从响应中提取图片数量
                return $this->extractImageCount($responseData);
                
            default:
                return 1.0;
        }
    }
    
    /**
     * 从响应数据中提取Token数量
     */
    private function extractTokenCount($responseData)
    {
        // 尝试多种常见的token字段名
        $tokenFields = ['token_count', 'tokens', 'usage.total_tokens', 'total_tokens'];
        
        foreach ($tokenFields as $field) {
            if (strpos($field, '.') !== false) {
                // 支持嵌套字段
                $value = $this->getNestedValue($responseData, $field);
            } else {
                $value = $responseData[$field] ?? null;
            }
            
            if ($value !== null && is_numeric($value)) {
                return floatval($value);
            }
        }
        
        // 默认返回1
        return 1.0;
    }
    
    /**
     * 从响应数据中提取时长(分钟)
     */
    private function extractDuration($responseData)
    {
        $durationFields = ['duration', 'duration_seconds', 'time_cost'];
        
        foreach ($durationFields as $field) {
            $value = $responseData[$field] ?? null;
            
            if ($value !== null && is_numeric($value)) {
                // 如果字段名包含seconds，转换为分钟
                if (strpos($field, 'seconds') !== false) {
                    return round(floatval($value) / 60, 2);
                }
                return floatval($value);
            }
        }
        
        return 1.0;
    }
    
    /**
     * 从响应数据中提取图片数量
     */
    private function extractImageCount($responseData)
    {
        $imageFields = ['image_count', 'images', 'output.images'];
        
        foreach ($imageFields as $field) {
            if (strpos($field, '.') !== false) {
                $value = $this->getNestedValue($responseData, $field);
            } else {
                $value = $responseData[$field] ?? null;
            }
            
            if ($value !== null) {
                // 如果是数组，返回数组长度
                if (is_array($value)) {
                    return floatval(count($value));
                }
                // 如果是数字，直接返回
                if (is_numeric($value)) {
                    return floatval($value);
                }
            }
        }
        
        return 1.0;
    }
    
    /**
     * 获取嵌套数组的值
     */
    private function getNestedValue($data, $path)
    {
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * 获取用户的免费额度剩余
     */
    public function getRemainingQuota($apiConfigId, $callerUid)
    {
        $pricing = ApiPricing::where('api_config_id', $apiConfigId)
            ->where('is_active', 1)
            ->find();
        
        if (!$pricing || $pricing->free_quota <= 0) {
            return 0;
        }
        
        return $pricing->getRemainingQuota($apiConfigId, $callerUid);
    }
    
    /**
     * 获取计费详情预览
     */
    public function getChargePreview($apiConfigId, $units = 1)
    {
        $pricing = ApiPricing::where('api_config_id', $apiConfigId)
            ->where('is_active', 1)
            ->find();
        
        if (!$pricing) {
            return [
                'success' => false,
                'message' => '未配置计费规则'
            ];
        }
        
        $charge = $pricing->calculateCharge($units, []);
        
        return [
            'success' => true,
            'billing_mode' => $pricing->billing_mode,
            'billing_mode_text' => $pricing->billing_mode_text,
            'unit_type' => $pricing->unit_type,
            'unit_type_text' => $pricing->unit_type_text,
            'price_per_unit' => $pricing->price_per_unit,
            'cost_per_unit' => $pricing->cost_per_unit,
            'min_charge' => $pricing->min_charge,
            'free_quota' => $pricing->free_quota,
            'estimated_units' => $units,
            'estimated_amount' => $charge['amount'],
            'charge_details' => $charge['details']
        ];
    }
    
    /**
     * 获取API收入统计
     */
    public function getRevenueStats($apiConfigId, $startTime, $endTime)
    {
        $logs = ApiCallLog::where('api_config_id', $apiConfigId)
            ->where('call_time', '>=', $startTime)
            ->where('call_time', '<=', $endTime)
            ->where('is_success', 1)
            ->select();
        
        $totalRevenue = 0;
        $totalCalls = 0;
        $totalUnits = 0;
        
        foreach ($logs as $log) {
            $totalRevenue += $log->charge_amount;
            $totalCalls++;
            $totalUnits += $log->consumed_units;
        }
        
        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_calls' => $totalCalls,
            'total_units' => round($totalUnits, 2),
            'avg_revenue_per_call' => $totalCalls > 0 ? round($totalRevenue / $totalCalls, 2) : 0,
            'start_time' => date('Y-m-d H:i:s', $startTime),
            'end_time' => date('Y-m-d H:i:s', $endTime)
        ];
    }
    
    /**
     * 更新计费规则
     */
    public function updatePricing($apiConfigId, $data)
    {
        try {
            $pricing = ApiPricing::where('api_config_id', $apiConfigId)->find();
            
            if (!$pricing) {
                $pricing = new ApiPricing();
                $pricing->api_config_id = $apiConfigId;
            }
            
            if (isset($data['billing_mode'])) $pricing->billing_mode = $data['billing_mode'];
            if (isset($data['cost_per_unit'])) $pricing->cost_per_unit = $data['cost_per_unit'];
            if (isset($data['price_per_unit'])) $pricing->price_per_unit = $data['price_per_unit'];
            if (isset($data['unit_type'])) $pricing->unit_type = $data['unit_type'];
            if (isset($data['min_charge'])) $pricing->min_charge = $data['min_charge'];
            if (isset($data['free_quota'])) $pricing->free_quota = $data['free_quota'];
            if (isset($data['tier_pricing_json'])) $pricing->tier_pricing_json = $data['tier_pricing_json'];
            if (isset($data['is_active'])) $pricing->is_active = $data['is_active'];
            
            $pricing->save();
            
            return [
                'success' => true,
                'message' => '更新成功',
                'data' => $pricing
            ];
        } catch (\Exception $e) {
            Log::error('更新计费规则失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

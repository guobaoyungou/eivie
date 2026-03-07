<?php
namespace app\service;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI模型调度服务
 * 提供统一的AI模型API调用、负载均衡、并发控制、失败重试等功能
 */
class AiModelService
{
    /**
     * 调用AI模型API
     * 
     * @param string $categoryCode 模型分类代码
     * @param string $businessType 业务类型 cutout/image_gen/video_gen
     * @param array $params 请求参数
     * @param int $mdid 门店ID（可选）
     * @param int $bid 商家ID（可选）
     * @param int $aid 平台ID（可选）
     * @return array ['success' => bool, 'data' => mixed, 'error' => string]
     */
    public static function call($categoryCode, $businessType, $params, $mdid = 0, $bid = 0, $aid = 0)
    {
        // 1. 获取可用的API配置（负载均衡）
        $apiConfig = self::getAvailableApiConfig($categoryCode, $mdid, $bid, $aid);
        
        if (!$apiConfig) {
            Log::error('AI模型调用失败：无可用API配置', [
                'category_code' => $categoryCode,
                'business_type' => $businessType,
                'mdid' => $mdid,
                'bid' => $bid
            ]);
            return ['success' => false, 'data' => null, 'error' => '无可用的API配置'];
        }
        
        $modelId = $apiConfig['id'];
        $maxRetry = $apiConfig['max_retry'] ?? 3;
        $retryCount = 0;
        $result = null;
        $error = '';
        $startTime = microtime(true);
        
        // 2. 增加并发计数
        self::incrementConcurrent($modelId);
        
        try {
            // 3. 调用第三方API（带重试机制）
            while ($retryCount <= $maxRetry) {
                $result = self::callThirdPartyApi($apiConfig, $businessType, $params);
                
                if ($result['success']) {
                    break; // 成功则跳出
                }
                
                // 判断是否需要重试
                if (!self::shouldRetry($result['error_code'], $retryCount, $maxRetry)) {
                    $error = $result['error'];
                    break;
                }
                
                $retryCount++;
                
                // 重试延迟
                if ($retryCount > 0) {
                    $delay = $retryCount == 1 ? 0 : ($retryCount == 2 ? 2 : 5);
                    if ($delay > 0) {
                        sleep($delay);
                    }
                }
            }
            
            $endTime = microtime(true);
            $responseTime = intval(($endTime - $startTime) * 1000); // 毫秒
            
            // 4. 减少并发计数
            self::decrementConcurrent($modelId);
            
            // 5. 记录使用日志
            self::saveUsageLog([
                'aid' => $aid,
                'bid' => $bid,
                'mdid' => $mdid,
                'model_id' => $modelId,
                'category_code' => $categoryCode,
                'business_type' => $businessType,
                'request_params' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'response_data' => json_encode($result['data'] ?? [], JSON_UNESCAPED_UNICODE),
                'status' => $result['success'] ? 1 : 0,
                'error_msg' => $error,
                'cost_amount' => self::calculateCost($apiConfig, $businessType, $result),
                'response_time' => $responseTime,
                'retry_count' => $retryCount,
                'create_time' => time()
            ]);
            
            // 6. 更新统计数据
            self::updateModelStats($modelId, $result['success']);
            
            return $result;
            
        } catch (\Exception $e) {
            // 异常处理
            self::decrementConcurrent($modelId);
            
            Log::error('AI模型调用异常', [
                'category_code' => $categoryCode,
                'model_id' => $modelId,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 获取可用的API配置（负载均衡）
     */
    private static function getAvailableApiConfig($categoryCode, $mdid, $bid, $aid)
    {
        $cacheKey = "ai_model:available_config:{$categoryCode}:{$mdid}:{$bid}:{$aid}";
        
        // 尝试从缓存获取（缓存5分钟）
        $configs = Cache::get($cacheKey);
        
        if (!$configs) {
            // 查询数据库
            $query = Db::name('ai_travel_photo_model')
                ->where('category_code', $categoryCode)
                ->where('status', 1)
                ->where('is_active', 1);
            
            // 门店优先级：先查门店专属配置，再查商家通用配置，最后查平台配置
            if ($mdid > 0) {
                $query->where(function ($query) use ($mdid, $bid, $aid) {
                    $query->where('mdid', $mdid)
                          ->whereOr(function ($query) use ($bid, $aid) {
                              $query->where('mdid', 0)
                                    ->where('bid', $bid);
                          })
                          ->whereOr(function ($query) use ($aid) {
                              $query->where('mdid', 0)
                                    ->where('bid', 0)
                                    ->where('aid', $aid);
                          });
                });
            } elseif ($bid > 0) {
                $query->where(function ($query) use ($bid, $aid) {
                    $query->where('bid', $bid)
                          ->where('mdid', 0)
                          ->whereOr(function ($query) use ($aid) {
                              $query->where('bid', 0)
                                    ->where('mdid', 0)
                                    ->where('aid', $aid);
                          });
                });
            } else {
                $query->where('aid', $aid)
                      ->where('bid', 0)
                      ->where('mdid', 0);
            }
            
            $configs = $query->select()->toArray();
            
            if ($configs) {
                Cache::set($cacheKey, $configs, 300); // 缓存5分钟
            }
        }
        
        if (empty($configs)) {
            return null;
        }
        
        // 过滤掉并发已满的配置
        $availableConfigs = array_filter($configs, function($config) {
            $currentConcurrent = self::getCurrentConcurrent($config['id']);
            return $currentConcurrent < $config['max_concurrent'];
        });
        
        if (empty($availableConfigs)) {
            return null;
        }
        
        // 排序：优先级降序 > 当前并发数升序 > 成功率降序
        usort($availableConfigs, function($a, $b) {
            // 1. 优先级降序
            if ($a['priority'] != $b['priority']) {
                return $b['priority'] - $a['priority'];
            }
            
            // 2. 当前并发数升序
            $aConcurrent = self::getCurrentConcurrent($a['id']);
            $bConcurrent = self::getCurrentConcurrent($b['id']);
            if ($aConcurrent != $bConcurrent) {
                return $aConcurrent - $bConcurrent;
            }
            
            // 3. 成功率降序
            $aSuccessRate = $a['use_count'] > 0 ? $a['success_count'] / $a['use_count'] : 0;
            $bSuccessRate = $b['use_count'] > 0 ? $b['success_count'] / $b['use_count'] : 0;
            return $bSuccessRate <=> $aSuccessRate;
        });
        
        // 返回第一个配置
        return $availableConfigs[0];
    }
    
    /**
     * 调用第三方API
     */
    private static function callThirdPartyApi($apiConfig, $businessType, $params)
    {
        // 根据不同的模型分类调用不同的API
        // 这里是示例实现，实际需要根据具体的API文档进行调用
        
        $categoryCode = $apiConfig['category_code'];
        $timeout = $apiConfig['timeout'] ?? 180;
        
        try {
            // 构建请求
            $requestUrl = $apiConfig['api_base_url'];
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiConfig['api_key']
            ];
            
            // 根据业务类型构建请求参数
            $requestData = self::buildRequestData($categoryCode, $businessType, $params);
            
            // 发送HTTP请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => '网络请求失败: ' . $error,
                    'error_code' => 'NETWORK_ERROR'
                ];
            }
            
            if ($httpCode != 200) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'HTTP错误: ' . $httpCode,
                    'error_code' => 'HTTP_' . $httpCode
                ];
            }
            
            $result = json_decode($response, true);
            
            // 根据不同的API响应格式解析结果
            return self::parseApiResponse($categoryCode, $result);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }
    
    /**
     * 构建请求数据
     */
    private static function buildRequestData($categoryCode, $businessType, $params)
    {
        // 根据不同的模型和业务类型构建请求数据
        // 这里是示例，实际需要根据具体API文档调整
        
        $requestData = $params;
        $requestData['business_type'] = $businessType;
        
        return $requestData;
    }
    
    /**
     * 解析API响应
     */
    private static function parseApiResponse($categoryCode, $response)
    {
        // 根据不同的API响应格式解析结果
        // 这里是通用解析逻辑，实际需要根据具体API调整
        
        if (isset($response['code']) && $response['code'] == 0) {
            return [
                'success' => true,
                'data' => $response['data'] ?? $response,
                'error' => '',
                'error_code' => ''
            ];
        }
        
        return [
            'success' => false,
            'data' => null,
            'error' => $response['message'] ?? $response['error'] ?? '未知错误',
            'error_code' => $response['code'] ?? 'UNKNOWN'
        ];
    }
    
    /**
     * 判断是否需要重试
     */
    private static function shouldRetry($errorCode, $retryCount, $maxRetry)
    {
        if ($retryCount >= $maxRetry) {
            return false;
        }
        
        // 网络错误和超时错误可以重试
        $retryableErrors = ['NETWORK_ERROR', 'TIMEOUT', 'HTTP_500', 'HTTP_502', 'HTTP_503', 'HTTP_504'];
        
        if (in_array($errorCode, $retryableErrors)) {
            return true;
        }
        
        // 限流错误（429）不重试，应该切换到其他API
        if ($errorCode == 'HTTP_429') {
            return false;
        }
        
        // 参数错误（400）不重试
        if ($errorCode == 'HTTP_400') {
            return false;
        }
        
        return false;
    }
    
    /**
     * 增加并发计数
     */
    private static function incrementConcurrent($modelId)
    {
        $cacheKey = "ai_model:concurrent:{$modelId}";
        Cache::inc($cacheKey);
        
        // 设置过期时间（10分钟）
        Cache::expire($cacheKey, 600);
    }
    
    /**
     * 减少并发计数
     */
    private static function decrementConcurrent($modelId)
    {
        $cacheKey = "ai_model:concurrent:{$modelId}";
        $current = Cache::get($cacheKey, 0);
        
        if ($current > 0) {
            Cache::dec($cacheKey);
        }
    }
    
    /**
     * 获取当前并发数（静态方法，供外部调用）
     */
    public static function getCurrentConcurrent($modelId)
    {
        $cacheKey = "ai_model:concurrent:{$modelId}";
        return \think\facade\Cache::get($cacheKey, 0);
    }
    
    /**
     * 保存使用日志
     */
    private static function saveUsageLog($data)
    {
        try {
            Db::name('ai_model_usage_log')->insert($data);
        } catch (\Exception $e) {
            Log::error('保存AI模型使用日志失败', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新模型统计数据
     */
    private static function updateModelStats($modelId, $success)
    {
        try {
            $update = ['use_count' => Db::raw('use_count + 1')];
            
            if ($success) {
                $update['success_count'] = Db::raw('success_count + 1');
            } else {
                $update['fail_count'] = Db::raw('fail_count + 1');
            }
            
            Db::name('ai_travel_photo_model')
                ->where('id', $modelId)
                ->update($update);
                
        } catch (\Exception $e) {
            Log::error('更新模型统计数据失败', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * 计算成本
     */
    private static function calculateCost($apiConfig, $businessType, $result)
    {
        if (!$result['success']) {
            return 0;
        }
        
        // 根据业务类型计算成本
        switch ($businessType) {
            case 'cutout':
            case 'image_gen':
                return $apiConfig['image_price'] ?? 0.05;
            case 'video_gen':
                return $apiConfig['video_price'] ?? 0.50;
            default:
                return 0;
        }
    }
    
    /**
     * 测试API连通性
     */
    public static function testConnection($apiConfig, $businessType = 'cutout')
    {
        $testParams = self::getTestParams($businessType);
        
        $startTime = microtime(true);
        $result = self::callThirdPartyApi($apiConfig, $businessType, $testParams);
        $endTime = microtime(true);
        
        $responseTime = intval(($endTime - $startTime) * 1000);
        
        // 更新测试状态
        try {
            Db::name('ai_travel_photo_model')
                ->where('id', $apiConfig['id'])
                ->update([
                    'test_passed' => $result['success'] ? 1 : 0,
                    'last_test_time' => time(),
                    'last_error' => $result['success'] ? '' : $result['error']
                ]);
        } catch (\Exception $e) {
            // 忽略更新错误
        }
        
        return [
            'success' => $result['success'],
            'response_time' => $responseTime,
            'error' => $result['error'] ?? '',
            'data' => $result['data'] ?? null
        ];
    }
    
    /**
     * 获取测试参数
     */
    private static function getTestParams($businessType)
    {
        switch ($businessType) {
            case 'cutout':
                return [
                    'image_url' => 'https://example.com/test.jpg',
                    'mode' => 'person'
                ];
            case 'image_gen':
                return [
                    'prompt' => 'test image generation',
                    'size' => '512x512'
                ];
            case 'video_gen':
                return [
                    'image_url' => 'https://example.com/test.jpg',
                    'duration' => 5
                ];
            default:
                return [];
        }
    }
}

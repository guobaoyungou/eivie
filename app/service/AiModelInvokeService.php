<?php

namespace app\service;

use app\model\AiModelInstance;
use think\facade\Log;

/**
 * AI模型调用服务
 * 整合配置、校验、解析的统一调用入口
 * Class AiModelInvokeService
 * @package app\service
 */
class AiModelInvokeService
{
    /**
     * 调用AI模型
     * @param string $modelCode 模型代码
     * @param array $params 业务参数
     * @param array $apiConfig API配置（包含API Key等）
     * @return array
     */
    public static function invoke($modelCode, $params, $apiConfig = [])
    {
        try {
            // 1. 准备请求数据
            $requestData = self::prepareRequest($modelCode, $params);
            if (!$requestData['success']) {
                return $requestData;
            }
            
            // 2. 发送HTTP请求
            $response = self::sendRequest($apiConfig, $requestData['data']);
            if (!$response['success']) {
                return $response;
            }
            
            // 3. 处理响应
            $result = self::handleResponse($modelCode, $response['data']);
            
            // 4. 记录调用日志
            self::logInvoke([
                'model_code' => $modelCode,
                'params' => $params,
                'api_config' => $apiConfig,
                'response' => $response['data'],
                'result' => $result,
                'cost_time' => $response['cost_time'] ?? 0
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('AI模型调用异常：' . $e->getMessage());
            return [
                'success' => false,
                'message' => '模型调用失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 准备请求数据
     * @param string $modelCode 模型代码
     * @param array $params 业务参数
     * @return array
     */
    public static function prepareRequest($modelCode, $params)
    {
        // 获取模型配置
        $model = AiModelInstance::where('model_code', $modelCode)
            ->where('is_active', 1)
            ->find();
            
        if (!$model) {
            return [
                'success' => false,
                'message' => "模型{$modelCode}不存在或未激活"
            ];
        }
        
        // 填充默认值
        $params = ModelParameterValidator::fillDefaults($modelCode, $params);
        
        // 校验参数
        $validateResult = ModelParameterValidator::validate($modelCode, $params);
        if (!$validateResult['valid']) {
            return [
                'success' => false,
                'message' => $validateResult['message'],
                'errors' => $validateResult['errors'] ?? []
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'model' => $model,
                'params' => $validateResult['params']
            ]
        ];
    }
    
    /**
     * 发送HTTP请求
     * @param array $apiConfig API配置
     * @param array $requestData 请求数据
     * @return array
     */
    public static function sendRequest($apiConfig, $requestData)
    {
        // 这里需要根据实际的API接口进行调用
        // 示例实现，实际需要根据不同服务商的API规范调整
        
        $model = $requestData['model'];
        $params = $requestData['params'];
        
        // 检查必要的API配置
        if (empty($apiConfig['api_key'])) {
            return [
                'success' => false,
                'message' => '缺少API Key配置'
            ];
        }
        
        if (empty($apiConfig['api_url'])) {
            return [
                'success' => false,
                'message' => '缺少API URL配置'
            ];
        }
        
        // 构建请求头
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiConfig['api_key']
        ];
        
        // 构建请求体
        $body = json_encode([
            'model' => $model->model_code,
            'input' => $params
        ]);
        
        // 发起HTTP请求
        $startTime = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiConfig['api_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $apiConfig['timeout'] ?? 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $costTime = round((microtime(true) - $startTime) * 1000);
        
        // 检查HTTP错误
        if ($error) {
            return [
                'success' => false,
                'message' => 'HTTP请求失败：' . $error
            ];
        }
        
        if ($httpCode != 200) {
            return [
                'success' => false,
                'message' => "HTTP状态码错误：{$httpCode}",
                'response' => $response
            ];
        }
        
        // 解析响应
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'JSON解析失败',
                'response' => $response
            ];
        }
        
        return [
            'success' => true,
            'data' => $responseData,
            'cost_time' => $costTime
        ];
    }
    
    /**
     * 处理响应
     * @param string $modelCode 模型代码
     * @param array $response 响应数据
     * @return array
     */
    public static function handleResponse($modelCode, $response)
    {
        // 使用响应解析服务解析
        $parseResult = ModelResponseParser::parse($modelCode, $response);
        
        if (!$parseResult['success']) {
            return $parseResult;
        }
        
        // 标准化结果
        $result = ModelResponseParser::standardizeResult($parseResult);
        
        return $result;
    }
    
    /**
     * 记录调用日志
     * @param array $invokeData 调用数据
     * @return bool
     */
    public static function logInvoke($invokeData)
    {
        try {
            // 记录到日志文件
            Log::info('AI模型调用记录', [
                'model_code' => $invokeData['model_code'],
                'params_count' => count($invokeData['params']),
                'success' => $invokeData['result']['success'] ?? false,
                'cost_time' => $invokeData['cost_time'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // 这里可以扩展：写入数据库统计表
            // 记录调用次数、成功率、平均耗时等统计数据
            
            return true;
        } catch (\Exception $e) {
            Log::error('记录调用日志失败：' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 异步调用模型（用于长时间任务）
     * @param string $modelCode 模型代码
     * @param array $params 业务参数
     * @param array $apiConfig API配置
     * @param string $callbackUrl 回调URL
     * @return array
     */
    public static function invokeAsync($modelCode, $params, $apiConfig, $callbackUrl = '')
    {
        // 创建异步任务
        // 可以使用队列系统（如Redis队列、RabbitMQ等）
        
        try {
            // 准备请求数据
            $requestData = self::prepareRequest($modelCode, $params);
            if (!$requestData['success']) {
                return $requestData;
            }
            
            // 生成任务ID
            $taskId = 'task_' . time() . '_' . uniqid();
            
            // 将任务推入队列
            // 示例：使用ThinkPHP的队列
            // Queue::push('app\job\AiModelInvokeJob', [
            //     'task_id' => $taskId,
            //     'model_code' => $modelCode,
            //     'params' => $params,
            //     'api_config' => $apiConfig,
            //     'callback_url' => $callbackUrl
            // ], 'ai_model_invoke');
            
            return [
                'success' => true,
                'message' => '任务已创建',
                'task_id' => $taskId
            ];
            
        } catch (\Exception $e) {
            Log::error('创建异步任务失败：' . $e->getMessage());
            return [
                'success' => false,
                'message' => '创建任务失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 批量调用模型
     * @param string $modelCode 模型代码
     * @param array $batchParams 批量参数数组
     * @param array $apiConfig API配置
     * @return array
     */
    public static function invokeBatch($modelCode, $batchParams, $apiConfig)
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;
        
        foreach ($batchParams as $index => $params) {
            $result = self::invoke($modelCode, $params, $apiConfig);
            
            $results[] = [
                'index' => $index,
                'params' => $params,
                'result' => $result
            ];
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        return [
            'success' => $failCount == 0,
            'message' => "批量调用完成：成功{$successCount}个，失败{$failCount}个",
            'total' => count($batchParams),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results
        ];
    }
}

<?php

namespace app\service;

use app\model\ApiConfig;
use app\model\ApiCallLog;
use think\facade\Log;
use think\facade\Cache;

/**
 * API调用执行服务
 * Class ApiCallService
 * @package app\service
 */
class ApiCallService
{
    /**
     * 执行API调用
     * 
     * @param string $apiCode API代码
     * @param array $params 请求参数
     * @param int $aid 调用者平台ID
     * @param int $bid 调用者商家ID
     * @param int $mdid 调用者门店ID
     * @param int $uid 调用者用户ID
     * @return array
     */
    public function call($apiCode, $params, $aid, $bid, $mdid, $uid)
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();
        
        try {
            // 1. 权限验证
            $permissionService = new ApiPermissionService();
            $accessCheck = $permissionService->checkAccess($apiCode, $aid, $bid, $mdid, $uid);
            
            if (!$accessCheck['allowed']) {
                return $this->errorResponse($accessCheck['message'], 403);
            }
            
            $api = $accessCheck['api'];
            
            // 2. 余额检查和预扣费
            $balanceService = new ApiBalanceService();
            $balanceCheck = $balanceService->checkAndPreDeduct($api->id, $uid, $aid, $bid, $mdid);
            
            if (!$balanceCheck['success']) {
                return $this->errorResponse($balanceCheck['message'], 402);
            }
            
            $preDeductId = $balanceCheck['deduct_id'] ?? null;
            
            // 3. 调用第三方API
            $response = $this->callThirdPartyApi($api, $params);
            
            $endTime = microtime(true);
            $responseTime = intval(($endTime - $startTime) * 1000);
            
            // 4. 处理调用结果
            if ($response['success']) {
                // 计算实际消耗单位
                $pricingService = new ApiPricingService();
                $units = $pricingService->calculateUnits(
                    $api->pricing->billing_mode ?? 'fixed',
                    $response['data']
                );
                
                // 计算实际费用
                $charge = $pricingService->calculateCharge($api->id, $units, [
                    'api_config_id' => $api->id,
                    'caller_uid' => $uid
                ]);
                
                // 确认扣费
                $balanceService->confirmDeduct($preDeductId, $charge['amount']);
                
                // 记录成功日志
                $this->recordLog([
                    'api_config_id' => $api->id,
                    'caller_aid' => $aid,
                    'caller_bid' => $bid,
                    'caller_mdid' => $mdid,
                    'caller_uid' => $uid,
                    'request_id' => $requestId,
                    'request_params' => $params,
                    'response_data' => $response['data'],
                    'status_code' => $response['status_code'],
                    'is_success' => 1,
                    'consumed_units' => $units,
                    'charge_amount' => $charge['amount'],
                    'balance_before' => $balanceCheck['balance_before'] ?? 0,
                    'balance_after' => $balanceCheck['balance_after'] ?? 0,
                    'response_time' => $responseTime,
                    'ip_address' => request()->ip()
                ]);
                
                return $this->successResponse($response['data'], [
                    'request_id' => $requestId,
                    'consumed_units' => $units,
                    'charge_amount' => $charge['amount']
                ]);
            } else {
                // 调用失败，退回预扣费
                $balanceService->refundDeduct($preDeductId);
                
                // 记录失败日志
                $this->recordLog([
                    'api_config_id' => $api->id,
                    'caller_aid' => $aid,
                    'caller_bid' => $bid,
                    'caller_mdid' => $mdid,
                    'caller_uid' => $uid,
                    'request_id' => $requestId,
                    'request_params' => $params,
                    'response_data' => [],
                    'status_code' => $response['status_code'],
                    'is_success' => 0,
                    'error_message' => $response['error'],
                    'consumed_units' => 0,
                    'charge_amount' => 0,
                    'balance_before' => $balanceCheck['balance_before'] ?? 0,
                    'balance_after' => $balanceCheck['balance_before'] ?? 0,
                    'response_time' => $responseTime,
                    'ip_address' => request()->ip()
                ]);
                
                return $this->errorResponse($response['error'], $response['status_code']);
            }
            
        } catch (\Exception $e) {
            Log::error('API调用异常: ' . $e->getMessage(), [
                'api_code' => $apiCode,
                'request_id' => $requestId
            ]);
            
            // 异常情况退回预扣费
            if (isset($preDeductId)) {
                $balanceService->refundDeduct($preDeductId);
            }
            
            return $this->errorResponse('系统错误: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 调用第三方API
     */
    private function callThirdPartyApi($api, $params)
    {
        try {
            $url = $api->endpoint_url;
            $headers = $this->buildHeaders($api);
            $body = $this->buildRequestBody($api, $params);
            
            // 使用cURL发送请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => '网络请求失败: ' . $error,
                    'status_code' => 0
                ];
            }
            
            // 解析响应
            $data = json_decode($response, true);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => $data,
                    'error' => null,
                    'status_code' => $statusCode
                ];
            } else {
                return [
                    'success' => false,
                    'data' => $data,
                    'error' => $data['message'] ?? $data['error'] ?? '第三方API调用失败',
                    'status_code' => $statusCode
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
    
    /**
     * 构建请求头
     */
    private function buildHeaders($api)
    {
        $headers = [
            'Content-Type: application/json'
        ];
        
        // 根据不同提供商添加认证头
        switch ($api->provider) {
            case 'aliyun':
                $headers[] = 'Authorization: Bearer ' . $api->api_key;
                break;
            case 'baidu':
                // 百度可能需要access_token
                break;
            case 'openai':
                $headers[] = 'Authorization: Bearer ' . $api->api_key;
                break;
            default:
                if ($api->api_key) {
                    $headers[] = 'X-API-Key: ' . $api->api_key;
                }
        }
        
        return $headers;
    }
    
    /**
     * 构建请求体
     */
    private function buildRequestBody($api, $params)
    {
        // 合并默认配置和用户参数
        $config = $api->config_json ?? [];
        $requestData = array_merge($config, $params);
        
        return json_encode($requestData, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 生成请求ID
     */
    private function generateRequestId()
    {
        return date('YmdHis') . mt_rand(100000, 999999);
    }
    
    /**
     * 记录调用日志
     */
    private function recordLog($data)
    {
        $data['call_time'] = time();
        ApiCallLog::recordLog($data);
    }
    
    /**
     * 成功响应
     */
    private function successResponse($data, $meta = [])
    {
        return [
            'code' => 0,
            'msg' => 'success',
            'data' => $data,
            'meta' => $meta
        ];
    }
    
    /**
     * 错误响应
     */
    private function errorResponse($message, $code = 400)
    {
        return [
            'code' => $code,
            'msg' => $message,
            'data' => null
        ];
    }
    
    /**
     * 批量调用API
     */
    public function batchCall($apiCode, $batchParams, $aid, $bid, $mdid, $uid)
    {
        $results = [];
        
        foreach ($batchParams as $key => $params) {
            $result = $this->call($apiCode, $params, $aid, $bid, $mdid, $uid);
            $results[$key] = $result;
        }
        
        return $results;
    }
}

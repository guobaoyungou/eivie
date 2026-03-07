<?php
/**
 * AI API调用实现示例
 * 
 * 说明：这是一个参考实现，展示如何调用各个AI服务商的API
 * 使用时请将这些方法复制到 AiTravelPhoto.php 中替换现有的待实现方法
 * 
 * @package app\controller
 * @author AI Assistant
 * @date 2026-02-04
 */

namespace app\controller;

use think\facade\Db;

class AiApiImplementationExample
{
    /**
     * 调用阿里云通义万相API（完整实现）
     * 
     * 文档：https://help.aliyun.com/zh/dashscope/developer-reference/api-details-9
     * 
     * @param array $apiConfig API配置信息
     * @param array $params 模型参数
     * @param array $scene 场景信息
     * @return array ['success' => bool, 'image_url' => string, 'error' => string]
     */
    private function callAliyunApi($apiConfig, $params, $scene = [])
    {
        $apiKey = $apiConfig['api_key'];
        $endpoint = $apiConfig['endpoint_url'] ?: 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';
        
        // 构建请求参数
        $requestData = [
            'model' => 'wanx-v1', // 或从 $apiConfig['config_json'] 中读取
            'input' => [
                'prompt' => $params['prompt'] ?? ''
            ],
            'parameters' => [
                'style' => $params['style'] ?? '<auto>',
                'size' => $params['size'] ?? '1024*1024',
                'n' => 1,
                'seed' => isset($params['seed']) && $params['seed'] > 0 ? intval($params['seed']) : null
            ]
        ];
        
        // 添加负面提示词（如果支持）
        if (!empty($params['negative_prompt'])) {
            $requestData['input']['negative_prompt'] = $params['negative_prompt'];
        }
        
        try {
            // 发送HTTP POST请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'X-DashScope-Async: enable' // 启用异步模式
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 生产环境建议开启
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return ['success' => false, 'error' => 'CURL错误: ' . $curlError];
            }
            
            // 解析响应
            $result = json_decode($response, true);
            
            if ($httpCode == 200 && isset($result['output']['task_id'])) {
                // 异步任务，需要轮询查询结果
                $taskId = $result['output']['task_id'];
                $imageUrl = $this->waitForAliyunTask($apiConfig, $taskId);
                
                if ($imageUrl) {
                    return ['success' => true, 'image_url' => $imageUrl];
                } else {
                    return ['success' => false, 'error' => '生成超时或失败，请稍后重试'];
                }
            } else {
                $error = $result['message'] ?? $result['error'] ?? '未知错误';
                return ['success' => false, 'error' => '阿里云API返回错误: ' . $error];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '请求异常: ' . $e->getMessage()];
        }
    }
    
    /**
     * 轮询查询阿里云异步任务结果
     * 
     * @param array $apiConfig API配置
     * @param string $taskId 任务ID
     * @param int $maxWait 最大等待时间（秒）
     * @return string|false 成功返回图片URL，失败返回false
     */
    private function waitForAliyunTask($apiConfig, $taskId, $maxWait = 60)
    {
        $queryEndpoint = 'https://dashscope.aliyuncs.com/api/v1/tasks/' . $taskId;
        $startTime = time();
        
        while (time() - $startTime < $maxWait) {
            sleep(2); // 每2秒查询一次
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $queryEndpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiConfig['api_key']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (isset($result['output']['task_status'])) {
                $status = $result['output']['task_status'];
                
                if ($status == 'SUCCEEDED') {
                    // 成功，返回图片URL
                    if (isset($result['output']['results'][0]['url'])) {
                        return $result['output']['results'][0]['url'];
                    }
                    return false;
                } elseif ($status == 'FAILED') {
                    // 失败
                    return false;
                }
                // PENDING 或 RUNNING 状态继续等待
            }
        }
        
        return false; // 超时
    }
    
    /**
     * 调用百度文心一言API（示例实现）
     * 
     * 文档：https://cloud.baidu.com/doc/WENXINWORKSHOP/s/Klkqubb9w
     * 
     * @param array $apiConfig API配置信息
     * @param array $params 模型参数
     * @param array $scene 场景信息
     * @return array ['success' => bool, 'image_url' => string, 'error' => string]
     */
    private function callBaiduApi($apiConfig, $params, $scene = [])
    {
        $apiKey = $apiConfig['api_key'];
        $secretKey = $apiConfig['api_secret'];
        
        try {
            // 第一步：获取Access Token
            $tokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';
            $tokenParams = [
                'grant_type' => 'client_credentials',
                'client_id' => $apiKey,
                'client_secret' => $secretKey
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl . '?' . http_build_query($tokenParams));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $tokenResponse = curl_exec($ch);
            curl_close($ch);
            
            $tokenResult = json_decode($tokenResponse, true);
            if (!isset($tokenResult['access_token'])) {
                return ['success' => false, 'error' => '获取百度Access Token失败'];
            }
            
            $accessToken = $tokenResult['access_token'];
            
            // 第二步：调用文心一格API
            $endpoint = $apiConfig['endpoint_url'] ?: 'https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/text2image/sd_xl';
            $endpoint .= '?access_token=' . $accessToken;
            
            $requestData = [
                'prompt' => $params['prompt'] ?? '',
                'negative_prompt' => $params['negative_prompt'] ?? '',
                'size' => $params['size'] ?? '1024x1024',
                'n' => 1,
                'steps' => isset($params['steps']) ? intval($params['steps']) : 20,
                'sampler_index' => 'Euler a'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode == 200 && isset($result['data']['sub_task_result_list'][0]['final_image_list'][0]['img_url'])) {
                $imageUrl = $result['data']['sub_task_result_list'][0]['final_image_list'][0]['img_url'];
                return ['success' => true, 'image_url' => $imageUrl];
            } else {
                $error = $result['error_msg'] ?? '未知错误';
                return ['success' => false, 'error' => '百度API返回错误: ' . $error];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '请求异常: ' . $e->getMessage()];
        }
    }
    
    /**
     * 调用OpenAI DALL-E API（示例实现）
     * 
     * 文档：https://platform.openai.com/docs/api-reference/images/create
     * 
     * @param array $apiConfig API配置信息
     * @param array $params 模型参数
     * @param array $scene 场景信息
     * @return array ['success' => bool, 'image_url' => string, 'error' => string]
     */
    private function callOpenAiApi($apiConfig, $params, $scene = [])
    {
        $apiKey = $apiConfig['api_key'];
        $endpoint = $apiConfig['endpoint_url'] ?: 'https://api.openai.com/v1/images/generations';
        
        try {
            $requestData = [
                'model' => 'dall-e-3', // 或 dall-e-2
                'prompt' => $params['prompt'] ?? '',
                'n' => 1,
                'size' => $params['size'] ?? '1024x1024', // 1024x1024, 1792x1024, 1024x1792
                'quality' => $params['quality'] ?? 'standard', // standard or hd
                'style' => $params['style'] ?? 'vivid' // vivid or natural
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode == 200 && isset($result['data'][0]['url'])) {
                $imageUrl = $result['data'][0]['url'];
                return ['success' => true, 'image_url' => $imageUrl];
            } else {
                $error = $result['error']['message'] ?? '未知错误';
                return ['success' => false, 'error' => 'OpenAI API返回错误: ' . $error];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '请求异常: ' . $e->getMessage()];
        }
    }
    
    /**
     * 下载远程图片到本地（可选功能）
     * 
     * 某些API返回的图片URL可能有时效性，建议下载到本地存储
     * 
     * @param string $imageUrl 远程图片URL
     * @param string $savePath 保存路径（相对于public目录）
     * @return string|false 成功返回本地路径，失败返回false
     */
    private function downloadImageToLocal($imageUrl, $savePath = '')
    {
        try {
            // 生成保存路径
            if (empty($savePath)) {
                $date = date('Ymd');
                $filename = md5($imageUrl . time()) . '.jpg';
                $savePath = "uploads/ai_generated/{$date}/{$filename}";
            }
            
            $fullPath = public_path() . $savePath;
            $dir = dirname($fullPath);
            
            // 创建目录
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // 下载图片
            $ch = curl_init($imageUrl);
            $fp = fopen($fullPath, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            
            if ($httpCode == 200 && file_exists($fullPath)) {
                // 返回相对URL路径
                return '/' . $savePath;
            } else {
                @unlink($fullPath);
                return false;
            }
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 记录API调用日志（推荐添加）
     * 
     * @param array $logData 日志数据
     */
    private function logApiCall($logData)
    {
        try {
            Db::name('api_call_log')->insert([
                'api_config_id' => $logData['api_config_id'] ?? 0,
                'caller_uid' => $logData['caller_uid'] ?? 0,
                'request_params' => json_encode($logData['request_params'] ?? []),
                'response_data' => json_encode($logData['response_data'] ?? []),
                'is_success' => $logData['is_success'] ?? 0,
                'error_message' => $logData['error_message'] ?? '',
                'response_time' => $logData['response_time'] ?? 0,
                'call_time' => time()
            ]);
        } catch (\Exception $e) {
            // 日志记录失败不影响主流程
        }
    }
}

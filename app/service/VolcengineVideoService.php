<?php
/**
 * 火山引擎方舟视频生成服务类
 * 封装Seedance全系列视频生成模型API调用
 * 
 * 支持模型：
 * - doubao-seedance-1-5-pro: 文生视频、首帧图生视频、首尾帧图生视频、有声视频
 * - doubao-seedance-1-0-pro: 文生视频、首帧图生视频、首尾帧图生视频
 * - doubao-seedance-1-0-pro-fast: 文生视频、首帧图生视频
 * - doubao-seedance-1-0-lite-t2v: 文生视频
 * - doubao-seedance-1-0-lite-i2v: 首帧图生视频、首尾帧图生视频、参考图生视频
 * 
 * @package app\service
 * @author AI旅拍开发团队
 * @date 2026-02-28
 */

namespace app\service;

use think\facade\Log;
use think\facade\Db;

class VolcengineVideoService
{
    /**
     * API基础地址
     */
    private $apiUrl = 'https://ark.cn-beijing.volces.com';
    
    /**
     * 内容生成任务端点（视频生成）
     * 注意：火山方舟Seedance系列使用contents/generations/tasks端点
     * 而非旧的videos/generations端点
     */
    private $videosEndpoint = '/api/v3/contents/generations/tasks';
    
    /**
     * API Key (Ark API Key)
     */
    private $apiKey;
    
    /**
     * 请求超时时间（秒）
     */
    private $timeout = 30;
    
    /**
     * 轮询间隔（秒）
     */
    private $pollInterval = 10;
    
    /**
     * 最大轮询时间（秒）
     */
    private $maxPollTime = 600;
    
    /**
     * 商家ID
     */
    private $bid;
    
    /**
     * 模型能力矩阵（使用基础model_code，不含日期后缀）
     */
    private static $modelCapabilities = [
        'doubao-seedance-1-5-pro' => [
            'text_to_video' => true,
            'first_frame' => true,
            'first_last_frame' => true,
            'reference_images' => false,
            'with_audio' => true
        ],
        'doubao-seedance-1-0-pro' => [
            'text_to_video' => true,
            'first_frame' => true,
            'first_last_frame' => true,
            'reference_images' => false,
            'with_audio' => false
        ],
        'doubao-seedance-1-0-pro-fast' => [
            'text_to_video' => true,
            'first_frame' => true,
            'first_last_frame' => false,
            'reference_images' => false,
            'with_audio' => false
        ],
        'doubao-seedance-1-0-lite-t2v' => [
            'text_to_video' => true,
            'first_frame' => false,
            'first_last_frame' => false,
            'reference_images' => false,
            'with_audio' => false
        ],
        'doubao-seedance-1-0-lite-i2v' => [
            'text_to_video' => false,
            'first_frame' => true,
            'first_last_frame' => true,
            'reference_images' => true,
            'with_audio' => false
        ],
        // 兼容旧的model_code
        'doubao-seedance-2-0' => [
            'text_to_video' => true,
            'first_frame' => true,
            'first_last_frame' => true,
            'reference_images' => false,
            'with_audio' => true
        ]
    ];
    
    /**
     * 从完整模型标识中提取基础model_code（去掉日期后缀）
     * 例如: doubao-seedance-1-5-pro-251215 => doubao-seedance-1-5-pro
     * 
     * @param string $fullModelCode 完整模型标识（可能含日期后缀）
     * @return string 基础model_code
     */
    public static function normalizeModelCode($fullModelCode)
    {
        // 如果已经在能力矩阵中直接匹配，返回原值
        if (isset(self::$modelCapabilities[$fullModelCode])) {
            return $fullModelCode;
        }
        
        // 尝试去掉末尾的日期后缀 (格式: -YYMMDD 如 -251215)
        $normalized = preg_replace('/-\d{6}$/', '', $fullModelCode);
        if (isset(self::$modelCapabilities[$normalized])) {
            return $normalized;
        }
        
        // 尝试去掉末尾的日期后缀 (格式: -YYYYMMDD 如 -20251215)
        $normalized = preg_replace('/-\d{8}$/', '', $fullModelCode);
        if (isset(self::$modelCapabilities[$normalized])) {
            return $normalized;
        }
        
        // 无法匹配，返回原值
        return $fullModelCode;
    }
    
    /**
     * 构造函数
     * 
     * @param int $bid 商家ID
     * @param string $apiKey API Key（可选，传入则覆盖配置）
     */
    public function __construct($bid = 0, $apiKey = '')
    {
        $this->bid = $bid;
        
        // 加载配置
        $config = config('aivideo.volcengine') ?? [];
        if (!empty($config['api_url'])) {
            $this->apiUrl = $config['api_url'];
        }
        if (!empty($config['videos_endpoint'])) {
            $this->videosEndpoint = $config['videos_endpoint'];
        }
        if (!empty($config['timeout'])) {
            $this->timeout = intval($config['timeout']);
        }
        if (!empty($config['poll_interval'])) {
            $this->pollInterval = intval($config['poll_interval']);
        }
        if (!empty($config['max_poll_time'])) {
            $this->maxPollTime = intval($config['max_poll_time']);
        }
        
        // 设置API Key
        if (!empty($apiKey)) {
            $this->apiKey = $apiKey;
        } elseif ($bid > 0) {
            $this->loadApiKeyFromConfig($bid);
        }
    }
    
    /**
     * 从API配置加载API Key
     * 
     * @param int $bid 商家ID
     */
    private function loadApiKeyFromConfig($bid)
    {
        // 先查询商家级别配置
        $apiConfig = Db::name('api_config')
            ->where('bid', $bid)
            ->where('provider', 'volcengine')
            ->where('is_active', 1)
            ->find();
        
        // 如果没有商家配置，使用平台配置
        if (!$apiConfig) {
            $apiConfig = Db::name('api_config')
                ->where('bid', 0)
                ->where('provider', 'volcengine')
                ->where('is_active', 1)
                ->find();
        }
        
        if ($apiConfig && !empty($apiConfig['api_key'])) {
            $this->apiKey = $apiConfig['api_key'];
        }
    }
    
    /**
     * 设置API Key
     * 
     * @param string $apiKey API Key
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }
    
    /**
     * 验证API Key格式
     * 火山方舟Ark API Key不以"AKLT"开头（IAM Access Key以AKLT开头）
     * 
     * @param string $apiKey API Key
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateApiKey($apiKey = null)
    {
        $key = $apiKey ?? $this->apiKey;
        
        if (empty($key)) {
            return ['valid' => false, 'message' => 'API Key不能为空'];
        }
        
        // 检查是否误用IAM Access Key
        if (strpos($key, 'AKLT') === 0) {
            return [
                'valid' => false, 
                'message' => '请使用Ark API Key而非IAM Access Key（不应以AKLT开头）'
            ];
        }
        
        // 检查长度（UUID格式约36字符）
        if (strlen($key) < 20 || strlen($key) > 100) {
            return ['valid' => false, 'message' => 'API Key格式不正确'];
        }
        
        return ['valid' => true, 'message' => 'OK'];
    }
    
    /**
     * 创建视频生成任务
     * 
     * @param array $params 参数
     *   - model: 模型代码
     *   - content: 多模态内容数组
     *   - with_audio: 是否有声视频（可选）
     *   - duration: 视频时长（可选）
     *   - resolution: 分辨率（可选）
     *   - seed: 随机种子（可选）
     * @return array ['success' => bool, 'task_id' => string, 'message' => string]
     */
    public function createVideoTask(array $params)
    {
        // 验证API Key
        $keyValidation = $this->validateApiKey();
        if (!$keyValidation['valid']) {
            return ['success' => false, 'message' => $keyValidation['message']];
        }
        
        // 构建请求数据
        // 注意：火山方舟Seedance API仅接受model和content两个字段
        // duration/resolution等参数通过content中text元素的命令行标志传递（如 --duration 5）
        $requestData = [
            'model' => $params['model'] ?? 'doubao-seedance-1-5-pro',
            'content' => $params['content'] ?? []
        ];
        
        // 验证content不能为空
        if (empty($requestData['content'])) {
            return ['success' => false, 'message' => 'content参数不能为空，请提供至少一个text类型的content元素'];
        }
        
        // seed参数（如果API支持）
        if (isset($params['seed'])) {
            $requestData['seed'] = intval($params['seed']);
        }
        
        $url = $this->apiUrl . $this->videosEndpoint;
        
        Log::info('VolcengineVideoService::createVideoTask 请求', [
            'url' => $url,
            'model' => $requestData['model'],
            'content_count' => count($requestData['content'])
        ]);
        
        $response = $this->sendRequest($url, $requestData, 'POST');
        
        if (!$response['success']) {
            return $response;
        }
        
        $data = $response['data'];
        
        // 解析任务ID
        $taskId = $data['id'] ?? '';
        if (empty($taskId)) {
            return ['success' => false, 'message' => 'API返回数据缺少任务ID'];
        }
        
        return [
            'success' => true,
            'task_id' => $taskId,
            'model' => $data['model'] ?? $requestData['model'],
            'status' => $data['status'] ?? 'pending',
            'created_at' => $data['created_at'] ?? time(),
            'raw_response' => $data
        ];
    }
    
    /**
     * 查询任务状态
     * 
     * @param string $taskId 任务ID
     * @return array
     */
    public function queryTaskStatus($taskId)
    {
        if (empty($taskId)) {
            return ['success' => false, 'message' => '任务ID不能为空'];
        }
        
        // 验证API Key
        $keyValidation = $this->validateApiKey();
        if (!$keyValidation['valid']) {
            return ['success' => false, 'message' => $keyValidation['message']];
        }
        
        $url = $this->apiUrl . $this->videosEndpoint . '/' . $taskId;
        
        $response = $this->sendRequest($url, [], 'GET');
        
        if (!$response['success']) {
            return $response;
        }
        
        $data = $response['data'];
        
        $result = [
            'success' => true,
            'task_id' => $taskId,
            'status' => $data['status'] ?? 'unknown',
            'model' => $data['model'] ?? '',
            'created_at' => $data['created_at'] ?? 0,
            'progress' => $data['progress'] ?? 0  // 进度百分比 0-100
        ];
        
        // 任务成功时提取视频URL
        if ($result['status'] === 'succeeded') {
            $content = $data['content'] ?? [];
            $result['video_url'] = '';
            $result['audio_url'] = '';
            
            // 记录content结构用于调试
            Log::info('queryTaskStatus succeeded, content结构', [
                'task_id' => $taskId,
                'content_type' => gettype($content),
                'content_raw' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ]);
            
            // 火山方舟Seedance API实际返回格式:
            // {"content": {"video_url": "https://..."}} - content是对象，不是数组
            if (is_array($content) && isset($content['video_url'])) {
                // 格式1: content是对象，直接包含video_url字段
                $result['video_url'] = $content['video_url'];
                if (isset($content['audio_url'])) {
                    $result['audio_url'] = $content['audio_url'];
                }
            } elseif (is_array($content) && !isset($content['video_url'])) {
                // 格式2: content是数组，遍历查找video_url类型的item
                foreach ($content as $item) {
                    if (isset($item['type'])) {
                        // video_url类型：视频输出
                        if ($item['type'] === 'video_url') {
                            // 兼容嵌套格式: {"type":"video_url","video_url":{"url":"..."}}
                            if (is_array($item['video_url'] ?? null)) {
                                $result['video_url'] = $item['video_url']['url'] ?? '';
                            } else {
                                $result['video_url'] = $item['video_url'] ?? '';
                            }
                        }
                        // audio_url类型：音频输出
                        if ($item['type'] === 'audio_url') {
                            if (is_array($item['audio_url'] ?? null)) {
                                $result['audio_url'] = $item['audio_url']['url'] ?? '';
                            } else {
                                $result['audio_url'] = $item['audio_url'] ?? '';
                            }
                        }
                    }
                }
            }
            
            // 兼容直接返回url的情况（顶层）
            if (empty($result['video_url']) && !empty($data['video_url'])) {
                $result['video_url'] = $data['video_url'];
            }
            
            // 记录解析结果
            Log::info('queryTaskStatus 解析视频URL完成', [
                'task_id' => $taskId,
                'video_url' => $result['video_url'] ? substr($result['video_url'], 0, 100) . '...' : '',
                'audio_url' => $result['audio_url'] ? substr($result['audio_url'], 0, 100) . '...' : ''
            ]);
        }
        
        // 任务失败时提取错误信息
        if ($result['status'] === 'failed') {
            $error = $data['error'] ?? [];
            $result['error_code'] = $error['code'] ?? '';
            $result['error_message'] = $error['message'] ?? '视频生成失败';
        }
        
        $result['raw_response'] = $data;
        
        return $result;
    }
    
    /**
     * 取消任务
     * 
     * @param string $taskId 任务ID
     * @return array
     */
    public function cancelTask($taskId)
    {
        if (empty($taskId)) {
            return ['success' => false, 'message' => '任务ID不能为空'];
        }
        
        // 验证API Key
        $keyValidation = $this->validateApiKey();
        if (!$keyValidation['valid']) {
            return ['success' => false, 'message' => $keyValidation['message']];
        }
        
        $url = $this->apiUrl . $this->videosEndpoint . '/' . $taskId . '/cancel';
        
        $response = $this->sendRequest($url, [], 'POST');
        
        return $response;
    }
    
    /**
     * 轮询任务直到完成
     * 
     * @param string $taskId 任务ID
     * @param callable $progressCallback 进度回调函数（可选）
     * @return array
     */
    public function pollUntilComplete($taskId, $progressCallback = null)
    {
        $startTime = time();
        $pollCount = 0;
        
        while (true) {
            $pollCount++;
            
            // 检查是否超时
            $elapsed = time() - $startTime;
            if ($elapsed > $this->maxPollTime) {
                return [
                    'success' => false,
                    'message' => '任务轮询超时（已等待' . $elapsed . '秒）',
                    'status' => 'timeout'
                ];
            }
            
            // 查询任务状态
            $result = $this->queryTaskStatus($taskId);
            
            if (!$result['success']) {
                return $result;
            }
            
            $status = $result['status'];
            
            // 调用进度回调
            if (is_callable($progressCallback)) {
                $progressCallback([
                    'status' => $status,
                    'poll_count' => $pollCount,
                    'elapsed_time' => $elapsed
                ]);
            }
            
            Log::info('VolcengineVideoService::pollUntilComplete 轮询', [
                'task_id' => $taskId,
                'status' => $status,
                'poll_count' => $pollCount,
                'elapsed_time' => $elapsed
            ]);
            
            // 判断终态
            if ($status === 'succeeded') {
                return $result;
            }
            
            if ($status === 'failed') {
                return [
                    'success' => false,
                    'message' => $result['error_message'] ?? '视频生成失败',
                    'error_code' => $result['error_code'] ?? '',
                    'status' => 'failed',
                    'raw_response' => $result['raw_response'] ?? []
                ];
            }
            
            if ($status === 'cancelled') {
                return [
                    'success' => false,
                    'message' => '任务已取消',
                    'status' => 'cancelled'
                ];
            }
            
            // 等待后继续轮询
            sleep($this->pollInterval);
        }
    }
    
    /**
     * 一站式视频生成（创建任务+轮询+返回结果）
     * 
     * @param array $params 参数
     * @param callable $progressCallback 进度回调（可选）
     * @return array
     */
    public function generateVideo(array $params, $progressCallback = null)
    {
        // 创建任务
        $createResult = $this->createVideoTask($params);
        
        if (!$createResult['success']) {
            return $createResult;
        }
        
        $taskId = $createResult['task_id'];
        
        // 轮询等待完成
        $result = $this->pollUntilComplete($taskId, $progressCallback);
        
        if ($result['success']) {
            $result['task_id'] = $taskId;
        }
        
        return $result;
    }
    
    /**
     * 检查模型是否支持指定的视频生成模式
     * 
     * @param string $modelCode 模型代码
     * @param string $videoMode 视频模式（text_to_video/first_frame/first_last_frame/reference_images）
     * @return bool
     */
    public static function supportsMode($modelCode, $videoMode)
    {
        $baseCode = self::normalizeModelCode($modelCode);
        if (!isset(self::$modelCapabilities[$baseCode])) {
            return false;
        }
        
        return self::$modelCapabilities[$baseCode][$videoMode] ?? false;
    }
    
    /**
     * 检查模型是否支持有声视频
     * 
     * @param string $modelCode 模型代码
     * @return bool
     */
    public static function supportsAudio($modelCode)
    {
        return self::supportsMode(self::normalizeModelCode($modelCode), 'with_audio');
    }
    
    /**
     * 获取模型能力
     * 
     * @param string $modelCode 模型代码
     * @return array
     */
    public static function getModelCapabilities($modelCode)
    {
        $baseCode = self::normalizeModelCode($modelCode);
        return self::$modelCapabilities[$baseCode] ?? [];
    }
    
    /**
     * 获取所有支持的模型代码
     * 
     * @return array
     */
    public static function getSupportedModels()
    {
        return array_keys(self::$modelCapabilities);
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param string $method 请求方法
     * @return array
     */
    private function sendRequest($url, $data, $method = 'POST')
    {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        // cURL错误
        if ($errno) {
            Log::error('VolcengineVideoService HTTP请求失败', [
                'url' => $url,
                'error' => $error,
                'errno' => $errno
            ]);
            return ['success' => false, 'message' => '网络请求失败: ' . $error];
        }
        
        // 解析响应
        $result = json_decode($response, true);
        
        // 记录响应日志
        Log::info('VolcengineVideoService HTTP响应', [
            'url' => $url,
            'http_code' => $httpCode,
            'response' => $result
        ]);
        
        // HTTP错误处理
        if ($httpCode === 401) {
            return [
                'success' => false, 
                'message' => 'API Key无效，请检查是否使用了正确的Ark API Key'
            ];
        }
        
        if ($httpCode === 429) {
            return [
                'success' => false, 
                'message' => '请求频率超限，请稍后重试',
                'retry_after' => 30
            ];
        }
        
        if ($httpCode >= 500) {
            return [
                'success' => false, 
                'message' => '火山方舟服务端错误，请稍后重试'
            ];
        }
        
        if ($httpCode === 404) {
            return [
                'success' => false, 
                'message' => '[HTTP_404] 接口未找到(404): API Key可能没有访问该模型的权限。'
                    . '请登录火山引擎方舟控制台(ark.cn-beijing.volces.com)检查：'
                    . '1.是否已开通图像/视频生成服务；'
                    . '2.API Key是否具有相应模型的调用权限；'
                    . '3.是否需要创建推理接入点',
                'http_code' => 404
            ];
        }
        
        if ($httpCode !== 200) {
            $errorMsg = $result['error']['message'] ?? $result['message'] ?? '请求失败(HTTP ' . $httpCode . ')';
            return ['success' => false, 'message' => $errorMsg, 'http_code' => $httpCode];
        }
        
        return ['success' => true, 'data' => $result];
    }
}

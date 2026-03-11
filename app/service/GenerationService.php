<?php
/**
 * 通用生成服务类
 * 处理照片生成和视频生成的核心业务逻辑
 */
namespace app\service;

use think\facade\Db;
use think\facade\Queue;
use think\facade\Log;
use app\model\GenerationRecord;
use app\model\GenerationOutput;
use app\model\GenerationSceneTemplate;
use app\service\VolcengineVideoService;
use app\service\AttachmentTransferService;

class GenerationService
{
    /**
     * @var SystemApiKeyService
     */
    protected $apiKeyService;
    
    /**
     * @var ModelSquareService
     */
    protected $modelService;
    
    public function __construct()
    {
        $this->apiKeyService = new SystemApiKeyService();
        $this->modelService = new ModelSquareService();
    }
    
    /**
     * 获取可用模型列表（按输出类型筛选）
     * @param string $outputType image/video
     * @return array
     */
    public function getAvailableModels($outputType = 'image')
    {
        // 获取输出类型对应的模型类型
        $types = Db::name('model_type')
            ->where('status', 1)
            ->select()->toArray();
        
        $typeIds = [];
        foreach ($types as $type) {
            $outputTypes = is_string($type['output_types']) 
                ? json_decode($type['output_types'], true) 
                : $type['output_types'];
            if (in_array($outputType, $outputTypes ?: [])) {
                $typeIds[] = $type['id'];
            }
        }
        
        if (empty($typeIds)) {
            return [];
        }
        
        // 获取模型列表
        $models = Db::name('model_info')->alias('m')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->field('m.id, m.model_code, m.model_name, m.provider_id, m.type_id, m.input_schema, m.endpoint_url, m.task_type, m.description, p.provider_name, p.provider_code, t.type_name')
            ->where('m.type_id', 'in', $typeIds)
            ->where('m.is_active', 1)
            ->where('p.status', 1)
            ->order('m.sort asc, m.id desc')
            ->select()->toArray();
        
        // 检查API Key配置状态
        foreach ($models as &$model) {
            $apiKeyConfig = Db::name('system_api_key')
                ->where('provider_id', $model['provider_id'])
                ->where('is_active', 1)
                ->find();
            $model['api_key_configured'] = $apiKeyConfig ? true : false;
            $inputSchema = is_string($model['input_schema']) 
                ? json_decode($model['input_schema'], true) 
                : $model['input_schema'];
            // 标准化 input_schema 格式
            $model['input_schema_parsed'] = $this->normalizeInputSchema($inputSchema);
        }
        
        return $models;
    }
    
    /**
     * 获取模型详情（含参数Schema）
     * @param int $modelId
     * @return array|null
     */
    public function getModelDetail($modelId)
    {
        $model = Db::name('model_info')->alias('m')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->field('m.*, p.provider_name, p.provider_code, p.auth_config, t.type_name, t.input_types, t.output_types')
            ->where('m.id', $modelId)
            ->find();
        
        if (!$model) {
            return null;
        }
        
        // 解析JSON字段
        $jsonFields = ['input_schema', 'output_schema', 'pricing_config', 'auth_config', 'input_types', 'output_types'];
        foreach ($jsonFields as $field) {
            if (isset($model[$field]) && is_string($model[$field])) {
                $model[$field] = json_decode($model[$field], true);
            }
        }
        
        // 标准化 input_schema 格式
        $model['input_schema'] = $this->normalizeInputSchema($model['input_schema']);
        
        // 检查API Key
        $apiKeyConfig = Db::name('system_api_key')
            ->where('provider_id', $model['provider_id'])
            ->where('is_active', 1)
            ->find();
        $model['api_key_configured'] = $apiKeyConfig ? true : false;
        
        return $model;
    }
    
    /**
     * 创建生成任务
     * @param array $data
     * @return array
     */
    public function createTask($data)
    {
        // 验证模型
        $model = $this->getModelDetail($data['model_id']);
        if (!$model) {
            return ['status' => 0, 'msg' => '所选模型不存在'];
        }
        
        if (!$model['is_active']) {
            return ['status' => 0, 'msg' => '所选模型已停用'];
        }
        
        if (!$model['api_key_configured']) {
            return ['status' => 0, 'msg' => '该模型的供应商API Key未配置，请先在API Key配置中添加'];
        }
        
        // 验证输入参数
        $inputParams = $data['input_params'] ?? [];
        $validationResult = $this->validateInputParams($model, $inputParams);
        if (!$validationResult['valid']) {
            return ['status' => 0, 'msg' => $validationResult['msg']];
        }
        
        // 创建生成记录
        $record = GenerationRecord::createRecord([
            'aid' => $data['aid'] ?? 0,
            'bid' => $data['bid'] ?? 0,
            'uid' => $data['uid'] ?? 0,
            'generation_type' => $data['generation_type'],
            'model_id' => $model['id'],
            'model_code' => $model['model_code'],
            'scene_id' => $data['scene_id'] ?? 0,
            'capability_type' => $data['capability_type'] ?? 0,
            'input_params' => $inputParams,
            'output_type' => $data['generation_type'] == GenerationRecord::TYPE_PHOTO ? 'image' : 'video',
            'order_id' => $data['order_id'] ?? 0
        ]);
        
        // 直接同步执行生成任务（请求立即处理）
        // 注：对于异步模型（视频生成等），会先返回任务ID，前端轮询状态
        $this->executeGeneration($record->id);
        
        // 如果使用了场景模板，增加使用计数
        $sceneId = $data['scene_id'] ?? 0;
        if ($sceneId > 0) {
            $this->incrementTemplateUsage($sceneId);
        }
        
        return [
            'status' => 1,
            'msg' => '任务创建成功，正在处理中',
            'record_id' => $record->id,
            'task_id' => $record->task_id
        ];
    }
    
    /**
     * 验证输入参数
     */
    protected function validateInputParams($model, $params)
    {
        $schema = $model['input_schema'] ?? [];
        
        // 确保 schema 已标准化
        $schema = $this->normalizeInputSchema($schema);
        
        // 检查必填参数（标准化后的 required 数组）
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!isset($params[$field]) || $params[$field] === '') {
                    $fieldLabel = $field;
                    if (isset($schema['properties'][$field]['label'])) {
                        $fieldLabel = $schema['properties'][$field]['label'];
                    } elseif (isset($schema['properties'][$field]['title'])) {
                        $fieldLabel = $schema['properties'][$field]['title'];
                    }
                    return ['valid' => false, 'msg' => "请填写{$fieldLabel}"];
                }
            }
        }
        
        return ['valid' => true, 'msg' => ''];
    }
    
    /**
     * 执行生成任务
     * @param int $recordId
     * @return array
     */
    public function executeGeneration($recordId)
    {
        $record = GenerationRecord::find($recordId);
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        // 获取模型信息
        $model = $this->getModelDetail($record->model_id);
        if (!$model) {
            $record->markFailed('MODEL_NOT_FOUND', '模型不存在');
            return ['status' => 0, 'msg' => '模型不存在'];
        }
        
        // 获取API Key
        $apiKeyConfig = $this->apiKeyService->getActiveConfigByProvider($model['provider_code']);
        if (!$apiKeyConfig) {
            $record->markFailed('API_KEY_NOT_CONFIGURED', 'API Key未配置');
            return ['status' => 0, 'msg' => 'API Key未配置'];
        }
        
        // 更新为处理中
        $taskId = $this->generateTaskId();
        $record->markProcessing($taskId);
        
        $startTime = microtime(true);
        
        try {
            // 调用模型API
            $result = $this->callModelApi($model, $apiKeyConfig, $record->input_params);
            
            $costTime = (microtime(true) - $startTime) * 1000;
            
            if ($result['status'] == 1) {
                // 检查是否为异步任务（如Seedance视频生成）
                if (!empty($result['async'])) {
                    // 异步任务：存储外部任务ID，保持处理中状态，等待前端轮询
                    $externalTaskId = $result['external_task_id'] ?? '';
                    if (!empty($externalTaskId)) {
                        $record->task_id = $externalTaskId;
                        $record->save();
                    }
                    \think\facade\Log::info('异步任务已提交, record_id=' . $recordId . ' external_task_id=' . $externalTaskId);
                    return ['status' => 1, 'msg' => '异步任务已提交，等待处理', 'async' => true];
                }
                
                // 同步结果：直接保存输出
                if (!empty($result['outputs'])) {
                    GenerationOutput::createOutputs($record->id, $result['outputs']);
                }
                
                $record->markSuccess(
                    $costTime,
                    $result['tokens'] ?? 0,
                    $result['cost'] ?? 0
                );
                
                return ['status' => 1, 'msg' => '生成成功', 'outputs' => $result['outputs'] ?? []];
            } else {
                $record->markFailed($result['error_code'] ?? 'API_ERROR', $result['msg'] ?? '生成失败');
                return ['status' => 0, 'msg' => $result['msg'] ?? '生成失败'];
            }
        } catch (\Exception $e) {
            $record->markFailed('EXCEPTION', $e->getMessage());
            return ['status' => 0, 'msg' => $e->getMessage()];
        }
    }
    
    /**
     * 调用模型API
     */
    protected function callModelApi($model, $apiKeyConfig, $inputParams)
    {
        $endpoint = $model['endpoint_url'];
        $apiKey = $apiKeyConfig['api_key_decrypted'];
        $apiSecret = $apiKeyConfig['api_secret_decrypted'] ?? '';
        
        // 检查API Key解密是否成功
        if (empty($apiKey) || $apiKey === false) {
            return [
                'status' => 0,
                'error_code' => 'API_KEY_DECRYPT_FAILED',
                'msg' => 'API Key解密失败，请检查系统加密配置或重新保存API Key'
            ];
        }
        
        // 检查API Key格式（特别针对火山引擎方舟平台）
        $providerCode = $model['provider_code'];
        if (in_array($providerCode, ['volcengine', 'doubao'])) {
            // AKLT前缀是火山引擎IAM Access Key，不是Ark API Key
            if (strpos($apiKey, 'AKLT') === 0) {
                return [
                    'status' => 0,
                    'error_code' => 'INVALID_API_KEY_FORMAT',
                    'msg' => 'API Key格式错误：检测到IAM Access Key(AKLT开头)。火山引擎方舟Ark API需要使用「方舟Ark API Key」，请登录火山引擎方舟控制台(ark.cn-beijing.volces.com)，在「开发管理-API Key管理」中创建新的API Key，然后在系统设置中更新。'
                ];
            }
        }
        
        // 构建请求头
        $headers = $this->buildAuthHeaders($providerCode, $apiKey, $apiSecret, $model['auth_config'] ?? []);
        
        // 构建请求体（含类型转换）
        $requestBody = $this->buildRequestBody($model, $inputParams);
        
        // 记录请求日志（脱敏）
        \think\facade\Log::info('模型API请求', [
            'endpoint' => $endpoint,
            'provider' => $providerCode,
            'model_code' => $model['model_code'],
            'api_key_prefix' => substr($apiKey, 0, 8) . '***',
            'request_body' => $requestBody
        ]);
        
        // 发送请求
        $response = $this->sendHttpRequest($endpoint, 'POST', $headers, $requestBody);
        
        // 记录响应日志
        \think\facade\Log::info('模型API响应(callModelApi) http_code=' . $response['http_code'] 
            . ' body_type=' . gettype($response['body'])
            . ' body=' . (is_array($response['body']) ? json_encode($response['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : substr((string)$response['body'], 0, 2000))
            . ' error=' . ($response['error'] ?? ''));
        
        if ($response['http_code'] != 200) {
            // 解析错误消息：兼容多种错误响应格式
            $errorMsg = $this->extractErrorMessage($response);
            $errorCode = 'HTTP_' . $response['http_code'];
            
            // 401 特别提示
            if ($response['http_code'] == 401) {
                $errorMsg = '认证失败: ' . ($errorMsg ?: 'API Key无效或已过期，请检查API Key配置是否正确');
            }
            
            // 404 特别提示 - 通常表示API Key没有相应模型的访问权限
            if ($response['http_code'] == 404) {
                $errorMsg = '接口未找到(404): API Key可能没有访问该模型的权限。请登录火山引擎方舟控制台(ark.cn-beijing.volces.com)检查：1.是否已开通图像/视频生成服务；2.API Key是否具有相应模型的调用权限；3.是否需要创建推理接入点';
            }
            
            return [
                'status' => 0,
                'error_code' => $errorCode,
                'msg' => $errorMsg ?: '请求失败(HTTP ' . $response['http_code'] . ')'
            ];
        }
        
        // 解析响应
        return $this->parseApiResponse($model, $response['body']);
    }
    
    /**
     * 从API响应中提取错误消息
     */
    protected function extractErrorMessage($response)
    {
        $body = $response['body'];
        
        if (!is_array($body)) {
            return is_string($body) ? $body : ($response['error'] ?? '');
        }
        
        // 格式1: {"error": {"message": "...", "code": "..."}}
        if (isset($body['error']) && is_array($body['error'])) {
            return $body['error']['message'] ?? $body['error']['msg'] ?? json_encode($body['error'], JSON_UNESCAPED_UNICODE);
        }
        
        // 格式2: {"error": "error string"}
        if (isset($body['error']) && is_string($body['error'])) {
            return $body['error'];
        }
        
        // 格式3: {"message": "..."}
        if (isset($body['message'])) {
            return $body['message'];
        }
        
        // 格式4: {"msg": "..."}
        if (isset($body['msg'])) {
            return $body['msg'];
        }
        
        // 格式5: {"ResponseMetadata": {"Error": {"Message": "..."}}} (火山引擎格式)
        if (isset($body['ResponseMetadata']['Error']['Message'])) {
            return $body['ResponseMetadata']['Error']['Message'];
        }
        
        // 返回完整body作为参考
        return json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 构建认证头
     */
    protected function buildAuthHeaders($providerCode, $apiKey, $apiSecret, $authConfig)
    {
        $headers = [
            'Content-Type: application/json'
        ];
        
        // 根据供应商类型构建认证
        switch ($providerCode) {
            case 'volcengine':
            case 'doubao':
                $headers[] = 'Authorization: Bearer ' . $apiKey;
                break;
            case 'aliyun':
            case 'dashscope':
                $headers[] = 'Authorization: Bearer ' . $apiKey;
                // 启用DashScope异步模式，避免同步请求超时
                $headers[] = 'X-DashScope-Async: enable';
                break;
            case 'openai':
                $headers[] = 'Authorization: Bearer ' . $apiKey;
                break;
            default:
                // 默认Bearer Token认证
                $headers[] = 'Authorization: Bearer ' . $apiKey;
        }
        
        return $headers;
    }
    
    /**
     * 构建请求体
     * 根据 input_schema 进行参数类型转换和清理
     */
    protected function buildRequestBody($model, $inputParams)
    {
        $modelCode = $model['model_code'];
        
        // 检查是否是Seedance视频生成模型（需要特殊content数组格式）
        if ($this->isSeedanceVideoModel($modelCode)) {
            return $this->buildSeedanceRequestBody($modelCode, $inputParams);
        }
        
        $body = [
            'model' => $modelCode
        ];
        
        // 获取标准化后的 input_schema 用于类型转换
        $schema = $model['input_schema'] ?? [];
        $properties = $schema['properties'] ?? [];
        
        // 合并输入参数（含类型转换）
        foreach ($inputParams as $key => $value) {
            // 跳过空值参数
            if ($value === '' || $value === null) {
                continue;
            }
            
            // 根据 schema 定义进行类型转换
            $paramDef = $properties[$key] ?? null;
            $paramType = $paramDef['type'] ?? 'string';
            
            $converted = $this->convertParamValue($value, $paramType);
            
            // 枚举类型校验：若值不在 options 列表中，回退到 schema 默认值
            if ($paramDef && ($paramType === 'enum') && !empty($paramDef['options']) && is_array($paramDef['options'])) {
                if (!in_array($converted, $paramDef['options'], true) && !in_array((string)$converted, $paramDef['options'], true)) {
                    $fallback = $paramDef['default'] ?? null;
                    if ($fallback !== null) {
                        \think\facade\Log::info('buildRequestBody: 参数 ' . $key . ' 值 "' . $converted . '" 不在枚举选项中，回退到默认值 "' . $fallback . '"', [
                            'options' => $paramDef['options'],
                            'original_value' => $converted,
                            'default_value' => $fallback
                        ]);
                        $converted = $fallback;
                    }
                }
            }
            
            $body[$key] = $converted;
        }
        
        // 特殊处理：豆包SeeDream 的 image 参数
        // 如果 image 是单个URL字符串，保持原样；如果是逗号分隔的多图，转为数组
        if (isset($body['image']) && is_string($body['image']) && strpos($body['image'], ',') !== false) {
            $body['image'] = array_map('trim', explode(',', $body['image']));
        }
        
        // 特殊处理：sequential_image_generation_options
        // 如果有 image 参数（参考图），自动设置多图生成选项
        if (isset($body['image']) && !isset($body['sequential_image_generation_options'])) {
            $body['sequential_image_generation_options'] = ['max_images' => 1];
        }
        
        return $body;
    }
    
    /**
     * 检查是否为Seedance视频生成模型
     * Seedance模型使用特殊的content数组格式，而非扁平参数
     * 
     * @param string $modelCode 模型代码
     * @return bool
     */
    protected function isSeedanceVideoModel($modelCode)
    {
        // 去掉日期后缀进行匹配
        $baseCode = preg_replace('/-\d{6}$/', '', $modelCode);
        $baseCode = preg_replace('/-\d{8}$/', '', $baseCode);
        
        $seedanceModels = [
            'doubao-seedance-1-5-pro',
            'doubao-seedance-1-0-pro',
            'doubao-seedance-1-0-pro-fast',
            'doubao-seedance-1-0-lite-t2v',
            'doubao-seedance-1-0-lite-i2v',
            'doubao-seedance-2-0',
        ];
        
        return in_array($baseCode, $seedanceModels) || strpos($modelCode, 'doubao-seedance') === 0;
    }
    
    /**
     * 为Seedance视频模型构建符合火山方舟API格式的请求体
     * 
     * 火山方舟Seedance API要求格式:
     * {
     *   "model": "doubao-seedance-1-5-pro-251215",
     *   "content": [
     *     {"type": "text", "text": "描述文字  --duration 5 --resolution 1080P"},
     *     {"type": "image_url", "image_url": {"url": "https://..."}}
     *   ]
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数（扁平格式）
     * @return array 符合API格式的请求体
     */
    protected function buildSeedanceRequestBody($modelCode, $inputParams)
    {
        $content = [];
        
        // 1. 构建text元素（包含提示词 + 参数标志）
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? $inputParams['description'] ?? '';
        
        // 构建命令行标志
        $flags = [];
        if (!empty($inputParams['duration'])) {
            $flags[] = '--duration ' . intval($inputParams['duration']);
        }
        if (!empty($inputParams['resolution'])) {
            $resolution = $inputParams['resolution'];
            if (strtolower($resolution) === '1080p' || strtolower($resolution) === '720p') {
                $flags[] = '--resolution ' . strtoupper($resolution);
            }
        }
        if (isset($inputParams['watermark'])) {
            $wmVal = $inputParams['watermark'];
            if (is_string($wmVal)) {
                $wmVal = in_array(strtolower($wmVal), ['true', '1', 'yes', 'on']);
            }
            $flags[] = '--watermark ' . ($wmVal ? 'true' : 'false');
        }
        if (!empty($inputParams['camera_motion'])) {
            $flags[] = '--camerafixed ' . ($inputParams['camera_motion'] === 'static' ? 'true' : 'false');
        }
        
        // 拼接最终文本
        $text = trim($prompt);
        if (!empty($flags)) {
            $text .= '  ' . implode(' ', $flags);
        }
        
        $content[] = [
            'type' => 'text',
            'text' => $text
        ];
        
        // 2. 构廾image_url元素（如果有图像输入）
        $imageUrl = $inputParams['first_frame_image'] 
            ?? $inputParams['image_url'] 
            ?? $inputParams['image'] 
            ?? $inputParams['input_image'] 
            ?? '';
        
        if (!empty($imageUrl)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl
                ]
            ];
        }
        
        // 3. 尾帧图像（首尾帧模式）
        $lastFrameUrl = $inputParams['last_frame_image'] 
            ?? $inputParams['tail_image_url'] 
            ?? $inputParams['last_frame'] 
            ?? '';
        
        if (!empty($lastFrameUrl)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $lastFrameUrl
                ]
            ];
        }
        
        // 4. 参考图数组
        $refImages = $inputParams['reference_images'] ?? $inputParams['ref_images'] ?? [];
        if (!empty($refImages) && is_array($refImages)) {
            foreach ($refImages as $refUrl) {
                if (!empty($refUrl)) {
                    $content[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $refUrl
                        ]
                    ];
                }
            }
        }
        
        $body = [
            'model' => $modelCode,
            'content' => $content
        ];
        
        // seed参数作为独立字段
        if (isset($inputParams['seed'])) {
            $body['seed'] = intval($inputParams['seed']);
        }
        
        \think\facade\Log::info('buildSeedanceRequestBody: 构建Seedance请求体', [
            'model' => $modelCode,
            'content_count' => count($content),
            'has_image' => !empty($imageUrl),
            'flags' => $flags
        ]);
        
        return $body;
    }
    
    /**
     * 根据类型定义转换参数值
     */
    protected function convertParamValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                if (is_string($value)) {
                    return in_array(strtolower($value), ['true', '1', 'yes', 'on']) ? true : false;
                }
                return (bool)$value;
                
            case 'integer':
            case 'int':
                return intval($value);
                
            case 'number':
            case 'float':
            case 'double':
                return floatval($value);
                
            case 'object':
                // 对象类型：如果是JSON字符串则解析，否则返回空对象
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : null;
                }
                return is_array($value) ? $value : null;
                
            case 'array':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [$value];
                }
                return is_array($value) ? $value : [$value];
                
            case 'enum':
            case 'string':
            case 'mixed':
            default:
                return $value;
        }
    }
    
    /**
     * 发送HTTP请求
     */
    protected function sendHttpRequest($url, $method = 'POST', $headers = [], $data = null)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // 尝试解析JSON响应
        $body = json_decode($response, true);
        
        // 如果JSON解析失败，可能是SSE流式响应格式
        if (json_last_error() !== JSON_ERROR_NONE && is_string($response)) {
            $body = $this->parseSSEResponse($response);
        }
        
        return [
            'http_code' => $httpCode,
            'body' => $body ?: $response,
            'error' => $error
        ];
    }
    
    /**
     * 解析SSE (Server-Sent Events) 流式响应
     * 
     * 火山引擎豆包SeeDream实际SSE格式:
     * event: image_generation.partial_succeeded
     * data: {"type":"image_generation.partial_succeeded","model":"...","created":xxx,"image_index":0,"url":"...","size":"1664x2496"}
     *
     * event: image_generation.completed
     * data: {"type":"image_generation.completed","model":"...","created":xxx}
     *
     * 也兼容标准格式:
     * data: {"created":xxx,"data":[{"url":"..."}]}
     * data: [DONE]
     */
    protected function parseSSEResponse($response)
    {
        $trimmed = trim($response);
        
        // 检查是否是SSE格式（以 "data:" 或 "event:" 开头）
        if (strpos($trimmed, 'data:') !== 0 && strpos($trimmed, 'event:') !== 0) {
            return null;
        }
        
        $allData = [];
        $lines = explode("\n", $response);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // 跳过 event: 行（事件类型标记）
            if (strpos($line, 'event:') === 0) {
                continue;
            }
            
            // 跳过 [DONE] 标记
            if ($line === 'data: [DONE]' || $line === 'data:[DONE]' || $line === '[DONE]') {
                continue;
            }
            
            // 提取 data: 后面的JSON
            if (strpos($line, 'data:') === 0) {
                $jsonStr = trim(substr($line, 5)); // 去掉 "data:" 前缀
                if (!empty($jsonStr) && $jsonStr !== '[DONE]') {
                    $json = json_decode($jsonStr, true);
                    if ($json !== null) {
                        $allData[] = $json;
                    }
                }
            }
        }
        
        if (empty($allData)) {
            return null;
        }
        
        // 合并所有SSE数据块中的图像数据
        $mergedImages = [];
        $created = 0;
        
        foreach ($allData as $chunk) {
            if (isset($chunk['created'])) {
                $created = $chunk['created'];
            }
            
            // 格式1: 火山引擎 image_generation.partial_succeeded 事件
            // 每个chunk顶层直接包含 url, size, image_index 等字段
            if (isset($chunk['type']) && $chunk['type'] === 'image_generation.partial_succeeded') {
                $imageItem = [];
                if (!empty($chunk['url'])) {
                    $imageItem['url'] = $chunk['url'];
                }
                if (!empty($chunk['b64_json'])) {
                    $imageItem['b64_json'] = $chunk['b64_json'];
                }
                // 解析 size 字段（如 "1664x2496"）
                if (!empty($chunk['size']) && strpos($chunk['size'], 'x') !== false) {
                    list($w, $h) = explode('x', $chunk['size']);
                    $imageItem['width'] = intval($w);
                    $imageItem['height'] = intval($h);
                }
                $imageItem['image_index'] = $chunk['image_index'] ?? 0;
                if (!empty($imageItem['url']) || !empty($imageItem['b64_json'])) {
                    $mergedImages[] = $imageItem;
                }
                continue;
            }
            
            // 格式1b: completed 事件 - 跳过
            if (isset($chunk['type']) && $chunk['type'] === 'image_generation.completed') {
                continue;
            }
            
            // 格式2: 标准格式 {"created":xxx,"data":[{"url":"..."}]}
            if (isset($chunk['data']) && is_array($chunk['data'])) {
                foreach ($chunk['data'] as $item) {
                    if (isset($item['url']) || isset($item['b64_json'])) {
                        $mergedImages[] = $item;
                    }
                }
                continue;
            }
            
            // 格式3: 顶层直接包含 url（无 type 字段的简单格式）
            if (isset($chunk['url'])) {
                $mergedImages[] = $chunk;
            }
        }
        
        // 按 image_index 排序（如果有）
        if (!empty($mergedImages)) {
            usort($mergedImages, function($a, $b) {
                return ($a['image_index'] ?? 0) - ($b['image_index'] ?? 0);
            });
        }
        
        // 返回合并后的标准格式（与非流式响应格式一致）
        return [
            'created' => $created,
            'data' => $mergedImages
        ];
    }
    
    /**
     * 解析API响应
     */
    protected function parseApiResponse($model, $response)
    {
        // 通用响应解析逻辑
        $outputs = [];
        
        // 记录原始响应用于调试
        \think\facade\Log::info('模型API响应 model=' . $model['model_code'] 
            . ' response_type=' . gettype($response) 
            . ' response=' . (is_array($response) ? json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : substr((string)$response, 0, 2000)));
        
        // 检查是否返回错误
        if (isset($response['error'])) {
            return [
                'status' => 0,
                'error_code' => $response['error']['code'] ?? 'API_ERROR',
                'msg' => $response['error']['message'] ?? 'API返回错误'
            ];
        }
        
        // 豆包SeeDream / 火山引擎 图片生成响应格式
        // {"created": 1234, "data": [{"url": "...", "b64_json": "..."}]}
        if (isset($response['data']) && is_array($response['data'])) {
            // 如果 data 直接是数组（每个元素是图片对象）
            foreach ($response['data'] as $item) {
                if (isset($item['url']) || isset($item['b64_json'])) {
                    $output = ['type' => 'image'];
                    
                    // 优先使用URL
                    if (!empty($item['url'])) {
                        $output['url'] = $item['url'];
                    } elseif (!empty($item['b64_json'])) {
                        // Base64图片，需要保存到OSS
                        $output['url'] = $this->saveBase64Image($item['b64_json']);
                    }
                    
                    $output['thumbnail'] = $item['thumbnail'] ?? '';
                    $output['width'] = $item['width'] ?? 0;
                    $output['height'] = $item['height'] ?? 0;
                    
                    $outputs[] = $output;
                }
                // data.images 嵌套格式
                elseif (isset($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        $outputs[] = [
                            'type' => 'image',
                            'url' => $img['url'] ?? $img['image_url'] ?? '',
                            'thumbnail' => $img['thumbnail'] ?? '',
                            'width' => $img['width'] ?? 0,
                            'height' => $img['height'] ?? 0
                        ];
                    }
                }
            }
        }
        // data.images 格式
        elseif (isset($response['data']['images']) && is_array($response['data']['images'])) {
            foreach ($response['data']['images'] as $img) {
                $outputs[] = [
                    'type' => 'image',
                    'url' => $img['url'] ?? $img['image_url'] ?? '',
                    'thumbnail' => $img['thumbnail'] ?? '',
                    'width' => $img['width'] ?? 0,
                    'height' => $img['height'] ?? 0
                ];
            }
        }
        // 视频生成响应
        elseif (isset($response['data']['video_url'])) {
            $outputs[] = [
                'type' => 'video',
                'url' => $response['data']['video_url'],
                'thumbnail' => $response['data']['cover_url'] ?? '',
                'duration' => $response['data']['duration'] ?? 0
            ];
        }
        // 阿里云DashScope异步任务响应
        elseif (isset($response['output']['task_status'])) {
            $taskStatus = $response['output']['task_status'];
            $taskId = $response['output']['task_id'] ?? '';
            
            if ($taskStatus == 'SUCCEEDED') {
                // 任务已完成，解析结果
                if (isset($response['output']['results'])) {
                    foreach ($response['output']['results'] as $result) {
                        $outputs[] = [
                            'type' => 'image',
                            'url' => $result['url'] ?? ''
                        ];
                    }
                }
            } elseif (in_array($taskStatus, ['PENDING', 'RUNNING'])) {
                // 异步任务进行中，返回async标记，保存task_id供后续轮询
                \think\facade\Log::info('DashScope异步任务创建成功, external_task_id=' . $taskId . ' status=' . $taskStatus);
                return [
                    'status' => 1,
                    'async' => true,
                    'async_type' => 'dashscope',
                    'external_task_id' => $taskId,
                    'outputs' => [],
                    'tokens' => 0,
                    'cost' => 0
                ];
            } else {
                // FAILED 或其他失败状态
                return [
                    'status' => 0,
                    'error_code' => 'TASK_' . $taskStatus,
                    'msg' => $response['output']['message'] ?? '任务失败',
                    'task_status' => $taskStatus
                ];
            }
        }
        
        // 如果仍然没有解析到输出，尝试其他格式
        if (empty($outputs)) {
            if (isset($response['url'])) {
                $outputs[] = ['type' => 'image', 'url' => $response['url']];
            } elseif (isset($response['image_url'])) {
                $outputs[] = ['type' => 'image', 'url' => $response['image_url']];
            }
        }
        
        // Seedance异步视频生成任务响应: {"id": "cgt-xxx"}
        // API成功创建任务后仅返回任务ID，需要后续轮询获取结果
        if (empty($outputs) && isset($response['id']) && is_string($response['id'])) {
            $taskId = $response['id'];
            // cgt- 前缀的ID表示Seedance内容生成异步任务
            if (strpos($taskId, 'cgt-') === 0 || $this->isSeedanceVideoModel($model['model_code'])) {
                \think\facade\Log::info('Seedance异步任务创建成功, external_task_id=' . $taskId . ' model=' . $model['model_code']);
                return [
                    'status' => 1,
                    'async' => true,
                    'external_task_id' => $taskId,
                    'outputs' => [],
                    'tokens' => 0,
                    'cost' => 0
                ];
            }
        }
        
        // 如果还是没有输出，可能是未知的响应格式
        if (empty($outputs)) {
            \think\facade\Log::warning('API响应格式未识别 response_type=' . gettype($response) 
                . ' response=' . (is_array($response) ? json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : substr((string)$response, 0, 2000)));
            return [
                'status' => 0,
                'error_code' => 'UNKNOWN_RESPONSE',
                'msg' => 'API响应格式未识别，请检查日志'
            ];
        }
        
        return [
            'status' => 1,
            'outputs' => $outputs,
            'tokens' => $response['usage']['total_tokens'] ?? 0,
            'cost' => 0
        ];
    }
    
    /**
     * 保存Base64图片到OSS
     */
    protected function saveBase64Image($base64Data)
    {
        try {
            // 解码Base64
            $imageData = base64_decode($base64Data);
            if (!$imageData) {
                return '';
            }
            
            // 生成文件名
            $filename = 'generation/' . date('Ymd') . '/' . md5(uniqid()) . '.png';
            $localPath = ROOT_PATH . 'upload/' . $filename;
            
            // 确保目录存在
            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // 保存到本地
            file_put_contents($localPath, $imageData);
            
            // 上传到OSS
            $localUrl = PRE_URL . '/upload/' . $filename;
            $ossUrl = \app\common\Pic::uploadoss($localUrl);
            
            return $ossUrl ?: $localUrl;
        } catch (\Exception $e) {
            \think\facade\Log::error('Base64图片保存失败: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * 生成任务ID
     */
    protected function generateTaskId()
    {
        return 'GEN_' . date('YmdHis') . '_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
    
    /**
     * 标准化input_schema格式
     * 将 parameters 数组格式转换为 properties 对象格式（兼容JSON Schema）
     * @param array|null $schema
     * @return array|null
     */
    protected function normalizeInputSchema($schema)
    {
        if (empty($schema)) {
            return $schema;
        }
        
        // 已经是 properties 格式，直接返回
        if (isset($schema['properties'])) {
            return $schema;
        }
        
        // 将 parameters 数组转换为 properties 对象
        if (isset($schema['parameters']) && is_array($schema['parameters'])) {
            $properties = [];
            $required = [];
            $order = 0;
            
            foreach ($schema['parameters'] as $param) {
                $name = $param['name'] ?? '';
                if (empty($name)) {
                    continue;
                }
                
                $prop = $param;
                unset($prop['name']); // name 用作 key
                $prop['order'] = $order++;
                
                // 收集必填字段
                if (!empty($param['required'])) {
                    $required[] = $name;
                }
                unset($prop['required']); // 移到顶层 required 数组
                
                $properties[$name] = $prop;
            }
            
            $schema['properties'] = $properties;
            $schema['required'] = $required;
        }
        
        return $schema;
    }
    
    /**
     * 获取生成记录列表
     * 对于处理中的Seedance视频记录，会主动轮询外部API更新状态
     */
    public function getRecordList($where, $page = 1, $limit = 20, $order = 'id desc')
    {
        // 先轮询处理中的异步任务记录，更新其状态（支持Seedance视频和DashScope图片生成）
        $this->pollProcessingAsyncRecords($where);
        
        return GenerationRecord::getListWithModel($where, $page, $limit, $order);
    }
    
    /**
     * 轮询处理中的异步任务记录
     * 在记录列表查询前调用，主动更新处理中任务的状态
     * 支持: Seedance视频生成、DashScope图片生成
     * @param array $where 查询条件
     */
    protected function pollProcessingAsyncRecords($where)
    {
        try {
            // 查找处理中的记录（状态=1，且有task_id）
            $processingRecords = Db::name('generation_record')
                ->alias('r')
                ->leftJoin('model_info m', 'r.model_id = m.id')
                ->leftJoin('model_provider p', 'm.provider_id = p.id')
                ->field('r.id, r.task_id, r.model_id, r.model_code, r.start_time, p.provider_code')
                ->where($where)
                ->where('r.status', GenerationRecord::STATUS_PROCESSING)
                ->where('r.task_id', '<>', '')
                ->limit(10)  // 限制每次最多轮询10条，避免请求过多
                ->select()
                ->toArray();
            
            foreach ($processingRecords as $record) {
                $modelCode = $record['model_code'] ?? '';
                $providerCode = $record['provider_code'] ?? '';
                
                // 根据模型类型调用对应的轮询方法
                if ($this->isSeedanceVideoModel($modelCode)) {
                    // Seedance视频模型
                    $this->pollSeedanceTaskStatus($record);
                } elseif ($this->isDashScopeModel($providerCode)) {
                    // DashScope图片生成模型（阿里云通义万象等）
                    $this->pollDashScopeTaskStatus($record);
                }
                // 其他模型暂不支持异步轮询，跳过
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('pollProcessingAsyncRecords 异常: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查是否为DashScope模型（阿里云通义万象、百炼等）
     * @param string $providerCode 供应商代码
     * @return bool
     */
    protected function isDashScopeModel($providerCode)
    {
        return in_array($providerCode, ['aliyun', 'dashscope', 'alibaba']);
    }
    
    /**
     * 获取生成记录详情
     */
    public function getRecordDetail($recordId)
    {
        $record = Db::name('generation_record')->alias('r')
            ->leftJoin('model_info m', 'r.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->field('r.*, m.model_name, m.model_code as model_code_ref, p.provider_name')
            ->where('r.id', $recordId)
            ->find();
        
        if ($record) {
            $record['outputs'] = GenerationOutput::getByRecordId($recordId);
            $record['create_time_text'] = $record['create_time'] ? date('Y-m-d H:i:s', $record['create_time']) : '';
            $record['finish_time_text'] = $record['finish_time'] ? date('Y-m-d H:i:s', $record['finish_time']) : '';
            
            // 解析JSON
            if (is_string($record['input_params'])) {
                $record['input_params'] = json_decode($record['input_params'], true);
            }
        }
        
        return $record;
    }
    
    /**
     * 获取记录状态（用于前端轮询）
     * @param int $recordId
     * @return array
     */
    public function getRecordStatus($recordId)
    {
        $record = Db::name('generation_record')
            ->alias('r')
            ->leftJoin('model_info m', 'r.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->field('r.id, r.status, r.task_id, r.model_id, r.model_code, r.error_code, r.error_msg, r.cost_time, r.create_time, r.start_time, r.finish_time, p.provider_code')
            ->where('r.id', $recordId)
            ->find();
        
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        $progress = 0; // 进度百分比
        
        // 如果记录处于处理中状态，根据模型类型主动查询外部异步任务状态
        if ($record['status'] == GenerationRecord::STATUS_PROCESSING && !empty($record['task_id'])) {
            $modelCode = $record['model_code'] ?? '';
            $providerCode = $record['provider_code'] ?? '';
            
            if ($this->isSeedanceVideoModel($modelCode)) {
                // Seedance视频模型
                $pollResult = $this->pollSeedanceTaskStatus($record);
                $progress = $pollResult['progress'] ?? 0;
            } elseif ($this->isDashScopeModel($providerCode)) {
                // DashScope图片生成模型
                $pollResult = $this->pollDashScopeTaskStatus($record);
                $progress = $pollResult['progress'] ?? 0;
            }
            
            // 重新读取记录（状态可能已被更新）
            $record = Db::name('generation_record')
                ->field('id, status, task_id, model_id, model_code, error_code, error_msg, cost_time, create_time, start_time, finish_time')
                ->where('id', $recordId)
                ->find();
        }
        
        $statusMap = [
            0 => '待处理',
            1 => '处理中',
            2 => '成功',
            3 => '失败',
            4 => '已取消'
        ];
        
        $result = [
            'status' => 1,
            'data' => [
                'record_id' => $record['id'],
                'task_status' => intval($record['status']),
                'task_status_text' => $statusMap[$record['status']] ?? '未知',
                'error_code' => $record['error_code'] ?? '',
                'error_msg' => $record['error_msg'] ?? '',
                'cost_time' => $record['cost_time'] > 0 ? round($record['cost_time'] / 1000, 2) : 0,
                'create_time' => $record['create_time'] ? date('Y-m-d H:i:s', $record['create_time']) : '',
                'finish_time' => $record['finish_time'] ? date('Y-m-d H:i:s', $record['finish_time']) : '',
                'progress' => $progress // 进度百分比 0-100
            ]
        ];
        
        // 如果已完成，附带输出结果
        if ($record['status'] == 2) {
            $result['data']['outputs'] = GenerationOutput::getByRecordId($recordId);
        }
        
        return $result;
    }
    
    /**
     * 主动轮询Seedance异步任务状态
     * 当前端轮询get_record_status时，对处理中的Seedance任务查询火山方舟API获取最新状态
     * 
     * @param array $record 生成记录（数据库行）
     * @return array ['progress' => int] 返回进度信息
     */
    protected function pollSeedanceTaskStatus($record)
    {
        $externalTaskId = $record['task_id'];
        $pollResult = ['progress' => 0];
        
        // 获取模型信息以确定API Key
        $model = $this->getModelDetail($record['model_id']);
        if (!$model) {
            \think\facade\Log::warning('pollSeedanceTaskStatus: 模型不存在 model_id=' . $record['model_id']);
            return $pollResult;
        }
        
        $apiKeyConfig = $this->apiKeyService->getActiveConfigByProvider($model['provider_code']);
        if (!$apiKeyConfig) {
            \think\facade\Log::warning('pollSeedanceTaskStatus: API Key未配置 provider=' . $model['provider_code']);
            return $pollResult;
        }
        
        $apiKey = $apiKeyConfig['api_key_decrypted'];
        if (empty($apiKey)) {
            return $pollResult;
        }
        
        try {
            // 使用VolcengineVideoService查询任务状态
            $videoService = new VolcengineVideoService(0, $apiKey);
            $result = $videoService->queryTaskStatus($externalTaskId);
            
            \think\facade\Log::info('pollSeedanceTaskStatus 查询结果', [
                'record_id' => $record['id'],
                'external_task_id' => $externalTaskId,
                'success' => $result['success'] ?? false,
                'status' => $result['status'] ?? 'unknown',
                'progress' => $result['progress'] ?? 0
            ]);
            
            if (!$result['success']) {
                // 查询失败，保持当前状态，等下次轮询
                return $pollResult;
            }
            
            $status = $result['status'];
            $pollResult['progress'] = $result['progress'] ?? 0;
            
            if ($status === 'succeeded') {
                // 任务成功，保存视频输出
                $outputs = [];
                $videoUrl = $result['video_url'] ?? '';
                $audioUrl = $result['audio_url'] ?? '';
                
                \think\facade\Log::info('pollSeedanceTaskStatus succeeded，准备保存输出', [
                    'record_id' => $record['id'],
                    'video_url' => $videoUrl,
                    'audio_url' => $audioUrl,
                    'raw_response_keys' => array_keys($result)
                ]);
                
                if (!empty($videoUrl)) {
                    $outputs[] = [
                        'type' => 'video',
                        'url' => $videoUrl,
                        'thumbnail' => '',
                        'duration' => 0
                    ];
                }
                // 音频输出（有声视频模式）
                if (!empty($audioUrl)) {
                    $outputs[] = [
                        'type' => 'audio',
                        'url' => $audioUrl,
                        'thumbnail' => '',
                        'duration' => 0
                    ];
                }
                
                \think\facade\Log::info('pollSeedanceTaskStatus outputs数量', [
                    'record_id' => $record['id'],
                    'outputs_count' => count($outputs)
                ]);
                
                if (!empty($outputs)) {
                    GenerationOutput::createOutputs($record['id'], $outputs);
                    \think\facade\Log::info('pollSeedanceTaskStatus 已保存outputs', [
                        'record_id' => $record['id']
                    ]);
                } else {
                    \think\facade\Log::warning('pollSeedanceTaskStatus: 任务成功但未获取到视频URL', [
                        'record_id' => $record['id'],
                        'result_keys' => array_keys($result),
                        'raw_response' => $result['raw_response'] ?? []
                    ]);
                }
                
                // 计算耗时
                $costTime = ($record['start_time'] > 0) ? (time() - $record['start_time']) * 1000 : 0;
                
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markSuccess($costTime);
                }
                
                \think\facade\Log::info('Seedance视频生成成功', [
                    'record_id' => $record['id'],
                    'video_url' => $result['video_url'] ?? '',
                    'cost_time' => $costTime
                ]);
            } elseif ($status === 'failed') {
                // 任务失败
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed(
                        $result['error_code'] ?? 'SEEDANCE_FAILED',
                        $result['error_message'] ?? '视频生成失败'
                    );
                }
                
                \think\facade\Log::warning('Seedance视频生成失败', [
                    'record_id' => $record['id'],
                    'error_code' => $result['error_code'] ?? '',
                    'error_message' => $result['error_message'] ?? ''
                ]);
            } elseif ($status === 'cancelled') {
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed('TASK_CANCELLED', '视频生成任务已被取消');
                }
            }
            // else: still processing (running/pending), do nothing, wait for next poll
        } catch (\Exception $e) {
            \think\facade\Log::error('pollSeedanceTaskStatus 异常: ' . $e->getMessage(), [
                'record_id' => $record['id'],
                'external_task_id' => $externalTaskId
            ]);
        }
        
        return $pollResult;
    }
    
    /**
     * 主动轮询DashScope异步任务状态
     * 对处理中的阿里云/DashScope任务查询API获取最新状态
     * 支持通义万象wanx、qwen-image等DashScope异步模型
     * 
     * @param array $record 生成记录（数据库行）
     * @return array ['progress' => int] 返回进度信息
     */
    protected function pollDashScopeTaskStatus($record)
    {
        $externalTaskId = $record['task_id'];
        $pollResult = ['progress' => 0];
        
        // 获取模型信息以确定API Key
        $model = $this->getModelDetail($record['model_id']);
        if (!$model) {
            \think\facade\Log::warning('pollDashScopeTaskStatus: 模型不存在 model_id=' . $record['model_id']);
            return $pollResult;
        }
        
        $apiKeyConfig = $this->apiKeyService->getActiveConfigByProvider($model['provider_code']);
        if (!$apiKeyConfig) {
            \think\facade\Log::warning('pollDashScopeTaskStatus: API Key未配置 provider=' . $model['provider_code']);
            return $pollResult;
        }
        
        $apiKey = $apiKeyConfig['api_key_decrypted'];
        if (empty($apiKey)) {
            return $pollResult;
        }
        
        try {
            // 查询DashScope异步任务状态
            // API: GET https://dashscope.aliyuncs.com/api/v1/tasks/{task_id}
            $queryEndpoint = 'https://dashscope.aliyuncs.com/api/v1/tasks/' . $externalTaskId;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $queryEndpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            \think\facade\Log::info('pollDashScopeTaskStatus 查询结果', [
                'record_id' => $record['id'],
                'external_task_id' => $externalTaskId,
                'http_code' => $httpCode,
                'task_status' => $result['output']['task_status'] ?? 'unknown'
            ]);
            
            if ($httpCode != 200 || !isset($result['output']['task_status'])) {
                // 查询失败，保持当前状态，等下次轮询
                return $pollResult;
            }
            
            $taskStatus = $result['output']['task_status'];
            
            if ($taskStatus == 'SUCCEEDED') {
                // 任务成功，解析图片结果
                $outputs = [];
                if (isset($result['output']['results']) && is_array($result['output']['results'])) {
                    foreach ($result['output']['results'] as $item) {
                        if (!empty($item['url'])) {
                            $outputs[] = [
                                'type' => 'image',
                                'url' => $item['url'],
                                'thumbnail' => '',
                                'width' => 0,
                                'height' => 0
                            ];
                        } elseif (!empty($item['b64_image'])) {
                            // Base64图片，保存到OSS
                            $savedUrl = $this->saveBase64Image($item['b64_image']);
                            if (!empty($savedUrl)) {
                                $outputs[] = [
                                    'type' => 'image',
                                    'url' => $savedUrl,
                                    'thumbnail' => '',
                                    'width' => 0,
                                    'height' => 0
                                ];
                            }
                        }
                    }
                }
                
                if (!empty($outputs)) {
                    GenerationOutput::createOutputs($record['id'], $outputs);
                    \think\facade\Log::info('pollDashScopeTaskStatus 已保存outputs', [
                        'record_id' => $record['id'],
                        'outputs_count' => count($outputs)
                    ]);
                } else {
                    \think\facade\Log::warning('pollDashScopeTaskStatus: 任务成功但未获取到图片URL', [
                        'record_id' => $record['id'],
                        'output' => $result['output'] ?? []
                    ]);
                }
                
                // 计算耗时
                $costTime = ($record['start_time'] > 0) ? (time() - $record['start_time']) * 1000 : 0;
                
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markSuccess($costTime);
                }
                
                \think\facade\Log::info('DashScope图片生成成功', [
                    'record_id' => $record['id'],
                    'outputs_count' => count($outputs),
                    'cost_time' => $costTime
                ]);
            } elseif ($taskStatus == 'FAILED') {
                // 任务失败
                $errorCode = $result['output']['code'] ?? $result['code'] ?? 'DASHSCOPE_FAILED';
                $errorMsg = $result['output']['message'] ?? $result['message'] ?? '图片生成失败';
                
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed($errorCode, $errorMsg);
                }
                
                \think\facade\Log::warning('DashScope图片生成失败', [
                    'record_id' => $record['id'],
                    'error_code' => $errorCode,
                    'error_msg' => $errorMsg
                ]);
            } elseif ($taskStatus == 'CANCELED' || $taskStatus == 'CANCELLED') {
                // 任务取消
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed('TASK_CANCELLED', '图片生成任务已被取消');
                }
            }
            // else: PENDING 或 RUNNING，继续等待下次轮询
        } catch (\Exception $e) {
            \think\facade\Log::error('pollDashScopeTaskStatus 异常: ' . $e->getMessage(), [
                'record_id' => $record['id'],
                'external_task_id' => $externalTaskId
            ]);
        }
        
        return $pollResult;
    }
    
    /**
     * 重试生成任务
     */
    public function retryTask($recordId)
    {
        $record = GenerationRecord::find($recordId);
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        if ($record->status != GenerationRecord::STATUS_FAILED) {
            return ['status' => 0, 'msg' => '只能重试失败的任务'];
        }
        
        $record->incrementRetry();
        
        return $this->executeGeneration($recordId);
    }
    
    /**
     * 重新获取Seedance异步任务结果
     * 用于修复已标记成功但未正确保存输出的任务
     * @param int $recordId
     * @return array
     */
    public function refetchSeedanceResult($recordId)
    {
        $record = Db::name('generation_record')
            ->where('id', $recordId)
            ->find();
        
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        // 检查是否为Seedance模型
        if (!$this->isSeedanceVideoModel($record['model_code'] ?? '')) {
            return ['status' => 0, 'msg' => '该功能仅适用于Seedance视频生成任务'];
        }
        
        // 检查是否有外部任务ID
        $externalTaskId = $record['task_id'] ?? '';
        if (empty($externalTaskId) || strpos($externalTaskId, 'cgt-') !== 0) {
            return ['status' => 0, 'msg' => '找不到有效的Seedance任务ID'];
        }
        
        // 获取API Key
        $model = $this->getModelDetail($record['model_id']);
        if (!$model) {
            return ['status' => 0, 'msg' => '模型不存在'];
        }
        
        $apiKeyConfig = $this->apiKeyService->getActiveConfigByProvider($model['provider_code']);
        if (!$apiKeyConfig) {
            return ['status' => 0, 'msg' => 'API Key未配置'];
        }
        
        $apiKey = $apiKeyConfig['api_key_decrypted'];
        if (empty($apiKey)) {
            return ['status' => 0, 'msg' => 'API Key解密失败'];
        }
        
        try {
            // 查询Seedance任务状态
            $videoService = new VolcengineVideoService(0, $apiKey);
            $result = $videoService->queryTaskStatus($externalTaskId);
            
            \think\facade\Log::info('refetchSeedanceResult 查询结果', [
                'record_id' => $recordId,
                'external_task_id' => $externalTaskId,
                'result' => $result
            ]);
            
            if (!$result['success']) {
                return ['status' => 0, 'msg' => '查询Seedance API失败: ' . ($result['message'] ?? '未知错误')];
            }
            
            $status = $result['status'];
            
            if ($status === 'succeeded') {
                // 删除旧的输出记录（如果有）
                GenerationOutput::deleteByRecordId($recordId);
                
                // 保存新的输出
                $outputs = [];
                $videoUrl = $result['video_url'] ?? '';
                if (!empty($videoUrl)) {
                    $outputs[] = [
                        'type' => 'video',
                        'url' => $videoUrl,
                        'thumbnail' => '',
                        'duration' => 0
                    ];
                }
                $audioUrl = $result['audio_url'] ?? '';
                if (!empty($audioUrl)) {
                    $outputs[] = [
                        'type' => 'audio',
                        'url' => $audioUrl,
                        'thumbnail' => '',
                        'duration' => 0
                    ];
                }
                
                if (!empty($outputs)) {
                    GenerationOutput::createOutputs($recordId, $outputs);
                    return ['status' => 1, 'msg' => '成功获取到' . count($outputs) . '个输出结果', 'outputs' => $outputs];
                } else {
                    return ['status' => 0, 'msg' => '任务已完成但未找到视频URL', 'raw_response' => $result['raw_response'] ?? []];
                }
            } elseif ($status === 'running' || $status === 'pending') {
                // 任务还在进行中，将记录状态改回处理中
                if ($record['status'] != GenerationRecord::STATUS_PROCESSING) {
                    Db::name('generation_record')->where('id', $recordId)->update([
                        'status' => GenerationRecord::STATUS_PROCESSING
                    ]);
                }
                $progress = $result['progress'] ?? 0;
                return ['status' => 1, 'msg' => '任务仍在进行中（进度:' . $progress . '%），请稍后重试', 'progress' => $progress];
            } elseif ($status === 'failed') {
                $errorMsg = $result['error_message'] ?? '视频生成失败';
                return ['status' => 0, 'msg' => 'Seedance任务失败: ' . $errorMsg];
            } else {
                return ['status' => 0, 'msg' => '未知任务状态: ' . $status];
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('refetchSeedanceResult 异常: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '查询异常: ' . $e->getMessage()];
        }
    }
    
    /**
     * 取消生成任务
     */
    public function cancelTask($recordId)
    {
        $record = GenerationRecord::find($recordId);
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        if (!in_array($record->status, [GenerationRecord::STATUS_PENDING, GenerationRecord::STATUS_PROCESSING])) {
            return ['status' => 0, 'msg' => '只能取消待处理或处理中的任务'];
        }
        
        $record->status = GenerationRecord::STATUS_CANCELLED;
        $record->finish_time = time();
        $record->save();
        
        return ['status' => 1, 'msg' => '任务已取消'];
    }
    
    /**
     * 删除生成记录
     */
    public function deleteRecord($recordId)
    {
        $record = GenerationRecord::find($recordId);
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        // 删除关联输出
        GenerationOutput::deleteByRecordId($recordId);
        
        // 删除记录
        $record->delete();
        
        return ['status' => 1, 'msg' => '删除成功'];
    }
    
    /**
     * 将生成记录转为场景模板
     * 每条记录仅允许转换一次模板，使用事务保护防止并发重复创建
     * @param int $recordId 生成记录ID
     * @param array $templateData 模板数据，支持 prompt 字段用于覆盖提示词
     * @return array
     */
    public function convertToTemplate($recordId, $templateData)
    {
        $record = GenerationRecord::find($recordId);
        if (!$record) {
            return ['status' => 0, 'msg' => '记录不存在'];
        }
        
        if ($record->status != GenerationRecord::STATUS_SUCCESS) {
            return ['status' => 0, 'msg' => '只能将成功的记录转为模板'];
        }
        
        // 检查是否已经转换过模板（事务外的初步检查）
        $existingTemplate = Db::name('generation_scene_template')
            ->where('source_record_id', $recordId)
            ->find();
        if ($existingTemplate) {
            return ['status' => 0, 'msg' => '该记录已经转换过模板，不能重复转换'];
        }
        
        if (empty($templateData['template_name'])) {
            return ['status' => 0, 'msg' => '请填写模板名称'];
        }
        
        // 将表单提交的 prompt 合并到 templateData 中，供 createFromRecord 使用
        if (isset($templateData['prompt'])) {
            // prompt 会在 createFromRecord 中合并到 default_params
            // 这里保持原值传递
        }
        
        // 获取完整的记录详情（包含outputs）用于转存
        $recordDetail = $this->getRecordDetail($recordId);
        
        // 执行附件转存
        try {
            $transferResult = $this->transferAttachments($recordDetail, $templateData, $record->generation_type);
            $templateData = $transferResult['templateData'];
            // 如果有更新后的input_params，注入到$record对象以便createFromRecord使用
            if (!empty($transferResult['inputParams'])) {
                $record->input_params = $transferResult['inputParams'];
            }
            
            Log::info('转模板附件转存完成', [
                'record_id' => $recordId,
                'transfer_count' => count($transferResult['urlMapping'] ?? [])
            ]);
        } catch (\Exception $e) {
            // 转存失败不阻止模板创建，仅记录日志
            Log::warning('转模板附件转存失败，将使用原始URL', [
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
        }
        
        // 使用事务保护，防止并发请求导致重复创建
        Db::startTrans();
        try {
            // 事务内再次查询确认无关联模板（加锁查询）
            $existCheck = Db::name('generation_scene_template')
                ->where('source_record_id', $recordId)
                ->lock(true)
                ->find();
            if ($existCheck) {
                Db::rollback();
                return ['status' => 0, 'msg' => '该记录已经转换过模板，不能重复转换'];
            }
            
            $template = GenerationSceneTemplate::createFromRecord($record, $templateData);
            
            // 维护模板引用关联：将被引用的用户文件标记为不可删除
            try {
                $storageService = new StorageService();
                $refUrls = [];
                if (!empty($template->cover_image)) {
                    $refUrls[] = $template->cover_image;
                }
                $defaultParams = $template->default_params;
                if (is_string($defaultParams)) {
                    $defaultParams = json_decode($defaultParams, true) ?: [];
                }
                if (!empty($defaultParams['image_url'])) {
                    $refUrls[] = $defaultParams['image_url'];
                }
                if (!empty($defaultParams['ref_image'])) {
                    $refUrls[] = $defaultParams['ref_image'];
                }
                // 多张参考图
                if (!empty($defaultParams['ref_images']) && is_array($defaultParams['ref_images'])) {
                    $refUrls = array_merge($refUrls, $defaultParams['ref_images']);
                }
                $mid = 0;
                if ($record->order_id > 0) {
                    $orderRow = Db::name('generation_order')->where('id', $record->order_id)->field('mid')->find();
                    $mid = intval($orderRow['mid'] ?? 0);
                }
                if ($mid <= 0 && $record->uid > 0) {
                    $mid = $record->uid;
                }
                if (!empty($refUrls) && $mid > 0) {
                    $storageService->updateTemplateRefByUrls($template->id, array_unique($refUrls), $mid);
                }
            } catch (\Exception $e) {
                Log::warning('convertToTemplate 模板引用关联失败: ' . $e->getMessage());
            }
            
            Db::commit();
            
            // 视频类型模板：事务提交后自动生成Animated WebP动态封面
            if (intval($record->generation_type) == 2 && !empty($template->cover_image) && $this->isVideoUrl($template->cover_image)) {
                try {
                    $this->generateGifCover($template->id, $template->cover_image);
                    Log::info('convertToTemplate: 自动生成动态封面成功', ['template_id' => $template->id]);
                } catch (\Exception $e) {
                    Log::warning('convertToTemplate: 自动生成动态封面失败，可稍后在模板列表手动迁移', [
                        'template_id' => $template->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return [
                'status' => 1,
                'msg' => '模板创建成功',
                'template_id' => $template->id
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('convertToTemplate 异常: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '模板创建失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 转存模板相关的附件资源
     * 将生成结果（图片/视频）转存到系统配置的存储中
     * 
     * @param array $recordDetail 完整的记录详情（含outputs）
     * @param array $templateData 模板数据
     * @param int $generationType 生成类型 1=照片 2=视频
     * @return array ['templateData' => array, 'inputParams' => array, 'urlMapping' => array]
     */
    protected function transferAttachments($recordDetail, $templateData, $generationType)
    {
        $aid = $recordDetail['aid'] ?? (defined('aid') ? aid : 0);
        $transferService = new AttachmentTransferService($aid);
        
        // 如果cover_image为空，从第一个输出中获取URL作为封面
        if (empty($templateData['cover_image']) && !empty($recordDetail['outputs'])) {
            $firstOutput = $recordDetail['outputs'][0] ?? null;
            if ($firstOutput && !empty($firstOutput['output_url'])) {
                $templateData['cover_image'] = $firstOutput['output_url'];
            }
        }
        
        // 1. 提取需要转存的URL列表
        $urlList = $transferService->extractUrlsFromRecord($recordDetail, $templateData, $generationType);
        
        if (empty($urlList)) {
            Log::info('转模板无需转存的URL');
            return [
                'templateData' => $templateData,
                'inputParams' => $recordDetail['input_params'] ?? [],
                'urlMapping' => []
            ];
        }
        
        Log::info('转模板开始转存附件', [
            'record_id' => $recordDetail['id'] ?? 0,
            'url_count' => count($urlList),
            'urls' => array_map(function($item) { return ['key' => $item['key'], 'type' => $item['type'], 'url' => substr($item['url'], 0, 80)]; }, $urlList)
        ]);
        
        // 2. 执行批量转存
        $urlMapping = $transferService->transferBatch($urlList);
        
        // 3. 应用URL映射更新数据
        $inputParams = $recordDetail['input_params'] ?? [];
        list($newTemplateData, $newInputParams) = $transferService->applyUrlMapping(
            $templateData,
            $inputParams,
            $urlMapping
        );
        
        // 4. 记录转存结果统计
        $successCount = 0;
        $failCount = 0;
        foreach ($urlMapping as $key => $item) {
            if ($item['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        Log::info('转模板附件转存结果', [
            'record_id' => $recordDetail['id'] ?? 0,
            'success' => $successCount,
            'failed' => $failCount,
            'details' => array_map(function($item) { return ['success' => $item['success'], 'error' => $item['error'], 'transferred' => substr($item['transferred'] ?? '', 0, 80)]; }, $urlMapping)
        ]);
        
        // 5. 封面图压缩处理（仅对图片类型的封面执行）
        $coverUrl = $newTemplateData['cover_image'] ?? '';
        if (!empty($coverUrl) && $generationType != 2) {
            // 视频类型封面不压缩
            try {
                $compressedUrl = $this->compressCoverImage($coverUrl, $transferService);
                if ($compressedUrl) {
                    $newTemplateData['cover_image'] = $compressedUrl;
                    Log::info('封面图压缩成功', [
                        'record_id' => $recordDetail['id'] ?? 0,
                        'original' => substr($coverUrl, 0, 80),
                        'compressed' => substr($compressedUrl, 0, 80)
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('封面图压缩失败，使用原图', [
                    'record_id' => $recordDetail['id'] ?? 0,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'templateData' => $newTemplateData,
            'inputParams' => $newInputParams,
            'urlMapping' => $urlMapping
        ];
    }
    
    /**
     * 压缩封面图
     * 将超过 800px 宽度的图片缩放到 800px 宽，优先输出为 WebP 格式（质量85）
     * 若PHP不支持WebP则回退为JPEG输出
     * 
     * @param string $coverUrl 封面图URL（已转存到本地/OSS的URL）
     * @param AttachmentTransferService $transferService 转存服务实例
     * @return string|false 压缩后的新URL，或 false 表示无需压缩/压缩失败
     */
    protected function compressCoverImage($coverUrl, $transferService)
    {
        $maxWidth = 800;
        $quality = 85;
        $useWebp = function_exists('imagewebp');
        
        // 下载封面图到临时文件
        $tempDir = defined('ROOT_PATH') ? ROOT_PATH . 'runtime/temp/transfer/' : sys_get_temp_dir() . '/transfer/';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        
        $tempFile = $tempDir . 'cover_compress_' . md5($coverUrl . time()) . '.tmp';
        
        // 使用 CURL 下载
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $coverUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($content === false || $httpCode != 200 || empty($content)) {
            Log::warning('compressCoverImage: 下载封面失败', ['url' => substr($coverUrl, 0, 80), 'httpCode' => $httpCode]);
            return false;
        }
        
        @file_put_contents($tempFile, $content);
        
        // 获取图片信息
        $imageInfo = @getimagesize($tempFile);
        if (!$imageInfo) {
            @unlink($tempFile);
            Log::info('compressCoverImage: 非图片文件或无法读取图片信息，跳过压缩');
            return false;
        }
        
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // 宽度不超过阈值，无需压缩
        if ($srcWidth <= $maxWidth) {
            @unlink($tempFile);
            Log::info('compressCoverImage: 图片宽度未超过阈值，跳过压缩', ['width' => $srcWidth, 'maxWidth' => $maxWidth]);
            return false;
        }
        
        // 创建源图像
        $srcImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($tempFile);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($tempFile);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImage = @imagecreatefromwebp($tempFile);
                }
                break;
            case 'image/gif':
                $srcImage = @imagecreatefromgif($tempFile);
                break;
        }
        
        if (!$srcImage) {
            @unlink($tempFile);
            Log::warning('compressCoverImage: 无法创建源图像', ['mimeType' => $mimeType]);
            return false;
        }
        
        // 按比例计算目标尺寸
        $newWidth = $maxWidth;
        $newHeight = intval($srcHeight * $maxWidth / $srcWidth);
        
        // 创建缩略图
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // 保持透明度（PNG/GIF/WebP）
        if (in_array($mimeType, ['image/png', 'image/gif', 'image/webp'])) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($thumbnail, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
        
        // 输出为 WebP 或 JPEG
        $outputExt = $useWebp ? 'webp' : 'jpg';
        $compressedFile = $tempDir . 'cover_compressed_' . md5($coverUrl . time() . mt_rand()) . '.' . $outputExt;
        
        if ($useWebp) {
            imagewebp($thumbnail, $compressedFile, $quality);
        } else {
            imagejpeg($thumbnail, $compressedFile, $quality);
        }
        
        // 释放资源
        imagedestroy($srcImage);
        imagedestroy($thumbnail);
        @unlink($tempFile);
        
        if (!file_exists($compressedFile) || filesize($compressedFile) === 0) {
            Log::warning('compressCoverImage: 压缩后文件无效');
            return false;
        }
        
        Log::info('compressCoverImage: 压缩完成', [
            'format' => $outputExt,
            'originalSize' => strlen($content),
            'compressedSize' => filesize($compressedFile),
            'originalDimensions' => $srcWidth . 'x' . $srcHeight,
            'newDimensions' => $newWidth . 'x' . $newHeight
        ]);
        
        // 上传压缩后的图片到存储
        try {
            // 使用反射调用 uploadToStorage 方法（protected方法）
            $reflection = new \ReflectionMethod($transferService, 'uploadToStorage');
            $reflection->setAccessible(true);
            $newUrl = $reflection->invoke($transferService, $compressedFile, '', $outputExt);
            
            @unlink($compressedFile);
            
            if ($newUrl) {
                return $newUrl;
            }
        } catch (\Exception $e) {
            @unlink($compressedFile);
            Log::warning('compressCoverImage: 上传压缩图失败', ['error' => $e->getMessage()]);
        }
        
        return false;
    }
    
    /**
     * 公开的封面图压缩方法（供控制器调用）
     * @param string $coverUrl 封面图URL
     * @param AttachmentTransferService $transferService 转存服务实例
     * @return string|false 压缩后的新URL，或 false
     */
    public function compressCoverImagePublic($coverUrl, $transferService)
    {
        return $this->compressCoverImage($coverUrl, $transferService);
    }
    
    /**
     * 获取场景模板列表
     */
    public function getTemplateList($where, $page = 1, $limit = 20, $order = 'sort asc, id desc')
    {
        return GenerationSceneTemplate::getListWithModel($where, $page, $limit, $order);
    }
    
    /**
     * 获取场景模板详情
     */
    public function getTemplateDetail($templateId)
    {
        return GenerationSceneTemplate::getDetailWithModel($templateId);
    }
    
    /**
     * 根据用户会员等级计算场景模板的实际价格
     * @param array $template 模板数据
     * @param int $memberLevelId 用户会员等级ID，0表示游客/未登录
     * @return array 包含价格信息的数组
     */
    public function calculateTemplatePrice($template, $memberLevelId = 0)
    {
        $basePrice = floatval($template['base_price'] ?? 0);
        $priceUnit = $template['price_unit'] ?? 'per_image';
        $lvprice = intval($template['lvprice'] ?? 0);
        
        $priceUnitTextMap = [
            'per_image' => '元/张',
            'per_second' => '元/秒'
        ];
        $priceUnitText = $priceUnitTextMap[$priceUnit] ?? '元/张';
        
        $result = [
            'price' => $basePrice,
            'base_price' => $basePrice,
            'price_unit' => $priceUnit,
            'price_unit_text' => $priceUnitText,
            'is_member_price' => false,
            'member_level_id' => $memberLevelId
        ];
        
        // 如果开启了会员价且用户已登录
        if ($lvprice == 1 && $memberLevelId > 0) {
            $lvpriceData = $template['lvprice_data'] ?? '';
            if (is_string($lvpriceData)) {
                $lvpriceData = json_decode($lvpriceData, true) ?: [];
            }
            if (is_array($lvpriceData) && isset($lvpriceData[$memberLevelId])) {
                $result['price'] = floatval($lvpriceData[$memberLevelId]);
                $result['is_member_price'] = true;
            }
        }
        
        return $result;
    }
    
    /**
     * 获取场景模板列表（含价格信息，前端API用）
     * @param int $aid 账户ID
     * @param int $bid 商户ID
     * @param int $generationType 生成类型 1=图片 2=视频
     * @param int $memberLevelId 用户会员等级ID
     * @param array $extraWhere 额外查询条件
     * @return array
     */
    public function getTemplateListWithPrice($aid, $bid, $generationType, $memberLevelId = 0, $extraWhere = [])
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['generation_type', '=', $generationType],
            ['status', '=', 1]
        ];
        
        if (!empty($extraWhere)) {
            $where = array_merge($where, $extraWhere);
        }
        
        $list = Db::name('generation_scene_template')
            ->where($where)
            ->order('sort asc, id desc')
            ->select()->toArray();
        
        foreach ($list as &$item) {
            $priceInfo = $this->calculateTemplatePrice($item, $memberLevelId);
            $item['price'] = $priceInfo['price'];
            $item['base_price'] = $priceInfo['base_price'];
            $item['price_unit'] = $priceInfo['price_unit'];
            $item['price_unit_text'] = $priceInfo['price_unit_text'];
            $item['is_member_price'] = $priceInfo['is_member_price'];
            
            // 获取所有等级价格（可选，用于展示价格对比）
            if ($item['lvprice'] == 1) {
                $lvpriceData = $item['lvprice_data'] ?? '';
                if (is_string($lvpriceData)) {
                    $lvpriceData = json_decode($lvpriceData, true) ?: [];
                }
                $item['all_prices'] = $lvpriceData;
            } else {
                $item['all_prices'] = [];
            }
            
            // 清理不需要返回给前端的字段
            unset($item['lvprice_data'], $item['default_params'], $item['param_schema']);
            
            // 确保 use_count 和 output_quantity 字段存在
            $item['use_count'] = intval($item['use_count'] ?? 0);
            $item['output_quantity'] = intval($item['output_quantity'] ?? 1);
            
            // 新增字段：提示词可见性、证件照模式
            $item['prompt_visible'] = intval($item['prompt_visible'] ?? 1);
            $item['is_id_photo'] = intval($item['is_id_photo'] ?? 0);
            $item['id_photo_type'] = intval($item['id_photo_type'] ?? 0);
        }
        
        return $list;
    }
    
    /**
     * 保存场景模板
     */
    public function saveTemplate($data)
    {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        
        if (empty($data['template_name'])) {
            return ['status' => 0, 'msg' => '请填写模板名称'];
        }
        
        if (empty($data['model_id'])) {
            return ['status' => 0, 'msg' => '请选择模型'];
        }
        
        // 校验 use_count
        $useCount = isset($data['use_count']) ? intval($data['use_count']) : 0;
        if ($useCount < 0) {
            return ['status' => 0, 'msg' => '使用次数必须为非负整数'];
        }
        
        // 校验 output_quantity 与模型能力
        $outputQuantity = isset($data['output_quantity']) ? intval($data['output_quantity']) : 1;
        if ($outputQuantity < 1) {
            return ['status' => 0, 'msg' => '每单输出数量必须≥1'];
        }
        
        $modelMaxOutput = $this->getModelMaxOutput(intval($data['model_id']));
        if ($modelMaxOutput > 0 && $outputQuantity > $modelMaxOutput) {
            return ['status' => 0, 'msg' => '每单输出数量不能超过绑定模型的能力上限(最大' . $modelMaxOutput . ')'];
        }
        
        // 校验 prompt_visible（仅接受 0 或 1）
        $promptVisible = isset($data['prompt_visible']) ? intval($data['prompt_visible']) : 1;
        if (!in_array($promptVisible, [0, 1])) {
            $promptVisible = 1;
        }
        
        // 校验 is_id_photo 和 id_photo_type
        $isIdPhoto = isset($data['is_id_photo']) ? intval($data['is_id_photo']) : 0;
        if (!in_array($isIdPhoto, [0, 1])) {
            $isIdPhoto = 0;
        }
        $idPhotoType = 0;
        if ($isIdPhoto == 1) {
            $idPhotoType = isset($data['id_photo_type']) ? intval($data['id_photo_type']) : 0;
            if (!in_array($idPhotoType, [1, 2, 3, 4, 5])) {
                return ['status' => 0, 'msg' => '请选择证件照类型'];
            }
        }
        
        $saveData = [
            'template_name' => $data['template_name'],
            'template_code' => $data['template_code'] ?? GenerationSceneTemplate::generateCode(),
            'category' => $data['category'] ?? '',
            'category_ids' => $data['category_ids'] ?? '',
            'group_ids' => $data['group_ids'] ?? '',
            'mdid' => intval($data['mdid'] ?? 0),
            'cover_image' => $data['cover_image'] ?? '',
            'description' => $data['description'] ?? '',
            'model_id' => intval($data['model_id']),
            'default_params' => isset($data['default_params']) ? (is_string($data['default_params']) ? $data['default_params'] : json_encode($data['default_params'], JSON_UNESCAPED_UNICODE)) : '{}',
            'is_public' => intval($data['is_public'] ?? 0),
            'status' => intval($data['status'] ?? 1),
            'sort' => intval($data['sort'] ?? 0),
            'use_count' => $useCount,
            'output_quantity' => $outputQuantity,
            'prompt_visible' => $promptVisible,
            'is_id_photo' => $isIdPhoto,
            'id_photo_type' => $idPhotoType,
            'base_price' => isset($data['base_price']) ? round(floatval($data['base_price']), 2) : 0.00,
            'price_unit' => $data['price_unit'] ?? 'per_image',
            'lvprice' => intval($data['lvprice'] ?? 0),
            'lvprice_data' => $data['lvprice_data'] ?? '',
            'update_time' => time()
        ];
        
        if ($id > 0) {
            Db::name('generation_scene_template')->where('id', $id)->update($saveData);
        } else {
            $saveData['aid'] = $data['aid'] ?? 0;
            $saveData['bid'] = $data['bid'] ?? 0;
            $saveData['generation_type'] = $data['generation_type'];
            $saveData['source_record_id'] = $data['source_record_id'] ?? 0;
            $saveData['create_time'] = time();
            $id = Db::name('generation_scene_template')->insertGetId($saveData);
        }
        
        // 视频类型模板：自动将视频封面转换为GIF（异步不阻塞保存）
        $genType = $data['generation_type'] ?? ($saveData['generation_type'] ?? 0);
        $coverUrl = $saveData['cover_image'] ?? '';
        if (intval($genType) == 2 && !empty($coverUrl) && $this->isVideoUrl($coverUrl)) {
            try {
                $this->generateGifCover($id, $coverUrl);
            } catch (\Exception $e) {
                Log::warning('saveTemplate: 自动生成动态封面失败', [
                    'template_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }
    
    /**
     * 获取模型的最大输出数量能力
     * 从 model_info 表的 capability_tags 或 input_schema 中解析
     * @param int $modelId 模型ID
     * @return int 最大输出数量，0表示未限制或无法解析
     */
    public function getModelMaxOutput($modelId)
    {
        if (!$modelId) {
            return 0;
        }
        
        $model = Db::name('model_info')
            ->where('id', $modelId)
            ->field('id, capability_tags, input_schema')
            ->find();
        
        if (!$model) {
            return 0;
        }
        
        // 1. 从 capability_tags 解析
        $capabilityTags = $model['capability_tags'] ?? '';
        if (is_string($capabilityTags)) {
            $capabilityTags = json_decode($capabilityTags, true) ?: [];
        }
        
        // 检查是否支持批量生成
        $hasBatchGeneration = false;
        foreach ($capabilityTags as $tag) {
            if (in_array($tag, ['batch_generation', '多图生成', '流式输出'])) {
                $hasBatchGeneration = true;
                break;
            }
        }
        
        if (!$hasBatchGeneration) {
            // 不支持批量生成，output_quantity 固定为 1
            return 1;
        }
        
        // 2. 从 input_schema 解析 n 参数的 max 值
        $inputSchema = $model['input_schema'] ?? '';
        if (is_string($inputSchema)) {
            $inputSchema = json_decode($inputSchema, true) ?: [];
        }
        
        // 标准化 schema
        $inputSchema = $this->normalizeInputSchema($inputSchema);
        
        // 查找 n 参数或 max_images 参数
        $properties = $inputSchema['properties'] ?? [];
        
        // 检查 n 参数
        if (isset($properties['n'])) {
            $nParam = $properties['n'];
            if (isset($nParam['max'])) {
                return intval($nParam['max']);
            }
            if (isset($nParam['maximum'])) {
                return intval($nParam['maximum']);
            }
            // 如果有 range 定义
            if (isset($nParam['range']) && isset($nParam['range']['max'])) {
                return intval($nParam['range']['max']);
            }
        }
        
        // 检查 max_images 参数
        if (isset($properties['max_images'])) {
            $maxImagesParam = $properties['max_images'];
            if (isset($maxImagesParam['max'])) {
                return intval($maxImagesParam['max']);
            }
            if (isset($maxImagesParam['maximum'])) {
                return intval($maxImagesParam['maximum']);
            }
        }
        
        // 默认：支持批量生成但未指定上限，返回较大默认值
        return 10;
    }
    
    /**
     * 增加模板使用计数
     * 每次模板被调用生成任务后自动+1
     * @param int $templateId 模板ID
     * @return bool
     */
    public function incrementTemplateUsage($templateId)
    {
        if (!$templateId) {
            return false;
        }
        
        try {
            Db::name('generation_scene_template')
                ->where('id', $templateId)
                ->inc('use_count', 1)
                ->update();
            return true;
        } catch (\Exception $e) {
            Log::error('incrementTemplateUsage 失败: ' . $e->getMessage(), [
                'template_id' => $templateId
            ]);
            return false;
        }
    }
    
    /**
     * 删除场景模板
     */
    public function deleteTemplate($templateId)
    {
        $template = GenerationSceneTemplate::find($templateId);
        if (!$template) {
            return ['status' => 0, 'msg' => '模板不存在'];
        }
        
        // 移除模板引用关联
        try {
            $storageService = new StorageService();
            $storageService->removeTemplateRefs($templateId);
        } catch (\Exception $e) {
            Log::warning('deleteTemplate 移除模板引用失败: ' . $e->getMessage());
        }
        
        $template->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }
    
    /**
     * 检查URL是否为视频文件
     * @param string $url
     * @return bool
     */
    public function isVideoUrl($url)
    {
        if (empty($url)) return false;
        $parsed = parse_url(strtolower($url), PHP_URL_PATH);
        if (!$parsed) return false;
        $ext = pathinfo($parsed, PATHINFO_EXTENSION);
        return in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'flv', 'm4v']);
    }
    
    /**
     * 将视频封面转换为动态预览图（Animated WebP优先，GIF回退）并存储
     * 使用FFmpeg提取视频前30帧，缩放到300px宽，10fps，生成优化的Animated WebP
     * 若FFmpeg不支持libwebp_anim编码器，则回退为GIF方案
     * 
     * @param int $templateId 模板ID
     * @param string $videoUrl 视频URL
     * @return string|false 动态封面URL，失败返回false
     */
    public function generateGifCover($templateId, $videoUrl)
    {
        // 检查 ffmpeg 是否可用
        $ffmpegPath = $this->findFfmpeg();
        if (!$ffmpegPath) {
            Log::warning('generateGifCover: FFmpeg未安装，无法生成动态封面');
            return false;
        }
        
        // 检测 libwebp_anim 编码器是否可用
        $useWebp = $this->checkFfmpegWebpSupport($ffmpegPath);
        
        $tempDir = defined('ROOT_PATH') ? ROOT_PATH . 'runtime/temp/gif/' : sys_get_temp_dir() . '/gif/';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        
        $uniqueId = md5($videoUrl . $templateId . time() . mt_rand());
        $tempVideo = $tempDir . 'src_' . $uniqueId . '.mp4';
        $tempPalette = $tempDir . 'palette_' . $uniqueId . '.png';
        // 根据编码器支持选择输出格式
        $outputExt = $useWebp ? '.webp' : '.gif';
        $tempOutput = $tempDir . 'cover_' . $uniqueId . $outputExt;
        
        try {
            // 1. 下载视频到临时文件
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $videoUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $videoContent = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($videoContent === false || $httpCode != 200 || empty($videoContent)) {
                Log::warning('generateGifCover: 视频下载失败', ['url' => substr($videoUrl, 0, 100), 'httpCode' => $httpCode]);
                return false;
            }
            
            @file_put_contents($tempVideo, $videoContent);
            unset($videoContent); // 释放内存
            
            if (!file_exists($tempVideo) || filesize($tempVideo) < 1000) {
                Log::warning('generateGifCover: 视频文件过小或不存在');
                @unlink($tempVideo);
                return false;
            }
            
            $encodeOutput = [];
            $encodeRetCode = 1;
            
            if ($useWebp) {
                // === Animated WebP 方案 ===
                // 直接使用 libwebp_anim 编码器，无需调色板步骤
                $webpCmd = sprintf(
                    '%s -i %s -vf "fps=10,scale=300:-1:flags=lanczos" -vcodec libwebp_anim -quality 75 -lossless 0 -frames:v 30 -loop 0 -an -y %s 2>&1',
                    escapeshellarg($ffmpegPath),
                    escapeshellarg($tempVideo),
                    escapeshellarg($tempOutput)
                );
                exec($webpCmd, $encodeOutput, $encodeRetCode);
                
                // WebP 生成失败时回退到 GIF 方案
                if ($encodeRetCode !== 0 || !file_exists($tempOutput) || filesize($tempOutput) < 100) {
                    Log::warning('generateGifCover: WebP生成失败，回退到GIF方案', [
                        'returnCode' => $encodeRetCode,
                        'output' => implode("\n", array_slice($encodeOutput, -5))
                    ]);
                    @unlink($tempOutput);
                    $useWebp = false;
                    $outputExt = '.gif';
                    $tempOutput = $tempDir . 'cover_' . $uniqueId . $outputExt;
                    $encodeOutput = [];
                    $encodeRetCode = 1;
                }
            }
            
            if (!$useWebp) {
                // === GIF 回退方案（原有逻辑） ===
                // 生成调色板（优化GIF色彩质量）
                $paletteCmd = sprintf(
                    '%s -i %s -vf "fps=10,scale=300:-1:flags=lanczos,palettegen=max_colors=128" -frames:v 1 -y %s 2>&1',
                    escapeshellarg($ffmpegPath),
                    escapeshellarg($tempVideo),
                    escapeshellarg($tempPalette)
                );
                exec($paletteCmd, $paletteOutput, $paletteRetCode);
                
                // 用调色板生成高质量GIF（前30帧，10fps，宽300px）
                if ($paletteRetCode === 0 && file_exists($tempPalette)) {
                    $gifCmd = sprintf(
                        '%s -i %s -i %s -lavfi "fps=10,scale=300:-1:flags=lanczos[x];[x][1:v]paletteuse=dither=bayer:bayer_scale=3" -frames:v 30 -loop 0 -y %s 2>&1',
                        escapeshellarg($ffmpegPath),
                        escapeshellarg($tempVideo),
                        escapeshellarg($tempPalette),
                        escapeshellarg($tempOutput)
                    );
                } else {
                    // 调色板失败，直接生成GIF（质量稍差）
                    $gifCmd = sprintf(
                        '%s -i %s -vf "fps=10,scale=300:-1:flags=lanczos" -frames:v 30 -loop 0 -y %s 2>&1',
                        escapeshellarg($ffmpegPath),
                        escapeshellarg($tempVideo),
                        escapeshellarg($tempOutput)
                    );
                }
                
                exec($gifCmd, $encodeOutput, $encodeRetCode);
            }
            
            if ($encodeRetCode !== 0 || !file_exists($tempOutput) || filesize($tempOutput) < 100) {
                Log::warning('generateGifCover: FFmpeg生成动态封面失败', [
                    'returnCode' => $encodeRetCode,
                    'format' => $useWebp ? 'webp' : 'gif',
                    'output' => implode("\n", array_slice($encodeOutput, -5))
                ]);
                @unlink($tempVideo);
                @unlink($tempPalette);
                @unlink($tempOutput);
                return false;
            }
            
            // 4. 上传动态封面到云存储
            $aid = Db::name('generation_scene_template')->where('id', $templateId)->value('aid') ?: (defined('aid') ? aid : 0);
            
            // 复制到upload目录
            $uploadDir = 'upload/generation_template/' . $aid . '/' . date('Ym') . '/';
            $localDir = defined('ROOT_PATH') ? ROOT_PATH . $uploadDir : $uploadDir;
            if (!is_dir($localDir)) {
                @mkdir($localDir, 0777, true);
            }
            
            $coverFilename = ($useWebp ? 'webp_cover_' : 'gif_cover_') . $uniqueId . $outputExt;
            $localPath = $localDir . $coverFilename;
            @copy($tempOutput, $localPath);
            
            // 上传到OSS/COS
            $coverUrl = '';
            if (defined('PRE_URL')) {
                $localUrl = PRE_URL . '/' . $uploadDir . $coverFilename;
                $coverUrl = \app\common\Pic::uploadoss($localUrl);
                if ($coverUrl === false) {
                    $coverUrl = $localUrl; // OSS失败时使用本地URL
                }
            } else {
                $coverUrl = '/' . $uploadDir . $coverFilename;
            }
            
            // 5. 更新数据库
            if (!empty($coverUrl)) {
                Db::name('generation_scene_template')->where('id', $templateId)->update([
                    'gif_cover' => $coverUrl,
                    'update_time' => time()
                ]);
                
                Log::info('generateGifCover: 动态封面生成成功', [
                    'template_id' => $templateId,
                    'format' => $useWebp ? 'webp' : 'gif',
                    'file_size' => filesize($tempOutput),
                    'cover_url' => substr($coverUrl, 0, 100)
                ]);
            }
            
            // 6. 清理临时文件
            @unlink($tempVideo);
            @unlink($tempPalette);
            @unlink($tempOutput);
            
            return $coverUrl;
            
        } catch (\Exception $e) {
            @unlink($tempVideo);
            @unlink($tempPalette);
            @unlink($tempOutput);
            Log::error('generateGifCover 异常: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 批量为已有视频模板生成动态封面（gif_cover为空的记录）
     * 用于迁移已有数据
     * @param int $limit 每次处理数量
     * @return array ['processed' => int, 'success' => int, 'failed' => int]
     */
    public function batchGenerateGifCovers($limit = 10)
    {
        // 查找视频类型模板，cover_image为视频且gif_cover为空
        $templates = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', '')
            ->where('cover_image', '<>', '')
            ->limit($limit)
            ->select()
            ->toArray();
        
        $processed = 0;
        $success = 0;
        $failed = 0;
        
        foreach ($templates as $tpl) {
            if (!$this->isVideoUrl($tpl['cover_image'])) {
                continue;
            }
            $processed++;
            try {
                $result = $this->generateGifCover($tpl['id'], $tpl['cover_image']);
                if ($result) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::warning('batchGenerateGifCovers: 模板' . $tpl['id'] . '失败: ' . $e->getMessage());
            }
        }
        
        return ['processed' => $processed, 'success' => $success, 'failed' => $failed];
    }
    
    /**
     * 批量将已有GIF动态封面迁移为WebP格式
     * 查找gif_cover字段以.gif结尾的视频模板记录，逐条重新生成WebP版本
     * @param int $limit 每批处理数量（默认10条，避免FFmpeg并发过高）
     * @return array ['processed' => int, 'success' => int, 'failed' => int, 'skipped' => int]
     */
    public function batchMigrateToWebpCovers($limit = 10)
    {
        // 查找gif_cover以.gif结尾的视频模板
        $templates = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', 'like', '%.gif')
            ->where('cover_image', '<>', '')
            ->limit($limit)
            ->select()
            ->toArray();
        
        $processed = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($templates as $tpl) {
            if (!$this->isVideoUrl($tpl['cover_image'])) {
                $skipped++;
                continue;
            }
            $processed++;
            try {
                // 调用优化后的generateGifCover，会自动生成WebP
                $result = $this->generateGifCover($tpl['id'], $tpl['cover_image']);
                if ($result) {
                    $success++;
                    Log::info('batchMigrateToWebpCovers: 模板' . $tpl['id'] . '迁移成功', [
                        'old_gif' => substr($tpl['gif_cover'], 0, 80),
                        'new_url' => substr($result, 0, 80)
                    ]);
                } else {
                    $failed++;
                    Log::warning('batchMigrateToWebpCovers: 模板' . $tpl['id'] . '生成失败，保留原GIF');
                }
            } catch (\Exception $e) {
                $failed++;
                Log::warning('batchMigrateToWebpCovers: 模板' . $tpl['id'] . '异常: ' . $e->getMessage());
            }
        }
        
        Log::info('batchMigrateToWebpCovers: 批量迁移完成', [
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped
        ]);
        
        return ['processed' => $processed, 'success' => $success, 'failed' => $failed, 'skipped' => $skipped];
    }
    
    /**
     * 检测FFmpeg是否支持libwebp_anim编码器
     * @param string $ffmpegPath FFmpeg可执行文件路径
     * @return bool
     */
    protected function checkFfmpegWebpSupport($ffmpegPath)
    {
        static $supported = null;
        if ($supported !== null) {
            return $supported;
        }
        
        $output = [];
        exec(escapeshellarg($ffmpegPath) . ' -encoders 2>/dev/null | grep libwebp_anim', $output, $retCode);
        $supported = ($retCode === 0 && !empty($output));
        
        if (!$supported) {
            Log::warning('generateGifCover: FFmpeg不支持libwebp_anim编码器，将使用GIF方案');
        }
        
        return $supported;
    }
    
    /**
     * 查找FFmpeg可执行文件路径
     * @return string|false
     */
    protected function findFfmpeg()
    {
        // 尝试常见路径
        $paths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/bin/ffmpeg'];
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // 尝试 which 命令
        $output = [];
        exec('which ffmpeg 2>/dev/null', $output, $retCode);
        if ($retCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        
        return false;
    }
    
    /**
     * 更新模板状态
     */
    public function updateTemplateStatus($templateId, $status)
    {
        Db::name('generation_scene_template')->where('id', $templateId)->update([
            'status' => intval($status),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }
    
    /**
     * 检查URL是否为第三方URL（非本系统存储）
     * @param string $url 要检查的URL
     * @return bool 是第三方URL返回true
     */
    public static function isThirdPartyUrl($url)
    {
        if (empty($url)) {
            return false;
        }
        
        // 不是http开头的不算第三方
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            return false;
        }
        
        // 检查是否是本站URL
        if (defined('PRE_URL') && !empty(PRE_URL) && strpos($url, PRE_URL) === 0) {
            return false;
        }
        
        // 检查是否已在配置的云存储中
        $aid = defined('aid') ? aid : 0;
        $remoteset = null;
        if ($aid > 0) {
            $remoteset = Db::name('admin')->where('id', $aid)->value('remote');
            $remoteset = json_decode($remoteset, true);
            if (!$remoteset || ($remoteset['type'] ?? 0) == 0) {
                $remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
                $remoteset = json_decode($remoteset, true);
            }
        } else {
            $remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
            $remoteset = json_decode($remoteset, true);
        }
        
        if ($remoteset) {
            $type = $remoteset['type'] ?? 0;
            $storageUrl = '';
            switch ($type) {
                case 2: $storageUrl = $remoteset['alioss']['url'] ?? ''; break;
                case 3: $storageUrl = $remoteset['qiniu']['url'] ?? ''; break;
                case 4: $storageUrl = $remoteset['cos']['url'] ?? ''; break;
            }
            if (!empty($storageUrl) && strpos($url, $storageUrl) === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 重试场景模板的附件转存
     * 对已创建但封面/附件仍为第三方URL的模板，重新执行转存
     * @param int $templateId 模板ID
     * @return array
     */
    public function retryTransferForTemplate($templateId)
    {
        $template = Db::name('generation_scene_template')->where('id', $templateId)->find();
        if (!$template) {
            return ['status' => 0, 'msg' => '模板不存在'];
        }
        
        $aid = $template['aid'] ?? (defined('aid') ? aid : 0);
        $transferService = new AttachmentTransferService($aid);
        $generationType = $template['generation_type'] ?? 1;
        
        // 收集需要转存的URL
        $urlList = [];
        
        // 1. 封面图
        $coverImage = $template['cover_image'] ?? '';
        if (!empty($coverImage) && self::isThirdPartyUrl($coverImage)) {
            $urlList[] = [
                'key' => 'cover_image',
                'url' => $coverImage,
                'type' => ($generationType == 2) ? 'video' : 'image'
            ];
        }
        
        // 2. default_params中的URL
        $defaultParams = $template['default_params'] ?? '';
        if (is_string($defaultParams)) {
            $defaultParams = json_decode($defaultParams, true) ?: [];
        }
        
        // 首帧图/输入图
        $firstFrameImage = $defaultParams['first_frame_image'] ?? $defaultParams['input_image'] ?? '';
        if (!empty($firstFrameImage) && self::isThirdPartyUrl($firstFrameImage)) {
            $urlList[] = [
                'key' => 'first_frame_image',
                'url' => $firstFrameImage,
                'type' => 'image'
            ];
        }
        
        // 参考图
        $imageUrl = $defaultParams['image_url'] ?? $defaultParams['image'] ?? '';
        if (!empty($imageUrl) && $imageUrl !== $firstFrameImage && self::isThirdPartyUrl($imageUrl)) {
            $urlList[] = [
                'key' => 'image_url',
                'url' => $imageUrl,
                'type' => 'image'
            ];
        }
        
        if (empty($urlList)) {
            return ['status' => 1, 'msg' => '无需转存，所有附件已在本系统存储中'];
        }
        
        Log::info('开始重试模板附件转存', [
            'template_id' => $templateId,
            'url_count' => count($urlList)
        ]);
        
        // 执行批量转存
        $urlMapping = $transferService->transferBatch($urlList);
        
        // 更新模板数据
        $updateData = ['update_time' => time()];
        $successCount = 0;
        $failCount = 0;
        
        // 更新封面图
        if (isset($urlMapping['cover_image']) && $urlMapping['cover_image']['success']) {
            $updateData['cover_image'] = $urlMapping['cover_image']['transferred'];
            $successCount++;
        } elseif (isset($urlMapping['cover_image'])) {
            $failCount++;
        }
        
        // 更新default_params中的URL
        $paramsUpdated = false;
        if (isset($urlMapping['first_frame_image']) && $urlMapping['first_frame_image']['success']) {
            if (isset($defaultParams['first_frame_image'])) {
                $defaultParams['first_frame_image'] = $urlMapping['first_frame_image']['transferred'];
                $paramsUpdated = true;
            }
            if (isset($defaultParams['input_image'])) {
                $defaultParams['input_image'] = $urlMapping['first_frame_image']['transferred'];
                $paramsUpdated = true;
            }
            $successCount++;
        } elseif (isset($urlMapping['first_frame_image'])) {
            $failCount++;
        }
        
        if (isset($urlMapping['image_url']) && $urlMapping['image_url']['success']) {
            if (isset($defaultParams['image_url'])) {
                $defaultParams['image_url'] = $urlMapping['image_url']['transferred'];
                $paramsUpdated = true;
            }
            if (isset($defaultParams['image'])) {
                $defaultParams['image'] = $urlMapping['image_url']['transferred'];
                $paramsUpdated = true;
            }
            $successCount++;
        } elseif (isset($urlMapping['image_url'])) {
            $failCount++;
        }
        
        if ($paramsUpdated) {
            $updateData['default_params'] = json_encode($defaultParams, JSON_UNESCAPED_UNICODE);
        }
        
        // 写入数据库
        if (count($updateData) > 1) {
            Db::name('generation_scene_template')->where('id', $templateId)->update($updateData);
        }
        
        Log::info('模板附件转存重试完成', [
            'template_id' => $templateId,
            'success' => $successCount,
            'failed' => $failCount
        ]);
        
        if ($failCount > 0 && $successCount == 0) {
            return ['status' => 0, 'msg' => '转存失败（' . $failCount . '个文件），请检查日志'];
        } elseif ($failCount > 0) {
            return ['status' => 1, 'msg' => '部分转存成功（成功' . $successCount . '个，失败' . $failCount . '个）'];
        }
        
        return ['status' => 1, 'msg' => '转存成功（' . $successCount . '个文件）'];
    }
}

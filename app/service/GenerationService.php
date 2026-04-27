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
use app\service\ImagePersistService;
use app\service\AutoTaggingService;

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
        
        // 判断是否需要异步队列执行
        // SeeDream多图SSE流式请求（max_images>1）耗时可达540~810秒，
        // 在Web(PHP-FPM)上下文中会被 max_execution_time 杀死进程，
        // 导致 markFailed() 永远不执行、记录永远卡在 status=1
        $needQueue = false;
        if ($this->isSeedreamImageModel($model['model_code'])) {
            $maxImages = 1;
            $seqOpts = $inputParams['sequential_image_generation_options'] ?? null;
            if (is_string($seqOpts)) {
                $seqOpts = json_decode($seqOpts, true);
            }
            if (is_array($seqOpts) && isset($seqOpts['max_images'])) {
                $maxImages = intval($seqOpts['max_images']);
            }
            // 多图生成（max_images > 1）需要长时间SSE流式等待，必须放入队列异步执行
            if ($maxImages > 1) {
                $needQueue = true;
            }
        }

        if ($needQueue) {
            // 异步队列执行：CLI worker 无 max_execution_time 限制
            // 使用专属队列 seedream_generation，worker --timeout 1200 以支持多图长耗时
            Queue::push('app\job\GenerationJob', ['record_id' => $record->id], 'seedream_generation');
            Log::info('SeeDream多图任务已推送队列异步执行', [
                'record_id' => $record->id,
                'model_code' => $model['model_code'],
                'max_images' => $maxImages ?? 1,
            ]);
        } else {
            // 直接同步执行生成任务（请求立即处理）
            // 注：对于异步模型（视频生成等），会先返回任务ID，前端轮询状态
            $this->executeGeneration($record->id);
        }
        
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
                    // 对图片类型的输出执行 WebP 压缩转存
                    $result['outputs'] = $this->persistImageOutputs($result['outputs'], $record->aid ?? 0);
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
        
        // 即梦AI模型使用单独的调用流程（火山引擎V4签名 + 特殊endpoint）
        if ($this->isJimengVideoModel($model['model_code'])) {
            return $this->callJimengVideoApi($model, $apiKeyConfig, $inputParams);
        }
        if ($this->isJimengImageModel($model['model_code'], $providerCode)) {
            return $this->callJimengApi($model, $apiKeyConfig, $inputParams);
        }
        
        // Ollama本地LLM使用单独的调用流程（无需认证，本地HTTP API）
        if ($this->isOllamaModel($model['model_code'], $providerCode)) {
            return $this->callOllamaApi($model, $apiKeyConfig, $inputParams);
        }
        
        // VoxCPM2语音合成使用单独的调用流程（自部署API服务）
        if ($this->isVoxCPMModel($model['model_code'], $providerCode)) {
            return $this->callVoxCPMApi($model, $apiKeyConfig, $inputParams);
        }
        
        // 构建请求头
        $headers = $this->buildAuthHeaders($providerCode, $apiKey, $apiSecret, $model['auth_config'] ?? []);
        
        // 构建请求体（含类型转换）
        $requestBody = $this->buildRequestBody($model, $inputParams);
        
        // 记录请求日志（内联方式，确保日志实际输出）
        $requestBodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        \think\facade\Log::info('模型API请求 endpoint=' . $endpoint
            . ' provider=' . $providerCode
            . ' model=' . $model['model_code']
            . ' api_key_prefix=' . substr($apiKey, 0, 8) . '***'
            . ' body=' . (strlen($requestBodyJson) > 3000 ? substr($requestBodyJson, 0, 3000) . '...[truncated]' : $requestBodyJson));
        
        // 计算请求超时：SeeDream SSE流式多图请求需要更长超时
        // 每张图约60-90秒，预留120秒/张的安全余量
        $timeout = 300; // 默认5分钟
        if ($this->isSeedreamImageModel($model['model_code'])) {
            $stream = filter_var($inputParams['stream'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $seqOpts = $inputParams['sequential_image_generation_options'] ?? null;
            if (is_string($seqOpts)) {
                $seqOpts = json_decode($seqOpts, true);
            }
            $maxImages = (is_array($seqOpts) && isset($seqOpts['max_images'])) ? intval($seqOpts['max_images']) : 1;
            if ($stream && $maxImages > 1) {
                // 多图SSE：每张预留120秒 + 60秒缓冲，最低600秒
                $timeout = max(600, $maxImages * 120 + 60);
            } else {
                // 单图或非流式：给予足够时间（10分钟）
                $timeout = 600;
            }
            Log::info('SeeDream请求超时设置', [
                'model' => $model['model_code'],
                'stream' => $stream,
                'max_images' => $maxImages,
                'timeout' => $timeout,
            ]);
        }

        // 发送请求
        $response = $this->sendHttpRequest($endpoint, 'POST', $headers, $requestBody, $timeout);
        
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
            case 'kling':
                // 可灵AI使用JWT Token认证
                $token = $this->generateKlingJwtToken($apiKey, $apiSecret);
                $headers[] = 'Authorization: Bearer ' . $token;
                break;
            case 'volcengine':
            case 'doubao':
                $headers[] = 'Authorization: Bearer ' . $apiKey;
                break;
            case 'aliyun':
            case 'dashscope':
            case 'aishi':
                $headers[] = 'Authorization: Bearer ' . $apiKey;
                // 启用DashScope异步模式，避免同步请求超时
                $headers[] = 'X-DashScope-Async: enable';
                break;
            case 'openai':
                $headers[] = 'Authorization: Bearer ' . $apiKey;
                break;
            case 'ollama':
                // Ollama本地部署，无需认证头
                break;
            case 'voxcpm':
                // VoxCPM自部署服务，无需认证头
                break;
            default:
                // 默认Bearer Token认证
                $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        return $headers;
    }

    /**
     * 生成可灵AI的JWT Token
     * @param string $accessKey AccessKey
     * @param string $secretKey SecretKey
     * @return string JWT Token
     */
    protected function generateKlingJwtToken($accessKey, $secretKey)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $accessKey,
            'exp' => time() + 3600,  // Token有效期1小时
            'nbf' => time() - 5      // 提前5秒生效，避免时钟偏差
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL安全编码
     * @param string $data 数据
     * @return string
     */
    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * 构建请求体
     * 根据 input_schema 进行参数类型转换和清理
     */
    protected function buildRequestBody($model, $inputParams)
    {
        $modelCode = $model['model_code'];
        $providerCode = $model['provider_code'] ?? '';
        
        // 检查是否是Seedance视频生成模型（需要特殊content数组格式）
        if ($this->isSeedanceVideoModel($modelCode)) {
            return $this->buildSeedanceRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是爱诗科技PixVerse视频生成模型（DashScope model+input+parameters格式）
        if ($this->isPixVerseVideoModel($modelCode, $providerCode)) {
            return $this->buildPixVerseRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是即梦AI视频生成模型（火山引擎CV API格式）
        if ($this->isJimengVideoModel($modelCode)) {
            return $this->buildJimengVideoRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是即梦AI图片生成模型（火山引擎CV API格式）
        if ($this->isJimengImageModel($modelCode, $providerCode)) {
            return $this->buildJimengRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是Ollama本地LLM模型
        if ($this->isOllamaModel($modelCode, $providerCode)) {
            return $this->buildOllamaChatRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是阿里云百炼Wan视频生成模型（DashScope model+input+parameters格式，img_url字段）
        if ($this->isAliyunWanVideoModel($modelCode, $providerCode)) {
            return $this->buildAliyunWanVideoRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是阿里云百炼图像生成/编辑模型（需要将图片转为Base64）
        if ($this->isAliyunWanImageModel($modelCode, $providerCode)) {
            return $this->buildAliyunWanRequestBody($modelCode, $inputParams);
        }
        
        // 检查是否是豆包SeeDream图像生成模型（需要严格参数白名单过滤）
        if ($this->isSeedreamImageModel($modelCode)) {
            return $this->buildSeedreamRequestBody($modelCode, $inputParams);
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
     * 检查是否为豆包SeeDream图像生成模型
     * SeeDream模型需要严格的参数白名单过滤，不能透传所有用户输入参数
     * 注意：排除Seedance视频模型
     * 
     * @param string $modelCode 模型代码
     * @return bool
     */
    protected function isSeedreamImageModel($modelCode)
    {
        // 排除Seedance视频模型
        if (strpos($modelCode, 'doubao-seedance') === 0) {
            return false;
        }
        return strpos($modelCode, 'doubao-seedream') === 0;
    }
    
    /**
     * 为豆包SeeDream图像生成模型构建请求体（严格参数白名单过滤）
     * 
     * SeeDream系列模型对API参数有严格要求，不支持的参数会导致HTTP 400错误。
     * 不同子系列支持的参数不同：
     * 
     * 5.0-lite/4.5/4.0 通用参数：
     *   model, prompt, image, size, n, response_format, stream,
     *   sequential_image_generation, sequential_image_generation_options, optimize_prompt_options
     * 5.0-lite 独有：tools, output_format
     * 3.0-t2i 专属：model, prompt, size, n, seed, guidance_scale, response_format
     * 需要过滤的无效参数：strength, edit_mode, num_inference_steps, output_quality 等
     * 需要映射的参数：reference_image → image, prompt_extend → optimize_prompt_options
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array 符合SeeDream API格式的请求体
     */
    protected function buildSeedreamRequestBody($modelCode, $inputParams)
    {
        $modelLower = strtolower($modelCode);
        $is30t2i  = (strpos($modelLower, 'seedream-3') !== false || strpos($modelLower, '3-0-t2i') !== false);
        $is50lite = (strpos($modelLower, 'seedream-5') !== false || strpos($modelLower, '5-0') !== false);
        
        // ========== 公共参数（所有SeeDream模型都支持） ==========
        $body = [
            'model' => $modelCode,
        ];
        
        // prompt（必填）
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? '';
        
        // negative_prompt 负面提示词处理（API不支持独立字段，需拼接到prompt尾部）
        $negativePrompt = $inputParams['negative_prompt'] ?? '';
        if (is_string($negativePrompt)) {
            $negativePrompt = trim($negativePrompt);
        } else {
            $negativePrompt = '';
        }
        if (!empty($prompt) && !empty($negativePrompt)) {
            $body['prompt'] = $prompt . '，请避免以下元素：' . $negativePrompt;
        } elseif (!empty($prompt)) {
            $body['prompt'] = $prompt;
        }
        
        // size 尺寸
        if (!empty($inputParams['size'])) {
            $body['size'] = $inputParams['size'];
        } else {
            $body['size'] = $is30t2i ? '1024x1024' : '2K';
        }
        
        // SeeDream 5.0-lite / 4.5 / 4.0 最低像素要求 3686400（1920x1920）
        // 3.0-t2i 无此限制（其范围为 512x512 ~ 2048x2048）
        // 当 size 为 WxH 格式且像素不足时，按比例放大到满足最低要求
        if (!$is30t2i && !empty($body['size']) && preg_match('/^(\d+)x(\d+)$/i', $body['size'], $m)) {
            $w = intval($m[1]);
            $h = intval($m[2]);
            $minPixels = 3686400;
            $currentPixels = $w * $h;
            if ($currentPixels > 0 && $currentPixels < $minPixels) {
                $scale = sqrt($minPixels / $currentPixels);
                // 向上取整到最近的64倍数（GPU友好）
                $newW = (int)(ceil(($w * $scale) / 64) * 64);
                $newH = (int)(ceil(($h * $scale) / 64) * 64);
                \think\facade\Log::info('buildSeedreamRequestBody: SeeDream尺寸不足，自动放大 original=' . $body['size']
                    . '(' . $currentPixels . 'px) new=' . $newW . 'x' . $newH
                    . '(' . ($newW * $newH) . 'px) min=' . $minPixels);
                $body['size'] = $newW . 'x' . $newH;
            }
        }
        
        // n 生成数量
        if (isset($inputParams['n']) && $inputParams['n'] !== '' && $inputParams['n'] !== null) {
            $body['n'] = intval($inputParams['n']);
        }
        
        // response_format
        if (!empty($inputParams['response_format'])) {
            $body['response_format'] = $inputParams['response_format'];
        }
        
        // watermark 水印控制（API默认true，不传则始终加水印）
        // 必须显式传递：用户设置了则用用户值，否则默认 false（不加水印）
        // 注意：前端/模板 input_params 中经常不包含 watermark 字段，
        // 若不显式传 false，API 会使用其默认值 true 导致始终有水印
        $body['watermark'] = isset($inputParams['watermark'])
            ? filter_var($inputParams['watermark'], FILTER_VALIDATE_BOOLEAN)
            : false;
        
        if ($is30t2i) {
            // ========== 3.0-t2i 专属参数 ==========
            // 3.0-t2i 仅支持文生图，不传 image/stream/sequential_image_generation 等参数
            if (isset($inputParams['seed']) && $inputParams['seed'] !== '' && $inputParams['seed'] !== null && $inputParams['seed'] !== '-1') {
                $body['seed'] = intval($inputParams['seed']);
            }
            if (isset($inputParams['guidance_scale']) && $inputParams['guidance_scale'] !== '' && $inputParams['guidance_scale'] !== null) {
                $body['guidance_scale'] = floatval($inputParams['guidance_scale']);
            }
        } else {
            // ========== 5.0-lite / 4.5 / 4.0 通用参数 ==========
            
            // image 参考图：支持从 reference_image 或 image 字段获取
            $imageValue = null;
            if (!empty($inputParams['image'])) {
                $imageValue = $inputParams['image'];
            } elseif (!empty($inputParams['reference_image'])) {
                $imageValue = $inputParams['reference_image'];
            }
            if (!empty($imageValue)) {
                // 如果是逗号分隔的多图URL，转为数组
                if (is_string($imageValue) && strpos($imageValue, ',') !== false) {
                    $imageValue = array_filter(array_map('trim', explode(',', $imageValue)));
                    $imageValue = array_values($imageValue);
                }
                $body['image'] = $imageValue;
            }
            
            // stream 流式输出
            if (isset($inputParams['stream'])) {
                $body['stream'] = filter_var($inputParams['stream'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // sequential_image_generation 组图模式
            if (isset($inputParams['sequential_image_generation']) && $inputParams['sequential_image_generation'] !== '' && $inputParams['sequential_image_generation'] !== null) {
                $body['sequential_image_generation'] = $inputParams['sequential_image_generation'];
                // 组图选项（max_images等）
                if (isset($inputParams['sequential_image_generation_options'])) {
                    $opts = $inputParams['sequential_image_generation_options'];
                    if (is_string($opts)) {
                        $decoded = json_decode($opts, true);
                        if ($decoded !== null) {
                            $opts = $decoded;
                        }
                    }
                    $body['sequential_image_generation_options'] = $opts;
                }
            } elseif (!empty($imageValue) && !isset($body['sequential_image_generation_options'])) {
                // 有image参数时默认设置
                $body['sequential_image_generation_options'] = ['max_images' => 1];
            }
            
            // optimize_prompt_options 提示词优化
            if (isset($inputParams['optimize_prompt_options'])) {
                $opts = $inputParams['optimize_prompt_options'];
                if (is_string($opts)) {
                    $decoded = json_decode($opts, true);
                    if ($decoded !== null) {
                        $opts = $decoded;
                    }
                }
                $body['optimize_prompt_options'] = $opts;
            } elseif (isset($inputParams['prompt_extend']) && $inputParams['prompt_extend'] == '1') {
                // prompt_extend=1 映射为 optimize_prompt_options.mode=standard
                $body['optimize_prompt_options'] = ['mode' => 'standard'];
            }
            
            // ---- 5.0-lite 独有参数 ----
            if ($is50lite) {
                // tools 联网搜索工具
                if (isset($inputParams['tools'])) {
                    $tools = $inputParams['tools'];
                    if (is_string($tools)) {
                        $decoded = json_decode($tools, true);
                        if ($decoded !== null) {
                            $tools = $decoded;
                        }
                    }
                    $body['tools'] = $tools;
                }
                // output_format 输出格式（png/jpeg）
                if (!empty($inputParams['output_format'])) {
                    $body['output_format'] = $inputParams['output_format'];
                }
            }
        }
        
        // 使用内联方式记录日志（ThinkPHP Log context数组不会被写入日志文件）
        $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        \think\facade\Log::info('buildSeedreamRequestBody: model=' . $modelCode
            . ' series=' . ($is30t2i ? '3.0-t2i' : ($is50lite ? '5.0-lite' : '4.5/4.0'))
            . ' watermark=' . var_export($body['watermark'] ?? 'NOT_SET', true)
            . ' body_keys=' . implode(',', array_keys($body))
            . ' filtered=' . implode(',', array_keys(array_diff_key($inputParams, $body)))
            . ' body_json=' . $bodyJson);
        
        return $body;
    }
    
    /**
     * 检查是否为爱诗科技PixVerse视频生成模型
     * PixVerse模型通过阿里云百炼DashScope接入，使用 model+input+parameters 三层格式
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商标识
     * @return bool
     */
    protected function isPixVerseVideoModel($modelCode, $providerCode = '')
    {
        // 按model_code匹配
        if (strpos($modelCode, 'pixverse') === 0) {
            return true;
        }
        // 按provider_code匹配
        if ($providerCode === 'aishi') {
            return true;
        }
        return false;
    }
    
    /**
     * 为爱诗科技PixVerse视频模型构建符合DashScope API格式的请求体
     * 
     * 支持三种模型变体：
     * - pixverse/pixverse-v5.6-it2v: 图生视频（首帧），1张图片(image_url) + 可选prompt
     * - pixverse/pixverse-v5.6-kf2v: 首尾帧生视频，首帧(first_frame)+尾帧(last_frame) + 必选prompt
     * - pixverse/pixverse-v5.6-r2v:  参考生视频，1-7张参考图(image_url) + 必选prompt，使用size参数
     * 
     * DashScope API请求格式:
     * {
     *   "model": "pixverse/pixverse-v5.6-it2v",
     *   "input": {
     *     "media": [{"type": "image_url", "url": "..."}],
     *     "prompt": "..."
     *   },
     *   "parameters": {
     *     "resolution": "720P",
     *     "duration": 5,
     *     "audio": false,
     *     "watermark": false
     *   }
     * }
     * 
     * @param string $modelCode 模型代码（如 pixverse/pixverse-v5.6-it2v）
     * @param array $inputParams 用户输入参数
     * @return array 符合API格式的请求体
     */
    protected function buildPixVerseRequestBody($modelCode, $inputParams)
    {
        // 识别PixVerse子模型类型
        $isKf2v = (strpos($modelCode, 'kf2v') !== false);
        $isR2v  = (strpos($modelCode, 'r2v') !== false);
        $isIt2v = (strpos($modelCode, 'it2v') !== false);
        
        // ========== 构建media数组 ==========
        $media = [];
        
        if ($isKf2v) {
            // kf2v: 需要首帧(first_frame) + 尾帧(last_frame)
            $firstFrameUrl = $inputParams['first_frame_image'] 
                ?? $inputParams['image_url'] 
                ?? $inputParams['image'] 
                ?? '';
            $lastFrameUrl = $inputParams['last_frame_image'] 
                ?? $inputParams['tail_image_url'] 
                ?? $inputParams['last_frame'] 
                ?? '';
            
            if (!empty($firstFrameUrl)) {
                $media[] = ['type' => 'first_frame', 'url' => $firstFrameUrl];
            }
            if (!empty($lastFrameUrl)) {
                $media[] = ['type' => 'last_frame', 'url' => $lastFrameUrl];
            }
        } elseif ($isR2v) {
            // r2v: 1-7张参考图片
            $refImages = $inputParams['reference_images'] ?? [];
            if (is_string($refImages) && !empty($refImages)) {
                $refImages = array_filter(array_map('trim', explode(',', $refImages)));
            }
            // 也检查单张图片参数作为兑容
            if (empty($refImages)) {
                $singleImage = $inputParams['image_url'] 
                    ?? $inputParams['first_frame_image'] 
                    ?? $inputParams['image'] 
                    ?? '';
                if (!empty($singleImage)) {
                    $refImages = [$singleImage];
                }
            }
            foreach ($refImages as $imgUrl) {
                if (!empty($imgUrl)) {
                    $media[] = ['type' => 'image_url', 'url' => $imgUrl];
                }
            }
        } else {
            // it2v (默认): 单张图片作为首帧
            $imageUrl = $inputParams['image_url'] 
                ?? $inputParams['first_frame_image'] 
                ?? $inputParams['image'] 
                ?? $inputParams['input_image'] 
                ?? '';
            if (!empty($imageUrl)) {
                $media[] = ['type' => 'image_url', 'url' => $imageUrl];
            }
        }
        
        // ========== 构建input部分 ==========
        $input = [];
        if (!empty($media)) {
            $input['media'] = $media;
        }
        
        // 提示词
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? $inputParams['description'] ?? '';
        if (!empty($prompt)) {
            $input['prompt'] = $prompt;
        }
        
        // ========== 构建parameters部分 ==========
        $parameters = [];
        
        // 视频时长
        if (!empty($inputParams['duration'])) {
            $parameters['duration'] = intval($inputParams['duration']);
        }
        
        // 分辨率参数：r2v使用size（宽*高），其他模型使用resolution（如720P）
        if ($isR2v) {
            if (!empty($inputParams['size'])) {
                $parameters['size'] = $inputParams['size'];
            } elseif (!empty($inputParams['resolution'])) {
                // 将resolution转换为size（默认16:9宽高比）
                $resolutionToSize = [
                    '360P'  => '640*360',
                    '540P'  => '1024*576',
                    '720P'  => '1280*720',
                    '1080P' => '1920*1080',
                ];
                $resKey = strtoupper($inputParams['resolution']);
                $parameters['size'] = $resolutionToSize[$resKey] ?? '1280*720';
            }
        } else {
            if (!empty($inputParams['resolution'])) {
                $parameters['resolution'] = strtoupper($inputParams['resolution']);
            }
        }
        
        // 音频参数
        if (isset($inputParams['audio'])) {
            $parameters['audio'] = filter_var($inputParams['audio'], FILTER_VALIDATE_BOOLEAN);
        } elseif (isset($inputParams['with_audio'])) {
            $parameters['audio'] = filter_var($inputParams['with_audio'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // 水印参数
        if (isset($inputParams['watermark'])) {
            $parameters['watermark'] = filter_var($inputParams['watermark'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // 随机种子
        if (isset($inputParams['seed']) && $inputParams['seed'] !== '' && $inputParams['seed'] !== null) {
            $parameters['seed'] = intval($inputParams['seed']);
        }
        
        // ========== 组装最终请求体 ==========
        $body = [
            'model' => $modelCode,
            'input' => $input,
        ];
        
        if (!empty($parameters)) {
            $body['parameters'] = $parameters;
        }
        
        \think\facade\Log::info('buildPixVerseRequestBody: 构建PixVerse请求体', [
            'model' => $modelCode,
            'sub_type' => $isKf2v ? 'kf2v' : ($isR2v ? 'r2v' : 'it2v'),
            'media_count' => count($media),
            'has_prompt' => !empty($prompt),
            'parameters' => $parameters
        ]);
        
        return $body;
    }
    
    /**
     * 检查是否为即梦AI图片生成模型
     * 即梦模型通过火山引擎CV API接入，使用V4签名认证
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商标识
     * @return bool
     */
    protected function isJimengImageModel($modelCode, $providerCode = '')
    {
        // 即梦视频模型不走图片流程
        if ($this->isJimengVideoModel($modelCode)) {
            return false;
        }
        if (strpos($modelCode, 'jimeng') === 0) {
            return true;
        }
        if ($providerCode === 'jimeng') {
            return true;
        }
        return false;
    }
    
    /**
     * 检查是否为即梦AI视频生成模型
     * 
     * @param string $modelCode 模型代码
     * @return bool
     */
    protected function isJimengVideoModel($modelCode)
    {
        return strpos($modelCode, 'jimeng_video') === 0;
    }
    
    /**
     * 为即梦AI图片生成模型构建请求体
     * 
     * 即梦API请求格式:
     * {
     *   "req_key": "jimeng_t2i_v40",
     *   "image_urls": ["url1", "url2"],
     *   "prompt": "描述文字",
     *   "size": 4194304,
     *   "width": 2048, "height": 2048,
     *   "scale": 0.5,
     *   "force_single": false
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array 符合API格式的请求体
     */
    protected function buildJimengRequestBody($modelCode, $inputParams)
    {
        // 从配置中获取req_key，默认为 jimeng_t2i_v40
        $jimengConfig = config('aivideo.jimeng.models.' . $modelCode) ?? [];
        $reqKey = $jimengConfig['req_key'] ?? 'jimeng_t2i_v40';
        
        $body = [
            'req_key' => $reqKey,
        ];
        
        // 提示词（必选）
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? '';
        if (!empty($prompt)) {
            $body['prompt'] = $prompt;
        }
        
        // 输入图片URL数组（可选，0-10张）
        $imageUrls = [];
        // 支持多种输入方式
        if (!empty($inputParams['image_urls'])) {
            $imgs = $inputParams['image_urls'];
            if (is_string($imgs)) {
                $imageUrls = array_filter(array_map('trim', explode(',', $imgs)));
            } elseif (is_array($imgs)) {
                $imageUrls = $imgs;
            }
        } elseif (!empty($inputParams['image'])) {
            $img = $inputParams['image'];
            if (is_string($img) && strpos($img, ',') !== false) {
                $imageUrls = array_filter(array_map('trim', explode(',', $img)));
            } elseif (is_string($img)) {
                $imageUrls = [$img];
            } elseif (is_array($img)) {
                $imageUrls = $img;
            }
        } elseif (!empty($inputParams['reference_images'])) {
            $imgs = $inputParams['reference_images'];
            if (is_string($imgs)) {
                $imageUrls = array_filter(array_map('trim', explode(',', $imgs)));
            } elseif (is_array($imgs)) {
                $imageUrls = $imgs;
            }
        }
        
        if (!empty($imageUrls)) {
            $body['image_urls'] = array_values(array_slice($imageUrls, 0, 10));
        }
        
        // 尺寸参数：优先使用width+height，其次用size面积
        if (!empty($inputParams['width']) && !empty($inputParams['height'])) {
            $body['width'] = intval($inputParams['width']);
            $body['height'] = intval($inputParams['height']);
        } elseif (!empty($inputParams['size'])) {
            // size可能是 "2048x2048" 或 "2048*2048" 格式，或直接是数字面积
            $sizeVal = $inputParams['size'];
            if (is_string($sizeVal) && (strpos($sizeVal, 'x') !== false || strpos($sizeVal, '*') !== false)) {
                $parts = preg_split('/[x*]/', $sizeVal);
                if (count($parts) == 2) {
                    $body['width'] = intval(trim($parts[0]));
                    $body['height'] = intval(trim($parts[1]));
                }
            } elseif (is_numeric($sizeVal)) {
                $body['size'] = intval($sizeVal);
            }
        }
        
        // 文本描述影响程度 scale (0-1)
        if (isset($inputParams['scale']) && $inputParams['scale'] !== '' && $inputParams['scale'] !== null) {
            $body['scale'] = floatval($inputParams['scale']);
        }
        
        // 是否强制单图
        if (isset($inputParams['force_single'])) {
            $body['force_single'] = filter_var($inputParams['force_single'], FILTER_VALIDATE_BOOLEAN);
        }
        
        \think\facade\Log::info('buildJimengRequestBody: 构建即梦请求体', [
            'model_code' => $modelCode,
            'req_key' => $reqKey,
            'has_prompt' => !empty($prompt),
            'image_count' => count($imageUrls),
            'body_keys' => array_keys($body)
        ]);
        
        return $body;
    }
    
    /**
     * 调用即梦AI API（火山引擎CV平台）
     * 即梦模型使用火山引擎V4签名认证，endpoint需拼接query参数
     * 
     * @param array $model 模型信息
     * @param array $apiKeyConfig API Key配置
     * @param array $inputParams 输入参数
     * @return array 响应结果
     */
    protected function callJimengApi($model, $apiKeyConfig, $inputParams)
    {
        $jimengConfig = config('aivideo.jimeng') ?? [];
        $apiUrl = $jimengConfig['api_url'] ?? 'https://visual.volcengineapi.com';
        $submitAction = $jimengConfig['submit_action'] ?? 'CVSync2AsyncSubmitTask';
        $apiVersion = $jimengConfig['api_version'] ?? '2022-08-31';
        $region = $jimengConfig['region'] ?? 'cn-north-1';
        $service = $jimengConfig['service'] ?? 'cv';
        
        $accessKey = $apiKeyConfig['api_key_decrypted'];
        $secretKey = $apiKeyConfig['api_secret_decrypted'] ?? '';
        
        if (empty($accessKey) || empty($secretKey)) {
            return [
                'status' => 0,
                'error_code' => 'JIMENG_KEY_MISSING',
                'msg' => '即梦AI需要配置火山引擎Access Key和Secret Key'
            ];
        }
        
        // 构建请求体
        $requestBody = $this->buildJimengRequestBody($model['model_code'], $inputParams);
        $bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
        
        // 拼接URL及query参数
        $queryParams = [
            'Action' => $submitAction,
            'Version' => $apiVersion,
        ];
        $queryString = http_build_query($queryParams);
        $fullUrl = $apiUrl . '?' . $queryString;
        
        // 生成火山引擎V4签名头
        $headers = $this->generateVolcengineV4Headers(
            $accessKey, $secretKey, $region, $service,
            'POST', '/', $queryString, $bodyJson
        );
        
        \think\facade\Log::info('即梦API提交任务请求', [
            'url' => $fullUrl,
            'model' => $model['model_code'],
            'body' => $requestBody
        ]);
        
        // 发送HTTP请求
        $response = $this->sendHttpRequest($fullUrl, 'POST', $headers, $requestBody);
        
        \think\facade\Log::info('即梦API提交任务响应', [
            'http_code' => $response['http_code'],
            'body' => is_array($response['body']) ? json_encode($response['body'], JSON_UNESCAPED_UNICODE) : substr((string)$response['body'], 0, 2000)
        ]);
        
        $body = $response['body'];
        
        // 即梦API成功状态码为 10000
        if (is_array($body) && isset($body['code']) && $body['code'] == 10000) {
            $taskId = $body['data']['task_id'] ?? '';
            if (!empty($taskId)) {
                return [
                    'status' => 1,
                    'async' => true,
                    'async_type' => 'jimeng',
                    'external_task_id' => $taskId,
                    'outputs' => [],
                    'tokens' => 0,
                    'cost' => 0
                ];
            }
        }
        
        // 错误处理
        $errorMsg = '';
        $errorCode = 'JIMENG_API_ERROR';
        if (is_array($body)) {
            $errorMsg = $body['message'] ?? 'API请求失败';
            $errorCode = 'JIMENG_' . ($body['code'] ?? $response['http_code']);
        } else {
            $errorMsg = '即梦API请求失败(HTTP ' . $response['http_code'] . ')';
        }
        
        return [
            'status' => 0,
            'error_code' => $errorCode,
            'msg' => $errorMsg
        ];
    }
    
    /**
     * 生成火山引擎V4签名认证头
     * 基于HMAC-SHA256算法，兼容AWS SigV4签名规范
     * 
     * @param string $accessKey Access Key ID
     * @param string $secretKey Secret Access Key
     * @param string $region 区域（如 cn-north-1）
     * @param string $service 服务（如 cv）
     * @param string $method HTTP方法
     * @param string $uri 请求路径
     * @param string $queryString 查询字符串
     * @param string $body 请求体
     * @return array HTTP请求头数组
     */
    protected function generateVolcengineV4Headers($accessKey, $secretKey, $region, $service, $method, $uri, $queryString, $body)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateStamp = $now->format('Ymd');
        $amzDate = $now->format('Ymd\THis\Z');
        
        // Step 1: 构建规范请求 (Canonical Request)
        $host = 'visual.volcengineapi.com';
        $contentType = 'application/json';
        
        // 规范化query string: 按参数名排序
        $queryParts = [];
        if (!empty($queryString)) {
            parse_str($queryString, $params);
            ksort($params);
            $sortedQuery = [];
            foreach ($params as $k => $v) {
                $sortedQuery[] = rawurlencode($k) . '=' . rawurlencode($v);
            }
            $queryString = implode('&', $sortedQuery);
        }
        
        $payloadHash = hash('sha256', $body);
        
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$host}\nx-content-sha256:{$payloadHash}\nx-date:{$amzDate}\n";
        $signedHeaders = 'content-type;host;x-content-sha256;x-date';
        
        $canonicalRequest = "{$method}\n{$uri}\n{$queryString}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        // Step 2: 构建待签字符串 (String to Sign)
        $algorithm = 'HMAC-SHA256';
        $credentialScope = "{$dateStamp}/{$region}/{$service}/request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
        
        // Step 3: 计算签名 (Signing Key)
        $kDate = hash_hmac('sha256', $dateStamp, $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'request', $kService, true);
        
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        // Step 4: 构建Authorization头
        $authorization = "{$algorithm} Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        
        return [
            'Content-Type: ' . $contentType,
            'Host: ' . $host,
            'X-Date: ' . $amzDate,
            'X-Content-Sha256: ' . $payloadHash,
            'Authorization: ' . $authorization,
        ];
    }
    
    /**
     * 轮询即梦AI异步任务状态
     * 通过 CVSync2AsyncGetResult 接口查询任务结果
     * 
     * @param array $record 生成记录
     * @return array ['progress' => int] 返回进度信息
     */
    protected function pollJimengTaskStatus($record)
    {
        $externalTaskId = $record['task_id'];
        $pollResult = ['progress' => 0];
        
        $model = $this->getModelDetail($record['model_id']);
        if (!$model) {
            \think\facade\Log::warning('pollJimengTaskStatus: 模型不存在 model_id=' . $record['model_id']);
            return $pollResult;
        }
        
        $apiKeyConfig = $this->apiKeyService->getActiveConfigByProvider($model['provider_code']);
        if (!$apiKeyConfig) {
            \think\facade\Log::warning('pollJimengTaskStatus: API Key未配置 provider=' . $model['provider_code']);
            return $pollResult;
        }
        
        $accessKey = $apiKeyConfig['api_key_decrypted'];
        $secretKey = $apiKeyConfig['api_secret_decrypted'] ?? '';
        if (empty($accessKey) || empty($secretKey)) {
            return $pollResult;
        }
        
        try {
            $jimengConfig = config('aivideo.jimeng') ?? [];
            $apiUrl = $jimengConfig['api_url'] ?? 'https://visual.volcengineapi.com';
            $queryAction = $jimengConfig['query_action'] ?? 'CVSync2AsyncGetResult';
            $apiVersion = $jimengConfig['api_version'] ?? '2022-08-31';
            $region = $jimengConfig['region'] ?? 'cn-north-1';
            $service = $jimengConfig['service'] ?? 'cv';
            
            // 即梦查询任务请求体
            $jimengModels = $jimengConfig['models'] ?? [];
            $modelCode = $model['model_code'];
            
            // 获取req_key: 图片模型直接用req_key字段，视频模型用第一个模式的req_key
            $modelConfig = $jimengModels[$modelCode] ?? [];
            if (!empty($modelConfig['req_key'])) {
                $reqKey = $modelConfig['req_key'];
            } elseif (!empty($modelConfig['modes'])) {
                // 视频模型：获取第一个模式的req_key作为查询用
                $firstMode = reset($modelConfig['modes']);
                $reqKey = $firstMode['req_key'] ?? $modelCode;
            } else {
                $reqKey = $modelCode;
            }
            
            $requestBody = [
                'req_key' => $reqKey,
                'task_id' => $externalTaskId,
                'req_json' => json_encode(['return_url' => true], JSON_UNESCAPED_UNICODE),
            ];
            $bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
            
            $queryString = http_build_query([
                'Action' => $queryAction,
                'Version' => $apiVersion,
            ]);
            $fullUrl = $apiUrl . '?' . $queryString;
            
            // 生成V4签名头
            $headers = $this->generateVolcengineV4Headers(
                $accessKey, $secretKey, $region, $service,
                'POST', '/', $queryString, $bodyJson
            );
            
            // 发送查询请求
            $response = $this->sendHttpRequest($fullUrl, 'POST', $headers, $requestBody);
            $body = $response['body'];
            
            \think\facade\Log::info('pollJimengTaskStatus 查询结果', [
                'record_id' => $record['id'],
                'task_id' => $externalTaskId,
                'http_code' => $response['http_code'],
                'code' => $body['code'] ?? 'null',
                'status' => $body['data']['status'] ?? 'null'
            ]);
            
            if (!is_array($body)) {
                return $pollResult;
            }
            
            // 先判断code是否为10000
            if (isset($body['code']) && $body['code'] != 10000) {
                // 任务失败
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed(
                        'JIMENG_' . ($body['code'] ?? 'ERROR'),
                        $body['message'] ?? '即梦生成失败'
                    );
                }
                return $pollResult;
            }
            
            $taskStatus = $body['data']['status'] ?? '';
            
            if ($taskStatus === 'done') {
                // 任务完成
                $outputs = [];
                
                // 判断是视频输出还是图片输出
                $isVideoModel = $this->isJimengVideoModel($model['model_code'] ?? '');
                
                if ($isVideoModel) {
                    // 视频输出：从 data.video_url 获取
                    $videoUrl = $body['data']['video_url'] ?? '';
                    if (!empty($videoUrl)) {
                        $outputs[] = [
                            'type' => 'video',
                            'url' => $videoUrl,
                            'thumbnail' => '',
                            'width' => 0,
                            'height' => 0,
                            'duration' => 0
                        ];
                    }
                    // 也检查 video_urls 数组格式
                    $videoUrls = $body['data']['video_urls'] ?? [];
                    foreach ($videoUrls as $vUrl) {
                        if (!empty($vUrl)) {
                            $outputs[] = [
                                'type' => 'video',
                                'url' => $vUrl,
                                'thumbnail' => '',
                                'width' => 0,
                                'height' => 0,
                                'duration' => 0
                            ];
                        }
                    }
                } else {
                    // 图片输出：从 data.image_urls 获取
                    $imageUrls = $body['data']['image_urls'] ?? [];
                    foreach ($imageUrls as $imgUrl) {
                        if (!empty($imgUrl)) {
                            $outputs[] = [
                                'type' => 'image',
                                'url' => $imgUrl,
                                'thumbnail' => '',
                                'width' => 0,
                                'height' => 0
                            ];
                        }
                    }
                }
                
                if (!empty($outputs)) {
                    // 对图片类型的输出执行 WebP 压缩转存
                    $outputs = $this->persistImageOutputs($outputs, $record['aid'] ?? 0);
                    GenerationOutput::createOutputs($record['id'], $outputs);
                }
                
                $costTime = ($record['start_time'] > 0) ? (time() - $record['start_time']) * 1000 : 0;
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markSuccess($costTime);
                }
                
                \think\facade\Log::info('即梦生成成功', [
                    'record_id' => $record['id'],
                    'output_type' => $isVideoModel ? 'video' : 'image',
                    'output_count' => count($outputs),
                    'cost_time' => $costTime
                ]);
                
                $pollResult['progress'] = 100;
            } elseif ($taskStatus === 'generating') {
                $pollResult['progress'] = 50;
            } elseif ($taskStatus === 'in_queue') {
                $pollResult['progress'] = 10;
            } elseif (in_array($taskStatus, ['not_found', 'expired'])) {
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed('JIMENG_TASK_' . strtoupper($taskStatus), '任务' . ($taskStatus === 'not_found' ? '未找到' : '已过期'));
                }
            }
            // else: 其他状态，继续等待
        } catch (\Exception $e) {
            \think\facade\Log::error('pollJimengTaskStatus 异常: ' . $e->getMessage(), [
                'record_id' => $record['id'],
                'task_id' => $externalTaskId
            ]);
        }
        
        return $pollResult;
    }
    
    /**
     * 检查是否为即梦模型（用于轮询路由）
     * @param string $providerCode
     * @return bool
     */
    protected function isJimengModel($providerCode)
    {
        return $providerCode === 'jimeng';
    }
    
    /**
     * 为即梦AI视频生成模型构建请求体
     * 
     * 视频API请求格式:
     * {
     *   "req_key": "jimeng_video_v30_t2v_720p",
     *   "prompt": "描述文字",
     *   "image_urls": ["url1"],          // 图生视频时提供
     *   "aspect_ratio": "16:9",           // 视频比例
     *   "duration": 5,                     // 视频时长(秒)
     *   "camera_type": "horizontal_right", // 运镜类型（运镜模式）
     *   "camera_amplitude": 1.0            // 运镜幅度（运镜模式）
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array 符合API格式的请求体
     */
    protected function buildJimengVideoRequestBody($modelCode, $inputParams)
    {
        $jimengConfig = config('aivideo.jimeng.models.' . $modelCode) ?? [];
        $modes = $jimengConfig['modes'] ?? [];
        
        // 推断视频生成模式
        $videoMode = $this->inferJimengVideoMode($inputParams);
        
        // 获取对应模式的req_key
        $reqKey = $modes[$videoMode]['req_key'] ?? ($modelCode . '_' . $videoMode);
        
        $body = [
            'req_key' => $reqKey,
        ];
        
        // 提示词
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? '';
        if (!empty($prompt)) {
            $body['prompt'] = $prompt;
        }
        
        // 图片URL数组（首帧图/首尾帧图/运镜模式）
        $imageUrls = [];
        if (!empty($inputParams['first_frame_image'])) {
            $imageUrls[] = $inputParams['first_frame_image'];
        } elseif (!empty($inputParams['image_url'])) {
            $imageUrls[] = $inputParams['image_url'];
        } elseif (!empty($inputParams['image'])) {
            $img = $inputParams['image'];
            if (is_array($img)) {
                $imageUrls = $img;
            } else {
                $imageUrls[] = $img;
            }
        }
        
        // 尾帧图（首尾帧模式）
        if ($videoMode === 'first_last_frame') {
            $lastFrame = $inputParams['last_frame_image'] ?? $inputParams['tail_image_url'] ?? $inputParams['last_frame'] ?? '';
            if (!empty($lastFrame)) {
                $imageUrls[] = $lastFrame;
            }
        }
        
        if (!empty($imageUrls)) {
            $body['image_urls'] = array_values($imageUrls);
        }
        
        // 视频比例
        $aspectRatio = $inputParams['aspect_ratio'] ?? $inputParams['ratio'] ?? '';
        if (!empty($aspectRatio)) {
            $body['aspect_ratio'] = $aspectRatio;
        }
        
        // 运镜参数（仅运镜模式 - 使用火山引擎API正确的参数名）
        if ($videoMode === 'camera_motion') {
            // template_id: 运镜模板（前端传camera_type，API接收template_id）
            $cameraType = $inputParams['camera_type'] ?? $inputParams['template_id'] ?? '';
            if (!empty($cameraType)) {
                // 兼容旧值映射到新的template_id
                $cameraType = $this->mapJimengCameraTemplateId($cameraType);
                $body['template_id'] = $cameraType;
            }
            // camera_strength: 运镜强度（前端传camera_amplitude，API接收camera_strength）
            $amplitude = $inputParams['camera_amplitude'] ?? $inputParams['camera_strength'] ?? 'medium';
            $body['camera_strength'] = $this->mapJimengCameraStrength($amplitude);
            // seed: 随机种子
            $body['seed'] = intval($inputParams['seed'] ?? -1);
            // frames: 帧数（24*秒+1），支持121(5s)和241(10s)
            $duration = intval($inputParams['duration'] ?? 5);
            $body['frames'] = ($duration >= 10) ? 241 : 121;
        } else {
            // 非运镜模式：使用duration和aspect_ratio
            $duration = $inputParams['duration'] ?? '';
            if (!empty($duration)) {
                $body['duration'] = intval($duration);
            }
        }
        
        \think\facade\Log::info('buildJimengVideoRequestBody: 构建即梦视频请求体', [
            'model_code' => $modelCode,
            'video_mode' => $videoMode,
            'req_key' => $reqKey,
            'has_prompt' => !empty($prompt),
            'image_count' => count($imageUrls),
            'body_keys' => array_keys($body)
        ]);
        
        return $body;
    }
    
    /**
     * 推断即梦视频生成模式
     * @param array $inputParams 输入参数
     * @return string 视频模式
     */
    protected function inferJimengVideoMode($inputParams)
    {
        // 运镜模式：有首帧图 + camera_type
        $hasFirstFrame = !empty($inputParams['first_frame_image']) || !empty($inputParams['image_url']) || !empty($inputParams['image']);
        $hasCameraType = !empty($inputParams['camera_type']);
        
        if ($hasFirstFrame && $hasCameraType) {
            return 'camera_motion';
        }
        
        // 首尾帧模式
        $hasLastFrame = !empty($inputParams['last_frame_image']) || !empty($inputParams['tail_image_url']) || !empty($inputParams['last_frame']);
        if ($hasFirstFrame && $hasLastFrame) {
            return 'first_last_frame';
        }
        
        // 首帧模式
        if ($hasFirstFrame) {
            return 'first_frame';
        }
        
        // 默认：文生视频
        return 'text_to_video';
    }
    
    /**
     * 映射运镜类型到火山引擎API的template_id
     * 兼容旧的camera_type值，映射到新的模板ID
     * 
     * @param string $cameraType 前端传入的运镜类型
     * @return string API接受的template_id
     */
    protected function mapJimengCameraTemplateId($cameraType)
    {
        // 新版有效的template_id值（直接透传）
        $validTemplateIds = [
            'hitchcock_dolly_in', 'hitchcock_dolly_out',
            'robo_arm', 'dynamic_orbit', 'central_orbit',
            'crane_push', 'quick_pull_back',
            'counterclockwise_swivel', 'clockwise_swivel',
            'handheld', 'rapid_push_pull',
        ];
        
        if (in_array($cameraType, $validTemplateIds)) {
            return $cameraType;
        }
        
        // 旧值到新值的映射（兼容已有数据）
        $legacyMapping = [
            'horizontal_right'  => 'handheld',
            'horizontal_left'   => 'handheld',
            'vertical_up'       => 'crane_push',
            'vertical_down'     => 'crane_push',
            'zoom_in'           => 'hitchcock_dolly_in',
            'zoom_out'          => 'hitchcock_dolly_out',
            'pan_right'         => 'clockwise_swivel',
            'pan_left'          => 'counterclockwise_swivel',
            'tilt_up'           => 'crane_push',
            'tilt_down'         => 'quick_pull_back',
            'hitchcock'         => 'hitchcock_dolly_in',
            'dynamic_surround'  => 'dynamic_orbit',
            'mechanical_arm'    => 'robo_arm',
        ];
        
        return $legacyMapping[$cameraType] ?? 'handheld';
    }
    
    /**
     * 映射运镜幅度/强度到火山引擎API的camera_strength
     * 兼容数值型的camera_amplitude和字符串型的camera_strength
     * 
     * @param mixed $amplitude 前端传入的幅度值
     * @return string API接受的强度值 (weak/medium/strong)
     */
    protected function mapJimengCameraStrength($amplitude)
    {
        // 已经是有效的强度字符串
        if (in_array($amplitude, ['weak', 'medium', 'strong'])) {
            return $amplitude;
        }
        
        // 数值映射: 1=weak, 2=medium, 3=strong
        $val = intval($amplitude);
        if ($val <= 1) return 'weak';
        if ($val >= 3) return 'strong';
        return 'medium';
    }
    
    /**
     * 调用即梦AI视频生成API（火山引擎CV平台）
     * 复用即梦图片API的V4签名和HTTP发送逻辑，但使用视频特定的请求体
     * 
     * @param array $model 模型信息
     * @param array $apiKeyConfig API Key配置
     * @param array $inputParams 输入参数
     * @return array 响应结果
     */
    protected function callJimengVideoApi($model, $apiKeyConfig, $inputParams)
    {
        $jimengConfig = config('aivideo.jimeng') ?? [];
        $apiUrl = $jimengConfig['api_url'] ?? 'https://visual.volcengineapi.com';
        $submitAction = $jimengConfig['submit_action'] ?? 'CVSync2AsyncSubmitTask';
        $apiVersion = $jimengConfig['api_version'] ?? '2022-08-31';
        $region = $jimengConfig['region'] ?? 'cn-north-1';
        $service = $jimengConfig['service'] ?? 'cv';
        
        $accessKey = $apiKeyConfig['api_key_decrypted'];
        $secretKey = $apiKeyConfig['api_secret_decrypted'] ?? '';
        
        if (empty($accessKey) || empty($secretKey)) {
            return [
                'status' => 0,
                'error_code' => 'JIMENG_KEY_MISSING',
                'msg' => '即梦AI需要配置火山引擎Access Key和Secret Key'
            ];
        }
        
        // 构建视频生成请求体
        $requestBody = $this->buildJimengVideoRequestBody($model['model_code'], $inputParams);
        $bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
        
        // 拼接URL及query参数
        $queryParams = [
            'Action' => $submitAction,
            'Version' => $apiVersion,
        ];
        $queryString = http_build_query($queryParams);
        $fullUrl = $apiUrl . '?' . $queryString;
        
        // 生成火山引擎V4签名头
        $headers = $this->generateVolcengineV4Headers(
            $accessKey, $secretKey, $region, $service,
            'POST', '/', $queryString, $bodyJson
        );
        
        \think\facade\Log::info('即梦视频API提交任务请求', [
            'url' => $fullUrl,
            'model' => $model['model_code'],
            'body' => $requestBody
        ]);
        
        // 发送HTTP请求
        $response = $this->sendHttpRequest($fullUrl, 'POST', $headers, $requestBody);
        
        \think\facade\Log::info('即梦视频API提交任务响应', [
            'http_code' => $response['http_code'],
            'body' => is_array($response['body']) ? json_encode($response['body'], JSON_UNESCAPED_UNICODE) : substr((string)$response['body'], 0, 2000)
        ]);
        
        $body = $response['body'];
        
        // 即梦API成功状态码为 10000
        if (is_array($body) && isset($body['code']) && $body['code'] == 10000) {
            $taskId = $body['data']['task_id'] ?? '';
            if (!empty($taskId)) {
                return [
                    'status' => 1,
                    'async' => true,
                    'async_type' => 'jimeng',
                    'external_task_id' => $taskId,
                    'outputs' => [],
                    'tokens' => 0,
                    'cost' => 0
                ];
            }
        }
        
        // 错误处理
        $errorMsg = '';
        $errorCode = 'JIMENG_VIDEO_API_ERROR';
        if (is_array($body)) {
            $errorMsg = $body['message'] ?? 'API请求失败';
            $errorCode = 'JIMENG_VIDEO_' . ($body['code'] ?? $response['http_code']);
        } else {
            $errorMsg = '即梦视频API请求失败(HTTP ' . $response['http_code'] . ')';
        }
        
        return [
            'status' => 0,
            'error_code' => $errorCode,
            'msg' => $errorMsg
        ];
    }
    
    /**
     * 检查是否为阿里云百炼Wan图像生成/编辑模型
     * 这些模型需要将图片URL转为Base64编码
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商标识
     * @return bool
     */
    protected function isAliyunWanImageModel($modelCode, $providerCode)
    {
        // 必须是阿里云/百炼供应商
        if (!in_array($providerCode, ['aliyun', 'dashscope', 'alibaba', 'bailian'])) {
            return false;
        }
        
        // Wan图像相关模型
        $wanImageModels = [
            'wan2.6-image',
            'wan2.5-i2i-preview',
            'wanx2.1-imageedit',
            'wanx2.1-t2i',
            'wanx2.1-i2i',
            'tongyi_wanxiang',
        ];
        
        foreach ($wanImageModels as $prefix) {
            if (strpos($modelCode, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查是否为阿里云百炼Wan视频生成模型
     * 这些模型使用 DashScope model+input+parameters 格式，且图片字段名为 img_url
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商标识
     * @return bool
     */
    protected function isAliyunWanVideoModel($modelCode, $providerCode)
    {
        // 必须是阿里云/百炼供应商
        if (!in_array($providerCode, ['aliyun', 'dashscope', 'alibaba', 'bailian'])) {
            return false;
        }
        
        // Wan视频生成相关模型
        $wanVideoModels = [
            'wan2.6-i2v-flash',
            'wan2.6-i2v',
            'wan2.5-i2v-preview',
            'wan2.6-t2v',
            'wan2.5-t2v-preview',
            'wan2.1-i2v',
        ];
        
        foreach ($wanVideoModels as $prefix) {
            if (strpos($modelCode, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 为阿里云百炼Wan视频模型构建请求体
     * DashScope视频生成API格式:
     * {
     *   "model": "wan2.6-i2v-flash",
     *   "input": {
     *     "img_url": "https://...",
     *     "prompt": "视频内容描述"
     *   },
     *   "parameters": {
     *     "resolution": "720P",
     *     "duration": 5
     *   }
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array 符合API格式的请求体
     */
    protected function buildAliyunWanVideoRequestBody($modelCode, $inputParams)
    {
        $input = [];
        $parameters = [];
        
        // 映射图片URL字段：内部使用 image，DashScope API 要求 img_url
        $imageUrl = $inputParams['image'] ?? $inputParams['img_url'] ?? $inputParams['image_url'] ?? '';
        if (!empty($imageUrl)) {
            $input['img_url'] = $imageUrl;
        }
        
        // 提示词
        if (!empty($inputParams['prompt'])) {
            $input['prompt'] = $inputParams['prompt'];
        }
        
        // 视频参数
        if (!empty($inputParams['resolution'])) {
            $parameters['resolution'] = $inputParams['resolution'];
        }
        if (!empty($inputParams['duration'])) {
            $parameters['duration'] = intval($inputParams['duration']);
        }
        if (!empty($inputParams['fps'])) {
            $parameters['fps'] = intval($inputParams['fps']);
        }
        if (!empty($inputParams['seed'])) {
            $parameters['seed'] = intval($inputParams['seed']);
        }
        
        $body = [
            'model' => $modelCode,
            'input' => $input,
        ];
        
        if (!empty($parameters)) {
            $body['parameters'] = $parameters;
        }
        
        \think\facade\Log::info('buildAliyunWanVideoRequestBody: 构建请求体', [
            'model_code' => $modelCode,
            'has_image' => !empty($imageUrl),
            'has_prompt' => !empty($inputParams['prompt']),
        ]);
        
        return $body;
    }
    
    /**
     * 为阿里云百炼Wan图像模型构建请求体
     * 阿里云百炼API要求图片通过Base64编码传递
     * 
     * API格式:
     * {
     *   "model": "wanx2.1-t2i-turbo",
     *   "input": {
     *     "prompt": "描述文字",
     *     "negative_prompt": "负面描述"
     *   },
     *   "parameters": {
     *     "size": "1024*1024"
     *   }
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array 符合API格式的请求体
     */
    protected function buildAliyunWanRequestBody($modelCode, $inputParams)
    {
        // 将内部模型代码映射为阿里云百炼实际模型名称
        $actualModelName = $this->getAliyunWanActualModelName($modelCode);
        
        // 构建 input 部分
        $input = [];
        $parameters = [];
        
        // 处理提示词
        if (!empty($inputParams['prompt'])) {
            $input['prompt'] = $inputParams['prompt'];
        }
        
        // 处理负面提示词
        if (!empty($inputParams['negative_prompt'])) {
            $input['negative_prompt'] = $inputParams['negative_prompt'];
        }
        
        // 处理参考图像 - 需要转为Base64
        $imageUrl = $inputParams['image'] ?? '';
        if (!empty($imageUrl)) {
            $base64Image = $this->convertImageUrlToBase64($imageUrl);
            if (!empty($base64Image)) {
                // 根据模型类型决定参数名
                if (strpos($modelCode, 'i2i') !== false || strpos($modelCode, 'imageedit') !== false || $modelCode === 'tongyi_wanxiang') {
                    // 图生图/图像编辑模型 - 使用images数组格式
                    $input['images'] = [$base64Image];
                } else {
                    // 文生图模型的参考图
                    $input['ref_image'] = $base64Image;
                }
            } else {
                \think\facade\Log::warning('buildAliyunWanRequestBody: 图片转为Base64失败', [
                    'model' => $modelCode,
                    'image_url' => $imageUrl
                ]);
            }
        }
        
        // 处理参数
        // 优先处理ratio（输出比例），根据比例计算尺寸
        if (!empty($inputParams['ratio'])) {
            $ratioSize = $this->convertRatioToSize($inputParams['ratio'], $actualModelName);
            if (!empty($ratioSize)) {
                $parameters['size'] = $ratioSize;
            }
        } elseif (!empty($inputParams['size'])) {
            // 阿里云百炼API要求size格式为 width*height，需要转换简写格式
            $parameters['size'] = $this->convertSizeFormat($inputParams['size'], $actualModelName);
        }
        if (!empty($inputParams['n'])) {
            $parameters['n'] = intval($inputParams['n']);
        }
        if (!empty($inputParams['seed'])) {
            $parameters['seed'] = intval($inputParams['seed']);
        }
        
        $body = [
            'model' => $actualModelName,
            'input' => $input,
        ];
        
        if (!empty($parameters)) {
            $body['parameters'] = $parameters;
        }
        
        \think\facade\Log::info('buildAliyunWanRequestBody: 构建请求体', [
            'model_code' => $modelCode,
            'actual_model' => $actualModelName,
            'has_image' => !empty($imageUrl)
        ]);
        
        return $body;
    }
    
    /**
     * 获取阿里云百炼Wan模型的实际API模型名称
     * 将内部模型代码映射为阿里云百炼平台实际使用的模型名称
     *
     * @param string $modelCode 内部模型代码
     * @return string 阿里云百炼实际模型名称
     */
    protected function getAliyunWanActualModelName($modelCode)
    {
        // 模型名称映射表 - 根据阿里云百炼官方文档
        // 参考: https://help.aliyun.com/zh/model-studio/wan2-5-image-edit-api-reference
        $modelMapping = [
            // 文生图模型
            'wan2.6-image' => 'wanx-v1',
            // 图生图/图像编辑模型 - 官方名称为 wan2.5-i2i-preview
            'wan2.5-i2i-preview' => 'wan2.5-i2i-preview',
            // 图像编辑模型
            'wanx2.1-imageedit' => 'wan2.5-i2i-preview',
            // 通义万相图生图（历史模型）
            'tongyi_wanxiang' => 'wan2.5-i2i-preview',
        ];

        return $modelMapping[$modelCode] ?? $modelCode;
    }

    /**
     * 将比例（如 2:3）转换为尺寸（如 768*1152）
     * 根据模型限制计算合适的尺寸
     *
     * @param string $ratio 比例字符串，如 "2:3"
     * @param string $modelName 模型名称
     * @return string 转换后的width*height格式
     */
    protected function convertRatioToSize($ratio, $modelName = '')
    {
        // 解析比例
        if (!preg_match('/^(\d+):(\d+)$/', $ratio, $matches)) {
            return '';
        }
        
        $ratioWidth = intval($matches[1]);
        $ratioHeight = intval($matches[2]);
        
        // wan2.5-i2i-preview 模型尺寸限制：768*768 到 1280*1280
        if ($modelName === 'wan2.5-i2i-preview') {
            $minSize = 768;
            $maxSize = 1280;
            $minPixels = $minSize * $minSize;      // 589824
            $maxPixels = $maxSize * $maxSize;      // 1638400
            
            // 根据比例计算尺寸，优先使用最大像素
            // 计算比例因子：width / height = ratioWidth / ratioHeight
            // width * height <= maxPixels
            // 解方程：height^2 = maxPixels * ratioHeight / ratioWidth
            $height = intval(sqrt($maxPixels * $ratioHeight / $ratioWidth));
            $width = intval($height * $ratioWidth / $ratioHeight);
            
            // 确保在限制范围内
            $width = max($minSize, min($maxSize, $width));
            $height = max($minSize, min($maxSize, $height));
            
            // 检查像素数是否在范围内
            $pixels = $width * $height;
            if ($pixels > $maxPixels) {
                // 按比例缩小
                $scale = sqrt($maxPixels / $pixels);
                $width = intval($width * $scale);
                $height = intval($height * $scale);
            } elseif ($pixels < $minPixels) {
                // 按比例放大
                $scale = sqrt($minPixels / $pixels);
                $width = intval($width * $scale);
                $height = intval($height * $scale);
            }
            
            // 最终确保在限制范围内
            $width = max($minSize, min($maxSize, $width));
            $height = max($minSize, min($maxSize, $height));
            
            return $width . '*' . $height;
        }
        
        // 默认处理：使用1024作为基准
        $baseSize = 1024;
        if ($ratioWidth >= $ratioHeight) {
            $width = $baseSize;
            $height = intval($baseSize * $ratioHeight / $ratioWidth);
        } else {
            $height = $baseSize;
            $width = intval($baseSize * $ratioWidth / $ratioHeight);
        }
        
        return $width . '*' . $height;
    }

    /**
     * 转换size参数格式
     * 将简写格式（如2K、1K）转换为阿里云百炼API要求的width*height格式
     *
     * @param string $size 原始size参数
     * @param string $modelName 模型名称
     * @return string 转换后的width*height格式
     */
    protected function convertSizeFormat($size, $modelName = '')
    {
        // 如果已经是width*height格式，验证是否在允许范围内
        if (preg_match('/^(\d+)\*(\d+)$/', $size, $matches)) {
            $width = intval($matches[1]);
            $height = intval($matches[2]);
            return $this->adjustSizeToLimits($width, $height, $modelName);
        }

        // wan2.5-i2i-preview 模型尺寸限制：768*768 到 1280*1280
        if ($modelName === 'wan2.5-i2i-preview') {
            $sizeMapping = [
                '1K' => '1024*1024',
                '2K' => '1280*1280',  // 最大限制
                '4K' => '1280*1280',  // 最大限制
                '720p' => '1280*720',
                '1080p' => '1280*1024', // 调整以适应限制
                '1080P' => '1280*1024',
                '480p' => '854*480',
                '480P' => '854*480',
            ];
        } else {
            // 默认尺寸映射
            $sizeMapping = [
                '1K' => '1024*1024',
                '2K' => '1440*1440',
                '4K' => '2048*2048',
                '720p' => '1280*720',
                '1080p' => '1920*1080',
                '1080P' => '1920*1080',
                '480p' => '854*480',
                '480P' => '854*480',
            ];
        }

        return $sizeMapping[$size] ?? '1024*1024';
    }

    /**
     * 根据模型限制调整尺寸
     * wan2.5-i2i-preview限制：最小768*768，最大1280*1280
     *
     * @param int $width 宽度
     * @param int $height 高度
     * @param string $modelName 模型名称
     * @return string 调整后的width*height格式
     */
    protected function adjustSizeToLimits($width, $height, $modelName)
    {
        // wan2.5-i2i-preview 模型尺寸限制
        if ($modelName === 'wan2.5-i2i-preview') {
            $minPixels = 768 * 768;      // 589824
            $maxPixels = 1280 * 1280;    // 1638400

            $pixels = $width * $height;

            // 如果超出限制，调整到最大允许值
            if ($pixels > $maxPixels) {
                // 保持宽高比，缩放到最大允许像素
                $ratio = sqrt($maxPixels / $pixels);
                $width = intval($width * $ratio);
                $height = intval($height * $ratio);
                // 确保不超过1280
                $width = min($width, 1280);
                $height = min($height, 1280);
            } elseif ($pixels < $minPixels) {
                // 保持宽高比，放大到最小允许像素
                $ratio = sqrt($minPixels / $pixels);
                $width = intval($width * $ratio);
                $height = intval($height * $ratio);
                // 确保不小于768
                $width = max($width, 768);
                $height = max($height, 768);
            }
        }

        return $width . '*' . $height;
    }

    /**
     * 将图片URL转为Base64编码（带data URI scheme）
     *
     * @param string $imageUrl 图片URL
     * @return string Base64编码的图片（格式：data:image/jpeg;base64,xxx）
     */
    protected function convertImageUrlToBase64($imageUrl)
    {
        try {
            // 下载图片
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            
            if ($httpCode !== 200 || empty($imageData)) {
                \think\facade\Log::warning('convertImageUrlToBase64: 下载图片失败', [
                    'url' => $imageUrl,
                    'http_code' => $httpCode
                ]);
                return '';
            }
            
            // 检测图片类型
            if (empty($contentType)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $contentType = $finfo->buffer($imageData);
            }
            
            // 构建Base64 data URI
            $base64 = base64_encode($imageData);
            return 'data:' . $contentType . ';base64,' . $base64;
            
        } catch (\Exception $e) {
            \think\facade\Log::error('convertImageUrlToBase64: 转换异常 ' . $e->getMessage(), [
                'url' => $imageUrl
            ]);
            return '';
        }
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
    protected function sendHttpRequest($url, $method = 'POST', $headers = [], $data = null, $timeout = 300)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
                // DashScope视频生成响应（如爱诗PixVerse）：通过 output.video_url 返回视频
                if (isset($response['output']['video_url'])) {
                    $outputs[] = [
                        'type' => 'video',
                        'url' => $response['output']['video_url'],
                        'thumbnail' => '',
                        'duration' => 0
                    ];
                }
                // DashScope图片生成响应：通过 output.results 数组返回图片
                elseif (isset($response['output']['results'])) {
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
            
            // 保存到临时目录
            $tempDir = runtime_path() . 'temp/persist_webp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFilename = 'gen_b64_' . date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8) . '.png';
            $tempPath = $tempDir . $tempFilename;
            file_put_contents($tempPath, $imageData);
            
            // WebP 压缩
            $uploadPath = $tempPath;
            $webpPath = \app\common\Pic::convertToWebp($tempPath, ImagePersistService::DEFAULT_QUALITY);
            if ($webpPath && $webpPath !== $tempPath) {
                $uploadPath = $webpPath;
            }
            
            // 复制到上传目录并通过 Pic::uploadoss 上传
            $ext = pathinfo($uploadPath, PATHINFO_EXTENSION) ?: 'webp';
            $subDir = 'upload/generation/' . date('Ymd');
            $targetDir = ROOT_PATH . $subDir;
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $targetFilename = substr(md5(uniqid((string)mt_rand(), true)), 0, 10) . '.' . $ext;
            copy($uploadPath, $targetDir . '/' . $targetFilename);
            
            // 上传到OSS
            $localUrl = PRE_URL . '/' . $subDir . '/' . $targetFilename;
            $ossUrl = \app\common\Pic::uploadoss($localUrl, false, false);
            
            // 清理临时文件
            @unlink($tempPath);
            if ($webpPath && $webpPath !== $tempPath) {
                @unlink($webpPath);
            }
            
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
     * 对输出数组中的图片类型进行 WebP 压缩转存
     * 视频/音频类型保持现有逻辑不变
     *
     * @param array $outputs 输出数组
     * @param int   $aid     平台 ID
     * @return array 处理后的输出数组
     */
    protected function persistImageOutputs(array $outputs, int $aid = 0): array
    {
        $persistService = new ImagePersistService();
        foreach ($outputs as &$output) {
            $type = $output['type'] ?? '';
            $url = $output['url'] ?? '';
            if ($type === 'image' && !empty($url)) {
                try {
                    $persistedUrl = $persistService->persistAndCompress($url, $aid, 'generation');
                    if (!empty($persistedUrl)) {
                        $output['url'] = $persistedUrl;
                        // 更新 file_size 为压缩后的实际大小
                        $output['file_size'] = ImagePersistService::getFileSize($persistedUrl);
                    }
                } catch (\Exception $e) {
                    \think\facade\Log::warning('persistImageOutputs: 单张转存失败 - ' . $e->getMessage(), [
                        'url' => substr($url, 0, 120),
                    ]);
                    // 失败时保留原 URL
                }
            }
        }
        unset($output);
        return $outputs;
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
     * 支持: Seedance视频生成、DashScope图片生成、volcengine/doubao SeeDream超时兜底
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
                } elseif ($this->isJimengModel($providerCode)) {
                    // 即梦AI图片生成模型
                    $this->pollJimengTaskStatus($record);
                } elseif ($this->isVolcengineSeeDreamModel($modelCode, $providerCode)) {
                    // 火山引擎/豆包 SeeDream 图片生成模型（同步调用，仅做超时兜底）
                    $this->checkSeeDreamTimeout($record);
                }
                // 其他模型暂不支持异步轮询，跳过
            }
            
            // 额外扫描：查找 volcengine/doubao 模型中没有 task_id 但卡在处理中的记录（同步调用崩溃场景）
            $stuckSeeDreamRecords = Db::name('generation_record')
                ->alias('r')
                ->leftJoin('model_info m', 'r.model_id = m.id')
                ->leftJoin('model_provider p', 'm.provider_id = p.id')
                ->field('r.id, r.task_id, r.model_id, r.model_code, r.start_time, p.provider_code')
                ->where($where)
                ->where('r.status', GenerationRecord::STATUS_PROCESSING)
                ->where(function($query) {
                    $query->whereNull('r.task_id')->whereOr('r.task_id', '');
                })
                ->whereIn('p.provider_code', ['volcengine', 'doubao'])
                ->limit(10)
                ->select()
                ->toArray();
            
            foreach ($stuckSeeDreamRecords as $record) {
                $modelCode = $record['model_code'] ?? '';
                $providerCode = $record['provider_code'] ?? '';
                if ($this->isVolcengineSeeDreamModel($modelCode, $providerCode)) {
                    $this->checkSeeDreamTimeout($record);
                }
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
        return in_array($providerCode, ['aliyun', 'dashscope', 'alibaba', 'aishi']);
    }
    
    /**
     * 检查是否为火山引擎/豆包 SeeDream 图片生成模型
     * SeeDream模型通过同步SSE调用，不支持异步轮询，仅用于超时兜底检测
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商代码
     * @return bool
     */
    protected function isVolcengineSeeDreamModel($modelCode, $providerCode)
    {
        // 必须是volcengine或doubao供应商
        if (!in_array($providerCode, ['volcengine', 'doubao'])) {
            return false;
        }
        // 排除Seedance视频模型（已有单独的轮询逻辑）
        if ($this->isSeedanceVideoModel($modelCode)) {
            return false;
        }
        // 剩下的volcengine/doubao模型即为SeeDream图片模型
        return true;
    }
    
    /**
     * 检查 SeeDream 模型记录是否超时，超时则自动标记为失败
     * SeeDream模型通过同步SSE调用，单图正常应在60~90秒内完成
     * 多图(max_images>1)通过队列异步执行，每张约60~90秒，总耗时可达540~810秒
     * 
     * 超时阈值按 max_images 动态计算：
     *   单图：600秒（10分钟）
     *   多图：max_images × 150 + 300 秒（预留充足余量，避免与队列worker竞态）
     * 
     * @param array $record 生成记录（数据库行）
     * @return void
     */
    protected function checkSeeDreamTimeout($record)
    {
        $startTime = intval($record['start_time'] ?? 0);
        if ($startTime <= 0) {
            return;
        }
        
        // 从 input_params 解析 max_images 以动态计算超时阈值
        $maxImages = 1;
        $inputParams = $record['input_params'] ?? null;
        if (empty($inputParams) && !empty($record['id'])) {
            // 如果record数组中没有input_params，单独查询
            $inputParams = Db::name('generation_record')->where('id', $record['id'])->value('input_params');
        }
        if (is_string($inputParams)) {
            $inputParams = json_decode($inputParams, true);
        }
        if (is_array($inputParams)) {
            $seqOpts = $inputParams['sequential_image_generation_options'] ?? null;
            if (is_string($seqOpts)) {
                $seqOpts = json_decode($seqOpts, true);
            }
            if (is_array($seqOpts) && isset($seqOpts['max_images'])) {
                $maxImages = max(1, intval($seqOpts['max_images']));
            }
        }
        
        // 动态超时：单图600秒，多图按 max_images * 150 + 300 计算
        // 确保超过 curl 超时（max_images * 120 + 60）+ 队列调度延迟
        if ($maxImages > 1) {
            $timeoutSeconds = $maxImages * 150 + 300;
        } else {
            $timeoutSeconds = 600; // 单图10分钟
        }
        
        $elapsed = time() - $startTime;
        
        if ($elapsed > $timeoutSeconds) {
            \think\facade\Log::warning('checkSeeDreamTimeout: SeeDream记录超时，自动标记为失败', [
                'record_id' => $record['id'],
                'model_code' => $record['model_code'] ?? '',
                'start_time' => date('Y-m-d H:i:s', $startTime),
                'elapsed_seconds' => $elapsed,
                'timeout_seconds' => $timeoutSeconds,
                'max_images' => $maxImages,
            ]);
            
            $recordModel = GenerationRecord::find($record['id']);
            if ($recordModel && $recordModel->status == GenerationRecord::STATUS_PROCESSING) {
                $recordModel->markFailed('TIMEOUT', '生成任务超时（超过' . intval($timeoutSeconds / 60) . '分钟），请重试');
            }
        }
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
        if ($record['status'] == GenerationRecord::STATUS_PROCESSING) {
            $modelCode = $record['model_code'] ?? '';
            $providerCode = $record['provider_code'] ?? '';
            
            // 异步轮询类模型（需要task_id）
            if (!empty($record['task_id'])) {
                if ($this->isSeedanceVideoModel($modelCode)) {
                    // Seedance视频模型
                    $pollResult = $this->pollSeedanceTaskStatus($record);
                    $progress = $pollResult['progress'] ?? 0;
                } elseif ($this->isJimengModel($providerCode)) {
                    // 即梦AI图片生成模型
                    $pollResult = $this->pollJimengTaskStatus($record);
                    $progress = $pollResult['progress'] ?? 0;
                } elseif ($this->isDashScopeModel($providerCode)) {
                    // DashScope图片生成模型
                    $pollResult = $this->pollDashScopeTaskStatus($record);
                    $progress = $pollResult['progress'] ?? 0;
                }
            }
            
            // 火山引擎/豆包 SeeDream 图片模型：同步调用，无需task_id，仅做超时兜底检测
            if ($this->isVolcengineSeeDreamModel($modelCode, $providerCode)) {
                $this->checkSeeDreamTimeout($record);
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
                // 任务成功，解析结果（支持图片和视频两种输出）
                $outputs = [];
                
                // DashScope视频生成响应（如爱诗PixVerse）：通过 output.video_url 返回视频
                if (isset($result['output']['video_url']) && !empty($result['output']['video_url'])) {
                    $outputs[] = [
                        'type' => 'video',
                        'url' => $result['output']['video_url'],
                        'thumbnail' => '',
                        'duration' => 0
                    ];
                }
                // DashScope图片生成响应：通过 output.results 数组返回图片
                elseif (isset($result['output']['results']) && is_array($result['output']['results'])) {
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
                    // 对图片类型的输出执行 WebP 压缩转存
                    $outputs = $this->persistImageOutputs($outputs, $record['aid'] ?? 0);
                    GenerationOutput::createOutputs($record['id'], $outputs);
                    \think\facade\Log::info('pollDashScopeTaskStatus 已保存outputs', [
                        'record_id' => $record['id'],
                        'outputs_count' => count($outputs)
                    ]);
                } else {
                    \think\facade\Log::warning('pollDashScopeTaskStatus: 任务成功但未获取到输出内容', [
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
                
                \think\facade\Log::info('DashScope生成成功', [
                    'record_id' => $record['id'],
                    'outputs_count' => count($outputs),
                    'cost_time' => $costTime
                ]);
            } elseif ($taskStatus == 'FAILED') {
                // 任务失败
                $errorCode = $result['output']['code'] ?? $result['code'] ?? 'DASHSCOPE_FAILED';
                $errorMsg = $result['output']['message'] ?? $result['message'] ?? '生成失败';
                
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed($errorCode, $errorMsg);
                }
                
                \think\facade\Log::warning('DashScope生成失败', [
                    'record_id' => $record['id'],
                    'error_code' => $errorCode,
                    'error_msg' => $errorMsg
                ]);
            } elseif ($taskStatus == 'CANCELED' || $taskStatus == 'CANCELLED') {
                // 任务取消
                $recordModel = GenerationRecord::find($record['id']);
                if ($recordModel) {
                    $recordModel->markFailed('TASK_CANCELLED', '生成任务已被取消');
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
     * 将图片压缩并统一转换为 WebP 格式输出（质量82）
     * 超过 800px 宽度的图片同时缩放到 800px 宽
     * 所有图片均强制转换为 WebP 格式，不依赖尺寸阈值
     * 
     * @param string $coverUrl 封面图URL（已转存到本地/OSS的URL）
     * @param AttachmentTransferService $transferService 转存服务实例
     * @return string|false 压缩后的新URL，或 false 表示压缩失败
     */
    protected function compressCoverImage($coverUrl, $transferService)
    {
        $maxWidth = 800;
        $quality = 82;
        
        // 检测 PHP 环境是否支持 webp
        if (!function_exists('imagewebp')) {
            Log::warning('compressCoverImage: PHP环境不支持imagewebp，跳过转换');
            return false;
        }
        
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
        
        // 如果已经是 webp 且宽度不超过阈值，无需处理
        if ($mimeType === 'image/webp' && $srcWidth <= $maxWidth) {
            @unlink($tempFile);
            Log::info('compressCoverImage: 已是WebP且宽度未超阈值，跳过', ['width' => $srcWidth]);
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
        
        // 判断是否需要缩放
        $needResize = ($srcWidth > $maxWidth);
        $newWidth = $needResize ? $maxWidth : $srcWidth;
        $newHeight = $needResize ? intval($srcHeight * $maxWidth / $srcWidth) : $srcHeight;
        
        if ($needResize) {
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
            imagedestroy($srcImage);
            $outputImage = $thumbnail;
        } else {
            // 无需缩放，直接使用源图像转换格式
            $outputImage = $srcImage;
        }
        
        // 统一输出为 WebP
        $compressedFile = $tempDir . 'cover_compressed_' . md5($coverUrl . time() . mt_rand()) . '.webp';
        imagewebp($outputImage, $compressedFile, $quality);
        
        // 释放资源
        imagedestroy($outputImage);
        @unlink($tempFile);
        
        if (!file_exists($compressedFile) || filesize($compressedFile) === 0) {
            Log::warning('compressCoverImage: WebP输出后文件无效');
            return false;
        }
        
        Log::info('compressCoverImage: 压缩/转换完成', [
            'format' => 'webp',
            'originalSize' => strlen($content),
            'compressedSize' => filesize($compressedFile),
            'originalDimensions' => $srcWidth . 'x' . $srcHeight,
            'newDimensions' => $newWidth . 'x' . $newHeight,
            'resized' => $needResize
        ]);
        
        // 上传压缩后的图片到存储
        try {
            // 使用反射调用 uploadToStorage 方法（protected方法）
            $reflection = new \ReflectionMethod($transferService, 'uploadToStorage');
            $reflection->setAccessible(true);
            $newUrl = $reflection->invoke($transferService, $compressedFile, '', 'webp');
            
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
        
        // 自动标签识别：异步推入队列（不阻塞保存主流程）
        try {
            // 从 sysset 表读取配置，优先于 config 文件
            $autoTagEnabled = false;
            $autoTagRow = Db::name('sysset')->where('name', 'auto_tagging')->value('value');
            if ($autoTagRow) {
                $autoTagConfig = json_decode($autoTagRow, true) ?: [];
                $autoTagEnabled = !empty($autoTagConfig['auto_tag_enabled']);
            } else {
                // 回退到 config 文件
                $autoTagConfig = \think\facade\Config::get('auto_tagging', []);
                $autoTagEnabled = !empty($autoTagConfig['auto_tag_enabled']);
            }
            if ($autoTagEnabled) {
                $autoTagService = new AutoTaggingService();
                $sourceImageUrl = $autoTagService->getSourceImageUrl(array_merge($saveData, [
                    'original_images' => $data['original_images'] ?? '',
                ]));
                if (!empty($sourceImageUrl)) {
                    $autoTagService->triggerAutoTagging($id, $sourceImageUrl);
                }
            }
        } catch (\Exception $e) {
            Log::warning('saveTemplate: 自动标签识别触发失败（不影响保存）', [
                'template_id' => $id,
                'error' => $e->getMessage()
            ]);
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
     * 将视频封面转换为动态预览图（Animated WebP）并存储
     * 使用FFmpeg截取视频前5秒，缩放到480px宽，10fps，生成优化的Animated WebP
     * 若FFmpeg不支持libwebp_anim编码器，则跳过生成并记录日志
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
        $output = [];
        exec(escapeshellarg($ffmpegPath) . ' -encoders 2>/dev/null | grep libwebp_anim', $output, $retCode);
        if ($retCode !== 0 || empty($output)) {
            Log::warning('generateGifCover: FFmpeg不支持libwebp_anim编码器，跳过动态封面生成');
            return false;
        }
        
        $tempDir = defined('ROOT_PATH') ? ROOT_PATH . 'runtime/temp/gif/' : sys_get_temp_dir() . '/gif/';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        
        $uniqueId = md5($videoUrl . $templateId . time() . mt_rand());
        $tempVideo = $tempDir . 'src_' . $uniqueId . '.mp4';
        $tempOutput = $tempDir . 'cover_' . $uniqueId . '.webp';
        
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
            
            // 2. 使用 FFmpeg 截取前5秒生成 Animated WebP
            $webpCmd = sprintf(
                '%s -i %s -t 5 -vf "fps=10,scale=480:-1:flags=lanczos" -vcodec libwebp_anim -quality 75 -lossless 0 -loop 0 -an -y %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($tempVideo),
                escapeshellarg($tempOutput)
            );
            $encodeOutput = [];
            exec($webpCmd, $encodeOutput, $encodeRetCode);
            
            if ($encodeRetCode !== 0 || !file_exists($tempOutput) || filesize($tempOutput) < 100) {
                Log::warning('generateGifCover: Animated WebP生成失败', [
                    'returnCode' => $encodeRetCode,
                    'output' => implode("\n", array_slice($encodeOutput, -5))
                ]);
                @unlink($tempVideo);
                @unlink($tempOutput);
                return false;
            }
            
            // 3. 检查文件体积，超过2MB则降低质量重新生成
            if (filesize($tempOutput) > 2 * 1024 * 1024) {
                Log::info('generateGifCover: 动态封面超过2MB，降低质量重新生成', ['size' => filesize($tempOutput)]);
                @unlink($tempOutput);
                $webpCmdLQ = sprintf(
                    '%s -i %s -t 5 -vf "fps=10,scale=480:-1:flags=lanczos" -vcodec libwebp_anim -quality 65 -lossless 0 -loop 0 -an -y %s 2>&1',
                    escapeshellarg($ffmpegPath),
                    escapeshellarg($tempVideo),
                    escapeshellarg($tempOutput)
                );
                exec($webpCmdLQ, $lqOutput, $lqRetCode);
                if ($lqRetCode !== 0 || !file_exists($tempOutput) || filesize($tempOutput) < 100) {
                    Log::warning('generateGifCover: 低质量Animated WebP生成也失败');
                    @unlink($tempVideo);
                    @unlink($tempOutput);
                    return false;
                }
            }
            
            // 4. 上传动态封面到云存储
            $aid = Db::name('generation_scene_template')->where('id', $templateId)->value('aid') ?: (defined('aid') ? aid : 0);
            
            // 复制到upload目录
            $uploadDir = 'upload/generation_template/' . $aid . '/' . date('Ym') . '/';
            $localDir = defined('ROOT_PATH') ? ROOT_PATH . $uploadDir : $uploadDir;
            if (!is_dir($localDir)) {
                @mkdir($localDir, 0777, true);
            }
            
            $coverFilename = 'webp_cover_' . $uniqueId . '.webp';
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
                
                Log::info('generateGifCover: Animated WebP动态封面生成成功', [
                    'template_id' => $templateId,
                    'format' => 'webp',
                    'file_size' => filesize($tempOutput),
                    'cover_url' => substr($coverUrl, 0, 100)
                ]);
            }
            
            // 6. 清理临时文件
            @unlink($tempVideo);
            @unlink($tempOutput);
            
            return $coverUrl;
            
        } catch (\Exception $e) {
            @unlink($tempVideo);
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
    
    // ==================== Ollama 本地LLM相关方法 ====================
    
    /**
     * 检查是否为Ollama本地LLM模型
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商标识
     * @return bool
     */
    protected function isOllamaModel($modelCode, $providerCode = '')
    {
        if ($providerCode === 'ollama') {
            return true;
        }
        // Ollama模型编码通常包含冒号，如 qwen3:8b, llama3.1:8b
        $ollamaModels = array_keys(config('aivideo.ollama.models') ?? []);
        return in_array($modelCode, $ollamaModels);
    }
    
    /**
     * 调用Ollama本地LLM API
     * Ollama本地部署，无需API Key认证，直接通过HTTP POST请求
     * 
     * 支持三种模式：
     * 1. Chat API (/api/chat) - 文本生成/深度思考（多轮对话）
     * 2. Generate API (/api/generate) - 纯文本生成（单轮）
     * 3. Embeddings API (/api/embeddings) - 向量嵌入
     * 
     * @param array $model 模型信息
     * @param array $apiKeyConfig API Key配置（Ollama无需使用）
     * @param array $inputParams 输入参数
     * @return array 响应结果
     */
    protected function callOllamaApi($model, $apiKeyConfig, $inputParams)
    {
        $ollamaConfig = config('aivideo.ollama') ?? [];
        
        // 支持通过API Key配置中的自定义服务器地址覆盖默认值
        $authConfig = $model['auth_config'] ?? [];
        if (is_string($authConfig)) {
            $authConfig = json_decode($authConfig, true) ?: [];
        }
        $customUrl = $apiKeyConfig['api_key_decrypted'] ?? '';
        $apiUrl = (!empty($customUrl) && $customUrl !== 'ollama_local') 
            ? rtrim($customUrl, '/') 
            : ($ollamaConfig['api_url'] ?? 'http://127.0.0.1:11434');
        
        $modelCode = $model['model_code'];
        $timeout = $ollamaConfig['timeout'] ?? 120;
        $stream = $ollamaConfig['stream'] ?? false;
        
        // 根据模型类型选择endpoint
        $modelConfig = $ollamaConfig['models'][$modelCode] ?? [];
        $modelType = $modelConfig['type'] ?? 'chat';
        
        if ($modelType === 'embedding') {
            $endpoint = $ollamaConfig['embeddings_endpoint'] ?? '/api/embeddings';
            $requestBody = $this->buildOllamaEmbeddingRequestBody($modelCode, $inputParams);
        } else {
            // 文本生成和深度思考都使用Chat API
            $endpoint = $ollamaConfig['chat_endpoint'] ?? '/api/chat';
            $requestBody = $this->buildOllamaChatRequestBody($modelCode, $inputParams);
        }
        
        $fullUrl = $apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json'
        ];
        
        \think\facade\Log::info('Ollama API请求', [
            'url' => $fullUrl,
            'model' => $modelCode,
            'type' => $modelType,
            'body' => $requestBody
        ]);
        
        // 发送HTTP请求（使用自定义curl以支持更长超时）
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody, JSON_UNESCAPED_UNICODE));
        // Ollama本地服务无需SSL验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // 连接失败特殊处理（Ollama服务未启动）
        if ($httpCode == 0 || !empty($curlError)) {
            \think\facade\Log::error('Ollama服务连接失败', [
                'url' => $fullUrl,
                'curl_error' => $curlError
            ]);
            return [
                'status' => 0,
                'error_code' => 'OLLAMA_CONNECTION_FAILED',
                'msg' => 'Ollama本地服务连接失败，请确认Ollama服务已启动（ollama serve）并监听在 ' . $apiUrl
            ];
        }
        
        $responseBody = json_decode($responseRaw, true);
        
        \think\facade\Log::info('Ollama API响应', [
            'http_code' => $httpCode,
            'body' => $responseBody ? json_encode($responseBody, JSON_UNESCAPED_UNICODE) : substr((string)$responseRaw, 0, 2000)
        ]);
        
        if ($httpCode != 200) {
            $errorMsg = 'Ollama请求失败(HTTP ' . $httpCode . ')';
            if (is_array($responseBody) && isset($responseBody['error'])) {
                $errorMsg = $responseBody['error'];
            }
            // 常见错误提示
            if ($httpCode == 404) {
                $errorMsg .= '。模型 ' . $modelCode . ' 可能未下载，请执行: ollama pull ' . $modelCode;
            }
            return [
                'status' => 0,
                'error_code' => 'OLLAMA_HTTP_' . $httpCode,
                'msg' => $errorMsg
            ];
        }
        
        // 解析响应
        return $this->parseOllamaResponse($model, $responseBody, $modelType);
    }
    
    /**
     * 构建Ollama Chat API请求体
     * 
     * Ollama Chat API格式:
     * {
     *   "model": "qwen3:8b",
     *   "messages": [
     *     {"role": "system", "content": "系统提示词"},
     *     {"role": "user", "content": "用户输入"}
     *   ],
     *   "stream": false,
     *   "options": {
     *     "temperature": 0.7,
     *     "top_p": 0.9,
     *     "num_predict": 4096
     *   }
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array 符合API格式的请求体
     */
    protected function buildOllamaChatRequestBody($modelCode, $inputParams)
    {
        $ollamaConfig = config('aivideo.ollama') ?? [];
        $defaultParams = $ollamaConfig['default_params'] ?? [];
        $stream = $ollamaConfig['stream'] ?? false;
        
        $messages = [];
        
        // 系统提示词
        $systemPrompt = $inputParams['system_prompt'] ?? $inputParams['system'] ?? '';
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        // 用户消息 - 支持messages数组格式和简单prompt格式
        if (!empty($inputParams['messages']) && is_array($inputParams['messages'])) {
            // 多轮对话格式
            foreach ($inputParams['messages'] as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }
        } else {
            // 单轮prompt格式
            $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? $inputParams['content'] ?? '';
            if (!empty($prompt)) {
                $messages[] = ['role' => 'user', 'content' => $prompt];
            }
        }
        
        $body = [
            'model' => $modelCode,
            'messages' => $messages,
            'stream' => $stream,
        ];
        
        // 构建 options 参数
        $options = [];
        
        // temperature
        $temperature = $inputParams['temperature'] ?? $defaultParams['temperature'] ?? null;
        if ($temperature !== null && $temperature !== '') {
            $options['temperature'] = floatval($temperature);
        }
        
        // top_p
        $topP = $inputParams['top_p'] ?? $defaultParams['top_p'] ?? null;
        if ($topP !== null && $topP !== '') {
            $options['top_p'] = floatval($topP);
        }
        
        // max_tokens → Ollama使用 num_predict
        $maxTokens = $inputParams['max_tokens'] ?? $defaultParams['max_tokens'] ?? null;
        if ($maxTokens !== null && $maxTokens !== '') {
            $options['num_predict'] = intval($maxTokens);
        }
        
        // top_k
        if (isset($inputParams['top_k']) && $inputParams['top_k'] !== '') {
            $options['top_k'] = intval($inputParams['top_k']);
        }
        
        // repeat_penalty
        if (isset($inputParams['repeat_penalty']) && $inputParams['repeat_penalty'] !== '') {
            $options['repeat_penalty'] = floatval($inputParams['repeat_penalty']);
        }
        
        // seed
        if (isset($inputParams['seed']) && $inputParams['seed'] !== '' && $inputParams['seed'] !== null) {
            $options['seed'] = intval($inputParams['seed']);
        }
        
        if (!empty($options)) {
            $body['options'] = $options;
        }
        
        return $body;
    }
    
    /**
     * 构建Ollama Embedding API请求体
     * 
     * Ollama Embeddings API格式:
     * {
     *   "model": "nomic-embed-text",
     *   "prompt": "要嵌入的文本"
     * }
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 输入参数
     * @return array
     */
    protected function buildOllamaEmbeddingRequestBody($modelCode, $inputParams)
    {
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? $inputParams['content'] ?? '';
        
        return [
            'model' => $modelCode,
            'prompt' => $prompt,
        ];
    }
    
    /**
     * 解析Ollama API响应
     * 
     * Chat API响应格式:
     * {
     *   "model": "qwen3:8b",
     *   "created_at": "2024-01-01T00:00:00Z",
     *   "message": {
     *     "role": "assistant",
     *     "content": "回复内容"
     *   },
     *   "done": true,
     *   "total_duration": 1234567890,
     *   "eval_count": 100,
     *   "prompt_eval_count": 20
     * }
     * 
     * Embeddings API响应格式:
     * {
     *   "embedding": [0.123, -0.456, ...]
     * }
     * 
     * @param array $model 模型信息
     * @param array $responseBody 解析后的响应体
     * @param string $modelType 模型类型 (chat/embedding)
     * @return array
     */
    protected function parseOllamaResponse($model, $responseBody, $modelType = 'chat')
    {
        if (!is_array($responseBody)) {
            return [
                'status' => 0,
                'error_code' => 'OLLAMA_INVALID_RESPONSE',
                'msg' => 'Ollama返回无效响应'
            ];
        }
        
        // 检查错误
        if (isset($responseBody['error'])) {
            return [
                'status' => 0,
                'error_code' => 'OLLAMA_ERROR',
                'msg' => $responseBody['error']
            ];
        }
        
        if ($modelType === 'embedding') {
            // 向量嵌入响应
            $embedding = $responseBody['embedding'] ?? [];
            if (empty($embedding)) {
                return [
                    'status' => 0,
                    'error_code' => 'OLLAMA_EMPTY_EMBEDDING',
                    'msg' => 'Ollama返回空向量'
                ];
            }
            return [
                'status' => 1,
                'outputs' => [
                    [
                        'type' => 'embedding',
                        'data' => $embedding,
                        'dimensions' => count($embedding)
                    ]
                ],
                'tokens' => 0,
                'cost' => 0
            ];
        }
        
        // Chat API响应
        $message = $responseBody['message'] ?? [];
        $content = $message['content'] ?? '';
        
        if (empty($content) && !isset($responseBody['done'])) {
            return [
                'status' => 0,
                'error_code' => 'OLLAMA_EMPTY_RESPONSE',
                'msg' => 'Ollama返回空响应，模型可能未加载完成'
            ];
        }
        
        // 计算token使用量
        $promptTokens = $responseBody['prompt_eval_count'] ?? 0;
        $completionTokens = $responseBody['eval_count'] ?? 0;
        $totalTokens = $promptTokens + $completionTokens;
        
        // 构建输出
        $outputs = [
            [
                'type' => 'text',
                'content' => $content,
                'role' => $message['role'] ?? 'assistant',
                'url' => '',
                'thumbnail' => ''
            ]
        ];
        
        // 记录性能信息
        $totalDuration = $responseBody['total_duration'] ?? 0;
        if ($totalDuration > 0) {
            \think\facade\Log::info('Ollama生成完成', [
                'model' => $model['model_code'],
                'total_duration_ms' => round($totalDuration / 1000000, 2),
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens
            ]);
        }
        
        return [
            'status' => 1,
            'outputs' => $outputs,
            'tokens' => $totalTokens,
            'cost' => 0  // 本地模型免费
        ];
    }
    
    // ==================== VoxCPM2 语音合成相关方法 ====================
    
    /**
     * 检查是否为VoxCPM语音合成模型
     * 
     * @param string $modelCode 模型代码
     * @param string $providerCode 供应商标识
     * @return bool
     */
    protected function isVoxCPMModel($modelCode, $providerCode = '')
    {
        if ($providerCode === 'voxcpm') {
            return true;
        }
        return strpos($modelCode, 'voxcpm') === 0;
    }
    
    /**
     * 调用VoxCPM2 API服务
     * 支持三种模式：普通合成/声音设计、可控克隆、极致克隆
     * 
     * @param array $model 模型信息
     * @param array $apiKeyConfig API Key配置（存储服务器地址）
     * @param array $inputParams 输入参数
     * @return array 响应结果
     */
    protected function callVoxCPMApi($model, $apiKeyConfig, $inputParams)
    {
        $voxcpmConfig = config('aivideo.voxcpm') ?? [];
        
        // 介API Key字段获取用户配置的服务器地址
        $serverUrl = $apiKeyConfig['api_key_decrypted'] ?? '';
        if (empty($serverUrl) || !preg_match('/^https?:\/\//', $serverUrl)) {
            // 尝试使用配置文件的默认地址
            $serverUrl = $voxcpmConfig['api_url'] ?? 'http://127.0.0.1:8866';
        }
        $serverUrl = rtrim($serverUrl, '/');
        
        $timeout = $voxcpmConfig['timeout'] ?? 300;
        
        // 判断合成模式
        $mode = $inputParams['mode'] ?? 'tts';
        $hasReferenceAudio = !empty($inputParams['reference_audio']);
        
        // 根据模式和参数自动判断调用哪个端点
        if ($hasReferenceAudio || in_array($mode, ['controllable_clone', 'ultimate_clone'])) {
            return $this->callVoxCPMCloneApi($serverUrl, $inputParams, $timeout, $model);
        } else {
            return $this->callVoxCPMTtsApi($serverUrl, $inputParams, $timeout, $model);
        }
    }
    
    /**
     * 调用VoxCPM TTS端点（文本转语音 + 声音设计）
     */
    protected function callVoxCPMTtsApi($serverUrl, $inputParams, $timeout, $model)
    {
        $voxcpmConfig = config('aivideo.voxcpm') ?? [];
        $endpoint = $voxcpmConfig['tts_endpoint'] ?? '/api/tts';
        $defaultParams = $voxcpmConfig['default_params'] ?? [];
        
        $fullUrl = $serverUrl . $endpoint;
        
        $requestBody = [
            'text' => $inputParams['text'] ?? $inputParams['prompt'] ?? '',
            'cfg_value' => floatval($inputParams['cfg_value'] ?? $defaultParams['cfg_value'] ?? 2.0),
            'inference_timesteps' => intval($inputParams['inference_timesteps'] ?? $defaultParams['inference_timesteps'] ?? 10),
            'normalize' => filter_var($inputParams['normalize'] ?? $defaultParams['normalize'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
        
        // 声音设计描述
        $control = $inputParams['control'] ?? $inputParams['voice_description'] ?? '';
        if (!empty($control)) {
            $requestBody['control'] = $control;
        }
        
        \think\facade\Log::info('VoxCPM TTS 请求', [
            'url' => $fullUrl,
            'text_len' => strlen($requestBody['text']),
            'has_control' => !empty($control),
        ]);
        
        return $this->sendVoxCPMRequest($fullUrl, $requestBody, $timeout, $model);
    }
    
    /**
     * 调用VoxCPM Clone端点（可控克隆 + 极致克隆）
     */
    protected function callVoxCPMCloneApi($serverUrl, $inputParams, $timeout, $model)
    {
        $voxcpmConfig = config('aivideo.voxcpm') ?? [];
        $endpoint = $voxcpmConfig['clone_endpoint'] ?? '/api/clone';
        $defaultParams = $voxcpmConfig['default_params'] ?? [];
        
        $fullUrl = $serverUrl . $endpoint;
        
        // 获取参考音频并转换为Base64
        $referenceAudioUrl = $inputParams['reference_audio'] ?? $inputParams['audio_url'] ?? '';
        $referenceAudioBase64 = '';
        
        if (!empty($referenceAudioUrl)) {
            $referenceAudioBase64 = $this->downloadAndEncodeAudio($referenceAudioUrl);
            if (empty($referenceAudioBase64)) {
                return [
                    'status' => 0,
                    'error_code' => 'VOXCPM_AUDIO_DOWNLOAD_FAILED',
                    'msg' => '参考音频下载或编码失败',
                ];
            }
        }
        
        $mode = $inputParams['mode'] ?? 'controllable_clone';
        $isUltimateClone = ($mode === 'ultimate_clone');
        
        $requestBody = [
            'text' => $inputParams['text'] ?? $inputParams['prompt'] ?? '',
            'reference_audio_base64' => $referenceAudioBase64,
            'ultimate_clone' => $isUltimateClone,
            'cfg_value' => floatval($inputParams['cfg_value'] ?? $defaultParams['cfg_value'] ?? 2.0),
            'inference_timesteps' => intval($inputParams['inference_timesteps'] ?? $defaultParams['inference_timesteps'] ?? 10),
        ];
        
        // 风格控制指令（可控克隆模式）
        if (!$isUltimateClone) {
            $control = $inputParams['control'] ?? $inputParams['voice_description'] ?? '';
            if (!empty($control)) {
                $requestBody['control'] = $control;
            }
        }
        
        // 参考音频文本（极致克隆模式）
        if ($isUltimateClone) {
            $promptText = $inputParams['prompt_text'] ?? '';
            if (!empty($promptText)) {
                $requestBody['prompt_text'] = $promptText;
            }
        }
        
        \think\facade\Log::info('VoxCPM Clone 请求', [
            'url' => $fullUrl,
            'text_len' => strlen($requestBody['text']),
            'ultimate_clone' => $isUltimateClone,
            'has_prompt_text' => !empty($requestBody['prompt_text'] ?? ''),
        ]);
        
        return $this->sendVoxCPMRequest($fullUrl, $requestBody, $timeout, $model);
    }
    
    /**
     * 发送VoxCPM HTTP请求并解析响应
     * 
     * @param string $url 完整URL
     * @param array $requestBody 请求体
     * @param int $timeout 超时时间
     * @param array $model 模型信息
     * @return array
     */
    protected function sendVoxCPMRequest($url, $requestBody, $timeout, $model)
    {
        $headers = [
            'Content-Type: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // 连接失败
        if ($httpCode == 0 || !empty($curlError)) {
            \think\facade\Log::error('VoxCPM服务连接失败', [
                'url' => $url,
                'curl_error' => $curlError,
            ]);
            return [
                'status' => 0,
                'error_code' => 'VOXCPM_CONNECTION_FAILED',
                'msg' => 'VoxCPM服务连接失败，请确认服务已启动并监听在正确的地址端口',
            ];
        }
        
        $responseBody = json_decode($responseRaw, true);
        
        \think\facade\Log::info('VoxCPM API响应', [
            'http_code' => $httpCode,
            'status' => $responseBody['status'] ?? 'unknown',
            'duration' => $responseBody['duration'] ?? 0,
            'generation_time' => $responseBody['generation_time'] ?? 0,
        ]);
        
        // HTTP错误
        if ($httpCode != 200) {
            $errorMsg = 'VoxCPM请求失败(HTTP ' . $httpCode . ')';
            if (is_array($responseBody)) {
                $errorMsg = $responseBody['detail'] ?? $responseBody['error'] ?? $errorMsg;
            }
            if ($httpCode == 503) {
                $errorMsg = 'VoxCPM模型未加载，请稍后重试或检查服务端日志';
            }
            return [
                'status' => 0,
                'error_code' => 'VOXCPM_HTTP_' . $httpCode,
                'msg' => $errorMsg,
            ];
        }
        
        return $this->parseVoxCPMResponse($model, $responseBody);
    }
    
    /**
     * 解析VoxCPM API响应
     * 
     * 响应格式:
     * {
     *   "status": "success",
     *   "audio_base64": "Base64编码的WAV音频",
     *   "sample_rate": 48000,
     *   "duration": 3.5,
     *   "generation_time": 2.1
     * }
     * 
     * @param array $model 模型信息
     * @param array $responseBody 解析后的响应体
     * @return array
     */
    protected function parseVoxCPMResponse($model, $responseBody)
    {
        if (!is_array($responseBody)) {
            return [
                'status' => 0,
                'error_code' => 'VOXCPM_INVALID_RESPONSE',
                'msg' => 'VoxCPM返回无效响应',
            ];
        }
        
        if (($responseBody['status'] ?? '') !== 'success') {
            return [
                'status' => 0,
                'error_code' => 'VOXCPM_GENERATE_FAILED',
                'msg' => $responseBody['error'] ?? $responseBody['detail'] ?? '语音合成失败',
            ];
        }
        
        $audioBase64 = $responseBody['audio_base64'] ?? '';
        if (empty($audioBase64)) {
            return [
                'status' => 0,
                'error_code' => 'VOXCPM_EMPTY_AUDIO',
                'msg' => 'VoxCPM返回空音频数据',
            ];
        }
        
        // 将Base64音频保存为文件
        $audioUrl = $this->saveBase64Audio($audioBase64, 'wav');
        if (empty($audioUrl)) {
            return [
                'status' => 0,
                'error_code' => 'VOXCPM_SAVE_FAILED',
                'msg' => '音频文件保存失败',
            ];
        }
        
        $duration = $responseBody['duration'] ?? 0;
        $sampleRate = $responseBody['sample_rate'] ?? 48000;
        
        $outputs = [
            [
                'type' => 'audio',
                'url' => $audioUrl,
                'thumbnail' => '',
                'duration' => $duration,
                'sample_rate' => $sampleRate,
            ],
        ];
        
        \think\facade\Log::info('VoxCPM生成成功', [
            'model' => $model['model_code'],
            'duration' => $duration,
            'sample_rate' => $sampleRate,
            'generation_time' => $responseBody['generation_time'] ?? 0,
        ]);
        
        return [
            'status' => 1,
            'outputs' => $outputs,
            'tokens' => 0,
            'cost' => 0,  // 本地部署免费
        ];
    }
    
    /**
     * 下载音频文件并转换为Base64
     * 
     * @param string $audioUrl 音频文件URL
     * @return string Base64编码的音频数据
     */
    protected function downloadAndEncodeAudio($audioUrl)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $audioUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            $audioData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || empty($audioData)) {
                \think\facade\Log::warning('downloadAndEncodeAudio: 下载音频失败', [
                    'url' => $audioUrl,
                    'http_code' => $httpCode,
                ]);
                return '';
            }
            
            return base64_encode($audioData);
        } catch (\Exception $e) {
            \think\facade\Log::error('downloadAndEncodeAudio: 异常 ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * 保存Base64编码的音频文件
     * 
     * @param string $base64Data Base64音频数据
     * @param string $format 音频格式 (wav/mp3)
     * @return string 保存后的URL
     */
    protected function saveBase64Audio($base64Data, $format = 'wav')
    {
        try {
            $audioData = base64_decode($base64Data);
            if (!$audioData) {
                return '';
            }
            
            $filename = 'generation/' . date('Ymd') . '/' . md5(uniqid()) . '.' . $format;
            $localPath = ROOT_PATH . 'upload/' . $filename;
            
            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($localPath, $audioData);
            
            $localUrl = PRE_URL . '/upload/' . $filename;
            $ossUrl = \app\common\Pic::uploadoss($localUrl);
            
            return $ossUrl ?: $localUrl;
        } catch (\Exception $e) {
            \think\facade\Log::error('Base64音频保存失败: ' . $e->getMessage());
            return '';
        }
    }
}

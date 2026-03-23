<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoQrcode;
use app\model\ApiConfig;
use app\common\Pic;

/**
 * AI旅拍合成服务
 * 处理图像合成、模板选择、水印添加等功能
 */
class AiTravelPhotoSynthesisService
{
    protected $watermarkService = null;
    protected $watermarkEnabled = false;
    protected $ossHelper = null;

    public function __construct()
    {
        // 延迟初始化水印服务，避免OSS未配置时报错
        try {
            $businessSet = Db::name('business')->where('id', '>', 0)->find();
            if ($businessSet && !empty($businessSet['ai_logo_watermark'])) {
                $this->watermarkService = new AiTravelPhotoWatermarkService();
                $this->watermarkEnabled = true;
            }
        } catch (\Exception $e) {
            // 水印服务初始化失败，跳过水印功能
            $this->watermarkEnabled = false;
        }
    }

    /**
     * 执行合成生成
     *
     * @param array $portrait 人像信息
     * @param array $templates 模板列表
     * @return array
     */
    public function generate(array $portrait, array $templates, string $operatorName = ''): array
    {
        try {
            $portraitId = $portrait['id'];
            $portraitUrl = $portrait['original_url'] ?? $portrait['cutout_url'] ?? '';

            // 获取人像的bid
            $bid = $portrait['bid'] ?? 0;
            $aid = $portrait['aid'] ?? 0;

            // 获取商家设置的水印配置
            $business = Db::name('business')
                ->where('id', $bid)
                ->find();

            $generatedResults = [];
            $lastError = '';

            // 遍历模板生成图片
            foreach ($templates as $template) {
                $templateId = $template['id'] ?? 0;
                $templateName = $template['template_name'] ?? $template['name'] ?? '';

                // P5: 创建generation记录（状态=处理中）
                $generationId = 0;
                try {
                    $generationId = $this->createGenerationRecord($portrait, $template);
                } catch (\Exception $e) {
                    Log::error('创建generation记录失败[' . $templateName . ']: ' . $e->getMessage());
                    $lastError = '创建generation记录失败: ' . $e->getMessage();
                }

                try {
                    // 调用AI模型生成图片
                    $generatedUrl = $this->callAiModel($portraitUrl, $template);

                    if (empty($generatedUrl)) {
                        // 更新generation状态为失败
                        $this->updateGenerationStatus($generationId, 3, '生成结果为空');
                        continue;
                    }

                    // 更新generation状态为成功
                    $this->updateGenerationStatus($generationId, 2);

                    // 添加水印
                    $watermarkedUrl = $generatedUrl;
                    // 只有水印服务初始化成功且商家开启了水印时才添加水印
                    if ($this->watermarkEnabled && !empty($business['ai_logo_watermark'])) {
                        try {
                            // 先将生成的结果存入result表获取ID
                            $resultId = $this->saveResult($portraitId, $bid, $template, $generatedUrl, $generationId);

                            // 添加水印
                            $watermarkResult = $this->watermarkService->addWatermark($resultId);
                            $watermarkedUrl = $watermarkResult['watermark_url'] ?? $generatedUrl;

                            // 更新水印URL
                            Db::name('ai_travel_photo_result')
                                ->where('id', $resultId)
                                ->update(['result_url_watermark' => $watermarkedUrl]);
                        } catch (\Throwable $e) {
                            // 水印添加失败，使用原图
                            \think\facade\Log::error('合成图片水印添加失败: ' . $e->getMessage());
                        }
                    } else {
                        // 没有设置水印，直接保存结果
                        $resultId = $this->saveResult($portraitId, $bid, $template, $generatedUrl, $generationId);
                    }

                    // 如果水印添加成功，更新水印URL字段
                    if (!empty($watermarkedUrl) && $watermarkedUrl !== $generatedUrl && !empty($resultId)) {
                        try {
                            Db::name('ai_travel_photo_result')
                                ->where('id', $resultId)
                                ->update(['watermark_url' => $watermarkedUrl]);
                        } catch (\Exception $e) {
                            // 忽略字段不存在的错误
                        }
                    }

                    $generatedResults[] = [
                        'template_id' => $templateId,
                        'template_name' => $templateName,
                        'result_url' => $generatedUrl,
                        'watermarked_url' => $watermarkedUrl
                    ];

                    // 写入照片生成订单管理（generation_order表）
                    try {
                        $this->createGenerationOrder($portrait, $template, $generationId, 2, $operatorName); // 2=成功
                    } catch (\Exception $orderEx) {
                        Log::error('创建生成订单失败[' . $templateName . ']: ' . $orderEx->getMessage());
                    }
                } catch (\Throwable $e) {
                    // 更新generation状态为失败，记录error_msg
                    if ($generationId > 0) {
                        $this->updateGenerationStatus($generationId, 3, $e->getMessage());
                    }

                    // 失败的也写入订单管理
                    try {
                        $this->createGenerationOrder($portrait, $template, $generationId, 3, $operatorName); // 3=失败
                    } catch (\Exception $orderEx) {
                        Log::error('创建失败订单记录失败[' . $templateName . ']: ' . $orderEx->getMessage());
                    }

                    $lastError = $e->getMessage();
                    Log::error('合成模板[' . $templateName . ']失败: ' . $e->getMessage());
                }
            }

            // 存入选片表（qrcode表）
            if (!empty($generatedResults)) {
                $this->saveToQrcode($portraitId, $bid, $generatedResults, $aid);
            }

            // 关键修复：当count=0时返回失败，避免控制器误判为成功
            if (empty($generatedResults)) {
                return [
                    'code' => 1,
                    'msg' => '所有模板生成均失败' . ($lastError ? ': ' . $lastError : '')
                ];
            }

            return [
                'code' => 0,
                'msg' => '生成成功',
                'data' => [
                    'count' => count($generatedResults),
                    'results' => $generatedResults
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'code' => 1,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 创建generation记录
     *
     * @param array $portrait 人像信息
     * @param array $template 模板信息
     * @return int generation记录ID
     */
    protected function createGenerationRecord(array $portrait, array $template): int
    {
        // 提取提示词
        $prompt = '';
        if (!empty($template['prompt'])) {
            $prompt = $template['prompt'];
        } elseif (!empty($template['default_params'])) {
            $defaultParams = is_string($template['default_params'])
                ? json_decode($template['default_params'], true)
                : $template['default_params'];
            $prompt = $defaultParams['prompt'] ?? '';
        }
        if (empty($prompt) && !empty($template['description'])) {
            $prompt = $template['description'];
        }

        $data = [
            'aid' => $portrait['aid'] ?? 0,
            'portrait_id' => $portrait['id'],
            'scene_id' => 0,
            'template_id' => $template['id'] ?? 0,
            'bid' => $portrait['bid'] ?? 0,
            'type' => 1,  // 商家自动生成
            'generation_type' => 1,  // 图生图
            'prompt' => $prompt,
            'status' => 1,  // 处理中
            'create_time' => time(),
            'update_time' => time()
        ];

        return (int)Db::name('ai_travel_photo_generation')->insertGetId($data);
    }

    /**
     * 更新generation记录状态
     *
     * @param int $generationId
     * @param int $status 2=成功, 3=失败
     * @param string $errorMsg 错误信息
     */
    protected function updateGenerationStatus(int $generationId, int $status, string $errorMsg = ''): void
    {
        $update = [
            'status' => $status,
            'update_time' => time()
        ];
        if (!empty($errorMsg)) {
            $update['error_msg'] = $errorMsg;
        }
        Db::name('ai_travel_photo_generation')
            ->where('id', $generationId)
            ->update($update);
    }

    /**
     * 调用AI模型生成图片
     *
     * @param string $portraitUrl 人像图片URL（抠图后的人像）
     * @param array $template 模板信息（包含model_id, prompt, default_params等）
     * @return string|null
     */
    protected function callAiModel(string $portraitUrl, array $template): ?string
    {
        // 调试日志
        Log::info('callAiModel 模板数据: ' . json_encode($template, JSON_UNESCAPED_UNICODE));

        // 检查人像URL是否有效
        if (empty($portraitUrl)) {
            throw new \Exception('人像图片URL为空');
        }

        $modelId = $template['model_id'] ?? 0;
        // 照片场景模板表使用 mdid（门店ID），合成模板表使用 bid
        $bid = $template['mdid'] ?? $template['bid'] ?? 0;
        $aid = $template['aid'] ?? 0;

        if ($modelId <= 0) {
            throw new \Exception('模板未绑定AI模型');
        }

        // 1. 获取API配置（优先商户级别，再平台级别）
        $apiConfig = $this->getApiConfigByModelId($modelId, $aid, $bid);

        if (!$apiConfig) {
            throw new \Exception('未找到可用的API配置');
        }

        // 2. 解析模板的 default_params（完整的API请求预设参数）
        $defaultParams = [];
        if (!empty($template['default_params'])) {
            $defaultParams = is_string($template['default_params'])
                ? json_decode($template['default_params'], true)
                : $template['default_params'];
            if (!is_array($defaultParams)) {
                $defaultParams = [];
            }
        }

        // 3. 获取参考图：优先从 default_params.image 获取，其次从模板字段获取
        $referenceImage = '';
        if (!empty($defaultParams['image'])) {
            // default_params 已包含完整的参考图数组，后续直接使用
            // referenceImage 留空，由 callVolcengineImageApi 从 defaultParams 中读取
        } elseif (!empty($template['cover_image'])) {
            $referenceImage = $template['cover_image'];
        } elseif (!empty($template['reference_image'])) {
            $refImages = explode(',', $template['reference_image']);
            $referenceImage = trim($refImages[0]);
        } elseif (!empty($template['images'])) {
            $templateImages = json_decode($template['images'], true);
            if (!is_array($templateImages)) {
                $templateImages = is_array($template['images']) ? $template['images'] : [];
            }
            $referenceImage = !empty($templateImages) ? $templateImages[0] : '';
        }

        // 4. 获取提示词：优先 default_params.prompt，其次模板字段
        $prompt = $defaultParams['prompt'] ?? '';
        if (empty($prompt) && !empty($template['prompt'])) {
            $prompt = $template['prompt'];
        }
        if (empty($prompt) && !empty($template['description'])) {
            $prompt = $template['description'];
        }
        if (empty($prompt)) {
            $prompt = '生成一张高质量旅拍照片';
        }

        Log::info('callAiModel 参数: modelId=' . $modelId . ', prompt=' . substr($prompt, 0, 50) . '..., referenceImage=' . $referenceImage . ', hasDefaultParams=' . (!empty($defaultParams) ? 'yes' : 'no'));

        // 5. 调用AI图生图API，传入模板预设参数
        $resultUrl = $this->callImageGenerationApi($portraitUrl, $referenceImage, $prompt, $apiConfig, $defaultParams);

        return $resultUrl;
    }

    /**
     * 根据模型ID获取API配置（优先商户级别，再平台级别，最后系统级Key池）
     *
     * @param int $modelId 模型ID
     * @param int $aid 商户ID
     * @param int $bid 门店ID
     * @return array|null
     */
    protected function getApiConfigByModelId(int $modelId, int $aid, int $bid): ?array
    {
        // 优先查找商户自己的API配置 (bid = $bid)
        $merchantConfig = Db::name('api_config')
            ->where('model_id', $modelId)
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('is_active', 1)
            ->find();

        if ($merchantConfig) {
            return $merchantConfig;
        }

        // 其次查找平台级配置 (bid = 0)
        $platformConfig = Db::name('api_config')
            ->where('model_id', $modelId)
            ->where('aid', $aid)
            ->where('bid', 0)
            ->where('is_active', 1)
            ->find();

        if ($platformConfig) {
            return $platformConfig;
        }

        // 再次查找全局配置 (aid = 0)
        $globalConfig = Db::name('api_config')
            ->where('model_id', $modelId)
            ->where('aid', 0)
            ->where('is_active', 1)
            ->find();

        if ($globalConfig) {
            return $globalConfig;
        }

        // 最后从系统级API Key池获取
        return $this->getSystemApiKeyPoolConfig($modelId);
    }

    /**
     * 从系统级API Key池获取配置
     *
     * @param int $modelId 模型ID
     * @return array|null
     */
    protected function getSystemApiKeyPoolConfig(int $modelId): ?array
    {
        try {
            // 1. 获取模型的provider_id
            $model = Db::name('model_info')
                ->where('id', $modelId)
                ->field('id, model_code, model_name, provider_id, endpoint_url')
                ->find();

            if (!$model || empty($model['provider_id'])) {
                Log::warning("getSystemApiKeyPoolConfig: 模型 {$modelId} 不存在或未配置provider_id");
                return null;
            }

            // 2. 获取provider信息
            $provider = Db::name('model_provider')
                ->where('id', $model['provider_id'])
                ->field('id, provider_code, provider_name')
                ->find();

            if (!$provider) {
                Log::warning("getSystemApiKeyPoolConfig: provider_id {$model['provider_id']} 不存在");
                return null;
            }

            // 3. 从系统级Key池获取可用的Key
            $keyPoolService = new SystemApiKeyPoolService();
            $keyConfig = $keyPoolService->acquireKey($provider['provider_code']);

            if (!$keyConfig) {
                Log::warning("getSystemApiKeyPoolConfig: provider {$provider['provider_code']} 无可用Key");
                return null;
            }

            // 4. 转换为统一的API配置格式
            // provider_code 到 provider 的映射
            $providerMap = [
                'volcengine' => 'volcengine',
                'aliyun' => 'aliyun',
                'tencent' => 'tencent',
                'baidu' => 'baidu',
                'openai' => 'openai',
                'kling' => 'kling',
                'zhipu' => 'zhipu',
            ];

            return [
                'id' => $keyConfig['id'],
                'model_id' => $modelId,
                'model_code' => $model['model_code'],  // 添加模型代码
                'provider' => $providerMap[$provider['provider_code']] ?? $provider['provider_code'],
                'provider_code' => $provider['provider_code'],
                'api_key' => $keyConfig['api_key'],
                'api_secret' => $keyConfig['api_secret'] ?? '',
                'endpoint_url' => $model['endpoint_url'] ?: ($keyConfig['extra_config']['endpoint_url'] ?? ''),
                'config_json' => json_encode($keyConfig['extra_config'] ?? []),
                'config_name' => $keyConfig['config_name'] ?? '',
                'source' => 'system_key_pool', // 标记来源
            ];
        } catch (\Exception $e) {
            Log::error("getSystemApiKeyPoolConfig 异常: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 调用图生图API
     *
     * @param string $portraitUrl 人像图片URL
     * @param string $referenceImage 参考图URL
     * @param string $prompt 提示词
     * @param array $apiConfig API配置
     * @param array $defaultParams 模板预设的API请求参数（来自default_params字段）
     * @return string
     */
    protected function callImageGenerationApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig, array $defaultParams = []): string
    {
        $provider = $apiConfig['provider'] ?? '';

        switch ($provider) {
            case 'aliyun':
                return $this->callAliyunImageApi($portraitUrl, $referenceImage, $prompt, $apiConfig);

            case 'baidu':
                return $this->callBaiduImageApi($portraitUrl, $referenceImage, $prompt, $apiConfig);

            case 'openai':
                return $this->callOpenAiImageApi($portraitUrl, $referenceImage, $prompt, $apiConfig);

            case 'volcengine':
            case 'doubao':
                return $this->callVolcengineImageApi($portraitUrl, $referenceImage, $prompt, $apiConfig, $defaultParams);

            default:
                throw new \Exception('不支持的服务提供商: ' . $provider);
        }
    }

    /**
     * 调用阿里云通义万相图生图API
     */
    protected function callAliyunImageApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig): string
    {
        $endpointUrl = $apiConfig['endpoint_url'] ?? '';
        $apiKey = $apiConfig['api_key'] ?? '';
        $apiSecret = $apiConfig['api_secret'] ?? '';

        if (empty($endpointUrl) || empty($apiKey)) {
            throw new \Exception('阿里云API配置不完整');
        }

        // 解析extra_config
        $extraConfig = [];
        if (!empty($apiConfig['extra_config'])) {
            if (is_string($apiConfig['extra_config'])) {
                $extraConfig = json_decode($apiConfig['extra_config'], true) ?: [];
            } elseif (is_array($apiConfig['extra_config'])) {
                $extraConfig = $apiConfig['extra_config'];
            }
        }

        // 构建请求参数
        $requestParams = [
            'model' => $apiConfig['model_code'] ?? 'wanx-image-edit',
            'input' => [
                'image_url' => $portraitUrl
            ],
            'parameters' => [
                'size' => $extraConfig['size'] ?? '1024x1024',
                'n' => 1
            ]
        ];

        // 添加参考图（如果有）
        if (!empty($referenceImage)) {
            $requestParams['input']['reference_image'] = $referenceImage;
        }

        // 添加提示词
        if (!empty($prompt)) {
            $requestParams['parameters']['prompt'] = $prompt;
        }

        // 发送请求
        $client = new \GuzzleHttp\Client([
            'timeout' => 120,
        ]);

        try {
            $response = $client->post($endpointUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestParams,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // 解析返回结果
            if (isset($result['data']['output']['image_url']['url'])) {
                return $result['data']['output']['image_url']['url'];
            }

            if (isset($result['output']['image_url'])) {
                return is_array($result['output']['image_url'])
                    ? ($result['output']['image_url']['url'] ?? '')
                    : $result['output']['image_url'];
            }

            // 检查任务ID（异步模式）
            if (isset($result['task_id'])) {
                return $this->pollAliyunTask($result['task_id'], $apiKey, $extraConfig);
            }

            throw new \Exception('阿里云API返回格式异常: ' . json_encode($result));

        } catch (\Exception $e) {
            Log::error('阿里云图生图API调用失败: ' . $e->getMessage());
            throw new \Exception('AI生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 轮询阿里云任务结果
     */
    protected function pollAliyunTask(string $taskId, string $apiKey, array $extraConfig): string
    {
        $maxAttempts = 30;
        $interval = 2;

        $taskStatusUrl = ($extraConfig['endpoint_url'] ?? '') . '/tasks/' . $taskId;
        $client = new \GuzzleHttp\Client(['timeout' => 30]);

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($interval);

            try {
                $response = $client->get($taskStatusUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                    ]
                ]);

                $result = json_decode($response->getBody()->getContents(), true);

                if (isset($result['task_status']) && $result['task_status'] === 'SUCCEEDED') {
                    if (isset($result['output']['image_url']['url'])) {
                        return $result['output']['image_url']['url'];
                    }
                }

                if (isset($result['task_status']) && $result['task_status'] === 'FAILED') {
                    throw new \Exception('AI任务执行失败: ' . ($result['message'] ?? '未知错误'));
                }

            } catch (\Exception $e) {
                Log::error('轮询阿里云任务失败: ' . $e->getMessage());
            }
        }

        throw new \Exception('AI生成超时');
    }

    /**
     * 调用百度AI图生图API
     */
    protected function callBaiduImageApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig): string
    {
        $apiKey = $apiConfig['api_key'] ?? '';
        $apiSecret = $apiConfig['api_secret'] ?? '';

        if (empty($apiKey) || empty($apiSecret)) {
            throw new \Exception('百度API配置不完整');
        }

        // 获取access_token
        $tokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';
        $tokenResponse = (new \GuzzleHttp\Client())->post($tokenUrl, [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $apiKey,
                'client_secret' => $apiSecret,
            ]
        ]);
        $tokenResult = json_decode($tokenResponse->getBody()->getContents(), true);
        $accessToken = $tokenResult['access_token'] ?? '';

        if (empty($accessToken)) {
            throw new \Exception('获取百度 access_token 失败');
        }

        // 调用百度图片生成API
        $requestParams = [
            'prompt' => $prompt ?: '生成一张图片',
            'image' => $this->urlToBase64($portraitUrl),
        ];

        if (!empty($referenceImage)) {
            $requestParams['reference_image'] = $this->urlToBase64($referenceImage);
        }

        $client = new \GuzzleHttp\Client(['timeout' => 120]);
        $response = $client->post('https://aip.baidubce.com/rpc/2.0/ai_custom/v1/wenxinworkshop/image_generation/' . ($apiConfig['model_code'] ?? 'sd_xl'), [
            'headers' => ['Content-Type' => 'application/json'],
            'query' => ['access_token' => $accessToken],
            'json' => $requestParams,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['data']['image'])) {
            return $result['data']['image'];
        }

        if (isset($result['error_msg'])) {
            throw new \Exception('百度AI生成失败: ' . $result['error_msg']);
        }

        throw new \Exception('百度API返回格式异常');
    }

    /**
     * 调用OpenAI DALL-E图生图API
     */
    protected function callOpenAiImageApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig): string
    {
        $apiKey = $apiConfig['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API配置不完整');
        }

        // OpenAI DALL-E 不支持图生图（只能文生图），这里用参考图作为mask的思路
        // 实际应该使用 GPT-4 Vision 或者其他支持图生图的模型

        // 构建请求参数
        $requestParams = [
            'model' => $apiConfig['model_code'] ?? 'dall-e-3',
            'prompt' => !empty($prompt) ? $prompt : 'Generate a beautiful image',
            'size' => '1024x1024',
            'n' => 1,
        ];

        // 如果有参考图URL，可以作为附加提示
        if (!empty($referenceImage)) {
            $requestParams['prompt'] = $prompt . ' Style reference: ' . $referenceImage;
        }

        $client = new \GuzzleHttp\Client([
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ]
        ]);

        $response = $client->post('https://api.openai.com/v1/images/generations', [
            'json' => $requestParams,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['data'][0]['url'])) {
            return $result['data'][0]['url'];
        }

        if (isset($result['error'])) {
            throw new \Exception('OpenAI生成失败: ' . $result['error']['message']);
        }

        throw new \Exception('OpenAI API返回格式异常');
    }

    /**
     * URL转Base64（用于百度API）
     */
    protected function urlToBase64(string $url): string
    {
        try {
            $response = (new \GuzzleHttp\Client())->get($url, ['timeout' => 30]);
            $imageData = $response->getBody()->getContents();
            return base64_encode($imageData);
        } catch (\Exception $e) {
            throw new \Exception('图片转换为Base64失败: ' . $e->getMessage());
        }
    }

    /**
     * 调用火山方舟SeeDream图生图API
     * 支持豆包SeeDream系列模型
     * API格式：prompt为纯文本字符串，image为参考图URL
     *
     * @param string $portraitUrl 人像图片URL
     * @param string $referenceImage 参考图URL（单张，当default_params无image时使用）
     * @param string $prompt 提示词
     * @param array $apiConfig API配置
     * @param array $defaultParams 模板预设的API请求参数（来自default_params字段，包含size/image/model等完整配置）
     * @return string
     */
    protected function callVolcengineImageApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig, array $defaultParams = []): string
    {
        $endpointUrl = $apiConfig['endpoint_url'] ?? '';
        $apiKey = $apiConfig['api_key'] ?? '';
        $keyId = $apiConfig['id'] ?? 0;  // Key池中的ID，用于释放并发

        if (empty($apiKey)) {
            throw new \Exception('火山方舟API Key未配置');
        }

        // 默认端点
        if (empty($endpointUrl)) {
            $endpointUrl = 'https://ark.cn-beijing.volces.com/api/v3/images/generations';
        }

        // 火山方舟 SeeDream 图像生成API (/api/v3/images/generations) 请求格式：
        // - prompt: 纯文本字符串（必填，不能为空）
        // - image: 参考图URL字符串或数组（图生图时传递）
        // - size: 图片尺寸（如 "2K", "1920x1080" 等）
        // - n: 生成数量
        // 注意：模板的 default_params 可能已包含完整的API参数配置

        // 1. 提示词必须是纯文本字符串
        $textPrompt = !empty($prompt) ? $prompt : '生成一张高质量旅拍照片';

        // 2. 确定参考图：优先使用 default_params 中的 image 数组（模板预设），将人像URL插入第一位
        $imageUrls = [];
        if (!empty($defaultParams['image']) && is_array($defaultParams['image'])) {
            // 模板预设了完整的参考图数组，将人像URL插入第一个位置
            $imageUrls = $defaultParams['image'];
            if (!empty($portraitUrl)) {
                array_unshift($imageUrls, $portraitUrl);
            }
        } else {
            // 无预设参考图，使用传入的人像和参考图
            if (!empty($portraitUrl)) {
                $imageUrls[] = $portraitUrl;
            }
            if (!empty($referenceImage)) {
                $imageUrls[] = $referenceImage;
            }
        }

        // 3. 确定尺寸：优先使用 default_params 中的 size，否则使用安全默认值
        $size = $defaultParams['size'] ?? '2K';

        // 4. 确定模型：优先 default_params > apiConfig
        $model = $defaultParams['model'] ?? $apiConfig['model_code'] ?? 'doubao-seedream-4-5-251128';

        // 5. 构建请求参数
        $requestParams = [
            'model' => $model,
            'prompt' => $textPrompt,
            'size' => $size,
            'n' => 1,
            'response_format' => $defaultParams['response_format'] ?? 'url',
        ];

        // 6. 如果有参考图，通过 image 字段传递
        if (!empty($imageUrls)) {
            // 单张图传字符串，多张图传数组
            $requestParams['image'] = count($imageUrls) === 1 ? $imageUrls[0] : $imageUrls;
        }

        // 7. 处理 sequential_image_generation 参数
        if (isset($defaultParams['sequential_image_generation'])) {
            $requestParams['sequential_image_generation'] = $defaultParams['sequential_image_generation'];
        } elseif (!empty($imageUrls)) {
            // 默认：有image参数时设置多图生成选项
            $requestParams['sequential_image_generation_options'] = ['max_images' => 1];
        }

        // 调试：记录完整的请求参数
        Log::info('火山方舟SeeDream图生图请求', [
            'endpoint' => $endpointUrl,
            'model' => $requestParams['model'],
            'prompt' => $textPrompt,
            'has_image' => !empty($imageUrls),
            'image_count' => count($imageUrls),
            'has_portrait' => !empty($portraitUrl),
            'has_reference' => !empty($referenceImage),
            'request_params' => $requestParams
        ]);

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 180,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);

            $response = $client->post($endpointUrl, [
                'json' => $requestParams,
            ]);

            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            // SSE格式兜底：如果json_decode失败，尝试解析SSE流式响应
            if ($result === null && json_last_error() !== JSON_ERROR_NONE && !empty($responseBody)) {
                Log::info('火山方舟响应非JSON，尝试SSE解析', ['body_preview' => substr($responseBody, 0, 500)]);
                $result = $this->parseSSEResponse($responseBody);
            }

            Log::info('火山方舟图生图响应', ['result' => $result]);

            // 解析响应 - 提取图片URL
            $imageUrl = null;

            if (isset($result['data'][0]['url'])) {
                $imageUrl = $result['data'][0]['url'];
            } elseif (isset($result['data'][0]['b64_json'])) {
                // 如果返回base64，先保存到本地
                $imageUrl = $this->saveBase64Image($result['data'][0]['b64_json']);
                $this->releaseKey($keyId);
                return $imageUrl; // base64已保存为本地文件，无需再持久化
            } elseif (isset($result['error'])) {
                $this->releaseKey($keyId);
                throw new \Exception('火山方舟生成失败: ' . ($result['error']['message'] ?? json_encode($result['error'])));
            } elseif (isset($result['task_id'])) {
                // 异步任务模式
                $imageUrl = $this->pollVolcengineTask($result['task_id'], $apiKey, $endpointUrl);
            }

            if (empty($imageUrl)) {
                $this->releaseKey($keyId);
                throw new \Exception('火山方舟API返回格式异常: ' . json_encode($result));
            }

            // 关键修复：将API返回的临时签名URL持久化到本地/OSS
            // 火山方舟TOS返回的URL是临时签名的，会很快过期
            $persistedUrl = $this->persistImageUrl($imageUrl);
            Log::info('火山方舟图片持久化完成', ['temp_url' => substr($imageUrl, 0, 120), 'persisted_url' => $persistedUrl]);

            $this->releaseKey($keyId);
            return $persistedUrl;

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->releaseKey($keyId);
            $response = $e->getResponse();
            $body = $response ? json_decode($response->getBody()->getContents(), true) : [];
            $errorMsg = $body['error']['message'] ?? $e->getMessage();
            
            if ($response && $response->getStatusCode() === 401) {
                throw new \Exception('API Key无效，请检查火山方舟Ark API Key配置');
            }
            if ($response && $response->getStatusCode() === 404) {
                throw new \Exception('接口未找到，请检查：1.是否已开通图像生成服务；2.API Key是否有该模型权限');
            }
            
            throw new \Exception('火山方舟API调用失败: ' . $errorMsg);
        } catch (\Exception $e) {
            $this->releaseKey($keyId);
            throw new \Exception('火山方舟API调用异常: ' . $e->getMessage());
        }
    }

    /**
     * 释放Key池并发占用
     */
    protected function releaseKey(int $keyId): void
    {
        if ($keyId <= 0) return;
        
        try {
            Db::name('system_api_key')
                ->where('id', $keyId)
                ->where('current_concurrency', '>', 0)
                ->dec('current_concurrency')
                ->update();
        } catch (\Exception $e) {
            Log::error('释放Key失败: ' . $e->getMessage());
        }
    }

    /**
     * 轮询火山方舟异步任务
     */
    protected function pollVolcengineTask(string $taskId, string $apiKey, string $baseUrl): string
    {
        $maxAttempts = 60;
        $interval = 5;

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($interval);

            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);

            $response = $client->get($baseUrl . '/tasks/' . $taskId);
            $result = json_decode($response->getBody()->getContents(), true);

            $status = $result['task_status'] ?? $result['status'] ?? '';

            if ($status === 'SUCCEEDED' || $status === 'completed') {
                if (isset($result['output']['image_url'])) {
                    return $result['output']['image_url'];
                }
                if (isset($result['data'][0]['url'])) {
                    return $result['data'][0]['url'];
                }
            }

            if ($status === 'FAILED' || $status === 'failed') {
                throw new \Exception('火山方舟任务失败: ' . ($result['error']['message'] ?? '未知错误'));
            }
        }

        throw new \Exception('火山方舟任务超时');
    }

    /**
     * 解析SSE（Server-Sent Events）流式响应
     * 火山方舟SeeDream API可能返回SSE格式而非标准JSON
     *
     * SSE格式示例:
     * event: image_generation.partial_succeeded
     * data: {"type":"image_generation.partial_succeeded","url":"...","size":"1664x2496"}
     * event: image_generation.completed
     * data: {"type":"image_generation.completed"}
     */
    protected function parseSSEResponse(string $response): ?array
    {
        $trimmed = trim($response);

        // 检查是否是SSE格式
        if (strpos($trimmed, 'data:') !== 0 && strpos($trimmed, 'event:') !== 0) {
            return null;
        }

        $allData = [];
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'event:') === 0) {
                continue;
            }
            if ($line === 'data: [DONE]' || $line === 'data:[DONE]') {
                continue;
            }
            if (strpos($line, 'data:') === 0) {
                $jsonStr = trim(substr($line, 5));
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

            // 火山引擎 image_generation.partial_succeeded 事件
            if (isset($chunk['type']) && $chunk['type'] === 'image_generation.partial_succeeded') {
                $imageItem = [];
                if (!empty($chunk['url'])) {
                    $imageItem['url'] = $chunk['url'];
                }
                if (!empty($chunk['b64_json'])) {
                    $imageItem['b64_json'] = $chunk['b64_json'];
                }
                if (!empty($chunk['size']) && strpos($chunk['size'], 'x') !== false) {
                    list($w, $h) = explode('x', $chunk['size']);
                    $imageItem['width'] = intval($w);
                    $imageItem['height'] = intval($h);
                }
                if (!empty($imageItem['url']) || !empty($imageItem['b64_json'])) {
                    $mergedImages[] = $imageItem;
                }
                continue;
            }

            // completed 事件 - 跳过
            if (isset($chunk['type']) && $chunk['type'] === 'image_generation.completed') {
                continue;
            }

            // 标准格式 {"data":[{"url":"..."}]}
            if (isset($chunk['data']) && is_array($chunk['data'])) {
                foreach ($chunk['data'] as $item) {
                    if (isset($item['url']) || isset($item['b64_json'])) {
                        $mergedImages[] = $item;
                    }
                }
                continue;
            }

            // 顶层直接包含 url
            if (isset($chunk['url'])) {
                $mergedImages[] = $chunk;
            }
        }

        if (empty($mergedImages)) {
            return null;
        }

        return [
            'created' => $created,
            'data' => $mergedImages
        ];
    }

    /**
     * 将API返回的临时签名URL持久化到本地/OSS
     * 火山方舟TOS返回的URL是临时签名的，会很快过期
     * 必须下载后保存到本地或上传到OSS才能长期访问
     *
     * @param string $tempUrl 临时签名URL
     * @return string 持久化后的永久URL
     */
    protected function persistImageUrl(string $tempUrl): string
    {
        try {
            // 使用Pic::uploadoss 下载远程图片并上传到OSS（或保存到本地）
            $persistedUrl = Pic::uploadoss($tempUrl);
            if (!empty($persistedUrl)) {
                return $persistedUrl;
            }

            // OSS上传失败时，退回到仅保存本地
            Log::warning('OSS上传失败，尝试保存到本地', ['url' => substr($tempUrl, 0, 120)]);
            $localUrl = Pic::tolocal($tempUrl);
            if (!empty($localUrl)) {
                return $localUrl;
            }

            // 如果都失败了，返回原始临时URL（虽然会过期）
            Log::error('图片持久化完全失败，返回临时URL', ['url' => substr($tempUrl, 0, 120)]);
            return $tempUrl;
        } catch (\Exception $e) {
            Log::error('图片持久化异常: ' . $e->getMessage(), ['url' => substr($tempUrl, 0, 120)]);
            return $tempUrl;
        }
    }

    /**
     * 保存Base64图片到OSS
     */
    protected function saveBase64Image(string $base64Data): string
    {
        // 简单处理：保存到临时目录
        $imageData = base64_decode($base64Data);
        $filename = 'synthesis_' . date('YmdHis') . '_' . uniqid() . '.png';
        $filepath = public_path() . 'upload/' . $filename;
        
        file_put_contents($filepath, $imageData);
        
        return '/upload/' . $filename;
    }

    /**
     * 保存生成结果到result表
     *
     * @param int $portraitId 人像ID
     * @param int $bid 门店ID
     * @param array $template 模板信息
     * @param string $resultUrl 生成结果URL
     * @param int $generationId 关联的generation记录ID
     * @return int
     */
    /**
     * 创建生成订单记录（写入generation_order表，使其出现在照片生成的订单管理中）
     *
     * @param array $portrait 人像信息
     * @param array $template 模板信息
     * @param int $generationId 合成generation记录ID
     * @param int $taskStatus 任务状态 2=成功 3=失败
     * @return int 订单ID
     */
    protected function createGenerationOrder(array $portrait, array $template, int $generationId, int $taskStatus, string $operatorName = ''): int
    {
        $aid = $portrait['aid'] ?? 0;
        $bid = $portrait['bid'] ?? 0;
        $uid = $portrait['uid'] ?? 0;

        // 尝试通过uid查找对应的会员mid
        $mid = 0;
        if ($uid > 0) {
            $member = Db::name('member')->where('id', $uid)->field('id')->find();
            if ($member) {
                $mid = $member['id'];
            }
        }

        $templateId = $template['id'] ?? 0;
        $templateName = $template['template_name'] ?? $template['name'] ?? '';

        // 获取模板价格（免费合成场景下为0）
        $basePrice = floatval($template['base_price'] ?? 0);
        $payPrice = $basePrice; // 合成场景直接使用模板原价

        // 生成订单号：PG + 日期时间 + 随机数
        $ordernum = 'PG' . date('YmdHis') . rand(1000, 9999);

        // 构建模板快照（包含操作者信息）
        $templateSnapshot = json_encode([
            'id' => $templateId,
            'template_name' => $templateName,
            'model_id' => $template['model_id'] ?? 0,
            'source' => 'ai_travel_photo_synthesis', // 标识来源为人像合成
            'portrait_id' => $portrait['id'] ?? 0,
            'operator_name' => $operatorName, // 提交人账号名
        ], JSON_UNESCAPED_UNICODE);

        $orderData = [
            'aid' => $aid,
            'bid' => $bid,
            'mid' => $mid,
            'ordernum' => $ordernum,
            'generation_type' => 1, // 照片生成
            'scene_id' => $templateId,
            'scene_name' => $templateName,
            'total_price' => $basePrice,
            'pay_price' => $payPrice,
            'pay_status' => $payPrice > 0 ? 1 : 1, // 合成场景直接标记已支付（商家后台操作）
            'pay_time' => time(),
            'paytype' => $payPrice > 0 ? '商家合成扣费' : '免费',
            'refund_status' => 0,
            'task_status' => $taskStatus,
            'record_id' => $generationId, // 关联ai_travel_photo_generation记录ID
            'template_snapshot' => $templateSnapshot,
            'remark' => $operatorName ? ('提交人:' . $operatorName . ' 人像ID:' . ($portrait['id'] ?? 0)) : ('人像合成自动生成 人像ID:' . ($portrait['id'] ?? 0)),
            'status' => 1,
            'createtime' => time(),
            'updatetime' => time()
        ];

        $orderId = (int)Db::name('generation_order')->insertGetId($orderData);

        Log::info('合成订单已写入generation_order', [
            'order_id' => $orderId,
            'ordernum' => $ordernum,
            'portrait_id' => $portrait['id'] ?? 0,
            'template_id' => $templateId,
            'task_status' => $taskStatus
        ]);

        return $orderId;
    }

    protected function saveResult(int $portraitId, int $bid, array $template, string $resultUrl, int $generationId = 0)
    {
        // 获取人像信息中的aid
        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();

        // ai_travel_photo_result表字段保存
        $data = [
            'aid' => $portrait['aid'] ?? 0,
            'bid' => $bid,
            'portrait_id' => $portraitId,
            'generation_id' => $generationId,
            'scene_id' => $template['id'] ?? 0,
            'url' => $resultUrl,
            'type' => 1, // 图片类型
            'status' => 1,
            'desc' => $template['template_name'] ?? $template['name'] ?? '',
            'create_time' => time(),
            'update_time' => time()
        ];

        return (int)Db::name('ai_travel_photo_result')->insertGetId($data);
    }

    /**
     * 存入选片表（qrcode表）
     * 将生成的图片关联到选片记录
     *
     * @param int $portraitId 人像ID
     * @param int $bid 门店ID
     * @param array $results 生成结果
     */
    protected function saveToQrcode(int $portraitId, int $bid, array $results, int $aid = 0): void
    {
        // 检查是否已有选片记录
        $qrcode = Db::name('ai_travel_photo_qrcode')
            ->where('portrait_id', $portraitId)
            ->find();

        if (!$qrcode) {
            // 生成唯一的qrcode值
            $qrcodeValue = '合成_' . $portraitId . '_' . time();

            // 创建新的选片记录
            $qrcodeId = Db::name('ai_travel_photo_qrcode')->insertGetId([
                'aid' => $aid,
                'bid' => $bid,
                'portrait_id' => $portraitId,
                'qrcode' => $qrcodeValue,
                'status' => 1, // 有效状态
                'create_time' => time(),
                'update_time' => time()
            ]);
        } else {
            $qrcodeId = $qrcode['id'];
        }

        // 更新选片记录中的生成结果
        // 将生成的水印图URL保存到result表，并在qrcode中记录
        $resultUrls = array_column($results, 'watermarked_url');

        Db::name('ai_travel_photo_qrcode')
            ->where('id', $qrcodeId)
            ->update([
                'update_time' => time()
            ]);
    }

    /**
     * 获取合成模板列表
     *
     * @param int $aid 商户ID
     * @param int $bid 门店ID
     * @return array
     */
    public function getTemplateList(int $aid, int $bid): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['status', '=', 1]
        ];

        $templates = Db::name('ai_travel_photo_synthesis_template')
            ->where($where)
            ->order('sort ASC, id DESC')
            ->select();

        return $templates->toArray();
    }

    /**
     * 获取合成设置
     *
     * @param int $portraitId 人像ID
     * @return array|null
     */
    public function getSetting(int $portraitId): ?array
    {
        $setting = Db::name('ai_travel_photo_synthesis_setting')
            ->where('portrait_id', $portraitId)
            ->find();

        return $setting ?: null;
    }
}

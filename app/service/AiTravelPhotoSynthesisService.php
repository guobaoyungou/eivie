<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoQrcode;
use app\model\ApiConfig;

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
    public function generate(array $portrait, array $templates): array
    {
        try {
            $portraitId = $portrait['id'];
            $portraitUrl = $portrait['original_url'] ?? $portrait['cutout_url'] ?? '';

            // 获取人像的bid
            $bid = $portrait['bid'] ?? 0;

            // 获取商家设置的水印配置
            $business = Db::name('business')
                ->where('id', $bid)
                ->find();

            $generatedResults = [];

            // 遍历模板生成图片
            foreach ($templates as $template) {
                // 调用AI模型生成图片
                $generatedUrl = $this->callAiModel($portraitUrl, $template);

                if (empty($generatedUrl)) {
                    continue;
                }

                // 添加水印
                $watermarkedUrl = $generatedUrl;
                // 只有水印服务初始化成功且商家开启了水印时才添加水印
                if ($this->watermarkEnabled && !empty($business['ai_logo_watermark'])) {
                    try {
                        // 先将生成的结果存入result表获取ID
                        $resultId = $this->saveResult($portraitId, $bid, $template, $generatedUrl);

                        // 添加水印
                        $watermarkResult = $this->watermarkService->addWatermark($resultId);
                        $watermarkedUrl = $watermarkResult['watermark_url'] ?? $generatedUrl;

                        // 更新水印URL
                        Db::name('ai_travel_photo_result')
                            ->where('id', $resultId)
                            ->update(['result_url_watermark' => $watermarkedUrl]);
                    } catch (\Exception $e) {
                        // 水印添加失败，使用原图
                        \think\facade\Log::error('合成图片水印添加失败: ' . $e->getMessage());
                    }
                } else {
                    // 没有设置水印，直接保存结果
                    $this->saveResult($portraitId, $bid, $template, $generatedUrl);
                }

                // 如果水印添加成功，更新水印URL字段（注意result表可能没有watermark_url字段，这里捕获异常）
                if (!empty($watermarkedUrl) && $watermarkedUrl !== $generatedUrl) {
                    try {
                        Db::name('ai_travel_photo_result')
                            ->where('id', $resultId)
                            ->update(['watermark_url' => $watermarkedUrl]);
                    } catch (\Exception $e) {
                        // 忽略字段不存在的错误
                    }
                }

                $generatedResults[] = [
                    'template_id' => $template['id'],
                    'template_name' => $template['template_name'] ?? $template['name'] ?? '',
                    'result_url' => $generatedUrl,
                    'watermarked_url' => $watermarkedUrl
                ];
            }

            // 存入选片表（qrcode表）
            if (!empty($generatedResults)) {
                // 获取人像信息中的aid
                $portraitInfo = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
                $this->saveToQrcode($portraitId, $bid, $generatedResults, $portraitInfo['aid'] ?? 0);
            }

            return [
                'code' => 0,
                'msg' => '生成成功',
                'data' => [
                    'count' => count($generatedResults),
                    'results' => $generatedResults
                ]
            ];

        } catch (\Exception $e) {
            return [
                'code' => 1,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 调用AI模型生成图片
     *
     * @param string $portraitUrl 人像图片URL（抠图后的人像）
     * @param array $template 模板信息（包含model_id, prompt, images等）
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

        // 2. 获取模板图片作为参考图
        // 支持三种格式：
        // - 合成模板(ai_travel_photo_synthesis_template)：images字段（JSON数组）
        // - 照片场景模板(ai_travel_photo_scene)：reference_image字段（逗号分隔的字符串）
        // - 生成场景模板(generation_scene_template)：cover_image字段（封面图）
        $referenceImage = '';
        
        if (!empty($template['cover_image'])) {
            // 生成场景模板：cover_image字段作为参考图
            $referenceImage = $template['cover_image'];
        } elseif (!empty($template['reference_image'])) {
            // 照片场景模板：reference_image字段，逗号分隔
            $refImages = explode(',', $template['reference_image']);
            $referenceImage = trim($refImages[0]);
        } elseif (!empty($template['images'])) {
            // 合成模板：images字段，JSON数组
            $templateImages = !empty($template['images']) ? json_decode($template['images'], true) : [];
            if (!is_array($templateImages)) {
                $templateImages = is_array($template['images']) ? $template['images'] : [];
            }
            $referenceImage = !empty($templateImages) ? $templateImages[0] : '';
        }

        // 3. 获取提示词
        // 支持从 prompt 字段或 default_params JSON 中获取
        $prompt = '';
        if (!empty($template['prompt'])) {
            $prompt = $template['prompt'];
        } elseif (!empty($template['default_params'])) {
            // default_params 是JSON字段
            $defaultParams = is_string($template['default_params']) 
                ? json_decode($template['default_params'], true) 
                : $template['default_params'];
            $prompt = $defaultParams['prompt'] ?? '';
        }

        Log::info('callAiModel 参数: modelId=' . $modelId . ', prompt=' . substr($prompt, 0, 50) . '..., referenceImage=' . $referenceImage);

        // 4. 调用AI图生图API
        $resultUrl = $this->callImageGenerationApi($portraitUrl, $referenceImage, $prompt, $apiConfig);

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
     * @return string
     */
    protected function callImageGenerationApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig): string
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
                return $this->callVolcengineImageApi($portraitUrl, $referenceImage, $prompt, $apiConfig);

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
     * 调用火山方舟图生图API
     * 支持豆包SeeDream系列模型
     * API格式：使用content数组格式
     */
    protected function callVolcengineImageApi(string $portraitUrl, string $referenceImage, string $prompt, array $apiConfig): string
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

        // 构建 content 数组格式（火山方舟API要求）
        $content = [];

        // 1. 添加文本提示
        $textPrompt = !empty($prompt) ? $prompt : 'Generate a beautiful travel photo';
        $content[] = [
            'type' => 'text',
            'text' => $textPrompt
        ];

        // 2. 添加人像图片（图生图模式）
        if (!empty($portraitUrl)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $portraitUrl
                ]
            ];
        }

        // 3. 添加风格参考图
        if (!empty($referenceImage)) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $referenceImage
                ]
            ];
        }

        // 构建请求参数
        $requestParams = [
            'model' => $apiConfig['model_code'] ?? 'doubao-seedream-4-5-251128',
            'content' => $content,
            'size' => '1024x1024',
            'n' => 1,
            'response_format' => 'url',
        ];

        // 如果有图片，添加多图生成选项
        if (!empty($portraitUrl) || !empty($referenceImage)) {
            $requestParams['sequential_image_generation_options'] = ['max_images' => 1];
        }

        Log::info('火山方舟图生图请求', [
            'endpoint' => $endpointUrl,
            'model' => $requestParams['model'],
            'content_count' => count($content),
            'has_portrait' => !empty($portraitUrl),
            'has_reference' => !empty($referenceImage)
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

            $result = json_decode($response->getBody()->getContents(), true);

            Log::info('火山方舟图生图响应', ['result' => $result]);

            // 解析响应
            if (isset($result['data'][0]['url'])) {
                $this->releaseKey($keyId);
                return $result['data'][0]['url'];
            }

            if (isset($result['data'][0]['b64_json'])) {
                // 如果返回base64，需要保存到OSS
                $this->releaseKey($keyId);
                return $this->saveBase64Image($result['data'][0]['b64_json']);
            }

            if (isset($result['error'])) {
                $this->releaseKey($keyId);
                throw new \Exception('火山方舟生成失败: ' . ($result['error']['message'] ?? json_encode($result['error'])));
            }

            // 异步任务模式
            if (isset($result['task_id'])) {
                $resultUrl = $this->pollVolcengineTask($result['task_id'], $apiKey, $endpointUrl);
                $this->releaseKey($keyId);
                return $resultUrl;
            }

            $this->releaseKey($keyId);
            throw new \Exception('火山方舟API返回格式异常: ' . json_encode($result));

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
     * @return int
     */
    protected function saveResult(int $portraitId, int $bid, array $template, string $resultUrl)
    {
        // 获取人像信息中的aid
        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();

        // ai_travel_photo_result表字段有限，只保存存在的字段
        $data = [
            'aid' => $portrait['aid'] ?? 0,
            'portrait_id' => $portraitId,
            'generation_id' => 0, // 合成任务ID
            'url' => $resultUrl,  // 字段名是url不是result_url
            'type' => 1, // 图片类型
            'status' => 1,
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

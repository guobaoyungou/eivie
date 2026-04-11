<?php
/**
 * 云端LLM服务
 * 通过 SystemApiKeyPoolService 调用云端LLM API（阿里云DashScope / 火山方舟Ark 等OpenAI兼容接口）
 * 当本地Ollama不可用时作为剧本生成的备选方案
 */
namespace app\service;

use think\facade\Db;
use think\facade\Config;
use think\facade\Log;

class CloudLLMService
{
    use \app\common\ApiKeyEncryptTrait;

    /** @var SystemApiKeyPoolService */
    protected $keyPool;

    /**
     * 供应商 → API端点 映射
     */
    protected static $endpoints = [
        'aliyun' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
        'volcengine' => 'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
    ];

    /**
     * 供应商 → 默认模型 映射
     */
    protected static $defaultModels = [
        'aliyun' => 'qwen-plus',
        'volcengine' => 'doubao-1-5-pro-256k-250115',
    ];

    /**
     * 可用的LLM供应商优先级（按优先级排序）
     */
    protected static $providerPriority = ['aliyun', 'volcengine'];

    public function __construct()
    {
        $this->keyPool = new SystemApiKeyPoolService();
    }

    /**
     * 发送对话消息到云端LLM
     * 自动选择可用供应商和API Key
     *
     * @param array  $messages  对话消息 [['role'=>'user','content'=>'...'], ...]
     * @param array  $options   可选参数 ['temperature'=>0.7, 'max_tokens'=>4096, 'model'=>'...', 'provider'=>'...']
     * @return array ['status'=>1, 'message'=>['role'=>'assistant','content'=>'...'], 'usage'=>[...]] 或 ['status'=>0, 'msg'=>'...']
     */
    public function sendMessage($messages = [], $options = [])
    {
        $preferredProvider = $options['provider'] ?? '';
        $model = $options['model'] ?? '';

        // 如果指定了供应商，只尝试该供应商
        if (!empty($preferredProvider)) {
            return $this->callProvider($preferredProvider, $messages, $options, $model);
        }

        // 否则按优先级依次尝试
        $lastError = '';
        foreach (self::$providerPriority as $providerCode) {
            $result = $this->callProvider($providerCode, $messages, $options, $model);
            if (($result['status'] ?? 0) == 1) {
                return $result;
            }
            $lastError = $result['msg'] ?? '调用失败';
            Log::info("CloudLLM: 供应商 {$providerCode} 调用失败: {$lastError}，尝试下一个");
        }

        return ['status' => 0, 'msg' => '所有云端LLM供应商均不可用: ' . $lastError];
    }

    /**
     * 调用指定供应商的LLM API
     */
    protected function callProvider($providerCode, $messages, $options, $model = '')
    {
        // 从Key池获取可用Key
        $keyConfig = $this->keyPool->acquireKey($providerCode);
        if (!$keyConfig) {
            return ['status' => 0, 'msg' => "供应商 {$providerCode} 无可用API Key"];
        }

        $keyId = $keyConfig['id'];
        $apiKey = $keyConfig['api_key'];
        $endpoint = self::$endpoints[$providerCode] ?? '';

        if (empty($endpoint)) {
            $this->keyPool->releaseKey($keyId);
            return ['status' => 0, 'msg' => "供应商 {$providerCode} 无LLM API端点"];
        }

        if (empty($model)) {
            $model = self::$defaultModels[$providerCode] ?? 'qwen-plus';
        }

        $temperature = floatval($options['temperature'] ?? 0.7);
        $maxTokens = intval($options['max_tokens'] ?? 4096);

        // 构建 OpenAI 兼容的请求体
        $body = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];

        $timeout = intval($options['timeout'] ?? 120);

        Log::info('CloudLLM 发送消息', [
            'provider' => $providerCode,
            'model'    => $model,
            'endpoint' => $endpoint,
            'messages_count' => count($messages),
        ]);

        $startTime = microtime(true);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $latencyMs = intval((microtime(true) - $startTime) * 1000);

            if ($response === false || $httpCode == 0) {
                $this->keyPool->recordCallResult($keyId, false, 'cURL错误: ' . $curlError);
                $this->keyPool->releaseKey($keyId);
                return ['status' => 0, 'msg' => "连接失败: {$curlError}"];
            }

            $respBody = json_decode($response, true);

            if ($httpCode !== 200) {
                $errorMsg = $respBody['error']['message'] ?? $respBody['message'] ?? "HTTP {$httpCode}";
                $this->keyPool->recordCallResult($keyId, false, $errorMsg);
                $this->keyPool->releaseKey($keyId);
                return ['status' => 0, 'msg' => "API错误({$providerCode}): {$errorMsg}"];
            }

            if (!is_array($respBody) || empty($respBody['choices'])) {
                $this->keyPool->recordCallResult($keyId, false, '响应格式异常');
                $this->keyPool->releaseKey($keyId);
                return ['status' => 0, 'msg' => '云端LLM返回无效响应'];
            }

            // 解析 OpenAI 兼容格式的响应
            $choice = $respBody['choices'][0] ?? [];
            $message = $choice['message'] ?? [];
            $content = $message['content'] ?? '';

            $usage = $respBody['usage'] ?? [];
            $promptTokens = $usage['prompt_tokens'] ?? 0;
            $completionTokens = $usage['completion_tokens'] ?? 0;

            // 记录成功调用
            $this->keyPool->recordCallResult($keyId, true);
            $this->keyPool->releaseKey($keyId);

            Log::info('CloudLLM 响应完成', [
                'provider'    => $providerCode,
                'model'       => $model,
                'latency_ms'  => $latencyMs,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
            ]);

            return [
                'status'  => 1,
                'message' => [
                    'role'    => $message['role'] ?? 'assistant',
                    'content' => $content,
                ],
                'usage'   => [
                    'prompt_tokens'     => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'total_tokens'      => $promptTokens + $completionTokens,
                ],
                'latency_ms' => $latencyMs,
                'provider'   => $providerCode,
                'model'      => $model,
            ];
        } catch (\Exception $e) {
            $this->keyPool->recordCallResult($keyId, false, $e->getMessage());
            $this->keyPool->releaseKey($keyId);
            return ['status' => 0, 'msg' => '调用异常: ' . $e->getMessage()];
        }
    }

    /**
     * 检查是否有可用的云端LLM供应商
     * @return array ['available'=>true, 'providers'=>[...]]
     */
    public function checkAvailability()
    {
        $available = [];
        foreach (self::$providerPriority as $providerCode) {
            $check = $this->keyPool->checkProviderConfig($providerCode);
            if ($check['has_config'] ?? false) {
                $available[] = [
                    'provider_code' => $providerCode,
                    'key_count' => $check['key_count'] ?? 0,
                    'default_model' => self::$defaultModels[$providerCode] ?? '',
                ];
            }
        }

        return [
            'available' => !empty($available),
            'providers' => $available,
        ];
    }

    /**
     * 检查Ollama本地服务是否可用
     * @return bool
     */
    public static function isOllamaAvailable()
    {
        $ollamaUrl = Config::get('aivideo.ollama.api_url', 'http://127.0.0.1:11434');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $ollamaUrl . '/api/tags',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}

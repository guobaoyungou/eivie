<?php
/**
 * Ollama 本地LLM 对话服务
 * 提供模型列表获取（实时查询已安装模型）和对话消息发送能力
 */
namespace app\service;

use think\facade\Db;
use think\facade\Config;
use think\facade\Log;

class OllamaChatService
{
    /**
     * 获取 Ollama 服务地址
     * 优先从 system_api_key 表读取配置，否则使用配置文件默认值
     * @return string
     */
    public function getOllamaApiUrl()
    {
        $defaultUrl = Config::get('aivideo.ollama.api_url', 'http://127.0.0.1:11434');

        // 从 system_api_key 读取 Ollama 供应商的配置
        $config = Db::name('system_api_key')
            ->where('provider_code', 'ollama')
            ->where('is_active', 1)
            ->order('weight desc, sort asc')
            ->find();

        if (!$config) {
            return $defaultUrl;
        }

        $apiKeyValue = $config['api_key'] ?? '';
        if (!empty($apiKeyValue)) {
            // 尝试解密
            try {
                $trait = new class { use \app\common\ApiKeyEncryptTrait; };
                $decrypted = $trait->decryptApiKey($apiKeyValue);
                if (!empty($decrypted)) {
                    $apiKeyValue = $decrypted;
                }
            } catch (\Throwable $e) {
                // 解密失败，使用原值
            }
        }

        if (empty($apiKeyValue) || $apiKeyValue === 'ollama_local') {
            return $defaultUrl;
        }
        if (filter_var($apiKeyValue, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $apiKeyValue)) {
            return rtrim($apiKeyValue, '/');
        }
        return $defaultUrl;
    }

    /**
     * 获取可用的对话模型列表
     * 实时查询 Ollama /api/tags 并与数据库注册信息合并
     * @return array ['status'=>1, 'models'=>[...]] 或 ['status'=>0, 'msg'=>'...']
     */
    public function getAvailableModels()
    {
        $apiUrl = $this->getOllamaApiUrl();
        $tagsEndpoint = Config::get('aivideo.ollama.tags_endpoint', '/api/tags');
        $url = $apiUrl . $tagsEndpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $errMsg = $curlError ?: ('HTTP ' . $httpCode);
            return [
                'status' => 0,
                'msg'    => 'Ollama 服务连接失败: ' . $errMsg . '。请确认已执行 ollama serve 并监听在 ' . $apiUrl,
            ];
        }

        $data = json_decode($response, true);
        $installedModels = $data['models'] ?? [];

        // 标准化已安装模型名称
        $installedMap = [];
        foreach ($installedModels as $m) {
            $name = $m['name'] ?? $m['model'] ?? '';
            $normalized = preg_replace('/:latest$/', '', $name);
            $installedMap[$normalized] = [
                'raw_name'    => $name,
                'size'        => $m['size'] ?? 0,
                'modified_at' => $m['modified_at'] ?? '',
                'digest'      => $m['digest'] ?? '',
            ];
        }

        // 从数据库获取 Ollama 供应商的注册模型
        $provider = Db::name('model_provider')
            ->where('provider_code', 'ollama')
            ->where('status', 1)
            ->find();

        $registeredModels = [];
        if ($provider) {
            $registeredModels = Db::name('model_info')
                ->alias('m')
                ->field('m.id, m.model_code, m.model_name, m.description, m.model_version, t.type_code, t.type_name')
                ->leftJoin('model_type t', 'm.type_id = t.id')
                ->where('m.provider_id', $provider['id'])
                ->where('m.is_active', 1)
                ->order('m.sort asc, m.id asc')
                ->select()->toArray();
        }

        // 合并：对于数据库注册的模型标记是否已安装，并补充未注册但已安装的模型
        $models = [];
        $matchedInstalled = [];

        foreach ($registeredModels as $reg) {
            $code = $reg['model_code'];
            $installed = isset($installedMap[$code]);
            $matchedInstalled[] = $code;

            // 过滤掉 embedding 模型（不支持对话）
            if (($reg['type_code'] ?? '') === 'embedding') {
                continue;
            }

            $sizeBytes = $installed ? ($installedMap[$code]['size'] ?? 0) : 0;
            $models[] = [
                'model_code'  => $code,
                'model_name'  => $reg['model_name'],
                'description' => $reg['description'] ?? '',
                'type_code'   => $reg['type_code'] ?? 'text_generation',
                'type_name'   => $reg['type_name'] ?? '文本生成',
                'version'     => $reg['model_version'] ?? '',
                'installed'   => $installed,
                'size_gb'     => $sizeBytes > 0 ? round($sizeBytes / 1073741824, 1) : 0,
                'registered'  => true,
            ];
        }

        // 追加已安装但未注册的 chat 类型模型（让用户也能与之对话）
        foreach ($installedMap as $normalized => $info) {
            if (!in_array($normalized, $matchedInstalled)) {
                $sizeBytes = $info['size'] ?? 0;
                $models[] = [
                    'model_code'  => $normalized,
                    'model_name'  => $normalized,
                    'description' => '已安装但未在系统注册的本地模型',
                    'type_code'   => 'text_generation',
                    'type_name'   => '文本生成',
                    'version'     => '',
                    'installed'   => true,
                    'size_gb'     => $sizeBytes > 0 ? round($sizeBytes / 1073741824, 1) : 0,
                    'registered'  => false,
                ];
            }
        }

        return [
            'status'  => 1,
            'models'  => $models,
            'api_url' => $apiUrl,
        ];
    }

    /**
     * 向 Ollama 模型发送对话消息
     * @param string $modelCode 模型标识（如 qwen3:8b）
     * @param array  $messages  对话消息数组 [['role'=>'user','content'=>'...'], ...]
     * @param array  $options   可选参数 ['temperature'=>0.7, 'max_tokens'=>4096, ...]
     * @return array
     */
    public function sendMessage($modelCode, $messages = [], $options = [])
    {
        $apiUrl = $this->getOllamaApiUrl();
        $chatEndpoint = Config::get('aivideo.ollama.chat_endpoint', '/api/chat');
        $url = $apiUrl . $chatEndpoint;

        $ollamaDefaults = Config::get('aivideo.ollama.default_params', []);
        $timeout = Config::get('aivideo.ollama.timeout', 120);

        // 构建请求体
        $body = [
            'model'    => $modelCode,
            'messages' => $messages,
            'stream'   => false,
        ];

        // options
        $ollamaOptions = [];
        $temperature = $options['temperature'] ?? $ollamaDefaults['temperature'] ?? 0.7;
        $ollamaOptions['temperature'] = floatval($temperature);

        $maxTokens = $options['max_tokens'] ?? $ollamaDefaults['max_tokens'] ?? 4096;
        $ollamaOptions['num_predict'] = intval($maxTokens);

        if (isset($options['top_p'])) {
            $ollamaOptions['top_p'] = floatval($options['top_p']);
        }

        $body['options'] = $ollamaOptions;

        Log::info('OllamaChat 发送消息', [
            'url'   => $url,
            'model' => $modelCode,
            'messages_count' => count($messages),
        ]);

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode == 0) {
            return [
                'status'  => 0,
                'msg'     => 'Ollama 服务连接失败: ' . ($curlError ?: '未知错误') . '。请确认服务已启动',
            ];
        }

        if ($httpCode !== 200) {
            $respBody = json_decode($response, true);
            $errorMsg = ($respBody['error'] ?? '') ?: ('HTTP ' . $httpCode);
            if ($httpCode == 404) {
                $errorMsg .= '。模型 ' . $modelCode . ' 可能未安装，请执行: ollama pull ' . $modelCode;
            }
            return [
                'status'  => 0,
                'msg'     => $errorMsg,
            ];
        }

        $respBody = json_decode($response, true);
        if (!is_array($respBody)) {
            return ['status' => 0, 'msg' => 'Ollama 返回无效响应'];
        }

        if (isset($respBody['error'])) {
            return ['status' => 0, 'msg' => $respBody['error']];
        }

        $message = $respBody['message'] ?? [];
        $content = $message['content'] ?? '';
        $promptTokens     = $respBody['prompt_eval_count'] ?? 0;
        $completionTokens = $respBody['eval_count'] ?? 0;
        $totalDuration    = $respBody['total_duration'] ?? 0;

        Log::info('OllamaChat 响应完成', [
            'model'        => $modelCode,
            'latency_ms'   => $latencyMs,
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
            'latency_ms'     => $latencyMs,
            'total_duration' => $totalDuration > 0 ? round($totalDuration / 1000000, 2) : 0,
        ];
    }
}

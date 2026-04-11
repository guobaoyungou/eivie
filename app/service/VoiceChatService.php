<?php
/**
 * 语音对话编排服务
 * 编排 Ollama 文本生成和 VoxCPM2 语音合成的串行调用
 */
namespace app\service;

use think\facade\Db;
use think\facade\Config;
use think\facade\Log;

class VoiceChatService
{
    use \app\common\ApiKeyEncryptTrait;

    /** @var OllamaChatService */
    protected $ollamaService;

    /** @var int 文本最大长度限制 */
    const MAX_TEXT_LENGTH = 500;

    /** @var string|null 缓存的服务模式检测结果 */
    protected static $cachedServerMode = null;

    /** @var string|null 缓存的 API URL */
    protected static $cachedApiUrl = null;

    public function __construct()
    {
        $this->ollamaService = new OllamaChatService();
    }

    // ================================================================
    // VoxCPM2 服务地址解析
    // ================================================================

    /**
     * 获取 VoxCPM2 服务地址
     * 优先从 system_api_key 表读取配置，否则使用配置文件默认值
     * @return string
     */
    public function getVoxCPMApiUrl()
    {
        $defaultUrl = Config::get('aivideo.voxcpm.api_url', 'http://127.0.0.1:8866');

        $config = Db::name('system_api_key')
            ->where('provider_code', 'voxcpm')
            ->where('is_active', 1)
            ->order('weight desc, sort asc')
            ->find();

        if (!$config) {
            return $defaultUrl;
        }

        $apiKeyValue = $config['api_key'] ?? '';
        if (!empty($apiKeyValue)) {
            try {
                $decrypted = $this->decryptApiKey($apiKeyValue);
                if (!empty($decrypted)) {
                    $apiKeyValue = $decrypted;
                }
            } catch (\Throwable $e) {
                // 解密失败，使用原值
            }
        }

        if (empty($apiKeyValue)) {
            return $defaultUrl;
        }
        if (filter_var($apiKeyValue, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $apiKeyValue)) {
            return rtrim($apiKeyValue, '/');
        }
        return $defaultUrl;
    }

    // ================================================================
    // VoxCPM2 服务模式检测与健康检查
    // ================================================================

    /**
     * 检测 VoxCPM2 服务运行模式（带缓存，同一请求周期内只检测一次）
     * @param string $apiUrl
     * @param bool $forceRefresh 强制刷新缓存
     * @return string 'rest_api' | 'gradio' | 'unknown'
     */
    public function detectServerMode($apiUrl, $forceRefresh = false)
    {
        // 使用静态缓存，同一进程请求内只检测一次
        if (!$forceRefresh && self::$cachedApiUrl === $apiUrl && self::$cachedServerMode !== null) {
            return self::$cachedServerMode;
        }
        // 1. 先尝试 REST API 健康检查
        $healthEndpoint = Config::get('aivideo.voxcpm.health_endpoint', '/api/health');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . $healthEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (is_array($data) && isset($data['status'])) {
                self::$cachedApiUrl = $apiUrl;
                self::$cachedServerMode = 'rest_api';
                return 'rest_api';
            }
        }

        // 2. 尝试 Gradio API info 端点
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/gradio_api/info',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (is_array($data) && isset($data['named_endpoints'])) {
                self::$cachedApiUrl = $apiUrl;
                self::$cachedServerMode = 'gradio';
                return 'gradio';
            }
        }

        self::$cachedApiUrl = $apiUrl;
        self::$cachedServerMode = 'unknown';
        return 'unknown';
    }

    /**
     * 检查 VoxCPM2 服务健康状态（自动检测 REST API / Gradio WebUI）
     * @return array
     */
    public function checkVoxCPMHealth()
    {
        $apiUrl = $this->getVoxCPMApiUrl();
        $mode = $this->detectServerMode($apiUrl);

        if ($mode === 'rest_api') {
            return $this->checkHealthRestApi($apiUrl);
        } elseif ($mode === 'gradio') {
            return $this->checkHealthGradio($apiUrl);
        }

        return [
            'online'       => false,
            'message'      => '服务不可达，请确认服务已启动',
            'api_url'      => $apiUrl,
            'server_mode'  => 'unknown',
        ];
    }

    /**
     * REST API 模式健康检查
     */
    protected function checkHealthRestApi($apiUrl)
    {
        $healthEndpoint = Config::get('aivideo.voxcpm.health_endpoint', '/api/health');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . $healthEndpoint,
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
            return [
                'online'       => false,
                'message'      => '服务不可达: ' . ($curlError ?: 'HTTP ' . $httpCode),
                'api_url'      => $apiUrl,
                'server_mode'  => 'rest_api',
            ];
        }

        $data = json_decode($response, true);
        $status = $data['status'] ?? '';
        $modelLoaded = $data['model_loaded'] ?? false;
        $sampleRate = $data['sample_rate'] ?? 0;

        if ($status !== 'ok' || !$modelLoaded) {
            return [
                'online'       => false,
                'message'      => '服务运行中但模型未加载',
                'api_url'      => $apiUrl,
                'sample_rate'  => $sampleRate,
                'server_mode'  => 'rest_api',
            ];
        }

        return [
            'online'       => true,
            'message'      => '服务正常（REST API），模型已加载',
            'api_url'      => $apiUrl,
            'sample_rate'  => $sampleRate,
            'model_name'   => $data['model'] ?? 'VoxCPM2',
            'server_mode'  => 'rest_api',
        ];
    }

    /**
     * Gradio WebUI 模式健康检查
     */
    protected function checkHealthGradio($apiUrl)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/gradio_api/info',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'online'       => false,
                'message'      => 'Gradio WebUI 不可达',
                'api_url'      => $apiUrl,
                'server_mode'  => 'gradio',
            ];
        }

        $data = json_decode($response, true);
        $endpoints = $data['named_endpoints'] ?? [];
        $hasGenerate = isset($endpoints['/generate']);

        if (!$hasGenerate) {
            return [
                'online'       => false,
                'message'      => 'Gradio WebUI 缺少 /generate 端点',
                'api_url'      => $apiUrl,
                'server_mode'  => 'gradio',
            ];
        }

        return [
            'online'       => true,
            'message'      => '服务正常（Gradio WebUI），/generate 端点可用',
            'api_url'      => $apiUrl,
            'sample_rate'  => 48000,
            'model_name'   => 'VoxCPM2 (Gradio)',
            'server_mode'  => 'gradio',
        ];
    }

    // ================================================================
    // 获取可用模型列表 (复用 Ollama)
    // ================================================================

    /**
     * 获取可用 Ollama 对话模型 + VoxCPM2 状态
     * @return array
     */
    public function getModelsAndStatus()
    {
        $ollamaResult = $this->ollamaService->getAvailableModels();
        $voxcpmStatus = $this->checkVoxCPMHealth();

        return [
            'status'        => $ollamaResult['status'] ?? 0,
            'msg'           => $ollamaResult['msg'] ?? '',
            'models'        => $ollamaResult['models'] ?? [],
            'voxcpm_status' => $voxcpmStatus,
        ];
    }

    // ================================================================
    // 文本预处理
    // ================================================================

    /**
     * 预处理 Ollama 回复文本，使其适合语音合成
     * @param string $text
     * @return string
     */
    public function preprocessText($text)
    {
        if (empty($text)) {
            return '';
        }

        // 1. 清除 <think>...</think> 标签及其内容
        $text = preg_replace('/<think>.*?<\/think>/s', '', $text);

        // 2. 清除 Markdown 标记
        // 标题 #
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        // 加粗 ** 或 __
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/__(.*?)__/', '$1', $text);
        // 斜体 * 或 _
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        $text = preg_replace('/(?<!\w)_(.*?)_(?!\w)/', '$1', $text);
        // 行内代码 `
        $text = preg_replace('/`([^`]*)`/', '$1', $text);
        // 代码块 ```
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        // 链接 [text](url)
        $text = preg_replace('/\[([^\]]*)\]\([^\)]*\)/', '$1', $text);
        // 图片 ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]*\)/', '', $text);
        // 列表标记 - * 或 数字.
        $text = preg_replace('/^[\s]*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^[\s]*\d+\.\s+/m', '', $text);
        // 引用 >
        $text = preg_replace('/^>\s*/m', '', $text);
        // 分割线
        $text = preg_replace('/^[-*_]{3,}$/m', '', $text);

        // 3. 空白压缩：连续空行合并为单个换行
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 4. 去首尾空白
        $text = trim($text);

        // 5. 截断过长文本
        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_TEXT_LENGTH);
            // 尝试在句号处截断
            $lastPeriod = mb_strrpos($text, '。');
            if ($lastPeriod === false) {
                $lastPeriod = mb_strrpos($text, '.');
            }
            if ($lastPeriod !== false && $lastPeriod > self::MAX_TEXT_LENGTH * 0.5) {
                $text = mb_substr($text, 0, $lastPeriod + 1);
            }
        }

        return $text;
    }

    // ================================================================
    // 发送语音对话消息（核心编排）
    // ================================================================

    /**
     * 发送语音对话消息（仅文本生成）
     * 只调用 Ollama 获取文本回复，语音合成由前端单独调用 synthesize 接口
     * @param string $modelCode Ollama 模型标识
     * @param array $messages 对话历史
     * @param array $options Ollama 生成参数
     * @return array
     */
    public function sendMessage($modelCode, $messages, $options = [])
    {
        $totalStart = microtime(true);

        // 调用 Ollama 获取文本回复
        $ollamaStart = microtime(true);
        $ollamaResult = $this->ollamaService->sendMessage($modelCode, $messages, $options);
        $ollamaMs = intval((microtime(true) - $ollamaStart) * 1000);

        if (($ollamaResult['status'] ?? 0) != 1) {
            return [
                'status'  => 0,
                'msg'     => $ollamaResult['msg'] ?? 'Ollama 对话失败',
                'error_type' => 'ollama',
                'latency' => ['ollama_ms' => $ollamaMs, 'total_ms' => $ollamaMs],
            ];
        }

        $message = $ollamaResult['message'] ?? [];
        $content = $message['content'] ?? '';
        $totalMs = intval((microtime(true) - $totalStart) * 1000);

        // 预处理文本，生成用于 TTS 的文本
        $processedText = $this->preprocessText($content);

        return [
            'status'         => 1,
            'message'        => $message,
            'processed_text' => $processedText,
            'usage'          => $ollamaResult['usage'] ?? [],
            'latency'        => ['ollama_ms' => $ollamaMs, 'total_ms' => $totalMs],
        ];
    }

    // ================================================================
    // 独立的语音合成接口
    // ================================================================

    /**
     * 独立的语音合成方法（前端在获取文本后单独调用）
     * @param string $text 要合成的文本
     * @param array $voiceOptions 语音参数
     * @return array
     */
    public function synthesize($text, $voiceOptions = [])
    {
        if (empty(trim($text))) {
            return [
                'status'       => 0,
                'msg'          => '合成文本为空',
                'audio_base64' => '',
            ];
        }

        $startTime = microtime(true);
        $audioResult = $this->callVoxCPMTts($text, $voiceOptions);
        $voxcpmMs = intval((microtime(true) - $startTime) * 1000);

        if (!$audioResult['success']) {
            Log::warning('VoiceChat VoxCPM2 合成失败', [
                'error' => $audioResult['error'],
                'text_length' => mb_strlen($text),
            ]);
            return [
                'status'       => 0,
                'msg'          => $audioResult['error'],
                'audio_base64' => '',
                'latency'      => ['voxcpm_ms' => $voxcpmMs],
            ];
        }

        return [
            'status'       => 1,
            'audio_base64' => $audioResult['audio_base64'],
            'latency'      => ['voxcpm_ms' => $voxcpmMs],
        ];
    }

    // ================================================================
    // VoxCPM2 TTS 调用（自动路由 REST API / Gradio）
    // ================================================================

    /**
     * 调用 VoxCPM2 TTS（自动检测服务模式并路由）
     * @param string $text 要合成的文本
     * @param array $voiceOptions 语音参数
     * @return array ['success'=>bool, 'audio_base64'=>string, 'error'=>string]
     */
    protected function callVoxCPMTts($text, $voiceOptions = [])
    {
        $apiUrl = $this->getVoxCPMApiUrl();
        $mode = $this->detectServerMode($apiUrl);

        Log::info('VoiceChat VoxCPM2 TTS 请求', [
            'api_url'     => $apiUrl,
            'server_mode' => $mode,
            'text_length' => mb_strlen($text),
            'has_control' => !empty($voiceOptions['control']),
        ]);

        if ($mode === 'gradio') {
            return $this->callGradioTts($apiUrl, $text, $voiceOptions);
        } elseif ($mode === 'rest_api') {
            return $this->callRestApiTts($apiUrl, $text, $voiceOptions);
        }

        return [
            'success'      => false,
            'audio_base64' => '',
            'error'        => 'VoxCPM2 服务不可达，请确认 voxcpm_server.py 或 Gradio WebUI 已启动',
        ];
    }

    /**
     * REST API 模式 TTS 调用（POST /api/tts）
     */
    protected function callRestApiTts($apiUrl, $text, $voiceOptions = [])
    {
        $ttsEndpoint = Config::get('aivideo.voxcpm.tts_endpoint', '/api/tts');
        $url = $apiUrl . $ttsEndpoint;
        $timeout = min(intval(Config::get('aivideo.voxcpm.timeout', 300)), 80);
        $defaults = Config::get('aivideo.voxcpm.default_params', []);

        $body = [
            'text'                 => $text,
            'control'              => $voiceOptions['control'] ?? '',
            'cfg_value'            => floatval($voiceOptions['cfg_value'] ?? $defaults['cfg_value'] ?? 2.0),
            'inference_timesteps'  => intval($voiceOptions['inference_timesteps'] ?? $defaults['inference_timesteps'] ?? 10),
        ];
        if (empty($body['control'])) {
            unset($body['control']);
        }

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

        if ($response === false || $httpCode == 0) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'VoxCPM2 REST API 连接失败: ' . ($curlError ?: '未知错误')];
        }
        if ($httpCode !== 200) {
            $errBody = json_decode($response, true);
            $detail = $errBody['detail'] ?? ($errBody['error'] ?? 'HTTP ' . $httpCode);
            return ['success' => false, 'audio_base64' => '', 'error' => 'VoxCPM2 合成失败: ' . $detail];
        }

        $data = json_decode($response, true);
        $audioBase64 = $data['audio_base64'] ?? '';
        if (empty($audioBase64)) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'VoxCPM2 返回的音频数据为空'];
        }

        Log::info('VoiceChat VoxCPM2 REST TTS 完成', [
            'duration' => $data['duration'] ?? 0, 'generation_time' => $data['generation_time'] ?? 0,
        ]);
        return ['success' => true, 'audio_base64' => $audioBase64, 'error' => ''];
    }

    // ================================================================
    // Gradio WebUI TTS 调用
    // ================================================================

    /**
     * 通过 Gradio WebUI API 进行 TTS 调用
     * 流程: POST /gradio_api/call/generate → 获取 event_id → GET SSE → 解析音频 → 下载转 base64
     * @param string $apiUrl 服务地址
     * @param string $text 要合成的文本
     * @param array $voiceOptions 语音参数
     * @return array
     */
    protected function callGradioTts($apiUrl, $text, $voiceOptions = [])
    {
        $defaults = Config::get('aivideo.voxcpm.default_params', []);
        $control = $voiceOptions['control'] ?? '';
        $cfgValue = floatval($voiceOptions['cfg_value'] ?? $defaults['cfg_value'] ?? 2.0);
        $ditSteps = intval($voiceOptions['inference_timesteps'] ?? $defaults['inference_timesteps'] ?? 10);

        // Gradio /generate 参数: [text, control_instruction, ref_wav, use_prompt_text, prompt_text_value, cfg_value, do_normalize, denoise, dit_steps]
        $gradioData = [
            $text,           // text
            $control,        // control_instruction
            null,            // ref_wav (无参考音频=纯 TTS)
            false,           // use_prompt_text
            '',              // prompt_text_value
            $cfgValue,       // cfg_value
            true,            // do_normalize
            false,           // denoise
            $ditSteps,       // dit_steps
        ];

        // 步骤1: 提交任务
        $submitUrl = $apiUrl . '/gradio_api/call/generate';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $submitUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['data' => $gradioData], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $submitResp = curl_exec($ch);
        $submitCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $submitErr = curl_error($ch);
        curl_close($ch);

        if ($submitResp === false || $submitCode !== 200) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'Gradio 提交失败: ' . ($submitErr ?: 'HTTP ' . $submitCode)];
        }

        $submitData = json_decode($submitResp, true);
        $eventId = $submitData['event_id'] ?? '';
        if (empty($eventId)) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'Gradio 未返回 event_id'];
        }

        Log::info('VoiceChat Gradio 任务已提交', ['event_id' => $eventId]);

        // 步骤2: 轮询 SSE 结果
        $pollUrl = $apiUrl . '/gradio_api/call/generate/' . $eventId;
        // 读取 Gradio SSE 专用超时，若未配置则使用通用 timeout，上限300秒下限30秒
        $sseTimeout = intval(Config::get('aivideo.voxcpm.gradio_sse_timeout', 0));
        if ($sseTimeout <= 0) {
            $sseTimeout = intval(Config::get('aivideo.voxcpm.timeout', 180));
        }
        $sseTimeout = max(min($sseTimeout, 300), 30);
        $sseResult = $this->pollGradioSSE($pollUrl, $sseTimeout);

        if (!$sseResult['success']) {
            return ['success' => false, 'audio_base64' => '', 'error' => $sseResult['error']];
        }

        // 步骤3: 解析音频文件并下载
        $audioInfo = $sseResult['data'];
        if (!is_array($audioInfo) || empty($audioInfo)) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'Gradio 返回的音频数据为空'];
        }

        // Gradio 返回格式: [{"path":"...", "url":"...", ...}]
        $audioFile = is_array($audioInfo[0] ?? null) ? $audioInfo[0] : $audioInfo;
        $audioUrl = $audioFile['url'] ?? '';

        if (empty($audioUrl)) {
            // 尝试从 path 构建 URL
            $audioPath = $audioFile['path'] ?? '';
            if (!empty($audioPath)) {
                $audioUrl = $apiUrl . '/gradio_api/file=' . $audioPath;
            }
        } else {
            // 确保 URL 是绝对路径
            if (strpos($audioUrl, 'http') !== 0) {
                $audioUrl = $apiUrl . $audioUrl;
            }
        }

        if (empty($audioUrl)) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'Gradio 返回的音频无有效URL'];
        }

        // 步骤4: 下载音频并转 base64
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $audioUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $audioBytes = curl_exec($ch);
        $dlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $dlErr = curl_error($ch);
        curl_close($ch);

        if ($audioBytes === false || $dlCode !== 200 || empty($audioBytes)) {
            return ['success' => false, 'audio_base64' => '', 'error' => 'Gradio 音频文件下载失败: ' . ($dlErr ?: 'HTTP ' . $dlCode)];
        }

        $audioBase64 = base64_encode($audioBytes);
        Log::info('VoiceChat Gradio TTS 完成', [
            'event_id'   => $eventId,
            'audio_size' => strlen($audioBytes),
        ]);

        return ['success' => true, 'audio_base64' => $audioBase64, 'error' => ''];
    }

    /**
     * 轮询 Gradio SSE 结果
     * @param string $pollUrl SSE 端点 URL
     * @param int $timeout 最大等待时间(秒)
     * @return array ['success'=>bool, 'data'=>mixed, 'error'=>string]
     */
    protected function pollGradioSSE($pollUrl, $timeout = 180)
    {
        $startTime = time();
        $buffer = '';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $pollUrl,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Accept: text/event-stream'],
            CURLOPT_LOW_SPEED_LIMIT => 1,   // 最低数据速率: 1 byte/s
            CURLOPT_LOW_SPEED_TIME  => 90,  // 低于阈值持续90秒才中断（heartbeat保活）
        ]);

        $result = ['success' => false, 'data' => null, 'error' => '超时'];

        // 使用 WRITEFUNCTION 来解析 SSE 流
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$result, $startTime, $timeout) {
            $buffer .= $chunk;

            // 解析 SSE 事件
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $eventBlock = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                $event = '';
                $data = '';
                foreach (explode("\n", $eventBlock) as $line) {
                    if (strpos($line, 'event: ') === 0) {
                        $event = trim(substr($line, 7));
                    } elseif (strpos($line, 'data: ') === 0) {
                        $data = substr($line, 6);
                    }
                }

                if ($event === 'complete') {
                    $parsed = json_decode($data, true);
                    $result = ['success' => true, 'data' => $parsed, 'error' => ''];
                    return 0; // 停止接收
                } elseif ($event === 'error') {
                    $parsed = json_decode($data, true);
                    $errMsg = is_array($parsed) ? ($parsed['message'] ?? json_encode($parsed)) : strval($data);
                    $result = ['success' => false, 'data' => null, 'error' => 'Gradio 错误: ' . $errMsg];
                    return 0;
                }
                // heartbeat 等其他事件，继续等待
            }

            // 检查超时
            if ((time() - $startTime) > $timeout) {
                $result = ['success' => false, 'data' => null, 'error' => 'Gradio TTS 超时 (' . $timeout . 's)'];
                return 0;
            }

            return strlen($chunk);
        });

        curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$result['success'] && $result['error'] === '超时' && !empty($curlError)) {
            $result['error'] = 'Gradio SSE 连接错误: ' . $curlError;
        }

        return $result;
    }
}

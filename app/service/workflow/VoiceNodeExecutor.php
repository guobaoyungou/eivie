<?php
/**
 * 配音节点执行器
 * 通过 VoiceChatService 将对话/旁白文本转化为语音片段
 */
namespace app\service\workflow;

use app\model\WorkflowNode;
use app\service\VoiceChatService;
use think\facade\Db;
use think\facade\Log;

class VoiceNodeExecutor implements NodeExecutorInterface
{
    /** @var VoiceChatService */
    protected $voiceService;

    public function __construct()
    {
        $this->voiceService = new VoiceChatService();
    }

    /**
     * 执行语音合成
     */
    public function execute(WorkflowNode $node, array $inputData): array
    {
        $config = $node->config_params;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        // 对话文本来自上游剧本节点
        $dialogues = $inputData['dialogue'] ?? [];
        // 角色资产（来自上游角色节点，包含音色信息）
        $characterAssets = $inputData['character_assets'] ?? [];
        // 角色-音色映射（手动配置优先，否则从角色资产自动构建）
        $voiceMapping = $config['voice_mapping'] ?? [];
        if (empty($voiceMapping) && !empty($characterAssets)) {
            $voiceMapping = $this->buildVoiceMappingFromAssets($characterAssets);
        }
        // 默认语速
        $speed = floatval($config['speed'] ?? 1.0);
        // 合成模式
        $synthMode = $config['synth_mode'] ?? 'sound_design';

        // 解析模型选择：从 model_id 查询模型信息
        $modelId = intval($config['model_id'] ?? 0);
        $modelCode = '';
        if ($modelId > 0) {
            $modelInfo = Db::name('model_info')->alias('m')
                ->leftJoin('model_provider p', 'm.provider_id = p.id')
                ->field('m.model_code, p.provider_code, m.model_name')
                ->where('m.id', $modelId)
                ->where('m.is_active', 1)
                ->find();
            if ($modelInfo) {
                $modelCode = $modelInfo['model_code'];
                Log::info('VoiceNode: 使用用户选择的语音模型 ' . ($modelInfo['model_name'] ?? $modelCode));
            }
        }

        if (empty($dialogues)) {
            // 无对话数据时优雅降级：返回空音频列表，允许工作流继续
            return [
                'status' => 1,
                'msg'    => '无对话文本，跳过配音环节',
                'data'   => ['audio_clips' => []],
            ];
        }

        // 检测TTS服务是否可用
        $ttsAvailable = $this->checkTTSAvailable();

        $audioClips = [];
        $allSuccess = true;

        foreach ($dialogues as $index => $dialogue) {
            $text = $dialogue['text'] ?? '';
            $characterTags = $dialogue['character_tags'] ?? [];

            if (empty($text)) {
                continue;
            }

            if (!$ttsAvailable) {
                // TTS不可用时生成静音占位文件
                $silenceResult = $this->generateSilencePlaceholder($text, $index, $node);
                $audioClips[] = [
                    'scene_index'   => $dialogue['scene_index'] ?? $index,
                    'audio_url'     => $silenceResult['audio_url'] ?? '',
                    'duration'      => $silenceResult['duration'] ?? 3,
                    'character_tag' => $characterTags[0] ?? 'narrator',
                    'text'          => $text,
                    'is_silence'    => true,
                ];
                continue;
            }

            // 确定使用的音色
            $voiceParams = $this->resolveVoiceParams($characterTags, $voiceMapping, $synthMode);

            try {
                // 调用 VoiceChatService 进行 TTS
                $ttsResult = $this->synthesizeSpeech($text, $voiceParams, $speed);

                if (($ttsResult['status'] ?? 0) == 1) {
                    $audioClips[] = [
                        'scene_index'   => $dialogue['scene_index'] ?? $index,
                        'audio_url'     => $ttsResult['audio_url'] ?? '',
                        'duration'      => $ttsResult['duration'] ?? 0,
                        'character_tag' => $characterTags[0] ?? 'narrator',
                        'text'          => $text,
                    ];
                } else {
                    $allSuccess = false;
                    $audioClips[] = [
                        'scene_index'   => $dialogue['scene_index'] ?? $index,
                        'audio_url'     => '',
                        'character_tag' => $characterTags[0] ?? 'narrator',
                        'text'          => $text,
                        'error'         => $ttsResult['msg'] ?? '语音合成失败',
                    ];
                }
            } catch (\Exception $e) {
                $allSuccess = false;
                Log::error("配音 [{$index}] 合成异常: " . $e->getMessage());
                $audioClips[] = [
                    'scene_index'   => $dialogue['scene_index'] ?? $index,
                    'audio_url'     => '',
                    'character_tag' => $characterTags[0] ?? 'narrator',
                    'text'          => $text,
                    'error'         => $e->getMessage(),
                ];
            }
        }

        // 如果TTS可用但所有调用都失败了，回退到静音占位
        if (!$allSuccess && $ttsAvailable) {
            $failedCount = count(array_filter($audioClips, fn($c) => !empty($c['error'])));
            $totalCount = count($audioClips);
            if ($failedCount >= $totalCount && $totalCount > 0) {
                Log::warning('VoiceNode: 所有TTS调用失败，回退到静音占位模式');
                $audioClips = [];
                foreach ($dialogues as $idx => $dlg) {
                    $txt = $dlg['text'] ?? '';
                    if (empty($txt)) continue;
                    $cTags = $dlg['character_tags'] ?? [];
                    $silenceResult = $this->generateSilencePlaceholder($txt, $idx, $node);
                    $audioClips[] = [
                        'scene_index'   => $dlg['scene_index'] ?? $idx,
                        'audio_url'     => $silenceResult['audio_url'] ?? '',
                        'duration'      => $silenceResult['duration'] ?? 3,
                        'character_tag' => $cTags[0] ?? 'narrator',
                        'text'          => $txt,
                        'is_silence'    => true,
                    ];
                }
                $ttsAvailable = false;
                $allSuccess = true;
            }
        }

        return [
            'status' => $allSuccess ? 1 : ($ttsAvailable ? 0 : 1),
            'msg'    => $ttsAvailable 
                ? ($allSuccess ? '配音生成成功' : '部分配音生成失败')
                : '配音服务不可用，已使用静音占位（仅含视频画面）',
            'data'   => [
                'audio_clips' => $audioClips,
            ],
        ];
    }

    /**
     * 检查TTS服务是否可用
     */
    protected function checkTTSAvailable()
    {
        try {
            $apiUrl = $this->voiceService->getVoxCPMApiUrl();
            $mode = $this->voiceService->detectServerMode($apiUrl);
            return $mode !== 'unknown';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 生成静音占位音频文件（当TTS不可用时）
     * 根据文本长度估算时长，生成对应长度的静音WAV文件
     */
    protected function generateSilencePlaceholder($text, $index, $node)
    {
        // 按中文语速估算：约每秒4个字
        $charCount = mb_strlen($text, 'UTF-8');
        $duration = max(2, ceil($charCount / 4));

        $tempDir = runtime_path() . 'workflow_compose/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $silencePath = $tempDir . 'silence_' . $node->id . '_' . $index . '.wav';

        // 使用FFmpeg生成静音WAV
        $cmd = "ffmpeg -y -f lavfi -i anullsrc=r=48000:cl=mono -t {$duration} -q:a 9 -acodec pcm_s16le {$silencePath} 2>&1";
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($silencePath)) {
            return [
                'audio_url' => $silencePath,
                'duration'  => $duration,
            ];
        }

        return ['audio_url' => '', 'duration' => $duration];
    }

    /**
     * 解析角色对应的音色参数
     */
    protected function resolveVoiceParams($characterTags, $voiceMapping, $synthMode)
    {
        $params = [
            'synth_mode' => $synthMode,
            'voice_id'   => '',
            'voice_desc' => '',
            'reference_audio' => '',
        ];

        foreach ($characterTags as $tag) {
            if (isset($voiceMapping[$tag])) {
                $mapping = $voiceMapping[$tag];
                if (is_array($mapping)) {
                    $params = array_merge($params, $mapping);
                } else {
                    $params['voice_id'] = $mapping;
                }
                break;
            }
        }

        // 如果有参考音频，切换为可控克隆模式
        if (!empty($params['reference_audio'])) {
            $params['synth_mode'] = 'controllable_clone';
            Log::info('VoiceNode: 角色 ' . ($characterTags[0] ?? '') . ' 使用参考音频克隆模式');
        }

        // 如果没有映射到音色，尝试从资源库获取默认音色
        if (empty($params['voice_id']) && empty($params['reference_audio'])) {
            $defaultVoice = Db::name('workflow_resource')
                ->where('resource_type', 'voice')
                ->where('is_system', 1)
                ->where('status', 1)
                ->order('usage_count desc')
                ->find();

            if ($defaultVoice) {
                $contentData = is_string($defaultVoice['content_data'])
                    ? json_decode($defaultVoice['content_data'], true)
                    : $defaultVoice['content_data'];
                $params['voice_id'] = $contentData['voice_id'] ?? '';
            }
        }

        return $params;
    }

    /**
     * 从角色资产构建音色映射
     * 将角色的voice_desc和reference_audio转换为音色映射表
     */
    protected function buildVoiceMappingFromAssets(array $characterAssets): array
    {
        $mapping = [];
        foreach ($characterAssets as $asset) {
            $tag = $asset['tag'] ?? '';
            if (empty($tag)) continue;
            
            $voiceDesc = $asset['voice_desc'] ?? '';
            $refAudio = $asset['reference_audio'] ?? '';
            
            if (!empty($voiceDesc) || !empty($refAudio)) {
                $mapping[$tag] = [
                    'voice_desc'      => $voiceDesc,
                    'reference_audio' => $refAudio,
                ];
                Log::info("VoiceNode: 角色 [{$tag}] 音色映射 - " . 
                    (!empty($voiceDesc) ? "描述:{$voiceDesc}" : '') . 
                    (!empty($refAudio) ? " 参考音频:{$refAudio}" : ''));
            }
        }
        return $mapping;
    }

    /**
     * 调用TTS服务合成语音
     */
    protected function synthesizeSpeech($text, $voiceParams, $speed)
    {
        // 调用 VoiceChatService 的 TTS 能力
        // VoxCPM2 支持声音设计/可控克隆/极致克隆模式
        $synthMode = $voiceParams['synth_mode'] ?? 'sound_design';

        try {
            $apiUrl = $this->voiceService->getVoxCPMApiUrl();
            $mode = $this->voiceService->detectServerMode($apiUrl);

            if ($mode === 'unknown') {
                return ['status' => 0, 'msg' => 'VoxCPM2 语音合成服务不可用'];
            }

            // 构建TTS请求
            $ttsParams = [
                'text'       => $text,
                'speed'      => $speed,
                'synth_mode' => $synthMode,
            ];

            if (!empty($voiceParams['voice_id'])) {
                $ttsParams['voice_id'] = $voiceParams['voice_id'];
            }
            if (!empty($voiceParams['reference_audio'])) {
                $ttsParams['reference_audio'] = $voiceParams['reference_audio'];
            }

            // 调用REST API模式的TTS端点
            if ($mode === 'rest_api') {
                $result = $this->callRestApiTts($apiUrl, $ttsParams);
            } else {
                $result = $this->callGradioTts($apiUrl, $ttsParams);
            }

            return $result;
        } catch (\Exception $e) {
            return ['status' => 0, 'msg' => '语音合成调用失败: ' . $e->getMessage()];
        }
    }

    /**
     * REST API 模式调用 TTS
     */
    protected function callRestApiTts($apiUrl, $params)
    {
        $endpoint = $apiUrl . '/api/tts';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return ['status' => 0, 'msg' => '语音合成API请求失败'];
        }

        $data = json_decode($response, true);
        return [
            'status'    => 1,
            'audio_url' => $data['audio_url'] ?? '',
            'duration'  => $data['duration'] ?? 0,
        ];
    }

    /**
     * Gradio 模式调用 TTS（降级方案）
     */
    protected function callGradioTts($apiUrl, $params)
    {
        // Gradio模式的TTS调用逻辑
        // 简化处理，实际需要通过Gradio API调用
        return ['status' => 0, 'msg' => 'Gradio模式TTS暂不支持工作流调用'];
    }
}

<?php
/**
 * 剧本节点执行器
 * 通过 CloudLLMService（云端）或 OllamaChatService（本地）调用 LLM 生成结构化剧本JSON
 * 优先使用本地Ollama，不可用时自动降级到云端LLM（aliyun/volcengine）
 */
namespace app\service\workflow;

use app\model\WorkflowNode;
use app\service\OllamaChatService;
use app\service\CloudLLMService;
use think\facade\Log;
use think\facade\Db;

class ScriptNodeExecutor implements NodeExecutorInterface
{
    /** @var OllamaChatService */
    protected $ollamaService;

    /** @var CloudLLMService */
    protected $cloudLLMService;

    public function __construct()
    {
        $this->ollamaService   = new OllamaChatService();
        $this->cloudLLMService = new CloudLLMService();
    }

    /**
     * 执行剧本生成
     */
    public function execute(WorkflowNode $node, array $inputData): array
    {
        $config = $node->config_params;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        $creativity = $config['creativity'] ?? '';
        $episodes   = intval($config['episodes'] ?? 3);
        $duration   = intval($config['duration'] ?? 60);
        $genre      = $config['genre'] ?? '甄宠';
        
        // 解析模型选择：优先从modelId查DB，否则使用model_code
        $modelId    = intval($config['model_id'] ?? 0);
        $modelCode  = $config['model_code'] ?? '';
        $providerCode = '';
        if ($modelId > 0) {
            $modelInfo = Db::name('model_info')->alias('m')
                ->leftJoin('model_provider p', 'm.provider_id = p.id')
                ->field('m.model_code, p.provider_code')
                ->where('m.id', $modelId)
                ->where('m.is_active', 1)
                ->find();
            if ($modelInfo) {
                $modelCode = $modelInfo['model_code'];
                $providerCode = $modelInfo['provider_code'] ?? '';
            }
        }
        if (empty($modelCode)) {
            $modelCode = 'qwen3:8b';
        }

        if (empty($creativity)) {
            return ['status' => 0, 'msg' => '请输入创意描述'];
        }

        // 构建系统提示词
        $systemPrompt = $this->buildSystemPrompt($episodes, $duration, $genre);

        // 构建消息
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => '请根据以下创意生成短剧剧本：' . $creativity],
        ];

        try {
            // 策略：优先Ollama本地，不可用时自动降级到云端LLM
            $result = $this->callLLM($modelCode, $messages, $providerCode);

            if (($result['status'] ?? 0) != 1) {
                return ['status' => 0, 'msg' => $result['msg'] ?? 'LLM调用失败'];
            }

            $content = $result['message']['content'] ?? '';

            // 尝试从LLM输出中解析JSON
            $scriptData = $this->parseScriptJson($content);

            if (!$scriptData) {
                // 如果无法解析为JSON，包装为简单结构
                $scriptData = [
                    'title'      => $creativity,
                    'genre'      => $genre,
                    'episodes'   => [],
                    'characters' => [],
                    'raw_text'   => $content,
                ];
            }

            return [
                'status' => 1,
                'msg'    => '剧本生成成功',
                'data'   => [
                    'characters' => $scriptData['characters'] ?? [],
                    'scenes'     => $this->extractScenes($scriptData),
                    'dialogue'   => $this->extractDialogue($scriptData),
                    'full_script' => $scriptData,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('剧本节点执行失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '剧本生成失败: ' . $e->getMessage()];
        }
    }

    /**
     * 调用LLM服务（自动选择本地或云端）
     */
    protected function callLLM($modelCode, $messages, $providerCode = '')
    {
        // 1. 如果是Ollama本地模型且可用，直接调用
        if ($providerCode === 'ollama' || (empty($providerCode) && CloudLLMService::isOllamaAvailable())) {
            if (CloudLLMService::isOllamaAvailable()) {
                Log::info('ScriptNode: 使用本地Ollama模型 ' . $modelCode);
                return $this->ollamaService->sendMessage($modelCode, $messages, [
                    'temperature' => 0.8,
                    'max_tokens'  => 8192,
                ]);
            }
        }

        // 2. 使用云端LLM（支持指定供应商和模型）
        Log::info('ScriptNode: 使用云端LLM' . ($providerCode ? " [供应商:{$providerCode}]" : '') . ' 模型:' . $modelCode);
        $options = [
            'temperature' => 0.8,
            'max_tokens'  => 8192,
            'timeout'     => 180,
        ];
        if (!empty($providerCode) && $providerCode !== 'ollama') {
            $options['provider'] = $providerCode;
        }
        if (!empty($modelCode)) {
            $options['model'] = $modelCode;
        }
        return $this->cloudLLMService->sendMessage($messages, $options);
    }

    /**
     * 构建系统提示词
     */
    protected function buildSystemPrompt($episodes, $duration, $genre)
    {
        return <<<PROMPT
你是一位专业的短剧编剧AI。请根据用户提供的创意，生成一部完整的短剧剧本。

要求：
1. 题材风格：{$genre}
2. 总集数：{$episodes} 集
3. 每集目标时长：{$duration} 秒
4. 输出格式：严格的JSON格式

JSON结构：
{
  "title": "短剧标题",
  "genre": "题材",
  "episodes": [
    {
      "episode_number": 1,
      "title": "第1集标题",
      "scenes": [
        {
          "scene_index": 1,
          "scene_desc": "详细的画面描述（用于AI绘图）",
          "dialogue": "角色对话或旁白文本",
          "character_tags": ["角色标签1", "角色标签2"],
          "camera_hint": "镜头提示（近景/中景/远景/特写）",
          "duration_hint": 5
        }
      ]
    }
  ],
  "characters": [
    {
      "tag": "唯一英文标签",
      "name": "角色中文名",
      "appearance": "详细外貌描述（发型、肤色、五官、服装、体型）",
      "personality": "性格描述"
    }
  ]
}

请确保：
- scene_desc 足够详细，可直接用于AI图像生成
- character_tags 使用characters中定义的tag
- 每个scene都要有dialogue（可以是对话或旁白）
- appearance描述要非常具体，便于保持角色一致性
PROMPT;
    }

    /**
     * 从LLM输出中解析JSON
     */
    protected function parseScriptJson($content)
    {
        // 尝试直接解析
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // 尝试从代码块中提取
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $content, $matches)) {
            $data = json_decode(trim($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        // 尝试查找JSON对象
        if (preg_match('/\{[\s\S]*\}/m', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    /**
     * 从剧本数据中提取所有分镜列表
     */
    protected function extractScenes($scriptData)
    {
        $scenes = [];
        foreach ($scriptData['episodes'] ?? [] as $episode) {
            foreach ($episode['scenes'] ?? [] as $scene) {
                $scenes[] = $scene;
            }
        }
        return $scenes;
    }

    /**
     * 从剧本数据中提取所有对话/旁白
     */
    protected function extractDialogue($scriptData)
    {
        $dialogues = [];
        foreach ($scriptData['episodes'] ?? [] as $episode) {
            foreach ($episode['scenes'] ?? [] as $scene) {
                if (!empty($scene['dialogue'])) {
                    $dialogues[] = [
                        'scene_index'    => $scene['scene_index'] ?? 0,
                        'text'           => $scene['dialogue'],
                        'character_tags' => $scene['character_tags'] ?? [],
                    ];
                }
            }
        }
        return $dialogues;
    }
}

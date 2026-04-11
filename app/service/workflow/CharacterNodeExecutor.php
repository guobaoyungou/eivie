<?php
/**
 * 角色节点执行器
 * 通过 GenerationService 调用图像生成模型生成角色形象图集
 */
namespace app\service\workflow;

use app\model\WorkflowNode;
use app\service\GenerationService;
use app\service\CharacterConsistencyService;
use app\model\GenerationRecord;
use think\facade\Log;

class CharacterNodeExecutor implements NodeExecutorInterface
{
    /** @var GenerationService */
    protected $generationService;

    /** @var CharacterConsistencyService */
    protected $consistencyService;

    public function __construct()
    {
        $this->generationService  = new GenerationService();
        $this->consistencyService = new CharacterConsistencyService();
    }

    /**
     * 执行角色形象生成
     */
    public function execute(WorkflowNode $node, array $inputData): array
    {
        $config = $node->config_params;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        // 角色来源判断：手动定义 vs 上游自动获取
        $charSource = $config['char_source'] ?? 'auto';
        $manualChars = $config['characters'] ?? [];
        
        if ($charSource === 'manual' && !empty($manualChars)) {
            $characters = $manualChars;
            Log::info('CharacterNode: 使用用户手动定义的 ' . count($characters) . ' 个角色');
        } else {
            $characters = $inputData['characters'] ?? [];
        }
        
        $styleRef   = $config['style'] ?? 'realistic';
        $modelId    = $node->model_id ?: ($config['model_id'] ?? 0);

        if (empty($characters)) {
            return ['status' => 0, 'msg' => '无角色数据，请先连接剧本节点或手动添加角色'];
        }

        // 如果没有指定 model_id，自动选择一个可用的图像生成模型
        if ($modelId <= 0) {
            $modelId = $this->findAvailableImageModel();
        }

        $characterAssets = [];
        $hasAsync = false;

        foreach ($characters as $charIndex => $character) {
            $tag  = $character['tag'] ?? 'char_' . uniqid();
            $name = $character['name'] ?? '未命名角色';
            $appearance = $character['appearance'] ?? '';
            $refImages = $character['reference_images'] ?? [];
            $voiceDesc = $character['voice_desc'] ?? '';
            $refAudio = $character['reference_audio'] ?? '';

            // 避免并发限制：第2个角色开始延迟
            if ($charIndex > 0) {
                sleep(5);
            }

            // 如果用户提供了参考图（三视图），直接使用而不生成
            if (!empty($refImages)) {
                Log::info("角色 [{$name}] 使用用户上传的参考图 (" . count($refImages) . "张)");
                $asset = [
                    'tag'               => $tag,
                    'name'              => $name,
                    'appearance_prompt'  => $appearance,
                    'images'            => $refImages,
                    'reference_images'  => $refImages,
                    'style'             => $styleRef,
                    'style_seed'        => rand(10000, 99999),
                    'voice_desc'        => $voiceDesc,
                    'reference_audio'   => $refAudio,
                    'record_id'         => 0,
                    'status'            => 'completed',
                    'is_user_uploaded'  => true,
                ];
                $characterAssets[] = $asset;

                // 构建角色身份卡
                $this->consistencyService->buildIdCard($node->project_id, $asset, [
                    'aid'  => $node->aid,
                    'bid'  => $node->bid,
                    'mdid' => $node->mdid,
                    'uid'  => $node->uid,
                ]);
                continue;
            }

            // 没有参考图，通过AI模型生成角色形象
            $prompt = $this->buildCharacterPrompt($name, $appearance, $styleRef);

            try {
                $taskResult = $this->generationService->createTask([
                    'aid'             => $node->aid,
                    'bid'             => $node->bid,
                    'uid'             => $node->uid,
                    'generation_type' => GenerationRecord::TYPE_PHOTO,
                    'model_id'        => $modelId,
                    'input_params'    => [
                        'prompt'       => $prompt,
                        'aspect_ratio' => '3:4',
                        'num_images'   => 1,
                        'style'        => $styleRef,
                    ],
                ]);

                if (($taskResult['status'] ?? 0) == 1) {
                    $recordId = $taskResult['record_id'] ?? 0;
                    $record = \think\facade\Db::name('generation_record')->where('id', $recordId)->find();
                    $isComplete = ($record && $record['status'] == GenerationRecord::STATUS_SUCCESS);

                    if ($isComplete) {
                        $images = $this->getGenerationOutputs($recordId);
                        $asset = [
                            'tag'              => $tag,
                            'name'             => $name,
                            'appearance_prompt' => $appearance,
                            'images'           => $images,
                            'style'            => $styleRef,
                            'style_seed'       => rand(10000, 99999),
                            'voice_desc'       => $voiceDesc,
                            'reference_audio'  => $refAudio,
                            'record_id'        => $recordId,
                            'status'           => 'completed',
                        ];
                        $characterAssets[] = $asset;

                        $this->consistencyService->buildIdCard($node->project_id, $asset, [
                            'aid'  => $node->aid,
                            'bid'  => $node->bid,
                            'mdid' => $node->mdid,
                            'uid'  => $node->uid,
                        ]);
                    } else {
                        $hasAsync = true;
                        $characterAssets[] = [
                            'tag'       => $tag,
                            'name'      => $name,
                            'appearance_prompt' => $appearance,
                            'images'    => [],
                            'style'     => $styleRef,
                            'voice_desc'     => $voiceDesc,
                            'reference_audio' => $refAudio,
                            'record_id' => $recordId,
                            'status'    => 'processing',
                        ];
                    }
                } else {
                    Log::warning("角色 [{$name}] 形象生成失败: " . ($taskResult['msg'] ?? ''));
                    $characterAssets[] = [
                        'tag'    => $tag,
                        'name'   => $name,
                        'images' => [],
                        'voice_desc'     => $voiceDesc,
                        'reference_audio' => $refAudio,
                        'error'  => $taskResult['msg'] ?? '生成失败',
                        'status' => 'failed',
                    ];
                }
            } catch (\Exception $e) {
                Log::error("角色 [{$name}] 形象生成异常: " . $e->getMessage());
                $characterAssets[] = [
                    'tag'    => $tag,
                    'name'   => $name,
                    'images' => [],
                    'voice_desc'     => $voiceDesc,
                    'reference_audio' => $refAudio,
                    'error'  => $e->getMessage(),
                    'status' => 'failed',
                ];
            }
        }

        if ($hasAsync) {
            return [
                'status'  => 2,
                'msg'     => '角色形象生成任务已提交，等待处理',
                'task_id' => 'batch_character_' . $node->id,
                'data'    => ['character_assets' => $characterAssets],
            ];
        }

        $allSuccess = !array_filter($characterAssets, fn($a) => ($a['status'] ?? '') === 'failed');
        return [
            'status' => $allSuccess ? 1 : 0,
            'msg'    => $allSuccess ? '角色形象生成成功' : '部分角色生成失败',
            'data'   => [
                'character_assets' => $characterAssets,
            ],
        ];
    }

    /**
     * 轮询异步角色生成状态
     */
    public function pollStatus(WorkflowNode $node)
    {
        $outputData = $node->output_data;
        if (is_string($outputData)) {
            $outputData = json_decode($outputData, true);
        }

        $characterAssets = $outputData['character_assets'] ?? [];
        $allComplete = true;
        $anyFailed = false;

        // 触发异步任务轮询（通过查询状态触发DashScope轮询）
        $genService = new GenerationService();

        foreach ($characterAssets as &$asset) {
            if (($asset['status'] ?? '') === 'processing' && !empty($asset['record_id'])) {
                // getRecordStatus内部会触发pollDashScopeTaskStatus
                $statusResult = $genService->getRecordStatus($asset['record_id']);
                $taskStatus = $statusResult['data']['task_status'] ?? 1;

                if ($taskStatus == GenerationRecord::STATUS_SUCCESS) {
                    $images = $this->getGenerationOutputs($asset['record_id']);
                    $asset['images'] = $images;
                    $asset['status'] = 'completed';

                    // 构建角色身份卡
                    $this->consistencyService->buildIdCard($node->project_id, $asset, [
                        'aid'  => $node->aid,
                        'bid'  => $node->bid,
                        'mdid' => $node->mdid,
                        'uid'  => $node->uid,
                    ]);
                } elseif ($taskStatus == GenerationRecord::STATUS_FAILED) {
                    $asset['status'] = 'failed';
                    $asset['error'] = $statusResult['data']['error_msg'] ?? '生成失败';
                    $anyFailed = true;
                } else {
                    $allComplete = false;
                }
            }
        }

        // 容许部分成功：只要有至少一个角色有图片就继续执行
        $hasAnyImages = count(array_filter($characterAssets, fn($a) => !empty($a['images']))) > 0;

        if ($allComplete) {
            return [
                'completed' => true,
                'success'   => $hasAnyImages,
                'data'      => ['character_assets' => $characterAssets],
                'msg'       => $anyFailed ? '部分角色生成失败（继续执行）' : '角色形象生成完成',
            ];
        }

        return ['completed' => false];
    }

    /**
     * 自动查找可用的图像生成模型
     */
    protected function findAvailableImageModel()
    {
        // 优先选择有API Key配置的图像生成模型
        $model = \think\facade\Db::name('model_info')->alias('m')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('system_api_key ak', 'ak.provider_id = m.provider_id AND ak.is_active = 1')
            ->where('m.is_active', 1)
            ->where('t.type_code', 'image_generation')
            ->whereNotNull('ak.id')
            ->order('m.sort asc, m.id asc')
            ->value('m.id');
        return $model ?: 0;
    }

    /**
     * 构建角色形象生成prompt
     */
    protected function buildCharacterPrompt($name, $appearance, $style)
    {
        $styleDescMap = [
            'realistic' => 'photorealistic, high detail, cinematic lighting',
            'anime'     => 'anime style, vibrant colors, clean lines',
            'guofeng'   => 'Chinese ink painting style, traditional Chinese art',
            '3d'        => '3D rendered, Pixar style, high quality CG',
        ];

        $styleDesc = $styleDescMap[$style] ?? $styleDescMap['realistic'];

        return "Character portrait, {$appearance}, {$styleDesc}, portrait photo, 9:16 aspect ratio, upper body, face clearly visible, consistent character design";
    }

    /**
     * 获取生成任务的输出图片URL列表
     */
    protected function getGenerationOutputs($recordId)
    {
        if ($recordId <= 0) return [];

        $outputs = \think\facade\Db::name('generation_output')
            ->where('record_id', $recordId)
            ->column('output_url');

        return $outputs ?: [];
    }
}

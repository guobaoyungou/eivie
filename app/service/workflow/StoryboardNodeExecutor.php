<?php
/**
 * 分镜节点执行器
 * 通过图像生成模型为每个镜头生成关键帧画面，自动注入角色一致性策略
 */
namespace app\service\workflow;

use app\model\WorkflowNode;
use app\service\GenerationService;
use app\service\CharacterConsistencyService;
use app\model\GenerationRecord;
use think\facade\Db;
use think\facade\Log;

class StoryboardNodeExecutor implements NodeExecutorInterface
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
     * 执行分镜画面生成
     */
    public function execute(WorkflowNode $node, array $inputData): array
    {
        $config = $node->config_params;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        // 分镜列表来自上游剧本节点
        $scenes = $inputData['scenes'] ?? [];
        // 角色形象来自上游角色节点
        $characterAssets = $inputData['character_assets'] ?? [];
        $modelId = $node->model_id ?: ($config['model_id'] ?? 0);
        $resolution = $config['resolution'] ?? '720P';

        if (empty($scenes)) {
            return ['status' => 0, 'msg' => '无分镜数据，请先连接剧本节点'];
        }

        // 如果没有指定 model_id，自动选择可用的图像生成模型
        if ($modelId <= 0) {
            $modelId = $this->findAvailableImageModel();
        }

        $frames = [];
        $hasAsync = false;

        foreach ($scenes as $index => $scene) {
            $sceneDesc     = $scene['scene_desc'] ?? '';
            $characterTags = $scene['character_tags'] ?? [];
            $cameraHint    = $scene['camera_hint'] ?? '中景';

            // 避免并发限制：第2帧开始延迟
            if ($index > 0) {
                sleep(5);
            }

            // 注入角色一致性
            $enhanced = $this->consistencyService->injectConsistencyPrompt(
                $sceneDesc, $characterTags, $node->project_id
            );

            $prompt = $enhanced['prompt'] . ', ' . $cameraHint . ' shot, 9:16 vertical composition, cinematic';
            $negativePrompt = $enhanced['negative_prompt'];
            $referenceImages = $enhanced['reference_images'];

            try {
                $inputParams = [
                    'prompt'          => $prompt,
                    'negative_prompt' => $negativePrompt,
                    'aspect_ratio'    => '9:16',
                    'num_images'      => 1,
                ];

                if (!empty($referenceImages)) {
                    $inputParams['reference_image'] = $referenceImages[0];
                }

                $taskResult = $this->generationService->createTask([
                    'aid'             => $node->aid,
                    'bid'             => $node->bid,
                    'uid'             => $node->uid,
                    'generation_type' => GenerationRecord::TYPE_PHOTO,
                    'model_id'        => $modelId,
                    'input_params'    => $inputParams,
                ]);

                if (($taskResult['status'] ?? 0) == 1) {
                    $recordId = $taskResult['record_id'] ?? 0;
                    // 检查是否已完成
                    $record = Db::name('generation_record')->where('id', $recordId)->find();
                    $isComplete = ($record && $record['status'] == GenerationRecord::STATUS_SUCCESS);

                    if ($isComplete) {
                        $outputs = $this->getGenerationOutputs($recordId);
                        $imageUrl = $outputs[0] ?? '';

                        $frames[] = [
                            'scene_index'       => $scene['scene_index'] ?? $index,
                            'image_url'         => $imageUrl,
                            'prompt_used'       => $prompt,
                            'record_id'         => $recordId,
                            'status'            => 'completed',
                        ];
                    } else {
                        $hasAsync = true;
                        $frames[] = [
                            'scene_index' => $scene['scene_index'] ?? $index,
                            'image_url'   => '',
                            'prompt_used' => $prompt,
                            'record_id'   => $recordId,
                            'status'      => 'processing',
                        ];
                    }
                } else {
                    $frames[] = [
                        'scene_index' => $scene['scene_index'] ?? $index,
                        'image_url'   => '',
                        'error'       => $taskResult['msg'] ?? '生成失败',
                        'status'      => 'failed',
                    ];
                }
            } catch (\Exception $e) {
                Log::error("分镜 [{$index}] 生成异常: " . $e->getMessage());
                $frames[] = [
                    'scene_index' => $scene['scene_index'] ?? $index,
                    'image_url'   => '',
                    'error'       => $e->getMessage(),
                    'status'      => 'failed',
                ];
            }
        }

        if ($hasAsync) {
            return [
                'status'  => 2,
                'msg'     => '分镜画面生成任务已提交，等待处理',
                'task_id' => 'batch_storyboard_' . $node->id,
                'data'    => ['frames' => $frames],
            ];
        }

        $allSuccess = !array_filter($frames, fn($f) => ($f['status'] ?? '') === 'failed');
        return [
            'status' => $allSuccess ? 1 : 0,
            'msg'    => $allSuccess ? '分镜画面生成成功' : '部分分镜生成失败',
            'data'   => ['frames' => $frames],
        ];
    }

    /**
     * 轮询异步分镜生成状态
     */
    public function pollStatus(WorkflowNode $node)
    {
        $outputData = $node->output_data;
        if (is_string($outputData)) {
            $outputData = json_decode($outputData, true);
        }

        $frames = $outputData['frames'] ?? [];
        $allComplete = true;
        $anyFailed = false;

        // 触发异步任务轮询
        $genService = new GenerationService();

        foreach ($frames as &$frame) {
            if (($frame['status'] ?? '') === 'processing' && !empty($frame['record_id'])) {
                $statusResult = $genService->getRecordStatus($frame['record_id']);
                $taskStatus = $statusResult['data']['task_status'] ?? 1;

                if ($taskStatus == GenerationRecord::STATUS_SUCCESS) {
                    $outputs = $this->getGenerationOutputs($frame['record_id']);
                    $frame['image_url'] = $outputs[0] ?? '';
                    $frame['status'] = 'completed';
                } elseif ($taskStatus == GenerationRecord::STATUS_FAILED) {
                    $frame['status'] = 'failed';
                    $frame['error'] = $statusResult['data']['error_msg'] ?? '生成失败';
                    $anyFailed = true;
                } else {
                    $allComplete = false;
                }
            }
        }

        // 容许部分成功：只要有至少一帧有图片就继续执行
        $hasAnyFrames = count(array_filter($frames, fn($f) => !empty($f['image_url']))) > 0;

        if ($allComplete) {
            return [
                'completed' => true,
                'success'   => $hasAnyFrames,
                'data'      => ['frames' => $frames],
                'msg'       => $anyFailed ? '部分分镜生成失败（继续执行）' : '分镜画面生成完成',
            ];
        }

        return ['completed' => false];
    }

    /**
     * 自动查找可用的图像生成模型
     */
    protected function findAvailableImageModel()
    {
        $model = Db::name('model_info')->alias('m')
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
     * 获取生成任务的输出图片URL列表
     */
    protected function getGenerationOutputs($recordId)
    {
        if ($recordId <= 0) return [];
        return Db::name('generation_output')
            ->where('record_id', $recordId)
            ->column('output_url') ?: [];
    }
}

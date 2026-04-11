<?php
/**
 * 视频节点执行器
 * 通过 GenerationService + VideoModeResolver 将分镜关键帧驱动为视频片段
 */
namespace app\service\workflow;

use app\model\WorkflowNode;
use app\service\GenerationService;
use app\service\VideoModeResolver;
use app\model\GenerationRecord;
use think\facade\Db;
use think\facade\Log;

class VideoNodeExecutor implements NodeExecutorInterface
{
    /** @var GenerationService */
    protected $generationService;

    public function __construct()
    {
        $this->generationService = new GenerationService();
    }

    /**
     * 执行视频片段生成
     */
    public function execute(WorkflowNode $node, array $inputData): array
    {
        $config = $node->config_params;
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        // 关键帧图片来自上游分镜节点
        $frames    = $inputData['frames'] ?? [];
        $modelId   = $node->model_id ?: ($config['model_id'] ?? 0);
        $duration  = $config['duration'] ?? 5;
        $genMode   = $config['generation_mode'] ?? 'first_frame';  // first_frame / first_last_frame / motion

        if (empty($frames)) {
            return ['status' => 0, 'msg' => '无关键帧数据，请先连接分镜节点'];
        }

        // 如果没有指定 model_id，自动选择一个可用的视频生成模型
        if ($modelId <= 0) {
            $modelId = $this->findAvailableVideoModel();
        }

        // 过滤掉没有图片的帧（分镜生成失败的）
        $validFrames = array_filter($frames, fn($f) => !empty($f['image_url']));
        if (empty($validFrames)) {
            return ['status' => 0, 'msg' => '所有关键帧图片均为空，无法生成视频'];
        }

        $clips = [];
        $hasAsync = false;

        foreach ($frames as $index => $frame) {
            $imageUrl = $frame['image_url'] ?? '';
            if (empty($imageUrl)) {
                // 跳过没有图片的帧
                continue;
            }

            // 避免并发限制
            if ($index > 0) {
                sleep(5);
            }

            try {
                // 使用分镜的prompt或scene_desc作为视频提示词
                $prompt = $frame['prompt_used'] ?? $frame['scene_desc'] ?? 'Generate a cinematic video from this image';

                $inputParams = [
                    'prompt'       => $prompt,
                    'image'        => $imageUrl,
                    'aspect_ratio' => '9:16',
                    'duration'     => $duration,
                    'mode'         => $genMode,
                ];

                // 首尾帧模式：使用下一个分镜的关键帧作为尾帧
                if ($genMode === 'first_last_frame' && isset($frames[$index + 1])) {
                    $inputParams['last_frame_image'] = $frames[$index + 1]['image_url'] ?? '';
                }

                $taskResult = $this->generationService->createTask([
                    'aid'             => $node->aid,
                    'bid'             => $node->bid,
                    'uid'             => $node->uid,
                    'generation_type' => GenerationRecord::TYPE_VIDEO,
                    'model_id'        => $modelId,
                    'input_params'    => $inputParams,
                ]);

                if (($taskResult['status'] ?? 0) == 1) {
                    $recordId = $taskResult['record_id'] ?? 0;
                    $taskId   = $taskResult['task_id'] ?? '';

                    // 视频生成通常是异步的，检查是否已完成
                    $record = Db::name('generation_record')->where('id', $recordId)->find();
                    $isComplete = ($record && $record['status'] == GenerationRecord::STATUS_SUCCESS);

                    if ($isComplete) {
                        $outputs = Db::name('generation_output')
                            ->where('record_id', $recordId)
                            ->column('output_url');

                        $clips[] = [
                            'scene_index' => $frame['scene_index'] ?? $index,
                            'video_url'   => $outputs[0] ?? '',
                            'duration'    => $duration,
                            'resolution'  => '1080x1920',
                            'record_id'   => $recordId,
                            'task_id'     => $taskId,
                        ];
                    } else {
                        $hasAsync = true;
                        $clips[] = [
                            'scene_index' => $frame['scene_index'] ?? $index,
                            'video_url'   => '',
                            'duration'    => $duration,
                            'status'      => 'processing',
                            'record_id'   => $recordId,
                            'task_id'     => $taskId,
                        ];
                    }
                } else {
                    $clips[] = [
                        'scene_index' => $frame['scene_index'] ?? $index,
                        'video_url'   => '',
                        'error'       => $taskResult['msg'] ?? '视频生成任务创建失败',
                    ];
                }
            } catch (\Exception $e) {
                Log::error("视频片段 [{$index}] 生成异常: " . $e->getMessage());
                $clips[] = [
                    'scene_index' => $frame['scene_index'] ?? $index,
                    'video_url'   => '',
                    'error'       => $e->getMessage(),
                ];
            }
        }

        if ($hasAsync) {
            // 有异步任务，返回status=2表示需要轮询
            return [
                'status'  => 2,
                'msg'     => '视频生成任务已提交，等待处理',
                'task_id' => 'batch_video_' . $node->id,
                'data'    => ['clips' => $clips],
            ];
        }

        return [
            'status' => 1,
            'msg'    => '视频片段生成完成',
            'data'   => ['clips' => $clips],
        ];
    }

    /**
     * 轮询异步视频生成状态
     */
    public function pollStatus(WorkflowNode $node)
    {
        $outputData = $node->output_data;
        if (is_string($outputData)) {
            $outputData = json_decode($outputData, true);
        }

        $clips = $outputData['clips'] ?? [];
        $allComplete = true;
        $anyFailed = false;

        // 通过GenerationService触发DashScope/Seedance异步任务轮询
        $genService = new GenerationService();

        foreach ($clips as &$clip) {
            if (($clip['status'] ?? '') === 'processing' && !empty($clip['record_id'])) {
                // getRecordStatus内部会触发pollDashScopeTaskStatus/pollSeedanceTaskStatus
                $statusResult = $genService->getRecordStatus($clip['record_id']);
                $taskStatus = $statusResult['data']['task_status'] ?? 1;

                if ($taskStatus == GenerationRecord::STATUS_SUCCESS) {
                    $outputs = Db::name('generation_output')
                        ->where('record_id', $clip['record_id'])
                        ->column('output_url');
                    $clip['video_url'] = $outputs[0] ?? '';
                    $clip['status'] = 'completed';
                } elseif ($taskStatus == GenerationRecord::STATUS_FAILED) {
                    $clip['status'] = 'failed';
                    $clip['error'] = $statusResult['data']['error_msg'] ?? '生成失败';
                    $anyFailed = true;
                } else {
                    $allComplete = false;
                }
            }
        }

        if ($allComplete) {
            // 容许部分成功：只要有至少一个视频就继续执行
            $hasAnyVideo = count(array_filter($clips, fn($c) => !empty($c['video_url']))) > 0;
            return [
                'completed' => true,
                'success'   => $hasAnyVideo,
                'data'      => ['clips' => $clips],
                'msg'       => $anyFailed ? '部分视频片段生成失败（继续执行）' : '全部视频片段生成完成',
            ];
        }

        return ['completed' => false];
    }

    /**
     * 自动查找可用的视频生成模型
     */
    protected function findAvailableVideoModel()
    {
        $model = Db::name('model_info')->alias('m')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('system_api_key ak', 'ak.provider_id = m.provider_id AND ak.is_active = 1')
            ->where('m.is_active', 1)
            ->where('t.type_code', 'video_generation')
            ->whereNotNull('ak.id')
            ->order('m.sort asc, m.id asc')
            ->value('m.id');
        return $model ?: 0;
    }
}

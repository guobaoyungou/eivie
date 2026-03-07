<?php
declare(strict_types=1);

namespace app\job;

use app\service\AiTravelPhotoAiService;
use app\service\VolcengineVideoService;
use app\service\VideoModeResolver;
use app\service\SceneParameterService;
use app\service\GenerationResultService;
use think\queue\Job;
use think\facade\Db;

/**
 * 图生视频队列任务
 * 支持可灵AI和火山引擎方舟双平台
 */
class VideoGenerationJob
{
    /**
     * 执行任务
     * 
     * @param Job $job 任务对象
     * @param array $data 任务数据
     * @return void
     */
    public function fire(Job $job, $data)
    {
        $generationId = $data['generation_id'] ?? 0;
        
        if ($generationId <= 0) {
            $job->delete();
            return;
        }
        
        try {
            // 查询生成记录获取provider信息
            $generation = Db::name('ai_travel_photo_generation')
                ->where('id', $generationId)
                ->find();
            
            if (!$generation) {
                trace('视频生成任务不存在：' . $generationId, 'error');
                $job->delete();
                return;
            }
            
            // 根据provider选择不同的处理方式
            $provider = $data['provider'] ?? $generation['provider'] ?? 'default';
            
            switch ($provider) {
                case 'volcengine':
                    $result = $this->processVolcengineGeneration($generationId, $data);
                    break;
                    
                default:
                    // 默认使用AiTravelPhotoAiService处理
                    $aiService = new AiTravelPhotoAiService();
                    $result = $aiService->processGenerationBySceneType($generationId);
                    break;
            }
            
            if ($result) {
                trace('视频生成任务成功：' . $generationId, 'info');
                $job->delete();
            } else {
                throw new \Exception('处理失败');
            }
            
        } catch (\Exception $e) {
            trace('视频生成任务失败：' . $generationId . ', ' . $e->getMessage(), 'error');
            
            // 视频生成任务失败，只重试1次
            if ($job->attempts() > 1) {
                // 更新任务状态为失败
                $this->updateGenerationFailed($generationId, $e->getMessage());
                $job->delete();
            } else {
                // 延迟180秒后重试
                $job->release(180);
            }
        }
    }
    
    /**
     * 处理火山引擎方舟视频生成
     * 
     * @param int $generationId 生成记录ID
     * @param array $data 任务数据
     * @return bool
     */
    private function processVolcengineGeneration($generationId, $data)
    {
        // 获取生成记录详情
        $generation = Db::name('ai_travel_photo_generation')
            ->where('id', $generationId)
            ->find();
        
        if (!$generation) {
            throw new \Exception('生成记录不存在');
        }
        
        // 更新状态为处理中
        Db::name('ai_travel_photo_generation')
            ->where('id', $generationId)
            ->update([
                'status' => 1, // 处理中
                'start_time' => time()
            ]);
        
        $startTime = microtime(true);
        
        try {
            // 获取API配置
            $apiKey = $data['api_key'] ?? '';
            $bid = $generation['bid'] ?? 0;
            
            if (empty($apiKey)) {
                // 从scene关联的api_config获取
                $scene = Db::name('ai_travel_photo_scene')
                    ->where('id', $generation['scene_id'])
                    ->find();
                
                if ($scene && $scene['api_config_id']) {
                    $apiConfig = Db::name('api_config')
                        ->where('id', $scene['api_config_id'])
                        ->where('is_active', 1)
                        ->find();
                    
                    if ($apiConfig) {
                        $apiKey = $apiConfig['api_key'];
                    }
                }
            }
            
            // 创建火山引擎服务实例
            $volcengineService = new VolcengineVideoService($bid, $apiKey);
            
            // 获取外部任务ID（如果已经创建了任务）
            $externalTaskId = $generation['external_task_id'] ?? $data['task_id'] ?? '';
            
            if (empty($externalTaskId)) {
                // 需要创建新任务
                $params = json_decode($generation['model_params'] ?? '{}', true) ?: [];
                
                // 从数据库获取模型代码
                $modelCode = $data['model_code'] ?? $generation['model_code'] ?? 'doubao-seedance-1-5-pro';
                $baseModelCode = VolcengineVideoService::normalizeModelCode($modelCode);
                
                // 从扁平参数构建content数组
                $inputParams = $this->extractInputParamsForVolcengine($params, $data);
                
                // 使用VideoModeResolver推断视频生成模式
                $modeResolver = new VideoModeResolver();
                $modeResult = $modeResolver->resolve($baseModelCode, $inputParams);
                
                if (!$modeResult['valid']) {
                    throw new \Exception('视频模式推断失败: ' . $modeResult['message']);
                }
                
                $videoMode = $modeResult['mode'];
                
                // 使用SceneParameterService构建content数组
                $paramService = new SceneParameterService();
                $contentArray = $paramService->buildContentArray($videoMode, $inputParams);
                
                // 构建符合火山方舟API格式的请求参数
                $apiParams = [
                    'model' => $modelCode,
                    'content' => $contentArray
                ];
                
                // seed参数
                if (isset($params['seed'])) {
                    $apiParams['seed'] = intval($params['seed']);
                }
                
                trace('VideoGenerationJob: 构建content数组', [
                    'video_mode' => $videoMode,
                    'model_code' => $modelCode,
                    'content_count' => count($contentArray)
                ]);
                
                $createResult = $volcengineService->createVideoTask($apiParams);
                
                if (!$createResult['success']) {
                    throw new \Exception($createResult['message']);
                }
                
                $externalTaskId = $createResult['task_id'];
                
                // 更新外部任务ID
                Db::name('ai_travel_photo_generation')
                    ->where('id', $generationId)
                    ->update([
                        'external_task_id' => $externalTaskId,
                        'api_request' => json_encode($params)
                    ]);
            }
            
            // 轮询等待任务完成
            $result = $volcengineService->pollUntilComplete($externalTaskId);
            
            $costTime = round((microtime(true) - $startTime) * 1000);
            
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? '视频生成失败');
            }
            
            // 保存结果
            $resultService = new GenerationResultService();
            $saveResult = $resultService->saveVideoResult(
                $generationId,
                [
                    'output' => [
                        'video_url' => $result['video_url'] ?? '',
                        'audio_url' => $result['audio_url'] ?? '',
                        'duration' => $data['duration'] ?? 5
                    ]
                ],
                []
            );
            
            if (!$saveResult['success']) {
                throw new \Exception($saveResult['error'] ?? '保存结果失败');
            }
            
            // 更新生成记录为成功
            Db::name('ai_travel_photo_generation')
                ->where('id', $generationId)
                ->update([
                    'status' => 2, // 成功
                    'cost_time' => $costTime,
                    'finish_time' => time(),
                    'result_video_url' => $result['video_url'] ?? '',
                    'result_audio_url' => $result['audio_url'] ?? '',
                    'api_response' => json_encode($result['raw_response'] ?? [])
                ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->updateGenerationFailed($generationId, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 更新生成记录为失败状态
     * 
     * @param int $generationId 生成记录ID
     * @param string $errorMsg 错误信息
     */
    private function updateGenerationFailed($generationId, $errorMsg)
    {
        Db::name('ai_travel_photo_generation')
            ->where('id', $generationId)
            ->update([
                'status' => 3, // 失败
                'error_msg' => $errorMsg,
                'finish_time' => time()
            ]);
    }

    /**
     * 任务失败处理
     * 
     * @param array $data 任务数据
     * @return void
     */
    public function failed($data)
    {
        $generationId = $data['generation_id'] ?? 0;
        
        trace('图生视频任务最终失败：' . json_encode($data), 'error');
        
        if ($generationId > 0) {
            $this->updateGenerationFailed($generationId, '任务重试次数已用尽');
        }
    }
    
    /**
     * 从扁平参数中提取火山引擎视频生成所需的输入参数
     * 将各种参数名称映射到标准字段
     * 
     * @param array $params 原始扁平参数（来自model_params）
     * @param array $data 任务数据
     * @return array 标准化的输入参数
     */
    private function extractInputParamsForVolcengine(array $params, array $data = [])
    {
        $inputParams = [];
        
        // 提示词
        $inputParams['prompt'] = $params['prompt'] ?? $data['prompt'] ?? $params['text'] ?? $params['description'] ?? '';
        
        // 首帧图像（多种参数名兼容）
        $firstFrameImage = $params['first_frame_image'] 
            ?? $params['image_url'] 
            ?? $params['image'] 
            ?? $params['input_image']
            ?? $data['image_url']
            ?? $data['image']
            ?? '';
        if (!empty($firstFrameImage)) {
            $inputParams['first_frame_image'] = $firstFrameImage;
            $inputParams['image'] = $firstFrameImage; // 别名
        }
        
        // 尾帧图像
        $lastFrameImage = $params['last_frame_image'] 
            ?? $params['tail_image_url'] 
            ?? $params['last_frame'] 
            ?? $params['end_image'] 
            ?? '';
        if (!empty($lastFrameImage)) {
            $inputParams['last_frame_image'] = $lastFrameImage;
        }
        
        // 参考图数组
        if (!empty($params['reference_images']) && is_array($params['reference_images'])) {
            $inputParams['reference_images'] = $params['reference_images'];
        } elseif (!empty($params['ref_images']) && is_array($params['ref_images'])) {
            $inputParams['reference_images'] = $params['ref_images'];
        }
        
        // 时长
        if (!empty($params['duration'])) {
            $inputParams['duration'] = $params['duration'];
        } elseif (!empty($data['duration'])) {
            $inputParams['duration'] = $data['duration'];
        }
        
        // 分辨率
        if (!empty($params['resolution'])) {
            $inputParams['resolution'] = $params['resolution'];
        } elseif (!empty($data['resolution'])) {
            $inputParams['resolution'] = $data['resolution'];
        }
        
        // 水印
        if (isset($params['watermark'])) {
            $inputParams['watermark'] = $params['watermark'];
        }
        
        // 相机运动
        if (!empty($params['camera_motion'])) {
            $inputParams['camera_motion'] = $params['camera_motion'];
        }
        
        // 有声视频
        if (!empty($params['with_audio'])) {
            $inputParams['with_audio'] = $params['with_audio'];
        }
        
        return $inputParams;
    }
}

<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoGeneration;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoScene;
use app\service\SceneParameterService;
use app\service\GenerationResultService;
use app\service\VolcengineVideoService;
use app\service\VideoModeResolver;
use app\common\OssHelper;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;

/**
 * AI生成服务类
 * 处理智能抠图、图生图、图生视频等AI能力调用
 */
class AiTravelPhotoAiService
{
    protected $ossHelper;
    protected $config;

    public function __construct()
    {
        $this->ossHelper = new OssHelper();
        $this->config = config('ai_travel_photo');
    }

    /**
     * 智能抠图
     * 
     * @param int $portraitId 人像ID
     * @return array
     * @throws \Exception
     */
    public function cutoutPortrait(int $portraitId): array
    {
        $portrait = AiTravelPhotoPortrait::find($portraitId);
        
        if (!$portrait) {
            throw new \Exception('人像不存在');
        }
        
        if ($portrait->cutout_status == AiTravelPhotoPortrait::CUTOUT_STATUS_SUCCESS) {
            return [
                'status' => 'already_done',
                'cutout_url' => $portrait->cutout_url,
            ];
        }
        
        // 更新状态为处理中
        $portrait->cutout_status = AiTravelPhotoPortrait::CUTOUT_STATUS_PROCESSING;
        $portrait->save();
        
        try {
            $startTime = microtime(true);
            
            // 调用阿里云抠图API
            $cutoutResult = $this->callAliyunCutoutApi($portrait->original_url);
            
            $costTime = round((microtime(true) - $startTime) * 1000);
            
            // 上传抠图结果到OSS
            $cutoutUrl = $this->uploadCutoutResult($cutoutResult, $portrait->md5);
            
            // 更新人像记录
            $portrait->cutout_url = $cutoutUrl;
            $portrait->cutout_status = AiTravelPhotoPortrait::CUTOUT_STATUS_SUCCESS;
            $portrait->cutout_time = time();
            $portrait->save();
            
            // 记录成本
            $this->recordAiCost('cutout', $portraitId, $costTime, $this->config['aliyun_cutout']['cost_per_image']);
            
            return [
                'status' => 'success',
                'cutout_url' => $cutoutUrl,
                'cost_time' => $costTime,
            ];
            
        } catch (\Exception $e) {
            // 更新状态为失败
            $portrait->cutout_status = AiTravelPhotoPortrait::CUTOUT_STATUS_FAILED;
            $portrait->cutout_error = $e->getMessage();
            $portrait->save();
            
            throw new \Exception('抠图失败：' . $e->getMessage());
        }
    }

    /**
     * 图生图（AI生成照片）
     * 
     * @param int $portraitId 人像ID
     * @param int $sceneId 场景ID
     * @param array $params 生成参数
     * @return array
     * @throws \Exception
     */
    public function generateImage(int $portraitId, int $sceneId, array $params = []): array
    {
        // 验证人像
        $portrait = AiTravelPhotoPortrait::find($portraitId);
        if (!$portrait) {
            throw new \Exception('人像不存在');
        }
        
        if ($portrait->cutout_status != AiTravelPhotoPortrait::CUTOUT_STATUS_SUCCESS) {
            throw new \Exception('人像抠图尚未完成');
        }
        
        // 验证场景
        $scene = AiTravelPhotoScene::find($sceneId);
        if (!$scene || $scene->status != AiTravelPhotoScene::STATUS_NORMAL) {
            throw new \Exception('场景不存在或已下架');
        }
        
        Db::startTrans();
        try {
            // 创建生成记录
            $generation = AiTravelPhotoGeneration::create([
                'aid' => $portrait->aid,
                'bid' => $portrait->bid,
                'portrait_id' => $portraitId,
                'scene_id' => $sceneId,
                'generation_type' => AiTravelPhotoGeneration::TYPE_IMAGE,
                'prompt' => $this->buildPrompt($scene, $params),
                'parameters' => json_encode($params),
                'status' => AiTravelPhotoGeneration::STATUS_PENDING,
                'add_time' => time(),
            ]);
            
            Db::commit();
            
            // 推送到队列异步处理
            Queue::push('app\job\ImageGenerationJob', [
                'generation_id' => $generation->id,
            ], 'ai_image_generation');
            
            return [
                'generation_id' => $generation->id,
                'status' => 'pending',
                'message' => '任务已提交，正在处理中',
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 处理图生图任务（队列调用）
     * 
     * @param int $generationId 生成记录ID
     * @return bool
     */
    public function processImageGeneration(int $generationId): bool
    {
        $generation = AiTravelPhotoGeneration::find($generationId);
        
        if (!$generation) {
            return false;
        }
        
        // 更新状态为处理中
        $generation->status = AiTravelPhotoGeneration::STATUS_PROCESSING;
        $generation->process_time = time();
        $generation->save();
        
        try {
            $startTime = microtime(true);
            
            // 获取人像和场景
            $portrait = $generation->portrait;
            $scene = $generation->scene;
            
            // 调用阿里百炼通义万相API
            $taskId = $this->callTongyiImageApi(
                $portrait->cutout_url,
                $scene->reference_image,
                $generation->prompt,
                json_decode($generation->parameters, true) ?: []
            );
            
            // 轮询任务状态
            $result = $this->pollTaskStatus($taskId, 'image');
            
            $costTime = round((microtime(true) - $startTime) * 1000);
            
            // 上传结果到OSS
            $resultUrl = $this->downloadAndUploadResult($result['url'], $portrait->md5, $sceneId, 'image');
            
            // 创建结果记录
            $resultRecord = AiTravelPhotoResult::create([
                'aid' => $generation->aid,
                'bid' => $generation->bid,
                'portrait_id' => $portrait->id,
                'scene_id' => $sceneId,
                'generation_id' => $generationId,
                'content_type' => AiTravelPhotoResult::CONTENT_TYPE_IMAGE,
                'result_url' => $resultUrl,
                'file_size' => $result['file_size'] ?? 0,
                'add_time' => time(),
            ]);
            
            // 更新生成记录
            $generation->result_id = $resultRecord->id;
            $generation->status = AiTravelPhotoGeneration::STATUS_SUCCESS;
            $generation->cost_time = $costTime;
            $generation->finish_time = time();
            $generation->save();
            
            // 更新场景统计
            $scene->generation_count = $scene->generation_count + 1;
            $scene->save();
            
            // 记录成本
            $this->recordAiCost('image_generation', $generationId, $costTime, $this->config['aliyun_tongyi']['cost_per_image']);
            
            return true;
            
        } catch (\Exception $e) {
            // 更新状态为失败
            $generation->status = AiTravelPhotoGeneration::STATUS_FAILED;
            $generation->error_msg = $e->getMessage();
            $generation->finish_time = time();
            $generation->save();
            
            trace('图生图失败：' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 图生视频（AI生成视频）
     * 
     * @param int $resultId 结果图片ID
     * @param array $params 生成参数
     * @return array
     * @throws \Exception
     */
    public function generateVideo(int $resultId, array $params = []): array
    {
        // 验证结果图片
        $result = AiTravelPhotoResult::find($resultId);
        if (!$result || $result->content_type != AiTravelPhotoResult::CONTENT_TYPE_IMAGE) {
            throw new \Exception('结果图片不存在');
        }
        
        Db::startTrans();
        try {
            // 创建生成记录
            $generation = AiTravelPhotoGeneration::create([
                'aid' => $result->aid,
                'bid' => $result->bid,
                'portrait_id' => $result->portrait_id,
                'scene_id' => $result->scene_id,
                'generation_type' => AiTravelPhotoGeneration::TYPE_VIDEO,
                'prompt' => $params['camera_move'] ?? 'zoom_in',
                'parameters' => json_encode($params),
                'status' => AiTravelPhotoGeneration::STATUS_PENDING,
                'add_time' => time(),
            ]);
            
            Db::commit();
            
            // 推送到队列异步处理
            Queue::push('app\job\VideoGenerationJob', [
                'generation_id' => $generation->id,
                'result_id' => $resultId,
            ], 'ai_video_generation');
            
            return [
                'generation_id' => $generation->id,
                'status' => 'pending',
                'message' => '视频生成任务已提交，预计需要3-5分钟',
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 处理图生视频任务（队列调用）
     * 
     * @param int $generationId 生成记录ID
     * @param int $resultId 原图结果ID
     * @return bool
     */
    public function processVideoGeneration(int $generationId, int $resultId): bool
    {
        $generation = AiTravelPhotoGeneration::find($generationId);
        $sourceResult = AiTravelPhotoResult::find($resultId);
        
        if (!$generation || !$sourceResult) {
            return false;
        }
        
        // 更新状态为处理中
        $generation->status = AiTravelPhotoGeneration::STATUS_PROCESSING;
        $generation->process_time = time();
        $generation->save();
        
        try {
            $startTime = microtime(true);
            
            $params = json_decode($generation->parameters, true) ?: [];
            
            // 调用可灵AI视频生成API
            $taskId = $this->callKelingVideoApi(
                $sourceResult->result_url,
                $params['camera_move'] ?? 'zoom_in',
                $params
            );
            
            // 轮询任务状态（视频生成时间较长，最长等待5分钟）
            $result = $this->pollTaskStatus($taskId, 'video', 300);
            
            $costTime = round((microtime(true) - $startTime) * 1000);
            
            // 上传视频到OSS
            $videoUrl = $this->downloadAndUploadResult(
                $result['url'],
                $sourceResult->portrait->md5,
                $sourceResult->scene_id,
                'video'
            );
            
            // 创建视频结果记录
            $videoResult = AiTravelPhotoResult::create([
                'aid' => $generation->aid,
                'bid' => $generation->bid,
                'portrait_id' => $sourceResult->portrait_id,
                'scene_id' => $sourceResult->scene_id,
                'generation_id' => $generationId,
                'content_type' => AiTravelPhotoResult::CONTENT_TYPE_VIDEO,
                'result_url' => $videoUrl,
                'file_size' => $result['file_size'] ?? 0,
                'duration' => $result['duration'] ?? 5,
                'add_time' => time(),
            ]);
            
            // 更新生成记录
            $generation->result_id = $videoResult->id;
            $generation->status = AiTravelPhotoGeneration::STATUS_SUCCESS;
            $generation->cost_time = $costTime;
            $generation->finish_time = time();
            $generation->save();
            
            // 记录成本
            $this->recordAiCost('video_generation', $generationId, $costTime, $this->config['keling_ai']['cost_per_video']);
            
            return true;
            
        } catch (\Exception $e) {
            // 更新状态为失败
            $generation->status = AiTravelPhotoGeneration::STATUS_FAILED;
            $generation->error_msg = $e->getMessage();
            $generation->finish_time = time();
            $generation->save();
            
            trace('图生视频失败：' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 获取生成任务状态
     * 
     * @param int $generationId 生成记录ID
     * @return array
     * @throws \Exception
     */
    public function getGenerationStatus(int $generationId): array
    {
        $generation = AiTravelPhotoGeneration::with(['result', 'scene'])->find($generationId);
        
        if (!$generation) {
            throw new \Exception('生成记录不存在');
        }
        
        $data = [
            'generation_id' => $generation->id,
            'generation_type' => $generation->generation_type,
            'status' => $generation->status,
            'status_text' => $this->getStatusText($generation->status),
            'add_time' => $generation->add_time,
            'process_time' => $generation->process_time,
            'finish_time' => $generation->finish_time,
            'cost_time' => $generation->cost_time,
        ];
        
        if ($generation->status == AiTravelPhotoGeneration::STATUS_SUCCESS && $generation->result) {
            $data['result'] = [
                'result_id' => $generation->result->id,
                'content_type' => $generation->result->content_type,
                'result_url' => $generation->result->result_url,
                'thumbnail_url' => $generation->result->thumbnail_url,
            ];
        }
        
        if ($generation->status == AiTravelPhotoGeneration::STATUS_FAILED) {
            $data['error_msg'] = $generation->error_msg;
        }
        
        return $data;
    }

    /**
     * 调用阿里云抠图API
     * 
     * @param string $imageUrl 图片URL
     * @return array
     * @throws \Exception
     */
    private function callAliyunCutoutApi(string $imageUrl): array
    {
        $apiUrl = $this->config['aliyun_cutout']['api_url'];
        $apiKey = $this->config['aliyun_cutout']['api_key'];
        
        $postData = [
            'image_url' => $imageUrl,
            'output_type' => 'png',
        ];
        
        $response = $this->curlPost($apiUrl, $postData, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        
        if (!isset($response['output_image_url'])) {
            throw new \Exception('抠图API返回异常');
        }
        
        return [
            'url' => $response['output_image_url'],
            'request_id' => $response['request_id'] ?? '',
        ];
    }

    /**
     * 调用阿里百炼通义万相图生图API
     * 
     * @param string $cutoutUrl 抠图URL
     * @param string $referenceImage 参考图
     * @param string $prompt 提示词
     * @param array $params 参数
     * @return string 任务ID
     * @throws \Exception
     */
    private function callTongyiImageApi(string $cutoutUrl, string $referenceImage, string $prompt, array $params): string
    {
        $apiUrl = $this->config['aliyun_tongyi']['api_url'];
        $apiKey = $this->config['aliyun_tongyi']['api_key'];
        
        $postData = [
            'model' => $this->config['aliyun_tongyi']['model'],
            'input' => [
                'cutout_image' => $cutoutUrl,
                'reference_image' => $referenceImage,
                'prompt' => $prompt,
            ],
            'parameters' => array_merge([
                'n' => 1,
                'size' => '1024*1024',
            ], $params),
        ];
        
        $response = $this->curlPost($apiUrl, $postData, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'X-DashScope-Async: enable',
        ]);
        
        if (!isset($response['output']['task_id'])) {
            throw new \Exception('图生图API返回异常');
        }
        
        return $response['output']['task_id'];
    }

    /**
     * 调用可灵AI视频生成API
     * 
     * @param string $imageUrl 图片URL
     * @param string $cameraMove 镜头运动
     * @param array $params 参数
     * @return string 任务ID
     * @throws \Exception
     */
    private function callKelingVideoApi(string $imageUrl, string $cameraMove, array $params): string
    {
        $apiUrl = $this->config['keling_ai']['api_url'];
        $apiKey = $this->config['keling_ai']['api_key'];
        
        $postData = [
            'model' => $this->config['keling_ai']['model'],
            'image_url' => $imageUrl,
            'camera_move' => $cameraMove,
            'duration' => $params['duration'] ?? 5,
        ];
        
        $response = $this->curlPost($apiUrl, $postData, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        
        if (!isset($response['task_id'])) {
            throw new \Exception('图生视频API返回异常');
        }
        
        return $response['task_id'];
    }

    /**
     * 轮询任务状态
     * 
     * @param string $taskId 任务ID
     * @param string $type 类型（image/video）
     * @param int $maxWaitTime 最长等待时间（秒）
     * @return array
     * @throws \Exception
     */
    private function pollTaskStatus(string $taskId, string $type, int $maxWaitTime = 60): array
    {
        $startTime = time();
        $interval = $type == 'video' ? 10 : 3; // 视频10秒查询一次，图片3秒查询一次
        
        while (true) {
            $status = $this->queryTaskStatus($taskId, $type);
            
            if ($status['status'] == 'success') {
                return $status;
            }
            
            if ($status['status'] == 'failed') {
                throw new \Exception($status['error'] ?? '任务失败');
            }
            
            if (time() - $startTime > $maxWaitTime) {
                throw new \Exception('任务超时');
            }
            
            sleep($interval);
        }
    }

    /**
     * 查询任务状态
     * 
     * @param string $taskId 任务ID
     * @param string $type 类型
     * @return array
     */
    private function queryTaskStatus(string $taskId, string $type): array
    {
        // 根据类型选择不同的查询接口
        $config = $type == 'video' ? $this->config['keling_ai'] : $this->config['aliyun_tongyi'];
        
        $queryUrl = $config['query_url'] . '?task_id=' . $taskId;
        
        $response = $this->curlGet($queryUrl, [
            'Authorization: Bearer ' . $config['api_key'],
        ]);
        
        return [
            'status' => $response['status'] ?? 'processing',
            'url' => $response['output']['url'] ?? '',
            'error' => $response['error'] ?? '',
            'file_size' => $response['file_size'] ?? 0,
            'duration' => $response['duration'] ?? 0,
        ];
    }

    /**
     * 下载并上传结果文件
     * 
     * @param string $url 源URL
     * @param string $md5 人像MD5
     * @param int $sceneId 场景ID
     * @param string $type 类型
     * @return string OSS URL
     */
    private function downloadAndUploadResult(string $url, string $md5, int $sceneId, string $type): string
    {
        $tmpFile = runtime_path() . 'temp/' . uniqid() . ($type == 'video' ? '.mp4' : '.jpg');
        
        // 下载文件
        $content = file_get_contents($url);
        file_put_contents($tmpFile, $content);
        
        // 上传到OSS
        $ext = $type == 'video' ? 'mp4' : 'jpg';
        $ossPath = $this->config['oss']['ai_travel_photo_path'] . "results/{$md5}_{$sceneId}_" . time() . ".{$ext}";
        
        $ossUrl = $this->ossHelper->uploadFile($tmpFile, $ossPath);
        
        // 删除临时文件
        @unlink($tmpFile);
        
        return $ossUrl;
    }

    /**
     * 上传抠图结果
     * 
     * @param array $cutoutResult 抠图结果
     * @param string $md5 人像MD5
     * @return string
     */
    private function uploadCutoutResult(array $cutoutResult, string $md5): string
    {
        $tmpFile = runtime_path() . 'temp/' . uniqid() . '.png';
        
        // 下载抠图结果
        $content = file_get_contents($cutoutResult['url']);
        file_put_contents($tmpFile, $content);
        
        // 上传到OSS
        $ossPath = $this->config['oss']['ai_travel_photo_path'] . "cutout/{$md5}_" . time() . ".png";
        $ossUrl = $this->ossHelper->uploadFile($tmpFile, $ossPath);
        
        @unlink($tmpFile);
        
        return $ossUrl;
    }

    /**
     * 构建提示词
     * 
     * @param AiTravelPhotoScene $scene 场景
     * @param array $params 参数
     * @return string
     */
    private function buildPrompt($scene, array $params): string
    {
        $prompt = $scene->prompt_template;
        
        // 支持变量替换
        if (!empty($params['custom_params'])) {
            foreach ($params['custom_params'] as $key => $value) {
                $prompt = str_replace('{' . $key . '}', $value, $prompt);
            }
        }
        
        return $prompt;
    }

    /**
     * 记录AI成本
     * 
     * @param string $type 类型
     * @param int $relatedId 关联ID
     * @param int $costTime 耗时
     * @param float $cost 成本
     * @return void
     */
    private function recordAiCost(string $type, int $relatedId, int $costTime, float $cost): void
    {
        // 可以记录到统计表或日志
        trace("AI成本：{$type}, ID:{$relatedId}, 耗时:{$costTime}ms, 成本:{$cost}元", 'info');
    }

    /**
     * 获取状态文本
     * 
     * @param int $status 状态
     * @return string
     */
    private function getStatusText(int $status): string
    {
        $map = [
            AiTravelPhotoGeneration::STATUS_PENDING => '排队中',
            AiTravelPhotoGeneration::STATUS_PROCESSING => '处理中',
            AiTravelPhotoGeneration::STATUS_SUCCESS => '成功',
            AiTravelPhotoGeneration::STATUS_FAILED => '失败',
        ];
        
        return $map[$status] ?? '未知';
    }

    /**
     * CURL POST请求
     */
    private function curlPost(string $url, array $data, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?: [];
    }

    /**
     * CURL GET请求
     */
    private function curlGet(string $url, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?: [];
    }

    /**
     * 基于场景类型处理生成任务（增强版）
     * 
     * @param int $generationId 生成记录ID
     * @return bool
     */
    public function processGenerationBySceneType(int $generationId): bool
    {
        $generation = AiTravelPhotoGeneration::with(['portrait', 'scene'])->find($generationId);
        
        if (!$generation) {
            return false;
        }
        
        // 更新状态为处理中
        $generation->status = AiTravelPhotoGeneration::STATUS_PROCESSING;
        $generation->start_time = time();
        $generation->save();
        
        try {
            $startTime = microtime(true);
            
            $portrait = $generation->portrait;
            $scene = $generation->scene;
            
            if (!$portrait || !$scene) {
                throw new \Exception('人像或场景不存在');
            }
            
            // 使用参数组装服务
            $paramService = new SceneParameterService();
            $resultService = new GenerationResultService();
            
            // 根据scene_type选择不同的处理逻辑
            if ($scene->scene_type >= 3 && $scene->scene_type <= 6) {
                // 视频生成场景（3-6）
                $params = $paramService->assembleVideoGenerationParams(
                    $scene->toArray(),
                    $portrait->toArray()
                );
                
                // 调用视频生成API（可灵AI等）
                $apiResponse = $this->callVideoGenerationApi($params, $scene);
                
                // 保存视频结果
                $saveResult = $resultService->saveVideoResult(
                    $generationId,
                    $apiResponse,
                    $scene->toArray()
                );
                
            } else {
                // 图生图场景（1-2）
                $params = $paramService->assembleImageGenerationParams(
                    $scene->toArray(),
                    $portrait->toArray()
                );
                
                // 调用图生图API（通义千问等）
                $apiResponse = $this->callImageGenerationApi($params, $scene);
                
                // 根据返回结果判断是单图还是多图
                $saveResult = $resultService->saveResultAuto(
                    $generationId,
                    $apiResponse
                );
            }
            
            $costTime = round((microtime(true) - $startTime) * 1000);
            
            if ($saveResult['success']) {
                // 更新生成记录为成功
                $resultService->updateGenerationStatus($generationId, 2, [
                    'cost_time' => $costTime,
                    'finish_time' => time()
                ]);
                
                // 更新场景统计
                $scene->use_count = $scene->use_count + 1;
                $scene->success_count = $scene->success_count + 1;
                $scene->save();
                
                return true;
            } else {
                throw new \Exception($saveResult['error'] ?? '保存结果失败');
            }
            
        } catch (\Exception $e) {
            // 更新状态为失败
            $generation->status = AiTravelPhotoGeneration::STATUS_FAILED;
            $generation->error_msg = $e->getMessage();
            $generation->finish_time = time();
            $generation->save();
            
            // 更新场景统计
            if (isset($scene)) {
                $scene->use_count = $scene->use_count + 1;
                $scene->fail_count = $scene->fail_count + 1;
                $scene->save();
            }
            
            trace('生成任务失败：' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 调用图生图API（通用方法）
     * 
     * @param array $params 参数
     * @param object $scene 场景对象
     * @return array
     */
    private function callImageGenerationApi(array $params, $scene): array
    {
        // 1. 查询API配置
        $apiConfig = \app\model\ApiConfig::where('id', $scene->api_config_id)
            ->where('is_active', 1)
            ->find();
        
        if (!$apiConfig) {
            throw new \Exception('API配置不存在或未启用');
        }
        
        // 2. 根据provider调用对应API
        switch ($apiConfig->provider) {
            case 'aliyun':
                return $this->callAliyunImageGenerationApi($params, $apiConfig, $scene);
            
            case 'baidu':
                return $this->callBaiduImageGenerationApi($params, $apiConfig, $scene);
            
            case 'openai':
                return $this->callOpenAiImageGenerationApi($params, $apiConfig, $scene);
            
            default:
                throw new \Exception('不支持的服务提供商: ' . $apiConfig->provider);
        }
    }
    
    /**
     * 调用视频生成API（通用方法）
     * 
     * @param array $params 参数
     * @param object $scene 场景对象
     * @return array
     */
    private function callVideoGenerationApi(array $params, $scene): array
    {
        // 1. 查询API配置
        $apiConfig = \app\model\ApiConfig::where('id', $scene->api_config_id)
            ->where('is_active', 1)
            ->find();
        
        if (!$apiConfig) {
            throw new \Exception('API配置不存在或未启用');
        }
        
        // 2. 根据provider调用对应API
        switch ($apiConfig->provider) {
            case 'volcengine':
                return $this->callVolcengineVideoGenerationApi($params, $apiConfig, $scene);
            
            case 'kling':
            case 'keling':
                return $this->callKlingVideoGenerationApi($params, $apiConfig, $scene);
            
            case 'aliyun':
                return $this->callAliyunVideoGenerationApi($params, $apiConfig, $scene);
            
            default:
                throw new \Exception('不支持的视频生成服务提供商: ' . $apiConfig->provider);
        }
    }
    
    /**
     * 调用阿里云通义万相图生图API
     * 
     * @param array $params 参数
     * @param object $apiConfig API配置
     * @param object $scene 场景
     * @return array
     */
    private function callAliyunImageGenerationApi(array $params, $apiConfig, $scene): array
    {
        $apiUrl = $apiConfig->endpoint_url;
        $apiKey = $apiConfig->api_key;
        
        // 解析配置JSON
        $configJson = $apiConfig->config_json ? json_decode($apiConfig->config_json, true) : [];
        
        // 构建请求数据
        $postData = [
            'model' => $configJson['model'] ?? 'wanx-v1',
            'input' => $params['input'],
            'parameters' => $params['parameters']
        ];
        
        // 添加异步调用标识
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'X-DashScope-Async: enable'
        ];
        
        try {
            $response = $this->curlPost($apiUrl, $postData, $headers);
            
            // 检查响应
            if (isset($response['output']['task_id'])) {
                // 异步任务，返回task_id
                return [
                    'task_id' => $response['output']['task_id'],
                    'request_id' => $response['request_id'] ?? '',
                    'is_async' => true
                ];
            } elseif (isset($response['output']['results'])) {
                // 同步返回结果
                return [
                    'output' => $response['output'],
                    'request_id' => $response['request_id'] ?? '',
                    'is_async' => false
                ];
            } else {
                throw new \Exception('阿里云API返回格式异常: ' . json_encode($response));
            }
        } catch (\Exception $e) {
            Log::error('阿里云图生图API调用失败', [
                'scene_id' => $scene->id,
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * 调用百度图生图API
     * 
     * @param array $params 参数
     * @param object $apiConfig API配置
     * @param object $scene 场景
     * @return array
     */
    private function callBaiduImageGenerationApi(array $params, $apiConfig, $scene): array
    {
        // TODO: 实现百度文心一言图生图API调用
        throw new \Exception('百度图生图API尚未实现');
    }
    
    /**
     * 调用OpenAI图生图API
     * 
     * @param array $params 参数
     * @param object $apiConfig API配置
     * @param object $scene 场景
     * @return array
     */
    private function callOpenAiImageGenerationApi(array $params, $apiConfig, $scene): array
    {
        $apiUrl = $apiConfig->endpoint_url;
        $apiKey = $apiConfig->api_key;
        
        // OpenAI的请求格式
        $postData = [
            'prompt' => $params['input']['prompt'] ?? '',
            'n' => $params['parameters']['n'] ?? 1,
            'size' => $params['parameters']['size'] ?? '1024x1024'
        ];
        
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];
        
        try {
            $response = $this->curlPost($apiUrl, $postData, $headers);
            
            // 转换为统一格式
            if (isset($response['data'])) {
                return [
                    'output' => [
                        'results' => array_map(function($item) {
                            return ['url' => $item['url']];
                        }, $response['data'])
                    ],
                    'is_async' => false
                ];
            } else {
                throw new \Exception('OpenAI API返回格式异常');
            }
        } catch (\Exception $e) {
            Log::error('OpenAI图生图API调用失败', [
                'scene_id' => $scene->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * 调用可灵AI视频生成API
     * 
     * @param array $params 参数
     * @param object $apiConfig API配置
     * @param object $scene 场景
     * @return array
     */
    private function callKlingVideoGenerationApi(array $params, $apiConfig, $scene): array
    {
        // 使用KlingAIService
        $klingService = new KlingAIService();
        
        // 构建可灵AI请求参数
        $klingParams = [
            'model_name' => $apiConfig->config_json['model_name'] ?? 'kling-v1',
            'image' => $params['input']['image_url'],
            'prompt' => $params['input']['prompt'] ?? '',
            'negative_prompt' => $params['input']['negative_prompt'] ?? '',
            'mode' => $params['parameters']['mode'] ?? 'std',
            'duration' => $params['parameters']['duration'] ?? 5
        ];
        
        // 根据scene_type选择不同的API
        switch ($scene->scene_type) {
            case 3: // 视频生成-首帧
            case 4: // 视频生成-首尾帧
            case 6: // 视频生成-参考生成
                $result = $klingService->image2video($klingParams);
                break;
                
            case 5: // 视频生成-特效
                $result = $klingService->effects([
                    'effect_scene' => $params['parameters']['effect_scene'] ?? 'default',
                    'input' => $params['input']
                ]);
                break;
                
            default:
                throw new \Exception('不支持的视频生成场景类型: ' . $scene->scene_type);
        }
        
        if (!$result['success']) {
            throw new \Exception('可灵AI调用失败: ' . $result['message']);
        }
        
        // 返回统一格式
        return [
            'task_id' => $result['data']['task_id'] ?? $result['data']['id'] ?? '',
            'is_async' => true
        ];
    }
    
    /**
     * 调用阿里云视频生成API
     * 
     * @param array $params 参数
     * @param object $apiConfig API配置
     * @param object $scene 场景
     * @return array
     */
    private function callAliyunVideoGenerationApi(array $params, $apiConfig, $scene): array
    {
        // TODO: 实现阿里云视频生成API（如有需要）
        throw new \Exception('阿里云视频生成API尚未实现');
    }
    
    /**
     * 调用火山引擎方舟视频生成API（Seedance系列模型）
     * 
     * 支持的生成模式：
     * - text_to_video: 文生视频
     * - first_frame: 首帧图生视频
     * - first_last_frame: 首尾帧图生视频
     * - reference_images: 参考图生视频（仅1.0 Lite i2v）
     * 
     * @param array $params 参数
     * @param object $apiConfig API配置
     * @param object $scene 场景
     * @return array
     */
    private function callVolcengineVideoGenerationApi(array $params, $apiConfig, $scene): array
    {
        // 解析配置JSON
        $configJson = $apiConfig->config_json ? json_decode($apiConfig->config_json, true) : [];
        
        // 获取完整模型标识（含日期后缀，用于API调用）
        $apiModelCode = $configJson['model_code'] ?? $configJson['model'] ?? 'doubao-seedance-1-5-pro';
        
        // 归一化为基础model_code（用于能力查询）
        $baseModelCode = VolcengineVideoService::normalizeModelCode($apiModelCode);
        
        // 使用VideoModeResolver推断视频生成模式（使用基础code检查能力）
        $modeResolver = new VideoModeResolver();
        
        // 各入口参数格式可能不同，统一提取输入参数
        $inputParams = $params['input'] ?? [];
        
        // 如果是扁平参数（没有input嵌套），则直接使用params
        if (empty($inputParams) && !empty($params['prompt'])) {
            $inputParams = $params;
        }
        
        // 将parameters中的duration/resolution等合并到inputParams（用于嵌入text标志）
        $parameters = $params['parameters'] ?? [];
        if (!empty($parameters['duration']) && empty($inputParams['duration'])) {
            $inputParams['duration'] = $parameters['duration'];
        }
        if (!empty($parameters['resolution']) && empty($inputParams['resolution'])) {
            $inputParams['resolution'] = $parameters['resolution'];
        }
        if (isset($parameters['watermark']) && !isset($inputParams['watermark'])) {
            $inputParams['watermark'] = $parameters['watermark'];
        }
        
        $modeResult = $modeResolver->resolve($baseModelCode, $inputParams);
        
        if (!$modeResult['valid']) {
            throw new \Exception($modeResult['message']);
        }
        
        $videoMode = $modeResult['mode'];
        
        // 使用SceneParameterService构建content数组
        $paramService = new SceneParameterService();
        $contentArray = $paramService->buildContentArray($videoMode, $inputParams);
        
        // 构建API请求参数（仅model和content，其他参数已嵌入text标志）
        $volcengineParams = [
            'model' => $apiModelCode,
            'content' => $contentArray
        ];
        
        // seed参数作为独立字段传递
        if (isset($parameters['seed'])) {
            $volcengineParams['seed'] = intval($parameters['seed']);
        }
        
        // 使用VolcengineVideoService调用API
        $volcengineService = new VolcengineVideoService($scene->bid ?? 0, $apiConfig->api_key);
        
        $result = $volcengineService->createVideoTask($volcengineParams);
        
        if (!$result['success']) {
            throw new \Exception('火山引擎视频生成API调用失败: ' . $result['message']);
        }
        
        // 返回统一格式
        return [
            'task_id' => $result['task_id'],
            'is_async' => true,
            'provider' => 'volcengine',
            'model_code' => $apiModelCode,
            'video_mode' => $videoMode,
            'raw_response' => $result['raw_response'] ?? []
        ];
    }
}

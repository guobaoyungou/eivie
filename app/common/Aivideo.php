<?php
/**
 * AI旅拍公共类
 * 提供AI旅拍功能的公共方法
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\common;

use think\facade\Db;
use think\facade\Log;
use app\service\KlingAIService;
use app\service\VolcengineVideoService;
use app\service\VideoModeResolver;
use app\service\SceneParameterService;

class Aivideo
{
    /**
     * 创建AI生成任务
     * @param int $aid 应用ID
     * @param int $bid 商家ID
     * @param int $mid 会员ID
     * @param array $params 任务参数
     * @return array
     */
    public static function createTask($aid, $bid, $mid, $params)
    {
        Db::startTrans();
        try {
            // 创建任务记录
            $taskData = [
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'task_type' => $params['task_type'],
                'task_name' => $params['task_name'] ?? '',
                'material_id' => $params['material_id'] ?? 0,
                'material_url' => $params['material_url'] ?? '',
                'prompt' => $params['prompt'] ?? '',
                'negative_prompt' => $params['negative_prompt'] ?? '',
                'model_name' => $params['model_name'] ?? '',
                'mode' => $params['mode'] ?? '',
                'aspect_ratio' => $params['aspect_ratio'] ?? '',
                'duration' => $params['duration'] ?? '',
                'effect_scene' => $params['effect_scene'] ?? '',
                'external_task_id' => $params['external_task_id'] ?? '',
                'task_status' => 'pending',
                'request_data' => json_encode($params),
                'createtime' => time(),
                'updatetime' => time(),
            ];

            $taskId = Db::name('aivideo_task')->insertGetId($taskData);

            // 加入队列
            $queueData = [
                'task_id' => $taskId,
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'params' => $params,
            ];

            $redis = \think\facade\Cache::store('redis')->handler();
            $redis->lpush(config('aivideo.queue.prefix') . 'task', json_encode($queueData));

            Db::commit();

            return ['success' => true, 'task_id' => $taskId];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建AI任务失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '创建任务失败'];
        }
    }

    /**
     * 处理AI任务
     * @param array $taskData 任务数据
     * @return array
     */
    public static function processTask($taskData)
    {
        $taskId = $taskData['task_id'];
        $aid = $taskData['aid'];
        $bid = $taskData['bid'];
        $params = $taskData['params'];

        // 更新任务状态为已提交
        Db::name('aivideo_task')->where('id', $taskId)->update([
            'task_status' => 'submitted',
            'updatetime' => time(),
        ]);

        // 根据provider选择不同的服务
        $provider = $params['provider'] ?? 'kling';
        
        switch ($provider) {
            case 'volcengine':
                return self::processVolcengineTask($taskId, $bid, $params);
            
            case 'kling':
            default:
                return self::processKlingTask($taskId, $bid, $params);
        }
    }
    
    /**
     * 处理可灵AI任务
     * @param int $taskId 任务ID
     * @param int $bid 商家ID
     * @param array $params 参数
     * @return array
     */
    private static function processKlingTask($taskId, $bid, $params)
    {
        // 调用可灵AI接口
        $klingService = new KlingAIService($bid);
        $result = [];

        switch ($params['task_type']) {
            case 'image2video':
                $result = $klingService->image2video($params);
                break;
            case 'text2video':
                $result = $klingService->text2video($params);
                break;
            case 'effects':
                $result = $klingService->effects($params);
                break;
            default:
                return ['success' => false, 'message' => '不支持的任务类型'];
        }

        if (!$result['success']) {
            // 更新任务状态为失败
            Db::name('aivideo_task')->where('id', $taskId)->update([
                'task_status' => 'failed',
                'task_status_msg' => $result['message'],
                'response_data' => json_encode($result),
                'updatetime' => time(),
            ]);
            return ['success' => false, 'message' => $result['message']];
        }

        // 更新任务状态为处理中
        Db::name('aivideo_task')->where('id', $taskId)->update([
            'task_status' => 'processing',
            'kling_task_id' => $result['data']['data']['task_id'] ?? '',
            'response_data' => json_encode($result['data']),
            'updatetime' => time(),
        ]);

        return ['success' => true];
    }
    
    /**
     * 处理火山引擎方舟任务
     * @param int $taskId 任务ID
     * @param int $bid 商家ID
     * @param array $params 参数（支持扁平参数或预构建content）
     * @return array
     */
    private static function processVolcengineTask($taskId, $bid, $params)
    {
        try {
            // 获取API Key
            $apiKey = $params['api_key'] ?? '';
            if (empty($apiKey)) {
                // 从配置获取
                $apiConfig = Db::name('api_config')
                    ->where('bid', $bid)
                    ->where('provider', 'volcengine')
                    ->where('is_active', 1)
                    ->find();
                
                if ($apiConfig) {
                    $apiKey = $apiConfig['api_key'];
                }
            }
            
            $volcengineService = new VolcengineVideoService($bid, $apiKey);
            
            // 获取模型代码（API调用使用完整标识）
            $apiModelCode = $params['model_code'] ?? $params['model'] ?? 'doubao-seedance-1-5-pro';
            // 归一化为基础model_code（用于能力查询）
            $baseModelCode = VolcengineVideoService::normalizeModelCode($apiModelCode);
            
            // 构建content数组（如果未预构建则从扁平参数构建）
            $contentArray = $params['content'] ?? [];
            if (empty($contentArray)) {
                // 从扁平参数构建输入参数
                $inputParams = self::extractInputParamsForVolcengine($params);
                
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
                
                Log::info('processVolcengineTask: 从扁平参数构建content', [
                    'video_mode' => $videoMode,
                    'input_params_keys' => array_keys($inputParams),
                    'content_count' => count($contentArray)
                ]);
            }
            
            // 构建请求参数
            $requestParams = [
                'model' => $apiModelCode,
                'content' => $contentArray
            ];
            
            // 注意：duration/resolution等参数已通过buildContentArray嵌入text的命令行标志
            // 以下仅作为备用，实际API可能忽略这些独立字段
            if (!empty($params['with_audio'])) {
                $requestParams['with_audio'] = true;
            }
            if (isset($params['seed'])) {
                $requestParams['seed'] = intval($params['seed']);
            }
            
            // 创建视频生成任务
            $result = $volcengineService->createVideoTask($requestParams);
            
            if (!$result['success']) {
                Db::name('aivideo_task')->where('id', $taskId)->update([
                    'task_status' => 'failed',
                    'task_status_msg' => $result['message'],
                    'response_data' => json_encode($result),
                    'updatetime' => time(),
                ]);
                return ['success' => false, 'message' => $result['message']];
            }
            
            // 更新任务状态为处理中
            Db::name('aivideo_task')->where('id', $taskId)->update([
                'task_status' => 'processing',
                'provider' => 'volcengine',
                'external_task_id' => $result['task_id'],
                'response_data' => json_encode($result),
                'updatetime' => time(),
            ]);
            
            return ['success' => true, 'task_id' => $result['task_id']];
            
        } catch (\Exception $e) {
            Log::error('火山引擎视频任务处理失败: ' . $e->getMessage());
            
            Db::name('aivideo_task')->where('id', $taskId)->update([
                'task_status' => 'failed',
                'task_status_msg' => $e->getMessage(),
                'updatetime' => time(),
            ]);
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 从扁平参数中提取火山引擎视频生成所需的输入参数
     * 将各种参数名称映射到标准字段
     * 
     * @param array $params 原始扁平参数
     * @return array 标准化的输入参数
     */
    private static function extractInputParamsForVolcengine(array $params)
    {
        $inputParams = [];
        
        // 提示词
        $inputParams['prompt'] = $params['prompt'] ?? $params['text'] ?? $params['description'] ?? '';
        
        // 首帧图像（多种参数名兼容）
        $firstFrameImage = $params['first_frame_image'] 
            ?? $params['image_url'] 
            ?? $params['image'] 
            ?? $params['input_image'] 
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
        }
        
        // 分辨率
        if (!empty($params['resolution'])) {
            $inputParams['resolution'] = $params['resolution'];
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

    /**
     * 查询任务状态并处理结果
     * @param int $taskId 任务ID
     * @return array
     */
    public static function checkTaskStatus($taskId)
    {
        $task = Db::name('aivideo_task')->where('id', $taskId)->find();
        if (!$task) {
            return ['success' => false, 'message' => '任务不存在'];
        }

        if ($task['task_status'] != 'processing') {
            return ['success' => false, 'message' => '任务状态不正确'];
        }

        // 根据provider选择不同的查询方式
        $provider = $task['provider'] ?? 'kling';
        
        switch ($provider) {
            case 'volcengine':
                return self::checkVolcengineTaskStatus($task);
            
            case 'kling':
            default:
                return self::checkKlingTaskStatus($task);
        }
    }
    
    /**
     * 查询可灵AI任务状态
     * @param array $task 任务数据
     * @return array
     */
    private static function checkKlingTaskStatus($task)
    {
        $klingService = new KlingAIService($task['bid']);
        $result = $klingService->queryTask($task['kling_task_id']);

        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        $data = $result['data']['data'];
        $taskStatus = $data['task_status'] ?? '';

        if ($taskStatus == 'succeed') {
            // 任务成功,创建作品记录
            return self::handleTaskSuccess($task, $data);
        } elseif ($taskStatus == 'failed') {
            // 任务失败
            Db::name('aivideo_task')->where('id', $task['id'])->update([
                'task_status' => 'failed',
                'task_status_msg' => $data['task_status_msg'] ?? '生成失败',
                'response_data' => json_encode($data),
                'updatetime' => time(),
            ]);
            return ['success' => false, 'message' => '任务失败'];
        }

        // 任务仍在处理中
        return ['success' => false, 'message' => '任务处理中'];
    }
    
    /**
     * 查询火山引擎任务状态
     * @param array $task 任务数据
     * @return array
     */
    private static function checkVolcengineTaskStatus($task)
    {
        try {
            // 获取API Key
            $apiKey = '';
            $apiConfig = Db::name('api_config')
                ->where('bid', $task['bid'])
                ->where('provider', 'volcengine')
                ->where('is_active', 1)
                ->find();
            
            if ($apiConfig) {
                $apiKey = $apiConfig['api_key'];
            }
            
            $volcengineService = new VolcengineVideoService($task['bid'], $apiKey);
            
            // 获取外部任务ID
            $externalTaskId = $task['external_task_id'] ?? '';
            
            if (empty($externalTaskId)) {
                return ['success' => false, 'message' => '外部任务ID不存在'];
            }
            
            // 查询任务状态
            $result = $volcengineService->queryTaskStatus($externalTaskId);
            
            if (!$result['success']) {
                return ['success' => false, 'message' => $result['message']];
            }
            
            $status = $result['status'];
            
            if ($status === 'succeeded') {
                // 任务成功
                $volcengineData = [
                    'task_result' => [
                        'videos' => [
                            [
                                'url' => $result['video_url'] ?? '',
                                'id' => $externalTaskId,
                                'duration' => $task['duration'] ?? 5
                            ]
                        ]
                    ]
                ];
                
                // 如果有音频URL，添加到结果中
                if (!empty($result['audio_url'])) {
                    $volcengineData['task_result']['audio_url'] = $result['audio_url'];
                }
                
                return self::handleTaskSuccess($task, $volcengineData);
                
            } elseif ($status === 'failed') {
                // 任务失败
                Db::name('aivideo_task')->where('id', $task['id'])->update([
                    'task_status' => 'failed',
                    'task_status_msg' => $result['error_message'] ?? '视频生成失败',
                    'response_data' => json_encode($result),
                    'updatetime' => time(),
                ]);
                return ['success' => false, 'message' => '任务失败'];
                
            } elseif ($status === 'cancelled') {
                // 任务被取消
                Db::name('aivideo_task')->where('id', $task['id'])->update([
                    'task_status' => 'cancelled',
                    'task_status_msg' => '任务已取消',
                    'response_data' => json_encode($result),
                    'updatetime' => time(),
                ]);
                return ['success' => false, 'message' => '任务已取消'];
            }
            
            // 任务仍在处理中
            return ['success' => false, 'message' => '任务处理中'];
            
        } catch (\Exception $e) {
            Log::error('查询火山引擎任务状态失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 处理任务成功
     * @param array $task 任务数据
     * @param array $klingData 可灵AI返回数据
     * @return array
     */
    private static function handleTaskSuccess($task, $klingData)
    {
        Db::startTrans();
        try {
            // 更新任务状态
            Db::name('aivideo_task')->where('id', $task['id'])->update([
                'task_status' => 'succeed',
                'response_data' => json_encode($klingData),
                'updatetime' => time(),
                'completetime' => time(),
            ]);

            // 创建作品记录
            $videos = $klingData['task_result']['videos'] ?? [];
            foreach ($videos as $video) {
                $workData = [
                    'aid' => $task['aid'],
                    'bid' => $task['bid'],
                    'mid' => $task['mid'],
                    'task_id' => $task['id'],
                    'work_name' => $task['task_name'],
                    'work_type' => 'video',
                    'work_url' => $video['url'] ?? '',
                    'video_id' => $video['id'] ?? '',
                    'duration' => $video['duration'] ?? '',
                    'price' => 0, // 默认价格,商家可修改
                    'createtime' => time(),
                ];

                $workId = Db::name('aivideo_work')->insertGetId($workData);

                // 生成预览图和二维码
                self::generateThumbnailAndQrcode($workId, $video['url'] ?? '');
            }

            Db::commit();
            return ['success' => true];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('处理任务成功失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '处理失败'];
        }
    }

    /**
     * 生成预览图和二维码
     * @param int $workId 作品ID
     * @param string $videoUrl 视频URL
     * @return bool
     */
    private static function generateThumbnailAndQrcode($workId, $videoUrl)
    {
        try {
            $config = config('aivideo');

            // 提取视频第一帧
            $thumbnailPath = self::extractVideoFrame($videoUrl, $workId);
            if (!$thumbnailPath) {
                return false;
            }

            // 生成二维码
            $qrcodePath = self::generateQrcode($workId);
            if (!$qrcodePath) {
                return false;
            }

            // 合并预览图和二维码
            $finalThumbnailPath = self::mergeThumbnailAndQrcode($thumbnailPath, $qrcodePath, $workId);
            if (!$finalThumbnailPath) {
                return false;
            }

            // 更新作品记录
            $thumbnailUrl = str_replace(ROOT_PATH, '/', $finalThumbnailPath);
            Db::name('aivideo_work')->where('id', $workId)->update([
                'thumbnail_url' => $thumbnailUrl,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('生成预览图和二维码失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 提取视频第一帧
     * @param string $videoUrl 视频URL
     * @param int $workId 作品ID
     * @return string|false
     */
    private static function extractVideoFrame($videoUrl, $workId)
    {
        $config = config('aivideo');
        $thumbnailPath = $config['upload']['thumbnail_path'] . $workId . '.jpg';

        // 使用FFmpeg提取第一帧
        $command = "ffmpeg -i {$videoUrl} -ss 00:00:00.000 -vframes 1 {$thumbnailPath} 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('提取视频帧失败: ' . implode("\n", $output));
            return false;
        }

        return $thumbnailPath;
    }

    /**
     * 生成二维码
     * @param int $workId 作品ID
     * @return string|false
     */
    private static function generateQrcode($workId)
    {
        $config = config('aivideo');
        $qrcodePath = $config['upload']['qrcode_path'] . $workId . '.png';

        // 生成作品访问URL
        $workUrl = request()->domain() . '/api/aivideo/work_detail?id=' . $workId;

        // 使用endroid/qrcode生成二维码
        $qrCode = new \Endroid\QrCode\QrCode($workUrl);
        $qrCode->setSize(150);
        $qrCode->setMargin(10);

        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);

        file_put_contents($qrcodePath, $result->getString());

        return $qrcodePath;
    }

    /**
     * 合并预览图和二维码
     * @param string $thumbnailPath 预览图路径
     * @param string $qrcodePath 二维码路径
     * @param int $workId 作品ID
     * @return string|false
     */
    private static function mergeThumbnailAndQrcode($thumbnailPath, $qrcodePath, $workId)
    {
        $config = config('aivideo');
        $finalPath = $config['upload']['thumbnail_path'] . $workId . '_final.jpg';

        // 加载预览图
        $thumbnail = imagecreatefromjpeg($thumbnailPath);
        if (!$thumbnail) {
            return false;
        }

        // 加载二维码
        $qrcode = imagecreatefrompng($qrcodePath);
        if (!$qrcode) {
            return false;
        }

        // 获取尺寸
        $thumbWidth = imagesx($thumbnail);
        $thumbHeight = imagesy($thumbnail);
        $qrWidth = imagesx($qrcode);
        $qrHeight = imagesy($qrcode);

        // 将二维码放在右下角
        $x = $thumbWidth - $qrWidth - 20;
        $y = $thumbHeight - $qrHeight - 20;

        // 合并图片
        imagecopy($thumbnail, $qrcode, $x, $y, 0, 0, $qrWidth, $qrHeight);

        // 保存最终图片
        imagejpeg($thumbnail, $finalPath, 90);

        // 释放资源
        imagedestroy($thumbnail);
        imagedestroy($qrcode);

        return $finalPath;
    }
}

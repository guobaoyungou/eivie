<?php
declare(strict_types=1);

namespace app\job;

use app\service\AiTravelPhotoAiService;
use think\queue\Job;

/**
 * 图生图队列任务
 */
class ImageGenerationJob
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
            $aiService = new AiTravelPhotoAiService();
            
            // 使用增强版方法，支持所有场景类型
            $result = $aiService->processGenerationBySceneType($generationId);
            
            if ($result) {
                trace('图生图任务成功：' . $generationId, 'info');
                $job->delete();
            } else {
                throw new \Exception('处理失败');
            }
            
        } catch (\Exception $e) {
            trace('图生图任务失败：' . $generationId . ', ' . $e->getMessage(), 'error');
            
            // 任务失败，最多重试2次（图生图比较耗时，减少重试次数）
            if ($job->attempts() > 2) {
                $job->delete();
            } else {
                // 延迟120秒后重试
                $job->release(120);
            }
        }
    }

    /**
     * 任务失败处理
     * 
     * @param array $data 任务数据
     * @return void
     */
    public function failed($data)
    {
        trace('图生图任务最终失败：' . json_encode($data), 'error');
    }
}

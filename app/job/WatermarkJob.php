<?php
declare(strict_types=1);

namespace app\job;

use app\service\AiTravelPhotoWatermarkService;
use think\queue\Job;

/**
 * 水印处理队列任务
 */
class WatermarkJob
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
        $resultId = $data['result_id'] ?? 0;
        $options = $data['options'] ?? [];
        
        if ($resultId <= 0) {
            $job->delete();
            return;
        }
        
        try {
            $watermarkService = new AiTravelPhotoWatermarkService();
            $result = $watermarkService->addWatermark($resultId, $options);
            
            trace('水印处理任务成功：' . $resultId, 'info');
            
            $job->delete();
            
        } catch (\Exception $e) {
            trace('水印处理任务失败：' . $resultId . ', ' . $e->getMessage(), 'error');
            
            // 任务失败，最多重试2次
            if ($job->attempts() > 2) {
                $job->delete();
            } else {
                // 延迟30秒后重试
                $job->release(30);
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
        trace('水印处理任务最终失败：' . json_encode($data), 'error');
    }
}

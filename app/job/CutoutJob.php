<?php
declare(strict_types=1);

namespace app\job;

use app\service\AiTravelPhotoAiService;
use think\queue\Job;

/**
 * 抠图队列任务
 */
class CutoutJob
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
        $portraitId = $data['portrait_id'] ?? 0;
        
        if ($portraitId <= 0) {
            $job->delete();
            return;
        }
        
        try {
            $aiService = new AiTravelPhotoAiService();
            $result = $aiService->cutoutPortrait($portraitId);
            
            trace('抠图任务成功：' . $portraitId . ', 耗时：' . $result['cost_time'] . 'ms', 'info');
            
            // 删除任务
            $job->delete();
            
        } catch (\Exception $e) {
            // 记录错误
            trace('抠图任务失败：' . $portraitId . ', ' . $e->getMessage(), 'error');
            
            // 任务失败，最多重试3次
            if ($job->attempts() > 3) {
                $job->delete();
            } else {
                // 延迟60秒后重试
                $job->release(60);
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
        trace('抠图任务最终失败：' . json_encode($data), 'error');
    }
}

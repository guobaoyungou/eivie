<?php
/**
 * 通用生成任务队列Job
 * 处理照片生成和视频生成的异步任务
 */
namespace app\job;

use think\queue\Job;
use app\service\GenerationService;

class GenerationJob
{
    /**
     * 执行任务
     * @param Job $job
     * @param array $data
     */
    public function fire(Job $job, $data)
    {
        $recordId = $data['record_id'] ?? 0;
        
        if (!$recordId) {
            $job->delete();
            return;
        }
        
        try {
            $service = new GenerationService();
            $result = $service->executeGeneration($recordId);
            
            if ($result['status'] == 1) {
                // 任务成功，删除队列任务
                $job->delete();
            } else {
                // 任务失败，检查重试次数
                if ($job->attempts() < 3) {
                    // 延迟30秒后重试
                    $job->release(30);
                } else {
                    // 超过最大重试次数，删除队列任务
                    $job->delete();
                }
            }
        } catch (\Exception $e) {
            // 异常处理
            \think\facade\Log::error('GenerationJob执行异常: ' . $e->getMessage(), [
                'record_id' => $recordId,
                'attempts' => $job->attempts(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($job->attempts() < 3) {
                $job->release(60);
            } else {
                $job->delete();
            }
        }
    }
    
    /**
     * 任务失败处理
     * @param array $data
     * @param \Exception $e
     */
    public function failed($data, $e)
    {
        \think\facade\Log::error('GenerationJob最终失败: ' . $e->getMessage(), [
            'data' => $data,
            'error' => $e->getMessage()
        ]);
    }
}

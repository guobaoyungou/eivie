<?php
declare(strict_types=1);

namespace app\job;

use think\facade\Log;
use app\service\AiTravelPhotoSynthesisQrService;

/**
 * 合成活动异步生成队列任务
 * 
 * 消费 synthesis_activity_generate 队列，
 * 异步执行标签识别→提示词改写→AI生成→水印添加的完整流程
 */
class SynthesisActivityGenerateJob
{
    /**
     * 执行任务
     * 
     * @param array $data ['user_photo_id' => int]
     */
    public function fire($job, $data): void
    {
        $userPhotoId = (int)($data['user_photo_id'] ?? 0);

        if ($userPhotoId <= 0) {
            Log::error('SynthesisActivityGenerateJob: 无效的 user_photo_id');
            $job->delete();
            return;
        }

        Log::info('SynthesisActivityGenerateJob: 开始处理', ['user_photo_id' => $userPhotoId]);

        try {
            $qrService = new AiTravelPhotoSynthesisQrService();
            $qrService->executeGeneration($userPhotoId);

            // 执行完成，删除任务
            $job->delete();
            Log::info('SynthesisActivityGenerateJob: 处理完成', ['user_photo_id' => $userPhotoId]);
        } catch (\Throwable $e) {
            Log::error('SynthesisActivityGenerateJob: 处理失败', [
                'user_photo_id' => $userPhotoId,
                'error' => $e->getMessage(),
            ]);

            // 当前尝试次数达到最大重试次数时删除任务
            if ($job->attempts() >= 2) {
                $job->delete();
                Log::error('SynthesisActivityGenerateJob: 超过最大重试次数，任务已删除', [
                    'user_photo_id' => $userPhotoId,
                    'attempts' => $job->attempts(),
                ]);
            } else {
                // 重试延迟60秒
                $job->release(60);
                Log::info('SynthesisActivityGenerateJob: 任务延迟重试', [
                    'user_photo_id' => $userPhotoId,
                    'attempts' => $job->attempts(),
                ]);
            }
        }
    }

    /**
     * 任务失败回调
     */
    public function failed($data): void
    {
        $userPhotoId = (int)($data['user_photo_id'] ?? 0);
        Log::error('SynthesisActivityGenerateJob: 任务彻底失败', ['user_photo_id' => $userPhotoId]);
    }
}

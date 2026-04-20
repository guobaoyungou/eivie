<?php
declare(strict_types=1);

namespace app\job;

use app\service\AiTravelPhotoPortraitService;
use think\facade\Log;
use think\queue\Job;

/**
 * 人脸特征补提队列任务
 *
 * 对缺少 face_embedding 的人像记录，调用 InsightFace 提取特征并写入 MySQL + Milvus。
 * 适用场景：
 * - InsightFace 服务宕机期间入库的人像，服务恢复后异步补提
 * - 历史人像无特征数据，商家后台点击"批量特征入库"触发
 *
 * Class FaceEmbeddingBackfillJob
 * @package app\job
 */
class FaceEmbeddingBackfillJob
{
    /**
     * 执行任务
     *
     * @param Job $job 任务对象
     * @param array $data 任务数据 ['portrait_id' => int]
     * @return void
     */
    public function fire(Job $job, $data)
    {
        $portraitId = $data['portrait_id'] ?? 0;

        if ($portraitId <= 0) {
            Log::warning('补提特征任务: portrait_id 无效', ['data' => $data]);
            $job->delete();
            return;
        }

        try {
            $service = new AiTravelPhotoPortraitService();
            $result = $service->backfillFaceEmbedding($portraitId);

            if ($result['success']) {
                Log::info('补提特征任务成功', [
                    'portrait_id' => $portraitId,
                    'message' => $result['message'],
                ]);
                $job->delete();
            } else {
                // 未检测到人脸等非异常情况，直接标记完成
                Log::info('补提特征任务完成（无需入库）', [
                    'portrait_id' => $portraitId,
                    'message' => $result['message'],
                ]);
                $job->delete();
            }
        } catch (\Exception $e) {
            Log::error('补提特征任务异常', [
                'portrait_id' => $portraitId,
                'error' => $e->getMessage(),
                'attempts' => $job->attempts(),
            ]);

            // 最多重试 3 次，间隔 120 秒
            if ($job->attempts() > 3) {
                Log::error('补提特征任务最终失败，已达最大重试次数', [
                    'portrait_id' => $portraitId,
                ]);
                $job->delete();
            } else {
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
        Log::error('补提特征任务最终失败', ['data' => json_encode($data)]);
    }
}

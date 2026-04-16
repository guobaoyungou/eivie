<?php
declare(strict_types=1);

namespace app\job;

use app\service\AutoTaggingService;
use think\facade\Db;
use think\facade\Log;
use think\queue\Job;

/**
 * 自动标签识别队列任务
 * 
 * 消费 auto_image_tagging 队列，调用 InsightFace + FairFace 服务
 * 对场景模板的源图片进行属性识别，并将标签写入模板
 * 
 * @date 2026-04-16
 */
class AutoTaggingJob
{
    /**
     * 执行任务
     *
     * @param Job   $job  任务对象
     * @param array $data 任务数据 ['template_id' => int, 'image_url' => string, 'timestamp' => int]
     * @return void
     */
    public function fire(Job $job, $data)
    {
        $templateId = intval($data['template_id'] ?? 0);
        $imageUrl   = $data['image_url'] ?? '';

        if ($templateId <= 0 || empty($imageUrl)) {
            Log::error('AutoTaggingJob: 无效的任务数据', $data);
            $job->delete();
            return;
        }

        $service = new AutoTaggingService();

        // 从 sysset 表读取配置，优先于 config 文件
        $maxRetry   = 2;
        $retryDelay = 60;
        try {
            $autoTagRow = Db::name('sysset')->where('name', 'auto_tagging')->value('value');
            if ($autoTagRow) {
                $atCfg = json_decode($autoTagRow, true) ?: [];
                $maxRetry   = intval($atCfg['auto_tag_max_retry'] ?? 2);
                $retryDelay = intval($atCfg['auto_tag_retry_delay'] ?? 60);
            } else {
                $config = \think\facade\Config::get('auto_tagging', []);
                $maxRetry   = intval($config['auto_tag_max_retry'] ?? 2);
                $retryDelay = intval($config['auto_tag_retry_delay'] ?? 60);
            }
        } catch (\Exception $e) {
            // 使用默认值
        }

        try {
            Log::info('AutoTaggingJob: 开始处理模板 ' . $templateId, [
                'image_url' => $imageUrl,
                'attempt'   => $job->attempts(),
            ]);

            // 1. 调用 InsightFace + FairFace 识别服务
            $apiResponse = $service->callFairFaceApi($imageUrl);

            // 2. 检查是否检测到人脸
            $faceCount = intval($apiResponse['face_count'] ?? 0);
            if ($faceCount === 0) {
                // 未检测到人脸，标记失败但不重试
                $service->markFailed($templateId, '未检测到人脸');
                Log::info('AutoTaggingJob: 模板 ' . $templateId . ' 未检测到人脸');
                $job->delete();
                return;
            }

            // 3. 解析标签并映射为中文
            $tagsData = $service->parseAndMapTags($apiResponse);

            // 4. 检查是否有有效标签（全部低于阈值时 primary_tags 为空）
            if (empty($tagsData['primary_tags'])) {
                $service->markFailed($templateId, '所有属性置信度低于阈值，无有效标签');
                Log::info('AutoTaggingJob: 模板 ' . $templateId . ' 置信度不足，无标签写入');
                $job->delete();
                return;
            }

            // 5. 写入标签到模板
            $merged = $service->mergeTagsToTemplate($templateId, $tagsData);

            if ($merged) {
                Log::info('AutoTaggingJob: 模板 ' . $templateId . ' 标签识别完成', [
                    'primary_tags' => $tagsData['primary_tags'],
                    'face_count'   => $faceCount,
                ]);
                $job->delete();
            } else {
                throw new \Exception('标签写入数据库失败');
            }

        } catch (\Exception $e) {
            Log::error('AutoTaggingJob: 模板 ' . $templateId . ' 处理异常: ' . $e->getMessage(), [
                'attempt' => $job->attempts(),
                'trace'   => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            // 重试判断
            if ($job->attempts() >= $maxRetry) {
                // 超过最大重试次数，标记失败
                $service->markFailed($templateId, '重试 ' . $job->attempts() . ' 次后仍失败: ' . $e->getMessage());
                $job->delete();
            } else {
                // 延迟重试
                $job->release($retryDelay);
            }
        }
    }

    /**
     * 任务最终失败处理
     *
     * @param array $data 任务数据
     * @return void
     */
    public function failed($data)
    {
        $templateId = intval($data['template_id'] ?? 0);
        Log::error('AutoTaggingJob: 任务最终失败', $data);

        if ($templateId > 0) {
            try {
                $service = new AutoTaggingService();
                $service->markFailed($templateId, '队列任务最终失败');
            } catch (\Throwable $e) {
                Log::error('AutoTaggingJob: failed() 异常: ' . $e->getMessage());
            }
        }
    }
}

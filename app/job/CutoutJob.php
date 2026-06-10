<?php
declare(strict_types=1);

namespace app\job;

use app\model\AiTravelPhotoPortrait;
use app\service\AiTravelPhotoAiService;
use app\service\ImageAnalysisService;
use think\facade\Db;
use think\facade\Log;
use think\queue\Job;

/**
 * 抠图 + 自动标签识别队列任务
 * 消费 ai_cutout 队列，先执行智能抠图，成功后自动分析人物属性标签
 */
class CutoutJob
{
    /**
     * 执行任务
     *
     * @param Job   $job  任务对象
     * @param array $data 任务数据 ['portrait_id' => int]
     * @return void
     */
    public function fire(Job $job, $data)
    {
        $portraitId = (int)($data['portrait_id'] ?? 0);
        
        if ($portraitId <= 0) {
            $job->delete();
            return;
        }
        
        try {
            // === 第1步：智能抠图 ===
            $aiService = new AiTravelPhotoAiService();
            $result = $aiService->cutoutPortrait($portraitId);
            
            Log::info('CutoutJob: 抠图成功 portrait_id=' . $portraitId . ', 耗时=' . $result['cost_time'] . 'ms');
            
            // === 第2步：自动标签识别（抠图成功后触发） ===
            $this->autoTagPortrait($portraitId);
            
            // 删除任务
            $job->delete();
            
        } catch (\Throwable $e) {
            Log::error('CutoutJob: 任务失败 portrait_id=' . $portraitId, [
                'error' => $e->getMessage(),
                'attempt' => $job->attempts(),
            ]);
            
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
     * 自动标签识别：从 NL 自然语言描述中正则提取人物属性
     * 替代原 InsightFace+FairFace API 调用，基于 LLM 生成的 nl_description 文本提取标签
     *
     * @param int $portraitId
     * @return void
     */
    private function autoTagPortrait(int $portraitId): void
    {
        try {
            // 获取人像的 NL 描述
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->field('id, nl_description, nl_description_status, auto_tag_status')
                ->find();

            if (empty($portrait)) {
                Log::warning('CutoutJob: autoTagPortrait 人像记录不存在', ['portrait_id' => $portraitId]);
                return;
            }

            $nlDesc = $portrait['nl_description'] ?? '';

            if (empty(trim($nlDesc))) {
                $nlStatus = (int)($portrait['nl_description_status'] ?? 0);

                // 如果 NL 描述生成失败（status=3），尝试重试一次
                if ($nlStatus === 3) {
                    Log::info('CutoutJob: NL描述生成失败，尝试重新生成 portrait_id=' . $portraitId);
                    try {
                        $descService = new \app\service\PortraitDescriptionService();
                        $retryResult = $descService->generateDescription($portraitId);
                        if ($retryResult['status']) {
                            Log::info('CutoutJob: NL描述重新生成成功 portrait_id=' . $portraitId);
                            return; // generateDescription 内部已完成标签写入
                        }
                        Log::warning('CutoutJob: NL描述重新生成仍然失败 portrait_id=' . $portraitId);
                    } catch (\Exception $e) {
                        Log::warning('CutoutJob: NL描述重新生成异常 portrait_id=' . $portraitId, ['error' => $e->getMessage()]);
                    }
                }

                // NL 描述尚未生成
                $currentAutoTagStatus = (int)($portrait['auto_tag_status'] ?? 0);
                if ($currentAutoTagStatus !== 2 && $currentAutoTagStatus !== 4) {
                    // 仅在 auto_tag 未完成时标记待处理（避免覆盖上传时已写入的结果）
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'auto_tag_status' => 0,
                        'update_time'     => time(),
                    ]);
                }
                Log::info('CutoutJob: 人像 ' . $portraitId . ' NL描述未生成，跳过自动标签（nl_status=' . $nlStatus . ' auto_tag_status=' . $currentAutoTagStatus . '）');
                return;
            }

            // 从 NL 描述正则提取全量标签
            $tags = ImageAnalysisService::extractTagsFromDescription($nlDesc);

            // 合并 update_time
            $tags['update_time'] = time();
            $tags['extended_tag_status'] = 2;
            $tags['extended_tag_time'] = time();

            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update($tags);

            Log::info('CutoutJob: 人像 ' . $portraitId . ' 自动标签识别完成（NL正则提取）', [
                'gender'        => $tags['gender_tag'],
                'age'           => $tags['age_tag'],
                'emotion'       => $tags['emotion_primary'],
                'glasses'       => $tags['glasses_type'],
                'has_beard'     => $tags['has_beard'],
                'hair_length'   => $tags['hair_length'],
                'face_shape'    => $tags['face_shape'],
            ]);

        } catch (\Throwable $e) {
            Log::warning('CutoutJob: autoTagPortrait 异常 portrait_id=' . $portraitId, [
                'error' => $e->getMessage(),
            ]);
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
        Log::error('CutoutJob: 任务最终失败', $data);

        $portraitId = (int)($data['portrait_id'] ?? 0);
        if ($portraitId > 0) {
            try {
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'cutout_status' => AiTravelPhotoPortrait::CUTOUT_STATUS_FAILED,
                    'cutout_error' => '任务最终失败',
                    'update_time' => time(),
                ]);
            } catch (\Throwable $e) {
                Log::error('CutoutJob: failed() 异常: ' . $e->getMessage());
            }
        }
    }
}

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
     * 自动标签识别：调用 InsightFace+FairFace 分析人物属性
     * 使用原图进行人脸分析（抠图后透明背景可能影响识别准确率）
     *
     * @param int $portraitId
     * @return void
     */
    private function autoTagPortrait(int $portraitId): void
    {
        try {
            // 标记为识别中
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'auto_tag_status' => 1,
                'update_time' => time(),
            ]);

            // 获取人像原图 URL
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->field('id, original_url')
                ->find();

            if (empty($portrait) || empty($portrait['original_url'])) {
                Log::warning('CutoutJob: autoTagPortrait 人像记录不存在或无原图', ['portrait_id' => $portraitId]);
                return;
            }

            // 调用人物属性分析服务
            $analysisService = new ImageAnalysisService();
            $result = $analysisService->analyzeFromUrl($portrait['original_url']);

            if (!$result || empty($result['faces'])) {
                // 未检测到人脸，标记失败
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'auto_tag_status' => 3,
                    'gender_tag' => 'Unknown',
                    'age_tag' => 'Unknown',
                    'is_multi_face' => 0,
                    'face_count' => 0,
                    'update_time' => time(),
                ]);
                Log::info('CutoutJob: 人像 ' . $portraitId . ' 未检测到人脸');
                return;
            }

            // 提取主体人物属性
            $attr = ImageAnalysisService::extractMainSubject($result);

            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'gender_tag' => $attr['gender'] ?? 'Unknown',
                'age_tag' => $attr['age_group'] ?? 'Unknown',
                'is_multi_face' => ($attr['is_multi_face'] ?? false) ? 1 : 0,
                'face_count' => $attr['face_count'] ?? 0,
                'auto_tag_status' => 2, // 2=已完成
                'update_time' => time(),
            ]);

            Log::info('CutoutJob: 人像 ' . $portraitId . ' 自动标签识别完成', [
                'gender' => $attr['gender'] ?? 'Unknown',
                'age_group' => $attr['age_group'] ?? 'Unknown',
                'face_count' => $attr['face_count'] ?? 0,
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

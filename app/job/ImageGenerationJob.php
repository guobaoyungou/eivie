<?php
declare(strict_types=1);

namespace app\job;

use app\service\AiTravelPhotoAiService;
use app\service\AiTravelPhotoSynthesisService;
use think\facade\Db;
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
        // 队列反序列化后 generation_id 可能是字符串，必须强制转 int
        $generationId = (int)($data['generation_id'] ?? 0);
        
        if ($generationId <= 0) {
            $job->delete();
            return;
        }
        
        try {
            // 查询 generation 记录，判断走哪条处理路径
            $generation = Db::name('ai_travel_photo_generation')
                ->where('id', $generationId)
                ->field('id, template_id, scene_id')
                ->find();

            if (!$generation) {
                trace('generation记录不存在：' . $generationId, 'error');
                $job->delete();
                return;
            }

            // 模板类任务（template_id>0）走 SynthesisService（支持volcengine+系统Key池）
            // 场景类任务（scene_id>0）走 AiService
            if (!empty($generation['template_id']) && empty($generation['scene_id'])) {
                $synthService = new AiTravelPhotoSynthesisService();
                $result = $synthService->processSingleGeneration($generationId);
            } else {
                $aiService = new AiTravelPhotoAiService();
                $result = $aiService->processGenerationBySceneType($generationId);
            }

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
                // 最终失败：更新 generation 记录状态为失败
                $this->markGenerationFailed($generationId, $e->getMessage());
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
        $generationId = (int)($data['generation_id'] ?? 0);
        trace('图生图任务最终失败：' . json_encode($data), 'error');
        
        // 确保 generation 记录被标记为失败
        if ($generationId > 0) {
            $this->markGenerationFailed($generationId, '队列任务最终失败');
        }
    }
    
    /**
     * 标记 generation 记录为失败，并同步更新关联 portrait 的 synthesis_status
     * 
     * @param int $generationId 生成记录ID
     * @param string $errorMsg 错误信息
     * @return void
     */
    private function markGenerationFailed(int $generationId, string $errorMsg): void
    {
        try {
            // 检查是否已经有更详细的错误信息（processGenerationBySceneType 可能已设置）
            $existing = Db::name('ai_travel_photo_generation')
                ->where('id', $generationId)
                ->field('status, error_msg, portrait_id')
                ->find();
            
            if (!$existing) {
                return;
            }
            
            // 如果记录已经被标记为失败且已有详细错误信息，不覆盖
            $shouldUpdateGeneration = true;
            if ($existing['status'] == 3 && !empty($existing['error_msg']) && $existing['error_msg'] !== $errorMsg) {
                $shouldUpdateGeneration = false;
            }
            
            if ($shouldUpdateGeneration) {
                Db::name('ai_travel_photo_generation')->where('id', $generationId)->update([
                    'status' => 3,
                    'error_msg' => mb_substr($errorMsg, 0, 500),
                    'finish_time' => time(),
                    'update_time' => time(),
                ]);
            }
            
            // 查询该 generation 关联的 portrait_id
            $portraitId = (int)($existing['portrait_id'] ?? 0);
            
            if ($portraitId > 0) {
                // 检查该 portrait 下所有 generation 是否都已完成（非 status=0 和 status=1）
                $pendingCount = Db::name('ai_travel_photo_generation')
                    ->where('portrait_id', $portraitId)
                    ->whereIn('status', [0, 1]) // 待处理 或 处理中
                    ->count();
                
                if ($pendingCount == 0) {
                    // 所有 generation 都已完成，检查是否有成功的
                    $successCount = Db::name('ai_travel_photo_generation')
                        ->where('portrait_id', $portraitId)
                        ->where('status', 2) // STATUS_SUCCESS
                        ->count();
                    
                    // 有成功的就标记为成功(3)，全部失败则标记为失败(4)
                    $synthesisStatus = $successCount > 0 ? 3 : 4;
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => $synthesisStatus,
                        'synthesis_error' => $successCount > 0 ? '' : '所有合成任务均失败',
                        'update_time' => time(),
                    ]);
                    
                    trace("Portrait {$portraitId} synthesis_status 更新为 {$synthesisStatus}（成功:{$successCount}）", 'info');
                }
            }
        } catch (\Throwable $e) {
            trace('markGenerationFailed 异常：' . $e->getMessage(), 'error');
        }
    }
}

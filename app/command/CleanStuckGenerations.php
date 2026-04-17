<?php
declare(strict_types=1);

namespace app\command;

use app\model\GenerationRecord;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

/**
 * 清理卡在"处理中"的生成记录
 * 
 * 定期扫描并修复异常滞留的"处理中"记录，包括：
 * - generation_record 表：场景模板订单的生成记录
 * - ai_travel_photo_generation 表：人像合成的生成记录
 * 
 * 超过15分钟仍处于处理中状态的记录将被标记为失败
 * 
 * 建议每5分钟执行一次：
 * crontab: * /5 * * * * php /home/www/ai.eivie.cn/think clean_stuck_generations
 */
class CleanStuckGenerations extends Command
{
    /** generation_record 默认超时阈值（秒）：15分钟 */
    const RECORD_TIMEOUT = 900;

    /** generation_record SeeDream多图任务的最大超时阈值（秒）：25分钟
     *  9张图的curl超时为 9*120+60=1140秒，加上队列调度延迟，1500秒安全余量 */
    const RECORD_TIMEOUT_MAX = 1500;

    /** ai_travel_photo_generation 超时阈值（秒）：15分钟 */
    const PHOTO_GEN_TIMEOUT = 900;

    protected function configure()
    {
        $this->setName('clean_stuck_generations')
            ->setDescription('清理卡在"处理中"的生成记录（超时自动标记为失败）');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('[' . date('Y-m-d H:i:s') . '] 开始清理卡住的生成记录...');

        $fixedRecords = $this->cleanStuckGenerationRecords($output);
        $fixedPhotos = $this->cleanStuckPhotoGenerations($output);

        $total = $fixedRecords + $fixedPhotos;
        if ($total > 0) {
            $output->info("共修复 {$total} 条卡住的记录（generation_record: {$fixedRecords}, ai_travel_photo_generation: {$fixedPhotos}）");
            Log::info("CleanStuckGenerations: 共修复 {$total} 条卡住的记录", [
                'generation_record' => $fixedRecords,
                'ai_travel_photo_generation' => $fixedPhotos,
            ]);
        } else {
            $output->writeln('未发现需要修复的卡住记录');
        }

        $output->writeln('[' . date('Y-m-d H:i:s') . '] 清理完成');
    }

    /**
     * 清理 generation_record 表中卡住的记录
     *
     * @param Output $output
     * @return int 修复的记录数
     */
    private function cleanStuckGenerationRecords(Output $output): int
    {
        $fixed = 0;
        $cutoffTime = time() - self::RECORD_TIMEOUT;

        try {
            // 查找超时的处理中记录
            $stuckRecords = Db::name('generation_record')
                ->where('status', GenerationRecord::STATUS_PROCESSING)
                ->where('start_time', '>', 0)
                ->where('start_time', '<', $cutoffTime)
                ->limit(100)
                ->select()
                ->toArray();

            foreach ($stuckRecords as $record) {
                try {
                    $recordModel = GenerationRecord::find($record['id']);
                    if (!$recordModel || $recordModel->status != GenerationRecord::STATUS_PROCESSING) {
                        continue;
                    }

                    // 对 SeeDream 多图任务动态计算超时阈值，避免与队列 worker 竞态
                    $recordTimeout = self::RECORD_TIMEOUT;
                    $modelCode = $record['model_code'] ?? '';
                    if (strpos($modelCode, 'doubao-seedream') === 0) {
                        $inputParams = $record['input_params'] ?? '';
                        if (is_string($inputParams)) {
                            $inputParams = json_decode($inputParams, true);
                        }
                        $seqOpts = $inputParams['sequential_image_generation_options'] ?? null;
                        if (is_string($seqOpts)) {
                            $seqOpts = json_decode($seqOpts, true);
                        }
                        $maxImages = (is_array($seqOpts) && isset($seqOpts['max_images'])) ? intval($seqOpts['max_images']) : 1;
                        if ($maxImages > 1) {
                            $recordTimeout = min(self::RECORD_TIMEOUT_MAX, $maxImages * 150 + 300);
                        }
                    }

                    $elapsed = time() - intval($record['start_time']);
                    if ($elapsed <= $recordTimeout) {
                        // 还在允许的处理时间内，跳过
                        continue;
                    }

                    // 标记为失败（markFailed 内部会自动同步更新关联 generation_order）
                    $recordModel->markFailed('TIMEOUT', '任务超时自动修复（超过' . intval($recordTimeout / 60) . '分钟）');
                    $output->writeln("  [generation_record] ID={$record['id']} model_code={$record['model_code']} 已滞留{$elapsed}秒 → 标记为失败");
                    Log::warning('CleanStuckGenerations: generation_record 超时修复', [
                        'record_id' => $record['id'],
                        'model_code' => $record['model_code'] ?? '',
                        'elapsed_seconds' => $elapsed,
                    ]);

                    $fixed++;
                } catch (\Throwable $e) {
                    $output->error("  [generation_record] ID={$record['id']} 修复失败: {$e->getMessage()}");
                    Log::error('CleanStuckGenerations: generation_record 修复异常', [
                        'record_id' => $record['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $output->error('cleanStuckGenerationRecords 异常: ' . $e->getMessage());
            Log::error('CleanStuckGenerations: cleanStuckGenerationRecords 异常: ' . $e->getMessage());
        }

        return $fixed;
    }

    /**
     * 清理 ai_travel_photo_generation 表中卡住的记录
     * 并检查关联的 portrait 的 synthesis_status
     *
     * @param Output $output
     * @return int 修复的记录数
     */
    private function cleanStuckPhotoGenerations(Output $output): int
    {
        $fixed = 0;
        $cutoffTime = time() - self::PHOTO_GEN_TIMEOUT;
        $affectedPortraitIds = [];

        try {
            // 查找超时的处理中记录
            $stuckGenerations = Db::name('ai_travel_photo_generation')
                ->where('status', 1) // 处理中
                ->where('start_time', '>', 0)
                ->where('start_time', '<', $cutoffTime)
                ->limit(100)
                ->select()
                ->toArray();

            foreach ($stuckGenerations as $gen) {
                try {
                    // 更新为失败
                    Db::name('ai_travel_photo_generation')->where('id', $gen['id'])->update([
                        'status' => 3,
                        'error_msg' => '任务超时自动修复（超过' . intval(self::PHOTO_GEN_TIMEOUT / 60) . '分钟）',
                        'finish_time' => time(),
                        'update_time' => time(),
                    ]);

                    $elapsed = time() - intval($gen['start_time']);
                    $output->writeln("  [ai_travel_photo_generation] ID={$gen['id']} portrait_id={$gen['portrait_id']} 已滞留{$elapsed}秒 → 标记为失败");
                    Log::warning('CleanStuckGenerations: ai_travel_photo_generation 超时修复', [
                        'generation_id' => $gen['id'],
                        'portrait_id' => $gen['portrait_id'] ?? 0,
                        'elapsed_seconds' => $elapsed,
                    ]);

                    // 收集需要检查的 portrait_id
                    $portraitId = intval($gen['portrait_id'] ?? 0);
                    if ($portraitId > 0) {
                        $affectedPortraitIds[$portraitId] = true;
                    }

                    $fixed++;
                } catch (\Throwable $e) {
                    $output->error("  [ai_travel_photo_generation] ID={$gen['id']} 修复失败: {$e->getMessage()}");
                    Log::error('CleanStuckGenerations: ai_travel_photo_generation 修复异常', [
                        'generation_id' => $gen['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 修复关联的 portrait synthesis_status
            foreach (array_keys($affectedPortraitIds) as $portraitId) {
                $this->fixPortraitSynthesisStatus($portraitId, $output);
            }
        } catch (\Throwable $e) {
            $output->error('cleanStuckPhotoGenerations 异常: ' . $e->getMessage());
            Log::error('CleanStuckGenerations: cleanStuckPhotoGenerations 异常: ' . $e->getMessage());
        }

        return $fixed;
    }

    /**
     * 修复 portrait 的 synthesis_status
     * 检查该 portrait 下所有 generation 是否都已完成，根据结果更新状态
     *
     * @param int $portraitId
     * @param Output $output
     * @return void
     */
    private function fixPortraitSynthesisStatus(int $portraitId, Output $output): void
    {
        try {
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->field('id, synthesis_status')
                ->find();

            if (!$portrait) {
                return;
            }

            // 检查是否还有未完成的 generation
            $pendingCount = Db::name('ai_travel_photo_generation')
                ->where('portrait_id', $portraitId)
                ->whereIn('status', [0, 1]) // 待处理或处理中
                ->count();

            if ($pendingCount > 0) {
                // 还有未完成的任务，不更新 portrait 状态
                return;
            }

            // 所有 generation 都已完成，检查成功数
            $successCount = Db::name('ai_travel_photo_generation')
                ->where('portrait_id', $portraitId)
                ->where('status', 2) // 成功
                ->count();

            // 有成功的标记为3（成功），全失败标记为4（失败）
            $newStatus = $successCount > 0 ? 3 : 4;
            $currentStatus = intval($portrait['synthesis_status']);

            // 仅在状态为2（处理中）时才更新，避免覆盖已手动处理过的状态
            if ($currentStatus == 2) {
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => $newStatus,
                    'synthesis_error' => $newStatus == 4 ? '所有合成任务均超时失败' : '',
                    'update_time' => time(),
                ]);

                $statusText = $newStatus == 3 ? '成功' : '失败';
                $output->writeln("  [portrait] ID={$portraitId} synthesis_status: {$currentStatus} → {$newStatus}（{$statusText}，成功数:{$successCount}）");
                Log::info('CleanStuckGenerations: portrait synthesis_status 修复', [
                    'portrait_id' => $portraitId,
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus,
                    'success_count' => $successCount,
                ]);
            }
        } catch (\Throwable $e) {
            $output->error("  [portrait] ID={$portraitId} 状态修复失败: {$e->getMessage()}");
            Log::error('CleanStuckGenerations: fixPortraitSynthesisStatus 异常', [
                'portrait_id' => $portraitId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

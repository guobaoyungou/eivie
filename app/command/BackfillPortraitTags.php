<?php
declare(strict_types=1);

namespace app\command;

use app\service\ImageAnalysisService;
use app\service\PortraitDescriptionService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Db;
use think\facade\Log;

/**
 * 批量回填人像自动标签
 * 
 * 对已标记人像重新分析并补全数据
 * 
 * 用法：
 *   php think backfill_portrait_tags                         # 仅补全缺失的 race + precise_age
 *   php think backfill_portrait_tags --extended              # 补全 race + precise_age + 扩展标签
 *   php think backfill_portrait_tags --force                # 强制重分析所有已标记人像（含性别翻转）
 *   php think backfill_portrait_tags --force --limit=20     # 强制重分析前20条
 *   php think backfill_portrait_tags --description-only     # 仅生成自然语言描述（不重新分析标签）
 *   php think backfill_portrait_tags --description-only --limit=10
 */
class BackfillPortraitTags extends Command
{
    protected function configure()
    {
        $this->setName('backfill_portrait_tags')
            ->setDescription('批量回填人像自动标签（race_tag + precise_age + 扩展标签）')
            ->addOption('extended', 'e', Option::VALUE_NONE, '启用扩展标签（表情+五官特征）')
            ->addOption('force', 'f', Option::VALUE_NONE, '强制重分析所有已标记人像（含性别翻转）')
            ->addOption('description-only', 'd', Option::VALUE_NONE, '仅生成自然语言描述（不重新分析标签）')
            ->addOption('limit', 'l', Option::VALUE_OPTIONAL, '限制处理数量', 0);
    }

    protected function execute(Input $input, Output $output)
    {
        $useExtended = $input->hasOption('extended') && $input->getOption('extended');
        $useForce = $input->hasOption('force') && $input->getOption('force');
        $descriptionOnly = $input->hasOption('description-only') && $input->getOption('description-only');
        $limit = (int)($input->getOption('limit') ?: 0);

        // --- 单独处理 description-only 模式 ---
        if ($descriptionOnly) {
            $output->writeln('=== 自然语言描述回填 ===');
            $output->writeln('限制数量: ' . ($limit > 0 ? $limit : '无限制'));
            $output->writeln('');

            $query = Db::name('ai_travel_photo_portrait')
                ->where('auto_tag_status', 2)
                ->where('original_url', '<>', '')
                ->where('nl_description_status', '<>', 2); // 仅处理未生成或失败的

            if ($limit > 0) {
                $query->limit($limit);
            }

            $portraits = $query->field('id, original_url')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            $total = count($portraits);
            $output->writeln("待处理: {$total} 条");
            $output->writeln('');

            if ($total === 0) {
                $output->writeln('没有需要生成描述的数据。');
                return;
            }

            $descService = new PortraitDescriptionService();
            $successCount = 0;
            $failedCount = 0;
            $startTime = time();

            foreach ($portraits as $idx => $portrait) {
                $num = $idx + 1;
                $pid = $portrait['id'];
                $output->write("[{$num}/{$total}] ID={$pid} ...");

                $result = $descService->generateDescription($pid);
                if ($result['status']) {
                    $output->writeln(' 成功 ('.mb_strlen($result['description']).'字)');
                    $successCount++;
                } else {
                    $output->writeln(' 失败: ' . ($result['msg'] ?? '未知'));
                    $failedCount++;
                }

                usleep(300000); // 300ms 间隔
            }

            $elapsed = time() - $startTime;
            $output->writeln('');
            $output->info("=== 描述回填完成 === 成功:{$successCount} 失败:{$failedCount} 耗时:{$elapsed}s");
            return;
        }

        $output->writeln('=== 人像标签批量回填 ===');
        $output->writeln('强制模式: ' . ($useForce ? '启用（全部重新分析）' : '禁用（仅补缺）'));
        $output->writeln('扩展标签: ' . ($useExtended ? '启用' : '禁用'));
        $output->writeln('限制数量: ' . ($limit > 0 ? $limit : '无限制'));
        $output->writeln('');

        // 查询人像
        $query = Db::name('ai_travel_photo_portrait')
            ->where('auto_tag_status', 2)
            ->where('original_url', '<>', '');

        // 非强制模式下仅处理缺少 race_tag 或 precise_age 的记录
        if (!$useForce) {
            $query->where(function ($q) {
                $q->where('race_tag', '=', '')
                  ->whereOr('precise_age', 'null');
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $portraits = $query->field('id, original_url, gender_tag, age_tag')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $total = count($portraits);
        $output->writeln("待处理: {$total} 条");
        $output->writeln('');

        if ($total === 0) {
            $output->writeln('没有需要回填的数据。');
            return;
        }

        $analysisService = new ImageAnalysisService();
        $success = 0;
        $noFace = 0;
        $errCount = 0;
        $startTime = time();

        foreach ($portraits as $idx => $portrait) {
            $num = $idx + 1;
            $pid = $portrait['id'];
            $url = $portrait['original_url'];
            $oldGender = $portrait['gender_tag'] ?? '';

            $output->write("[{$num}/{$total}] ID={$pid} ...");

            try {
                $result = $analysisService->analyzeFromUrl($url);

                if (!$result || empty($result['faces'])) {
                    $output->writeln(' 无人脸');
                    $noFace++;
                    continue;
                }

                $attr = ImageAnalysisService::extractMainSubject($result);

                $newGender = $attr['gender'] ?? '';
                $genderFlipped = (bool)($attr['is_low_confidence'] ?? false);

                $updateData = [
                    'gender_tag'       => $newGender,
                    'race_tag'         => $attr['race'] ?? '',
                    'gender_confidence' => isset($attr['gender_confidence']) ? round((float)$attr['gender_confidence'], 4) : null,
                    'age_confidence'    => isset($attr['age_confidence']) ? round((float)$attr['age_confidence'], 4) : null,
                    'update_time'      => time(),
                ];

                if (isset($attr['age']) && $attr['age'] !== null) {
                    $updateData['precise_age'] = round((float)$attr['age'], 2);
                }

                // 年龄标签（用新映射）
                $newAgeLabel = '';
                if (isset($attr['age']) && $attr['age'] !== null) {
                    $newAgeLabel = ImageAnalysisService::mapAgeToPreciseRange((float)$attr['age']);
                }
                if (empty($newAgeLabel) && isset($attr['age_group']) && $attr['age_group'] !== '') {
                    $newAgeLabel = $attr['age_group'];
                }
                if (!empty($newAgeLabel)) {
                    $updateData['age_tag'] = $newAgeLabel;
                }

                // 扩展标签
                if ($useExtended) {
                    try {
                        $extended = $analysisService->analyzeExtended($url);
                        if ($extended && !empty($extended['faces'])) {
                            $extendedAttr = ImageAnalysisService::parseExtendedAttributes($extended);
                            if (!empty($extendedAttr)) {
                                $updateData = array_merge($updateData, $extendedAttr);
                            }
                        }
                    } catch (\Throwable $extErr) {
                        $output->write(" [扩展失败]");
                    }
                }

                Db::name('ai_travel_photo_portrait')
                    ->where('id', $pid)
                    ->update($updateData);

                $race = $attr['race'] ?? '-';
                $age = isset($attr['age']) ? round((float)$attr['age'], 1) . '岁' : '-';
                $emotion = $updateData['emotion_primary'] ?? '';
                $info = " gender={$newGender} race={$race} age={$age}";
                if ($emotion) {
                    $info .= " emotion={$emotion}";
                }
                $output->writeln($info);

                $success++;

            } catch (\Throwable $e) {
                $output->writeln(" 错误: " . $e->getMessage());
                $errCount++;
            }

            usleep(150000); // 150ms 间隔
        }

        $elapsed = time() - $startTime;
        $output->writeln('');
        $summary = "=== 回填完成 === 成功:{$success} 无人脸:{$noFace} 错误:{$errCount}";
        $summary .= " 耗时:{$elapsed}s";
        $output->info($summary);
    }
}

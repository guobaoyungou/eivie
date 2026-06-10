<?php
declare(strict_types=1);

namespace app\command;

use app\service\ImagePersistService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Db;
use think\facade\Log;

/**
 * 批量回填合成结果图片URL：将临时API地址替换为WebP持久化地址
 * 
 * 用法：
 *   php think backfill_result_urls                  # 预览模式（仅统计，不修改）
 *   php think backfill_result_urls --fix            # 执行修复
 *   php think backfill_result_urls --fix --limit=50 # 限制处理条数
 */
class BackfillResultUrls extends Command
{
    /** 临时URL特征模式 */
    const TEMP_URL_PATTERNS = [
        'tos-cn-beijing.volces.com',  // 火山方舟 TOS
        'tos-cn-shanghai.volces.com',
        'tos-sg.volces.com',
        'aliyuncs.com/wanx',          // 阿里通义临时地址
        'baidu.com/ai-image',         // 百度AI临时地址
        'openai.com/v1/images',       // OpenAI临时地址
    ];

    protected function configure()
    {
        $this->setName('backfill_result_urls')
            ->addOption('fix', null, Option::VALUE_NONE, '执行修复（默认仅预览）')
            ->addOption('limit', null, Option::VALUE_REQUIRED, '限制处理条数', 0)
            ->setDescription('将合成结果表中的临时API图片URL替换为WebP持久化地址');
    }

    protected function execute(Input $input, Output $output)
    {
        $isFix = $input->getOption('fix');
        $limit = intval($input->getOption('limit') ?: 0);

        $output->writeln('========================================');
        $output->writeln(' 合成结果图片URL回填工具');
        $output->writeln(' 模式: ' . ($isFix ? '修复模式' : '预览模式'));
        if ($limit > 0) {
            $output->writeln(' 限制: 最多处理 ' . $limit . ' 条');
        }
        $output->writeln('========================================');
        $output->writeln('');

        // 1. 查找需要修复的记录（url 包含临时地址特征）
        $query = Db::name('ai_travel_photo_result')
            ->where('status', 1)
            ->where(function ($q) {
                foreach (self::TEMP_URL_PATTERNS as $pattern) {
                    $q->whereOr('url', 'like', '%' . $pattern . '%');
                }
            });

        $totalCount = (clone $query)->count();
        $output->writeln("共发现 {$totalCount} 条包含临时地址的record");

        if ($totalCount === 0) {
            $output->writeln('无需处理。');
            return 0;
        }

        // 按ID倒序，优先处理最新记录（TOS URL 较新更可能有效）
        $query->order('id', 'desc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $records = $query->select()->toArray();
        $output->writeln("本次处理: " . count($records) . " 条");
        $output->writeln('');

        // 2. 逐条处理
        $successCount = 0;
        $failCount = 0;
        $skipCount = 0;

        foreach ($records as $i => $record) {
            $oldUrl = $record['url'];
            $recordId = $record['id'];
            $idx = $i + 1;

            // 跳过已修复的（url不再包含临时特征）
            $isTemp = false;
            foreach (self::TEMP_URL_PATTERNS as $pattern) {
                if (strpos($oldUrl, $pattern) !== false) {
                    $isTemp = true;
                    break;
                }
            }
            if (!$isTemp) {
                $skipCount++;
                if (!$isFix) {
                    $output->writeln("[{$idx}/" . count($records) . "] 跳过 #{$recordId}: URL已是持久化地址");
                }
                continue;
            }

            $output->write("[{$idx}/" . count($records) . "] 处理 #{$recordId}: " . substr($oldUrl, 0, 80) . '...');

            if (!$isFix) {
                $output->writeln(' [预览模式，跳过修改]');
                $skipCount++;
                continue;
            }

            // 执行持久化
            try {
                $aid = (int)($record['aid'] ?? 0);
                $persistService = new ImagePersistService();
                $newUrl = $persistService->persistAndCompress($oldUrl, $aid, 'ai_travel_photo');

                if (!empty($newUrl) && $newUrl !== $oldUrl) {
                    // 更新数据库
                    Db::name('ai_travel_photo_result')
                        ->where('id', $recordId)
                        ->update([
                            'url' => $newUrl,
                            'update_time' => time(),
                        ]);
                    $output->writeln(' ✅ ' . substr($newUrl, 0, 80));
                    $successCount++;
                    Log::info('BackfillResultUrls: 修复成功', [
                        'record_id' => $recordId,
                        'old' => substr($oldUrl, 0, 80),
                        'new' => substr($newUrl, 0, 80),
                    ]);
                } else {
                    $output->writeln(' ⏭️ 持久化未产出新URL，保留原地址');
                    $failCount++;
                    Log::warning('BackfillResultUrls: 持久化失败，保留原URL', [
                        'record_id' => $recordId,
                        'url' => substr($oldUrl, 0, 120),
                    ]);
                }
            } catch (\Throwable $e) {
                $output->writeln(' ❌ 异常: ' . $e->getMessage());
                $failCount++;
                Log::error('BackfillResultUrls: 处理异常 - ' . $e->getMessage(), [
                    'record_id' => $recordId,
                    'url' => substr($oldUrl, 0, 120),
                ]);
            }
        }

        // 3. 输出统计
        $output->writeln('');
        $output->writeln('========================================');
        $output->writeln(' 回填完成');
        $output->writeln(" 成功: {$successCount}");
        $output->writeln(" 失败: {$failCount}");
        $output->writeln(" 跳过: {$skipCount}");
        $output->writeln(" 总计: " . count($records));
        $output->writeln('========================================');

        // 同步修复 qrcode 表（选片记录中的图片URL也需要更新）
        if ($isFix && $successCount > 0) {
            $output->writeln('');
            $output->writeln('提示：部分选片记录(qrcode表)可能也引用了临时URL，');
            $output->writeln('建议在生成新的选片记录时会自动使用持久化URL。');
        }

        return 0;
    }
}

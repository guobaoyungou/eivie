<?php
/**
 * 人像自然语言描述服务
 * 调用豆包视觉模型 doubao-seed-2-0-mini-260428 对上传的人像照片
 * 进行完整的人物特征自然语言描述。
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class PortraitDescriptionService
{
    /**
     * 状态常量
     */
    const STATUS_PENDING    = 0; // 未生成
    const STATUS_PROCESSING = 1; // 生成中
    const STATUS_SUCCESS    = 2; // 已生成
    const STATUS_FAILED     = 3; // 失败

    /**
     * 默认 Prompt 模板：要求模型详细描述图中人物的所有可观察特征
     */
    const PROMPT = <<<'EOT'
请详细描述图中人物的所有可观察特征，以自然流畅的中文段落形式输出（200字以内）。

必须覆盖以下方面（如不可见则标注"未见"或"不可见"，不要编造）：
1. 性别与年龄区间
2. 脸型
3. 眉眼细节（眉形、眼型）
4. 唇妆/唇色
5. 胡子（有无及类型）
6. 发型与发色
7. 配饰（眼镜、耳环、项链、帽子等）
8. 上衣款式与颜色
9. 下装款式与颜色
10. 肢体动作
11. 面部表情

请严格基于图片可见内容描述，不添加主观评价。
EOT;

    /**
     * 生成单条人像的自然语言描述
     *
     * @param int $portraitId 人像记录ID
     * @return array ['status'=>true/false, 'description'=>'...', 'msg'=>'...']
     */
    public function generateDescription(int $portraitId): array
    {
        // 读取人像记录（含当前 NL 状态）
        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
        if (!$portrait) {
            return ['status' => false, 'msg' => "人像记录不存在: id={$portraitId}"];
        }

        $originalUrl = $portrait['original_url'] ?? '';
        if (empty($originalUrl)) {
            return ['status' => false, 'msg' => "人像无原始图片: id={$portraitId}"];
        }

        // 防重入：已生成成功则跳过
        $nlStatus = (int)($portrait['nl_description_status'] ?? 0);
        if ($nlStatus === self::STATUS_SUCCESS) {
            $nlDesc = $portrait['nl_description'] ?? '';
            if (!empty(trim($nlDesc))) {
                Log::info("PortraitDescription: 已有NL描述，跳过生成 portrait_id={$portraitId}");
                return ['status' => true, 'description' => $nlDesc, 'msg' => '已有描述，跳过'];
            }
        }

        // 防重入：正在生成中则跳过（避免并发）
        if ($nlStatus === self::STATUS_PROCESSING) {
            Log::info("PortraitDescription: NL描述正在生成中，跳过 portrait_id={$portraitId}");
            return ['status' => false, 'msg' => '正在生成中，跳过'];
        }

        // 标记为生成中
        Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
            'nl_description_status' => self::STATUS_PROCESSING,
            'update_time' => time(),
        ]);

        try {
            // 构建 Prompt（可附加已有的标签信息作为上下文）
            $genderTag = $portrait['gender_tag'] ?? '';
            $ageTag = $portrait['age_tag'] ?? '';
            $prompt = self::PROMPT;
            if (!empty($genderTag) || !empty($ageTag)) {
                $contextParts = [];
                if (!empty($genderTag)) {
                    $genderLabel = ($genderTag === 'Male') ? '男性' : (($genderTag === 'Female') ? '女性' : $genderTag);
                    $contextParts[] = "已知参考信息：模型判定此人像为{$genderLabel}";
                }
                if (!empty($ageTag)) {
                    $contextParts[] = "年龄标签为\"{$ageTag}\"";
                }
                if (!empty($contextParts)) {
                    $prompt = implode('，', $contextParts) . "。请注意校验，如有偏差请以图片实际内容为准。\n\n" . $prompt;
                }
            }

            // 调用豆包视觉模型
            $llm = new CloudLLMService();
            $result = $llm->describeImage($originalUrl, $prompt, [
                'temperature' => 0.3,
                'max_tokens'  => 1024,
                'timeout'     => 90,
            ]);

            if (($result['status'] ?? 0) !== 1) {
                $errorMsg = $result['msg'] ?? '未知错误';
                Log::error("PortraitDescription: 生成失败 portrait_id={$portraitId}, error={$errorMsg}");
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'nl_description_status' => self::STATUS_FAILED,
                    'update_time' => time(),
                ]);
                return ['status' => false, 'msg' => $errorMsg];
            }

            $description = $result['content'] ?? '';

            // 从 NL 描述正则提取全量人物标签（替代 InsightFace 图像识别）
            $tags = ImageAnalysisService::extractTagsFromDescription($description);
            $updateData = array_merge([
                'nl_description'        => $description,
                'nl_description_status' => self::STATUS_SUCCESS,
                'nl_description_time'   => time(),
                'update_time'           => time(),
            ], $tags);

            // 写入数据库
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update($updateData);

            Log::info("PortraitDescription: 生成成功 portrait_id={$portraitId}, len=" . mb_strlen($description), [
                'extracted_gender'   => $tags['gender_tag'] ?? '',
                'extracted_age'      => $tags['age_tag'] ?? '',
                'extracted_emotion'  => $tags['emotion_primary'] ?? '',
                'extracted_glasses'  => $tags['glasses_type'] ?? '',
                'extracted_beard'    => $tags['has_beard'] ?? 0,
                'extracted_hair'     => $tags['hair_length'] ?? '',
            ]);
            return ['status' => true, 'description' => $description];

        } catch (\Exception $e) {
            Log::error("PortraitDescription: 生成异常 portrait_id={$portraitId}, error={$e->getMessage()}");
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'nl_description_status' => self::STATUS_FAILED,
                'update_time' => time(),
            ]);
            return ['status' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 批量生成描述（用于回填命令）
     *
     * @param array      $portraitIds 人像ID数组
     * @param callable|null $onProgress 进度回调 function(int $current, int $total, int $id, bool $success)
     * @return array ['total'=>..., 'success'=>..., 'skip'=>..., 'failed'=>...]
     */
    public function batchGenerate(array $portraitIds, ?callable $onProgress = null): array
    {
        $total = count($portraitIds);
        $successCount = 0;
        $skipCount = 0;
        $failedCount = 0;

        foreach ($portraitIds as $index => $portraitId) {
            // 检查是否已有描述（已生成成功则跳过）
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->field('id,nl_description_status,original_url')
                ->find();

            if (!$portrait || empty($portrait['original_url'])) {
                $skipCount++;
                if ($onProgress) {
                    $onProgress($index + 1, $total, $portraitId, false, '无图片跳过');
                }
                continue;
            }

            $result = $this->generateDescription($portraitId);
            if ($result['status']) {
                $successCount++;
            } else {
                $failedCount++;
            }

            if ($onProgress) {
                $onProgress($index + 1, $total, $portraitId, $result['status'], $result['msg'] ?? '');
            }
        }

        return [
            'total'   => $total,
            'success' => $successCount,
            'skip'    => $skipCount,
            'failed'  => $failedCount,
        ];
    }

    /**
     * 检查商户是否开启了自然语言描述功能
     * 改为始终返回 true（顺序流水线模式下 NL 描述为必需步骤）
     *
     * @param int $aid 商户ID
     * @param int $bid 门店ID
     * @return bool
     */
    public static function isEnabled(int $aid, int $bid): bool
    {
        return true; // 顺序流水线模式始终需要 NL 描述
    }
}

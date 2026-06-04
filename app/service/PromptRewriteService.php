<?php
/**
 * AI提示词改写服务
 * 根据人像标签（性别、年龄、单人/多人）改写合成提示词，使生成结果更符合人像特征
 */
namespace app\service;

use think\facade\Log;

class PromptRewriteService
{
    /** @var CloudLLMService */
    protected $llm;

    public function __construct()
    {
        $this->llm = new CloudLLMService();
    }

    /**
     * 改写提示词
     *
     * @param string $originalPrompt 原始提示词
     * @param array $portraitTags 人像标签 ['gender'=>'Male', 'age'=>'中年主力', 'is_multi'=>0, 'face_count'=>1]
     * @param string $provider LLM供应商 (aliyun|volcengine)
     * @param string $model LLM模型名
     * @return string 改写后的提示词（失败时返回原提示词）
     */
    public function rewrite(
        string $originalPrompt,
        array $portraitTags,
        string $provider = 'aliyun',
        string $model = 'qwen-plus'
    ): string {
        if (empty(trim($originalPrompt))) {
            return $originalPrompt;
        }

        // 构建人设描述
        $personaDesc = $this->buildPersonaDescription($portraitTags);
        if (empty($personaDesc)) {
            // 标签信息不足，跳过改写
            Log::info('PromptRewrite: 标签信息不足，跳过改写');
            return $originalPrompt;
        }

        // 构建改写指令
        $systemPrompt = $this->buildSystemPrompt($personaDesc);
        $userMessage = $this->buildUserMessage($originalPrompt);

        try {
            $result = $this->llm->sendMessage(
                [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                [
                    'provider' => $provider,
                    'model' => $model,
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]
            );

            if (($result['status'] ?? 0) == 1 && !empty($result['message']['content'])) {
                $rewritten = trim($result['message']['content']);
                // 清理可能的引号包裹
                $rewritten = preg_replace('/^["\']|["\']$/u', '', $rewritten);
                if (!empty($rewritten) && $rewritten !== $originalPrompt) {
                    Log::info('PromptRewrite 成功', [
                        'original' => mb_substr($originalPrompt, 0, 80),
                        'rewritten' => mb_substr($rewritten, 0, 80),
                        'provider' => $provider,
                        'model' => $model,
                    ]);
                    return $rewritten;
                }
            }

            Log::warning('PromptRewrite 返回无效结果，使用原提示词', [
                'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
            return $originalPrompt;
        } catch (\Throwable $e) {
            Log::error('PromptRewrite 异常: ' . $e->getMessage());
            return $originalPrompt;
        }
    }

    /**
     * 根据人像标签构建人物描述
     */
    protected function buildPersonaDescription(array $tags): string
    {
        $parts = [];

        $gender = $tags['gender'] ?? '';
        $age = $tags['age'] ?? '';
        $isMulti = intval($tags['is_multi'] ?? 0);
        $faceCount = intval($tags['face_count'] ?? 0);

        // 性别
        $genderMap = [
            'Male' => '男性', 'Female' => '女性',
            '男' => '男性', '女' => '女性',
            '男性' => '男性', '女性' => '女性',
        ];
        $genderText = $genderMap[$gender] ?? '';
        if ($genderText) {
            $parts[] = $genderText;
        }

        // 年龄（中文描述直接使用）
        if (!empty($age) && $age !== '-' && $age !== 'Unknown' && $age !== '未知') {
            $parts[] = $age;
        }

        // 单人/多人
        if ($isMulti && $faceCount > 1) {
            $parts[] = "{$faceCount}人合影";
        } elseif (!$isMulti && $faceCount <= 1) {
            $parts[] = "单人照";
        }

        return implode('，', $parts);
    }

    /**
     * 构建系统提示词
     */
    protected function buildSystemPrompt(string $personaDesc): string
    {
        return "你是一个专业的AI图像提示词优化专家。用户会给出一条图像生成的原始提示词，以及照片中的人物特征描述。"
            . "你需要根据人物特征改写提示词，使生成的图像更符合该人物的人设和气质。\n\n"
            . "人物特征：{$personaDesc}\n\n"
            . "改写规则：\n"
            . "1. 保持原始提示词的核心场景、风格和构图不变\n"
            . "2. 根据人物的性别、年龄和人数，调整关于人物外貌、姿态、服饰、表情等的描述\n"
            . "3. 年龄对应穿着风格：青少年→青春活力，职场青年→职业干练，中年→成熟稳重，老年→优雅从容\n"
            . "4. 多人合影时注意人物之间的互动和协调\n"
            . "5. 输出仅包含改写后的完整提示词，不要加引号或任何解释\n"
            . "6. 提示词使用中文描述，简洁精炼，控制在200字以内";
    }

    /**
     * 构建用户消息
     */
    protected function buildUserMessage(string $originalPrompt): string
    {
        return "原始提示词：{$originalPrompt}\n\n请根据上述规则改写这条提示词。";
    }
}
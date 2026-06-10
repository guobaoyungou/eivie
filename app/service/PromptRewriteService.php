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
     * @param string $rewriteTemplate 可配置的改写模板（支持{变量名}占位符）
     * @param string $templateModel 模板绑定的模型名称
     * @param string $nlDescription 人像NL描述（用于{人像描述}变量）
     * @return string 改写后的提示词（失败时返回原提示词）
     */
    public function rewrite(
        string $originalPrompt,
        array $portraitTags,
        string $provider = 'aliyun',
        string $model = 'qwen-plus',
        string $rewriteTemplate = '',
        string $templateModel = '',
        string $nlDescription = ''
    ): string {
        if (empty(trim($originalPrompt))) {
            return $originalPrompt;
        }

        // 构建人设描述
        $personaDesc = $this->buildPersonaDescription($portraitTags);
        
        // 获取性别和年龄的原始标签值
        $gender = $portraitTags['gender'] ?? '';
        $age = $portraitTags['age'] ?? '';
        
        $genderMap = [
            'Male' => '男性', 'Female' => '女性',
            '男' => '男性', '女' => '女性',
            '男性' => '男性', '女性' => '女性',
        ];
        $genderText = $genderMap[$gender] ?? $gender;

        // 构建改写指令
        $systemPrompt = $this->buildSystemPrompt($personaDesc, $rewriteTemplate, $genderText, $age, $templateModel, $nlDescription, $originalPrompt);
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
        if (!empty($age) && $age !== '-') {
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
     * 
     * @param string $personaDesc 人物特征描述
     * @param string $rewriteTemplate 可配置的改写模板
     * @param string $genderText 性别中文描述
     * @param string $ageText 年龄描述
     * @param string $templateModel 模板绑定模型名
     * @param string $nlDescription 人像NL描述（用于{人像描述}变量）
     * @param string $templatePrompt 模板原始提示词（用于{模板提示词}变量）
     */
    protected function buildSystemPrompt(
        string $personaDesc,
        string $rewriteTemplate = '',
        string $genderText = '',
        string $ageText = '',
        string $templateModel = '',
        string $nlDescription = '',
        string $templatePrompt = ''
    ): string {
        // 如果有配置的改写模板，使用配置模板
        if (!empty($rewriteTemplate)) {
            $variables = [
                '自动标签性别' => $genderText ?: '',
                '自动标签年龄' => $ageText ?: '',
                '模板绑定模型' => $templateModel ?: '未指定',
                '人像描述' => $nlDescription ?: '',
                '模板提示词' => $templatePrompt ?: '',
            ];
            $template = $this->resolveVariables($rewriteTemplate, $variables);
            
            return "你是一个专业的AI图像提示词优化专家。\n\n"
                . "人物特征：{$personaDesc}\n\n"
                . "请按以下指令改写提示词：{$template}\n\n"
                . "改写规则：\n"
                . "1. 保持原始提示词的核心场景、风格和构图不变\n"
                . "2. 根据人物的性别、年龄和人数，调整关于人物外貌、姿态、服饰、表情等的描述\n"
                . "3. 输出仅包含改写后的完整提示词，不要加引号或任何解释\n"
                . "4. 提示词使用中文描述，简洁精炼，控制在200字以内";
        }

        // 默认行为：使用硬编码模板（向后兼容）
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
     * 解析模板中的系统变量占位符
     * 
     * @param string $template 含{变量名}占位符的模板
     * @param array $variables 变量名=>值 的映射
     * @return string 替换后的模板
     */
    protected function resolveVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    /**
     * 构建用户消息
     */
    protected function buildUserMessage(string $originalPrompt): string
    {
        return "原始提示词：{$originalPrompt}\n\n请根据上述规则改写这条提示词。";
    }

    /**
     * 基于 NL 描述的提示词改写（调用LLM，解析JSON返回）
     * LLM 输出JSON: {"feature_info":"...", "optimize_prompt":"..."}
     *
     * @param string $originalPrompt 原始模板提示词（替换模板中的{模板提示词}变量）
     * @param string $nlDescription  人像NL描述文本
     * @param string $rewriteTemplate 改写指令模板（特征提取部分，支持{模板提示词}{人像描述}变量）
     * @param string $provider        LLM供应商
     * @param string $model           LLM模型名
     * @param string $promptRewriteTemplate 提示词优化模板（支持{模板提示词}{人像描述}{模板绑定模型}变量）
     * @param string $templateModel   模板绑定模型名称（用于{模板绑定模型}变量）
     * @return array ['optimize_prompt'=>'...', 'feature_info'=>'...', 'success'=>bool]
     */
    public function rewriteWithDescription(
        string $originalPrompt,
        string $nlDescription,
        string $rewriteTemplate,
        string $provider = 'volcengine',
        string $model = 'doubao-seed-2-0-pro-260215',
        string $promptRewriteTemplate = '',
        string $templateModel = ''
    ): array {
        if (empty(trim($nlDescription)) || empty(trim($originalPrompt))) {
            Log::warning('rewriteWithDescription: 缺少必要参数', [
                'has_desc' => !empty(trim($nlDescription)),
                'has_prompt' => !empty(trim($originalPrompt)),
            ]);
            return ['success' => false, 'feature_info' => '', 'optimize_prompt' => ''];
        }

        // 替换 {模板提示词} 和 {人像描述} 系统变量（特征提取模板）
        $resolvedNlTemplate = $this->resolveVariables($rewriteTemplate, [
            '模板提示词' => $originalPrompt,
            '人像描述' => $nlDescription,
        ]);

        // 构建完整系统提示词：特征提取 + 提示词优化
        // 从 NL 描述预提取性别信息，用于强化改写指令
        $extractedGender = '';
        $extractedAge = '';
        if (preg_match('/男(?:性)?/u', $nlDescription) || preg_match('/\bMale\b/ui', $nlDescription)) {
            $extractedGender = '男性';
        } elseif (preg_match('/女(?:性)?/u', $nlDescription) || preg_match('/\bFemale\b/ui', $nlDescription)) {
            $extractedGender = '女性';
        }
        // 用 ImageAnalysisService 提取年龄标签
        if (class_exists('\\app\\service\\ImageAnalysisService')) {
            $ageResult = \app\service\ImageAnalysisService::extractGenderAgeFromDescription($nlDescription);
            $extractedAge = $ageResult['age_tag'] ?? '';
        }

        $genderHint = '';
        if (!empty($extractedGender)) {
            $genderHint = "⚠️ 已识别人物性别为：{$extractedGender}";
            if (!empty($extractedAge)) {
                $genderHint .= "，年龄段：{$extractedAge}";
            }
            $genderHint .= "。改写提示词时的人物着装风格、发型、配饰必须与此性别和年龄匹配！\n";
        }

        $systemPrompt = "你是一个专业的AI图像提示词优化专家。\n\n"
            . "人物特征描述（参考以下信息）：\n"
            . "{$nlDescription}\n\n"
            . "{$genderHint}\n"
            . "原始模板提示词（必须改写此文本）：\n"
            . "【{$originalPrompt}】\n\n"
            . "重要约束（必须严格遵守）：\n"
            . "1. 从人物特征描述中提取人物的【性别】和【年龄段】\n"
            . "2. 检查原始模板提示词中的服装、发型、妆容、配饰描述是否与人物实际性别一致\n"
            . "3. 若不一致（如原提示词描述「女性长裙」「淑女」而人物实际为男性），必须改写为符合实际性别的对应描述\n"
            . "4. optimize_prompt 中的人物着装、发型、配饰、妆容必须与人物实际性别和年龄严格匹配\n"
            . "5. optimize_prompt 必须是对原始模板提示词的实质性改写，禁止原样复制或只改个别字词\n"
            . "6. 禁止在 optimize_prompt 中包含与实际性别相反的服装、发型、妆容描述\n\n"
            . "请严格按照以下两步指令执行：\n\n"
            . "【第1步：提取特征】\n"
            . "{$resolvedNlTemplate}\n\n";

        // 如果有提示词改写模板，追加第2步
        if (!empty(trim($promptRewriteTemplate))) {
            $resolvedRewriteTemplate = $this->resolveVariables($promptRewriteTemplate, [
                '模板提示词' => $originalPrompt,
                '人像描述' => $nlDescription,
                '模板绑定模型' => $templateModel ?: '未指定',
            ]);
            // 检查改写模板是否包含 {模板提示词}，若不含则自动补充
            if (strpos($promptRewriteTemplate, '{模板提示词}') === false && strpos($promptRewriteTemplate, '模板提示词') === false) {
                $resolvedRewriteTemplate = "原始模板提示词：【{$originalPrompt}】\n" . $resolvedRewriteTemplate;
            }
            $systemPrompt .= "【第2步：优化提示词】\n"
                . "{$resolvedRewriteTemplate}\n\n";
        }

        $systemPrompt .= "重要提醒：\n"
            . "1. 必须输出标准JSON格式，不要添加任何markdown代码块标记\n"
            . "2. feature_info 为提取的人像特征描述，optimize_prompt 为优化后的绘图提示词\n"
            . "3. optimize_prompt 用词专业、细节饱满，适配写实人像生成\n"
            . "4. 不要添加任何JSON之外的文字说明\n"
            . "5. optimize_prompt 必须与原始模板提示词有实质性差异，体现性别和年龄适配";

        try {
            $result = $this->llm->sendMessage(
                [
                    ['role' => 'system', 'content' => $systemPrompt],
                ],
                [
                    'provider' => $provider,
                    'model' => $model,
                    'temperature' => 0.3,
                    'max_tokens' => 2048,
                    'timeout' => 120,
                ]
            );

            if (($result['status'] ?? 0) != 1) {
                $errorMsg = $result['msg'] ?? 'LLM调用失败';
                Log::error('rewriteWithDescription LLM调用失败: ' . $errorMsg);
                return ['success' => false, 'feature_info' => '', 'optimize_prompt' => ''];
            }

            $content = $result['message']['content'] ?? '';
            if (empty($content)) {
                Log::error('rewriteWithDescription LLM返回空内容');
                return ['success' => false, 'feature_info' => '', 'optimize_prompt' => ''];
            }

            // 解析JSON（去除可能的 markdown 代码块包裹）
            $cleaned = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $content));
            $parsed = json_decode($cleaned, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // 尝试提取JSON片段
                if (preg_match('/\{[^{}]*"feature_info"[^{}]*"optimize_prompt"[^{}]*\}/s', $cleaned, $m)) {
                    $parsed = json_decode($m[0], true);
                }
            }

            $featureInfo = trim($parsed['feature_info'] ?? '');
            $optimizePrompt = trim($parsed['optimize_prompt'] ?? '');

            if (empty($optimizePrompt)) {
                Log::warning('rewriteWithDescription JSON解析后无optimize_prompt', [
                    'raw' => mb_substr($content, 0, 400),
                    'parsed' => json_encode($parsed, JSON_UNESCAPED_UNICODE),
                ]);
                return ['success' => false, 'feature_info' => $featureInfo, 'optimize_prompt' => ''];
            }

            Log::info('rewriteWithDescription 成功', [
                'feature_info_len' => mb_strlen($featureInfo),
                'original_len' => mb_strlen($originalPrompt),
                'optimize_len' => mb_strlen($optimizePrompt),
                'original_preview' => mb_substr($originalPrompt, 0, 100),
                'optimize_preview' => mb_substr($optimizePrompt, 0, 100),
                'extracted_gender' => $extractedGender,
                'extracted_age' => $extractedAge,
                'is_different' => ($originalPrompt !== $optimizePrompt) ? 'yes' : 'NO_CHANGE',
            ]);

            return [
                'success' => true,
                'feature_info' => $featureInfo,
                'optimize_prompt' => $optimizePrompt,
            ];

        } catch (\Throwable $e) {
            Log::error('rewriteWithDescription 异常: ' . $e->getMessage());
            return ['success' => false, 'feature_info' => '', 'optimize_prompt' => ''];
        }
    }
}
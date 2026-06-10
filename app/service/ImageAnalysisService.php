<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;

/**
 * 图片人物属性分析服务
 *
 * 封装对 InsightFace+FairFace 一体化 API 的 /api/analyze 端点调用，
 * 返回图中所有人脸的性别、年龄段、人种、多人判断等信息。
 *
 * Class ImageAnalysisService
 * @package app\service
 */
class ImageAnalysisService
{
    /** 性别：永远选择置信度更高的性别（Male/Female），不再使用阈值过滤 */
    const MIN_GENDER_CONFIDENCE = -1.0;
    /** 年龄置信度阈值：使用检测置信度，-1.0 表示不进行阈值过滤 */
    const MIN_AGE_CONFIDENCE = -1.0;

    private $serviceHost;
    private $servicePort;
    private $timeout;

    public function __construct()
    {
        $this->serviceHost = env('insightface.host', '127.0.0.1');
        $this->servicePort = (int)env('insightface.port', 8867);
        $this->timeout = 30;
    }

    private function getBaseUrl(): string
    {
        return "http://{$this->serviceHost}:{$this->servicePort}";
    }

    /**
     * 从远程图片 URL 分析人物属性
     *
     * @param string $imageUrl  图片 OSS/CDN URL
     * @param bool   $detectBodyType 是否检测体型（默认关闭，减少开销）
     * @return array|null 成功返回 AnalyzeResponse 结构；失败返回 null
     */
    public function analyzeFromUrl(string $imageUrl, bool $detectBodyType = false): ?array
    {
        return $this->doAnalyze(['image_url' => $imageUrl, 'detect_body_type' => $detectBodyType]);
    }

    /**
     * 从 Base64 图片分析人物属性
     */
    public function analyzeFromBase64(string $base64Data, bool $detectBodyType = false): ?array
    {
        return $this->doAnalyze(['image_base64' => $base64Data, 'detect_body_type' => $detectBodyType]);
    }

    private function doAnalyze(array $requestData): ?array
    {
        try {
            $result = $this->callApi('/api/analyze', $requestData);

            if (empty($result) || ($result['status'] ?? '') !== 'success') {
                Log::warning('ImageAnalysis analyze 返回异常', ['result' => $result]);
                return null;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('ImageAnalysis API 调用失败', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callApi(string $path, array $data): array
    {
        $url = $this->getBaseUrl() . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('ImageAnalysis API 请求失败: ' . $error);
        }

        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = $decoded['detail'] ?? "HTTP $httpCode";
            throw new \Exception('ImageAnalysis API 错误: ' . $msg);
        }

        return $decoded ?: [];
    }

    /**
     * 从分析结果中提取主体人物属性（按人脸面积最大选取）
     *
     * 性别：永远选置信度更高的 Male/Female，不输出 "Unknown"
     * 年龄：基于精确浮点年龄映射到 25 段精细化区间
     * 人种：直接使用 FairFace 分类结果（无置信度过滤）
     *
     * @param array $analyzeResult /api/analyze 返回的完整结果
     * @param float|null $minGenderConfidence 性别置信度阈值（null=不进行阈值过滤，永远二选一）
     * @param float|null $minAgeConfidence 年龄置信度阈值（null=不进行阈值过滤）
     * @return array
     */
    public static function extractMainSubject(
        array $analyzeResult,
        ?float $minGenderConfidence = null,
        ?float $minAgeConfidence = null
    ): array {
        $faces = $analyzeResult['faces'] ?? [];
        $faceCount = count($faces);

        // 无人脸时的默认值
        $default = [
            'gender' => '',
            'age_group' => '',
            'is_multi_face' => $faceCount > 1,
            'face_count' => $faceCount,
            'gender_confidence' => 0.0,
            'age_confidence' => 0.0,
            'race' => '',
            'race_confidence' => 0.0,
            'raw_gender' => '',
            'raw_age_group' => '',
            'is_low_confidence' => true,
            'age' => null,
            'age_lower' => null,
            'age_upper' => null,
            'gender_model' => null,
        ];

        if ($faceCount === 0) {
            return $default;
        }

        // 按 bbox_area 降序，取最大人脸作为主体
        usort($faces, fn($a, $b) => ($b['bbox_area'] ?? 0) <=> ($a['bbox_area'] ?? 0));
        $main = $faces[0];

        // 原始值（API 直接返回）
        $rawGender = ucfirst(strtolower($main['gender'] ?? ''));
        $rawAgeGroup = $main['age_group'] ?? '';
        $genderConf = round($main['gender_confidence'] ?? 0, 4);
        $ageConf = round($main['age_confidence'] ?? 0, 4);

        // --- 精确浮点年龄字段（InsightFace buffalo_l） ---
        $preciseAge = isset($main['age']) ? round(floatval($main['age']), 2) : null;
        $ageLower = isset($main['age_lower']) ? round(floatval($main['age_lower']), 2) : null;
        $ageUpper = isset($main['age_upper']) ? round(floatval($main['age_upper']), 2) : null;
        $genderModel = $main['gender_model'] ?? null;

        // --- 性别判定（信任模型 argmax 预测，不翻转） ---
        // InsightFace buffalo_l genderage.onnx 输出二分类 softmax，最低 0.5
        // 永远选择置信度更高的性别，不进行翻转
        $finalGender = in_array($rawGender, ['Male', 'Female']) ? $rawGender : 'Male';

        // --- 年龄：基于精确浮点年龄映射到 25 段区间 ---
        $finalAgeGroup = '';
        if ($preciseAge !== null) {
            $finalAgeGroup = self::mapAgeToPreciseRange($preciseAge);
        }
        // 无精确年龄时使用旧 age_group 映射（向后兼容）
        if (empty($finalAgeGroup) && !empty($rawAgeGroup) && $rawAgeGroup !== 'Unknown') {
            $finalAgeGroup = $rawAgeGroup;
        }

        // 低置信度标记：人脸检测到但性别/年龄数据质量可疑
        $isLowConf = ($faceCount > 0) && (($genderConf < 0.3) || ($preciseAge === null && empty($finalAgeGroup)));

        return [
            'gender' => $finalGender,
            'age_group' => $finalAgeGroup,
            'is_multi_face' => $faceCount > 1,
            'face_count' => $faceCount,
            'gender_confidence' => $genderConf,
            'age_confidence' => $ageConf,
            'race' => ucwords(strtolower($main['race'] ?? '')),
            'race_confidence' => round($main['race_confidence'] ?? 0, 4),
            'raw_gender' => $rawGender,
            'raw_age_group' => $rawAgeGroup,
            'is_low_confidence' => $isLowConf,
            // 精确年龄字段
            'age' => $preciseAge,
            'age_lower' => $ageLower,
            'age_upper' => $ageUpper,
            'gender_model' => $genderModel,
        ];
    }

    /**
     * 将精确浮点年龄映射到 25 段精细化年龄区间
     *
     * 区间覆盖 0.0～100.0 岁，线性查找 O(25)。
     * 标签与 config/auto_tagging.php 的 precise_age_ranges 保持一致。
     *
     * @param float $age 精确浮点年龄
     * @return string 年龄区间标签，如 "职场青年"，未匹配返回空字符串
     */
    public static function mapAgeToPreciseRange(float $age): string
    {
        $ranges = [
            ['min' => 0.0,  'max' => 0.99,  'label' => '新生儿婴儿'],
            ['min' => 1.0,  'max' => 1.99,  'label' => '学步期'],
            ['min' => 2.0,  'max' => 2.99,  'label' => '低龄幼童'],
            ['min' => 3.0,  'max' => 3.99,  'label' => '小班幼儿'],
            ['min' => 4.0,  'max' => 4.99,  'label' => '中班幼儿'],
            ['min' => 5.0,  'max' => 5.99,  'label' => '大班幼儿'],
            ['min' => 6.0,  'max' => 6.99,  'label' => '学前儿童'],
            ['min' => 7.0,  'max' => 9.99,   'label' => '小学低龄'],
            ['min' => 10.0, 'max' => 12.99,  'label' => '小学高龄'],
            ['min' => 13.0, 'max' => 15.99,  'label' => '初中少年'],
            ['min' => 16.0, 'max' => 17.99,  'label' => '高中青年'],
            ['min' => 18.0, 'max' => 22.99,  'label' => '校园青年'],
            ['min' => 23.0, 'max' => 25.99,  'label' => '初入职场'],
            ['min' => 26.0, 'max' => 29.99,  'label' => '职场青年'],
            ['min' => 30.0, 'max' => 35.99,  'label' => '而立青年'],
            ['min' => 36.0, 'max' => 40.99,  'label' => '青中年'],
            ['min' => 41.0, 'max' => 45.99,  'label' => '壮年期'],
            ['min' => 46.0, 'max' => 50.99,  'label' => '中年主力'],
            ['min' => 51.0, 'max' => 55.99,  'label' => '中老年前期'],
            ['min' => 56.0, 'max' => 60.99,  'label' => '准老年前期'],
            ['min' => 61.0, 'max' => 65.99,  'label' => '低龄活力老人'],
            ['min' => 66.0, 'max' => 70.99,  'label' => '健康老人'],
            ['min' => 71.0, 'max' => 75.99,  'label' => '中年老人'],
            ['min' => 76.0, 'max' => 80.99,  'label' => '高龄老人'],
            ['min' => 81.0, 'max' => 85.99,  'label' => '超高龄老人'],
            ['min' => 86.0, 'max' => 100.0,  'label' => '长寿老人'],
        ];

        foreach ($ranges as $range) {
            if ($age >= $range['min'] && $age <= $range['max']) {
                return $range['label'];
            }
        }

        // 未匹配：超限但合理处理
        if ($age > 100.0) {
            return '86～100 岁';
        }
        if ($age < 0.0) {
            return '0～0.9 岁 新生儿婴儿';
        }

        return '';
    }

    /**
     * 从 NL 自然语言描述文本中提取性别和年龄标签
     *
     * NL 描述由豆包视觉模型生成，格式如：
     * "男性，中年主力，圆脸型，浓眉大眼，短发，白色T恤..."
     * 第一句天然包含性别和年龄段信息，比 InsightFace 图像识别更准确。
     *
     * @param string $nlDescription NL 自然语言描述文本
     * @return array ['gender' => 'Male'|'Female'|'', 'age_tag' => '中年主力'|'', 'precise_age_label' => '中年主力'|'']
     */
    public static function extractGenderAgeFromDescription(string $nlDescription): array
    {
        $gender = '';
        $ageTag = '';

        if (empty(trim($nlDescription))) {
            return ['gender' => '', 'age_tag' => '', 'precise_age_label' => ''];
        }

        // --- 性别提取：匹配 男/女/Male/Female ---
        // 注意：\b 在 PCRE 中不支持中文字符（中文字符属于 \W），需中英文分开匹配
        if (preg_match('/男(?:性)?/u', $nlDescription) || preg_match('/\bMale\b/ui', $nlDescription)) {
            $gender = 'Male';
        } elseif (preg_match('/女(?:性)?/u', $nlDescription) || preg_match('/\bFemale\b/ui', $nlDescription)) {
            $gender = 'Female';
        }

        // --- 年龄标签提取：与 precise_age_ranges 的 25 段标签逐一匹配 ---
        $ageLabels = [
            '新生儿婴儿', '学步期', '低龄幼童', '小班幼儿', '中班幼儿', '大班幼儿',
            '学前儿童', '小学低龄', '小学高龄',
            '初中少年', '高中青年', '校园青年', '初入职场', '职场青年',
            '而立青年', '青中年', '壮年期', '中年主力',
            '中老年前期', '准老年前期',
            '低龄活力老人', '健康老人', '中年老人', '高龄老人', '超高龄老人', '长寿老人',
        ];

        foreach ($ageLabels as $label) {
            if (mb_strpos($nlDescription, $label) !== false) {
                $ageTag = $label;
                break; // 取第一个匹配的年龄标签
            }
        }

        return [
            'gender'            => $gender,
            'age_tag'           => $ageTag,
            'precise_age_label' => $ageTag,
        ];
    }

    /**
     * 从 NL 自然语言描述中提取全量人物标签
     *
     * 基于 LLM 生成的 NL 描述固定格式进行正则提取，覆盖性别、年龄、表情、
     * 眼镜类型、眼皮类型、眉形、眉浓淡、胡子、发型发色、脸型、配饰等维度。
     * 替代 InsightFace Python 服务的图像分析，避免外部服务依赖。
     *
     * @param string $nlDescription NL 自然语言描述文本
     * @return array 可直接 merge 到 portrait update 的字段数组，包含 auto_tag_status/auto_tag_time
     */
    public static function extractTagsFromDescription(string $nlDescription): array
    {
        $defaults = [
            'gender_tag'         => '',
            'age_tag'            => '',
            'emotion_primary'    => '',
            'glasses_type'       => '',
            'eyelid_type'        => '',
            'eyebrow_shape'      => '',
            'eyebrow_thickness'  => '',
            'has_beard'          => 0,
            'hair_length'        => '',
            'hair_color'         => '',
            'face_shape'         => '',
            'has_accessory'      => 0,
            'is_multi_face'      => 0,
            'face_count'         => 1,
            'auto_tag_status'    => 2,
            'auto_tag_time'      => time(),
        ];

        if (empty(trim($nlDescription))) {
            $defaults['auto_tag_status'] = 0;
            return $defaults;
        }

        // ========== 1. 性别提取 ==========
        if (preg_match('/男(?:性)?/u', $nlDescription) || preg_match('/\bMale\b/ui', $nlDescription)) {
            $defaults['gender_tag'] = 'Male';
        } elseif (preg_match('/女(?:性)?/u', $nlDescription) || preg_match('/\bFemale\b/ui', $nlDescription)) {
            $defaults['gender_tag'] = 'Female';
        }

        // ========== 2. 年龄提取：取第一个和第二个分隔符之间的文本 ==========
        // NL 描述格式：[0]性别描述，[1]年龄描述，[2]脸型...
        // 直接用第1段和第2段分隔符之间的原始文本作为年龄标签，不做任何映射
        $parts = preg_split('/[，,。；;]/u', $nlDescription);
        // 找第二个非空段（段0=性别，段1=年龄）
        $nonEmptyIdx = 0;
        foreach ($parts as $i => $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                if ($nonEmptyIdx === 1) {
                    $defaults['age_tag'] = $trimmed;
                    break;
                }
                $nonEmptyIdx++;
            }
        }

        // ========== 3. 表情提取 ==========
        // 多策略匹配，优先级：表情+情绪词 > 面带/露出+情绪词 > 独立情绪词
        $emotionPatterns = [
            '温和的微笑', '微笑', '平静', '伤心', '惊讶', '生气', '恐惧',
            '平和', '温和', '严肃', '放松', '自然',
        ];
        // 策略A：匹配 "表情xxx情绪词"
        foreach ($emotionPatterns as $emo) {
            if (preg_match('/表情(?:为\s*)?(?:.{0,5}?)(' . preg_quote($emo, '/') . ')/u', $nlDescription, $m)) {
                $defaults['emotion_primary'] = $m[1];
                break;
            }
        }
        // 策略B：fallback - 匹配 "面带微笑" / "露出微笑" 等
        if (empty($defaults['emotion_primary'])) {
            if (preg_match('/(?:面带|露出|浮现)(.{0,5}?)(微笑)/u', $nlDescription, $m)) {
                $defaults['emotion_primary'] = '微笑';
            }
        }
        // 策略C：最后的独立情绪词匹配
        if (empty($defaults['emotion_primary'])) {
            foreach (['微笑', '平静', '平和', '放松', '自然', '严肃'] as $emo) {
                if (mb_strpos($nlDescription, $emo) !== false) {
                    $defaults['emotion_primary'] = $emo;
                    break;
                }
            }
        }

        // ========== 4. 眼镜类型 ==========
        if (preg_match('/墨镜/u', $nlDescription)) {
            $defaults['glasses_type'] = 'sunglasses';
        } elseif (preg_match('/配饰.*?(?:普通)?眼镜/u', $nlDescription) ||
                  preg_match('/戴.*?(?:普通)?眼镜/u', $nlDescription)) {
            $defaults['glasses_type'] = 'eyeglasses';
        } elseif (preg_match('/未见.*?眼镜/u', $nlDescription)) {
            $defaults['glasses_type'] = 'none';
        }

        // ========== 5. 眼皮类型 ==========
        if (preg_match('/眼型(?:为\s*)?(.{0,2}?)(单眼皮)/u', $nlDescription)) {
            $defaults['eyelid_type'] = 'single';
        } elseif (preg_match('/眼型(?:为\s*)?(.{0,2}?)(双眼皮)/u', $nlDescription)) {
            $defaults['eyelid_type'] = 'double';
        }

        // ========== 6. 眉形 ==========
        if (preg_match('/眉形.{0,10}?(弯|curved)/ui', $nlDescription)) {
            $defaults['eyebrow_shape'] = 'curved';
        } elseif (preg_match('/眉形.{0,10}?(平直|平)/u', $nlDescription)) {
            $defaults['eyebrow_shape'] = 'flat';
        }

        // ========== 7. 眉浓淡 ==========
        if (preg_match('/眉形.{0,15}?(浓黑|浓)/u', $nlDescription)) {
            $defaults['eyebrow_thickness'] = 'thick';
        } elseif (preg_match('/眉形.{0,15}?(淡|平缓)/u', $nlDescription)) {
            $defaults['eyebrow_thickness'] = 'thin';
        }

        // ========== 8. 胡子 ==========
        if (preg_match('/未见胡子/u', $nlDescription) || preg_match('/无(?:胡须|胡子)/u', $nlDescription)) {
            $defaults['has_beard'] = 0;
        } elseif (preg_match('/有(?:短|浓密|稀疏|灰白|白色|黑色)?(?:胡须|胡子)/u', $nlDescription)) {
            $defaults['has_beard'] = 1;
        }

        // ========== 9. 发型与发色 ==========
        if (preg_match('/发型(?:为\s*)?(?:.*?)?(短发)/u', $nlDescription)) {
            $defaults['hair_length'] = 'short';
        } elseif (preg_match('/发型(?:为\s*)?(?:.*?)?(长发)/u', $nlDescription)) {
            $defaults['hair_length'] = 'long';
        }

        // 发色提取
        $hairColors = ['黑色', '棕色', '金色', '白色', '灰色', '栗色', '红色', '黄色'];
        foreach ($hairColors as $color) {
            if (preg_match('/发色.{0,10}?(' . preg_quote($color, '/') . ')/u', $nlDescription, $m)) {
                $defaults['hair_color'] = $m[1];
                break;
            }
        }
        // fallback: 如果发型描述中直接包含特定颜色词
        if (empty($defaults['hair_color'])) {
            foreach ($hairColors as $color) {
                if (preg_match('/发型(?:为\s*)?(?:.+?)(' . preg_quote($color, '/') . ')/u', $nlDescription, $m)) {
                    $defaults['hair_color'] = $m[1];
                    break;
                }
            }
        }

        // ========== 10. 脸型 ==========
        if (preg_match('/脸型(.{1,10}?)(?:，|。|；|\s|$)/u', $nlDescription, $m)) {
            $defaults['face_shape'] = trim($m[1]);
        }

        // ========== 11. 配饰 ==========
        if (preg_match('/未见(?:任何)?配饰/u', $nlDescription)) {
            $defaults['has_accessory'] = 0;
        } elseif (preg_match('/配饰[为是]?(?!.*未见).*?(耳环|项链|帽子|口罩|戒指|手镯|手链|手串)/u', $nlDescription)) {
            $defaults['has_accessory'] = 1;
        }

        return $defaults;
    }

    /**
     * 扩展人脸分析（调用 /api/analyze/extended）
     * 返回基础属性 + 表情识别 + 关键点推断结果
     *
     * @param string $imageUrl 图片URL
     * @return array|null 成功返回扩展分析结果，失败返回 null
     */
    public function analyzeExtended(string $imageUrl): ?array
    {
        $config = \think\facade\Config::get('auto_tagging', []);
        $origTimeout = $this->timeout;
        $this->timeout = intval($config['extended_analyze_timeout'] ?? 45);

        try {
            $result = $this->callApi('/api/analyze/extended', ['image_url' => $imageUrl]);
            if (empty($result) || ($result['status'] ?? '') !== 'success') {
                Log::warning('ImageAnalysis 扩展分析返回异常', ['result' => $result]);
                return null;
            }
            return $result;
        } catch (\Exception $e) {
            Log::error('ImageAnalysis 扩展分析API调用失败', ['error' => $e->getMessage()]);
            return null;
        } finally {
            $this->timeout = $origTimeout;
        }
    }

    /**
     * 解析表情分值，取最高分对应的中文标签作为主表情
     *
     * @param array $emotionScores ['平静' => 0.x, '微笑' => 0.x, ...]
     * @return string 主表情中文标签
     */
    public static function parsePrimaryEmotion(array $emotionScores): string
    {
        if (empty($emotionScores)) {
            return '';
        }
        arsort($emotionScores);
        $primary = array_key_first($emotionScores);
        return $primary ?: '';
    }

    /**
     * 解析扩展分析结果为数据库写入格式
     *
     * @param array $extendedResult /api/analyze/extended 的完整响应
     * @return array 包含所有扩展标签字段的关联数组（可安全 merge 到 updateData）
     */
    public static function parseExtendedAttributes(array $extendedResult): array
    {
        $faces = $extendedResult['faces'] ?? [];
        $faceCount = count($faces);

        $defaults = [
            'emotion_primary'      => '',
            'emotion_scores'       => null,
            'glasses_type'         => '',
            'eyelid_type'          => '',
            'eyebrow_shape'        => '',
            'eyebrow_thickness'    => '',
            'lip_type'             => '',
            'has_beard'            => 0,
            'skin_tone'            => '',
            'hair_length'          => '',
            'has_bangs'            => 0,
            'has_mask'             => 0,
            'has_accessory'        => 0,
            'extended_tag_status'  => 3,  // 默认失败
            'extended_tag_time'    => time(),
            'extended_tag_data'    => null,
        ];

        if ($faceCount === 0) {
            return $defaults;
        }

        // 取主体人脸（已按面积排序）
        $mainFace = $faces[0];
        $emotion = $mainFace['emotion'] ?? [];
        $emotionPrimary = $mainFace['emotion_primary'] ?? '';

        $extendedData = [
            'emotion_primary'      => $emotionPrimary,
            'emotion_scores'       => !empty($emotion) ? json_encode($emotion, JSON_UNESCAPED_UNICODE) : null,
            'glasses_type'         => $mainFace['glasses_type'] ?? '',
            'eyelid_type'          => $mainFace['eyelid_type'] ?? '',
            'eyebrow_shape'        => '',
            'eyebrow_thickness'    => '',
            'lip_type'             => '',
            'has_beard'            => 0,
            'skin_tone'            => $mainFace['skin_tone'] ?? '',
            'hair_length'          => $mainFace['hair_length'] ?? '',
            'has_bangs'            => 0,
            'has_mask'             => 0,
            'has_accessory'        => 0,
            'extended_tag_status'  => 2,  // 完成
            'extended_tag_time'    => time(),
            'extended_tag_data'    => json_encode($extendedResult, JSON_UNESCAPED_UNICODE),
        ];

        return $extendedData;
    }
}

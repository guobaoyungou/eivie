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
    /** 性别置信度阈值：二分类基准0.5 + 0.15安全边际 */
    const MIN_GENDER_CONFIDENCE = 0.65;
    /** 年龄置信度阈值：九分类基准0.11 + 0.44安全边际 */
    const MIN_AGE_CONFIDENCE = 0.55;

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
     * 加入置信度阈值过滤：低于阈值将对应字段设为 Unknown，
     * 同时保留原始识别值（raw_gender/raw_age_group）用于诊断日志。
     *
     * @param array $analyzeResult /api/analyze 返回的完整结果
     * @param float|null $minGenderConfidence 性别置信度阈值（null=使用类常量默认值）
     * @param float|null $minAgeConfidence 年龄置信度阈值（null=使用类常量默认值）
     * @return array
     */
    public static function extractMainSubject(
        array $analyzeResult,
        ?float $minGenderConfidence = null,
        ?float $minAgeConfidence = null
    ): array {
        $minGenderConfidence = $minGenderConfidence ?? self::MIN_GENDER_CONFIDENCE;
        $minAgeConfidence = $minAgeConfidence ?? self::MIN_AGE_CONFIDENCE;

        $faces = $analyzeResult['faces'] ?? [];
        $faceCount = count($faces);

        // 默认值
        $default = [
            'gender' => 'Unknown',
            'age_group' => 'Unknown',
            'is_multi_face' => $faceCount > 1,
            'face_count' => $faceCount,
            'gender_confidence' => 0.0,
            'age_confidence' => 0.0,
            'race' => 'Unknown',
            'raw_gender' => 'Unknown',
            'raw_age_group' => 'Unknown',
            'is_low_confidence' => true,
        ];

        if ($faceCount === 0) {
            return $default;
        }

        // 按 bbox_area 降序，取最大人脸作为主体
        usort($faces, fn($a, $b) => ($b['bbox_area'] ?? 0) <=> ($a['bbox_area'] ?? 0));
        $main = $faces[0];

        // 原始值（API 直接返回）
        $rawGender = ucfirst(strtolower($main['gender'] ?? 'Unknown'));
        $rawAgeGroup = $main['age_group'] ?? 'Unknown';
        $genderConf = round($main['gender_confidence'] ?? 0, 4);
        $ageConf = round($main['age_confidence'] ?? 0, 4);

        // 置信度阈值过滤
        $finalGender = ($genderConf >= $minGenderConfidence) ? $rawGender : 'Unknown';
        $finalAgeGroup = ($ageConf >= $minAgeConfidence) ? $rawAgeGroup : 'Unknown';
        $isLowConf = ($finalGender === 'Unknown' || $finalAgeGroup === 'Unknown') && $faceCount > 0;

        return [
            'gender' => $finalGender,
            'age_group' => $finalAgeGroup,
            'is_multi_face' => $faceCount > 1,
            'face_count' => $faceCount,
            'gender_confidence' => $genderConf,
            'age_confidence' => $ageConf,
            'race' => ucfirst(strtolower($main['race'] ?? 'Unknown')),
            'race_confidence' => round($main['race_confidence'] ?? 0, 4),
            'raw_gender' => $rawGender,
            'raw_age_group' => $rawAgeGroup,
            'is_low_confidence' => $isLowConf,
        ];
    }
}

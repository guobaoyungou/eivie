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
     * @param array $analyzeResult /api/analyze 返回的完整结果
     * @return array ['gender' => 'Male'|'Female', 'age_group' => '20-29', 'is_multi_face' => bool, 'face_count' => int]
     */
    public static function extractMainSubject(array $analyzeResult): array
    {
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
        ];

        if ($faceCount === 0) {
            return $default;
        }

        // 按 bbox_area 降序，取最大人脸作为主体
        usort($faces, fn($a, $b) => ($b['bbox_area'] ?? 0) <=> ($a['bbox_area'] ?? 0));
        $main = $faces[0];

        return [
            'gender' => ucfirst(strtolower($main['gender'] ?? 'Unknown')),
            'age_group' => $main['age_group'] ?? 'Unknown',
            'is_multi_face' => $faceCount > 1,
            'face_count' => $faceCount,
            'gender_confidence' => round($main['gender_confidence'] ?? 0, 3),
            'age_confidence' => round($main['age_confidence'] ?? 0, 3),
            'race' => ucfirst(strtolower($main['race'] ?? 'Unknown')),
        ];
    }
}

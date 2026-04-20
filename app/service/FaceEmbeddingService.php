<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Config;
use think\facade\Log;

/**
 * 人脸特征提取服务
 * 
 * 封装对 InsightFace 后端服务 /api/extract_embedding 的调用，
 * 统一返回 512 维 L2 归一化人脸特征向量。
 * 
 * 替代前端 face-api.js 的 128 维向量，确保所有来源
 * （商家上传、笑脸抓拍、用户自拍）使用同一模型提取特征。
 *
 * Class FaceEmbeddingService
 * @package app\service
 */
class FaceEmbeddingService
{
    /** @var string InsightFace 服务地址 */
    private $serviceHost;

    /** @var int InsightFace 服务端口 */
    private $servicePort;

    /** @var int HTTP 请求超时（秒） */
    private $timeout;

    public function __construct()
    {
        // InsightFace 服务地址配置（复用 .env 或默认本机 8867）
        $this->serviceHost = env('insightface.host', '127.0.0.1');
        $this->servicePort = (int)env('insightface.port', 8867);
        $this->timeout = 30; // 特征提取涉及模型推理，需要较长超时
    }

    /**
     * 获取 InsightFace 服务 API 基础 URL
     */
    private function getBaseUrl(): string
    {
        return "http://{$this->serviceHost}:{$this->servicePort}";
    }

    /**
     * 从远程图片 URL 提取人脸特征
     *
     * @param string $imageUrl 图片远程 URL（OSS/CDN 地址）
     * @param int $maxFaces 最多提取几张人脸，默认1（取最大面积）
     * @return array|null 成功返回 ['embedding' => float[], 'dim' => int, 'det_score' => float, 'bbox' => float[]]，无人脸或失败返回 null
     */
    public function extractFromUrl(string $imageUrl, int $maxFaces = 1): ?array
    {
        return $this->doExtract(['image_url' => $imageUrl, 'max_faces' => $maxFaces]);
    }

    /**
     * 从 Base64 编码图片提取人脸特征
     *
     * @param string $base64Data 图片 Base64 编码（可含 data:image/... 前缀）
     * @param int $maxFaces 最多提取几张人脸，默认1
     * @return array|null 成功返回特征数据，无人脸或失败返回 null
     */
    public function extractFromBase64(string $base64Data, int $maxFaces = 1): ?array
    {
        return $this->doExtract(['image_base64' => $base64Data, 'max_faces' => $maxFaces]);
    }

    /**
     * 批量提取：返回图中所有人脸的特征
     *
     * @param string $imageUrl 图片 URL
     * @param int $maxFaces 最多返回人脸数
     * @return array 每个元素为 ['embedding' => float[], 'dim' => int, 'det_score' => float, 'bbox' => float[]]
     */
    public function extractAllFromUrl(string $imageUrl, int $maxFaces = 5): array
    {
        return $this->doExtractAll(['image_url' => $imageUrl, 'max_faces' => $maxFaces]);
    }

    /**
     * 检查 InsightFace 服务是否可用
     *
     * @return bool
     */
    public function isServiceHealthy(): bool
    {
        try {
            $url = $this->getBaseUrl() . '/api/health';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return false;
            }
            $data = json_decode($response, true);
            return !empty($data['insightface_loaded']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 执行特征提取请求（返回最大面积的单张人脸）
     *
     * @param array $requestData 请求参数
     * @return array|null
     */
    private function doExtract(array $requestData): ?array
    {
        try {
            $result = $this->callApi('/api/extract_embedding', $requestData);

            if (empty($result) || $result['status'] !== 'success') {
                Log::warning('InsightFace 特征提取返回异常', ['result' => $result]);
                return null;
            }

            if (empty($result['faces'])) {
                Log::info('InsightFace 特征提取: 未检测到人脸');
                return null;
            }

            // 返回第一张（最大面积）人脸的特征
            $face = $result['faces'][0];
            $embedding = $face['embedding'] ?? [];

            if (empty($embedding)) {
                Log::warning('InsightFace 特征提取: embedding 为空');
                return null;
            }

            return [
                'embedding' => $embedding,
                'dim' => $face['embedding_dim'] ?? count($embedding),
                'det_score' => $face['det_score'] ?? 0.0,
                'bbox' => $face['bbox'] ?? [],
                'bbox_area' => $face['bbox_area'] ?? 0.0,
            ];
        } catch (\Exception $e) {
            Log::error('InsightFace 特征提取失败', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 执行特征提取请求（返回所有人脸）
     *
     * @param array $requestData 请求参数
     * @return array
     */
    private function doExtractAll(array $requestData): array
    {
        try {
            $result = $this->callApi('/api/extract_embedding', $requestData);

            if (empty($result) || $result['status'] !== 'success' || empty($result['faces'])) {
                return [];
            }

            $faces = [];
            foreach ($result['faces'] as $face) {
                $embedding = $face['embedding'] ?? [];
                if (!empty($embedding)) {
                    $faces[] = [
                        'embedding' => $embedding,
                        'dim' => $face['embedding_dim'] ?? count($embedding),
                        'det_score' => $face['det_score'] ?? 0.0,
                        'bbox' => $face['bbox'] ?? [],
                        'bbox_area' => $face['bbox_area'] ?? 0.0,
                    ];
                }
            }
            return $faces;
        } catch (\Exception $e) {
            Log::error('InsightFace 批量特征提取失败', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 调用 InsightFace API
     *
     * @param string $path API 路径
     * @param array $data POST 数据
     * @return array 解码后的响应
     */
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
            throw new \Exception('InsightFace API 请求失败: ' . $error);
        }

        if ($httpCode >= 400) {
            $errDetail = '';
            $decoded = json_decode($response, true);
            if ($decoded && isset($decoded['detail'])) {
                $errDetail = $decoded['detail'];
            }
            throw new \Exception("InsightFace API 返回错误 HTTP {$httpCode}: {$errDetail}");
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new \Exception('InsightFace API 返回数据解析失败');
        }

        return $result;
    }
}

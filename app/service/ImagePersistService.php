<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\common\Pic;

/**
 * 图片持久化转存服务
 *
 * 统一封装「下载远程临时图片 → WebP 压缩 → 云存储上传 → 清理临时文件」完整流程。
 * 供 AiTravelPhotoSynthesisService / GenerationService 等链路统一调用，
 * 确保 AI 生图成片链接永久可用且体积最优。
 */
class ImagePersistService
{
    /** 默认 WebP 压缩质量 */
    const DEFAULT_QUALITY = 82;

    /** 临时文件子目录（相对 runtime/temp/） */
    const TEMP_DIR = 'persist_webp';

    /**
     * 将远程临时 URL 下载、WebP 压缩并上传到云存储，返回永久 URL
     *
     * @param string $tempUrl   AI API 返回的临时签名 URL
     * @param int    $aid       平台 ID（用于定位云存储配置和上传路径）
     * @param string $bizType   业务类型标识，影响存储子目录，默认 ai_result
     * @param int    $quality   WebP 压缩质量（1-100），默认 82
     * @return string 永久可用的云存储 URL（WebP 格式优先，失败时为原格式）
     */
    public function persistAndCompress(string $tempUrl, int $aid = 0, string $bizType = 'ai_result', int $quality = self::DEFAULT_QUALITY): string
    {
        if (empty($tempUrl)) {
            return '';
        }

        try {
            // 1. 前置检查：如果已在目标存储中，直接返回
            if ($this->isAlreadyPersisted($tempUrl)) {
                Log::info('ImagePersistService: URL已在目标存储中，跳过转存', ['url' => substr($tempUrl, 0, 120)]);
                return $tempUrl;
            }

            // 2. 下载远程图片到本地临时目录
            $tempFilePath = $this->downloadToTemp($tempUrl);
            if (empty($tempFilePath)) {
                Log::warning('ImagePersistService: 下载失败，返回原始URL', ['url' => substr($tempUrl, 0, 120)]);
                return $tempUrl;
            }

            // 3. WebP 压缩
            $uploadFilePath = $tempFilePath;
            $webpPath = $this->compressToWebp($tempFilePath, $quality);
            if ($webpPath !== false && $webpPath !== $tempFilePath) {
                $uploadFilePath = $webpPath;
            }

            // 4. 上传到云存储
            $permanentUrl = $this->uploadToPermanentStorage($uploadFilePath, $aid, $bizType);

            // 5. 清理临时文件
            $this->cleanup([$tempFilePath, $webpPath]);

            if (!empty($permanentUrl)) {
                Log::info('ImagePersistService: 转存成功', [
                    'original_url' => substr($tempUrl, 0, 120),
                    'permanent_url' => substr($permanentUrl, 0, 120),
                    'is_webp' => (pathinfo($permanentUrl, PATHINFO_EXTENSION) === 'webp'),
                ]);
                return $permanentUrl;
            }

            // 上传失败，返回原始 URL
            Log::warning('ImagePersistService: 上传失败，返回原始URL', ['url' => substr($tempUrl, 0, 120)]);
            return $tempUrl;

        } catch (\Exception $e) {
            Log::error('ImagePersistService: 转存异常 - ' . $e->getMessage(), [
                'url' => substr($tempUrl, 0, 120),
            ]);
            return $tempUrl;
        }
    }

    /**
     * 批量转存（单张失败不影响其他图片）
     *
     * @param array  $urls     临时 URL 数组
     * @param int    $aid      平台 ID
     * @param string $bizType  业务类型
     * @param int    $quality  WebP 压缩质量
     * @return array 转存后的永久 URL 数组（与输入一一对应）
     */
    public function batchPersistAndCompress(array $urls, int $aid = 0, string $bizType = 'ai_result', int $quality = self::DEFAULT_QUALITY): array
    {
        $results = [];
        foreach ($urls as $url) {
            try {
                $results[] = $this->persistAndCompress($url, $aid, $bizType, $quality);
            } catch (\Exception $e) {
                Log::error('ImagePersistService batch: 单张转存失败 - ' . $e->getMessage(), [
                    'url' => substr($url, 0, 120),
                ]);
                $results[] = $url; // 失败时保留原 URL
            }
        }
        return $results;
    }

    /**
     * 判断 URL 是否已在目标云存储中
     */
    protected function isAlreadyPersisted(string $url): bool
    {
        // 本地存储
        if (defined('PRE_URL') && !empty(PRE_URL) && strpos($url, PRE_URL) === 0) {
            return true;
        }

        // 从数据库读取云存储配置
        $remoteset = $this->getRemoteConfig();
        if (empty($remoteset)) {
            return false;
        }

        $type = intval($remoteset['type'] ?? 0);

        // 阿里云 OSS
        if ($type == 2 && !empty($remoteset['alioss']['url'])) {
            if (strpos($url, $remoteset['alioss']['url']) === 0) {
                return true;
            }
        }

        // 七牛云
        if ($type == 3 && !empty($remoteset['qiniu']['url'])) {
            if (strpos($url, $remoteset['qiniu']['url']) === 0) {
                return true;
            }
        }

        // 腾讯云 COS
        if ($type == 4 && !empty($remoteset['cos']['url'])) {
            if (strpos($url, $remoteset['cos']['url']) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取云存储配置
     */
    protected function getRemoteConfig(): array
    {
        try {
            if (defined('aid') && aid > 0) {
                $remoteset = Db::name('admin')->where('id', aid)->value('remote');
                $remoteset = json_decode($remoteset ?: '', true);
                if (!empty($remoteset) && ($remoteset['type'] ?? 0) > 0) {
                    return $remoteset;
                }
            }
            $remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
            return json_decode($remoteset ?: '', true) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 下载远程图片到本地临时目录
     *
     * @param string $url 远程图片 URL
     * @return string|null 本地临时文件路径，失败返回 null
     */
    protected function downloadToTemp(string $url): ?string
    {
        $tempDir = runtime_path() . 'temp/' . self::TEMP_DIR . '/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        if (!is_writable($tempDir)) {
            Log::error('ImagePersistService: 临时目录不可写', ['dir' => $tempDir]);
            return null;
        }

        // 从 URL 推断扩展名
        $ext = $this->guessExtension($url);
        $filename = 'persist_' . date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8) . '.' . $ext;
        $localPath = $tempDir . $filename;

        try {
            $content = $this->downloadWithCurl($url);
            if (empty($content)) {
                return null;
            }

            file_put_contents($localPath, $content);

            if (!file_exists($localPath) || filesize($localPath) === 0) {
                @unlink($localPath);
                return null;
            }

            return $localPath;
        } catch (\Exception $e) {
            Log::error('ImagePersistService: 下载异常 - ' . $e->getMessage());
            @unlink($localPath);
            return null;
        }
    }

    /**
     * 使用 cURL 下载远程文件
     */
    protected function downloadWithCurl(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $content === false) {
            Log::warning('ImagePersistService: cURL下载失败', [
                'http_code' => $httpCode,
                'error' => $error,
            ]);
            return null;
        }

        return $content;
    }

    /**
     * 将本地图片转换为 WebP 格式
     *
     * @param string $localPath 本地图片路径
     * @param int    $quality   WebP 质量
     * @return string|false 成功返回 WebP 文件路径，失败返回 false
     */
    protected function compressToWebp(string $localPath, int $quality = self::DEFAULT_QUALITY)
    {
        // 检查文件是否已经是 WebP
        $imageInfo = @getimagesize($localPath);
        if ($imageInfo && $imageInfo['mime'] === 'image/webp') {
            return $localPath; // 已经是 WebP，无需转换
        }

        return Pic::convertToWebp($localPath, $quality);
    }

    /**
     * 上传到永久存储（云存储或本地）
     *
     * @param string $localPath 本地文件路径
     * @param int    $aid       平台 ID
     * @param string $bizType   业务类型
     * @return string 永久 URL
     */
    protected function uploadToPermanentStorage(string $localPath, int $aid = 0, string $bizType = 'ai_result'): string
    {
        if (!file_exists($localPath)) {
            return '';
        }

        // 构建上传目标路径
        $ext = pathinfo($localPath, PATHINFO_EXTENSION) ?: 'webp';
        $uniqueId = substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
        $dateDir = date('Ymd');

        // 根据业务类型确定子目录
        switch ($bizType) {
            case 'ai_travel_photo':
                $subDir = "upload/{$aid}/{$dateDir}";
                $filename = "synth_{$uniqueId}.{$ext}";
                break;
            case 'generation':
                $subDir = "upload/generation/{$dateDir}";
                $filename = "{$uniqueId}.{$ext}";
                break;
            case 'watermark':
                $subDir = "upload/{$aid}/{$dateDir}";
                $filename = "wm_{$uniqueId}.{$ext}";
                break;
            default:
                $subDir = "upload/{$aid}/{$dateDir}";
                $filename = "{$uniqueId}.{$ext}";
                break;
        }

        // 确保目录存在
        $targetDir = ROOT_PATH . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 复制文件到上传目录
        $targetPath = $targetDir . '/' . $filename;
        if (!copy($localPath, $targetPath)) {
            Log::error('ImagePersistService: 文件复制失败', ['from' => $localPath, 'to' => $targetPath]);
            return '';
        }

        // 构建本地 URL 并调用 Pic::uploadoss 上传到云存储
        $localUrl = PRE_URL . '/' . $subDir . '/' . $filename;
        $ossUrl = Pic::uploadoss($localUrl, false, false); // transcode=false，已自行转 WebP

        return $ossUrl ?: $localUrl;
    }

    /**
     * 清理临时文件
     *
     * @param array $filePaths 文件路径列表
     */
    protected function cleanup(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (!empty($path) && file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * 从 URL 推断文件扩展名
     */
    protected function guessExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                return $ext;
            }
        }
        return 'jpg'; // 默认
    }

    /**
     * 获取文件大小（字节数）
     * 用于替代远程 HTTP HEAD 获取文件大小的场景
     *
     * @param string $url 文件 URL（本地或远程）
     * @return int 文件大小（字节），失败返回 0
     */
    public static function getFileSize(string $url): int
    {
        if (empty($url)) {
            return 0;
        }

        // 如果是本地文件
        if (defined('PRE_URL') && strpos($url, PRE_URL) === 0) {
            $localPath = ROOT_PATH . ltrim(str_replace(PRE_URL, '', $url), '/');
            if (file_exists($localPath)) {
                return (int)filesize($localPath);
            }
        }

        // 远程文件通过 HTTP HEAD 获取
        return SpaceCheckService::getRemoteFileSize($url);
    }
}

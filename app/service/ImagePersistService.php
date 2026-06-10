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
        // 确保运行环境常量已定义（CLI/队列/Web 上下文可能不同）
        if (!defined('aid') && $aid > 0) {
            define('aid', $aid);
        } elseif (!defined('aid')) {
            define('aid', 0);
        }
        if (!defined('PRE_URL')) {
            $siteUrl = 'https://' . (gethostname() ?: 'localhost');
            try {
                $admin = \think\facade\Db::name('admin')->where('id', 1)->field('domain')->find();
                if ($admin && !empty($admin['domain'])) {
                    $siteUrl = 'https://' . $admin['domain'];
                }
            } catch (\Throwable $e) {}
            define('PRE_URL', $siteUrl);
        }

        Log::info('ImagePersistService::persistAndCompress: 开始处理', [
            'tempUrl' => substr($tempUrl, 0, 120),
            'aid' => $aid,
            'bizType' => $bizType,
            'quality' => $quality,
        ]);
        
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
            Log::info('ImagePersistService::persistAndCompress: downloadToTemp返回', [
                'tempFilePath' => $tempFilePath ?: 'NULL',
            ]);
            
            if (empty($tempFilePath)) {
                Log::warning('ImagePersistService: 下载失败，返回原始URL', ['url' => substr($tempUrl, 0, 120)]);
                return $tempUrl;
            }

            // 3. 验证文件大小
            $fileSize = file_exists($tempFilePath) ? filesize($tempFilePath) : 0;
            Log::info('ImagePersistService: 文件下载完成', [
                'file' => $tempFilePath,
                'size' => $fileSize,
            ]);
            
            if ($fileSize < 100) {
                Log::warning('ImagePersistService: 下载的文件太小，可能无效', [
                    'url' => substr($tempUrl, 0, 120),
                    'file' => $tempFilePath,
                    'size' => $fileSize,
                ]);
                $this->cleanup([$tempFilePath]);
                return $tempUrl;
            }

            // 4. WebP 压缩（如果失败，使用原文件）
            //    注意：convertToWebp 成功后会自动删除原文件，所以必须准确跟踪最终有效的文件路径
            Log::info('ImagePersistService: WebP压缩前 tempFile=' . basename($tempFilePath));
            $uploadFilePath = $tempFilePath;
            try {
                $webpPath = $this->compressToWebp($tempFilePath, $quality);

                // 转换成功：原文件已被 convertToWebp 删除，WebP 文件是新的有效文件
                if ($webpPath !== false && $webpPath !== $tempFilePath && file_exists($webpPath)) {
                    $uploadFilePath = $webpPath;
                    Log::info('ImagePersistService: WebP压缩成功 webpFile=' . basename($webpPath));
                }
                // 转换跳过（已是 WebP 或转换失败）：原文件仍存在
                elseif (file_exists($tempFilePath)) {
                    // 原文件还在，继续使用
                    Log::info('ImagePersistService: WebP压缩跳过，使用原文件 tempFile=' . basename($tempFilePath) . ' exists=YES');
                }
                // 转换失败且原文件已被删除：无可用文件
                else {
                    Log::error('ImagePersistService: WebP压缩后无可用文件 tempFile=' . basename($tempFilePath) . ' exists=NO webpPath=' . var_export($webpPath, true));
                    return $tempUrl;
                }
            } catch (\Throwable $e) {
                // 异常后检查原文件是否存在
                if (file_exists($tempFilePath)) {
                    Log::warning('ImagePersistService: WebP压缩异常，使用原文件: ' . get_class($e) . ': ' . $e->getMessage());
                } else {
                    Log::error('ImagePersistService: WebP压缩异常且原文件已丢失: ' . get_class($e) . ': ' . $e->getMessage());
                    return $tempUrl;
                }
            }

            Log::info('ImagePersistService: 准备上传 uploadFile=' . basename($uploadFilePath) . ' exists=' . (file_exists($uploadFilePath) ? 'YES' : 'NO'));

            // 5. 上传到云存储
            Log::info('ImagePersistService: 开始上传到云存储', [
                'uploadFilePath' => $uploadFilePath,
                'aid' => $aid,
                'bizType' => $bizType,
            ]);
            $permanentUrl = $this->uploadToPermanentStorage($uploadFilePath, $aid, $bizType);
            Log::info('ImagePersistService: 上传完成', [
                'permanentUrl' => substr($permanentUrl, 0, 120) ?: 'EMPTY',
                'permanentUrl_len' => strlen($permanentUrl ?: ''),
            ]);

            // 6. 清理临时文件（包装 try/catch 防止清理异常中断流程）
            try {
                $this->cleanup([$tempFilePath, $webpPath]);
            } catch (\Throwable $cleanupEx) {
                Log::warning('ImagePersistService: 清理临时文件异常 - ' . $cleanupEx->getMessage());
            }

            // 7. 判断结果：必须有值且不能是原临时地址
            if (!empty($permanentUrl) && $permanentUrl !== $tempUrl) {
                Log::info('ImagePersistService: 转存成功', [
                    'original_url' => substr($tempUrl, 0, 120),
                    'permanent_url' => substr($permanentUrl, 0, 120),
                    'is_webp' => (pathinfo($permanentUrl, PATHINFO_EXTENSION) === 'webp'),
                ]);
                return $permanentUrl;
            }

            // 上传返回了空值或与原始URL相同，视为失败
            Log::error('ImagePersistService: 持久化未产出有效新URL（结果为空或与原始相同）', [
                'original' => substr($tempUrl, 0, 120),
                'result' => substr($permanentUrl ?: '(empty)', 0, 120),
            ]);
            return $tempUrl;

        } catch (\Throwable $e) {
            Log::error('ImagePersistService: 转存异常(' . get_class($e) . '): ' . $e->getMessage(), [
                'url' => substr($tempUrl, 0, 120),
                'trace' => $e->getTraceAsString(),
            ]);
            return $tempUrl;
        }
    }
    
    /**
     * 验证文件是否是有效的图片
     *
     * @param string $filePath 本地文件路径
     * @return bool 是否是有效图片
     */
    protected function isValidImage(string $filePath): bool
    {
        if (!file_exists($filePath) || filesize($filePath) < 1000) {
            return false;
        }
        
        // 检查文件头部是否是图片格式
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 16);
        fclose($handle);
        
        // JPEG: FF D8 FF
        if (substr($header, 0, 3) === "\xFF\xD8\xFF") {
            return true;
        }
        
        // PNG: 89 50 4E 47 0D 0A 1A 0A
        if (substr($header, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            return true;
        }
        
        // WebP: RIFF....WEBP
        if (substr($header, 0, 4) === "RIFF" && strpos($header, 'WEBP') !== false) {
            return true;
        }
        
        // GIF: 47 49 46 38 (GIF8)
        if (substr($header, 0, 4) === "GIF8") {
            return true;
        }
        
        // BMP: 42 4D (BM)
        if (substr($header, 0, 2) === "BM") {
            return true;
        }
        
        return false;
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
        // 排除代理路径（如 /p/img/），这些需要转存到云存储
        if (strpos($url, '/p/img/') !== false) {
            return false;
        }

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_REFERER, '');
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if ($httpCode !== 200 || $content === false || empty($content)) {
            Log::warning('ImagePersistService: cURL下载失败', [
                'url' => substr($url, 0, 120),
                'effective_url' => substr($effectiveUrl, 0, 120),
                'http_code' => $httpCode,
                'content_length' => strlen($content ?: ''),
                'error' => $error,
            ]);
            return null;
        }

        Log::info('ImagePersistService: cURL下载成功', [
            'url' => substr($url, 0, 120),
            'content_length' => strlen($content),
        ]);

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
            Log::error('ImagePersistService: uploadToPermanentStorage 源文件不存在', ['path' => $localPath]);
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
            Log::error('ImagePersistService: 文件复制失败', [
                'from' => $localPath,
                'from_exists' => file_exists($localPath),
                'to' => $targetPath,
                'target_dir' => $targetDir,
                'target_dir_writable' => is_writable($targetDir),
            ]);
            return '';
        }

        // 构建本地 URL 并调用 Pic::uploadoss 上传到云存储
        // 确保 PRE_URL 已定义，CLI 环境下可能未设置
        if (!defined('PRE_URL')) {
            $siteUrl = 'https://' . (gethostname() ?: 'localhost');
            try {
                $admin = \think\facade\Db::name('admin')->where('id', 1)->field('domain')->find();
                if ($admin && !empty($admin['domain'])) {
                    $siteUrl = 'https://' . $admin['domain'];
                }
            } catch (\Throwable $e) {}
            define('PRE_URL', $siteUrl);
        }
        $localUrl = PRE_URL . '/' . $subDir . '/' . $filename;
        Log::info('ImagePersistService: uploadToPermanentStorage 准备上传到云存储', [
            'localUrl' => substr($localUrl, 0, 100),
            'file_size' => file_exists($targetPath) ? filesize($targetPath) : 0,
        ]);

        $ossUrl = Pic::uploadoss($localUrl, false, false); // transcode=false，已自行转 WebP

        Log::info('ImagePersistService: uploadToPermanentStorage Pic::uploadoss 返回', [
            'ossUrl' => substr($ossUrl ?: '(empty)', 0, 100),
            'ossUrl_type' => gettype($ossUrl),
            'localUrl' => substr($localUrl, 0, 80),
        ]);

        $result = $ossUrl ?: $localUrl;

        Log::info('ImagePersistService: uploadToPermanentStorage 最终返回', [
            'result' => substr($result, 0, 100),
        ]);

        return $result;
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

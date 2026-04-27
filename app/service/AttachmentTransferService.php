<?php
/**
 * 附件转存服务类
 * 将远程文件转存到系统配置的附件存储中
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class AttachmentTransferService
{
    /**
     * 配置项
     */
    protected $config = [
        'transfer_enabled' => true,       // 是否启用转存
        'download_timeout' => 60,         // 下载超时时间（秒）
        'max_image_size' => 20971520,     // 图片最大大小 20MB
        'max_video_size' => 524288000,    // 视频最大大小 500MB
        'retry_times' => 1,               // 失败重试次数
        'cleanup_temp' => true,           // 是否清理临时文件
        'convert_to_webp' => true,        // 是否将图片自动转换为WebP格式
        'webp_quality' => 85,             // WebP输出质量（1-100）
    ];
    
    /**
     * 支持的文件类型
     */
    protected $supportedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'mov', 'avi', 'webm']
    ];
    
    /**
     * 当前商家的存储配置
     */
    protected $storageConfig = null;
    
    /**
     * 平台ID
     */
    protected $aid = 0;
    
    /**
     * 构造函数
     * @param int $aid 平台ID
     */
    public function __construct($aid = 0)
    {
        $this->aid = $aid ?: (defined('aid') ? aid : 0);
        $this->loadStorageConfig();
    }
    
    /**
     * 加载存储配置
     */
    protected function loadStorageConfig()
    {
        // 先获取商家配置
        if ($this->aid > 0) {
            $remoteset = Db::name('admin')->where('id', $this->aid)->value('remote');
            $remoteset = json_decode($remoteset, true);
            
            // 如果商家配置为空或type为0，则使用系统配置
            if (!$remoteset || $remoteset['type'] == 0) {
                $remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
                $remoteset = json_decode($remoteset, true);
            } else {
                // 获取系统配置的delete_local设置
                $sysset = Db::name('sysset')->where('name', 'remote')->value('value');
                $sysset = json_decode($sysset, true);
                $remoteset['delete_local'] = $sysset['delete_local'] ?? 0;
            }
        } else {
            $remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
            $remoteset = json_decode($remoteset, true);
        }
        
        $this->storageConfig = $remoteset;
        
        // 记录存储配置信息（便于诊断）
        Log::info('AttachmentTransferService加载存储配置', [
            'aid' => $this->aid,
            'storageType' => $remoteset['type'] ?? 'null',
            'hasConfig' => !empty($remoteset)
        ]);
    }
    
    /**
     * 获取存储配置
     * @return array
     */
    public function getStorageConfig()
    {
        return $this->storageConfig;
    }
    
    /**
     * 转存单个远程文件
     * @param string $sourceUrl 源文件URL
     * @param string $targetPath 目标路径（可选，自动生成）
     * @param string $fileType 文件类型 image/video
     * @return array TransferResult
     */
    public function transferRemoteFile($sourceUrl, $targetPath = '', $fileType = 'image')
    {
        $startTime = microtime(true);
        
        Log::info('开始转存单个文件', [
            'url' => substr($sourceUrl, 0, 100),
            'fileType' => $fileType,
            'storageType' => $this->storageConfig['type'] ?? 'unknown',
            'aid' => $this->aid
        ]);
        
        $result = [
            'success' => false,
            'original_url' => $sourceUrl,
            'new_url' => $sourceUrl,  // 默认返回原URL
            'error_msg' => '',
            'file_type' => $fileType,
            'file_size' => 0,
            'transfer_time' => 0
        ];
        
        // 检查是否启用转存
        if (!$this->config['transfer_enabled']) {
            $result['error_msg'] = '转存功能未启用';
            return $result;
        }
        
        // 检查URL有效性
        if (empty($sourceUrl) || !$this->isValidUrl($sourceUrl)) {
            $result['error_msg'] = 'URL无效或为空';
            return $result;
        }
        
        // 检查是否已在目标存储
        if ($this->isAlreadyInStorage($sourceUrl)) {
            $result['success'] = true;
            $result['new_url'] = $sourceUrl;
            return $result;
        }
        
        // 获取文件扩展名，根据fileType智能选择默认值
        $extension = $this->getExtensionFromUrl($sourceUrl, $fileType);
        
        // 检查文件类型是否支持
        if (!$this->isSupportedType($extension, $fileType)) {
            // 如果扩展名不支持，使用该类型的默认扩展名
            $extension = ($fileType === 'video') ? 'mp4' : 'jpg';
            Log::info('使用默认扩展名', ['url' => $sourceUrl, 'extension' => $extension, 'fileType' => $fileType]);
        }
        
        // 执行转存（带重试）
        $retryCount = 0;
        $maxRetries = $this->config['retry_times'];
        
        while ($retryCount <= $maxRetries) {
            try {
                // 1. 下载远程文件到本地临时目录
                $tempFile = $this->downloadRemote($sourceUrl, $fileType);
                
                if (!$tempFile || !file_exists($tempFile)) {
                    throw new \Exception('下载失败：临时文件不存在');
                }
                
                // 2. 验证文件大小
                $fileSize = filesize($tempFile);
                $maxSize = ($fileType === 'video') ? $this->config['max_video_size'] : $this->config['max_image_size'];
                
                if ($fileSize > $maxSize) {
                    $this->cleanupTemp($tempFile);
                    $result['error_msg'] = '文件过大: ' . round($fileSize / 1048576, 2) . 'MB，限制: ' . round($maxSize / 1048576, 2) . 'MB';
                    return $result;
                }
                
                // 2.5 图片自动转换为WebP（减小体积、加速加载）
                if ($fileType === 'image' && $this->config['convert_to_webp'] && strtolower($extension) !== 'webp') {
                    $webpFile = \app\common\Pic::convertToWebp($tempFile, $this->config['webp_quality']);
                    if ($webpFile && file_exists($webpFile)) {
                        Log::info('附件转存: 图片已转换为WebP', [
                            'original' => basename($tempFile) . ' (' . round($fileSize / 1024) . 'KB)',
                            'webp' => basename($webpFile) . ' (' . round(filesize($webpFile) / 1024) . 'KB)',
                            'saved' => round((1 - filesize($webpFile) / max($fileSize, 1)) * 100) . '%'
                        ]);
                        $tempFile = $webpFile;
                        $extension = 'webp';
                        $fileSize = filesize($webpFile);
                    } else {
                        Log::info('附件转存: WebP转换失败，保留原格式', ['file' => basename($tempFile)]);
                    }
                }
                
                // 3. 上传到存储
                $newUrl = $this->uploadToStorage($tempFile, $targetPath, $extension);
                
                // 4. 清理临时文件
                if ($this->config['cleanup_temp']) {
                    $this->cleanupTemp($tempFile);
                }
                
                if ($newUrl) {
                    $result['success'] = true;
                    $result['new_url'] = $newUrl;
                    $result['file_size'] = $fileSize;
                    $result['transfer_time'] = round((microtime(true) - $startTime) * 1000);
                    
                    Log::info('附件转存成功', [
                        'original' => substr($sourceUrl, 0, 80),
                        'new' => $newUrl,
                        'size' => $fileSize
                    ]);
                    
                    return $result;
                }
                
                throw new \Exception('上传失败');
                
            } catch (\Exception $e) {
                $retryCount++;
                $result['error_msg'] = $e->getMessage();
                
                if ($retryCount <= $maxRetries) {
                    Log::info('附件转存重试', [
                        'url' => substr($sourceUrl, 0, 80),
                        'retry' => $retryCount,
                        'error' => $e->getMessage()
                    ]);
                    usleep(500000); // 等待0.5秒后重试
                }
            }
        }
        
        // 所有重试都失败，记录日志
        Log::error('附件转存失败', [
            'url' => substr($sourceUrl, 0, 120),
            'error' => $result['error_msg'],
            'retries' => $maxRetries
        ]);
        
        return $result;
    }
    
    /**
     * 批量转存
     * @param array $urlList URL列表，格式：[['key' => 'cover_image', 'url' => 'http://...', 'type' => 'image'], ...]
     * @return array URL映射表
     */
    public function transferBatch(array $urlList)
    {
        $mapping = [];
        
        foreach ($urlList as $item) {
            $key = $item['key'] ?? '';
            $url = $item['url'] ?? '';
            $type = $item['type'] ?? 'image';
            
            if (empty($key) || empty($url)) {
                continue;
            }
            
            $result = $this->transferRemoteFile($url, '', $type);
            
            $mapping[$key] = [
                'original' => $result['original_url'],
                'transferred' => $result['new_url'],
                'success' => $result['success'],
                'error' => $result['error_msg']
            ];
        }
        
        return $mapping;
    }
    
    /**
     * 检查URL是否有效
     */
    protected function isValidUrl($url)
    {
        if (empty($url)) {
            return false;
        }
        
        // 必须是http或https协议
        return (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
    }
    
    /**
     * 检查URL是否已在目标存储
     */
    protected function isAlreadyInStorage($url)
    {
        if (!$this->storageConfig) {
            return false;
        }
        
        $type = $this->storageConfig['type'] ?? 0;
        
        switch ($type) {
            case 2: // 阿里云OSS
                $storageUrl = $this->storageConfig['alioss']['url'] ?? '';
                break;
            case 3: // 七牛云
                $storageUrl = $this->storageConfig['qiniu']['url'] ?? '';
                break;
            case 4: // 腾讯云COS
                $storageUrl = $this->storageConfig['cos']['url'] ?? '';
                break;
            default: // 本地存储
                $storageUrl = defined('PRE_URL') ? PRE_URL : '';
        }
        
        if (!empty($storageUrl) && strpos($url, $storageUrl) === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 从URL获取文件扩展名
     * @param string $url URL地址
     * @param string $fileType 文件类型提示 image/video
     * @return string 扩展名
     */
    protected function getExtensionFromUrl($url, $fileType = 'image')
    {
        // 根据文件类型设置默认扩展名
        $defaultExt = ($fileType === 'video') ? 'mp4' : 'jpg';
        
        // 移除URL参数
        $urlPath = parse_url($url, PHP_URL_PATH);
        if (!$urlPath) {
            return $defaultExt;
        }
        
        $pathInfo = pathinfo($urlPath);
        $ext = strtolower($pathInfo['extension'] ?? '');
        
        // 处理特殊情况：扩展名过长、为空或不合法
        if (strlen($ext) > 6 || empty($ext)) {
            return $defaultExt;
        }
        
        // 验证扩展名是否在支持列表中
        $allSupported = array_merge($this->supportedTypes['image'], $this->supportedTypes['video']);
        if (!in_array($ext, $allSupported)) {
            return $defaultExt;
        }
        
        return $ext;
    }
    
    /**
     * 检查是否支持的文件类型
     */
    protected function isSupportedType($extension, $fileType)
    {
        $types = $this->supportedTypes[$fileType] ?? [];
        return in_array($extension, $types);
    }
    
    /**
     * 下载远程文件到本地临时目录
     * 使用直接CURL下载，比GuzzleHttp更可靠
     * @param string $url 远程URL
     * @param string $fileType 文件类型提示
     * @return string|false 本地临时文件路径
     */
    protected function downloadRemote($url, $fileType = 'image')
    {
        Log::info('开始下载远程文件', ['url' => substr($url, 0, 100) . '...', 'fileType' => $fileType]);
        
        // 创建临时目录并确保可写
        $tempDir = defined('ROOT_PATH') ? ROOT_PATH . 'runtime/temp/transfer/' : sys_get_temp_dir() . '/transfer/';
        if (!$this->ensureDirectoryWritable($tempDir)) {
            Log::error('临时目录不可用', ['tempDir' => $tempDir]);
            return false;
        }
        
        // 生成临时文件名，传入fileType以获取正确的默认扩展名
        $extension = $this->getExtensionFromUrl($url, $fileType);
        $tempFile = $tempDir . md5($url . time() . mt_rand()) . '.' . $extension;
        
        // 直接使用CURL下载（不依赖GuzzleHttp）
        $content = $this->curlDownload($url, $this->config['download_timeout']);
        
        if ($content === false || empty($content)) {
            Log::error('下载远程文件失败-内容为空', ['url' => substr($url, 0, 100)]);
            return false;
        }
        
        // 写入临时文件
        $written = @file_put_contents($tempFile, $content);
        
        if ($written === false || $written === 0) {
            Log::error('写入临时文件失败', ['url' => substr($url, 0, 100), 'tempFile' => $tempFile, 'contentLen' => strlen($content)]);
            return false;
        }
        
        Log::info('下载远程文件成功', ['url' => substr($url, 0, 80), 'size' => $written, 'tempFile' => basename($tempFile)]);
        return $tempFile;
    }
    
    /**
     * 使用CURL下载文件内容
     * 比GuzzleHttp更可靠，有详细的错误报告
     * @param string $url 远程URL
     * @param int $timeout 超时时间
     * @return string|false 文件内容或false
     */
    protected function curlDownload($url, $timeout = 60)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/*, video/*, */*',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            ],
            CURLOPT_ENCODING => '',  // 自动处理gzip等压缩
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $downloadSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        
        curl_close($ch);
        
        // 详细记录下载结果
        if ($curlErrno !== 0) {
            Log::error('CURL下载错误', [
                'errno' => $curlErrno,
                'error' => $curlError,
                'url' => substr($url, 0, 100)
            ]);
            return false;
        }
        
        if ($httpCode !== 200) {
            Log::error('CURL下载HTTP错误', [
                'httpCode' => $httpCode,
                'url' => substr($url, 0, 100),
                'contentType' => $contentType
            ]);
            return false;
        }
        
        if (empty($content)) {
            Log::error('CURL下载内容为空', [
                'httpCode' => $httpCode,
                'downloadSize' => $downloadSize,
                'url' => substr($url, 0, 100)
            ]);
            return false;
        }
        
        Log::info('CURL下载成功', [
            'httpCode' => $httpCode,
            'size' => strlen($content),
            'contentType' => $contentType
        ]);
        
        return $content;
    }
    
    /**
     * 确保目录存在且可写（递归修复权限）
     * 解决因root创建目录导致www用户无法写入的问题
     * @param string $dir 目标目录路径
     * @return bool
     */
    protected function ensureDirectoryWritable($dir)
    {
        // 目录已存在且可写
        if (is_dir($dir) && is_writable($dir)) {
            return true;
        }
        
        // 目录存在但不可写，尝试修复权限
        if (is_dir($dir)) {
            @chmod($dir, 0777);
            if (is_writable($dir)) {
                Log::info('ensureDirectoryWritable: chmod修复成功', ['dir' => $dir]);
                return true;
            }
            Log::error('ensureDirectoryWritable: 目录不可写且chmod失败，请手动执行: chown -R www:www ' . $dir, ['dir' => $dir]);
            return false;
        }
        
        // 目录不存在，先确保父目录可写
        $parentDir = dirname($dir);
        if ($parentDir !== $dir && !is_dir($parentDir)) {
            if (!$this->ensureDirectoryWritable($parentDir)) {
                return false;
            }
        }
        
        // 父目录存在但不可写，尝试修复
        if (is_dir($parentDir) && !is_writable($parentDir)) {
            @chmod($parentDir, 0777);
            if (!is_writable($parentDir)) {
                Log::error('ensureDirectoryWritable: 父目录不可写，请手动执行: chown -R www:www ' . $parentDir, [
                    'parentDir' => $parentDir,
                    'owner' => function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($parentDir))['name'] ?? 'unknown') : 'unknown'
                ]);
                return false;
            }
        }
        
        // 创建目录
        $result = @mkdir($dir, 0777, true);
        if ($result || is_dir($dir)) {
            @chmod($dir, 0777);
            return true;
        }
        
        $lastError = error_get_last();
        Log::error('ensureDirectoryWritable: mkdir失败', [
            'dir' => $dir,
            'error' => $lastError['message'] ?? 'unknown'
        ]);
        return false;
    }
    
    /**
     * 上传到存储
     * @param string $localFile 本地文件路径
     * @param string $targetPath 目标路径（可选）
     * @param string $extension 文件扩展名
     * @return string|false 存储URL
     */
    protected function uploadToStorage($localFile, $targetPath = '', $extension = 'jpg')
    {
        if (!file_exists($localFile)) {
            Log::error('uploadToStorage: 本地文件不存在', ['localFile' => $localFile]);
            return false;
        }
        
        // 如果未指定目标路径，自动生成
        if (empty($targetPath)) {
            $targetPath = $this->generateStoragePath($localFile, $extension);
        }
        
        Log::info('uploadToStorage: 开始上传', ['targetPath' => $targetPath, 'extension' => $extension, 'fileSize' => filesize($localFile)]);
        
        // 确保本地目录存在且可写
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : '/www/wwwroot/eivie/';
        $localDir = dirname($rootPath . $targetPath);
        
        if (!$this->ensureDirectoryWritable($localDir)) {
            Log::error('uploadToStorage: 无法创建或写入本地目录', ['dir' => $localDir]);
            return false;
        }
        
        // 复制文件到本地上传目录
        $localPath = $rootPath . $targetPath;
        if (!@copy($localFile, $localPath)) {
            $lastError = error_get_last();
            Log::error('uploadToStorage: 复制文件失败', [
                'from' => $localFile,
                'to' => $localPath,
                'error' => $lastError['message'] ?? 'unknown'
            ]);
            return false;
        }
        
        Log::info('uploadToStorage: 文件已复制到本地', ['localPath' => $localPath, 'exists' => file_exists($localPath)]);
        
        // 构建本地URL
        $preUrl = defined('PRE_URL') ? PRE_URL : '';
        if (empty($preUrl)) {
            Log::error('uploadToStorage: PRE_URL未定义，无法构建本地URL');
            return false;
        }
        
        $localUrl = $preUrl . '/' . $targetPath;
        
        try {
            // 使用Pic工具类上传到配置的存储（OSS/COS/七牛等）
            $newUrl = \app\common\Pic::uploadoss($localUrl, false, false);
            
            Log::info('uploadToStorage: Pic::uploadoss返回', ['newUrl' => $newUrl, 'localUrl' => $localUrl]);
            
            if ($newUrl === false || $newUrl === '') {
                // 上传失败但本地文件已保存，返回本地URL
                Log::error('uploadToStorage: OSS上传返回空，使用本地URL', ['localUrl' => $localUrl]);
                return $localUrl;
            }
            
            return $newUrl;
        } catch (\Exception $e) {
            Log::error('uploadToStorage异常', ['error' => $e->getMessage(), 'localUrl' => $localUrl]);
            // 返回本地URL作为降级
            return $localUrl;
        }
    }
    
    /**
     * 生成存储路径
     * 格式: upload/generation_template/{aid}/{年月}/{类型前缀}_{唯一标识}.{扩展名}
     */
    protected function generateStoragePath($localFile, $extension)
    {
        $aid = $this->aid ?: 0;
        $yearMonth = date('Ym');
        
        // 使用文件内容MD5作为唯一标识（避免重复上传）
        $fileHash = md5_file($localFile) ?: md5(uniqid(mt_rand(), true));
        $uniqueId = substr($fileHash, 0, 16);
        
        // 根据扩展名判断类型前缀
        $prefix = in_array($extension, $this->supportedTypes['video']) ? 'video' : 'img';
        
        return "upload/generation_template/{$aid}/{$yearMonth}/{$prefix}_{$uniqueId}.{$extension}";
    }
    
    /**
     * 清理临时文件
     */
    protected function cleanupTemp($tempFile)
    {
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }
    
    /**
     * 从生成记录提取需要转存的URL列表
     * @param array $record 生成记录
     * @param array $templateData 模板数据
     * @param int $generationType 生成类型 1=照片 2=视频
     * @return array URL列表
     */
    public function extractUrlsFromRecord($record, $templateData, $generationType)
    {
        $urlList = [];
        
        // 1. 封面图/视频
        $coverImage = $templateData['cover_image'] ?? '';
        if (!empty($coverImage)) {
            $urlList[] = [
                'key' => 'cover_image',
                'url' => $coverImage,
                'type' => ($generationType == 2) ? 'video' : 'image'
            ];
        }
        
        // 2. 从input_params提取图片URL
        $inputParams = $record['input_params'] ?? [];
        if (is_string($inputParams)) {
            $inputParams = json_decode($inputParams, true) ?: [];
        }
        
        // 首帧图（视频生成）
        $firstFrameImage = $inputParams['first_frame_image'] ?? $inputParams['input_image'] ?? '';
        if (!empty($firstFrameImage)) {
            $urlList[] = [
                'key' => 'first_frame_image',
                'url' => $firstFrameImage,
                'type' => 'image'
            ];
        }
        
        // 参考图
        $imageUrl = $inputParams['image_url'] ?? $inputParams['image'] ?? '';
        if (!empty($imageUrl) && $imageUrl !== $firstFrameImage) {
            $urlList[] = [
                'key' => 'image_url',
                'url' => $imageUrl,
                'type' => 'image'
            ];
        }
        
        // 3. 输出结果URL（从generation_output表）
        if (isset($record['outputs']) && is_array($record['outputs'])) {
            foreach ($record['outputs'] as $index => $output) {
                $outputUrl = $output['output_url'] ?? '';
                if (!empty($outputUrl)) {
                    $outputType = $output['output_type'] ?? 'image';
                    $urlList[] = [
                        'key' => 'output_' . $index,
                        'url' => $outputUrl,
                        'type' => $outputType
                    ];
                }
            }
        }
        
        return $urlList;
    }
    
    /**
     * 根据URL映射更新模板数据
     * @param array $templateData 原模板数据
     * @param array $inputParams 原输入参数
     * @param array $urlMapping URL映射表
     * @return array [更新后的templateData, 更新后的inputParams]
     */
    public function applyUrlMapping($templateData, $inputParams, $urlMapping)
    {
        // 1. 更新封面图
        if (isset($urlMapping['cover_image']) && $urlMapping['cover_image']['success']) {
            $templateData['cover_image'] = $urlMapping['cover_image']['transferred'];
        } elseif (isset($urlMapping['output_0']) && $urlMapping['output_0']['success']) {
            // 如果cover_image转存失败但output_0成功，且cover_image和output_0是同一个URL，使用output_0的结果
            $coverOriginal = $urlMapping['cover_image']['original'] ?? '';
            $outputOriginal = $urlMapping['output_0']['original'] ?? '';
            if ($coverOriginal === $outputOriginal) {
                $templateData['cover_image'] = $urlMapping['output_0']['transferred'];
            }
        }
        
        // 2. 更新输入参数中的URL
        if (is_string($inputParams)) {
            $inputParams = json_decode($inputParams, true) ?: [];
        }
        
        // 首帧图
        if (isset($urlMapping['first_frame_image']) && $urlMapping['first_frame_image']['success']) {
            if (isset($inputParams['first_frame_image'])) {
                $inputParams['first_frame_image'] = $urlMapping['first_frame_image']['transferred'];
            }
            if (isset($inputParams['input_image'])) {
                $inputParams['input_image'] = $urlMapping['first_frame_image']['transferred'];
            }
        }
        
        // 参考图
        if (isset($urlMapping['image_url']) && $urlMapping['image_url']['success']) {
            if (isset($inputParams['image_url'])) {
                $inputParams['image_url'] = $urlMapping['image_url']['transferred'];
            }
            if (isset($inputParams['image'])) {
                $inputParams['image'] = $urlMapping['image_url']['transferred'];
            }
        }
        
        return [$templateData, $inputParams];
    }
}

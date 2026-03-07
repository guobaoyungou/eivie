<?php
declare(strict_types=1);

namespace app\common;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云OSS上传辅助类
 * Class OssHelper
 * @package app\common
 */
class OssHelper
{
    private $ossClient;
    private $bucket;
    private $endpoint;
    private $domain;
    
    public function __construct()
    {
        $config = config('ai_travel_photo.oss');
        
        $this->bucket = $config['bucket'];
        $this->endpoint = $config['endpoint'];
        $this->domain = $config['domain'] ?: ('https://' . $this->bucket . '.' . $this->endpoint);
        
        try {
            $this->ossClient = new OssClient(
                $config['access_key_id'],
                $config['access_key_secret'],
                $this->endpoint
            );
        } catch (OssException $e) {
            throw new \Exception('OSS初始化失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 上传文件
     * @param string $localFile 本地文件路径
     * @param string $ossPath OSS存储路径
     * @return string 文件URL
     */
    public function uploadFile(string $localFile, string $ossPath): string
    {
        try {
            $this->ossClient->uploadFile($this->bucket, $ossPath, $localFile);
            return $this->domain . '/' . $ossPath;
        } catch (OssException $e) {
            throw new \Exception('文件上传失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 上传文件内容
     * @param string $content 文件内容
     * @param string $ossPath OSS存储路径
     * @return string 文件URL
     */
    public function putObject(string $content, string $ossPath): string
    {
        try {
            $this->ossClient->putObject($this->bucket, $ossPath, $content);
            return $this->domain . '/' . $ossPath;
        } catch (OssException $e) {
            throw new \Exception('文件上传失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除文件
     * @param string $ossPath OSS文件路径
     * @return bool
     */
    public function deleteFile(string $ossPath): bool
    {
        try {
            $this->ossClient->deleteObject($this->bucket, $ossPath);
            return true;
        } catch (OssException $e) {
            return false;
        }
    }
    
    /**
     * 检查文件是否存在
     * @param string $ossPath OSS文件路径
     * @return bool
     */
    public function doesObjectExist(string $ossPath): bool
    {
        try {
            return $this->ossClient->doesObjectExist($this->bucket, $ossPath);
        } catch (OssException $e) {
            return false;
        }
    }
    
    /**
     * 生成签名URL
     * @param string $ossPath OSS文件路径
     * @param int $timeout 过期时间（秒）
     * @return string 签名URL
     */
    public function getSignedUrl(string $ossPath, int $timeout = 3600): string
    {
        try {
            return $this->ossClient->signUrl($this->bucket, $ossPath, $timeout);
        } catch (OssException $e) {
            return '';
        }
    }
    
    /**
     * 从URL提取OSS路径
     * @param string $url 完整URL
     * @return string OSS路径
     */
    public function extractOssPath(string $url): string
    {
        $url = str_replace($this->domain . '/', '', $url);
        return $url;
    }
}

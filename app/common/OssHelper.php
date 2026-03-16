<?php
declare(strict_types=1);

namespace app\common;

use OSS\OssClient;
use OSS\Core\OssException;
use think\facade\Db;

/**
 * 云存储上传辅助类
 * 支持阿里云OSS和腾讯云COS
 * Class OssHelper
 * @package app\common
 */
class OssHelper
{
    private $storageType; // 2=阿里云, 3=七牛云, 4=腾讯云COS
    private $ossClient;
    private $cosClient;
    private $bucket;
    private $domain;
    private $config;
    
    public function __construct()
    {
        // 从系统附件设置中获取存储配置
        $remoteSet = Db::name('sysset')->where('name', 'remote')->value('value');
        $remoteSet = $remoteSet ? json_decode($remoteSet, true) : [];
        
        if (empty($remoteSet) || empty($remoteSet['type'])) {
            throw new \Exception('存储未配置，请先在系统设置中配置附件存储');
        }
        
        $this->storageType = $remoteSet['type'];
        $this->config = $remoteSet;
        
        // 根据存储类型初始化
        if ($this->storageType == 2) {
            // 阿里云OSS
            $this->initAliyunOss($remoteSet);
        } elseif ($this->storageType == 4) {
            // 腾讯云COS
            $this->initQcloudCos($remoteSet);
        } elseif ($this->storageType == 3) {
            // 七牛云
            $this->initQiniu($remoteSet);
        } else {
            throw new \Exception('暂不支持的存储类型，请配置阿里云OSS或腾讯云COS');
        }
    }
    
    /**
     * 初始化阿里云OSS
     */
    private function initAliyunOss($remoteSet)
    {
        if (empty($remoteSet['alioss'])) {
            throw new \Exception('阿里云OSS配置不存在');
        }
        
        $aliOssConf = $remoteSet['alioss'];
        
        if (empty($aliOssConf['key']) || empty($aliOssConf['secret']) || empty($aliOssConf['bucket'])) {
            throw new \Exception('阿里云OSS配置不完整');
        }
        
        $this->bucket = $aliOssConf['bucket'];
        $this->domain = $aliOssConf['url'] ?? ('https://' . $this->bucket . '.' . $aliOssConf['ossurl']);
        
        try {
            $endpoint = 'http://' . $aliOssConf['ossurl'];
            $this->ossClient = new OssClient(
                $aliOssConf['key'],
                $aliOssConf['secret'],
                $endpoint
            );
        } catch (OssException $e) {
            throw new \Exception('阿里云OSS初始化失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 初始化腾讯云COS
     */
    private function initQcloudCos($remoteSet)
    {
        if (empty($remoteSet['cos'])) {
            throw new \Exception('腾讯云COS配置不存在');
        }
        
        $cosConf = $remoteSet['cos'];
        
        if (empty($cosConf['secretid']) || empty($cosConf['secretkey']) || empty($cosConf['bucket'])) {
            throw new \Exception('腾讯云COS配置不完整');
        }
        
        $this->bucket = $cosConf['bucket'];
        // 腾讯云COS的域名格式
        $this->domain = $cosConf['url'] ?? ('https://' . $this->bucket . '.cos.' . ($cosConf['local'] ?? 'ap-guangzhou') . '.myqcloud.com');
        
        // 使用腾讯云COS SDK
        $secretId = $cosConf['secretid'];
        $secretKey = $cosConf['secretkey'];
        $region = $cosConf['local'] ?? 'ap-guangzhou';
        
        try {
            require_once ROOT_PATH . 'vendor/qcloud/cos-sdk-v5/include.php';
            $this->cosClient = new \Qcloud\Cos\Client([
                'region' => $region,
                'credentials' => [
                    'secretId' => $secretId,
                    'secretKey' => $secretKey,
                ],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('腾讯云COS初始化失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 初始化七牛云
     */
    private function initQiniu($remoteSet)
    {
        if (empty($remoteSet['qiniu'])) {
            throw new \Exception('七牛云配置不存在');
        }
        
        $qiniuConf = $remoteSet['qiniu'];
        
        if (empty($qiniuConf['accesskey']) || empty($qiniuConf['secretkey']) || empty($qiniuConf['bucket'])) {
            throw new \Exception('七牛云配置不完整');
        }
        
        $this->bucket = $qiniuConf['bucket'];
        $this->domain = $qiniuConf['url'] ?? '';
        
        // 七牛云配置存储在config中，稍后使用
    }
    
    /**
     * 上传文件
     * @param string $localFile 本地文件路径
     * @param string $ossPath 存储路径
     * @return string 文件URL
     */
    public function uploadFile(string $localFile, string $ossPath): string
    {
        if ($this->storageType == 2) {
            // 阿里云OSS
            return $this->uploadToAliyun($localFile, $ossPath);
        } elseif ($this->storageType == 4) {
            // 腾讯云COS
            return $this->uploadToQcloud($localFile, $ossPath);
        } elseif ($this->storageType == 3) {
            // 七牛云
            return $this->uploadToQiniu($localFile, $ossPath);
        }
        
        throw new \Exception('不支持的存储类型');
    }
    
    /**
     * 上传到阿里云OSS
     */
    private function uploadToAliyun(string $localFile, string $ossPath): string
    {
        try {
            $this->ossClient->uploadFile($this->bucket, $ossPath, $localFile);
            return $this->domain . '/' . $ossPath;
        } catch (OssException $e) {
            throw new \Exception('阿里云OSS上传失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 上传到腾讯云COS
     */
    private function uploadToQcloud(string $localFile, string $ossPath): string
    {
        try {
            $this->cosClient->upload(
                $this->bucket,
                $ossPath,
                fopen($localFile, 'r')
            );
            return $this->domain . '/' . $ossPath;
        } catch (\Exception $e) {
            throw new \Exception('腾讯云COS上传失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 上传到七牛云
     */
    private function uploadToQiniu(string $localFile, string $ossPath): string
    {
        $qiniuConf = $this->config['qiniu'];
        
        require_once ROOT_PATH . 'vendor/qiniu/php-sdk/autoload.php';
        
        $auth = new \Qiniu\Auth($qiniuConf['accesskey'], $qiniuConf['secretkey']);
        $uploadManager = new \Qiniu\Storage\UploadManager();
        
        $token = $auth->uploadToken($qiniuConf['bucket']);
        list($ret, $err) = $uploadManager->putFile($token, $ossPath, $localFile);
        
        if ($err !== null) {
            throw new \Exception('七牛云上传失败: ' . $err->message());
        }
        
        return $this->domain . '/' . $ossPath;
    }
    
    /**
     * 上传文件内容
     * @param string $content 文件内容
     * @param string $ossPath 存储路径
     * @return string 文件URL
     */
    public function putObject(string $content, string $ossPath): string
    {
        if ($this->storageType == 2) {
            try {
                $this->ossClient->putObject($this->bucket, $ossPath, $content);
                return $this->domain . '/' . $ossPath;
            } catch (OssException $e) {
                throw new \Exception('文件上传失败: ' . $e->getMessage());
            }
        } elseif ($this->storageType == 4) {
            try {
                $this->cosClient->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => $ossPath,
                    'Body' => $content
                ]);
                return $this->domain . '/' . $ossPath;
            } catch (\Exception $e) {
                throw new \Exception('文件上传失败: ' . $e->getMessage());
            }
        }
        
        throw new \Exception('此存储类型不支持putObject');
    }
    
    /**
     * 删除文件
     * @param string $ossPath 文件路径
     * @return bool
     */
    public function deleteFile(string $ossPath): bool
    {
        if ($this->storageType == 2) {
            try {
                $this->ossClient->deleteObject($this->bucket, $ossPath);
                return true;
            } catch (OssException $e) {
                throw new \Exception('文件删除失败: ' . $e->getMessage());
            }
        } elseif ($this->storageType == 4) {
            try {
                $this->cosClient->deleteObject([
                    'Bucket' => $this->bucket,
                    'Key' => $ossPath
                ]);
                return true;
            } catch (\Exception $e) {
                throw new \Exception('文件删除失败: ' . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * 获取签名URL
     * @param string $ossPath 文件路径
     * @param int $expires 过期时间（秒）
     * @return string
     */
    public function getSignedUrl(string $ossPath, int $expires = 3600): string
    {
        if ($this->storageType == 2) {
            try {
                return $this->ossClient->getSignedUrl($this->bucket, $ossPath, $expires);
            } catch (OssException $e) {
                throw new \Exception('获取签名URL失败: ' . $e->getMessage());
            }
        } elseif ($this->storageType == 4) {
            try {
                $signedUrl = $this->cosClient->getObjectUrl([
                    'Bucket' => $this->bucket,
                    'Key' => $ossPath,
                    'Expires' => $expires
                ]);
                return $signedUrl;
            } catch (\Exception $e) {
                throw new \Exception('获取签名URL失败: ' . $e->getMessage());
            }
        }
        
        throw new \Exception('此存储类型不支持签名URL');
    }
}

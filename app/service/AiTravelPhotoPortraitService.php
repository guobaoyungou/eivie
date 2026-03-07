<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoQrcode;
use app\common\OssHelper;
use think\exception\ValidateException;
use think\facade\Queue;

/**
 * AI旅拍-人像管理服务
 * Class AiTravelPhotoPortraitService
 * @package app\service
 */
class AiTravelPhotoPortraitService
{
    private $ossHelper;
    
    public function __construct()
    {
        $this->ossHelper = new OssHelper();
    }
    
    /**
     * 上传人像
     * @param array $fileInfo 文件信息
     * @param array $params 参数
     * @return array
     */
    public function uploadPortrait(array $fileInfo, array $params): array
    {
        // 计算文件MD5
        $md5 = md5_file($fileInfo['tmp_name']);
        
        // 检查MD5是否重复
        $existPortrait = AiTravelPhotoPortrait::where('md5', $md5)
            ->where('bid', $params['bid'])
            ->where('status', '<>', AiTravelPhotoPortrait::STATUS_DELETED)
            ->find();
        
        if ($existPortrait) {
            // 文件已存在，直接返回
            return [
                'portrait_id' => $existPortrait->id,
                'original_url' => $existPortrait->original_url,
                'status' => 'exists',
                'message' => '文件已存在'
            ];
        }
        
        // 解析EXIF信息
        $exifData = $this->extractExif($fileInfo['tmp_name']);
        
        // 获取图片尺寸
        $imageInfo = getimagesize($fileInfo['tmp_name']);
        $width = $imageInfo[0] ?? 0;
        $height = $imageInfo[1] ?? 0;
        
        // 生成OSS路径
        $ossPath = $this->generateOssPath($fileInfo['name'], 'original');
        
        // 上传到OSS
        try {
            $originalUrl = $this->ossHelper->uploadFile($fileInfo['tmp_name'], $ossPath);
        } catch (\Exception $e) {
            throw new ValidateException('文件上传失败: ' . $e->getMessage());
        }
        
        // 生成缩略图
        $thumbnailUrl = $this->generateThumbnail($fileInfo['tmp_name'], $md5);
        
        // 创建人像记录
        $portrait = AiTravelPhotoPortrait::create([
            'aid' => $params['aid'],
            'uid' => $params['uid'] ?? 0,
            'bid' => $params['bid'],
            'mdid' => $params['mdid'] ?? 0,
            'device_id' => $params['device_id'] ?? 0,
            'type' => $params['type'] ?? AiTravelPhotoPortrait::TYPE_BUSINESS,
            'original_url' => $originalUrl,
            'thumbnail_url' => $thumbnailUrl,
            'file_name' => $fileInfo['name'],
            'file_size' => $fileInfo['size'],
            'width' => $width,
            'height' => $height,
            'md5' => $md5,
            'exif_data' => json_encode($exifData),
            'shoot_time' => $params['shoot_time'] ?? ($exifData['DateTimeOriginal'] ?? time()),
            'desc' => $params['desc'] ?? '',
            'tags' => $params['tags'] ?? '',
            'status' => AiTravelPhotoPortrait::STATUS_NORMAL,
        ]);
        
        // 更新设备上传统计
        if (!empty($params['device_id'])) {
            $deviceService = new AiTravelPhotoDeviceService();
            $deviceService->updateUploadStats($params['device_id'], true);
        }
        
        // 推送抠图任务到队列
        $this->pushCutoutJob($portrait->id);
        
        // 生成二维码
        $this->generateQrcode($portrait->id);
        
        return [
            'portrait_id' => $portrait->id,
            'original_url' => $portrait->original_url,
            'thumbnail_url' => $portrait->thumbnail_url,
            'status' => 'success',
            'message' => '上传成功'
        ];
    }
    
    /**
     * 提取EXIF信息
     * @param string $filePath 文件路径
     * @return array
     */
    private function extractExif(string $filePath): array
    {
        $exifData = [];
        
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($filePath, 'ANY_TAG', true);
                if ($exif) {
                    // 提取常用信息
                    $exifData = [
                        'Make' => $exif['IFD0']['Make'] ?? '',
                        'Model' => $exif['IFD0']['Model'] ?? '',
                        'DateTime' => $exif['IFD0']['DateTime'] ?? '',
                        'DateTimeOriginal' => isset($exif['EXIF']['DateTimeOriginal']) ? strtotime($exif['EXIF']['DateTimeOriginal']) : 0,
                        'ExposureTime' => $exif['EXIF']['ExposureTime'] ?? '',
                        'FNumber' => $exif['EXIF']['FNumber'] ?? '',
                        'ISOSpeedRatings' => $exif['EXIF']['ISOSpeedRatings'] ?? '',
                        'FocalLength' => $exif['EXIF']['FocalLength'] ?? '',
                    ];
                }
            } catch (\Exception $e) {
                // 忽略EXIF读取错误
            }
        }
        
        return $exifData;
    }
    
    /**
     * 生成缩略图
     * @param string $sourceFile 源文件
     * @param string $md5 文件MD5
     * @return string 缩略图URL
     */
    private function generateThumbnail(string $sourceFile, string $md5): string
    {
        $thumbnailWidth = config('ai_travel_photo.image.thumbnail_width', 800);
        
        // 获取原图信息
        $imageInfo = getimagesize($sourceFile);
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // 如果宽度小于缩略图宽度，不生成缩略图
        if ($srcWidth <= $thumbnailWidth) {
            return '';
        }
        
        // 计算缩略图尺寸
        $thumbnailHeight = intval($srcHeight * $thumbnailWidth / $srcWidth);
        
        // 创建源图像
        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = imagecreatefromjpeg($sourceFile);
                break;
            case 'image/png':
                $srcImage = imagecreatefrompng($sourceFile);
                break;
            default:
                return '';
        }
        
        // 创建缩略图
        $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
        imagecopyresampled($thumbnail, $srcImage, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $srcWidth, $srcHeight);
        
        // 保存到临时文件
        $tempFile = sys_get_temp_dir() . '/' . $md5 . '_thumb.jpg';
        imagejpeg($thumbnail, $tempFile, config('ai_travel_photo.image.quality', 90));
        
        // 释放资源
        imagedestroy($srcImage);
        imagedestroy($thumbnail);
        
        // 上传到OSS
        try {
            $ossPath = $this->generateOssPath($md5 . '_thumb.jpg', 'thumbnail');
            $thumbnailUrl = $this->ossHelper->uploadFile($tempFile, $ossPath);
            
            // 删除临时文件
            @unlink($tempFile);
            
            return $thumbnailUrl;
        } catch (\Exception $e) {
            @unlink($tempFile);
            return '';
        }
    }
    
    /**
     * 生成OSS路径
     * @param string $fileName 文件名
     * @param string $type 类型
     * @return string
     */
    private function generateOssPath(string $fileName, string $type = 'original'): string
    {
        $basePath = config('ai_travel_photo.oss.ai_travel_photo_path', 'ai_travel_photo/');
        $date = date('Ymd');
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        // uniqid() 的第一个参数必须是字符串类型
        $uniqueName = md5(uniqid((string)mt_rand(), true)) . '.' . $ext;
        
        return $basePath . $type . '/' . $date . '/' . $uniqueName;
    }
    
    /**
     * 推送抠图任务到队列
     * @param int $portraitId 人像ID
     * @return void
     */
    private function pushCutoutJob(int $portraitId): void
    {
        $queueConfig = config('ai_travel_photo.queue.cutout');
        
        Queue::push('app\job\AiCutoutJob', [
            'portrait_id' => $portraitId,
        ], $queueConfig['name']);
    }
    
    /**
     * 生成二维码
     * @param int $portraitId 人像ID
     * @return void
     */
    private function generateQrcode(int $portraitId): void
    {
        $qrcodeService = new AiTravelPhotoQrcodeService();
        $qrcodeService->generateQrcode($portraitId);
    }
    
    /**
     * 获取人像列表
     * @param array $params 查询参数
     * @return array
     */
    public function getPortraitList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoPortrait::withSearch(['bid', 'uid', 'device_id', 'status', 'md5', 'create_time'], $params);
        
        // 关联设备信息
        if (!empty($params['with_device'])) {
            $query->with(['device']);
        }
        
        // 排序
        $query->order('create_time', 'desc');
        
        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
        
        return [
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'page_size' => $pageSize,
        ];
    }
    
    /**
     * 获取人像详情
     * @param int $portraitId 人像ID
     * @return array
     */
    public function getPortraitDetail(int $portraitId): array
    {
        $portrait = AiTravelPhotoPortrait::with(['device', 'generations', 'results', 'qrcodes'])->find($portraitId);
        
        if (!$portrait) {
            throw new ValidateException('人像不存在');
        }
        
        return $portrait->toArray();
    }
    
    /**
     * 删除人像
     * @param int $portraitId 人像ID
     * @return bool
     */
    public function deletePortrait(int $portraitId): bool
    {
        $portrait = AiTravelPhotoPortrait::find($portraitId);
        
        if (!$portrait) {
            throw new ValidateException('人像不存在');
        }
        
        // 软删除
        $portrait->status = AiTravelPhotoPortrait::STATUS_DELETED;
        return $portrait->save();
    }
}

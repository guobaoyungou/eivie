<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoQrcode;
use app\common\OssHelper;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Log;
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

    /**
     * 单条人像人脸特征补提
     *
     * 对指定 portrait_id 调用 InsightFace 提取特征，写入 MySQL + Milvus。
     * 由 FaceEmbeddingBackfillJob 队列消费者调用。
     *
     * @param int $portraitId 人像ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function backfillFaceEmbedding(int $portraitId): array
    {
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portraitId)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->field('id, original_url, face_embedding, face_embedding_id')
            ->find();

        if (!$portrait) {
            return ['success' => false, 'message' => '人像记录不存在或已禁用'];
        }

        if (empty($portrait['original_url'])) {
            return ['success' => false, 'message' => '原图URL为空，无法提取特征'];
        }

        // 跳过已有有效特征的记录
        $existingEmbedding = $portrait['face_embedding'] ?? '';
        if (!empty($existingEmbedding) && $existingEmbedding !== '[]') {
            $decoded = json_decode($existingEmbedding, true);
            if (is_array($decoded) && count($decoded) >= 64) {
                return ['success' => true, 'message' => '已有有效特征，跳过'];
            }
        }

        // 调用 InsightFace 提取特征
        try {
            $faceEmbeddingService = new FaceEmbeddingService();
            $faceResult = $faceEmbeddingService->extractFromUrl($portrait['original_url']);

            if (!$faceResult || empty($faceResult['embedding'])) {
                Log::info('补提特征：图片未检测到人脸', ['portrait_id' => $portraitId]);
                return ['success' => false, 'message' => '未检测到人脸'];
            }

            $embedding = $faceResult['embedding'];

            // 写入 MySQL
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'face_embedding' => json_encode($embedding),
                'update_time' => time(),
            ]);

            // 写入 Milvus
            try {
                $milvusService = new MilvusService();
                if ($milvusService->isHealthy()) {
                    // 如果已有旧的 Milvus 记录，先删除
                    $oldMilvusId = $portrait['face_embedding_id'] ?? 0;
                    if ($oldMilvusId) {
                        $milvusService->delete($oldMilvusId);
                    }
                    $vectorIds = $milvusService->insert([$embedding], ['portrait_id' => $portraitId]);
                    if (!empty($vectorIds)) {
                        Db::name('ai_travel_photo_portrait')->where('id', $portraitId)
                            ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                    }
                } else {
                    Log::warning('补提特征: Milvus不可用，MySQL已备份', ['portrait_id' => $portraitId]);
                }
            } catch (\Exception $e) {
                Log::warning('补提特征Milvus存储失败，MySQL已备份', [
                    'portrait_id' => $portraitId, 'error' => $e->getMessage(),
                ]);
            }

            Log::info('补提特征成功', [
                'portrait_id' => $portraitId,
                'dim' => $faceResult['dim'],
                'det_score' => $faceResult['det_score'],
            ]);

            return ['success' => true, 'message' => '特征提取并入库成功'];
        } catch (\Exception $e) {
            Log::error('补提特征失败', [
                'portrait_id' => $portraitId, 'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => '特征提取失败: ' . $e->getMessage()];
        }
    }

    /**
     * 查询无特征的人像列表（供批量补提）
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $limit 最大返回数量
     * @return array portrait 记录列表
     */
    public function getPortraitsWithoutEmbedding(int $aid, int $bid, int $limit = 100): array
    {
        return Db::name('ai_travel_photo_portrait')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where(function ($query) {
                $query->whereNull('face_embedding')
                    ->whereOr('face_embedding', '')
                    ->whereOr('face_embedding', '[]');
            })
            ->where('original_url', '<>', '')
            ->field('id, original_url')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 批量投递特征补提任务到队列
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $limit 最大处理数量
     * @return array ['total' => int, 'queued' => int]
     */
    public function batchQueueBackfill(int $aid, int $bid, int $limit = 100): array
    {
        $portraits = $this->getPortraitsWithoutEmbedding($aid, $bid, $limit);
        $total = count($portraits);
        $queued = 0;

        foreach ($portraits as $portrait) {
            try {
                Queue::push(
                    'app\job\FaceEmbeddingBackfillJob',
                    ['portrait_id' => (int)$portrait['id']],
                    'face_embedding_backfill'
                );
                $queued++;
            } catch (\Exception $e) {
                Log::warning('补提任务投递失败', [
                    'portrait_id' => $portrait['id'], 'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('批量补提任务已投递', [
            'aid' => $aid, 'bid' => $bid, 'total' => $total, 'queued' => $queued,
        ]);

        return ['total' => $total, 'queued' => $queued];
    }

    /**
     * 将 MySQL 已有特征但未同步到 Milvus 的记录批量同步
     *
     * 说明：Milvus 服务恢复后，历史数据的 face_embedding 已存在于 MySQL，
     * 但 face_embedding_id 为空/0，需要将向量同步插入到 Milvus 并回写 ID。
     * 无需重新调用 InsightFace，直接读取 MySQL 中的向量数据。
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $limit 单次最大处理数量
     * @return array ['total' => int, 'synced' => int, 'failed' => int, 'errors' => array]
     */
    public function syncEmbeddingsToMilvus(int $aid, int $bid, int $limit = 200): array
    {
        $milvusService = new MilvusService();
        if (!$milvusService->isHealthy()) {
            return ['total' => 0, 'synced' => 0, 'failed' => 0, 'errors' => ['Milvus 服务不可用']];
        }

        // 查找 MySQL 有特征但 Milvus 无 ID 的记录
        $portraits = Db::name('ai_travel_photo_portrait')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where(function ($q) {
                $q->whereNull('face_embedding_id')
                  ->whereOr('face_embedding_id', '=', 0);
            })
            ->whereRaw('face_embedding IS NOT NULL AND face_embedding != \'\' AND face_embedding != \'[]\'  AND LENGTH(face_embedding) > 10')
            ->field('id, face_embedding, face_embedding_id')
            ->order('id', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();

        $total = count($portraits);
        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($portraits as $portrait) {
            try {
                $embedding = json_decode($portrait['face_embedding'], true);
                if (!is_array($embedding) || count($embedding) < 64) {
                    $errors[] = "ID {$portrait['id']}: 特征数据无效";
                    $failed++;
                    continue;
                }

                $vectorIds = $milvusService->insert([$embedding], ['portrait_id' => (int)$portrait['id']]);
                if (!empty($vectorIds)) {
                    Db::name('ai_travel_photo_portrait')
                        ->where('id', $portrait['id'])
                        ->update([
                            'face_embedding_id' => $vectorIds[0] ?? 0,
                            'update_time' => time(),
                        ]);
                    $synced++;
                } else {
                    $errors[] = "ID {$portrait['id']}: Milvus 插入未返回向量 ID";
                    $failed++;
                }
            } catch (\Exception $e) {
                $errors[] = "ID {$portrait['id']}: " . $e->getMessage();
                $failed++;
                Log::warning('Milvus同步失败', [
                    'portrait_id' => $portrait['id'], 'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('MySQL→Milvus 批量同步完成', [
            'aid' => $aid, 'bid' => $bid, 'total' => $total, 'synced' => $synced, 'failed' => $failed,
        ]);

        return ['total' => $total, 'synced' => $synced, 'failed' => $failed, 'errors' => $errors];
    }
}

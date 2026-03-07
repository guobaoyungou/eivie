<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoUserAlbum;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoOrder;
use app\common\OssHelper;
use think\facade\Cache;
use think\facade\Db;

/**
 * 用户相册服务类
 * 管理用户购买后的照片相册，提供查看、下载、分享功能
 */
class AiTravelPhotoAlbumService
{
    protected $ossHelper;

    public function __construct()
    {
        $this->ossHelper = new OssHelper();
    }

    /**
     * 获取用户相册列表
     * 
     * @param int $uid 用户ID
     * @param array $params 查询参数
     * @return array
     */
    public function getAlbumList(int $uid, array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoUserAlbum::where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL);
        
        // 按内容类型筛选
        if (isset($params['content_type'])) {
            $query->where('content_type', $params['content_type']);
        }
        
        // 按收藏筛选
        if (isset($params['is_favorite']) && $params['is_favorite'] == 1) {
            $query->where('is_favorite', 1);
        }
        
        // 按关键词搜索（搜索场景名称）
        if (!empty($params['keyword'])) {
            $query->where(function($q) use ($params) {
                $q->whereHas('result', function($sq) use ($params) {
                    $sq->whereHas('scene', function($ssq) use ($params) {
                        $ssq->where('scene_name', 'like', '%' . $params['keyword'] . '%');
                    });
                });
            });
        }
        
        // 分页查询
        $list = $query->with([
            'result' => function($q) {
                $q->with(['scene', 'portrait']);
            },
            'order'
        ])
        ->order('add_time', 'desc')
        ->page($page, $pageSize)
        ->select();
        
        $total = $query->count();
        
        // 格式化数据
        $formattedList = [];
        foreach ($list as $item) {
            $result = $item->result;
            if (!$result) {
                continue;
            }
            
            $formattedList[] = [
                'album_id' => $item->id,
                'content_type' => $item->content_type,
                'content_type_text' => $item->content_type == AiTravelPhotoUserAlbum::CONTENT_TYPE_IMAGE ? '图片' : '视频',
                'result_id' => $result->id,
                'result_url' => $result->result_url,
                'thumbnail_url' => $result->thumbnail_url ?? $result->result_url,
                'scene_name' => $result->scene->scene_name ?? '',
                'portrait_url' => $result->portrait->thumbnail_url ?? '',
                'is_favorite' => $item->is_favorite,
                'view_count' => $item->view_count,
                'download_count' => $item->download_count,
                'add_time' => $item->add_time,
                'add_time_text' => date('Y-m-d H:i:s', $item->add_time),
                'order_no' => $item->order->order_no ?? '',
            ];
        }
        
        return [
            'list' => $formattedList,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
        ];
    }

    /**
     * 获取相册详情
     * 
     * @param int $albumId 相册ID
     * @param int $uid 用户ID
     * @return array
     * @throws \Exception
     */
    public function getAlbumDetail(int $albumId, int $uid): array
    {
        $album = AiTravelPhotoUserAlbum::where('id', $albumId)
            ->where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->with(['result.scene', 'result.portrait', 'order'])
            ->find();
        
        if (!$album) {
            throw new \Exception('相册记录不存在');
        }
        
        $result = $album->result;
        if (!$result) {
            throw new \Exception('结果文件不存在');
        }
        
        // 更新查看次数
        $this->updateViewCount($albumId);
        
        return [
            'album_id' => $album->id,
            'content_type' => $album->content_type,
            'content_type_text' => $album->content_type == AiTravelPhotoUserAlbum::CONTENT_TYPE_IMAGE ? '图片' : '视频',
            'result_id' => $result->id,
            'result_url' => $result->result_url,
            'result_no_watermark_url' => $result->result_no_watermark_url,
            'thumbnail_url' => $result->thumbnail_url ?? $result->result_url,
            'scene_id' => $result->scene_id,
            'scene_name' => $result->scene->scene_name ?? '',
            'scene_cover' => $result->scene->scene_cover ?? '',
            'portrait_id' => $result->portrait_id,
            'portrait_url' => $result->portrait->original_url ?? '',
            'portrait_thumbnail' => $result->portrait->thumbnail_url ?? '',
            'is_favorite' => $album->is_favorite,
            'view_count' => $album->view_count,
            'download_count' => $album->download_count,
            'share_count' => $album->share_count,
            'add_time' => $album->add_time,
            'add_time_text' => date('Y-m-d H:i:s', $album->add_time),
            'order_no' => $album->order->order_no ?? '',
            'order_amount' => $album->order->order_amount ?? 0,
        ];
    }

    /**
     * 下载照片（无水印原图）
     * 
     * @param int $albumId 相册ID
     * @param int $uid 用户ID
     * @return array
     * @throws \Exception
     */
    public function downloadPhoto(int $albumId, int $uid): array
    {
        $album = AiTravelPhotoUserAlbum::where('id', $albumId)
            ->where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->with(['result'])
            ->find();
        
        if (!$album) {
            throw new \Exception('相册记录不存在');
        }
        
        $result = $album->result;
        if (!$result) {
            throw new \Exception('结果文件不存在');
        }
        
        // 获取无水印原图URL（如果存在）
        $downloadUrl = $result->result_no_watermark_url ?: $result->result_url;
        
        // 生成签名URL（有效期1小时）
        $signedUrl = $this->ossHelper->getSignedUrl(
            str_replace($this->ossHelper->domain . '/', '', $downloadUrl),
            3600
        );
        
        // 更新下载次数
        $this->updateDownloadCount($albumId);
        
        return [
            'download_url' => $signedUrl,
            'filename' => $this->generateFilename($result, $album),
            'file_size' => $result->file_size ?? 0,
            'content_type' => $album->content_type,
        ];
    }

    /**
     * 批量下载照片
     * 
     * @param array $albumIds 相册ID数组
     * @param int $uid 用户ID
     * @return array
     * @throws \Exception
     */
    public function batchDownload(array $albumIds, int $uid): array
    {
        if (empty($albumIds)) {
            throw new \Exception('请选择要下载的照片');
        }
        
        if (count($albumIds) > 50) {
            throw new \Exception('单次最多下载50张照片');
        }
        
        $albums = AiTravelPhotoUserAlbum::where('uid', $uid)
            ->whereIn('id', $albumIds)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->with(['result'])
            ->select();
        
        if ($albums->isEmpty()) {
            throw new \Exception('没有可下载的照片');
        }
        
        $downloadList = [];
        foreach ($albums as $album) {
            $result = $album->result;
            if (!$result) {
                continue;
            }
            
            $downloadUrl = $result->result_no_watermark_url ?: $result->result_url;
            $signedUrl = $this->ossHelper->getSignedUrl(
                str_replace($this->ossHelper->domain . '/', '', $downloadUrl),
                3600
            );
            
            $downloadList[] = [
                'album_id' => $album->id,
                'download_url' => $signedUrl,
                'filename' => $this->generateFilename($result, $album),
            ];
            
            // 更新下载次数
            $this->updateDownloadCount($album->id);
        }
        
        return [
            'total' => count($downloadList),
            'list' => $downloadList,
            'expire_time' => time() + 3600,
        ];
    }

    /**
     * 生成分享链接
     * 
     * @param int $albumId 相册ID
     * @param int $uid 用户ID
     * @param int $expireTime 过期时间（秒）
     * @return array
     * @throws \Exception
     */
    public function generateShareLink(int $albumId, int $uid, int $expireTime = 86400): array
    {
        $album = AiTravelPhotoUserAlbum::where('id', $albumId)
            ->where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->find();
        
        if (!$album) {
            throw new \Exception('相册记录不存在');
        }
        
        // 生成分享token
        $shareToken = md5($albumId . $uid . time() . uniqid());
        
        // 缓存分享信息
        $cacheKey = 'album_share:' . $shareToken;
        Cache::set($cacheKey, [
            'album_id' => $albumId,
            'uid' => $uid,
            'create_time' => time(),
        ], $expireTime);
        
        // 更新分享次数
        $album->save(['share_count' => $album->share_count + 1]);
        
        $shareUrl = request()->domain() . '/pages/album/share?token=' . $shareToken;
        
        return [
            'share_token' => $shareToken,
            'share_url' => $shareUrl,
            'expire_time' => time() + $expireTime,
            'expire_time_text' => date('Y-m-d H:i:s', time() + $expireTime),
        ];
    }

    /**
     * 获取分享的相册详情
     * 
     * @param string $shareToken 分享token
     * @return array
     * @throws \Exception
     */
    public function getSharedAlbum(string $shareToken): array
    {
        $cacheKey = 'album_share:' . $shareToken;
        $shareData = Cache::get($cacheKey);
        
        if (!$shareData) {
            throw new \Exception('分享链接已过期或不存在');
        }
        
        $album = AiTravelPhotoUserAlbum::where('id', $shareData['album_id'])
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->with(['result.scene', 'result.portrait'])
            ->find();
        
        if (!$album) {
            throw new \Exception('相册记录不存在');
        }
        
        $result = $album->result;
        
        return [
            'content_type' => $album->content_type,
            'result_url' => $result->result_url,
            'thumbnail_url' => $result->thumbnail_url ?? $result->result_url,
            'scene_name' => $result->scene->scene_name ?? '',
            'add_time_text' => date('Y-m-d H:i:s', $album->add_time),
            'share_time_text' => date('Y-m-d H:i:s', $shareData['create_time']),
        ];
    }

    /**
     * 设置/取消收藏
     * 
     * @param int $albumId 相册ID
     * @param int $uid 用户ID
     * @param bool $isFavorite 是否收藏
     * @return bool
     * @throws \Exception
     */
    public function setFavorite(int $albumId, int $uid, bool $isFavorite): bool
    {
        $album = AiTravelPhotoUserAlbum::where('id', $albumId)
            ->where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->find();
        
        if (!$album) {
            throw new \Exception('相册记录不存在');
        }
        
        $album->is_favorite = $isFavorite ? 1 : 0;
        return $album->save();
    }

    /**
     * 删除相册（软删除）
     * 
     * @param int $albumId 相册ID
     * @param int $uid 用户ID
     * @return bool
     * @throws \Exception
     */
    public function deleteAlbum(int $albumId, int $uid): bool
    {
        $album = AiTravelPhotoUserAlbum::where('id', $albumId)
            ->where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->find();
        
        if (!$album) {
            throw new \Exception('相册记录不存在');
        }
        
        $album->status = AiTravelPhotoUserAlbum::STATUS_DELETED;
        return $album->save();
    }

    /**
     * 批量删除相册
     * 
     * @param array $albumIds 相册ID数组
     * @param int $uid 用户ID
     * @return int 删除数量
     */
    public function batchDelete(array $albumIds, int $uid): int
    {
        return AiTravelPhotoUserAlbum::where('uid', $uid)
            ->whereIn('id', $albumIds)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL)
            ->update(['status' => AiTravelPhotoUserAlbum::STATUS_DELETED]);
    }

    /**
     * 获取相册统计信息
     * 
     * @param int $uid 用户ID
     * @return array
     */
    public function getAlbumStats(int $uid): array
    {
        $query = AiTravelPhotoUserAlbum::where('uid', $uid)
            ->where('status', AiTravelPhotoUserAlbum::STATUS_NORMAL);
        
        $totalCount = $query->count();
        $imageCount = (clone $query)->where('content_type', AiTravelPhotoUserAlbum::CONTENT_TYPE_IMAGE)->count();
        $videoCount = (clone $query)->where('content_type', AiTravelPhotoUserAlbum::CONTENT_TYPE_VIDEO)->count();
        $favoriteCount = (clone $query)->where('is_favorite', 1)->count();
        
        // 总查看次数
        $totalViewCount = (clone $query)->sum('view_count');
        
        // 总下载次数
        $totalDownloadCount = (clone $query)->sum('download_count');
        
        // 最近购买时间
        $latestAlbum = (clone $query)->order('add_time', 'desc')->find();
        
        return [
            'total_count' => $totalCount,
            'image_count' => $imageCount,
            'video_count' => $videoCount,
            'favorite_count' => $favoriteCount,
            'total_view_count' => $totalViewCount,
            'total_download_count' => $totalDownloadCount,
            'latest_time' => $latestAlbum ? $latestAlbum->add_time : 0,
            'latest_time_text' => $latestAlbum ? date('Y-m-d H:i:s', $latestAlbum->add_time) : '',
        ];
    }

    /**
     * 更新查看次数
     * 
     * @param int $albumId 相册ID
     * @return void
     */
    private function updateViewCount(int $albumId): void
    {
        // 使用原生SQL避免模型事件
        Db::name('ai_travel_photo_user_album')
            ->where('id', $albumId)
            ->inc('view_count')
            ->update();
    }

    /**
     * 更新下载次数
     * 
     * @param int $albumId 相册ID
     * @return void
     */
    private function updateDownloadCount(int $albumId): void
    {
        Db::name('ai_travel_photo_user_album')
            ->where('id', $albumId)
            ->inc('download_count')
            ->update();
    }

    /**
     * 生成下载文件名
     * 
     * @param AiTravelPhotoResult $result 结果对象
     * @param AiTravelPhotoUserAlbum $album 相册对象
     * @return string
     */
    private function generateFilename($result, $album): string
    {
        $scene = $result->scene;
        $sceneName = $scene ? $scene->scene_name : 'photo';
        
        // 清理场景名称中的特殊字符
        $sceneName = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}_-]/u', '', $sceneName);
        
        $ext = $album->content_type == AiTravelPhotoUserAlbum::CONTENT_TYPE_IMAGE ? 'jpg' : 'mp4';
        $timestamp = date('YmdHis', $album->add_time);
        
        return "{$sceneName}_{$timestamp}.{$ext}";
    }
}

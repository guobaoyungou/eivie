<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdAttachment;

/**
 * 大屏互动 - 相册PPT服务
 * 功能：相册设置、照片管理
 */
class HdAlbumService
{
    /**
     * 获取相册设置（xiangce feature config）
     */
    public function getAlbumConfig(int $aid, int $bid, int $activityId): array
    {
        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'xiangce')->find();

        $config = $feature ? ($feature->config ?: []) : [];

        return [
            'code' => 0,
            'data' => [
                'enabled'        => $feature ? $feature->enabled : 0,
                'auto_play'      => $config['auto_play'] ?? 1,
                'play_interval'  => $config['play_interval'] ?? 5,
                'transition'     => $config['transition'] ?? 'fade',
                'show_title'     => $config['show_title'] ?? 1,
            ],
        ];
    }

    /**
     * 更新相册设置
     */
    public function updateAlbumConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'xiangce')->find();

        if (!$feature) {
            $feature = new HdActivityFeature();
            $feature->aid = $aid;
            $feature->bid = $bid;
            $feature->activity_id = $activityId;
            $feature->feature_code = 'xiangce';
            $feature->sort = 15;
        }

        if (isset($data['enabled'])) $feature->enabled = (int)$data['enabled'];

        $config = $feature->config ?: [];
        $allowedKeys = ['auto_play', 'play_interval', 'transition', 'show_title'];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) $config[$key] = $data[$key];
        }
        $feature->config = $config;
        $feature->save();

        return ['code' => 0, 'msg' => '相册设置已更新'];
    }

    /**
     * 获取照片列表
     */
    public function getPhotos(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['activity_id', '=', $activityId],
            ['category', '=', 'album'],
        ];

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdAttachment::where($where)->page($page, $limit)
            ->order('id desc')->select()->toArray();
        $count = HdAttachment::where($where)->count();

        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 添加照片
     */
    public function addPhoto(int $aid, int $bid, int $activityId, array $data): array
    {
        $attachment = new HdAttachment();
        $attachment->aid = $aid;
        $attachment->bid = $bid;
        $attachment->activity_id = $activityId;
        $attachment->file_name = $data['file_name'] ?? '';
        $attachment->file_path = $data['file_path'] ?? '';
        $attachment->file_type = $data['file_type'] ?? 'image';
        $attachment->file_size = (int)($data['file_size'] ?? 0);
        $attachment->category = 'album';
        $attachment->createtime = time();
        $attachment->save();

        return ['code' => 0, 'msg' => '添加成功', 'data' => $attachment->toArray()];
    }

    /**
     * 批量添加照片
     */
    public function batchAddPhotos(int $aid, int $bid, int $activityId, array $photos): array
    {
        $now = time();
        $insertData = [];
        foreach ($photos as $photo) {
            $insertData[] = [
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'file_name'   => $photo['file_name'] ?? '',
                'file_path'   => $photo['file_path'] ?? '',
                'file_type'   => $photo['file_type'] ?? 'image',
                'file_size'   => (int)($photo['file_size'] ?? 0),
                'category'    => 'album',
                'createtime'  => $now,
            ];
        }

        if ($insertData) {
            Db::name('hd_attachment')->insertAll($insertData);
        }

        return ['code' => 0, 'msg' => '批量添加成功', 'data' => ['count' => count($insertData)]];
    }

    /**
     * 删除照片
     */
    public function deletePhoto(int $aid, int $bid, int $activityId, int $id): array
    {
        $att = HdAttachment::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$att) {
            return ['code' => 1, 'msg' => '照片不存在'];
        }
        $att->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 清空相册
     */
    public function clearAlbum(int $aid, int $bid, int $activityId): array
    {
        HdAttachment::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('category', 'album')->delete();
        return ['code' => 0, 'msg' => '相册已清空'];
    }
}

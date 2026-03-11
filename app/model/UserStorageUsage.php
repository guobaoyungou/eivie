<?php
/**
 * 用户存储用量模型
 * 记录每个用户的存储空间使用统计
 */
namespace app\model;

use think\facade\Db;

class UserStorageUsage
{
    protected $table = 'user_storage_usage';

    /**
     * 默认配额 5GB (字节)
     */
    const DEFAULT_QUOTA_BYTES = 5368709120;

    /**
     * 获取或创建用户存储用量记录
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public static function getOrCreate($aid, $mid)
    {
        $record = Db::name('user_storage_usage')
            ->where('aid', $aid)
            ->where('mid', $mid)
            ->find();

        if (!$record) {
            $now = time();
            $data = [
                'aid' => $aid,
                'mid' => $mid,
                'total_quota_bytes' => self::DEFAULT_QUOTA_BYTES,
                'used_bytes' => 0,
                'file_count' => 0,
                'image_count' => 0,
                'video_count' => 0,
                'last_warning_time' => 0,
                'updatetime' => $now,
                'createtime' => $now,
            ];
            $data['id'] = Db::name('user_storage_usage')->insertGetId($data);
            $record = $data;
        }

        return $record;
    }

    /**
     * 更新用量数据
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateUsage($id, $data)
    {
        $data['updatetime'] = time();
        return Db::name('user_storage_usage')->where('id', $id)->update($data) !== false;
    }

    /**
     * 增加用量
     * @param int $aid
     * @param int $mid
     * @param int $fileSize 文件大小(字节)
     * @param string $fileType image/video
     * @return array 更新后的用量记录
     */
    public static function incrementUsage($aid, $mid, $fileSize, $fileType = 'image')
    {
        $record = self::getOrCreate($aid, $mid);

        $updateData = [
            'used_bytes' => $record['used_bytes'] + $fileSize,
            'file_count' => $record['file_count'] + 1,
            'updatetime' => time(),
        ];

        if ($fileType === 'video') {
            $updateData['video_count'] = $record['video_count'] + 1;
        } else {
            $updateData['image_count'] = $record['image_count'] + 1;
        }

        self::updateUsage($record['id'], $updateData);

        // 同步更新 member 冗余字段
        Db::name('member')->where('id', $mid)->update([
            'storage_used_bytes' => $updateData['used_bytes']
        ]);

        return array_merge($record, $updateData);
    }

    /**
     * 减少用量
     * @param int $aid
     * @param int $mid
     * @param int $fileSize
     * @param string $fileType
     * @return array
     */
    public static function decrementUsage($aid, $mid, $fileSize, $fileType = 'image')
    {
        $record = self::getOrCreate($aid, $mid);

        $newUsed = max(0, $record['used_bytes'] - $fileSize);
        $newFileCount = max(0, $record['file_count'] - 1);

        $updateData = [
            'used_bytes' => $newUsed,
            'file_count' => $newFileCount,
            'updatetime' => time(),
        ];

        if ($fileType === 'video') {
            $updateData['video_count'] = max(0, $record['video_count'] - 1);
        } else {
            $updateData['image_count'] = max(0, $record['image_count'] - 1);
        }

        self::updateUsage($record['id'], $updateData);

        // 同步更新 member 冗余字段
        Db::name('member')->where('id', $mid)->update([
            'storage_used_bytes' => $newUsed
        ]);

        return array_merge($record, $updateData);
    }

    /**
     * 更新配额
     * @param int $aid
     * @param int $mid
     * @param int $quotaBytes
     * @return bool
     */
    public static function updateQuota($aid, $mid, $quotaBytes)
    {
        $record = self::getOrCreate($aid, $mid);
        return self::updateUsage($record['id'], [
            'total_quota_bytes' => $quotaBytes,
        ]);
    }

    /**
     * 重算用量（全量扫描 user_storage_file 表）
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public static function recalculateUsage($aid, $mid)
    {
        $stats = Db::name('user_storage_file')
            ->where('aid', $aid)
            ->where('mid', $mid)
            ->where('is_deleted', 0)
            ->field('COUNT(*) as file_count, COALESCE(SUM(file_size),0) as used_bytes, SUM(IF(file_type="image",1,0)) as image_count, SUM(IF(file_type="video",1,0)) as video_count')
            ->find();

        $record = self::getOrCreate($aid, $mid);

        $updateData = [
            'used_bytes' => intval($stats['used_bytes']),
            'file_count' => intval($stats['file_count']),
            'image_count' => intval($stats['image_count']),
            'video_count' => intval($stats['video_count']),
            'updatetime' => time(),
        ];

        self::updateUsage($record['id'], $updateData);

        // 同步更新 member 冗余字段
        Db::name('member')->where('id', $mid)->update([
            'storage_used_bytes' => $updateData['used_bytes']
        ]);

        return array_merge($record, $updateData);
    }
}

<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;

/**
 * 云空间检查服务
 * 
 * 处理合成生成时的云空间预检、用量更新、用量查询等操作。
 * 已用量通过实时聚合 portrait + result 表的 file_size 计算，不再依赖 business.cloud_space_used 缓存字段。
 */
class SpaceCheckService
{
    /**
     * 预估单次合成空间消耗（MB）
     */
    const ESTIMATE_IMAGE_SIZE_MB = 2;   // 图片合成预估2MB
    const ESTIMATE_VIDEO_SIZE_MB = 20;  // 视频合成预估20MB

    /**
     * 实时计算商户云空间已使用量（字节）
     * 合计：人像照片 file_size + 成片 file_size
     *
     * @param int $bid 商家ID
     * @return int 已用字节数
     */
    public static function calculateUsedBytes(int $bid): int
    {
        // 人像照片大小
        $portraitBytes = (int) Db::name('ai_travel_photo_portrait')
            ->where('bid', $bid)
            ->where('status', '>', 0)
            ->sum('file_size');

        // 成片（含水印、缩略图等同一条记录的综合文件）大小
        $resultBytes = (int) Db::name('ai_travel_photo_result')
            ->where('bid', $bid)
            ->where('status', '>', 0)
            ->sum('file_size');

        return $portraitBytes + $resultBytes;
    }

    /**
     * 实时计算商户云空间已使用量（MB）
     *
     * @param int $bid 商家ID
     * @return float 已用MB数
     */
    public static function calculateUsedMB(int $bid): float
    {
        $bytes = self::calculateUsedBytes($bid);
        return round($bytes / 1024 / 1024, 2);
    }

    /**
     * 空间预检（使用实时计算）
     *
     * @param int $bid 商家ID
     * @param float $requiredMB 所需空间（MB）
     * @return array ['allowed' => bool, 'remainingMB' => float, 'shortfallMB' => float]
     */
    public function checkSpace(int $bid, float $requiredMB): array
    {
        $business = Db::name('business')
            ->where('id', $bid)
            ->field('cloud_space')
            ->find();

        $totalMB = (float) ($business['cloud_space'] ?? 5120);
        $usedMB = self::calculateUsedMB($bid);
        $remainingMB = $totalMB - $usedMB;

        $allowed = $remainingMB >= $requiredMB;
        $shortfallMB = $allowed ? 0 : round($requiredMB - $remainingMB, 2);

        return [
            'allowed' => $allowed,
            'remainingMB' => round($remainingMB, 2),
            'shortfallMB' => $shortfallMB,
        ];
    }

    /**
     * 更新已用空间（向 business.cloud_space_used 同步缓存值）
     * 注意：此方法仅用于缓存同步，实际用量以 calculateUsedMB 实时聚合为准
     *
     * @param int $bid 商家ID
     * @param int $fileSizeBytes 文件大小（字节）
     * @return array ['status' => bool]
     */
    public function addUsage(int $bid, int $fileSizeBytes): array
    {
        if ($fileSizeBytes <= 0) {
            return ['status' => true];
        }

        // 同步更新缓存字段
        $usedMB = self::calculateUsedMB($bid);
        Db::name('business')
            ->where('id', $bid)
            ->update(['cloud_space_used' => $usedMB]);

        Log::info("SpaceCheckService::addUsage 同步空间用量, bid={$bid}, usedMB={$usedMB}");

        return ['status' => true];
    }

    /**
     * 获取空间用量信息（实时计算）
     *
     * @param int $bid 商家ID
     * @return array ['totalMB' => int, 'usedMB' => float, 'percent' => float]
     */
    public function getUsageInfo(int $bid): array
    {
        $business = Db::name('business')
            ->where('id', $bid)
            ->field('cloud_space')
            ->find();

        $totalMB = (int) ($business['cloud_space'] ?? 5120);
        $usedMB = self::calculateUsedMB($bid);
        $percent = $totalMB > 0 ? round($usedMB / $totalMB * 100, 1) : 0;

        return [
            'totalMB' => $totalMB,
            'usedMB' => round($usedMB, 2),
            'percent' => $percent,
        ];
    }

    /**
     * 估算合成所需空间
     *
     * @param int $templateCount 模板数量
     * @param string $type 生成类型 image/video
     * @return float 预估所需MB
     */
    public function estimateRequired(int $templateCount, string $type = 'image'): float
    {
        $perSize = $type === 'video' ? self::ESTIMATE_VIDEO_SIZE_MB : self::ESTIMATE_IMAGE_SIZE_MB;
        return $templateCount * $perSize;
    }

    /**
     * 通过 HTTP HEAD 请求获取远程文件大小
     *
     * @param string $url 文件URL
     * @return int 文件大小（字节），失败返回0
     */
    public static function getRemoteFileSize(string $url): int
    {
        if (empty($url)) {
            return 0;
        }
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $size = (int) curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($ch);
            return ($httpCode === 200 && $size > 0) ? $size : 0;
        } catch (\Exception $e) {
            Log::error('getRemoteFileSize failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 批量回填 result 表中 file_size=0 的记录
     * 通过 HTTP HEAD 请求获取 COS 文件实际大小
     *
     * @param int $bid 商家ID（0=所有商家）
     * @param int $limit 每次处理条数
     * @return array ['updated' => int, 'failed' => int, 'total' => int]
     */
    public static function backfillResultFileSizes(int $bid = 0, int $limit = 200): array
    {
        $query = Db::name('ai_travel_photo_result')
            ->where('file_size', 0)
            ->whereNotNull('url')
            ->where('url', '<>', '');

        if ($bid > 0) {
            $query->where('bid', $bid);
        }

        $records = $query->limit($limit)->field('id, url, watermark_url, thumbnail_url, video_cover')->select()->toArray();
        $total = count($records);
        $updated = 0;
        $failed = 0;

        foreach ($records as $record) {
            $totalSize = 0;

            // 主文件
            $mainSize = self::getRemoteFileSize($record['url']);
            $totalSize += $mainSize;

            // 水印文件
            if (!empty($record['watermark_url'])) {
                $totalSize += self::getRemoteFileSize($record['watermark_url']);
            }

            // 缩略图
            if (!empty($record['thumbnail_url'])) {
                $totalSize += self::getRemoteFileSize($record['thumbnail_url']);
            }

            // 视频封面
            if (!empty($record['video_cover'])) {
                $totalSize += self::getRemoteFileSize($record['video_cover']);
            }

            if ($totalSize > 0) {
                Db::name('ai_travel_photo_result')
                    ->where('id', $record['id'])
                    ->update(['file_size' => $totalSize]);
                $updated++;
            } else {
                $failed++;
                Log::warning('backfillResultFileSizes: 无法获取文件大小, id=' . $record['id'] . ', url=' . $record['url']);
            }
        }

        // 同步更新 business 缓存字段
        if ($bid > 0) {
            $usedMB = self::calculateUsedMB($bid);
            Db::name('business')->where('id', $bid)->update(['cloud_space_used' => $usedMB]);
        }

        Log::info("backfillResultFileSizes 完成: total={$total}, updated={$updated}, failed={$failed}");

        return ['updated' => $updated, 'failed' => $failed, 'total' => $total];
    }
}

<?php
/**
 * 存储空间管理服务
 * 处理用户云端存储的配额计算、文件管理、告警等核心业务逻辑
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\UserStorageUsage;
use app\model\UserStorageFile;
use app\model\CreativeMemberPlan;
use app\model\CreativeMemberSubscription;

class StorageService
{
    /**
     * 默认配额 5GB (字节)
     */
    const DEFAULT_QUOTA_BYTES = 5368709120;

    /**
     * 1GB 字节数
     */
    const BYTES_PER_GB = 1073741824;

    /**
     * 告警等级常量
     */
    const WARNING_NORMAL = 'normal';
    const WARNING_WARNING = 'warning';
    const WARNING_CRITICAL = 'critical';
    const WARNING_FULL = 'full';

    /**
     * 告警冷却时间（秒）：24小时
     */
    const WARNING_COOLDOWN = 86400;

    /**
     * 获取用户存储空间信息
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public function getUserStorageInfo($aid, $mid)
    {
        $usage = UserStorageUsage::getOrCreate($aid, $mid);
        $quota = $this->getUserQuota($aid, $mid);

        // 更新配额（如果有变化）
        if ($usage['total_quota_bytes'] != $quota) {
            UserStorageUsage::updateQuota($aid, $mid, $quota);
            $usage['total_quota_bytes'] = $quota;
        }

        $usedBytes = intval($usage['used_bytes']);
        $totalBytes = intval($usage['total_quota_bytes']);
        $usedPercent = $totalBytes > 0 ? round($usedBytes / $totalBytes * 100, 2) : 0;
        $warningLevel = $this->getWarningLevel($usedPercent);

        // 获取会员信息
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        $isMember = (bool)$subscription;
        $versionName = '';
        if ($subscription) {
            $plan = CreativeMemberPlan::getById($subscription['plan_id']);
            $versionName = $plan ? $plan['version_name'] : ucfirst($subscription['version_code']);
        }

        return [
            'total_quota_bytes' => $totalBytes,
            'total_quota_gb' => round($totalBytes / self::BYTES_PER_GB, 2),
            'used_bytes' => $usedBytes,
            'used_gb' => round($usedBytes / self::BYTES_PER_GB, 2),
            'used_percent' => $usedPercent,
            'file_count' => intval($usage['file_count']),
            'image_count' => intval($usage['image_count']),
            'video_count' => intval($usage['video_count']),
            'is_member' => $isMember,
            'version_name' => $versionName,
            'warning_level' => $warningLevel,
        ];
    }

    /**
     * 获取用户文件列表
     * @param int $aid
     * @param int $mid
     * @param array $filters
     * @return array
     */
    public function getUserStorageFiles($aid, $mid, $filters = [])
    {
        $fileType = $filters['file_type'] ?? 'all';
        $sourceType = $filters['source_type'] ?? 'all';
        $page = intval($filters['page'] ?? 1);
        $limit = intval($filters['limit'] ?? 20);
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 20;

        $fileData = UserStorageFile::getFileList($aid, $mid, [
            'file_type' => $fileType,
            'source_type' => $sourceType,
        ], $page, $limit);

        $storageInfo = $this->getUserStorageInfo($aid, $mid);

        return [
            'list' => $fileData['list'],
            'count' => $fileData['count'],
            'storage_info' => $storageInfo,
        ];
    }

    /**
     * 配额预检
     * @param int $aid
     * @param int $mid
     * @param int $requiredBytes 需要的字节数
     * @return array
     */
    public function checkQuota($aid, $mid, $requiredBytes = 0)
    {
        $usage = UserStorageUsage::getOrCreate($aid, $mid);
        $quota = $this->getUserQuota($aid, $mid);

        $usedBytes = intval($usage['used_bytes']);
        $remainingBytes = max(0, $quota - $usedBytes);
        $usedPercent = $quota > 0 ? round($usedBytes / $quota * 100, 2) : 0;
        $warningLevel = $this->getWarningLevel($usedPercent);

        $allowed = true;
        $upgradeTip = '';

        // 空间已满
        if ($usedPercent >= 100) {
            $allowed = false;
            $upgradeTip = '您的云端存储空间已满，请清理文件或升级创作会员获取更多空间';
        }
        // 空间不足以容纳本次操作
        elseif ($requiredBytes > 0 && $requiredBytes > $remainingBytes) {
            $allowed = false;
            $upgradeTip = '云端存储空间不足，请清理文件或升级创作会员获取更多空间';
        }
        // 临界告警
        elseif ($warningLevel === self::WARNING_CRITICAL) {
            $upgradeTip = '存储空间即将用满（已用' . $usedPercent . '%），建议尽快清理或升级会员';
        }
        // 普通告警
        elseif ($warningLevel === self::WARNING_WARNING) {
            $upgradeTip = '存储空间即将用满（已用' . $usedPercent . '%），建议清理文件或升级会员';
        }

        return [
            'allowed' => $allowed,
            'remaining_bytes' => $remainingBytes,
            'warning_level' => $warningLevel,
            'upgrade_tip' => $upgradeTip,
            'used_percent' => $usedPercent,
        ];
    }

    /**
     * 添加文件记录
     * @param int $aid
     * @param int $mid
     * @param array $fileData
     * @return array ['status'=>0/1, 'msg'=>'', 'file_id'=>int]
     */
    public function addFile($aid, $mid, $fileData)
    {
        $fileSize = intval($fileData['file_size'] ?? 0);
        $fileType = $fileData['file_type'] ?? 'image';

        // 预检空间
        $check = $this->checkQuota($aid, $mid, $fileSize);
        if (!$check['allowed']) {
            return [
                'status' => 0,
                'msg' => $check['upgrade_tip'],
                'warning_level' => $check['warning_level'],
            ];
        }

        // 写入文件记录
        $insertData = [
            'aid' => $aid,
            'mid' => $mid,
            'file_url' => $fileData['file_url'] ?? '',
            'thumbnail_url' => $fileData['thumbnail_url'] ?? '',
            'file_type' => $fileType,
            'source_type' => $fileData['source_type'] ?? 'upload',
            'source_id' => intval($fileData['source_id'] ?? 0),
            'file_size' => $fileSize,
            'width' => intval($fileData['width'] ?? 0),
            'height' => intval($fileData['height'] ?? 0),
            'duration' => intval($fileData['duration'] ?? 0),
        ];

        $fileId = UserStorageFile::addFile($insertData);

        // 更新用量
        $updatedUsage = UserStorageUsage::incrementUsage($aid, $mid, $fileSize, $fileType);

        // 检查告警
        $totalQuota = intval($updatedUsage['total_quota_bytes']);
        $usedBytes = intval($updatedUsage['used_bytes']);
        $usedPercent = $totalQuota > 0 ? round($usedBytes / $totalQuota * 100, 2) : 0;
        $warningLevel = $this->getWarningLevel($usedPercent);

        if ($warningLevel !== self::WARNING_NORMAL) {
            $this->sendStorageWarning($aid, $mid, $warningLevel, $updatedUsage);
        }

        return [
            'status' => 1,
            'msg' => '文件记录添加成功',
            'file_id' => $fileId,
            'warning_level' => $warningLevel,
        ];
    }

    /**
     * 删除文件
     * @param int $aid
     * @param int $mid
     * @param array $fileIds
     * @return array
     */
    public function deleteFiles($aid, $mid, $fileIds)
    {
        if (empty($fileIds)) {
            return ['status' => 0, 'msg' => '请选择要删除的文件'];
        }

        $files = UserStorageFile::getByIds($fileIds, $aid, $mid);
        $deletedCount = 0;
        $skipped = [];

        foreach ($files as $file) {
            // 检查模板引用保护
            if ($file['is_template_ref']) {
                $skipped[] = [
                    'id' => $file['id'],
                    'reason' => '已被关联为场景模板素材，不可删除',
                ];
                continue;
            }

            // 软删除
            UserStorageFile::softDelete($file['id']);

            // 减少用量
            UserStorageUsage::decrementUsage($aid, $mid, $file['file_size'], $file['file_type']);

            $deletedCount++;
        }

        // 检查是否有找不到的文件
        $foundIds = array_column($files, 'id');
        foreach ($fileIds as $fid) {
            if (!in_array($fid, $foundIds)) {
                $skipped[] = [
                    'id' => $fid,
                    'reason' => '文件不存在或不属于当前用户',
                ];
            }
        }

        $storageInfo = $this->getUserStorageInfo($aid, $mid);

        return [
            'status' => 1,
            'msg' => "成功删除 {$deletedCount} 个文件",
            'deleted_count' => $deletedCount,
            'skipped' => $skipped,
            'storage_info' => $storageInfo,
        ];
    }

    /**
     * 重算用户用量
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public function recalculateUsage($aid, $mid)
    {
        return UserStorageUsage::recalculateUsage($aid, $mid);
    }

    /**
     * 获取用户配额（字节）
     * @param int $aid
     * @param int $mid
     * @return int
     */
    public function getUserQuota($aid, $mid)
    {
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        if ($subscription) {
            $plan = CreativeMemberPlan::getById($subscription['plan_id']);
            if ($plan && intval($plan['cloud_storage_gb']) > 0) {
                return intval($plan['cloud_storage_gb']) * self::BYTES_PER_GB;
            }
        }
        return self::DEFAULT_QUOTA_BYTES;
    }

    /**
     * 检查模板引用
     * @param array $fileIds
     * @return array 被引用的文件ID列表
     */
    public function checkTemplateRef($fileIds)
    {
        if (empty($fileIds)) return [];

        $refs = Db::name('user_storage_file')
            ->whereIn('id', $fileIds)
            ->where('is_template_ref', 1)
            ->where('is_deleted', 0)
            ->column('id');

        return $refs;
    }

    /**
     * 判断告警等级
     * @param float $usedPercent
     * @return string
     */
    public function getWarningLevel($usedPercent)
    {
        if ($usedPercent >= 100) {
            return self::WARNING_FULL;
        } elseif ($usedPercent >= 90) {
            return self::WARNING_CRITICAL;
        } elseif ($usedPercent >= 80) {
            return self::WARNING_WARNING;
        }
        return self::WARNING_NORMAL;
    }

    /**
     * 发送空间告警（频率限制：每24小时每等级最多1次）
     * @param int $aid
     * @param int $mid
     * @param string $level
     * @param array $usageRecord
     * @return bool
     */
    public function sendStorageWarning($aid, $mid, $level, $usageRecord = [])
    {
        $lastWarningTime = intval($usageRecord['last_warning_time'] ?? 0);
        $now = time();

        // 24小时冷却
        if ($now - $lastWarningTime < self::WARNING_COOLDOWN) {
            return false;
        }

        // 更新告警时间
        if (!empty($usageRecord['id'])) {
            UserStorageUsage::updateUsage($usageRecord['id'], [
                'last_warning_time' => $now,
            ]);
        }

        Log::info('用户存储空间告警', [
            'aid' => $aid,
            'mid' => $mid,
            'level' => $level,
            'used_bytes' => $usageRecord['used_bytes'] ?? 0,
            'total_quota_bytes' => $usageRecord['total_quota_bytes'] ?? 0,
        ]);

        return true;
    }

    /**
     * 动态更新配额（会员订阅/升级/降级/过期时调用）
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public function recalculateQuota($aid, $mid)
    {
        $newQuota = $this->getUserQuota($aid, $mid);
        UserStorageUsage::updateQuota($aid, $mid, $newQuota);

        $usage = UserStorageUsage::getOrCreate($aid, $mid);
        $usedPercent = $newQuota > 0 ? round($usage['used_bytes'] / $newQuota * 100, 2) : 0;
        $warningLevel = $this->getWarningLevel($usedPercent);

        return [
            'total_quota_bytes' => $newQuota,
            'total_quota_gb' => round($newQuota / self::BYTES_PER_GB, 2),
            'used_bytes' => intval($usage['used_bytes']),
            'used_percent' => $usedPercent,
            'warning_level' => $warningLevel,
        ];
    }

    /**
     * 维护模板引用关联（模板创建/编辑时）
     * @param int $templateId
     * @param array $refUrls 引用的URL列表
     * @param int $mid 用户ID
     */
    public function updateTemplateRefByUrls($templateId, $refUrls, $mid = 0)
    {
        if (empty($refUrls) || !$templateId) return;

        foreach ($refUrls as $url) {
            $file = UserStorageFile::getByUrl($url, $mid);
            if ($file) {
                UserStorageFile::addTemplateRef($file['id'], $templateId);
            }
        }
    }

    /**
     * 移除模板引用关联（模板删除时）
     * @param int $templateId
     */
    public function removeTemplateRefs($templateId)
    {
        if (!$templateId) return;

        // 查找所有引用该模板的文件
        $files = Db::name('user_storage_file')
            ->where('is_template_ref', 1)
            ->where('is_deleted', 0)
            ->whereFindInSet($templateId, 'template_ids')
            ->select()
            ->toArray();

        foreach ($files as $file) {
            UserStorageFile::removeTemplateRef($file['id'], $templateId);
        }
    }

    /**
     * 获取文件URL的大小（通过HEAD请求或下载）
     * @param string $url
     * @return int 文件大小(字节)，失败返回0
     */
    public static function getRemoteFileSize($url)
    {
        if (empty($url)) return 0;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        return ($size > 0) ? intval($size) : 0;
    }
}

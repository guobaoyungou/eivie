<?php
/**
 * 用户存储文件模型
 * 记录用户云端空间中每一个文件的元数据
 */
namespace app\model;

use think\facade\Db;

class UserStorageFile
{
    protected $table = 'user_storage_file';

    /**
     * 添加文件记录
     * @param array $data
     * @return int 文件记录ID
     */
    public static function addFile($data)
    {
        $data['createtime'] = $data['createtime'] ?? time();
        $data['is_deleted'] = 0;
        $data['is_template_ref'] = $data['is_template_ref'] ?? 0;
        $data['template_ids'] = $data['template_ids'] ?? '';
        return Db::name('user_storage_file')->insertGetId($data);
    }

    /**
     * 获取文件详情
     * @param int $id
     * @return array|null
     */
    public static function getById($id)
    {
        return Db::name('user_storage_file')->where('id', $id)->find();
    }

    /**
     * 获取用户文件列表
     * @param int $aid
     * @param int $mid
     * @param array $filters ['file_type'=>'', 'source_type'=>'']
     * @param int $page
     * @param int $limit
     * @return array ['list'=>[], 'count'=>int]
     */
    public static function getFileList($aid, $mid, $filters = [], $page = 1, $limit = 20)
    {
        $query = Db::name('user_storage_file')
            ->where('aid', $aid)
            ->where('mid', $mid)
            ->where('is_deleted', 0);

        if (!empty($filters['file_type']) && $filters['file_type'] !== 'all') {
            $query->where('file_type', $filters['file_type']);
        }
        if (!empty($filters['source_type']) && $filters['source_type'] !== 'all') {
            $query->where('source_type', $filters['source_type']);
        }

        $count = $query->count();
        $list = $query->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 获取本站存储域名前缀列表（用于判断是否为第三方链接）
        $localDomains = self::getLocalStorageDomains();

        // 格式化输出
        $now = time();
        foreach ($list as &$item) {
            $item['can_delete'] = $item['is_template_ref'] == 0;
            $item['is_template_ref'] = (bool)$item['is_template_ref'];
            $item['create_time'] = $item['createtime'] ? date('Y-m-d H:i:s', $item['createtime']) : '';
            $item['file_size_text'] = self::formatFileSize($item['file_size']);

            // 第三方链接过期检测
            $item['is_third_party'] = false;
            $item['is_expiring'] = false;
            $item['expire_time'] = 0;
            $item['remaining_seconds'] = 0;
            $item['expire_time_text'] = '';

            if ($item['source_type'] === 'generated' && !empty($item['file_url'])) {
                $isLocal = self::isLocalUrl($item['file_url'], $localDomains);
                if (!$isLocal) {
                    $item['is_third_party'] = true;
                    // 第三方生成链接默认24小时后过期
                    $expireTime = intval($item['createtime']) + 86400;
                    $remaining = $expireTime - $now;
                    $item['expire_time'] = $expireTime;
                    $item['remaining_seconds'] = max(0, $remaining);
                    $item['expire_time_text'] = date('Y-m-d H:i', $expireTime);
                    $item['is_expiring'] = $remaining > 0;
                    // 已过期的标记
                    if ($remaining <= 0) {
                        $item['is_expired'] = true;
                    } else {
                        $item['is_expired'] = false;
                    }
                }
            }
        }

        return ['list' => $list, 'count' => $count];
    }

    /**
     * 软删除文件
     * @param int $id
     * @return bool
     */
    public static function softDelete($id)
    {
        return Db::name('user_storage_file')
            ->where('id', $id)
            ->update(['is_deleted' => 1]) !== false;
    }

    /**
     * 批量获取文件
     * @param array $ids
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public static function getByIds($ids, $aid, $mid)
    {
        return Db::name('user_storage_file')
            ->where('aid', $aid)
            ->where('mid', $mid)
            ->whereIn('id', $ids)
            ->where('is_deleted', 0)
            ->select()
            ->toArray();
    }

    /**
     * 通过来源查找文件
     * @param string $sourceType
     * @param int $sourceId
     * @param int $mid
     * @return array|null
     */
    public static function getBySource($sourceType, $sourceId, $mid = 0)
    {
        $query = Db::name('user_storage_file')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('is_deleted', 0);
        if ($mid > 0) {
            $query->where('mid', $mid);
        }
        return $query->find();
    }

    /**
     * 通过URL查找文件
     * @param string $fileUrl
     * @param int $mid
     * @return array|null
     */
    public static function getByUrl($fileUrl, $mid = 0)
    {
        $query = Db::name('user_storage_file')
            ->where('file_url', $fileUrl)
            ->where('is_deleted', 0);
        if ($mid > 0) {
            $query->where('mid', $mid);
        }
        return $query->find();
    }

    /**
     * 更新模板引用状态
     * @param int $id
     * @param int $isTemplateRef
     * @param string $templateIds
     * @return bool
     */
    public static function updateTemplateRef($id, $isTemplateRef, $templateIds = '')
    {
        return Db::name('user_storage_file')
            ->where('id', $id)
            ->update([
                'is_template_ref' => $isTemplateRef,
                'template_ids' => $templateIds,
            ]) !== false;
    }

    /**
     * 为文件追加模板引用
     * @param int $fileId
     * @param int $templateId
     * @return bool
     */
    public static function addTemplateRef($fileId, $templateId)
    {
        $file = self::getById($fileId);
        if (!$file) return false;

        $existingIds = array_filter(explode(',', $file['template_ids']));
        $templateId = strval($templateId);
        if (!in_array($templateId, $existingIds)) {
            $existingIds[] = $templateId;
        }

        return self::updateTemplateRef(
            $fileId,
            1,
            implode(',', $existingIds)
        );
    }

    /**
     * 为文件移除模板引用
     * @param int $fileId
     * @param int $templateId
     * @return bool
     */
    public static function removeTemplateRef($fileId, $templateId)
    {
        $file = self::getById($fileId);
        if (!$file) return false;

        $existingIds = array_filter(explode(',', $file['template_ids']));
        $templateId = strval($templateId);
        $existingIds = array_diff($existingIds, [$templateId]);

        $isRef = !empty($existingIds) ? 1 : 0;
        return self::updateTemplateRef(
            $fileId,
            $isRef,
            implode(',', $existingIds)
        );
    }

    /**
     * 获取本站存储域名前缀列表
     * @return array
     */
    public static function getLocalStorageDomains()
    {
        $domains = [];

        // 本地域名
        if (defined('PRE_URL') && PRE_URL) {
            $domains[] = PRE_URL;
        }

        // 从远程存储配置中提取
        try {
            $remoteset = Db::name('sysset')->where('name', 'remote')->value('value');
            $remoteset = $remoteset ? json_decode($remoteset, true) : [];
            if (!empty($remoteset)) {
                // 阿里云OSS
                if (!empty($remoteset['alioss']['url'])) {
                    $domains[] = rtrim($remoteset['alioss']['url'], '/');
                }
                // 七牛云
                if (!empty($remoteset['qiniu']['url'])) {
                    $domains[] = rtrim($remoteset['qiniu']['url'], '/');
                }
                // 腾讯云COS
                if (!empty($remoteset['cos']['url'])) {
                    $domains[] = rtrim($remoteset['cos']['url'], '/');
                }
            }
        } catch (\Exception $e) {
            // 配置读取失败不影响主流程
        }

        return $domains;
    }

    /**
     * 判断URL是否属于本站存储
     * @param string $url
     * @param array $localDomains
     * @return bool
     */
    public static function isLocalUrl($url, $localDomains = [])
    {
        if (empty($url)) return false;
        if (empty($localDomains)) {
            $localDomains = self::getLocalStorageDomains();
        }
        foreach ($localDomains as $domain) {
            if (!empty($domain) && strpos($url, $domain) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 格式化文件大小
     * @param int $size 字节
     * @return string
     */
    public static function formatFileSize($size)
    {
        if ($size < 1024) {
            return $size . 'B';
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 1) . 'KB';
        } elseif ($size < 1024 * 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . 'MB';
        } else {
            return round($size / (1024 * 1024 * 1024), 2) . 'GB';
        }
    }
}

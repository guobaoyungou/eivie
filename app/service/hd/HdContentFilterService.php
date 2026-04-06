<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdActivity;

/**
 * 大屏互动 - 内容安全过滤服务
 * 功能：关键词过滤/敏感词检测/用户禁言黑名单/审核开关
 */
class HdContentFilterService
{
    /** 默认模板活动ID */
    private static $templateActivityId = 2;

    // ========== 内容过滤核心方法 ==========

    /**
     * 过滤内容（发送消息/弹幕前调用）
     * @return array ['pass'=>bool, 'filtered_content'=>string, 'reason'=>string, 'action'=>int]
     */
    public function filterContent(int $activityId, string $openid, string $content): array
    {
        // 1. 检查用户是否被禁言
        if ($this->isUserBanned($activityId, $openid)) {
            return ['pass' => false, 'filtered_content' => '', 'reason' => '您已被禁言', 'action' => 2];
        }

        // 2. 获取活动的安全配置
        $config = $this->getSecurityConfig($activityId);

        // 安全过滤未开启则直接放行
        if (empty($config['filter_enabled'])) {
            return ['pass' => true, 'filtered_content' => $content, 'reason' => '', 'action' => 0];
        }

        // 3. 关键词过滤
        $keywords = $this->getKeywords($activityId);
        $filteredContent = $content;
        $hitAction = 0;

        foreach ($keywords as $kw) {
            if (!$kw['enabled']) continue;
            $matched = false;

            switch ((int)$kw['match_type']) {
                case 1: // 包含
                    $matched = mb_stripos($filteredContent, $kw['keyword']) !== false;
                    break;
                case 2: // 完全匹配
                    $matched = mb_strtolower($filteredContent) === mb_strtolower($kw['keyword']);
                    break;
                case 3: // 正则
                    $matched = @preg_match('/' . $kw['keyword'] . '/iu', $filteredContent) === 1;
                    break;
            }

            if ($matched) {
                $action = (int)$kw['action'];
                if ($action === 2) {
                    // 拒绝发送
                    return ['pass' => false, 'filtered_content' => '', 'reason' => '内容包含违禁词', 'action' => 2];
                } elseif ($action === 3) {
                    // 转人工审核
                    $hitAction = max($hitAction, 3);
                } else {
                    // 替换为***
                    if ((int)$kw['match_type'] === 1) {
                        $filteredContent = str_ireplace($kw['keyword'], str_repeat('*', mb_strlen($kw['keyword'])), $filteredContent);
                    }
                    $hitAction = max($hitAction, 1);
                }
            }
        }

        // 4. 内置敏感词快速检测
        $builtinResult = $this->builtinFilter($filteredContent);
        if ($builtinResult['hit']) {
            $filteredContent = $builtinResult['content'];
            $hitAction = max($hitAction, $builtinResult['action']);
        }

        // action=3 表示需要人工审核
        if ($hitAction === 3) {
            return ['pass' => true, 'filtered_content' => $filteredContent, 'reason' => '命中敏感词，已转审核', 'action' => 3];
        }

        return ['pass' => true, 'filtered_content' => $filteredContent, 'reason' => '', 'action' => $hitAction];
    }

    /**
     * 内置通用敏感词库（广告/政治/色情等高频违禁词）
     */
    private function builtinFilter(string $content): array
    {
        $builtinWords = [
            '赌博','博彩','色情','裸聊','代孕','枪支','毒品','传销','诈骗','刷单',
            '兼职日赚','加微信','加QQ','扫码领取','免费领','恭喜发财点击','中奖了点击',
            '办证','发票代开','套现','洗钱','代开发票','高利贷',
        ];
        $hit = false;
        $filtered = $content;
        foreach ($builtinWords as $w) {
            if (mb_stripos($filtered, $w) !== false) {
                $filtered = str_ireplace($w, str_repeat('*', mb_strlen($w)), $filtered);
                $hit = true;
            }
        }
        return ['hit' => $hit, 'content' => $filtered, 'action' => 1];
    }

    // ========== 安全配置 ==========

    /**
     * 获取活动安全配置（从 hd_activity.screen_config 读取）
     * 优先级：当前活动配置 > 活动#2模板配置 > 默认值
     */
    public function getSecurityConfig(int $activityId): array
    {
        $cacheKey = 'hd_security_config:' . $activityId;
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        $defaults = [
            'filter_enabled'  => 1,
            'need_approve'    => 0,
            'max_msg_length'  => 200,
            'msg_interval'    => 3,
            'global_mute'     => 0,
        ];

        // 获取当前活动的屏幕配置
        $activityConfig = $this->getActivityScreenConfig($activityId);
        $config = $activityConfig['security'] ?? [];

        // 如果当前活动没有配置，尝试使用活动#2模板
        if (empty($config) && $activityId !== self::$templateActivityId) {
            $templateConfig = $this->getActivityScreenConfig(self::$templateActivityId);
            $templateSecurity = $templateConfig['security'] ?? [];
            // 合并模板配置
            foreach ($defaults as $key => $defaultValue) {
                if (isset($templateSecurity[$key])) {
                    $config[$key] = $templateSecurity[$key];
                }
            }
        }

        // 合并默认值
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($config[$key])) {
                $config[$key] = $defaultValue;
            }
        }

        Cache::set($cacheKey, $config, 60);
        return $config;
    }

    /**
     * 更新安全配置（保存到 hd_activity.screen_config）
     */
    public function updateSecurityConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        // 获取活动的屏幕配置
        $screenConfig = $this->getActivityScreenConfig($activityId);
        $security = $screenConfig['security'] ?? [];

        $allowedKeys = ['filter_enabled', 'need_approve', 'max_msg_length', 'msg_interval', 'global_mute'];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) $security[$key] = $data[$key];
        }

        // 保存配置
        $screenConfig['security'] = $security;
        $this->saveActivityScreenConfig($activityId, $screenConfig);

        Cache::delete('hd_security_config:' . $activityId);
        return ['code' => 0, 'msg' => '安全设置已更新'];
    }

    /**
     * 获取活动的屏幕配置（从 hd_activity.screen_config 读取）
     */
    private function getActivityScreenConfig(int $activityId): array
    {
        if ($activityId <= 0) {
            return [];
        }

        try {
            $activity = HdActivity::where('id', $activityId)->find();
            if ($activity && !empty($activity->screen_config)) {
                $configRaw = $activity->getData('screen_config');
                if (is_string($configRaw)) {
                    return json_decode($configRaw, true) ?: [];
                }
                return is_array($configRaw) ? $configRaw : [];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }

    /**
     * 保存活动的屏幕配置（到 hd_activity.screen_config）
     */
    private function saveActivityScreenConfig(int $activityId, array $screenConfig): bool
    {
        if ($activityId <= 0) {
            return false;
        }

        try {
            $activity = HdActivity::where('id', $activityId)->find();
            if (!$activity) {
                return false;
            }

            $activity->screen_config = $screenConfig;
            $activity->save();
            return true;
        } catch (\Throwable $e) {
            Log::error('保存活动屏幕配置失败: ' . $e->getMessage());
            return false;
        }
    }

    // ========== 关键词管理 ==========

    public function getKeywords(int $activityId): array
    {
        $cacheKey = 'hd_keywords:' . $activityId;
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        $list = Db::name('hd_keyword_blacklist')
            ->where('activity_id', $activityId)
            ->order('id asc')
            ->select()->toArray();

        Cache::set($cacheKey, $list, 120);
        return $list;
    }

    public function getKeywordList(int $aid, int $bid, int $activityId): array
    {
        $list = Db::name('hd_keyword_blacklist')
            ->where('activity_id', $activityId)
            ->order('id desc')->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    public function addKeyword(int $aid, int $bid, int $activityId, array $data): array
    {
        $keyword = trim($data['keyword'] ?? '');
        if (empty($keyword)) return ['code' => 1, 'msg' => '关键词不能为空'];

        Db::name('hd_keyword_blacklist')->insert([
            'aid' => $aid, 'bid' => $bid, 'activity_id' => $activityId,
            'keyword'    => $keyword,
            'match_type' => (int)($data['match_type'] ?? 1),
            'action'     => (int)($data['action'] ?? 1),
            'enabled'    => 1,
            'createtime' => time(),
        ]);

        Cache::delete('hd_keywords:' . $activityId);
        return ['code' => 0, 'msg' => '关键词已添加'];
    }

    public function batchAddKeywords(int $aid, int $bid, int $activityId, array $data): array
    {
        $text = trim($data['keywords_text'] ?? '');
        if (empty($text)) return ['code' => 1, 'msg' => '关键词不能为空'];

        $words = preg_split('/[\r\n,，;；|]+/', $text);
        $now = time();
        $rows = [];
        foreach ($words as $w) {
            $w = trim($w);
            if (empty($w)) continue;
            $rows[] = [
                'aid' => $aid, 'bid' => $bid, 'activity_id' => $activityId,
                'keyword' => $w, 'match_type' => 1, 'action' => 1, 'enabled' => 1, 'createtime' => $now,
            ];
        }
        if ($rows) Db::name('hd_keyword_blacklist')->insertAll($rows);
        Cache::delete('hd_keywords:' . $activityId);
        return ['code' => 0, 'msg' => '批量添加 ' . count($rows) . ' 个关键词'];
    }

    public function deleteKeyword(int $activityId, int $id): array
    {
        Db::name('hd_keyword_blacklist')->where('id', $id)->where('activity_id', $activityId)->delete();
        Cache::delete('hd_keywords:' . $activityId);
        return ['code' => 0, 'msg' => '已删除'];
    }

    public function toggleKeyword(int $activityId, int $id): array
    {
        $kw = Db::name('hd_keyword_blacklist')->where('id', $id)->where('activity_id', $activityId)->find();
        if (!$kw) return ['code' => 1, 'msg' => '关键词不存在'];
        Db::name('hd_keyword_blacklist')->where('id', $id)->update(['enabled' => $kw['enabled'] ? 0 : 1]);
        Cache::delete('hd_keywords:' . $activityId);
        return ['code' => 0, 'msg' => $kw['enabled'] ? '已禁用' : '已启用'];
    }

    // ========== 用户禁言/黑名单 ==========

    public function isUserBanned(int $activityId, string $openid): bool
    {
        if (empty($openid)) return false;
        $ban = Db::name('hd_user_ban')
            ->where('activity_id', $activityId)
            ->where('openid', $openid)
            ->where(function ($query) {
                $query->whereNull('expired_at')->whereOr('expired_at', '>', time());
            })
            ->find();
        return !empty($ban);
    }

    public function getBanList(int $activityId): array
    {
        $list = Db::name('hd_user_ban')
            ->where('activity_id', $activityId)
            ->order('id desc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    public function banUser(int $aid, int $bid, int $activityId, array $data): array
    {
        $openid = $data['openid'] ?? '';
        if (empty($openid)) return ['code' => 1, 'msg' => '缺少用户标识'];

        $existing = Db::name('hd_user_ban')
            ->where('activity_id', $activityId)->where('openid', $openid)->find();
        if ($existing) return ['code' => 1, 'msg' => '用户已在禁言名单中'];

        Db::name('hd_user_ban')->insert([
            'aid' => $aid, 'bid' => $bid, 'activity_id' => $activityId,
            'openid'     => $openid,
            'nickname'   => $data['nickname'] ?? '',
            'reason'     => $data['reason'] ?? '管理员操作',
            'ban_type'   => (int)($data['ban_type'] ?? 1),
            'expired_at' => !empty($data['duration']) ? time() + (int)$data['duration'] * 60 : null,
            'created_by' => $data['created_by'] ?? '',
            'createtime' => time(),
        ]);

        return ['code' => 0, 'msg' => '用户已禁言'];
    }

    public function unbanUser(int $activityId, int $id): array
    {
        Db::name('hd_user_ban')->where('id', $id)->where('activity_id', $activityId)->delete();
        return ['code' => 0, 'msg' => '已解除禁言'];
    }

    /**
     * 一键全局禁言（开关）
     */
    public function toggleGlobalMute(int $aid, int $bid, int $activityId): array
    {
        $config = $this->getSecurityConfig($activityId);
        $newMute = $config['global_mute'] ? 0 : 1;
        $this->updateSecurityConfig($aid, $bid, $activityId, ['global_mute' => $newMute]);
        return ['code' => 0, 'msg' => $newMute ? '已开启全局禁言' : '已关闭全局禁言', 'data' => ['global_mute' => $newMute]];
    }

    /**
     * 检查全局禁言状态
     */
    public function isGlobalMuted(int $activityId): bool
    {
        $config = $this->getSecurityConfig($activityId);
        return !empty($config['global_mute']);
    }
}

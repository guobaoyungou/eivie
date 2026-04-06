<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdKaimuConfig;
use app\model\hd\HdBimuConfig;
use app\model\hd\HdMusic;

/**
 * 大屏互动 - 主题展示服务
 * 功能：开幕墙、闭幕墙、背景设置、音乐设置、自定义二维码
 */
class HdThemeService
{
    // ========================================================
    // 开幕墙
    // ========================================================

    /**
     * 获取开幕墙配置
     */
    public function getKaimuConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdKaimuConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新开幕墙配置
     */
    public function updateKaimuConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdKaimuConfig::where('activity_id', $activityId)->find();
        if (!$config) {
            $config = new HdKaimuConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['title'])) $config->title = $data['title'];
        if (isset($data['subtitle'])) $config->subtitle = $data['subtitle'];
        if (isset($data['bg_image'])) $config->bg_image = $data['bg_image'];
        if (isset($data['video_url'])) $config->video_url = $data['video_url'];
        if (isset($data['config'])) $config->config = $data['config'];
        $config->save();

        return ['code' => 0, 'msg' => '开幕墙配置已更新'];
    }

    // ========================================================
    // 闭幕墙
    // ========================================================

    /**
     * 获取闭幕墙配置
     */
    public function getBimuConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdBimuConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新闭幕墙配置
     */
    public function updateBimuConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdBimuConfig::where('activity_id', $activityId)->find();
        if (!$config) {
            $config = new HdBimuConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['title'])) $config->title = $data['title'];
        if (isset($data['subtitle'])) $config->subtitle = $data['subtitle'];
        if (isset($data['bg_image'])) $config->bg_image = $data['bg_image'];
        if (isset($data['config'])) $config->config = $data['config'];
        $config->save();

        return ['code' => 0, 'msg' => '闭幕墙配置已更新'];
    }

    // ========================================================
    // 背景设置（活动级配置，存储在 hd_activity.screen_config）
    // ========================================================

    /** 需要过滤掉的 plugname */
    private static $excludePlugnames = ['shuqian', 'pashu'];

    /** 无素材时返回空字符串，前端用纯色展示 */
    private static $defaultBgPath = '';

    /** 默认模板活动ID */
    private static $templateActivityId = 2;

    /**
     * 获取背景列表（按功能模块，从活动 screen_config 读取）
     * 优先级：当前活动配置 > 活动#2模板配置 > weixin_background 全局默认
     */
    public function getBackgrounds(int $aid, int $bid, int $activityId, string $featureCode = ''): array
    {
        try {
            // 1. 获取全局背景模块定义（从 weixin_background 表读取模块列表）
            $query = Db::connect('huodong')->table('weixin_background');
            if ($featureCode) {
                $query->where('plugname', $featureCode);
            }
            $globalList = $query->select()->toArray();

            // 过滤掉不需要的功能模块
            $globalList = array_values(array_filter($globalList, function ($item) {
                return !in_array($item['plugname'], self::$excludePlugnames);
            }));

            // 2. 获取当前活动的屏幕配置
            $screenConfig = $this->getActivityScreenConfig($activityId);
            $activityBgConfig = $screenConfig['backgrounds'] ?? [];

            // 3. 获取活动#2的模板配置（如果当前活动没有配置，使用模板）
            $templateBgConfig = [];
            if ($activityId !== self::$templateActivityId) {
                $templateScreenConfig = $this->getActivityScreenConfig(self::$templateActivityId);
                $templateBgConfig = $templateScreenConfig['backgrounds'] ?? [];
            }

            // 4. 合并配置：当前活动配置 > 活动#2模板配置 > 全局默认
            foreach ($globalList as &$item) {
                $plugname = $item['plugname'];

                // 确定使用哪个配置：活动配置 > 模板配置 > 全局默认
                $attachmentId = 0;
                $bgtype = intval($item['bgtype'] ?? 1);

                if (isset($activityBgConfig[$plugname])) {
                    $attachmentId = intval($activityBgConfig[$plugname]['attachmentid'] ?? 0);
                    $bgtype = intval($activityBgConfig[$plugname]['bgtype'] ?? $bgtype);
                } elseif (isset($templateBgConfig[$plugname])) {
                    $attachmentId = intval($templateBgConfig[$plugname]['attachmentid'] ?? 0);
                    $bgtype = intval($templateBgConfig[$plugname]['bgtype'] ?? $bgtype);
                }

                $item['attachmentid'] = $attachmentId;
                $item['bgtype'] = $bgtype;

                // 关联查询附件路径
                if ($attachmentId > 0) {
                    $attachment = Db::connect('huodong')->table('weixin_attachments')
                        ->where('id', $attachmentId)->find();
                    $item['attachmentpath'] = ($attachment && !empty($attachment['filepath'])) ? $attachment['filepath'] : '';
                } else {
                    $item['attachmentpath'] = '';
                }

                // 标记是否有素材
                $item['has_material'] = ($attachmentId > 0 && !empty($item['attachmentpath'])) ? 1 : 0;
            }
            unset($item);

            return ['code' => 0, 'data' => $globalList];
        } catch (\Exception $e) {
            Log::error('获取背景列表失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '获取背景列表失败: ' . $e->getMessage()];
        }
    }

    /**
     * 获取活动的屏幕配置（从 hd_activity 表）
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
     * 保存活动的屏幕配置（到 hd_activity 表）
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

    /**
     * 删除背景素材（清除活动 screen_config 中对应模块的背景配置）
     * 注意：不删除功能模块行，仅清除关联的素材
     */
    public function resetBackground(int $aid, int $bid, int $activityId, string $plugname): array
    {
        if (empty($plugname)) {
            return ['code' => 1, 'msg' => '请指定功能模块'];
        }

        try {
            // 获取当前活动的屏幕配置
            $screenConfig = $this->getActivityScreenConfig($activityId);
            $backgrounds = $screenConfig['backgrounds'] ?? [];

            // 清除对应模块的背景配置
            if (isset($backgrounds[$plugname])) {
                unset($backgrounds[$plugname]);
            }

            // 保存配置
            $screenConfig['backgrounds'] = $backgrounds;
            $this->saveActivityScreenConfig($activityId, $screenConfig);

            return ['code' => 0, 'msg' => '重置成功'];
        } catch (\Exception $e) {
            Log::error('重置背景失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '重置失败: ' . $e->getMessage()];
        }
    }

    /**
     * 上传并更新背景（更新活动 screen_config 中对应 plugname 的记录）
     */
    public function updateBackgroundByPlugname(int $activityId, string $plugname, int $attachmentId, int $bgtype = 1): array
    {
        if (empty($plugname)) {
            return ['code' => 1, 'msg' => '请指定功能模块'];
        }

        try {
            // 获取当前活动的屏幕配置
            $screenConfig = $this->getActivityScreenConfig($activityId);
            $backgrounds = $screenConfig['backgrounds'] ?? [];

            // 更新对应模块的背景配置
            $backgrounds[$plugname] = [
                'attachmentid' => $attachmentId,
                'bgtype' => $bgtype,
            ];

            // 保存配置
            $screenConfig['backgrounds'] = $backgrounds;
            $this->saveActivityScreenConfig($activityId, $screenConfig);

            return ['code' => 0, 'msg' => '更新成功'];
        } catch (\Exception $e) {
            Log::error('更新背景失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()];
        }
    }

    /**
     * 保存附件到 weixin_attachments 表
     */
    public function saveAttachment(string $filepath, string $extension, int $type = 1, string $filemd5 = ''): int
    {
        // 先按md5查找是否已存在
        if ($filemd5) {
            $existing = Db::connect('huodong')->table('weixin_attachments')
                ->where('filemd5', $filemd5)->find();
            if ($existing) {
                return intval($existing['id']);
            }
        }

        $id = Db::connect('huodong')->table('weixin_attachments')->insertGetId([
            'filepath'  => $filepath,
            'extension' => $extension,
            'type'      => $type,
            'filemd5'   => $filemd5,
        ]);

        return intval($id);
    }

    // ========================================================
    // 音乐设置（hd_music 表，保留不动）
    // ========================================================

    /**
     * 获取音乐列表
     */
    public function getMusics(int $aid, int $bid, int $activityId): array
    {
        $list = HdMusic::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('is_default desc, sort asc, id asc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 添加音乐
     */
    public function addMusic(int $aid, int $bid, int $activityId, array $data): array
    {
        $music = new HdMusic();
        $music->aid = $aid;
        $music->bid = $bid;
        $music->activity_id = $activityId;
        $music->name = $data['name'] ?? '';
        $music->file_url = $data['file_url'] ?? '';
        $music->duration = (int)($data['duration'] ?? 0);
        $music->is_default = (int)($data['is_default'] ?? 0);
        $music->sort = (int)($data['sort'] ?? 0);
        $music->createtime = time();
        $music->save();

        return ['code' => 0, 'msg' => '添加成功', 'data' => $music->toArray()];
    }

    /**
     * 更新音乐
     */
    public function updateMusic(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $music = HdMusic::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$music) {
            return ['code' => 1, 'msg' => '音乐不存在'];
        }

        if (isset($data['name'])) $music->name = $data['name'];
        if (isset($data['file_url'])) $music->file_url = $data['file_url'];
        if (isset($data['duration'])) $music->duration = (int)$data['duration'];
        if (isset($data['is_default'])) {
            if ((int)$data['is_default'] === 1) {
                HdMusic::where('activity_id', $activityId)->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }
            $music->is_default = (int)$data['is_default'];
        }
        $music->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除音乐
     */
    public function deleteMusic(int $aid, int $bid, int $activityId, int $id): array
    {
        $music = HdMusic::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$music) {
            return ['code' => 1, 'msg' => '音乐不存在'];
        }
        $music->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    // ========================================================
    // 背景音乐管理（活动级配置，存储在 hd_activity.screen_config）
    // ========================================================

    /** 默认音乐路径 */
    private static $defaultMusicPath = '/wall/themes/meepo/assets/music/Radetzky_Marsch.mp3';

    /**
     * 获取全部功能模块的背景音乐配置列表
     * 优先级：当前活动配置 > 活动#2模板配置 > weixin_music 全局默认
     */
    public function getBgMusics(int $activityId): array
    {
        try {
            // 1. 获取全局音乐模块定义（从 weixin_music 表读取模块列表）
            $list = Db::connect('huodong')->table('weixin_music')
                ->select()->toArray();

            // 2. 获取当前活动的屏幕配置
            $screenConfig = $this->getActivityScreenConfig($activityId);
            $activityMusicConfig = $screenConfig['bgmusics'] ?? [];

            // 3. 获取活动#2的模板配置（如果当前活动没有配置，使用模板）
            $templateMusicConfig = [];
            if ($activityId !== self::$templateActivityId) {
                $templateScreenConfig = $this->getActivityScreenConfig(self::$templateActivityId);
                $templateMusicConfig = $templateScreenConfig['bgmusics'] ?? [];
            }

            // 4. 合并配置：当前活动配置 > 活动#2模板配置 > 全局默认
            foreach ($list as &$item) {
                $plugname = $item['plugname'];

                // 确定使用哪个配置：活动配置 > 模板配置 > 全局默认
                $bgmusicId = 0;
                $bgmusicstatus = intval($item['bgmusicstatus'] ?? 2);

                if (isset($activityMusicConfig[$plugname])) {
                    $bgmusicId = intval($activityMusicConfig[$plugname]['bgmusic'] ?? 0);
                    $bgmusicstatus = intval($activityMusicConfig[$plugname]['bgmusicstatus'] ?? $bgmusicstatus);
                } elseif (isset($templateMusicConfig[$plugname])) {
                    $bgmusicId = intval($templateMusicConfig[$plugname]['bgmusic'] ?? 0);
                    $bgmusicstatus = intval($templateMusicConfig[$plugname]['bgmusicstatus'] ?? $bgmusicstatus);
                }

                $item['bgmusic'] = $bgmusicId;
                $item['bgmusicstatus'] = $bgmusicstatus;

                // 关联查询附件路径
                if ($bgmusicId > 0) {
                    $attachment = Db::connect('huodong')->table('weixin_attachments')
                        ->where('id', $bgmusicId)->find();
                    $item['bgmusicpath'] = ($attachment && !empty($attachment['filepath']))
                        ? $attachment['filepath']
                        : self::$defaultMusicPath;
                } else {
                    $item['bgmusicpath'] = self::$defaultMusicPath;
                }
            }
            unset($item);

            return ['code' => 0, 'data' => $list];
        } catch (\Exception $e) {
            Log::error('获取背景音乐列表失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '获取背景音乐列表失败: ' . $e->getMessage()];
        }
    }

    /**
     * 切换指定 plugname 的背景音乐开/关（活动级别配置）
     */
    public function toggleBgMusic(int $activityId, string $plugname, int $status): array
    {
        if (empty($plugname)) {
            return ['code' => 1, 'msg' => '请指定功能模块'];
        }
        if (!in_array($status, [1, 2])) {
            return ['code' => 1, 'msg' => '状态值无效，1=开，2=关'];
        }

        try {
            // 验证功能模块是否存在
            $record = Db::connect('huodong')->table('weixin_music')
                ->where('plugname', $plugname)->find();
            if (!$record) {
                return ['code' => 1, 'msg' => '功能模块不存在: ' . $plugname];
            }

            // 获取当前活动的屏幕配置
            $screenConfig = $this->getActivityScreenConfig($activityId);
            $bgmusics = $screenConfig['bgmusics'] ?? [];

            // 确保该模块的配置存在
            if (!isset($bgmusics[$plugname])) {
                $bgmusics[$plugname] = [
                    'bgmusic' => 0,
                    'bgmusicstatus' => $status,
                ];
            }
            $bgmusics[$plugname]['bgmusicstatus'] = $status;

            // 保存配置
            $screenConfig['bgmusics'] = $bgmusics;
            $this->saveActivityScreenConfig($activityId, $screenConfig);

            return ['code' => 0, 'msg' => ($status === 1 ? '已开启' : '已关闭') . '背景音乐'];
        } catch (\Exception $e) {
            Log::error('切换背景音乐失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()];
        }
    }

    /**
     * 更新指定 plugname 的背景音乐附件，并自动开启（活动级别配置）
     */
    public function updateBgMusic(int $activityId, string $plugname, int $attachmentId): array
    {
        if (empty($plugname)) {
            return ['code' => 1, 'msg' => '请指定功能模块'];
        }

        try {
            // 验证功能模块是否存在
            $record = Db::connect('huodong')->table('weixin_music')
                ->where('plugname', $plugname)->find();
            if (!$record) {
                return ['code' => 1, 'msg' => '功能模块不存在: ' . $plugname];
            }

            // 获取当前活动的屏幕配置
            $screenConfig = $this->getActivityScreenConfig($activityId);
            $bgmusics = $screenConfig['bgmusics'] ?? [];

            // 更新配置
            $bgmusics[$plugname] = [
                'bgmusic' => $attachmentId,
                'bgmusicstatus' => 1, // 上传后自动开启
            ];

            // 保存配置
            $screenConfig['bgmusics'] = $bgmusics;
            $this->saveActivityScreenConfig($activityId, $screenConfig);

            // 获取新的音乐路径
            $attachment = Db::connect('huodong')->table('weixin_attachments')
                ->where('id', $attachmentId)->find();
            $bgmusicpath = ($attachment && !empty($attachment['filepath']))
                ? $attachment['filepath']
                : self::$defaultMusicPath;

            return ['code' => 0, 'msg' => '上传成功', 'data' => ['bgmusicpath' => $bgmusicpath]];
        } catch (\Exception $e) {
            Log::error('更新背景音乐失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()];
        }
    }

    // ========================================================
    // 自定义二维码
    // ========================================================

    /**
     * 获取自定义二维码配置（存储在活动 screen_config 中）
     */
    public function getQrcodeConfig(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];

        return [
            'code' => 0,
            'data' => [
                'qrcode_logo'  => $screenConfig['qrcode_logo'] ?? '',
                'qrcode_text'  => $screenConfig['qrcode_text'] ?? '扫码参与互动',
                'qrcode_color' => $screenConfig['qrcode_color'] ?? '#000000',
            ],
        ];
    }

    /**
     * 更新自定义二维码配置
     */
    public function updateQrcodeConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];
        if (isset($data['qrcode_logo'])) $screenConfig['qrcode_logo'] = $data['qrcode_logo'];
        if (isset($data['qrcode_text'])) $screenConfig['qrcode_text'] = $data['qrcode_text'];
        if (isset($data['qrcode_color'])) $screenConfig['qrcode_color'] = $data['qrcode_color'];
        $activity->screen_config = $screenConfig;
        $activity->save();

        return ['code' => 0, 'msg' => '二维码配置已更新'];
    }

    // ========================================================
    // 签到主题配置
    // ========================================================

    /** 签到主题配置键前缀 */
    private static $signThemePrefix = 'sign_theme_';

    /** 签到主题配置默认值 */
    private static $signThemeDefaults = [
        'sign_theme_style'          => 'classic',   // classic=样式一(经典), matrix=样式二(矩阵墙)
        'sign_theme_entrance'       => 'bounce',    // bounce=弹入缩放, fade=淡入, none=无
        'sign_theme_scroll'         => 'smooth',    // smooth=平滑滚动, none=不滚动
        'sign_theme_toast_enabled'  => '1',          // 1=开启Toast通知, 0=关闭
        'sign_theme_center_avatar'  => '1',          // 1=显示中央大头像, 0=隐藏
        'sign_theme_glow_border'    => '1',          // 1=流光边框, 0=普通边框
        'sign_theme_matrix_cols'    => '6',           // 矩阵列数
        'sign_theme_matrix_rows'    => '4',           // 矩阵可见行数
    ];

    /**
     * 获取签到主题配置
     */
    public function getSignThemeConfig(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];
        $result = [];
        foreach (self::$signThemeDefaults as $key => $default) {
            $result[$key] = $screenConfig[$key] ?? $default;
        }

        return ['code' => 0, 'data' => $result];
    }

    /**
     * 更新签到主题配置
     */
    public function updateSignThemeConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];

        // 只允许 sign_theme_ 前缀的键
        foreach ($data as $key => $value) {
            if (strpos($key, self::$signThemePrefix) === 0 && array_key_exists($key, self::$signThemeDefaults)) {
                $screenConfig[$key] = $value;
            }
        }

        $activity->screen_config = $screenConfig;
        $activity->save();

        return ['code' => 0, 'msg' => '签到主题配置已更新'];
    }
}

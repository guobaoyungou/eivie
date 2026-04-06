<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Filesystem;
use app\model\hd\HdActivity;
use app\model\hd\HdThreeDEffect;

/**
 * 大屏互动 - 3D签到管理服务
 * 功能：3D效果配置、效果列表管理、datastr同步
 */
class HdThreeDSignService
{
    /**
     * 获取3D签到配置 + 效果列表
     */
    public function getConfig(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];

        // 读取效果列表（若为空则自动初始化默认效果）
        $effects = HdThreeDEffect::where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();

        if (empty($effects)) {
            $effects = $this->initDefaultEffects($aid, $bid, $activityId);
        }

        return [
            'code' => 0,
            'data' => [
                'config' => [
                    'avatarnum'  => (int)($screenConfig['threed_avatarnum'] ?? 30),
                    'avatarsize' => (int)($screenConfig['threed_avatarsize'] ?? 7),
                    'avatargap'  => (int)($screenConfig['threed_avatargap'] ?? 15),
                    'play_mode'  => $screenConfig['threed_play_mode'] ?? 'sequential',
                    'idle_enabled' => (bool)($screenConfig['threed_idle_enabled'] ?? true),
                    'idle_delay'   => (int)($screenConfig['threed_idle_delay'] ?? 5000),
                ],
                'effects' => $effects,
            ],
        ];
    }

    /**
     * 保存全局配置（头像参数 + 播放模式）
     */
    public function saveConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];

        if (isset($data['avatarnum'])) {
            $screenConfig['threed_avatarnum'] = max(1, min(200, (int)$data['avatarnum']));
        }
        if (isset($data['avatarsize'])) {
            $screenConfig['threed_avatarsize'] = max(1, min(50, (int)$data['avatarsize']));
        }
        if (isset($data['avatargap'])) {
            $screenConfig['threed_avatargap'] = max(1, min(50, (int)$data['avatargap']));
        }
        if (isset($data['play_mode']) && in_array($data['play_mode'], ['sequential', 'random'])) {
            $screenConfig['threed_play_mode'] = $data['play_mode'];
        }
        if (isset($data['idle_enabled'])) {
            $screenConfig['threed_idle_enabled'] = (bool)$data['idle_enabled'];
        }
        if (isset($data['idle_delay'])) {
            $screenConfig['threed_idle_delay'] = max(3000, min(30000, (int)$data['idle_delay']));
        }

        $activity->screen_config = $screenConfig;
        $activity->save();

        return ['code' => 0, 'msg' => '配置已保存'];
    }

    /**
     * 添加效果
     */
    public function addEffect(int $aid, int $bid, int $activityId, array $data): array
    {
        $type = $data['type'] ?? '';
        $content = trim($data['content'] ?? '');

        if (!in_array($type, HdThreeDEffect::allowedTypes())) {
            return ['code' => 1, 'msg' => '无效的效果类型'];
        }

        // 验证 content
        if ($type === HdThreeDEffect::TYPE_PRESET_SHAPE) {
            if (!array_key_exists($content, HdThreeDEffect::presetShapes())) {
                return ['code' => 1, 'msg' => '无效的预设造型'];
            }
        } elseif ($type === HdThreeDEffect::TYPE_TEXT_LOGO) {
            if (empty($content) || mb_strlen($content) > 20) {
                return ['code' => 1, 'msg' => '文字内容不能为空且不超过20字'];
            }
        } elseif ($type === HdThreeDEffect::TYPE_IMAGE_LOGO) {
            if (empty($content)) {
                return ['code' => 1, 'msg' => '请先上传Logo图片'];
            }
        } elseif ($type === HdThreeDEffect::TYPE_COUNTDOWN) {
            $seconds = (int)$content;
            if ($seconds < 3 || $seconds > 300) {
                return ['code' => 1, 'msg' => '倒计时秒数须在3-300之间'];
            }
            $content = (string)$seconds;
        }

        // 获取当前最大 sort
        $maxSort = HdThreeDEffect::where('activity_id', $activityId)->max('sort') ?: 0;

        $effect = new HdThreeDEffect();
        $effect->aid = $aid;
        $effect->bid = $bid;
        $effect->activity_id = $activityId;
        $effect->type = $type;
        $effect->content = $content;
        $effect->sort = $maxSort + 1;
        $effect->is_default = 0;
        $effect->created_at = time();
        $effect->save();

        // 同步 datastr
        $this->syncDatastr($activityId);

        // 返回更新后的列表
        $effects = HdThreeDEffect::where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();

        return ['code' => 0, 'msg' => '效果已添加', 'data' => ['effects' => $effects]];
    }

    /**
     * 删除效果
     */
    public function deleteEffect(int $aid, int $bid, int $activityId, int $effectId): array
    {
        $effect = HdThreeDEffect::where('id', $effectId)
            ->where('activity_id', $activityId)
            ->find();

        if (!$effect) {
            return ['code' => 1, 'msg' => '效果不存在'];
        }

        // 保底检查：至少保留一个效果
        $count = HdThreeDEffect::where('activity_id', $activityId)->count();
        if ($count <= 1) {
            return ['code' => 1, 'msg' => '至少保留一个效果'];
        }

        $effect->delete();

        // 同步 datastr
        $this->syncDatastr($activityId);

        // 返回更新后的列表
        $effects = HdThreeDEffect::where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();

        return ['code' => 0, 'msg' => '效果已删除', 'data' => ['effects' => $effects]];
    }

    /**
     * 重排序效果
     */
    public function reorderEffects(int $aid, int $bid, int $activityId, array $effectIds): array
    {
        if (empty($effectIds)) {
            return ['code' => 1, 'msg' => '排序列表不能为空'];
        }

        foreach ($effectIds as $sort => $id) {
            HdThreeDEffect::where('id', (int)$id)
                ->where('activity_id', $activityId)
                ->update(['sort' => $sort + 1]);
        }

        // 同步 datastr
        $this->syncDatastr($activityId);

        $effects = HdThreeDEffect::where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();

        return ['code' => 0, 'msg' => '排序已更新', 'data' => ['effects' => $effects]];
    }

    /**
     * 上传图片Logo
     */
    public function uploadLogo(int $aid, int $bid, int $activityId): array
    {
        $file = request()->file('logo_file');
        if (!$file) {
            return ['code' => 1, 'msg' => '请上传Logo文件'];
        }

        try {
            validate([
                'logo_file' => [
                    'fileSize'    => 2 * 1024 * 1024,
                    'fileExt'     => 'png',
                    'fileMime'    => 'image/png',
                ],
            ])->check(['logo_file' => $file]);
        } catch (\think\exception\ValidateException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'fileSize') !== false || strpos($msg, '大小') !== false) {
                return ['code' => 1, 'msg' => '文件大小超过2MB，请压缩后重试'];
            }
            if (strpos($msg, 'fileExt') !== false || strpos($msg, 'fileMime') !== false) {
                return ['code' => 1, 'msg' => '仅支持PNG格式图片'];
            }
            return ['code' => 1, 'msg' => $msg];
        }

        try {
            $savename = Filesystem::putFile('hd/' . $bid . '/3d_logo', $file);
            $filepath = 'upload/' . str_replace("\\", '/', $savename);
            $url = request()->domain() . '/' . $filepath;

            return ['code' => 0, 'msg' => '上传成功', 'data' => ['url' => $url, 'path' => $filepath]];
        } catch (\Exception $e) {
            return ['code' => 1, 'msg' => '上传失败: ' . $e->getMessage()];
        }
    }

    /**
     * 初始化默认效果（首次加载时）
     */
    private function initDefaultEffects(int $aid, int $bid, int $activityId): array
    {
        $defaults = HdThreeDEffect::defaultEffects();
        $now = time();
        $records = [];

        foreach ($defaults as $item) {
            $records[] = [
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'type'        => $item['type'],
                'content'     => $item['content'],
                'sort'        => $item['sort'],
                'is_default'  => $item['is_default'],
                'created_at'  => $now,
            ];
        }

        (new HdThreeDEffect())->saveAll($records);

        // 同步 datastr
        $this->syncDatastr($activityId);

        return HdThreeDEffect::where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();
    }

    /**
     * 同步 datastr 到 screen_config
     * 将效果列表序列化为管道符分隔字符串，存入 screen_config['threed_datastr']
     * 供大屏 3dsign.php 读取
     */
    private function syncDatastr(int $activityId): void
    {
        $effects = HdThreeDEffect::where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select();

        $parts = [];
        foreach ($effects as $effect) {
            switch ($effect->type) {
                case HdThreeDEffect::TYPE_PRESET_SHAPE:
                    $parts[] = '#' . $effect->content;
                    break;
                case HdThreeDEffect::TYPE_IMAGE_LOGO:
                    $parts[] = '#icon ' . $effect->content;
                    break;
                case HdThreeDEffect::TYPE_TEXT_LOGO:
                    $parts[] = $effect->content;
                    break;
                case HdThreeDEffect::TYPE_COUNTDOWN:
                    $parts[] = '#countdown ' . $effect->content;
                    break;
            }
        }

        $datastr = implode('|', $parts);

        // 更新 screen_config
        $activity = HdActivity::where('id', $activityId)->find();
        if ($activity) {
            $screenConfig = $activity->screen_config ?: [];
            $screenConfig['threed_datastr'] = $datastr;
            $activity->screen_config = $screenConfig;
            $activity->save();
        }
    }
}

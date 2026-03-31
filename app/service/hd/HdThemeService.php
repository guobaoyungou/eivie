<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdKaimuConfig;
use app\model\hd\HdBimuConfig;
use app\model\hd\HdBackground;
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
    // 背景设置
    // ========================================================

    /**
     * 获取背景列表（按功能模块）
     */
    public function getBackgrounds(int $aid, int $bid, int $activityId, string $featureCode = ''): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];
        if ($featureCode) {
            $where[] = ['feature_code', '=', $featureCode];
        }

        $list = HdBackground::where($where)->order('sort asc, id asc')->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 更新背景
     */
    public function updateBackground(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $bg = HdBackground::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$bg) {
            return ['code' => 1, 'msg' => '背景不存在'];
        }

        if (isset($data['image_url'])) $bg->image_url = $data['image_url'];
        if (isset($data['is_default'])) {
            if ((int)$data['is_default'] === 1) {
                HdBackground::where('activity_id', $activityId)
                    ->where('feature_code', $bg->feature_code)
                    ->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }
            $bg->is_default = (int)$data['is_default'];
        }
        $bg->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 添加背景
     */
    public function addBackground(int $aid, int $bid, int $activityId, array $data): array
    {
        $bg = new HdBackground();
        $bg->aid = $aid;
        $bg->bid = $bid;
        $bg->activity_id = $activityId;
        $bg->feature_code = $data['feature_code'] ?? '';
        $bg->image_url = $data['image_url'] ?? '';
        $bg->is_default = (int)($data['is_default'] ?? 0);
        $bg->sort = (int)($data['sort'] ?? 0);
        $bg->createtime = time();
        $bg->save();

        return ['code' => 0, 'msg' => '添加成功', 'data' => $bg->toArray()];
    }

    /**
     * 删除背景
     */
    public function deleteBackground(int $aid, int $bid, int $activityId, int $id): array
    {
        $bg = HdBackground::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$bg) {
            return ['code' => 1, 'msg' => '背景不存在'];
        }
        $bg->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    // ========================================================
    // 音乐设置
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
}

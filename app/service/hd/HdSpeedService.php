<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdShakeConfig;
use app\model\hd\HdShakeTheme;
use app\model\hd\HdShakeRecord;
use app\model\hd\HdGameConfig;
use app\model\hd\HdGameTheme;
use app\model\hd\HdGameRecord;

/**
 * 大屏互动 - 拼手速服务
 * 功能：摇一摇竞技、互动游戏、答题、聚力启动
 */
class HdSpeedService
{
    // ========================================================
    // 摇一摇竞技
    // ========================================================

    /**
     * 获取摇一摇配置
     */
    public function getShakeConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdShakeConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->order('id desc')->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新摇一摇配置
     */
    public function updateShakeConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdShakeConfig::where('activity_id', $activityId)->order('id desc')->find();
        if (!$config) {
            $config = new HdShakeConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['duration'])) $config->duration = (int)$data['duration'];
        if (isset($data['max_winners'])) $config->max_winners = (int)$data['max_winners'];
        if (isset($data['max_participants'])) $config->max_participants = (int)$data['max_participants'];
        if (isset($data['prize_id'])) $config->prize_id = (int)$data['prize_id'];
        if (isset($data['bg_image'])) $config->bg_image = $data['bg_image'];
        if (isset($data['config'])) $config->config = $data['config'];
        if (isset($data['status'])) $config->status = (int)$data['status'];
        $config->save();

        return ['code' => 0, 'msg' => '摇一摇配置已更新'];
    }

    /**
     * 摇一摇主题列表
     */
    public function getShakeThemes(int $aid, int $bid, int $activityId): array
    {
        $list = HdShakeTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('is_default desc, id asc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 更新摇一摇主题
     */
    public function updateShakeTheme(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $theme = HdShakeTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$theme) {
            return ['code' => 1, 'msg' => '主题不存在'];
        }

        if (isset($data['name'])) $theme->name = $data['name'];
        if (isset($data['bg_image'])) $theme->bg_image = $data['bg_image'];
        if (isset($data['config'])) $theme->config = $data['config'];
        if (isset($data['is_default'])) {
            if ((int)$data['is_default'] === 1) {
                HdShakeTheme::where('activity_id', $activityId)->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }
            $theme->is_default = (int)$data['is_default'];
        }
        $theme->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 摇一摇排行榜
     */
    public function getShakeRanking(int $aid, int $bid, int $activityId, int $configId = 0): array
    {
        $where = [['activity_id', '=', $activityId]];
        if ($configId) {
            $where[] = ['shake_config_id', '=', $configId];
        }

        $list = HdShakeRecord::where($where)
            ->order('score desc, id asc')
            ->limit(100)
            ->select()->toArray();

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 重置摇一摇记录
     */
    public function resetShakeRecords(int $aid, int $bid, int $activityId): array
    {
        HdShakeRecord::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->delete();

        // 重置配置状态为待开始
        HdShakeConfig::where('activity_id', $activityId)->update(['status' => 1]);

        return ['code' => 0, 'msg' => '摇一摇记录已重置'];
    }

    // ========================================================
    // 互动游戏
    // ========================================================

    /**
     * 获取游戏配置
     */
    public function getGameConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdGameConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->order('id desc')->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新游戏配置
     */
    public function updateGameConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdGameConfig::where('activity_id', $activityId)->order('id desc')->find();
        if (!$config) {
            $config = new HdGameConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['game_type'])) $config->game_type = $data['game_type'];
        if (isset($data['duration'])) $config->duration = (int)$data['duration'];
        if (isset($data['max_winners'])) $config->max_winners = (int)$data['max_winners'];
        if (isset($data['prize_id'])) $config->prize_id = (int)$data['prize_id'];
        if (isset($data['bg_image'])) $config->bg_image = $data['bg_image'];
        if (isset($data['config'])) $config->config = $data['config'];
        if (isset($data['status'])) $config->status = (int)$data['status'];
        $config->save();

        return ['code' => 0, 'msg' => '游戏配置已更新'];
    }

    /**
     * 游戏主题列表
     */
    public function getGameThemes(int $aid, int $bid, int $activityId): array
    {
        $list = HdGameTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('is_default desc, id asc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 更新游戏主题
     */
    public function updateGameTheme(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $theme = HdGameTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$theme) {
            return ['code' => 1, 'msg' => '主题不存在'];
        }

        if (isset($data['name'])) $theme->name = $data['name'];
        if (isset($data['bg_image'])) $theme->bg_image = $data['bg_image'];
        if (isset($data['config'])) $theme->config = $data['config'];
        if (isset($data['is_default'])) {
            if ((int)$data['is_default'] === 1) {
                HdGameTheme::where('activity_id', $activityId)
                    ->where('game_type', $theme->game_type)
                    ->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }
            $theme->is_default = (int)$data['is_default'];
        }
        $theme->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 游戏排行榜
     */
    public function getGameRanking(int $aid, int $bid, int $activityId, int $configId = 0): array
    {
        $where = [['activity_id', '=', $activityId]];
        if ($configId) {
            $where[] = ['game_config_id', '=', $configId];
        }

        $list = HdGameRecord::where($where)
            ->order('score desc, id asc')
            ->limit(100)
            ->select()->toArray();

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 重置游戏记录
     */
    public function resetGameRecords(int $aid, int $bid, int $activityId): array
    {
        HdGameRecord::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->delete();

        HdGameConfig::where('activity_id', $activityId)->update(['status' => 1]);

        return ['code' => 0, 'msg' => '游戏记录已重置'];
    }
}

<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdPrize;
use app\model\hd\HdLotteryConfig;
use app\model\hd\HdLotteryTheme;
use app\model\hd\HdChoujiangConfig;
use app\model\hd\HdImportlottery;

/**
 * 大屏互动 - 抽奖管理服务
 * 功能：大屏抽奖轮次、奖品、抽奖主题、手机抽奖、摇大奖、导入抽奖
 */
class HdLotteryService
{
    // ========================================================
    // 奖品管理
    // ========================================================

    /**
     * 奖品列表
     */
    public function getPrizes(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];
        $list = HdPrize::where($where)->order('sort asc, id asc')->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 创建奖品
     */
    public function createPrize(int $aid, int $bid, int $activityId, array $data): array
    {
        $prize = new HdPrize();
        $prize->aid = $aid;
        $prize->bid = $bid;
        $prize->activity_id = $activityId;
        $prize->name = $data['name'] ?? '';
        $prize->image = $data['image'] ?? '';
        $prize->total_num = (int)($data['total_num'] ?? 0);
        $prize->used_num = 0;
        $prize->sort = (int)($data['sort'] ?? 0);
        $prize->createtime = time();
        $prize->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $prize->toArray()];
    }

    /**
     * 更新奖品
     */
    public function updatePrize(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $prize = HdPrize::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$prize) {
            return ['code' => 1, 'msg' => '奖品不存在'];
        }

        if (isset($data['name'])) $prize->name = $data['name'];
        if (isset($data['image'])) $prize->image = $data['image'];
        if (isset($data['total_num'])) $prize->total_num = (int)$data['total_num'];
        if (isset($data['sort'])) $prize->sort = (int)$data['sort'];
        $prize->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除奖品
     */
    public function deletePrize(int $aid, int $bid, int $activityId, int $id): array
    {
        $prize = HdPrize::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$prize) {
            return ['code' => 1, 'msg' => '奖品不存在'];
        }
        $prize->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    // ========================================================
    // 抽奖轮次管理
    // ========================================================

    /**
     * 抽奖轮次列表
     */
    public function getRounds(int $aid, int $bid, int $activityId): array
    {
        $list = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('round_num asc, id asc')
            ->select()->toArray();

        // 附加奖品信息
        foreach ($list as &$item) {
            if ($item['prize_id']) {
                $prize = HdPrize::find($item['prize_id']);
                $item['prize_name'] = $prize ? $prize->name : '';
            } else {
                $item['prize_name'] = '';
            }
        }
        unset($item);

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 创建抽奖轮次
     */
    public function createRound(int $aid, int $bid, int $activityId, array $data): array
    {
        $maxRound = (int)HdLotteryConfig::where('activity_id', $activityId)->max('round_num');

        $round = new HdLotteryConfig();
        $round->aid = $aid;
        $round->bid = $bid;
        $round->activity_id = $activityId;
        $round->round_name = $data['round_name'] ?? '第' . ($maxRound + 1) . '轮活动';
        $round->round_num = $maxRound + 1;
        $round->prize_id = (int)($data['prize_id'] ?? 0);
        $round->win_num = (int)($data['win_num'] ?? 1);
        $round->is_repeat = (int)($data['is_repeat'] ?? 0);
        $round->status = 1;
        $round->winners = '';
        $round->createtime = time();
        $round->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $round->toArray()];
    }

    /**
     * 更新抽奖轮次
     */
    public function updateRound(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $round = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }

        if (isset($data['round_name'])) $round->round_name = $data['round_name'];
        if (isset($data['prize_id'])) $round->prize_id = (int)$data['prize_id'];
        if (isset($data['win_num'])) $round->win_num = (int)$data['win_num'];
        if (isset($data['is_repeat'])) $round->is_repeat = (int)$data['is_repeat'];
        if (isset($data['status'])) $round->status = (int)$data['status'];
        $round->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除抽奖轮次
     */
    public function deleteRound(int $aid, int $bid, int $activityId, int $id): array
    {
        $round = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }
        $round->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 重置抽奖轮次（清空中奖者）
     */
    public function resetRound(int $aid, int $bid, int $activityId, int $id): array
    {
        $round = HdLotteryConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }
        $round->winners = '';
        $round->status = 1;
        $round->save();
        return ['code' => 0, 'msg' => '轮次已重置'];
    }

    // ========================================================
    // 抽奖主题管理
    // ========================================================

    /**
     * 抽奖主题列表
     */
    public function getThemes(int $aid, int $bid, int $activityId): array
    {
        $list = HdLotteryTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('is_default desc, id asc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 创建抽奖主题
     */
    public function createTheme(int $aid, int $bid, int $activityId, array $data): array
    {
        $theme = new HdLotteryTheme();
        $theme->aid = $aid;
        $theme->bid = $bid;
        $theme->activity_id = $activityId;
        $theme->name = $data['name'] ?? '';
        $theme->bg_image = $data['bg_image'] ?? '';
        $theme->config = $data['config'] ?? [];
        $theme->is_default = (int)($data['is_default'] ?? 0);
        $theme->createtime = time();
        $theme->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $theme->toArray()];
    }

    /**
     * 更新抽奖主题
     */
    public function updateTheme(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $theme = HdLotteryTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$theme) {
            return ['code' => 1, 'msg' => '主题不存在'];
        }

        if (isset($data['name'])) $theme->name = $data['name'];
        if (isset($data['bg_image'])) $theme->bg_image = $data['bg_image'];
        if (isset($data['config'])) $theme->config = $data['config'];
        if (isset($data['is_default'])) {
            if ((int)$data['is_default'] === 1) {
                // 将其他主题设为非默认
                HdLotteryTheme::where('activity_id', $activityId)->where('id', '<>', $id)
                    ->update(['is_default' => 0]);
            }
            $theme->is_default = (int)$data['is_default'];
        }
        $theme->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除抽奖主题
     */
    public function deleteTheme(int $aid, int $bid, int $activityId, int $id): array
    {
        $theme = HdLotteryTheme::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$theme) {
            return ['code' => 1, 'msg' => '主题不存在'];
        }
        $theme->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    // ========================================================
    // 手机抽奖管理
    // ========================================================

    /**
     * 获取手机抽奖配置
     */
    public function getChoujiangConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdChoujiangConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新手机抽奖配置
     */
    public function updateChoujiangConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdChoujiangConfig::where('activity_id', $activityId)->find();
        if (!$config) {
            $config = new HdChoujiangConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['max_times'])) $config->max_times = (int)$data['max_times'];
        if (isset($data['bg_image'])) $config->bg_image = $data['bg_image'];
        if (isset($data['config'])) $config->config = $data['config'];
        $config->save();

        return ['code' => 0, 'msg' => '手机抽奖配置已更新'];
    }

    // ========================================================
    // 导入抽奖管理
    // ========================================================

    /**
     * 导入抽奖名单
     */
    public function getImportList(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];

        if (!empty($params['keyword'])) {
            $where[] = ['name|phone|code', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['is_winner']) && $params['is_winner'] !== '') {
            $where[] = ['is_winner', '=', (int)$params['is_winner']];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdImportlottery::where($where)->page($page, $limit)->order('id desc')->select()->toArray();
        $count = HdImportlottery::where($where)->count();

        return ['code' => 0, 'data' => ['list' => $list, 'count' => $count]];
    }

    /**
     * 批量导入抽奖名单
     */
    public function batchImport(int $aid, int $bid, int $activityId, array $items): array
    {
        $now = time();
        $insertData = [];
        foreach ($items as $item) {
            $insertData[] = [
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'name'        => $item['name'] ?? '',
                'phone'       => $item['phone'] ?? '',
                'code'        => $item['code'] ?? '',
                'extra'       => isset($item['extra']) ? json_encode($item['extra'], JSON_UNESCAPED_UNICODE) : '',
                'is_winner'   => 0,
                'prize_id'    => 0,
                'createtime'  => $now,
            ];
        }

        if ($insertData) {
            Db::name('hd_importlottery')->insertAll($insertData);
        }

        return ['code' => 0, 'msg' => '导入成功', 'data' => ['count' => count($insertData)]];
    }

    /**
     * 清空导入抽奖名单
     */
    public function clearImportList(int $aid, int $bid, int $activityId): array
    {
        HdImportlottery::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->delete();
        return ['code' => 0, 'msg' => '名单已清空'];
    }
}

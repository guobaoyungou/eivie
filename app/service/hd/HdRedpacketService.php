<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdRedpacketConfig;
use app\model\hd\HdRedpacketRound;
use app\model\hd\HdRedpacketUser;

/**
 * 大屏互动 - 红包互动服务
 * 功能：红包设置、红包轮次、中奖记录
 */
class HdRedpacketService
{
    /**
     * 获取红包配置
     */
    public function getConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdRedpacketConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新红包配置
     */
    public function updateConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdRedpacketConfig::where('activity_id', $activityId)->find();
        if (!$config) {
            $config = new HdRedpacketConfig();
            $config->aid = $aid;
            $config->bid = $bid;
            $config->activity_id = $activityId;
            $config->createtime = time();
        }

        if (isset($data['total_amount'])) $config->total_amount = (float)$data['total_amount'];
        if (isset($data['total_num'])) $config->total_num = (int)$data['total_num'];
        if (isset($data['min_amount'])) $config->min_amount = (float)$data['min_amount'];
        if (isset($data['max_amount'])) $config->max_amount = (float)$data['max_amount'];
        if (isset($data['duration'])) $config->duration = (int)$data['duration'];
        if (isset($data['config'])) $config->config = $data['config'];
        $config->save();

        return ['code' => 0, 'msg' => '红包配置已更新'];
    }

    /**
     * 红包轮次列表
     */
    public function getRounds(int $aid, int $bid, int $activityId): array
    {
        $list = HdRedpacketRound::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('round_num asc, id asc')
            ->select()->toArray();
        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 创建红包轮次
     */
    public function createRound(int $aid, int $bid, int $activityId, array $data): array
    {
        $config = HdRedpacketConfig::where('activity_id', $activityId)->find();
        if (!$config) {
            return ['code' => 1, 'msg' => '请先配置红包'];
        }

        $maxRound = (int)HdRedpacketRound::where('activity_id', $activityId)->max('round_num');

        $round = new HdRedpacketRound();
        $round->aid = $aid;
        $round->bid = $bid;
        $round->activity_id = $activityId;
        $round->redpacket_config_id = (int)$config->id;
        $round->round_num = $maxRound + 1;
        $round->total_amount = (float)($data['total_amount'] ?? 0);
        $round->sent_amount = 0;
        $round->total_num = (int)($data['total_num'] ?? 0);
        $round->sent_num = 0;
        $round->status = 1;
        $round->createtime = time();
        $round->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $round->toArray()];
    }

    /**
     * 更新红包轮次
     */
    public function updateRound(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $round = HdRedpacketRound::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }

        if (isset($data['total_amount'])) $round->total_amount = (float)$data['total_amount'];
        if (isset($data['total_num'])) $round->total_num = (int)$data['total_num'];
        if (isset($data['status'])) $round->status = (int)$data['status'];
        $round->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除红包轮次
     */
    public function deleteRound(int $aid, int $bid, int $activityId, int $id): array
    {
        $round = HdRedpacketRound::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$round) {
            return ['code' => 1, 'msg' => '轮次不存在'];
        }

        // 同时删除相关中奖记录
        HdRedpacketUser::where('round_id', $id)->delete();
        $round->delete();

        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 中奖记录列表
     */
    public function getWinRecords(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];

        if (!empty($params['round_id'])) {
            $where[] = ['round_id', '=', (int)$params['round_id']];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['nickname|openid', 'like', '%' . $params['keyword'] . '%'];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdRedpacketUser::where($where)
            ->page($page, $limit)
            ->order('id desc')
            ->select()->toArray();
        $count = HdRedpacketUser::where($where)->count();

        $totalAmount = HdRedpacketUser::where($where)->sum('amount');

        return [
            'code' => 0,
            'data' => [
                'list'         => $list,
                'count'        => $count,
                'total_amount' => (float)$totalAmount,
            ],
        ];
    }
}

<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use app\model\hd\HdVoteItem;
use app\model\hd\HdVoteRecord;

/**
 * 大屏互动 - 投票设置服务
 * 功能：投票选项CRUD、投票统计
 */
class HdVoteService
{
    /**
     * 获取投票选项列表
     */
    public function getItems(int $aid, int $bid, int $activityId): array
    {
        $list = HdVoteItem::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()->toArray();

        $totalVotes = HdVoteRecord::where('activity_id', $activityId)->count();

        return [
            'code' => 0,
            'data' => [
                'list'        => $list,
                'total_votes' => $totalVotes,
            ],
        ];
    }

    /**
     * 创建投票选项
     */
    public function createItem(int $aid, int $bid, int $activityId, array $data): array
    {
        $item = new HdVoteItem();
        $item->aid = $aid;
        $item->bid = $bid;
        $item->activity_id = $activityId;
        $item->title = $data['title'] ?? '';
        $item->image = $data['image'] ?? '';
        $item->vote_count = 0;
        $item->sort = (int)($data['sort'] ?? 0);
        $item->createtime = time();
        $item->save();

        return ['code' => 0, 'msg' => '创建成功', 'data' => $item->toArray()];
    }

    /**
     * 更新投票选项
     */
    public function updateItem(int $aid, int $bid, int $activityId, int $id, array $data): array
    {
        $item = HdVoteItem::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$item) {
            return ['code' => 1, 'msg' => '选项不存在'];
        }

        if (isset($data['title'])) $item->title = $data['title'];
        if (isset($data['image'])) $item->image = $data['image'];
        if (isset($data['sort'])) $item->sort = (int)$data['sort'];
        $item->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除投票选项
     */
    public function deleteItem(int $aid, int $bid, int $activityId, int $id): array
    {
        $item = HdVoteItem::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$item) {
            return ['code' => 1, 'msg' => '选项不存在'];
        }

        // 同时删除投票记录
        HdVoteRecord::where('vote_item_id', $id)->delete();
        $item->delete();

        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 获取投票统计
     */
    public function getStats(int $aid, int $bid, int $activityId): array
    {
        $items = HdVoteItem::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('vote_count desc, sort asc')
            ->select()->toArray();

        $totalVotes = HdVoteRecord::where('activity_id', $activityId)->count();
        $totalVoters = HdVoteRecord::where('activity_id', $activityId)
            ->group('openid')->count();

        return [
            'code' => 0,
            'data' => [
                'items'        => $items,
                'total_votes'  => $totalVotes,
                'total_voters' => $totalVoters,
            ],
        ];
    }

    /**
     * 重置投票
     */
    public function resetVotes(int $aid, int $bid, int $activityId): array
    {
        HdVoteRecord::where('activity_id', $activityId)->delete();
        HdVoteItem::where('activity_id', $activityId)->update(['vote_count' => 0]);

        return ['code' => 0, 'msg' => '投票已重置'];
    }
}

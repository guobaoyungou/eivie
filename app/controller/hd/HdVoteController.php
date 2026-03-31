<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdVoteService;

/**
 * 大屏互动 - 投票设置控制器
 */
class HdVoteController extends HdBaseController
{
    protected $voteService;

    protected function initialize()
    {
        $this->voteService = new HdVoteService();
    }

    /** 投票选项列表 */
    public function items(int $activity_id)
    {
        return json($this->voteService->getItems($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 创建投票选项 */
    public function createItem(int $activity_id)
    {
        return json($this->voteService->createItem($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 更新投票选项 */
    public function updateItem(int $activity_id, int $id)
    {
        return json($this->voteService->updateItem($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    /** 删除投票选项 */
    public function deleteItem(int $activity_id, int $id)
    {
        return json($this->voteService->deleteItem($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 投票统计 */
    public function stats(int $activity_id)
    {
        return json($this->voteService->getStats($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 重置投票 */
    public function resetVotes(int $activity_id)
    {
        return json($this->voteService->resetVotes($this->getAid(), $this->getBid(), $activity_id));
    }
}

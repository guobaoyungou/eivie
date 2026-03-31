<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdRedpacketService;

/**
 * 大屏互动 - 红包互动控制器
 */
class HdRedpacketController extends HdBaseController
{
    protected $redpacketService;

    protected function initialize()
    {
        $this->redpacketService = new HdRedpacketService();
    }

    /** 获取红包配置 */
    public function config(int $activity_id)
    {
        return json($this->redpacketService->getConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新红包配置 */
    public function updateConfig(int $activity_id)
    {
        return json($this->redpacketService->updateConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 红包轮次列表 */
    public function rounds(int $activity_id)
    {
        return json($this->redpacketService->getRounds($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 创建红包轮次 */
    public function createRound(int $activity_id)
    {
        return json($this->redpacketService->createRound($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 更新红包轮次 */
    public function updateRound(int $activity_id, int $id)
    {
        return json($this->redpacketService->updateRound($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    /** 删除红包轮次 */
    public function deleteRound(int $activity_id, int $id)
    {
        return json($this->redpacketService->deleteRound($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 中奖记录 */
    public function winRecords(int $activity_id)
    {
        $params = [
            'round_id' => input('get.round_id', ''),
            'keyword'  => input('get.keyword', ''),
            'page'     => input('get.page', 1),
            'limit'    => input('get.limit', 50),
        ];
        return json($this->redpacketService->getWinRecords($this->getAid(), $this->getBid(), $activity_id, $params));
    }
}

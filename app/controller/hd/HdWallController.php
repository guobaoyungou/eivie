<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdWallService;

/**
 * 大屏互动 - 弹幕互动控制器
 */
class HdWallController extends HdBaseController
{
    protected $wallService;

    protected function initialize()
    {
        $this->wallService = new HdWallService();
    }

    /** 获取上墙设置 */
    public function wallConfig(int $activity_id)
    {
        return json($this->wallService->getWallConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新上墙设置 */
    public function updateWallConfig(int $activity_id)
    {
        return json($this->wallService->updateWallConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 获取弹幕配置 */
    public function danmuConfig(int $activity_id)
    {
        return json($this->wallService->getDanmuConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新弹幕配置 */
    public function updateDanmuConfig(int $activity_id)
    {
        return json($this->wallService->updateDanmuConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 消息列表 */
    public function messages(int $activity_id)
    {
        $params = [
            'is_approved' => input('get.is_approved', ''),
            'keyword'     => input('get.keyword', ''),
            'type'        => input('get.type', ''),
            'page'        => input('get.page', 1),
            'limit'       => input('get.limit', 50),
        ];
        return json($this->wallService->getMessages($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    /** 审核消息 */
    public function approveMessage(int $activity_id, int $id)
    {
        $status = (int)input('post.status', 1);
        return json($this->wallService->approveMessage($this->getAid(), $this->getBid(), $activity_id, $id, $status));
    }

    /** 批量审核 */
    public function batchApprove(int $activity_id)
    {
        $ids = input('post.ids/a', []);
        $status = (int)input('post.status', 1);
        return json($this->wallService->batchApprove($this->getAid(), $this->getBid(), $activity_id, $ids, $status));
    }

    /** 删除消息 */
    public function deleteMessage(int $activity_id, int $id)
    {
        return json($this->wallService->deleteMessage($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 消息置顶 */
    public function toggleTop(int $activity_id, int $id)
    {
        return json($this->wallService->toggleTop($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 发布公告 */
    public function publishNotice(int $activity_id)
    {
        return json($this->wallService->publishNotice($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }
}

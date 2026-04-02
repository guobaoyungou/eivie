<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdActivityService;

/**
 * 大屏互动 - 活动管理控制器
 */
class HdActivityController extends HdBaseController
{
    protected $activityService;

    protected function initialize()
    {
        $this->activityService = new HdActivityService();
    }

    /**
     * 活动列表
     * GET /api/hd/activities
     */
    public function index()
    {
        $params = [
            'keyword' => input('get.keyword', ''),
            'status'  => input('get.status', ''),
            'mdid'    => input('get.mdid', ''),
            'page'    => input('get.page', 1),
            'limit'   => input('get.limit', 20),
        ];

        $result = $this->activityService->getList($this->getAid(), $this->getBid(), $params);
        return json($result);
    }

    /**
     * 创建活动
     * POST /api/hd/activities
     */
    public function create()
    {
        $data = input('post.');
        $plan = $this->request->hd_plan ?? null;

        $result = $this->activityService->create($this->getAid(), $this->getBid(), $data, $plan);
        return json($result);
    }

    /**
     * 活动详情
     * GET /api/hd/activities/:id
     */
    public function detail(int $id)
    {
        $result = $this->activityService->detail($this->getAid(), $this->getBid(), $id);
        return json($result);
    }

    /**
     * 更新活动
     * PUT /api/hd/activities/:id
     */
    public function update(int $id)
    {
        $data = input('post.');
        $plan = $this->request->hd_plan ?? null;
        $result = $this->activityService->update($this->getAid(), $this->getBid(), $id, $data, $plan);
        return json($result);
    }

    /**
     * 删除活动
     * DELETE /api/hd/activities/:id
     */
    public function delete(int $id)
    {
        $result = $this->activityService->delete($this->getAid(), $this->getBid(), $id);
        return json($result);
    }

    /**
     * 切换活动状态
     * PUT /api/hd/activities/:id/status
     */
    public function updateStatus(int $id)
    {
        $status = (int)input('post.status', 1);
        $result = $this->activityService->updateStatus($this->getAid(), $this->getBid(), $id, $status);
        return json($result);
    }

    /**
     * 获取活动功能配置列表
     * GET /api/hd/activities/:id/features
     */
    public function features(int $id)
    {
        $result = $this->activityService->getFeatures($this->getAid(), $this->getBid(), $id);
        return json($result);
    }

    /**
     * 更新指定功能配置
     * PUT /api/hd/activities/:id/features/:code
     */
    public function updateFeature(int $id, string $code)
    {
        $data = input('post.');
        $result = $this->activityService->updateFeature($this->getAid(), $this->getBid(), $id, $code, $data);
        return json($result);
    }

    /**
     * 参与者列表
     * GET /api/hd/activities/:id/participants
     */
    public function participants(int $id)
    {
        $params = [
            'flag'  => input('get.flag', ''),
            'page'  => input('get.page', 1),
            'limit' => input('get.limit', 50),
        ];

        $result = $this->activityService->getParticipants($this->getAid(), $this->getBid(), $id, $params);
        return json($result);
    }

    /**
     * 活动数据统计
     * GET /api/hd/activities/:id/stats
     */
    public function stats(int $id)
    {
        $result = $this->activityService->getStats($this->getAid(), $this->getBid(), $id);
        return json($result);
    }

    /**
     * 获取全部可用功能列表
     * GET /api/hd/features
     */
    public function allFeatures()
    {
        return $this->success(HdActivityService::ALL_FEATURES);
    }

    /**
     * 克隆活动
     * POST /api/hd/activities/:id/clone
     */
    public function cloneActivity(int $id)
    {
        $plan = $this->request->hd_plan ?? null;
        $result = $this->activityService->cloneActivity($this->getAid(), $this->getBid(), $id, $plan);
        return json($result);
    }
}

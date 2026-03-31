<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdAdminService;
use app\service\hd\HdSetupService;

/**
 * 大屏互动 - 平台超管 API 控制器
 */
class HdAdminController extends HdBaseController
{
    protected $adminService;

    protected function initialize()
    {
        $this->adminService = new HdAdminService();
    }

    /**
     * 租户列表
     * GET /api/hd/admin/tenants
     */
    public function tenants()
    {
        $params = [
            'keyword' => input('get.keyword', ''),
            'page'    => input('get.page', 1),
            'limit'   => input('get.limit', 20),
        ];
        $result = $this->adminService->getTenants($params);
        return json($result);
    }

    /**
     * 启用/禁用租户
     * PUT /api/hd/admin/tenants/:id/status
     */
    public function updateTenantStatus(int $id)
    {
        $status = (int)input('post.status', 1);
        $result = $this->adminService->updateTenantStatus($id, $status);
        return json($result);
    }

    /**
     * 套餐列表
     * GET /api/hd/admin/plans
     */
    public function plans()
    {
        $result = $this->adminService->getPlans();
        return json($result);
    }

    /**
     * 创建套餐
     * POST /api/hd/admin/plans
     */
    public function createPlan()
    {
        $data = input('post.');
        $result = $this->adminService->createPlan($data);
        return json($result);
    }

    /**
     * 更新套餐
     * PUT /api/hd/admin/plans/:id
     */
    public function updatePlan(int $id)
    {
        $data = input('post.');
        $result = $this->adminService->updatePlan($id, $data);
        return json($result);
    }

    /**
     * 平台统计数据
     * GET /api/hd/admin/stats
     */
    public function stats()
    {
        $result = $this->adminService->getStats();
        return json($result);
    }

    /**
     * 初始化 Demo 活动
     * POST /api/hd/admin/setup-demo
     */
    public function setupDemo()
    {
        $setupService = new HdSetupService();
        $result = $setupService->initDemo();
        return json($result);
    }
}

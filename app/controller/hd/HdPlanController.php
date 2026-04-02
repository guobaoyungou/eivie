<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdPlanService;

/**
 * 大屏互动 - 套餐查询控制器
 */
class HdPlanController extends HdBaseController
{
    protected $planService;

    protected function initialize()
    {
        $this->planService = new HdPlanService();
    }

    /**
     * 获取可购买套餐列表
     * GET /api/hd/plans
     */
    public function list()
    {
        $result = $this->planService->getActivePlans();
        return json($result);
    }
}

<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdStoreService;

/**
 * 大屏互动 - 门店管理控制器
 */
class HdStoreController extends HdBaseController
{
    protected $storeService;

    protected function initialize()
    {
        $this->storeService = new HdStoreService();
    }

    /**
     * 门店列表
     * GET /api/hd/stores
     */
    public function index()
    {
        $params = [
            'keyword' => input('get.keyword', ''),
            'page'    => input('get.page', 1),
            'limit'   => input('get.limit', 20),
        ];

        $result = $this->storeService->getList($this->getAid(), $this->getBid(), $params);
        return json($result);
    }

    /**
     * 创建门店
     * POST /api/hd/stores
     */
    public function create()
    {
        $data = input('post.');
        $plan = $this->request->hd_plan ?? null;
        if ($plan) {
            $data['max_stores'] = $plan->max_stores;
        }

        $result = $this->storeService->create($this->getAid(), $this->getBid(), $data);
        return json($result);
    }

    /**
     * 门店详情
     * GET /api/hd/stores/:id
     */
    public function detail(int $id)
    {
        $result = $this->storeService->detail($this->getAid(), $this->getBid(), $id);
        return json($result);
    }

    /**
     * 更新门店
     * PUT /api/hd/stores/:id
     */
    public function update(int $id)
    {
        $data = input('post.');
        $result = $this->storeService->update($this->getAid(), $this->getBid(), $id, $data);
        return json($result);
    }

    /**
     * 删除门店
     * DELETE /api/hd/stores/:id
     */
    public function delete(int $id)
    {
        $result = $this->storeService->delete($this->getAid(), $this->getBid(), $id);
        return json($result);
    }
}

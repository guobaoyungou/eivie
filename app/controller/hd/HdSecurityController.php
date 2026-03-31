<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdContentFilterService;
use app\service\hd\HdBrandService;

/**
 * 大屏互动 - 内容安全 + 品牌定制 控制器
 */
class HdSecurityController extends HdBaseController
{
    protected $filterService;
    protected $brandService;

    protected function initialize()
    {
        $this->filterService = new HdContentFilterService();
        $this->brandService = new HdBrandService();
    }

    // ============ 安全配置 ============

    public function securityConfig(int $activity_id)
    {
        $config = $this->filterService->getSecurityConfig($activity_id);
        return json(['code' => 0, 'data' => $config]);
    }

    public function updateSecurityConfig(int $activity_id)
    {
        $data = $this->request->post();
        $result = $this->filterService->updateSecurityConfig(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id, $data
        );
        return json($result);
    }

    // ============ 关键词管理 ============

    public function keywords(int $activity_id)
    {
        $result = $this->filterService->getKeywordList(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id
        );
        return json($result);
    }

    public function addKeyword(int $activity_id)
    {
        $data = $this->request->post();
        $result = $this->filterService->addKeyword(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id, $data
        );
        return json($result);
    }

    public function batchAddKeywords(int $activity_id)
    {
        $data = $this->request->post();
        $result = $this->filterService->batchAddKeywords(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id, $data
        );
        return json($result);
    }

    public function deleteKeyword(int $activity_id, int $id)
    {
        $result = $this->filterService->deleteKeyword($activity_id, $id);
        return json($result);
    }

    public function toggleKeyword(int $activity_id, int $id)
    {
        $result = $this->filterService->toggleKeyword($activity_id, $id);
        return json($result);
    }

    // ============ 用户禁言管理 ============

    public function banList(int $activity_id)
    {
        $result = $this->filterService->getBanList($activity_id);
        return json($result);
    }

    public function banUser(int $activity_id)
    {
        $data = $this->request->post();
        $result = $this->filterService->banUser(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id, $data
        );
        return json($result);
    }

    public function unbanUser(int $activity_id, int $id)
    {
        $result = $this->filterService->unbanUser($activity_id, $id);
        return json($result);
    }

    public function toggleGlobalMute(int $activity_id)
    {
        $result = $this->filterService->toggleGlobalMute(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id
        );
        return json($result);
    }

    // ============ 品牌定制 ============

    public function brandConfig(int $activity_id)
    {
        $result = $this->brandService->getBrandConfig($activity_id);
        return json($result);
    }

    public function updateBrandConfig(int $activity_id)
    {
        $data = $this->request->post();
        $result = $this->brandService->updateBrandConfig(
            (int)$this->request->aid, (int)$this->request->bid, $activity_id, $data
        );
        return json($result);
    }

    public function animationPresets()
    {
        return json(['code' => 0, 'data' => HdBrandService::ANIMATION_PRESETS]);
    }
}

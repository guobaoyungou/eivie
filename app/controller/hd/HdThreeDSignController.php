<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdThreeDSignService;

/**
 * 大屏互动 - 3D签到管理控制器
 */
class HdThreeDSignController extends HdBaseController
{
    protected $service;

    protected function initialize()
    {
        $this->service = new HdThreeDSignService();
    }

    /**
     * 获取3D签到配置 + 效果列表
     * GET /api/hd/sign/:activity_id/3d-config
     */
    public function getConfig(int $activity_id)
    {
        return json($this->service->getConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /**
     * 保存全局配置
     * POST /api/hd/sign/:activity_id/3d-config
     */
    public function saveConfig(int $activity_id)
    {
        return json($this->service->saveConfig(
            $this->getAid(),
            $this->getBid(),
            $activity_id,
            input('post.')
        ));
    }

    /**
     * 添加效果
     * POST /api/hd/sign/:activity_id/3d-effects/add
     */
    public function addEffect(int $activity_id)
    {
        return json($this->service->addEffect(
            $this->getAid(),
            $this->getBid(),
            $activity_id,
            input('post.')
        ));
    }

    /**
     * 删除效果
     * POST /api/hd/sign/:activity_id/3d-effects/:effect_id/delete
     */
    public function deleteEffect(int $activity_id, int $effect_id)
    {
        return json($this->service->deleteEffect(
            $this->getAid(),
            $this->getBid(),
            $activity_id,
            $effect_id
        ));
    }

    /**
     * 重排序效果
     * POST /api/hd/sign/:activity_id/3d-effects/reorder
     */
    public function reorderEffects(int $activity_id)
    {
        $effectIds = input('post.effect_ids/a', []);
        return json($this->service->reorderEffects(
            $this->getAid(),
            $this->getBid(),
            $activity_id,
            $effectIds
        ));
    }

    /**
     * 上传图片Logo
     * POST /api/hd/sign/:activity_id/3d-effects/upload-logo
     */
    public function uploadLogo(int $activity_id)
    {
        return json($this->service->uploadLogo(
            $this->getAid(),
            $this->getBid(),
            $activity_id
        ));
    }
}

<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdSignService;

/**
 * 大屏互动 - 签到管理控制器
 */
class HdSignController extends HdBaseController
{
    protected $signService;

    protected function initialize()
    {
        $this->signService = new HdSignService();
    }

    /** 获取签到设置 */
    public function config(int $activity_id)
    {
        return json($this->signService->getSignConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新签到设置 */
    public function updateConfig(int $activity_id)
    {
        return json($this->signService->updateSignConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 签到名单 */
    public function signList(int $activity_id)
    {
        $params = [
            'flag'    => input('get.flag', ''),
            'keyword' => input('get.keyword', ''),
            'page'    => input('get.page', 1),
            'limit'   => input('get.limit', 50),
        ];
        return json($this->signService->getSignList($this->getAid(), $this->getBid(), $activity_id, $params));
    }

    /** 删除签到记录 */
    public function deleteParticipant(int $activity_id, int $id)
    {
        return json($this->signService->deleteParticipant($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    /** 清空签到名单 */
    public function clearSignList(int $activity_id)
    {
        return json($this->signService->clearSignList($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 获取手机页面设计 */
    public function mobileConfig(int $activity_id)
    {
        return json($this->signService->getMobilePageConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新手机页面设计 */
    public function updateMobileConfig(int $activity_id)
    {
        return json($this->signService->updateMobilePageConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    /** 获取大屏密码配置 */
    public function screenPasswordConfig(int $activity_id)
    {
        return json($this->signService->getScreenPasswordConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    /** 更新大屏密码配置 */
    public function updateScreenPasswordConfig(int $activity_id)
    {
        return json($this->signService->updateScreenPasswordConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }
}

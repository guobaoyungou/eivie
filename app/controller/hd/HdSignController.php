<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdSignService;
use app\model\hd\HdParticipant;

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

    /** 切换参与者管理员状态 */
    public function toggleAdmin(int $activity_id, int $id)
    {
        $participant = HdParticipant::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('activity_id', $activity_id)
            ->where('id', $id)
            ->find();

        if (!$participant) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        $participant->is_admin = $participant->is_admin ? 0 : 1;
        $participant->save();

        return json([
            'code' => 0,
            'msg'  => $participant->is_admin ? '已设为管理员' : '已取消管理员',
            'data' => ['is_admin' => $participant->is_admin],
        ]);
    }

    /** 切换参与者核销员状态 */
    public function toggleVerifier(int $activity_id, int $id)
    {
        $participant = HdParticipant::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('activity_id', $activity_id)
            ->where('id', $id)
            ->find();

        if (!$participant) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        $participant->is_verifier = $participant->is_verifier ? 0 : 1;
        $participant->save();

        return json([
            'code' => 0,
            'msg'  => $participant->is_verifier ? '已设为核销员' : '已取消核销员',
            'data' => ['is_verifier' => $participant->is_verifier],
        ]);
    }
}

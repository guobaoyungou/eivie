<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdSpeedService;

/**
 * 大屏互动 - 拼手速控制器
 */
class HdSpeedController extends HdBaseController
{
    protected $speedService;

    protected function initialize()
    {
        $this->speedService = new HdSpeedService();
    }

    // —— 摇一摇竞技 ——

    public function shakeConfig(int $activity_id)
    {
        return json($this->speedService->getShakeConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateShakeConfig(int $activity_id)
    {
        return json($this->speedService->updateShakeConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function shakeThemes(int $activity_id)
    {
        return json($this->speedService->getShakeThemes($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateShakeTheme(int $activity_id, int $id)
    {
        return json($this->speedService->updateShakeTheme($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function shakeRanking(int $activity_id)
    {
        $configId = (int)input('get.config_id', 0);
        return json($this->speedService->getShakeRanking($this->getAid(), $this->getBid(), $activity_id, $configId));
    }

    public function resetShake(int $activity_id)
    {
        return json($this->speedService->resetShakeRecords($this->getAid(), $this->getBid(), $activity_id));
    }

    // —— 互动游戏 ——

    public function gameConfig(int $activity_id)
    {
        return json($this->speedService->getGameConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateGameConfig(int $activity_id)
    {
        return json($this->speedService->updateGameConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function gameThemes(int $activity_id)
    {
        return json($this->speedService->getGameThemes($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateGameTheme(int $activity_id, int $id)
    {
        return json($this->speedService->updateGameTheme($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function gameRanking(int $activity_id)
    {
        $configId = (int)input('get.config_id', 0);
        return json($this->speedService->getGameRanking($this->getAid(), $this->getBid(), $activity_id, $configId));
    }

    public function resetGame(int $activity_id)
    {
        return json($this->speedService->resetGameRecords($this->getAid(), $this->getBid(), $activity_id));
    }
}

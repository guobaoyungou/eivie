<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdThemeService;

/**
 * 大屏互动 - 主题展示控制器
 */
class HdThemeController extends HdBaseController
{
    protected $themeService;

    protected function initialize()
    {
        $this->themeService = new HdThemeService();
    }

    // —— 开幕墙 ——
    public function kaimuConfig(int $activity_id)
    {
        return json($this->themeService->getKaimuConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateKaimuConfig(int $activity_id)
    {
        return json($this->themeService->updateKaimuConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    // —— 闭幕墙 ——
    public function bimuConfig(int $activity_id)
    {
        return json($this->themeService->getBimuConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateBimuConfig(int $activity_id)
    {
        return json($this->themeService->updateBimuConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    // —— 背景 ——
    public function backgrounds(int $activity_id)
    {
        $featureCode = input('get.feature_code', '');
        return json($this->themeService->getBackgrounds($this->getAid(), $this->getBid(), $activity_id, $featureCode));
    }

    public function addBackground(int $activity_id)
    {
        return json($this->themeService->addBackground($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function updateBackground(int $activity_id, int $id)
    {
        return json($this->themeService->updateBackground($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function deleteBackground(int $activity_id, int $id)
    {
        return json($this->themeService->deleteBackground($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    // —— 音乐 ——
    public function musics(int $activity_id)
    {
        return json($this->themeService->getMusics($this->getAid(), $this->getBid(), $activity_id));
    }

    public function addMusic(int $activity_id)
    {
        return json($this->themeService->addMusic($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }

    public function updateMusic(int $activity_id, int $id)
    {
        return json($this->themeService->updateMusic($this->getAid(), $this->getBid(), $activity_id, $id, input('post.')));
    }

    public function deleteMusic(int $activity_id, int $id)
    {
        return json($this->themeService->deleteMusic($this->getAid(), $this->getBid(), $activity_id, $id));
    }

    // —— 自定义二维码 ——
    public function qrcodeConfig(int $activity_id)
    {
        return json($this->themeService->getQrcodeConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateQrcodeConfig(int $activity_id)
    {
        return json($this->themeService->updateQrcodeConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }
}

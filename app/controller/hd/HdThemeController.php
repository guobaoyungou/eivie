<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdThemeService;
use think\facade\Filesystem;

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
        // 已废弃，背景上传改由 HdUploadController::background 处理
        return json(['code' => 1, 'msg' => '请使用上传接口']);
    }

    public function resetBackground(int $activity_id)
    {
        $plugname = input('post.plugname', '');
        return json($this->themeService->resetBackground($this->getAid(), $this->getBid(), $activity_id, $plugname));
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

    // —— 背景音乐（活动级配置，存储在 hd_activity.screen_config） ——
    public function bgMusics(int $activity_id)
    {
        return json($this->themeService->getBgMusics($activity_id));
    }

    public function toggleBgMusic(int $activity_id)
    {
        $plugname = input('post.plugname', '');
        $bgmusicstatus = (int)input('post.bgmusicstatus', 0);
        return json($this->themeService->toggleBgMusic($activity_id, $plugname, $bgmusicstatus));
    }

    public function uploadBgMusic(int $activity_id)
    {
        $file = request()->file('file');
        if (!$file) {
            return $this->error('请上传音乐文件');
        }

        $plugname = input('post.plugname', '');
        if (empty($plugname)) {
            return $this->error('请指定功能模块(plugname)');
        }

        try {
            validate(['file' => [
                'fileSize'    => 20 * 1024 * 1024, // 20MB
                'fileExt'     => 'mp3',
                'fileMime'    => 'audio/mpeg,audio/mp3',
            ]])->check(['file' => $file]);

            // 保存文件
            $savename = Filesystem::putFile('hd/' . $this->getBid() . '/bgmusic', $file);
            $filepath = '/upload/' . str_replace("\\" , '/', $savename);

            // 记录到 weixin_attachments
            $ext = strtolower($file->extension());
            $filemd5 = md5($file->getOriginalName() . '|' . $file->getSize());
            $attachmentId = $this->themeService->saveAttachment($filepath, $ext, 1, $filemd5);

            // 更新活动 screen_config 中对应 plugname 的背景音乐配置
            $result = $this->themeService->updateBgMusic($activity_id, $plugname, $attachmentId);
            return json($result);
        } catch (\think\exception\ValidateException $e) {
            return $this->error('仅支持 mp3 格式，最大 20MB');
        } catch (\Exception $e) {
            return $this->error('上传失败: ' . $e->getMessage());
        }
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

    // —— 签到主题 ——
    public function signThemeConfig(int $activity_id)
    {
        return json($this->themeService->getSignThemeConfig($this->getAid(), $this->getBid(), $activity_id));
    }

    public function updateSignThemeConfig(int $activity_id)
    {
        return json($this->themeService->updateSignThemeConfig($this->getAid(), $this->getBid(), $activity_id, input('post.')));
    }
}

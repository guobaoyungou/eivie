<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\service\hd\HdActivityService;
use app\service\hd\HdBrandService;
use app\service\hd\HdFrameDataProvider;

/**
 * 大屏互动 - 活动入口页控制器
 * 访问 /s/{access_code} 时，根据 User-Agent 自适应大屏/手机端
 * 重构：大屏端使用 Smarty 渲染 huodong frame.html 模板
 */
class HdEntryController extends HdBaseController
{
    /** @var HdFrameDataProvider */
    protected $dataProvider;

    protected function initialize()
    {
        $this->dataProvider = new HdFrameDataProvider();
    }

    /**
     * 活动入口页
     * GET /s/{access_code}
     */
    public function index(string $access_code)
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return $this->renderError('活动不存在', '您访问的活动链接无效或已删除');
        }

        // 检查活动是否过期
        if ($activity->ended_at && time() > $activity->ended_at) {
            if ($activity->status != HdActivity::STATUS_ENDED) {
                $activity->status = HdActivity::STATUS_ENDED;
                $activity->save();
            }
        }

        // 获取功能配置
        $features = HdActivityFeature::where('activity_id', $activity->id)
            ->where('enabled', 1)
            ->order('sort asc')
            ->select()
            ->toArray();

        foreach ($features as &$f) {
            $f['feature_name'] = HdActivityService::ALL_FEATURES[$f['feature_code']] ?? $f['feature_code'];
            $f['config'] = $f['config'] ? (is_string($f['config']) ? json_decode($f['config'], true) : $f['config']) : [];
        }
        unset($f);

        // 判断设备类型
        $ua = $this->request->header('User-Agent', '');
        $isMobile = (bool)preg_match('/Mobile|Android|iPhone|iPad|MicroMessenger/i', $ua);

        if ($isMobile) {
            return $this->renderMobile($activity, $features, $access_code);
        }

        // 大屏端：使用 Smarty 渲染 frame.html
        return $this->renderFrame($activity, $features);
    }

    /**
     * iframe 功能页渲染
     * GET /s/{access_code}/wall/{feature}
     */
    public function wallPage(string $access_code, string $feature)
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return $this->renderError('活动不存在', '您访问的活动链接无效或已删除');
        }

        // 构建功能页 Smarty 变量
        $vars = $this->dataProvider->buildWallPageVars($activity, $feature);

        // 确定模板文件
        $templateMap = [
            'index'  => ['header', 'login', 'footer'],
            'qdq'    => ['header', 'login', 'footer'],
            'wall'   => ['header', 'wall', 'footer'],
            '3dsign' => ['header', '3dsign', 'footer'],
            'threedimensionalsign' => ['header', '3dsign', 'footer'],
            'danmu'  => ['header', 'danmu', 'footer'],
            'vote'   => ['header', 'vote', 'footer'],
            'kaimu'  => ['header', 'kaimu', 'footer'],
            'bimu'   => ['header', 'bimu', 'footer'],
            'xiangce'=> ['header', 'xiangce'],
            'redpacket' => ['header', 'redpacket', 'footer'],
            'xyh'    => ['header', 'xyh', 'footer'],
            'xysjh'  => ['header', 'xysjh', 'footer'],
            'danye'  => ['header', 'danye', 'footer'],
            'ddp'    => ['header', 'ddp', 'footer'],
        ];

        $templates = $templateMap[$feature] ?? ['header', $feature, 'footer'];

        return $this->renderSmartyTemplates($templates, $vars);
    }

    /**
     * 使用 Smarty 渲染大屏容器 frame.html
     */
    private function renderFrame(HdActivity $activity, array $features)
    {
        $vars = $this->dataProvider->buildFrameVars($activity, $features);

        $smarty = $this->createSmarty();
        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $templatePath = app()->getRootPath() . 'app/view/hd/screen/frame.html';
        $html = $smarty->fetch($templatePath);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 渲染 Smarty 功能页模板（header + content + footer 组合）
     */
    private function renderSmartyTemplates(array $templates, array $vars)
    {
        $smarty = $this->createSmarty();
        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $basePath = app()->getRootPath() . 'app/view/hd/screen/';
        $html = '';
        foreach ($templates as $tpl) {
            $tplFile = $basePath . 'themes/meepo/' . $tpl . '.html';
            if (file_exists($tplFile)) {
                $html .= $smarty->fetch($tplFile);
            }
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 创建 Smarty 实例
     */
    private function createSmarty(): \Smarty
    {
        $smartyPath = app()->getRootPath() . 'huodong/smarty/Smarty.class.php';
        if (!class_exists('Smarty', false)) {
            require_once $smartyPath;
        }

        $smarty = new \Smarty();
        $smarty->caching = false;
        $smarty->compile_dir = app()->getRuntimePath() . 'hd_templates_c' . DIRECTORY_SEPARATOR;
        $smarty->template_dir = app()->getRootPath() . 'app/view/hd/screen/';

        // 确保编译目录存在
        if (!is_dir($smarty->compile_dir)) {
            mkdir($smarty->compile_dir, 0777, true);
        }

        return $smarty;
    }

    /**
     * 渲染手机端页面（保留原有简化版内联 HTML）
     */
    private function renderMobile(HdActivity $activity, array $features, string $accessCode)
    {
        $title = htmlspecialchars($activity->title);
        $statusText = ['1' => '未开始', '2' => '进行中', '3' => '已结束'];
        $status = $statusText[$activity->status] ?? '未知';
        $apiBase = '/api/hd/screen/' . $accessCode;

        $featureIcons = [
            'qdq' => '📋', 'threedimensionalsign' => '🎯', 'wall' => '💬',
            'danmu' => '💭', 'vote' => '🗳️', 'lottery' => '🎰',
            'choujiang' => '🎲', 'ydj' => '🎳', 'shake' => '📱',
            'game' => '🎮', 'redpacket' => '🧧', 'importlottery' => '📥',
            'kaimu' => '🎬', 'bimu' => '🎞️', 'xiangce' => '📸',
            'xyh' => '🔢', 'xysjh' => '📞',
        ];

        $featureMenu = '';
        foreach ($features as $f) {
            $icon = $featureIcons[$f['feature_code']] ?? '⚡';
            $name = $f['feature_name'];
            $featureMenu .= '<div class="feature-btn" data-code="' . $f['feature_code'] . '">';
            $featureMenu .= '<span class="f-icon">' . $icon . '</span>';
            $featureMenu .= '<span class="f-name">' . $name . '</span>';
            $featureMenu .= '</div>';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{$title} - 艺为微信大屏互动</title>
    <link rel="stylesheet" href="/static/css/screen.css">
</head>
<body>
    <div class="mobile-view" id="app">
        <div class="m-header">
            <h1>{$title}</h1>
            <div class="status">{$status}</div>
        </div>
        <div class="m-body">
            <button class="m-sign-btn" id="btnSign" onclick="doSign()">📋 立即签到</button>
            <div class="m-feature-list">
                {$featureMenu}
            </div>
            <div id="mobileContent"></div>
        </div>
    </div>
    <script>
    window.HD_API_BASE = '{$apiBase}';
    window.HD_ACCESS_CODE = '{$accessCode}';
    window.HD_IS_MOBILE = true;
    function getOpenid(){ var k='hd_openid',v=localStorage.getItem(k); if(!v){v='visitor_'+Math.random().toString(36).substr(2,8);localStorage.setItem(k,v);} return v; }
    function getNickname(){ return localStorage.getItem('hd_nickname') || '访客'; }
    function getAvatar(){ return localStorage.getItem('hd_avatar') || ''; }
    </script>
    <script src="/static/js/mobile.js"></script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 渲染错误页
     */
    private function renderError(string $title, string $msg)
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - 艺为微信大屏互动</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #0a0a1a; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; }
        h1 { font-size: 32px; margin-bottom: 12px; color: #a5b4fc; }
        p { font-size: 16px; color: rgba(255,255,255,0.6); margin-bottom: 24px; }
        a { color: #6366f1; font-size: 14px; }
    </style>
</head>
<body>
    <div>
        <h1>😕 {$title}</h1>
        <p>{$msg}</p>
        <a href="https://wxhd.eivie.cn">返回首页</a>
    </div>
</body>
</html>
HTML;
        return response($html, 404, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}

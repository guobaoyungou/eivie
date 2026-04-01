<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdParticipant;
use app\service\hd\HdActivityService;
use app\service\hd\HdBrandService;
use app\service\hd\HdFrameDataProvider;
use app\service\hd\HdSignService;

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
            // 处理子页面路由（管理员控制、核销）
            $page = $this->request->param('page', '');
            if ($page === 'admin_control' || $page === 'verify') {
                return $this->renderMobileSubPage($activity, $features, $access_code, $page);
            }
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

        // 模块功能页（抽奖/游戏）使用老系统 Smarty 模板 + 新系统数据渲染
        $moduleFeatures = ['lottery', 'game', 'importlottery'];
        if (in_array($feature, $moduleFeatures)) {
            return $this->renderModulePage($activity, $feature, $access_code);
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
     * 渲染模块页面（抽奖/游戏）
     * 使用老系统 Smarty 模板 + 新系统 ddwx_hd_* 表数据
     */
    private function renderModulePage(HdActivity $activity, string $feature, string $accessCode)
    {
        $vars = $this->dataProvider->buildModulePageVars($activity, $feature);
        $baseUrl = '/s/' . $accessCode;

        // 确定模板路径
        $templateDir = '';
        $templatePath = '';

        if ($feature === 'lottery' || $feature === 'importlottery') {
            $themePath = $vars['_theme_path'] ?? 'zjd';
            $templateDir = app()->getRootPath() . 'huodong/Modules/Lottery/templates/front/';
            $templatePath = $templateDir . $themePath . '/index.html';
            if (!file_exists($templatePath)) {
                $templatePath = $templateDir . 'zjd/index.html';
            }
        } elseif ($feature === 'game') {
            $themePath = $vars['_theme_path'] ?? 'car';
            $templateDir = app()->getRootPath() . 'huodong/Modules/Game/templates/front/';
            $templatePath = $templateDir . strtolower($themePath) . '/index.html';
            if (!file_exists($templatePath)) {
                $templatePath = $templateDir . 'car/index.html';
            }
        }

        if (empty($templatePath) || !file_exists($templatePath)) {
            return $this->renderError('模板不存在', '找不到对应的模板文件');
        }

        // 移除内部变量
        unset($vars['_theme_path']);

        $smarty = $this->createSmarty();

        // 设置老系统模板路径变量
        $vars['assets'] = '/static/hd/themes/meepo/assets';
        $vars['module_front_path'] = $feature === 'game'
            ? '/Modules/Game/templates/front'
            : '/Modules/Lottery/templates/front';
        $vars['module_assets'] = $feature === 'game'
            ? '/Modules/Game/templates/assets'
            : '/Modules/Lottery/templates/assets';

        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $html = $smarty->fetch($templatePath);

        // 注入模块URL重写脚本，将 /Modules/module.php 路径映射到ThinkPHP路由
        $html = $this->injectModuleUrlRewriter($html, $baseUrl);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 在模块页面HTML中注入URL重写脚本
     */
    private function injectModuleUrlRewriter(string $html, string $baseUrl): string
    {
        $rewriteScript = <<<JSBLOCK
<script type="text/javascript">
(function(){
    var HD_MODULE_BASE = '{$baseUrl}';
    // 覆盖jQuery的ajax方法来拦截module.php请求
    if (typeof jQuery !== 'undefined') {
        var origAjax = jQuery.ajax;
        jQuery.ajax = function(url, options) {
            if (typeof url === 'object') { options = url; url = options.url; }
            if (!options) options = {};
            if (!url) url = options.url || '';
            var moduleMatch = url.match(/\/Modules\/module\.php\?m=(\w+)&c=(\w+)&a=(\w+)(.*)/i);
            if (moduleMatch) {
                var newUrl = HD_MODULE_BASE + '/module/' + moduleMatch[1] + '/' + moduleMatch[2] + '/' + moduleMatch[3];
                var extra = moduleMatch[4] || '';
                if (extra) { newUrl += extra.charAt(0) === '&' ? '?' + extra.substring(1) : extra; }
                url = newUrl;
            }
            options.url = url;
            return origAjax.call(this, options);
        };
    }
})();
</script>
JSBLOCK;

        // 替换HTML中的静态module URL引用
        $html = preg_replace_callback(
            '#(["\'])/?Modules/module\.php\?m=(\w+)&c=(\w+)&a=(\w+)([^"\']*)\1#',
            function ($matches) use ($baseUrl) {
                $quote = $matches[1];
                $newUrl = $baseUrl . '/module/' . $matches[2] . '/' . $matches[3] . '/' . $matches[4];
                if ($matches[5]) { $newUrl .= $matches[5]; }
                return $quote . $newUrl . $quote;
            },
            $html
        );

        // 注入到</body>之前
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $rewriteScript . "\n</body>", $html);
        } else {
            $html .= "\n" . $rewriteScript;
        }

        return $html;
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
     * 渲染手机端页面（Smarty 模板渲染，支持签到全流程）
     */
    private function renderMobile(HdActivity $activity, array $features, string $accessCode)
    {
        $ua = $this->request->header('User-Agent', '');
        $isWeChat = (bool)preg_match('/MicroMessenger/i', $ua);

        // === 非微信浏览器：展示引导页 ===
        if (!$isWeChat) {
            return $this->renderWxGuide($activity, $accessCode);
        }

        // === 获取用户信息（从 Session 或 WeChatOAuth 中间件注入）===
        $openid = $this->request->hd_wx_openid ?? session('hd_wx_openid') ?? '';
        $nickname = $this->request->hd_wx_nickname ?? session('hd_wx_nickname') ?? '';
        $avatar = $this->request->hd_wx_avatar ?? session('hd_wx_avatar') ?? '';

        // === 读取签到配置 ===
        $screenConfig = $activity->screen_config ?: [];
        $signConfig = [
            'enabled'               => (int)($screenConfig['enabled'] ?? 1),
            'start_time'            => $screenConfig['start_time'] ?? '',
            'end_time'              => $screenConfig['end_time'] ?? '',
            'require_name'          => (int)($screenConfig['require_name'] ?? 0),
            'require_phone'         => (int)($screenConfig['require_phone'] ?? 0),
            'require_company'       => (int)($screenConfig['require_company'] ?? 0),
            'require_position'      => (int)($screenConfig['require_position'] ?? 0),
            'use_wx_avatar'         => (int)($screenConfig['use_wx_avatar'] ?? 1),
            'show_employee_no'      => (int)($screenConfig['show_employee_no'] ?? 0),
            'require_employee_no'   => (int)($screenConfig['require_employee_no'] ?? 0),
            'show_photo'            => (int)($screenConfig['show_photo'] ?? 0),
            'require_photo'         => (int)($screenConfig['require_photo'] ?? 0),
            'show_custom_fields'    => (int)($screenConfig['show_custom_fields'] ?? 0),
            'sign_location_enabled' => (int)($screenConfig['sign_location_enabled'] ?? 0),
        ];
        $signCustomFields = $screenConfig['sign_custom_fields'] ?? [];

        // === 查询参与者状态 ===
        $participant = null;
        if ($openid) {
            $participant = HdParticipant::where('activity_id', $activity->id)
                ->where('openid', $openid)
                ->find();
        }

        $isSigned = $participant && $participant->flag == HdParticipant::FLAG_SIGNED;
        $isAdmin = $participant ? (int)$participant->is_admin : 0;
        $isVerifier = $participant ? (int)$participant->is_verifier : 0;

        // === 判断签到状态 ===
        $signStatus = 'open'; // countdown / open / closed / signed
        $now = time();
        $startTime = $signConfig['start_time'];
        $endTime = $signConfig['end_time'];

        if ($isSigned) {
            $signStatus = 'signed';
        } elseif (!empty($startTime) && $now < strtotime($startTime)) {
            $signStatus = 'countdown';
        } elseif (!empty($endTime) && $now > strtotime($endTime)) {
            $signStatus = 'closed';
        } else {
            $signStatus = 'open';
        }

        // === 构建用户信息数组 ===
        $user = [
            'openid'    => $openid,
            'nickname'  => $nickname,
            'avatar'    => $avatar ?: '/huodong/mobile/template/app/images/default_avatar.png',
            'flag'      => $participant ? $participant->flag : HdParticipant::FLAG_NOT_SIGNED,
            'signorder' => $participant ? $participant->signorder : 0,
            'sign_time' => ($isSigned && $participant->createtime) ? date('Y-m-d H:i', $participant->createtime) : '',
            'status'    => $isSigned ? 1 : 0,
            'datetime'  => ($isSigned && $participant->createtime) ? date('Y-m-d H:i:s', $participant->createtime) : '',
            'info'      => '',
        ];

        // === 构建 Smarty 变量 ===
        $vars = [
            'title'                => htmlspecialchars($activity->title),
            'activity_title'       => htmlspecialchars($activity->title),
            'access_code'          => $accessCode,
            'api_base'             => '/api/hd/screen/' . $accessCode,
            'user'                 => $user,
            'sign_config'          => $signConfig,
            'sign_custom_fields'   => $signCustomFields,
            'sign_status'          => $signStatus,
            'sign_start_time'      => $startTime,
            'sign_end_time'        => $endTime,
            'server_time'          => $now,
            'is_admin'             => $isAdmin,
            'is_verifier'          => $isVerifier,
            'features'             => $features,
            // 手机端配置变量（严格遵循 $mobile_ 前缀规范）
            'mobile_bg'            => $screenConfig['mobile_bg_image'] ?? '',
            'mobile_activity_image'=> $screenConfig['mobile_activity_image'] ?? '',
            'mobile_hide_avatar'   => (int)($screenConfig['mobile_hide_avatar'] ?? 0),
            'mobile_quick_message' => (int)($screenConfig['mobile_quick_message'] ?? 0),
            'welcome_text'         => $screenConfig['mobile_welcome_text'] ?? '欢迎参与本次活动',
            'btn_text'             => $screenConfig['mobile_btn_text'] ?? '参 与 活 动',
            'btn_image'            => $screenConfig['mobile_btn_image'] ?? '',
            'openid'               => $openid,
            'menucolor'            => '#333',
        ];

        // === 根据签到状态选择模板 ===
        $templateDir = app()->getRootPath() . 'huodong/mobile/template/';
        $smarty = $this->createSmarty();
        $smarty->template_dir = $templateDir;

        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $html = '';

        switch ($signStatus) {
            case 'signed':
                // 已签到 → 活动控制台
                $html .= $smarty->fetch($templateDir . 'app_header.html');
                $html .= $smarty->fetch($templateDir . 'app_qd.html');
                break;

            case 'countdown':
                // 倒计时
                $html .= $smarty->fetch($templateDir . 'app_header.html');
                $html .= $smarty->fetch($templateDir . 'app_countdown.html');
                break;

            case 'closed':
                // 签到已结束
                $html .= $smarty->fetch($templateDir . 'app_header.html');
                $html .= $smarty->fetch($templateDir . 'app_closed.html');
                break;

            case 'open':
            default:
                // 签到表单
                $html .= $smarty->fetch($templateDir . 'app_header.html');
                $html .= $smarty->fetch($templateDir . 'app_register.html');
                break;
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 渲染手机端子页面（管理员控制 / 核销）
     */
    private function renderMobileSubPage(HdActivity $activity, array $features, string $accessCode, string $page)
    {
        $ua = $this->request->header('User-Agent', '');
        $isWeChat = (bool)preg_match('/MicroMessenger/i', $ua);

        if (!$isWeChat) {
            return $this->renderWxGuide($activity, $accessCode);
        }

        $openid = $this->request->hd_wx_openid ?? session('hd_wx_openid') ?? '';

        // 校验用户已签到
        $participant = null;
        if ($openid) {
            $participant = HdParticipant::where('activity_id', $activity->id)
                ->where('openid', $openid)
                ->find();
        }
        if (!$participant || $participant->flag != HdParticipant::FLAG_SIGNED) {
            return response('<script>alert("请先完成签到");history.back();</script>', 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // 权限校验
        if ($page === 'admin_control' && !$participant->isAdmin()) {
            return response('<script>alert("您没有管理员权限");history.back();</script>', 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }
        if ($page === 'verify' && !$participant->isVerifier()) {
            return response('<script>alert("您没有核销权限");history.back();</script>', 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $vars = [
            'title'          => htmlspecialchars($activity->title),
            'activity_title' => htmlspecialchars($activity->title),
            'access_code'    => $accessCode,
            'api_base'       => '/api/hd/screen/' . $accessCode,
            'openid'         => $openid,
        ];

        $templateDir = app()->getRootPath() . 'huodong/mobile/template/';
        $smarty = $this->createSmarty();
        $smarty->template_dir = $templateDir;
        foreach ($vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $templateFile = $page === 'admin_control' ? 'app_admin_control.html' : 'app_verify.html';
        $html = $smarty->fetch($templateDir . 'app_header.html');
        $html .= $smarty->fetch($templateDir . $templateFile);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 非微信浏览器引导页
     */
    private function renderWxGuide(HdActivity $activity, string $accessCode)
    {
        $title = htmlspecialchars($activity->title);
        $qrcodeUrl = 'https://wxhd.eivie.cn/s/' . $accessCode;
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>{$title}</title>
<style>
body{margin:0;font-family:-apple-system,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.guide-box{text-align:center;padding:40px 30px;}
.guide-icon{font-size:60px;margin-bottom:20px;}
.guide-title{color:#fff;font-size:20px;font-weight:bold;margin-bottom:10px;}
.guide-desc{color:rgba(255,255,255,0.8);font-size:14px;margin-bottom:30px;line-height:1.6;}
.guide-qrcode{background:#fff;padding:15px;border-radius:12px;display:inline-block;margin-bottom:15px;}
.guide-qrcode img{width:180px;height:180px;display:block;}
.guide-tip{color:rgba(255,255,255,0.6);font-size:12px;}
</style>
</head>
<body>
<div class="guide-box">
<div class="guide-icon">📱</div>
<div class="guide-title">{$title}</div>
<div class="guide-desc">请使用微信扫码打开</div>
<div class="guide-qrcode"><img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={$qrcodeUrl}" alt="二维码"></div>
<div class="guide-tip">长按二维码保存后，用微信扫一扫打开</div>
</div>
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

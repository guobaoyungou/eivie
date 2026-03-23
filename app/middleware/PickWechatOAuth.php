<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Db;
use think\facade\Session;
use think\Request;
use think\Response;

/**
 * 选片H5微信OAuth授权中间件
 * 
 * 用于选片H5页面，通过微信OAuth2.0静默授权（scope=snsapi_base）获取用户OpenID
 * 非微信浏览器访问时返回提示
 */
class PickWechatOAuth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // 排除回调和通知接口（无需OpenID）
        $action = $request->action();
        $excludeActions = ['oauth_callback', 'notify', 'start_oauth'];
        if (in_array($action, $excludeActions)) {
            return $next($request);
        }

        // 检测是否微信浏览器
        $userAgent = $request->header('user-agent', '');
        $isWechat = strpos($userAgent, 'MicroMessenger') !== false;

        // AJAX请求（API调用）不强制跳转，由前端处理
        $isAjax = $request->isAjax() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if (!$isWechat && !$isAjax) {
            return json(['code' => 403, 'msg' => '请使用微信扫码打开']);
        }

        // 检查Session中是否已有OpenID
        $openid = Session::get('pick_openid', '');

        if (!empty($openid)) {
            // 已有OpenID，继续
            return $next($request);
        }

        // 对API请求返回需要授权标记，前端处理跳转
        if ($isAjax) {
            return $next($request);
        }

        // 页面请求 + 在微信中 + 无OpenID：触发OAuth
        $qr = $request->get('qr', '');
        return $this->redirectToOAuth($request, $qr);
    }

    /**
     * 重定向到微信OAuth授权
     */
    protected function redirectToOAuth(Request $request, string $qr): Response
    {
        // 需要确定aid来获取正确的appid
        // 暂时从qrcode表获取
        $qrcode = Db::name('ai_travel_photo_qrcode')->where('qrcode', $qr)->find();
        if (!$qrcode) {
            return json(['code' => 400, 'msg' => '无效的二维码']);
        }

        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $qrcode['portrait_id'])->find();
        if (!$portrait) {
            return json(['code' => 400, 'msg' => '人像不存在']);
        }

        $aid = $portrait['aid'];
        $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
        $appid = $wxset['appid'] ?? '';

        if (empty($appid)) {
            return json(['code' => 500, 'msg' => '微信配置缺失']);
        }

        // 构建OAuth回调URL
        $callbackUrl = $request->domain() . '/index.php?s=/api/ai_travel_photo/pick/oauth_callback';
        $redirectUri = urlencode($callbackUrl);
        $state = 'qr_' . $qr;

        $oauthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";

        return redirect($oauthUrl);
    }
}

<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdParticipant;
use app\model\hd\HdBusinessConfig;

/**
 * 微信 OAuth 授权中间件
 * 用于手机端互动页面，自动判断使用租户自有公众号或平台公众号
 *
 * 流程：
 * 1. 判断是否微信浏览器，非微信直接放行
 * 2. Session 中已有 openid 则直接放行
 * 3. 根据 access_code 查询活动 → 商家配置(wxfw_appid)
 * 4. 若商家有自有公众号则使用，否则使用平台公众号(admin_setapp_mp)
 * 5. 若 URL 无 code，重定向至微信 OAuth 授权页
 * 6. 若 URL 有 code，换取 access_token → 获取用户信息 → 写入 Session
 */
class WeChatOAuth
{
    /**
     * Session key 前缀
     */
    const SESSION_OPENID   = 'hd_wx_openid';
    const SESSION_NICKNAME = 'hd_wx_nickname';
    const SESSION_AVATAR   = 'hd_wx_avatar';
    const SESSION_MID      = 'hd_wx_mid';

    public function handle(Request $request, Closure $next): Response
    {
        // 1. 非微信浏览器直接放行
        if (!$this->isWeChatBrowser($request)) {
            return $next($request);
        }

        // 2. 已有授权信息，直接放行
        $openid = session(self::SESSION_OPENID);
        if ($openid) {
            $request->hd_wx_openid  = $openid;
            $request->hd_wx_nickname = session(self::SESSION_NICKNAME) ?: '';
            $request->hd_wx_avatar   = session(self::SESSION_AVATAR) ?: '';
            $request->hd_wx_mid      = (int)(session(self::SESSION_MID) ?: 0);
            return $next($request);
        }

        // 3. 获取活动 access_code，解析租户
        $accessCode = $request->param('access_code', '');
        if (empty($accessCode)) {
            return $next($request);
        }

        $activity = $request->hd_activity ?? HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return $next($request);
        }

        // 4. 获取公众号配置（优先商家自有，其次平台公众号）
        $wxConfig = $this->getWxConfig((int)$activity->aid, (int)$activity->bid);
        if (!$wxConfig || empty($wxConfig['appid'])) {
            // 无可用公众号配置，直接放行（降级为非微信模式）
            Log::warning("[WeChatOAuth] 活动[{$accessCode}]无可用公众号配置，跳过OAuth");
            return $next($request);
        }

        // 5. 处理 OAuth 回调（URL 中有 code 参数）
        $code = $request->param('code', '');
        if ($code) {
            return $this->handleCallback($request, $next, $code, $wxConfig, $activity);
        }

        // 6. 未授权，重定向至微信 OAuth 页面
        return $this->redirectToOAuth($request, $wxConfig);
    }

    /**
     * 判断是否微信浏览器
     */
    protected function isWeChatBrowser(Request $request): bool
    {
        $ua = strtolower($request->header('user-agent', ''));
        return strpos($ua, 'micromessenger') !== false;
    }

    /**
     * 获取微信公众号配置
     * 优先级：商家自有公众号 → 平台公众号
     *
     * @return array{appid: string, appsecret: string, source: string}|null
     */
    protected function getWxConfig(int $aid, int $bid): ?array
    {
        // 方式1：商家自有公众号 (hd_business_config 或 business 表的 wxfw_appid)
        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        if ($bizConfig && !empty($bizConfig->wxfw_appid) && !empty($bizConfig->wxfw_appsecret)) {
            return [
                'appid'     => $bizConfig->wxfw_appid,
                'appsecret' => $bizConfig->wxfw_appsecret,
                'source'    => 'tenant',
            ];
        }

        // 方式2：检查商家系统设置表 business_sysset（通过aid关联，含wxfw_appid但无appsecret）
        // 注意：business_sysset 表仅有 wxfw_appid，无 wxfw_appsecret，故此处无法构成完整的OAuth配置
        // 如需要可查询 business 主表补充，目前跳过此步骤直接查平台公众号

        // 方式3：平台公众号 (admin_setapp_mp 表)
        $platformMp = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
        if ($platformMp && !empty($platformMp['appid']) && !empty($platformMp['appsecret'])) {
            return [
                'appid'     => $platformMp['appid'],
                'appsecret' => $platformMp['appsecret'],
                'source'    => 'platform',
            ];
        }

        return null;
    }

    /**
     * 重定向到微信 OAuth 授权页
     */
    protected function redirectToOAuth(Request $request, array $wxConfig): Response
    {
        // 构建回调 URL（当前页面 URL，去掉已有的 code/state 参数）
        $currentUrl = $request->url(true);
        $currentUrl = $this->removeQueryParams($currentUrl, ['code', 'state']);

        $scope = 'snsapi_userinfo'; // 获取用户信息
        $state = md5(uniqid((string)mt_rand(), true));
        session('hd_wx_oauth_state', $state);

        $oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize'
            . '?appid=' . $wxConfig['appid']
            . '&redirect_uri=' . urlencode($currentUrl)
            . '&response_type=code'
            . '&scope=' . $scope
            . '&state=' . $state
            . '#wechat_redirect';

        return redirect($oauthUrl);
    }

    /**
     * 处理 OAuth 回调
     */
    protected function handleCallback(Request $request, Closure $next, string $code, array $wxConfig, $activity): Response
    {
        try {
            // 1. 用 code 换取 access_token
            $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token'
                . '?appid=' . $wxConfig['appid']
                . '&secret=' . $wxConfig['appsecret']
                . '&code=' . $code
                . '&grant_type=authorization_code';

            $tokenResp = $this->httpGet($tokenUrl);
            $tokenData = json_decode($tokenResp, true);

            if (empty($tokenData) || !empty($tokenData['errcode'])) {
                Log::error('[WeChatOAuth] 获取access_token失败: ' . ($tokenResp ?: 'empty'));
                return $next($request);
            }

            $openid      = $tokenData['openid'] ?? '';
            $accessToken = $tokenData['access_token'] ?? '';

            if (empty($openid)) {
                Log::error('[WeChatOAuth] openid为空: ' . json_encode($tokenData));
                return $next($request);
            }

            // 2. 获取用户信息
            $nickname = '';
            $avatar   = '';
            if (!empty($accessToken) && ($tokenData['scope'] ?? '') === 'snsapi_userinfo') {
                $userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo'
                    . '?access_token=' . $accessToken
                    . '&openid=' . $openid
                    . '&lang=zh_CN';
                $userResp = $this->httpGet($userInfoUrl);
                $userInfo = json_decode($userResp, true);

                if (!empty($userInfo) && empty($userInfo['errcode'])) {
                    $nickname = $userInfo['nickname'] ?? '';
                    $avatar   = $userInfo['headimgurl'] ?? '';
                }
            }

            // 3. 写入 Session
            session(self::SESSION_OPENID, $openid);
            session(self::SESSION_NICKNAME, $nickname);
            session(self::SESSION_AVATAR, $avatar);

            // 4. 创建或更新参与者记录
            $mid = $this->syncParticipant($activity, $openid, $nickname, $avatar);
            session(self::SESSION_MID, $mid);

            // 5. 注入到请求
            $request->hd_wx_openid   = $openid;
            $request->hd_wx_nickname = $nickname;
            $request->hd_wx_avatar   = $avatar;
            $request->hd_wx_mid      = $mid;

            // 6. 重定向去掉 code/state 参数（避免刷新时重复使用 code）
            $cleanUrl = $this->removeQueryParams($request->url(true), ['code', 'state']);
            return redirect($cleanUrl);

        } catch (\Throwable $e) {
            Log::error('[WeChatOAuth] OAuth回调异常: ' . $e->getMessage());
            return $next($request);
        }
    }

    /**
     * 同步参与者记录
     * 查找或创建 hd_participant，返回关联的 member id
     */
    protected function syncParticipant($activity, string $openid, string $nickname, string $avatar): int
    {
        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->find();

        if (!$participant) {
            $participant = new HdParticipant();
            $participant->aid         = $activity->aid;
            $participant->bid         = $activity->bid;
            $participant->activity_id = $activity->id;
            $participant->openid      = $openid;
            $participant->nickname    = $nickname;
            $participant->avatar      = $avatar;
            $participant->flag        = HdParticipant::FLAG_NOT_SIGNED;
            $participant->createtime  = time();
            $participant->save();
        } else {
            // 更新用户信息（微信昵称/头像可能变化）
            $needUpdate = false;
            if ($nickname && $participant->nickname !== $nickname) {
                $participant->nickname = $nickname;
                $needUpdate = true;
            }
            if ($avatar && $participant->avatar !== $avatar) {
                $participant->avatar = $avatar;
                $needUpdate = true;
            }
            if ($needUpdate) {
                $participant->save();
            }
        }

        // 查找关联的 member (通过 openid → member 表)
        $member = Db::name('member')
            ->where('aid', $activity->aid)
            ->where('openid', $openid)
            ->find();

        if ($member) {
            if ($participant->mid !== (int)$member['id']) {
                $participant->mid = (int)$member['id'];
                $participant->save();
            }
            return (int)$member['id'];
        }

        return 0;
    }

    /**
     * HTTP GET 请求
     */
    protected function httpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            Log::error('[WeChatOAuth] CURL错误: ' . curl_error($ch) . ' URL: ' . $url);
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        return $result ?: '';
    }

    /**
     * 移除 URL 中的指定查询参数
     */
    protected function removeQueryParams(string $url, array $params): string
    {
        $parsed = parse_url($url);
        if (empty($parsed['query'])) {
            return $url;
        }

        parse_str($parsed['query'], $queryArr);
        foreach ($params as $p) {
            unset($queryArr[$p]);
        }

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '')
            . ($parsed['path'] ?? '');

        if (!empty($queryArr)) {
            $base .= '?' . http_build_query($queryArr);
        }

        if (!empty($parsed['fragment'])) {
            $base .= '#' . $parsed['fragment'];
        }

        return $base;
    }
}

<?php
declare(strict_types=1);

namespace app\controller\hd;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdBusinessConfig;

/**
 * 大屏互动 - 微信事件回调控制器
 * 处理微信服务号的事件推送（扫码关注 / 已关注用户扫码等）
 *
 * 流程：
 * 1. 大屏显示微信带参数二维码（scene_str = access_code）
 * 2. 新用户扫码关注 → subscribe 事件（EventKey = qrscene_{access_code}）
 * 3. 已关注用户扫码 → SCAN 事件（EventKey = {access_code}）
 * 4. 本控制器解析事件，提取 access_code，通过客服消息向用户推送签到页链接
 */
class HdWxCallbackController
{
    /**
     * 微信服务器验证 + 事件接收
     * GET  /api/hd/wx/callback - 验证服务器（echostr）
     * POST /api/hd/wx/callback - 接收事件推送
     */
    public function handle()
    {
        $method = request()->method();

        if ($method === 'GET') {
            return $this->verify();
        }

        return $this->receiveEvent();
    }

    /**
     * 微信服务器验证（GET请求）
     * 微信配置服务器URL时，会发送GET请求验证，需原样返回echostr
     */
    protected function verify()
    {
        $signature = input('get.signature', '');
        $timestamp = input('get.timestamp', '');
        $nonce     = input('get.nonce', '');
        $echostr   = input('get.echostr', '');

        // 获取 token（优先从请求参数中的 aid 获取，否则尝试通用 token）
        $token = $this->getVerifyToken();

        if ($this->checkSignature($token, $signature, $timestamp, $nonce)) {
            return response($echostr, 200, ['Content-Type' => 'text/plain']);
        }

        Log::warning('[HdWxCallback] 签名验证失败');
        return response('fail', 403, ['Content-Type' => 'text/plain']);
    }

    /**
     * 接收微信事件推送（POST请求）
     */
    protected function receiveEvent()
    {
        try {
            // 使用 ThinkPHP request()->getContent() 获取原始POST数据
            // 注意：不能用 file_get_contents('php://input')，因为 ThinkPHP 框架
            // 在请求初始化时已读取过该流，后续再读可能返回空
            $xml = request()->getContent();

            if (empty($xml)) {
                Log::warning('[HdWxCallback] 收到空的POST请求体, method=' . request()->method()
                    . ', content-type=' . request()->contentType()
                    . ', ip=' . request()->ip());
                return response('success', 200, ['Content-Type' => 'text/plain']);
            }

            Log::info('[HdWxCallback] 收到事件推送, 长度=' . strlen($xml) . ', 内容: ' . $xml);

            // 解析XML（PHP 7.4 兼容）
            libxml_disable_entity_loader(true);
            $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$data) {
                Log::error('[HdWxCallback] XML解析失败, 原始数据: ' . substr($xml, 0, 500));
                return response('success', 200, ['Content-Type' => 'text/plain']);
            }

            $msgType  = strtolower(trim((string)$data->MsgType));
            $event    = strtolower(trim((string)($data->Event ?? '')));
            $openid   = trim((string)$data->FromUserName);
            $toUser   = trim((string)$data->ToUserName);
            $eventKey = trim((string)($data->EventKey ?? ''));

            Log::info("[HdWxCallback] 解析结果: MsgType={$msgType}, Event={$event}, "
                . "FromUser={$openid}, EventKey={$eventKey}");

            // 只处理事件消息
            if ($msgType !== 'event') {
                Log::info("[HdWxCallback] 非事件消息，跳过: MsgType={$msgType}");
                return response('success', 200, ['Content-Type' => 'text/plain']);
            }

            // 处理扫码事件（新关注 subscribe + 已关注用户 scan）
            if ($event === 'subscribe' || $event === 'scan') {
                $this->handleScanEvent($openid, $toUser, $event, $eventKey);
            } else {
                Log::info("[HdWxCallback] 未处理的事件类型: {$event}");
            }

        } catch (\Throwable $e) {
            Log::error('[HdWxCallback] receiveEvent异常: ' . $e->getMessage()
                . ' at ' . $e->getFile() . ':' . $e->getLine()
                . "\n" . $e->getTraceAsString());
        }

        return response('success', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * 处理扫码事件
     * subscribe事件（新用户扫码关注）: EventKey = "qrscene_{access_code}"
     * SCAN事件（已关注用户扫码）:     EventKey = "{access_code}"
     *
     * 两种事件都会向用户推送签到页链接
     */
    protected function handleScanEvent(string $openid, string $toUser, string $event, string $eventKey)
    {
        if (empty($eventKey)) {
            Log::info('[HdWxCallback] 普通关注（无带参数二维码），无EventKey，跳过');
            return;
        }

        // 提取 access_code
        // subscribe 事件: EventKey = "qrscene_xxx"，需去掉前缀
        // scan 事件:      EventKey = "xxx"，直接使用
        $accessCode = $eventKey;
        if ($event === 'subscribe' && strpos($eventKey, 'qrscene_') === 0) {
            $accessCode = substr($eventKey, 8); // 去掉 "qrscene_" 前缀
        }

        if (empty($accessCode)) {
            Log::warning('[HdWxCallback] 提取access_code为空, eventKey=' . $eventKey);
            return;
        }

        $eventDesc = ($event === 'subscribe') ? '新关注' : '已关注用户扫码';
        Log::info("[HdWxCallback] {$eventDesc}: openid={$openid}, event={$event}, access_code={$accessCode}");

        // 查找活动
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            Log::warning("[HdWxCallback] 活动不存在: access_code={$accessCode}");
            return;
        }

        Log::info("[HdWxCallback] 找到活动: id={$activity->id}, title={$activity->title}, aid={$activity->aid}, bid={$activity->bid}");

        // 检查是否启用了强制关注公众号
        $screenConfig = $activity->screen_config ?: [];
        $forceWxAuth = (int)($screenConfig['mobile_force_wx_auth'] ?? 1);
        if (!$forceWxAuth) {
            Log::info("[HdWxCallback] 活动未启用强制关注(mobile_force_wx_auth=0)，跳过推送");
            return;
        }

        // 构建签到页URL
        $signUrl = request()->scheme() . '://' . request()->host() . '/s/' . $accessCode;

        // 获取微信公众号配置（用于发送客服消息）
        $wxConfig = $this->getWxConfig((int)$activity->aid, (int)$activity->bid);
        if (!$wxConfig) {
            Log::error("[HdWxCallback] 无法获取微信配置, aid={$activity->aid}, bid={$activity->bid}");
            return;
        }

        Log::info("[HdWxCallback] 使用微信配置: source={$wxConfig['source']}, appid={$wxConfig['appid']}");

        // 获取 access_token
        $accessToken = $this->getAccessToken($wxConfig['appid'], $wxConfig['appsecret']);
        if (!$accessToken) {
            Log::error('[HdWxCallback] 获取access_token失败, appid=' . $wxConfig['appid']);
            return;
        }

        // 发送客服消息（包含签到页链接）
        $activityTitle = $activity->title ?: '活动';
        $message = "欢迎参加「{$activityTitle}」！\n\n点击下方链接立即签到：\n" . $signUrl;

        $ok = $this->sendTextMessage($accessToken, $openid, $message);

        if ($ok) {
            Log::info("[HdWxCallback] ✓ 成功推送签到页给用户 {$openid} ({$eventDesc}): {$signUrl}");
        } else {
            Log::error("[HdWxCallback] ✗ 推送签到页失败: openid={$openid}, url={$signUrl}");
        }
    }

    /**
     * 发送客服文本消息
     */
    protected function sendTextMessage(string $accessToken, string $openid, string $content): bool
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $accessToken;
        $postData = json_encode([
            'touser'  => $openid,
            'msgtype' => 'text',
            'text'    => [
                'content' => $content,
            ],
        ], JSON_UNESCAPED_UNICODE);

        Log::info('[HdWxCallback] 发送客服消息: touser=' . $openid . ', data=' . $postData);

        $result = $this->httpPost($url, $postData);

        if ($result === '') {
            Log::error('[HdWxCallback] 客服消息API请求失败（空响应）');
            return false;
        }

        $data = json_decode($result, true);

        if (!is_array($data)) {
            Log::error('[HdWxCallback] 客服消息API响应非JSON: ' . substr($result, 0, 200));
            return false;
        }

        // errcode=0 或不存在 errcode 表示成功
        if (!empty($data['errcode']) && (int)$data['errcode'] !== 0) {
            Log::error('[HdWxCallback] 客服消息发送失败: errcode=' . $data['errcode']
                . ', errmsg=' . ($data['errmsg'] ?? 'unknown'));
            return false;
        }

        return true;
    }

    /**
     * 获取微信公众号配置
     * 优先级：商家自有公众号 → 平台公众号
     */
    protected function getWxConfig(int $aid, int $bid): ?array
    {
        // 商家自有公众号
        try {
            $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
            if ($bizConfig && !empty($bizConfig->wxfw_appid) && !empty($bizConfig->wxfw_appsecret)) {
                Log::info("[HdWxCallback] 使用商家自有公众号, bid={$bid}, appid={$bizConfig->wxfw_appid}");
                return [
                    'appid'     => $bizConfig->wxfw_appid,
                    'appsecret' => $bizConfig->wxfw_appsecret,
                    'source'    => 'tenant',
                ];
            }
        } catch (\Throwable $e) {
            Log::error('[HdWxCallback] 查询HdBusinessConfig异常: ' . $e->getMessage());
        }

        // 平台公众号
        try {
            $platformMp = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            if ($platformMp && !empty($platformMp['appid']) && !empty($platformMp['appsecret'])) {
                Log::info("[HdWxCallback] 使用平台公众号, aid={$aid}, appid={$platformMp['appid']}");
                return [
                    'appid'     => $platformMp['appid'],
                    'appsecret' => $platformMp['appsecret'],
                    'source'    => 'platform',
                ];
            }
        } catch (\Throwable $e) {
            Log::error('[HdWxCallback] 查询admin_setapp_mp异常: ' . $e->getMessage());
        }

        Log::warning("[HdWxCallback] 未找到任何可用的微信公众号配置, aid={$aid}, bid={$bid}");
        return null;
    }

    /**
     * 获取微信服务端 access_token（带缓存）
     */
    protected function getAccessToken(string $appid, string $appsecret): ?string
    {
        $cacheKey = 'hd_wx_access_token:' . $appid;
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info('[HdWxCallback] 使用缓存的access_token, appid=' . $appid);
            return $cached;
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential'
            . '&appid=' . $appid
            . '&secret=' . $appsecret;

        $result = $this->httpGet($url);

        if ($result === '') {
            Log::error('[HdWxCallback] 获取access_token请求失败（空响应）, appid=' . $appid);
            return null;
        }

        $data = json_decode($result, true);

        if (empty($data['access_token'])) {
            Log::error('[HdWxCallback] 获取access_token失败: ' . $result);
            return null;
        }

        // 缓存 access_token（提前100秒过期）
        $expiresIn = ($data['expires_in'] ?? 7200) - 100;
        Cache::set($cacheKey, $data['access_token'], $expiresIn);

        Log::info('[HdWxCallback] 成功获取新access_token, appid=' . $appid . ', expires_in=' . $expiresIn);
        return $data['access_token'];
    }

    /**
     * 获取验证 token
     * 从平台公众号配置中读取 token，或使用默认值
     */
    protected function getVerifyToken(): string
    {
        // 尝试从平台公众号配置读取
        try {
            $platformMp = Db::name('admin_setapp_mp')->order('id asc')->find();
            if ($platformMp && !empty($platformMp['token'])) {
                return $platformMp['token'];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 默认 token
        return 'hd_wx_callback_token';
    }

    /**
     * 验证微信签名
     */
    protected function checkSignature(string $token, string $signature, string $timestamp, string $nonce): bool
    {
        if (empty($signature) || empty($timestamp) || empty($nonce)) {
            return false;
        }

        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode('', $tmpArr);
        $tmpStr = sha1($tmpStr);

        return $tmpStr === $signature;
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
            Log::error('[HdWxCallback] CURL GET错误: ' . curl_error($ch) . ', URL: ' . $url);
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        return $result ?: '';
    }

    /**
     * HTTP POST 请求
     */
    protected function httpPost(string $url, string $data): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            Log::error('[HdWxCallback] CURL POST错误: ' . curl_error($ch) . ', URL: ' . $url);
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        return $result ?: '';
    }
}

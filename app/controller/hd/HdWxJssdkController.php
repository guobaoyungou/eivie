<?php
declare(strict_types=1);

namespace app\controller\hd;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdBusinessConfig;

/**
 * 大屏互动 - 微信 JS-SDK 控制器
 * 提供 JSSDK 签名配置，用于手机端调用微信 JS-SDK（分享/扫码/拍照等）
 */
class HdWxJssdkController extends HdBaseController
{
    /**
     * 获取 JSSDK 签名配置
     * GET /api/hd/wx/jssdk?access_code=xxx&url=xxx
     *
     * 返回签名数据供前端 wx.config() 使用
     */
    public function config()
    {
        $accessCode = input('get.access_code', '');
        $url = input('get.url', '');

        if (empty($url)) {
            $url = request()->header('referer', '');
        }
        if (empty($url)) {
            return $this->error('缺少页面URL参数');
        }

        // 获取活动信息 → 商家配置 → 公众号
        $activity = null;
        $aid = 0;
        $bid = 0;

        if ($accessCode) {
            $activity = HdActivity::where('access_code', $accessCode)->find();
            if ($activity) {
                $aid = (int)$activity->aid;
                $bid = (int)$activity->bid;
            }
        }

        if (!$aid) {
            $aid = $this->getAid();
            $bid = $this->getBid();
        }

        // 获取 appid 和 appsecret
        $wxConfig = $this->getWxAppConfig($aid, $bid);
        if (!$wxConfig) {
            return $this->error('未配置公众号信息');
        }

        $appid = $wxConfig['appid'];
        $appsecret = $wxConfig['appsecret'];

        // 获取 jsapi_ticket
        $ticket = $this->getJsApiTicket($appid, $appsecret);
        if (!$ticket) {
            return $this->error('获取jsapi_ticket失败');
        }

        // 生成签名
        $noncestr = md5(uniqid((string)mt_rand(), true));
        $timestamp = time();

        $signStr = 'jsapi_ticket=' . $ticket
            . '&noncestr=' . $noncestr
            . '&timestamp=' . $timestamp
            . '&url=' . $url;

        $signature = sha1($signStr);

        return $this->success([
            'appId'     => $appid,
            'timestamp' => $timestamp,
            'nonceStr'  => $noncestr,
            'signature' => $signature,
        ]);
    }

    /**
     * 获取微信公众号配置
     * 优先商家自有 → 平台公众号
     */
    protected function getWxAppConfig(int $aid, int $bid): ?array
    {
        // 1. 商家自有
        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        if ($bizConfig && !empty($bizConfig->wxfw_appid) && !empty($bizConfig->wxfw_appsecret)) {
            return [
                'appid'     => $bizConfig->wxfw_appid,
                'appsecret' => $bizConfig->wxfw_appsecret,
            ];
        }

        // 2. 商家系统设置表（business_sysset 仅有 wxfw_appid 无 appsecret，跳过）
        // ddwx_business_set 表不存在，直接查平台公众号

        // 3. 平台公众号
        $mp = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
        if ($mp && !empty($mp['appid']) && !empty($mp['appsecret'])) {
            return [
                'appid'     => $mp['appid'],
                'appsecret' => $mp['appsecret'],
            ];
        }

        return null;
    }

    /**
     * 获取 jsapi_ticket（缓存7200秒）
     */
    protected function getJsApiTicket(string $appid, string $appsecret): string
    {
        $cacheKey = 'hd_jsapi_ticket:' . $appid;
        $ticket = Cache::get($cacheKey);
        if ($ticket) {
            return $ticket;
        }

        // 先获取 access_token
        $accessToken = $this->getAccessToken($appid, $appsecret);
        if (!$accessToken) {
            return '';
        }

        // 获取 jsapi_ticket
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket'
            . '?access_token=' . $accessToken
            . '&type=jsapi';

        $resp = $this->httpGet($url);
        $data = json_decode($resp, true);

        if (empty($data) || ($data['errcode'] ?? -1) !== 0) {
            Log::error('[HdWxJssdk] 获取jsapi_ticket失败: ' . ($resp ?: 'empty'));
            return '';
        }

        $ticket = $data['ticket'] ?? '';
        if ($ticket) {
            Cache::set($cacheKey, $ticket, 7000);
        }

        return $ticket;
    }

    /**
     * 获取 access_token（缓存7200秒）
     */
    protected function getAccessToken(string $appid, string $appsecret): string
    {
        $cacheKey = 'hd_wx_access_token:' . $appid;
        $token = Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token'
            . '?grant_type=client_credential'
            . '&appid=' . $appid
            . '&secret=' . $appsecret;

        $resp = $this->httpGet($url);
        $data = json_decode($resp, true);

        if (empty($data) || !empty($data['errcode'])) {
            Log::error('[HdWxJssdk] 获取access_token失败: ' . ($resp ?: 'empty'));
            return '';
        }

        $token = $data['access_token'] ?? '';
        if ($token) {
            Cache::set($cacheKey, $token, 7000);
        }

        return $token;
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
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            Log::error('[HdWxJssdk] CURL错误: ' . curl_error($ch));
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        return $result ?: '';
    }
}

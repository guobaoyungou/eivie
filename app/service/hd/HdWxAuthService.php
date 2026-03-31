<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdPlan;

/**
 * 大屏互动 - 微信授权认证服务
 * 处理微信 OAuth 登录与公司信息绑定
 */
class HdWxAuthService
{
    /**
     * 获取微信 OAuth 授权 URL
     *
     * @param string $redirectUri 回调地址
     * @return array
     */
    public function getOAuthUrl(string $redirectUri = ''): array
    {
        try {
            if (empty($redirectUri)) {
                $redirectUri = 'https://wxhd.eivie.cn/login';
            }

            // 获取平台公众号配置
            $admin = Db::name('admin')->order('id asc')->find();
            $aid = $admin ? (int)$admin['id'] : 1;

            $platformMp = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            if (!$platformMp || empty($platformMp['appid'])) {
                return ['code' => 1, 'msg' => '平台公众号未配置'];
            }

            // 生成 state 防 CSRF
            $state = md5(uniqid((string)mt_rand(), true));
            Cache::set('hd_wx_oauth_state:' . $state, 1, 600); // 10分钟有效

            $oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize'
                . '?appid=' . $platformMp['appid']
                . '&redirect_uri=' . urlencode($redirectUri)
                . '&response_type=code'
                . '&scope=snsapi_userinfo'
                . '&state=' . $state
                . '#wechat_redirect';

            return [
                'code' => 0,
                'msg'  => 'success',
                'data' => [
                    'oauth_url' => $oauthUrl,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('[HdWxAuth] 获取OAuth URL失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '获取授权地址失败'];
        }
    }

    /**
     * 微信授权登录
     * 使用 OAuth code 换取 openid，检查是否已绑定商家
     *
     * @param string $code   微信 OAuth 回调 code
     * @param string $state  CSRF state
     * @return array
     */
    public function wxLogin(string $code, string $state = ''): array
    {
        try {
            // 获取平台公众号配置
            $admin = Db::name('admin')->order('id asc')->find();
            $aid = $admin ? (int)$admin['id'] : 1;

            $platformMp = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            if (!$platformMp || empty($platformMp['appid']) || empty($platformMp['appsecret'])) {
                return ['code' => 1, 'msg' => '平台公众号未配置'];
            }

            // 用 code 换取 access_token + openid
            $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token'
                . '?appid=' . $platformMp['appid']
                . '&secret=' . $platformMp['appsecret']
                . '&code=' . $code
                . '&grant_type=authorization_code';

            $tokenResp = $this->httpGet($tokenUrl);
            $tokenData = json_decode($tokenResp, true);

            if (empty($tokenData) || !empty($tokenData['errcode'])) {
                $errMsg = $tokenData['errmsg'] ?? '未知错误';
                Log::error('[HdWxAuth] 获取access_token失败: ' . $errMsg . ' code=' . $code);
                return ['code' => 1, 'msg' => '授权已过期，请重新登录'];
            }

            $openid      = $tokenData['openid'] ?? '';
            $accessToken = $tokenData['access_token'] ?? '';

            if (empty($openid)) {
                Log::error('[HdWxAuth] openid为空: ' . json_encode($tokenData));
                return ['code' => 1, 'msg' => '授权失败，请重试'];
            }

            // 获取微信用户信息
            $nickname = '';
            $avatar   = '';
            if (!empty($accessToken)) {
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

            // 查询 openid 是否已绑定管理员账号
            $adminUser = Db::name('admin_user')
                ->where('openid', $openid)
                ->where('bid', '>', 0)
                ->where('status', 1)
                ->find();

            if ($adminUser) {
                // 已绑定 → 检查商家状态 → 生成 Token → 直接登录
                $business = Db::name('business')->where('id', $adminUser['bid'])->find();
                if (!$business || ($business['status'] ?? 0) == 2) {
                    return ['code' => 1, 'msg' => '商家账户已被禁用'];
                }

                // 生成登录 Token
                $token = $this->generateToken(
                    (int)$adminUser['id'],
                    (int)$adminUser['aid'],
                    (int)$adminUser['bid']
                );

                // 设置 Session
                session('hd_user_id', $adminUser['id']);
                session('hd_aid', $adminUser['aid']);
                session('hd_bid', $adminUser['bid']);
                session('hd_mdid', $adminUser['mdid'] ?? 0);

                Log::info("[HdWxAuth] 微信登录成功: openid={$openid}, user_id={$adminUser['id']}, bid={$adminUser['bid']}");

                return [
                    'code' => 0,
                    'msg'  => '登录成功',
                    'data' => [
                        'need_bind'   => false,
                        'token'       => $token,
                        'user_id'     => (int)$adminUser['id'],
                        'bid'         => (int)$adminUser['bid'],
                        'name'        => $business['name'] ?? '',
                    ],
                ];
            }

            // 未绑定 → 生成 bind_token → 引导到绑定页
            $bindToken = md5($openid . time() . uniqid());
            $cacheKey  = 'hd_bind_token:' . $bindToken;
            Cache::set($cacheKey, [
                'openid'   => $openid,
                'nickname' => $nickname,
                'avatar'   => $avatar,
            ], 900); // 15分钟有效

            Log::info("[HdWxAuth] 微信授权成功但未绑定: openid={$openid}, nickname={$nickname}");

            return [
                'code' => 0,
                'msg'  => '请完善公司信息',
                'data' => [
                    'need_bind'   => true,
                    'bind_token'  => $bindToken,
                    'wx_nickname' => $nickname,
                    'wx_avatar'   => $avatar,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('[HdWxAuth] 微信登录异常: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '登录失败，请重试'];
        }
    }

    /**
     * 绑定手机号完成注册（微信扫码后填写手机号 + 短信验证码完成注册）
     *
     * @param string $bindToken    临时绑定凭证
     * @param array  $data         绑定信息（phone + sms_code + 可选 name/contact_name）
     * @return array
     */
    public function wxBind(string $bindToken, array $data): array
    {
        Db::startTrans();
        try {
            // 验证 bind_token
            $cacheKey = 'hd_bind_token:' . $bindToken;
            $bindData = Cache::get($cacheKey);

            if (!$bindData || empty($bindData['openid'])) {
                return ['code' => 1, 'msg' => '授权已过期，请重新扫码'];
            }

            $openid   = $bindData['openid'];
            $nickname = $bindData['nickname'] ?? '';
            $avatar   = $bindData['avatar'] ?? '';

            $phone       = $data['phone'] ?? '';
            $smsCode     = $data['sms_code'] ?? '';
            $name        = $data['name'] ?? '';
            $password    = $data['password'] ?? '';
            $contactName = $data['contact_name'] ?? '';

            // 手机号校验
            if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
                return ['code' => 1, 'msg' => '手机号格式不正确'];
            }

            // 短信验证码校验
            if (empty($smsCode)) {
                return ['code' => 1, 'msg' => '请输入短信验证码'];
            }
            $authService = new HdAuthService();
            if (!$authService->verifyBindCode($phone, $smsCode)) {
                return ['code' => 1, 'msg' => '验证码错误或已过期'];
            }

            // 检查 openid 是否已被绑定
            $existsOpenid = Db::name('admin_user')
                ->where('openid', $openid)
                ->where('bid', '>', 0)
                ->find();
            if ($existsOpenid) {
                return ['code' => 1, 'msg' => '该微信已绑定商家，请直接登录'];
            }

            // 检查手机号是否已注册
            $existsPhone = Db::name('admin_user')
                ->where('un', $phone)
                ->where('bid', '>', 0)
                ->find();
            if ($existsPhone) {
                // 手机号已存在，直接绑定 openid 到现有账号
                Db::name('admin_user')->where('id', $existsPhone['id'])->update(['openid' => $openid]);
                Db::commit();

                // 清除缓存
                Cache::delete($cacheKey);
                $authService->clearBindCode($phone);

                $business = Db::name('business')->where('id', $existsPhone['bid'])->find();
                $token = $this->generateToken(
                    (int)$existsPhone['id'],
                    (int)$existsPhone['aid'],
                    (int)$existsPhone['bid']
                );

                Log::info("[HdWxAuth] 微信绑定已有账号: openid={$openid}, user_id={$existsPhone['id']}");

                return [
                    'code' => 0,
                    'msg'  => '绑定成功',
                    'data' => [
                        'token'   => $token,
                        'user_id' => (int)$existsPhone['id'],
                        'bid'     => (int)$existsPhone['bid'],
                        'name'    => $business['name'] ?? '',
                    ],
                ];
            }

            // 获取平台 aid
            $admin = Db::name('admin')->order('id asc')->find();
            $aid = $admin ? (int)$admin['id'] : 1;

            // 商家名称：优先用用户填写的，其次用微信昵称，最后用手机号
            if (empty($name)) {
                $name = !empty($nickname) ? $nickname . '的公司' : $phone . '的公司';
            }
            if (empty($contactName)) {
                $contactName = !empty($nickname) ? $nickname : $phone;
            }

            // 创建商家记录
            $bid = Db::name('business')->insertGetId([
                'aid'        => $aid,
                'name'       => $name,
                'tel'        => $phone,
                'linkman'    => $contactName,
                'status'     => 1,
                'createtime' => time(),
            ]);

            // 生成随机密码（用户可以之后在设置中修改）
            $autoPassword = !empty($password) ? $password : substr(md5(uniqid()), 0, 8);

            // 创建管理员账号（含 openid）
            $userId = Db::name('admin_user')->insertGetId([
                'aid'        => $aid,
                'bid'        => $bid,
                'mdid'       => 0,
                'un'         => $phone,
                'pwd'        => md5($autoPassword),
                'openid'     => $openid,
                'isadmin'    => 1,
                'status'     => 1,
                'createtime' => time(),
            ]);

            // 赠送试用套餐
            $trialPlan = HdPlan::where('price', 0)
                ->where('status', HdPlan::STATUS_ACTIVE)
                ->order('sort desc')
                ->find();

            $planId       = $trialPlan ? (int)$trialPlan->id : 0;
            $durationDays = $trialPlan ? (int)$trialPlan->duration_days : 7;

            // 创建商家扩展配置
            HdBusinessConfig::create([
                'aid'              => $aid,
                'bid'              => $bid,
                'plan_id'          => $planId,
                'plan_expire_time' => time() + ($durationDays * 86400),
                'trial_used'       => 1,
                'createtime'       => time(),
            ]);

            Db::commit();

            // 清除缓存
            Cache::delete($cacheKey);
            $authService->clearBindCode($phone);

            // 生成登录 Token
            $token = $this->generateToken((int)$userId, (int)$aid, (int)$bid);

            // 设置 Session
            session('hd_user_id', $userId);
            session('hd_aid', $aid);
            session('hd_bid', $bid);
            session('hd_mdid', 0);

            Log::info("[HdWxAuth] 微信绑定成功: openid={$openid}, user_id={$userId}, bid={$bid}, name={$name}");

            return [
                'code' => 0,
                'msg'  => '注册成功',
                'data' => [
                    'token'   => $token,
                    'user_id' => $userId,
                    'bid'     => $bid,
                    'name'    => $name,
                ],
            ];

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('[HdWxAuth] 微信绑定失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 生成登录 Token
     */
    private function generateToken(int $userId, int $aid, int $bid): string
    {
        $token = md5($userId . $aid . $bid . time() . uniqid());
        $cacheKey = 'hd_token:' . $token;
        Cache::set($cacheKey, [
            'user_id' => $userId,
            'aid'     => $aid,
            'bid'     => $bid,
        ], 7200);
        return $token;
    }

    // ============================================================
    // 公众号带参数二维码扫码登录
    // ============================================================

    /**
     * 生成临时带参数二维码用于扫码登录
     *
     * @return array
     */
    public function createLoginQrCode(): array
    {
        try {
            // 获取平台公众号配置
            $admin = Db::name('admin')->order('id asc')->find();
            $aid = $admin ? (int)$admin['id'] : 1;

            $accessToken = \app\common\Wechat::access_token($aid, 'mp');
            if (empty($accessToken)) {
                return ['code' => 1, 'msg' => '获取access_token失败，请检查公众号配置'];
            }

            // 生成唯一 scene_id
            $sceneId = 'hdlogin_' . md5(uniqid((string)mt_rand(), true));

            // 调用微信API创建临时二维码（5分钟有效）
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
            $postData = json_encode([
                'expire_seconds' => 300,
                'action_name'    => 'QR_STR_SCENE',
                'action_info'    => ['scene' => ['scene_str' => $sceneId]],
            ], JSON_UNESCAPED_UNICODE);

            $resp = $this->httpPost($url, $postData);
            $result = json_decode($resp, true);

            if (empty($result) || empty($result['ticket'])) {
                $errMsg = $result['errmsg'] ?? '未知错误';
                Log::error('[HdWxAuth] 创建二维码失败: ' . $errMsg);
                return ['code' => 1, 'msg' => '生成二维码失败'];
            }

            // 二维码图片URL
            $qrUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($result['ticket']);

            // 缓存 pending 状态（5分钟有效）
            Cache::set($sceneId, [
                'status'      => 'pending',
                'create_time' => time(),
            ], 300);

            Log::info('[HdWxAuth] 生成登录二维码: scene=' . $sceneId);

            return [
                'code' => 0,
                'msg'  => 'success',
                'data' => [
                    'scene_id' => $sceneId,
                    'qr_url'   => $qrUrl,
                    'expire'   => 300,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('[HdWxAuth] 创建登录二维码异常: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '生成二维码失败'];
        }
    }

    /**
     * 检查扫码状态
     *
     * @param string $sceneId  场景值
     * @return array
     */
    public function checkQrCodeStatus(string $sceneId): array
    {
        try {
            if (empty($sceneId) || strpos($sceneId, 'hdlogin_') !== 0) {
                return ['code' => 1, 'msg' => '无效的场景值'];
            }

            $cacheData = Cache::get($sceneId);
            if (empty($cacheData)) {
                return [
                    'code' => 0,
                    'msg'  => 'success',
                    'data' => ['status' => 'expired'],
                ];
            }

            $status = $cacheData['status'] ?? 'pending';

            if ($status === 'confirmed') {
                // 已扫码且已绑定 → 返回 token
                Cache::delete($sceneId);
                return [
                    'code' => 0,
                    'msg'  => '登录成功',
                    'data' => [
                        'status'  => 'confirmed',
                        'token'   => $cacheData['token'] ?? '',
                        'user_id' => $cacheData['user_id'] ?? 0,
                        'bid'     => $cacheData['bid'] ?? 0,
                        'name'    => $cacheData['name'] ?? '',
                    ],
                ];
            }

            if ($status === 'need_bind') {
                // 已扫码但未绑定 → 返回 bind_token
                Cache::delete($sceneId);
                return [
                    'code' => 0,
                    'msg'  => '请完善公司信息',
                    'data' => [
                        'status'      => 'need_bind',
                        'bind_token'  => $cacheData['bind_token'] ?? '',
                        'wx_nickname' => $cacheData['wx_nickname'] ?? '',
                        'wx_avatar'   => $cacheData['wx_avatar'] ?? '',
                    ],
                ];
            }

            // 仍在等待扫码
            return [
                'code' => 0,
                'msg'  => 'success',
                'data' => ['status' => 'pending'],
            ];

        } catch (\Exception $e) {
            Log::error('[HdWxAuth] 检查扫码状态异常: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '检查状态失败'];
        }
    }

    /**
     * 处理微信扫码事件回调（由 ApiWechat 调用）
     * 用户扫描 hdlogin_ 前缀的二维码时触发
     *
     * @param string $sceneId  场景值（hdlogin_xxx）
     * @param string $openid   扫码用户的openid
     * @return void
     */
    public static function handleScanLogin(string $sceneId, string $openid): void
    {
        try {
            Log::info("[HdWxAuth] handleScanLogin called: sceneId={$sceneId}, openid={$openid}");

            $cacheData = Cache::get($sceneId);

            if (empty($cacheData) || ($cacheData['status'] ?? '') !== 'pending') {
                return;
            }

            // 查询 openid 是否已绑定管理员账号
            $adminUser = Db::name('admin_user')
                ->where('openid', $openid)
                ->where('bid', '>', 0)
                ->where('status', 1)
                ->find();

            if ($adminUser) {
                // 已绑定 → 检查商家状态
                $business = Db::name('business')->where('id', $adminUser['bid'])->find();
                if (!$business || ($business['status'] ?? 0) == 2) {
                    return; // 商家被禁用，忽略
                }

                // 生成登录 Token
                $token = md5($adminUser['id'] . $adminUser['aid'] . $adminUser['bid'] . time() . uniqid());
                Cache::set('hd_token:' . $token, [
                    'user_id' => (int)$adminUser['id'],
                    'aid'     => (int)$adminUser['aid'],
                    'bid'     => (int)$adminUser['bid'],
                ], 7200);

                // 更新缓存为已确认
                Cache::set($sceneId, [
                    'status'      => 'confirmed',
                    'token'       => $token,
                    'user_id'     => (int)$adminUser['id'],
                    'bid'         => (int)$adminUser['bid'],
                    'name'        => $business['name'] ?? '',
                    'create_time' => $cacheData['create_time'],
                ], 300);

                Log::info("[HdWxAuth] 扫码登录成功: openid={$openid}, user_id={$adminUser['id']}");
            } else {
                // 未绑定 → 生成 bind_token，引导注册
                $bindToken = md5($openid . time() . uniqid());

                // 尝试获取微信用户信息
                $admin = Db::name('admin')->order('id asc')->find();
                $aid = $admin ? (int)$admin['id'] : 1;
                $nickname = '';
                $avatar = '';

                $accessToken = \app\common\Wechat::access_token($aid, 'mp');
                if ($accessToken) {
                    $infoUrl = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN';
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $infoUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    $infoResp = curl_exec($ch);
                    curl_close($ch);
                    $info = json_decode($infoResp, true);
                    if ($info && empty($info['errcode'])) {
                        $nickname = $info['nickname'] ?? '';
                        $avatar = $info['headimgurl'] ?? '';
                    }
                }

                Cache::set('hd_bind_token:' . $bindToken, [
                    'openid'   => $openid,
                    'nickname' => $nickname,
                    'avatar'   => $avatar,
                ], 900);

                // 更新扫码缓存为需要绑定
                Cache::set($sceneId, [
                    'status'      => 'need_bind',
                    'bind_token'  => $bindToken,
                    'wx_nickname' => $nickname,
                    'wx_avatar'   => $avatar,
                    'create_time' => $cacheData['create_time'],
                ], 300);

                Log::info("[HdWxAuth] 扫码用户未绑定: openid={$openid}, bind_token={$bindToken}");
            }
        } catch (\Exception $e) {
            Log::error('[HdWxAuth] 处理扫码登录异常: ' . $e->getMessage());
        }
    }

    /**
     * HTTP GET 请求
     */
    private function httpGet(string $url): string
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
            Log::error('[HdWxAuth] CURL错误: ' . curl_error($ch) . ' URL: ' . $url);
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        return $result ?: '';
    }

    /**
     * HTTP POST 请求
     */
    private function httpPost(string $url, string $data): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            Log::error('[HdWxAuth] CURL POST错误: ' . curl_error($ch) . ' URL: ' . $url);
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        return $result ?: '';
    }
}

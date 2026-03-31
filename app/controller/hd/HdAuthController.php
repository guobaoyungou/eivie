<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdAuthService;
use app\service\hd\HdWxAuthService;

/**
 * 大屏互动 - 认证控制器
 * 商家注册/登录/profile + 微信授权登录
 */
class HdAuthController extends HdBaseController
{
    protected $authService;
    protected $wxAuthService;

    protected function initialize()
    {
        $this->authService = new HdAuthService();
        $this->wxAuthService = new HdWxAuthService();
    }

    /**
     * 商家注册
     * POST /api/hd/auth/register
     */
    public function register()
    {
        $data = [
            'name'         => input('post.name', ''),
            'phone'        => input('post.phone', ''),
            'password'     => input('post.password', ''),
            'contact_name' => input('post.contact_name', ''),
        ];

        // 基本验证
        if (empty($data['name'])) return $this->error('请填写商家名称');
        if (empty($data['phone'])) return $this->error('请填写手机号');
        if (!preg_match('/^1[3-9]\d{9}$/', $data['phone'])) return $this->error('手机号格式不正确');
        if (empty($data['password'])) return $this->error('请设置密码');
        if (strlen($data['password']) < 6) return $this->error('密码至少6位');

        $result = $this->authService->register($data);
        return json($result);
    }

    /**
     * 商家登录
     * POST /api/hd/auth/login
     */
    public function login()
    {
        $username = input('post.username', '');
        $password = input('post.password', '');

        if (empty($username)) return $this->error('请输入用户名');
        if (empty($password)) return $this->error('请输入密码');

        $result = $this->authService->login($username, $password);
        return json($result);
    }

    /**
     * 退出登录
     * POST /api/hd/auth/logout
     */
    public function logout()
    {
        $result = $this->authService->logout();
        return json($result);
    }

    /**
     * 获取当前账户信息
     * GET /api/hd/auth/profile
     */
    public function profile()
    {
        $result = $this->authService->getProfile($this->getUserId(), $this->getBid());
        return json($result);
    }

    /**
     * 更新账户信息
     * PUT /api/hd/auth/profile
     */
    public function updateProfile()
    {
        $data = input('post.');
        $result = $this->authService->updateProfile($this->getUserId(), $this->getBid(), $data);
        return json($result);
    }

    // ============================================================
    // 微信授权登录相关 API
    // ============================================================

    /**
     * 获取微信 OAuth 授权跳转 URL
     * GET /api/hd/auth/wx-oauth-url
     */
    public function wxOauthUrl()
    {
        $redirectUri = input('get.redirect_uri', '');
        $result = $this->wxAuthService->getOAuthUrl($redirectUri);
        return json($result);
    }

    /**
     * 微信授权登录（code 换 openid → 检查绑定）
     * POST /api/hd/auth/wx-login
     */
    public function wxLogin()
    {
        $code  = input('post.code', '');
        $state = input('post.state', '');

        if (empty($code)) return $this->error('缺少授权code参数');

        $result = $this->wxAuthService->wxLogin($code, $state);
        return json($result);
    }

    /**
     * 绑定手机号完成注册（微信扫码后填写手机号 + 短信验证码）
     * POST /api/hd/auth/wx-bind
     */
    public function wxBind()
    {
        $bindToken = input('post.bind_token', '');
        if (empty($bindToken)) return $this->error('缺少绑定凭证');

        $data = [
            'phone'        => input('post.phone', ''),
            'sms_code'     => input('post.sms_code', ''),
            'name'         => input('post.name', ''),
            'password'     => input('post.password', ''),
            'contact_name' => input('post.contact_name', ''),
        ];

        $result = $this->wxAuthService->wxBind($bindToken, $data);
        return json($result);
    }

    // ============================================================
    // 手机绑定短信验证码
    // ============================================================

    /**
     * 发送绑定手机验证码（新用户扫码后绑定手机时使用）
     * POST /api/hd/auth/send-bind-code
     */
    public function sendBindCode()
    {
        $phone = input('post.phone', '');
        if (empty($phone)) return $this->error('请输入手机号');
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) return $this->error('手机号格式不正确');

        $result = $this->authService->sendBindCode($phone);
        return json($result);
    }

    // ============================================================
    // 公众号二维码扫码登录 API
    // ============================================================

    /**
     * 生成登录二维码
     * GET /api/hd/auth/qr-code
     */
    public function qrCode()
    {
        $result = $this->wxAuthService->createLoginQrCode();
        return json($result);
    }

    /**
     * 检查扫码状态（前端轮询）
     * GET /api/hd/auth/qr-check?scene_id=xxx
     */
    public function qrCheck()
    {
        $sceneId = input('get.scene_id', '');
        if (empty($sceneId)) return $this->error('缺少scene_id参数');

        $result = $this->wxAuthService->checkQrCodeStatus($sceneId);
        return json($result);
    }

    // ============================================================
    // 密码重置相关 API
    // ============================================================

    /**
     * 发送密码重置验证码
     * POST /api/hd/password/send-code
     */
    public function sendResetCode()
    {
        $phone = input('post.phone', '');
        if (empty($phone)) return $this->error('请输入手机号');
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) return $this->error('手机号格式不正确');

        $result = $this->authService->sendResetCode($phone);
        return json($result);
    }

    /**
     * 重置密码
     * POST /api/hd/password/reset
     */
    public function resetPassword()
    {
        $phone = input('post.phone', '');
        $code = input('post.code', '');
        $password = input('post.password', '');

        if (empty($phone)) return $this->error('请输入手机号');
        if (empty($code)) return $this->error('请输入验证码');
        if (empty($password) || strlen($password) < 6) return $this->error('密码至少6位');

        $result = $this->authService->resetPassword($phone, $code, $password);
        return json($result);
    }
}

<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdPlan;

/**
 * 大屏互动 - 认证服务
 */
class HdAuthService
{
    /**
     * 商家注册
     */
    public function register(array $data): array
    {
        Db::startTrans();
        try {
            $name = $data['name'] ?? '';
            $phone = $data['phone'] ?? '';
            $password = $data['password'] ?? '';
            $contactName = $data['contact_name'] ?? $name;

            if (empty($name) || empty($phone) || empty($password)) {
                throw new \Exception('商家名称、手机号和密码不能为空');
            }

            // 检查手机号是否已注册（手机号即为用户名，存储在 un 字段）
            $exists = Db::name('admin_user')->where('un', $phone)->where('bid', '>', 0)->find();
            if ($exists) {
                throw new \Exception('该手机号已注册');
            }

            // 获取平台固定 aid（使用第一个 admin 的 id）
            $admin = Db::name('admin')->order('id asc')->find();
            $aid = $admin ? (int)$admin['id'] : 1;

            // 创建商家记录
            $bid = Db::name('business')->insertGetId([
                'aid'        => $aid,
                'name'       => $name,
                'tel'        => $phone,
                'linkman'    => $contactName,
                'status'     => 1,
                'createtime' => time(),
            ]);

            // 创建管理员账号
            $userId = Db::name('admin_user')->insertGetId([
                'aid'        => $aid,
                'bid'        => $bid,
                'mdid'       => 0,
                'un'         => $phone,
                'pwd'        => md5($password),
                'isadmin'    => 1,
                'status'     => 1,
                'createtime' => time(),
            ]);

            // 赠送试用套餐
            $trialPlan = HdPlan::where('price', 0)
                ->where('status', HdPlan::STATUS_ACTIVE)
                ->order('sort desc')
                ->find();

            $planId = $trialPlan ? (int)$trialPlan->id : 0;
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

            // 生成登录Token
            $token = $this->generateToken($userId, $aid, $bid);

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
            Log::error('大屏互动商家注册失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 商家登录
     */
    public function login(string $username, string $password): array
    {
        $user = Db::name('admin_user')
            ->where('un', $username)
            ->where('bid', '>', 0)
            ->where('status', 1)
            ->find();

        if (!$user) {
            return ['code' => 1, 'msg' => '账号不存在'];
        }

        if ($user['pwd'] !== md5($password)) {
            return ['code' => 1, 'msg' => '密码错误'];
        }

        // 检查商家状态
        $business = Db::name('business')->where('id', $user['bid'])->find();
        if (!$business || ($business['status'] ?? 0) == 2) {
            return ['code' => 1, 'msg' => '商家账户已被禁用'];
        }

        // 生成 Token
        $token = $this->generateToken((int)$user['id'], (int)$user['aid'], (int)$user['bid']);

        // 设置 Session
        session('hd_user_id', $user['id']);
        session('hd_aid', $user['aid']);
        session('hd_bid', $user['bid']);
        session('hd_mdid', $user['mdid'] ?? 0);

        return [
            'code' => 0,
            'msg'  => '登录成功',
            'data' => [
                'token'    => $token,
                'user_id'  => $user['id'],
                'bid'      => $user['bid'],
                'username' => $user['un'],
                'name'     => $business['name'] ?? '',
            ],
        ];
    }

    /**
     * 退出登录
     */
    public function logout(): array
    {
        session('hd_user_id', null);
        session('hd_aid', null);
        session('hd_bid', null);
        session('hd_mdid', null);

        return ['code' => 0, 'msg' => '已退出'];
    }

    /**
     * 获取当前账户信息
     */
    public function getProfile(int $userId, int $bid): array
    {
        $user = Db::name('admin_user')->where('id', $userId)->find();
        $business = Db::name('business')->where('id', $bid)->find();
        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        $plan = null;
        if ($bizConfig && $bizConfig->plan_id) {
            $plan = HdPlan::find($bizConfig->plan_id);
        }

        // 构建套餐信息（包含前端所需的使用量统计字段）
        $planData = null;
        if ($plan) {
            $aid = (int)($user['aid'] ?? 0);

            // 统计当前门店数和活动数
            $storeCount = Db::name('mendian')
                ->where('bid', $bid)
                ->where('aid', $aid)
                ->count();
            $activityCount = Db::name('hd_activity')
                ->where('bid', $bid)
                ->where('aid', $aid)
                ->count();

            // 格式化到期时间为可读日期
            $expireTime = (int)$bizConfig->plan_expire_time;
            $expireDate = $expireTime > 0 ? date('Y-m-d', $expireTime) : '--';

            $planData = [
                'name'             => $plan->name,
                'code'             => $plan->code ?? '',
                'max_stores'       => (int)$plan->max_stores,
                'max_activities'   => (int)$plan->max_activities,
                'max_participants' => (int)$plan->max_participants,
                'expire_time'      => $expireTime,
                'expire_date'      => $expireDate,
                'is_valid'         => $bizConfig->isPlanValid(),
                'store_count'      => $storeCount,
                'activity_count'   => $activityCount,
            ];
        }

        return [
            'code' => 0,
            'data' => [
                'user_id'       => $user['id'],
                'username'      => $user['un'],
                'tel'           => $user['un'],
                'business_name' => $business['name'] ?? '',
                'business_logo' => $business['logo'] ?? '',
                'contact'       => $business['linkman'] ?? '',
                'plan'          => $planData,
            ],
        ];
    }

    /**
     * 更新账户信息
     */
    public function updateProfile(int $userId, int $bid, array $data): array
    {
        Db::startTrans();
        try {
            // 更新管理员信息
            if (isset($data['tel'])) {
                Db::name('admin_user')->where('id', $userId)->update(['un' => $data['tel']]);
            }
            if (isset($data['password']) && !empty($data['password'])) {
                Db::name('admin_user')->where('id', $userId)->update(['pwd' => md5($data['password'])]);
            }

            // 更新商家信息
            $bizData = [];
            if (isset($data['name'])) $bizData['name'] = $data['name'];
            if (isset($data['logo'])) $bizData['logo'] = $data['logo'];
            if (isset($data['contact_name'])) $bizData['linkman'] = $data['contact_name'];
            if (isset($data['address'])) $bizData['address'] = $data['address'];

            if ($bizData) {
                Db::name('business')->where('id', $bid)->update($bizData);
            }

            // 更新公众号配置
            if (isset($data['wxfw_appid'])) {
                HdBusinessConfig::where('bid', $bid)->update([
                    'wxfw_appid'     => $data['wxfw_appid'],
                    'wxfw_appsecret' => $data['wxfw_appsecret'] ?? '',
                    'updatetime'     => time(),
                ]);
            }

            Db::commit();
            return ['code' => 0, 'msg' => '更新成功'];
        } catch (\Exception $e) {
            Db::rollback();
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

    /**
     * 发送密码重置验证码
     */
    public function sendResetCode(string $phone): array
    {
        // 检查手机号是否存在
        $user = Db::name('admin_user')
            ->where('un', $phone)
            ->where('bid', '>', 0)
            ->where('status', 1)
            ->find();

        if (!$user) {
            return ['code' => 1, 'msg' => '该手机号未注册'];
        }

        return $this->_sendSmsCode($phone, 'hd_reset_code', (int)$user['aid']);
    }

    /**
     * 发送注册/绑定用短信验证码（新用户注册时使用，不检查手机是否已注册）
     */
    public function sendBindCode(string $phone): array
    {
        // 获取平台 aid
        $admin = Db::name('admin')->order('id asc')->find();
        $aid = $admin ? (int)$admin['id'] : 1;

        return $this->_sendSmsCode($phone, 'hd_bind_code', $aid);
    }

    /**
     * 验证绑定短信验证码
     */
    public function verifyBindCode(string $phone, string $code): bool
    {
        $cacheKey = 'hd_bind_code:' . $phone;
        $cachedCode = Cache::get($cacheKey);
        return !empty($cachedCode) && $cachedCode === $code;
    }

    /**
     * 清除绑定短信验证码
     */
    public function clearBindCode(string $phone): void
    {
        Cache::delete('hd_bind_code:' . $phone);
    }

    /**
     * 通用短信验证码发送
     */
    private function _sendSmsCode(string $phone, string $cachePrefix, int $aid): array
    {
        // 防频率限制（60秒内不能重复发送）
        $rateLimitKey = $cachePrefix . '_rate:' . $phone;
        if (Cache::get($rateLimitKey)) {
            return ['code' => 1, 'msg' => '发送太频繁，请60秒后再试'];
        }

        // 生成 6 位验证码
        $code = (string)rand(100000, 999999);
        $cacheKey = $cachePrefix . ':' . $phone;

        // 调用平台短信服务发送验证码（使用平台统一的 tmpl_smscode 模板）
        try {
            if (class_exists('\\app\\common\\Sms')) {
                $rs = \app\common\Sms::send($aid, $phone, 'tmpl_smscode', ['code' => $code]);
                if (isset($rs['status']) && $rs['status'] != 1) {
                    Log::error("[HdAuth] 短信发送失败: " . ($rs['msg'] ?? '未知错误'));
                    return ['code' => 1, 'msg' => $rs['msg'] ?? '短信发送失败，请联系管理员'];
                }
                Log::info("[HdAuth] 验证码已发送至 {$phone}");
            } else {
                Log::error("[HdAuth] 短信服务类不存在");
                return ['code' => 1, 'msg' => '短信服务未配置，请联系管理员'];
            }
        } catch (\Throwable $e) {
            Log::error("[HdAuth] 发送验证码异常: " . $e->getMessage());
            return ['code' => 1, 'msg' => '短信发送失败，请稍后重试'];
        }

        // 短信发送成功后再缓存验证码和防频
        Cache::set($cacheKey, $code, 300); // 5分钟有效
        Cache::set($rateLimitKey, 1, 60); // 60秒防频

        return ['code' => 0, 'msg' => '验证码已发送'];
    }

    /**
     * 重置密码
     */
    public function resetPassword(string $phone, string $code, string $newPassword): array
    {
        $cacheKey = 'hd_reset_code:' . $phone;
        $cachedCode = Cache::get($cacheKey);

        if (!$cachedCode || $cachedCode !== $code) {
            return ['code' => 1, 'msg' => '验证码错误或已过期'];
        }

        $user = Db::name('admin_user')
            ->where('un', $phone)
            ->where('bid', '>', 0)
            ->find();

        if (!$user) {
            return ['code' => 1, 'msg' => '账号不存在'];
        }

        Db::name('admin_user')->where('id', $user['id'])->update([
            'pwd' => md5($newPassword),
        ]);

        // 清除验证码
        Cache::delete($cacheKey);

        return ['code' => 0, 'msg' => '密码重置成功'];
    }
}

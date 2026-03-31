<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;
use think\facade\Cache;

/**
 * 大屏互动 - 商家认证中间件
 * 验证商家登录态（Session方式）
 */
class HdAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 方式1: 从 Session 获取登录信息
        $hdUserId = session('hd_user_id');
        $hdBid = session('hd_bid');
        $hdAid = session('hd_aid');

        // 方式2: 从 Header Token 获取
        if (!$hdUserId) {
            $token = $request->header('Hd-Token', '');
            if ($token) {
                $cacheKey = 'hd_token:' . $token;
                $tokenData = Cache::get($cacheKey);
                if ($tokenData) {
                    $hdUserId = $tokenData['user_id'] ?? 0;
                    $hdBid = $tokenData['bid'] ?? 0;
                    $hdAid = $tokenData['aid'] ?? 0;
                    // 续期
                    Cache::set($cacheKey, $tokenData, 7200);
                }
            }
        }

        if (!$hdUserId || !$hdBid) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        // 检查商家状态
        $business = Db::name('business')->where('id', $hdBid)->find();
        if (!$business || ($business['status'] ?? 0) == 2) {
            return json(['code' => 403, 'msg' => '商家账户已被禁用']);
        }

        // 注入用户信息到请求
        $request->hd_user_id = (int)$hdUserId;
        $request->hd_aid = (int)$hdAid;
        $request->hd_bid = (int)$hdBid;
        $request->hd_mdid = (int)(session('hd_mdid') ?: 0);
        $request->hd_business = $business;

        return $next($request);
    }
}

<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 用户Token认证中间件
 */
class UserTokenAuth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('User-Token', '');
        
        if (empty($token)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }
        
        // 从缓存中获取用户信息
        $cacheKey = 'user_token:' . $token;
        $userInfo = Cache::get($cacheKey);
        
        if (!$userInfo) {
            return json(['code' => 401, 'msg' => '登录已过期，请重新登录']);
        }
        
        // 将用户信息注入到请求中
        $request->userInfo = $userInfo;
        $request->uid = $userInfo['uid'];
        
        // 刷新Token有效期
        Cache::set($cacheKey, $userInfo, 7200);
        
        return $next($request);
    }
}

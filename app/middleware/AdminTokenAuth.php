<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 管理员Token认证中间件
 */
class AdminTokenAuth
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
        $token = $request->header('Admin-Token', '');
        
        if (empty($token)) {
            return json(['code' => 401, 'msg' => '请先登录管理后台']);
        }
        
        // 从缓存中获取管理员信息
        $cacheKey = 'admin_token:' . $token;
        $adminInfo = Cache::get($cacheKey);
        
        if (!$adminInfo) {
            return json(['code' => 401, 'msg' => '登录已过期，请重新登录']);
        }
        
        // 将管理员信息注入到请求中
        $request->adminInfo = $adminInfo;
        $request->bid = $adminInfo['bid'] ?? 0;
        
        // 刷新Token有效期
        Cache::set($cacheKey, $adminInfo, 7200);
        
        return $next($request);
    }
}

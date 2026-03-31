<?php
namespace app\sysadmin\middleware;

use think\facade\Cache;
use think\response\Json;

class RequestRateLimiter
{
    public function handle($request, \Closure $next)
    {
        $licenseCode = $request->param('license_code');
        if (!$licenseCode) {
            return $next($request);
        }
        
        $key = 'sysadmin_rate_limit_' . md5($licenseCode);
        $count = Cache::get($key, 0);
        
        if ($count >= 10) {
            return Json::create(['status' => 0, 'msg' => '请求过于频繁，请稍后再试']);
        }
        
        Cache::set($key, $count + 1, 60);
        
        return $next($request);
    }
}
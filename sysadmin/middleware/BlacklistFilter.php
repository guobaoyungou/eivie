<?php
namespace app\sysadmin\middleware;

use app\sysadmin\model\SysadminBlacklist;
use think\response\Json;

class BlacklistFilter
{
    public function handle($request, \Closure $next)
    {
        $domain = $request->param('domain');
        $serverIp = $request->param('server_ip') ?: $request->ip();
        
        $now = time();
        
        $blacklist = SysadminBlacklist::where(function($query) use ($domain, $serverIp, $now) {
            $query->where(function($q) use ($domain, $now) {
                $q->where('domain', $domain)
                  ->where('type', 1)
                  ->where(function($q2) use ($now) {
                      $q2->where('expire_time', 0)->whereOr('expire_time', '>', $now);
                  });
            })->whereOr(function($q) use ($serverIp, $now) {
                $q->where('ip', $serverIp)
                  ->where('type', 2)
                  ->where(function($q2) use ($now) {
                      $q2->where('expire_time', 0)->whereOr('expire_time', '>', $now);
                  });
            });
        })->find();
        
        if ($blacklist) {
            return Json::create(['status' => -1, 'msg' => '已被禁止']);
        }
        
        return $next($request);
    }
}
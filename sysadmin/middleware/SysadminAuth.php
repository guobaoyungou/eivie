<?php
namespace app\sysadmin\middleware;

use think\facade\Session;
use think\response\Json;

class SysadminAuth
{
    public function handle($request, \Closure $next)
    {
        $adminId = Session::get('sysadmin_admin_id');
        
        if (!$adminId) {
            if ($request->isAjax()) {
                return Json::create(['code' => 401, 'msg' => '请先登录']);
            } else {
                return redirect('/sysadmin/login');
            }
        }
        
        return $next($request);
    }
}
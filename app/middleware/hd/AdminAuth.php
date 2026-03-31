<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;

/**
 * 平台超级管理员认证中间件
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 从 Session 获取管理员信息
        $adminId = session('hd_admin_id');
        $isSuper = session('hd_is_super');

        if (!$adminId || !$isSuper) {
            return json(['code' => 401, 'msg' => '需要超级管理员权限']);
        }

        // 验证管理员身份
        $admin = Db::name('admin_user')
            ->where('id', $adminId)
            ->where('isadmin', 1)
            ->where('bid', 0) // bid=0 表示平台级
            ->find();

        if (!$admin) {
            return json(['code' => 403, 'msg' => '无超级管理员权限']);
        }

        $request->hd_admin_id = (int)$adminId;
        $request->hd_admin = $admin;

        return $next($request);
    }
}

<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;
use app\model\hd\HdActivity;

/**
 * 租户识别中间件
 * 从 Session / URL access_code 解析 aid + bid，注入租户上下文
 */
class TenantResolver
{
    public function handle(Request $request, Closure $next): Response
    {
        $aid = 0;
        $bid = 0;
        $mdid = 0;
        $activityId = 0;

        // 优先从 Session 获取（管理后台场景）
        $sessionBid = session('hd_bid');
        $sessionAid = session('hd_aid');

        if ($sessionBid && $sessionAid) {
            $aid = (int)$sessionAid;
            $bid = (int)$sessionBid;
            $mdid = (int)session('hd_mdid');
        }

        // 从 URL 参数中获取 access_code（大屏/手机端场景）
        $accessCode = $request->param('access_code', '');
        if ($accessCode) {
            $activity = HdActivity::where('access_code', $accessCode)->find();
            if ($activity) {
                $aid = (int)$activity->aid;
                $bid = (int)$activity->bid;
                $mdid = (int)$activity->mdid;
                $activityId = (int)$activity->id;
                $request->hd_activity = $activity;
            }
        }

        // 注入租户上下文到请求
        $request->hd_aid = $aid;
        $request->hd_bid = $bid;
        $request->hd_mdid = $mdid;
        $request->hd_activity_id = $activityId;

        return $next($request);
    }
}

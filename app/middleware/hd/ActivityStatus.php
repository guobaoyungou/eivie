<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;
use app\model\hd\HdActivity;

/**
 * 活动状态中间件
 * 检查活动是否在有效时间范围内
 */
class ActivityStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $activity = $request->hd_activity ?? null;

        if (!$activity) {
            $accessCode = $request->param('access_code', '');
            if ($accessCode) {
                $activity = HdActivity::where('access_code', $accessCode)->find();
            }
        }

        if (!$activity) {
            return json(['code' => 404, 'msg' => '活动不存在']);
        }

        // 检查活动时间
        $now = time();
        if ($activity->ended_at && $now > $activity->ended_at) {
            // 自动更新状态
            if ($activity->status != HdActivity::STATUS_ENDED) {
                $activity->status = HdActivity::STATUS_ENDED;
                $activity->save();
            }
            return json(['code' => 403, 'msg' => '活动已结束']);
        }

        $request->hd_activity = $activity;
        $request->hd_activity_id = (int)$activity->id;
        $request->hd_aid = (int)$activity->aid;
        $request->hd_bid = (int)$activity->bid;

        return $next($request);
    }
}

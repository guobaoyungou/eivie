<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdPlan;
use app\model\hd\HdActivity;

/**
 * 套餐权限中间件
 * 校验当前租户的套餐是否包含所请求功能的权限
 */
class PlanPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $bid = $request->hd_bid ?? 0;
        if (!$bid) {
            return json(['code' => 403, 'msg' => '无法识别商家身份']);
        }

        // 获取商家的大屏互动配置
        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        if (!$bizConfig) {
            return json(['code' => 403, 'msg' => '请先激活大屏互动服务']);
        }

        // 检查套餐是否过期
        if (!$bizConfig->isPlanValid()) {
            return json(['code' => 403, 'msg' => '套餐已过期，请续费']);
        }

        // 获取套餐信息
        $plan = HdPlan::find($bizConfig->plan_id);
        if (!$plan) {
            return json(['code' => 403, 'msg' => '套餐信息不存在']);
        }

        // 注入套餐信息
        $request->hd_plan = $plan;
        $request->hd_biz_config = $bizConfig;

        return $next($request);
    }
}

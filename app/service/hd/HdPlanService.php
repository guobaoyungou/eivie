<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;

/**
 * 大屏互动 - 套餐查询服务
 */
class HdPlanService
{
    /**
     * 获取所有启用的套餐列表（按 sort 降序排列，试用版在前）
     */
    public function getActivePlans(): array
    {
        try {
            $plans = Db::name('hd_plan')
                ->where('status', 1)
                ->order('sort desc')
                ->field('id, name, code, price, period, duration_days, max_stores, max_activities, max_participants, features, is_recommended')
                ->select()
                ->toArray();

            // 格式化套餐数据
            $list = [];
            foreach ($plans as $plan) {
                $list[] = [
                    'id'               => (int)$plan['id'],
                    'name'             => $plan['name'],
                    'code'             => $plan['code'],
                    'price'            => (int)($plan['price'] * 100), // 转为分
                    'price_display'    => $plan['code'] === 'custom' ? '联系客服' : ($plan['price'] > 0 ? '¥' . intval($plan['price']) : '免费'),
                    'period'           => $plan['period'],
                    'max_stores'       => (int)$plan['max_stores'],
                    'max_activities'   => (int)$plan['max_activities'],
                    'max_participants' => (int)$plan['max_participants'],
                    'features'         => $plan['features'] ? explode(',', $plan['features']) : [],
                    'is_recommended'   => (int)$plan['is_recommended'] === 1,
                ];
            }

            return ['code' => 0, 'data' => $list];
        } catch (\Exception $e) {
            Log::error('获取套餐列表失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '获取套餐列表失败'];
        }
    }
}

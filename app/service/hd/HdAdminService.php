<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdPlan;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdActivity;

/**
 * 大屏互动 - 平台管理服务
 */
class HdAdminService
{
    /**
     * 租户列表
     */
    public function getTenants(array $params = []): array
    {
        $where = [];
        if (!empty($params['keyword'])) {
            $where[] = ['b.name|b.tel', 'like', '%' . $params['keyword'] . '%'];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $list = Db::name('business')
            ->alias('b')
            ->leftJoin('hd_business_config bc', 'b.id = bc.bid')
            ->leftJoin('hd_plan p', 'bc.plan_id = p.id')
            ->where($where)
            ->field('b.id as bid, b.name, b.tel, b.lianxiren, b.status, b.createtime, bc.plan_id, bc.plan_expire_time, p.name as plan_name')
            ->page($page, $limit)
            ->order('b.id desc')
            ->select()
            ->toArray();

        // 附加统计
        foreach ($list as &$item) {
            $item['activity_count'] = HdActivity::where('bid', $item['bid'])->count();
            $item['is_plan_valid'] = $item['plan_expire_time'] > time();
        }
        unset($item);

        $count = Db::name('business')
            ->alias('b')
            ->leftJoin('hd_business_config bc', 'b.id = bc.bid')
            ->where($where)
            ->count();

        return [
            'code' => 0,
            'data' => [
                'list'  => $list,
                'count' => $count,
            ],
        ];
    }

    /**
     * 启用/禁用租户
     */
    public function updateTenantStatus(int $bid, int $status): array
    {
        $business = Db::name('business')->where('id', $bid)->find();
        if (!$business) {
            return ['code' => 1, 'msg' => '商家不存在'];
        }

        Db::name('business')->where('id', $bid)->update(['status' => $status]);

        return ['code' => 0, 'msg' => $status == 1 ? '已启用' : '已禁用'];
    }

    /**
     * 套餐列表
     */
    public function getPlans(): array
    {
        $list = HdPlan::order('sort desc, id asc')->select()->toArray();
        return ['code' => 0, 'data' => $list];
    }

    /**
     * 创建套餐
     */
    public function createPlan(array $data): array
    {
        try {
            $plan = new HdPlan();
            $plan->aid = 0;
            $plan->name = $data['name'] ?? '';
            $plan->price = (float)($data['price'] ?? 0);
            $plan->duration_days = (int)($data['duration_days'] ?? 365);
            $plan->max_stores = (int)($data['max_stores'] ?? 1);
            $plan->max_activities = (int)($data['max_activities'] ?? 1);
            $plan->max_participants = (int)($data['max_participants'] ?? 100);
            $plan->features = $data['features'] ?? '';
            $plan->status = (int)($data['status'] ?? 1);
            $plan->sort = (int)($data['sort'] ?? 0);
            $plan->createtime = time();
            $plan->save();

            return ['code' => 0, 'msg' => '创建成功', 'data' => $plan->toArray()];
        } catch (\Exception $e) {
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 更新套餐
     */
    public function updatePlan(int $id, array $data): array
    {
        $plan = HdPlan::find($id);
        if (!$plan) {
            return ['code' => 1, 'msg' => '套餐不存在'];
        }

        if (isset($data['name'])) $plan->name = $data['name'];
        if (isset($data['price'])) $plan->price = (float)$data['price'];
        if (isset($data['duration_days'])) $plan->duration_days = (int)$data['duration_days'];
        if (isset($data['max_stores'])) $plan->max_stores = (int)$data['max_stores'];
        if (isset($data['max_activities'])) $plan->max_activities = (int)$data['max_activities'];
        if (isset($data['max_participants'])) $plan->max_participants = (int)$data['max_participants'];
        if (isset($data['features'])) $plan->features = $data['features'];
        if (isset($data['status'])) $plan->status = (int)$data['status'];
        if (isset($data['sort'])) $plan->sort = (int)$data['sort'];

        $plan->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 平台统计数据
     */
    public function getStats(): array
    {
        $totalTenants = Db::name('hd_business_config')->count();
        $activeTenants = Db::name('hd_business_config')
            ->where('plan_expire_time', '>', time())
            ->count();
        $totalActivities = HdActivity::count();
        $activeActivities = HdActivity::where('status', HdActivity::STATUS_IN_PROGRESS)->count();

        return [
            'code' => 0,
            'data' => [
                'total_tenants'     => $totalTenants,
                'active_tenants'    => $activeTenants,
                'total_activities'  => $totalActivities,
                'active_activities' => $activeActivities,
            ],
        ];
    }
}

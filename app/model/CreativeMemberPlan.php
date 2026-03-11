<?php
/**
 * 创作会员套餐模型
 */
namespace app\model;

use think\facade\Db;

class CreativeMemberPlan
{
    protected $table = 'creative_member_plan';
    
    /**
     * 获取启用的套餐列表
     * @param int $aid 账户ID
     * @return array
     */
    public static function getActivePlans($aid = 0)
    {
        $query = Db::name('creative_member_plan')
            ->where('status', 1);
        if ($aid > 0) {
            $query->where('aid', $aid);
        }
        return $query->order('sort asc, id asc')->select()->toArray();
    }
    
    /**
     * 获取套餐详情
     * @param int $id
     * @return array|null
     */
    public static function getById($id)
    {
        return Db::name('creative_member_plan')->where('id', $id)->find();
    }
    
    /**
     * 按版本代码和购买模式获取套餐
     * @param string $versionCode
     * @param string $purchaseMode
     * @param int $aid
     * @return array|null
     */
    public static function getByVersionAndMode($versionCode, $purchaseMode, $aid = 0)
    {
        $query = Db::name('creative_member_plan')
            ->where('version_code', $versionCode)
            ->where('purchase_mode', $purchaseMode)
            ->where('status', 1);
        if ($aid > 0) {
            $query->where('aid', $aid);
        }
        return $query->find();
    }
    
    /**
     * 按版本分组获取套餐
     * @param int $aid
     * @return array 以version_code为key的分组数据
     */
    public static function getGroupedByVersion($aid = 0)
    {
        $plans = self::getActivePlans($aid);
        $grouped = [];
        foreach ($plans as $plan) {
            $code = $plan['version_code'];
            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'version_code' => $code,
                    'version_name' => $plan['version_name'],
                    'monthly_score' => $plan['monthly_score'],
                    'max_concurrency' => $plan['max_concurrency'],
                    'cloud_storage_gb' => $plan['cloud_storage_gb'],
                    'modes' => []
                ];
            }
            $grouped[$code]['modes'][$plan['purchase_mode']] = $plan;
        }
        return $grouped;
    }
    
    /**
     * 格式化套餐信息给前端
     * @param array $plan
     * @return array
     */
    public static function formatForApi($plan)
    {
        return [
            'id' => $plan['id'],
            'version_code' => $plan['version_code'],
            'version_name' => $plan['version_name'],
            'purchase_mode' => $plan['purchase_mode'],
            'price' => floatval($plan['price']),
            'original_price' => floatval($plan['original_price']),
            'discount_text' => $plan['discount_text'],
            'monthly_score' => intval($plan['monthly_score']),
            'daily_login_score' => intval($plan['daily_login_score']),
            'max_concurrency' => intval($plan['max_concurrency']),
            'cloud_storage_gb' => intval($plan['cloud_storage_gb']),
            'model_rights' => $plan['model_rights'] ? json_decode($plan['model_rights'], true) : [],
            'features' => $plan['features'] ? json_decode($plan['features'], true) : [],
        ];
    }
}

<?php
/**
 * 创作会员订阅模型
 */
namespace app\model;

use think\facade\Db;

class CreativeMemberSubscription
{
    protected $table = 'creative_member_subscription';
    
    /**
     * 订阅状态常量
     */
    const STATUS_EXPIRED = 0;   // 已过期
    const STATUS_ACTIVE = 1;    // 生效中
    const STATUS_CANCELLED = 2; // 已取消
    
    /**
     * 获取用户当前有效订阅
     * @param int $mid 会员ID
     * @param int $aid 账户ID
     * @return array|null
     */
    public static function getActiveSubscription($mid, $aid = 0)
    {
        $query = Db::name('creative_member_subscription')
            ->where('mid', $mid)
            ->where('status', self::STATUS_ACTIVE)
            ->where('expire_time', '>', time());
        if ($aid > 0) {
            $query->where('aid', $aid);
        }
        return $query->order('id desc')->find();
    }
    
    /**
     * 创建订阅记录
     * @param array $data
     * @return int 订阅ID
     */
    public static function createSubscription($data)
    {
        return Db::name('creative_member_subscription')->insertGetId($data);
    }
    
    /**
     * 更新订阅状态
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function updateSubscription($id, $data)
    {
        return Db::name('creative_member_subscription')->where('id', $id)->update($data) !== false;
    }
    
    /**
     * 获取用户订阅历史
     * @param int $mid
     * @param int $aid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getSubscriptionHistory($mid, $aid = 0, $page = 1, $limit = 20)
    {
        $query = Db::name('creative_member_subscription')
            ->where('mid', $mid);
        if ($aid > 0) {
            $query->where('aid', $aid);
        }
        $total = $query->count();
        $list = $query->order('id desc')->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total];
    }
    
    /**
     * 获取待续费的订阅列表
     * @return array
     */
    public static function getPendingRenewalSubscriptions()
    {
        return Db::name('creative_member_subscription')
            ->where('status', self::STATUS_ACTIVE)
            ->where('auto_renew', 1)
            ->where('next_renew_time', '<=', time())
            ->where('next_renew_time', '>', 0)
            ->select()->toArray();
    }
    
    /**
     * 获取已过期的订阅列表
     * @return array
     */
    public static function getExpiredSubscriptions()
    {
        return Db::name('creative_member_subscription')
            ->where('status', self::STATUS_ACTIVE)
            ->where('expire_time', '<=', time())
            ->select()->toArray();
    }
    
    /**
     * 格式化订阅信息给前端
     * @param array $sub
     * @return array
     */
    public static function formatForApi($sub)
    {
        if (!$sub) return null;
        return [
            'id' => $sub['id'],
            'plan_id' => $sub['plan_id'],
            'version_code' => $sub['version_code'],
            'purchase_mode' => $sub['purchase_mode'],
            'start_time' => date('Y-m-d H:i:s', $sub['start_time']),
            'expire_time' => date('Y-m-d H:i:s', $sub['expire_time']),
            'auto_renew' => intval($sub['auto_renew']),
            'status' => intval($sub['status']),
            'remaining_score' => intval($sub['remaining_score']),
            'total_score_used' => intval($sub['total_score_used']),
        ];
    }
}

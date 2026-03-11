<?php
/**
 * 创作积分流水模型
 */
namespace app\model;

use think\facade\Db;

class CreativeMemberScoreLog
{
    protected $table = 'creative_member_score_log';
    
    /**
     * 流水类型常量
     */
    const TYPE_MONTHLY_GRANT = 1;  // 月度发放
    const TYPE_DAILY_LOGIN = 2;    // 每日登录
    const TYPE_CONSUME = 3;        // 消费扣除
    const TYPE_REFUND = 4;         // 退款返还
    
    /**
     * 记录积分流水
     * @param array $data
     * @return int 流水ID
     */
    public static function log($data)
    {
        $logData = [
            'aid' => $data['aid'] ?? 0,
            'mid' => $data['mid'] ?? 0,
            'subscription_id' => $data['subscription_id'] ?? 0,
            'type' => $data['type'] ?? 0,
            'amount' => $data['amount'] ?? 0,
            'balance' => $data['balance'] ?? 0,
            'remark' => $data['remark'] ?? '',
            'related_order_id' => $data['related_order_id'] ?? 0,
            'createtime' => time(),
        ];
        return Db::name('creative_member_score_log')->insertGetId($logData);
    }
    
    /**
     * 获取用户积分流水列表
     * @param int $mid
     * @param int $aid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getLogList($mid, $aid = 0, $page = 1, $limit = 20)
    {
        $query = Db::name('creative_member_score_log')
            ->where('mid', $mid);
        if ($aid > 0) {
            $query->where('aid', $aid);
        }
        $total = $query->count();
        $list = $query->order('id desc')->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total];
    }
    
    /**
     * 检查用户今日是否已领取登录积分
     * @param int $mid
     * @param int $aid
     * @return bool
     */
    public static function hasDailyLoginToday($mid, $aid = 0)
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86400;
        $query = Db::name('creative_member_score_log')
            ->where('mid', $mid)
            ->where('type', self::TYPE_DAILY_LOGIN)
            ->where('createtime', '>=', $todayStart)
            ->where('createtime', '<', $todayEnd);
        if ($aid > 0) {
            $query->where('aid', $aid);
        }
        return $query->count() > 0;
    }
    
    /**
     * 获取类型名称
     * @param int $type
     * @return string
     */
    public static function getTypeName($type)
    {
        $names = [
            self::TYPE_MONTHLY_GRANT => '月度积分发放',
            self::TYPE_DAILY_LOGIN => '每日登录奖励',
            self::TYPE_CONSUME => '创作消费扣除',
            self::TYPE_REFUND => '退款积分返还',
        ];
        return $names[$type] ?? '未知';
    }
}

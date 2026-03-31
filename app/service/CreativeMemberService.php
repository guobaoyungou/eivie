<?php
/**
 * 创作会员服务类
 * 处理创作会员套餐查询、购买、订阅管理、积分发放与消费、并发检查等
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\CreativeMemberPlan;
use app\model\CreativeMemberSubscription;
use app\model\CreativeMemberScoreLog;

class CreativeMemberService
{
    /**
     * 获取AI创作积分支付配置
     * @param int $aid
     * @return array
     */
    public function getScorePayConfig($aid)
    {
        $adminSet = Db::name('admin_set')->where('aid', $aid)->field('ai_score_pay_status,ai_score_exchange_rate,ai_score_pay_mode,ai_score_unit_name')->find();
        $unitName = trim($adminSet['ai_score_unit_name'] ?? '');
        if ($unitName === '') {
            $unitName = '词元';
        }
        return [
            'enabled' => intval($adminSet['ai_score_pay_status'] ?? 0) == 1,
            'exchange_rate' => floatval($adminSet['ai_score_exchange_rate'] ?? 0.01),
            'pay_mode' => intval($adminSet['ai_score_pay_mode'] ?? 0), // 0=全额积分 1=积分优先余额补足
            'unit_name' => $unitName, // 计量单位展示名称，默认"词元"
        ];
    }
    
    /**
     * 将金额转换为所需积分数
     * @param float $amount 金额(元)
     * @param float $exchangeRate 兑换比例(1积分=多少元)
     * @return int
     */
    public function moneyToScore($amount, $exchangeRate)
    {
        if ($exchangeRate <= 0) return 0;
        return intval(ceil($amount / $exchangeRate));
    }
    
    /**
     * 将积分转换为金额
     * @param int $score
     * @param float $exchangeRate
     * @return float
     */
    public function scoreToMoney($score, $exchangeRate)
    {
        return round($score * $exchangeRate, 2);
    }
    
    /**
     * 获取用户余额和积分信息
     * @param int $mid
     * @param int $aid
     * @return array
     */
    public function getUserBalanceInfo($mid, $aid)
    {
        $member = Db::name('member')
            ->where('id', $mid)
            ->where('aid', $aid)
            ->field('id,money,score')
            ->find();
        
        $scoreConfig = $this->getScorePayConfig($aid);
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        
        return [
            'balance' => floatval($member['money'] ?? 0),
            'score' => intval($member['score'] ?? 0),
            'creative_score' => $subscription ? intval($subscription['remaining_score']) : 0,
            'score_pay_enabled' => $scoreConfig['enabled'],
            'score_exchange_rate' => $scoreConfig['exchange_rate'],
            'has_creative_member' => $subscription ? true : false,
            'creative_member_version' => $subscription ? $subscription['version_code'] : '',
        ];
    }
    
    /**
     * 获取套餐列表（API格式）
     * @param int $aid
     * @param int $mid 当前用户ID（可选）
     * @return array
     */
    public function getPlanList($aid, $mid = 0)
    {
        $plans = CreativeMemberPlan::getActivePlans($aid);
        $formattedPlans = [];
        foreach ($plans as $plan) {
            $formattedPlans[] = CreativeMemberPlan::formatForApi($plan);
        }
        
        $currentSubscription = null;
        if ($mid > 0) {
            $sub = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
            $currentSubscription = CreativeMemberSubscription::formatForApi($sub);
        }
        
        return [
            'plans' => $formattedPlans,
            'current_subscription' => $currentSubscription,
        ];
    }
    
    /**
     * 购买创作会员
     * @param int $aid
     * @param int $mid
     * @param int $planId
     * @param string $purchaseMode
     * @return array
     */
    public function buyCreativeMember($aid, $mid, $planId, $purchaseMode)
    {
        $plan = CreativeMemberPlan::getById($planId);
        if (!$plan || $plan['status'] != 1) {
            return ['status' => 0, 'msg' => '套餐不存在或已下架'];
        }
        
        if ($plan['purchase_mode'] != $purchaseMode) {
            return ['status' => 0, 'msg' => '购买模式不匹配'];
        }
        
        // 检查是否已有有效订阅
        $existingSub = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        if ($existingSub) {
            return ['status' => 0, 'msg' => '您已有生效中的创作会员，请到期后再购买'];
        }
        
        $price = floatval($plan['price']);
        
        // 查询用户余额
        $member = Db::name('member')
            ->where('id', $mid)
            ->where('aid', $aid)
            ->field('id,money,score')
            ->find();
        
        if (!$member) {
            return ['status' => 0, 'msg' => '用户不存在'];
        }
        
        $money = floatval($member['money']);
        
        // 创建支付订单
        $ordernum = 'CM' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        Db::startTrans();
        try {
            // 如果价格为0，直接激活
            if ($price <= 0) {
                $subId = $this->activateSubscription($aid, $mid, $plan);
                Db::commit();
                return ['status' => 1, 'msg' => '开通成功', 'data' => ['subscription_id' => $subId, 'need_pay' => false]];
            }
            
            // 创建 payorder 支付订单（orderid存储plan_id，便于回调时直接查找套餐）
            $payorderData = [
                'aid' => $aid,
                'bid' => 0,
                'mid' => $mid,
                'ordernum' => $ordernum,
                'orderid' => $planId, // 存储plan_id，回调时用于查找套餐
                'type' => 'creative_member',
                'title' => '创作会员-' . $plan['version_name'] . '(' . $this->getPurchaseModeName($purchaseMode) . ')',
                'money' => $price,
                'status' => 0,
                'createtime' => time(),
                'platform' => 'h5',
            ];
            $payorderId = Db::name('payorder')->insertGetId($payorderData);

            // 关闭该用户同类型的旧未支付订单
            Db::name('payorder')->where('id', '<>', $payorderId)
                ->where('aid', $aid)->where('mid', $mid)
                ->where('type', 'creative_member')->where('status', 0)
                ->update(['status' => 2]);
            
            Db::commit();
            
            return [
                'status' => 1,
                'msg' => '订单创建成功',
                'data' => [
                    'ordernum' => $ordernum,
                    'payorder_id' => $payorderId,
                    'price' => $price,
                    'need_pay' => true,
                    'plan_name' => $plan['version_name'],
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('购买创作会员失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '购买失败，请重试'];
        }
    }
    
    /**
     * 激活订阅（支付成功后调用）
     * @param int $aid
     * @param int $mid
     * @param array $plan
     * @param int $orderId 支付订单ID
     * @return int subscription_id
     */
    public function activateSubscription($aid, $mid, $plan, $orderId = 0)
    {
        $now = time();
        $purchaseMode = $plan['purchase_mode'];
        
        // 计算到期时间
        if ($purchaseMode == 'yearly') {
            $expireTime = strtotime('+1 year', $now);
        } else {
            $expireTime = strtotime('+1 month', $now);
        }
        
        // 连续包月需要设置自动续费
        $autoRenew = ($purchaseMode == 'monthly_auto') ? 1 : 0;
        $nextRenewTime = $autoRenew ? $expireTime : 0;
        
        $subData = [
            'aid' => $aid,
            'mid' => $mid,
            'plan_id' => $plan['id'],
            'version_code' => $plan['version_code'],
            'purchase_mode' => $purchaseMode,
            'start_time' => $now,
            'expire_time' => $expireTime,
            'next_renew_time' => $nextRenewTime,
            'auto_renew' => $autoRenew,
            'status' => CreativeMemberSubscription::STATUS_ACTIVE,
            'remaining_score' => $plan['monthly_score'],
            'total_score_used' => 0,
            'orderid' => $orderId,
            'createtime' => $now,
        ];
        
        $subId = CreativeMemberSubscription::createSubscription($subData);
        
        // 更新用户云端存储配额
        try {
            $storageService = new StorageService();
            $storageService->recalculateQuota($aid, $mid);
        } catch (\Exception $e) {
            Log::warning('激活订阅后更新存储配额失败: ' . $e->getMessage());
        }
        
        // 记录月度积分发放流水
        CreativeMemberScoreLog::log([
            'aid' => $aid,
            'mid' => $mid,
            'subscription_id' => $subId,
            'type' => CreativeMemberScoreLog::TYPE_MONTHLY_GRANT,
            'amount' => $plan['monthly_score'],
            'balance' => $plan['monthly_score'],
            'remark' => '创作会员开通-' . $plan['version_name'] . '月度积分发放',
        ]);
        
        return $subId;
    }
    
    /**
     * 支付成功回调处理
     * @param string $ordernum
     * @param array $payInfo
     * @return array
     */
    public function onPaid($ordernum, $payInfo = [])
    {
        $payorder = Db::name('payorder')
            ->where('ordernum', $ordernum)
            ->where('type', 'creative_member')
            ->find();
        
        if (!$payorder) {
            return ['status' => 0, 'msg' => '订单不存在'];
        }
        
        // 查找对应的plan
        $aid = $payorder['aid'];
        $mid = $payorder['mid'];
        $plan = null;
        
        // 优先通过orderid存储的plan_id查找套餐
        if (!empty($payorder['orderid'])) {
            $plan = Db::name('creative_member_plan')
                ->where('id', intval($payorder['orderid']))
                ->where('status', 1)
                ->find();
        }
        // 兼容旧订单：通过价格匹配
        if (!$plan) {
            $plan = Db::name('creative_member_plan')
                ->where('aid', $aid)
                ->where('price', $payorder['money'])
                ->where('status', 1)
                ->find();
        }
        if (!$plan) {
            $plan = Db::name('creative_member_plan')
                ->where('price', $payorder['money'])
                ->where('status', 1)
                ->find();
        }
        
        if (!$plan) {
            Log::error("创作会员支付回调：找不到匹配的套餐, ordernum={$ordernum}");
            return ['status' => 0, 'msg' => '找不到匹配的套餐'];
        }
        
        Db::startTrans();
        try {
            $subId = $this->activateSubscription($aid, $mid, $plan, $payorder['id']);
            
            // 更新payorder状态
            Db::name('payorder')->where('id', $payorder['id'])->update([
                'orderid' => $subId,
                'status' => 1,
            ]);
            
            Db::commit();
            return ['status' => 1, 'msg' => '会员开通成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创作会员开通失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '开通失败'];
        }
    }
    
    /**
     * 消费创作积分
     * @param int $aid
     * @param int $mid
     * @param int $scoreAmount 需要消费的积分数
     * @param int $orderId 关联订单ID
     * @return array ['status'=>0/1, 'msg'=>'', 'deducted_score'=>int, 'deducted_system_score'=>int]
     */
    public function consumeScore($aid, $mid, $scoreAmount, $orderId = 0)
    {
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        
        $deductedCreative = 0;
        $deductedSystem = 0;
        $remainingNeed = $scoreAmount;
        
        // 先从创作会员积分扣除
        if ($subscription && $subscription['remaining_score'] > 0) {
            $deductFromSub = min($subscription['remaining_score'], $remainingNeed);
            $newRemaining = $subscription['remaining_score'] - $deductFromSub;
            
            CreativeMemberSubscription::updateSubscription($subscription['id'], [
                'remaining_score' => $newRemaining,
                'total_score_used' => $subscription['total_score_used'] + $deductFromSub,
            ]);
            
            CreativeMemberScoreLog::log([
                'aid' => $aid,
                'mid' => $mid,
                'subscription_id' => $subscription['id'],
                'type' => CreativeMemberScoreLog::TYPE_CONSUME,
                'amount' => -$deductFromSub,
                'balance' => $newRemaining,
                'remark' => '创作消费扣除',
                'related_order_id' => $orderId,
            ]);
            
            $deductedCreative = $deductFromSub;
            $remainingNeed -= $deductFromSub;
        }
        
        // 如果还不够，从系统积分扣除
        if ($remainingNeed > 0) {
            $member = Db::name('member')->where('id', $mid)->where('aid', $aid)->field('id,score')->find();
            $systemScore = intval($member['score'] ?? 0);
            
            if ($systemScore < $remainingNeed) {
                // 回滚创作积分扣除
                if ($deductedCreative > 0 && $subscription) {
                    CreativeMemberSubscription::updateSubscription($subscription['id'], [
                        'remaining_score' => $subscription['remaining_score'],
                        'total_score_used' => $subscription['total_score_used'],
                    ]);
                }
                return ['status' => 0, 'msg' => '积分不足'];
            }
            
            Db::name('member')->where('id', $mid)->dec('score', $remainingNeed)->update();
            $deductedSystem = $remainingNeed;
        }
        
        return [
            'status' => 1,
            'msg' => '积分扣除成功',
            'deducted_creative_score' => $deductedCreative,
            'deducted_system_score' => $deductedSystem,
        ];
    }
    
    /**
     * 退还积分（生成失败退款）
     * @param int $aid
     * @param int $mid
     * @param int $scoreAmount
     * @param int $orderId
     * @return bool
     */
    public function refundScore($aid, $mid, $scoreAmount, $orderId = 0)
    {
        // 优先退还到系统积分
        Db::name('member')->where('id', $mid)->where('aid', $aid)->inc('score', $scoreAmount)->update();
        
        $member = Db::name('member')->where('id', $mid)->where('aid', $aid)->field('score')->find();
        
        CreativeMemberScoreLog::log([
            'aid' => $aid,
            'mid' => $mid,
            'subscription_id' => 0,
            'type' => CreativeMemberScoreLog::TYPE_REFUND,
            'amount' => $scoreAmount,
            'balance' => intval($member['score'] ?? 0),
            'remark' => '生成失败积分退还',
            'related_order_id' => $orderId,
        ]);
        
        return true;
    }
    
    /**
     * 每日登录赠送积分
     * @param int $aid
     * @param int $mid
     * @return array
     */
    public function dailyLoginBonus($aid, $mid)
    {
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        if (!$subscription) {
            return ['status' => 0, 'msg' => '非创作会员'];
        }
        
        // 检查今日是否已领取
        if (CreativeMemberScoreLog::hasDailyLoginToday($mid, $aid)) {
            return ['status' => 0, 'msg' => '今日已领取'];
        }
        
        $plan = CreativeMemberPlan::getById($subscription['plan_id']);
        $dailyScore = $plan ? intval($plan['daily_login_score']) : 20;
        
        // 发放到系统积分
        Db::name('member')->where('id', $mid)->where('aid', $aid)->inc('score', $dailyScore)->update();
        
        $member = Db::name('member')->where('id', $mid)->where('aid', $aid)->field('score')->find();
        
        CreativeMemberScoreLog::log([
            'aid' => $aid,
            'mid' => $mid,
            'subscription_id' => $subscription['id'],
            'type' => CreativeMemberScoreLog::TYPE_DAILY_LOGIN,
            'amount' => $dailyScore,
            'balance' => intval($member['score'] ?? 0),
            'remark' => '每日登录赠送积分',
        ]);
        
        return ['status' => 1, 'msg' => '领取成功', 'data' => ['score' => $dailyScore]];
    }
    
    /**
     * 检查用户并发任务数是否超限
     * @param int $aid
     * @param int $mid
     * @return array ['allowed'=>bool, 'max'=>int, 'current'=>int]
     */
    public function checkConcurrency($aid, $mid)
    {
        // 查询用户当前处理中的任务数
        $currentTasks = Db::name('generation_order')
            ->where('aid', $aid)
            ->where('mid', $mid)
            ->where('pay_status', 1) // 已支付
            ->whereIn('task_status', [0, 1]) // 待处理或处理中
            ->count();
        
        // 获取用户创作会员的并发上限
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        $maxConcurrency = 1; // 非会员默认1
        
        if ($subscription) {
            $plan = CreativeMemberPlan::getById($subscription['plan_id']);
            if ($plan) {
                $maxConcurrency = intval($plan['max_concurrency']);
                if ($maxConcurrency <= 0) $maxConcurrency = 999; // 无限
            }
        }
        
        return [
            'allowed' => $currentTasks < $maxConcurrency,
            'max' => $maxConcurrency,
            'current' => $currentTasks,
        ];
    }
    
    /**
     * 检查模型是否在用户免积分列表中
     * @param int $aid
     * @param int $mid
     * @param string $modelCode
     * @return bool
     */
    public function isModelFreeForUser($aid, $mid, $modelCode)
    {
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        if (!$subscription) return false;
        
        $plan = CreativeMemberPlan::getById($subscription['plan_id']);
        if (!$plan || empty($plan['model_rights'])) return false;
        
        $modelRights = json_decode($plan['model_rights'], true);
        if (!is_array($modelRights)) return false;
        
        $subStartTime = $subscription['start_time'];
        $now = time();
        
        foreach ($modelRights as $right) {
            if (($right['model_code'] ?? '') == $modelCode) {
                $freeDays = intval($right['free_days'] ?? 0);
                if ($freeDays <= 0) continue;
                
                $freeExpireTime = strtotime("+{$freeDays} days", $subStartTime);
                if ($now < $freeExpireTime) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 获取购买模式名称
     * @param string $mode
     * @return string
     */
    public function getPurchaseModeName($mode)
    {
        $names = [
            'yearly' => '按年购买',
            'monthly_auto' => '连续包月',
            'monthly' => '单月购买',
        ];
        return $names[$mode] ?? $mode;
    }
    
    /**
     * 获取用户总可用积分（创作积分+系统积分）
     * @param int $aid
     * @param int $mid
     * @return int
     */
    public function getTotalAvailableScore($aid, $mid)
    {
        $member = Db::name('member')->where('id', $mid)->where('aid', $aid)->field('score')->find();
        $systemScore = intval($member['score'] ?? 0);
        
        $subscription = CreativeMemberSubscription::getActiveSubscription($mid, $aid);
        $creativeScore = $subscription ? intval($subscription['remaining_score']) : 0;
        
        return $systemScore + $creativeScore;
    }
}

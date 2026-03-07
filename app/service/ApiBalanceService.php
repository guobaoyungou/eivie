<?php

namespace app\service;

use app\model\Member;
use app\model\MemberMoneylog;
use app\model\ApiPricing;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 余额检查扣费服务
 * Class ApiBalanceService
 * @package app\service
 */
class ApiBalanceService
{
    /**
     * 检查余额并预扣费
     * 
     * @param int $apiConfigId API配置ID
     * @param int $uid 用户ID
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array
     */
    public function checkAndPreDeduct($apiConfigId, $uid, $aid, $bid, $mdid)
    {
        try {
            // 1. 获取计费规则
            $pricing = ApiPricing::where('api_config_id', $apiConfigId)
                ->where('is_active', 1)
                ->find();
            
            if (!$pricing) {
                // 没有计费规则，视为免费
                return [
                    'success' => true,
                    'message' => '免费API',
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'deduct_id' => null
                ];
            }
            
            // 2. 检查免费额度
            $pricingService = new ApiPricingService();
            $remainingQuota = $pricingService->getRemainingQuota($apiConfigId, $uid);
            
            if ($remainingQuota > 0) {
                // 还有免费额度，不扣费
                return [
                    'success' => true,
                    'message' => '使用免费额度',
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'deduct_id' => null,
                    'free_quota_used' => true
                ];
            }
            
            // 3. 获取用户余额
            $member = Member::find($uid);
            if (!$member) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }
            
            $currentBalance = floatval($member->money ?? 0);
            
            // 4. 预估费用（使用最小收费或单价）
            $estimatedAmount = $pricing->min_charge > 0 
                ? $pricing->min_charge 
                : $pricing->price_per_unit;
            
            // 5. 检查余额是否充足
            if ($currentBalance < $estimatedAmount) {
                return [
                    'success' => false,
                    'message' => '余额不足，当前余额：' . $currentBalance . '元，预估费用：' . $estimatedAmount . '元',
                    'balance' => $currentBalance,
                    'estimated_amount' => $estimatedAmount
                ];
            }
            
            // 6. 预扣费（锁定金额）
            $deductId = $this->preDeduct($uid, $estimatedAmount, $apiConfigId);
            
            return [
                'success' => true,
                'message' => '预扣费成功',
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance - $estimatedAmount,
                'deduct_id' => $deductId,
                'pre_deduct_amount' => $estimatedAmount
            ];
            
        } catch (\Exception $e) {
            Log::error('余额检查失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 预扣费（锁定金额）
     */
    private function preDeduct($uid, $amount, $apiConfigId)
    {
        // 使用缓存锁定金额
        $lockKey = "api_balance_lock:{$uid}:{$apiConfigId}:" . time();
        $lockData = [
            'uid' => $uid,
            'amount' => $amount,
            'api_config_id' => $apiConfigId,
            'status' => 'locked',
            'create_time' => time()
        ];
        
        Cache::set($lockKey, $lockData, 300); // 锁定5分钟
        
        return $lockKey;
    }
    
    /**
     * 确认扣费
     */
    public function confirmDeduct($deductId, $actualAmount)
    {
        if (!$deductId) {
            // 没有预扣费ID，可能是免费额度
            return ['success' => true];
        }
        
        try {
            // 获取预扣费信息
            $lockData = Cache::get($deductId);
            if (!$lockData) {
                throw new \Exception('预扣费记录不存在或已过期');
            }
            
            $uid = $lockData['uid'];
            $apiConfigId = $lockData['api_config_id'];
            
            // 开启事务
            Db::startTrans();
            
            // 扣除用户余额
            $member = Member::lock(true)->find($uid);
            if (!$member) {
                throw new \Exception('用户不存在');
            }
            
            $balanceBefore = floatval($member->money ?? 0);
            
            if ($balanceBefore < $actualAmount) {
                throw new \Exception('余额不足');
            }
            
            $member->money = $balanceBefore - $actualAmount;
            $member->save();
            
            // 记录余额变动日志
            $this->recordMoneyLog([
                'uid' => $uid,
                'type' => 'api_call',
                'money' => -$actualAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $member->money,
                'remark' => 'API调用费用扣除',
                'related_id' => $apiConfigId
            ]);
            
            // 删除预扣费锁
            Cache::delete($deductId);
            
            Db::commit();
            
            return [
                'success' => true,
                'balance_before' => $balanceBefore,
                'balance_after' => $member->money,
                'deduct_amount' => $actualAmount
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('确认扣费失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 退回预扣费
     */
    public function refundDeduct($deductId)
    {
        if (!$deductId) {
            return ['success' => true];
        }
        
        try {
            // 删除预扣费锁即可
            Cache::delete($deductId);
            
            return [
                'success' => true,
                'message' => '预扣费已退回'
            ];
        } catch (\Exception $e) {
            Log::error('退回预扣费失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 记录余额变动日志
     */
    private function recordMoneyLog($data)
    {
        try {
            $log = new MemberMoneylog();
            $log->uid = $data['uid'];
            $log->type = $data['type'];
            $log->money = $data['money'];
            $log->remark = $data['remark'];
            $log->create_time = time();
            
            // 如果MemberMoneylog模型有这些字段
            if (method_exists($log, 'setAttribute')) {
                if (isset($data['balance_before'])) {
                    $log->setAttribute('balance_before', $data['balance_before']);
                }
                if (isset($data['balance_after'])) {
                    $log->setAttribute('balance_after', $data['balance_after']);
                }
                if (isset($data['related_id'])) {
                    $log->setAttribute('related_id', $data['related_id']);
                }
            }
            
            $log->save();
        } catch (\Exception $e) {
            Log::error('记录余额日志失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取用户余额
     */
    public function getBalance($uid)
    {
        $member = Member::find($uid);
        if (!$member) {
            return 0;
        }
        
        return floatval($member->money ?? 0);
    }
    
    /**
     * 充值
     */
    public function recharge($uid, $amount, $remark = '余额充值')
    {
        Db::startTrans();
        try {
            $member = Member::lock(true)->find($uid);
            if (!$member) {
                throw new \Exception('用户不存在');
            }
            
            $balanceBefore = floatval($member->money ?? 0);
            $member->money = $balanceBefore + $amount;
            $member->save();
            
            // 记录充值日志
            $this->recordMoneyLog([
                'uid' => $uid,
                'type' => 'recharge',
                'money' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $member->money,
                'remark' => $remark
            ]);
            
            Db::commit();
            
            return [
                'success' => true,
                'balance' => $member->money
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('充值失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 批量检查余额
     */
    public function batchCheckBalance($uids)
    {
        $members = Member::whereIn('id', $uids)->column('money', 'id');
        
        $result = [];
        foreach ($uids as $uid) {
            $result[$uid] = floatval($members[$uid] ?? 0);
        }
        
        return $result;
    }
}

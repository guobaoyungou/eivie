<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;

/**
 * 商家余额扣费服务
 * 
 * 处理合成生成时的余额预检、预扣费、单模板退款、确认扣费等操作。
 * 使用乐观锁策略防止并发超扣。
 */
class BalanceDeductService
{
    /**
     * 余额预检
     *
     * @param int $bid 商家ID
     * @param float $requiredAmount 所需金额
     * @return array ['allowed' => bool, 'balance' => float, 'shortfall' => float]
     */
    public function checkBalance(int $bid, float $requiredAmount): array
    {
        $balance = (float) Db::name('business')
            ->where('id', $bid)
            ->value('account_balance');

        $allowed = $balance >= $requiredAmount;
        $shortfall = $allowed ? 0 : round($requiredAmount - $balance, 2);

        return [
            'allowed' => $allowed,
            'balance' => $balance,
            'shortfall' => $shortfall,
        ];
    }

    /**
     * 预扣费（使用乐观锁防止并发超扣）
     *
     * @param int $bid 商家ID
     * @param float $amount 扣除金额
     * @param int $portraitId 关联人像ID
     * @param string $reason 扣费原因
     * @return array ['status' => bool, 'deductId' => int]
     */
    public function preDeduct(int $bid, float $amount, int $portraitId = 0, string $reason = ''): array
    {
        if ($amount <= 0) {
            return ['status' => true, 'deductId' => 0];
        }

        // 先获取当前余额用于流水记录
        $balanceBefore = (float) Db::name('business')
            ->where('id', $bid)
            ->value('account_balance');

        // 乐观锁扣减: WHERE id={bid} AND account_balance >= {amount}
        $affected = Db::name('business')
            ->where('id', $bid)
            ->where('account_balance', '>=', $amount)
            ->dec('account_balance', $amount)
            ->update();

        if ($affected === 0) {
            Log::warning("BalanceDeductService::preDeduct 扣费失败(余额不足或并发冲突), bid={$bid}, amount={$amount}");
            return ['status' => false, 'deductId' => 0];
        }

        $balanceAfter = round($balanceBefore - $amount, 2);

        // 获取aid
        $aid = (int) Db::name('business')->where('id', $bid)->value('aid');

        // 写入扣费流水
        $deductId = (int) Db::name('business_balance_log')->insertGetId([
            'aid' => $aid,
            'bid' => $bid,
            'type' => 'deduct',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'portrait_id' => $portraitId,
            'order_id' => 0,
            'remark' => $reason ?: '合成预扣费',
            'createtime' => time(),
        ]);

        Log::info("BalanceDeductService::preDeduct 预扣费成功, bid={$bid}, amount={$amount}, deductId={$deductId}");

        return ['status' => true, 'deductId' => $deductId];
    }

    /**
     * 单模板失败退款
     *
     * @param int $bid 商家ID
     * @param float $amount 退回金额
     * @param int $deductId 关联的预扣费记录ID
     * @param string $reason 退款原因
     * @return array ['status' => bool]
     */
    public function refundSingle(int $bid, float $amount, int $deductId = 0, string $reason = ''): array
    {
        if ($amount <= 0) {
            return ['status' => true];
        }

        $balanceBefore = (float) Db::name('business')
            ->where('id', $bid)
            ->value('account_balance');

        Db::name('business')
            ->where('id', $bid)
            ->inc('account_balance', $amount)
            ->update();

        $balanceAfter = round($balanceBefore + $amount, 2);

        $aid = (int) Db::name('business')->where('id', $bid)->value('aid');

        Db::name('business_balance_log')->insert([
            'aid' => $aid,
            'bid' => $bid,
            'type' => 'refund',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'portrait_id' => 0,
            'order_id' => $deductId, // 关联原扣费记录
            'remark' => $reason ?: '合成失败退款',
            'createtime' => time(),
        ]);

        Log::info("BalanceDeductService::refundSingle 退款成功, bid={$bid}, amount={$amount}, deductId={$deductId}");

        return ['status' => true];
    }

    /**
     * 确认扣费（标记扣费完成）
     *
     * @param int $deductId 扣费记录ID
     * @return array ['status' => bool]
     */
    public function confirmDeduct(int $deductId): array
    {
        if ($deductId <= 0) {
            return ['status' => true];
        }

        Db::name('business_balance_log')
            ->where('id', $deductId)
            ->update(['remark' => Db::raw("CONCAT(remark, ' [已确认]')")]);

        return ['status' => true];
    }
}

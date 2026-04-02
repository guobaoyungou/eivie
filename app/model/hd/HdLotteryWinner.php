<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-中奖记录模型
 */
class HdLotteryWinner extends Model
{
    protected $name = 'hd_lottery_winner';
    protected $autoWriteTimestamp = false;

    // 状态常量
    const STATUS_NOT_GIVEN = 2; // 未发奖
    const STATUS_GIVEN     = 3; // 已发奖

    /**
     * 所属轮次
     */
    public function round()
    {
        return $this->belongsTo(HdLotteryConfig::class, 'round_id');
    }

    /**
     * 所属奖品
     */
    public function prize()
    {
        return $this->belongsTo(HdPrize::class, 'prize_id');
    }

    /**
     * 所属参与者
     */
    public function participant()
    {
        return $this->belongsTo(HdParticipant::class, 'participant_id');
    }
}

<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-内定记录模型
 */
class HdLotteryDesignated extends Model
{
    protected $name = 'hd_lottery_designated';
    protected $autoWriteTimestamp = false;

    // 内定类型常量
    const DESIGNATED_WIN  = 2; // 必中
    const DESIGNATED_LOSE = 3; // 不中

    /**
     * 所属参与者
     */
    public function participant()
    {
        return $this->belongsTo(HdParticipant::class, 'participant_id');
    }

    /**
     * 所属奖品
     */
    public function prize()
    {
        return $this->belongsTo(HdPrize::class, 'prize_id');
    }
}

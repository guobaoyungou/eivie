<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-抽奖轮次配置
 */
class HdLotteryConfig extends Model
{
    protected $name = 'hd_lottery_config';
    protected $autoWriteTimestamp = false;

    public function getWinnersAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setWinnersAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function prize()
    {
        return $this->belongsTo(HdPrize::class, 'prize_id');
    }
}

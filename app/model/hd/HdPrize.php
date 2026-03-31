<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

class HdPrize extends Model
{
    protected $name = 'hd_prize';
    protected $autoWriteTimestamp = false;

    public function activity()
    {
        return $this->belongsTo(HdActivity::class, 'activity_id');
    }

    /**
     * 判断奖品是否还有剩余
     */
    public function hasRemaining(): bool
    {
        return $this->total_num <= 0 || $this->used_num < $this->total_num;
    }
}

<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-参与者模型
 */
class HdParticipant extends Model
{
    protected $name = 'hd_participant';
    protected $autoWriteTimestamp = false;

    const FLAG_NOT_SIGNED = 1;
    const FLAG_SIGNED = 2;

    /**
     * 所属活动
     */
    public function activity()
    {
        return $this->belongsTo(HdActivity::class, 'activity_id');
    }
}

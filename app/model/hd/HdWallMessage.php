<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-上墙消息模型
 */
class HdWallMessage extends Model
{
    protected $name = 'hd_wall_message';
    protected $autoWriteTimestamp = false;

    const APPROVED_PENDING = 0;
    const APPROVED_PASS = 1;
    const APPROVED_REJECT = 2;

    public function activity()
    {
        return $this->belongsTo(HdActivity::class, 'activity_id');
    }
}

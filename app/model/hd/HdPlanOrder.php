<?php
declare(strict_types=1);
namespace app\model\hd;
use think\Model;

class HdPlanOrder extends Model
{
    protected $name = 'hd_plan_order';
    protected $autoWriteTimestamp = false;

    const PAY_STATUS_UNPAID = 0;
    const PAY_STATUS_PAID = 1;
}

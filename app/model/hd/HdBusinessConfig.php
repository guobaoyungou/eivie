<?php
declare(strict_types=1);
namespace app\model\hd;
use think\Model;

class HdBusinessConfig extends Model
{
    protected $name = 'hd_business_config';
    protected $autoWriteTimestamp = false;

    /**
     * 检查套餐是否有效
     */
    public function isPlanValid(): bool
    {
        if (!$this->plan_expire_time) {
            return false;
        }
        return $this->plan_expire_time > time();
    }

    /**
     * 获取关联的套餐
     */
    public function plan()
    {
        return $this->belongsTo(HdPlan::class, 'plan_id');
    }
}

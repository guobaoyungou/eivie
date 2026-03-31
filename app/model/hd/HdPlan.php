<?php
declare(strict_types=1);
namespace app\model\hd;
use think\Model;

class HdPlan extends Model
{
    protected $name = 'hd_plan';
    protected $autoWriteTimestamp = false;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    /**
     * 检查功能是否在套餐中
     */
    public function hasFeature(string $featureCode): bool
    {
        $features = $this->features ? explode(',', $this->features) : [];
        return in_array($featureCode, $features);
    }
}

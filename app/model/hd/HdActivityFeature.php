<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-活动功能配置模型
 */
class HdActivityFeature extends Model
{
    protected $name = 'hd_activity_feature';
    protected $autoWriteTimestamp = false;

    /**
     * 获取 config JSON
     */
    public function getConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 所属活动
     */
    public function activity()
    {
        return $this->belongsTo(HdActivity::class, 'activity_id');
    }
}

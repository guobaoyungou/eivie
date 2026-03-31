<?php
declare(strict_types=1);
namespace app\model\hd;
use think\Model;

class HdKaimuConfig extends Model
{
    protected $name = 'hd_kaimu_config';
    protected $autoWriteTimestamp = false;

    public function getConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}

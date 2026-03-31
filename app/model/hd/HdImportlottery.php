<?php
declare(strict_types=1);
namespace app\model\hd;
use think\Model;

class HdImportlottery extends Model
{
    protected $name = 'hd_importlottery';
    protected $autoWriteTimestamp = false;

    public function getExtraAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setExtraAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}

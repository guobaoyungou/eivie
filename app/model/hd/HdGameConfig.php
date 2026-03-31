<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

class HdGameConfig extends Model
{
    protected $name = 'hd_game_config';
    protected $autoWriteTimestamp = false;

    public function getConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function records()
    {
        return $this->hasMany(HdGameRecord::class, 'game_config_id');
    }
}

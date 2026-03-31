<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动-活动模型
 */
class HdActivity extends Model
{
    protected $name = 'hd_activity';
    protected $autoWriteTimestamp = false;

    // 状态常量
    const STATUS_NOT_STARTED = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_ENDED = 3;

    /**
     * 功能配置关联
     */
    public function features()
    {
        return $this->hasMany(HdActivityFeature::class, 'activity_id');
    }

    /**
     * 参与者关联
     */
    public function participants()
    {
        return $this->hasMany(HdParticipant::class, 'activity_id');
    }

    /**
     * 奖品关联
     */
    public function prizes()
    {
        return $this->hasMany(HdPrize::class, 'activity_id');
    }

    /**
     * 生成唯一访问码
     */
    public static function generateAccessCode(): string
    {
        do {
            $code = strtolower(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        } while (self::where('access_code', $code)->find());
        return $code;
    }

    /**
     * 获取 screen_config JSON
     */
    public function getScreenConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置 screen_config JSON
     */
    public function setScreenConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}

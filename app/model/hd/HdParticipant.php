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

    // 管理员标识
    const ROLE_NORMAL = 0;
    const ROLE_ADMIN = 1;

    // 核销员标识
    const VERIFIER_NO = 0;
    const VERIFIER_YES = 1;

    /**
     * custom_data JSON 自动序列化
     */
    public function getCustomDataAttr($value)
    {
        if (empty($value)) return [];
        if (is_array($value)) return $value;
        return json_decode($value, true) ?: [];
    }

    public function setCustomDataAttr($value)
    {
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return $value;
    }

    /**
     * 所属活动
     */
    public function activity()
    {
        return $this->belongsTo(HdActivity::class, 'activity_id');
    }

    /**
     * 是否为管理员
     */
    public function isAdmin(): bool
    {
        return (int)$this->is_admin === self::ROLE_ADMIN;
    }

    /**
     * 是否为核销员
     */
    public function isVerifier(): bool
    {
        return (int)$this->is_verifier === self::VERIFIER_YES;
    }
}

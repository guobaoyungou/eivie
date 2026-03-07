<?php

namespace app\model;

use think\Model;

/**
 * API使用授权模型
 * Class ApiAuthorization
 * @package app\model
 */
class ApiAuthorization extends Model
{
    // 设置表名
    protected $name = 'api_authorization';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'api_config_id' => 'integer',
        'grantee_aid' => 'integer',
        'grantee_bid' => 'integer',
        'grantee_mdid' => 'integer',
        'auth_type' => 'integer',
        'quota_daily' => 'integer',
        'quota_monthly' => 'integer',
        'is_active' => 'integer',
        'expire_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 授权类型常量
    const AUTH_FREE = 1;  // 免费授权
    const AUTH_PAID = 2;  // 付费授权
    
    /**
     * 关联API配置
     */
    public function apiConfig()
    {
        return $this->hasOne(ApiConfig::class, 'id', 'api_config_id');
    }
    
    /**
     * 获取激活状态文本
     */
    public function getIsActiveTextAttr($value, $data)
    {
        $status = [0 => '停用', 1 => '启用'];
        return $status[$data['is_active']] ?? '未知';
    }
    
    /**
     * 获取授权类型文本
     */
    public function getAuthTypeTextAttr($value, $data)
    {
        $types = [
            self::AUTH_FREE => '免费授权',
            self::AUTH_PAID => '付费授权'
        ];
        return $types[$data['auth_type']] ?? '未知';
    }
    
    /**
     * 获取过期状态
     */
    public function getIsExpiredAttr($value, $data)
    {
        if ($data['expire_time'] == 0) {
            return false; // 永久有效
        }
        
        return $data['expire_time'] < time();
    }
    
    /**
     * 获取过期时间文本
     */
    public function getExpireTimeTextAttr($value, $data)
    {
        if ($data['expire_time'] == 0) {
            return '永久有效';
        }
        
        return date('Y-m-d H:i:s', $data['expire_time']);
    }
    
    /**
     * 搜索器：API配置ID
     */
    public function searchApiConfigIdAttr($query, $value)
    {
        if ($value) {
            $query->where('api_config_id', $value);
        }
    }
    
    /**
     * 搜索器：被授权平台ID
     */
    public function searchGranteeAidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('grantee_aid', $value);
        }
    }
    
    /**
     * 搜索器：被授权商家ID
     */
    public function searchGranteeBidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('grantee_bid', $value);
        }
    }
    
    /**
     * 搜索器：被授权门店ID
     */
    public function searchGranteeMdidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('grantee_mdid', $value);
        }
    }
    
    /**
     * 搜索器：授权类型
     */
    public function searchAuthTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('auth_type', $value);
        }
    }
    
    /**
     * 搜索器：激活状态
     */
    public function searchIsActiveAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_active', $value);
        }
    }
    
    /**
     * 搜索器：未过期
     */
    public function searchNotExpiredAttr($query, $value)
    {
        if ($value) {
            $query->where(function($q) {
                $q->where('expire_time', 0)
                  ->whereOr('expire_time', '>', time());
            });
        }
    }
    
    /**
     * 检查授权是否有效
     */
    public function isValid()
    {
        // 未启用
        if ($this->is_active != 1) {
            return false;
        }
        
        // 已过期
        if ($this->expire_time > 0 && $this->expire_time < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查是否还有额度
     */
    public function hasQuota($period = 'daily')
    {
        if (!$this->isValid()) {
            return false;
        }
        
        // 获取已使用额度
        $usedQuota = $this->getUsedQuota($period);
        
        // 检查额度限制
        if ($period == 'daily' && $this->quota_daily > 0) {
            return $usedQuota < $this->quota_daily;
        }
        
        if ($period == 'monthly' && $this->quota_monthly > 0) {
            return $usedQuota < $this->quota_monthly;
        }
        
        // 没有额度限制，默认有额度
        return true;
    }
    
    /**
     * 获取已使用额度
     */
    public function getUsedQuota($period = 'daily')
    {
        $startTime = 0;
        $endTime = time();
        
        if ($period == 'daily') {
            $startTime = strtotime('today');
        } elseif ($period == 'monthly') {
            $startTime = strtotime('first day of this month 00:00:00');
        }
        
        $used = ApiCallLog::where('api_config_id', $this->api_config_id)
            ->where('caller_aid', $this->grantee_aid)
            ->where('caller_bid', $this->grantee_bid)
            ->where('caller_mdid', $this->grantee_mdid)
            ->where('call_time', '>=', $startTime)
            ->where('call_time', '<=', $endTime)
            ->where('is_success', 1)
            ->count();
            
        return $used;
    }
    
    /**
     * 获取剩余额度
     */
    public function getRemainingQuota($period = 'daily')
    {
        if ($period == 'daily' && $this->quota_daily > 0) {
            $used = $this->getUsedQuota('daily');
            return max(0, $this->quota_daily - $used);
        }
        
        if ($period == 'monthly' && $this->quota_monthly > 0) {
            $used = $this->getUsedQuota('monthly');
            return max(0, $this->quota_monthly - $used);
        }
        
        return -1; // 表示无限制
    }
    
    /**
     * 授权API访问
     */
    public static function grantAccess($apiConfigId, $granteeAid, $granteeBid = 0, $granteeMdid = 0, $options = [])
    {
        // 检查是否已存在授权
        $auth = self::where('api_config_id', $apiConfigId)
            ->where('grantee_aid', $granteeAid)
            ->where('grantee_bid', $granteeBid)
            ->where('grantee_mdid', $granteeMdid)
            ->find();
        
        if ($auth) {
            // 更新现有授权
            $auth->auth_type = $options['auth_type'] ?? self::AUTH_PAID;
            $auth->quota_daily = $options['quota_daily'] ?? 0;
            $auth->quota_monthly = $options['quota_monthly'] ?? 0;
            $auth->is_active = $options['is_active'] ?? 1;
            $auth->expire_time = $options['expire_time'] ?? 0;
            $auth->save();
        } else {
            // 创建新授权
            $auth = new self();
            $auth->api_config_id = $apiConfigId;
            $auth->grantee_aid = $granteeAid;
            $auth->grantee_bid = $granteeBid;
            $auth->grantee_mdid = $granteeMdid;
            $auth->auth_type = $options['auth_type'] ?? self::AUTH_PAID;
            $auth->quota_daily = $options['quota_daily'] ?? 0;
            $auth->quota_monthly = $options['quota_monthly'] ?? 0;
            $auth->is_active = $options['is_active'] ?? 1;
            $auth->expire_time = $options['expire_time'] ?? 0;
            $auth->save();
        }
        
        return $auth;
    }
    
    /**
     * 撤销授权
     */
    public static function revokeAccess($apiConfigId, $granteeAid, $granteeBid = 0, $granteeMdid = 0)
    {
        $auth = self::where('api_config_id', $apiConfigId)
            ->where('grantee_aid', $granteeAid)
            ->where('grantee_bid', $granteeBid)
            ->where('grantee_mdid', $granteeMdid)
            ->find();
        
        if ($auth) {
            $auth->is_active = 0;
            $auth->save();
            return true;
        }
        
        return false;
    }
    
    /**
     * 清理过期授权
     */
    public static function cleanExpiredAuths()
    {
        try {
            $count = self::where('expire_time', '>', 0)
                ->where('expire_time', '<', time())
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
            return $count;
        } catch (\Exception $e) {
            trace('清理过期授权失败: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
}

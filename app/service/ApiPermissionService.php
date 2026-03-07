<?php

namespace app\service;

use app\model\ApiConfig;
use app\model\ApiAuthorization;
use think\facade\Log;

/**
 * API权限验证服务
 * Class ApiPermissionService
 * @package app\service
 */
class ApiPermissionService
{
    /**
     * 检查API访问权限
     * 
     * @param string $apiCode API代码
     * @param int $aid 调用者平台ID
     * @param int $bid 调用者商家ID
     * @param int $mdid 调用者门店ID
     * @param int $uid 调用者用户ID
     * @return array ['allowed' => bool, 'message' => string, 'api' => ApiConfig|null]
     */
    public function checkAccess($apiCode, $aid, $bid, $mdid, $uid)
    {
        // 1. 查找API配置
        $api = ApiConfig::where('api_code', $apiCode)->find();
        
        if (!$api) {
            return [
                'allowed' => false,
                'message' => 'API不存在',
                'api' => null
            ];
        }
        
        // 2. 检查API是否启用
        if ($api->is_active != 1) {
            return [
                'allowed' => false,
                'message' => 'API已禁用',
                'api' => $api
            ];
        }
        
        // 3. 检查作用域和权限
        $accessCheck = $api->canAccess($aid, $bid, $mdid, $uid);
        
        if (!$accessCheck) {
            return [
                'allowed' => false,
                'message' => '无访问权限',
                'api' => $api
            ];
        }
        
        // 4. 如果是付费公开API，检查授权额度
        if ($api->scope_type == ApiConfig::SCOPE_PAID_PUBLIC) {
            $auth = ApiAuthorization::where('api_config_id', $api->id)
                ->where('grantee_aid', $aid)
                ->where('grantee_bid', $bid)
                ->where('grantee_mdid', $mdid)
                ->where('is_active', 1)
                ->find();
            
            if ($auth) {
                // 检查每日额度
                if (!$auth->hasQuota('daily')) {
                    return [
                        'allowed' => false,
                        'message' => '今日调用额度已用完',
                        'api' => $api
                    ];
                }
                
                // 检查每月额度
                if (!$auth->hasQuota('monthly')) {
                    return [
                        'allowed' => false,
                        'message' => '本月调用额度已用完',
                        'api' => $api
                    ];
                }
            }
        }
        
        return [
            'allowed' => true,
            'message' => '权限验证通过',
            'api' => $api
        ];
    }
    
    /**
     * 检查是否是API所有者
     */
    public function isOwner($apiId, $aid, $bid, $mdid)
    {
        $api = ApiConfig::find($apiId);
        if (!$api) {
            return false;
        }
        
        return $api->aid == $aid && $api->bid == $bid && $api->mdid == $mdid;
    }
    
    /**
     * 检查是否是超级管理员
     */
    public function isAdmin($uid)
    {
        // 假设超级管理员的uid为1，实际应该从配置或权限表获取
        return $uid == 1;
    }
    
    /**
     * 检查是否可以管理API配置
     */
    public function canManageApi($apiId, $aid, $bid, $mdid, $uid)
    {
        // 超级管理员可以管理所有API
        if ($this->isAdmin($uid)) {
            return true;
        }
        
        // 所有者可以管理自己的API
        if ($this->isOwner($apiId, $aid, $bid, $mdid)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取用户可管理的API列表权限范围
     */
    public function getManageableScope($aid, $bid, $mdid, $uid)
    {
        if ($this->isAdmin($uid)) {
            // 超级管理员可以管理所有API
            return [
                'type' => 'all',
                'aid' => 0,
                'bid' => 0,
                'mdid' => 0
            ];
        }
        
        // 普通用户只能管理自己创建的API
        return [
            'type' => 'own',
            'aid' => $aid,
            'bid' => $bid,
            'mdid' => $mdid
        ];
    }
    
    /**
     * 批量检查API访问权限
     */
    public function batchCheckAccess($apiCodes, $aid, $bid, $mdid, $uid)
    {
        $results = [];
        
        foreach ($apiCodes as $apiCode) {
            $results[$apiCode] = $this->checkAccess($apiCode, $aid, $bid, $mdid, $uid);
        }
        
        return $results;
    }
}

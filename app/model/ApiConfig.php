<?php

namespace app\model;

use think\Model;

/**
 * API配置模型
 * Class ApiConfig
 * @package app\model
 */
class ApiConfig extends Model
{
    // 设置表名
    protected $name = 'api_config';
    
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
        'aid' => 'integer',
        'bid' => 'integer',
        'mdid' => 'integer',
        'model_id' => 'integer',
        'is_system' => 'integer',
        'owner_uid' => 'integer',
        'scope_type' => 'integer',
        'is_active' => 'integer',
        'sort' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // JSON字段
    protected $json = ['config_json'];
    protected $jsonAssoc = true;
    
    // 作用域类型常量
    const SCOPE_GLOBAL = 1;      // 全局公开
    const SCOPE_PRIVATE = 2;     // 仅自用
    const SCOPE_PAID_PUBLIC = 3; // 付费公开
    
    /**
     * 关联AI模型实例
     */
    public function modelInstance()
    {
        return $this->hasOne(AiModelInstance::class, 'id', 'model_id');
    }
    
    /**
     * 关联计费规则
     */
    public function pricing()
    {
        return $this->hasOne(ApiPricing::class, 'api_config_id', 'id')
            ->where('is_active', 1);
    }
    
    /**
     * 关联授权记录
     */
    public function authorizations()
    {
        return $this->hasMany(ApiAuthorization::class, 'api_config_id', 'id');
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
     * 获取系统预置文本
     */
    public function getIsSystemTextAttr($value, $data)
    {
        $status = [0 => '自定义', 1 => '系统预置'];
        return $status[$data['is_system']] ?? '未知';
    }
    
    /**
     * 获取作用域文本
     */
    public function getScopeTypeTextAttr($value, $data)
    {
        $scopes = [
            self::SCOPE_GLOBAL => '全局公开',
            self::SCOPE_PRIVATE => '仅自用',
            self::SCOPE_PAID_PUBLIC => '付费公开'
        ];
        return $scopes[$data['scope_type']] ?? '未知';
    }
    
    /**
     * 搜索器：API代码
     */
    public function searchApiCodeAttr($query, $value)
    {
        if ($value) {
            $query->where('api_code', 'like', "%{$value}%");
        }
    }
    
    /**
     * 搜索器：API名称
     */
    public function searchApiNameAttr($query, $value)
    {
        if ($value) {
            $query->where('api_name', 'like', "%{$value}%");
        }
    }
    
    /**
     * 搜索器：API类型
     */
    public function searchApiTypeAttr($query, $value)
    {
        if ($value) {
            $query->where('api_type', $value);
        }
    }
    
    /**
     * 搜索器：服务提供商
     */
    public function searchProviderAttr($query, $value)
    {
        if ($value) {
            $query->where('provider', $value);
        }
    }
    
    /**
     * 搜索器：作用域类型
     */
    public function searchScopeTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('scope_type', $value);
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
     * 搜索器：是否系统预置
     */
    public function searchIsSystemAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_system', $value);
        }
    }
    
    /**
     * 搜索器：平台ID
     */
    public function searchAidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('aid', $value);
        }
    }
    
    /**
     * 搜索器：商家ID
     */
    public function searchBidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('bid', $value);
        }
    }
    
    /**
     * 搜索器：门店ID
     */
    public function searchMdidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('mdid', $value);
        }
    }
    
    /**
     * 搜索器：关联模型ID
     */
    public function searchModelIdAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('model_id', $value);
        }
    }
    
    /**
     * API密钥加密存储
     */
    public function setApiKeyAttr($value)
    {
        return $this->encryptApiKey($value);
    }
    
    /**
     * API密钥解密读取
     */
    public function getApiKeyAttr($value)
    {
        return $this->decryptApiKey($value);
    }
    
    /**
     * API Secret加密存储
     */
    public function setApiSecretAttr($value)
    {
        return $value ? $this->encryptApiKey($value) : null;
    }
    
    /**
     * API Secret解密读取
     */
    public function getApiSecretAttr($value)
    {
        return $value ? $this->decryptApiKey($value) : null;
    }
    
    /**
     * 加密API密钥
     */
    private function encryptApiKey($value)
    {
        if (empty($value)) {
            return '';
        }
        
        // 使用AES-256-CBC加密
        $config = config('app');
        $key = $config['authkey'] ?? 'default_encryption_key';
        $iv = substr(md5($key), 0, 16);
        
        return base64_encode(openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv));
    }
    
    /**
     * 解密API密钥
     */
    private function decryptApiKey($value)
    {
        if (empty($value)) {
            return '';
        }
        
        $config = config('app');
        $key = $config['authkey'] ?? 'default_encryption_key';
        $iv = substr(md5($key), 0, 16);
        
        return openssl_decrypt(base64_decode($value), 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * 获取API完整配置（含模型、计费规则）
     */
    public static function getFullConfig($apiCode, $aid = 0, $bid = 0, $mdid = 0)
    {
        $api = self::where('api_code', $apiCode)
            ->where('is_active', 1)
            ->find();
            
        if (!$api) {
            return null;
        }
        
        // 加载关联的AI模型配置
        if ($api->model_id > 0) {
            $api->model_instance;
        }
        
        // 加载计费规则
        $api->pricing;
        
        return $api;
    }
    
    /**
     * 检查API是否可被访问
     */
    public function canAccess($aid, $bid, $mdid, $uid)
    {
        // 如果是所有者，允许访问
        if ($this->aid == $aid && $this->bid == $bid && $this->mdid == $mdid) {
            return true;
        }
        
        // 如果是系统预置且全局公开，允许访问
        if ($this->is_system == 1 && $this->scope_type == self::SCOPE_GLOBAL) {
            return true;
        }
        
        // 如果是仅自用，检查是否是下级组织
        if ($this->scope_type == self::SCOPE_PRIVATE) {
            return $this->isSubOrganization($aid, $bid, $mdid);
        }
        
        // 如果是付费公开，检查授权
        if ($this->scope_type == self::SCOPE_PAID_PUBLIC) {
            return $this->hasAuthorization($aid, $bid, $mdid);
        }
        
        return false;
    }
    
    /**
     * 检查是否是下级组织
     */
    private function isSubOrganization($aid, $bid, $mdid)
    {
        // 如果API所属平台级，则所有该平台下的商家和门店可访问
        if ($this->aid == $aid && $this->bid == 0 && $this->mdid == 0) {
            return true;
        }
        
        // 如果API所属商家级，则该商家下的所有门店可访问
        if ($this->aid == $aid && $this->bid == $bid && $this->mdid == 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检查是否有授权
     */
    private function hasAuthorization($aid, $bid, $mdid)
    {
        $auth = ApiAuthorization::where('api_config_id', $this->id)
            ->where('grantee_aid', $aid)
            ->where('grantee_bid', $bid)
            ->where('grantee_mdid', $mdid)
            ->where('is_active', 1)
            ->where(function($query) {
                $query->where('expire_time', 0)
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
            
        return $auth ? true : false;
    }
}

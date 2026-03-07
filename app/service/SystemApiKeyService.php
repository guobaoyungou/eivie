<?php
/**
 * 系统API Key配置服务类
 * 提供API Key的增删改查、加密解密、脱敏展示等功能
 */
namespace app\service;

use think\facade\Db;
use think\facade\Config;

class SystemApiKeyService
{
    /**
     * 加密密钥（使用系统配置的authkey）
     */
    private function getEncryptKey()
    {
        $config = include(ROOT_PATH . 'config.php');
        return $config['authkey'] ?? 'default_secret_key_32bytes!!';
    }

    /**
     * 获取加密IV
     */
    private function getEncryptIv()
    {
        return substr(md5($this->getEncryptKey()), 0, 16);
    }

    /**
     * 加密API Key
     * @param string $plainText 明文
     * @return string 加密后的密文（base64编码）
     */
    public function encryptApiKey($plainText)
    {
        if (empty($plainText)) {
            return '';
        }
        $key = $this->getEncryptKey();
        $iv = $this->getEncryptIv();
        $encrypted = openssl_encrypt($plainText, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($encrypted);
    }

    /**
     * 解密API Key
     * @param string $cipherText 密文（base64编码）
     * @return string 解密后的明文
     */
    public function decryptApiKey($cipherText)
    {
        if (empty($cipherText)) {
            return '';
        }
        $key = $this->getEncryptKey();
        $iv = $this->getEncryptIv();
        $decoded = base64_decode($cipherText);
        return openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * 脱敏API Key展示
     * @param string $apiKey 原始API Key
     * @return string 脱敏后的API Key
     */
    public function maskApiKey($apiKey)
    {
        if (empty($apiKey)) {
            return '';
        }
        $length = strlen($apiKey);
        if ($length <= 8) {
            return '****';
        }
        return substr($apiKey, 0, 4) . '****' . substr($apiKey, -4);
    }

    /**
     * 获取配置列表
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页条数
     * @param string $order 排序
     * @return array
     */
    public function getList($where = [], $page = 1, $limit = 20, $order = 'config.sort asc, config.id desc')
    {
        $query = Db::name('system_api_key')->alias('config')
            ->leftJoin('model_provider provider', 'provider.id = config.provider_id')
            ->where($where);
        
        $count = $query->count();
        
        $data = Db::name('system_api_key')->alias('config')
            ->field('config.*, provider.provider_name, provider.provider_code as provider_code_ref, provider.logo as provider_logo')
            ->leftJoin('model_provider provider', 'provider.id = config.provider_id')
            ->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();
        
        foreach ($data as &$item) {
            // 解密并脱敏API Key展示
            $decryptedKey = $this->decryptApiKey($item['api_key']);
            $item['api_key_masked'] = $this->maskApiKey($decryptedKey);
            $item['api_key_plain'] = $decryptedKey; // 用于编辑判断，不直接展示
            
            // 时间格式化
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
            $item['update_time_text'] = $item['update_time'] ? date('Y-m-d H:i', $item['update_time']) : '';
            $item['last_used_time_text'] = ($item['last_used_time'] ?? 0) ? date('Y-m-d H:i', $item['last_used_time']) : '-';
            
            // 并发显示
            $maxConcurrency = $item['max_concurrency'] ?? 5;
            $currentConcurrency = $item['current_concurrency'] ?? 0;
            $item['concurrency_text'] = $currentConcurrency . '/' . $maxConcurrency;
            
            // 成功率计算
            $totalCalls = $item['total_calls'] ?? 0;
            $failCalls = $item['fail_calls'] ?? 0;
            if ($totalCalls > 0) {
                $successRate = round(($totalCalls - $failCalls) / $totalCalls * 100, 1);
                $item['success_rate'] = $successRate . '%';
            } else {
                $item['success_rate'] = '-';
            }
            $item['call_stats'] = $totalCalls . '/' . $failCalls;
            
            // 获取该供应商下的模型数量
            $item['model_count'] = Db::name('model_info')->where('provider_id', $item['provider_id'])->count();
        }
        
        return ['count' => $count, 'data' => $data];
    }

    /**
     * 获取配置详情
     * @param int $id 配置ID
     * @return array|null
     */
    public function getDetail($id)
    {
        $info = Db::name('system_api_key')->alias('config')
            ->field('config.*, provider.provider_name, provider.provider_code as provider_code_ref, provider.auth_config')
            ->leftJoin('model_provider provider', 'provider.id = config.provider_id')
            ->where('config.id', $id)
            ->find();
        
        if ($info) {
            // 解密API Key（编辑时需要）
            $info['api_key_decrypted'] = $this->decryptApiKey($info['api_key']);
            $info['api_secret_decrypted'] = $this->decryptApiKey($info['api_secret']);
            
            // 解析extra_config
            if (isset($info['extra_config']) && is_string($info['extra_config'])) {
                $info['extra_config'] = json_decode($info['extra_config'], true) ?: [];
            }
            
            // 解析auth_config
            if (isset($info['auth_config']) && is_string($info['auth_config'])) {
                $info['auth_config'] = json_decode($info['auth_config'], true) ?: [];
            }
        }
        
        return $info;
    }

    /**
     * 保存配置
     * @param array $data 配置数据
     * @return array
     */
    public function save($data)
    {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $providerId = intval($data['provider_id'] ?? 0);
        
        // 验证供应商
        if ($providerId <= 0) {
            return ['status' => 0, 'msg' => '请选择供应商'];
        }
        
        $provider = Db::name('model_provider')->where('id', $providerId)->find();
        if (!$provider) {
            return ['status' => 0, 'msg' => '所选供应商不存在'];
        }
        
        // 验证API Key
        $apiKey = trim($data['api_key'] ?? '');
        if (empty($apiKey)) {
            return ['status' => 0, 'msg' => '请填写API Key'];
        }
        
        // API Key格式验证（非空且长度≥20）
        if (strlen($apiKey) < 20) {
            return ['status' => 0, 'msg' => 'API Key格式不正确，长度应不少于20个字符'];
        }
        
        // 检查Key唯一性（不允许重复添加相同的API Key）
        $encryptedKey = $this->encryptApiKey($apiKey);
        $exists = Db::name('system_api_key')
            ->where('api_key', $encryptedKey)
            ->where('id', '<>', $id)
            ->find();
        if ($exists) {
            return ['status' => 0, 'msg' => '该API Key已存在，请勿重复添加'];
        }
        
        // 处理API Secret
        $apiSecret = trim($data['api_secret'] ?? '');
        
        // 自动生成配置名称
        $configName = trim($data['config_name'] ?? '');
        if (empty($configName)) {
            // 查询该供应商已有多少个Key，自动编号
            $keyCount = Db::name('system_api_key')
                ->where('provider_id', $providerId)
                ->where('id', '<>', $id)
                ->count();
            $configName = $provider['provider_name'] . '-Key' . ($keyCount + 1);
        }
        
        // 准备保存数据
        $saveData = [
            'provider_id' => $providerId,
            'provider_code' => $provider['provider_code'],
            'api_key' => $encryptedKey,
            'api_secret' => !empty($apiSecret) ? $this->encryptApiKey($apiSecret) : '',
            'config_name' => $configName,
            'max_concurrency' => max(1, min(100, intval($data['max_concurrency'] ?? 5))),
            'weight' => max(1, min(100, intval($data['weight'] ?? 100))),
            'remark' => trim($data['remark'] ?? ''),
            'is_active' => intval($data['is_active'] ?? 1),
            'sort' => intval($data['sort'] ?? 0),
            'update_time' => time(),
        ];
        
        // 处理扩展配置
        if (isset($data['extra_config'])) {
            $extraConfig = $data['extra_config'];
            if (is_string($extraConfig)) {
                $decoded = json_decode($extraConfig, true);
                $saveData['extra_config'] = $decoded !== null ? $extraConfig : json_encode([], JSON_UNESCAPED_UNICODE);
            } else {
                $saveData['extra_config'] = json_encode($extraConfig, JSON_UNESCAPED_UNICODE);
            }
        }
        
        if ($id > 0) {
            // 更新
            Db::name('system_api_key')->where('id', $id)->update($saveData);
        } else {
            // 新增
            $saveData['create_time'] = time();
            $saveData['current_concurrency'] = 0;
            $saveData['total_calls'] = 0;
            $saveData['fail_calls'] = 0;
            $id = Db::name('system_api_key')->insertGetId($saveData);
        }
        
        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 删除配置
     * @param int $id 配置ID
     * @return array
     */
    public function delete($id)
    {
        $info = Db::name('system_api_key')->where('id', $id)->find();
        if (!$info) {
            return ['status' => 0, 'msg' => '配置不存在'];
        }
        
        Db::name('system_api_key')->where('id', $id)->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }

    /**
     * 更新配置状态
     * @param int $id 配置ID
     * @param int $status 状态值
     * @return array
     */
    public function updateStatus($id, $status)
    {
        Db::name('system_api_key')->where('id', $id)->update([
            'is_active' => intval($status),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }

    /**
     * 获取指定供应商的有效配置
     * @deprecated 请使用 SystemApiKeyPoolService::acquireKey() 代替
     * @param string $providerCode 供应商标识
     * @return array|null
     */
    public function getActiveConfigByProvider($providerCode)
    {
        // 获取该供应商所有启用的Key，按权重排序
        $config = Db::name('system_api_key')
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->order('weight desc, sort asc')
            ->find();
        
        if ($config) {
            $config['api_key_decrypted'] = $this->decryptApiKey($config['api_key']);
            $config['api_secret_decrypted'] = $this->decryptApiKey($config['api_secret']);
            
            if (isset($config['extra_config']) && is_string($config['extra_config'])) {
                $config['extra_config'] = json_decode($config['extra_config'], true) ?: [];
            }
        }
        
        return $config;
    }

    /**
     * 获取指定供应商的所有启用配置
     * @param string $providerCode 供应商标识
     * @return array
     */
    public function getActiveConfigsByProvider($providerCode)
    {
        $configs = Db::name('system_api_key')
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->order('weight desc, sort asc')
            ->select()->toArray();
        
        foreach ($configs as &$config) {
            $config['api_key_decrypted'] = $this->decryptApiKey($config['api_key']);
            $config['api_secret_decrypted'] = $this->decryptApiKey($config['api_secret']);
            
            if (isset($config['extra_config']) && is_string($config['extra_config'])) {
                $config['extra_config'] = json_decode($config['extra_config'], true) ?: [];
            }
        }
        
        return $configs;
    }

    /**
     * 获取所有启用的供应商（用于下拉选择）
     * 现在支持同一供应商多个Key，所以不再排除已配置的
     * @param int $excludeConfigId 排除的配置ID（当前无用，保留兼容）
     * @return array
     */
    public function getAvailableProviders($excludeConfigId = 0)
    {
        // 获取所有启用的供应商（支持多Key，不再排除已配置的）
        $providers = Db::name('model_provider')
            ->field('id, provider_code, provider_name, logo, auth_config')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->select()->toArray();
        
        foreach ($providers as &$provider) {
            if (isset($provider['auth_config']) && is_string($provider['auth_config'])) {
                $provider['auth_config'] = json_decode($provider['auth_config'], true) ?: [];
            }
            // 添加当前供应商的Key数量统计
            $provider['key_count'] = Db::name('system_api_key')
                ->where('provider_id', $provider['id'])
                ->count();
        }
        
        return $providers;
    }

    /**
     * 获取供应商的认证字段配置
     * @param int $providerId 供应商ID
     * @return array
     */
    public function getProviderAuthFields($providerId)
    {
        $provider = Db::name('model_provider')
            ->field('id, provider_code, provider_name, auth_config')
            ->where('id', $providerId)
            ->find();
        
        if (!$provider) {
            return [];
        }
        
        if (isset($provider['auth_config']) && is_string($provider['auth_config'])) {
            $provider['auth_config'] = json_decode($provider['auth_config'], true) ?: [];
        }
        
        return $provider;
    }

    /**
     * 测试API连接（本地格式验证）
     * @param int $id 配置ID
     * @return array
     */
    public function testConnection($id)
    {
        $config = $this->getDetail($id);
        if (!$config) {
            return ['status' => 0, 'msg' => '配置不存在'];
        }
        
        $apiKey = $config['api_key_decrypted'];
        
        // 本地格式验证
        if (empty($apiKey)) {
            return ['status' => 0, 'msg' => 'API Key为空'];
        }
        
        if (strlen($apiKey) < 20) {
            return ['status' => 0, 'msg' => 'API Key格式不正确，长度不足'];
        }
        
        // 基本格式检查通过
        return [
            'status' => 1, 
            'msg' => 'API Key格式验证通过，真实有效性将在业务调用时验证',
            'provider' => $config['provider_name']
        ];
    }
}

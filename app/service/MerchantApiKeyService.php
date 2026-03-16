<?php
/**
 * 商家API Key配置服务类
 * 提供商家级别的API Key管理、Key池负载均衡、门店范围控制等功能
 */
namespace app\service;

use think\facade\Db;
use think\facade\Config;

class MerchantApiKeyService
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
     * @param int $bid 商家ID
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页条数
     * @param string $order 排序
     * @return array
     */
    public function getList($bid, $where = [], $page = 1, $limit = 20, $order = 'config.sort asc, config.id desc')
    {
        $where[] = ['config.bid', '=', $bid];
        
        $query = Db::name('merchant_api_key')->alias('config')
            ->leftJoin('model_provider provider', 'provider.id = config.provider_id')
            ->where($where);
        
        $count = $query->count();
        
        $data = Db::name('merchant_api_key')->alias('config')
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
            
            // 时间格式化
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
            $item['update_time_text'] = $item['update_time'] ? date('Y-m-d H:i', $item['update_time']) : '';
            $item['last_used_time_text'] = $item['last_used_time'] ? date('Y-m-d H:i', $item['last_used_time']) : '-';
            
            // 并发显示
            $item['concurrency_text'] = $item['current_concurrency'] . '/' . $item['max_concurrency'];
            
            // 门店范围文本
            if ($item['scope_type'] == 0) {
                $item['scope_text'] = '全部门店';
            } else {
                // 获取关联门店数量
                $mendianCount = Db::name('merchant_api_key_mendian')->where('key_id', $item['id'])->count();
                $item['scope_text'] = "指定{$mendianCount}个门店";
            }
            
            // 成功率
            if ($item['total_calls'] > 0) {
                $successRate = round(($item['total_calls'] - $item['fail_calls']) / $item['total_calls'] * 100, 1);
                $item['success_rate'] = $successRate . '%';
            } else {
                $item['success_rate'] = '-';
            }
        }
        
        return ['count' => $count, 'data' => $data];
    }

    /**
     * 获取配置详情
     * @param int $id 配置ID
     * @param int $bid 商家ID（用于权限验证）
     * @return array|null
     */
    public function getDetail($id, $bid)
    {
        $info = Db::name('merchant_api_key')->alias('config')
            ->field('config.*, provider.provider_name, provider.provider_code as provider_code_ref, provider.auth_config')
            ->leftJoin('model_provider provider', 'provider.id = config.provider_id')
            ->where('config.id', $id)
            ->where('config.bid', $bid)
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
            
            // 获取关联的门店ID列表
            if ($info['scope_type'] == 1) {
                $info['mendian_ids'] = Db::name('merchant_api_key_mendian')
                    ->where('key_id', $id)
                    ->column('mdid');
            } else {
                $info['mendian_ids'] = [];
            }
        }
        
        return $info;
    }

    /**
     * 保存配置
     * @param array $data 配置数据
     * @param int $bid 商家ID
     * @param int $aid 平台ID
     * @return array
     */
    public function save($data, $bid, $aid)
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
        
        // 解析供应商的认证配置
        $authConfig = json_decode($provider['auth_config'] ?? '{}', true);
        $authFields = $authConfig['fields'] ?? [];
        
        // 根据认证字段类型进行验证
        $apiKey = '';
        $apiSecret = '';
        $extraConfigData = [];
        
        if (empty($authFields)) {
            // 默认使用 api_key 验证
            $apiKey = trim($data['api_key'] ?? '');
            if (empty($apiKey)) {
                return ['status' => 0, 'msg' => '请填写API Key'];
            }
            if (strlen($apiKey) < 20) {
                return ['status' => 0, 'msg' => 'API Key格式不正确，长度应不少于20个字符'];
            }
        } else {
            // 根据定义的字段进行验证
            foreach ($authFields as $field) {
                $fieldName = $field['name'] ?? '';
                $fieldLabel = $field['label'] ?? $fieldName;
                $isRequired = $field['required'] ?? false;
                $fieldValue = trim($data[$fieldName] ?? '');
                
                if ($isRequired && empty($fieldValue)) {
                    return ['status' => 0, 'msg' => '请填写' . $fieldLabel];
                }
                
                // 映射到标准字段或额外配置
                if ($fieldName === 'api_key') {
                    $apiKey = $fieldValue;
                } elseif ($fieldName === 'api_secret') {
                    $apiSecret = $fieldValue;
                } else {
                    $extraConfigData[$fieldName] = $fieldValue;
                }
            }
            
            // 如果有 api_key，验证长度
            if (!empty($apiKey) && strlen($apiKey) < 20) {
                return ['status' => 0, 'msg' => 'API Key格式不正确，长度应不少于20个字符'];
            }
        }
        
        // 检查Key唯一性（仅当api_key不为空时）
        if (!empty($apiKey)) {
            $encryptedKey = $this->encryptApiKey($apiKey);
            $exists = Db::name('merchant_api_key')
                ->where('bid', $bid)
                ->where('api_key', $encryptedKey)
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return ['status' => 0, 'msg' => '该API Key已存在，请勿重复添加'];
            }
        } else {
            $encryptedKey = '';
        }
        
        // 准备保存数据
        $saveData = [
            'aid' => $aid,
            'bid' => $bid,
            'provider_id' => $providerId,
            'provider_code' => $provider['provider_code'],
            'api_key' => $encryptedKey,
            'api_secret' => !empty($apiSecret) ? $this->encryptApiKey($apiSecret) : '',
            'config_name' => trim($data['config_name'] ?? '') ?: ($provider['provider_name'] . ' Key'),
            'max_concurrency' => max(1, min(100, intval($data['max_concurrency'] ?? 5))),
            'weight' => max(1, min(100, intval($data['weight'] ?? 100))),
            'scope_type' => intval($data['scope_type'] ?? 0),
            'remark' => trim($data['remark'] ?? ''),
            'is_active' => intval($data['is_active'] ?? 1),
            'sort' => intval($data['sort'] ?? 0),
            'update_time' => time(),
        ];
        
        // 处理扩展配置 - 合并自定义认证字段
        $extraConfig = [];
        if (isset($data['extra_config'])) {
            $existingConfig = $data['extra_config'];
            if (is_string($existingConfig)) {
                $decoded = json_decode($existingConfig, true);
                if ($decoded !== null) {
                    $extraConfig = $decoded;
                }
            } elseif (is_array($existingConfig)) {
                $extraConfig = $existingConfig;
            }
        }
        
        // 将自定义认证字段（非 api_key/api_secret）合并到 extra_config
        if (!empty($extraConfigData)) {
            $extraConfig = array_merge($extraConfig, $extraConfigData);
        }
        
        // 加密敏感字段
        foreach ($extraConfig as $key => $value) {
            if (stripos($key, 'key') !== false || stripos($key, 'secret') !== false || stripos($key, 'token') !== false) {
                if (!empty($value) && strlen($value) > 0) {
                    $extraConfig[$key] = $this->encryptApiKey($value);
                }
            }
        }
        
        $saveData['extra_config'] = json_encode($extraConfig, JSON_UNESCAPED_UNICODE);
        
        Db::startTrans();
        try {
            if ($id > 0) {
                // 更新（验证所有权）
                $existing = Db::name('merchant_api_key')->where('id', $id)->where('bid', $bid)->find();
                if (!$existing) {
                    Db::rollback();
                    return ['status' => 0, 'msg' => '配置不存在或无权限操作'];
                }
                Db::name('merchant_api_key')->where('id', $id)->update($saveData);
            } else {
                // 新增
                $saveData['create_time'] = time();
                $saveData['current_concurrency'] = 0;
                $saveData['total_calls'] = 0;
                $saveData['fail_calls'] = 0;
                $id = Db::name('merchant_api_key')->insertGetId($saveData);
            }
            
            // 处理门店关联
            if ($saveData['scope_type'] == 1) {
                // 删除旧关联
                Db::name('merchant_api_key_mendian')->where('key_id', $id)->delete();
                
                // 添加新关联
                $mendianIds = $data['mendian_ids'] ?? [];
                if (is_string($mendianIds)) {
                    $mendianIds = array_filter(explode(',', $mendianIds));
                }
                if (!empty($mendianIds)) {
                    $insertData = [];
                    foreach ($mendianIds as $mdid) {
                        $insertData[] = [
                            'key_id' => $id,
                            'mdid' => intval($mdid)
                        ];
                    }
                    Db::name('merchant_api_key_mendian')->insertAll($insertData);
                }
            } else {
                // 全部门店，删除所有关联
                Db::name('merchant_api_key_mendian')->where('key_id', $id)->delete();
            }
            
            Db::commit();
            return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
        } catch (\Exception $e) {
            Db::rollback();
            return ['status' => 0, 'msg' => '保存失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除配置
     * @param int $id 配置ID
     * @param int $bid 商家ID
     * @return array
     */
    public function delete($id, $bid)
    {
        $info = Db::name('merchant_api_key')->where('id', $id)->where('bid', $bid)->find();
        if (!$info) {
            return ['status' => 0, 'msg' => '配置不存在或无权限操作'];
        }
        
        Db::startTrans();
        try {
            Db::name('merchant_api_key')->where('id', $id)->delete();
            Db::name('merchant_api_key_mendian')->where('key_id', $id)->delete();
            Db::commit();
            return ['status' => 1, 'msg' => '删除成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['status' => 0, 'msg' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 更新配置状态
     * @param int $id 配置ID
     * @param int $status 状态值
     * @param int $bid 商家ID
     * @return array
     */
    public function updateStatus($id, $status, $bid)
    {
        $info = Db::name('merchant_api_key')->where('id', $id)->where('bid', $bid)->find();
        if (!$info) {
            return ['status' => 0, 'msg' => '配置不存在或无权限操作'];
        }
        
        Db::name('merchant_api_key')->where('id', $id)->update([
            'is_active' => intval($status),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }

    /**
     * 从模型广场获取可选供应商列表
     * @return array
     */
    public function getAvailableProviders()
    {
        $providers = Db::name('model_provider')
            ->field('id, provider_code, provider_name, logo, auth_config')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->select()->toArray();
        
        foreach ($providers as &$provider) {
            if (isset($provider['auth_config']) && is_string($provider['auth_config'])) {
                $provider['auth_config'] = json_decode($provider['auth_config'], true) ?: [];
            }
        }
        
        return $providers;
    }

    /**
     * 获取当前商家的门店列表
     * @param int $bid 商家ID
     * @return array
     */
    public function getMendianList($bid)
    {
        return Db::name('mendian')
            ->field('id, name')
            ->where('bid', $bid)
            ->where('status', 1)
            ->order('id asc')
            ->select()->toArray();
    }

    /**
     * 从Key池中获取一个可用Key
     * @param int $bid 商家ID
     * @param string $providerCode 供应商标识
     * @param int $mdid 当前门店ID（可选）
     * @return array|null 返回解密后的Key配置信息
     */
    public function acquireKey($bid, $providerCode, $mdid = 0)
    {
        // 查询该商家该供应商所有启用的Key
        $keys = Db::name('merchant_api_key')
            ->where('bid', $bid)
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->order('sort asc, weight desc')
            ->select()->toArray();
        
        if (empty($keys)) {
            return null;
        }
        
        // 筛选匹配当前门店的Key
        $availableKeys = [];
        foreach ($keys as $key) {
            // 检查门店范围
            if ($key['scope_type'] == 0) {
                // 全部门店可用
                $availableKeys[] = $key;
            } elseif ($mdid > 0) {
                // 检查是否在关联门店列表中
                $hasAccess = Db::name('merchant_api_key_mendian')
                    ->where('key_id', $key['id'])
                    ->where('mdid', $mdid)
                    ->find();
                if ($hasAccess) {
                    $availableKeys[] = $key;
                }
            }
        }
        
        if (empty($availableKeys)) {
            return null;
        }
        
        // 筛选未达并发上限的Key
        $candidateKeys = [];
        foreach ($availableKeys as $key) {
            if ($key['current_concurrency'] < $key['max_concurrency']) {
                $candidateKeys[] = $key;
            }
        }
        
        if (empty($candidateKeys)) {
            return null; // 所有Key并发已满
        }
        
        // 按加权轮询策略选择Key
        $selectedKey = $this->selectKeyByWeight($candidateKeys);
        
        if (!$selectedKey) {
            return null;
        }
        
        // 原子操作：增加并发计数
        $affected = Db::name('merchant_api_key')
            ->where('id', $selectedKey['id'])
            ->where('current_concurrency', '<', $selectedKey['max_concurrency'])
            ->inc('current_concurrency')
            ->update(['last_used_time' => time()]);
        
        if (!$affected) {
            // 竞争失败，递归重试
            return $this->acquireKey($bid, $providerCode, $mdid);
        }
        
        // 返回解密后的Key信息
        return [
            'id' => $selectedKey['id'],
            'provider_code' => $selectedKey['provider_code'],
            'api_key' => $this->decryptApiKey($selectedKey['api_key']),
            'api_secret' => $this->decryptApiKey($selectedKey['api_secret']),
            'extra_config' => json_decode($selectedKey['extra_config'] ?: '{}', true)
        ];
    }

    /**
     * 按权重选择Key
     * @param array $keys 候选Key列表
     * @return array|null
     */
    private function selectKeyByWeight($keys)
    {
        if (empty($keys)) {
            return null;
        }
        
        // 计算失败率并调整权重
        foreach ($keys as &$key) {
            $effectiveWeight = $key['weight'];
            
            // 如果近期失败率超过50%且调用超过10次，降低权重
            if ($key['total_calls'] >= 10) {
                $failRate = $key['fail_calls'] / $key['total_calls'];
                if ($failRate > 0.5) {
                    $effectiveWeight = max(1, $effectiveWeight * 0.3);
                }
            }
            
            // 并发余量加成
            $remainingCapacity = $key['max_concurrency'] - $key['current_concurrency'];
            $key['effective_weight'] = $effectiveWeight * (1 + $remainingCapacity * 0.1);
        }
        
        // 按有效权重排序
        usort($keys, function($a, $b) {
            return $b['effective_weight'] - $a['effective_weight'];
        });
        
        // 加权随机选择
        $totalWeight = array_sum(array_column($keys, 'effective_weight'));
        $random = mt_rand(1, max(1, (int)$totalWeight));
        $cumulative = 0;
        
        foreach ($keys as $key) {
            $cumulative += $key['effective_weight'];
            if ($random <= $cumulative) {
                return $key;
            }
        }
        
        return $keys[0];
    }

    /**
     * 释放Key的并发占用
     * @param int $keyId 配置ID
     * @return array
     */
    public function releaseKey($keyId)
    {
        $affected = Db::name('merchant_api_key')
            ->where('id', $keyId)
            ->where('current_concurrency', '>', 0)
            ->dec('current_concurrency')
            ->update();
        
        return ['status' => 1, 'msg' => '释放成功'];
    }

    /**
     * 记录调用结果
     * @param int $keyId 配置ID
     * @param bool $success 是否成功
     * @param string $errorMsg 错误信息
     * @return array
     */
    public function recordCallResult($keyId, $success, $errorMsg = '')
    {
        $updateData = [
            'total_calls' => Db::raw('total_calls + 1')
        ];
        
        if (!$success) {
            $updateData['fail_calls'] = Db::raw('fail_calls + 1');
            $updateData['last_error_time'] = time();
            $updateData['last_error_msg'] = mb_substr($errorMsg, 0, 500);
        }
        
        Db::name('merchant_api_key')->where('id', $keyId)->update($updateData);
        
        return ['status' => 1, 'msg' => '记录成功'];
    }

    /**
     * 获取Key池状态概览
     * @param int $bid 商家ID
     * @param string $providerCode 供应商标识（可选）
     * @return array
     */
    public function getPoolStatus($bid, $providerCode = '')
    {
        $where = [['bid', '=', $bid]];
        if ($providerCode) {
            $where[] = ['provider_code', '=', $providerCode];
        }
        
        $keys = Db::name('merchant_api_key')
            ->field('id, config_name, provider_code, max_concurrency, current_concurrency, total_calls, fail_calls, is_active, last_used_time')
            ->where($where)
            ->order('provider_code, sort asc')
            ->select()->toArray();
        
        $summary = [
            'total_keys' => count($keys),
            'active_keys' => 0,
            'total_capacity' => 0,
            'used_capacity' => 0,
            'available_capacity' => 0
        ];
        
        foreach ($keys as &$key) {
            if ($key['is_active']) {
                $summary['active_keys']++;
                $summary['total_capacity'] += $key['max_concurrency'];
                $summary['used_capacity'] += $key['current_concurrency'];
            }
            $key['remaining'] = $key['max_concurrency'] - $key['current_concurrency'];
            $key['last_used_text'] = $key['last_used_time'] ? date('Y-m-d H:i:s', $key['last_used_time']) : '-';
        }
        
        $summary['available_capacity'] = $summary['total_capacity'] - $summary['used_capacity'];
        
        return [
            'summary' => $summary,
            'keys' => $keys
        ];
    }

    /**
     * 检查图像生成API配置
     * @param int $bid 商家ID
     * @param int $mdid 门店ID（可选）
     * @return array
     */
    public function checkImageApiConfig($bid, $mdid = 0)
    {
        return $this->checkMerchantConfig($bid, $mdid, '图像生成');
    }

    /**
     * 检查视频生成API配置
     * @param int $bid 商家ID
     * @param int $mdid 门店ID（可选）
     * @return array
     */
    public function checkVideoApiConfig($bid, $mdid = 0)
    {
        return $this->checkMerchantConfig($bid, $mdid, '视频生成');
    }

    /**
     * 检查商户是否配置了任意可用的API Key
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $funcName 功能名称
     * @return array
     */
    public function checkMerchantConfig($bid, $mdid = 0, $funcName = '')
    {
        // 查询商家所有启用的API Key
        $configs = Db::name('merchant_api_key')
            ->where('bid', $bid)
            ->where('is_active', 1)
            ->select()->toArray();
        
        if (empty($configs)) {
            return [
                'status' => 0,
                'msg' => "请先配置{$funcName}所需的API Key",
                'has_config' => false,
                'redirect_url' => '/MerchantApiKey/index'
            ];
        }
        
        // 如果有门店筛选，检查是否有适用当前门店的Key
        if ($mdid > 0) {
            foreach ($configs as $config) {
                if ($config['scope_type'] == 0) {
                    // 全部门店可用
                    return [
                        'status' => 1,
                        'msg' => '配置有效',
                        'has_config' => true
                    ];
                } else {
                    // 检查是否在关联门店列表中
                    $hasAccess = Db::name('merchant_api_key_mendian')
                        ->where('key_id', $config['id'])
                        ->where('mdid', $mdid)
                        ->find();
                    if ($hasAccess) {
                        return [
                            'status' => 1,
                            'msg' => '配置有效',
                            'has_config' => true
                        ];
                    }
                }
            }
            return [
                'status' => 0,
                'msg' => "当前门店暂无可用的API Key配置，请先配置{$funcName}所需的API Key",
                'has_config' => false,
                'redirect_url' => '/MerchantApiKey/index'
            ];
        }
        
        // 无门店筛选，有启用的Key即可
        return [
            'status' => 1,
            'msg' => '配置有效',
            'has_config' => true
        ];
    }

    /**
     * 获取指定供应商的有效配置
     * @param int $bid 商家ID
     * @param string $providerCode 供应商标识
     * @param int $mdid 门店ID
     * @return array|null
     */
    public function getActiveConfig($bid, $providerCode, $mdid = 0)
    {
        $configs = Db::name('merchant_api_key')
            ->where('bid', $bid)
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->select()->toArray();
        
        foreach ($configs as $config) {
            // 检查门店范围
            if ($config['scope_type'] == 0) {
                return $config;
            } elseif ($mdid > 0) {
                $hasAccess = Db::name('merchant_api_key_mendian')
                    ->where('key_id', $config['id'])
                    ->where('mdid', $mdid)
                    ->find();
                if ($hasAccess) {
                    return $config;
                }
            }
        }
        
        return null;
    }

    /**
     * 测试API连接（本地格式验证）
     * @param int $id 配置ID
     * @param int $bid 商家ID
     * @return array
     */
    public function testConnection($id, $bid)
    {
        $config = $this->getDetail($id, $bid);
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

    /**
     * 重置所有Key的并发计数（服务重启时调用）
     * @param int $bid 商家ID（可选，不传则重置所有）
     */
    public function resetConcurrency($bid = 0)
    {
        $where = [];
        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }
        
        Db::name('merchant_api_key')
            ->where($where)
            ->update(['current_concurrency' => 0]);
    }
}

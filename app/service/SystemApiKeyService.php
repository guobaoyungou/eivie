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
    use \app\common\ApiKeyEncryptTrait;

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
            $exists = Db::name('system_api_key')
                ->where('api_key', $encryptedKey)
                ->where('id', '<>', $id)
                ->find();
            if ($exists) {
                return ['status' => 0, 'msg' => '该API Key已存在，请勿重复添加'];
            }
        } else {
            $encryptedKey = '';
        }
        
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
     * 测试API连接（统一入口，按供应商路由分发）
     * @param int $id 配置ID
     * @return array
     */
    public function testConnection($id)
    {
        $config = $this->getDetail($id);
        if (!$config) {
            return ['status' => 0, 'msg' => '配置不存在'];
        }

        return $this->routeTestByProvider($config);
    }

    /**
     * 根据 provider_code 路由到对应供应商的测试逻辑
     * @param array $config 配置详情（含解密后字段）
     * @return array
     */
    private function routeTestByProvider($config)
    {
        $providerCode = $config['provider_code'] ?? ($config['provider_code_ref'] ?? '');

        switch ($providerCode) {
            case 'ollama':
                return $this->testOllamaConnection($config);
            case 'voxcpm':
                return $this->testVoxCPMConnection($config);
            default:
                return $this->testGenericConnection($config);
        }
    }

    /**
     * 通用供应商测试（原有格式校验逻辑）
     * @param array $config
     * @return array
     */
    private function testGenericConnection($config)
    {
        $apiKey = $config['api_key_decrypted'] ?? '';

        if (empty($apiKey)) {
            return ['status' => 0, 'msg' => 'API Key为空'];
        }

        if (strlen($apiKey) < 20) {
            return ['status' => 0, 'msg' => 'API Key格式不正确，长度不足'];
        }

        return [
            'status'   => 1,
            'msg'      => 'API Key格式验证通过，真实有效性将在业务调用时验证',
            'provider' => $config['provider_name'] ?? ''
        ];
    }

    // ================================================================
    // Ollama 真实连通性测试（三阶段）
    // ================================================================

    /**
     * Ollama 三阶段真实连通性测试
     * @param array $config
     * @return array
     */
    private function testOllamaConnection($config)
    {
        $defaultUrl = Config::get('aivideo.ollama.api_url', 'http://127.0.0.1:11434');
        $apiUrl = $this->resolveOllamaUrl($config['api_key_decrypted'] ?? '', $defaultUrl);

        $result = [
            'status'      => 1,
            'msg'         => '',
            'provider'    => $config['provider_name'] ?? 'Ollama',
            'test_detail' => [
                'service'    => ['passed' => false, 'latency_ms' => 0, 'message' => ''],
                'models'     => ['passed' => false, 'available' => [], 'missing' => []],
                'capability' => ['passed' => false, 'model_tested' => '', 'response_preview' => '', 'latency_ms' => 0, 'tokens' => 0],
            ]
        ];

        // ---------- 阶段 1：服务连通性 ----------
        $phase1 = $this->testOllamaServiceReachability($apiUrl);
        $result['test_detail']['service'] = $phase1;

        if (!$phase1['passed']) {
            $result['status'] = 0;
            $result['msg'] = 'Ollama 服务连接失败，请确认已执行 ollama serve 并监听在 ' . $apiUrl;
            return $result;
        }

        $installedModels = $phase1['_installed_models'] ?? []; // 内部传递

        // ---------- 阶段 2：模型可用性 ----------
        $providerId = $config['provider_id'] ?? 0;
        $phase2 = $this->testOllamaModelAvailability($apiUrl, $installedModels, $providerId);
        $result['test_detail']['models'] = $phase2;

        $availableModels = $phase2['available'] ?? [];

        if (empty($availableModels)) {
            $result['status'] = 0;
            $missingList = implode(', ', $phase2['missing'] ?? []);
            $result['msg'] = 'Ollama 服务正常，但以下模型未安装：' . $missingList . '。请执行 ollama pull <model> 下载';
            return $result;
        }

        // ---------- 阶段 3：真实能力调用 ----------
        $phase3 = $this->testOllamaModelCapabilityAuto($apiUrl, $availableModels, $providerId);
        $result['test_detail']['capability'] = $phase3;

        // 汇总结论
        $totalAvailable = count($availableModels);
        $missingModels = $phase2['missing'] ?? [];

        if ($phase3['passed']) {
            $result['status'] = 1;
            $result['msg'] = '✅ Ollama 服务正常，' . $totalAvailable . ' 个模型可用，已验证模型 ' . $phase3['model_tested'] . ' 对话能力正常（耗时 ' . $phase3['latency_ms'] . 'ms）';
            if (!empty($missingModels)) {
                $result['msg'] .= '。注意：以下模型未安装：' . implode(', ', $missingModels);
            }
        } else {
            // 前两阶段通过但能力测试失败
            $result['status'] = 0;
            $result['msg'] = '模型 ' . $phase3['model_tested'] . ' 推理失败：' . ($phase3['message'] ?? '未知错误') . '。可能内存/显存不足，请检查系统资源';
        }

        return $result;
    }

    /**
     * 解析 Ollama 服务地址
     * @param string $apiKeyValue api_key 字段值（可能是 URL / 空 / ollama_local）
     * @param string $defaultUrl 默认地址
     * @return string 最终服务地址
     */
    private function resolveOllamaUrl($apiKeyValue, $defaultUrl)
    {
        if (empty($apiKeyValue) || $apiKeyValue === 'ollama_local') {
            return $defaultUrl;
        }
        if (filter_var($apiKeyValue, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $apiKeyValue)) {
            return rtrim($apiKeyValue, '/');
        }
        // 非 URL 格式，回退默认地址
        return $defaultUrl;
    }

    /**
     * 阶段1：Ollama 服务连通性检测
     * @param string $apiUrl 服务地址
     * @return array
     */
    private function testOllamaServiceReachability($apiUrl)
    {
        $tagsEndpoint = Config::get('aivideo.ollama.tags_endpoint', '/api/tags');
        $url = $apiUrl . $tagsEndpoint;
        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode !== 200) {
            $errMsg = $curlError ?: ('HTTP状态码: ' . $httpCode);
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '连接失败: ' . $errMsg,
            ];
        }

        $data = json_decode($response, true);
        $models = $data['models'] ?? [];
        $modelNames = array_map(function ($m) {
            return $m['name'] ?? $m['model'] ?? '';
        }, $models);

        return [
            'passed'            => true,
            'latency_ms'        => $latencyMs,
            'message'           => '服务正常，已安装 ' . count($modelNames) . ' 个模型',
            '_installed_models' => $modelNames,  // 内部传递，不直接输出给前端
        ];
    }

    /**
     * 阶段2：Ollama 模型可用性检测
     * @param string $apiUrl
     * @param array $installedModels 已安装的模型名称列表
     * @param int $providerId 供应商 ID
     * @return array
     */
    private function testOllamaModelAvailability($apiUrl, $installedModels, $providerId)
    {
        // 从数据库查询该供应商下所有已注册且启用的模型
        $registeredModels = Db::name('model_info')
            ->field('model_code, model_name, type_id')
            ->where('provider_id', $providerId)
            ->where('is_active', 1)
            ->select()->toArray();

        // 将已安装模型做标准化对比（Ollama tags 返回格式可能带 :latest 后缀）
        $installedNormalized = array_map(function ($name) {
            // 去除 :latest 后缀以便对比
            return preg_replace('/:latest$/', '', $name);
        }, $installedModels);

        $available = [];
        $missing   = [];

        foreach ($registeredModels as $model) {
            $code = $model['model_code'];
            // 直接匹配或去掉 :latest 后匹配
            if (in_array($code, $installedNormalized) || in_array($code, $installedModels)) {
                $available[] = $code;
            } else {
                $missing[] = $code;
            }
        }

        return [
            'passed'    => !empty($available),
            'available' => $available,
            'missing'   => $missing,
        ];
    }

    /**
     * 阶段3：自动选取模型进行真实能力调用
     * 优先选 chat 类型（text_generation/deep_thinking），其次 embedding
     * @param string $apiUrl
     * @param array $availableModels 可用的 model_code 列表
     * @param int $providerId
     * @return array
     */
    private function testOllamaModelCapabilityAuto($apiUrl, $availableModels, $providerId)
    {
        // 查询可用模型的类型信息
        $modelsInfo = Db::name('model_info')
            ->alias('m')
            ->field('m.model_code, t.type_code')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->where('m.provider_id', $providerId)
            ->where('m.is_active', 1)
            ->whereIn('m.model_code', $availableModels)
            ->select()->toArray();

        // 按类型排序：优先 text_generation > deep_thinking > embedding
        $priorityOrder = ['text_generation' => 1, 'deep_thinking' => 2, 'embedding' => 3];
        usort($modelsInfo, function ($a, $b) use ($priorityOrder) {
            $pa = $priorityOrder[$a['type_code']] ?? 99;
            $pb = $priorityOrder[$b['type_code']] ?? 99;
            return $pa - $pb;
        });

        if (empty($modelsInfo)) {
            return [
                'passed'           => false,
                'model_tested'     => '',
                'response_preview' => '',
                'latency_ms'       => 0,
                'tokens'           => 0,
                'message'          => '没有可测试的模型',
            ];
        }

        $target = $modelsInfo[0];
        return $this->testOllamaModelCapability($apiUrl, $target['model_code'], $target['type_code']);
    }

    /**
     * 对单个 Ollama 模型执行真实能力调用
     * @param string $apiUrl
     * @param string $modelCode 模型编码（如 qwen3:8b）
     * @param string $typeCode 模型类型（text_generation / deep_thinking / embedding）
     * @return array
     */
    private function testOllamaModelCapability($apiUrl, $modelCode, $typeCode)
    {
        if ($typeCode === 'embedding') {
            return $this->testOllamaEmbedding($apiUrl, $modelCode);
        }
        return $this->testOllamaChat($apiUrl, $modelCode, $typeCode);
    }

    /**
     * 测试 Ollama Chat 能力（text_generation / deep_thinking）
     * @param string $apiUrl
     * @param string $modelCode
     * @param string $typeCode
     * @return array
     */
    private function testOllamaChat($apiUrl, $modelCode, $typeCode)
    {
        $chatEndpoint = Config::get('aivideo.ollama.chat_endpoint', '/api/chat');
        $url = $apiUrl . $chatEndpoint;

        if ($typeCode === 'deep_thinking') {
            $prompt = '1+1等于几？';
            $temperature = 0.1;
        } else {
            $prompt = '你好，请用一句话介绍你自己';
            $temperature = 0.3;
        }

        $payload = json_encode([
            'model'   => $modelCode,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'stream'  => false,
            'options' => [
                'num_predict'  => 50,
                'temperature'  => $temperature,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode !== 200) {
            $errMsg = $curlError ?: ('HTTP ' . $httpCode);
            return [
                'passed'           => false,
                'model_tested'     => $modelCode,
                'response_preview' => '',
                'latency_ms'       => $latencyMs,
                'tokens'           => 0,
                'message'          => '推理请求失败: ' . $errMsg,
            ];
        }

        $data = json_decode($response, true);
        $content = $data['message']['content'] ?? '';
        $evalCount = $data['eval_count'] ?? 0;

        return [
            'passed'           => !empty($content),
            'model_tested'     => $modelCode,
            'response_preview' => mb_substr($content, 0, 100),
            'latency_ms'       => $latencyMs,
            'tokens'           => intval($evalCount),
            'message'          => !empty($content) ? '对话能力正常' : '模型返回空内容',
        ];
    }

    /**
     * 测试 Ollama Embedding 能力
     * @param string $apiUrl
     * @param string $modelCode
     * @return array
     */
    private function testOllamaEmbedding($apiUrl, $modelCode)
    {
        $embeddingsEndpoint = Config::get('aivideo.ollama.embeddings_endpoint', '/api/embeddings');
        $url = $apiUrl . $embeddingsEndpoint;

        $payload = json_encode([
            'model'  => $modelCode,
            'prompt' => 'API连通性测试',
        ], JSON_UNESCAPED_UNICODE);

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode !== 200) {
            $errMsg = $curlError ?: ('HTTP ' . $httpCode);
            return [
                'passed'           => false,
                'model_tested'     => $modelCode,
                'response_preview' => '',
                'latency_ms'       => $latencyMs,
                'tokens'           => 0,
                'message'          => '向量化请求失败: ' . $errMsg,
            ];
        }

        $data = json_decode($response, true);
        $embedding = $data['embedding'] ?? [];
        $dimensions = count($embedding);

        return [
            'passed'           => $dimensions > 0,
            'model_tested'     => $modelCode,
            'response_preview' => '向量维度: ' . $dimensions,
            'latency_ms'       => $latencyMs,
            'tokens'           => 0,
            'message'          => $dimensions > 0 ? '向量化能力正常（' . $dimensions . '维）' : '返回空向量',
        ];
    }

    // ================================================================
    // VoxCPM2 真实连通性测试（两阶段）
    // ================================================================

    /**
     * VoxCPM2 两阶段真实连通性测试
     * 阶段1：健康检查 GET /api/health
     * 阶段2：试合成 POST /api/tts（短文本"你好"）
     * @param array $config
     * @return array
     */
    private function testVoxCPMConnection($config)
    {
        $defaultUrl = Config::get('aivideo.voxcpm.api_url', 'http://127.0.0.1:8866');
        $apiUrl = $this->resolveVoxCPMUrl($config['api_key_decrypted'] ?? '', $defaultUrl);

        // 自动检测服务模式: REST API vs Gradio WebUI
        $serverMode = $this->detectVoxCPMServerMode($apiUrl);

        if ($serverMode === 'gradio') {
            return $this->testVoxCPMGradioConnection($config, $apiUrl);
        }

        // REST API 模式（原有逻辑）
        return $this->testVoxCPMRestApiConnection($config, $apiUrl);
    }

    /**
     * 检测 VoxCPM2 服务运行模式
     * @param string $apiUrl
     * @return string 'rest_api' | 'gradio' | 'unknown'
     */
    private function detectVoxCPMServerMode($apiUrl)
    {
        // 1. 尝试 REST API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/api/health',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) {
            $data = json_decode($resp, true);
            if (is_array($data) && isset($data['status'])) return 'rest_api';
        }

        // 2. 尝试 Gradio API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/gradio_api/info',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) {
            $data = json_decode($resp, true);
            if (is_array($data) && isset($data['named_endpoints'])) return 'gradio';
        }

        return 'unknown';
    }

    /**
     * VoxCPM2 REST API 模式两阶段测试
     */
    private function testVoxCPMRestApiConnection($config, $apiUrl)
    {
        $result = [
            'status'      => 1,
            'msg'         => '',
            'provider'    => $config['provider_name'] ?? 'VoxCPM2',
            'test_detail' => [
                'service'    => ['passed' => false, 'latency_ms' => 0, 'message' => ''],
                'synthesis'  => ['passed' => false, 'sample_rate' => 0, 'duration' => 0, 'latency_ms' => 0, 'message' => ''],
            ]
        ];

        // ---------- 阶段 1：健康检查 ----------
        $phase1 = $this->testVoxCPMHealthCheck($apiUrl);
        $result['test_detail']['service'] = $phase1;

        if (!$phase1['passed']) {
            $result['status'] = 0;
            $result['msg'] = 'VoxCPM2 服务连接失败: ' . ($phase1['message'] ?? '未知错误') . '。请确认 voxcpm_server.py 已启动并监听在 ' . $apiUrl;
            return $result;
        }

        // ---------- 阶段 2：试合成 ----------
        $phase2 = $this->testVoxCPMSynthesis($apiUrl);
        $result['test_detail']['synthesis'] = $phase2;

        if ($phase2['passed']) {
            $result['status'] = 1;
            $sampleRate = $phase2['sample_rate'] ?? 0;
            $duration = $phase2['duration'] ?? 0;
            $latency = $phase2['latency_ms'] ?? 0;
            $result['msg'] = '✅ VoxCPM2 服务正常，语音合成测试通过（采样率 ' . $sampleRate . 'Hz，时长 ' . $duration . 's，耗时 ' . $latency . 'ms）';
        } else {
            $result['status'] = 0;
            $result['msg'] = '健康检查通过，但语音合成测试失败: ' . ($phase2['message'] ?? '未知错误');
        }

        return $result;
    }

    /**
     * 解析 VoxCPM2 服务地址
     * @param string $apiKeyValue
     * @param string $defaultUrl
     * @return string
     */
    private function resolveVoxCPMUrl($apiKeyValue, $defaultUrl)
    {
        if (empty($apiKeyValue)) {
            return $defaultUrl;
        }
        if (filter_var($apiKeyValue, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $apiKeyValue)) {
            return rtrim($apiKeyValue, '/');
        }
        return $defaultUrl;
    }

    /**
     * 阶段1：VoxCPM2 健康检查
     * @param string $apiUrl
     * @return array
     */
    private function testVoxCPMHealthCheck($apiUrl)
    {
        $healthEndpoint = Config::get('aivideo.voxcpm.health_endpoint', '/api/health');
        $url = $apiUrl . $healthEndpoint;
        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode !== 200) {
            $errMsg = $curlError ?: ('HTTP状态码: ' . $httpCode);
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '连接失败: ' . $errMsg,
            ];
        }

        $data = json_decode($response, true);
        $status = $data['status'] ?? '';
        $modelLoaded = $data['model_loaded'] ?? false;
        $sampleRate = $data['sample_rate'] ?? 0;

        if ($status !== 'ok') {
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '服务状态异常: status=' . $status,
            ];
        }

        if (!$modelLoaded) {
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '模型未加载到GPU，请检查 voxcpm_server.py 日志',
            ];
        }

        return [
            'passed'      => true,
            'latency_ms'  => $latencyMs,
            'message'     => '服务正常，模型已加载（采样率 ' . $sampleRate . 'Hz）',
            'sample_rate' => $sampleRate,
            'model_name'  => $data['model'] ?? 'VoxCPM2',
        ];
    }

    /**
     * 阶段2：VoxCPM2 试合成
     * 使用短文本"你好"进行实际合成
     * @param string $apiUrl
     * @return array
     */
    private function testVoxCPMSynthesis($apiUrl)
    {
        $ttsEndpoint = Config::get('aivideo.voxcpm.tts_endpoint', '/api/tts');
        $url = $apiUrl . $ttsEndpoint;

        $payload = json_encode([
            'text' => '你好',
        ], JSON_UNESCAPED_UNICODE);

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode !== 200) {
            $errMsg = $curlError ?: ('HTTP ' . $httpCode);
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '合成请求失败: ' . $errMsg,
            ];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '返回数据格式无效',
            ];
        }

        $audioBase64 = $data['audio_base64'] ?? '';
        $status = $data['status'] ?? '';

        if ($status !== 'success' || empty($audioBase64)) {
            $detail = $data['detail'] ?? ($data['error'] ?? '音频数据为空');
            return [
                'passed'     => false,
                'latency_ms' => $latencyMs,
                'message'    => '合成失败: ' . $detail,
            ];
        }

        return [
            'passed'      => true,
            'latency_ms'  => $latencyMs,
            'sample_rate' => $data['sample_rate'] ?? 0,
            'duration'    => round($data['duration'] ?? 0, 3),
            'message'     => '合成成功',
        ];
    }

    // ================================================================
    // VoxCPM2 Gradio WebUI 模式测试
    // ================================================================

    /**
     * VoxCPM2 Gradio WebUI 模式两阶段测试
     * 阶段1：检查 /gradio_api/info 是否有 /generate 端点
     * 阶段2：提交短文本"你好"合成任务，等待结果
     */
    private function testVoxCPMGradioConnection($config, $apiUrl)
    {
        $result = [
            'status'      => 1,
            'msg'         => '',
            'provider'    => $config['provider_name'] ?? 'VoxCPM2',
            'test_detail' => [
                'service'    => ['passed' => false, 'latency_ms' => 0, 'message' => ''],
                'synthesis'  => ['passed' => false, 'sample_rate' => 0, 'duration' => 0, 'latency_ms' => 0, 'message' => ''],
            ]
        ];

        // ---------- 阶段 1：Gradio API 可用性检查 ----------
        $startTime = microtime(true);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/gradio_api/info',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if ($response === false || $httpCode !== 200) {
            $result['test_detail']['service'] = [
                'passed' => false, 'latency_ms' => $latencyMs,
                'message' => 'Gradio WebUI 不可达: ' . ($curlError ?: 'HTTP ' . $httpCode),
            ];
            $result['status'] = 0;
            $result['msg'] = 'Gradio WebUI 服务连接失败。请确认服务已启动并监听在 ' . $apiUrl;
            return $result;
        }

        $data = json_decode($response, true);
        $endpoints = $data['named_endpoints'] ?? [];
        $hasGenerate = isset($endpoints['/generate']);

        if (!$hasGenerate) {
            $result['test_detail']['service'] = [
                'passed' => false, 'latency_ms' => $latencyMs,
                'message' => 'Gradio WebUI 缺少 /generate 端点',
            ];
            $result['status'] = 0;
            $result['msg'] = 'Gradio WebUI 已连接但缺少 /generate API，请检查 VoxCPM2 版本';
            return $result;
        }

        $result['test_detail']['service'] = [
            'passed' => true, 'latency_ms' => $latencyMs,
            'message' => 'Gradio WebUI 服务正常，/generate 端点可用（' . $latencyMs . 'ms）',
        ];

        // ---------- 阶段 2：试合成（Gradio API）----------
        $phase2 = $this->testVoxCPMGradioSynthesis($apiUrl);
        $result['test_detail']['synthesis'] = $phase2;

        if ($phase2['passed']) {
            $result['status'] = 1;
            $latency2 = $phase2['latency_ms'] ?? 0;
            $result['msg'] = '✅ VoxCPM2 Gradio WebUI 服务正常，语音合成测试通过（耗时 ' . $latency2 . 'ms）';
        } else {
            $result['status'] = 0;
            $result['msg'] = 'Gradio 服务正常，但语音合成测试失败: ' . ($phase2['message'] ?? '未知错误');
        }

        return $result;
    }

    /**
     * Gradio 模式试合成：提交短文本，等待 SSE 结果
     */
    private function testVoxCPMGradioSynthesis($apiUrl)
    {
        $startTime = microtime(true);

        // Gradio /generate 参数: [text, control, ref_wav, use_prompt_text, prompt_text, cfg, normalize, denoise, dit_steps]
        $gradioData = ['你好', '', null, false, '', 2.0, true, false, 10];

        // 步骤1: 提交
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/gradio_api/call/generate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['data' => $gradioData], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $submitResp = curl_exec($ch);
        $submitCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($submitResp === false || $submitCode !== 200) {
            return ['passed' => false, 'latency_ms' => intval((microtime(true) - $startTime) * 1000), 'message' => 'Gradio 提交失败: HTTP ' . $submitCode];
        }

        $submitData = json_decode($submitResp, true);
        $eventId = $submitData['event_id'] ?? '';
        if (empty($eventId)) {
            return ['passed' => false, 'latency_ms' => intval((microtime(true) - $startTime) * 1000), 'message' => 'Gradio 未返回 event_id'];
        }

        // 步骤2: 轮询 SSE (最多等120秒)
        $pollUrl = $apiUrl . '/gradio_api/call/generate/' . $eventId;
        $pollTimeout = 120;
        $sseResult = $this->pollVoxCPMGradioSSE($pollUrl, $pollTimeout);

        $latencyMs = intval((microtime(true) - $startTime) * 1000);

        if (!$sseResult['success']) {
            return ['passed' => false, 'latency_ms' => $latencyMs, 'message' => $sseResult['error']];
        }

        return [
            'passed'      => true,
            'latency_ms'  => $latencyMs,
            'sample_rate' => 48000,
            'duration'    => 0,
            'message'     => '合成成功',
        ];
    }

    /**
     * 轮询 Gradio SSE 结果（简化版，用于测试）
     */
    private function pollVoxCPMGradioSSE($pollUrl, $timeout = 120)
    {
        $startTime = time();
        $buffer = '';
        $result = ['success' => false, 'error' => 'Gradio TTS 超时 (' . $timeout . 's)，模型可能正在加载或GPU资源不足'];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $pollUrl,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Accept: text/event-stream'],
        ]);

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$result, $startTime, $timeout) {
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $eventBlock = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);
                $event = '';
                $data = '';
                foreach (explode("\n", $eventBlock) as $line) {
                    if (strpos($line, 'event: ') === 0) $event = trim(substr($line, 7));
                    elseif (strpos($line, 'data: ') === 0) $data = substr($line, 6);
                }
                if ($event === 'complete') {
                    $result = ['success' => true, 'error' => ''];
                    return 0;
                } elseif ($event === 'error') {
                    $parsed = json_decode($data, true);
                    $errMsg = is_array($parsed) ? ($parsed['message'] ?? json_encode($parsed)) : strval($data);
                    $result = ['success' => false, 'error' => 'Gradio 错误: ' . $errMsg];
                    return 0;
                }
            }
            if ((time() - $startTime) > $timeout) {
                $result = ['success' => false, 'error' => 'Gradio TTS 超时 (' . $timeout . 's)'];
                return 0;
            }
            return strlen($chunk);
        });

        curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}

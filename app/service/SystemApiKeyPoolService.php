<?php
/**
 * 系统API Key池服务类
 * 提供Key池负载均衡、并发控制、调用统计等功能
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;

class SystemApiKeyPoolService extends SystemApiKeyService
{
    /**
     * 从Key池中获取一个可用Key
     * @param string $providerCode 供应商标识
     * @return array|null 返回解密后的Key配置信息，失败返回null
     */
    public function acquireKey($providerCode)
    {
        // 查询该供应商所有启用的Key
        $keys = Db::name('system_api_key')
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->order('sort asc, weight desc')
            ->select()->toArray();
        
        if (empty($keys)) {
            return null;
        }
        
        // 筛选未达并发上限的Key
        $candidateKeys = [];
        foreach ($keys as $key) {
            $maxConcurrency = $key['max_concurrency'] ?? 5;
            $currentConcurrency = $key['current_concurrency'] ?? 0;
            if ($currentConcurrency < $maxConcurrency) {
                $candidateKeys[] = $key;
            }
        }
        
        if (empty($candidateKeys)) {
            // 所有Key并发已满
            Log::warning("SystemApiKeyPool: 供应商 {$providerCode} 所有Key并发已满");
            return null;
        }
        
        // 按加权轮询策略选择Key
        $selectedKey = $this->selectKeyByWeight($candidateKeys);
        
        if (!$selectedKey) {
            return null;
        }
        
        // 原子操作：增加并发计数
        $maxConcurrency = $selectedKey['max_concurrency'] ?? 5;
        $affected = Db::name('system_api_key')
            ->where('id', $selectedKey['id'])
            ->where('current_concurrency', '<', $maxConcurrency)
            ->inc('current_concurrency')
            ->update(['last_used_time' => time()]);
        
        if (!$affected) {
            // 竞争失败，递归重试
            return $this->acquireKey($providerCode);
        }
        
        // 返回解密后的Key信息
        return [
            'id' => $selectedKey['id'],
            'provider_code' => $selectedKey['provider_code'],
            'config_name' => $selectedKey['config_name'],
            'api_key' => $this->decryptApiKey($selectedKey['api_key']),
            'api_secret' => $this->decryptApiKey($selectedKey['api_secret']),
            'extra_config' => json_decode($selectedKey['extra_config'] ?: '{}', true)
        ];
    }

    /**
     * 按权重选择Key（加权轮询）
     * @param array $keys 候选Key列表
     * @return array|null
     */
    private function selectKeyByWeight($keys)
    {
        if (empty($keys)) {
            return null;
        }
        
        // 计算有效权重
        foreach ($keys as &$key) {
            $effectiveWeight = $key['weight'] ?? 100;
            
            // 故障降权：如果失败率超过50%且调用超过10次，降低权重
            $totalCalls = $key['total_calls'] ?? 0;
            $failCalls = $key['fail_calls'] ?? 0;
            if ($totalCalls >= 10) {
                $failRate = $failCalls / $totalCalls;
                if ($failRate > 0.5) {
                    $effectiveWeight = max(1, $effectiveWeight * 0.3);
                }
            }
            
            // 并发余量加成
            $maxConcurrency = $key['max_concurrency'] ?? 5;
            $currentConcurrency = $key['current_concurrency'] ?? 0;
            $remainingCapacity = $maxConcurrency - $currentConcurrency;
            $key['effective_weight'] = $effectiveWeight * (1 + $remainingCapacity * 0.1);
        }
        
        // 按有效权重排序
        usort($keys, function($a, $b) {
            return $b['effective_weight'] - $a['effective_weight'];
        });
        
        // 加权随机选择
        $totalWeight = array_sum(array_column($keys, 'effective_weight'));
        if ($totalWeight <= 0) {
            return $keys[0];
        }
        
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
        $affected = Db::name('system_api_key')
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
            
            // 检查是否需要自动禁用（连续失败超过5次）
            $key = Db::name('system_api_key')->where('id', $keyId)->find();
            if ($key) {
                $totalCalls = $key['total_calls'] + 1;
                $failCalls = $key['fail_calls'] + 1;
                // 如果调用超过10次且失败率超过80%，自动禁用
                if ($totalCalls >= 10 && ($failCalls / $totalCalls) > 0.8) {
                    $updateData['is_active'] = 0;
                    Log::warning("SystemApiKeyPool: Key ID={$keyId} 失败率过高，已自动禁用");
                }
            }
        }
        
        Db::name('system_api_key')->where('id', $keyId)->update($updateData);
        
        return ['status' => 1, 'msg' => '记录成功'];
    }

    /**
     * 获取Key池状态概览
     * @param string $providerCode 供应商标识（可选，不传则查询全部）
     * @return array
     */
    public function getPoolStatus($providerCode = '')
    {
        $where = [];
        if ($providerCode) {
            $where[] = ['provider_code', '=', $providerCode];
        }
        
        $keys = Db::name('system_api_key')
            ->field('id, config_name, provider_code, max_concurrency, current_concurrency, weight, total_calls, fail_calls, is_active, last_used_time, last_error_time, last_error_msg')
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
        
        // 按供应商分组统计
        $byProvider = [];
        
        foreach ($keys as &$key) {
            $maxConcurrency = $key['max_concurrency'] ?? 5;
            $currentConcurrency = $key['current_concurrency'] ?? 0;
            
            if ($key['is_active']) {
                $summary['active_keys']++;
                $summary['total_capacity'] += $maxConcurrency;
                $summary['used_capacity'] += $currentConcurrency;
            }
            
            $key['remaining'] = $maxConcurrency - $currentConcurrency;
            $key['last_used_text'] = $key['last_used_time'] ? date('Y-m-d H:i:s', $key['last_used_time']) : '-';
            $key['last_error_text'] = $key['last_error_time'] ? date('Y-m-d H:i:s', $key['last_error_time']) : '-';
            
            // 计算成功率
            $totalCalls = $key['total_calls'] ?? 0;
            $failCalls = $key['fail_calls'] ?? 0;
            if ($totalCalls > 0) {
                $key['success_rate'] = round(($totalCalls - $failCalls) / $totalCalls * 100, 1) . '%';
            } else {
                $key['success_rate'] = '-';
            }
            
            // 按供应商分组
            $pc = $key['provider_code'];
            if (!isset($byProvider[$pc])) {
                $byProvider[$pc] = [
                    'provider_code' => $pc,
                    'total_keys' => 0,
                    'active_keys' => 0,
                    'total_capacity' => 0,
                    'used_capacity' => 0,
                    'keys' => []
                ];
            }
            $byProvider[$pc]['total_keys']++;
            if ($key['is_active']) {
                $byProvider[$pc]['active_keys']++;
                $byProvider[$pc]['total_capacity'] += $maxConcurrency;
                $byProvider[$pc]['used_capacity'] += $currentConcurrency;
            }
            $byProvider[$pc]['keys'][] = $key;
        }
        
        $summary['available_capacity'] = $summary['total_capacity'] - $summary['used_capacity'];
        
        return [
            'summary' => $summary,
            'by_provider' => array_values($byProvider),
            'keys' => $keys
        ];
    }

    /**
     * 重置所有Key的并发计数（服务重启时调用）
     * @param string $providerCode 供应商标识（可选，不传则重置所有）
     */
    public function resetConcurrency($providerCode = '')
    {
        $where = [];
        if ($providerCode) {
            $where[] = ['provider_code', '=', $providerCode];
        }
        
        Db::name('system_api_key')
            ->where($where)
            ->update(['current_concurrency' => 0]);
    }

    /**
     * 检查指定供应商是否有可用的API Key
     * @param string $providerCode 供应商标识
     * @return array
     */
    public function checkProviderConfig($providerCode)
    {
        $activeCount = Db::name('system_api_key')
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->count();
        
        if ($activeCount == 0) {
            return [
                'status' => 0,
                'msg' => "供应商 {$providerCode} 未配置可用的API Key",
                'has_config' => false,
                'redirect_url' => '/SystemApiKey/index'
            ];
        }
        
        return [
            'status' => 1,
            'msg' => '配置有效',
            'has_config' => true,
            'key_count' => $activeCount
        ];
    }

    /**
     * 获取指定供应商的可用并发容量
     * @param string $providerCode 供应商标识
     * @return int
     */
    public function getAvailableCapacity($providerCode)
    {
        $keys = Db::name('system_api_key')
            ->field('max_concurrency, current_concurrency')
            ->where('provider_code', $providerCode)
            ->where('is_active', 1)
            ->select()->toArray();
        
        $available = 0;
        foreach ($keys as $key) {
            $max = $key['max_concurrency'] ?? 5;
            $current = $key['current_concurrency'] ?? 0;
            $available += max(0, $max - $current);
        }
        
        return $available;
    }
}

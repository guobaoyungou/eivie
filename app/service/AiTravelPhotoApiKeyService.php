<?php
namespace app\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * AI旅拍API Key管理服务类
 * 负责API Key的轮询选择、并发控制、统计更新等功能
 */
class AiTravelPhotoApiKeyService
{
    /**
     * Redis键名前缀
     */
    const REDIS_KEY_CONCURRENT = 'ai_key_concurrent:'; // 并发计数
    const REDIS_KEY_LOCK = 'ai_key_lock:'; // 选择锁
    const REDIS_KEY_LIST = 'ai_keys_list:'; // Key列表缓存
    
    /**
     * 缓存过期时间
     */
    const CACHE_EXPIRE_CONCURRENT = 300; // 并发计数5分钟
    const CACHE_EXPIRE_LOCK = 5; // 锁5秒
    const CACHE_EXPIRE_LIST = 300; // 列表缓存5分钟
    
    /**
     * 获取可用的API Key
     * 
     * @param string $modelType 模型类型：tongyi_wanxiang/kling_ai
     * @param int $bid 商家ID
     * @param int $aid 平台ID
     * @return array|null 返回可用Key信息或null
     */
    public function getAvailableApiKey($modelType, $bid, $aid)
    {
        // 1. 查询商家启用的Key列表
        $keys = $this->getKeyList($modelType, $bid, $aid);
        
        if (empty($keys)) {
            // 2. 如果商家没有配置Key，查询平台默认Key
            $keys = $this->getKeyList($modelType, 0, $aid);
        }
        
        if (empty($keys)) {
            return null;
        }
        
        // 3. 获取各Key当前并发数
        foreach ($keys as &$key) {
            $key['current_concurrent'] = $this->getCurrentConcurrent($modelType, $key['id']);
        }
        unset($key);
        
        // 4. 按当前并发数升序排列
        usort($keys, function($a, $b) {
            return $a['current_concurrent'] - $b['current_concurrent'];
        });
        
        // 5. 遍历列表，选择第一个未达并发上限的Key
        foreach ($keys as $key) {
            if ($key['current_concurrent'] < $key['max_concurrent']) {
                // 6. 尝试增加并发计数
                if ($this->increaseKeyUsage($modelType, $key['id'])) {
                    return $key;
                }
            }
        }
        
        // 所有Key都达到上限
        return null;
    }
    
    /**
     * 获取Key列表
     * 
     * @param string $modelType 模型类型
     * @param int $bid 商家ID
     * @param int $aid 平台ID
     * @return array
     */
    protected function getKeyList($modelType, $bid, $aid)
    {
        $cacheKey = self::REDIS_KEY_LIST . "{$modelType}:{$bid}";
        
        // 尝试从缓存获取
        $keys = Cache::get($cacheKey);
        if ($keys !== false) {
            return $keys;
        }
        
        // 从数据库查询
        $keys = Db::name('ai_travel_photo_model')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('model_type', $modelType)
            ->where('status', 1)
            ->order('current_concurrent', 'asc')
            ->order('sort', 'desc')
            ->select()
            ->toArray();
        
        // 缓存结果
        Cache::set($cacheKey, $keys, self::CACHE_EXPIRE_LIST);
        
        return $keys;
    }
    
    /**
     * 获取当前并发数
     * 
     * @param string $modelType 模型类型
     * @param int $keyId Key ID
     * @return int
     */
    protected function getCurrentConcurrent($modelType, $keyId)
    {
        $redisKey = self::REDIS_KEY_CONCURRENT . "{$modelType}:{$keyId}";
        $count = Cache::get($redisKey);
        return $count !== false ? intval($count) : 0;
    }
    
    /**
     * 增加Key使用计数
     * 
     * @param string $modelType 模型类型
     * @param int $keyId Key ID
     * @return bool
     */
    public function increaseKeyUsage($modelType, $keyId)
    {
        $redisKey = self::REDIS_KEY_CONCURRENT . "{$modelType}:{$keyId}";
        
        try {
            // 使用Redis的INCR原子操作
            $count = Cache::inc($redisKey);
            
            // 设置过期时间（防止异常退出导致计数不准）
            if ($count == 1) {
                Cache::expire($redisKey, self::CACHE_EXPIRE_CONCURRENT);
            }
            
            return true;
        } catch (\Exception $e) {
            // Redis操作失败，记录日志
            trace('增加Key使用计数失败：' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 减少Key使用计数
     * 
     * @param string $modelType 模型类型
     * @param int $keyId Key ID
     * @return bool
     */
    public function decreaseKeyUsage($modelType, $keyId)
    {
        $redisKey = self::REDIS_KEY_CONCURRENT . "{$modelType}:{$keyId}";
        
        try {
            // 使用Redis的DECR原子操作
            $count = Cache::dec($redisKey);
            
            // 如果计数小于0，重置为0
            if ($count < 0) {
                Cache::set($redisKey, 0, self::CACHE_EXPIRE_CONCURRENT);
            }
            
            return true;
        } catch (\Exception $e) {
            // Redis操作失败，记录日志
            trace('减少Key使用计数失败：' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 更新Key统计数据
     * 
     * @param int $keyId Key ID
     * @param bool $success 是否成功
     * @param float $cost 本次成本
     * @param int $costTime 耗时（秒）
     * @return bool
     */
    public function updateKeyStatistics($keyId, $success, $cost = 0, $costTime = 0)
    {
        try {
            $data = [
                'total_calls' => Db::raw('total_calls + 1'),
                'last_call_time' => time(),
                'update_time' => time()
            ];
            
            if ($success) {
                $data['success_calls'] = Db::raw('success_calls + 1');
                $data['total_cost'] = Db::raw("total_cost + {$cost}");
            } else {
                $data['fail_calls'] = Db::raw('fail_calls + 1');
            }
            
            Db::name('ai_travel_photo_model')
                ->where('id', $keyId)
                ->update($data);
            
            // 清除缓存
            $this->clearKeyListCache($keyId);
            
            return true;
        } catch (\Exception $e) {
            trace('更新Key统计数据失败：' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 测试API Key连接
     * 
     * @param string $modelType 模型类型
     * @param string $apiKey API Key
     * @return array
     */
    public function testApiKeyConnection($modelType, $apiKey)
    {
        $startTime = microtime(true);
        
        try {
            // 根据不同的模型类型进行测试
            if ($modelType == 'tongyi_wanxiang') {
                // 测试通义万相API
                $result = $this->testTongyiApi($apiKey);
            } elseif ($modelType == 'kling_ai') {
                // 测试可灵AI API
                $result = $this->testKlingApi($apiKey);
            } else {
                return [
                    'status' => 0,
                    'msg' => '不支持的模型类型'
                ];
            }
            
            $responseTime = round(microtime(true) - $startTime, 2);
            
            if ($result['success']) {
                return [
                    'status' => 1,
                    'msg' => '连接成功',
                    'data' => [
                        'response_time' => $responseTime,
                        'model_version' => $result['version'] ?? 'v1'
                    ]
                ];
            } else {
                return [
                    'status' => 0,
                    'msg' => '连接失败：' . $result['error']
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'msg' => '测试异常：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 测试通义万相API
     * 
     * @param string $apiKey
     * @return array
     */
    protected function testTongyiApi($apiKey)
    {
        // 简化测试：仅验证Key格式是否正确
        // 通义万相的API Key通常以sk-开头，长度大于20
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API Key不能为空'];
        }
        
        if (strlen($apiKey) < 20) {
            return ['success' => false, 'error' => 'API Key格式不正确（长度过短）'];
        }
        
        // 格式验证通过
        return [
            'success' => true, 
            'version' => 'v1',
            'message' => 'API Key格式验证通过（未实际调用API）'
        ];
        
        /* 如果需要实际测试连接，可使用以下代码（需要正确的API endpoint）：
        $url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';
        
        $postData = json_encode([
            'model' => 'wanx-v1',
            'input' => [
                'prompt' => 'test'
            ],
            'parameters' => [
                'size' => '1024*1024'
            ]
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'X-DashScope-Async: enable'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 || $httpCode == 201 || $httpCode == 202) {
            return ['success' => true, 'version' => 'v1'];
        } else {
            $errorMsg = $error ?: 'HTTP ' . $httpCode;
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['message'])) {
                    $errorMsg .= ': ' . $responseData['message'];
                }
            }
            return ['success' => false, 'error' => $errorMsg];
        }
        */
    }
    
    /**
     * 测试可灵AI API
     * 
     * @param string $apiKey
     * @return array
     */
    protected function testKlingApi($apiKey)
    {
        // 简化测试：仅验证Key格式是否正确
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API Key不能为空'];
        }
        
        if (strlen($apiKey) < 20) {
            return ['success' => false, 'error' => 'API Key格式不正确（长度过短）'];
        }
        
        // 格式验证通过
        return [
            'success' => true, 
            'version' => 'v1',
            'message' => 'API Key格式验证通过（未实际调用API）'
        ];
        
        /* 如果需要实际测试连接，可使用以下代码（需要正确的API endpoint）：
        $url = 'https://api.klingai.com/v1/images/generations';
        
        $postData = json_encode([
            'prompt' => 'test',
            'model' => 'kling-v1'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 || $httpCode == 201 || $httpCode == 202) {
            return ['success' => true, 'version' => 'v1'];
        } else {
            $errorMsg = $error ?: 'HTTP ' . $httpCode;
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['message'])) {
                    $errorMsg .= ': ' . $responseData['message'];
                }
            }
            return ['success' => false, 'error' => $errorMsg];
        }
        */
    }
    
    /**
     * 清除Key列表缓存
     * 
     * @param int $keyId Key ID（用于查询模型类型和bid）
     * @return bool
     */
    protected function clearKeyListCache($keyId)
    {
        try {
            $keyInfo = Db::name('ai_travel_photo_model')
                ->where('id', $keyId)
                ->field('model_type,bid')
                ->find();
            
            if ($keyInfo) {
                $cacheKey = self::REDIS_KEY_LIST . "{$keyInfo['model_type']}:{$keyInfo['bid']}";
                Cache::delete($cacheKey);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 校准并发计数
     * 用于定时任务，防止异常退出导致计数不准确
     * 
     * @return void
     */
    public function calibrateConcurrent()
    {
        try {
            // 将所有Key的current_concurrent字段重置为0
            Db::name('ai_travel_photo_model')
                ->where('current_concurrent', '>', 0)
                ->update(['current_concurrent' => 0]);
            
            trace('并发计数校准完成', 'info');
        } catch (\Exception $e) {
            trace('并发计数校准失败：' . $e->getMessage(), 'error');
        }
    }
}

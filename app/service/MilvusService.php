<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Config;

/**
 * Milvus 向量数据库服务
 * 用于存储和检索人脸特征向量
 * Class MilvusService
 * @package app\service
 */
class MilvusService
{
    private $host;
    private $port;        // API 端口 (19530)
    private $restPort;    // 健康检查端口 (9091)
    private $collectionName;
    private $dimension;
    private $timeout;
    
    public function __construct()
    {
        $this->host = Config::get('milvus.host', '127.0.0.1');
        $this->port = Config::get('milvus.port', '19530');
        $this->restPort = Config::get('milvus.rest_port', '9091');
        $this->collectionName = Config::get('milvus.collection_name', 'face_features_512');
        $this->dimension = Config::get('milvus.dimension', 512);
        $this->timeout = Config::get('milvus.timeout', 30);
    }
    
    /**
     * 获取 Simple REST API 基础 URL（端口 19530）
     */
    private function getApiBaseUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    /**
     * 获取健康检查基础 URL（端口 9091）
     */
    private function getHealthBaseUrl(): string
    {
        return "http://{$this->host}:{$this->restPort}";
    }
    
    /**
     * 发送 HTTP 请求到 Milvus Simple REST API
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $url = $this->getApiBaseUrl() . $path;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('Milvus request failed: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new \Exception('Milvus HTTP error ' . $httpCode . ': ' . ($result['message'] ?? $response));
        }

        // Milvus Simple API 返回 HTTP 200 但通过 code 字段指示错误
        if (isset($result['code']) && $result['code'] != 200 && $result['code'] != 0) {
            $reason = $result['message'] ?? $result['status']['reason'] ?? $response;
            throw new \Exception('Milvus API error [code=' . $result['code'] . ']: ' . $reason);
        }
        
        return $result ?? [];
    }
    
    /**
     * 创建集合 (Milvus v2.3 Simple REST API)
     */
    public function createCollection(): bool
    {
        try {
            // 检查集合是否存在
            $collections = $this->listCollections();
            if (in_array($this->collectionName, $collections)) {
                return true;
            }
            
            // 创建集合
            $this->request('POST', '/v1/vector/collections/create', [
                'collectionName' => $this->collectionName,
                'dimension' => $this->dimension,
                'metricType' => Config::get('milvus.metric_type', 'L2'),
            ]);
            
            return true;
        } catch (\Exception $e) {
            // 集合可能已存在，忽略错误
            return true;
        }
    }
    
    /**
     * 创建索引（Simple API 创建集合时自动创建索引，这里保留为空操作）
     */
    public function createIndex(): bool
    {
        return true;
    }
    
    /**
     * 列出所有集合
     */
    public function listCollections(): array
    {
        try {
            $result = $this->request('GET', '/v1/vector/collections');
            return $result['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 插入向量数据 (Milvus v2.3 Simple REST API)
     * @param array $vectors 向量数组（每个向量为 512 维浮点数数组）
     * @param array $metadata 元数据（如人像ID等）
     * @return array 插入的ID列表
     */
    public function insert(array $vectors, array $metadata = []): array
    {
        // 确保集合存在
        $this->createCollection();
        
        $data = [
            'collectionName' => $this->collectionName,
            'data' => []
        ];
        
        foreach ($vectors as $index => $vector) {
            $insertData = [
                'vector' => $vector,
            ];
            
            $data['data'][] = $insertData;
        }
        
        $result = $this->request('POST', '/v1/vector/insert', $data);
        
        // Milvus v2.3 Simple API 响应格式：
        // {"code":200, "data":{"insertCount":1, "insertIds":["465692751965721888"]}}
        $ids = $result['data']['insertIds']
            ?? $result['data']['ids']
            ?? $result['ids']
            ?? [];
        
        return $ids;
    }
    
    /**
     * 搜索向量 (Milvus v2.3 Simple REST API)
     * @param array $vector 查询向量（512维浮点数数组）
     * @param int $topK 返回前K个最相似的结果
     * @param array $filter 过滤条件
     * @return array 搜索结果
     */
    public function search(array $vector, int $topK = 10, array $filter = []): array
    {
        $data = [
            'collectionName' => $this->collectionName,
            'vector' => $vector,
            'limit' => $topK,
        ];
        
        if (!empty($filter)) {
            $data['filter'] = $filter;
        }
        
        $result = $this->request('POST', '/v1/vector/search', $data);
        
        return $result['data'] ?? [];
    }
    
    /**
     * 删除向量 (Milvus v2.3 Simple REST API)
     * @param int|string|array $id 向量ID
     * @return bool
     */
    public function delete($id): bool
    {
        try {
            $ids = is_array($id) ? $id : [(string)$id];
            $this->request('POST', '/v1/vector/delete', [
                'collectionName' => $this->collectionName,
                'id' => $ids,
            ]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取集合信息
     */
    public function getCollectionInfo(): array
    {
        try {
            $result = $this->request('GET', '/v1/vector/collections/' . $this->collectionName);
            return $result['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 检查 Milvus 服务是否可用（使用 9091 端口的健康检查端点）
     */
    public function isHealthy(): bool
    {
        try {
            $url = $this->getHealthBaseUrl() . '/healthz';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // 兼容不同 Milvus 版本的健康检查响应
            // 某些版本返回 "OK"，某些返回 JSON {"status":"ok"}
            if ($httpCode === 200) {
                if ($response === 'OK' || $response === 'ok') {
                    return true;
                }
                $decoded = json_decode($response, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取集合统计信息
     */
    public function getCollectionStats(): array
    {
        // Simple API 不直接提供 stats 端点，通过 collection 详情获取
        return $this->getCollectionInfo();
    }
    
    /**
     * 刷新集合（Simple API 自动管理，保留为兼容接口）
     */
    public function flush(): bool
    {
        return true;
    }
}

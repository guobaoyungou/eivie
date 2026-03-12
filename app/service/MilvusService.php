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
    private $port;
    private $collectionName;
    private $dimension;
    private $timeout;
    
    public function __construct()
    {
        $this->host = Config::get('milvus.host', '127.0.0.1');
        $this->port = Config::get('milvus.port', '19530');
        $this->restPort = Config::get('milvus.rest_port', '9091');
        $this->collectionName = Config::get('milvus.collection_name', 'face_features');
        $this->dimension = Config::get('milvus.dimension', 128);
        $this->timeout = Config::get('milvus.timeout', 30);
    }
    
    /**
     * 获取 REST API 基础 URL
     */
    private function getBaseUrl(): string
    {
        return "http://{$this->host}:{$this->restPort}";
    }
    
    /**
     * 发送 HTTP 请求
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $url = $this->getBaseUrl() . $path;
        
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
            throw new \Exception('Milvus request failed: ' . ($result['message'] ?? $response));
        }
        
        return $result;
    }
    
    /**
     * 创建集合
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
            $this->request('POST', '/v1/collections', [
                'collectionName' => $this->collectionName,
                'dimension' => $this->dimension,
                'metricType' => Config::get('milvus.metric_type', 'L2'),
                'primaryFieldName' => 'id',
                'vectorFieldName' => 'vector',
                'idType' => 'Int',
                'vectorType' => 'FloatVector',
            ]);
            
            return true;
        } catch (\Exception $e) {
            // 集合可能已存在，忽略错误
            return true;
        }
    }
    
    /**
     * 创建索引
     */
    public function createIndex(): bool
    {
        try {
            $this->request('POST', '/v1/collections/index', [
                'collectionName' => $this->collectionName,
                'indexType' => Config::get('milvus.index_type', 'IVF_FLAT'),
                'metricType' => Config::get('milvus.metric_type', 'L2'),
                'params' => Config::get('milvus.index_params', ['nlist' => 128]),
            ]);
            
            return true;
        } catch (\Exception $e) {
            // 索引可能已存在
            return true;
        }
    }
    
    /**
     * 列出所有集合
     */
    public function listCollections(): array
    {
        try {
            $result = $this->request('GET', '/v1/collections');
            return $result['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 插入向量数据
     * @param array $vectors 向量数组（每个向量为128维浮点数数组）
     * @param array $metadata 元数据（如人像ID等）
     * @return array 插入的ID列表
     */
    public function insert(array $vectors, array $metadata = []): array
    {
        // 确保集合和索引存在
        $this->createCollection();
        $this->createIndex();
        
        $data = [
            'collectionName' => $this->collectionName,
            'data' => []
        ];
        
        foreach ($vectors as $index => $vector) {
            $insertData = [
                'vector' => $vector,
            ];
            
            // 添加元数据
            if (!empty($metadata)) {
                if (isset($metadata[$index])) {
                    $insertData = array_merge($insertData, $metadata[$index]);
                } elseif (isset($metadata['portrait_id'])) {
                    $insertData['portrait_id'] = $metadata['portrait_id'];
                }
            }
            
            $data['data'][] = $insertData;
        }
        
        $result = $this->request('POST', '/v1/entities', $data);
        
        return $result['ids'] ?? [];
    }
    
    /**
     * 搜索向量
     * @param array $vector 查询向量（128维浮点数数组）
     * @param int $topK 返回前K个最相似的结果
     * @param array $filter 过滤条件
     * @return array 搜索结果
     */
    public function search(array $vector, int $topK = 10, array $filter = []): array
    {
        $data = [
            'collectionName' => $this->collectionName,
            'vector' => $vector,
            'topK' => $topK,
            'params' => Config::get('milvus.search_params', ['nprobe' => 16]),
        ];
        
        if (!empty($filter)) {
            $data['filter'] = $filter;
        }
        
        $result = $this->request('POST', '/v1/entities/search', $data);
        
        return $result['data'] ?? [];
    }
    
    /**
     * 删除向量
     * @param int|string $id 向量ID
     * @return bool
     */
    public function delete($id): bool
    {
        try {
            $this->request('POST', '/v1/entities/delete', [
                'collectionName' => $this->collectionName,
                'ids' => is_array($id) ? $id : [$id],
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
            $result = $this->request('GET', '/v1/collections/' . $this->collectionName);
            return $result['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 检查Milvus服务是否可用
     */
    public function isHealthy(): bool
    {
        try {
            $url = $this->getBaseUrl() . '/healthz';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200 && $response === 'OK';
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取集合统计信息
     */
    public function getCollectionStats(): array
    {
        try {
            $result = $this->request('GET', '/v1/collections/' . $this->collectionName . '/stats');
            return $result['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 刷新集合（加载到内存）
     */
    public function flush(): bool
    {
        try {
            $this->request('POST', '/v1/entities/flush', [
                'collectionNames' => [$this->collectionName],
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

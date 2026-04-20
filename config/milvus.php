<?php

/**
 * Milvus 向量数据库配置
 */

return [
    // Milvus 服务地址
    'host' => env('milvus.host', '127.0.0.1'),
    
    // Milvus 端口
    'port' => env('milvus.port', '19530'),
    
    // REST API 端口
    'rest_port' => env('milvus.rest_port', '9091'),
    
    // 集合名称（InsightFace buffalo_l 模型产生 512 维 L2 归一化向量）
    'collection_name' => env('milvus.collection_name', 'face_features_512'),
    
    // 向量维度 (InsightFace buffalo_l normed_embedding = 512 维)
    'dimension' => 512,
    
    // 索引类型
    'index_type' => 'IVF_FLAT',
    
    // 索引参数
    'index_params' => [
        'nlist' => 128,
    ],
    
    // 搜索参数
    'search_params' => [
        'nprobe' => 16,
    ],
    
    // 度量类型 (L2 或 IP)
    'metric_type' => 'L2',
    
    // 超时时间（秒）
    'timeout' => 30,
];

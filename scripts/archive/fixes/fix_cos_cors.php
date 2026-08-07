#!/usr/bin/env php
<?php
/**
 * 腾讯云 COS CORS 跨域配置脚本
 * 用途：为 ailvpai-1308501196 存储桶配置跨域访问规则
 */

require __DIR__ . '/vendor/autoload.php';

use Qcloud\Cos\Client;

// COS 配置（需要填写实际的密钥）
$config = [
    'region' => 'ap-guangzhou',
    'credentials' => [
        'secretId' => 'YOUR_SECRET_ID',    // 替换为你的 SecretId
        'secretKey' => 'YOUR_SECRET_KEY',  // 替换为你的 SecretKey
    ],
];

$bucket = 'ailvpai-1308501196'; // 存储桶名称

try {
    $cosClient = new Client($config);
    
    // 配置 CORS 规则
    $result = $cosClient->putBucketCors([
        'Bucket' => $bucket,
        'CORSRules' => [
            [
                'ID' => 'ai-eivie-cn-cors',
                'AllowedOrigins' => [
                    'https://ai.eivie.cn',
                    'http://ai.eivie.cn',
                ],
                'AllowedMethods' => ['GET', 'HEAD', 'OPTIONS'],
                'AllowedHeaders' => ['*'],
                'ExposeHeaders' => ['ETag', 'Content-Length', 'x-cos-request-id'],
                'MaxAgeSeconds' => 600,
            ],
        ],
    ]);
    
    echo "✅ CORS 规则配置成功！\n";
    echo "存储桶: {$bucket}\n";
    echo "允许的域名: https://ai.eivie.cn\n";
    echo "\n请等待 1-2 分钟后刷新页面测试\n";
    
} catch (\Exception $e) {
    echo "❌ 配置失败：" . $e->getMessage() . "\n";
    echo "\n请检查：\n";
    echo "1. SecretId 和 SecretKey 是否正确\n";
    echo "2. 账号是否有修改存储桶权限\n";
    echo "3. 存储桶名称是否正确\n";
}

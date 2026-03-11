#!/usr/bin/env php
<?php
/**
 * 修复COS存储桶CORS配置
 * 解决H5前端通过XHR请求COS图片时的跨域问题
 * 
 * 运行方式: php fix_cos_cors.php
 */

// 加载依赖
define('ROOT_PATH', __DIR__ . '/');
require __DIR__ . '/vendor/autoload.php';

// 读取数据库配置
$dbConf = include __DIR__ . '/config.php';
$prefix = $dbConf['prefix'] ?? 'ddwx_';

$dsn = "mysql:host={$dbConf['hostname']};port={$dbConf['hostport']};dbname={$dbConf['database']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbConf['username'], $dbConf['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "[ERROR] 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 获取远程存储配置
$stmt = $pdo->prepare("SELECT `value` FROM `{$prefix}sysset` WHERE `name` = 'remote' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !$row['value']) {
    echo "[ERROR] 未找到远程存储配置\n";
    exit(1);
}

$remoteset = json_decode($row['value'], true);
if (!$remoteset) {
    echo "[ERROR] 远程存储配置JSON解析失败\n";
    exit(1);
}

if (intval($remoteset['type'] ?? 0) !== 4) {
    echo "[INFO] 当前远程存储类型不是腾讯云COS (type={$remoteset['type']})，尝试查找管理员配置...\n";
    
    // 尝试从admin表查找COS配置
    $stmt2 = $pdo->prepare("SELECT `remote` FROM `{$prefix}admin` WHERE `remote` LIKE '%\"type\":4%' OR `remote` LIKE '%\"type\": 4%' LIMIT 1");
    $stmt2->execute();
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($row2 && $row2['remote']) {
        $remoteset = json_decode($row2['remote'], true);
    }
    
    if (!$remoteset || intval($remoteset['type'] ?? 0) !== 4) {
        // 直接尝试从admin表查找包含ailvpai的配置
        $stmt3 = $pdo->prepare("SELECT `remote` FROM `{$prefix}admin` WHERE `remote` LIKE '%ailvpai%' LIMIT 1");
        $stmt3->execute();
        $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        if ($row3 && $row3['remote']) {
            $remoteset = json_decode($row3['remote'], true);
        }
    }
    
    if (!$remoteset || intval($remoteset['type'] ?? 0) !== 4) {
        echo "[ERROR] 未找到腾讯云COS配置\n";
        exit(1);
    }
}

$cosConf = $remoteset['cos'] ?? [];
if (empty($cosConf['secretid']) || empty($cosConf['secretkey']) || empty($cosConf['bucket']) || empty($cosConf['appid'])) {
    echo "[ERROR] COS配置不完整\n";
    echo "  appid: " . ($cosConf['appid'] ?? '空') . "\n";
    echo "  secretid: " . (empty($cosConf['secretid']) ? '空' : '已设置') . "\n";
    echo "  secretkey: " . (empty($cosConf['secretkey']) ? '空' : '已设置') . "\n";
    echo "  bucket: " . ($cosConf['bucket'] ?? '空') . "\n";
    echo "  local(region): " . ($cosConf['local'] ?? '空') . "\n";
    exit(1);
}

$secretId = $cosConf['secretid'];
$secretKey = $cosConf['secretkey'];
$region = $cosConf['local'];
$bucket = str_replace("-" . $cosConf['appid'], '', $cosConf['bucket']) . "-" . $cosConf['appid'];

echo "====== COS CORS 配置修复工具 ======\n";
echo "Bucket: $bucket\n";
echo "Region: $region\n";
echo "URL: " . ($cosConf['url'] ?? '') . "\n\n";

// 创建COS客户端
try {
    $cosClient = new Qcloud\Cos\Client([
        'region' => $region,
        'schema' => 'https',
        'credentials' => [
            'secretId' => $secretId,
            'secretKey' => $secretKey,
        ]
    ]);
} catch (Exception $e) {
    echo "[ERROR] COS客户端初始化失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 先获取当前CORS配置
echo ">>> 获取当前CORS配置...\n";
try {
    $result = $cosClient->getBucketCors([
        'Bucket' => $bucket,
    ]);
    $existingRules = $result['CORSRules'] ?? [];
    echo "  当前规则数: " . count($existingRules) . "\n";
    foreach ($existingRules as $i => $rule) {
        $origins = is_array($rule['AllowedOrigins']) ? implode(', ', $rule['AllowedOrigins']) : ($rule['AllowedOrigins'] ?? '');
        echo "  规则{$i}: Origins={$origins}\n";
    }
} catch (Exception $e) {
    echo "  当前无CORS配置 (" . $e->getMessage() . ")\n";
    $existingRules = [];
}

// 检查是否已经配置了需要的规则
$needsUpdate = true;
foreach ($existingRules as $rule) {
    $origins = $rule['AllowedOrigins'] ?? [];
    if (in_array('*', $origins) || in_array('https://ai.eivie.cn', $origins)) {
        echo "\n[INFO] 已存在匹配的CORS规则，检查是否需要更新...\n";
        $methods = $rule['AllowedMethods'] ?? [];
        if (in_array('GET', $methods)) {
            echo "[INFO] CORS规则已正确配置，无需修改\n";
            $needsUpdate = false;
            break;
        }
    }
}

if (!$needsUpdate) {
    echo "\n====== 完成 ======\n";
    exit(0);
}

// 设置新的CORS规则
echo "\n>>> 设置CORS规则...\n";

$corsRules = [
    [
        'AllowedHeaders' => ['*'],
        'AllowedMethods' => ['GET', 'HEAD'],
        'AllowedOrigins' => ['https://ai.eivie.cn', 'http://ai.eivie.cn', 'https://*.eivie.cn'],
        'ExposeHeaders' => ['Content-Length', 'Content-Type', 'ETag'],
        'MaxAgeSeconds' => 86400,
    ],
];

// 保留其他已存在的、不冲突的规则
foreach ($existingRules as $rule) {
    $origins = $rule['AllowedOrigins'] ?? [];
    $hasConflict = false;
    foreach ($origins as $o) {
        if (stripos($o, 'eivie.cn') !== false || $o === '*') {
            $hasConflict = true;
            break;
        }
    }
    if (!$hasConflict) {
        $corsRules[] = $rule;
    }
}

try {
    $cosClient->putBucketCors([
        'Bucket' => $bucket,
        'CORSRules' => $corsRules,
    ]);
    echo "[SUCCESS] CORS规则设置成功！\n";
} catch (Exception $e) {
    echo "[ERROR] CORS规则设置失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 验证
echo "\n>>> 验证CORS配置...\n";
try {
    $result = $cosClient->getBucketCors([
        'Bucket' => $bucket,
    ]);
    $rules = $result['CORSRules'] ?? [];
    echo "  当前规则数: " . count($rules) . "\n";
    foreach ($rules as $i => $rule) {
        $origins = is_array($rule['AllowedOrigins']) ? implode(', ', $rule['AllowedOrigins']) : ($rule['AllowedOrigins'] ?? '');
        $methods = is_array($rule['AllowedMethods']) ? implode(', ', $rule['AllowedMethods']) : ($rule['AllowedMethods'] ?? '');
        echo "  规则{$i}: Origins=[{$origins}] Methods=[{$methods}]\n";
    }
    echo "\n[SUCCESS] CORS配置验证通过！\n";
} catch (Exception $e) {
    echo "[WARNING] 验证失败: " . $e->getMessage() . "\n";
}

echo "\n====== 完成 ======\n";
echo "H5前端 (ai.eivie.cn) 现在应该可以通过XHR正常加载COS图片了。\n";
echo "如果浏览器仍有缓存，请清除浏览器缓存后重试。\n";

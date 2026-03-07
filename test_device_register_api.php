<?php
/**
 * AI旅拍设备注册API测试脚本
 * 
 * 使用方法：
 * php test_device_register_api.php
 * 或访问：http://your-domain.com/test_device_register_api.php
 */

// 配置
$config = [
    'api_base_url' => 'http://192.168.11.222',  // 使用实际IP地址
    'api_path' => '/api/ai_travel_photo/device/register',
    'test_aid' => 1,  // 添加aid参数
    'test_bid' => 1,
    'test_mdid' => 1,
];

/**
 * 发送HTTP请求
 */
function sendRequest($url, $data) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'duration' => $duration,
        'error' => $error,
    ];
}

/**
 * 输出测试结果
 */
function printResult($testName, $result, $expectedCode = 200) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "测试: {$testName}\n";
    echo str_repeat('=', 80) . "\n";
    
    echo "HTTP状态码: {$result['http_code']}\n";
    echo "响应时间: {$result['duration']}ms\n";
    
    if ($result['error']) {
        echo "❌ CURL错误: {$result['error']}\n";
        return false;
    }
    
    echo "响应内容:\n";
    $json = json_decode($result['response'], true);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // 验证结果
        $success = ($result['http_code'] == $expectedCode);
        if ($success && isset($json['code'])) {
            $success = ($json['code'] == 200);
        }
        
        echo "\n结果: " . ($success ? "✅ 通过" : "❌ 失败") . "\n";
        return $success;
    } else {
        echo $result['response'] . "\n";
        echo "\n结果: ❌ 响应不是有效的JSON\n";
        return false;
    }
}

/**
 * 生成随机设备ID
 */
function generateDeviceId() {
    return strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 12));
}

// 开始测试
echo "\n";
echo "┌" . str_repeat('─', 78) . "┐\n";
echo "│" . str_pad("AI旅拍设备注册API测试工具", 72, ' ', STR_PAD_BOTH) . "│\n";
echo "└" . str_repeat('─', 78) . "┘\n";

$apiUrl = $config['api_base_url'] . $config['api_path'];
echo "\nAPI地址: {$apiUrl}\n";
echo "测试时间: " . date('Y-m-d H:i:s') . "\n";

$testResults = [];

// ============================================
// 测试1：正常注册（新设备）
// ============================================
$deviceId1 = generateDeviceId();
$testData1 = [
    'device_code' => 'TEST001',
    'device_name' => 'Windows测试设备',
    'aid' => $config['test_aid'],  // 添加aid
    'bid' => $config['test_bid'],
    'mdid' => $config['test_mdid'],
    'device_id' => $deviceId1,
    'os_version' => 'Windows 10 Pro 64-bit',
    'client_version' => '1.0.0',
    'pc_name' => 'WIN-TEST-PC',
    'cpu_info' => 'Intel Core i7-10700K @ 3.80GHz',
    'memory_size' => '16GB',
    'disk_info' => 'C: 500GB SSD',
    'ip' => '192.168.1.100',
];

$result1 = sendRequest($apiUrl, $testData1);
$testResults['新设备注册'] = printResult('新设备注册', $result1);

// 保存device_token用于后续测试
$deviceToken1 = null;
if ($result1['response']) {
    $json = json_decode($result1['response'], true);
    if (isset($json['data']['device_token'])) {
        $deviceToken1 = $json['data']['device_token'];
        echo "\n✅ 获取到设备令牌: {$deviceToken1}\n";
    }
}

sleep(1);

// ============================================
// 测试2：重复注册（已存在的设备ID）
// ============================================
$testData2 = $testData1;
$result2 = sendRequest($apiUrl, $testData2);
$testResults['重复注册检测'] = printResult('重复注册检测（应返回已存在的令牌）', $result2);

sleep(1);

// ============================================
// 测试3：缺少必填参数 - device_code
// ============================================
$testData3 = $testData1;
unset($testData3['device_code']);
$testData3['device_id'] = generateDeviceId();

$result3 = sendRequest($apiUrl, $testData3);
$testResults['缺少device_code'] = printResult('缺少必填参数: device_code', $result3, 400);

sleep(1);

// ============================================
// 测试4：缺少必填参数 - bid
// ============================================
$testData4 = $testData1;
unset($testData4['bid']);
$testData4['device_id'] = generateDeviceId();

$result4 = sendRequest($apiUrl, $testData4);
$testResults['缺少bid'] = printResult('缺少必填参数: bid', $result4, 400);

sleep(1);

// ============================================
// 测试5：最小化参数（仅必填字段）
// ============================================
$testData5 = [
    'device_code' => 'TEST_MIN',
    'aid' => $config['test_aid'],  // 添加aid
    'bid' => $config['test_bid'],
    'device_id' => generateDeviceId(),
];

$result5 = sendRequest($apiUrl, $testData5);
$testResults['最小化参数'] = printResult('最小化参数（仅必填字段）', $result5);

sleep(1);

// ============================================
// 测试6：完整参数（所有字段）
// ============================================
$testData6 = [
    'device_code' => 'TEST_FULL',
    'device_name' => '完整参数测试设备',
    'aid' => $config['test_aid'],  // 添加aid
    'bid' => $config['test_bid'],
    'mdid' => $config['test_mdid'],
    'device_id' => generateDeviceId(),
    'device_type' => 'windows',
    'mac_address' => '00:15:5D:12:34:56',
    'os_version' => 'Windows 11 Pro',
    'client_version' => '1.2.3',
    'pc_name' => 'FULL-TEST-PC',
    'cpu_info' => 'AMD Ryzen 9 5900X',
    'memory_size' => '32GB DDR4',
    'disk_info' => 'C: 1TB NVMe SSD',
    'ip' => '192.168.1.200',
];

$result6 = sendRequest($apiUrl, $testData6);
$testResults['完整参数'] = printResult('完整参数测试', $result6);

sleep(1);

// ============================================
// 测试7:特殊字符处理
// ============================================
$testData7 = [
    'device_code' => 'TEST_SPECIAL_' . date('YmdHis'),
    'device_name' => '特殊字符测试: <>&"\'',
    'aid' => $config['test_aid'],  // 添加aid
    'bid' => $config['test_bid'],
    'device_id' => generateDeviceId(),
    'pc_name' => 'PC-中文名称',
];

$result7 = sendRequest($apiUrl, $testData7);
$testResults['特殊字符'] = printResult('特殊字符处理', $result7);

// ============================================
// 测试总结
// ============================================
echo "\n\n";
echo "┌" . str_repeat('─', 78) . "┐\n";
echo "│" . str_pad("测试总结", 72, ' ', STR_PAD_BOTH) . "│\n";
echo "└" . str_repeat('─', 78) . "┘\n\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults));
$failedTests = $totalTests - $passedTests;
$successRate = round(($passedTests / $totalTests) * 100, 2);

echo "总测试数: {$totalTests}\n";
echo "通过: {$passedTests} ✅\n";
echo "失败: {$failedTests} ❌\n";
echo "成功率: {$successRate}%\n\n";

// 详细结果
echo "详细结果:\n";
echo str_repeat('-', 80) . "\n";
foreach ($testResults as $testName => $passed) {
    $status = $passed ? '✅ 通过' : '❌ 失败';
    echo sprintf("%-40s %s\n", $testName, $status);
}
echo str_repeat('-', 80) . "\n";

// 关键检查
echo "\n关键检查项:\n";
echo str_repeat('-', 80) . "\n";

$checks = [
    '新设备注册成功' => $testResults['新设备注册'] ?? false,
    '重复注册检测正常' => $testResults['重复注册检测'] ?? false,
    '参数验证有效' => ($testResults['缺少device_code'] ?? false) && ($testResults['缺少bid'] ?? false),
    '最小化参数支持' => $testResults['最小化参数'] ?? false,
    '完整参数支持' => $testResults['完整参数'] ?? false,
];

foreach ($checks as $checkName => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "{$status} {$checkName}\n";
}
echo str_repeat('-', 80) . "\n";

// 建议和说明
echo "\n💡 说明:\n";
echo "1. 如果所有测试通过，表示API工作正常\n";
echo "2. device_token 应该是32位MD5字符串\n";
echo "3. 重复注册应返回已存在的设备信息\n";
echo "4. 缺少必填参数应返回400错误\n";
echo "5. 特殊字符应被正确转义和存储\n\n";

if ($deviceToken1) {
    echo "🔑 测试设备令牌: {$deviceToken1}\n";
    echo "   可用于后续API测试（心跳、上传等）\n\n";
}

// 返回结果用于自动化测试
if (php_sapi_name() === 'cli') {
    exit($failedTests > 0 ? 1 : 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>设备注册API测试结果</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        pre {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .info { color: #569cd6; }
        .warning { color: #dcdcaa; }
    </style>
</head>
<body>
    <h1>设备注册API测试完成</h1>
    <p>请查看上方控制台输出的详细测试结果</p>
    <pre><?php
    echo "测试完成时间: " . date('Y-m-d H:i:s') . "\n";
    echo "成功率: {$successRate}%\n";
    echo "通过测试: {$passedTests}/{$totalTests}\n";
    ?></pre>
</body>
</html>

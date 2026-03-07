<?php
/**
 * 测试API保存功能
 */

require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use think\facade\Db;
use app\service\ApiManageService;

echo "=== 测试API保存功能 ===\n\n";

// 测试数据
$testInterfaces = [
    [
        'controller' => 'TestController',
        'action' => 'testAction',
        'name' => '测试接口',
        'category' => '测试分类',
        'method' => 'POST',
        'path' => '/test/testaction',
        'description' => '这是一个测试接口',
        'auth_required' => 0,
        'request_params' => '',
        'response_example' => '',
        'tags' => 'test',
        'sort' => 0,
        'status' => 1
    ]
];

$aid = 1; // 测试aid

echo "1. 检查数据库表是否存在...\n";
try {
    $count = Db::name('api_interface')->count();
    echo "   ✓ api_interface 表存在，当前记录数: {$count}\n\n";
} catch (\Exception $e) {
    echo "   ✗ 错误: " . $e->getMessage() . "\n\n";
    exit;
}

echo "2. 测试保存接口...\n";
try {
    $service = new ApiManageService();
    $result = $service->saveScanResults($aid, $testInterfaces);
    
    if ($result['status'] == 1) {
        echo "   ✓ 保存成功: " . $result['msg'] . "\n\n";
    } else {
        echo "   ✗ 保存失败: " . $result['msg'] . "\n\n";
    }
    
    print_r($result);
    
} catch (\Exception $e) {
    echo "   ✗ 发生异常:\n";
    echo "   错误信息: " . $e->getMessage() . "\n";
    echo "   错误文件: " . $e->getFile() . "\n";
    echo "   错误行号: " . $e->getLine() . "\n";
    echo "   错误堆栈:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n3. 查询保存的数据...\n";
try {
    $data = Db::name('api_interface')
        ->where('controller', 'TestController')
        ->where('action', 'testAction')
        ->find();
    
    if ($data) {
        echo "   ✓ 查询成功，数据已保存:\n";
        print_r($data);
    } else {
        echo "   ✗ 未找到保存的数据\n";
    }
} catch (\Exception $e) {
    echo "   ✗ 查询失败: " . $e->getMessage() . "\n";
}

echo "\n=== 测试完成 ===\n";

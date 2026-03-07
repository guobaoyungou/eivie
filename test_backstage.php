<?php
// 简单测试Backstage控制器是否能正常加载
require __DIR__ . '/vendor/autoload.php';

try {
    // 测试基本的类加载
    $reflection = new ReflectionClass('app\controller\Backstage');
    echo "Backstage controller class loaded successfully.\n";
    
    // 测试Base controller
    $reflection = new ReflectionClass('app\controller\Base');
    echo "Base controller class loaded successfully.\n";
    
    // 测试Common controller
    $reflection = new ReflectionClass('app\controller\Common');
    echo "Common controller class loaded successfully.\n";
    
    echo "All controllers loaded without syntax errors.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
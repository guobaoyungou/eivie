#!/usr/bin/env php
<?php
/**
 * 直接测试模板编译，绕过登录验证
 */

define('ROOT_PATH', __DIR__ . '/');

// 加载ThinkPHP
require __DIR__ . '/vendor/autoload.php';

// 创建应用实例
$app = new think\App();
$app->initialize();

// 准备模拟数据
$data = [
    'info' => [
        'id' => 4,
        'model_id' => 1,
        'api_config_id' => 1,
        'model_params' => '{}',
        'aspect_ratio' => '1:1',
        'status' => 1,
        'is_recommend' => 0,
        'name' => '测试场景',
        'category' => '风景',
        'mdid' => 0,
        'is_public' => 1,
        'cover' => '',
        'background_url' => '',
        'desc' => '',
        'sort' => 0,
        'tags' => '',
    ],
    'models' => [
        ['id' => 1, 'model_name' => 'WANX', 'provider' => 'Alibaba'],
    ],
    'mendian_list' => [
        ['id' => 1, 'name' => '测试门店'],
    ],
];

echo "=== 开始测试模板编译 ===\n\n";

try {
    // 清除缓存
    echo "1. 清除模板缓存...\n";
    $files = glob(__DIR__ . '/runtime/temp/*.php');
    foreach ($files as $file) {
        @unlink($file);
    }
    echo "   缓存已清除\n\n";
    
    // 渲染模板
    echo "2. 编译模板...\n";
    $content = \think\facade\View::assign($data)->fetch('ai_travel_photo/scene_edit');
    
    echo "   模板编译成功！\n\n";
    
    // 查找编译后的文件
    echo "3. 查找编译后的文件...\n";
    $files = glob(__DIR__ . '/runtime/temp/*.php');
    if (empty($files)) {
        echo "   ✗ 未找到编译文件\n";
        exit(1);
    }
    
    // 按时间排序，取最新的
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $compiledFile = $files[0];
    echo "   找到: " . basename($compiledFile) . "\n\n";
    
    // PHP语法检查
    echo "4. PHP语法检查...\n";
    $output = shell_exec("php -l " . escapeshellarg($compiledFile) . " 2>&1");
    echo "   " . trim($output) . "\n\n";
    
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ ✓ ✓ 所有测试通过！场景编辑模板语法正确！\n";
        exit(0);
    } else {
        echo "✗ 存在语法错误\n\n";
        echo "=== 错误详情 ===\n";
        echo $output . "\n";
        
        // 显示错误行附近的代码
        if (preg_match('/on line (\d+)/', $output, $matches)) {
            $errorLine = intval($matches[1]);
            echo "\n=== 错误行附近代码 (" . ($errorLine - 5) . " - " . ($errorLine + 5) . ") ===\n";
            $lines = file($compiledFile);
            for ($i = max(0, $errorLine - 6); $i < min(count($lines), $errorLine + 5); $i++) {
                $prefix = ($i == $errorLine - 1) ? ">>> " : "    ";
                printf("%s%4d: %s", $prefix, $i + 1, $lines[$i]);
            }
        }
        
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "✗ 编译失败\n\n";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}

<?php
/**
 * 测试场景编辑页面的模板编译
 */

require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

// 模拟场景信息数据
$info = [
    'id' => 4,
    'model_id' => 1,
    'api_config_id' => 1,
    'model_params' => '{}',
    'aspect_ratio' => '1:1',
    'status' => '1',
    'is_recommend' => '0',
    'name' => '测试场景',
    'category' => '风景',
    'mdid' => 0,
    'is_public' => 1,
    'cover' => '',
    'background_url' => '',
    'desc' => '',
    'sort' => 0,
    'tags' => '',
];

// 模拟models数据
$models = [
    ['id' => 1, 'model_name' => 'WANX', 'provider' => 'Alibaba'],
];

// 模拟门店数据
$mendian_list = [
    ['id' => 1, 'name' => '测试门店'],
];

// 分配变量到视图
$view = \think\facade\View::assign([
    'info' => $info,
    'models' => $models,
    'mendian_list' => $mendian_list,
]);

try {
    // 尝试编译模板
    $content = \think\facade\View::fetch('ai_travel_photo/scene_edit');
    
    // 检查是否有PHP语法错误
    $tempFile = sys_get_temp_dir() . '/scene_edit_compiled_' . time() . '.php';
    file_put_contents($tempFile, $content);
    
    $output = shell_exec("php -l " . escapeshellarg($tempFile) . " 2>&1");
    
    unlink($tempFile);
    
    echo "=== 模板编译结果 ===\n";
    echo $output . "\n";
    
    if (strpos($output, 'No syntax errors') !== false) {
        echo "\n✓ 模板语法检查通过！\n";
        exit(0);
    } else {
        echo "\n✗ 模板存在语法错误\n";
        // 输出一部分编译后的内容用于调试
        echo "\n=== 编译后的脚本部分 (JavaScript区域) ===\n";
        if (preg_match('/<script>(.*?)<\/script>/s', $content, $matches)) {
            echo substr($matches[1], 0, 2000) . "...\n";
        }
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "✗ 编译失败: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}

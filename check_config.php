<?php
// 检查配置和常量定义
require __DIR__ . '/vendor/autoload.php';

// 检查配置
$config = include __DIR__ . '/config/app.php';
echo "App Debug Mode: " . (isset($config['app_debug']) ? var_export($config['app_debug'], true) : 'Not Set') . "\n";
echo "Show Error Message: " . (isset($config['show_error_msg']) ? var_export($config['show_error_msg'], true) : 'Not Set') . "\n";

// 检查环境变量
echo "Environment APP_DEBUG: " . var_export(env('app_debug', false), true) . "\n";

// 检查类是否存在
$classes = [
    'app\controller\Base',
    'app\controller\Common',
    'app\controller\Backstage'
];

foreach ($classes as $class) {
    echo "Class $class exists: " . (class_exists($class) ? 'Yes' : 'No') . "\n";
}

echo "Configuration check completed.\n";
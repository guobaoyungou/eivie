<?php
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'vendor/autoload.php';

// 加载ThinkPHP应用
$app = new \think\App();
$app->initialize();

// 测试模型保存
use app\model\AiModelInstance;

echo "测试模型保存功能...\n\n";

try {
    // 测试新增
    $model = new AiModelInstance();
    $model->aid = 0;
    $model->category_code = 'image_generation';
    $model->model_code = 'test-model-' . time();
    $model->model_name = '测试模型';
    $model->model_version = 'v1.0';
    $model->provider = 'aliyun';
    $model->description = '这是一个测试模型';
    $model->capability_tags = ['测试', '示例'];
    $model->is_system = 0;
    $model->is_active = 1;
    $model->sort = 100;
    $model->cost_per_call = 0.01;
    $model->cost_unit = 'per_call';
    $model->billing_mode = 'fixed';
    
    $result = $model->save();
    
    if ($result) {
        echo "✓ 新增成功！\n";
        echo "  ID: {$model->id}\n";
        echo "  create_time: {$model->create_time}\n";
        echo "  update_time: {$model->update_time}\n";
        echo "  时间类型: " . gettype($model->create_time) . "\n\n";
        
        // 测试更新
        $model->model_name = '测试模型（已更新）';
        $model->save();
        
        echo "✓ 更新成功！\n";
        echo "  update_time: {$model->update_time}\n\n";
        
        // 清理测试数据
        $model->delete();
        echo "✓ 清理测试数据完成\n";
    } else {
        echo "✗ 保存失败\n";
    }
    
} catch (\Exception $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
}

<?php
/**
 * 测试 scene_template_detail 接口
 */

// 引入框架
require __DIR__ . '/vendor/autoload.php';

// 初始化应用
$app = new think\App();
$app->initialize();

// 设置请求参数
$_GET['template_id'] = 22; // 测试模板ID 22
$_GET['aid'] = 1;
$_GET['platform'] = 'h5';
$_GET['session_id'] = 'test_session';

// 模拟全局变量
define('aid', 1);
define('platform', 'h5');

try {
    // 创建控制器实例
    $controller = new \app\controller\ApiAivideo();
    
    // 调用方法
    $result = $controller->scene_template_detail();
    
    // 输出结果
    echo "=== 接口调用成功 ===\n";
    echo $result;
    echo "\n\n=== JSON格式化 ===\n";
    $data = json_decode($result, true);
    if ($data) {
        echo "状态: " . ($data['status'] ?? 'unknown') . "\n";
        echo "消息: " . ($data['msg'] ?? 'no message') . "\n";
        if (isset($data['data'])) {
            echo "模板ID: " . ($data['data']['id'] ?? 'unknown') . "\n";
            echo "模板名称: " . ($data['data']['template_name'] ?? 'unknown') . "\n";
            echo "价格: " . ($data['data']['price'] ?? 'unknown') . "\n";
            echo "积分支付启用: " . ($data['data']['score_pay_enabled'] ? '是' : '否') . "\n";
            echo "AI评分单位: " . ($data['data']['ai_score_unit_name'] ?? '未设置') . "\n";
            echo "分销启用: " . ($data['data']['commission_enabled'] ? '是' : '否') . "\n";
        }
    } else {
        echo "JSON解析失败\n";
    }
    
} catch (\Exception $e) {
    echo "=== 发生异常 ===\n";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "错误位置: " . $e->getFile() . ':' . $e->getLine() . "\n";
    echo "\n堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

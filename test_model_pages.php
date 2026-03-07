<?php
/**
 * 模型管理页面访问测试脚本
 * 测试所有模型管理相关页面是否能正常访问
 */

// 测试页面列表
$pages = [
    '模型分类列表' => '/AiTravelPhoto/model_category_list',
    'API配置列表' => '/AiTravelPhoto/model_config_list',
    '调用统计' => '/AiTravelPhoto/model_usage_stats',
    '模型分类编辑' => '/AiTravelPhoto/model_category_edit',
    'API配置编辑' => '/AiTravelPhoto/model_config_edit',
];

echo "=== 模型管理页面访问测试 ===\n\n";

$baseUrl = 'http://192.168.11.222';
$success = 0;
$failed = 0;

foreach ($pages as $name => $url) {
    echo "测试: {$name}\n";
    echo "URL: {$baseUrl}{$url}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        // 检查是否包含关键内容
        $hasLayui = strpos($response, 'layui') !== false;
        $hasError = strpos($response, 'Error') !== false || strpos($response, 'error') !== false;
        
        if ($hasLayui && !$hasError) {
            echo "✓ 状态: 正常 (HTTP {$httpCode})\n";
            $success++;
        } else {
            echo "✗ 状态: 异常 - ";
            if (!$hasLayui) echo "未找到layui引用 ";
            if ($hasError) echo "页面包含错误信息 ";
            echo "\n";
            $failed++;
        }
    } else {
        echo "✗ 状态: HTTP {$httpCode}\n";
        $failed++;
    }
    
    echo "\n";
}

echo "=== 测试结果 ===\n";
echo "成功: {$success}个页面\n";
echo "失败: {$failed}个页面\n";

if ($failed == 0) {
    echo "\n✓ 所有页面访问正常！\n";
} else {
    echo "\n✗ 部分页面存在问题，请检查错误日志\n";
}

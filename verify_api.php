<?php
// 验证ApiUnifiedOrder代码
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查文件是否存在
$file = __DIR__ . '/app/controller/ApiUnifiedOrder.php';
if (!file_exists($file)) {
    die("文件不存在: $file\n");
}

// 读取文件内容
$content = file_get_contents($file);

// 检查关键方法是否存在
$checks = [
    'public function ordercount()' => false,
    'public function detail()' => false,
    'protected function getAiPickOrderDetail($order)' => false,
    'protected $detailRoutes' => false,
];

foreach ($checks as $method => $found) {
    if (strpos($content, $method) !== false) {
        $checks[$method] = true;
    }
}

echo "=== ApiUnifiedOrder 代码验证 ===\n\n";
echo "文件: $file\n";
echo "文件大小: " . filesize($file) . " bytes\n\n";

echo "方法检查:\n";
foreach ($checks as $method => $found) {
    $status = $found ? "✓ 存在" : "✗ 缺失";
    echo "  $status: $method\n";
}

// 检查路由配置
if (strpos($content, "'ai_pick'") !== false) {
    echo "\n✓ 选片订单路由配置存在\n";
} else {
    echo "\n✗ 选片订单路由配置缺失\n";
}

// 检查详情方法是否处理选片订单
if (strpos($content, "ai_travel_photo_order") !== false) {
    echo "✓ 详情方法包含选片订单处理逻辑\n";
} else {
    echo "✗ 详情方法缺少选片订单处理逻辑\n";
}

echo "\n=== 验证完成 ===\n";

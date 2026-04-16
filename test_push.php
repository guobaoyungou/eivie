<?php
/**
 * 临时测试脚本 - 测试合成完成通知推送
 * 用法: php think test_push
 * 或: php test_push.php
 */

// 使用ThinkPHP的入口
define('ROOT_PATH', __DIR__ . '/');

// 加载 config.php
if (file_exists(ROOT_PATH . 'config.php')) {
    require ROOT_PATH . 'config.php';
}

require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

if (!defined('PRE_URL')) {
    define('PRE_URL', 'https://ai.eivie.cn');
}
if (!defined('aid')) {
    define('aid', 1);
}

use think\facade\Db;

echo "=== 测试自拍端通知推送 ===\n\n";

// 1. 检查 getSiteDomain
$service = new \app\service\AiTravelPhotoSelfieService();
$domain = $service->getSiteDomain();
echo "1. getSiteDomain(): {$domain}\n\n";

// 2. 检查 portrait 220 的数据
$portrait = Db::name('ai_travel_photo_portrait')->where('id', 220)->find();
echo "2. Portrait 220:\n";
echo "   - user_openid: " . ($portrait['user_openid'] ?? 'N/A') . "\n";
echo "   - aid: " . ($portrait['aid'] ?? 'N/A') . "\n";
echo "   - source_type: " . ($portrait['source_type'] ?? 'N/A') . "\n";
echo "   - synthesis_status: " . ($portrait['synthesis_status'] ?? 'N/A') . "\n\n";

// 3. 检查 qrcode
$qrcode = Db::name('ai_travel_photo_qrcode')->where('portrait_id', 220)->where('status', 1)->find();
echo "3. QRcode: " . ($qrcode ? $qrcode['qrcode'] : 'NOT FOUND') . "\n\n";

// 4. 检查 access_token
echo "4. Testing access_token...\n";
try {
    $token = \app\common\Wechat::access_token(1, 'mp');
    echo "   access_token: " . ($token ? substr($token, 0, 20) . '...' : 'EMPTY') . "\n\n";
} catch (\Throwable $e) {
    echo "   ERROR: " . $e->getMessage() . "\n\n";
}

// 5. 直接测试推送
echo "5. Calling pushPickUrlToSelfieUser(221)...\n";
$result = $service->pushPickUrlToSelfieUser(221);
echo "   Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n\n";

// 6. 检查日志
$logFile = ROOT_PATH . 'runtime/log/202604/' . date('d') . '.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    // 取最后 2000 字节
    $tail = substr($logContent, -2000);
    echo "6. Recent log entries:\n";
    echo $tail . "\n";
}

echo "\n=== 测试完成 ===\n";

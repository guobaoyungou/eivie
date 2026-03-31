<?php
/**
 * HdSetupService 初始化测试脚本
 * 直接通过 CLI 调用，验证 Demo 初始化逻辑
 *
 * 用法：php test_hd_setup.php
 */
define('ROOT_PATH', __DIR__ . '/');

// 引导 ThinkPHP
require __DIR__ . '/vendor/autoload.php';

$http = (new \think\App())->http;
$http->run(); // 初始化框架

use app\service\hd\HdSetupService;
use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdPrize;

echo "=== HdSetupService 初始化测试 ===\n\n";

$service = new HdSetupService();

// ---- 测试1: 首次初始化 ----
echo "[TEST 1] 首次初始化...\n";
$result = $service->initDemo();
echo "  结果: code={$result['code']}, msg={$result['msg']}\n";
if ($result['code'] === 0) {
    $data = $result['data'];
    echo "  bid={$data['bid']}\n";
    echo "  activity_id={$data['activity_id']}\n";
    echo "  access_code={$data['access_code']}\n";
    echo "  screen_url={$data['screen_url']}\n";
    echo "  login_username={$data['login_username']}\n";
} else {
    echo "  *** 初始化失败 ***\n";
    exit(1);
}

$bid = $data['bid'];
$activityId = $data['activity_id'];

// ---- 测试2: 幂等性测试 ----
echo "\n[TEST 2] 幂等性测试（再次调用）...\n";
$result2 = $service->initDemo();
echo "  结果: code={$result2['code']}, msg={$result2['msg']}\n";
if ($result2['data']['bid'] === $bid && $result2['data']['activity_id'] === $activityId) {
    echo "  ✓ 幂等性通过：返回同一 bid 和 activity_id\n";
} else {
    echo "  ✗ 幂等性失败！\n";
}

// ---- 测试3: 功能完整性（17种功能） ----
echo "\n[TEST 3] 功能完整性检查...\n";
$featureCount = HdActivityFeature::where('activity_id', $activityId)->count();
echo "  activity_feature 记录数: {$featureCount}\n";
if ($featureCount === 17) {
    echo "  ✓ 17 种功能全部写入\n";
} else {
    echo "  ✗ 期望 17 种，实际 {$featureCount} 种\n";
}

$enabledCount = HdActivityFeature::where('activity_id', $activityId)->where('enabled', 1)->count();
echo "  enabled=1 的数量: {$enabledCount}\n";

// ---- 测试4: 奖品数量 ----
echo "\n[TEST 4] 奖品数量检查...\n";
$prizeCount = HdPrize::where('activity_id', $activityId)->count();
echo "  hd_prize 记录数: {$prizeCount}\n";
if ($prizeCount >= 15) {
    echo "  ✓ 奖品数量正确（>= 15）\n";
} else {
    echo "  ✗ 期望 >= 15，实际 {$prizeCount}\n";
}

// ---- 测试5: 抽奖轮次 ----
echo "\n[TEST 5] 抽奖轮次检查...\n";
$lotteryConfigCount = Db::name('hd_lottery_config')->where('activity_id', $activityId)->count();
echo "  hd_lottery_config 记录数: {$lotteryConfigCount}\n";
$roundNums = Db::name('hd_lottery_config')
    ->where('activity_id', $activityId)
    ->distinct(true)
    ->column('round_num');
sort($roundNums);
echo "  round_num 值: " . implode(',', $roundNums) . "\n";
if (count($roundNums) === 3 && $roundNums === [1, 2, 3]) {
    echo "  ✓ 3 轮轮次正确\n";
} else {
    echo "  ✗ 轮次不正确\n";
}

// ---- 测试6: 游戏主题 ----
echo "\n[TEST 6] 游戏主题检查...\n";
$gameThemeCount = Db::name('hd_game_theme')->where('activity_id', $activityId)->count();
echo "  hd_game_theme 记录数: {$gameThemeCount}\n";
if ($gameThemeCount === 11) {
    echo "  ✓ 11 种游戏主题\n";
} else {
    echo "  ✗ 期望 11，实际 {$gameThemeCount}\n";
}

// ---- 测试7: 摇一摇默认配置 ----
echo "\n[TEST 7] 摇一摇默认配置检查...\n";
$shakeConfig = Db::name('hd_shake_config')->where('activity_id', $activityId)->find();
if ($shakeConfig) {
    $ok = $shakeConfig['duration'] == 100 && $shakeConfig['max_winners'] == 3 && $shakeConfig['max_participants'] == 200;
    echo "  duration={$shakeConfig['duration']}, max_winners={$shakeConfig['max_winners']}, max_participants={$shakeConfig['max_participants']}\n";
    echo $ok ? "  ✓ 摇一摇配置正确\n" : "  ✗ 配置不匹配\n";
} else {
    echo "  ✗ 未找到摇一摇配置\n";
}

// ---- 测试8: access_code 唯一性 ----
echo "\n[TEST 8] access_code 唯一性检查...\n";
$activity = HdActivity::find($activityId);
$duplicates = HdActivity::where('access_code', $activity->access_code)->count();
echo "  access_code={$activity->access_code}, 相同记录数: {$duplicates}\n";
echo ($duplicates === 1) ? "  ✓ 唯一\n" : "  ✗ 存在重复\n";

// ---- 测试9: 弹幕配置 ----
echo "\n[TEST 9] 弹幕配置检查...\n";
$danmuConfig = Db::name('hd_danmu_config')->where('activity_id', $activityId)->find();
if ($danmuConfig) {
    echo "  speed={$danmuConfig['speed']}, font_size={$danmuConfig['font_size']}, opacity={$danmuConfig['opacity']}\n";
    $cfg = json_decode($danmuConfig['config'], true);
    echo "  textcolor=" . ($cfg['textcolor'] ?? 'N/A') . "\n";
    echo "  ✓ 弹幕配置存在\n";
} else {
    echo "  ✗ 未找到弹幕配置\n";
}

// ---- 测试10: 套餐绑定 ----
echo "\n[TEST 10] 套餐绑定检查...\n";
$bizConfig = Db::name('hd_business_config')->where('bid', $bid)->find();
if ($bizConfig && $bizConfig['plan_id'] > 0 && $bizConfig['plan_expire_time'] > time()) {
    echo "  plan_id={$bizConfig['plan_id']}, expire_time=" . date('Y-m-d H:i:s', $bizConfig['plan_expire_time']) . "\n";
    echo "  ✓ 套餐绑定有效\n";
} else {
    echo "  ✗ 套餐绑定无效\n";
}

// ---- 测试11: 红包配置 ----
echo "\n[TEST 11] 红包配置检查...\n";
$rpConfig = Db::name('hd_redpacket_config')->where('activity_id', $activityId)->find();
$rpRound = Db::name('hd_redpacket_round')->where('activity_id', $activityId)->find();
if ($rpConfig && $rpRound) {
    echo "  duration={$rpConfig['duration']}, min={$rpConfig['min_amount']}, max={$rpConfig['max_amount']}\n";
    echo "  ✓ 红包配置和轮次存在\n";
} else {
    echo "  ✗ 红包配置不完整\n";
}

// ---- 测试12: 背景与音乐 ----
echo "\n[TEST 12] 背景与音乐检查...\n";
$bgCount = Db::name('hd_background')->where('activity_id', $activityId)->count();
$musicCount = Db::name('hd_music')->where('activity_id', $activityId)->count();
echo "  背景记录数: {$bgCount}, 音乐记录数: {$musicCount}\n";
echo ($bgCount >= 13 && $musicCount >= 1) ? "  ✓ 背景和音乐初始化正确\n" : "  ✗ 数量不足\n";

echo "\n=== 测试完成 ===\n";

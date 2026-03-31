<?php
/**
 * 活动管理系统后台服务集成测试
 * 验证所有 8 个服务 + 10 个控制器的可用性
 *
 * 用法：php test_hd_integration.php
 */
define('ROOT_PATH', __DIR__ . '/');
require __DIR__ . '/vendor/autoload.php';
$http = (new \think\App())->http;
$http->run();

use think\facade\Db;
use app\service\hd\HdSignService;
use app\service\hd\HdLotteryService;
use app\service\hd\HdSpeedService;
use app\service\hd\HdRedpacketService;
use app\service\hd\HdWallService;
use app\service\hd\HdThemeService;
use app\service\hd\HdAlbumService;
use app\service\hd\HdVoteService;

echo "=== 活动管理系统后台集成测试 ===\n\n";

// 获取已初始化的 demo 数据
$business = Db::name('business')->where('name', '贵州果宝电子商务有限公司')->find();
if (!$business) {
    echo "请先运行 test_hd_setup.php 初始化 Demo 数据\n";
    exit(1);
}

$bid = (int)$business['id'];
$admin = Db::name('admin')->order('id asc')->find();
$aid = $admin ? (int)$admin['id'] : 1;
$activity = \app\model\hd\HdActivity::where('bid', $bid)->find();
if (!$activity) {
    echo "未找到 Demo 活动\n";
    exit(1);
}
$activityId = (int)$activity->id;

echo "测试环境: aid={$aid}, bid={$bid}, activityId={$activityId}\n\n";

$passed = 0;
$failed = 0;

function test($name, $result) {
    global $passed, $failed;
    $ok = ($result['code'] ?? 1) === 0;
    if ($ok) { $passed++; echo "  ✓ {$name}\n"; }
    else { $failed++; echo "  ✗ {$name} — {$result['msg']}\n"; }
    return $ok;
}

// ---- 1. HdSignService ----
echo "[1] HdSignService\n";
$signSvc = new HdSignService();
test('getSignConfig', $signSvc->getSignConfig($aid, $bid, $activityId));
test('updateSignConfig', $signSvc->updateSignConfig($aid, $bid, $activityId, ['sign_match_mode' => 2]));
test('getSignList', $signSvc->getSignList($aid, $bid, $activityId));
test('getMobilePageConfig', $signSvc->getMobilePageConfig($aid, $bid, $activityId));
test('updateMobilePageConfig', $signSvc->updateMobilePageConfig($aid, $bid, $activityId, ['mobile_btn_text' => '立即签到']));

// ---- 2. HdLotteryService ----
echo "\n[2] HdLotteryService\n";
$lotterySvc = new HdLotteryService();
test('getPrizes', $lotterySvc->getPrizes($aid, $bid, $activityId));
$prizeResult = $lotterySvc->createPrize($aid, $bid, $activityId, ['name' => '测试奖品', 'total_num' => 50]);
test('createPrize', $prizeResult);
if ($prizeResult['code'] === 0) {
    $testPrizeId = $prizeResult['data']['id'];
    test('updatePrize', $lotterySvc->updatePrize($aid, $bid, $activityId, $testPrizeId, ['name' => '测试奖品-改']));
    test('deletePrize', $lotterySvc->deletePrize($aid, $bid, $activityId, $testPrizeId));
}
test('getRounds', $lotterySvc->getRounds($aid, $bid, $activityId));
test('getThemes', $lotterySvc->getThemes($aid, $bid, $activityId));
test('getChoujiangConfig', $lotterySvc->getChoujiangConfig($aid, $bid, $activityId));
test('updateChoujiangConfig', $lotterySvc->updateChoujiangConfig($aid, $bid, $activityId, ['max_times' => 3]));
test('getImportList', $lotterySvc->getImportList($aid, $bid, $activityId));
$importResult = $lotterySvc->batchImport($aid, $bid, $activityId, [
    ['name' => '张三', 'phone' => '13800000001', 'code' => 'T001'],
    ['name' => '李四', 'phone' => '13800000002', 'code' => 'T002'],
]);
test('batchImport', $importResult);
test('clearImportList', $lotterySvc->clearImportList($aid, $bid, $activityId));

// ---- 3. HdSpeedService ----
echo "\n[3] HdSpeedService\n";
$speedSvc = new HdSpeedService();
test('getShakeConfig', $speedSvc->getShakeConfig($aid, $bid, $activityId));
test('updateShakeConfig', $speedSvc->updateShakeConfig($aid, $bid, $activityId, ['duration' => 60]));
test('getShakeThemes', $speedSvc->getShakeThemes($aid, $bid, $activityId));
test('getShakeRanking', $speedSvc->getShakeRanking($aid, $bid, $activityId));
test('getGameConfig', $speedSvc->getGameConfig($aid, $bid, $activityId));
test('updateGameConfig', $speedSvc->updateGameConfig($aid, $bid, $activityId, ['duration' => 45]));
test('getGameThemes', $speedSvc->getGameThemes($aid, $bid, $activityId));
test('getGameRanking', $speedSvc->getGameRanking($aid, $bid, $activityId));

// ---- 4. HdRedpacketService ----
echo "\n[4] HdRedpacketService\n";
$rpSvc = new HdRedpacketService();
test('getConfig', $rpSvc->getConfig($aid, $bid, $activityId));
test('updateConfig', $rpSvc->updateConfig($aid, $bid, $activityId, ['duration' => 45, 'total_amount' => 100]));
test('getRounds', $rpSvc->getRounds($aid, $bid, $activityId));
$rpRoundResult = $rpSvc->createRound($aid, $bid, $activityId, ['total_amount' => 50, 'total_num' => 10]);
test('createRound', $rpRoundResult);
if ($rpRoundResult['code'] === 0) {
    $testRpRoundId = $rpRoundResult['data']['id'];
    test('updateRound', $rpSvc->updateRound($aid, $bid, $activityId, $testRpRoundId, ['total_amount' => 60]));
    test('deleteRound', $rpSvc->deleteRound($aid, $bid, $activityId, $testRpRoundId));
}
test('getWinRecords', $rpSvc->getWinRecords($aid, $bid, $activityId));

// ---- 5. HdWallService ----
echo "\n[5] HdWallService\n";
$wallSvc = new HdWallService();
test('getWallConfig', $wallSvc->getWallConfig($aid, $bid, $activityId));
test('updateWallConfig', $wallSvc->updateWallConfig($aid, $bid, $activityId, ['need_approve' => 0]));
test('getDanmuConfig', $wallSvc->getDanmuConfig($aid, $bid, $activityId));
test('updateDanmuConfig', $wallSvc->updateDanmuConfig($aid, $bid, $activityId, ['speed' => 5]));
test('getMessages', $wallSvc->getMessages($aid, $bid, $activityId));
test('publishNotice', $wallSvc->publishNotice($aid, $bid, $activityId, ['content' => '测试公告']));

// ---- 6. HdThemeService ----
echo "\n[6] HdThemeService\n";
$themeSvc = new HdThemeService();
test('getKaimuConfig', $themeSvc->getKaimuConfig($aid, $bid, $activityId));
test('updateKaimuConfig', $themeSvc->updateKaimuConfig($aid, $bid, $activityId, ['title' => '测试开幕']));
test('getBimuConfig', $themeSvc->getBimuConfig($aid, $bid, $activityId));
test('updateBimuConfig', $themeSvc->updateBimuConfig($aid, $bid, $activityId, ['title' => '测试闭幕']));
test('getBackgrounds', $themeSvc->getBackgrounds($aid, $bid, $activityId));
$bgResult = $themeSvc->addBackground($aid, $bid, $activityId, ['feature_code' => 'qdq', 'image_url' => '/test.jpg']);
test('addBackground', $bgResult);
if ($bgResult['code'] === 0) {
    test('deleteBackground', $themeSvc->deleteBackground($aid, $bid, $activityId, $bgResult['data']['id']));
}
test('getMusics', $themeSvc->getMusics($aid, $bid, $activityId));
test('getQrcodeConfig', $themeSvc->getQrcodeConfig($aid, $bid, $activityId));
test('updateQrcodeConfig', $themeSvc->updateQrcodeConfig($aid, $bid, $activityId, ['qrcode_text' => '扫我']));

// ---- 7. HdAlbumService ----
echo "\n[7] HdAlbumService\n";
$albumSvc = new HdAlbumService();
test('getAlbumConfig', $albumSvc->getAlbumConfig($aid, $bid, $activityId));
test('updateAlbumConfig', $albumSvc->updateAlbumConfig($aid, $bid, $activityId, ['play_interval' => 3]));
test('getPhotos', $albumSvc->getPhotos($aid, $bid, $activityId));
$photoResult = $albumSvc->addPhoto($aid, $bid, $activityId, ['file_name' => 'test.jpg', 'file_path' => '/test.jpg']);
test('addPhoto', $photoResult);
if ($photoResult['code'] === 0) {
    test('deletePhoto', $albumSvc->deletePhoto($aid, $bid, $activityId, $photoResult['data']['id']));
}

// ---- 8. HdVoteService ----
echo "\n[8] HdVoteService\n";
$voteSvc = new HdVoteService();
test('getItems', $voteSvc->getItems($aid, $bid, $activityId));
$voteItemResult = $voteSvc->createItem($aid, $bid, $activityId, ['title' => '测试选项E', 'sort' => 5]);
test('createItem', $voteItemResult);
if ($voteItemResult['code'] === 0) {
    $testVoteItemId = $voteItemResult['data']['id'];
    test('updateItem', $voteSvc->updateItem($aid, $bid, $activityId, $testVoteItemId, ['title' => '测试选项E-改']));
    test('deleteItem', $voteSvc->deleteItem($aid, $bid, $activityId, $testVoteItemId));
}
test('getStats', $voteSvc->getStats($aid, $bid, $activityId));

// ---- 验证类实例化 ----
echo "\n[9] 控制器类实例化验证\n";
$controllers = [
    'HdSignController', 'HdLotteryController', 'HdSpeedController',
    'HdRedpacketController', 'HdWallController', 'HdThemeController',
    'HdAlbumController', 'HdVoteController', 'HdSwitchController', 'HdSettingController',
];
foreach ($controllers as $ctrl) {
    $class = "\\app\\controller\\hd\\{$ctrl}";
    if (class_exists($class)) {
        $passed++;
        echo "  ✓ {$ctrl} 类存在\n";
    } else {
        $failed++;
        echo "  ✗ {$ctrl} 类不存在\n";
    }
}

echo "\n=== 测试完成: {$passed} 通过, {$failed} 失败 ===\n";
exit($failed > 0 ? 1 : 0);

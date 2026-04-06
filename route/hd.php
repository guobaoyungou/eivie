<?php
/**
 * 大屏互动系统路由配置
 * 部署于独立域名 wxhd.eivie.cn
 */
use think\facade\Route;

// ============================================================
// CORS 跨域中间件
// ============================================================
$hdCors = \app\middleware\hd\HdCors::class;
$hdTenant = \app\middleware\hd\TenantResolver::class;
$hdAuth = \app\middleware\hd\HdAuthMiddleware::class;
$hdPlan = \app\middleware\hd\PlanPermission::class;
$hdAdminAuth = \app\middleware\hd\AdminAuth::class;
$hdActivityStatus = \app\middleware\hd\ActivityStatus::class;
$hdWeChatOAuth = \app\middleware\hd\WeChatOAuth::class;

// ============================================================
// 1. 认证 API（无需登录）
// ============================================================
Route::group('api/hd/auth', function () {
    Route::post('register', 'hd.HdAuthController/register');
    Route::post('login', 'hd.HdAuthController/login');
    Route::post('logout', 'hd.HdAuthController/logout');
    // 微信授权登录
    Route::get('wx-oauth-url', 'hd.HdAuthController/wxOauthUrl');
    Route::post('wx-login', 'hd.HdAuthController/wxLogin');
    Route::post('wx-bind', 'hd.HdAuthController/wxBind');
    // 手机绑定短信验证码
    Route::post('send-bind-code', 'hd.HdAuthController/sendBindCode');
    // 公众号二维码扫码登录
    Route::get('qr-code', 'hd.HdAuthController/qrCode');
    Route::get('qr-check', 'hd.HdAuthController/qrCheck');
})->middleware([$hdCors, $hdTenant]);

// 需要登录的认证路由
Route::group('api/hd/auth', function () {
    Route::get('profile', 'hd.HdAuthController/profile');
    Route::put('profile', 'hd.HdAuthController/updateProfile');
    Route::post('profile', 'hd.HdAuthController/updateProfile');
})->middleware([$hdCors, $hdTenant, $hdAuth]);

// ============================================================
// 2. 门店管理 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/stores', function () {
    Route::get('', 'hd.HdStoreController/index');
    Route::post('', 'hd.HdStoreController/create');
    Route::get(':id', 'hd.HdStoreController/detail');
    Route::put(':id', 'hd.HdStoreController/update');
    Route::post(':id/update', 'hd.HdStoreController/update');
    Route::delete(':id', 'hd.HdStoreController/delete');
    Route::post(':id/delete', 'hd.HdStoreController/delete');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 3. 活动管理 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/activities', function () {
    Route::get('', 'hd.HdActivityController/index');
    Route::post('', 'hd.HdActivityController/create');
    Route::get(':id', 'hd.HdActivityController/detail')->pattern(['id' => '\d+']);
    Route::put(':id', 'hd.HdActivityController/update')->pattern(['id' => '\d+']);
    Route::post(':id/update', 'hd.HdActivityController/update')->pattern(['id' => '\d+']);
    Route::delete(':id', 'hd.HdActivityController/delete')->pattern(['id' => '\d+']);
    Route::post(':id/delete', 'hd.HdActivityController/delete')->pattern(['id' => '\d+']);
    Route::put(':id/status', 'hd.HdActivityController/updateStatus')->pattern(['id' => '\d+']);
    Route::post(':id/status', 'hd.HdActivityController/updateStatus')->pattern(['id' => '\d+']);
    Route::get(':id/features', 'hd.HdActivityController/features')->pattern(['id' => '\d+']);
    Route::put(':id/features/:code', 'hd.HdActivityController/updateFeature')->pattern(['id' => '\d+']);
    Route::post(':id/features/:code', 'hd.HdActivityController/updateFeature')->pattern(['id' => '\d+']);
    Route::get(':id/participants', 'hd.HdActivityController/participants')->pattern(['id' => '\d+']);
    Route::get(':id/stats', 'hd.HdActivityController/stats')->pattern(['id' => '\d+']);
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// 功能列表（无需套餐权限）
Route::get('api/hd/features', 'hd.HdActivityController/allFeatures')->middleware([$hdCors, $hdTenant, $hdAuth]);

// 套餐列表（无需套餐权限，未购买套餐的用户也需查看）
Route::get('api/hd/plans', 'hd.HdPlanController/list')->middleware([$hdCors, $hdTenant, $hdAuth]);

// ============================================================
// 3.5 文件上传 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/upload', function () {
    Route::post('image', 'hd.HdUploadController/image');
    Route::post('background', 'hd.HdUploadController/background');
    Route::post('music', 'hd.HdUploadController/music');
    Route::get('backgrounds', 'hd.HdUploadController/backgrounds');
    Route::get('musics', 'hd.HdUploadController/musics');
    Route::delete('background/:id', 'hd.HdUploadController/deleteBackground');
    Route::post('background/:id/delete', 'hd.HdUploadController/deleteBackground');
    Route::delete('music/:id', 'hd.HdUploadController/deleteMusic');
    Route::post('music/:id/delete', 'hd.HdUploadController/deleteMusic');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 3.6 微信 JS-SDK 配置（无需商家登录，大屏/手机端使用）
// ============================================================
Route::get('api/hd/wx/jssdk', 'hd.HdWxJssdkController/config')->middleware([$hdCors, $hdTenant]);

// ============================================================
// 3.7 微信事件回调（公众号服务器配置URL，无需登录/租户）
// 用于接收微信服务号的事件推送（扫码关注等）
// 微信公众号后台「服务器配置」URL 填写: https://wxhd.eivie.cn/api/hd/wx/callback
// ============================================================
Route::rule('api/hd/wx/callback', 'hd.HdWxCallbackController/handle', 'GET|POST');

// ============================================================
// 4. 大屏/互动 API（通过 access_code 访问，无需商家登录）
// ============================================================
Route::group('api/hd/screen/:access_code', function () {
    Route::get('config', 'hd.HdScreenController/config');
    Route::get('sign-list', 'hd.HdScreenController/signList');
    Route::post('sign', 'hd.HdScreenController/sign');
    Route::post('sign-sms', 'hd.HdScreenController/sendSignSms');
    Route::get('wall', 'hd.HdScreenController/wall');
    Route::post('wall', 'hd.HdScreenController/sendWall');
    Route::post('lottery/draw', 'hd.HdScreenController/lotteryDraw');
    Route::get('shake/status', 'hd.HdScreenController/shakeStatus');
    Route::post('shake/score', 'hd.HdScreenController/shakeScore');
    Route::post('redpacket/grab', 'hd.HdScreenController/redpacketGrab');
    Route::post('vote', 'hd.HdScreenController/vote');
    Route::get('danmu', 'hd.HdScreenController/danmu');
    Route::post('danmu', 'hd.HdScreenController/sendDanmu');
    // 大屏数据查询（抽奖轮次、投票选项、相册照片、开幕墙、闭幕墙）
    Route::get('lottery/rounds', 'hd.HdScreenController/lotteryRounds');
    Route::get('vote/items', 'hd.HdScreenController/voteItems');
    Route::get('album/photos', 'hd.HdScreenController/albumPhotos');
    Route::get('theme/kaimu', 'hd.HdScreenController/kaimu');
    Route::get('theme/bimu', 'hd.HdScreenController/bimu');
    // SSE 实时推送
    Route::get('sse', 'hd.HdSseController/stream');
    // 管理员 API
    Route::get('admin/check', 'hd.HdScreenController/adminCheck');
    Route::get('admin/features', 'hd.HdScreenController/adminFeatures');
    Route::post('admin/feature-toggle', 'hd.HdScreenController/adminFeatureToggle');
    Route::post('admin/lottery-draw', 'hd.HdScreenController/adminLotteryDraw');
    // 核销员 API（预留）
    Route::get('verify/check', 'hd.HdScreenController/verifyCheck');
    Route::get('verify/orders', 'hd.HdScreenController/verifyOrders');
    Route::post('verify/order', 'hd.HdScreenController/verifyOrder');
})->middleware([$hdCors, $hdTenant]);

// ============================================================
// 5. 大屏 iframe 功能页路由（必须在入口页路由之前，否则会被前缀匹配拦截）
// ============================================================
Route::get('s/:access_code/wall/:feature', 'hd.HdEntryController/wallPage')->middleware([$hdCors, $hdTenant]);

// ============================================================
// 5.05 模块代理路由（老系统 Lottery/Game 等模块 PHP 代理）
// ============================================================
Route::rule('s/:access_code/module/:m/:c/:a', 'hd.HdModuleProxyController/proxy', 'GET|POST')->middleware([$hdCors, $hdTenant]);

// ============================================================
// 5.1 活动入口页路由（大屏/手机端自适应 HTML 页面）
// ============================================================
Route::get('s/:access_code', 'hd.HdEntryController/index')->completeMatch()->middleware([$hdCors, $hdTenant, $hdWeChatOAuth]);

// ============================================================
// 5.2 AJAX 桥接路由（老前端 JS 请求映射到新后端 API）
// ============================================================
Route::group('s/:access_code/ajax', function () {
    Route::get('countperson', 'hd.HdAjaxBridgeController/countPerson');
    Route::get('get_sign', 'hd.HdAjaxBridgeController/getSign');
    Route::get('get_new_sign', 'hd.HdAjaxBridgeController/getNewSign');
    Route::get('new_msg', 'hd.HdAjaxBridgeController/newMsg');
    Route::rule('vote', 'hd.HdAjaxBridgeController/voteAction', 'GET|POST');
    Route::get('vote_status', 'hd.HdAjaxBridgeController/voteStatus');
    Route::get('vote_record', 'hd.HdAjaxBridgeController/voteRecord');
    Route::get('danmu_config', 'hd.HdAjaxBridgeController/danmuConfig');
    Route::get('danmu', 'hd.HdAjaxBridgeController/danmuGet');
    Route::rule('redpacket', 'hd.HdAjaxBridgeController/redpacketAction', 'GET|POST');
    Route::rule('xyh', 'hd.HdAjaxBridgeController/xyhAction', 'GET|POST');
    Route::rule('xysjh', 'hd.HdAjaxBridgeController/xysjhAction', 'GET|POST');
    Route::rule('lottery', 'hd.HdAjaxBridgeController/lotteryAction', 'GET|POST');
    Route::rule('login', 'hd.HdAjaxBridgeController/login', 'GET|POST');
    Route::rule('shake', 'hd.HdAjaxBridgeController/shakeAction', 'GET|POST');
    Route::get('defaultqrcode', 'hd.HdAjaxBridgeController/defaultQrcode');
    Route::rule('set_qrcodepos', 'hd.HdAjaxBridgeController/setQrcodePos', 'GET|POST');
    // 新增端点
    Route::rule('set_bgmusic', 'hd.HdAjaxBridgeController/setBgmusic', 'GET|POST');
    Route::get('get_new_qd', 'hd.HdAjaxBridgeController/getNewQd');
    Route::get('shake_result', 'hd.HdAjaxBridgeController/shakeResultPage');
    Route::rule('lottory_remove_user', 'hd.HdAjaxBridgeController/lotteryRemoveUser', 'GET|POST');
})->middleware([$hdCors, $hdTenant]);

// ============================================================
// 6. 平台超管 API
// ============================================================
Route::group('api/hd/admin', function () {
    Route::get('tenants', 'hd.HdAdminController/tenants');
    Route::put('tenants/:id/status', 'hd.HdAdminController/updateTenantStatus');
    Route::post('tenants/:id/status', 'hd.HdAdminController/updateTenantStatus');
    Route::get('plans', 'hd.HdAdminController/plans');
    Route::post('plans', 'hd.HdAdminController/createPlan');
    Route::put('plans/:id', 'hd.HdAdminController/updatePlan');
    Route::post('plans/:id/update', 'hd.HdAdminController/updatePlan');
    Route::get('stats', 'hd.HdAdminController/stats');
    Route::post('setup-demo', 'hd.HdAdminController/setupDemo');
})->middleware([$hdCors, $hdTenant, $hdAdminAuth]);

// ============================================================
// 7. 密码重置（无需登录）
// ============================================================
Route::group('api/hd/password', function () {
    Route::post('send-code', 'hd.HdAuthController/sendResetCode');
    Route::post('reset', 'hd.HdAuthController/resetPassword');
})->middleware([$hdCors]);

// ============================================================
// 8. 数据导出（需要登录）
// ============================================================
Route::group('api/hd/export', function () {
    Route::get('participants/:activity_id', 'hd.HdExportController/participants');
    Route::get('messages/:activity_id', 'hd.HdExportController/messages');
    Route::get('lottery/:activity_id', 'hd.HdExportController/lottery');
})->middleware([$hdCors, $hdTenant, $hdAuth]);

// ============================================================
// 9. 活动克隆（需要登录 + 套餐权限）
// ============================================================
Route::post('api/hd/activities/:id/clone', 'hd.HdActivityController/cloneActivity')->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan])->pattern(['id' => '\d+']);

// ============================================================
// 10. 签到管理 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/sign', function () {
    Route::get(':activity_id/config', 'hd.HdSignController/config');
    Route::post(':activity_id/config', 'hd.HdSignController/updateConfig');
    Route::get(':activity_id/list', 'hd.HdSignController/signList');
    Route::delete(':activity_id/participant/:id', 'hd.HdSignController/deleteParticipant');
    Route::post(':activity_id/participant/:id/delete', 'hd.HdSignController/deleteParticipant');
    Route::post(':activity_id/clear', 'hd.HdSignController/clearSignList');
    Route::get(':activity_id/mobile-config', 'hd.HdSignController/mobileConfig');
    Route::post(':activity_id/mobile-config', 'hd.HdSignController/updateMobileConfig');
    Route::get(':activity_id/mobile', 'hd.HdSignController/mobileConfig');
    Route::post(':activity_id/mobile', 'hd.HdSignController/updateMobileConfig');
    // 大屏密码管理
    Route::get(':activity_id/screen-password', 'hd.HdSignController/screenPasswordConfig');
    Route::post(':activity_id/screen-password', 'hd.HdSignController/updateScreenPasswordConfig');
    // 参与者角色管理
    Route::post(':activity_id/participant/:id/toggle-admin', 'hd.HdSignController/toggleAdmin');
    Route::post(':activity_id/participant/:id/toggle-verifier', 'hd.HdSignController/toggleVerifier');
    // 3D签到管理
    Route::get(':activity_id/3d-config', 'hd.HdThreeDSignController/getConfig');
    Route::post(':activity_id/3d-config', 'hd.HdThreeDSignController/saveConfig');
    Route::post(':activity_id/3d-effects/add', 'hd.HdThreeDSignController/addEffect');
    Route::post(':activity_id/3d-effects/:effect_id/delete', 'hd.HdThreeDSignController/deleteEffect');
    Route::post(':activity_id/3d-effects/reorder', 'hd.HdThreeDSignController/reorderEffects');
    Route::post(':activity_id/3d-effects/upload-logo', 'hd.HdThreeDSignController/uploadLogo');
    // 白名单管理
    Route::get(':activity_id/whitelist', 'hd.HdSignController/whitelist');
    Route::post(':activity_id/whitelist', 'hd.HdSignController/saveWhitelist');
    Route::post(':activity_id/whitelist/:id', 'hd.HdSignController/updateWhitelist');
    Route::delete(':activity_id/whitelist/:id', 'hd.HdSignController/deleteWhitelist');
    Route::post(':activity_id/whitelist/:id/delete', 'hd.HdSignController/deleteWhitelist');
    Route::delete(':activity_id/whitelist/clear', 'hd.HdSignController/clearWhitelist');
    Route::post(':activity_id/whitelist/clear', 'hd.HdSignController/clearWhitelist');
    // 导入导出功能
    Route::get('import', 'hd.HdSignController/import');
    Route::post('import', 'hd.HdSignController/doImport');
    Route::get('export', 'hd.HdSignController/export');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 11. 抽奖管理 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/lottery', function () {
    // 奖品管理
    Route::get(':activity_id/prizes', 'hd.HdLotteryController/prizes');
    Route::post(':activity_id/prizes', 'hd.HdLotteryController/createPrize');
    Route::post(':activity_id/prizes/:id/update', 'hd.HdLotteryController/updatePrize');
    Route::post(':activity_id/prizes/:id/delete', 'hd.HdLotteryController/deletePrize');
    // 抽奖轮次
    Route::get(':activity_id/rounds', 'hd.HdLotteryController/rounds');
    Route::post(':activity_id/rounds', 'hd.HdLotteryController/createRound');
    Route::post(':activity_id/rounds/:id/update', 'hd.HdLotteryController/updateRound');
    Route::post(':activity_id/rounds/:id/delete', 'hd.HdLotteryController/deleteRound');
    Route::post(':activity_id/rounds/:id/reset', 'hd.HdLotteryController/resetRound');
    // 抽奖主题
    Route::get(':activity_id/themes', 'hd.HdLotteryController/themes');
    Route::post(':activity_id/themes', 'hd.HdLotteryController/createTheme');
    Route::post(':activity_id/themes/:id/update', 'hd.HdLotteryController/updateTheme');
    Route::post(':activity_id/themes/:id/delete', 'hd.HdLotteryController/deleteTheme');
    // 手机抽奖
    Route::get(':activity_id/choujiang', 'hd.HdLotteryController/choujiangConfig');
    Route::post(':activity_id/choujiang', 'hd.HdLotteryController/updateChoujiangConfig');
    // 导入抽奖
    Route::get(':activity_id/import', 'hd.HdLotteryController/importList');
    Route::post(':activity_id/import', 'hd.HdLotteryController/batchImport');
    Route::post(':activity_id/import/clear', 'hd.HdLotteryController/clearImportList');
    // 中奖名单
    Route::get(':activity_id/winners', 'hd.HdLotteryController/winners');
    Route::post(':activity_id/winners/:id/give', 'hd.HdLotteryController/givePrize');
    Route::post(':activity_id/winners/:id/cancel', 'hd.HdLotteryController/cancelPrize');
    Route::post(':activity_id/winners/:id/delete', 'hd.HdLotteryController/deleteWinner');
    Route::post(':activity_id/winners/clear', 'hd.HdLotteryController/clearWinners');
    // 内定名单
    Route::get(':activity_id/designated', 'hd.HdLotteryController/designated');
    Route::post(':activity_id/designated', 'hd.HdLotteryController/addDesignated');
    Route::post(':activity_id/designated/:id/cancel', 'hd.HdLotteryController/cancelDesignated');
    Route::get(':activity_id/designated/search-users', 'hd.HdLotteryController/searchUsers');
    // 幸运手机号
    Route::get(':activity_id/lucky-phone', 'hd.HdLotteryController/luckyPhoneRecords');
    // 幸运号码
    Route::get(':activity_id/lucky-number/config', 'hd.HdLotteryController/luckyNumberConfig');
    Route::post(':activity_id/lucky-number/config', 'hd.HdLotteryController/updateLuckyNumberConfig');
    Route::get(':activity_id/lucky-number/records', 'hd.HdLotteryController/luckyNumberRecords');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 12. 拼手速 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/speed', function () {
    // 摇一摇竞技
    Route::get(':activity_id/shake/config', 'hd.HdSpeedController/shakeConfig');
    Route::post(':activity_id/shake/config', 'hd.HdSpeedController/updateShakeConfig');
    Route::get(':activity_id/shake/themes', 'hd.HdSpeedController/shakeThemes');
    Route::post(':activity_id/shake/themes/:id/update', 'hd.HdSpeedController/updateShakeTheme');
    Route::get(':activity_id/shake/ranking', 'hd.HdSpeedController/shakeRanking');
    Route::post(':activity_id/shake/reset', 'hd.HdSpeedController/resetShake');
    // 互动游戏
    Route::get(':activity_id/game/config', 'hd.HdSpeedController/gameConfig');
    Route::post(':activity_id/game/config', 'hd.HdSpeedController/updateGameConfig');
    Route::get(':activity_id/game/themes', 'hd.HdSpeedController/gameThemes');
    Route::post(':activity_id/game/themes/:id/update', 'hd.HdSpeedController/updateGameTheme');
    Route::get(':activity_id/game/ranking', 'hd.HdSpeedController/gameRanking');
    Route::post(':activity_id/game/reset', 'hd.HdSpeedController/resetGame');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 13. 红包互动 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/redpacket', function () {
    Route::get(':activity_id/config', 'hd.HdRedpacketController/config');
    Route::post(':activity_id/config', 'hd.HdRedpacketController/updateConfig');
    Route::get(':activity_id/rounds', 'hd.HdRedpacketController/rounds');
    Route::post(':activity_id/rounds', 'hd.HdRedpacketController/createRound');
    Route::post(':activity_id/rounds/:id/update', 'hd.HdRedpacketController/updateRound');
    Route::post(':activity_id/rounds/:id/delete', 'hd.HdRedpacketController/deleteRound');
    Route::get(':activity_id/records', 'hd.HdRedpacketController/winRecords');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 14. 弹幕互动 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/wall', function () {
    Route::get(':activity_id/config', 'hd.HdWallController/wallConfig');
    Route::post(':activity_id/config', 'hd.HdWallController/updateWallConfig');
    Route::get(':activity_id/danmu-config', 'hd.HdWallController/danmuConfig');
    Route::post(':activity_id/danmu-config', 'hd.HdWallController/updateDanmuConfig');
    Route::get(':activity_id/messages', 'hd.HdWallController/messages');
    Route::post(':activity_id/messages/:id/approve', 'hd.HdWallController/approveMessage');
    Route::post(':activity_id/messages/batch-approve', 'hd.HdWallController/batchApprove');
    Route::post(':activity_id/messages/:id/delete', 'hd.HdWallController/deleteMessage');
    Route::post(':activity_id/messages/:id/toggle-top', 'hd.HdWallController/toggleTop');
    Route::post(':activity_id/notice', 'hd.HdWallController/publishNotice');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 15. 主题展示 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/theme', function () {
    Route::get(':activity_id/kaimu', 'hd.HdThemeController/kaimuConfig');
    Route::post(':activity_id/kaimu', 'hd.HdThemeController/updateKaimuConfig');
    Route::get(':activity_id/bimu', 'hd.HdThemeController/bimuConfig');
    Route::post(':activity_id/bimu', 'hd.HdThemeController/updateBimuConfig');
    Route::get(':activity_id/backgrounds', 'hd.HdThemeController/backgrounds');
    Route::post(':activity_id/backgrounds', 'hd.HdThemeController/addBackground');
    Route::post(':activity_id/backgrounds/reset', 'hd.HdThemeController/resetBackground');
    Route::post(':activity_id/backgrounds/:id/update', 'hd.HdThemeController/updateBackground');
    Route::post(':activity_id/backgrounds/:id/delete', 'hd.HdThemeController/deleteBackground');
    Route::get(':activity_id/musics', 'hd.HdThemeController/musics');
    Route::post(':activity_id/musics', 'hd.HdThemeController/addMusic');
    Route::post(':activity_id/musics/:id/update', 'hd.HdThemeController/updateMusic');
    Route::post(':activity_id/musics/:id/delete', 'hd.HdThemeController/deleteMusic');
    // 背景音乐管理（weixin_music 表，按功能模块）
    Route::get(':activity_id/bgmusics', 'hd.HdThemeController/bgMusics');
    Route::post(':activity_id/bgmusics/toggle', 'hd.HdThemeController/toggleBgMusic');
    Route::post(':activity_id/bgmusics/upload', 'hd.HdThemeController/uploadBgMusic');
    Route::get(':activity_id/qrcode', 'hd.HdThemeController/qrcodeConfig');
    Route::post(':activity_id/qrcode', 'hd.HdThemeController/updateQrcodeConfig');
    // 签到主题配置
    Route::get(':activity_id/sign-theme', 'hd.HdThemeController/signThemeConfig');
    Route::post(':activity_id/sign-theme', 'hd.HdThemeController/updateSignThemeConfig');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 16. 相册PPT API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/album', function () {
    Route::get(':activity_id/config', 'hd.HdAlbumController/config');
    Route::post(':activity_id/config', 'hd.HdAlbumController/updateConfig');
    Route::get(':activity_id/photos', 'hd.HdAlbumController/photos');
    Route::post(':activity_id/photos', 'hd.HdAlbumController/addPhoto');
    Route::post(':activity_id/photos/batch', 'hd.HdAlbumController/batchAddPhotos');
    Route::post(':activity_id/photos/:id/delete', 'hd.HdAlbumController/deletePhoto');
    Route::post(':activity_id/clear', 'hd.HdAlbumController/clearAlbum');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 17. 投票设置 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/vote', function () {
    Route::get(':activity_id/items', 'hd.HdVoteController/items');
    Route::post(':activity_id/items', 'hd.HdVoteController/createItem');
    Route::post(':activity_id/items/:id/update', 'hd.HdVoteController/updateItem');
    Route::post(':activity_id/items/:id/delete', 'hd.HdVoteController/deleteItem');
    Route::get(':activity_id/stats', 'hd.HdVoteController/stats');
    Route::post(':activity_id/reset', 'hd.HdVoteController/resetVotes');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 18. 功能开关 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/switch', function () {
    Route::get(':activity_id', 'hd.HdSwitchController/index');
    Route::post(':activity_id/batch', 'hd.HdSwitchController/batchUpdate');
    Route::post(':activity_id/toggle/:code', 'hd.HdSwitchController/toggle');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 19. 系统设置 API（需要登录）
// ============================================================
Route::group('api/hd/setting', function () {
    Route::get('', 'hd.HdSettingController/index');
    Route::post('business', 'hd.HdSettingController/updateBusiness');
    Route::post('wx-config', 'hd.HdSettingController/updateWxConfig');
    Route::post('password', 'hd.HdSettingController/changePassword');
    Route::get('map-key', 'hd.HdSettingController/mapKey');
    Route::get('place-search', 'hd.HdSettingController/placeSearch');
    Route::get('reverse-geo', 'hd.HdSettingController/reverseGeo');
    Route::get('mobile-urls', 'hd.HdSettingController/mobileUrls');
    // 大屏显示设置
    Route::get('display', 'hd.HdSettingController/displayConfig');
    Route::post('display', 'hd.HdSettingController/updateDisplayConfig');
    Route::post('display-logo', 'hd.HdSettingController/uploadDisplayLogo');
    Route::post('display-logo-delete', 'hd.HdSettingController/deleteDisplayLogo');
})->middleware([$hdCors, $hdTenant, $hdAuth]);

// ============================================================
// 20. 内容安全 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/security', function () {
    // 安全配置
    Route::get(':activity_id/config', 'hd.HdSecurityController/securityConfig');
    Route::post(':activity_id/config', 'hd.HdSecurityController/updateSecurityConfig');
    // 关键词管理
    Route::get(':activity_id/keywords', 'hd.HdSecurityController/keywords');
    Route::post(':activity_id/keywords', 'hd.HdSecurityController/addKeyword');
    Route::post(':activity_id/keywords/batch', 'hd.HdSecurityController/batchAddKeywords');
    Route::post(':activity_id/keywords/:id/delete', 'hd.HdSecurityController/deleteKeyword');
    Route::post(':activity_id/keywords/:id/toggle', 'hd.HdSecurityController/toggleKeyword');
    // 用户禁言
    Route::get(':activity_id/bans', 'hd.HdSecurityController/banList');
    Route::post(':activity_id/bans', 'hd.HdSecurityController/banUser');
    Route::post(':activity_id/bans/:id/unban', 'hd.HdSecurityController/unbanUser');
    // 全局禁言
    Route::post(':activity_id/global-mute', 'hd.HdSecurityController/toggleGlobalMute');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

// ============================================================
// 21. 品牌定制 API（需要登录 + 套餐权限）
// ============================================================
Route::group('api/hd/brand', function () {
    Route::get(':activity_id/config', 'hd.HdSecurityController/brandConfig');
    Route::post(':activity_id/config', 'hd.HdSecurityController/updateBrandConfig');
    Route::get('animation-presets', 'hd.HdSecurityController/animationPresets');
})->middleware([$hdCors, $hdTenant, $hdAuth, $hdPlan]);

<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');
//扩展功能在下面扩展下添加
//------------------营销功能-----------------------------
Route::any('DayGive/:function', 'yingxiao.DayGive/:function');
Route::any('TeamSaleYeji/:function', 'yingxiao.TeamSaleYeji/:function');
Route::any('TeamYejiManage/:function', 'yingxiao.TeamYejiManage/:function');
Route::any('OrderCollectReward/:function', 'yingxiao.OrderCollectReward/:function');

if(getcustom('yx_queue_free')){
    Route::any('ApiQueueFree/:function', 'yingxiao.ApiQueueFree/:function');
    Route::any('QueueFree/:function', 'yingxiao.QueueFree/:function');
    Route::any('QueueFreeSet/:function', 'yingxiao.QueueFreeSet/:function');
    Route::any('ApiAdminQueueFree/:function', 'yingxiao.ApiAdminQueueFree/:function');
}
if(getcustom('yx_order_discount_rand')){
    Route::any('OrderDiscountRand/:function', 'yingxiao.OrderDiscountRand/:function');
}
if(getcustom('shop_paiming_fenhong')){
    Route::any('PaimingFenhong/:function', 'yingxiao.PaimingFenhong/:function');
    Route::any('ApiPaimingFenhong/:function', 'yingxiao.ApiPaimingFenhong/:function');
}

if(getcustom('yx_mangfan')){
    Route::any('ApiMangfan/:function', 'yingxiao.ApiMangfan/:function');
    Route::any('Mangfan/:function', 'yingxiao.Mangfan/:function');
    Route::any('MangfanSet/:function', 'yingxiao.MangfanSet/:function');
}
if(getcustom('yx_buy_fenhong')){
    Route::any('BuyFenhong/:function', 'yingxiao.BuyFenhong/:function');
    Route::any('BuyFenhongSet/:function', 'yingxiao.BuyFenhongSet/:function');
}

if(getcustom('yx_hongbao_queue_free')){
    Route::any('HongbaoQueueFree/:function', 'yingxiao.HongbaoQueueFree/:function');
    Route::any('HongbaoQueueFreeSet/:function', 'yingxiao.HongbaoQueueFreeSet/:function');
}

if(getcustom('extend_invite_redpacket')){
    Route::any('InviteRedpacket/:function', 'yingxiao.InviteRedpacket/:function');
}
if(getcustom('yx_team_yeji_weight')){
    Route::any('TeamYejiWeight/:function', 'yingxiao.TeamYejiWeight/:function');
}
if(getcustom('yx_team_yeji_tongji')){
    Route::any('TeamYejiTongji/:function', 'yingxiao.TeamYejiTongji/:function');
    Route::any('ApiTeamYejiTongji/:function', 'yingxiao.ApiTeamYejiTongji/:function');
}
if(getcustom('yx_score_freeze')){
    Route::any('ScoreFreeze/:function', 'yingxiao.ScoreFreeze/:function');
}
if(getcustom('yx_shop_order_team_yeji_bonus')){
    Route::any('ShopOrderTeamYejiBonus/:function', 'yingxiao.ShopOrderTeamYejiBonus/:function');
}
if(getcustom('yx_yeji_fenhong')){
    Route::any('YejiFenhong/:function', 'yingxiao.YejiFenhong/:function');
}

if(getcustom('yx_buy_product_manren_choujiang')){
    Route::any('ManrenChoujiang/:function', 'yingxiao.ManrenChoujiang/:function');
    Route::any('ApiManrenChoujiang/:function', 'yingxiao.ApiManrenChoujiang/:function');
}
if(getcustom('transfer_order_parent_check')){
    Route::any('TransferOrderParentCheckTongji/:function', 'yingxiao.TransferOrderParentCheckTongji/:function');
}
if(getcustom('cps_jutuike_douyin')){
    Route::any('ApiDouyinTuangou/:function', 'yingxiao.ApiDouyinTuangou/:function');
    Route::any('DouyinTuangouCategory/:function', 'yingxiao.DouyinTuangouCategory/:function');
    Route::any('DouyinTuangouProduct/:function', 'yingxiao.DouyinTuangouProduct/:function');
    Route::any('DouyinTuangouSet/:function', 'yingxiao.DouyinTuangouSet/:function');
}
if(getcustom('yx_task')){
    Route::any('Task/:function', 'yingxiao.Task/:function');
    Route::any('TaskLog/:function', 'yingxiao.TaskLog/:function');
}
if(getcustom('teamyeji_pv')){
    Route::any('TeamyejiPv/:function', 'yingxiao.TeamyejiPv/:function');
}
if(getcustom('yx_collage_jipin2')){
    Route::any('Jipin/:function', 'yingxiao.Jipin/:function');
    Route::any('ApiJipinLog/:function', 'yingxiao.ApiJipinLog/:function');
}
if(getcustom('yx_collage_jipin')){
    Route::any('CollageJipin/:function', 'yingxiao.CollageJipin/:function');
    Route::any('ApiCollageJipin/:function', 'yingxiao.ApiCollageJipin/:function');
}
if(getcustom('yx_team_yeji_fenhong')){
    Route::any('TeamYejiFenhong/:function', 'yingxiao.TeamYejiFenhong/:function');
}
if(getcustom('yx_single_adset')){
    Route::any('AdsetSet/:function', 'yingxiao.AdsetSet/:function');
}
if(getcustom('product_luckyfree')){
    Route::any('ProductLuckyfree/:function', 'yingxiao.ProductLuckyfree/:function');
    Route::any('ApiLuckyfree/:function', 'yingxiao.ApiLuckyfree/:function');
}
if(getcustom('yx_liandong')){
    Route::any('Liandong/:function', 'yingxiao.Liandong/:function');
}
if(getcustom('yx_network_help')){
    Route::any('NetworkHelp/:function', 'yingxiao.NetworkHelp/:function');
}
if(getcustom('member_recommend_apply_business')){
	Route::any('RecommendApplyBusiness/:function', 'yingxiao.RecommendApplyBusiness/:function');
}
if(getcustom('yx_buyer_subsidy')){
    Route::any('Subsidy/:function', 'yingxiao.Subsidy/:function');
}
if(getcustom('yx_money_send_hongbao')){
    Route::any('MoneySendHongbaoSysset/:function', 'yingxiao.MoneySendHongbaoSysset/:function');
    Route::any('MoneySendHongbao/:function', 'yingxiao.MoneySendHongbao/:function');
    Route::any('ApiMoneySendHongbao/:function', 'yingxiao.ApiMoneySendHongbao/:function');
    Route::any('MoneySendHongbaoPoster/:function', 'yingxiao.MoneySendHongbaoPoster/:function');
}
if(getcustom('yx_new_score')){
    Route::any('NewScore/:function', 'yingxiao.NewScore/:function');
    if(getcustom('yx_new_score_speed_pack')) {
        Route::any('NewScoreSpeed/:function', 'yingxiao.NewScoreSpeed/:function');
    }
    if(getcustom('yx_new_score_active')) {
        Route::any('NewScoreFormula/:function', 'yingxiao.NewScoreFormula/:function');
    }
}
if(getcustom('yx_offline_subsidies')){
    Route::any('OfflineSubsidies/:function', 'yingxiao.OfflineSubsidies/:function');
}
if(getcustom('yx_team_yeji_activity')){
    Route::any('TeamSaleYejiActivity/:function', 'yingxiao.TeamSaleYejiActivity/:function');
    Route::any('TeamSaleYejiActivityRecord/:function', 'yingxiao.TeamSaleYejiActivityRecord/:function');
}
if(getcustom('yx_butie_activity')){
    Route::any('ButieActivity/:function', 'yingxiao.ButieActivity/:function');
    Route::any('ApiButieActivity/:function', 'yingxiao.ApiButieActivity/:function');
}
if(getcustom('yx_commission_to_lingqiantong')){
    Route::any('CommissionLingqiantong/:function', 'yingxiao.CommissionLingqiantong/:function');
    Route::any('ApiCommissionLingqiantong/:function', 'yingxiao.ApiCommissionLingqiantong/:function');
}
if(getcustom('yx_daily_lirun_choujiang')){
    Route::any('LirunChoujiang/:function', 'yingxiao.LirunChoujiang/:function');
    Route::any('ApiLirunChoujiang/:function', 'yingxiao.ApiLirunChoujiang/:function');
}
if(getcustom('gold_bean_shop')){
    Route::any('GoldBeanShopCategory/:function', 'yingxiao.GoldBeanShopCategory/:function');
    Route::any('GoldBeanShopProduct/:function', 'yingxiao.GoldBeanShopProduct/:function');
    Route::any('GoldBeanShopCode/:function', 'yingxiao.GoldBeanShopCode/:function');
    Route::any('GoldBeanShopOrder/:function', 'yingxiao.GoldBeanShopOrder/:function');
    Route::any('GoldBeanShopPoster/:function', 'yingxiao.GoldBeanShopPoster/:function');
    Route::any('GoldBeanShopSet/:function', 'yingxiao.GoldBeanShopSet/:function');
    Route::any('ApiGoldBeanShop/:function', 'yingxiao.ApiGoldBeanShop/:function');
}
if(getcustom('yx_queue_free_multi_team_business')){
    Route::any('QueueFreeMultiTeam/:function', 'yingxiao.QueueFreeMultiTeam/:function');
}
if(getcustom('yx_digital_consum')){
    Route::any('DigitalConsum/:function', 'yingxiao.DigitalConsum/:function');
}
if(getcustom('yx_farm')){
    Route::any('Farm/:function', 'yingxiao.Farm/:function');
}
//--------------------------扩展功能------------------------------------
if(getcustom('extend_certificate')){
    Route::any('CertificateList/:function', 'extend.CertificateList/:function');
    Route::any('CertificateCategory/:function', 'extend.CertificateCategory/:function');
    Route::any('CertificateJob/:function', 'extend.CertificateJob/:function');
    Route::any('CertificateEducation/:function', 'extend.CertificateEducation/:function');
}
if(getcustom('supply_zhenxin')){
    Route::any('SupplyZhenxinProduct/:function', 'extend.SupplyZhenxinProduct/:function');
    Route::any('SupplyZhenxinSet/:function', 'extend.SupplyZhenxinSet/:function');
}
if(getcustom('extend_linghuoxin')){
    Route::any('LinghuoxinSet/:function', 'extend.LinghuoxinSet/:function');
}
if(getcustom('pay_allinpay')){
    Route::any('AllinpayYunstSet/:function', 'extend.AllinpayYunstSet/:function');
}
if(getcustom('mobile_admin_qrcode_variable_maidan')){
    Route::any('BindQrcodeVar/:function', 'extend.BindQrcodeVar/:function');
}
if(getcustom('form_tan')){
    Route::any('FormTanSet/:function', 'extend.FormTanSet/:function');
    Route::any('FormTan/:function', 'extend.FormTan/:function');
    Route::any('ApiFormTan/:function', 'extend.ApiFormTan/:function');
}
if(getcustom('score_to_fenhongdian')){
    Route::any('ScoreToFenhongdian/:function', 'extend.ScoreToFenhongdian/:function');
}
if(getcustom('extend_planorder')){
    Route::any('Planorder/:function', 'extend.Planorder/:function');
    Route::any('PlanorderShop/:function', 'extend.PlanorderShop/:function');
}
if(getcustom('extend_elecalbum')){
    Route::any('Elecalbum/:function', 'extend.Elecalbum/:function');
}
if(getcustom('extend_exchange_card')){
    Route::any('ExchangeCard/:function', 'extend.ExchangeCard/:function');
    Route::any('ExchangeCardCategory/:function', 'extend.ExchangeCardCategory/:function');
    Route::any('ApiExchangeCard/:function', 'extend.ApiExchangeCard/:function');
}
if(getcustom('extend_hanglvfeike')){
    Route::any('HanglvfeikeStation/:function', 'extend.HanglvfeikeStation/:function');
    Route::any('HanglvfeikeOrder/:function', 'extend.HanglvfeikeOrder/:function');
    Route::any('HanglvfeikeOrderRefund/:function', 'extend.HanglvfeikeOrderRefund/:function');
    Route::any('HanglvfeikeAirline/:function', 'extend.HanglvfeikeAirline/:function');
    Route::any('HanglvfeikeSet/:function', 'extend.HanglvfeikeSet/:function');
}
if(getcustom('business_expert')){
    Route::any('BusinessExpert/:function', 'extend.BusinessExpert/:function');
}
if(getcustom('water_tongyuan')){
    Route::any('WaterTongyuanCard/:function', 'extend.WaterTongyuanCard/:function');
    Route::any('WaterTongyuanDevice/:function', 'extend.WaterTongyuanDevice/:function');
    Route::any('WaterTongyuanSet/:function', 'extend.WaterTongyuanSet/:function');
    Route::any('ApiWaterTongyuan/:function', 'extend.ApiWaterTongyuan/:function');
    Route::any('WaterTongyuanCallback/:function', 'extend.WaterTongyuanCallback/:function');
}
if(getcustom('extend_zhiyoubao_theater')){
    Route::any('ZhiyoubaoShow/:function', 'extend.ZhiyoubaoShow/:function');
    Route::any('ZhiyoubaoPerform/:function', 'extend.ZhiyoubaoPerform/:function');
    Route::any('ZhiyoubaoOrder/:function', 'extend.ZhiyoubaoOrder/:function');
    Route::any('ZhiyoubaoOrderRefund/:function', 'extend.ZhiyoubaoOrderRefund/:function');
    Route::any('ZhiyoubaoRaiseprice/:function', 'extend.ZhiyoubaoRaiseprice/:function');
    Route::any('ZhiyoubaoSet/:function', 'extend.ZhiyoubaoSet/:function');
}
if(getcustom('meituan_xinyoujie')){
    Route::any('MeituanSupplieProduct/:function', 'extend.MeituanSupplieProduct/:function');
    Route::any('MeituanProduct/:function', 'extend.MeituanProduct/:function');
    Route::any('MeituanPriceStrategy/:function', 'extend.MeituanPriceStrategy/:function');
    Route::any('MeituanOrder/:function', 'extend.MeituanOrder/:function');
    Route::any('MeituanSet/:function', 'extend.MeituanSet/:function');
    Route::any('MeituanScenicSpot/:function', 'extend.MeituanScenicSpot/:function');
}
if(getcustom('supply_yongsheng')){
	Route::any('SupplyYongshengProduct/:function', 'extend.SupplyYongshengProduct/:function');
    Route::any('SupplyYongshengOrder/:function', 'extend.SupplyYongshengOrder/:function');
    Route::any('SupplyYongshengSet/:function', 'extend.SupplyYongshengSet/:function');
}
if(getcustom('extend_advertising')){
    Route::any('Advertising/:function', 'extend.Advertising/:function');
}
if(getcustom('extend_business_shareholder')){
    Route::any('BusinessShareholder/:function', 'extend.BusinessShareholder/:function');
}
if(getcustom('extend_tencent_qian')){
    Route::any('TencentQian/:function', 'extend.TencentQian/:function');
}
if(getcustom('yx_collage_jiqiren')){
    Route::any('CollageJiqiren/:function', 'yingxiao.CollageJiqiren/:function');
}

// ====================模型广场路由====================
Route::any('WebModelSquare/:function', 'WebModelSquare/:function');

// ====================Ollama本地LLM对话路由====================
Route::any('OllamaChat/:function', 'OllamaChat/:function');

// ====================VoxCPM2语音对话路由====================
Route::any('VoiceChat/:function', 'VoiceChat/:function');

// ====================AI短剧工作流系统路由====================
Route::any('Workflow/:function', 'Workflow/:function');

// ====================系统API Key配置路由====================
Route::any('SystemApiKey/:function', 'SystemApiKey/:function');

// ====================AI旅拍系统路由====================
// 商家后台管理路由
Route::any('AiTravelPhoto/:function', 'AiTravelPhoto/:function');

// AI模型配置管理路由
Route::any('ModelConfig/:function', 'ModelConfig/:function');

// API配置管理路由
Route::any('ApiConfig/:function', 'ApiConfig/:function');

// API调用接口路由
Route::any('ApiCall/:function', 'ApiCall/:function');

// API统计监控路由
Route::any('ApiStatistics/:function', 'ApiStatistics/:function');

// API路由 - 设备相关
Route::any('api/ai_travel_photo/device/:function', 'api.AiTravelPhotoDevice/:function');

// API路由 - 二维码相关
Route::any('api/ai_travel_photo/qrcode/:function', 'api.AiTravelPhotoQrcode/:function');

// API路由 - 场景相关
Route::any('api/ai_travel_photo/scene/:function', 'api.AiTravelPhotoScene/:function');

// API路由 - 人像相关
Route::any('api/ai_travel_photo/portrait/:function', 'api.AiTravelPhotoPortrait/:function');

// API路由 - 订单相关
Route::any('api/ai_travel_photo/order/:function', 'api.AiTravelPhotoOrder/:function');

// API路由 - 相册相关
Route::any('api/ai_travel_photo/album/:function', 'api.AiTravelPhotoAlbum/:function');

// API路由 - 选片交付（H5扫码选片 → 套餐推荐 → 付费下载）
Route::any('api/ai_travel_photo/pick/:function', 'api.AiTravelPhotoPick/:function');

// API路由 - 用户自拍端（笑脸拍照自抩端）
Route::any('api/ai_travel_photo/selfie/:function', 'api.AiTravelPhotoSelfie/:function');

// API路由 - XPD大屏选片端
Route::any('api/ai-travel-photo/selection-list', 'api.AiTravelPhotoXpd/selection_list');
Route::any('api/ai_travel_photo/xpd/:function', 'api.AiTravelPhotoXpd/:function');
// ====================AI旅拍系统路由结束====================

// 统一订单聚合接口
Route::any('ApiUnifiedOrder/:function', 'ApiUnifiedOrder/:function');

// 授权管理后台路由
// 测试路由
Route::get('sysadmin/test', function() {
    return 'Sysadmin test route working!';
});

// 管理员登录路由
Route::group('sysadmin', function() {
    Route::get('login', 'sysadmin.SysadminLogin/index');
    Route::post('login', 'sysadmin.SysadminLogin/login');
    Route::get('logout', 'sysadmin.SysadminLogin/logout');
    Route::get('dashboard', 'sysadmin.SysadminDashboard/index');
    
    // 授权管理
    Route::get('license', 'sysadmin.SysadminLicense/index');
    Route::get('license/create', 'sysadmin.SysadminLicense/create');
    Route::post('license/save', 'sysadmin.SysadminLicense/save');
    Route::get('license/edit/:id', 'sysadmin.SysadminLicense/edit');
    Route::post('license/update', 'sysadmin.SysadminLicense/update');
    Route::post('license/revoke/:id', 'sysadmin.SysadminLicense/revoke');
    Route::get('license/renew/:id', 'sysadmin.SysadminLicense/renew');
    Route::post('license/doRenew', 'sysadmin.SysadminLicense/doRenew');
    
    // 黑名单管理
    Route::get('blacklist', 'sysadmin.SysadminBlacklist/index');
    Route::get('blacklist/add', 'sysadmin.SysadminBlacklist/add');
    Route::post('blacklist/save', 'sysadmin.SysadminBlacklist/save');
    Route::post('blacklist/remove/:id', 'sysadmin.SysadminBlacklist/remove');
    
    // 客户端 API
    Route::group('api', function() {
        Route::post('verify', 'sysadmin.SysadminApi/verify');
        Route::post('activate', 'sysadmin.SysadminApi/activate');
        Route::post('checkUpgrade', 'sysadmin.SysadminApi/checkUpgrade');
        Route::post('downloadUpgrade', 'sysadmin.SysadminApi/downloadUpgrade');
        Route::post('reportFingerprint', 'sysadmin.SysadminApi/reportFingerprint');
        Route::post('reportPiracy', 'sysadmin.SysadminApi/reportPiracy');
    });
});
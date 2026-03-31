<?php
use think\facade\Route;

// 测试路由
Route::get('sysadmin/test', function() {
    return 'Sysadmin test route working!';
});

// 直接登录路由
Route::get('sysadmin/login', function() {
    return 'Sysadmin login route working!';
});
Route::post('sysadmin/login', 'SysadminLogin/login');

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

<?php
namespace app\middleware;

use app\service\SysadminLicenseClient;
use think\response\Json;

class LicenseVerify
{
    public function handle($request, \Closure $next)
    {
        $licenseCode = config('license.code');
        $domain = $request->host();
        
        if (!$licenseCode) {
            return Json::create(['code' => 403, 'msg' => '未配置授权码']);
        }
        
        $client = new SysadminLicenseClient($licenseCode, $domain);
        $result = $client->verify();
        
        if ($result['status'] != 1) {
            if (isset($result['status']) && $result['status'] == -1) {
                return Json::create(['code' => 403, 'msg' => '已被禁止']);
            } else {
                return Json::create(['code' => 403, 'msg' => '授权验证失败: ' . ($result['msg'] ?? '未知错误')]);
            }
        }
        
        return $next($request);
    }
}
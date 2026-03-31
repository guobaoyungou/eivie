<?php
namespace app\sysadmin\middleware;

use app\sysadmin\model\SysadminLicense;
use think\response\Json;

class LicenseSignatureVerify
{
    public function handle($request, \Closure $next)
    {
        $licenseCode = $request->param('license_code');
        $timestamp = $request->param('timestamp');
        $nonce = $request->param('nonce');
        $signature = $request->param('signature');
        
        if (!$licenseCode || !$timestamp || !$nonce || !$signature) {
            return Json::create(['status' => 0, 'msg' => '缺少必要参数']);
        }
        
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        $hmacSecret = $license->hmac_secret;
        if (!$hmacSecret) {
            return Json::create(['status' => 0, 'msg' => '授权未激活']);
        }
        
        $params = $request->param();
        unset($params['signature']);
        ksort($params);
        
        $signString = http_build_query($params);
        $expectedSignature = hash_hmac('sha256', $signString, $hmacSecret);
        
        if ($expectedSignature !== $signature) {
            return Json::create(['status' => 0, 'msg' => '签名验证失败']);
        }
        
        $now = time();
        if (abs($now - $timestamp) > 300) {
            return Json::create(['status' => 0, 'msg' => '请求已过期']);
        }
        
        return $next($request);
    }
}
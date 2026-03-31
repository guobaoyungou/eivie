<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminLicense;
use app\sysadmin\model\SysadminBlacklist;
use think\Controller;
use think\response\Json;

class SysadminApi extends Controller
{
    public function verify()
    {
        $data = $this->request->param();
        $license = SysadminLicense::where('license_code', $data['license_code'])->where('status', 1)->find();
        
        if (!$license) {
            return Json::create(['code' => 0, 'msg' => '授权码不存在或已过期']);
        }
        
        if (strtotime($license->expire_time) < time()) {
            return Json::create(['code' => 0, 'msg' => '授权已过期']);
        }
        
        $blacklist = SysadminBlacklist::where('domain', $data['domain'])->find();
        if ($blacklist) {
            return Json::create(['code' => 0, 'msg' => '域名已被拉黑']);
        }
        
        return Json::create(['code' => 1, 'msg' => '授权验证通过', 'data' => $license]);
    }
    
    public function activate()
    {
        $data = $this->request->param();
        $license = SysadminLicense::where('license_code', $data['license_code'])->where('status', 1)->find();
        
        if (!$license) {
            return Json::create(['code' => 0, 'msg' => '授权码不存在或已过期']);
        }
        
        $license->domain = $data['domain'];
        $license->fingerprint = $data['fingerprint'];
        $license->save();
        
        return Json::create(['code' => 1, 'msg' => '激活成功']);
    }
    
    public function checkUpgrade()
    {
        $data = $this->request->param();
        $license = SysadminLicense::where('license_code', $data['license_code'])->where('status', 1)->find();
        
        if (!$license) {
            return Json::create(['code' => 0, 'msg' => '授权码不存在或已过期']);
        }
        
        // 这里可以添加版本检查逻辑
        return Json::create(['code' => 1, 'msg' => '当前版本最新']);
    }
    
    public function downloadUpgrade()
    {
        $data = $this->request->param();
        $license = SysadminLicense::where('license_code', $data['license_code'])->where('status', 1)->find();
        
        if (!$license) {
            return Json::create(['code' => 0, 'msg' => '授权码不存在或已过期']);
        }
        
        // 这里可以添加下载逻辑
        return Json::create(['code' => 1, 'msg' => '下载成功']);
    }
    
    public function reportFingerprint()
    {
        $data = $this->request->param();
        $license = SysadminLicense::where('license_code', $data['license_code'])->where('status', 1)->find();
        
        if (!$license) {
            return Json::create(['code' => 0, 'msg' => '授权码不存在或已过期']);
        }
        
        $license->fingerprint = $data['fingerprint'];
        $license->save();
        
        return Json::create(['code' => 1, 'msg' => '上报成功']);
    }
    
    public function reportPiracy()
    {
        $data = $this->request->param();
        // 这里可以添加盗版检测逻辑
        return Json::create(['code' => 1, 'msg' => '上报成功']);
    }
}
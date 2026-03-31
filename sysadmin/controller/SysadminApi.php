<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminLicense;
use app\sysadmin\model\SysadminHeartbeatLog;
use app\sysadmin\model\SysadminActivationLog;
use app\sysadmin\model\SysadminPiracyAlert;
use app\sysadmin\model\SysadminUpgradeVersion;
use think\Controller;
use think\response\Json;

class SysadminApi extends Controller
{
    public function verify()
    {
        $data = $this->request->param();
        $licenseCode = $data['license_code'];
        $domain = $data['domain'];
        $version = $data['version'];
        $fileHash = $data['file_hash'];
        $serverIp = $data['server_ip'];
        $serverMac = $data['server_mac'];
        
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        if ($license->status != 1) {
            return Json::create(['status' => 0, 'msg' => '授权已失效']);
        }
        
        if ($license->expire_time > 0 && $license->expire_time < time()) {
            $license->status = 3;
            $license->save();
            return Json::create(['status' => 0, 'msg' => '授权已过期']);
        }
        
        if ($license->domain != $domain) {
            $this->recordPiracyAlert($license->id, $domain, $serverIp, $serverMac, 1);
            return Json::create(['status' => 0, 'msg' => '域名不匹配']);
        }
        
        if ($license->server_mac && $license->server_mac != $serverMac) {
            $this->recordPiracyAlert($license->id, $domain, $serverIp, $serverMac, 5);
            return Json::create(['status' => 0, 'msg' => '服务器不匹配']);
        }
        
        $license->last_heartbeat = time();
        $license->last_version = $version;
        $license->file_hash = $fileHash;
        $license->save();
        
        $this->recordHeartbeatLog($license->id, $domain, $serverIp, $serverMac, $version, $fileHash, 1);
        
        $latestVersion = $this->getLatestVersion($license->edition_id);
        
        $response = [
            'status' => 1,
            'msg' => '验证成功',
            'expire_time' => $license->expire_time,
            'edition' => $license->edition->name,
            'force_upgrade' => $latestVersion['force_upgrade'],
            'latest_version' => $latestVersion['version']
        ];
        
        $response['signature'] = $this->generateResponseSignature($response, $license->hmac_secret);
        
        return Json::create($response);
    }
    
    public function activate()
    {
        $data = $this->request->param();
        $licenseCode = $data['license_code'];
        $domain = $data['domain'];
        $serverIp = $data['server_ip'];
        $serverMac = $data['server_mac'];
        $serverInfo = $data['server_info'];
        
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        if ($license->status == 1) {
            if ($license->domain == $domain && $license->server_mac == $serverMac) {
                return Json::create(['status' => 1, 'msg' => '已激活', 'expire_time' => $license->expire_time]);
            } else {
                $this->recordPiracyAlert($license->id, $domain, $serverIp, $serverMac, 3);
                return Json::create(['status' => 0, 'msg' => '授权码已绑定其他服务器']);
            }
        }
        
        $license->status = 1;
        $license->domain = $domain;
        $license->domain_hash = md5($domain);
        $license->server_ip = $serverIp;
        $license->server_mac = $serverMac;
        $license->mac_hash = md5($serverMac);
        $license->activate_time = time();
        $license->save();
        
        $this->recordActivationLog($license->id, $domain, $serverIp, $serverMac, $serverInfo, 1);
        
        return Json::create([
            'status' => 1,
            'msg' => '激活成功',
            'hmac_secret' => $license->hmac_secret,
            'expire_time' => $license->expire_time
        ]);
    }
    
    public function checkUpgrade()
    {
        $data = $this->request->param();
        $licenseCode = $data['license_code'];
        $version = $data['version'];
        
        $license = SysadminLicense::with('edition')->where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        $latestVersion = $this->getLatestVersion($license->edition_id, $version);
        
        if ($latestVersion['version']) {
            return Json::create([
                'has_upgrade' => true,
                'version' => $latestVersion['version'],
                'changelog' => $latestVersion['changelog'],
                'is_force' => $latestVersion['is_force'],
                'package_size' => $latestVersion['package_size'],
                'download_token' => md5(time() . uniqid())
            ]);
        } else {
            return Json::create(['has_upgrade' => false]);
        }
    }
    
    public function downloadUpgrade()
    {
        $data = $this->request->param();
        $licenseCode = $data['license_code'];
        $downloadToken = $data['download_token'];
        
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        $latestVersion = $this->getLatestVersion($license->edition_id);
        if (!$latestVersion['version']) {
            return Json::create(['status' => 0, 'msg' => '无可用升级']);
        }
        
        return Json::create([
            'status' => 1,
            'msg' => '下载成功',
            'package_path' => $latestVersion['package_path'],
            'package_hash' => $latestVersion['package_hash']
        ]);
    }
    
    public function reportFingerprint()
    {
        $data = $this->request->param();
        $licenseCode = $data['license_code'];
        $fileHash = $data['file_hash'];
        
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        $license->file_hash = $fileHash;
        $license->save();
        
        return Json::create(['status' => 1, 'msg' => '上报成功']);
    }
    
    public function reportPiracy()
    {
        $data = $this->request->param();
        $licenseCode = $data['license_code'];
        $pirateDomain = $data['pirate_domain'];
        $pirateIp = $data['pirate_ip'];
        $evidenceType = $data['evidence_type'];
        $evidenceDetail = $data['evidence_detail'];
        
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return Json::create(['status' => 0, 'msg' => '无效授权码']);
        }
        
        $this->recordPiracyAlert($license->id, $pirateDomain, $pirateIp, '', 6, 2, $evidenceDetail);
        
        return Json::create(['status' => 1, 'msg' => '上报成功']);
    }
    
    private function recordHeartbeatLog($licenseId, $domain, $serverIp, $serverMac, $version, $fileHash, $verifyResult, $failReason = '')
    {
        $log = new SysadminHeartbeatLog();
        $log->license_id = $licenseId;
        $log->domain = $domain;
        $log->server_ip = $serverIp;
        $log->server_mac = $serverMac;
        $log->version = $version;
        $log->file_hash = $fileHash;
        $log->verify_result = $verifyResult;
        $log->fail_reason = $failReason;
        $log->create_time = time();
        $log->save();
    }
    
    private function recordActivationLog($licenseId, $domain, $serverIp, $serverMac, $serverInfo, $result, $failReason = '')
    {
        $log = new SysadminActivationLog();
        $log->license_id = $licenseId;
        $log->domain = $domain;
        $log->server_ip = $serverIp;
        $log->server_mac = $serverMac;
        $log->server_info = json_encode($serverInfo);
        $log->result = $result;
        $log->fail_reason = $failReason;
        $log->create_time = time();
        $log->save();
    }
    
    private function recordPiracyAlert($licenseId, $domain, $serverIp, $serverMac, $alertType, $source = 1, $detail = '')
    {
        $alert = new SysadminPiracyAlert();
        $alert->license_id = $licenseId;
        $alert->domain = $domain;
        $alert->server_ip = $serverIp;
        $alert->server_mac = $serverMac;
        $alert->alert_type = $alertType;
        $alert->source = $source;
        $alert->detail = $detail;
        $alert->status = 0;
        $alert->create_time = time();
        $alert->save();
    }
    
    private function getLatestVersion($editionId, $currentVersion = '')
    {
        $versions = SysadminUpgradeVersion::where('status', 1)->order('version DESC')->select();
        
        foreach ($versions as $version) {
            $targetEditions = json_decode($version->target_editions, true);
            if (in_array($editionId, $targetEditions)) {
                if (empty($currentVersion) || version_compare($version->version, $currentVersion, '>')) {
                    return [
                        'version' => $version->version,
                        'changelog' => $version->changelog,
                        'is_force' => $version->is_force,
                        'package_size' => $version->package_size,
                        'package_path' => $version->package_path,
                        'package_hash' => $version->package_hash,
                        'force_upgrade' => $version->is_force
                    ];
                }
            }
        }
        
        return ['version' => '', 'force_upgrade' => false];
    }
    
    private function generateResponseSignature($data, $secret)
    {
        ksort($data);
        $signString = http_build_query($data);
        return hash_hmac('sha256', $signString, $secret);
    }
}
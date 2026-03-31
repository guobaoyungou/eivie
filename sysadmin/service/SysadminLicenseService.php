<?php
namespace app\sysadmin\service;

use app\sysadmin\model\SysadminLicense;
use app\sysadmin\model\SysadminLicenseEdition;

class SysadminLicenseService
{
    public function createLicense($data)
    {
        $edition = SysadminLicenseEdition::find($data['edition_id']);
        if (!$edition) {
            return ['code' => 0, 'msg' => '套餐不存在'];
        }
        
        $licenseCode = $this->generateLicenseCode($edition->code);
        $licenseCipher = $this->generateLicenseCipher($licenseCode, $data['domain']);
        $hmacSecret = $this->generateHmacSecret();
        $encryptKey = $this->generateEncryptKey();
        
        $expireTime = $data['expire_time'] ? strtotime($data['expire_time']) : 0;
        if (!$expireTime && $edition->duration_days > 0) {
            $expireTime = time() + ($edition->duration_days * 86400);
        }
        
        $license = new SysadminLicense();
        $license->license_code = $licenseCode;
        $license->license_cipher = $licenseCipher;
        $license->domain = $data['domain'];
        $license->domain_hash = md5($data['domain']);
        $license->edition_id = $data['edition_id'];
        $license->contact_name = $data['contact_name'];
        $license->contact_phone = $data['contact_phone'];
        $license->contact_company = $data['contact_company'];
        $license->status = 0;
        $license->expire_time = $expireTime;
        $license->hmac_secret = $hmacSecret;
        $license->encrypt_key = $encryptKey;
        $license->remark = $data['remark'];
        $license->create_time = time();
        $license->update_time = time();
        
        if ($license->save()) {
            return ['code' => 1, 'msg' => '创建成功', 'data' => $license];
        } else {
            return ['code' => 0, 'msg' => '创建失败'];
        }
    }
    
    public function updateLicense($data)
    {
        $license = SysadminLicense::find($data['id']);
        if (!$license) {
            return ['code' => 0, 'msg' => '授权不存在'];
        }
        
        $license->domain = $data['domain'];
        $license->domain_hash = md5($data['domain']);
        $license->edition_id = $data['edition_id'];
        $license->contact_name = $data['contact_name'];
        $license->contact_phone = $data['contact_phone'];
        $license->contact_company = $data['contact_company'];
        $license->expire_time = $data['expire_time'] ? strtotime($data['expire_time']) : 0;
        $license->remark = $data['remark'];
        $license->update_time = time();
        
        if ($license->save()) {
            return ['code' => 1, 'msg' => '更新成功'];
        } else {
            return ['code' => 0, 'msg' => '更新失败'];
        }
    }
    
    public function renewLicense($data)
    {
        $license = SysadminLicense::find($data['id']);
        if (!$license) {
            return ['code' => 0, 'msg' => '授权不存在'];
        }
        
        $edition = SysadminLicenseEdition::find($data['edition_id']);
        if (!$edition) {
            return ['code' => 0, 'msg' => '套餐不存在'];
        }
        
        $currentExpireTime = $license->expire_time;
        $newExpireTime = $currentExpireTime > time() ? $currentExpireTime : time();
        $newExpireTime += ($edition->duration_days * 86400);
        
        $license->edition_id = $data['edition_id'];
        $license->expire_time = $newExpireTime;
        $license->status = 1;
        $license->update_time = time();
        
        if ($license->save()) {
            return ['code' => 1, 'msg' => '续期成功'];
        } else {
            return ['code' => 0, 'msg' => '续期失败'];
        }
    }
    
    public function verifyLicense($licenseCode, $domain, $serverMac)
    {
        $license = SysadminLicense::where('license_cipher', $licenseCode)->find();
        if (!$license) {
            return ['status' => 0, 'msg' => '无效授权码'];
        }
        
        if ($license->status != 1) {
            return ['status' => 0, 'msg' => '授权已失效'];
        }
        
        if ($license->expire_time > 0 && $license->expire_time < time()) {
            $license->status = 3;
            $license->save();
            return ['status' => 0, 'msg' => '授权已过期'];
        }
        
        if ($license->domain != $domain) {
            return ['status' => 0, 'msg' => '域名不匹配'];
        }
        
        if ($license->server_mac && $license->server_mac != $serverMac) {
            return ['status' => 0, 'msg' => '服务器不匹配'];
        }
        
        return ['status' => 1, 'msg' => '验证成功', 'license' => $license];
    }
    
    private function generateLicenseCode($editionCode)
    {
        $prefixMap = [
            'basic' => 'BAS',
            'pro' => 'PRO',
            'premium' => 'PRE'
        ];
        
        $prefix = $prefixMap[$editionCode] ?? 'BAS';
        $randomPart = bin2hex(random_bytes(12));
        $combined = $prefix . $randomPart;
        $checksum = substr(crc32($combined), -4);
        
        return strtoupper($combined . $checksum);
    }
    
    private function generateLicenseCipher($licenseCode, $domain)
    {
        $encryptKey = $this->generateEncryptKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($licenseCode, 'AES-256-CBC', $encryptKey, 0, $iv);
        $cipher = base64_encode($encrypted . '::' . base64_encode($iv) . '::' . md5($domain) . '::' . time());
        
        return $this->shuffleString($cipher);
    }
    
    private function generateHmacSecret()
    {
        return bin2hex(random_bytes(32));
    }
    
    private function generateEncryptKey()
    {
        return bin2hex(random_bytes(32));
    }
    
    private function shuffleString($str)
    {
        $chars = str_split($str);
        shuffle($chars);
        return implode('', $chars);
    }
}
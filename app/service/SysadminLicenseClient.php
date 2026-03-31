<?php
namespace app\service;

class SysadminLicenseClient
{
    protected $licenseCode;
    protected $domain;
    protected $hmacSecret;
    protected $localCacheFile;
    
    public function __construct($licenseCode, $domain)
    {
        $this->licenseCode = $licenseCode;
        $this->domain = $domain;
        $this->localCacheFile = runtime_path() . 'license_cache.php';
    }
    
    public function verify()
    {
        $cache = $this->getLocalCache();
        if ($cache && $this->isCacheValid($cache)) {
            return $cache;
        }
        
        $result = $this->sendHeartbeat();
        if ($result['status'] == 1) {
            $this->saveLocalCache($result);
        }
        
        return $result;
    }
    
    public function activate($serverIp, $serverMac, $serverInfo = [])
    {
        $url = $this->getApiUrl('activate');
        $data = [
            'license_code' => $this->licenseCode,
            'domain' => $this->domain,
            'server_ip' => $serverIp,
            'server_mac' => $serverMac,
            'server_info' => $serverInfo,
            'timestamp' => time(),
            'nonce' => uniqid()
        ];
        
        $data['signature'] = $this->generateSignature($data);
        
        $response = $this->httpPost($url, $data);
        $result = json_decode($response, true);
        
        if ($result['status'] == 1 && isset($result['hmac_secret'])) {
            $this->hmacSecret = $result['hmac_secret'];
            $this->saveHmacSecret($result['hmac_secret']);
        }
        
        return $result;
    }
    
    public function checkUpgrade($currentVersion)
    {
        $url = $this->getApiUrl('checkUpgrade');
        $data = [
            'license_code' => $this->licenseCode,
            'version' => $currentVersion,
            'timestamp' => time(),
            'nonce' => uniqid()
        ];
        
        $data['signature'] = $this->generateSignature($data);
        
        $response = $this->httpPost($url, $data);
        return json_decode($response, true);
    }
    
    public function downloadUpgrade($downloadToken)
    {
        $url = $this->getApiUrl('downloadUpgrade');
        $data = [
            'license_code' => $this->licenseCode,
            'download_token' => $downloadToken,
            'timestamp' => time(),
            'nonce' => uniqid()
        ];
        
        $data['signature'] = $this->generateSignature($data);
        
        $response = $this->httpPost($url, $data);
        return json_decode($response, true);
    }
    
    public function reportFingerprint($fileHash)
    {
        $url = $this->getApiUrl('reportFingerprint');
        $data = [
            'license_code' => $this->licenseCode,
            'file_hash' => $fileHash,
            'timestamp' => time(),
            'nonce' => uniqid()
        ];
        
        $data['signature'] = $this->generateSignature($data);
        
        $response = $this->httpPost($url, $data);
        return json_decode($response, true);
    }
    
    public function reportPiracy($pirateDomain, $pirateIp, $evidenceType, $evidenceDetail = '')
    {
        $url = $this->getApiUrl('reportPiracy');
        $data = [
            'license_code' => $this->licenseCode,
            'pirate_domain' => $pirateDomain,
            'pirate_ip' => $pirateIp,
            'evidence_type' => $evidenceType,
            'evidence_detail' => $evidenceDetail,
            'timestamp' => time(),
            'nonce' => uniqid()
        ];
        
        $data['signature'] = $this->generateSignature($data);
        
        $response = $this->httpPost($url, $data);
        return json_decode($response, true);
    }
    
    public function calculateFileFingerprint($fileList)
    {
        $hashes = [];
        foreach ($fileList as $file) {
            if (file_exists($file)) {
                $hashes[] = md5_file($file);
            }
        }
        sort($hashes);
        return md5(implode('', $hashes));
    }
    
    private function sendHeartbeat()
    {
        $url = $this->getApiUrl('verify');
        $fileHash = $this->calculateFileFingerprint($this->getCoreFiles());
        $serverInfo = $this->getServerInfo();
        
        $data = [
            'license_code' => $this->licenseCode,
            'domain' => $this->domain,
            'version' => $this->getCurrentVersion(),
            'file_hash' => $fileHash,
            'server_ip' => $serverInfo['ip'],
            'server_mac' => $serverInfo['mac'],
            'php_version' => PHP_VERSION,
            'timestamp' => time(),
            'nonce' => uniqid()
        ];
        
        $data['signature'] = $this->generateSignature($data);
        
        $response = $this->httpPost($url, $data);
        return json_decode($response, true);
    }
    
    private function generateSignature($data)
    {
        if (!$this->hmacSecret) {
            $this->hmacSecret = $this->getHmacSecret();
        }
        
        $params = $data;
        unset($params['signature']);
        ksort($params);
        
        $signString = http_build_query($params);
        return hash_hmac('sha256', $signString, $this->hmacSecret);
    }
    
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
    private function getApiUrl($action)
    {
        return 'http://' . $this->domain . '/sysadmin/api/' . $action;
    }
    
    private function getLocalCache()
    {
        if (file_exists($this->localCacheFile)) {
            return include $this->localCacheFile;
        }
        return false;
    }
    
    private function saveLocalCache($data)
    {
        $data['cached_at'] = time();
        file_put_contents($this->localCacheFile, '<?php return ' . var_export($data, true) . ';');
    }
    
    private function isCacheValid($cache)
    {
        $cachedAt = $cache['cached_at'] ?? 0;
        return (time() - $cachedAt) < (21600 * 3);
    }
    
    private function saveHmacSecret($secret)
    {
        file_put_contents(runtime_path() . 'hmac_secret.txt', $secret);
    }
    
    private function getHmacSecret()
    {
        $file = runtime_path() . 'hmac_secret.txt';
        if (file_exists($file)) {
            return trim(file_get_contents($file));
        }
        return '';
    }
    
    private function getServerInfo()
    {
        return [
            'ip' => $this->getServerIp(),
            'mac' => $this->getServerMac()
        ];
    }
    
    private function getServerIp()
    {
        return $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    }
    
    private function getServerMac()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('ipconfig /all');
            preg_match('/Physical Address[^:]*:(.*)/', $output, $matches);
            return isset($matches[1]) ? trim($matches[1]) : '';
        } else {
            $output = shell_exec('ifconfig');
            preg_match('/ether (.*)/', $output, $matches);
            return isset($matches[1]) ? trim($matches[1]) : '';
        }
    }
    
    private function getCoreFiles()
    {
        return [
            APP_PATH . 'BaseController.php',
            APP_PATH . 'service/SysadminLicenseClient.php',
            APP_PATH . 'middleware/LicenseVerify.php'
        ];
    }
    
    private function getCurrentVersion()
    {
        $versionFile = ROOT_PATH . 'version.php';
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        return '1.0.0';
    }
}
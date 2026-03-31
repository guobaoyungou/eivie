<?php
// 数据存储类 - 使用 JSON 文件存储数据
class DataStore {
    private $dataDir;
    
    public function __construct() {
        // 使用PHP临时目录作为数据存储位置，确保Web服务器有权限写入
        $this->dataDir = sys_get_temp_dir() . '/sysadmin_data';
        if (!is_dir($this->dataDir)) {
            $created = mkdir($this->dataDir, 0777, true);
            if (!$created) {
                error_log('Failed to create data directory: ' . $this->dataDir);
            }
        } else {
            // 确保目录有写权限
            chmod($this->dataDir, 0777);
        }
    }
    
    public function getLicenses() {
        $file = $this->dataDir . '/licenses.json';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?: [];
    }
    
    public function saveLicenses($licenses) {
        $file = $this->dataDir . '/licenses.json';
        $result = file_put_contents($file, json_encode($licenses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            error_log('Failed to save licenses: ' . $file . ', error: ' . error_get_last()['message']);
            return false;
        }
        return true;
    }
    
    public function getLicense($id) {
        $licenses = $this->getLicenses();
        foreach ($licenses as $license) {
            if ($license['id'] == $id) {
                return $license;
            }
        }
        return null;
    }
    
    public function addLicense($data) {
        $licenses = $this->getLicenses();
        $data['id'] = time() . rand(1000, 9999);
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['license_code'] = $this->generateLicenseCode();
        $licenses[] = $data;
        $success = $this->saveLicenses($licenses);
        if (!$success) {
            return false;
        }
        return $data;
    }
    
    public function updateLicense($id, $data) {
        $licenses = $this->getLicenses();
        foreach ($licenses as &$license) {
            if ($license['id'] == $id) {
                $license = array_merge($license, $data);
                $license['update_time'] = date('Y-m-d H:i:s');
                $success = $this->saveLicenses($licenses);
                if (!$success) {
                    return false;
                }
                return $license;
            }
        }
        return null;
    }
    
    public function deleteLicense($id) {
        $licenses = $this->getLicenses();
        $newLicenses = array_filter($licenses, function($license) use ($id) {
            return $license['id'] != $id;
        });
        $success = $this->saveLicenses(array_values($newLicenses));
        if (!$success) {
            error_log('Failed to delete license: ' . $id);
            return false;
        }
        return true;
    }
    
    private function generateLicenseCode() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = 'LP-';
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $code .= $chars[rand(0, strlen($chars) - 1)];
            }
            if ($i < 3) $code .= '-';
        }
        return $code;
    }
    
    public function getBlacklist() {
        $file = $this->dataDir . '/blacklist.json';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?: [];
    }
    
    public function saveBlacklist($blacklist) {
        $file = $this->dataDir . '/blacklist.json';
        file_put_contents($file, json_encode($blacklist, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function addBlacklist($data) {
        $blacklist = $this->getBlacklist();
        $data['id'] = time() . rand(1000, 9999);
        $data['create_time'] = date('Y-m-d H:i:s');
        $blacklist[] = $data;
        $this->saveBlacklist($blacklist);
        return $data;
    }
    
    public function deleteBlacklist($id) {
        $blacklist = $this->getBlacklist();
        $newBlacklist = array_filter($blacklist, function($item) use ($id) {
            return $item['id'] != $id;
        });
        $this->saveBlacklist(array_values($newBlacklist));
        return true;
    }
    
    public function getSettings() {
        $file = $this->dataDir . '/settings.json';
        if (!file_exists($file)) {
            return [
                'site_name' => '授权管理后台',
                'admin_email' => 'admin@example.com',
                'license_expire_days' => 365,
                'auto_renew' => false,
                'notify_before_expire' => 7,
                'api_key' => '',
                'secret_key' => '',
            ];
        }
        $settings = json_decode(file_get_contents($file), true) ?: [];
        // 确保所有必要的字段都存在
        $defaultSettings = [
            'site_name' => '授权管理后台',
            'admin_email' => 'admin@example.com',
            'license_expire_days' => 365,
            'auto_renew' => false,
            'notify_before_expire' => 7,
            'api_key' => '',
            'secret_key' => '',
        ];
        return array_merge($defaultSettings, $settings);
    }
    
    public function saveSettings($settings) {
        $file = $this->dataDir . '/settings.json';
        $result = file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            error_log('Failed to save settings: ' . $file . ', error: ' . error_get_last()['message']);
            return false;
        }
        return true;
    }
    
    public function getUsers() {
        $file = $this->dataDir . '/users.json';
        if (!file_exists($file)) {
            return [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'password' => 'admin123456',
                    'create_time' => date('Y-m-d H:i:s')
                ]
            ];
        }
        return json_decode(file_get_contents($file), true) ?: [];
    }
    
    public function saveUsers($users) {
        $file = $this->dataDir . '/users.json';
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function getUser($id) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }
    
    public function getUserByUsername($username) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] == $username) {
                return $user;
            }
        }
        return null;
    }
    
    public function updateUserPassword($id, $password) {
        $users = $this->getUsers();
        foreach ($users as &$user) {
            if ($user['id'] == $id) {
                $user['password'] = $password;
                $user['update_time'] = date('Y-m-d H:i:s');
                $this->saveUsers($users);
                return $user;
            }
        }
        return null;
    }
}

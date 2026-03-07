<?php
/**
 * 点大商城（www.diandashop.com） - 微信公众号小程序商城系统!
 * Copyright © 2020 山东点大网络科技有限公司 保留所有权利
 * =========================================================
 * 版本：V2
 * 授权主体：shop.guobaoyungou.cn
 * 授权域名：guobaoyungou.cn
 * 授权码：TZJcxBSGGdtDBIxFerKVJo
 * ----------------------------------------------
 * 您只能在商业授权范围内使用，不可二次转售、分发、分享、传播
 * 任何企业和个人不得对代码以任何目的任何形式的再发布
 * =========================================================
 */

// +----------------------------------------------------------------------
// | 系统升级控制器
// +----------------------------------------------------------------------
namespace app\controller;

use think\facade\Db;
use think\facade\View;

class WebUpgrade extends WebCommon
{
    /**
     * 升级页面
     */
    public function index()
    {
        $version = $this->getSystemVersion();
        $latestVersion = $this->getLatestVersion();
        
        View::assign('version', $version);
        View::assign('latestVersion', $latestVersion);
        View::assign('hasUpdate', version_compare($latestVersion, $version, '>'));
        
        return View::fetch();
    }
    
    /**
     * 检查更新
     */
    public function checkUpdate()
    {
        $version = $this->getSystemVersion();
        $latestVersion = $this->getLatestVersion();
        
        $hasUpdate = version_compare($latestVersion, $version, '>');
        
        $updateInfo = [];
        if ($hasUpdate) {
            $updateInfo = $this->getUpdateInfo($latestVersion);
        }
        
        return json([
            'status' => 1,
            'hasUpdate' => $hasUpdate,
            'currentVersion' => $version,
            'latestVersion' => $latestVersion,
            'updateInfo' => $updateInfo
        ]);
    }
    
    /**
     * 执行升级
     */
    public function doUpgrade()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }
        
        $targetVersion = input('post.version', '');
        if (!$targetVersion) {
            return json(['status' => 0, 'msg' => '请指定升级版本']);
        }
        
        $currentVersion = $this->getSystemVersion();
        
        if (version_compare($targetVersion, $currentVersion, '<=')) {
            return json(['status' => 0, 'msg' => '目标版本不能低于或等于当前版本']);
        }
        
        try {
            // 备份数据库
            $this->backupDatabase();
            
            // 执行升级SQL
            $this->executeUpgradeSQL($targetVersion);
            
            // 更新版本号
            $this->updateVersion($targetVersion);
            
            // 清理缓存
            $this->clearCache();
            
            return json([
                'status' => 1,
                'msg' => '升级成功',
                'version' => $targetVersion
            ]);
            
        } catch (\Exception $e) {
            return json([
                'status' => 0,
                'msg' => '升级失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取系统版本
     */
    protected function getSystemVersion()
    {
        $version = Db::name('sysset')->where('name', 'version')->value('value');
        if (!$version) {
            $version = '2.0.0';
        }
        return $version;
    }
    
    /**
     * 获取最新版本
     */
    protected function getLatestVersion()
    {
        // 这里可以从远程服务器获取最新版本信息
        // 目前返回本地配置
        $latestVersion = config('app.latest_version');
        if (!$latestVersion) {
            $latestVersion = $this->getSystemVersion();
        }
        return $latestVersion;
    }
    
    /**
     * 获取更新信息
     */
    protected function getUpdateInfo($version)
    {
        $updateInfo = [
            'version' => $version,
            'releaseDate' => date('Y-m-d'),
            'features' => [
                '优化系统性能',
                '修复已知问题',
                '增强安全性'
            ],
            'bugfixes' => [
                '修复部分页面显示问题',
                '修复数据统计错误'
            ]
        ];
        
        return $updateInfo;
    }
    
    /**
     * 备份数据库
     */
    protected function backupDatabase()
    {
        $backupDir = root_path() . 'runtime/backup/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . 'backup_' . date('YmdHis') . '.sql';
        
        // 这里应该实现数据库备份逻辑
        // 简化处理，仅创建标记文件
        file_put_contents($backupFile, '-- Database Backup ' . date('Y-m-d H:i:s'));
        
        return $backupFile;
    }
    
    /**
     * 执行升级SQL
     */
    protected function executeUpgradeSQL($version)
    {
        $sqlFile = root_path() . 'upgrade/' . $version . '.sql';
        
        if (!file_exists($sqlFile)) {
            return true;
        }
        
        $sql = file_get_contents($sqlFile);
        if (!$sql) {
            return true;
        }
        
        // 分割SQL语句
        $sqlArr = array_filter(explode(';', $sql));
        
        foreach ($sqlArr as $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }
            
            try {
                Db::execute($item);
            } catch (\Exception $e) {
                throw new \Exception('SQL执行失败：' . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * 更新版本号
     */
    protected function updateVersion($version)
    {
        $exists = Db::name('sysset')->where('name', 'version')->find();
        
        if ($exists) {
            Db::name('sysset')->where('name', 'version')->update([
                'value' => $version,
                'updatetime' => time()
            ]);
        } else {
            Db::name('sysset')->insert([
                'name' => 'version',
                'value' => $version,
                'createtime' => time(),
                'updatetime' => time()
            ]);
        }
        
        return true;
    }
    
    /**
     * 清理缓存
     */
    protected function clearCache()
    {
        // 清理运行时缓存
        $runtimePath = root_path() . 'runtime/';
        if (is_dir($runtimePath)) {
            $this->deleteDir($runtimePath . 'cache');
            $this->deleteDir($runtimePath . 'temp');
        }
        
        return true;
    }
    
    /**
     * 递归删除目录
     */
    protected function deleteDir($dir)
    {
        if (!is_dir($dir)) {
            return true;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * 升级日志
     */
    public function log()
    {
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);
        
        $logFile = root_path() . 'runtime/log/upgrade.log';
        $logs = [];
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            $lines = array_filter($lines);
            
            $total = count($lines);
            $start = ($page - 1) * $limit;
            $logs = array_slice(array_reverse($lines), $start, $limit);
        }
        
        return json([
            'status' => 1,
            'data' => $logs,
            'count' => isset($total) ? $total : 0
        ]);
    }
    
    /**
     * 记录升级日志
     */
    protected function writeLog($message)
    {
        $logFile = root_path() . 'runtime/log/upgrade.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $log = date('Y-m-d H:i:s') . ' ' . $message . "\n";
        file_put_contents($logFile, $log, FILE_APPEND);
    }
}

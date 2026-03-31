<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminUpgradeDownloadLog extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_upgrade_download_log';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    public function license()
    {
        return $this->belongsTo('SysadminLicense', 'license_id', 'id');
    }
    
    public function version()
    {
        return $this->belongsTo('SysadminUpgradeVersion', 'version_id', 'id');
    }
}
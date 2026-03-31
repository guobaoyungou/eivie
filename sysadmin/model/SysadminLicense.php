<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminLicense extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_license';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function edition()
    {
        return $this->belongsTo('SysadminLicenseEdition', 'edition_id', 'id');
    }
    
    public function heartbeatLogs()
    {
        return $this->hasMany('SysadminHeartbeatLog', 'license_id', 'id');
    }
    
    public function activationLogs()
    {
        return $this->hasMany('SysadminActivationLog', 'license_id', 'id');
    }
    
    public function piracyAlerts()
    {
        return $this->hasMany('SysadminPiracyAlert', 'license_id', 'id');
    }
    
    public function upgradeDownloadLogs()
    {
        return $this->hasMany('SysadminUpgradeDownloadLog', 'license_id', 'id');
    }
}
<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminUpgradeVersion extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_upgrade_version';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function downloadLogs()
    {
        return $this->hasMany('SysadminUpgradeDownloadLog', 'version_id', 'id');
    }
}
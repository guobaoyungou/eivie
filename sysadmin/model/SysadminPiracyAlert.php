<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminPiracyAlert extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_piracy_alert';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    public function license()
    {
        return $this->belongsTo('SysadminLicense', 'license_id', 'id');
    }
}
<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminActivationLog extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_activation_log';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    public function license()
    {
        return $this->belongsTo('SysadminLicense', 'license_id', 'id');
    }
}
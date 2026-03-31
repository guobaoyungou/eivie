<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminLicenseEdition extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_license_edition';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function licenses()
    {
        return $this->hasMany('SysadminLicense', 'edition_id', 'id');
    }
}
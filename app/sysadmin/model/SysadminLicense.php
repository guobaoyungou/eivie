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
}
<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminAdmin extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_admin';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
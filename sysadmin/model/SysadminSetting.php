<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminSetting extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_setting';
    protected $autoWriteTimestamp = true;
    protected $createTime = false;
    protected $updateTime = 'update_time';
}
<?php
namespace app\sysadmin\model;

use think\Model;

class SysadminBlacklist extends Model
{
    protected $connection = 'sysadmin';
    protected $table = 'sa_blacklist';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
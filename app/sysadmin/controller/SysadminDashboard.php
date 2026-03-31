<?php
namespace app\sysadmin\controller;

use app\sysadmin\middleware\SysadminAuth;
use think\Controller;

class SysadminDashboard extends Controller
{
    protected $middleware = [SysadminAuth::class];
    
    public function index()
    {
        return '授权管理后台仪表盘';
    }
}
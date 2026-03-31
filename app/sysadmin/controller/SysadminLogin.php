<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminAdmin;
use think\Controller;
use think\facade\Session;
use think\response\Json;

class SysadminLogin extends Controller
{
    public function index()
    {
        return $this->fetch('login/login');
    }
    
    public function login()
    {
        $username = $this->request->param('username');
        $password = $this->request->param('password');
        
        $admin = SysadminAdmin::where('username', $username)->where('status', 1)->find();
        if (!$admin) {
            return Json::create(['code' => 0, 'msg' => '账号不存在或已禁用']);
        }
        
        if (!password_verify($password, $admin->password)) {
            return Json::create(['code' => 0, 'msg' => '密码错误']);
        }
        
        Session::set('sysadmin_admin_id', $admin->id);
        Session::set('sysadmin_admin_name', $admin->nickname);
        
        $admin->last_login_time = time();
        $admin->last_login_ip = $this->request->ip();
        $admin->save();
        
        return Json::create(['code' => 1, 'msg' => '登录成功', 'url' => '/sysadmin/dashboard']);
    }
    
    public function logout()
    {
        Session::delete('sysadmin_admin_id');
        Session::delete('sysadmin_admin_name');
        return redirect('/sysadmin/login');
    }
}
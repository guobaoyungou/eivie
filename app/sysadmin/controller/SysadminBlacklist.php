<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminBlacklist;
use think\Controller;

class SysadminBlacklist extends Controller
{
    public function index()
    {
        $blacklists = SysadminBlacklist::order('id desc')->paginate(10);
        return $this->fetch('blacklist/index', ['blacklists' => $blacklists]);
    }
    
    public function add()
    {
        return $this->fetch('blacklist/add');
    }
    
    public function save()
    {
        $data = $this->request->param();
        $blacklist = new SysadminBlacklist();
        $blacklist->save($data);
        return $this->success('添加成功', 'blacklist/index');
    }
    
    public function remove($id)
    {
        SysadminBlacklist::destroy($id);
        return $this->success('移除成功', 'blacklist/index');
    }
}
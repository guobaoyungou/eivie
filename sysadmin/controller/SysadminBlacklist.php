<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminBlacklist;
use think\Controller;
use think\response\Json;

class SysadminBlacklist extends Controller
{
    public function index()
    {
        $blacklist = SysadminBlacklist::order('create_time DESC')->paginate(20);
        $this->assign('blacklist', $blacklist);
        return $this->fetch('blacklist/index');
    }
    
    public function add()
    {
        return $this->fetch('blacklist/add');
    }
    
    public function save()
    {
        $data = $this->request->param();
        
        $blacklist = new SysadminBlacklist();
        $blacklist->domain = $data['domain'];
        $blacklist->domain_hash = md5($data['domain']);
        $blacklist->ip = $data['ip'];
        $blacklist->type = $data['type'];
        $blacklist->reason = $data['reason'];
        $blacklist->source_license_id = $data['source_license_id'];
        $blacklist->expire_time = $data['expire_time'] ? strtotime($data['expire_time']) : 0;
        $blacklist->create_time = time();
        
        if ($blacklist->save()) {
            return Json::create(['code' => 1, 'msg' => '添加成功', 'url' => '/sysadmin/blacklist']);
        } else {
            return Json::create(['code' => 0, 'msg' => '添加失败']);
        }
    }
    
    public function remove($id)
    {
        $blacklist = SysadminBlacklist::find($id);
        if ($blacklist && $blacklist->delete()) {
            return Json::create(['code' => 1, 'msg' => '移除成功']);
        }
        return Json::create(['code' => 0, 'msg' => '操作失败']);
    }
}
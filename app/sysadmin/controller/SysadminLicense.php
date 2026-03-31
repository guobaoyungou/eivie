<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminLicense;
use think\Controller;

class SysadminLicense extends Controller
{
    public function index()
    {
        $licenses = SysadminLicense::order('id desc')->paginate(10);
        return $this->fetch('license/index', ['licenses' => $licenses]);
    }
    
    public function create()
    {
        return $this->fetch('license/create');
    }
    
    public function save()
    {
        $data = $this->request->param();
        $license = new SysadminLicense();
        $license->save($data);
        return $this->success('创建成功', 'license/index');
    }
    
    public function edit($id)
    {
        $license = SysadminLicense::find($id);
        return $this->fetch('license/edit', ['license' => $license]);
    }
    
    public function update()
    {
        $data = $this->request->param();
        $license = SysadminLicense::find($data['id']);
        $license->save($data);
        return $this->success('更新成功', 'license/index');
    }
    
    public function revoke($id)
    {
        $license = SysadminLicense::find($id);
        $license->status = 0;
        $license->save();
        return $this->success('撤销成功', 'license/index');
    }
    
    public function renew($id)
    {
        $license = SysadminLicense::find($id);
        return $this->fetch('license/renew', ['license' => $license]);
    }
    
    public function doRenew()
    {
        $data = $this->request->param();
        $license = SysadminLicense::find($data['id']);
        $license->expire_time = strtotime($data['expire_time']);
        $license->save();
        return $this->success('续期成功', 'license/index');
    }
}
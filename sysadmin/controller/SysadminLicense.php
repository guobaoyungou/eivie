<?php
namespace app\sysadmin\controller;

use app\sysadmin\model\SysadminLicense;
use app\sysadmin\model\SysadminLicenseEdition;
use think\Controller;
use think\response\Json;

class SysadminLicense extends Controller
{
    public function index()
    {
        $licenses = SysadminLicense::with('edition')->order('create_time DESC')->paginate(20);
        $this->assign('licenses', $licenses);
        return $this->fetch('license/index');
    }
    
    public function create()
    {
        $editions = SysadminLicenseEdition::where('status', 1)->select();
        $this->assign('editions', $editions);
        return $this->fetch('license/create');
    }
    
    public function save()
    {
        $data = $this->request->param();
        
        $licenseService = new \app\sysadmin\service\SysadminLicenseService();
        $result = $licenseService->createLicense($data);
        
        if ($result['code']) {
            return Json::create(['code' => 1, 'msg' => '创建成功', 'url' => '/sysadmin/license']);
        } else {
            return Json::create(['code' => 0, 'msg' => $result['msg']]);
        }
    }
    
    public function edit($id)
    {
        $license = SysadminLicense::find($id);
        $editions = SysadminLicenseEdition::where('status', 1)->select();
        $this->assign('license', $license);
        $this->assign('editions', $editions);
        return $this->fetch('license/edit');
    }
    
    public function update()
    {
        $data = $this->request->param();
        
        $licenseService = new \app\sysadmin\service\SysadminLicenseService();
        $result = $licenseService->updateLicense($data);
        
        if ($result['code']) {
            return Json::create(['code' => 1, 'msg' => '更新成功', 'url' => '/sysadmin/license']);
        } else {
            return Json::create(['code' => 0, 'msg' => $result['msg']]);
        }
    }
    
    public function revoke($id)
    {
        $license = SysadminLicense::find($id);
        if ($license) {
            $license->status = 2;
            $license->save();
            return Json::create(['code' => 1, 'msg' => '吊销成功']);
        }
        return Json::create(['code' => 0, 'msg' => '操作失败']);
    }
    
    public function renew($id)
    {
        $license = SysadminLicense::find($id);
        $editions = SysadminLicenseEdition::where('status', 1)->select();
        $this->assign('license', $license);
        $this->assign('editions', $editions);
        return $this->fetch('license/renew');
    }
    
    public function doRenew()
    {
        $data = $this->request->param();
        
        $licenseService = new \app\sysadmin\service\SysadminLicenseService();
        $result = $licenseService->renewLicense($data);
        
        if ($result['code']) {
            return Json::create(['code' => 1, 'msg' => '续期成功', 'url' => '/sysadmin/license']);
        } else {
            return Json::create(['code' => 0, 'msg' => $result['msg']]);
        }
    }
}
<?php
/**
 * 点大商城（www.diandashop.com） - 微信公众号小程序商城系统!
 * Copyright © 2020 山东点大网络科技有限公司 保留所有权利
 * =========================================================
 * 版本：V2
 * 授权主体：shop.guobaoyungou.cn
 * 授权域名：guobaoyungou.cn
 * 授权码：TZJcxBSGGdtDBIxFerKVJo
 * ----------------------------------------------
 * 您只能在商业授权范围内使用，不可二次转售、分发、分享、传播
 * 任何企业和个人不得对代码以任何目的任何形式的再发布
 * =========================================================
 */

// +----------------------------------------------------------------------
// | 基础控制器 - 全局常量定义、系统信息获取
// +----------------------------------------------------------------------
namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Base extends BaseController
{
    public $aid;
    public $mid;
    public $bid;
    public $platform;
    public $sysset;
    public $admin;
    public $member;
    public $business;
    public $agent;
    
    public function initialize()
    {
        parent::initialize();
        
        $request = request();
        
        // 获取aid参数
        $aid = input('param.aid/d');
        if (!$aid) {
            $aid = session('ADMIN_AID');
        }
        
        if (!$aid) {
            $this->error('参数错误');
        }
        
        $this->aid = $aid;
        
        // 定义全局常量aid
        if (!defined('aid')) {
            define('aid', $aid);
        }
        
        // 获取系统信息
        $admin = Db::name('admin')->where('id', $aid)->find();
        if (!$admin) {
            $this->error('系统信息不存在');
        }
        
        // 检查系统状态
        if ($admin['status'] == 0) {
            $this->error('账号未启用');
        }
        
        if ($admin['endtime'] > 0 && $admin['endtime'] < time()) {
            $this->error('账号已过期');
        }
        
        $this->admin = $admin;
        
        // 获取平台参数
        $platform = input('param.platform', '');
        if ($platform && !in_array($platform, ['mp', 'wx', 'alipay', 'baidu', 'toutiao', 'qq', 'h5', 'app'])) {
            $platform = '';
        }
        
        if (!$platform) {
            if (is_weixin()) {
                $platform = 'mp';
            } else {
                $platform = 'h5';
            }
        }
        
        $this->platform = $platform;
        
        // 定义全局常量platform
        if (!defined('platform')) {
            define('platform', $platform);
        }
        
        // 获取会员ID
        $mid = input('param.mid/d', 0);
        if (!$mid) {
            $mid = session('MID');
        }
        $this->mid = $mid;
        
        // 定义全局常量mid
        if (!defined('mid')) {
            define('mid', $mid);
        }
        
        // 获取商家ID
        $bid = input('param.bid/d', 0);
        if (!$bid) {
            $bid = session('ADMIN_BID');
        }
        $this->bid = $bid;
        
        // 定义全局常量bid
        if (!defined('bid')) {
            define('bid', $bid);
        }
        
        // 获取会员信息
        if ($mid > 0) {
            $member = Db::name('member')->where('id', $mid)->where('aid', $aid)->find();
            if ($member) {
                $this->member = $member;
            }
        }
        
        // 获取商家信息
        if ($bid > 0) {
            $business = Db::name('business')->where('id', $bid)->where('aid', $aid)->find();
            if ($business) {
                $this->business = $business;
            }
        }
        
        // 获取代理商信息
        $agentid = 0;
        if (isset($this->member['agentid'])) {
            $agentid = $this->member['agentid'];
        }
        if ($agentid > 0) {
            $agent = Db::name('agent')->where('id', $agentid)->where('aid', $aid)->find();
            if ($agent) {
                $this->agent = $agent;
            }
        }
        
        // 获取系统设置
        $this->sysset = \app\common\Common::getSysset();
        
        // 设置附件URL
        $this->setAttachUrl();
        
        // 传递常用变量到视图
        View::assign('aid', $this->aid);
        View::assign('mid', $this->mid);
        View::assign('bid', $this->bid);
        View::assign('platform', $this->platform);
    }
    
    /**
     * 设置附件访问URL
     */
    protected function setAttachUrl()
    {
        $admin = $this->admin;
        
        // 判断是否使用OSS
        if (isset($admin['oss']) && $admin['oss'] == 1) {
            // 阿里云OSS
            $ossset = Db::name('oss_set')->where('aid', $this->aid)->find();
            if ($ossset && $ossset['status'] == 1) {
                define('ATTACH_URL', $ossset['url']);
                return;
            }
        }
        
        // 判断是否使用腾讯云COS
        if (isset($admin['cos']) && $admin['cos'] == 1) {
            $cosset = Db::name('cos_set')->where('aid', $this->aid)->find();
            if ($cosset && $cosset['status'] == 1) {
                define('ATTACH_URL', $cosset['url']);
                return;
            }
        }
        
        // 判断是否使用七牛云
        if (isset($admin['qiniu']) && $admin['qiniu'] == 1) {
            $qiniuset = Db::name('qiniu_set')->where('aid', $this->aid)->find();
            if ($qiniuset && $qiniuset['status'] == 1) {
                define('ATTACH_URL', $qiniuset['url']);
                return;
            }
        }
        
        // 使用本地存储
        define('ATTACH_URL', request()->domain());
    }
    
    /**
     * 成功返回
     */
    protected function success($msg = '操作成功', $data = [], $url = '')
    {
        $result = [
            'status' => 1,
            'msg' => $msg,
            'data' => $data
        ];
        
        if ($url) {
            $result['url'] = $url;
        }
        
        return json($result);
    }
    
    /**
     * 失败返回
     */
    protected function error($msg = '操作失败', $data = [], $code = 0)
    {
        $result = [
            'status' => 0,
            'msg' => $msg,
            'data' => $data
        ];
        
        if ($code) {
            $result['code'] = $code;
        }
        
        return json($result);
    }
    
    /**
     * 检查系统是否正常
     */
    protected function checkSystem()
    {
        if (!$this->admin) {
            return false;
        }
        
        if ($this->admin['status'] == 0) {
            return false;
        }
        
        if ($this->admin['endtime'] > 0 && $this->admin['endtime'] < time()) {
            return false;
        }
        
        return true;
    }
}

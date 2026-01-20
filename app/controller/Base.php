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


namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Session;

class Base extends BaseController
{
    public $aid = 0;
    public $mid = 0;
    public $member = [];
    public $agent = [];
    public $platform = '';
    public $pre_url = '';
    public $uniacid = 0;
    public $attachurl = '';
    public $attachurl_local = '';
    public $attachurl_cos = '';
    public $attachurl_oss = '';
    public $attachurl_qiniu = '';
    public $webinfo = [];
    public $pluginfo = [];
    public $plugconfig = [];
    public $plugset = [];
    public $plugsysset = [];
    public $plugmodule = [];
    public $plugmoduleinfo = [];
    public $plugmoduleconfig = [];
    public $plugmoduleset = [];
    public $plugmodulesysset = [];
    public $plugmoduleplug = [];
    public $plugmodulepluginfo = [];
    public $plugmoduleplugconfig = [];
    public $plugmoduleplugset = [];
    public $plugmodulepluginsysset = [];

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        
        // 初始化平台和URL
        $this->platform = request()->param('platform/s', 'h5');
        $this->pre_url = defined('PRE_URL') ? PRE_URL : request()->domain();
        
        // 获取aid（系统ID）
        $this->aid = request()->param('aid/d', 0);
        
        // 获取mid（会员ID）
        $this->mid = request()->param('mid/d', 0);
        
        // 定义全局常量
        if (!defined('aid')) {
            define('aid', $this->aid);
        }
        if (!defined('mid')) {
            define('mid', $this->mid);
        }
        if (!defined('platform')) {
            define('platform', $this->platform);
        }
        
        try {
            // 获取系统信息
            $this->getWebInfo();
            
            // 获取会员信息
            if ($this->mid) {
                $this->getMemberInfo();
            }
            
            // 获取代理商信息
            if ($this->mid) {
                $this->getAgentInfo();
            }
            
            // 设置附件URL
            $this->setAttachUrl();
            
            // 检查系统是否关闭
            $this->checkSystemStatus();
        } catch (\Exception $e) {
            // 在调试模式下显示详细错误
            if (env('app_debug', false) || config('app.app_debug', false)) {
                throw $e; // 抛出异常以便调试
            } else {
                // 生产环境下记录错误日志
                \think\facade\Log::error('Base controller initialization error: ' . $e->getMessage());
            }
        }
    }

    /**
     * 获取系统信息
     */
    private function getWebInfo()
    {
        $sysinfo = Db::name('sysset')->where('name', 'webinfo')->find();
        if ($sysinfo && $sysinfo['value']) {
            $this->webinfo = json_decode($sysinfo['value'], true);
        }
    }

    /**
     * 获取会员信息
     */
    private function getMemberInfo()
    {
        if ($this->aid && $this->mid) {
            $this->member = Db::name('member')->where('aid', $this->aid)->where('id', $this->mid)->find();
            if (!$this->member) {
                $this->mid = 0;
            }
        }
    }

    /**
     * 获取代理商信息
     */
    private function getAgentInfo()
    {
        if ($this->aid && $this->mid) {
            $this->agent = Db::name('agent')->where('aid', $this->aid)->where('mid', $this->mid)->find();
        }
    }

    /**
     * 设置附件URL
     */
    private function setAttachUrl()
    {
        $this->attachurl = $this->pre_url . '/upload';
        $this->attachurl_local = $this->attachurl;
        
        // 根据系统设置确定实际的附件URL
        $storage_set = Db::name('sysset')->where('name', 'storage')->find();
        if ($storage_set && $storage_set['value']) {
            $storage_config = json_decode($storage_set['value'], true);
            if ($storage_config['type'] == 'cos') {
                $this->attachurl = $storage_config['cos']['url'];
                $this->attachurl_cos = $this->attachurl;
            } elseif ($storage_config['type'] == 'oss') {
                $this->attachurl = $storage_config['oss']['url'];
                $this->attachurl_oss = $this->attachurl;
            } elseif ($storage_config['type'] == 'qiniu') {
                $this->attachurl = $storage_config['qiniu']['url'];
                $this->attachurl_qiniu = $this->attachurl;
            }
        }
    }

    /**
     * 检查系统是否关闭
     */
    private function checkSystemStatus()
    {
        if (isset($this->webinfo['close_shop']) && $this->webinfo['close_shop'] == 1) {
            $close_msg = $this->webinfo['close_shop_text'] ?? '系统维护中，请稍后访问！';
            die('<div style="padding: 50px;"><h1>' . $close_msg . '</h1></div>');
        }
    }

    /**
     * 检查登录状态
     */
    protected function checkLogin()
    {
        if (!$this->mid || !$this->member) {
            $this->error('请先登录', $this->pre_url . '/login');
        }
    }

    /**
     * 成功提示
     */
    protected function success($msg = '', $url = '', $data = [])
    {
        $result = [
            'status' => 1,
            'msg' => $msg,
            'url' => $url,
            'data' => $data
        ];
        return json($result);
    }

    /**
     * 错误提示
     */
    protected function error($msg = '', $url = '', $data = [])
    {
        $result = [
            'status' => 0,
            'msg' => $msg,
            'url' => $url,
            'data' => $data
        ];
        return json($result);
    }

    /**
     * 检查权限
     */
    protected function checkAuth($permission = '')
    {
        // 实现权限检查逻辑
        return true;
    }
}
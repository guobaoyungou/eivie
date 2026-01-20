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
// | 收银台设置
// +----------------------------------------------------------------------
namespace app\controller;

use app\common\Alipay;
use app\common\Order;
use app\custom\Sxpay;
use think\facade\View;
use think\facade\Db;
use think\Log;

class CashierSet extends Common
{
    public function initialize()
    {
        parent::initialize();
    }

    public function index()
    {
        $domain = request()->domain();
        $c_where = [];
        $c_where[] = ['aid','=',aid];
        $c_where[] = ['bid','=',bid];
        $cashier = Db::name('cashier')->where($c_where)->find();
        if (empty($cashier)) {
            $insert =   ['createtime' => time(), 'bid' => bid, 'aid' => aid, 'name' => '收银台'];
            Db::name('cashier')->insert($insert);
            $cashier = Db::name('cashier')->where($c_where)->find();
        }
        $domain = PRE_URL;
        $cashier_url = $domain . '/cashier/index.html#/index/index?id=' . $cashier['id'];
        //入口文件作为参数传递
        $mode = trim(str_replace('.php','',$_SERVER['PHP_SELF']),'/');
        if($mode!='index'){
            $cashier_url.='&_mode='.$mode;
        }
        $bsysset = Db::name('business_sysset')->where('aid',aid)->find();
        $bwxtitle = '微信收款';
        View::assign('bwxtitle',$bwxtitle);
        $wxtitle = '微信';
        View::assign('wxtitle',$wxtitle);
        $login_url =  $domain.'/?s=/CashierLogin/index';
        $pinfo = Db::name('admin_setapp_cashdesk')->where('aid',aid)->where('bid',bid)->find();
        View::assign('sysset',$bsysset);
        View::assign('pinfo',$pinfo);
        View::assign('info',$cashier);
        View::assign('cashier_url', $cashier_url);
        View::assign('login_url', $login_url);
        //打印机
        $printArr = Db::name('wifiprint_set')->where('aid',aid)->where('bid',bid)->order('id')->where('machine_type',0)->column('name','id');
        View::assign('printArr',$printArr);
        return View::fetch();
    }
    public function save(){
        $info = input('post.info/a');
        $info['wxpay'] = !$info['wxpay']?0:$info['wxpay'];
        $info['cashpay'] = !$info['cashpay']?0:$info['cashpay'];
        $info['moneypay'] = !$info['moneypay']?0:$info['moneypay'];
        $info['jiaoban_print_ids'] = implode(',',$info['jiaoban_print_ids']);
        if($info['id']){
            $info['updatetime'] = time();
            Db::name('cashier')->where('aid',aid)->where('id',$info['id'])->update($info);
            \app\common\System::plog('编辑收银台'.$info['id']);
        }else{
            $info['aid'] = aid;
            $info['createtime'] = time();
            $id = Db::name('cashier')->insertGetId($info);
            \app\common\System::plog('添加收银台'.$id);
        }
        //设置支付信息
        $pinfo = input('post.pinfo/a');
        $pinfo['wxpay_apiclient_cert'] = str_replace(PRE_URL.'/','',$pinfo['wxpay_apiclient_cert']);
        $pinfo['wxpay_apiclient_key'] = str_replace(PRE_URL.'/','',$pinfo['wxpay_apiclient_key']);
        if(!empty($pinfo['wxpay_apiclient_cert']) && substr($pinfo['wxpay_apiclient_cert'], -4) != '.pem'){
            return json(['status'=>0,'msg'=>'PEM证书格式错误']);
        }
        if(!empty($pinfo['wxpay_apiclient_key']) && substr($pinfo['wxpay_apiclient_key'], -4) != '.pem'){
            return json(['status'=>0,'msg'=>'证书密钥格式错误']);
        }
        if($pinfo){
            $appinfo = Db::name('admin_setapp_cashdesk')->where('aid',aid)->where('bid',bid)->find();
            if($appinfo){
                Db::name('admin_setapp_cashdesk')->where('aid',aid)->where('bid',bid)->update($pinfo);
            }else{
                $pinfo['aid'] =aid;
                $pinfo['bid'] =bid;
                Db::name('admin_setapp_cashdesk')->insert($pinfo);
            }  
        }
       
        return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
    }


    public function cashier()
    {
        echo 'cashier html......';
//        return View::fetch();
    }
  
}

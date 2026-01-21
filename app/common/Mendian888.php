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

namespace app\common;
use think\facade\Db;
class Mendian
{
	//加余额
	public static function addmoney($aid,$mdid,$money,$remark){
		if($money==0) return ;
		$mendian = Db::name('mendian')->where('aid',$aid)->where('id',$mdid)->find();
		if(!$mendian) return ['status'=>0,'msg'=>'商家不存在'];
		Db::name('mendian')->where('aid',$aid)->where('id',$mdid)->inc('money',$money)->update();
		
		$data = [];
		$data['aid']    = $aid;
		$data['bid']    = $mendian['bid'];
		$data['mdid']   = $mdid;
		$data['money']  = $money;
		$data['after']  = $mendian['money'] + $money;
		$data['createtime'] = time();
		$data['remark'] = $remark;
		Db::name('mendian_moneylog')->insert($data);
		return ['status'=>1,'msg'=>''];
	}

    /**
     * 获取门店和商户名称
     * @param $order
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getNameWithBusines($order){
        $bname = '';
        if($order['bid']>0) {
            $bname = Db::name('business')->where('aid', $order['aid'])->where('id', $order['bid'])->value('name');
        }else{
            $bname = Db::name('admin_set')->where('aid', $order['aid'])->value('name');
        }
        if($order['mdid'] > 0){
            $mdname = Db::name('mendian')->where('id', $order['mdid'])->value('name');
            if($mdname)
                $bname = $bname.'-'.$mdname;
        }
        return $bname;
    }
}
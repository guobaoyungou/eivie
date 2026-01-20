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

//管理员中心 - 订单管理
namespace app\controller;
use think\facade\Db;
class ApiAdminOrder extends ApiAdmin
{
	//商城订单
	public function shoporder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if(getcustom('business_show_platform_product') && bid > 0){
			unset($where[1]); //删除bid条件
			$where[] = Db::raw("(bid = ".bid." or (bid = 0 and sell_business = ".bid."))");
		}
        if(input('param.keyword')){
            $keywords = input('param.keyword');
            $orderids = Db::name('shop_order_goods')->where($where)->where('name','like','%'.input('param.keyword').'%')->column('orderid');
            if(!$orderids){
                $where[] = ['ordernum|title', 'like', '%'.$keywords.'%'];
            }
        }
        $whereMd = '';
		if($this->user['mdid']){
            $whereMd = 'mdid='.$this->user['mdid'];
            if(getcustom('mendian_no_select')) {
                $whereMd .= ' or find_in_set (' . $this->user['mdid'] . ',mdids)';
            }
		}
		if(getcustom('mendian_usercenter')){
		    //不可处理门店中心的订单
            if(!$this->user['mdid']){
                $whereMd = 'mdid<=0 || mdid is null';
            }
        }

        if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}

        if(getcustom('yx_collage_jipin_optimize')){
            $where[] = ['is_jipin_show','=',1];
        }

		if(input('param.mid')) $where[] = ['mid','=',input('param.mid')];
        if(getcustom('fuwu_usercenter')){
            if($this->user['is_fuwu']){
                $where[] = ['fuwu_uid','=',$this->user['id']];
            }
        }
        if(getcustom('user_auth_province')){
            //管理员省市权限
//            $bids = \app\common\Business::get_auth_bids($this->user);
//            if($bids!='all'){
//                $where[] = ['bid','in',$bids];
//            }
        }
        if(getcustom('product_supplier_admin')){
            if($this->user['supplier_id']){
                $where[] = ['supplier_id','=',$this->user['supplier_id']];
            }
        }

		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
        $datalist = Db::name('shop_order')->where($where);
        if($orderids){
            $datalist->where(function ($query) use ($orderids,$keywords){
                $query->whereIn('id',$orderids)->whereOr('ordernum|title','like','%'.$keywords.'%');
            });
        }
        $datalist = $datalist->where($whereMd)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
            $datalist[$key]['prolist'] = [];
			$prolist = Db::name('shop_order_goods')->where('orderid',$v['id'])->select()->toArray();
            if(getcustom('product_glass')){
                foreach ($prolist as $pk=>$pv){
                    $prolist[$pk]['has_glassrecord'] = 0;
                    if($pv['glass_record_id']){
                        $glassrecord = \app\model\Glass::orderGlassRecord($pv['glass_record_id']);
                        if($glassrecord){
                            $prolist[$pk]['has_glassrecord'] = 1;
                            $prolist[$pk]['glassrecord'] = $glassrecord;
                        }
                    }
                }
            }
            if(getcustom('product_weight')){
                foreach ($prolist as $gk=>$gv){
                    $prolist[$gk]['product_type'] = $v['product_type'];
                    if($v['product_type']==2){
                        $prolist[$gk]['total_weight'] = round($gv['total_weight']/500,2);
                        $prolist[$gk]['real_total_weight'] = round($gv['real_total_weight']/500,2);
                    }
                }
            }
            if($prolist) $datalist[$key]['prolist'] = $prolist;
			$datalist[$key]['procount'] = Db::name('shop_order_goods')->where('orderid',$v['id'])->sum('num');
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];

            if(getcustom('shoporder_update')){
                $datalist[$key]['shoporder_update'] = true;
            }
            if(getcustom('shoporder_shdimg_mobile')){
                $datalist[$key]['shoporder_shdimg_mobile'] = true;
            }
            if(getcustom('wx_express_intracity')){
                $datalist[$key]['wx_express_intracity'] = true;
                if($v['wxtc_wx_order_id'] && $v['status'] == 2){
                    $order_status = \app\custom\WxExpressIntracity::order_status;
                    $wxtc_order = Db::name('peisong_order_wx_express_intracity')->where('aid', aid)->where('orderid', $v['id'])->where('wx_order_id', $v['wxtc_wx_order_id'])->find();
                    $datalist[$key]['wxtc_status_name'] = $order_status[$wxtc_order['order_status']] ?? '';
                }
            }
		}
        $wifiprintAuth = false;
        if(getcustom('shop_order_mobile_wifiprint')){
            $wifiprintAuth = true;
        }
        $shoporder_copy = false;
        if(getcustom('shoporder_copy')){
            $shoporder_copy = true;
        }
        $shoporder_update_member = false;
        if(getcustom('shoporder_update_member')){
            $shoporder_update_member = true;
        }

		$rdata = [];
        $rdata['wifiprintAuth'] = $wifiprintAuth;
        $rdata['shoporder_copy'] = $shoporder_copy;
        $rdata['shoporder_update_member'] = $shoporder_update_member;
		$rdata['datalist'] = $datalist;
		$rdata['codtxt'] = Db::name('shop_sysset')->where('aid',aid)->value('codtxt');
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//商城订单详情
	public function shoporderdetail(){

		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if(getcustom('business_show_platform_product')  && bid > 0){
			unset($where[1]); //删除bid条件
			$where[] = Db::raw("(bid = ".bid." or (bid = 0 and sell_business = ".bid."))");
		}
        $detail = Db::name('shop_order')->where('id',input('param.id/d'))->where($where)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
        $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
        $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
        $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
        $detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'shop_order');

        $member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
        $detail['nickname'] = $member['nickname'];
        $detail['headimg'] = $member['headimg'];
        $canFahuo = 0;
        if($detail['status']==1){
            $canFahuo = 1;
        }
        if(getcustom('erp_wangdiantong')){
            if($detail['status']==1 && $detail['wdt_status']==2) {
                $canFahuo = 1;
            }elseif($detail['status']==1 && $detail['wdt_status']==1){
                $canFahuo = 0;//由ERP进行发货
            }
        }
        if(getcustom('supply_zhenxin')){
            if($detail['issource'] == 1 && $detail['source'] == 'supply_zhenxin'){
                $canFahuo = 0;
            }
        }
        if(getcustom('supply_yongsheng')){
            if($detail['issource'] == 1 && $detail['source'] == 'supply_yongsheng'){
                $canFahuo = 0;
            }
        }
        $detail['can_fahuo'] = $canFahuo;
        $storeinfo = [];
        $storelist = [];
        $detail['hidefahuo'] = false;
        if($detail['freight_type'] == 1){
            if($detail['mdid'] == -1){
                $freight = Db::name('freight')->where('id',$detail['freight_id'])->find();
                if($freight && $freight['hxbids']){
                    if($detail['longitude'] && $detail['latitude']){
                        $orderBy = Db::raw("({$detail['longitude']}-longitude)*({$detail['longitude']}-longitude) + ({$detail['latitude']}-latitude)*({$detail['latitude']}-latitude) ");
                    }else{
                        $orderBy = 'sort desc,id';
                    }
                    $storelist = Db::name('business')->where('aid',$freight['aid'])->where('id','in',$freight['hxbids'])->where('status',1)->field('id,name,logo pic,longitude,latitude,address')->order($orderBy)->select()->toArray();
                    foreach($storelist as $k2=>$v2){
                        if($detail['longitude'] && $detail['latitude'] && $v2['longitude'] && $v2['latitude']){
                            $v2['juli'] = '距离'.getdistance($detail['longitude'],$detail['latitude'],$v2['longitude'],$v2['latitude'],2).'千米';
                        }else{
                            $v2['juli'] = '';
                        }
                        $storelist[$k2] = $v2;
                    }
                }
            }else{
                $storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('id,name,address,longitude,latitude')->find();
            }
            if(getcustom('freight_selecthxbids')){
                $detail['hidefahuo'] = true;
            }
        }

        $prolist = Db::name('shop_order_goods')->where('orderid',$detail['id'])->select()->toArray();
        $isjici = 0;
        foreach ($prolist as $pk=>$pv){
            $prolist[$pk]['is_quanyi'] = (isset($pv['product_type']) && $pv['product_type']==8) ? 1 : 0;
        }
//            if(getcustom('probgcolor')){
//                $shopset = Db::name('shop_sysset')->where('aid',aid)->field('comment,autoclose,canrefund,order_detail_toppic')->find();
//            }else{
//                $shopset = Db::name('shop_sysset')->where('aid',aid)->field('comment,autoclose,canrefund')->find();
//            }

        $shopsetfield = 'comment,autoclose,canrefund';

        if(getcustom('probgcolor')){
            $shopsetfield .= ',order_detail_toppic';
        }

        if(getcustom('product_service_fee')){
            $shopsetfield .= ',show_shd_remark';
        }

        if(getcustom('product_thali')){
            $shopsetfield .= ',product_shop_school';
        }

        if (getcustom('shoporder_changeprice')){
            $shopsetfield .= ',changeprice_status';
        }

        if (getcustom('shoporder_admin_refund_switch')){
            $shopsetfield .= ',order_admin_refund_switch';
        }

        if (getcustom('shoporder_admin_payorder_switch')){
            $shopsetfield .= ',order_admin_payorder_switch';
        }

        $shopset = Db::name('shop_sysset')->where('aid',aid)->field($shopsetfield)->find();
        if (!getcustom('shoporder_changeprice')){
            $shopset['changeprice_status'] = 1;
        }

        if($detail['status']==0 && $shopset['autoclose'] > 0){
            $lefttime = strtotime($detail['createtime']) + $shopset['autoclose']*60 - time();
            if($lefttime < 0) $lefttime = 0;
        }else{
            $lefttime = 0;
        }

        //弃用
        if($detail['field1']){
            $detail['field1data'] = explode('^_^',$detail['field1']);
        }
        if($detail['field2']){
            $detail['field2data'] = explode('^_^',$detail['field2']);
        }
        if($detail['field3']){
            $detail['field3data'] = explode('^_^',$detail['field3']);
        }
        if($detail['field4']){
            $detail['field4data'] = explode('^_^',$detail['field4']);
        }
        if($detail['field5']){
            $detail['field5data'] = explode('^_^',$detail['field5']);
        }
        $peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
        if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
        $detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
        $detail['express_wx_status'] = $peisong_set['express_wx_status']==1 ? true : false;

        if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }

        if($detail['freight_type'] == 2){
            $peisong = Db::name('peisong_order')->where('orderid',$detail['id'])->where('type','shop_order')->field('id,psid')->find();
            if($peisong){
                $detail['psid'] = $peisong['psid'];
            }
        }

        if($detail['checkmemid']){
            $detail['checkmember'] = Db::name('member')->field('id,nickname,headimg,realname,tel')->where('id',$detail['checkmemid'])->find();
        }else{
            $detail['checkmember'] = [];
        }
        if(getcustom('product_glass')){
            foreach($prolist as $k=>$pro){
                if($pro['glass_record_id']){
                    $glassrecord = \app\model\Glass::orderGlassRecord($pro['glass_record_id'],aid);
                }
                $prolist[$k]['glassrecord'] = $glassrecord??'';
            }
        }
        if(getcustom('product_weight') && $detail['product_type']==2){
            //称重商品，单价 重量kg
            foreach ($prolist as $k=>$v){
                $prolist[$k]['total_weight'] = round($v['total_weight']/500,2);
                $prolist[$k]['real_total_weight'] = round($v['real_total_weight']/500,2);
                $prolist[$k]['product_type'] = 2;
            }
        }
        if(getcustom('product_service_fee') && $shopset['show_shd_remark']==1){
            foreach ($prolist as &$vv){
                $vv['shd_remark'] = Db::name('shop_product')->where('id',$vv['proid'])->value('shd_remark');
            }
            unset($vv);
        }
        $detail['message'] = \app\model\ShopOrder::checkOrderMessage($detail['id'],$detail);
        if(getcustom('pay_money_combine')){
            if($detail['combine_money'] && $detail['combine_money'] > 0){
                if(!empty($detail['paytype'])){
                    $detail['paytype'] .= ' + '.t('余额').'支付';
                }else{
                    $detail['paytype'] .= t('余额').'支付';
                }
            }
        }
        if(getcustom('product_pickup_device')){
            if($detail['dgid']&& $detail['freight_type'] ==1){
                $f_device =  Db::name('product_pickup_device_goods')->alias('dg')
                    ->join('product_pickup_device d','d.id = dg.device_id')
                    ->where('dg.aid',aid)->where('dg.id',$detail['dgid'])->field('d.address,d.name')->find();
                $storeinfo['address'] = $f_device['address'];
                $storeinfo['name'] = $f_device['name'];
            }
        }
        $detail['is_quanyi'] = 0;
        if(getcustom('product_quanyi') && $detail['product_type']==8){
            $detail['hexiao_num_remain'] = $detail['hexiao_num_total']-$detail['hexiao_num_used'];
            $detail['is_quanyi'] = 1;
        }

        //如果是兑换订单显示兑换码
        if($detail['lipin_dhcode'] && $detail['paytype'] == '兑换码兑换'){
            $detail['paytype'] = $detail['paytype']."(".$detail['lipin_dhcode'].")";
        }

        $detail['product_thali'] = false;
        if(getcustom('product_thali') && $shopset['product_shop_school'] == 1){
            $detail['product_thali'] = true;
        }

        $shopset['send_show'] = 1;
        $shopset['hexiao_show'] = 1;
        if($detail['bid'] > 0){
            $business_sysset = \db('business_sysset')->where('aid',aid)->find();
            if(getcustom('business_shop_order_send_show')){
                $shopset['send_show'] = $business_sysset['shop_order_send_show'];
            }
            if(getcustom('business_shop_order_hexiao_show')){
                $shopset['hexiao_show'] = $business_sysset['shop_order_hexiao_show'];
            }
        }
        if(getcustom('shop_giveorder')){
            if($detail['usegiveorder'] && $detail['giveordermid']>0){
                $givemember = Db::name('member')->where('id',$detail['giveordermid'])->field('id,nickname,headimg,tel,realname')->find();
            }
            $detail['givemember'] = $givemember??'';
        }

        if(getcustom('extend_planorder')){
            if($detail['poshopid']){
                $detail['poshop'] = Db::name('planorder_shop')->where('id',$detail['poshopid'])->field('id,name,pic')->find();
            }else{
                $detail['poshop'] = [];
            }
        }

        if(getcustom('supply_yongsheng')){
            if($detail['source'] == 'supply_yongsheng' && $detail['issource']){
                $detail['express_content'] = $express_content = \app\custom\SupplyYongsheng::dealExpressContent($detail,'shop');
                $detail['express_no'] = '';
                if($express_content){
                    $express_contentArr = json_decode($express_content,true);
                    $express_contentNum = count($express_contentArr);
                    if($express_contentNum == 1){
                        $detail['express_com'] = $express_contentArr[0]['express_com'];
                        $detail['express_com'] = $express_contentArr[0]['express_no'];
                    }else{
                        $detail['express_com'] = '多单发货';
                    }
                }else{
                    $detail['express_com'] = '无';
                }
            }
        }

        if(getcustom('shoporder_update')){
            $detail['shoporder_update'] = true;
            if(input('param.frompage') == 'updateOrder'){
                $newprolist = [];
                foreach ($prolist as $vk => $vp){
                    $vp['id'] = $vp['ggid'];
                    $newprolist[$vk]['guige'] = $vp;
                    $newprolist[$vk]['num'] = $vp['num'];
                    $newprolist[$vk]['name'] = $vp['name'];
                    $newprolist[$vk]['ggname'] = $vp['ggname'];
                    $newprolist[$vk]['sell_price'] = $vp['sell_price'];
                    $newprolist[$vk]['remark'] = $vp['remark'];
                    $newprolist[$vk]['product'] = Db::name('shop_product')->where('aid',$vp['aid'])->where('id',$vp['proid'])->find();
                }
                $prolist = $newprolist;

                $member = Db::name('member')->where('id',$detail['mid'])->find();
                $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$member['levelid'])->find();
                if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
                    $discount = $userlevel['discount']*0.1; //会员折扣
                }else{
                    $discount = 1;
                }
                $detail['discount'] = $discount;

                if($detail['status'] != 0){
                    return $this->json(['status'=>0,'msg'=>'当前订单状态不可修改']);
                }
            }
        }

        if(getcustom('shoporder_shdimg_mobile')){
            $detail['shoporder_shdimg_mobile'] = true;
        }

        //是否可以操作退款
        $can_refund = 1;
        $auth_data = $this->auth_data;
        if (getcustom('handle_auth') && ($auth_data!='all' || !in_array('ShopOrderRefund',$auth_data))){
            $can_refund = 0;
        }
        if(getcustom('saas_business_refund_order')){
            //控制台设置的商家退款
            if(bid>0){
                $business_refund_order = Db::name('admin')->where('id', aid)->value('business_refund_order');
                if(!$business_refund_order){
                    $can_refund = 0;
                }
            }
        }

        if (getcustom('shoporder_admin_refund_switch') && $shopset['order_admin_refund_switch']==0){
            $can_refund = 0;
        }

        $detail['can_refund'] = $can_refund;

        $detail['order_admin_payorder_switch'] = 1;
        if (getcustom('shoporder_admin_payorder_switch') && $shopset['order_admin_payorder_switch']==0){
            $detail['order_admin_payorder_switch'] = 0;
        }

        $del_auth = 1;
        if(getcustom('shoporder_del_auth')){
            $auth_data = json_decode($this->user['auth_data'],true);
            if($auth_data!='all' && !in_array('OrderDelAuth,OrderDelAuth',$auth_data)){
                $del_auth = 0;
            }
        }
        $detail['del_auth'] = $del_auth;

        if(getcustom('wx_express_intracity')){

            $detail['wx_express_intracity'] = true;
            if($peisong_set['wxtc_status'] != 1){
                $detail['wx_express_intracity'] = false;
            }

            if($detail['bid'] > 0 && $peisong_set['wxtc_status_business'] != 1){
                $binfo = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->find();
                if($binfo['wxtc_status'] != 1){
                    $detail['wx_express_intracity'] = false;
                }
            }

            $cargo_type = \app\custom\WxExpressIntracity::cargo_type;
            $detail['cargo_type_arr'] = $cargo_type;

            $peisong = Db::name('peisong_set')->where('aid',aid)->find();


            $iswxtcyf = 1;
            if($peisong['wxtc_status'] != 1){
                $iswxtcyf = 0;
            }

            if($detail['bid'] == 0){
                if($peisong['wxtc_store_id'] <= 0){
                    $iswxtcyf = 0;
                }

                $wxstore = Db::name('wx_express_intracity_store')->where('aid',aid)->where('id',$peisong['wxtc_store_id'])->find();
            }else{
                if($peisong['wxtc_status_business'] != 1){
                    $iswxtcyf = 0;
                }

                $binfo = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->find();

                if($binfo['wxtc_status'] != 1){
                    $iswxtcyf = 0;
                }
                if($binfo['wxtc_store_id'] <= 0){
                    $iswxtcyf = 0;
                }

                $wxstore = Db::name('wx_express_intracity_store')->where('aid',aid)->where('id',$binfo['wxtc_store_id'])->find();
            }

            if($wxstore['status'] != 1){
                $iswxtcyf = 0;
            }

            //查询重量
            $weight = \app\custom\WxExpressIntracity::getweight(aid,$detail,'shop_order');//默认一千克


            //查询预估配送费
            if($iswxtcyf == 1){
                $detail['cargo_weight'] = $weight['weight'] ?? 1000;
                $detail['cargo_num'] = $weight['num'] ?? 1;
                $detail['wx_store_id'] = $wxstore['wx_store_id'] ?? '';

                //预估配送费
                $res_price = \app\custom\WxExpressIntracity::preaddorder(aid,$detail);

                if($res_price['status'] == 1){
                    $data = $res_price['data'];
                    if($data && $data['data']){
                        $detail['est_fee'] = $data['data']['est_fee'] / 100 .'元';
                    }
                }
            }

            if($detail['wxtc_wx_order_id'] && $detail['status'] == 2){
                $order_status = \app\custom\WxExpressIntracity::order_status;
                $wxtc_order = Db::name('peisong_order_wx_express_intracity')->where('aid', aid)->where('orderid', $detail['id'])->where('wx_order_id', $detail['wxtc_wx_order_id'])->find();
                $detail['wxtc_status_name'] = $order_status[$wxtc_order['order_status']] ?? '';
            }

            $detail['cargo_weight'] = $weight? $weight['weight'] / 1000 : 1;
        }

        $rdata = [];
        $rdata['detail'] = $detail;
        $rdata['prolist'] = $prolist;
        $rdata['shopset'] = $shopset;
        $rdata['storeinfo'] = $storeinfo;
        $rdata['lefttime'] = $lefttime;
        $rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));
        $rdata['codtxt'] = Db::name('shop_sysset')->where('aid',aid)->value('codtxt');
        $rdata['storelist'] = $storelist;
        //是否是门店中心
        $rdata['mendian_usercenter'] = $this->user['mendian_usercenter']??0;
        $rdata['admin_user'] = $this->user;
        return $this->json($rdata);
	}
    //退款单列表
    public function shopRefundOrder(){
        $st = input('param.st');
        if(!input('param.st') || $st === ''){
            $st = 'all';
        }
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $order = ['id' => 'desc'];
        if($this->user['mdid']){
            $where[] = ['mdid','=',$this->user['mdid']];
        }
        if(input('param.keyword')) $where[] = ['refund_ordernum|ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){

        }elseif($st == '0'){
            $where[] = ['refund_status','=',0];
        }elseif($st == '1'){
            $where[] = ['refund_status','=',1];
            $order['id'] = 'asc';
        }elseif($st == '2'){
            $where[] = ['refund_status','=',2];
        }elseif($st == '3'){
            $where[] = ['refund_status','=',3];
        }

        $pernum = 10;
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;

        if(input('param.orderid/d')) {
            $where[] = ['orderid','=',input('param.orderid/d')];
            $pernum = 99;
        }

        $datalist = Db::name('shop_refund_order')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
        if(!$datalist) $datalist = array();
        foreach($datalist as $key=>$v){
            $datalist[$key]['prolist'] = Db::name('shop_refund_order_goods')->where('refund_orderid',$v['id'])->select()->toArray();
            if(!$datalist[$key]['prolist']) $datalist[$key]['prolist'] = [];
            $datalist[$key]['procount'] = Db::name('shop_refund_order_goods')->where('refund_orderid',$v['id'])->sum('refund_num');
            $datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
            if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
            if($v['refund_type'] == 'refund') {
                $datalist[$key]['refund_type_label'] = '退款';
            }elseif($v['refund_type'] == 'return') {
                $datalist[$key]['refund_type_label'] = '退货退款';
            }
        }
        $rdata = [];
        $rdata['datalist'] = $datalist;
        $rdata['st'] = $st;
        return $this->json($rdata);
    }
    public function shopRefundOrderDetail()
    {
        $where = [];
        $where[] = ['id','=',input('param.id/d')];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        if($this->user['mdid']){
            $where[] = ['mdid','=',$this->user['mdid']];
        }
        $detail = Db::name('shop_refund_order')->where($where)->find();
        if(!$detail) $this->json(['status'=>0,'msg'=>'退款单不存在']);
        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
        if($detail['refund_type'] == 'refund') {
            $detail['refund_type_label'] = '退款';
        }elseif($detail['refund_type'] == 'return') {
            $detail['refund_type_label'] = '退货退款';
        }
        if($detail['refund_pics']) {
            $detail['refund_pics'] = explode(',', $detail['refund_pics']);
        }
        unset($where['id']);
        $where[] = ['orderid', '=', $detail['orderid']];
        $detail['refundMoneyTotal'] =  Db::name('shop_refund_order')->where($where)->where('refund_status',2)->sum('refund_money');

        $member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
        $detail['nickname'] = $member['nickname'];
        $detail['headimg'] = $member['headimg'];

        $order = Db::name('shop_order')->where('id',$detail['orderid'])->where('aid',aid)->where('bid',bid)->find();
        $order['createtime'] = $order['createtime'] ? date('Y-m-d H:i:s',$order['createtime']) : '';
        $order['collect_time'] = $order['collect_time'] ? date('Y-m-d H:i:s',$order['collect_time']) : '';
        $order['paytime'] = $order['paytime'] ? date('Y-m-d H:i:s',$order['paytime']) : '';
        $order['refund_time'] = $order['refund_time'] ? date('Y-m-d H:i:s',$order['refund_time']) : '';
        $order['send_time'] = $order['send_time'] ? date('Y-m-d H:i:s',$order['send_time']) : '';
        $order['formdata'] = \app\model\Freight::getformdata($order['id'],'shop_order');

        $prolist = Db::name('shop_refund_order_goods')->where('refund_orderid',$detail['id'])->select()->toArray();
        if(getcustom('pay_money_combine')){
            if($detail['combine_money'] && $detail['combine_money'] > 0){
                if(!empty($detail['paytype'])){
                    $detail['paytype'] .= ' + '.t('余额').'支付';
                }else{
                    $detail['paytype'] .= t('余额').'支付';
                }
            }
        }

        $detail['cancheck'] = true;
        if(getcustom('supply_zhenxin')){
            if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                $detail['cancheck'] = false;
            }
        }
        if(getcustom('supply_yongsheng')){
            if($order['issource'] && $order['source'] == 'supply_yongsheng'){
                $detail['cancheck'] = false;
            }
        }

        //门店自提和同城配送类型退货判断 
        if($detail['refund_type'] != 'refund'){
            if($order['freight_type'] == 1 || $order['freight_type'] == 2){
                //是否通过快递发货 ，不是则退款类型可直接退款
                if(empty($order['express_no']) && empty($order['express_content'])){
                    $detail['refund_type'] = 'refund';
                }
            }
        }

        $rdata = [];
        $rdata['detail'] = $detail;
        $rdata['order'] = $order;
        $rdata['prolist'] = $prolist;
        return $this->json($rdata);
    }
	//拼团订单
	public function collageorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
	
		if($this->user['mdid']){
            $where[] = ['mdid', '=', $this->user['mdid']];
		}
        if(getcustom('yx_collage_team_in_team')){
            $where[] = ['isteaminteam','=',0];
        }
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('collage_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
	
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
			if($v['buytpe']!=1) $datalist[$key]['team'] = Db::name('collage_order_team')->where('id',$v['teamid'])->find();
		}
		$sysset = [];
        if(getcustom('mobile_collage_order_operate')){
            $sysset['show_operate'] =1;
        }
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['codtxt'] = Db::name('shop_sysset')->where('aid',aid)->value('codtxt');
		$rdata['st'] = $st;
        $rdata['sysset'] = $sysset;
        $rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));
        if(getcustom('mobile_collage_order_operate')){
            $rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));
        }
		return $this->json($rdata);
	}
	//拼团订单详情
	public function collageorderdetail(){
		$detail = Db::name('collage_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'collage_order');

		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];

		$storeinfo = [];
		if($detail['freight_type'] == 1){
			$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
		}
		
		if($detail['buytype'] != 1){
			$team = Db::name('collage_order_team')->where('id',$detail['teamid'])->find();
		}else{
			$team = [];
		}
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;

        if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }
        if(getcustom('mobile_collage_order_operate')){
            $detail['show_operate'] =1;
        }
		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['team'] = $team;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));

		return $this->json($rdata);
	}
    //拼团订单退款
    public function collageorderrefund(){
        $orderid = input('post.orderid/d');
        $reason = input('post.reason');
        $order = Db::name('collage_order')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->find();
        $refund_money = $order['totalprice'];
        if($order['status']!=1 && $order['status']!=2){
            return $this->json(['status'=>0,'msg'=>'该订单状态不允许退款']);
        }
        $team = Db::name('collage_order_team')->where('id',$order['teamid'])->find();
        if($team['status'] == 1) {
            return $this->json(['status'=>0,'msg'=>'拼团中不允许退款']);
        }

        if($refund_money > 0) {
            $rs = \app\common\Order::refund($order,$refund_money,$reason);
            if($rs['status']==0){
                return $this->json(['status'=>0,'msg'=>$rs['msg']]);
            }
        }

        Db::name('collage_order')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->update(['status'=>4,'refund_status'=>2, 'refund_money' => $refund_money]);
        if(getcustom('yx_collage_refund_to_continued')){
            $collage_set = Db::name('collage_sysset')->where('aid',aid)->where('bid',$order['bid'])->find();
            if($collage_set['refund_team_status'] ==1 && $team['status'] ==2){
                //成功的改为进行中 并 释放一个名额
                $upnum =  $team['num']-1 <=0?0:$team['num']-1;
                Db::name('collage_order_team')->where('aid',aid)->where('id',$order['teamid'])->update(['status' => 1,'num' => $upnum]);
            }
        }
        //积分抵扣的返还
        if($order['scoredkscore'] > 0){
            \app\common\Member::addscore(aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
        }
        //扣除消费赠送积分
        \app\common\Member::decscorein(aid,'collage',$order['id'],$order['ordernum'],'订单退款扣除消费赠送');
        //优惠券抵扣的返还
        if($order['coupon_rid'] > 0){
            \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
        }
        //退款退还佣金
        if(getcustom('commission_orderrefund_deduct')){
            \app\common\Fenxiao::refundFenxiao(aid,$order['id'],'collage');
            \app\common\Order::refundFenhongDeduct($order,'collage');
        }

        if(getcustom('yx_collage_team_in_team')){
            //扣除团中团赠送佣金
            \app\custom\CollageTeamInTeamCustom::deccommission(aid,$order['id'],$order['ordernum'],'订单退款扣除');
        }

        if(getcustom('yx_mangfan_collage')){
            \app\custom\Mangfan::delAndCreate(aid, $order['mid'], $order['id'], $order['ordernum'],'collage');
        }

        if(getcustom('yx_queue_free_collage')){
            \app\custom\QueueFree::orderRefundQuit($order,'collage');
        }

        //退款成功通知
        $tmplcontent = [];
        $tmplcontent['first'] = '您的订单已经完成退款，¥'.$refund_money.'已经退回您的付款账户，请留意查收。';
        $tmplcontent['remark'] = $reason.'，请点击查看详情~';
        $tmplcontent['orderProductPrice'] = $refund_money;
        $tmplcontent['orderProductName'] = $order['title'];
        $tmplcontent['orderName'] = $order['ordernum'];
        $tmplcontentNew = [];
        $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
        $tmplcontentNew['thing2'] = $order['title'];//商品名称
        $tmplcontentNew['amount3'] = $refund_money;//退款金额
        \app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
        //订阅消息
        $tmplcontent = [];
        $tmplcontent['amount6'] = $refund_money;
        $tmplcontent['thing3'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];

        $tmplcontentnew = [];
        $tmplcontentnew['amount3'] = $refund_money;
        $tmplcontentnew['thing6'] = $order['title'];
        $tmplcontentnew['character_string4'] = $order['ordernum'];
        \app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

        //短信通知
        $member = Db::name('member')->where('id',$order['mid'])->find();
        if($member['tel']){
            $tel = $member['tel'];
        }else{
            $tel = $order['tel'];
        }
        $rs = \app\common\Sms::send(aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$refund_money]);

        \app\common\System::plog('手机端拼团订单退款'.$orderid);
        return $this->json(['status'=>1,'msg'=>'已退款成功']);
    }
	
	
	 //周期购订单列表
    public function cycleorder(){
        $this->checklogin();
        $st = input('param.st');
        if(!input('?param.st') || $st === ''){
            $st = 'all';
        }
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['delete','=',0];
        
        if($this->user['mdid']){
            $where[] = ['mdid', '=', $this->user['mdid']];
        }
    
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){

        }elseif($st == '0'){
            $where[] = ['status','=',0];
        }elseif($st == '1'){
            $where[] = ['status','=',1];
        }elseif($st == '2'){
            $where[] = ['status','=',2];
        }elseif($st == '3'){
            $where[] = ['status','=',3];
        }elseif($st == '10'){
            $where[] = ['refund_status','>',0];
        }
        $pernum = 10;
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;
        $datalist = Db::name('cycle_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            
        if(!$datalist) $datalist = array();
        foreach($datalist as $key=>$v){
            //发票
            $datalist[$key]['invoice'] = 0;
            if($v['bid']) {
                $datalist[$key]['invoice'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->value('invoice');
            } else {
                $datalist[$key]['invoice'] = Db::name('admin_set')->where('aid',aid)->value('invoice');
            }
        }
        $rdata = [];
        $rdata['st'] = $st;
        $rdata['datalist'] = $datalist;
        return $this->json($rdata);
    }
    /**
     * 获取周期列表
     */
    public function getCycleList(){
        $orderid = input('param.id/d');
        $this->checklogin();
        $detail = Db::name('cycle_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
        if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        $list = Db::name('cycle_order_stage')
            ->where('orderid',$orderid)
            ->field('id,cycle_date,cycle_number,status')
            ->order('cycle_number asc')
            ->select()->toArray();
        foreach ($list as $k=>&$v){
            $v['title'] = '第'.$v['cycle_number'].'期';
        }
        return $this->json(['status'=>1,'data'=>$list,'detail' => $detail]);
    }
    public function cycleorderdetail(){
        $this->checklogin();

        $detail = Db::name('cycle_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
        if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        $member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
        $detail['nickname'] = $member['nickname'];
        $detail['headimg'] = $member['headimg'];
        
        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
        $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
        $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
        $detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'cycle_order');
        //配送频率

        $ps_cycle = ['1' => '每日一期','2' => '每周一期' ,'3' => '每月一期'];
        $every_day = ['1' => '每天配送','2' => '工作日配送' ,'3' => '周末配送','4' => '隔天配送'];

        $detail['pspl'] = $ps_cycle[$detail['ps_cycle']];
        if($detail['ps_cycle'] == 1){
            $detail['every_day'] =$every_day[$detail['fwtc']];
        }else{
            $detail['every_day'] = '';

        }

        $storeinfo = [];
        if($detail['freight_type'] == 1){
            $storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('id,name,address,longitude,latitude')->find();
        }

        $shopset = Db::name('cycle_sysset')->where('aid',aid)->find();
        if($detail['status']==0 && $shopset['autoclose'] > 0 && $detail['paytypeid'] != 5){
            $lefttime = strtotime($detail['createtime']) + $shopset['autoclose']*60 - time();
            if($lefttime < 0) $lefttime = 0;
        }else{
            $lefttime = 0;
        }

        $rdata = [];
        //发票
        $rdata['invoice'] = 0;
        if($detail['bid']) {
            $rdata['invoice'] = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->value('invoice');
        } else {
            $rdata['invoice'] = Db::name('admin_set')->where('aid',aid)->value('invoice');
        }
        $rdata['detail'] = $detail;
        $rdata['shopset'] = $shopset;
        $rdata['storeinfo'] = $storeinfo;
        $rdata['lefttime'] = $lefttime;
        return $this->json($rdata);
    }
    //核销
    public function cycleorderHexiao(){
        $post = input('post.');
        $orderid = intval($post['id']);
        $order = Db::name('cycle_order_stage')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->find();
         
        if(!$order || !in_array($order['status'], [1,2]) ){
            return $this->json(['status'=>0,'msg'=>'订单状态不符合完成要求1']);
        }
        $cycle_order = Db::name('cycle_order')->where('aid',aid)->where('bid',bid)->where('id',$order['orderid'])->find();
        if(!$cycle_order ||  $cycle_order['freight_type'] !=1){
            return $this->json(['status'=>0,'msg'=>'订单状态不符合完成要求2']);
        }
        Db::name('cycle_order_stage')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);

        $order_stage_count = Db::name('cycle_order_stage')
            ->where('status','in','0,1,2')
            ->where('orderid',$order['orderid'])
            ->count();
        if($order_stage_count == 0){
            Db::name('cycle_order')->where('aid',aid)->where('bid',bid)->where('id',$order['orderid'])->update(['status'=>3,'collect_time'=>time()]);

            $rs = \app\common\Order::collect($cycle_order, 'cycle');
            if($rs['status']==0) return $rs;
        }else{
            Db::name('cycle_order')->where('aid',aid)->where('bid',bid)->where('id',$order['orderid'])->update(['status'=>2]);
        }

        //发货信息录入 微信小程序+微信支付
        if($cycle_order['platform'] == 'wx' && $cycle_order['paytypeid'] == 2){
            \app\common\Order::wxShipping(aid,$cycle_order,'cycle');
        }

        \app\common\System::plog('周期购周期订单确认核销'.$orderid);
        return $this->json(['status'=>1,'msg'=>'订单完成']);
    }
    //确认收货
    public function cycleorderStageCollect(){
        $post = input('post.');
        $orderid = intval($post['orderid']);
        $order = Db::name('cycle_order_stage')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->find();

        if(!$order || !in_array($order['status'], [2])){
            return $this->json(['status'=>0,'msg'=>'订单状态不符合完成要求']);
        }
        Db::name('cycle_order_stage')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);


        $order_stage_count = Db::name('cycle_order_stage')
            ->where('status','in','0,1,2')
            ->where('orderid',$order['orderid'])
            ->count();
        if($order_stage_count == 0){
            Db::name('cycle_order')->where('aid',aid)->where('bid',bid)->where('id',$order['orderid'])->update(['status'=>3,'collect_time'=>time()]);

            $cycle_order = Db::name('cycle_order')->where('aid',aid)->where('bid',bid)->where('id',$order['orderid'])->find();
            $rs = \app\common\Order::collect($cycle_order, 'cycle');
            if($rs['status']==0) return $rs;
        }

        \app\common\System::plog('周期购周期订单确认收货'.$orderid);
        return $this->json(['status'=>1,'msg'=>'订单完成']);
    }
	//拼团订单
	public function luckycollageorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if($this->user['mdid']){
	          $where[] = ['mdid', '=', $this->user['mdid']];
		}
	  if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
	  if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('lucky_collage_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
			if($v['buytpe']!=1) $datalist[$key]['team'] = Db::name('collage_order_team')->where('id',$v['teamid'])->find();
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['codtxt'] = Db::name('shop_sysset')->where('aid',aid)->value('codtxt');
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//拼团订单详情
	public function luckycollageorderdetail(){
		$detail = Db::name('lucky_collage_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'collage_order');
	
		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];
	
		$storeinfo = [];
		if($detail['freight_type'] == 1){
			$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
		}
		
		if($detail['buytype'] != 1){
			$team = Db::name('lucky_collage_order_team')->where('id',$detail['teamid'])->find();
		}else{
			$team = [];
		}
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
	    if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }

		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['team'] = $team;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));
	
		return $this->json($rdata);
	}
	//砍价订单
	public function kanjiaorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if($this->user['mdid']){
            $where[] = ['mdid', '=', $this->user['mdid']];
		}
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('kanjia_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//砍价订单详情
	public function kanjiaorderdetail(){
		$detail = Db::name('kanjia_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'kanjia_order');

		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];

		$storeinfo = [];
		if($detail['freight_type'] == 1){
			$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
		}
		
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
        if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }

		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));

		return $this->json($rdata);
	}
	//秒杀订单
	public function seckillorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if($this->user['mdid']){
            $where[] = ['mdid', '=', $this->user['mdid']];
		}
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('seckill_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//秒杀订单详情
	public function seckillorderdetail(){
		$detail = Db::name('seckill_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'seckill_order');

		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];

		$storeinfo = [];
		if($detail['freight_type'] == 1){
			$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
		}
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
        if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }

		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));

		return $this->json($rdata);
	}

	//积分商城订单
	public function scoreshoporder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if($this->user['mdid']){
			$where[] = ['mdid', '=', $this->user['mdid']];
		}
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
        if(getcustom('user_auth_province')){
            //管理员省市权限
//            $bids = \app\common\Business::get_auth_bids($this->user);
//            if($bids!='all'){
//                $where[] = ['bid','in',$bids];
//            }
        }
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('scoreshop_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['prolist'] = Db::name('scoreshop_order_goods')->where('orderid',$v['id'])->select()->toArray();
			if(!$datalist[$key]['prolist']) $datalist[$key]['prolist'] = [];
			$datalist[$key]['procount'] = Db::name('scoreshop_order_goods')->where('orderid',$v['id'])->sum('num');
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//积分商城订单详情
	public function scoreshoporderdetail(){
		$detail = Db::name('scoreshop_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'scoreshop_order');

		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];
		
		$detail['hidefahuo'] = false;
		$storeinfo = [];
		if($detail['freight_type'] == 1){
			if($detail['mdid'] == -1){
				$freight = Db::name('freight')->where('id',$detail['freight_id'])->find();
				if($freight && $freight['hxbids']){
					if($detail['longitude'] && $detail['latitude']){
						$orderBy = Db::raw("({$detail['longitude']}-longitude)*({$detail['longitude']}-longitude) + ({$detail['latitude']}-latitude)*({$detail['latitude']}-latitude) ");
					}else{
						$orderBy = 'sort desc,id';
					}
					$storelist = Db::name('business')->where('aid',$freight['aid'])->where('id','in',$freight['hxbids'])->where('status',1)->field('id,name,logo pic,longitude,latitude,address')->order($orderBy)->select()->toArray();
					foreach($storelist as $k2=>$v2){
						if($detail['longitude'] && $detail['latitude'] && $v2['longitude'] && $v2['latitude']){
							$v2['juli'] = '距离'.getdistance($detail['longitude'],$detail['latitude'],$v2['longitude'],$v2['latitude'],2).'千米';
						}else{
							$v2['juli'] = '';
						}
						$storelist[$k2] = $v2;
					}
				}
			}else{
				$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('id,name,address,longitude,latitude')->find();
			}
			if(getcustom('freight_selecthxbids')){
				$detail['hidefahuo'] = true;
			}
		}
		$prolist = Db::name('scoreshop_order_goods')->where('orderid',$detail['id'])->select()->toArray();
		
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
        if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }
        if(getcustom('scoreshop_otheradmin_buy')){
            //查询是否其他账号购买
            $detail['otherinfo'] = '';
            if($detail['othermid']){
                $appname = '';
                $admin_user = Db::name('admin_user')->where('aid',$detail['otheraid'])->where('isadmin','>',0)->where('bid',0)->field('un as name')->find();
                if($admin_user && !empty($admin_user['name'])){
                    $appname = $admin_user['name'];
                }
                $detail['otherinfo'] = '来自平台:'.$detail['otheraid'].' '.$appname." 用户:ID".$detail['othermid'].'兑换';
            }
        }
        if(getcustom('supply_yongsheng')){
            if($detail['source'] == 'supply_yongsheng' && $detail['issource']){
                $detail['express_content'] = $express_content = \app\custom\SupplyYongsheng::dealExpressContent($detail,'scoreshop');
                $detail['express_no'] = '';
                if($express_content){
                    $express_contentArr = json_decode($express_content,true);
                    $express_contentNum = count($express_contentArr);
                    if($express_contentNum == 1){
                        $detail['express_com'] = $express_contentArr[0]['express_com'];
                        $detail['express_com'] = $express_contentArr[0]['express_no'];
                    }else{
                        $detail['express_com'] = '多单发货';
                    }
                }else{
                    $detail['express_com'] = '无';
                }
            }
        }
		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['prolist'] = $prolist;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));

		return $this->json($rdata);
	}

	//团购订单
	public function tuangouorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if($this->user['mdid']){
            $where[] = ['mdid', '=', $this->user['mdid']];
		}
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
		if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('tuangou_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
			$datalist[$key]['real_price'] = round($v['totalprice'] - $v['tuimoney'],2);
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//团购订单详情
	public function tuangouorderdetail(){
		$detail = Db::name('tuangou_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'tuangou_order');

		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];

		$storeinfo = [];
		if($detail['freight_type'] == 1){
			$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
		}
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
		if(getcustom('express_maiyatian')){
            $detail['myt_status']    = $peisong_set['myt_status']==1 ? true : false;
            $detail['myt_set']       = true;
            $detail['myt_shop']      = false;
            $detail['myt_shoplist']  = [];
            if($detail['myt_shop']){
                $detail['myt_shoplist']  = Db::name('peisong_myt_shop')->where('aid',aid)->where('bid',bid)->where('is_del',0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                if(!$detail['myt_shoplist']){
                    $detail['myt_shoplist']  = [['id'=>0,'origin_id'=>0,'name'=>'无门店可选择']];
                }
            }
        }

		$detail['real_price'] = round($detail['totalprice'] - $detail['tuimoney'],2);

		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));
		$rdata['mdid'] = $this->user['mdid'];

		return $this->json($rdata);
	}
	//约课订单
	public function yuekeorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		if($this->user['mdid']){
            $where[] = ['mdid', '=', $this->user['mdid']];
		}
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('yueke_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $key=>$v){
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
			$datalist[$key]['workerinfo'] = Db::name('yueke_worker')->where('aid',aid)->where('id',$v['workerid'])->field('id,realname,tel,headimg,dengji')->find();
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//约课订单详情
	public function yuekeorderdetail(){
		$detail = Db::name('yueke_order')->where('id',input('param.id/d'))->where('aid',aid)->where('bid',bid)->find();
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'yueke_order');

		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];

		$storeinfo = [];
		if($detail['freight_type'] == 1){
			$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
		}
		$peisong_set = Db::name('peisong_set')->where('aid',aid)->find();
		if($peisong_set['status']==1 && bid>0 && $peisong_set['businessst']==0 && $peisong_set['make_status']==0) $peisong_set['status'] = 0;
		$detail['canpeisong'] = ($detail['freight_type']==2 && $peisong_set['status']==1) ? true : false;
		
		$workerinfo = Db::name('yueke_worker')->where('aid',aid)->where('id',$detail['workerid'])->field('id,realname,tel,headimg,dengji')->find();

		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['workerinfo'] = $workerinfo;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['expressdata'] = array_keys(express_data(['aid'=>aid,'bid'=>bid]));

		return $this->json($rdata);
	}

	//备注
	public function setremark(){
		$post = input('post.');
		$type = $post['type'];
		$orderid = $post['orderid'];
		$content = $post['content'];
		Db::name($type.'_order')->where(['aid'=>aid,'bid'=>bid,'id'=>$orderid])->update(['remark'=>$content]);
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'设置备注'.$orderid);
		return $this->json(['status'=>1,'msg'=>'设置完成']);
	}
	//删除订单
	public function delOrder(){
		$post = input('post.');
		$type = $post['type'];
		$orderid = input('post.orderid/d');
		$order = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->find();
		if(!$order || $order['status']!=4){
			return $this->json(['status'=>0,'msg'=>'删除失败,订单状态错误']);
		}else{
            \app\common\Order::order_close_done(aid,$orderid,$type);
			$rs = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->delete();
			if($type=='shop' || $type=='collage'){
				$rs = Db::name($type.'_order_goods')->where(['orderid'=>$orderid,'aid'=>aid])->delete();
			}
            $typeName = \app\common\Order::getOrderTypeName($type);
            \app\common\System::plog('手机端后台'.$typeName.'删除'.$orderid);
			return $this->json(['status'=>1,'msg'=>'删除成功']);
		}
	}
	//改为已支付
    public function ispay(){
		if(bid != 0) return $this->json(['status'=>-4,'msg'=>'无操作权限']);
		$type = input('post.type');
		$orderid = input('post.orderid/d');

        if (getcustom('shoporder_admin_payorder_switch') ){
            $shopset = Db::name('shop_sysset')->where('aid',aid)->field('order_admin_payorder_switch')->find();
            if($shopset['order_admin_payorder_switch']==0){
                return $this->json(['status'=>0,'msg'=>'功能已关闭']);
            }
        }
        
        if(getcustom('douyin_groupbuy')){
            if($type == 'shop'){
                $order = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid,'bid'=>bid])->field('id,status,isdygroupbuy')->find();
                if(!$order || $order['status'] !=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符']);
                }
                if($order['isdygroupbuy'] == 1){
                    return $this->json(['status'=>0,'msg'=>'抖音团购券订单不支持手动改为已支付']);
                }
            }
        }
        if(getcustom('supply_zhenxin')){
            if($order['issource'] == 1 && $order['source'] == 'supply_zhenxin'){
                return $this->json(['status'=>0,'msg'=>'甄新汇选订单不支持手动改为已支付']);
            }
        }
        if(getcustom('supply_yongsheng')){
            if($order['issource'] == 1 && $order['source'] == 'supply_yongsheng'){
                return $this->json(['status'=>0,'msg'=>'改订单不支持手动改为已支付']);
            }
        }

        $updata = [];
        $updata['status']  = 1;
        $updata['paytime'] = time();
        $updata['paytype'] = '后台支付';

        if(getcustom('pay_money_combine')){
            if($type == 'shop'){
                $order = Db::name('shop_order')->where(['id'=>$orderid,'aid'=>aid,'bid'=>bid])->field('totalprice,combine_money')->find();
                //余额组合支付退款，改为都是余额支付
                if($order['combine_money']>0){
                    $updata['combine_money'] = 0;
                    $updata['combine_wxpay'] = 0;
                    $updata['combine_alipay']= 0;
                }
            }
        }
		Db::name($type.'_order')->where(['aid'=>aid,'id'=>$orderid])->update($updata);
		$payfun = $type.'_pay';
		\app\model\Payorder::$payfun($orderid);
		//if(\app\common\Order::hasOrderGoodsTable($type)){
		//	Db::name($type.'_order_goods')->where(['orderid'=>$orderid,'aid'=>aid])->update(['status'=>1]);
		//}
		//奖励积分
		//$order = Db::name($type.'_order')->where(['aid'=>aid,'id'=>$orderid])->find();
		//if($order['givescore'] > 0){
		//	\app\common\Member::addscore(aid,$order['mid'],$order['givescore'],'购买产品奖励'.t('积分'));
		//}
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'改为已支付'.$orderid);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
    //改为已接单
    public function jiedan(){
        $type = input('post.type');
        $orderid = input('post.orderid/d');
        Db::name($type.'_order')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->update(['status'=>12]);
        Db::name($type.'_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>12]);
        //判断是否自动派单
        if(in_array($type,['shop','restaurant_takeaway','collage'])) {
            $order = Db::name($type.'_order')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->find();
            if($order['freight_type'] == 2){
                $peisong_set = \db('peisong_set')->where('aid',aid)->find();
                if($peisong_set['express_wx_status'] == 1 && $peisong_set['express_wx_paidan'] == 1){
                    Db::name($type.'_order')->where('id',$orderid)->update(['express_type'=>'express_wx']);
                    \app\custom\ExpressWx::addOrder($type.'_order',$order);
                    \app\common\System::plog('订单接单，即时配送自动派单:'.$orderid);
                }else{
                    // 自动派单到大厅
                    if(getcustom('express_paidan')){
                        if($peisong_set['paidantype'] == 0){
                            if($peisong_set['express_paidan'] == 1){
                                $rs = \app\model\PeisongOrder::create('restaurant_takeaway_order',$order);
                            }
                        }
                    }
                }
                if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                    \app\common\Order::wxShipping(aid,$order,$type);
                }
            }
        }
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'改为已接单'.$orderid);
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }


    //退款
    public function judan(){
        $type = input('post.type');
        $orderid = input('post.orderid/d');
        $reason = input('post.reason','拒单退款');
        $order = Db::name($type.'_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
        $refund_money = $order['totalprice'];
        if($order['status']!=1 && $order['status']!=2 && $order['status']!=12){
            return $this->json(['status'=>0,'msg'=>'该订单状态不允许退款']);
        }
        if($refund_money > 0) {
            $rs = \app\common\Order::refund($order,$refund_money,$reason);
            if($rs['status']==0){
                return $this->json(['status'=>0,'msg'=>$rs['msg']]);
            }
        }

        Db::name($type.'_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4,'refund_status'=>2,'refund_money'=>$refund_money,'refund_reason'=>$reason]);
        Db::name($type.'_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4]);

        //积分抵扣的返还
        if($order['scoredkscore'] > 0){
            \app\common\Member::addscore(aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
        }
		if($order['givescore2'] > 0){
			\app\common\Member::addscore(aid,$order['mid'],-$order['givescore2'],'订单退款扣除');
		}
        //扣除消费赠送积分
        \app\common\Member::decscorein(aid,$type,$order['id'],$order['ordernum'],'订单退款扣除消费赠送');

        //优惠券抵扣的返还
        if($order['coupon_rid']){
            \app\common\Coupon::refundCoupon2(aid,$order['mid'],$order['coupon_rid'],$order,2);
        }
        if(getcustom('yx_invite_cashback')){
            if($type == 'shop'){
                //取消邀请返现
                \app\custom\OrderCustom::cancel_invitecashbacklog(aid,$order,'订单退款');
            }
        }
        //退款成功通知
        $tmplcontent = [];
        $tmplcontent['first'] = '您的订单已经完成退款，¥'.$refund_money.'已经退回您的付款账户，请留意查收。';
        $tmplcontent['remark'] = $reason.'，请点击查看详情~';
        $tmplcontent['orderProductPrice'] = $refund_money;
        $tmplcontent['orderProductName'] = $order['title'];
        $tmplcontent['orderName'] = $order['ordernum'];
        $tmplcontentNew = [];
        $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
        $tmplcontentNew['thing2'] = $order['title'];//商品名称
        $tmplcontentNew['amount3'] = $refund_money;//退款金额
        \app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
        //订阅消息
        $tmplcontent = [];
        $tmplcontent['amount6'] = $refund_money;
        $tmplcontent['thing3'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
		
		$tmplcontentnew = [];
		$tmplcontentnew['amount3'] = $refund_money;
		$tmplcontentnew['thing6'] = $order['title'];
		$tmplcontentnew['character_string4'] = $order['ordernum'];
        \app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

        //短信通知
        $member = Db::name('member')->where('id',$order['mid'])->find();
        if($member['tel']){
            $tel = $member['tel'];
        }else{
            $tel = $order['tel'];
        }
        $rs = \app\common\Sms::send(aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$refund_money]);

        \app\common\System::plog('餐饮外卖订单退款'.$orderid);
        return $this->json(['status'=>1,'msg'=>'已退款成功']);
    }

	public function print()
    {
        $type = input('post.type');
        $orderid = input('post.orderid/d');

        if(in_array($type,['restaurant_takeaway','restaurant_shop'])) {
            $order = Db::name($type.'_order')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->find();
            $rs = \app\custom\Restaurant::print($type, $order, [], 0);//0普通打印，1一菜一单
        } else {
            $rs = \app\common\Wifiprint::print(aid,$type,$orderid,0);
        }
        return $this->json($rs);
    }
    //改为核销
    function hexiao(){
        $type = input('post.type');
        $orderid = input('post.orderid/d');
        $order = Db::name($type.'_order')->where(['aid'=>aid,'id'=>$orderid])->find();
        $auth_data = json_decode($this->user['hexiao_auth_data'],true);
		
		if($this->user['isadmin']==0){
			if(!in_array($type,$auth_data)){
				return $this->json(['status'=>0,'msg'=>'您没有核销权限']);
			}
			if($type=='shop' || $type=='collage' || $type=='kanjia' || $type=='scoreshop'){
				if($this->user['mdid'] != 0 && $this->user['mdid']!=$order['mdid']){
					return $this->json(['status'=>0,'msg'=>'您没有该门店核销权限']);
				}
			}
		}

        if($type=='shop' || $type=='collage' || $type=='kanjia' || $type=='scoreshop' || $type=='seckill' || $type=='yueke' || $type =='tuangou' || $type =='gold_bean_shop'){
			if($order['status']==3) return $this->json(['status'=>0,'msg'=>'订单已核销']);
            $data = array();
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['uid'] = $this->uid;
            $data['mid'] = $order['mid'];
            $data['orderid'] = $order['id'];
            $data['ordernum'] = $order['ordernum'];
            $data['title'] = $order['title'];
            $data['type'] = $type;
            $data['createtime'] = time();
            $data['remark'] = '核销员['.$this->user['un'].']核销';
            $data['mdid']   = empty($this->user['mdid'])?0:$this->user['mdid'];
			Db::name('hexiao_order')->insert($data);
            $remark = $order['remark'] ? $order['remark'].' '.$data['remark'] : $data['remark'];

            $rs = \app\common\Order::collect($order,$type, $this->user['mid']);
            if($rs['status']==0) return $this->json($rs);

            db($type.'_order')->where(['aid'=>aid,'id'=>$orderid])->update(['status'=>3,'collect_time'=>time(),'remark'=>$remark]);
            if($type == 'scoreshop'){
                Db::name('scoreshop_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->update(['status'=>3,'endtime'=>time()]);
            }
            if(getcustom('gold_bean_shop')){
                if($type == 'gold_bean_shop'){
                    Db::name('gold_bean_shop_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->update(['status'=>3,'endtime'=>time()]);
                }
            }

            if($type == 'shop'){
                Db::name('shop_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->update(['status'=>3,'endtime'=>time()]);
                if(getcustom('ciruikang_fenxiao')){
                    //一次购买升级
                    \app\common\Member::uplv(aid,$order['mid'],'shop',['onebuy'=>1,'onebuy_orderid'=>$order['id']]);
                }else{
                    \app\common\Member::uplv(aid,$order['mid']);
                }
                if(getcustom('member_shougou_parentreward')){
                    //首购解锁
                    Db::name('member_commission_record')->where('orderid',$order['id'])->where('type','shop')->where('status',0)->where('islock',1)->where('aid',$order['aid'])->where('remark','like','%首购奖励')->update(['islock'=>0]);
                }
                if(getcustom('shop_zthx_backmoney')){
                    if($order['freight_type'] == 1){
                        $prolist = Db::name('shop_order_goods')->where(['orderid'=>$order['id']])->field('id,market_price,num')->select()->toArray();
                        $levelid = Db::name('member')->where('id',$order['mid'])->value('levelid');
                        if(!empty($prolist) && !empty($levelid) && $levelid>0){
                            //自提核销返现
                            $zthx_backmoney = Db::name('member_level')->where('id',$levelid)->where('aid',aid)->value('zthx_backmoney');
                            if($zthx_backmoney && $zthx_backmoney>0){
                                $back_money = 0;
                                foreach($prolist as $pv){
                                    $back_money += $pv['market_price']*$pv['num'];
                                }
                                unset($pv);
                                $back_money = $back_money*$zthx_backmoney/100;
                                $back_money = round($back_money,2);
                                if($back_money>0){
                                    \app\common\Member::addmoney(aid,$order['mid'],$back_money,'自提核销返回，订单号: '.$order['ordernum']);
                                }
                            }
                        }
                    }
                }

                // 分销商开启门店核销后，对应核销的商品金额自动换算成对应积分赠送到核销管理员
                if(getcustom('mendian_hexiao_money_to_score')){
                    \app\common\Order::mendian_hexiao_money_to_score(aid,$this->user['mid'],$order['totalprice']);
                }

            }

            // 核销送积分
            if(getcustom('mendian_hexiao_give_score') && $order['mdid']){
                $mendian = Db::name('mendian')->where('aid',aid)->where('id',$order['mdid'])->find();
                if($mendian && $type == 'shop'){
                    $givescore = 0;
                    $oglist = Db::name('shop_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->select()->toArray();
                    if($oglist){
                        foreach ($oglist as $og){
                            $pro = Db::name('shop_product')->where('aid',aid)->where('id',$og['proid'])->find();
                            if(!is_null($pro['hexiao_give_score_bili'])){
                                $givescore += $pro['hexiao_give_score_bili'] * 0.01 * $og['real_totalmoney'];
                            }else{
                                $givescore += $mendian['hexiao_give_score_bili'] * 0.01 * $og['real_totalmoney'];
                            }
                        }
                    }
                    $givescore = floor($givescore);
                    if($givescore > 0){
                        \app\common\Member::addscore(aid,$this->user['mid'],$givescore,'核销订单'.$order['ordernum']);
                    }
                }
            }
                    
            if((getcustom('mendian_hexiao_givemoney') || getcustom('scoreshop_mendian_hexiao_givemoney')) && $order['mdid']){
                $mendian = Db::name('mendian')->where('aid',aid)->where('id',$order['mdid'])->find();
                if($mendian){
                    $givemoney = 0;
                    $commission_to_money = 0;
                    if($type == 'shop'){
                        $oglist = Db::name('shop_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->select()->toArray();
                        if($oglist){
                            if(getcustom('product_mendian_hexiao_givemoney')){
                                foreach ($oglist as $og){
                                    $pro = Db::name('shop_product')->where('aid',aid)->where('id',$og['proid'])->find();
                                    $hexiao_set = Db::name('shop_product_mendian_hexiaoset')->where('aid',aid)->where('mdid',$order['mdid'])->where('proid',$og['proid'])->find();
                                    if($hexiao_set['hexiaogivepercent']>0 || $hexiao_set['hexiaogivemoney']>0){
                                        $givemoney += $hexiao_set['hexiaogivepercent'] * 0.01 * $og['real_totalmoney'] + $hexiao_set['hexiaogivemoney'];
                                    }
                                    elseif(!is_null($pro['hexiaogivepercent']) || !is_null($pro['hexiaogivemoney'])){

                                        $givemoney += $pro['hexiaogivepercent'] * 0.01 * $og['real_totalmoney'] + $pro['hexiaogivemoney'];
                                    }else{
                                        $givemoney += $mendian['hexiaogivepercent'] * 0.01 * $og['real_totalmoney'] + $mendian['hexiaogivemoney'];
                                    }
                                }
                            }else{
                                foreach ($oglist as $og){
                                    $totalprice = $og['real_totalmoney'];
                                    if(getcustom('mendian_hexiao_givemoney_price')){
                                        $hexiao_price = Db::name('admin_set')->where('aid',aid)->value('hexiao_price');
                                        if($hexiao_price){
                                            $totalprice = $og['totalprice'];
                                        }
                                    }
                                    $pro = Db::name('shop_product')->where('aid',aid)->where('id',$og['proid'])->find();
                                    if(!is_null($pro['hexiaogivepercent']) || !is_null($pro['hexiaogivemoney'])){
                                        $givemoney += $pro['hexiaogivepercent'] * 0.01 * $totalprice + $pro['hexiaogivemoney']*$og['num'];
                                        if(getcustom('mendian_hexiao_commission_to_money') && $pro['commission_to_money']){
                                            $commission_to_money += $pro['hexiaogivepercent'] * 0.01 * $totalprice + $pro['hexiaogivemoney']*$og['num'];
                                        }
                                    }else{
                                        $givemoney += $mendian['hexiaogivepercent'] * 0.01 * $totalprice + $mendian['hexiaogivemoney'];
                                        if(getcustom('mendian_hexiao_commission_to_money') && $mendian['commission_to_money']){
                                            $commission_to_money += $mendian['hexiaogivepercent'] * 0.01 * $totalprice + $mendian['hexiaogivemoney'];
                                        }
                                    }
                                }
                            }
                        }
                    }elseif($type == 'scoreshop'){
                        if(getcustom('scoreshop_mendian_hexiao_givemoney')){
                            $oglist = Db::name('scoreshop_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->select()->toArray();
                            if($oglist){
                                foreach ($oglist as $og){
                                    $totalprice = $og['totalmoney'];
                                    if(getcustom('mendian_hexiao_givemoney_price')){
                                        $hexiao_price = Db::name('admin_set')->where('aid',aid)->value('hexiao_price');
                                        if($hexiao_price){
                                            $totalprice = $og['totalmoney'];
                                        }
                                    }
                                    $pro = Db::name('scoreshop_product')->where('aid',aid)->where('id',$og['proid'])->find();
                                    if(!is_null($pro['hexiaogivepercent']) || !is_null($pro['hexiaogivemoney'])){
                                        $givemoney += $pro['hexiaogivepercent'] * 0.01 * $totalprice + $pro['hexiaogivemoney']*$og['num'];
                                        if(getcustom('mendian_hexiao_commission_to_money') && $pro['commission_to_money']){
                                            $commission_to_money += $pro['hexiaogivepercent'] * 0.01 * $og['totalmoney'] + $pro['hexiaogivemoney']*$og['num'];
                                        }
                                    }else{
                                        $givemoney += $mendian['hexiaogivepercent'] * 0.01 * $totalprice + $mendian['hexiaogivemoney'];
                                        if(getcustom('mendian_hexiao_commission_to_money') && $mendian['commission_to_money']){
                                            $commission_to_money += $mendian['hexiaogivepercent'] * 0.01 * $og['totalmoney'] + $mendian['hexiaogivemoney'];
                                        }
                                    }
                                }
                            }
                        }
                    }elseif(($mendian['hexiaogivepercent'] || $mendian['hexiaogivemoney'])){
                        $givemoney = $mendian['hexiaogivepercent'] * 0.01 * $order['totalprice'] + $mendian['hexiaogivemoney'];
                    }
                    if($givemoney > 0){
                        
                        // 分润
                        if(getcustom('commission_mendian_hexiao_coupon') && !empty($mendian['fenrun'])){
                            $fenrun = json_decode($mendian['fenrun'],true);
                            $givemoney_old = $givemoney;
        
                            // {"bili":["10","10","20","20","10"],"mids":["3980,3996,4000","3980,3995","","3993,3995,4001","3992,3999"]}
                            $data_bonus = [];
                            foreach ($fenrun['bili'] as $key => $bili) {
                                if($bili > 0 && !empty($fenrun['mids'][$key])){

                                    $send_commission_total = dd_money_format($bili*$givemoney_old/100,2);

                                    $givemoney -= $send_commission_total;
                                    $mids = $fenrun['mids'][$key];
                                    $mids = explode(',', $mids);
                                    $mnum = count($mids);
                                    $send_commission = dd_money_format($send_commission_total/$mnum,2);
                
                                    if($send_commission > 0){
                                        foreach ($mids as $k => $mid) {
                                            $data_shop_bonus = ['aid'=>aid,'bid'=>bid,'mid'=>$mid,'frommid'=>$order['mid'],'orderid'=>$order['id'],'totalcommission'=>$send_commission_total,'commission'=>$send_commission,'bili'=>$bili,'createtime'=>time()];
                                            $data_bonus[] = $data_shop_bonus;

                                        }
                                    }
                                }
                            }
                            if(!empty($data_bonus)){
                                Db::name('mendian_coupon_commission_log')->insertAll($data_bonus);
                            }
                        }
                        if(getcustom('mendian_hexiao_commission_to_money') && $commission_to_money > 0){
                            \app\common\Member::addmoney(aid,$this->user['mid'],$commission_to_money,'核销订单'.$order['ordernum']);
                            $givemoney -= $commission_to_money;
                        }
                        if($givemoney > 0){
                            \app\common\Mendian::addmoney(aid,$mendian['id'],$givemoney,'核销订单'.$order['ordernum']);
                        }
                    }
                    if(getcustom('business_platform_auth')){
                        if($mendian['bid']>0 && $order['bid']!=$mendian['bid']){
                            $business = Db::name('business')->where('aid',aid)->where('id',$mendian['bid'])->find();
                            if($business['isplatform_auth']==1){
                                \app\common\Business::addmoney(aid,$mendian['bid'],$givemoney,$mendian['name'].'核销平台商品 订单号：'.$order['ordernum']);
                            }
                        }
                    }
                }
            }

            // 门店添加店长核销返佣
            if( $type == 'shop' && getcustom('mendian_dianzhan_commission') && $order['mdid']){
                \app\common\Order::dianzhangCommission(aid,$order);
            }

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping(aid,$order,$type);
            }

            //即拼
            if(getcustom('yx_collage_jipin')) {
                \app\common\Order::collageJipinOrder(aid,$orderid);
            }
            //即拼7人成团
            if(getcustom('yx_collage_jipin2')) {
                \app\common\Order::jipin(aid,$order,3);
            }

            //消费赠送佣金提现额度
            if(getcustom('commission_withdraw_limit') && $this->sysset['commission_withdraw_limit_set'] == 1) {
                $user_info = Db::name('member')->where('aid',aid)->where('id',$order['mid'])->field('shop_commission_withdraw_limit,commission_withdraw_limit_infinite')->find();
                //商城消费赠送佣金提现额度
                $consume_withdraw_limit_arr = json_decode($this->sysset['shop_consume_commission_withdraw_limit'],true);
                if($consume_withdraw_limit_arr){
                    //把数组按照奖励最大的排序
                    usort($consume_withdraw_limit_arr, function($a, $b) {
                        if ($a['money'] == $b['money']) return 0;
                        return ($a['money'] > $b['money']) ? -1 : 1;
                    });
                    foreach ($consume_withdraw_limit_arr as $vg){
                        if($vg['money'] && $vg['give'] && $order['totalprice'] >= $vg['money']){
                            //增加佣金提现额度
                            Db::name('member')->where('aid', aid)->where('id', $order['mid'])->update(['commission_withdraw_limit' => Db::raw("commission_withdraw_limit+" . $vg['give'])]);
                            break;
                        }
                    }
                }

                //商城消费赠送佣金提现额度-无限制
                if($user_info['commission_withdraw_limit_infinite'] == 0 && $this->sysset['shop_consume_money_give_infinite'] > 0){
                    //统计确认收货的订单金额
                    $collectOrdrMoney = Db::name('shop_order')->where('aid',aid)->where('mid',$order['mid'])->where('status',3)->sum('totalprice');
                    if($collectOrdrMoney >= $this->sysset['shop_consume_money_give_infinite']){
                        //增加佣金提现额度-无限制
                        Db::name('member')->where('aid', aid)->where('id', $order['mid'])->update(['commission_withdraw_limit_infinite' =>1]);
                    }
                }
            }
        }
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'改为核销'.$orderid);
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }
	//发货
	public function sendExpress(){
		$post = input('post.');
		$type = $post['type'];
		$orderid = $post['orderid'];
		$order = Db::name($type.'_order')->where(['aid'=>aid,'id'=>$orderid])->find();

		//如果选择了配送时间，未到配送时间内不可以进行配送
		if(getcustom('business_withdraw')){
			if($order['freight_time']){
				if($this->user['auth_type']==0){
					$auth_data = json_decode($this->user['auth_data'],true);
					$auth_path = \app\common\Menu::blacklist();
					foreach($auth_data as $v){
						$auth_path = array_merge($auth_path,explode(',',$v));
					}
					$auth_data = $auth_path;
				}else{
					$auth_data = 'all';
				}
				if($auth_data == 'all' || in_array('OrderSendintime',$auth_data)){
					$freight_time = explode('~',$order['freight_time']);
					$begin_time = strtotime($freight_time[0]);
					$date = explode(' ',$freight_time[0]);
					$end_time =strtotime($date[0].' '.$freight_time[1]);
					if(time()<$begin_time){// || time()>$end_time 超时可配送
						return $this->json(['status'=>0,'msg'=>'未在配送时间范围内']);	
					}
				}
			}
		}

		if($order['freight_type']==10){
			$pic = input('post.pic');
			$fhname = input('post.fhname');
			$fhaddress = input('post.fhaddress');
			$shname = input('post.shname');
			$shaddress = input('post.shaddress');
			$remark = input('post.remark');
			$data = [];
			$data['aid'] = aid;
			$data['pic'] = $pic;
			$data['fhname'] = $fhname;
			$data['fhaddress'] = $fhaddress;
			$data['shname'] = $shname;
			$data['shaddress'] = $shaddress;
			$data['remark'] = $remark;
			$data['createtime'] = time();
			$id = Db::name('freight_type10_record')->insertGetId($data);
			$express_com = '货运托运';
			$express_no = $id;
		}else{
			$express_com = input('post.express_com');
			$express_no = input('post.express_no');
		}

		if($type == 'tuangou'){
			$product = Db::name('tuangou_product')->where('id',$order['proid'])->find();
			if($product['endtime'] > time()){
				return $this->json(['status'=>0,'msg'=>'团购活动未结束 暂不允许发货']);
			}
		}
        if($type == 'shop'){
            if(getcustom('supply_zhenxin')){
                if($order['issource'] == 1 && $order['source'] == 'supply_zhenxin'){
                    return $this->json(['status'=>0,'msg'=>'甄新汇选商品订单不允许发货']);
                }
            }
            if(getcustom('supply_yongsheng')){
                if($order['issource'] == 1 && $order['source'] == 'supply_yongsheng'){
                    return $this->json(['status'=>0,'msg'=>'改订单不允许发货']);
                }
            }
        }

		Db::name($type.'_order')->where(['aid'=>aid,'id'=>$orderid])->update(['express_com'=>$express_com,'express_no'=>$express_no,'send_time'=>time(),'status'=>2]);
		if(\app\common\Order::hasOrderGoodsTable($type)){
			Db::name($type.'_order_goods')->where(['orderid'=>$orderid,'aid'=>aid])->update(['status'=>2]);
		}

		if($type=='shop' && $order['fromwxvideo'] == 1){
			\app\common\Wxvideo::deliverysend($orderid);
		}

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping(aid,$order,$type,['express_com'=>$express_com,'express_no'=>$express_no]);
        }
		
		if(getcustom('cefang') && aid==2){ //定制1 订单对接 同步到策方
		    $order['status'] = 2;
			\app\custom\Cefang::api($order);
		}
		
		//订单发货通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的订单已发货';
		$tmplcontent['remark'] = '请点击查看详情~';
		$tmplcontent['keyword1'] = $order['title'];
		$tmplcontent['keyword2'] = $express_com;
		$tmplcontent['keyword3'] = $express_no;
		$tmplcontent['keyword4'] = $order['linkman'].' '.$order['tel'];
        $tmplcontentNew = [];
        $tmplcontentNew['thing4'] = $order['title'];//商品名称
        $tmplcontentNew['thing13'] = $express_com;//快递公司
        $tmplcontentNew['character_string14'] = $express_no;//快递单号
        $tmplcontentNew['thing16'] = $order['linkman'].' '.$order['tel'];//收货人
		\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_orderfahuo',$tmplcontent,m_url('/pages/my/usercenter'),$tmplcontentNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['thing2'] = $order['title'];
		$tmplcontent['thing7'] = $express_com;
		$tmplcontent['character_string4'] = $express_no;
		$tmplcontent['thing11'] = $order['address'];
		$tmplcontentnew = [];
		$tmplcontentnew['thing29'] = $order['title'];
		$tmplcontentnew['thing1'] = $express_com;
		$tmplcontentnew['character_string2'] = $express_no;
		$tmplcontentnew['thing9'] = $order['address'];
		\app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_orderfahuo',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

		//短信通知
		$member = Db::name('member')->where(['id'=>$order['mid']])->find();
		$rs = \app\common\Sms::send(aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderfahuo',['ordernum'=>$order['ordernum'],'express_com'=>$express_com,'express_no'=>$express_no]);

        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'发货'.$orderid);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//退款驳回
	public function refundnopass(){
		$type = input('post.type');
		$orderid = input('post.orderid/d');
		$remark = input('post.remark');
        $release = input('post.release');

        if($release == '2106') {
            //新版本退款
            $order = Db::name('shop_refund_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
            $orderOrigin = Db::name('shop_order')->where('id',$order['orderid'])->where('aid',aid)->where('bid',bid)->find();
            if(getcustom('supply_zhenxin')){
                if($orderOrigin['issource'] && $orderOrigin['source'] == 'supply_zhenxin'){
                    return $this->json(['status'=>0,'msg'=>'甄新汇选商品，暂不支持此操作']);
                }
            }
            $reog = Db::name('shop_refund_order_goods')->where('refund_orderid',$orderid)->select();
            Db::name('shop_refund_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->update(['refund_status'=>3,'refund_checkremark'=>$remark]);
            foreach ($reog as $item) {
                Db::name('shop_order_goods')->where('id',$item['ogid'])->where('orderid',$orderOrigin['id'])
                    ->dec('refund_num', $item['refund_num'])->update();
            }

			//聚水潭售后订单驳回
            if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
				$type='普通退货'; 
				//创建聚水潭售後订单  关闭退款单
				$rs = \app\custom\Jushuitan::refund($order,'WAIT_SELLER_AGREE','CLOSED',$type);
			}

            if(getcustom('erp_hupun')){
                $wln = new \app\custom\Hupun(aid);
                $wln->orderReturn($orderid);
            }
			if($orderOrigin['fromwxvideo'] == 1){
				\app\common\Wxvideo::aftersaleupdate($order['orderid'],$order['id']);
			}

            //退款申请驳回通知
            $tmplcontent = [];
            $tmplcontent['first'] = '您的退款申请被商家驳回，可与商家协商沟通。';
            $tmplcontent['remark'] = $remark.'，请点击查看详情~';
            $tmplcontent['orderProductPrice'] = $order['refund_money'];
            $tmplcontent['orderProductName'] = $orderOrigin['title'];
            $tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $orderOrigin['title'];//商品名称
            $tmplcontentNew['amount3'] = $order['refund_money'];//退款金额
            \app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuierror',$tmplcontent,m_url('/pages/my/usercenter'),$tmplcontentNew);
            //订阅消息
            $tmplcontent = [];
            $tmplcontent['amount3'] = $order['refund_money'];
            $tmplcontent['thing2'] = $orderOrigin['title'];
            $tmplcontent['character_string1'] = $order['ordernum'];
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $order['refund_money'];
			$tmplcontentnew['thing8'] = $orderOrigin['title'];
			$tmplcontentnew['character_string4'] = $order['ordernum'];
            \app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuierror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
            //短信通知
            $member = Db::name('member')->where(['id'=>$order['mid']])->find();
            $rs = \app\common\Sms::send(aid,$member['tel']?$member['tel']:$orderOrigin['tel'],'tmpl_tuierror',['ordernum'=>$order['ordernum'],'reason'=>$remark]);
        } else {
            if($type == 'shop'){
                if(getcustom('supply_zhenxin')){
                    $order = Db::name('shop_order')->where('id',$orderid)->field('issource,source')->find();
                    if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                        return $this->json(['status'=>0,'msg'=>'甄新汇选商品，暂不支持此操作']);
                    }
                }
            }
            Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->update(['refund_status'=>3,'refund_checkremark'=>$remark]);
            $order = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->find();
            //退款申请驳回通知
            $tmplcontent = [];
            $tmplcontent['first'] = '您的退款申请被商家驳回，可与商家协商沟通。';
            $tmplcontent['remark'] = $remark.'，请点击查看详情~';
            $tmplcontent['orderProductPrice'] = $order['refund_money'];
            $tmplcontent['orderProductName'] = $order['title'];
            $tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $order['title'];//商品名称
            $tmplcontentNew['amount3'] = $order['refund_money'];//退款金额
            \app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuierror',$tmplcontent,m_url('/pages/my/usercenter'),$tmplcontentNew);
            //订阅消息
            $tmplcontent = [];
            $tmplcontent['amount3'] = $order['refund_money'];
            $tmplcontent['thing2'] = $order['title'];
            $tmplcontent['character_string1'] = $order['ordernum'];
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $order['refund_money'];
			$tmplcontentnew['thing8'] = $order['title'];
			$tmplcontentnew['character_string4'] = $order['ordernum'];
            \app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuierror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
            //短信通知
            $member = Db::name('member')->where(['id'=>$order['mid']])->find();
            $rs = \app\common\Sms::send(aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_tuierror',['ordernum'=>$order['ordernum'],'reason'=>$remark]);
        }
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'驳回退款'.$orderid);
		return $this->json(['status'=>1,'msg'=>'退款已驳回']);
	}
	//退款通过
	public function refundpass(){
		$type = input('post.type');
		$orderid = input('post.orderid/d');
		$refund_desc = input('post.reason');
        $release = input('post.release');

        if($release == '2106') {
            //新版本
            $order = Db::name('shop_refund_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
            $orderOrigin = Db::name('shop_order')->where('id',$order['orderid'])->where('aid',aid)->where('bid',bid)->find();
            if(getcustom('supply_zhenxin')){
                if($orderOrigin['issource'] && $orderOrigin['source'] == 'supply_zhenxin'){
                    return $this->json(['status'=>0,'msg'=>'甄新汇选商品，暂不支持此操作']);
                }
            }

            $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
            if(getcustom('shoporder_refund_sendcoupon')){
                //退款是否关联优惠券，关联则购买赠送的优惠券使用了，商品不能退款
                if($shopset['return_sendcoupon']){
                    $coupon = Db::name('coupon_record')->where('mid',$orderOrigin['mid'])->where('source','shop')->where('orderid',$orderOrigin['id'])->order('status desc')->find();
                    if($coupon && $coupon['status'] == 1){
                        return json(['status' => 0, 'msg' => '该订单所赠送的'.t('优惠券').'已使用，不能退款']);
                    }
                }
            }

            $reog = Db::name('shop_refund_order_goods')->where('refund_orderid',$orderid)->select()->toArray();

            if($orderOrigin['status']!=1 && $orderOrigin['status']!=2){
                return $this->json(['status'=>0,'msg'=>'该订单状态不允许退款']);
            }
            $params = [];
            if(getcustom('pay_money_combine')){
                //如果是组合支付，退款需要判断余额、微信、支付宝退款部分
                if($orderOrigin['combine_money']>0 && ($orderOrigin['combine_wxpay']>0 || $orderOrigin['combine_alipay']>0)){
                    //refund_combine 1 走shop_refund_order 退款;2 走shop_order 退款
                    $params = [
                        'refund_combine'=> 1,
                        'refund_order'  => $order
                    ];
                }
            }
            $rs = \app\common\Order::refund($orderOrigin,$order['refund_money'],$order['refund_reason'],$params);
            if($rs['status']==0){
				if($orderOrigin['balance_price'] > 0){
					$orderOrigin2 = $orderOrigin;
					$orderOrigin2['totalprice'] = $orderOrigin2['totalprice'] - $orderOrigin2['balance_price'];
					$orderOrigin2['ordernum'] = $orderOrigin2['ordernum'].'_0';
					$rs = \app\common\Order::refund($orderOrigin2,$order['refund_money'],$order['refund_reason']);
					if($rs['status']==0){
						return $this->json(['status'=>0,'msg'=>$rs['msg']]);
					}
					if($orderOrigin['balance_pay_status'] == 0){
						$orderOrigin['totalprice'] = $orderOrigin['totalprice'] - $orderOrigin['balance_price'];
					}
				}else{
					return $this->json(['status'=>0,'msg'=>$rs['msg']]);
				}
            }

            Db::name('shop_refund_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4,'refund_status'=>2]);
            $reOrder = Db::name('shop_refund_order')->where('orderid',$order['orderid'])->where('refund_status', 'in', [2,4])->where('aid',aid)->select();
            $refundTotal = 0;
            foreach ($reOrder as $item) {
                $refundTotal += $item['refund_money'];
            }
            $refundTotal = round($refundTotal,2);
            $orderOrigin['totalprice'] = round($orderOrigin['totalprice'],2);

            //整单全部退时 返还积分和优惠券
            $canRefundNum = 0;
            $prolist = Db::name('shop_order_goods')->where('orderid',$orderOrigin['id'])->select()->toArray();
            foreach ($prolist as $key => $item) {
                $canRefundNum += $item['num'] - $item['refund_num'];
            }
            if($canRefundNum == 0 && $refundTotal == $orderOrigin['totalprice']) {
                Db::name('shop_order')->where('id',$order['orderid'])->where('aid',aid)->where('bid',bid)->update(['status'=>4,'refund_status'=>2,'refund_money' => $refundTotal]);
				Db::name('shop_order_goods')->where(['orderid'=>$order['orderid'],'aid'=>aid, 'bid' => bid])->update(['status'=>4]);
                //积分抵扣的返还
                if($orderOrigin['scoredkscore'] > 0){
                    \app\common\Member::addscore(aid,$orderOrigin['mid'],$orderOrigin['scoredkscore'],'订单退款返还');
                }
				if($orderOrigin['givescore2'] > 0){
					\app\common\Member::addscore(aid,$orderOrigin['mid'],-$orderOrigin['givescore2'],'订单退款扣除');
				}

                //扣除消费赠送积分
                \app\common\Member::decscorein(aid,'shop',$orderOrigin['id'],$orderOrigin['ordernum'],'订单退款扣除消费赠送');

                //查询后台是否开启退还已使用的优惠券
                $return_coupon = Db::name('shop_sysset')->where('aid',aid)->value('return_coupon');
                //优惠券抵扣的返还
                if($return_coupon && $orderOrigin['coupon_rid']){
                    \app\common\Coupon::refundCoupon2(aid,$orderOrigin['mid'],$orderOrigin['coupon_rid'],$orderOrigin,2);
                }
                if(getcustom('money_dec')){
                    if($orderOrigin['dec_money'] && $orderOrigin['dec_money']>0){
                        \app\common\Member::addmoney(aid,$orderOrigin['mid'],$orderOrigin['dec_money'],t('余额').'抵扣返回，订单号: '.$orderOrigin['ordernum']);
                    }
                }
                if(getcustom('product_givetongzheng')){
                    //返回余额抵扣
                    if($orderOrigin['tongzhengdktongzheng'] && $orderOrigin['tongzhengdktongzheng']>0){
                        \app\common\Member::addtongzheng(aid,$orderOrigin['mid'],$orderOrigin['tongzhengdktongzheng'],t('通证').'抵扣返回，订单号: '.$orderOrigin['ordernum']);
                    }
                }
                if(getcustom('yx_invite_cashback')){
                    //取消邀请返现
                    \app\custom\OrderCustom::cancel_invitecashbacklog(aid,$orderOrigin,'订单退款');
                }
                if(getcustom('member_goldmoney_silvermoney')){
                    //返回银值抵扣
                    if($orderOrigin['silvermoneydec']>0){
                        $res = \app\common\Member::addsilvermoney(aid,$orderOrigin['mid'],$orderOrigin['silvermoneydec'],t('银值').'抵扣返回，订单号: '.$orderOrigin['ordernum'],$orderOrigin['ordernum']);
                    }
                    //返回金值抵扣
                    if($orderOrigin['goldmoneydec']>0){
                        $res = \app\common\Member::addgoldmoney(aid,$orderOrigin['mid'],$orderOrigin['goldmoneydec'],t('金值').'抵扣返回，订单号: '.$orderOrigin['ordernum'],$orderOrigin['ordernum']);
                    }
                }
                if(getcustom('member_dedamount')){
                    //返回抵抗金抵扣
                    if($orderOrigin['dedamount_dkmoney'] && $orderOrigin['dedamount_dkmoney']>0){
                        $params=['orderid'=>$orderOrigin['id'],'ordernum'=>$orderOrigin['ordernum'],'paytype'=>'shop','opttype'=>'return'];
                        \app\common\Member::adddedamount(aid,$orderOrigin['bid'],$orderOrigin['mid'],$orderOrigin['dedamount_dkmoney'],'抵扣金抵扣返回，订单号: '.$orderOrigin['ordernum'],$params);
                    }
                }
                if(getcustom('member_shopscore')){
                    //返回产品积分抵扣
                    if($orderOrigin['shopscore']>0 && $orderOrigin['shopscore_status'] == 1){
                        $params=['orderid'=>$orderOrigin['id'],'ordernum'=>$orderOrigin['ordernum'],'paytype'=>'shop'];
                        \app\common\Member::addshopscore(aid,$orderOrigin['mid'],$orderOrigin['shopscore'],t('产品积分').'抵扣返回，订单号: '.$orderOrigin['ordernum'],$params);
                    }
                }
            } else {
                //部分退款
                Db::name('shop_order')->where('id',$order['orderid'])->where('aid',aid)->where('bid',bid)->inc('refund_money',$order['refund_money'])->update(['refund_status'=>2]);
                //重新计算佣金
                \app\common\Order::updateCommission($prolist,$reog);
            }
            \app\common\System::plog('商城订单退款审核通过并退款'.$orderid);

            //退货退款 增加库存 减销量
            if($order['refund_type'] == 'return') {
                $is_mendian_usercenter = 0;
                if(getcustom('mendian_usercenter') && $orderOrigin['mdid']){
                    $is_mendian_usercenter = 1;
                }
                foreach($reog as $item) {
                    if($is_mendian_usercenter==1){
                        //门店中心增加门店库存
                        \app\custom\MendianUsercenter::addMendianStock(aid,$orderOrigin['lock_mdid'],$item['proid'],$item['ggid'],$item['refund_num']);
                    }
                    if($is_mendian_usercenter==0){
                        Db::name('shop_guige')->where('aid',aid)->where('id',$item['ggid'])->update(['stock'=>Db::raw("stock+".$item['refund_num']),'sales'=>Db::raw("sales-".$item['refund_num'])]);
                        Db::name('shop_product')->where('aid',aid)->where('id',$item['proid'])->update(['stock'=>Db::raw("stock+".$item['refund_num']),'sales'=>Db::raw("sales-".$item['refund_num'])]);
                    }
					if(getcustom('guige_split')){
						\app\model\ShopProduct::addlinkstock($item['proid'],$item['ggid'],$item['refund_num']);
					}
                    if(getcustom('ciruikang_fenxiao')){
                        //是否开启了商城商品需上级购买足量
                        if($item['ogid']){
                            $og = Db::name('shop_order_goods')->where('id',$item['ogid'])->find();
                            if($og){
                                $deal_ogstock2 = \app\custom\CiruikangCustom::deal_ogstock2($orderOrigin,$og,$item['refund_num'],'下级订单退款');
                            }
                        }
                    }
                    if(getcustom('shoporder_refund_sendcoupon')){
                        //退款是否关联优惠券，关联则退款时赠送的优惠券也同时失效
                        if($shopset['return_sendcoupon']){
                            Db::name('coupon_record')->where('mid',$orderOrigin['mid'])->where('source','shop')->where('orderid',$orderOrigin['id'])->where('proid',$item['proid'])->where('status',0)->where('endtime','>',time())->update(['endtime'=>time()]);
                        }
                    }
                }
            }else{
                foreach($reog as $item) {
                    if(getcustom('shoporder_refund_sendcoupon')){
                        //退款是否关联优惠券，关联则退款时赠送的优惠券也同时失效
                        if($shopset['return_sendcoupon']){
                            Db::name('coupon_record')->where('mid',$orderOrigin['mid'])->where('source','shop')->where('orderid',$orderOrigin['id'])->where('proid',$item['proid'])->where('status',0)->where('endtime','>',time())->update(['endtime'=>time()]);
                        }
                    }
                }
            }

			if($orderOrigin['fromwxvideo'] == 1){
				\app\common\Wxvideo::aftersaleupdate($order['orderid'],$order['id']);
			}

            if(getcustom('member_gongxian')){
                //扣除贡献值
                $admin = Db::name('admin')->where('id',aid)->find();
                $set = Db::name('admin_set')->where('aid',aid)->find();
                $gongxian_days = $set['gongxian_days'];
                //等级设置优先
                $level_gongxian_days = Db::name('member_level')->where('aid',aid)->where('id',$orderOrigin['mid'])->value('gongxian_days');
                if($level_gongxian_days > 0){
                    $gongxian_days = $level_gongxian_days;
                }
                if($admin['member_gongxian_status'] == 1 && $set['gongxianin_money'] > 0 && $set['gognxianin_value'] > 0 && ((time()-$orderOrigin['paytime'])/86400 <= $gongxian_days)){
                    $log = Db::name('member_gongxianlog')->where('aid',aid)->where('mid',$orderOrigin['mid'])->where('channel','shop')->where('orderid',$orderOrigin['id'])->find();
                    if($log){
                        $givevalue = $log['value']*-1;
                        Db::name('member_gongxianlog')->where('aid',aid)->where('id',$log['id'])->update(['is_expire'=>2]);
                        \app\common\Member::addgongxian(aid,$orderOrigin['mid'],$givevalue,'退款扣除'.t('贡献'),'shop_refund',$orderOrigin['id']);
                    }
                }
            }

			//聚水潭售后订单确认收货
            if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
				$type='普通退货';
				//创建聚水潭售後订单
				$rs = \app\custom\Jushuitan::refund($order,'SUCCESS','SELLER_RECEIVED',$type);
			}
            if(getcustom('erp_hupun')){
                $wln = new \app\custom\Hupun(aid);
                $wln->orderReturn($orderid);
            }
            if(getcustom('consumer_value_add')){
                foreach($reog as $reog_v){
                    $goods_info =  Db::name('shop_order_goods')->where('aid', aid)->where('id', $reog_v['ogid'])->find();
                    //扣除绿色积分
                    if($goods_info['give_green_score2'] > 0){
                        \app\common\Member::addgreenscore(aid,$goods_info['mid'],-bcmul($goods_info['give_green_score2'],$reog_v['refund_num'],2),'订单退款扣除'.t('绿色积分'),'shop_order',$orderid);
                        \app\common\Member::addmaximum(aid,$goods_info['mid'],-$goods_info['give_maximum'],'订单退款扣除','shop_order',$orderid);
                    }
                    //扣除奖金池
                    if($goods_info['give_bonus_pool2'] > 0){
                        \app\common\Member::addbonuspool(aid,$goods_info['mid'],-bcmul($goods_info['give_bonus_pool2'],$reog_v['refund_num'],2),'订单退款扣除'.t('奖金池'),'shop_order',$orderid,0,-bcmul($goods_info['give_green_score2'],$reog_v['refund_num'],2));
                    }
                }
            }
            \app\common\Order::order_close_done(aid,$order['id'],'shop');

            //退款完成操作
            \app\model\ShopOrder::after_refund(aid,$order['orderid'],$prolist,'shop');
            //退款成功通知
            $tmplcontent = [];
            $tmplcontent['first'] = '您的订单已经完成退款，¥'.$order['refund_money'].'已经退回您的付款账户，请留意查收。';
            $tmplcontent['remark'] = '请点击查看详情~';
            $tmplcontent['orderProductPrice'] = $order['refund_money'];
            $tmplcontent['orderProductName'] = $orderOrigin['title'];
            $tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $orderOrigin['title'];//商品名称
            $tmplcontentNew['amount3'] = $order['refund_money'];//退款金额
            \app\common\Wechat::sendtmpl(aid,$orderOrigin['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
            //订阅消息
            $tmplcontent = [];
            $tmplcontent['amount6'] = $order['refund_money'];
            $tmplcontent['thing3'] = $orderOrigin['title'];
            $tmplcontent['character_string2'] = $order['ordernum'];
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $order['refund_money'];
			$tmplcontentnew['thing6'] = $orderOrigin['title'];
			$tmplcontentnew['character_string4'] = $order['ordernum'];
            \app\common\Wechat::sendwxtmpl(aid,$orderOrigin['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

            //短信通知
            $member = Db::name('member')->where('id',$order['mid'])->find();
            if($member['tel']){
                $tel = $member['tel'];
            }else{
                $tel = $orderOrigin['tel'];
            }
            $rs = \app\common\Sms::send(aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$order['refund_money']]);

        } else {
            $order = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid, 'bid' => bid])->find();
            if($type == 'shop'){
                if(getcustom('supply_zhenxin')){
                    if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                        return $this->json(['status'=>0,'msg'=>'甄新汇选商品，暂不支持此操作']);
                    }
                }
            }
            if($order['status']!=1 && $order['status']!=2){
                return $this->json(['status'=>0,'msg'=>'该订单状态不允许退款']);
            }

            if($type=='shop'){
                $refundingMoney = Db::name('shop_refund_order')->where('orderid',$order['id'])->where('aid',aid)->where('bid',bid)->whereIn('refund_status',[1,4])->sum('refund_money');
                if($refundingMoney) {
                    return $this->json(['status'=>0,'msg'=>'请先处理完进行中的退款单']);
                }
                $refundedMoney = Db::name('shop_refund_order')->where('orderid',$order['id'])->where('aid',aid)->where('bid',bid)->where('refund_status',2)->sum('refund_money');
                $order['refund_money'] -= $refundedMoney;
            }

            $params = [];
            if(getcustom('pay_money_combine')){
                if($type=='shop'){
                    //如果是组合支付，退款需要判断余额、微信、支付宝退款部分
                    if($order['combine_money']>0 && ($order['combine_wxpay']>0 || $order['combine_alipay']>0)){
                        $refund_order = Db::name('shop_refund_order')->where('orderid',$order['id'])->where('aid',aid)->where('bid',bid)->field('id,aid,bid,mid,orderid,ordernum,refund_combine_money,refund_combine_money,refund_combine_wxpay,refund_combine_alipay,refundcombine')->find();
                        $params = [
                            'refund_combine'=> 1,
                            'refund_order'  => $refund_order
                        ];
                    }
                }
            }
            //查询配送订单 
            $have_peisong_order = Db::name('peisong_order')->where('aid',aid)->where('orderid',$orderid)->where('ordernum',$order['ordernum'])->where('type','restaurant_takeaway_order')->where('status','in',[0,1,2,3])->find();
            if($have_peisong_order){
                return json(['status'=>0,'msg'=>'请到“PC后台-扩展-同城配送-配送单列表”中取消对应配送订单后再进行退款']);
            }
            $rs = \app\common\Order::refund($order,$order['refund_money'],$refund_desc,$params);
            if($rs['status']==0){
                return $this->json(['status'=>0,'msg'=>$rs['msg']]);
            }
            $orderupdate =['status'=>4,'refund_status'=>2];
            if($refund_desc){
                $orderupdate['refund_reason'] = $refund_desc;
            }

            Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid, 'bid' => bid])->update($orderupdate);
            if(\app\common\Order::hasOrderGoodsTable($type)){
                Db::name($type.'_order_goods')->where(['orderid'=>$orderid,'aid'=>aid, 'bid' => bid])->update(['status'=>4]);
            }
            //积分抵扣的返还
            if($order['scoredkscore'] > 0){
                \app\common\Member::addscore(aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
            }
			if($order['givescore2'] > 0){
                \app\common\Member::addscore(aid,$order['mid'],-$order['givescore2'],'订单退款扣除');
            }

            if($type=='scoreshop'){
                //积分返还
                if($order['totalscore'] > 0){
                    $aid2 = aid;$mid2 = $order['mid'];
                    $remark = '订单退款返还';
                    $addscore_params = [];//其他参数
                    if(getcustom('scoreshop_otheradmin_buy')){
                        //如果扣除的是其他平台用积分
                        if($order['othermid']){
                            $aid2 = $order['otheraid'];$mid2 = $order['othermid'];
                            $appinfo = Db::name('admin_setapp_wx')->where('aid',aid)->field('id,nickname')->find();
                            if($appinfo && !empty($appinfo['nickname'])){
                                $remark = $appinfo['nickname'].'订单'.$order['ordernum'].'退款返还';
                            }else{
                                $set = Db::name('admin_set')->where('aid',aid)->field('name')->find();
                                if($set && !empty($set['name'])){
                                    $remark = $set['name'].'订单'.$order['ordernum'].'退款返还';
                                }
                            }
                            $addscore_params['optaid'] = aid;
                        }
                    }
                    \app\common\Member::addscore($aid2,$mid2,$order['totalscore'],$remark,'',0,0,1,$addscore_params);
                }
                //加库存减销量
                $order_goods = Db::name('scoreshop_order_goods')->where('orderid',$orderid)->where('aid',aid)->select()->toArray();
                foreach ($order_goods as $item) {
                    Db::name('scoreshop_product')->where('aid',aid)->where('id',$item['proid'])->update(['stock'=>Db::raw("stock+".$item['num'])]);
                }
            }
            if(getcustom('gold_bean_shop')){
                if($type=='gold_bean_shop'){
                    //积分返还
                    if($order['totalscore'] > 0){
                        $aid2 = aid;$mid2 = $order['mid'];
                        $remark = '订单退款返还,订单号：'.$order['ordernum'];
                        $addscore_params = [];//其他参数
                        \app\common\Member::addgoldbean($aid2,$mid2,$order['totalscore'],$remark,'',0,$addscore_params);
                    }
                    //加库存减销量
                    $order_goods = Db::name('gold_bean_shop_order_goods')->where('orderid',$orderid)->where('aid',aid)->select()->toArray();
                    foreach ($order_goods as $item) {
                        Db::name('gold_bean_shop_product')->where('aid',aid)->where('id',$item['proid'])->update(['stock'=>Db::raw("stock+".$item['num'])]);
                    }
                }
            }
           
            //扣除消费赠送积分
            \app\common\Member::decscorein(aid,$type,$order['id'],$order['ordernum'],'订单退款扣除消费赠送');
            if(getcustom('money_dec')){
                if($order['dec_money'] && $order['dec_money']>0){
                    \app\common\Member::addmoney(aid,$order['mid'],$order['dec_money'],t('余额').'抵扣返回，订单号: '.$order['ordernum']);
                }
            }
            if(getcustom('member_goldmoney_silvermoney')){
                //返回银值抵扣
                if($order['silvermoneydec']>0){
                    $res = \app\common\Member::addsilvermoney(aid,$order['mid'],$order['silvermoneydec'],t('银值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
                }
                //返回金值抵扣
                if($order['goldmoneydec']>0){
                    $res = \app\common\Member::addgoldmoney(aid,$order['mid'],$order['goldmoneydec'],t('金值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
                }
            }
            if(getcustom('member_dedamount')){
                //返回抵抗金抵扣
                if($order['dedamount_dkmoney'] && $order['dedamount_dkmoney']>0){
                    $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop','opttype'=>'return'];
                    \app\common\Member::adddedamount(aid,$order['bid'],$order['mid'],$order['dedamount_dkmoney'],'抵扣金抵扣返回，订单号: '.$order['ordernum'],$params);
                }
            }
            if(getcustom('member_shopscore')){
                //返回产品积分抵扣
                if($order['shopscore']>0 && $order['shopscore_status'] == 1){
                    $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop'];
                    \app\common\Member::addshopscore(aid,$order['mid'],$order['shopscore'],t('产品积分').'抵扣返回，订单号: '.$order['ordernum'],$params);
                }
            }
            //查询后台是否开启退还已使用的优惠券
            $return_coupon = Db::name('shop_sysset')->where('aid',aid)->value('return_coupon');
            //优惠券抵扣的返还
            if($return_coupon && $order['coupon_rid']){
                \app\common\Coupon::refundCoupon2(aid,$order['mid'],$order['coupon_rid'],$order,2);
            }
			
			if(getcustom('cefang') && aid==2){ //定制1 订单对接 同步到策方
				$order['status'] = 4;
				\app\custom\Cefang::api($order);
			}
            if(getcustom('yx_invite_cashback')){
                if($type == 'shop'){
                    //取消邀请返现
                    \app\custom\OrderCustom::cancel_invitecashbacklog(aid,$order,'订单退款');
                }
            }

            if($type == 'collage'){
                if(getcustom('yx_collage_team_in_team')){
                    //扣除团中团赠送佣金
                    \app\custom\CollageTeamInTeamCustom::deccommission(aid,$order['id'],$order['ordernum'],'订单退款扣除');
                }

                if(getcustom('yx_mangfan_collage')){
                    \app\custom\Mangfan::delAndCreate(aid, $order['mid'], $order['id'], $order['ordernum'],'collage');
                }

                if(getcustom('yx_queue_free_collage')){
                    \app\custom\QueueFree::orderRefundQuit($order,'collage');
                }
            }
            //\app\common\System::plog('商城订单退款审核通过并退款'.$orderid);
            \app\common\Order::order_close_done(aid,$order['id'],$type);
            //退款成功通知
            $tmplcontent = [];
            if($type=='scoreshop'){
                $tmplcontent['first'] = '您的订单已经完成退款，'.$order['totalscore'].t('积分').' + ¥'.$order['refund_money'].'已经退回您的付款账户，请留意查收。';
                $tmplcontent['orderProductPrice'] = $order['totalscore'].t('积分').' + ¥'.$order['refund_money'];
            }else{
                $tmplcontent['first'] = '您的订单已经完成退款，¥'.$order['refund_money'].'已经退回您的付款账户，请留意查收。';
                $tmplcontent['orderProductPrice'] = $order['refund_money'];
            }
            $tmplcontent['remark'] = '请点击查看详情~';
            $tmplcontent['orderProductName'] = $order['title'];
            $tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $order['title'];//商品名称
            $tmplcontentNew['amount3'] = $order['refund_money'];//退款金额
            \app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('/pages/my/usercenter'),$tmplcontentNew);
            //订阅消息
            $tmplcontent = [];
            $tmplcontent['amount6'] = $order['refund_money'];
            $tmplcontent['thing3'] = $order['title'];
            $tmplcontent['character_string2'] = $order['ordernum'];
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $order['refund_money'];
			$tmplcontentnew['thing6'] = $order['title'];
			$tmplcontentnew['character_string4'] = $order['ordernum'];
            \app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

            //短信通知
            $member = Db::name('member')->where(['id'=>$order['mid']])->find();
            $rs = \app\common\Sms::send(aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$order['refund_money']]);
        }
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'通过退款'.$orderid);
		return $this->json(['status'=>1,'msg'=>'已退款成功']);
	}

	public function returnpass()
    {
        $orderid = input('post.orderid/d');
        $remark = input('post.remark');
        $order = Db::name('shop_refund_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
        $orderOrigin = Db::name('shop_order')->where('id',$order['orderid'])->where('aid',aid)->where('bid',bid)->find();
        if(getcustom('supply_zhenxin')){
            if($orderOrigin['issource'] && $orderOrigin['source'] == 'supply_zhenxin'){
                return $this->json(['status'=>0,'msg'=>'甄新汇选商品，暂不支持此操作']);
            }
        }
        if($orderOrigin['status']!=1 && $orderOrigin['status']!=2){
            return $this->json(['status'=>0,'msg'=>'该订单状态不允许退款']);
        }

        Db::name('shop_refund_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->update(['refund_status'=>4,'refund_checkremark'=>$remark]);
		
		if($orderOrigin['fromwxvideo'] == 1){
			\app\common\Wxvideo::aftersaleupdate($order['orderid'],$order['id']);
		}
        //退款同意通知
        $tmplcontent = [];
        $tmplcontent['first'] = '商家已同意您的退货退款申请，请及时联系商家退货，商家收到退货后将进行退款';
        $tmplcontent['remark'] = $remark.'，请点击查看详情~';
        $tmplcontent['orderProductPrice'] = $order['refund_money'];
        $tmplcontent['orderProductName'] = $orderOrigin['title'];
        $tmplcontent['orderName'] = $order['ordernum'];
        $tmplcontentNew = [];
        $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
        $tmplcontentNew['thing2'] = $orderOrigin['title'];//商品名称
        $tmplcontentNew['amount3'] = $order['refund_money'];//退款金额
        \app\common\Wechat::sendtmpl(aid,$orderOrigin['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
        //订阅消息
        $tmplcontent = [];
        $tmplcontent['amount6'] = $order['refund_money'];
        $tmplcontent['thing3'] = $orderOrigin['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
		
		$tmplcontentnew = [];
		$tmplcontentnew['amount3'] = $order['refund_money'];
		$tmplcontentnew['thing6'] = $orderOrigin['title'];
		$tmplcontentnew['character_string4'] = $order['ordernum'];
        \app\common\Wechat::sendwxtmpl(aid,$orderOrigin['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

        //短信通知
//        $member = Db::name('member')->where('id',$order['mid'])->find();
//        if($member['tel']){
//            $tel = $member['tel'];
//        }else{
//            $tel = $orderOrigin['tel'];
//        }
//        $rs = \app\common\Sms::send(aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$order['refund_money']]);



		//聚水潭售后订单审核通过等待退货
        if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
			$type='普通退货';
			//创建聚水潭售後订单
			$rs = \app\custom\Jushuitan::refund($order,'WAIT_BUYER_RETURN_GOODS','BUYER_RECEIVED',$type);
		}
        if(getcustom('erp_hupun')){
            $wln = new \app\custom\Hupun(aid);
            $wln->orderReturn($orderid);
        }
        \app\common\System::plog('商城订单退款审核通过等待退货'.$orderid);
        return $this->json(['status'=>1,'msg'=>'审核通过，等待买家退货']);
    }
	//关闭订单
	public function closeOrder(){
		$type = input('post.type');
		$orderid = input('post.orderid/d');
		$order = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->find();
		if(!$order || $order['status']!=0){
			return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
		}

        $rs = Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->where('status',0)->update(['status'=>4]);
        if(!$rs) $this->json(['status'=>0,'msg'=>'操作失败']);
        if(\app\common\Order::hasOrderGoodsTable($type)){
            Db::name($type.'_order_goods')->where(['orderid'=>$orderid,'aid'=>aid])->update(['status'=>4]);
        }

		if($type=='shop'){
			//加库存
			$oglist = Db::name($type.'_order_goods')->where(['aid'=>aid,'orderid'=>$orderid])->select();
            $is_mendian_usercenter = 0;
            if(getcustom('mendian_usercenter') && $order['mdid']){
                $is_mendian_usercenter = 1;
            }
			foreach($oglist as $og){
                if($is_mendian_usercenter==1){
                    //门店中心增加门店库存
                    \app\custom\MendianUsercenter::addMendianStock(aid,$order['lock_mdid'],$og['proid'],$og['ggid'],$og['num']);
                }
                if($is_mendian_usercenter==0){
                    Db::name($type.'_guige')->where(['aid'=>aid,'id'=>$og['ggid']])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
                    Db::name($type.'_product')->where(['aid'=>aid,'id'=>$og['proid']])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
                }

				if(getcustom('guige_split')){
					\app\model\ShopProduct::addlinkstock($og['proid'],$og['ggid'],$og['num']);
				}
                if(getcustom('ciruikang_fenxiao')){
                    //是否开启了商城商品需上级购买足量
                    $deal_ogstock2 = \app\custom\CiruikangCustom::deal_ogstock2($order,$og,$og['num'],'下级订单关闭');
                }
			}
		}elseif($type=='scoreshop'){
			//加库存
			$oglist = Db::name($type.'_order_goods')->where(['aid'=>aid,'orderid'=>$orderid])->select();
			foreach($oglist as $og){
				Db::name($type.'_product')->where(['aid'=>aid,'id'=>$og['proid']])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
                if($og['ggid']) Db::name($type.'_guige')->where(['aid'=>aid,'id'=>$og['ggid']])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
			}
		}elseif($type=='restaurant_shop'){
            $oglist = Db::name($type.'_order_goods')->where(['aid'=>aid,'orderid'=>$orderid])->select();
            foreach($oglist as $og){
                Db::name('restaurant_product')->where(['aid'=>aid,'id'=>$og['proid']])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
                if($og['ggid']) Db::name('restaurant_product_guige')->where(['aid'=>aid,'id'=>$og['ggid']])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
            }
        }else{
			Db::name($type.'_product')->where(['aid'=>aid,'id'=>$order['proid']])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("sales-".$order['num'])]);
			if($type=='collage' || $type=='seckill'){
				Db::name($type.'_guige')->where(['aid'=>aid,'id'=>$order['ggid']])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("sales-".$order['num'])]);
			}
		}
		//优惠券抵扣的返还
		if($order['coupon_rid']){
            \app\common\Coupon::refundCoupon2(aid,$order['mid'],$order['coupon_rid'],$order,2);
		}
        if(getcustom('money_dec')){
            //返回余额抵扣
            if($order['dec_money']>0){
                \app\common\Member::addmoney(aid,$order['mid'],$order['dec_money'],t('余额').'抵扣返回，订单号: '.$order['ordernum']);
            }
        }
        if(getcustom('product_givetongzheng')){
            //返回余额抵扣
            if($order['tongzhengdktongzheng']>0){
                \app\common\Member::addtongzheng(aid,$order['mid'],$order['tongzhengdktongzheng'],t('通证').'抵扣返回，订单号: '.$order['ordernum']);
            }
        }
        if(getcustom('pay_money_combine')){
            //返回余额组合支付
            if($type == 'shop' && $order['combine_money']>0){
                $res = \app\common\Member::addmoney(aid,$order['mid'],$order['combine_money'],t('余额').'组合支付返回，订单号: '.$order['ordernum']);
                if($res['status'] ==1){
                    Db::name($type.'_order')->where(['id'=>$orderid,'aid'=>aid])->update(['combine_money'=>0]);
                }
            }
        }
        if(getcustom('member_goldmoney_silvermoney')){
            //返回银值抵扣
            if($order['silvermoneydec']>0){
                $res = \app\common\Member::addsilvermoney(aid,$order['mid'],$order['silvermoneydec'],t('银值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
            }
            //返回金值抵扣
            if($order['goldmoneydec']>0){
                $res = \app\common\Member::addgoldmoney(aid,$order['mid'],$order['goldmoneydec'],t('金值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
            }
        }
        if(getcustom('member_dedamount')){
            //返回抵抗金抵扣
            if($order['dedamount_dkmoney'] && $order['dedamount_dkmoney']>0){
                $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop','opttype'=>'return'];
                \app\common\Member::adddedamount(aid,$order['bid'],$order['mid'],$order['dedamount_dkmoney'],'抵扣金抵扣返回，订单号: '.$order['ordernum'],$params);
            }
        }
        if(getcustom('member_shopscore')){
            //返回产品积分抵扣
            if($order['shopscore']>0 && $order['shopscore_status'] == 1){
                $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop'];
                \app\common\Member::addshopscore(aid,$order['mid'],$order['shopscore'],t('产品积分').'抵扣返回，订单号: '.$order['ordernum'],$params);
            }
        }

        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\Order::order_close_done(aid,$orderid,$type);
        \app\common\System::plog('手机端后台'.$typeName.'关闭订单'.$orderid);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	public function maidanlog(){
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['maidan_order.aid','=',aid];
		$where[] = ['maidan_order.bid','=',bid];
		$where[] = ['maidan_order.status','=',1];
		if($this->user['mdid']){
			$where[] = ['maidan_order.mdid','=',$this->user['mdid']];
		}
		if(input('param.keyword')){
			$where[] = ['member.nickname|maidan_order.ordernum','like','%'.input('param.keyword').'%'];
		}
		if($pagenum == 1){
			$count = 0 + Db::name('maidan_order')->alias('maidan_order')->field('member.nickname,member.headimg,maidan_order.*')->join('member member','member.id=maidan_order.mid','left')->where($where)->count();
		}else{
			$count = 0;
		}
		$datalist = Db::name('maidan_order')->alias('maidan_order')->field('member.nickname,member.headimg,maidan_order.*')->join('member member','member.id=maidan_order.mid','left')->where($where)->page($pagenum,$pernum)->order('maidan_order.id desc')->select()->toArray();
		if($datalist){
            foreach($datalist as &$dv){
                if(getcustom('pay_money_combine_maidan')){
                    if($dv['combine_money'] && $dv['combine_money'] > 0){
                        if(!empty($dv['paytype'])){
                            $dv['paytype'] .= ' + '.t('余额').'支付';
                        }else{
                            $dv['paytype'] .= t('余额').'支付';
                        }
                    }
                }
            }
            unset($dv);
        }else{
            $datalist = [];
        }
		$rdata = [];
		$rdata['count'] = $count;
		$rdata['data'] = $datalist;
		return $this->json($rdata);
	}
	public function maidandetail(){
		$id = input('param.id/d');
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		$where[] = ['id','=',$id];
		$detail = Db::name('maidan_order')->where($where)->find();
		$detail['paytime'] = date('Y-m-d H:i:s',$detail['paytime']);
		if($detail['couponrid']){
			$couponrecord = Db::name('coupon_record')->where(['aid'=>aid,'id'=>$detail['couponrid']])->find();
		}else{
			$couponrecord = false;
		}
		if($detail['mdid']){
			$mendian = Db::name('mendian')->field('id,name')->where(['aid'=>aid,'id'=>$detail['mdid']])->find();
		}else{
			$mendian = false;
		}
        if(getcustom('pay_money_combine_maidan')){
            if($detail['combine_money'] && $detail['combine_money'] > 0){
                if(!empty($detail['paytype'])){
                    $detail['paytype'] .= ' + '.t('余额').'支付';
                }else{
                    $detail['paytype'] .= t('余额').'支付';
                }
            }
        }
		$member = Db::name('member')->where(['id'=>$detail['mid']])->find();
		if(!$member) $member = [];
		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['couponrecord'] = $couponrecord;
		$rdata['mendian'] = $mendian;
		$rdata['member'] = $member;
		return $this->json($rdata);
	}
	
	//获取配送员
	public function getpeisonguser(){
		$set = Db::name('peisong_set')->where('aid',aid)->find();
		
		$order = Db::name(input('param.type'))->where('id',input('param.orderid'))->find();
		if($order['bid']>0){
			$business = Db::name('business')->field('name,address,tel,logo,longitude,latitude')->where('id',$order['bid'])->find();
		}else{
			$business = Db::name('admin_set')->field('name,address,tel,logo,longitude,latitude')->where('aid',aid)->find();
		}
        //查询骑行距离
        $mapqq = new \app\common\MapQQ();
        $bicycl = $mapqq->getDirectionDistance($order['longitude'],$order['latitude'],$business['longitude'],$business['latitude'],1);
        if($bicycl && $bicycl['status']==1){
            $juli = $bicycl['distance'];
        }else{
            $juli = getdistance($order['longitude'],$order['latitude'],$business['longitude'],$business['latitude'],1);
        }
		$ticheng = \app\model\PeisongOrder::ticheng($set,$order,$juli/1000);
		if($set['make_status']==1){ //码科配送
			$rs = \app\common\Make::getprice(aid,$order['bid'],$business['latitude'],$business['longitude'],$order['latitude'],$order['longitude']);
			if($rs['status']==0) return $this->json($rs);
			$ticheng = $rs['price'];
			$selectArr = [];
			$set['paidantype'] = 2;
		}else{
			$selectArr = [];
			if($set['paidantype'] == 0){ //抢单模式
				$selectArr[] = ['id'=>0,'title'=>'--配送员抢单--'];
			}else{
				$peisonguser = Db::name('peisong_user')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
				foreach($peisonguser as $k=>$v){
					$dan = Db::name('peisong_order')->where('psid',$v['id'])->where('status','in','0,1')->count();
					$title = $v['realname'].'-'.$v['tel'].'(配送中'.$dan.'单)';
					$selectArr[] = ['id'=>$v['id'],'title'=>$title];
				}
			}
		}
		$psfee = $ticheng * (1 + $set['businessfee']*0.01);
		return $this->json(['status'=>1,'peisonguser'=>$selectArr,'paidantype'=>$set['paidantype'],'psfee'=>$psfee,'ticheng'=>$ticheng]);
	}
	//下配送单
	public function peisong(){
		$orderid = input('post.orderid/d');
		$type = input('post.type');
		$psid = input('post.psid/d');

        $set = Db::name('peisong_set')->where('aid',aid)->find();
        if(getcustom('express_maiyatian')) {
            if($set['myt_status'] == 1){
                $psid = -2;//  -1、码科  -2、麦芽田配送
            }
        }

		$order = Db::name($type)->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();

		//如果选择了配送时间，未到配送时间内不可以进行配送
		if(getcustom('business_withdraw')){
			if($order['freight_time']){
				$freight_time = explode('~',$order['freight_time']);
				$begin_time = strtotime($freight_time[0]);
				$date = explode(' ',$freight_time[0]);
				$end_time =strtotime($date[0].' '.$freight_time[1]);
				if(time()<$begin_time || time()>$end_time){
					return $this->json(['status'=>0,'msg'=>'未在配送时间内']);	
				}
			}
		}

		if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
		if($order['status']!=1 && $order['status']!=12) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);

        $other = [];
        if(getcustom('express_maiyatian')){
            $other['myt_shop_id'] = input('post.myt_shop_id')?input('post.myt_shop_id'):0;
            $other['myt_weight']  = input('post.myt_weight')?input('post.myt_weight'):1;
            if(!is_numeric($other['myt_weight'])){
                return $this->json(['status'=>0,'msg'=>'重量必须为纯数字']);
            }
            $other['myt_remark']  = input('post.myt_remark')?input('post.myt_remark'):'';
        }

		$rs = \app\model\PeisongOrder::create($type,$order,$psid,$other);
		if($rs['status']==0) return $this->json($rs);
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping(aid,$order,$type);
        }
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'配送'.$orderid);

		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}

    //下配送单
    public function peisongWx(){
        $orderid = input('post.orderid/d');
        $type = input('post.type');
        $psid = input('post.psid/d');
        $order = Db::name($type)->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();

        if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        if($order['status']!=1 && $order['status']!=12) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);

        $rs = \app\custom\ExpressWx::addOrder($type,$order,$psid);
        if($rs['status']==0) return $this->json($rs);
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping(aid,$order, $type);
        }
        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.'即时配送派单'.$orderid);

        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }

	//订单列表
	public function yuyueorder(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		$where[] = ['delete','=',0];
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if(getcustom('yuyue_yytime_search')){
            $yystarttime = input('param.yy_start_date');
            $yyendtime = input('param.yy_end_date');
            if($yystarttime && $yyendtime){
                $startTime = date('Y年m月d H:i', strtotime($yystarttime));
                $endTime = date('Y年m月d H:i', strtotime($yyendtime) + 86400);
                $where[] = ['yy_time', '>=', $startTime];
                $where[] = ['yy_time', '<=', $endTime];
            }

            $collect_start_date = input('param.collect_start_date');
            $collect_end_date = input('param.collect_end_date');
            if($collect_start_date && $collect_end_date){
                $where[] = ['collect_time', '>=', strtotime($collect_start_date)];
                $where[] = ['collect_time', '<=', strtotime($collect_end_date) + 86400];
            }
        }
        if($st == 'all'){
				
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '4'){
			$where[] = ['status','=',4];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('yuyue_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
        $showpaidanfee = true;
        if(bid>0){
            $showpaidanfee = false;
        }
		foreach($datalist as $key=>$v){
			if($v['bid']!=0){
				$datalist[$key]['binfo'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->field('id,name,logo')->find();
			}
			//查看服务状态
			if($v['worker_orderid']>0){
				$datalist[$key]['worker'] = Db::name('yuyue_worker_order')->where('aid',aid)->where('id',$v['worker_orderid'])->field('id,status')->find();
			}
			$datalist[$key]['senddate'] = date('Y-m-d H:i:s',$v['send_time']);
			$datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id',$v['mid'])->find();
			$datalist[$key]['showpaidanfee'] = $showpaidanfee;
			if(!$datalist[$key]['member']) $datalist[$key]['member'] = [];
            if(getcustom('yuyue_datetype1_model_selnum')){
                if(!empty($v['yydates'])){
                    $yydates = json_decode($v['yydates'],true);
                    $yydates_num = count($yydates);
                    $datalist[$key]['yy_time'] .= ' '.$yydates_num.'个时间段';
                }
            }

            $showlist=false;
            if(getcustom('yuyue_apply')){
                $showlist=true;
            }
            $datalist[$key]['showlist'] = $showlist;
            $datalist[$key]['checked']  = false;
		}
		$yuyue_sign = false;
		if(getcustom('yuyue_apply')){
			$yuyue_sign = true;
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['codtxt'] = Db::name('shop_sysset')->where('aid',aid)->value('codtxt');
		$rdata['st'] = $st;
		$rdata['yuyue_sign'] = $yuyue_sign;

        if(getcustom('yuyue_batchpaidan')){
            $rdata['checkstatus'] = true;
        }
        if(getcustom('yuyue_yytime_search')){
            $rdata['yytime_search'] = true;
        }
        $rdata['bid'] = bid;
		return $this->json($rdata);
	}

	public function yuyueorderdetail(){
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
		$detail = Db::name('yuyue_order')->where($where)->where('id',input('param.id/d'))->find();
		if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);

		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'yuyue_order');
        if(getcustom('yuyue_form_upload_pics') && $detail['formdata']){
            foreach ($detail['formdata'] as $fk => $fv){
                if($fv[2] == 'upload_pics'){
                    $detail['formdata'][$fk][1] = explode(',',$fv[1]);
                    break;
                }
            }
        }
		$storeinfo = [];
		if($detail['freight_type'] == 1){
            $storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('id,name,address,longitude,latitude')->find();
		}
        $ticheng = 0;
        $plateform_rate = 0;
        $yuyueset = Db::name('yuyue_set')->where('aid',aid)->field('autoclose')->find();
		if($detail['bid'] > 0){
			$business = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->field('id,name,logo,feepercent')->find();
            $detail['binfo'] = $business;
			$iscommentdp = 0;
			$commentdp = Db::name('business_comment')->where('orderid',$detail['id'])->find();
			if($commentdp) $iscommentdp = 1;
            if($business['feepercent']>0){
                $plateform_rate = $business['feepercent'];
            }
		}else{
			$iscommentdp = 1;
		}

        $showfeedetail = false;
        if(getcustom('yuyue_plateform_peisonguser')){
            $showfeedetail = true;
            $workerOrder = Db::name('yuyue_worker_order')->where('aid',aid)->where('id',$detail['worker_orderid'])->find();
            if($workerOrder && $workerOrder['ticheng']>0){
                $ticheng = $workerOrder['ticheng'];
            }
            $plateform_fee = 0;
            $totalprice = $detail['totalprice'];
            if($plateform_rate){
                $plateform_fee = round($totalprice * $plateform_rate * 0.01,2);
            }
            $js_totalprice = round($totalprice - $plateform_fee,2);
            $detail['ticheng'] = $ticheng;
            $detail['plateform_fee'] = $plateform_fee;
            $detail['js_totalprice'] = $js_totalprice;
        }
        $detail['showfeedetail'] = $showfeedetail;

		$prolist = Db::name('yuyue_order')->where('id',$detail['id'])->find();
		if($detail['status']==0 && $yuyueset['autoclose'] > 0 && $detail['paytypeid'] != 5){
			$lefttime = strtotime($detail['createtime']) + $yuyueset['autoclose']*60 - time();
			if($lefttime < 0) $lefttime = 0;
		}else{
			$lefttime = 0;
		}
        $canPaidan = true;
        if(getcustom('yuyue_plateform_peisonguser')){
            if(bid>0){
                $canPaidan = false;
            }
        }
        $detail['can_paidan'] = $canPaidan;
		
		$member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
		$detail['nickname'] = $member['nickname'];
		$detail['headimg'] = $member['headimg'];

		$showlist=false;
		if(getcustom('yuyue_apply')){
			$showlist=true;
		}
        if(getcustom('yuyue_gobusiness')){
            if($detail['fwtype'] == 3){
                $detail['fwbinfo'] = ['name'=>'不存在'];
                $fwbusines = Db::name('business')->where('id',$detail['fwbid'])->where('status',1)->where('aid',aid)->field('id,aid,name,logo,tel,linkman,linktel,province,city,district,address,latitude,longitude')->find();
                if($fwbusines){
                    $fwbusines['address'] = $fwbusines['province'].$fwbusines['city'].$fwbusines['district'].$fwbusines['address'];
                    $detail['fwbinfo'] = $fwbusines;
                }
                
            }
        }
        if(getcustom('yuyue_datetype1_model_selnum')){
            if(!empty($detail['yydates'])){
                $yydates = json_decode($detail['yydates'],true);
                $yydates_num = count($yydates);
                $detail['yy_time'] .= ' '.$yydates_num.'个时间段';
            }
        }
		$rdata = [];
		$rdata['detail'] = $detail;
		$rdata['showlist'] = $showlist;
		$rdata['iscommentdp'] = $iscommentdp;
		$rdata['prolist'] = $prolist;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['lefttime'] = $lefttime;

		$text = ['上门服务'=>'上门服务','到店服务'=>'到店服务'];
		if(getcustom('yuyue_fuwutype_text')){
			$yyset = Db::name('yuyue_set')->where('aid',aid)->where('bid',$detail['bid'])->find();
			if($yyset['fuwutype_text']) $text = json_decode($yyset['fuwutype_text'], true);
		}
		$rdata['text'] = $text;
		return $this->json($rdata);
	}

	//获取预约配送员
	public function getyuyuepsuser(){
		$orderid = input('?param.orderid')?input('param.orderid/d'):0;//单个派单
        $type = input('?param.type')?input('param.type'):'yuyue_order';

        $set = Db::name('yuyue_set')->where('aid',aid)->where('bid',bid)->find();

        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];

        if($orderid){
            $orders = Db::name($type)->where('id',$orderid)->where($where)->select()->toArray();
            if($orders) $bid = $orders[0]['bid'];
        }else{
            if(getcustom('yuyue_batchpaidan')){
                $orderids = input('?param.orderids')?input('param.orderids/a'):0;//批量派单
                if(!$orderids) return $this->json(['status'=>0,'msg'=>'请选择要派单的订单','peisonguser'=>[],'paidantype'=>$set['paidantype'],'psfee'=>0,'ticheng'=>0]);
                $orders = Db::name($type)->where('id','in',$orderids)->where($where)->select()->toArray();
                $bid = bid;//批量只能配送自己的配送员
            }else{
                return $this->json(['status'=>0,'msg'=>'请选择要派单的订单','peisonguser'=>[],'paidantype'=>$set['paidantype'],'psfee'=>0,'ticheng'=>0]);
            }
        }
        if(!$orders) return $this->json(['status'=>0,'msg'=>'订单数据不存在','peisonguser'=>[],'paidantype'=>$set['paidantype'],'psfee'=>0,'ticheng'=>0]);

        $psfee = $ticheng = 0;
		if($set['paidantype'] == 0){ //抢单模式
			$selectArr[] = ['id'=>0,'title'=>'--服务人员抢单--'];
		}else{
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['status','=',1];
            //使用平台配送员
            if(getcustom('yuyue_plateform_peisonguser') && bid>0){
                $bid = 0;
            }
            $where[] = ['bid','=',$bid];
			$peisonguser = Db::name('yuyue_worker')->where($where)->order('sort desc,id')->select()->toArray();
			if($peisonguser){
                foreach($peisonguser as &$v){
                    $dan = Db::name('yuyue_worker_order')->where('worker_id',$v['id'])->where('status','in','0,1')->count();
                    $v['title'] = $title = $v['realname'].'-'.$v['tel'].'(进行中'.$dan.'单)';
                }
                unset($v);
            }
		}

        $selectArrIds = $selectArr = [];
        foreach($orders as $order){
            if($order['worker_id'] && $order['worker_id']>0) return $this->json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单已有服务人员']);
            //服务人员订单
            $hasorder = Db::name('yuyue_worker_order')->where('orderid',$order['id'])->field('status')->find();
            if($hasorder && $hasorder['status'] >1 && $hasorder['status'] !=10){
                return $this->json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单已派单，不可变更']);
            }
            $ticheng+= $order['paidan_money'];
            $psfee  += $order['paidan_money'] * (1 + $set['businessfee']*0.01);

            //需要验证订单预约时间
            if($peisonguser){
                $dealyytime = \app\common\Order::dealyytime($order['yy_time'],$order['begintime']);
                $yybegintime = $dealyytime['yybegintime'];//开始时间
                $yyendtime = $dealyytime['yyendtime'];//结束时间
                foreach($peisonguser as $v){

                    //查看该服务人员该时间是否已经预约出去
                    $status = 1;
                    $workerorders = Db::name('yuyue_order')->where('worker_id',$v['id'])->where('aid',aid)->where('status','in','1,2')->select()->toArray();
                    if($workerorders){
                        foreach($workerorders as $ov){
                            $dealyytime2 = \app\common\Order::dealyytime($ov['yy_time'],$ov['begintime']);
                            $yybegintime2 = $dealyytime2['yybegintime'];//开始时间
                            $yyendtime2 = $dealyytime2['yyendtime'];//结束时间
                            if( ($yybegintime2==$yybegintime || $yyendtime2==$yyendtime) || ($yybegintime2<$yybegintime && $yyendtime2>=$yyendtime) || ($yybegintime2<=$yyendtime && $yyendtime2 > $yyendtime) ) {
                                $status = -1;
                            }
                        }
                        unset($ov);
                    }
                    unset($yv);
                    if($status == 1){
                        if(!in_array($v['id'],$selectArrIds)){
                            $selectArrIds[] = $v['id'];
                            $selectArr[] = ['id'=>$v['id'],'title'=>$v['title'],'status'=>$status];
                        }
                    }else{
                        if(in_array($v['id'],$selectArrIds)){
                            $pos = array_search($v['id'],$selectArrIds);
                            unset($selectArrIds[$pos]);
                            unset($selectArr[$pos]);
                        }
                    }
                }
                unset($v);
            }
        }
		return $this->json(['status'=>1,'peisonguser'=>$selectArr,'paidantype'=>$set['paidantype'],'psfee'=>$psfee,'ticheng'=>$ticheng]);
	}
    
	//派单
	public function yuyuepeisong(){
		$orderid  = input('?post.orderid')?input('post.orderid/d'):0;//单订单ID
        $orderids = input('?post.orderids')?input('post.orderids'):'';//多个订单ID
        $worker_id= input('post.worker_id/d');
        $type = input('?post.type')?input('post.type'):'';
        $othertype = input('?post.othertype')?input('post.othertype'):'';//新增的其他类型，之前改派类型传输错误type=update
        if(!$othertype){
            $othertype = $type;//兼容之前的type=update
        }
        if(!$orderid && !$orderids) return $this->json(['status'=>0,'msg'=>'请选择要派送的订单']);

		$where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        if($orderid){
            $orderids = '';//多个订单ID标记为空
            $where[] = ['id','=',$orderid];
        }else if($orderids){
            $where[] = ['id','in',$orderids];
        }else{
            $where[] = ['id','=',0];
        }
        $orders = Db::name('yuyue_order')->where($where)->select()->toArray();
        if(!$orders) return $this->json(['status'=>0,'msg'=>'订单不存在']);

        $yy_times = [];
        //检查参数
        foreach($orders as $order){
            if($worker_id && in_array($order['yy_time'],$yy_times)){
                return json(['status'=>0,'msg'=>'订单派单失败，预约时间段'.$order['yy_time'].'存在订单重复']);
            }
            $yy_times[] = $order['yy_time'];

            if($order['status']!=1 && $order['status']!=12) return $this->json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单状态不符合派单']);

            if(getcustom('yuyue_apply') && $othertype=='update'){
            }else{
                if($order['worker_id'] && $order['worker_id']>0) return $this->json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单已有服务人员']);
                //服务人员订单
                $hasorder = Db::name('yuyue_worker_order')->where('orderid',$order['id'])->field('status')->find();
                if($hasorder && $hasorder['status'] >1 && $hasorder['status'] !=10){
                    return $this->json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单已派单，不可变更']);
                }
            }

            //查看该服务人员该时间是否已经预约出去
            if($worker_id){
                $status = 1;
                $dealyytime  = \app\common\Order::dealyytime($order['yy_time'],$order['begintime']);
                $yybegintime = $dealyytime['yybegintime'];//开始时间
                $yyendtime = $dealyytime['yyendtime'];//结束时间
                $workerorders = Db::name('yuyue_order')->where('worker_id',$worker_id)->where('aid',aid)->where('status','in','1,2')->select()->toArray();
                if($workerorders){
                    foreach($workerorders as $ov){
                        $dealyytime2 = \app\common\Order::dealyytime($ov['yy_time'],$ov['begintime']);
                        $yybegintime2 = $dealyytime2['yybegintime'];//开始时间
                        $yyendtime2 = $dealyytime2['yyendtime'];//结束时间
                        if( ($yybegintime2==$yybegintime || $yyendtime2==$yyendtime) || ($yybegintime2<$yybegintime && $yyendtime2>=$yybegintime) || ($yybegintime2<=$yyendtime && $yyendtime2 > $yyendtime) ) {
                            $status = -1;
                        }
                    }
                    unset($ov);
                }
                if($status != 1) return $this->json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单派单失败，该预约时间段服务人员已有订单在服务']);
            }

        }

        $successnum = 0;
        $failnum = 0;
        foreach($orders as $order){

            //再次查看该服务人员该时间是否已经预约出去
            if($orderids && $worker_id){
                $status = 1;
                $dealyytime  = \app\common\Order::dealyytime($order['yy_time'],$order['begintime']);
                $yybegintime = $dealyytime['yybegintime'];//开始时间
                $yybegintime = $dealyytime['yybegintime'];//结束时间
                $workerorders = Db::name('yuyue_order')->where('worker_id',$worker_id)->where('aid',aid)->where('status','in','1,2')->select()->toArray();
                if($workerorders){
                    foreach($workerorders as $ov){
                        $dealyytime2 = \app\common\Order::dealyytime($ov['yy_time'],$ov['begintime']);
                        $yybegintime2 = $dealyytime2['yybegintime'];//开始时间
                        $yyendtime2 = $dealyytime2['yyendtime'];//结束时间
                        if( ($yybegintime2==$yybegintime || $yyendtime2==$yyendtime) || ($yybegintime2<$yybegintime && $yyendtime2>=$yybegintime) || ($yybegintime2<=$yyendtime && $yyendtime2 > $yyendtime) ) {
                            $status = -1;
                        }
                    }
                    unset($ov);
                }
                if($status != 1){
                    $failnum++;
                    continue;
                }
            }

            //取出该订单的服务人员
            $fwpeoid = Db::name('yuyue_product')->where('id',$order['proid'])->where('aid',aid)->where('bid',$order['bid'])->value('fwpeoid');
            if(getcustom('yuyue_apply') && $othertype=='update'){
                $rs = \app\model\YuyueWorkerOrder::update($order,$worker_id,$fwpeoid);
            }else{
                $rs = \app\model\YuyueWorkerOrder::create($order,$worker_id,$fwpeoid);
            }
            if($rs['status']==0){
                $failnum++;
                if($orderid || ($orderids && count($orderids) == 1)) return json($rs);//单个的需要报错，多个的不需要报错，只统计错误个数
            }else{
                $successnum++;//统计成功个数
                \app\common\System::plog('预约派单'.$order['id']);
            }
        }

        $remark = '';
        if($orderids){
            $remark = ',成功派单'.$successnum.'个订单,失败'.$failnum.'个订单';
        }

        $typeName = \app\common\Order::getOrderTypeName($type);
        \app\common\System::plog('手机端后台'.$typeName.$remark);

        return json(['status'=>1,'msg'=>'操作成功'.$remark]);
	}

	public function selectworker(){
		$orderid = input('param.id/d');
		$orders = Db::name('yuyue_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
        if(empty($orders)) return $this->json(['status'=>0,'msg'=>'订单不存在']);
		//取出该订单的服务人员
		$fwpeoid = Db::name('yuyue_product')->where('id',$orders['proid'])->where('aid',aid)->where('bid',bid)->value('fwpeoid');
		$pernum = 10;
		$pagenum = input('param.pagenum');
		if(!$pagenum) $pagenum = 1;
		$peoarr = explode(',',$fwpeoid);
		$longitude = $orders['longitude'];
		$latitude = $orders['latitude'];
		if($longitude && $latitude){
			$orderBy = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) ");
		}else{
			$orderBy = 'sort desc,id';
		}

		$datalist = Db::name('yuyue_worker')->where('aid',aid)->where('status',1)->where('id','in',$peoarr)->page($pagenum,$pernum)->order($orderBy)->select()->toArray();
		//查看该时间是否已经预约出去
		foreach($datalist as &$d){
			$type = Db::name('yuyue_worker_category')->where(['id'=>$d['cid']])->find();
			$d['typename'] = $type['name'];
			$order = Db::name('yuyue_order')->where('aid',aid)->where('worker_id',$d['id'])->where('status','in','1,2')->where('yy_time',$orders['yy_time'])->find();
			$d['yystatus']=1;
			if($order){
				$d['yystatus']=-1;
			}
			//服务人员到用户的距离 骑行距离
            $mapqq = new \app\common\MapQQ();
            $bicycl = $mapqq->getDirectionDistance($orders['longitude'],$orders['latitude'],$d['longitude'],$d['latitude'],1);
            if($bicycl && $bicycl['status']==1){
                $juli = $bicycl['distance'];
            }else{
                $juli = getdistance($orders['longitude'],$orders['latitude'],$d['longitude'],$d['latitude'],1);
            }
            //var_dump($juli);
			if($juli> 1000){
				$d['juli'] = round($juli/1000,1);
				$d['juli_unit'] = 'km';
			}else{
				$d['juli_unit'] = 'm';
			}
		}
		if(!$datalist) $datalist = [];
		return $this->json(['status'=>1,'data'=>$datalist]);
	}
	public function addyuyuemoney(){
		$orderid = input('post.orderid');
		$price = input('post.price');
		if(!$price) return $this->json(['status'=>0,'msg'=>'请填写金额']);

        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
		$order = Db::name('yuyue_order')->where($where)->where(['id'=>$orderid])->find();
		if(!$order ) return $this->json(['status'=>0,'msg'=>'预约订单不存在']);
		$wokerorder = Db::name('yuyue_worker_order')->where($where)->where(['id'=>$order['worker_orderid']])->find();
		if(!$wokerorder )  return $this->json(['status'=>0,'msg'=>'服务订单不存在']);
	
		$addmoneyPayorderid = input('post.addmoneyPayorderid');
		if($addmoneyPayorderid){
			//修改
			if($price == $order['addmoney']){
				return $this->json(['status'=>0,'msg'=>'金额无变化']);
			}
			$payorder = Db::name('payorder')->where(['id'=>$addmoneyPayorderid])->find();
			if(!$payorder){
				return $this->json(['status'=>0,'msg'=>'支付订单不存在']);
			}
			Db::name('payorder')->where(['id'=>$addmoneyPayorderid])->update(['money'=>$price]);
			$balance_pay_orderid = $addmoneyPayorderid;
			Db::name('yuyue_order')->where(['id'=>$orderid])->update(['addmoney'=>$price]);

            \app\common\System::plog('手机端后台预约订单修改差价'.$orderid);
		}

	    return $this->json(['status'=>0,'msg'=>'修改成功','payorderid'=>$balance_pay_orderid]);
	}

    //下配送单
    public function mytprice(){
        if(getcustom('express_maiyatian')) {
            if(request()->isPost()){
                //权限
                $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                if($admin_user){
                    if($admin_user['auth_type'] !=1 ){
                        $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                        if(!in_array('Peisong/mytset,Peisong/*',$admin_auth)){
                            return $this->json(['status'=>0,'msg'=>'此功能已关闭']);
                        }
                    }
                }

                //预先读取计费
                $orderid = input('orderid/d');
                $type    = input('type');

                $order = Db::name($type)->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
                
                //如果选择了配送时间，未到配送时间内不可以进行配送
                if(getcustom('business_withdraw')){
                    if($order['freight_time']){
                        $freight_time = explode('~',$order['freight_time']);
                        $begin_time = strtotime($freight_time[0]);
                        $date = explode(' ',$freight_time[0]);
                        $end_time =strtotime($date[0].' '.$freight_time[1]);
                        if(time()<$begin_time || (time()>$end_time)){
                            return $this->json(['status'=>0,'msg'=>'未在配送时间内']);    
                        }
                    }
                }
                if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
                if($order['status']!=1 && $order['status']!=12) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);

                $data   = '';//计价数据
                $detail = '';//配送详情
                $weight = \app\custom\MaiYaTianCustom::getweight(aid,$order,$type);//默认一千克

                $other = [];
                $other['myt_shop_id'] = 0;
                $other['myt_weight']  = $weight;
                $other['myt_remark']  = '';

                //预估配送费
                $set = Db::name('peisong_set')->where('aid',aid)->find();
                $res_price = \app\custom\MaiYaTianCustom::order_price(aid,$set,$order,'',$other,1);

                $msg = '';
                if($res_price['status']== 0){
                    $msg = $res_price['msg'].'(麦芽田返回)';
                }else{
                    $data = $res_price['data'];
                    if($data && $data['detail']){
                        $detail = $data['detail'];
                    }
                }

                $resdata = [];
                $resdata['orderid'] = $orderid;
                $resdata['type']    = $type;
                $resdata['data']    = $data;
                $resdata['detail']  = $detail;
                $resdata['weight']  = $weight;
                $resdata['msg']     = $msg;
                return $this->json(['status'=>1,'data'=>$resdata]);
            }
        }else{
            return $this->json(['status'=>0,'data'=>[],'msg'=>'未开启此功能']);
        }
    }

    //商城订单详情
    public function weightOrderFahuo(){
        if(getcustom('product_weight')){
            $orderid = input('param.id/d');
            $detail = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
            if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);
            if($detail['status']!=1){
                return $this->json(['status'=>0,'msg'=>'该订单状态无需发货']);
            }
            if(request()->isPost()){
                    Db::startTrans();
                    $prolist = input('param.prolist/a');
                    $totalprice = 0;
                    $changeNum = 0;
                    foreach ($prolist as $k=>$goods){
                        $gprice = $goods['real_sell_price'];
                        $gweight = $goods['real_total_weight'];
                        if($gprice != $goods['sell_price'] || $gweight!=$goods['total_weight']){
                            $changeNum++;
                            $real_totalprice = round($gprice * $gweight,2);
                            $update = [
                                'real_sell_price' => $gprice,
                                'real_total_weight' => $gweight * 500,
                                'real_totalprice' => $real_totalprice,
                                'status'=>2
                            ];
                            $totalprice = $totalprice + $real_totalprice;
                            Db::name('shop_order_goods')->where('aid',aid)->where('bid',bid)->where('id',$goods['id'])->update($update);
                        }
                    }
                    if($totalprice<$detail['totalprice']){
                        //自动退款
                        $refundMoney = $detail['totalprice'] - $totalprice;
                        $rs = \app\common\Order::refund($detail,$refundMoney,'订单差额退款');
                        if($rs['status']!=1){
                            Db::rollback();
                            return  $this->json(['status'=>0,'msg'=>$rs['msg']]);
                        }
                    }
                    $orderUpdate = ['status'=>2,'send_time'=>time()];
                    if($changeNum>0){
                        $orderUpdate['totalprice'] = $totalprice;
                    }
                    Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('id',$orderid)->update($orderUpdate);
                    Db::commit();
                    return  $this->json(['status'=>1,'msg'=>'发货成功']);
            }
            $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
            $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
            $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
            $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
            $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
            $detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'shop_order');

            $member = Db::name('member')->where('id',$detail['mid'])->field('id,nickname,headimg')->find();
            $detail['nickname'] = $member['nickname'];
            $detail['headimg'] = $member['headimg'];
            $prolist = Db::name('shop_order_goods')->where('orderid',$detail['id'])->select()->toArray();
            foreach ($prolist as $k=>$v){
                $prolist[$k]['total_weight'] = round($v['total_weight']/500,2);
                $prolist[$k]['real_total_weight'] = round($v['real_total_weight']/500,2);
            }
            $detail['message'] = \app\model\ShopOrder::checkOrderMessage($detail['id'],$detail);
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['detail'] = $detail;
            $rdata['prolist'] = $prolist;
            return $this->json($rdata);
        }
    }

    public function wifiprint(){
        if (getcustom('shop_order_mobile_wifiprint')){
            $ids = input('post.ids');
            foreach($ids as $id){
                $rs = \app\common\Wifiprint::print(aid,'shop',$id,0);
            }
            return $this->json($rs);
        }
    }
    //改价格
    public function changeprice(){

        if(request()->isPost()) {
            //预先读取计费
            $orderid = input('post.orderid/d');
            $type = input('post.type');
            $newprice = input('post.val/f');
            $newordernum = date('ymdHis').rand(100000,999999);

            Db::startTrans();
            $where = [];
            $where[] = ['aid','=',aid];
            if(bid > 0){
                $where[] = ['bid','=',bid];
            }

            $order = Db::name('shop_order')->where($where)->where('id',$orderid)->find();
            if($newprice > $order['totalprice']) return json(['status'=>0,'msg'=>'只能优惠不可加价，加价可通过下单其他商品补差价']);
            $ordernumArr = explode('_',$order['ordernum']);
            if($ordernumArr[1]) $newordernum .= '_'.$ordernumArr[1];
            $discount_money_admin = $order['totalprice']-$newprice;//管理员优惠金额（正数）
            Db::name('shop_order')->where($where)->where('id',$orderid)->update(['totalprice'=>$newprice,'ordernum'=>$newordernum,'discount_money_admin'=>$discount_money_admin]);
            Db::name('shop_order_goods')->where($where)->where('orderid',$orderid)->update(['ordernum'=>$newordernum]);
            //订单商品价格也需同步修改，涉及商家结算
            $oglist = Db::name('shop_order_goods')->where($where)->where('orderid',$orderid)->select()->toArray();
            foreach ($oglist as $og){
                $rate = $newprice/$order['totalprice'];
                $og['real_totalprice'] = $rate*$og['real_totalprice'];
                if(!is_null($og['business_total_money'])) {
                    $og['business_total_money'] = $rate*$og['business_total_money'];
                }
                if(getcustom('yx_buyer_subsidy')) {
                    $og['order_fee'] = $rate*$og['order_fee'];
                }
                Db::name('shop_order_goods')->where('id',$og['id'])->where('orderid',$orderid)->update($og);
            }

            //修改价格重新计算分销
            \app\common\Order::editShopOrderCommission(aid,$orderid);
            $payorderid = Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->value('payorderid');

            \app\model\Payorder::updateorder($payorderid,$newordernum,$newprice,$orderid);

            $typeName = \app\common\Order::getOrderTypeName($type);
            Db::commit();
            \app\common\System::plog('手机端后台'.$typeName.'改价格'.$orderid.'，原价格:'.$order['totalprice'].'，新价格:'.$newprice);
            return $this->json(['status'=>1,'msg'=>'修改完成']);
        }
    }

    //退款信息查询
    public function refundinit(){
        //查询订单信息
        $detail = Db::name('shop_order')->where('id',input('param.orderid/d'))->where('aid',aid)->where('bid',bid)->find();
        if(!$detail){
            return $this->json(['status'=>0,'msg'=>'订单不存在']);
        }

        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
        $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
        $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
        $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';

        $refundMoneySum = Db::name('shop_refund_order')->where('orderid',$detail['id'])->where('aid',aid)->whereIn('refund_status',[1,2,4])->sum('refund_money');

        $canRefundNum = 0;
        $totalNum = 0;
        $returnTotalprice = 0;
        $prolist = Db::name('shop_order_goods')->where('orderid',$detail['id'])->select()->toArray();
        foreach ($prolist as $key => $item) {
            $prolist[$key]['canRefundNum'] = $item['num'] - $item['refund_num'];
            $totalNum += $item['num'];
            $canRefundNum += $item['num'] - $item['refund_num'];
//            $returnTotalprice += $item['real_totalprice'] / $item['num'] * ($item['num'] - $item['refund_num']);
        }
        $totalprice = $detail['totalprice'];
        if($detail['balance_price'] > 0 && $detail['balance_pay_status'] == 0){
            $totalprice = $totalprice - $detail['balance_price'];
        }
        if($canRefundNum == $totalNum) {
            $returnTotalprice = $totalprice;
        } else {
            $returnTotalprice = $totalprice - $refundMoneySum;
        }
        //可退款金额=总金额-审核中-已退款
        $detail['canRefundNum'] = $canRefundNum;
        $detail['totalNum'] = $totalNum;
        $detail['returnTotalprice'] = $returnTotalprice;
//        if($canRefundNum == 0) {
//            return $this->json(['status'=>0,'msg'=>'当前订单没有可退款的商品']);
//        }
        //todo 确认收货后的退款

        $rdata = [];
        $rdata['status'] = 1;
        $rdata['detail'] = $detail;
        $rdata['prolist'] = $prolist;

        return $this->json($rdata);
    }

    //退款
    public function refund(){
        $orderid = input('post.orderid/d');
        $reason = input('post.reason');
        $order = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
        if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        if($order['status']!=1 && $order['status']!=2){
            return $this->json(['status'=>0,'msg'=>'该订单状态不允许退款']);
        }
        $refundingMoney = Db::name('shop_refund_order')->where('orderid',$order['id'])->where('aid',aid)->whereIn('refund_status',[1,4])->sum('refund_money');
        if($refundingMoney) {
            return $this->json(['status'=>0,'msg'=>'请先处理完进行中的退款单']);
        }
        $shopset = Db::name('shop_sysset')->where('aid',aid)->find();

        if (getcustom('shoporder_admin_refund_switch') && $shopset['order_admin_refund_switch']==0){
            return $this->json(['status'=>0,'msg'=>'退款功能已关闭']);
        }

        try {
            Db::startTrans();
            //新退款 202108
            $post = input('post.');
            $orderid = intval($post['orderid']);
            $money = floatval($post['money']);
            $refundNum = $post['refundNum'];

            $order = Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->find();
            if (!$order || ($order['status'] != 1 && $order['status'] != 2)) {
                return $this->json(['status' => 0, 'msg' => '订单状态不符合退款要求']);
            }
            if ($money < 0 || $money > $order['totalprice']) {
                return $this->json(['status' => 0, 'msg' => '退款金额有误']);
            }
            if (empty($refundNum)) {
                return $this->json(['status' => 0, 'msg' => '请选择退款的商品']);
            }
            $totalRefundNum = 0;
            $returnTotalprice = 0;
            $prolist = Db::name('shop_order_goods')->where('orderid', $orderid)->select();
            $newKey = 'id';
            $prolist = $prolist->dictionary(null, $newKey);
            $ogids = array_keys($prolist);
            $refundMoneySum = Db::name('shop_refund_order')->where('orderid', $orderid)->where('aid', aid)->whereIn('refund_status', [1, 2, 4])->sum('refund_money');

            $canRefundNum = 0;
            $totalNum = 0;
            $canRefundProductPrice = 0;
            $canRefundTotalprice = 0;
            if(getcustom('freeze_money')){
                $refundFreezemoney = 0;
            }
            foreach ($prolist as $key => $item) {
                $prolist[$key]['canRefundNum'] = $item['num'] - $item['refund_num'];
                $totalNum += $item['num'];
                $canRefundNum += $item['num'] - $item['refund_num'];
                $canRefundProductPrice += $item['real_totalprice'] / $item['num'] * ($item['num'] - $item['refund_num']);
            }

            foreach ($refundNum as $item) {
                if (!in_array($item['ogid'], $ogids)) {
                    return $this->json(['status' => 0, 'msg' => '退款商品不存在']);
                }
                if ($item['num'] > $prolist[$item['ogid']]['num'] - $prolist[$item['ogid']]['refund_num']) {
                    return $this->json(['status' => 0, 'msg' => $prolist[$item['ogid']]['name'] . '退款数量超出范围']);
                }
                $totalRefundNum += $item['num'];
                $returnTotalprice += $prolist[$item['ogid']]['real_totalprice'] / $prolist[$item['ogid']]['num'] * $item['num'];
                if(getcustom('freeze_money')){
                    //冻结资金支付
                    $refundFreezemoney += $prolist[$item['ogid']]['freezemoney_price'] * $item['num'];
                }
            }
            if ($totalRefundNum == 0) {
                return $this->json(['status' => 0, 'msg' => '请选择退款的商品']);
            }
            $totalprice = $order['totalprice'];
            if($order['balance_price'] > 0 && $order['balance_pay_status'] == 0){
                $totalprice = $totalprice - $order['balance_price'];
            }
            if ($canRefundNum == $totalNum && $totalNum == $totalRefundNum) {
                $canRefundTotalprice = $totalprice;
            } else {
                $canRefundTotalprice = $totalprice - $refundMoneySum;
            }

            if ($money > $canRefundTotalprice) {
                return $this->json(['status' => 0, 'msg' => '退款金额超出范围']);
            }

            $data = [
                'aid' => $order['aid'],
                'bid' => $order['bid'],
                'mdid' => $order['mdid'],
                'mid' => $order['mid'],
                'orderid' => $order['id'],
                'ordernum' => $order['ordernum'],
                'refund_type' => 'refund',
                'refund_ordernum' => '' . date('ymdHis') . rand(100000, 999999),
                'refund_money' => $money,
                'refund_reason' => '手机端后台退款：' . $post['reason'],
                'refund_pics' => '',
                'createtime' => time(),
                'refund_time' => time(),
                'refund_status' => 2,
                'platform' => platform,
            ];

            if(getcustom('pay_money_combine')){
                //处理余额组合支付退款
                $res = \app\custom\OrderCustom::deal_refund_combine($order,$money);
                if($res['status'] == 1){
                    $data['refund_combine_money']  = $res['refund_combine_money'];//退余额部分
                    $data['refund_combine_wxpay']  = $res['refund_combine_wxpay'];//退微信部分
                    $data['refund_combine_alipay'] = $res['refund_combine_alipay'];//退支付宝部分
                }else{
                    return $this->json($res);
                }
            }
            if(getcustom('erp_wangdiantong')) {
                $data['wdt_status'] = $order['wdt_status'];
            }
            if(getcustom('freeze_money')){
                //冻结资金支付
                $data['refund_freezemoney'] = $refundFreezemoney;
            }
            $refund_id = Db::name('shop_refund_order')->insertGetId($data);

            if ($order['fromwxvideo'] == 1) {
                \app\common\Wxvideo::aftersaleadd($order['id'], $refund_id);
            }
            if ($data['refund_money'] > 0) {
                $is_refund = 1;
                if(getcustom('shop_product_fenqi_pay')){
                    if($order['is_fenqi'] == 1){
                        $rs = \app\common\Order::fenqi_refund($order,$data['refund_money'], $reason);
                        if($rs['status']==0){
                            return $this->json(['status'=>0,'msg'=>$rs['msg']]);
                        }
                        $is_refund = 0;
                    }

                }
                if($is_refund){
                    $params = [];
                    if(getcustom('pay_money_combine')){
                        //如果是组合支付，退款需要判断余额、微信、支付宝退款部分
                        if($order['combine_money']>0 && ($order['combine_wxpay']>0 || $order['combine_alipay']>0)){
                            //refund_combine 1 走shop_refund_order 退款;2 走shop_order 退款
                            $params = [
                                'refund_combine'=> 1,
                                'refund_order'  => $data
                            ];
                        }
                    }
                    if(getcustom('erp_hupun')){
                        //万里牛erp
                        $wln = new \app\custom\Hupun($order['aid']);
                        $wln->orderPush($order['id']);
                    }
                    if(getcustom('freeze_money') && !getcustom('pay_money_combine')){
                        //冻结资金支付
                        $params = [
                            'refund_order'  => $data
                        ];
                    }
                    $rs = \app\common\Order::refund($order, $data['refund_money'], $reason,$params);
                    if ($rs['status'] == 0) {
                        if($order['balance_price'] > 0){
                            $order2 = $order;
                            $order2['totalprice'] = $order2['totalprice'] - $order2['balance_price'];
                            $order2['ordernum'] = $order2['ordernum'].'_0';
                            $rs = \app\common\Order::refund($order2,$data['refund_money'],$reason);
                            if($rs['status']==0){
                                Db::name('shop_refund_order')->where('id', $refund_id)->delete();
                                return $this->json(['status'=>0,'msg'=>$rs['msg']]);
                            }
                        }else{
                            Db::name('shop_refund_order')->where('id', $refund_id)->delete();
                            return $this->json(['status'=>0,'msg'=>$rs['msg']]);
                        }
                    }
                }

            }

            $ztuikuan = count($refundNum);
            $refund_money_z = 0;
            foreach ($refundNum as $item) {
                if ($item['num'] < 1) continue;

                //退款金额 *（单价*退款数量）/退款总价=当前商品占退款金额比例
                $refund_money = $returnTotalprice==0?0:($money * (($prolist[$item['ogid']]['real_totalprice'] / $prolist[$item['ogid']]['num']) * $item['num'] / $returnTotalprice));
                $refund_money = round($refund_money,2);
                if($ztuikuan > 1 && ($ztuikuan == ($rk+1))){
                    $refund_money = round(($money - $refund_money_z),2);
                }
                $refund_money_z += $refund_money;

                $od = [
                    'aid' => $order['aid'],
                    'bid' => $order['bid'],
                    'mid' => $order['mid'],
                    'orderid' => $order['id'],
                    'ordernum' => $order['ordernum'],
                    'refund_orderid' => $refund_id,
                    'refund_ordernum' => $data['refund_ordernum'],
                    'refund_num' => $item['num'],
                    // 'refund_money' => $item['num'] * $prolist[$item['ogid']]['real_totalprice'] / $prolist[$item['ogid']]['num'],
                    'refund_money' => $refund_money,//退款金额 *（单价*退款数量）/退款总价=当前商品占退款金额比例
                    'ogid' => $item['ogid'],
                    'proid' => $prolist[$item['ogid']]['proid'],
                    'name' => $prolist[$item['ogid']]['name'],
                    'pic' => $prolist[$item['ogid']]['pic'],
                    'procode' => $prolist[$item['ogid']]['procode'],
                    'ggid' => $prolist[$item['ogid']]['ggid'],
                    'ggname' => $prolist[$item['ogid']]['ggname'],
                    'cid' => $prolist[$item['ogid']]['cid'],
                    'cost_price' => $prolist[$item['ogid']]['cost_price'],
                    'sell_price' => $prolist[$item['ogid']]['sell_price'],
                    'createtime' => time()
                ];
                if(getcustom('erp_wangdiantong')) {
                    $od['wdt_status'] = $prolist[$item['ogid']]['wdt_status'];
                }
                Db::name('shop_refund_order_goods')->insertGetId($od);
                Db::name('shop_order_goods')->where('aid', aid)->where('id', $item['ogid'])->inc('refund_num', $item['num'])->update();
                if(getcustom('consumer_value_add')){
                    $goods_info =  Db::name('shop_order_goods')->where('aid', aid)->where('id', $item['ogid'])->find();
                    //扣除绿色积分
                    if($goods_info['give_green_score2'] > 0){
                        \app\common\Member::addgreenscore(aid,$order['mid'],-bcmul($goods_info['give_green_score2'],$item['num'],2),'订单退款扣除'.t('绿色积分'),'shop_order',$orderid);
                        \app\common\Member::addmaximum(aid,$order['mid'],-bcmul($goods_info['give_maximum'],$item['num'],2),'订单退款扣除','shop_order',$orderid);
                    }
                    //扣除奖金池
                    if($goods_info['give_bonus_pool2'] > 0){
                        \app\common\Member::addbonuspool(aid,$order['mid'],-bcmul($goods_info['give_bonus_pool2'],$item['num'],2),'订单退款扣除'.t('奖金池'),'shop_order',$orderid,0,-bcmul($goods_info['give_green_score2'],$item['num'],2));
                    }
                }
            }

            //        $order_goods = Db::name('shop_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('bid',bid)->fieldRaw('ggid,proid,num,refund_num, num-refund_num as true_num')->select()->toArray();
            //		Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4,'refund_status'=>2,'refund_money'=>$refund_money,'refund_reason'=>$reason]);
            //		Db::name('shop_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4,'refund_num' => Db::raw('num')]);

            $is_mendian_usercenter = 0;
            if(getcustom('mendian_usercenter') && $order['mdid']){
                $is_mendian_usercenter = 1;
            }
            //恢复库存 删除销量
            foreach ($refundNum as $item) {
                if($is_mendian_usercenter==1){
                    //门店中心增加门店库存
                    \app\custom\MendianUsercenter::addMendianStock(aid,$order['lock_mdid'],$prolist[$item['ogid']]['proid'],$prolist[$item['ogid']]['ggid'],$item['num']);
                }
                if($is_mendian_usercenter==0) {
                    Db::name('shop_guige')->where('aid', aid)->where('id', $prolist[$item['ogid']]['ggid'])->update(['stock' => Db::raw("stock+" . $item['num']), 'sales' => Db::raw("sales-" . $item['num'])]);
                    Db::name('shop_product')->where('aid', aid)->where('id', $prolist[$item['ogid']]['proid'])->update(['stock' => Db::raw("stock+" . $item['num']), 'sales' => Db::raw("sales-" . $item['num'])]);
                }
                if (getcustom('guige_split')) {
                    \app\model\ShopProduct::addlinkstock($prolist[$item['ogid']]['proid'], $prolist[$item['ogid']]['ggid'], $item['num']);
                }
                if(getcustom('ciruikang_fenxiao')){
                    //是否开启了商城商品需上级购买足量
                    $og = $prolist[$item['ogid']];
                    if($og){
                        $deal_ogstock2 = \app\custom\CiruikangCustom::deal_ogstock2($order,$og,$item['num'],'下级订单退款');
                    }
                }
                if(getcustom('shoporder_refund_sendcoupon')){
                    //退款是否关联优惠券，关联则退款时赠送的优惠券也同时失效
                    if($shopset['return_sendcoupon']){
                        Db::name('coupon_record')->where('mid',$order['mid'])->where('source','shop')->where('orderid',$order['id'])->where('proid',$prolist[$item['ogid']]['proid'])->where('status',0)->where('endtime','>',time())->update(['endtime'=>time()]);
                    }
                }
            }

            //整单全部退时 返还积分和优惠券
            if ($totalRefundNum == $canRefundNum && $money == $canRefundTotalprice) {
                Db::name('shop_order')->where('id', $order['id'])->where('aid', aid)->update(['status' => 4, 'refund_status' => 2, 'refund_money' => $refundMoneySum + $data['refund_money']]);
                Db::name('shop_order_goods')->where('orderid', $order['id'])->where('aid', aid)->update(['status' => 4]);
                //积分抵扣的返还
                if ($order['scoredkscore'] > 0) {
                    \app\common\Member::addscore(aid, $order['mid'], $order['scoredkscore'], '订单退款返还');
                }
                if ($order['givescore2'] > 0) {
                    \app\common\Member::addscore(aid, $order['mid'], -$order['givescore2'], '订单退款扣除');
                }
                //扣除消费赠送积分
                \app\common\Member::decscorein(aid,'shop',$order['id'],$order['ordernum'],'订单退款扣除消费赠送');
                //查询后台是否开启退还已使用的优惠券
                $return_coupon = Db::name('shop_sysset')->where('aid',aid)->value('return_coupon');
                //优惠券抵扣的返还
                if ($return_coupon && $order['coupon_rid']) {
                    \app\common\Coupon::refundCoupon2(aid,$order['mid'],$order['coupon_rid'],$order,2);
                }
                //元宝返回
                if (getcustom('pay_yuanbao') && $order['is_yuanbao_pay'] == 1 && $order['total_yuanbao'] > 0) {
                    \app\common\Member::addyuanbao(aid, $order['mid'], $order['total_yuanbao'], '订单退款返还');
                }
                if ($order['givescore2'] > 0) {
                    \app\common\Member::addscore(aid, $order['mid'], -$order['givescore2'], '订单退款扣除');
                }
                if(getcustom('money_dec')){
                    if($order['dec_money']>0){
                        \app\common\Member::addmoney(aid,$order['mid'],$order['dec_money'],t('余额').'抵扣返回，订单号: '.$order['ordernum']);
                    }
                }
                if(getcustom('yx_invite_cashback')){
                    //取消邀请返现
                    \app\custom\OrderCustom::cancel_invitecashbacklog(aid,$order,'订单退款');
                }
                //退款退还佣金
                if(getcustom('commission_orderrefund_deduct')){
                    \app\common\Fenxiao::refundFenxiao(aid,$order['id'],'shop');
                    \app\common\Order::refundFenhongDeduct($order,'shop');
                }
                if(getcustom('member_goldmoney_silvermoney')){
                    //返回银值抵扣
                    if($order['silvermoneydec']>0){
                        $res = \app\common\Member::addsilvermoney(aid,$order['mid'],$order['silvermoneydec'],t('银值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
                    }
                    //返回金值抵扣
                    if($order['goldmoneydec']>0){
                        $res = \app\common\Member::addgoldmoney(aid,$order['mid'],$order['goldmoneydec'],t('金值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
                    }
                }
                if(getcustom('member_dedamount')){
                    //返回抵抗金抵扣
                    if($order['dedamount_dkmoney'] && $order['dedamount_dkmoney']>0){
                        $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop','opttype'=>'return'];
                        \app\common\Member::adddedamount(aid,$order['bid'],$order['mid'],$order['dedamount_dkmoney'],'抵扣金抵扣返回，订单号: '.$order['ordernum'],$params);
                    }
                }
                if(getcustom('member_shopscore')){
                    //返回产品积分抵扣
                    if($order['shopscore']>0 && $order['shopscore_status'] == 1){
                        $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop'];
                        \app\common\Member::addshopscore(aid,$order['mid'],$order['shopscore'],t('产品积分').'抵扣返回，订单号: '.$order['ordernum'],$params);
                    }
                }
            } else {
                //部分退款
                Db::name('shop_order')->where('id', $order['id'])->where('aid', aid)->inc('refund_money', $data['refund_money'])->update(['refund_status' => 2]);
                //重新计算佣金
                $prolist = Db::name('shop_order_goods')->where('orderid', $order['id'])->where('aid', aid)->select();
                $reog = Db::name('shop_refund_order_goods')->where('refund_orderid', $refund_id)->select();
                \app\common\Order::updateCommission($prolist, $reog);

                //判断当前订单是否全部退款 退款关闭
                $total_num = Db::name('shop_order_goods')->where('orderid', $order['id'])->where('aid', aid)->field("SUM(`num`) as total_num,SUM(`refund_num`) as total_refund_num")->find();
                if ($total_num['total_num'] == $total_num['total_refund_num']) {
                    if($money >= $canRefundTotalprice){
                        Db::name('shop_order')->where('id', $order['id'])->where('aid', aid)->update(['status' => 4, 'refund_status' => 2]);
                        Db::name('shop_order_goods')->where('orderid', $order['id'])->where('aid', aid)->update(['status' => 4]);
                    }else{
                        Db::name('shop_order')->where('id', $order['id'])->where('aid', aid)->update(['refund_status' => 2]);
                    }
                }
            }

            if(getcustom('yx_mangfan')){
                \app\custom\Mangfan::delAndCreate(aid, $order['mid'], $order['id'], $order['ordernum']);
            }

            //		//积分抵扣的返还
            //		if($order['scoredkscore'] > 0){
            //			\app\common\Member::addscore(aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
            //		}
            //		//优惠券抵扣的返还
            //		if($order['coupon_rid'] > 0){
            //			Db::name('coupon_record')->where('aid',aid)->where(['mid'=>$order['mid'],'id'=>$order['coupon_rid']])->update(['status'=>0,'usetime'=>'']);
            //		}

            if (getcustom('cefang') && aid == 2) { //定制1 订单对接 同步到策方
                $order['status'] = 4;
                \app\custom\Cefang::api($order);
            }
            if(getcustom('member_gongxian')){
                //扣除贡献值
                $admin = Db::name('admin')->where('id',aid)->find();
                $set = Db::name('admin_set')->where('aid',aid)->find();
                $gongxian_days = $set['gongxian_days'];
                //等级设置优先
                $level_gongxian_days = Db::name('member_level')->where('aid',aid)->where('id',$order['mid'])->value('gongxian_days');
                if($level_gongxian_days > 0){
                    $gongxian_days = $level_gongxian_days;
                }
                if($admin['member_gongxian_status'] == 1 && $set['gongxianin_money'] > 0 && $set['gognxianin_value'] > 0 && ((time()-$order['paytime'])/86400 <= $gongxian_days)){
                    $log = Db::name('member_gongxianlog')->where('aid',aid)->where('mid',$order['mid'])->where('channel','shop')->where('orderid',$order['id'])->find();
                    if($log){
                        $givevalue = $log['value']*-1;
                        Db::name('member_gongxianlog')->where('aid',aid)->where('id',$log['id'])->update(['is_expire'=>2]);
                        \app\common\Member::addgongxian(aid,$order['mid'],$givevalue,'退款扣除'.t('贡献'),'shop_refund',$order['id']);
                    }
                }
            }
            if(getcustom('product_bonus_pool')){
                foreach ($prolist as $key=>$val){
                    $recordlist = Db::name('member_bonus_pool_record')->where('ogid',$val['id'])->select()->toArray();
                    foreach ($recordlist as $rkey=>$rval){
                        //修改记录
                        Db::name('member_bonus_pool_record')->where('id',$rval['id'])->update(['status' => 2]);
                        //修改奖金池
                        Db::name('bonus_pool')->where('id',$rval['bpid'])->update(['status' => 0,'mid' => 0]);
                    }
                }
            }
            if(getcustom('erp_wangdiantong')){
                $reog = Db::name('shop_refund_order_goods')->where('refund_orderid', $refund_id)->where('wdt_status',1)->select();
                if($reog) {
                    $c = new \app\custom\Wdt(aid, bid);
                    $data['id'] = $refund_id;
                    $c->orderRefund($order, $data, $reog);
                }
            }
            \app\common\Order::order_close_done(aid,$order['id'],'shop');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->json(['status'=>0,'msg'=>'提交失败,请重试']);
        }

        $refund_money = $data['refund_money'];
        //退款成功通知
        $tmplcontent = [];
        $tmplcontent['first'] = '您的订单已经完成退款，¥'.$refund_money.'已经退回您的付款账户，请留意查收。';
        $tmplcontent['remark'] = $reason.'，请点击查看详情~';
        $tmplcontent['orderProductPrice'] = $refund_money.'元';
        $tmplcontent['orderProductName'] = $order['title'];
        $tmplcontent['orderName'] = $order['ordernum'];
        $tmplcontentNew = [];
        $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
        $tmplcontentNew['thing2'] = $order['title'];//商品名称
        $tmplcontentNew['amount3'] = $refund_money;//退款金额
        \app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
        //订阅消息
        $tmplcontent = [];
        $tmplcontent['amount6'] = $refund_money;
        $tmplcontent['thing3'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];

        $tmplcontentnew = [];
        $tmplcontentnew['amount3'] = $refund_money;
        $tmplcontentnew['thing6'] = $order['title'];
        $tmplcontentnew['character_string4'] = $order['ordernum'];
        \app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

        //短信通知
        $member = Db::name('member')->where('id',$order['mid'])->find();
        if($member['tel']){
            $tel = $member['tel'];
        }else{
            $tel = $order['tel'];
        }
        $rs = \app\common\Sms::send(aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$refund_money]);

        \app\common\System::plog('手机端后台商城订单退款'.$orderid);
        return $this->json(['status'=>1,'msg'=>'已退款成功']);
    }
    //订单修改
    public function orderUpdate(){
        $shoporder_update = getcustom('shoporder_update');
        if ($shoporder_update) {
            $orderid = input('param.id/d');

            $cwhere = [];
            $cwhere[] = ['aid', '=', aid];

            $info = Db::name('shop_order')->where($cwhere)->where('id', $orderid)->find();
            if (!$info) return $this->json(['status' => 0, 'msg' => '订单不存在']);
            $order_goods = Db::name('shop_order_goods')->where($cwhere)->where('orderid', $orderid)->select()->toArray();
            foreach ($order_goods as $k => $v) {
                $order_goods[$k]['lvprice'] = Db::name('shop_product')->where('id', $v['proid'])->value('lvprice'); //是否开启会员价
            }
            $member = Db::name('member')->where('id', $info['mid'])->find();
            $userlevel = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
            if ($userlevel && $userlevel['discount'] > 0 && $userlevel['discount'] < 10) {
                $discount = $userlevel['discount'] * 0.1; //会员折扣
            } else {
                $discount = 1;
            }
            $price_update_status = $info['status'] == 0 ? 1 : 0;
            if (getcustom('order_update_price_anystatus')) {
                $price_update_status = 1;
            }

            $calType = 0;//计算类型
            if (getcustom('product_weight') && $info['product_type'] == 2) {
                //计重商品，价格为单价/kg,总价为单价*重量
                $calType = 2;
            }

            if (request()->isPost()) {
                if (getcustom('supply_zhenxin') || getcustom('supply_yongsheng')) {
                    if ($info['issource'] == 1 && ($info['source'] == 'supply_zhenxin' || $info['source'] == 'supply_yongsheng')) {
                        if ($info['source'] == 'supply_zhenxin') {
                            return $this->json(['status' => 0, 'msg' => '甄新汇选订单不支持修改']);
                        } else {
                            return $this->json(['status' => 0, 'msg' => '该订单不支持修改']);
                        }
                    }
                }
                $order = input('post.order');
                $goods_weight = input('post.goods_weight/a', []);
                $prodataarr = input('post.prodata/a', []);
                $newpro = input('param.newpro');
                $buydatastr = input('param.buydatastr');

                $postinfo = [];
                $postinfo['freight_price'] = input('post.freightprice') ?? 0;
                $postinfo['linkman'] = input('post.linkman') ?? '';
                $postinfo['tel'] = input('post.tel') ?? 0;
                $postinfo['address'] = input('post.address') ?? '';
                $postinfo['remark'] = input('post.remark') ?? '';
                $postinfo['totalprice'] = input('post.totalprice') ?? 0;
                $product_price = $postinfo['product_price'] = input('post.goodsprice') ?? 0;
                $postinfo['leveldk_money'] = input('post.leveldk_money') ?? 0;

                if (input('post.province') && input('post.city') && input('post.district')) {
                    $postinfo['area'] = input('post.province') . input('post.city') . input('post.district');
                    $postinfo['area2'] = input('post.province') . ',' . input('post.city') . ',' . input('post.district');
                }

//                if(!$postinfo['linkman']){
//                    return $this->json(['status' => 0, 'msg' => '请输入联系人']);
//                }
//
//                if(!$postinfo['tel']){
//                    return $this->json(['status' => 0, 'msg' => '请输入联系电话']);
//                }
//
//                if($info['freight_type'] != 1 && !$postinfo['address']){
//                    return $this->json(['status' => 0, 'msg' => '请输入详细地址']);
//                }

                if(!$prodataarr || !$buydatastr){
                    return $this->json(['status' => 0, 'msg' => '商品不能为空']);
                }

                Db::startTrans();
                try {

                    if ($price_update_status) {
                        Db::name('shop_order')->where('id', $orderid)->update($postinfo);
                    } else {
                        Db::rollback();
                        return $this->json(['status' => 0, 'msg' => '当前订单状态暂不支持修改']);
                    }
                    $order = Db::name('shop_order')->where('id', $orderid)->find();
                    $sysset = Db::name('admin_set')->where('aid', aid)->find();
                    $mid = $order['mid'];
                    //是否是复购
                    $hasordergoods = Db::name('shop_order_goods')->where('aid', aid)->where('mid', $mid)->where('createtime', '<', $order['createtime'])->where('status', 'in', '1,2,3')->find();
                    if ($hasordergoods) {
                        $isfg = 1;
                    } else {
                        $isfg = 0;
                    }

                    $rate = bcdiv($postinfo['totalprice'], $info['totalprice'], 2);

                    $remark_arr = $ggid = $goods_num = $goods_sell_price = $goods_name = $goods_ggname = $goods_id = [];
                    if($prodataarr){
                        foreach ($prodataarr as $key => $prodata){
                            $goods_id[] = $prodata['guige']['id'];
                            $goods_name[] = $prodata['guige']['name'];
                            $goods_sell_price[] = $prodata['guige']['sell_price'];
                            $goods_num[] = $prodata['num'];
                            $goods_ggname[] = $prodata['guige']['ggname'];
                            $ggid[] = $prodata['guige']['ggid'];
                            $remark_arr[] = $prodata['guige']['remark'];
                        }
                    }

                    if ($newpro == 1) {

                        $prodata = explode('-', $buydatastr);

                        $givescore = 0; //奖励积分 确认收货后赠送
                        $givescore2 = 0; //奖励积分2 付款后赠送
                        $prolist = [];
                        foreach ($prodata as $key => $pro) {
                            $sdata = explode(',', $pro);
                            $product = Db::name('shop_product')->where('aid', aid)->where('id', $sdata[0])->find();
                            if (!$product){
                                Db::rollback();
                                return $this->json(['status' => 0, 'msg' => '产品不存在或已下架']);
                            }
                            $guige = Db::name('shop_guige')->where('aid', aid)->where('id', $sdata[1])->find();
                            if (!$guige) {
                                Db::rollback();
                                return $this->json(['status' => 0, 'msg' => '产品规格不存在或已下架']);
                            }
                            if ($guige['stock'] < $sdata[2]) {
                                Db::rollback();
                                return $this->json(['status' => 0, 'msg' => $product['name'] . $guige['name'] . '库存不足']);
                            }
                            if ($product['lvprice'] == 1 && $member) {
                                $lvprice_data = json_decode($guige['lvprice_data'], true);
                                if ($lvprice_data)
                                    $guige['sell_price'] = $lvprice_data[$member['levelid']];
                            }

                            if ($key == 0) $title = $product['name'];

                            $prolist[] = ['product' => $product, 'guige' => $guige, 'num' => $sdata[2]];
                        }


                        //先删除之前规格
                        if ($old_goods = Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $orderid)->select()) {
                            foreach ($old_goods as $vg) {
                                $oldnum = $vg['num'];
                                Db::name('shop_guige')->where('aid', aid)->where('id', $vg['ggid'])->update(['stock' => Db::raw("stock+$oldnum"), 'sales' => Db::raw("sales-$oldnum")]);
                                Db::name('shop_product')->where('aid', aid)->where('id', $vg['proid'])->update(['stock' => Db::raw("stock+$oldnum"), 'sales' => Db::raw("sales-$oldnum")]);

                                Db::name('shop_order_goods')->where('aid', aid)->where('id', $vg['id'])->delete();

                                Db::name('member_commission_record')->where(['aid' => aid, 'orderid' => $orderid, 'ogid' => $vg['id'], 'type' => 'shop'])->delete();
                            }
                        }


                        $istc = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
                        $istc1 = 0;
                        $istc2 = 0;
                        $istc3 = 0;
                        foreach ($prolist as $key => $v) {
                            $product = $v['product'];
                            $guige = $v['guige'];
                            $num = $goods_num[$key];
                            $guige['sell_price'] = $goods_sell_price[$key];
                            if (getcustom('product_wholesale') && $product['product_type'] == 4) {
                                $guigedata = json_decode($product['guigedata'], true);
                                $gg_name_arr = explode(',', $guige['name']);
                                foreach ($guigedata as $pk => $pg) {
                                    foreach ($pg['items'] as $pgt) {
                                        if (isset($pgt['ggpic_wholesale']) && !empty($pgt['ggpic_wholesale'])) {
                                            if (in_array($pgt['title'], $gg_name_arr)) {
                                                $guige['pic'] = $pgt['ggpic_wholesale'];
                                            }
                                        }
                                    }
                                }
                            }
                            $ogdata = [];
                            $ogdata['aid'] = aid;
                            $ogdata['bid'] = $product['bid'];
                            $ogdata['mid'] = $order['mid'];
                            $ogdata['orderid'] = $orderid;
                            $ogdata['ordernum'] = $order['ordernum'];
                            $ogdata['proid'] = $product['id'];
                            $ogdata['name'] = $goods_name[$key];
                            $ogdata['pic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
                            $ogdata['procode'] = $product['procode'];
                            $ogdata['barcode'] = $product['barcode'];
                            $ogdata['ggid'] = $guige['id'];
                            $ogdata['ggname'] = $goods_ggname[$key];
                            $ogdata['cid'] = $product['cid'];
                            $ogdata['num'] = $num;
                            $ogdata['cost_price'] = $guige['cost_price'];
                            $ogdata['sell_price'] = $guige['sell_price'];
                            $ogdata['totalprice'] = $num * $guige['sell_price'];
                            $ogdata['status'] = 1;
                            $ogdata['remark'] = $remark_arr[$key] ?? '';
                            $ogdata['createtime'] = time();
                            if ($product['fenhongset'] == 0) { //不参与分红
                                $ogdata['isfenhong'] = 2;
                            }

                            $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                            if ($istc != 1) {
                                $og_totalprice = $ogdata['totalprice'];
                                $leveldk_money = 0;
                                $coupon_money = 0;
                                $scoredk_money = 0;
                                $manjian_money = $order['product_price'] + $order['freight_price'] - $order['totalprice'];

                                //计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                                if ($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2) {
                                    $allproduct_price = $order['product_price'];
                                    $og_leveldk_money = 0;
                                    $og_coupon_money = 0;
                                    $og_scoredk_money = 0;
                                    $og_manjian_money = 0;
                                    if ($allproduct_price > 0 && $og_totalprice > 0) {
                                        if ($leveldk_money) {
                                            $og_leveldk_money = $og_totalprice / $allproduct_price * $leveldk_money;
                                        }
                                        if ($coupon_money) {
                                            $og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
                                        }
                                        if ($scoredk_money) {
                                            $og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
                                        }
                                        if ($manjian_money) {
                                            $og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
                                        }
                                    }
                                    $og_totalprice = round($og_totalprice - $og_coupon_money - $og_scoredk_money - $og_manjian_money, 2);
                                    if ($og_totalprice < 0) $og_totalprice = 0;
                                }
                                $ogdata['real_totalprice'] = $og_totalprice; //实际商品销售金额

                                //计算佣金的商品金额
                                $commission_totalprice = $ogdata['totalprice'];
                                if ($sysset['fxjiesuantype'] == 1) {
                                    $commission_totalprice = $ogdata['real_totalprice'];
                                }
                                if ($sysset['fxjiesuantype'] == 2) { //按利润提成
                                    $commission_totalprice = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                                }
                                if ($commission_totalprice < 0) $commission_totalprice = 0;
                                $commission_totalpriceCache = $commission_totalprice;

                                //平级奖计算金额
                                $commission_totalprice_pj = 0;//
                                if (getcustom('commission_parent_pj_jiesuantype')) {
                                    if ($sysset['fxjiesuantype_pj'] == 1) { //按商品价格
                                        $commission_totalprice_pj = $ogdata['totalprice'];
                                    }
                                    if ($sysset['fxjiesuantype_pj'] == 2) { //按成交价格
                                        $commission_totalprice_pj = $ogdata['real_totalprice'];
                                    }
                                    if ($sysset['fxjiesuantype_pj'] == 3) { //按销售利润
                                        $commission_totalprice_pj = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                                    }
                                    if ($commission_totalprice_pj < 0) {
                                        $commission_totalprice_pj = 0;
                                    }
                                }

                                $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                                if ($agleveldata['can_agent'] > 0 && $agleveldata['commission1own'] == 1) {
                                    $member['pid'] = $member['id'];
                                }
                                if ($product['commissionset'] != -1) {
                                    if (!getcustom('fenxiao_manage')) {
                                        $sysset['fenxiao_manage_status'] = 0;
                                    }
                                    if ($sysset['fenxiao_manage_status']) {
                                        $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset, $member, $product, $num, $commission_totalprice, $commission_totalprice_pj);
                                    } else {
                                        $commission_data = \app\common\Fenxiao::fenxiao($sysset, $member, $product, $num, $commission_totalprice, 0, $istc1, $istc2, $istc3, $commission_totalprice_pj);
                                    }
                                    $ogdata['parent1'] = $commission_data['parent1'] ?? 0;
                                    $ogdata['parent2'] = $commission_data['parent2'] ?? 0;
                                    $ogdata['parent3'] = $commission_data['parent3'] ?? 0;
                                    $ogdata['parent4'] = $commission_data['parent4'] ?? 0;
                                    $ogdata['parent1commission'] = $commission_data['parent1commission'] ?? 0;
                                    $ogdata['parent2commission'] = $commission_data['parent2commission'] ?? 0;
                                    $ogdata['parent3commission'] = $commission_data['parent3commission'] ?? 0;
                                    $ogdata['parent4commission'] = $commission_data['parent4commission'] ?? 0;
                                    $ogdata['parent1score'] = $commission_data['parent1score'] ?? 0;
                                    $ogdata['parent2score'] = $commission_data['parent2score'] ?? 0;
                                    $ogdata['parent3score'] = $commission_data['parent3score'] ?? 0;
                                    //20250626新增 平级奖独立记录
                                    if (getcustom('commission_parent_pj')) {
                                        $ogdata['parent_pj1'] = $commission_data['parent_pj1'] ?? 0;
                                        $ogdata['parent_pj2'] = $commission_data['parent_pj2'] ?? 0;
                                        $ogdata['parent_pj3'] = $commission_data['parent_pj3'] ?? 0;
                                        $ogdata['parent1commission_pj'] = $commission_data['parent1commission_pj'] ?? 0;
                                        $ogdata['parent2commission_pj'] = $commission_data['parent2commission_pj'] ?? 0;
                                        $ogdata['parent3commission_pj'] = $commission_data['parent3commission_pj'] ?? 0;
                                    }

                                    $istc1 = $commission_data['istc1'] ?? 0;
                                    $istc2 = $commission_data['istc2'] ?? 0;
                                    $istc3 = $commission_data['istc3'] ?? 0;
                                }
                            }
                            $ogid = Db::name('shop_order_goods')->insertGetId($ogdata);
                            if (getcustom('member_product_price')) {
                                if ($guige['is_member_product'] == 1) {
                                    $buylog = [
                                        'aid' => aid,
                                        'mid' => $member['id'],
                                        'ordernum' => $order['ordernum'],
                                        'type' => 'admin',
                                        'proid' => $ogdata['proid'],
                                        'ggid' => $ogdata['ggid'],
                                        'orderid' => $orderid,
                                        'sell_price' => $ogdata['sell_price'],
                                        'num' => $ogdata['num'],
                                        'createtime' => time()
                                    ];
                                    Db::name('member_product_buylog')->insert($buylog);
                                }
                            }
                            $totalcommission = 0;
                            if ($ogdata['parent1'] && ($ogdata['parent1commission'] > 0 || $ogdata['parent1score'] > 0)) {
                                Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent1'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent1commission'], 'score' => $ogdata['parent1score'], 'remark' => '下级购买商品奖励', 'createtime' => time()]);
                                $totalcommission += $ogdata['parent1commission'];
                            }
                            if ($ogdata['parent2'] && ($ogdata['parent2commission'] || $ogdata['parent2score'])) {
                                Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent2'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent2commission'], 'score' => $ogdata['parent2score'], 'remark' => '下二级购买商品奖励', 'createtime' => time()]);
                                $totalcommission += $ogdata['parent2commission'];
                            }
                            if ($ogdata['parent3'] && ($ogdata['parent3commission'] || $ogdata['parent3score'])) {
                                Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent3'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent3commission'], 'score' => $ogdata['parent3score'], 'remark' => '下三级购买商品奖励', 'createtime' => time()]);
                                $totalcommission += $ogdata['parent3commission'];
                            }
                            if (getcustom('commission_parent_pj')) {
                                if ($ogdata['parent_pj1'] && ($ogdata['parent1commission_pj'] > 0)) {
                                    $remark = '平级一级奖励';
                                    $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj1'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                    Db::name('member_commission_record')->insert($data_c);
                                    $totalcommission += $ogdata['parent1commission_pj'];
                                }
                                if ($ogdata['parent_pj2'] && ($ogdata['parent2commission_pj'] > 0)) {
                                    $remark = '平级二级奖励';
                                    $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj2'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                    Db::name('member_commission_record')->insert($data_c);
                                    $totalcommission += $ogdata['parent2commission_pj'];
                                }
                                if ($ogdata['parent_pj3'] && ($ogdata['parent3commission_pj'] > 0)) {
                                    $remark = '平级三级奖励';
                                    $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj3'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                    Db::name('member_commission_record')->insert($data_c);
                                    $totalcommission += $ogdata['parent1commission_pj'];
                                }
                            }
                            if (getcustom('product_glass')) {
                                $glass_record_id = $guige['glass_record_id'];
                                if ($glass_record_id) {
                                    $glassrecord = Db::name('glass_record')->where('aid', aid)->where('id', $glass_record_id)->find();
                                    if ($glassrecord) {
                                        $orderglassrecord = [
                                            'glass_record_id' => $glassrecord['id'],
                                            'name' => $glassrecord['name'],
                                            'desc' => $glassrecord['desc'],
                                            'degress_left' => $glassrecord['degress_left'],
                                            'degress_right' => $glassrecord['degress_right'],
                                            'ipd' => $glassrecord['ipd'],
                                            'ipd_left' => $glassrecord['ipd_left'],
                                            'ipd_right' => $glassrecord['ipd_right'],
                                            'double_ipd' => $glassrecord['double_ipd'],
                                            'correction_left' => $glassrecord['correction_left'],
                                            'correction_right' => $glassrecord['correction_right'],
                                            'is_ats' => $glassrecord['is_ats'],
                                            'ats_left' => $glassrecord['ats_left'],
                                            'ats_right' => $glassrecord['ats_right'],
                                            'ats_zright' => $glassrecord['ats_zright'],
                                            'ats_zleft' => $glassrecord['ats_zleft'],
                                            'add_right' => $glassrecord['add_right'],
                                            'add_left' => $glassrecord['add_left'],
                                            'check_time' => $glassrecord['check_time'],
                                            'nickname' => $glassrecord['nickname'],
                                            'sex' => $glassrecord['sex'],
                                            'age' => $glassrecord['age'],
                                            'tel' => $glassrecord['tel'],
                                            'remark' => $glassrecord['remark'],
                                            'type' => $glassrecord['type'],
                                            'mid' => $glassrecord['mid'],
                                            'createtime' => time(),
                                            'aid' => aid,
                                            'bid' => $ogdata['bid'] ?? 0,
                                            'order_goods_id' => $ogid
                                        ];
                                        $order_glass_record_id = Db::name('order_glass_record')->insertGetId($orderglassrecord);
                                        Db::name('shop_order_goods')->where('id', $ogid)->update(['glass_record_id' => $order_glass_record_id]);
                                    }
                                }
                            }

                            if ($product['commissionset4'] == 1 && $product['lvprice'] == 1) { //极差分销
                                if (getcustom('jicha_removecommission')) { //算极差时先减去分销的钱
                                    $commission_totalpriceCache = $commission_totalpriceCache - $totalcommission;
                                }
                                if ($member['path']) {
                                    $parentList = Db::name('member')->where('id', 'in', $member['path'])->order(Db::raw('field(id,' . $member['path'] . ')'))->select()->toArray();
                                    if ($parentList) {
                                        $parentList = array_reverse($parentList);
                                        $lvprice_data = json_decode($guige['lvprice_data'], true);
                                        $nowprice = $commission_totalpriceCache;
                                        $giveidx = 0;
                                        foreach ($parentList as $k => $parent) {
                                            if ($parent['levelid'] && $lvprice_data[$parent['levelid']]) {
                                                $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                                if ($nowprice > $thisprice) {
                                                    $commission = $nowprice - $thisprice;
                                                    $nowprice = $thisprice;
                                                    $giveidx++;
                                                    //if($giveidx <=3){
                                                    //	$ogupdate['parent'.$giveidx] = $parent['id'];
                                                    //	$ogupdate['parent'.$giveidx.'commission'] = $commission;
                                                    //}
                                                    Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent['id'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => '下级购买商品差价', 'createtime' => time()]);

                                                    //平级奖
                                                    if (getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')) {
                                                        if ($parentList[$k + 1] && $parentList[$k + 1]['levelid'] == $parent['levelid']) {
                                                            $parent2 = $parentList[$k + 1];
                                                            $parent2lv = Db::name('member_level')->where('id', $parent2['levelid'])->find();
                                                            $parent2lv['commissionpingjitype'] = $parent2lv['commissiontype'];
                                                            if ($product['commissionpingjiset'] != 0) {
                                                                if ($product['commissionpingjiset'] == 1) {
                                                                    $commissionpingjidata1 = json_decode($product['commissionpingjidata1'], true);
                                                                    $parent2lv['commission_parent_pj'] = $commissionpingjidata1[$parent2lv['id']]['commission'];
                                                                } elseif ($product['commissionpingjiset'] == 2) {
                                                                    $commissionpingjidata2 = json_decode($product['commissionpingjidata2'], true);
                                                                    $parent2lv['commission_parent_pj'] = $commissionpingjidata2[$parent2lv['id']]['commission'];
                                                                    $parent2lv['commissionpingjitype'] = 1;
                                                                } else {
                                                                    $parent2lv['commission_parent_pj'] = 0;
                                                                }
                                                            }
                                                            if ($parent2lv['commission_parent_pj'] > 0) {
                                                                if ($parent2lv['commissionpingjitype'] == 0) {
                                                                    $pingjicommission = $commission * $parent2lv['commission_parent_pj'] * 0.01;
                                                                } else {
                                                                    $pingjicommission = $parent2lv['commission_parent_pj'];
                                                                }
                                                                if ($pingjicommission > 0) {
                                                                    Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent2['id'], 'frommid' => $parent['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $pingjicommission, 'score' => 0, 'remark' => '平级奖励', 'createtime' => time()]);
                                                                }
                                                            }
                                                        }
                                                    }

                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            Db::name('shop_guige')->where('aid', aid)->where('id', $guige['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
                            Db::name('shop_product')->where('aid', aid)->where('id', $product['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
                        }

                    } else {
                        foreach ($goods_id as $k => $ogid) {
                            $oginfo = Db::name('shop_order_goods')->where('id', $ogid)->find();
                            $ogdata = [];
                            $ogdata['ggname'] = $goods_ggname[$k];
                            $ogdata['name'] = $goods_name[$k];
                            $ogdata['sell_price'] = $goods_sell_price[$k];
                            if ($calType == 2) {
                                $num = 1;
                                $gweight = $goods_weight[$k] * 1000;//化成kg
                                $ogdata['num'] = 1;
                                $ogdata['real_sell_price'] = $goods_sell_price[$k];
                                $ogdata['total_weight'] = $gweight;//化成kg
                                $ogdata['real_total_weight'] = $gweight;//化成kg
                                $ogdata['totalprice'] = round($ogdata['sell_price'] * $goods_weight[$k], 2);
                                $ogdata['real_totalprice'] = $ogdata['totalprice'];
                            } else {
                                $num = $goods_num[$k];
                                $ogdata['num'] = $goods_num[$k];
                                $ogdata['totalprice'] = $ogdata['sell_price'] * $ogdata['num'];
                            }
                            $product = Db::name('shop_product')->where('id', $oginfo['proid'])->find();
                            $og_totalprice = $ogdata['totalprice'];
                            if ($product['balance'] > 0) {
                                $og_totalprice = $og_totalprice * (1 - $product['balance'] * 0.01);
                            }

                            $allproduct_price = $product_price;
                            $og_leveldk_money = 0;
                            $og_coupon_money = 0;
                            $og_scoredk_money = 0;
                            $og_manjian_money = 0;
                            if (getcustom('money_dec')) {
                                $og_dec_money = 0;//余额抵扣比例
                            }
                            if ($allproduct_price > 0 && $og_totalprice > 0) {
                                if ($order['leveldk_money']) {
                                    $og_leveldk_money = $og_totalprice / $allproduct_price * $order['leveldk_money'];
                                }
                                if ($order['coupon_money']) {
                                    $og_coupon_money = $og_totalprice / $allproduct_price * $order['coupon_money'];
                                }
                                if ($order['scoredk_money']) {
                                    $og_scoredk_money = $og_totalprice / $allproduct_price * $order['scoredk_money'];
                                }
                                if ($order['manjian_money']) {
                                    $og_manjian_money = $og_totalprice / $allproduct_price * $order['manjian_money'];
                                }
                                if (getcustom('money_dec')) {
                                    if ($order['dec_money']) {
                                        $og_dec_money = $og_totalprice / $allproduct_price * $order['dec_money'];
                                    }
                                }
                            }
                            $ogdata['scoredk_money'] = $og_scoredk_money;
                            $ogdata['leveldk_money'] = $og_leveldk_money;
                            $ogdata['manjian_money'] = $og_manjian_money;
                            $ogdata['coupon_money'] = $og_coupon_money;
                            if (getcustom('money_dec')) {
                                $ogdata['dec_money'] = $og_dec_money;//余额抵扣比例
                            }
                            if ($shoporder_update) {
                                $ogdata['remark'] = $remark_arr[$k] ?? '';
                            }

                            if (bid && bid > 0) {
                                $store_info = Db::name('business')->where('aid', aid)->where('id', bid)->find();
                            } else {
                                $store_info = Db::name('admin_set')->where('aid', aid)->find();
                            }

                            if ($product['bid'] > 0) {
                                $totalprice_business = $og_totalprice - $og_manjian_money - $og_coupon_money;
                                //商品独立费率
                                if ($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                                    $ogdata['business_total_money'] = $totalprice_business * (100 - $product['feepercent']) * 0.01;
                                    if (getcustom('business_deduct_cost')) {
                                        if ($store_info && $store_info['deduct_cost'] == 1 && $oginfo['cost_price'] > 0) {
                                            if ($oginfo['cost_price'] <= $ogdata['sell_price']) {
                                                $all_cost_price = $oginfo['cost_price'];
                                            } else {
                                                $all_cost_price = $ogdata['sell_price'];
                                            }
                                            //扣除成本
                                            $ogdata['business_total_money'] = $totalprice_business - ($totalprice_business - $all_cost_price) * $product['feepercent'] / 100;
                                        }
                                    }
                                    if (getcustom('business_fee_type')) {
                                        $bset = Db::name('business_sysset')->where('aid', aid)->find();
                                        if ($bset['business_fee_type'] == 1) {
                                            $platformMoney = $totalprice_business * $product['feepercent'] * 0.01;
                                            $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                        } elseif ($bset['business_fee_type'] == 2) {
                                            $platformMoney = $oginfo['cost_price'] * $product['feepercent'] * 0.01;
                                            $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                        }
                                    }
                                } else {
                                    //商户费率
                                    $ogdata['business_total_money'] = $totalprice_business * (100 - $store_info['feepercent']) * 0.01;
                                    if (getcustom('business_deduct_cost')) {
                                        if ($store_info && $store_info['deduct_cost'] == 1 && $oginfo['cost_price'] > 0) {
                                            if ($oginfo['cost_price'] <= $ogdata['sell_price']) {
                                                $all_cost_price = $oginfo['cost_price'];
                                            } else {
                                                $all_cost_price = $ogdata['sell_price'];
                                            }
                                            //扣除成本
                                            $ogdata['business_total_money'] = $totalprice_business - ($totalprice_business - $all_cost_price) * $store_info['feepercent'] / 100;
                                        }
                                    }
                                    if (getcustom('business_fee_type')) {
                                        $bset = Db::name('business_sysset')->where('aid', aid)->find();
                                        if ($bset['business_fee_type'] == 1) {
                                            $platformMoney = $totalprice_business * $store_info['feepercent'] * 0.01;
                                            $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                        } elseif ($bset['business_fee_type'] == 2) {
                                            $platformMoney = $oginfo['cost_price'] * $store_info['feepercent'] * 0.01;
                                            $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                        }
                                    }
                                }
                            }

                            //计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                            if ($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2) {
                                $og_totalprice = $og_totalprice - $og_leveldk_money - $og_scoredk_money - $og_manjian_money;
                                if ($couponrecord['type'] != 4) {//运费抵扣券
                                    $og_totalprice -= $og_coupon_money;
                                }
                                $og_totalprice = round($og_totalprice, 2);
                                if ($og_totalprice < 0) $og_totalprice = 0;
                            }
                            if (getcustom('money_dec')) {
                                $og_totalprice -= $og_dec_money;//余额抵扣比例
                            }
                            $ogdata['real_totalprice'] = $og_totalprice; //实际商品销售金额
                            $guige = Db::name('shop_guige')->where('id', $oginfo['ggid'])->lock(true)->find();
                            //计算佣金的商品金额
                            $commission_totalprice = $ogdata['totalprice'];
                            if ($sysset['fxjiesuantype'] == 1) { //按成交价格
                                $commission_totalprice = $ogdata['real_totalprice'];
                                if ($commission_totalprice < 0) $commission_totalprice = 0;
                            }
                            $commission_totalpriceCache = $commission_totalprice;
                            if ($sysset['fxjiesuantype'] == 2) { //按销售利润
                                $commission_totalprice = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                                if ($commission_totalprice < 0) $commission_totalprice = 0;
                            }
                            if (getcustom('pay_yuanbao')) {
                                $ogdata['yuanbao'] = $product['yuanbao'];
                                $ogdata['total_yuanbao'] = $num * $product['yuanbao'];
                            }
                            $ogdata['real_totalmoney'] = $rate * ($oginfo['real_totalmoney'] ?? 0);
                            $ogdata['real_totalmoney']  =  $ogdata['real_totalmoney'] ?? 0;

                            Db::name('shop_order_goods')->where('id', $ogid)->update($ogdata);

                            $hasff = Db::name('member_commission_record')->where('aid', aid)->where('orderid', $orderid)->where('ogid', $ogid)->where('type', 'shop')->where('status', 1)->find();
                            //佣金没有发放 重新计算佣金
                            if (!$hasff) {
                                Db::name('member_commission_record')->where('aid', aid)->where('orderid', $orderid)->where('ogid', $ogid)->where('type', 'shop')->delete();
                                $ogupdate = [];
                                $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                                if ($agleveldata['can_agent'] > 0 && $agleveldata['commission1own'] == 1) {
                                    $member['pid'] = $mid;
                                }
                                if ($product['commissionset'] != -1) {
                                    if ($member['pid']) {
                                        $parent1 = Db::name('member')->where('aid', aid)->where('id', $member['pid'])->find();
                                        if ($parent1) {
                                            $agleveldata1 = Db::name('member_level')->where('aid', aid)->where('id', $parent1['levelid'])->find();
                                            if ($agleveldata1['can_agent'] != 0) {
                                                $ogupdate['parent1'] = $parent1['id'];
                                            }
                                        }
                                    }
                                    if ($parent1['pid']) {
                                        $parent2 = Db::name('member')->where('aid', aid)->where('id', $parent1['pid'])->find();
                                        if ($parent2) {
                                            $agleveldata2 = Db::name('member_level')->where('aid', aid)->where('id', $parent2['levelid'])->find();
                                            if ($agleveldata2['can_agent'] > 1) {
                                                $ogupdate['parent2'] = $parent2['id'];
                                            }
                                        }
                                    }
                                    if ($parent2['pid']) {
                                        $parent3 = Db::name('member')->where('aid', aid)->where('id', $parent2['pid'])->find();
                                        if ($parent3) {
                                            $agleveldata3 = Db::name('member_level')->where('aid', aid)->where('id', $parent3['levelid'])->find();
                                            if ($agleveldata3['can_agent'] > 2) {
                                                $ogupdate['parent3'] = $parent3['id'];
                                            }
                                        }
                                    }
                                    if ($parent3['pid']) {
                                        $parent4 = Db::name('member')->where('aid', aid)->where('id', $parent3['pid'])->find();
                                        if ($parent4) {
                                            $agleveldata4 = Db::name('member_level')->where('aid', aid)->where('id', $parent4['levelid'])->find();
                                            //持续推荐奖励
                                            if ($agleveldata4['can_agent'] > 0 && ($agleveldata4['commission_parent'] > 0 || ($parent4['levelid'] == $parent3['levelid'] && $agleveldata4['commission_parent_pj'] > 0))) {
                                                $ogupdate['parent4'] = $parent4['id'];
                                            }
                                        }
                                    }
                                    if ($product['commissionset'] == 1) {//按商品设置的分销比例
                                        $commissiondata = json_decode($product['commissiondata1'], true);
                                        if ($commissiondata) {
                                            if ($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                                            if ($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                                            if ($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                                        }
                                    } elseif ($product['commissionset'] == 2) {//按固定金额
                                        $commissiondata = json_decode($product['commissiondata2'], true);
                                        if ($commissiondata) {
                                            if (getcustom('fengdanjiangli') && $product['fengdanjiangli']) {

                                            } else {
                                                if ($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                                                if ($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                                                if ($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                                            }
                                        }
                                    } elseif ($product['commissionset'] == 3) {//提成是积分
                                        $commissiondata = json_decode($product['commissiondata3'], true);
                                        if ($commissiondata) {
                                            if ($agleveldata1) $ogupdate['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                                            if ($agleveldata2) $ogupdate['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                                            if ($agleveldata3) $ogupdate['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                                        }
                                    } else { //按会员等级设置的分销比例
                                        if ($agleveldata1) {
                                            if (getcustom('commission_fugou') && $isfg == 1) {
                                                $agleveldata1['commission1'] = $agleveldata1['commission4'];
                                            }
                                            if ($agleveldata1['commissiontype'] == 1) { //固定金额按单
                                                if ($istc1 == 0) {
                                                    $ogupdate['parent1commission'] = $agleveldata1['commission1'];
                                                    $istc1 = 1;
                                                }
                                            } else {
                                                $ogupdate['parent1commission'] = $agleveldata1['commission1'] * $commission_totalprice * 0.01;
                                            }
                                        }
                                        if ($agleveldata2) {
                                            if (getcustom('commission_fugou') && $isfg == 1) {
                                                $agleveldata2['commission2'] = $agleveldata2['commission5'];
                                            }
                                            if ($agleveldata2['commissiontype'] == 1) {
                                                if ($istc2 == 0) {
                                                    $ogupdate['parent2commission'] = $agleveldata2['commission2'];
                                                    $istc2 = 1;
                                                    //持续推荐奖励
                                                    if ($agleveldata2['commission_parent'] > 0) {
                                                        $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $agleveldata2['commission_parent'];
                                                    }
                                                    if ($agleveldata1['id'] == $agleveldata2['id'] && $agleveldata2['commission_parent_pj'] > 0) {
                                                        $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $agleveldata2['commission_parent_pj'];
                                                    }
                                                }
                                            } else {
                                                $ogupdate['parent2commission'] = $agleveldata2['commission2'] * $commission_totalprice * 0.01;
                                                //持续推荐奖励
                                                if ($agleveldata2['commission_parent'] > 0 && $ogupdate['parent1commission'] > 0) {
                                                    $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $ogupdate['parent1commission'] * $agleveldata2['commission_parent'] * 0.01;
                                                }
                                                if ($agleveldata1['id'] == $agleveldata2['id'] && $agleveldata2['commission_parent_pj'] > 0 && $ogupdate['parent1commission'] > 0) {
                                                    $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $ogupdate['parent1commission'] * $agleveldata2['commission_parent_pj'] * 0.01;
                                                }
                                            }
                                        }
                                        if ($agleveldata3) {
                                            if (getcustom('commission_fugou') && $isfg == 1) {
                                                $agleveldata3['commission3'] = $agleveldata3['commission6'];
                                            }
                                            if ($agleveldata3['commissiontype'] == 1) {
                                                if ($istc3 == 0) {
                                                    $ogupdate['parent3commission'] = $agleveldata3['commission3'];
                                                    $istc3 = 1;
                                                    //持续推荐奖励
                                                    if ($agleveldata3['commission_parent'] > 0) {
                                                        $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $agleveldata3['commission_parent'];
                                                    }
                                                    if ($agleveldata2['id'] == $agleveldata3['id'] && $agleveldata3['commission_parent_pj'] > 0) {
                                                        $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $agleveldata3['commission_parent_pj'];
                                                    }
                                                }
                                            } else {
                                                $ogupdate['parent3commission'] = $agleveldata3['commission3'] * $commission_totalprice * 0.01;
                                                //持续推荐奖励
                                                if ($agleveldata3['commission_parent'] > 0 && $ogupdate['parent2commission'] > 0) {
                                                    $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $ogupdate['parent2commission'] * $agleveldata3['commission_parent'] * 0.01;
                                                }
                                                if ($agleveldata2['id'] == $agleveldata3['id'] && $agleveldata3['commission_parent_pj'] > 0 && $ogupdate['parent2commission'] > 0) {
                                                    $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $ogupdate['parent2commission'] * $agleveldata3['commission_parent_pj'] * 0.01;
                                                }
                                            }
                                        }
                                        //持续推荐奖励
                                        if ($agleveldata4['commission_parent'] > 0) {
                                            if ($agleveldata3['commissiontype'] == 1) {
                                                $ogupdate['parent4commission'] = $agleveldata4['commission_parent'];
                                            } else {
                                                $ogupdate['parent4commission'] = $ogupdate['parent3commission'] * $agleveldata4['commission_parent'] * 0.01;
                                            }
                                        }
                                        if ($agleveldata3['id'] == $agleveldata4['id'] && $agleveldata4['commission_parent_pj'] > 0) {
                                            if ($agleveldata3['commissiontype'] == 1) {
                                                $ogupdate['parent4commission'] = $agleveldata4['commission_parent_pj'];
                                            } else {
                                                $ogupdate['parent4commission'] = $ogupdate['parent3commission'] * $agleveldata4['commission_parent_pj'] * 0.01;
                                            }
                                        }
                                    }
                                }
                                if ($ogupdate) {
                                    Db::name('shop_order_goods')->where('id', $ogid)->update($ogupdate);
                                }

                                if ($product['commissionset4'] == 1 && $product['lvprice'] == 1) { //极差分销
                                    if ($member['path']) {
                                        $parentList = Db::name('member')->where('id', 'in', $member['path'])->order(Db::raw('field(id,' . $member['path'] . ')'))->select()->toArray();
                                        if ($parentList) {
                                            $parentList = array_reverse($parentList);
                                            $lvprice_data = json_decode($guige['lvprice_data'], true);
                                            $nowprice = $commission_totalpriceCache;
                                            $giveidx = 0;
                                            foreach ($parentList as $k => $parent) {
                                                if ($parent['levelid'] && $lvprice_data[$parent['levelid']]) {
                                                    $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                                    if ($nowprice > $thisprice) {
                                                        $commission = $nowprice - $thisprice;
                                                        $nowprice = $thisprice;
                                                        $giveidx++;
                                                        //if($giveidx <=3){
                                                        //	$ogupdate['parent'.$giveidx] = $parent['id'];
                                                        //	$ogupdate['parent'.$giveidx.'commission'] = $commission;
                                                        //}
                                                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent['id'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => '下级购买商品差价', 'createtime' => time()]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($product['commissionset'] != 4) {
                                    if (getcustom('commission_fugou') && $isfg == 1) {
                                        if ($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent1'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission'], 'score' => $ogupdate['parent1score'], 'remark' => '下级复购奖励', 'createtime' => time()]);
                                        }
                                        if ($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent2'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission'], 'score' => $ogupdate['parent2score'], 'remark' => '下二级复购奖励', 'createtime' => time()]);
                                        }
                                        if ($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent3'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission'], 'score' => $ogupdate['parent3score'], 'remark' => '下三级复购奖励', 'createtime' => time()]);
                                        }
                                    } else {
                                        if ($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent1'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission'], 'score' => $ogupdate['parent1score'], 'remark' => '下级购买商品奖励', 'createtime' => time()]);
                                        }
                                        if ($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent2'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission'], 'score' => $ogupdate['parent2score'], 'remark' => '下二级购买商品奖励', 'createtime' => time()]);
                                        }
                                        if ($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent3'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission'], 'score' => $ogupdate['parent3score'], 'remark' => '下三级购买商品奖励', 'createtime' => time()]);
                                        }
                                        if ($ogupdate['parent4'] && ($ogupdate['parent4commission'])) {
                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogupdate['parent4'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent4commission'], 'score' => 0, 'remark' => '持续推荐奖励', 'createtime' => time()]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $newordernum = date('ymdHis') . rand(100000, 999999);
                    $ordernumArr = explode('_', $info['ordernum']);
                    if ($ordernumArr[1]) $newordernum .= '_' . $ordernumArr[1];
                    Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->update(['ordernum' => $newordernum]);
                    Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $orderid)->update(['ordernum' => $newordernum]);
                    $payorderid = Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->value('payorderid');
                    \app\model\Payorder::updateorder($payorderid, $newordernum, $postinfo['totalprice'], $orderid);
                    \app\common\System::plog('移动端商城订单编辑' . $orderid);
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $this->json(['status' => 0, 'msg' => $e->getMessage() ?? '修改失败']);
                }

                return $this->json(['status' => 1, 'msg' => '修改成功']);
            }
        }
    }
    //送货单图片
    public function shdImgNew()
    {
        if (getcustom('shoporder_shdimg_mobile')) {
            $post = input('post.');
            $order = Db::name('shop_order')->where('aid', aid)->where('id', $post['id'])->find();
            if (!$order) {
                return $this->json(['status' => 0, 'msg' => '订单不存在']);
            }
            $order_goods = Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $order['id'])->select()->toArray();
            $aid = $order['aid'];

            $newdate = [];
            $zhk = 0;
            if ($order_goods) {
                foreach ($order_goods as $k => $v) {
                    $k1 = $k + 1;
                    $newdate[] = [
                        'id' => $k1,
                        'product' => $v['name'].' '.$v['ggname'],
                        'quantity' => $v['num'] ?? '',
                        'price' => $v['sell_price'] ? '￥'.$v['sell_price'] : '',
                        'amount' => $v['totalprice'] ? '￥'.$v['totalprice'] : '',
                        'remark' => $v['remark'] ?? '',
                    ];
                    $zhk = $k1;
                }
            }

//            $newdate[] = [
//                'id' => $zhk + 1,
//                'product' => '运费',
//                'quantity' => '',
//                'price' => '',
//                'amount' => $order['freight_price'] ? '￥'.$order['freight_price'] : '',
//                'remark' => '',
//            ];

            $rdata = [];
            $rdata['data'] = $newdate;
            $rdata['totalAmount'] = $order['totalprice'] ? '￥'.$order['totalprice'] : '';
            $rdata['totalAmountChinese'] = num_to_rmb($order['totalprice']);
            $rdata['productAmount'] = $order['product_price'] ? '￥'.$order['product_price'] : '';
            $rdata['productAmountChinese'] = num_to_rmb($order['product_price']);
            $rdata['status'] = 1;
            return $this->json($rdata);

        }
    }
    public function shdImg(){
        $mendian_apply = getcustom('mendian_apply');
        if(getcustom('shoporder_shdimg_mobile')){
            $post = input('post.');
            $order = Db::name('shop_order')->where('aid',aid)->where('id',$post['id'])->find();
            if(!$order){
                return $this->json(['status'=>0,'msg'=>'订单不存在']);
            }
            $order_goods = Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$order['id'])->select()->toArray();
            $aid = $order['aid'];

            $guige = '';
            if($order_goods){
                foreach ($order_goods as $k=>$v){
                    $re = '';
                    $k1 = $k+1;
                    if($v['remark']){
                        $re = '，(备注：'.$v['remark'].')';
                    }
                    $guige .= $k1.'、【'.$v['name'].' '.$v['ggname'].'】数量:'.$v['num'].'，单价:￥'.$v['sell_price'].'，金额:￥'.$v['totalprice'].$re."\n";
                }
            }

            $khdz = '无';
            if($order['area'] || $order['address'] || $order['area2']){
                if($order['area']){
                    $khdz = $order['area'] ?? '';
                }else{
                    $khdz = $order['area2'] ?? '';
                }
                if($order['address']){
                    $khdz .= $order['address'] ?? '';
                }
            }

            $textReplaceArr = [
                '[订单编号]'=>$order['ordernum'],
                '[送货日期]'=>date('Y-m-d',time()),
                '[客户名称]'=>$order['linkman'],
                '[客户电话]'=>$order['tel'],
                '[客户地址]'=>$khdz,
                '[运费]'=>$order['freight_price'],
                '[合计金额]'=>'￥'.$order['totalprice'].'（运费：￥'.$order['freight_price'].'）',
                '[品名及规格]'=>$guige,
                '[买家留言]'=>$order['message'] ?? '无',
            ];

            set_time_limit(0);
            $posterdata = '{"poster_bg":"","poster_data":[{"left":"133px","top":"25px","type":"text","width":"68px","height":"24px","size":"22px","color":"#000","content":"送货单"},{"left":"6px","top":"50px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"订单编号："},{"left":"82px","top":"50px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"[订单编号]"},{"left":"6px","top":"131px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"客户名称："},{"left":"82px","top":"132px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"[客户名称]"},{"left":"6px","top":"152px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"客户电话："},{"left":"82px","top":"152px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"[客户电话]"},{"left":"6px","top":"173px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"客户地址："},{"left":"6px","top":"70px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"送货日期："},{"left":"82px","top":"70px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"[送货日期]"},{"left":"79px","top":"172px","type":"textarea","width":"254px","height":"37px","size":"14px","color":"#000","content":"[客户地址]"},{"left":"125px","top":"230px","type":"text","width":"92px","height":"20px","size":"18px","color":"#000","content":"品名及规格"},{"left":"14px","top":"254px","type":"textarea","width":"314px","height":"341px","size":"13px","color":"#000","content":"[品名及规格]"},{"left":"6px","top":"90px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"合计金额："},{"left":"82px","top":"90px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"[合计金额]"},{"left":"6px","top":"111px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"买家留言："},{"left":"82px","top":"111px","type":"text","width":"72px","height":"16px","size":"14px","color":"#000","content":"[买家留言]"}]}';

            $posterdata_md = '{"poster_bg":"","poster_data":[{"left":"133px","top":"25px","type":"text","width":"67px","height":"23px","size":"22px","color":"#000","content":"送货单"},{"left":"6px","top":"50px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"订单编号："},{"left":"82px","top":"50px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"[订单编号]"},{"left":"6px","top":"131px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"客户名称："},{"left":"82px","top":"132px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"[客户名称]"},{"left":"6px","top":"152px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"客户电话："},{"left":"82px","top":"152px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"[客户电话]"},{"left":"6px","top":"173px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"客户地址："},{"left":"6px","top":"70px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"送货日期："},{"left":"82px","top":"70px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"[送货日期]"},{"left":"79px","top":"172px","type":"textarea","width":"254px","height":"37px","size":"14px","color":"#000","content":"[客户地址]"},{"left":"126px","top":"255px","type":"text","width":"91px","height":"19px","size":"18px","color":"#000","content":"品名及规格"},{"left":"14px","top":"280px","type":"textarea","width":"315px","height":"317px","size":"13px","color":"#000","content":"[品名及规格]"},{"left":"6px","top":"90px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"合计金额："},{"left":"82px","top":"90px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"[合计金额]"},{"left":"6px","top":"111px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"买家留言："},{"left":"82px","top":"111px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"[买家留言]"},{"left":"6px","top":"215px","type":"text","width":"71px","height":"15px","size":"14px","color":"#000","content":"门店信息："},{"left":"80px","top":"212px","type":"textarea","width":"253px","height":"34px","size":"14px","color":"#000","content":"[门店信息]"}]}';

            if($mendian_apply){
                if($order['mdid']){
                    $mendian =  Db::name('mendian')->where('id',$order['mdid'])->find();
                    if($mendian){
                        $textReplaceArr['[门店信息]'] = $mendian['name'].'（地址：'. $mendian['address'].'）';
                        $posterdata = $posterdata_md;
                    }
                }
            }

            $posterdata = json_decode($posterdata,true);
            $poster_bg = $posterdata['poster_bg'];
            $poster_data = $posterdata['poster_data'];
            @ini_set('memory_limit', '256M');

            if(strpos($poster_bg,'http') ===false){
                $poster_bg = PRE_URL.$poster_bg;
            }
            $bg = imagecreatefromstring(request_get($poster_bg));
            if($bg){
                $bgwidth = imagesx($bg);
                $bgheight = imagesy($bg);
                if($bgheight/$bgwidth > 1.92) $bgheight = floor($bgwidth * 1.92);
                $target = imagecreatetruecolor($bgwidth, $bgheight);
                imagecopy($target, $bg, 0, 0, 0, 0,$bgwidth,$bgheight);
                imagedestroy($bg);
            }else{
                $bgwidth = 680;
                $bgheight = 1080;
                $target = imagecreatetruecolor(680, 1400);
                imagefill($target,0,0,imagecolorallocate($target, 255, 255, 255));
            }
            $huansuan = $bgwidth/340;
            //$bgwidth = imagesx($bg);
            //$bgheight = imagesy($bg);


            $font = ROOT_PATH."static/fonts/msyh.ttf";
            foreach ($poster_data as $d){

                $d['left'] = intval(str_replace('px', '', $d['left'])) * $huansuan;
                $d['top'] = intval(str_replace('px', '', $d['top'])) * $huansuan;
                $d['width'] = intval(str_replace('px', '', $d['width'])) * $huansuan;
                $d['height'] = intval(str_replace('px', '', $d['height'])) * $huansuan;
                $d['size'] = intval(str_replace('px', '', $d['size'])) * $huansuan/2*1.5;

                //var_dump($d['type']);exit;
                if ($d['type'] == 'img') {
                    if($d['src'][0] == '/') $d['src'] = PRE_URL.$d['src'];
                    $img = imagecreatefromstring(request_get($d['src']));
                    if($img)
                        imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'],imagesx($img), imagesy($img));
                } else if ($d['type'] == 'text') {
                    $d['content'] = str_replace(array_keys($textReplaceArr),array_values($textReplaceArr),$d['content']);
                    $colors = hex2rgb($d['color']);
                    $color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
                    imagettftext($target, $d['size'], 0, $d['left'], $d['top'] + $d['size'], $color, $font,  $d['content']);
                } else if ($d['type'] == 'textarea') {
                    $d['content'] = str_replace(array_keys($textReplaceArr),array_values($textReplaceArr),$d['content']);
                    $colors = hex2rgb($d['color']);
                    $color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
                    $string = $d['content'];
                    $_string='';
                    $__string='';
                    $_height = 0;
                    mb_internal_encoding("UTF-8"); // 设置编码
                    for($i=0;$i<mb_strlen($string);$i++){
                        $box = imagettfbbox($d['size'],0,$font,$_string);
                        $_string_length = $box[2]-$box[0];
                        $box = imagettfbbox($d['size'],0,$font,mb_substr($string,$i,1));
                        if($_string_length+$box[2]-$box[0]<$d['width']*1){
                            $_string.=mb_substr($string,$i,1);
                        }else{
                            $_height += $box[1]-$box[7]+4;
                            //var_dump($_height.'--'.$d['height']);
                            if($_height >= $d['height']*1){
                                break;
                            }
                            $__string.=$_string."\n";
                            $_string=mb_substr($string,$i,1);
                        }
                    }
                    $__string.=$_string;
                    $box=imagettfbbox($d['size'],0,$font,mb_substr($__string,0,1));
                    imagettftext($target,$d['size'],0,$d['left'],$d['top']+($box[3]-$box[7]),$color,$font,$__string);

                } else if ($d['type'] == 'pro_img') {
                    $img = imagecreatefromstring(request_get($textReplaceArr['[商品图片]']));
                    if($img)
                        imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'],imagesx($img), imagesy($img));
                } else if ($d['type'] == 'shadow') {
                    $rgba = explode(',',str_replace(array(' ','(',')','rgba'),'',$d['shadow']));
                    //dump($rgba);
                    $black = imagecreatetruecolor($d['width'], $d['height']);
                    imagealphablending($black, false);
                    imagesavealpha($black, true);
                    $blackcolor = imagecolorallocatealpha($black,$rgba[0],$rgba[1],$rgba[2],(1-$rgba[3])*127);
                    imagefill($black, 0, 0, $blackcolor);
                    imagecopy($target, $black, $d['left'], $d['top'], 0, 0, $d['width'], $d['height']);
                    imagedestroy($black);
                } else if($d['type'] == 'head') {
                    $src_img = imagecreatefromstring(request_get($textReplaceArr['[头像]']));
                    if($src_img){
                        $w = imagesx($src_img);
                        $h = imagesy($src_img);
                        $radius = $d['radius']*0.01*$w/2;
                        if($radius > 0){
                            $img = imagecreatetruecolor($w, $h);
                            //这一句一定要有
                            imagesavealpha($img, true);
                            //拾取一个完全透明的颜色,最后一个参数127为全透明
                            $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
                            imagefill($img, 0, 0, $bg);
                            $r = $radius; //圆 角半径
                            for ($x = 0; $x < $w; $x++) {
                                for ($y = 0; $y < $h; $y++) {
                                    $rgbColor = imagecolorat($src_img, $x, $y);
                                    if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                                        //不在四角的范围内,直接画
                                        imagesetpixel($img, $x, $y, $rgbColor);
                                    } else {
                                        //在四角的范围内选择画
                                        //上左
                                        $y_x = $r; //圆心X坐标
                                        $y_y = $r; //圆心Y坐标
                                        if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                                            imagesetpixel($img, $x, $y, $rgbColor);
                                        }
                                        //上右
                                        $y_x = $w - $r; //圆心X坐标
                                        $y_y = $r; //圆心Y坐标
                                        if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                                            imagesetpixel($img, $x, $y, $rgbColor);
                                        }
                                        //下左
                                        $y_x = $r; //圆心X坐标
                                        $y_y = $h - $r; //圆心Y坐标
                                        if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                                            imagesetpixel($img, $x, $y, $rgbColor);
                                        }
                                        //下右
                                        $y_x = $w - $r; //圆心X坐标
                                        $y_y = $h - $r; //圆心Y坐标
                                        if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                                            imagesetpixel($img, $x, $y, $rgbColor);
                                        }
                                    }
                                }
                            }
                            imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'],imagesx($img), imagesy($img));
                        }else{
                            imagecopyresampled($target, $src_img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'],imagesx($src_img), imagesy($src_img));
                        }
                    }
                }
            }
            $url = '/upload/'.date('Ym/d_His').rand(1000,9999).'.jpg';
            $filepath = ROOT_PATH.ltrim($url,'/');
            mk_dir(dirname($filepath));
            imagejpeg($target,$filepath,100);
            return $this->json( ['status' => 1 ,'url' => PRE_URL.$url ]);
        }
    }

    public function copyOrder()
    {
        $shoporder_copy = getcustom('shoporder_copy');
        if ($shoporder_copy) {
            $post = input('post.');
            $order = Db::name('shop_order')->where('aid', aid)->where('id', $post['id'])->find();
            if (!$order) {
                return $this->json(['status' => 0, 'msg' => '订单不存在']);
            }
            $order_goods = Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $order['id'])->select()->toArray();
            $aid = $order['aid'];
            $mid = $order['mid'];
            $member = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
            $sysset = Db::name('admin_set')->where('aid', aid)->find();

            Db::startTrans();
            try {

                unset($order['id'], $order['payorderid'], $order['paytypeid'], $order['paytype'], $order['paynum'], $order['paytime'], $order['express_com'], $order['express_no'], $order['express_ogids'], $order['express_isbufen'], $order['express_type'], $order['express_content'], $order['refund_reason'], $order['refund_money'], $order['refund_status'], $order['refund_time'], $order['refund_checkremark'], $order['send_time'], $order['collect_time'], $order['isfenhong']);

                $order['createtime'] = time();
                $order['status'] = 0;
                $order['platform'] = 'admin';
                $order['hexiao_code'] = random(16);
                $order['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co='.$order['hexiao_code']));
                $order['remark'] = '复制订单';

                $orderid = Db::name('shop_order')->insertGetId($order);

                $prolist = [];

                foreach ($order_goods as $key => $pro) {

                    $product = Db::name('shop_product')->where('aid', aid)->where('id', $pro['proid'])->find();
                    if (!$product) {
                        Db::rollback();
                        return $this->json(['status' => 0, 'msg' => '产品不存在或已下架']);
                    }
                    $guige = Db::name('shop_guige')->where('aid', aid)->where('id', $pro['ggid'])->find();
                    if (!$guige) {
                        Db::rollback();
                        return $this->json(['status' => 0, 'msg' => '产品规格不存在或已下架']);
                    }
                    if ($guige['stock'] < $pro['num']) {
                        Db::rollback();
                        return $this->json(['status' => 0, 'msg' => $product['name'] . $guige['name'] . '库存不足']);
                    }
                    if ($product['lvprice'] == 1 && $member) {
                        $lvprice_data = json_decode($guige['lvprice_data'], true);
                        if ($lvprice_data)
                            $guige['sell_price'] = $lvprice_data[$member['levelid']];
                    }

                    if ($key == 0) $title = $product['name'];

                    $prolist[] = ['product' => $product, 'guige' => $guige, 'num' => $pro['num'], 'order_goods' => $pro];
                }

                $istc = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
                $istc1 = 0;
                $istc2 = 0;
                $istc3 = 0;
                foreach ($prolist as $key => $v) {
                    $product = $v['product'];
                    $guige = $v['guige'];
                    $num = $v['num'];
                    $order_goods_v = $v['order_goods'];
                    if (getcustom('product_wholesale') && $product['product_type'] == 4) {
                        $guigedata = json_decode($product['guigedata'], true);
                        $gg_name_arr = explode(',', $guige['name']);
                        foreach ($guigedata as $pk => $pg) {
                            foreach ($pg['items'] as $pgt) {
                                if (isset($pgt['ggpic_wholesale']) && !empty($pgt['ggpic_wholesale'])) {
                                    if (in_array($pgt['title'], $gg_name_arr)) {
                                        $guige['pic'] = $pgt['ggpic_wholesale'];
                                    }
                                }
                            }
                        }
                    }
                    $ogdata = [];
                    $ogdata['aid'] = aid;
                    $ogdata['bid'] = $product['bid'];
                    $ogdata['mid'] = $order['mid'];
                    $ogdata['orderid'] = $orderid;
                    $ogdata['ordernum'] = $order['ordernum'];
                    $ogdata['proid'] = $product['id'];
                    $ogdata['name'] = $order_goods_v['name'];
                    $ogdata['pic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
                    $ogdata['procode'] = $product['procode'];
                    $ogdata['barcode'] = $product['barcode'];
                    $ogdata['ggid'] = $order_goods_v['ggid'];
                    $ogdata['ggname'] = $order_goods_v['ggname'];
                    $ogdata['cid'] = $product['cid'];
                    $ogdata['num'] = $num;
                    $ogdata['cost_price'] = $order_goods_v['cost_price'];
                    $ogdata['sell_price'] = $order_goods_v['sell_price'];
                    $ogdata['totalprice'] = $order_goods_v['totalprice'];
                    $ogdata['status'] = 0;
                    $ogdata['remark'] = $order_goods_v['remark'] ?? '';
                    $ogdata['createtime'] = time();
                    if ($product['fenhongset'] == 0) { //不参与分红
                        $ogdata['isfenhong'] = 2;
                    }

                    $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                    if ($istc != 1) {
                        $og_totalprice = $ogdata['totalprice'];
                        $leveldk_money = 0;
                        $coupon_money = 0;
                        $scoredk_money = 0;
                        $manjian_money = $order['product_price'] + $order['freight_price'] - $order['totalprice'];

                        //计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                        if ($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2) {
                            $allproduct_price = $order['product_price'];
                            $og_leveldk_money = 0;
                            $og_coupon_money = 0;
                            $og_scoredk_money = 0;
                            $og_manjian_money = 0;
                            if ($allproduct_price > 0 && $og_totalprice > 0) {
                                if ($leveldk_money) {
                                    $og_leveldk_money = $og_totalprice / $allproduct_price * $leveldk_money;
                                }
                                if ($coupon_money) {
                                    $og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
                                }
                                if ($scoredk_money) {
                                    $og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
                                }
                                if ($manjian_money) {
                                    $og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
                                }
                            }
                            $og_totalprice = round($og_totalprice - $og_coupon_money - $og_scoredk_money - $og_manjian_money, 2);
                            if ($og_totalprice < 0) $og_totalprice = 0;
                        }
                        $ogdata['real_totalprice'] = $og_totalprice; //实际商品销售金额

                        //计算佣金的商品金额
                        $commission_totalprice = $ogdata['totalprice'];
                        if ($sysset['fxjiesuantype'] == 1) {
                            $commission_totalprice = $ogdata['real_totalprice'];
                        }
                        if ($sysset['fxjiesuantype'] == 2) { //按利润提成
                            $commission_totalprice = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                        }
                        if ($commission_totalprice < 0) $commission_totalprice = 0;
                        $commission_totalpriceCache = $commission_totalprice;

                        //平级奖计算金额
                        $commission_totalprice_pj = 0;//
                        if (getcustom('commission_parent_pj_jiesuantype')) {
                            if ($sysset['fxjiesuantype_pj'] == 1) { //按商品价格
                                $commission_totalprice_pj = $ogdata['totalprice'];
                            }
                            if ($sysset['fxjiesuantype_pj'] == 2) { //按成交价格
                                $commission_totalprice_pj = $ogdata['real_totalprice'];
                            }
                            if ($sysset['fxjiesuantype_pj'] == 3) { //按销售利润
                                $commission_totalprice_pj = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                            }
                            if ($commission_totalprice_pj < 0) {
                                $commission_totalprice_pj = 0;
                            }
                        }

                        $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                        if ($agleveldata['can_agent'] > 0 && $agleveldata['commission1own'] == 1) {
                            $member['pid'] = $member['id'];
                        }
                        if ($product['commissionset'] != -1) {
                            if (!getcustom('fenxiao_manage')) {
                                $sysset['fenxiao_manage_status'] = 0;
                            }
                            if ($sysset['fenxiao_manage_status']) {
                                $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset, $member, $product, $num, $commission_totalprice, $commission_totalprice_pj);
                            } else {
                                $commission_data = \app\common\Fenxiao::fenxiao($sysset, $member, $product, $num, $commission_totalprice, 0, $istc1, $istc2, $istc3, $commission_totalprice_pj);
                            }
                            $ogdata['parent1'] = $commission_data['parent1'] ?? 0;
                            $ogdata['parent2'] = $commission_data['parent2'] ?? 0;
                            $ogdata['parent3'] = $commission_data['parent3'] ?? 0;
                            $ogdata['parent4'] = $commission_data['parent4'] ?? 0;
                            $ogdata['parent1commission'] = $commission_data['parent1commission'] ?? 0;
                            $ogdata['parent2commission'] = $commission_data['parent2commission'] ?? 0;
                            $ogdata['parent3commission'] = $commission_data['parent3commission'] ?? 0;
                            $ogdata['parent4commission'] = $commission_data['parent4commission'] ?? 0;
                            $ogdata['parent1score'] = $commission_data['parent1score'] ?? 0;
                            $ogdata['parent2score'] = $commission_data['parent2score'] ?? 0;
                            $ogdata['parent3score'] = $commission_data['parent3score'] ?? 0;
                            //20250626新增 平级奖独立记录
                            if (getcustom('commission_parent_pj')) {
                                $ogdata['parent_pj1'] = $commission_data['parent_pj1'] ?? 0;
                                $ogdata['parent_pj2'] = $commission_data['parent_pj2'] ?? 0;
                                $ogdata['parent_pj3'] = $commission_data['parent_pj3'] ?? 0;
                                $ogdata['parent1commission_pj'] = $commission_data['parent1commission_pj'] ?? 0;
                                $ogdata['parent2commission_pj'] = $commission_data['parent2commission_pj'] ?? 0;
                                $ogdata['parent3commission_pj'] = $commission_data['parent3commission_pj'] ?? 0;
                            }

                            $istc1 = $commission_data['istc1'] ?? 0;
                            $istc2 = $commission_data['istc2'] ?? 0;
                            $istc3 = $commission_data['istc3'] ?? 0;
                        }
                    }
                    $ogid = Db::name('shop_order_goods')->insertGetId($ogdata);
                    if (getcustom('member_product_price')) {
                        if ($guige['is_member_product'] == 1) {
                            $buylog = [
                                'aid' => aid,
                                'mid' => $member['id'],
                                'ordernum' => $order['ordernum'],
                                'type' => 'admin',
                                'proid' => $ogdata['proid'],
                                'ggid' => $ogdata['ggid'],
                                'orderid' => $orderid,
                                'sell_price' => $ogdata['sell_price'],
                                'num' => $ogdata['num'],
                                'createtime' => time()
                            ];
                            Db::name('member_product_buylog')->insert($buylog);
                        }
                    }
                    $totalcommission = 0;
                    if ($ogdata['parent1'] && ($ogdata['parent1commission'] > 0 || $ogdata['parent1score'] > 0)) {
                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent1'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent1commission'], 'score' => $ogdata['parent1score'], 'remark' => '下级购买商品奖励', 'createtime' => time()]);
                        $totalcommission += $ogdata['parent1commission'];
                    }
                    if ($ogdata['parent2'] && ($ogdata['parent2commission'] || $ogdata['parent2score'])) {
                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent2'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent2commission'], 'score' => $ogdata['parent2score'], 'remark' => '下二级购买商品奖励', 'createtime' => time()]);
                        $totalcommission += $ogdata['parent2commission'];
                    }
                    if ($ogdata['parent3'] && ($ogdata['parent3commission'] || $ogdata['parent3score'])) {
                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent3'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent3commission'], 'score' => $ogdata['parent3score'], 'remark' => '下三级购买商品奖励', 'createtime' => time()]);
                        $totalcommission += $ogdata['parent3commission'];
                    }
                    if (getcustom('commission_parent_pj')) {
                        if ($ogdata['parent_pj1'] && ($ogdata['parent1commission_pj'] > 0)) {
                            $remark = '平级一级奖励';
                            $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj1'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogdata['parent1commission_pj'];
                        }
                        if ($ogdata['parent_pj2'] && ($ogdata['parent2commission_pj'] > 0)) {
                            $remark = '平级二级奖励';
                            $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj2'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogdata['parent2commission_pj'];
                        }
                        if ($ogdata['parent_pj3'] && ($ogdata['parent3commission_pj'] > 0)) {
                            $remark = '平级三级奖励';
                            $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj3'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogdata['parent1commission_pj'];
                        }
                    }
                    if (getcustom('product_glass')) {
                        $glass_record_id = $guige['glass_record_id'];
                        if ($glass_record_id) {
                            $glassrecord = Db::name('glass_record')->where('aid', aid)->where('id', $glass_record_id)->find();
                            if ($glassrecord) {
                                $orderglassrecord = [
                                    'glass_record_id' => $glassrecord['id'],
                                    'name' => $glassrecord['name'],
                                    'desc' => $glassrecord['desc'],
                                    'degress_left' => $glassrecord['degress_left'],
                                    'degress_right' => $glassrecord['degress_right'],
                                    'ipd' => $glassrecord['ipd'],
                                    'ipd_left' => $glassrecord['ipd_left'],
                                    'ipd_right' => $glassrecord['ipd_right'],
                                    'double_ipd' => $glassrecord['double_ipd'],
                                    'correction_left' => $glassrecord['correction_left'],
                                    'correction_right' => $glassrecord['correction_right'],
                                    'is_ats' => $glassrecord['is_ats'],
                                    'ats_left' => $glassrecord['ats_left'],
                                    'ats_right' => $glassrecord['ats_right'],
                                    'ats_zright' => $glassrecord['ats_zright'],
                                    'ats_zleft' => $glassrecord['ats_zleft'],
                                    'add_right' => $glassrecord['add_right'],
                                    'add_left' => $glassrecord['add_left'],
                                    'check_time' => $glassrecord['check_time'],
                                    'nickname' => $glassrecord['nickname'],
                                    'sex' => $glassrecord['sex'],
                                    'age' => $glassrecord['age'],
                                    'tel' => $glassrecord['tel'],
                                    'remark' => $glassrecord['remark'],
                                    'type' => $glassrecord['type'],
                                    'mid' => $glassrecord['mid'],
                                    'createtime' => time(),
                                    'aid' => aid,
                                    'bid' => $ogdata['bid'] ?? 0,
                                    'order_goods_id' => $ogid
                                ];
                                $order_glass_record_id = Db::name('order_glass_record')->insertGetId($orderglassrecord);
                                Db::name('shop_order_goods')->where('id', $ogid)->update(['glass_record_id' => $order_glass_record_id]);
                            }
                        }
                    }

                    if ($product['commissionset4'] == 1 && $product['lvprice'] == 1) { //极差分销
                        if (getcustom('jicha_removecommission')) { //算极差时先减去分销的钱
                            $commission_totalpriceCache = $commission_totalpriceCache - $totalcommission;
                        }
                        if ($member['path']) {
                            $parentList = Db::name('member')->where('id', 'in', $member['path'])->order(Db::raw('field(id,' . $member['path'] . ')'))->select()->toArray();
                            if ($parentList) {
                                $parentList = array_reverse($parentList);
                                $lvprice_data = json_decode($guige['lvprice_data'], true);
                                $nowprice = $commission_totalpriceCache;
                                $giveidx = 0;
                                foreach ($parentList as $k => $parent) {
                                    if ($parent['levelid'] && $lvprice_data[$parent['levelid']]) {
                                        $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                        if ($nowprice > $thisprice) {
                                            $commission = $nowprice - $thisprice;
                                            $nowprice = $thisprice;

                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent['id'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => '下级购买商品差价', 'createtime' => time()]);

                                            //平级奖
                                            if (getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')) {
                                                if ($parentList[$k + 1] && $parentList[$k + 1]['levelid'] == $parent['levelid']) {
                                                    $parent2 = $parentList[$k + 1];
                                                    $parent2lv = Db::name('member_level')->where('id', $parent2['levelid'])->find();
                                                    $parent2lv['commissionpingjitype'] = $parent2lv['commissiontype'];
                                                    if ($product['commissionpingjiset'] != 0) {
                                                        if ($product['commissionpingjiset'] == 1) {
                                                            $commissionpingjidata1 = json_decode($product['commissionpingjidata1'], true);
                                                            $parent2lv['commission_parent_pj'] = $commissionpingjidata1[$parent2lv['id']]['commission'];
                                                        } elseif ($product['commissionpingjiset'] == 2) {
                                                            $commissionpingjidata2 = json_decode($product['commissionpingjidata2'], true);
                                                            $parent2lv['commission_parent_pj'] = $commissionpingjidata2[$parent2lv['id']]['commission'];
                                                            $parent2lv['commissionpingjitype'] = 1;
                                                        } else {
                                                            $parent2lv['commission_parent_pj'] = 0;
                                                        }
                                                    }
                                                    if ($parent2lv['commission_parent_pj'] > 0) {
                                                        if ($parent2lv['commissionpingjitype'] == 0) {
                                                            $pingjicommission = $commission * $parent2lv['commission_parent_pj'] * 0.01;
                                                        } else {
                                                            $pingjicommission = $parent2lv['commission_parent_pj'];
                                                        }
                                                        if ($pingjicommission > 0) {
                                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent2['id'], 'frommid' => $parent['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $pingjicommission, 'score' => 0, 'remark' => '平级奖励', 'createtime' => time()]);
                                                        }
                                                    }
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
                    Db::name('shop_guige')->where('aid', aid)->where('id', $guige['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
                    Db::name('shop_product')->where('aid', aid)->where('id', $product['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
                }

                $newordernum = date('ymdHis') . rand(100000, 999999);
                $ordernumArr = explode('_', $order['ordernum']);
                if ($ordernumArr[1]) $newordernum .= '_' . $ordernumArr[1];
                $payorderid = \app\model\Payorder::createorder(aid,$order['bid'],$order['mid'],'shop',$orderid,$newordernum,$order['title'],$order['totalprice']);
                Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->update(['ordernum' => $newordernum,'payorderid' =>$payorderid]);
                Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $orderid)->update(['ordernum' => $newordernum]);

                //订单创建完成后操作
                $orderids = \app\model\ShopOrder::after_create(aid,$orderid);

                \app\common\System::plog('移动端商城订单复制' . $orderid);

                Db::commit();
                $rdata['status'] = 1;
                return $this->json($rdata);

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->json(['status' => 0, 'msg' => $e->getMessage() ?? '复制失败']);
            }
        }
    }

    public function updateMember()
    {
        $shoporder_update_member = getcustom('shoporder_update_member');
        if ($shoporder_update_member) {
            $post = input('post.');
            $order = Db::name('shop_order')->where('aid', aid)->where('id', $post['id'])->find();
            if (!$order) {
                return $this->json(['status' => 0, 'msg' => '订单不存在']);
            }

            if ($order['status'] != 0) {
                return $this->json(['status' => 0, 'msg' => '只有待付款的订单才能更换下单人']);
            }

            $order_goods = Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $order['id'])->select()->toArray();

            if(!$post['updatemid']){
                return $this->json(['status' => 0, 'msg' => '请输入要更换的用户ID']);
            }
            $member = Db::name('member')->where('aid', aid)->where('id', $post['updatemid'])->find();
            if(!$member){
                return $this->json(['status' => 0, 'msg' => '用户不存在']);
            }

            if($member['id'] == $order['mid']){
                return $this->json(['status' => 0, 'msg' => '当前订单的用户和要更换的用户是同一个']);
            }

            $sysset = Db::name('admin_set')->where('aid', aid)->find();

            //获取用户地址
            $address = Db::name('member_address')->where('aid', aid)->where('mid', $member['id'])->order('isdefault desc,id desc')->find();

            $orderid = $order['id'];

            Db::startTrans();
            try {

                $update_order = [];
                $update_order['mid'] = $member['id'];
                $update_order['linkman'] = $address['name'] ?? '';
                $update_order['tel'] = $address['tel'] ?? '';
                $update_order['area'] = $address['area'] ?? '';
                $update_order['area2'] = $address['province'] ? $address['province'].','.$address['city'].','.$address['province'] : '';
                $update_order['address'] = $address['address'] ?? '';

                Db::name('shop_order')->where('aid', $order['aid'])->where('id', $order['id'])->update($update_order);

                Db::name('payorder')->where('aid', $order['aid'])->where('orderid', $order['id'])->where('type', 'shop')->update(['mid' => $member['id']]);

                //先删除之前规格
                if ($old_goods = Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $orderid)->select()) {
                    foreach ($old_goods as $vg) {
                        $oldnum = $vg['num'];
//                        Db::name('shop_guige')->where('aid', aid)->where('id', $vg['ggid'])->update(['stock' => Db::raw("stock+$oldnum"), 'sales' => Db::raw("sales-$oldnum")]);
//                        Db::name('shop_product')->where('aid', aid)->where('id', $vg['proid'])->update(['stock' => Db::raw("stock+$oldnum"), 'sales' => Db::raw("sales-$oldnum")]);

                        Db::name('shop_order_goods')->where('aid', aid)->where('id', $vg['id'])->delete();

                        Db::name('member_commission_record')->where(['aid' => aid, 'orderid' => $orderid, 'ogid' => $vg['id'], 'type' => 'shop'])->delete();
                    }
                }

                $prolist = [];

                foreach ($order_goods as $key => $pro) {

                    Db::name('member_fenhonglog')->where(['aid' => aid, 'ogids' => $pro['id'], 'module' => 'shop'])->delete();

                    $product = Db::name('shop_product')->where('aid', aid)->where('id', $pro['proid'])->find();
                    if (!$product) {
                        //Db::rollback();
                        //return $this->json(['status' => 0, 'msg' => '产品不存在或已下架']);
                    }
                    $guige = Db::name('shop_guige')->where('aid', aid)->where('id', $pro['ggid'])->find();
                    if (!$guige) {
                        //Db::rollback();
                        //return $this->json(['status' => 0, 'msg' => '产品规格不存在或已下架']);
                    }
                    if ($guige['stock'] < $pro['num']) {
                        //Db::rollback();
                        //return $this->json(['status' => 0, 'msg' => $product['name'] . $guige['name'] . '库存不足']);
                    }
                    if ($product['lvprice'] == 1 && $member) {
                        $lvprice_data = json_decode($guige['lvprice_data'], true);
                        if ($lvprice_data)
                            $guige['sell_price'] = $lvprice_data[$member['levelid']];
                    }

                    if ($key == 0) $title = $product['name'];

                    $prolist[] = ['product' => $product, 'guige' => $guige, 'num' => $pro['num'], 'order_goods' => $pro];
                }

                $istc = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
                $istc1 = 0;
                $istc2 = 0;
                $istc3 = 0;
                foreach ($prolist as $key => $v) {
                    $product = $v['product'];
                    $guige = $v['guige'];
                    $num = $v['num'];
                    $order_goods_v = $v['order_goods'];
                    if (getcustom('product_wholesale') && $product['product_type'] == 4) {
                        $guigedata = json_decode($product['guigedata'], true);
                        $gg_name_arr = explode(',', $guige['name']);
                        foreach ($guigedata as $pk => $pg) {
                            foreach ($pg['items'] as $pgt) {
                                if (isset($pgt['ggpic_wholesale']) && !empty($pgt['ggpic_wholesale'])) {
                                    if (in_array($pgt['title'], $gg_name_arr)) {
                                        $guige['pic'] = $pgt['ggpic_wholesale'];
                                    }
                                }
                            }
                        }
                    }
                    $ogdata = [];
                    $ogdata['aid'] = aid;
                    $ogdata['bid'] = $product['bid'];
                    $ogdata['mid'] = $member['id'];
                    $ogdata['orderid'] = $orderid;
                    $ogdata['ordernum'] = $order['ordernum'];
                    $ogdata['proid'] = $product['id'];
                    $ogdata['name'] = $order_goods_v['name'];
                    $ogdata['pic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
                    $ogdata['procode'] = $product['procode'];
                    $ogdata['barcode'] = $product['barcode'];
                    $ogdata['ggid'] = $order_goods_v['ggid'];
                    $ogdata['ggname'] = $order_goods_v['ggname'];
                    $ogdata['cid'] = $product['cid'];
                    $ogdata['num'] = $num;
                    $ogdata['cost_price'] = $order_goods_v['cost_price'];
                    $ogdata['sell_price'] = $order_goods_v['sell_price'];
                    $ogdata['totalprice'] = $order_goods_v['totalprice'];
                    $ogdata['status'] = 0;
                    $ogdata['remark'] = $order_goods_v['remark'] ?? '';
                    $ogdata['createtime'] = time();
                    if ($product['fenhongset'] == 0) { //不参与分红
                        $ogdata['isfenhong'] = 2;
                    }

                    $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                    if ($istc != 1) {
                        $og_totalprice = $ogdata['totalprice'];
                        $leveldk_money = 0;
                        $coupon_money = 0;
                        $scoredk_money = 0;
                        $manjian_money = $order['product_price'] + $order['freight_price'] - $order['totalprice'];

                        //计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                        if ($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2) {
                            $allproduct_price = $order['product_price'];
                            $og_leveldk_money = 0;
                            $og_coupon_money = 0;
                            $og_scoredk_money = 0;
                            $og_manjian_money = 0;
                            if ($allproduct_price > 0 && $og_totalprice > 0) {
                                if ($leveldk_money) {
                                    $og_leveldk_money = $og_totalprice / $allproduct_price * $leveldk_money;
                                }
                                if ($coupon_money) {
                                    $og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
                                }
                                if ($scoredk_money) {
                                    $og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
                                }
                                if ($manjian_money) {
                                    $og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
                                }
                            }
                            $og_totalprice = round($og_totalprice - $og_coupon_money - $og_scoredk_money - $og_manjian_money, 2);
                            if ($og_totalprice < 0) $og_totalprice = 0;
                        }
                        $ogdata['real_totalprice'] = $og_totalprice; //实际商品销售金额

                        //计算佣金的商品金额
                        $commission_totalprice = $ogdata['totalprice'];
                        if ($sysset['fxjiesuantype'] == 1) {
                            $commission_totalprice = $ogdata['real_totalprice'];
                        }
                        if ($sysset['fxjiesuantype'] == 2) { //按利润提成
                            $commission_totalprice = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                        }
                        if ($commission_totalprice < 0) $commission_totalprice = 0;
                        $commission_totalpriceCache = $commission_totalprice;

                        //平级奖计算金额
                        $commission_totalprice_pj = 0;//
                        if (getcustom('commission_parent_pj_jiesuantype')) {
                            if ($sysset['fxjiesuantype_pj'] == 1) { //按商品价格
                                $commission_totalprice_pj = $ogdata['totalprice'];
                            }
                            if ($sysset['fxjiesuantype_pj'] == 2) { //按成交价格
                                $commission_totalprice_pj = $ogdata['real_totalprice'];
                            }
                            if ($sysset['fxjiesuantype_pj'] == 3) { //按销售利润
                                $commission_totalprice_pj = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                            }
                            if ($commission_totalprice_pj < 0) {
                                $commission_totalprice_pj = 0;
                            }
                        }

                        $agleveldata = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                        if ($agleveldata['can_agent'] > 0 && $agleveldata['commission1own'] == 1) {
                            $member['pid'] = $member['id'];
                        }
                        if ($product['commissionset'] != -1) {
                            if (!getcustom('fenxiao_manage')) {
                                $sysset['fenxiao_manage_status'] = 0;
                            }
                            if ($sysset['fenxiao_manage_status']) {
                                $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset, $member, $product, $num, $commission_totalprice, $commission_totalprice_pj);
                            } else {
                                $commission_data = \app\common\Fenxiao::fenxiao($sysset, $member, $product, $num, $commission_totalprice, 0, $istc1, $istc2, $istc3, $commission_totalprice_pj);
                            }
                            $ogdata['parent1'] = $commission_data['parent1'] ?? 0;
                            $ogdata['parent2'] = $commission_data['parent2'] ?? 0;
                            $ogdata['parent3'] = $commission_data['parent3'] ?? 0;
                            $ogdata['parent4'] = $commission_data['parent4'] ?? 0;
                            $ogdata['parent1commission'] = $commission_data['parent1commission'] ?? 0;
                            $ogdata['parent2commission'] = $commission_data['parent2commission'] ?? 0;
                            $ogdata['parent3commission'] = $commission_data['parent3commission'] ?? 0;
                            $ogdata['parent4commission'] = $commission_data['parent4commission'] ?? 0;
                            $ogdata['parent1score'] = $commission_data['parent1score'] ?? 0;
                            $ogdata['parent2score'] = $commission_data['parent2score'] ?? 0;
                            $ogdata['parent3score'] = $commission_data['parent3score'] ?? 0;
                            //20250626新增 平级奖独立记录
                            if (getcustom('commission_parent_pj')) {
                                $ogdata['parent_pj1'] = $commission_data['parent_pj1'] ?? 0;
                                $ogdata['parent_pj2'] = $commission_data['parent_pj2'] ?? 0;
                                $ogdata['parent_pj3'] = $commission_data['parent_pj3'] ?? 0;
                                $ogdata['parent1commission_pj'] = $commission_data['parent1commission_pj'] ?? 0;
                                $ogdata['parent2commission_pj'] = $commission_data['parent2commission_pj'] ?? 0;
                                $ogdata['parent3commission_pj'] = $commission_data['parent3commission_pj'] ?? 0;
                            }

                            $istc1 = $commission_data['istc1'] ?? 0;
                            $istc2 = $commission_data['istc2'] ?? 0;
                            $istc3 = $commission_data['istc3'] ?? 0;
                        }
                    }
                    $ogid = Db::name('shop_order_goods')->insertGetId($ogdata);
                    if (getcustom('member_product_price')) {
                        if ($guige['is_member_product'] == 1) {
                            Db::name('member_product_buylog')->where('aid', aid)->where('orderid', $orderid)->update(['mid' => $member['id']]);
                        }
                    }
                    $totalcommission = 0;
                    if ($ogdata['parent1'] && ($ogdata['parent1commission'] > 0 || $ogdata['parent1score'] > 0)) {
                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent1'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent1commission'], 'score' => $ogdata['parent1score'], 'remark' => '下级购买商品奖励', 'createtime' => time()]);
                        $totalcommission += $ogdata['parent1commission'];
                    }
                    if ($ogdata['parent2'] && ($ogdata['parent2commission'] || $ogdata['parent2score'])) {
                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent2'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent2commission'], 'score' => $ogdata['parent2score'], 'remark' => '下二级购买商品奖励', 'createtime' => time()]);
                        $totalcommission += $ogdata['parent2commission'];
                    }
                    if ($ogdata['parent3'] && ($ogdata['parent3commission'] || $ogdata['parent3score'])) {
                        Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $ogdata['parent3'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent3commission'], 'score' => $ogdata['parent3score'], 'remark' => '下三级购买商品奖励', 'createtime' => time()]);
                        $totalcommission += $ogdata['parent3commission'];
                    }
                    if (getcustom('commission_parent_pj')) {
                        if ($ogdata['parent_pj1'] && ($ogdata['parent1commission_pj'] > 0)) {
                            $remark = '平级一级奖励';
                            $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj1'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogdata['parent1commission_pj'];
                        }
                        if ($ogdata['parent_pj2'] && ($ogdata['parent2commission_pj'] > 0)) {
                            $remark = '平级二级奖励';
                            $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj2'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogdata['parent2commission_pj'];
                        }
                        if ($ogdata['parent_pj3'] && ($ogdata['parent3commission_pj'] > 0)) {
                            $remark = '平级三级奖励';
                            $data_c = ['aid' => aid, 'mid' => $ogdata['parent_pj3'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogdata['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogdata['parent1commission_pj'];
                        }
                    }

                    if ($product['commissionset4'] == 1 && $product['lvprice'] == 1) { //极差分销
                        if (getcustom('jicha_removecommission')) { //算极差时先减去分销的钱
                            $commission_totalpriceCache = $commission_totalpriceCache - $totalcommission;
                        }
                        if ($member['path']) {
                            $parentList = Db::name('member')->where('id', 'in', $member['path'])->order(Db::raw('field(id,' . $member['path'] . ')'))->select()->toArray();
                            if ($parentList) {
                                $parentList = array_reverse($parentList);
                                $lvprice_data = json_decode($guige['lvprice_data'], true);
                                $nowprice = $commission_totalpriceCache;
                                $giveidx = 0;
                                foreach ($parentList as $k => $parent) {
                                    if ($parent['levelid'] && $lvprice_data[$parent['levelid']]) {
                                        $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                        if ($nowprice > $thisprice) {
                                            $commission = $nowprice - $thisprice;
                                            $nowprice = $thisprice;

                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent['id'], 'frommid' => $member['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => '下级购买商品差价', 'createtime' => time()]);

                                            //平级奖
                                            if (getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')) {
                                                if ($parentList[$k + 1] && $parentList[$k + 1]['levelid'] == $parent['levelid']) {
                                                    $parent2 = $parentList[$k + 1];
                                                    $parent2lv = Db::name('member_level')->where('id', $parent2['levelid'])->find();
                                                    $parent2lv['commissionpingjitype'] = $parent2lv['commissiontype'];
                                                    if ($product['commissionpingjiset'] != 0) {
                                                        if ($product['commissionpingjiset'] == 1) {
                                                            $commissionpingjidata1 = json_decode($product['commissionpingjidata1'], true);
                                                            $parent2lv['commission_parent_pj'] = $commissionpingjidata1[$parent2lv['id']]['commission'];
                                                        } elseif ($product['commissionpingjiset'] == 2) {
                                                            $commissionpingjidata2 = json_decode($product['commissionpingjidata2'], true);
                                                            $parent2lv['commission_parent_pj'] = $commissionpingjidata2[$parent2lv['id']]['commission'];
                                                            $parent2lv['commissionpingjitype'] = 1;
                                                        } else {
                                                            $parent2lv['commission_parent_pj'] = 0;
                                                        }
                                                    }
                                                    if ($parent2lv['commission_parent_pj'] > 0) {
                                                        if ($parent2lv['commissionpingjitype'] == 0) {
                                                            $pingjicommission = $commission * $parent2lv['commission_parent_pj'] * 0.01;
                                                        } else {
                                                            $pingjicommission = $parent2lv['commission_parent_pj'];
                                                        }
                                                        if ($pingjicommission > 0) {
                                                            Db::name('member_commission_record')->insert(['aid' => aid, 'mid' => $parent2['id'], 'frommid' => $parent['id'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $pingjicommission, 'score' => 0, 'remark' => '平级奖励', 'createtime' => time()]);
                                                        }
                                                    }
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }
//                    Db::name('shop_guige')->where('aid', aid)->where('id', $guige['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
//                    Db::name('shop_product')->where('aid', aid)->where('id', $product['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
                }

                //订单创建完成后操作
                $orderids = \app\model\ShopOrder::after_create(aid,$orderid);

                \app\common\System::plog('移动端商城订单更换下单人' . $orderid);

                Db::commit();
                $rdata['status'] = 1;
                return $this->json($rdata);

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->json(['status' => 0, 'msg' => $e->getMessage() ?? '更换失败']);
            }
        }
    }
    //金豆商城订单
    public function goldbeanshoporder(){
        if(getcustom('gold_bean_shop')) {
            $st = input('param.st');
            if (!input('?param.st') || $st === '') {
                $st = 'all';
            }
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['bid', '=', bid];
            if ($this->user['mdid']) {
                $where[] = ['mdid', '=', $this->user['mdid']];
            }
            if (input('param.keyword')) $where[] = ['ordernum|title', 'like', '%' . input('param.keyword') . '%'];
            if ($st == 'all') {

            } elseif ($st == '0') {
                $where[] = ['status', '=', 0];
            } elseif ($st == '1') {
                $where[] = ['status', '=', 1];
            } elseif ($st == '2') {
                $where[] = ['status', '=', 2];
            } elseif ($st == '3') {
                $where[] = ['status', '=', 3];
            } elseif ($st == '10') {
                $where[] = ['refund_status', '>', 0];
            }
            $pernum = 10;
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $datalist = Db::name('gold_bean_shop_order')->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            if (!$datalist) $datalist = array();
            foreach ($datalist as $key => $v) {
                $datalist[$key]['prolist'] = Db::name('gold_bean_shop_order_goods')->where('orderid', $v['id'])->select()->toArray();
                if (!$datalist[$key]['prolist']) $datalist[$key]['prolist'] = [];
                $datalist[$key]['procount'] = Db::name('gold_bean_shop_order_goods')->where('orderid', $v['id'])->sum('num');
                $datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id', $v['mid'])->find();
                if (!$datalist[$key]['member']) $datalist[$key]['member'] = [];
            }
            $rdata = [];
            $rdata['datalist'] = $datalist;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }
    //金豆商城订单详情
    public function goldbeanshoporderdetail(){
        if(getcustom('gold_bean_shop')) {
            $detail = Db::name('gold_bean_shop_order')->where('id', input('param.id/d'))->where('aid', aid)->where('bid', bid)->find();
            if (!$detail) $this->json(['status' => 0, 'msg' => '订单不存在']);
            $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s', $detail['createtime']) : '';
            $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s', $detail['collect_time']) : '';
            $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s', $detail['paytime']) : '';
            $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s', $detail['refund_time']) : '';
            $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s', $detail['send_time']) : '';
            $detail['formdata'] = \app\model\Freight::getformdata($detail['id'], 'gold_bean_shop_order');

            $member = Db::name('member')->where('id', $detail['mid'])->field('id,nickname,headimg')->find();
            $detail['nickname'] = $member['nickname'];
            $detail['headimg'] = $member['headimg'];

            $detail['hidefahuo'] = false;
            $storeinfo = [];
            if ($detail['freight_type'] == 1) {
                if ($detail['mdid'] == -1) {
                    $freight = Db::name('freight')->where('id', $detail['freight_id'])->find();
                    if ($freight && $freight['hxbids']) {
                        if ($detail['longitude'] && $detail['latitude']) {
                            $orderBy = Db::raw("({$detail['longitude']}-longitude)*({$detail['longitude']}-longitude) + ({$detail['latitude']}-latitude)*({$detail['latitude']}-latitude) ");
                        } else {
                            $orderBy = 'sort desc,id';
                        }
                        $storelist = Db::name('business')->where('aid', $freight['aid'])->where('id', 'in', $freight['hxbids'])->where('status', 1)->field('id,name,logo pic,longitude,latitude,address')->order($orderBy)->select()->toArray();
                        foreach ($storelist as $k2 => $v2) {
                            if ($detail['longitude'] && $detail['latitude'] && $v2['longitude'] && $v2['latitude']) {
                                $v2['juli'] = '距离' . getdistance($detail['longitude'], $detail['latitude'], $v2['longitude'], $v2['latitude'], 2) . '千米';
                            } else {
                                $v2['juli'] = '';
                            }
                            $storelist[$k2] = $v2;
                        }
                    }
                } else {
                    $storeinfo = Db::name('mendian')->where('id', $detail['mdid'])->field('id,name,address,longitude,latitude')->find();
                }
                if (getcustom('freight_selecthxbids')) {
                    $detail['hidefahuo'] = true;
                }
            }
            $prolist = Db::name('gold_bean_shop_order_goods')->where('orderid', $detail['id'])->select()->toArray();

            $peisong_set = Db::name('peisong_set')->where('aid', aid)->find();
            if ($peisong_set['status'] == 1 && bid > 0 && $peisong_set['businessst'] == 0 && $peisong_set['make_status'] == 0) $peisong_set['status'] = 0;
            $detail['canpeisong'] = ($detail['freight_type'] == 2 && $peisong_set['status'] == 1) ? true : false;


            $rdata = [];
            $rdata['detail'] = $detail;
            $rdata['prolist'] = $prolist;
            $rdata['storeinfo'] = $storeinfo;
            $rdata['expressdata'] = array_keys(express_data(['aid' => aid, 'bid' => bid]));

            return $this->json($rdata);
        }
    }

    /**
     * 微信同城配送
     * @author: liud
     * @time: 2025/11/5 14:41
     */
    public function wxtcPeisong(){
        if(getcustom('wx_express_intracity')){
            $orderid = input('post.orderid/d');
            $type = input('post.type');
            $weight = input('post.weight');
            $cargo_type = input('post.cargo_type');
            $psid = input('post.psid/d');
            $psset_where = [];
            $psset_where[] = ['aid','=',aid];

            $peisong = Db::name('peisong_set')->where($psset_where)->find();

            if(bid == 0){
                $order = Db::name($type)->where('id',$orderid)->where('aid',aid)->find();

            }else{
                $order = Db::name($type)->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
            }

            if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
            if($order['status']!=1 && $order['status']!=12) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);

            if($peisong['wxtc_status'] != 1){
                return $this->json(['status'=>0,'msg'=>'微信同城配送功能未开启']);
            }

            if($order['bid'] == 0){
                if($peisong['wxtc_store_id'] <= 0){
                    return $this->json(['status'=>0,'msg'=>'平台未绑定微信配送门店']);
                }

                $wxstore = Db::name('wx_express_intracity_store')->where('aid',aid)->where('id',$peisong['wxtc_store_id'])->find();
            }else{
                if($peisong['wxtc_status_business'] != 1){
                    return $this->json(['status'=>0,'msg'=>'平台未开启商户微信同城配送功能']);
                }

                $binfo = Db::name('business')->where('aid',aid)->where('id',$order['bid'])->find();

                if($binfo['wxtc_status'] != 1){
                    return $this->json(['status'=>0,'msg'=>'商户未开启微信同城配送功能']);
                }
                if($binfo['wxtc_store_id'] <= 0){
                    return $this->json(['status'=>0,'msg'=>'商户未绑定微信配送门店']);
                }

                $wxstore = Db::name('wx_express_intracity_store')->where('aid',aid)->where('id',$binfo['wxtc_store_id'])->find();
            }

            if($wxstore['status'] != 1){
                return $this->json(['status'=>0,'msg'=>'微信配送门店(ID:'.$wxstore['id'].')已关闭']);
            }

            //查询商品
            $order['item_list'] = Db::name($type.'_goods')->where('orderid',$order['id'])->select()->toArray();

            $order['cargo_weight'] = $weight ? $weight*1000 : 1000;
            $order['cargo_type'] = $cargo_type ? intval($cargo_type) : 99;
            $order['wx_store_id'] = $wxstore['wx_store_id'] ?? '';

            $order['user_openid'] = Db::name('member')->where('aid',aid)->where('id',$order['mid'])->value('wxopenid') ?? '';

            $res = \app\custom\WxExpressIntracity::addorder(aid,$order);
            if($res['status'] != 1){
                return $this->json(['status'=>0,'msg'=>'操作失败：'.$res['msg']]);
            }

            if($order['bid']>0){
                $business = Db::name('business')->field('name,address,tel,logo,longitude,latitude,money')->where('id',$order['bid'])->find();
            }elseif($order['mdid']>0){
                $business = Db::name('mendian')->field('name,address,tel,pic,longitude,latitude,money')->where('id',$order['mdid'])->find();
            }else{
                $business = Db::name('admin_set')->field('name,address,tel,logo,longitude,latitude')->where('aid',$order['aid'])->find();
            }

            $wxtc_data = $res['data']['data'];

            $service_trans = \app\custom\WxExpressIntracity::service_trans;

            $express_com = '微信同城配送-'.$service_trans[$wxtc_data['service_trans_id']];

            $order['procount'] = Db::name($type.'_goods')->where('orderid',$order['id'])->sum('num');

            $psorderdata = [];
            $psorderdata['aid'] = $order['aid'];
            $psorderdata['bid'] = $order['bid'];
            $psorderdata['mid'] = $order['mid'];
            $psorderdata['orderid'] = $order['id'];
            $psorderdata['ordernum'] = $order['ordernum'];
            $psorderdata['mdid'] = $order['mdid'];
            $psorderdata['createtime'] = time();
            $psorderdata['status'] = 0;
            $psorderdata['type'] = $type;
            $psorderdata['wxtc_wx_store_id'] = $wxtc_data['wx_store_id'];
            $psorderdata['wxtc_wx_order_id'] = $wxtc_data['wx_order_id'];
            $psorderdata['wxtc_service_trans_id'] = $wxtc_data['service_trans_id'];
            $psorderdata['wxtc_fee'] = $wxtc_data['fee'] / 100;
            $psorderdata['wxtc_trans_order_id'] = $wxtc_data['trans_order_id'];;
            $psorderdata['wxtc_addorder_notify'] =jsonEncode($wxtc_data);
            $psorderdata['orderinfo'] =  jsonEncode($order);
            $psorderdata['prolist'] = jsonEncode($order['item_list']);
            $psorderdata['binfo'] = jsonEncode($business);

            $psorderid = Db::name('peisong_order')->insertGetId($psorderdata);

            $wxtc_insertdata = [];
            $wxtc_insertdata['aid'] = $order['aid'];
            $wxtc_insertdata['bid'] = $order['bid'];
            $wxtc_insertdata['mid'] = $order['mid'];
            $wxtc_insertdata['poid'] = $psorderid;
            $wxtc_insertdata['createtime'] = time();
            $wxtc_insertdata['orderid'] = $order['id'];
            $wxtc_insertdata['ordernum'] = $order['ordernum'];
            $wxtc_insertdata['cargo_weight'] = $order['cargo_weight'];
            $wxtc_insertdata['cargo_type'] = $order['cargo_type'];
            $wxtc_insertdata['addorder_notify'] = jsonEncode($wxtc_data);
            $wxtc_insertdata['wx_store_id'] = $wxtc_data['wx_store_id'];
            $wxtc_insertdata['wx_order_id'] = $wxtc_data['wx_order_id'];
            $wxtc_insertdata['service_trans_id'] = $wxtc_data['service_trans_id'];
            $wxtc_insertdata['logistic_no'] = $wxtc_insertdata['trans_order_id'] = $wxtc_data['trans_order_id'];
            $wxtc_insertdata['order_status'] = 10000;

            Db::name('peisong_order_wx_express_intracity')->insertGetId($wxtc_insertdata);

            Db::name($type)->where('aid',aid)->where('id',$orderid)->update(['express_com'=>$express_com,'express_no'=>$wxtc_data['trans_order_id'],'send_time'=>time(),'status'=>2,'wxtc_wx_order_id' => $wxtc_data['wx_order_id']]);
            Db::name($type.'_goods')->where('orderid',$orderid)->where('aid',aid)->update(['status'=>2]);

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping(aid,$order,$type);
            }
            \app\common\System::plog('移动端微信同城订单配送'.$orderid);
            return $this->json(['status'=>1,'msg'=>'操作成功']);
        }
    }
}
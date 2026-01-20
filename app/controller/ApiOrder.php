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
use think\Exception;
use think\facade\Db;
use think\facade\Log;

class ApiOrder extends ApiCommon
{
	public function initialize(){
		parent::initialize();
		$this->checklogin();
	}
	public function orderlist(){
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
        if(input('param.keyword')){
            $keywords = input('param.keyword');
            $orderids = Db::name('shop_order_goods')->where($where)->where('name','like','%'.input('param.keyword').'%')->column('orderid');
            if(!$orderids){
                $where[] = ['ordernum|title', 'like', '%'.$keywords.'%'];
            }
        }

        $where[] = ['delete','=',0];

        $mendian_upgrade = false;  
        if(getcustom('mendian_upgrade')){
            $mendian_upgrade_status = Db::name('admin')->where('id',aid)->value('mendian_upgrade_status');
            if($mendian_upgrade_status == 1){
               $mendian_upgrade = true;  
            }
        }
		if($st == 'all'){

		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			if($mendian_upgrade){
				$where[] = ['status','in',[2,8]];
			}else{
				$where[] = ['status','=',2];
			}
			
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}

        if(getcustom('yx_collage_jipin_optimize')){
            $where[] = ['is_jipin_show','=',1];
        }

        if(getcustom('active_coin') && getcustom('yx_cashback_multiply')){
            $show_auto_order = Db::name('active_coin_set')
                ->where('aid',aid)
                ->value('show_auto_order');
            if($show_auto_order==0){
                $where[] = ['platform','<>','admin'];
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
        $datalist = $datalist->page($pagenum,$pernum)->order('id desc')->select()->toArray();
        if(!$datalist) $datalist = array();
        if(getcustom('school_product')){
            //如果是学校购入订单且设置了该学校不能退款
            $school_ids = array_filter(array_column($datalist,'school_id'));
            $schoollist = [];
            if($school_ids){
                $schoollist = Db::name('school')->where('aid',aid)->where('id','in',$school_ids)->column('is_refund','id');
            }
        }
        $supplierName = '';
        if(getcustom('product_supply_chain')){
            $supplierName = Db::name('admin')->alias('a')->where('a.id',aid)->join('supplier s','a.supplier_id=s.id')->field('s.name,s.id')->value('name');
        }
		$shopset = Db::name('shop_sysset')->where('aid',aid)->find();
		if(getcustom('product_collect_time')){
            //查询可确认收货时间
            $start_time = time()-$shopset['ordercollect_time']*24*60*60;
        }
         $collectReward = Db::name('order_collect_reward')->field('order_type,platform,max_daily_return,max_month_return,gettj,min_order_amount,prompt,fontcolor,bgcolor,start_time,end_time')->where('aid',aid)->where('start_time','<=',time())->where('end_time','>=',time())->where('status',1)->find();
		$isCollectReward = false;
        if($collectReward){
            if($collectReward['bgcolor']){
                $color1rgb = hex2rgb($collectReward['bgcolor']);
                $collectReward['bgcolor'] = $color1rgb['red'] . ',' . $color1rgb['green'] . ',' . $color1rgb['blue'];
            }
            $isCollectReward = $this->collectRewardNumLimit(aid,mid,$collectReward);
        }
        foreach($datalist as $key=>$v){
			$can_collect = true;
			if(getcustom('product_collect_time')){
				//查询确认收货状态
	            $can_collect = false;
	            if($v['status']==2 && $v['send_time'] && $v['send_time']<=$start_time){
	                $can_collect = true;
	            }
			}
			$datalist[$key]['can_collect'] = $can_collect;

            $datalist[$key]['prolist'] = [];
			$prolist = Db::name('shop_order_goods')->where('orderid',$v['id'])->select()->toArray();
			$isjici = 0;
            $procanrefund = 0;
            $canRefundNum = 0;
            $datalist[$key]['procanrefund'] = $shopset['canrefund'];
 
			foreach ($prolist as $pk=>$pv){
				if($pv['hexiao_code']) $isjici++;
                $prolist[$pk]['is_quanyi'] = (isset($pv['product_type']) && $pv['product_type']==8) ? 1 : 0;
                if(getcustom('product_quanyi') && $v['product_type']==8){
                    $check_res = \app\common\Order::quanyihexiao($pv['id'],1);
                    $prolist[$pk]['hexiao_tip'] = $check_res['msg'];
                }
                if(getcustom('shop_product_jialiao')){
                    if($pv['jltitle']) {
                        $prolist[$pk]['ggname'] = $pv['ggname'] . '(' . rtrim($pv['jltitle'],'/') . ')';
                    }
                    $prolist[$pk]['sell_price'] = dd_money_format($pv['sell_price'] + $pv['jlprice']);
                }

                // 退款退货
                if(getcustom('product_refund')){
                    $canrefund = Db::name('shop_product')->where('aid',aid)->where('id',$pv['proid'])->value('canrefund');
                    if($canrefund == '-1'){
                        // 跟随系统
                        $prolist[$pk]['canrefund'] = $shopset['canrefund'];
                    }else{
                        $prolist[$pk]['canrefund'] = $canrefund;
                    }
                    if($prolist[$pk]['canrefund'] == 1){
                        $procanrefund = 1;
                        $canRefundNum += $pv['num'];
                    }
                }
			}

            if(getcustom('product_refund')){
                $datalist[$key]['procanrefund'] = $procanrefund;
            }


			if($isjici >= count($prolist)) $datalist[$key]['hexiao_qr'] = '';
			if(getcustom('product_glass')){
			    foreach ($prolist as $pk=>$pv){
                    $prolist[$pk]['has_glassrecord'] = 0;
			        if($pv['glass_record_id']){
                        $glassrecord = \app\model\Glass::orderGlassRecord($pv['glass_record_id'],aid);
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
                        $prolist[$gk]['total_weight'] = round($gv['total_weight']/1000,2);
                        $prolist[$gk]['real_total_weight'] = round($gv['real_total_weight']/1000,2);
                    }
                }
            }
			if($prolist) $datalist[$key]['prolist'] = $prolist;
			$datalist[$key]['procount'] = Db::name('shop_order_goods')->where('orderid',$v['id'])->sum('num');
			$datalist[$key]['refundnum'] = Db::name('shop_order_goods')->where('orderid',$v['id'])->sum('refund_num');
            if(getcustom('product_refund')){
                $datalist[$key]['procount'] = $canRefundNum;
            }

			if($v['bid']!=0){
                $business = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->field('id,name,logo')->find();
				if(!$business) $business = ['id'=>$v['bid']];
				$commentdp = Db::name('business_comment')->where('orderid',$v['id'])->where('aid',aid)->where('mid',mid)->find();
				if($commentdp){
					$datalist[$key]['iscommentdp'] = 1;
				}else{
					$datalist[$key]['iscommentdp'] = 0;
				}
			} else {
                $business = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
            }
            $isNeedCard = 0;//是否需要上传海关所需身份证
            if(getcustom('product_supply_chain')){
                if($v['trade_type']=='1101'){
                    $orderSupplier = $supplierName?'['.$supplierName.'-保税]':'[保税]';
                }elseif($v['trade_type']=='1303'){
                    $orderSupplier = $supplierName?'['.$supplierName.'-海外直邮]':'[海外直邮]';
                }else{
                    $orderSupplier = $supplierName?'['.$supplierName.']':'';
                }
                if($v['product_type']==7 && $supplierName){
                    $business['name'] = $business['name'].$orderSupplier;
                }
                if($v['supplier_status']==200 && in_array($v['trade_type'],['1101','1303'])){
                    $isNeedCard = 1;
                }
            }
            $datalist[$key]['isNeedCard'] = $isNeedCard;
            $datalist[$key]['binfo'] = $business;

            $refundOrder = Db::name('shop_refund_order')->where('refund_status','>',0)->where('aid',aid)->where('orderid',$v['id'])->count();
            $datalist[$key]['refundCount'] = $refundOrder;
            //发票
            $datalist[$key]['invoice'] = 0;
            if($v['bid']) {
                $datalist[$key]['invoice'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->value('invoice');
            } else {
                $datalist[$key]['invoice'] = Db::name('admin_set')->where('aid',aid)->value('invoice');
            }
			$datalist[$key]['tips'] = '';
			if(getcustom('to86yk') && in_array($v['status'],[1,2,3])){
				foreach($datalist[$key]['prolist'] as $kp=>$vp){
					if($vp['to86yk_tid'] && $vp['to86yk_successnum'] < $vp['num']){
						$datalist[$key]['tips'] = '充值失败，请申请退款';
					}
				}
			}
            //独立订单是否允许退款
            $orderCanRefund = 1;
            if(getcustom('school_product')){
                if($v['school_id']){
                    $orderCanRefund = isset($schoollist[$v['school_id']])?$schoollist[$v['school_id']]:1;
                }
            }
            if(getcustom('shoporder_refund_sendcoupon')){
                //退款是否关联优惠券，关联则购买赠送的优惠券使用了，商品不能退款
                if($shopset['return_sendcoupon']){
                    $countCouponRecord = Db::name('coupon_record')->where('mid',mid)->where('source','shop')->where('orderid',$v['id'])->where('status',1)->count();
                    if($countCouponRecord) $orderCanRefund = 0;
                }
            }
            $datalist[$key]['order_can_refund'] = $orderCanRefund;

            $needWxpaylog = false;
            //发货信息录入 微信小程序+微信支付
            if($v['platform'] == 'wx' && $v['paytypeid'] == 2){
                $needWxpaylog = true;
            }
            if($needWxpaylog){
                $wxpaylog = [];
                //先查询流水号
                $pay_transaction = Db::name('pay_transaction')->where(['aid'=>aid,'ordernum'=>$v['ordernum'],'type'=>'shop','status'=>1])->order('id desc')->find();
                if($pay_transaction){
                    $wxpaylog = Db::name('wxpay_log')->where('aid',aid)->where('ordernum',$pay_transaction['transaction_num'])->where('tablename','shop')->find();
                }
                if(!$wxpaylog){
                    $wxpaylog = Db::name('wxpay_log')->where('aid',aid)->where('ordernum',$v['ordernum'])->where('tablename','shop')->field('ordernum,mch_id,transaction_id,openid,is_upload_shipping_info')->find();
                }
                $datalist[$key]['wxpaylog'] = $wxpaylog;
            }
            if(getcustom('product_handwork')){
        		//手工活回寄数量
	            $datalist[$key]['hand_num'] = Db::name('shop_order_goods')->where('orderid',$v['id'])->sum('hand_num');
	            //回寄记录
		        $handOrder = Db::name('shop_hand_order')->where('status','>=',0)->where('aid',aid)->where('orderid',$v['id'])->count();
		        $datalist[$key]['handCount'] = $handOrder;
		        //是否可以回寄
		        $canhand = false;
		        if($v['status'] == 3 && $v['ishand']){
		        	//回寄时间
		        	$collect_time = $v['collect_time']+$shopset['autoreturn_hwtime']*86400;
		        	$handtime = strtotime(date("Y-m-d",$collect_time).' 23:59:59');
		        	if(time()<=$handtime){
		        		$canhand = true;
		        	}
		        }
		        $datalist[$key]['canhand'] = $canhand;
	        }

            if(getcustom('shop_order_add_shipping_status')){
                $datalist[$key]['freight_type1_shipping_status'] = 1;
                if($v['freight_type'] == 1){
                    $datalist[$key]['express_com'] = '自提';
                    $datalist[$key]['express_no'] = $v['ordernum'];
                }
            }
            if(getcustom('shop_product_fenqi_pay')){
                if($v['is_fenqi'] == 1){
                    $fenqi_data = json_decode($v['fenqi_data'],true);
                    $datalist[$key]['fenqi_data'] = $fenqi_data;
                }
            }
            $datalist[$key]['transfer_order_parent_check'] = false;
            if(getcustom('transfer_order_parent_check') && $shopset['transfer_order_parent_check'] == 1 && $v['bid'] == 0){
                //查询权限组
                $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                //如果开启了设计组件分类多选
                if($user['auth_type'] == 1){
                    //转单给上级审核
                    $datalist[$key]['transfer_order_parent_check'] = true;
                }else{
                    $admin_auth = json_decode($user['auth_data'],true);
                    if(in_array('transferOrderParentCheck,transferOrderParentCheck',$admin_auth)){
                        //转单给上级审核
                        $datalist[$key]['transfer_order_parent_check'] = true;
                    }
                }
            }

            //是否可以换货
            $datalist[$key]['shop_order_exchange_product'] = false;
            if(getcustom('shop_order_exchange_product') && $shopset['exchange_product'] == 1){
                if($v['status'] == 2){
                    //已发货可以换货
                    $datalist[$key]['shop_order_exchange_product'] = true;
                }elseif ($v['status'] == 3 && $shopset['exchange_product_day'] > 0){
                    //已收货后N天内可以换货
                    $collect_time = $v['collect_time'] + $shopset['exchange_product_day'] * 86400;
                    if(time() < $collect_time){
                        $datalist[$key]['shop_order_exchange_product'] = true;
                    }
                }

                //判断是否有换货中的订单
                if(Db::name('shop_refund_order')->where('aid',aid)->where('orderid',$v['id'])->where('refund_type','exchange')->where('refund_status','in',[1,2,4,8])->find()){
                    $datalist[$key]['shop_order_exchange_product'] = false;
                }
            }
            if(getcustom('order_list_show_address')){
                //显示备注信息
                if($v['freight_id']){
                    $formfield = Db::name('freight')->where('id',$v['freight_id'])->find();
                    $formdataSet = json_decode($formfield['formdata'],true);
                    foreach($formdataSet as $k1=>$v1){
                        if($v1['val1'] == '备注'){
                            $message = Db::name('freight_formdata')->where('type','shop_order')->where('orderid',$v['id'])->value('form'.$k1);
                            $value = explode('^_^',$message);
                            if($value[1] !== ''){
                                $datalist[$key]['message'] = $value[1];
                            }
                            break;
                        }
                    }
                }
            }

            if(getcustom('supply_yongsheng')){
                if($v['source'] == 'supply_yongsheng' && $v['issource']){
                    $datalist[$key]['express_content'] = $express_content = \app\custom\SupplyYongsheng::dealExpressContent($v,'shop');
                }
            }

            if(getcustom('wx_express_intracity')){
                if($v['wxtc_wx_order_id'] && $v['status'] == 2){
                    $order_status = \app\custom\WxExpressIntracity::order_status;
                    $wxtc_order = Db::name('peisong_order_wx_express_intracity')->where('aid', aid)->where('orderid', $v['id'])->where('wx_order_id', $v['wxtc_wx_order_id'])->find();
                    $datalist[$key]['wxtc'] = 1;
                    $datalist[$key]['wxtc_status_name'] = $order_status[$wxtc_order['order_status']] ?? '';
                }
            }
            //确认收货奖励
			$datalist[$key]['is_collect_reward'] = $isCollectReward && $this->isCollectReward($v,$collectReward,$this->member['levelid']);
        }

        $datalist = \app\common\Order::formatOrderMoney($datalist);//金额格式化
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['codtxt'] = $shopset['codtxt'];
		$rdata['canrefund'] = $shopset['canrefund'];
		$rdata['st'] = $st;
		$rdata['showprice_dollar'] = false;
		if(getcustom('price_dollar')){
			$rdata['showprice_dollar'] = true;
		}
        $mendian_no_select = 0;
        if(getcustom('mendian_no_select')){
            //甘尔定制，不需要选择门店
            $mendian_no_select = 1;
        }
        $rdata['mendian_no_select'] = $mendian_no_select;
        if(getcustom('order_list_show_address')){
            $rdata['glass_order_custom'] = 1;
        }
        $rdata['mendian_no_select'] = $mendian_no_select;
        $rdata['collect_reward_set'] = $collectReward;
		return $this->json($rdata);
	}
	public function detail(){
        $channel = input('channel');//渠道参数，兼容小程序订单关联跳转(pagesExt/order/detail?id=${商品订单号}&channel=wxOrder) 后期可以扩展 文档 https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/order_center/order_center.html
        if($channel == 'wxOrder'){
            $detail = Db::name('shop_order')->where('ordernum',input('param.id'))->where('aid',aid)->where('mid',mid)->find();
        }else{
            $detail = Db::name('shop_order')->where('id',input('param.id/d'))->where('aid',aid)->where('mid',mid)->find();
            if(getcustom('showdownorderinfo') && !$detail){
                $detail = Db::name('shop_order')->where('id',input('param.id/d'))->where('aid',aid)->find();
            }
        }

		if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        $detail = \app\common\Order::formatOrderMoney($detail);//金额格式化

        $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
		$can_collect = true;
		if(getcustom('product_collect_time')){
			//查询可确认收货时间
	        $start_time = time()-$shopset['ordercollect_time']*24*60*60;

	        //查询确认收货状态
	        $can_collect = false;
	        if($detail['status']==2 && $detail['send_time'] && $detail['send_time']<=$start_time){
	            $can_collect = true;
	        }
		}
		$detail['can_collect'] = $can_collect;
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'shop_order');
		$detail['procount'] = Db::name('shop_order_goods')->where('orderid',$detail['id'])->sum('num');
		$detail['refundnum'] = Db::name('shop_order_goods')->where('orderid',$detail['id'])->sum('refund_num');

        if($detail['formdata']){
            foreach ($detail['formdata'] as $fk => $fv){
                //如果是多图
                if($fv[2] == 'upload_pics'){
                    if (getcustom('freight_upload_pics')){
                        $detail['formdata'][$fk][1] = explode(',',$fv[1]);
                    }else{
                        unset($detail['formdata'][$fk]);
                    }
                }
            }
        }

		$storeinfo = [];
		$storelist = [];
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
		}
        $mendian_no_select = 0;
        if(getcustom('mendian_no_select')){
            //甘尔定制，不需要选择门店
            $mendian_no_select = 1;
        }
        $mendianArr = [];
        if($mendian_no_select){
            $pro_ids = Db::name('shop_order_goods')->where('orderid',$detail['id'])->column('proid');
            $mendian_ids = Db::name('shop_product')->where('id','in',$pro_ids)->column('bind_mendian_ids');
            $mendian_ids = explode(',',implode(',',$mendian_ids));
            if(in_array('-1',$mendian_ids)){
                $limit_mendianids = [];
            }else{
                $limit_mendianids = $mendian_ids;
            }
            $whereb = [];
            $whereb[] = ['aid','=',aid];
            $whereb[] = ['status','=',1];
            if($limit_mendianids){
                $whereb[] = ['id','in',$limit_mendianids];
            }
            $mendianArr = Db::name('mendian')->where($whereb)->order($orderBy)->field('id,name,pic,longitude,latitude,address')->select()->toArray();
            $address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->order('isdefault desc,id desc')->find();
            if(!$address) $address = [];
            $longitude = $address['longitude'];
            $latitude = $address['latitude'];
            foreach($mendianArr as $k2=>$v2){
                //限定显示门店
                if($longitude && $latitude){
                    $v2['juli'] = '距离'.getdistance($longitude,$latitude,$v2['longitude'],$v2['latitude'],2).'千米';
                }else{
                    $v2['juli'] = '';
                }
                $mendianArr[$k2] = $v2;
            }
        }


		if($detail['bid'] > 0){
			$detail['binfo'] = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->field('id,name,logo')->find();
			if(!$detail['binfo']) $detail['binfo'] = [];
			$iscommentdp = 0;
			$commentdp = Db::name('business_comment')->where('orderid',$detail['id'])->where('aid',aid)->where('mid',mid)->find();
			if($commentdp) $iscommentdp = 1;
		}else{
			$iscommentdp = 1;
		}

		$prolist = Db::name('shop_order_goods')->where('orderid',$detail['id'])->select()->toArray();
		$isjici = 0;
        $show_product_xieyi = 0;
        $product_xieyi = [];
        $xieyi_ids = [];

        $field = 'comment,autoclose,canrefund';
        if(getcustom('probgcolor')){
            $field .= ',order_detail_toppic';
        }
        if(getcustom('product_handwork')){
            $field .= ',autoreturn_hwtime';
        }
        if(getcustom('transfer_order_parent_check')){
            $field .= ',transfer_order_parent_check';
        }
        if(getcustom('product_thali')){
            $field .= ',product_shop_school';
        }
        if(getcustom('shop_order_exchange_product')){
            $field .= ',exchange_product,exchange_product_day';
        }
        if(getcustom('shop_cod_onlinepay')){
            $field .= ',cod_onlinepay';
        }
        
        $procanrefund = 0;
        $canRefundNum = 0;
		foreach ($prolist as $pk=>$pv){
			if($pv['hexiao_code']) $isjici++;
            $prolist[$pk]['is_quanyi'] = (isset($pv['product_type']) && $pv['product_type']==8) ? 1 : 0;
            if(getcustom('product_xieyi') ){
               $xieyi_id = Db::name('shop_product')->where('id',$pv['proid'])->value('xieyi_id');
                if($xieyi_id && !in_array($xieyi_id,$xieyi_ids)){
                    $show_product_xieyi = 1;
                    $product_xieyi[] = Db::name('product_xieyi')->where('id',$xieyi_id)->field('name,content')->find();
                    $xieyi_ids[] = $xieyi_id;
                }
            }
            if(getcustom('shop_product_jialiao')){
                if($pv['jltitle']) {
                    $prolist[$pk]['ggname'] = $pv['ggname'] . '(' . rtrim($pv['jltitle'],'/') . ')';
                }
                $prolist[$pk]['sell_price'] = dd_money_format($pv['sell_price'] + $pv['jlprice']);
            }

            // 退款退货
            if(getcustom('product_refund')){
                $canrefund = Db::name('shop_product')->where('aid',aid)->where('id',$pv['proid'])->value('canrefund');
                if($canrefund == '-1'){
                    // 跟随系统
                    $prolist[$pk]['canrefund'] = $shopset['canrefund'];
                }else{
                    $prolist[$pk]['canrefund'] = $canrefund;
                }
                if($prolist[$pk]['canrefund'] == 1){
                    $procanrefund = 1;
                    $canRefundNum += $pv['num'];
                }
                
            }else{
                $prolist[$pk]['canrefund'] = $shopset['canrefund'];
            }
        }
        if(getcustom('product_refund')){
            $shopset['canrefund'] = $procanrefund;
            $detail['procount'] = $canRefundNum;
        }

		if($isjici >= count($prolist)) $detail['hexiao_qr'] = '';

		if($detail['status']==0 && $shopset['autoclose'] > 0 && $detail['paytypeid'] != 5){
			$lefttime = $detail['createtime'] + $shopset['autoclose']*60 - time();
			if($lefttime < 0) $lefttime = 0;
		}else{
			$lefttime = 0;
		}

		if(getcustom('douyin_groupbuy')){
            if($detail['status']==0 && $detail['isdygroupbuy']==1){
            	//查询抖音时间限制
            	$dyset = Db::name('douyin_groupbuy_set')->where('aid',aid)->where('bid',$detail['bid'])->field('status,autoclose')->find();
                if($dyset['autoclose'] > 0 && $detail['paytypeid'] != 5){
					$lefttime = $detail['createtime'] + $dyset['autoclose']*60 - time();
					if($lefttime < 0) $lefttime = 0;
				}else{
					$lefttime = 0;
				}
            }
        }

		//退款记录
        $refundOrder = Db::name('shop_refund_order')->where('refund_status','>',0)->where('aid',aid)->where('orderid',$detail['id'])->count();
        $refundingMoneyTotal = Db::name('shop_refund_order')->where('refund_status','in',[1,4])->where('aid',aid)->where('orderid',$detail['id'])->sum('refund_money');
        $refundedMoneyTotal = Db::name('shop_refund_order')->where('refund_status','=',2)->where('aid',aid)->where('orderid',$detail['id'])->sum('refund_money');
		$detail['refundCount'] = $refundOrder;
        $detail['refundingMoneyTotal'] = $refundingMoneyTotal;
        $detail['refundedMoneyTotal'] = $refundedMoneyTotal;
		if(getcustom('product_handwork')){
        	//手工活回寄数量
            $detail['hand_num'] = Db::name('shop_order_goods')->where('orderid',$detail['id'])->sum('hand_num');
            //回寄记录
	        $handOrder = Db::name('shop_hand_order')->where('status','>=',0)->where('aid',aid)->where('orderid',$detail['id'])->count();
	        $detail['handCount'] = $handOrder;
	        //是否可以回寄
	        $canhand = false;
	        $detail['handtime'] = '';//回寄时间
	        if($detail['status'] == 3 && $detail['ishand']){
	        	//回寄时间
	        	$collect_time = $detail['collect_time']+$shopset['autoreturn_hwtime']*86400;
	        	$handtime = strtotime(date("Y-m-d",$collect_time).' 23:59:59');
	        	$handtime2 = date("Y-m-d",$collect_time).' 23:59:59';
	        	$detail['handtime'] = '需在'.$handtime2.'之前回寄';
	        	if(time()<=$handtime){
	        		$canhand = true;
	        	}
	        }
	        $detail['canhand'] = $canhand;
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
		if($detail['freight_type']==11 && $detail['freight_content']){
			$detail['freight_content'] = json_decode($detail['freight_content'],true);
		}

		if($detail['checkmemid']){
			$detail['checkmember'] = Db::name('member')->field('id,nickname,headimg,realname,tel')->where('id',$detail['checkmemid'])->find();
		}else{
			$detail['checkmember'] = [];
		}
		$detail['payaftertourl'] = '';
		$detail['payafterbtntext'] = '';
		if(getcustom('payaftertourl') && in_array($detail['status'],['1','2','3'])){
            $showtourl = true;//是否展示支付后跳转
            if(getcustom('system_admin_payaftertourl_set')){
                //平台统一设置的支付跳转
                $payaftertourlSet = Db::name('sysset')->where('name','payaftertourl_set')->value('value');
                $payaftertourlSet = $payaftertourlSet && !empty($payaftertourlSet)? json_decode($payaftertourlSet,true):[];
                if($payaftertourlSet && $payaftertourlSet['status'] && $payaftertourlSet['status'] ==1 ){
                    $showtourl = false;
                    //if($payaftertourlSet['allurl'] && !empty($payaftertourlSet['allurl'])) $detail['payaftertourl'] = $payaftertourlSet['allurl'];
                    if($payaftertourlSet['shopproducturl'] && !empty($payaftertourlSet['shopproducturl'])) $detail['payaftertourl'] = $payaftertourlSet['shopproducturl'];
                    if($payaftertourlSet['shopproductbtntext'] && !empty($payaftertourlSet['shopproductbtntext'])) $detail['payafterbtntext'] = $payaftertourlSet['shopproductbtntext'];
                }
            }
            if($showtourl){
                foreach($prolist as $pro){
                    $product = Db::name('shop_product')->where('id',$pro['proid'])->find();
                    if($product['payaftertourl'] && $product['payafterbtntext']){
                        $detail['payaftertourl'] = $product['payaftertourl'];
                        $detail['payafterbtntext'] = $product['payafterbtntext'];
                        if(platform == 'mp' && strpos($product['payaftertourl'],'miniProgram::') === 0){
                            $afterurl = explode('|',str_replace('miniProgram::','',$detail['payaftertourl']));
                            $detail['payafter_username'] = $afterurl[2];
                            $appinfo = \app\common\System::appinfo(aid,platform);
                            $detail['payafter_path'] = $afterurl[1].(strpos($afterurl[1],'?')!==false ? '&' : '?') .'appid='.$appinfo['appid'].'&uid='.mid.'&ordernum='.$detail['ordernum'];
                        }
                    }
                }
            }
		}
        if(getcustom('product_glass')){
            foreach($prolist as $k=>$pro){
                if($pro['glass_record_id']){
                    $glassrecord = \app\model\Glass::orderGlassRecord($pro['glass_record_id'],aid);
                }
                $prolist[$k]['glassrecord'] = $glassrecord??'';
            }
        }
        $detail['message'] = \app\model\ShopOrder::checkOrderMessage($detail['id'],$detail);

        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
        $detail['is_pingce'] = $detail['is_pingce'] ?? 0;//兼容测评
		$rdata = [];
		$rdata['status'] = 1;
        //订单是否允许退款【在全局设置的基础上再控制退款】
        $detail['order_can_refund'] = 1;
        if(getcustom('school_product')){
            //如果是学校购入订单且设置了该学校不能退款
            if($detail['school_id']){
                $school = Db::name('school')->where('aid',aid)->where('id',$detail['school_id'])->find();
                if($school && $school['is_refund']===0){
                    $detail['order_can_refund'] = 0;
                    $detail['order_refund_tips'] = $school['refund_tips'];
                }
            }
        }
        if(getcustom('shoporder_refund_sendcoupon')){
            //退款是否关联优惠券，关联则购买赠送的优惠券使用了，商品不能退款
            if($shopset['return_sendcoupon']){
                $countCouponRecord = Db::name('coupon_record')->where('mid',mid)->where('source','shop')->where('orderid',$detail['id'])->where('status',1)->count();
                if($countCouponRecord) $detail['order_can_refund'] = 0;
            }
        }
        if(getcustom('product_weight') && $detail['product_type']==2){
            //称重商品，单价 重量kg
            foreach ($prolist as $k=>$v){
                $prolist[$k]['total_weight'] = round($v['total_weight']/1000,2);
                $prolist[$k]['real_total_weight'] = round($v['real_total_weight']/1000,2);
                $prolist[$k]['product_type'] = 2;
            }
        }
        if(getcustom('shop_order_add_shipping_status')){
            $detail['freight_type1_shipping_status'] = 1;
        }
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

        //如果是兑换订单显示兑换码
        if($detail['lipin_dhcode'] && $detail['paytype'] == '兑换码兑换'){
            $detail['paytype'] = $detail['paytype']."(".$detail['lipin_dhcode'].")";
        }

        $detail['transfer_order_parent_check'] = false;
        if(getcustom('transfer_order_parent_check') && $shopset['transfer_order_parent_check'] == 1 && $detail['bid'] == 0){
            //查询权限组
            $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            //如果开启了设计组件分类多选
            if($user['auth_type'] == 1){
                //转单给上级审核
                $detail['transfer_order_parent_check'] = true;
            }else{
                $admin_auth = json_decode($user['auth_data'],true);
                if(in_array('transferOrderParentCheck,transferOrderParentCheck',$admin_auth)){
                    //转单给上级审核
                    $detail['transfer_order_parent_check'] = true;
                }
            }
        }

        $detail['product_thali'] = false;
        if(getcustom('product_thali') && $shopset['product_shop_school'] == 1){
            $detail['product_thali'] = true;
        }

        //是否可以换货
        $detail['shop_order_exchange_product'] = false;
        if(getcustom('shop_order_exchange_product') && $shopset['exchange_product'] == 1){
            if($detail['status'] == 2){
                //已发货可以换货
                $detail['shop_order_exchange_product'] = true;
            }elseif ($detail['status'] == 3 && $shopset['exchange_product_day'] > 0){
                //已收货后N天内可以换货
                $collect_time = strtotime($detail['collect_time']) + $shopset['exchange_product_day'] * 86400;
                if(time() < $collect_time){
                    $detail['shop_order_exchange_product'] = true;
                }
            }

            //判断是否有换货中的订单
            if(Db::name('shop_refund_order')->where('aid',aid)->where('orderid',$detail['id'])->where('refund_type','exchange')->where('refund_status','in',[1,2,4,8])->find()){
                $detail['shop_order_exchange_product'] = false;
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

        if(getcustom('shop_code_exchangepage')){ 
            //卡密商品，查看是否设置了跳转
            if(($detail['status']==2 || $detail['status']==3) && $detail['freight_type']==4){
                if($detail['bid']>0){
                    $pageset = Db::name('business')->where('id',$detail['bid'])->field('exchange_page')->find();
                }else{
                    $pageset = Db::name('shop_sysset')->where('aid',aid)->field('exchange_page')->find();
                }
                if($pageset && $pageset['exchange_page']) $detail['exchangepage'] = 1;
            }
        }
        if(getcustom('shop_cod_onlinepay')) {
            $detail['cancodpay'] = false;
            //货到付款在线支付(同城配送)
            if($shopset['cod_onlinepay'] && $detail['status'] == 2 && !empty($detail['paytypeid']) && $detail['paytypeid'] == 4 && $detail['freight_id']){
                $freight =  Db::name('freight')->where('id',$detail['freight_id'])->where('aid',aid)->find();
                if($freight['pstype'] == 2){
                    //查询同城配送订单状态
                    $endpeisong = Db::name('peisong_order')->where('orderid',$detail['id'])->where('type','shop_order')->where('status',4)->count();
                    if($endpeisong) $detail['cancodpay'] = true;
                }
            }
        }

		$rdata['detail'] = $detail;
		$rdata['iscommentdp'] = $iscommentdp;
		$rdata['prolist'] = $prolist;
		$rdata['shopset'] = $shopset;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['storelist'] = $storelist;
        $rdata['mendianArr'] = $mendianArr;
		$rdata['lefttime'] = $lefttime;
		$rdata['codtxt'] = Db::name('shop_sysset')->where('aid',aid)->value('codtxt');

        if(getcustom('pay_transfer')){
            //转账汇款
            if($detail['paytypeid'] == 5) {
                $set = Db::name('admin_set')->where('aid',aid)->find();
                $pay_transfer = 1;
                $pay_transfer_info['pay_transfer_account_name'] = $pay_transfer ? $set['pay_transfer_account_name'] : '';
                $pay_transfer_info['pay_transfer_account'] = $pay_transfer ? $set['pay_transfer_account'] : '';
                $pay_transfer_info['pay_transfer_bank'] = $pay_transfer ? $set['pay_transfer_bank'] : '';
                $pay_transfer_info['pay_transfer_desc'] = $pay_transfer ? $set['pay_transfer_desc'] : '';
                $pay_transfer_info['pay_transfer_qrcode'] = $pay_transfer ? $set['pay_transfer_qrcode'] : '';
                $pay_transfer_info['pay_transfer_qrcode_arr'] = $set['pay_transfer_qrcode'] ? explode(',',$set['pay_transfer_qrcode']) : [];
                $rdata['pay_transfer_info'] = $pay_transfer_info;
                $payorder = Db::name('payorder')->where('id',$detail['payorderid'])->where('aid',aid)->find();
                If($payorder) {
                    If($payorder['check_status'] === 0) {
                        $payorder['check_status_label'] = '待审核';
                    }Elseif($payorder['check_status'] == 1) {
                        $payorder['check_status_label'] = '通过';
                    }Elseif($payorder['check_status'] == 2) {
                        $payorder['check_status_label'] = '驳回';
                    }Else{
                        $payorder['check_status_label'] = '未上传';
                    }
                }
                $rdata['payorder'] = $payorder ? $payorder : [];
            }
        }
		$rdata['showprice_dollar'] = false;
		if(getcustom('price_dollar')){
			$rdata['showprice_dollar'] = true;
		}

        //发票
        $rdata['invoice'] = 0;
        if($detail['bid']) {
            $rdata['invoice'] = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->value('invoice');
        } else {
            $rdata['invoice'] = Db::name('admin_set')->where('aid',aid)->value('invoice');
        }

		//定制 查看是否有工单可提交
		$rdata['isworkorder'] = 0;
		if(getcustom('workorder')){
			$workcount = 0+ Db::name('workorder_category')->where('aid',aid)->where('status',1)->where('isglorder',1)->count();
			$rdata['detail']['isworkorder']  = $workcount>0?1:0;
		}
        $needWxpaylog = false;
        //发货信息录入 微信小程序+微信支付
        if($detail['platform'] == 'wx' && $detail['paytypeid'] == 2){
            $needWxpaylog = true;
        }
        if($needWxpaylog){
            $wxpaylog = Db::name('wxpay_log')->where('aid',aid)->where('ordernum',$detail['ordernum'])->where('tablename','shop')->field('ordernum,mch_id,transaction_id,openid,is_upload_shipping_info')->find();
            $rdata['detail']['wxpaylog'] = $wxpaylog;
        }

        $rdata['mendian_no_select'] = $mendian_no_select;
        //产品协议
        $rdata['show_product_xieyi'] = $show_product_xieyi;
        $rdata['product_xieyi'] = $product_xieyi;
		return $this->json($rdata);
	}

	public function invoice()
    {
        $id = input('param.id/d');
        $post = input('post.');
        $order_type = input('param.type');

        $detail = Db::name($order_type.'_order')->where('id',$id)->where('aid',aid)->where('mid',mid)->find();
        if(empty($detail)) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        if($detail['refund_money']){
        	$detail['totalprice'] -= $detail['refund_money'];
        }
        if($detail['totalprice']<0){
        	$detail['totalprice'] = 0;
        }

        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        //发票
        $invoice = [];
        if($detail['bid']) {
            $invoice = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->find();
        } else {
            $invoice = Db::name('admin_set')->where('aid',aid)->find();
        }
        if(!$invoice['invoice']) return $this->json(['status'=>0,'msg'=>'未开启发票功能']);

        $info = Db::name('invoice')->where('order_type', $order_type)->where('orderid', $detail['id'])->find();

        if($post) {
            if($info['status'] == 1) {
                return $this->json(['status'=>0,'msg'=>'当前状态不可修改']);
            }
            $data = [
                'order_type' => $order_type,
                'orderid' => $detail['id'],
                'ordernum' => $detail['ordernum'],
                'type' => $post['formdata']['invoice_type'] ? $post['formdata']['invoice_type'] : 1,
                'invoice_name' => $post['formdata']['invoice_name'] ? $post['formdata']['invoice_name'] : '个人',
                'name_type' => $post['formdata']['name_type'] ? $post['formdata']['name_type'] : 1,
                'tax_no' => $post['formdata']['tax_no'] ? $post['formdata']['tax_no'] : '',
                'address' => $post['formdata']['address'] ? $post['formdata']['address'] : '',
                'tel' => $post['formdata']['tel'] ? $post['formdata']['tel'] : '',
                'bank_name' => $post['formdata']['bank_name'] ? $post['formdata']['bank_name'] : '',
                'bank_account' => $post['formdata']['bank_account'] ? $post['formdata']['bank_account'] : '',
                'mobile' => $post['formdata']['mobile'] ? $post['formdata']['mobile'] : '',
                'email' => $post['formdata']['email'] ? $post['formdata']['email'] : ''
            ];
            if(empty($info)) {
                $data['aid'] = aid;
                $data['bid'] = $detail['bid'];
                $data['create_time'] = time();
                Db::name('invoice')->insertGetId($data);
            } else {
                Db::name('invoice')->where('order_type', $order_type)->where('orderid', $detail['id'])->update($data);
            }

            return $this->json(['status'=>1,'msg'=>'操作成功']);
        }
        if($info) {
            if($info['status'] === 0) {
                $info['status_label'] = '待审核';
            }elseif($info['status'] == 1) {
                $info['status_label'] = '通过';
            }elseif($info['status'] == 2) {
                $info['status_label'] = '驳回';
            }else{
                $info['status_label'] = '未申请';
            }
        }
        $rdata['status'] = 1;
        $rdata['detail'] = $detail;
        $rdata['invoice'] = $info;
        $rdata['invoice_type'] = explode(',', $invoice['invoice_type']);
        return $this->json($rdata);
    }
	public function logistics(){
		$get = input('param.');
		//if($get['express_com'] == '同城配送'){
		if(strpos($get['express_com'], '同城配送') !== false){
		    if($get['type'] == 'express_wx'){
                $psorder = Db::name('express_wx_order')->where('id',$get['express_no'])->find();
                $psuser=['realname'=>$psorder['rider_name'],'tel'=>$psorder['rider_phone'],'latitude' => $psorder['rider_lat'],'longitude'=>$psorder['rider_lng']];
                $orderinfo = json_decode($psorder['orderinfo'],true);
                $binfo = json_decode($psorder['binfo'],true);
                $prolist = json_decode($psorder['prolist'],true);
                if($psorder['distance']> 1000){
                    $psorder['juli'] = round($psorder['distance']/1000,1);
                    $psorder['juli_unit'] = 'km';
                }else{
                    $psorder['juli']=$psorder['distance'];
                    $psorder['juli_unit'] = 'm';
                }
                //查询骑行距离
                $mapqq = new \app\common\MapQQ();
                $bicycl = $mapqq->getDirectionDistance($psorder['orderinfo']['longitude'],$psorder['orderinfo']['latitude'],$psuser['longitude'],$psuser['latitude'],1);
                if($bicycl && $bicycl['status']==1){
                    $juli2 = $bicycl['distance'];
                }else{
                    $juli2 = getdistance($psorder['orderinfo']['longitude'],$psorder['orderinfo']['latitude'],$psuser['longitude'],$psuser['latitude'],1);
                }
                $psorder['juli2'] = $juli2;
                if($juli2> 1000){
                    $psorder['juli2'] = round($juli2/1000,1);
                    $psorder['juli2_unit'] = 'km';
                }else{
                    $psorder['juli2_unit'] = 'm';
                }
            }else{
                $psorder = Db::name('peisong_order')->where('id',$get['express_no'])->find();
                if($psorder['psid'] == -2){
                    $psuser = ['realname'=>'','tel'=>'','latitude'=>'','longitude'=>''];
                    if(getcustom('express_maiyatian')) {
                        //查询关联表
                        $myt = Db::name('peisong_order_myt')->where('poid',$psorder['id'])->where('aid',aid)->find();
                        if($myt){
                            $psuser = [];
                            $psuser['realname'] = $myt['rider_name'];
                            $psuser['tel']      = $myt['rider_phone'];
                            $psuser['latitude'] = '';
                            $psuser['longitude']= '';
                        }
                        if($psorder['status']>=1 && $psorder['status']<=3){
                            //更新骑手位置
                            $res_location = \app\custom\MaiYaTianCustom::delivery_location($psorder['aid'],$psorder['bid'],[],$psorder['ordernum']);

                            if($res_location['status'] == 1){
                                $location_data = $res_location['data'];
                                if($location_data["rider_name"])      $psuser['realname']    = $location_data["rider_name"];
                                if($location_data["rider_phone"])     $psuser['tel']         = $location_data["rider_phone"];
                                if($location_data["rider_longitude"]) $psuser['longitude']   = $location_data["rider_longitude"];
                                if($location_data["rider_latitude"])  $psuser['latitude']    = $location_data["rider_latitude"];
                            }
                        }
                    }
                }else if($psorder['psid']<0){
                    $psuser=['realname'=>$psorder['make_rider_name'],'tel'=>$psorder['make_rider_mobile']];
                }else{
                    $psuser = Db::name('peisong_user')->where('id',$psorder['psid'])->find();
                }

                if(getcustom('wx_express_intracity')){
                    $psorder = Db::name('peisong_order')->where('wxtc_trans_order_id',$get['express_no'])->find();
                    $peisong_order_wx = Db::name('peisong_order_wx_express_intracity')->where('trans_order_id',$get['express_no'])->find();

                    $psuser = [];
                    $psuser['realname'] = $peisong_order_wx['rider_name'];
                    $psuser['tel']      = $peisong_order_wx['rider_phone'];
                    $psuser['latitude'] = $peisong_order_wx['rider_latitude'];
                    $psuser['longitude']= $peisong_order_wx['rider_longitude'];
                }

                $orderinfo = json_decode($psorder['orderinfo'],true);
                $binfo = json_decode($psorder['binfo'],true);
                $prolist = json_decode($psorder['prolist'],true);
                if($psorder['juli']> 1000){
                    $psorder['juli'] = round($psorder['juli']/1000,1);
                    $psorder['juli_unit'] = 'km';
                }else{
                    $psorder['juli_unit'] = 'm';
                }

                if($psuser['longitude'] && $psuser['latitude']){
                    //查询骑行距离
                    $mapqq = new \app\common\MapQQ();
                    $bicycl = $mapqq->getDirectionDistance($psorder['longitude2'],$psorder['latitude2'],$psuser['longitude'],$psuser['latitude'],1);
                    if($bicycl && $bicycl['status']==1){
                        $juli2 = $bicycl['distance'];
                    }else{
                        $juli2 = getdistance($psorder['longitude2'],$psorder['latitude2'],$psuser['longitude'],$psuser['latitude'],1);
                    }
                    $psorder['juli2'] = $juli2;
                    if($juli2> 1000){
                        $psorder['juli2'] = round($juli2/1000,1);
                        $psorder['juli2_unit'] = 'km';
                    }else{
                        $psorder['juli2_unit'] = 'm';
                    }
                }else{
                    $psorder['juli2']      = '无';
                    $psorder['juli2_unit'] = '';
                }

                $psorder['leftminute'] = ceil(($psorder['yujitime'] - time()) / 60);

                $psorder['ticheng'] = round($psorder['ticheng'],2);
                if($psorder['status']==4){
                    $psorder['useminute'] = ceil(($psorder['endtime'] - $psorder['createtime']) / 60);
                    $psorder['useminute2'] = ceil(($psorder['endtime'] - $psorder['starttime']) / 60);
                }
            }
            if(getcustom('paotui')){
	            if($orderinfo && $orderinfo['expect_take_time'] && $orderinfo['expect_take_time']>0){
	            	$orderinfo['expect_take_time'] = date("Y-m-d H:i",$orderinfo['expect_take_time']);
	            }else{
	            	$orderinfo['expect_take_time'] = '立即取件';
	            }
	        }
			$rdata = [];
			$rdata['psorder'] = $psorder;
			$rdata['binfo'] = $binfo;
			$rdata['psuser'] = $psuser;
			$rdata['orderinfo'] = $orderinfo;
			$rdata['prolist'] = $prolist;
			if(getcustom('paotui')){
				$admin = Db::name('admin_set')->where('aid',aid)->field('tel')->find();
				$rdata['shop_tel'] = $admin && $admin['tel']? $admin['tel']:'';
			}
			return $this->json($rdata);
		}elseif($get['express_com'] == '货运托运'){
			$data = Db::name('freight_type10_record')->where('id',$get['express_no'])->find();
			return $this->json(['datalist'=>$data]);
		}elseif($get['express_com'] == '自提') {
            $list = Db::name('shop_order_shipping_log')->field('id,aid,ordernum,freight_message,freight_time,remark')->where('aid',aid)->where('ordernum',$get['express_no'])
                ->order('freight_time','desc')->select()->toArray();
            if($list){
                foreach ($list as $k => $row){
                    $list[$k] = ['time'=>date('Y-m-d H:i',$row['freight_time']),'context'=>$row['freight_message']];
                }
            }
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['datalist'] = $list;
            return $this->json($rdata);
        }else{
            $getwuliu = true;//是否请求接口
            if(getcustom('supply_yongsheng')){
                if(input('?param.ordertype') && input('?param.orderid')){
                    $ordertype = input('param.ordertype');
                    $order = Db::name($ordertype.'_order')->where('id',input('param.orderid/d'))->find();
                    if($order && $order['source'] == 'supply_yongsheng' && $order['issource']){
                        $getwuliu = false;
                        $list = \app\custom\SupplyYongsheng::dealexpress2($order,$get['express_no'],$get['express_com'],$ordertype);
                    }
                }
            }
            if($getwuliu){
    			if($get['express_com'] == '顺丰速运' || $get['express_com'] == '中通快递'){
    				$totel = Db::name('shop_order')->where('aid',aid)->where(function ($query) use ($get) {
                        $query->where('express_no',$get['express_no'])->whereOr('express_content','like','%'.$get['express_no'].'%');
                    })->value('tel');
    				if(!$totel){
    					$totel = Db::name('seckill_order')->where('aid',aid)->where('express_no',$get['express_no'])->value('tel');
    				}
    				if(!$totel){
    					$totel = Db::name('collage_order')->where('aid',aid)->where('express_no',$get['express_no'])->value('tel');
    				}
    				if(!$totel){
    					$totel = Db::name('kanjia_order')->where('aid',aid)->where('express_no',$get['express_no'])->value('tel');
    				}
    				if(!$totel){
    					$totel = Db::name('scoreshop_order')->where('aid',aid)->where('express_no',$get['express_no'])->value('tel');
    				}
                    if(getcustom('shop_order_exchange_product')) {
                        if (!$totel) {
                            if ($orderid = Db::name('shop_refund_order')->where('aid', aid)->where('exchange_express_no', $get['express_no'])->value('orderid')) {
                                $totel = Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->value('tel');
                            }
                        }
                    }
    				$get['express_no'] = $get['express_no'].":".substr($totel,-4);
    			}
                $list = \app\common\Common::getwuliu($get['express_no'],$get['express_com'],'', aid);
            }
			$rdata = [];
			$rdata['datalist'] = $list;
			return $this->json($rdata);
		}
	}

	function closeOrder(){
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(!$order || $order['status']!=0){
			return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
		}
        $rs = Db::name('shop_order')->where('id',$orderid)->where('status',0)->where('aid',aid)->where('mid',mid)->update(['status'=>4]);
        if(!$rs)  return $this->json(['status'=>0,'msg'=>'操作失败']);
        Db::name('shop_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('mid',mid)->update(['status'=>4]);

		//加库存
		$oglist = Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$orderid)->select()->toArray();
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
                Db::name('shop_guige')->where('aid',aid)->where('id',$og['ggid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
                Db::name('shop_product')->where('aid',aid)->where('id',$og['proid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
            }
			if($og['seckill_starttime']){
				Db::name('seckill_prodata')->where('aid',aid)->where('proid',$og['proid'])->where('ggid',$og['ggid'])->where('starttime',$og['seckill_starttime'])->dec('sales',$og['num'])->update();
			}
			if(getcustom('guige_split')){
				\app\model\ShopProduct::addlinkstock($og['proid'],$og['ggid'],$og['num']);
			}
			if(getcustom('ciruikang_fenxiao')){
                //是否开启了商城商品需上级购买足量
                $deal_ogstock2 = \app\custom\CiruikangCustom::deal_ogstock2($order,$og,$og['num'],'下级订单关闭');
            }
            //商品库存为大于0时，上架商品
            if(getcustom('product_nostock_show')) {
                $stock_pro = Db::name('shop_product')->where('aid',aid)->where('id',$og['proid'])->field('stock,status,ischecked')->find();
                if($stock_pro['stock'] > 0 && $stock_pro['status'] == 0 && $stock_pro['ischecked'] == 1){
                    Db::name('shop_product')->where('aid',aid)->where('id',$og['proid'])->update(['status' =>1]);
                }
            }
            if(getcustom('product_chinaums_subsidy') && $og['subsidy_id'] && $og['cred_frozen_no']){
                $subsidy = new \app\custom\ChinaumsSubsidy(aid);
                $og['auth_code'] = Db::name('chinaums_subsidy_apply')->where('id',$og['subsidy_id'])->value('auth_code');
                $cancelRes = $subsidy->authCodeCancel($og);
                if($cancelRes['respCode'] == '000000'){
                    Db::name('chinaums_subsidy_apply')->where('id',$og['subsidy_id'])->update(['status'=>1]);
                }
            }
            if(getcustom('deposit')) {
                if ($og['deposit_hexiao_num'] > 0) {
                    \app\common\Member::addDeposit(aid, $og['bid'], $og['mid'], $og['deposit_hexiao_num'], $og['deposit_id'], '取消订单解冻' . t('押金'), ['orderid' => $order['id']]);
                }
            }
		}
		//优惠券抵扣的返还
		if($order['coupon_rid']){
//			Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('id','in',$order['coupon_rid'])->update(['status'=>0,'usetime'=>'']);
            \app\common\Coupon::refundCoupon(aid,mid,$order['coupon_rid'],$order);
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
        	if($order['combine_money']>0){
                $res = \app\common\Member::addmoney(aid,$order['mid'],$order['combine_money'],t('余额').'组合支付返回，订单号: '.$order['ordernum']);
                if($res['status'] ==1){
                    Db::name('shop_order')->where('id',$orderid)->update(['combine_money'=>0]);
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
            if($order['dedamount_dkmoney']>0){
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
        if(getcustom('deposit')){
            //计次券 应该返回次数
            if($order['water_coupon_rid']){
                $couponrecord_list = Db::name('coupon_record')->where('aid',aid)->where('mid',$order['mid'])->where('id','in',$order['water_coupon_rid'])->select()->toArray();
                $water_coupon_num_array = explode(',',$order['water_coupon_num']);
                foreach($couponrecord_list as $key=>$couponrecord){
                    $water_coupon_num = $water_coupon_num_array[$key];//对应的数量
                    $used_count = $couponrecord['used_count'] - $water_coupon_num;
                    $update =[
                        'status' => 0,
                        'used_count'=> $used_count <=0?0:$used_count
                    ];
                    Db::name('coupon_record')->where('aid',aid)->where(['mid'=>$order['mid'],'id'=>$couponrecord['id']])->update($update);
                    //核销记录删除
                    Db::name('hexiao_order')->where('aid',aid)->where('ordernum',$order['ordernum'])->where('orderid',$couponrecord['id'])->limit($water_coupon_num)->delete();
                }
            }
        }

		if($order['platform'] == 'toutiao'){
			\app\common\Ttpay::pushorder(aid,$order['ordernum'],2);
		}
        if(getcustom('product_supply_chain')){
            if($order['product_type']==7){
                \app\custom\Chain::orderCancel(aid,$order['id']);
            }
        }
        if(getcustom('transfer_order_parent_check')){
            Db::name('transfer_order_parent_check_log')->where('orderid',$orderid)->where('aid',aid)->where('status',0)->where('hide',0)->update(['status'=>2,'examinetime'=>time()]);

            //关闭订单减分销数据统计的单量
            \app\common\Fenxiao::decTransferOrderCommissionTongji(aid, mid, $order['id'], 1);
        }
        \app\common\Order::order_close_done(aid,$orderid,'shop');
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
    
	public function delOrder(){
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(!$order || ($order['status']!=4 && $order['status']!=3)){
			return $this->json(['status'=>0,'msg'=>'删除失败,订单状态错误']);
		}
        \app\common\Order::order_close_done(aid,$orderid,'shop');

        $rs = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->update(['delete'=>1]);

		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}

	public function orderCollect(){ //确认收货
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
        if(empty($order)) return $this->json(['status'=>0,'msg'=>'订单不存在']);

        $rsCheck = \app\common\Order::collectCheck(aid,$orderid,$order,mid);
        if($rsCheck['status'] != 1) return $this->json($rsCheck);
        $order['collect_reward_platform'] = platform; //确认收货奖励判断平台
		$rs = \app\common\Order::collect($order,'shop');
		if($rs['status'] == 0) return $this->json($rs);

		Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);
		Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$orderid)->update(['status'=>3,'endtime'=>time()]);
		if(getcustom('ciruikang_fenxiao')){
            //一次购买升级
            \app\common\Member::uplv(aid,mid,'shop',['onebuy'=>1,'onebuy_orderid'=>$order['id']]);
        }else{
            \app\common\Member::uplv(aid,mid);
        }

        if(getcustom('member_shougou_parentreward')){
            //首购解锁
            Db::name('member_commission_record')->where('orderid',$order['id'])->where('type','shop')->where('status',0)->where('islock',1)->where('aid',$order['aid'])->where('remark','like','%首购奖励')->update(['islock'=>0]);
        }

        //确认收货奖励
        $collectReward = $this->getCollectReward(aid,mid,$orderid);
		$return = ['status'=>1,'msg'=>'确认收货成功','url'=>true,'collect_reward' => $collectReward];
		if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
			//确认收货
			$rs = \app\custom\Jushuitan::createOrder($order,'TRADE_FINISHED');
		}
        if(getcustom('product_supply_chain')){
            if($order['product_type']==7){
                $rs = \app\custom\Chain::orderConfirm(aid,$orderid);
            }
        }
        if(getcustom('transfer_order_parent_check')) {
            //确认收货增加有效金额
            \app\common\Fenxiao::addTotalOrderNum(aid, mid, $order['id'],3);
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
            $user_info = Db::name('member')->where('aid',aid)->where('id',mid)->field('shop_commission_withdraw_limit,commission_withdraw_limit_infinite')->find();
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
                $collectOrdrMoney = Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('status',3)->sum('totalprice');
                if($collectOrdrMoney >= $this->sysset['shop_consume_money_give_infinite']){
                    //增加佣金提现额度-无限制
                    Db::name('member')->where('aid', aid)->where('id', mid)->update(['commission_withdraw_limit_infinite' =>1]);
                }
            }
        }
        if(getcustom('erp_hupun')){
            //万里牛erp
            $wln = new \app\custom\Hupun($order['aid']);
            $wln->orderPush($order['id']);
        }
		$tmplcontent = [];
		$tmplcontent['first'] = '有订单客户已确认收货';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $this->member['nickname'];
		$tmplcontent['keyword2'] = $order['ordernum'];
		$tmplcontent['keyword3'] = $order['totalprice'].'元';
		$tmplcontent['keyword4'] = date('Y-m-d H:i',$order['paytime']);
        $tmplcontentNew = [];
        $tmplcontentNew['thing3'] = $this->member['nickname'];//收货人
        $tmplcontentNew['character_string7'] = $order['ordernum'];//订单号
        $tmplcontentNew['time8'] = date('Y-m-d H:i');//送达时间
		\app\common\Wechat::sendhttmpl(aid,$order['bid'],'tmpl_ordershouhuo',$tmplcontent,m_url('admin/order/shoporder'),$order['mdid'],$tmplcontentNew);

		$tmplcontent = [];
		$tmplcontent['thing2'] = $order['title'];
		$tmplcontent['character_string6'] = $order['ordernum'];
		$tmplcontent['thing3'] = $this->member['nickname'];
		$tmplcontent['date5'] = date('Y-m-d H:i');
		\app\common\Wechat::sendhtwxtmpl(aid,$order['bid'],'tmpl_ordershouhuo',$tmplcontent,'admin/order/shoporder',$order['mdid']);

		return $this->json($return);
	}
    //确认收货 前 验证
    public function orderCollectBefore(){
        $post = input('post.');
        $orderid = intval($post['orderid']);
        $rs = \app\common\Order::collectCheck(aid,$orderid,[],mid);

        return $this->json($rs);
    }

	//退款单列表
    public function refundList(){
        $st = input('param.st');
        if(!input('param.st') || $st === ''){
            $st = 'all';
        }

        $shopset = Db::name('shop_sysset')->where('aid',aid)->find();

        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['mid','=',mid];
        $where[] = ['delete','=',0];
        if(input('param.keyword')) $where[] = ['ordernum|refund_ordernum|title', 'like', '%'.input('param.keyword').'%'];
        if($st == 'all'){

        }elseif($st == '0'){
            $where[] = ['refund_status','=',0];
        }elseif($st == '1'){
            $where[] = ['refund_status','=',1];
        }elseif($st == '2'){
            $where[] = ['refund_status','=',2];
        }elseif($st == '3'){
            if(getcustom('shop_order_exchange_product') && $shopset['exchange_product'] == 1){
                $where[] = ['refund_status','in','3,6'];
            }else{
                $where[] = ['refund_status','=',3];
            }
        }elseif($st == '4'){
            $where[] = ['refund_status','=',8];
        }

        $pernum = 10;
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;

        if(input('param.orderid/d')) {
            $where[] = ['orderid','=',input('param.orderid/d')];
            $pernum = 99;
        }

        $datalist = Db::name('shop_refund_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
        if(!$datalist) $datalist = array();
        foreach($datalist as $key=>$v){
            $datalist[$key]['prolist'] = Db::name('shop_refund_order_goods')->where('refund_orderid',$v['id'])->select()->toArray();
            if(!$datalist[$key]['prolist']) $datalist[$key]['prolist'] = [];
            $datalist[$key]['procount'] = Db::name('shop_refund_order_goods')->where('refund_orderid',$v['id'])->sum('refund_num');
            if($v['bid']!=0){
                $datalist[$key]['binfo'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->field('id,name,logo')->find();
            } else {
                $datalist[$key]['binfo'] = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
            }
            if($v['refund_type'] == 'refund') {
                $datalist[$key]['refund_type_label'] = '退款';
            }elseif($v['refund_type'] == 'return') {
                $datalist[$key]['refund_type_label'] = '退货退款';
            }elseif($v['refund_type'] == 'exchange') {
                $datalist[$key]['refund_type_label'] = '换货';
            }
        }
        $rdata = [];
        $rdata['datalist'] = $datalist;
        $rdata['st'] = $st;

        //是否开启换货
        $rdata['shop_order_exchange_product'] = false;
        if(getcustom('shop_order_exchange_product') && $shopset['exchange_product'] == 1){
            $rdata['shop_order_exchange_product'] = true;
        }
        return $this->json($rdata);
    }
    public function refundDetail()
    {
        $detail = Db::name('shop_refund_order')->where('id',input('param.id/d'))->where('aid',aid)->where('mid',mid)->find();
        if(!$detail) $this->json(['status'=>0,'msg'=>'退款单不存在']);
        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
        $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
        $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
        $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
        if($detail['refund_type'] == 'refund') {
            $detail['refund_type_label'] = '退款';
        }elseif($detail['refund_type'] == 'return') {
            $detail['refund_type_label'] = '退货退款';
        }elseif($detail['refund_type'] == 'exchange') {
            $detail['refund_type_label'] = '换货';
        }
        if($detail['refund_pics']) {
            $detail['refund_pics'] = explode(',', $detail['refund_pics']);
        }
        $show_return_component = false;
        if(getcustom('return_component')){
            $show_return_component = \app\model\ShopOrder::checkReturnComponent(aid,$detail['bid']);
//            $detail['return_info'] = \app\common\WxDelivery::returnQuery(aid,$detail['return_id']);
        }
        $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
        if($detail['bid'] > 0){
            $detail['binfo'] = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->field('id,name,logo')->find();
        }else{
            $detail['binfo'] = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
        }

        $prolist = Db::name('shop_refund_order_goods')->where('refund_orderid',$detail['id'])->select()->toArray();

        //判断是否有回寄信息
        if($detail['refund_status'] ==4 && !$detail['isexpress']){
        	if(!$detail['return_address'] && !$detail['return_name']){
        		$detail['return_address'] = '等待商家填写';
        	}
        }

        //换新商品
        if(getcustom('shop_order_exchange_product')){
            $newprolist = Db::name('shop_refund_order_goods_exchange')->where('aid',aid)->where('refund_orderid',$detail['id'])->select()->toArray();
        }

        $rdata = [];
        $rdata['detail'] = $detail;
        $rdata['prolist'] = $prolist;
        $rdata['newprolist'] = $newprolist ?? [];
        $rdata['show_return_component'] = $show_return_component;
        $bid = $detail['bid']??0;

        $expressdata = array_keys(express_data(['aid'=>aid,'bid'=>$bid]));
        if(getcustom('supply_zhenxin')){
            if($detail['issource'] && $detail['source'] == 'supply_zhenxin'){
                $getExpress = \app\custom\SupplyZhenxinCustom::getExpress(aid,$detail['bid']);
                if(!$getExpress || $getExpress['status'] != 1){
                    $expressdata = [];
                }else{
                    $expressdata = [];
                    foreach($getExpress['data'] as $gv){
                        $expressdata[] = $gv['name'];
                    }
                    unset($gv);
                }
            }
        }
        $rdata['expressdata'] = $expressdata;
        //是否开启换货
        $rdata['shop_order_exchange_product'] = false;
        if(getcustom('shop_order_exchange_product') && $shopset['exchange_product'] == 1){
            $rdata['shop_order_exchange_product'] = true;
        }
        return $this->json($rdata);
    }
    public function refundOrderClose(){
        $post = input('post.');
        $id = intval($post['id']);
        Db::startTrans();
        $order = Db::name('shop_refund_order')->lock(true)->where('id',$id)->where('aid',aid)->where('mid',mid)->find();
        if(!$order || !in_array($order['refund_status'], [1,4,6])){
            Db::rollback();
            return $this->json(['status'=>0,'msg'=>'关闭失败,退款单状态错误']);
        }
        if(getcustom('supply_zhenxin')){
            if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                $cancelrefund = \app\custom\SupplyZhenxinCustom::cancelrefund(aid,$order['bid'],$order['zxservice_sn']);
                if(!$cancelrefund || $cancelrefund['status'] != 1){
                    $msg = $cancelrefund && $cancelrefund['msg']?$cancelrefund['msg']:'关闭失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
            }
        }
        $rupdate = ['refund_status'=>0];
        if(getcustom('return_component')){
            //只有returnquery.status=2 用户自主填写运单时才可解绑
            $isopen = \app\model\ShopOrder::checkReturnComponent(aid,$order['bid']);
            if($isopen){
                $unbindRes = \app\common\WxDelivery::returnUnbind(aid,$order['return_id']);
            }
        }
        Db::name('shop_refund_order')->where('id',$id)->where('aid',aid)->where('mid',mid)->update($rupdate);
//        $rs = Db::name('shop_order')->where('id',$order['orderid'])->where('aid',aid)->where('mid',mid)->update(['refund_status'=>0]);
        $og = Db::name('shop_refund_order_goods')->where('refund_orderid',$id)->where('aid',aid)->where('mid',mid)->select()->toArray();
        foreach ($og as $item) {
            if($order['refund_type'] != 'exchange'){
                //恢复退款数量
                Db::name('shop_order_goods')->where('id',$item['ogid'])->where('orderid',$order['orderid'])->where('aid',aid)->where('mid',mid)
                    ->dec('refund_num', $item['refund_num'])->update();
            }
        }

		Db::commit();
		if($order['fromwxvideo'] == 1){
			\app\common\Wxvideo::aftersaleupdate($order['orderid'],$order['id']);
		}
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }
	//退款
	public function refundinit(){
        $type = input('type');
	    //查询订单信息
        $detail = Db::name('shop_order')->where('id',input('param.id/d'))->where('aid',aid)->where('mid',mid)->find();
        if(!$detail)
            return $this->json(['status'=>0,'msg'=>'订单不存在']);
        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
        $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
        $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
        $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';

        $setfield = 'comment,autoclose,refundpic';
        if(getcustom('shop_order_exchange_product')){
            $setfield .= ',exchange_product,exchange_product_day';
        }
        if(getcustom('product_refund')){
            $setfield .= ',canrefund';
        }
        $shopset = Db::name('shop_sysset')->where('aid',aid)->field($setfield)->find();

        $storeinfo = [];
        if($detail['freight_type'] == 1){
            if($detail['bid'] == 0){
                $storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('name,address,longitude,latitude')->find();
            }else{
                $storeinfo = Db::name('business')->where('id',$detail['bid'])->field('name,address,longitude,latitude')->find();
            }
        }

        if($detail['bid'] > 0){
            $detail['binfo'] = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->field('id,name,logo')->find();
        }

        //新增指定商品退款，默认为整单处理
        $ogid = input('ogid', 0);
        $refundMoneyWhere = [
            ["rog.orderid", "=", $detail['id']],
            ["rog.aid", "=", aid],
            ["ro.refund_status", "in", [1,2,4]]
        ];
        if(!empty($ogid)){
            $refundMoneyWhere[] = [
                "rog.ogid", "=", $ogid
            ];
        }

        $refundMoneySum = Db::name('shop_refund_order_goods')->alias('rog')->join('shop_refund_order ro', 'rog.refund_orderid=ro.id')->where($refundMoneyWhere)->sum('rog.refund_money');

        $canRefundNum = 0;
        $totalNum = Db::name('shop_order_goods')->where("orderid", $detail['id'])->sum(Db::raw('num-refund_num'));
        $returnTotalprice = 0;

        $ogwhere = [
            [
                'orderid', '=', $detail['id']
            ]
        ];
        if(!empty($ogid)){
            $ogwhere[] = [
                "id", "=", $ogid
            ];
        }
        $prolist = Db::name('shop_order_goods')->where($ogwhere)->select()->toArray();

        //是否可以换货
        $detail['shop_order_exchange_product'] = false;
        if(getcustom('shop_order_exchange_product') && $shopset['exchange_product'] == 1){
            if($detail['status'] == 2){
                //已发货可以换货
                $detail['shop_order_exchange_product'] = true;
            }elseif ($detail['status'] == 3 && $shopset['exchange_product_day'] > 0){
                //已收货后N天内可以换货
                $collect_time = strtotime($detail['collect_time']) + $shopset['exchange_product_day'] * 86400;
                if(time() < $collect_time){
                    $detail['shop_order_exchange_product'] = true;
                }
            }

            if($detail['shop_order_exchange_product']){
                //如过订单所有商品都换过货则不能换货
                foreach ($prolist as $key => $item1) {
                    $detail['shop_order_exchange_product'] = false;
                    if(!Db::name('shop_refund_order_goods_exchange')->where('aid',aid)->where('ogid',$item1['id'])->count()){
                        $detail['shop_order_exchange_product'] = true;
                        break;
                    }
                }
            }
        }
        $untotalprice = 0;
        foreach ($prolist as $key => $item) {
            $prolist[$key]['canRefundNum'] = $item['num'] - $item['refund_num'];
//            $totalNum += $item['num'];
            $canRefundNum += $item['num'] - $item['refund_num'];
//            $returnTotalprice += $item['real_totalprice'] / $item['num'] * ($item['num'] - $item['refund_num']);
            if(getcustom('shop_order_exchange_product') && $type =='exchange'){
                $prolist[$key]['checked'] = false;
                if(Db::name('shop_refund_order_goods_exchange')->where('aid',aid)->where('ogid',$item['id'])->count()){
                    //如果此商品有换货记录则不显示
                    unset($prolist[$key]);
                }
            }
            // 退款退货
            if(getcustom('product_refund')){
                $canrefund = Db::name('shop_product')->where('aid',aid)->where('id',$item['proid'])->value('canrefund');
                if($canrefund == '-1'){
                    // 跟随系统
                    $canrefund = $shopset['canrefund'];
                }
                if($canrefund == 0){
                    $untotalprice += $prolist[$key]['real_totalmoney'];
                    unset($prolist[$key]);
                }
            }
        }

        $prolistNew = [];
        if($prolist){
            foreach ($prolist as $value) {
                $prolistNew[] = $value;
            }
        }

		$totalprice = $detail['totalprice'];
		if($detail['balance_price'] > 0 && $detail['balance_pay_status'] == 0){
			$totalprice = $totalprice - $detail['balance_price'];
		}
        if($canRefundNum != $totalNum) {
            //选中的可退款数量和总数量不相同，则表示订单相关其他费用不用退回（例如运费）,重置总的可退款金额
            $totalprice = Db::name('shop_order_goods')->where($ogwhere)->sum('real_totalmoney');
        }else{
            $refundMoneySum = Db::name('shop_refund_order')->where('orderid',$detail['id'])->where('aid',aid)->whereIn('refund_status',[1,2,4])->sum('refund_money');
        }
        if($untotalprice > 0){
            $totalprice -= $untotalprice;
        }
        $returnTotalprice = $totalprice - $refundMoneySum;
        $returnTotalprice = max($returnTotalprice, 0);
        //可退款金额=总金额-审核中-已退款
        $detail['canRefundNum'] = $canRefundNum;
        $detail['totalNum'] = $totalNum;
        $detail['returnTotalprice'] = round($returnTotalprice,2);
//        if($canRefundNum == 0) {
//            return $this->json(['status'=>0,'msg'=>'当前订单没有可退款的商品']);
//        }
        //todo 确认收货后的退款

        if(getcustom('wx_express_intracity')){
            if($detail['wxtc_wx_order_id']){
                $detail['wx_express_intracity'] = true;
                //取消配送违约金
                $deductfee = \app\custom\WxExpressIntracity::getDeductfee(aid,['wxtc_wx_order_id' => $detail['wxtc_wx_order_id']]);
                $cancel_reason = [ "不需要了","信息填错", "无人接单", "其他",];
                $detail['wxtc_cancel_reason_arr'] = $cancel_reason;
                $detail['wxtc_deductfee'] = 0;//$deductfee;
            }
        }

        $rdata = [];
        $rdata['status'] = 1;
        $rdata['detail'] = $detail;
        $rdata['prolist'] = $prolistNew;
        $rdata['shopset'] = $shopset;
        $rdata['storeinfo'] = $storeinfo;

		//订阅消息
		$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
		$tmplids = [];

		if($wx_tmplset['tmpl_tuisuccess_new']){
			$tmplids[] = $wx_tmplset['tmpl_tuisuccess_new'];
		}elseif($wx_tmplset['tmpl_tuisuccess']){
			$tmplids[] = $wx_tmplset['tmpl_tuisuccess'];
		}
		if($wx_tmplset['tmpl_tuierror_new']){
			$tmplids[] = $wx_tmplset['tmpl_tuierror_new'];
		}elseif($wx_tmplset['tmpl_tuierror']){
			$tmplids[] = $wx_tmplset['tmpl_tuierror'];
		}
		$rdata['tmplids'] = $tmplids;
        if(getcustom('shop_order_exchange_product')){
            $expressdata = array_keys(express_data(['aid'=>aid,'bid'=>$detail['bid']]));
            $rdata['expressdata'] = $expressdata;
            $returnField = 'return_name,return_tel,return_province,return_city,return_area,return_address';
            if($detail['bid'] > 0){
                $return = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->field($returnField)->find();
            }else{
                $return = Db::name('shop_sysset')->where('aid',aid)->field($returnField)->find();
            }
            if(empty($return) || empty($return['return_tel']) || empty($return['return_name']) || empty($return['return_province']) || empty($return['return_address'])){
                return $this->json(['status' => 0, 'msg' => '商家还未设置退货地址']);
            }
            $rdata['return_address'] = $return;
        }

		return $this->json($rdata);
	}
	function refund(){//申请退款

		if(request()->isPost()){
			$post = input('post.');
			$orderid = intval($post['orderid']);
			$money = floatval($post['money']);
			$refundNum = $post['refundNum'];

            try {
                Db::startTrans();
                $addtype = 1;//添加订单类型 1：默认添加方式 2：根据退款商品，一商品规格一退货订单
                //老版本
                if (empty($refundNum)) {
                    $order = Db::name('shop_order')->where('aid', aid)->where('mid', mid)->where('id', $orderid)->find();
                    if (!$order || ($order['status'] != 1 && $order['status'] != 2) || $order['refund_status'] == 2) {
                        return $this->json(['status' => 0, 'msg' => '订单状态不符合退款要求']);
                    }
                    if ($money < 0 || $money > $order['totalprice']) {
                        return $this->json(['status' => 0, 'msg' => '退款金额有误']);
                    }
                    Db::name('shop_order')->where('aid', aid)->where('mid', mid)->where('id', $orderid)->update(['refund_time' => time(), 'refund_status' => 1, 'refund_reason' => $post['reason'], 'refund_money' => $money]);
                } else {
                    //新退款 20210610
                    $order = Db::name('shop_order')->where('aid', aid)->where('mid', mid)->where('id', $orderid)->find();

                    if (!$order || ($order['status'] != 1 && $order['status'] != 2) && !(getcustom('mendian_upgrade') && $order['status'] == 8)) {
                        return $this->json(['status' => 0, 'msg' => '订单状态不符合退款要求']);
                    }
                    if ($money < 0 || $money > $order['totalprice']) {
                        return $this->json(['status' => 0, 'msg' => '退款金额有误']);
                    }
                    if (empty($refundNum)) {
                        return $this->json(['status' => 0, 'msg' => '请选择退款的商品']);
                    }

                    $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
                    if(getcustom('shoporder_refund_sendcoupon')){
                        //退款是否关联优惠券，关联则购买赠送的优惠券使用了，商品不能退款
                        if($shopset['return_sendcoupon']){
                            $countCouponRecord = Db::name('coupon_record')->where('mid',mid)->where('source','shop')->where('orderid',$order['id'])->where('status',1)->count();
                            if($countCouponRecord) return $this->json(['status' => 0, 'msg' => '该订单不符合退款要求，不能退款']);
                        }
                    }

                    if(getcustom('wx_express_intracity') && $order['wxtc_wx_order_id']){
                        //取消微信配送违约金
                        $deductfee = \app\custom\WxExpressIntracity::getDeductfee(aid,['wxtc_wx_order_id' => $order['wxtc_wx_order_id']]);

                        if($deductfee > 0){
                            //退款金额减去配送违约金
                            $money = bcsub ($money,$deductfee,2);
                        }
                    }

                    //仅退款判断图片是否上传
                    $refundpic = Db::name('shop_sysset')->where('aid',aid)->value('refundpic');
                    if($post['type']=='refund' && $refundpic == 1){
                        if(!$post['content_pic'] || empty($post['content_pic'])){
                            return $this->json(['status' => 0, 'msg' => '请上传图片']);
                        }
                    }

                    $refundMoneySum = Db::name('shop_refund_order')->where('orderid', $order['id'])->where('aid', aid)->whereIn('refund_status', [1, 2, 4])->sum('refund_money');

                    $totalRefundNum = 0;
                    $returnTotalprice = 0;
                    $prolist = Db::name('shop_order_goods')->where('orderid', $orderid)->select();
                    $newKey = 'id';
                    $prolist = $prolist->dictionary(null, $newKey);
                    $ogids = array_keys($prolist);
                    Log::write([
                        'file' => __FILE__ . ' L' . __LINE__,
                        'function' => __FUNCTION__,
                        '$prolist' => $prolist,
                    ]);

                    $canRefundNum = 0;
                    $totalNum = 0;
                    $canRefundProductPrice = 0;
                    $canRefundTotalprice = 0;
                    foreach ($prolist as $key => $item) {
                        $prolist[$key]['canRefundNum'] = $item['num'] - $item['refund_num'];
                        $totalNum += $item['num'];
                        $canRefundNum += $item['num'] - $item['refund_num'];
                        $canRefundProductPrice += $item['real_totalprice'] / $item['num'] * ($item['num'] - $item['refund_num']);
                    }
                    if(getcustom('product_service_fee')) {
                        $surplus = $money;
                        $deff = $money;
                        $refundServiceFee = 0;
                    }
                    if(getcustom('supply_zhenxin')){
                        if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                            $addtype = 2;
                        }
                    }
                    if(getcustom('freeze_money')){
                        $refundFreezemoney = 0;
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

                        if(getcustom('product_service_fee')) {
                            if ($item['num'] < 1) continue;
                            $sell_price = $prolist[$item['ogid']]['sell_price'];
                            $realTotalprice = $prolist[$item['ogid']]['real_totalprice'];
                            $surplus -= $prolist[$item['ogid']]['real_totalprice'];
                            if($sell_price * $item['num'] == $money){
                                $refundServiceFee = $prolist[$item['ogid']]['service_fee'] * $item['num'];
                            }else{
                                if($surplus >= 0){
                                    $refundServiceFee += $prolist[$item['ogid']]['service_fee'] * $item['num'];
                                }else{
                                    if($surplus < 0) $surplus += $realTotalprice;
                                    if($surplus >= $sell_price){
                                        $refundServiceFee += $prolist[$item['ogid']]['service_fee'];
                                    }else{
                                        $refundServiceFee += 0;
                                    }
                                }
                            }
                        }
                        if(getcustom('supply_zhenxin')){
                            if($addtype == 2){
                                //判断是否商品待发货订单，是的话仅支持仅退款，且需要退全部
                                if($order['status'] == 1){
                                    if($post['type'] != 'refund'){
                                        return $this->json(['status' => 0, 'msg' => '商品待发货，仅支持仅退款']);
                                    }
                                    if($item['num'] != $prolist[$item['ogid']]['num']){
                                        return $this->json(['status' => 0, 'msg' => '商品待发货，请退商品全部数量']);
                                    }
                                }
                            }
                        }
                        if(getcustom('freeze_money')){
                            //冻结资金支付
                            $refundFreezemoney += $prolist[$item['ogid']]['freezemoney_price'] * $item['num'];
                        }
                    }
                    if ($totalRefundNum <= 0) {
                        return $this->json(['status' => 0, 'msg' => '请选择退款的商品']);
                    }
                    if ($canRefundNum == $totalNum && $totalNum == $totalRefundNum) {
                        $canRefundTotalprice = $order['totalprice'];
                    } else {
                        $canRefundTotalprice = $order['totalprice'] - $refundMoneySum;
                    }
                    $canRefundTotalprice = round($canRefundTotalprice, 2);
                    if ($money > $canRefundTotalprice) {
                        return $this->json(['status' => 0, 'msg' => '退款金额超出范围']);
                    }
                	//Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->update(['refund_time'=>time(),'refund_status'=>1,'refund_money'=>$money]);

                    $data = [
                        'aid' => $order['aid'],
                        'bid' => $order['bid'],
                        'mdid' => $order['mdid'],
                        'mid' => $order['mid'],
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'title' => $order['title'],
                        'refund_type' => $post['type'],
                        'refund_ordernum' => '' . date('ymdHis') . rand(100000, 999999),
                        'refund_money' => $money,
                        'refund_reason' => $post['reason'],
                        'refund_pics' => implode(',', $post['content_pic']),
                        'createtime' => time(),
                        'refund_time' => time(),
                        'refund_status' => 1,
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
                    if(getcustom('product_service_fee')) {
                        $data['refund_service_fee'] = $refundServiceFee;
                    }
                    if(getcustom('freeze_money')){
                        //冻结资金支付
                        $data['refund_freezemoney'] = $refundFreezemoney;
                    }
                    if(getcustom('erp_wangdiantong')) {
                        $data['wdt_status'] = $order['wdt_status'];
                    }

                    if(getcustom('wx_express_intracity')) {
                        $data['wxtc_cancel_reason'] = $post['wxtc_cancel_reason'] ?? '';
                        $data['wxtc_deductfee'] = $post['wxtc_deductfee'] ?? 0;
                    }

                    //1、默认添加方式 2、根据退款商品、一商品规格一退货单
                    if($addtype == 1){
                        $refund_id = Db::name('shop_refund_order')->insertGetId($data);
                    }

                    $ztuikuan = count($refundNum);
                    $refund_money_z = 0;
                    foreach ($refundNum as $rk => $item) {
                        if ($item['num'] < 1) continue;
                        //退款金额 *（单价*退款数量）/退款总价=当前商品占退款金额比例
                        $refund_money = $returnTotalprice==0?0:($money * (($prolist[$item['ogid']]['real_totalprice'] / $prolist[$item['ogid']]['num']) * $item['num'] / $returnTotalprice));
                        $refund_money = round($refund_money,2);
                        if($ztuikuan > 1 && ($ztuikuan == ($rk+1))){
                            $refund_money = round(($money - $refund_money_z),2);
                        }
                        $refund_money_z += $refund_money;
                        if(getcustom('supply_zhenxin')){
                            //根据退款商品、一商品规格一退货单
                            if($addtype == 2){
                                if($order['status'] != 1 && $order['status'] != 2) {
                                    return $this->json(['status' => 0, 'msg' => '订单状态不符合退款要求']);
                                }
                                if($order['usegiveorder'] && !$order['giveordermid']) {
                                    return $this->json(['status' => 0, 'msg' => '等待好友领取中，暂不支持退款']);
                                }
                                //甄新汇选申请退款
                                $params = [];
                                //售后类型 1仅退款 2退货退款 3换货 4补发 5部分退款（待发货订单请申请1仅退款）
                                $params['service_type'] = $post['type'] == 'refund'?1:2;
                                $params['order_sn']     = $order['sordernum'];
                                $params['sku_id']       = $prolist[$item['ogid']]['skuid'];
                                $params['sku_num']      = $item['num'];
                                $params['amount_money'] = $refund_money;//部分退款必须填写
                                if($post['content_pic']){
                                    $params['images'] = $post['content_pic'];
                                }
                                if($post['reason']){
                                    $params['remark'] = $post['reason'];
                                }
                                $applyrefund = \app\custom\SupplyZhenxinCustom::applyrefund(aid,$order['bid'],$params);
                                if(!$applyrefund ||  $applyrefund['status'] != 1){
                                    $msg = $applyrefund && $applyrefund['msg']?$applyrefund['msg']:'退款失败';
                                    return $this->json(['status' => 0, 'msg' => $msg]);
                                }
                                $data['issource'] = 1;
                                $data['source']   = 'supply_zhenxin';
                                $data['zxservice_sn'] = $applyrefund['data']['service_sn'];
                                $refund_id = Db::name('shop_refund_order')->insertGetId($data);
                            }
                        }
                        $od = [
                            'aid' => $order['aid'],
                            'bid' => $order['bid'],
                            'mid' => $order['mid'],
                            'orderid' => $order['id'],
                            'ordernum' => $order['ordernum'],
                            'refund_orderid' => $refund_id,
                            'refund_ordernum' => $data['refund_ordernum'],
                            'refund_num' => $item['num'],
                            //'refund_money' => $item['num'] * $prolist[$item['ogid']]['real_totalprice'] / $prolist[$item['ogid']]['num'],
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

                        if(getcustom('product_service_fee')) {
                            $sell_price = $prolist[$item['ogid']]['sell_price'];
                            $realTotalprice = $prolist[$item['ogid']]['real_totalprice'];
                            $deff -= $prolist[$item['ogid']]['real_totalprice'];
                            if($sell_price * $item['num'] == $money){
                                $od['service_fee'] = $prolist[$item['ogid']]['service_fee'] * $item['num'];
                            }else {
                                if ($deff >= 0) {
                                    $od['service_fee'] = $prolist[$item['ogid']]['service_fee'] * $item['num'];
                                } else {
                                    if ($deff < 0) $deff += $realTotalprice;
                                    if ($deff >= $sell_price) {
                                        $od['service_fee'] = $prolist[$item['ogid']]['service_fee'];
                                    } else {
                                        $od['service_fee'] = 0;
                                    }
                                }
                            }
                        }
                        if(getcustom('erp_wangdiantong')) {
                            $od['wdt_status'] = $prolist[$item['ogid']]['wdt_status'];
                        }
                        if(getcustom('supply_zhenxin')){
                            //根据退款商品、一商品规格一退货单
                            if($addtype == 2){
                            	$od['issource'] = 1;
                                $od['source']   = 'supply_zhenxin';
                            	$od['skuid']        = $prolist[$item['ogid']]['skuid'];
                            	$od['zxservice_sn'] = $data['service_sn'];
                            }
                        }
                        if(getcustom('freeze_money')){
                            //冻结资金支付
                            $od['refund_freezemoney'] = $prolist[$item['ogid']]['freezemoney_price'] * $item['num'];
                        }
                        Db::name('shop_refund_order_goods')->insertGetId($od);
                        Db::name('shop_order_goods')->where('aid', aid)->where('mid', mid)->where('id', $item['ogid'])->inc('refund_num', $item['num'])->update();

                        if($addtype == 2){
                            if ($order['fromwxvideo'] == 1) {
                                \app\common\Wxvideo::aftersaleadd($order['id'], $refund_id);
                            }
                            //退款打印小票
                            if(getcustom('shoporder_refund_wifiprint') && $refund_id){
                                \app\common\Wifiprint::print($order['aid'],'shop_refund',$refund_id,1,0);
                            }
                        }
                    }

                    if($addtype == 1){
                        if ($order['fromwxvideo'] == 1) {
                            \app\common\Wxvideo::aftersaleadd($order['id'], $refund_id);
                        }
                    }
                }
                Db::commit();
                if($addtype == 1){
                    //退款打印小票
                    if(getcustom('shoporder_refund_wifiprint') && $refund_id){
                        \app\common\Wifiprint::print($order['aid'],'shop_refund',$refund_id,1,0);
                    }
                }
            } catch (\Exception $e) {
                Log::error([
                    'file' => __FILE__ . ' L' . __LINE__,
                    'function' => __FUNCTION__,
                    'error' => $e->getMessage(),
                ]);
                Db::rollback();
                return $this->json(['status'=>0,'msg'=>'提交失败,请重试']);
            }
            try {
                $tmplcontent = [];
                $tmplcontent['first'] = '有订单客户申请退款';
                $tmplcontent['remark'] = '点击进入查看~';
                $tmplcontent['keyword1'] = $order['ordernum'];
                $tmplcontent['keyword2'] = $money.'元';
                $tmplcontent['keyword3'] = $post['reason'];
                $tmplcontentNew = [];
                $tmplcontentNew['number2'] = $order['ordernum'];//订单号
                $tmplcontentNew['amount4'] = $money;//退款金额
                \app\common\Wechat::sendhttmpl(aid,$order['bid'],'tmpl_ordertui',$tmplcontent,m_url('admin/order/shopRefundOrder'),$order['mdid'],$tmplcontentNew);

				$tmplcontent = [];
				$tmplcontent['thing1'] = $order['title'];
				$tmplcontent['character_string4'] = $order['ordernum'];
				$tmplcontent['amount2'] = $order['totalprice'];
				$tmplcontent['amount9'] = $money.'元';
				$tmplcontent['thing10'] = $post['reason'];
				\app\common\Wechat::sendhtwxtmpl(aid,$order['bid'],'tmpl_ordertui',$tmplcontent,'admin/order/shopRefundOrder',$order['mdid']);
            } catch (\Exception $e) {

            }
            if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
				if($post['type']=='refund'){
					$good_status = 'BUYER_NOT_RECEIVED';
					$type = '仅退款';
				}
				if($post['type']=='return'){
					$good_status = 'BUYER_RECEIVED';
					$type="普通退货";
				}

				$order = Db::name('shop_refund_order')->where('aid', aid)->where('id', $refund_id)->find();
				//创建聚水潭售後订单
				$rs = \app\custom\Jushuitan::refund($order,'WAIT_SELLER_AGREE',$good_status,$type);
			}
            if(getcustom('erp_hupun') && $refund_id && $post['type'] == 'return'){
                //万里牛erp
                $wln = new \app\custom\Hupun($order['aid']);
                $wln->orderReturn($refund_id);
            }
			return $this->json(['status'=>1,'msg'=>'提交成功,请等待商家审核','rs'=>$rs]);
		}
	}

    /**
     * 申请换货
     * @author: liud
     * @time: 2024/12/24 下午2:46
     */
    function exchange(){
        if(getcustom('shop_order_exchange_product')){
            if(request()->isPost()){
                $post = input('post.');
                $orderid = intval($post['orderid']);
                $newpro = $post['newpro'];
                $express_com = $post['express_com'];
                $express_no = $post['express_no'];

                try {
                    Db::startTrans();

                    $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
                    $order = Db::name('shop_order')->where('aid', aid)->where('mid', mid)->where('id', $orderid)->find();

                    //是否可以换货
                    if($shopset['exchange_product'] != 1){
                        return $this->json(['status' => 0, 'msg' => '换货未开启']);
                    }

                    if (!$order || !in_array($order['status'],[2,3])) {
                        return $this->json(['status' => 0, 'msg' => '订单状态不符合换货要求']);
                    }

                    if ($order['status'] == 3 && $shopset['exchange_product_day'] > 0){
                        //已收货后N天内可以换货
                        $collect_time = $order['collect_time'] + $shopset['exchange_product_day'] * 86400;
                        if(time() > $collect_time) {
                            return $this->json(['status' => 0, 'msg' => '已过换货有效期']);
                        }
                    }

                    if(!$express_no){
                        return $this->json(['status' => 0, 'msg' => '请填写快递单号']);
                    }

                    //仅退款判断图片是否上传
                    if(($post['type']=='refund' || $post['type']=='exchange') && $shopset['refundpic'] == 1){
                        if(empty($post['content_pic'])){
                            return $this->json(['status' => 0, 'msg' => '请上传图片']);
                        }
                    }

                    $prolist = Db::name('shop_order_goods')->where('orderid', $orderid)->select();
                    $newKey = 'id';
                    $prolist = $prolist->dictionary(null, $newKey);

                    $express_content = jsonEncode([['express_com'=>$express_com,'express_no'=>$express_no]]);

                    $returnField = 'return_name,return_tel,return_province,return_city,return_area,return_address';
                    if($order['bid'] > 0){
                        $return = Db::name('business')->where('aid',aid)->where('id',$order['bid'])->field($returnField)->find();
                    }else{
                        $return = $shopset;
                    }
                    if(empty($return) || empty($return['return_tel']) || empty($return['return_name']) || empty($return['return_province']) || empty($return['return_address'])){
                        return $this->json(['status' => 0, 'msg' => '商家还未设置退回地址']);
                    }

                    $data = [
                        'aid' => $order['aid'],
                        'bid' => $order['bid'],
                        'mdid' => $order['mdid'],
                        'mid' => $order['mid'],
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'title' => $order['title'],
                        'refund_type' => 'exchange',
                        'refund_status' => 4,//同意待用户退回
                        'refund_ordernum' => '' . date('ymdHis') . rand(100000, 999999),
                        'refund_reason' => $post['reason'],
                        'refund_pics' => implode(',', $post['content_pic']),
                        'express_com' => $express_com,
                        'express_no' => $express_no,
                        'express_content' => $express_content,
                        'isexpress' => 1,
                        'expresstime' => time(),
                        'createtime' => time(),
                        'refund_time' => time(),
                        'platform' => platform,
                        'return_name'=> $return['return_name'],
                        'return_tel' => $return['return_tel'],
                        'return_province' => $return['return_province'],
                        'return_city' => $return['return_city'],
                        'return_area' => $return['return_area'],
                        'return_address' => $return['return_address'],
                    ];
                    $refund_id = Db::name('shop_refund_order')->insertGetId($data);

                    foreach ($newpro as $item) {
                        //换货数量-暂时是全部换
                        $exchange_num = $prolist[$item['ogid']]['num'];

                        if($shop_order_goods = Db::name('shop_order_goods')->where('aid', aid)->where('id', $item['ogid'])->where('refund_num', '>',0)->find()){
                            return $this->json(['status' => 0,'msg' => $shop_order_goods['ggname'].'正在退款中，不能换货']);
                        }

                        $od = [
                            'aid' => $order['aid'],
                            'bid' => $order['bid'],
                            'mid' => $order['mid'],
                            'orderid' => $order['id'],
                            'ordernum' => $order['ordernum'],
                            'refund_orderid' => $refund_id,
                            'refund_ordernum' => $data['refund_ordernum'],
                            'refund_num' => $exchange_num,
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
                        $refund_order_goodsid = Db::name('shop_refund_order_goods')->insertGetId($od);
                    }

                    Db::commit();
                } catch (\Exception $e) {
                    Log::error([
                        'file' => __FILE__ . ' L' . __LINE__,
                        'function' => __FUNCTION__,
                        'error' => $e->getMessage(),
                    ]);
                    Db::rollback();
                    return $this->json(['status'=>0,'msg'=>'提交失败,请重试']);
                }

                return $this->json(['status'=>1,'msg'=>'提交成功,请等待商家处理']);
            }
        }
    }
	//评价商品
	public function comment(){
		$ogid = input('param.ogid/d');
		$og = Db::name('shop_order_goods')->where('id',$ogid)->where('mid',mid)->find();
		if(!$og){
			return $this->json(['status'=>0,'msg'=>'未查找到相关记录']);
		}
		$comment = Db::name('shop_comment')->where('ogid',$ogid)->where('aid',aid)->where('mid',mid)->find();
		if(request()->isPost()){
			$shopset = Db::name('shop_sysset')->where('aid',aid)->find();
			if($shopset['comment']==0) return $this->json(['status'=>0,'msg'=>'评价功能未开启']);
			if($comment){
				return $this->json(['status'=>0,'msg'=>'您已经评价过了']);
			}
			$order_good = Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where('id',$ogid)->find();
			$order = Db::name('shop_order')->where('id',$order_good['orderid'])->find();
			$content = input('post.content');
			$content_pic = input('post.content_pic');
			$score = input('post.score/d');
			if($score < 1){
				return $this->json(['status'=>0,'msg'=>'请打分']);
			}
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['bid'] = $order_good['bid'];
			$data['ogid'] = $order_good['id'];
			$data['proid'] =$order_good['proid'];
			$data['proname'] = $order_good['name'];
			$data['propic'] = $order_good['pic'];
			$data['orderid']= $order['id'];
			$data['ordernum']= $order['ordernum'];
			$data['score'] = $score;
			$data['content'] = $content;
			$data['openid']= $this->member['openid'];
			$data['nickname']= $this->member['nickname'];
			$data['headimg'] = $this->member['headimg'];
			$data['createtime'] = time();
			$data['content_pic'] = $content_pic;
			$data['ggid'] = $order_good['ggid'];
			$data['ggname'] = $order_good['ggname'];
			$data['status'] = ($shopset['comment_check']==1 ? 0 : 1);
			Db::name('shop_comment')->insert($data);
			Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where('id',$ogid)->update(['iscomment'=>1]);
			//Db::name('shop_order')->where('id',$order['id'])->update(['iscomment'=>1]);

			//如果不需要审核 增加产品评论数及评分
			if($shopset['comment_check']==0){
				$countnum = Db::name('shop_comment')->where('proid',$order_good['proid'])->where('status',1)->count();
				$score = Db::name('shop_comment')->where('proid',$order_good['proid'])->where('status',1)->avg('score'); //平均评分
				$haonum = Db::name('shop_comment')->where('proid',$order_good['proid'])->where('status',1)->where('score','>',3)->count(); //好评数
				if($countnum > 0){
					$haopercent = $haonum/$countnum*100;
				}else{
					$haopercent = 100;
				}
				Db::name('shop_product')->where('id',$order_good['proid'])->update(['comment_num'=>$countnum,'comment_score'=>$score,'comment_haopercent'=>$haopercent]);
			}
			return $this->json(['status'=>1,'msg'=>'评价成功']);
		}
		$rdata = [];
		$rdata['og'] = $og;
		$rdata['comment'] = $comment;
		return $this->json($rdata);
	}
	//评价店铺
	public function commentdp(){
		$orderid = input('param.orderid/d');
		$order = Db::name('shop_order')->where('id',$orderid)->where('mid',mid)->find();
		if(!$order){
			return $this->json(['status'=>0,'msg'=>'未查找到相关记录']);
		}
		$comment = Db::name('business_comment')->where('orderid',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(request()->isPost()){
			if($comment){
				return $this->json(['status'=>0,'msg'=>'您已经评价过了']);
			}
			$content = input('post.content');
			$content_pic = input('post.content_pic');
			$score = input('post.score/d');
			if($score < 1){
				return $this->json(['status'=>0,'msg'=>'请打分']);
			}
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['bid'] = $order['bid'];
			$data['bname'] = Db::name('business')->where('id',$order['bid'])->value('name');
			$data['orderid']= $order['id'];
			$data['ordernum']= $order['ordernum'];
			$data['score'] = $score;
			$data['content'] = $content;
			$data['content_pic'] = $content_pic;
			$data['openid']= $this->member['openid'];
			$data['nickname']= $this->member['nickname'];
			$data['headimg'] = $this->member['headimg'];
			$data['createtime'] = time();
			$data['status'] = 1;
			Db::name('business_comment')->insert($data);

			//如果不需要审核 增加店铺评论数及评分
			$countnum = Db::name('business_comment')->where('bid',$order['bid'])->where('status',1)->count();
			$score = Db::name('business_comment')->where('bid',$order['bid'])->where('status',1)->avg('score');
			$haonum = Db::name('business_comment')->where('bid',$order['bid'])->where('status',1)->where('score','>',3)->count(); //好评数
			if($countnum > 0){
				$haopercent = $haonum/$countnum*100;
			}else{
				$haopercent = 100;
			}
			Db::name('business')->where('id',$order['bid'])->update(['comment_num'=>$countnum,'comment_score'=>$score,'comment_haopercent'=>$haopercent]);
			return $this->json(['status'=>1,'msg'=>'评价成功']);
		}
		$rdata = [];
		$rdata['order'] = $order;
		$rdata['comment'] = $comment;
		return $this->json($rdata);
	}
	//评价配送员
	public function commentps(){
		$id = input('param.id/d');
		$psorder = Db::name('peisong_order')->where('id',$id)->where('mid',mid)->find();
		if(!$psorder) return $this->json(['status'=>0,'msg'=>'未找到相关记录']);
		$comment = Db::name('peisong_order_comment')->where('orderid',$id)->where('aid',aid)->where('mid',mid)->find();
		if(request()->isPost()){
			if($comment){
				return $this->json(['status'=>0,'msg'=>'您已经评价过了']);
			}
			$content = input('post.content');
			$content_pic = input('post.content_pic');
			$score = input('post.score/d');
			if($score < 1){
				return $this->json(['status'=>0,'msg'=>'请打分']);
			}
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['bid'] = $psorder['bid'];
			$data['psid'] = $psorder['psid'];
			$data['orderid']= $psorder['id'];
			$data['ordernum']= $psorder['ordernum'];
			$data['score'] = $score;
			$data['content'] = $content;
			$data['content_pic'] = $content_pic;
			$data['nickname']= $this->member['nickname'];
			$data['headimg'] = $this->member['headimg'];
			$data['createtime'] = time();
			$data['status'] = 1;
			Db::name('peisong_order_comment')->insert($data);

			//如果不需要审核 增加配送员评论数及评分
			$countnum = Db::name('peisong_order_comment')->where('psid',$psorder['psid'])->where('status',1)->count();
			$score = Db::name('peisong_order_comment')->where('psid',$psorder['psid'])->where('status',1)->avg('score'); //平均评分
			$haonum = Db::name('peisong_order_comment')->where('psid',$psorder['psid'])->where('status',1)->where('score','>',3)->count(); //好评数
			if($countnum > 0){
				$haopercent = $haonum/$countnum*100;
			}else{
				$haopercent = 100;
			}
			Db::name('peisong_user')->where('id',$psorder['psid'])->update(['comment_num'=>$countnum,'comment_score'=>$score,'comment_haopercent'=>$haopercent]);

			return $this->json(['status'=>1,'msg'=>'评价成功']);
		}
		$rdata = [];
		$rdata['psorder'] = $psorder;
		$rdata['comment'] = $comment;
		return $this->json($rdata);
	}

	public function getproducthxqr(){
		$ogid = input('post.hxogid/d');
		$hxnum = input('post.hxnum/d');
		if(!$ogid || !$hxnum) return json(['status'=>0,'msg'=>'参数错误']);

		$og = Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where('id',$ogid)->find();
		$is_quanyi = (isset($og['product_type']) && $og['product_type']==8)?1:0;
		if($is_quanyi){
            $quanyi_res = \app\common\Order::quanyihexiao($og['id'],1,input('param.hxnum'));
            if(!$quanyi_res['status']){
                return $this->json($quanyi_res);
            }
        }else{
            if($og['num'] - $og['hexiao_num'] < $hxnum) return json(['status'=>0,'msg'=>'剩余可核销数量不足']);
        }

		$hexiao_qr = createqrcode(m_url('admin/hexiao/hexiao?type=shopproduct&hxnum='.$hxnum.'&co='.$og['hexiao_code']));
		return json(['status'=>1,'hexiao_qr'=>$hexiao_qr]);
	}

    public function hexiao(){
        $post = input('post.');
        $orderid = intval($post['orderid']);
        $order = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
        if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
//
        $type = 'shop';
        if($order['status']==0) return $this->json(['status'=>0,'msg'=>'订单未支付']);
        if($order['status']==3) return $this->json(['status'=>0,'msg'=>'订单已核销']);
        if($order['status']==4) return $this->json(['status'=>0,'msg'=>'订单已关闭']);
        if($order['hexiao_code_member'] != $post['hexiao_code_member']){
            return $this->json(['status'=>0,'msg'=>'核销密码不正确']);
        }
        $order['prolist'] = Db::name($type.'_order_goods')->where(['orderid'=>$order['id']])->select()->toArray();
        if($order['freight_type'] == 1){
            $order['storeinfo'] = Db::name('mendian')->where('id',$order['mdid'])->field('name,address,longitude,latitude')->find();
        }
        $member = Db::name('member')->where('id',$order['mid'])->field('id,nickname,levelid,headimg')->find();
        $order['nickname'] = $member['nickname'];
        $order['headimg'] = $member['headimg'];
        $data = array();
        $data['aid'] = aid;
        $data['bid'] = $order['bid'];
        $data['uid'] = 0;
        $data['mid'] = $order['mid'];
        $data['orderid'] = $order['id'];
        $data['ordernum'] = $order['ordernum'];
        $data['title'] = $order['title'];
        $data['type'] = $type;
        $data['createtime'] = time();
        $data['remark'] = t('会员').'核销';
        Db::name('hexiao_order')->insert($data);
        $remark = $order['remark'] ? $order['remark'].' '.$data['remark'] : $data['remark'];

        $rs = \app\common\Order::collect($order,$type);
        if($rs['status']==0) return $this->json($rs);

        db($type.'_order')->where('id',$orderid)->where('aid',aid)->update(['status'=>3,'collect_time'=>time(),'remark'=>$remark]);

        if($type == 'shop'){
            Db::name($type.'_order_goods')->where(['aid'=>aid,'orderid'=>$order['id']])->update(['status'=>3,'endtime'=>time()]);
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
	                //自提核销返现
	                $zthx_backmoney = Db::name('member_level')->where('id',$member['levelid'])->where('aid',aid)->value('zthx_backmoney');
	                if(!empty($zthx_backmoney) && $zthx_backmoney>0){
	                    $back_money = 0;
	                    if($order['prolist']){
	                        foreach($order['prolist'] as $pv){
	                            $back_money += $pv['market_price']*$pv['num'];
	                        }
	                        unset($pv);
	                    }

	                    $back_money = $back_money*$zthx_backmoney/100;
	                    $back_money = round($back_money,2);
	                    if($back_money>0){
	                        \app\common\Member::addmoney(aid,$order['mid'],$back_money,'自提核销返回，订单号: '.$order['ordernum']);
	                    }
	                }
	            }
	        }
        }

        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }

    /**
     * 获取收银台订单信息
     * 待结算status=0
     * 已结算订单status=1
     * 挂单status=2
     */
    public function getCashierOrder()
    {
        $status = input('param.st', 'all');
        $keyword = input('param.keyword', 0);
        $page = input('param.pagenum/d', 1);
        $limit = input('param.limit/d', 10);
        $where = [];
        $where[] = ['o.aid','=',aid];

        if($status == 'all'){
            $where[] = ['o.status' ,'in', [1,10]];
        }else{
            $where[] = ['o.status' ,'=', $status];
        }
        $where[] = ['o.mid','=',mid];
        if($keyword){
            $where[] = ['g.proname|g.barcode','like','%'.$keyword.'%'];
        }
        if($status==2){
            $orderby = 'hangup_time desc';
        }else{
            $orderby = 'id desc';
        }
        $lists = Db::name('cashier_order')->alias('o')->join('cashier_order_goods g','o.id=g.orderid')->group('o.id')->where($where)->field('o.*')->order($orderby)->page($page,$limit)->select()->toArray();
        if (empty($lists)) $lists = [];
        foreach ($lists as $k => $order) {
            if($order['uid'] > 0){
                $admin_user_name = Db::name('admin_user')->where('id',$order['uid'])->value('un');
                $lists[$k]['admin_user'] = $admin_user_name??'超级管理员';
            }else{
                $lists[$k]['admin_user'] = '超级管理员';
            }
            $goodslist = Db::name('cashier_order_goods')->where('orderid', $order['id'])->select()->toArray();
            if (empty($goodslist)) $goodslist = [];
            $totalprice = 0;
            foreach ($goodslist as $gk => $goods) {
                $goodslist[$gk]['stock'] = 0;
                if($status==2){
                    $stock = 0;
                    if ($goods['protype'] == 1) {
                        $stock = Db::name('shop_guige')->where('proid', $goods['proid'])->where('id', $goods['ggid'])->value('stock');
                    }
                    $goods_totalprice = round($goods['sell_price'] * $goods['num'],2);
                    $totalprice = $totalprice+$goods_totalprice;
                    $goodslist[$gk]['stock'] = $stock ?? 0;
                }
                if($goods['protype'] ==2){
                    $goodslist[$gk]['propic'] =PRE_URL.'/static/imgsrc/picture-1.jpg';
                }
            }
            if($status==2){
                $lists[$k]['totalprice']  = $totalprice;
            }
            $lists[$k]['hangup_time'] = '';
            if ($order['hangup_time']) {
                $lists[$k]['hangup_time'] = date('Y-m-d H:i:s', $order['hangup_time']);
            }
            $lists[$k]['paytime'] = $order['paytime']?date('Y-m-d H:i:s', $order['paytime']):'';
            $lists[$k]['createtime'] = date('Y-m-d H:i:s', $order['createtime']);
            $arr =[0=>'待付款',1=>'已支付',2=>'挂单',3=>'st3',4=>'已关闭'];
            $lists[$k]['status_desc'] = $arr[$status]??$status;
            if($order['mid']){
                $member =  Db::name('member')->where('id',$order['mid'])->field('id,nickname,realname')->find();
                $lists[$k]['buyer'] = $member['nickname']??'';
            }else{
                $lists[$k]['buyer'] = '匿名购买';
            }
            $lists[$k]['prolist'] = $goodslist ?? [];
            if($order['bid']!=0){
                $lists[$k]['binfo'] = Db::name('business')->where('aid',aid)->where('id',$order['bid'])->field('id,name,logo')->find();
                if(!$lists[$k]['binfo']) $lists[$k]['binfo'] = [];
                $commentdp = Db::name('business_comment')->where('orderid',$order['id'])->where('aid',aid)->where('mid',mid)->find();
                if($commentdp){
                    $lists[$k]['iscommentdp'] = 1;
                }else{
                    $lists[$k]['iscommentdp'] = 0;
                }
            } else {
                $lists[$k]['binfo'] = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
            }
            $lists[$k]['procount'] = Db::name('cashier_order_goods')->where('orderid',$order['id'])->sum('num');
        }
        return $this->json(['status'=>1,'msg'=>'操作成功','datalist' => $lists]);
    }
    public function getCashierOrderDetail(){
        $detail = Db::name('cashier_order')->where('id',input('param.id/d'))->where('aid',aid)->where('mid',mid)->find();
        if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);
        $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
        $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
        $detail['hangup_time'] = $detail['hangup_time'] ? date('Y-m-d H:i:s',$detail['hangup_time']) : '';
        $detail['procount'] = Db::name('cashier_order_goods')->where('orderid',$detail['id'])->sum('num');

        if($detail['uid'] > 0){
            $admin_user_name = Db::name('admin_user')->where('id',$detail['uid'])->value('un');
            $detail['admin_user'] = $admin_user_name??'超级管理员';
        }else{
            $detail['admin_user'] = '超级管理员';
        }
        $prolist = Db::name('cashier_order_goods')->where('orderid',$detail['id'])->select()->toArray();
        foreach ($prolist as $gk=>$goods){
            if($goods['protype'] ==2){
                $prolist[$gk]['propic'] =PRE_URL.'/static/imgsrc/picture-1.jpg';
            }
        }
        $rdata = [];
        $rdata['detail'] = $detail;
        $rdata['prolist'] = $prolist;
        return $this->json($rdata);
    }


	public function handinit(){
        if(getcustom('product_handwork')){
            //手工活回寄
            $id = input('param.id/d');
            $res = \app\custom\HandWork::handinit(aid,mid,$id);
            if($res && $res['status'] == 0){
                return $this->json($res);
            }else if(!$res){
            	return $this->json(['status'=>0,'msg'=>'系统错误']);
            }
            return $this->json($res);
        }
    }

    public function hand(){
        if(getcustom('product_handwork')){
            //手工活回寄
            if(request()->isPost()){
                $post        = input('post.');
                $res = \app\custom\HandWork::hand(aid,mid,platform,$post);
	            if($res && $res['status'] == 0){
	                return $this->json($res);
	            }else if(!$res){
	            	return $this->json(['status'=>0,'msg'=>'系统错误']);
	            }
	            return $this->json($res);
	        }
        }
    }

    public function handList(){
        if(getcustom('product_handwork')){
            //手工活回寄
            $st = input('param.st');
            if(!input('param.st') || $st === ''){
                $st = 'all';
            }
            $keyword = input('param.keyword');
            $pagenum = input('post.pagenum');
            $orderid = input('post.orderid')?input('orderid/d'):0;
            $res = \app\custom\HandWork::handList(aid,mid,$st,$keyword,$pagenum,$orderid);
            if($res && $res['status'] == 0){
                return $this->json($res);
            }else if(!$res){
            	return $this->json(['status'=>0,'msg'=>'系统错误']);
            }
            return $this->json($res);
       }
    }

    public function handDetail()
    {
        if(getcustom('product_handwork')){
        	$id = input('param.id/d');
            $res = \app\custom\HandWork::handDetail(aid,mid,$id);
            if($res && $res['status'] == 0){
                return $this->json($res);
            }else if(!$res){
            	return $this->json(['status'=>0,'msg'=>'系统错误']);
            }
            return $this->json($res);
        }
    }

    public function handChangeexpress()
    {
        if(getcustom('product_handwork')){
        	$id = input('param.id/d');
        	$express_content = input('param.express_content');
        	if(!$express_content || empty($express_content)){
        		return $this->json(['status'=>0,'msg'=>'请填写快递信息']);
        	}
            $res = \app\custom\HandWork::handChangeexpress(aid,mid,$id,$express_content);
            if($res && $res['status'] == 0){
                return $this->json($res);
            }else if(!$res){
            	return $this->json(['status'=>0,'msg'=>'系统错误']);
            }
            return $this->json($res);
        }
    }
    //退款退货发快递
    function refundExpress(){
        if(request()->isPost()){
            $post = input('post.');
            $orderid = intval($post['orderid']);
            $refund_order = Db::name('shop_refund_order')->where('id',$orderid)->where('refund_status',4)->where('mid',mid)->find();
            if(!$refund_order){
            	return $this->json(['status'=>0,'msg'=>'退款订单不存在或状态不符']);
            }
            if($refund_order['isexpress']){
            	return $this->json(['status'=>0,'msg'=>'已填写过快递']);
            }
            //处理快递信息
            $express_com     = $post['express_com'];
            $express_no      = $post['express_no'];
            if(!$express_com || !$express_no){
               return $this->json(['status'=>0,'msg'=>'快递信息请填写完整']);
            }

            if(getcustom('supply_zhenxin')){
                if($refund_order['issource'] && $refund_order['source'] == 'supply_zhenxin'){
                    $getExpress = \app\custom\SupplyZhenxinCustom::getExpress(aid,$refund_order['bid']);
                    if(!$getExpress || $getExpress['status'] != 1){
                        $msg = $getExpress && $getExpress['msg']?$getExpress['msg']:'系统快递信息错误';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                    //快递公司ID
                    $express_id = 0;
                    foreach($getExpress['data'] as $gv){
                        if($express_com == $gv['name']){
                            $express_id = $gv['id'];
                        }
                    }
                    unset($gv);
                    if(!$express_id){
                        return $this->json(['status'=>0,'msg'=>'快递不存在，请重新选择']);
                    }
                    //退换货填写运单号
                    $params=['service_sn'=>$refund_order['zxservice_sn'],'express_id'=>$express_id,'express_no'=>$express_no];
                    $expressSubmit = \app\custom\SupplyZhenxinCustom::expressSubmit(aid,$refund_order['bid'],$params);
                    if(!$expressSubmit || $expressSubmit['status'] != 1){
                        $msg = $expressSubmit && $expressSubmit['msg']?$expressSubmit['msg']:'提交失败';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                }
            }
            $data = [];
            $data['express_com'] = $express_com;
            $data['express_no']  = $express_no;
            $express_content = jsonEncode([['express_com'=>$express_com,'express_no'=>$express_no]]);
            $data['express_content'] = $express_content;
            $data['isexpress']  = 1;
            $data['expresstime']= time();
            $up = Db::name('shop_refund_order')->where('id',$orderid)->update($data);

            if(getcustom('erp_hupun')){
                $wln = new \app\custom\Hupun(aid);
                $wln->orderReturn($orderid,2);
            }
            if($up){
            	return $this->json(['status'=>1,'msg'=>'提交成功']);
            }else{
            	return $this->json(['status'=>0,'msg'=>'提交失败']);
            }
        }
    }
    //获取分期数据详情
    public function getFenqidata(){
        if(getcustom('shop_product_fenqi_pay')){
            $post = input('post.');
            $orderid = intval($post['orderid']);
            $order = Db::name('shop_order')->where('id',$orderid)->where('aid', aid)->where('mid', mid)->find();
            if($order['is_fenqi'] == 1){
                $now_fenqi_num = explode(',',$order['now_fenqi_num']);
                $fenqi_data = json_decode($order['fenqi_data'],true);
                $fenqi_one_paydate = $order['fenqi_one_paydate'];
                $time = time();
                foreach($fenqi_data as $fqkey=>$fq){
                    if($fq['status'] == 0){
                        $i = $fq['fenqi_num'] - 1;
                        if(isset($fq['end_paytime'])){
                            $nextMonth =  strtotime($fq['end_paytime'])+86400;
                        }else{
                            $nextMonth = strtotime("+{$i} month", strtotime($fenqi_one_paydate)+86400);
                        }
                        if($time > $nextMonth){
                            $fenqi_data[$fqkey]['status'] = 2;
                        }
                    }

                }
                $up = Db::name('shop_order')->where('id',$orderid)->update(['fenqi_data'=>json_encode($fenqi_data,JSON_UNESCAPED_UNICODE)]);
            }

            return $this->json(['status'=>1,'msg'=>'获取成功','data'=>$fenqi_data]);
        }
    }

    //分期数据生成支付订单
    public function saveFenqidata(){
        if(getcustom('shop_product_fenqi_pay')){
            $post = input('post.');
            $orderid = intval($post['orderid']);
            //$select_fenqi_num = $post['select_fenqi_num'];
            $select_fenqi_type = $post['select_fenqi_type'];
            // if(!$select_fenqi_num){
            //     return $this->json(['status'=>0,'msg'=>'请选择分期']);
            // }
            $order = Db::name('shop_order')->where('id',$orderid)->where('aid', aid)->where('mid', mid)->find();
            if(!$order){
                return $this->json(['status'=>0,'msg'=>'请选择正确的订单']);
            }
            if($order['status'] == 4){
                return $this->json(['status'=>0,'msg'=>'订单已关闭，不能发起支付']);
            }
            if($order['is_fenqi'] == 1){
                //$now_fenqi_num = explode(',',$select_fenqi_num);
                $fenqi_data = json_decode($order['fenqi_data'],true);
                $fenqi_one_paydate = $order['fenqi_one_paydate'];
                $time = time();
                $totalprice = 0;
                $ordernum = \app\common\Common::generateOrderNo(aid);
                if($select_fenqi_type == 2){
                    $ordernum = $ordernum.count($fenqi_data);
                }
                foreach($fenqi_data as $fqkey=>$fq){
                    if($fq['status'] == 0){
                        $i = $fq['fenqi_num'] - 1;
                        if(isset($fq['end_paytime'])){
                            $nextMonth =  strtotime($fq['end_paytime'])+86400;
                        }else{
                            $nextMonth = strtotime("+{$i} month", strtotime($fenqi_one_paydate)+86400);
                        }
                        $nextMonth =  strtotime($fq['end_paytime'])+86400;
                        if($time < $nextMonth){
                            if($select_fenqi_type == 2){
                                $totalprice += $fq['fenqi_money'];
                                $fenqi_num_c[] = $fq['fenqi_num'];
                            }else{
                                $totalprice += $fq['fenqi_money'];
                                $fenqi_num_c[] = $fq['fenqi_num'];
                                $ordernum = $ordernum.$fq['fenqi_num'];
                                break;
                            }
                        }else{
                            $fenqi_data[$fqkey]['status'] = 2;
                        }
                    }
                }
                if(!$fenqi_num_c){
                    $up = Db::name('shop_order')->where('id',$orderid)->update(['fenqi_data'=>json_encode($fenqi_data,JSON_UNESCAPED_UNICODE)]);
                    return $this->json(['status'=>0,'msg'=>'当前分期已无效，请重新选择']);
                }
                $fenqi_num_up = implode(',',$fenqi_num_c);
                $payorderid = \app\model\Payorder::createorder(aid,$order['bid'],$order['mid'],'shop_fenqi',$orderid,$ordernum,$order['title'],$totalprice,$order['scoredkscore']);
				Db::name('shop_order')->where('id',$orderid)->update(['payorderid'=>$payorderid,'now_fenqi_num'=>$fenqi_num_up]);
            }
            return $this->json(['status'=>1,'payorderid'=>$payorderid,'msg'=>'提交成功']);
        }
    }

    public function uploadcard()
    {
        $orderid = input('param.orderid/d');
        $detail = Db::name('shop_order')->where('id', $orderid)->where('aid', aid)->where('mid', mid)->find();
        $oglist = Db::name('shop_order_goods')->where('orderid', $orderid)->where('aid', aid)->select()->toArray();
        if (!$detail) return $this->json(['status' => 0, 'msg' => '订单不存在']);
        if(!in_array($detail['trade_type'],['1101','1303'])){
            return $this->json(['status' => 0, 'msg' => '无须上传身份证信息']);
        }
        $detail['area2'] = $detail['area2']?str_replace(',','/',$detail['area2']): '';
        if(request()->isPost()){
            $data = input('post.');
            if($detail['supplier_status']!=200){
                return $this->json(['status'=>0,'msg'=>'订单状态有误']);
            }
            if(empty($data['cardno']) || empty($data['card']) || empty($data['cardf'])){
                return $this->json(['status'=>0,'msg'=>'身份证信息不完整']);
            }
            $data['area'] = $data['area']?str_replace('/',',',$data['area']): '';
            $areainfo = \app\custom\Chain::getHaidaiArea($data['area']);
            $update = [
                'linkman'=>$data['linkman'],
                'tel'=>$data['tel'],
                'address'=>$data['address'],
                'area'=>str_replace(',','',$data['area']),
                'area2'=>$data['area'],
                'cardno'=>$data['cardno'],
                'card'=>$data['card'],
                'card_back'=>$data['cardf'],
                'area_id'=>$areainfo['area_id'],
                'area_regionid'=>$areainfo['region_id'],
            ];
            Db::name('shop_order')->where('id',$orderid)->update($update);
            $res = \app\custom\Chain::syncOrderToSupplier(aid,$orderid);
            if($res['status']==1){
                \app\custom\Chain::orderPay(aid,$orderid);
            }
        }
        return $this->json(['status'=>1,'order'=>$detail,'oglist'=>$oglist]);
    }
    // 查看测评订单状态,更新系统状态
    public function pingceOrder(){
        if(getcustom('product_pingce')){
            $id = input('param.id/d');
            $order = Db::name('shop_order')->where('id',$id)->where('aid',aid)->where('mid', mid)->find();
            $return = json_decode($order['pingce_return'],true);
            $Haneo = new \app\custom\Haneo(aid);
            $url = $Haneo->getTestUrl($return['response']['playerkey'],$return['response']['gbaurl']);//测评地址
            if($order['pingce_status'] == 2 && $order['pingce_result_urls']){
                return $this->json(['status'=>$order['pingce_status'],'url'=>$url,'report_arr'=>$Haneo->formatReportArr($order['pingce_result_urls'],$order)]);
            }else{
                $rs = $Haneo->pingceOrder($order['id']);
                $order = Db::name('shop_order')->where('id',$id)->where('aid',aid)->find();
                if($rs['status'] == 1){
                    return $this->json(['status'=>$order['pingce_status'],'url'=>$url,'report_arr'=>$Haneo->formatReportArr($rs['report_arr'],$order)]);
                }
                return $this->json(['status'=>'1','msg'=>$rs['msg'],'url'=>$url]);
            }
        }
    }
    public function toExchangeConfirm()
    {
        if (getcustom('shop_order_exchange_product')) {
            $orderid = input('post.orderid/d');
            $order = Db::name('shop_refund_order')->where('id', $orderid)->where('aid', aid)->where('mid', mid)->find();
            if (!$order) {
                return $this->json(['status' => 0, 'msg' => '订单不存在']);
            }
            //确认收到货
            if ($order['refund_status'] != 7) {
                return $this->json(['status' => 0, 'msg' => '订单状态不符']);
            }

            Db::name('shop_refund_order')->where('id', $orderid)->update(['refund_status' =>8]);

            return $this->json(['status' => 1, 'msg' => '操作成功']);
        }
    }

    public function takegiveorder(){
        if(getcustom('shop_giveorder')){
            $payorderid = input('?param.payorderid')?input('param.payorderid/d'):0;
            if(!$payorderid){
                return $this->json(['status' => 0, 'msg' => '请选择要领取的订单']);
            }
            $payorder = Db::name('payorder')->where('id',$payorderid)->where('aid',aid)->find();
            if(!$payorder){
                return $this->json(['status' => 0, 'msg' => '要领取的订单不存在']);
            }
            if($payorder['mid'] == mid) return $this->json(['status' => 0, 'msg' => '自己不能领取自己的订单']);
            
            $ordernum = $payorder['ordernum'];
            $ishb = false;//是否是合并订单 默认否

            if($payorder['type'] == 'shop_hb'){
                if($payorder['status'] !=1) return $this->json(['status' => 0, 'msg' => '未合并支付成功，不能一起领取']);
                //查询是否已被领取
                $countorder = Db::name('shop_order')->where('ordernum',$payorder['ordernum'].'_1')->where('status',1)->where('giveordermid',0)->count('id');
                if(!$countorder){
                    return $this->json(['status' => 0, 'msg' => '订单状态不符或已被领取']);
                }
                $ishb = true;
            }else{
                if($payorder['status'] !=1){
                    //查询是否是合并订单支付
                    $ordernumArr = explode('_',$payorder['ordernum']);
                    if(isset($ordernumArr[1]) || $ordernumArr[1]){
                        $ordernum = $ordernumArr[1];
                        $payorder = Db::name('payorder')->where('ordernum',$ordernum)->where('aid',aid)->find();
                        if(!$payorder){
                            return $this->json(['status' => 0, 'msg' => '要领取的订单不存在']);
                        }
                        if($payorder['status'] != 1){
                            return $this->json(['status' => 0, 'msg' => '要领取的订单未支付']);
                        }
                        $ishb = true;
                    }
                }
            }
            //合并订单
            if($ishb){
                $orders = Db::name('shop_order')->where('ordernum', 'like',  $ordernum . '_%' )->where('status',1)->where('giveordermid',0)->order('id asc')->select()->toArray();
            }else{
                $orders = Db::name('shop_order')->where('ordernum', $ordernum)->order('id asc')->where('status',1)->where('giveordermid',0)->select()->toArray();
            }
            if(!$orders){
                return $this->json(['status' => 0, 'msg' => '订单状态不符或已被领取']);
            }
            foreach($orders as &$order){
                if($order['freight_type']==4){
                    return $this->json(['status'=>0,'msg'=>'虚拟产品不支持赠送']);
                }
                $refundingMoney = Db::name('shop_refund_order')->where('orderid',$order['id'])->where('aid',aid)->whereIn('refund_status',[1,4])->count('id');
                if($refundingMoney) {
                    return json(['status'=>0,'msg'=>'订单有退款申请，暂不能领取']);
                }
                $order['oglist'] = Db::name('shop_order_goods')->where('orderid',$order['id'])->where('aid',aid)->select()->toArray();
            }
            unset($order);
            if(request()->isPost()){
                $post = input('post.');
                //处理订单配送数据
                $dealgiveorder2 = $this->dealgiveorder2($orders,$post);
                if($dealgiveorder2){
                    return $this->json($dealgiveorder2);
                }else{
                    return $this->json(['status'=>0,'msg'=>'订单信息有误']);
                }
            }else{
                //处理订单配送数据
                $dealgiveorder = $this->dealgiveorder($orders);
                if($dealgiveorder){
                    return $this->json($dealgiveorder);
                }else{
                    return $this->json(['status'=>0,'msg'=>'订单信息有误']);
                }
            }
        }
    }
    public function dealgiveorder($orders){
        if(getcustom('shop_giveorder')){

            $userinfo = [];
            $userinfo['realname']= $this->member['realname'];
            $userinfo['tel']     = $this->member['tel'];
            $userinfo['money']   = $this->member['money'];

            if(getcustom('supply_zhenxin')){
                $havezxpro   = false;//是否有甄新汇选商品
                $needusercard= false;//是否需要填写身份证号码
            }
            $allbuydata = [];
            $glassProductNum = 0;
            foreach($orders as $order){
                $orderid = $order['id'];
                $buydata = [];
                $buydata['bid']     = $order['bid'];
                $buydata['orderid'] = $order['id'];
                $buydata['issource']= $order['issource']??0;
                $buydata['source']  = $order['source']??'';
                $buydata['product_type'] = $order['product_type']??0;
                $buydata['trade_type']   = $order['trade_type']??0;
                $buydata['totalprice']   = $order['totalprice'];
                $buydata['product_price']= $order['product_price'];
                
                $oglist = $order['oglist'];
                if($oglist){
                    $buydata['prodata'] = [];
                    foreach($oglist as $progg){
                        $proid= $progg['proid'];
                        $ggid = $progg['ggid'];
                        $num  = $progg['num'];

                        $product = Db::name('shop_product')->where('aid',aid)->where('ischecked',1)->where('id',$proid)->find();
                        if(!$product){
                            return ['status'=>0,'msg'=>'产品不存在或已下架'];
                        }
                        if($product['freighttype'] == 4){
                            return ['status'=>0,'msg'=>'虚拟产品不支持赠送'];
                        }

                        if(($product['freighttype'] == 3 || $product['freighttype'] == 4) && $product['contact_require'] == 1){
                            $contact_require = 1;
                        }
                        if($product['freighttype'] == 3 || $product['freighttype'] == 4) $autofahuo = $product['freighttype'];
                        $guige = Db::name('shop_guige')->where('id',$ggid)->find();
                        if(!$guige){
                            return ['status'=>0,'msg'=>'产品该规格不存在或已下架'];
                        }
                        $ggid_arr[] = $ggid;
                        $guige['gg_group_title'] = '';
                        $guigedata = json_decode($product['guigedata'],true);
                        $guige = $this->formatguige($guige, $product['bid'],$product['lvprice']);

                        if(getcustom('supply_zhenxin')){
                            if($product['issource'] && $product['source'] == 'supply_zhenxin'){
                                $havezxpro = true;//是否有甄新汇选商品
                                //查询甄新汇选商品详情
                                $checkproduct = \app\custom\SupplyZhenxinCustom::checkproduct(aid,$product['bid'],$num,$product,$guige);
                                if(!$checkproduct || $checkproduct['status'] != 1){
                                    $msg = $checkproduct && $checkproduct['msg']?$checkproduct['msg']:'['.$product['name'].'规格'.$guige['name'].'] '.'信息错误';
                                    return ['status'=>0,'msg'=>$msg];
                                }
                                $guige = $checkproduct['guige'];
                                if($guige['is_overseas'] == 1){
                                    $needusercard = true;//是否跨境完税商品 需要填写身份证
                                }
                            }
                        }

                        $product['weightkey'] = 0;
                        $product['weightlist'] = [];

                        $thisprodata = ['product'=>$product,'guige'=>$guige,'num'=>$num];
                        $buydata['prodata'][] = $thisprodata;

                        if($product['to86yk_tid']){
                            $extendInput = [['key'=>'input','val1'=>'充值账号','val2'=>'请输入充值账号','val3'=>1],['key'=>'input','val1'=>'确认账号','val2'=>'请再次输入充值账号','val3'=>1]];
                        }
                    }
                }
                $allbuydata[] = $buydata;
            }

            $havetongcheng = 0;
            if(getcustom('shop_buy_worknum')){
                $worknum_status=false;//是否显示工号
            }
            foreach($allbuydata as &$buydata){
                $orderid = $buydata['orderid'];
                $bid = $buydata['bid'];
                $customfreight = 0;//自定义快递 0：不是不自定义 1：自定义为普通快递
                if(getcustom('supply_zhenxin')){
                    //判断是不是甄新汇选商品
                    if($buydata['issource'] == 1 && $buydata['source'] == 'supply_zhenxin'){
                        $customfreight = 1;
                    }
                }
                if($customfreight>0){
                    if($customfreight == 1){
                        $formdata = json_encode([['key'=>'input','val1'=>'备注','val2'=>'选填，请输入备注信息','val3'=>'0']]);
                        $freightList = [['id'=>0,'name'=>'普通快递','pstype'=>0,'formdata'=>$formdata]];
                    }
                }else if($autofahuo>0){
                    $freightList = [['id'=>0,'name'=>($autofahuo==3?'自动发货':'在线卡密'),'pstype'=>$autofahuo]];
                }else{
                    $freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
                    $fids = [];
                    foreach($freightList as $v){
                        $fids[] = $v['id'];
                    }
                    foreach($buydata['prodata'] as $prodata){
                        if($prodata['product']['freighttype']==0){
                            $fids = array_intersect($fids,explode(',',$prodata['product']['freightdata']));
                        }else{
                            $thisfreightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
                            $thisfids = [];
                            foreach($thisfreightList as $v){
                                $thisfids[] = $v['id'];
                            }
                            $fids = array_intersect($fids,$thisfids);
                        }
                    }
                    if(!$fids){
                        return ['status'=>0,'msg'=>'获取配送方式失败'];
                    }
                    $freight_order = 'sort desc,id';
                    $freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid],['id','in',$fids]],$freight_order);

                    foreach($freightList as $k=>$v){
                        //var_dump($freightList);
                        if($v['pstype']==2){ //同城配送
                            $havetongcheng = 1;
                        }
                        if(getcustom('hmy_yuyue') && $v['pstype']==12){
                            //红蚂蚁定制
                            $havetongcheng = 1;
                        }
                        if(getcustom('shop_buy_worknum')){
                            if($k == 0 && $v['worknum_status']){
                                $worknum_status=true;//是否显示工号
                            }
                        }
                    }
                }
                $buydata['freightList'] = $freightList;
            }
            unset($buydata);
            if($havetongcheng){
                $address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->where('latitude','>',0)->order('isdefault desc,id desc')->find();
            }else{
                $address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->order('isdefault desc,id desc')->find();
            }
            if(!$address) $address = [];

            $needLocation = 0;

            $bids = [];
            foreach($allbuydata as &$buydata){
                $orderid = $buydata['orderid'];

                $bid = $buydata['bid'];
                if(!in_array($bid,$bids)){
                    $bids[] = $bid;
                }

                $product_type = $buydata['product_type'];
                $trade_type   = $buydata['trade_type'];

                if($bid!=0){
                    $field = 'id,aid,cid,name,logo,tel,address,sales,longitude,latitude,start_hours,end_hours,start_hours2,end_hours2,start_hours3,end_hours3,end_buy_status,invoice,invoice_type,is_open';
                    if(getcustom('member_dedamount')){
                        $field .= ',paymoney_givepercent';
                    }
                    $business = Db::name('business')->where('id',$bid)->field($field)->find();
                }else{
                    $business = Db::name('admin_set')->where('aid',aid)->field('id,name,logo,desc,tel,invoice,invoice_type,invoice_rate')->find();
                }

                $product_price = $buydata['product_price'];
                $totalweight = 0;
                $totalnum = 0;
                $mendian_id = 0;

                $bindMendianIds = [];
                $supplierFreight= 0;//供货的额外运费

                foreach($buydata['prodata'] as &$prodata){
                    if(getcustom('product_bind_mendian')){
                        if($prodata['product']['bind_mendian_ids'] && !in_array('-1',explode(',',$prodata['product']['bind_mendian_ids']))){
                            $bindMendianIds = array_unique(array_merge($bindMendianIds,explode(',',$prodata['product']['bind_mendian_ids'])));
                        }
                    }
                }

                $rs = \app\model\Freight::formatFreightList($buydata['freightList'],$address,$product_price,$totalnum,$totalweight,$bindMendianIds,$mendian_id);

                if(!getcustom('freight_upload_pics') && $rs['freightList']){
                    //是否开启配送方式多图
                    foreach ($rs['freightList'] as $fk => $fv){
                        foreach ($fv['formdata'] as $fk1 => $fv1){
                            if($fv1['key'] == 'upload_pics'){
                                unset($rs['freightList'][$fk]['formdata'][$fk1]);
                            }
                        }
                    }
                }

                $freightList = $rs['freightList'];
                if(getcustom('supply_zhenxin')){
                    //甄新汇选
                    if($buydata['issource'] == 1 && $buydata['source'] == 'supply_zhenxin'){
                        //查询甄新汇并给地址赋值区域编号
                        if($address){
                            if(empty($address['province_zxcode']) || empty($address['city_zxcode']) || empty($address['district_zxcode'])){
                                //换算区域code
                                $area = ['province'=>$address['province'],'city'=>$address['city'],'district'=>$address['district']];
                                $getarea2 = \app\custom\SupplyZhenxinCustom::getarea2(aid,$bid,$area);
                                if($getarea2 && $getarea2['status'] == 1){
                                    $address['province_zxcode'] = $getarea2['data']['province_zxcode'];
                                    $address['city_zxcode']     = $getarea2['data']['city_zxcode'];
                                    $address['district_zxcode'] = $getarea2['data']['district_zxcode'];
                                    Db::name('member_address')->where('id',$address['id'])->where('mid',mid)->update(['province_zxcode'=>$getarea2['data']['province_zxcode'],'city_zxcode'=>$getarea2['data']['city_zxcode'],'district_zxcode'=>$getarea2['data']['district_zxcode']]);
                                }
                            }
                        }
                    }
                }

                $freightArr = $rs['freightArr'];
                if($rs['needLocation']==1) $needLocation = 1;

                if($bid > 0){
                    $business = Db::name('business')->where('aid',aid)->where('id', $bid)->find();
                    $bcids = $business['cid'] ? explode(',',$business['cid']) : [];
                }else{
                    $bcids = [];
                }

                if($bcids){
                    $whereCid = [];
                    foreach($bcids as $bcid){
                        $whereCid[] = "find_in_set({$bcid},canused_bcids)";
                    }
                    $whereCids = implode(' or ',$whereCid);
                }else{
                    $whereCids = '0=1';
                }

                if($extendInput){
                    foreach($freightList as $fk=>$fv){
                        $freightList[$fk]['formdata'] = array_merge($extendInput,$fv['formdata']);
                    }
                }
                if(getcustom('product_supply_chain')) {
                    if ($product_type == 7) {
                        $supplier = Db::name('admin')->alias('a')->where('a.id', aid)->join('supplier s', 'a.supplier_id=s.id')->field('s.name,s.id')->find();
                        if ($supplier) {
                            $business['name'] = $business['name'] . '[' . $supplier['name'] . ']';
                        }
                        if ($trade_type == '1101'){
                            $business['name'] = $business['name'].'[保税]';
                        }else if ($trade_type == '1303'){
                            $business['name'] = $business['name'].'[海外直邮]';
                        }
                    }
                }

                $buydata['bid']       = $bid;
                $buydata['orderid']   = $buydata['orderid'];
                $buydata['business']  = $business;

                $buydata['freightList'] = $freightList;
                $buydata['freightArr']  = $freightArr;
                $buydata['extFreightPrice']= $supplierFreight;//其他扩展运费
                $buydata['product_price']  = round($product_price,2);
                $buydata['freightkey']   = 0;
                $buydata['pstimetext']   = '';
                $buydata['freight_time'] = '';
                $buydata['storeid']   = 0;
                $buydata['storename'] = '';
                $buydata['editorFormdata'] = [];
            }
            unset($buydata);

            $custom = [];
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['hasglassproduct'] = $glassProductNum>0?1:0;
            $rdata['havetongcheng']   = $havetongcheng;
            $rdata['address'] = $address;
            $rdata['linkman'] = $address ? $address['name'] : strval($userinfo['realname']);
            $rdata['tel']     = $address ? $address['tel'] : strval($userinfo['tel']);
            if(!$rdata['linkman']){
                $lastorder = Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('linkman','<>','')->find();
                if($lastorder){
                    $rdata['linkman'] = $lastorder['linkman'];
                    $rdata['tel'] = $lastorder['tel'];
                }
            }
            $rdata['realname'] = isset($this->member['realname']) ? $this->member['realname'] : '';

            $rdata['userinfo']   = $userinfo;
            $rdata['allbuydata'] = $allbuydata;

            $rdata['bid']    = $bids?$bids[0]:0;
            $rdata['business_payconfirm'] = 0;

            $rdata['needLocation'] = $needLocation;
            $rdata['pstype3needAddress'] = false;

            if(getcustom('shop_buy_worknum')){
                $rdata['worknum_status'] = $worknum_status?true:false;//是否显示工号
                $rdata['worknumtip']     = '请输入您的工号(必须为10位数字)';//
            }
            $rdata['custom'] = $custom;

            $rdata['mendian_upgrade'] = false;
            if(getcustom('mendian_upgrade')){
                $admin =  Db::name('admin')->where('id',aid)->field('mendian_upgrade_status')->find();
                if($admin['mendian_upgrade_status']==1){
                    $rdata['mendian_upgrade'] = true;
                }
                $rdata['needLocation'] = 1; // 门店升级功能开启定位
            }

            $mendian_no_select = 0;
            if(getcustom('mendian_no_select')){
                //甘尔定制，不需要选择门店
                $mendian_no_select = 1;
            }

            $rdata['mendian_no_select']= $mendian_no_select;
            $rdata['contact_require']  = $contact_require;
            $rdata['ismultiselect']=false;

            if(getcustom('supply_zhenxin')){
                //是否必须使用地址
                $mustuseaddress = false;
                if($havezxpro){
                    $mustuseaddress = true;
                }
                //需要使用身份证
                $rdata['needusercard']       = $needusercard;
                $rdata['mustuseaddress']     = $mustuseaddress;
            }

            //评测 判断当前购买商品product_type == 9
            $rdata['is_pingce'] = false;
            if(getcustom('product_pingce')){
                if($product['product_type'] == 9){
                    $rdata['is_pingce'] = true;
                }
            }
            return $rdata;
        }
    }
    public function dealgiveorder2($orders,$post){
        if(getcustom('shop_giveorder')){

            $sysset = $this->sysset;
            $ordermid = mid;
            //收货地址
            if($post['addressid']=='' || $post['addressid']==0){
                $address = ['id'=>0,'name'=>$post['linkman'],'tel'=>$post['tel'],'area'=>'','address'=>''];
            }else{
                $address = Db::name('member_address')->where('id',$post['addressid'])->where('aid',aid)->where('mid',mid)->find();
                if(!$address) return ['status'=>0,'msg'=>'所选收货地址不存在'];
            }
            $shopset = Db::name('shop_sysset')->where('aid',aid)->find();

            if(getcustom('product_thali') && $shopset['product_shop_school'] == 1 && ($address['product_thali_student_name']=='' || $address['product_thali_school'] =='')){
                return ['status'=>0,'msg'=>'请完善收货地址里的学校和学生信息'];
            }

            $usercard = $post['usercard']?trim($post['usercard']):'';
            $buydata  = $post['buydata'];
            foreach($orders as $order){
                //先处理订单信息
                $haveorder = false;
                foreach($buydata as &$data){
                    if($data['orderid'] == $order['id']){
                        $haveorder = true;
                        $data['bid']      = $order['bid'];
                        $data['issource'] = $order['issource']??0;
                        $data['source']   = $order['source']??'';
                        $data['oglist']   = $order['oglist'];
                        $data['product_price']  = $order['product_price'];
                    } 
                }
                unset($data);
                if(!$haveorder) return ['status'=>0,'msg'=>'订单信息有误'];
            }

            $orderdatas = [];
            $i = 0;
            foreach($buydata as $data){
                $i++;
                $orderid = $data['orderid'];
                $bid = $data['bid'];

                $productType  = 0;
                $tradeType    = 0;

                $product_price = $data['product_price'];
                $totalweight = 0;//重量
                $totalnum = 0;
                $prolist = [];

                if($bid)
                    $store_info = Db::name('business')->where('id',$bid)->where('aid',aid)->find();
                else
                    $store_info = Db::name('admin_set')->where('aid',aid)->find();

                $store_name = $store_info['name'];

                $fids = [];
                $customfreight = 0;//自定义快递 0：默认快递 1：自定义普通快递
                if(getcustom('supply_zhenxin')){
                    //判断是不是甄新汇选商品
                    if($data['issource'] == 1 && $data['source'] == 'supply_zhenxin'){
                        $customfreight = 1;
                    }
                }

                if($customfreight>0){
                    if($customfreight == 1){
                        $fids = [0];
                    }
                }else{
                    $freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
                    foreach($freightList as $v){
                        $fids[] = $v['id'];
                    }
                }

                $extendInput = [];
                $oglist = $data['oglist'];
                foreach($oglist as $key=>$pro){
                    $proid = $pro['proid'];
                    $ggid  = $pro['ggid'];
                    $num   = $pro['num'];

                    $product = Db::name('shop_product')->where('id',$proid)->where('aid',aid)->where('ischecked',1)->where('bid',$bid)->find();
                    if(!$product) return ['status'=>0,'msg'=>'产品不存在或已下架'];

                    //商品类型数组
                    $productType= $product['product_type']??0;
                    $tradeType  = $product['trade_type']??0;

                    $guige = Db::name('shop_guige')->where('id',$ggid)->lock(true)->find();
                    if(!$guige || $guige['aid'] != aid) return ['status'=>0,'msg'=>'产品规格不存在或已下架'];

                    $guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);
                    if(getcustom('supply_zhenxin')){
                        if($data['issource'] == 1 && $data['source'] == 'supply_zhenxin'){
                            if($product['issource'] !=1 || $product['source'] != 'supply_zhenxin'){
                                return ['status'=>0,'msg'=>'商品类型出错，请重新选择商品'];
                            }

                            //查询甄新汇选商品详情
                            $checkproduct = self::checkproduct(aid,$product['bid'],$num,$product,$guige);
                            if(!$checkproduct || $checkproduct['status'] != 1){
                                $msg = $checkproduct && $checkproduct['msg']?$checkproduct['msg']:'['.$product['name'].'规格'.$guige['name'].'] '.'信息错误';
                                return ['status'=>0,'msg'=>$msg];
                            }
                            $guige = $checkproduct['guige'];
                            if($guige['is_overseas'] == 1){
                                if(!$usercard){
                                    return ['status'=>0,'msg'=>'请填写身份证号'];
                                }
                            }

                            if(empty($address['name']) || empty($address['tel'])){
                                return ['status'=>0,'msg'=>'请填写姓名和联系电话'];
                            }
                            if($address['tel']!='' && !checkTel(aid,$address['tel'])){
                                return ['status'=>0,'msg'=>'请填写正确的联系电话'];
                            }
                            if(empty($address['province_zxcode']) || empty($address['city_zxcode']) || empty($address['district_zxcode'])){
                                return ['status'=>0,'msg'=>'所选区域编码错误，请重新选择'];
                            }
                            if(empty($address['address'])){
                                return ['status'=>0,'msg'=>'请填写详情地址'];
                            }
                        }
                    }

                    if(getcustom('product_supply_chain')) {
                        $supplierGuige = Db::name('supplier_shop_guige')->where('aid', aid)->where('proid', $product['id'])->where('ggid', $guige['id'])->field('min_price,max_price,is_free_post,freight_money,tax_amount,num gg_num')->find();
                        if ($supplierGuige) {
                            $guige = array_merge($guige, $supplierGuige);
                        }
                    }

                    //是否自定义
                    if($customfreight>0){
                        if($customfreight == 1){
                            $fids = [0];
                        }
                    }else{
                         if($product['freighttype']==0){
                            $fids = array_intersect($fids,explode(',',$product['freightdata']));
                        }else{
                            $thisfreightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
                            $thisfids = [];
                            foreach($thisfreightList as $v){
                                $thisfids[] = $v['id'];
                            }
                            $fids = array_intersect($fids,$thisfids);
                        }
                    }

                    if($product['to86yk_tid']){
                        $extendInput = [['key'=>'input','val1'=>'充值账号','val2'=>'请输入充值账号','val3'=>1],['key'=>'input','val1'=>'确认账号','val2'=>'请再次输入充值账号','val3'=>1]];
                    }
                }

                if(!$fids){
                    return ['status'=>0,'msg'=>'获取配送方式失败'];
                }

                //运费
                $freight_price = 0;
                //是否自定义快递
                if($customfreight>0){
                    if($customfreight == 1){
                        if(getcustom('supply_zhenxin')){
                            $freight = ['id'=>0,'name'=>'普通快递','pstype'=>0];
                            $freight_price = 0;
                        }
                    }
                }else{
                    if($data['freight_id']){
                        $freight = Db::name('freight')->where('aid',aid)->where('id',$data['freight_id'])->find();
                        if($freight['pstype'] == 0){
                            if(!$address || !$address['id']) return ['status'=>0,'msg'=>'请选择收货地址'];
                        }
                        if($freight['pstype']==11){
                            $freight['type11key'] = $data['type11key'];
                        }
                        if($freight['minpriceset']==1 && $freight['minprice']>0 && $freight['minprice'] > $product_price){
                            return ['status'=>0,'msg'=>$freight['name'] . '满'.$freight['minprice'].'元起送'];
                        }
                        if(($address['name']=='' || $address['tel'] =='') && ($freight['pstype']==1 || $freight['pstype']==3) && $freight['needlinkinfo']==1){
                            return ['status'=>0,'msg'=>'请填写联系人和联系电话'];
                        }
                        
                        if(getcustom('mendian_upgrade')){
                            $admin = Db::name('admin')->field('mendian_upgrade_status')->where('id',aid)->find();
                            if($admin['mendian_upgrade_status']==1){
                                $mendianset =  Db::name('mendian_sysset')->field('fwdistance')->where('aid',aid)->find();

                                if($mendianset['fwdistance']>0){
                                    $mendian = Db::name('mendian')->where('id',$data['storeid'])->find();
                                    $juli = getdistance($post['longitude'],$post['latitude'],$mendian['longitude'],$mendian['latitude'],2);

                                    if($juli>$mendianset['fwdistance']){
                                        return ['status'=>0,'msg'=>'超出门店服务距离'];
                                    }
                                }
                            }
                        }

                        $rs = \app\model\Freight::getFreightPrice($freight,$address,$product_price,$totalnum,$totalweight);
                        if($rs['status']==0) return $rs;
                        $freight_price = $rs['freight_price'];
                        //判断配送时间选择是否符合要求
                        if($freight['pstimeset']==1){
                            $freight_times = explode('~',$data['freight_time']);
                            if($freight_times[1]){
                                $freighttime = strtotime(explode(' ',$freight_times[0])[0] . ' '.$freight_times[1]);
                            }else{
                                $freighttime = strtotime($freight_times[0]);
                            }
                            if(time() + $freight['psprehour']*3600 > $freighttime){
                                return ['status'=>0,'msg'=>(($freight['pstype']==0 || $freight['pstype']==2 || $freight['pstype']==10)?'配送':'提货').'时间必须在'.$freight['psprehour'].'小时之后'];
                            }
                        }
                    }elseif($product['freighttype']==3){
                        $freight = ['id'=>0,'name'=>'自动发货','pstype'=>3];
                        if($product['contact_require'] == 1 && ($address['name']=='' || $address['tel'] =='')){
                            return ['status'=>0,'msg'=>'请填写联系人和联系电话'];
                        }
                        if($address['tel']!='' && !checkTel(aid,$address['tel'])){
                            return ['status'=>0,'msg'=>'请填写正确的联系电话'];
                        }
                    }elseif($product['freighttype']==4){
                        return ['status'=>0,'msg'=>'虚拟产品不支持赠送'];
                        $freight = ['id'=>0,'name'=>'在线卡密','pstype'=>4];
                        if($product['contact_require'] == 1 && ($address['name']=='' || $address['tel'] =='')){
                            return ['status'=>0,'msg'=>'请填写联系人和联系电话'];
                        }
                        if($address['tel']!='' && !checkTel(aid,$address['tel'])){
                            return ['status'=>0,'msg'=>'请填写正确的联系电话'];
                        }
                    }else{
                        $freight = ['id'=>0,'name'=>'包邮','pstype'=>0];
                    }
                }

                $orderdata = [];
                $orderdata['orderid'] = $data['orderid'];
                $orderdata['giveordermid'] = mid;
                $orderdata['linkman'] = $address['name'];
                $orderdata['company'] = $address['company'];
                $orderdata['tel']     = $address['tel'];
                $orderdata['area']    = $address['area'];
                $orderdata['address'] = $address['address'];
                $orderdata['longitude'] = $address['longitude'];
                $orderdata['latitude']  = $address['latitude'];
                $orderdata['area2']     = $address['province'].','.$address['city'].','.$address['district'];
                if(getcustom('product_thali')){
                    $orderdata['product_thali_student_name'] = $address['product_thali_student_name'] ?? '';
                    $orderdata['product_thali_school'] = $address['product_thali_school'] ?? '';
                }
                if($freight && ($freight['pstype']==0 || $freight['pstype']==10)){ //快递
                    $orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
                    $orderdata['freight_type'] = $freight['pstype'];
                }elseif($freight && $freight['pstype']==1){ //到店自提
                    $orderdata['mdid'] = $data['storeid'];
                    if(empty($orderdata['mdid'])){
                        return ['status'=>0,'msg'=>'请选择门店'];
                    }
                    $mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
                    $orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                    $orderdata['area2'] = $mendian['area'];
                    $orderdata['freight_type'] = 1;
                    if(getcustom('freight_selecthxbids') && $freight['bid']==0){
                        $orderdata['freight_text'] = $freight['name'];
                        $orderdata['area2'] = '';
                        $orderdata['mdid'] = '-1';
                        if(!$orderdata['longitude']){
                            $orderdata['longitude'] = $post['longitude'];
                            $orderdata['latitude'] = $post['latitude'];
                        }
                    }
                    if(getcustom('mendian_no_select')){
                        $mdids = '';
                        foreach($prolist as $key=>$v) {
                            $product = $v['product'];
                            $mdids .= ','.$product['bind_mendian_ids'];
                        }
                        $mdids = ltrim($mdids,',');
                        $orderdata['mdids'] = $mdids;
                    }
                }elseif($freight && $freight['pstype']==5){ //门店配送
                    $orderdata['mdid'] = $data['storeid'];
                    $mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
                    if(empty($mendian)){
                        return ['status'=>0,'msg'=>'请选择门店'];
                    }
                    $orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                    $orderdata['area2'] = $mendian['area'];
                    $orderdata['freight_type'] = 5;
                }elseif($freight && $freight['pstype']==2){ //同城配送
                    if(getcustom('tongcheng_mendian')){
                        $orderdata['mdid'] = $data['storeid'];
                        $mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
                        $orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                    }else{
                        $orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
                    }

                    $orderdata['freight_type'] = 2;
                }elseif($freight && $freight['pstype']==12){ //app配送
                    $orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
                    $orderdata['freight_type'] = 2;
                }elseif($freight && ($freight['pstype']==3 || $freight['pstype']==4)){ //自动发货 在线卡密
                    $orderdata['freight_text'] = $freight['name'];
                    $orderdata['freight_type'] = $freight['pstype'];
                }elseif($freight && $freight['pstype']==11){ //选择物流配送
                    $type11pricedata = json_decode($freight['type11pricedata'],true);
                    $orderdata['freight_text'] = $type11pricedata[$freight['type11key']]['name'].'('.$freight_price.'元)';
                    $orderdata['freight_type'] = $freight['pstype'];
                    $orderdata['freight_content'] = jsonEncode($type11pricedata[$freight['type11key']]);
                }else{
                    $orderdata['freight_text'] = '包邮';
                }

                if($sysset['areafenhong_checktype'] == 1 && $orderdata['tel']){
                    $addressrs = getaddressfromtel($orderdata['tel']);
                    if($addressrs && $addressrs['province']){
                        $orderdata['area2'] = $addressrs['province'].','.$addressrs['city'];
                    }
                }
                $orderdata['freight_id'] = $freight['id'];
                $orderdata['freight_time'] = $data['freight_time']; //配送时间

                if(getcustom('shop_buy_worknum')){
                    $worknum = $post['worknum']?trim($post['worknum']):'';
                    $worknum_status = false;
                    if($freight && $freight['worknum_status']){
                        $worknum_status = true;
                    }
                    if($worknum_status){
                        if(empty($worknum)){
                            return ['status'=>0,'msg'=>'请填写工号'];
                        }

                        if(!is_numeric($worknum)){
                            return ['status'=>0,'msg'=>'工号必须为10位数字'];
                        }
                        $len = strlen($worknum);
                        if($len != 10){
                            return ['status'=>0,'msg'=>'工号必须为10位数字'];
                        }
                        $orderdata['worknum'] = $worknum;//工号
                    };
                }

                if(getcustom('product_supply_chain')){
                    $haidaiarea = \app\custom\Chain::getHaidaiArea($orderdata['area2']);
                    $orderdata['area_id'] = $haidaiarea['area_id'];
                    $orderdata['area_regionid'] = $haidaiarea['region_id'];
                    $orderdata['supplier_status'] = 100;//待同步
                    if(in_array($tradeType,['1101','1303'])){
                        $orderdata['supplier_status'] = 200;//待补充海关身份证信息
                    }
                }

                if(getcustom('supply_zhenxin')){
                    if($data['issource'] == 1 && $data['source'] == 'supply_zhenxin'){
                        //甄新汇选必须有姓名、电话、地址
                        if(empty($orderdata['linkman']) || empty($orderdata['tel']) || empty($orderdata['area']) || empty($orderdata['address'])){
                            return ['status'=>0,'msg'=>'此订单商品必须填写姓名、电话、地址'];
                        }
                        $shipAreaCode = $address['province_zxcode'].','.$address['city_zxcode'].','.$address['district_zxcode'];
                        $orderdata['shipAreaCode'] = $shipAreaCode??'';
                        $orderdata['usercard']     = $usercard??'';
                        $orderdata['message']      = $data['formdata'] && $data['formdata']['form0']? $data['formdata']['form0']:'';
                    }
                }

                if(getcustom('product_pingce') && $productType == 9){
                    $pingce = ['user_id'=>mid,'name'=>$post['linkman'],
                        'email'=>$post['pcemail'],
                        'tel'=>$post['tel'],
                        'age'=>$post['age'],
                        'gender'=>$post['gender'],
                        'school'=>$post['school'],
                        'major'=>$post['major'],
                        'education'=>$post['education'],
                        'enrol'=>$post['enrol'],
                        'class_name'=>$post['class_name']
                    ];
                    $orderdata['pingce'] = json_encode($pingce);
                }
                $orderdata['formdata'] = $data['formdata']??'';
                $orderdata['extendInput'] = $extendInput??'';
                $orderdata['oglist']  = $data['oglist'];
                $orderdatas[] = $orderdata;
            }

            if($orderdatas){
                foreach($orderdatas as $key=>$orderdata){
                    $orderid    = $orderdata['orderid'];unset($orderdata['orderid']);
                    $formdata   = $orderdata['formdata'];unset($orderdata['formdata']);
                    $extendInput= $orderdata['extendInput'];unset($orderdata['extendInput']);
                    $oglist     = $orderdata['oglist'];unset($orderdata['oglist']);
                    if($key == 0){
                        //查询是否领取过
                        $count = Db::name('shop_order')->where('id',$orderid)->where('status',1)->where('giveordermid',0)->count('id');
                        if(!$count){
                            return ['status'=>0,'msg'=>'订单已被领取或状态已改变'];
                        }
                    }
                    Db::name('shop_order')->where('id',$orderid)->where('status',1)->where('giveordermid',0)->update($orderdata);
                    \app\model\Freight::saveformdata($orderid,'shop_order',$orderdata['freight_id'],$formdata,$extendInput);

                    $order = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->find();
                    //需要同步到供货商的订单
                    if(getcustom('product_supply_chain')) {
                        if($order['product_type']==7 && !in_array($order['trade_type'],['1101','1303'])) {
                            $resultSp = \app\custom\Chain::syncOrderToSupplier(aid, $orderid);
                        }
                    }
                    
                    $jushuitanadd = true;
                    if(getcustom('supply_zhenxin')){
                        //甄新汇选不推送聚水潭
                        if($order['issource'] == 1 && $order['source'] == 'supply_zhenxin'){
                            $jushuitanadd = false;
                        }
                    }
                    if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
                        if($sysset['jushuitankey'] && $jushuitanadd){
                            //创建聚水潭订单
                            $rs = \app\custom\Jushuitan::createOrder($order,'WAIT_BUYER_PAY');
                            if(!$rs['code']){
                                $ordernum = $rs['data']['datas']['so_id'];
                                //修改订单的聚水潭内部单号
                            }
                        }
                    }

                    //处理订单延后事件
                    \app\model\Payorder::dealshoppayDelayed($order['aid'],$orderid,$order,$oglist);
                }
            }else{
                return ['status'=>0,'msg'=>'订单信息有误'];
            }
            return ['status'=>1,'payorderid'=>0,'msg'=>'提交成功'];
        }
    }

    public function code_exchangepage(){
        if(getcustom('shop_code_exchangepage')){
            $orderid = input('?param.orderid')?input('param.orderid/d'):0;
            if(!$orderid) return $this->json(['status'=>0,'msg'=>'参数错误']);
            $order = Db::name('shop_order')->where('id',$orderid)->field('id,aid,bid,freight_type,freight_content')->find();
            if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
            if($order['freight_type']!= 4) return $this->json(['status'=>0,'msg'=>'订单类型不符']);
            if(empty($order['freight_content'])) return $this->json(['status'=>2,'url'=>'/pages/my/usercenter']);

            if($order['bid']>0){
                $pageset = Db::name('business')->where('id',$order['bid'])->field('exchange_page,exchange_page_bgpic,exchange_page_tourl')->find();
            }else{
                $pageset = Db::name('shop_sysset')->where('aid',aid)->field('exchange_page,exchange_page_bgpic,exchange_page_tourl')->find();
            }
            if(!$pageset) return $this->json(['status'=>0,'msg'=>'设置不存在']);
            if($pageset['exchange_page'] !=1) $pageset['exchange_page_tourl'] = '';

            return $this->json(['status'=>1,'set'=>$pageset,'code'=>$order['freight_content']]);
        }
    }

    public function checkcodpay(){
        if(getcustom('shop_cod_onlinepay')) {
            //检查货到付款在线支付条件是否满足，使用尾款付款方式
            $orderid = input('?param.orderid')?input('param.orderid'):0;
            if(!$orderid) return $this->json(['status'=>0,'msg'=>'请选择订单']);
            $order = Db::name('shop_order')->where('id',$orderid)->where('mid',mid)->where('aid',aid)->find();
            if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
            if($order['status'] !=2 )  return $this->json(['status'=>0,'msg'=>'订单状态不符']);
            //已支付过尾款字段不符合
            if($order['balance_pay_status'] != 0 )  return $this->json(['status'=>0,'msg'=>'订单支付状态不符']);

            $shopset = Db::name('shop_sysset')->where('aid',aid)->field('cod_onlinepay,codtxt')->find();
            if(!$shopset['cod_onlinepay']) return $this->json(['status'=>0,'msg'=>'系统未开启'.$shopset['codtxt'].'在线支付方式']);
            //不是货到付款不符合
            if(empty($order['paytypeid']) || $order['paytypeid'] != 4 || !$order['freight_id']){
                return $this->json(['status'=>0,'msg'=>'订单不是'.$shopset['codtxt']]);
            }

            //查询同城配送订单状态
            $peisong_order = Db::name('peisong_order')->where('orderid',$order['id'])->where('type','shop_order')->find();
            if(!$peisong_order) return $this->json(['status'=>0,'msg'=>'配送订单不存在']);
            if($peisong_order['status'] != 4) return $this->json(['status'=>0,'msg'=>'配送订单状态不符']);

            if($order['balance_pay_status2'] != 0){
                 return $this->json(['status'=>0,'msg'=>'该订单已在线支付']);
            }

            //查询是否发起过
            $balance_pay_orderid = 0;
            if($order['balance_pay_orderid2']>0){
                $balance_pay_orderid = Db::name('payorder')->where('id',$order['balance_pay_orderid2'])->where('type','balance')->where('orderid',$orderid)->value('id');
                $balance_pay_orderid = $balance_pay_orderid??0;
            }
            $payorder = Db::name('payorder')->where('id',$order['payorderid'])->where('orderid',$order['id'])->where('aid',aid)->where('mid',mid)->find();
            if(!$payorder) return $this->json(['status'=>0,'msg'=>'该订单'.$shopset['codtxt'].'记录不存在']);

            if(!$balance_pay_orderid){
                $balancedata = [];
                $balancedata = $payorder;
                unset($balancedata['id']);
                unset($balancedata['paytypeid']);
                unset($balancedata['paytype']);
                unset($balancedata['paynum']);

                $balancedata['orderid'] = $orderid;
                $balancedata['ordernum']= $order['ordernum'];
                $balancedata['title']   = $order['title'];
                $balancedata['money']   = $order['totalprice'];
                $balancedata['status']  = 0;
                $balancedata['type']    = 'balance';
                $newPayorder['platform']= platform;
                $balancedata['createtime'] = time();
                $balance_pay_orderid = Db::name('payorder')->insertGetId($balancedata);
            }
            Db::name('shop_order')->where('id',$orderid)->update(['balance_pay_orderid2'=>$balance_pay_orderid]);

            return $this->json(['status'=>1,'payorderid'=>$balance_pay_orderid]);
        }
    }

    public function testwxtc(){

        $wx_order_id = input('param.wx_order_id');
        $order_status = input('param.order_status');

        $pdata = [
            'wx_order_id'=>$wx_order_id,
            'order_status'=>$order_status,
        ];
        $res = \app\custom\WxExpressIntracity::mocknotify(aid,$pdata);
        if($res['status'] != 1){
            return json(['status'=>0,'msg'=>'操作失败：'.$res['msg']]);
        }

        return json(['status'=>1,'msg'=>'成功']);
    }
 }
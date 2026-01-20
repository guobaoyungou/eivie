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

namespace app\model;
use think\facade\Db;
class PeisongOrder
{
	//创建订单
	static function create($type,$order,$psid=0,$other = []){
		$aid = $order['aid'];
		$hasorder = Db::name('peisong_order')->where('type',$type)->where('orderid',$order['id'])->where('status','<>',10)->find();
		if($hasorder) return ['status'=>0,'msg'=>'已存在配送单'];
		$set_where = [];
        $set_where[] = ['aid','=',aid];
        $set = Db::name('peisong_set')->where($set_where)->find();
        if($type == 'paotui_order'){
			$business = ['id'=>0,'name'=>$order['take_name'],'tel'=>$order['take_tel'],'city'=>$order['take_city'],'longitude'=>$order['take_longitude'],'latitude'=>$order['take_latitude'],'area'=>$order['take_area'],'address'=>$order['take_address']];
		}else{
			if($order['bid']>0){
				$business = Db::name('business')->field('name,address,tel,logo,longitude,latitude,money')->where('id',$order['bid'])->find();
			}elseif($order['mdid']>0){
                $business = Db::name('mendian')->field('name,address,tel,pic,longitude,latitude,money')->where('id',$order['mdid'])->find();
            }else{
				$business = Db::name('admin_set')->field('name,address,tel,logo,longitude,latitude')->where('aid',$aid)->find();
			}
		}

		//查询骑行距离
        $mapqq = new \app\common\MapQQ();
        $bicycl = $mapqq->getDirectionDistance($business['longitude'],$business['latitude'],$order['longitude'],$order['latitude'],1);
		if($bicycl && $bicycl['status']==1){
			$juli = $bicycl['distance'];
		}else{
			$juli = getdistance($order['longitude'],$order['latitude'],$business['longitude'],$business['latitude'],1);
		}

        $order_stage = $order;
		//订单信息
        if($type=='cycle_order_stage'){
            $orderinfo = Db::name('cycle_order')->where('id',$order['orderid'])->find();
            $order['paytime'] = $orderinfo['paytime'];
            $order['linkman'] = $orderinfo['linkman'];
            $order['tel'] = $orderinfo['tel'];
            $order['area'] = $orderinfo['area'];
            $order['address'] = $orderinfo['address'];
            $order['title'] = $orderinfo['title'];
            //设置订单 cycle_order 的状态为 已发货
            if($orderinfo['status'] ==1){
                Db::name('cycle_order')->where('id',$order['orderid'])->update(['status' => 2,'send_time' => time()]);
            }
        }elseif($type =='deposit_refund_order'){
            $orderinfo = [];
            $orderinfo['ordernum'] = $order['ordernum'];
            $orderinfo['createtime'] = $order['createtime'];
            $orderinfo['longitude'] = $order['longitude'];
            $orderinfo['latitude'] = $order['latitude'];
            $orderinfo['product_price'] = $order['totalmoney'];
            $orderinfo['totalprice'] = $order['totalmoney'];
            $orderinfo['linkman'] = $order['linkman'];
            $orderinfo['tel'] = $order['tel'];
            $orderinfo['area'] = $order['area'];
            $orderinfo['address'] = $order['address'];
            $orderinfo['paytype'] = '无';
        }else{
            $orderinfo = [];
            $orderinfo['id'] = $order['id'];
            $orderinfo['ordernum'] = $order['ordernum'];
            $orderinfo['createtime'] = $order['createtime'];
            $orderinfo['paytime'] = $order['paytime'];
            $orderinfo['paytype'] = $order['paytype'];
        	$orderinfo['product_price'] = $order['product_price'];
        	$orderinfo['freight_price'] = $order['freight_price'];
            $orderinfo['message'] = $order['message'];
            $orderinfo['linkman'] = $order['linkman'];
            $orderinfo['tel'] = $order['tel'];
            $orderinfo['area'] = $order['area'];
            $orderinfo['address'] = $order['address'];
            $orderinfo['longitude'] = $order['longitude'];
            $orderinfo['latitude'] = $order['latitude'];
            $orderinfo['totalprice'] = $order['totalprice'];
            if($type == 'paotui_order'){
            	$orderinfo['type'] 		       = 'paotui_order';
            	$orderinfo['expect_take_time'] = $order['take_time']?$order['take_time']:0;
            	$orderinfo['pic'] 			   = $order['pic']?$order['pic']:'';
            	$orderinfo['btntype'] 		   = $order['btntype']?$order['btntype']:'';
            	$orderinfo['take_tel'] 		   = $order['take_tel']?$order['take_tel']:'';
            	$orderinfo['send_tel'] 		   = $order['send_tel']?$order['send_tel']:'';
            }
            if($type=='restaurant_takeaway_order'){
                if($order['field1']){
                    $orderinfo['field1data'] = explode('^_^',$order['field1']);
                }
                if($order['field2']){
                    $orderinfo['field2data'] = explode('^_^',$order['field2']);
                }
                if($order['field3']){
                    $orderinfo['field3data'] = explode('^_^',$order['field3']);
                }
                if($order['field4']){
                    $orderinfo['field4data'] = explode('^_^',$order['field4']);
                }
                if($order['field5']){
                    $orderinfo['field5data'] = explode('^_^',$order['field5']);
                }
            }
        }
        if($type == 'paotui_order'){
        	$formdata = [];
        }else{
        	$formdata = \app\model\Freight::getformdata($order['id'],$type);
        }
        $orderinfo['formdata']  =  $formdata?$formdata:[];

		//商品信息
		if($type == 'shop_order' || $type=='scoreshop_order' || $type=='restaurant_takeaway_order' ){
			if($type=='scoreshop_order'){
				$prolist = Db::name($type.'_goods')->field('name,pic,sell_price,num')->where('orderid',$order['id'])->select()->toArray();
			}else{
			    $field =  'id,aid,name,ggname,pic,sell_price,num';
			    if($type == 'shop_order'){
                    $field .=',real_totalmoney';
                }
			    $prolist = Db::name($type.'_goods')->field($field)->where('orderid',$order['id'])->select()->toArray();
			}
			$orderinfo['procount'] = Db::name($type.'_goods')->where('orderid',$order['id'])->sum('num');
            if ($type == 'shop_order'){
                //减掉部分退款
                $newprolist = [];
                foreach($prolist as $ok=>$og){
                    
                    $refund_og = Db::name('shop_refund_order_goods')->alias('shop_refund_order_goods')
                        ->join('shop_refund_order','shop_refund_order.id = shop_refund_order_goods.refund_orderid')
                        ->where('shop_refund_order_goods.aid',$og['aid'])
                        ->where('shop_refund_order_goods.ogid',$og['id'])
                        ->where('shop_refund_order.refund_status',2)
                        ->find();
                  
                   if($refund_og['refund_money'] < $og['real_totalmoney']){
                       $newprolist[] =  $og;
                   }
                }
                $prolist = $newprolist;
            }
		}elseif($type == 'deposit_refund_order'){
		    }else{
			$proinfo = [];
			$proinfo['name'] = $order['proname'];
			$proinfo['ggname'] = $order['ggname'];
			$proinfo['pic'] = $order['propic'];
			$proinfo['sell_price'] = $order['sell_price'];
			$proinfo['num'] = $order['num'];
			$prolist = [$proinfo];
			$orderinfo['procount'] = $order['num'];
		}
		$psorderdata = [];
		$psorderdata['aid'] = $aid;
		$psorder_bid = $order['bid'];
		$psorderdata['bid'] = $psorder_bid;
		
        $psorderdata['mdid'] = $order['mdid'];
		$psorderdata['mid'] = $order['mid'];
		$psorderdata['psid'] = $psid;
		$psorderdata['orderid'] = $order['id'];
		$psorderdata['ordernum'] = $order['ordernum'];
		$psorderdata['createtime'] = time();

		$psorderdata['longitude'] = $business['longitude'];
		$psorderdata['latitude'] = $business['latitude'];
		$psorderdata['longitude2'] = $order['longitude'];
		$psorderdata['latitude2'] = $order['latitude'];

		if($psid != 0 && $psid!=-1 && $psid!=-2){ //指定配送员配送  -1码科配送 -2 麦芽田
			$psorderdata['status'] = 1;
			$psorderdata['starttime'] = time();
		}
		$psorderdata['type'] = $type;
		$psorderdata['juli'] = $juli;
		$psorderdata['yujitime'] = self::yujitime($set,$order,$juli/1000);
        $psorderdata['orderinfo'] =  jsonEncode($orderinfo);
		$psorderdata['prolist'] = jsonEncode($prolist);
		$psorderdata['binfo'] = jsonEncode($business);
		if($psid == -1){ //码科配送费
			if($type == 'paotui_order'){
				$rs = \app\common\Make::getprice($aid,$order['bid'],$order['take_latitude'],$order['take_longitude'],$order['send_latitude'],$order['send_longitude']);
			}else{
				$rs = \app\common\Make::getprice($aid,$order['bid'],$business['latitude'],$business['longitude'],$order['latitude'],$order['longitude']);
			}
			if($rs['status']==0) return $rs;
			$price = $rs['price'];
			$psorderdata['ticheng'] = $price;
			$psorderdata['psfee']   = $psorderdata['ticheng'] * (1 + $set['businessfee']*0.01);
		} else if($psid == -2){
			}else{
			$psorderdata['ticheng'] = self::ticheng($set,$order,$juli/1000);
			$psorderdata['psfee'] = $psorderdata['ticheng'] * (1 + $set['businessfee']*0.01);
			}
		//如果
         $is_psfee = 1;
		 if($order['bid']>0 && !getcustom('hmy_yuyue') &&  $is_psfee){
			if($psid!=-1 || $set['make_shopkoufei']==1){
				$businessMoney = Db::name('business')->where('id',$order['bid'])->value('money');
				if($businessMoney < $psorderdata['psfee']){
					return ['status'=>0,'msg'=>'商家余额不足'];
				}
				\app\common\Business::addmoney($aid,$order['bid'],-$psorderdata['psfee'],'配送费');
			}
		}
        //		$hasorder = Db::name('peisong_order')->where('type',$type)->where('orderid',$order['id'])->find();
		$psorderid = Db::name('peisong_order')->insertGetId($psorderdata);
		$psorder = Db::name('peisong_order')->where('id',$psorderid)->find();
		if($psid == -1){
			$rs = \app\common\Make::createorder($psorder);
			if($rs['status']== 0){
				Db::name('peisong_order')->where('id',$psorderid)->delete();
				return $rs;
			}
		}
		//发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,$type);
        }

	    if($type == 'paotui_order' ){
	    	Db::name($type)->where('aid',$aid)->where('id',$order['id'])->update(['express_com'=>'同城配送','express_no'=>$psorderid,'send_time'=>time()]);
	    }elseif ($type =='deposit_refund_order'){
	        }else{
		    Db::name($type)->where('aid',$aid)->where('id',$order['id'])->update(['express_com'=>'同城配送','express_no'=>$psorderid,'send_time'=>time(),'status'=>2]);
	    }

		if($type == 'shop_order'){
			Db::name('shop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>2]);
		}
		if($type == 'scoreshop_order'){
			Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>2]);
		}
		if($type == 'restaurant_takeaway_order'){
			Db::name('restaurant_takeaway_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>2]);
		}
		if($psid>=0){
			//发送到socket通知
            $socketdata =   ['aid'=>$aid,'psorderid'=>$psorderid];
            send_socket(['type'=>'peisong','data'=>$socketdata]);
		}
		if($psid>0){
			//新配送订单通知
			$psmid = Db::name('peisong_user')->where('id',$psid)->value('mid');
			$tmplcontent = [];
			$tmplcontent['first'] = '您有新的订单待配送，请及时配送';
			$tmplcontent['remark'] = '请点击查看详情~';
			$tmplcontent['keyword1'] = $order['linkman'];
			$tmplcontent['keyword2'] = $order['tel'];
			$tmplcontent['keyword3'] = $order['area'] .' '. $order['address'];
			$tmplcontent['keyword4'] = $order['title'];
			$tmplcontent['keyword5'] = date('Y-m-d H:i',$order['paytime']);
            $tempconNew = [];
            $tempconNew['character_string1'] = $order['ordernum'];//订单编号
            $tempconNew['thing16'] = $business['name'];//门店名称
            $tempconNew['thing8'] = $order['title'];//商品名称
            $tempconNew['thing5'] = $order['address']?$order['address']:'无';//客户地址
            $tempconNew['time2'] = date('Y-m-d H:i',$order['paytime']);//订单时间
			\app\common\Wechat::sendtmpl($aid,$psmid,'tmpl_peisongorder',$tmplcontent,m_url('activity/peisong/orderlist', $aid),$tempconNew);
		}else if($psid==0){
			$psuserlist = Db::name('peisong_user')->where('aid',$aid)->where('status',1)->select()->toArray();
			foreach($psuserlist as $psuser){
				//新配送订单通知
				$psmid = Db::name('peisong_user')->where('id',$psuser['id'])->value('mid');
				$tmplcontent = [];
				$tmplcontent['first'] = '['.$business['name'].']有新的配送订单待接单';
				$tmplcontent['remark'] = '点击查看详情~';
                $tmplcontent['keyword1'] = $order['linkman'];
                $tmplcontent['keyword2'] = $order['tel'];
				$tmplcontent['keyword3'] = $order['area'] .' '. $order['address'];
				$tmplcontent['keyword4'] = $order['title'];
				$tmplcontent['keyword5'] = date('Y-m-d H:i',$order['paytime']);
                $tempconNew = [];
                $tempconNew['character_string1'] = $order['ordernum'];//订单编号
                $tempconNew['thing16'] = $business['name'];//门店名称
                $tempconNew['thing8'] = $order['title'];//商品名称
                $tempconNew['thing5'] = $order['address']?$order['address']:'无';//客户地址
                $tempconNew['time2'] = date('Y-m-d H:i',$order['paytime']);//订单时间
				\app\common\Wechat::sendtmpl($aid,$psmid,'tmpl_peisongorder',$tmplcontent,m_url('activity/peisong/dating', $aid),$tempconNew);
			}
		}
		//订单发货通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的订单已分派配送人员进行配送';
		$tmplcontent['remark'] = '请点击查看详情~';
		$tmplcontent['keyword1'] = $order['title'];
		$tmplcontent['keyword2'] = '同城配送';
		$tmplcontent['keyword3'] = $psorder['ordernum'];
		$tmplcontent['keyword4'] = $order['linkman'].' '.$order['tel'];
        $tmplcontentNew = [];
        $tmplcontentNew['thing4'] = $order['title'];//商品名称
        $tmplcontentNew['thing13'] = '同城配送';//快递公司
        $tmplcontentNew['character_string14'] = $psorder['ordernum'];//快递单号
        $tmplcontentNew['thing16'] = $order['linkman'].' '.$order['tel'];//收货人
		\app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderfahuo',$tmplcontent,m_url('pages/my/usercenter', $aid),$tmplcontentNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['thing2'] = $order['title'];
		$tmplcontent['thing7'] = '同城配送';
		$tmplcontent['character_string4'] = $psorder['ordernum'];
		$tmplcontent['thing11'] = $order['address'];

		$tmplcontentnew = [];
		$tmplcontentnew['thing29'] = $order['title'];
		$tmplcontentnew['thing1'] = '同城配送';
		$tmplcontentnew['character_string2'] = $psorder['ordernum'];
		$tmplcontentnew['thing9'] = $order['address'];
		\app\common\Wechat::sendwxtmpl($aid,$order['mid'],'tmpl_orderfahuo',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

		//短信通知
		$member = Db::name('member')->where('id',$order['mid'])->find();
		if($member['tel']){
			$tel = $member['tel'];
		}else{
			$tel = $order['tel'];
		}
		$rs = \app\common\Sms::send($aid,$tel,'tmpl_orderfahuo',['ordernum'=>$order['ordernum'],'express_com'=>'同城配送','express_no'=>'']);
		return ['status'=>1,'msg'=>''];
	}
	//计算配送员提成
	public static function ticheng($set,$order,$juli){
		if($set['jiesuantype']==0){
			$ticheng = $set['tcmoney'];
		}else{
			$ticheng = floatval($set['peisong_tcmoney1']);
			if($juli - floatval($set['peisong_juli1']) > 0 && floatval($set['peisong_juli2']) > 0){
				$ticheng += ceil(($juli - floatval($set['peisong_juli1']))/floatval($set['peisong_juli2'])) * floatval($set['peisong_tcmoney2']);
			}
		}
		if($set['peisong_tcmoneymax'] > 0 && $ticheng > $set['peisong_tcmoneymax']) $ticheng = $set['peisong_tcmoneymax'];
		return $ticheng;
	}
	//计算预计送达时间
	public static function yujitime($set,$order,$juli){
		$psminute = floatval($set['yuji_psminute1']);
		if($juli - floatval($set['yuji_psjuli1']) > 0 && floatval($set['yuji_psjuli2']) > 0){
			$psminute += ceil(($juli - floatval($set['yuji_psjuli1']))/floatval($set['yuji_psjuli2'])) * floatval($set['yuji_psminute2']);
		}
		$paytime =   $order['paytime'];
		if(!$paytime){
            $paytime =   $order['createtime'];
        }
		$yujitime =$paytime + $psminute*60;
		return $yujitime;
	}

	//取消配送单
	public static function quxiao($order){
		$aid = $order['aid'];
		$data = [];
		$set = Db::name('peisong_set')->where('aid',$aid)->find();

		if($order['type'] !='paotui_order' && $order['psid'] !=-2 && $order['status']!=10 && $order['bid'] > 0 && $order['psfee'] > 0){
			if($order['psid']!=-1 || $set['make_shopkoufei']==1){
				\app\common\Business::addmoney($order['aid'],$order['bid'],$order['psfee'],'取消配送返还配送费');
			}
		}

		if($order['psid']==-1){
			$rs = \app\common\Make::cancelorder($order);
			if($rs['status']== 0) return $rs;
		}else if($order['psid']==-2){
			}

		Db::name('peisong_order')->where('id',$order['id'])->update(['status'=>10]);

		return ['status'=>1,'msg'=>'取消成功'];
	}

    public static function getStatusTxt($st){
        $status = [
           '-1'=>'未派单','0'=>'待接单', '1'=>'已接单', '2'=>'已到店', '3'=>'已取货','4'=>'已完成','10'=>'已取消'
        ];
        if(isset($status[$st])){
            return $status[$st];
        }else{
            return $st;
        }
    }
}

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
// | 电子面单 物流助手
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\Log;
use think\facade\View;
use think\facade\Db;
use app\common\Wechat;

class Miandan extends Common
{
	//运单信息
	public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'id desc';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			$where[] = ['status','<>',0];
			if(input('param.order_id')) $where[] = ['order_id','=',input('param.order_id')];
			if(input('param.waybill_id')) $where[] = ['waybill_id','=',input('param.waybill_id')];
			if(input('param.name')) $where[] = ['receiver_mobile|receiver_tel|receiver_name','like','%'.input('param.name').'%'];
			if(input('param.tel')) $where[] = ['receiver_mobile|receiver_tel','like','%'.input('param.tel').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			$count = 0 + Db::name('miandan_order')->where($where)->count();
			$data = Db::name('miandan_order')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
//        $sys = Db::name('admin_set')->where('aid',aid)->find();2.6.7废弃
//        View::assign('miandanst',$sys['miandanst']);
//        View::assign('miandan_wx',$sys['miandan_wx']);
		return View::fetch();
	}
	//生成运单 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/express/express-by-business/addOrder.html
	public function addorder(){
		$ordertype = input('param.ordertype');
		$orderid = input('param.orderid/d');
		$access_token = Wechat::access_token(aid,'wx');
        $miandanset = Db::name('miandan_set')->where('aid',aid)->where('bid',bid)->find();

		if(request()->isPost()){
			$postinfo = input('post.info/a');
			$express_num = 1;
			//生成多个面单
			if($postinfo['express_num']){
				$express_num = intval($postinfo['express_num']);
			}
			if($express_num <1){
				$express_num = 1;
			}
			for($i=0;$i<$express_num;$i++){
				$postinfo['cargo_detail_list_name'] = input('post.cargo_detail_list_name/a');
				$postinfo['cargo_detail_list_count'] = input('post.cargo_detail_list_count/a');
                $postinfo['goodsList'] = input('post.goodsList/a');
				
				$adddata = [];
				$adddata['aid'] = aid;
				$adddata['orderid'] = $orderid;
				$adddata['ordertype'] = $ordertype;
				$adddata['mid'] = $postinfo['mid'];
				$adddata['add_source'] = $postinfo['add_source'];
				$adddata['wx_appid'] = $postinfo['wx_appid'];
				$adddata['order_id'] = $postinfo['order_id'];
				$adddata['openid'] = $postinfo['openid'];

				$delivery_info = explode(',',$postinfo['delivery_info']);
				$adddata['biz_id'] = $delivery_info[0];
				$adddata['delivery_id'] = $delivery_info[1];
				$adddata['delivery_name'] = $delivery_info[2];
				$adddata['service_type'] = $delivery_info[3];
				$adddata['service_name'] = $delivery_info[4];
				$adddata['expect_time'] = $postinfo['expect_time'] ? strtotime($postinfo['expect_time']) : 0;
				$adddata['custom_remark'] = $postinfo['custom_remark'];

				$adddata['receiver_name'] = $postinfo['receiver_name'];
				$adddata['receiver_mobile'] = $postinfo['receiver_mobile'];
				$adddata['receiver_province'] = $postinfo['receiver_province'];
				$adddata['receiver_city'] = $postinfo['receiver_city'];
				$adddata['receiver_area'] = $postinfo['receiver_area'];
				$adddata['receiver_address'] = $postinfo['receiver_address'];
				$adddata['receiver_tel'] = $postinfo['receiver_tel'];
				$adddata['receiver_company'] = $postinfo['receiver_company'];
				$adddata['receiver_post_code'] = $postinfo['receiver_post_code'];

				$adddata['sender_name'] = $postinfo['sender_name'];
				$adddata['sender_mobile'] = $postinfo['sender_mobile'];
				$adddata['sender_province'] = $postinfo['sender_province'];
				$adddata['sender_city'] = $postinfo['sender_city'];
				$adddata['sender_area'] = $postinfo['sender_area'];
				$adddata['sender_address'] = $postinfo['sender_address'];
				$adddata['sender_tel'] = $postinfo['sender_tel'];
				$adddata['sender_company'] = $postinfo['sender_company'];
				$adddata['sender_post_code'] = $postinfo['sender_post_code'];

				$adddata['cargo_count'] = $postinfo['cargo_count'];
				$adddata['cargo_weight'] = $postinfo['cargo_weight'];
				$adddata['cargo_space_x'] = $postinfo['cargo_space_x'];
				$adddata['cargo_space_y'] = $postinfo['cargo_space_y'];
				$adddata['cargo_space_z'] = $postinfo['cargo_space_z'];

				$cargo_detail_list = [];
				foreach($postinfo['cargo_detail_list_name'] as $k=>$v){
					$cargo_detail_list[] = ['name'=>$v,'count'=>$postinfo['cargo_detail_list_count'][$k]];
				}
				$adddata['cargo_detail_list'] = jsonEncode($cargo_detail_list);
				$adddata['shop_wxa_path'] = $postinfo['shop_wxa_path'];
				$adddata['shop_img_url'] = $postinfo['shop_img_url'];
				$adddata['shop_goods_name'] = $postinfo['shop_goods_name'];
				$adddata['shop_goods_count'] = $postinfo['shop_goods_count'];
                $adddata['shop_detail_list'] = $postinfo['goodsList'] ? jsonEncode($postinfo['goodsList']) : null;
				
				$adddata['use_insured'] = $postinfo['use_insured'] ? 1 : 0;
				$adddata['insured_value'] = $postinfo['insured_value'];

				$miandanid = Db::name('miandan_order')->insertGetId($adddata);

				$postdata = [];
				if($i>0){
					$postdata['order_id'] = $adddata['order_id'].'_'.$i;
				}else{
					$postdata['order_id'] = $adddata['order_id'];
				}
				
				$postdata['add_source'] = $adddata['add_source'];
				if($postdata['add_source']==2){
					$postdata['wx_appid'] = $adddata['wx_appid'];
				}
				if($adddata['openid']){
					$postdata['openid'] = $adddata['openid'];
				}
				$postdata['delivery_id'] = $adddata['delivery_id']; //TEST
				$postdata['biz_id'] = $adddata['biz_id']; //test_biz_id
				if($adddata['custom_remark']){
					$postdata['custom_remark'] = $adddata['custom_remark'];
				}

				$sender = [];
				$sender['name'] = $adddata['sender_name'];
				if($adddata['sender_mobile']){
					$sender['mobile'] = $adddata['sender_mobile'];
				}
				$sender['country'] = '中国';
				$sender['province'] = $adddata['sender_province'];
				$sender['city'] = $adddata['sender_city'];
				$sender['area'] = $adddata['sender_area'];
				$sender['address'] = $adddata['sender_address'];
				if($adddata['sender_tel']){
					$sender['tel'] = $adddata['sender_tel'];
				}
				if($adddata['sender_company']){
					$sender['company'] = $adddata['sender_company'];
				}
				if($adddata['sender_post_code']){
					$sender['post_code'] = $adddata['sender_post_code'];
				}
				$postdata['sender'] = $sender;
				
				$receiver = [];
				$receiver['name'] = $adddata['receiver_name'];
				if($adddata['receiver_mobile']){
					$receiver['mobile'] = $adddata['receiver_mobile'];
				}
				$receiver['country'] = '中国';
				$receiver['province'] = $adddata['receiver_province'];
				$receiver['city'] = $adddata['receiver_city'];
				$receiver['area'] = $adddata['receiver_area'];
				$receiver['address'] = $adddata['receiver_address'];
				if($adddata['receiver_tel']){
					$receiver['tel'] = $adddata['receiver_tel'];
				}
				if($adddata['receiver_company']){
					$receiver['company'] = $adddata['receiver_company'];
				}
				if($adddata['receiver_post_code']){
					$receiver['post_code'] = $adddata['receiver_post_code'];
				}
				$postdata['receiver'] = $receiver;
				
				$cargo = [];
				$cargo['count'] = $adddata['cargo_count'];
				$cargo['weight'] = $adddata['cargo_weight'];
				$cargo['space_x'] = $adddata['cargo_space_x'];
				$cargo['space_y'] = $adddata['cargo_space_y'];
				$cargo['space_z'] = $adddata['cargo_space_z'];
				$cargo['detail_list'] = $cargo_detail_list;
				$postdata['cargo'] = $cargo;//包裹信息，将传递给快递公司

				$shop = [];
				$shop['wxa_path'] = $adddata['shop_wxa_path'];
				$shop['img_url'] = $adddata['shop_img_url'];
				$shop['goods_name'] = $adddata['shop_goods_name'];//商品名称, 不超过128字节
				$shop['goods_count'] = $adddata['shop_goods_count'];
                if($adddata['shop_detail_list']){//miandan_print_allproduct
                    foreach ($postinfo['goodsList'] as $goods){
                        if($shop['goods_name']) $shop['goods_name'] .= ',';
                        $shop['goods_name'] .= $goods['goods_name'].'*'.$goods['goods_desc'];
                    }
                    $shop['detail_list'] = $postinfo['goodsList'];
                }
                else
				    $shop['detail_list'] = [['goods_name'=>$adddata['shop_goods_name'],'goods_img_url'=>$adddata['shop_img_url'],'goods_desc'=>'']];
                $shop['goods_name'] = mb_substr($shop['goods_name'],0,32);//商品名称, 不超过128字节
				$postdata['shop'] = $shop;//商品信息，会展示到物流服务通知和电子面单中

				$insured = [];
				$insured['use_insured'] = $adddata['use_insured'];
				$insured['insured_value'] = $adddata['insured_value']*100;
				$postdata['insured'] = $insured;

				$service = [];
				$service['service_type'] = $adddata['service_type']; //1
				$service['service_name'] = $adddata['service_name']; //test_service_name
				$postdata['service'] = $service;
				
				$postdata['expect_time'] = $adddata['expect_time'];
				//物流助手 /小程序使用 /生成运单 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/express/express-by-business/addOrder.html
				$url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/add?access_token='.$access_token;
				
				$rs = request_post($url,jsonEncode($postdata));
				$rs = json_decode($rs,true);
				
				if($rs['errcode'] && $rs['errcode']!=0){
					Log::write([
						'file'=>__FILE__.__LINE__,
						'postdata'=>jsonEncode($postdata),
						'addOrder-rs'=>$rs
					]);
					return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($rs) . "<br>" .$rs['delivery_resultmsg'].'('.$rs['delivery_resultcode'].')']);
				}

				Db::name('miandan_order')->where('id',$miandanid)->update(['status'=>1,'waybill_id'=>$rs['waybill_id'],'waybill_data'=>jsonEncode($rs['waybill_data'])]);
				
				$express_com = $adddata['delivery_name'];
				$express_no = $rs['waybill_id'];

				if($express_com == '顺丰现付') $express_com = '顺丰速运';
				if($express_com == '德邦现付') $express_com = '德邦快递';

				$express_comArr[] = [
					'express_com'=>$express_com,
					'express_no'=>$express_no
				];

				if($ordertype == 'shop_order' || $ordertype == 'scoreshop_order'){
					Db::name($ordertype)->where('aid',aid)->where('id',$orderid)->update(['express_com'=>$express_com,'express_no'=>$express_no,'send_time'=>time(),'status'=>2]);
					Db::name($ordertype.'_goods')->where('aid',aid)->where('orderid',$orderid)->update(['status'=>2]);

					$order = Db::name($ordertype)->where('aid',aid)->where('id',$orderid)->find();

					if($order['fromwxvideo'] == 1){
						\app\common\Wxvideo::deliverysend($orderid);
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
					\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_orderfahuo',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
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
					$member = Db::name('member')->where('id',$order['mid'])->find();
					if($member['tel']){
						$tel = $member['tel'];
					}else{
						$tel = $order['tel'];
					}
					$rs = \app\common\Sms::send(aid,$tel,'tmpl_orderfahuo',['ordernum'=>$order['ordernum'],'express_com'=>$express_com,'express_no'=>$express_no]);
				}
				//发货信息录入 微信小程序+微信支付
				$order = Db::name($ordertype)->where('aid',aid)->where('id',$orderid)->find();
				if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
					\app\common\Order::wxShipping($order['aid'],$order,$ordertype,['express_com'=>$express_com,'express_no'=>$express_no]);
				}
			}
			if($ordertype == 'shop_order'){
				if(count($express_comArr) > 1){
					$express_com = '多单发货';
					$express_no = '';
					$express_content = [];
					foreach($express_comArr as $k=>$v){
						$express_content[] = ['express_com'=>$v['express_com'],'express_no'=>$v['express_no']];
					}
					$express_content = jsonEncode($express_content);
				}
				Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->update(['express_com'=>$express_com,'express_no'=>$express_no,'express_content'=>$express_content]);
			}
			\app\common\System::plog('生成运单'.$miandanid);
			return json(['status'=>1,'msg'=>'发货成功']);
		}

		$info = [];
		
		$cargo_detail_list = [];
		if($ordertype == 'shop_order' || $ordertype == 'scoreshop_order'){
            if($ordertype == 'shop_order'){
                $refundingMoney = Db::name('shop_refund_order')->where('orderid',$orderid)->where('aid',aid)->whereIn('refund_status',[1,4])->sum('refund_money');
                if($refundingMoney > 0) {
                    return json(['status'=>0,'msg'=>'请先处理完订单关联的退款单']);
                }
            }
			$order = Db::name($ordertype)->where('aid',aid)->where('id',$orderid)->find();
			$ordergoods = Db::name($ordertype.'_goods')->where('aid',aid)->where('orderid',$orderid)->select()->toArray();
			$shop_goods_count = 0;

            $goodsList = [];
			foreach($ordergoods as $v){
                $temp = [];
				if($v['num']>$v['refund_num']){
					$v['num'] = $v['num']-$v['refund_num'];
					$cargo_detail_list[] = ['name'=>mb_substr($v['name'],0,32),'count'=>$v['num']];
					$shop_goods_count += $v['num'];
                    if(getcustom('miandan_print_allproduct') && $miandanset['print_allproduct']){
                        if($ordertype == 'shop_order'){
                            $print_name = Db::name('shop_product')->where('aid',aid)->where('id',$v['proid'])->value('print_name');
                            if($print_name) $temp['goods_name'] = $print_name.'['.$v['ggname'].']';
                            else $temp['goods_name'] = mb_substr($v['name'],0,32).'['.$v['ggname'].']';
                        }else{
                            $temp['goods_name'] = mb_substr($v['name'],0,32).$v['ggname'];
                        }
                        $temp['goods_img_url'] = $v['pic'];
                        $temp['goods_desc'] = $v['num'];
                        $goodsList[] = $temp;
                    }
				}
			}
			//商品信息，会展示到物流服务通知和电子面单中
			$info['shop_wxa_path'] = 'pagesExt/order/orderlist';
			$info['shop_img_url'] = $ordergoods[0]['pic'];
			$info['shop_goods_name'] = $order['title'];
			$info['shop_goods_count'] = $shop_goods_count;
			//收件人信息
			$info['receiver_name'] = $order['linkman'];
			if(strlen($order['tel']) == 11){
				$info['receiver_mobile'] = $order['tel'];
			}else{
				$info['receiver_tel'] = $order['tel'];
			}
			$area2 = explode(',',$order['area2']);
			$info['receiver_province'] = $area2[0];
			$info['receiver_city'] = $area2[1];
			$info['receiver_area'] = $area2[2];
			$info['receiver_address'] = $order['address'];
		}
		$member = Db::name('member')->where('id',$order['mid'])->find();

		$info['mid'] = $order['mid'];
		$info['add_source'] = $member['wxopenid'] ? 0 : 2;//订单来源，0为小程序订单，2为App或H5订单，填2则不发送物流服务通知
		if($info['add_source']==2){
			$appinfo = \app\common\System::appinfo(aid,'mp');
			$appid = $appinfo['appid'];
			$info['wx_appid'] = $appid;
		}else{
			$info['openid'] = $member['wxopenid'];
		}
		$info['order_id'] = $order['ordernum'];

        //获取支持的快递公司列表,该接口所属的权限集id为：45、71
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token='.$access_token;
		$res = request_get($url);
		$res = json_decode($res,true);
		if($res['errcode'] && $res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
		}
		$deliveryList = $res['data'];
		$deliveryArr = [];
		foreach($deliveryList as $k=>$v){
			$deliveryArr[$v['delivery_id']] = $v['delivery_name'];
		}

		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/getall?access_token='.$access_token;
		$res = request_get($url);
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
		}
		$deliverylist = [];
		foreach($res['list'] as $k=>$v){
			if($v['status_code'] == 0){
				$v['delivery_name'] = $deliveryArr[$v['delivery_id']];
				$deliverylist[] = ['biz_id'=>$v['biz_id'],'delivery_id'=>$v['delivery_id'],'delivery_name'=>$v['delivery_name'],'service_type'=>$v['service_type']];
			}
		}
		$deliverylist[] = ['biz_id'=>'SF_CASH','delivery_id'=>'SF','delivery_name'=>'顺丰现付','service_type'=>[['service_type'=>'0','service_name'=>'标准快递'],['service_type'=>'1','service_name'=>'顺丰即日'],['service_type'=>'2','service_name'=>'顺丰次晨'],['service_type'=>'3','service_name'=>'顺丰标快'],['service_type'=>'4','service_name'=>'顺丰标快（陆运）'],]];
		$deliverylist[] = ['biz_id'=>'DB_CASH','delivery_id'=>'DB','delivery_name'=>'德邦现付','service_type'=>[['service_type'=>'1','service_name'=>'大件快递3.60'],['service_type'=>'2','service_name'=>'特准快件']]];

		$lastorder = Db::name('miandan_order')->where('aid',aid)->order('id desc')->find();
		if(!$lastorder) $lastorder = [];
		View::assign('info',$info);
		View::assign('lastorder',$lastorder);
		View::assign('deliverylist',$deliverylist);
		View::assign('cargo_detail_list',$cargo_detail_list);
        View::assign('goodsList',$goodsList);
		return View::fetch();
	}
	//批量生成运单
	public function pladdorder(){
		if(getcustom('miandan_batch_shipping')){
		$ordertype = input('param.ordertype');
		$orderids = input('param.orderid');
		$access_token = Wechat::access_token(aid,'wx');

		if(request()->isPost()){
			$postinfo = input('post.info/a');

			$orderids = explode(',',$orderids);
			foreach($orderids as $orderid){
                $order = Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->find();
                if($order['status'] !=1){
                    continue;
                }
                $ordergoods = Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$orderid)->select()->toArray();
                $shop_goods_count = 0;
                $cargo_detail_list = [];
                foreach($ordergoods as $v){
                    if($v['num']>$v['refund_num']){
                        $v['num'] = $v['num']-$v['refund_num'];
                        $cargo_detail_list[] = ['name'=>mb_substr($v['name'],0,32),'count'=>$v['num']];
                        $shop_goods_count += $v['num'];
                    }
                }
                //商品信息，会展示到物流服务通知和电子面单中
                $postinfo['shop_wxa_path'] = 'pagesExt/order/orderlist';
                $postinfo['shop_img_url'] = $ordergoods[0]['pic'];
                $postinfo['shop_goods_name'] = $order['title'];
                $postinfo['shop_goods_count'] = $shop_goods_count;
                //收件人信息
                $postinfo['receiver_name'] = $order['linkman'];

                $postinfo['receiver_mobile'] = '';
                $postinfo['receiver_tel'] = '';
                if(strlen($order['tel']) == 11){
                    $postinfo['receiver_mobile'] = $order['tel'];
                }else{
                    $postinfo['receiver_tel'] = $order['tel'];
                }
                $area2 = explode(',',$order['area2']);
                $postinfo['receiver_province'] = $area2[0];
                $postinfo['receiver_city'] = $area2[1];
                $postinfo['receiver_area'] = $area2[2];
                $postinfo['receiver_address'] = $order['address'];
                $member = Db::name('member')->where('id',$order['mid'])->find();
                $postinfo['mid'] = $order['mid'];
                $postinfo['add_source'] = $member['wxopenid'] ? 0 : 2;
                $postinfo['wx_appid'] = '';
                $postinfo['openid'] = '';
                if($postinfo['add_source']==2){
                    $appinfo = \app\common\System::appinfo(aid,'mp');
                    $appid = $appinfo['appid'];
                    $postinfo['wx_appid'] = $appid;
                }else{
                    $postinfo['openid'] = $member['wxopenid'];
                }

                $postinfo['order_id'] = $order['ordernum'];
                //发件人信息
                // if(!$lastorder){
                // 	return json(['status'=>0,'msg'=>'请先单独使用物流助手创建一次发货单']);
                // }
                // $postinfo['sender_name'] = $lastorder['sender_name'];
                // $postinfo['sender_mobile'] = $lastorder['sender_mobile'];
                // $postinfo['sender_province'] = $lastorder['sender_province'];
                // $postinfo['sender_city'] = $lastorder['sender_city'];
                // $postinfo['sender_area'] = $lastorder['sender_area'];
                // $postinfo['sender_address'] = $lastorder['sender_address'];
                // $postinfo['sender_tel'] = $lastorder['sender_tel'];
                // $postinfo['sender_company'] = $lastorder['sender_company'];
                // $postinfo['sender_post_code'] = $lastorder['sender_post_code'];
                //包裹信息
                $postinfo['cargo_count'] = count($cargo_detail_list);
                $postinfo['cargo_weight'] = 1;
                $postinfo['cargo_space_x'] = 10;
                $postinfo['cargo_space_y'] = 10;
                $postinfo['cargo_space_z'] = 10;

                //end 构造数据
				
				$adddata = [];
				$adddata['aid'] = aid;
				$adddata['orderid'] = $orderid;
				$adddata['ordertype'] = $ordertype;
				$adddata['mid'] = $postinfo['mid'];
				$adddata['add_source'] = $postinfo['add_source'];
				$adddata['wx_appid'] = $postinfo['wx_appid'];
				$adddata['order_id'] = $postinfo['order_id'];
				$adddata['openid'] = $postinfo['openid'];

				$delivery_info = explode(',',$postinfo['delivery_info']);
				$adddata['biz_id'] = $delivery_info[0];
				$adddata['delivery_id'] = $delivery_info[1];
				$adddata['delivery_name'] = $delivery_info[2];
				$adddata['service_type'] = $delivery_info[3];
				$adddata['service_name'] = $delivery_info[4];
				$adddata['expect_time'] = $postinfo['expect_time'] ? strtotime($postinfo['expect_time']) : 0;
				$adddata['custom_remark'] = $postinfo['custom_remark'];

				$adddata['receiver_name'] = $postinfo['receiver_name'];
				$adddata['receiver_mobile'] = $postinfo['receiver_mobile'];
				$adddata['receiver_province'] = $postinfo['receiver_province'];
				$adddata['receiver_city'] = $postinfo['receiver_city'];
				$adddata['receiver_area'] = $postinfo['receiver_area'];
				$adddata['receiver_address'] = $postinfo['receiver_address'];
				$adddata['receiver_tel'] = $postinfo['receiver_tel'];
				$adddata['receiver_company'] = $postinfo['receiver_company'];
				$adddata['receiver_post_code'] = $postinfo['receiver_post_code'];

				$adddata['sender_name'] = $postinfo['sender_name'];
				$adddata['sender_mobile'] = $postinfo['sender_mobile'];
				$adddata['sender_province'] = $postinfo['sender_province'];
				$adddata['sender_city'] = $postinfo['sender_city'];
				$adddata['sender_area'] = $postinfo['sender_area'];
				$adddata['sender_address'] = $postinfo['sender_address'];
				$adddata['sender_tel'] = $postinfo['sender_tel'];
				$adddata['sender_company'] = $postinfo['sender_company'];
				$adddata['sender_post_code'] = $postinfo['sender_post_code'];

				$adddata['cargo_count'] = $postinfo['cargo_count'];
				$adddata['cargo_weight'] = $postinfo['cargo_weight'];
				$adddata['cargo_space_x'] = $postinfo['cargo_space_x'];
				$adddata['cargo_space_y'] = $postinfo['cargo_space_y'];
				$adddata['cargo_space_z'] = $postinfo['cargo_space_z'];

				
				$adddata['cargo_detail_list'] = jsonEncode($cargo_detail_list);
				$adddata['shop_wxa_path'] = $postinfo['shop_wxa_path'];
				$adddata['shop_img_url'] = $postinfo['shop_img_url'];
				$adddata['shop_goods_name'] = $postinfo['shop_goods_name'];
				$adddata['shop_goods_count'] = $postinfo['shop_goods_count'];
				
				$adddata['use_insured'] = $postinfo['use_insured'] ? 1 : 0;
				$adddata['insured_value'] = $postinfo['insured_value'];

				$miandanid = Db::name('miandan_order')->insertGetId($adddata);

				$postdata = [];
	
				$postdata['order_id'] = $adddata['order_id'];
	
				
				$postdata['add_source'] = $adddata['add_source'];
				if($postdata['add_source']==2){
					$postdata['wx_appid'] = $adddata['wx_appid'];
				}
				if($adddata['openid']){
					$postdata['openid'] = $adddata['openid'];
				}
				$postdata['delivery_id'] = $adddata['delivery_id']; //TEST
				$postdata['biz_id'] = $adddata['biz_id']; //test_biz_id
				if($adddata['custom_remark']){
					$postdata['custom_remark'] = $adddata['custom_remark'];
				}

				$sender = [];
				$sender['name'] = $adddata['sender_name'];
				if($adddata['sender_mobile']){
					$sender['mobile'] = $adddata['sender_mobile'];
				}
				$sender['country'] = '中国';
				$sender['province'] = $adddata['sender_province'];
				$sender['city'] = $adddata['sender_city'];
				$sender['area'] = $adddata['sender_area'];
				$sender['address'] = $adddata['sender_address'];
				if($adddata['sender_tel']){
					$sender['tel'] = $adddata['sender_tel'];
				}
				if($adddata['sender_company']){
					$sender['company'] = $adddata['sender_company'];
				}
				if($adddata['sender_post_code']){
					$sender['post_code'] = $adddata['sender_post_code'];
				}
				$postdata['sender'] = $sender;
				
				$receiver = [];
				$receiver['name'] = $adddata['receiver_name'];
				if($adddata['receiver_mobile']){
					$receiver['mobile'] = $adddata['receiver_mobile'];
				}
				$receiver['country'] = '中国';
				$receiver['province'] = $adddata['receiver_province'];
				$receiver['city'] = $adddata['receiver_city'];
				$receiver['area'] = $adddata['receiver_area'];
				$receiver['address'] = $adddata['receiver_address'];
				if($adddata['receiver_tel']){
					$receiver['tel'] = $adddata['receiver_tel'];
				}
				if($adddata['receiver_company']){
					$receiver['company'] = $adddata['receiver_company'];
				}
				if($adddata['receiver_post_code']){
					$receiver['post_code'] = $adddata['receiver_post_code'];
				}
				$postdata['receiver'] = $receiver;
				
				$cargo = [];
				$cargo['count'] = $adddata['cargo_count'];
				$cargo['weight'] = $adddata['cargo_weight'];
				$cargo['space_x'] = $adddata['cargo_space_x'];
				$cargo['space_y'] = $adddata['cargo_space_y'];
				$cargo['space_z'] = $adddata['cargo_space_z'];
				$cargo['detail_list'] = $cargo_detail_list;
				$postdata['cargo'] = $cargo;

				$shop = [];
				$shop['wxa_path'] = $adddata['shop_wxa_path'];
				$shop['img_url'] = $adddata['shop_img_url'];
				$shop['goods_name'] = $adddata['shop_goods_name'];
				$shop['goods_count'] = $adddata['shop_goods_count'];
				$shop['detail_list'] = [['goods_name'=>$adddata['shop_goods_name'],'goods_img_url'=>$adddata['shop_img_url'],'goods_desc'=>'']];
				$postdata['shop'] = $shop;

				$insured = [];
				$insured['use_insured'] = $adddata['use_insured'];
				$insured['insured_value'] = $adddata['insured_value']*100;
				$postdata['insured'] = $insured;

				$service = [];
				$service['service_type'] = $adddata['service_type']; //1
				$service['service_name'] = $adddata['service_name']; //test_service_name
				$postdata['service'] = $service;
				
				$postdata['expect_time'] = $adddata['expect_time'];
				
				$url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/add?access_token='.$access_token;
				
				$rs = request_post($url,jsonEncode($postdata));
				$rs = json_decode($rs,true);
				
				if($rs['errcode'] && $rs['errcode']!=0){
					Log::write([
						'file'=>__FILE__.__LINE__,
						'postdata'=>jsonEncode($postdata),
						'addOrder-rs'=>$rs
					]);
					return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($rs) . "<br>" .$rs['delivery_resultmsg'].'('.$rs['delivery_resultcode'].')']);
				}

				Db::name('miandan_order')->where('id',$miandanid)->update(['status'=>1,'waybill_id'=>$rs['waybill_id'],'waybill_data'=>jsonEncode($rs['waybill_data'])]);
				
				$express_com = $adddata['delivery_name'];
				$express_no = $rs['waybill_id'];

				if($express_com == '顺丰现付') $express_com = '顺丰速运';
				if($express_com == '德邦现付') $express_com = '德邦快递';

				$express_comArr[] = [
					'express_com'=>$express_com,
					'express_no'=>$express_no
				];

				if($ordertype == 'shop_order'){
					Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->update(['express_com'=>$express_com,'express_no'=>$express_no,'send_time'=>time(),'status'=>2]);
					Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$orderid)->update(['status'=>2]);

					$order = Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->find();

					if($order['fromwxvideo'] == 1){
						\app\common\Wxvideo::deliverysend($orderid);
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
					\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_orderfahuo',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
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
					if($member['tel']){
						$tel = $member['tel'];
					}else{
						$tel = $order['tel'];
					}
					$rs = \app\common\Sms::send(aid,$tel,'tmpl_orderfahuo',['ordernum'=>$order['ordernum'],'express_com'=>$express_com,'express_no'=>$express_no]);
					//发货信息录入 微信小程序+微信支付
					$order = Db::name($ordertype)->where('aid',aid)->where('id',$orderid)->find();
					if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
						\app\common\Order::wxShipping($order['aid'],$order,$ordertype,['express_com'=>$express_com,'express_no'=>$express_no]);
					}
				}
				
			}
			\app\common\System::plog('生成运单'.$miandanid);
			return json(['status'=>1,'msg'=>'发货成功']);
		}

		$info = [];

        //获取支持的快递公司列表,该接口所属的权限集id为：45、71
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token='.$access_token;
		$res = request_get($url);
		$res = json_decode($res,true);
		if($res['errcode'] && $res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
		}
		$deliveryList = $res['data'];
		$deliveryArr = [];
		foreach($deliveryList as $k=>$v){
			$deliveryArr[$v['delivery_id']] = $v['delivery_name'];
		}

		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/getall?access_token='.$access_token;
		$res = request_get($url);
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
		}
		$deliverylist = [];
		foreach($res['list'] as $k=>$v){
			if($v['status_code'] == 0){
				$v['delivery_name'] = $deliveryArr[$v['delivery_id']];
				$deliverylist[] = ['biz_id'=>$v['biz_id'],'delivery_id'=>$v['delivery_id'],'delivery_name'=>$v['delivery_name'],'service_type'=>$v['service_type']];
			}
		}
		$deliverylist[] = ['biz_id'=>'SF_CASH','delivery_id'=>'SF','delivery_name'=>'顺丰现付','service_type'=>[['service_type'=>'0','service_name'=>'标准快递'],['service_type'=>'1','service_name'=>'顺丰即日'],['service_type'=>'2','service_name'=>'顺丰次晨'],['service_type'=>'3','service_name'=>'顺丰标快'],['service_type'=>'4','service_name'=>'顺丰标快（陆运）'],]];
		$deliverylist[] = ['biz_id'=>'DB_CASH','delivery_id'=>'DB','delivery_name'=>'德邦现付','service_type'=>[['service_type'=>'1','service_name'=>'大件快递3.60'],['service_type'=>'2','service_name'=>'特准快件']]];

		$lastorder = Db::name('miandan_order')->where('aid',aid)->order('id desc')->find();
		//if(!$lastorder) $lastorder = [];
		if(!$lastorder){
			$admin_set = Db::name('admin_set')->where('aid',aid)->find();
			$lastorder['sender_name'] = $admin_set['name'];
			$lastorder['sender_mobile'] = $admin_set['tel'];
			$lastorder['sender_tel'] = $admin_set['tel'];
			$lastorder['sender_address'] = $admin_set['address'];
		}
		
		View::assign('info',$info);
		View::assign('lastorder',$lastorder);
		View::assign('deliverylist',$deliverylist);
		return View::fetch();
		}
	}
	//取消运单 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/express/express-by-business/cancelOrder.html
	public function quxiao(){
		$order_id = input('post.order_id');
		$delivery_id = input('post.delivery_id');
		$waybill_id = input('post.waybill_id');
		
		$postdata = [];
		$postdata['order_id'] = $order_id;
		$postdata['delivery_id'] = $delivery_id;
		$postdata['waybill_id'] = $waybill_id;
		$access_token = Wechat::access_token(aid,'wx');
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/cancel?access_token='.$access_token;
		$res = request_post($url,jsonEncode($postdata));
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res) . "<br>" .$res['delivery_resultmsg']]);
		}
		Db::name('miandan_order')->where('aid',aid)->where('order_id',$order_id)->where('delivery_id',$delivery_id)->where('waybill_id',$waybill_id)
            ->update(['status'=>4]);
		
		\app\common\System::plog('取消运单'.$order_id);
		return json(['status'=>1,'msg'=>'取消成功']);
	}

	//查询运单 物流助手 /小程序使用 /获取运单数据 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/express/express-by-business/getOrder.html
	public function getorder(){
		$order_id = input('post.order_id');
		$delivery_id = input('post.delivery_id');
		$waybill_id = input('post.waybill_id');
		$postdata = [];
		$postdata['order_id'] = $order_id;
		$postdata['delivery_id'] = $delivery_id;
        $postdata['print_type'] = 0;//该参数仅在getOrder接口生效，batchGetOrder接口不生效。获取打印面单类型【1：一联单，0：二联单】，默认获取二联单，（顺丰不支持一联单，自动切换为二联单）
        //2.6.7新增 物流助手独立设置
        $miandanset = Db::name('miandan_set')->where('aid',aid)->where('bid',bid)->find();
        if($miandanset) {
            if($delivery_id != 'SF')//顺丰不支持一联单，自动切换为二联单
                $postdata['print_type'] = $miandanset['print_type'];
        }

		$access_token = Wechat::access_token(aid,'wx');
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/get?access_token='.$access_token;
		$res = request_post($url,jsonEncode($postdata));
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res) . "<br>" .$res['delivery_resultmsg']]);
		}
		if($res['order_status']==1 && $waybill_id){
			Db::name('miandan_order')->where('aid',aid)->where('order_id',$order_id)->where('delivery_id',$delivery_id)->where('waybill_id',$waybill_id)->update(['status'=>4]);
		}
		$res['print_html'] = base64_decode($res['print_html']);
        if(getcustom('miandan_print_allproduct') && $miandanset['print_allproduct']){
            $miandan = Db::name('miandan_order')->where('aid',aid)->where('order_id',$order_id)->where('delivery_id',$delivery_id)->where('waybill_id',$waybill_id)->find();

            $goodsname = '';
            $miandan['shop_detail_list'] = json_decode($miandan['shop_detail_list'],true);
            foreach ($miandan['shop_detail_list'] as $value){
                if($goodsname) $goodsname .= '，';
                $goodsname .= $value['goods_name'].'*'.$value['goods_desc'];
            }
            //修改二联单html
            if($postdata['print_type'] == 0){
                $pattern = '/\.goodsname\s*\{([^\}]*)\}/';
                $replacement = '.goodsname { display: block; width: 655px;}';
                $res['print_html'] = preg_replace($pattern, $replacement, $res['print_html']);

                // 正则表达式匹配
                $pattern = '/(<font\s+[^>]*class=["\']goodsname["\'][^>]*>)(.*?)(<\/font>)/si';

                // 替换内容
                $replacement = '<font class="goodsname">'.$goodsname.'</font>';

                $res['print_html'] = preg_replace($pattern, $replacement, $res['print_html']);
            }
            elseif($postdata['print_type'] == 1){
                //修改一联单html 客户为圆通，其他快递可能不同

                // 构建精确正则表达式（含全部 style 属性且保留标签结构）
                $pattern = '/<div\s+class="item text"\s+style="'
                    . 'left:\s*8\.9972px;\s*'          // 强制匹配 left 值:ml-citation{ref="1" data="citationList"}
                    . 'top:\s*780px;\s*'               // 属性顺序与原始结构一致:ml-citation{ref="3,4" data="citationList"}
                    . 'width:\s*436\.98px;\s*'
                    . 'height:\s*210px;\s*'
                    . 'font-family:\s*\'微软雅黑\';\s*' // 中文引号转义处理:ml-citation{ref="3,4" data="citationList"}
                    . 'font-size:\s*21\.80px;\s*'
                    . 'font-weight:\s*400;\s*'
                    . 'overflow:\s*hidden;?'           // 允许末尾分号可选:ml-citation{ref="3" data="citationList"}
                    . '"\s*>'                          // 结束 style 属性
                    . '(\s*)<\/div>/s';                // 捕获原始空格并闭合标签:ml-citation{ref="3" data="citationList"}

                // 执行替换（保留原标签结构）
                $res['print_html'] = preg_replace(
                    $pattern,'<div class="item text" style="left: 8.9972px;top:780px;width: 436.98px;height:210px;font-family:\'微软雅黑\';font-size:21.80px;font-weight:400;overflow:hidden;">'.htmlspecialchars($goodsname).'</div>',
                    $res['print_html']
                );
            }
        }
		return json(['status'=>1,'msg'=>'查询成功','data'=>$res]);
	}

	//查询运单   物流助手 /小程序使用 /获取运单数据 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/express/express-by-business/getOrder.html
	public function getorderlist(){
		$orderids = input('param.ids');
		$rdata = [];
		foreach($orderids as $k=>$orderid){
			$order = Db::name('miandan_order')->where('aid',aid)->where('id',$orderid)->find();		
			$order_id = $order['order_id'];
			$delivery_id = $order['delivery_id'];
			$waybill_id = $order['waybill_id'];
			$postdata = [];
			$postdata['order_id'] = $order_id;
			$postdata['delivery_id'] = $delivery_id;
            $postdata['print_type'] = 0;//该参数仅在getOrder接口生效，batchGetOrder接口不生效。获取打印面单类型【1：一联单，0：二联单】，默认获取二联单，（顺丰不支持一联单，自动切换为二联单）
            //2.6.7新增 物流助手独立设置
            $miandanset = Db::name('miandan_set')->where('aid',aid)->where('bid',bid)->find();
            if($miandanset) {
                if($delivery_id != 'SF')//顺丰不支持一联单，自动切换为二联单
                    $postdata['print_type'] = $miandanset['print_type'];
            }
			$access_token = Wechat::access_token(aid,'wx');
			$url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/get?access_token='.$access_token;
			$res = request_post($url,jsonEncode($postdata));
			$res = json_decode($res,true);
			if($res['errcode']!=0){
				return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res) . "<br>" .$res['delivery_resultmsg']]);
			}
			if($res['order_status']==1 && $waybill_id){
				Db::name('miandan_order')->where('aid',aid)->where('order_id',$order_id)->where('delivery_id',$delivery_id)->where('waybill_id',$waybill_id)->update(['status'=>4]);
			}
			$rdata['print_html'] .= base64_decode($res['print_html']);
		}
		return json(['status'=>1,'msg'=>'查询成功','data'=>$rdata]);
	}
	//查询运单轨迹
	public function getpath(){
		$order_id = input('post.order_id');
		$delivery_id = input('post.delivery_id');
		$waybill_id = input('post.waybill_id');
		$postdata = [];
		$postdata['order_id'] = $order_id;
		$postdata['delivery_id'] = $delivery_id;
		$postdata['waybill_id'] = $waybill_id;
        //微信物流查询抽离
        $res = \app\common\Wechat::getwuliu($delivery_id, $waybill_id, aid);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res) . "<br>" .$res['delivery_resultmsg']]);
		}

		return json(['status'=>1,'msg'=>'查询成功','data'=>$res]);
	}
	//物流账号列表
	public function myaccount(){
		if(request()->isAjax()){
			$access_token = Wechat::access_token(aid,'wx');
			$url = 'https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token='.$access_token;
			$res = request_get($url);
			$res = json_decode($res,true);
			if($res['errcode'] && $res['errcode']!=0){
				return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
			}
			$deliveryList = $res['data'];
			$deliveryArr = [];
			foreach($deliveryList as $k=>$v){
				$deliveryArr[$v['delivery_id']] = $v['delivery_name'];
			}

			$url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/getall?access_token='.$access_token;
			$res = request_get($url);
			$res = json_decode($res,true);
			if($res['errcode']!=0){
				return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
			}
			$list = $res['list'];
			foreach($list as $k=>$v){
				$list[$k]['delivery_name'] = $deliveryArr[$v['delivery_id']];
			}

			return json(['code'=>0,'msg'=>'查询成功','count'=>count($list),'data'=>$list]);
		}
		return View::fetch();
	}
	//绑定物流账号
	public function bindaccount(){
		$access_token = Wechat::access_token(aid,'wx');
		if(request()->isPost()){
			$info = input('post.info/a');
			$postdata = [];
			$postdata['type'] = 'bind';
			$postdata['biz_id'] = $info['biz_id'];
			$postdata['delivery_id'] = $info['delivery_id'];
			if($info['password']){
				$postdata['password'] = $info['password'];
			}
			if($info['remark_content']){
				$postdata['remark_content'] = $info['remark_content'];
			}
			$url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/bind?access_token='.$access_token;
			$res = request_post($url,jsonEncode($postdata));
			$res = json_decode($res,true);
			if($res['errcode']!=0){
				return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
			}
			\app\common\System::plog('绑定物流账号');
			return json(['status'=>1,'msg'=>'绑定成功']);
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token='.$access_token;
		$res = request_get($url);
		$res = json_decode($res,true);
		if($res['errcode'] && $res['errcode']!=0){
			showmsg(\app\common\Wechat::geterror($res));
		}
		$deliveryList = $res['data'];
		View::assign('deliveryList',$deliveryList);
		return View::fetch();
	}
	//解绑物流账号
	public function unbindaccount(){
		$access_token = Wechat::access_token(aid,'wx');
		$info = input('post.');
		$postdata = [];
		$postdata['type'] = 'unbind';
		$postdata['biz_id'] = $info['biz_id'];
		$postdata['delivery_id'] = $info['delivery_id'];
		if($info['password']){
			$postdata['password'] = $info['password'];
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/bind?access_token='.$access_token;
		$res = request_post($url,jsonEncode($postdata));
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>Wechat::geterror($res)]);
		}
		\app\common\System::plog('解绑物流账号');
		return json(['status'=>1,'msg'=>'解绑成功']);
	}

	//模拟更新订单轨迹
	public function testupdateorder(){
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/test_update_order?access_token='.Wechat::access_token(aid,'wx');
		$postdata = [];
		$postdata['biz_id'] = 'test_biz_id';
		$postdata['order_id'] = '201115233858416311';
		$postdata['delivery_id'] = 'TEST';
		$postdata['waybill_id'] = '201115233858416311_1606568923_waybill_id';
		$postdata['action_time'] = time();
		$postdata['action_type'] = '300003';
		$postdata['action_msg'] = 'asssssssss ';
		$res = request_post($url,jsonEncode($postdata));
		$res = json_decode($res,true);
		dump($res);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('miandan_order')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除运单'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//功能开关 2.6.7废弃
    public function setstatus(){
        $st = input('post.st/d');
        Db::name('admin_set')->where('aid',aid)->update(['miandanst'=>$st]);
        return json(['status'=>1,'msg'=>'设置成功']);
    }
    //2.6.7废弃
    public function setwx(){
        $st = input('post.st/d');
        Db::name('admin_set')->where('aid',aid)->update(['miandan_wx'=>$st]);
        return json(['status'=>1,'msg'=>'设置成功']);
    }

    //设置 2.6.7新增
    public function set(){
        $info = Db::name('miandan_set')->where('aid',aid)->where('bid',bid)->find();
        if(request()->isPost()){
            $info = input('post.info/a');

            Db::name('miandan_set')->where('aid',aid)->where('bid',bid)->update($info);

            \app\common\System::plog('设置物流助手');
            return json(['status'=>1,'msg'=>'操作成功']);
        }
        if(empty($info)){
            $adminset = Db::name('admin_set')->where('aid',aid)->find();
            $info = [
                'status'=>$adminset['miandanst'],
                'query_status'=>$adminset['miandan_wx'],
                'print_type'=>0,
                'aid'=>aid,
                'bid'=>bid,
            ];
            Db::name('miandan_set')->insert($info);
        }

        View::assign('info',$info);
        return View::fetch();
    }

	//打印员列表
	public function printerlist(){
		if(request()->isAjax()){
			$access_token = Wechat::access_token(aid,'wx');
			$url = 'https://api.weixin.qq.com/cgi-bin/express/business/printer/getall?access_token='.$access_token;
			$res = request_get($url);
			$res = json_decode($res,true);
			if($res['errcode'] && $res['errcode']!=0){
				return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
			}
			$list = [];
			foreach($res['openid'] as $k=>$v){
				$list[$k]['openid'] = $v;
				$member = Db::name('member')->where('aid',aid)->where('wxopenid',$v)->find();
				if($member){
					$list[$k]['nickname'] = $member['nickname'];
					$list[$k]['headimg'] = $member['headimg'];
				}else{
					$list[$k]['nickname'] = '';
					$list[$k]['headimg'] = '';
				}
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>count($list),'data'=>$list]);
		}
		return View::fetch();
	}
	//添加打印员
	public function addprinter(){
		$access_token = Wechat::access_token(aid,'wx');
		if(request()->isPost()){
			$openid = input('param.openid');
			if(is_numeric($openid)){
				$openid = Db::name('member')->where('aid',aid)->where('id',$openid)->value('wxopenid');
			}
			if(!$openid) return json(['status'=>0,'msg'=>'请填写会员ID或openid']);
			$postdata = [];
			$postdata['update_type'] = 'bind';
			$postdata['openid'] = $openid;
			$url = 'https://api.weixin.qq.com/cgi-bin/express/business/printer/update?access_token='.$access_token;
			$res = request_post($url,jsonEncode($postdata));
			$res = json_decode($res,true);
			if($res['errcode']!=0){
				return json(['status'=>0,'msg'=>\app\common\Wechat::geterror($res)]);
			}
			\app\common\System::plog('添加物流助手打印员');
			return json(['status'=>1,'msg'=>'添加成功']);
		}
		return View::fetch();
	}
	//解除打印员
	public function delprinter(){
		$access_token = Wechat::access_token(aid,'wx');
		$info = input('post.');
		$postdata = [];
		$postdata['update_type'] = 'unbind';
		$postdata['openid'] = input('param.openid');
		$url = 'https://api.weixin.qq.com/cgi-bin/express/business/printer/update?access_token='.$access_token;
		$res = request_post($url,jsonEncode($postdata));
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			return json(['status'=>0,'msg'=>Wechat::geterror($res)]);
		}
		\app\common\System::plog('删除物流助手打印员');
		return json(['status'=>1,'msg'=>'解绑成功']);
	}

}
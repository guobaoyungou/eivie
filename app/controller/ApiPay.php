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
use think\facade\Db;
use think\facade\Log;

class ApiPay extends ApiCommon
{
    public $authlogin = 0;//默认0登录页面  1直接全信息授权，2静默授权仅获取openid
    public $moeny_weishu = 2;//余额位数
	public function initialize(){
        parent::initialize();
        
        if (((platform=='mp' || platform=='wx') && in_array(request()->action(),['daifu']))||(input('param.is_maidan') && $this->sysset['maidan_login'] != 1)){
            $this->authlogin = 2;//静默授权
        }
		$this->checklogin($this->authlogin);
        $shouquan = 1;
        if(getcustom('maidan_auto_reg') ){
            $orderid = input('param.orderid/d');
            $payorder = Db::name('payorder')->where('id',$orderid)->where('aid',aid)->find();
            $set = Db::name('admin_set')->where('aid',aid)->find();
            if($payorder['type']=='maidan' && !$set['maidan_auto_reg'] && cache($this->sessionid.'_openid')){
                $shouquan = 0;
            }
        }
		if(platform=='mp' && !$this->member['mpopenid'] && !in_array(request()->action(),['webviewjump','paypalRedirect']) && $shouquan==1){
			$appinfo = \app\common\System::appinfo(aid,'mp');
			if($appinfo['wxpay'] == 1){
				//授权登录
				if(input('param.state') && input('param.state') == 'getMpOpenid' && input('param.code')){
					$code = input('param.code');
                    $rs = \app\common\Wechat::getAccessTokenByCode(aid,$code);
                    //is_snapshotuser	是否为快照页模式虚拟账号，只有当用户是快照页模式虚拟账号时返回，值为1
                    if($rs['is_snapshotuser'] == 1){
                        return $this->json(['status'=>0,'msg'=>'授权登录失败，请点击下方“使用完整服务”']);
                    }
					if($rs['openid']){
						Db::name('member')->where('id',mid)->update(['mpopenid'=>$rs['openid']]);
					}
					header('location:'.input('param.thisurl'));
				}else{
					//获取用户openid
					$request_url = ltrim($_SERVER["REQUEST_URI"],'/');
					if(strpos($request_url,'?code=')!==false){
						$request_url = explode('?code=',$request_url)[0];
					}elseif(strpos($request_url,'&code=')!==false){
						$request_url = explode('&code=',$request_url)[0];
					}
					$redirectUrl = request()->domain().'/'.$request_url.'&thisurl='.urlencode(input('param.thisurl'));
					$redirectUrl = urlencode($redirectUrl);
                    $AuthorizeUrl = \app\common\Wechat::getOauth2AuthorizeUrl(aid,$redirectUrl,'snsapi_base','getMpOpenid');
					die(jsonEncode(['status'=>-2,'msg'=>'获取用户openid','url'=>$AuthorizeUrl]));
				}
			}
		}
		if(getcustom('member_money_weishu')){
            $this->moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
        }
        $this->member['money'] = dd_money_format($this->member['money'],$this->moeny_weishu);
	}

	//订单支付
	public function pay(){
		$orderid = input('param.orderid/d');
		$payorder = Db::name('payorder')->where('id',$orderid)->where('aid',aid)->find();
 		if(!$payorder){
			return $this->json(['status'=>0,'msg'=>'该订单不存在']);
		}
		if($payorder['type']!='business_recharge' && $payorder['type']!='yuyue_addmoney'){
		    $is_create_child_order = true;
		    if(getcustom('member_create_child_order')){
                if($payorder['pmid'] == mid)$is_create_child_order = false;
            }
            if(getcustom('maidan_orderadd_mobile_paytransfer') && $payorder['type'] == 'maidan'){
                $is_create_child_order = false;
            }
			if($is_create_child_order && $payorder && $payorder['mid'] != mid && $payorder['type'] != 'restaurant_shop') {
				return $this->json(['status'=>0,'msg'=>'该订单不存在']);
			}
		}

        //跳转地址
        $detailurl = '';
        $tourl = '/pages/my/usercenter';
        if($payorder['type'] == 'shop' || $payorder['type'] == 'balance'){
            $detailurl = '/pagesExt/order/detail?id='.$payorder['orderid'];
        }
        if(getcustom('h5zb')){
            if($payorder['type'] == 'shop'){
                $order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
                if($order['roomid']>0){
                    $tourl = '/h5zb/client/main?id='.$order['roomid'];
                }
            }
        }
        if($payorder['type'] == 'shop_fenqi'){
            $tourl = '/pagesExt/order/orderlist';
        }
        if($payorder['type'] == 'collage'){
            $detailurl = '/activity/collage/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/collage/orderlist';
        }
        if($payorder['type'] == 'kanjia'){
            $detailurl = '/activity/kanjia/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/kanjia/orderlist';
        }
        if($payorder['type'] == 'seckill'){
            $detailurl = '/activity/seckill/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/seckill/orderlist';
        }
        if($payorder['type'] == 'scoreshop'){
            $detailurl = '/activity/scoreshop/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/scoreshop/orderlist';
        }
        if($payorder['type'] == 'designerpage'){
            $order = Db::name('designerpage_order')->where('id',$payorder['orderid'])->find();
            $tourl = '/pages/index/main?id='.$order['pageid'];
        }
        if($payorder['type'] == 'restaurant_shop'){
            $detailurl = '/restaurant/shop/orderdetail?id='.$payorder['orderid'];
            $tourl = '/restaurant/shop/orderlist';
        }
        if($payorder['type'] == 'restaurant_takeaway'){
            $detailurl = '/restaurant/takeaway/orderdetail?id='.$payorder['orderid'];
            $tourl = '/restaurant/takeaway/orderlist';
        }
        if($payorder['type'] == 'restaurant_booking'){
            $detailurl = '/restaurant/booking/detail?id='.$payorder['orderid'];
            $tourl = '/restaurant/booking/orderlist';
        }
        if($payorder['type'] == 'seckill2'){
            $detailurl = '/activity/seckill2/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/seckill2/orderlist';
        }
        if($payorder['type'] == 'yuyue'){
            $detailurl = '/yuyue/yuyue/orderdetail?id='.$payorder['orderid'];
            $tourl = '/yuyue/yuyue/orderlist';
        }

        if($payorder['type'] == 'kecheng'){
            $kcorder = Db::name('kecheng_order')->where('id',$payorder['orderid'])->find();
            $detailurl = '/activity/kecheng/product?id='.$kcorder['kcid'];
            $tourl = '/activity/kecheng/product?id='.$kcorder['kcid'];
            if(getcustom('kecheng_lecturer')){
                $kecheng = Db::name('kecheng_list')->where('id',$kcorder['kcid'])->field('chaptertype')->find();
                if($kecheng && $kecheng['chaptertype'] == 1){
                    $tourl = '/pagesB/kecheng/lecturermldetail?kcid='.$kcorder['kcid'];
                }
            }
        }
        if($payorder['type'] == 'tuangou'){
            $detailurl = '/activity/tuangou/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/tuangou/orderlist';
        }
        if($payorder['type'] == 'lucky_collage'){
            $detailurl = '/activity/luckycollage/orderdetail?id='.$payorder['orderid'];
            $tourl = '/activity/luckycollage/orderlist';
        }
        if($payorder['type'] == 'workorder'){
            $detailurl = '/pagesB/workorder/detail?id='.$payorder['orderid'];
            $tourl = '/pagesB/workorder/record';
        }
        if($payorder['type'] == 'business_recharge'){
            $detailurl = '/admin/index/index';
            $tourl = '/admin/index/index';
        }
        if($payorder['type'] == 'maidan'){
            $tourl = '/pagesB/maidan/maidanlog';
            if($payorder['bid'] > 0) {
                $maidan_payafterurl = Db::name('business')->where('aid',aid)->where('id',$payorder['bid'])->value('maidan_payaftertourl');
                if($maidan_payafterurl && !empty($maidan_payafterurl)){
                    $tourl = $maidan_payafterurl;
                }else{
                    $s_maidan_payafterurl = Db::name('admin_set')->where('aid',aid)->value('maidan_payaftertourl');
                    if($s_maidan_payafterurl && !empty($s_maidan_payafterurl)) $tourl =  $s_maidan_payafterurl;
                }
            }else{
                $s_maidan_payafterurl = Db::name('admin_set')->where('aid',aid)->value('maidan_payaftertourl');
                if($s_maidan_payafterurl && !empty($s_maidan_payafterurl)) $tourl =  $s_maidan_payafterurl;
            }
        }
        if($payorder['type'] == 'maidan_new'){
            $tourl = '/pagesC/maidan/maidanlog';
            if($payorder['bid'] > 0) {
                $maidan_payafterurl = Db::name('business')->where('aid',aid)->where('id',$payorder['bid'])->value('maidan_new_payaftertourl');
                if($maidan_payafterurl && !empty($maidan_payafterurl)){
                    $tourl = $maidan_payafterurl;
                }else{
                    $s_maidan_payafterurl = Db::name('admin_set')->where('aid',aid)->value('maidan_new_payaftertourl');
                    if($s_maidan_payafterurl && !empty($s_maidan_payafterurl)) $tourl =  $s_maidan_payafterurl;
                }
            }else{
                $s_maidan_payafterurl = Db::name('admin_set')->where('aid',aid)->value('maidan_new_payaftertourl');
                if($s_maidan_payafterurl &&!empty($s_maidan_payafterurl)) $tourl =  $s_maidan_payafterurl;
            }
        }
        if($payorder['type'] == 'yuyue_workerapply'){
            $yuyueset = Db::name('yuyue_set')->field('apply_url')->where('aid',aid)->find();
            $tourl = $yuyueset['apply_url']?$yuyueset['apply_url']:'/yuyue/yuyue/apply';
        }
        if(getcustom('xixie')){
            if($payorder['type']=='xixie'){
                $detailurl = '/pagesExt/xixie/orderdetail?id='.$payorder['orderid'];
            }
        }
        if($payorder['type'] == 'yueke'){
            $detailurl = '/pagesExt/yueke/orderdetail?id='.$payorder['orderid'];
            $tourl = '/pagesExt/yueke/orderlist';
        }
        if(getcustom('article_reward')){
            if($payorder['type']=='article_reward'){
                $tourl = '/pagesExt/article/detail?id='.$order['artid'];
            }
        }
        if(getcustom('paotui')){
            if($payorder['type']=='paotui'){
                $tourl = '/pagesExt/paotui/orderlist';
            }
        }
        if(getcustom('extend_tour')){
            if($payorder['type']=='tour_activity'){
                $tourl = '/pagesA/tour/orderlist';
            }
        }
        if(getcustom('extend_gift_bag')){
            if($payorder['type']=='gift_bag'){
                $tourl = '/pagesA/giftbag/orderlist';
            }
        }
        if(getcustom('lipinka_morefee') || getcustom('lipinka_freight_free')){
            if($payorder['type']=='lipin'){
                if($order['type'] == 1){
                    $tourl = '/pagesExt/order/orderlist';
                }
                if($order['type'] == 4){
                    $tourl = '/activity/scoreshop/orderlist';
                }
            }
        }
        if(getcustom('lot_cerberuse')){
            if($payorder['type']=='cerberuse'){
                $tourl = '/pagesZ/cerberuse/orderlist';
            }
        }
        if(getcustom('baoming_xcx')){
            if($payorder['type'] == 'baoming_xcx'){
                $tourl = '/pagesA/baomingxcx/index?id='.$order['bmid'];
            }
        }
        if(getcustom('extend_chongzhi')){
            if($payorder['type'] == 'livepay'){
                $tourl = '/pagesA/livepay/record_recharge?type=all';
            }
        }
        if(getcustom('huodong_baoming')){
            if($payorder['type'] == 'huodong_baoming'){
                $tourl = '/pagesB/huodongbaoming/orderlist';
            }
        }
        if(getcustom('taocan_product')) {
            if ($payorder['type'] == 'taocan') {
                $detailurl = '/pagesA/taocan/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/pagesA/taocan/orderlist';
            }
        }
        if(getcustom('sign_pay_bonus')){
            if($payorder['type']=='sign'){
                $tourl = '/pagesExt/sign/index';
            }
        }
        if(getcustom('extend_qrcode_variable_fenzhang')){
            if($payorder['type'] == 'restaurant_shop'){
                $fzcode = Db::name('restaurant_shop_order')->where('aid',aid)->where('ordernum',$payorder['ordernum'])->value('qrcode_val_code');
                if($fzcode){
                    $tourl = Db::name('qrcode_list_variable')->where('aid',aid)->where('code',$fzcode)->value('path');
                    $tourl = $tourl?'/'.$tourl:'';
                }
            }
        }
		if(getcustom('hotel')){
			if($payorder['type'] == 'hotel'){
				$detailurl = '/hotel/order/orderdetail?id='.$payorder['orderid'];
				$tourl = '/hotel/order/orderlist';
			}
		}
        if(getcustom('product_thali')) {
            if ($payorder['type'] == 'product_thali') {
                $detailurl = '/pagesC/productthali/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/pagesC/productthali/orderlist';
            }
        }
        if(getcustom('water_happy_ti')){
            if($payorder['type']=='water_happyti'){
                $tourl = '/pagesD/water/orderList';
            }
        }
        if(getcustom('business_withdraw_cash_mobile')){
            if($payorder['type']=='business_withdrawfee_cash'){
                $tourl = '/admin/finance/index';
            }
        }
        $appinfo = \app\common\System::appinfo(aid,platform);
        if($payorder['type'] == 'shop' || $payorder['type'] == 'shop_fenqi'){
            if(getcustom('payaftertourl')){
                $product = Db::name('shop_order_goods')->alias('og')->where('og.orderid',$order['id'])->where('product.payaftertourl','<>','')->where('product.payaftertourl','not null')->join('shop_product product','og.proid=product.id')->find();
                $payaftertourl = $product['payaftertourl'];
                $payorder['payafterbtntext'] = $product['payafterbtntext'];
                if($payaftertourl){
                    if(strpos($payaftertourl,'miniProgram::') === 0){
                        if(platform == 'mp'){
                            $afterurl = explode('|',str_replace('miniProgram::','',$payaftertourl));
                            $payorder['payafter_username'] = $afterurl[2];
                            $payorder['payafter_path'] = $afterurl[1].(strpos($afterurl[1],'?')!==false ? '&' : '?') .'appid='.$appinfo['appid'].'&uid='.mid.'&ordernum='.$payorder['ordernum'];
                        }else{
                            $afterurl = explode('|',$payaftertourl);
                            $payaftertourl = $afterurl[0].'|'.$afterurl[1];
                            $payaftertourl = $payaftertourl.(strpos($payaftertourl,'?')!==false ? '&' : '?') .'appid='.$appinfo['appid'].'&uid='.mid.'&ordernum='.$payorder['ordernum'].'|'.$detailurl;
                        }
                    }
                    $tourl = $payaftertourl;
                }
            }
            if(getcustom('member_auto_addlogin')){
                $is_member_auto_addlogin = Db::name('admin_set')->where('aid',aid)->value('is_member_auto_addlogin');
                if($is_member_auto_addlogin == 1){
                    $detailurl = '/pagesExt/order/detail?id='.$payorder['orderid'];
                    $tourl = '/pagesA/shop/addressorder?orderid='.$payorder['orderid'];
                }
            }

        }
        else if($payorder['type'] == 'kanjia'){
            $product = Db::name('kanjia_product')->where('id',$order['proid'])->find();
            $payaftertourl = $product['payaftertourl'];
            $payorder['payafterbtntext'] = $product['payafterbtntext'];
            if($payaftertourl){
                if(strpos($payaftertourl,'miniProgram::') === 0){
                    if(platform == 'mp'){
                        $afterurl = explode('|',str_replace('miniProgram::','',$payaftertourl));
                        $payorder['payafter_username'] = $afterurl[2];
                        $payorder['payafter_path'] = $afterurl[1].(strpos($afterurl[1],'?')!==false ? '&' : '?') .'appid='.$appinfo['appid'].'&uid='.mid.'&ordernum='.$payorder['ordernum'];
                    }else{
                        $afterurl = explode('|',$payaftertourl);
                        $payaftertourl = $afterurl[0].'|'.$afterurl[1];
                        $payaftertourl = $payaftertourl.(strpos($payaftertourl,'?')!==false ? '&' : '?') .'appid='.$appinfo['appid'].'&uid='.mid.'&ordernum='.$payorder['ordernum'].'|'.$detailurl;
                    }
                }
                $tourl = $payaftertourl;
            }
        }
        //百度AI绘画支付
        if(getcustom('image_ai') && $payorder['type']=='imgai'){
            $detailurl = '/pagesExt/imgai/detail?id='.$payorder['orderid'];
            $tourl = '/pagesExt/imgai/detail?id='.$payorder['orderid'];
        }
        //地图标注支付
        if(getcustom('map_mark') && $payorder['type']=='mapmark'){
            $detailurl = '/pagesExt/mapmark/detail?id='.$payorder['orderid'];
            $tourl = '/pagesExt/mapmark/detail?id='.$payorder['orderid'];
        }
        //短视频去水印支付
        if(getcustom('video_spider') && $payorder['type']=='videospider'){
            $detailurl = '/pagesExt/videospider/detail?id='.$payorder['orderid'];
            $tourl = '/pagesExt/videospider/detail?id='.$payorder['orderid'];
        }
        if(getcustom('extend_hanglvfeike')){
            if($payorder['type'] == 'hanglvfeike'){
                $detailurl = '/pagesC/hanglvfeike/orderdetail?id='.$payorder['orderid'];
                $tourl = '/pagesC/hanglvfeike/orderlist';
            }
        }
        if(getcustom('extend_zhiyoubao_theater')){
            if($payorder['type'] == 'zhiyoubao'){
                $detailurl = '/pagesC/zhiyoubao/orderdetail?id='.$payorder['orderid'];
                $tourl = '/pagesC/zhiyoubao/orderlist';
            }
        }
        if(getcustom('gold_bean_shop')){
            if($payorder['type'] == 'gold_bean_shop'){
                $detailurl = '/pagesD/goldbeanshop/orderdetail?id='.$payorder['orderid'];
                $tourl = '/pagesD/goldbeanshop/orderlist';
            }
        }
        $lirun_choujiang = getcustom('yx_daily_lirun_choujiang');
        if(getcustom('money_dec_payafter_page')){
            //开启了支付后台跳转余额页面
            if($this->sysset['money_dec_payafter_page']){
                $tourl = '/pagesD/pay/moneyDecPayafterPage';
                if($lirun_choujiang){
                    $tourl .= '?payid='.$payorder['id'];
                }
            }
        }

        if(getcustom('system_admin_payaftertourl_set')){
            //平台统一设置的支付跳转
            $payaftertourlSet = Db::name('sysset')->where('name','payaftertourl_set')->value('value');
            $payaftertourlSet = $payaftertourlSet && !empty($payaftertourlSet)? json_decode($payaftertourlSet,true):[];
            //所有支付后台跳转、商城商品、营销-支付后赠送、买单支付跳转、
            $payaftertourl_allurl = $payaftertourl_shopproducturl = $payaftertourl_payaftergiveurl = $payaftertourl_maidanurl = '';
            if($payaftertourlSet && $payaftertourlSet['status'] && $payaftertourlSet['status'] ==1 ){
                if($payaftertourlSet['allurl'] && !empty($payaftertourlSet['allurl'])) $payaftertourl_allurl = $payaftertourlSet['allurl'];
                if($payaftertourlSet['shopproducturl'] && !empty($payaftertourlSet['shopproducturl'])) $payaftertourl_shopproducturl = $payaftertourlSet['shopproducturl'];
                if($payaftertourlSet['payaftergiveurl'] && !empty($payaftertourlSet['payaftergiveurl'])) $payaftertourl_payaftergiveurl = $payaftertourlSet['payaftergiveurl'];
                if($payaftertourlSet['maidanurl'] && !empty($payaftertourlSet['maidanurl'])) $payaftertourl_maidanurl = $payaftertourlSet['maidanurl'];
            }
            //平台统一设置的支付跳转 所有支付跳转
            if($payaftertourl_allurl) $tourl = $payaftertourl_allurl;
            //平台统一设置的支付跳转 商城商品
            if($payaftertourl_shopproducturl && ($payorder['type'] == 'shop' || $payorder['type'] == 'shop_hb' || $payorder['type'] == 'shop_fenqi')) $tourl = $payaftertourl_shopproducturl;
            //平台统一设置的支付跳转 买单支付
            if($payaftertourl_maidanurl && ($payorder['type'] == 'maidan' || $payorder['type'] == 'maidan_new')) $tourl = $payaftertourl_maidanurl;
        }

        //有没有支付后赠送活动设置了跳转链接
        $payordertype = $payorder['type'];
        if($payordertype == 'shop_hb') $payordertype = 'shop';
        if($payordertype == 'scoreshop_hb') $payordertype = 'scoreshop';
        if($payordertype == 'shop_fenqi') $payordertype = 'shop';
        if($payordertype == 'restaurant_shop' || $payordertype == 'restaurant_takeaway' || $payordertype == 'restaurant_booking') $payordertype = 'restaurant';
        $pwhere = [];
        $pwhere[] = ['aid','=',aid];
        $pwhere[] = ['bid','=',$payorder['bid']];
        if(getcustom('payaftergive_bind_bids')){
            $pwhere[] = Db::raw("find_in_set({$payorder['bid']},`bind_bids`) OR ISNULL(bind_bids)");
        }
        $payaftergive = Db::name('payaftergive')->where($pwhere)->where('pricestart','<=',$payorder['money'])->where('priceend','>=',$payorder['money'])->where('starttime','<',time())->where('endtime','>',time())->where('tourl','<>','')->whereRaw("find_in_set('".$payordertype."',paygive_scene)")->whereRaw("find_in_set('-1',gettj) or find_in_set('".$this->member['levelid']."',gettj)")->order('sort desc')->find();
        if($payaftergive){
            if($payaftergive['tourl']){
                $tourl = $payaftergive['tourl'];
            }
            if($payaftergive['btntext']){
                $payorder['payafterbtntext'] = $payaftergive['btntext'];
            }
            if(getcustom('system_admin_payaftertourl_set')){
                //平台统一设置的支付跳转 支付后赠送跳转
                if($payaftertourl_payaftergiveurl){
                    $tourl = $payaftertourl_payaftergiveurl;
                }else if($payaftertourl_allurl){
                    $tourl = $payaftertourl_allurl;
                }
            }
        }

        if(getcustom('yx_invite_cashback')) {
            if($payorder['type'] == 'shop' || $payorder['type'] == 'shop_hb'){
                //处理邀请返现文字提示
                $deal_ictips = \app\custom\OrderCustom::deal_ictips(aid,mid,1,$payorder);
                $payorder['ictips']  = $deal_ictips['ictips'];
                $payorder['proid']   = $deal_ictips['proid'];
                $payorder['propic']  = $deal_ictips['propic'];
                $payorder['proname'] = $deal_ictips['proname'];
            }
        }
        if(input('param.tourl')) $tourl = input('param.tourl');
        //跳转地址end

        $score_weishu = 0;
        if(getcustom('score_weishu')){
            $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
        $payorder['score'] = dd_score_format($payorder['score'],$score_weishu);

		if($payorder['status']==1){
            return $this->json(['status'=>0,'msg'=>'该订单已支付','url'=>$tourl]);
		}

        if($payorder['status']==2){
            $payorder = Db::name('payorder')->where('aid',$payorder['aid'])->where('bid',$payorder['bid'])->where('orderid',$payorder['orderid'])->where('type',$payorder['type'])->where('mid',$payorder['mid'])->where('status',0)->find();
            if($payorder)
                return $this->json(['status'=>-4,'msg'=>'该订单信息变动，请支付新订单','url'=>'/pagesExt/pay/pay?id='.$payorder['id']]);
            else
                return $this->json(['status'=>0,'msg'=>'该订单已取消']);
        }
		//$payorder['type']=='shop' || $payorder['type'] == 'collage' || $payorder['type'] == 'cycle' || $payorder['type'] == 'kanjia' || $payorder['type'] == 'seckill' || $payorder['type'] == 'seckill2' || $payorder['type'] == 'scoreshop' || $payorder['type'] == 'restaurant_booking' || $payorder['type'] == 'restaurant_takeaway' || $payorder['type']=='choujiang'
		if(in_array($payorder['type'],[
            'shop','collage','cycle','kanjia','seckill','seckill2','scoreshop','restaurant_booking','restaurant_takeaway','choujiang','yuyue','form'
        ])){
            $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
            if($order['status']==4){
                return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
            }elseif($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
            }
            if(getcustom('yx_collage_jipin2') && $payorder['type'] == 'shop'){
                $time = time();
                $proids = Db::name('shop_order_goods')->where('orderid',$order['id'])->column('proid');
                foreach ($proids as $proid) {
                    $jipin_where = [];
                    $jipin_where[] = ['aid', '=', aid];
                    $jipin_where[] = ['status', '=', 1];
                    $jipin_where[] = ['starttime', '<', $time];
                    $jipin_where[] = ['endtime', '>', $time];
                    $jipin_where[] = Db::raw('find_in_set('.$proid.',productids)');
                    $jipin = Db::name('jipin')->where($jipin_where)->order('id desc')->find();
                    if($jipin){
                        // 级别
                        if(!$jipin['showtj']){
                            return $this->json(['status'=>0,'msg'=>'参与等级限制，暂不允许下单']);
                        }
                        $jipinshowtj = explode(',', $jipin['showtj']);
                        if(!in_array('-1', $jipinshowtj) && !in_array($this->member['levelid'], $jipinshowtj)){
                            return $this->json(['status'=>0,'msg'=>'当前等级，暂不允许下单']);
                        }
                        //拼团中
                        $log = Db::name('jipin_log')->where('aid',aid)->where('jipinid',$jipin['id'])->where('status','0')->where('find_in_set('.mid.',mids)')->find();
                        if($log){
                            return $this->json(['status'=>0,'msg'=>'已在拼团中，暂不允许下单']);
                        }
                        // 成团时间确认收货后的 再判断未收货订单是否存在
                        if($jipin['tuanstatus'] == 1){
                            $jipinoginfo = Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('status','in',[1,2])->find();
                            if($jipinoginfo){
                                return $this->json(['status'=>0,'msg'=>'已在拼团中，暂不允许重复下单']);
                            }
                        }
                    }
                }
            }
            if(getcustom('yx_butie_activity',aid)){
                if($payorder['type'] == 'shop'){
                    //消费补贴检测是否有未完成的活动商品，未完成，不可复购
                    $oglist = Db::name('shop_order_goods')->field('id,name,proid,mid')->where('orderid',$order['id'])->select()->toArray();
                    $check = \app\custom\ButieActivity::checkButieProduct(aid,$this->member,$oglist);
                    if($check['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>$check['msg']]);
                    }
                }
            }
            if($payorder['type'] == 'yuyue'){
				$product = Db::name('yuyue_product')->where('id',$order['proid'])->find();
                //是否是多时间段选择模式
                $selmoretime = false;
                if(getcustom('yuyue_datetype1_model_selnum')){
                    //判断是否是:时间段、模式2多段模式、且时间段起订量大于等于1，需要多选
                    if(($product['rqtype']!=4) && $product['datetype'] == 1 && $product['datetype1_model'] == 1 && $product['datetype1_modelselnum'] >=1){
                        $selmoretime = true;
                    }
                }

                $worker_sametime_yynum = 1;//服务人员同一时间接单次数 0为不限制
                if(getcustom('yuyue_worker_sametime_yynum')){
                    //服务人员同一时间接单次数 0为不限制
                    $worker_sametime_yynum = 0+Db::name('yuyue_set')->where('aid',aid)->where('bid',$product['bid'])->value('worker_sametime_yynum');
                }
                if(!$selmoretime){
                    $yydate = $order['yy_time'];
                    if(getcustom('yuyue_selecttime_with_stock')){
                        if($product['showdatetype']==1){
                            $yydate = explode('~',$order['yy_time']);
                            $yydate = $yydate[0];
                        }
                    }
                    //开始时间
                    $begindate = $yydate;
                    if(strpos($begindate,'年') === false){
                        $begindate = date('Y').'年'.$begindate;
                    }
                    $begindate = preg_replace(['/年|月/','/日/'],['-',''],$begindate);
                    $date = date('Y-m-d H:i:s',strtotime(date('H:i',time())));
                    $begintime = strtotime($begindate);
                    if($begintime <= strtotime(date('H:i',time()))+$product['pdprehour']*60*60){
                        return $this->json(['status'=>0,'msg'=>'预约时间已过，请选择其他时间']);
                    }
                    //查看是否已经存在
                    $yycount= Db::name($payorder['type'].'_order')->where('aid',aid)->where('yy_time',$order['yy_time'])->where('proid',$order['proid'])->where('mid','<>',$order['mid'])->where('status','in','1,2')->count();
                    if($yycount>=$product['yynum']){
                        return $this->json(['status'=>0,'msg'=>'该段时间预约人数已满']);
                    }

                    //查看该服务人员该时间是否已经预约出去
                    if($worker_sametime_yynum && $order['worker_id']){
                        $count = Db::name('yuyue_order')->where('worker_id',$order['worker_id'])->where('aid',aid)->where('status','in','1,2')->where('yy_time',$order['yy_time'])->count('id');
                        if($count && $worker_sametime_yynum<= $count){
                            return $this->json(['status'=>0,'msg'=>$yydate.'该段时间不可预约']);
                        }
                    }
                }else{
                    if(getcustom('yuyue_selectpeople_inproduct')){
                        $yydates     = $order['yydates'] && !empty($order['yydates'])?json_decode($order['yydates'],true):[];
                        $yydates_num = count($yydates);
                        if($yydates_num<$product['datetype1_modelselnum']){
                            return $this->json(['status'=>0,'msg'=>'服务时间最少选择'.$product['datetype1_modelselnum'].'个连续时间段']);
                        }
                        $porders = Db::name('yuyue_order')->where('proid',$order['proid'])->where('status','in','1,2')->where('aid',aid)->field('yy_time,yy_times,yydates')->select()->toArray();
                        if($porders){
                            foreach($yydates as &$yv){
                                $yv['num'] = 0;
                                $yy_time = $yv['year'].$yv['date'].' '.$yv['time'];
                                if(strpos($yy_time,'年') === false){
                                    $yy_time = date('Y').'年'.$yy_time;
                                }
                                $yy_time = preg_replace(['/年|月/','/日/'],['-',''],$yy_time);
                                $yv['datetime'] = strtotime($yy_time);
                                foreach($porders as $ov){
                                    if($ov['yy_times']){
                                        $yy_times = json_decode($ov['yy_times']);
                                        if(in_array($yy_time,$yy_times)){
                                            $yv['num'] += 1;
                                        }
                                    }else if($yy_time == $ov['yy_time']){
                                        $yv['num'] += 1;
                                    }
                                }
                                unset($ov);
                            }
                            unset($yv);
                            foreach($yydates as $yv){
                                $yy_time = $yv['year'].$yv['date'].' '.$yv['time'];
                                if($yv['num']>=$product['yynum']){
                                    return $this->json(['status'=>0,'msg'=>$yy_time.'该段时间预约人数已满']);
                                }
                            }
                        }
                        //查看该服务人员该时间是否已经预约出去
                        if($worker_sametime_yynum && $order['worker_id']){
                            //查看该服务人员该时间是否已经预约出去
                            $worders = Db::name('yuyue_order')->where('worker_id',$order['worker_id'])->where('aid',aid)->where('status','in','1,2')->field('yy_time,yy_times,yydates')->select()->toArray();
                            if($worders){
                                foreach($yydates as $yv){
                                    $yy_time = $yv['year'].$yv['date'].' '.$yv['time'];
                                    //统计同一时间段预约的次数
                                    $sametimes = [];
                                    foreach($worders as $ov){
                                        if($ov['yy_times']){
                                            $yy_times = json_decode($ov['yy_times']);
                                            if(in_array($yy_time,$yy_times)){
                                                if($sametimes[$yy_time]){
                                                    $sametimes[$yy_time] += 1;
                                                }else{
                                                    $sametimes[$yy_time] = 1;
                                                }
                                                if($worker_sametime_yynum<=$sametimes[$yy_time]){
                                                    return $this->json(['status'=>0,'msg'=>$yy_time.'该段时间不可预约']);
                                                }
                                            }
                                        }else if($yy_time == $ov['yy_time']){
                                            if($sametimes[$yy_time]){
                                                $sametimes[$yy_time] += 1;
                                            }else{
                                                $sametimes[$yy_time] = 1;
                                            }
                                            if($worker_sametime_yynum<=$sametimes[$yy_time]){
                                                return $this->json(['status'=>0,'msg'=>$yy_time.'该段时间不可预约']);
                                            }
                                        }
                                    }
                                    unset($ov);
                                }
                            }
                            unset($yv);
                        }
                    }
                }
			}
            if($order['discount_rand_money'] > 0){
                $payorder['discountText'] = '随机立减'.$order['discount_rand_money'];
            }
            if(getcustom('douyin_groupbuy')){
            	//抖音团购券再次验证
                if($payorder['type'] == 'shop' && $order['isdygroupbuy']==1){
                    $checkpay = \app\custom\DouyinGroupbuyCustom::checkpay(aid,$order,$this->member);
                    if(!$checkpay || $checkpay['status'] == 0){
                        $msg = $checkpay && $checkpay['msg']?$checkpay['msg']:'抖音兑换码获取信息失败';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                }
            }
            if(getcustom('shop_code_exchangepage')){ 
                //卡密商品，查看是否设置了跳转
                if($payorder['type'] == 'shop' && $order['freight_type'] == 4){
                    if($order['bid']>0){
                        $pageset = Db::name('business')->where('id',$order['bid'])->field('exchange_page')->find();
                    }else{
                        $pageset = Db::name('shop_sysset')->where('aid',aid)->field('exchange_page')->find();
                    }
                    if($pageset && $pageset['exchange_page']) $tourl = '/pagesD/shop/codeExchangepage?orderid='.$order['id'];
                }
            }
        }

        if(getcustom('lipinka_morefee') || getcustom('lipinka_freight_free')){
            if($payorder['type'] == 'lipin'){
               $order = Db::name('lipin_order')->where('id',$payorder['orderid'])->where('aid',aid)->field('id,bid,type,ordernum,status')->find();
               if(!$order || $order['status']!=0) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
               if(getcustom('shop_code_exchangepage')){ 
                    //卡密商品，查看是否设置了跳转
                   if($order['type'] == 1){
                        $shoporder = Db::name('shop_order')->where('ordernum','like',$order['ordernum'].'%')->where('freight_type',4)->where('aid',aid)->field('id,aid,bid,freight_type')->find();
                        if($shoporder){
                            if($shoporder['bid']>0){
                                $pageset = Db::name('business')->where('id',$shoporder['bid'])->field('exchange_page')->find();
                            }else{
                                $pageset = Db::name('shop_sysset')->where('aid',aid)->field('exchange_page')->find();
                            }
                            if($pageset && $pageset['exchange_page']) $tourl = '/pagesD/shop/codeExchangepage?orderid='.$shoporder['id'];
                        }
                   }
               }
           }
        }

		if($payorder['type'] == 'shopfront'){
			$order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
            if($order['status']==4){
                return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
            }elseif($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
            }
		}

		if($payorder['type'] == 'restaurant_shop') {
            $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
            if($order['status']==4){
                return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
            }
            if(getcustom('restaurant_shop_pindan')){
                //拼单模式下 谁支付，payorder的mid 更新成谁的
                $table = Db::name('restaurant_table')->where('aid',aid)->where('id', $order['tableid'])->find();
                if($table['pindan_status'] ==1){
                    Db::name('payorder')->where('id',$payorder['id'])->update(['mid' => mid]);
                }
            }
        }
		if($payorder['type'] == 'collage'){ //拼团
			$order = Db::name('collage_order')->where('id',$payorder['orderid'])->find();
			if($order['buytype']!=1){
				$team = Db::name('collage_order_team')->where('aid',aid)->where('id',$order['teamid'])->find();
				if($team['status']==2){
					return $this->json(['status'=>0,'msg'=>'该团已满员']);
				}
				if($team['status']==3){
					return $this->json(['status'=>0,'msg'=>'该团已解散']);
				}
			}
		}

		if($payorder['type'] == 'lucky_collage'){ //幸运拼团
			$order = Db::name('lucky_collage_order')->where('id',$payorder['orderid'])->find();
			Db::startTrans();
			if($order['buytype']!=1){
				$team = Db::name('lucky_collage_order_team')->where('aid',aid)->where('id',$order['teamid'])->lock(true)->find();
				if($team['status']==2){
					Db::rollback();
					return $this->json(['status'=>0,'msg'=>'该团已满员']);
				}
				if($team['status']==3){
					Db::rollback();
					return $this->json(['status'=>0,'msg'=>'该团已解散']);
				}
				$rs = Db::name('lucky_collage_order')->where('aid',aid)->where('teamid',$order['teamid'])->where('mid',mid)->where('status','>',0)->where('id','<>',$order['id'])->where('isjiqiren',0)->find();
				if($rs){
					Db::rollback();
					return $this->json(['status'=>0,'msg'=>'您已经参与该团了']);
				}
			}
			
			if(getcustom('member_tag')){
				$product = Db::name('lucky_collage_product')->where('aid',aid)->where('status',1)->where('ischecked',1)->where('id',$order['proid'])->find();
				if($product['istag']==1){
					$rs = \app\model\LuckyCollage::membertag_collage($order['mid'],$order['teamid'],$product);
					if($rs && $rs['status']==0){
						return $this->json(['status'=>0,'msg'=>'该团您暂时没有参加条件']);
					}
				}
			}
			Db::commit(); 
		}
		if($payorder['type']!='shop_hb' && $payorder['type']!='scoreshop_hb' && $payorder['type']!='balance' && $payorder['type']!='yuyue_balance' && $payorder['type']!='yuyue_addmoney' && $payorder['type']!='shop_fenqi'){
			if($payorder['type'] == 'shopfront'){
				$orderinfo = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
			}else{
				$orderinfo = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
			}
			//判断配送时间选择是否符合要求
			if($orderinfo['freightid'] && $orderinfo['freight_time']){
				$freight = Db::name('freight')->where('id',$orderinfo['freightid'])->find();
				if($freight){
					$freight_times = explode('~',$orderinfo['freight_time']);
					if($freight_times[1]){
						$freighttime = strtotime(explode(' ',$freight_times[0])[0] . ' '.$freight_times[1]);
					}else{
						$freighttime = strtotime($freight_times[0]);
					}
					if(time() + $freight['psprehour']*3600 > $freighttime){
						return $this->json(['status'=>0,'msg'=>($freight['pstype']!=1?'配送':'取货').'时间必须在'.$freight['psprehour'].'小时之后']);
					}
				}
			}
		}

        if($payorder['type'] == 'shop_fenqi'){
			$orderinfo = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
		}

        if($payorder['type'] == 'livepay'){//生活缴费
			$order = Db::name('livepay_order')->where('id',$payorder['orderid'])->find();
            if($order['status']==4){
                return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
            }elseif($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
            }
		}
        if(getcustom('huodong_baoming') && $payorder['type'] == 'huodong_baoming'){//活动报名
			$order = Db::name('huodong_baoming_order')->where('id',$payorder['orderid'])->find();
            if($order['status']==4){
                return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
            }elseif($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
            }
		}

		if($payorder['type'] == 'coupon'){
			$orderinfo = Db::name('coupon_order')->where('id',$payorder['orderid'])->find();
			$coupon = Db::name('coupon')->where('id',$orderinfo['cpid'])->find();
			if($coupon['stock']<=0) return $this->json(['status'=>0,'msg'=>$coupon['name'].'库存不足']);
		}

		$set = Db::name('admin_set')->where('aid',aid)->find();

        if(getcustom('business_score_duli_set')){//如果商户单独设置了赠送积分规则
            $business_duli = Db::name('business')->where('aid',aid)->where('id',$payorder['bid'])->field('scorein_money,scorein_score,scorecz_money,scorecz_score')->find();
            if(!is_null($business_duli['scorein_money']) && !is_null($business_duli['scorein_score'])){
                $set['scorein_money'] = $business_duli['scorein_money'];
                $set['scorein_score'] = $business_duli['scorein_score'];
            }
            if(!is_null($business_duli['scorecz_money']) && !is_null($business_duli['scorecz_score'])){
                $set['scorecz_money'] = $business_duli['scorecz_money'];
                $set['scorecz_score'] = $business_duli['scorecz_score'];
            }
        }
        if(getcustom('pay_yuanbao') && $payorder['type']=='shop'){
            $yuanbao_money_ratio = $set['yuanbao_money_ratio'];
            $yuanbao_money = $order['total_yuanbao']*$yuanbao_money_ratio/100;

            $yuanbao_money = round($yuanbao_money,2);//现金
            $total_yuanbao = $order['total_yuanbao'];//总支付元宝

            $yuanbao_msg = $order['total_yuanbao'].t('元宝').'+'.t('现金').$yuanbao_money.'元';//元宝信息提示
            $yuanbaopay  = $set['yuanbao_pay'];//是否开启元宝支付

            //如果存在非元宝商品，则关闭元宝支付
            if($order['have_no_yuanbao']){
                $yuanbaopay  = 0;
            }
        }else{
            $yuanbao_money = 0;
            $total_yuanbao = 0;
            $yuanbao_msg   = '';
            $yuanbaopay    = 0;
        }
        if(getcustom('xixie')){
            if($payorder['type']=='xixie'){
                $order = Db::name('xixie_order')->where('id',$payorder['orderid'])->find();
                 if($order['status']==4){
                    return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
                }elseif($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
            }
        }
        if(getcustom('article_reward')){
            if($payorder['type']=='article_reward'){
                $order = Db::name('article_reward_order')->where('id',$payorder['orderid'])->find();
                if(!$order){
                    return $this->json(['status'=>0,'msg'=>'订单不存在']);
                }
                if($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
            }
        }
        if(getcustom('lipinka_morefee') || getcustom('lipinka_freight_free')){
        	if($payorder['type']=='lipin'){
                $order = Db::name('lipin_order')->where('id',$payorder['orderid'])->find();
                if(!$order){
                    return $this->json(['status'=>0,'msg'=>'订单不存在']);
                }
                if($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
            }
        }
        if(getcustom('business_deposit')){
            if($payorder['type']=='business_deposit'){
                $order = Db::name('business_deposit_order')->where('id',$payorder['orderid'])->find();
                if(!$order){
                    return $this->json(['status'=>0,'msg'=>'订单不存在']);
                }
                if($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
            }
        }
        $overdraft_moneypay = 0;
        if(getcustom('member_overdraft_money') && $set['overdraft_moneypay'] && !in_array($payorder['type'],['recharge','overdraft_recharge','xianjin_recharge'])){
            //这里增加绑定客户的判断
            $overdraft_moneypay = 1;
            if(getcustom('customer_overdraft_money')){
                //未绑定客户的会员不可用信用额度
                $customerBind = Db::name('sh_customer')->where('aid',aid)->where('mid',$this->mid)->count();
                if(empty($customerBind)){
                    $overdraft_moneypay = 0;
                }
            }
        }
		 if(getcustom('baoming_xcx')){
            if($payorder['type']=='baoming_xcx'){
                $order = Db::name('baoming_xcx_order')->where('id',$payorder['orderid'])->find();
                if(!$order){
                    return $this->json(['status'=>0,'msg'=>'订单不存在']);
                }
                if($order['status']!=1){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
            }
        }

        if(getcustom('ciruikang_fenxiao')){
        	//是否开启了商城商品需上级购买足量
            $deal_ogstock = \app\custom\CiruikangCustom::deal_ogstock(aid,mid,$this->member,$set,$order,$payorder);
            if($deal_ogstock['status'] == 0 ){
                return $this->json($deal_ogstock);
            }
        }

        if(getcustom('water_happy_ti') && $payorder['type'] == 'water_happyti'){//打水订单
            $order = Db::name('water_happyti_order')->where('id',$payorder['orderid'])->find();
            if($order['status']==4){
                return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
            }elseif($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
            }
        }

        $checkscore_params = [];//检查积分方法额外参数
        $decscore_params   = [];//减少积分方法额外参数
        if(getcustom('scoreshop_otheradmin_buy')){
            //积分兑换：是来自本系统其他平台的用户
            if(platform == 'wx' && ($payorder['type']=='scoreshop' || $payorder['type']=='scoreshop_hb') && $order['otheraid'] != aid && $order['othermid']>0){
                $othermember = [];//其他平台用户
                $BuyOverallScoreshop = false;//权限
                $othermember = Db::name('member')->where('id',$order['othermid'])->where('aid',$order['otheraid'])->field('id,aid,nickname,score,money')->find();
                //用户存在，且不是本平台
                if($othermember && $othermember['aid'] != aid){
                    //查询权限组 是否开启兑换总平台积分商品
                    $admin_user = db('admin_user')->where('aid',$othermember['aid'])->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                    if($admin_user['auth_type'] != 1){
                        if($admin_user['groupid']){
                            $admin_user['auth_data'] = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                        }
                        $admin_auth = json_decode($admin_user['auth_data'],true);
                        if($admin_auth && in_array('BuyOverallScoreshop,BuyOverallScoreshop',$admin_auth)){
                            $BuyOverallScoreshop = true;//标记有兑换权限
                        }
                    }else{
                        $BuyOverallScoreshop = true;//标记有兑换权限
                    }
                    if($BuyOverallScoreshop){
                        //查询小程序名称
                        $appinfo = Db::name('admin_setapp_wx')->where('aid',aid)->field('id,nickname')->find();
                        $othermember['appname'] = $appinfo && !empty($appinfo['nickname'])?$appinfo['nickname']:$set['name'];
                        $checkscore_params['othermember'] = $othermember;//检查积分额外参数
                        $decscore_params['othermember']   = $othermember;//减少积分额外参数
                    }
                }
            }
        }

        if(getcustom('hotel')){
            if($payorder['type']=='hotel'){
                $order = Db::name('hotel_order')->where('id',$payorder['orderid'])->find();
                if(!$order){
                    return $this->json(['status'=>0,'msg'=>'订单不存在']);
                }
                if($order['status']==-1){
                    return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
                }
                $hoteltext = \app\model\Hotel::gettext(aid);
                $where = [];
                $where[] = ['id','=',$order['hotelid']];
                $where[] = ['aid','=',aid];
                $hotel = Db::name('hotel')->where($where)->find();
                //同一时间不可下多笔订单
                if($hotel && $hotel['islimit']==1){
                    $starttime = strtotime($order['in_date']);
                    $endtime   = strtotime($order['leave_date']);
                    $where = [];
                    $where[] = ['aid','=',aid];
                    //$where[] = ['hotelid','=',$hotel['id']];
                    $where[] = ['mid','=',mid];
                    $where[] = ['status','in','1,2,3'];
                    //$where[] = Db::raw(" (unix_timestamp(in_date)<$endtime and unix_timestamp(leave_date)>$endtime) or unix_timestamp(leave_date)>$starttime");

                    //统计入驻时间等于开始时间 或 离开时间等于结束时间 或 （入驻时间大于开始时间 且 小于结束时间）或 （离开时间大于开始时间 且 小于结束时间）或 （入驻时间小于开始时间 且 离开大于开始时间）或 （入驻时间小于结束时间 且 离开大于结束时间）
                    $where2 = "unix_timestamp(in_date) = ".$starttime." or unix_timestamp(leave_date) = ".$endtime." or".
                      "( unix_timestamp(in_date) > ".$starttime." && unix_timestamp(in_date) < ".$endtime." ) or".
                      "( unix_timestamp(leave_date) > ".$starttime."&& unix_timestamp(leave_date) < ".$endtime." ) or".
                      "( unix_timestamp(in_date) < ".$starttime." && unix_timestamp(leave_date) > ".$starttime." ) or".
                      "( unix_timestamp(in_date) < ".$endtime." && unix_timestamp(leave_date) > ".$endtime." )";
                    $ordercount = Db::name('hotel_order')->where($where)->where($where2)->count();
                    if($ordercount>0){
                        return $this->json(['status'=>0,'msg'=>'同一时间不可下多笔订单']);
                    }
                }

                $startdate = $order['in_date'];
                $dayCount  = $order['daycount'];
                $roomprice =  Db::name('hotel_room_prices')->where('roomid',$order['roomid'])->where('datetime',$startdate)->find();
                $minstock = $roomprice['stock']?$roomprice['stock']:0;
                $isstatus=1;
                for($i=0;$i<$dayCount;$i++){
                    $datetime = strtotime($startdate)+86400*$i;
                    $date = date('Y-m-d',$datetime);
                    $roomprice =  Db::name('hotel_room_prices')->where('roomid',$order['roomid'])->where('datetime',$date)->find();
                    if(!$roomprice['status']){
                        $isstatus = 0;
                    }
                    if($roomprice['stock']<$minstock){
                        $minstock = $roomprice['stock'];
                    }
                }
                if(!$isstatus){
                    return $this->json(['status'=>0,'msg'=>'该日期不可预订，请选择其他日期或其他房型']);
                }
                if($minstock<=0){
                    return $this->json(['status'=>0,'msg'=>'该日期已订满，请选择其他日期或其他房型']);
                }elseif($minstock<$order['totalnum']){
                    return $this->json(['status'=>0,'msg'=>'该日期最多可订'.$minstock.$hoteltext['间'].'，请选择其他日期或其他房型']);
                }
                if(getcustom('member_upgradescore')){
                    if($order['upgradescore'] && $order['upgradescore']>0 && $order['upgradescore_status'] != 1){
                        //扣除升级积分
                        $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'hotel'];
                        $res = \app\common\Member::addupgradescore($order['aid'],$order['mid'],-$order['upgradescore'],'抵扣酒店订单，订单号: '.$order['ordernum'],$params);
                        if(!$res || $res['status'] != 1){
                            $msg = $res && $res['msg']? $res['msg'] : $hoteltext['升级积分'].'抵扣失败';
                            return $this->json(['status'=>0,'msg'=>$msg]);
                        }
                        Db::name('hotel_order')->where('id',$order['id'])->update(['upgradescore_status'=>1]);
                    }
                }

                if($order['use_money']>0 && $order['use_money_paystaus'] != 1){
                    if($order['use_money'] > $this->member['money']){
                        return $this->json(['status'=>0,'msg'=>t('余额').'不足']);
                    }
                    $res = \app\common\Member::addmoney($order['aid'],$order['mid'],-$order['use_money'],t('余额').'抵扣，订单号: '.$order['ordernum']);
                    if($res['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>t('余额').'抵扣失败']);
                    }
                    Db::name('hotel_order')->where('id',$order['id'])->update(['use_money_paystaus'=>1]);
                }
            }
        }
        if($payorder['type'] == 'maidan'){
            $order = Db::name('maidan_order')->where('id',$payorder['orderid'])->find();
            if($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
            }
            if(getcustom('member_shopscore')){
                if($order['shopscore'] && $order['shopscore']>0 && $order['shopscore_status'] != 1){
                    //扣除产品积分
                    $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'maidan'];
                    $res = \app\common\Member::addshopscore($order['aid'],$order['mid'],-$order['shopscore'],'抵扣买单订单，订单号: '.$order['ordernum'],$params);
                    if(!$res || $res['status'] != 1){
                        $msg = $res && $res['msg']? $res['msg'] : t('产品积分').'抵扣失败';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                    Db::name('maidan_order')->where('id',$order['id'])->update(['shopscore_status'=>1]);
                }
            }
        }
        if(getcustom('member_shopscore')){
            //扩展类型
            $shoptypes = ['shop_hb','shop_fenqi','balance','shopfront'];
            //商城产品积分扣除情况
            if($payorder['type'] == 'shop_hb'){
                $shopscoreOrders = Db::name('shop_order')->where('ordernum','like',$payorder['ordernum'].'%')->where('shopscore','>',0)->where('shopscore_status',0)->field('id,aid,mid,ordernum,shopscore,shopscore_status')->select()->toArray();
                if($shopscoreOrders){
                    foreach($shopscoreOrders as $shopscoreOrder){
                        $res = $this->dealshopscore($shopscoreOrder);
                        if(!$res || $res['status'] != 1){
                            $msg = $res && $res['msg']? $res['msg'] : t('产品积分').'抵扣失败';
                            return $this->json(['status'=>0,'msg'=>$msg]);
                        }
                    }
                }
            }else if($payorder['type'] == 'shop' || ($payorder['type'] != 'shop' && in_array($payorder['type'],$shoptypes))){
                if($order || $orderinfo){
                    $order = $orderinfo?$orderinfo:$order;
                }else{
                    $order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
                }
                $res = $this->dealshopscore($order);
                if(!$res || $res['status'] != 1){
                    $msg = $res && $res['msg']? $res['msg'] : t('产品积分').'抵扣失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
            }
        }
        if(getcustom('extend_hanglvfeike')){
            if($payorder['type'] == 'hanglvfeike'){
                $order = Db::name('hanglvfeike_order')->where('id',$payorder['orderid'])->find();
                if($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
                //查询控制台用户机票余额是否足够
                $airticketmoney = $this->admin['airticketmoney'];
                if($airticketmoney<$order['amount']) return $this->json(['status'=>0,'msg'=>'系统支付功能异常，请联系系统客服人员']);
                //机票接口需要验舱/验价
                if(!$order['ischange']){
                    //验舱/验价
                    // $checkparams = [];
                    // $checkparams=['searchNo'=>$order['searchNo'],'flightNo'=>$order['flightNo'],'cabin'=>$order['cabin'],'cabinNo'=>$order['cabinNo'],'owner'=>'8023'];
                    // $rescheck = \app\custom\Hanglvfeike::bookBeforeCheck(aid,$checkparams);
                    // if($rescheck['status'] == 0){
                    //    return $this->json($rescheck);
                    // }
                    // if(!$rescheck['data'] || empty($rescheck['data'])){
                    //     return $this->json(['status'=>2,'msg'=>'数据已过期']);
                    // }
                    // if($rescheck['data']['code'] != 1 || $rescheck['data']['changeType'] != 1){
                    //     $msg = $rescheck['data']['shortText']?? '数据已过期';
                    //     return $this->json(['status'=>0,'msg'=>$msg]);
                    // }
                }else{
                    if($order['change_status']!= 2){
                        return $this->json(['status'=>0,'msg'=>'审核未通过，暂不能支付']);
                    }
                }
            }
        }
        if(getcustom('extend_zhiyoubao_theater')){
            if($payorder['type'] == 'zhiyoubao'){
                $order = Db::name('zhiyoubao_order')->where('id',$payorder['orderid'])->find();
                if($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
                //检查订单参数是否符合下单标准
                $dealorder = \app\custom\Zhiyoubao::dealorder(aid,$order['performid'],$order['fids']);
                if(!$dealorder || $dealorder['status'] == 0){
                    $msg = $dealorder && $dealorder['msg']?$dealorder['msg']:'提交失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
            }
        }

        if(getcustom('system_moneypayscene')){
            //判断场景能否余额支付//0:不能用余额 1：能用余额（默认）
            $moneypayscene = 1; 
            if($set['moneypay']){
                $moneypayscene = $this->dealmoneypayscene($set['moneypay'],$set['payscene'],$payorder['type']);
            }
        }

        if(getcustom('pay_countdown') || getcustom('sxpay_native')) {
            $countDown = -1;//倒计时，默认-1，不显示倒计时
            $autoclose = $this->getautoclose(aid,$payorder);//自动关闭时间
            if($autoclose>=0){
                $countDown = $payorder['createtime'] + $autoclose*60 - time();
                if($countDown<0) $countDown = 0;
            }
        }
        if(getcustom('deposit')){
            if($payorder['type'] == 'deposit') {
                $tourl = '/pagesD/deposit/index';
            }
        }
        if($payorder['type'] == 'balance'){
            $order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
            if(!$order) return $this->json(['status'=>0,'msg'=>'订单不存在']);
            if($order['balance_price']<=0 || $order['balance_pay_orderid'] != $payorder['id']){
                $canbalance = false;
                if(getcustom('shop_cod_onlinepay')) {
                    //货款在线支付
                    if($order['balance_pay_orderid2'] == $payorder['id']){
                        $cod_onlinepay = Db::name('shop_sysset')->where('aid',aid)->value('cod_onlinepay');
                        if(!$cod_onlinepay || empty($cod_onlinepay)) return $this->json(['status'=>0,'msg'=>'该订单在线支付已关闭']);
                        if($order['balance_pay_status2'] != 0){
                            return $this->json(['status'=>0,'msg'=>'该货款已支付']);
                        }else{
                            $canbalance = true;
                        }
                    } 
                }
                if(!$canbalance){
                    return $this->json(['status'=>0,'msg'=>'操作失败，尾款支付参数发生改变，请返回订单列表，刷新后重试']);
                }
            }
            if(getcustom('shop_cod_onlinepay')) {
                //货款在线支付
                if($order['balance_pay_orderid2'] == $payorder['id'] && $order['balance_pay_status2'] != 0){
                    return $this->json(['status'=>0,'msg'=>'该货款已支付']);
                }
            }
        }

        //发起支付-----------------------------------------------------------------------------------------------------------
		if(input('param.op') == 'submit'){
			$post = input('param.');
            Db::name('payorder')->where('id',$orderid)->update(['platform'=>platform]);
            //元宝支付
            if(getcustom('pay_yuanbao')){
                //支付类型
                $pay_type = $post['pay_type'];

                //如果支付类型是元宝 且是商城支付
                if( $yuanbaopay && $pay_type == 'yuanbao' && $payorder['type']=='shop'){
                    //检查用户元宝是否足够
                    if($this->member['yuanbao']<$total_yuanbao){
                        return $this->json(['status'=>0,'msg'=>t('元宝').'不足']);
                    }
                    //重新赋值
                    $payorder['money'] = $yuanbao_money;
                }
            }
            if(getcustom('product_service_fee')){
                if($this->member['service_fee'] < $payorder['service_fee_money']){
                    return $this->json(['status'=>0,'msg'=>t('服务费').'不足，请充值']);
                }
            }
          	if(getcustom('business_sales_quota')){
				if($order['bid']>0){
					$business2 = Db::name('business')->where(['aid'=>aid,'id'=>$order['bid']])->field('kctime,kctype,sales_quota,total_sales_quota')->find();
					$sales_price = $order['product_price'];
					if($business2['kctype']==1){
						$sales_price = $order['totalprice'];
					}
					$syquota = $business2['sales_quota']-$business2['total_sales_quota'];
					if($business2['sales_quota']>0 && $syquota<$sales_price){
						return $this->json(['status'=>0,'msg'=>'该商户商品额度不足']);
					}
				}
			}

			if($payorder['type']=='yuyue'){
			    $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
				$product = Db::name('yuyue_product')->field('pdprehour,yynum')->where('id',$order['proid'])->find();
				//查看是否已经存在
				$yycount= Db::name($payorder['type'].'_order')->where('aid',aid)->where('yy_time',$order['yy_time'])->where('proid',$order['proid'])->where('mid','<>',$order['mid'])->where('status','in','1,2')->count();
				if($yycount>=$product['yynum']){
					return $this->json(['status'=>0,'msg'=>'该段时间预约人数已满']);
				}
				if($order['scoredkscore'] > 0){
					\app\common\Member::addscore(aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
				}
			}

			if($this->member['paypwd'] && $post['typeid']==1){ //余额支付 验证支付密码
				if(!\app\common\Member::checkPayPwd($this->member,$post['paypwd'] )){
					return $this->json(['status'=>0,'msg'=>'支付密码输入错误']);
				}
			}

			/*if($payorder['type']=='hotel'){ //酒店使用余额抵扣的 //放到回调里面
				$order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
				if($order['use_money']>0){
					$res = \app\common\Member::addmoney(aid,mid,-$order['use_money'],t('余额').'抵扣，订单号: '.$order['ordernum']);
					if($res['status'] != 1){
						return $this->json(['status'=>0,'msg'=>t('余额').'抵扣失败']);
					}
				}
			}*/
            if(getcustom('freeze_money')){
                if($this->member['freezemoney'] < $payorder['total_freezemoney_price']){
                    return $this->json(['status'=>0,'msg'=>t('冻结资金').'不足,请充值']);
                }
            }
            if(!getcustom('freeze_money')){
                $payorder['total_freezemoney_price'] = 0;
            }
            $pay_gold_bean = 0;
            if(getcustom('gold_bean_shop')){
                $pay_gold_bean =  $payorder['gold_bean'];
            }
			if($payorder['money'] <=0 && $payorder['score']<=0 && $pay_gold_bean <=0){
                Db::startTrans();
                try{
                    \app\model\Payorder::payorder($orderid,'无须付款',1,'');
                    if($payorder['type'] != 'shop_hb'){
                        //\app\common\notice\Notice::orderPay(aid,$payorder,mid,$this->member);
                        $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
                        if($payorder['type'] =='maidan'){
                            $order['totalprice'] =  $order['money'];
                        }
                        //$this->sendNotice(aid,$payorder,$order,$this->member);
                    }                    
                    Db::commit();
                    return $this->json(['status'=>2,'msg'=>'付款成功']);
                }catch(Exception $e){
                    Log::write([
                        'file'=>__FILE__,
                        'line'=>__LINE__,
                        'error'=>$e->getMessage()
                    ]);
                    Db::rollback();
                    return $this->json(['status'=>1,'msg'=>'异常错误，请重试']);
                }
			}
			if($payorder['score'] > 0){
				$ckrs = $this->checkscore($payorder,$checkscore_params);
				if($ckrs['status'] == 0) return $this->json($ckrs);
			}
            if(getcustom('gold_bean_shop')){
                if($payorder['gold_bean'] > 0){
                    $gold_bean = $this->member['gold_bean'];
                    if($gold_bean < $payorder['gold_bean']){
                        return $this->json( ['status'=>0,'msg'=>t('金豆').'不足']);
                    }
                }
            }
            if(getcustom('member_level_moneypay_price')){
                if($payorder['usemoneypay'] >=0){
                    if($payordertype == 'shop' || in_array($payorder['type'],['restaurant_shop','restaurant_shop_hb','restaurant_takeaway','restaurant_takeaway_hb'])){
                        //是否余额支付、且会员价仅限余额支付
                        $moneypay_lvprice_status = false;
                        if($set['moneypay'] && $set['moneypay_lvprice_status'] == 1){
                            $moneypay_lvprice_status = true;
                        }
                        if($moneypay_lvprice_status){
                            if($post['typeid']==1){
                                $payorder['money'] = $payorder['moneyprice'];
                            }else{
                                //如果支付价格不等于普通价格，需要重置订单号
                                if($payorder['moneypaytypeid']>0 && $payorder['money'] != $payorder['putongprice']){
                                    //生成新的订单号
                                    $newordernum = date('ymdHis').rand(100000,999999);
                                    \app\model\Payorder::updateorder($payorder['id'],$newordernum,$payorder['putongprice']);

                                    //更改单个订单号
                                    if(!strpos($payorder['ordernum'],'_')){
                                        if($payordertype == 'shop'){
                                            Db::name('shop_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                            Db::name('shop_order_goods')->where('orderid',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                        }else if($payorder['type'] == 'restaurant_shop'){
                                            Db::name('restaurant_shop_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                            Db::name('restaurant_shop_order_goods')->where('orderid',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                        }else if($payorder['type'] == 'restaurant_takeaway'){
                                            Db::name('restaurant_takeaway_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                            Db::name('restaurant_takeaway_order_goods')->where('orderid',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                        }
                                    }

                                    $payorder['ordernum'] = $newordernum;
                                }
                                $payorder['money'] = $payorder['putongprice'];
                            }
                            //更新支付余额和会员价仅限余额支付支付方式
                            Db::name('payorder')->where('id',$payorder['id'])->update(['money'=>$payorder['money'],'moneypaytypeid'=>$post['typeid'],'usemoneypay'=>1]);
                        }else{
                            if($payorder['usemoneypay'] != 0 || $payorder['money'] != $payorder['moneyprice']){
                                $updata = [];
                                //判断是否是正常会员购买价格，不是则重置为正常购买价
                                if($payorder['money'] != $payorder['moneyprice']){
                                    //若使用其他支付过，需要重置订单号
                                    if($post['typeid'] != 1){
                                        if($payorder['moneypaytypeid']>0){
                                            //生成新的订单号
                                            $newordernum = date('ymdHis').rand(100000,999999);
                                            \app\model\Payorder::updateorder($payorder['id'],$newordernum,$payorder['moneyprice']);

                                            //更改单个订单号
                                            if(!strpos($payorder['ordernum'],'_')){
                                                if($payordertype == 'shop'){
                                                    Db::name('shop_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                                    Db::name('shop_order_goods')->where('orderid',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                                }else if($payorder['type'] == 'restaurant_shop'){
                                                    Db::name('restaurant_shop_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                                    Db::name('restaurant_shop_order_goods')->where('orderid',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                                }else if($payorder['type'] == 'restaurant_takeaway'){
                                                    Db::name('restaurant_takeaway_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                                    Db::name('restaurant_takeaway_order_goods')->where('orderid',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
                                                }
                                            }

                                            $payorder['ordernum'] = $newordernum;
                                            $updata['moneypaytypeid'] = 0;
                                        }
                                    }
                                    $updata['money']   = $payorder['moneyprice'];
                                    $payorder['money'] = $payorder['moneyprice'];
                                }
                                $updata['usemoneypay']    = 0;//标记未使用
                                Db::name('payorder')->where('id',$payorder['id'])->update($updata);
                            }
                        }
                    }
                }
            }

            if(getcustom('bonus_pool_gold')) {
                //购买金币前台隐藏了微信支付时提交默认的是微信支付，这里加一层判断
                if ($payorder['type'] == 'buy_gold') {
                    $orderinfo = Db::name('buy_gold_order')->where('id',$payorder['orderid'])->find();
                    if($orderinfo['select_paytype']!='online' && $post['typeid']!=1 && $orderinfo['select_paytype']!='freezemoney'){
                        return $this->json(['status'=>0,'msg'=>'请选择正确的支付方式']);
                    }
                }
            }
            //typeid枚举值汇总 https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwKTcT9kNuQa0MtULMT3
			if($post['typeid']==1){ //余额支付
				if($payorder['type'] == 'recharge') return $this->json(['status'=>0,'msg'=>'不能用'.t('余额').'支付']);
                $canmoneypay = true;
                if(getcustom('money_dec')){
                    //使用余额抵扣，则不显示余额支付
                    if($payorder['type'] == 'shop' && $order['dec_money']>0){
                        $canmoneypay = false;
                    }
                    if($payorder['type'] == 'shop_hb'){
                        $hblist = Db::name('shop_order')->where('aid',aid)->where('ordernum','like',$payorder['ordernum'].'%')->order('ordernum')->select()->toArray();
                        foreach($hblist as $hbv){
                            if($hbv['dec_money']>0){
                                $canmoneypay = false;
                            }
                        }
                    }
                    if(getcustom('maidan_money_dec')){
                        if($payorder['type'] == 'maidan'){
                            $dec_money = Db::name('maidan_order')->where('id',$payorder['orderid'])->value('dec_money');
                            if($dec_money>0) $canmoneypay = false;
                        }
                    }
                }
                if(getcustom('yuyue_money_dec')){
                    //使用余额抵扣，则不显示余额支付
                    if($payorder['type'] == 'yuyue'){
                        $dec_money = Db::name('yuyue_order')->where('id',$payorder['orderid'])->value('dec_money');
                        if($dec_money && $dec_money>0){
                            $canmoneypay = false;
                        }
                    }
                }
                if(getcustom('hotel')){
                    if($payorder['type'] == 'hotel'){
                        if($order['use_money']>0){
                            $canmoneypay = false;
                        }
                    }
                }
                if(!$canmoneypay) return $this->json(['status'=>0,'msg'=>'不能用'.t('余额').'支付']);

                if(getcustom('commission_xianjin_percent')){
                    if($payorder['type'] == 'xianjin_recharge') return $this->json(['status'=>0,'msg'=>'不能用'.t('余额').'支付']);
                }
                if(getcustom('system_moneypayscene')){
                    if(!$moneypayscene && $payorder['money']>0) return $this->json(['status'=>0,'msg'=>'该场景不支持'.t('余额').'支付']);
                }
                if(getcustom('commission_xianjin_percent') && getcustom('bonus_pool_gold')){
                    if($payorder['type']=='buy_gold'){
                        $buy_order = Db::name('buy_gold_order')->where('id',$payorder['orderid'])->find();
                        if($buy_order['select_paytype']=='xianjin'){
                            if($this->member['xianjin'] < $payorder['money']){
                                return $this->json(['status'=>0,'msg'=>t('现金').'不足,请充值']);
                            }
                            //选择现金账户时充值余额为0
                            \app\custom\MemberCustom::addXianjin(aid,mid,-$payorder['money'],'支付订单,订单号: '.$payorder['ordernum']);
                            $payorder['money'] = 0;
                        }
                    }
                }
				if($this->member['money'] < $payorder['money']){
					return $this->json(['status'=>0,'msg'=>t('余额').'不足,请充值']);
				}
				if($payorder['money'] > 0){
                    $aid = aid;$mid = mid;
                    $remark = '支付订单,订单号: '.$payorder['ordernum'];
                    $addmoney_params = [];//其他参数
                    if(getcustom('scoreshop_otheradmin_buy')){
                        //是来自本系统其他平台的用户
                        if(($payorder['type']=='scoreshop' || $payorder['type']=='scoreshop_hb') && $BuyOverallScoreshop && $othermember){
                            $aid = $othermember['aid'];$mid = $othermember['id'];
                            $remark = '支付'.$othermember['appname'].'订单,订单号: '.$payorder['ordernum'];
                            $addmoney_params['optaid'] = aid;
                        }
                    }
                    if(getcustom('moneylog_detail')){
                        $addmoney_params['ordernum'] = $payorder['ordernum'];    
                        $addmoney_params['type'] = $payorder['type'];    
                    }
                    if(getcustom('water_happy_ti')){
                        if($payorder['type']=='water_happyti'){
                            $remark = '支付打水订单,订单号: '.$payorder['ordernum'];
                        }
                    }
                    if(getcustom('luntan_pay_top')){
                        if($payorder['type'] == 'luntan'){
                            $remark = '支付置顶论坛,订单号: '.$payorder['ordernum'];
                        }
                    }

					//减去会员的余额
					if($payorder['type'] == 'maidan'){
                        if(getcustom('maidan_use_mendian')){
                            $addmoney_params['mdid'] = $order['mdid']??0;
                        }
                        $remark = $payorder['title'].',订单号: '.$payorder['ordernum'];
					}
                    if($post['scorepay']){
                        //使用积分支付
						$res = \app\common\Member::addscore($aid,$mid,-$payorder['money'],$remark,0,'','',1,$addmoney_params);
                        if(!$res['status']){
                            return $this->json(['status'=>0,'msg'=>$res['msg']]);
                        }
					}else{
                        \app\common\Member::addmoney($aid,$mid,-$payorder['money'],$remark,0,'',0,$addmoney_params);
                    }
                    
				}
				if($payorder['score'] > 0){
					//减去会员的积分
					$this->decscore($payorder,$decscore_params);
				}
                if(getcustom('gold_bean_shop')){
                    if($payorder['gold_bean'] > 0){
                        $goldRemark = '支付订单,订单号：'.$payorder['ordernum'];
                        \app\common\Member::addgoldbean($payorder['aid'],$payorder['mid'],-$payorder['gold_bean'],$goldRemark,'',$payorder['bid'],$addscore_params);
                    }
                }
                if(getcustom('yuyue_money_dec') && $payorder['type'] == 'yuyue'){
                    if($order['dec_money']>0){
                        \app\common\Member::addmoney($aid,$mid,-$order['dec_money'],t('余额').'抵扣订单,订单号: '.$order['ordernum']);
                        Db::name('yuyue_order')->where('id',$order['id'])->update(['dec_money_status'=>1]);
                    }
                }

                if(getcustom('product_service_fee') && $payorder['service_fee_money'] > 0){
                    //减去服务费
                    $this->payServiceFee($payorder);
                }
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				if($payorder['money']>0){
					$paytype = t('余额').'支付';
				}elseif($payorder['money']==0 && $payorder['score']>0 ){
					$paytype = t('积分').'支付';
				}
                if(getcustom('gold_bean_shop')){
                    if($payorder['money']==0 && $payorder['gold_bean']>0 ){
                        $paytype = t('金豆').'支付';
                    }
                }
				\app\model\Payorder::payorder($orderid,$paytype,1,'');
                //消费送积分
                $giftsScore = 1;
                //积分转赠手续费
                if(getcustom('score_transfer_sxf') && $payorder['type'] =='score_transfer'){
                    $giftsScore = 0;
                }
                $iszs = true;
                if(getcustom('score_stacking_give_set') && $set['score_stacking_give_set'] == 2){
                    $iszs = false;
                }

                //余额消费赠送积分单独配置
                if (getcustom('money_pay_give_score')){
                    $set['scorein_money'] = $this->sysset_custom['money_pay_scorein_money'];
                    $set['scorein_score'] = $this->sysset_custom['money_pay_scorein_score'];
                }

                if(getcustom('water_happy_ti')){
                    $water_happyti_order = Db::name('water_happyti_order')->where('id',$payorder['orderid'])->find();
                    if($water_happyti_order['status'] != 1){
                        $iszs = false;
                    }
                }
                 if(getcustom('maidan_give_score')){
                     if($payorder['type'] =='maidan'&& $payorder['bid'] > 0 ){
                         //是买单，重置 系统-系统设置-积分设置中的  余额支付送积分
                         $business_maidan_scorein = Db::name('business')->where('aid',$payorder['aid'])->where('id',$payorder['bid'])->field('moneypay_give_score_status,maidan_give_score_money,maidan_give_score_givenum')->find();
                         if($business_maidan_scorein['moneypay_give_score_status'] ==0){
                             $set['score_from_moneypay']  = 0;
                         }elseif($business_maidan_scorein['moneypay_give_score_status'] ==1){
                             $set['score_from_moneypay'] = 1;
                             $set['scorein_money']= $business_maidan_scorein['maidan_give_score_money'];
                             $set['scorein_score']= $business_maidan_scorein['maidan_give_score_givenum'];
                         }
                     }
                 }
                if($giftsScore == 1 && $iszs){
                    if($set['scorein_money']>0 && $set['scorein_score']>0 && $set['score_from_moneypay'] == 1){
                        $givescore = floor($payorder['money'] / $set['scorein_money']) * $set['scorein_score'];
                        if(getcustom('shop_alone_give_score') && $set['maidan_give_score'] == 2 && $payorder['type'] =='maidan'){
                            $givescore = $this->getGiveScore($payorder,$set);
                        }
                        $res = \app\common\Member::addscore(aid,mid,$givescore,'消费送'.t('积分'));
                        if($res && $res['status'] == 1){
                            //记录消费赠送积分记录
                            \app\common\Member::scoreinlog(aid,0,mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$payorder['money']);
                        }
                    }
                }
                if(getcustom('gold_bean')){
                    //余额支付送积分
                    $business_moneypay_goldbean = Db::name('business')->where('aid',$payorder['aid'])->where('id',$payorder['bid'])->field('maidan_give_gold_bean_money,maidan_give_gold_bean_givenum,maidan_give_gold_bean_status,moneypay_give_gold_bean_status')->find();
                    if($business_moneypay_goldbean['moneypay_give_gold_bean_status'] && $business_moneypay_goldbean['maidan_give_gold_bean_givenum']>0 && $business_moneypay_goldbean['maidan_give_gold_bean_money']>0 ){
                        $give_gold_bean = floor($payorder['money']/$business_moneypay_goldbean['maidan_give_gold_bean_money'] )*  $business_moneypay_goldbean['maidan_give_gold_bean_givenum'];
                        if($give_gold_bean > 0){
                            //$aid,$mid,$gold_bean,$remark,$channel='',$bid=0,$frommid=0,$addtotal=1,$params = []
                            $res = \app\common\Member::addgoldbean($payorder['aid'],$payorder['mid'],$give_gold_bean,t('余额').'支付送'.t('金豆').'，订单号：'.$payorder['ordernum']);
                            if($res && $res['status'] == 1){
                                //记录消费赠送金豆记录
                                \app\common\Member::goldbeaninlog($payorder['aid'],0,$payorder['mid'],$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$give_gold_bean);
                            }
                        }
                    }
                }
				return $this->json(['status'=>2,'msg'=>'付款成功']);
			}elseif($post['typeid']==4){ //货到付款
                if(getcustom('restaurant') && $payorder['type'] == 'restaurant_shop') {
                    $sysset = Db::name('restaurant_shop_sysset')->where('aid', aid)->where('bid', $payorder['bid'])->find();
                    $paytype_rest = explode(',', $sysset['paytype']);
					$codtxt = '线下支付';
                    if(!in_array('cash', $paytype_rest)) return $this->json(['status'=>0,'msg'=>'不支持'.$codtxt]);
                } else {
                    if($payorder['type'] != 'shop') return $this->json(['status'=>0,'msg'=>'不支持该付款方式']);
					$shopset = Db::name('shop_sysset')->where('aid',aid)->find();
					$codtxt = $shopset['codtxt'];
                    if(!$shopset['cancod']) return $this->json(['status'=>0,'msg'=>'不支持'.$codtxt]);
                    if(getcustom('shop_cod_precondition')) {
                        //货到付款前置条件
                        if($shopset['cod_precondition']){
                            //查询他是否有未完成的货到付款
                            $count = Db::name('shop_order')->where('mid',mid)->where('paytypeid',4)->where('status','>=',1)->where('status','<=',2)->where('aid',aid)->count();
                            if($count) return $this->json(['status'=>0,'msg'=>'请先完成之前'.$codtxt.'订单']);
                        }
                    }
                    if(getcustom('shop_cod_level')) {
                        if(empty($shopset['cod_levelids']))  return $this->json(['status'=>0,'msg'=>'不支持'.$codtxt]);
                        //货到付款可使用等级
                        $cod_levelids = explode(',',$shopset['cod_levelids']);
                        if(!in_array('-1',$cod_levelids) && !in_array($this->member['levelid'],$cod_levelids)){
                             return $this->json(['status' => 0, 'msg' => $shopset['codtxt'].'暂不能使用，不符合使用等级']);
                        }
                    }
                }
				if($payorder['score'] > 0){
					//减去会员的积分
					$this->decscore($payorder,$decscore_params);
				}
				\app\model\Payorder::payorder($orderid,$codtxt,4,'');
                $iszs = true;
                if(getcustom('score_stacking_give_set') && $set['score_stacking_give_set'] == 2){
                    $iszs = false;
                }
                //消费送积分
                if($set['scorein_money']>0 && $set['scorein_score']>0 && $set['score_from_xianxiapay'] == 1 && $iszs){
                    $givescore = floor($payorder['money'] / $set['scorein_money']) * $set['scorein_score'];
                    if(getcustom('shop_alone_give_score') && $set['alone_give_score'] == 2 && $payorder['type'] =='maidan'){
                        $givescore = $this->getGiveScore($payorder,$set);
                    }
                    $res = \app\common\Member::addscore(aid,mid,$givescore,'消费送'.t('积分'));
                    if($res && $res['status'] == 1){
                        //记录消费赠送积分记录
                        \app\common\Member::scoreinlog(aid,0,mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$payorder['money']);
                    }
                }
                if($payorder['type'] == 'shop'){
                    //有尾款支付金额，删除尾款payorder记录，重置付款
                    if($order['balance_pay_status'] == 0 && $order['balance_price']>0 && $order['balance_pay_orderid']){
                        Db::name('payorder')->where('id',$payorder['id'])->inc('money',$order['balance_price'])->update();
                        Db::name('payorder')->where('id',$order['balance_pay_orderid'])->where('orderid',$order['id'])->delete();
                        Db::name('shop_order')->where('id',$order['id'])->inc('totalprice',$order['balance_price'])->inc('product_price',$order['balance_price'])->update(['balance_price'=>0,'balance_pay_orderid'=>0]);
                    }
                }
				return $this->json(['status'=>2,'msg'=>'提交成功']);
            }elseif($post['typeid']==5){
                $money_recharge_transfer = getcustom('money_recharge_transfer');//余额充值转账汇款
                $maidan_orderadd_mobile_paytransfer = getcustom('maidan_orderadd_mobile_paytransfer');//买单转账汇款
                if(getcustom('pay_transfer')){
                    //转账汇款
                    $transfer_typ_arr = ['shop'];//支持转账汇款的类型
                    if($money_recharge_transfer){
                        //余额充值
                        $transfer_typ_arr[] = 'recharge';
                    }
                    if(getcustom('commission_xianjin_percent')){
                        $transfer_typ_arr[] = 'xianjin_recharge';
                    }
                    if($maidan_orderadd_mobile_paytransfer){
                        $transfer_typ_arr[] = 'maidan';
                    }

                    if(!in_array($payorder['type'],$transfer_typ_arr)) return $this->json(['status'=>0,'msg'=>'不支持'.t('转账汇款')]);
                    $pay_transfer = Db::name('admin_set')->where('aid',aid)->value('pay_transfer');
                    if(!$pay_transfer) return $this->json(['status'=>0,'msg'=>'不支持该支付方式']);
                    //使用角色
                    $gettj = explode(',',$set['pay_transfer_gettj']);
                    if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){
                        return $this->json(['status'=>0,'msg'=>'不支持该支付方式']);
                    }
                    if($payorder['score'] > 0){
                        //减去会员的积分
                        $this->decscore($payorder,$decscore_params);
                    }
                    //转账后修改状态为已支付
                    $paytype = t('转账汇款');
                    $paytypeid = 5;
                    //\app\model\Payorder::payorder($orderid,'转账汇款',5,'');
                    if($set && $set['pay_transfer_check'] == 1){
                        //需要审核
                        $transfer_check = 0;
                    }else{
                        //不需要审核
                        $transfer_check = 1;
                    }
                    Db::name('payorder')->where('id',$orderid)->update(['paytype'=>$paytype,'paytypeid'=>$paytypeid]);
                    Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->update(['paytype'=>$paytype,'paytypeid'=>$paytypeid,'platform'=>platform,'transfer_check'=>$transfer_check]);

                    if($payorder['type']=='recharge'){
                        $gotourl = '/pagesExt/money/moneylog?st=1';
                    }else if($payorder['type']=='xianjin_recharge'){
                        $gotourl = '/pagesD/my/xianjinlog?st=1';
                    }else if($maidan_orderadd_mobile_paytransfer && $payorder['type'] == 'maidan'){
                        $gotourl = '/adminExt/order/maidanlog';
                    }else {
                        $gotourl = '/pagesExt/order/orderlist';
                    }

                    if($transfer_check == 1){
                        return $this->json(['status'=>2,'msg'=>'提交成功']);
                    }else{
                        return $this->json(['status'=>1,'msg'=>'提交成功待审核','gotourl'=>$gotourl]);
                    }
                }
			}elseif($post['typeid']==2){ //微信支付
                if(getcustom('wxpay_member_level')){
                    //微信支付使用角色
                    if($set && $set['wxpay_gettj']){
                        $gettj = explode(',',$set['wxpay_gettj']);
                        //不是所有人
                        if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){
                            return $this->json(['status'=>0,'msg'=>'暂不能使用微信支付']);
                        }
                    }
                }
                if(getcustom('pay_money_combine')){
                    //余额组合支付
                    $combines = $post['combines']?$post['combines']:'';
                    if($combines && $combines['moneypay'] == 1){
                        $deal = self::deal_money_combine(aid,mid,$this->member,$post['typeid'],$combines,$payorder,$set);
                        if(!$deal || $deal['status'] == 0){
                            $msg = $deal['msg']?$deal['msg']:t('余额').'支付错误，请重试';
                            return $this->json(['status'=>0,'msg'=>$msg]);
                        }else if($deal['status'] == 2){
                            return $this->json(['status'=>2,'msg'=>$deal['msg']]);
                        }else{
                            $payorder['money'] = $deal['money'];
                        }
                    }
                }
				$buildfun = 'build_'.platform;
                $openid = '';
                if(getcustom('maidan_auto_reg')){
                    //开启了买单不注册会员
                    $maidan_auto_reg = Db::name('admin_set')->where('aid',aid)->value('maidan_auto_reg');
                    if(!$maidan_auto_reg && !$this->mid && cache($this->sessionid.'_openid')){
                        $openid = cache($this->sessionid.'_openid');
                    }
                }
                if(getcustom('sxpay_apptowx') ){
                    $appinfo = \app\common\System::appinfo(aid,'app');
                    if($appinfo['wxpay_type']==3 && platform=='app'){
                        $sessionid = \think\facade\Session::getId();;
                        $path = '/pagesB/pay/pay';
                        $rs['data']['pay_plate'] = 'wx';
                        $query = 'session_id='.$sessionid.'&orderid='.$orderid;
                        $res_url = \app\common\Wechat::getUrlScheme(aid,$path,$query);
                        if($res_url['status']) {
                            $url = $res_url['url'];
                            $rs['data']['is_jump'] = 1;
                            $rs['data']['jump_link'] = m_url('/pagesB/pay/pay?session_id=' . $sessionid . '&wx_url=' . urlencode($url));
                            $rs['data']['wx_url'] = $url;
                            return $this->json($rs);
                        }else{
                            return $this->json(['status'=>0,'msg'=>$res_url['msg']]);
                        }
                    }
                }
                if(getcustom('product_service_fee') && $payorder['service_fee_money'] > 0){
                    //减去服务费
                    $this->payServiceFee($payorder);
                }
                if(getcustom('pay_ysepay')){
                    if(platform=='h5'){
                        $appinfo = \app\common\System::appinfo(aid,'h5');
                        if($appinfo['wxpay_type']==5){
                            //银盛支付 https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwX338xTWCSPmNPQMkhh
                            require_once(ROOT_PATH.'/extend/ysepay/YsfApiService.php');
                            $YsfApiService = new \YsfApiService(aid,$payorder['bid'],mid);
                            $rs = $YsfApiService->createOrder($payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type']);
                            if($rs['status'] == 1 && $rs['data']) {
                                $rs['data']['wx_url'] = $rs['data']['payUrl'];//重定义，防止接口变动
                                return $this->json($rs);
                            }else{
                                return $this->json($rs);
                            }
                        }
                    }
                }
                if(getcustom('wxpay_native_h5') && $post['wxpay_type'] == 7){
                    $wx_native_h5 = Db::name('admin_setapp_h5')->where('aid',aid)->value('wx_native_h5');
                    if($wx_native_h5 != 1){
                        return $this->json(['status'=>0,'msg'=> '微信收款码功能未开启']);
                    }
                    //微信收款码
                    $buildfun = 'build_pay_native_h5';
                }
				$rs = \app\common\Wxpay::$buildfun(aid,$payorder['bid'],mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],'',$openid);
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==3){ //支付宝支付
                //支付宝交易组件
                $plugin_order_id = '';
                if(getcustom('alipay_plugin_trade') && in_array($payorder['type'],['shop','shop_hb'])){
                    $alipayPluginOrder = input('param.alipayPluginOrder');
                    $sourceId = input('param.sourceId');
                    if($alipayPluginOrder==1 && $sourceId){
                        $pluginResult = \app\common\Alipay::pluginOrderCreate(aid,$payorder['orderid'],mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],$sourceId);
                        if(!$pluginResult || $pluginResult['status']!=1){
                            return $this->json($pluginResult);
                        }
                        //更新订单`alipay_component_orderid`
                        $plugin_order_id = $pluginResult['order_id'];
                        writeLog('-----------pluginOrder------');
                        writeLog(json_encode([
                            'alipayPluginOrder'=>$alipayPluginOrder,
                            'sourceId'=>$sourceId,
                            'orderid'=>$payorder['orderid'],
                        ]));
                        writeLog('-----------pluginOrder------');
                        writeLog(json_encode($pluginResult));
                        writeLog('-----------pluginOrder------');
                    }
                    $ordmap = [];
                    $ordmap[] = ['aid','=',aid];
                    if($payorder['type']=='shop_hb'){
                        $ordmap[] = ['ordernum','like',$payorder['ordernum'].'_%'];
                    }else{
                        $ordmap[] = ['ordernum','=',$payorder['ordernum']];
                    }
                    Db::name('shop_order')->where($ordmap)->update(['alipay_component_orderid'=>$plugin_order_id]);
                }
                if(getcustom('pay_money_combine')){
                    //余额组合支付
                    $combines = $post['combines']?$post['combines']:'';
                    if($combines && $combines['moneypay'] == 1){
                        $deal = self::deal_money_combine(aid,mid,$this->member,$post['typeid'],$combines,$payorder,$set);
                        if(!$deal || $deal['status'] == 0){
                            $msg = $deal['msg']?$deal['msg']:t('余额').'支付错误，请重试';
                            return $this->json(['status'=>0,'msg'=>$msg]);
                        }else if($deal['status'] == 2){
                            return $this->json(['status'=>2,'msg'=>$deal['msg']]);
                        }else{
                            $payorder['money'] = $deal['money'];
                        }
                    }
                }
				$buildfun = 'build_'.platform;
				$alih5 = input('param.alih5')?true:false;
                $openid = '';
                $openid_new = '';
                if(getcustom('maidan_auto_reg')){
                    //开启了买单不注册会员
                    $maidan_auto_reg = Db::name('admin_set')->where('aid',aid)->value('maidan_auto_reg');
                    if(!$maidan_auto_reg && !$this->mid && cache($this->sessionid.'_openid')){
                        $openid = cache($this->sessionid.'_openid');
                        $openid_new = cache($this->sessionid.'_openid_new');
                    }
                }
                if(getcustom('sxpay_apptowx') && platform=='app' ) {
                    $appinfo = \app\common\System::appinfo(aid, 'app');
                    $appinfo_alipay = \app\common\System::appinfo(aid, 'alipay');
                    if ($appinfo['alipay_type'] == 3) {
                        $appid = $appinfo_alipay['appid'];
                        $sessionid = \think\facade\Session::getId();;
                        $page = 'pagesB/pay/pay';
                        $query = 'session_id='.$sessionid.'&orderid='.$orderid.'&ali_appid='.$appid;
                        $scheme = 'alipays://platformapi/startapp?appId=' . $appid . '&page=' . $page . '&query=' . urlencode($query);
                        $ali_url = 'https://ds.alipay.com/?scheme=' . urlencode($scheme);
                        $rs['data']['is_jump'] = 1;
                        $rs['data']['jump_link'] = m_url('/pagesB/pay/pay?session_id=' . $sessionid .'&wx_url=' . urlencode($ali_url));
                        $rs['data']['wx_url'] = $ali_url;
                        return $this->json($rs);
                    }
                }
                if(getcustom('product_service_fee') && $payorder['service_fee_money'] > 0){
                    //减去服务费
                    $this->payServiceFee($payorder);
                }
				$rs = \app\common\Alipay::$buildfun(aid,$payorder['bid'],mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],'','',1,$alih5,$plugin_order_id,$openid,$openid_new);
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==11){ //百度小程序支付
				$rs = \app\common\Baidupay::build(aid,mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type']);
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==12){ //头条小程序支付
				$rs = \app\common\Ttpay::build(aid,mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type']);
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==22){ //云收银
				$buildfun = 'build_'.platform;
				$rs = \app\common\Yunpay::$buildfun(aid,mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],'',m_url('pagesExt/pay/pay?id='.$payorder['id']));
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==23){
				$buildfun = 'build_h5';
				$rs = \app\common\Qmpay::$buildfun(aid,mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],'',m_url('pagesExt/pay/pay?id='.$payorder['id']));
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==24){
				$buildfun = 'build_h5_2';
				$rs = \app\common\Qmpay::$buildfun(aid,mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],'',m_url('pagesExt/pay/pay?id='.$payorder['id']));
				//元宝 更新payordr支付
                $this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==41){ //月结
                if(getcustom('pay_month')){
                    $paytypeid=41;
                    if($payorder['type'] != 'shop') return $this->json(['status'=>0,'msg'=>'不支持该付款方式']);
                    $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
                    $codtxt = $shopset['pay_month_txt'];
                    if(!$shopset['pay_month']) return $this->json(['status'=>0,'msg'=>'不支持'.$codtxt]);

                    if($payorder['score'] > 0){
                        //减去会员的积分
						$this->decscore($payorder,$decscore_params);
                    }
                    \app\model\Payorder::payorder($orderid,$codtxt,$paytypeid,'');
                    return $this->json(['status'=>2,'msg'=>'提交成功']);
                }
            }elseif($post['typeid']==51){ //paypal支付
				$rs = \app\custom\PayPal::build(aid,platform,$payorder);
				return $this->json($rs);
			}elseif($post['typeid']==61){ //汇付天下银联支付
				if(getcustom('pay_adapay')){
	                $buildfun = 'build_union_'.platform;
	                $rs = \app\custom\AdapayPay::$buildfun(aid,$payorder['bid'],mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type']);
	                return $this->json($rs);
				}
            }elseif($post['typeid']==63){ //汇付斗拱快捷支付
                if(getcustom('pay_huifu_quickpay')){
                    $huifu = new \app\custom\Huifu($appinfo,aid,$payorder['bid'],mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type']);
                    $rs = $huifu->quickpay(['front_url' => request()->domain().'/payReturn.php?type=quickpay&aid='.aid.'&p='.platform.'&url='.urlencode($tourl)]);
                    return $this->json($rs);
                }
            }elseif($post['typeid']==71){
			    //冻结佣金支付
                if(getcustom('commission_xiaofei')){
                    if($payorder['type'] == 'recharge') return $this->json(['status'=>0,'msg'=>'不能用'.t('冻结佣金').'支付']);
                    if(getcustom('commission_xianjin_percent')){
                        if($payorder['type'] == 'xianjin_recharge') return $this->json(['status'=>0,'msg'=>'不能用'.t('余额').'支付']);
                    }
                    if($this->member['xiaofei_money'] < $payorder['money']){
                        return $this->json(['status'=>0,'msg'=>t('冻结佣金').'不足,请充值']);
                    }
                    if($payorder['money'] > 0){
                        //减去会员的余额
                        if($payorder['type'] == 'maidan'){
                            \app\common\Member::addxiaofei(aid,mid,mid,-$payorder['money'],$payorder['title'].',订单号: '.$payorder['ordernum']);
                        }else{
                            \app\common\Member::addxiaofei(aid,mid,mid,-$payorder['money'],'支付订单,订单号: '.$payorder['ordernum']);
                        }
                    }

                    \app\model\Payorder::payorder($orderid,t('余额').'支付',1,'');
                    $iszs = true;
                    if(getcustom('score_stacking_give_set') && $set['score_stacking_give_set'] == 2){
                        $iszs = false;
                    }
                    //消费送积分
                    if($set['scorein_money']>0 && $set['scorein_score']>0 && $set['score_from_moneypay'] == 1 && $iszs){
                        $givescore = floor($payorder['money'] / $set['scorein_money']) * $set['scorein_score'];
                        $res = \app\common\Member::addscore(aid,mid,$givescore,'消费送'.t('积分'));
                        if($res && $res['status'] == 1){
							//记录消费赠送积分记录
							\app\common\Member::scoreinlog(aid,0,mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$payorder['money']);
						}
                    }
                    return $this->json(['status'=>2,'msg'=>'付款成功']);
                }
            }elseif($post['typeid'] == 81){
                //餐饮收银台随行付支付,勿使用该值作为其他类型支付
            }elseif($post['typeid']==38){//信用付
                if(getcustom('member_overdraft_money')){
                    if($overdraft_moneypay==0){
                        return $this->json(['status'=>0,'msg'=>t('信用额度').'支付未开启']);
                    }
                    //信用额度支付
                    $open_overdraft_money = $this->member['open_overdraft_money']??0;
                    $limit_money = $this->member['limit_overdraft_money']??0;
                    if($open_overdraft_money == 0 && $limit_money == 0){
                        return $this->json(['status'=>0,'msg'=>t('信用额度').'不足']);
                    }
                    if($open_overdraft_money == 0 && $limit_money>0 && ($this->member['overdraft_money']-$payorder['money'] < $limit_money*-1)){
                        return $this->json(['status'=>0,'msg'=>t('信用额度').'不足']);
                    }
                    if($payorder['money'] > 0){
                        //减去会员的额度
                        \app\common\Member::addOverdraftMoney(aid,mid,-$payorder['money'],'支付订单,订单号: '.$payorder['ordernum']);
                    }
                    if($payorder['score'] > 0){
                        //减去会员的积分
                        $this->decscore($payorder,$decscore_params);
                    }
                    \app\model\Payorder::payorder($orderid,'信用额度支付',38,'');
                    $iszs = true;
                    if(getcustom('score_stacking_give_set') && $set['score_stacking_give_set'] == 2){
                        $iszs = false;
                    }
                    //消费送积分
                    if($set['scorein_money']>0 && $set['scorein_score']>0 && $set['score_from_moneypay'] == 1 && $iszs){
                        $givescore = floor($payorder['money'] / $set['scorein_money']) * $set['scorein_score'];
                        $res = \app\common\Member::addscore(aid,mid,$givescore,'消费送'.t('积分'),$payorder['type'],0,0,1,['orderid'=>$payorder['orderid'],'ordernum'=>$payorder['ordernum'],'residue'=>$givescore]);
                        if($res && $res['status'] == 1){
							//记录消费赠送积分记录
							\app\common\Member::scoreinlog(aid,0,mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$payorder['money']);
						}
                    }
                    return $this->json(['status'=>2,'msg'=>'付款成功']);
                }
            }elseif($post['typeid'] == 122){
//                //国补凭证码上报
//                if($payorder['type'] == 'shop'){
//                    $this->authcodeExchangeCommit($payorder['orderid']);
//                }
                //跳转云闪付小程序支付
                return $this->yunshanfuWxPay($payorder);
            }elseif($post['typeid']=='sxpay_native'){ 
                if(getcustom('sxpay_native')){
                    //随行付扫码支付 $post['typeid'] == 124

                    $alih5 = input('param.alih5')?true:false;
                    if(getcustom('product_service_fee') && $payorder['service_fee_money'] > 0){
                        //减去服务费
                        $this->payServiceFee($payorder);
                    }

                    $paytype = '';
                    if(platform == 'mp'){
                        $paytype = 'sxpaymp_native';
                    }else if(platform == 'wx'){
                        $paytype = 'sxpaywx_native';
                    }else if(platform == 'alipay'){
                        if($alih5){
                            $paytype = 'sxalih5_native';
                        }else{
                            $paytype = 'sxpayalipay_native';
                        }
                    }else if(platform == 'app'){
                        $paytype = 'sxpayapp_native';
                    }else if(platform == 'h5'){
                        $paytype = 'sxpayh5_native';
                    }
                    $ordernum= $payorder['ordernum'];
                    //生成支付交易流水
                    $pay_transaction = \app\common\Common::createPayTransaction(aid,$ordernum,$payorder['type']);
                    if(!$pay_transaction){
                        return $this->json(['status'=>0,'data'=>'生成交易流水失败']);
                    }
                    $ordernum = $pay_transaction['transaction_num'];

                    $timeExpire = 5;//订单失效时间，默认5分钟
                    if($countDown>=0){
                        if($countDown<15){
                            return $this->json(['status'=>0,'data'=>'订单即将关闭，暂不可用聚合支付']);
                        }
                        $timeExpire = floor($countDown/60);
                        if($timeExpire == 0) $timeExpire = 1;
                    }
                    $timeExpire = intval($timeExpire)+'';
                    $rs = \app\custom\Sxpay::build_native(aid,$payorder['bid'],mid,$payorder['title'],$ordernum,$payorder['money'],$payorder['type'],platform,$paytype,$timeExpire);
                    return $this->json($rs);
                }
            }
			if(getcustom('plug_more_alipay') && (platform == 'h5' || platform == 'app')){
				if($post['typeid']>=302 && $post['typeid']<=330){ //支付宝支付
					if(getcustom('pay_money_combine')){
                        //余额组合支付
                        $combines = $post['combines']?$post['combines']:'';
                        if($combines && $combines['moneypay'] == 1){
                            $deal = self::deal_money_combine(aid,mid,$this->member,$post['typeid'],$combines,$payorder,$set);
                            if(!$deal || $deal['status'] == 0){
                                $msg = $deal['msg']?$deal['msg']:t('余额').'支付错误，请重试';
                                return $this->json(['status'=>0,'msg'=>$msg]);
                            }else if($deal['status'] == 2){
                                return $this->json(['status'=>2,'msg'=>$deal['msg']]);
                            }else{
                                $payorder['money'] = $deal['money'];
                            }
                        }
                    }
					$more = $post['typeid'] - 300;
					$buildfun = 'build_'.platform;
					$rs = \app\common\Alipay::$buildfun(aid,$payorder['bid'],mid,$payorder['title'],$payorder['ordernum'],$payorder['money'],$payorder['type'],'','',$more);
					//元宝 更新payordr支付
                	$this->yuanbao_up_pay(aid,$yuanbaopay,$orderid,$post['pay_type'],$payorder);
					return $this->json($rs);
				}
			}
		}

		$userinfo = [];
		$userinfo['money'] = $this->member['money'];
		$userinfo['score'] = dd_money_format($this->member['score'],$score_weishu);
        if(getcustom('scoreshop_otheradmin_buy')){
            //记录来源平台及来源平台用户
            if(($payorder['type']=='scoreshop' || $payorder['type']=='scoreshop_hb') && $BuyOverallScoreshop && $othermember){
                $userinfo['money'] = dd_money_format($othermember['money'],$this->moeny_weishu);
                $userinfo['score'] = dd_money_format($othermember['score'],$score_weishu);
            }
        }
        $userinfo['xiaofei_money'] = 0;
		if(getcustom('commission_xiaofei')){
            $userinfo['xiaofei_money'] = $this->member['xiaofei_money'];
        }

		if($this->member['paypwd']==''){
			$userinfo['haspwd'] = 0;
		}else{
			$userinfo['haspwd'] = 1;
		}
		$userinfo['yuanbao'] = 0;
		if(getcustom('pay_yuanbao')) {
            $userinfo['yuanbao'] = $this->member['yuanbao'];
        }
        if(getcustom('member_overdraft_money')){
            $userinfo['overdraft_money'] = $this->member['overdraft_money'];
            $userinfo['limit_overdraft_money'] = $this->member['limit_overdraft_money'];
            $open_overdraft_money = $this->member['open_overdraft_money'];
            $limit_money = $this->member['limit_overdraft_money'];
            $overdraft_money = $this->member['overdraft_money']*-1;
            if(empty($limit_money)){
                $overdraft_money_now = 0; 
            }else{
                $overdraft_money_now = round($limit_money - $overdraft_money,2);
            }
            if($open_overdraft_money == 1){
                $overdraft_money_now = '无限';
            }
            $userinfo['overdraft_money'] = $overdraft_money_now;
            
        }
        $userinfo['xianjin'] = 0;
        if(getcustom('commission_xianjin_percent')){
            $userinfo['xianjin'] = $this->member['xianjin'];
        }

        //订阅消息
		$tmplids = [];
		if(platform == 'wx' && in_array($payorder['type'],['shop','collage','scoreshop','kanjia','seckill'])){
			$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
			if($wx_tmplset['tmpl_orderfahuo_new']){
				$tmplids[] = $wx_tmplset['tmpl_orderfahuo_new'];
			}elseif($wx_tmplset['tmpl_orderfahuo']){
				$tmplids[] = $wx_tmplset['tmpl_orderfahuo'];
			}
			if($payorder['type'] == 'collage'){
				if($wx_tmplset['tmpl_collagesuccess_new']){
					$tmplids[] = $wx_tmplset['tmpl_collagesuccess_new'];
				}elseif($wx_tmplset['tmpl_collagesuccess']){
					$tmplids[] = $wx_tmplset['tmpl_collagesuccess'];
				}
			}
		}
        if(getcustom('choujiang_time') && 	platform == 'wx' && in_array($payorder['type'],['dscj'])){
            $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
            if($wx_tmplset['tmpl_choujiang']){
                $tmplids[] = $wx_tmplset['tmpl_choujiang'];
            }
        }

		if(getcustom('yuyue_apply') && 	platform == 'wx' && in_array($payorder['type'],['yuyue_workerapply'])){
			$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
			if($wx_tmplset['tmpl_shenhe_new']){
				$tmplids[] = $wx_tmplset['tmpl_shenhe_new'];
			}
		}

		if(getcustom('hotel') && platform == 'wx' && in_array($payorder['type'],['hotel'])){
			$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
			if($wx_tmplset['tmpl_hotelbooking_success']){
				$tmplids[] = $wx_tmplset['tmpl_hotelbooking_success'];
			}
			$order = Db::name('hotel_order')->where('aid',aid)->where('id',$payorder['orderid'])->find();
			if($order['use_money']>0) $tmplids[] = $wx_tmplset['tmpl_moneychange'];

		}
		if(getcustom('product_luckyfree') && platform == 'wx' && in_array($payorder['type'],['shop'])){
			$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
			if($wx_tmplset['tmpl_luckyfree']){
				$tmplids[] = $wx_tmplset['tmpl_luckyfree'];
			}
		}
		$pay_transfer = 0;
		if($payorder['type'] == 'shop'){
			$shopset = Db::name('shop_sysset')->where('aid',aid)->find();
            if(getcustom('pay_transfer')){
                $gettj = explode(',',$set['pay_transfer_gettj']);
                //不是所有人
                if(in_array('-1',$gettj) || in_array($this->member['levelid'],$gettj)){
                    $pay_transfer = $set['pay_transfer'];
                }
            }
			\think\facade\Log::write(input('param.scene'));
			if(in_array(input('param.scene'),[1175,1176,1177,1191,1195,1216,10001])){
				$order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
				if($order['fromwxvideo'] == 0){
					Db::name('shop_order')->where('id',$payorder['orderid'])->update(['fromwxvideo'=>1,'scene'=>input('param.scene')]);
				}
			}
		}
		//\think\facade\Log::write($payorder);
        if(getcustom('active_score')) {
            //处理让利积分预读取
            if($payorder['type']=='shop' || $payorder['type']=='shop_hb' || $payorder['type']=='maidan'){
                if($this->sysset_custom['active_score_payshow'] && $this->sysset_custom['active_score_payshow'] == 1){
                    $all_active_score = 0;
                    if($payorder['type']=='shop'){
                        $giveActiveScore = \app\common\Order::giveActiveScore(aid,$order,'shop',1);
                        $all_active_score += $giveActiveScore['member_activescore'];
                        $all_active_score += $giveActiveScore['business_activescore'];
                    }else if($payorder['type']=='shop_hb'){
                        $acordes = Db::name('shop_order')->where('aid',aid)->where('ordernum','like',$payorder['ordernum'].'%')->select()->toArray();
                        foreach($acordes as $ov){
                            $giveActiveScore = \app\common\Order::giveActiveScore(aid,$ov,'shop',1);
                            $all_active_score += $giveActiveScore['member_activescore'];
                            $all_active_score += $giveActiveScore['business_activescore'];
                        }
                        unset($ov);
                    }else if($payorder['type']=='maidan'){
                        $giveActiveScore = \app\common\Order::giveActiveScore(aid,$order,'maidan',1);
                        $all_active_score += $giveActiveScore['member_activescore'];
                        $all_active_score += $giveActiveScore['business_activescore'];
                    }
                    if($score_weishu==0){
                        $payorder['all_active_score'] = intval($all_active_score);//总获取让利积分
                    }else{
                        $payorder['all_active_score'] = dd_money_format($all_active_score,$score_weishu);//总获取让利积分
                    }
                    $payorder['active_score_payshow_text'] = $this->sysset_custom['active_score_payshow_text']??'预估获得';
                    $payorder['score_to_money_and_commission'] = 0;//是否开启{:t('积分')}转{:t('余额')}和{:t('佣金')}
                    if(getcustom('score_to_money_and_commission')){
                        //若开启{:t('积分')}转{:t('余额')}和{:t('佣金')}，则计算让利积分转换结果
                        if($this->sysset['score_to_money_and_commission'] && $this->sysset['score_to_money_and_commission'] ==1){
                            $payorder['score_to_money_and_commission'] = $this->sysset['score_to_money_and_commission'];
                            $payorder['score_to_money'] =  $payorder['score_to_commission'] =  $payorder['remaining_score'] = 0;
                            $moneyRatio = $this->sysset['score_to_money_bili'] / 100;
                            $commissionRatio = $this->sysset['score_to_commission_bili'] / 100;
                            $totalRatio = $moneyRatio + $commissionRatio;
                            //换算比例
                            $money = $payorder['all_active_score'] * $moneyRatio * $this->sysset['score_to_money_ratio'];
                            $commission = $payorder['all_active_score'] * $commissionRatio * $this->sysset['score_to_commission_ratio'];

                            if($money > 0)  $payorder['score_to_money'] = dd_money_format($money,$this->moeny_weishu);
                            if($commission > 0){
                                $commission_weishu = 2;//佣金位数
                                if(getcustom('fenhong_money_weishu',$aid)){
                                    $commission_weishu = $this->sysset['fenhong_money_weishu'];
                                }
                                $payorder['score_to_commission'] = dd_money_format($commission,$commission_weishu);
                            } 
                            //计算剩余积分
                            if($totalRatio < 1){
                                $remainingScore = $payorder['all_active_score'] * (1 - $totalRatio);
                                if($remainingScore >0){
                                    if($score_weishu==0){
                                        $payorder['remaining_score'] = intval($remainingScore);//总获取让利积分
                                    }else{
                                        $payorder['remaining_score'] = dd_money_format($remainingScore,$score_weishu);
                                    }
                                }
                            };
                            //若都为0，则重置总获取让利积分为0，前不显示
                            if($payorder['score_to_money'] == 0 && $payorder['score_to_commission']== 0 && $payorder['remaining_score'] == 0){
                                $payorder['all_active_score'] = 0;
                            }
                        }
                    }
                }
            }
        }
		$rdata = [];
		$rdata['payorder'] = $payorder;
		$rdata['wxpay'] = $appinfo['wxpay']==1 ? 1 : 0;
		$rdata['wxpay_type'] = $appinfo['wxpay_type'];
		$rdata['alipay'] = $appinfo['alipay']==1 ? 1 : 0;
		$rdata['baidupay'] = $appinfo['baidupay']==1 ? 1 : 0;
		$rdata['toutiaopay'] = $appinfo['toutiaopay']==1 ? 1 : 0;
		$rdata['moneypay'] = ($set['moneypay'] && $payorder['type']!='recharge' && $payorder['type']!='overdraft_recharge') ? 1 : 0;//余额支付开关
        if(getcustom('system_moneypayscene')){
            //判断场景能否余额支付
            if($rdata['moneypay']){
                if(!$moneypayscene) $rdata['moneypay'] = 0;
            }
        }
        if(getcustom('commission_xianjin_percent')){
            if($payorder['type'] == 'xianjin_recharge') $rdata['moneypay'] = 0;
        }
		$rdata['overdraft_moneypay'] = $overdraft_moneypay;

        if(getcustom('business_withdraw_cash_mobile')){
            if($payorder['type'] == 'business_withdrawfee_cash') $rdata['moneypay'] = 0;
        }

		if(getcustom('business_moneypay')){ //多商户设置的是否可以用余额支付
			if($payorder['bid'] != 0){
				$moneypayset = Db::name('business_sysset')->where('aid',aid)->value('moneypay');
				if($moneypayset == 1) $rdata['moneypay'] = 1;
				if($moneypayset == 2) $rdata['moneypay'] = 0;
			}
		}
		if(getcustom('product_moneypay') && $payorder['type'] == 'shop'){ //商品中设置的是否可以用余额支付
			$hasnomoneypay = Db::name('shop_order_goods')->alias('og')->where('og.orderid',$order['id'])->join('shop_product product','og.proid=product.id')->column('product.product_moneypay');
            //product_moneypay 余额支付状态 0关闭 1开启 2仅限余额
            if(in_array(0,$hasnomoneypay)){
                //有一个关闭 就不能用余额支付
                $rdata['moneypay'] = 0;
            }else{
                //有一个仅限余额 就只能仅限余额支付
                if(in_array(2,$hasnomoneypay)){
                    $rdata['moneypay'] = 2;
                }else{
                    $rdata['moneypay'] = 1;
                }
            }
        }
        $rdata['xiaofeipay'] = 0;
        if(getcustom('commission_xiaofei') && $payorder['type'] == 'shop'){ //商品中设置的是否可以用余额支付
            $hasnomoneypay = Db::name('shop_order_goods')->alias('og')->where('og.orderid',$order['id'])->join('shop_product product','og.proid=product.id')->field('product.product_xiaofeipay')->find();
            if($hasnomoneypay){
                $rdata['xiaofeipay'] = $hasnomoneypay['product_xiaofeipay'];
            }else{
                $rdata['xiaofeipay'] = 0;
            }
        }
        //收银台广告位
        if(getcustom('maidan_pay_ads')){
            $adlist = [];
            //找出bid
            $paytype = $payorder['type'];
            $bids = $payorder['bid']>0?[$payorder['bid']]:[];
            if(strpos($paytype, '_hb') !== false){
                $bidsHb = Db::name('payorder')->where('bid','>',0)->where('aid',$payorder['aid'])->where('ordernum', 'like',  $payorder['ordernum'] . '_%' )->column('bid');
                if(!empty($bidsHb)){
                    $bids = array_merge($bids,$bidsHb);
                }
            }
            //多商户的
            $bidFilter = [];
            if($bids) {
                foreach ($bids as $k => $bid) {
                    $bidFilter[] = "find_in_set({$bid},`bind_bids`)";
                }
            }
            $adwhere = [];
            $adwhere[] = ['aid','=',aid];
            $adwhere[] = ['status','=',1];
            $adwhere[] = Db::raw("find_in_set('-1',`scene`) OR find_in_set('pay',`scene`)");
            $whereStr = "is_bind_bid=0";
            if($bidFilter){
                $bidFilterString = implode(' OR ',$bidFilter);
                $whereStr = $whereStr." OR (is_bind_bid=1 and ({$bidFilterString}))";
            }
            $adwhere[] = Db::raw($whereStr);
            $adlist = Db::name('maidan_ads')->where($adwhere)->order('sort desc,id desc')->select()->toArray();
            $rdata['adlist'] = $adlist??[];
        }

        //余额充值-转账汇款
        $money_recharge_transfer = getcustom('money_recharge_transfer');
        if($payorder['type'] == 'recharge' && $money_recharge_transfer){
            if(getcustom('pay_transfer')){
                $gettj = explode(',',$set['pay_transfer_gettj']);
                //不是所有人
                if(in_array('-1',$gettj) || in_array($this->member['levelid'],$gettj)){
                    $pay_transfer = $set['pay_transfer'];
                }
            }
            //可用支付方式
            $money_recharge_pay_type = explode(',',$set['money_recharge_pay_type']);
            if(!in_array('wxpay',$money_recharge_pay_type)){
                $rdata['wxpay'] = 0;
            }
            if(!in_array('alipay',$money_recharge_pay_type)){
                $rdata['alipay'] = 0;
            }
            if(!in_array('transfer',$money_recharge_pay_type)){
                $pay_transfer = 0;
            }
        }
        if(getcustom('commission_xianjin_percent')){
            if($payorder['type'] == 'xianjin_recharge'){
                if(getcustom('pay_transfer')){
                    $gettj = explode(',',$set['pay_transfer_gettj']);
                    //不是所有人
                    if(in_array('-1',$gettj) || in_array($this->member['levelid'],$gettj)){
                        $pay_transfer = $set['pay_transfer'];
                    }
                }

                //可用支付方式
                $xianjin_recharge_pay_type = explode(',',$set['xianjin_recharge_pay_type']);
                if(!in_array('wxpay',$xianjin_recharge_pay_type)){
                    $rdata['wxpay'] = 0;
                }
                if(!in_array('alipay',$xianjin_recharge_pay_type)){
                    $rdata['alipay'] = 0;
                }
                if(!in_array('transfer',$xianjin_recharge_pay_type)){
                    $pay_transfer = 0;
                }
            }
        }

		$rdata['pay_transfer'] = $pay_transfer;
        $rdata['userinfo'] = $userinfo;
		$rdata['cancod'] = $shopset['cancod'];//货到付款
        if($payorder['type'] == 'shop'){
            if(getcustom('shop_cod_precondition')) {
                //货到付款前置条件
                if($rdata['cancod'] && $shopset['cod_precondition']){
                    //查询他是否有未完成的货到付款
                    $count = Db::name('shop_order')->where('mid',mid)->where('paytypeid',4)->where('status','>=',1)->where('status','<=',2)->where('aid',aid)->count();
                    if($count) $rdata['cancod'] = $shopset['cancod'] = 0;
                }
            }
            if(getcustom('shop_cod_level')) {
                //货到付款可使用等级
                if($rdata['cancod']){
                    if(empty($shopset['cod_levelids'])) $rdata['cancod'] = $shopset['cancod'] = 0;
                    $cod_levelids = !empty($shopset['cod_levelids'])?explode(',',$shopset['cod_levelids']):[];
                    if(!in_array('-1',$cod_levelids) && !in_array($this->member['levelid'],$cod_levelids)) $rdata['cancod'] = $shopset['cancod'] = 0;
                }
            }
        }
        if(getcustom('shop_cod_onlinepay')){
            if($payorder['status'] != 0){
                $rdata['cancod'] = $shopset['cancod'] = 0;
            }
        }

		$rdata['codtxt'] = $shopset['codtxt'];
		$rdata['cod_frontmoney'] = 0;
		$rdata['cod_payorderid'] = 0;
		if(getcustom('shop_cod_frontpercent') && $shopset['cancod'] == 1 && $payorder['type'] == 'shop'){
			$frontorder = Db::name('payorder')->where('orderid',$payorder['orderid'])->where('type','shopfront')->find();
			if($frontorder){
				$rdata['cod_frontmoney'] = $frontorder['money'];
				$rdata['cod_payorderid'] = $frontorder['id'];
			}
		}
        $rdata['pay_month'] = $shopset['pay_month'] && getcustom('pay_month') ? 1 : 0;//月结
        $rdata['pay_month_txt'] = $shopset['pay_month_txt'];
		if($payorder['type'] == 'shop'){
			$rdata['give_coupon_list'] = \app\common\Coupon::getpaygive(aid,mid,$payorder['type'],$payorder['money'],$payorder['orderid']);
		}else{
			$rdata['give_coupon_list'] = \app\common\Coupon::getpaygive(aid,mid,$payorder['type'],$payorder['money'],$payorder['orderid']);
		}
        if(getcustom('invite_free')){
            $rdata['invite_free'] = '';
            $rdata['free_tmplids'] = '';
            $invite_free = Db::name('invite_free')->where('aid',aid)->where('status',1)->find();
            if($invite_free && $invite_free['gettj'] && $invite_free['start_time']<=time() && $invite_free['end_time']>=time()){
                $gettj = explode(',',$invite_free['gettj']);
                if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
                    $rdata['invite_free'] = '';
                }else{
                    $rdata['invite_free'] = $invite_free;
                    if(platform == 'wx'){
                        $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->field('tmpl_orderconfirm,tmpl_activity_notice')->find();
                        if($wx_tmplset['tmpl_orderconfirm']){
                            $free_tmplids[] = $wx_tmplset['tmpl_orderconfirm'];
                        }
                        if($wx_tmplset['tmpl_activity_notice']){
                            $free_tmplids[] = $wx_tmplset['tmpl_activity_notice'];
                        }
                        if($free_tmplids){
                            $rdata['free_tmplids'] = $free_tmplids;
                        }
                    }
                }
            }
        }
        if(getcustom('yuyue_before_starting')){
            //预约服务器通知
            if(platform == 'wx' && $payorder['type'] =='yuyue'){
                $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
                if($wx_tmplset['tmpl_yuyue_before_starting']){
                    $tmplids[] = $wx_tmplset['tmpl_yuyue_before_starting'];
                }
            }
        } 
        if(getcustom('yx_daily_lirun_choujiang')){
            if(platform == 'wx' && in_array($payorder['type'],['shop','maidan'])){
                $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
                if($wx_tmplset['tmpl_lirun_choujiang']){
                    $tmplids[] = $wx_tmplset['tmpl_lirun_choujiang'];
                }
            }
        }
		$rdata['tmplids'] = $tmplids;
		$rdata['detailurl'] = $detailurl;
		$rdata['tourl'] = $tourl;
		if($payorder['type'] == 'plug_businessqr_pay'){
			$rdata['wxpay'] = 0;$rdata['alipay'] = 0;
		}

		if(getcustom('restaurant') && $payorder['type'] == 'restaurant_shop') {
		    $sysset = Db::name('restaurant_shop_sysset')->where('aid', aid)->where('bid', $payorder['bid'])->find();
		    $paytype_rest = explode(',', $sysset['paytype']);

            $rdata['wxpay'] = $appinfo['wxpay']==1 && in_array('weixin', $paytype_rest) ? 1 : 0;
            $rdata['alipay'] = $appinfo['alipay']==1 && in_array('alipay', $paytype_rest) ? 1 : 0;
            $rdata['moneypay'] = $set['moneypay'] && in_array('money', $paytype_rest) && $payorder['type']!='recharge' ? 1 : 0;
            //2025-05-24 修改bug 要求移出
//            $rdata['cancod'] = in_array('cash', $paytype_rest) ? 1 : 0;//线下支付
//            $rdata['codtxt'] = '线下支付';
            if(getcustom('restaurant_cuxiao_not_yuepay')){
                $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
                if($order['cuxiao_ids']){
                    $cuxiao_ids =  explode(',',$order['cuxiao_ids']);
                    $cuxaiolist = Db::name('restaurant_cuxiao')->where('aid',$payorder['aid'])->where('id','in',$cuxiao_ids)->select()->toArray();
                    foreach($cuxaiolist as $cxk=>$cxv){
                        //促销设置了 不使用余额支付强制关闭
                        if($cxv['is_use_yuepay'] == 0){
                            $rdata['moneypay'] = 0;
                        }
                    }
                }
            }
        }
		if(getcustom('plug_zhangyuan')) $rdata['alipay'] = 2;

		//其他支付宝
		$rdata['more_alipay'] = 0;
		$rdata['more_alipay_data'] = [];
		if(getcustom('plug_more_alipay') && (platform == 'h5' || platform == 'mp' || platform == 'app')){
			$more_alipay_data = [];
			if(platform == 'mp'){
				$appinfo2 = \app\common\System::appinfo(aid,'h5');
				$rdata['alipay'] = $appinfo2['alipay']==1 ? 1 : 0;
				for($i=2;$i<=30;$i++){
					if($appinfo2['alipay'.$i] == 1){
						$more_alipay_data[] = ['typeid'=>300 + $i,'name'=>$appinfo2['alipayname'.$i]];
					}
				}
			}else{
				for($i=2;$i<=30;$i++){
					if($appinfo['alipay'.$i] == 1){
						$more_alipay_data[] = ['typeid'=>300 + $i,'name'=>$appinfo['alipayname'.$i]];
					}
				}
			}
			if($more_alipay_data){
				$rdata['more_alipay'] = 1;
				$rdata['more_alipay_data'] = $more_alipay_data;
			}
		}
        if(getcustom('article_reward')){
            if($payorder['type']=='article_reward'){
                $rdata['pay_transfer'] = 0;
                $rdata['cancod']       = 0;//线下支付 货到付款
                $rdata['codtxt']       = '';
            }
        }
        $rdata['daifu'] = 0;
        if(getcustom('pay_daifu')){
            if($set['pay_daifu']){
                $rdata['daifu']  = 1;
                $rdata['daifu_txt'] = '好友代付';
                $rdata['daifu_share'] = [

                ];
            }
        }
        if(getcustom('wxpay_member_level')){
            //微信支付使用角色
            if($set && $set['wxpay_gettj'] && $rdata['wxpay'] != 0){
                $gettj = explode(',',$set['wxpay_gettj']);
                if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
                    $rdata['wxpay'] = 0;
                }
            }
        }

        if(getcustom('paotui')){
        	if($payorder['type'] == 'paotui'){
        		$rdata['pay_transfer'] = 0;//转账
	            $rdata['cancod']       = 0;//线下支付 货到付款
	            $rdata['codtxt']       = '';
        	}
        }

        if(getcustom('yx_moneypay')){
        	if($payorder['type']=='shop' && $order['cuxiao_id']){
        		$cuxiao = Db::name('cuxiao')->where('id',$order['cuxiao_id'])->field('id,moneypay')->find();
        		if($cuxiao && !$cuxiao['moneypay']){
        			$rdata['moneypay'] = 0;
        		}
			}
        	if($payorder['type'] == 'collage' && $order['proid']){
        		$product = Db::name('collage_product')->where('id',$order['proid'])->field('id,moneypay')->find();
        		if($product && !$product['moneypay']){
        			$rdata['moneypay'] = 0;
        		}
			}
        }
        if(getcustom('collage_teampay')){
        	if($payorder['type'] == 'collage' && $order['proid'] && $order['buytype'] == 3){
        		$product = Db::name('collage_product')->where('id',$order['proid'])->field('id,teampay')->find();
        		//是否开启只发起者团长能使用余额支付，若是则其他参团者不能用余额支付
        		//是否单独开启
        		if($product && $product['teampay'] !=0){
        			if($product['teampay'] == -1){
        				$rdata['moneypay'] = 0;
        			}
        		}else{
        			$set = Db::name('collage_sysset')->where('aid',aid)->field('teampay')->find();
        			if($set){
        				if($set['teampay'] == 0){
        					$rdata['moneypay'] = 0;
        				}
        			}
        		}
			}
        }
        if(getcustom('sxpay_h5') ){
        	$rdata['alih5pay'] = true;
			if(platform == 'h5' && $rdata['alipay']==1){
				$rdata['alipay_type'] = $appinfo['alipay_type'];
				$alipayopenid = $this->member['alipayopenid'] && !empty($this->member['alipayopenid'])?$this->member['alipayopenid']:$this->member['alipayopenid_new'];
				$rdata['alipayopenid']      = $alipayopenid && !empty($alipayopenid)?$alipayopenid:'';//支付宝用id
				$rdata['ali_appid']   = '';//支付appid;
				if($appinfo['alipay_type'] == 3){
					$rdata['ali_appid']   = $appinfo['ali_appid'];
				}
			}
		}
		if(getcustom('money_dec')){
            //使用余额抵扣，则不显示余额支付
            if($rdata['moneypay'] == 1){
                if($payorder['type'] == 'shop' && $order['dec_money']>0){
                    $rdata['moneypay'] = 0;
                }
                if($payorder['type'] == 'shop_hb'){
                    $hblist = Db::name('shop_order')->where('aid',aid)->where('ordernum','like',$payorder['ordernum'].'%')->order('ordernum')->select()->toArray();
                    foreach($hblist as $hbv){
                        if($hbv['dec_money']>0){
                            $rdata['moneypay'] = 0;
                            break;
                        }
                    }
                }
                if(getcustom('maidan_money_dec')){
					if($payorder['type'] == 'maidan'){
                        $dec_money = Db::name('maidan_order')->where('id',$payorder['orderid'])->value('dec_money');
	                    if($dec_money>0) $rdata['moneypay'] = 0;
	                }
		        }
            }
        }
        if(getcustom('yuyue_money_dec')){
            //使用余额抵扣，则不显示余额支付
            if($rdata['moneypay'] == 1 && $payorder['type'] == 'yuyue'){
                $dec_money = Db::name('yuyue_order')->where('id',$payorder['orderid'])->value('dec_money');
                if($dec_money>0){
                    $rdata['moneypay'] = 0;
                }
            }
        }
        if(getcustom('hotel')){
            if($payorder['type'] == 'hotel'){
                if($order['use_money']>0){
                    $rdata['moneypay'] = 0;
                }
            }
        }
        if(getcustom('alipay_plugin_trade')){
            $rdata['alipayPlugin'] = 1;
        }

        if(getcustom('maidan_auto_reg')){
            if(!$this->member['id']){
                $rdata['moneypay'] = 0;
                $rdata['overdraft_moneypay'] = 0;
                $rdata['xiaofeipay'] = 0;
                $rdata['yuanbaopay'] = 0;
            }
        }
		$rdata['paypal'] = $appinfo['paypal']==1 ? 1 : 0;
        if(getcustom('pay_adapay')){
            $rdata['adapay_union'] = $appinfo['adapay_union']==1 ? 1 : 0;
        }
        if(getcustom('pay_share')) {
            //付款前分享 （支持公众号、小程序）
            if (platform == 'mp' || platform == 'wx') {
                if ($payorder['type'] == 'shop') {
                    $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                    $rdata['share_payment'] = 0;
                    $share = [];
                    if ($shopset['share_payment'] == 1) {
                        $rdata['share_payment'] = 1;
                        //查询商品
                        if (!isset($order['id'])) {
                            $order = Db::name($payorder['type'] . '_order')->where('id', $payorder['orderid'])->find();
                        }
                        $productid = Db::name('shop_order_goods')->where('orderid', $order['id'])->value('proid');
                        $product_share = Db::name('shop_product')->field('id,name,pic,sharetitle,sharepic,sharedesc')->where('id', $productid)->find();
                        if ($product_share) {
                            $share['id'] = $product_share['id'];
                            $share['sharetitle'] = $product_share['sharetitle'] != '' ? $product_share['sharetitle'] : $product_share['name'];
                            $share['sharepic'] = $product_share['sharepic'] != '' ? $product_share['sharepic'] : $product_share['pic'];
                            $share['sharedesc'] = $product_share['sharedesc'];
                        }
                    }
                    $rdata['share_product'] = $share;
                }
            }
        }
        if(getcustom('product_pingce')){
            $rdata['is_pingce'] = Db::name('shop_order')->where('id',$payorder['orderid'])->value('is_pingce');
        }
		$rdata['yuanbao_money']  = $yuanbao_money;
        $rdata['total_yuanbao']  = $total_yuanbao;
        $rdata['yuanbao_msg']    = $yuanbao_msg;
        $rdata['yuanbaopay']     = $yuanbaopay;
        if(getcustom('pay_money_combine')){
        	//是否开启余额和微信或支付组合支付 暂只支持 $payorder['type'] == 'shop'
        	if($payorder['type'] == 'shop'){
            	$rdata['iscombine'] = $set['iscombine']==1 ? 1 : 0;
            }
            if(getcustom('pay_money_combine_maidan')){
                if($payorder['type'] == 'maidan'){
                    $rdata['iscombine'] = $set['iscombine']==1 ? 1 : 0;
                }
            }
        }
        if(getcustom('member_level_moneypay_price')){
            if($payordertype == 'shop' || in_array($payorder['type'],['restaurant_shop','restaurant_shop_hb','restaurant_takeaway','restaurant_takeaway_hb'])){
                //是否余额支付、且会员价仅限余额支付
                $moneypay_lvprice_status = false;
                if($rdata['moneypay'] && $set['moneypay_lvprice_status'] == 1){
                    $moneypay_lvprice_status = true;
                }
                $rdata['moneypay_lvprice_status'] = $moneypay_lvprice_status;
            }
        }
        if(getcustom('bonus_pool_gold')){
            if($payorder['type']=='buy_gold'){
                $order = Db::name('buy_gold_order')->where('id',$payorder['orderid'])->find();
                if($order['select_paytype']!='money' && $order['select_paytype']!='freezemoney'){
                    $rdata['moneypay']  = 0;
                }elseif($order['select_paytype']=='money' || $order['select_paytype']=='freezemoney'){
                    $rdata['moneypay']  = 2;
                }
                if($order['select_paytype']=='xianjin'){
                    $rdata['xianjinpay']  = 1;
                    $rdata['moneypay']  = 0;
                }
            }
        }
        if(getcustom('pay_chinaums')){
            $wxappinfo = Db::name('admin_setapp_wx')->where('aid',aid)->find();
            if($wxappinfo['ysf_pay']){
                $rdata['yunshanfuwxpay'] = 1;
            }
        }

        $rdata['wx_liji_pay'] = true;
        if(getcustom('wxpay_native_h5')){
            $setapp_h5 = Db::name('admin_setapp_h5')->where('aid',aid)->field('wx_native_h5,wx_liji_pay')->find();
            if($setapp_h5['wx_native_h5'] == 1){
                $rdata['wxpay_native_h5'] = true;
            }
            if(!$setapp_h5['wx_liji_pay']){
                //是否开启微信立即支付按钮
                $rdata['wx_liji_pay'] = false;
            }
        }
        if(getcustom('shop_giveorder')){
            $rdata['usegiveorder']  = false;
            $rdata['giveordertitle']= '';
            $rdata['giveorderpic'] = '';
            if($payordertype == 'shop'){
                if($payorder['type'] != 'shop'){
                    $giveorder = Db::name('shop_order')->where('ordernum',$payorder['ordernum'])->where('aid',aid)->field('usegiveorder,giveordertitle,giveorderpic')->find();
                }else{
                    $giveorder = $order;
                }
                if($giveorder){
                    $rdata['usegiveorder']  = $giveorder['usegiveorder']?true:false;
                    $rdata['giveordertitle']= $giveorder['giveordertitle'];
                    $rdata['giveorderpic'] = $giveorder['giveorderpic'];
                }
            }
        }
        if(getcustom('pay_allinpay')){
            if($rdata['wxpay'] == 1 && (platform == 'wx' || platform == 'mp')){
                //通联支付 云商通 无感自动注册会员(暂时仅微信小程序、微信公众号)
               \app\custom\AllinpayYunst::autocreateuser(aid,mid,platform,'beforepay');
            }
        }
        if(getcustom('pay_huifu_quickpay')){
            $rdata['huifu_quickpay'] = $appinfo['huifu_quickpay_st']??0;
        }
        if(getcustom('pay_limit_paytype')){
          if($set['limit_money_score_pay'] ==1 && $payorder['type'] != 'recharge'){
              $rdata['wxpay'] = 0;
              $rdata['alipay'] = 0;
          } 
        }
        if(getcustom('sxpay_native') ){
            $rdata['sxpay_native'] = 0;//是否开启随行付二维码支付 0：不开启，1：开启二维码支付
            if(platform == 'mp' || platform == 'wx' || platform == 'alipay' || platform == 'app' || platform == 'h5'){
                if($appinfo['native_type'] == 1) $rdata['sxpay_native'] = 1;
            }
        }
        if(getcustom('pay_countdown')) {
            $rdata['countDown'] = $countDown;//倒计时
        }
        if(getcustom('extend_advertising')){
            //查询此时间段的广告
            $rdata['advertising'] = \app\model\ApiIndexs::dealadvertising([],$this->admin,0,platform);
        }
        if(getcustom('luckycollage_product_paytype')){
            //幸运拼团商品单独设置的支付类型
            if($payorder['type'] == 'lucky_collage'){
                $proid = Db::name('lucky_collage_order')->where('id',$payorder['orderid'])->value('proid');
                $product_paytype = Db::name('lucky_collage_product')->where('id',$proid)->value('paytype');
                if($product_paytype=='money'){
                    $rdata['moneypay'] = 1;
                    $rdata['wxpay'] = 0;
                    $rdata['alipay'] = 0;
                    $rdata['scorepay'] = 0;
                }
                if($product_paytype=='score'){
                    $rdata['moneypay'] = 0;
                    $rdata['wxpay'] = 0;
                    $rdata['alipay'] = 0;
                    $rdata['scorepay'] = 1;
                }
                if($product_paytype=='online'){
                    $rdata['moneypay'] = 0;
                    $rdata['wxpay'] = 1;
                    $rdata['alipay'] = 1;
                    $rdata['scorepay'] = 0;
                }
            }

        }
        if(getcustom('lock_money')){
            if($this->member['lock_money']==1){
                $rdata['moneypay'] = 0;
            }
        }
		return $this->json($rdata);
	}
	
	private function checkscore($payorder,$params=[]){
        $aid = aid;$mid = mid;
        $member= $this->member;
        $score = $this->member['score'];
        if(getcustom('scoreshop_otheradmin_buy')){
            if(($payorder['type']=='scoreshop' || $payorder['type']=='scoreshop_hb') && $params && $params['othermember']){
                $member= $params['othermember'];
                $aid   = $params['othermember']['aid'];
                $mid   = $params['othermember']['id'];
                $score = $params['othermember']['score'];
            }
        }
		if($payorder['bid']==0 && $payorder['type'] != 'shop_hb' && $payorder['type'] != 'scoreshop_hb' && $score < $payorder['score']){
			return ['status'=>0,'msg'=>t('积分').'不足'];
		}else{
			$business_selfscore = 0;
			if(getcustom('business_selfscore')){
				$bset = Db::name('business_sysset')->where('aid',$aid)->find();
				if($bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
					$business_selfscore = 1;
				}
			}
			if($business_selfscore == 0 && $score < $payorder['score']){
				return ['status'=>0,'msg'=>t('积分').'不足'];
			}
			if($business_selfscore == 1){
				if($payorder['bid']==0 && $payorder['type'] != 'shop_hb' && $payorder['type'] != 'scoreshop_hb'){
					if($score < $payorder['score']){
						return ['status'=>0,'msg'=>t('积分').'不足'];
					}
				}else{
					if($payorder['type'] != 'shop_hb' && $payorder['type'] != 'scoreshop_hb'){
						$memberscore = Db::name('business_memberscore')->where('aid',$aid)->where('bid',$payorder['bid'])->where('mid',$mid)->value('score');
						if(!$memberscore) $memberscore = 0;
						if($memberscore < $payorder['score']){
							return ['status'=>0,'msg'=>t('积分').'不足'];
						}
					}else{
						$subpayorderlist = Db::name('payorder')->where('aid',$aid)->where('type','shop')->where('ordernum','like',$payorder['ordernum'].'_%')->select()->toArray();
						foreach($subpayorderlist as $subpayorder){
							if($subpayorder['score'] == 0) continue;
							if($subpayorder['bid'] == 0 && $score < $subpayorder['score']){
								return ['status'=>0,'msg'=>t('积分').'不足'];
							}elseif($subpayorder['bid'] != 0){
								$memberscore = Db::name('business_memberscore')->where('aid',$aid)->where('bid',$subpayorder['bid'])->where('mid',$mid)->value('score');
								if(!$memberscore) $memberscore = 0;
								if($memberscore < $subpayorder['score']){
									return ['status'=>0,'msg'=>t('积分').'不足'];
								}
							}
						}
					}
				}
			}
		}
		return ['status'=>1];
	}
	private function decscore($payorder,$params=[]){
        $aid = aid;$mid = mid;
        $member= $this->member;
        $score = $this->member['score'];
        $preRemark = '支付订单,订单号：';
        $addscore_params = [];//其他参数
        if(getcustom('scoreshop_otheradmin_buy')){
            if(($payorder['type']=='scoreshop' || $payorder['type']=='scoreshop_hb') && $params && $params['othermember']){
                $member= $params['othermember'];
                $aid   = $params['othermember']['aid'];
                $mid   = $params['othermember']['id'];
                $score = $params['othermember']['score'];
                $preRemark = '支付'.$params['othermember']['appname'].'订单,订单号';
                $addscore_params['optaid'] = aid;
            }
        }
		if($payorder['bid']==0 && $payorder['type'] != 'shop_hb' && $payorder['type'] != 'scoreshop_hb'){
			\app\common\Member::addscore($aid,$mid,-$payorder['score'],$preRemark.$payorder['ordernum'],'',0,0,1,$addscore_params);
		}else{
			$business_selfscore = 0;
			if(getcustom('business_selfscore')){
				$bset = Db::name('business_sysset')->where('aid',$aid)->find();
				if($bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
					$business_selfscore = 1;
				}
			}
			if($business_selfscore == 0){
				\app\common\Member::addscore($aid,$mid,-$payorder['score'],$preRemark.$payorder['ordernum'],'',$payorder['bid'],0,1,$addscore_params);
				if(getcustom('business_selfscore') && $bset['business_selfscore'] == 1){
					\app\common\Business::addscore($aid,$payorder['bid'],$payorder['score'],t('用户').$member['nickname'].'花费'.t('积分'));
				}
			}else{
				if($payorder['type'] != 'shop_hb' && $payorder['type'] != 'scoreshop_hb'){
					\app\common\Business::addmemberscore($aid,$payorder['bid'],$mid,-$payorder['score'],$preRemark.$payorder['ordernum'],1);
				}else{
					$subpayorderlist = Db::name('payorder')->where('aid',$aid)->where('type','shop')->where('ordernum','like',$payorder['ordernum'].'_%')->select()->toArray();
					foreach($subpayorderlist as $subpayorder){
						if($subpayorder['score'] == 0) continue;
						if($subpayorder['bid'] == 0){
							\app\common\Member::addscore($aid,$mid,-$subpayorder['score'],$preRemark.$subpayorder['ordernum'],'',0,0,1,$addscore_params);
						}elseif($subpayorder['bid'] != 0){
							\app\common\Business::addmemberscore($aid,$subpayorder['bid'],$mid,-$subpayorder['score'],$preRemark.$subpayorder['ordernum'],1);
						}
					}
				}
			}
		}
	}

    private function payServiceFee($payorder){
        if($payorder['type'] == 'shop'){
            $check = Db::name('member_servicefee_log')->where('aid',aid)->where('mid',mid)->where('remark','支付订单,订单号: '.$payorder['ordernum'])->find();
            if(empty($check)){
                \app\common\Member::addServiceFee(aid,mid,-$payorder['service_fee_money'],'支付订单,订单号: '.$payorder['ordernum']);
            }
        }
    }
	public function transfer()
    {
        $money_recharge_transfer = getcustom('money_recharge_transfer');//余额充值转账汇款
        $transfer_order_parent_check = getcustom('transfer_order_parent_check');
        $maidan_orderadd_mobile_paytransfer = getcustom('maidan_orderadd_mobile_paytransfer');
        if(getcustom('pay_transfer')){
            //转账汇款
            $id = input('param.id/d');
            if($maidan_orderadd_mobile_paytransfer){
                $payorder = Db::name('payorder')->where('id',$id)->where('aid',aid)->find();
            }else{
                $payorder = Db::name('payorder')->where('id',$id)->where('aid',aid)->where('mid',mid)->find();
            }

            if(!$payorder){
                return $this->json(['status'=>0,'msg'=>'该订单不存在']);
            }
            $mid = $payorder['mid'];
            $transfer_typ_arr = ['shop'];//支持转账汇款的类型
            if($money_recharge_transfer){
                //余额充值
                $transfer_typ_arr[] = 'recharge';
            }
            if(getcustom('commission_xianjin_percent')){
                //现金充值
                $transfer_typ_arr[] = 'xianjin_recharge';
            }
            if($maidan_orderadd_mobile_paytransfer){
                $transfer_typ_arr[] = 'maidan';
            }
            if(!in_array($payorder['type'],$transfer_typ_arr) || $payorder['paytypeid'] != 5){
                return $this->json(['status'=>0,'msg'=>'订单不支持'.t('转账汇款')]);
            }

            $detail = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->where('aid',aid)->where('mid',$mid)->find();
            if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);

            $set = Db::name('admin_set')->where('aid',aid)->field('pay_transfer_check')->find();
            if($set && $set['pay_transfer_check'] == 1 && $detail['transfer_check'] == 0){
                //需要审核
                return $this->json(['status'=>0,'msg'=>'该订单付款正在审核中']);
            }
            $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
            if($payorder['type'] == 'recharge'){
                $gotourl = '/pagesExt/money/moneylog?st=1';
            }else if($payorder['type'] == 'xianjin_recharge'){
                $gotourl = '/pagesD/my/xianjinlog?st=1';
            }else if($maidan_orderadd_mobile_paytransfer && $payorder['type'] == 'maidan'){
                $gotourl = '/adminExt/order/maidanlog';
            }else {
                $gotourl = '/pages/my/usercenter';
            }

            $post = input('post.');
            if($post) {
                if($payorder['status']==1){
                    return $this->json(['status'=>0,'msg'=>'该订单已支付']);
                }
                if($payorder['check_status'] == 1) {
                    return $this->json(['status'=>0,'msg'=>'已审核通过，请勿重复提交']);
                }
                if(empty($post['pics'])) {
                    return $this->json(['status'=>0,'msg'=>'请上传付款凭证']);
                }
                $update['paypics'] = implode(',', $post['pics']);
                if(getcustom('pay_yuanbao') ){
                    $update['check_status'] = 1;
                    Db::name('payorder')->where('id',$id)->where('aid',aid)->where('mid',$mid)->update($update);
                    Db::name('shop_order')->where('id',$payorder['orderid'])->where('aid',aid)->where('mid',$mid)->update(['status'=>1,'paytime'=>time()]);
                    return $this->json(['status'=>1,'msg'=>'提交成功','gotourl' => $gotourl]);
                }else{
                    $update['check_status'] = 0;
                    Db::name('payorder')->where('id',$id)->where('aid',aid)->where('mid',$mid)->update($update);
                    return $this->json(['status'=>1,'msg'=>'提交成功，等待审核','gotourl' => $gotourl]);
                }
            }

            $payorder['paypics'] =  $payorder['paypics'] ? explode(',', $payorder['paypics']) : [];
            if($payorder['check_status'] === 0) {
                $payorder['check_status_label'] = '待审核';
            }elseif($payorder['check_status'] == 1) {
                $payorder['check_status_label'] = '通过';
            }elseif($payorder['check_status'] == 2) {
                $payorder['check_status_label'] = '驳回';
            }else{
                $payorder['check_status_label'] = '未上传';
            }

            if($transfer_order_parent_check){
                $payorder['transfer_order_parent_check'] = true;
            }
            if($money_recharge_transfer){
                $payorder['money_recharge_transfer'] = true;
            }
            if(getcustom('commission_xianjin_percent')){
                $payorder['money_recharge_transfer'] = true;
            }
            if($maidan_orderadd_mobile_paytransfer){
                $payorder['money_recharge_transfer'] = true;
            }

            $set = Db::name('admin_set')->where('aid',aid)->find();
            $pay_transfer = 1;
            $pay_transfer_info['pay_transfer_account_name'] = $pay_transfer ? $set['pay_transfer_account_name'] : '';
            $pay_transfer_info['pay_transfer_account'] = $pay_transfer ? $set['pay_transfer_account'] : '';
            $pay_transfer_info['pay_transfer_bank'] = $pay_transfer ? $set['pay_transfer_bank'] : '';
            $pay_transfer_info['pay_transfer_desc'] = $pay_transfer ? $set['pay_transfer_desc'] : '';
            $pay_transfer_info['pay_transfer_qrcode'] = $pay_transfer ? $set['pay_transfer_qrcode'] : '';
            $pay_transfer_info['pay_transfer_qrcode_arr'] = $set['pay_transfer_qrcode'] ? explode(',',$set['pay_transfer_qrcode']) : [];
            $rdata['pay_transfer_info'] = $pay_transfer_info;
            $rdata['status'] = 1;
            $rdata['detail'] = $payorder;
            $rdata['orderDetail'] = $detail;

            return $this->json($rdata);
        }
    }

	//云收银 获取sessionkey所需要的参数
	public function getYunMpauthParams(){
		$jscode = input('post.jscode');
		$wxapp = \app\common\System::appinfo(aid,'wx');
		//$url = 'https://showmoney.cn/scanpay/fixed/mpauth';
		$params = [];
		$params['busicd'] = 'WXAU';
		$params['chcd'] = 'WXP';
		$params['inscd'] = '92721888';
		$params['jsCode'] = $jscode;
		$params['mchntid'] = $wxapp['yun_mchntid'];
		$params['signType'] = 'SHA256';
		$params['subappid'] = $wxapp['appid'];
		$params['txndir'] = 'Q';
		$params['version'] = '2.3.9';

		ksort($params, SORT_STRING);
		$string1 = '';
		foreach ($params as $key => $v) {
			if (empty($v)) {
				continue;
			}
			$string1 .= "{$key}={$v}&";
		}
		$string1 = trim($string1,'&');
		$string1 .= $wxapp['yun_mchkey'];
		$params['sign'] = hash("sha256",$string1);
		return json(['status'=>1,'params'=>$params]);
	}
	//云收银 获取unified所需要的参数
	public function getYunUnifiedParams(){
		$orderid = input('post.orderid/d');
		$wxapp = \app\common\System::appinfo(aid,'wx');
		//$url = 'https://showmoney.cn/scanpay/unified';

		$newordernum = date('ymdHis').rand(100000,999999);
		Db::name('payorder')->where('aid',aid)->where('id',$orderid)->update(['ordernum'=>$newordernum]);
		$payorder = Db::name('payorder')->where('aid',aid)->where('id',$orderid)->find();

		if($payorder['type'] == 'shop_hb'){
			$orderlist = Db::name('shop_order')->where('aid',aid)->where('ordernum','like',$payorder['ordernum'].'%')->order('ordernum')->select()->toArray();
			foreach($orderlist as $k=>$order){
				Db::name('shop_order')->where('id',$order['id'])->update(['ordernum'=>$newordernum.'_'.$k]);
			}
		}elseif($payorder['type'] == 'scoreshop_hb'){
			$orderlist = Db::name('scoreshop_order')->where('aid',aid)->where('ordernum','like',$payorder['ordernum'].'%')->order('ordernum')->select()->toArray();
			foreach($orderlist as $k=>$order){
				Db::name('scoreshop_order')->where('id',$order['id'])->update(['ordernum'=>$newordernum.'_'.$k]);
			}
		}else{
			Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->update(['ordernum'=>$newordernum]);
		}

		$params = [];
		$params['backUrl'] = PRE_URL.'/notify.php';
		$params['busicd'] = 'WXMP';
		$params['charset'] = 'utf-8';
		$params['chcd'] = 'WXP';
		$params['inscd'] = '92721888';
		$params['mchntid'] = $wxapp['yun_mchntid'];
		$params['signType'] = 'SHA256';
		$params['subappid'] = $wxapp['appid'];
		$params['txndir'] = 'Q';
		$params['version'] = '2.3.9';
		$params['profitsharing'] = 'N';
		$params['sessionKey'] = input('param.sessionKey');
		$params['terminalType'] = 'miniProgPay';
		$params['subject'] = $payorder['title'];
		$params['orderNum'] = $payorder['ordernum'];
		$params['txamt'] = sprintf("%012d",$payorder['money']*100);

		ksort($params, SORT_STRING);
		$string1 = '';
		foreach ($params as $key => $v) {
			if (empty($v)) {
				continue;
			}
			$string1 .= "{$key}={$v}&";
		}
		$string1 = trim($string1,'&');
		$string1 .= $wxapp['yun_mchkey'];
		$params['sign'] = hash("sha256",$string1);
		return json(['status'=>1,'params'=>$params]);
	}
	//元宝 更新payorder支付参数
    private function yuanbao_up_pay($aid,$yuanbaopay,$orderid,$pay_type,$payorder){
        //元宝支付
        if(getcustom('pay_yuanbao') && $yuanbaopay){
            //如果支付类型是元宝 且是商城支付
            if($pay_type == 'yuanbao' && $payorder['type']=='shop'){
                //更新支pay付
                $up_pay = Db::name('payorder')->where('id',$orderid)->where('aid',$aid)->update(['is_yuanbao_pay'=>1,'yuanbao_money'=>$payorder['money']]);
            }else{
                $up_pay = Db::name('payorder')->where('id',$orderid)->where('aid',$aid)->update(['is_yuanbao_pay'=>0,'yuanbao_money'=>0]);
            }
        }
    }

    private function sendNotice($aid,$payorder,$order,$member)
    {
        $url_admin = 'admin/index/index';
        if($payorder['type'] == 'shop' || $payorder['type'] == 'balance'){
            $url_admin = 'admin/order/shoporder';
        }
        if($payorder['type'] == 'yuyue'){
            $detailurl = '/yuyue/yuyue/orderdetail?id='.$payorder['orderid'];
            $tourl = '/yuyue/yuyue/orderlist';
            $url_admin = 'admin/order/yuyueorder';
        }

        //公众号通知 订单支付成功
        $tmplcontent = [];
        if($order['paytypeid'] != 4) {
            $tmplcontent['first'] = '有新订单支付成功';
        } else {
            $tmplcontent['first'] = '有新订单下单成功（'.$order['paytype'].'）';
        }
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
        \app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url($url_admin, $aid),$order['mdid'],$tmplcontentNew);


        if($order['paytypeid'] != 4) {
            $tmplcontent['first'] = '恭喜您的订单已支付成功';
        } else {
            $tmplcontent['first'] = '恭喜您的订单已下单成功';
        }
        $rs = \app\common\Wechat::sendtmpl($aid,$member['id'],'tmpl_orderpay',$tmplcontent,m_url('pages/my/usercenter', $aid),$tmplcontentNew);

        $tmplcontent = [];
        $tmplcontent['thing11'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
        if($order['paytypeid'] != 4) {
            $tmplcontent['phrase10'] = '已支付';
        }else{
            $tmplcontent['phrase10'] = $order['paytype'];
        }
        $tmplcontent['amount13'] = $order['totalprice'].'元';
        $tmplcontent['thing27'] = $member['nickname'];
        \app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,$url_admin,$order['mdid']);
    }


    //代付
    public function daifu(){
	    if(getcustom('pay_daifu')) {
            $set = Db::name('admin_set')->where('aid', aid)->find();
            if (!$set['pay_daifu']) {
                return $this->json(['status' => 0, 'msg' => '该功能未开启']);
            }
            $orderid = input('param.orderid/d');
            $payorder = Db::name('payorder')->where('id', $orderid)->where('aid', aid)->find();
            if (!$payorder) {
                return $this->json(['status' => 0, 'msg' => '该订单不存在']);
            }
            if ($payorder['status'] == 1) {
                return $this->json(['status' => 0, 'msg' => '该订单已支付']);
            }
            $order = [];
            if(in_array($payorder['type'],[
            'shop','collage','cycle','kanjia','seckill','seckill2','scoreshop','restaurant_booking','restaurant_takeaway','choujiang','yuyue'
            ])){
                $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
                if($order['status']==4){
                    return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
                }elseif($order['status']!=0){
                    return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
                }
                if($payorder['type'] == 'yuyue'){
                    $product = Db::name('yuyue_product')->field('pdprehour,yynum')->where('id',$order['proid'])->find();
                    $yydate = explode('-',$order['yy_time']);
                    //开始时间
                    $begindate = $yydate[0];
                    if(strpos($begindate,'年') === false){
                        $begindate = date('Y').'年'.$begindate;
                    }
                    $begindate = preg_replace(['/年|月/','/日/'],['-',''],$begindate);
                    $date = date('Y-m-d H:i:s',strtotime(date('H:i',time())));
                    $begintime = strtotime($begindate);
                    if($begintime <= strtotime(date('H:i',time()))+$product['pdprehour']*60*60){
                        return $this->json(['status'=>0,'msg'=>'预约时间已过，请选择其他时间']);
                    }
                    //查看是否已经存在
                    $yycount= Db::name($payorder['type'].'_order')->where('aid',aid)->where('yy_time',$order['yy_time'])->where('proid',$order['proid'])->where('mid','<>',$order['mid'])->where('status','in','1,2')->count();
                    if($yycount>=$product['yynum']){
                        return $this->json(['status'=>0,'msg'=>'该段时间预约人数已满']);
                    }
                }
                if($order['discount_rand_money'] > 0){
                    $payorder['discountText'] = '随机立减'.$order['discount_rand_money'];
                }
                if(getcustom('douyin_groupbuy')){
                    //抖音团购券再次验证
                    if($order['isdygroupbuy']==1){
                        return $this->json(['status'=>0,'msg'=>'抖音团购券不支持代付']);
                    }
                }
            }

            if($payorder['type'] == 'restaurant_shop') {
                $order = Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->find();
                if($order['status']==4){
                    return $this->json(['status'=>0,'msg'=>'该订单已关闭']);
                }
            }else if ($payorder['type'] == 'collage') { //拼团
                $order = Db::name('collage_order')->where('id', $payorder['orderid'])->find();
                if ($order['buytype'] != 1) {
                    $team = Db::name('collage_order_team')->where('aid', aid)->where('id', $order['teamid'])->find();
                    if ($team['status'] == 2) {
                        return $this->json(['status' => 0, 'msg' => '该团已满员']);
                    }
                    if ($team['status'] == 3) {
                        return $this->json(['status' => 0, 'msg' => '该团已解散']);
                    }
                }
            } elseif ($payorder['type'] == 'lucky_collage') { //幸运拼团
                $order = Db::name('lucky_collage_order')->where('id', $payorder['orderid'])->find();
                if ($order['buytype'] != 1) {
                    $team = Db::name('lucky_collage_order_team')->where('aid', aid)->where('id', $order['teamid'])->find();
                    if ($team['status'] == 2) {
                        return $this->json(['status' => 0, 'msg' => '该团已满员']);
                    }
                    if ($team['status'] == 3) {
                        return $this->json(['status' => 0, 'msg' => '该团已解散']);
                    }
                }
            } else {
                //判断是否是合并订单
                $table = $payorder['type'];
                if(strpos($payorder['type'],'_hb')!==false){
                    $table = substr($payorder['type'],0,-3);
                    $order = Db::name($table.'_order')->where('id', $payorder['orderid'])->find();
	                if ($order['status'] == 4) {
	                    return $this->json(['status' => 0, 'msg' => '该订单已关闭']);
	                } elseif ($order['status'] != 0) {
	                    return $this->json(['status' => 0, 'msg' => '订单状态不符合']);
	                }
                }
            }

            if (input('param.op') == 'submit') {
                $post = input('param.');
                Db::name('payorder')->where('id', $orderid)->update(['platform' => platform]);
                if ($this->member['paypwd'] && $post['typeid'] == 1) { //余额支付 验证支付密码
                    if(!\app\common\Member::checkPayPwd($this->member,$post['paypwd'] )){
                        return $this->json(['status'=>0,'msg'=>'支付密码输入错误']);
                    }
                }
                if ($payorder['money'] <= 0 && $payorder['score'] <= 0) {
                    \app\model\Payorder::payorder($orderid, '无须付款', 1, '');
					//\app\common\notice\Notice::orderPay(aid,$payorder,mid,$this->member);
                    //$this->sendNotice(aid, $payorder, $order, $this->member);
                    return $this->json(['status' => 2, 'msg' => '付款成功']);
                }
				if ($payorder['score'] > 0) {
					$ckrs = $this->checkscore($payorder);
					if($ckrs['status'] == 0) return $this->json($ckrs);
                }
                //代付订单
                if ($payorder['mid'] != $this->mid) {
                    //重新创建订单
                    $newPayorder = $payorder;
                    $newPayorder['id'] = '';
                    $newPayorder['orderid'] = 0;//代付订单
                    $newPayorder['createtime'] = time();
                    $newPayorder['title'] = $payorder['title'].'(代付)';
                    $newPayorder['bid'] = $payorder['bid'];
                    $newPayorder['pid'] = $payorder['id'];
                    $newPayorder['mid'] = $this->mid;
                    $newPayorder['type'] = 'daifu';
                    $newPayorder['platform'] = platform;
                    $newPayorder['ordernum'] = \app\common\Common::generateOrderNo($payorder['aid']);
                    unset($newPayorder['discountText']);
                    $newpayorderid = Db::name('payorder')->insertGetId($newPayorder);
                    $newPayorder['id'] = $newpayorderid;
                    $payorder = $newPayorder;
                }
                if ($post['typeid'] == 1) { //余额支付
                    if ($payorder['type'] == 'recharge') return $this->json(['status' => 0, 'msg' => '不能用余额支付']);
                    if ($this->member['money'] < $payorder['money']) {
                        return $this->json(['status' => 0, 'msg' => t('余额') . '不足,请充值']);
                    }
                    if ($payorder['money'] > 0) {
                        //减去会员的余额
                        if ($payorder['type'] == 'maidan') {
                            \app\common\Member::addmoney(aid, mid, -$payorder['money'], $payorder['title'] . ',订单号: ' . $payorder['ordernum']);
                        } else {
                            \app\common\Member::addmoney(aid, mid, -$payorder['money'], '代付订单,订单号: ' . $payorder['ordernum']);
                        }
                    }
                    if ($payorder['score'] > 0) {
                        //减去会员的积分
                        $this->decscore($payorder);
                    }
                    \app\model\Payorder::payorder($orderid, t('余额') . '支付', 1, '');
                    return $this->json(['status' => 2, 'msg' => '付款成功']);
                } elseif ($post['typeid'] == 4) { //货到付款
                    if (getcustom('restaurant') && $payorder['type'] == 'restaurant_shop') {
                        $sysset = Db::name('restaurant_shop_sysset')->where('aid', aid)->where('bid', $payorder['bid'])->find();
                        $paytype_rest = explode(',', $sysset['paytype']);
                        $codtxt = '线下支付';
                        if (!in_array('cash', $paytype_rest)) return $this->json(['status' => 0, 'msg' => '不支持' . $codtxt]);
                    } else {
                        if ($payorder['type'] != 'shop') return $this->json(['status' => 0, 'msg' => '不支持该付款方式']);
                        $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                        $codtxt = $shopset['codtxt'];
                        if (!$shopset['cancod']) return $this->json(['status' => 0, 'msg' => '不支持' . $codtxt]);
                        if(getcustom('shop_cod_precondition')) {
                            //货到付款前置条件
                            if($shopset['cod_precondition']){
                                //查询他是否有未完成的货到付款
                                $count = Db::name('shop_order')->where('mid',mid)->where('paytypeid',4)->where('status','>=',1)->where('status','<=',2)->where('aid',aid)->count();
                                if($count) return $this->json(['status'=>0,'msg'=>'请先完成之前'.$codtxt.'订单']);
                            }
                        }
                        if(getcustom('shop_cod_level')) {
                            if(empty($shopset['cod_levelids'])) return $this->json(['status' => 0, 'msg' => '不支持' . $codtxt]);
                            //货到付款可使用等级
                            $cod_levelids = !empty($shopset['cod_levelids'])?explode(',',$shopset['cod_levelids']):[];
                            if(!in_array('-1',$cod_levelids) && !in_array($this->member['levelid'],$cod_levelids)){
                                 return $this->json(['status' => 0, 'msg' => $shopset['codtxt'].'暂不能使用，不符合货到付款使用等级']);
                            }
                        }
                    }

                    if ($payorder['score'] > 0) {
                        //减去会员的积分
                        $this->decscore($payorder);
                    }
                    \app\model\Payorder::payorder($orderid, $codtxt, 4, '');

                    if($payorder['type'] == 'shop'){
                        //有尾款支付金额，删除尾款payorder记录，重置付款
                        if($order['balance_pay_status'] == 0 && $order['balance_price']>0 && $order['balance_pay_orderid']){
                            Db::name('payorder')->where('id',$payorder['id'])->inc('money',$order['balance_price'])->update();
                            Db::name('payorder')->where('id',$order['balance_pay_orderid'])->where('orderid',$order['id'])->delete();
                            Db::name('shop_order')->where('id',$order['id'])->inc('totalprice',$order['balance_price'])->inc('product_price',$order['balance_price'])->update(['balance_price'=>0,'balance_pay_orderid'=>0]);
                        }
                    }
                    return $this->json(['status' => 2, 'msg' => '提交成功']);
                } elseif ($post['typeid'] == 5) {
                    if (getcustom('pay_transfer')) {
                        //转账汇款
                        if ($payorder['type'] != 'shop') return $this->json(['status' => 0, 'msg' => '不支持'.t('转账汇款')]);
                        $pay_transfer = Db::name('admin_set')->where('aid', aid)->value('pay_transfer');
                        if (!$pay_transfer) return $this->json(['status' => 0, 'msg' => '不支持该支付方式']);
                        if ($payorder['score'] > 0) {
                            //减去会员的积分
                            $this->decscore($payorder);
                        }
                        //转账后修改状态为已支付
                        $paytype = t('转账汇款');
                        $paytypeid = 5;
                //\app\model\Payorder::payorder($orderid,'转账汇款',5,'');
                        Db::name('payorder')->where('id', $orderid)->update(['paytype' => $paytype, 'paytypeid' => $paytypeid]);
                        Db::name('shop_order')->where('id', $payorder['orderid'])->update(['paytype' => $paytype, 'paytypeid' => $paytypeid, 'platform' => platform]);

                        return $this->json(['status' => 2, 'msg' => '提交成功']);
                    }
                } elseif ($post['typeid'] == 2) { //微信支付
                    $buildfun = 'build_' . platform;
                    Log::write('---------------wx_daifu------------------');
                    Log::write('mid='.mid);
                    Log::write('midp='.$payorder['mid']);
                    $rs = \app\common\Wxpay::$buildfun(aid, $payorder['bid'], mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type']);
                    Log::write($rs);
                    return $this->json($rs);
                } elseif ($post['typeid'] == 3) { //支付宝支付
                    $buildfun = 'build_' . platform;
                    $rs = \app\common\Alipay::$buildfun(aid, $payorder['bid'], mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type']);
                    return $this->json($rs);
                } elseif ($post['typeid'] == 11) { //百度小程序支付
                    $rs = \app\common\Baidupay::build(aid, mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type']);
                    return $this->json($rs);
                } elseif ($post['typeid'] == 12) { //头条小程序支付
                    $rs = \app\common\Ttpay::build(aid, mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type']);
                    return $this->json($rs);
                } elseif ($post['typeid'] == 22) { //云收银
                    $buildfun = 'build_' . platform;
                    $rs = \app\common\Yunpay::$buildfun(aid, mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type'], '', m_url('pagesExt/pay/pay?id=' . $payorder['id']));
                    return $this->json($rs);
                } elseif ($post['typeid'] == 23) {
                    $buildfun = 'build_h5';
                    $rs = \app\common\Qmpay::$buildfun(aid, mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type'], '', m_url('pagesExt/pay/pay?id=' . $payorder['id']));
                    return $this->json($rs);
                } elseif ($post['typeid'] == 24) {
                    $buildfun = 'build_h5_2';
                    $rs = \app\common\Qmpay::$buildfun(aid, mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type'], '', m_url('pagesExt/pay/pay?id=' . $payorder['id']));
                    return $this->json($rs);
                } elseif ($post['typeid'] == 41) { //月结
                    if(getcustom('pay_month')){
                        $paytypeid = 41;
                        if ($payorder['type'] != 'shop') return $this->json(['status' => 0, 'msg' => '不支持该付款方式']);
                        $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                        $codtxt = $shopset['pay_month_txt'];
                        if (!$shopset['pay_month']) return $this->json(['status' => 0, 'msg' => '不支持' . $codtxt]);

                        if ($payorder['score'] > 0) {
                            //减去会员的积分
                            $this->decscore($payorder);
                        }
                        \app\model\Payorder::payorder($orderid, $codtxt, $paytypeid, '');
                        return $this->json(['status' => 2, 'msg' => '提交成功']);
                    }
                }
                if (getcustom('plug_more_alipay') && platform == 'h5') {
                    if ($post['typeid'] == 31 || $post['typeid'] == 32) { //支付宝支付
                        if ($post['typeid'] == 31) {
                            $more = 2;
                        }
                        if ($post['typeid'] == 32) {
                            $more = 3;
                        }

                        $buildfun = 'build_' . platform;
                        $rs = \app\common\Alipay::$buildfun(aid, $payorder['bid'], mid, '代付订单', $payorder['ordernum'], $payorder['money'], $payorder['type'], '', '', $more);
                        return $this->json($rs);
                    }
                }
            }
            $orderMember = Db::name('member')->where('aid', aid)->where('id', $payorder['mid'])->field('id,nickname,headimg')->find();
            $orderGoods = [];
            if (in_array($payorder['type'], ['shop', 'restaurant_takeaway', 'restaurant_shop'])) {
                $orderGoods = Db::name($payorder['type'] . '_order_goods')->where('aid', aid)->where('orderid', $payorder['orderid'])->field('bid,name,ggname,num,totalprice,pic,real_totalprice')->select()->toArray();
            }elseif($payorder['type'] == 'xixie'){
                $orderGoods = Db::name($payorder['type'] . '_order_goods')->where('aid', aid)->where('orderid', $payorder['orderid'])->field('bid,name,"" ggname,num,totalprice,pic,real_totalprice')->select()->toArray();
            }elseif($payorder['type'] == 'scoreshop'){
                //无real_totalprice,ggname
                $orderGoods = Db::name('scoreshop_order_goods')->where('aid', aid)->where('orderid', $payorder['orderid'])->field('0 bid,name,"" ggname,num,totalscore totalprice,pic,0 real_totalprice')->select()->toArray();
            }elseif($payorder['type'] == 'restaurant_booking'){
                //无real_totalprice
                $orderGoods = Db::name('restaurant_booking_order_goods')->where('aid', aid)->where('orderid', $payorder['orderid'])->field('bid,name,"" ggname,num,totalprice,pic,0 real_totalprice')->select()->toArray();
            }elseif ($payorder['type'] == 'cycle') {
                $orderGoods = Db::name('cycle_order_stage')->where('aid', aid)->where('orderid', $payorder['orderid'])->field('bid,proname as name,ggname,num,sell_price as totalprice,propic as pic,0 real_totalprice')->select()->toArray();
            }elseif ($payorder['type'] == 'shop_hb') {
                $orderGoods = Db::name('shop_order_goods')->where('aid', aid)->where('ordernum', 'like', $payorder['ordernum'] . '_%')->field('bid,name,ggname,num,totalprice,pic,real_totalprice')->select()->toArray();
            }elseif ($payorder['type'] == 'scoreshop_hb') {
                $orderGoods = Db::name('scoreshop_order_goods')->where('aid', aid)->where('ordernum', 'like', $payorder['ordernum'] . '_%')->field('bid,name,ggname,num,totalprice,pic,0 real_totalprice')->select()->toArray();
            } elseif ($payorder['type'] == 'restaurant_takeaway_hb') {
                $orderGoods = Db::name('restaurant_takeaway_order_goods')->where('aid', aid)->where('ordernum', 'like', $payorder['ordernum'] . '_%')->field('bid,name,ggname,num,totalprice,pic,real_totalprice')->select()->toArray();
            } elseif ($payorder['type'] != 'balance' && $payorder['type'] != 'yuyue_balance' && $payorder['type'] != 'yuyue_addmoney') {
                $orderGoods = [];
            } else {
                if ($order) {
                    $orderGoods[] = [
                        'bid' => $order['bid'],
                        'name' => $order['title'] ?? $order['proname'],
                        'ggname' => '',
                        'num' => $order['num'] ?? 1,
                        'totalprice' => ($order['totalprice'] ?? $order['money']) ?? 0,
                        'real_totalprice' => $order['product_price']??0,
                        'pic' => $order['propic'] ?? '',
                    ];
                }
            }
            //订单信息按商家分组
            $newGoodsTemp = [];
            foreach ($orderGoods as $k=>$v){
                if($v['bid']){
                    $business = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->field('id,name,logo')->find();
                    $v['bname'] = $business['name']??'';
                }else{
                    $v['bname'] = $set['name'];
                }
                $newGoodsTemp[$v['bid']][] = $v;
            }
            $newGoods = [];
            foreach ($newGoodsTemp as $bid=>$goods){
                $newGoods[] = [
                    'bid'=>$goods[0]['bid'],
                    'bname'=>$goods[0]['bname'],
                    'goodslist'=>$goods,
                ];
            }

            $userinfo = [];
            $userinfo['money'] = $this->member['money'];
            $userinfo['score'] = $this->member['score'];
            if ($this->member['paypwd'] == '') {
                $userinfo['haspwd'] = 0;
            } else {
                $userinfo['haspwd'] = 1;
            }
            $userinfo['yuanbao'] = 0;
            if (getcustom('pay_yuanbao')) {
                $userinfo['yuanbao'] = $this->member['yuanbao'];
            }
            //订阅消息
            $tmplids = [];
            if (platform == 'wx' && in_array($payorder['type'], ['shop', 'collage', 'scoreshop', 'kanjia', 'seckill'])) {
                $wx_tmplset = Db::name('wx_tmplset')->where('aid', aid)->find();
                if ($wx_tmplset['tmpl_orderfahuo_new']) {
                    $tmplids[] = $wx_tmplset['tmpl_orderfahuo_new'];
                } elseif ($wx_tmplset['tmpl_orderfahuo']) {
                    $tmplids[] = $wx_tmplset['tmpl_orderfahuo'];
                }
                if ($payorder['type'] == 'collage') {
                    if ($wx_tmplset['tmpl_collagesuccess_new']) {
                        $tmplids[] = $wx_tmplset['tmpl_collagesuccess_new'];
                    } elseif ($wx_tmplset['tmpl_collagesuccess']) {
                        $tmplids[] = $wx_tmplset['tmpl_collagesuccess'];
                    }
                }
            }

            $pay_transfer = 0;
            if ($payorder['type'] == 'shop') {
                $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                if (getcustom('pay_transfer')) {
                    $pay_transfer = $set['pay_transfer'];
                }
                \think\facade\Log::write(input('param.scene'));
                if (in_array(input('param.scene'), [1175, 1176, 1177, 1191, 1195, 1216, 10001])) {
                    $order = Db::name('shop_order')->where('id', $payorder['orderid'])->find();
                    if ($order['fromwxvideo'] == 0) {
                        Db::name('shop_order')->where('id', $payorder['orderid'])->update(['fromwxvideo' => 1, 'scene' => input('param.scene')]);
                    }
                }
            }
            //\think\facade\Log::write($payorder);

            $detailurl = '';
            $tourl = '/pages/my/usercenter';
            if ($payorder['type'] == 'shop' || $payorder['type'] == 'balance') {
                $detailurl = '/pagesExt/order/detail?id=' . $payorder['orderid'];
            }
            if ($payorder['type'] == 'collage') {
                $detailurl = '/activity/collage/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/collage/orderlist';
            }
            if ($payorder['type'] == 'kanjia') {
                $detailurl = '/activity/kanjia/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/kanjia/orderlist';
            }
            if ($payorder['type'] == 'seckill') {
                $detailurl = '/activity/seckill/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/seckill/orderlist';
            }
            if ($payorder['type'] == 'scoreshop') {
                $detailurl = '/activity/scoreshop/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/scoreshop/orderlist';
            }
            if ($payorder['type'] == 'designerpage') {
                $order = Db::name('designerpage_order')->where('id', $payorder['orderid'])->find();
                $tourl = '/pages/index/main?id=' . $order['pageid'];
            }
            if ($payorder['type'] == 'restaurant_shop') {
                $detailurl = '/restaurant/shop/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/restaurant/shop/orderlist';
            }
            if ($payorder['type'] == 'restaurant_takeaway') {
                $detailurl = '/restaurant/takeaway/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/restaurant/takeaway/orderlist';
            }
            if ($payorder['type'] == 'restaurant_booking') {
                $detailurl = '/restaurant/booking/detail?id=' . $payorder['orderid'];
                $tourl = '/restaurant/booking/orderlist';
            }
            if ($payorder['type'] == 'seckill2') {
                $detailurl = '/activity/seckill2/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/seckill2/orderlist';
            }
            if ($payorder['type'] == 'yuyue') {
                $detailurl = '/yuyue/yuyue/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/yuyue/yuyue/orderlist';
            }
            if ($payorder['type'] == 'kecheng') {
                $kcorder = Db::name('kecheng_order')->where('id', $payorder['orderid'])->find();
                $detailurl = '/activity/kecheng/product?id=' . $kcorder['kcid'];
                $tourl = '/activity/kecheng/product?id=' . $kcorder['kcid'];
                if(getcustom('kecheng_lecturer')){
                    $kecheng = Db::name('kecheng_list')->where('id',$kcorder['kcid'])->field('chaptertype')->find();
                    if($kecheng && $kecheng['chaptertype'] == 1){
                        $tourl = '/pagesB/kecheng/lecturermldetail?kcid='.$kcorder['kcid'];
                    }
                }
            }
            if ($payorder['type'] == 'tuangou') {
                $detailurl = '/activity/tuangou/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/tuangou/orderlist';
            }
            if ($payorder['type'] == 'lucky_collage') {
                $detailurl = '/activity/luckycollage/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/activity/luckycollage/orderlist';
            }
            if ($payorder['type'] == 'workorder') {
                $detailurl = '/pagesB/workorder/detail?id=' . $payorder['orderid'];
                $tourl = '/pagesB/workorder/record';
            }
            if ($payorder['type'] == 'business_recharge') {
                $detailurl = '/admin/index/index';
                $tourl = '/admin/index/index';
            }
            if ($payorder['type'] == 'maidan') {
                $tourl = '/pagesB/maidan/maidanlog';
            }
            if ($payorder['type'] == 'yuyue_workerapply') {
                $yuyueset = Db::name('yuyue_set')->field('apply_url')->where('aid', aid)->find();
                $tourl = $yuyueset['apply_url'] ? $yuyueset['apply_url'] : '/yuyue/yuyue/apply';
            }
            if (getcustom('xixie')) {
                if ($payorder['type'] == 'xixie') {
                    $detailurl = '/pagesExt/xixie/orderdetail?id=' . $payorder['orderid'];
                }
            }
            if ($payorder['type'] == 'yueke') {
                $detailurl = '/pagesExt/yueke/orderdetail?id=' . $payorder['orderid'];
                $tourl = '/pagesExt/yueke/orderlist';
            }
            $appinfo = \app\common\System::appinfo(aid, platform);
            if ($payorder['type'] == 'shop') {
                if (getcustom('payaftertourl')) {
                    $product = Db::name('shop_order_goods')->alias('og')->where('og.orderid', $order['id'])->where('product.payaftertourl', '<>', '')->where('product.payaftertourl', 'not null')->join('shop_product product', 'og.proid=product.id')->find();
                    $payaftertourl = $product['payaftertourl'];
                    $payorder['payafterbtntext'] = $product['payafterbtntext'];
                    if ($payaftertourl) {
                        if (strpos($payaftertourl, 'miniProgram::') === 0) {
                            if (platform == 'mp') {
                                $afterurl = explode('|', str_replace('miniProgram::', '', $payaftertourl));
                                $payorder['payafter_username'] = $afterurl[2];
                                $payorder['payafter_path'] = $afterurl[1] . (strpos($afterurl[1], '?') !== false ? '&' : '?') . 'appid=' . $appinfo['appid'] . '&uid=' . mid . '&ordernum=' . $payorder['ordernum'];
                                \think\facade\Log::write($payorder['payafter_path']);
                            } else {
                                $afterurl = explode('|', $payaftertourl);
                                $payaftertourl = $afterurl[0] . '|' . $afterurl[1];
                                $payaftertourl = $payaftertourl . (strpos($payaftertourl, '?') !== false ? '&' : '?') . 'appid=' . $appinfo['appid'] . '&uid=' . mid . '&ordernum=' . $payorder['ordernum'] . '|' . $detailurl;
                            }
                        }
                        $tourl = $payaftertourl;
                    }
                }
            }

            if(getcustom('system_admin_payaftertourl_set')){
                //平台统一设置的支付跳转
                $payaftertourlSet = Db::name('sysset')->where('name','payaftertourl_set')->value('value');
                $payaftertourlSet = $payaftertourlSet && !empty($payaftertourlSet)? json_decode($payaftertourlSet,true):[];
                //所有支付后台跳转、商城商品、营销-支付后赠送、买单支付跳转、
                $payaftertourl_allurl = $payaftertourl_shopproducturl = $payaftertourl_payaftergiveurl = $payaftertourl_maidanurl = '';
                if($payaftertourlSet && $payaftertourlSet['status'] && $payaftertourlSet['status'] ==1 ){
                    if($payaftertourlSet['allurl'] && !empty($payaftertourlSet['allurl'])) $payaftertourl_allurl = $payaftertourlSet['allurl'];
                    if($payaftertourlSet['shopproducturl'] && !empty($payaftertourlSet['shopproducturl'])) $payaftertourl_shopproducturl = $payaftertourlSet['shopproducturl'];
                    if($payaftertourlSet['payaftergiveurl'] && !empty($payaftertourlSet['payaftergiveurl'])) $payaftertourl_payaftergiveurl = $payaftertourlSet['payaftergiveurl'];
                    if($payaftertourlSet['maidanurl'] && !empty($payaftertourlSet['maidanurl'])) $payaftertourl_maidanurl = $payaftertourlSet['maidanurl'];
                }
                //平台统一设置的支付跳转 所有支付跳转
                if($payaftertourl_allurl) $tourl = $payaftertourl_allurl;
                //平台统一设置的支付跳转 商城商品
                if($payaftertourl_shopproducturl && ($payorder['type'] == 'shop' || $payorder['type'] == 'shop_hb' || $payorder['type'] == 'shop_fenqi')) $tourl = $payaftertourl_shopproducturl;
                //平台统一设置的支付跳转 买单支付
                if($payaftertourl_maidanurl && ($payorder['type'] == 'maidan' || $payorder['type'] == 'maidan_new')) $tourl = $payaftertourl_maidanurl;
            }

            if (input('param.tourl')) $tourl = input('param.tourl');
            $rdata = [];
            $rdata['payorder'] = $payorder;
            $rdata['wxpay'] = $appinfo['wxpay'] == 1 ? 1 : 0;
            $rdata['wxpay_type'] = $appinfo['wxpay_type'];
            $rdata['alipay'] = $appinfo['alipay'] == 1 ? 1 : 0;
            $rdata['baidupay'] = $appinfo['baidupay'] == 1 ? 1 : 0;
            $rdata['toutiaopay'] = $appinfo['toutiaopay'] == 1 ? 1 : 0;
            $rdata['moneypay'] = $set['moneypay'] && $payorder['type'] != 'recharge' ? 1 : 0;

            if (getcustom('business_moneypay')) { //多商户设置的是否可以用余额支付
                if ($payorder['bid'] != 0) {
                    $moneypayset = Db::name('business_sysset')->where('aid', aid)->value('moneypay');
                    if ($moneypayset == 1) $rdata['moneypay'] = 1;
                    if ($moneypayset == 2) $rdata['moneypay'] = 0;
                }
            }
            if (getcustom('product_moneypay') && $payorder['type'] == 'shop') { //商品中设置的是否可以用余额支付
                $hasnomoneypay = Db::name('shop_order_goods')->alias('og')->where('og.orderid',$order['id'])->join('shop_product product','og.proid=product.id')->column('product.product_moneypay');
                //product_moneypay 余额支付状态 0关闭 1开启 2仅限余额
                if(in_array(0,$hasnomoneypay)){
                    //有一个关闭 就不能用余额支付
                    $rdata['moneypay'] = 0;
                }else{
                    //有一个仅限余额 就只能仅限余额支付
                    if(in_array(2,$hasnomoneypay)){
                        $rdata['moneypay'] = 2;
                    }else{
                        $rdata['moneypay'] = 1;
                    }
                }
            }

            $rdata['pay_transfer'] = $pay_transfer;
            $rdata['userinfo'] = $userinfo;
            $rdata['cancod'] = $shopset['cancod'];//货到付款
            if($payorder['type'] == 'shop'){
                if(getcustom('shop_cod_precondition')) {
                    //货到付款前置条件
                    if($rdata['cancod'] && $shopset['cod_precondition']){
                        //查询他是否有未完成的货到付款
                        $count = Db::name('shop_order')->where('mid',mid)->where('paytypeid',4)->where('status','>=',1)->where('status','<=',2)->where('aid',aid)->count();
                        if($count) $rdata['cancod'] = $shopset['cancod'] = 0;
                    }
                }
                if(getcustom('shop_cod_level')) {
                    //货到付款可使用等级
                    if($rdata['cancod']){
                        if(empty($shopset['cod_levelids'])) $rdata['cancod'] = $shopset['cancod'] = 0;
                        $cod_levelids = !empty($shopset['cod_levelids'])?explode(',',$shopset['cod_levelids']):[];
                        if(!in_array('-1',$cod_levelids) && !in_array($this->member['levelid'],$cod_levelids)) $rdata['cancod'] = $shopset['cancod'] = 0;
                    }
                }
            }

            if(getcustom('shop_cod_onlinepay')){
                if($payorder['status'] != 0){
                    $rdata['cancod'] = $shopset['cancod'] = 0;
                }
            }

            $rdata['codtxt'] = $shopset['codtxt'];
            $rdata['pay_month'] = $shopset['pay_month'] && getcustom('pay_month') ? 1 : 0;//月结
            $rdata['pay_month_txt'] = $shopset['pay_month_txt'];
            if ($payorder['type'] == 'shop') {
                $rdata['give_coupon_list'] = \app\common\Coupon::getpaygive(aid, mid, $payorder['type'], $payorder['money'], $payorder['orderid']);
            } else {
                $rdata['give_coupon_list'] = \app\common\Coupon::getpaygive(aid, mid, $payorder['type'], $payorder['money']);
            }
            $rdata['tmplids'] = $tmplids;
            $rdata['detailurl'] = $detailurl;
            $rdata['tourl'] = $tourl;
            if ($payorder['type'] == 'plug_businessqr_pay') {
                $rdata['wxpay'] = 0;
                $rdata['alipay'] = 0;
            }

            if (getcustom('restaurant') && $payorder['type'] == 'restaurant_shop') {
                $sysset = Db::name('restaurant_shop_sysset')->where('aid', aid)->where('bid', $payorder['bid'])->find();
                $paytype_rest = explode(',', $sysset['paytype']);

                $rdata['wxpay'] = $appinfo['wxpay'] == 1 && in_array('weixin', $paytype_rest) ? 1 : 0;
                $rdata['alipay'] = $appinfo['alipay'] == 1 && in_array('alipay', $paytype_rest) ? 1 : 0;
                $rdata['moneypay'] = $set['moneypay'] && in_array('money', $paytype_rest) && $payorder['type'] != 'recharge' ? 1 : 0;
                $rdata['cancod'] = in_array('cash', $paytype_rest) ? 1 : 0;//线下支付
                $rdata['codtxt'] = '线下支付';
            }
            if (getcustom('plug_zhangyuan')) $rdata['alipay'] = 2;

            //其他支付宝
            $rdata['more_alipay'] = 0;
            if (getcustom('plug_more_alipay') && platform == 'h5') {
                $rdata['more_alipay'] = 1;
                $rdata['alipay2'] = $appinfo['alipay2'] == 1 ? 1 : 0;
                $rdata['alipay3'] = $appinfo['alipay3'] == 1 ? 1 : 0;
            }
            $orderMember['platform_logo'] = $set['logo'];
            $rdata['daifu'] = 0;
            $rdata['daifu_txt'] = t('好友代付');
            $rdata['daifu_desc'] = $set['pay_daifu_desc']?addslashes($set['pay_daifu_desc']):'';
            $rdata['order_member'] = $orderMember;
            $rdata['order_goods'] = $newGoods;
            return $this->json($rdata);
        }
    }

	public function paypalRedirect(){
		$rs = \app\custom\PayPal::payRedirect();
		return $this->json($rs);
	}

	//关闭webview 跳转回到支付页面
	public function webviewjump(){

		$paramsdata = input('param.');
		foreach($paramsdata as $key => $val){
			$val = str_replace('=','-',$val);
			$params[] = "$key=$val";
		}
		$paramstr = implode("&",$params);

		echo '<!DOCTYPE html><html>'."\r\n";
		echo '<head>'."\r\n";
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\r\n";
		echo '<meta name="viewport" content="width=device-width,minimum-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no" />'."\r\n";
		echo '<meta name="robots" content="index, follow" />'."\r\n";
		echo '<title>信息提示</title>'."\r\n";
		if($paramsdata['fromplat'] == 'wx'){
			echo '<script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>'."\r\n";
		}else{
			echo '<script type="text/javascript" src="/static/js/uni.webview.1.5.4.js"></script>'."\r\n";
		}
		echo '</head>'."\r\n";
		echo '<body>'."\r\n";
		echo '<h2 style="text-align:center;padding-top:100px">支付跳转中...</h2>'."\r\n";
		if($paramsdata['fromplat'] == 'wx'){
			echo '<script>';
			//echo 'function gotopay(){';
			echo '	wx.miniProgram.redirectTo({ url:"/pagesExt/pay/pay?'.$paramstr.'"});';
			//echo '}';
			echo '</script>';
		}else{
			echo '<script>'."\r\n";
			echo 'document.addEventListener(\'UniAppJSBridgeReady\', function() {'."\r\n";
			echo '	uni.redirectTo({'."\r\n";
			echo '	  url:"/pagesExt/pay/pay?'.$paramstr.'"'."\r\n";
			echo '	});'."\r\n";
			echo '});'."\r\n";
			echo '</script>'."\r\n";
		}
		echo '</body></html>';
		die;
	}

	public static function deal_money_combine($aid,$mid,$member,$typeid,$combines,$payorder,$set){
        if(getcustom('pay_money_combine')){
            //余额组合支付
            if($combines && $combines['moneypay'] == 1){
                $combinestatus = false;
                if($payorder['type'] == 'shop'){
                    $combinestatus = true;
                }
                if(getcustom('pay_money_combine_maidan')){
                    if($payorder['type'] == 'maidan'){
                        $combinestatus = true;
                    }
                }
                //是否开启余额和微信或支付组合支付
                if($combinestatus){
                    if($set['iscombine'] != 1){
                        return ['status'=>0,'msg'=>'系统暂未开启'.t('余额').'组合支付'];
                    }
                    //可扣除多少
                    if($member['money']>0){
                        $cha = $member['money']-$payorder['money'];
                        //扣除全部，直接是余额支付
                        if($cha>=0){
                            //减去会员的余额
                            $res = \app\common\Member::addmoney($aid,$mid,-$payorder['money'],'支付订单,订单号: '.$payorder['ordernum']);
                            if($res['status'] == 1){
                                $res = \app\model\Payorder::payorder($payorder['id'],t('余额').'支付',1,'');

                                Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->update(['combine_money'=>0,'combine_wxpay'=>0,'combine_alipay'=>0]);
                                return ['status'=>2,'msg'=>'付款成功'];
                            }else{
                                return ['status'=>0,'msg'=>$res['msg']?$res['msg']:t('余额').'支付出错，请重试'];
                            }
                        //扣除余额部分 剩余部分微信支付
                        }else{
                            //减去会员的余额
                            $res = \app\common\Member::addmoney($aid,$mid,-$member['money'],'支付订单,订单号: '.$payorder['ordernum']);
                            if($res['status'] != 1){
                                return ['status'=>0,'msg'=>$res['msg']?$res['msg']:t('余额').'支付出错，请重试'];
                            }
                            //微信或支付宝支付的部分
                            $payorder['money'] = $payorder['money']-$member['money'];
                            $payorder['money'] = round($payorder['money'],2);
                            //修改支付表
                            Db::name('payorder')->where('id',$payorder['id'])->update(['money'=>$payorder['money']]);

                            //修改订单实际支付金额和组合支付
                            $updata = [];
                            if($typeid == 2){
                                $updata['combine_wxpay']  = $payorder['money'];
                                $updata['combine_alipay'] = 0;
                            }else if($typeid == 3 || ($typeid>=302 && $typeid<=330)){
                                $updata['combine_wxpay']  = 0;
                                $updata['combine_alipay'] = $payorder['money'];
                            }
                            Db::name($payorder['type'].'_order')->where('id',$payorder['orderid'])->inc('combine_money',$member['money'])->update($updata);
                        }
                    }
                }else{
                    return ['status'=>0,'msg'=>'仅商城商品支持'.t('余额').'组合支付'];
                }
            }
            return ['status'=>1,'money'=>$payorder['money']];
        }
    }

    public function getOpenid(){
	    if(getcustom('sxpay_apptowx')){
            $member_info = Db::name('member')->where('id',mid)->find();
            $openid = '';
            if(platform=='wx'){
                $openid = $member_info['wxopenid'];
            }else{
                $openid = $member_info['alipayopenid'];
            }
            return $this->json(['status'=>0,'openid'=>$openid]);
        }

    }
    public function sxfpay_app(){
        if(getcustom('sxpay_apptowx')) {
            $orderid = input('param.orderid/d');
            $payorder = Db::name('payorder')->where('id', $orderid)->where('aid', aid)->find();

            $aid = aid;
            $notify_url = PRE_URL . '/notify.php';

            if (platform == 'wx') {
                $rs = \app\custom\Sxpay::build_wx($aid, $payorder['bid'], mid, $payorder['title'], $payorder['ordernum'], $payorder['money'], $payorder['type'], '', 0, 'app');
            } else {
                $rs = \app\custom\Sxpay::build_alipay($aid, $payorder['bid'], mid, $payorder['title'], $payorder['ordernum'], $payorder['money'], $payorder['type'], '', 'app');
            }
            return $this->json($rs);
        }
    }

    /**
     * 付款前分享：分享成功后修改分享订单状态
     */
    public function sharePaymentStatus(){
        if(getcustom('pay_share')) {
            $orderid = input('param.orderid/d');
            $payorder = Db::name('payorder')->where('id', $orderid)->where('aid', aid)->find();
            if (!$payorder) {
                return $this->json(['status' => 0, 'msg' => '该订单不存在']);
            }
            $res = Db::name('payorder')->where('id', $orderid)->update(['share_payment_status' => 1]);
            if ($res) {
                return $this->json(['status' => 1, 'msg' => 'ok']);
            }
            return $this->json(['status' => 0, 'msg' => 'error']);
        }
    }

    public function cancelpay(){
        $orderid = input('param.orderid/d');
        $payorder = Db::name('payorder')->where('id',$orderid)->where('aid',aid)->find();
        if(!$payorder){
            return $this->json(['status'=>0,'msg'=>'该订单不存在']);
        }
        //如果不是买单，其他bid都是0
        $bid = 0;
        if($payorder['type'] == 'maidan'){
            $bid = $payorder['bid'];
        }
        if(getcustom('sound')){
            \app\common\Sound::playmsg(aid,$bid,'cancelpay');
        }
    }

    //计算赠送积分
    public function getGiveScore($payorder,$set){
        if(getcustom('shop_alone_give_score')){
            $givescore = 0;
            $payMoney = Db::name('maidan_order')->where('id',$payorder['orderid'])->value('money');
            if($payMoney <= 0 ){
                return $givescore;
            }
            return $set['scorein_score'] * ($payorder['money'] / $payMoney);
        }
    }

    //云闪付小程序支付
    public function yunshanfuWxPay($payorder){
        if(getcustom('pay_chinaums')){
            if($payorder['paynum']){
                $set = Db::name('admin_setapp_wx')->where('aid',aid)->find();
                if($set['ysf_jump_appid'] && $set['ysf_jump_path']){
                    return $this->json(['status'=>1,'msg'=>'ok','data' => [
                        'cqpMpAppId' => $set['ysf_jump_appid'],
                        'cqpMpPath' => $set['ysf_jump_path'] . '?tn='.$payorder['paynum']
                    ]]);
                }
            }
            $payorder['wxopenid'] = $this->member['wxopenid'];
            $yunshanfu = new \app\custom\YunshanfuWxPay(aid);
            $res = $yunshanfu->ysxOrderPay($payorder);
            if($res['status'] == 1){
                $miniPay = $res['data']['miniPayRequest'];
                Db::name('payorder')->where('id',$payorder['id'])->where('aid',aid)->update([
                    'paynum' => $miniPay['tn']
                ]);

                //截取路径
                $path = substr($miniPay['cqpMpPath'], 0, strpos($miniPay['cqpMpPath'], '?'));
                if ($path === false) {
                    $path = $miniPay['cqpMpPath'];
                }
                Db::name('admin_setapp_wx')->where('aid',aid)->update([
                    'ysf_jump_appid' => $miniPay['cqpMpAppId'], //云闪付小程序的appid
                    'ysf_jump_path' => $path //云闪付小程序支付路径
                ]);
                return $this->json(['status'=>1,'msg'=>'ok','data' => $res['data']['miniPayRequest']]);
            }
            return $this->json(['status'=>0,'msg'=>$res['msg']]);
        }
    }

    public function dealmoneypayscene($moneypay=0,$payscenes,$payordertype=''){
        if(getcustom('system_moneypayscene')){
            $moneypayscene = $moneypay;
            //判断场景能否能用余额支付
            if($moneypay){
                $payscenes = $payscenes && !empty($payscenes)?explode(',',$payscenes):[];
                if($payscenes && $payscenes[0] != 'all'){
                    if($payscenes[0] == 'none'){
                        $moneypayscene = 0;
                    }else{
                        //扩展类型
                        $shoptypes = ['shop_hb','shop_fenqi','balance','shopfront'];
                        $scoreshoptypes = ['scoreshop_hb'];
                        $yuyuetypes = ['yuyue_balance','yuyue_addmoney','yuyue_workerapply'];
                        $restaurant_shoptypes = ['restaurant_shop_hb'];
                        $restaurant_takeawaytypes = ['restaurant_takeaway_hb'];

                        //如果不在范围，判断是否是扩展类型
                        if(!in_array($payordertype,$payscenes)){
                            $inpayscene = false;
                            if(in_array('shop',$payscenes)){
                                if(in_array($payordertype,$shoptypes)){
                                    $inpayscene = true;
                                }
                            }else if(in_array('scoreshop',$payscenes)){
                                if(in_array($payordertype,$scoreshoptypes)){
                                    $inpayscene = true;
                                }
                            }else if(in_array('yuyue',$payscenes)){
                                if(in_array($payordertype,$yuyuetypes)){
                                    $inpayscene = true;
                                }
                            }else if(in_array('restaurant_shop',$payscenes)){
                                if(in_array($payordertype,$restaurant_shoptypes)){
                                    $inpayscene = true;
                                }
                            }else if(in_array('restaurant_takeaway',$payscenes)){
                                if(in_array($payordertype,$restaurant_takeawaytypes)){
                                    $inpayscene = true;
                                }
                            }
                            if(!$inpayscene) $moneypayscene = 0;
                        }
                    }
                }
            }
            return $moneypayscene;
        }
    }
    public function dealshopscore($order){
        if(getcustom('member_shopscore')){
            if($order['shopscore'] && $order['shopscore']>0 && $order['shopscore_status'] != 1){
                //扣除产品积分
                $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop'];
                $res = \app\common\Member::addshopscore($order['aid'],$order['mid'],-$order['shopscore'],'抵扣订单，订单号: '.$order['ordernum'],$params);
                if(!$res || $res['status'] != 1){
                    $msg = $res && $res['msg']? $res['msg'] : t('产品积分').'抵扣失败';
                    return ['status'=>0,'msg'=>$msg];
                }
                Db::name('shop_order')->where('id',$order['id'])->update(['shopscore_status'=>1]);
            }
        }
        return ['status'=>1,'msg'=>''];
    }

    //检查支付信息，如二维码支付，是否已扫码支付
    public function checkpay(){
        $orderid = input('param.orderid/d');
        $payorder = Db::name('payorder')->where('id',$orderid)->where('aid',aid)->find();
        if(!$payorder){
            return $this->json(['status'=>0,'msg'=>'该订单不存在']);
        }
        if($payorder['status'] == 1){
            return $this->json(['status'=>2,'msg'=>'该订单已支付']);
        }
        return $this->json(['status'=>1,'msg'=>'']);
    }

    public function getautoclose($aid,$payorder){
        if(getcustom('pay_countdown') || getcustom('sxpay_native')) {
            $shoptypes = ['shop_hb','shop_fenqi'];//商城类型
            $autoclose = -1;//自动关闭时间（分钟），默认没有倒计时
            if($payorder['type'] == 'shop' || in_array($payorder['type'],$shoptypes)) {
                $autoclose =  Db::name('shop_sysset')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'scoreshop' || $payorder['type'] == 'scoreshop_hb'){
                $autoclose = Db::name('scoreshop_sysset')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'yuyue'){
                $autoclose = Db::name('yuyue_set')->where('aid',$aid)->where('bid',$payorder['bid'])->value('autoclose');
            }else if($payorder['type'] == 'restaurant_shop' || $payorder['type'] == 'restaurant_shop_hb'){
                $autoclose =  Db::name('shop_sysset')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'restaurant_takeaway' || $payorder['type'] == 'restaurant_takeaway_hb'){
                //$autoclose = Db::name('restaurant_admin_set')->where('aid',$aid)->value('takeaway_autoclose');
            }else if($payorder['type'] == 'seckill') {
                $autoclose = Db::name('seckill_sysset')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'yueke') {
                $autoclose = Db::name('yueke_set')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'huodong_baoming') {
                $autoclose = Db::name('huodong_baoming_set')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'hotel') {
                $autoclose = Db::name('hotel_set')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'cycle') {
                //$autoclose = Db::name('cycle_sysset')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'car_hailing') {
                $autoclose = Db::name('car_hailing_set')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'hanglvfeike') {
                $autoclose = Db::name('hanglvfeike_set')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'zhiyoubao') {
                $autoclose = Db::name('zhiyoubao_set')->where('aid',$aid)->value('autoclose');
            }else if($payorder['type'] == 'score_transfer') {
                $autoclose = Db::name('admin_set')->where('aid',$aid)->value('autoclose_score_transfer');
            }else if($payorder['type'] == 'fishpond') {
                $autoclose = Db::name('fishpond_sysset')->where('aid',$aid)->value('autoclose');
            }
            $autoclose = $autoclose??0;
            return $autoclose;
        }
    }
}

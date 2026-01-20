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
// | 菜单配置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class DesignerMenuShopdetail extends Common
{

	/**
	 * 商城详情页导航
	 */
	public function shopdetail(){
		$type = input('param.type') ? input('param.type') : 'all';
		$rs = Db::name('business_sysset')->where('aid',aid)->find();
		$showbusiness = 0;
		$carturl = "/pages/shop/cart";
		if(bid>0){
			$binfo = Db::name('business')->where('id',bid)->field('id,aid,kfurl,bottomImg')->find();
			if($rs['show_shopdetail_menu'] == 0){//自定义商品详情导航 1开启 0关闭
				$showbusiness = 1;
			}else{
				$info = Db::name('designer_shopdetail')->where('aid',aid)->where('bid',bid)->find();
				$carturl = "/pages/shop/cart?bid=".bid;
				if(!$info){
					$info_bid0 = Db::name('designer_shopdetail')->where('aid',aid)->where('bid',0)->find();
					if($info_bid0){						
						if($binfo && !empty($binfo['kfurl'])){
							$menudata_bid0 = json_decode($info_bid0['menudata'],true);
							
								foreach($menudata_bid0['list'] as $kl=>$vl){
									if($vl['menuType'] == 1){
										$menudata_bid0['list'][$kl]['pagePath'] = $binfo['kfurl'];
										$menudata_bid0['list'][$kl]['useSystem'] = 0;
									}																		
								}	

							$info_bid0['menudata'] = jsonEncode($menudata_bid0);
						}
						$menudata_bid0 = json_decode($info_bid0['menudata'],true);
							
						foreach($menudata_bid0['list'] as $kl=>$vl){
							if($vl['menuType'] == 2){
								$menudata_bid0['list'][$kl]['pagePath'] = $carturl;
							}
						}
						$info_bid0['menudata'] = jsonEncode($menudata_bid0);
						$info_bid0['bid'] = bid;
						$insertdata = $info_bid0;
						unset($insertdata['id']);
						Db::name('designer_shopdetail')->insert($insertdata);
					}
					
				}
			}
			View::assign('binfo',$binfo);
		}
		
		$info = Db::name('designer_shopdetail')->where('aid',aid)->where('bid',bid)->find();
		if($showbusiness == 0){		
			if(!$info){
				$AdminSet = Db::name('admin_set')->where('aid',aid)->field('id,aid,kfurl')->find();
				$kfurl = '';
				$useSystem = 1;
				
				if(bid > 0){
					$AdminSet = Db::name('business')->where('id',bid)->field('id,aid,kfurl')->find();
					$carturl = "/pages/shop/cart?bid=".bid;
				}
				if($AdminSet && !empty($AdminSet['kfurl'])){
					$kfurl = $AdminSet['kfurl'];
					$useSystem = 0;
				}
				$insertdata = [];
				$insertdata['aid'] = aid;
				$insertdata['bid'] = bid;
				$insertdata['menucount'] = 3;
				$insertdata['indexurl'] = '/pages/index/index';
				$insertdata['menudata'] = jsonEncode([
					"color"=>"#BBBBBB",
					"selectedColor"=>"#FD4A46",
					"backgroundColor"=>"#ffffff",
					"borderStyle"=>"black",
					"position"=>"bottom",
					"list"=>[
						["text"=>"客服","pagePath"=>$kfurl,"iconPath"=>PRE_URL.'/static/img/tabbar/kefu.png',"selectedIconPath"=>PRE_URL.'/static/img/tabbar/kefu.png',"pagePathname"=>"功能>客服","isShow"=>1,"menuType"=>1,"useSystem"=>$useSystem
						],
						["text"=>"购物车","pagePath"=>$carturl,"iconPath"=>PRE_URL.'/static/img/tabbar/gwc.png',"selectedIconPath"=>PRE_URL.'/static/img/tabbar/gwc.png',"pagePathname"=>"基础功能>购物车","isShow"=>1,"menuType"=>2,"useSystem"=>0
						],
						["text"=>"收藏","pagePath"=>"addfavorite::","iconPath"=>PRE_URL.'/static/img/tabbar/shoucang.png',"selectedIconPath"=>PRE_URL.'/static/img/tabbar/shoucangselected.png',"pagePathname"=>"基础功能>收藏","isShow"=>1,"menuType"=>3,"useSystem"=>0,"selectedtext"=>"已收藏"
						],			
					]
				]);
				$insertdata['navigationBarBackgroundColor'] = '#333333';
				$insertdata['navigationBarTextStyle'] = 'white';
				$insertdata['platform'] = 'all';
				Db::name('designer_shopdetail')->insert($insertdata);
				$info = Db::name('designer_shopdetail')->where('aid',aid)->where('bid',bid)->find();
			}
		}
		$menudata = json_decode($info['menudata'],true);
		View::assign('showbusiness',$showbusiness);
		View::assign('menudata',$menudata);
		View::assign('info',$info);		
		View::assign('type',$type);
		$mode = Db::name('admin_set')->where('aid',aid)->value('mode');
		View::assign('mode',$mode);
		
		return View::fetch();
	}
	/**
	 * 商城详情页导航保存
	 */
	public function shopdetailsave(){
		$type = input('param.type') ? input('param.type') : 'all';
		$data = input('post.info/a');
		$data['menudata'] = jsonEncode($data['menudata']);
		$data['updatetime'] = time();
		
		
		Db::name('designer_shopdetail')->where('aid',aid)->where('bid',bid)->update($data);
		
		\app\common\System::plog('详情页导航');
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('shopdetail').'/type/'.$type]);
	}
	/**
	 * 商城详情页导航保存
	 */
	public function busnesssave(){
		if(request()->isPost()){
			$postinfo = input('post.binfo/a');
			$info['kfurl'] = $postinfo['kfurl'];
            $info['bottomImg'] = $postinfo['bottomImg']??'';
			db('business')->where(['aid'=>aid,'id'=>bid])->update($info);
			\app\common\System::plog('系统设置');
			return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('shopdetail')]);
		}
	}
	
}
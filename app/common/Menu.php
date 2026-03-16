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
class Menu
{
	//获取菜单数据
    //$ismenu 是否是菜单栏读取操作，默认不是
    //$webUserAid 控制台编辑用户列表aid
	public static function getdata($aid=0,$uid=0,$ismenu=false,$webUserAid=0,$bid=0){
		$user = [];
		if($aid == 0){
			$platform = ['mp','wx','alipay','baidu','toutiao','qq','h5','app'];
		}else{
            $admin = Db::name('admin')->where('id',$aid)->find();
			$platform = explode(',',$admin['platform']);
		}
		if($uid > 0){
			$user = Db::name('admin_user')->where('id',$uid)->find();
			if($user['bid'] > 0){
				$isadmin = false;
				if($user['auth_type'] == 1){
					$user = Db::name('admin_user')->where('aid',$aid)->where('isadmin','>',0)->find();
				}
			}else{
				$isadmin = true;
			}
		}else{
			if($uid == -1){
				$isadmin = false;
				$user = Db::name('admin_user')->where('aid',$aid)->where('isadmin','>',0)->find();
			}else{
				$isadmin = true;
			}
		}

		$menudata = [];
        $text = ['商品服务' => '商品服务','商品评价' => '商品评价','服务订单' => '服务订单'];
        $shop_child = [];
		$shop_child[] = ['name'=>'商品管理','path'=>'ShopProduct/index','authdata'=>'ShopProduct/*,ShopCode/*'];
        if($isadmin) {
            }
		$shop_child[] = ['name'=>'订单管理','path'=>'ShopOrder/index','authdata'=>'ShopOrder/*'];
        //        $shop_child[] = ['name'=>'订单管理','path'=>'ShopOrder/index','authdata'=>'ShopOrder/*'];
        $shop_child[] = ['name'=>'退款申请','path'=>'ShopRefundOrder/index','authdata'=>'ShopRefundOrder/*'];
        $shop_child[] = ['name'=>'评价管理','path'=>'ShopComment/index','authdata'=>'ShopComment/*'];
		if($isadmin){
			$shop_child[] = ['name'=>'商品分类','path'=>'ShopCategory/index','authdata'=>'ShopCategory/*'];
            $shop_child[] = ['name'=>'商品分组','path'=>'ShopGroup/index','authdata'=>'ShopGroup/*'];
            }else{
			$shop_child[] = ['name'=>'商品分类','path'=>'ShopCategory2/index','authdata'=>'ShopCategory2/*'];
		}
		$shop_child[] = ['name'=>'商品参数','path'=>'ShopParam/index','authdata'=>'ShopParam/*'];
		$shop_child[] = ['name'=>'商品服务','path'=>'ShopFuwu/index','authdata'=>'ShopFuwu/*'];
        if($isadmin){
			$shop_child[] = ['name'=>'商品海报','path'=>'ShopPoster/index','authdata'=>'ShopPoster/*'];
			$shop_child[] = ['name'=>'录入订单','path'=>'ShopOrderlr/index','authdata'=>'ShopOrderlr/*,ShopProduct/chooseproduct,ShopProduct/index,ShopProduct/getproduct,Member/choosemember'];
            }
		$shop_child[] = ['name'=>'商品采集','path'=>'ShopTaobao/index','authdata'=>'ShopTaobao/*'];

		$shop_child[] = ['name'=>'销售统计','path'=>'ShopOrder/tongji','authdata'=>'ShopOrder/*'];
        if($isadmin){
            $shop_child[] = ['name'=>'系统设置','path'=>'ShopSet/index','authdata'=>'ShopSet/*'];
            }else{
            }
        if($isadmin) {
            }
        if($isadmin) {
            }
        if(getcustom('shopbuy_sign',$aid)){
            $shop_child[] = ['name'=>'下单签订合同','path'=>'ShopbuySign','authdata'=>'ShopbuySign','hide'=>true];
        }
        if(getcustom('shoporder_del_auth',$aid)){
            $shop_child[] = ['name'=>'订单删除','path'=>'OrderDelAuth','authdata'=>'OrderDelAuth','hide'=>true];
        }
		$menudata['shop'] = ['name'=>'商城','fullname'=>'商城系统','icon'=>'my-icon my-icon-shop','child'=>$shop_child];

		// ============================================================
		// 照片生成菜单 (新)
		// ============================================================
		if($isadmin || $uid == -1){
			$photo_gen_child = [];
			$photo_gen_child[] = ['name'=>'生成任务','path'=>'PhotoGeneration/task_create','authdata'=>'PhotoGeneration/task_create,PhotoGeneration/get_model_schema,PhotoGeneration/get_template_detail'];
			$photo_gen_child[] = ['name'=>'生成记录','path'=>'PhotoGeneration/record_list','authdata'=>'PhotoGeneration/record_list,PhotoGeneration/record_detail,PhotoGeneration/task_retry,PhotoGeneration/task_cancel,PhotoGeneration/record_delete,PhotoGeneration/convert_to_template'];
			$photo_gen_child[] = ['name'=>'场景分类','path'=>'PhotoSceneCategory/index','authdata'=>'PhotoSceneCategory/index,PhotoSceneCategory/edit,PhotoSceneCategory/save,PhotoSceneCategory/del,PhotoSceneCategory/choosecategory'];
			$photo_gen_child[] = ['name'=>'场景分组','path'=>'PhotoSceneGroup/index','authdata'=>'PhotoSceneGroup/index,PhotoSceneGroup/edit,PhotoSceneGroup/save,PhotoSceneGroup/del,PhotoSceneGroup/choosegroup'];
			$photo_gen_child[] = ['name'=>'场景模板','path'=>'PhotoGeneration/scene_list','authdata'=>'PhotoGeneration/scene_list,PhotoGeneration/scene_edit,PhotoGeneration/scene_save,PhotoGeneration/scene_delete,PhotoGeneration/scene_status'];
			$photo_gen_child[] = ['name'=>'订单管理','path'=>'PhotoGeneration/order_list','authdata'=>'PhotoGeneration/order_list,PhotoGeneration/order_detail,PhotoGeneration/refund_check'];
			$menudata['photo_generation'] = ['name'=>'照片生成','fullname'=>'照片生成','icon'=>'layui-icon layui-icon-picture','child'=>$photo_gen_child];
		}elseif($bid > 0){
			// 商户用户登录时，检查商户是否开通照片生成功能
			$business_photo = Db::name('business')->where('id', $bid)->find();
			if($business_photo && isset($business_photo['photo_generation_enabled']) && $business_photo['photo_generation_enabled'] == 1){
				$photo_gen_child = [];
				$photo_gen_child[] = ['name'=>'生成任务','path'=>'PhotoGeneration/task_create','authdata'=>'PhotoGeneration/task_create,PhotoGeneration/get_model_schema,PhotoGeneration/get_template_detail'];
				$photo_gen_child[] = ['name'=>'生成记录','path'=>'PhotoGeneration/record_list','authdata'=>'PhotoGeneration/record_list,PhotoGeneration/record_detail,PhotoGeneration/task_retry,PhotoGeneration/task_cancel,PhotoGeneration/record_delete,PhotoGeneration/convert_to_template'];
				$photo_gen_child[] = ['name'=>'场景分类','path'=>'PhotoSceneCategory/index','authdata'=>'PhotoSceneCategory/index,PhotoSceneCategory/edit,PhotoSceneCategory/save,PhotoSceneCategory/del,PhotoSceneCategory/choosecategory'];
				$photo_gen_child[] = ['name'=>'场景分组','path'=>'PhotoSceneGroup/index','authdata'=>'PhotoSceneGroup/index,PhotoSceneGroup/edit,PhotoSceneGroup/save,PhotoSceneGroup/del,PhotoSceneGroup/choosegroup'];
				$photo_gen_child[] = ['name'=>'场景模板','path'=>'PhotoGeneration/scene_list','authdata'=>'PhotoGeneration/scene_list,PhotoGeneration/scene_edit,PhotoGeneration/scene_save,PhotoGeneration/scene_delete,PhotoGeneration/scene_status'];
				$photo_gen_child[] = ['name'=>'订单管理','path'=>'PhotoGeneration/order_list','authdata'=>'PhotoGeneration/order_list,PhotoGeneration/order_detail,PhotoGeneration/refund_check'];
				$menudata['photo_generation'] = ['name'=>'照片生成','fullname'=>'照片生成','icon'=>'layui-icon layui-icon-picture','child'=>$photo_gen_child];
			}
		}

		// ============================================================
		// 视频生成菜单 (新)
		// ============================================================
		if($isadmin || $uid == -1){
			$video_gen_child = [];
			$video_gen_child[] = ['name'=>'生成任务','path'=>'VideoGeneration/task_create','authdata'=>'VideoGeneration/task_create,VideoGeneration/get_model_schema,VideoGeneration/get_template_detail'];
			$video_gen_child[] = ['name'=>'生成记录','path'=>'VideoGeneration/record_list','authdata'=>'VideoGeneration/record_list,VideoGeneration/record_detail,VideoGeneration/task_retry,VideoGeneration/task_cancel,VideoGeneration/record_delete,VideoGeneration/convert_to_template'];
			$video_gen_child[] = ['name'=>'场景分类','path'=>'VideoSceneCategory/index','authdata'=>'VideoSceneCategory/index,VideoSceneCategory/edit,VideoSceneCategory/save,VideoSceneCategory/del,VideoSceneCategory/choosecategory'];
			$video_gen_child[] = ['name'=>'场景分组','path'=>'VideoSceneGroup/index','authdata'=>'VideoSceneGroup/index,VideoSceneGroup/edit,VideoSceneGroup/save,VideoSceneGroup/del,VideoSceneGroup/choosegroup'];
			$video_gen_child[] = ['name'=>'场景模板','path'=>'VideoGeneration/scene_list','authdata'=>'VideoGeneration/scene_list,VideoGeneration/scene_edit,VideoGeneration/scene_save,VideoGeneration/scene_delete,VideoGeneration/scene_status'];
			$video_gen_child[] = ['name'=>'订单管理','path'=>'VideoGeneration/order_list','authdata'=>'VideoGeneration/order_list,VideoGeneration/order_detail,VideoGeneration/refund_check'];
			$menudata['video_generation'] = ['name'=>'视频生成','fullname'=>'视频生成','icon'=>'layui-icon layui-icon-video','child'=>$video_gen_child];
		}elseif($bid > 0){
			// 商户用户登录时，检查商户是否开通视频生成功能
			$business_video = Db::name('business')->where('id', $bid)->find();
			if($business_video && isset($business_video['video_generation_enabled']) && $business_video['video_generation_enabled'] == 1){
				$video_gen_child = [];
				$video_gen_child[] = ['name'=>'生成任务','path'=>'VideoGeneration/task_create','authdata'=>'VideoGeneration/task_create,VideoGeneration/get_model_schema,VideoGeneration/get_template_detail'];
				$video_gen_child[] = ['name'=>'生成记录','path'=>'VideoGeneration/record_list','authdata'=>'VideoGeneration/record_list,VideoGeneration/record_detail,VideoGeneration/task_retry,VideoGeneration/task_cancel,VideoGeneration/record_delete,VideoGeneration/convert_to_template'];
				$video_gen_child[] = ['name'=>'场景分类','path'=>'VideoSceneCategory/index','authdata'=>'VideoSceneCategory/index,VideoSceneCategory/edit,VideoSceneCategory/save,VideoSceneCategory/del,VideoSceneCategory/choosecategory'];
				$video_gen_child[] = ['name'=>'场景分组','path'=>'VideoSceneGroup/index','authdata'=>'VideoSceneGroup/index,VideoSceneGroup/edit,VideoSceneGroup/save,VideoSceneGroup/del,VideoSceneGroup/choosegroup'];
				$video_gen_child[] = ['name'=>'场景模板','path'=>'VideoGeneration/scene_list','authdata'=>'VideoGeneration/scene_list,VideoGeneration/scene_edit,VideoGeneration/scene_save,VideoGeneration/scene_delete,VideoGeneration/scene_status'];
				$video_gen_child[] = ['name'=>'订单管理','path'=>'VideoGeneration/order_list','authdata'=>'VideoGeneration/order_list,VideoGeneration/order_detail,VideoGeneration/refund_check'];
				$menudata['video_generation'] = ['name'=>'视频生成','fullname'=>'视频生成','icon'=>'layui-icon layui-icon-video','child'=>$video_gen_child];
			}
		}

		// ============================================================
		// AI旅拍菜单 - 保留业务子菜单，去掉模型配置相关子菜单
		// ============================================================
		if($isadmin || $uid == -1){
			$ai_travel_photo_child = [];
			
			// 套餐管理
			$ai_travel_photo_child[] = ['name'=>'套餐管理','path'=>'AiTravelPhoto/package_list','authdata'=>'AiTravelPhoto/package_list,AiTravelPhoto/package_edit,AiTravelPhoto/package_delete,AiTravelPhoto/package_batch'];
			
			// 人像管理
			$ai_travel_photo_child[] = ['name'=>'人像管理','path'=>'AiTravelPhoto/portrait_list','authdata'=>'AiTravelPhoto/portrait_list,AiTravelPhoto/portrait_delete,AiTravelPhoto/portrait_batch'];

			// 合成模板
			$ai_travel_photo_child[] = ['name'=>'合成模板','path'=>'AiTravelPhoto/synthesis_template_list','authdata'=>'AiTravelPhoto/synthesis_template_list,AiTravelPhoto/synthesis_template_edit,AiTravelPhoto/synthesis_template_save,AiTravelPhoto/synthesis_template_delete'];

			// 生成结果（隐藏菜单，通过人像列表进入）
			$ai_travel_photo_child[] = ['name'=>'生成结果','path'=>'AiTravelPhoto/portrait_detail','authdata'=>'AiTravelPhoto/portrait_detail','hide'=>true];

			// 订单管理
			$ai_travel_photo_child[] = ['name'=>'订单管理','path'=>'AiTravelPhoto/order_list','authdata'=>'AiTravelPhoto/order_list,AiTravelPhoto/order_detail,AiTravelPhoto/order_refund'];

			// 设备管理
			$ai_travel_photo_child[] = ['name'=>'设备管理','path'=>'AiTravelPhoto/device_list','authdata'=>'AiTravelPhoto/device_list,AiTravelPhoto/device_generate_token,AiTravelPhoto/device_delete'];

			// 选片列表
			$ai_travel_photo_child[] = ['name'=>'选片列表','path'=>'AiTravelPhoto/qrcode_list','authdata'=>'AiTravelPhoto/qrcode_list'];

			// 成品列表
			$ai_travel_photo_child[] = ['name'=>'成品列表','path'=>'AiTravelPhoto/result_list','authdata'=>'AiTravelPhoto/result_list'];

			// 数据统计
			$ai_travel_photo_child[] = ['name'=>'数据统计','path'=>'AiTravelPhoto/statistics','authdata'=>'AiTravelPhoto/statistics'];

			// 系统设置
			$ai_travel_photo_child[] = ['name'=>'系统设置','path'=>'AiTravelPhoto/settings','authdata'=>'AiTravelPhoto/settings'];

			$menudata['ai_travel_photo'] = ['name'=>'旅拍','fullname'=>'AI旅拍','icon'=>'my-icon my-icon-aitravelphoto','child'=>$ai_travel_photo_child];
		}elseif($bid > 0){
			// 商户用户登录时，检查商户是否开通AI旅拍功能
			$business = Db::name('business')->where('id', $bid)->find();
			if($business && isset($business['ai_travel_photo_enabled']) && $business['ai_travel_photo_enabled'] == 1){
				$ai_travel_photo_child = [];

				// 套餐管理
				$ai_travel_photo_child[] = ['name'=>'套餐管理','path'=>'AiTravelPhoto/package_list','authdata'=>'AiTravelPhoto/package_list,AiTravelPhoto/package_edit,AiTravelPhoto/package_delete,AiTravelPhoto/package_batch'];

				// 人像管理
				$ai_travel_photo_child[] = ['name'=>'人像管理','path'=>'AiTravelPhoto/portrait_list','authdata'=>'AiTravelPhoto/portrait_list,AiTravelPhoto/portrait_delete,AiTravelPhoto/portrait_batch'];

				// 合成模板
				$ai_travel_photo_child[] = ['name'=>'合成模板','path'=>'AiTravelPhoto/synthesis_template_list','authdata'=>'AiTravelPhoto/synthesis_template_list,AiTravelPhoto/synthesis_template_edit,AiTravelPhoto/synthesis_template_save,AiTravelPhoto/synthesis_template_delete'];

				// 生成结果（隐藏菜单，通过人像列表进入）
				$ai_travel_photo_child[] = ['name'=>'生成结果','path'=>'AiTravelPhoto/portrait_detail','authdata'=>'AiTravelPhoto/portrait_detail','hide'=>true];

				// 订单管理
				$ai_travel_photo_child[] = ['name'=>'订单管理','path'=>'AiTravelPhoto/order_list','authdata'=>'AiTravelPhoto/order_list,AiTravelPhoto/order_detail,AiTravelPhoto/order_refund'];
				
				// 设备管理
				$ai_travel_photo_child[] = ['name'=>'设备管理','path'=>'AiTravelPhoto/device_list','authdata'=>'AiTravelPhoto/device_list,AiTravelPhoto/device_generate_token,AiTravelPhoto/device_delete'];
				
				// 选片列表
				$ai_travel_photo_child[] = ['name'=>'选片列表','path'=>'AiTravelPhoto/qrcode_list','authdata'=>'AiTravelPhoto/qrcode_list'];
				
				// 成品列表
				$ai_travel_photo_child[] = ['name'=>'成品列表','path'=>'AiTravelPhoto/result_list','authdata'=>'AiTravelPhoto/result_list'];
				
				// 数据统计
				$ai_travel_photo_child[] = ['name'=>'数据统计','path'=>'AiTravelPhoto/statistics','authdata'=>'AiTravelPhoto/statistics'];
				
				// 系统设置
				$ai_travel_photo_child[] = ['name'=>'系统设置','path'=>'AiTravelPhoto/settings','authdata'=>'AiTravelPhoto/settings'];
				
				$menudata['ai_travel_photo'] = ['name'=>'旅拍','fullname'=>'AI旅拍','icon'=>'my-icon my-icon-aitravelphoto','child'=>$ai_travel_photo_child];
			}
		}

        if($isadmin){
            $component_business = [];
            $component_business[] = ['name'=>'商户列表','path'=>'Business/index','authdata'=>'Business/*,BusinessFreight/*'];
            if(getcustom('business_city',$aid) && bid==0){
                $component_business[] = ['name'=>'城市商户','path'=>'Business/businesscity','authdata'=>'Business/*'];
            }
            $component_business[] = ['name'=>'商户分类','path'=>'Business/category','authdata'=>'Business/*'];
            $component_business[] = ['name'=>'商户商品','path'=>'ShopProduct/index&showtype=2','authdata'=>'ShopProduct/*'];
            $component_business[] = ['name'=>'商户销量','path'=>'Business/sales','authdata'=>'Business/*'];
            $component_business[] = ['name'=>'商户订单','path'=>'ShopOrder/index&showtype=2','authdata'=>'ShopOrder/*'];
            $component_business[] = ['name'=>'商户评价','path'=>'BusinessComment/index&showtype=2','authdata'=>'BusinessComment/*'];
            $component_business[] = ['name'=>'拼团商品','path'=>'CollageProduct/index&showtype=2','authdata'=>'CollageProduct/*'];
            $component_business[] = ['name'=>'砍价商品','path'=>'KanjiaProduct/index&showtype=2','authdata'=>'KanjiaProduct/*'];
            $component_business[] = ['name'=>'秒杀商品','path'=>'SeckillProduct/index&showtype=2','authdata'=>'SeckillProduct/*'];
            $component_business[] = ['name'=>'团购商品','path'=>'TuangouProduct/index&showtype=2','authdata'=>'TuangouProduct/*'];
            $component_business[] = ['name'=>'服务商品','path'=>'YuyueList/index&showtype=2','authdata'=>'YuyueList/*'];
            $component_business[] = ['name'=>'周期购商品','path'=>'CycleProduct/index&showtype=2','authdata'=>'CycleProduct/*'];
            $component_business[] = ['name'=>t('幸运拼团').'商品','path'=>'LuckyCollageProduct/index&showtype=2','authdata'=>'LuckyCollageProduct/*'];
            if(getcustom('business_selfscore') || getcustom('business_score_withdraw') || getcustom('business_score_jiesuan')){
                $component_businessC = [];
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($isadmin==2 || $bset['business_selfscore'] == 1){
                    }
                if($component_businessC){
                    if(count($component_businessC)==1){
                        $component_business[] = $component_businessC[0];
                    }else{
                        $component_business[] = ['name'=>'商户积分','child'=>$component_businessC];
                    }
                }
            }
            $component_business[] = ['name'=>'文章列表','path'=>'Article/index&showtype=2','authdata'=>'Article/*'];
            $component_business[] = ['name'=>'短视频列表','path'=>'Shortvideo/index&showtype=2','authdata'=>'Shortvideo/*'];
            if(false){}else{
                $component_business[] = ['name'=>t('自定义表单'),'path'=>'Form/index&showtype=2','authdata'=>'Form/*'];
            }
            $component_business[] = ['name'=>t('余额').'明细','path'=>'BusinessMoney/moneylog','authdata'=>'BusinessMoney/moneylog,BusinessMoney/moneylogexcel,BusinessMoney/moneylogsetst,BusinessMoney/moneylogdel'];
            $component_business[] = ['name'=>'提现记录','path'=>'BusinessMoney/withdrawlog','authdata'=>'BusinessMoney/*'];
            $component_business[] = ['name'=>'通知公告','path'=>'BusinessNotice/index','authdata'=>'BusinessNotice/*'];
            $component_business[] = ['name'=>'默认导航','path'=>'DesignerMenu/business','authdata'=>'DesignerMenu/*'];
            $component_business[] = ['name'=>'系统设置','path'=>'Business/sysset','authdata'=>'Business/sysset'];
            if($uid == 0){
                $component_business2[] = ['name'=>'商品分类','path'=>'ShopCategory2/index','authdata'=>'ShopCategory2/*','hide'=>true];
                $component_business2[] = ['name'=>'余额提现','path'=>'BusinessMoney/withdraw','authdata'=>'BusinessMoney/*','hide'=>true];
                $component_business2[] = ['name'=>'买单扣费','path'=>'BusinessMaidan/add','authdata'=>'BusinessMaidan/*','hide'=>true];
                $component_business2[] = ['name'=>'买单记录','path'=>'BusinessMaidan/index','authdata'=>'BusinessMaidan/*','hide'=>true];
                $component_business2[] = ['name'=>'收款码','path'=>'BusinessMaidan/set','authdata'=>'BusinessMaidan/*','hide'=>true];
				$component_business2[] = ['name'=>'店铺评价','path'=>'BusinessComment/index','authdata'=>'BusinessComment/*','hide'=>true];
                $component_business[] = ['name'=>'商户后台','child'=>$component_business2,'hide'=>true];
            }
            $menudata['business'] = ['name'=>'商户','fullname'=>'多商户','icon'=>'my-icon my-icon-shop','child'=>$component_business];
        }else{
            }

		if($isadmin){
			$member_child = [];
			$member_child[] = ['name'=>t('会员').'列表','path'=>'Member/index','authdata'=>'Member/index,Member/excel,Member/excel,Member/importexcel,Member/getplatform,Member/edit,Member/save,Member/del,Member/getcarddetail,Member/charts,Member/setst,Member/choosemember,Member/check'];
			$member_child[] = ['name'=>'充值','path'=>'Member/recharge','authdata'=>'Member/recharge','hide'=>true];
			$member_child[] = ['name'=>'加'.t('积分'),'path'=>'Member/addscore','authdata'=>'Member/addscore','hide'=>true];
            if(getcustom('yx_score_freeze_release') || getcustom('yx_score_freeze')){
                $member_child[] = ['name'=>'编辑冻结'.t('积分'),'path'=>'Member/addfreezescore','authdata'=>'Member/addfreezescore','hide'=>true];
            }
			$member_child[] = ['name'=>'加'.t('佣金'),'path'=>'Member/addcommission','authdata'=>'Member/addcommission','hide'=>true];
			$member_child[] = ['name'=>'等级及分销','path'=>'MemberLevel/index','authdata'=>'MemberLevel/*'];
            $member_child[] = ['name'=>'升级申请记录','path'=>'MemberLevel/applyorder','authdata'=>'MemberLevel/*'];
            $member_child[] = ['name'=>t('会员').'关系图','path'=>'Member/charts','authdata'=>'Member/charts'];
            $member_child[] = ['name'=>'分享海报','path'=>'MemberPoster/index','authdata'=>'MemberPoster/*'];
			$member_child[] = ['name'=>'团队分红','path'=>'teamfenhong','authdata'=>'teamfenhong','hide'=>true];
            $member_child[] = ['name'=>'股东分红','path'=>'gdfenhong','authdata'=>'gdfenhong','hide'=>true];
			$member_child[] = ['name'=>'区域分红','path'=>'areafenhong','authdata'=>'areafenhong','hide'=>true];
			if(getcustom('levelup_code') || getcustom('levelup_bg')){
				$member_child[] = ['name'=>t('会员').'等级背景设置','path'=>'MemberLevel/bgset','authdata'=>'MemberLevel/bgset','hide'=>true];
			}
			//优惠券记录
            $member_child[] = ['name'=>'优惠券','path'=>'Coupon/record','authdata'=>'Coupon/record,Coupon/recordexcel,Coupon/recorddel,Coupon/recordsetst,Coupon/decCouponOne,Coupon/delay,Coupon/creategiveqr,Coupon/creategiveqr2','hide'=>true];
			// 创作会员管理
			$creative_member_child = [];
			$creative_member_child[] = ['name'=>'套餐管理','path'=>'CreativeMember/plan_list','authdata'=>'CreativeMember/plan_list,CreativeMember/plan_edit,CreativeMember/plan_save,CreativeMember/plan_del,CreativeMember/plan_status'];
			$creative_member_child[] = ['name'=>'订阅记录','path'=>'CreativeMember/subscription_list','authdata'=>'CreativeMember/subscription_list'];
			$creative_member_child[] = ['name'=>'积分流水','path'=>'CreativeMember/score_log','authdata'=>'CreativeMember/score_log'];
			$member_child[] = ['name'=>'创作会员','child'=>$creative_member_child];
			$menudata['member'] = ['name'=>t('会员'),'fullname'=>t('会员').'管理','icon'=>'my-icon my-icon-member','child'=>$member_child];
            
		}else{
            }
		if($isadmin){
			$finance_child = [];
			$finance_child[] = ['name'=>'消费明细','path'=>'Payorder/index','authdata'=>'Payorder/*'];
			$finance_child[] = ['name'=>t('余额').'明细','path'=>'Money/moneylog','authdata'=>'Money/moneylog,Money/moneylogexcel,Money/moneylogsetst,Money/moneylogdel'];
            $finance_child[] = ['name'=>'充值记录','path'=>'Money/rechargelog','authdata'=>'Money/rechargelog,Money/rechargelogexcel,Money/rechargelogdel'];
			$finance_child[] = ['name'=>t('余额').'提现','path'=>'Money/withdrawlog','authdata'=>'Money/*'];
            $finance_child[] = ['name'=>'微信转账记录','path'=>'Money/wx_transfer_log','authdata'=>'Money/*'];
            $finance_child[] = ['name'=>t('佣金').'记录','path'=>'Commission/record','authdata'=>'Commission/record'];
            $finance_child[] = ['name'=>t('佣金').'明细','path'=>'Commission/commissionlog','authdata'=>'Commission/commissionlog,Commission/commissionlogexcel,Commission/commissionlogdel'];
			$finance_child[] = ['name'=>t('佣金').'提现','path'=>'Commission/withdrawlog','authdata'=>'Commission/*'];
            $finance_child[] = ['name'=>t('积分').'明细','path'=>'Score/scorelog','authdata'=>'Score/*'];
            $finance_childC = [];
            if(getcustom('business_selfscore') || getcustom('business_score_withdraw') || getcustom('business_score_jiesuan')){
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($isadmin==2 || $bset['business_selfscore'] == 1){
                    $finance_childC[] = ['name'=>t('积分').'明细','path'=>'BusinessScore/scorelog','authdata'=>'BusinessScore/*','hide'=>true];
                    if($bset['business_selfscore2'] == 1){
                        $finance_childC[] = ['name'=>t('会员').t('积分'),'path'=>'BusinessScore/memberscore','authdata'=>'BusinessScore/*','hide'=>true];
                    }
                    }
            }
            if($finance_childC){
                $finance_child[] = ['name'=>'商户'.t('积分'),'child'=>$finance_childC,'hide'=>true];
            }
            $maidan_child = [];
            $maidan_child[] = ['name'=>'买单扣费','path'=>'Maidan/add','authdata'=>'Maidan/*'];
            $maidan_child[] = ['name'=>'买单记录','path'=>'Maidan/index','authdata'=>'Maidan/*'];
			$maidan_child[] = ['name'=>'聚合收款码','path'=>'Maidan/set','authdata'=>'Maidan/set'];
			$finance_child[] = ['name'=>'买单收款','child'=>$maidan_child];
            $finance_child[] = ['name'=>'分红记录','path'=>'Commission/fenhonglog','authdata'=>'Commission/*'];
            }else{
            //多商户
			$finance_child = [];
			$finance_child[] = ['name'=>'余额明细','path'=>'BusinessMoney/moneylog','authdata'=>'BusinessMoney/moneylog,BusinessMoney/moneylogexcel,BusinessMoney/moneylogsetst,BusinessMoney/moneylogdel'];
            $finance_child[] = ['name'=>'余额提现','path'=>'BusinessMoney/withdraw','authdata'=>'BusinessMoney/*'];
			$finance_child[] = ['name'=>'提现记录','path'=>'BusinessMoney/withdrawlog','authdata'=>'BusinessMoney/*'];
            $finance_childC = [];
            if(getcustom('business_selfscore') || getcustom('business_score_withdraw') || getcustom('business_score_jiesuan')){
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['business_selfscore'] == 1){
                    $finance_childC[] = ['name'=>t('积分').'明细','path'=>'BusinessScore/scorelog','authdata'=>'BusinessScore/*'];
                    if($bset['business_selfscore2'] == 1){
                        $finance_childC[] = ['name'=>t('会员').t('积分'),'path'=>'BusinessScore/memberscore','authdata'=>'BusinessScore/*'];
                    }
                    }
            }
            if($finance_childC){
                $finance_child[] = ['name'=>'商户'.t('积分'),'child'=>$finance_childC];
            }

			$maidan_child = [];
            $maidan_child[] = ['name'=>'买单扣费','path'=>'BusinessMaidan/add','authdata'=>'BusinessMaidan/*'];
            $maidan_child[] = ['name'=>'买单记录','path'=>'BusinessMaidan/index','authdata'=>'BusinessMaidan/*'];
            $maidan_child[] = ['name'=>'收款码','path'=>'BusinessMaidan/set','authdata'=>'BusinessMaidan/*'];
			$finance_child[] = ['name'=>'买单收款','child'=>$maidan_child];
            }
		$finance_child[] = ['name'=>'核销记录','path'=>'Hexiao/index','authdata'=>'Hexiao/*'];
		if(getcustom('freight_selecthxbids') || getcustom('product_quanyi')){
			$finance_child[] = ['name'=>'计次核销记录','path'=>'Hexiao/shopproduct','authdata'=>'Hexiao/*'];
		}
		$finance_child[] = ['name'=>'发票管理','path'=>'Invoice/index','authdata'=>'Invoice/*'];
		$menudata['finance'] = ['name'=>'财务','fullname'=>'财务管理','icon'=>'my-icon my-icon-finance','child'=>$finance_child];

		$yingxiao_child = [];
        $yingxiao_child[] = ['name'=>t('优惠券'),'path'=>'Coupon/index','authdata'=>'Coupon/*,ShopCategory/index,ShopCategory/choosecategory'];
		if($isadmin){
            $yingxiao_child[] = ['name'=>'注册赠送','path'=>'Member/registerGive','authdata'=>'Member/registerGive'];
            $yingxiao_child[] = ['name'=>'充值赠送','path'=>'Money/giveset','authdata'=>'Money/giveset'];
			$yingxiao_child[] = ['name'=>'购物满减','path'=>'Manjian/set','authdata'=>'Manjian/set'];
            }
		$yingxiao_child[] = ['name'=>'商品促销','path'=>'Cuxiao/index','authdata'=>'Cuxiao/*'];
		if($isadmin){
            $backprice_child = [];
            if($backprice_child){
                $yingxiao_child[] = ['name'=>t('购物返现'),'child' => $backprice_child];
            }else{
                $yingxiao_child[] = ['name'=>t('购物返现'),'path'=>'Cashback/index','authdata'=>'Cashback/*'];
                }
			}else{
            }
		$yingxiao_collage = [];
		$yingxiao_collage[] = ['name'=>'商品管理','path'=>'CollageProduct/index','authdata'=>'CollageProduct/*,CollageCode/*'];
		$yingxiao_collage[] = ['name'=>'订单管理','path'=>'CollageOrder/index','authdata'=>'CollageOrder/*'];
		$yingxiao_collage[] = ['name'=>'拼团管理','path'=>'CollageTeam/index','authdata'=>'CollageTeam/*'];
		$yingxiao_collage[] = ['name'=>'评价管理','path'=>'CollageComment/index','authdata'=>'CollageComment/*'];
		if($isadmin){
			$yingxiao_collage[] = ['name'=>'商品分类','path'=>'CollageCategory/index','authdata'=>'CollageCategory/*'];
			$yingxiao_collage[] = ['name'=>'分享海报','path'=>'CollagePoster/index','authdata'=>'CollagePoster/*'];
            $yingxiao_collage[] = ['name'=>'系统设置','path'=>'CollageSet/index','authdata'=>'CollageSet/*'];
		} else{
		    }
        $yingxiao_child[] = ['name'=>'多人拼团','child'=>$yingxiao_collage];

        $lucky_collage = [];
        $lucky_collage[] = ['name'=>'商品管理','path'=>'LuckyCollageProduct/index','authdata'=>'LuckyCollageProduct/*,LuckyCollageCode/*'];
        $lucky_collage[] = ['name'=>'订单管理','path'=>'LuckyCollageOrder/index','authdata'=>'LuckyCollageOrder/*'];
        $lucky_collage[] = ['name'=>'拼团管理','path'=>'LuckyCollageTeam/index','authdata'=>'LuckyCollageTeam/*'];
        $lucky_collage[] = ['name'=>'评价管理','path'=>'LuckyCollageComment/index','authdata'=>'LuckyCollageComment/*'];
        if($isadmin){
            $lucky_collage[] = ['name'=>'商品分类','path'=>'LuckyCollageCategory/index','authdata'=>'LuckyCollageCategory/*'];
            $lucky_collage[] = ['name'=>'分享海报','path'=>'LuckyCollagePoster/index','authdata'=>'LuckyCollagePoster/*'];
            $lucky_collage[] = ['name'=>'机器人管理','path'=>'LuckyCollageJiqiren/index','authdata'=>'LuckyCollageJiqiren/*'];
            $lucky_collage[] = ['name'=>'系统设置','path'=>'LuckyCollageSet/index','authdata'=>'LuckyCollageSet/*'];
        }
        $yingxiao_child[] = ['name'=>t('幸运拼团'),'child'=>$lucky_collage];

		$yingxiao_kanjia = [];
		$yingxiao_kanjia[] = ['name'=>'商品管理','path'=>'KanjiaProduct/index','authdata'=>'KanjiaProduct/*,KanjiaCode/*'];
		$yingxiao_kanjia[] = ['name'=>'订单管理','path'=>'KanjiaOrder/index','authdata'=>'KanjiaOrder/*'];
		if($isadmin){
			$yingxiao_kanjia[] = ['name'=>'分享海报','path'=>'KanjiaPoster/index','authdata'=>'KanjiaPoster/*'];
			$yingxiao_kanjia[] = ['name'=>'系统设置','path'=>'KanjiaSet/index','authdata'=>'KanjiaSet/*'];
		}
		$yingxiao_child[] = ['name'=>'砍价活动','child'=>$yingxiao_kanjia];

		$yingxiao_seckill= [];
		//$yingxiao_seckill[] = ['name'=>'商品设置','path'=>'SeckillProset/index','authdata'=>'SeckillProset/*,ShopProduct/chooseproduct,ShopProduct/getproduct'];
		//$yingxiao_seckill[] = ['name'=>'秒杀列表','path'=>'SeckillList/index','authdata'=>'SeckillList/*'];
		$yingxiao_seckill[] = ['name'=>'商品列表','path'=>'SeckillProduct/index','authdata'=>'SeckillProduct/*,SeckillCode/*'];
		$yingxiao_seckill[] = ['name'=>'订单列表','path'=>'SeckillOrder/index','authdata'=>'SeckillOrder/*'];
		$yingxiao_seckill[] = ['name'=>'用户评价','path'=>'SeckillComment/index','authdata'=>'SeckillComment/*'];
		if($isadmin){
			$yingxiao_seckill[] = ['name'=>'秒杀设置','path'=>'SeckillSet/index','authdata'=>'SeckillSet/*'];
		}
		$yingxiao_child[] = ['name'=>'整点秒杀','child'=>$yingxiao_seckill];

		$yingxiao_tuangou = [];
		$yingxiao_tuangou[] = ['name'=>'商品管理','path'=>'TuangouProduct/index','authdata'=>'TuangouProduct/*,TuangouCode/*'];
		$yingxiao_tuangou[] = ['name'=>'订单管理','path'=>'TuangouOrder/index','authdata'=>'TuangouOrder/*'];
		$yingxiao_tuangou[] = ['name'=>'评价管理','path'=>'TuangouComment/index','authdata'=>'TuangouComment/*'];
		$yingxiao_tuangou[] = ['name'=>'商品分类','path'=>'TuangouCategory/index','authdata'=>'TuangouCategory/*'];
		if($isadmin){
			$yingxiao_tuangou[] = ['name'=>'分享海报','path'=>'TuangouPoster/index','authdata'=>'TuangouPoster/*'];
			$yingxiao_tuangou[] = ['name'=>'系统设置','path'=>'TuangouSet/index','authdata'=>'TuangouSet/*'];
		}
		$yingxiao_child[] = ['name'=>'团购活动','child'=>$yingxiao_tuangou];

		if($isadmin){
			$yingxiao_scoreshop = [];
			$yingxiao_scoreshop[] = ['name'=>'商品管理','path'=>'ScoreshopProduct/index','authdata'=>'ScoreshopProduct/*,ScoreshopCode/*'];
			$yingxiao_scoreshop[] = ['name'=>'订单管理','path'=>'ScoreshopOrder/index','authdata'=>'ScoreshopOrder/*'];
			$yingxiao_scoreshop[] = ['name'=>'商品分类','path'=>'ScoreshopCategory/index','authdata'=>'ScoreshopCategory/*'];
			$yingxiao_scoreshop[] = ['name'=>'分享海报','path'=>'ScoreshopPoster/index','authdata'=>'ScoreshopPoster/*'];
			$yingxiao_scoreshop[] = ['name'=>'系统设置','path'=>'ScoreshopSet/index','authdata'=>'ScoreshopSet/*'];
            $yingxiao_child[] = ['name'=>t('积分').'兑换','child'=>$yingxiao_scoreshop];

            //$yingxiao_hongbao = [];
			//$yingxiao_hongbao[] = ['name'=>'活动列表','path'=>'Hongbao/index','authdata'=>'Hongbao/*'];
			//$yingxiao_hongbao[] = ['name'=>'领取记录','path'=>'Hongbao/record','authdata'=>'Hongbao/*'];
			//$yingxiao_child[] = ['name'=>'微信红包','child'=>$yingxiao_hongbao];

		}else{
			}
		if($isadmin){
			$yingxiao_choujiang = [];
			$yingxiao_choujiang[] = ['name'=>'活动列表','path'=>'Choujiang/index','authdata'=>'Choujiang/*'];
			$yingxiao_choujiang[] = ['name'=>'抽奖记录','path'=>'Choujiang/record','authdata'=>'Choujiang/*'];
            $yingxiao_child[] = ['name'=>'抽奖活动','child'=>$yingxiao_choujiang];
            }else{
            }
        if($isadmin){
            }

		$short_video= [];
		$short_video[] = ['name'=>'分类列表','path'=>'ShortvideoCategory/index','authdata'=>'ShortvideoCategory/*'];
		$short_video[] = ['name'=>'视频列表','path'=>'Shortvideo/index','authdata'=>'Shortvideo/*'];
		$short_video[] = ['name'=>'评论列表','path'=>'ShortvideoComment/index','authdata'=>'ShortvideoComment/*'];
		$short_video[] = ['name'=>'回评列表','path'=>'ShortvideoCommentReply/index','authdata'=>'ShortvideoCommentReply/*'];
		if($isadmin){
			$short_video[] = ['name'=>'海报设置','path'=>'ShortvideoPoster/index','authdata'=>'ShortvideoPoster/*'];
			$short_video[] = ['name'=>'系统设置','path'=>'ShortvideoSysset/index','authdata'=>'ShortvideoSysset/*'];
		}
		$yingxiao_child[] = ['name'=>'短视频','child'=>$short_video];
        $yx_invite_cashback_video_receive_custom = getcustom('yx_invite_cashback_video_receive');
        //$fifa_child = [];
		//$fifa_child[] = ['name'=>'竞猜设置','path'=>'Fifa/set','authdata'=>'Fifa/*'];
		//$fifa_child[] = ['name'=>'竞猜记录','path'=>'Fifa/record','authdata'=>'Fifa/*'];
		//$fifa_child[] = ['name'=>'海报设置','path'=>'Fifa/posterset','authdata'=>'Fifa/*'];
		//$yingxiao_child[] = ['name'=>'世界杯竞猜','child'=>$fifa_child];

	    if($isadmin){
            }
        if(getcustom('yx_queue_free_multiple_no',$aid)){
            $queue_free_child[] = ['name'=>'多排队号','path'=>'QueueFreeMultipleNo','authdata'=>'QueueFreeMultipleNo','hide'=>true];
        }
        if(getcustom('yx_queue_free_business_shopkeeper',$aid) && bid == 0){
            $queue_free_child[] = ['name'=>'商户店主会员排队','path'=>'QueueFreeBusinessShopkeeper','authdata'=>'QueueFreeBusinessShopkeeper','hide'=>true];
        }
        $greenscore_max_custom = getcustom('greenscore_max');
        if(getcustom('yx_offline_subsidies',$aid)){
            if($isadmin){
                $yingxiao_offline = [];
                $yingxiao_offline[] = ['name'=>'数据统计','path'=>'OfflineSubsidies/log','authdata'=>'OfflineSubsidies/*'];
                $yingxiao_offline[] = ['name'=>'补助配置','path'=>'OfflineSubsidies/set','authdata'=>'OfflineSubsidies/*'];
                $yingxiao_child[] = ['name'=>'线下补助','child'=>$yingxiao_offline];
            }
        }
        if($isadmin){
            $ordercollectreward_child[] = ['name'=>'奖励记录','path'=>'OrderCollectReward/index','authdata'=>'OrderCollectReward/*'];
            $ordercollectreward_child[] = ['name'=>'奖励设置','path'=>'OrderCollectReward/set','authdata'=>'OrderCollectReward/*'];
            $yingxiao_child[] = ['name'=>'确认收货奖励','child'=>$ordercollectreward_child];
        }
		$menudata['yingxiao'] = ['name'=>'营销','fullname'=>'营销活动','icon'=>'my-icon my-icon-yingxiao','child'=>$yingxiao_child];
        $component_article = [];
		$component_article[] = ['name'=>'文章列表','path'=>'Article/index','authdata'=>'Article/*'];
		$component_article[] = ['name'=>'文章分类','path'=>'ArticleCategory/index','authdata'=>'ArticleCategory/*'];
		$component_article[] = ['name'=>'评论列表','path'=>'ArticlePinglun/index','authdata'=>'ArticlePinglun/*'];
		$component_article[] = ['name'=>'回评列表','path'=>'ArticlePlreply/index','authdata'=>'ArticlePlreply/*'];
		$component_article[] = ['name'=>'系统设置','path'=>'ArticleSet/set','authdata'=>'ArticleSet/*'];
		$component_child[] = ['name'=>'文章管理','child'=>$component_article];
        if($isadmin){
			$component_luntan = [];
			$component_luntan[] = ['name'=>'帖子列表','path'=>'Luntan/index','authdata'=>'Luntan/*'];
			$component_luntan[] = ['name'=>'分类管理','path'=>'LuntanCategory/index','authdata'=>'LuntanCategory/*'];
            $component_luntan[] = ['name'=>'评论列表','path'=>'LuntanPinglun/index','authdata'=>'LuntanPinglun/*'];
			$component_luntan[] = ['name'=>'回评列表','path'=>'LuntanPlreply/index','authdata'=>'LuntanPlreply/*'];
			$component_luntan[] = ['name'=>'系统设置','path'=>'Luntan/sysset','authdata'=>'Luntan/sysset'];
			$component_child[] = ['name'=>'用户论坛','child'=>$component_luntan];
		}
		if($isadmin){
			$component_sign = [];
			$component_sign[] = ['name'=>'签到记录','path'=>'Sign/record','authdata'=>'Sign/record,Sign/recordexcel,Sign/recorddel'];
			$component_sign[] = ['name'=>'签到设置','path'=>'Sign/set','authdata'=>'Sign/set'];
            $component_child[] = ['name'=>t('积分').'签到','child'=>$component_sign];
		}
		//预约服务
		$component_yuyue=[];
		$component_yuyue[] = ['name'=>'服务类目','path'=>'Yuyue/index','authdata'=>'Yuyue/*'];
		$component_yuyue[] = ['name'=>'服务商品','path'=>'YuyueList/index','authdata'=>'YuyueList/*'];
		$component_yuyue[] = ['name'=>$text['服务订单'],'path'=>'YuyueOrder/index','authdata'=>'YuyueOrder/*'];
		$component_yuyue[] = ['name'=>$text['商品服务'],'path'=>'YuyueFuwu/index','authdata'=>'YuyueFuwu/*'];
		$component_yuyue[] = ['name'=>$text['商品评价'],'path'=>'YuyueComment/index','authdata'=>'YuyueComment/*'];
		if($isadmin){
			$component_yuyue[] = ['name'=>'海报设置','path'=>'YuyuePoster/index','authdata'=>'YuyuePoster/*'];
		}
        //使用平台服务人员的，多商户不再管理人员
        $yuyueUserShow = true;
        if($yuyueUserShow){
            $component_yuyue[] = ['name'=>t('人员').'类型','path'=>'YuyueWorkerCategory/index','authdata'=>'YuyueWorkerCategory/*'];
            $component_yuyue[] = ['name'=>t('人员').'列表','path'=>'YuyueWorker/index','authdata'=>'YuyueWorker/*'];
            $component_yuyue[] = ['name'=>t('人员').'评价','path'=>'YuyueWorkerComment/index','authdata'=>'YuyueWorkerComment/*'];
        }
		$component_yuyue[] = ['name'=>'提成明细','path'=>'YuyueMoney/moneylog','authdata'=>'YuyueMoney/*'];
		$component_yuyue[] = ['name'=>'提现记录','path'=>'YuyueMoney/withdrawlog','authdata'=>'YuyueMoney/*'];
		$component_yuyue[] = ['name'=>'系统设置','path'=>'YuyueSet/set','authdata'=>'YuyueSet/*'];
		$component_child[] = ['name'=>'预约服务','child'=>$component_yuyue];

		$component_kecheng=[];
		$component_kecheng[] = ['name'=>'课程分类','path'=>'KechengCategory/index','authdata'=>'KechengCategory/*'];
		$component_kecheng[] = ['name'=>'课程列表','path'=>'KechengList/index','authdata'=>'KechengList/*,KechengRecord/*'];
		$component_kecheng[] = ['name'=>'课程章节','path'=>'KechengChapter/index','authdata'=>'KechengChapter/*'];
		$component_kecheng[] = ['name'=>'题库管理','path'=>'KechengTiku/index','authdata'=>'KechengTiku/*'];
		$component_kecheng[] = ['name'=>'课程订单','path'=>'KechengOrder/index','authdata'=>'KechengOrder/*'];
		$component_kecheng[] = ['name'=>'学习记录','path'=>'KechengStudylog/index','authdata'=>'KechengStudylog/*'];
		if($isadmin){
            $component_kecheng[] = ['name'=>'课程海报','path'=>'KechengPoster/index','authdata'=>'KechengPoster/*'];
			$component_kecheng[] = ['name'=>'课程设置','path'=>'KechengSet/index','authdata'=>'KechengSet/*'];
		}
		$component_child[] = ['name'=>'知识付费','child'=>$component_kecheng];
        $component_child[] = ['name'=>'视频管理','path'=>'VideoList/index','authdata'=>'VideoList/*'];


		if($isadmin){
			$component_peisong = [];
			$component_peisong[] = ['name'=>'配送员列表','path'=>'PeisongUser/index','authdata'=>'PeisongUser/*'];
			$component_peisong[] = ['name'=>'配送单列表','path'=>'PeisongOrder/index','authdata'=>'PeisongOrder/*'];
			$component_peisong[] = ['name'=>'评价列表','path'=>'PeisongComment/index','authdata'=>'PeisongComment/*'];
			$component_peisong[] = ['name'=>'提成明细','path'=>'PeisongMoney/moneylog','authdata'=>'PeisongMoney/*'];
			$component_peisong[] = ['name'=>'提现记录','path'=>'PeisongMoney/withdrawlog','authdata'=>'Peisong/*'];
			$component_peisong[] = ['name'=>'系统设置','path'=>'Peisong/set','authdata'=>'Peisong/*'];
			$component_peisong[] = ['name'=>t('码科跑腿').'对接','path'=>'Peisong/makeset','authdata'=>'Peisong/*'];
			$component_child[] = ['name'=>'同城配送','child'=>$component_peisong];
            if(in_array('wx',$platform)){
                $component_child[] = ['name'=>'物流助手','path'=>'Miandan/index','authdata'=>'Miandan/*'];
            }
			$component_tp = [];
			$component_tp[] = ['name'=>'活动列表','path'=>'Toupiao/index','authdata'=>'Toupiao/*'];
			$component_tp[] = ['name'=>'选手列表','path'=>'Toupiao/joinlist','authdata'=>'Toupiao/*'];
			$component_tp[] = ['name'=>'投票记录','path'=>'Toupiao/helplist','authdata'=>'Toupiao/*'];
            $component_tp[] = ['name'=>'投票分组','path'=>'ToupiaoGroup/index','authdata'=>'ToupiaoGroup/*'];
			//$component_tp[] = ['name'=>'投票设置','path'=>'Toupiao/set','authdata'=>'Toupiao/*'];
			$component_child[] = ['name'=>'投票活动','child'=>$component_tp];
		}else{
           
            if(getcustom('express_maiyatian') || getcustom('express_tongcheng_business')){
                $component_peisong = [];
                $component_peisong[] = ['name'=>'配送单列表','path'=>'PeisongOrder/index','authdata'=>'PeisongOrder/*'];
            }

            if(getcustom('express_maiyatian') || getcustom('express_tongcheng_business') || getcustom('wx_express_intracity')){
                $component_child[] = ['name'=>'同城配送','child'=>$component_peisong];
            }               
        }

		$product_thali = false;
        $yingxiao_cycle = [];
		$yingxiao_cycle[] = ['name'=>'商品管理','path'=>'CycleProduct/index','authdata'=>'CycleProduct/*,CycleCode/*'];
		$yingxiao_cycle[] = ['name'=>'订单管理','path'=>'CycleOrder/index','authdata'=>'CycleOrder/*'];
		$yingxiao_cycle[] = ['name'=>'配送管理','path'=>'CycleOrder/cycle_list&status=1','authdata'=>'CycleOrder/*'];
		$yingxiao_cycle[] = ['name'=>'评价管理','path'=>'CycleComment/index','authdata'=>'CycleComment/*'];
		if($isadmin){
			$yingxiao_cycle[] = ['name'=>'商品分类','path'=>'CycleCategory/index','authdata'=>'CycleCategory/*'];
			$yingxiao_cycle[] = ['name'=>'分享海报','path'=>'CyclePoster/index','authdata'=>'CyclePoster/*'];
			$yingxiao_cycle[] = ['name'=>'系统设置','path'=>'CycleSet/index','authdata'=>'CycleSet/*'];
		}
		$component_child[] = ['name'=>'周期购','child'=>$yingxiao_cycle];

		if($isadmin){
			$component_mingpian=[];
			$component_mingpian[] = ['name'=>'名片列表','path'=>'Mingpian/index','authdata'=>'Mingpian/*'];
			$component_mingpian[] = ['name'=>'系统设置','path'=>'Mingpian/set','authdata'=>'Mingpian/*'];
			$component_child[] = ['name'=>'名片','child'=>$component_mingpian];
		}

        $cashier_child = [];
        $cashier_child[] = ['name' => '收银设置', 'path' => 'CashierSet/index', 'authdata' => 'CashierSet/*'];
        $cashier_child[] = ['name' => '收银台操作', 'path' => 'Cashier/index', 'authdata' => 'Cashier/*','hide'=>true];
        $cashier_child[] = ['name' => '收银订单', 'path' => 'CashierOrder/index', 'authdata' => 'CashierOrder/*'];
        $cashier_child[] = ['name' => '订单统计', 'path' => 'CashierOrder/tongji', 'authdata' => 'CashierOrder/*'];
        $component_child[] = ['name'=>'收银台','child'=>$cashier_child];
        $component_child[] = ['name'=>t('自定义表单'),'path'=>'Form/index','authdata'=>'Form/*'];
        if($isadmin){
            $lipin_child = [];
            $lipin_child[] = ['name' => '礼品卡', 'path' => 'Lipin/index', 'authdata' => 'Lipin/*'];
            $lipin_child[] = ['name' => '礼品卡兑换码', 'path' => 'Lipin/codelist', 'authdata' => 'Lipin/codelist'];
            $lipin_child[] = ['name'=>'兑换码生成','path'=>'Lipin/makecode','authdata'=>'Lipin/makecode','hide'=>true];
            $lipin_child[] = ['name'=>'兑换码导入','path'=>'Lipin/importexcel','authdata'=>'Lipin/importexcel','hide'=>true];
            $lipin_child[] = ['name'=>'兑换码导出','path'=>'Lipin/codelistexcel','authdata'=>'Lipin/codelistexcel','hide'=>true];
            $lipin_child[] = ['name'=>'兑换码修改状态','path'=>'Lipin/setst','authdata'=>'Lipin/setst','hide'=>true];
            $lipin_child[] = ['name'=>'兑换码删除','path'=>'Lipin/codelistdel','authdata'=>'Lipin/codelistdel','hide'=>true];
            $lipin_child[] = ['name' => '礼品卡分类', 'path' => 'LipinCategory/index', 'authdata' => 'LipinCategory/*'];
            $component_child[] = ['name'=>'礼品卡','child'=>$lipin_child];
            $exchange_card_product_select_more = getcustom('extend_exchange_card_product_select_more');
            $extend_exchange_card_yuyue_send = getcustom('extend_exchange_card_yuyue_send');
            if(in_array('wx',$platform)){
				$component_child[] = ['name'=>'小程序直播','path'=>'Live/index','authdata'=>'Live/*'];
				/*
				$component_child[] = ['name'=>'视频号接入','child'=>[
					['name'=>'申请接入','path'=>'Wxvideo/apply','authdata'=>'Wxvideo/*'],
					['name'=>'商家信息','path'=>'Wxvideo/setinfo','authdata'=>'Wxvideo/*'],
					['name'=>'商品管理','path'=>'ShopProduct/index&fromwxvideo=1','authdata'=>'ShopProduct/*,ShopCode/*'],
					['name'=>'订单管理','path'=>'ShopOrder/index&fromwxvideo=1','authdata'=>'ShopOrder/*'],
					['name'=>'退款申请','path'=>'ShopRefundOrder/index&fromwxvideo=1','authdata'=>'ShopRefundOrder/*'],
					['name'=>'我的类目','path'=>'Wxvideo/category','authdata'=>'Wxvideo/*'],
					['name'=>'我的品牌','path'=>'Wxvideo/brand','authdata'=>'Wxvideo/*'],
					['name'=>'同步修复','path'=>'Wxvideo/deliverytongbu','authdata'=>'Wxvideo/*'],
				]];
				*/
			}
            // if(in_array('toutiao',$platform)){
			// 	$component_child[] = ['name'=>'抖音接入','child'=>[
			// 		['name'=>'接入配置','path'=>'DouyinSet/index','authdata'=>'Douyin/*'],
			// 		['name'=>'商品管理','path'=>'ShopProduct/index&fromdouyin=1','authdata'=>'ShopProduct/*,ShopCode/*'],
			// 		['name'=>'商品管理','path'=>'DouyinProduct/index','authdata'=>'DouyinProduct/*'],
			// 	]];
			// }
            }
        if($isadmin) {
            }

        if($isadmin) {
            }
        //海康摄像机设置
        $qrcode_fenzhang =   getcustom('extend_qrcode_variable_fenzhang');
        $douyin_groupbuy_goods_custom = getcustom('douyin_groupbuy_goods');
        $materialSet = getcustom('extend_material_custom');
        $menudata['component'] = ['name'=>'扩展','fullname'=>'扩展功能','icon'=>'my-icon my-icon-kuozhan','child'=>$component_child];
		if(getcustom('restaurant')){
			$menudata['restaurant'] = \app\custom\Restaurant::getmenu($isadmin);
		}

		 $jiemian_child = [];
        if(false){}else{
			$jiemian_child[] = ['name'=>'页面设计','path'=>'DesignerPage/index','authdata'=>'DesignerPage/*'];
		}
        if($isadmin){
            $jiemian_child[] = ['name'=>'底部导航','path'=>'DesignerMenu/index','authdata'=>'DesignerMenu/*'];
            $jiemian_child[] = ['name'=>'内页导航','path'=>'DesignerMenu/menu2','authdata'=>'DesignerMenu/*'];
			$jiemian_child[] = ['name'=>'商品详情','path'=>'DesignerMenuShopdetail/shopdetail','authdata'=>'DesignerMenuShopdetail/*'];
        }else{
            $jiemian_child[] = ['name'=>'底部导航','path'=>'DesignerMenu/menu2','authdata'=>'DesignerMenu/*'];
			$jiemian_child[] = ['name'=>'商品详情','path'=>'DesignerMenuShopdetail/shopdetail','authdata'=>'DesignerMenuShopdetail/*'];
        }
        if($isadmin){
        	$jiemian_child[] = ['name'=>'登录页面','path'=>'DesignerLogin/index','authdata'=>'DesignerLogin/*'];
            $jiemian_child[] = ['name'=>'移动端后台','path'=>'DesignerMobile/index','authdata'=>'DesignerMobile/*'];
        }
        $jiemian_child[] = ['name'=>'分享设置','path'=>'DesignerShare/index','authdata'=>'DesignerShare/*'];
        $jiemian_child[] = ['name'=>'链接地址','path'=>'DesignerPage/chooseurl','params'=>'/type/geturl','authdata'=>'DesignerPage/chooseurl,DesignerPage/getwxqrcode'];
        
        $menudata['jiemian'] = ['name'=>'设计','fullname'=>'界面设计','icon'=>'my-icon my-icon-sheji','child'=>$jiemian_child];

        if($isadmin){
			$pingtai_child = [];
			if(in_array('mp',$platform)){
				$pingtai_child_mp = [];
				$pingtai_child_mp[] = ['name'=>'公众号绑定','path'=>'Binding/index','authdata'=>'Binding/*'];
				$pingtai_child_mp[] = ['name'=>'菜单设置','path'=>'Mpmenu/index','authdata'=>'Mpmenu/*'];
				$pingtai_child_mp[] = ['name'=>'支付设置','path'=>'Mppay/set','authdata'=>'Mppay/*'];
                $pingtai_child_mp[] = ['name'=>'随行付分账','path'=>'SxpayFenzhang/*','authdata'=>'SxpayFenzhang/*','hide'=>true];
				$pingtai_child_mp[] = ['name'=>'模板消息设置','path'=>'Mptmpl/tmplset','authdata'=>'Mptmpl/*'];
                $pingtai_child_mp[] = ['name'=>'类目模板消息','path'=>'Mptmpl/tmplsetNew','authdata'=>'Mptmpl/tmplsetNew'];
				$pingtai_child_mp[] = ['name'=>'已添加的模板','path'=>'Mptmpl/mytmpl','authdata'=>'Mptmpl/*'];
				$pingtai_child_mp[] = ['name'=>'被关注回复','path'=>'Mpkeyword/subscribe','authdata'=>'Mpkeyword/*'];
				$pingtai_child_mp[] = ['name'=>'关键字回复','path'=>'Mpkeyword/index','authdata'=>'Mpkeyword/*'];
				$pingtai_child_mp[] = ['name'=>'粉丝列表','path'=>'Mpfans/fanslist','authdata'=>'Mpfans/*'];
				//$pingtai_child_mp[] = ['name'=>'素材管理','path'=>'Mpfans/sourcelist','authdata'=>'Mpfans/*'];
				$pingtai_child_mp[] = ['name'=>'模板消息群发','path'=>'Mpfans/tmplsend','authdata'=>'Mpfans/*'];
				//$pingtai_child_mp[] = ['name'=>'活跃粉丝群发','path'=>'Mpfans/kfmsgsend','authdata'=>'Mpfans/*'];
				$pingtai_child[] = ['name'=>'微信公众号','child'=>$pingtai_child_mp];
				$pingtai_child_mpcard = [];
				$pingtai_child_mpcard[] = ['name'=>'领取记录','path'=>'Membercard/record','authdata'=>'Membercard/record'];
				$pingtai_child_mpcard[] = ['name'=>'会员卡/创建','path'=>'Membercard/index','authdata'=>'Membercard/*'];
                $pingtai_child[] = ['name'=>'微信会员卡','child'=>$pingtai_child_mpcard];
			}
			if(in_array('wx',$platform)){
				$pingtai_child_wx = [];
				$pingtai_child_wx[] = ['name'=>'小程序绑定','path'=>'Binding/index','authdata'=>'Binding/*'];
				$pingtai_child_wx[] = ['name'=>'支付设置','path'=>'Wxpay/set','authdata'=>'Wxpay/*'];
				$pingtai_child_wx[] = ['name'=>'订阅消息设置','path'=>'Wxtmpl/tmplset','authdata'=>'Wxtmpl/*'];
				$pingtai_child_wx[] = ['name'=>'服务类目','path'=>'Wxleimu/index','authdata'=>'Wxleimu/*'];
				$pingtai_child_wx[] = ['name'=>'外部链接','path'=>'Wxurl/index','authdata'=>'Wxurl/*'];
				//$pingtai_child_wx[] = ['name'=>'关键字回复','path'=>'Wxkeyword/index','authdata'=>'Wxkeyword/*'];
                $pingtai_child_wx[] = ['name'=>'半屏小程序','path'=>'Wxembedded/index','authdata'=>'Wxembedded/*'];
				$pingtai_child[] = ['name'=>'微信小程序','child'=>$pingtai_child_wx];
			}
			if(in_array('alipay',$platform)){
				$pingtai_child[] = ['name'=>'支付宝小程序','path'=>'Binding/alipay','authdata'=>'Binding/*'];
			}
			if(in_array('baidu',$platform)){
				$pingtai_child[] = ['name'=>'百度小程序','path'=>'Binding/baidu','authdata'=>'Binding/*'];
			}
			if(in_array('toutiao',$platform)){
				$pingtai_child[] = ['name'=>'抖音小程序','path'=>'Binding/toutiao','authdata'=>'Binding/*'];
			}
			if(in_array('qq',$platform)){
				$pingtai_child[] = ['name'=>'QQ小程序','path'=>'Binding/qq','authdata'=>'Binding/*'];
			}
			if(in_array('h5',$platform)){
				$pingtai_child[] = ['name'=>'手机H5','path'=>'Binding/h5','authdata'=>'Binding/*'];
			}
			if(in_array('app',$platform)){
				$pingtai_child[] = ['name'=>'手机APP','path'=>'Binding/app','authdata'=>'Binding/*'];
			}
			if(getcustom('wx_channels')){
                $pingtai_child_channels = [];
                $pingtai_child_channels[] = ['name'=>'小店绑定','path'=>'WxChannels/bind','authdata'=>'WxChannels/*'];
                $pingtai_child_channels[] = ['name'=>'商品管理','path'=>'WxChannelsProduct/index','authdata'=>'WxChannelsProduct/*'];
                $pingtai_child_channels[] = ['name'=>'商品类目','path'=>'WxChannelsCategory/index','authdata'=>'WxChannelsCategory/*'];
                $pingtai_child_channels[] = ['name'=>'品牌库','path'=>'WxChannelsBrand/index','authdata'=>'WxChannelsBrand/*'];
                $pingtai_child_channels[] = ['name'=>'订单管理','path'=>'WxChannelsOrder/index','authdata'=>'WxChannelsOrder/*'];
                $pingtai_child_channels[] = ['name'=>'售后管理','path'=>'WxChannelsAfterSales/index','authdata'=>'WxChannelsAfterSales/*'];
                $pingtai_child_channels[] = ['name'=>'地址管理','path'=>'WxChannelsAddress/index','authdata'=>'WxChannelsAddress/*'];
                $pingtai_child_channels[] = ['name'=>'行政区域','path'=>'WxChannelsArea/index','authdata'=>'WxChannelsArea/*'];
                $pingtai_child_channels[] = ['name'=>'电子面单','path'=>'WxChannelsEwaybill/index','authdata'=>'WxChannelsMiandan/*'];
                $pingtai_child_channels[] = ['name'=>'运费模板','path'=>'WxChannelsFreight/index','authdata'=>'WxChannelsFreight/*'];
                $pingtai_child_channels[] = ['name'=>'优惠券','path'=>'WxChannelsCoupon/index','authdata'=>'WxChannelsCoupon/*'];
                $pingtai_child_channels[] = ['name'=>'分享员','path'=>'WxChannelsSharer/index','authdata'=>'WxChannelsSharer/*'];
                $pingtai_child_channels[] = ['name'=>'结算账户','path'=>'WxChannelsBankacct/index','authdata'=>'WxChannelsBankacct/*'];
                $pingtai_child_channels[] = ['name'=>'资金结算','path'=>'WxChannelsFundsflow/index','authdata'=>'WxChannelsFundsflow/*'];
                $pingtai_child_channels[] = ['name'=>'预约直播设置','path'=>'WxChannelsLiveSet/set','authdata'=>'WxChannelsLiveSet/*'];
                $pingtai_child[] = ['name'=>'视频号小店','child'=>$pingtai_child_channels];
            }
			if($pingtai_child)
			    $menudata['pingtai'] = ['name'=>'平台','fullname'=>'平台设置','icon'=>'my-icon my-icon-pingtai','child'=>$pingtai_child];
		}

		// API管理菜单 - 仅平台管理员可见
		if($isadmin && $uid != -1){
			$api_child = [];
			$api_child[] = ['name'=>'接口列表','path'=>'ApiManage/index','authdata'=>'ApiManage/index,ApiManage/detail,ApiManage/edit'];
			$api_child[] = ['name'=>'接口扫描','path'=>'ApiManage/scan','authdata'=>'ApiManage/scan,ApiManage/savescan'];
			$api_child[] = ['name'=>'测试历史','path'=>'ApiManage/testlog','authdata'=>'ApiManage/testlog,ApiManage/testlogdetail'];
			// 隐藏菜单项
			$api_child[] = ['name'=>'在线测试','path'=>'ApiManage/test','authdata'=>'ApiManage/test,ApiManage/sendtest','hide'=>true];
			$api_child[] = ['name'=>'文档导出','path'=>'ApiManage/export','authdata'=>'ApiManage/export','hide'=>true];
			$menudata['api'] = ['name'=>'API','fullname'=>'API管理','icon'=>'my-icon my-icon-api','child'=>$api_child];
		}

		$system_child = [];
		$system_child[] = ['name'=>'系统设置','path'=>'Backstage/sysset','authdata'=>'Backstage/sysset'];
        
		// API Key配置 - 平台管理员上下文在此添加（auth_type=1不受权限过滤影响）
		// 商户上下文的MerchantApiKey菜单在权限过滤之后添加，避免被auth_data过滤掉
		if($isadmin && $bid == 0){
			$system_child[] = ['name'=>'API Key配置','path'=>'SystemApiKey/index','authdata'=>'SystemApiKey/*'];
		}
        
		$system_child[] = ['name'=>'门店管理','path'=>'Mendian/index','authdata'=>'Mendian/*'];
		$system_child[] = ['name'=>'管理员列表','path'=>'User/index','authdata'=>'User/*,UserGroup/*'];
		$system_child[] = ['name'=>'配送方式','path'=>'Freight/index','authdata'=>'Freight/*'];
		$system_child[] = ['name'=>'快递设置','path'=>'ExpressData/index','authdata'=>'ExpressData/*'];
        $system_child[] = ['name'=>'送货单设置','path'=>'ShdSet/index','authdata'=>'ShdSet/*'];
		$system_child[] = ['name'=>'小票打印机','path'=>'Wifiprint/index','authdata'=>'Wifiprint/*'];
        if($isadmin){
			$system_child[] = ['name'=>'短信设置','path'=>'Sms/set','authdata'=>'Sms/*'];
           
		}
        $system_child[] = ['name'=>'店铺评价','path'=>'BusinessComment/index','authdata'=>'BusinessComment/*'];
        if($isadmin) {
            $wanyue10086 = getcustom('wanyue10086');
            }
        $system_child[] = ['name'=>'操作日志','path'=>'Backstage/plog','authdata'=>'Backstage/plog'];
        // aid == 1不可移除
        $system_child[] = ['name'=>'快捷菜单','path'=>'ShortcutMenu/index','authdata'=>'ShortcutMenu/*'];
        $system_child[] = ['name'=>'定制短信通道','path'=>'SmsChannel/index','authdata'=>'SmsChannel/*','hide' => true,'description' => '定制的短信通道说明：由卖家信平台的短信通道方提供相关代码和售后与业务支持，与当前系统开发方无关。使用中任何问题与当前系统开发方无关，默认不开启，开启则同意本系统开发方免责。开启后商家在短信配置页面，可在短信接口选择处选择“定制短信通道”，选择后新增几个短信模板，这些新增的模板，阿里云和腾讯云可能以防诈名义拦载，此接口不拦载。本通道丰要适用干有营销需求的大客户，审核相对宽松，但是充值金额每次最少5000元，目有接口费。'];
        
		$menudata['system'] = ['name'=>'系统','fullname'=>'系统设置','icon'=>'my-icon my-icon-sysset','child'=>$system_child];
        if($user && $user['auth_type']==0){
			if($user['groupid']){
				$user['auth_data'] = Db::name('admin_user_group')->where('id',$user['groupid'])->value('auth_data');
			}

			$auth_data = json_decode($user['auth_data'],true);
			foreach($menudata as $k=>$v){
				if($v['child']){
                    $needcheckchild = true;//需要检验子权限
                    if($needcheckchild){
                        foreach($v['child'] as $k1=>$v1){
                            if(!$v1['authdata'] && $v1['child']){
                                $path = array();
                                foreach($v1['child'] as $k2=>$v2){
                                    if(!in_array($v2['path'].','.$v2['authdata'],$auth_data)){
                                        unset($menudata[$k]['child'][$k1]['child'][$k2]);
                                    }
                                }
                                if(count($menudata[$k]['child'][$k1]['child'])==0){
                                    unset($menudata[$k]['child'][$k1]);
                                }
                            }else{
                                $path = $v1['path'].','.$v1['authdata'];
                                if(!in_array($path,$auth_data)){
                                    unset($menudata[$k]['child'][$k1]);
                                }
                            }
                        }
                        if(count($menudata[$k]['child'])==0){
                            unset($menudata[$k]);
                        }
                    }
				}else{
					if(!in_array($v['path'].','.$v['authdata'],$auth_data)){
						unset($menudata[$k]);
					}
				}
			}
		}else{
            foreach($menudata as $k=>$v){
                if($v['child']){
                    foreach($v['child'] as $k1=>$v1){
                        if($v1['child']){
                            if(count($menudata[$k]['child'][$k1]['child'])==0){
                                unset($menudata[$k]['child'][$k1]);
                            }
                        }
                    }
                }else{
                    if(count($menudata[$k]['child'])==0){
                        unset($menudata[$k]);
                    }
                }
            }
        }
		// PC端配置菜单 - 在权限过滤之后强制添加到平台设置中
		// 因为新增菜单项不在现有管理员的auth_data中，放在过滤前会被auth_type==0的子管理员过滤掉
		if($isadmin && isset($menudata['pingtai'])){
			$pcItem = ['name'=>'PC端','path'=>'Binding/pc','authdata'=>'Binding/*'];
			// 找到插入位置：优先在手机APP之后，其次手机H5之后，否则末尾
			$existingChildren = array_values($menudata['pingtai']['child']);
			$insertAfter = -1;
			foreach($existingChildren as $idx => $child){
				if(isset($child['name'])){
					if($child['name'] == '手机APP'){ $insertAfter = $idx; break; }
					if($child['name'] == '手机H5'){ $insertAfter = $idx; }
				}
			}
			if($insertAfter >= 0){
				array_splice($existingChildren, $insertAfter + 1, 0, [$pcItem]);
			} else {
				$existingChildren[] = $pcItem;
			}
			$menudata['pingtai']['child'] = $existingChildren;
		}

		// 商户API Key配置 - 在权限过滤之后添加，确保始终对商户可见
		// 因为新增菜单项不在现有商户管理员的auth_data中，放在过滤前会被移除
		if($bid > 0 && isset($menudata['system'])){
			$apiKeyItem = ['name'=>'API Key配置','path'=>'MerchantApiKey/index','authdata'=>'MerchantApiKey/*'];
			$newChild = [];
			$inserted = false;
			if(isset($menudata['system']['child'])){
				foreach($menudata['system']['child'] as $child){
					$newChild[] = $child;
					if(!$inserted && isset($child['path']) && $child['path'] == 'Backstage/sysset'){
						$newChild[] = $apiKeyItem;
						$inserted = true;
					}
				}
			}
			if(!$inserted) $newChild[] = $apiKeyItem;
			$menudata['system']['child'] = $newChild;
		}

		return $menudata;
	}

    public static function getdata2($uid=0){
        $menudata = [];
        $menudata['user'] = ['name'=>'用户列表','path'=>'WebUser/index'];
        $menudata['wxpayset'] = ['name'=>'服务商配置','path'=>'WebSystem/wxpayset'];
        $menudata['wxpaylog'] = ['name'=>'微信支付记录','path'=>'WebSystem/wxpaylog'];
        $menudata['component'] = ['name'=>'开放平台设置','path'=>'WebSystem/component'];
        $child = [];
        $child[] = ['name'=>'系统设置','path'=>'WebSystem/set'];
        $menudata['sysset'] = ['name'=>'系统设置','path'=>'WebSystem/set','child'=>$child];
        
        // 模型广场菜单 - 位于系统设置和附件设置之间
        $model_square_child = [];
        $model_square_child[] = ['name'=>'供应商管理','path'=>'WebModelSquare/provider_list'];
        $model_square_child[] = ['name'=>'模型类型','path'=>'WebModelSquare/type_list'];
        $model_square_child[] = ['name'=>'模型列表','path'=>'WebModelSquare/model_list'];
        $menudata['model_square'] = ['name'=>'模型广场','fullname'=>'模型广场','path'=>'WebModelSquare/provider_list','child'=>$model_square_child];
        
        $menudata['remote'] = ['name'=>'附件设置','path'=>'WebSystem/remote'];
        $menudata['help'] = ['name'=>'帮助中心','path'=>'WebHelp/index'];
        $menudata['webnotice'] = ['name'=>'通知公告','path'=>'WebNotice/index'];
        $menudata['upgrade'] = ['name'=>'系统升级','path'=>'WebUpgrade/index'];
        return $menudata;
    }

	//白名单 不校验权限
	public static function blacklist(){
		$data = [];
		$data[] = 'Backstage/index';
		$data[] = 'Backstage/welcome';
		$data[] = 'Backstage/welcomeOld';
		$data[] = 'Backstage/setpwd';
		$data[] = 'Backstage/about';
		$data[] = 'Help/*';
		$data[] = 'Upload/*';
		$data[] = 'DesignerPage/chooseurl';
        $data[] = 'DesignerPage/getwxqrcode';
		$data[] = 'Peisong/getpeisonguser';
		$data[] = 'Peisong/peisong';
		$data[] = 'Miandan/addorder';
		$data[] = 'Wxset/*';
		$data[] = 'Notice/*';
		$data[] = 'notice/*';
		$data[] = 'SxpayIncome/*';
		$data[] = 'Member/inputlockpwd';
		$data[] = 'MemberLevel/inputlockpwd';
		$data[] = 'ShopProduct/inputlockpwd';
		$data[] = 'Member/dolock';
		$data[] = 'MemberLevel/dolock';
		$data[] = 'ShopProduct/dolock';
		$data[] = 'MemberArchives/*';
		$data[] = 'Map/*';
		$data[] = 'DesignerPage/choosezuobiao';
		$data[] = 'MerchantApiKey/*'; // 商户API Key配置 - 商户始终可访问
		$data[] = 'Binding/pc'; // PC端支付配置 - 平台管理员始终可访问
        return $data;
	}

    public static function fuwu_auth(){
	    }
    public static function fuwu_wxauth(){
        }

}

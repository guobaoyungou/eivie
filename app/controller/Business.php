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
// | 商家管理
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Business extends Common
{
	public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无操作权限');
	}
	//列表
    public function index(){
        $refreshYeji = 1;
        if($refreshYeji){
            $this->refreshYeji();
        }
		if(request()->isAjax()){
			$page = input('param.page/d',1);
			$limit = input('param.limit/d',10);
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id desc';
			}
			$where = array();
			$where[] = ['aid','=',aid];
            if(input('param.id')) $where[] = ['id','=',input('param.id/d')];
			if(input('param.cid')) $where[] = Db::raw("find_in_set(".input('param.cid/d').",cid)");
			if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
			if(input('?param.status') && input('param.status')!==''){
				$where[] = ['status','=',input('param.status')];
			}
            if(input('?param.is_open') && input('param.is_open')!==''){
                $where[] = ['is_open','=',input('param.is_open')];
            }
            $bset = Db::name('business_sysset')->where('aid',aid)->find();
			$count = 0 + Db::name('business')->where($where)->count();
			$carr = Db::name('business_category')->where('aid',aid)->column('name','id');
			$data = Db::name('business')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
				$cnames = [];
				if($v['cid']){
					$cids = explode(',',$v['cid']);
					foreach($cids as $cid){
						$cnames[] = $carr[$cid];
					}
				}
				$data[$k]['cname'] = implode(',',$cnames);
				if($v['mid']){
					$member = Db::name('member')->where('id',$v['mid'])->find();
					$data[$k]['nickname'] = $member['nickname'];
					$data[$k]['headimg'] = $member['headimg'];
                    }else{
					$data[$k]['nickname'] = '';
					$data[$k]['headimg'] = '';
				}
                $independent = 0;
                $maidan_new_custom = getcustom('maidan_new');
                }

			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		//分类
		$clist = Db::name('business_category')->Field('id,name')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
		$bset = Db::name('business_sysset')->where('aid',aid)->find();
        View::assign('clist',$clist);
		View::assign('bset',$bset);

		View::assign('isadmin',$this->user['isadmin']);

        //商户操作权限
        $handle_auth = [
            'recharge' => 1,//充值
            'edit' => 1,//编辑
        ];
        if ($this->user['isadmin'] == 3){
            $handle_auth['recharge'] = 0;
        }
        View::assign('handle_auth',$handle_auth);
		return View::fetch();
    }
    //异步获取统计数据
    public function business_statistics(){
        $where = array();
        $where[] = ['aid','=',aid];
        if(input('param.id')) $where[] = ['id','=',input('param.id/d')];
        if(input('param.cid')) $where[] = Db::raw("find_in_set(".input('param.cid/d').",cid)");
        if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
        if(input('?param.status') && input('param.status')!==''){
            $where[] = ['status','=',input('param.status')];
        }
        if(input('?param.is_open') && input('param.is_open')!==''){
            $where[] = ['is_open','=',input('param.is_open')];
        }
        $total = [];
        return json(['code'=>0,'msg'=>'查询成功','totaldata'=>$total]);
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('business')->where('aid',aid)->where('id',input('param.id/d'))->find();
			if($info){
				$uinfo = Db::name('admin_user')->where('aid',aid)->where('bid',$info['id'])->where('isadmin',1)->find();
                }else{
				$uinfo = [];
			}
            }else{
			$info = array('id'=>'','cid'=>'0');
            $info['feepercent'] = Db::name('business_sysset')->where('aid',aid)->value('default_rate');
            $uinfo = [];
		}
		$info['cid'] = explode(',',$info['cid']);

		$auth_data = $uinfo ? json_decode($uinfo['auth_data'],true) : array();
		if(!$auth_data) $auth_data = array();

		//分类
		$clist = Db::name('business_category')->Field('id,name')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
		// 传递 $info['id'] 作为 bid 参数，以便 Menu::getdata 正确判断AI旅拍菜单显示
		$menudata = \app\common\Menu::getdata(aid,-1,false,0,$info['id']?:0);

        $wxauth_data = $uinfo ? json_decode($uinfo['wxauth_data'],true) : array();
        if(!$wxauth_data) $wxauth_data = array();
        $notice_auth_data = $uinfo ? json_decode($uinfo['notice_auth_data'],true) : array();
        if(!$notice_auth_data) $notice_auth_data = array();
        $hexiao_auth_data = $uinfo ? json_decode($uinfo['hexiao_auth_data'],true) : array();
        if(!$hexiao_auth_data) $hexiao_auth_data = array();
        $wxauth_data = $uinfo ? json_decode($uinfo['wxauth_data'],true) : array();
        if(!$wxauth_data) $wxauth_data = array();

        $set = Db::name('business_sysset')->where('aid',aid)->find();
        $submchidlength = 0;
		//买单分销
        View::assign('auth_data',$auth_data);
        View::assign('admin_auth_data',$this->auth_data);
        View::assign('notice_auth_data',$notice_auth_data);
        View::assign('hexiao_auth_data',$hexiao_auth_data);
        View::assign('wxauth_data',$wxauth_data);
		View::assign('menudata',$menudata);
		View::assign('clist',$clist);
		View::assign('info',$info);
		View::assign('uinfo',$uinfo);
		View::assign('set',$set);

        View::assign('thisuser',$this->user);
        View::assign('thisuser_showtj',$this->user['showtj']==1 || $this->user['isadmin']>0 ? 1 : 0);
        View::assign('thisuser_mdid',$this->user['mdid']);
        View::assign('thisuser_wxauth',json_decode($this->user['wxauth_data'],true));
        View::assign('thisuser_notice_auth',json_decode($this->user['notice_auth_data'],true));
        View::assign('thisuser_hexiao_auth',json_decode($this->user['hexiao_auth_data'],true));
        View::assign('restaurant_auth',strpos($this->user['wxauth_data'],'restaurant') !== false ? true : false);
        View::assign('thisuserid',$this->user['id']);

        $sysset = Db::name('admin_set')->where('aid',aid)->find();
        $maidan_fenhong_new = 0;
        View::assign('maidan_fenhong_new',$maidan_fenhong_new);

        $queue_free_business_mode = getcustom('yx_queue_free_business_mode') && getcustom('yx_queue_free_other_mode');
        $wx_express_intracity = 0;
        View::assign('wx_express_intracity',$wx_express_intracity);

		return View::fetch();
	}
	public function save(){
		$info = input('post.info/a');
        $uinfo = input('post.uinfo/a');
		$uinfo['auth_data'] = str_replace('^_^','\/*',jsonEncode(input('post.auth_data/a')));
        $uinfo['notice_auth_data'] = jsonEncode(input('post.notice_auth_data/a'));
        $uinfo['hexiao_auth_data'] = jsonEncode(input('post.hexiao_auth_data/a'));
        $uinfo['wxauth_data'] = jsonEncode(input('post.wxauth_data/a'));
        $info['endtime'] = strtotime($info['endtime']);

        //买单分销
        $hasun = Db::name('admin_user')->where('id','<>',$uinfo['id'])->where('un',$uinfo['un'])->find();
		if($hasun){
			return json(['status'=>0,'msg'=>'该账号已存在']);
		}
        if($info['latitude'] && $info['longitude'] && !$info['district']){
            //通过坐标获取省市区
            $mapqq = new \app\common\MapQQ();
            $address_component = $mapqq->locationToAddress($info['latitude'],$info['longitude']);
            if($address_component && $address_component['status']==1){
                $info['province'] = $address_component['province'];
                $info['city'] = $address_component['city'];
                $info['district'] = $address_component['district'];
            }
        }

        if($info['id']){
			$bid = $info['id'];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['id','=',$info['id']];
            $pass = 0;
            Db::name('business')->where($where)->update($info);
			if($uinfo['pwd']!=''){
				$uinfo['pwd'] = md5($uinfo['pwd']);
			}else{
				unset($uinfo['pwd']);
			}
			$uwhere = [];
			$uwhere[]  =['aid','=',aid];
			$uwhere[]  =['id','=',$uinfo['id']];
			if(!getcustom('admin_user_multi_business')){
			    //管理员管理多个商户时，不使用bid
                $uwhere[]  =['bid','=',$info['id']];
            }
			Db::name('admin_user')->where($uwhere)->update($uinfo);
            $independent = 0;
			\app\common\System::plog('修改商户'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
            $bid = Db::name('business')->insertGetId($info);
			
			$uinfo['aid'] = aid;
			$uinfo['bid'] = $bid;
			//$uinfo['auth_type'] = 1;
			$uinfo['pwd'] = md5($uinfo['pwd']);
			$uinfo['createtime'] = time();
			$uinfo['isadmin'] = 1;
			$uinfo['random_str'] = random(16);
			$id = Db::name('admin_user')->insertGetId($uinfo);
            \app\common\System::plog('添加商户'.$bid);

			$mendian = Db::name('mendian')->where('bid',$bid)->find();
			if(!$mendian){
				Db::name('mendian')->insert(['aid'=>aid,'bid'=>$bid,'name'=>$info['name'],'address'=>$info['address'],'pic'=>$info['logo'],'longitude'=>$info['longitude'],'latitude'=>$info['latitude'],'createtime'=>time()]);
			}
			$freight = Db::name('freight')->where('bid',$bid)->find();
			if(!$freight){
				Db::name('freight')->insert([
					'aid'=>aid,
					'bid'=>$bid,
					'name'=>'普通快递',
					'pstype'=>0,
					'pricedata'=>'[{"region":"全国(默认运费)","fristweight":"1000","fristprice":"0","secondweight":"1000","secondprice":"0"}]',
					'pstimedata'=>'[{"day":"1","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"1","hour":"18","minute":"0","hour2":"18","minute2":"30"},{"day":"2","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"2","hour":"18","minute":"0","hour2":"18","minute2":"30"}]',
					'status'=>1,
				]);
				Db::name('freight')->insert([
					'aid'=>aid,
					'bid'=>$bid,
					'name'=>'到店自提',
					'pstype'=>1,
					'pricedata'=>'[{"region":"全国(默认运费)","fristweight":"1000","fristprice":"0","secondweight":"1000","secondprice":"0"}]',
					'pstimedata'=>'[{"day":"1","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"1","hour":"18","minute":"0","hour2":"18","minute2":"30"},{"day":"2","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"2","hour":"18","minute":"0","hour2":"18","minute2":"30"}]',
					'status'=>1,
				]);
			}
			if(getcustom('restaurant')){
				\app\custom\Restaurant::init_freight(aid,$bid);
			}
			//添加默认数据
            \app\common\Business::addDefaultData(aid,$bid);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//改状态
	public function setst(){
		$st = input('post.st/d');
		$ids = input('post.ids/a');
		Db::name('business')->where('aid',aid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog('修改商户状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//审核
	public function setcheckst(){
		$st = input('post.st/d');
		$id = input('post.id/d');
		$reason = input('post.reason');
		$business = Db::name('business')->where('aid',aid)->where('id',$id)->find();
		if(!$business) return json(['status'=>0,'msg'=>'商家不存在']);
		Db::name('business')->where('aid',aid)->where('id',$id)->update(['status'=>$st,'reason'=>$reason]);
		if($st == 1){
			$mendian = Db::name('mendian')->where('bid',$id)->find();
			if(!$mendian){
				Db::name('mendian')->insert(['aid'=>aid,'bid'=>$id,'name'=>$business['name'],'address'=>$business['address'],'pic'=>$business['logo'],'longitude'=>$business['longitude'],'latitude'=>$business['latitude'],'createtime'=>time()]);
			}
			$freight = Db::name('freight')->where('bid',$id)->find();
			if(!$freight){
				Db::name('freight')->insert([
					'aid'=>aid,
					'bid'=>$id,
					'name'=>'普通快递',
					'pstype'=>0,
					'pricedata'=>'[{"region":"全国(默认运费)","fristweight":"1000","fristprice":"0","secondweight":"1000","secondprice":"0"}]',
					'pstimedata'=>'[{"day":"1","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"1","hour":"18","minute":"0","hour2":"18","minute2":"30"},{"day":"2","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"2","hour":"18","minute":"0","hour2":"18","minute2":"30"}]',
					'status'=>1,
				]);
				Db::name('freight')->insert([
					'aid'=>aid,
					'bid'=>$id,
					'name'=>'到店自提',
					'pstype'=>1,
					'pricedata'=>'[{"region":"全国(默认运费)","fristweight":"1000","fristprice":"0","secondweight":"1000","secondprice":"0"}]',
					'pstimedata'=>'[{"day":"1","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"1","hour":"18","minute":"0","hour2":"18","minute2":"30"},{"day":"2","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"2","hour":"18","minute":"0","hour2":"18","minute2":"30"}]',
					'status'=>1,
				]);
			}
			if(getcustom('restaurant')){
				\app\custom\Restaurant::init_freight(aid,$id);
			}
			$isIndependence = 0; //商家推荐人独立
			}else{
			//商品下架
			Db::name('shop_product')->where('aid',aid)->where('bid',$id)->update(['status'=>0]);
			Db::name('collage_product')->where('aid',aid)->where('bid',$id)->update(['status'=>0]);
			Db::name('kanjia_product')->where('aid',aid)->where('bid',$id)->update(['status'=>0]);
			Db::name('seckill_product')->where('aid',aid)->where('bid',$id)->update(['status'=>0]);
			Db::name('tuangou_product')->where('aid',aid)->where('bid',$id)->update(['status'=>0]);

		}

        // 短信通知
        return json(['status'=>1,'msg'=>'操作成功']);
	}
	private function addcommonproduct($bid){
        }

    private function tongbuproduct($bid){
        }
	//删除
	public function del(){
		$ids = input('post.ids/a');
        //查询是否存在未处理的订单
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','in',$ids];
        $checkShop = Db::name('shop_order')->where($where)->where('status',1)->find();
        if($checkShop) showmsg('存在未发货的订单，不能删除');
        $checkRefundShop = Db::name('shop_refund_order')->where($where)->where('status','in',[1,4])->find();
        if($checkRefundShop) showmsg('存在未处理的退款订单，不能删除');
        //拼团订单
        $checkCollage = Db::name('collage_order')->where($where)->where('status = 1 or refund_status = 1')->find();
        if($checkCollage) showmsg('存在未处理的拼团订单，不能删除');
        //砍价订单
        $checkKanjia = Db::name('kanjia_order')->where($where)->where('status = 1 or refund_status = 1')->find();
        if($checkKanjia) showmsg('存在未处理的砍价订单，不能删除');
        //秒杀订单
        $checkSeckill = Db::name('seckill_order')->where($where)->where('status = 1 or refund_status = 1')->find();
        if($checkSeckill) showmsg('存在未处理的秒杀订单，不能删除');
        //团购订单
        $checkTuangou = Db::name('tuangou_order')->where($where)->where('status = 1 or refund_status = 1')->find();
        if($checkTuangou) showmsg('存在未处理的团购订单，不能删除');
        //幸运拼团订单
        $checkLucky = Db::name('lucky_collage_order')->where($where)->where('status = 1 or refund_status = 1')->find();
        if($checkLucky) showmsg('存在未处理的幸运拼团订单，不能删除');
        //预约订单
        $checkYuyue = Db::name('yuyue_order')->where($where)->where(function($query) use ($where){
            $query->where('status','in',[1,2])->whereOr('refund_status',1);
        })->find();
        if($checkYuyue) showmsg('存在未处理的预约订单，不能删除');
        //积分订单
        if(getcustom('restaurant')){
            //餐饮订单
            $checkRestaurantShop =Db::name('restaurant_shop_order')->where($where)->where('status = 1 or refund_status = 1')->find();
            if ($checkRestaurantShop) showmsg('存在未处理的点餐订单，不能删除');

            $checkTakeaway = Db::name('restaurant_takeaway_order')->where($where)->where('status = 1 or refund_status = 1')->find();
            if ($checkTakeaway) showmsg('存在未处理的外卖订单，不能删除');

            $checkBooking = Db::name('restaurant_booking_order')->where($where)->where('status = 1 or refund_status = 1')->find();
            if ($checkBooking) showmsg('存在未处理的预约订单，不能删除');

            $checkDeposit = Db::name('restaurant_deposit_order')->where($where)->where('status',0)->find();
            if ($checkDeposit) showmsg('存在未处理的寄存订单，不能删除');
        }
		Db::name('business')->where('aid',aid)->where('id','in',$ids)->delete();
		Db::name('admin_user')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('shop_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
		//Db::name('shop_order')->where('aid',aid)->where('bid','in',$ids)->delete();
		//Db::name('shop_order_goods')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('collage_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
		//Db::name('collage_order')->where('aid',aid)->where('bid','in',$ids)->delete();
		//Db::name('collage_order_team')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('kanjia_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
		//Db::name('kanjia_order')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('coupon')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('coupon_record')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('business_comment')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('business_moneylog')->where('aid',aid)->where('bid','in',$ids)->delete();
		Db::name('business_withdrawlog')->where('aid',aid)->where('bid','in',$ids)->delete();
        //Db::name('shop_refund_order')->where('aid',aid)->where('bid','in',$ids)->delete();
        //Db::name('shop_refund_order_goods')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('invoice')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('cycle_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //删除预约服务信息
        Db::name('yuyue_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //Db::name('yuyue_order')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('yuyue_set')->where('aid',aid)->where('bid','in',$ids)->delete();
        //Db::name('yuyue_guige')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('yuyue_fuwu')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('yuyue_comment')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('yuyue_category')->where('aid',aid)->where('bid','in',$ids)->delete();
        //知识付费信息删除
        Db::name('kecheng_category')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('kecheng_list')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        Db::name('kecheng_chapter')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        Db::name('kecheng_tiku')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //Db::name('kecheng_order')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('kecheng_studylog')->where('aid',aid)->where('bid','in',$ids)->delete();
        //秒杀删除
        Db::name('seckill_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //团购删除
        Db::name('tuangou_category')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('tuangou_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //幸运拼团
        Db::name('lucky_collage_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //文章
        Db::name('article_category')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('article')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
        //短视频
        Db::name('shortvideo_category')->where('aid',aid)->where('bid','in',$ids)->delete();
        Db::name('shortvideo')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);

        //约课删除
        //酒店删除
        if(getcustom('wx_channels')) {
            //删除视频号小店账号
            $res = Db::name("admin_setapp_channels")->where('aid', aid)->where('bid', 'in', $ids)->delete();
        }
        //门店删除
        Db::name('mendian')->where('aid', aid)->where('bid', 'in', $ids)->delete();

        //删除销量统计
        Db::name('business_sales')->where('aid',aid)->where('bid','in',$ids)->delete();

        //删除餐饮
        if(getcustom('restaurant')){
            Db::name('restaurant_product')->where('aid',aid)->where('bid','in',$ids)->update(['status'=>0]);
            Db::name('restaurant_product_category')->where('aid',aid)->where('bid','in',$ids)->delete();

            //Db::name('restaurant_shop_order')->where('aid',aid)->where('bid','in',$ids)->delete();
            //Db::name('restaurant_shop_order_goods')->where('aid',aid)->where('bid','in',$ids)->delete();

            //Db::name('restaurant_takeaway_order')->where('aid',aid)->where('bid','in',$ids)->delete();
            //Db::name('restaurant_takeaway_order_goods')->where('aid',aid)->where('bid','in',$ids)->delete();

            //Db::name('restaurant_booking_order')->where('aid',aid)->where('bid','in',$ids)->delete();
            //Db::name('restaurant_booking_order_goods')->where('aid',aid)->where('bid','in',$ids)->delete();

            //Db::name('restaurant_deposit_order')->where('aid',aid)->where('bid','in',$ids)->delete();
            //Db::name('restaurant_deposit_order_log')->where('aid',aid)->where('bid','in',$ids)->delete();

            Db::name('restaurant_queue')->where('aid',aid)->where('bid','in',$ids)->delete();
            Db::name('restaurant_queue_category')->where('aid',aid)->where('bid','in',$ids)->delete();

            Db::name('restaurant_cuxiao')->where('aid',aid)->where('bid','in',$ids)->delete();

            Db::name('restaurant_table')->where('aid',aid)->where('bid','in',$ids)->delete();
            Db::name('restaurant_table_category')->where('aid',aid)->where('bid','in',$ids)->delete();
        }
        \app\common\System::plog('删除商户'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	//充值
	public function recharge(){
		$bid = input('post.rechargemid/d');
		$money = floatval(input('post.rechargemoney'));
        if($money == 0){
			return json(['status'=>0,'msg'=>'请输入充值金额']);
		}
		$info = Db::name('business')->where('aid',aid)->where('id',$bid)->find();
		if(!$info) return json(['status'=>0,'msg'=>'未找到该商家']);
		\app\common\Business::addmoney(aid,$bid,$money,'平台充值');
		\app\common\System::plog('给商户充值'.$bid);
		return json(['status'=>1,'msg'=>'充值成功']);
	}

    public function deposit(){
        }
	//加积分
	public function addscore(){
		$bid = input('post.id/d');
		$score = intval(input('post.score'));
		$remark = input('post.remark');
        $actionname = '增加';
		if($score == 0){
			return json(['status'=>0,'msg'=>'请输入'.t('积分').'数']);
		}
        if($score < 0) $actionname = '扣除';
		$rs = \app\common\Business::addscore(aid,$bid,$score,''.$remark,1);
		\app\common\System::plog('给商户'.$bid.$actionname.'积分'.$score);
		if($rs['status']==0) return json($rs);
		return json(['status'=>1,'msg'=>'操作成功']);
	}

	//商家小程序码
	public function getmdqr(){
		$id = input('post.id/d');
		$set = Db::name('admin_set')->where('aid',aid)->find();
		if(!$set['wxappid']){
			return json(['status'=>0,'msg'=>'请先授权小程序']);
		}
		$rs = \app\Common\Wechat::getQRCode(aid,'wx','pagesExt/business/detail',['id' => $id]);
		return $rs;
	}
	
	//复制数据
	public function copydata(){
		set_time_limit(0);
		ini_set('memory_limit','-1');

		$info = input('post.info/a');
		$toid = $info['toid'];
		$delold = $info['delold'];
		$module_data = input('post.module_data');
		if(!$module_data)  return json(['status'=>0,'msg'=>'请选择要复制的数据']);
		$business = Db::name('business')->where('aid',aid)->where('id',$toid)->find();
		if(!$business) return json(['status'=>0,'msg'=>'要复制到的商户未查找到']);
		
		foreach($module_data as $modulename){
			if($modulename == '商城商品分类'){
				if($delold == 1){
					Db::name('shop_category2')->where('aid',aid)->where('bid',$toid)->delete();
				}
				$clist = Db::name('shop_category')->where('aid',aid)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
				foreach($clist as $k=>$v){
					$newdata = [];
					$newdata['id'] = '';
					$newdata['aid'] = $v['aid'];
					$newdata['bid'] = $toid;
					$newdata['pid'] = 0;
					$newdata['name'] = $v['name'];
					$newdata['pic'] = $v['pic'];
					$newdata['status'] = $v['status'];
					$newdata['sort'] = $v['sort'];
					$newdata['createtime'] = $v['createtime'];
					$newdata['fromid'] = $v['id'];
					$newid = Db::name('shop_category2')->insertGetId($newdata);

					$child = Db::name('shop_category')->where('aid',aid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
					foreach($child as $k2=>$v2){
						$newdata = [];
						$newdata['id'] = '';
						$newdata['aid'] = $v2['aid'];
						$newdata['bid'] = $toid;
						$newdata['pid'] = $newid;
						$newdata['name'] = $v2['name'];
						$newdata['pic'] = $v2['pic'];
						$newdata['status'] = $v2['status'];
						$newdata['sort'] = $v2['sort'];
						$newdata['createtime'] = $v2['createtime'];
						$newdata['fromid'] = $v2['id'];
						$newid2 = Db::name('shop_category2')->insertGetId($newdata);

						$child2 = Db::name('shop_category')->where('aid',aid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
						foreach($child2 as $k3=>$v3){
							$newdata = [];
							$newdata['id'] = '';
							$newdata['aid'] = $v3['aid'];
							$newdata['bid'] = $toid;
							$newdata['pid'] = $newid2;
							$newdata['name'] = $v3['name'];
							$newdata['pic'] = $v3['pic'];
							$newdata['status'] = $v3['status'];
							$newdata['sort'] = $v3['sort'];
							$newdata['createtime'] = $v3['createtime'];
							$newdata['fromid'] = $v3['id'];
							$newid3 = Db::name('shop_category2')->insertGetId($newdata);
						}
					}
				}
			}
		}
		foreach($module_data as $modulename){
			if($modulename == '商城商品'){
				//服务
				if($delold == 1) Db::name('shop_fuwu')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('shop_fuwu')->where('aid',aid)->where('bid',0)->select()->toArray();
				$shop_fuwu_ids_map = [];
				$shop_fuwu_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$shop_fuwu_ids_map[$oldid] = Db::name('shop_fuwu')->insertGetId($data);
				}
				//参数
				if($delold == 1) Db::name('shop_param')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('shop_param')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					Db::name('shop_param')->insertGetId($data);
				}
				//商品
				if($delold == 1){
					$proids = Db::name('shop_product')->where('aid',aid)->where('bid',$toid)->column('id');
					Db::name('shop_product')->where('aid',aid)->where('bid',$toid)->delete();
					Db::name('shop_guige')->where('aid',aid)->where('proid','in',$proids)->delete();
				}
				$fromdata = [];

				if(false){}else{
					$fromdata = Db::name('shop_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				}
				
				foreach($fromdata as $data){
					$oldid = $data['id'];
					unset($data['wxvideo_product_id']);
					unset($data['wxvideo_edit_status']);
					unset($data['wxvideo_status']);
					unset($data['wxvideo_reject_reason']);
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['fwid'] = $this->getnewids($shop_fuwu_ids_map,$data['fwid']);
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					if($data['cid']){
						$cid2 = Db::name('shop_category2')->where('bid',$toid)->where('fromid','in',$data['cid'])->column('id');
						if($cid2) $data['cid2'] = implode(',',$cid2);
					}
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('shop_product')->insertGetId($data);
					$gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$oldid)->select()->toArray();
					foreach($gglist as $gg){
						$gg['id'] = '';
						$gg['proid'] = $id;
						Db::name('shop_guige')->insert($gg);
					}
				}
			}
			if($modulename == '拼团商品'){
				//商品
				if($delold == 1){
					$proids = Db::name('collage_product')->where('aid',aid)->where('bid',$toid)->column('id');
					Db::name('collage_product')->where('aid',aid)->where('bid',$toid)->delete();
					Db::name('collage_guige')->where('aid',aid)->where('proid','in',$proids)->delete();
				}
				$fromdata = Db::name('collage_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('collage_product')->insertGetId($data);
					$gglist = Db::name('collage_guige')->where('aid',aid)->where('proid',$oldid)->select()->toArray();
					foreach($gglist as $gg){
						$gg['id'] = '';
						$gg['proid'] = $id;
						Db::name('collage_guige')->insert($gg);
					}
				}
			}
			if($modulename == '砍价商品'){
				//商品
				if($delold == 1) Db::name('kanjia_product')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('kanjia_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					$id = Db::name('kanjia_product')->insertGetId($data);
				}
			}
			if($modulename == '秒杀商品'){
				if($delold == 1){
					$proids = Db::name('seckill_product')->where('aid',aid)->where('bid',$toid)->column('id');
					Db::name('seckill_product')->where('aid',aid)->where('bid',$toid)->delete();
					Db::name('seckill_guige')->where('aid',aid)->where('proid','in',$proids)->delete();
				}
				$fromdata = Db::name('seckill_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('seckill_product')->insertGetId($data);
					$gglist = Db::name('seckill_guige')->where('aid',aid)->where('proid',$oldid)->select()->toArray();
					foreach($gglist as $gg){
						$gg['id'] = '';
						$gg['proid'] = $id;
						Db::name('seckill_guige')->insert($gg);
					}
				}
			}
			if($modulename == '团购商品'){
				//分类
				if($delold == 1) Db::name('tuangou_category')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('tuangou_category')->where('aid',aid)->where('bid',0)->select()->toArray();
				$tuangou_category_ids_map = [];
				$tuangou_category_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['pid'] = $tuangou_category_ids_map[$data['pid']];
					$tuangou_category_ids_map[$oldid] = Db::name('tuangou_category')->insertGetId($data);
				}
				//商品
				if($delold == 1) Db::name('tuangou_product')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('tuangou_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['cid'] = $this->getnewids($tuangou_category_ids_map,$data['cid']);
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('tuangou_product')->insertGetId($data);
				}
			}
			if($modulename == t('幸运拼团').'商品'){
				//商品
				if($delold == 1){
					$proids = Db::name('lucky_collage_product')->where('aid',aid)->where('bid',$toid)->column('id');
					Db::name('lucky_collage_product')->where('aid',aid)->where('bid',$toid)->delete();
					Db::name('lucky_collage_guige')->where('aid',aid)->where('proid','in',$proids)->delete();
				}
				$fromdata = Db::name('lucky_collage_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('lucky_collage_product')->insertGetId($data);
					$gglist = Db::name('lucky_collage_guige')->where('aid',aid)->where('proid',$oldid)->select()->toArray();
					foreach($gglist as $gg){
						$gg['id'] = '';
						$gg['proid'] = $id;
						Db::name('lucky_collage_guige')->insert($gg);
					}
				}
			}
			if($modulename == '短视频'){
				//分类
				if($delold == 1) Db::name('shortvideo_category')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('shortvideo_category')->where('aid',aid)->where('bid',0)->select()->toArray();
				$shortvideo_category_ids_map = [];
				$shortvideo_category_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$shortvideo_category_ids_map[$oldid] = Db::name('shortvideo_category')->insertGetId($data);
				}
				//商品
				if($delold == 1) Db::name('shortvideo')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('shortvideo')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['mid'] = 0;
					$data['cid'] = $this->getnewids($shortvideo_category_ids_map,$data['cid']);
					$id = Db::name('shortvideo')->insertGetId($data);
				}
			}
			if($modulename == '文章列表'){
				//分类
				if($delold == 1) Db::name('article_category')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('article_category')->where('aid',aid)->where('bid',0)->select()->toArray();
				$article_category_ids_map = [];
				$article_category_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['pid'] = $article_category_ids_map[$data['pid']];
					$article_category_ids_map[$oldid] = Db::name('article_category')->insertGetId($data);
				}
				//文章
				if($delold == 1) Db::name('article')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('article')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['cid'] = $this->getnewids($article_category_ids_map,$data['cid']);
					$id = Db::name('article')->insertGetId($data);
				}
			}
			if($modulename == '预约服务商品'){
				//分类
				if($delold == 1) Db::name('yuyue_category')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('yuyue_category')->where('aid',aid)->where('bid',0)->select()->toArray();
				$yuyue_category_ids_map = [];
				$yuyue_category_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['pid'] = $yuyue_category_ids_map[$data['pid']];
					$yuyue_category_ids_map[$oldid] = Db::name('yuyue_category')->insertGetId($data);
				}
				//服务
				if($delold == 1) Db::name('yuyue_fuwu')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('yuyue_fuwu')->where('aid',aid)->where('bid',0)->select()->toArray();
				$yuyue_fuwu_ids_map = [];
				$yuyue_fuwu_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$yuyue_fuwu_ids_map[$oldid] = Db::name('yuyue_fuwu')->insertGetId($data);
				}
				//商品
				if($delold == 1){
					$proids = Db::name('yuyue_product')->where('aid',aid)->where('bid',$toid)->column('id');
					Db::name('yuyue_product')->where('aid',aid)->where('bid',$toid)->delete();
					Db::name('yuyue_guige')->where('aid',aid)->where('proid','in',$proids)->delete();
				}
				$fromdata = Db::name('yuyue_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['cid'] = $this->getnewids($yuyue_category_ids_map,$data['cid']);
					$data['fwid'] = $this->getnewids($yuyue_fuwu_ids_map,$data['fwid']);
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('yuyue_product')->insertGetId($data);
					$gglist = Db::name('yuyue_guige')->where('aid',aid)->where('proid',$oldid)->select()->toArray();
					foreach($gglist as $gg){
						$gg['id'] = '';
						$gg['bid'] = $toid;
						$gg['proid'] = $id;
						Db::name('yuyue_guige')->insert($gg);
					}
				}
			}
			if($modulename == '知识付费课程'){
				//分类
				if($delold == 1) Db::name('kecheng_category')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('kecheng_category')->where('aid',aid)->where('bid',0)->select()->toArray();
				$kecheng_category_ids_map = [];
				$kecheng_category_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['pid'] = $kecheng_category_ids_map[$data['pid']];
					$kecheng_category_ids_map[$oldid] = Db::name('kecheng_category')->insertGetId($data);
				}
				$fromdata = Db::name('kecheng_list')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['cid'] = $this->getnewids($kecheng_category_ids_map,$data['cid']);
					$id = Db::name('kecheng_list')->insertGetId($data);
					$chapterlist = Db::name('kecheng_chapter')->where('aid',aid)->where('kcid',$oldid)->select()->toArray();
					foreach($chapterlist as $chapter){
						$chapter['id'] = '';
						$chapter['bid'] = $toid;
						$chapter['kcid'] = $id;
						Db::name('kecheng_chapter')->insert($chapter);
					}
					$tikulist = Db::name('kecheng_tiku')->where('aid',aid)->where('kcid',$oldid)->select()->toArray();
					foreach($tikulist as $tiku){
						$tiku['id'] = '';
						$tiku['bid'] = $toid;
						$tiku['kcid'] = $id;
						Db::name('kecheng_tiku')->insert($tiku);
					}
				}
			}
			if($modulename == '餐饮菜品'){
				//分类
				if($delold == 1) Db::name('restaurant_product_category')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('restaurant_product_category')->where('aid',aid)->where('bid',0)->select()->toArray();
				$restaurant_category_ids_map = [];
				$restaurant_category_ids_map['0'] = '0';
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['pid'] = $restaurant_category_ids_map[$data['pid']];
					$restaurant_category_ids_map[$oldid] = Db::name('restaurant_product_category')->insertGetId($data);
				}
				//商品
				if($delold == 1){
					$proids = Db::name('restaurant_product')->where('aid',aid)->where('bid',$toid)->column('id');
					Db::name('restaurant_product')->where('aid',aid)->where('bid',$toid)->delete();
					Db::name('restaurant_product_guige')->where('aid',aid)->where('product_id','in',$proids)->delete();
				}

				if(false){}else{
					$fromdata = Db::name('restaurant_product')->where('aid',aid)->where('bid',0)->select()->toArray();
				}

				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$data['cid'] = $this->getnewids($restaurant_category_ids_map,$data['cid']);
					if($data['freighttype'] == 0) $data['freighttype'] = 1;
					//if($data['commissionset'] == 1 || $data['commissionset'] == 2 || $data['commissionset'] == 3) $data['commissionset'] = 0;
					$id = Db::name('restaurant_product')->insertGetId($data);
					$gglist = Db::name('restaurant_product_guige')->where('aid',aid)->where('product_id',$oldid)->select()->toArray();
					foreach($gglist as $gg){
						$gg['id'] = '';
						$gg['product_id'] = $id;
						Db::name('restaurant_product_guige')->insert($gg);
					}
				}
			}
			if($modulename == '优惠券'){
				if($delold == 1) Db::name('coupon')->where('aid',aid)->where('bid',$toid)->delete();
				$fromdata = Db::name('coupon')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
                    $data['buyprogive'] = 0;
                    $data['buyproids'] = '';
                    $data['buyyuyueprogive'] = 0;
                    $data['buyyuyueproids'] = '';
                    $id = Db::name('coupon')->insertGetId($data);
				}
			}
			if($modulename == '设计页面'){
				if($delold == 1){
					Db::name('designerpage')->where('aid',aid)->where('bid',$toid)->delete();
				}else{
					Db::name('designerpage')->where('aid',aid)->where('bid',$toid)->update(['ishome'=>0]);
				}
				$fromdata = Db::name('designerpage')->where('aid',aid)->where('bid',0)->select()->toArray();
				foreach($fromdata as $data){
					$oldid = $data['id'];
					$data['id'] = '';
					$data['bid'] = $toid;
					$id = Db::name('designerpage')->insertGetId($data);
				}
			}
			}
		return json(['status'=>1,'msg'=>'复制完成']);
	}
	private function getnewids($arr,$ids){
		if(!$ids) return $ids;
		$ids = explode(',',$ids);
		$newids = [];
		foreach($ids as $id){
			$newids[] = $arr[$id];
		}
		return implode(',',$newids);
	}

	//分类列表
    public function category(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			$count = 0 + Db::name('business_category')->where($where)->count();
			$data = Db::name('business_category')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
    }
	//编辑
	public function categoryedit(){
		if(input('param.id')){
			$info = Db::name('business_category')->where('aid',aid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}
		$pcatelist = Db::name('business_category')->where('aid',aid)->order('sort desc,id')->select()->toArray();
		View::assign('info',$info);
		return View::fetch();
	}
	//保存
	public function categorysave(){
		$info = input('post.info/a');
		if($info['id']){
			Db::name('business_category')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('修改商家分类'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
			$id = Db::name('business_category')->insertGetId($info);
			\app\common\System::plog('添加商家分类'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function categorydel(){
		$ids = input('post.ids/a');
		Db::name('business_category')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除商家分类'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	//系统设置
	public function sysset(){
		if(request()->isPost()){
			$rs = Db::name('business_sysset')->where('aid',aid)->find();
			$info = input('post.info/a');

			$info['wxfw_apiclient_cert'] = str_replace(PRE_URL.'/','',$info['wxfw_apiclient_cert']);
			$info['wxfw_apiclient_key'] = str_replace(PRE_URL.'/','',$info['wxfw_apiclient_key']);

            if(!empty($info['wxfw_apiclient_cert']) && substr($info['wxfw_apiclient_cert'], -4) != '.pem'){
                return json(['status'=>0,'msg'=>'PEM证书格式错误']);
            }
            if(!empty($info['wxfw_apiclient_key']) && substr($info['wxfw_apiclient_key'], -4) != '.pem'){
                return json(['status'=>0,'msg'=>'证书密钥格式错误']);
            }
             if($rs){
                //关闭多商户分销设置
                if($rs['commission_canset'] == 1 && $info['commission_canset'] == 0){
                    Db::name('shop_product')->where('aid',aid)->where('bid','>',0)->update(['commissionset'=>-1]);
                }
				Db::name('business_sysset')->where('aid',aid)->update($info);
				\app\common\System::plog('多商户系统设置');
			}else{
				$info['aid'] = aid;
				Db::name('business_sysset')->insert($info);
			}
			return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
		}
		$info = Db::name('business_sysset')->where('aid',aid)->find();
		if(!$info){
			Db::name('business_sysset')->insert(['aid'=>aid]);
			$info = Db::name('business_sysset')->where('aid',aid)->find();
		}
        //权限
        if($this->auth_data == 'all' || in_array('Cashier/*',$this->auth_data)){
            $info['business_auth'] = true;
        }
        if($info['wxfw_status']==0){
            $info['duli_disabled'] = true;
        }
        $scoredk_kouchu_list = [0=>'否',1=>'是'];
        View::assign('scoredk_kouchu_list',$scoredk_kouchu_list);
        View::assign('info',$info);
        //服务商模式支付开关，默认开启
        $wxpay_fws_status = 1;
        View::assign('wxpay_fws_status',$wxpay_fws_status);
		return View::fetch();
	}
	public function choosebusiness(){
		return View::fetch();
	}
	public function getbusinessinfo(){
		$id = input('post.id/d');
		$info = Db::name('business')->where('id',$id)->where('aid',aid)->find();
		return json(['status'=>1,'data'=>$info]);
	}
	//登录
	public function blogin(){
		$id = input('param.id/d');
		$user = Db::name('admin_user')->where('aid',aid)->where('bid',$id)->where('isadmin',1)->find();
		if(!$user) die('未找到该商家');
		session('ADMIN_AUTH_UID',$user['id']);
		session('ADMIN_AUTH_BID',$id);
		return redirect(PRE_URL.'/business.php?s=/Backstage/index');
	}

	//商户销量统计
	public function sales(){
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 's.'.input('param.field').' '.input('param.order');
            }else{
                $order = 's.bid desc';
            }
            $where = array();
            $where[] = ['s.aid','=',aid];
            if(input('param.bid')) $where[] = ['s.bid','=',input('param.bid/d')];
            if(input('param.name')) $where[] = ['b.name','like','%'.input('param.name').'%'];
            $count = 0 + Db::name('business_sales')
                    ->alias('s')
                    ->join('business b','s.bid=b.id','left')
                    ->where($where)->count();
            $data = Db::name('business_sales')
                ->alias('s')
                ->join('business b','s.bid=b.id','left')
                ->field('s.*,b.name')
                ->where($where)->page($page,$limit)->order($order)->select()->toArray();

            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        //判断管理员商品虚拟销量和数据是否匹配，不匹配显示手动更新按钮（解决首次更新代码销量没有数据问题）
        $show_update = 0;
        $product_sales = Db::name('shop_product')->where('aid',aid)->where('bid',0)->sum('sales');
        $business_sales = Db::name('business_sales')->where('aid',aid)->where('bid',0)->value('sales');
        if($product_sales>$business_sales){
            $show_update = 1;
        }
        View::assign('show_update',$show_update);

        return View::fetch();
    }
    //删除销量
    public function del_sales(){
        $bids = input('post.bids/a');
        Db::name('business_sales')->where('aid',aid)->where('bid','in',$bids)->delete();
        \app\common\System::plog('删除商户销量'.implode(',',$bids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    //导出
    public function excel(){
        if(getcustom('business_fenxiao') || getcustom('business_excel')){
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'sort desc,id desc';
            }
            $page = input('param.page')?:1;
            $limit = input('param.limit')?:10;
            $where = array();
            $where[] = ['aid','=',aid];
            if(input('param.cid')) $where[] = ['cid','=',input('param.cid/d')];
            if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['status','=',input('param.status')];
            }
            if(input('?param.is_open') && input('param.is_open')!==''){
                $where[] = ['is_open','=',input('param.is_open')];
            }
            $carr = Db::name('business_category')->where('aid',aid)->column('name','id');
            $lists = Db::name('business')->where($where)->order($order)->page($page,$limit)->select()->toArray();
            $count = Db::name('business')->where($where)->order($order)->count();
            foreach($lists as $k=>$v){
                $cnames = [];
                if($v['cid']){
                    $cids = explode(',',$v['cid']);
                    foreach($cids as $cid){
                        $cnames[] = $carr[$cid];
                    }
                }
                $lists[$k]['cname'] = implode(',',$cnames);
                if($v['mid']){
                    $member = Db::name('member')->where('id',$v['mid'])->find();
                    $lists[$k]['nickname'] = $member['nickname'];
                    $lists[$k]['headimg'] = $member['headimg'];
                }else{
                    $lists[$k]['nickname'] = '';
                    $lists[$k]['headimg'] = '';
                }
                }
            $title = array('ID','商家分类','商家名称','商家地址','联系人','联系人电话','余额','浏览量','创建时间','状态','营业状态');
            $status_arr = [
                '0' => '待审核',
                '1' => '已通过',
                '2' => '已驳回',
                '-1' => '已过期',
            ];
            $open_arr = [
                '0' => '休息',
                '1' => '营业',
            ];
            $data = array();
            foreach($lists as $k=>$vo){
                $arr = [
                    $vo['id'],
                    $vo['cname'],
                    $vo['name'],
                    $vo['address'],
                    $vo['linkman'],
                    $vo['linktel'],
                    $vo['money'],
                    $vo['viewnum'],
                    date('Y-m-d H:i',$vo['createtime']),
                    $status_arr[$vo['status']],
                    $open_arr[$vo['is_open']],
                ];
                $data[] = $arr;
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
            $this->export_excel($title,$data);
        }

    }

    public function setdepositrefund(){
	    }
    
    //支付宝服务商对商户的授权
    public function alipayIsvAuthorization(){
	    }
    public function alipaySet(){
	    }
    
    //刷新业绩
    public function refreshYeji(){
	    }

    public function setPaymoneyGivepercent(){
        }

    //审核商品抽成费率
    public function setFeepercent(){
        }

    public function memberRecommendApplyBusiness($business,$isIndependence=0){
        }

    /**
     * 城市商户
     * @author: liud
     * @time: 2025/9/28 09:42
     */
    public function businesscity(){
        }
    public function businesscitydel(){
        }
    public function businesscitysave(){
        }
    public function  businesscityset(){
        }
}

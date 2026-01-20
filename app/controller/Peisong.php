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
// | 同城配送设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Peisong extends Common
{
    public function initialize(){
		parent::initialize();
	}
    public function set(){
        $check_bid = 1;//检验商户权限
        if($check_bid){
            if(bid > 0) showmsg('无访问权限');
        }
        $where = [];
        $where[] = ['aid','=',aid];
        $info = Db::name('peisong_set')->where($where)->find();
		if(!$info){
		    $insert = ['aid' => aid];
            Db::name('peisong_set')->insert($insert);
			$info = Db::name('peisong_set')->where($where)->find();
		}

        $myt_status = 0;//麦芽田状态
        $info['myt_status'] = $myt_status;

        $wxtc_status = 0;
        $info['wxtc_status'] = $wxtc_status;

		View::assign('info',$info);
        View::assign('bid',bid);
        View::assign('auth_data',$this->auth_data);
		return View::fetch();
    }
	public function save(){
        $check_bid = 1;//检验商户权限
        if($check_bid){
            if(bid > 0) showmsg('无访问权限');
        }
        $where = [];
        $where[] = ['aid','=',aid];
        $info = input('post.info/a');
        $wx_api_sign = getcustom('wx_api_sign');
        Db::name('peisong_set')->where($where)->update($info);
		\app\common\System::plog('同城配送系统设置');
		return json(['status'=>1,'msg'=>'保存成功','url'=>true]);
	}
    public function makeset(){
		if(bid > 0) showmsg('无访问权限');
		$info = Db::name('peisong_set')->where('aid',aid)->find();
		if(!$info){
			Db::name('peisong_set')->insert(['aid'=>aid]);
			$info = Db::name('peisong_set')->where('aid',aid)->find();
		}
		View::assign('info',$info);
		return View::fetch();
    }
	public function save2(){
		if(bid > 0) showmsg('无访问权限');
		$info = input('post.info/a');
        if($info['make_status']==1) {
            $set = Db::name('peisong_set')->where('aid', aid)->find();
            if($set['express_wx_status'] == 1) {
                return json(['status'=>0,'msg'=>'请先关闭即时配送']);
            }
            }
		$info['make_access_token'] = '';
		$info['make_expire_time'] = '';
		Db::name('peisong_set')->where('aid',aid)->update($info);
		\app\common\System::plog(t('码科跑腿').'设置');
		if($info['make_status']==1){
			\app\common\Make::access_token(aid);
		}
		return json(['status'=>1,'msg'=>'保存成功','url'=>true]);
	}

	//获取配送员
	public function getpeisonguser(){
        //如果showtype==1 则不区分派单模式，直接强制派单
        $showtype = input('param.showtype',0);
        $where = [];
        $where[] = ['aid','=',aid];
		$set = Db::name('peisong_set')->where($where)->find();
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
			if($rs['status']==0) return json($rs);
			$ticheng = $rs['price'];
			$selectArr = [];
			$set['paidantype'] = 2;
		}else{
			$selectArr = [];
			if($set['paidantype'] == 0 && $showtype!=1){ //抢单模式
				$selectArr[] = ['id'=>0,'title'=>'--配送员抢单--'];
			}else{
			    $where = [];
			    $where[] = ['aid','=',aid];
                $peisonguser = Db::name('peisong_user')->where($where)->where('status',1)->order('sort desc,id')->select()->toArray();
				foreach($peisonguser as $k=>$v){
					$dan = Db::name('peisong_order')->where('psid',$v['id'])->where('status','in','0,1')->count();
					$title = $v['realname'].'-'.$v['tel'].'(配送中'.$dan.'单)';
					$selectArr[] = ['id'=>$v['id'],'title'=>$title];
				}
			}
		}
		$psfee = 1;
        $business_up_floor_fee = 0;
		if($psfee){
            $psfee = $ticheng * (1 + $set['businessfee']*0.01);
        }
		
		return json(['status'=>1,'peisonguser'=>$selectArr,'paidantype'=>$set['paidantype'],'psfee'=>dd_money_format($psfee),'ticheng'=>dd_money_format($ticheng),'business_up_floor_fee' => $business_up_floor_fee,'set'=> $set]);
	}
	//下配送单
	public function peisong(){
		$orderid = input('post.orderid/d');
		$type = input('post.type');
		$psid = input('post.psid/d');
        $psset_where = [];
        $psset_where[] = ['aid','=',aid];
        $set = Db::name('peisong_set')->where($psset_where)->find();
        if(bid == 0){
			$order = Db::name($type)->where('id',$orderid)->where('aid',aid)->find();

		}else{
			$order = Db::name($type)->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
		}

		//如果选择了配送时间，未到配送时间内不可以进行配送
		if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
		if($order['status']!=1 && $order['status']!=12) return json(['status'=>0,'msg'=>'订单状态不符合']);

        $other = [];
        $rs = \app\model\PeisongOrder::create($type,$order,$psid,$other);
		if($rs['status']==0) return json($rs);
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping(aid,$order,$type);
        }
		\app\common\System::plog('订单配送'.$orderid);
		return json(['status'=>1,'msg'=>'操作成功']);
	}

	public function wxset()
    {
        if(bid > 0) showmsg('无访问权限');
        return View::fetch();
    }

    public function wxset_save(){
        if(bid > 0) showmsg('无访问权限');
        $info = input('post.info/a');
        if($info['express_wx_status'] == 1) {
            $set = Db::name('peisong_set')->where('aid',aid)->find();
            if($set['make_status'] == 1) {
                return json(['status'=>0,'msg'=>'请先关闭'.t('码科').'配送']);
            }
            }
        Db::name('peisong_set')->where('aid',aid)->update($info);
        \app\common\System::plog('即时配送设置');

        return json(['status'=>1,'msg'=>'保存成功','url'=>true]);
    }

    public function wxset_refresh(){
        if(bid > 0) showmsg('无访问权限');
        if(request()->isAjax()){
            $rs = \app\custom\ExpressWx::getBindAccount(aid,true);
            return json(['status'=>$rs['status'],'msg'=>$rs['msg'],'count'=>count($rs['shop_list']),'data'=>$rs['shop_list']]);
        }
    }

    public function wx_edit(){
        $id = input('param.id');
        $info = \app\custom\ExpressWx::getAccount(aid,$id);

        View::assign('info',$info);
        return View::fetch();
    }


    public function wx_edit_save(){
        if(bid > 0) showmsg('无访问权限');
        $info = input('post.info/a');
        $id = input('param.id');
        \app\custom\ExpressWx::updateAccount(aid,$id,$info);

        \app\common\System::plog('即时配送编辑');
        return json(['status'=>1,'msg'=>'保存成功','url'=>true]);
    }
    public function wx_set_status() {
        $id = input('post.id');

        $rs = \app\custom\ExpressWx::accountSetStatus(aid,$id);
        return json(['status'=>$rs['status'],'msg'=>$rs['msg']]);
    }

    public function wx_addorder()
    {
        $orderid = input('post.orderid/d');
        $type = input('post.type');
        $psid = input('post.psid/d');
        if(bid == 0){
            $order = Db::name($type)->where('id',$orderid)->where('aid',aid)->find();
        }else{
            $order = Db::name($type)->where('id',$orderid)->where('aid',aid)->where('bid',bid)->find();
        }

        if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
        if($order['status']!=1 && $order['status']!=12) return json(['status'=>0,'msg'=>'订单状态不符合']);

        $rs = \app\custom\ExpressWx::addOrder($type,$order,$psid);
        if($rs['status']==0) return json($rs);
        \app\common\System::plog('订单即时配送派单:'.$orderid);
        return json(['status'=>1,'msg'=>'操作成功']);
    }

    public function mytset()
    {
        }

    public function mytsave(){
        }

    public function mytshopedit(){
        }
    public function mytshopsave(){
        }

    public function get_balance(){
        }

    public function mytprice(){
        if(false){}else{
             return showmsg('未开启此功能');
        }
    }

    public function mytsave2(){
        }

    public function wxExpressIntracitySet(){
        }
}

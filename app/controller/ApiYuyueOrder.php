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
// | curl https://域名/?s=/ApiAuto/index/key/配置文件中的authtoken
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\Log;
class ApiYuyueOrder extends BaseController
{
    public function initialize(){

	}

	function updateStatus(){
		$config = include(ROOT_PATH.'config.php');
		$headr = $this->get_all_headers();
	
		if(!$headr['Token']) {
			echojson(['status'=>0,'msg'=>'token不能为空!']);
		}
		if(md5($config['authtoken']!=$headr['Token'])){
			//echojson(['status'=>0,'msg'=>'token有误!']);
		}
		$post = $_POST;
		if(!$post['ordernum']) echojson(['status'=>0,'msg'=>'订单号不能为空!']);
		if(!$post['masterId']) echojson(['status'=>0,'msg'=>'师傅ID不能为空!']);
		if(!$post['status']) echojson(['status'=>0,'msg'=>'状态不能为空!']);
		if(!$post['orderType']) echojson(['status'=>0,'msg'=>'订单类型不能为空!']);
		if($post['orderType']==1 || $post['orderType']==2 || !$post['orderType']){
			$order = db('yuyue_order')->where('ordernum',$post['ordernum'])->find();
			if(!$order){
				echojson(['status'=>0,'msg'=>'订单不存在!']);
			}
			if($post['status']==1){
				db('yuyue_order')->where('ordernum',$post['ordernum'])->update(['worker_id'=>$post['masterId']]);
				$rs = \app\model\YuyueWorkerOrder::create2($order,$post['masterId']);
				if($rs['status']==1) echojson(['status'=>200,'msg'=>'派单成功!']);
				else echojson(['status'=>0,'msg'=>$rs['msg']]);
				//\app\common\System::plog('预约派单'.$orderid);
			}else{
				$psorder = Db::name('yuyue_worker_order')->where('aid',$order['aid'])->where('worker_id',$order['worker_id'])->where('ordernum',$order['ordernum'])->find();
				//echo db('yuyue_worker_order')->getlastsql();					
				if($order['paidan_type']==3){
					$updata = [];
					$updata['status'] = $post['status'];
					if($post['status'] == 2){
						$updata['daodiantime'] = time();
					}else if($post['status'] == 3){
						$order = db('yuyue_order')->where(['aid'=>$order['aid'],'id'=>$psorder['orderid']])->find();
						if($order['balance_price']>0 && $order['balance_pay_status']!=1){
							echojson(['status'=>0,'msg'=>'请等顾客支付尾款后，再点击完成']);
						}
						$updata['endtime'] = time();
						//Db::name('yuyue_worker')->where('id',$psorder['id'])->inc('totalnum')->update();
						db('yuyue_order')->where(['aid'=>$order['aid'],'id'=>$psorder['orderid']])->update(['status'=>3,'collect_time'=>time()]);
						$rs = \app\common\Order::collect($order,'yuyue');
						if($rs['status'] == 0) echojson($rs);
						//\app\common\YuyueWorker::addmoney($order['aid'],$psorder['bid'],$this->worker['id'],$psorder['ticheng'],'服务提成');
					}else if($post['status']==4){  //退款操作	
						if($psorder['status']==2){
							Db::name('yuyue_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_status'=>2,'refund_money'=>$order['commission'],'refund_reason'=>'师傅协商退款','refund_time'=>time()]);
							$rs = \app\common\Order::refund($order,$order['commission'],'师傅协商退款');
						}else{
							$remark='师傅未接单退款';
							if($psorder['status']==1){
								$remark='师傅协商退款';
							}
							Db::name('yuyue_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_status'=>2,'refund_money'=>$order['totalprice'],'refund_reason'=>$remark,'refund_time'=>time()]);
							$rs = \app\common\Order::refund($order,$order['totalprice'],$remark);	
						}
					}
				}else{
				
					$updata = [];
					$updata['status'] = $post['status'];
					if($post['status'] == 2){
						if($psorder['status']!=1) echojson(['status'=>0,'msg'=>'订单状态不符合']);
						$updata['daodiantime'] = time();
					}else if($post['status'] == 3){
						if($psorder['status']!=2) echojson(['status'=>0,'msg'=>'订单状态不符合']);
						$order = db('yuyue_order')->where(['aid'=>$order['aid'],'id'=>$psorder['orderid']])->find();
						if($order['balance_price']>0 && $order['balance_pay_status']!=1){
							echojson(['status'=>0,'msg'=>'请等顾客支付尾款后，再点击完成']);
						}
						$updata['endtime'] = time();
						//Db::name('yuyue_worker')->where('id',$psorder['id'])->inc('totalnum')->update();
						db('yuyue_order')->where(['aid'=>$order['aid'],'id'=>$psorder['orderid']])->update(['status'=>3,'collect_time'=>time()]);
						$rs = \app\common\Order::collect($order,'yuyue');
						if($rs['status'] == 0) echojson($rs);
						//\app\common\YuyueWorker::addmoney($order['aid'],$psorder['bid'],$this->worker['id'],$psorder['ticheng'],'服务提成');
					}else if($post['status']==4){  //退款操作
						if($psorder['status']==2){
							Db::name('yuyue_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_status'=>2,'refund_money'=>$order['commission'],'refund_reason'=>'师傅协商退款','refund_time'=>time()]);
							$rs = \app\common\Order::refund($order,$order['commission'],'师傅协商退款');
						}else{
							Db::name('yuyue_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_status'=>2,'refund_money'=>$order['totalprice'],'refund_reason'=>'师傅未接单退款','refund_time'=>time()]);
							$rs = \app\common\Order::refund($order,$order['totalprice'],'师傅未接单退款');
						}
					}
				
				}
				Db::name('yuyue_worker_order')->where('id',$psorder['id'])->update($updata);
				echojson(['status'=>200,'msg'=>'操作成功']);
			}		
		}else{
			$order = db('shop_order')->where('ordernum',$post['ordernum'])->find();
			if(!$order) echojson(['status'=>0,'msg'=>'订单不存在!']);
			if($post['status']==1){
				$rs = \app\model\PeisongOrder::create('shop_order',$order,$post['masterId']);
				if($rs['status']==1) echojson(['status'=>200,'msg'=>'派单成功!']);
				else echojson(['status'=>0,'msg'=>$rs['msg']]);
				//\app\common\System::plog('商城派单'.$orderid);
			}else{
				$psorder = Db::name('peisong_order')->where('aid',$order['aid'])->where('psid',$post['masterId'])->where('orderid',$order['id'])->find();
				//echo db('yuyue_worker_order')->getlastsql();
				if(!$psorder) echojson(['status'=>0,'msg'=>'订单不存在']);
				
				$updata = [];
				$updata['status'] = $post['status'];
				if($post['status'] == 2){
					if($psorder['status']!=1) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
					$updata['daodiantime'] = time();
				}
				//if($st == 3){
				//	if($psorder['status']!=2) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
				//	$updata['quhuotime'] = time();
				//}
				if($post['status'] == 3){
					if($psorder['status']!=2) return $this->json(['status'=>0,'msg'=>'订单状态不符合']);
					$updata['endtime'] = time();
				}
				Db::name('peisong_order')->where('id',$psorder['id'])->update($updata);
				db('shop_order')->where(['aid'=>$order['aid'],'id'=>$psorder['orderid']])->update(['status'=>3,'collect_time'=>time()]);
				$rs = \app\common\Order::collect($order,'shop');
				echojson(['status'=>200,'msg'=>'操作成功']);
			}		
			
		
		}
	}

	function get_all_headers() {
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if(substr($key, 0, 5) === 'HTTP_') {
			$key = substr($key, 5);
			$key = strtolower($key);
			$key = str_replace('_',' ',$key);
			$key = ucwords($key);
			$key = str_replace(' ','_', $key);

			$headers[$key] = $value;
			}
		}
		return $headers;
	}
}
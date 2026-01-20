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
class ApiRestaurantDeposit extends ApiCommon{
    public function initialize(){
		parent::initialize();
		$this->checklogin();
	}
	
	//全部存酒
	public function orderlist(){
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;

		$datalist = Db::query("SELECT bid FROM ( select * from ".table_name('restaurant_deposit_order')." where aid=".aid." and mid=".mid." and status=1 ORDER BY id desc)a GROUP BY `bid` ORDER BY id DESC LIMIT ".(($pagenum-1)*$pernum).",{$pernum}");
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$v){
			if($v['bid']==0){
				$datalist[$k]['binfo'] = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
			}else{
				$datalist[$k]['binfo'] = Db::name('business')->where('aid',aid)->field('name,logo')->find();
			}
			$datalist[$k]['prolist'] = Db::name('restaurant_deposit_order')->where('aid',aid)->where('bid',$v['bid'])->where('mid',mid)->where('status',1)->order('id desc')->select()->toArray();
		}

		$rdata = [];
		$rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	//某商家的存酒记录
	public function orderdetail(){
		$bid = input('param.bid/d');
		if(!$bid) $bid = 0;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',$bid];
      
		if(false){}else{
            $where[] = ['mid','=',mid];
        }
	
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('restaurant_deposit_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		$sysset = [];
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
        $rdata['sysset'] = $sysset;
        $tablelist = [];
        $rdata['tablelist'] = $tablelist;
		return $this->json($rdata);
	}
    //存酒记录log
    public function orderlog(){
        $id = input('param.id/d');
        if(!$id) {
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $where = [];
        $where['aid'] = aid;
        $where['id'] = $id;
        $where['mid'] = mid;

        $data = Db::name('restaurant_deposit_order')->where($where)->find();
        if(!$data) $data = [];
        else {
            if($data['bid']<=0){
                $data['binfo'] = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
            }else{
                $data['binfo'] = Db::name('business')->where('aid',aid)->where('id',$data['bid'])->field('name,logo')->find();
            }
            $where = [];
            $where['aid'] = aid;
            $where['order_id'] = $id;
            $where['mid'] = mid;
            $log = Db::name('restaurant_deposit_order_log')->where($where)->order('id desc')->select()->toArray();
            $data['log'] =  $log;
        }
        $rdata = [];
        $rdata['status'] = 1;
        $rdata['data'] = $data;
        
        return $this->json($rdata);
    }
	//添加存酒
	public function add(){
        //查询是否能寄存
        $bid = input('param.bid/d');
        if(!$bid) $bid = 0;
        $deposit_status = Db::name('restaurant_deposit_sysset')->where(['aid'=>aid])->where('bid',$bid)->find();
         if($deposit_status['status'] == 0){
            return $this->json(['status'=>0,'msg'=>'当前不能寄存']);
         }
	
		$name = input('param.name');
		$num = input('param.num');
		$linkman = input('param.linkman');
		$tel = input('param.tel');
		$message = input('param.message');
		$pic = input('param.pic');
		$data = [];
		$data['aid'] = aid;
		$data['bid'] = $bid;
		$data['mid'] = mid;
		$data['name'] = $name;
		$data['num'] = $num;
		$data['linkman'] = $linkman;
		$data['tel'] = $tel;
		$data['message'] = $message;
		$data['pic'] = $pic;
		$data['createtime'] = time();
		$data['platform'] = platform;
		Db::name('restaurant_deposit_order')->insert($data);
		return $this->json(['status'=>1,'msg'=>'提交成功,请等待审核']);
	}
	//取酒
	public function takeout(){
		$bid = input('param.bid/d');
		$numbers = input('param.numbers/d'); //取出数量
		if(!$bid) $bid = 0;
		$orderid = input('param.orderid/d');
		$where = [];
		$where['aid'] = aid;
		$where['bid'] = $bid;
		$where['mid'] = mid;
		$where['status'] = 1;
		$time = time();
		if($orderid != '0'){
			$where['id'] = $orderid;
             //查询对应的数量
            $order = Db::name('restaurant_deposit_order')->where(['id'=>$orderid])->find();
          
            if($order['num'] == 0){
                return $this->json(['status'=>0,'msg'=>'没有可取出的物品']);
		    }

		    if($numbers > $order['num']){
		    	return $this->json(['status'=>0,'msg'=>'数量不足']);
		    }
            
            //剩余数量
            $left_number = $order['num']-$numbers ;

            $log = [
                'aid'=>$order['aid'],
                'bid'=>$order['bid'],
                'mid'=>mid,
                'order_id' => $order['id'],
                'num' => $numbers,
                'type'=>1,
                'createtime' => time(),
                'remark' => '用户取出',
                'platform' => platform
            ];

            if($left_number > 0){
                Db::name('restaurant_deposit_order')->where($where)->update(['takeout_time'=>$time,'num'=>$left_number]);
                \db('restaurant_deposit_order_log')->insert($log);
                return $this->json(['status'=>1,'msg'=>'操作成功']);
            } else {
               Db::name('restaurant_deposit_order')->where($where)->update(['status'=>2,'takeout_time'=>$time,'num'=>0]);
               $log['num'] = $order['num'];
               \db('restaurant_deposit_order_log')->insert($log);
               return $this->json(['status'=>1,'msg'=>'操作成功']);
            }

		} else {
            $orderlist = Db::name('restaurant_deposit_order')->where($where)->select()->toArray();
			if(count($orderlist) == 0){
				return $this->json(['status'=>0,'msg'=>'没有可取出的物品']);
			}
			foreach ($orderlist as $order) {
                $log = [
                    'aid'=>$order['aid'],
                    'bid'=>$order['bid'],
                    'mid'=>mid,
                    'order_id' => $order['id'],
                    'num' => $order['num'],
                    'type'=>1,
                    'createtime' => $time,
                    'remark' => '用户取出',
                    'platform' => platform
                ];
                \db('restaurant_deposit_order_log')->insert($log);
            }
			Db::name('restaurant_deposit_order')->where($where)->update(['status'=>2,'takeout_time'=>$time,'num'=>0]);
			return $this->json(['status'=>1,'msg'=>'操作成功']);
		}
	}
	
	//桌台信息
	public function gettablelist(){
        }
}
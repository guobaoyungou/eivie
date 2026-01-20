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
// | 配送员
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class YuyueWorker extends Common
{
    public function initialize(){
		parent::initialize();
	}
	//列表
    public function index(){
		$set = db('yuyue_set')->field('diyname')->where('aid',aid)->find();
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
			$where[] = ['bid','=',bid];
			if(input('param.realname')) $where[] = ['realname','like','%'.input('param.realname').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			if(input('?param.shstatus') && input('param.shstatus')!=='') $where[] = ['shstatus','=',input('param.shstatus')];
			$count = 0 + Db::name('yuyue_worker')->where($where)->count();
			$data = Db::name('yuyue_worker')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
				$member = Db::name('member')->where('id',$v['mid'])->find();
				$data[$k]['nickname'] = $member['nickname'];
				$data[$k]['mheadimg'] = $member['headimg'];
				$pszCount = Db::name('yuyue_worker_order')->where('aid',aid)->where('worker_id',$v['id'])->where('status','in','0,1,2')->count();
				$ywcCount = Db::name('yuyue_worker_order')->where('aid',aid)->where('worker_id',$v['id'])->where('status',3)->count();
				$data[$k]['pszCount'] = $pszCount;
				$data[$k]['ywcCount'] = $ywcCount;

				}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		View::assign('set',$set);
        $this->defaultSet();
		return View::fetch();
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('yuyue_worker')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}

		//分类
		$clist = Db::name('yuyue_worker_category')->Field('id,name')->where('aid',aid)->where('bid',bid)->where('status',1)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
		$bid = $info['bid']?$info['bid']:bid;
		$fwclist = Db::name('yuyue_category')->Field('id,name,pid')->where('aid',aid)->where('bid',$bid)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
		foreach($fwclist as $k=>$v){
			$child = Db::name('yuyue_category')->Field('id,name')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
			$fwclist[$k]['child'] = $child;
		}
		$isapply = false;
		View::assign('isapply',$isapply);
		View::assign('fwclist',$fwclist);
		View::assign('clist',$clist);
		View::assign('info',$info);
		return View::fetch();
	}
	public function save(){
		$info = input('post.info/a');
		$hasun = Db::name('yuyue_worker')->where('aid',aid)->where('id','<>',$info['id'])->where('un',$info['un'])->find();
		if($hasun){
			return json(['status'=>0,'msg'=>'该账号已被占用']);
		}
		$hasrealname = Db::name('yuyue_worker')->where('aid',aid)->where('bid',bid)->where('id','<>',$info['id'])->where('realname',$info['realname'])->find();
		if($hasrealname){
			return json(['status'=>0,'msg'=>'该姓名已存在，请填写其他姓名']);
		}
		if($info['id']){
			//$member = Db::name('member')->where('id',$info['mid'])->find();
			//$info['headimg'] = $member['headimg'];
			$info['pwd'] = md5($info['pwd']);
			Db::name('yuyue_worker')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑人员'.$info['id']);
		}else{
			if($info['pwd']!=''){
				$info['pwd'] = md5($info['pwd']);
			}else{
				unset($info['pwd']);
			}
			$info['aid'] = aid;
			$info['bid'] = bid;
			$info['createtime'] = time();
			//$member = Db::name('member')->where('id',$info['mid'])->find();
			//$info['headimg'] = $member['headimg'];
			$id = Db::name('yuyue_worker')->insertGetId($info);
			\app\common\System::plog('添加人员'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//改状态
	public function setst(){
		$st = input('post.st/d');
		$ids = input('post.ids/a');
		Db::name('yuyue_worker')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog('配送员改状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('yuyue_worker')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
		\app\common\System::plog('配送员删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//获取配送员
	public function getpeisonguser(){
		$set = Db::name('yuyue_set')->where('aid',aid)->where('bid',bid)->find();

		$orderid = input('?param.orderid')?input('param.orderid/d'):0;//单个派送
		if($orderid){
			$orderids = '';//设置多id为空
			$orders = Db::name(input('param.type'))->where('id',$orderid)->select()->toArray();
			if($orders) $bid = $orders[0]['bid'];
		}else{
			}
		if(!$orders) return json(['status'=>0,'msg'=>'订单数据不存在','peisonguser'=>[],'paidantype'=>$set['paidantype'],'psfee'=>0,'ticheng'=>0]);

		$psfee = $ticheng = 0;
		
		if($set['paidantype'] == 0){ //抢单模式
			$selectArr[] = ['id'=>0,'title'=>'--服务人员抢单--'];
		}else{
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['status','=',1];
            //使用平台配送员
            $where[] = ['bid','=',$bid];
			$peisonguser = Db::name('yuyue_worker')->where($where)->order('sort desc,id')->select()->toArray();
			if($peisonguser){
				foreach($peisonguser as &$v){
					$dan = Db::name('yuyue_worker_order')->where('worker_id',$v['id'])->where('status','in','0,1')->count();
					$v['title'] = $title = $v['realname'].'-'.$v['tel'].'(进行中'.$dan.'单)';
				}
				unset($v);
			}
		}

		$selectArrIds = $selectArr = [];
        foreach($orders as $order){
            $ticheng+= $order['paidan_money'];
            $psfee  += $order['paidan_money'] * (1 + $set['businessfee']*0.01);

            //需要验证订单预约时间
            if($peisonguser){
                $dealyytime = \app\common\Order::dealyytime($order['yy_time'],$order['begintime']);
                $yybegintime = $dealyytime['yybegintime'];//开始时间
                $yybegintime = $dealyytime['yybegintime'];//结束时间
                foreach($peisonguser as $v){
                    //查看该服务人员该时间是否已经预约出去
                    $status = 1;
                    $workerorders = Db::name('yuyue_order')->where('worker_id',$v['id'])->where('aid',aid)->where('status','in','1,2')->select()->toArray();
                    if($workerorders){
                        foreach($workerorders as $ov){
                            $dealyytime2 = \app\common\Order::dealyytime($ov['yy_time'],$ov['begintime']);
                            $yybegintime2 = $dealyytime2['yybegintime'];//开始时间
                            $yyendtime2 = $dealyytime2['yyendtime'];//结束时间
                            if( ($yybegintime2==$yybegintime || $yyendtime2==$yyendtime) || ($yybegintime2<$yybegintime && $yyendtime2>=$yybegintime) || ($yybegintime2<=$yyendtime && $yyendtime2 > $yyendtime) ) {
                                $status = -1;
                            }
                        }
                        unset($ov);
                    }
                    unset($yv);
                    if($status == 1){
                        if(!in_array($v['id'],$selectArrIds)){
                            $selectArrIds[] = $v['id'];
                            $selectArr[] = ['id'=>$v['id'],'title'=>$v['title'],'status'=>$status];
                        }
                    }else{
                        if(in_array($v['id'],$selectArrIds)){
                            $pos = array_search($v['id'],$selectArrIds);
                            array_splice($selectArrIds,$pos,1);
                            array_splice($selectArr,$pos,1);
                        }
                    }
                }
                unset($v);
            }
        }
		return json(['status'=>1,'peisonguser'=>$selectArr,'paidantype'=>$set['paidantype'],'psfee'=>$psfee,'ticheng'=>$ticheng]);
	}

	//派单
	public function peisong(){
		$orderid  = input('?post.orderid')?input('post.orderid/d'):0;//单订单ID
		$orderids = input('?post.orderids')?input('post.orderids'):'';//多个订单ID
		$worker_id= input('post.worker_id/d');
		if(!$orderid && !$orderids) return json(['status'=>0,'msg'=>'请选择要派送的订单']);
		$allBid = false;
        $where = [];
        $where[] = ['aid','=',aid];
        if($orderid){
        	$orderids = '';//多个订单ID标记为空
        	$where[] = ['id','=',$orderid];
        }else if($orderids){
        	$allBid = false;
        	$where[] = ['id','in',$orderids];
        }else{
        	$where[] = ['id','=',0];
        }

        if(!$allBid){
            $where[] = ['bid','=',bid];
        }
		$orders = Db::name('yuyue_order')->where($where)->select()->toArray();
		if(!$orders) return json(['status'=>0,'msg'=>'订单不存在']);

        $worker_sametime_yynum = 1;//服务人员同一时间接单次数 0为不限制(目前仅一种预约商品，可设置统一默认次数)
        $yy_times = [];
        $time_orders_count = []; // 用于统计每个时间段要派单的数量
		//检查参数
		foreach($orders as $order){
            $yy_time = $order['yy_time'];
            if(!isset($time_orders_count[$yy_time])) {
                $time_orders_count[$yy_time] = 0;
            }
            $time_orders_count[$yy_time]++;

			if($worker_id && $worker_sametime_yynum == 1 && in_array($order['yy_time'],$yy_times)){
				return json(['status'=>0,'msg'=>'订单派单失败，预约时间段'.$order['yy_time'].'存在订单重复']);
			}
			$yy_times[] = $order['yy_time'];
			if($order['status']!=1 && $order['status']!=12) return json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单状态不符合派单']);
			//if($order['worker_id'] && $order['worker_id']>0) return json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单已有服务人员']);

			//服务人员订单
            $hasorder = Db::name('yuyue_worker_order')->where('orderid',$order['id'])->field('status')->find();
            if($hasorder && $hasorder['status'] > 1 && $hasorder['status'] !=10){
            	return json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单已派单，不可变更']);
            }

            //查看该服务人员该时间是否已经预约出去
            if($worker_id && $worker_sametime_yynum != 0){
                $dealyytime  = \app\common\Order::dealyytime($order['yy_time'],$order['begintime']);
                $yybegintime = $dealyytime['yybegintime'];//开始时间
                $yyendtime = $dealyytime['yyendtime'];//结束时间
                $workerorders = Db::name('yuyue_order')->where('worker_id',$worker_id)->where('aid',aid)->where('status','in','1,2')->select()->toArray();
                $same_time_count = 0; //同一时间订单数
                if($workerorders){
                    foreach($workerorders as $ov){
                        $dealyytime2 = \app\common\Order::dealyytime($ov['yy_time'],$ov['begintime']);
                        $yybegintime2 = $dealyytime2['yybegintime'];//开始时间
                        $yyendtime2 = $dealyytime2['yyendtime'];//结束时间
                        if( ($yybegintime2==$yybegintime || $yyendtime2==$yyendtime) || ($yybegintime2<$yybegintime && $yyendtime2>=$yybegintime) || ($yybegintime2<=$yyendtime && $yyendtime2 > $yyendtime) ) {
                            $same_time_count++;
                        }
                    }
                    unset($ov);
                }
                $total_count = $same_time_count + $time_orders_count[$order['yy_time']];
                if($total_count > $worker_sametime_yynum) return json(['status'=>0,'msg'=>'订单号'.$order['ordernum'].'订单派单失败，该预约时间段服务人员已有订单在服务']);
            }
		}
		$successnum = 0;
		$failnum = 0;
		foreach($orders as $order){

			//再次查看该服务人员该时间是否已经预约出去
			if($orderids && $worker_id && $worker_sametime_yynum != 0){
				$status = 1;
                $dealyytime  = \app\common\Order::dealyytime($order['yy_time'],$order['begintime']);
                $yybegintime = $dealyytime['yybegintime'];//开始时间
                $yyendtime = $dealyytime['yyendtime'];//结束时间
                $workerorders = Db::name('yuyue_order')->where('worker_id',$worker_id)->where('aid',aid)->where('status','in','1,2')->select()->toArray();
                $same_time_count = 0;
                if($workerorders){
                    foreach($workerorders as $ov){
                        $dealyytime2 = \app\common\Order::dealyytime($ov['yy_time'],$ov['begintime']);
                        $yybegintime2 = $dealyytime2['yybegintime'];//开始时间
                        $yyendtime2 = $dealyytime2['yyendtime'];//结束时间
                        if( ($yybegintime2==$yybegintime || $yyendtime2==$yyendtime) || ($yybegintime2<$yybegintime && $yyendtime2>=$yybegintime) || ($yybegintime2<=$yyendtime && $yyendtime2 > $yyendtime) ) {
                            $same_time_count++;

                            //已经达到限制数量 则不再派单
                            if($same_time_count >= $worker_sametime_yynum) {
                                $status = -1;
                            }
                        }
                    }
                    unset($ov);
                }
                if($status != 1){
                	$failnum++;
                    continue;
                }
			}

			//取出该订单的服务人员
			$fwpeoid = Db::name('yuyue_product')->where('id',$order['proid'])->where('aid',aid)->where('bid',$order['bid'])->value('fwpeoid');
			$rs = \app\model\YuyueWorkerOrder::create($order,$worker_id,$fwpeoid);
			if($rs['status']==0){
				$failnum++;
				if($orderid || ($orderids && count($orderids) == 1)) return json($rs);//单个的需要报错，多个的不需要报错，只统计错误个数
			}else{
				$successnum++;//多个的统计成功个数
				\app\common\System::plog('预约派单'.$order['id']);
			}
		}

		$remark = '';
		if($orderids){
			$remark = ',成功派单'.$successnum.'个订单,失败'.$failnum.'个订单';
		}
		return json(['status'=>1,'msg'=>'操作成功'.$remark]);
	}

	//审核
	public function setcheckst(){
		$st = input('post.st/d');
		$id = input('post.id/d');
		$reason = input('post.reason');
		$worker = Db::name('yuyue_worker')->where('aid',aid)->where('id',$id)->find();
		if(!$worker) return json(['status'=>0,'msg'=>'信息不存在']);
	
		if($st == 1){
			Db::name('yuyue_worker')->where('aid',aid)->where('id',$id)->update(['shstatus'=>$st,'status'=>1]);
			//通过后将类目下的商品服务人员增加
			$list = Db::name('yuyue_product')->where('aid',aid)->where('bid',$worker['bid'])->where('cid','in',$worker['fwcids'])->select()->toArray();
			foreach($list as $l){
				$peoids = '';
				if($l['fwpeoid']){
					$peoarr = explode(',',$l['fwpeoid']);
					if(!in_array($worker['id'],$peoarr)) $peoids = $l['fwpeoid'].','.$worker['id'];
					else 
						$peoids = $l['fwpeoid'];
				}else{
					$peoids = $worker['id'];
				}	
				Db::name('yuyue_product')->where('id',$l['id'])->update(['fwpeoid'=>$peoids]);
			}
		}else{
			Db::name('yuyue_worker')->where('aid',aid)->where('id',$id)->update(['shstatus'=>$st,'reason'=>$reason]);
		}
		//审核结果通知
		$tmplcontent = [];
		$tmplcontent['first'] = ($st == 1 ? '恭喜您的申请入驻通过' : '抱歉您的提交未审核通过');
		$tmplcontent['remark'] = ($st == 1 ? '' : ($reason.'，')) .'请点击查看详情~';
		$tmplcontent['keyword1'] = $worker['realname'].'师傅申请';
		$tmplcontent['keyword2'] = ($st == 1 ? '已通过' : '未通过');
		$tmplcontent['keyword3'] = date('Y年m月d日 H:i');
        $tempconNew = [];
        $tempconNew['thing9'] = $worker['realname'].'师傅申请';
        $tempconNew['thing2'] = ($st == 1 ? '已通过' : '未通过');
        $tempconNew['time3'] = date('Y年m月d日 H:i');
		$rs = \app\common\Wechat::sendtmpl(aid,$worker['mid'],'tmpl_shenhe',$tmplcontent,m_url('yuyue/yuyue/apply'),$tempconNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['thing8'] = $worker['realname'].'师傅申请';
		$tmplcontent['phrase2'] = ($st == 1 ? '已通过' : '未通过');
		$tmplcontent['thing4'] = $st == 1?'您的申请未通过':'您的申请已通过';
		$rs = \app\common\Wechat::sendwxtmpl(aid,$worker['mid'],'tmpl_shenhe',$tmplcontent,'yuyue/yuyue/apply','');
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	public function refund(){
		$id = input('post.id/d');
		if(bid == 0){
			$worker = Db::name('yuyue_worker')->where('id',$id)->where('aid',aid)->find();
		}else{
			$worker = Db::name('yuyue_worker')->where('id',$id)->where('aid',aid)->where('bid',bid)->find();
		}
		$order = Db::name('yuyue_workerapply_order')->where('ordernum',$worker['ordernum'])->where('aid',aid)->where('bid',bid)->find();
		if(!$order) return json(['status'=>0,'msg'=>'支付订单不存在']);
		if($order['status']!=1 || $order['refund_status']==1){
			return json(['status'=>0,'msg'=>'该订单状态不允许退款']);
		}
		if($order['price'] > 0){
			$order['totalprice'] = $order['price'];
			$rs = \app\common\Order::refund($order,$order['price'],'预约申请费用后台退款');
			if($rs['status']==1){
				Db::name('yuyue_workerapply_order')->where('id',$order['id'])->where('aid',aid)->update(['status'=>2,'refund_status'=>1,'refund_money' => $order['price'], 'refund_time'=>time()]);
			}else{
				return json(['status'=>0,'msg'=>$rs['msg']]);
			}
		}
		
	
        $refund_money = $order['price'];

		//退款成功通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的预约申请费用已经完成退款，¥'.$refund_money.'已经退回您的付款账户，请留意查收。';
		$tmplcontent['remark'] = '请点击查看详情~';
		$tmplcontent['orderProductPrice'] = $refund_money.'元';
		$tmplcontent['orderProductName'] = $order['title'];
		$tmplcontent['orderName'] = $order['ordernum'];
        $tmplcontentNew = [];
        $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
        $tmplcontentNew['thing2'] = $order['title'];//商品名称
        $tmplcontentNew['amount3'] = $refund_money;//退款金额
		\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount6'] = $refund_money;
		$tmplcontent['thing3'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		
		$tmplcontentnew = [];
		$tmplcontentnew['amount3'] = $refund_money;
		$tmplcontentnew['thing6'] = $order['title'];
		$tmplcontentnew['character_string4'] = $order['ordernum'];
		\app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

		//短信通知
		$member = Db::name('member')->where('id',$order['mid'])->find();
		if($member['tel']){
			$tel = $member['tel'];
		}else{
			$tel = $order['tel'];
		}
		$rs = \app\common\Sms::send(aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$refund_money]);
		\app\common\System::plog('预约申请费用退款'.$order['id']);
		return json(['status'=>1,'msg'=>'已退款成功']);
	}
    function defaultSet(){
        $set = Db::name('yuyue_set')->where('aid',aid)->where('bid',bid)->find();
        if(!$set){
            Db::name('yuyue_set')->insert(['aid'=>aid,'bid' => bid]);
        }
    }
    //选择人员
    public function chooseyuyueworker(){
        return View::fetch();
    }

    //获取商品信息
    public function getworker(){
        $workerid = input('post.workerid/d');
        $worker = Db::name('yuyue_worker')->where('id',$workerid)->where('aid',aid)->find();
        return json(['worker'=>$worker]);
    }

    //添加/扣除余额
    public function addMoney(){
        $mid = input('param.id/d');
        $money = input('param.money');
        $remark = input('param.remark');
        if($this->user['isadmin']==0){
            return json(['status'=>0,'msg'=>'无权限操作']);
        }
        if($money == 0 || $money == ''){
            return json(['status'=>0,'msg'=>'请输入金额']);
        }
        $where = [];
        $where[] = ['id','=',$mid];
        $where[] = ['aid','=',aid];
        $member = Db::name('yuyue_worker')->where($where)->find();
        if(!$member){
            return json(['status'=>0,'msg'=>t('教练').'不存在']);
        }

        $money = floatval($money);

        $actionname = '充值';
        if($money < 0) $actionname = '扣费';

        if(session('IS_ADMIN')==0){
            $user = Db::name('admin_user')->where('aid',aid)->where('id',$this->uid)->find();
            $remark1 = '商家'.$actionname.'，操作员：'.$user['un'];
        }else{
            $remark1 = '商家'.$actionname;
        }
        if(!$remark) $remark = $remark1;
        $rs = \app\common\YuyueWorker::addmoney($member['aid'],$member['bid'],$member['id'],$money,$remark);

		\app\common\System::plog('给'.t('教练').$mid.$actionname.'，金额'.$money);
		if($rs['status']==0) return json($rs);
		return json(['status'=>1,'msg'=>$actionname.'成功']);
    }
}

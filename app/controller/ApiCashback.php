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
class ApiCashback extends ApiCommon
{

    public function initialize(){
        parent::initialize();
		$this->checklogin();
    }
    public function index(){
        $where = [];
        $where[] = ['cashback_member.aid','=',aid];
        $where[] = ['cashback_member.mid','=',mid];
        $pernum = 20;
        $pagenum = input('post.pagenum');
        $status = input('post.status');
        $time = time();
        if($status == 1){
            $where[] = ['c.starttime','<',$time];
            $where[] = ['c.endtime','>',$time];
            if(getcustom('yx_cashback_stop')){
                $where[] = ['c.status','=',1];
            }
        }elseif($status == 2){
            if(getcustom('yx_cashback_stop')){
                $where[] = function ($query) use ($time) {
                    $query->where('c.endtime', '<', $time)->whereOr('c.status', '=', 0);
                };
            }else{
                $where[] = ['c.endtime','<',$time];
            }
        }
        if(!$pagenum) $pagenum = 1;
        $datalist = Db::name('cashback_member')->alias('cashback_member')->field('cashback_member.*,c.name,c.starttime,c.endtime,c.status as cashback_status,c.bid,c.shuoming')->join('cashback c','c.id=cashback_member.cashback_id')->where($where)->page($pagenum,$pernum)->order('cashback_member.id desc')->select()->toArray();
        
        foreach($datalist as &$v){
            if($v['starttime'] > $time){
                $v['status'] = '未开始';
            }elseif($v['endtime'] < $time){
                $v['status'] = '已结束';
            }else{
                $v['status'] = '进行中';
            }
            if(getcustom('yx_cashback_stop')){
                if($v['cashback_status'] == 0 || time() > $v['endtime']){
                    $v['status'] = '已结束';
                }
            }
            $cashback_num = 0;
            $v['back_type_name'] = '额度';    
            if($v['back_type'] == 1){
                $cashback_num = $v['cashback_money'];                
                $v['back_type_name'] = t('余额');                
            }elseif($v['back_type'] == 2){
                $cashback_num = $v['commission'];
                $v['back_type_name'] = t('佣金'); 
            }elseif($v['back_type'] == 3){
                $cashback_num = $v['score'];
                $v['back_type_name'] = t('积分');    
            }
            $v['cashback_num'] = $cashback_num;
            $v['progress'] = $v['cashback_money_max']>0 && $cashback_num >0 ?round($cashback_num/$v['cashback_money_max']*100,2):0;
            if($v['bid'] > 0){
                $v['business_name'] = Db::name('business')->where('aid',$v['aid'])->where('id',$v['bid'])->value('name');
            }
            $v['back_name'] = '';
            if($v['ogid'] > 0){
                $v['back_name'] = Db::name('shop_order_goods')->where('id',$v['ogid'])->value('name');
            }
            if(getcustom('yx_cashback_decay')){
                if($v['type'] == 'maidan'){
                    $v['back_name'] = Db::name('maidan_order')->where('aid',$v['aid'])->where('id',$v['orderid'])->value('title');
                }
            }
        }
        $rdata = [];
        $rdata['status']   = 1;
        $rdata['datalist'] = $datalist;
        $rdata['show_rebate']   = 0;


        // 【待返余额】、【待返佣金】、【待返积分】 和已返余额、佣金、积分
        if(getcustom('yx_cashback_show_rebate')){
            $send_money = 0;
            $send_commission = 0;
            $send_score = 0;
            $un_send_money = 0;
            $un_send_commission = 0;
            $un_send_score = 0;
            
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $lists = Db::name('cashback_member')->where($where)->select()->toArray();
            foreach ($lists as $key => $value) {
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['cashback_id','=',$value['cashback_id']];
                $where[] = ['pro_id','=',$value['pro_id']];
                if(getcustom('yx_cashback_yongjin')){
                    $where[] = ['cashback_yongjin','<>',2];
                }

                if($value['back_type'] == 1){//余额
                    // 已发放的
                    $send_money += $value['cashback_money'];

                    $where[] = ['back_type','=',1];
                    $where[] = ['moneystatus','=',1];
                    // 未发放的
                    $un_send = Db::name('shop_order_goods_cashback')->where($where)->sum(Db::raw("allmoney-money"));
                    // 限额
                    if(getcustom('cashback_max') && $value['cashback_money_max']>0){
                        if($value['cashback_money_max'] > $value['cashback_money']){
                            //最大可追加金额
                            $cashback_money_max = $value['cashback_money_max'] - $value['cashback_money'];
                            if($cashback_money_max < $un_send){
                                $un_send = $cashback_money_max;
                            }    
                        }else{
                           $un_send = 0;
                        }
                    }
                    if(getcustom('yx_cashback_show_rebate')){
                        $un_send += $value['back_after'] ?? 0;
                    }
                    $un_send_money += $un_send;
                }
                if($value['back_type'] == 2){//佣金
                    // 已发放的
                    $send_commission += $value['commission'];

                    $where[] = ['back_type','=',2];
                    $where[] = ['commissionstatus','=',1];
                    // 未发放的
                    $un_send = Db::name('shop_order_goods_cashback')->where($where)->sum(Db::raw("allcommission-commission"));
                    // 限额
                    if(getcustom('cashback_max') && $value['cashback_money_max']>0){
                        if($value['cashback_money_max'] > $value['commission']){
                            //最大可追加金额
                            $cashback_money_max = $value['cashback_money_max'] - $value['commission'];
                            if($cashback_money_max < $un_send){
                                $un_send = $cashback_money_max;
                            }    
                        }else{
                           $un_send = 0;
                        }
                    }
                    if(getcustom('yx_cashback_show_rebate')){
                        $un_send += $value['back_after'] ?? 0;
                    }
                    $un_send_commission += $un_send;
                }
                if($value['back_type'] == 3){//积分
                    // 已发放的
                    $send_score += $value['score'];

                    $where[] = ['back_type','=',3];
                    $where[] = ['scorestatus','=',1];
                    // 未发放的
                    $un_send = Db::name('shop_order_goods_cashback')->where($where)->sum(Db::raw("allscore-score"));
                    // 限额
                    if(getcustom('cashback_max') && $value['cashback_money_max']>0){
                        if($value['cashback_money_max'] > $value['score']){
                            //最大可追加金额
                            $cashback_money_max = $value['cashback_money_max'] - $value['score'];
                            if($cashback_money_max < $un_send){
                                $un_send = $cashback_money_max;
                            }    
                        }else{
                           $un_send = 0;
                        }
                    }
                    if(getcustom('yx_cashback_show_rebate')){
                        $un_send += $value['back_after'] ?? 0;
                    }
                    $un_send_score += $un_send;
                }
            }
            $rdata['send_money'] = dd_money_format($send_money,2);
            $rdata['send_commission'] = dd_money_format($send_commission,2);
            $rdata['send_score'] = dd_money_format($send_score,0);
            $rdata['un_send_money'] = dd_money_format($un_send_money,2);
            $rdata['un_send_commission'] = dd_money_format($un_send_commission,2);
            // $rdata['un_send_commission'] = 1000000555550000.55;
            $rdata['un_send_score'] = dd_money_format($un_send_score,0);
            $rdata['show_rebate']   = 1;
        }
        
        $money = Db::name('cashback_member')->where('aid',aid)->where('mid',mid)->sum('cashback_money');
        $commission = Db::name('cashback_member')->where('aid',aid)->where('mid',mid)->sum('commission');
        $total_send = $money + $commission;
        $rdata['total_send'] = dd_money_format($total_send,2);
        return $this->json($rdata);
    }

    public function recordlog(){
        $where = [];
        $where[] = ['cml.aid','=',aid];
        $where[] = ['cml.mid','=',mid];
        $pernum = 20;
        $pagenum = input('post.pagenum');
        $cashback_id = input('post.cashback_id');
        $pro_id = input('post.pro_id');
        $where[] = ['cml.pro_id','=',$pro_id];
        $where[] = ['cml.cashback_id','=',$cashback_id];
        if(getcustom('yx_cashback_decay')){
            $id = input('post.id');
            $where[] = ['cml.cashback_member_id','=',$id];
        }
        $time = time();
        if(!$pagenum) $pagenum = 1;
        $datalist = Db::name('cashback_member_log')->alias('cml')->field('cml.*,c.name,c.starttime,c.endtime')->join('cashback c','c.id=cml.cashback_id')->where($where)->page($pagenum,$pernum)->order('cml.id desc')->select()->toArray();
        
        foreach($datalist as &$v){
            $v['back_type_name'] = '额度';    
            $cashback_num = 0;
            if($v['back_type'] == 1){
                $cashback_num = $v['cashback_money'];                
                $v['back_type_name'] = t('余额');                
            }elseif($v['back_type'] == 2){
                $cashback_num = $v['commission'];
                $v['back_type_name'] = t('佣金'); 
            }elseif($v['back_type'] == 3){
                $cashback_num = $v['score'];
                $v['back_type_name'] = t('积分');  
            }
            $v['cashback_num'] = $cashback_num;   
            $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);;   
        }
        $rdata = [];
        $rdata['status']   = 1;
        $rdata['datalist'] = $datalist;
        return $this->json($rdata);
    }

    public function cashback_log(){
        if(getcustom('yx_cashback_log')){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['return_type','in',[0,1,2]];
            $pernum = 20;
            $pagenum = input('post.pagenum');

            if(!$pagenum) $pagenum = 1;
            $datalist = Db::name('cashback_log')->field('*')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();

            foreach($datalist as &$v){
                $v['create_time'] = date('Y-m-d H:i:s',$v['createtime']);;
            }
            //汇总数据
            $total_data = Db::name('shop_order_goods_cashback')
                ->where('aid',aid)
                ->where('mid',mid)
                ->where('IFNULL(return_type,"") !=3')
                ->field('sum(allmoney+allcommission+allscore) as chahback_price,sum(money+commission+score) as have_release')->find();
            if($total_data){
                $total_data['have_release'] = bcmul($total_data['have_release'],1,2);
                $total_data['remain_release'] = bcsub($total_data['chahback_price'] , $total_data['have_release'],2);
            }else{
                $total_data = [
                    'cashback_price' => 0,
                    'have_release' => 0,
                    'remain_release' => 0
                ];
            }
            //今日新增
            $today_add = Db::name('cashback_log')
                ->where('aid',aid)
                ->where('mid',mid)
                ->where('back_price','>',0)
                ->where('IFNULL(return_type,"") !=3')
                ->where('createtime','>=',strtotime(date('Y-m-d')))
                ->order('id desc')->sum('back_price');
            $total_data['today_add'] = $today_add??0;
            //今日释放
            $today_release = Db::name('cashback_log')
                ->where('aid',aid)
                ->where('mid',mid)
                ->where('back_price','<',0)
                ->where('IFNULL(return_type,"") !=3')
                ->where('createtime','>=',strtotime(date('Y-m-d')))
                ->sum('back_price');
            $total_data['today_release'] = abs($today_release);
            $rdata = [];
            $rdata['status']   = 1;
            $rdata['datalist'] = $datalist;
            $rdata['total_data'] = $total_data;
            return $this->json($rdata);
        }
    }

    public function og_log(){
        if(getcustom('yx_cashback_multiply')){
            $pernum = 20;
            $pagenum = input('post.pagenum');

            $set = [];
            $set['cashback_multiply_yeji_type'] = 0;
            $set = $this->sysset;

            if(!$pagenum) $pagenum = 1;
            $st = input('st')?:0;
            $order = 'c.id desc';
            $where = [];
            $where[] = ['c.aid','=',aid];
            $where[] = ['c.return_type','=',3];
            $where[] = ['c.mid','=',mid];
            if($st==1){
                $where[] = ['c.status','=',2];
            }else{
                $where[] = ['c.status','in',[0,1]];
            }
            $datalist = Db::name('shop_order_goods_cashback')->alias('c')
                ->field('c.*,member.nickname,member.headimg')
                ->join('member member','c.mid=member.id')
                ->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();

            foreach($datalist as &$v){
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $have_send = 0;
                if($v['back_type']==1){
                    $have_send = $v['money'];
                }
                if($v['back_type']==2){
                    $have_send = $v['commission'];
                }
                if($v['back_type']==3){
                    $have_send = $v['score'];
                }
                $remain = bcsub($v['back_price'],$have_send,2);
                //20250417修改 前台展示发放数量和剩余数量用订单金额
//                $remain = $v['totalprice'];
//                $v['back_price'] = $v['totalprice'];
//                if(getcustom('yx_cashback_multiply_yejitype')) {
//                    if ($set['cashback_multiply_yeji_type'] == 1) {
//                        $remain = $v['back_price'];
//                    }
//                }
                $v['remain'] = $remain;
                $v['have_send'] = $have_send;
                if($v['status']==2){
                    $v['remain'] = 0;
                }
            }
            //前端是否展示头部业绩数据
            $showyejidata = true;
            //汇总数据
            $total_data = [];
            if($showyejidata){
                //汇总数据
                $total_data = Db::name('shop_order_goods_cashback')
                    ->where('aid',aid)
                    ->where('mid',mid)
                    ->where('return_type','=',3)
                    ->where('status','in',[0,1])
                    ->field('sum(allmoney+allcommission+allscore) as chahback_price,sum(money+commission+score) as have_release')->find();
                if($total_data){
                    //待释放
                    $total_data['have_release'] = dd_money_format($total_data['have_release'],2);
                    $total_data['remain_release'] = bcsub($total_data['chahback_price'] , $total_data['have_release'],2);
                }else{
                    $total_data = [
                        'chahback_price' => 0,
                        'have_release' => 0,
                        'remain_release' => 0
                    ];
                }
                //今日新增
                $today_add = Db::name('shop_order_goods_cashback')
                    ->where('aid',aid)
                    ->where('mid',mid)
                    ->where('return_type','=',3)
                    ->where('createtime','>=',strtotime(date('Y-m-d')))
                    ->sum('back_price');
                $total_data['today_add'] = dd_money_format($today_add,2);;
                //今日释放
                $today_release = Db::name('cashback_log')
                    ->where('aid',aid)
                    ->where('mid',mid)
                    ->where('return_type',3)
                    ->where('back_price','<',0)
                    ->where('createtime','>=',strtotime(date('Y-m-d')))
                    ->sum('back_price');
                $total_data['today_release'] = dd_money_format(abs($today_release),2);
            }

            $rdata = [];
            $rdata['status']   = 1;
            $rdata['datalist'] = $datalist;
            $rdata['total_data'] = $total_data;
            $rdata['set'] = $set;
            $rdata['showyejidata'] = $showyejidata;

            $showLastCircleYeji2 = false;//前端是否展示上期平台业绩
            if($this->sysset['show_yejidata'] && $this->sysset['show_last_circle_yeji2']){
                $showLastCircleYeji2 = true;
            }
            $rdata['showLastCircleYeji'] = $showLastCircleYeji2;
            return $this->json($rdata);
        }
    }
    public function og_detail_log(){
        if(getcustom('yx_cashback_multiply')){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $og_id = input('og_id');
            $where[] = ['og_id','=',$og_id];


            $showyejidata = $this->sysset['show_yejidata']??false;//前端是否展示头部业绩数据
            $showHaveRelease = $this->sysset['show_have_release']??false;//前端是否展示已释放
            $showRemainRelease = $this->sysset['show_remain_release']??false;//前端是否展示待释放
            $showLastCircleYeji = $this->sysset['show_last_circle_yeji']??false;//前端是否展示上期平台业绩
            $showNextCircleYeji = $this->sysset['show_next_circle_yeji']??false;//前端是否展示下期平台业绩

            //汇总数据
            $total_data = [];
            if($showyejidata){
                //汇总数据
                $total_data = Db::name('shop_order_goods_cashback')
                    ->where('aid',aid)
                    ->where('mid',mid)
                    ->where('sog_id',$og_id)
                    ->find();
                $cash_back = Db::name('cashback')->where('aid',aid)->where('id',$total_data['cashback_id'])->find();
            }

            $pernum = 20;
            $pagenum = input('post.pagenum');

            if(!$pagenum) $pagenum = 1;
            $datalist = Db::name('cashback_log')->field('*')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();

            foreach($datalist as $k=>$v){
                $datalist[$k]['create_time'] = date('Y-m-d H:i:s',$v['createtime']);
                $datalist[$k]['is_yuji'] = 0;
            }
            if($datalist && $pagenum==1){
                if($showyejidata){
                    if($cash_back['show_future_back']==1 && count($datalist)<$total_data['circle_max']+1){
                        //预估下一期
                        $circle = $total_data['send_circle'] + 1;
                        $min_price = $total_data['last_circle_send'];
                        $max_price = bcadd($min_price,bcmul($min_price,$total_data['circle_add']/100,4),2);
                        $next_circle = [
                            'back_price' => $min_price.'~'.$max_price,
                            'remark' => '第'.$circle.'期预估待释放',
                            'create_time' => date('Y-m-d H:i:s'),
                            'is_yuji' => 1
                        ];
                        if($total_data['back_type'] == 1){
                            $sendtime = $total_data['money_sendtime'];
                        }else if($total_data['back_type'] == 2){
                            $sendtime = $total_data['commission_sendtime'];
                        }else if($total_data['back_type'] == 3){
                            $sendtime = $total_data['score_sendtime'];
                        }
                        //增值比例未达到要求时判断时间，次月按实际增值比例发放
                        $s_time = strtotime(date('Y-m-1',$sendtime));
                        $e_time = strtotime(date('Y-m-t 23:59:59',$sendtime));
                        $back_count = Db::name('cashback_log')->where('mid',$total_data['mid'])->where('og_id',$total_data['sog_id'])->where('createtime','between',[$s_time,$e_time])->count();
                        //1，首期不作为判断次数
                        //2，当月如果发放超过1次，那么判断为下下月
                        //3，当月如果仅发放1次，那么判断为次月
                        $first_back_time = Db::name('cashback_log')->where('mid',$total_data['mid'])->where('og_id',$total_data['sog_id'])->order('id asc')->value('createtime');
                        if($first_back_time>=$s_time){
                            //第一次发放时间在本月内，减掉
                            $back_count = $back_count-1;
                        }

                        //默认下期发放时间是次月初
                        $check_time = strtotime(date('Y-m-t 24:00:00',$sendtime));
                        if($back_count>1){
                            //当月已经发放超过一次，发放时间位下下月处
                            $check_time = strtotime(date('Y-m-t 24:00:00',$check_time+86400));
                        }
                        $next_circle['create_time'] = date('Y年m月d日',$check_time).'内';


                        array_unshift($datalist,$next_circle);
                    }
                }
            }

            if($showyejidata){
                $have_release = $total_data['money'] + $total_data['commission'] + $total_data['score'];
                $total_data['have_release'] = $have_release;
                $total_data['remain_release'] = bcsub($total_data['back_price'] , $total_data['have_release'],2);

                $next_circle_yeji = bcadd($total_data['last_circle_yeji'],bcmul($total_data['last_circle_yeji'],$total_data['circle_add']/100,4),2);
                $total_data['next_circle_yeji'] = $next_circle_yeji;
            }
            $rdata = [];
            $rdata['status']   = 1;
            $rdata['datalist'] = $datalist;
            $rdata['total_data'] = $total_data;
            $rdata['showyejidata'] = $showyejidata;
            $rdata['showHaveRelease']   = $showHaveRelease;//前端是否展示已释放数据
            $rdata['showRemainRelease'] = $showRemainRelease;//前端是否展示待释放数据
            $rdata['showLastCircleYeji']= $showLastCircleYeji;//前端是否展示上期平台数据
            $rdata['showNextCircleYeji']= $showNextCircleYeji;//前端是否展示下期平台数据
            return $this->json($rdata);
        }
    }

	//签到
	public function signin(){
		if(getcustom('yx_cashback_addup_return_sign_send')){
			$cashbackSysset = Db::name('cashback_sysset')->field('back_type,send_condition')->where('aid', aid)->find();
			$cashback_back_type = $cashbackSysset['back_type'];
			if(!$cashbackSysset['send_condition']){
				return $this->json(['status' => 0, 'msg' => '当前未启用签到发放规则']);
			}

			$date = date("Y-m-d");
			$hasSigned = Db::name('cashback_addup_sign')->where('mid', mid)->where('signdate', $date)->find();
			if($hasSigned){
				return $this->json(['status' => 0, 'msg' => '今日已签到']);
			}

			try {
				Db::startTrans();

				Db::name('cashback_addup_sign')->insert([
					'aid'        => aid,
					'mid'        => mid,
					'signdate'   => $date,
					'createtime' => time()
				]);

				$record = Db::name('cashback_addup_record')->where('aid', aid)->where('mid', mid)->where('status', 0)->find();
				if(!$record) return $this->json(['status'=>0, 'msg' => '记录不存在']);

				if ($cashback_back_type == 1) {
					\app\common\Member::addmoney(aid, $record['mid'], $record['money'], t('购物返现').'领取');
				} else if ($cashback_back_type == 2) {
					\app\common\Member::addcommission(aid, $record['mid'], $record['mid'], $record['money'], t('购物返现').'领取');
				} else if ($cashback_back_type == 3) {
					\app\common\Member::addscore(aid, $record['mid'], $record['money'], t('购物返现').'领取');
				}

				Db::name('cashback_addup_record')->where('aid', aid)->where('id', $record['id'])->update(['status' => 1, 'collecttime' => time()]);
				Db::commit();
				return $this->json(['status' => 1, 'msg' => '签到成功']);
			} catch (\Exception $e) {
				Db::rollback();
				return $this->json(['status' => -1, 'msg' => '签到失败：'.$e->getMessage()]);
			}
		}
	}

    //冻结余额明细
    public function cashbackLockLog(){
        if(getcustom('yx_cashback_decmoney_lock')){
            if(request()->isPost()){
                $pagenum = input('post.pagenum');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['mid','=',mid];
                $where[] = ['aid','=',aid];
                $datalist = Db::name('member_cashback_locklog')->field("id,money,after,remark,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if($datalist){
                    foreach($datalist as &$dv){

                    }
                }else{
                    $datalist = [];
                }
                unset($dv);
                return $this->json(['status'=>1,'data'=>$datalist,'my_cashback_lock'=>$this->member['cashback_lock']]);
            }
        }
    }
}
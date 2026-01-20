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
// | 消费记录
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Payorder extends Common
{
    public $financelog_query_type = 0;//查询方式 0现有的连表查询，1不连member表独立查询速度更快
    public function initialize(){
		parent::initialize();
		if(request()->action() !='tradereport'){
            if(bid > 0) showmsg('无访问权限');
        }
        $financelog_query_type = 0;
        if(getcustom('financelog_query_type')){
            //查询方式 0现有的连表查询，1不连member表独立查询速度更快
            $financelog_query_type = $this->adminSet['financelog_query_type'];
        }
        $this->financelog_query_type = $financelog_query_type;
	}
    public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'payorder.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'payorder.id desc';
			}
            if($this->financelog_query_type==0) {
                //连表member查询
                $where = array();
                $where[] = ['member.aid', '=', aid];
                $where[] = ['payorder.status', '=', 1];
                if(!getcustom('payorder_show_money_dec')){
                    $where[] = ['payorder.money', '>', 0];
                }
                if (input('param.paytypeid') != '') {
                    $where[] = ['payorder.paytypeid', '=', trim(input('param.paytypeid'))];
                }

                if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
                if (input('param.mid')) $where[] = ['payorder.mid', '=', trim(input('param.mid'))];
                if (input('param.tel')) $where[] = ['member.tel', 'like', '%' . trim(input('param.tel')) . '%'];
                if (input('?param.status') && input('param.status') !== '') $where[] = ['payorder.status', '=', input('param.status')];
                if (getcustom('payorder_business_search')) {
                    if (input('param.bid') != '') {
                        $where[] = ['payorder.bid', '=', trim(input('param.bid'))];
                    }
                }
                if (getcustom('restaurant_cashdesk_multi_pay')) {
                    if (input('param.orderid') && input('param.type')) {
                        $where[] = ['payorder.orderid', '=', trim(input('param.orderid'))];
                        $where[] = ['payorder.type', '=', trim(input('param.type'))];
                    }
                }
                if (input('param.ordernum')) {
                    $where[] = ['payorder.ordernum', 'like', '%' . input('param.ordernum') . '%'];
                }
                if (getcustom('restaurant_cashdesk_table_merge_pay')) {
                    if (input('param.merge_ordernum')) {
                        $where[] = ['payorder.merge_ordernum', 'like', '%' . input('param.merge_ordernum') . '%'];
                    }
                }
                if(getcustom('payorder_feepercent')){
                    if(input('param.feepercent')){
                        $where[] = ['payorder.feepercent', '=', trim(input('param.feepercent'))];
                    }
                }
                //重置 总退款 的where
                $total_refund_where = $where;
                //重置 总收款 的where
                $total_where = $where;
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['payorder.createtime|payorder.refund_time', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];
//                $where[] = ['payorder.createtime','<',strtotime($ctime[1])];
                    $total_refund_where[] = ['payorder.refund_time', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];
                    $total_where[] = ['payorder.paytime', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];

                }
                $count = 0 + Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($where)->count();
                $data = Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($where)->page($page, $limit)->order($order)->select()->toArray();
                //总收款
                $total_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($total_where)->sum('payorder.money');
                //总退款
                $refund_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($total_refund_where)->sum('payorder.refund_money');
                //总消费
                $xf_money = dd_money_format($total_money - $refund_money);

                foreach($data as $k=>$v){
                    $data[$k]['dec_money'] = '';
                    if(getcustom('payorder_show_money_dec')){
                        if (in_array($v['type'],['shop','maidan'])){
                            $data[$k]['dec_money'] = Db::name($v['type'].'_order')->where('aid',aid)->where('id',$v['orderid'])->value('dec_money');
                        }
                    }
                }
            }
            else{
                //独立查询
                $where = array();
                $where[] = ['payorder.aid', '=', aid];
                $where[] = ['payorder.status', '=', 1];
                $where[] = ['payorder.money', '>', 0];
                if (input('param.paytypeid') != '') {
                    $where[] = ['payorder.paytypeid', '=', trim(input('param.paytypeid'))];
                }

                $where_m = [];
                if (input('param.nickname')) $where_m[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
                if (input('param.tel')) $where_m[] = ['member.tel', 'like', '%' . trim(input('param.tel')) . '%'];
                if($where_m){
                    $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                    if(!$mids){
                        $mids = ['-1'];
                    }
                    $where[] = ['payorder.mid','in',$mids];
                }
                if (input('param.mid')) $where[] = ['payorder.mid', '=', trim(input('param.mid'))];

                if (input('?param.status') && input('param.status') !== '') $where[] = ['payorder.status', '=', input('param.status')];
                if (getcustom('payorder_business_search')) {
                    if (input('param.bid') != '') {
                        $where[] = ['payorder.bid', '=', trim(input('param.bid'))];
                    }
                }
                if (getcustom('restaurant_cashdesk_multi_pay')) {
                    if (input('param.orderid') && input('param.type')) {
                        $where[] = ['payorder.orderid', '=', trim(input('param.orderid'))];
                        $where[] = ['payorder.type', '=', trim(input('param.type'))];
                    }
                }
                if (input('param.ordernum')) {
                    $where[] = ['payorder.ordernum', 'like', '%' . input('param.ordernum') . '%'];
                }
                if (getcustom('restaurant_cashdesk_table_merge_pay')) {
                    if (input('param.merge_ordernum')) {
                        $where[] = ['payorder.merge_ordernum', 'like', '%' . input('param.merge_ordernum') . '%'];
                    }
                }
                //重置 总退款 的where
                $total_refund_where = $where;
                //重置 总收款 的where
                $total_where = $where;
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['payorder.createtime|payorder.refund_time', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];
//                $where[] = ['payorder.createtime','<',strtotime($ctime[1])];
                    $total_refund_where[] = ['payorder.refund_time', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];
                    $total_where[] = ['payorder.paytime', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];

                }
                $count = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->where($where)->count();
                $data = Db::name('payorder')->alias('payorder')->field('payorder.*')->where($where)->page($page, $limit)->order($order)->select()->toArray();
                foreach($data as $k=>$v){
                    $member = Db::name('member')->where('id',$v['mid'])->where('aid',aid)->find();
                    $data[$k]['nickname'] = $member['nickname'];
                    $data[$k]['headimg'] = $member['headimg'];

                    $data[$k]['dec_money'] = '';
                    if(getcustom('payorder_show_money_dec')){
                        if (in_array($v['type'],['shop','maidan'])){
                            $data[$k]['dec_money'] = Db::name($v['type'].'_order')->where('aid',aid)->where('id',$v['orderid'])->value('dec_money');
                        }
                    }
                }
                //总收款
                $total_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->where($total_where)->sum('payorder.money');
                //总退款
                $refund_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->where($total_refund_where)->sum('payorder.refund_money');
                //总消费
                $xf_money = dd_money_format($total_money - $refund_money);
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'total_money'=>dd_money_format($total_money),'refund_money'=>$refund_money,'xf_money'=>$xf_money]);
		}
		if(getcustom('restaurant_cashdesk_custom_pay')){
		    $custom_paylist = Db::name('restaurant_cashdesk_custom_pay')->where('aid',aid)->where('bid',bid)->where('status',1)->order('sort desc,id desc')->select()->toArray();
		    foreach($custom_paylist as $ck=>$cv){
                $custom_paylist[$ck]['id'] = 10000+ $cv['id'];
            }
            View::assign('custom_paylist',$custom_paylist);
        }
		if(getcustom('payorder_business_search')){
            $blist = Db::name('business')->where('aid',aid)->order('sort desc,id desc')->select()->toArray();
            View::assign('blist',$blist);
        }
	    if(getcustom('restaurant_cashdesk_table_merge_pay')){
            $show_merge_order = false;
            if($this->auth_data == 'all' || in_array('RestaurantCashdesk/tableMergePay',$this->auth_data) ){
                $show_merge_order = true;
            }
            View::assign('show_merge_order',$show_merge_order);
        }
		return View::fetch();
    }
	//导出
	public function excel(){
		if(input('param.field') && input('param.order')){
			$order = 'payorder.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'payorder.id desc';
		}
        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
        if($this->financelog_query_type==0) {
            //连表member查询
            $where = array();
            $where[] = ['payorder.aid', '=', aid];
            $where[] = ['payorder.status', '=', 1];
            $where[] = ['payorder.money', '>', 0];
            $total_refund_where = $where;
            $total_where = $where;
            if (input('param.paytypeid') != '') {
                $where[] = ['payorder.paytypeid', '=', trim(input('param.paytypeid'))];
            }
            if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
            if (input('param.mid')) $where[] = ['payorder.mid', '=', trim(input('param.mid'))];
            if (input('?param.status') && input('param.status') !== '') $where[] = ['payorder.status', '=', input('param.status')];
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['payorder.createtime|payorder.refund_time', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];
                $total_refund_where[] = ['payorder.refund_time', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];
                $total_where[] = ['payorder.paytime', 'between', [strtotime($ctime[0]), strtotime($ctime[1])]];

            }
            if(getcustom('payorder_feepercent')){
                if(input('param.feepercent')){
                    $where[] = ['payorder.feepercent', '=', trim(input('param.feepercent'))];
                }
            }
            $list = Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')
                ->join('member member', 'member.id=payorder.mid', 'left')->where($where)->order($order)
                ->page($page, $limit)
                ->select()->toArray();
            $count = Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')
                ->join('member member', 'member.id=payorder.mid', 'left')->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员') . '信息';
            $title[] = '订单号';
            $title[] = '支付项目';
            $title[] = '支付金额';
            $title[] = '支付方式';
            $title[] = '发起时间';
            $title[] = '支付时间';
            $title[] = '收款多商户ID'; 
            $title[] = '最后退款时间';  //因为列表中的时间筛选增加了 退款时间搜索，这里导出也给加了，需要展示时间，不然时间对不上，容易理解错误
    
            if(getcustom('payorder_feepercent')){
                $title[] = '抽成费率';
            }
            $data = array();
            foreach ($list as $v) {
                $tdata = array();
                $tdata[] = $v['nickname'] . '(' . t('会员') . 'ID:' . $v['mid'] . ')';
                $tdata[] = ' ' . $v['ordernum'];
                $tdata[] = $v['title'];
                $tdata[] = $v['money'];
                $tdata[] = $v['paytype'];
                $tdata[] = date('Y-m-d H:i:s', $v['createtime']);
                $tdata[] = date('Y-m-d H:i:s', $v['paytime']);
                $tdata[] = $v['bid'];
                $tdata[] =$v['refund_time']? date('Y-m-d H:i:s',$v['refund_time']):'';

                if(getcustom('payorder_feepercent')){
                    $tdata[] = $v['feepercent'];
                }
                $data[] = $tdata;
            }
            $total_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($total_where)->sum('payorder.money');
            $refund_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($total_refund_where)->sum('payorder.refund_money');
            $xf_money = dd_money_format($total_money - $refund_money);
            if (!$data) { //最后一页没有数据的时候再追加，放到最后
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '总消费金额：' . dd_money_format($xf_money)
                ];
            }
        }
        else{
            //独立查询
            $where = array();
            $where[] = ['payorder.aid', '=', aid];
            $where[] = ['payorder.status', '=', 1];
            $where[] = ['payorder.money', '>', 0];
            if (input('param.paytypeid') != '') {
                $where[] = ['payorder.paytypeid', '=', trim(input('param.paytypeid'))];
            }

            $where_m = [];
            if (input('param.nickname')) $where_m[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
            if (input('param.tel')) $where_m[] = ['member.tel', 'like', '%' . trim(input('param.tel')) . '%'];
            if($where_m){
                $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                if(!$mids){
                    $mids = ['-1'];
                }
                $where[] = ['payorder.mid','in',$mids];
            }
            if (input('param.mid')) $where[] = ['payorder.mid', '=', trim(input('param.mid'))];
            if (input('?param.status') && input('param.status') !== '') $where[] = ['payorder.status', '=', input('param.status')];
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['payorder.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['payorder.createtime', '<', strtotime($ctime[1]) ];
            }
            if(getcustom('payorder_feepercent')){
                if(input('param.feepercent')){
                    $where[] = ['payorder.feepercent', '=', trim(input('param.feepercent'))];
                }
            }
            $list = Db::name('payorder')->alias('payorder')->field('payorder.*')
                ->where($where)->order($order)
                ->page($page, $limit)
                ->select()->toArray();
            $count = Db::name('payorder')->alias('payorder')->field('payorder.*')
                ->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员') . '信息';
            $title[] = '订单号';
            $title[] = '支付项目';
            $title[] = '支付金额';
            $title[] = '支付方式';
            $title[] = '发起时间';
            $title[] = '支付时间';
            if(getcustom('payorder_feepercent')){
                $title[] = '抽成费率';
            }
            $data = array();
            foreach ($list as $v) {
                $member = Db::name('member')->where('id',$v['mid'])->where('aid',aid)->find();
                $v['nickname'] = $member['nickname'];
                $v['headimg'] = $member['headimg'];
                $tdata = array();
                $tdata[] = $v['nickname'] . '(' . t('会员') . 'ID:' . $v['mid'] . ')';
                $tdata[] = ' ' . $v['ordernum'];
                $tdata[] = $v['title'];
                $tdata[] = $v['money'];
                $tdata[] = $v['paytype'];
                $tdata[] = date('Y-m-d H:i:s', $v['createtime']);
                $tdata[] = date('Y-m-d H:i:s', $v['paytime']);
                if(getcustom('payorder_feepercent')){
                    $tdata[] = $v['feepercent'];
                }
                $data[] = $tdata;
            }
            $total_money = 0 + Db::name('payorder')->alias('payorder')->field('payorder.*')->where($where)->sum('payorder.money');
            if (!$data) { //最后一页没有数据的时候再追加，放到最后
                $data[] = [
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '总消费金额：' . dd_money_format($total_money)
                ];
            }
        }
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
		$this->export_excel($title,$data);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('payorder')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('消费记录删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//头条小程序分账
	public function datasettle(){
		$ids = input('post.ids/a');
		$payorderlist = Db::name('payorder')->where('aid',aid)->where('id','in',$ids)->where('paytypeid',12)->where('status',1)->where('money','>',0)->where('issettle','in','0,2')->select()->toArray();
		$successnum = 0;
		foreach($payorderlist as $payorder){
			$rs = \app\common\Ttpay::settle(aid,$payorder['ordernum']);
			if($rs['status'] == 1){
				Db::name('payorder')->where('id',$payorder['id'])->update(['issettle'=>1]);
				$successnum++;
			}else{
				return json(['status'=>0,'msg'=>$rs['msg']]);
			}
		}
		return json(['status'=>1,'msg'=>'成功处理分账'.$successnum.'条']);
	}
	//抖音小程序订单核销
	public function datapushorder(){
		$ids = input('post.ids/a');
		$payorderlist = Db::name('payorder')->where('aid',aid)->where('id','in',$ids)->where('paytypeid',12)->where('status',1)->where('money','>',0)->where('issettle',0)->select()->toArray();
		$successnum = 0;
		foreach($payorderlist as $payorder){
			$rs = \app\common\Ttpay::pushorder(aid,$payorder['ordernum']);
			if($rs['status'] == 1){
				Db::name('payorder')->where('id',$payorder['id'])->update(['issettle'=>2]);
				$successnum++;
			}else{
				return json(['status'=>0,'msg'=>$rs['msg']]);
			}
		}
		return json(['status'=>1,'msg'=>'成功核销'.$successnum.'条']);
	}

    //营业报表
    public function tradereport(){
        if(getcustom('finance_trade_report')){
            if(request()->isAjax()){
                $other['datetype'] ='today';
                $paytypeid = input('param.paytypeid');
                if($paytypeid !='')$other['search_paytype'] =  $paytypeid;
                $ctime = input('param.ctime');
                if($ctime){
                    $ctime = explode('~',$ctime);
                    $other['starttime'] =$ctime[0];
                    $other['endtime'] =$ctime[1];
                    $other['datetype'] ='custom';
                }
               $data = \app\model\Payorder::tradeReport(aid,bid,0,2,$other);
                return json(['code'=>0,'msg'=>'查询成功','data' => $data]);
            }
            if(getcustom('restaurant_cashdesk_custom_pay')){
                $custom_paylist = Db::name('restaurant_cashdesk_custom_pay')->where('aid',aid)->where('bid',bid)->where('status',1)->order('sort desc,id desc')->select()->toArray();
                foreach($custom_paylist as $ck=>$cv){
                    $custom_paylist[$ck]['id'] = 10000+ $cv['id'];
                }
                View::assign('custom_paylist',$custom_paylist);
            }
            View::assign('bid',bid); 
            return View::fetch();
        }
    }

    public function getorderdetail(){
        if(getcustom('payorder_show_orderdetail')){
            $id = input('param.id');
            $payorder = Db::name('payorder')->where('aid',aid)->where('id',$id)->find();
            $orderid = $payorder['orderid'];
            $type = $payorder['type'];
            $detail = Db::name($type.'_order')->where ('aid',aid) ->where('id',$orderid)->find();
            $detail['type'] = $type;
            $comdata = array();
            $oglist = [];
            if(!in_array($type,['recharge','maidan','form','collage'])){
                $comdata['parent1'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
                $comdata['parent2'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
                $comdata['parent3'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
               
                $ogwhere = [];
                $ogwhere[] = ['aid','=',aid];
                $ogwhere[] = ['orderid','=',$orderid];
                $oglist = Db::name($type.'_order_goods')->where($ogwhere)->select()->toArray();
                $ogids = [];
                foreach($oglist as $gk=>$v){
                    $ogids[] = $v['id'];
                    if($v['parent1']){
                        $parent1 = Db::name('member')->where('id',$v['parent1'])->find();
                        $comdata['parent1']['mid'] = $v['parent1'];
                        $comdata['parent1']['nickname'] = $parent1['nickname'];
                        $comdata['parent1']['headimg'] = $parent1['headimg'];
                        $comdata['parent1']['money'] += $v['parent1commission'];
                        $comdata['parent1']['score'] += $v['parent1score'];
                    }
                    if($v['parent2']){
                        $parent2 = Db::name('member')->where('id',$v['parent2'])->find();
                        $comdata['parent2']['mid'] = $v['parent2'];
                        $comdata['parent2']['nickname'] = $parent2['nickname'];
                        $comdata['parent2']['headimg'] = $parent2['headimg'];
                        $comdata['parent2']['money'] += $v['parent2commission'];
                        $comdata['parent2']['score'] += $v['parent2score'];
                    }
                    if($v['parent3']){
                        $parent3 = Db::name('member')->where('id',$v['parent3'])->find();
                        $comdata['parent3']['mid'] = $v['parent3'];
                        $comdata['parent3']['nickname'] = $parent3['nickname'];
                        $comdata['parent3']['headimg'] = $parent3['headimg'];
                        $comdata['parent3']['money'] += $v['parent3commission'];
                        $comdata['parent3']['score'] += $v['parent3score'];
                    }
                }
                $comdata['parent1']['money'] = round($comdata['parent1']['money'],2);
                $comdata['parent2']['money'] = round($comdata['parent2']['money'],2);
                $comdata['parent3']['money'] = round($comdata['parent3']['money'],2);
            }
            if($type =='recharge'){
                $detail['totalprice'] = $detail['money'];
            }elseif($type =='maidan'){
                $detail['totalprice'] = $detail['paymoney'];
            }elseif($type =='form'){
                $detail['totalprice'] = $detail['money'];
            }elseif($type =='restaurant_shop'){
                $detail['tablename'] = Db::name('restaurant_table')->where('id',$detail['tableid'])->value('name');
                if(getcustom('restaurant_cashdesk_multi_pay')){
                    if($detail['paytypeid'] ==13481){
                        $multi_return = \app\custom\Restaurant::getMultiPayAndRefundDetail(aid,$detail);
                        $detail['paytype_list'] = $multi_return['paytype_list']??[];;
                        $detail['refund_money_list'] = $multi_return['refund_money_list']??[];
                    }
                }
            }
            $detail['realprice'] = dd_money_format( $detail['totalprice'] - $detail['refund_money']);
            $orderdetail = $detail??[];
            $member = Db::name('member')->field('id,nickname,headimg,realname,tel,wxopenid,unionid')->where('id',$detail['mid'])->find();
            if(!$member) $member = ['id'=>$detail['mid'],'nickname'=>'','headimg'=>''];
            return json(['order'=>$orderdetail,'member'=>$member,'oglist'=>$oglist,'comdata'=>$comdata]); 
        }
    }
    
    public function shoprestauranttongji(){
        if(getcustom('payorder_shop_restaurant_tongji')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'payorder.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'payorder.id desc';
                }
                $where = array();
                $where[] = ['payorder.aid','=',aid];
                $where[] = ['payorder.status','=',1];
                $where[] = ['payorder.money','>',0];
                $where[] = ['payorder.type','in',['restaurant_shop','shop','cashier','restaurant_takeaway']];
                if(input('param.bid')) $where[] = ['payorder.bid','=',input('param.bid')];
                if(input('param.mid')) $where[] = ['member.id','=',input('param.mid')];
                if(input('param.paytypeid') !='') $where[] = ['payorder.paytypeid','=',input('param.paytypeid')];
                if(input('param.ordernum')) $where[] = ['payorder.ordernum','like','%'.input('param.ordernum').'%'];
                //数据类型 restaurant  shop
                if(input('param.type')){
                    if(input('param.type') =='restaurant'){
                        $where[] = ['payorder.type','in',['restaurant_shop','restaurant_takeaway']];
                    }
                    if(input('param.type') =='shop'){
                        $where[] = ['payorder.type','in',['shop','cashier']];
                    }
                } 
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['payorder.paytime','>=',strtotime($ctime[0])];
                    $where[] = ['payorder.paytime','<',strtotime($ctime[1]) + 86400];
                }
                $count = 0 + Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member','member.id=payorder.mid','left')->where($where)->count();
                
                $data = Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member','member.id=payorder.mid','left')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                foreach($data as $key=>$val){
                    if($val['bid'] > 0){
                        $bname = Db::name('business')->where('id',$val['bid'])->value('name');
                    }else{
                        $bname ='平台订单';
                    }
                    //订单信息
                    $order = Db::name($val['type'].'_order')->where('aid',aid)->where('id',$val['orderid'])->find();
                    $data[$key]['ostatus'] = $order['status'];
                    $data[$key]['refund_status'] = $order['refund_status'];
                    $data[$key]['bname'] = $bname;
                    $leixing ='商城';
                    if(in_array($val['type'],['restaurant_shop','restaurant_takeaway'])){
                        $oglist = Db::name('restaurant_shop_order_goods')->where('aid',aid)->where('orderid',$val['orderid'])->select()->toArray();
                        $goodsdata=array();
                        foreach($oglist as $og){
                            $sell_price = $og['sell_price'];
                            $ggname =  $og['ggname'];
                            $goodsdata[] = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                                '<img src="'.$og['pic'].'" style="max-width:60px;float:left">'.
                                '<div style="float: left;width:250px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                                '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$og['name'].'</div>'.
                                '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$ggname.'</span></div>'.
                                '<div style="padding-top:0px;color:#f60;">￥'.$sell_price.' × '.$og['num'].'</div>'.
                                '</div>'.
                                '</div>';
                        }
                        $data[$key]['product_price'] =  $order['product_price'];
                        $data[$key]['totalprice'] =  $order['totalprice'];
                        $leixing ='餐饮';
                    }
                    if(in_array($val['type'],['shop','cashier'])){
                        $oglist = Db::name($val['type'].'_order_goods')->where('aid',aid)->where('orderid',$val['orderid'])->select()->toArray();
                        $goodsdata=array();
                        foreach($oglist as $og){
                            $pic = $og['pic'];
                            if($val['type'] =='cashier')$pic = $og['propic'];
                            $goodshtml = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                                '<img src="'.$pic.'" style="max-width:60px;float:left">'.
                                '<div style="float: left;width:250px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                                '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$og['name'].'</div>'.
                                '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$og['ggname'].'</span></div>';
                            
                                $goodshtml.='<div style="padding-top:0px;color:#f60;">￥'.$og['sell_price'].' × '.$og['num'].'</div>';
                           
                            $goodshtml.='</div>';
                            $goodshtml.='</div>';

                            $goodsdata[] = $goodshtml;
                        }
                        $data[$key]['product_price'] =  $order['product_price'];
                        $data[$key]['totalprice'] =  $order['totalprice'];
                    }
                    if(in_array($val['type'],['cashier'])){
                        $data[$key]['product_price'] =  $order['pre_totalprice'];
                        $data[$key]['totalprice'] =  $order['totalprice'];
                    }
                    $data[$key]['goodsdata'] = implode('',$goodsdata);
                    $data[$key]['leixing'] = $leixing;
                    
                    $data[$key]['typename'] = \app\common\Order::getOrderTypeName($val['type']);
                    $member = Db::name('member')->field('nickname,headimg')->where('id',$val['mid'])->find();
                    $data[$key]['nickname'] = $member['nickname'];
                    $data[$key]['headimg'] = $member['headimg'];
                    $data[$key]['createtime']  = $order['createtime'];
                }
               
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }
    }
    public function shoprestauranttongjiexcel(){
        if(getcustom('payorder_shop_restaurant_tongji')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'payorder.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'payorder.id desc';
                }
                $where = array();
                $where[] = ['payorder.aid','=',aid];
                $where[] = ['payorder.status','=',1];
                $where[] = ['payorder.money','>',0];
                $where[] = ['payorder.type','in',['restaurant_shop','shop','cashier','restaurant_takeaway']];
                if(input('param.bid')) $where[] = ['payorder.bid','=',input('param.bid')];
                if(input('param.mid')) $where[] = ['member.id','=',input('param.mid')];
                if(input('param.paytypeid') !='') $where[] = ['payorder.paytypeid','=',input('param.paytypeid')];
                if(input('param.ordernum')) $where[] = ['payorder.ordernum','like','%'.input('param.ordernum').'%'];
                //数据类型 restaurant  shop
                if(input('param.type')){
                    if(input('param.type') =='restaurant'){
                        $where[] = ['payorder.type','in',['restaurant_shop','restaurant_takeaway']];
                    }
                    if(input('param.type') =='shop'){
                        $where[] = ['payorder.type','in',['shop','cashier']];
                    }
                }
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['payorder.paytime','>=',strtotime($ctime[0])];
                    $where[] = ['payorder.paytime','<',strtotime($ctime[1]) + 86400];
                }

                $ostatusarr = ['0' => '未支付','1' => '已支付','3' =>'已完成','4' => '已关闭'];
                $refundstatusarr = ['1' => '退款待审核','2' => '已退款','3' => '退款驳回'];
                $data = Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member','member.id=payorder.mid','left')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                $title = ['订单号','所属商户','会员信息','商品名称','规格','数量','单价','总价','实付金额','支付方式','下单时间','付款时间','数据类型','数据来源','状态'];
                $exceldata = [];
                foreach($data as $key=>$val){
                   
                    if($val['bid'] > 0){
                        $bname = Db::name('business')->where('id',$val['bid'])->value('name');
                    }else{
                        $bname ='平台订单';
                    }
                    //订单信息
                    $order = Db::name($val['type'].'_order')->where('aid',aid)->where('id',$val['orderid'])->find();
                    $totalprice =  $order['totalprice'];
                    $product_price = $order['product_price'];
                    if($val['type'] == 'cashier'){
                        $product_price =  $order['pre_totalprice'];
                    }
                    $leixing ='商城';
                    $typename = \app\common\Order::getOrderTypeName($val['type']);
                    $member = Db::name('member')->field('nickname,headimg')->where('id',$val['mid'])->find();
                    $nickname = $member['nickname'];
                    $ostatus  = $ostatusarr[$order['status']];
                    $refundstatus =  $refundstatusarr[$order['refund_status']];
                    if(in_array($val['type'],['restaurant_shop','restaurant_takeaway'])){
                        $oglist = Db::name('restaurant_shop_order_goods')->where('aid',aid)->where('orderid',$val['orderid'])->select()->toArray();
                        foreach($oglist as $og){
                            $sell_price = $og['sell_price'];
                            $ggname =  $og['ggname'];
                            $leixing ='餐饮';
                            $exceldata[] =[
                                $order['ordernum'],
                                $bname,
                                $nickname.'(ID:'.$val['mid'].')',
                                $og['name'],
                                $ggname,
                                $og['num'],
                                $sell_price,
                                $og['totalprice'],
                                $og['real_totalprice'],
                                $val['paytype'],
                                date('Y-m-d H:i',$order['createtime']),
                                date('Y-m-d H:i',$order['paytime']),
                                $leixing,
                                $typename,
                                $ostatus?$ostatus:$refundstatus
                            ]; 
                        }
                     
                    }
                    if(in_array($val['type'],['shop','cashier'])){
                        $oglist = Db::name($val['type'].'_order_goods')->where('aid',aid)->where('orderid',$val['orderid'])->select()->toArray();
                        foreach($oglist as $og){
                            $sell_price = $og['sell_price'];
                            $ggname = $og['ggname'];
                            $exceldata[] =[
                                $order['ordernum'],
                                $bname,
                                $nickname.'(ID:'.$val['mid'].')',
                                $og['name'],
                                $ggname,
                                $og['num'],
                                $sell_price,
                                $og['totalprice'],
                                $og['real_totalprice']?$og['real_totalprice']:$og['totalprice'],
                                $val['paytype'],
                                date('Y-m-d H:i',$order['createtime']),
                                date('Y-m-d H:i',$order['paytime']),
                                $leixing,
                                $typename ,
                                $ostatus?$ostatus:$refundstatus
                            ];
                        }
                    }
               
                }
                return json(['code'=>0,'msg'=>'查询成功','count'=>count($exceldata),'data'=>$exceldata,'title'=>$title]);
                $this->export_excel($title,$data);
            }
            return View::fetch();
        }
    }
    public function repairFenzhang(){
        $tablename = input('param.tablename');
        $ordernum = input('param.ordernum');
        $platform = input('param.platform','wx');
        $update = input('param.update');//先看数据，确认更新再传1
        $order =  Db::name($tablename.'_order')->where('aid',aid)->where('ordernum',$ordernum)->find();
        if(empty($order)){
            return json(['status'=>0,'msg'=>'订单不存在']);
        }
        dump('order',$order);
        $aid = $order['aid'];
        $bid = $order['bid'];
        $wxpay_log = Db::name('wxpay_log')->where('aid',aid)->where('tablename',$tablename)->where('ordernum',$ordernum)->find();
        if(empty($wxpay_log)){
            return json(['status'=>0,'msg'=>'wxpay_log不存在']);
        }
        dump('$wxpay_log',$wxpay_log);

        $msg['total_fee'] = $wxpay_log['total_fee']*100;
        $msg['out_trade_no'] = $wxpay_log['transaction_id'];
        $payorder = Db::name('payorder')->where(['aid'=>$aid,'type'=>$tablename,'ordernum'=>$ordernum])->find();
        if(empty($payorder)){
            return json(['status'=>0,'msg'=>'payorder不存在']);
        }
        dump('$payorder',$payorder);

        $dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
        dump('$dbwxpayset',$dbwxpayset);
        $chouchengmoney = 0;
        $chouchengmoney2 = 0;
        //多商户订单
        if($bid){
            $business = Db::name('business')->where('id',$bid)->find();
            dump('$business',$business);
            $bset = Db::name('business_sysset')->where('aid',$aid)->find();
            dump('wxfw_status:'.$bset['wxfw_status']);
            //使用平台服务商
            if($bset['wxfw_status'] == 2){
                $paymoney = $msg['total_fee']*0.01;
                $feemoney = 0;

                $countpaymoney = $paymoney;//重新赋值，用于计算使用
                if(getcustom('business_toaccount_type')){
                    //商城商品实际到账方式，差额
                    $toaccountcha = \app\custom\NotifyCustom::businessToaccountType($aid,$bid,$tablename,$msg['out_trade_no'],$countpaymoney);
                    if($toaccountcha>0){
                        $countpaymoney -= $toaccountcha;
                        $countpaymoney = $countpaymoney>=0?$countpaymoney:0;
                    }
                }

                if($business['feepercent'] > 0){
                    if(getcustom('business_deduct_cost')){
                        $paymoney2 = \app\custom\NotifyCustom::deduct_cost($aid,$bid,$tablename,$msg,$countpaymoney);
                        $feemoney = floatval($business['feepercent']) * 0.01 * $paymoney2;
                    }else{
                        $feemoney = floatval($business['feepercent']) * 0.01 * $countpaymoney;
                    }
                    if(getcustom('business_fee_type')){
                        $paymoney3 = \app\custom\NotifyCustom::business_fee_type_money($aid,$bid,$tablename,$msg,$countpaymoney);
                        $feemoney = floatval($business['feepercent']) * 0.01 * $paymoney3;
                    }
                }

                $admindata = Db::name('admin')->where('id',aid)->find();

                if($admindata['chouchengset']==0){ //默认抽成
                    if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
                        if($dbwxpayset['chouchengset'] == 1){
                            //$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $paymoney;
                            $chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $feemoney;
                            if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
                                $chouchengmoney = floatval($dbwxpayset['chouchengmin']);
                            }
                        }else{
                            $chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
                        }
                    }
                }elseif($admindata['chouchengset']==1){ //按比例抽成
                    $chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $feemoney;
                    if($chouchengmoney < floatval($admindata['chouchengmin'])){
                        $chouchengmoney = floatval($admindata['chouchengmin']);
                    }
                }elseif($admindata['chouchengset']==2){ //按固定金额抽成
                    $chouchengmoney = floatval($admindata['chouchengmoney']);
                }

                if(getcustom('business_toaccount_type')){
                    //商城商品实际到账方式，差额
                    if($toaccountcha>0){
                        $chouchengmoney += $toaccountcha;
                    }
                }

                if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
                    $chouchengmoney = intval(bcmul($chouchengmoney,100))/100;
                }else{
                    $chouchengmoney = 0;
                }
                if($business['feepercent'] > 0){
                    $chouchengmoney2 = $feemoney;
                    if($bset['commission_kouchu'] == 1){
                        $commission = \app\common\Wxpay::getcommission($tablename,$msg['out_trade_no']);
                    }else{
                        $commission = 0;
                        if(getcustom('yx_collage_team_in_team')){
                            //单独获取拼团团中团订单的佣金
                            $commission = \app\common\Wxpay::getcommission_teaminteam($tablename,$msg['out_trade_no']);
                        }
                    }

                    $chouchengmoney2 = $chouchengmoney2 + $commission;

                    if($chouchengmoney2 >= 0.01 && $paymoney*0.3 >= $chouchengmoney2){
                        $chouchengmoney2 = intval(bcmul($chouchengmoney2,100))/100;
                    }else{
                        $chouchengmoney2 = 0;
                    }
                }
            }else{
                $paymoney = $msg['total_fee']*0.01;
                $countpaymoney = $paymoney;//重新赋值，用于计算使用
                if(getcustom('business_toaccount_type')){
                    //商城商品实际到账方式，差额
                    $toaccountcha = \app\custom\NotifyCustom::businessToaccountType($aid,$bid,$tablename,$msg['out_trade_no'],$countpaymoney);
                    if($toaccountcha>0){
                        $countpaymoney -= $toaccountcha;
                        $countpaymoney = $countpaymoney>=0?$countpaymoney:0;
                    }
                }

                dump('$business feepercent:'.$business['feepercent']);
                //使用多商户配置的服务商或者关闭
                if($business['feepercent'] > 0){
                    if(getcustom('business_deduct_cost')){
                        $paymoney2 = \app\custom\NotifyCustom::deduct_cost($aid,$bid,$tablename,$msg,$countpaymoney);
                        $chouchengmoney = floatval($business['feepercent']) * 0.01 * $paymoney2;
                    }else{
                        $chouchengmoney = floatval($business['feepercent']) * 0.01 * $countpaymoney;
                    }
                    if(getcustom('business_fee_type')){
                        $paymoney3 = \app\custom\NotifyCustom::business_fee_type_money($aid,$bid,$tablename,$msg,$countpaymoney);
                        $chouchengmoney = floatval($business['feepercent']) * 0.01 * $paymoney3;
                    }

                    if($bset['commission_kouchu'] == 1){
                        $commission = \app\common\Wxpay::getcommission($tablename,$msg['out_trade_no']);
                    }else{
                        $commission = 0;
                        if(getcustom('yx_collage_team_in_team')){
                            //单独获取拼团团中团订单的佣金
                            $commission = \app\common\Wxpay::getcommission_teaminteam($tablename,$msg['out_trade_no']);
                        }
                    }

                    $chouchengmoney = $chouchengmoney + $commission;
                }

                if(getcustom('business_toaccount_type')){
                    //商城商品实际到账方式，差额
                    if($toaccountcha>0){
                        $chouchengmoney += $toaccountcha;
                    }
                }

                if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
                    $chouchengmoney = intval(bcmul($chouchengmoney,100))/100;
                }else{
                    $chouchengmoney = 0;
                }

                //商户编辑多个分账接收方
                if(getcustom('business_more_account')){
                    $wxpays =  json_decode($business['wxpay_submchid_text'],true);
                    $subpays = [];
                    foreach($wxpays as $sub){
                        $subamount = $sub['feepercent']*$msg['total_fee']*0.01;
                        if($subamount >= 0.01 && $msg['total_fee']*0.3 >= $subamount){
                            $subamount = intval($subamount);
                        }else{
                            $subamount = 0;
                        }
                        $subpays[] = ['submchid'=>$sub['submchid'],'amount'=>$subamount,'subname'=>$sub['subname']];
                    }
                    $wxpay_submchid_text = jsonEncode($subpays);
                }

            }
            //扣除返现比例
            $queue_feepercent_type = 0;
            $queue_feepercent_allmoney = 0;
            $has_yx_queue_free_collage = 0;
            if(getcustom('yx_queue_free_collage')){
                $has_yx_queue_free_collage = 1;
            }

            if(getcustom('yx_queue_free')){
                $queue_free_set = Db::name('queue_free_set')->where('aid',$aid)->where('bid',0)->find();
                $b_queue_free_set = Db::name('queue_free_set')->where('aid',$aid)->where('bid',$bid)->find();
                $queue_free_set['order_types'] = explode(',',$queue_free_set['order_types']);

                if($tablename == 'maidan'){
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('maidan',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                        }
                    }
                    $order = Db::name('maidan_order')->where('aid', $aid)->where('bid', $bid)->where('id', $payorder['orderid'])->find();
                    if($queue_feepercent_type == 1 && $paymoney >0  && $b_queue_free_set['rate_back'] > 0){
                        $chouchengmoney = $chouchengmoney + $paymoney * $b_queue_free_set['rate_back'] * 0.01;

                    }
                }elseif($tablename == 'shop'){
                    $oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$payorder['orderid'])->select()->toArray();
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('shop',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                        }
                    }
                    foreach($oglist as $og){
                        $product = Db::name('shop_product')->where('id',$og['proid'])->where('aid',$aid)->where('bid',$bid)->find();
                        if($product['queue_free_status'] == 1){
                            $queue_feepercent_allmoney += $og['real_totalprice'];
                        }
                    }
                    if($queue_feepercent_type == 1 && $paymoney >0 && $queue_feepercent_allmoney > 0 && $b_queue_free_set['rate_back'] > 0){
                        $chouchengmoney = $chouchengmoney + $queue_feepercent_allmoney * $b_queue_free_set['rate_back'] * 0.01;
                    }
                }elseif($tablename == 'collage' && $has_yx_queue_free_collage){
                    $order = Db::name('collage_order')->where('aid', $aid)->where('bid', $bid)->where('id', $payorder['orderid'])->find();
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('collage',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                            $product = Db::name('collage_product')->where('id',$order['proid'])->field('queue_free_status,queue_free_rate_back')->find();
                            if($product['queue_free_status'] == 1 && $product['queue_free_rate_back']>=0){
                                $b_queue_free_set['rate_back'] =  $product['queue_free_rate_back'];
                            }
                        }
                    }
                    if($queue_feepercent_type == 1 && $paymoney > 0 && $b_queue_free_set['rate_back'] > 0){
                        $chouchengmoney = $chouchengmoney + $paymoney * $b_queue_free_set['rate_back'] * 0.01;
                    }
                }

            }
            //yx_queue_free end
            $sub_mchid = $business['wxpay_submchid'];
            //多商户订单 end
        }else{
            //平台订单 服务商分账
            $chouchengmoney = 0;

            $appinfo = \app\common\System::appinfo($aid,$platform);
            if($appinfo['wxpay_type'] == 1){
                $paymoney = $msg['total_fee']*0.01;
                $admindata = Db::name('admin')->where('id',aid)->find();
                if($admindata['chouchengset']==0){ //默认抽成
                    if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
                        if($dbwxpayset['chouchengset'] == 1){
                            $chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $paymoney;
                            if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
                                $chouchengmoney = floatval($dbwxpayset['chouchengmin']);
                            }
                        }else{
                            $chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
                        }
                    }
                }elseif($admindata['chouchengset']==1){ //按比例抽成
                    $chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $paymoney;
                    if($chouchengmoney < floatval($admindata['chouchengmin'])){
                        $chouchengmoney = floatval($admindata['chouchengmin']);
                    }
                }elseif($admindata['chouchengset']==2){ //按固定金额抽成
                    $chouchengmoney = floatval($admindata['chouchengmoney']);
                }

                if(getcustom('product_fenzhangmoney') && $payorder['type'] == 'shop'){
                    $oglist = Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$payorder['orderid'])->select()->toArray();
                    $issetfzmoney = false;
                    $fzmoney = 0;
                    foreach($oglist as $og){
                        $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                        if($product && $product['product_fenzhangmoney']!=-1){
                            $issetfzmoney = true;
                            $fzmoney += $product['product_fenzhangmoney'] * $og['num'];
                        }
                    }
                    if($issetfzmoney){
                        $chouchengmoney = $fzmoney;
                    }
                }

                if($chouchengmoney >= 0.01 && $paymoney*0.3 >= $chouchengmoney){
                    $chouchengmoney = intval(bcmul($chouchengmoney,100))/100;
                }else{
                    $chouchengmoney = 0;
                }
            }
            $sub_mchid = ($appinfo['wxpay_type'] == 1 ? $appinfo['wxpay_sub_mchid'] : '');
        }

        //记录
        $data = array();
        $data['fenzhangmoney'] = $chouchengmoney;
        $data['fenzhangmoney2'] = $chouchengmoney2;
        $data['sub_mchid'] = $sub_mchid;
        if(getcustom('business_more_account')){
            $data['wxpay_submchid_text'] = $wxpay_submchid_text;
        }
        dump('wxpay_log-data',$data);
        if($update == 1){
            $rs = Db::name('wxpay_log')->where('id',$wxpay_log['id'])->update($data);
            dd($rs);
        }
        dd('end');
    }

    public function cacelFenzhang(){

        $platform = input('param.platform','wx');
        $where = [];
        $where[] = ['aid','=', aid];
//        $where[] = ['platform','=', $platform];
        $where[] = ['isfenzhang','=', 0];
        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['createtime','>=',strtotime($ctime[0])];
            $where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
        }
        if(input('param.ordernum')) $where[] = ['ordernum','=',input('ordernum')];
        if(input('param.transaction_id')) $where[] = ['transaction_id','=',input('transaction_id')];
        $tasnstr = '4200002735202508112186913909,4200002737202508114074830658,4200002764202508111187387767,4200002790202508113917414937,4200002734202508125685826901,4200002761202508125026701059,4200002766202508121818059007,4200002771202508120262700264,4200002777202508121052634066,4200002779202508121513386409,4200002780202508128927017082,4200002789202508126331029786,4200002791202508122400225750,4200002726202508135677079449,4200002738202508138097414511,4200002760202508130776327374,4200002788202508130379833154,4200002792202508132485083137,4200002725202508155561409624,4200002740202508157829259623,4200002759202508151798142077,4200002759202508153446439449,4200002792202508154267991627,4200002777202508118423321829,4200002737202508120954414710,4200002744202508126457608164,4200002765202508121287834065,4200002766202508126780561341,4200002768202508128430675729,4200002721202508139749894953,4200002733202508133412574160,4200002735202508134823612130,4200002781202508139906888909,4200002794202508130487418445,4200002736202508140800348416,4200002776202508148997930101,4200002722202508157477142909,4200002734202508152987046555,4200002743202508155806723405,4200002763202508153545904060,4200002772202508158325139802,4200002774202508153702296190,4200002774202508154421830000,4200002778202508159359500820,4200002773202508105108916805,4200002780202508113803935172,4200002738202508139119514624,4200002723202508149172246560';
        $where[] = ['transaction_id','in',explode(',',$tasnstr)];

        $loglist = Db::name('wxpay_log')->where($where)
//            ->limit(99)
            ->select();
        // dd($loglist);
        $dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
        $dbwxpayset = json_decode($dbwxpayset,true);

        foreach($loglist as $log){
            dump($log);
            $sub_mchid = $log['sub_mchid'];
            if($log['bid'] > 0){
                //多商户独立配置服务商
                $bset = Db::name('business_sysset')->where('aid',$log['aid'])->find();
                $wxpaybset = [
                    'mchname'=>$bset['wxfw_mchname'],
                    'appid'=>$bset['wxfw_appid'],
                    'mchid'=>$bset['wxfw_mchid'],
                    'mchkey'=>$bset['wxfw_mchkey'],
                    'apiclient_cert'=>$bset['wxfw_apiclient_cert'],
                    'apiclient_key'=>$bset['wxfw_apiclient_key'],
                ];
                $sslcert = ROOT_PATH.$wxpaybset['apiclient_cert'];
                $sslkey = ROOT_PATH.$wxpaybset['apiclient_key'];
                $mchkey = $wxpaybset['mchkey'];
            }else{
                //$appinfo = \app\common\System::appinfo(aid,$platform);
                //$sub_mchid = ($appinfo['wxpay_type'] == 1 ? $appinfo['wxpay_sub_mchid'] : '');
                $wxpaybset = [];
                $sslcert = ROOT_PATH.$dbwxpayset['apiclient_cert'];
                $sslkey = ROOT_PATH.$dbwxpayset['apiclient_key'];
                $mchkey = $dbwxpayset['mchkey'];
            }

            $pars = [];
            $pars['mch_id'] = $log['mch_id'];
            $pars['sub_mch_id'] = $sub_mchid;
            $pars['appid'] = $wxpaybset['appid'] ? $wxpaybset['appid'] : $dbwxpayset['appid'];
            $pars['nonce_str'] = random(32);
            $pars['transaction_id'] = $log['transaction_id'];
            $pars['out_order_no'] = 'P'.date('YmdHis').rand(1000,9999);
            $pars['description'] = '分账已完成';
            //$pars['sign_type'] = 'MD5';
            ksort($pars, SORT_STRING);
            $string1 = '';
            foreach ($pars as $k => $v) {
                $string1 .= "{$k}={$v}&";
            }
            $string1 .= "key=" . $mchkey;
            //$pars['sign'] = strtoupper(md5($string1));
            dump($pars);
            $pars['sign'] = strtoupper(hash_hmac("sha256",$string1 ,$mchkey));
            $dat = array2xml($pars);
            $client = new \GuzzleHttp\Client(['timeout'=>30,'verify'=>false]);
            $response = $client->request('POST',"https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish",['body'=>$dat,'cert'=>$sslcert,'ssl_key'=>$sslkey]);
            $info = $response->getBody()->getContents();

            $resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
            dump($resp);
            //Log::write($resp);
            if($resp['return_code'] == 'SUCCESS' && $resp['result_code']=='SUCCESS'){
                $msg = '取消成功';
            }else{
                $msg = '未知错误';
                if ($resp['return_code'] == 'FAIL') {
                    $msg = $resp['return_msg'];
                }
                if ($resp['result_code'] == 'FAIL') {
                    $msg = $resp['err_code_des'];
                }
            }
        }
        dd('end');
        return json(['status'=>1,'msg'=>$msg,'resp'=>$resp,'ordernum'=>$pars['out_order_no']]);
    }
}

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
class ApiHongbaoEveryday extends ApiCommon
{
	public function initialize(){
		parent::initialize();
		$this->checklogin();
	}
	public function index(){
		$hid = input('param.id/d');
		Db::startTrans();
		$hd = Db::name('hongbao_everyday')->where('aid',aid)->find();
		if(!$hd) return $this->json(['status'=>0,'msg'=>'活动不存在']);
		if($hd['status']==0) return $this->json(['status'=>0,'msg'=>'活动未开启']);
		$member = Db::name('member')->where('aid',aid)->where('id',mid)->find();
		$date = date('Y-m-d');
		$todayStart = strtotime($date);
		$yestdayStart = $todayStart - 86400;
        $yestdayEnd = $yestdayStart + 86399;
        $yestdayDate = date('Y-m-d',$yestdayStart);

		//前一天业绩
        if($hd['shop_order_money_type'] == 'pay') {
            $where[] = ['status', 'in', [1,2,3]];
            $where[] = ['paytime', 'between', [$yestdayStart,$yestdayEnd]];
        }else if($hd['shop_order_money_type'] == 'receive') {
            $where[] = ['status', '=', 3];
            $where[] = ['collect_time', 'between', [$yestdayStart,$yestdayEnd]];
        } else {
            $where[] = ['status', 'in', [1,2,3]];
            $where[] = ['paytime', 'between', [$yestdayStart,$yestdayEnd]];
        }
        $totalOrder = Db::name('shop_order')->where('aid',aid)->where('bid', 0)->where($where)->sum('totalprice');
        $totalOrder = round($totalOrder * $hd['hongbao_bl'] / 100,2);
        $orderBusiness = Db::name('shop_order')->where('aid',aid)->where('bid', '>',0)->where($where)->field('id,aid,bid,totalprice')->select()->toArray();
        $business = Db::name('business')->where('aid',aid)->column('feepercent','id');
        $totalOrderBusiness = 0;
        foreach ($orderBusiness as $item) {
            $totalOrderBusiness += $item['totalprice'] * $business[$item['bid']] / 100;
        }
        $totalOrderBusiness = round($totalOrderBusiness * $hd['hongbao_bl_business'] / 100,2);
        //买单业绩
        $maidanOrder = Db::name('maidan_order')->where('aid',aid)->where('createtime','between',[$yestdayStart,$yestdayEnd])->where('status',1)->select()->toArray();
        $totalMaidan = 0;
        foreach ($maidanOrder as $item) {
            $totalMaidan += $item['paymoney'] * $business[$item['bid']] / 100;
        }
        $totalMaidan = round($totalMaidan * $hd['hongbao_bl_maidan'] / 100,2);
        $yestdayLeft = Db::name('hongbao_everyday_list')->where('aid',aid)->where('createdate','=',$yestdayDate)->sum('left');
        //红包数量
        $total = $totalOrder + $totalOrderBusiness + $totalMaidan + $yestdayLeft;
        $total = $total > 0 ? $total : 0;
        $total = round($total,2);
        $todayNum = Db::name('member_hbe_record')->where('aid',aid)->where('status',1)->where('createdate',$date)->count();
        $todayNum = $todayNum > 0 ?$todayNum : 0;

        $hongbao_count = Db::name('member_hbe_record')->where('aid',aid)->where('status',1)->where('mid',mid)->count();
        $hongbao_count = $hongbao_count > 0 ?$hongbao_count : 0;

        $todayNum = Db::name('hongbao_everyday_list')->where('aid',aid)->where('createdate','=',$date)->count();
        $todayLeftNum = Db::name('hongbao_everyday_list')->where('aid',aid)->where('createdate','=',$date)->where('status',0)->count();

        $todayRecord = Db::name('member_hbe_record')->where('aid',aid)->where('status',1)->where('mid',mid)->where('money','>',0)->where('createtime','>=',$todayStart)->order('id','desc')->find();

		$rdata = [];
		$rdata['info'] = $hd;
        $rdata['data']['total'] = $total;
        $rdata['data']['todayNum'] = $todayNum;
        $rdata['data']['todayLeftNum'] = $todayLeftNum;
		$rdata['zjlist'] = [];
		$rdata['todayRecord'] = $todayRecord;
		$rdata['member'] = ['realname'=>$member['realname'],'tel'=>$member['tel'],'score'=>$member['score'],'hongbao_everyday_edu' => $member['hongbao_everyday_edu'],'hongbao_count' => $hongbao_count];
		return $this->json($rdata);
	}

	public function getHongbao()
    {
        $date = date('Y-m-d');
        $todayStart = strtotime($date);

        Db::startTrans();
        $hd = Db::name('hongbao_everyday')->where('aid',aid)->find();
        if(!$hd) return $this->json(['status'=>0,'msg'=>'活动不存在']);
        if($hd['status']==0) return $this->json(['status'=>0,'msg'=>'活动未开启']);

        if($hd['starttime'] > time() || $hd['endtime'] < time()) return $this->json(['status'=>0,'msg'=>'不在活动时间']);

        $member = Db::name('member')->where('aid',aid)->where('id',mid)->find();
        //判断有无机会
        if(!$member){
            return $this->json(['status'=>0,'msg'=>'请先登录']);
        }
        $todayRecord = Db::name('member_hbe_record')->where('aid',aid)->where('status',1)->where('mid',mid)->where('createtime','>=',$todayStart)->find();
        if($todayRecord) {
            return $this->json(['status'=>0,'msg'=>'今日已领过']);
        }
        if($member['hongbao_everyday_edu'] <= 0) {
            return $this->json(['status'=>0,'msg'=>'额度不足，请先参与活动增加额度']);
        }

        $hongbaoCount = Db::name('hongbao_everyday_list')->where('aid',aid)->where('createdate','=',$date)->where('status',0)->count();
        if($hongbaoCount == 0) {
            return $this->json(['status'=>0,'msg'=>'已领完']);
        }

        //分配红包（不能超出额度）
        $hongbaoReal = 0;
        $left = 0;
        $hongbaoInfo = Db::name('hongbao_everyday_list')->where('aid',aid)->where('createdate','=',$date)->where('status',0)->orderRand()->limit(1)->find();
        if(empty($hongbaoInfo)) {
            return $this->json(['status'=>0,'msg'=>'已领完']);
        }
        $hongbaoReal = $hongbaoInfo['money'];
        if($hongbaoInfo['money'] > $member['hongbao_everyday_edu']) {
            $hongbaoReal = $member['hongbao_everyday_edu'];
            $left = $hongbaoInfo['money'] - $member['hongbao_everyday_edu'];
        }
        if($hongbaoReal < 0.01 || empty($hongbaoReal)) {
            return $this->json(['status'=>0,'msg'=>'已领完']);
        }
        Db::name('hongbao_everyday_list')->where('id',$hongbaoInfo['id'])->update(['status' => 1, 'left' => $left, 'mid' => mid,'updatetime' => time()]);
//        Db::name('member')->where('id',mid)->inc('hongbao_ereryday_total', $hongbao)->update();
        Db::commit(); //解锁

        //记录
        $data = [];
        $data['aid'] = aid;
        $data['mid'] = mid;
        $data['money'] = $hongbaoReal;
        $data['createdate'] = date('Y-m-d');
        $data['createtime'] = time();
        $data['remark'] = '每日补贴';
        $data['status'] = 1;
        Db::name('member_hbe_record')->insert($data);
        \app\common\Member::addHongbaoLog(aid,mid,$hongbaoReal,'每日补贴');

        $afterTotal = $member['hongbao_ereryday_total'] + $hongbaoReal;
//        $afterEdu = $member['hongbao_everyday_edu'] - $hongbaoReal;
        Db::name('member')->where('aid',aid)->where('id',mid)->update(['hongbao_ereryday_total' => $afterTotal]);

        \app\common\Member::addHongbaoEverydayEdu(aid,mid,-1*$hongbaoReal,'每日补贴');


        return $this->json(['status'=>1,'msg'=>'', 'money' => $hongbaoReal]);

    }
	//领取记录
    public function log(){
        $pagenum = input('param.pagenum');
        $st = input('post.st');
        if(!$pagenum) $pagenum = 1;
        $pernum = 20;
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['mid','=',mid];
        if($st == 0) {
            $datalist = Db::name('member_hbe_record')->field('id,money,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
        }elseif($st ==2){//提现记录
            $datalist = Db::name('member_hbe_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime) createtime,paytype,score")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
        }

        if($pagenum == 1)
            $hd = Db::name('hongbao_everyday')->where('aid',aid)->find();

        $rdata = [];
        $rdata['datalist'] = $datalist;
        $rdata['pernum'] = $pernum;
        $rdata['st'] = $st;
        $rdata['money'] = $this->member['hongbao_ereryday_total'];
        $rdata['withdraw'] = $hd['withdraw'] == 1 || $hd['withdraw_weixin'] == 1 || $hd['withdraw_score'] == 1  ? true : false;
        return $this->json($rdata);
    }
    //额度记录
    public function eduLog(){
        $pagenum = input('param.pagenum');
        $st = input('post.st');
        if(!$pagenum) $pagenum = 1;
        $pernum = 20;
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['mid','=',mid];
        if($st == 0) {
            $datalist = Db::name('member_hbe_edu_record')->field('id,money,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];

            $count = Db::name('member_hbe_edu_record')->where($where)->count();
        } elseif($st == 1) {
            $datalist = Db::name('member_hbe_withdrawlog')->field('id,money,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];

            $count = Db::name('member_hbe_withdrawlog')->where($where)->count();
        }

        $rdata = [];
        $rdata['count'] = $count;
        $rdata['datalist'] = $datalist;
        $rdata['pernum'] = $pernum;
        $rdata['st'] = $st;
        $member = $this->member;
        $rdata['member'] = ['realname'=>$member['realname'],'tel'=>$member['tel'],'score'=>$member['score'],'hongbao_everyday_edu' => $member['hongbao_everyday_edu']];
        return $this->json($rdata);
    }

    public function prolist()
    {
        $pagenum = input('param.pagenum');
        if(!$pagenum) $pagenum = 1;
        $pernum = 10;
        $tjwhere = [];
        $tjwhere[] = ['aid','=',aid];
        $tjwhere[] = ['status','=',1];
        $tjwhere[] = ['ischecked','=',1];
        $tjwhere[] = ['sell_price','>',0];
//        $tjwhere[] = ['bid','=',0];
        if(isdouyin == 1){
            $tjwhere[] = ['douyin_product_id','<>',''];
        }else{
            $tjwhere[] = ['douyin_product_id','=',''];
        }
        $where2 = "find_in_set('-1',showtj)";
        if($this->member){
            $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
            if($this->member['subscribe']==1){
                $where2 .= " or find_in_set('0',showtj)";
            }
        }
        $order = 'sort desc,id desc';
        $tjwhere[] = Db::raw($where2);
        $tjwhere[] = Db::raw('(everyday_hongbao_bl > 0 or everyday_hongbao_bl is null)');
        $tjdatalist = Db::name('shop_product')->field("id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,everyday_hongbao_bl,sort")->where($tjwhere)->page($pagenum,$pernum)->order($order)->select()->toArray();
        if(!$tjdatalist) $tjdatalist = array();
        $tjdatalist = $this->formatprolist($tjdatalist);


        $hd = Db::name('hongbao_everyday')->where('aid',aid)->find();
        foreach($tjdatalist as $k => $v) {
            if($v['everyday_hongbao_bl'] === null) {
                $tjdatalist[$k]['hongbaoEdu'] = $v['sell_price'] * $hd['shop_product_hongbao_bl'] / 100;
            } elseif($v['everyday_hongbao_bl'] > 0 ) {
                $tjdatalist[$k]['hongbaoEdu'] = $v['sell_price'] * $v['everyday_hongbao_bl'] / 100;
            } else {
                $tjdatalist[$k]['hongbaoEdu'] = 0;
            }
            $tjdatalist[$k]['hongbaoEdu'] = round($tjdatalist[$k]['hongbaoEdu'],2);
        }

        $rdata = [];
        $rdata['tjdatalist'] = $tjdatalist;
        return $this->json($rdata);
    }

    public function withdraw(){
        $set = Db::name('hongbao_everyday')->where('aid',aid)->find();

        if(request()->isPost()){
            $post = input('post.');
            if($set['withdraw'] == 0 && $set['withdraw_weixin'] == 0 && $set['withdraw_score'] == 0){
                return $this->json(['status'=>0,'msg'=>'提现功能未开启']);
            }

            $money = $post['money'];
            if($money<=0 || $money < $set['withdrawmin']){
                return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['withdrawmin']?$set['withdrawmin']:0)]);
            }
            if($money > $this->member['hongbao_ereryday_total']){
                return $this->json(['status'=>0,'msg'=>'可提现金额不足']);
            }
            $money = $money*(1-$set['withdrawfee']*0.01);
            $money = round($money,2);
            $score=0;
            if($set['withdraw_score'] ==1 && $set['withdraw_score_bili'] > 0 && $post['paytype'] == '积分') {
                $score = round($money * $set['withdraw_score_bili'],0);
            }

            $ordernum = date('ymdHis').aid.rand(1000,9999);
            $record['aid'] = aid;
            $record['mid'] = mid;
            $record['createtime']= time();
            $record['money'] = $money;
            $record['score'] = $score;
            $record['txmoney'] = $post['money'];

            $record['ordernum'] = $ordernum;
            $record['paytype'] = $post['paytype'];
            $record['platform'] = platform;
            $recordid = Db::name('member_hbe_withdrawlog')->insertGetId($record);

            \app\common\Member::addHongbaoLog(aid,mid,-$post['money'],'补贴提现:'.$post['paytype']);
            //记录
            $data = [];
            $data['aid'] = aid;
            $data['mid'] = mid;
            $data['money'] = $post['money'] * -1;
            $data['createdate'] = date('Y-m-d');
            $data['createtime'] = time();
            $data['remark'] = '每日补贴';
            $data['status'] = 1;
            Db::name('member_hbe_record')->insert($data);
            \app\common\Member::addHongbaoLog(aid,mid, $post['money'] * -1,$data['remark']);
            $afterTotal = $this->member['hongbao_ereryday_total'] - $post['money'];
            Db::name('member')->where('aid',aid)->where('id',mid)->update(['hongbao_ereryday_total' => $afterTotal]);

            $tmplcontent = array();
            $tmplcontent['first'] = '有客户申请补贴提现';
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $this->member['nickname'];
            $tmplcontent['keyword2'] = date('Y-m-d H:i');
            $tmplcontent['keyword3'] = $money.'元';
            $tmplcontent['keyword4'] = $post['paytype'];
            \app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('admin/index/index'));
			$tmplcontent = [];
			$tmplcontent['name3'] = $this->member['nickname'];
			$tmplcontent['amount1'] = $money.'元';
			$tmplcontent['date2'] = date('Y-m-d H:i');
			$tmplcontent['thing4'] = '提现到'.$post['paytype'];
			\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'admin/index/index');

            if($post['paytype'] == '余额') {
                Db::name('member_hbe_withdrawlog')->where('id',$recordid)->update(['status' => 3, 'paytime' => time()]);
                \app\common\Member::addmoney(aid,mid,$record['money'],'补贴提现');
            } elseif($post['paytype'] == '积分' && $score > 0) {
                Db::name('member_hbe_withdrawlog')->where('id',$recordid)->update(['status' => 3, 'paytime' => time()]);
                \app\common\Member::addscore(aid,mid,$score,'补贴提现到积分');
            }
            if($set['withdraw_autotransfer'] && $post['paytype'] == '微信钱包'){
                $rs = \app\common\Wxpay::transfers(aid,mid,$record['money'],$record['ordernum'],platform,'补贴额度提现');
                if($rs['status']==0){
                    return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                }else{
                    Db::name('member_hbe_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                    //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的红包提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) $record['money'];
                    $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                    \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'));
                    //订阅消息
                    $tmplcontent = [];
                    $tmplcontent['amount1'] = $record['money'];
                    $tmplcontent['thing3'] = '微信打款';
                    $tmplcontent['time5'] = date('Y-m-d H:i');
					
					$tmplcontentnew = [];
					$tmplcontentnew['amount3'] = $record['money'];
					$tmplcontentnew['phrase9'] = '微信打款';
					$tmplcontentnew['date8'] = date('Y-m-d H:i');
                    \app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                    //短信通知
                    if($this->member['tel']){
                        \app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
                    }
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }

            return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
        }
        $userinfo = Db::name('member')->where('id',mid)->field('id,money,aliaccount,bankname,bankcarduser,bankcardnum,hongbao_ereryday_total')->find();
        //订阅消息
        $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
        $tmplids = [];
        if($wx_tmplset['tmpl_tixiansuccess_new']){
            $tmplids[] = $wx_tmplset['tmpl_tixiansuccess_new'];
        }elseif($wx_tmplset['tmpl_tixiansuccess']){
            $tmplids[] = $wx_tmplset['tmpl_tixiansuccess'];
        }
        if($wx_tmplset['tmpl_tixianerror_new']){
            $tmplids[] = $wx_tmplset['tmpl_tixianerror_new'];
        }elseif($wx_tmplset['tmpl_tixianerror']){
            $tmplids[] = $wx_tmplset['tmpl_tixianerror'];
        }
        $set['withdraw_score_desc'] = '';
        if($set['withdraw_score_bili'] > 0) {
            $set['withdraw_score_desc'] = '1元='.$set['withdraw_score_bili'].t('积分').'，结果取整（四舍五入）';
        }
        $rdata = [];
        $rdata['userinfo'] = $userinfo;
        $rdata['sysset'] = $set;
        $rdata['tmplids'] = $tmplids;
        return $this->json($rdata);
    }
}
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
// | 每日红包
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class HongbaoEveryday extends Common
{	
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
	//编辑
	public function index(){
        $info = Db::name('hongbao_everyday')->where('aid',aid)->find();
		if(empty($info)){
			$info = array(
				'id'=>'',
				'guize'=>'1.活动时间：'.date('Y年m月d日').'——'.date('Y年m月d日',time()+86400).'。',
				'pertotal'=>'3',
				'perday'=>'3',
				'shareaddnum'=>'0',
				'sharedaytimes'=>'0',
				'sharetimes'=>0,
				'starttime'=>time()-100,
				'endtime'=>time()+86400-100,
				'usescore'=>0,
				'status'=>1,
			);
		}
		View::assign('info',$info);

		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
		$info['starttime'] = strtotime($info['starttime']);
		$info['endtime'] = strtotime($info['endtime']);
        $info['withdraw'] = $info['withdraw'] ? $info['withdraw'] : 0;
        $info['withdraw_weixin'] = $info['withdraw_weixin'] ? $info['withdraw_weixin'] : 0;

		if($info['id']){
			$info['updatetime'] = time();
			Db::name('hongbao_everyday')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑每日红包'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
			$id = Db::name('hongbao_everyday')->insertGetId($info);
			\app\common\System::plog('添加每日红包'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//领取记录
	public function record(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'hl.id desc';
			}
			$where = [];
			$where[] = ['hl.aid','=',aid];
			if(input('param.mid')) $where[] = ['hl.mid','=',input('param.mid')];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['hl.createtime','>=',strtotime($ctime[0])];
				$where[] = ['hl.createtime','<',strtotime($ctime[1]) + 86400];
			}
            if(input('param.utime') ){
                $utime = explode(' ~ ',input('param.utime'));
                $where[] = ['hl.updatetime','>=',strtotime($utime[0])];
                $where[] = ['hl.updatetime','<',strtotime($utime[1]) + 86400];
            }
			if(input('?param.status') && input('param.status')!==''){
				$where[] = ['hl.status','=',input('param.status')];
			}
			$count = 0 + Db::name('hongbao_everyday_list')->alias('hl')->leftJoin('member m', 'm.id = hl.mid')->where($where)->count();
			$data = Db::name('hongbao_everyday_list')->alias('hl')->leftJoin('member m', 'm.id = hl.mid')->where($where)->page($page,$limit)->order($order)->fieldRaw('hl.*,m.nickname,m.headimg')->select()->toArray();
			foreach($data as $k=>$v){

			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
	}
	//改状态
	public function setst(){
		$ids = input('post.ids/a');
		$st = input('post.st/d');
		Db::name('hongbao_everyday_list')->where('aid',aid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog('修改每日红包记录状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'修改成功']);
	}
	//领取记录导出
	public function recordexcel(){
        $where = [];
        $where[] = ['hl.aid','=',aid];
        if(input('param.mid')) $where[] = ['hl.mid','=',input('param.mid')];
        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['hl.createtime','>=',strtotime($ctime[0])];
            $where[] = ['hl.createtime','<',strtotime($ctime[1]) + 86400];
        }
        if(input('param.utime') ){
            $utime = explode(' ~ ',input('param.utime'));
            $where[] = ['hl.updatetime','>=',strtotime($utime[0])];
            $where[] = ['hl.updatetime','<',strtotime($utime[1]) + 86400];
        }
        if(input('?param.status') && input('param.status')!==''){
            $where[] = ['hl.status','=',input('param.status')];
        }
        $list = Db::name('hongbao_everyday_list')->alias('hl')->leftJoin('member m', 'm.id = hl.mid')->where($where)->fieldRaw('hl.*,m.nickname,m.headimg')->select()->toArray();


        $title = array();
		$title[] = '序号';
		$title[] = t('会员').'ID';
		$title[] = '昵称';
		$title[] = '红包金额';
		$title[] = '剩余金额';
        $title[] = '红包时间';
		$title[] = '领取时间';
		$title[] = '状态';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['id'];
			$tdata[] = $v['mid'];
			$tdata[] = $v['nickname'];
			$tdata[] = $v['money'];
			$tdata[] = $v['left'];
            $tdata[] = $v['createdate'];
			$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
			$status = '';
			if($v['status']==1){
				$status = '已领取';
			}elseif($v['status']==0){
				$status = '未领取';
			}
			$tdata[] = $status;
			$data[] = $tdata;
		}
		$this->export_excel($title,$data);
	}
	//删除
	public function recorddel(){
		$ids = input('post.ids/a');
		Db::name('hongbao_everyday_list')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除每日红包记录'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	public function eduRecord() {
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'hl.id desc';
            }
            $where = [];
            $where[] = ['hl.aid','=',aid];
            if(input('param.mid')) $where[] = ['hl.mid','=',input('param.mid')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['hl.createtime','>=',strtotime($ctime[0])];
                $where[] = ['hl.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(input('param.utime') ){
                $utime = explode(' ~ ',input('param.utime'));
                $where[] = ['hl.updatetime','>=',strtotime($utime[0])];
                $where[] = ['hl.updatetime','<',strtotime($utime[1]) + 86400];
            }
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['hl.status','=',input('param.status')];
            }
            $count = 0 + Db::name('member_hbe_edu_record')->alias('hl')->leftJoin('member m', 'm.id = hl.mid')->where($where)->count();
            $data = Db::name('member_hbe_edu_record')->alias('hl')->leftJoin('member m', 'm.id = hl.mid')->where($where)->page($page,$limit)->order($order)->fieldRaw('hl.*,m.nickname,m.headimg')->select()->toArray();
            foreach($data as $k=>$v){

            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        return View::fetch();
    }

    //删除
    public function eduRecordDel(){
        $ids = input('post.ids/a');
        Db::name('member_hbe_edu_record')->where('aid',aid)->where('id','in',$ids)->delete();
        \app\common\System::plog('删除每日红包额度记录'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    //红包明细
    public function log(){
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 'member_hbe_log.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'member_hbe_log.id desc';
            }
            $where = [];
            $where[] = ['member_hbe_log.aid','=',aid];

            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_hbe_log.mid','=',trim(input('param.mid'))];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_hbe_log.status','=',input('param.status')];
            $count = 0 + Db::name('member_hbe_log')->alias('member_hbe_log')->field('member.nickname,member.headimg,member_hbe_log.*')->join('member member','member.id=member_hbe_log.mid')->where($where)->count();
            $data = Db::name('member_hbe_log')->alias('member_hbe_log')->field('member.nickname,member.headimg,member_hbe_log.*')->join('member member','member.id=member_hbe_log.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        return View::fetch();
    }
    //红包明细导出
    public function logExcel(){
        if(input('param.field') && input('param.order')){
            $order = 'member_hbe_log.'.input('param.field').' '.input('param.order');
        }else{
            $order = 'member_hbe_log.id desc';
        }
        $where = array();
        $where[] = ['member_hbe_log.aid','=',aid];

        if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
        if(input('param.mid')) $where[] = ['member_hbe_log.mid','=',trim(input('param.mid'))];
        if(input('?param.status') && input('param.status')!=='') $where[] = ['member_hbe_log.status','=',input('param.status')];
        $list = Db::name('member_hbe_log')->alias('member_hbe_log')->field('member.nickname,member.headimg,member_hbe_log.*')->join('member member','member.id=member_hbe_log.mid')->where($where)->order($order)->select()->toArray();
        $title = array();
        $title[] = t('会员').'信息';
        $title[] = '变更金额';
        $title[] = '变更后剩余';
        $title[] = '变更时间';
        $title[] = '备注';
        $data = array();
        foreach($list as $v){
            $tdata = array();
            $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
            $tdata[] = $v['money'];
            $tdata[] = $v['after'];
            $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
            $tdata[] = $v['remark'];
            $data[] = $tdata;
        }
        $this->export_excel($title,$data);
    }
    //红包明细删除
    public function logDel(){
        $ids = input('post.ids/a');
        Db::name('member_hbe_log')->where('aid',aid)->where('id','in',$ids)->delete();
        \app\common\System::plog('删除红包明细'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    //提现记录
    public function withdrawLog(){
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 'member_hbe_withdrawlog.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'member_hbe_withdrawlog.id desc';
            }
            $where = [];
            $where[] = ['member_hbe_withdrawlog.aid','=',aid];
            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_hbe_withdrawlog.mid','=',trim(input('param.mid'))];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_hbe_withdrawlog.status','=',input('param.status')];
            $count = 0 + Db::name('member_hbe_withdrawlog')->alias('member_hbe_withdrawlog')->field('member.nickname,member.headimg,member_hbe_withdrawlog.*')->join('member member','member.id=member_hbe_withdrawlog.mid')->where($where)->count();
            $data = Db::name('member_hbe_withdrawlog')->alias('member_hbe_withdrawlog')->field('member.nickname,member.headimg,member_hbe_withdrawlog.*')->join('member member','member.id=member_hbe_withdrawlog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        return View::fetch();
    }
    //提现记录导出
    public function withdrawLogExcel(){
        if(input('param.field') && input('param.order')){
            $order = 'member_hbe_withdrawlog.'.input('param.field').' '.input('param.order');
        }else{
            $order = 'member_hbe_withdrawlog.id desc';
        }
        $where = [];
        $where[] = ['member_hbe_withdrawlog.aid','=',aid];
        if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
        if(input('param.mid')) $where[] = ['member_hbe_withdrawlog.mid','=',trim(input('param.mid'))];
        if(input('?param.status') && input('param.status')!=='') $where[] = ['member_hbe_withdrawlog.status','=',input('param.status')];
        $list = Db::name('member_hbe_withdrawlog')->alias('member_hbe_withdrawlog')->field('member.nickname,member.headimg,member_hbe_withdrawlog.*')->join('member member','member.id=member_hbe_withdrawlog.mid')->where($where)->order($order)->select()->toArray();
        $title = array();
        $title[] = t('会员').'信息';
        $title[] = '提现金额';
        $title[] = '打款金额';
        $title[] = '提现方式';
        $title[] = '收款账号';
        $title[] = '提现时间';
        $title[] = '状态';
        $data = array();
        foreach($list as $v){
            $tdata = array();
            $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
            $tdata[] = $v['txmoney'];
            $tdata[] = $v['money'];
            $tdata[] = $v['paytype'];
            if($v['paytype'] == '支付宝'){
                $tdata[] = $v['aliaccount'];
            }elseif($v['paytype'] == '银行卡'){
                $tdata[] = $v['bankname'] . ' - ' .$v['bankcarduser']. ' - '.$v['bankcardnum'];
            }else{
                $tdata[] = '';
            }
            $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
            $st = '';
            if($v['status']==0){
                $st = '审核中';
            }elseif($v['status']==1){
                $st = '已审核';
            }elseif($v['status']==2){
                $st = '已驳回';
            }elseif($v['status']==3){
                $st = '已打款';
            }
            $tdata[] = $st;
            $data[] = $tdata;
        }
        $this->export_excel($title,$data);
    }
    //提现记录改状态
    public function withdrawLogSetst(){
        $id = input('post.id/d');
        $st = input('post.st/d');
        $reason = input('post.reason');
        $info = Db::name('member_hbe_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        if($st==10){//微信打款
            if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
            $rs = \app\common\Wxpay::transfers(aid,$info['mid'],$info['money'],$info['ordernum'],$info['platform'],'红包提现');
            if($rs['status']==0){
                return json(['status'=>0,'msg'=>$rs['msg']]);
            }else{
                Db::name('member_hbe_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'reason'=>$reason,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                //提现成功通知
                $tmplcontent = [];
                $tmplcontent['first'] = '您的红包提现申请已打款，请留意查收';
                $tmplcontent['remark'] = '请点击查看详情~';
                $tmplcontent['money'] = (string) $info['money'];
                $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                \app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'));
                //订阅消息
                $tmplcontent = [];
                $tmplcontent['amount1'] = $info['money'];
                $tmplcontent['thing3'] = $info['paytype'];
                $tmplcontent['time5'] = date('Y-m-d H:i');
				
				$tmplcontentnew = [];
				$tmplcontentnew['amount3'] = $info['money'];
				$tmplcontentnew['phrase9'] = $info['paytype'];
				$tmplcontentnew['date8'] = date('Y-m-d H:i');
                \app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                //短信通知
                $member = Db::name('member')->where('id',$info['mid'])->find();
                if($member['tel']){
                    $tel = $member['tel'];
                    \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                }
                \app\common\System::plog('红包提现微信打款'.$id);
                return json(['status'=>1,'msg'=>$rs['msg']]);
            }
        }else{
            Db::name('member_hbe_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>$st,'reason'=>$reason]);
            if($st == 2){//驳回返还余额
                \app\common\Member::addHongbaoLog(aid,$info['mid'],$info['txmoney'],'红包提现返还');
                //提现失败通知
                $tmplcontent = [];
                $tmplcontent['first'] = '您的红包提现申请被商家驳回，可与商家协商沟通。';
                $tmplcontent['remark'] = $reason.'，请点击查看详情~';
                $tmplcontent['money'] = (string) $info['txmoney'];
                $tmplcontent['time'] = date('Y-m-d H:i',$info['createtime']);
                \app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontent,m_url('pages/my/usercenter'));
                //订阅消息
                $tmplcontent = [];
                $tmplcontent['amount1'] = $info['txmoney'];
                $tmplcontent['time3'] = date('Y-m-d H:i',$info['createtime']);
                $tmplcontent['thing4'] = $reason;
				
				$tmplcontentnew = [];
				$tmplcontentnew['thing1'] = '提现失败';
				$tmplcontentnew['amount2'] = $info['txmoney'];
				$tmplcontentnew['date4'] = date('Y-m-d H:i',$info['createtime']);
				$tmplcontentnew['thing12'] = $reason;
                \app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                //短信通知
                $member = Db::name('member')->where('id',$info['mid'])->find();
                if($member['tel']){
                    $tel = $member['tel'];
                    \app\common\Sms::send(aid,$tel,'tmpl_tixianerror',['reason'=>$reason]);
                }
                \app\common\System::plog('红包提现驳回'.$id);
            }
            if($st==3){
                //提现成功通知
                $tmplcontent = [];
                $tmplcontent['first'] = '您的红包提现申请已打款，请留意查收';
                $tmplcontent['remark'] = '请点击查看详情~';
                $tmplcontent['money'] = (string) $info['money'];
                $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                \app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'));
                //订阅消息
                $tmplcontent = [];
                $tmplcontent['amount1'] = $info['money'];
                $tmplcontent['thing3'] = $info['paytype'];
                $tmplcontent['time5'] = date('Y-m-d H:i');
				
				$tmplcontentnew = [];
				$tmplcontentnew['amount3'] = $info['money'];
				$tmplcontentnew['phrase9'] = $info['paytype'];
				$tmplcontentnew['date8'] = date('Y-m-d H:i');
                \app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                //短信通知
                $member = Db::name('member')->where('id',$info['mid'])->find();
                if($member['tel']){
                    $tel = $member['tel'];
                    \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                }
                \app\common\System::plog('红包提现改为已打款'.$id);
            }
        }
        return json(['status'=>1,'msg'=>'操作成功']);
    }
    //提现记录删除
    public function withdrawLogDel(){
        $ids = input('post.ids/a');
        Db::name('member_hbe_withdrawlog')->where('aid',aid)->where('id','in',$ids)->delete();
        \app\common\System::plog('余额提现记录删除'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }
}
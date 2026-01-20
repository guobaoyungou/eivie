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
// | 商户 - 余额管理
// +----------------------------------------------------------------------
namespace app\controller;
use pay\wechatpay\WxPayV3;
use think\facade\View;
use think\facade\Db;

class BusinessMoney extends Common
{
	//余额明细
    public function moneylog(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'business_moneylog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'business_moneylog.id desc';
			}
			$where = [];
			$where[] = ['business_moneylog.aid','=',aid];
			if(bid != 0){
				$where[] = ['business_moneylog.bid','=',bid];
			}else{
				if(input('param.bid')) $where[] = ['business_moneylog.bid','=',trim(input('param.bid'))];
                if(getcustom('user_area_agent') && $this->user['isadmin']==3){
                    $areaBids = \app\common\Business::getUserAgentBids(aid,$this->user);
                    $where[] = ['business_moneylog.bid','in',$areaBids];
                }
			}
			if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['business_moneylog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['business_moneylog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['business_moneylog.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(input('remark')){
                $where[] = ['business_moneylog.remark','like','%'.trim(input('param.remark')).'%'];
            }
			$count = 0 + Db::name('business_moneylog')->alias('business_moneylog')->field('business.name,business_moneylog.*')->join('business business','business.id=business_moneylog.bid')->where($where)->count();
			$data = Db::name('business_moneylog')->alias('business_moneylog')->field('business.name,business_moneylog.*')->join('business business','business.id=business_moneylog.bid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			if(getcustom('hmy_yuyue')){
				foreach($data as &$d){
					 if (strpos($d['remark'], '/')) {
						$workerarr = explode('/',$d['remark']);	
						$d['worker'] = $workerarr[1];
					 }
				}	
			}
            $total = [];
            if(getcustom('finance_statistics')){
                if($page==1){
                    //统计余额数据
                    $total = \app\custom\FinanceStatistics::businessmoney_statistics(aid,bid,$where);
                }
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'total'=>$total]);
		}
		return View::fetch();
    }
	//余额明细导出
	public function moneylogexcel(){
		if(input('param.field') && input('param.order')){
			$order = 'business_moneylog.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'business_moneylog.id desc';
		}
		$where = [];
		$where[] = ['business_moneylog.aid','=',aid];
		if(bid != 0){
			$where[] = ['business_moneylog.bid','=',bid];
		}else{
			if(input('param.bid')) $where[] = ['business_moneylog.bid','=',trim(input('param.bid'))];
		}
		if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['business_moneylog.status','=',input('param.status')];
        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['business_moneylog.createtime','>=',strtotime($ctime[0])];
            $where[] = ['business_moneylog.createtime','<',strtotime($ctime[1]) + 86400];
        }
        if(input('remark')){
            $where[] = ['business_moneylog.remark','like','%'.trim(input('param.remark')).'%'];
        }
		$list = Db::name('business_moneylog')->alias('business_moneylog')->field('business.name,business_moneylog.*')->join('business business','business.id=business_moneylog.bid')->where($where)->order($order)->select()->toArray();
		$title = array();
		$title[] = '商户名称';
		$title[] = '变更金额';
		$title[] = '变更后剩余';
		$title[] = '变更时间';
		$title[] = '备注';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['name'];
			$tdata[] = $v['money'];
			$tdata[] = $v['after'];
			$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
			$tdata[] = $v['remark'];
			$data[] = $tdata;
		}
		$this->export_excel($title,$data);
	}
	//余额明细改状态
	public function moneylogsetst(){
		if(bid > 0) showmsg('无操作权限');
		$ids = input('post.ids/a');
        $st = input('post.st/d');
		Db::name('business_moneylog')->where('aid',aid)->where('id','in',$ids)->update(['status'=>$st]);
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//余额明细删除
	public function moneylogdel(){
		if(bid > 0) showmsg('无操作权限');
		$ids = input('post.ids/a');
		Db::name('business_moneylog')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除商户余额明细'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

    //余额提现
    public function withdraw(){
        $business = db('business')->where(array('id'=>bid))->find();
        $bset = db('business_sysset')->where(['aid'=>aid])->find();
        $admin_set = $this->adminSet;

        //pc商家余额提现是否开启
        $pc_withdraw_status = 1;
        if(getcustom('business_withdraw_pc')){
            if(bid > 0){
                $business_info = Db::name('business')->where('aid',aid)->where('id',bid)->field('id,pc_withdraw_status_type,pc_withdraw_status')->find();
                //如果是独立配置
                if($business_info['pc_withdraw_status_type'] == 1){
                    if($business_info['pc_withdraw_status'] == 0){
                        $pc_withdraw_status = 0;
                    }
                }else{
                    //跟随系统
                    $business_sysset = Db::name('business_sysset')->where('aid',aid)->field('id,pc_withdraw_status')->find();
                    if($business_sysset['pc_withdraw_status'] == 0){
                        $pc_withdraw_status = 0;
                    }
                }
            }else{
                $business_sysset = Db::name('business_sysset')->where('aid',aid)->field('id,pc_withdraw_status')->find();
                if($business_sysset['pc_withdraw_status'] == 0){
                    $pc_withdraw_status = 0;
                }
            }
        }

        if(request()->isPost()){
            $info = input('post.info/a');
            $money = floatval($info['money']);
            if($pc_withdraw_status != 1){
                return json(['status'=>0,'msg'=>'pc端余额提现已关闭']);
            }
            if($money < $bset['withdrawmin']){
                return json(['status'=>0,'msg'=>'提现金额不能小于'.$bset['withdrawmin']]);
            }
            if(getcustom('admin_login_sms_verify')){
                $checkTel = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('tel',$info['tel'])->find();
                if(empty($checkTel)){
                    return json(['status'=>0,'msg'=>'绑定手机号不正确']);
                }
                if($info['smscode'] == ''){
                    return json(['status'=>0,'msg'=>'短信验证码不能为空']);
                }elseif(md5($info['tel'].'-'.$info['smscode']) != session('smscode') || time() > session('smscodetime')){
                    return json(['status'=>0,'msg'=>'验证码错误或已过期']);
                }
            }

            if(getcustom('business_withdraw_otherset')){
                if($bset['withdrawmax']>0 && $money > $bset['withdrawmax']){
                    return json(['status'=>0,'msg'=>'提现金额过大，单笔'.t('余额').'提现最高金额为'.$bset['withdrawmax'].'元']);
                }
                if($bset['day_withdraw_num']<0){
                    return json(['status'=>0,'msg'=>'暂时不可提现']);
                }else if($bset['day_withdraw_num']>0){
                    $start_time = strtotime(date('Y-m-d 00:00:01'));
                    $end_time = strtotime(date('Y-m-d 23:59:59'));
                    $day_withdraw_num = 0 + Db::name('business_withdrawlog')->where('aid',aid)->where('bid',bid)->where('createtime','between',[$start_time,$end_time])->count();
                    $daynum = $day_withdraw_num+1;
                    if($daynum>$bset['day_withdraw_num']){
                        return json(['status'=>0,'msg'=>'今日申请提现次数已满，请明天继续申请提现']);
                    }
                }
            }
            //if($money > 5000){
            //	return ['status'=>0,'msg'=>'单次提现金额不能大于5000'];
            //}
            if($business['money'] < $money) return json(['status'=>0,'msg'=>'可提现余额不足']);
            if(empty($info['paytype'])){
                return json(['status'=>0,'msg'=>'请选择提现方式']);
            }
            $data = array();
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['txmoney'] = $money;
            $data['money'] = $money * (1-$bset['withdrawfee']*0.01);
            $data['paytype'] = $info['paytype'];
            $data['ordernum'] = date('YmdHis').rand(1000,9999);
            $data['createtime'] = time();
            $data['status'] = 0;
            $data['platform'] = 'pc';
            if($data['paytype'] == '银行卡'){
                $data['bankname'] = $business['bankname'];
                $data['bankcarduser'] = $business['bankcarduser'];
                $data['bankcardnum'] = $business['bankcardnum'];
                if($data['bankname']=='' || $data['bankcarduser']=='' || $data['bankcardnum']==''){
                    return json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>(string)url('Backstage/sysset')]);
                }
                db('business')->where(['id'=>bid])->update(['bankname'=>$data['bankname'],'bankcarduser'=>$data['bankcarduser'],'bankcardnum'=>$data['bankcardnum']]);
            }else if($data['paytype'] == '微信' || $data['paytype'] == '微信钱包'){

                $is_weixin_withdraw_max = 0;
                //超额判断$is_withdraw_max 1：超额 0不超
                if($bset['commission_autotransfer'] ==1 && $money > $bset['weixin_withdraw_max'] && $bset['weixin_withdraw_max'] > 0){
                    return json(['status'=>0,'msg'=>'该方式提现限额为'.$bset['weixin_withdraw_max'].'元']);
                    $is_weixin_withdraw_max = 1;
                }
                $data['weixin'] = $business['weixin'];
                if($data['weixin']==''){
                    return json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>(string)url('Backstage/sysset')]);
                }
                if($bset['commission_autotransfer']==1 && !$is_weixin_withdraw_max &&  $admin_set['wx_transfer_type']==0){
                    //是否超过限额
                    $mid = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('isadmin',1)->value('mid');
                    if(!$mid) return json(['status'=>0,'msg'=>'商户主管理员未绑定微信']);
                }
                //db('agent')->where(['agid'=>$agid])->update(['weixin'=>$data['weixin']]);
            }else if($data['paytype'] == '支付宝'){
                $is_alipay_withdraw_max = 0;
                //超额判断$is_withdraw_max 1：超额 0不超
                if($bset['commission_autotransfer'] ==1 && $money > $bset['alipay_withdraw_max'] && $bset['alipay_withdraw_max'] > 0){
                    return json(['status'=>0,'msg'=>'该方式提现限额为'.$bset['alipay_withdraw_max'].'元']);
                    $is_alipay_withdraw_max = 1;
                }
                $data['aliaccount'] = $business['aliaccount'];
                if($data['aliaccount']==''){
                    return json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>(string)url('Backstage/sysset')]);
                }
            }else if($data['paytype'] == '汇付斗拱'){

                if(getcustom('pay_huifu_business_withdraw') && getcustom('pay_huifu_fenzhang')){
                    if($money > $bset['huifu_withdraw_max'] && $bset['huifu_withdraw_max'] > 0){
                        return json(['status'=>0,'msg'=>'该方式提现限额为'.$bset['huifu_withdraw_max'].'元']);
                    }
                    //查询会员信息
                    if(!$business){
                        return json(['status'=>0,'msg'=>t('商户').'不存在']);
                    }
                    if($business['huifu_business_status']==0 || empty($business['huifu_id']) ){
                        return json(['status'=>0,'msg'=>t('商户').'汇付信息不完整']);
                    }
                }
            }else if($data['paytype'] == '店长汇付打款'){

                if(getcustom('pay_huifu_dianzhang_withdraw')){
                    if($money > $bset['huifu_withdraw_max'] && $bset['huifu_withdraw_max'] > 0){
                        return json(['status'=>0,'msg'=>'该方式提现限额为'.$bset['huifu_withdraw_max'].'元']);
                    }
                    //查询会员信息
                    if(!$business){
                        return json(['status'=>0,'msg'=>t('商户').'不存在']);
                    }
                    if(empty($business['mid'])){
                        return json(['status'=>0,'msg'=>'请先在管理员列表进行会员绑定']);
                    }
                    $memberinfo = Db::name('member')->where('aid',aid)->where('id',$business['mid'])->find();
                    if(empty($memberinfo['huifu_id']) || empty($memberinfo['huifu_token_no'])){
                        return json(['status'=>0,'msg'=>'请先对商家绑定的会员进行汇付进件操作']);
                    }
                }
            }else{
                if(getcustom('pay_adapay')){
                    if($data['paytype'] == '汇付天下'){
                        //查询商家的管理员，判断管理员是否已绑定会员
                        $admin_user =  Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('status',1)->where('isadmin',1)->find();
                        if(!$admin_user || !$admin_user['mid']){
                            return json(['status'=>0,'msg'=>'请到[系统-管理员列表]对默认管理员进行会员信息绑定']);
                        }
                        $mid = $admin_user['mid'];
                        $adapay_member = Db::name('adapay_member')->where('aid',aid)->where('mid',$admin_user['mid'])->find();
                        if(!$adapay_member){
                            return json(['status'=>0,'msg'=>'请会员到小程序端[余额提现]绑定汇付天下的信息']);
                        }
                    }
                }
            }

            $res = \app\common\Business::addmoney(aid,bid,-$money,'余额提现',false,'withdraw');
            if(!$res || ($res && $res['status'] !=1)){
                \think\facade\Log::write('Businesswithdrawfail_'.bid.'_'.$money);
                return json(['status'=>0,'msg'=>'提现失败']);
            }

            $id = db('business_withdrawlog')->insertGetId($data);
            if(!$id) return json(['status'=>0,'msg'=>'提现失败']);

            \app\common\System::plog('商家提现'.$id);

            if($data['paytype'] == '微信' || $data['paytype'] == '微信钱包'){

                if($bset['commission_autotransfer']==1 && !$is_weixin_withdraw_max &&  $admin_set['wx_transfer_type']==0){

                    $rs = \app\common\Wxpay::transfers(aid,$mid,$data['money'],$data['ordernum'],'','余额提现');
                    if($rs['status']==0){
                        //\app\common\Business::addmoney(aid,bid,$money,'余额提现失败返还');
                        $data = [];
                        $data['status'] = 1;
                        $data['reason'] = $rs['msg']??'微信提现失败';
                        Db::name('business_withdrawlog')->where('id',$id)->update($data);
                        return json(['status'=>1,'msg'=>'提交成功,请等待打款','url'=>(string)url('withdrawlog')]);
                    }else{
                        $data = [];
                        $data['weixin'] = t('会员').'ID：'.$mid;
                        $data['status'] = 3;
                        $data['paytime']= time();
                        $data['paynum'] = $rs['resp']['payment_no'];
                        Db::name('business_withdrawlog')->where('id',$id)->update($data);

                        //提现成功通知
                        $tmplcontent = [];
                        $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                        $tmplcontent['remark'] = '请点击查看详情~';
                        $tmplcontent['money'] = (string) $data['money'];
                        $tmplcontent['timet'] = date('Y-m-d H:i',$data['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) round($data['money'],2);//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$data['createtime']);//提现时间
                        \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                        //短信通知
                        $member = Db::name('member')->where('id',$mid)->find();
                        if($member['tel']){
                            $tel = $member['tel'];
                            \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$data['money']]);
                        }
                        
                        return json(['status'=>1,'msg'=>$rs['msg'],'url'=>(string)url('withdrawlog')]);
                    }
                }
            }else if($data['paytype'] == '支付宝'){
                if($bset['commission_autotransfer']==1 && !$is_alipay_withdraw_max){
                    $rs = \app\common\Alipay::transfers(aid,$data['ordernum'],$money,t('余额').'提现',$business['aliaccount'],$business['aliaccountname'],t('余额').'提现');
                    if($rs && $rs['status']==1){
                        $data = [];
                        $data['aliaccount'] = $business['aliaccount'];
                        $data['status'] = 3;
                        $data['paytime'] = time();
                        $data['paynum'] = $rs['resp']['payment_no'];
                        Db::name('business_withdrawlog')->where('id',$id)->update($data);
                        \app\common\System::plog('商家提现支付宝打款'.$id);
                        return json(['status'=>1,'msg'=>$rs['msg'],'url'=>(string)url('withdrawlog')]);
                    }else{
                        $data = [];
                        $data['status'] = 1;
                        $data['reason'] = $rs['sub_msg']??'支付宝提现失败';
                        Db::name('business_withdrawlog')->where('id',$id)->update($data);
                        return json(['status'=>1,'msg'=>'提交成功,请等待打款','url'=>(string)url('withdrawlog')]);
                    }
                }
            }else if($data['paytype'] == '汇付斗拱'){
                if(getcustom('pay_huifu_business_withdraw') && getcustom('pay_huifu_fenzhang') && $bset['commission_autotransfer']==1){
                    $is_huifu_withdraw_max = 0;
                    if($money > $bset['huifu_withdraw_max'] && $bset['huifu_withdraw_max'] > 0){
                        $is_huifu_withdraw_max = 1;
                    }
                    if(!$is_huifu_withdraw_max){
                        $huifu = new \app\custom\Huifu([],aid,bid,0,t('余额').'提现',$data['ordernum'],$data['money']);
                        $data['id'] = $id;
                        $rs = $huifu->moneypayTradeAcctpaymentPay($business['huifu_id'],array_merge($data,['tablename'=>'member_withdrawlog']));
                        if($rs['status']==0){
                            Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['reason'=>$rs['msg']]);
                            return json(['status'=>0,'msg'=>$rs['msg']?:'审核中']);
                        }elseif($rs['status']==2){//处理中
                            Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>4,'paynum'=>$rs['data']['hf_seq_id']]);
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$id);
                            return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                        }else{
                            $huifu->tradeSettlementEnchashmentRequest();
                            Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['hf_seq_id'],'reason'=>'']);

                            //提现成功通知
                            $tmplcontent = [];
                            $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                            $tmplcontent['remark'] = '请点击查看详情~';
                            $tmplcontent['money'] = (string) $data['money'];
                            $tmplcontent['timet'] = date('Y-m-d H:i',$data['createtime']);
                            $tempconNew = [];
                            $tempconNew['amount2'] = (string) round($data['money'],2);//提现金额
                            $tempconNew['time3'] = date('Y-m-d H:i',$data['createtime']);//提现时间
                            \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                            //短信通知
                            $member = Db::name('member')->where('id',$mid)->find();
                            if($member['tel']){
                                $tel = $member['tel'];
                                \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$data['money']]);
                            }
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$id);
                            return json(['status'=>1,'msg'=>$rs['msg'],'url'=>(string)url('withdrawlog')]);

                        }
                    }

                }
            }else if($data['paytype'] == '店长汇付打款'){
                if(getcustom('pay_huifu_dianzhang_withdraw') && $bset['commission_autotransfer']==1){
                    $is_huifu_withdraw_max = 0;
                    if($money > $bset['huifu_withdraw_max'] && $bset['huifu_withdraw_max'] > 0){
                        $is_huifu_withdraw_max = 1;
                    }
                    if(!$is_huifu_withdraw_max) {
                        $huifu = new \app\custom\Huifu([], aid, bid, $business['mid'], t('余额') . '提现', $data['ordernum'], $data['money']);
                        $data['id'] = $id;
                        $rs = $huifu->moneypayTradeAcctpaymentPay($business['huifu_id'], array_merge($data, ['tablename' => 'member_withdrawlog']));
                        if ($rs['status'] == 0) {
                            Db::name('business_withdrawlog')->where('aid', aid)->where('id', $id)->update(['reason' => $rs['msg']]);
                            return json(['status' => 0, 'msg' => $rs['msg'] ?: '审核中']);
                        } elseif ($rs['status'] == 2) {//处理中
                            Db::name('business_withdrawlog')->where('aid', aid)->where('id', $id)->update(['status' => 4, 'paynum' => $rs['data']['hf_seq_id']]);
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款' . $id);
                            return json(['status' => 1, 'msg' => '支付处理中，' . $rs['msg']]);
                        } else {
                            $huifu->tradeSettlementEnchashmentRequest();
                            Db::name('business_withdrawlog')->where('aid', aid)->where('id', $id)->update(['status' => 3, 'paytime' => time(), 'paynum' => $rs['data']['hf_seq_id'], 'reason' => '']);

                            //提现成功通知
                            $tmplcontent = [];
                            $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                            $tmplcontent['remark'] = '请点击查看详情~';
                            $tmplcontent['money'] = (string)$data['money'];
                            $tmplcontent['timet'] = date('Y-m-d H:i', $data['createtime']);
                            $tempconNew = [];
                            $tempconNew['amount2'] = (string)round($data['money'], 2);//提现金额
                            $tempconNew['time3'] = date('Y-m-d H:i', $data['createtime']);//提现时间
                            \app\common\Wechat::sendtmpl(aid, $mid, 'tmpl_tixiansuccess', $tmplcontent, m_url('admin/index/index'), $tempconNew);
                            //短信通知
                            $member = Db::name('member')->where('id', $mid)->find();
                            if ($member['tel']) {
                                $tel = $member['tel'];
                                \app\common\Sms::send(aid, $tel, 'tmpl_tixiansuccess', ['money' => $data['money']]);
                            }
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款' . $id);
                            return json(['status' => 1, 'msg' => $rs['msg'], 'url' => (string)url('withdrawlog')]);

                        }
                    }
                }
            }else{
                if(getcustom('pay_adapay')){
                    if($data['paytype'] == '汇付天下'){
                        //自动打款
                        if($bset['commission_autotransfer']==1){
                            $data['money'] = dd_money_format($data['money']);
                            $rs = \app\custom\AdapayPay::balancePay(aid,'h5',$adapay_member['member_id'],$data['ordernum'],$data['money']);
                            if($rs['status'] == 0){
                                $data = [];
                                $data['status'] = 1;
                                $data['reason'] = $rs['msg']??'汇付天下提现失败';
                                Db::name('business_withdrawlog')->where('id',$id)->update($data);
                                return json(['status'=>1,'msg'=>'提交成功,请等待打款','url'=>(string)url('withdrawlog')]);
                            }else{
                                //从用户余额中进行提现到银行卡
                                $drs = \app\custom\AdapayPay::drawcash(aid,'h5',$adapay_member['member_id'],$data['ordernum'],$data['money']);
                                if($drs['status'] == 0){
                                    $data = [];
                                    $data['status'] = 1;
                                    $data['reason'] = $drs['msg']??'汇付天下提现失败';
                                    Db::name('business_withdrawlog')->where('id',$id)->update($data);
                                    return json(['status'=>1,'msg'=>'提交成功,请等待打款','url'=>(string)url('withdrawlog')]);
                                }

                                $data = [];
                                $data['weixin'] = t('会员').'ID：'.$admin_user['mid'];
                                $data['status'] = 3;
                                $data['paytime'] = time();
                                $data['paynum'] = $rs['resp']['payment_no'];
                                Db::name('business_withdrawlog')->where('id',$id)->update($data);

                                //提现成功通知
                                $tmplcontent = [];
                                $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                                $tmplcontent['remark'] = '请点击查看详情~';
                                $tmplcontent['money'] = (string) $data['money'];
                                $tmplcontent['timet'] = date('Y-m-d H:i',$data['createtime']);
                                $tempconNew = [];
                                $tempconNew['amount2'] = (string) round($data['money'],2);//提现金额
                                $tempconNew['time3'] = date('Y-m-d H:i',$data['createtime']);//提现时间
                                \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                                //短信通知
                                $member = Db::name('member')->where('id',$mid)->find();
                                if($member['tel']){
                                    $tel = $member['tel'];
                                    \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$data['money']]);
                                }
                                \app\common\System::plog('商家提现汇付天下打款'.$id);
                                return json(['status'=>1,'msg'=>$rs['msg'],'url'=>(string)url('withdrawlog')]);
                            }
                        }else{
                            \app\common\Business::addmoney(aid,bid,-$money,'余额提现');
                        }
                    }
                }
            }

            if($bset['commission_autotransfer'] ==1 && $admin_set['wx_transfer_type']==1 && !$is_weixin_withdraw_max){
                if($data['paytype'] == '微信' || $data['paytype'] == '微信钱包') {
                    //使用了新版的商家转账功能
                    $mid = Db::name('admin_user')->where('aid', aid)->where('bid', bid)->where('isadmin', 1)->value('mid');
                    if (!$data['platform']) {
                        $member = Db::name('member')->where('id', $mid)->field('id,realname,wxopenid,mpopenid')->find();
                        $openid = $member['mpopenid'];
                        if (!$openid) {
                            $platform = 'wx';
                        } else {
                            $platform = 'mp';
                        }
                    } else {
                        $platform = $data['platform'];
                    }

                    //使用了新版的商家转账功能
                    $paysdk = new WxPayV3(aid, $mid, $platform);
                    $rs = $paysdk->transfer($data['ordernum'], $data['money'], '', t('余额') . '提现', 'business_withdrawlog', $id);
                    if ($rs['status'] == 1) {
                        $data_u = [
                            'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                            'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                            'wx_state' => $rs['data']['state'],//转账状态
                            'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                            'platform' => $platform
                        ];
                        Db::name('business_withdrawlog')->where('id', $id)->update($data_u);
                    } else {
                        $data_u = [
                            'status' => 1,
                            'wx_transfer_msg' => $rs['msg'],
                            'platform' => $platform
                        ];
                        Db::name('business_withdrawlog')->where('id', $id)->update($data_u);
                    }
                }
            }
            return json(['status'=>1,'msg'=>'提交成功','url'=>(string)url('withdrawlog')]);
        }
        View::assign('money',$business['money']);
        View::assign('business',$business);
        View::assign('bset',$bset);
        View::assign('admin_set',$admin_set);
        View::assign('pc_withdraw_status',$pc_withdraw_status);
        return View::fetch();
    }

	//提现记录
	public function withdrawlog(){
        cache('withdrawlog_invoice_id_'.aid.'_'.bid,null);
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'business_withdrawlog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'business_withdrawlog.id desc';
			}
			$where = [];
			$where[] = ['business_withdrawlog.aid','=',aid];
			if(bid != 0){
				$where[] = ['business_withdrawlog.bid','=',bid];
			}else{
				if(input('param.bid')) $where[] = ['business_withdrawlog.bid','=',trim(input('param.bid'))];
			}
			if(input('id')){
                $where[] = ['business_withdrawlog.id','=',input('id')];
            }
            if(getcustom('business_withdraw_cash_mobile')){
                $where[] = ['business_withdrawlog.status','<>',20];
            }
            if(getcustom('business_withdraw_invoice_mobile') && input('param.withdrawlog_invoice_id')){
                $where[] = ['business_withdrawlog.withdrawlog_invoice_id','=',input('param.withdrawlog_invoice_id')];
                cache('withdrawlog_invoice_id_'.aid.'_'.bid,input('param.withdrawlog_invoice_id'));
            }

			if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['business_withdrawlog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['business_withdrawlog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['business_withdrawlog.createtime','<',strtotime($ctime[1]) + 86400];
            }
			$count = 0 + Db::name('business_withdrawlog')->alias('business_withdrawlog')->field('business.mid,business.name,business_withdrawlog.*')->join('business business','business.id=business_withdrawlog.bid')->where($where)->count();
			$data = Db::name('business_withdrawlog')->alias('business_withdrawlog')->field('business.mid,business.name,business_withdrawlog.*')->join('business business','business.id=business_withdrawlog.bid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
				$mid = Db::name('admin_user')->where('aid',aid)->where('bid',$v['bid'])->where('isadmin',1)->value('mid');
				if($mid){
					$member = Db::name('member')->where('aid',aid)->where('id',$mid)->find();
					$data[$k]['headimg'] = $member['headimg'];
					$data[$k]['nickname'] = $member['nickname'];
				}else{
					$data[$k]['headimg'] = '';
					$data[$k]['nickname'] = '';
				}
                if(getcustom('pay_huifu_dianzhang_withdraw') && getcustom('pay_huifu')){
                    $data[$k]['huifu_id'] = $member['huifu_id'];
                }
                if(getcustom('pay_allinpay')){
                    $allinpaystatus = false;
                    if($v['paytype'] == '通联支付银行卡'){
                        $allinpaystatus = true;
                        //查询此商户提现信息
                        $log = Db::name('member_allinpay_yunst_withdrawlog')->where('logid',$v['id'])->where('type',3)->field('id,mid')->find();
                        if(!$log){
                            $allinpaystatus = false;
                        }
                        $member = Db::name('member')->where('id',$log['mid'])->where('aid',aid)->field('id')->find();
                        if(!$member){
                            $allinpaystatus = false;
                        }
                        //通联支付 通联企业会员
                        $companyuser = Db::name('member_allinpay_yunst_companyuser')->where('mid',$log['mid'])->where('status',2)->where('aid',aid)->field('id')->find();
                        if(!$companyuser){
                            $allinpaystatus = false;
                        }
                    }
                    $data[$k]['allinpaystatus'] = $allinpaystatus;
                }
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
    }
	//提现记录导出
	public function withdrawlogexcel(){
		if(input('param.field') && input('param.order')){
			$order = 'business_withdrawlog.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'business_withdrawlog.id desc';
		}
        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
		$where = [];
		$where[] = ['business_withdrawlog.aid','=',aid];
		if(bid != 0){
			$where[] = ['business_withdrawlog.bid','=',bid];
		}else{
			if(input('param.bid')) $where[] = ['business_withdrawlog.bid','=',trim(input('param.bid'))];
		}
        if(getcustom('business_withdraw_invoice_mobile') && cache('withdrawlog_invoice_id_'.aid.'_'.bid)){
            $where[] = ['business_withdrawlog.withdrawlog_invoice_id','=',cache('withdrawlog_invoice_id_'.aid.'_'.bid)];
        }
		if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['business_withdrawlog.status','=',input('param.status')];
        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['business_withdrawlog.createtime','>=',strtotime($ctime[0])];
            $where[] = ['business_withdrawlog.createtime','<',strtotime($ctime[1]) + 86400];
        }
		$list = Db::name('business_withdrawlog')->alias('business_withdrawlog')->field('business.name,business_withdrawlog.*')
            ->join('business business','business.id=business_withdrawlog.bid')
            ->where($where)->order($order)->page($page,$limit)->select()->toArray();
        $count = Db::name('business_withdrawlog')->alias('business_withdrawlog')->field('business.name,business_withdrawlog.*')
            ->join('business business','business.id=business_withdrawlog.bid')
            ->where($where)->order($order)->count();
		$title = array();
		$title[] = '商户名称';
		$title[] = '提现金额';
		$title[] = '打款金额';
		$title[] = '提现方式';
		$title[] = '收款账号';
		$title[] = '提现时间';
		$title[] = '状态';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['name'];
			$tdata[] = $v['txmoney'];
			$tdata[] = $v['money'];
			$tdata[] = $v['paytype'];
			if($v['paytype'] == '支付宝'){
				$tdata[] = $v['aliaccount'];
			}elseif($v['paytype'] == '银行卡'){
				$tdata[] = $v['bankname'] . ' - ' .$v['bankcarduser']. ' - '.$v['bankcardnum'];
			}elseif($v['paytype'] == '微信' || $v['paytype'] == '微信钱包'){
				$tdata[] = $v['weixin'];
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
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
		$this->export_excel($title,$data);
	}
	//提现记录改状态
	public function withdrawlogsetst(){
		if(bid > 0) showmsg('无操作权限');
		$id = input('post.id/d');
		$st = input('post.st/d');
		$reason = input('post.reason');
		$info = Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        $info['money'] = dd_money_format($info['money']);
        $info['txmoney'] = dd_money_format($info['txmoney']);
		$mid = Db::name('admin_user')->where('aid',aid)->where('bid',$info['bid'])->where('isadmin',1)->value('mid');
		if($st==10){//微信打款
			if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
			if(!$mid) return json(['status'=>0,'msg'=>'商户未绑定微信']);
			if(!$info['platform']){
                $member = Db::name('member')->where('id',$mid)->field('id,realname,wxopenid,mpopenid')->find();
                $openid = $member['mpopenid'];
                if(!$openid){
                    $platform = 'wx';
                }else{
                    $platform = 'mp';
                }
            }else{
                $platform = $info['platform'];
            }
            $admin_set = $this->adminSet;
            if($admin_set['wx_transfer_type']==1){
                //使用了新版的商家转账功能
                $paysdk = new WxPayV3(aid,$mid,$platform);
                $rs = $paysdk->transfer($info['ordernum'],$info['money'],'',t('余额').'提现','business_withdrawlog',$info['id']);
                if($rs['status']==1){
                    $data = [
                        'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                        'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                        'wx_state' => $rs['data']['state'],//转账状态
                        'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                        'platform' => $platform
                    ];
                    Db::name('business_withdrawlog')->where('id',$info['id'])->update($data);
                }else{
                    $data = [
                        'wx_transfer_msg' => $rs['msg'],
                        'platform' => $platform
                    ];
                    Db::name('business_withdrawlog')->where('id',$info['id'])->update($data);
                }
            }else {
                $rs = \app\common\Wxpay::transfers(aid, $mid, $info['money'], $info['ordernum'], '', '余额提现');
                if($rs['status']==1){
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'reason'=>$reason,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                }
            }
			if($rs['status']==0){
				return json(['status'=>0,'msg'=>$rs['msg']]);
			}else{
				//提现成功通知
				$tmplcontent = [];
				$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
				$tmplcontent['remark'] = '请点击查看详情~';
				$tmplcontent['money'] = (string) $info['money'];
				$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                $tempconNew = [];
                $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
				\app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
				//短信通知
				$member = Db::name('member')->where('id',$mid)->find();
				if($member['tel']){
					$tel = $member['tel'];
					\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
				}
				\app\common\System::plog('商家提现微信打款'.$id);
				return json(['status'=>1,'msg'=>$rs['msg']]);
			}
		}else if($st == 20){
            if(getcustom('pay_adapay')){
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                $adapay = Db::name('adapay_member')->where('aid',aid)->where('mid',$mid)->find();
                $rs = \app\custom\AdapayPay::balancePay(aid,'h5',$adapay['member_id'],$info['ordernum'],$info['money']);
                if($rs['status'] == 0){
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$info['id'])->update(['reason'=>$rs['msg']]);
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }else{
                    //从用户余额中进行提现到银行卡
                    $drs = \app\custom\AdapayPay::drawcash(aid,'h5',$adapay['member_id'],$info['ordernum'],$info['money']);
                    if($drs['status'] == 0){
                        Db::name('business_withdrawlog')->where('aid',aid)->where('id',$info['id'])->update(['reason'=>$drs['msg']]);
                        return json(['status'=>0,'msg'=>$drs['msg']]);
                    }

                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['balance_seq_id'],'reason'=>'']);
                    //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) round($info['money'],2);
                    $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
                    \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                    //短信通知
                    $member = Db::name('member')->where('id',$mid)->find();
                    if($member['tel']){
                        $tel = $member['tel'];
                        \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                    }
                    \app\common\System::plog('佣金提现汇付天下打款'.$id);
                    return json(['status'=>1,'msg'=>'已提交打款，请耐心等待']);
                }
            }
        }else if($st==30){
            if(getcustom('alipay_auto_transfer')){
                //支付宝打款
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                //查询会员信息
                $business = Db::name('business')->where('id',$info['bid'])->field('aliaccount,aliaccountname')->find();
                if(!$business){
                    return json(['status'=>0,'msg'=>t('商户').'不存在']);
                }
                if(empty($business['aliaccount']) || empty($business['aliaccountname']) ){
                    return json(['status'=>0,'msg'=>t('商户').'支付宝信息不完整']);
                }
                $rs = \app\common\Alipay::transfers(aid,$info['ordernum'],$info['money'],t('余额').'提现',$business['aliaccount'],$business['aliaccountname'],t('余额').'提现');
                if($rs['status']==0){
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }else{
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['pay_fund_order_id']]);
                    //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) $info['money'];
                    $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
                    \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                    //短信通知
                    $member = Db::name('member')->where('id',$mid)->find();
                    if($member['tel']){
                        $tel = $member['tel'];
                        \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                    }
                    \app\common\System::plog('商家提现支付宝打款'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }
        }else if($st==40){
            if(getcustom('pay_allinpay')){
                //云商通
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                //查询会员信息
                $business = Db::name('business')->where('id',$info['bid'])->field('aliaccount,aliaccountname')->find();
                if(!$business){
                    return json(['status'=>0,'msg'=>t('商户').'不存在']);
                }
                //查询此商户提现信息
                $log = Db::name('member_allinpay_yunst_withdrawlog')->where('logid',$id)->where('type',3)->find();
                if(!$log){
                    return json(['status'=>0,'msg'=>'系统通联用户提现记录不存在，不能提现']);
                }
                $member = Db::name('member')->where('id',$log['mid'])->where('aid',aid)->find();
                if(!$member){
                    return json(['status'=>0,'msg'=>'系统通联提现记录用户不存在，不能提现']);
                }

                //通联支付 通联企业会员
                $companyuser = Db::name('member_allinpay_yunst_companyuser')->where('mid',$log['mid'])->where('aid',aid)->find();
                if(!$companyuser){
                    return json(['status'=>0,'msg'=>'该提现发起用户通联企业会员不存在']);
                }
                if($companyuser['status'] == 1){
                    return json(['status'=>0,'msg'=>'该提现发起用户通联企业会员申请中']);
                }
                if($companyuser['status'] != 2){
                    return json(['status'=>0,'msg'=>'该提现发起用户通联企业会员申请失败']);
                }

                //先查询余额，余额不足去转账，然后提现，充足直接提现
                $queryBalance = \app\custom\AllinpayYunst::queryBalance(aid,$companyuser['bizUserId']);
                if(!$queryBalance || $queryBalance['status'] != 1){
                    $msg = $applicationTransfer && $applicationTransfer['msg']?$applicationTransfer['msg']:'';
                    Db::name('business_withdrawlog')->where('id',$id)->update(['reason'=>'通联查询余额失败'.$msg]);
                    return json(['status'=>0,'msg'=>$msg]);
                }
                $balance = ($queryBalance['data']['allAmount'] - $queryBalance['data']['freezenAmount'])/100;

                $withdrawStatus = false;
                if($balance>=$record['txmoney']){
                    //提现
                    $withdrawApply = \app\custom\AllinpayYunst::withdrawApply(aid,$member,$id,$info,3,2);
                    if($withdrawApply && $withdrawApply['status'] == 1){
                        $updata = [];
                        $updata['status']   = 1;
                        $updata['allinpayorderNo'] = $withdrawApply['data']['orderNo'];
                        Db::name('business_withdrawlog')->where('id',$id)->update($updata);
                        $withdrawStatus = true;
                    }else{
                        $msg = $withdrawApply && $withdrawApply['msg']?$withdrawApply['msg']:'';
                        Db::name('business_withdrawlog')->where('id',$id)->update(['reason'=>'通联提现失败'.$msg]);
                        return json(['status'=>0,'msg'=>$msg]);
                    }
                }else{
                    //转账
                    $applicationTransfer = \app\custom\AllinpayYunst::applicationTransfer(aid,$member,$id,$info,3,2);
                    if($applicationTransfer && $applicationTransfer['status'] == 1){
                        //提现
                        $withdrawApply = \app\custom\AllinpayYunst::withdrawApply(aid,$member,$id,$info,3,2);
                        if($withdrawApply && $withdrawApply['status'] == 1){
                            $updata = [];
                            $updata['status']   = 1;
                            $updata['allinpayorderNo'] = $withdrawApply['data']['orderNo'];
                            Db::name('business_withdrawlog')->where('id',$id)->update($updata);
                            $withdrawStatus = true;
                        }else{
                            $msg = $withdrawApply && $withdrawApply['msg']?$withdrawApply['msg']:'';
                            Db::name('business_withdrawlog')->where('id',$id)->update(['reason'=>'通联提现失败'.$msg]);
                            return json(['status'=>0,'msg'=>$msg]);
                        }
                    }else{
                        $msg = $applicationTransfer && $applicationTransfer['msg']?$applicationTransfer['msg']:'';
                        Db::name('business_withdrawlog')->where('id',$id)->update(['reason'=>'通联转账失败'.$msg]);
                        return json(['status'=>0,'msg'=>$msg]);
                    }
                }
                if($withdrawStatus){
                    //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) $info['money'];
                    $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
                    \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                    if($member['tel']){
                        $tel = $member['tel'];
                        \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                    }
                    \app\common\System::plog('商家提现通联云商通'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }
        }else if($st == 50){
            if(getcustom('pay_huifu_business_withdraw') && getcustom('pay_huifu_fenzhang')){
                //汇付斗拱余额打款 余额支付
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                //查询会员信息
                $business = Db::name('business')->where('id',$info['bid'])->field('huifu_business_status,huifu_id')->find();
                if(!$business){
                    return json(['status'=>0,'msg'=>t('商户').'不存在']);
                }
                if($business['huifu_business_status']==0 || empty($business['huifu_id']) ){
                    return json(['status'=>0,'msg'=>t('商户').'汇付信息不完整']);
                }

                $huifu = new \app\custom\Huifu([],aid,bid,0,t('余额').'提现',$info['ordernum'],$info['money']);
                $rs = $huifu->moneypayTradeAcctpaymentPay($business['huifu_id'],array_merge($info,['tablename'=>'member_withdrawlog']));
                if($rs['status']==0){
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$info['id'])->update(['reason'=>$rs['msg']]);
                    return json(['status'=>0,'msg'=>$rs['msg']?:'打款失败']);
                }elseif($rs['status']==2){//处理中
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>4,'paynum'=>$rs['data']['hf_seq_id']]);
                    \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$id);
                    return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                }else{
                    $huifu->tradeSettlementEnchashmentRequest();
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['hf_seq_id'],'reason'=>'']);
                     //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) round($info['money'],2);
                    $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
                    \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                    //短信通知
                    $member = Db::name('member')->where('id',$mid)->find();
                    if($member['tel']){
                        $tel = $member['tel'];
                        \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                    }
                    \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }

        }else if($st == 60){
            if(getcustom('pay_huifu_dianzhang_withdraw')){
                //店长汇付打款 余额支付
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                //查询会员信息
                $business = Db::name('business')->where('id',$info['bid'])->find();
                if(!$business){
                    return json(['status'=>0,'msg'=>t('商户').'不存在']);
                }

                if(!$business['mid']){
                    return $this->json(['status'=>0,'msg'=>'请先在管理员列表进行会员绑定']);
                }
                $memberinfo = Db::name('member')->where('aid',aid)->where('id',$business['mid'])->find();
                if(empty($memberinfo['huifu_id']) || empty($memberinfo['huifu_token_no'])){
                    return $this->json(['status'=>0,'msg'=>'请先对商家绑定的会员进行汇付进件操作']);
                }


                $huifu = new \app\custom\Huifu([],aid,bid,$business['mid'],t('余额').'提现',$info['ordernum'],$info['money']);
                $rs = $huifu->moneypayTradeAcctpaymentPay($memberinfo['huifu_id'],array_merge($info,['tablename'=>'member_withdrawlog']));
                if($rs['status']==0){
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$info['id'])->update(['reason'=>$rs['msg']]);
                    return json(['status'=>0,'msg'=>$rs['msg']?:'打款失败']);
                }elseif($rs['status']==2){//处理中
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>4,'paynum'=>$rs['data']['hf_seq_id']]);
                    \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$id);
                    return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                }else{
                    $huifu->tradeSettlementEnchashmentRequest();
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['hf_seq_id'],'reason'=>'']);
                     //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) round($info['money'],2);
                    $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
                    \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                    //短信通知
                    $member = Db::name('member')->where('id',$mid)->find();
                    if($member['tel']){
                        $tel = $member['tel'];
                        \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                    }
                    \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }
        }elseif($st=='shangfutong'){
            if(getcustom('shangfutong_daifu')){
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                $business = Db::name('business')->where('id',$info['bid'])->field('aliaccount,aliaccountname')->find();
                if(!$business){
                    return json(['status'=>0,'msg'=>t('商户').'不存在']);
                }
                $info['aliaccount'] = $business['aliaccount'];
                $info['aliaccountname'] = $business['aliaccountname'];
                $sft = new \app\custom\Shangfutong(aid);
                $rs = $sft->transfer($info,3,t('佣金').'提现');
                if($rs['status']==0){
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>2,'reason'=>$rs['msg']]);
                    \app\common\Business::addmoney(aid,$info['bid'],$info['txmoney'],'余额提现返还');
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }elseif($rs['status'] == 1 && $rs['data']['state'] == 2) {
                    Db::name('business_withdrawlog')->where('aid', aid)->where('id', $id)->update(['status' => 3, 'paytime' => time(), 'paynum' => $rs['data']['transferId']]);
                    \app\common\System::plog('商家余额提现商福通打款' . $id);
                    return json(['status' => 1, 'msg' => $rs['msg']]);
                }else{
                    //处理中 回调修改
                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>4,'paynum'=>$rs['data']['transferId']]);
                    \app\common\System::plog('商家余额提现商福通打款'.$id);
                    return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                }
            }
        }else{
			Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>$st,'reason'=>$reason]);
			if($st == 2){//驳回返还余额
				\app\common\Business::addmoney(aid,$info['bid'],$info['txmoney'],'余额提现返还');

                if(getcustom('business_withdraw_cash_mobile')){
                    //商家余额提现驳回，自动退款
                    $order = Db::name('business_withdrawfee_cash_order')->where('aid',aid)->where('status',1)->where('withdrawlog_id',$id)->find();
                    if($order){
                        $rs = \app\common\Order::refund($order,$order['totalprice'],'商户余额提现驳回，自动退款');
                        if($rs['status']==1){
                            Db::name('business_withdrawfee_cash_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);
                        }
                    }
                }

				//提现失败通知
				$tmplcontent = [];
				$tmplcontent['first'] = '您的提现申请被商家驳回，可与商家协商沟通。';
				$tmplcontent['remark'] = $reason.'，请点击查看详情~';
				$tmplcontent['money'] = (string) $info['txmoney'];
				$tmplcontent['time'] = date('Y-m-d H:i',$info['createtime']);
				\app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixianerror',$tmplcontent,m_url('admin/index/index'));
				//短信通知
				$member = Db::name('member')->where('id',$mid)->find();
				if($member['tel']){
					$tel = $member['tel'];
					$rs = \app\common\Sms::send(aid,$tel,'tmpl_tixianerror',['reason'=>$reason]);
				}
				\app\common\System::plog('商家提现驳回'.$id);
			}
			if($st==3){
				//提现成功通知
				$tmplcontent = [];
				$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
				$tmplcontent['remark'] = '请点击查看详情~';
				$tmplcontent['money'] = (string) $info['money'];
				$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                $tempconNew = [];
                $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
				\app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
				//短信通知
				$member = Db::name('member')->where('id',$mid)->find();
				if($member['tel']){
					$tel = $member['tel'];
					$rs = \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
				}
				\app\common\System::plog('商家提现改为已打款'.$id);
			}

            if($info['paytype']=='商家管理员余额' || $info['status'] == 0){
                if(getcustom('business_withdraw_cash_mobile')){
                    $admin_user = Db::name('admin_user')->where('aid',aid)->where('bid',$info['bid'])->where('id',$info['tx_admin_user_id'])->find();
                    $rs = \app\common\Member::addmoney(aid,$admin_user['mid'], $info['money'],"商户余额提现");
                    if($rs && $rs['status']==1){
                        Db::name('business_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3]);
                        $tmplcontent = [];
                        $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                        $tmplcontent['remark'] = '请点击查看详情~';
                        $tmplcontent['money'] = (string) $info['money'];
                        $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
                        \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                        //短信通知
                        $member = Db::name('member')->where('id',$mid)->find();
                        if($member['tel']){
                            $tel = $member['tel'];
                            $rs = \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
                        }
                        \app\common\System::plog('商家提现到管理员余额已打款'.$id);
                    }else{
                        \app\common\System::plog('商家提现到管理员余额失败'.$id);
                    }
                }
            }
		}
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//提现记录删除
	public function withdrawlogdel(){
		if(bid > 0) showmsg('无操作权限');
		$ids = input('post.ids/a');
		Db::name('business_withdrawlog')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除商家提现记录'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

    //明细
    public function depositlog(){
        if(getcustom('business_deposit')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'business_depositlog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'business_depositlog.id desc';
                }
                $where = [];
                $where[] = ['business_depositlog.aid','=',aid];
                if(bid != 0){
                    $where[] = ['business_depositlog.bid','=',bid];
                }else{
                    if(input('param.bid')) $where[] = ['business_depositlog.bid','=',trim(input('param.bid'))];
                }
                if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['business_depositlog.status','=',input('param.status')];
                $count = 0 + Db::name('business_depositlog')->alias('business_depositlog')->field('business.name,business_depositlog.*')->join('business business','business.id=business_depositlog.bid')->where($where)->count();
                $data = Db::name('business_depositlog')->alias('business_depositlog')->field('business.name,business_depositlog.*')->join('business business','business.id=business_depositlog.bid')->where($where)->page($page,$limit)->order($order)->select()->toArray();

                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }

    }

    //明细删除
    public function depositlogdel(){
        if(bid > 0) showmsg('无操作权限');
        $ids = input('post.ids/a');
        Db::name('business_depositlog')->where('aid',aid)->where('id','in',$ids)->delete();
        \app\common\System::plog('删除商户保证金明细'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }
    //明细导出
    public function depositlogexcel(){
        if (getcustom('business_deposit')){
            if(input('param.field') && input('param.order')){
                $order = 'business_depositlog.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'business_depositlog.id desc';
            }
            $where = [];
            $where[] = ['business_depositlog.aid','=',aid];
            if(bid != 0){
                $where[] = ['business_depositlog.bid','=',bid];
            }else{
                if(input('param.bid')) $where[] = ['business_depositlog.bid','=',trim(input('param.bid'))];
            }
            if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['business_depositlog.status','=',input('param.status')];
            $list = Db::name('business_depositlog')->alias('business_depositlog')->field('business.name,business_depositlog.*')->join('business business','business.id=business_depositlog.bid')->where($where)->order($order)->select()->toArray();
            $title = array();
            $title[] = '商户名称';
            $title[] = '变更金额';
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            $data = array();
            foreach($list as $v){
                $tdata = array();
                $tdata[] = $v['name'];
                $tdata[] = $v['money'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
                $tdata[] = $v['remark'];
                $data[] = $tdata;
            }
            $this->export_excel($title,$data);
        }

    }

    //余额转账
    public function transfer(){
        $business = db('business')->where(array('id'=>bid))->find();
        $bset = db('business_sysset')->where(['aid'=>aid])->find();

        if(request()->isPost()){
            $info = input('post.info/a');
            $money = floatval($info['money']);
            $tomid = trim($info['tomid']);
            $type = $info['type'];

            $tomember = Db::name('member')->where('aid',aid)->where('id',$tomid)->find();
            if(!$tomember){
                return json(['status'=>0,'msg'=>'转入会员不存在']);
            }
            
            if($business['money'] < $money) return json(['status'=>0,'msg'=>'可转账余额不足']);
            // 实际到账 去掉手续费
            $tomoney = dd_money_format($money * (1-$bset['withdrawfee']*0.01));

            $res = \app\common\Business::addmoney(aid,bid,-$money,'余额转账');
            if ($res['status'] == 1 && $tomoney>0) {
                if($type == 'money'){
                    \app\common\Member::addmoney(aid,$tomid,$tomoney,'商家'.$business['name'].'转账');
                }
                if($type == 'commission'){
                    \app\common\Member::addcommission(aid,$tomid,0,$tomoney,'商家'.$business['name'].'转账');
                }
            }else{
                return $this->json(['status'=>0, 'msg' => '转账失败']);
            }
            \app\common\System::plog('商家余额转账'.$id);
            return json(['status'=>1,'msg'=>'转账成功','url'=>(string)url('moneylog')]);
        }
        View::assign('money',$business['money']);
        return View::fetch();
    }

    /**
     * 提现现金支付记录
     * https://doc.weixin.qq.com/doc/w3_AV4AYwbFACwCN1ZD3PXaSQiK0DP0F?scode=AHMAHgcfAA0A6OKFY5AeYAOQYKALU
     * @author: liud
     * @time: 2025/9/3 16:51
     */
    public function withdrawfeecashlog(){
        if(getcustom('business_withdraw_cash_mobile')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'cash_order.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'cash_order.id desc';
                }
                $where = [];
                $where[] = ['cash_order.aid','=',aid];
                $where[] = ['cash_order.status','in',[1,4]];

                if(bid != 0){
                    $where[] = ['cash_order.bid','=',bid];
                }else{
                    if(input('param.bid') && input('param.bid')!=0) $where[] = ['cash_order.bid','=',trim(input('param.bid'))];
                }

                if (input('param.keyword')) {
                    $where[] = ['member.nickname|cash_order.ordernum', 'like', '%' . input('param.keyword') . '%'];
                }
                if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];

                $count = 0 + Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('business.name,member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->join('business business','business.id=cash_order.bid', 'left')->order($order)->where($where)->count();
                $datalist = Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('business.name,member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->join('business business','business.id=cash_order.bid', 'left')->where($where)->order($order)->page($page,$limit)->order('cash_order.id desc')->select()->toArray();
                if (!$datalist) $datalist = [];
//var_dump(Db::name('business_withdrawfee_cash_order')->getLastSql());
                foreach ($datalist as &$v) {
                    $datekey = date('Ymd', $v['paytime']);
                    if (empty($v['paynum'])) {
                        $v['paynum'] = '';
                    }
                    if (empty($v['paytype'])) {
                        $v['paytype'] = '';
                    }
                    $v['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '';

                    if ( $v['status'] == 1) {
                        $v['status_name'] = '已支付';
                    }elseif ($v['status'] == 4){
                        $v['status_name'] = '已退款';
                    }else{
                        $v['status_name'] = '';
                    }
                }
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$datalist]);
            }
            return View::fetch();
        }
    }

    //删除提现现金支付记录
    public function withdrawfeecashdel(){
        if(getcustom('business_withdraw_cash_mobile')){
            $ids = input('post.ids/a');
            Db::name('business_withdrawfee_cash_order')->where('aid',aid)->where('id','in',$ids)->delete();
            \app\common\System::plog('删除提现现金支付记录'.implode(',',$ids));
            return json(['status'=>1,'msg'=>'删除成功']);
        }
    }

    public function withdrawfeecashexcel(){
        if(getcustom('business_withdraw_cash_mobile')){
            set_time_limit(0);
            ini_set('memory_limit', '2000M');
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 'cash_order.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'cash_order.id desc';
            }
            $where = [];
            $where[] = ['cash_order.aid','=',aid];
            $where[] = ['cash_order.status','=',1];

            if(bid != 0){
                $where[] = ['cash_order.bid','=',bid];
            }else{
                if(input('param.bid') && input('param.bid')!=0) $where[] = ['cash_order.bid','=',trim(input('param.bid'))];
            }

            if (input('param.keyword')) {
                $where[] = ['member.nickname|cash_order.ordernum', 'like', '%' . input('param.keyword') . '%'];
            }
            if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];

            $count = 0 + Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('business.name,member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->join('business business','business.id=cash_order.bid', 'left')->order($order)->where($where)->count();
            $datalist = Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('business.name,member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->join('business business','business.id=cash_order.bid', 'left')->where($where)->order($order)->page($page,$limit)->order('cash_order.id desc')->select()->toArray();
            if (!$datalist) $datalist = [];
//var_dump(Db::name('business_withdrawfee_cash_order')->getLastSql());

            $title = array('商户ID','商户名称','会员信息','订单号','订单金额','提现金额','现金支付比例%','支付方式','支付时间','操作平台');
            $data = [];
            foreach ($datalist as &$v) {
                $datekey = date('Ymd', $v['paytime']);
                if (empty($v['paynum'])) {
                    $v['paynum'] = '';
                }
                if (empty($v['paytype'])) {
                    $v['paytype'] = '';
                }
                $v['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '';
                if($v['platform'] =='mp'){
                    $v['platform'] = '公众号';
                }elseif ($v['platform'] =='wx'){
                    $v['platform'] = '小程序';
                }

                $data[] = [
                    ' '.$v['bid'],
                    $v['name'],
                    $v['nickname'].'(ID:'.$v['mid'].')',
                    $v['ordernum'],
                    $v['totalprice'],
                    $v['money'],
                    $v['withdrawfee_cash_rate'].'%',
                    $v['paytype'],
                    $v['paytime'],
                    $v['platform']
                ];
            }
            //var_dump(Db::name('water_happyti_order')->getLastSql());

            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
            $this->export_excel($title,$data);
        }
    }

    public function withdrawloginvoice(){
        if(getcustom('business_withdraw_invoice_mobile')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'business_withdrawlog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'business_withdrawlog.id desc';
                }
                $where = [];
                $where[] = ['business_withdrawlog.aid','=',aid];
                if(bid != 0){
                    $where[] = ['business_withdrawlog.bid','=',bid];
                }else{
                    if(input('param.bid')) $where[] = ['business_withdrawlog.bid','=',trim(input('param.bid'))];
                }
                if(input('id')){
                    $where[] = ['business_withdrawlog.id','=',input('id')];
                }

                if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['business_withdrawlog.status','=',input('param.status')];
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['business_withdrawlog.createtime','>=',strtotime($ctime[0])];
                    $where[] = ['business_withdrawlog.createtime','<',strtotime($ctime[1]) + 86400];
                }
                $count = 0 + Db::name('business_withdrawlog_invoice')->alias('business_withdrawlog')->field('business.mid,business.name,business_withdrawlog.*')->join('business business','business.id=business_withdrawlog.bid')->where($where)->count();
                $data = Db::name('business_withdrawlog_invoice')->alias('business_withdrawlog')->field('business.mid,business.name,business_withdrawlog.*')->join('business business','business.id=business_withdrawlog.bid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                foreach($data as $k=>$v){
                    $data[$k]['zdzq'] = date('Y/m/d',$v['start_time']).'-'. date('Y/m/d',$v['end_time']);
                }
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }
    }

    public function withdrawloginvoiceexcel(){

        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
        if(input('param.field') && input('param.order')){
            $order = 'business_withdrawlog.'.input('param.field').' '.input('param.order');
        }else{
            $order = 'business_withdrawlog.id desc';
        }
        $where = [];
        $where[] = ['business_withdrawlog.aid','=',aid];
        if(bid != 0){
            $where[] = ['business_withdrawlog.bid','=',bid];
        }else{
            if(input('param.bid')) $where[] = ['business_withdrawlog.bid','=',trim(input('param.bid'))];
        }
        if(input('id')){
            $where[] = ['business_withdrawlog.id','=',input('id')];
        }

        if(input('param.name')) $where[] = ['business.name','like','%'.trim(input('param.name')).'%'];
        if(input('?param.status') && input('param.status')!=='') $where[] = ['business_withdrawlog.status','=',input('param.status')];
        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['business_withdrawlog.createtime','>=',strtotime($ctime[0])];
            $where[] = ['business_withdrawlog.createtime','<',strtotime($ctime[1]) + 86400];
        }
        $count = 0 + Db::name('business_withdrawlog_invoice')->alias('business_withdrawlog')->field('business.mid,business.name,business_withdrawlog.*')->join('business business','business.id=business_withdrawlog.bid')->where($where)->count();
        $list = Db::name('business_withdrawlog_invoice')->alias('business_withdrawlog')->field('business.mid,business.name,business_withdrawlog.*')->join('business business','business.id=business_withdrawlog.bid')->where($where)->page($page,$limit)->order($order)->select()->toArray();

        $title = array();
        $title[] = '商户ID';
        $title[] = '商户名称';
        $title[] = '业务单号';
        $title[] = '账单金额';
        $title[] = '包含账单数量';
        $title[] = '账单周期';
        $title[] = '开票状态';
        $title[] = '备注';
        $title[] = '创建时间';
        $title[] = '发票信息';
        $data = array();
        foreach($list as $v){

            $zdzq = date('Y/m/d',$v['start_time']).'-'. date('Y/m/d',$v['end_time']);
            $invoice_pics = explode(',',$v['invoice_pics']);
            $tdata = array();
            $tdata[] = $v['bid'];
            $tdata[] = $v['name'];
            $tdata[] = $v['ordernum'];
            $tdata[] = $v['money'];
            $tdata[] = $v['num'];
            $tdata[] = $zdzq;
            if($v['status']==0){
                $st = '账单待确认';
            }elseif($v['status']==1){
                $st = '待提交发票';
            }elseif($v['status']==2){
                $st = '已开票';
            }elseif($v['status']==3){
                $st = '待审核';
            }elseif($v['status']==4){
                $st = '已驳回';
            }else{
                $st = '';
            }
            $tdata[] = $st;
            $tdata[] = $v['rejection'] ?? '';
            $tdata[] = date('Y/m/d H:i:s',$v['createtime']);
            $tdata[] = $v['invoice_pics'];
            $data[] = $tdata;
        }
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
        $this->export_excel($title,$data);
        exit;
    }

    public function withdrawloginvoiceSetcheckst(){
        if(getcustom('business_withdraw_invoice_mobile')){
            $id = input('param.id');
            $rejection = input('param.reason');
            $st = input('param.st');

            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['id','=',$id];

            if(!$info = Db::name('business_withdrawlog_invoice')->where($where)->find()){
                return json(['status'=>0,'msg'=>'数据不存在']);
            }

            if($info['status'] != 3){
                return json(['status'=>0,'msg'=>'只可审核待审核发票']);
            }

            Db::name('business_withdrawlog_invoice')->where('aid',aid)->where('id',$id)->update(['status'=>$st,'rejection'=>$rejection ?? '']);

            if($st == 2){
                Db::name('business_withdrawlog')->where('aid',aid)->where('withdrawlog_invoice_id',$info['id'])->update([
                    'withdrawlog_invoice_status' => 2,
                ]);
            }

            \app\common\System::plog('后台审核提现发票'.implode(',',$id));
            return json(['status'=>1,'msg'=>'操作成功']);
        }
    }
}

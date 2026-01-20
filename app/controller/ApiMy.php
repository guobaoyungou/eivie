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
use think\Exception;
use think\facade\Db;
use think\facade\Log;
use pay\wechatpay\WxPayV3;

class ApiMy extends ApiCommon
{
	public function initialize(){
		parent::initialize();
        if(!in_array(request()->action(),['usercenter'])){
            //个人中心支持未登录查看
            $this->checklogin();
        }
	}
	public function getWebinfo()
	{
		$field = 'name,logo,address';
		$rs = Db::name('admin_set')->where('aid',aid)->field($field)->find();
		if($rs){
			return $this->json($rs);
		}else{
			return $this->json(['status'=>0,'msg'=>'页面不存在']);
		}
	}
	public function usercenter(){
        //注意区分登录和未登录两种状态的查询和返回
        if(mid){
            \app\custom\HuiDong::syncMemberSingle(aid,mid);

            if(getcustom('plug_tengrui')){
                //全部同步认证
                $tengrui = new \app\custom\TengRui(aid,mid);
                $tengrui->tb_dan_member($this->member['mpopenid']);
            }
        }
        if(getcustom('member_blocked')){
            if($this->member && $this->member['is_blocked'] == 1){
                $blockedMsg = '账号已被拉黑，请联系管理员处理！';
                if($this->sysset['member_blocked_text']){
                    $blockedMsg = $this->sysset['member_blocked_text'];
                }
                return $this->json(['status'=>-4,'msg'=>$blockedMsg,'url'=>'/pages/index/login']);
            }
        }
        $pageid = input('param.id/d');
        $where = [];
        $where[] = ['aid','=',aid];
        if(!$pageid){
            $where[] = ['ishome','=',2];
        }else{
            $where[] = ['id','=',$pageid];
        }
		$pagedata = Db::name('designerpage')->where($where)->find();
		if($pagedata){
			$pageinfo = json_decode($pagedata['pageinfo'],true);
			$pagecontent = json_decode(\app\common\System::initpagecontent($pagedata['content'],aid,mid,platform),true);
			$rdata = [];
			$rdata['status'] = 1;
			$rdata['msg'] = '查询成功';
			$rdata['pageinfo'] = $pageinfo[0]['params'];
			$rdata['pagecontent'] = $pagecontent;
			$rdata['copyright'] = Db::name('admin')->where('id',aid)->value('copyright');
			if(getcustom('copyright_link')){
				$rdata['copyright_link'] = Db::name('admin')->where('id',aid)->value('copyright_link');
			}
			if(getcustom('copyright_type')){
				$rdata['copyrighttype'] = $this->admin['copyrighttype']??0;
				if($rdata['copyrighttype'] == 1){
					$rdata['copyright'] = $this->admin['copyright2'];
				}
			}
			$rdata['showthqrcode'] = false;
			$rdata['thqrcode'] = '';
			$rdata['isapplymendian'] = 0;
            if(getcustom('task_banner') && $this->mid){
                $task_banner_set = Db::name('task_banner_set')->where('aid',aid)->find();
                $rdata['rewardedvideoad'] = $task_banner_set['rewardedvideoad'];
            }
			if(getcustom('mendian_upgrade') && $this->mid){
				$mendian_upgrade_status = Db::name('admin')->where('id',aid)->value('mendian_upgrade_status');
                $mendian_sysset = Db::name('mendian_sysset')->where('aid',aid)->find();
				if($mendian_upgrade_status==1){
					$field = platform.'qrcode';
					$mendian =  Db::name('mendian_memberqrcode')->where('mid',mid)->where('aid',aid)->find();

					if(!$mendian || !$mendian[$field]){
                        if(platform=='wx'){
                            $wxthqrcode = \app\common\Wechat::getQRCode(aid,platform,'pagesA/mendiancenter/hxorderlist',['mid'=>mid]);
                            if($wxthqrcode['msg'] && $wxthqrcode['status']===0){
                                return $this->json(['status'=>0, 'msg'=>$wxthqrcode]);
                            }else{
                                $wxthqrcode = $wxthqrcode['url'];
                            }
                        }elseif(platform=='aliapy'){
                            $rs = \app\common\Alipay::getQRCode(aid,'pagesA/mendiancenter/hxorderlist?type=shop&mid='.mid);
                            if($rs['status'] == 0){
                                $rdata['status'] = 0;
                                $rdata['msg'] = $rs['msg'] ?? '获取失败';
                            }else{
                                $wxthqrcode = $rs['url'];
                            }
                        }else{//h5 mp
                            $wxthqrcode = createqrcode(m_url('pagesA/mendiancenter/hxorderlist?type=shop&mid='.mid));
                        }

                        if(in_array(platform,['wx','h5','mp','alipay'])){
                            if(!$mendian){
                                $mdata = [];
                                $mdata['aid'] = aid;
                                $mdata['mid'] = mid;
                                $mdata[$field] = $wxthqrcode;
                                Db::name('mendian_memberqrcode')->insert($mdata);
                            }else{
                                Db::name('mendian_memberqrcode')->where('mid',mid)->where('aid',aid)->update([$field=>$wxthqrcode]);
                            }
                        }
						
					}else{
						$wxthqrcode = $mendian[$field];
					}
					$mendian = Db::name('mendian')->where('aid',aid)->where('mid',mid)->find();
					if($mendian){
						$rdata['isapplymendian'] = 1;
					}else{
						$rdata['isapplymendian'] = 2;
					}

                    if($mendian_sysset['hx_qrcode_show'] == 1){
                        $rdata['thqrcode'] = $wxthqrcode;
                        $rdata['showthqrcode'] = true;
                    }

					if($this->member['mdid']){
						$mendiantel = Db::name('mendian')->where('aid',aid)->where('id',$this->member['mdid'])->value('tel');
						$rdata['mendiantel'] = $mendiantel;
					}
				}
			}
            $greenscore_hb = [
                'show_hb' => 0,
                'hbmoney' => 0,
                'hbtext' => '',
                'log_id' => 0
            ];
            if(getcustom('greenscore_max') && $this->mid){
                $consumer_set = Db::name('consumer_set')->where('aid',aid)->find();
                $hbstatus = $consumer_set['hbstatus'];
                $show_greenscore_hb = Db::name('greenscore_hb_log')->where('mid',mid)
                    ->where('show_greenscore_hb',date('Ymd'))
                    ->order('id desc')
                    ->find();
                $show_hb = 0;//是否展示红包
                $add_value = 0;//绿色积分增值金额
                $log_id = 0;
                if($hbstatus && $this->member['green_score']>0 ){
                    //计算增值金额
                    if(!$show_greenscore_hb || $show_greenscore_hb['status']==0){
                        $show_hb = 1;
                    }
                    $last_price = Db::name('member_greenscore_log')
                        ->where('aid',aid)
                        ->where('value','>',0)
                        ->where('createtime','<',strtotime(date('Y-m-d')))
                        ->order('createtime','desc')
                        ->value('green_score_price');
                    if($last_price>0){
                        $add_price = bcsub($consumer_set['green_score_price'],$last_price,2);
                    }else{
                        $add_price = 0;
                    }

                    $add_value = bcmul($add_price,$this->member['green_score'],2);
                    if($add_value>0 && $show_hb==1){
                        if(!$show_greenscore_hb || $this->member['green_score']!=$show_greenscore_hb['green_score']){
                            //插入增值记录
                            $log = [];
                            $log['aid'] = aid;
                            $log['mid'] = mid;
                            $log['ordernum'] = date('ymdHis') .aid. rand(1000, 9999);
                            $log['hbmoney'] = $add_value;
                            $log['hbaccount'] = $consumer_set['hbaccount'];
                            $log['green_score'] = $this->member['green_score'];
                            $log['last_green_score_price'] = $last_price;
                            $log['green_score_price'] = $consumer_set['green_score_price'];
                            $log['createtime'] = time();
                            $log['show_greenscore_hb'] = date('Ymd');
                            $log_id = Db::name('greenscore_hb_log')->insertGetId($log);
                        }else{
                            $log_id = $show_greenscore_hb['id'];
                            $add_value = $show_greenscore_hb['hbmoney'];
                        }
                    }else{
                        $show_hb = 0;
                    }

                }
                $greenscore_hb = [
                    'show_hb' => $show_hb,
                    'hbmoney' => $add_value,
                    'hbtext' => $consumer_set['hbtext'],
                    'log_id' => $log_id
                ];
            }
            $rdata['greenscore_hb'] = $greenscore_hb;

            if(getcustom('extend_advertising')){
            	//查询此时间段的广告
                $rdata['advertising'] = \app\model\ApiIndexs::dealadvertising([],$this->admin,1,platform);
            }
			return $this->json($rdata);
		}else{
			return $this->json(['status'=>0,'msg'=>'页面不存在']);
		}
	}
	public function set(){
		$smsset = Db::name('admin_set_sms')->where('aid',aid)->find();
		if($smsset && $smsset['status'] == 1 && $smsset['tmpl_smscode'] && $smsset['tmpl_smscode_st']==1){
			$needsms = 1;
		}else{
			$needsms = 0;
		}
		if(request()->isPost()){
			$formdata = input('post.info/a');
			if($needsms==1){
				if(md5($formdata['tel'].'-'.$formdata['code']) != cache(input('param.session_id').'_smscode') || cache(input('param.session_id').'_smscodetimes') > 5){
					return $this->json(['status'=>0,'msg'=>'短信验证码错误']);
				}
			}
			cache(input('param.session_id').'_smscode',null);
			cache(input('param.session_id').'_smscodetimes',null);
			
			$info = [];
			$info['realname'] = $formdata['realname'];
			$info['tel'] = $formdata['tel'];
			$info['usercard'] = $formdata['usercard'];
			$info['weixin'] = $formdata['weixin'];
			$info['aliaccount'] = $formdata['aliaccount'];
			$info['bankname'] = $formdata['bankname'];
			$info['bankcarduser'] = $formdata['bankcarduser'];
			$info['bankcardnum'] = $formdata['bankcardnum'];
			$info['sex'] = $formdata['sex'];
			if($formdata['province_city']){
				$province_city = explode(' ',$formdata['province_city']);
				if($province_city){
					$info['province'] = $province_city[0];
					$info['city'] = $province_city[1];
				}
			}
			$info['birthday'] = $formdata['birthday'];
			Db::name('member')->where('id',mid)->update($info);

			//若头像、昵称、姓名、手机号发生改动，则删除他的会员海报
			if($info['realname'] != $this->member['realname'] || $info['tel'] != $this->member['tel']){
				Db::name('member_poster')->where('mid',mid)->where('aid',aid)->delete();
			}
			return $this->json(['status'=>1,'msg'=>'修改成功']);
		}
		$field = 'id,headimg,nickname,realname,tel,usercard,weixin,aliaccount,aliaccountname,bankname,bankaddress,bankcarduser,bankcardnum,sex,province,city,birthday';
        if(getcustom('restaurant_finance_notice_switch')){
            $field .=',is_receive_finance_tmpl,is_receive_finance_sms';
        }
        if(getcustom('register_fields')){
            $field .= ',form_record_id';
        }
        if(getcustom('shop_label')){
            $field .= ',labelid';
        }
        if(getcustom('withdraw_paycode')){
            $field .= ',wxpaycode,alipaycode';
        }

		$userinfo = Db::name('member')->where('id',mid)->field($field)->find();
		if($userinfo['realname'] == null) $userinfo['realname'] = '';
		if($userinfo['tel'] == null) $userinfo['tel'] = '';
		if($userinfo['usercard'] == null) $userinfo['usercard'] = '';
		if($userinfo['weixin'] == null) $userinfo['weixin'] = '';
		if($userinfo['aliaccount'] == null) $userinfo['aliaccount'] = '';
		if($userinfo['aliaccountname'] == null) $userinfo['aliaccountname'] = '';
		if($userinfo['bankname'] == null) $userinfo['bankname'] = '';
		if($userinfo['bankcarduser'] == null) $userinfo['bankcarduser'] = '';
		if($userinfo['bankcardnum'] == null) $userinfo['bankcardnum'] = '';
		if($userinfo['bankaddress'] == null) $userinfo['bankaddress'] = '';
		if($userinfo['sex'] == null) $userinfo['sex'] = '';
		if($userinfo['province'] == null) $userinfo['province'] = '';
		if($userinfo['city'] == null) $userinfo['city'] = '';
		if($userinfo['birthday'] == null) $userinfo['birthday'] = '';
		if($this->member['pwd']==''){
			$userinfo['haspwd'] = 0;
		}else{
			$userinfo['haspwd'] = 1;
		}
        if($this->member['paypwd']==''){
            $userinfo['haspaypwd'] = 0;
        }else{
            $userinfo['haspaypwd'] = 1;
        }

		$rdata = [];
		$rdata['needsms'] = $needsms;

		$userinfo['set_alipay'] = false;
		if(getcustom('alipay_auto_transfer')){
			$userinfo['set_alipay'] = true;
		}
		$userinfo['set_bank']   = false;
        $userinfo['set_receive_notice']   = false;
        if(getcustom('restaurant_finance_notice_switch')){
            $userinfo['set_receive_notice']   = true;  
        }
        if(getcustom('shop_label')){
        	$userinfo['haslabel'] = true;
            $labelnames = '';
            if(!empty($userinfo['labelid'])){
                $labels = Db::name('shop_label')->where('id','in',$userinfo['labelid'])->where('status',1)->where('aid',aid)->order('sort desc,id desc')->column('name');
                if($labels){
                    $labelnames = implode(' ',$labels);
                }
            }
            $userinfo['labelnames'] = $labelnames;
        }
		if(getcustom('business_bind_show_page')){
			//绑定多商户显示多商户首页
			$sysset = Db::name('admin_set')->field('mode,loc_business_show_type')->where('aid',aid)->find();
			if($sysset['mode'] == 1 && $sysset['loc_business_show_type'] == 2){
				if($this->member && $this->member['bind_business']){
					$userinfo['bind_business'] = $this->member['bind_business'];
				}
			}
		}
		$rdata['userinfo'] = $userinfo;

		if(getcustom('member_set')){
			//查询设置
			$otherdata = '';
			$set = Db::name('member_set')->where('aid',aid)->find();
			if($set){
				$setcontent = json_decode($set['content'],true);
				if($setcontent){
					foreach($setcontent as $sk=>&$sv){
						$sv['content'] = '';
						//查询用户设置
						$log = Db::name('member_set_log')->where('mid',mid)->where('formid',$set['id'])->find();
						if($log){
							$sv['content'] = $log['form'.$sk];
						}
					}
					unset($sv);
				}
				$otherdata = $setcontent;
			}
			$rdata['otherdata'] = $otherdata;
			$rdata['member_edit_switch'] = $set['member_edit_switch'];
		}

        //注册自定义
        $register_forms = [];
        if(getcustom('register_fields')){
            //系统设置参数
            $sys_forms = Db::name("register_form")->where("aid", aid)->find();
            if($sys_forms){
                $content = json_decode($sys_forms['content'], true);
                if($userinfo['form_record_id']>0){
                    $register_record = Db::name('register_form_record')->where('id', $userinfo['form_record_id'])->find();
                    if($register_record){
                        foreach ($content as $k=>$item) {
                            if(!in_array($item['key'], ['realname','sex', 'birthday'])){
                                $item['content'] = $register_record['form'.$k]??'';
                                $register_forms[$k] = $item;
                            }
                        }
                    }
                }
            }
        }
        $rdata['register_forms'] = $register_forms;

		return $this->json($rdata);
	}
	public function setfield(){
		$headimg = input('post.headimg');
		$nickname = trim(input('post.nickname'));
        $mid = mid;

        if(getcustom('team_update_member_info') && input('post.mid/d')){
            //判断当前等级是否用于修改团队成员信息权限
            $levelAuth = Db::name('member_level')
                ->where('aid',aid)
                ->where('id',$this->member['levelid'])
                ->value('team_update_member_info');
            if(!$levelAuth){
                return json(['status'=>0,'msg'=>'您没有权限修改该用户信息']);
            }
            //修改团队成员信息
            $mid = input('post.mid/d');
        }

		// 小程序安全检测
		if(platform == 'wx' ){
			if(!empty($nickname) || !empty($headimg)){
				$openid = Db::name('member')->where('aid',aid)->where('id',$mid)->value('wxopenid');
				if($openid && !empty($nickname)){
					// 昵称
					$res = \app\common\Wechat::checkMessageSafe($nickname,$openid,aid);
					if($res['errcode'] == 0){
						if(!empty($res['result']['suggest']) && $res['result']['suggest'] != 'pass'){
							return  json(['status'=>0,'msg'=>'输入文字内容违规，请重新填写']);
						}
					}else{
						return json(['status'=>0,'msg'=>$res['errmsg']]);
					}
				}
				// 头像
				if($openid && !empty($headimg)){
					$res = \app\common\Wechat::checkImageSafe($headimg,$openid,aid);
					if($res['errcode'] == 0){
						if(!empty($res['trace_id'])){
							$data_wx = [
								'aid' => aid,
								'mid' => $mid,
								'trace_id'=>$res['trace_id'],
								'headimg'=>$headimg,
								'createtime'=>time()
							];
		
							Db::name('member_wximage_log')->insert($data_wx);
						}
					}else{
						return json(['status'=>0,'msg'=>$res['errmsg']]);
					}
				}
			}
		}

		$realname = input('post.realname');
        $usercard = input('post.usercard');
		$sex = input('post.sex');
		$birthday = input('post.birthday');
		$weixin = input('post.weixin');
		$aliaccount = input('post.aliaccount');
		$aliaccountname = input('post.aliaccountname');
		$bankname = input('post.bankname');
		$bankcarduser = input('post.bankcarduser');
		$bankcardnum = input('post.bankcardnum');
		$bankaddress = input('post.bankaddress');
        //验证身份证号唯一性
        if(getcustom('usercard_unique')){
            if($usercard){
                $exit = Db::name('member')->where('aid',aid)->where('usercard',$usercard)->where('id','<>',$mid)->find();
                if($exit){
                    return $this->json(['status'=>0,'msg'=>'身份证号已存在']);
                }
            }
        }
		$data = [];
		if($headimg) $data['headimg'] = $headimg;
		if($nickname) $data['nickname'] = $nickname;
		if($realname) $data['realname'] = $realname;
        if($usercard) {
            if(!checkIdCard($usercard)){
                return $this->json(['status'=>0,'msg'=>'请输入正确的身份证号']);
            }
            $data['usercard'] = $usercard;
        }
		if($sex) $data['sex'] = $sex;
		if($birthday) $data['birthday'] = $birthday;
		if($weixin) $data['weixin'] = $weixin;
		if($aliaccount) $data['aliaccount'] = $aliaccount;
		if($aliaccountname) $data['aliaccountname'] = $aliaccountname;
		if($bankname !== null) $data['bankname'] = $bankname;
		if($bankcarduser !== null) $data['bankcarduser'] = $bankcarduser;
		if($bankcardnum !== null) $data['bankcardnum'] = $bankcardnum;
		if($bankaddress !== null) $data['bankaddress'] = $bankaddress;
		if(getcustom('withdraw_paycode')){
            if(input('post.wxpaycode') !== null) $data['wxpaycode']   = input('post.wxpaycode');
			if(input('post.alipaycode') !== null) $data['alipaycode'] = input('post.alipaycode');
        }
        if(getcustom('withdraw_custom')){
            //自定义提现方式
            $customaccount = input('post.customaccount');
            $customaccountname = input('post.customaccountname');
            $customtel = input('post.customtel');

            if($customaccount !== null) $data['customaccount'] = $customaccount;
            if($customaccountname !== null) $data['customaccountname'] = $customaccountname;
            if($customtel !== null) $data['customtel'] = $customtel;
        }
		Db::name('member')->where('id',$mid)->update($data);
		if($headimg || $nickname || $realname ) {
		    //删除海报
            Db::name('member_poster')->where('mid',$mid)->where('aid',aid)->delete();
        }
		$member = Db::name('member')->where('id',$mid)->find();
        if(getcustom('register_fields_extend') && $member['form_record_id']>0){
            $formRecord = Db::name('register_form')->where('aid',aid)->find();
            $formContent = json_decode($formRecord['content'],true);
            foreach ($formContent as $k=>$item){
                if($realname && $item['key'] == 'realname'){
                    Db::name('register_form_record')->where('aid',aid)->where('id',$member['form_record_id'])->update(['form'.$k => $realname]);
                }
                if($birthday && $item['key'] == 'birthday'){
                    Db::name('register_form_record')->where('aid',aid)->where('id',$member['form_record_id'])->update(['form'.$k => $birthday]);
                }
                if($sex && $item['key'] == 'sex'){
                    Db::name('register_form_record')->where('aid',aid)->where('id',$member['form_record_id'])->update(['form'.$k => $sex]);
                }
            }
        }
		if($member['is_wanshan_score'] == 0 && $member['realname'] && $member['tel'] && $member['sex'] && $member['birthday']){
			Db::name('member')->where('id',$mid)->update(['is_wanshan_score'=>1]);
			$set = Db::name('register_giveset')->where('aid',aid)->find();
			if($set['status']==1 && $set['wanshan_score']>0){
				$date = date('Y-m-d H:i:s');
				if($date >= $set['starttime'] && $date < $set['endtime']){
					\app\common\Member::addscore(aid,$mid,$set['wanshan_score'],'完善资料赠送');
				}
			}
		}

        if(getcustom('member_sync_xiaoe')){//同步小鹅通
            \app\custom\Xiaoe::updateUser(aid,$mid);
        }
		return $this->json(['status'=>1,'msg'=>'修改成功']);
	}

    public function setHuifuField(){
        if(getcustom('pay_huifu')){
            if(request()->isPost()){
//                dd(input('post.formdata'));
                $realname = input('post.formdata.realname');
                $usercard = input('post.formdata.usercard');
                $bankname = input('post.formdata.bankname');
//                $bankcarduser = input('post.formdata.bankcarduser');
                $bankcardnum = input('post.formdata.bankcardnum');
                $bankaddress = input('post.formdata.bankaddress');
                $data = [];
                if($realname) {
                    $data['realname'] = $realname;
                }else{
                    return $this->json(['status'=>0,'msg'=>'请输入姓名']);
                }
                if($usercard) {
                    $data['usercard'] = $usercard;
                }else{
                    return $this->json(['status'=>0,'msg'=>'请输入身份证号']);
                }
                if(input('post.formdata.usercard_begin_date')) {
                    $data['usercard_begin_date'] = input('post.formdata.usercard_begin_date');
                }else{
                    return $this->json(['status'=>0,'msg'=>'请选择身份证开始日期']);
                }
//                dd(input('post.formdata.usercard_date_type.0'));
                $usercard_date_type = input('post.formdata.usercard_date_type.0')?:input('post.formdata.usercard_date_type');
                if(input('post.formdata.usercard_end_date') || $usercard_date_type == 1) {
                    if($usercard_date_type == 1){
                        $data['usercard_end_date'] = NULL;//空字符串报错
                        $data['usercard_date_type'] = 1;
                    }else{
                        $data['usercard_date_type'] = 0;
                        $data['usercard_end_date'] = input('post.formdata.usercard_end_date');
                    }
                }else{
                    return $this->json(['status'=>0,'msg'=>'请选择身份证结束日期']);
                }
                if($bankname) {
                    $data['bankname'] = $bankname;
                }else{
                    return $this->json(['status'=>0,'msg'=>'请选择开户行']);
                }

                $data['bankcarduser'] = $realname;
//                if($bankcarduser) {
//                    $data['bankcarduser'] = $bankcarduser;
//                }else{
//                    return $this->json(['status'=>0,'msg'=>'请输入持卡人姓名']);
//                }
                if($bankcardnum) {
                    $data['bankcardnum'] = $bankcardnum;
                }else{
                    return $this->json(['status'=>0,'msg'=>'请输入银行卡号']);
                }
                if($bankaddress) $data['bankaddress'] = $bankaddress;
                if(input('post.formdata.areaval')) {
                    $data['bank_province'] = input('post.formdata.areaname.0');
                    $data['bank_province_code'] = input('post.formdata.areaval.0');
                    $data['bank_city'] = input('post.formdata.areaname.1');
                    $data['bank_city_code'] = input('post.formdata.areaval.1');
                }else{
                    return $this->json(['status'=>0,'msg'=>'请选择银行所属地区']);
                }
                if(input('post.formdata.tel')) {
                    $data['tel'] = input('post.formdata.tel');
                }else{
                    return $this->json(['status'=>0,'msg'=>'请输入手机号']);
                }
                if($this->member['huifu_id']){
                    //用户进件 修改信息
                    $huifu = new \app\custom\Huifu([],aid,0,mid);
                    $rs = $huifu->userBasicdataIndvModify($data,$this->member['huifu_id']);
                    if($rs['status'] != 1 && empty($rs['resp']['huifu_id'])){
                        return $this->json($rs);
                    }
                    $data['huifu_id'] = $rs['resp']['huifu_id'];
                    Db::name('member')->where('id',mid)->update($data);
                    if($this->member['huifu_token_no']){
                        //已经入驻成功走修改接口
                        $rs2 = $huifu->userBusiOpenModify($rs['resp']['huifu_id'],$data);
                    }else{
                        //可能上一次用户进件成功了但是没有入驻成功
                        $rs2 = $huifu->userBusiOpen($rs['resp']['huifu_id'],$data);
                    }
                    if($rs2['status'] != 1){
                        return $this->json($rs2);
                    }

                    Db::name('member')->where('id',mid)->update(['huifu_token_no'=>$rs2['resp']['token_no']]);
                }else{
                    //用户进件
                    $huifu = new \app\custom\Huifu([],aid,0,mid);
                    $rs = $huifu->userBasicdataIndv($data);
                    if($rs['status'] != 1 && empty($rs['resp']['huifu_id'])){
                        return $this->json($rs);
                    }
                    $data['huifu_id'] = $rs['resp']['huifu_id'];
                    Db::name('member')->where('id',mid)->update($data);
                    $rs2 = $huifu->userBusiOpen($rs['resp']['huifu_id'],$data);
                    if($rs2['status'] != 1){
                        return $this->json($rs2);
                    }
                    Db::name('member')->where('id',mid)->update(['huifu_token_no'=>$rs2['resp']['token_no']]);
                }

                return $this->json(['status'=>1,'msg'=>'修改成功']);
            }else{
                $userinfo = Db::name('member')->where('id',mid)->field('huifu_id,usercard_begin_date,usercard_end_date,usercard_date_type,bank_province,bank_province_code,bank_city,bank_city_code,id,headimg,nickname,realname,tel,usercard,bankname,bankaddress,bankcarduser,bankcardnum,sex,province,city,birthday')->find();
                if($userinfo['realname'] == null) $userinfo['realname'] = '';
                if($userinfo['tel'] == null) $userinfo['tel'] = '';
                if($userinfo['usercard'] == null) $userinfo['usercard'] = '';
                if($userinfo['bankname'] == null) $userinfo['bankname'] = '';
                if($userinfo['bankcarduser'] == null) $userinfo['bankcarduser'] = '';
                if($userinfo['bankcardnum'] == null) $userinfo['bankcardnum'] = '';
                if($userinfo['bankaddress'] == null) $userinfo['bankaddress'] = '';
                if($userinfo['usercard_begin_date'] == null) $userinfo['usercard_begin_date'] = '';
                if($userinfo['usercard_end_date'] == null) $userinfo['usercard_end_date'] = '';

                $rdata = [];
                $rdata['status'] = 1;
                $rdata['userinfo'] = $userinfo;

                return $this->json($rdata);
            }
        }

    }
	//编辑手机号
	public function settel(){
		$smsset = Db::name('admin_set_sms')->where('aid',aid)->find();
		if($smsset && $smsset['status'] == 1 && $smsset['tmpl_smscode'] && $smsset['tmpl_smscode_st']==1){
			$needsms = true;
		}else{
			$needsms = false;
		}
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['needsms'] = $needsms;
		return $this->json($rdata);
	}
	public function settelsub(){
		$tel = input('param.tel');
		$smscode = input('param.smscode');
		$smsset = Db::name('admin_set_sms')->where('aid',aid)->find();
		if($smsset && $smsset['status'] == 1 && $smsset['tmpl_smscode'] && $smsset['tmpl_smscode_st']==1){
			$needsms = true;
		}else{
			$needsms = false;
		}
        if(!checkTel(aid,$tel)){
            return $this->json(['status'=>0, 'msg'=>'请填写正确的手机号']);
        }
		if($needsms && md5($tel.'-'.$smscode) != cache($this->sessionid.'_smscode') || cache($this->sessionid.'_smscodetimes')>5){
			cache($this->sessionid.'_smscodetimes',cache($this->sessionid.'_smscodetimes')+1);
			return $this->json(['status'=>0,'msg'=>'短信验证码错误']);
		}
		$member = Db::name('member')->where('aid',aid)->where('id','<>',mid)->where('tel',$tel)->find();
		if($member){
			return $this->json(['status'=>0,'msg'=>'该手机号已绑定其他账号']);
		}

		cache($this->sessionid.'_smscode',null);
		cache($this->sessionid.'_smscodetimes',null);
		Db::name('member')->where('id',mid)->update(['tel'=>$tel]);
        if(getcustom('member_up_binding_tel')){
            \app\common\Member::uplv(aid,mid);
        }

		//若头像、昵称、姓名、手机号发生改动，则删除他的会员海报
		if($tel != $this->member['tel']){
			Db::name('member_poster')->where('mid',mid)->where('aid',aid)->delete();
		}
		return $this->json(['status'=>1,'msg'=>'修改成功']);

	}
	//修改密码
	public function setpwd(){
		if($this->member['pwd']==''){
			$haspwd = 0;
		}else{
			$haspwd = 1;
		}
		if(request()->isPost()){
			$pwd = input('post.pwd');
			$oldpwd = input('post.oldpwd');
			if($this->member['pwd'] && $this->member['pwd'] != md5($oldpwd)){
				return $this->json(['status'=>0,'msg'=>'原密码输入错误']);
			}
			Db::name('member')->where('aid',aid)->where('id',mid)->update(['pwd'=>md5($pwd)]);
			return $this->json(['status'=>1,'msg'=>'修改成功']);
		}
		$rdata = [];
		$rdata['haspwd'] = $haspwd;
		return $this->json($rdata);
	}
	//支付密码设置
	public function paypwd(){
		if($this->member['paypwd']==''){
			$haspwd = 0;
		}else{
			$haspwd = 1;
		}
		if(request()->isPost()){
			$paypwd = input('post.paypwd');
			$oldpaypwd = input('post.oldpaypwd');
			$data_u = [];
			//验证原支付密码
			if($this->member['paypwd']){
			   if(!\app\common\Member::checkPayPwd($this->member,$oldpaypwd )){
                   return $this->json(['status'=>0,'msg'=>'原密码输入错误']);
               }
            }
			//设置过MD5加密的
			if($this->member['paypwd_rand']){
                $paypwd = md5($paypwd.$this->member['paypwd_rand']);
                $data_u['paypwd'] = $paypwd;
            }else{
			    //未设置过MD5加密的自动生成MD5随机数
			    $rand_str = make_rand_code(2,4);
			    $paypwd = md5($paypwd.$rand_str);
                $data_u['paypwd_rand'] = $rand_str;
                $data_u['paypwd'] = $paypwd;
            }
			Db::name('member')->where('aid',aid)->where('id',mid)->update($data_u);
			return $this->json(['status'=>1,'msg'=>'修改成功']);
		}
		$rdata = [];
		$rdata['haspwd'] = $haspwd;
		return $this->json($rdata);
	}
    //提现
	public function withdraw(){
		$field = 'withdraw_autotransfer,withdraw,withdrawmin,withdrawfee,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,withdraw_desc,withdrawmax,day_withdraw_num,wx_transfer_type';
        if(getcustom('comwithdrawdate')){
            $field .= ',comwithdrawdate_money';
        }
		if(getcustom('alipay_auto_transfer')){
			$field .= ',ali_withdraw_autotransfer';
		}
        if(getcustom('pay_adapay')){
            $field .= ',withdraw_adapay';
        }
        if(getcustom('transfer_farsion')){
            $field .= ',withdraw_bankcard_xiaoetong,withdraw_aliaccount_xiaoetong';
        }
        if(getcustom('pay_huifu')){
            $field .= ',withdraw_huifu';
        }
		if(getcustom('money_withdraw_level_sxf')){
			$field .= ',withdrawfee_level';
		}
        if(getcustom('withdraw_mul')){
          $field .= ',withdrawmul';
        }
        if(getcustom('product_givetongzheng')){
            $field .= ',withdraw2tongzheng';
        }
        if(getcustom('extend_linghuoxin')){
            $field .= ',withdraw_aliaccount_linghuoxin,withdraw_bankcard_linghuoxin';
        }
        // if(getcustom('pay_allinpay')){
        //     $field .= ',withdraw_bankcard_allinpayYunst';
        // }
        if(getcustom('withdraw_paycode')){
            $field .= ',withdraw_paycode';
        }
        if(getcustom('money_commission_withdraw_fenxiao')){
            //手续费 分销
            $field .= ',money_withdrawfee_fenxiao,money_withdrawfee_commissiondata';
        }
        if(getcustom('withdraw_custom')){
            //自定义提现方式
            $field .= ',custom_status,custom_name';
        }
        if(getcustom('money_withdraw_week')){
            $field .= ',money_withdraw_week';
        }
        if(getcustom('withdraw_max_limit_text')){
            $field .= ',max_withdraw_limit_text';
        }
        if(getcustom('withdraw_wxpay_limit')){
            $field .= ',wxpay_withdraw_limit';
        }
		$set = Db::name('admin_set')->where('aid',aid)->field($field)->find();

        if($set['withdraw'] == 0){
            return $this->json(['status'=>0,'msg'=>t('余额').'提现功能未开启']);
        }

        if(getcustom('member_realname_verify')) {
            $realname_set = Db::name('member_realname_set')->where('aid', aid)->find();
            if ($realname_set['status'] == 1 && $realname_set['withdraw_status'] == 0 && $this->member['realname_status'] != 1){
                return $this->json(['status'=>-4,'msg'=>'未实名认证不可提现','url'=>'/pagesExt/my/setrealname']);
            }
        }
		if(getcustom('transfer_farsion') && ($set['withdraw_bankcard_xiaoetong'] == 1 || $set['withdraw_aliaccount_xiaoetong'] == 1)){
			$xetService = new  \app\common\Xiaoetong(aid);
			$res_sign = $xetService->getXiaoetongSigning(mid);
			if ($res_sign['status'] == 0){
                return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesA/my/withdrawXiaoetong']);
            }
		}

		if(getcustom('money_withdraw_level_sxf')){
			$withdrawfee_level = json_decode($set['withdrawfee_level'],true);
			$set['withdrawfee'] = $withdrawfee_level[$this->member['levelid']]['sxf'];
		}
        if(getcustom('lock_money')){
            if($this->member['lock_money']==1){
                $lock_tip = $this->sysset_custom['lock_money_tip']?:'您的'.t('余额').'已被冻结';
                return $this->json(['status'=>0,'msg'=>$lock_tip]);
            }
        }
		if(request()->isPost()){
			$post = input('post.');

			//验证今天提现了几次
            $nowtime = strtotime(date("Y-m-d",time()));//今日时间戳
            $daywithdrawnum   = 'daywithdrawnum'.mid.$nowtime;//会员今日时间参数
            $day_withdraw_num = cache($daywithdrawnum);//获取会员提现次数
            if($set['day_withdraw_num']<0){
                return $this->json(['status'=>0,'msg'=>'暂时不可提现']);
            }else if($set['day_withdraw_num']>0){
                if($day_withdraw_num && !empty($day_withdraw_num)){
                    $daynum = $day_withdraw_num+1;
                    if($daynum>$set['day_withdraw_num']){
                        return $this->json(['status'=>0,'msg'=>'今日申请提现次数已满，请明天继续申请提现']);
                    }
                }
            }
			if(getcustom('member_lock')){
				$field = 'lock_withdraw_givemoney';
				$userinfo = Db::name('member')->where('id',mid)->field($field)->find();
				if($userinfo['lock_withdraw_givemoney'] == 1){                
					return $this->json(['status'=>0,'msg'=>'账号已锁定，请联系管理员处理！']);
				}            
			}

            if(getcustom('comwithdrawdate') && $set['comwithdrawdate_money'] && $set['comwithdrawdate_money']!='0'){
                $comwithdrawdate = explode(',',$set['comwithdrawdate_money']);
                $indate = false;
                $nowdata = date('d');
                foreach($comwithdrawdate as $date){
                    if($date == $nowdata || '0'.$date == $nowdata){
                        $indate = true;
                        break;
                    }
                }
                if(!$indate) return $this->json(['status'=>0,'msg'=>'不在可提现日期内']);
            }

            if(getcustom('money_withdraw_week')){
                //每周可提现日期
                $today_week = date('N');

                if(!empty($set['money_withdraw_week']) ){
                    $money_withdraw_week = explode(',',$set['money_withdraw_week']);
                    if(!in_array($today_week,$money_withdraw_week)){
                        return $this->json(['status'=>0,'msg'=>'不在可提现日期内']);
                    }
                }
            }
			if($post['paytype']=='支付宝'){
                if($set['withdraw_aliaccount'] == 0){
                    return $this->json(['status'=>0,'msg'=>'支付宝提现功能未开启']);
                }

                if(!$this->member['aliaccount'] || !$this->member['aliaccountname']){
                	return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                }
			}
			if(getcustom('transfer_farsion')){
				if($post['paytype']=='小额通支付宝'){
					if(!$this->member['aliaccount'] || !$this->member['aliaccountname']){
						return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
					}
				}
				if($post['paytype']=='小额通银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
					if($set['withdraw_bankcard'] == 0)
						return $this->json(['status'=>0,'msg'=>'银行卡提现功能未开启']);
                    return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
			}
            if(getcustom('extend_linghuoxin')){
                if($post['paytype']=='灵活薪支付宝' || $post['paytype']=='灵活薪银行卡'){
                    if($post['paytype']=='灵活薪支付宝'){
                        if($set['withdraw_aliaccount_linghuoxin'] != 1){
                            return $this->json(['status'=>0,'msg'=>'灵活薪支付宝提现功能未开启']);
                        }
                        if(empty($this->member['aliaccount']) || empty($this->member['aliaccountname'])){
                            return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                        }
                    }
                    if($post['paytype']=='灵活薪银行卡'){
                        if($set['withdraw_bankcard_linghuoxin'] != 1){
                            return $this->json(['status'=>0,'msg'=>'灵活薪银行卡提现功能未开启']);
                        }
                        if(empty($this->member['bankname']) || empty($this->member['bankcarduser'])|| empty($this->member['bankcardnum'])){
                            return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                        }
                    }
                    //查看是否签约
                    if(!empty($this->member['usercard'])){
                        $getchecksign = \app\custom\LinghuoxinCustom::getchecksign(aid,0,$this->member['usercard']);
                        if($getchecksign && $getchecksign['status'] == 1){
                        	Db::name('member_linghuoxin_signlog')->where('mid',mid)->where('usercard',$this->member['usercard'])->update(['status'=>$getchecksign['data']['status'],'updatetime'=>time()]);
                            if($getchecksign['data']['status'] == 0){
                                return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesB/my/linghuoxinsign']);
                            }else if($getchecksign['data']['status'] == 1){
                                return $this->json(['status'=>0,'msg'=>'已实名认证，等待签约中']);
                            }
                        }else{
                            //return $this->json($getchecksign);
                            return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesB/my/linghuoxinsign']);
                        }
                    }else{
                        return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesB/my/linghuoxinsign']);
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                if($post['paytype']=='通联支付银行卡'){
                    if($set['withdraw_bankcard_allinpayYunst'] != 1){
                        return $this->json(['status'=>0,'msg'=>'通联支付银行卡提现功能未开启']);
                    }
                    // if(empty($this->member['bankname']) || empty($this->member['bankcarduser'])|| empty($this->member['bankcardnum'])){
                    //     return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                    // }
                    //通联支付 云商通个人会员
                    $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                    if(!$yunstuser){
                        return $this->json(['status'=>-4,'msg'=>'未创建通联会员，请前去创建','url'=>'/pagesC/allinpay/yunstMember']);
                    }
                    if($yunstuser['signstatus'] == 2){
                    	return $this->json(['status'=>0,'msg'=>'提现签约申请中' ,'url'=>'/pagesC/allinpay/yunstMemberSign']);
                    }
                    if($yunstuser['signstatus'] != 3){
                    	return $this->json(['status'=>-4,'msg'=>'请先申请提现签约','url'=>'/pagesC/allinpay/yunstMemberSign']);
                    }
                }
            }
            if(getcustom('withdraw_paycode')){
                if($post['paytype']=='收款码'){
                    if(!$this->member['wxpaycode'] && !$this->member['alipaycode']){
                        return $this->json(['status'=>0,'msg'=>'请先设置一个收款码']);
                    }
                }
            }
            if(getcustom('withdraw_custom')){
                //自定义提现方式
                if($post['paytype'] == $set['custom_name']){
                    if($set['custom_status'] == 0){
                        return $this->json(['status'=>0,'msg'=>'自定义提现方式未开启']);
                    }

                    if(!$this->member['customaccountname'] || $this->member['customaccount'] == '' || $this->member['customtel'] == ''){
                        return $this->json(['status'=>0,'msg'=>'请先设置'.$set['custom_name'].'账户信息']);
                    }
                }
            }
			if($post['paytype']=='银行卡' &&  getcustom('yx_gift_pack')){
				$bank = Db::name('member_bank')->where('aid',aid)->where('mid',mid)->where('isdefault',1)->find();
				if(!$bank){
					return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
				$this->member['bankname'] = $bank['bankname'];
				$this->member['bankcarduser'] = $bank['bankcarduser'];
				$this->member['bankcardnum'] = $bank['bankcardnum'];
				$this->member['bankaddress'] = $bank['bankaddress'];
			}else{
				if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
					if($set['withdraw_bankcard'] == 0)
						return $this->json(['status'=>0,'msg'=>'银行卡提现功能未开启']);
					return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
			}
            if($post['paytype']=='银行卡' && $set['withdraw_huifu'] == 1 && ($this->member['realname']==''||$this->member['tel']==''||$this->member['usercard']==''||$this->member['huifu_id']==''||$this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
                return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
            }
            if(getcustom('pay_huifu')) {
                if ($post['paytype'] == '银行卡' && $set['withdraw_huifu'] == 1 && $this->member['huifu_token_no'] == '') {
                    return $this->json(['status' => 0, 'msg' => '请完善汇付进件资料']);
                }
            }
            if($post['paytype'] == '微信钱包' && $set['withdraw_weixin'] == 0){
                return $this->json(['status'=>0,'msg'=>'微信钱包提现功能未开启']);
            }

			$money = $post['money'];
            if(getcustom('withdraw_mul')){
                if($set['withdrawmul']>0 && !isMulInt($money, $set['withdrawmul'])){
                  return $this->json(['status'=>0,'msg'=>'提现金额必须为'.$set['withdrawmul'].'整数倍']);
                }
            }

			if($money<=0 || $money < $set['withdrawmin']){
				return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['withdrawmin']?$set['withdrawmin']:0)]);
			}
			//每次重新查，避免出现连续重复提交后余额没更新的情况
			$member_money = Db::name('member')->where('aid',aid)->where('id',mid)->value('money');
			if($money > $member_money){
				return $this->json(['status'=>0,'msg'=>'可提现'.t('余额').'不足']);
			}
            if(getcustom('withdraw_wxpay_limit')){
                if ($post['paytype'] == '微信钱包' && $set['wxpay_withdraw_limit'] > 0) {
                    $set['withdrawmax'] = $set['wxpay_withdraw_limit']; //微信提现 覆盖 提现最高金额
                }
            }
			if($set['withdrawmax']>0 && $money > $set['withdrawmax']){
                if(getcustom('withdraw_max_limit_text') && $set['max_withdraw_limit_text']){
                    return $this->json(['status'=>-4,'msg'=>$set['max_withdraw_limit_text']]);
                }
				return $this->json(['status'=>0,'msg'=>'提现金额过大，单笔'.t('余额').'提现最高金额为'.$set['withdrawmax'].'元']);
			}

			//验证小数点后两位
            $money_arr = explode('.',$money);
            if($money_arr && $money_arr[1]){
                $dot_len = strlen($money_arr[1]);
                if($dot_len>2){
                    return $this->json(['status'=>0,'msg'=>'提现金额最小位数为小数点后两位']);
                }
            }

			$ordernum = date('ymdHis').aid.rand(1000,9999);
			$record['aid'] = aid;
			$record['mid'] = mid;
			$record['createtime']= time();
            $real_money = $money*(1-$set['withdrawfee']*0.01);
			if($real_money <= 0) {
                return $this->json(['status'=>0,'msg'=>'提现金额有误']);
            }
            //提现到账通证
            if(getcustom('product_givetongzheng')){
                $withdraw2tongzheng = $set['withdraw2tongzheng'];
                $tongzheng_num = bcmul($money,$withdraw2tongzheng/100,3);
                $real_money = bcsub($real_money,$tongzheng_num,2);
                if($real_money <= 0) {
                    return $this->json(['status'=>0,'msg'=>'提现金额有误']);
                }
                $record['tongzheng'] = $tongzheng_num;
            }
            $record['money'] = round($real_money,2);
			$record['txmoney'] = $money;
			if($post['paytype']=='支付宝'){
				$record['aliaccountname'] = $this->member['aliaccountname'];
				$record['aliaccount'] = $this->member['aliaccount'];
			}
			if($post['paytype']=='银行卡'){
				$record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
				$record['bankcarduser'] = $this->member['bankcarduser'];
				$record['bankcardnum'] = $this->member['bankcardnum'];
			}
            if($post['paytype']=='银行卡' && $set['withdraw_huifu'] == 1){
                $record['huifu_id'] = $this->member['huifu_id'];
            }
			if(getcustom('transfer_farsion')){
				$account_no = '';
				$xiaoetong_type = 1;
				if($post['paytype']=='小额通支付宝'){
					$record['aliaccountname'] = $this->member['aliaccountname'];
					$record['aliaccount'] = $this->member['aliaccount'];
					$account_no = $record['aliaccount'];
					$xiaoetong_type = 2;
				}
				if($post['paytype']=='小额通银行卡'){
					$record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
					$record['bankcarduser'] = $this->member['bankcarduser'];
					$record['bankcardnum'] = $this->member['bankcardnum'];
					$account_no = $record['bankcardnum'];
					$xiaoetong_type = 1;
				}
			}
            if(getcustom('extend_linghuoxin')){
                if($post['paytype']=='灵活薪支付宝' || $post['paytype']=='灵活薪银行卡'){
                    //查看账号余额
                    // $getbalance = \app\custom\LinghuoxinCustom::getbalance(aid);
                    // if($getbalance['status'] == 0){
                    //     return $this->json($getchecksign);
                    // }
                    // if($record['money']>$getbalance['data']['availableBalance']){
                    //     return $this->json(['status'=>0,'msg'=>'灵活薪账号金额不足，请减少提现金额后重试']);
                    // }
                    if($post['paytype']=='灵活薪支付宝'){
                        $record['aliaccountname'] = $this->member['aliaccountname'];
                        $record['aliaccount'] = $this->member['aliaccount'];
                    }
                    if($post['paytype']=='灵活薪银行卡'){
                        $record['bankname']    = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
                        $record['bankcarduser']= $this->member['bankcarduser'];
                        $record['bankcardnum'] = $this->member['bankcardnum'];
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                if($post['paytype']=='通联支付银行卡'){
                    $record['bankname']    = '';
                    $record['bankcarduser']= $yunstuser['name'];
                    $record['bankcardnum'] = $yunstuser['cardNo'];
                }
            }
            if(getcustom('withdraw_paycode')){
                if($post['paytype']=='收款码'){
                    if($this->member['wxpaycode']){
                        $record['wxpaycode'] = $this->member['wxpaycode'];
                    }
                    if($this->member['alipaycode']){
                        $record['alipaycode'] = $this->member['alipaycode'];
                    }
                }
            }
            if(getcustom('withdraw_custom')){
                //自定义提现方式
                if($post['paytype'] == $set['custom_name']){
                    $record['customaccountname'] = $this->member['customaccountname'];
                    $record['customaccount'] = $this->member['customaccount'];
                    $record['customtel'] = $this->member['customtel'];
                    $record['customwithdraw'] = 1;
                }
            }
			$record['ordernum'] = $ordernum;
			$record['paytype'] = $post['paytype'];
			$record['platform'] = platform;
			if(getcustom('money_commission_withdraw_fenxiao')){
			    $commission_totalprice = dd_money_format($money -  $real_money);
                $ogupdate = \app\common\Fenxiao::withdrawfeeFenxiao($set,$this->member,$commission_totalprice,'money_withdraw');
                $record['parent1'] = $ogupdate['parent1'];
                $record['parent2'] = $ogupdate['parent2'];
                $record['parent3'] = $ogupdate['parent3'];
                $record['parent4'] = $ogupdate['parent4'];
                $record['parent1commission'] = $ogupdate['parent1commission'];
                $record['parent2commission'] = $ogupdate['parent2commission'];
                $record['parent3commission'] = $ogupdate['parent3commission'];
                $record['parent4commission'] = $ogupdate['parent4commission'];
            }

            $res = \app\common\Member::addmoney(aid,mid,-$money,t('余额').'提现');
            if(!$res || ($res && $res['status'] !=1)){
                \think\facade\Log::write('moneywithdrawfail_'.mid.'_'.$money);
                return json(['status'=>0,'msg'=>'提现失败']);
            }

			$recordid = Db::name('member_withdrawlog')->insertGetId($record);
			if($recordid){
                //记录今天提现了几次
                if(!$day_withdraw_num || empty($day_withdraw_num)){
                    cache($daywithdrawnum,1,86400);
                }else{
                    $daynum = $day_withdraw_num+1;
                    cache($daywithdrawnum,$daynum,86400);
                }
            }

            if(getcustom('money_commission_withdraw_fenxiao')){
                $record['id'] = $recordid;
                if($ogupdate) {
                    \app\common\Fenxiao::addWithdrawCommissionRecord($ogupdate, $record, 'money_withdraw');
                }
            }
			$tmplcontent = array();
			$tmplcontent['first'] = '有客户申请'.t('余额').'提现';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $this->member['nickname'];
			$tmplcontent['keyword2'] = date('Y-m-d H:i');
			$tmplcontent['keyword3'] = $money.'元';
			$tmplcontent['keyword4'] = $post['paytype'];
			\app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('admin/finance/withdrawlog'));
			
			$tmplcontent = [];
			$tmplcontent['name3'] = $this->member['nickname'];
			$tmplcontent['amount1'] = $money.'元';
			$tmplcontent['date2'] = date('Y-m-d H:i');
			$tmplcontent['thing4'] = '提现到'.$post['paytype'];
			\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'admin/finance/withdrawlog');
			//小额通提现
			if(getcustom('transfer_farsion')){
				if($set['withdraw_autotransfer'] &&  ($post['paytype'] == '小额通支付宝' || $post['paytype'] == '小额通银行卡' )){
					
					$xetService = new  \app\common\Xiaoetong(aid);
					//导入数据
                    $record['id'] = $recordid;
					$xet_res = $xetService->sendData($record,$this->member,'余额提现');	
					//print_r($res);die;
					if($xet_res['code'] == 0){
						Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 1]);			
						return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);					
					}else{
						\app\common\Member::addmoney(aid,mid,$money,t('余额').'提现返还');
						Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 2,'reason'=>'快商小额通推送失败'.$xet_res['msg']]);
						return $this->json(['status'=>1,'msg'=>'提现失败','data'=>[]]);
					}
				}
            }

            if(getcustom('extend_linghuoxin')){
                //灵活薪提现
                if($set['withdraw_autotransfer'] && ($post['paytype'] == '灵活薪支付宝' || $post['paytype'] == '灵活薪银行卡' )){
                    $gopay = \app\custom\LinghuoxinCustom::gopay(aid,0,$this->member,$recordid,$record,$post['paytype'],1);
                    if($gopay && $gopay['status'] == 1){
                    	$updata = [];
                        $updata['status']   = 1;
                        $updata['taskNo']   = $gopay['data']['taskNo'];
                        $updata['taskdata'] = json_encode($gopay['data']);
                    	Db::name('member_withdrawlog')->where('id',$recordid)->update($updata);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        Db::name('member_withdrawlog')->where('id',$recordid)->update(['reason'=>'灵活薪推送失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                        // $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        // \app\common\Member::addmoney(aid,mid,$money,t('余额').'提现返还');
                        // Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 2,'reason'=>'灵活薪推送失败'.$msg]);
                        // return $this->json(['status'=>0,'msg'=>'提现失败','data'=>[]]);
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                //通联支付 云商通
                if($set['withdraw_autotransfer'] && $post['paytype'] == '通联支付银行卡'){
                    $withdrawApply = \app\custom\AllinpayYunst::withdrawApply(aid,$this->member,$recordid,$record,1);
                    if($withdrawApply && $withdrawApply['status'] == 1){
                    	$updata = [];
                        $updata['status']   = 1;
                        $updata['allinpayorderNo'] = $withdrawApply['data']['orderNo'];
                    	Db::name('member_withdrawlog')->where('id',$recordid)->update($updata);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $withdrawApply && $withdrawApply['msg']?$withdrawApply['msg']:'';
                        Db::name('member_withdrawlog')->where('id',$recordid)->update(['reason'=>'通联推送失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }
                }
            }

            $need_confirm = 0;
			if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
                Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 1]);
                if($set['wx_transfer_type']==1 && $post['paytype'] == '微信钱包'){
                    //使用了新版的商家转账功能
                    $paysdk = new WxPayV3(aid,mid,platform);
                    $rs = $paysdk->transfer($record['ordernum'],$record['money'],'',t('余额').'提现','member_withdrawlog',$recordid);
                    if($rs['status']==1){
                        $data = [
                            'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                            'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                            'wx_state' => $rs['data']['state'],//转账状态
                            'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                        ];
                        Db::name('member_withdrawlog')->where('id',$recordid)->update($data);
                        $need_confirm = 1;
                    }else{
                        $data = [
                            'wx_transfer_msg' => $rs['msg'],
                        ];
                        Db::name('member_withdrawlog')->where('id',$recordid)->update($data);
                    }
                    if($rs['status']==0){
                        if(strpos($rs['msg'],'资金不足')!==false){
                            $rs['msg'] = '商户资金不足，请联系管理员';
                        }
                        return $this->json(['status'=>0,'msg'=>'提现失败【'.$rs['msg'].'】']);
                    }
                }else{
                    $rs = \app\common\Wxpay::transfers(aid,mid,$record['money'],$record['ordernum'],platform,t('余额').'提现');
                    if($rs['status']==1){
                        Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 3]);
                        Db::name('member_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['out_batch_no']]);
                    }
                }
				if($rs['status']==0){
					return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
				}else{
					 if(getcustom('money_commission_withdraw_fenxiao')){
					     //$record 是提现订单信息
                         $record['id'] = $recordid;
                         \app\common\Fenxiao::jiesuanWithdrawCommission(aid,$record,'money_withdraw');
                     }
					//提现成功通知
					$tmplcontent = [];
					$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
					$tmplcontent['remark'] = '请点击查看详情~';
					$tmplcontent['money'] = (string) round($record['money'],2);
					$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($record['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
					\app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
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
					return $this->json(['status'=>1,'msg'=>$rs['msg'],'need_confirm'=>$need_confirm,'id'=>$recordid]);
				}
			}
			if(getcustom('alipay_auto_transfer')){
				if($set['ali_withdraw_autotransfer'] && $post['paytype'] == '支付宝'){
	                //Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 1]);
					$rs = \app\common\Alipay::transfers(aid,$record['ordernum'],$record['money'],t('余额').'提现',$record['aliaccount'],$record['aliaccountname'],t('余额').'提现');
					if($rs['status']==0){
						$sub_msg = $rs['sub_msg']?$rs['sub_msg']:'';
	                    if($sub_msg){
	                       Db::name('member_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['reason'=>$sub_msg]);
	                    }
						return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
					}else{
	                    Db::name('member_withdrawlog')->where('id',$recordid)->update(['status' => 3]);
						Db::name('member_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['pay_fund_order_id']]);
						//提现成功通知
						$tmplcontent = [];
						$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
						$tmplcontent['remark'] = '请点击查看详情~';
						$tmplcontent['money'] = (string) round($record['money'],2);
						$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
	                    $tempconNew = [];
	                    $tempconNew['amount2'] = (string) round($record['money'],2);//提现金额
	                    $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
						\app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
						//订阅消息
						$tmplcontent = [];
						$tmplcontent['amount1'] = $record['money'];
						$tmplcontent['thing3'] = '支付宝打款';
						$tmplcontent['time5'] = date('Y-m-d H:i');
						$tmplcontentnew = [];
						$tmplcontentnew['amount3'] = $record['money'];
						$tmplcontentnew['phrase9'] = '支付宝打款';
						$tmplcontentnew['date8'] = date('Y-m-d H:i');
						\app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
						//短信通知
						if($this->member['tel']){
							\app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
						}
						return $this->json(['status'=>1,'msg'=>$rs['msg']]);
					}
				}
			}
			if(getcustom('pay_allinpay')){
                //灵活薪提现
                if($set['withdraw_autotransfer'] && ($post['paytype'] == '通联支付' )){
                    $gopay = \app\custom\LinghuoxinCustom::gopay(aid,0,$this->member,$recordid,$record,$post['paytype'],1);
                    if($gopay && $gopay['status'] == 1){
                    	$updata = [];
                        $updata['status']   = 1;
                        $updata['taskNo']   = $gopay['data']['taskNo'];
                        $updata['taskdata'] = json_encode($gopay['data']);
                    	Db::name('member_withdrawlog')->where('id',$recordid)->update($updata);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        Db::name('member_withdrawlog')->where('id',$recordid)->update(['reason'=>'灵活薪推送失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }
                }
            }
			return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
		}

        $member_field = 'id,money,aliaccount,bankname,bankcarduser,bankcardnum,realname';
        if(getcustom('pay_huifu')){
            $member_field .= ',realname,usercard,usercard_begin_date,bank_province_code,bank_city_code,tel';
        }
        if(getcustom('withdraw_custom')){
            //自定义提现方式
            $member_field .= ',customaccountname,customaccount,customtel';
        }
        $userinfo = Db::name('member')->where('id',mid)->field($member_field)->find();
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
        if(getcustom('pay_adapay')){
            $adapay = Db::name('adapay_member')->where('aid',aid)->where('mid',mid)->find();
            $userinfo['to_set_adapay'] = 0;
            if(!$adapay ||  !$adapay['settle_account_id']){
                $userinfo['to_set_adapay'] = 1;
            }
        }
		$rdata = [];

		$selectbank = false;
		if(getcustom('yx_gift_pack')){
			$selectbank = true;
			//选择默认银行卡
			$bank = Db::name('member_bank')->where('aid',aid)->where('mid',mid)->where('isdefault',1)->find();
			if($bank) $bank['bankcardnum'] = substr($bank['bankcardnum'],0,3).'******'.substr($bank['bankcardnum'],-4);
			$rdata['bank'] = $bank;
		}
		$rdata['selectbank'] =  $selectbank;
		$moeny_weishu = 2;
		if(getcustom('member_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
        }
        $userinfo['money'] = dd_money_format($userinfo['money'],$moeny_weishu);
        $set['wx_max_money'] = 2000;//微信暂时定义的提现金额大于2000需要填写姓名
		$rdata['userinfo'] = $userinfo;
		$rdata['sysset'] = $set;
		$rdata['tmplids'] = $tmplids;
		return $this->json($rdata);
	}

	//获取提现配置信息，用于前台调用确认收款
	public function getwithdrawinfo(){
	    $id = input('id');
	    $type = input('type')?:'member_withdrawlog';
	    //$withdraw_log = Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        if($type=='business_withdrawlog'){
            $withdraw_log = Db::name($type)->where('aid',aid)->where('id',$id)->find();
        }elseif($type=='yuyue_worker_withdrawlog'){
            $worker = Db::name('yuyue_worker')->where('aid',aid)->where('mid',mid)->find();
            $withdraw_log = Db::name($type)->where('aid',aid)->where('id',$id)->where('uid',$worker['id'])->find();
        }elseif($type=='peisong_withdrawlog'){
            $worker = Db::name('peisong_user')->where('aid',aid)->where('mid',mid)->find();
            $withdraw_log = Db::name($type)->where('aid',aid)->where('id',$id)->where('uid',$worker['id'])->find();
        }else{
            $withdraw_log = Db::name($type)->where('aid',aid)->where('mid',mid)->where('id',$id)->find();
        }
        $platform = $withdraw_log['platform'];
        if($platform == 'wx'){ //小程序
            $appinfo = \app\common\System::appinfo(aid,'wx');
            if(empty($appinfo) || $appinfo['wxpay'] == 0) {
                $appinfo = \app\common\System::appinfo(aid,'mp');
            }
        }elseif($platform == 'app'){
            $appinfo = \app\common\System::appinfo(aid,'app');
            if(empty($appinfo) || $appinfo['wxpay'] == 0) {
                $appinfo = \app\common\System::appinfo(aid,'mp');
            }
        }else{ //公众号网页
            $appinfo = \app\common\System::appinfo(aid,'mp');
            if(empty($appinfo) || $appinfo['wxpay'] == 0) {
                $appinfo = \app\common\System::appinfo(aid,'wx');
            }
        }
        return json(['status'=>1,'appinfo'=>$appinfo,'detail'=>$withdraw_log]);
    }
    //检测提现结果
    public function check_withdraw_result(){
        $id = input('post.id/d');
        //$info = Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        $type = input('type')?:'member_withdrawlog';
        //$withdraw_log = Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        if($type=='business_withdrawlog'){
            $info = Db::name($type)->where('aid',aid)->where('id',$id)->find();
            $mid = Db::name('admin_user')->where('aid',aid)->where('bid',$info['bid'])->where('isadmin',1)->value('mid');
            $info['mid'] = $mid;
        }elseif($type=='yuyue_worker_withdrawlog'){
            $worker = Db::name('yuyue_worker')->where('aid',aid)->where('mid',mid)->find();
            $info = Db::name($type)->where('aid',aid)->where('id',$id)->where('uid',$worker['id'])->find();
            $info['mid'] = $worker['mid'];
        }elseif($type=='peisong_withdrawlog'){
            $worker = Db::name('peisong_user')->where('aid',aid)->where('mid',mid)->find();
            $info = Db::name($type)->where('aid',aid)->where('id',$id)->where('uid',$worker['id'])->find();
            $info['mid'] = $worker['mid'];
        }else{
            $info = Db::name($type)->where('aid',aid)->where('mid',mid)->where('id',$id)->find();
        }
        $paysdk = new WxPayV3(aid,$info['mid'],$info['platform']);
        $rs = $paysdk->transfer_query($info['ordernum'],$type,$id);
        if($rs['status']==1){
            $result = $rs['data'];
            if($result['state']=='SUCCESS'){
                Db::name($type)->where('aid',aid)->where('id',$id)->update(['status'=>3,'wx_state'=>$result['state']]);
            }elseif($result['state']=='CANCELING' || $result['state']=='CANCELLED'){//已撤销
                Db::name($type)->where('aid',aid)->where('id',$id)->update(['status'=>2,'wx_state'=>$result['state']]);
            }
            return $this->json($rs);
        }else{
            return $this->json($rs);
        }

    }
	public function moneylog(){
		$st = input('param.st');
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		if($st == 1){//充值记录
            $recharge_order_where_status = [];
            $recharge_order_where_status[] = ['status','=',1];
            $field = 'id,money,`status`,from_unixtime(createtime) createtime';
            if(getcustom('money_recharge_transfer')){
                //如果开启余额转账汇款
                $recharge_order_where_status = [];
                $where[] = ['paytype','<>','null'];
                $field .= ',paytypeid,transfer_check,payorderid';
            }
            if(getcustom('maidan_use_mendian')){
                $field .=',mdid';
            }
			$datalist = Db::name('recharge_order')->field($field)->where($where)->where($recharge_order_where_status)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                $datalist[$k]['money_recharge_transfer'] = false;
                if(getcustom('money_recharge_transfer')){
                    $datalist[$k]['money_recharge_transfer'] = true;
                    $datalist[$k]['payorder'] = Db::name('payorder')->where('aid',aid)->where('type','recharge')->where('orderid',$v['id'])->find();
                }
                if(getcustom('maidan_use_mendian')){
                    $mendian_name = Db::name('mendian')->where('aid',aid)->where('id',$v['mdid'])->value('name');
                    $datalist[$k]['mendian_name'] =  $mendian_name??'';
                }
            }
		}elseif($st ==2){//提现记录
			$datalist = Db::name('member_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime) createtime,reason,wx_state")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
		}else{ //余额明细
			$datalist = Db::name('member_moneylog')->field("id,money,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
			foreach($datalist as $k=>$v){
				if(strpos($v['remark'],'商家充值，') === 0){
					$datalist[$k]['remark'] = '商家充值';
				}
				$moeny_weishu = 2;
				if(getcustom('member_money_weishu')){
		            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
		        }
		        $datalist[$k]['money'] = dd_money_format($v['money'],$moeny_weishu);
		        $datalist[$k]['after'] = dd_money_format($v['after'],$moeny_weishu);
			}
		}
		if($pagenum == 1){
			$canwithdraw = Db::name('admin_set')->where('aid',aid)->value('withdraw');
		}
        $admin_set = Db::name('admin_set')->field('moneypay,recharge,withdraw')->where('aid',aid)->find();

        $showstatus = [];
        $showstatus[] = $admin_set['moneypay'] ;
        $showstatus[] = $admin_set['recharge'];
        $showstatus[] = $admin_set['withdraw'];
       
		return $this->json(['status'=>1,'data'=>$datalist,'canwithdraw'=>$canwithdraw,'showstatus' => $showstatus]);
	}
    public function gongxian_log(){
        if(getcustom('member_gongxian')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_gongxianlog')->field('id,value,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if(request()->isPost()){

                return $this->json(['status'=>1,'data'=>$datalist] );
            }

            $count = Db::name('member_gongxianlog')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }
	public function scorelog(){
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		$datalist = Db::name('member_scorelog')->field('id,score,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
        $score_weishu = 0;
        if(getcustom('score_weishu')){
            $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
		if($datalist){
		    foreach($datalist as $k=>$v){
		        $datalist[$k]['score'] = dd_money_format($v['score'],$score_weishu);
            }
        }
		if(request()->isPost()){
		    if($pagenum == 1) {
                $scoreTransfer = false;
                $set = [];
                $set = Db::name('admin_set')->where('aid', aid)->find();
                if(getcustom('score_transfer') && $set['score_transfer']){
                    $gettj = explode(',',$set['score_transfer_gettj']);
                    if(in_array('-1',$gettj) || in_array($this->member['levelid'],$gettj)){
                        $scoreTransfer = true;
                    }
                }
                $scoreWithdraw = $set['score_withdraw'] ? true : false;
            }
            $member_score = dd_money_format($this->member['score'],$score_weishu);
		    $score_withdraw = dd_money_format($this->member['score_withdraw'],$score_weishu);
            $score_freeze = 0;
            if(getcustom('yx_score_freeze')){
                $score_freeze = dd_money_format($this->member['score_freeze'],$score_weishu);
            }
            return $this->json(['status'=>1,'data'=>$datalist,'myscore'=>$member_score,
                'score_withdraw'=>$score_withdraw, 'scoreTransfer' => $scoreTransfer,'scoreWithdraw' => $scoreWithdraw,'scoreFreeze' => $score_freeze, 'set' => $set] );
		}

		$count = Db::name('member_scorelog')->where($where)->count();

		$rdata = [];
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		$rdata['pernum'] = $pernum;
		$rdata['st'] = $st;
		$member_score = dd_money_format($this->member['score'],$score_weishu);
		$rdata['myscore'] = $member_score;
        return $this->json($rdata);
	}
    public function scorefreezelog(){
	    if(getcustom('yx_score_freeze')){
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 15;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $count = Db::name('score_freeze_log')->where($where)->count();
            $datalist = Db::name('score_freeze_log')->field('id,score,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $datalist;
            return $this->json($rdata);
        }
    }

    public function scoreTransfer()
    {
        $setrs = [];
        if(getcustom('score_transfer_wxqrcode')){
            $setrs['score_transfer_wxqrcode'] = true;
        }
        if(getcustom('score_transfer_qrcode')){
            $setrs['score_transfer_qrcode'] = true;
        }
		$setrs['score_transfer_sxf_ratio']= $this->sysset['score_transfer_sxf_ratio'];
		$setrs['score_transfer_sxf_type'] = $this->sysset['score_transfer_sxf_type']??0;
        $score_weishu = 0;
        if(getcustom('score_weishu')){
            $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
        if(getcustom('score_transfer') || getcustom('score_friend_transfer')){
            $mid = input('param.mid/d',0);
            //积分转送
            $set = Db::name('admin_set')->where('aid', aid)->find();
            if ($set['score_transfer'] != 1) {
                return $this->json(['status'=>0,'msg'=>t('积分').'转赠未开启']);
            }
            $gettj = explode(',',$set['score_transfer_gettj']);
            if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
                return $this->json(['status'=>0,'msg'=>'您没有权限']);
            }
            $rectj = explode(',',$set['score_transfer_receivetj']);
            if(!in_array('-1',$rectj) && !in_array($this->member['levelid'],$rectj)){ //不是所有人
                return $this->json(['status'=>0,'msg'=>'您没有接收权限']);
            }
            if(getcustom('score_friend_transfer') && $set['score_transfer_range'] == 2) {
                //是不是好友
                $hasFriend  = Db::name('friend')->where('aid',aid)->where('mid',$this->mid)->where('fmid',$mid)->count();
                if(!$hasFriend){
                    return $this->json(['status'=>0,'msg'=>'非好友，不可转增']);
                }
            }
            $sxf_ratio = 0; //积分手续费比例
            $score_transfer_sxf = 0; //积分手续费开关
            if(getcustom('score_transfer_sxf')){
                $score_transfer_sxf = $set['score_transfer_sxf'];
                $sxf_ratio = Db::name('member_level')->where('id',$this->member['levelid'])->value('score_transfer_sxf_ratio');
                $sxf_ratio = $sxf_ratio/100;
            }
            if(request()->isPost()){
                $mobile = input('post.mobile');
                $mid = input('post.mid/d');
                $integral = input('post.integral');
                $integral = dd_money_format($integral,$score_weishu);
				$fee = dd_money_format($integral*$set['score_transfer_sxf_ratio']*0.01,$score_weishu);
				$feetype  = 0;//费用类型0:转出方 1：接收方

				//判断扣除手续费方是转出方还是接收方 0:转出方 1：接收方
                if($setrs['score_transfer_sxf_type'] && $setrs['score_transfer_sxf_type'] == 1){
                	$feetype = 1;
                }else if($setrs['score_transfer_sxf_type'] && $setrs['score_transfer_sxf_type'] == 2){
                	$feetype = input('?param.feetype')?input('param.feetype/d'):0;
                }
				//如果是转出方出，则判断需要加上费用再判断
                if($feetype == 1){
                	$payscore = dd_money_format($integral,$score_weishu);
                }else{
                	$payscore = dd_money_format($integral + $fee,$score_weishu);
                }

                if ($integral <= 0){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的'.t('积分').'数量']);
                }
                if (input('?post.mobile')) {
                    $info = Db::name('member')->where('aid', aid)->where('tel', $mobile)->find();
                }
                if (input('?post.mid')) {
                    $info = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
                }

                if(!$info) return $this->json(['status'=>0,'msg'=>'未找到该'.t('会员')]);
                $user_id = $info['id'];

                if ($info['id'] == mid) {
                    return $this->json(['status'=>0,'msg'=>'不能转赠给自己']);
                }
                if($set['score_transfer_range'] == 1) {
                    //所有上下级
                    $isparent = false;
                    if(in_array($user_id,explode(',',$this->member['path']))){
                        $isparent = true;
                    }
                    if(!$isparent){
                        if(!in_array(mid,explode(',',$info['path']))){
                            return $this->json(['status'=>0,'msg'=>'仅限转赠给上下级'.t('会员')]);
                        }
                    }
                }
                if ($payscore > $this->member['score']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('积分').'数量不足']);
                }
                //验证支付密码
                $pwd_check = $set['score_transfer_pwd'];
                if($pwd_check){
                    if(!$this->member['paypwd']){
                        return $this->json(['status'=>0,'msg'=>'请先设置支付密码','set_paypwd'=>1]);
                    }
                    $pay_pwd = input('paypwd')?:'';
                    if(!\app\common\Member::checkPayPwd($this->member,$pay_pwd )){
                        return $this->json(['status'=>0,'msg'=>'支付密码输入错误']);
                    }
                }

                //积分手续费
                if(getcustom('score_transfer_sxf')){
                    //0:关闭 1:开启
                    if(isset($this->sysset['score_transfer_sxf']) && $this->sysset['score_transfer_sxf'] == 1){
                        $ordernum = date('YmdHis').rand(1000,9999);
                        //计算手续费
                        $sxf = bcmul($integral,$sxf_ratio,2);
                        $dataOrder = [];
                        $dataOrder['aid'] = aid;
                        $dataOrder['mid'] = mid;
                        $dataOrder['receive_mid'] = $mid;
                        $dataOrder['score_num'] = $integral;
                        $dataOrder['transfer_sxf'] = $sxf;
                        $dataOrder['ordernum'] = $ordernum;
                        $dataOrder['createtime'] = time();
                        $orderid = Db::name('score_transfer_order')->insertGetId($dataOrder);
                        //扣除积分
                        $rs = \app\common\Member::addscore(aid,mid,$integral * -1, sprintf(t('积分')."转赠给：%s",$info['nickname']));
                        if ($rs['status'] == 1) {
                            //创建支付订单
                            $payorderid = \app\model\Payorder::createorder(aid,0,mid,'score_transfer',$orderid,$dataOrder['ordernum'],'积分转赠',$sxf);
                            return $this->json(['status' => 2,'msg' => '需要支付','orderid' => $orderid,'payorderid' => $payorderid]);
                        }else{
                            return $this->json(['status' => 0, 'msg' => '转赠失败']);
                        }
                    }
                }
                $where = [];
                $where['aid'] = aid;
                $where['id'] = $user_id;
                $rs = \app\common\Member::addscore(aid,mid,$integral * -1, sprintf(t('积分')."转赠给：%s",$info['nickname']));
                if ($rs['status'] == 1) {
                    \app\common\Member::addscore(aid,$user_id,$integral,sprintf("来自%s的".t('积分')."转赠", $this->member["nickname"]), '', 0, $this->mid);
					if($fee > 0){
						if($feetype == 1){
							\app\common\Member::addscore(aid,$user_id,$fee * -1, sprintf(t('积分')."转赠手续费"));
						}else{
							\app\common\Member::addscore(aid,mid,$fee * -1, sprintf(t('积分')."转赠手续费"));
						}
					}
                }else{
                    return $this->json(['status'=>0, 'msg' => '转赠失败']);
                }
                return $this->json(['status'=>1, 'msg' => '转赠成功', 'url'=>'/pages/my/usercenter']);
            }
            $tomember = [];
            if($mid){
                $tomember = Db::name('member')->where('aid',aid)->where('id',$mid)->field('id,money,nickname,headimg')->find();
            }
            $rdata['paycheck'] = $set['score_transfer_pwd'] ? true : false;
            $rdata['status'] = 1;
            $rdata['myscore'] = dd_money_format($this->member['score'],$score_weishu);
            $rdata['set'] = $setrs;
            $rdata['sxf_ratio'] = $sxf_ratio;
            $rdata['transfer_sxf'] = $score_transfer_sxf;
            $rdata['tomember'] = $tomember?$tomember:['nickname'=>''];//转给谁
            return $this->json($rdata);
        }
    }

    //积分转赠小程序码
    public function scoreTransferWxqrcode()
    {
        if(getcustom('score_transfer_wxqrcode')) {
            $poster = \app\common\Wechat::getQRCode(aid,'wx','pagesExt/my/scoreTransfer',['mid'=>mid,'pid'=>mid]);
            return $this->json(['status'=>1,'poster'=>$poster['url']]);
        }
    }

    //积分转赠二维码
    public function scoreTransferQrcode()
    {
        if(getcustom('score_transfer_qrcode')) {
            $poster = createqrcode(m_url('pagesExt/my/scoreTransfer?mid='.mid.'&pid='.mid,aid),'',aid);
            return $this->json(['status'=>1,'poster'=>$poster]);
        }
    }

    public function scoreWithdraw()
    {
        $score_weishu = 0;
        if(getcustom('score_weishu')){
            $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
        if(getcustom('score_withdraw')){
            //积分转到余额
            $set = Db::name('admin_set')->where('aid', aid)->find();
            if ($set['score_withdraw'] != 1) {
                return $this->json(['status'=>0,'msg'=>t('积分').'提现未开启']);
            }
            if(request()->isPost()){
                $integral = input('post.integral');
                $integral =  dd_money_format($integral,$score_weishu);
                if ($integral <= 0){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的'.t('积分').'数量']);
                }
                if ($integral > $this->member['score_withdraw']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('积分').'数量不足']);
                }
                $where = [];
                $where['aid'] = aid;
                $where['id'] = mid;
                $money = round($integral * $set['score_to_money_percent'],2);
                if($money < 0.01) {
                    return $this->json(['status'=>0,'msg'=>'提现金额不足0.01']);
                }
                //最低兑换金额
                if($money < $set['score_to_money_min_money']){
                    return $this->json(['status'=>0,'msg'=>'最低兑换金额'.$set['score_to_money_min_money']]);
                }
                $rs = \app\common\Member::addmoney(aid,mid,$money,'允提'.t('积分').'提现');
                if ($rs['status'] == 1) {
                    \app\common\Member::addscore_withdraw(aid,mid,$integral * -1, '允提'.t('积分').'提现');
                }
                return $this->json(['status'=>1, 'msg' => '操作成功', 'url'=>'/pages/my/usercenter']);
            }

            $rdata['status'] = 1;
            $rdata['myscore'] = dd_money_format($this->member['score_withdraw'],$score_weishu);
            $rdata['score_to_money_percent'] = $set['score_to_money_percent']+0;
            $rdata['score_to_money_min_money'] = $set['score_to_money_min_money']+0;
            return $this->json($rdata);
        }
    }
    public function memberScoreWithdraw(){
        $score_weishu = 0;
        if(getcustom('score_weishu')){
            $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
	    if(getcustom('member_score_withdraw')){
            //积分转到余额
            $set = Db::name('admin_set')->where('aid', aid)->find();
            if ($set['member_score_withdraw'] != 1) {
                return $this->json(['status'=>0,'msg'=>t('积分').'提现未开启']);
            }
            if(request()->isPost()){
                $integral = input('post.integral');
                $integral =  dd_money_format($integral,$score_weishu);
                if ($integral <= 0){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的'.t('积分').'数量']);
                }
                if ($integral > $this->member['score']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('积分').'数量不足']);
                }
                $where = [];
                $where['aid'] = aid;
                $where['id'] = mid;
                $money = round($integral * $set['member_score_to_money_ratio'],2);
                if($money < 0.01) {
                    return $this->json(['status'=>0,'msg'=>'提现金额不足0.01']);
                }
                //最低兑换金额
                if($money < $set['member_score_to_money_min']){
                    return $this->json(['status'=>0,'msg'=>'最低兑换金额'.$set['member_score_to_money_min']]);
                }
                $rs = \app\common\Member::addmoney(aid,mid,$money,t('积分').'提现');
                if ($rs['status'] == 1) {
                    \app\common\Member::addscore(aid,mid,$integral * -1, t('积分').'提现');
                }
                return $this->json(['status'=>1, 'msg' => '操作成功', 'url'=>'/pages/my/usercenter']);
            }

            $rdata['status'] = 1;
            $rdata['myscore'] = dd_money_format($this->member['score'],$score_weishu);
            $rdata['score_to_money_percent'] = $set['member_score_to_money_ratio']+0;
            $rdata['score_to_money_min_money'] = $set['member_score_to_money_min']+0;
            return $this->json($rdata);
        }
    }
	public function bscore(){
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['business_memberscore.aid','=',aid];
		$where[] = ['business_memberscore.mid','=',mid];
		$datalist = Db::name('business_memberscore')->alias('business_memberscore')->field('business.logo,business.name,business_memberscore.*')->join('business business','business.id=business_memberscore.bid')->where($where)->page($pagenum,$pernum)->order('business_memberscore.score desc')->select()->toArray();
		if(!$datalist) $datalist = [];

		$rdata = [];
		$rdata['status'] = 1;
		$rdata['data'] = $datalist;
		$rdata['mybscore'] = $this->member['bscore'];
		$rdata['myscore'] = $this->member['score'];
		$rdata['status'] = 1;
		return $this->json($rdata);
	}
	public function bscorelog(){
		$bid = input('param.bid/d');
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',$bid];
		$where[] = ['mid','=',mid];
		$datalist = Db::name('business_member_scorelog')->field('id,score,after,remark,createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		
		if($pagenum == 1){
			$memberscore = Db::name('business_memberscore')->where('aid',aid)->where('bid',$bid)->where('mid',mid)->find();
			$bname = Db::name('business')->where('aid',aid)->where('id',$bid)->value('name');
		}

		$rdata = [];
		$rdata['status'] = 1;
		$rdata['data'] = $datalist;
		$rdata['status'] = 1;
		$rdata['mybscore'] = $memberscore['score'] ?? 0;
		$rdata['bname'] = $bname;
		return $this->json($rdata);
	}

	public function formlog(){
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		$where[] = ['isudel','=',0];
		
		if(input('post.keyword')){
			$where[] = ['title|form0|form1|form2|form3|form4|form5|form6|form7|form8|form9|form10','like','%'.input('param.keyword').'%'];
		}

		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		if($st == 'all'){

		}elseif($st == '0'){
			$where[] = ['status','=',0];
			$where[] = Db::raw('payorderid is null or paystatus=1');
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '10'){
			$where[] = ['status','=',0];
			$where[] = ['paystatus','=',0];
			$where[] = ['payorderid','<>',''];
		}

		//$where['status'] = 1;
		$datalist = Db::name('form_order')->field('*,from_unixtime(createtime)createtime,from_unixtime(paytime)paytime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];

		if($datalist){
		    foreach($datalist as $dk=>$detail){
                $form = Db::name('form')->where('aid',aid)->where('id',$detail['formid'])->find();
                $formcontent = json_decode($form['content'],true);

                $linkitemArr = [];
                foreach($formcontent as $k=>$v){
                    if(($v['key'] == 'radio' || $v['key'] == 'selector') && $detail['form'.$k]!==''){
                        $linkitemArr[] = $v['val1'].'|'.$detail['form'.$k];
                    }
                }
                foreach($formcontent as $k=>$v){
                    if($v['linkitem'] && !in_array($v['linkitem'],$linkitemArr)){
                        $formcontent[$k]['hidden'] = true;
                    }
                    if(!getcustom('form_map')){
                        $formcontent[$k]['val12'] = 1;
                    }
                }
                //距离
                $detail['distance'] = '';
                $detail['show_distance'] = 0;
                if(getcustom('form_map')){
                    if($detail['adr_lon'] && $detail['adr_lat']){
                        $detail['show_distance'] = 1;
                        if( input('longitude') && input('latitude')){
                            $distance = getdistance($detail['adr_lon'], $detail['adr_lat'], input('longitude'), input('latitude'));
                            $distance = bcdiv($distance,1000,2);
                            $detail['distance'] = $distance.'km';
                        }
                    }
                }
                $detail['formcontent'] = $formcontent;
                if(getcustom('form_custom_number')){
                    $detail['custom_number_text'] = $form['custom_number_text'];
                }
                $datalist[$dk] = $detail;
            }
        }
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
		$count = Db::name('form_order')->where($where)->count();
		$rdata = [];
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		$rdata['pernum'] = $pernum;
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
    public function formlog2(){
	    if(!getcustom('form_log_plug')){
	        return false;
        }
        $pagenum = input('post.pagenum');
        $st = input('post.st');
        $id = input('post.id');
        $query_form = Db::name('form')->where('id',$id)->find();

        if(getcustom('form_match')){
            //验证权限
            $quanxian = json_decode($query_form['quanxian'],true);
            if(!empty($quanxian) && $quanxian['all']!=='on'){
                $levelid = $this->member['levelid'];
                if($quanxian[$levelid]!=='on'){
                    $noauth_text = $query_form['noauth_text']?:'无权限访问';
                    $noauth_url = $query_form['noauth_url']?:'/pages/my/usercenter';
                    return $this->json(['status'=>0,'msg'=>$noauth_text,'redirect_url'=>$noauth_url]);
                }
            }
            $query_form['custom_search'] = 1;
        }
        if(!$pagenum) $pagenum = 1;
        $pernum = 20;
        $order_sort = 'id desc';
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['isudel','=',0];
        $where[] = ['formid','=',$id];

		if(input('post.keyword')){
		    if(getcustom('form_match') && !empty($query_form['form_match'])){
		        //开启数据匹配模式
		        $formcontent = json_decode($query_form['content'],true);
		        $match_key = 0;
		        foreach($formcontent as $k=>$v){
		            if(!empty($v['val13'])){
                        $match_key = $k;
                    }
                }
		        //查询匹配数据
		        $keyword = input('keyword')?:0;
                $keyword = intval($keyword);

		        $form_match = Db::name('form_match')->where('search_val',$keyword)->find();
		        if(!$form_match){
                    return $this->json(['status'=>1,'data'=>[],'queryform'=>$query_form]);
                }
		        //计算百分比
		        $match_data = round(bcdiv($form_match['div_val'],$form_match['total'],5),4);
		        //向下匹配
                $match_limit = $query_form['match_limit']?:1;
                $where_match = [];
                $where_match[] = ['formid','=',$query_form['id']];
                $where_match[] = ['form'.$match_key,'>=',$match_data];
                $order_sort = 'form'.$match_key.' asc,id desc';
                $order_ids1 = Db::name('form_order')->where($where_match)->limit($match_limit)->order($order_sort)->column('id');
                //再向上匹配三条数据
                $match_limit_down = $query_form['match_limit_up']?:1;
                $where_match = [];
                $where_match[] = ['formid','=',$query_form['id']];
                $where_match[] = ['form'.$match_key,'<',$match_data];
                $order_ids2 = Db::name('form_order')->where($where_match)->limit($match_limit_down)->order('form'.$match_key.' desc,id desc')->column('id');
                $order_ids = array_merge($order_ids1,$order_ids2);
                $where[] = ['id','in',$order_ids];

            }else{
                $where[] = ['title|form0|form1|form2|form3|form4|form5|form6|form7|form8|form9|form10','like','%'.input('param.keyword').'%'];
            }

		}

        if(!input('?param.st') || $st === ''){
            $st = 'all';
        }
        if($st == 'all'){

        }elseif($st == '0'){
            $where[] = ['status','=',0];
            $where[] = Db::raw('payorderid is null or paystatus=1');
        }elseif($st == '1'){
            $where[] = ['status','=',1];
        }elseif($st == '2'){
            $where[] = ['status','=',2];
        }elseif($st == '10'){
            $where[] = ['status','=',0];
            $where[] = ['paystatus','=',0];
            $where[] = ['payorderid','<>',''];
        }

        //$where['status'] = 1;
        $datalist = Db::name('form_order')->field('*,from_unixtime(createtime)createtime,from_unixtime(paytime)paytime')->where($where)->page($pagenum,$pernum)->order($order_sort)->select()->toArray();
        if(!$datalist) $datalist = [];
        else {
            foreach ($datalist as $k => $item) {
            	//处理前四条提交类型
            	$datalist[$k]['form0_key'] = '';
            	$datalist[$k]['form1_key'] = '';
            	$datalist[$k]['form2_key'] = '';
            	$datalist[$k]['form3_key'] = '';

            	$datalist[$k]['form0_val'] = '';
            	$datalist[$k]['form1_val'] = '';
            	$datalist[$k]['form2_val'] = '';
            	$datalist[$k]['form3_val'] = '';
            	$datalist[$k]['member'] = '';
            	if($item['mid']){
            		 $datalist[$k]['member'] = \db('member')->where('aid',aid)->where('id',$item['mid'])->field('nickname,realname')->find();
            		 $datalist[$k]['member']['nickname'] = $datalist[$k]['member']['nickname'] && !is_null($datalist[$k]['member']['nickname'])?$datalist[$k]['member']['nickname']:'';
            		 $datalist[$k]['member']['realname'] = $datalist[$k]['member']['realname'] && !is_null($datalist[$k]['member']['realname'])?$datalist[$k]['member']['realname']:'';
            	}else{
                    $datalist[$k]['member'] = ['nickname'=>'','realname'=>''];
                }
                $form = Db::name('form')->where('aid',aid)->where('id',$item['formid'])->find();
				$formcontent = json_decode($form['content'],true);
				
				if($formcontent){
					foreach($formcontent as $k2=>$v){
						if($v['key'] == 'upload_pics'){
							$pics = $item['form'.$k];
							if($pics){
								$datalist[$k]['form'.$k] = explode(",",$pics);
							}
						}
					}
					if($formcontent[0]){
						$datalist[$k]['form0_key'] = $formcontent[0]['key'];
						$datalist[$k]['form0_val'] = $formcontent[0]['val1'];
                        $datalist[$k]['form0_show'] = 1;
					}
					if($formcontent[1]){
						$datalist[$k]['form1_key'] = $formcontent[1]['key'];
						$datalist[$k]['form1_val'] = $formcontent[1]['val1'];
                        $datalist[$k]['form1_show'] = 1;
					}
					if($formcontent[2]){
						$datalist[$k]['form2_key'] = $formcontent[2]['key'];
						$datalist[$k]['form2_val'] = $formcontent[2]['val1'];
                        $datalist[$k]['form2_show'] = 1;
					}
					if($formcontent[3]){
						$datalist[$k]['form3_key'] = $formcontent[3]['key'];
						$datalist[$k]['form3_val'] = $formcontent[3]['val1'];
                        $datalist[$k]['form3_show'] = 1;
					}
                    $show_distance = 0;
					if(getcustom('form_map')){
					    //是否展示字段、是否显示距离
                        foreach($formcontent as $c_key=>$c_val){
                            $datalist[$k]['form'.$c_key.'_show'] = $c_val['val12'];
                            if($item['adr_lat'] && $item['adr_lon']){
                                $show_distance = 1;
                                if( input('longitude') && input('latitude')){
                                    $distance = getdistance($item['adr_lon'], $item['adr_lat'], input('longitude'), input('latitude'))?:0;
                                    $distance = bcdiv($distance,1000,2);
                                    $datalist[$k]['distance'] = $distance.'km';
                                }
                            }
                        }
                        //是否显示距离
                    }
                    $datalist[$k]['show_distance'] = $show_distance;
				}
                $datalist[$k]['background_color'] = '#fff';
                if(getcustom('form_match') && !empty($query_form['form_match'])){
                    if(in_array($datalist[$k]['id'],$order_ids1)){
                        $datalist[$k]['background_color'] = $query_form['background_color_down'];
                    }
                    if(in_array($datalist[$k]['id'],$order_ids2)){
                        $datalist[$k]['background_color'] = $query_form['background_color_up'];
                    }
                }
            }
        }
        if(!getcustom('form_match')){
            $query_form['show_title'] = 1;//是否显示页面标题 1显示 0不显示
            $query_form['log_title'] = '';//页面标题内容自定义
            $query_form['show_name'] = 1;//是否显示表单记录中表单名称 1显示 0不显示
            $query_form['show_time'] = 1;//是否显示提交时间 1显示 0不显示
            $query_form['show_audit'] = 1;//是否显示审核状态 1显示 0不显示
            $query_form['desc'] = '';//表单说明
            $query_form['search_title'] = '输入关键字搜索';//搜索框提示语自定义
            $query_form['custom_search'] = 0;//定制搜索框样式
        }
        if(request()->isPost()){
            return $this->json(['status'=>1,'data'=>$datalist,'queryform'=>$query_form]);
        }
        $count = Db::name('form_order')->where($where)->count();
        $rdata = [];
        $rdata['count'] = $count;
        $rdata['datalist'] = $datalist;
        $rdata['pernum'] = $pernum;
        $rdata['queryform'] = $query_form;
        $rdata['st'] = $st;
        return $this->json($rdata);
    }
	public function formdetail(){
		$id = input('param.id/d');
        $op = input('param.op');
        if($op == 'view' && (getcustom('form_log_plug') || cache($this->sessionid.'_formquery') == $id))
            $detail = Db::name('form_order')->where('aid',aid)->where('id',$id)->find();
        else
		    $detail = Db::name('form_order')->where('aid',aid)->where('mid',mid)->where('id',$id)->find();

        $detail['is_other_fee'] = 0;
        if(getcustom('form_other_money')){
            if($detail['fee_items']){
                $detail['is_other_fee'] = 1;
                $detail['fee_items'] = json_decode($detail['fee_items'],true);
            }
        }

        $detail['is_ht'] = false;
        if(getcustom('form_sign_pdf')){
            $detail['is_ht'] = true;
        }

        $detail['yx_order_discount_rand'] = false;
        if(getcustom('yx_order_discount_rand')){
            $detail['yx_order_discount_rand'] = true;
        }

		$detail['paytime'] = date('Y-m-d H:i:s',$detail['paytime']);
		$detail['createtime'] = date('Y-m-d H:i:s',$detail['createtime']);
        $detail['distance'] = '';
        $detail['show_distance'] = 0;
        if(getcustom('form_map')){
            if($detail['adr_lon'] && $detail['adr_lat'] ){
                $detail['show_distance'] = 1;
                if(input('longitude') && input('latitude')){
                    $distance = getdistance($detail['adr_lon'], $detail['adr_lat'], input('longitude'), input('latitude'));
                    $distance = bcdiv($distance,1000,2);
                    $detail['distance'] = $distance.'km';
                }
            }
        }

		$form = Db::name('form')->where('aid',aid)->where('id',$detail['formid'])->find();
		$formcontent = json_decode($form['content'],true);

        if(getcustom('form_match')){
            //验证权限
            $quanxian = json_decode($form['quanxian'],true);
            if(!empty($quanxian) && $quanxian['all']!=='on'){
                $levelid = $this->member['levelid'];
                if($quanxian[$levelid]!=='on'){
                    $noauth_text = $form['noauth_text']?:'无权限访问';
                    $noauth_url = $form['noauth_url']?:'/pages/my/usercenter';
                    return $this->json(['status'=>0,'msg'=>$noauth_text,'redirect_url'=>$noauth_url]);
                }
            }
        }
		
		$linkitemArr = [];
		foreach($formcontent as $k=>$v){
			if(($v['key'] == 'radio' || $v['key'] == 'selector') && $detail['form'.$k]!==''){
				$linkitemArr[] = $v['val1'].'|'.$detail['form'.$k];
			}
		}
		foreach($formcontent as $k=>$v){
			if($v['linkitem'] && !in_array($v['linkitem'],$linkitemArr)){
				$formcontent[$k]['hidden'] = true;
			}
			if(!getcustom('form_map')){
                $formcontent[$k]['val12'] = 1;
            }
            if($v['key'] == 'upload_pics'){
				$pics = $detail['form'.$k];
				if($pics){
					$detail['form'.$k] = explode(",",$pics);
				}
			}
		}

		$againname = '再次提交';
		$detail['againsubmit'] = true;
		if(getcustom('article_portion') || getcustom('form_edit')){
			//是否能编辑
			$detail['edit_status'] = false;
			if($form['edit_status'] == 1 && $detail['mid']==mid){
				$detail['edit_status'] = true;
			}

			$detail['edit_name'] = '编辑';
		}
		if(getcustom('businessindex_showfw')){
			$againname = '复制档案';
		}
        if(!getcustom('form_match')){
            $form['show_title'] = 1;
            $form['log_title'] = '';
            $form['show_name'] = 1;
            $form['show_time'] = 1;
            $form['show_audit'] = 1;
        }
        if(getcustom('form_data')){
			$detail['edit_name']   = '修改';
			$detail['againsubmit'] = false;
		}

		$rdata = [];
		$rdata['form'] = $form;
		$rdata['formcontent'] = $formcontent;
		$rdata['detail'] = $detail;
		$rdata['againname'] = $againname;
		return $this->json($rdata);
	}

	public function formdelete(){
		$id = input('param.id/d');
		Db::name('form_order')->where('aid',aid)->where('mid',mid)->where('id',$id)->update(['isudel'=>1]);
		return json(['status'=>1,'msg'=>'操作成功']);
	}

	public function favorite(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		$datalist = Db::name('member_favorite')->field('id,proid,from_unixtime(createtime)createtime,type')->where($where)->page($pagenum,$pernum)->order('createtime desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$v){
			$product = [];
			if($v['type'] == 'shop'){
				$product = Db::name('shop_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'yuyue'){
				$product = Db::name('yuyue_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'seckill'){
				$product = Db::name('seckill_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'collage'){
				$product = Db::name('collage_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'scoreshop'){
				$product = Db::name('scoreshop_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'choujiang'){
				$product = Db::name('choujiang_product')->where('id',$v['proid'])->find();
				$product['market_price'] = $product['sell_price'];
				$product['sell_price'] = $product['min_price'];
			}elseif($v['type'] == 'kecheng'){
				$product = Db::name('kecheng_list')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'luckycollage'){
				$product = Db::name('lucky_collage_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'zhaopin'){
                $product = Db::name('zhaopin')->where('id',$v['proid'])->find();
            }elseif($v['type'] == 'qiuzhi'){
                $product = Db::name('zhaopin_qiuzhi')->where('id',$v['proid'])->find();
            }elseif($v['type'] == 'yueke'){
                $product = Db::name('yueke_product')->where('id',$v['proid'])->find();
            } elseif($v['type'] == 'cycle'){
                $product = Db::name('cycle_product')->where('id',$v['proid'])->find();
            }   elseif($v['type'] == 'car_hailing'){
                $product = Db::name('car_hailing_product')->where('id',$v['proid'])->find();
               
                $date = date('Y-m-d',time() + 86400 * 0);
                $yyorderlist = Db::name('car_hailing_order')->alias('car_hailing_order')->field('member.headimg,member.nickname')->join('member member','member.id=car_hailing_order.mid')->where('car_hailing_order.proid',$product['id'])->where('car_hailing_order.yy_date',$date)->where('car_hailing_order.status','in',[1,2,3])->select()->toArray();
                if(!$yyorderlist) $yyorderlist = [];
                if($product['cid'] ==2){
                    $product['leftnum'] = $product['yynum'] - count($yyorderlist);
                    $starttime = strtotime($date.' '.$v['starttime']);
                    if($starttime < time() + $v['prehour']*3600 && $v['cid'] !=1){
                        $product['isend'] = true;
                    }else{
                        $product['isend'] = false;
                    }
                }
                
                $product['yyorderlist'] = $yyorderlist;
            }elseif($v['type'] == 'huodongbaoming'){
                $product = Db::name('huodong_baoming_product')->where('id',$v['proid'])->find();
            }
			if(!$product){
				Db::name('member_favorite')->where('id',$v['id'])->delete();
				unset($datalist[$k]);
			}else{
				$datalist[$k]['product'] = $product;
			}
		}
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
		$count = Db::name('member_favorite')->where($where)->count();

		$rdata = [];
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		$rdata['pernum'] = $pernum;
		return $this->json($rdata);
	}
	public function favoritedel(){
		$post = input('post.');
		Db::name('member_favorite')->where('aid',aid)->where('mid',mid)->where('id',$post['id'])->delete();
		return $this->json(['status'=>1,'msg'=>'已取消','url'=>true]);
	}

	public function history(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
        $mid = $this->mid;
        if(getcustom('team_member_history')){
            //可以查看下级的足迹
            $mid = input('param.mid/d',0);
            if(empty($mid)) $mid = $this->mid;
            //我所有的下级
            $myallDownMids = \app\common\Member::getdownmids(aid,mid);
            if ($myallDownMids){
                $myallDownMids[] = $this->mid;//自己和下级
            }else{
                $myallDownMids = [$this->mid];
            }
            if(!in_array($mid,$myallDownMids)){
                return $this->json(['status'=>0,'msg'=>'无权查看该会员足迹','data'=>[]]);
            }
        }
		$where[] = ['mid','=',$mid];
		$datalist = Db::name('member_history')->field('id,proid,from_unixtime(createtime)createtime,type,mid')->where($where)->page($pagenum,$pernum)->order('createtime desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$v){
			$product = [];
			if($v['type'] == 'shop'){
				$product = Db::name('shop_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'yuyue'){
				$product = Db::name('yuyue_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'collage'){
				$product = Db::name('collage_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'scoreshop'){
				$product = Db::name('scoreshop_product')->where('id',$v['proid'])->find();
			}elseif($v['type'] == 'choujiang'){
				$product = Db::name('choujiang_product')->where('id',$v['proid'])->find();
				$product['market_price'] = $product['sell_price'];
				$product['sell_price'] = $product['min_price'];
			}elseif($v['type'] == 'zhaopin'){
                $product = Db::name('zhaopin')->where('id',$v['proid'])->find();
            }elseif($v['type'] == 'qiuzhi'){
                $product = Db::name('zhaopin_qiuzhi')->where('id',$v['proid'])->find();
            }
			if(!$product){
				Db::name('member_history')->where('id',$v['id'])->delete();
				unset($datalist[$k]);
			}else{
				$datalist[$k]['product'] = $product;
			}
		}
        foreach ($datalist as $k=>$v){
            $datalist[$k]['ismine'] = true;
            if($v['mid']!=$this->mid){
                $datalist[$k]['ismine'] = false;
            }
        }
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
		$count = Db::name('member_history')->where($where)->count();

		$rdata = [];
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		$rdata['pernum'] = $pernum;
		return $this->json($rdata);
	}
	public function historydel(){
		$post = input('post.');
		if($post['proid'] =='all'){
			$rs = Db::name('member_history')->where('aid',aid)->where('mid',mid)->delete();
		}else{
			$rs = Db::name('member_history')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('type',$post['type'])->delete();
		}
		return $this->json(['status'=>1,'msg'=>'已删除','url'=>true]);
	}
	//等级说明
	public function levelinfo(){
        $id = input('param.id/d', $this->member['levelid']);
        if(empty($id)) $id = $this->member['levelid'];
		$userinfo = Db::name('member')->where('id',mid)->field('id,nickname,headimg,sex,levelid,levelendtime,areafenhong,areafenhongbl,areafenhong_province province,areafenhong_city city,areafenhong_area area,areafenhong_largearea largearea,totalcommission')->find();
		if($id != $this->member['levelid']) {
            $levelids = Db::name('member_level_record')->where('aid', aid)->where('mid',mid)->column('levelid');
            if(!in_array($id,$levelids)) {
                return $this->json(['status'=>0,'msg'=>'不存在的等级','url'=>true]);
            }
        }

		$userlevel = Db::name('member_level')->where('id',$id)->find();
		if($userinfo['areafenhong'] == 1) $userlevel['areafenhong'] = 1;
		if($userinfo['areafenhong'] == 2) $userlevel['areafenhong'] = 2;
		if($userinfo['areafenhong'] == 3) $userlevel['areafenhong'] = 3;
		if($userinfo['areafenhong'] == 4) $userlevel['areafenhong'] = 0;
		if($userlevel){
			$field = 'id,sort,name,up_fxordermoney,up_fxordermoney_removemax,up_fxorderlevelnum,up_fxorderlevelid';
			if(getcustom('levelup_fxordermoney_self',aid)){
				$field = $field.',up_fxordermoney_self';
			}
			$nextlevel = Db::name('member_level')->where("`sort`>{$userlevel['sort']} or (`sort`={$userlevel['sort']} and id>{$userlevel['id']})")->where('cid', $userlevel['cid'])->where('aid',aid)->field($field)->find();
		}
		$showprogress = 0;
		if($nextlevel){
			$hasnext = 1;
            $up_fxordermoney_self = false;
            if(getcustom('levelup_fxordermoney_self',aid) && $nextlevel['up_fxordermoney_self'] == 1){
                //下级总订单金额含自己，1开启
                $up_fxordermoney_self = true;
            }
			if(getcustom('team_yeji_uplv_pace')){
                //会员等级页面增加文字和进度条,团队业绩升级进度,不设置团队业绩这个条件不显示进度条，最高级别也不显示
				//查找当前业绩  最后一次降级时间
				$fxordermoney = 0;
		        $down_level_time = Db::name('member_levelup_order')->where('mid',mid)->where('type',1)->order('createtime desc')->value('createtime');
		        $down_level_time = $down_level_time?:0;
				if($nextlevel['up_fxordermoney_removemax'] ==1){
	                $downmids = \app\common\Member::getdownmids_removemax(aid,mid,$nextlevel['up_fxorderlevelnum'],$nextlevel['up_fxorderlevelid']);
	            }else{
	                $downmids = \app\common\Member::getdownmids(aid,mid,$nextlevel['up_fxorderlevelnum'],$nextlevel['up_fxorderlevelid'],0,1,$down_level_time);
	            }
                if($up_fxordermoney_self) $downmids[] = mid;
	            if($downmids){
	                $fxordermoney = 0 + Db::name('shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');

	                // 餐饮订单计入团队业绩，参与升级条件统计
	                if(getcustom('restaurant_team_yeji')){
	                	$restaurant_team_yeji_open = Db::name('admin_set')->where('aid',aid)->value('restaurant_team_yeji_open');
	                	if($restaurant_team_yeji_open){
	                		// 外卖
		                    $rtakeaway_fxordermoney = Db::name('restaurant_takeaway_order_goods')->where('status','in','1,2,3,12')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
		                    $fxordermoney += $rtakeaway_fxordermoney;
		                    // 店内点餐
		                    $rshop_fxordermoney = Db::name('restaurant_shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
		                    $fxordermoney += $rshop_fxordermoney;
	                	}
	                }
	            }

	            $nextlevel['now_team_yeji'] = $fxordermoney;
	            $nextlevel['up_team_yeji'] = $nextlevel['up_fxordermoney'];
	            if($nextlevel['up_fxordermoney'] > 0){
	            	$nextlevel['progress'] = floatval($fxordermoney / $nextlevel['up_fxordermoney']*100);
	            	$showprogress = 1;
	            }
	        }
			
		}else{
			$hasnext = 0;
		}
		
		$showleveldown= false;
		$leveldowncommission=0;
		if(getcustom('member_level_down_commission') && $userlevel['down_level_totalcommission']>0){
			$member = Db::name('member')->where('id',mid)->field('id,isauto_down,totalcommission,down_commission')->find();
			$userinfo['isauto_down'] = $member['isauto_down'];
			$showleveldown=true;
			if(!$member['isauto_down']){
				$leveldowncommission =$userlevel['down_level_totalcommission']-($userinfo['totalcommission']-$member['down_commission']);
				$userinfo['leveldowncommission'] = $leveldowncommission;
				$leveldown = Db::name('member_level')->field('id,name')->where('id',$userlevel['down_level_id2'])->find();
				$userinfo['leveldownname'] = $leveldown['name'];
			}else{
				$pro = Db::name('shop_product')->field('id,name')->where('id',$userlevel['recovery_level_proid'])->find();
				$userinfo['buyproname'] = $pro['name'];
			}
		}
		$rdata = [];
		$rdata['userinfo'] = $userinfo;
		$rdata['nextlevel'] = $nextlevel??[];
		$rdata['userlevel'] = $userlevel;
		$rdata['hasnext'] = $hasnext;
		$rdata['showprogress'] = $showprogress;
		$rdata['showleveldown'] = $showleveldown;
		return $this->json($rdata);
	}
	//升级
	public function levelup(){
		if(request()->isPost()){
			$post = input('post.');
			$formdata = $post;
			if(!$this->member){
				return $this->json(['status'=>0,'msg'=>'参数错误,请重新操作']);
			}
			$this->member['ordercount'] = 0 + Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('status',3)->count();
			$this->member['ordermoney'] = 0 + Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('status',3)->sum('totalprice');
			$this->member['rechargemoney'] = 0 + Db::name('recharge_order')->where('aid',aid)->where('mid',mid)->where('status',1)->sum('money');

			$leveldata = Db::name('member_level')->where('aid',aid)->where('id',$formdata['levelid'])->find();
			if(!$leveldata['apply_paytxt']) $leveldata['apply_paytxt'] = '升级费用';
			//dump($formdata);
			if(!$leveldata){
				return $this->json(['status'=>0,'msg'=>'参数错误,请重新操作!']);
			}
            if(getcustom('taocan_product')){
                //查询会有最后一次降级时间
                //$down_level_time = Db::name('member_leveldown_record')->where('mid',mid)->order('createtime desc')->value('createtime');
                $down_level_time = Db::name('member_levelup_order')->where('mid',mid)->where('type',1)->order('createtime desc')->value('createtime');
                if( $leveldata['apply_taocan_proid']>0){
                    //查找套餐订单
                    $buy_taocan = Db::name('taocan_order_goods')
                        ->where('mid','=',mid)
                        ->where('proid','=',$leveldata['apply_taocan_proid'])
                        ->where('status','in','1,2,3')
                        ->where('createtime','>',$down_level_time?:0)
                        ->find();
                    if($buy_taocan){
                        $leveldata['apply_paymoney'] = 0;
                    }
                }
            }
			$canapply = 0;
			if($leveldata['apply_ordermoney'] <= 0 && $leveldata['apply_rechargemoney'] <= 0){
				$canapply = 1;
			}
			if ($leveldata['apply_ordermoney']>0 && $this->member['ordermoney'] >= $leveldata['apply_ordermoney']){
				$canapply = 1;
			}
			if ($leveldata['apply_rechargemoney']>0 && $this->member['rechargemoney'] >= $leveldata['apply_rechargemoney']){
				$canapply = 1;
			}
            $data = [];
            $data['aid'] = aid;
            $data['mid'] = mid;
			if(getcustom('levelup_code')){
                $code = $formdata['code']?trim($formdata['code']):'';
                if($code){
                	if($leveldata['can_apply'] != 1){
                		return $this->json(['status'=>0,'msg'=>'此等级申请功能暂未开启']);
                	}else{
                		if($code != $leveldata['apply_code']){
	                        return $this->json(['status'=>0,'msg'=>'验证码错误']);
	                    }else{
	                        $canapply = 1;
	                    }
                	}
                }
                if(getcustom('school_product')){
                    $data['school_id'] = input('post.school_id/d',0);
                    $data['grade_id'] = input('post.grade_id/d',0);
                    $data['class_id'] = input('post.class_id/d',0);
                }
            }
			if(!$canapply){
				return $this->json(['status'=>0,'msg'=>'不满足申请条件']);
			}
            if(getcustom('member_up_binding_tel')){
                $smscode = $formdata['smscode'];
                $tel = input('param.tel');
                if($smscode && $tel){
                    if(md5($tel.'-'.$smscode) != cache($this->sessionid.'_smscode') || cache($this->sessionid.'_smscodetimes')>5){
                        cache($this->sessionid.'_smscodetimes',cache($this->sessionid.'_smscodetimes')+1);
                        return $this->json(['status'=>0,'msg'=>'短信验证码错误']);
                    }
                }
            }
			//是否有待审核的记录
			$hasds = Db::name('member_levelup_order')->where('aid',aid)->where('mid',mid)->where('levelid',$leveldata['id'])->where('status',1)->find();
			if($hasds){
				return $this->json(['status'=>0,'msg'=>'您已经提交过了,请等待审核']);
			}
			if(getcustom('plug_xiongmao')){
				$hasds = Db::name('member_levelup_order')->where('aid',aid)->where('mid',mid)->find();
				if($hasds){
					return $this->json(['status'=>0,'msg'=>'您已经提交过了']);
				}
			}

			if($leveldata['maxnum'] > 0){
				$hascount = Db::name('member')->where('aid',aid)->where('levelid',$leveldata['id'])->count();
				if($hascount >= $leveldata['maxnum']) return $this->json(['status'=>0,'msg'=>'该等级申请名额已满']);
			}

			$return = [];
			$data['levelid'] = $leveldata['id'];
			$data['beforelevelid'] = $this->member['levelid'];

			$ordernum = date('ymdHis').aid.rand(1000,9999);
			$data['ordernum'] = $ordernum;
			$data['title'] = '升级成为'.$leveldata['name'];

			$mendiandata = [];
			$apply_formdata = json_decode($leveldata['apply_formdata'],true);

			foreach($apply_formdata as $k=>$v){
				$value = $formdata['form'.$k];
				if(is_array($value)){
					$value = implode(',',$value);
				}
				$value = strval($value);
				$data['form'.$k] = $v['val1'] . '^_^' .$value . '^_^' .$v['key'];
				if($v['val3']==1 && $value===''){
					return $this->json(['status'=>0,'msg'=>$v['val1'].' 必填']);
				}
				if($v['key']=='region' && getcustom('buy_selectmember')){
					$region = explode(',',$value);
					Db::name('member')->where('aid',aid)->where('id',mid)->update(['province'=>$region[0],'city'=>$region[1],'area'=>$region[2]]);
				}
				if(getcustom('member_level_add_apply_mendian') && $v['addmendian'] == 1){
					$mendiandata[$v['name']] = $value;
				}
			}
			if(getcustom('plug_xiongmao')){
				Db::name('member')->where('id',mid)->update(['realname'=>explode('^_^',$data['form0'])[1],'tel'=>explode('^_^',$data['form1'])[1]]);
			}
			if($leveldata['areafenhong']==1){
				$data['areafenhong_province'] = $post['areafenhong_province'];
				if($leveldata['areafenhongmaxnum'] > 0){
					$hascount = Db::name('member')->where('aid',aid)->where('levelid',$leveldata['id'])->where('areafenhong_province',$data['areafenhong_province'])->count();
					if($hascount >= $leveldata['areafenhongmaxnum']) return $this->json(['status'=>0,'msg'=>'该区域名额已满']);
				}
			}elseif($leveldata['areafenhong']==2){
				$data['areafenhong_province'] = $post['areafenhong_province'];
				$data['areafenhong_city'] = $post['areafenhong_city'];
				if($leveldata['areafenhongmaxnum'] > 0){
					$hascount = Db::name('member')->where('aid',aid)->where('levelid',$leveldata['id'])->where('areafenhong_province',$data['areafenhong_province'])->where('areafenhong_city',$data['areafenhong_city'])->count();
					if($hascount >= $leveldata['areafenhongmaxnum']) return $this->json(['status'=>0,'msg'=>'该区域名额已满']);
				}
			}elseif($leveldata['areafenhong']==3){
				$data['areafenhong_province'] = $post['areafenhong_province'];
				$data['areafenhong_city'] = $post['areafenhong_city'];
				$data['areafenhong_area'] = $post['areafenhong_area'];
				if($leveldata['areafenhongmaxnum'] > 0){
					$hascount = Db::name('member')->where('aid',aid)->where('levelid',$leveldata['id'])->where('areafenhong_province',$data['areafenhong_province'])->where('areafenhong_city',$data['areafenhong_city'])->where('areafenhong_area',$data['areafenhong_area'])->count();
					if($hascount >= $leveldata['areafenhongmaxnum']) return $this->json(['status'=>0,'msg'=>'该区域名额已满']);
				}
			}elseif($leveldata['areafenhong']==10){
				$data['areafenhong_largearea'] = $post['areafenhong_largearea'];
				if($leveldata['areafenhongmaxnum'] > 0){
					$hascount = Db::name('member')->where('aid',aid)->where('levelid',$leveldata['id'])->where('areafenhong_largearea',$data['areafenhong_largearea'])->count();
					if($hascount >= $leveldata['areafenhongmaxnum']) return $this->json(['status'=>0,'msg'=>'该区域名额已满']);
				}
			}

			$data['totalprice'] = $leveldata['apply_paymoney'];
			$data['createtime'] = time();
            $data['pid'] = $this->member['pid'];
            if(getcustom('mendian_member_levelup_fenhong')){
                $data['mdid'] = $post['mdid'] ?? 0;
            }

            // 门店入驻
            if(getcustom('member_level_add_apply_mendian') && !empty($mendiandata)){
            	$area = explode(',', $mendiandata['area']);
				$info = [];
				$info['aid'] = aid;
				$info['name'] = $mendiandata['name'];
				$info['tel'] = $mendiandata['tel'];
				$info['province'] = $area[0];
				$info['city'] = $area[1];
				$info['district'] = $area[2];
				$info['street'] = $area[3];
				$zuobiao = explode(',', $mendiandata['zuobiao']);
				$info['latitude'] = $zuobiao[0];
				$info['longitude'] = $zuobiao[1];
				$info['address'] = $mendiandata['address'];
				$info['status'] = 0;
				$info['check_status'] = 0;
				$info['createtime'] = time();
				$info['mid'] = mid;

				$level =  Db::name('mendian_level')->where('aid',aid)->where('isdefault',1)->find();
				$info['levelid'] = $level['id'];
				$group =  Db::name('mendian_group')->where('aid',aid)->where('isdefault',1)->find();
				$info['groupid'] = $group['id'];
				if($this->member['pid']){
					$pmendian = Db::name('mendian')->where('mid',$this->member['pid'])->where('aid',aid)->where('status',1)->find();
					if($pmendian){
						$info['pid'] = $this->member['pid'];
					}
				}
				$mendian = Db::name('mendian')->where('aid',aid)->where('mid',mid)->find();
				if($mendian && $mendian['check_status'] != 1){
					Db::name('mendian')->where('aid',aid)->where('id',$mendian['id'])->update($info);
					$mdid = $mendian['id'];
					$data['up_mdid'] = $mdid;
				}
				if(!$mendian){
					$mdid = Db::name('mendian')->insertGetId($info);
					$data['up_mdid'] = $mdid;
				}
            }
            
			if($leveldata['apply_paymoney'] > 0){
				$data['status'] = 0;
				$orderid = Db::name('member_levelup_order')->insertGetId($data);
				$payorderid = \app\model\Payorder::createorder(aid,0,$data['mid'],'member_levelup',$orderid,$data['ordernum'],$data['title'],$data['totalprice']);
				$return = ['status'=>1,'msg'=>'提交成功，正在跳转到支付','url'=>'/pagesExt/pay/pay?id='.$payorderid];
			}else{
				$data['status'] = 1;
				$orderid = Db::name('member_levelup_order')->insertGetId($data);
				\app\model\Payorder::member_levelup_pay($orderid);
                if($leveldata['apply_check']){
                    $return = ['status'=>1,'msg'=>'提交成功请等待审核','url'=>'/pages/my/usercenter'];
                }else{
                	if(getcustom('member_level_add_apply_mendian') && !empty($mdid)){
                		Db::name('mendian')->where('aid',aid)->where('mid',mid)->where('id',$mdid)->where('check_status',0)->update(['check_status'=>1,'status'=>1]);
                	}
                    $return = ['status'=>1,'msg'=>'申请成功','url'=>'/pages/my/usercenter'];
                }
			}
			$return['orderid'] = $orderid;
			return $this->json($return);
		}


		$member = Db::name('member')->field('id,realname,tel,weixin,aliaccount,bankcardnum,bankname,bankcarduser,levelid,pid')->where('aid',aid)->where('id',mid)->find();

		$member['ordermoney'] = Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('status',3)->sum('totalprice');
		$member['rechargemoney'] = Db::name('recharge_order')->where('aid',aid)->where('mid',mid)->where('status',1)->sum('money');

        $id = input('param.id/d');//传id为其他分组等级升级
        $cid = input('param.cid/d');//传cid为指定分组升级，

        if($cid && !$id) {
            $level_record = Db::name('member_level_record')->where('aid',aid)->where('mid', mid)->where('cid', $cid)->find();
            if(!empty($level_record)) {
                $id = $level_record['levelid'];
            }
        }
		if(!$id) $id = $member['levelid'];
		//我的等级
		$mylevel = Db::name('member_level')->where('aid',aid)->where('id',$id)->find();
        if(empty($mylevel)) {
            $mylevel['sort'] = 0;
            $mylevel['cid'] = 0;
        }
		$member['levelid'] = $mylevel['id'];
		//等级列
		$where = [];
		$where[] = ['aid','=',aid];
		//如果可跨级 则允许展示所有等级 如果不允许跨级 则只能显示比当前等级高的
        $skip_level = 0;
        if(getcustom('skip_levelup')){
            $skip_level = Db::name('member_level_bgset')->where('aid',aid)->value('skip_level');
        }
        if($skip_level==1){
            //不等于当前等级
//            $where[] = ['id','<>',$mylevel['id']];
        }else{
            $where[] = ['sort','>',$mylevel['sort']];
        }

        $canupwhere = '';
        if(getcustom('levelup_biglittlearea_yeji')){
        	$selfareayeji = $allareayeji = $bigareayeji = 0; //自己的已确认收货的业绩、全部业绩、最大区业绩
        	$canupwhere .= ' || ( up_bigarea_yeji >0 || up_littlearea_yeji>0)';
        }
		if(getcustom('levelup_code')){
			$levelid =  input('param.levelid')?input('param.levelid/d'):0;//等级id
			if($levelid>0){
				$where[] = ['id','=',$levelid];
			}
			$ycode =  input('param.ycode');//验证码
			if(isset($ycode)){
				$ycode = trim($ycode);
				if($ycode){
					$where[] = ['apply_code','=',$ycode];
					$where[] = ['can_apply','=',1];
				}else{
					$where[] = ['id','=',-1];
				}
			}else{
				$where[] = Db::raw("can_apply=1 || (can_up=1 && (up_wxpaymoney>0 || up_ordermoney>0 || up_rechargemoney>0 || up_getmembercard>0 || up_fxordermoney>0 || up_fxdowncount>0 || up_proid>0 ".$canupwhere."))");
			}
		}else{
			$where[] = Db::raw("can_apply=1 || (can_up=1 && (up_wxpaymoney>0 || up_ordermoney>0 || up_rechargemoney>0 || up_getmembercard>0 || up_fxordermoney>0 || up_fxdowncount>0 || up_proid>0 ".$canupwhere."))");
		}
		$aglevelList = Db::name('member_level')->where($where)->where('cid', $cid ? $cid : $mylevel['cid'])->order('sort')->select()->toArray();
		if(!$aglevelList) $aglevelList = [];
        //查询会有最后一次降级时间
        //$down_level_time = Db::name('member_leveldown_record')->where('mid',mid)->order('createtime desc')->value('createtime');
        $down_level_time = Db::name('member_levelup_order')->where('mid',mid)->where('type',1)->order('createtime desc')->value('createtime');
		foreach($aglevelList as $k=>$lv){
			if(getcustom('levelup_code')){
				$aglevelList[$k]['applycode']  = '';//增加一个额外的参数
				$aglevelList[$k]['apply_code'] = $aglevelList[$k]['apply_code']?true:false;//去掉验证码数据
			}
			if($lv['up_proid']){
                $aglevelList[$k]['up_proname'] = '';
			    $up_proid = explode(',', $lv['up_proid']);
                $up_pronum = explode(',', $lv['up_pronum']);
                if($up_proid)
				    $up_pro = Db::name('shop_product')->whereIn('id',$up_proid)->field('name')->select()->toArray();
                if($up_pro) {
                    foreach ($up_pro as $pk => $pro) {
                        if($pro['name'] && $up_pronum[$pk]) {
                            if($pk) {
                                $aglevelList[$k]['up_proname'] .= ' + ';
                            }
                            $aglevelList[$k]['up_proname'] .= $pro['name'].'x'.$up_pronum[$pk];
                        }
                    }
                }
			}
            $atj = array();
			//申请
            $aglevelList[$k]['applytj_reach'] = 0;
            $aglevelList[$k]['applytj'] = '';
            if($lv['can_apply'] == 1){
                if(getcustom('levelup_apply_range')){
                    //apply_level_range申请等级：1所有等级，2小于等于推荐人等级，3小于推荐人等级
                    if ($member['pid'] && $mylevel['apply_level_range'] == 2) {
                        $parent = Db::name('member')->where('aid',aid)->where('id',$member['pid'])->find();
                        if($parent) $parentLevel = Db::name('member_level')->where('aid',aid)->where('id',$parent['levelid'])->find();
                        if($parentLevel && $lv['sort'] > $parentLevel['sort']){
                            unset($aglevelList[$k]);
                            continue;
                        }
                    }
                    elseif ($member['pid'] && $mylevel['apply_level_range'] == 3) {
                        $parent = Db::name('member')->where('aid',aid)->where('id',$member['pid'])->find();
                        if($parent) $parentLevel = Db::name('member_level')->where('aid',aid)->where('id',$parent['levelid'])->find();
                        if($parentLevel && $lv['sort'] >= $parentLevel['sort']){
                            unset($aglevelList[$k]);
                            continue;
                        }
                    }
                }
                if (empty($lv['apply_ordermoney'])) $lv['apply_ordermoney'] = 0;
                if (empty($lv['apply_rechargemoney'])) $lv['apply_rechargemoney'] = 0;
                if ($lv['apply_ordermoney'] <= 0 && $lv['apply_rechargemoney'] <= 0) {
                    $aglevelList[$k]['applytj_reach'] = 1;
                }
                if($lv['apply_ordermoney'] > 0) {
                    $atj[]='累计订单金额满'.$lv['apply_ordermoney'].'元';
                    if($member['ordermoney'] >= $lv['apply_ordermoney']) {
                        $aglevelList[$k]['applytj_reach'] = 1;
                    }
                }
                if($lv['apply_rechargemoney'] > 0) {
                    $atj[]='累计充值金额满'.$lv['apply_rechargemoney'].'元';
                    if($member['rechargemoney'] >= $lv['apply_rechargemoney']) {
                        $aglevelList[$k]['applytj_reach'] = 1;
                    }
                }
                if(getcustom('taocan_product')){
                    if( $lv['apply_taocan_proid']>0){
                        $atj[]='购买套餐商品ID'.$lv['apply_taocan_proid'];
                        //查找套餐订单
                        $buy_taocan = Db::name('taocan_order_goods')
                            ->where('mid','=',mid)
                            ->where('proid','=',$lv['apply_taocan_proid'])
                            ->where('status','in','1,2,3')
                            ->where('createtime','>',$down_level_time)
                            ->find();
                    }
                }
                if($atj){
                    $aglevelList[$k]['applytj'] = implode(' 或 ',$atj);
                }
            }


            //升级
            if($lv['can_up']){
                $tj = array();
                //if($v['up_ordercount'] > 0) $tj[]='订单满'.$v['up_ordercount'].'个';
                if($lv['up_wxpaymoney'] > 0) $tj['up_wxpaymoney']='微信支付金额满'.$lv['up_wxpaymoney'].'元';
                if($lv['up_ordermoney'] > 0) $tj['up_ordermoney']='订单金额满'.$lv['up_ordermoney'].'元';
                if($lv['up_rechargemoney'] > 0) $tj['up_rechargemoney']='充值金额满'.$lv['up_rechargemoney'].'元';
                if($lv['up_perpaymoney'] > 0) $tj['up_perpaymoney']='单次消费满'.$lv['up_perpaymoney'].'元';
                if(getcustom('member_levelup_orderprice')){
                    if($lv['up_orderprice'] > 0) $tj['up_orderprice']='单次订单满'.$lv['up_orderprice'].'元';
                }
//                if($lv['up_fxordermoney'] > 0) $tj['up_fxordermoney']='分销订单满'.$lv['up_fxordermoney'].'元';
//                if($lv['up_fxdowncount'] > 0) $tj['up_fxdowncount']='下级总人数满'.$lv['up_fxdowncount'].'个';

                if($lv['up_fxordermoney'] > 0){
                    $up_fxdownlevelid_and_name = '';
                    if(!empty($lv['up_fxorderlevelid'])){
                        $up_fxorderlevelid_arr = explode(',',$lv['up_fxorderlevelid']);
                        foreach ($up_fxorderlevelid_arr as $vll){
                            $member_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vll)->value('name') ?? '';
                            $up_fxdownlevelid_and_name .= $member_level_name . '/';
                        }
                    }

                    $up_fxdownlevelid_and_name = $up_fxdownlevelid_and_name ? '['.rtrim($up_fxdownlevelid_and_name,'/').']' : '会员';

                    $p_renshu = empty($lv['up_fxorderlevelnum']) ? '' : $lv['up_fxorderlevelnum'];
                    $tj['up_fxordermoney'] = '下'. $p_renshu .'级'.$up_fxdownlevelid_and_name.'总订单金额满'.$lv['up_fxordermoney'].'元';
                }

                if($lv['up_fxdowncount'] > 0){
                    $up_fxdownlevelid_and_name = '';
                    if(!empty($lv['up_fxdownlevelid'])){
                        $up_fxorderlevelid_arr = explode(',',$lv['up_fxdownlevelid']);
                        foreach ($up_fxorderlevelid_arr as $vll){
                            $member_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vll)->value('name') ?? '';
                            $up_fxdownlevelid_and_name .= $member_level_name . '/';
                        }
                    }

                    $up_fxdownlevelid_and_name = $up_fxdownlevelid_and_name ? '['.rtrim($up_fxdownlevelid_and_name,'/').']' : '会员';

                    $p_renshu = empty($lv['up_fxdownlevelnum']) ? '' : $lv['up_fxdownlevelnum'];
                    $tj['up_fxdowncount'] = '下'. $p_renshu .'级'.$up_fxdownlevelid_and_name.'总人数满'.$lv['up_fxdowncount'].'个';
                }

                if($lv['up_fxdowncount2'] > 0){
                    $up_fxdownlevelid_and_name = '';
                    if(!empty($lv['up_fxdownlevelid2'])){
                        $up_fxorderlevelid_arr = explode(',',$lv['up_fxdownlevelid2']);
                        foreach ($up_fxorderlevelid_arr as $vll){
                            $member_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vll)->value('name') ?? '';
                            $up_fxdownlevelid_and_name .= $member_level_name . '/';
                        }
                    }

                    $up_fxdownlevelid_and_name = $up_fxdownlevelid_and_name ? '['.rtrim($up_fxdownlevelid_and_name,'/').']' : '会员';

                    $p_renshu = empty($lv['up_fxdownlevelnum2']) ? '' : $lv['up_fxdownlevelnum2'];
                    $tj['up_fxdowncount2'] = '下'. $p_renshu .'级'.$up_fxdownlevelid_and_name.'总人数满'.$lv['up_fxdowncount2'].'个';
                }

                if($lv['up_fxdowncount_and'] > 0){
                    $up_fxdownlevelid_and_name = '';
                    if(!empty($lv['up_fxdownlevelid_and'])){
                        $up_fxorderlevelid_arr = explode(',',$lv['up_fxdownlevelid_and']);
                        foreach ($up_fxorderlevelid_arr as $vll){
                            $member_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vll)->value('name') ?? '';
                            $up_fxdownlevelid_and_name .= $member_level_name . '/';
                        }
                    }

                    $up_fxdownlevelid_and_name = $up_fxdownlevelid_and_name ? '['.rtrim($up_fxdownlevelid_and_name,'/').']' : '会员';

                    $p_renshu = empty($lv['up_fxdownlevelnum_and']) ? '' : $lv['up_fxdownlevelnum_and'];
                    $tj['up_fxdowncount_and']='下'. $p_renshu .'级'.$up_fxdownlevelid_and_name.'总人数满'.$lv['up_fxdowncount_and'].'个';
                }

                if($lv['up_fxdowncount2_and'] > 0){
                    $up_fxdownlevelid_and_name = '';
                    if(!empty($lv['up_fxdownlevelid2_and'])){
                        $up_fxorderlevelid_arr = explode(',',$lv['up_fxdownlevelid2_and']);
                        foreach ($up_fxorderlevelid_arr as $vll){
                            $member_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vll)->value('name') ?? '';
                            $up_fxdownlevelid_and_name .= $member_level_name . '/';
                        }
                    }

                    $up_fxdownlevelid_and_name = $up_fxdownlevelid_and_name ? '['.rtrim($up_fxdownlevelid_and_name,'/').']' : '会员';

                    $p_renshu = empty($lv['up_fxdownlevelnum2_and']) ? '' : $lv['up_fxdownlevelnum2_and'];
                    $tj['up_fxdowncount2_and']='下'. $p_renshu .'级'.$up_fxdownlevelid_and_name.'总人数满'.$lv['up_fxdowncount2_and'].'个';
                }

                if(getcustom('levelup_shop_childre_number')){
                    if($lv['up_shopordermoney'] > 0) $tj['up_shopordermoney']='商城订单金额满'.$lv['up_shopordermoney'].'元';

                    if($lv['up_childre_shopordermoney'] > 0){
                        $up_childre_shopordermoney_name = '';

                        if(!empty($lv['up_childre_shopordermoney_levelid'])){
                            $up_childre_shopordermoney_arr = explode(',',$lv['up_childre_shopordermoney_levelid']);
                            foreach ($up_childre_shopordermoney_arr as $vl){
                                $hildre_shopordermoney_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vl)->value('name') ?? '';
                                $up_childre_shopordermoney_name .= $hildre_shopordermoney_level_name . '/';
                            }
                        }

                        $up_childre_shopordermoney_name = $up_childre_shopordermoney_name ? '['.rtrim($up_childre_shopordermoney_name,'/').']' : '会员';
                        $p_renshu = empty($lv['up_childre_shopordermoney_num']) ? '' : $lv['up_childre_shopordermoney_num'];
                        $tj['up_childre_shopordermoney'] = '下'. $p_renshu .'级'.$up_childre_shopordermoney_name.'订单总金额满'.$lv['up_childre_shopordermoney'].'元';
                    }

                    if($lv['up_childre_number'] > 0){
                        $childre_number_level_name = '';

                        if(!empty($lv['up_childre_number_levelid'])){
                            $up_childre_number_arr = explode(',',$lv['up_childre_number_levelid']);
                            foreach ($up_childre_number_arr as $vl){
                                $childre_number_level_name = Db::name('member_level')->where('aid',aid)->where('id',$vl)->value('name') ?? '';
                                $childre_number_level_name .= $childre_number_level_name . '/';
                            }
                        }

                        $childre_number_level_name = $childre_number_level_name ? '['.rtrim($childre_number_level_name,'/').']' : '会员';
                        $p_renshu = empty($lv['up_childre_number_num']) ? '' : $lv['up_childre_number_num'];
                        $tj['up_childre_number'] = '下'. $p_renshu .'级'.$childre_number_level_name.'总人数满'.$lv['up_childre_number'].'个';
                    }
                }

                if($lv['up_regtime_and'] > 0){
                    $tj['up_regtime_and']='注册'. $lv['up_regtime_and'] .'天内（包含）';
                }

                $prooo = '';
                if($lv['up_proid'] && $lv['up_pronum']){

                    $pi = explode(',',$lv['up_proid']);
                    $pn = explode(',',$lv['up_pronum']);

                    foreach ($pi as $kk => $vv){
                        if($pro = Db::name('shop_product')->where('aid',aid)->where('id',$vv)->value('name')){
                            if(count($pn) == 1){
                                $prooo .= $pro. ' + ';
                            }else{
                                $pnum = $pn[$kk] ?? 1;
                                $prooo .= '购买商品['.$pro.']*'. $pnum . '；';
                            }
                        }
                    }

                    if(count($pn) == 1){
                        $tj['up_proid'] = '购买商品['.rtrim($prooo,' + ').'] 总数量到达'.$pn[0].'件';
                    }else{
                        $tj['up_proid'] = rtrim($prooo,'；');
                    }
                }

                $prooo = '';
                if($lv['up_proid2'] && $lv['up_pronum2']){

                    $pi = explode(',',$lv['up_proid2']);
                    $pn = explode(',',$lv['up_pronum2']);

                    foreach ($pi as $kk => $vv){
                        if($pro = Db::name('shop_product')->where('aid',aid)->where('id',$vv)->value('name')){
                            if(count($pn) == 1){
                                $prooo .= $pro. ' + ';
                            }else{
                                $pnum = $pn[$kk] ?? 1;
                                $prooo .= '购买商品['.$pro.']*'. $pnum . '；';
                            }
                        }
                    }

                    if(count($pn) == 1){
                        $tj['up_proid2'] = '购买商品['.rtrim($prooo,' + ').'] 总数量到达'.$pn[0].'件';
                    }else{
                        $tj['up_proid2'] = rtrim($prooo,'；');
                    }
                }

                if(getcustom('levelup_small_market_yeji')){
                    if($lv['up_small_market_yeji'] > 0){
                        if($lv['up_small_market_yeji'] > 0){
                            if(!empty($lv['up_small_market_yeji_proids'])){
                                $proarr = explode(',',$lv['up_small_market_yeji_proids']);
                                $prooo = '';
                                foreach ($proarr as $kk => $vv){
                                    if($pro = Db::name('shop_product')->where('aid',aid)->where('id',$vv)->value('name')){
                                        $prooo .= $pro. '；';
                                    }
                                }
                                $tj['up_small_market_yeji'] = '小市场购买商品['.rtrim($prooo,'；').'] 总业绩满'.$lv['up_small_market_yeji'].'元';
                            }else{
                                $tj['up_small_market_yeji'] = '小市场总业绩满'.$lv['up_small_market_yeji'].'元';
                            }
                        }
                    }
                }

                if(getcustom('levelup_small_market_num_product')){
                    if($lv['up_small_market_num'] > 0){
                        if(!empty($lv['up_small_market_num_proids'])){
                            $proarr = explode(',',$lv['up_small_market_num_proids']);
                            $prooo = '';
                            foreach ($proarr as $kk => $vv){
                                if($pro = Db::name('shop_product')->where('aid',aid)->where('id',$vv)->value('name')){
                                    $prooo .= $pro. '；';
                                }
                            }
                            $tj['up_small_market_num'] = '小市场购买商品['.rtrim($prooo,'；').'] 总数量满'.$lv['up_small_market_num'].'件';
                        }else{
                            $tj['up_small_market_num'] = '小市场总数量满'.$lv['up_small_market_num'].'件';
                        }
                    }
                }

                if(getcustom('levelup_selfanddown_order_num')){
                    if($lv['up_selfanddown_order_num'] > 0){
                        if(!empty($lv['up_selfanddown_order_num_proids'])){
                            $proarr = explode(',',$lv['up_selfanddown_order_num_proids']);
                            $prooo = '';
                            foreach ($proarr as $kk => $vv){
                                if($pro = Db::name('shop_product')->where('aid',aid)->where('id',$vv)->value('name')){
                                    $prooo .= $pro. '；';
                                }
                            }
                            $tj['up_selfanddown_order_num'] = '自己和下级购买商品['.rtrim($prooo,'；').'] 总单数满'.$lv['up_selfanddown_order_num'].'单';
                        }else{
                            $tj['up_selfanddown_order_num'] = '自己和下级总下单数量满'.$lv['up_selfanddown_order_num'].'单';
                        }
                    }
                }

                if(getcustom('levelup_selfanddown_order_product_num')){
                    if($lv['up_selfanddown_order_product_num'] > 0){
                        //下级会员
                        $mids3 = \app\common\Member::getdownmids(aid,mid);
                        //加入自己
                        $mids3[] = mid;
                        //是否有商品条件
                        $prowhere = [];
                        if(!empty($lv['up_selfanddown_order_product_num_proids'])){
                            $proarr = explode(',',$lv['up_selfanddown_order_product_num_proids']);
                            if($proarr){
                                $prowhere[] = ['proid','in',$proarr];
                            }
                        }
                        //按订单商品数量
                        $goods = Db::name('shop_order_goods')->where('mid','in',$mids3)->where('status','in',[1,2,3])->where($prowhere)->where('aid',aid)->fieldRaw('sum(num) num,sum(refund_num) refund_num')->find();
                        $pro_num = 0;
                        if($goods){
                            $pro_num = $goods['num']-$goods['refund_num'];
                        }
                        $progress = '(已完成：'. $pro_num .'件)';

                        if(!empty($lv['up_selfanddown_order_product_num_proids'])){
                            $proarr = explode(',',$lv['up_selfanddown_order_product_num_proids']);
                            $prooo = '';
                            foreach ($proarr as $kk => $vv){
                                if($pro = Db::name('shop_product')->where('aid',aid)->where('id',$vv)->value('name')){
                                    $prooo .= $pro. '；';
                                }
                            }
                            $tj['up_selfanddown_order_product_num'] = '自己和下级购买商品['.rtrim($prooo,'；').'] 总数量满'.$lv['up_selfanddown_order_product_num'].'件'.$progress;
                        }else{
                            $tj['up_selfanddown_order_product_num'] = '自己和下级总下单商品数量满'.$lv['up_selfanddown_order_product_num'].'件'.$progress;
                        }
                    }
                }

                if($lv['up_getmembercard']==1) $tj['up_getmembercard']='领取微信会员卡';
                if(getcustom('member_levelup_businessnum')){
                    if($lv['up_businessnum'] > 0) $tj['up_businessnum']='推荐商家成功入驻数量满'.$lv['up_businessnum'].'个';
                }
                if(getcustom('member_up_binding_tel')){
                    if($lv['up_binding_tel']>0)$tj['up_binding_tel']='绑定手机号';
                }
                if(getcustom('levelup_teamnum_peoplenum')){
                    $up_team_path_num_tj = '';
                    if($lv['up_team_path_num']>0) {
                        $up_team_path_num_tj .='团队满'.$lv['up_team_path_num'].'条线';
                        if($lv['up_team_people_num']>0){
                            $up_team_path_num_tj .='，每条线超'.$lv['up_team_people_num'].'人';
                            if($lv['up_team_path_level']){
                                $up_team_path_num_tj .='等级ID：'.$lv['up_team_path_level'];
                            }
                        }
                    }
                    if($up_team_path_num_tj){
                        $tj['up_team_path_num']=$up_team_path_num_tj;
                    }
                }
                if(getcustom('levelup_wx_channels')){
                    $prooo = '';
                    if($lv['up_wxchannels_proid'] && $lv['up_wxchannels_pronum']){
                        $pi = explode(',',$lv['up_wxchannels_proid']);
                        $pn = explode(',',$lv['up_wxchannels_pronum']);
                        foreach ($pi as $kk => $vv){
                            if($pro = Db::name('channels_product')->where('aid',aid)->where('id',$vv)->value('name')){
                                if(count($pn) == 1){
                                    $prooo .= $pro. ' + ';
                                }else{
                                    $pnum = $pn[$kk] ?? 1;
                                    $prooo .= '购买商品['.$pro.']*'. $pnum . '；';
                                }
                            }
                        }
                        if(count($pn) == 1){
                            $tj['up_wxchannels_proid'] = '购买小店商品['.rtrim($prooo,' + ').'] 总数量到达'.$pn[0].'件';
                        }else{
                            $tj['up_wxchannels_proid'] = rtrim($prooo,'；');
                        }
                    }
                }
                if(getcustom('levelup_biglittlearea_yeji')){
                    $up_biglittlearea_yeji = '';
                    if($lv['up_bigarea_yeji']>0){
                        $up_biglittlearea_yeji .= '大区业满：'.$lv['up_bigarea_yeji'].'元';
                    }
                    if($lv['up_littlearea_yeji']>0){
                        $up_biglittlearea_yeji .= $up_biglittlearea_yeji?' 且 ':'';
                        $up_biglittlearea_yeji .= '小区业满：'.$lv['up_littlearea_yeji'].'元';
                    }
                    if($up_biglittlearea_yeji){
                        $tj['up_biglittlearea_yeji'] = $up_biglittlearea_yeji;

                        //大区是否包含自己的已确认收货的业绩
                        if($lv['up_bigarea_yeji']>0 && $lv['up_bigarea_yeji_self'] == 1 && $selfareayeji <=0){
                            //团队业绩 0:统计下单时参与商品 1：统计现在订单参与商品
                            if($lv['up_biglittlearea_yeji_join'] == 1){
                                $selfareayeji = Db::name('shop_order_goods')->alias('og')
                                    ->join('shop_product p','p.id = og.proid')
                                    ->where('og.mid',mid)->where('p.biglittlearea_yeji',1)->where('og.status',3)->sum('og.real_totalprice');
                            }else{
                                $selfareayeji = Db::name('shop_order_goods')->where('mid',mid)->where('biglittlearea_yeji',1)->where('status',3)->sum('real_totalprice');
                            }
                        }

                        //分别统计各下级及伞下已确认收货的业绩
                        if($allareayeji<=0){
                        	$bigareayeji = 0;
                            //查询自己的直推下级
                            $childmids = Db::name('member')->where('pid',mid)->column('id');
                            if($childmids){
                                foreach($childmids as $cmid){
                                    //查询下级的伞下
                                    $downmids = \app\common\Member::getdownmids($aid,$cmid);
                                    if($downmids){
                                        $downmids[] = $cmid;
                                    }else{
                                        $downmids = [$cmid];
                                    }
                                    //团队业绩 0:统计下单时参与商品 1：统计现在订单参与商品
                                    if($lv['up_biglittlearea_yeji_join'] == 1){
                                        $childyeji = Db::name('shop_order_goods')->alias('og')
                                            ->join('shop_product p','p.id = og.proid')
                                            ->where('p.biglittlearea_yeji',1)->where('og.mid','in',$downmids)->where('og.status',3)->sum('og.real_totalprice');
                                    }else{
                                        $childyeji = Db::name('shop_order_goods')->where('mid','in',$downmids)->where('biglittlearea_yeji',1)->where('status',3)->sum('real_totalprice');
                                    }
                                    if($childyeji>$bigareayeji) $bigareayeji = $childyeji;
                                    $allareayeji += $childyeji;
                                }
                                unset($cmid);
                            }
                        }

                        $allareayeji2 = $allareayeji;//全部业绩
                        $bigareayeji2 = $bigareayeji;//最大区业绩

                        $littleareayeji = $allareayeji2 - $bigareayeji2;//小区业绩
                        if($littleareayeji<0) $littleareayeji = 0;

                        $showselfareayeji = '';
                        if($lv['up_bigarea_yeji']>0 && $lv['up_bigarea_yeji_self'] == 1 ){
                            $bigareayeji2 += $selfareayeji;//大区业绩加上自己的业绩
                            $showselfareayeji = '(包含自己业绩：'.$selfareayeji.')';
                        }
                        if($bigareayeji2<0) $bigareayeji2 = 0;

                        $tj['up_biglittlearea_yeji2'] = '（当前';
                        if($lv['up_bigarea_yeji']>0){
                        	$tj['up_biglittlearea_yeji2'] .= '大区总业绩：'.$bigareayeji2.$showselfareayeji.'；';
                        }
                        if($lv['up_littlearea_yeji']>0){
                        	$tj['up_biglittlearea_yeji2'] .= '小区总业绩：'.$littleareayeji;
                        }
                        $tj['up_biglittlearea_yeji2'] .= '）';
                    }
                }

                if($tj){
                    $i = 1;
                    $aglevelList[$k]['autouptj'] = '';
                    foreach($tj as $key => $item) {
                        $realtion = ' 或 ';
                        if($lv['up_fxorder_condition'] == 'and' && $key == 'up_fxordermoney') {
                            $realtion = ' 且 ';
                        }
                        if (getcustom('member_levelup_orderprice') && $lv['up_orderprice_condition'] == 'and' && $key == 'up_orderprice') {
                            $realtion = ' 且 ';
                        }
                        if($key == 'up_fxdowncount_and' || $key == 'up_fxdowncount2' || $key == 'up_fxdowncount2_and' || $key == 'up_regtime_and') {
                            $realtion = ' 且 ';
                        }
                        if($lv['up_buygoods_condition'] == 'and' && $key == 'up_proid') {
                            $realtion = ' 且 ';
                        }
                        if(getcustom('member_up_binding_tel')){
                            if($lv['up_binding_tel_condition'] == 'and' && $key == 'up_binding_tel') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('levelup_teamnum_peoplenum')){
                            if($lv['up_team_path_condition'] == 'and' && $key == 'up_team_path_num') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('levelup_small_market_yeji')){
                            if($lv['up_small_market_yeji_condition'] == 'and' && $key == 'up_small_market_yeji') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('levelup_small_market_num_product')){
                            if($lv['up_small_market_num_condition'] == 'and' && $key == 'up_small_market_num') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('levelup_selfanddown_order_num')){
                            if($lv['up_selfanddown_order_num_condition'] == 'and' && $key == 'up_selfanddown_order_num') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('levelup_selfanddown_order_product_num')){
                            if($lv['up_selfanddown_order_product_num_condition'] == 'and' && $key == 'up_selfanddown_order_product_num') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('member_levelup_businessnum')){
                            if($lv['up_businessnum_condition'] && $lv['up_businessnum_condition'] == 'and' && $key == 'up_businessnum') {
                                $realtion = ' 且 ';
                            }
                        }
                        if(getcustom('levelup_wx_channels')){
                        	if($lv['up_wxchannels_buygoods_condition'] == 'and' && $key == 'up_wxchannels_proid') {
	                            $realtion = ' 且 ';
	                        }
                        }
                        if(getcustom('levelup_biglittlearea_yeji')){
                            if($key == 'up_biglittlearea_yeji' && $lv['up_biglittlearea_yeji_condition'] == 'and' ) {
                                $realtion = ' 且 ';
                            }
                        }

                        if(getcustom('levelup_shop_childre_number')){
                            if($key == 'up_shopordermoney' && $lv['up_shop_childre_number_condition'] == 'and' ) {
                                $realtion = ' 且 ';
                            }
                            if($key == 'up_childre_shopordermoney' || $key == 'up_childre_number') {
                                $realtion = ' 且 ';
                            }
                        }

                        if($i == 1) {
                            $prefix = '';
                            if($realtion == ' 且 '){
                                $prefix = '必须满足';
                            }
                            $aglevelList[$k]['autouptj'] .= $prefix.$item;
                        } else {
                            $aglevelList[$k]['autouptj'] .= $realtion.$item;
                        }
                        $i++;
                    }
                }else{
                    $aglevelList[$k]['autouptj'] = '不自动升级';
                }

            }
            else{
                $aglevelList[$k]['autouptj'] = '不自动升级';
                if($lv['isdefault']){
                    $aglevelList[$k]['autouptj'] = '默认等级无需升级';
                }
            }

			$aglevelList[$k]['apply_formdata'] = json_decode($lv['apply_formdata'],true);
			if(!$aglevelList[$k]['apply_paytxt']) $aglevelList[$k]['apply_paytxt'] = '升级费用';
			if(getcustom('up_level_agree3')){
                $aglevelList[$k]['is_agree'] = $lv['is_agree'];
                $aglevelList[$k]['agree_content'] = $lv['agree_content'];
            }else{
                $aglevelList[$k]['is_agree'] = 0;
            }
		}

		$set = Db::name('admin_set')->where('aid',aid)->field('name,logo,desc,banner_levelup')->find();

        $set['mendian_member_levelup_fenhong'] = false;
        if(getcustom('mendian_member_levelup_fenhong') && input('param.mdid')){
            $set['mendian_member_levelup_fenhong'] = true;
            $mdinfo = Db::name('mendian')->where('aid',aid)->where('id',input('param.mdid'))->field('name,id')->find();
            $set['mdname'] =  $mdinfo ? $mdinfo['name'] : '';
            $set['mdid'] =  $mdinfo ? $mdinfo['id'] : '';
        }
		
		$rdata = [];
		$rdata['sysset'] = $set;
		$rdata['userinfo'] = $member;
		$rdata['aglevelList'] = array_values($aglevelList);
		$rdata['userlevel'] = $mylevel;
		if(getcustom('areafenhong_jiaquan')){
			$largearea = Db::name('largearea')->where('aid',aid)->where('status',1)->order('sort desc,id')->column('name');
			$rdata['largearea'] = $largearea;
		}
		if(getcustom('levelup_code')){
			$rdata['levelupcode'] = true;
			if(getcustom('school_product')){
			    if($ycode){
                    $need_school = Db::name('admin')->where('id',aid)->value('need_school');
                    if($need_school==1){
                        //查询该学校下面的班级
                        $school = Db::name('school')->where('aid',aid)->where('number',$ycode)->find();
                        if(empty($school)){
                            return $this->json(['status'=>0,'msg'=>'错误的学校编码']);
                        }
                        $gradelist = Db::name('school_class')->where('aid',aid)->where('sid',$school['id'])->where('pid',0)->select()->toArray();
                        foreach ($gradelist as $sk=>$sv){
                            $classlist = Db::name('school_class')->where('aid',aid)->where('sid',$school['id'])->where('pid',$sv['id'])->select()->toArray();
                            $gradelist[$sk]['classlist'] = $classlist??[];
                        }
                        $rdata['need_school'] = 1;
                        $rdata['school_id'] = $school['id'];
                        $rdata['gradelist'] = $gradelist;
                        return $this->json($rdata);
                    }
                }
            }
	    }
	    if(getcustom('levelup_code') || getcustom('levelup_bg')){
			$rdata['bgset'] = '';
			//查询背景设置
			$bgset = Db::name('member_level_bgset')->where('aid',aid)->find();
			if($bgset){
				$rdata['bgset'] = [
					'title'      => $bgset['title'],
					'level_name' => $bgset['level_name'],
					'bgcolor'    => $bgset['bgcolor'],
					'bgimg'      => $bgset['bgimg'],
				];
			}
		}
		return $this->json($rdata);
	}
	function getPayCommissionApplyOrder(){
		$post = input('post.');
		$order = Db::name('member_levelup_order')->field('title,totalprice,ordernum,id')->where('id',$post['orderid'])->where('aid',aid)->where('mid',mid)->find();
		if(!$order){
			return $this->json(['status'=>0,'msg'=>'该订单不存在']);
		}
		if($order['status']){
			return $this->json(['status'=>0,'msg'=>'该订单已支付']);
		}
		return $this->json(['status'=>1,'msg'=>'获取成功','orderinfo'=>$order]);
	}
	function payCommissionApplyOrder(){
		$post = input('post.');
		$order = Db::name('member_levelup_order')->where('id',$post['orderid'])->where('aid',aid)->where('mid',mid)->find();
		if(!$order){
			return $this->json(['status'=>0,'msg'=>'该订单不存在']);
		}
		if($order['status'] > 0){
			return $this->json(['status'=>0,'msg'=>'该订单已支付']);
		}
		$leveldata = Db::name('member_level')->where('aid',aid)->where('id',$order['levelid'])->find();

		$levelid = $leveldata['id'];

		if($order['totalprice'] <=0){
			Db::name('member_levelup_order')->where('id',$order['id'])->update(['status'=>1]);
			if($leveldata['apply_check']){
				Db::name('member')->where('aid',aid)->where('id',mid)->update(['realname'=>$order['realname'],'tel'=>$order['tel'],'weixin'=>$order['weixin'],'aliaccount'=>$order['aliaccount'],'bankcardnum'=>$order['bankcardnum'],'bankcarduser'=>$order['bankcarduser'],'bankname'=>$order['bankname']]);
				$return = ['status'=>2,'msg'=>'付款成功请等待审核'];
			}else{
				Db::name('member')->where('aid',aid)->where('id',mid)->update(['realname'=>$order['realname'],'tel'=>$order['tel'],'weixin'=>$order['weixin'],'aliaccount'=>$order['aliaccount'],'bankcardnum'=>$order['bankcardnum'],'bankcarduser'=>$order['bankcarduser'],'bankname'=>$order['bankname'],'levelid'=>$levelid]);
				Db::name('member_levelup_order')->where('id',$order['id'])->update(['status'=>2]);
				$return = ['status'=>3,'msg'=>'申请成功'];
			}
			return $return;
		}
		//余额支付
		if($post['typeid']==2){
			if($this->member['money'] < $order['totalprice']){
				return $this->json(['status'=>0,'msg'=>t('余额').'不足,请充值']);
			}
			Db::name('member_levelup_order')->where('id',$order['id'])->update(['paytype'=>t('余额').'支付','paytime'=>time(),'status'=>1]);
			//减去会员的余额
			\app\common\Member::addmoney(aid,mid,-$order['totalprice'],$order['title']);

			if($leveldata['apply_check']){
				Db::name('member')->where('aid',aid)->where('id',mid)->update(['realname'=>$order['realname'],'tel'=>$order['tel'],'weixin'=>$order['weixin'],'aliaccount'=>$order['aliaccount'],'bankcardnum'=>$order['bankcardnum'],'bankcarduser'=>$order['bankcarduser'],'bankname'=>$order['bankname']]);
				$return = ['status'=>2,'msg'=>'付款成功请等待审核'];
			}else{
				Db::name('member')->where('aid',aid)->where('id',mid)->update(['realname'=>$order['realname'],'tel'=>$order['tel'],'weixin'=>$order['weixin'],'aliaccount'=>$order['aliaccount'],'bankcardnum'=>$order['bankcardnum'],'bankcarduser'=>$order['bankcarduser'],'bankname'=>$order['bankname'],'levelid'=>$levelid]);
				Db::name('member_levelup_order')->where('id',$order['id'])->update(['status'=>2]);
				$return = ['status'=>3,'msg'=>'申请成功'];
			}
			return $return;
		}else{
			$rs = \app\common\Wxpay::build(aid,mid,$this->member['openid'],'升级['.$leveldata['name'].']',$order['ordernum'],$order['totalprice'],'member_levelup_order');
			$rs['apply_check'] = $leveldata['apply_check'];
			return $rs;
		}
	}
	//领卡回调
	public function usergetcard(){
		$post = input('post.');
		Log::write('领取会员卡---'.mid.'---'.print_r($post,true));
		$extraData = $post['extraData'];
		$code = $extraData['code'];
		$activate_ticket = $extraData['activate_ticket'];
		$card_id = $extraData['card_id'];
		if($this->member['activate_ticket'] && $this->member['activate_ticket'] == $activate_ticket){
			return $this->json(['status'=>1,'msg'=>'已开卡']);
		}
		$url = urldecode($extraData['wx_activate_after_submit_url']);
		$params =  explode('&',explode('?',$url)[1]);
		$mpopenid = '';
		foreach($params as $v){
			if($v!=''){
				$vArr = explode('=',$v);
				if($vArr[0] == 'openid'){
					$mpopenid = $vArr[1];
				}
			}
		}
		Log::write('公众号openid '.$mpopenid);

		$mdata = [];
		$mdata['mpopenid'] = $mpopenid;
		$mdata['card_id'] = $card_id;
		$mdata['card_code'] = $code;
		$mdata['activate_ticket'] = $activate_ticket;

		Db::name('member')->where('aid',aid)->where('id',mid)->update($mdata);
		return $this->json(['status'=>1,'msg'=>'开卡成功']);
		die;
		$access_token = access_token(aid,'mp');
		$url = 'https://api.weixin.qq.com/card/membercard/activatetempinfo/get?access_token='.$access_token;
		$rs = request_post($url,jsonEncode(['activate_ticket'=>$activate_ticket]));
		$rs = json_decode($rs,true);
		Log::write('获取领卡信息---'.mid.'---'.print_r($rs,true));
		if($rs['errcode'] == 0){
			if($rs['info']){
				foreach($rs['info']['common_field_list'] as $key=>$v){
					if($v['name'] == 'USER_FORM_INFO_FLAG_MOBILE'){
						$mdata['tel']=$v['value'];
					}
					if($v['name'] == 'USER_FORM_INFO_FLAG_NAME'){
						$mdata['realname']=$v['value'];
					}
					if($v['name'] == 'USER_FORM_INFO_FLAG_BIRTHDAY'){
						$mdata['birthday'] = $v['value'];
					}
					if($v['name'] == 'USER_FORM_INFO_FLAG_LOCATION'){
						$mdata['location']=$v['value'];
					}
					if($v['name'] == 'USER_FORM_INFO_FLAG_IDCARD'){
						$mdata['usercard']=$v['value'];
					}
				}
			}
		}else{
			Log::write('领卡时获取开卡的信息失败:'.$rs);
		}
	}

	//余额宝提现
	public function yuebao_withdraw(){
		if(!getcustom('plug_yuebao')) {
			die;
		}
		$set = Db::name('admin_set')
		     ->where('aid',aid)
		     ->field('open_yuebao,yuebao_withdraw_time,yuebao_withdraw,yuebao_withdrawmin,yuebao_withdrawfee,withdraw_autotransfer,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,yuebao_turn_yue')
		     ->find();
		if($set['open_yuebao'] == 0){
			return $this->json(['status'=>0,'msg'=>t('余额宝').'功能未开启']);
		}
		if(request()->isPost()){
			$post = input('post.');

			if($set['yuebao_withdraw'] == 0){
				return $this->json(['status'=>0,'msg'=>t('余额宝').'收益提现功能未开启']);
			}

			//查询上次提现、转余额时间
			$find_ytime = Db::name('member_yuebao_moneylog')
				->where('aid',aid)
				->where('mid',mid)
				->where('money','<',0)
				->where('type','>=',2)
				->where('type','<=',3)
				->field('createtime')
				->order('createtime desc')
				->find();
			if($find_ytime){
				//现在时间与上次时间差
				$cha = time()-$find_ytime['createtime'];

				//限制提现天数
				$wday  = 0;
				//限制提现秒数
				$wtime = 0;

				//如果单独设置天数
				if($this->member['yuebao_withdraw_time']>0){
					$wday = $this->member['yuebao_withdraw_time'];
					//转换天为秒
					$wtime = $this->member['yuebao_withdraw_time']*24*60*60;
				}else{
					//如果单独设置天数为负数，且总天数设置大于0
					if($this->member['yuebao_withdraw_time'] <0 && $set['yuebao_withdraw_time']>0){
						$wday = $set['yuebao_withdraw_time'];
						//转换天为秒
						$wtime = $set['yuebao_withdraw_time']*24*60*60;
					}
				}

				if($wtime>0 && $cha<$wtime){
					return $this->json(['status'=>0,'msg'=>t('余额宝').'收益'.$wday.'天可提现一次']);
				}

			}
			if($post['paytype']=='支付宝' && $this->member['aliaccount']==''){
				return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
			}
			if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
				return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
			}

			$money = $post['money'];
			if($money<=0 || $money < $set['yuebao_withdrawmin']){
				return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['yuebao_withdrawmin']?$set['yuebao_withdrawmin']:0)]);
			}
			if($money > $this->member['yuebao_money']){
				return $this->json(['status'=>0,'msg'=>'可提现'.t('余额宝').'收益不足']);
			}

			$ordernum = date('ymdHis').aid.rand(1000,9999);
			$record['aid'] = aid;
			$record['mid'] = mid;
			$record['createtime']= time();
			$record['money'] = $money*(1-$set['yuebao_withdrawfee']*0.01);
			if($record['money'] <= 0) {
                return $this->json(['status'=>0,'msg'=>'提现金额有误']);
            }
            $record['money'] = round($record['money'],2);
			$record['txmoney'] = $money;
			if($post['paytype']=='支付宝'){
				$record['aliaccountname'] = $this->member['aliaccountname'];
				$record['aliaccount'] = $this->member['aliaccount'];
			}
			if($post['paytype']=='银行卡'){
				$record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
				$record['bankcarduser'] = $this->member['bankcarduser'];
				$record['bankcardnum'] = $this->member['bankcardnum'];
			}
			$record['ordernum'] = $ordernum;
			$record['paytype']  = $post['paytype'];
			$record['platform'] = platform;
			$recordid = Db::name('member_yuebao_withdrawlog')->insertGetId($record);

			\app\common\Member::addyuebaomoney(aid,mid,-$money,t('余额宝').'收益提现',2);

			if($post['paytype'] != ''){
				$tmplcontent = array();
				$tmplcontent['first'] = '有客户申请'.t('余额宝').'收益提现';
				$tmplcontent['remark'] = '点击进入查看~';
				$tmplcontent['keyword1'] = $this->member['nickname'];
				$tmplcontent['keyword2'] = date('Y-m-d H:i');
				$tmplcontent['keyword3'] = $money.'元';
				$tmplcontent['keyword4'] = $post['paytype'];
				\app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('admin/finance/yuebaowithdrawlog'));
				
				$tmplcontent = [];
				$tmplcontent['name3'] = $this->member['nickname'];
				$tmplcontent['amount1'] = $money.'元';
				$tmplcontent['date2'] = date('Y-m-d H:i');
				$tmplcontent['thing4'] = '提现到'.$post['paytype'];
				\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'admin/finance/yuebaowithdrawlog');
			}

			if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
                Db::name('member_yuebao_withdrawlog')->where('id',$recordid)->update(['status' => 1]);
				$rs = \app\common\Wxpay::transfers(aid,mid,$record['money'],$record['ordernum'],platform,t('余额宝').'收益提现');
				if($rs['status']==0){
					return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
				}else{
                    Db::name('member_yuebao_withdrawlog')->where('id',$recordid)->update(['status' => 3]);
					Db::name('member_yuebao_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
					//提现成功通知
					$tmplcontent = [];
					$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
					$tmplcontent['remark'] = '请点击查看详情~';
					$tmplcontent['money'] = (string) round($record['money'],2);
					$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) round($record['money'],2);//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
					\app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
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
		$userinfo = Db::name('member')->where('id',mid)->field('id,yuebao_money,aliaccount,bankname,bankcarduser,bankcardnum')->find();
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
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['userinfo'] = $userinfo;
		$rdata['sysset'] = $set;
		$rdata['tmplids'] = $tmplids;
		return $this->json($rdata);
	}

	//余额宝收益转到余额
    public function yuebao_turn_money()
    {
    	if(!getcustom('plug_yuebao')) {
			die;
		}
		$set = Db::name('admin_set')->where('aid',aid)->field('open_yuebao,yuebao_withdraw_time,yuebao_turn_yue')->find();
		if($set['open_yuebao'] !=1){
			return $this->json(['status'=>0,'msg'=>'余额宝功能未启用']);
		}
		if($set['yuebao_turn_yue'] !=1){
			return $this->json(['status'=>0,'msg'=>'该功能未启用']);
		}
		//查询上次提现、转余额时间
		$find_ytime = Db::name('member_yuebao_moneylog')
			->where('aid',aid)
			->where('mid',mid)
			->where('money','<',0)
			->where('type','>=',2)
			->where('type','<=',3)
			->field('createtime')
			->order('createtime desc')
			->find();
		if($find_ytime){
			//现在时间与上次时间差
			$cha = time()-$find_ytime['createtime'];

			//限制提现天数
			$wday  = 0;
			$wtime = 0;
			//单独设置天数
			if($this->member['yuebao_withdraw_time']>0){
				$wday = $this->member['yuebao_withdraw_time'];
				//转换天为秒
				$wtime = $this->member['yuebao_withdraw_time']*24*60*60;
			}else{
				//如果单独设置天数为负数，且总天数设置大于0
				if($this->member['yuebao_withdraw_time'] <0 && $set['yuebao_withdraw_time']>0){
					$wday = $set['yuebao_withdraw_time'];
					//转换天为秒
					$wtime = $set['yuebao_withdraw_time']*24*60*60;
				}
			}

			if($wtime>0 && $cha<$wtime){
				return $this->json(['status'=>0,'msg'=>t('余额宝').'收益'.$wday.'天可转'.t('余额').'一次']);
			}
		}
		$post = input('post.');

		$money = floatval($post['money']);
		if($money <= 0 || $money > $this->member['yuebao_money']){
			return $this->json(['status'=>0,'msg'=>'转入金额不正确']);
		}
		\app\common\Member::addmoney(aid,mid,$money,t('余额宝').'收益转'.t('余额'));
		\app\common\Member::addyuebaomoney(aid,mid,-$money,t('余额宝').'收益转'.t('余额'),3);
		return $this->json(['status'=>1,'msg'=>'转入成功']);
	}
	//余额宝明细
	public function yuebaolog(){
		$st = input('param.st');
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		if($st ==1){//提现记录
			$datalist = Db::name('member_yuebao_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
		}else{ //余额明细
			$datalist = Db::name('member_yuebao_moneylog')->field("id,money,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
			foreach($datalist as $k=>$v){
				if(strpos($v['remark'],'商家充值，') === 0){
					$datalist[$k]['remark'] = '商家充值';
				}
			}
		}
		if($pagenum == 1){
			$canwithdraw = Db::name('admin_set')->where('aid',aid)->value('yuebao_withdraw');
		}
		return $this->json(['status'=>1,'data'=>$datalist,'canwithdraw'=>$canwithdraw]);
	}

	//注销账号
	public function delaccount(){
		$mid = mid.'';
		\app\model\Member::del(aid,$mid);
		sleep(2);
		return $this->json(['status'=>1,'msg'=>'账号已注销']);
	}

	public function setAgentCard()
    {
        if(request()->isPost()){
            $formdata = input('post.formdata/a');

            $info = [];
            $info['aid'] = aid;
            $info['mid'] = mid;
            $info['name'] = $formdata['name'];
            $info['shopname'] = $formdata['shopname'];
            $info['address'] = $formdata['address'];
            $info['tel'] = $formdata['tel'];
            $info['logo'] = $formdata['logo'];
            $info['pagecontent'] = json_encode(input('post.pagecontent'));
            $info['latitude'] = $formdata['latitude'];
            $info['longitude'] = $formdata['longitude'];

            $info['createtime'] = time();

            if($formdata['id']){
                Db::name('member_agent_card')->where('aid',aid)->where('mid',mid)->where('id',$formdata['id'])->update($info);
            }else{
                Db::name('member_agent_card')->insertGetId($info);
            }
            return $this->json(['status'=>1,'msg'=>'提交成功']);
        }
        $info = Db::name('member_agent_card')->where('aid',aid)->where('mid',mid)->find();
        $pagecontent = json_decode(\app\common\System::initpagecontent($info['pagecontent'],aid),true);
        if(!$pagecontent) $pagecontent = [];
        $rdata = [];
        $rdata['info'] = $info ? $info : [];
        $rdata['pagecontent'] = $pagecontent;
        return $this->json($rdata);
    }

    //元宝记录
    public function yuanbaolog(){
        if(getcustom('pay_yuanbao')) {
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_yuanbaolog')->field('id,yuanbao,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if(request()->isPost()){
                if($pagenum == 1) {
                    $set = $this->sysset;
                    $yuanbaoTransfer = $set['yuanbao_transfer'] ? true : false;
                }
                return $this->json(['status'=>1,'data'=>$datalist,'myyuanbao'=>$this->member['yuanbao'],'yuanbaoTransfer' => $yuanbaoTransfer] );
            }

            $count = Db::name('member_yuanbaolog')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $rdata['myyuanbao'] = $this->member['yuanbao'];
            $rdata['title'] = t('元宝')."明细";
            return $this->json($rdata);
        }
        
    }

    //元宝转送
    public function yuanbaoTransfer()
    {
        if(getcustom('pay_yuanbao')) {
            $set = $this->sysset;
            if ($set['yuanbao_transfer'] != 1) {
                return $this->json(['status'=>0,'msg'=>t('元宝').'转赠未开启']);
            }
            //元宝转账现金比例
            $yuanbao_money_ratio = $set['yuanbao_money_ratio'];
            
            if(request()->isPost()){
                $mobile = input('post.mobile');
                $mid = input('post.mid/d');
                $integral = input('post.integral');
                if ($integral <= 0){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的'.t('元宝').'数量']);
                }
                if (input('?post.mobile')) {
                    $info = Db::name('member')->where('aid', aid)->where('tel', $mobile)->find();
                }
                if (input('?post.mid')) {
                    $info = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
                }

                if(!$info) return $this->json(['status'=>0,'msg'=>'未找到该'.t('会员')]);
                $user_id = $info['id'];

                if ($info['id'] == mid) {
                    return $this->json(['status'=>0,'msg'=>'不能转赠给自己']);
                }
                if ($integral > $this->member['yuanbao']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('元宝').'数量不足']);
                }

                $money = $integral*$yuanbao_money_ratio/100;
                $money = round($money,2);
                if($money<=0){
                    //直接转账
                    $rs = \app\common\Member::addyuanbao(aid,$user_id,$integral,sprintf("来自%s的".t('元宝')."转赠", $this->member["nickname"]));
                    if ($rs['status'] == 1) {
                        \app\common\Member::addyuanbao(aid,mid,$integral * -1, sprintf(t('元宝')."转赠给：%s",$info['nickname']));
                    }
                    return $this->json(['status'=>1, 'msg' => '转赠成功', 'url'=>'/pages/my/usercenter']);
                }else{
                    $data = [];
                    $data['aid'] = aid;
                    $data['mid'] = mid;
                    $data['to_mid']   = $user_id;
                    $data['ordernum'] = 'Z'.date('ymdHis').rand(100000,999999);
                    $data['money']    = $money;
                    $data['yuanbao']  = $integral;
                    $data['create_time'] = time();

                    if($this->member['pid']){
                        $parent1 = Db::name('member')->where('aid',aid)->where('id',$this->member['pid'])->find();
                        if($parent1){
                            $agleveldata1 = Db::name('member_level')->where('aid',aid)->where('id',$parent1['levelid'])->find();
                            if($agleveldata1 && $agleveldata1['can_agent']!=0){
                                $data['parent1'] = $parent1['id'];
                                if($agleveldata1['commissiontype']==1){ //固定金额按单
                                    $data['parent1commission'] = $agleveldata1['commission1'];
                                }else{
                                    $data['parent1commission'] = $agleveldata1['commission1'] * $money * 0.01;
                                }
                            }
                        }
                    }
                    if($parent1['pid']){
                        $parent2 = Db::name('member')->where('aid',aid)->where('id',$parent1['pid'])->find();
                        if($parent2){
                            $agleveldata2 = Db::name('member_level')->where('aid',aid)->where('id',$parent2['levelid'])->find();
                            if($agleveldata2 && $agleveldata2['can_agent']>1){
                                $data['parent2'] = $parent2['id'];
                                if($agleveldata2['commissiontype']==1){ //固定金额按单
                                    $data['parent2commission'] = $agleveldata2['commission2'];
                                }else{
                                    $data['parent2commission'] = $agleveldata2['commission2'] * $money * 0.01;
                                }
                            }
                        }
                    }
                    if($parent2['pid']){
                        $parent3 = Db::name('member')->where('aid',aid)->where('id',$parent2['pid'])->find();
                        if($parent3){
                            $agleveldata3 = Db::name('member_level')->where('aid',aid)->where('id',$parent3['levelid'])->find();
                            if($agleveldata3 && $agleveldata3['can_agent']>2){
                                $data['parent3'] = $parent3['id'];
                                if($agleveldata3['commissiontype']==1){ //固定金额按单
                                    $data['parent3commission'] = $agleveldata3['commission3'];
                                }else{
                                    $data['parent3commission'] = $agleveldata3['commission3'] * $money * 0.01;
                                }
                            }
                        }
                    }

                    $insert_id = Db::name('member_yuanbao_transfer_order')->insertGetId($data);
                    if($insert_id){
                        $payorderid = \app\model\Payorder::createorder(aid,0,mid,'member_yuanbao_transfer',$insert_id,$data['ordernum'],t('元宝')."转赠给".$info['nickname'],$money,0);
                        $up = Db::name('member_yuanbao_transfer_order')->where('id',$insert_id)->update(['payorderid'=>$payorderid]);
                        return $this->json(['status'=>2, 'msg' => '提交成功','payorderid'=>$payorderid]);
                    }else{
                        return $this->json(['status'=>0, 'msg' => '提交失败']);
                    }
                }
            }

            $rdata['status'] = 1;
            $rdata['myyuanbao'] = $this->member['yuanbao'];
            $rdata['yuanbao_money_ratio'] = $yuanbao_money_ratio;
            $rdata['title'] = t('元宝')."转账";
            return $this->json($rdata);
        }
    }

    public function othermoneylog(){
    	if(getcustom('other_money')){
    		//是否有多账户权限
            $othermoney_status = Db::name('admin')->where('id',aid)->value('othermoney_status');
            if($othermoney_status != 1){
                return json(['status'=>0,'msg'=>'无权限操作']);
            }
	    	$type   = input('post.type');
	    	$st = input('param.st');
	        if($type == 'money2'){
	            $type_name = t('余额2');
	            $log_type  = 2;
	        }else if($type == 'money3'){
	            $type_name = t('余额3');
	            $log_type  = 3;
	        }else if($type == 'money4'){
	            $type_name = t('余额4');
	            $log_type  = 4;
	        }else if($type == 'money5'){
	            $type_name = t('余额5');
	            $log_type  = 5;
	        }else if($type == 'frozen_money'){
	            $type_name = t('冻结金额');
	            $log_type  = 0;
	        }else{
	            return json(['status'=>0,'msg'=>'操作类型错误']);
	        }

	        $pagenum = input('post.pagenum');
	        if(!$pagenum) $pagenum = 1;
	        $pernum = 20;
	        $where = [];
	        $where[] = ['aid','=',aid];
	        $where[] = ['mid','=',mid];
	        $where[] = ['type','=',$log_type];

	        if($st ==1){//提现记录
	            $datalist = Db::name('member_otherwithdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
	            if(!$datalist) $datalist = [];
	        }else{ //明细
	            $datalist = Db::name('member_othermoneylog')->field("id,money,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
	            if(!$datalist) $datalist = [];
	            foreach($datalist as $k=>$v){
	                if(strpos($v['remark'],'商家充值，') === 0){
	                    $datalist[$k]['remark'] = '商家充值';
	                }
	            }
	        }
	        if($pagenum == 1){
	            $canwithdraw = Db::name('admin_set')->where('aid',aid)->value('withdraw');
	        }
	        if($type == 'frozen_money'){
	             return $this->json(['status'=>1,'data'=>$datalist,'canwithdraw'=>$canwithdraw,'type_name'=>$type_name,'money'=>$this->member['frozen_money']]);
	        }else{
	             return $this->json(['status'=>1,'data'=>$datalist,'canwithdraw'=>$canwithdraw,'type_name'=>$type_name,]);
	        }
	    }
    }

    public function otherwithdraw(){
    	if(getcustom('other_money')){
    		//是否有多账户权限
            $othermoney_status = Db::name('admin')->where('id',aid)->value('othermoney_status');
            if($othermoney_status != 1){
                return json(['status'=>0,'msg'=>'无权限操作']);
            }
	        $set = Db::name('admin_set')->where('aid',aid)->field('withdraw_autotransfer,withdraw,withdrawmin,withdrawfee,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,othermoney_withdraw')->find();
	        $type   = input('type');
	        if($type == 'money2'){
	            $type_name    = t('余额2');
	            $log_type     = 2;
	            $field 	      = 'id,money2 as money,aliaccount,bankname,bankcarduser,bankcardnum';
	            $member_money = $this->member['money2'];
	        }else if($type == 'money3'){
	            $type_name    = t('余额3');
	            $log_type     = 3;
	            $field 	      = 'id,money3 as money,aliaccount,bankname,bankcarduser,bankcardnum';
	            $member_money = $this->member['money3'];
	        }else if($type == 'money4'){
	            $type_name    = t('余额4');
	            $log_type     = 4;
	            $field 	      = 'id,money4 as money,aliaccount,bankname,bankcarduser,bankcardnum';
	            $member_money = $this->member['money4'];
	        }else if($type == 'money5'){
	            $type_name    = t('余额5');
	            $log_type     = 5;
	            $field 	      = 'id,money5 as money,aliaccount,bankname,bankcarduser,bankcardnum';
	            $member_money = $this->member['money5'];
	        }else{
	            return json(['status'=>0,'msg'=>'操作类型错误']);
	        }
	        if(request()->isPost()){
	            $post = input('post.');

	            if($set['withdraw'] == 0 ){
	                return $this->json(['status'=>0,'msg'=>$type_name.'提现功能未开启']);
	            }
	            if(!$set['othermoney_withdraw']){
	                return $this->json(['status'=>0,'msg'=>$type_name.'提现功能未开启']);
	            }
	            if($post['paytype']=='支付宝' && $this->member['aliaccount']==''){
	                if($set['withdraw_aliaccount'] == 0)
	                    return $this->json(['status'=>0,'msg'=>'支付宝提现功能未开启']);
	                return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
	            }
	            if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
	                if($set['withdraw_bankcard'] == 0)
	                    return $this->json(['status'=>0,'msg'=>'银行卡提现功能未开启']);
	                return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
	            }
	            if($post['paytype'] == '微信钱包' && $set['withdraw_weixin'] == 0){
	                return $this->json(['status'=>0,'msg'=>'微信钱包提现功能未开启']);
	            }

	            $money = $post['money'];
	            if($money<=0 || $money < $set['withdrawmin']){
	                return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['withdrawmin']?$set['withdrawmin']:0)]);
	            }
	            if($money > $member_money){
	                return $this->json(['status'=>0,'msg'=>'可提现'.$type_name.'不足']);
	            }

	            $ordernum = date('ymdHis').aid.rand(1000,9999);
	            $record['aid'] = aid;
	            $record['mid'] = mid;
	            $record['createtime']= time();
	            $record['money'] = $money*(1-$set['withdrawfee']*0.01);
	            if($record['money'] <= 0) {
	                return $this->json(['status'=>0,'msg'=>'提现金额有误']);
	            }
	            $record['money'] = round($record['money'],2);
	            $record['txmoney'] = $money;
	            if($post['paytype']=='支付宝'){
	                $record['aliaccountname'] = $this->member['aliaccountname'];
	                $record['aliaccount'] = $this->member['aliaccount'];
	            }
	            if($post['paytype']=='银行卡'){
	                $record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
	                $record['bankcarduser'] = $this->member['bankcarduser'];
	                $record['bankcardnum'] = $this->member['bankcardnum'];
	            }
	            $record['ordernum'] = $ordernum;
	            $record['paytype']  = $post['paytype'];
	            $record['platform'] = platform;
	            $record['type']     = $log_type;
	            $recordid = Db::name('member_otherwithdrawlog')->insertGetId($record);

	            \app\common\Member::addOtherMoney(aid,mid,$type,-$money,$type_name.'提现');

	            /*$tmplcontent = array();
	            $tmplcontent['first'] = '有客户申请'.$type_name.'提现';
	            $tmplcontent['remark'] = '点击进入查看~';
	            $tmplcontent['keyword1'] = $this->member['nickname'];
	            $tmplcontent['keyword2'] = date('Y-m-d H:i');
	            $tmplcontent['keyword3'] = $money.'元';
	            $tmplcontent['keyword4'] = $post['paytype'];
	            \app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('admin/finance/withdrawlog'));*/

	            /*$tmplcontent = [];
	            $tmplcontent['name3'] = $this->member['nickname'];
	            $tmplcontent['amount1'] = $money.'元';
	            $tmplcontent['date2'] = date('Y-m-d H:i');
	            $tmplcontent['thing4'] = '提现到'.$post['paytype'];
	            \app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'admin/finance/withdrawlog');*/

	            if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
	                Db::name('member_otherwithdrawlog')->where('id',$recordid)->where('type',$log_type)->update(['status' => 1]);
	                $rs = \app\common\Wxpay::transfers(aid,mid,$record['money'],$record['ordernum'],platform,$type_name.'提现');
	                if($rs['status']==0){
	                    return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
	                }else{
	                    Db::name('member_otherwithdrawlog')->where('id',$recordid)->where('type',$log_type)->update(['status' => 3]);
	                    Db::name('member_otherwithdrawlog')->where('aid',aid)->where('id',$recordid)->where('type',$log_type)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
	                    //提现成功通知
	                    $tmplcontent = [];
	                    $tmplcontent['first'] = '您的'.$type_name.'提现申请已打款，请留意查收';
	                    $tmplcontent['remark'] = '请点击查看详情~';
	                    $tmplcontent['money'] = (string) round($record['money'],2);
	                    $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) round($record['money'],2);//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
	                    \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
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
	        $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
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
	        $rdata = [];
	        $rdata['status']    = 1;
	        $rdata['userinfo']  = $userinfo;
	        $rdata['sysset']    = $set;
	        $rdata['tmplids']   = $tmplids;
	        $rdata['type_name'] = $type_name;

	        $rdata['canWithdraw'] = $set['othermoney_withdraw']?true:false;
	        return $this->json($rdata);
	    }
    }

    public function otherset(){
        if(getcustom('member_set')){

            $index = input('index');
            if(!$index && $index !== '0'){
                return $this->json(['status'=>0,'msg'=>'请选择要设置的信息']);
            }

            $set = Db::name('member_set')->where('aid',aid)->find();
            if(!$set){
                return $this->json(['status'=>0,'msg'=>'设置信息不存在']);
            }
            $setcontent = json_decode($set['content'],true);
            if(!$setcontent){
                return $this->json(['status'=>0,'msg'=>'设置信息不存在']);
            }
            //查询用户设置
            $log = Db::name('member_set_log')->where('mid',mid)->where('aid',aid)->where('formid',$set['id'])->find();

            if(request()->isPost()){
                $content = input('content')?input('content'):'';
                if($content){
                    if(is_array($content)){
                        $content = implode(',',$content);
                    }
                }
                //查询是否已添加过
                if($log){
                    $up_data = [];
                    $up_data['form'.$index] = $content;
                    $up = Db::name('member_set_log')->where('id',$log['id'])->update($up_data);
                }else{
                    if(!empty($content)){
                        $logdata = [];
                        $logdata['aid'] = aid;
                        $logdata['mid'] = mid;
                        $logdata['formid'] = $set['id'];
                        $logdata['form'.$index]= $content;
                        $logdata['createtime'] = time();
                        $up = Db::name('member_set_log')->insert($logdata);
                    }else{
                        return $this->json(['status'=>0,'msg'=>'内容不能为空']);
                    }
                }
                if($up){
                    return $this->json(['status'=>1,'msg'=>'保存成功']);
                }else{
                    return $this->json(['status'=>0,'msg'=>'保存失败']);
                }
            }else{
                $detail = '';
                foreach($setcontent as $sk=>&$sv){
                    if($sk == $index){
                        $sv['content'] = '';
                        // if($log){
                        //     $sv['content'] = $log['form'.$sk];
                        // }
                        $detail = $sv;
                    }
                }
                unset($sv);
                return $this->json(['status'=>1,'detail'=>$detail]);
            }
        }
    }


    public function registset()
    {
        if (getcustom('register_fields')) {

            $index = input('index');
            $mid = mid;
            if(getcustom('team_update_member_info') && input('mid')){
                $mid = input('mid');
            }
            if (!$index && $index !== '0') {
                return $this->json(['status' => 0, 'msg' => '请选择要设置的信息']);
            }
            $member = Db::name("member")->where('id', $mid)->find();
            if (!$member) {
                return $this->json(['status' => 0, 'msg' => '会员不存在']);
            }
            $set = Db::name('register_form')->where('aid', aid)->find();
            if ($member['form_record_id'] > 0) {
                $form_record = Db::name('register_form_record')->where('id', $member['form_record_id'])->where('aid', aid)->find();
            }
            if (!$set) {
                return $this->json(['status' => 0, 'msg' => '设置信息不存在']);
            }
            $setcontent = json_decode($set['content'], true);
            if (!$setcontent) {
                return $this->json(['status' => 0, 'msg' => '设置信息不存在']);
            }

            if (request()->isPost()) {
                Db::startTrans();
                try {
                    $content = input('content') ? input('content') : '';
                    if ($content) {
                        if (is_array($content)) {
                            $content = implode(',', $content);
                        }
                    }
                    $item_content = $setcontent[$index];
                    //查询是否已添加过
                    if ($member['form_record_id']) {
                        $up_data = [];
                        $up_data['form' . $index] = $content;
                        $up = Db::name('register_form_record')->where('id', $member['form_record_id'])->update($up_data);
                    } else {
                        if (!empty($content)) {
                            $logdata = [];
                            $logdata['aid'] = aid;
                            $logdata['bid'] = bid;
                            $logdata['formid'] = $set['id'];
                            $logdata['form' . $index] = $content;
                            $logdata['createtime'] = time();
                            $up = Db::name('register_form_record')->insertGetId($logdata);
                            Db::name("member")->where('id', $mid)->save([
                                "form_record_id"=>$up
                            ]);
                        } else {
                            throw new Exception('内容不能为空');
                        }
                    }
                    if ($up) {
                        if($item_content['key'] == "usercard"){
                            //
                            if(!checkIdCard($content)){
                                throw new Exception('请输入正确的身份证号');
                            }
                            Db::name("member")->where('id', $mid)->save([
                                "usercard"=>$content
                            ]);
                        }
                        Db::commit();
                        return $this->json(['status' => 1, 'msg' => '保存成功']);
                    } else {
                        throw new Exception('保存失败');
                    }
                }catch (\Throwable $e){
                    Db::rollback();
                    return $this->json(['status' => 0, 'msg' => $e->getMessage()]);
                }
            } else {
                $detail = '';
                foreach ($setcontent as $sk => &$sv) {
                    if ($sk == $index) {
                        $sv['content'] = '';
                        $detail = $sv;
                    }
                }
                unset($sv);
                return $this->json(['status' => 1, 'detail' => $detail]);
            }
        }
    }

	public function rechargeyjlog(){
		if(getcustom('member_recharge_yj')){
			//充值业绩记录
			$st = input('param.st');
			$pagenum = input('post.pagenum');
			if(!$pagenum) $pagenum = 1;
			$pernum = 20;
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['mid','=',mid];
			if($st ==1){//提现记录
				$datalist = Db::name('member_recharge_yj_withdrawlog')->field("id,rechargeyj_money,money,txmoney,`status`,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
				if(!$datalist) $datalist = [];
			}else{ 
				//业绩明细
				$datalist = Db::name('member_recharge_yj_log')->field("id,money,get_yj,remark,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
				if(!$datalist) $datalist = [];
			}
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
	}

	public function rechargeyj_withdraw(){
		if(getcustom('member_recharge_yj')) {
			$set = Db::name('admin_set')
			     ->where('aid',aid)
			     ->field('rechargeyj_withdraw,rechargeyj_withdrawmin,rechargeyj_withdrawfee,withdraw_autotransfer,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard')
			     ->find();
			$levelyj = Db::name('member_level')
	            ->where('id',$this->member['levelid'])
	            ->where('aid',aid)
	            ->field('id,open_yj,recharge_yj_ratio,recharge_yj_ratio,yj_datas,yj_moneys_after,yj_ratios_after')
	            ->find();
	        $userinfo = Db::name('member')->where('id',mid)->field('id,levelid,rechargeyj_money,aliaccount,bankname,bankcarduser,bankcardnum')->find();
			if(request()->isPost()){
				$post = input('post.');

				if($set['rechargeyj_withdraw'] == 0){
					return $this->json(['status'=>0,'msg'=>'业绩提现功能未开启']);
				}
				if($post['paytype']=='支付宝' && $this->member['aliaccount']==''){
					return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
				}
				if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
					return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
				//$money = $post['money'];
				$money = 0;
				$ratio = 0;
				if($levelyj){
		        	if(!$levelyj['open_yj']){
		        		return $this->json(['status'=>0,'msg'=>'当前等级暂未开启业绩']);
		        	}else{
		        		if($levelyj['yj_datas']){
		        			
	        				//业绩数据
			                $yj_datas = json_decode($levelyj['yj_datas'],true);
			                if($yj_datas){
			                	$ratio = 0;
			                    $yjlist = $yj_datas['yj_data']?$yj_datas['yj_data']:[];
			                    if($yjlist){
			                    	foreach($yjlist as $yv){
			                    		if($userinfo['rechargeyj_money']<=$yv['money']){
			                    			$ratio = $yv['ratio'];
			                    			break;
			                    		}
			                    	}
			                    	unset($yv);
			                    }
			                    if($ratio == 0 && $userinfo['rechargeyj_money']>=$levelyj['yj_moneys_after'] && $levelyj['yj_ratios_after']>0){
		        					$ratio = $levelyj['yj_ratios_after'];
		        				}
			                }else{
			                	if($ratio == 0 && $userinfo['rechargeyj_money']>=$levelyj['yj_moneys_after'] && $levelyj['yj_ratios_after']>0){
		        					$ratio = $levelyj['yj_ratios_after'];
		        				}
			                }
			                if($ratio<=0){
			                	return $this->json(['status'=>0,'msg'=>'当前等级业绩转换提现金额比例为0']);
			                }
			                //计算可提现金额
			                $money = $userinfo['rechargeyj_money'] * $ratio/100;
			            }else{
			            	return $this->json(['status'=>0,'msg'=>'当前等级暂未设置业绩转换提现金额比例']);
			            }
		        	}
		        }else{
		        	$yj_tip = '当前等级不可提现';
		        	$set['rechargeyj_withdraw'] = 0;
		        }
				
				if($money<=0 || $money < $set['rechargeyj_withdrawmin']){
					return $this->json(['status'=>0,'msg'=>'转换提现金额为'.$money.'元，提现金额必须大于'.($set['rechargeyj_withdrawmin']?$set['rechargeyj_withdrawmin']:0)]);
				}

				$ordernum = date('ymdHis').aid.rand(1000,9999);
				$record['aid'] = aid;
				$record['mid'] = mid;
				$record['createtime']= time();
				$record['rechargeyj_withdrawfee'] = !empty($set['rechargeyj_withdrawfee'])?$set['rechargeyj_withdrawfee']:0;
	            $record['rechargeyj_money'] 	  = $userinfo['rechargeyj_money'];
	            $record['ratio'] 	  			  = $ratio;
				$record['money'] = $money*(1-$set['rechargeyj_withdrawfee']*0.01);
				if($record['money'] <= 0) {
	                return $this->json(['status'=>0,'msg'=>'提现金额有误']);
	            }
	            $record['money'] = round($record['money'],2);
				$record['txmoney'] = $money;
				if($post['paytype']=='支付宝'){
					$record['aliaccountname'] = $this->member['aliaccountname'];
					$record['aliaccount'] = $this->member['aliaccount'];
				}
				if($post['paytype']=='银行卡'){
					$record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
					$record['bankcarduser'] = $this->member['bankcarduser'];
					$record['bankcardnum'] = $this->member['bankcardnum'];
				}
				$record['ordernum'] = $ordernum;
				$record['paytype']  = $post['paytype'];
				$record['platform'] = platform;
				$recordid = Db::name('member_recharge_yj_withdrawlog')->insertGetId($record);

				\app\custom\RechargeYj::changeyj(aid,mid,-$userinfo['rechargeyj_money'],'提现减少');

				if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
	                Db::name('member_recharge_yj_withdrawlog')->where('id',$recordid)->update(['status' => 1]);
					$rs = \app\common\Wxpay::transfers(aid,mid,$record['money'],$record['ordernum'],platform,t('余额宝').'收益提现');
					if($rs['status']==0){
						return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
					}else{
	                    Db::name('member_recharge_yj_withdrawlog')->where('id',$recordid)->update(['status' => 3]);
						Db::name('member_recharge_yj_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
						//提现成功通知
						$tmplcontent = [];
						$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
						$tmplcontent['remark'] = '请点击查看详情~';
						$tmplcontent['money'] = (string) round($record['money'],2);
						$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) round($record['money'],2);//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
						\app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
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
			$yj_tip = '';
	        if($levelyj){
	        	if(!$levelyj['open_yj']){
	        		$yj_tip = '当前等级暂未开启业绩';
	        		$set['rechargeyj_withdraw'] = 0;
	        	}else{
	        		$yj_tip = '当前等级暂未设置业绩转换提现金额比例';
	        		if($levelyj['yj_datas']){
        				//业绩数据
		                $yj_datas = json_decode($levelyj['yj_datas'],true);
		                if($yj_datas){
		                	$ratio = 0;
		                    $yjlist = $yj_datas['yj_data']?$yj_datas['yj_data']:[];
		                    if($yjlist){
		                    	foreach($yjlist as $yv){
		                    		if($userinfo['rechargeyj_money']<=$yv['money']){
		                    			$ratio = $yv['ratio'];
		                    			break;
		                    		}
		                    	}
		                    	unset($yv);
		                    	$yj_tip = '当前等级业绩可转换提现金额比例为'.$ratio.'%';
		                    }
		                    if($ratio == 0 && $userinfo['rechargeyj_money']>=$levelyj['yj_moneys_after'] && $levelyj['yj_ratios_after']>0){
	        					$yj_tip = '当前等级业绩可转换提现金额比例为'.$levelyj['yj_ratios_after'].'%';
	        				}
		                }else{
		                	if($userinfo['rechargeyj_money']>=$levelyj['yj_moneys_after'] && $levelyj['yj_ratios_after']>0){
	        					$yj_tip = '当前等级业绩可转换提现金额比例为'.$levelyj['yj_ratios_after'].'%';
	        				}
		                }
	        			
		            }
	        	}
	        }else{
	        	$yj_tip = '当前等级不可提现';
	        	$set['rechargeyj_withdraw'] = 0;
	        }
			$rdata = [];
			$rdata['status'] = 1;
			$rdata['userinfo'] = $userinfo;
			$rdata['sysset']   = $set;

			$rdata['yj_tip']   = $yj_tip;
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
			$rdata['tmplids']  = $tmplids;
			
			return $this->json($rdata);
		}
		
	}

	public function getRechargeyj(){
		if(getcustom('member_recharge_yj')) {
		    $field = 'id,headimg,nickname,pid,levelid,rechargeyj_money';
			$userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',mid)->find();

			$txmoney = 0 + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('mid',mid)->where('status',3)->sum('txmoney');

			if($userinfo['pid']){
				$userinfo['pnickname'] = Db::name('member')->where('aid',aid)->where('id',$userinfo['pid'])->value('nickname');
			}
			$set = Db::name('admin_set')->where('aid',aid)->field('parent_show,rechargeyj_withdraw')->find();
	        $rsset = [];
	        $rsset['parent_show'] = $set['parent_show'];
			$rdata = [];
			$rdata['txmoney']     = $txmoney;
			$rdata['rechargeyj_withdraw'] = $set['rechargeyj_withdraw'];
	        $rdata['set'] 		  = $rsset;
			$rdata['userinfo']    = $userinfo;
			return $this->json($rdata);
		}
	}

    public function getMemberBase()
    {
        $mid = input('param.mid/d');
        $tel = input('param.tel');
        $where = [];
        $where[] = ['aid','=',aid];
        if($mid){
            $where[] = ['id','=',$mid];
        }
        if($tel){
            $where[] = ['tel','=',$tel];
        }
        if(!$mid && !$tel){
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $member = Db::name('member')->where($where)->find();
        if($member){
            if(getcustom('maidan_orderadd_mobile_paytransfer')){
                $p_member = Db::name('member')->where('id',$member['pid'])->field('id,nickname,headimg,tel')->find();
            }
            return $this->json(['status'=>1,'data'=>['nickname'=>$member['nickname'],'id'=>$member['id'],'headimg'=>$member['headimg'],'tel'=>$member['tel'],'p_member' => $p_member ?? '']]);
        }else{
            return $this->json(['status'=>0,'msg'=>'无数据']);
        }

    }

    public function getMemberCode()
    {
        if(getcustom('member_code')){
            $showmeberinfo = false;
            $tablist = [];
            $set = Db::name('member_code_set')->where('aid',aid)->find();
            if($set['status'] != 1) return $this->json(['status'=>0,'msg'=>'功能未开启']);
            if(!isset($set['code_type'])) $set['code_type'] = 0;//兼容之前没有type的情况
            $field = 'id,headimg,nickname,pid,levelid,member_code,member_code_img,tel';
            if(getcustom('member_code_paycode')){
                $field.= ',member_barcode_img,money,score';
            }
            if(getcustom('member_overdraft_money')){
                $field.= ',overdraft_money';
            }
            $userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',mid)->find();
            if(empty($userinfo['member_code']))
            {
                $membercode = \app\common\Member::createMemberCode(aid,mid);
                $userinfo['member_code'] = $membercode['member_code'];
                $userinfo['member_code_img'] = $membercode['member_code_img'];
            }
            if($userinfo['tel']){
                $userinfo['tel'] = hidePhoneNumber($userinfo['tel']);
            }
            if(getcustom('member_code_paycode')){
                $showmeberinfo = true;
                //开启条形码
                if(($set['code_type'] == 1 || $set['code_type'] == 2)  && empty($userinfo['member_barcode_img']) && $userinfo['member_code']){
                    $member_barcode_img = createbarcode($userinfo['member_code'],'',aid);
                    if($member_barcode_img){
                        $userinfo['member_barcode_img'] = $member_barcode_img;
                        Db::name('member')->where('aid',aid)->where('id',mid)->update(['member_barcode_img'=>$member_barcode_img]);
                    }
                }
                $couponcount = Db::name('coupon_record')->where('aid',aid)->where('mid',$this->mid)->where('status',0)->where('endtime','>=',time())->count();
                $tablist[] = ['name'=>t('优惠券'),'value'=>$couponcount,'tag'=>'','path'=>'/pagesExt/coupon/mycoupon'];
                $tablist[] = ['name'=>t('余额'),'value'=>$userinfo['money'],'tag'=>'￥','path'=>'/pagesExt/money/recharge'];
                $tablist[] = ['name'=>t('积分'),'value'=>round($userinfo['score'],$this->score_weishu),'tag'=>'','path'=>'/pagesExt/my/scorelog'];
            }
            if(getcustom('member_overdraft_money')){
                $tablist[] = ['name'=>t('信用额度'),'value'=>$userinfo['overdraft_money'],'tag'=>'','path'=>'/pagesA/overdraft/detail'];
            }

            $rdata['set'] 		  = $set;
            $rdata['userinfo']    = $userinfo;
            $rdata['tablist']    = $tablist;
            $rdata['showmeberinfo']    = $showmeberinfo;
            return $this->json($rdata);
        }
    }
    public function wxOfflinePayView(){
        $data = [];
        $appinfo = \app\common\System::appinfo(aid,platform);
        $data['appId'] = $appinfo['appid'];
        $data['timeStamp'] = time();
        $data['nonceStr'] = random(8);
        $data['package'] = 'package:mch_id='.$appinfo['wxpay_mchid'];
        $data['signType'] = 'MD5';
        ksort($data, SORT_STRING);
        $string1 = '';
        foreach ($data as $key => $v){
            if (empty($v)) {
                continue;
            }
            $string1 .= "{$key}={$v}&";
        }
        $mchkey = $appinfo['wxpay_mchkey'];
        $string1 .= "key=".$mchkey;
        $data['paySign'] =  strtoupper(md5($string1));
        return $this->json(['status'=>1,'data'=>$data]);
    }

    //获取升级协议
    public function getUpAgree(){
        //升级协议
        $uplv_agree = 0;
        $agree_content = '';
        if(getcustom('up_level_agree')  || getcustom('extend_tencent_qian')){
            $map_a = [];
            $map_a[] = ['mid','=',mid];
            $map_a[] = ['status','=',0];
            $exit = Db::name('member_level_agree')->where($map_a)->order('sort desc')->find();
            if($exit){
                $uplv_agree = $showxieyi = 1;
                $newlevel = Db::name('member_level')->where('id',$exit['newlv_id'])->find();
                $agree_content = $newlevel['agree_content']??'';
                if(getcustom('extend_tencent_qian')){
                    $level = Db::name('member_level')->where('id',$this->member['levelid'])->find();
                    if($level && $level['sort']>$newlevel['sort']){
                        $showxieyi = 0;
                    }else{
                        if($newlevel['tencent_qian']){
                            $showxieyi = 2;
                            $xytitle = '升级提示';
                            $xycontent = '升级到'.$newlevel['name'].'等级，需要签署升级协议';
                            //查询是否签署过
                            $qian = Db::name('member_tencent_qianlog')->where('mid',mid)->where('agreeid',$exit['id'])->where('type','member_uplevel')->order('id desc')->find();
                            if($qian && ( $qian['status'] ==0 || $qian['status'] ==1 || $qian['status'] ==2)){
                                $showxieyi = 0;
                            }

                            //查询是否已签署过相同签署
		                    $qian2 = Db::name('member_tencent_qianlog')->where('mid',mid)->where('createflowid',$newlevel['tencent_qian_createflowid'])->where('status','>=',1)->where('status','<=',2)->order('status desc')->find();
		                    //若签署成功过，则可直接升级
		                    if($qian2){
		                    	$showxieyi = 0;
		                    	if($qian2['status'] == 2){
		                        	\app\custom\TencentQian::dealuplevel(mid,$exit['id']);
		                    	}
		                    }
                        } 
                    }
                }
            }
        }
        $rdata = [];
        $rdata['status'] = 1;
        $rdata['data'] = [
        	'uplv_agree'=>$uplv_agree,
        	'agree_content'=>$agree_content
        ];
        $rdata['showxieyi'] = $showxieyi;
        $rdata['xytitle']   = $xytitle;
        $rdata['xycontent'] = $xycontent;
        return $this->json($rdata);
    }
    //同意升级
    public function agreeUplv(){
        if(getcustom('up_level_agree')){
            Db::startTrans();
            //执行升级
            $mid = mid;
            $map_a = [];
            $map_a[] = ['mid','=',$mid];
            $map_a[] = ['status','=',0];
            $exit = Db::name('member_level_agree')->where($map_a)->order('sort desc')->find();
            $newlv = Db::name('member_level')->where('id',$exit['newlv_id'])->find();
            $member = Db::name('member')->where('id',$mid)->find();
            \app\common\Member::handleUpLevel($exit['aid'],$exit['mid'],$newlv,$member,$member,$exit['cid']);

            //更新记录状态
            $map = [];
            $map[] = ['mid','=',$mid];
            $map[] = ['sort','<=',$exit['sort']];
            Db::name('member_level_agree')->where($map)->update(['status'=>1]);
            Db::commit();
            return $this->json(['status'=>1,'msg'=>'']);
        }
    }

    //汇付天下
	public function getAdapay(){
        if(getcustom('pay_adapay')){
            $adapay = Db::name('adapay_member')->where('aid',aid)->where('mid',mid)->find();
            $smscode_show =0;
            if(empty($adapay)){
                $adapay['bankcardnum'] =  $this->member['bankcardnum'];
                $adapay['bankname'] =  $this->member['bankname'];
            }else{
                if( $adapay['card_id'] && !$adapay['token_no']){
                    $smscode_show =1;
                }
            }
            return json(['status' => 1,'data'=>$adapay??[],'smscode_show' => $smscode_show]);
        }
    }
    //设置汇付天下银行卡
    public function setAdapay(){
        if(getcustom('pay_adapay')){
            $bankname = input('param.bankname');
            $bankcardnum = input('param.bankcardnum');
            $tel_no = input('param.tel_no');
            $realname = input('param.realname');
            $idcard = input('param.idcard');
            if(!$bankcardnum){
                return json(['status' => 0,'msg' => '请输入银行卡号']);
            }
            if(!$tel_no || !checkTel(aid,$tel_no)){
                return json(['status' => 0,'msg' => '请检查手机号格式']);
            }
            $member_id = aid.'_'.mid;
            //查询adapay下 是否存在用户，不存在创建，并且判断用户记录中是否存在不存在创建
            $query_res = \app\custom\AdapayPay::queryMember(aid,'h5',$member_id);
            if($query_res['status'] ==0){
                $rs = \app\custom\AdapayPay::createRealnameMember(aid,'h5',$member_id,$realname,$tel_no,$idcard);
                if(!$rs['data']){
                    return json($rs['msg']);
                }
            }
            $adapay= Db::name('adapay_member')->where('member_id',$member_id)->find();
            if(!$adapay){
                $card_rs = \app\custom\AdapayPay::createSettleAccount(aid,'h5',$member_id,$bankcardnum,
                    $realname,$tel_no,$idcard,$bankname);
                if($card_rs['status'] ==1){
                    $insert = [
                        'aid' => aid,
                        'mid' => mid,
                        'member_id' => $member_id,
                        'appid' => $card_rs['data']['app_id'],
                        'realname' => $realname,
                        'idcard' => $idcard,
                        'createtime' => time(),
                        'bank_name' => $bankname,
                        'card_id' => $bankcardnum,
                        'tel_no' => $tel_no,
                        'settle_account_id' =>$card_rs['data']['id'],
                        'account_info' =>json_encode($card_rs['data']['account_info'],JSON_UNESCAPED_UNICODE),
                        'apply_id' => $card_rs['data']['id'],
                    ];
                    $res = Db::name('adapay_member')->insert($insert);
                    if($res){
                        return  json(['status' => 1,'msg' => '设置成功','smscode_show' =>1]);
                    }else{
                        return  json(['status' => 0,'msg' => '设置失败']);
                    }
                }else{
                    return json($card_rs);
                }
            }else{
                if($adapay['idcard'] && $idcard !=$adapay['idcard']){
                    return  json(['status' => 0,'msg' => '与原创建结算账户使用的身份证不同']);
                }
                if( $realname!=$adapay['realname'] && $adapay['realname']){
                    return  json(['status' => 0,'msg' => '与原创建结算账户使用的银行卡户名不同']);
                }
                //如果新银行卡号和旧银行卡号不一样 再进行删除 重新创建，否则就直接是成功
                if($adapay['idcard'] !=$idcard || !$adapay['settle_account_id']){
                    if($adapay['settle_account_id'] ){
                        //先查询是否存在，再进行删除 再添加
                        $settledata = \app\custom\AdapayPay::querySettleAccount(aid,'h5',$adapay['apply_id'],$member_id,$adapay['settle_account_id']);
                        if($settledata){
                            $delres = \app\custom\AdapayPay::deleteSettleAccount(aid,'h5',$adapay['apply_id'],$adapay['member_id'],$adapay['settle_account_id']);
                            if($delres['status'] ==0){
                                return json($delres['msg']);
                            } 
                        }
                    }
                    $card_rs = \app\custom\AdapayPay::createSettleAccount(aid,'h5',$member_id,$bankcardnum, $realname,$tel_no,$idcard,$bankname);
                    print_r($card_rs);
                    if($card_rs['status'] ==1){
                        $update = [
                            'card_id' => $bankcardnum,
                            'settle_account_id' =>$card_rs['data']['id'],
                            'account_info' =>json_encode($card_rs['data']['account_info'],JSON_UNESCAPED_UNICODE),
                        ];
                        $res = Db::name('adapay_member')->where('id',$adapay['id'])->update($update);
                        print_r($res);
                        return  json(['status' => 1,'msg' => '设置成功']);
                    } else{
                        return  json($card_rs['msg']);
                    }
                } else{
                    return  json(['status' => 1,'msg' => '设置成功']);
                }
   
            }
           
        }
    }

    public function xiaofeimoneylog(){
	    if(getcustom('commission_xiaofei')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_xiaofei_money_log')->field('id,commission,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if(request()->isPost()){

                return $this->json(['status'=>1,'data'=>$datalist,'myxiaofei'=>$this->member['xiaofei_money']] );
            }

            $count = Db::name('member_xiaofei_money_log')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $rdata['myxiaofei'] = $this->member['xiaofei_money'];
            return $this->json($rdata);
        }

    }

    public function invitecashbacklog(){
        $yx_invite_cashback_commission_day = getcustom('yx_invite_cashback_commission_day');
        //看视频领取
        $yx_invite_cashback_video_receive_custom = getcustom('yx_invite_cashback_video_receive');
        if(getcustom('yx_invite_cashback')){
            if(request()->isPost()){
                $pagenum = input('post.pagenum');
                $st      = input('post.st');
                $pernum = 15;
                if(!$pagenum) $pagenum = 1;
                if($pagenum == 1){
                    cache(mid.'pagenum2',null);
                }

                //返现类型 1、余额 2、佣金 3、积分 小数位数
                $moeny_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                if(getcustom('member_money_weishu')){
                    $moeny_weishu = $this->sysset['member_money_weishu'];
                }
                if(getcustom('fenhong_money_weishu')){
                    $commission_weishu = $this->sysset['fenhong_money_weishu'];
                }
                if(getcustom('score_weishu')){
                    $score_weishu = $this->sysset['score_weishu'];
                }

                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                //$where[] = ['status','>=',0];
                $datalist = Db::name('member_invite_cashback_log')->where($where)->page($pagenum,$pernum)->order('update_time desc')->select()->toArray();

                if(!$datalist){
                    $datalist = [];
                }
                if($datalist){
                    foreach($datalist as &$dv){
                    	$dv['money'] = dd_money_format($dv['money'],$moeny_weishu);
                    	$dv['money2'] = dd_money_format($dv['money2'],$moeny_weishu);
                    	$dv['allmoney'] = dd_money_format($dv['allmoney'],$moeny_weishu);

                    	$dv['score'] = dd_money_format($dv['score'],$score_weishu);
                    	$dv['score2'] = dd_money_format($dv['score2'],$score_weishu);
                    	$dv['allscore'] = dd_money_format($dv['allscore'],$score_weishu);

                    	$dv['commission'] = dd_money_format($dv['commission'],$commission_weishu);
                    	$dv['commission2'] = dd_money_format($dv['commission2'],$commission_weishu);
                    	$dv['allcommission'] = dd_money_format($dv['allcommission'],$commission_weishu);

                    	if(getcustom('yx_invite_cashback_time')){
                    		$dv['sendmoney'] = dd_money_format($dv['sendmoney'],$moeny_weishu);
	                    	$dv['sendscore'] = dd_money_format($dv['sendscore'],$score_weishu);
	                    	$dv['sendcommission'] = dd_money_format($dv['sendcommission'],$commission_weishu);
                    	}

                        $dv['tipinfor'] = '';
                        if($dv['status'] == 0){
                            $dv['tipinfor'] = '此为预估返现，具体发放金额以下级确认收货时间为先后顺序，重新计算返现金额';
                        }
                        //查询商品
                        $proname = Db::name('shop_product')->where('id',$dv['proid'])->value('name');
                        $dv['proname'] = !empty($proname)?$proname:'';
                        $dv['create_time'] = date("Y-m-d H:i:s",$dv['update_time']);
                        if($dv['status'] == 0){
                            //重新计算他的预估金额
                            $count_sendback = \app\custom\OrderCustom::count_sendback(aid,$dv);
                            $dv['allmoney']      = $count_sendback['allmoney'];
                            $dv['allscore']      = $count_sendback['allscore'];
                            $dv['allcommission'] = $count_sendback['allcommission'];
                            $dv['status']        = $count_sendback['status'];
                        }else if($dv['status'] == -1){
                            $dv['allmoney']      = 0;
                            $dv['allscore']      = 0;
                            $dv['allcommission'] = 0;
                            $dv['tipinfor']      = $dv['reason'];
                        }

                        if($yx_invite_cashback_commission_day){
                            $dv['yx_invite_cashback_commission_day'] = true;
                            if(in_array($dv['status'],[0,2]) && $dv['back_commission_day'] > 0){
                                $dv['tipinfor']      = t('佣金').'每日返还进度：'.round($dv['back_commission'],2).'/'.$dv['allcommission'];
                            }
                        }
                        if($yx_invite_cashback_video_receive_custom){
                            $startOfDay = strtotime(date('Y-m-d'));
                            if($dv['status'] ==2 ){ //是领取时，判断当前是否已经领取
                                if($dv['back_commission_lasttime'] >= $startOfDay){
                                    $dv['receive_st'] = 1;//已领取
                                }else{
                                    $dv['receive_st'] = 0;
                                }
                            }
                        }
                    }
                }
                $rdata = [];
                if($yx_invite_cashback_video_receive_custom){
                   $sysset =  Db::name('invite_cashback_sysset')->where('aid',aid)->find();
                    $rdata =$sysset; 
                }
                return $this->json(['status'=>1,'data'=>$datalist,'rdata' => $rdata??[]]);
            }
        }
    }
    //领取邀请返现佣金
    public function receiveinvitecash(){
	    if(getcustom('yx_invite_cashback_video_receive')){
	        $id = input('param.id');//记录id
	        $log = Db::name('member_invite_cashback_log')->where('aid',aid)->where('mid',mid)->where('id',$id)->find();
	        if(!$log) return $this->json(['status'=>0,'msg'=>'记录不存在']);
            $gname = Db::name('shop_product')->where('aid',$log['aid'])->where('id',$log['proid'])->value('name') ?? '';
            $remark = '领取商品'.$gname.'邀请返还';
            $allmoney = $log['allmoney'];
            $allscore =  $log['allscore'];
            $allcommission =  $log['allcommission'];
            $mid = $log['mid'];
            $aid = aid;
            //如果不是按天返回的，一次领取完成，否则只领取佣金
            if(!$log['back_commission_lasttime']){
                if($allmoney>0 ){
                    \app\common\Member::addmoney($aid,$mid,$allmoney,$remark);
                }
                if($allscore){
                    \app\common\Member::addscore($aid,$mid,$allscore,$remark);
                }
            }
            $update = [];
            if(!$log['back_commission_day']){
               
                if($allcommission>0 ){
                    \app\common\Member::addcommission($aid,$mid,$log['order_mid'],$allcommission,$remark);
                }
                $update['status'] = 1;
            }else{
                
                $startOfDay = strtotime(date('Y-m-d'));
                if($log['back_commission_lasttime'] >= $startOfDay)return $this->json(['status'=>0,'msg'=>'今日已领取']);
               if($log['back_commission_lasttime'] < $startOfDay){
                   if($log['back_commission'] < $log['allcommission']){
                       //计算每天要发放多少  
                       $mtff = round(bcdiv($log['allcommission'],$log['back_commission_day'],3),2);
                       //计算还有多钱没发
                       $mfdeq = bcsub($log['allcommission'],$log['back_commission'],2);

                       if($mfdeq < $mtff){
                           $mtff = $mfdeq;
                       }
                       $zz = bcadd($log['back_commission'],$mtff,2);
                       if($zz <= $log['allcommission']){
                           //发放
                           $remark =  '领取商品'.$gname.'邀请返还，每日返还进度：'.$zz.'/'.$log['allcommission'];
                           \app\common\Member::addcommission($log['aid'],$log['mid'],$log['order_mid'],$mtff,$remark);
                           $update['back_commission_lasttime'] = time();
                           $update['back_commission'] = $zz;
                           if($zz == $log['allcommission']){
                               //已发放完修改状态
                               $update['status'] = 1;
                           }
                       }
                   }
               } 
            }
            Db::name('member_invite_cashback_log')->where('aid',$log['aid'])->where('id',$log['id'])->where('status',2)->update($update);
            return $this->json(['status'=>1,'msg'=>$remark]);
        }
    }
    public function getRealnameSet()
    {
        if(getcustom('member_realname_verify')){
            $set = Db::name('member_realname_set')->field('status,idno_area_range')->where('aid',aid)->find();

            $field = 'id,headimg,nickname,pid,levelid,realname,usercard,realname_status';
            $userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',mid)->find();
            if($userinfo['realname']){
                $userinfo['realname'] = mb_substr($userinfo['realname'],0,1).'**';
            }
            if($userinfo['usercard']){
                $userinfo['usercard'] = substr($userinfo['usercard'],0,4).'********'.substr($userinfo['usercard'],-4);
            }

            $rdata['set'] 		  = $set ? $set : ['status'=>0];
            $rdata['userinfo']    = $userinfo;
            return $this->json($rdata);
        }
        return $this->json(['set'=>['status'=>0],'userinfo'=>$this->member]);
    }

    public function saveRealname()
    {
        if(getcustom('member_realname_verify')){
            $set = Db::name('member_realname_set')->where('aid',aid)->find();
            if($set['status'] != 1) return $this->json(['status'=>0,'msg'=>'功能未开启']);

            $field = 'id,headimg,nickname,pid,levelid,realname,usercard,realname_status';
            $userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',mid)->find();
            if($userinfo['realname_status']) return $this->json(['status'=>0,'msg'=>'认证通过不可修改']);

            $post = input('post.info');
            if(empty($post['idcard'])){
                return $this->json(['status'=>0,'msg'=>'请上传身份证头像面']);
            }
            if(empty($post['idcard_back'])){
                return $this->json(['status'=>0,'msg'=>'请上传身份证国徽面']);
            }
            //腾讯云识别
            $rs = \app\custom\OCR\Tencent::IDCard($set['tencent_secret_id'],$set['tencent_secret_key'],['ImageUrl'=>$post['idcard']]);
            Log::write($rs);
            if($rs['status'] != 1){
                return $this->json($rs);
            }
            $AdvancedInfo = json_decode($rs['info']['AdvancedInfo'],true);
            if(!empty($AdvancedInfo)){
                return $this->json(['status'=>0,'msg'=>'身份证照片识别失败，请勿翻拍、使用复印件、遮挡、反光、边框不完整，保持证件在有效期内']);
            }
            $rs2 = \app\custom\OCR\Tencent::IDCard($set['tencent_secret_id'],$set['tencent_secret_key'],['ImageUrl'=>$post['idcard_back']]);
            Log::write($rs2);
            if($rs2['status'] != 1){
                return $this->json($rs2);
            }
            $AdvancedInfo = json_decode($rs2['info']['AdvancedInfo'],true);
            if(!empty($AdvancedInfo)){
                return $this->json(['status'=>0,'msg'=>'身份证照片识别失败，请勿翻拍、使用复印件、遮挡、反光、边框不完整，保持证件在有效期内']);
            }
            $idno = $rs['info']['IdNum'];
            //校验
            if($set['idno_area_range']){
                $area_arr = explode(',',$set['idno_area_range']);
                $idno_area = substr($idno,0,6);
                if(!in_array($idno_area,$area_arr)){
                    return $this->json(['status'=>0,'msg'=>'身份证超出区域限制，认证失败']);
                }
            }
            //绑定数量bind_member_num
            if($set['bind_member_num'] > 0){
                $count = Db::name('member')->where('aid',aid)->where('id','<>',mid)->where('usercard',$rs['info']['IdNum'])->count();
                if($count >= $set['bind_member_num']){
                    return $this->json(['status'=>0,'msg'=>'该身份证已被其他人绑定，认证失败']);
                }
            }

            //保存
            $log = [
                'aid'=>aid,
                'mid'=>mid,
                'name'=>$rs['info']['Name'],
                'sex'=>$rs['info']['Sex'],
                'nation'=>$rs['info']['Nation'],
                'birth'=>$rs['info']['Birth'],
                'address'=>$rs['info']['Address'],
                'id_num'=>$rs['info']['IdNum'],
                'authority'=>$rs['info']['Authority'],
                'valid_date'=>$rs['info']['ValidDate'],
                'idcard'=>$post['idcard'],
                'idcard_back'=>$post['idcard_back'],
            ];
            Db::name('member_realname_log')->insert($log);

            $udpate = [
                'realname'=>$rs['info']['Name'],
                'usercard'=>$idno,
                'realname_status'=>1,
                'sex'=>$rs['info']['Sex'] == '男' ? 1 : 2,
//                'birthday'=>$rs['info']['Birth'],
//                'Address'=>$rs['info']['Address']
            ];
            Db::name('member')->where('aid',aid)->where('id',mid)->update($udpate);

            //后置-奖励
            if($userinfo['pid']){
                $field = 'id,headimg,nickname,pid,levelid,realname,usercard,realname_status';
                $parent = Db::name('member')->field($field)->where('aid',aid)->where('id',$userinfo['pid'])->find();
                $plevel = Db::name('member')->where('aid',aid)->where('id',$parent['levelid'])->find();
                if($plevel['can_agent'] > 0 && $plevel['realname_commission1'] > 0) {
                    \app\common\Member::addcommission(aid,$parent['id'],$plevel['realname_commission1'],'下级实名认证奖励');
                }
            }

            return $this->json(['status'=>1,'msg'=>'认证成功']);
        }
    }

	/**
	 * 小额通银行的打款要推送信息到平台签约获取签约地址
	 */
	public function saveRealnameCard()
    {
		if(getcustom('transfer_farsion')){
			$field = 'id,headimg,nickname,pid,levelid,realname,usercard,tel';
            $userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',mid)->find();
            //if($userinfo['xiaoetong_signing_status'] == 1) return $this->json(['status'=>0,'msg'=>'认证已通过']);
			$xetService = new  \app\common\Xiaoetong(aid);
			$post = input('post.');
			if(empty($post['realname']) || empty($post['usercard']) || empty($post['tel'])){
				return $this->json(['status'=>0,'msg'=>'请填写完整信息','data'=>[]]);
			}
			$udpate = [
                'realname'=>$post['realname'],
                'usercard'=>$post['usercard'],
                'tel'=>$post['tel']
            ];
            Db::name('member')->where('aid',aid)->where('id',mid)->update($udpate);
			$rs = Db::name('transfer_farsion_set')->where('aid',aid)->where('bid',0)->find();
			$url = trim($rs['domain_url'],'/').'/front/mobile/#/sign?appid='.$rs['appid'].'&idcard='.$post['usercard'];
			$apidata = [
				'params'=>[[
					'name' => $post['realname'],
					'idcard' => $post['usercard'],
					'phone' => $post['tel'],
					'third_id' => $userinfo['id']
					]
				]
			];
			//查询是否导入
			$personList = $xetService->getPerson([
			   'idcard' => $post['usercard']
			]);
			$res = $xetService->getdata($personList);
			if($res['code'] == 0){
				//是否签约成功	
				if($res['data'][0]['real_status'] == 0 || empty($res['data']['sign'])){
					return $this->json(['status'=>1,'msg'=>'操作成功','data'=>['url'=>$url]]);
				}
			}
			//导入数据
			$personList = $xetService->importPerson($apidata);
			$res = $xetService->getdata($personList);
			if($res['code'] == 0 || $res['code'] == 1005){
				//继续调用签约				
				return $this->json(['status'=>1,'msg'=>'操作成功','data'=>['url'=>$url]]);
			}else{
				return $this->json(['status'=>1,'msg'=>'操作失败'.$res['code'],'data'=>['url'=>$url]]);
			}
		}

	}
	/**
	 * 小额通是否已签约签约获取签约地址
	 */
	public function getXiaoetongSigning()
    {
		if(getcustom('transfer_farsion')){
			$xetService = new  \app\common\Xiaoetong(aid);
			$res = $xetService->getXiaoetongSigning(mid);
			return $this->json($res);
		}

	}

    //分红份数
    public function fhcopieslog(){
        if(getcustom('fenhong_jiaquan_bylevel')) {
            $pagenum = input('param.pagenum');
            if (!$pagenum) $pagenum = 1;
            $mycopies = 0;
            if($pagenum==1){
                $mycopies = Db::name('member')->where('aid',aid)->where('id',$this->mid)->value('fhcopies');
            }
            $pernum = 20;
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['mid', '=', mid];
            $datalist = Db::name('member_fhcopies_log')->field('id,copies,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            if (!$datalist) $datalist = [];
            $rdata = [];
            $rdata['datalist'] = $datalist;
            $rdata['mycopies'] = $mycopies;
            return $this->json($rdata);
        }
        return $this->json(['status'=>1,'datalist'=>[]]);
    }

	public function getmemberinfo(){
		$member = Db::name('member')->field('id,nickname,headimg,tel')->where('id',mid)->find();
		return $this->json(['status'=>1,'data'=>$member]);
	}

    public function tongzhenglog(){
	    if(getcustom('product_givetongzheng')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_tongzhenglog')->field('id,money,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if($datalist){
                foreach($datalist as $k=>$v){
                    //$datalist[$k]['tonzgheng'] = dd_money_format($v['score'],$score_weishu);
                }
            }
            $member_tongzheng = $this->member['tongzheng'];
            $release_total = Db::name('tongzheng_order_log')->where('mid',mid)->sum('remain');
            $tongzheng_transfer = 0;

            if($pagenum == 1) {
                $set = Db::name('admin_set')->where('aid', aid)->find();
                $tongzheng_transfer = $set['tongzheng_transfer'] ? true : false;
            }
            if(request()->isPost()){

                return $this->json(['status'=>1,'data'=>$datalist,'mytongzheng'=>$member_tongzheng,'release_total'=>$release_total,'tongzheng_transfer'=>$tongzheng_transfer] );
            }
            $count = Db::name('member_tongzhenglog')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $rdata['mytongzheng'] = $member_tongzheng;
            $rdata['release_total'] = $release_total;
            return $this->json($rdata);
        }

    }
    public function tongzheng_releaselog(){
        if(getcustom('product_givetongzheng')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            $pid = input('pid');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if($pid){
                $where[] = ['pid','=',$pid];
            }
            $datalist = Db::name('tongzheng_release_log')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if($datalist){
                foreach($datalist as $k=>$v){
                    $datalist[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                }
            }
            if(request()->isPost()){
                return $this->json(['status'=>1,'data'=>$datalist] );
            }
            $count = Db::name('tongzheng_release_log')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }
    public function tongzheng_transfer()
    {
        if(getcustom('product_givetongzheng')) {
            $mid = input('param.mid/d',0);
            $set = Db::name('admin_set')->where('aid',aid)->find();
            if($set['tongzheng_transfer'] != 1) {
                return $this->json(['status'=>0,'msg'=>'未开启此功能']);
            }

            if(request()->isPost()){
                $mobile = input('post.mobile');
                $mid = input('post.mid/d');
                $money = input('post.money/f');
                if ($money < 0.001){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的金额，最小金额为：0.001']);
                }
                if (input('?post.mobile')) {
                    $member = Db::name('member')->where('aid', aid)->where('tel', $mobile)->find();
                }
                if (input('?post.mid')) {
                    $member = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
                }
                if(!$member) return $this->json(['status'=>0,'msg'=>'未找到该'.t('会员')]);
                $user_id = $member['id'];

                if ($user_id == mid) {
                    return $this->json(['status'=>0,'msg'=>'不能转账给自己']);
                }

                if ($money > $this->member['tongzheng']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('通证').'不足']);
                }
                //验证支付密码
                $pwd_check = $set['tongzheng_transfer_pwd'];
                if($pwd_check){
                    if(!$this->member['paypwd']){
                        return $this->json(['status'=>0,'msg'=>'请先设置支付密码','set_paypwd'=>1]);
                    }
                    $pay_pwd = input('paypwd')?:'';
                    if(!\app\common\Member::checkPayPwd($this->member,$pay_pwd )){
                        return $this->json(['status'=>0,'msg'=>'支付密码输入错误']);
                    }
                }
                $midMsg = sprintf("转账给：%s",$member['nickname']);
                $toMidMsg = sprintf("来自%s的转账", $this->member["nickname"]);

                $rs = \app\common\Member::addtongzheng(aid,mid,$money * -1, $midMsg);
                if ($rs['status'] == 1) {
                    \app\common\Member::addtongzheng(aid,$user_id,$money,$toMidMsg,$this->mid);
                }else{
                    return $this->json(['status'=>0, 'msg' => '转账失败']);
                }
                return $this->json(['status'=>1, 'msg' => '转账成功', 'url'=>'/pages/my/usercenter']);
            }
            $tomember = [];
            if($mid){
                $tomember = Db::name('member')->where('aid',aid)->where('id',$mid)->field('id,money,nickname,headimg')->find();
            }
            $rdata['paycheck'] = $set['tongzheng_transfer_pwd'] ? true : false;
            $rdata['status'] = 1;
            $rdata['mytongzheng'] = $this->member['tongzheng'];
            $rdata['tomember'] = $tomember?$tomember:['nickname'=>''];//转给谁
            return $this->json($rdata);
        }
    }
    public function tongzheng_orderlog(){
        if(getcustom('product_givetongzheng')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('tongzheng_order_log')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if($datalist){
                $status_arr = [0=>'释放中',1=>'释放完成',2=>'订单删除'];
                foreach($datalist as $k=>$v){
                    $datalist[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                    $datalist[$k]['status_str'] = $status_arr[$v['status']];
                }
            }
            if(request()->isPost()){
                return $this->json(['status'=>1,'data'=>$datalist] );
            }
            $count = Db::name('tongzheng_order_log')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }

	public function commission_withdraw_scorelog(){
		if(getcustom('commission_duipeng_score_withdraw')){		
			$pagenum = input('post.pagenum');
			if(!$pagenum) $pagenum = 1;
			$pernum = 20;
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['mid','=',mid];
			$datalist = Db::name('member_commission_withdraw_scorelog')->field('id,score,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];

			$count = Db::name('member_commission_withdraw_scorelog')->where($where)->count();

			$rdata = [];
			$rdata['count'] = $count;
			$rdata['datalist'] = $datalist;
			$rdata['pernum'] = $pernum;
			$rdata['myscore'] = $this->member['commission_withdraw_score'];
			return $this->json($rdata);
		}
	}
	//消费通知开关
	public function  setFinanceNoticeSwitch(){
	    if(getcustom('restaurant_finance_notice_switch')){
            $field = input('param.field');//tmpl 公众号通知   sms短信通知
            $value = input('param.value');
            if($field =='tmpl' || $field =='sms' ){
                $update = ['is_receive_finance_'.$field => $value];
                Db::name('member')->where('aid',aid)->where('id',mid)->update($update);
            }
            return $this->json(['status' =>1,'msg' =>'']);
        }
    }

    public function crk_stcoklog(){
        if(getcustom('ciruikang_fenxiao')){
            if(request()->isPost()){
                //统计购买自己及下级购买的商品数量
                $pagenum = input('post.pagenum');
                $st = input('post.st');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['m.pid','=',mid];
                $where[] = ['m.aid','=',aid];
                $datalist = Db::name('member')->alias('m')->join('member_product_stock ps','ps.mid = m.id')
                    ->where($where)->group('m.id')
                    ->page($pagenum,$pernum)->order('allnum desc')
                    ->field('m.id,m.headimg,m.nickname,sum(ps.num) as allnum')
                    ->select()->toArray();
                if(!$datalist) $datalist = [];
                //统计自己购买的鞋数
                $allnum = Db::name('member_product_stock')->where('mid',mid)->where('aid',aid)->sum('num');
                return $this->json(['status'=>1,'data'=>$datalist,'mynum'=>$allnum??0] );
            }
        }
    }

    public function serviceFeeLog(){
        $st = input('param.st');
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;
        $pernum = 20;
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['mid','=',mid];
        if($st == 1){//充值记录
            $datalist = Db::name('servicefee_recharge_order')->field("id,money,`status`,from_unixtime(createtime) createtime")->where($where)->where('status=1')->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
        }else{ //余额明细
            $datalist = Db::name('member_servicefee_log')->field("id,service_fee as money,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
        }

        $admin_set = Db::name('admin_set')->field('moneypay,recharge,withdraw')->where('aid',aid)->find();

        $showstatus = [];
        $showstatus[] = $admin_set['moneypay'] ;
        $showstatus[] = $admin_set['recharge'];

        return $this->json(['status'=>1,'data'=>$datalist,'showstatus' => $showstatus]);
    }

    public function activecoinlog(){
	    if(getcustom('active_coin')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_activecoin_log')->field('id,value,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            $score_weishu = 2;

            if($datalist){
                foreach($datalist as $k=>$v){
                    $datalist[$k]['value'] = dd_money_format($v['value'],$score_weishu);
                }
            }
            if(request()->isPost()){
                if($pagenum == 1) {
                    $set = Db::name('consumer_set')->where('aid', aid)->find();
                }
                $member_score = dd_money_format($this->member['active_coin'],$score_weishu);

                return $this->json(['status'=>1,'data'=>$datalist,'myscore'=>$member_score,
                    'set' => $set] );
            }

            $count = Db::name('member_activecoin_log')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $active_coin = Db::name('member')->where('id',mid)->value('active_coin');
            $member_score = dd_money_format($active_coin,$score_weishu);
            $rdata['myscore'] = $member_score;
            return $this->json($rdata);
        }
    }
    public function scoreweightlog(){
        if(getcustom('yx_buy_fenhong')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('buy_fenhong_log')->field('id,score_weight,score,from_unixtime(createtime)createtime,remark')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            $score_weishu = 2;

            if($datalist){
                foreach($datalist as $k=>$v){
                    $datalist[$k]['value'] = dd_money_format($v['value'],$score_weishu);
                }
            }
            if(request()->isPost()){
               
                $member_score_weight = $this->member['buy_fenhong_score_weight'];

                return $this->json(['status'=>1,'data'=>$datalist,'myscoreweight'=>$member_score_weight] );
            }

            $count = Db::name('buy_fenhong_log')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $active_coin = Db::name('member')->where('id',mid)->value('active_coin');
            $member_score = dd_money_format($active_coin,$score_weishu);
            $rdata['myscore'] = $member_score;
            return $this->json($rdata);
        }
    }
    
    public function commissionmaxtoscore(){
        $add_commission_max = getcustom('add_commission_max')?:0;
        if(getcustom('member_commission_max_toscore')){
            $userinfo = [];
//            $commission_max = $this->member['commission_max']?:0;
//            $totalcommission = $this->member['totalcommission']?:0;
//            $userinfo['commission_max'] = bcsub($commission_max,$totalcommission,2);
            $userinfo['commission_max'] =  $this->member['commission_max']?:0;
            if($add_commission_max){
                $userinfo['commission_max_self'] =  $this->member['commission_max_self']?:0;
                $userinfo['commission_max_plate'] =  $this->member['commission_max_plate']?:0;
            }else{
                $userinfo['commission_max_self'] =  $userinfo['commission_max']?:0;
                $userinfo['commission_max_plate'] =  0;
            }

            $sysset = Db::name('admin_set')->where('aid',aid)->field('member_commission_max,member_commission_max_toscore_st,member_commission_max_toscore_ratio')->find();
            if(!$sysset['member_commission_max_toscore_st'] || !$sysset['member_commission_max'])return $this->json(['status'=>0,'msg'=>'功能未开启']);
            $userinfo['member_commission_max'] = $sysset['member_commission_max'];
            $userinfo['member_commission_max_toscore_st'] = $sysset['member_commission_max_toscore_st'];
            $userinfo['member_commission_max_toscore_ratio'] = $sysset['member_commission_max_toscore_ratio'];
            $rdata['userinfo'] = $userinfo;
            if(request()->isPost()){
                Db::startTrans();
                if(!$sysset['member_commission_max_toscore_st'] || !$sysset['member_commission_max'])return $this->json(['status'=>0,'msg'=>'功能未开启']);
                $money =  input('param.money');
                if($money <= 0)return $this->json(['status'=>0,'msg'=>'请输入需要转换的'.t('佣金上限').'值']);
                if($money > $userinfo['commission_max_self']) return $this->json(['status'=>0,'msg'=>t('佣金上限').'值不足']);
                $score = dd_money_format( $money * $sysset['member_commission_max_toscore_ratio'] * 0.01,0);
                if($score < 1)return $this->json(['status'=>0,'msg'=>'输入的'.t('佣金上限').'值过低']);
                \app\common\Member::addscore(aid,mid,$score,t('佣金上限').'转'.t('积分'));
                $res = \app\common\Member::addcommissionmax(aid,mid,$money*-1,t('佣金上限').'转'.t('积分'));
                if(getcustom('greenscore_max')){
                    $maximum = $money>$this->member['maximum']?$this->member['maximum']:$money;
                    $rs = \app\common\Member::addmaximum(aid,mid,$maximum*-1,t('佣金上限').'转入'.t('积分').'扣除');
                }
                Db::commit();
                return $this->json(['status'=>1,'msg'=>'提交成功']);
            }
            return $this->json($rdata);
        }
    }
    public function getcommissionmaxlog(){
	    if(getcustom('member_commission_max_toscore')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_commissionmax_log')->field('id,value,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if($datalist){
                foreach($datalist as $k=>$v){
                    $datalist[$k]['value'] = $v['value'];
                }
            }
            $count = Db::name('member_commissionmax_log')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $commission_max = $this->member['commission_max']?:0;
            //$totalcommission = $this->member['totalcommission']?:0;
            //$commission_max = bcsub($commission_max,$totalcommission,2);
            $rdata['commission_max'] = $commission_max;
            return $this->json($rdata);
        }
    }
    
    //获取商品的收藏
    public function getShopFavorite(){
	    if(getcustom('member_shop_favorite')) {
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['f.aid', '=', aid];
            $where[] = ['f.mid', '=', mid];
            $where[] = ['f.type', '=', 'shop'];
            if (input('param.keyword')) $where[] = ['s.name', 'like', '%' . input('param.keyword') . '%'];
            if (input('param.cid')) {
                $cid = input('post.cid') ? input('post.cid/d') : input('param.cid/d');
                $where2 = "find_in_set('-1',showtj)";
                if ($this->member) {
                    $where2 .= " or find_in_set('" . $this->member['levelid'] . "',showtj)";
                    if ($this->member['subscribe'] == 1) {
                        $where2 .= " or find_in_set('0',showtj)";
                    }
                }
                $tjwhere[] = Db::raw($where2);
                //子分类
                $clist = Db::name('shop_category')->where($tjwhere)->where('aid', aid)->where('pid', $cid)->column('id');
                if ($clist) {
                    $clist2 = Db::name('shop_category')->where($tjwhere)->where('aid', aid)->where('pid', 'in', $clist)->column('id');
                    $cCate = array_merge($clist, $clist2, [$cid]);
                    if ($cCate) {
                        $whereCid = [];
                        foreach ($cCate as $k => $c2) {
                            $whereCid[] = "find_in_set({$c2},s.cid)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
                } else {
                    $where[] = Db::raw("find_in_set(" . $cid . ",s.cid)");
                }
            }
            //商家的商品分类 
            if (input('param.cid2')) {
                $cid2 = input('post.cid2') ? input('post.cid2/d') : input('param.cid2/d');
                //子分类
                $clist = Db::name('shop_category2')->where('aid', aid)->where('pid', $cid2)->column('id');
                if ($clist) {
                    $clist2 = Db::name('shop_category2')->where('aid', aid)->where('pid', 'in', $clist)->column('id');
                    $cCate = array_merge($clist, $clist2, [$cid2]);
                    if ($cCate) {
                        $whereCid = [];
                        foreach ($cCate as $k => $c2) {
                            $whereCid[] = "find_in_set({$c2},s.cid2)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
                } else {
                    $where[] = Db::raw("find_in_set(" . $cid2 . ",s.cid2)");
                }
            }
            if (input('param.field') && input('param.order')) {
                $order = 's.' . input('param.field') . ' ' . input('param.order') . ',s.sort desc,s.id desc';
            } else {
                $order = 's.sort desc,s.id desc';
            }
            $where[] = ['status','=',1];
            $shop_set = Db::name('shop_sysset')->where('aid',aid)->field('sellprice_name,sellprice_color')->find();
            $datalist = Db::name('member_favorite')->alias('f')
                ->join('shop_product s', 's.id = f.proid')
                ->where($where)
                ->field('f.*,s.id,s.name,s.pic,s.sales,s.cid,s.cid2,s.sort,s.sell_price,s.lvprice_data')
                ->page($pagenum, $pernum)
                ->order($order)
                ->select()->toArray();
            foreach($datalist as $key=>$val){
                $lvprice_data = json_decode($val['lvprice_data'], true);
                if($lvprice_data){
                    $datalist[$key]['sell_price'] = floatval( $lvprice_data[$this->member['levelid']]);
                }else{
                    $datalist[$key]['sell_price'] = floatval( $val['sell_price']);
                }
                $datalist[$key]['price'] = floatval( $val['price']);
                $datalist[$key]['sellprice_color'] = $shop_set['sellprice_color'];
                $datalist[$key]['sellprice_name'] = $shop_set['sellprice_name'];
            }
            $count = Db::name('member_favorite')->alias('f')
                ->join('shop_product s', 's.id = f.proid')
                ->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $datalist;
            $rdata['pernum'] = $pernum;
            return $this->json($rdata);
        }
    }

    public function staffcommissionlog(){
        if(getcustom('extend_staff')){
            if(request()->isPost()){
                $staff = Db::name('staff')->where('mid',mid)->where('aid',aid)->find();
                if(!$staff){
                    return $this->json(['status'=>1,'data'=>[],'mycommission'=>0] );
                }
                $pagenum = input('post.pagenum');
                $st = input('post.st');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['sid','=',$staff['id']];
                $datalist = Db::name('staff_commission_log')->field('id,commission,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                return $this->json(['status'=>1,'data'=>$datalist,'mycommission'=>$staff['commission']] );
            }
        }
    }

    public function silvermoneylog(){
	    if(getcustom('member_goldmoney_silvermoney')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_silvermoneylog')->field('id,silvermoney,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if(request()->isPost()){
                return $this->json(['status'=>1,'data'=>$datalist,'mysilvermoney'=>$this->member['silvermoney']] );
            }
            $count = Db::name('member_silvermoneylog')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $rdata['mysilvermoney'] = $this->member['silvermoney'];
            return $this->json($rdata);
        }
    }

    public function goldmoneylog(){
	    if(getcustom('member_goldmoney_silvermoney')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_goldmoneylog')->field('id,goldmoney,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if(request()->isPost()){
                return $this->json(['status'=>1,'data'=>$datalist,'mygoldmoney'=>$this->member['goldmoney']] );
            }
            $count = Db::name('member_goldmoneylog')->where($where)->count();

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $rdata['mygoldmoney'] = $this->member['goldmoney'];
            return $this->json($rdata);
        }
    }

    public function linghuoxinsign()
    {
        if(getcustom('extend_linghuoxin')){
            $set = Db::name('linghuoxin_set')->where('aid',aid)->find();
            if(!$set){
                return $this->json(['status'=>0,'msg'=>'系统设置不存在，暂不能使用']);
            }
            if(empty($set['app_id']) || empty($set['secret']) || empty($set['corpid']) || empty($set['taskid']) || empty($set['apiurl'])){
                return $this->json(['status'=>0,'msg'=>'系统设置不完善，暂不能使用']);
            }
            if(request()->isPost()){
                $realname = input('?param.realname')?input('realname'):'';
                $tel      = input('?param.tel')?input('tel'):'';
                $usercard = input('?param.usercard')?input('usercard'):'';
                if(!$realname || !$tel || !$usercard){
                    return $this->json(['status'=>0,'msg'=>'请填写完整信息']);
                }
                if(!checkIdCard($usercard)){
                    return $this->json(['status'=>0,'msg'=>'身份证号码错误']);
                }
            }else{
                $usercard = $this->member['usercard'];
            }

            if($usercard){
                //查看是否签约
                $getchecksign = \app\custom\LinghuoxinCustom::getchecksign(aid,0,$usercard,$set);
                if($getchecksign && $getchecksign['status'] == 1){
                    if($getchecksign['data']['status'] == 2){
                        return $this->json(['status'=>0,'msg'=>'已签约成功']);
                    }else if($getchecksign['data']['status'] == 1){
                        return $this->json(['status'=>0,'msg'=>'已实名认证，等待签约中']);
                    }
                }else{
                    //return $this->json($getchecksign);
                }
            }
            //验证身份证是否提交过
            if($this->member['linghuoxin_signlogid']){
                $signlog = Db::name('member_linghuoxin_signlog')->where('id',$this->member['linghuoxin_signlogid'])->where('mid',mid)->where('usercard',$usercard)->field('id,status')->find();
            }else{
                $signlog = [];
            }
            if(request()->isPost()){
                //记录签约
                $log = [];
                $log['realname'] = $realname;
                $log['tel']      = $tel;
                $log['usercard'] = $usercard;
                if(!$signlog){
                    $log['aid'] = aid;
                    $log['mid'] = mid;
                    $log['createtime'] = time();
                    $logid = Db::name('member_linghuoxin_signlog')->insertGetId($log);
                    if(!$logid){
                        return $this->json(['status'=>0,'msg'=>'操作失败']);
                    }
                }else{
                    $log['updatetime'] = time();
                    $up = Db::name('member_linghuoxin_signlog')->where('id',$signlog['id'])->update($log);
                    if(!$up){
                        return $this->json(['status'=>0,'msg'=>'操作失败']);
                    }
                    $logid = $signlog['id'];
                }

                $udpate = [
                    'realname'=>$realname,
                    'tel'=>$tel,
                    'usercard'=>$usercard,
                    'linghuoxin_signlogid'=>$logid,
                ];
                //更新信息
                $up = Db::name('member')->where('aid',aid)->where('id',mid)->update($udpate);

                //h5签约链接:二要素（身份证+人脸）
                $signurl = $set['apiurl'].'/faquick/face?companyId='.$set['app_id'].'&thirdId='.mid.'&name='.$realname.'&idcard='.$usercard.'&phone='.$tel;
                //小程序
                $signurl2 = 'https://sign.linghuoxin.com/faindex?companyId='.$set['app_id'].'&thirdId='.mid.'&name='.$realname.'&idcard='.$usercard.'&phone='.$tel.'&env=miniprogram';
                return $this->json(['status'=>1,'msg'=>'操作成功','signurl'=>$signurl,'signurl2'=>$signurl2,'signappid'=>'wx01e9e17c8c07189c','signstatus'=>true]);
            }else{
                //灵活新签约
                $userinfo = [];
                $userinfo['realname'] = $this->member['realname'];
                $userinfo['tel']      = $this->member['tel'];
                $userinfo['usercard'] = $this->member['usercard'];

                $rdata = [];
                $rdata['status']     = 1;
                $rdata['signstatus'] = false;
                $rdata['userinfo']   = $userinfo;
                return $this->json($rdata);
            }
        }
    }
    // 穿衣风格
    public function getStyleType(){
    	$styles = Db::name('clothing_style')->where('aid',aid)->order('sort desc')->column('name');
    	$rdata = [];
        $rdata['status'] = 1;
        $rdata['data']   = $styles;
        return $this->json($rdata);
    }

    // 设置提现自定义账户
    public function setCustomAccess(){
        if(getcustom('withdraw_custom')){
            $member = Db::name('member')
                ->field('customaccountname,customaccount,customtel')
                ->where('aid',aid)
                ->where('id',mid)
                ->find();

            $sysset =  Db::name('admin_set')
                ->field('custom_name,custom_status,withdraw_desc')
                ->where('aid',aid)
                ->find();

            return $this->json(['status'=>1,'sysset'=>$sysset,'account'=>$member]);
        }
    }

    //团队成员信息
    public function teamMemberinfo(){
        if(getcustom('team_update_member_info')) {
            $mid = input('post.mid');
            if (empty($mid)) {
                return $this->json(['status' => 0, 'msg' => '参数错误']);
            }

            $field = 'id,headimg,nickname,realname,sex,province,city,birthday,form_record_id';
            $userinfo = Db::name('member')->where('id', $mid)->field($field)->find();
            if ($userinfo['realname'] == null) $userinfo['realname'] = '';
            if ($userinfo['sex'] == null) $userinfo['sex'] = '';
            if ($userinfo['province'] == null) $userinfo['province'] = '';
            if ($userinfo['city'] == null) $userinfo['city'] = '';
            if ($userinfo['birthday'] == null) $userinfo['birthday'] = '';
            $rdata = [];
            $rdata['userinfo'] = $userinfo;
            //注册自定义
            $register_forms = [];
            if (getcustom('register_fields')) {
                //系统设置参数
                $sys_forms = Db::name("register_form")->where("aid", aid)->find();
                if ($sys_forms) {
                    $content = json_decode($sys_forms['content'], true);
                    if ($userinfo['form_record_id'] > 0) {
                        $register_record = Db::name('register_form_record')->where('id', $userinfo['form_record_id'])->find();
                        if ($register_record) {
                            foreach ($content as $k => $item) {
                                if (!in_array($item['key'], ['realname', 'sex', 'birthday'])) {
                                    $item['content'] = $register_record['form' . $k] ?? '';
                                    $register_forms[$k] = $item;
                                }
                            }
                        }
                    }
                }
            }
            $rdata['register_forms'] = $register_forms;

            return $this->json($rdata);
        }
    }

    public function yunstUser()
    {
        if(getcustom('pay_allinpay')){
        	//直接创建通联会员
            $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
            //通联会员
            if(request()->isPost()){
            	if($yunstuser && $yunstuser['status'] == 1){
	                return $this->json(['status'=>0,'msg'=>'已创建成功','goback'=>true]);
	            }
                $bizUserId = input('?param.bizUserId')?trim(input('bizUserId')):'';
                if(!$bizUserId){
                    return $this->json(['status'=>0,'msg'=>'请输入会员名称']);
                }
                //查询是否添加过
		        $old = Db::name('member_allinpay_yunst_user')->where('bizUserId',$bizUserId)->where('memberType',3)->field('id')->find();
		        if($old && $old['id'] != $yunstuser['id']){
		            return $this->json(['status'=>0,'msg'=>'此会员已有用户使用']);
		        }
                $createMember = \app\custom\AllinpayYunst::createMember(aid,$bizUserId);
                if(!$createMember || $createMember['status'] != 1){
                    $msg = $createMember && $createMember['msg']?$createMember['msg']:'操作失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$createMember['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }

                $checkset = \app\custom\AllinpayYunst::checkset(aid);
	            if($checkset['status'] == 0){
	                return $this->json($checkset);
	            }
	            $set = $checkset['set'];

                //记录签约
                $data = [];
                $data['aid'] = aid;
                $data['mid'] = mid;
                $data['appId']      = $set['app_id'];
                $data['bizUserId']  = $bizUserId;
                $data['userId']     = $createMember['data']['userId'];
                $data['status']     = 1;
                $data['createtime'] = time();
                $dataid = Db::name('member_allinpay_yunst_user')->insertGetId($data);

                return $this->json(['status'=>1,'msg'=>'创建成功','signstatus'=>2]);
            }else{
            	$type = input('?param.type')?input('param.type'):'';
            	if($type && $type == 'bindphone'){
            		if(!empty($yunstuser['phone'])) return $this->json(['status'=>0,'msg'=>'已绑定支付手机号']);
            	}
                $rdata = [];
                $rdata['status']     = 1;

                $signstatus = 1;
                if($yunstuser){
                	if(empty($yunstuser['phone'])){
                		$signstatus = 2;
                		$yunstuser['phone']  = $this->member['tel']??'';
                	}else if(empty($yunstuser['identityNo'])){
                		$signstatus = 3;
                		$yunstuser['name']       = $this->member['realname']??'';
		                $yunstuser['identityNo'] = $this->member['usercard']??'';
                	}else if(empty($yunstuser['cardNo'])){
                		$signstatus = 4;
                		$yunstuser['cardNo']    = $this->member['bankcardnum']??'';
                		$yunstuser['cardPhone'] = $yunstuser['phone'];
                	}else{
                		$signstatus = 0;
                	}
                }
                if(!$signstatus){
                	return $this->json(['status'=>0,'msg'=>'已完成注册']);
                }
                $rdata['data']       = $yunstuser?$yunstuser:'';
                $rdata['signstatus'] = $signstatus;
                $rdata['cardCheck']  = 8;//8 银行卡四要素验证 无需调用【确认绑定银行卡】;
                return $this->json($rdata);
            }
        }
    }

    public function yunstSendSmsCode()
    {
        if(getcustom('pay_allinpay')){
            //发送短信验证码
            if(request()->isPost()){
                $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                if(!$yunstuser){
                    return $this->json(['status'=>0,'msg'=>'通联会员不存在']);
                }
                if($yunstuser['phone']){
                    return $this->json(['status'=>0,'msg'=>'已绑定手机号']);
                }
                $bizUserId = $yunstuser['bizUserId'];
                if(!$bizUserId){
                    return $this->json(['status'=>0,'msg'=>'会员名称不存在']);
                }
                $phone = input('?param.phone')?trim(input('phone')):'';
                if(!$phone){
                    return $this->json(['status'=>0,'msg'=>'请输入手机号']);
                }
                $sendVerificationCode = \app\custom\AllinpayYunst::sendVerificationCode(aid,$bizUserId,$phone,9);
                if(!$sendVerificationCode || $sendVerificationCode['status'] != 1){
                    $msg = $sendVerificationCode && $sendVerificationCode['msg']?$sendVerificationCode['msg']:'发送失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$sendVerificationCode['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }
                //Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update(['phone'=>$phone]);
                return $this->json(['status'=>1,'msg'=>'发送成功']);
            }
        }
    }

    public function yunstBindPhone()
    {
        if(getcustom('pay_allinpay')){
            //绑定手机号
            if(request()->isPost()){
                $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                if(!$yunstuser){
                    return $this->json(['status'=>0,'msg'=>'通联会员不存在']);
                }
                if($yunstuser['phone']){
                    return $this->json(['status'=>0,'msg'=>'已绑定手机号']);
                }
                $bizUserId = $yunstuser['bizUserId'];
                if(!$bizUserId){
                    return $this->json(['status'=>0,'msg'=>'会员名称不存在']);
                }
                $phone = input('?param.phone')?trim(input('phone')):'';
                if(!$phone){
                    return $this->json(['status'=>0,'msg'=>'请输入手机号']);
                }
                $verificationCode = input('?param.verificationCode')?trim(input('verificationCode')):'';
                if(!$verificationCode){
                    return $this->json(['status'=>0,'msg'=>'请输入验证码']);
                }
                $bindPhone = \app\custom\AllinpayYunst::bindPhone(aid,$bizUserId,$phone,$verificationCode);
                if(!$bindPhone || $bindPhone['status'] != 1){
                    $msg = $bindPhone && $bindPhone['msg']?$bindPhone['msg']:'发送失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$bindPhone['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }
                Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update(['phone'=>$phone]);
                return $this->json(['status'=>1,'msg'=>'绑定成功','signstatus'=>3]);
            }
        }
    }

    public function yunstRenzheng()
    {
        if(getcustom('pay_allinpay')){
            //实名认证
            if(request()->isPost()){
                $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                if(!$yunstuser){
                    return $this->json(['status'=>0,'msg'=>'通联会员不存在']);
                }
                if($yunstuser['identityNo']){
                    return $this->json(['status'=>0,'msg'=>'已认证']);
                }
                $bizUserId = $yunstuser['bizUserId'];
                if(!$bizUserId){
                    return $this->json(['status'=>0,'msg'=>'会员名称不存在']);
                }
                $name = input('?param.name')?trim(input('name')):'';
                if(!$name){
                    return $this->json(['status'=>0,'msg'=>'请输入手机号']);
                }
                $identityNo = input('?param.identityNo')?trim(input('identityNo')):'';
                if(!$identityNo){
                    return $this->json(['status'=>0,'msg'=>'请输入身份证号']);
                }
                // if(!checkIdCard($identityNo)){
                //     return $this->json(['status'=>0,'msg'=>'请输入正确的身份证号']);
                // }
                $setRealName = \app\custom\AllinpayYunst::setRealName(aid,$bizUserId,$name,$identityNo);
                if(!$setRealName || $setRealName['status'] != 1){
                    $msg = $setRealName && $setRealName['msg']?$setRealName['msg']:'认证失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$setRealName['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }
                Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update(['name'=>$name,'identityNo'=>$identityNo]);
                Db::name('member')->where('id',mid)->where('aid',aid)->update(['realname'=>$name,'usercard'=>$identityNo]);
                return $this->json(['status'=>1,'msg'=>'认证成功','signstatus'=>4]);
            }
        }
    }

    public function yunstApplyBindBankCard()
    {
        if(getcustom('pay_allinpay')){
            //请求绑定银行卡
            if(request()->isPost()){
                $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                if(!$yunstuser){
                    return $this->json(['status'=>0,'msg'=>'通联会员不存在']);
                }
                if($yunstuser['cardNo']){
                    return $this->json(['status'=>0,'msg'=>'已绑定银行卡']);
                }
                $bizUserId = $yunstuser['bizUserId'];
                if(!$bizUserId){
                    return $this->json(['status'=>0,'msg'=>'会员名称不存在']);
                }
                $cardNo = input('?param.cardNo')?trim(input('cardNo')):'';
                if(!$cardNo){
                    return $this->json(['status'=>0,'msg'=>'请输入银行卡']);
                }
                $cardPhone = input('?param.cardPhone')?trim(input('cardPhone')):'';
                if(!$cardPhone){
                    return $this->json(['status'=>0,'msg'=>'请输入银行预留手机号']);
                }
                $name = $yunstuser['name'];
                if(!$name){
                    return $this->json(['status'=>0,'msg'=>'真实姓名不存在']);
                }
                $identityNo = $yunstuser['identityNo'];
                if(!$identityNo){
                    return $this->json(['status'=>0,'msg'=>'身份证号不存在']);
                }
                $cardCheck = 8;//8 银行卡四要素验证 无需调用【确认绑定银行卡】;
                $applyBindBankCard = \app\custom\AllinpayYunst::applyBindBankCard(aid,$bizUserId,$cardNo,$cardPhone,$name,$identityNo,$cardCheck);
                
                if(!$applyBindBankCard || $applyBindBankCard['status'] != 1){
                    $msg = $applyBindBankCard && $applyBindBankCard['msg']?$applyBindBankCard['msg']:'操作失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$applyBindBankCard['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }

				$updata = [];
				if($cardCheck != 8){
	                $updata['tranceNum'] = $applyBindBankCard['data']['tranceNum'];
	                $updata['transDate'] = $applyBindBankCard['data']['transDate'];
	            }else{
	            	$updata['cardNo']    = $cardNo;
	            	$updata['cardPhone'] = $cardPhone;
	            }
                $updata['bankCode']  = $applyBindBankCard['data']['bankCode']??'';
                $updata['bankName']  = $applyBindBankCard['data']['bankName']??'';
                $updata['cardType']  = $applyBindBankCard['data']['cardType']??'';
                Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update();
                return $this->json(['status'=>1,'msg'=>'发送成功']);
            }
        }
    }

    public function yunstBindBankCard()
    {
        if(getcustom('pay_allinpay')){
            //绑定银行卡号
            if(request()->isPost()){
                $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                if(!$yunstuser){
                    return $this->json(['status'=>0,'msg'=>'通联会员不存在']);
                }
                if($yunstuser['cardNo']){
                    return $this->json(['status'=>0,'msg'=>'已绑定银行卡号']);
                }
                $bizUserId = $yunstuser['bizUserId'];
                if(!$bizUserId){
                    return $this->json(['status'=>0,'msg'=>'请输入会员名称']);
                }
                //流水号
                $tranceNum = $yunstuser['tranceNum'];
                if(!$tranceNum){
                	//发送验证吗得来
                    return $this->json(['status'=>0,'msg'=>'发送验证码失败']);
                }
                $cardNo = input('?param.cardNo')?trim(input('cardNo')):'';
                if(!$cardNo){
                    return $this->json(['status'=>0,'msg'=>'请输入银行卡号']);
                }
                $cardPhone = input('?param.cardPhone')?trim(input('cardPhone')):'';
                if(!$cardPhone){
                    return $this->json(['status'=>0,'msg'=>'请输入银行预留手机号']);
                }
                $verificationCode = input('?param.verificationCode')?trim(input('verificationCode')):'';
                if(!$verificationCode){
                    return $this->json(['status'=>0,'msg'=>'请输入验证码']);
                }

                $bindBankCard = \app\custom\AllinpayYunst::bindBankCard(aid,$bizUserId,$tranceNum,$cardPhone,$verificationCode);
                if(!$bindBankCard || $bindBankCard['status'] != 1){
                    $msg = $bindBankCard && $bindBankCard['msg']?$bindBankCard['msg']:'发送失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$bindBankCard['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }
                Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update(['cardNo'=>$cardNo,'cardPhone'=>$cardPhone]);
                return $this->json(['status'=>1,'msg'=>'绑定成功']);
            }
        }
    }

    public function yunstUser2()
    {
        if(getcustom('pay_allinpay')){
        	//直接创建通联会员
            $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
            $rdata = [];
            $rdata['status']     = 1;
            if($yunstuser){
            	return $this->json(['status'=>0,'msg'=>'已完成注册']);
            }else{
            	$yunstuser['phone']     = $this->member['tel']??'';
        		$yunstuser['name']      = $this->member['realname']??'';
                $yunstuser['identityNo']= $this->member['usercard']??'';
        		$yunstuser['cardNo']    = $this->member['bankcardnum']??'';
        		$yunstuser['cardPhone'] = $yunstuser['phone'];
            }
            $rdata['data']       = $yunstuser?$yunstuser:'';
            return $this->json($rdata);
        }
    }

    public function yunstUserSign()
    {
        if(getcustom('pay_allinpay')){
            $set = Db::name('allinpay_yunst_set')->where('aid',aid)->find();
            if(!$set || !$set['apiurl']){
                return $this->json(['status'=>0,'msg'=>'系统设置不存在']);
            }
            //直接创建通联会员
            $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
            if(!$yunstuser){
            	return $this->json(['status'=>0,'msg'=>'未创建通联会员，请先前去创建','url'=>'/pagesC/allinpay/yunstMember']);
            }
            if($yunstuser){
            	if($yunstuser['signstatus'] == 3){
	            	return $this->json(['status'=>0,'msg'=>'已签约成功']);
	            }
            	if(empty($yunstuser['phone']) || empty($yunstuser['identityNo']) || empty($yunstuser['cardNo'])){
            		return $this->json(['status'=>0,'msg'=>'通联会员信息未填写完整，请先完善信息','url'=>'/pagesC/allinpay/yunstMember']);
            	}
            }
            //通联会员
            if(request()->isPost()){
                if($yunstuser && $yunstuser['status'] == 2){
                    return $this->json(['status'=>0,'msg'=>'已创建成功','goback'=>true]);
                }
                $info = input('?param.info')?input('param.info/a'):[];
                if(!$info){
                    return $this->json(['status'=>0,'msg'=>'请填写会员信息']);
                }
                if(!$info['signAcctName']){
                    return $this->json(['status'=>0,'msg'=>'请输入签约户名']);
                }

                //记录
                $updata = [];
                $updata['signAcctName'] = $info['signAcctName'];
                $updata['jumpPageType'] = 1; //跳转页面类型  1-H5页面 2-小程序页面 兼容存量模式，不上送默认跳转H5页面
                if(platform == 'wx'){
                    $updata['jumpPageType'] = 2;
                }
                $updata['signstatus']   = 1;
                $updata['updatetime']   = time();
                $sql = Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update($updata);
                if(!$sql){
                    return $this->json(['status'=>0,'msg'=>'操作失败']);
                }

                $param = [];
                $param['bizUserId']    = $yunstuser['bizUserId'];
                $param['signAcctName'] = $info['signAcctName'];
                $param['jumpPageType'] = $updata['jumpPageType'];
                $signAcctProtocol = \app\custom\AllinpayYunst::signAcctProtocol(aid,$param,$set);
                if(!$signAcctProtocol || $signAcctProtocol['status'] != 1){
                    $msg = $signAcctProtocol && $signAcctProtocol['msg']?$signAcctProtocol['msg']:'操作失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$signAcctProtocol['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }
                Db::name('member_allinpay_yunst_user')->where('id',$yunstuser['id'])->update(['signstatus'=>2]);

                //跳转地址 10分钟内有效
                $gourl = $signAcctProtocol['data']['url']??'';
                return $this->json(['status'=>1,'msg'=>'创建成功','signstatus'=>2,'gourl'=>$gourl,'allipayappid'=>$set['allipayappid']]);
            }else{
                $rdata = [];
                $rdata['status']     = 1;

                $signstatus = 1;//提交签约
                // if($yunstuser && $yunstuser['signstatus'] == 2){
                //     $signstatus = 2;//跳转签约页面
                // }
                $rdata['info']       = $yunstuser?$yunstuser:'';
                $rdata['signstatus'] = $signstatus;
                $rdata['cardCheck']  = 8;//8 银行卡四要素验证 无需调用【确认绑定银行卡】;
                return $this->json($rdata);
            }
        }
    }

    public function dedamountlog(){
    	if(getcustom('member_dedamount')){
    		if(request()->isPost()){
    			$pid = input('?param.pid')?input('param.pid/d'):0;
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$pernum = 20;
				$where = [];
				$where[] = ['mid','=',mid];
				$where[] = ['pid','=',$pid];
				$where[] = ['aid','=',aid];
				$datalist = Db::name('member_dedamountlog')->field("id,bid,dedamount,dedamount2,after,remark,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
				if($datalist){
					foreach($datalist as &$dv){
						$dv['bname'] = '';
						if($dv['bid']){
							$business = Db::name('business')->where('id',$dv['bid'])->where('aid',aid)->field('name')->find();
							if($business){
								$dv['bname'] = '商家'.$business['name'];
							}else{
								$dv['bname'] = '已失效';
							}
						}else{
							$dv['bname'] = '平台';
						}
					}
				}else{
					$datalist = [];
				} 
				unset($dv);
		        return $this->json(['status'=>1,'data'=>$datalist,'mydedamount'=>$this->member['dedamount']]);
    		}
    	}
	}

	public function shopscorelog(){
		if(getcustom('member_shopscore')){
    		if(request()->isPost()){
    			$pid = input('?param.pid')?input('param.pid/d'):0;
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$pernum = 20;
				$where = [];
				$where[] = ['mid','=',mid];
				$where[] = ['aid','=',aid];
				$datalist = Db::name('member_shopscorelog')->field("id,shopscore,after,remark,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
				if(!$datalist) $datalist = [];
				unset($dv);
		        return $this->json(['status'=>1,'data'=>$datalist,'myshopscore'=>$this->member['shopscore']]);
    		}
    	}
	}

    /**
     * 我的押金
     * 功能1：https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0s91tNOVAeYAOQYKALU&tab=lom7cg
     * @author: liud
     * @time: 2025/1/4 下午3:55
     */
    public function productdeposit(){
        if(getcustom('product_deposit_mode')){
            $shopsyss = Db::name('shop_sysset')->where('aid',aid)->find();
            if($shopsyss['product_deposit_mode'] != 1){
                return $this->json(['status'=>0,'msg'=>'功能已关闭']);
            }
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            $ordernum = input('post.ordernum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['d.aid','=',aid];
            $where[] = ['d.mid','=',mid];

            if($st != 'all'){
                $where[] = ['d.status','=',$st];
            }
            if($ordernum){
                $where[] = ['o.ordernum','like','%'.$ordernum.'%'];
            }

            $field = 'd.*,o.ordernum';
            $datalist = Db::name('product_deposit_log')->alias('d')
                ->leftJoin('shop_order o','o.id = d.orderid')
                ->field($field)
                ->where($where)->page($pagenum,$pernum)->order('d.id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            $score_weishu = 2;

            if($datalist){
                foreach($datalist as $k=>$v){
                    $datalist[$k]['diff'] = getTimeDiff(time(),$v['withdrawaltime']);
                    if(time() > $v['withdrawaltime']){
                        $datalist[$k]['istx'] = true;
                    }
                    $datalist[$k]['createtime'] = date('Y-m-d H:i',$v['createtime']);
                    $datalist[$k]['withdrawaltime'] = date('Y-m-d H:i',$v['withdrawaltime']);
                    $datalist[$k]['money'] = round($v['money'],2);
                    if($v['status'] == 0){
                        $datalist[$k]['status_name'] = '未提现';
                    }else if($v['status'] == 1){
                        $datalist[$k]['status_name'] = '提现审核中';
                        $datalist[$k]['diff'] = '';
                    }else if($v['status'] == 2){
                        $datalist[$k]['status_name'] = '提现已驳回';
                    }else if($v['status'] == 3){
                        $datalist[$k]['status_name'] = '已提现';
                        $datalist[$k]['diff'] = '';
                    }else{
                        $datalist[$k]['status_name'] = '未知';
                    }
                }
            }
            if(request()->isPost()){
                $product_deposit = round($this->member['product_deposit'],$score_weishu);
                $rdata = ['status'=>1,'data'=>$datalist,'myproduct_deposit'=>$product_deposit];
                return $this->json($rdata);
            }

            $count = Db::name('product_deposit_log')->alias('d')
                ->leftJoin('shop_order o','o.id = d.orderid')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $product_deposit = round($this->member['product_deposit'],$score_weishu);
            $rdata['myproduct_deposit'] = $product_deposit;
            return $this->json($rdata);
        }
    }

    /**
    * 押金申请提现
    * 功能1：https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0s91tNOVAeYAOQYKALU&tab=lom7cg
    * @author: liud
    * @time: 2025/1/6 上午10:10
    */
    public function productdeposittixian(){
        if(getcustom('product_deposit_mode')){
            $shopsyss = Db::name('shop_sysset')->where('aid',aid)->find();
            if($shopsyss['product_deposit_mode'] != 1){
                return $this->json(['status'=>0,'msg'=>'功能已关闭']);
            }
            $id = input('post.id');
            if(!$po = Db::name('product_deposit_log')->where('aid',aid)->where('id',$id)->find()){
                return $this->json(['status'=>0,'msg'=>'押金记录不存在']);
            }
            if(!in_array( $po['status'],[0,2])){
                return $this->json(['status'=>0,'msg'=>'当前状态不可申请']);
            }
            if(time() < $po['withdrawaltime']){
                return $this->json(['status'=>0,'msg'=>'未到可提现时间']);
            }
            Db::name('product_deposit_log')->where('aid',aid)->where('id',$id)->update(['status' =>1,'applywithdrawaltime'=> time()]);
            return $this->json(['status'=>1,'msg'=>'操作成功']);
        }
    }

    /**
     * 我的滤芯
     * 功能2：https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0s91tNOVAeYAOQYKALU&tab=lom7cg
     * @author: liud
     * @time: 2025/1/4 下午3:55
     */
    public function productlvxinreplace(){
        if(getcustom('product_lvxin_replace_remind')){
            $shopsyss = Db::name('shop_sysset')->where('aid',aid)->find();
            if($shopsyss['product_lvxin_replace_remind'] != 1){
                return $this->json(['status'=>0,'msg'=>'功能未开启']);
            }
            $pagenum = input('post.pagenum');;
            $keyword = input('post.keyword');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['d.aid','=',aid];
            $where[] = ['d.mid','=',mid];

            if($keyword){
                $where[] = ['o.name','like','%'.$keyword.'%'];
            }

            $field = 'd.*,o.name,o.pic';
            $datalist = Db::name('product_lvxin_replace')->alias('d')
                ->join('shop_product o','o.id = d.proid')
                ->field($field)
                ->where($where)->page($pagenum,$pernum)->order('d.day asc,d.id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            if($datalist){
                foreach($datalist as $k=>$v){
                    //$dqtime = bcdiv(($v['expiretime'] - time()),86400,0);
                    if($v['day'] <= $shopsyss['product_lvxin_expireday_remind']){
                        $datalist[$k]['txing'] = true;
                    }
                    $datalist[$k]['createtime'] = date('Y-m-d H:i',$v['createtime']);
                    $datalist[$k]['expiretime'] = date('Y-m-d H:i',$v['expiretime']);
                    $datalist[$k]['tel'] = $this->sysset['tel'];
                }
            }
            if(request()->isPost()){
                $rdata = ['status'=>1,'datalist'=>$datalist];
                return $this->json($rdata);
            }

            $count = Db::name('product_lvxin_replace')->alias('d')
                ->join('shop_product o','o.id = d.proid')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            return $this->json($rdata);
        }
    }

    /**
     * 我的加权分红贡献值
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwvMK9JCBLTLy2fHPYm1?scode=AHMAHgcfAA0ooVbfpTAeYAOQYKALU
     * @author: liud
     * @time: 2025/1/18 下午3:55
     */
    public function jqfenhonggxz(){
        if(getcustom('fenhong_jiaquan_area') || getcustom('fenhong_jiaquan_gudong')){
            $type = input('post.type',1);
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=',$type];

            $datalist = Db::name('member_fenhong_jiaquan_gxz')
                ->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];

            if($datalist){
                foreach($datalist as $k=>$v){
                    $datalist[$k]['jd'] = $v['year'].'年'.$v['year_jd'].'季度';
                    $datalist[$k]['starttime'] = date('Y-m-d H:i',$v['starttime']);
                    $datalist[$k]['endtime'] = date('Y-m-d H:i',$v['endtime']);
                    $datalist[$k]['time'] = $datalist[$k]['starttime'] . ' 至 '.$datalist[$k]['endtime'];
                }
            }

            if($pagenum == 1){
                //当前季度开始时间和结束时间
                $season = ceil((date('n', time()))/3);
                $starttime = mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
                $endtime = mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));

                //区域贡献值
                if($type == 1){
                    //$areagxz = \app\common\Fenhong::getareafenhonggxz(aid,mid,$starttime,$endtime);
                    $gxz = $areagxz['gxz'] ?? 0;
                }else{
                    //$gdgxz = \app\common\Fenhong::getgdfenhonggxz(aid,mid,$starttime,$endtime,[]);
                    $gxz = $gdgxz['gxz'] ?? 0;
                }

                $toubu  = [
                    'gxz' => $gxz,
                    'jd' => date('Y').'年'.$season.'季度',
                    'time' => date('Y-m-d H:i',$starttime) . ' 至'. date('Y-m-d H:i',$endtime)
                ];

                // 在头部插入新元素
                //array_unshift($datalist, $toubu);
            }
            $title = $type == 1 ? '历史区域贡献值' : '历史股东贡献值';
            if(request()->isPost()){
                $rdata = ['status'=>1,'data'=>$datalist,'title' => $title];
                return $this->json($rdata);
            }

            $count = Db::name('member_fenhong_jiaquan_gxz')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['title'] = $title;
            return $this->json($rdata);
        }
    }

    public function forzengxcommissionlog(){
        if(getcustom('member_forzengxcommission')){
        	//读取冻结贡献值佣金
            if(request()->isPost()){
                $pagenum = input('post.pagenum');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $status = input('?param.st')?input('param.st'):'all';
                $where2 = '1=1';
                if($status != 'all'){
                	$where2 = 'status='.$status;
                }
                $datalist = Db::name('member_forzengxcommissionlog')->where($where)->where($where2)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];

                $commission_weishu = 2;
                $myforzengxcommission = 0;
                if($pagenum == 1){
                	$myforzengxcommission = Db::name('member_forzengxcommissionlog')->where($where)->where('status=0')->sum('commission2');
                	$myforzengxcommission = dd_money_format($myforzengxcommission,$commission_weishu);
                }
                if($datalist){
                    foreach($datalist as &$v){
                        $v['commission']  = dd_money_format($v['commission'],$commission_weishu);
                        $v['commission2'] = dd_money_format($v['commission2'],$commission_weishu);
                        $v['createtime']  = date("Y-m-d H:i:s");
                    }
                    unset($v);
                }
                return $this->json(['status'=>1,'data'=>$datalist,'myforzengxcommission'=>$myforzengxcommission] );
            }
        }
    }

    public function commissionTransfer()
    {
        if(getcustom('commission_transfer')){
        	$set =$this->sysset;
            if ($set['commission_transfer'] != 1) {
                return $this->json(['status'=>0,'msg'=>t('佣金').'转赠未开启']);
            }

            $setrs = [];
            $setrs['commission_transfer_sxf_ratio']= $set['commission_transfer_sxf_ratio'];
            $setrs['commission_transfer_sxf_type'] = $set['commission_transfer_sxf_type']??0;
            $commission_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $commission_weishu = $set['fenhong_money_weishu'];
            }

            $mid = input('param.mid/d',0);
            
            $gettj = explode(',',$set['commission_transfer_gettj']);
            if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
                return $this->json(['status'=>0,'msg'=>'您没有权限']);
            }
            $sxf_ratio = 0; //佣金手续费比例
            $commission_transfer_sxf = 0; //佣金手续费开关
            if(request()->isPost()){
                $mobile = input('post.mobile');
                $mid = input('post.mid/d');
                $integral = input('post.integral');
                $integral = dd_money_format($integral,$commission_weishu);
                $fee      = dd_money_format($integral*$set['commission_transfer_sxf_ratio']*0.01,$commission_weishu);
                $feetype  = 0;//费用类型0:转出方 1：接收方

                //判断扣除手续费方是转出方还是接收方 0:转出方 1：接收方
                if($setrs['commission_transfer_sxf_type'] && $setrs['commission_transfer_sxf_type'] == 1){
                	$feetype = 1;
                }else if($setrs['commission_transfer_sxf_type'] && $setrs['commission_transfer_sxf_type'] == 2){
                	$feetype = input('?param.feetype')?input('param.feetype/d'):0;
                }
                //如果是转出方出，则判断需要加上费用再判断
                if($feetype == 1){
                	$paycommission = dd_money_format($integral,$commission_weishu);
                }else{
                	$paycommission = dd_money_format($integral + $fee,$commission_weishu);
                }

                if ($integral <= 0){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的'.t('佣金').'数量']);
                }
                if (input('?post.mobile')) {
                    $info = Db::name('member')->where('aid', aid)->where('tel', $mobile)->find();
                }
                if (input('?post.mid')) {
                    $info = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
                }

                if(!$info) return $this->json(['status'=>0,'msg'=>'未找到该'.t('会员')]);
                $user_id = $info['id'];

                if ($info['id'] == mid) {
                    return $this->json(['status'=>0,'msg'=>'不能转赠给自己']);
                }
                if($set['commission_transfer_range'] == 1) {
                    //所有上下级
                    $isparent = false;
                    if(in_array($user_id,explode(',',$this->member['path']))){
                        $isparent = true;
                    }
                    if(!$isparent){
                        if(!in_array(mid,explode(',',$info['path']))){
                            return $this->json(['status'=>0,'msg'=>'仅限转赠给上下级'.t('会员')]);
                        }
                    }
                }
                if ($paycommission > $this->member['commission']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('佣金').'数量不足']);
                }
                //验证支付密码
                $pwd_check = $set['commission_transfer_pwd'];
                if($pwd_check){
                    if(!$this->member['paypwd']){
                        return $this->json(['status'=>0,'msg'=>'请先设置支付密码','set_paypwd'=>1]);
                    }
                    $pay_pwd = input('paypwd')?:'';
                    if(!\app\common\Member::checkPayPwd($this->member,$pay_pwd )){
                        return $this->json(['status'=>0,'msg'=>'支付密码输入错误']);
                    }
                }

                $where = [];
                $where['aid'] = aid;
                $where['id'] = $user_id;
                $rs = \app\common\Member::addcommission(aid,mid,0,$integral * -1, sprintf(t('佣金')."转赠给：%s",$info['nickname']));
                if ($rs['status'] == 1) {
                    \app\common\Member::addcommission(aid,$user_id,mid,$integral,sprintf("来自%s的".t('佣金')."转赠", $this->member["nickname"]), '', 0, $this->mid);
                    if($fee > 0){
                    	if($feetype == 1){
                        	\app\common\Member::addcommission(aid,$user_id,0,$fee * -1, sprintf(t('佣金')."转赠手续费"));
                        }else{
                        	\app\common\Member::addcommission(aid,mid,0,$fee * -1, sprintf(t('佣金')."转赠手续费"));
                        }
                    }
                }else{
                    return $this->json(['status'=>0, 'msg' => '转赠失败']);
                }
                return $this->json(['status'=>1, 'msg' => '转赠成功', 'url'=>'/pages/my/usercenter']);
            }
            $tomember = [];
            if($mid){
                $tomember = Db::name('member')->where('aid',aid)->where('id',$mid)->field('id,money,nickname,headimg')->find();
            }
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['paycheck'] = $set['commission_transfer_pwd'] ? true : false;
            $rdata['mycommission'] = dd_money_format($this->member['commission'],$commission_weishu);
            $rdata['set']          = $setrs;
            $rdata['sxf_ratio']    = $sxf_ratio;
            $rdata['transfer_sxf'] = $commission_transfer_sxf;
            $rdata['tomember']     = $tomember?$tomember:['nickname'=>''];//转给谁
            return $this->json($rdata);
        }
        
    }

    //积分转赠小程序码
    public function commissionTransferWxqrcode()
    {
        if(getcustom('commission_transfer_wxqrcode')) {
            $poster = \app\common\Wechat::getQRCode(aid,'wx','pagesC/my/commissionTransfer',['mid'=>mid,'pid'=>mid]);
            return $this->json(['status'=>1,'poster'=>$poster['url']]);
        }
    }

    //积分转赠二维码
    public function commissionTransferQrcode()
    {
        if(getcustom('commission_transfer_qrcode')) {
            $poster = createqrcode(m_url('pagesC/my/commissionTransfer?mid='.mid.'&pid='.mid,aid),'',aid);
            return $this->json(['status'=>1,'poster'=>$poster]);
        }
    }

    public function fundpoollog(){
         if (getcustom('commission_withdrawfee_fundpool')){
         	//基金池记录
            if(request()->isPost()){
                $pagenum = input('post.pagenum');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['status','=',1];
                $datalist = Db::name('admin_fundpool_log')->field('id,fundpool,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                return $this->json(['status'=>1,'data'=>$datalist,'fundpool'=>$this->admin['fundpool']] );
            }
        }
    }

    /**
     * 申请会员标签
     * 标签：member_tag_age
     * 开发文档，第7条：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwL9yuYq8HSTagBwsGZK?scode=AHMAHgcfAA0EtMV8gaAeYAOQYKALUhttps://doc.weixin.qq.com/doc/w3_AT4AYwbFACwL9yuYq8HSTagBwsGZK?scode=AHMAHgcfAA0EtMV8gaAeYAOQYKALU
     * @author: liud
     * @time: 2025/3/12 下午2:01
     */
    public function tagageapply(){
        if (getcustom('member_tag_age')){
            if(request()->isPost()){
                $post = input('post.');
                $formdata = $post;
                if(!$this->member){
                    return $this->json(['status'=>0,'msg'=>'参数错误,请重新操作']);
                }

                $tagdata = Db::name('member_tag')->where('aid',aid)->where('id',$formdata['tagid'])->find();

                if(!$tagdata){
                    return $this->json(['status'=>0,'msg'=>'参数错误,请重新操作!']);
                }

                if($tagdata['status'] != 1){
                    return $this->json(['status'=>0,'msg'=>'标签['.$tagdata['name'].']已关闭!']);
                }

                $data = [];
                $data['aid'] = aid;
                $data['mid'] = mid;
                $data['tag_id'] = $tagdata['id'];

                //是否有待审核的记录
                $hasds = Db::name('member_tag_age_apply_order')->where('aid',aid)->where('mid',mid)->where('tag_id',$tagdata['id'])->where('status',0)->find();
                if($hasds){
                    return $this->json(['status'=>0,'msg'=>'您已经提交过了,请等待审核']);
                }

                $tags_arr = explode(',',$this->member['tags']);
                if($tagdata['tag_age_type'] == 0 && in_array($tagdata['id'],$tags_arr)){
                    return $this->json(['status'=>0,'msg'=>'您已拥有['.$tagdata['name'].']标签，无需再次申请']);
                }

                $ordernum = date('ymdHis').aid.rand(1000,9999);
                $data['ordernum'] = $ordernum;
                $data['createtime'] = time();

                //获取出生日期
                $birthday = '';
                if($tagdata['apply_formdata']){
                    $apply_formdata = json_decode($tagdata['apply_formdata'],true);
                    foreach($apply_formdata as $k=>$v){
                        $value = $formdata['form'.$k];
                        if(is_array($value)){
                            $value = implode(',',$value);
                        }

                        $value = strval($value);
                        $csrq = '';
                        if($v['val4']==1){
                            $csrq = '^_^csrq';
                            $birthday = $value;
                        }
                        $data['form'.$k] = $v['val1'] . '^_^' .$value . '^_^' .$v['key']. $csrq;
                        if($v['val3']==1 && $value===''){
                            return $this->json(['status'=>0,'msg'=>$v['val1'].' 必填']);
                        }
                    }

                    if(!$birthday){
                        foreach($apply_formdata as $k=>$v){
                            $value = $formdata['form'.$k];
                            if(is_array($value)){
                                $value = implode(',',$value);
                            }
                            $value = strval($value);
                            if((strpos($v['val1'], '身份证') !== false)){
                                if (strlen($value) == 18) {
                                    $birthday = substr($value, 6, 8);
                                    break;
                                }
                            }
                        }
                    }

                    //判断此身份证号是否申请过
                    foreach($apply_formdata as $k=>$v){
                        $value = $formdata['form'.$k];
                        if(is_array($value)){
                            $value = implode(',',$value);
                        }
                        $value = strval($value);
                        if((strpos($v['val1'], '身份证') !== false)){
                            $sfz_where[] = ['form0|form1|form2|form3|form4|form5|form6|form7|form8|form9|form10','like','%'.$value.'%'];
                            $sfzwy = Db::name('member_tag_age_apply_order')->where('aid',aid)->where($sfz_where)->where('tag_id',$tagdata['id'])->where('status','in',[0,1])->find();
                            if($sfzwy){
                                return $this->json(['status'=>0,'msg'=>'当前身份证号已申请过此标签！']);
                            }
                        }
                    }
                }

                if(!$birthday && $tagdata['tag_age_type'] == 1){
                    return $this->json(['status'=>0,'msg'=>'未获取到出生日期！']);
                }

                $data['birthday'] = $birthday ?? '';

                $tag_age = $tagdata;
                $minfo = $this->member;
                //注册时间
                $regdatestatus=1;
                if($tag_age['regdatestatus']==1){
                    $starttime = time() - $tag_age['maxdays']*86400;
                    $endtime = time() - $tag_age['mindays']*86400;
                    $regdatestatus=0;
                    if($minfo['createtime'] >= $starttime && $minfo['createtime'] <= $endtime){
                        $regdatestatus = 1;
                    }
                }

                //会员等级
                $levelstatus=1;
                if($tag_age['levelstatus']==1){
                    $levelstatus=0;
                    if($minfo['levelid'] == $tag_age['levelid']){
                        $levelstatus = 1;
                    }
                }

                //消费次数
                $buystatus=1;
                if($tag_age['buystatus']==1){
                    $buystatus=0;
                    if($minfo['buynum'] >= $tag_age['buynum']){
                        $buystatus = 1;
                    }
                }

                //消费金额
                $buymoneystatus=1;
                if($tag_age['buymoneystatus']==1){
                    $buymoneystatus=0;
                    if($minfo['buymoney'] >= $tag_age['buymoney']){
                        $buymoneystatus = 1;
                    }
                }

                //指定商品
                $prostatus=1;
                if($tag_age['prostatus']==1){
                    $proids = explode(',',$tag_age['productids']);
                    $prostatus = Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where('proid','in',$proids)->where('status','in','1,2,3')->count();
                }

                //判断之前条件
                $zqtj = 0;
                if($tag_age['condition'] == 'or'){
                    if($regdatestatus>0 || $levelstatus>0 || $buystatus>0 || $buymoneystatus>0 || $prostatus>0){
                        $zqtj = 1;
                    }
                }else {
                    if($regdatestatus>0 && $levelstatus>0 && $buystatus>0 && $buymoneystatus>0 && $prostatus>0){
                        $zqtj = 1;
                    }
                }

                if($zqtj == 0){
                    return $this->json(['status'=>0,'msg'=>'不满足条件设置！']);
                }

                Db::startTrans();
                try{
                    if($tagdata['apply_check'] > 0){
                        //需要审核
                        $data['apply_check_type'] = 1;
                        $orderid = Db::name('member_tag_age_apply_order')->insertGetId($data);
                        Db::commit();
                        return $this->json(['status'=>1,'msg'=>'提交成功请等待审核','url'=>'/pages/my/usercenter']);
                    }else{
                        //无需审核
                        $data['apply_check_type'] = 0;

                        $orderid = Db::name('member_tag_age_apply_order')->insertGetId($data);

                        $res = \app\common\Member::checktagageapply(aid,mid,$orderid);

                        if($res['status'] == 1){
                            Db::commit();
                            return $this->json(['status'=>1,'msg'=>'申请成功','url'=>'/pages/my/usercenter']);
                        }else{
                            Db::rollback();
                            return $this->json(['status'=>0,'msg'=>$res['msg']]);
                        }
                    }
                }catch(Exception $e){
                    Db::rollback();
                    return $this->json(['status'=>0,'msg'=>$e->getMessage()]);
                }

            }

            $tagid = input('param.tagid','');
            //$member = Db::name('member')->where('aid',aid)->where('id',mid)->find();

            //等级列
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['pid','=',0];
            $where[] = ['type','=',3];
            if($tagid){
                $where[] = ['id','=',$tagid];
                $where[] = ['status','=',1];
            }else{
                $where[] = ['status','=',1];
            }

            $tags_arr = explode(',',$this->member['tags']);
            $tj = [];
            if($tags_arr){
                foreach ($tags_arr as $v){
                    $tj_tag = Db::name('member_tag')->where('aid',aid)->where('id',$v)->where('type',3)->find();
                    if($tj_tag){
                        if($tj_tag['pid'] == 0){
                            $tj[] = $tj_tag['id'];
                        }else{
                            $tj[] = $tj_tag['pid'];
                        }
                    }
                }
            }

            if($tj){
                $where[] = ['id','not in',$tj];
            }

            $aglevelList = Db::name('member_tag')->where($where)->order('sort,id asc')->select()->toArray();
            if(!$aglevelList) $aglevelList = [];

            $msg = '';
            $sqname = '会员标签申请';
            foreach($aglevelList as $k=>$lv){
                $aglevelList[$k]['apply_formdata'] = json_decode($lv['apply_formdata'],true);
                if($tagid && $lv['status'] != 1){
                    //$msg = $lv['name'].'已关闭！';
                }
                if($tagid){
                    $sqname = $lv['name'].'申请';
                }

                $member_tag_age_apply_order = Db::name('member_tag_age_apply_order')->where('aid',aid)->where('mid',mid)->where('tag_id',$lv['id'])->order('id desc')->find();
                $aglevelList[$k]['bh_info'] = '';
                if($member_tag_age_apply_order['status'] == 2){
                    if($member_tag_age_apply_order['reason']){
                        $aglevelList[$k]['bh_info'] = '标签申请已被驳回。驳回原因：'.$member_tag_age_apply_order['reason'];
                    }else{
                        $aglevelList[$k]['bh_info'] = '标签申请已被驳回。';
                    }
                }
            }

            $bh_info = '';
            $bhxx = Db::name('member_tag_age_apply_order')->where('aid',aid)->where('mid',mid)->where('tag_id',$tagid)->order('id desc')->find();
            if($bhxx && $bhxx['status'] == 2){
                if($bhxx['reason']){
                    $bh_info = '标签申请已被驳回。驳回原因：'.$bhxx['reason'];
                }else{
                    $bh_info = '标签申请已被驳回。';
                }
            }

            $rdata = [];
            $rdata['sysset'] = [];
            $rdata['userinfo'] = ['id'=>$this->member['id'],'nickname'=>$this->member['nickname'],'tags'=>$this->member['tags']];
            $rdata['aglevelList'] = $msg ? [] : $aglevelList;
            $rdata['msg'] = $msg;
            $rdata['sqname'] = $sqname;
            $rdata['bh_info'] = $bh_info;

            return $this->json($rdata);
        }
    }

    public function upgradescorelog(){
		if(getcustom('member_upgradescore')){
    		if(request()->isPost()){
    			$pid = input('?param.pid')?input('param.pid/d'):0;
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$pernum = 20;
				$where = [];
				$where[] = ['mid','=',mid];
				$where[] = ['aid','=',aid];
				$datalist = Db::name('member_upgradescorelog')->field("id,upgradescore,after,remark,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
				if(!$datalist) $datalist = [];
				unset($dv);
				$upgradescorename = '';
				if($pagenum == 1){
					$hoteltext = \app\model\Hotel::gettext(aid);
					$upgradescorename = $hoteltext['升级积分'];
				}
		        return $this->json(['status'=>1,'data'=>$datalist,'myupgradescore'=>$this->member['upgradescore'],'upgradescorename'=>$upgradescorename]);
    		}
    	}
	}
	//
    public function adsetSingle(){
        if(getcustom('yx_single_adset')){
            $set = Db::name('adset_sysset')->where('aid',aid)->find();
            if(request()->isPost()){
                //单页广告 0关闭，1开启
                 if($set['single_st'] ==1){
                     $totalnum = Db::name('adset_reward_log')->where('aid',aid)->where('mid',mid)->count();
                     if($totalnum >= $set['limit_num'] && $set['limit_num'] > 0)return $this->json(['status'=>0,'msg'=>'观看次数已完成']); //总数大于设置的总参与数返回
                     $start_time = strtotime(date('Y-m-d 00:00:01'));
                     $end_time = strtotime(date('Y-m-d 23:59:59'));
                     $today_num =   Db::name('adset_reward_log')->where('aid',aid)->where('mid',mid)->where('createtime','between',[$start_time,$end_time])->count();
                     //今日超过设置
                     if($today_num >= $set['everyday_limit'] && $set['limit_num'] > 0)return $this->json(['status'=>0,'msg'=>'今日观看次数已完成']);
                     $pid = $this->member['pid'];
                     if($set['reward_type'] ==0){
                         \app\common\Member::addmoney(aid,mid,$set['money'],'观看广告获得'.t('余额').'奖励');
                         if($pid > 0){
                             \app\common\Member::addmoney(aid,$pid,$set['money'],'下级观看广告获得'.t('余额').'奖励');
                         }
                     }elseif($set['reward_type'] ==1){
                         \app\common\Member::addscore(aid,mid,$set['money'],'观看广告获得'.t('积分').'奖励');
                         if($pid > 0){
                             \app\common\Member::addscore(aid,$pid,$set['money'],'下级观看广告获得'.t('积分').'奖励');
                         }
                     }elseif ($set['reward_type'] ==2){
                         \app\common\Member::addcommission(aid,mid,mid,$set['money'],'观看广告获得'.t('佣金').'奖励');
                         if($pid > 0){
                             \app\common\Member::addcommission(aid,$pid,mid,$set['money'],'下级观看广告获得'.t('佣金').'奖励');
                         }
                     }
                     $log = [
                         'aid' => aid,
                         'mid' => mid,
                         'money' => $set['money'],
                         'pid' => $pid,
                         'type' => $set['reward_type'],//类型  0余额，1积分 2佣金
                         'zt_money' => $set['zt_money'],
                         'createtime' =>time()
                     ];
                     Db::name('adset_reward_log')->insert($log);
                     return $this->json(['status'=>1,'msg' => '奖励发放成功']); 
                 }
            }
            return $this->json(['status' => 1,'sysset' => $set]);
        }
    }

    //新增团队页面的接口
    public function myteam(){
        if(getcustom('team_data')){
            $mid = input('mid')?input('mid/d'):0;
            if(!$mid){
                $mid = mid;
            }
            $date_start = 0;
            $date_end = 0;
            if(input('date_start') && input('date_end')){
                $date_start = strtotime(input('date_start'));
                $date_end = strtotime(input('date_end'));
            }
            $checkLevelid = input('checkLevelid')?input('checkLevelid/d'):0;

            $field = 'id,nickname,headimg,levelid,last_visittime,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong';
            $userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',$mid)->find();
            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$userinfo['levelid'])->find();
            $areafenhong_adr = '';
            $areafenhong = $userinfo['areafenhong']>0?$userinfo['areafenhong']:$userlevel['areafenhong'];
            if($areafenhong==1){
                $areafenhong_adr = $userinfo['areafenhong_province'];
            }
            if($areafenhong==2){
                $areafenhong_adr = $userinfo['areafenhong_province'].'-'.$userinfo['areafenhong_city'];
            }
            if($areafenhong==3){
                $areafenhong_adr = $userinfo['areafenhong_province'].'-'.$userinfo['areafenhong_city'].'-'.$userinfo['areafenhong_area'];
            }
            $userinfo['areafenhong_adr'] = $areafenhong_adr;

            $downdeep = input('param.st/d');
            $pernum = 20;
            $pagenum = input('post.pagenum');
            $keyword = input('post.keyword');
            $order = "id desc";

            $where2 = "1=1";
            $query_params = [];//query查询条件
            if($keyword){
                $where2 = "(nickname like :keyword or realname like :keyword2 or tel like :keyword3 or id like :keyword4 )";
                $query_params['keyword'] = $query_params['keyword2'] = $query_params['keyword3']= $query_params['keyword4'] = '%'.$keyword.'%';
            }
            if($date_start && $date_end){
                $where_date = "createtime>=:date_start and createtime<=:date_end";
                $query_params['date_start'] = $date_start;
                $query_params['date_end']   = $date_end;
                if($where2=='1=1'){
                    $where2 = $where_date;
                }else{
                    $where2 = $where2.' and '.$where_date;
                }
            }
            if($checkLevelid){
                $where_level = 'levelid='.$checkLevelid;
                if($where2=='1=1'){
                    $where2 = $where_level;
                }else{
                    $where2 = $where2.' and '.$where_level;
                }
            }
            if(!$pagenum) $pagenum = 1;
            if(!$downdeep) $downdeep = 1;
            if(!$mid){
                $datalist = [];
            }else{
                $field = 'id,nickname,headimg,tel,pid,score,totalcommission,from_unixtime(createtime)createtime,levelid,last_visittime';
                $datalist = Db::name('member')
                    ->field($field)
                    ->where("aid",aid)
                    ->where("find_in_set(".$mid.",path)")
                    ->whereRaw($where2,$query_params)
                    ->page($pagenum,$pernum)
                    ->order($order)->select()->toArray();
            }
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                $commission_where = [];
                $commission_where[] = ['aid','=',aid];
                $commission_where[] = ['mid','=',$mid];
                $commission_where[] = ['frommid','=',$v['id']];
                $commission_where[] = ['status','=',1];
                if($date_start && $date_end){
                    $commission_where[] = ['createtime','between',[$date_start,$date_end]];
                }
                $commission = Db::name('member_commission_record')
                    ->where($commission_where)
                    ->sum('commission');
                $datalist[$k]['commission'] = 0 + dd_money_format($commission,2);
                $downcount_where = [];
                $downcount_where[] = ['aid','=',aid];
                $downcount_where[] = ['pid','=',$v['id']];
                if($date_start && $date_end){
                    $downcount_where[] = ['createtime','between',[$date_start,$date_end]];
                }
                $datalist[$k]['downcount'] = 0 + Db::name('member')->where($downcount_where)->count();
                $level = Db::name('member_level')->where('aid',aid)->where('id',$v['levelid'])->find();
                $datalist[$k]['levelname'] = $level['name'];
                $datalist[$k]['levelsort'] = $level['sort'];
                $datalist[$k]['last_visittime'] = $v['last_visittime']?date('Y-m-d H:i:s',$v['last_visittime']):'';
                if($userlevel['team_showtel'] == 0){
                    $datalist[$k]['tel'] = '';
                }
                $datalist[$k]['can_change_pid'] = 0;
                $datalist[$k]['team_down_total'] = '计算中';
                $datalist[$k]['teamyeji'] = '计算中';
                $datalist[$k]['selfyeji'] = '计算中';
            }
            //小部门业绩[只统计确认收货且减掉退款的]
            if($pagenum==1){
                $downmids = \app\common\Member::getteammids(aid,$mid);
                //统计团队订单数量
                if($downmids){
                    $where = [];
                    $where[] = ['aid','=',aid];
                    $where[] = ['status','in',[1,2,3]];
                    $where[] = ['mid','in',$downmids];
                    $team_order_total = Db::name('shop_order')
                        ->field($field)
                        ->where($where)
                        ->count();
                }

                //统计区域订单数量
                $member = Db::name('member')->where('id',$mid)->find();
                $member_level = Db::name('member_level')->where('aid',aid)->where('id',$member['levelid'])->find();
                $province = $member['areafenhong_province']?:'';
                $city = $member['areafenhong_city']?:'';
                $area = $member['areafenhong_area']?:'';
                $areafenhong = $member['areafenhong']>$member['areafenhong']?$member['areafenhong']:$member_level['areafenhong'];
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['status','in',[1,2,3]];
                if($areafenhong>0 && ($province || $city || $area)){
                    if($areafenhong==1 && $province){
                        $where[] = ['area2','like','%'.$province.'%'];
                    }
                    if($areafenhong==2 && $province && $city){
                        $where[] = ['area2','like','%'.$province.','.$city.'%'];
                    }
                    if($areafenhong==3 && $province && $city && $area){
                        $where[] = ['area2','like','%'.$province.','.$city.','.$area.'%'];
                    }
                    $field = '*';
                    $area_order_total = Db::name('shop_order')
                        ->field($field)
                        ->where($where)
                        ->count();
                }
                if($downmids){
                    $where_date = '';
                    if($date_start && $date_end){
                        $where_date = "createtime>=".$date_start." and createtime<=".$date_end;
                    }
                    $team_total = Db::name('member')->where('aid','=',aid)->where('id','in',$downmids)->where($where_date)->count();
                }
                $userinfo['team_total'] = $team_total?:0;
                $userinfo['team_order_total'] = $team_order_total?:0;
                $userinfo['area_order_total'] = $area_order_total?:0;
            }
            $rdata = [];
            $rdata['datalist'] = $datalist;
            $rdata['userinfo'] = $userinfo;
            //显示直推、间推业绩
            $userlevel['team_yeji_zhitui'] = $userlevel['team_yeji_zhitui']??0;
            $userlevel['team_yeji_jiantui'] = $userlevel['team_yeji_jiantui']??0;

            $rdata['userlevel'] = $userlevel;

            $all_level = Db::name('member_level')->field('id,sort,name')->where('aid',aid)->select()->toArray();
            foreach($all_level as $k=>$v){
                //根据级别统计下级人数
                $count = Db::name('member')
                    ->field($field)
                    ->where("aid",aid)
                    ->where("find_in_set(".$mid.",path)")
                    ->where('levelid',$v['id'])
                    ->count();
                $v['name'] = $v['name'].'（'.$count.'人）';
                $all_level[$k] = $v;
            }
            $rdata['all_level'] = $all_level;
            $rdata['st'] = 1;
            $rdata['team_auth'] = getcustom('team_auth')||getcustom('member_levelup_givechild')?1:0;
            $rdata['showlevel'] = true;
            $rdata['level_tab'] = true;
            //是否是区域代理
            $is_area = 0;
            if($userlevel['areafenhong']>0 || $userlevel['areafenhong']>0){
                $is_area = 1;
            }
            $rdata['is_area'] = $is_area;

            return $this->json($rdata);
        }
    }
    //区域代理、股东分红订单数据
    public function fhorder(){
        if(getcustom('team_data')) {
            $mid = input('mid') ? input('mid/d') : 0;
            if (!$mid) {
                $mid = mid;

            }
            $st = input('st') ?: 2;

            $pernum = 20;
            $pagenum = input('post.pagenum');
            $keyword = input('post.keyword');

            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['status', 'in', [1, 2, 3]];
            if ($keyword) {
                $where[] = ['ordernum', 'like', '%' . $keyword . '%'];
            }
            $sdate = input('sdate');
            $edate = input('edate');
            if ($sdate && $edate) {
                $sdate = strtotime($sdate);
                $edate = strtotime($edate);
                $where[] = ['createtime', 'between', [$sdate, $edate]];
            }
            $field = '*';
            $datalist = [];
            if ($st == 2) {
                //获取团队订单数据
                $downmids = \app\common\Member::getteammids(aid, $mid);
                if ($downmids) {
                    $where[] = ['mid', 'in', $downmids];
                    $datalist = Db::name('shop_order')
                        ->field($field)
                        ->where($where)
                        ->page($pagenum, $pernum)
                        ->order('id desc')->select()->toArray();
                }
            } elseif ($st == 3) {
                //获取区域订单数据
                $member = Db::name('member')->where('id', $mid)->find();
                $member_level = Db::name('member_level')->where('aid', aid)->where('id', $member['levelid'])->find();
                $province = $member['areafenhong_province'] ?: '';
                $city = $member['areafenhong_city'] ?: '';
                $area = $member['areafenhong_area'] ?: '';
                $areafenhong = $member['areafenhong'] > $member['areafenhong'] ? $member['areafenhong'] : $member_level['areafenhong'];
                if ($areafenhong > 0 && ($province || $city || $area)) {
                    if ($areafenhong == 1 && $province) {
                        $where[] = ['area2', 'like', '%' . $province . '%'];
                    }
                    if ($areafenhong == 2 && $province && $city) {
                        $where[] = ['area2', 'like', '%' . $province . ',' . $city . '%'];
                    }
                    if ($areafenhong == 3 && $province && $city && $area) {
                        $where[] = ['area2', 'like', '%' . $province . ',' . $city . ',' . $area . '%'];
                    }

                    $datalist = Db::name('shop_order')
                        ->field($field)
                        ->where($where)
                        ->page($pagenum, $pernum)
                        ->order('id desc')->select()->toArray();
                }
            }
            if ($datalist) {
                foreach ($datalist as $k => $v) {
                    $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
                    //统计获得分红
                    $ogids = Db::name('shop_order_goods')->where('orderid', $v['id'])->column('id');
                    $commission = Db::name('member_fenhonglog')->where('ogids', 'in', $ogids)->where('mid', $mid)->where('status', 'in', [0, 1])->sum('commission');
                    $v['commission'] = $commission ?: 0;
                    $datalist[$k] = $v;
                }
            }
            $rdata = [
                'status' => 1,
                'datalist' => $datalist,
            ];
            return $this->json($rdata);
        }
    }

    //冻结资金明细
    public function freezemoneylog(){
        if(getcustom('freeze_money')){
            if(request()->isPost()){
                $pagenum = input('post.pagenum');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['mid','=',mid];
                $where[] = ['aid','=',aid];
                $datalist = Db::name('member_freezemoneylog')->field("id,freezemoney,after,remark,from_unixtime(createtime) createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if($datalist){
                    foreach($datalist as &$dv){

                    }
                }else{
                    $datalist = [];
                }
                unset($dv);
                return $this->json(['status'=>1,'data'=>$datalist,'myfreezemoney'=>$this->member['freezemoney']]);
            }
        }
    }

    public function jichamoneylog(){
        if(getcustom('teamfenhong_jichamoney')){
            $st = input('param.st');
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $moeny_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if($st ==1){//提现记录
                $datalist = Db::name('member_jichawithdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime) createtime,reason,wx_state")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
            }else{ 
                //明细
                $datalist = Db::name('member_jichamoneylog')->field("id,money,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){
                    $datalist[$k]['money'] = dd_money_format($v['money'],$moeny_weishu);
                    $datalist[$k]['after'] = dd_money_format($v['after'],$moeny_weishu);
                }
            }

            $admin_set = Db::name('admin_set')->field('moneypay,recharge,withdraw')->where('aid',aid)->find();

            $showstatus = [];
            $showstatus[] = $admin_set['moneypay'] ;
            $showstatus[] = $admin_set['withdraw'];
            $canwithdraw  = $this->sysset['teamfenhong_jichamoney_withdraw'];

            $myjichamoney = dd_money_format($this->member['jichamoney'],$moeny_weishu);
            return $this->json(['status'=>1,'data'=>$datalist,'canwithdraw'=>$canwithdraw,'showstatus' => $showstatus,'myjichamoney'=>$myjichamoney]);
        }
    }

    public function jichawithdraw(){
        if(getcustom('teamfenhong_jichamoney')){
            $field = 'teamfenhong_jichamoney_withdraw,teamfenhong_jichamoney_withdraw_daylimit,teamfenhong_jichamoney_withdraw_type,teamfenhong_jichamoney_withdraw_day,teamfenhong_jichamoney_withdraw_day2,teamfenhong_jichamoney_withdraw_fee';
            $set = Db::name('admin_set')->where('aid',aid)->field($field)->find();
            if($set['teamfenhong_jichamoney_withdraw'] == 0){
                return $this->json(['status'=>0,'msg'=>'提现功能未开启']);
            }
            $set['jichawithdrawfee'] = $set['teamfenhong_jichamoney_withdraw_fee'];
            $set['jichawithdraw_bankcard'] = 1;//默认银行卡提现

            $set['jichawithdraw_desc'] = '';
            if($set['teamfenhong_jichamoney_withdraw_type']){
                //固定时间提现
                $day = intval(date('d'));
                $withdraw_day  = intval($set['teamfenhong_jichamoney_withdraw_day']);
                $withdraw_day2 = intval($set['teamfenhong_jichamoney_withdraw_day2']);
            	if($withdraw_day == $withdraw_day2){
                 	$set['jichawithdraw_desc'] = '请在每个月的'.$withdraw_day.'号申请提现一次';
            	}else{
            		$set['jichawithdraw_desc'] = '请在每个月的'.$withdraw_day.'号到'.$withdraw_day2.'号申请提现一次';
            	}
            }else{
                //提现天数限制
                if($set['teamfenhong_jichamoney_withdraw_daylimit']>0){
                    $set['jichawithdraw_desc'] = '每次提现需间隔'.$set['teamfenhong_jichamoney_withdraw_daylimit'].'天可提现一次';
                }
            }
            if(request()->isPost()){
                $post = input('post.');

                if($set['teamfenhong_jichamoney_withdraw_type']){
                    //固定时间提现
                    if($day<$withdraw_day || $day>$withdraw_day2){
                    	if($withdraw_day == $withdraw_day2){
                         	return $this->json(['status'=>0,'msg'=>'不在可提现日期内，请在每个月的'.$withdraw_day.'号提现']);
                    	}else{
                    		return $this->json(['status'=>0,'msg'=>'不在可提现日期内，请在每个月的'.$withdraw_day.'号到'.$withdraw_day2.'号提现']);
                    	}
                    }
                    //查询本月是否提现
                    $nowdaytime = strtotime(date('Y-m'));
                    $log = Db::name('member_jichawithdrawlog')->where('mid',mid)->where('createtime','>=',$nowdaytime)->where('status','<>',2)->where('aid',aid)->order('id desc')->field('id,createtime')->find();
                    if($log){
                        //计算提现的日期
                        $day2 = intval(date('d',$log['createtime']));
                        if($day2>=$withdraw_day && $day2<=$withdraw_day2){
                             return $this->json(['status'=>0,'msg'=>'已提交过提现过，固定日期内只能提现一次']);
                        }
                    }
                }else{
                    //提现天数限制
                    if($set['teamfenhong_jichamoney_withdraw_daylimit']>0){
                        //查询上次提现记录
                        $log = Db::name('member_jichawithdrawlog')->where('mid',mid)->where('status','<>',2)->where('aid',aid)->order('id desc')->field('id,createtime')->find();
                        if($log){
                            $cha = $log['createtime'] - time();
                            $daylimit = $set['teamfenhong_jichamoney_withdraw_daylimit'] * 84600;

                            if($cha<$daylimit) return $this->json(['status'=>0,'msg'=>'每次提现需间隔'.$set['teamfenhong_jichamoney_withdraw_daylimit'].'天可提现一次']);
                        }
                    }
                }
                
                if($post['paytype']=='支付宝'){
                    if($set['withdraw_aliaccount'] == 0){
                        return $this->json(['status'=>0,'msg'=>'支付宝提现功能未开启']);
                    }

                    if(!$this->member['aliaccount'] || !$this->member['aliaccountname']){
                        return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                    }
                }
                if(getcustom('withdraw_paycode')){
                    if($post['paytype']=='收款码'){
                        if(!$this->member['wxpaycode'] && !$this->member['alipaycode']){
                            return $this->json(['status'=>0,'msg'=>'请先设置一个收款码']);
                        }
                    }
                }

                if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
                    if($set['withdraw_bankcard'] == 0)
                        return $this->json(['status'=>0,'msg'=>'银行卡提现功能未开启']);
                    return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                }

                $money = $post['money'];
                //每次重新查，避免出现连续重复提交后余额没更新的情况
                $member_money = Db::name('member')->where('aid',aid)->where('id',mid)->value('jichamoney');
                if($money > $member_money){
                    return $this->json(['status'=>0,'msg'=>'可提现余额不足']);
                }

                //验证小数点后两位
                $money_arr = explode('.',$money);
                if($money_arr && $money_arr[1]){
                    $dot_len = strlen($money_arr[1]);
                    if($dot_len>2){
                        return $this->json(['status'=>0,'msg'=>'提现金额最小位数为小数点后两位']);
                    }
                }

                $ordernum = date('ymdHis').aid.rand(1000,9999);
                $record['aid'] = aid;
                $record['mid'] = mid;
                $record['createtime']= time();

                $real_money = $money*(1-$set['teamfenhong_jichamoney_withdraw_fee']*0.01);
				if($real_money <= 0) {
	                return $this->json(['status'=>0,'msg'=>'提现金额有误']);
	            }
	            $record['money'] = round($real_money,2);

                $record['txmoney'] = $money;
                if($post['paytype']=='支付宝'){
                    $record['aliaccountname'] = $this->member['aliaccountname'];
                    $record['aliaccount'] = $this->member['aliaccount'];
                }
                if($post['paytype']=='银行卡'){
                    $record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
                    $record['bankcarduser'] = $this->member['bankcarduser'];
                    $record['bankcardnum'] = $this->member['bankcardnum'];
                }
                if(getcustom('withdraw_paycode')){
                    if($post['paytype']=='收款码'){
                        if($this->member['wxpaycode']){
                            $record['wxpaycode'] = $this->member['wxpaycode'];
                        }
                        if($this->member['alipaycode']){
                            $record['alipaycode'] = $this->member['alipaycode'];
                        }
                    }
                }
                $record['ordernum'] = $ordernum;
                $record['paytype'] = $post['paytype'];
                $record['platform'] = platform;
                $recordid = Db::name('member_jichawithdrawlog')->insertGetId($record);
                if(!$recordid) $this->json(['status'=>0,'msg'=>'提交失败']);

                $res = \app\common\Member::addjichamoney(aid,mid,-$money,'提现');
                if(!$res || $res['status'] != 1){
                    $msg = $res && $res['msg']?$res['msg']:'减少账号余额失败';
                    $this->json(['status'=>0,'msg'=>$msg]);
                }
                return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
            }
            $member_field = 'id,money,aliaccount,bankname,bankcarduser,bankcardnum,realname';
            $userinfo = Db::name('member')->where('id',mid)->field($member_field)->find();
            //订阅消息
            $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
            $tmplids = [];
            // if($wx_tmplset['tmpl_tixiansuccess_new']){
            //     $tmplids[] = $wx_tmplset['tmpl_tixiansuccess_new'];
            // }elseif($wx_tmplset['tmpl_tixiansuccess']){
            //     $tmplids[] = $wx_tmplset['tmpl_tixiansuccess'];
            // }
            // if($wx_tmplset['tmpl_tixianerror_new']){
            //     $tmplids[] = $wx_tmplset['tmpl_tixianerror_new'];
            // }elseif($wx_tmplset['tmpl_tixianerror']){
            //     $tmplids[] = $wx_tmplset['tmpl_tixianerror'];
            // }
            $rdata = [];
            $rdata['canwithdraw']= $this->sysset['teamfenhong_jichamoney_withdraw'];
            $rdata['selectbank'] =  false;
            $moeny_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            $userinfo['jichamoney']  = dd_money_format($this->member['jichamoney'],$moeny_weishu);
            $rdata['userinfo']  = $userinfo;
            $rdata['sysset']    = $set;
            $rdata['tmplids']   = $tmplids;
            return $this->json($rdata);
        }
    }

    //链动数据统计
    public function userdata(){
        if(getcustom('up_giveparent_userdata')){
            $pernum = 10;
            $pagenum = input('post.pagenum')?:1;
            $commission_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $commission_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            if(getcustom('reg_invite_code')){
                $reg_invite_code_type = Db::name('admin_set')->where('aid',aid)->value('reg_invite_code_type');
            }
            //会员等级
            $level_list = Db::name('member_level')->where('aid',aid)->column('id,name,icon,can_agent','id');
            $default_level = Db::name('member_level')->where('aid',aid)->where('isdefault',1)->find();
            if($pagenum==1){
                //会员信息
                $field = 'id,tel,headimg,nickname,levelid,pid,totalcommission,commission';
                if(getcustom('reg_invite_code')){
                    $field .= ',yqcode';
                }
                $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
                $userinfo['commission'] = dd_money_format($userinfo['commission'],$commission_weishu);
                $userinfo['totalcommission'] = dd_money_format($userinfo['totalcommission'],$commission_weishu);
                if(getcustom('reg_invite_code') && $reg_invite_code_type==0){
                    $userinfo['yqcode'] = $userinfo['tel'];
                }
                $userlevel = $level_list[$userinfo['levelid']];
                if(!$userlevel) $userlevel = $default_level;
                $userinfo['levelname'] = $userlevel['name'];
                $userinfo['levelicon'] = $userlevel['icon'];
                $userinfo['can_agent'] = $userlevel['can_agent'];
                //推荐人昵称
                if($userinfo['pid']){
                    $pid_info = Db::name('member')->where('id',$userinfo['pid'])->field('id,nickname')->find();
                    $userinfo['pid_nickname'] = $pid_info['nickname'];
                }else{
                    $userinfo['pid_nickname'] = '无';
                }
                //累计佣金
                $total_commission = $userinfo['commission'];
                //直推佣金(一级分销)
                $comission_type = \app\model\CommissionType::commission_type['parent1'];
                $map = [];
                $map[] = ['aid','=',aid];
                $map[] = ['mid','=',mid];
                $map[] = ['status','=',1];
                $map[] = ['commission_type','=',$comission_type];
                $parent1 = Db::name('member_commission_record')->where($map)->sum('commission');
                $userinfo['parent1'] = $parent1?:0;
                $userinfo['parent1'] = dd_money_format($userinfo['parent1'],$commission_weishu);
                //见点佣金(等级价格级差分销)
                $comission_type = \app\model\CommissionType::commission_type['parent_jicha'];
                $map = [];
                $map[] = ['aid','=',aid];
                $map[] = ['mid','=',mid];
                $map[] = ['status','=',1];
                $map[] = ['commission_type','=',$comission_type];
                $parent_jicha = Db::name('member_commission_record')->where($map)->sum('commission');
                $userinfo['parent_jicha'] = $parent_jicha?:0;
                $userinfo['parent_jicha'] = dd_money_format($userinfo['parent_jicha'],$commission_weishu);
                //剩余佣金

                //直推粉丝数量(一级会员)
                //原上级是我+无原上级，现上级是我
                $where_str = "pid_origin=".mid.' or (pid='.mid.' and (pid_origin=0 or pid_origin is null))';
                $tj_num = Db::name('member')
                    ->field($field)
                    ->where("aid",aid)
                    ->where($where_str)->count();
                $userinfo['tj_num'] = $tj_num?:0;
                //裂变粉丝(团队会员个数)
                //有原上级，现上级是我
                $where_str = "pid_origin>0 and pid=".mid;
                $team_num = Db::name('member')
                    ->field($field)
                    ->where("aid",aid)
                    ->where($where_str)->count();
                $userinfo['team_num'] = $team_num?:0;
            }
            //粉丝列表
            $field = 'id,tel,nickname,headimg,tel,pid,pid_origin,score,totalcommission,from_unixtime(createtime)createtime,levelid,last_visittime,totalcommission';
            if(getcustom('reg_invite_code')){
                $field .= ',yqcode';
            }
            $st = input('st')?:0;
            $where_str = '';
            if($st==0){
                //原上级是我+无原上级，现上级是我
                $where_str = "pid_origin=".mid.' or (pid='.mid.' and (pid_origin=0 or pid_origin is null))';
            }else{
                //有原上级，现上级是我
                $where_str = "pid_origin>0 and pid=".mid;
            }
            $datalist = Db::name('member')
                ->field($field)
                ->where("aid",aid)
                ->where($where_str)
                ->page($pagenum,$pernum)
                ->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                $downcount_where = [];
                $downcount_where[] = ['aid','=',aid];
                $downcount_where[] = ['pid','=',$v['id']];
                $datalist[$k]['downcount'] = 0 + Db::name('member')->where($downcount_where)->count();
                $level = $level_list[$v['levelid']]??$default_level;
                $datalist[$k]['levelname'] = $level['name'];
                $datalist[$k]['levelicon'] = $level['icon'];
                $path_desc = '';
                if($v['pid_origin']==mid){
                    //脱离点位 原上级是我
                    $path_desc = '脱离点位';
                }elseif(empty($v['pid_origin']) && $v['pid']==mid){
                    //裂变点位=无原上级，现上级是我
                    $path_desc = '裂变点位';
                }elseif(!empty($v['pid_origin']) && $v['pid']==mid){
                    //财富点位=有原上级，现上级是我
                    $path_desc = '财富点位';
                }
                $datalist[$k]['path_desc'] = $path_desc;
                
                $datalist[$k]['totalcommission'] = dd_money_format($v['totalcommission'],$commission_weishu);

                if(getcustom('reg_invite_code') && $reg_invite_code_type==0){
                    $datalist[$k]['yqcode'] = $v['tel'];
                }
            }
            $rdata = [];
            $rdata['datalist'] = $datalist;
            $rdata['userinfo'] = $userinfo;
            return $this->json($rdata);
        }
    }

    public function xianjinlog(){
        if(getcustom('commission_xianjin_percent')){
        	//现金明细
            $st = input('param.st');
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if($st == 1){//充值记录
                $recharge_order_where_status = [];
                $recharge_order_where_status[] = ['status','=',1];
                $field = 'id,money,`status`,from_unixtime(createtime) createtime';

                //转账汇款
                $recharge_order_where_status = [];
                $where[] = ['paytype','<>','null'];
                $field .= ',paytypeid,transfer_check,payorderid';

                $datalist = Db::name('xianjin_recharge_order')->field($field)->where($where)->where($recharge_order_where_status)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){
                    $datalist[$k]['money_recharge_transfer'] = true;
                    $datalist[$k]['payorder'] = Db::name('payorder')->where('aid',aid)->where('type','xianjin_recharge')->where('orderid',$v['id'])->find();
                }
            }elseif($st ==2){//提现记录
                $datalist = Db::name('member_xianjin_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime) createtime,reason,wx_state")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
            }else{ //余额明细
                $datalist = Db::name('member_xianjinlog')->field("id,money,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){
                    if(strpos($v['remark'],'商家充值，') === 0){
                        $datalist[$k]['remark'] = '商家充值';
                    }
                    $moeny_weishu = 2;
                    $datalist[$k]['money'] = dd_money_format($v['money'],$moeny_weishu);
                    $datalist[$k]['after'] = dd_money_format($v['after'],$moeny_weishu);
                }
            }

            $setxianjin = Db::name('admin_set_xianjin')->field('xianjinrecharge,xianjinwithdraw')->where('aid',aid)->find();
            $showstatus = [];
            $showstatus[] = true;
            $showstatus[] = $setxianjin['xianjinrecharge'];
            $showstatus[] = $setxianjin['xianjinwithdraw'];
            return $this->json(['status'=>1,'data'=>$datalist,'showstatus' => $showstatus,'showmyxianjin'=>true,'myxianjin'=>$this->member['xianjin']]);
         }
    }

    public function xianjinWithdraw(){
        if(getcustom('commission_xianjin_percent')){
            //现金提现
            $field = 'withdraw_autotransfer,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,withdraw_desc,day_withdraw_num,wx_transfer_type';
            $set = Db::name('admin_set')->where('aid',aid)->field($field)->find();

            $setxianjin = Db::name('admin_set_xianjin')->where('aid',aid)->find();
            if(!$setxianjin || $setxianjin['xianjinwithdraw'] == 0){
                return $this->json(['status'=>-4,'msg'=>t('现金').'提现功能未开启']);
            }
            $set['xianjinwithdraw']    = $setxianjin['xianjinwithdraw'];
            $set['xianjinwithdrawmin'] = $setxianjin['xianjinwithdrawmin'];
            $set['xianjinwithdrawmax'] = $setxianjin['xianjinwithdrawmax'];
            $set['xianjinwithdrawfee'] = $setxianjin['xianjinwithdrawfee'];
            $set['xianjinwithdrawdate']= $setxianjin['xianjinwithdrawdate'];
            $set['xianjinwithdrawbl']  = $setxianjin['xianjinwithdrawbl'];
            $set['xianjinrecord_withdrawlog_show']  = $setxianjin['xianjinrecord_withdrawlog_show'];

            $memberlevel = Db::name('member_level')->where('id',$this->member['levelid'])->find();
            // if(getcustom('member_realname_verify')) {
            //     $realname_set = Db::name('member_realname_set')->where('aid', aid)->find();
            //     if ($realname_set['status'] == 1 && $realname_set['withdraw_status'] == 0 && $this->member['realname_status'] != 1){
            //         return $this->json(['status'=>-4,'msg'=>'未实名认证不可提现','url'=>'/pagesExt/my/setrealname']);
            //     }
            // }

            $field = 'id,headimg,nickname,xianjin,aliaccount,bankname,bankcarduser,bankcardnum,realname';
            $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
            if(request()->isPost()){
                $post = input('post.');

                Db::startTrans();
                $userinfo = Db::name('member')->where('id',mid)->field($field)->lock(true)->find();

                //验证今天提现了几次
                $nowtime = strtotime(date("Y-m-d",time()));//今日时间戳
                $daywithdrawnum   = 'daywithdrawnum'.mid.$nowtime;//会员今日时间参数
                $day_withdraw_num = cache($daywithdrawnum);//获取会员提现次数
                if($set['day_withdraw_num']<0){
                    return $this->json(['status'=>0,'msg'=>'暂时不可提现']);
                }else if($set['day_withdraw_num']>0){
                    if($day_withdraw_num && !empty($day_withdraw_num)){
                        $daynum = $day_withdraw_num+1;
                        if($daynum>$set['day_withdraw_num']){
                            return $this->json(['status'=>0,'msg'=>'今日申请提现次数已满，请明天继续申请提现']);
                        }
                    }
                }

                if(empty($post['paytype'])){
                    return $this->json(['status'=>0,'msg'=>'请选择提现方式']);
                }

                if($post['paytype']=='支付宝' && $this->member['aliaccount']==''){
                    return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                }

                if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
                    return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                }

                if($post['paytype']=='银行卡' && $set['withdraw_huifu'] == 1 && ($this->member['realname']==''||$this->member['tel']==''||$this->member['usercard']==''||$this->member['huifu_id']==''||$this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
                    return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                }

                $money = $post['money'];
                if($money<=0 || $money < $set['xianjinwithdrawmin']){
                    return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['xianjinwithdrawmin']?$set['xianjinwithdrawmin']:0)]);
                }
                if($money > $userinfo['xianjin']){
                    return $this->json(['status'=>0,'msg'=>'可提现'.t('现金').'不足']);
                }

                if($set['xianjinwithdrawmax']>0 && $money > $set['xianjinwithdrawmax']){
                    return $this->json(['status'=>0,'msg'=>'提现金额过大，单笔'.t('现金').'提现最高金额为'.$set['xianjinwithdrawmax'].'元']);
                }

                //验证小数点后两位
                $money_arr = explode('.',$money);
                if($money_arr && $money_arr[1]){
                    $dot_len = strlen($money_arr[1]);
                    if($dot_len>2){
                        return $this->json(['status'=>0,'msg'=>'提现金额最小位数为小数点后两位']);
                    }
                }

                $ordernum = date('ymdHis').aid.rand(1000,9999);
                $record['aid'] = aid;
                $record['mid'] = mid;
                $record['createtime']= time();

                $real_money = dd_money_format($money*(1-$set['xianjinwithdrawfee']*0.01)) ;
                if($real_money <= 0) {
                    return $this->json(['status'=>0,'msg'=>'提现金额有误']);
                }

                $record['money'] = $real_money;
                $record['txmoney'] = $money;
                if($post['paytype']=='支付宝'){
                    $record['aliaccountname'] = $this->member['aliaccountname'];
                    $record['aliaccount'] = $this->member['aliaccount'];
                }
                if($post['paytype']=='银行卡'){
                    $record['bankname'] = $this->member['bankname'].($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');;
                    $record['bankcarduser'] = $this->member['bankcarduser'];
                    $record['bankcardnum'] = $this->member['bankcardnum'];
                }
                if($post['paytype']=='银行卡' && $set['withdraw_huifu'] == 1){
                    $record['huifu_id'] = $this->member['huifu_id'];
                }
                
                $record['ordernum'] = $ordernum;
                $record['paytype']  = $post['paytype'];
                $record['platform'] = platform;

                $res = \app\custom\MemberCustom::addXianjin(aid,mid,-$money,t('现金').'提现');
                if($res && $res['status'] == 0) return $this->json(['status'=>0,'msg'=>$res['msg']]);

                $recordid = Db::name('member_xianjin_withdrawlog')->insertGetId($record);
                if(!$recordid) return $this->json(['status'=>0,'msg'=>'提现失败']);
                Db::commit();

                //记录今天提现了几次
                if(!$day_withdraw_num || empty($day_withdraw_num)){
                    cache($daywithdrawnum,1,86400);
                }else{
                    $daynum = $day_withdraw_num+1;
                    cache($daywithdrawnum,$daynum,86400);
                }

                $tmplcontent = array();
                $tmplcontent['first'] = '有客户申请'.t('现金').'提现';
                $tmplcontent['remark'] = '点击进入查看~';
                $tmplcontent['keyword1'] = $this->member['nickname'];
                $tmplcontent['keyword2'] = date('Y-m-d H:i');
                $tmplcontent['keyword3'] = $money.'元';
                $tmplcontent['keyword4'] = $post['paytype'];
                \app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('adminExt/finance/xianjinwithdrawlog'));

                $tmplcontent = [];
                $tmplcontent['name3'] = $this->member['nickname'];
                $tmplcontent['amount1'] = $money.'元';
                $tmplcontent['date2'] = date('Y-m-d H:i');
                $tmplcontent['thing4'] = '提现到'.$post['paytype'];
                \app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'adminExt/finance/xianjinwithdrawlog');

                $need_confirm = 0;//是否需要用户主动确认
                if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
                    $paymoney = $record['money'];
                    $paymoney = dd_money_format($paymoney,2);

                    if($set['wx_transfer_type']==1 && $post['paytype'] == '微信钱包'){
                        //使用了新版的商家转账功能
                        $paysdk = new WxPayV3(aid,mid,platform);
                        $rs = $paysdk->transfer($record['ordernum'],$paymoney,'',t('现金').'提现','member_xianjin_withdrawlog',$recordid);
                        if($rs['status']==1){
                            $data = [
                                'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                                'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                                'wx_state' => $rs['data']['state'],//转账状态
                                'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                            ];
                            Db::name('member_xianjin_withdrawlog')->where('id',$recordid)->update($data);
                            $need_confirm = 1;
                        }else{
                            $data = [
                                'wx_transfer_msg' => $rs['msg'],
                            ];
                            Db::name('member_xianjin_withdrawlog')->where('id',$recordid)->update($data);
                            if(strpos($rs['msg'],'资金不足')!==false){
                                $rs['msg'] = '商户资金不足，请联系管理员';
                            }
                            return $this->json(['status'=>0,'msg'=>'提现失败【'.$rs['msg'].'】']);
                        }
                    }else{
                        $rs = \app\common\Wxpay::transfers(aid,mid,$paymoney,$record['ordernum'],platform,t('现金').'提现');
                        if($rs['status']==1){
                            Db::name('member_xianjin_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                        }
                    }
                    if($rs['status']==0){
                        return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }else{
                        //提现成功通知
                        $tmplcontent = [];
                        $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                        $tmplcontent['remark'] = '请点击查看详情~';
                        $tmplcontent['money'] = (string) round($record['money'],2);
                        $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) $record['money'];//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
                        \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
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
                        return json(['status'=>1,'msg'=>$rs['msg'],'need_confirm'=>$need_confirm,'id'=>$recordid]);
                    }
                }
                return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
            }

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
            $rdata = [];

            $userinfo['show_cash_count'] = 0;
            $moeny_weishu = 2;
            $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
            $userinfo['xianjin'] = dd_money_format($userinfo['xianjin'],$moeny_weishu);

            $selectbank = false;
            $set['wx_max_money'] = 2000;//微信暂时定义的提现金额大于2000需要填写姓名
            $rdata['selectbank'] = $selectbank;
            $rdata['userinfo']   = $userinfo ;
            $rdata['sysset']     = $set;
            $rdata['tmplids']    = $tmplids;
            return $this->json($rdata);
        }
    }

    /**
     * 我的爱心基金/升级奖励
     * https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwCNiyaR5sdcSSS4fsRZ?scode=AHMAHgcfAA0vxoyhtgAeYAOQYKALU
     * @author: liud
     * @time: 2025/8/11 17:14
     */
    public function myOfflineSubsidies()
    {
        if(getcustom('yx_offline_subsidies')){
            $mid = input('param.mid/d',0);
            $ly_type = input('param.ly_type/d',0); //0爱心基金  1升级奖励
            $set = Db::name('offline_subsidies_set')->where('aid', aid)->find();

            $ly_text = '';
            $mymoney_field = '';
            if($ly_type == 1){
                $ly_text = $set['upgrade_text'] ?? '升级奖励';
                $mymoney_field = 'upgrade_rewards';
                $data_url='/pagesD/my/offlineSubsidiesLog?ly_type=1';
            }else{
                $ly_text = $set['lovefund_text'] ?? '爱心基金';
                $mymoney_field = 'love_fund';
                $data_url='/pagesD/my/offlineSubsidiesLog?ly_type=0';
            }

            if(request()->isPost()){
                $mobile = input('post.mobile');
                $mid = input('post.mid/d');
                $money = input('post.money/f');
                if ($money < 0.01){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的金额，最小金额为：0.01']);
                }
                if (input('?post.mobile') && !empty($mobile)) {
                    $member = Db::name('member')->where('aid', aid)->where('tel', $mobile)->find();
                }
                if (input('?post.mid') && $mid > 0) {
                    $member = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
                }
                if(!$member) return $this->json(['status'=>0,'msg'=>'未找到该'.t('会员')]);
                $user_id = $member['id'];

                if ($user_id == mid) {
                    return $this->json(['status'=>0,'msg'=>'不能转账给自己']);
                }
//                if($set['money_transfer_range'] == 1) {
//                    //所有上下级
//                    $isparent = false;
//                    if(in_array($user_id,explode(',',$this->member['path']))){
//                        $isparent = true;
//                    }
//                    if(!$isparent){
//                        if(!in_array(mid,explode(',',$member['path']))){
//                            return $this->json(['status'=>0,'msg'=>'仅限转账给上下级'.t('会员')]);
//                        }
//                    }
//                }

                //扣除会员自身的金额
                $dec_money = $money;
                $to_money = $money;
                if ($money > $this->member['love_fund']){
                    return $this->json(['status'=>0,'msg'=>'您的账户余额不足']);
                }
                //验证支付密码
                $pwd_check = false;
                if($pwd_check){
                    if(!$this->member['paypwd']){
                        return $this->json(['status'=>0,'msg'=>'请先设置支付密码','set_paypwd'=>1]);
                    }
                    $pay_pwd = input('paypwd')?:'';
                    if(!\app\common\Member::checkPayPwd($this->member,$pay_pwd )){
                        return $this->json(['status'=>0,'msg'=>'支付密码输入错误']);
                    }
                }
                $midMsg = sprintf("转给：%s",$member['nickname']);
                $toMidMsg = sprintf("来自%s的%s转账", $this->member["nickname"],$ly_text);

                //扣除
                if($ly_type == 1){
                    $rs =  \app\common\Member::addUpgradeRewards(aid,$this->mid,$dec_money * -1,$midMsg,'zhuanzhang',$user_id,0,'');
                }else{
                    $rs =  \app\common\Member::addLoveFund(aid,$this->mid,$dec_money * -1,$midMsg,'zhuanzhang',$user_id,0,'');
                }

                if ($rs['status'] == 1) {
                    //增加
                    \app\common\Member::addmoney(aid,$user_id,$to_money,$toMidMsg,$this->mid);
                }else{
                    return $this->json(['status'=>0, 'msg' => '转账失败']);
                }
                return $this->json(['status'=>1, 'msg' => '转账成功','tourl'=>$data_url]);
            }
            $tomember = [];
            if($mid){
                $tomember = Db::name('member')->where('aid',aid)->where('id',$mid)->field('id,nickname,headimg')->find();
            }
            $rdata['status'] = 1;
            $rdata['mymoney'] = $this->member[$mymoney_field];
            $rdata['moneyList'] = [];//可选金额列表
            $rdata['tomember'] = $tomember?$tomember:['nickname'=>''];//转给谁
            $rdata['title_name'] = $ly_text;

            return $this->json($rdata);
        }
    }

    /**
     * 线下补助明细
     * https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwCNiyaR5sdcSSS4fsRZ?scode=AHMAHgcfAA0vxoyhtgAeYAOQYKALU
     * @author: liud
     * @time: 2025/8/14 10:00
     */
    public function myOfflineSubsidiesLog(){
        if(getcustom('yx_offline_subsidies')) {
            $pagenum = input('post.pagenum');
            $set = Db::name('offline_subsidies_set')->where('aid', aid)->find();
            $ly_type = input('param.ly_type/d',0); //0爱心基金  1升级奖励..
            if (!$pagenum) $pagenum = 1;
            $pernum =10;
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['mid', '=', mid];

            $weisj = $yisj = $zhitui_num = '-1';

            if($ly_type == 1){
                $ly_text = $set['upgrade_text'] ?? '升级奖励';
                $dbname = 'member_upgrade_rewards_log';

                //判断获得过多少个升级奖励
                $upgrad_count = Db::name('member_offline_subsidies_log')->where('aid',aid)->where('mid',mid)->where('type',5)->where('status',1)->sum('commission');

                $level_name = Db::name('member_level')->where('aid',aid)->where('id',$set['upgrade_auto_uplevelid'])->value('name');
                if($upgrad_count >= $set['upgrade_num']){
                    $yisj = $level_name;
                }else{
                    $weisj = bcsub( $set['upgrade_num'],$upgrad_count,0);
                }

            }elseif($ly_type == 0){
                $ly_text = $set['lovefund_text'] ?? '爱心基金';
                $dbname = 'member_love_fund_log';
            }
            elseif($ly_type == 2){
                $ly_text = $set['pass_text'] ?? '通证分红';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 1];
            }
            elseif($ly_type == 3){
                $ly_text = $set['recommend_text'] ?? '推荐奖励';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 2];
            }
            elseif($ly_type == 4){
                $ly_text = $set['lecturer_text'] ?? '讲师奖励';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 3];
            }
            elseif($ly_type == 5){
                $ly_text = $set['yejireward_text'] ?? '业绩奖励';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 6];
            }elseif($ly_type == 6){
                $ly_text = $set['renshureward_text'] ?? '人数奖励';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 7];

                //当月直推人数
                $month_start = date('Y-m-01 00:00:00');
                $month_end  = date('Y-m-t 23:59:59');
                $zhitui_num = Db::name('member')->where('aid',aid)->where('pid',mid)->where('createtime','between',[$month_start,$month_end])->count('id');
            }
            elseif($ly_type == 7){
                $ly_text = $set['offline_text'] ? $set['offline_text'].'直推奖' : '线下补助';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 8];
                $where[] = ['scene', '<>', 'area'];
            }
            elseif($ly_type == 8){
                $ly_text = $set['offline_text'] ? $set['offline_text'].'区域代理奖' : '线下补助';
                $dbname = 'member_offline_subsidies_log';
                $where[] = ['type', '=', 8];
                $where[] = ['scene', '=', 'area'];
            }


            if(in_array($ly_type,[0,1])){
                $datalist = Db::name($dbname)->field("id,money,`after`,from_unixtime(createtime) createtime,remark,ordernum,type")->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            }else{
                $datalist = Db::name($dbname)->field("id,money,score,commission,from_unixtime(createtime) createtime,remark,ordernum,type,scene")->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            }

            if (!$datalist) $datalist = [];
            foreach ($datalist as $k => $v) {
                if($v['type'] == 1){
                    $datalist[$k]['money'] = $v['score'];
                }elseif (in_array($v['type'],[2,3,4,5,6,7,8])){
                    if($v['scene'] == 'area'){
                        $datalist[$k]['money'] = $v['money'];
                    }else{
                        $datalist[$k]['money'] = $v['commission'];
                    }
                }
            }

            return $this->json(['status' => 1, 'data' => $datalist,'title_name' => $ly_text,'zhitui_num' => $zhitui_num,'weisj' => $weisj,'yisj' => $yisj,'level_name' => $level_name??'']);
        }
    }

    /**
     * 我的线下补助总览
     * https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwCNiyaR5sdcSSS4fsRZ?scode=AHMAHgcfAA0vxoyhtgAeYAOQYKALU
     * @author: liud
     * @time: 2025/8/14 09:02
     */
    public function myOfflineSubsidiesOverview(){
        if(getcustom('yx_offline_subsidies')) {
            $set = Db::name('offline_subsidies_set')->where('aid', aid)->find();

            $datalist['pass'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 1)->sum('score') ?? 0;
            $datalist['recommend'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 2)->sum('commission') ?? 0;
            $datalist['lecturer'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 3)->sum('commission') ?? 0;
            $datalist['lovefund'] = $this->member['love_fund'] ?? 0;
            $datalist['upgrade'] = $this->member['upgrade_rewards'] ?? 0;
            $datalist['yejireward'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 6)->sum('commission') ?? 0;
            $datalist['renshureward'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 7)->sum('commission') ?? 0;
            $datalist['offline_zhitui'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 8)->where('scene','<>','area')->sum('commission') ?? 0;
            $datalist['offline_area'] = Db::name('member_offline_subsidies_log')->where('aid', aid)->where('mid', mid)->where('type', 8)->where('scene', 'area')->sum('money') ?? 0;

            return $this->json(['status' => 1, 'data' => $datalist,'set' => $set]);
        }
    }
    
    public function goldbeanLog(){
        if(getcustom('gold_bean')) {
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['mid', '=', mid];
            $datalist = Db::name('member_gold_bean_log')->field('id,gold_bean,after,remark,from_unixtime(createtime,\' %Y-%m-%d %H:%i:%s\') createtime')->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            if (!$datalist) $datalist = [];
            $score_weishu = 0;
            if ($datalist) {
                foreach ($datalist as $k => $v) {
                    $datalist[$k]['score'] = dd_money_format($v['score'], $score_weishu);
                }
            }
            $count = Db::name('member_gold_bean_log')->where($where)->count();
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['datalist'] = $datalist;
            $rdata['pernum'] = $pernum;
            $rdata['st'] = $st;
            $member_goldbean = dd_money_format($this->member['gold_bean'], $score_weishu);
            $rdata['mygoldbean'] = $member_goldbean;
            return $this->json($rdata);
        }
    }

    public function tencentqianUplevel(){
        if(getcustom('extend_tencent_qian')){
            //腾讯电子签
            $agree_id = input('?param.agree_id')?input('param.agree_id/d'):0;
            $where = [];
            if(request()->isPost()){
                if(!$agree_id) return $this->json(['status'=>0,'msg'=>'参数错误']);
                $where[] = ['id','=',$agree_id];
            }
            $where[] = ['mid','=',mid];
            $where[] = ['status','=',0];
            $exit = Db::name('member_level_agree')->where($where)->order('sort desc')->find();
            if(!$exit || !$exit) return $this->json(['status'=>0,'msg'=>'不存在可升级的等级记录']);

            //查询是否存在待签署
            $qian = Db::name('member_tencent_qianlog')->where('mid',mid)->where('agreeid',$exit['id'])->where('type','member_uplevel')->order('id desc')->find();
            if($qian){
                if($qian['status'] == 0 || $qian['status'] == 1){
                    return $this->json(['status'=>0,'msg'=>'有相同签署在进行，不能发起签署']);
                }else if($qian['status'] == 2){
                    return $this->json(['status'=>1,'msg'=>'已签署完成']);
                }
            } 

            $newlevel = Db::name('member_level')->where('id',$exit['newlv_id'])->find();
            if(!$newlevel || $newlevel['tencent_qian_createflowid']<=0) return $this->json(['status'=>0,'msg'=>'不存在签署升级的等级']);
            $level  = Db::name('member_level')->where('id',$this->member['levelid'])->find();
            if($level && $level['sort']>$newlevel['sort']) return $this->json(['status'=>1,'msg'=>'该签署等级低当前会员等级，不能签署']);

            //查询签署版信息
            $createflow = Db::name('tencent_qian_template_createflow')->where('id',$newlevel['tencent_qian_createflowid'])->where('aid',aid)->find();
            if(!$createflow) return $this->json(['status'=>0,'msg'=>'系统签署信息不存在']);
            if(empty($createflow['FlowName']) || empty($createflow['TemplateId']))  return $this->json(['status'=>0,'msg'=>'系统签署设置不完善，暂不能签署']);

            //查询模板
        	$template = Db::name('tencent_qian_template')->where('TemplateId',$createflow['TemplateId'])->where('aid',aid)->find();
        	if(!$template) return $this->json(['status'=>1,'msg'=>'模板数据不存在']);

            //查询合同签署人
            $approvers = Db::name('tencent_qian_template_createflow_approvers')->where('createflowid',$createflow['id'])->where('type',1)->where('aid',aid)->order('sort asc,id asc')->select()->toArray();
            if(!$approvers) return $this->json(['status'=>0,'msg'=>'系统签署合同不完善']);

            if(request()->isPost()){
                //控件自定义信息 checkbox 和 upload_pics是数组形式
                $fieldFormdata = input('?param.fieldFormdata')?input('param.fieldFormdata/a'):[];
                $formFields = !empty($createflow['FormFields'])?json_decode($createflow['FormFields'],true):[];
                $newFormFields = [];
                if($formFields){
                    foreach($formFields as $i=>$formField){
                        $newFormField = [];
                        $newFormField['ComponentId'] = $formField['val0'];
                        //需要用户填写
                        if($formField['val12']){
                            if($fieldFormdata){
                                $newFormField['ComponentValue'] = $fieldFormdata[$i]['ComponentValue']??'';
                                if(!$newFormField['ComponentValue'] || empty($newFormField['ComponentValue'])){
                                    return $this->json(['status'=>0,'msg'=>$formField['val1'].'内容不能为空']);
                                    break;
                                }
                                //checkbox 和 upload_pics是数组形式
                                if($formField['key'] == 'checkbox' || $formField['key'] == 'upload_pics'){
                                    $newFormField['ComponentValue'] = implode(',',$newFormField['ComponentValue']);
                                }
                            }else{
                                $newFormField['ComponentValue'] =$formField['val21'];
                            }
                        }else{
                            $newFormField['ComponentValue'] =$formField['val21'];
                        }
                        $newFormFields[] = $newFormField;
                    }
                    unset($i);unset($formField);
                }

                $allapprovers = Db::name('tencent_qian_template_createflow_approvers')->where('createflowid',$createflow['id'])->where('aid',aid)->order('sort asc,id asc')->select()->toArray();
                $i=0;
                //签署人自定义信息
                $approverFormdata = input('?param.approverFormdata')?input('param.approverFormdata/a'):[];
                $approverdatas = [];
                $params = [];
                foreach($allapprovers as $approver){
                    $approverdata = [];
                    //合同填写类型 0：后端填写 1：用户端填写
                    if($approver['type'] == 1){
                        $approverdata['ApproverType'] = $approver['ApproverType']??0;
                        $approverdata['RecipientId']  = $approver['RecipientId']??'';

                        if($approverdata['ApproverType'] != 1){
                        	//组织名称
	                        if($approver['OrganizationNameShow']){
	                        	$approverdata['OrganizationName'] = $approverFormdata[$i] && $approverFormdata[$i]['OrganizationName']?$approverFormdata[$i]['OrganizationName']:'';
	                        }else{
	                        	$approverdata['OrganizationName'] = $approver['OrganizationName']?:'';
	                        }

	                        //UserID
	                        if($approverdata['ApproverType'] != 1 && $approver['UserIdShow']){
	                        	$approverdata['UserId'] = $approverFormdata[$i] && $approverFormdata[$i]['UserId']?$approverFormdata[$i]['UserId']:'';
	                        }else{
	                        	$approverdata['UserId'] = $approver['UserId']?:'';
	                        }
                        }else{
                        	$approverdata['UserId'] = '';
                        }

                        //姓名
                        if($approver['ApproverNameShow']){
                        	$approverdata['ApproverName'] = $approverFormdata[$i] && $approverFormdata[$i]['ApproverName']?$approverFormdata[$i]['ApproverName']:'';
                        }else{
                        	$approverdata['ApproverName'] = $approver['ApproverName']?:'';
                        }

                        //手机号
                        if($approver['ApproverMobileShow']){
                        	$approverdata['ApproverMobile'] = $approverFormdata[$i] && $approverFormdata[$i]['ApproverMobile']?$approverFormdata[$i]['ApproverMobile']:'';
                        }else{
                        	$approverdata['ApproverMobile'] = $approver['ApproverMobile']?:'';
                        }

                        //身份证信息
                        if($approver['ApproverIdCardNumberShow']){
                        	$ApproverIdCardTypeIndex = $approverFormdata[$i] && $approverFormdata[$i]['ApproverIdCardTypeIndex']?$approverFormdata[$i]['ApproverIdCardTypeIndex']:'';
	                        if($ApproverIdCardTypeIndex == 1){
	                        	$approverdata['ApproverIdCardType'] = 'HONGKONG_AND_MACAO';
	                        }else if($ApproverIdCardTypeIndex == 2){
	                        	$approverdata['ApproverIdCardType'] = 'HONGKONG_MACAO_AND_TAIWAN';
	                        }else{
	                        	$approverdata['ApproverIdCardType'] = 'ID_CARD';
	                        }

	                        $approverdata['ApproverIdCardNumber'] = $approverFormdata[$i] && $approverFormdata[$i]['ApproverIdCardNumber']?$approverFormdata[$i]['ApproverIdCardNumber']:'';
                        }else{
                        	$approverdata['ApproverIdCardType']   = $approver['ApproverIdCardType']?:0;
                        	$approverdata['ApproverIdCardNumber'] = $approver['ApproverIdCardNumber']?:'';
                        }

                        $approverdata['ApproverOption']['FillType'] = $approver['FillType'];

                        if($approverdata['ApproverType'] != 1){
                        	if($approver['OrganizationNameShow'] && !$approverdata['OrganizationName']) return $this->json(['status'=>0,'msg'=>'签署人组织机构名称不能为空']);
                        	if($approver['UserIdShow'] && !$approverdata['UserId']) return $this->json(['status'=>0,'msg'=>'签署人UserId不能为空']);
                        }

                        if(!$approverdata['UserId']){
                        	if($approver['ApproverNameShow'] && !$approverdata['ApproverName']) return $this->json(['status'=>0,'msg'=>'签署人姓名不能为空']);
	                        if($approver['ApproverMobileShow']){
	                        	if(!$approverdata['ApproverMobile']) return $this->json(['status'=>0,'msg'=>'签署人手机号不能为空']);
	                        	if(!checkTel(aid,$approverdata['ApproverMobile'])){
						            return $this->json(['status'=>0, 'msg'=>'请填写正确的签署人手机号']);
						        }
	                        }
	                        if($approver['ApproverIdCardNumberShow']){
	                        	if(!$approverdata['ApproverIdCardNumber']) return $this->json(['status'=>0,'msg'=>'签署人证件不能为空']);
	                        	if($approverdata['ApproverIdCardType'] == 'ID_CARD' && !checkIdCard($approverdata['ApproverIdCardNumber'])){
					                return $this->json(['status'=>0,'msg'=>'请输入正确的签署人身份证号']);
					            }
	                        }
                        }
                        $i++;
                    }else{
                        $approverdata['ApproverType'] = $approver['ApproverType'];
                        $approverdata['RecipientId'] = $approver['RecipientId'];
                        $approverdata['OrganizationName'] = $approver['OrganizationName'];
                        $approverdata['UserId'] = $approver['UserId'];
                        $approverdata['ApproverName'] = $approver['ApproverName'];
                        $approverdata['ApproverMobile'] = $approver['ApproverMobile'];
                        $approverdata['ApproverIdCardType'] = $approver['ApproverIdCardType'];
                        $approverdata['ApproverIdCardNumber'] = $approver['ApproverIdCardNumber'];
                        $approverdata['ApproverOption']['FillType'] = $approver['FillType'];
                    }
                    $approverdatas[] = $approverdata;
                }
                $params['Approvers'] = $approverdatas;
                $params['createflow'] = $createflow;

                //创建签署
                $rescreateFlow = \app\custom\TencentQian::createFlow(aid,0,$params);
                if(!$rescreateFlow || $rescreateFlow['status'] != 1){
                    $msg = $rescreateFlow && $rescreateFlow['msg']?$rescreateFlow['msg']:'发起签署错误';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                $FlowId = $rescreateFlow['data']['FlowId'];

                //增加签署记录
                $data = [];
                $data['aid'] = aid;
                $data['mid'] = mid;
                $data['type']= 'member_uplevel';
                $data['levelid'] = $exit['newlv_id'];
                $data['agreeid'] = $exit['id'];
                $data['FlowId']  = $FlowId;

                $data['createflowid']= $createflow['id'];
                $data['FlowName']    = $createflow['FlowName'];
                $data['TemplateId']  = $createflow['TemplateId'];
                //签署人自定义信息
                $data['approverFormdata'] = $approverFormdata?json_encode($approverFormdata,JSON_UNESCAPED_UNICODE):'';
                $data['createtime']  = time();
                $mtqid = Db::name('member_tencent_qianlog')->insertGetId($data);
                if(!$mtqid) return $this->json(['status'=>0,'msg'=>'发起签署失败']);

                $params = [];
                $params['TemplateId'] = $createflow['TemplateId'];
                $params['FlowId']     = $FlowId;
                $params['FormFields'] = $newFormFields;
                //创建电子签
                $rescreateDocument = \app\custom\TencentQian::createDocument(aid,0,$params);
                if(!$rescreateDocument || $rescreateDocument['status'] != 1){
                    $msg = $rescreateDocument && $rescreateDocument['msg']?$rescreateDocument['msg']:'发起签署错误';
                    Db::name('member_tencent_qianlog')->where('id',$mtqid)->update(['status'=>-1,'reason'=>$msg]);
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                $DocumentId    = $rescreateDocument['data']['DocumentId']??'';
                $PreviewFileUrl= $rescreateDocument['data']['PreviewFileUrl']??'';

                $updata = [];
                $updata['fieldFormdata'] = $fieldFormdata?json_encode($fieldFormdata,JSON_UNESCAPED_UNICODE):'';
                $updata['DocumentId']    = $DocumentId;
                $updata['PreviewFileUrl']= $PreviewFileUrl;
                $up = Db::name('member_tencent_qianlog')->where('id',$mtqid)->update($updata);
                if(!$up){
                    Db::name('member_tencent_qianlog')->where('id',$mtqid)->update(['status'=>-1,'reason'=>'发起签署失败']);
                    return $this->json(['status'=>0,'msg'=>'发起签署失败']);
                } 
                if($rescreateDocument['data']['Approvers'] && !empty($rescreateDocument['data']['Approvers'])){
                    foreach($rescreateDocument['data']['Approvers'] as $dv){
                        $data = [];
                        $data['aid'] = aid;
                        $data['mid'] = mid;
                        $data['mtqid'] = $mtqid;
                        $data['createflowid']= $createflow['id'];
                        $data['TemplateId']  = $createflow['TemplateId'];

                        $data['ApproverRoleName']= $dv['ApproverRoleName'];
                        $data['RecipientId'] = $dv['RecipientId'];
                        $data['SignId'] = $dv['SignId'];
                        $data['createtime'] = time();
                        $mtqaid = Db::name('member_tencent_qianlog_approvers')->insertGetId($data);
                        if(!$mtqaid){
                            Db::name('member_tencent_qianlog')->where('id',$mtqid)->update(['status'=>-1,'reason'=>'发起签署失败']);
                            return $this->json(['status'=>0,'msg'=>'发起签署失败']);
                        }
                    }
                }

                $params = [];
                $params['FlowId'] = $FlowId;
                $resstartFlow = \app\custom\TencentQian::startFlow(aid,0,$params);
                if(!$resstartFlow || $resstartFlow['status'] != 1){
                    $msg = $resstartFlow && $resstartFlow['msg']?$resstartFlow['msg']:'发起签署错误';
                    Db::name('member_tencent_qianlog')->where('id',$mtqid)->update(['status'=>-1,'reason'=>$msg]);
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }

                $updata = [];
                $updata['status'] = 1;
                //获取跳转至腾讯电子签小程序的签署链接
                $createSchemeUrl = \app\custom\TencentQian::createSchemeUrl(aid,0,$params);
                if($createSchemeUrl &&  $createSchemeUrl['status'] == 1){
                    $wxurl = $createSchemeUrl['data']['SchemeUrl']??'';
                    $updata['wxurl'] = $wxurl;
                }
                Db::name('member_tencent_qianlog')->where('id',$mtqid)->update($updata);
                return $this->json(['status'=>1,'msg'=>'发起成功，请注意查看短信发送']);
            }else{
                //获取控件参数
                $fieldDatas = $fieldFormdatas = [];//控件所有数据、控件要提交数据
                $formFields = !empty($createflow['FormFields'])?json_decode($createflow['FormFields'],true):[];
                if($formFields){
                    foreach($formFields as $i=>$formField){
                        if(!$formField['val12']) continue;
                        $fieldDatas[] = $formField;
                        if($formField['key'] == 'checkbox' || $formField['key'] == 'upload_pics' ){
                            $ComponentValue = [];
                        }else{
                            $ComponentValue = '';
                        }
                        $fieldFormdatas[] = [
                            'i'=>$i,
                            'key'=>$formField['key'],
                            'val0'=>$formField['val0'],
                            'val1'=>$formField['val1'],
                            'val2'=>$formField['val2'],
                            'val4'=>$formField['val4'],
                            'val22'=>$formField['val22'],
                            'pickindex'=>0,
                            'ComponentId'=>$formField['val0'],
                            'ComponentName'=>$formField['val1'],
                            'ComponentValue'=>$ComponentValue
                        ];
                    }
                    unset($i);unset($formField);
                }

                //获得合同参与方
                $approverDatas = $approverFormdatas = [];
                foreach($approvers as $approver){
                    $approverData = $approverFormdata=  [];

                    $approverData['OrganizationNameShow'] = $approver['OrganizationNameShow']??0;
                    $approverFormdata['OrganizationName'] = '';

                    $approverData['UserIdShow'] = $approver['UserIdShow']??0;
                    $approverFormdata['UserId'] = '';

                    if($approver['ApproverType'] == 1){
                    	$approverData['OrganizationNameShow'] = 0;
                    	$approverData['UserIdShow'] = 0;
                    }

                    $approverData['ApproverNameShow'] = $approver['ApproverNameShow']??0;
                    $approverFormdata['ApproverName'] = '';

                    $approverData['ApproverMobileShow'] = $approver['ApproverMobileShow']??0;
                    $approverFormdata['ApproverMobile'] = '';

                    $approverData['ApproverIdCardNumberShow'] = $approver['ApproverIdCardNumberShow']??0;
                    $approverFormdata['ApproverIdCardNumber'] = '';

                    $approverFormdata['ApproverIdCardType'] = $approver['ApproverIdCardType']??'';
                    $approverFormdata['ApproverIdCardTypeIndex']= 0;
                    if($approver['ApproverIdCardType'] == 'ID_CARD'){
                        $approverData['ApproverIdCardTypeName'] = '居民身份证';
                    }else if($approver['ApproverIdCardType'] == 'HONGKONG_AND_MACAO'){
                        $approverData['ApproverIdCardTypeName'] = '港澳居民来往内地通行证';
                        $approverFormdata['ApproverIdCardTypeIndex']= 1;
                    }else if($approver['ApproverIdCardType'] == 'HONGKONG_MACAO_AND_TAIWAN'){
                        $approverData['ApproverIdCardTypeName'] = '港澳台居民居住证';
                        $approverFormdata['ApproverIdCardTypeIndex']= 2;
                    }

                    $approverFormdata['ApproverIdCardNumber'] = '';
                    if($approverData['ApproverNameShow'] || $approverData['ApproverMobileShow'] || $approverData['ApproverIdCardNumberShow']){
                        $approverDatas[] = $approverData;
                        $approverFormdatas[] = $approverFormdata;
                    }
                }
                unset($approver);

                $rdata = [];
                $rdata['status'] = 1;
                $rdata['templatepics'] = !empty($template['templatepics'])?explode(',',$template['templatepics']):[];
                $rdata['FlowName']  = $createflow['FlowName'];
                $rdata['fieldData'] = $fieldDatas;
                $rdata['fieldFormdata'] = $fieldFormdatas;

                $rdata['approverData'] = $approverDatas;
                $rdata['approverFormdata'] = $approverFormdatas;
                $rdata['createflowid'] = $newlevel['tencent_qian_createflowid'];
                $rdata['agree_id'] = $exit['id'];
                $rdata['newlv_id'] = $exit['newlv_id'];
                $rdata['fieldfill']= $createflow['fieldfill'];
                return $this->json($rdata);
            }
        }
    }

    public function tencentqianlog(){
        if(getcustom('extend_tencent_qian')){
            if(request()->isPost()){
                $pagenum = input('post.pagenum');
                $st = input('post.st');
                if(!$pagenum) $pagenum = 1;
                $pernum = 20;
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                if($st!= 'all'){
                    if($st>=0){
                        $where[] = ['status','=',$st];
                    }else if($st<0){
                        $where[] = ['status','<',0];
                    }
                }
                $datalist = Db::name('member_tencent_qianlog')->where($where)->field('id,FlowName,levelid,type,wxurl,h5url,status,createtime')->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as &$dv){
                	if($dv['status'] == 1){
                		$dv['cancelFlow'] = true;
                	}
                	if($dv['status']<0){
                		$dv['wxurl'] = $dv['h5url'] = '';
                	}
                    $dv['typename'] = $dv['levelname'] = '';
                    if($dv['type'] == 'member_uplevel'){
                        $dv['typename'] = '等级升级';
                        $level = Db::name('member_level')->where('id',$dv['levelid'])->field('id,name')->find();
                        if($level){
                            $dv['levelname'] = $level['name'];
                        }else{
                            $dv['levelname'] = '等级已失效';
                        }
                        $dv['createtime'] = date("Y-m-d H:i",$dv['createtime']);
                    }
                }
                unset($dv);
                return $this->json(['status'=>1,'data'=>$datalist]);
            }
        }
    }

    public function tencentqianCancelFlow(){
        if(getcustom('extend_tencent_qian')){
            if(request()->isPost()){
                $id = input('?param.id')?input('param.id/d'):0;
                $reason = input('?param.reason')?input('param.reason'):'';
                if(!$reason)  return $this->json(['status'=>0,'msg'=>'请填写撤销理由']);
                $where = [];
                $where[] = ['id','=',$id];
                $where[] = ['mid','=',mid];
                $where[] = ['aid','=',aid];
                $qianlog = Db::name('member_tencent_qianlog')->where($where)->find();
                if(!$qianlog) return $this->json(['status'=>0,'msg'=>'记录不存在']);
                if($qianlog['status'] != 1) return $this->json(['status'=>0,'msg'=>'记录状态不符']);

                //撤销合同
                $params = [];
                $params['FlowId'] = $qianlog['FlowId'];
                $params['CancelMessage'] = $reason;
                $resstartFlow = \app\custom\TencentQian::cancelFlow(aid,0,$params);
                if(!$resstartFlow || $resstartFlow['status'] != 1){
                    $msg = $resstartFlow && $resstartFlow['msg']?$resstartFlow['msg']:'撤销失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                Db::name('member_tencent_qianlog')->where('id',$id)->update(['status'=>-1,'reason'=>'主动撤销合同:'.$reason]);
                return $this->json(['status'=>1,'data'=>$datalist]);
            }
        }
    }

    //核销记录
    public function couponHexiaolog(){
        if(getcustom('coupon_mobile_hexiaolog')){
            $pagenum = input('post.pagenum');
            $id = input('post.id/d',0);
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['orderid','=',$id];
            $where[] = ['type','=','coupon'];
            $where[] = ['aid','=',aid];
            $datalist = Db::name('hexiao_order')->where($where)->page($pagenum,$pernum)->field('id,uid,createtime')->order('id desc')->select()->toArray();
            foreach($datalist as &$v){
                //门店
                $v['md_name'] = '';
                // $v['md_name'] = '无';
                // if($v['mdid'] && $v['mdid']>0){
                //  $mendian = Db::name('mendian')->where('id',$v['mdid'])->field('id,name')->find();
                //  if($mendian){
                //      $v['md_name'] = $mendian['name'];
                //  }
                // }

                $v['un'] = '';
                if($v['uid']){
                    $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                    if($un){
                        $v['un'] = '核销员['.hideMiddleName($un).']核销';
                    }
                }

                $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
            }
            unset($v);
            return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
        }
    }
}
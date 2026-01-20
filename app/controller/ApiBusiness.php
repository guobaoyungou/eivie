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
class ApiBusiness extends ApiCommon{
	public $bset = [];
    public function initialize(){
		parent::initialize();
		$bset = Db::name('business_sysset')->where('aid',aid)->find();
		if($bset['status'] == 0){
			die(jsonEncode(['status'=>-4, 'msg' => '功能未开启', 'url'=>'/pages/index/index']));
		}
		$this->bset = $bset;
	}
	//商家详情页
	public function index($select_bid=0){
		if(getcustom('member_business')){
			//商户注册会员
            if(input('param.regbid')){
            	//平台权限
                $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                if($admin_user){
                	if($admin_user['auth_type'] !=1 ){
	                    $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
	                    if(in_array('MemberBusiness,MemberBusiness',$admin_auth)){
	                        $this->checklogin();
		                }
	                }else{
	                	$this->checklogin();
	                }
	            }
            }
        }
		$bid = $select_bid>0 ? $select_bid : input('param.id/d');
        //记录接口访问请求的bid
        if($bid > 0) cache($this->sessionid.'_api_bid',$bid,3600);
        $latitude = input('param.latitude/f');
        $longitude = input('param.longitude/f');

        //首消店铺
        if(getcustom('business_buy_bind_show_page') || getcustom('business_last_buy_bind_show_page')){
            $type = input('param.type','');
            if($type == 'firstbuy') {
                $firstbuyBid = $this->firstbuyBusiness($type);
                if ($firstbuyBid == 0) {
                    return $this->json(['status' => 0, 'msg' => '未查询到店铺消费记录', 'type' => 'firstbuy']);
                }
                $bid = $firstbuyBid;
            }elseif ($type == 'lastbuy'){
                $this->checklogin();
                $currentMember = $this->member;
                if ($currentMember['lastbuy_business'] > 0) {
                    $bid = $currentMember['lastbuy_business'];
                }else{
                    //没有末消店铺查询上级末消店铺
                    if (!$currentMember['pid']) {
                        return $this->json(['status' => 0, 'msg' => '未查询到店铺消费记录', 'type' => 'lastbuy']);
                    }
                    $parentLastbuyBid = Db::name('member')->where('aid', aid)->where('id', $currentMember['pid'])->value('lastbuy_business');
                    if ($parentLastbuyBid <= 0) {
                        return $this->json(['status' => 0, 'msg' => '未查询到店铺消费记录', 'type' => 'lastbuy']);
                    }
                    $bid = $parentLastbuyBid;
                }
            }
        }
		if(getcustom('business_bind_show_page')){
			$type = input('param.type','');
			if($type == 'bind' && $bid > 0 && $this->member) {
				Db::name('member')->where('aid',aid)->where('id',$this->member['id'])->update(['bind_business' => $bid]);
			}
		}
		$business = Db::name('business')->where('aid',aid)->where('id',$bid)->where('status',1)->find();
		if(!$business) return $this->json(['status'=>0,'msg'=>'商家信息不存在']);
        //如果是门店模式且用户选择了门店，则显示该商户该门店的数据，否则显示全部门店数据
        $mendian_id = input('param.mendian_id/d',0);
		if($business['is_open']==0) return $this->json(['status'=>-4,'msg'=>'商家未营业']);
		

        //新积分
        if(getcustom('yx_new_score')){
            $business['newscore_ratio'] = bcmul($business['newscore_ratio'],$business['member_newscore_ratio']/100,2);
        }
		$is_open = 0;
		if($is_open==0){
			if($business['start_hours'] != $business['end_hours']){
				$start_time = strtotime(date('Y-m-d '.$business['start_hours']));
				$end_time = strtotime(date('Y-m-d '.$business['end_hours']));
				if(($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time > $end_time && ($start_time > time() && $end_time < time()))){
					//return $this->json(['status'=>-4,'msg'=>'商家不在营业时间']);
				}else{
					$is_open = 1;
				}
			}else{
				$is_open = 1;
			}
		}
		if($is_open==0){
			$start_time = strtotime(date('Y-m-d '.$business['start_hours2']));
			$end_time = strtotime(date('Y-m-d '.$business['end_hours2']));
			if($start_time == $end_time || ($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time > $end_time && ($start_time > time() && $end_time < time()))){
				//return $this->json(['status'=>-4,'msg'=>'商家不在营业时间']);
			}else{
				$is_open = 1;
			}
		}
		if($is_open==0){
			$start_time = strtotime(date('Y-m-d '.$business['start_hours3']));
			$end_time = strtotime(date('Y-m-d '.$business['end_hours3']));
			if($start_time == $end_time || ($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time > $end_time && ($start_time > time() && $end_time < time()))){
				//return $this->json(['status'=>-4,'msg'=>'商家不在营业时间']);
			}else{
				$is_open = 1;
			}
		}
		if($is_open == 0 && $business['end_buy_status'] == 0){
            $open_time = $business['start_hours'].'-'.$business['end_hours'];
            if($business['start_hours2'] != $business['end_hours2']){
                $open_time .= ' '.$business['start_hours2'].'-'.$business['end_hours2'];
            }
            if($business['start_hours3'] != $business['end_hours3']){
                $open_time .= ' '.$business['start_hours3'].'-'.$business['end_hours3'];
            }
			return $this->json(['status'=>-4,'msg'=>'商家已打烊，营业时间为:'.$open_time]);
		}
		$bset = Db::name('business_sysset')->where('aid',aid)->find();

		// 是否强制登录
		if(getcustom('design_business_history')){
			if(!empty($bset['homepage_need_login']) && $bset['homepage_need_login'] == 1){
				$this->checklogin();
			}
        }
		$addviewnum = 1;
		if(getcustom('blist_showviewnum')){
			$addviewnum = $bset['viewnum_addnum'];
			$business['viewnum'] = $business['viewnum'] + $bset['viewnum_defaultnum'];
		}
		$bset['show_mianndan'] = 0;
		if(getcustom('yx_business_miandan')){
			$miandan_set = Db::name('business_miandan_set')->where('aid',aid)->where('bid',$bid)->find();
			if($miandan_set['status'] == 1){
				$bset['show_mianndan'] = 1;
			}
		}
		Db::name('business')->where('id',$bid)->inc('viewnum',$addviewnum)->update();
        $business['turnover'] = 0;
        if(getcustom('business_show_turnover')){
            if($business['turnover_show'] == 1){
                $business['turnover'] = \app\common\Business::totalTurnover(aid, $bid);
            }
        }
		$countcomment = Db::name('business_comment')->where('aid',aid)->where('bid',$bid)->where('status',1)->count();
		$couponcount= Db::name('coupon')->where('aid',aid)->where('bid',$bid)->where('tolist',1)->order('sort desc,id desc')->count();
		
		$prosales = Db::name('shop_product')->where('bid',$bid)->sum('sales');
		if($business['sales'] < $prosales) $business['sales'] = $prosales;
        $sales = Db::name('business_sales')->where('bid',$bid)->value('total_sales');
        $business['sales'] = !$sales?0:$sales;
        $admin_set = Db::name('admin_set')->where('aid',aid)->find();
		$pagedata = Db::name('designerpage')->where('aid',aid)->where('bid',$bid)->where('ishome',1)->find();

        //门店模式下 如果有门店，则显示门店信息
        $show_mendian = 0;
        $mendian = '';
        $rdata = [];
        if(getcustom('show_location')){
            if($admin_set['mode']==3){
                //商户门店
                $bfield = 'id,name,province,city,district,address,longitude,latitude,pic';

                if($latitude && $longitude){
                    $mdorder = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) asc");
                    $bfield .=",round(6378.138*2*asin(sqrt(pow(sin( ({$latitude}*pi()/180-latitude*pi()/180)/2),2)+cos({$latitude}*pi()/180)*cos(latitude*pi()/180)* pow(sin( ({$longitude}*pi()/180-longitude*pi()/180)/2),2)))*1000) as distance";
                    $mendianlist = Db::name('mendian')->where('aid',aid)->where('status',1)->where('bid',$bid)->orderRaw($mdorder)->field($bfield)->select()->toArray();
                }else{
                    $bfield .=',0 distance';
                    $mendianlist = Db::name('mendian')->where('aid',aid)->where('status',1)->where('bid',$bid)->order('sort desc,id asc')->field($bfield)->select()->toArray();
                }
                if(count($mendianlist)>0){
                    $show_mendian = 1;
                    foreach ($mendianlist as $k=>$v){
                        if(empty($v['distance'])){
                            $mendianlist[$k]['distance'] = '';
                        }else{
                            $mendianlist[$k]['distance'] = round($v['distance']/1000,2).'km';
                        }
                        if($mendian_id && $mendian_id==$v['id']){
                            $mendian = $mendianlist[$k];
                        }
                    }
                }
                $rdata['show_mendian'] = $show_mendian;
                $rdata['mendian'] = $mendian??'';
                $rdata['mendianlist'] = $mendianlist??[];
            }
			if($admin_set['mode']==2 ){
				$rdata['show_location_jl'] = 1;
			}
        }
		if(getcustom('business_indexbindfenxiao')){
			$rdata['business_indexbindfenxiao'] = true;
			$buser = Db::name('admin_user')->where('aid',aid)->where('bid',$bid)->where('isadmin',1)->find();
			if($buser['mid']) $business['mid'] = $buser['mid'];
		}
        $sysset = ['name'=>$business['name'],'logo'=>$business['logo'],'desc'=>$business['address'],'tel'=>$business['tel'],'mode'=>$admin_set['mode'],'address' => $business['address'],'sysname'=>$admin_set['name']];
        if(getcustom('loc_business')){
        	$sysset['loc_business_show_address'] = $admin_set['loc_business_show_address'];
        }
		if(getcustom('business_bind_show_page')){
			$sysset['loc_business_show_type'] = $admin_set['loc_business_show_type'];
		}

        if(mid>0){
			//添加浏览历史
			$rs = Db::name('member_history')->where(array('aid'=>aid,'mid'=>mid,'proid'=>$bid,'type'=>'business'))->find();
			if($rs){
				Db::name('member_history')->where(array('id'=>$rs['id']))->update(['createtime'=>time()]);
			}else{
				Db::name('member_history')->insert(array('aid'=>aid,'mid'=>mid,'proid'=>$bid,'type'=>'business','createtime'=>time()));
			}
            if(getcustom('business_qrcode_binding_parent')){
                $business_binding_parent = Db::name('business_sysset')->where('aid',aid)->value('business_binding_parent');
                if($business_binding_parent){
                    //是扫码进来时
                    $umid = Db::name('admin_user')->where('aid',aid)->where('bid',$bid)->where('isadmin',1) ->value('mid');
                 
                    //我没绑定推荐人，且管理员有绑定会员  且 付款用户不能是管理员，且 父级推荐链中不能有我
                    $umpath = Db::name('member')->where('aid',aid)->where('id',$umid)->value('path');
                    $umpath = explode(',',$umpath);
                    $is_in = 0;
                    if($umpath && in_array(mid,$umpath))$is_in = 1;
                    if(!$this->member['pid'] && $umid && mid != $umid && !$is_in){
                        \app\model\Member::edit(aid,['id'=>mid,'pid'=>$umid]);
                    } 
                }
            }
		}

		if(!$pagedata){
			if(getcustom('plug_businessqr')){
				die(jsonEncode(['status'=>-3,'url'=>'/pages/shop/fastbuy2?bid='.$bid]));
			}
			$rdata['status'] = 1;
			$rdata['isdiy'] = 0;
			$rdata['bset'] = $bset;
			$rdata['sysset'] = $sysset;
			$rdata['business'] = $business;
			$rdata['countcomment'] = $countcomment;
			$rdata['couponcount'] = $couponcount;
			$rdata['pics'] = $business['pics']?explode(',',$business['pics']):[PRE_URL.'/static/img/topbg.png'];
			$rdata['showfw'] = getcustom('businessindex_showfw');
            if($select_bid) $rdata['needlocation'] = true;
			if(getcustom('businessindex_showfw')){
				$clist = Db::name('yuyue_category')->where('aid',aid)->where('pid',0)->where('bid',$bid)->where('status',1)->order('sort desc,id')->select()->toArray();
				$rdata['yuyue_clist'] = $clist;
			}
            if(getcustom('business_showshortvideo')){
                //短视频
                $rdata['shortvideos'] = [];
                $sysset = Db::name('shortvideo_sysset')->where('aid',aid)->field('list_type')->find();
                $rdata['shortvideo_type'] = $sysset?$sysset['list_type']:0;
                if($business['show_shortvideo'] && $business['show_shortvideo_num']>0){
                    $where = [];
                    $where[] = ['bid','=',$bid];
                    $where[] = ['status','=',1];
                    $where[] = ['aid','=',aid];
                    $svlist = Db::name('shortvideo')->where($where)->order('sort desc,zan_num desc,createtime desc')->page(1,$business['show_shortvideo_num'])->select()->toArray();
                    if($svlist){
                        foreach($svlist as &$sv){
                            if($sv['view_num'] > 10000){
                                $sv['view_num'] = round($sv['view_num'] / 10000,1).'W';
                            }
                            if($sv['zan_num'] > 10000){
                                $sv['zan_num'] = round($sv['zan_num'] / 10000,1).'W';
                            }
                            if(getcustom('video_qq_url')){
                                $sv['url'] = \app\custom\VideoQQ::getMp4Url($sv['url']);
                            }
                            if($sv['bid']!=0){
                                $binfo = Db::name('business')->where('aid',aid)->where('id',$sv['bid'])->field('id,name,logo')->find();
                            } else {
                                if($sv['mid'] > 0){
                                    $binfo = Db::name('member')->where('aid',aid)->where('id',$sv['mid'])->field('nickname name,headimg logo')->find();
                                }else{
                                    $binfo = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
                                }
                            }
                            if(!$binfo) $binfo = [];
                            $sv['binfo'] = $binfo;
                        }
                        unset($sv);
                        $rdata['shortvideos'] = $svlist;
                    }
                }
            }
            if(getcustom('business_poster')){
            	$rdata['showShare'] = true;
            }
            if(getcustom('loc_business')){
	        	$rdata['loc_business_title_color'] = $this->sysset['loc_business_title_color'];
	        }
			return $this->json($rdata);
		}

		$pageinfo = json_decode($pagedata['pageinfo'],true);
        $other = [];
        if(getcustom('business_channels') && $business['channels']){
			//替换视频号ID
			$pageDataContent = json_decode($pagedata['content'], true);
			$channels = $business['channels'];
			array_walk_recursive($pageDataContent, function(&$value, $key) use ($channels) {
				if (is_string($value) && strpos($value, 'channelsUserProfile::') === 0) {
					$value = "channelsUserProfile::".$channels;
				}
			});
			$pagedata['content'] = json_encode($pageDataContent);
        }
		$pagecontent = json_decode(\app\common\System::initpagecontent($pagedata['content'],aid,mid,platform,$latitude,$longitude,'',$mendian_id,0,0,$other),true);
		$pageparams = $pageinfo[0]['params'];
        if($pageparams['needlogin'] == '1'){
            $this->checklogin();
        }

		
		$guanggaopic = '';
		$guanggaourl = '';
		if(getcustom('extend_advertising')){
            //查询此时间段的开屏广告
            $dealHideDesigner = \app\model\ApiIndexs::dealHideDesigner($pageparams,$this->admin,platform);
            $advertisingset = $dealHideDesigner['advertisingset'];
        	$hide_designer = $dealHideDesigner['hide_designer'];
        	$advertising = $dealHideDesigner['advertising'];
        	$pageparams = $dealHideDesigner['pageparams'];
        }
		if($pageparams['showgg']==1 || $pageparams['showgg']==2){
			$showgg = 0;
			if($pageparams['ggrenqun']){
				if($pageparams['ggrenqun']['0']){
					$showgg = 1;
				}
				if($pageparams['ggrenqun']['-1'] && $this->member['subscribe']==1){
					$showgg = 1;
				}
				if($pageparams['ggrenqun']['-2'] && $this->member['subscribe']!=1){
					$showgg = 1;
				}
				if($showgg==0 && $pageparams['ggrenqun'][$this->member['levelid']]){
					$showgg = 1;
				}
			}
			if($showgg == 1 && $pageparams['cishu']==0 && $this->member){
				$hasshowlog = Db::name('guanggao_showlog')->where('mid',mid)->where('pic',$pageparams['guanggao'])->find();
				if($hasshowlog){
					$showgg = 0;
				}else{
					Db::name('guanggao_showlog')->insert(['aid'=>aid,'mid'=>mid,'pic'=>$pageparams['guanggao'],'createtime'=>time()]);
				}
			}
			if($showgg){
				$guanggaopic = $pageparams['guanggao'];
				$guanggaourl = $pageparams['hrefurl'];
			}
		}
        //广告参数
        $default_ggskip = $pageparams['showgg']==2?0:1;//视频默认不跳过，图片默认跳过
        $default_ggcover = $pageparams['showgg']==2?1:0;//视频默认全屏，图片默认不全屏
        $guanggaoparam = [
            'showgg'=>$pageparams['showgg']??0,
            'guanggaopic'=>$guanggaopic,
            'guanggaourl'=>$guanggaourl,
            'ggskip'=>$default_ggskip,
            'ggcover'=>$default_ggcover,
            'skiptype'=>1,//1右上角关闭 2底部跳过
            'cishu'=>$pageparams['cishu']??0,
        ];
        if($pageparams['showgg']>0){
            if(getcustom('design_guanggao_control')){
                $guanggaoparam['ggskip'] = isset($pageparams['ggskip'])?$pageparams['ggskip']:$default_ggskip;
                $guanggaoparam['ggcover'] = isset($pageparams['ggcover'])?$pageparams['ggcover']:$default_ggcover;
                $guanggaoparam['skiptype'] = 2;
            }
        }
        if(getcustom('extend_advertising')){
            //查询屏蔽设计页开屏广告是否开启了
            if($hide_designer && $advertising){
                $guanggaoparam['duration']= $advertising['duration'];//广告时长
                if($pageparams['showgg'] == 4){
                    $guanggaoparam['unitid']   = $advertising['unitid'];//小程序广告id
                    $guanggaoparam['adtype']   = $advertising['adtype'];//小程序广告类型
                    $guanggaoparam['bgcolor']  = $advertising['bgcolor'];//小程序广告颜色
                    $guanggaoparam['margin_y'] = $advertising['margin_y'];$guanggaoparam['margin_x'] = $advertising['margin_x'];
                    $guanggaoparam['padding_y']= $advertising['padding_y'];$guanggaoparam['padding_x']= $advertising['padding_x'];
                }
            }
        }


		
		if(!$pageparams['bgcolor']){
			$pageparams['bgcolor'] = '#f7f7f8';
		}
        $rdata['pics'] = $business['pics']?explode(',',$business['pics']):[PRE_URL.'/static/img/topbg.png'];
		$rdata['status'] = 1;
		$rdata['isdiy'] = 1;
		$rdata['bset'] = $bset;
		$rdata['guanggaopic'] = $guanggaopic;
		$rdata['guanggaourl'] = $guanggaourl;
		$rdata['guanggaotype'] = $pageparams['showgg'];
        $rdata['guanggaoparam'] = $guanggaoparam;
		$rdata['pageinfo'] = $pageparams;
		$rdata['pagecontent'] = $pagecontent;
        $rdata['sysset'] = $sysset;
        $rdata['business'] = $business;
        if($select_bid) $rdata['needlocation'] = true;
        if(getcustom('yuyue_douyin_video')){
            $yuyueset = Db::name('yuyue_set')->field('ad_status,ad_pic,ad_link,video_status,video_tag,video_title')->where('aid',aid)->where('bid',0)->find();
            if($yuyueset['video_tag']) $yuyueset['video_tag'] = explode(',',$yuyueset['video_tag']);
            else $yuyueset['video_tag'] = [];
            $rdata['yuyueset'] = $yuyueset;
        }

        if(getcustom('loc_business')){
        	$rdata['loc_business_title_color'] = $this->sysset['loc_business_title_color'];
        }
		return $this->json($rdata);
	}

	public function main(){
		$pageid = input('param.id/d');
		$pagedata = Db::name('designerpage')->where('aid',aid)->where('id',$pageid)->find();
		if(!$pagedata){
			return $this->json(['status'=>0,'msg'=>'页面不存在']);
		}
		$bid = $pagedata['bid'];

		$business = Db::name('business')->where('aid',aid)->where('id',$bid)->where('status',1)->find();
		$pageinfo = json_decode($pagedata['pageinfo'],true);
		$pagecontent = json_decode(\app\common\System::initpagecontent($pagedata['content'],aid,mid,platform),true);
		$pageparams = $pageinfo[0]['params'];
		if($pageparams['quanxian']){
			if(!$pageparams['quanxian']['0'] && !$pageparams['quanxian'][$this->member['levelid']]){
				return $this->json(['status'=>0,'msg'=>'您无查看权限']);
			}
		}
		if($pageparams['fufei']==1 && floatval($pageparams['money'])>0){//付费查看
			$hasff = Db::name('designerpage_order')->where('aid',aid)->where('pageid',$pagedata['id'])->where('mid',mid)->where('status',1)->find();
			if(!$hasff){
				$adata = array();
				$adata['aid'] = aid;
				$adata['pageid'] = $pagedata['id'];
				$adata['mid'] = mid;
				$adata['title'] = $pageparams['title'];
				$adata['price'] = floatval($pageparams['money']);
				$adata['ordernum'] = date('ymdHis').aid.rand(1000,9999);
				$adata['createtime'] = time();
				$orderid = Db::name('designerpage_order')->insertGetId($adata);
				return $this->json(['status'=>2,'msg'=>'需要付费查看','orderid'=>$orderid]);
			}
		}
		
		$guanggaopic = '';
		$guanggaourl = '';
		if(getcustom('extend_advertising')){
            //查询此时间段的开屏广告
            $dealHideDesigner = \app\model\ApiIndexs::dealHideDesigner($pageparams,$this->admin,platform);
            $advertisingset = $dealHideDesigner['advertisingset'];
        	$hide_designer = $dealHideDesigner['hide_designer'];
        	$advertising = $dealHideDesigner['advertising'];
        	$pageparams = $dealHideDesigner['pageparams'];
        }
		if($pageparams['showgg']==1 || $pageparams['showgg']==2){
			$showgg = 0;
			if($pageparams['ggrenqun']){
				if($pageparams['ggrenqun']['0']){
					$showgg = 1;
				}
				if($pageparams['ggrenqun']['-1'] && $this->member['subscribe']==1){
					$showgg = 1;
				}
				if($pageparams['ggrenqun']['-2'] && $this->member['subscribe']!=1){
					$showgg = 1;
				}
				if($showgg==0 && $pageparams['ggrenqun'][$this->member['levelid']]){
					$showgg = 1;
				}
			}
			if($showgg == 1 && $pageparams['cishu']==0 && $this->member){
				$hasshowlog = Db::name('guanggao_showlog')->where('mid',mid)->where('pic',$pageparams['guanggao'])->find();
				if($hasshowlog){
					$showgg = 0;
				}else{
					Db::name('guanggao_showlog')->insert(['aid'=>aid,'mid'=>mid,'pic'=>$pageparams['guanggao'],'createtime'=>time()]);
				}
			}
			if($showgg){
				$guanggaopic = $pageparams['guanggao'];
				$guanggaourl = $pageparams['hrefurl'];
			}
		}
		//广告参数
        $default_ggskip = $pageparams['showgg']==2?0:1;//视频默认不跳过，图片默认跳过
        $default_ggcover = $pageparams['showgg']==2?1:0;//视频默认全屏，图片默认不全屏
        $guanggaoparam = [
            'showgg'=>$pageparams['showgg']??0,
            'guanggaopic'=>$guanggaopic,
            'guanggaourl'=>$guanggaourl,
            'ggskip'=>$default_ggskip,
            'ggcover'=>$default_ggcover,
            'skiptype'=>1,//1右上角关闭 2底部跳过
            'cishu'=>$pageparams['cishu']??0,
        ];
        if($pageparams['showgg']>0){
            if(getcustom('design_guanggao_control')){
                $guanggaoparam['ggskip'] = isset($pageparams['ggskip'])?$pageparams['ggskip']:$default_ggskip;
                $guanggaoparam['ggcover'] = isset($pageparams['ggcover'])?$pageparams['ggcover']:$default_ggcover;
                $guanggaoparam['skiptype'] = 2;
            }
        }
        if(getcustom('extend_advertising')){
            //查询屏蔽设计页开屏广告是否开启了
            if($hide_designer && $advertising){
                $guanggaoparam['duration']= $advertising['duration'];//广告时长
                if($pageparams['showgg'] == 4){
                    $guanggaoparam['unitid']   = $advertising['unitid'];//小程序广告id
                    $guanggaoparam['adtype']   = $advertising['adtype'];//小程序广告类型
                    $guanggaoparam['bgcolor']  = $advertising['bgcolor'];//小程序广告颜色
                    $guanggaoparam['margin_y'] = $advertising['margin_y'];$guanggaoparam['margin_x'] = $advertising['margin_x'];
                    $guanggaoparam['padding_y']= $advertising['padding_y'];$guanggaoparam['padding_x']= $advertising['padding_x'];
                }
            }
        }

		$sysset = ['name'=>$business['name'],'logo'=>$business['pic'],'desc'=>$business['address'],'tel'=>$business['tel']];
		
		if(!$pageparams['bgcolor']){
			$pageparams['bgcolor'] = '#f7f7f8';
		}

		$rdata = [];
		$rdata['status'] = 1;
		$rdata['isdiy'] = 1;
		$rdata['guanggaopic'] = $guanggaopic;
		$rdata['guanggaourl'] = $guanggaourl;
        $rdata['guanggaotype'] = $pageparams['showgg'];
        $rdata['guanggaoparam'] = $guanggaoparam;
		$rdata['pageinfo'] = $pageparams;
		$rdata['pagecontent'] = $pagecontent;
		//dump($pagecontent);die;
		$rdata['sysset'] = $sysset;
		return $this->json($rdata);
	}
	//获取商品列表 评价列表
	public function getdatalist(){
		$id = input('param.id/d');
		$st = input('param.st/d');
		$mendian_id = input('param.mendian_id/d',0);//如果切换了门店，则只显示该商家下该门店的数据
		$pagenum = input('param.pagenum');
		if(!$pagenum) $pagenum = 1;
		if($st == 0){//商品
			$pernum = 20;
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',$id];
			$where[] = ['ischecked','=',1];
			$nowtime = time();
			$nowhm = date('H:i');
            if(getcustom('product_bind_mendian')){
                if($mendian_id){
                    $where[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
                }
            }
            $price_tag = $cost_tag = '￥';
            $price_color = $cost_color = '';
            $show_sellprice = true;
            $show_cost = false;
            $hidecart = false;
            if(getcustom('product_cost_show') || getcustom('product_sellprice_show') || getcustom('product_list_nocart')){
                $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
                if(isset($shopset['hide_sellprice']) && $shopset['hide_sellprice']==1){
                    $show_sellprice = false;
                }
                if(isset($shopset['hide_cost']) && $shopset['hide_sellprice']==0){
                    $show_cost = true;
                }
                if($shopset['sellprice_name']){
                    $price_tag = $shopset['sellprice_name'];
                }
                if($shopset['sellprice_color']){
                    $price_color = $shopset['sellprice_color'];
                }
                if($shopset['cost_name']){
                    $cost_tag = $shopset['cost_name'];
                }
                if($shopset['sellprice_color']){
                    $cost_color = $shopset['cost_color'];
                }
                if (getcustom('product_list_nocart') && $shopset['list_nocart_platform']){
                    $cartnoplatform = explode(',',$shopset['list_nocart_platform']);
                    if(in_array(platform,$cartnoplatform)){
                        $hidecart = true;
                    }
                }
            }

            //显示条件
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }else{
                $where2 .= " or find_in_set('-2',showtj)";
            }
            $where[] = Db::raw($where2);

			$where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

			$prolist = Db::name('shop_product')->where($where)->where('ischecked',1)->page($pagenum,$pernum)->order('sort desc,id desc')->select()->toArray();
            $prolist = $this->formatprolist($prolist);
			foreach ($prolist as $v=>&$value){
                $value['price_tag'] = $price_tag;
                $value['price_color'] = $price_color;
                $value['cost_tag'] = $cost_tag;
                $value['cost_color'] = $cost_color;
                $value['show_sellprice'] = $show_sellprice;
                $value['show_cost'] = $show_cost;
                $value['hide_cart'] = $hidecart;

                if(getcustom('member_level_price_show')){
                    //获取第一个规格的会员等级价格
                    $priceshows = [];
                    $price_show = 0;
                    $price_show_text = '';
                }

                $gglist = Db::name('shop_guige')->where('proid',$value['id'])->select()->toArray();
                foreach($gglist as $gk=>$gv){
                    if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        if($gk == 0 && $value['lvprice'] == 1 && $gv['lvprice_data']){
                            $lvprice_data = json_decode($gv['lvprice_data'],true);
                            if($lvprice_data){
                                $lk=0;
                                foreach($lvprice_data as $lid=>$lv){
                                    $level = Db::name('member_level')->where('id',$lid)->where('price_show',1)->field('id,price_show_text')->find();
                                    if($level){
                                        //当前会员等级价格标记并去掉
                                        if($this->member && $this->member['levelid'] == $lid){
                                            $price_show = 1;
                                            $price_show_text = $level['price_show_text'];
                                        }else{
                                            $priceshow = [];
                                            $priceshow['id'] = $lid;
                                            $priceshow['sell_price'] = $lv;
                                            $priceshow['price_show_text'] = $level['price_show_text'];
                                            $priceshows[] = $priceshow;
                                        }
                                    }
                                    if($lk == 0){
                                        //普通价格
                                        $value['sell_putongprice'] = $lv;
                                    }
                                    $lk ++ ;
                                }
                                unset($lid);unset($lv);
                            }
                        }
                    }
                }
                unset($gk);unset($gv);

                if(getcustom('member_level_price_show')){
                    //获取第一个规格的会员等级价格
                    $value['priceshows'] = $priceshows?$priceshows:'';
                    $value['price_show'] = $price_show;
                    $value['price_show_text'] = $price_show_text;
                }
            }
			if(getcustom('product_wholesale')){
				foreach($prolist as $k=>$v){
					if($v['product_type'] == 4){
						$guigedata = json_decode($v['guigedata'],true);
						$prolist[$k]['gg_num'] =  count($guigedata);
					}
				}
			}
			//未登录查看价格
			if(getcustom('show_price_unlogin')){
				$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
				$mid = mid;
				if(!$mid && $shopset['is_show_price_unlogin'] == 0){
					foreach($prolist as &$pv){
						$pv['sell_price'] =  $shopset['show_price_unlogin_txt'];					
					}
				}			
			}
			//未审核查看价格
			if(getcustom('show_price_uncheck')){
				$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
				if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){
					foreach($prolist as &$pv){
						$pv['sell_price'] =  $shopset['show_price_uncheck_txt'];					
					}
				}			
			}
            if(!$prolist) $prolist = [];
			if(request()->isPost()){
				return $this->json(['status'=>1,'data'=>$prolist]);
			}
		}elseif($st == -1){//预约商品
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['ischecked','=',1];
			//$where[] = ['status','=',1];
			$nowtime = time();
			$where[] = Db::raw("`status`=1  or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime)");
			$where[] = ['bid','=',$id];
			$order = 'sort desc,id desc';
			//分类 
			if(input('param.yuyue_cid')){
				$cid = input('post.yuyue_cid/d');
				//子分类
				$clist = Db::name('yuyue_category')->where('aid',aid)->where('pid',$cid)->column('id');
				if($clist){
					$clist2 = Db::name('yuyue_category')->where('aid',aid)->where('pid','in',$clist)->column('id');
					$cCate = array_merge($clist, $clist2, [$cid]);
					if($cCate){
						$whereCid = [];
						foreach($cCate as $k => $c2){
							$whereCid[] = "find_in_set({$c2},cid)";
						}
						$where[] = Db::raw(implode(' or ',$whereCid));
					}
				} else {
					$where[] = Db::raw("find_in_set(".$cid.",cid)");
				}
			}
			if(input('param.keyword')){
				$where[] = ['name','like','%'.input('param.keyword').'%'];
			}
			$pernum = 10;
			$pagenum = input('post.pagenum');
			if(!$pagenum) $pagenum = 1;
			$datalist = Db::name('yuyue_product')->field("id,pic,name,sales,sell_price,danwei")->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
			if(!$datalist) $datalist = [];
			$datalist = $this->formatprolist($datalist);
			return $this->json(['status'=>1,'data'=>$datalist]);
		}elseif($st == 3){
			//免单
			if(getcustom('yx_business_miandan')){
				$where = [];
				$where[] = ['aid','=',aid];
				$where[] = ['is_del','=',0];
				$where[] = ['status','=',1];
				$where[] = ['ischecked','=',1];

				if(input('param.bid')){
					$bid = input('param.bid/d');
				}elseif(input('param.id/d')){
					$bid = input('param.id/d');
				}else{
					$bid = 0;
				}			
				$where[] = ['bid','=',$bid];
				$order = 'id desc';
				$pernum = 10;
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$datalist = Db::name('business_miandan')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
				if(!$datalist) $datalist = [];
				$miandanset = Db::name('business_miandan_set')->where('aid',aid)->where('bid',$bid)->find();
				if($miandanset['status'] == 0){
					$datalist = [];
				}
				return $this->json(['status'=>1,'data'=>$datalist]);
			}
		}else{//评价
			$pernum = 10;
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',$id];
			$where[] = ['status','=',1];
			$commentlist = Db::name('business_comment')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$commentlist) $commentlist = [];
			foreach($commentlist as $k=>$pl){
				$commentlist[$k]['createtime'] = date('Y-m-d H:i',$pl['createtime']);
				if($commentlist[$k]['content_pic']) $commentlist[$k]['content_pic'] = explode(',',$commentlist[$k]['content_pic']);
			}
			if(request()->isPost()){
				return $this->json(['status'=>1,'data'=>$commentlist]);
			}
		}
	}
	//商家优惠券
	public function couponlist(){
		//商家优惠券
		$couponlist= Db::name('coupon')->where('aid',aid)->where('bid',$id)->where("unix_timestamp(starttime)<=".time()." and unix_timestamp(endtime)>=".time())->order('sort desc,id desc')->select()->toArray();
		if(!$couponlist) $couponlist = [];
		foreach($couponlist as $k=>$v){
			$haveget = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('couponid',$v['id'])->count();
			$couponlist[$k]['haveget'] = $haveget;
			$couponlist[$k]['starttime'] = date('m-d H:i',strtotime($v['starttime']));
			$couponlist[$k]['endtime'] = date('m-d H:i',strtotime($v['endtime']));
		}
		$rdata = [];
		$rdata['couponlist'] = $couponlist;
		return $this->json($rdata);
	}
	//分类商家
	public function clist(){
		$clist = Db::name('business_category')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
		if(request()->isPost()){
			$cid = input('param.cid/d');
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['status','=',1];
			//分类 
			if($cid){
				$where[] = Db::raw('find_in_set('.$cid.',cid)'); //['cid','=',$cid];
			}else{
				//$where[] = Db::raw('find_in_set('.$clist[0]['id'].',cid)'); // ['cid','=',$clist[0]['id']];
			}
			if(input('param.keyword')){
				$where[] = ['name','like','%'.input('param.keyword').'%'];
			}
			$nowhm = date('H:i');
			$where[] = Db::raw("(start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm')) or (start_hours2<end_hours2 and start_hours2<='$nowhm' and end_hours2>='$nowhm') or (start_hours2>end_hours2 and (start_hours2<='$nowhm' or end_hours2>='$nowhm')) or (start_hours3<end_hours3 and start_hours3<='$nowhm' and end_hours3>='$nowhm') or (start_hours3>end_hours3 and (start_hours3<='$nowhm' or end_hours3>='$nowhm'))");

			$pernum = 12;
			$pagenum = input('post.pagenum');
			if(!$pagenum) $pagenum = 1;

			$longitude = input('post.longitude/f');
			$latitude = input('post.latitude/f');
			if($longitude && $latitude){
				$orderBy = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) ");
			}else{
				$orderBy = 'sort desc,id';
			}
			$datalist = Db::name('business')->field("id,name,logo,desc,tel,address,longitude,latitude")->where($where)->order($orderBy)->page($pagenum,$pernum)->select()->toArray();
			if(!$datalist) $datalist = array();
			
			if($this->member){
				$memberlevel = Db::name('member_level')->where('id',$this->member['levelid'])->find();
			}else{
				$memberlevel = [];
			}
			if(getcustom('business_otherpage_showlink')){
                $showlink = true;//是否显示联系电话
                //otherpage_showlink 其他页面的联系电话是否显示 0 :默认 1：跟随首页显示联系商家设置 2 隐藏
                if($this->bset['otherpage_showlink'] && $this->bset['otherpage_showlink'] >0){
                    if($this->bset['otherpage_showlink'] == 1){
                        // 首页显示联系商家 0 隐藏 1 显示
                        if($this->bset['show_link'] == 0){
                            $showlink = false;
                        }
                    }else{
                        $showlink = false;
                    }
                }
            }
			foreach($datalist as $k=>$v){
				if($longitude && $latitude){
					$datalist[$k]['juli'] = (getdistance($longitude,$latitude,$v['longitude'],$v['latitude'],2)).'km';
				}else{
					$datalist[$k]['juli'] = '';
				}
				if($memberlevel && $memberlevel['notshowbtel']==1){
					$datalist[$k]['tel'] = Db::name('admin_set')->where('aid',aid)->value('tel');
				}
				if(getcustom('business_show_queue_free_ratio')){
                    $datalist[$k]['show_maidan'] = 1;
                }
                $activity_time_custom = getcustom('yx_queue_free_activity_time');
                if(getcustom('yx_queue_free')){
                    $free_set = Db::name('queue_free_set')->where('aid',aid)->where('bid',0)->find();
                    if($free_set['business_show_ratio_back']){
                        $rate_back = $free_set['rate_back'];
                        if($free_set['queue_type_business']==0){
                            $rate_back =  Db::name('queue_free_set')->where('aid',aid)->where('bid',$v['id'])->value('rate_back');
                        }
                        $datalist[$k]['rate_back'] = floatval($rate_back);
                        if($activity_time_custom) {
                            $queue_activity = Db::name('queue_free_set')->where('aid', aid)->where('bid', $v['id'])->field('activity_time,activity_time_status')->find();
                            $activity_time = $queue_activity['activity_time'];
                            $activity_time_status = $queue_activity['activity_time_status'];
                            $datalist[$k]['activity_time'] = $activity_time;
                            $datalist[$k]['activity_time_status'] = $activity_time_status;
                            $scoredkmaxpercent = $this->sysset['scoredkmaxpercent'];
                            $datalist[$k]['scoredkmaxpercent'] = floatval($scoredkmaxpercent);
                        }
                    }
                    
                }
                if(getcustom('business_otherpage_showlink')){
                	//是否显示联系电话
                    if(!$showlink){
                        $datalist[$k]['tel'] = '';
                    }
                }
			}
            $show_business_tel = 1;
            if(getcustom('business_list_show_tel')){
                $show_business_tel = Db::name('business_sysset')->where('aid',aid)->value('show_business_tel');
            }
			return $this->json(['status'=>1,'data'=>$datalist,'show_business_tel' => $show_business_tel]);
		}
		$rdata = [];
		$rdata['clist'] = $clist;
		return $this->json($rdata);
	}
	//商家列表
	public function blist(){
		if(getcustom('business_show_maidanscoredk')){
			//是否展示买单积分抵扣
			$show_maidanscoredk = Db::name('business_sysset')->where('aid',aid)->value('show_maidanscoredk');
			$show_maidanscoredk = $show_maidanscoredk?true:false;
        }
		if(request()->isPost()){
			$pernum = 10;
			$pagenum = input('param.pagenum/d');
			if(!$pagenum) $pagenum = 1;
			$cid = input('param.cid/d');
			$type = input('param.type','');
			$where = [];
			if(input('param.ids')){
				$where[] = ['b.id','in',input('param.ids')];
			}
			$where[] = ['b.aid','=',aid];
			$where[] = ['b.status','=',1];
			$where[] = ['b.is_open','=',1];
			if(getcustom('business_expert')){
				//查询可选择的达人商户
				if($type && $type == 'businessexpert'){
					$where[] = ['b.expertid','=',0];
				}
			}
            if(getcustom('business_city')){
                $business_city_id = input('param.business_city_id');
                if($business_city_id){
                    $business_city = Db::name('business_city')->where('aid',aid)->where('id',$business_city_id)->find();
                    if(!$business_city || !$business_city['status']){
                        return $this->json(['status'=>0,'msg'=>'城市链接已失效']);
                    }else{
                        if($business_city['district']){
                            $where[] = ['b.district','=',$business_city['district']];
                        }elseif ($business_city['city']){
                            $where[] = ['b.city','=',$business_city['city']];
                        }else{
                            $where[] = ['b.province','=',$business_city['province']];
                        }
                    }
                }
            }
			if($cid) $where[] = Db::raw('find_in_set('.$cid.',cid)'); // ['cid','=',$cid];
			if(input('param.keyword')){
				$where[] = ['b.name|b.desc','like','%'.input('param.keyword').'%'];
			}
			$nowhm = date('H:i');
			$where[] = Db::raw("(start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm')) or (start_hours2<end_hours2 and start_hours2<='$nowhm' and end_hours2>='$nowhm') or (start_hours2>end_hours2 and (start_hours2<='$nowhm' or end_hours2>='$nowhm')) or (start_hours3<end_hours3 and start_hours3<='$nowhm' and end_hours3>='$nowhm') or (start_hours3>end_hours3 and (start_hours3<='$nowhm' or end_hours3>='$nowhm'))");

			$latitude = input('param.latitude/f');
			$longitude = input('param.longitude/f');
			if($longitude && $latitude){
				$order = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) ");
			}else{
				$order = 'b.sort desc,b.id desc';
			}
			$field = input('param.field');
			if(getcustom('blist_showviewnum') && $field == 'sales'){
				$field = 'b.viewnum';
			}else if($field == 'sales'){
                $field = 's.total_sales';
            }
			if($field && $field!='juli'){
				$order = $field.' '.input('param.order').',b.id desc';
			}

			$bset = $this->bset;
			$field = 'b.id,b.logo,b.name,b.tel,b.address,b.latitude,b.longitude,b.comment_score,b.viewnum,b.tourl,s.total_sales sales';
			if(getcustom('business_reward_member')){
                $field .= ',reward_member,reward_member_bili';
            }
            if(getcustom('business_show_turnover')){
                $field .= ',turnover_show';
            }
            if(getcustom('business_maidan_scoredk')){
            	$field .= ',scoredkmaxset,scoredkmaxval';
            }
            if(getcustom('member_dedamount')){
            	$field .= ',paymoney_givepercent';
            }
            //购买过的商户列表
            if(getcustom('member_buy_business')){
                //未登录状态下 返回空
                if(!$this->member){
                    return $this->json(['status'=>1,'data'=>[]]);
                }

                if($type == 'buylog'){
                    $ids = Db::name('shop_order')
                        ->where('aid',aid)
                        ->where('mid',mid)
                        ->where('status','in','1,2,3')
                        ->where('bid','>',0)
                        ->group('bid')
                        ->column('bid');

                    //查询买单记录
                    $buy_ids = Db::name('maidan_order')
                        ->where('aid',aid)
                        ->where('mid',mid)
                        ->where('status','in','1,2,3')
                        ->where('bid','>',0)
                        ->group('bid')
                        ->column('bid');
                    $buy_new_ids = [];
                    if(getcustom('maidan_new')){
                        $buy_new_ids = Db::name('maidan_new_order')
                            ->where('aid',aid)
                            ->where('mid',mid)
                            ->where('status','in','1,2,3')
                            ->where('bid','>',0)
                            ->group('bid')
                            ->column('bid');
                    }
                    $ids = array_merge($ids,$buy_ids,$buy_new_ids);
                    $where[] = ['b.id','in',$ids];
                }
            }
            if(getcustom('active_coin')){
                $field .= ',activecoin_ratio,member_activecoin_ratio';
            }
            if(getcustom('yx_new_score')){
                $field .= ',newscore_ratio,member_newscore_ratio';
            }
            if(getcustom('yx_buyer_subsidy')){
                $blist_show_feepercent = Db::name('subsidy_set')->where('aid',aid)->value('blist_show_feepercent');
                if($blist_show_feepercent){
                    $field .= ',feepercent subsidy_ratio';
                }
            }
			$datalist = Db::name('business')
                ->alias('b')
                ->join('business_sales s','b.id=s.bid','left')
                ->where($where)
                ->field($field)
                ->page($pagenum,$pernum)
                ->order($order)
                ->select()
                ->toArray();
			$nowtime = time();
			$nowhm = date('H:i');
			if(!$datalist) $datalist = array();
			if($datalist){
				if(getcustom('business_show_maidanscoredk')){
                    if($show_maidanscoredk){
                        //查询买单积分抵扣比例
                        $scoredkmaxpercent = Db::name('admin_set')->where('aid',aid)->value('scoredkmaxpercent');
                        $scoredkmaxpercent = $scoredkmaxpercent?$scoredkmaxpercent:0;
                        if(getcustom('sysset_scoredkmaxpercent_memberset')){
                            //处理会员单独设置积分最大抵扣比例
                            $scoredkmaxpercent = \app\custom\ScoredkmaxpercentMemberset::dealmemberscoredk(aid,$this->member,$scoredkmaxpercent);
                        }
                    }
                }

                //隐藏手机号
                $show_business_tel = 1;
                if(getcustom('business_list_show_tel')){
                    $show_business_tel = Db::name('business_sysset')->where('aid',aid)->value('show_business_tel');
                }
                if(getcustom('member_dedamount')){
	                $dedamount_dkpercent = $this->sysset['dedamount_dkpercent'];
	            }
	            if(getcustom('business_otherpage_showlink')){
                    $showlink = true;//是否显示联系电话
                    //otherpage_showlink 其他页面的联系电话是否显示 0 :默认 1：跟随首页显示联系商家设置 2 隐藏
                    if($bset['otherpage_showlink'] && $bset['otherpage_showlink'] >0){
                        if($bset['otherpage_showlink'] == 1){
                            // 首页显示联系商家 0 隐藏 1 显示
                            if($bset['show_link'] == 0){
                                $showlink = false;
                            }
                        }else{
                            $showlink = false;
                        }
                    }
                }
		        foreach($datalist as $k=>$v){
	                $turnover = 0;
	                if(isset($v['turnover_show'])){
	                    $turnover = \app\common\Business::totalTurnover(aid, $v['id']);
	                }else{
	                    $v['turnover_show'] = 0;
	                }
	                $v['turnover'] = $turnover;
					$statuswhere = "`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )";
					if(getcustom('blist_showtype1')){
						$prolist = Db::name('shop_product')->where('bid',$v['id'])->where('ischecked',1)->where($statuswhere)->field('id,pic,name,sales,market_price,sell_price')->limit(8)->order('sales desc,sort desc,id desc')->select()->toArray();
					}else{
						$prolist = Db::name('shop_product')->where('bid',$v['id'])->where('ischecked',1)->where($statuswhere)->field('id,pic,name,sales,market_price,sell_price')->limit(4)->order('sort desc,id desc')->select()->toArray();
					}
					if(!$prolist) $prolist = array();
					$v['prolist'] = $prolist;
	                if(getcustom('restaurant')) {
	                    $restaurantProlist = Db::name('restaurant_product')->where('bid',$v['id'])->where('ischecked',1)->where($statuswhere)->field('id,pic,name,sales,market_price,sell_price')->limit(4)->order('sort desc,id desc')->select()->toArray();
	                    if(!$restaurantProlist) $restaurantProlist = array();
	                    $v['restaurantProlist'] = $restaurantProlist;
	                }
					if($longitude && $latitude){
						$v['juli'] = ''.getdistance($longitude,$latitude,$v['longitude'],$v['latitude'],2).'km';
					}else{
						$v['juli'] = '';
					}
					//商城销量
					$prosales = Db::name('shop_product')->where('bid',$v['id'])->sum('sales');
					//当 外卖或餐饮开启时，统计其销量 
	                $restaurant_sales = 0;
	                $restaurant_shop_status = Db::name('restaurant_shop_sysset')->where('bid',$v['id'])->value('status');
	                $restaurant_takeaway_status = Db::name('restaurant_takeaway_sysset')->where('bid',$v['id'])->value('status');
//	                if($restaurant_shop_status || $restaurant_takeaway_status){
//	                    $restaurant_sales =   Db::name('restaurant_product')->where('bid',$v['id'])->sum('sales');
//	                }

					if(getcustom('blist_showviewnum')){
						$v['viewnum'] = $v['viewnum'] + $bset['viewnum_defaultnum'];
					}
	                
					// if($v['sales'] < ($prosales + $restaurant_sales)) $v['sales'] =$prosales+$restaurant_sales;
	                $sales = Db::name('business_sales')->where('bid',$v['id'])->value('total_sales');
	                $v['sales'] = $sales?:0;
	                if(!getcustom('business_reward_member')){
	                    $v['reward_member'] = 0;
	                    $v['reward_member_bili'] = 0;
	                }
                    if(getcustom('business_show_maidanscoredk')){
                    	//是否展示买单积分抵扣
                        if($show_maidanscoredk){
                            $v['maidanscoredk_text'] = '线下可抵扣'.floatval($scoredkmaxpercent).'%';
                            if(getcustom('business_maidan_scoredk')){
                                if($v['scoredkmaxset']==1){
                                    $v['maidanscoredk_text'] = '线下可抵扣'.floatval($v['scoredkmaxval']).'%';
                                }else if($v['scoredkmaxset']==-1){
                                    $v['maidanscoredk_text'] = '';
                                }
                            }
                        }
                    }
                    if(getcustom('business_show_queue_free_ratio')){
                        $b_free_set =  Db::name('queue_free_set')->where('aid',aid)->where('bid',$v['id'])->find();
                        $free_set = Db::name('queue_free_set')->where('aid',aid)->where('bid',0)->find();
                        if($b_free_set['rate_status_business'] ==0 || $b_free_set['rate_status_business'] ==-1){//-1跟随系统0关闭修改
                            $v['queue_free_rate_back'] =$free_set['rate'];
                        }else{
                            $v['queue_free_rate_back'] =$b_free_set['rate'];
                        }
                        $v['queue_free_set'] = $b_free_set['status'];
                    }
                    $activity_time_custom = getcustom('yx_queue_free_activity_time');
                    if(getcustom('yx_queue_free')){
                        $free_set = Db::name('queue_free_set')->where('aid',aid)->where('bid',0)->find();
                        if($free_set['business_show_ratio_back']){
                            $rate_back = $free_set['rate_back'];
                            if($free_set['queue_type_business']==0){
                                $rate_back =  Db::name('queue_free_set')->where('aid',aid)->where('bid',$v['id'])->value('rate_back');
                            }
                            $v['rate_back'] = floatval($rate_back);
                            if($activity_time_custom) {
                                $queue_activity = Db::name('queue_free_set')->where('aid', aid)->where('bid', $v['id'])->field('activity_time,activity_time_status')->find();
                                $activity_time = $queue_activity['activity_time'];
                                $activity_time_status = $queue_activity['activity_time_status'];
                                $v['activity_time'] = $activity_time;
                                $v['activity_time_status'] = $activity_time_status;
                                $scoredkmaxpercent = $this->sysset['scoredkmaxpercent'];
                                $v['scoredkmaxpercent'] = floatval($scoredkmaxpercent);
                            }
                        }
                        
                    }
                    if(getcustom('member_dedamount')){
			            $dedamount_maxdkpercent = 0;//最大抵扣比例
			            if($dedamount_dkpercent>0 && $v['paymoney_givepercent']>0){
			            	$dedamount_maxdkpercent = round($dedamount_dkpercent * $v['paymoney_givepercent']/100,2);
			            }
			            $v['dedamount_maxdkpercent'] = $dedamount_maxdkpercent;
			        }

                    if($show_business_tel == 0){
                        $v['tel'] = '';
                    }
                    if(getcustom('active_coin')){
                        //显示激活币返利比例
                        $page_show_ratio = Db::name('active_coin_set')->where('aid', aid)->value('page_show_ratio');
                        if($page_show_ratio){
                            $activecoin_ratio = $v['activecoin_ratio'];
                            $member_activecoin_ratio = $v['member_activecoin_ratio'];
                            $v['activecoin_bili'] = bcmul($activecoin_ratio,$member_activecoin_ratio/100,2);
                        }
                    }
                    if(getcustom('yx_new_score')){
                        //显示激活币返利比例
                        $newscore_ratio = $v['newscore_ratio'];
                        $member_newscore_ratio = $v['member_newscore_ratio'];
                        $v['newscore_ratio'] = bcmul($newscore_ratio,$member_newscore_ratio/100,2);
                    }
                    if(getcustom('business_otherpage_showlink')){
                    	//是否显示联系电话
                        if(!$showlink){
                            $v['tel'] =  '';
                        }
                    }

                    if(getcustom('yx_new_score_speed_pack')){
                        //新积分加速包
                        $pack = Db::name('newscore_speed_pack')->where('aid',aid)->where('bid',$v['id'])->where('status',1)->find();
                        $v['newscore_pack_ratio'] = $pack['ratio']??0;
                    }
					$datalist[$k] = $v;
				}
			}
			
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
		//分类
		$clist = Db::name('business_category')->where('aid',aid)->where('status',1)->field('id,name,pic')->order('sort desc,id')->select()->toArray();
		
		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['showtype'] = 0;
		// if(getcustom('blist_showtype1')){
		// 	$rdata['showtype'] = 1;
		// }
		$rdata['showviewnum'] = false;
		if(getcustom('blist_showviewnum')){
			$rdata['showviewnum'] = true;
		}
		$show_style = 0;
		if(getcustom('business_nearby_list')){
            $show_style = 1;
        }
        $rdata['show_style'] = $show_style;
        if(getcustom('business_show_maidanscoredk')){
        	//是否展示买单积分抵扣
			$rdata['show_maidanscoredk'] = $show_maidanscoredk;
        }
        //设置首消店铺
        if(getcustom('business_select_show_page')){
            $firstbuy_bid = Db::name('member')->where('aid',aid)->where('id',mid)->value('firstbuy_business');
            $rdata['setfirstbuy'] = true;
            $rdata['firstbuy_bid'] = $firstbuy_bid;
        }
        //设置末消店铺
        if(getcustom('business_last_buy_bind_show_page')){
            $lastbuy_bid = Db::name('member')->where('aid',aid)->where('id',mid)->value('lastbuy_business');
            $rdata['setlastbuy'] = true;
            $rdata['lastbuy_bid'] = $lastbuy_bid;
        }

        $show_sales = 1;
        if(getcustom('business_list_show_sales')){
            $show_sales = Db::name('business_sysset')->where('aid',aid)->value('show_business_sales');
        }
        $rdata['show_sales'] = $show_sales;
		return $this->json($rdata);
	}
    //商家列表
    public function blist2(){
            $pernum = 15;
            $pagenum = input('post.pagenum/d');
            if(!$pagenum) $pagenum = 1;
            $cid = input('post.cid/d');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['status','=',1];
            $where[] = ['is_open','=',1];
            if($cid) $where[] = Db::raw('find_in_set('.$cid.',cid)'); // ['cid','=',$cid];
            if(input('param.keyword')){
                $where[] = ['name','like','%'.input('param.keyword').'%'];
            }
            $nowhm = date('H:i');
            $where[] = Db::raw("(start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm')) or (start_hours2<end_hours2 and start_hours2<='$nowhm' and end_hours2>='$nowhm') or (start_hours2>end_hours2 and (start_hours2<='$nowhm' or end_hours2>='$nowhm')) or (start_hours3<end_hours3 and start_hours3<='$nowhm' and end_hours3>='$nowhm') or (start_hours3>end_hours3 and (start_hours3<='$nowhm' or end_hours3>='$nowhm'))");

            $latitude = input('param.latitude/f');
            $longitude = input('param.longitude/f');
            if($longitude && $latitude){
                $order = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) ");
            }else{
                $order = 'sort desc,id desc';
            }
            $field = input('param.field');
            if($field && $field!='juli'){
                $order = $field.' '.input('param.order').',id desc';
            }
            $datalist = Db::name('business')->where($where)->field('id,logo,name,sales,address,latitude,longitude,comment_score,tel,start_hours,end_hours,start_hours2,end_hours2,start_hours3,end_hours3')->page($pagenum,$pernum)->order($order)->select()->toArray();
            $nowtime = time();
            $nowhm = date('H:i');
            if(!$datalist) $datalist = array();
            if(getcustom('business_otherpage_showlink')){
                $showlink = true;//是否显示联系电话
                //otherpage_showlink 其他页面的联系电话是否显示 0 :默认 1：跟随首页显示联系商家设置 2 隐藏
                if($this->bset['otherpage_showlink'] && $this->bset['otherpage_showlink'] >0){
                    if($this->bset['otherpage_showlink'] == 1){
                        // 首页显示联系商家 0 隐藏 1 显示
                        if($this->bset['show_link'] == 0){
                            $showlink = false;
                        }
                    }else{
                        $showlink = false;
                    }
                }
            }
            foreach($datalist as $k=>$v){
                $statuswhere = "`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )";
//                $prolist = Db::name('shop_product')->where('bid',$v['id'])->where($statuswhere)->field('id,pic,name,sales,market_price,sell_price')->limit(4)->order('sort desc,id desc')->select()->toArray();
                if(!$prolist) $prolist = array();
                $v['prolist'] = $prolist;
                if(getcustom('restaurant')) {
//                    $restaurantProlist = Db::name('restaurant_product')->where('bid',$v['id'])->where($statuswhere)->field('id,pic,name,sales,market_price,sell_price')->limit(4)->order('sort desc,id desc')->select()->toArray();
                    if(!$restaurantProlist) $restaurantProlist = array();
                    $v['restaurantProlist'] = $restaurantProlist;
                }
                if($longitude && $latitude){
                    $v['juli'] = ''.getdistance($longitude,$latitude,$v['longitude'],$v['latitude'],2).'km';
                }else{
                    $v['juli'] = '';
                }
                if(getcustom('business_otherpage_showlink')){
                	//是否显示联系电话
                    if(!$showlink){
                        $v['tel'] =  '';
                    }
                }
//                $prosales = Db::name('shop_product')->where('bid',$v['id'])->sum('sales');
//                if($v['sales'] < $prosales) $v['sales'] = $prosales;
                $datalist[$k] = $v;
            }
            return $this->json(['status'=>1,'data'=>$datalist]);
    }
	//入驻申请
	public function apply(){
		$this->checklogin();
        if(getcustom('business_num_limit')){
            if($this->admin['business_num_limit'] > 0){
                $bcount = Db::name('business')->where('aid',aid)->count();
                if($bcount >= $this->admin['business_num_limit']){
                    return $this->json(['status'=>-4,'msg'=>'多商户数量已达上限']);
                }
            }
        }
        if(getcustom('level_business_apply')){
            $business_apply = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->value('business_apply');
            if($business_apply == 0){
                return $this->json(['status'=>-4,'msg'=>'当前等级不允许申请','url'=>'/pagesExt/my/levelup']);
            }
        }

        $showunpwd = 1;//账号密码显示 0:隐藏 1：显示;
        $field = 'xieyi_show,xieyi,default_rate';
        if(getcustom('business_deposit')){
            $field .= ',deposit';
        }
        if(getcustom('business_apply_pid_input')){
            $field .= ',pid_input_status';
        }
        if(getcustom('business_apply_form_zhengming')){
        	$field .= ',zhengmingtype';
        }
        if(getcustom('business_apply_showunpwd')){
        	$field .= ',showunpwd';
        }
        if(getcustom('active_coin')){
            $field .= ',showMemberActivecoinRatio,showBusinessActivecoinRatio';
        }
        if(getcustom('active_coin_async_ratio')){
            $field .= ',auto_async_activecoin_ratio,activecoin_ratio_min,activecoin_ratio_max,activecoin_ratio_business,activecoin_ratio_member';
        }
        if(getcustom('yx_new_score')){
            $field .= ',showMemberNewscoreRatio,showBusinessNewscoreRatio
            ,auto_async_newscore_ratio,newscore_ratio_min,newscore_ratio_max,newscore_ratio_business,newscore_ratio_member';
        }
		if(getcustom('business_agent_referrer_independent')){
			$field .= ',referrer_independent';
		}
        if(getcustom('yx_buyer_subsidy')){
            $field .= ',maidan_rate_set,maidan_rate_min,maidan_rate_max';
        }
        $bset = Db::name('business_sysset')->where('aid',aid)->field($field)->find();
        if(getcustom('business_apply_showunpwd')){
        	$showunpwd = $bset['showunpwd'];
        }
        if(getcustom('active_coin')){
            //前端参数写反了
            $showMemberActivecoinRatio  = $bset['showMemberActivecoinRatio']?:0;
            $showBusinessActivecoinRatio= $bset['showBusinessActivecoinRatio']?:0;
        }
        if(getcustom('yx_new_score')){
            $showMemberNewscoreRatio  = $bset['showMemberNewscoreRatio']?:0;
            $showBusinessNewscoreRatio= $bset['showBusinessNewscoreRatio']?:0;
            $new_score = 1;
        }

        if(request()->isPost()){
			$formdata = input('post.info/a');
			//print_r($formdata['customformdata']);die;
			if($showunpwd){
				$hasun = Db::name('admin_user')->where('id','<>',$formdata['id'])->where('un',$formdata['un'])->find();
				if(!$formdata['id'] && $hasun){
					return $this->json(['status'=>0,'msg'=>'该账号已存在']);
				}
			}

            if(!checkTel(aid,$formdata['linktel'])){
                return $this->json(['status'=>0, 'msg'=>'请输入正确的联系人手机号']);
            }
            if(!checkTel(aid,$formdata['tel'], [1,2,3])){
                return $this->json(['status'=>0, 'msg'=>'请输入正确的客服电话']);
            }

			$info = [];
			$info['aid'] = aid;
			$info['mid'] = mid;
			$info['cid'] = $formdata['cid'];
			$info['name'] = $formdata['name'];
			$info['desc'] = $formdata['desc'];
			$info['linkman'] = $formdata['linkman'];
			$info['linktel'] = $formdata['linktel'];
			$info['tel'] = $formdata['tel'];
			$info['logo'] = $formdata['pic'];
			$info['pics'] = $formdata['pics'];
			$info['content'] = $formdata['content'];
			$info['address'] = $formdata['address'];
			$info['latitude'] = $formdata['latitude'];
			$info['longitude'] = $formdata['longitude'];
			$info['zhengming'] = $formdata['zhengming']??'';
			$info['status'] = 0;
            if(getcustom('business_apply_check') && isset($bset['business_apply_check']) && $bset['business_apply_check'] == 0) $info['status'] = 1;//审核关闭自动通过
			$info['createtime'] = time();
			if(getcustom('active_coin')){
			    $info['activecoin_ratio'] = $formdata['activecoin_ratio']?:0;
//                $info['member_activecoin_ratio'] = $formdata['member_activecoin_ratio']?:0;
//                $info['business_activecoin_ratio'] = $formdata['business_activecoin_ratio']?:0;
                //前端参数写反了，不想改前端
                if($showMemberActivecoinRatio){
                	$info['member_activecoin_ratio'] = $formdata['business_activecoin_ratio']?:0;
                }
                if($showBusinessActivecoinRatio){
	                $info['business_activecoin_ratio'] = $formdata['member_activecoin_ratio']?:0;
	            }
                if(getcustom('active_coin_async_ratio')){
                    if($bset['auto_async_activecoin_ratio']==1){
                        //自动同步让利比例
                        if($info['activecoin_ratio']<$bset['activecoin_ratio_min'] || $info['activecoin_ratio']>$bset['activecoin_ratio_max']){
                            return $this->json(['status'=>0, 'msg'=>'让利比例范围为'.$bset['activecoin_ratio_min'].' - '.$bset['activecoin_ratio_max']]);
                        }
                        $info['feepercent'] = $info['activecoin_ratio'];//抽成费率
                        $info['business_activecoin_ratio'] = $bset['activecoin_ratio_business']?:0;//让利到商家
                        $info['member_activecoin_ratio'] = $bset['activecoin_ratio_member']?:100;//让利到消费者
                        $info['cost_bili'] = 100-$info['activecoin_ratio'];//成本比例
                        if(getcustom('maidan_fenhong_new')){
                            $info['maidan_cost'] = 100-$info['activecoin_ratio'];//买单成本比例
                        }
                    }
                }
            }

            if(getcustom('yx_new_score')){
                //新积分让利比例设置
                $info['newscore_ratio'] = $formdata['newscore_ratio']?:0;
                if($showMemberNewscoreRatio){
                    $info['member_newscore_ratio'] = $formdata['member_newscore_ratio'] ?: 0;
                }
                if($showBusinessNewscoreRatio){
                    $info['business_newscore_ratio'] = $formdata['business_newscore_ratio'] ?: 0;
                }
                if($bset['auto_async_newscore_ratio']==1){
                    //自动同步让利比例
                    if($info['newscore_ratio']<$bset['newscore_ratio_min'] || $info['newscore_ratio']>$bset['newscore_ratio_max']){
                        return $this->json(['status'=>0, 'msg'=>'让利比例范围为'.$bset['newscore_ratio_min'].' - '.$bset['newscore_ratio_max']]);
                    }
                    $info['feepercent'] = $formdata['newscore_ratio'];//抽成费率
                    if(!$showMemberNewscoreRatio){
                        $info['member_newscore_ratio'] = $bset['newscore_ratio_member']?:100;//让利到消费者
                    }
                    if(!$showBusinessNewscoreRatio) {
                        $info['business_newscore_ratio'] = $bset['newscore_ratio_business'] ?: 0;//让利到商家
                    }
                    $info['cost_bili'] = 100-$formdata['newscore_ratio'];//成本比例
                    if(getcustom('maidan_fenhong_new')){
                        $info['maidan_cost'] = 100-$formdata['newscore_ratio'];//买单成本比例
                    }
                }
            }
            if(getcustom('business_category_feepercent')){
				//商家分类是否设置了抽成费率
				if($formdata['cid']){
					$category = Db::name('business_category')->where('id',$formdata['cid'])->field('feepercent')->find();
					if(!$category){
						return $this->json(['status'=>0, 'msg'=>'商家分类不存在']);
					}
					if(!is_null($category['feepercent'])){
						$info['feepercent'] = $category['feepercent'];
					}
				}
			}
			if(!isset($info['feepercent'])){
				$info['feepercent'] = Db::name('business_sysset')->where('aid',aid)->value('default_rate');
			}
            if(getcustom('yx_buyer_subsidy')){
                //消费补贴让利比例
                if($bset['maidan_rate_set']==1){
                    $info['feepercent'] = $formdata['feepercent']>0?$formdata['feepercent']:$bset['default_rate'];
                    if($info['feepercent']<$bset['maidan_rate_min'] || $info['feepercent']>$bset['maidan_rate_max']){
                        return $this->json(['status'=>0, 'msg'=>'抽成比例范围为'.$bset['maidan_rate_min'].' - '.$bset['maidan_rate_max']]);
                    }
                }else{
                    $info['feepercent'] = $bset['default_rate'];
                }
            }
            //通过经纬度获取省市区
            if($info['latitude'] && $info['longitude'] && !$info['district']){
                //通过坐标获取省市区
                $mapqq = new \app\common\MapQQ();
                $address_component = $mapqq->locationToAddress($info['latitude'],$info['longitude']);
                if($address_component && $address_component['status']==1){
                    $info['province'] = $address_component['province'];
                    $info['city'] = $address_component['city'];
                    $info['district'] = $address_component['district'];
                }
            }
			$uinfo = [];
			$uinfo['un'] = $formdata['un'];
			$uinfo['pwd'] = $formdata['pwd'];

			$hasyxqueuefree = 0;
			if(getcustom('yx_queue_free')){
				$hasyxqueuefree = 1;
			}
			if(getcustom('business_apply_pid_input')){
				$info['business_pid'] = $formdata['business_pid'];
			}
            if(getcustom('member_business_poster')){
                if(empty($info['business_pid'])){
                    $info['business_pid'] = $formdata['pid'];
                }
            }
			if(getcustom('member_recommend_apply_business')){
                $currentTime = time();
                $recommendSet = Db::name('recommend_apply_business')->where('aid',aid)->where('status',1)->where('start_time', '<=', $currentTime)->where('end_time', '>=', $currentTime)->find();
				if($recommendSet && $this->member['pid']) {
					$levelData = json_decode($recommendSet['leveldata'],true);
					//查询推荐人是否符合推荐条件
					$pidLevelId = Db::name('member')->where('aid',aid)->where('id',$this->member['pid'])->value('levelid');
					if (isset($levelData[$pidLevelId]) && (!isset($info['business_pid']) || !$info['business_pid'])) {
						$info['business_pid'] = $this->member['pid'];
					}
				}
			}
            $level_business_independent = getcustom('level_business_independent');
            if(getcustom('business_agent_referrer_independent')){
				//开启商家独立推荐
                if($bset['referrer_independent'] == 1){
                    if($level_business_independent){
                        $memberlevel = Db::name('member_level')->where('id',$this->member['levelid'])->find();
                        if($memberlevel['business_independent']==1){
                            $info['business_pid'] = mid;
                        }
                    }
                    $info['recommend_type'] = 2;//独立推荐
                    $business_pid = $info['business_pid'];
					if(isset($info['business_pid'])){
						unset($info['business_pid']);
					}
				}
			}
			//自定义表单end
			if($formdata['id']){
				//自定义表单start
				if(getcustom('business_apply_form')){
					$res = $this->customRegister($formdata['customformdata'],$formdata['customformid'],$formdata['id']);
					if($res['status']!=1){
						return $this->json(['status'=>0,'msg'=>$res['msg']]);
					}else if($res['status']==1 && isset($res['recordid']) && $res['recordid']>0){
						$info['form_record_id'] = $res['recordid'];
					}
				}
				Db::name('business')->where('aid',aid)->where('mid',mid)->where('id',$formdata['id'])->update($info);

				if($showunpwd){
					if($uinfo['pwd']!=''){
						$uinfo['pwd'] = md5($uinfo['pwd']);
					}else{
						unset($uinfo['pwd']);
					}
				}else{
					unset($uinfo['un']);
					unset($uinfo['pwd']);
				}
				Db::name('admin_user')->where('aid',aid)->where('bid',$info['id'])->where('id',$uinfo['id'])->update($uinfo);
				
				if(getcustom('business_apply_queue_free_rate_back') && $hasyxqueuefree == 1){
					if($formdata['id']){
						$free_set = Db::name('queue_free_set')->where('aid',aid)->where('bid',$formdata['id'])->find();
						$queue_free_set['rate_back'] = $formdata['rate_back'];
						if($free_set){							
							Db::name('queue_free_set')->where('aid',aid)->where('bid',$formdata['id'])->update($queue_free_set);
						}else{
							$queue_free_set['aid'] = aid;
							$queue_free_set['bid'] = $formdata['id'];
							$queue_free_set['rate_status_business'] = -1;
							$queue_free_set['money_max'] = null;
							$queue_free_set['status'] = 0;
							$queue_free_set['createtime'] = time();
							Db::name('queue_free_set')->insertGetId($queue_free_set);
						}						
					}			
				}
				if(getcustom('business_agent_referrer_independent')){
					//开启商家独立推荐
					if($bset['referrer_independent'] == 1 && isset($business_pid) && $business_pid > 0){
                        $bri = Db::name('business_referrer_independent')->where('aid',aid)->where('bid',$formdata['id'])->find();
                        if($bri){
						    Db::name('business_referrer_independent')->where('aid',aid)->where('bid',$formdata['id'])->update(['mid' => $business_pid]);
					    }else{
                            $bri['aid'] = aid;
                            $bri['bid'] = $formdata['id'];
                            $bri['mid'] = $business_pid;
                            $bri['createtime'] = time();
                            Db::name('business_referrer_independent')->insertGetId($bri);
                        }
                    }
				}
			}else{
				$bid = Db::name('business')->insertGetId($info);

				$uinfo['aid'] = aid;
				$uinfo['bid'] = $bid;
                $uinfo['mid'] = mid;
				$uinfo['auth_type'] = 1;
				if($showunpwd){
					$uinfo['pwd'] = md5($uinfo['pwd']);
				}else{
					unset($uinfo['un']);
					unset($uinfo['pwd']);
				}
				$uinfo['createtime'] = time();
				$uinfo['isadmin'] = 1;
				$uinfo['random_str'] = random(16);
				$id = Db::name('admin_user')->insertGetId($uinfo);

				//自定义表单start
				if(getcustom('business_apply_form')){
					$res = $this->customRegister($formdata['customformdata'],$formdata['customformid'],$bid);
					if($res['status']!=1){
						return $this->json(['status'=>0,'msg'=>$res['msg']]);
					}else if($res['status']==1 && isset($res['recordid']) && $res['recordid']>0){
						$infoa['form_record_id'] = $res['recordid'];
						Db::name('business')->where('aid',aid)->where('mid',mid)->where('id',$bid)->update($infoa);
					}
				}
				//返利比例
				if(getcustom('business_apply_queue_free_rate_back') && $hasyxqueuefree == 1){
					if($bid){
						$free_set = Db::name('queue_free_set')->where('aid',aid)->where('bid',$bid)->find();
						$queue_free_set['rate_back'] = $formdata['rate_back'];
						if($free_set){							
							Db::name('queue_free_set')->where('aid',aid)->where('bid',$bid)->update($queue_free_set);
						}else{
							$queue_free_set['aid'] = aid;
							$queue_free_set['bid'] = $bid;
							$queue_free_set['rate_status_business'] = -1;
							$queue_free_set['money_max'] = null;
							$queue_free_set['status'] = 0;
							$queue_free_set['createtime'] = time();
							Db::name('queue_free_set')->insertGetId($queue_free_set);
						}						
					}			
				}
                if(getcustom('business_deposit')){
                    if($bset['deposit'] > 0){
                        //生成保证金订单，跳转支付
                        $ordernum = date('ymdHis').aid.rand(1000,9999);
                        $money = $bset['deposit'];
                        $orderdata = [];
                        $orderdata['aid'] = aid;
                        $orderdata['mid'] = mid;
                        $orderdata['bid'] = $bid;
                        $orderdata['createtime']= time();
                        $orderdata['money'] = $money;
                        $orderdata['ordernum'] = $ordernum;
                        $orderid = Db::name('business_deposit_order')->insertGetId($orderdata);
                        $payorderid = \app\model\Payorder::createorder(aid,0,$orderdata['mid'],'business_deposit',$orderid,$ordernum,'商家入驻保证金充值',$money);

                        return $this->json(['status'=>-3,'msg'=>'提交成功，请支付保证金，等待审核','url'=>'/pagesExt/pay/pay?id='.$payorderid,'orderid'=>$orderid,'payorderid'=>$payorderid]);
                    }
                }
				if(getcustom('business_agent_referrer_independent')){
					//开启商家独立推荐
					if($bset['referrer_independent'] == 1 && isset($business_pid) && $business_pid > 0){
						Db::name('business_referrer_independent')->where('aid',aid)->where('bid',$formdata['id'])->insert([
							'aid' => aid,
							'bid' => $bid,
							'mid' => $business_pid,
							'createtime' => time()
						]);
					}
				}

                //添加默认数据
                \app\common\Business::addDefaultData(aid,$bid);
			}
			return $this->json(['status'=>1,'msg'=>'提交成功，请等待审核']);
		}

		
		$info = Db::name('business')->where('aid',aid)->where('mid',mid)->find();
		if($info && $info['status']==1){
			return $this->json(['status'=>2,'msg'=>'您已成功入驻']); 
		}
        if($info && $info['logo']){
            $info['pic'] = $info['logo'];
        }
		$clist = Db::name('business_category')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
        $nearby = 0;
        if(getcustom('business_nearby_list')){
            $nearby = 1;
        }
		if(getcustom('business_agent_referrer_independent')){
			if($bset['referrer_independent']){
				$info['business_pid'] = Db::name('business_referrer_independent')->where('bid',$info['id'])->value('mid')?:'';
			}
		}
        $bset['nearby'] = $nearby;
        $active_coin = 0;
        if(getcustom('active_coin')){
            $active_coin = 1;
        }
		//排队免单返利
		$queue_free_set = [];
		$queue_free_set['rate_back']= '';
		$queue_free_set['show_rate_back']= 0;
		$hasyxqueuefree = 0;
		if(getcustom('yx_queue_free')){
			$hasyxqueuefree = 1;
		}
		if($hasyxqueuefree == 1){
			$queue_free_set_all = Db::name('queue_free_set')->where('aid',aid)->where('bid',0)->find();
			if(getcustom('business_apply_queue_free_rate_back') && $queue_free_set_all['status'] ==1){
				$queue_free_set['show_rate_back'] = 1;
				if($info){
					$free_set = Db::name('queue_free_set')->where('aid',aid)->where('bid',$info['id'])->find();
					$queue_free_set['rate_back'] = $free_set['rate_back'];
				}			
			}
		}
		
		//定制内容.77
		$formField = [];
		$formvaldata = [];
		$register_record = [];
		$hasCustom = 0;
		if(getcustom('business_apply_form')){
			
				$formField = Db::name('business_apply_form')->where('aid',aid)->find();
				if($formField && $formField['content']){
					$custom_content = json_decode($formField['content'],true);
					foreach ($custom_content as &$cc) {
						if($cc['val4'] == 0){
							$cc['input_type']='text';
						}
						if($cc['val4'] == 1){
							$cc['input_type']='digit';
						}
						if($cc['val4'] == 2){
							$cc['input_type']='tel';
						}
						if($cc['val4'] == 3){
							$cc['input_type']='idcard';
						}
						if($cc['val4'] == 4){
							$cc['input_type']='email';
						}
						$cc['showtype'] = 0;//显示类型 0：默认显示方式 1：证明材料单独显示
						if(getcustom('business_apply_form_zhengming')){
							//证明材料样式，若开启证明材料自定义，则证明材料需要单独显示
							if($this->bset['zhengmingtype'] == 1 && $cc['key'] =='upload' && $cc['val6']=='1'){
								$cc['showtype'] = 1;
							}
						}
					}
					$formField['content']  = $custom_content;
					$hasCustom = 1;

					if($info['form_record_id']>0){
						$register_record = Db::name('business_apply_form_record')->where('id', $info['form_record_id'])->find();
						if($register_record){
							foreach ($custom_content as $k=>$item) {										
								$formvaldata['form'.$k] = $register_record['form'.$k]??'';
								//$register_forms[$k] = $item;
								if($item['key']=='checkbox'){
									if($register_record['form'.$k]){
										$register_forms[$k] = explode(',',$register_record['form'.$k]);		
									}else{
										$register_forms[$k] = [];		
									}
									
								}elseif($item['key']=='selector'){
									$register_forms[$k] = '';
									foreach($item['val2'] as $k2=>$v2){
										if($v2 == $register_record['form'.$k]){
											$register_forms[$k] = $k2;
										}
									}
																		
								}else{
									$register_forms[$k] = $register_record['form'.$k]??'';		
								}
															
							}
						}
					}
				}else{
					$formField = [];
				}
			
		}
		$rdata = [];
		$rdata['has_custom'] = $hasCustom;
		$rdata['custom_form_field'] = $formField;
		$rdata['register_forms'] = $register_forms;
		$rdata['formvaldata'] = $formvaldata;
        $rdata['title'] = '申请入驻';
		$rdata['clist'] = $clist;
		$rdata['bset'] = $bset;
		$rdata['info'] = $info ? $info : [];
		$rdata['active_coin'] = $active_coin;
		$rdata['queue_free_set'] = $queue_free_set;
		$rdata['showunpwd'] = $showunpwd;
		if(getcustom('active_coin')){
            //前端参数写反了
            $rdata['showBusinessActivecoinRatio'] = $showMemberActivecoinRatio;
            $rdata['showMemberActivecoinRatio']   = $showBusinessActivecoinRatio;
        }
        $rdata['new_score'] = $new_score??0;
        $rdata['showBusinessNewscoreRatio'] = $showMemberNewscoreRatio??0;
        $rdata['showMemberNewscoreRatio']   = $showBusinessNewscoreRatio??0;
		//是否展示消费补贴比例
        $rdata['showSubsidyRatio'] = $bset['maidan_rate_set']??0;
		return $this->json($rdata);
	}
	
	//商品搜索
	public function search(){
		$bid = input('param.bid/d');
		//分类
		if(input('param.cid')){
			$clist = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',input('param.cid/d'))->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}else{
			$clist = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}
		//分组
		//$glist = Db::name('shop_group')->where('aid',aid)->where('status',1)->select()->toArray();
		//if(!$glist) $glist = [];

		$productlisttype = cookie('productlisttype');
		if(!$productlisttype) $productlisttype = 'item2';
		
		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['glist'] = [];
		$rdata['productlisttype'] = $productlisttype;
		return $this->json($rdata);
	}
	//商品列表
	public function prolist(){
		$bid = input('param.bid/d');
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',$bid];
		$nowtime = time();
		$nowhm = date('H:i');
		$where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

		if(input('param.field') && input('param.order')){
			$order = input('param.field').' '.input('param.order').',sort,id desc';
		}else{
			$order = 'sort desc,id desc';
		}
		//分类 
		if(input('param.cid')){
			$cid = input('param.cid/d');
			$where[] = ['bcid','=',$cid];
			//子分类
			$clist = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',$cid)->select()->toArray();
			if($clist){
				$cateArr = [$cid];
				foreach($clist as $c){
					$cateArr[] = $c['id'];
				}
				$where[] = ['bcid','in',$cateArr];
			}
		}
		if(input('param.keyword')){
			$where[] = ['name','like','%'.input('param.keyword').'%'];
		}
		if(input('param.groupid')) $where[] = Db::raw("find_in_set(".intval(input('param.groupid')).",gid)");
		if(input('param.gid')) $where[] = Db::raw("find_in_set(".intval(input('param.gid')).",gid)");
        $where[] = ['ischecked','=',1];
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('shop_product')->field("id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint")->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		if(!$datalist) $datalist = array();
		$datalist = $this->formatprolist($datalist);
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
		
		//分类
		if(input('param.cid')){
			$clist = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',input('param.cid/d'))->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}else{
			$clist = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}

		$productlisttype = cookie('productlisttype');
		if(!$productlisttype) $productlisttype = 'item2';
		
		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['glist'] = [];
		$rdata['datalist'] = $datalist;
		$rdata['productlisttype'] = $productlisttype;
		
		return $this->json($rdata);
	}
	//分类商品
	public function classify(){
		$order = 'sort desc,id desc';
		if(input('param.field') && input('param.order')){
			$order = input('param.field').' '.input('param.order').',sort,id desc';
		}else{
			$order = 'sort desc,id desc';
		}
		$bid = input('param.bid/d');
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',$bid];
		//$where[] = ['status','=',1];
		$nowtime = time();
		$nowhm = date('H:i');
		$where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

		$cid = input('param.cid');
		
		$clist = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
		foreach($clist as $k=>$v){
			$rs = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$rs) $rs = [];
			$clist[$k]['child'] = $rs;
		}
		//if(!$cid) $cid = $clist[0]['id'];
		//分类 
		if($cid){
			$where[] = ['','=',intval($cid)];
			$title = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('id',$where['bcid'])->order('sort desc,id')->value('name');
			//子分类
			$child = Db::name('business_shop_category')->where('aid',aid)->where('bid',$bid)->where('pid',$where['bcid'])->select()->toArray();
			if($child){
				$cateArr = [$where['bcid']];
				foreach($child as $c){
					$cateArr[] = $c['id'];
				}
				$where[] = ['bcid','in',$cateArr];
			}
		}
		if(input('param.keyword')){
			$where[] = ['name','like','%'.input('param.keyword').'%'];
		}
		if(input('param.groupid')) $where[] = Db::raw("find_in_set(".intval(input('param.groupid')).",gid)");
        $where[] = ['ischecked','=',1];
		$pernum = 12;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('shop_product')->field("pic,id,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint")->where($where)->order($order)->page($pagenum,$pernum)->select()->toArray();
		if(!$datalist) $datalist = array();
		$datalist = $this->formatprolist($datalist);
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}

		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	//获取子分类
	public function getdownclist(){
		$pid = input('param.id/d');
		$clist = Db::name('business_shop_category')->where('aid',aid)->where('pid',$pid)->where('status',1)->order('sort desc,id')->select()->toArray();
		if(!$clist) $clist = [];
		return $this->json(['status'=>1,'data'=>$clist]);
	}
	
	//商品评价
	public function commentlist(){
		$bid = input('param.bid/d');
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',$bid];
		$where[] = ['status','=',1];
		$datalist = Db::name('business_comment')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$pl){
			$datalist[$k]['createtime'] = date('Y-m-d H:i',$pl['createtime']);
			if($datalist[$k]['content_pic']) $datalist[$k]['content_pic'] = explode(',',$datalist[$k]['content_pic']);
		}
		
		$business = Db::name('business')->field('id,name,logo,desc,comment_num,comment_score,comment_haopercent')->where('aid',aid)->where('id',$bid)->where('status',1)->find();
		if(!$business) return $this->json(['status'=>0,'msg'=>'商家信息不存在']);

		$rdata = [];
		$rdata['data'] = $datalist;
		$rdata['business'] = $business;
		return $this->json($rdata);
	}


    public function mybusiness(){
        if(getcustom('business_mybusiness')){
        	$this->checklogin();
            //我的店铺
            if(request()->isPost()){
                //查询我的店铺
                $bids = Db::name('admin_user')->alias('au')->join('business b','b.id = au.bid')
                    ->where('au.isadmin','>=',1)->where('au.isadmin','<=',2)->where('au.bid','>',0)->where('au.mid',mid)->where('au.aid',aid)->group('au.bid')->column('au.bid');
                if(!$bids){
                    return $this->json(['status'=>0,'msg'=>'您还未入驻','goback'=>true]);
                }
                $bidsnum = count($bids);
                if($bidsnum == 1){
                    return $this->json(['status'=>1,'msg'=>'获取成功','bid'=>$bids[0]]);
                }else{
                	$bids = implode(',',$bids);
                    return $this->json(['status'=>2,'msg'=>'获取成功','bids'=>$bids]);
                }
            }
        }
    }

    //首消店铺
    public function firstbuyBusiness($type=''){
        $maidan_new_getcustom = getcustom('maidan_new');
        if(getcustom('business_buy_bind_show_page')){
            if(!$this->member){
                $this->checklogin();
            }

            //查询用户首次购买的店铺
            $firstbuyBid = $this->member['firstbuy_business'] ?: 0;
            if($firstbuyBid == 0) {
                //查询用户首次购买的店铺订单
                $businessOrder = Db::name('shop_order')
                    ->field('bid,createtime')
                    ->where('aid', aid)
                    ->where('mid', mid)
                    ->where('bid', '>', 0)
                    ->where('status', 'in', '1,2,3')
                    ->order('createtime asc')
                    ->find();

                //查询用户首次店铺买单记录
                $businessMaidanOrder = Db::name('maidan_order')
                    ->field('bid,createtime')
                    ->where('aid', aid)
                    ->where('mid', mid)
                    ->where('bid', '>', 0)
                    ->where('status', 'in', '1,2,3')
                    ->order('createtime asc')
                    ->find();
                $businessMaidanNewOrder = [];
                if($maidan_new_getcustom){
                    $businessMaidanNewOrder = Db::name('maidan_new_order')
                        ->field('bid,createtime')
                        ->where('aid', aid)
                        ->where('mid', mid)
                        ->where('bid', '>', 0)
                        ->where('status', 'in', '1,2,3')
                        ->order('createtime asc')
                        ->find();
                }
                // 比较两个订单的时间，取较早的一个  2 3 1
                if($businessOrder && $businessMaidanOrder && $businessMaidanNewOrder){
                    if ($businessOrder['createtime'] <= $businessMaidanOrder['createtime'] && $businessOrder['createtime'] <= $businessMaidanNewOrder['createtime']) {
                        $firstbuyBid = $businessOrder['bid'];
                    } elseif ($businessMaidanOrder['createtime'] <= $businessOrder['createtime'] && $businessMaidanOrder['createtime'] < $businessMaidanNewOrder['createtime']) {
                        $firstbuyBid = $businessMaidanOrder['bid'];
                    } else {
                        $firstbuyBid  = $businessMaidanNewOrder['bid'];
                    }
                }elseif($businessOrder && $businessMaidanNewOrder){
                    $firstbuyBid = $businessOrder['createtime'] < $businessMaidanNewOrder['createtime'] ? $businessOrder['bid'] : $businessMaidanNewOrder['bid'];
                } elseif($businessMaidanOrder && $businessMaidanNewOrder){
                    $firstbuyBid = $businessMaidanOrder['createtime'] < $businessMaidanNewOrder['createtime'] ? $businessMaidanOrder['bid'] : $businessMaidanNewOrder['bid'];
                }
                elseif ($businessOrder && $businessMaidanOrder) {
                    $firstbuyBid = $businessOrder['createtime'] < $businessMaidanOrder['createtime'] ? $businessOrder['bid'] : $businessMaidanOrder['bid'];
                } elseif ($businessOrder) {
                    $firstbuyBid = $businessOrder['bid'];
                } elseif ($businessMaidanOrder) {
                    $firstbuyBid = $businessMaidanOrder['bid'];
                }   elseif ($businessMaidanNewOrder) {
                    $firstbuyBid = $businessMaidanNewOrder['bid'];
                }

                // 如果没有订单获取上级的首消店铺
                if ($firstbuyBid == 0) {
                    $firstbuyBid = Db::name('member')
                        ->where('aid', aid)
                        ->where('id', $this->member['pid'])
                        ->value('firstbuy_business') ?: 0;
                }else{
                    Db::name('member')
                        ->where('aid',aid)
                        ->where('id',mid)
                        ->update(['firstbuy_business' => $firstbuyBid]);
                }
            }

            return $firstbuyBid;
        }
        return 0;
    }

    //设置首消店铺/末消店铺
    public function setFirstBuyBusiness(){
        if(getcustom('business_select_show_page') || getcustom('business_last_buy_bind_show_page')){
            $this->checklogin();
            $setBid = input('param.bid/d');
            $type = input('param.type','firstbuy');
            if(empty($setBid)){
                return $this->json(['status' => 0 ,'msg' => '请选择店铺']);
            }
            $field = 'firstbuy_business';
            if($type == 'lastbuy'){
                $field = 'lastbuy_business';
            }

            //验证是否重复设置
            $memberBid = Db::name('member')->where('aid',aid)->where('id',mid)->value($field);
            if($memberBid == $setBid){
                return $this->json(['status' => 0 ,'msg' => '设置失败，请不要重复设置']);
            }

            //查询信息
            $check = Db::name('business')->where('aid',aid)->where('id',$setBid)->find();
            if(empty($check)){
                return $this->json(['status'=>0,'msg'=>'商家不存在']);
            }

            $res = Db::name('member')->where('aid',aid)->where('id',mid)->update([$field => $setBid]);
            if($res){
                return $this->json(['status'=>1,'msg'=>'设置成功']);
            }
            return $this->json(['status'=>0,'msg'=>'设置失败']);
        }
    }

	protected function customRegister($formdata=[],$formid=0,$bid){
        if(getcustom('business_apply_form')){
            $form = Db::name('business_apply_form')->where('aid',aid)->find();
            if(empty($form) || empty($form['content'])){
                return ['status'=>1,'msg'=>''];
            }
            if($form['id']!=$formid){
                return ['status'=>0,'msg'=>'表单数据有误！'.$formid];
            }
            $formheader = [];
            if(empty($formdata)) $formdata = [];
            $data = [];
            $formcontent = json_decode($form['content'],true);
            foreach($formcontent as $k=>$v){
                $value = $formdata['form'.$k];
                if(is_array($value)){
                    $value = implode(',',$value);
                }
                if($v['key']=='switch'){
                    if($value){
                        $value = '是';
                    }else{
                        $value = '否';
                    }
                }
                $data['form'.$k] = strval($value);
                if($v['val3']==1 && $data['form'.$k]===''){
                    return ['status'=>0,'msg'=>$v['val1'].' 必填'.$data['form'.$k]];
                }
                if($v['key'] == 'usercard' && !checkIdCard($value)){
                    return ['status'=>0, 'msg'=>'请输入正确的身份证号'];
                }
                
            }
			$form_record = Db::name("business_apply_form_record")->where('aid', aid)->where('bid',$bid)
                ->find();
			
            if($data){				
                $data['aid'] = aid;
                $data['formid'] = $formid;
                $data['content'] = $form['content'];                
                $data['bid'] = $bid;
				if($form_record){
					Db::name('business_apply_form_record')->where('aid',aid)->where('id',$form_record['id'])->update($data);
					$recordid = $form_record['id'];
				}else{
					$data['createtime'] = time();
					$recordid = Db::name('business_apply_form_record')->insertGetId($data);
				}
                
                return ['status'=>1,'msg'=>'自定义表单保存成功','recordid'=>$recordid];
            }else{
                return ['status'=>1,'msg'=>''];
            }
        }
        return ['status'=>1,'msg'=>''];
    }

    //查询商家信息
    public function getBusiness(){
        if(getcustom('commission_transfer_business_money')){
            $id = input('param.id/d');
            $business = Db::name('business')->field('id,name,logo')->where('aid',aid)->where('id',$id)->find();
            if(empty($business)){
                return $this->json(['status'=>0,'msg'=>'商家不存在']);
            }
            return $this->json(['status'=>1,'msg'=>'','data'=>$business]);
        }
    }


    public function applyexpert(){
        if(getcustom('business_expert')){
            //申请商户达人
            $this->checklogin();

            $set = Db::name('business_expert_sysset')->where('aid',aid)->find();
            if(!$set || $set['status'] == 0 ) return $this->json(['status'=>0,'msg'=>'未开启此功能']);
            if($set['applystatus'] == 0) return $this->json(['status'=>0,'msg'=>'未开启申请达人功能']);

            //查询绑定商户的限制
            if($set['applylimit']>0){
                $countnum = Db::name('business_expert')->where('mid',mid)->where('status','>=',0)->where('aid',aid)->count('id');
                if($countnum>=$set['applylimit']){
                    return $this->json(['status'=>0,'msg'=>'申请数量已达'.$set['applylimit'].'个上限']); 
                }
            }
            if(request()->isPost()){
                $formdata = input('post.info/a');
                if(!$formdata['bid']){
                    return $this->json(['status'=>0,'msg'=>'请选择商户']); 
                }
                $bid = $formdata['bid'];

                $expert = Db::name('business_expert')->where('bid',$bid)->where('status','>=',0)->where('aid',aid)->field('id,mid')->find();
                if($expert){
                    if($expert['mid'] != mid){
                        return $this->json(['status'=>0,'msg'=>'此商户已有其他人申请']);
                    }else{
                        return $this->json(['status'=>0,'msg'=>'你已申请过此商户']);
                    }
                }

                if(!$formdata['linkman']) return $this->json(['status'=>0,'msg'=>'请填写联系人姓名']); 

                if(!checkTel(aid,$formdata['linktel'])){
                    return $this->json(['status'=>0, 'msg'=>'请输入正确的联系人手机号']);
                }
                if(!$formdata['reason']) return $this->json(['status'=>0,'msg'=>'请填写申请理由']);
                if($set['showpics'] && !$formdata['pics'])  return $this->json(['status'=>0,'msg'=>'请上传相同图片']);

                $info = [];
                $info['aid'] = aid;
                $info['mid'] = mid;
                $info['bid'] = $formdata['bid'];
                $info['linkman']= $formdata['linkman'];
                $info['linktel']= $formdata['linktel'];
                $info['reason'] = $formdata['reason'];
                $info['pics']   = $formdata['pics']??'';
                $info['status'] = 0;
                if($formdata['id']){
                    Db::name('business_expert')->where('aid',aid)->where('mid',mid)->where('id',$formdata['id'])->update($info);
                }else{
                    $info['createtime'] = time();
                    $bid = Db::name('business_expert')->insertGetId($info);
                }
                return $this->json(['status'=>1,'msg'=>'提交成功，请耐心等待审核']);
            }

            $rdata = [];
            $rdata['status']= 1;
            $rdata['title'] = '申请入驻';
            $rdata['set']   = $set;
            $rdata['info']  = $info ??'';
            return $this->json($rdata);
        }
    }

    public function expertlist(){
        if(getcustom('business_expert')){
        	$this->checklogin();
            //达人列表
            $st = input('param.st');
            if(!input('?param.st') || $st === ''){
                $st = 'all';
            }
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if($st == 'all'){

            }elseif($st == '0'){
                $where[] = ['status','=',0];
            }elseif($st == '1'){
                $where[] = ['status','=',1];
            }elseif($st == '-1'){
                $where[] = ['status','=',-1];
            }elseif($st == '-2'){
                $where[] = ['status','=',-2];
            }

            $pernum = 10;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $datalist = Db::name('business_expert')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = array();
            foreach($datalist as $key=>&$v){

                if($v['bid']!=0){
                    $business = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->field('id,name,logo')->find();
                    if(!$business) $business = ['id'=>$v['bid'],'name'=>'商家已失效'];
                } else {
                    $business = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
                }
                $v['business'] = $business;
                $v['pics'] = $v['pics']?explode(",",$v['pics']):'';

                $v['premonthinfo'] = '';
                if($v['premonth']){
                	$premonth = date('Y.m月',$v['premonth']);
                	$v['premonthinfo'] = $premonth.'：营业额'.$v['premonth_totalprice'].'元 成交数量：'.$v['premonth_num'];
                }
                $v['checktime'] = $v['checktime']?date("Y-m-d H:i",$v['checktime']):'';
                $v['expiredtime'] = $v['expiredtime']?date("Y-m-d H:i",$v['expiredtime']):'';
                $v['createtime'] = $v['createtime']?date("Y-m-d H:i",$v['createtime']):'';
            }
            unset($v);
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['datalist'] = $datalist;
            return $this->json($rdata);
        }
    }
}
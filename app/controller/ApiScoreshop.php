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
class ApiScoreshop extends ApiCommon
{
	public function initialize(){
		parent::initialize();
		$set = Db::name('scoreshop_sysset')->where('aid',aid)->find();
		$gettj = explode(',',$set['gettj']);
		if(!in_array('-1',$gettj)){ //不是所有人
			$this->checklogin();
			if(!in_array($this->member['levelid'],$gettj)){
				echojson(['status'=>-4,'msg'=>$set['gettjtip'],'url'=>$set['gettjurl']]);
			}
		}
	}
	public function index(){
		$clist = Db::name('scoreshop_category')->where('aid',aid)->where('status',1)->where('pid',0)->order('sort desc,id')->select()->toArray();
		$score = $this->member ? $this->member['score'] : 0;
		$bid = input('param.bid');
		if($bid > 0 && $this->member){
			$memberscore = Db::name('business_memberscore')->where('aid',aid)->where('bid',$bid)->where('mid',mid)->find();
			$score = $memberscore['score'] ?? 0;
		}

        $score_weishu = $this->score_weishu;
        $score = dd_money_format($score,$score_weishu);

        $background = PRE_URL.'/static/img/scoreshop_top.png';
		if(getcustom('scoreshop_background')){
            $backgrounds = Db::name('scoreshop_sysset')->where('aid',aid)->value('background_pic');
            if($backgrounds) $background = $backgrounds;
        }
        $adset_show = 0;
        $adpid = '';
        if(getcustom('ad_adset')){
        	$scoreshop_sysset = Db::name('scoreshop_sysset')->where('aid',aid)->field('adset_show,adpid')->find();
        	$adset_show = $scoreshop_sysset['adset_show']?:0;
        	$adpid = $scoreshop_sysset['adpid'];
        }

		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['bgurl'] = $background;
		$rdata['score'] = $score;
		$rdata['adset_show'] = $adset_show;
		$rdata['adpid'] = $adpid;
		return $this->json($rdata);
	}
	public function category(){
		$datalist = Db::name('scoreshop_category')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
		$rdata = [];
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	public function prolist(){
		//分类
		if(input('param.cid')){
			$clist = Db::name('scoreshop_category')->where('aid',aid)->where('pid',input('param.cid/d'))->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}else{
			$clist = Db::name('scoreshop_category')->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}
		return $this->json(['clist'=>$clist]);
	}
	public function getprolist(){
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['status','=',1];
		$where[] = ['ischecked','=',1];
		if(input('param.bid')){
			$where[] = ['bid','=',input('param.bid/d')];
		}else{
			$business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
			if(!$business_sysset || $business_sysset['status']==0 || $business_sysset['product_isshow']==0){
				$where[] = ['bid','=',0];
			}
		}
		if(getcustom('scoreshop_product_bind_mendian')){
			$mendian_id = input('param.mendian_id/d',0);
            if($mendian_id>0){
                $where[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
            }
        }

		//分类 
		$searchcid = input('param.cid');
		if(input('param.cid')){
			$cid = input('param.cid/d');
			//子分类
			$clist = Db::name('scoreshop_category')->where('aid',aid)->where('pid',$cid)->select()->toArray();
			if($clist){
				$cateArr = [$cid];
				foreach($clist as $c){
					$cateArr[] = $c['id'];
				}
				$where[] = ['cid','in',$cateArr];
			}else{
				$where[] = ['cid','=',$cid];
				$pid = Db::name('scoreshop_category')->where('aid',aid)->where('id',$cid)->value('pid');
				if($pid){
					$searchcid = $pid;
					$clist = Db::name('scoreshop_category')->where('aid',aid)->where('pid',$pid)->select()->toArray();
				}
			}
		}
		if(input('param.keyword')){
			$where[] = ['name','like','%'.input('param.keyword').'%'];
		}

		$where2 = "find_in_set('-1',showtj)";
		if($this->member){
			$where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
			if($this->member['subscribe']==1){
				$where2 .= " or find_in_set('0',showtj)";
			}
		}
		$where[] = Db::raw($where2);
		
		if(input('param.field') && input('param.order')){
			$order = input('param.field').' '.input('param.order').',sort,id desc';
		}else{
			$order = 'sort desc,id desc';
		}

		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$field = "id,pic,name,sales,score_price,money_price,sell_price,sellpoint,fuwupoint,sales,lvprice,lvprice_data,freighttype";
        if(getcustom('plug_tengrui')) {
            $field .= ',house_status,is_rzh,relation_type,group_status,group_ids';
        }
		$datalist = Db::name('scoreshop_product')->field($field)->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
        $score_weishu = $this->score_weishu;
		if(!$datalist){
			$datalist = array();
		} else {
		    foreach ($datalist as $k=>&$product) {
				if(getcustom('plug_tengrui')) {
	                $tr_check = new \app\common\TengRuiCheck();
	                //判断是否是否符合会员认证、会员关系、一户，不符合则直接去掉
	                $check_score = $tr_check->check_score($this->member,$product,1);
	                if($check_score && $check_score['status'] == 0 ){
	                    unset($datalist[$k]);
	                }else{
	                	$product = $this->formatScoreProduct($product);
	                }
	        	}else{
					$product = $this->formatScoreProduct($product);
	        	}
                $product['score_price'] = dd_money_format($product['score_price'],$score_weishu);

                $canaddcart = true;
                if($product['freighttype']==3 || $product['freighttype']==4){ 
                	//虚拟商品不能加入购物车
					$canaddcart = false;
				}
				$product['canaddcart'] = $canaddcart;
            }
            if(getcustom('plug_tengrui')) {
	            $len = count($datalist);
	            if($len<20 && $len>0){
	                //重置索引,防止上方去掉的数据产生空缺
	                $datalist=array_values($datalist);
	            }
	        }
        }
		return $this->json(['status'=>1,'data'=>$datalist]);
		$count = Db::name('scoreshop_product')->where($where)->count();

		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['searchcid'] = $searchcid;
		$rdata['pernum'] = $pernum;
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	public function product(){
		//if(!$this->member){
		//	return $this->json(['status'=>-1,'msg'=>'请先登录']);
		//}
		$proid = input('param.id/d');
		
        if(getcustom('scoreshop_product_bind_mendian')){
        	//判断是否需要经纬度【如果门店模式，显示最近门店，经纬度必须】
        	$needlocation = false;
            $latitude = input('param.latitude/f','');
            $longitude = input('param.longitude/f','');
            $mendian_id = input('param.mendian_id/d','');
            if((empty($latitude) || empty($longitude))){
                $needlocation = true;
            }
        }

		$where = [];
		$where[] = ['id','=',$proid];
		$where[] = ['aid','=',aid];
		$product = Db::name('scoreshop_product')->where($where)->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'商品不存在']);
		if($product['status']==0) return $this->json(['status'=>0,'msg'=>'商品已下架']);
		if($product['ischecked']==0) return $this->json(['status'=>0,'msg'=>'商品未审核']);
        $score_weishu = $this->score_weishu;
        $product['score_price'] = dd_money_format($product['score_price'],$score_weishu);

		//显示条件
		if($product['showtj'] == '' && $product['bid']!=0) $product['showtj'] = '-1';
        $levelids = explode(',',$product['showtj']);
        //限制等级
        if(!in_array('-1',$levelids)){
            $this->checklogin();
            $showtj1 = false;
            $showtj2 = false;
            if(in_array($this->member['levelid'], $levelids)) {
                $showtj1 = true;
            }
            if(in_array('0',$levelids) && $this->member['subscribe']==1){
                $showtj2 = true;
            }
            if(!$showtj1 && !$showtj2){
                return $this->json(['status'=>0,'msg'=>'商品状态不可见']);
            }
        }

		if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户
            $tr_check = new \app\common\TengRuiCheck();
            $check_score = $tr_check->check_score($this->member,$product,1);
            if($check_score && $check_score['status'] == 0 ){
                return $this->json(['status'=>$check_score['status'],'msg'=>$check_score['msg']]);
            }
            $tr_roomId = $check_score['tr_roomId'];
        }

		if(!$product['pics']) $product['pics'] = $product['pic'];
		$product['pics'] = explode(',',$product['pics']);
		if($product['fuwupoint']){
			$product['fuwupoint'] = explode(' ',preg_replace("/\s+/",' ',str_replace('　',' ',trim($product['fuwupoint']))));
		}
        $product = $this->formatScoreProduct($product);

        //是否收藏
		$rs = Db::name('member_favorite')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('type','scoreshop')->find();
		if($rs){
			$isfavorite = true;
		}else{
			$isfavorite = false;
		}
		if($this->member){
			//添加浏览历史
			$rs = Db::name('member_history')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('type','scoreshop')->find();
			if($rs){
				Db::name('member_history')->where('id',$rs['id'])->update(['createtime'=>time()]);
			}else{
				Db::name('member_history')->insert(['aid'=>aid,'mid'=>mid,'proid'=>$proid,'type'=>'scoreshop','createtime'=>time()]);
			}
		}
		
        $sysset = Db::name('admin_set')->where('aid',aid)->field('name,logo,desc,fxjiesuantype,tel,kfurl,gzts,ddbb')->find();
        $shopset = Db::name('scoreshop_sysset')->where('aid',aid)->field('showjd,showcommission')->find();
		
		if($product['bid']!=0){
			$business = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->field('id,name,logo,desc,tel,address,sales,kfurl')->find();
		}else{
			$business = $sysset;
		}

        //预计佣金
        $commission = 0;
        $product['commission_desc'] = '元';
        if($this->member && $shopset['showcommission']==1 && $product['commissionset']!=-1){
            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
            if($userlevel['can_agent']!=0){
                if($product['commissionset']==1){//按比例
                    $commissiondata = json_decode($product['commissiondata1'],true);
                    if($commissiondata){
                        $commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['money_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                    }
                }elseif($product['commissionset']==2){//按固定金额
                    $commissiondata = json_decode($product['commissiondata2'],true);
                    if($commissiondata){
                        $commission = $commissiondata[$userlevel['id']]['commission1'];
                    }
                }elseif($product['commissionset']==3) {//提成是积分
                    $commissiondata = json_decode($product['commissiondata3'],true);
                    if($commissiondata){
                        $commission = $commissiondata[$userlevel['id']]['commission1'];
                    }
                    $product['commission_desc'] = t('积分');
                }elseif($product['commissionset']==4 && $product['lvprice']==1){//按价格差
                    $lvprice_data = json_decode($product['lvprice_data'],true);
                    $commission = array_shift($lvprice_data)['money_price'] - $product['money_price'];
                    if($commission < 0) $commission = 0;
                }elseif($product['commissionset']==5){//比例+积分
                    $commissiondata = json_decode($product['commissiondata5'],true);
                    if($commissiondata){
                        $commission = $commissiondata[$userlevel['id']]['commission1']['money'] * ($product['money_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                    }
                    $commissiondata = json_decode($product['commissiondata5'],true);
                    if($commissiondata){
                        $commission_score = $commissiondata[$userlevel['id']]['commission1']['score'];
                    }
                    $product['commission_score_desc'] = t('积分');
                }elseif($product['commissionset']==0){//按会员等级
                    //fxjiesuantype 0按商品价格,1按成交价格,2按销售利润
                    if($userlevel['commissiontype']==1){ //固定金额按单
                        $commission = $userlevel['commission1'];
                    }else{
                        $commission = $userlevel['commission1'] * ($product['money_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                    }
                }
            }
        }
        $product['commission'] = round($commission*100)/100;
        $product['commission_score'] = $commission_score ? $commission_score : 0;
        unset($product['cost_price']);

		$product['detail'] = \app\common\System::initpagecontent($product['detail'],aid,mid,platform);
        if(getcustom('form_jingmo_auth')){
            $pagecontent = json_decode($product['detail'],true);
            if(platform == 'wx' || platform == 'mp'){
                if(!$this->member) {
                    foreach ($pagecontent as $k => $v) {
                        if ($v['temp'] == 'form') {
                            //is_jingmo 静默登录注册 1:开启 0：关闭
                            if (isset($v['params']['is_jingmo']) && $v['params']['is_jingmo'] == 1) {
                                return $this->json(['status' => -1, 'msg' => '请先登录', 'authlogin' => 2], 1);
                            }
                        }
                    }
                }
            }
        }
		if($product['guigeset'] == 1){
			$gglist = Db::name('scoreshop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
			if($product['lvprice']==1) $gglist = $this->formatscoreshopgglist($gglist);
			$guigelist = array();
			foreach($gglist as $k=>$v){
				$guigelist[$v['ks']] = $v;
			}
			$guigedata = json_decode($product['guigedata'],true);
		}else{
			$guigelist = [
				['id'=>'','name'=>'','pic'=>'','market_price'=>$product['sell_price'],'cost_price'=>$product['cost_price'],'money_price'=>$product['money_price'],'score_price'=>$product['score_price'],'weight'=>$product['weight'],'stock'=>$product['stock'],'ks'=>'0']
			];
			if(getcustom('supply_yongsheng')){
            	if($product['issource'] && $product['source'] == 'supply_yongsheng'){
            		$guigelist[0]['source_code'] = $product['source_code'];
            	}
            }
			$guigedata = json_decode('[{"k":0,"title":"规格","items":[{"k":0,"title":"默认规格"}]}]',true);
		}
		if(getcustom('supply_yongsheng')){
            if($product['issource'] && $product['source'] == 'supply_yongsheng'){
            	if($product['guigeset'] != 1){
            		$gglist = $guigelist;
            	}
                $haveyspro = true;//是否有永盛商品
                //查询永盛商品详情
                $checkproductguige = \app\custom\SupplyYongsheng::checkproductguige(aid,$product['bid'],$product,$gglist);
                if(!$checkproductguige || $checkproductguige['status'] != 1){
                    $msg = $checkproductguige && $checkproductguige['msg']?$checkproductguige['msg']:$product['name'].'信息错误';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                $product = $checkproductguige['product'];
                $gglist = $checkproductguige['gglist'];
                if(!$gglist){
                	return $this->json(['status'=>0,'msg'=>'该商品暂不能购买']);
                }
                if($product['guigeset'] == 1){
					$guigelist = array();
					foreach($gglist as $k=>$v){
						$guigelist[$v['ks']] = $v;
					}
					$guigedata = json_decode($product['guigedata'],true);
				}
            }
        }

        $product['show_lvprice']==0;
		if(getcustom('scoreshop_lvprice_show')){
		    $lvprice_show = Db::name('scoreshop_sysset')->where('aid',aid)->value('lvprice_show');
            if($product['lvprice']==1 && $lvprice_show==1){
                $lvprice_data = json_decode($product['lvprice_data'],true);
                $member_levels = Db::name('member_level')->where('aid',aid)->order('id desc')->column('name','id');
                $new_lvprice_data = [];
                $default_levelid = Db::name('member_level')->where('aid',aid)->where('isdefault',1)->value('id');
                foreach ($member_levels as $level_id=>$level_name){
                    $i++;
                    $arr = [];
                    $arr['money'] = $lvprice_data[$level_id]['money'];
                    $arr['score'] = $lvprice_data[$level_id]['score'];
                    $arr['name'] = $level_name;
                    if($this->member['levelid']==$level_id || (!$this->member && $level_id==$default_levelid)){
                        $arr['is_select'] = 1;
                    }else{
                        $arr['is_select'] = 0;
                    }
                    $new_lvprice_data[] = $arr;
                }
                $product['new_lvprice_data'] = $new_lvprice_data;
                $product['show_lvprice'] = 1;
            }
        }
        $probid = $product['bid'];
        if(getcustom('scoreshop_product_bind_mendian')){
            $freightlist = Db::name('freight')->where('aid',aid)->where('bid',$probid)->where('status',1)->where('pstype',1)->column('id','id');
            if($freightlist){
                //该商品是否可自提
                if($product['freighttype']==1){
                    $product['can_ziti'] = 1;
                }else if($product['freighttype']==0){
                    $freightdata = $product['freightdata']?explode(',',$product['freightdata']):[];
                    foreach ($freightdata as $freightid){
                        if(isset($freightlist[$freightid])){
                            $product['can_ziti'] = 1;
                            break;
                        }
                    }
                }

                if($product['can_ziti']==1){
                    //如果是全部门店，则取所有门店中最近的一个，如果是部分门店,则取售卖门店中最近的一个
                    $mdwhere = [];
                    $mdwhere[] = ['aid','=',aid];
					if(getcustom('business_platform_auth')){
						if($product['bid']===0){
							$busids = Db::name('business')->where('aid',aid)->where('isplatform_auth',1)->where('status',1)->column('id');		
							array_push($busids,$product['bid']);
							$mdwhere[] = ['bid','in',$busids];
						}else{
							$mdwhere[] = ['bid','=',$product['bid']];
						}
					}else{
						$mdwhere[] = ['bid','=',$probid];
					}
                    $mdwhere[] = ['status','=',1];
                    $bindMendianIds = $product['bind_mendian_ids'] ? explode(',', $product['bind_mendian_ids']) : [];
                    if($bindMendianIds && !in_array('-1',$bindMendianIds)){
                        $mdwhere[] = ['id','in',explode(',',$product['bind_mendian_ids'])];
                    }
                    $mdfield = '*';
                    if($longitude && $latitude){
                       $mdfield .= ",round(6378.138*2*asin(sqrt(pow(sin( ({$latitude}*pi()/180-latitude*pi()/180)/2),2)+cos({$latitude}*pi()/180)*cos(latitude*pi()/180)* pow(sin( ({$longitude}*pi()/180-longitude*pi()/180)/2),2)))*1000) AS distance";
                        $mdorder = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) asc");
                    }else{
                        $mdfield .= ",0 distance";
                        $mdorder = 'sort desc,id asc';
                    }
                    $mendian = [];
                    $bindmendianIds = [];
                    $bindmendianlist = Db::name('mendian')->field($mdfield)->where($mdwhere)->orderRaw($mdorder)->select()->toArray();
                    if(empty($bindmendianlist)) $bindmendianlist = [];
                    foreach ($bindmendianlist as $mdkey=>$bindmendian){
                        if(!$bindmendian['distance']){
                            $bindmendianlist[$mdkey]['distance'] = '';
                        }elseif($bindmendian['distance']<1000){
                            $bindmendianlist[$mdkey]['distance'] = round($bindmendian['distance'],2).'m';
                        }else{
                            $bindmendianlist[$mdkey]['distance'] = round($bindmendian['distance']/1000,1).'km';
                        }
                        if($mendian_id && $bindmendian['id']==$mendian_id){
                            $mendian = $bindmendianlist[$mdkey];
                        }
                        $bindmendianIds[] = $bindmendian['id'];
                    }
				
                    if($mendian && empty($mendian['pic'])){
                        $mendian['pic'] = PRE_URL.'/static/img/location/mendian.png';
                    }
		
                }
                if((empty($mendian_id) || empty($mendian)) && $bindmendianlist){
                    $mendian = $bindmendianlist[0];
                }
                if($mendian){
					if(getcustom('mendian_upgrade')){
						$admin = Db::name('admin')->where('id',aid)->field('mendian_upgrade_status')->find();
						if($admin['mendian_upgrade_status']==1 && $mendian['mid']){
							$member = Db::name('member')->field('headimg')->where('id',$mendian['mid'])->find();
							$mendian['pic'] = $member['headimg'];
						}
						
					}
                    $mendian['address'] = $mendian['address']??'';
                    $mendian['area'] = $mendian['area']??'';
                }
            }
        }

		$rdata = [];
		$rdata['product'] = $product;
		$rdata['myscore'] = $this->member['score'];
		$rdata['sysset'] = $sysset;
		$rdata['shopset'] = $shopset;
		$rdata['business'] = $business;
		$rdata['isfavorite'] = $isfavorite;
		$rdata['cartnum'] = Db::name('scoreshop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
		$rdata['guigelist'] = $guigelist ?? [];
		$rdata['guigedata'] = $guigedata ?? [];

		if(getcustom('scoreshop_product_bind_mendian')){
			$rdata['mendian'] = $mendian??'';
	        $rdata['bindmendianids'] = $bindmendianIds??[];
	        $rdata['needlocation'] = $needlocation?true:false;
	    }

        $rdata['scoreshop_everytime_buymin'] = false;
        if(getcustom('scoreshop_everytime_buymin')){
            $rdata['scoreshop_everytime_buymin'] = true;
        }

		return $this->json($rdata);
	}
	public function formatscoreshopgglist($gglist){
		if(!$this->member) return $gglist;
		foreach($gglist as $k=>$v){
			$lvprice_data = json_decode($v['lvprice_data'],true);
			if($lvprice_data && isset($lvprice_data[$this->member['levelid']])){
			    $gglist[$k]['money_price'] = $lvprice_data[$this->member['levelid']]['money'];
			    $gglist[$k]['score_price'] = $lvprice_data[$this->member['levelid']]['score'];
			}
		}
		return $gglist;
	}
	//购物车 
	public function cart(){
		$this->checklogin();
		$gwcdata = [];
		if(input('param.bid')){
			$cartlist = Db::name('scoreshop_cart')->field('id,bid,proid,ggid,num')->where('aid',aid)->where('mid',mid)->where('bid',input('param.bid'))->order('createtime desc')->select()->toArray();
		}else{
			$cartlist = Db::name('scoreshop_cart')->field('id,bid,proid,ggid,num')->where('aid',aid)->where('mid',mid)->order('createtime desc')->select()->toArray();
		}
		if(!$cartlist) $cartlist = [];
		
		if(input('param.isnew') == 1){ //新的方式 按商家归类
			$newcartlist = [];
			foreach($cartlist as $k=>$gwc){
				if($newcartlist[$gwc['bid']]){
					$newcartlist[$gwc['bid']][] = $gwc;
				}else{
					$newcartlist[$gwc['bid']] = [$gwc];
				}
			}
			foreach($newcartlist as $bid=>$gwclist){
				if($bid == 0){
					$business = [
						'id'=>$this->sysset['id'],
						'name'=>$this->sysset['name'],
						'logo'=>$this->sysset['logo'],
						'tel'=>$this->sysset['tel']
					];
				}else{
					$business = Db::name('business')->where('aid',aid)->where('id',$bid)->field('id,name,logo,tel')->find();
				}
				$prolist = [];
				foreach($gwclist as $gwc){
					$product = Db::name('scoreshop_product')->where('aid',aid)->where('status',1)->where('id',$gwc['proid'])->find();
					if(!$product){
						Db::name('scoreshop_cart')->where('aid',aid)->where('proid',$gwc['proid'])->delete();continue;
					}
					$product = $this->formatScoreProduct($product);

					if($product['guigeset'] == 1){
						if(!$gwc['ggid']){
							Db::name('scoreshop_cart')->where('id',$gwc['id'])->delete();continue;
						}
						$guige = Db::name('scoreshop_guige')->where('aid',aid)->where('proid',$gwc['proid'])->where('id',$gwc['ggid'])->find();
						if(!$guige){
							Db::name('scoreshop_cart')->where('id',$gwc['id'])->delete();continue;
						}
						if($product['lvprice']==1){
							$lvprice_data = json_decode($guige['lvprice_data'],true);
							if($lvprice_data && isset($lvprice_data[$this->member['levelid']])){
								$guige['money_price'] = $lvprice_data[$this->member['levelid']]['money'];
								$guige['score_price'] = $lvprice_data[$this->member['levelid']]['score'];
							}
						}
						$product['money_price'] = $guige['money_price'];
						$product['score_price'] = $guige['score_price'];
						$product['ggname'] = $guige['name'];
						$product['ggpic'] = $guige['pic'];
						$product['stock'] = $guige['stock'];
					}
					$cartlist[$k]['product'] = $product;
					$tmpitem = ['id'=>$gwc['id'],'checked'=>true,'product'=>$product,'num'=>$gwc['num'],'ggid'=>$gwc['ggid']];
					$prolist[] = $tmpitem;
				}
				$newcartlist[$bid] = ['bid'=>$bid,'checked'=>true,'business'=>$business,'prolist'=>$prolist];
			}
			$cartlist = array_values($newcartlist);
		}else{
			foreach($cartlist as $k=>$gwc){
				$product = Db::name('scoreshop_product')->where('aid',aid)->where('status',1)->where('id',$gwc['proid'])->find();
				if(!$product){
					Db::name('scoreshop_cart')->where('aid',aid)->where('proid',$gwc['proid'])->delete();continue;
				}
				$product = $this->formatScoreProduct($product);

				if($product['guigeset'] == 1){
					if(!$gwc['ggid']){
						Db::name('scoreshop_cart')->where('id',$gwc['id'])->delete();continue;
					}
					$guige = Db::name('scoreshop_guige')->where('aid',aid)->where('proid',$gwc['proid'])->where('id',$gwc['ggid'])->find();
					if(!$guige){
						Db::name('scoreshop_cart')->where('id',$gwc['id'])->delete();continue;
					}
					if($product['lvprice']==1){
						$lvprice_data = json_decode($guige['lvprice_data'],true);
						if($lvprice_data && isset($lvprice_data[$this->member['levelid']])){
							$guige['money_price'] = $lvprice_data[$this->member['levelid']]['money'];
							$guige['score_price'] = $lvprice_data[$this->member['levelid']]['score'];
						}
					}
					$product['money_price'] = $guige['money_price'];
					$product['score_price'] = $guige['score_price'];
					$product['ggname'] = $guige['name'];
				}
				$cartlist[$k]['product'] = $product;
			}
		}

		$rdata = [];
		$rdata['status'] = 1;
		$rdata['cartlist'] = $cartlist;
		return $this->json($rdata);
	}
	public function addcart(){
		$this->checklogin();
		$post = input('post.');
		$oldnum = 0;
		$proid = intval($post['proid']);
		$ggid = $post['ggid'] ? intval($post['ggid']) : null;
		$num = intval($post['num']);
		$gwc = Db::name('scoreshop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('ggid',$ggid)->find();
		if($gwc) $oldnum = $gwc['num'];

		$product = Db::name('scoreshop_product')->where('aid',aid)->where('status',1)->where('id',$proid)->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
		if($product['freighttype']==3 || $product['freighttype']==4) return $this->json(['status'=>0,'msg'=>'虚拟商品不能加入购物车']);
		if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户
            $tr_check = new \app\common\TengRuiCheck();
            $check_score = $tr_check->check_score($this->member,$product);
            if($check_score && $check_score['status'] == 0){
                return $this->json(['status'=>$check_score['status'],'msg'=>$check_score['msg']]);
            }
            $tr_roomId = $check_score['tr_roomId'];
        }
		if($oldnum + $num <=0){
			Db::name('scoreshop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('ggid',$ggid)->update(['num'=>1]);
			$cartnum = Db::name('scoreshop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
			return $this->json(['status'=>1,'msg'=>'加入购物车成功','cartnum'=>$cartnum]);
		}
		if($gwc){
			Db::name('scoreshop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('ggid',$ggid)->inc('num',$num)->update();
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['bid'] = $product['bid'];
			$data['mid'] = mid;
			$data['proid'] = $proid;
			$data['ggid'] = $ggid;
			$data['num'] = $num;
			$data['createtime'] = time();
			Db::name('scoreshop_cart')->insert($data);
		}
		$cartnum = Db::name('scoreshop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
		return $this->json(['status'=>1,'msg'=>'加入购物车成功','cartnum'=>$cartnum]);
	}
	public function cartChangenum(){
		$this->checklogin();
		$id = input('post.id/d');
		$num = input('post.num/d');
		if($num < 1) $num = 1;
		Db::name('scoreshop_cart')->where('id',$id)->where('mid',mid)->update(['num'=>$num]);
		return $this->json(['status'=>1,'msg'=>'修改成功']);
	}
	public function cartdelete(){
		$this->checklogin();
		$id = input('post.id/d');
		if(!$id){
			$bid = input('post.bid/d');
			Db::name('scoreshop_cart')->where('bid',$bid)->where('mid',mid)->delete();
			return $this->json(['status'=>1,'msg'=>'删除成功']);
		}
		Db::name('scoreshop_cart')->where('id',$id)->where('mid',mid)->delete();
		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}
	public function buy(){
		$this->checklogin();

		$prodata = explode('-',input('param.prodata'));
		
		$product = Db::name('scoreshop_product')->where('aid',aid)->where('status',1)->where('id',explode(',',$prodata[0])[0])->find();
		$bid = $product['bid'];

		$freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);

		$fids = [];
		foreach($freightList as $v){
			$fids[] = $v['id'];
		}
		$allbuydata = [];
		$totalmoney = 0;
		$totalscore = 0;
		$totalweight = 0;
		$totalnum = 0;
		$prolist = [];
		$autofahuo = 0;
		$bids = [];
        $score_weishu = $this->score_weishu;
        $contact_require = 0;
        if(getcustom('supply_yongsheng')){
			$haveyspro = false;//是否有永盛商品
		}
		foreach($prodata as $key=>$gwc){
			$gwcArr = explode(',',$gwc);
			$proid = intval($gwcArr[0]);
			$num = intval($gwcArr[1]);
			$ggid = $gwcArr[2] && $gwcArr[2] != 'null' ? intval($gwcArr[2]) : null;
			if($num < 1) $num = 1;
			$product = Db::name('scoreshop_product')->where('aid',aid)->where('status',1)->where('id',$proid)->find();
            $product['score_price'] = dd_money_format($product['score_price'],$score_weishu);
			if(!$product){
				return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
			}

			if(($product['freighttype'] == 3||$product['freighttype']==4) && $product['contact_require'] == 1){
				$contact_require = 1;
			}

			if($product['stock'] < $num){
				return $this->json(['status'=>0,'msg'=>$product['name'].'库存不足']);
			}
			if($product['gettj'] == '' && $product['bid']!=0) $product['gettj'] = '-1';
			$gettj = explode(',',$product['gettj']);
			if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj) && (!in_array('0',$gettj) || $this->member['subscribe']!=1)){ //不是所有人
				if(!$product['gettjtip']) $product['gettjtip'] = '没有权限兑换该商品';
				return $this->json(['status'=>0,'msg'=>$product['gettjtip'],'url'=>$product['gettjurl']]);
			}

            //是否达到限制兑换数
            if($product['buymax'] > 0){
                $buynum = $num + Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('status','in','0,1,2,3')->sum('num');
                if($buynum > $product['buymax']){
                    return $this->json(['status'=>0,'msg'=>'每人限兑'.$product['buymax'].'次']);
                }
            }
            //是否达到每天限制兑换数
            if($product['everyday_buymax'] > 0){
                $today_start = strtotime(date('Y-m-d').' 00:00:01');
                $today_end = strtotime(date('Y-m-d').' 23:59:59');
                $everydaybuynum = $num + Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('status','in','0,1,2,3')->where('createtime','between',[$today_start,$today_end])->sum('num');
                if($everydaybuynum > $product['everyday_buymax']){
                    return $this->json(['status'=>0,'msg'=>'每人每天限兑'.$product['everyday_buymax'].'件']);
                }
            }
            if(getcustom('plug_tengrui')) {
	            //判断是否是否符合会员认证、会员关系、一户
	            $tr_check = new \app\common\TengRuiCheck();
	            $check_score = $tr_check->check_score($this->member,$product);
	            if($check_score && $check_score['status'] == 0){
	                return $this->json(['status'=>$check_score['status'],'msg'=>$check_score['msg']]);
	            }
	            $tr_roomId = $check_score['tr_roomId'];
	        }
            $product = $this->formatScoreProduct($product);
			
			if($product['guigeset'] == 1){
				if(!$ggid) return $this->json(['status'=>0,'msg'=>'请选择规格']);
				$guige = Db::name('scoreshop_guige')->where('aid',aid)->where('proid',$proid)->where('id',$ggid)->find();
				if(!$guige) return $this->json(['status'=>0,'msg'=>'规格不存在']);
				if($guige['stock'] < $num){
					return $this->json(['status'=>0,'msg'=>$product['name'].$guige['name'].'库存不足']);
				}
				if($product['lvprice']==1){
					$lvprice_data = json_decode($guige['lvprice_data'],true);
					if($lvprice_data && isset($lvprice_data[$this->member['levelid']])){
						$guige['money_price'] = $lvprice_data[$this->member['levelid']]['money'];
						$guige['score_price'] = $lvprice_data[$this->member['levelid']]['score'];
					}
				}
				$product['money_price'] = $guige['money_price'];
				$product['score_price'] = $guige['score_price'];
				$product['cost_price']  = $guige['cost_price'];
				$product['ggname'] = $guige['name'];
				$product['ggpic'] = $guige['pic'];
				$product['weight'] = $guige['weight'];
				$product['stock'] = $guige['stock'];
			}
			if(getcustom('supply_yongsheng')){
                if($product['issource'] && $product['source'] == 'supply_yongsheng'){
                    $haveyspro = true;//是否有永盛商品
                    //查询永盛商品详情
                    if(!$product['guigeset']){
                    	$checkguige = ['name'=>$product['ggname'],'source_code'=>$product['source_code']];
                    }else{
                    	$checkguige = $guige;
                    }
                    $checkproduct = \app\custom\SupplyYongsheng::checkproduct(aid,$product['bid'],$num,$product,$checkguige);
                    if(!$checkproduct || $checkproduct['status'] != 1){
                        $msg = $checkproduct && $checkproduct['msg']?$checkproduct['msg']:'['.$product['name'].'规格'.$guige['name'].'] '.'信息错误';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                }
            }

			$totalmoney += $product['money_price'] * $num;
			$totalscore += $product['score_price'] * $num;
			$totalweight += $product['weight'] * $num;
			$totalnum += $num;

			if($product['freighttype']==3 || $product['freighttype']==4) $autofahuo = $product['freighttype'];
		
			if($product['freighttype']==0){
				$fids = array_intersect($fids,explode(',',$product['freightdata']));
			}elseif($product['freighttype']==3 || $product['freighttype']==4){
				$autofahuo = $product['freighttype'];
			}else{
				$thisfreightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
				$thisfids = [];
				foreach($thisfreightList as $v){
					$thisfids[] = $v['id'];
				}
				$fids = array_intersect($fids,$thisfids);
			}
			$product['num'] = $num;
			if(!in_array($product['bid'],$bids)) $bids[] = $product['bid'];
			$prolist[] = $product;

			$groupBid = $product['bid'];
			if(getcustom('supply_yongsheng')){
            	//永盛商品单独分组下单
            	if($product['issource'] && $product['source'] == 'supply_yongsheng'){
                    $groupBid = $groupBid .'_supplyyongsheng_'.$product['sproid'];
                }
            }

			if(!$allbuydata[$groupBid]) $allbuydata[$groupBid] = [];
			if(!$allbuydata[$groupBid]['prodata']) $allbuydata[$groupBid]['prodata'] = [];
			$allbuydata[$groupBid]['prodata'][] = ['product'=>$product,'num'=>$num,'ggid'=>$ggid];
		}
		//if(count($bids) > 1) return $this->json(['status'=>0,'msg'=>'不同商家的商品请分别下单']);
		
		if($autofahuo>0 && count($prodata) > 1){
			return $this->json(['status'=>0,'msg'=>'虚拟商品请单独购买']);
		}
		
		$havetongcheng = 0;
		$needLocation = 0;
		foreach($allbuydata as $groupBid=>$buydata){
			$bidGroupArr = explode('_',$groupBid);
            $bid = $bidGroupArr[0];
			if($autofahuo>0){
				$freightList = [['id'=>0,'name'=>($autofahuo==3?'自动发货':'在线卡密'),'pstype'=>$autofahuo]];
			}else{
				$freightwhere = [['status','=',1],['aid','=',aid],['bid','=',$bid]];
                if(getcustom('supply_yongsheng')){
					//判断是不是永盛商品，永盛商品仅支持普通快递
					if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
						$freightwhere[] = ['pstype','=',0];
					}
				}
				$freightList = \app\model\Freight::getList($freightwhere);
				$fids = [];
				foreach($freightList as $v){
					$fids[] = $v['id'];
				}
				foreach($buydata['prodata'] as $prodata){
					if($prodata['product']['freighttype']==0){
						$fids = array_intersect($fids,explode(',',$prodata['product']['freightdata']));
					}else{
						$thisfreightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
						$thisfids = [];
						foreach($thisfreightList as $v){
							$thisfids[] = $v['id'];
						}
						$fids = array_intersect($fids,$thisfids);
					}
				}
				if(!$fids){
					if(count($buydata['prodata'])>1){
						return $this->json(['status'=>0,'msg'=>'所选择商品配送方式不同，请分别下单']);
					}else{
						return $this->json(['status'=>0,'msg'=>'获取配送方式失败']);
					}
				}
				$freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid],['id','in',$fids]]);
				foreach($freightList as $k=>$v){
					if($v['pstype']==2){ //同城配送
						$havetongcheng = 1;
					}
				}
			}
			$allbuydata[$groupBid]['freightList'] = $freightList;
		}
		if($havetongcheng){
			$address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->where('latitude','>',0)->order('isdefault desc,id desc')->find();
		}else{
			$address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->order('isdefault desc,id desc')->find();
		}
		if(!$address) $address = array();

		$bidGroupList = [];//统计分组数
		$bidList = [];//统计bid数
		foreach($allbuydata as $groupBid=>$buydata){
			$bidGroupList[] = $groupBid;
            $bidGroupArr = explode('_',$groupBid);
            $bid = $bidGroupArr[0];
            if(!in_array($bid,$bidList)) $bidList[] = $bid;

			$product_priceArr = [];
			$product_scoreArr = [];
			$product_price = 0;
			$product_score = 0;
			$totalweight = 0;
			$totalnum = 0;
			$prodataArr = [];
			$proids = [];

			$bindMendianIds = [];//筛选绑定的门店门店
			foreach($buydata['prodata'] as $prodata){
				$product_priceArr[] = $prodata['product']['money_price'] * $prodata['num'];
				$product_scoreArr[] = $prodata['product']['score_price'] * $prodata['num'];
				$product_price += $prodata['product']['money_price'] * $prodata['num'];
				$product_score += $prodata['product']['score_price'] * $prodata['num'];
				$totalweight += $prodata['product']['weight'] * $prodata['num'];
				$totalnum += $prodata['num'];
				$prodataArr[] =  $prodata['product']['id'].','.$prodata['num'].','.$prodata['ggid'];
				$proids[] = $prodata['product']['id'];
				
				if(getcustom('scoreshop_product_bind_mendian')){
                    if($prodata['product']['bind_mendian_ids'] && !in_array('-1',explode(',',$prodata['product']['bind_mendian_ids']))){
                        $bindMendianIds = array_unique(array_merge($bindMendianIds,explode(',',$prodata['product']['bind_mendian_ids'])));
                    }
                }
			}
			$rs = \app\model\Freight::formatFreightList($buydata['freightList'],$address,$product_price,$totalnum,$totalweight,$bindMendianIds);

            if(!getcustom('freight_upload_pics') && $rs['freightList']){
                //是否开启配送方式多图
                foreach ($rs['freightList'] as $fk => $fv){
                    foreach ($fv['formdata'] as $fk1 => $fv1){
                        if($fv1['key'] == 'upload_pics'){
                            unset($rs['freightList'][$fk]['formdata'][$fk1]);
                        }
                    }
                }
            }

			$freightList = $rs['freightList'];
			$freightArr = $rs['freightArr'];
			if($rs['needLocation']==1) $needLocation = 1;

			
			if($bid!=0){
				$business = Db::name('business')->where('id',$bid)->field('id,aid,cid,name,logo,tel,address,sales,longitude,latitude,start_hours,end_hours,start_hours2,end_hours2,start_hours3,end_hours3,end_buy_status,invoice,invoice_type')->find();
			}else{
				$business = Db::name('admin_set')->where('aid',aid)->field('id,name,logo,desc,tel,invoice,invoice_type,invoice_rate')->find();
			}

			$allbuydata[$groupBid]['bid'] = $bid;
			$allbuydata[$groupBid]['business'] = $business;
			$allbuydata[$groupBid]['prodatastr'] = implode('-',$prodataArr);
			$allbuydata[$groupBid]['freightList'] = $freightList;
			$allbuydata[$groupBid]['freightArr'] = $freightArr;
			$allbuydata[$groupBid]['product_price'] = round($product_price,2);
			$allbuydata[$groupBid]['product_score'] = $product_score;
			$allbuydata[$groupBid]['freightkey'] = 0;
			$allbuydata[$groupBid]['pstimetext'] = '';
			$allbuydata[$groupBid]['freight_time'] = '';
			$allbuydata[$groupBid]['storeid'] = 0;
			$allbuydata[$groupBid]['storename'] = '';
            $allbuydata[$groupBid]['cuxiao_money'] = 0;
            $allbuydata[$groupBid]['cuxiaotype'] = 0;
            $allbuydata[$groupBid]['cuxiaoid'] = 0;
            $allbuydata[$groupBid]['invoice_money'] = 0;
            $allbuydata[$groupBid]['editorFormdata'] = [];
		}

		$rdata = [];
		$rdata['linkman'] = $address ? $address['name'] : strval($this->member['realname']);
		$rdata['tel'] = $address ? $address['tel'] : strval($this->member['tel']);
		if(!$rdata['linkman']){
			$lastorder = Db::name('scoreshop_order')->where('aid',aid)->where('mid',mid)->where('linkman','<>','')->find();
			if($lastorder){
				$rdata['linkman'] = $lastorder['linkman'];
				$rdata['tel'] = $lastorder['tel'];
			}
		}
		$rdata['totalmoney'] = $totalmoney;
		$rdata['totalscore'] = $totalscore;
		$rdata['totalnum'] = $totalnum;
		$rdata['totalweight'] = $totalweight;
		$rdata['havetongcheng'] = $havetongcheng;
		$rdata['address'] = $address;
		$rdata['prolist'] = $prolist;
		$rdata['freightList'] = $freightList;
		$rdata['freightArr'] = $freightArr;
		$rdata['needLocation'] = $needLocation;
		$rdata['allbuydata'] = $allbuydata;
    	$rdata['contact_require'] = $contact_require;
    	if(getcustom('supply_yongsheng')){
			//是否必须使用地址
			$mustuseaddress = $mustuseaddress?$mustuseaddress:false;
			if($haveyspro){
				$mustuseaddress = true;
			}
			$rdata['mustuseaddress']     = $mustuseaddress;
		}
		return $this->json($rdata);
	}
	public function createOrder(){
		$this->checklogin();
		if(getcustom('scoreshop_otheradmin_buy')){
			//是来自本系统其他平台的用户(仅小程序)
			$othermember = [];//其他平台用户
			$BuyOverallScoreshop = false;//权限
            $othermid = input('?param.othermid')?input('param.othermid/d'):0;
            if(platform == 'wx' && $othermid){
                $othermember = Db::name('member')->where('id',$othermid)->field('id,aid,nickname,score')->find();
                //用户存在，且不是本平台
                if($othermember && $othermember['aid'] != aid){
                    //查询权限组 是否开启兑换总平台积分商品
                    $admin_user = db('admin_user')->where('aid',$othermember['aid'])->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                    if($admin_user['auth_type'] != 1){
                        if($admin_user['groupid']){
                            $admin_user['auth_data'] = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                        }
                        $admin_auth = json_decode($admin_user['auth_data'],true);
                        if($admin_auth && in_array('BuyOverallScoreshop,BuyOverallScoreshop',$admin_auth)){
                            $BuyOverallScoreshop = true;//标记有兑换权限
                        }
                    }else{
                        $BuyOverallScoreshop = true;//标记有兑换权限
                    }
                    if($BuyOverallScoreshop){
                        $othermember = $othermember;//来自其他账号的会员
                    }
                }
            }
        }
		$post = input('post.');
		if(input('param.prodata')){
			$buydata = [[
				'bid'=>0,
				'prodata'=>$post['prodata'],
				'freight_id'=>$post['freightid'],
				'freight_time'=>$post['freight_time'],
				'storeid'=>$post['storeid'],
				'formdata'=>$post['formdata']
			]];
		}else{
			$buydata = $post['buydata'];
		}
        $sysset = Db::name('admin_set')->where('aid',aid)->find();

		//收货地址
		if($post['addressid']=='' || $post['addressid']==0){
			$address = ['id'=>0,'name'=>$post['linkman'],'tel'=>$post['tel'],'area'=>'','address'=>''];
		}else{
			$address = Db::name('member_address')->where('id',$post['addressid'])->where('aid',aid)->where('mid',mid)->find();
		}

		$alltotalprice = 0;
		$alltotalscore = 0;
		$i = 0;
		$ordernum = date('ymdHis').aid.rand(1000,9999);
        if(getcustom('supply_yongsheng')){
            //判断是不是永盛商品 是的话需要提前验证
            foreach($buydata as $key=>$data){
            	$bidGroup = $data['bidGroup']??'';
                $bidGroupArr = $bidGroup?explode('_',$bidGroup):[$data['bid']];
                $bid = $data['bid'];
                if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
                    if(getcustom('supply_yongsheng')){
                    	//提前检验订单商品
                        if($data['prodata']){
                            $prodata = explode('-',$data['prodata']);
                        }else{
                            return $this->json(['status'=>0,'msg'=>'产品数据错误']);
                        }

                        $checkorderproduct = \app\custom\SupplyYongsheng::checkScoreshopOrderProduct(aid,$bid,mid,$this->member,$prodata,$data,$address);
                        if(!$checkorderproduct || $checkorderproduct['status'] != 1){
                            $msg = $checkorderproduct && $checkorderproduct['msg']?$checkorderproduct['msg']:'订单信息错误';
                            return $this->json(['status'=>0,'msg'=>$msg]);
                        }
                    }
                }
            }
        }

		foreach($buydata as $data){
			$i++;
			$bidGroup = $data['bidGroup']??'';
            $bidGroupArr = $bidGroup?explode('_',$bidGroup):[$data['bid']];
            $bid = $data['bid'];
			if($data['prodata']){
				$prodata = explode('-',$data['prodata']);
			}else{
				return $this->json(['status'=>0,'msg'=>'产品数据错误']);
			}

			$totalmoney = 0;
			$totalscore = 0;
			$totalweight = 0;
			$totalnum = 0;
			$prolist = [];
			$autofahuo = 0;
            $isdghongbao = 0;//是否是兑换红包
            if(getcustom('consumer_value_add') && getcustom('consumer_value_add_scoreshop')){
                $give_green_score = 0; //奖励绿色积分 确认收货后赠送
                $give_green_score2 = 0; //奖励绿色积分 付款后赠送
                $give_bonus_pool = 0; //奖励绿色积分 确认收货后赠送
                $give_bonus_pool2 = 0; //奖励绿色积分 付款后赠送
                $consumer_set = Db::name('consumer_set')->where('aid',aid)->find();
                $green_score_price = $consumer_set['green_score_price']>$consumer_set['min_price']?$consumer_set['green_score_price']:$consumer_set['min_price'];
                if(getcustom('green_score_reserves')){
                    $give_green_score_reserves = 0;//订单进入预备金 确认收货后赠送
                    $give_green_score_reserves2 = 0;//订单进入预备金 付款后赠送
                }
            }
            if(getcustom('scoreshop_givescore')){
            	$givescore = 0; //奖励积分 确认收货后赠送
            }
			foreach($prodata as $key=>$gwc){
				$gwcArr = explode(',',$gwc);
				$proid = intval($gwcArr[0]);
				$num = intval($gwcArr[1]);
				$ggid = $gwcArr[2] && $gwcArr[2] != 'null' ? intval($gwcArr[2]) : null;
				if($num < 1) $num = 1;
				$product = Db::name('scoreshop_product')->where('aid',aid)->where('status',1)->where('id',$proid)->find();
				if(!$product){
					return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
				}
				if($product['stock'] < $num){
					return $this->json(['status'=>0,'msg'=>$product['name'].'库存不足']);
				}
				if($product['gettj'] == '' && $product['bid']!=0) $product['gettj'] = '-1';
				$gettj = explode(',',$product['gettj']);
				if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj) && (!in_array('0',$gettj) || $this->member['subscribe']!=1)){ //不是所有人
					if(!$product['gettjtip']) $product['gettjtip'] = '没有权限兑换该商品';
					return $this->json(['status'=>0,'msg'=>$product['gettjtip'],'url'=>$product['gettjurl']]);
				}

				$bid = $product['bid'];
				//是否达到限制兑换数
				if($product['buymax'] > 0){
					$buynum = $num + Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('status','in','0,1,2,3')->sum('num');
					if($buynum > $product['buymax']){
						return $this->json(['status'=>0,'msg'=>'每人限兑'.$product['buymax'].'次']);
					}
				}
				if(getcustom('plug_tengrui')) {
					//判断是否是否符合会员认证、会员关系、一户
					$tr_check = new \app\common\TengRuiCheck();
					$check_score = $tr_check->check_score($this->member,$product);
					if($check_score && $check_score['status'] == 0){
						return $this->json(['status'=>$check_score['status'],'msg'=>$check_score['msg']]);
					}
					$tr_roomId = $check_score['tr_roomId'];
					$product['tr_roomId'] = $tr_roomId;
				}
				$product = $this->formatScoreProduct($product);

				if($product['guigeset'] == 1){
					if(!$ggid) return $this->json(['status'=>0,'msg'=>'请选择规格']);
					$guige = Db::name('scoreshop_guige')->where('aid',aid)->where('proid',$proid)->where('id',$ggid)->find();
					if(!$guige) return $this->json(['status'=>0,'msg'=>'规格不存在']);
					if($guige['stock'] < $num){
						return $this->json(['status'=>0,'msg'=>$product['name'].$guige['name'].'库存不足']);
					}
					if($product['lvprice']==1){
						$lvprice_data = json_decode($guige['lvprice_data'],true);
						if($lvprice_data && isset($lvprice_data[$this->member['levelid']])){
							$guige['money_price'] = $lvprice_data[$this->member['levelid']]['money'];
							$guige['score_price'] = $lvprice_data[$this->member['levelid']]['score'];
						}
					}
					$product['money_price'] = $guige['money_price'];
					$product['score_price'] = $guige['score_price'];
					$product['cost_price']  = $guige['cost_price'];
					$product['ggid'] = $guige['id'];
					$product['ggname'] = $guige['name'];
                    if(getcustom('consumer_value_add') && getcustom('consumer_value_add_scoreshop')){
                        $product['give_green_score'] = $guige['give_green_score'];
                        $product['give_green_score2'] = $guige['give_green_score2'];
                    }
                    if(getcustom('green_score_reserves') && getcustom('consumer_value_add_scoreshop')){
                        $product['give_green_score'] = $guige['give_green_score_reserves'];
                    }
                    if(getcustom('scoreshop_givescore')){
		            	$givescore = $guige['givescore'] *$num; //奖励积分 确认收货后赠送
		            }
		            if(getcustom('supply_yongsheng')){
		            	$product['source_code'] = $guige['source_code']??'';
		            }
				}else{
					if(getcustom('scoreshop_givescore')){
		            	$givescore = $product['givescore'] * $num; //奖励积分 确认收货后赠送
		            }
				}

				$totalmoney += $product['money_price'] * $num;
				$totalscore += $product['score_price'] * $num;
				$totalweight += $product['weight'] * $num;
				$totalnum += $num;
				$product['num'] = $num;
				$prolist[] = $product;
				if($product['freighttype']==3 || $product['freighttype']==4){
					$autofahuo = $product['freighttype'];
				}
				if(getcustom('scoreshop_wx_hongbao')){
				    if($product['type'] ==1){ //判断是否是兑换红包 
				           $isdghongbao = 1;
                    }
                }
                if(getcustom('consumer_value_add') && getcustom('consumer_value_add_scoreshop')){
                    $can_give_green_score = 1;
                    if($consumer_set['fwtype']==2){//指定商品可用
                        $productids = explode(',',$consumer_set['productids']);
                        if(!in_array($product['id'],$productids)){
                            $can_give_green_score = 0;
                        }
                    }

                    if($consumer_set['fwtype']==1){//指定类目可用
                        $categoryids = explode(',',$consumer_set['categoryids']);
                        $cids = explode(',',$product['cid']);
                        $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                        foreach($clist as $vc){
                            $categoryids[] = $vc['id'];
                            $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                            $categoryids[] = $cate2['id'];
                        }
                        if(!array_intersect($cids,$categoryids)){
                            $can_give_green_score = 0;
                        }
                    }
                    if($can_give_green_score){
                        if($product['give_green_score']<=0){
                            //$guige['give_green_score'] = bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,2);
                            $product['give_green_score'] = bcdiv(bcmul($product['sell_price'],$consumer_set['green_score_bili']/100,4),$green_score_price,2);
                        }else{
                            $product['give_green_score'] = bcdiv($product['give_green_score'],$green_score_price,2);
                        }
                        if($product['give_bonus_pool']<=0){
                            $product['give_bonus_pool'] = bcmul($product['sell_price'],$consumer_set['bonus_pool_bili']/100,2);
                        }
                        if($consumer_set['reward_time']==0){
                            $give_green_score += $product['give_green_score'] * $num; //奖励绿色积分 确认收货后赠送
                            $give_bonus_pool += $product['give_bonus_pool'] * $num; //放入奖金池 确认收货后赠送
                        }else{
                            $give_green_score2 += $product['give_green_score'] * $num; //奖励绿色积分 确认收货后赠送
                            $give_bonus_pool2 += $product['give_bonus_pool'] * $num; //放入奖金池 确认收货后赠送
                        }
                        if(getcustom('green_score_reserves')){
                            //订单进入预备金
                            if($product['give_green_score_reserves']<=0){
                                //$guige['give_green_score'] = bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,2);
                                $product['give_green_score_reserves'] = bcmul($product['sell_price'],$consumer_set['reserves_bili']/100,2);
                            }
                            if($consumer_set['reward_time']==0){
                                $give_green_score_reserves += $product['give_green_score_reserves'] * $num; //预备金 确认收货后赠送
                            }else{
                                $give_green_score_reserves2 += $product['give_green_score_reserves'] * $num; //预备金 确认收货后赠送
                            }
                        }
                    }
                }
			}
			if($autofahuo && count($prodata)>1) $this->json(['status'=>0,'msg'=>'虚拟商品请分别下单']);
			
			//运费
			$freight_price = 0;
			if($data['freight_id']){
				$freight = Db::name('freight')->where('aid',aid)->where('bid',$bid)->where('id',$data['freight_id'])->find();
				if(($address['name']=='' || $address['tel'] =='') && ($freight['pstype']==1 || $freight['pstype']==3) && $freight['needlinkinfo']==1){
					return $this->json(['status'=>0,'msg'=>'请填写联系人和联系电话']);
				}

				$rs = \app\model\Freight::getFreightPrice($freight,$address,$totalmoney,$totalnum,$totalweight);
				if($rs['status']==0) return $this->json($rs);
				$freight_price = $rs['freight_price'];

				//判断配送时间选择是否符合要求
				if($freight['pstimeset']==1){
					//$freighttime = strtotime(explode('~',$data['freight_time'])[0]);
					$freight_times = explode('~',$data['freight_time']);
					if($freight_times[1]){
						$freighttime = strtotime(explode(' ',$freight_times[0])[0] . ' '.$freight_times[1]);
					}else{
						$freighttime = strtotime($freight_times[0]);
					}
					if(time() + $freight['psprehour']*3600 > $freighttime){
						return $this->json(['status'=>0,'msg'=>(($freight['pstype']==0 || $freight['pstype']==2 || $freight['pstype']==10)?'配送':'提货').'时间必须在'.$freight['psprehour'].'小时之后']);
					}
				}
			}elseif($product['freighttype']==3){
				$freight = ['id'=>0,'name'=>'自动发货','pstype'=>3];
                if($product['contact_require'] == 1 && ($address['name']=='' || $address['tel'] =='')){
                    return $this->json(['status'=>0,'msg'=>'请填写联系人和联系电话']);
                }
                if($address['tel']!='' && !checkTel(aid,$address['tel'])){
                    return $this->json(['status'=>0,'msg'=>'请填写正确的联系电话']);
                }
			}elseif($product['freighttype']==4){
				$freight = ['id'=>0,'name'=>'在线卡密','pstype'=>4];
                if($product['contact_require'] == 1 && ($address['name']=='' || $address['tel'] =='')){
                    return $this->json(['status'=>0,'msg'=>'请填写联系人和联系电话']);
                }
                if($address['tel']!='' && !checkTel(aid,$address['tel'])){
                    return $this->json(['status'=>0,'msg'=>'请填写正确的联系电话']);
                }
			}else{
				$freight = ['id'=>0,'name'=>'包邮','pstype'=>0];
			}
			//$totalmoney = $totalmoney + $freight_price;
			
			$orderdata = [];
			$orderdata['aid'] = aid;
			$orderdata['bid'] = $bid;
			$orderdata['mid'] = mid;
			if(getcustom('scoreshop_wx_hongbao')){
                if($isdghongbao){
                    $orderdata['type'] = 1;
                }
                $day_start_time  = strtotime(date('Y-m-d 00:00:00'));
                $day_end_time  =$day_start_time + 86400 ;
                $scoredk_syssey =  Db::name('scoreshop_sysset')->where('aid',aid)->find();
                $buynum =  Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid',mid)->where('status','in','0,1,2,3')->where('createtime','between',[$day_start_time,$day_end_time])->sum('num');
                 $total_buynum = $totalnum +  $buynum;
                 if($scoredk_syssey['buymax'] > 0 && $total_buynum > $scoredk_syssey['buymax']){
                    return $this->json(['status'=>0,'msg'=>'每人每天限兑'.$scoredk_syssey['buymax'].'件，已兑换'.$buynum.'件']);
                 }
            }
            if(getcustom('scoreshop_to_money')){
                $orderdata['type'] = 2;
            }
			if(count($buydata) > 1){
				$orderdata['ordernum'] = $ordernum.'_'.$i;
			}else{
				$orderdata['ordernum'] = $ordernum;
			}
			$orderdata['title'] = removeEmoj($prolist[0]['name']).(count($prolist)>1 ? '等' : '');
			$orderdata['linkman'] = $address['name'];
			$orderdata['tel'] = $address['tel'];
			$orderdata['area'] = $address['area'];
			$orderdata['area2'] = $address['province'].','.$address['city'].','.$address['district'];
			$orderdata['address'] = $address['address'];
			$orderdata['longitude'] = $address['longitude'];
			$orderdata['latitude'] = $address['latitude'];
            $score_weishu = $this->score_weishu;
			$orderdata['totalscore'] = dd_money_format($totalscore,$score_weishu);
			$orderdata['totalmoney'] = $totalmoney;
			$orderdata['totalnum'] = $totalnum;
			$orderdata['freight_price'] = $freight_price; //运费
			$orderdata['totalprice'] = $totalmoney + $freight_price*1;
			if($freight && ($freight['pstype']==0||$freight['pstype']==10)){
				$orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
				$orderdata['freight_type'] = $freight['pstype'];
			}elseif($freight && $freight['pstype']==1){
                $mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
				$orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                $orderdata['area2'] = $mendian['area'];
				$orderdata['freight_type'] = 1;
				$orderdata['mdid'] = $data['storeid'];
				if(getcustom('freight_selecthxbids') && $freight['bid']==0){
					$orderdata['freight_text'] = $freight['name'];
					$orderdata['area2'] = '';
					$orderdata['mdid'] = '-1';
					if(!$orderdata['longitude']){
						$orderdata['longitude'] = $post['longitude'];
						$orderdata['latitude'] = $post['latitude'];
					}
				}
			}elseif($freight && $freight['pstype']==2){
				$orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
				$orderdata['freight_type'] = 2;
			}elseif($freight && ($freight['pstype']==3 || $freight['pstype']==4)){ //自动发货 在线卡密
				$orderdata['freight_text'] = $freight['name'];
				$orderdata['freight_type'] = $freight['pstype'];
			}else{
				$orderdata['freight_text'] = '包邮';
			}
			$orderdata['freight_id'] = $freight['id'];
			$orderdata['freight_time'] = $data['freight_time']; //配送时间
			$orderdata['createtime'] = time();
			$orderdata['hexiao_code'] = random(16);
			$orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=scoreshop&co='.$orderdata['hexiao_code']));
			$orderdata['platform'] = platform;
			if(getcustom('scoreshop_otheradmin_buy')){
				//记录来源平台及来源平台用户
				if(platform == 'wx' && $BuyOverallScoreshop && $othermember){
					$orderdata['otheraid'] = $othermember['aid'];
					$orderdata['othermid'] = $othermember['id'];
				}
			}
			if(getcustom('fuwu_usercenter')){
                $orderdata['fuwu_uid'] = $this->member['fuwu_uid']?:0;
            }
			if(getcustom('user_auth_province')){
                $orderdata['province'] = $address['province'];
                $orderdata['city'] = $address['city'];
            }
            if(getcustom('consumer_value_add') && getcustom('consumer_value_add_scoreshop')){
                $orderdata['give_green_score'] = $give_green_score;
                $orderdata['give_bonus_pool'] = $give_bonus_pool;
                $orderdata['give_green_score2'] = $give_green_score2;
                $orderdata['give_bonus_pool2'] = $give_bonus_pool2;
                if(getcustom('green_score_reserves')){
                    //订单进入预备金
                    $orderdata['give_green_score_reserves'] = $give_green_score_reserves;
                    $orderdata['give_green_score_reserves2'] = $give_green_score_reserves2;
                }
            }
            if(getcustom('scoreshop_givescore')){
                $orderdata['givescore'] = dd_money_format($givescore,$this->score_weishu);
            }
            if(getcustom('supply_yongsheng')){
                if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
                  $orderdata['issource'] = 1;
                  $orderdata['source']   = 'supply_yongsheng';
                }
            }
			$orderid = Db::name('scoreshop_order')->insertGetId($orderdata);
			\app\model\Freight::saveformdata($orderid,'scoreshop_order',$freight['id'],$data['formdata']);
			$payorderid = \app\model\Payorder::createorder(aid,$orderdata['bid'],$orderdata['mid'],'scoreshop',$orderid,$orderdata['ordernum'],$orderdata['title'],$orderdata['totalprice'],$orderdata['totalscore']);

			
			$alltotalprice += $orderdata['totalprice'];
			$alltotalscore += $orderdata['totalscore'];

			$istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
			$istc2 = 0;
			$istc3 = 0;
			foreach($prolist as $product){
				$ogdata = [];
				$ogdata['aid'] = aid;
				$ogdata['bid'] = $bid;
				$ogdata['mid'] = mid;
				$ogdata['orderid'] = $orderid;
				$ogdata['ordernum'] = $orderdata['ordernum'];
				$ogdata['proid'] = $product['id'];
				$ogdata['name'] = $product['name'];
				$ogdata['ggid'] = $product['ggid'] ?? null;
				$ogdata['ggname'] = $product['ggname'] ?? null;
				$ogdata['pic'] = $product['pic'];
				$ogdata['procode'] = $product['procode'];
				$ogdata['num'] = $product['num'];
				$ogdata['sell_price'] = $product['sell_price'];
				$ogdata['cost_price'] = $product['cost_price'];
				$ogdata['money_price'] = $product['money_price'];
				$ogdata['score_price'] = $product['score_price'];
				$ogdata['totalscore'] = $product['score_price'] * $product['num'];
				$ogdata['totalmoney'] = $product['money_price'] * $product['num'];
				$ogdata['status'] = 0;
				$ogdata['createtime'] = time();
				if(getcustom('scoreshop_wx_hongbao')){
				    $ogdata['type'] = $product['type'];
                }
                if(getcustom('scoreshop_to_money')){
                    $ogdata['type'] = $product['type'];
                }
                if(getcustom('supply_yongsheng')){
                    if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
                      $ogdata['issource'] = 1;
                      $ogdata['source']   = 'supply_yongsheng';
                      $ogdata['sproid']   = $product['sproid'];//永盛商品ID
                      $ogdata['source_code'] = $product['source_code'];//永盛商品规格code
                    }
                }

				//分销
				$og_totalprice = $ogdata['totalmoney'];

				//计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
				//0按商品价格，1按成交价，2按销售利润
				$leveldk_money = 0;
				$coupon_money = 0;
				$scoredk_money = 0;
				$manjian_money = 0;
				if($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2){
					$allproduct_price = $og_totalprice;
					$og_leveldk_money = 0;
					$og_coupon_money = 0;
					$og_scoredk_money = 0;
					$og_manjian_money = 0;
					if($allproduct_price > 0 && $og_totalprice > 0){
						if($leveldk_money){
							$og_leveldk_money = $og_totalprice / $allproduct_price * $leveldk_money;
						}
						if($coupon_money){
							$og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
						}
						if($scoredk_money){
							$og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
						}
						if($manjian_money){
							$og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
						}
					}
					$og_totalprice = $og_totalprice - $og_leveldk_money - $og_scoredk_money - $og_manjian_money;
	//                if($couponrecord['type']!=4) {//运费抵扣券
						$og_totalprice -= $og_coupon_money;
	//                }
					$og_totalprice = round($og_totalprice,2);
					if($og_totalprice < 0) $og_totalprice = 0;
				}

				//计算佣金的商品金额
				$commission_totalprice = $ogdata['totalmoney'];
				if($sysset['fxjiesuantype']==1){ //按成交价格
					$commission_totalprice = $og_totalprice;
					if($commission_totalprice < 0) $commission_totalprice = 0;
				}
				if($sysset['fxjiesuantype']==2){ //按销售利润
					$commission_totalprice = $og_totalprice - $product['cost_price'] * $product['num'];
					if($commission_totalprice < 0) $commission_totalprice = 0;
				}

				$agleveldata = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
				if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
					$this->member['pid'] = mid;
				}

				if($product['commissionset']!=-1){
					if($this->member['pid']){
						$parent1 = Db::name('member')->where('aid',aid)->where('id',$this->member['pid'])->find();
						if($parent1){
							$agleveldata1 = Db::name('member_level')->where('aid',aid)->where('id',$parent1['levelid'])->find();
							if($agleveldata1['can_agent']!=0){
								$ogdata['parent1'] = $parent1['id'];
							}
						}
					}
					if($parent1['pid']){
						$parent2 = Db::name('member')->where('aid',aid)->where('id',$parent1['pid'])->find();
						if($parent2){
							$agleveldata2 = Db::name('member_level')->where('aid',aid)->where('id',$parent2['levelid'])->find();
							if($agleveldata2['can_agent']>1){
								$ogdata['parent2'] = $parent2['id'];
							}
						}
					}
					if($parent2['pid']){
						$parent3 = Db::name('member')->where('aid',aid)->where('id',$parent2['pid'])->find();
						if($parent3){
							$agleveldata3 = Db::name('member_level')->where('aid',aid)->where('id',$parent3['levelid'])->find();
							if($agleveldata3['can_agent']>2){
								$ogdata['parent3'] = $parent3['id'];
							}
						}
					}
					if($product['commissionset']==1){//按商品设置的分销比例
						$commissiondata = json_decode($product['commissiondata1'],true);
						if($commissiondata){
							if($agleveldata1) $ogdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
							if($agleveldata2) $ogdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
							if($agleveldata3) $ogdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
						}
					}elseif($product['commissionset']==2){//按固定金额
						$commissiondata = json_decode($product['commissiondata2'],true);
						if($commissiondata){
							if($agleveldata1) $ogdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $ogdata['num'];
							if($agleveldata2) $ogdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $ogdata['num'];
							if($agleveldata3) $ogdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $ogdata['num'];
						}
					}elseif($product['commissionset']==3){//提成是积分
						$commissiondata = json_decode($product['commissiondata3'],true);
						if($commissiondata){
							if($agleveldata1) $ogdata['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $ogdata['num'];
							if($agleveldata2) $ogdata['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $ogdata['num'];
							if($agleveldata3) $ogdata['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $ogdata['num'];
						}
					}elseif($product['commissionset']==5){//比例+积分
						$commissiondata = json_decode($product['commissiondata5'],true);
						if($commissiondata){
							if($agleveldata1) {
								$ogdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1']['money'] * $commission_totalprice * 0.01;
								$ogdata['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1']['score'] * $ogdata['num'];
							}
							if($agleveldata2) {
								$ogdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2']['money'] * $commission_totalprice * 0.01;
								$ogdata['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2']['score'] * $ogdata['num'];
							}
							if($agleveldata3) {
								$ogdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3']['money'] * $commission_totalprice * 0.01;
								$ogdata['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3']['score'] * $ogdata['num'];
							}
						}
					}else{ //按会员等级设置的分销比例
						if($agleveldata1){
							if($agleveldata1['commissiontype']==1){ //固定金额按单
								if($istc1==0){
									$ogdata['parent1commission'] = $agleveldata1['commission1'];
									$istc1 = 1;
								}
							}else{
								$ogdata['parent1commission'] = $agleveldata1['commission1'] * $commission_totalprice * 0.01;
							}
						}
						if($agleveldata2){
							if($agleveldata2['commissiontype']==1){
								if($istc2==0){
									$ogdata['parent2commission'] = $agleveldata2['commission2'];
									$istc2 = 1;
								}
							}else{
								$ogdata['parent2commission'] = $agleveldata2['commission2'] * $commission_totalprice * 0.01;
							}
						}
						if($agleveldata3){
							if($agleveldata3['commissiontype']==1){
								if($istc3==0){
									$ogdata['parent3commission'] = $agleveldata3['commission3'];
									$istc3 = 1;
								}
							}else{
								$ogdata['parent3commission'] = $agleveldata3['commission3'] * $commission_totalprice * 0.01;
							}
						}
					}
				}

				//计算门店佣金
				if(getcustom('plug_zhiming')) {
					$mendian_money = 0;
					if($ogdata['totalmoney'] > 0){
						if($product['commission_money_type'] == 0) {
							if($product['commission_money_percent'] > 0)
								$mendian_money = $ogdata['totalmoney'] * $product['commission_money_percent'] * 0.01;
						} elseif($product['commission_money_type'] == 1) {
							$mendian_money = $product['commission_money'] * $product['num'];
						}
					}
					$ogdata['mendian_commission'] = $mendian_money ? round($mendian_money,2 ) : 0;

					$mendian_score = 0;
					if($ogdata['totalscore'] > 0){
						if($product['commission_score_type'] == 0) {
							if($product['commission_score_percent'] > 0)
								$mendian_score = floor($ogdata['totalscore'] * $product['commission_score_percent'] * 0.01);
						} elseif($product['commission_score_type'] == 1) {
							$mendian_score = $product['commission_score'] * $product['num'];
						}
					}
					$ogdata['mendian_score'] = $mendian_score ? floor($mendian_score) : 0;
					$ogdata['mendian_iscommission'] = 0;
				}
				if(getcustom('plug_tengrui')) {
					$ogdata['tr_roomId'] = $product['tr_roomId']?$product['tr_roomId']:0;
				}
                if(getcustom('scoreshop_fenhong') && $product['fenhongset'] == 0){ //不参与分红
                    $ogdata['isfenhong'] = 2;
                }
				if(getcustom('score_product_membergive')){
					$ogdata['membergive_member_id'] = $product['membergive_member_id'];
					$ogdata['membergive_commission'] = $product['membergive_commission'];
					$ogdata['membergive_score'] = $product['membergive_score'];
					$ogdata['membergive_money'] = $product['membergive_money'];
				}		 
				$ogid = Db::name('scoreshop_order_goods')->insertGetId($ogdata);
				if($ogdata['parent1'] && ($ogdata['parent1commission'] > 0 || $ogdata['parent1score'] > 0)){
					$parent1_levelid = $parent1['levelid']??0;
					Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$ogdata['parent1'],'frommid'=>mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'scoreshop','commission'=>$ogdata['parent1commission'],'score'=>$ogdata['parent1score'],'remark'=>'下级购买积分商品奖励','createtime'=>time(),'levelid'=>$parent1_levelid]);
				}
				if($ogdata['parent2'] && ($ogdata['parent2commission'] || $ogdata['parent2score'])){
					$parent2_levelid = $parent2['levelid']??0;
					Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$ogdata['parent2'],'frommid'=>mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'scoreshop','commission'=>$ogdata['parent2commission'],'score'=>$ogdata['parent2score'],'remark'=>'下二级购买积分商品奖励','createtime'=>time(),'levelid'=>$parent2_levelid]);
				}
				if($ogdata['parent3'] && ($ogdata['parent3commission'] || $ogdata['parent3score'])){
					$parent3_levelid = $parent3['levelid']??0;
					Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$ogdata['parent3'],'frommid'=>mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'scoreshop','commission'=>$ogdata['parent3commission'],'score'=>$ogdata['parent3score'],'remark'=>'下三级购买积分商品奖励','createtime'=>time(),'levelid'=>$parent3_levelid]);
				}
                //删除购物车
                $cartwhere = [];
                $cartwhere[] = ['aid','=',aid];
                $cartwhere[] = ['mid','=',mid];
                $cartwhere[] = ['proid','=',$product['id']];
                if(isset($product['ggid']) && $product['ggid']){
                    $cartwhere[] = ['ggid','=', $product['ggid']];
                }
                Db::name('scoreshop_cart')->where('aid',aid)->where($cartwhere)->delete();
                //减库存加销量
				Db::name('scoreshop_product')->where('aid',aid)->where('id',$product['id'])->update(['stock'=>$product['stock'] - $ogdata['num'],'sales'=>$product['sales'] + $ogdata['num']]);
			}
            //订单创建完成，触发订单完成事件
            \app\common\Order::order_create_done(aid,$orderid,'scoreshop');
            $store_name = Db::name('admin_set')->where('aid',aid)->value('name');
			//公众号通知 订单提交成功
			$tmplcontent = [];
			$tmplcontent['first'] = '有新'.t('积分').'兑换订单提交成功';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $store_name; //店铺
			$tmplcontent['keyword2'] = date('Y-m-d H:i:s',$orderdata['createtime']);//下单时间
			$tmplcontent['keyword3'] = $orderdata['title'];//商品
			$tmplcontent['keyword4'] = $orderdata['totalscore'].t('积分').($orderdata['totalprice']>0?' + '.$orderdata['totalprice'].'元':'');//金额
            $tempconNew = [];
            $tempconNew['character_string2'] = $orderdata['ordernum'];//订单号
            $tempconNew['thing8'] = $store_name;//门店名称
            $tempconNew['thing3'] = $orderdata['title'];//商品名称
            $tempconNew['amount7'] = $orderdata['totalscore'].t('积分').($orderdata['totalprice']>0?' + '.$orderdata['totalprice'].'元':'');//金额
            $tempconNew['time4'] = date('Y-m-d H:i:s',$orderdata['createtime']);//下单时间
			\app\common\Wechat::sendhttmpl(aid,0,'tmpl_orderconfirm',$tmplcontent,m_url('admin/order/scoreshoporder'),$orderdata['mdid'],$tempconNew);
			
			$tmplcontent = [];
			$tmplcontent['thing11'] = $orderdata['title'];
			$tmplcontent['character_string2'] = $orderdata['ordernum'];
			$tmplcontent['phrase10'] = '待付款';
			$tmplcontent['amount13'] = $orderdata['totalprice'].'元';
			$tmplcontent['thing27'] = $this->member['nickname'];
			\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_orderconfirm',$tmplcontent,'admin/order/scoreshoporder',$orderdata['mdid']);
		}
		
		if(count($buydata) > 1){ //创建合并支付单
			$payorderid = \app\model\Payorder::createorder(aid,0,mid,'scoreshop_hb',$orderid,$ordernum,$orderdata['title'],$alltotalprice,$alltotalscore);
		}

		return $this->json(['status'=>1,'orderid'=>$orderid,'payorderid'=>$payorderid,'msg'=>'提交成功']);
	}
	public function orderlist(){
        $this->checklogin();
		$st = input('param.st');
		if(!input('?param.st') || $st === ''){
			$st = 'all';
		}
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		$where[] = ['delete','=',0];
        if(input('param.keyword')) $where[] = ['ordernum|title', 'like', '%'.input('param.keyword').'%'];
		if($st == 'all'){
			
		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}elseif($st == '10'){
			$where[] = ['refund_status','>',0];
		}
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('scoreshop_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
        $score_weishu = $this->score_weishu;
        $collectReward = Db::name('order_collect_reward')->field('order_type,platform,gettj,min_order_amount,prompt,fontcolor,bgcolor,start_time,end_time')->where('aid',aid)->where('start_time','<=',time())->where('end_time','>=',time())->where('status',1)->find();
        $isCollectReward = false;
        if($collectReward) {
            if ($collectReward['bgcolor']) {
                $color1rgb = hex2rgb($collectReward['bgcolor']);
                $collectReward['bgcolor'] = $color1rgb['red'] . ',' . $color1rgb['green'] . ',' . $color1rgb['blue'];
            }
            $isCollectReward = $this->collectRewardNumLimit(aid,mid,$collectReward);
        }
        foreach($datalist as $key=>$v){
		    $prolist = Db::name('scoreshop_order_goods')->where('orderid',$v['id'])->select()->toArray();
		    foreach($prolist as $k_p=>$v_p){
                $prolist[$k_p]['score_price'] = dd_money_format($v_p['score_price'],$score_weishu);
            }
			$datalist[$key]['prolist'] = $prolist;
			if(!$datalist[$key]['prolist']) $datalist[$key]['prolist'] = [];
			$datalist[$key]['procount'] = Db::name('scoreshop_order_goods')->where('orderid',$v['id'])->sum('num');

			if(getcustom('supply_yongsheng')){
	        	if($v['source'] == 'supply_yongsheng' && $v['issource']){
	        		$datalist[$key]['express_content'] = $express_content = \app\custom\SupplyYongsheng::dealExpressContent($v,'scoreshop');
	        	}
	        }
            //确认收货奖励
            $datalist[$key]['is_collect_reward'] = $isCollectReward && $this->isCollectReward($v, $collectReward, $this->member['levelid'], 'scoreshop');
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
        $showRefund = 1;
        if(getcustom('scoreshop_hide_refund')){
            $showRefund = Db::name('scoreshop_sysset')->where('aid',aid)->value('show_refund');
        }
        $rdata['show_refund'] = $showRefund;
        $rdata['collect_reward_set'] = $collectReward;
		return $this->json($rdata);
	}
	public function orderdetail(){
        $this->checklogin();
        $score_weishu = $this->score_weishu;
		$detail = Db::name('scoreshop_order')->where('id',input('param.id/d'))->where('aid',aid)->where('mid',mid)->find();
        $detail['totalscore'] = dd_money_format($detail['totalscore'],$score_weishu);
		if(!$detail) $this->json(['status'=>0,'msg'=>'订单不存在']);
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'scoreshop_order');

        if($detail['formdata']){
            foreach ($detail['formdata'] as $fk => $fv){
                //如果是多图
                if($fv[2] == 'upload_pics'){
                    if (getcustom('freight_upload_pics')){
                        $detail['formdata'][$fk][1] = explode(',',$fv[1]);
                    }else{
                        unset($detail['formdata'][$fk]);
                    }
                }
            }
        }

		$storeinfo = [];//门店
		$storelist = [];
		if($detail['freight_type'] == 1){
			if($detail['mdid'] == -1){
				$freight = Db::name('freight')->where('id',$detail['freight_id'])->find();
				if($freight && $freight['hxbids']){
					if($detail['longitude'] && $detail['latitude']){
						$orderBy = Db::raw("({$detail['longitude']}-longitude)*({$detail['longitude']}-longitude) + ({$detail['latitude']}-latitude)*({$detail['latitude']}-latitude) ");
					}else{
						$orderBy = 'sort desc,id';
					}
					$storelist = Db::name('business')->where('aid',$freight['aid'])->where('id','in',$freight['hxbids'])->where('status',1)->field('id,name,logo pic,longitude,latitude,address')->order($orderBy)->select()->toArray();
					foreach($storelist as $k2=>$v2){
						if($detail['longitude'] && $detail['latitude'] && $v2['longitude'] && $v2['latitude']){
							$v2['juli'] = '距离'.getdistance($detail['longitude'],$detail['latitude'],$v2['longitude'],$v2['latitude'],2).'千米';
						}else{
							$v2['juli'] = '';
						}
						$storelist[$k2] = $v2;
					}
				}
			}else{
				$storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('id,name,address,longitude,latitude')->find();
			}
		}

        $sysset = Db::name('admin_set')->where('aid',aid)->field('name,logo,desc,fxjiesuantype,tel,kfurl,gzts,ddbb')->find();
        if($detail['bid']>0){
            $business = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->field('id,name,logo,desc,tel,address,sales,kfurl')->find();
        }else{
            $business = $sysset;
        }
        $detail['binfo'] = $business;
        $prolist = Db::name('scoreshop_order_goods')->where('orderid',$detail['id'])->select()->toArray();
        foreach($prolist as $k=>$v){
            $prolist[$k]['score_price'] = dd_money_format($v['score_price'],$score_weishu);
        }
        $scoreshopfield = 'comment,autoclose';
        if(getcustom('scoreshop_hide_refund')){
            $scoreshopfield .= ',show_refund';
        }
		$scoreshopset = Db::name('scoreshop_sysset')->where('aid',aid)->field($scoreshopfield)->find();
		if($detail['status']==0 && $scoreshopset['autoclose'] > 0){
			$lefttime = strtotime($detail['createtime']) + $scoreshopset['autoclose']*60 - time();
			if($lefttime < 0) $lefttime = 0;
		}else{
			$lefttime = 0;
		}
		if(getcustom('scoreshop_otheradmin_buy')){
            //查询是否其他账号购买
            $detail['otherinfo'] = '';
            if($detail['othermid']){
            	$appinfo = Db::name('admin_setapp_wx')->where('aid',$detail['otheraid'])->field('id,nickname')->find();
                if($appinfo && !empty($appinfo['nickname'])){
                    $detail['otherinfo'] = $appinfo['nickname'];
                }else{
                    $set = Db::name('admin_set')->where('aid',$detail['otheraid'])->field('name')->find();
                    if($set && !empty($set['name'])){
                        $detail['otherinfo'] =  $set['name'];
                    }
                }
            }
        }
        if(getcustom('supply_yongsheng')){
        	if($detail['source'] == 'supply_yongsheng' && $detail['issource']){
        		$detail['express_content'] = $express_content = \app\custom\SupplyYongsheng::dealExpressContent($detail,'scoreshop');
        		$detail['express_no'] = '';
        		if($express_content){
                    $express_contentArr = json_decode($express_content,true);
                    $express_contentNum = count($express_contentArr);
                    if($express_contentNum == 1){
                        $detail['express_com'] = $express_contentArr[0]['express_com'];
                        $detail['express_com'] = $express_contentArr[0]['express_no'];
                    }else{
                        $detail['express_com'] = '多单发货';
                    }
                }else{
                    $detail['express_com'] = '无';
                }
        	}
        }
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['detail'] = $detail;
		$rdata['prolist'] = $prolist;
		$rdata['scoreshopset'] = $scoreshopset;
		$rdata['storeinfo'] = $storeinfo;
		$rdata['storelist'] = $storelist;
		$rdata['lefttime'] = $lefttime;
		return $this->json($rdata);
	}
	public function logistics(){
		$get = input('param.');

		$getwuliu = true;//是否请求接口
		if(getcustom('supply_yongsheng')){
            if(input('?param.ordertype') && input('?param.orderid')){
                $ordertype = input('param.ordertype');
                $order = Db::name($ordertype.'_order')->where('id',input('param.orderid/d'))->find();
                if($order && $order['source'] == 'supply_yongsheng' && $order['issource']){
                    $getwuliu = false;
                    $list = \app\custom\SupplyYongsheng::dealexpress2($order,$get['express_no'],$get['express_com'],$ordertype);
                }
            }
        }
        if($getwuliu){
        	$list = \app\common\Common::getwuliu($get['express_no'],$get['logistics'], '', aid);
        }

		$rdata = [];
		$rdata['express_no'] = $get['express_no'];
		$rdata['logistics'] = $get['logistics'];
		$rdata['datalist'] = $list;
		return $this->json($rdata);
	}
	
	public function closeOrder(){
        $this->checklogin();
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('scoreshop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(!$order || $order['status']!=0){
			return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
		}
		$rs = Db::name('scoreshop_order')->where('id',$orderid)->where('status',0)->where('aid',aid)->where('mid',mid)->update(['status'=>4]);
		if(!$rs)  return $this->json(['status'=>0,'msg'=>'操作失败']);
		Db::name('scoreshop_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('mid',mid)->update(['status'=>4]);

		//加库存
		$oglist = Db::name('scoreshop_order_goods')->where('aid',aid)->where('orderid',$orderid)->select()->toArray();
		foreach($oglist as $og){
			Db::name('scoreshop_product')->where('aid',aid)->where('id',$og['proid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
		}
        \app\common\Order::order_close_done(aid,$orderid,'scoreshop');
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	public function delOrder(){
        $this->checklogin();
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('scoreshop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(!$order || ($order['status']!=4 && $order['status']!=3)){
			return $this->json(['status'=>0,'msg'=>'删除失败,订单状态错误']);
		}
		if($order['status']==3){
			$rs = Db::name('scoreshop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->update(['delete'=>1]);
		}else{
			$rs = Db::name('scoreshop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->delete();
			$rs = Db::name('scoreshop_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('mid',mid)->delete();
		}
		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}
	public function orderCollect(){ //确认收货
        $this->checklogin();
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('scoreshop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
		if(!$order || ($order['status']!=2) || $order['paytypeid']==4){
			return $this->json(['status'=>0,'msg'=>'订单状态不符合收货要求']);
		}
        $order['collect_reward_platform'] = platform; //确认收货奖励判断平台
        $rs = \app\common\Order::collect($order,'scoreshop');
        if($rs['status'] == 0) return $this->json($rs);
		
		Db::name('scoreshop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);
		Db::name('scoreshop_order_goods')->where('aid',aid)->where('orderid',$orderid)->update(['status'=>3,'endtime'=>time()]);
		\app\common\Member::uplv(aid,mid);
        //确认收货奖励
        $collectReward = $this->getCollectReward(aid,mid,$orderid);
        $return = ['status'=>1,'msg'=>'确认收货成功','url'=>true,'collect_reward' => $collectReward];

		$tmplcontent = [];
		$tmplcontent['first'] = '有订单客户已确认收货';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $this->member['nickname'];
		$tmplcontent['keyword2'] = $order['ordernum'];
		$tmplcontent['keyword3'] = $order['totalprice'].'元';
		$tmplcontent['keyword4'] = date('Y-m-d H:i',$order['paytime']);
        $tmplcontentNew = [];
        $tmplcontentNew['thing3'] = $this->member['nickname'];//收货人
        $tmplcontentNew['character_string7'] = $order['ordernum'];//订单号
        $tmplcontentNew['time8'] = date('Y-m-d H:i');//送达时间
		\app\common\Wechat::sendhttmpl(aid,0,'tmpl_ordershouhuo',$tmplcontent,m_url('admin/order/scoreshoporder'),$order['mdid'],$tmplcontentNew);

		$tmplcontent = [];
		$tmplcontent['thing2'] = $order['title'];
		$tmplcontent['character_string6'] = $order['ordernum'];
		$tmplcontent['thing3'] = $this->member['nickname'];
		$tmplcontent['date5'] = date('Y-m-d H:i');
		\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_ordershouhuo',$tmplcontent,'admin/order/scoreshoporder',$order['mdid']);

		return $this->json($return);
	}
	public function refundinit(){
		//订阅消息
		$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
		$tmplids = [];
		if($wx_tmplset['tmpl_tuisuccess_new']){
			$tmplids[] = $wx_tmplset['tmpl_tuisuccess_new'];
		}elseif($wx_tmplset['tmpl_tuisuccess']){
			$tmplids[] = $wx_tmplset['tmpl_tuisuccess'];
		}
		if($wx_tmplset['tmpl_tuierror_new']){
			$tmplids[] = $wx_tmplset['tmpl_tuierror_new'];
		}elseif($wx_tmplset['tmpl_tuierror']){
			$tmplids[] = $wx_tmplset['tmpl_tuierror'];
		}
		$rdata = [];
		$rdata['tmplids'] = $tmplids;
		return $this->json($rdata);
	}
	public function refund(){//申请退款
        $this->checklogin();
		if(request()->isPost()){
			$post = input('post.');
			$orderid = intval($post['orderid']);
			$money = floatval($post['money']);
            $score = intval($post['score']);
			$order = Db::name('scoreshop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
			if(!$order || ($order['status']!=1 && $order['status'] != 2) || $order['refund_status'] == 2){
				return $this->json(['status'=>0,'msg'=>'订单状态不符合退款要求']);
			}
            //金额可为0
            if($money < 0 || $money > $order['totalprice']){
                return $this->json(['status'=>0,'msg'=>'退款金额有误']);
            }
            //积分可为0
            if($score <0 || $score > $order['totalscore']){
                return $this->json(['status'=>0,'msg'=>'退回'.t('积分').'有误']);
            }
            if(getcustom('business_scoreshop')){
                if($order['bid'] > 0){
                    $business = Db::name('business')->where('aid',aid)->where('id',$order['bid'])->find();
                    if(empty($business)) return $this->json(['status'=>0,'msg'=>'请联系平台客服处理退款']);
                }
            }
            Db::name('scoreshop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->update(['refund_time'=>time(),'refund_status'=>1,'refund_reason'=>$post['reason'],'refund_money'=>$money]);

            $tmplcontent = [];
			$tmplcontent['first'] = '有订单客户申请退款';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $order['ordernum'];
			$tmplcontent['keyword2'] = $money.'元';
			$tmplcontent['keyword3'] = $post['reason'];
            $tmplcontentNew = [];
            $tmplcontentNew['number2'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount4'] = $money;//退款金额
			\app\common\Wechat::sendhttmpl(aid,0,'tmpl_ordertui',$tmplcontent,m_url('admin/order/scoreshoporder'),$order['mdid'],$tmplcontentNew);
			
			$tmplcontent = [];
			$tmplcontent['thing1'] = $order['title'];
			$tmplcontent['character_string4'] = $order['ordernum'];
			$tmplcontent['amount2'] = $order['totalprice'];
			$tmplcontent['amount9'] = $money.'元';
			$tmplcontent['thing10'] = $post['reason'];
			\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_ordertui',$tmplcontent,'admin/order/scoreshoporder',$order['mdid']);

			return $this->json(['status'=>1,'msg'=>'提交成功,请等待商家审核']);
		}
		$orderid = input('param.orderid/d');
		$price = input('param.price/f');
		$order = Db::name('scoreshop_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
		$price = $order['totalprice'];
		$this->assign('orderid',$orderid);
		$this->assign('price',$price);
		return $this->fetch();
	}
	//评价商品
	public function comment(){
        $this->checklogin();
		$ogid = input('param.ogid/d');
		$og = Db::name('scoreshop_order_goods')->where('id',$ogid)->where('mid',mid)->find();
		if(!$og){
			return $this->json(['status'=>0,'msg'=>'未查找到相关记录']);
		}
		$comment = Db::name('shop_comment')->where('ogid',$ogid)->where('aid',aid)->where('mid',mid)->find();
		if(request()->isPost()){
			$scoreshopset = Db::name('scoreshop_sysset')->where('aid',aid)->find();
			if($scoreshopset['comment']==0) return $this->json(['status'=>0,'msg'=>'评价功能未开启']);
			if($comment){
				return $this->json(['status'=>0,'msg'=>'您已经评价过了']);
			}
			$order_good = Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid',mid)->where('id',$ogid)->find();
			$order = Db::name('scoreshop_order')->where('id',$order_good['orderid'])->find();
			$content = input('post.content');
			$content_pic = input('post.content_pic');
			$score = input('post.score/d');
			if($score < 1){
				return $this->json(['status'=>0,'msg'=>'请打分']);
			}
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['ogid'] = $order_good['id'];
			$data['proid'] =$order_good['proid'];
			$data['proname'] = $order_good['name'];
			$data['propic'] = $order_good['pic'];
			$data['orderid']= $order['id'];
			$data['ordernum']= $order['ordernum'];
			$data['score'] = $score;
			$data['content'] = $content;
			$data['openid']= $this->member['openid'];
			$data['nickname']= $this->member['nickname'];
			$data['headimg'] = $this->member['headimg'];
			$data['createtime'] = time();
			$data['content_pic'] = $content_pic;
			$data['ggid'] = $order_good['ggid'];
			$data['ggname'] = $order_good['ggname'];
			$data['status'] = ($scoreshopset['comment_check']==1 ? 0 : 1);
			Db::name('scoreshop_comment')->insert($data);
			Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid',mid)->where('id',$ogid)->update(['iscomment'=>1]);
			//Db::name('scoreshop_order')->where('id',$order['id'])->update(['iscomment'=>1]);
			
			//如果不需要审核 增加产品评论数及评分
			if($scoreshopset['comment_check']==0){
				$countnum = Db::name('scoreshop_comment')->where('proid',$order_good['proid'])->where('status',1)->count();
				$score = Db::name('scoreshop_comment')->where('proid',$order_good['proid'])->where('status',1)->avg('score');
				Db::name('scoreshop_product')->where('id',$order_good['proid'])->update(['comment_num'=>$countnum,'comment_score'=>$score]);
			}
			return $this->json(['status'=>1,'msg'=>'评价成功']);
		}
		$rdata = [];
		$rdata['og'] = $og;
		$rdata['comment'] = $comment;
		return $this->json($rdata);
	}
	
	//商品海报
	public function getposter(){
		$this->checklogin();
		$post = input('post.');
		$platform = platform;
		$page = '/activity/scoreshop/product';
		$scene = 'id_'.$post['proid'].'-pid_'.$this->member['id'];
		//if($platform == 'mp' || $platform == 'h5' || $platform == 'app'){
		//	$page = PRE_URL .'/h5/'.aid.'.html#'. $page;
		//}
		$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','scoreshop')->where('platform',$platform)->order('id')->find();

		$posterdata = Db::name('member_poster')->where('aid',aid)->where('mid',mid)->where('scene',$scene)->where('type','scoreshop')->where('posterid',$posterset['id'])->find();
		if(true || !$posterdata){
			$product = Db::name('scoreshop_product')->where('id',$post['proid'])->find();
			$sysset = Db::name('admin_set')->where('aid',aid)->find();
			$textReplaceArr = [
				'[头像]'=>$this->member['headimg'],
				'[昵称]'=>$this->member['nickname'],
				'[姓名]'=>$this->member['realname'],
				'[手机号]'=>$this->member['mobile'],
				'[商城名称]'=>$sysset['name'],
				'[商品名称]'=>$product['name'],
				'[商品销售价]'=>$product['score_price'].t('积分').($product['money_price']>0?'+'.$product['money_price'].'元':''),
				'[商品市场价]'=>$product['sell_price'],
				'[商品图片]'=>$product['pic'],
			];

			$poster = $this->_getposter(aid,$product['bid'],$platform,$posterset['content'],$page,$scene,$textReplaceArr);
			$posterdata = [];
			$posterdata['aid'] = aid;
			$posterdata['mid'] = $this->member['id'];
			$posterdata['scene'] = $scene;
			$posterdata['page'] = $page;
			$posterdata['type'] = 'scoreshop';
			$posterdata['poster'] = $poster;
			$posterdata['createtime'] = time();
			Db::name('member_poster')->insert($posterdata);
		}
		return $this->json(['status'=>1,'poster'=>$posterdata['poster']]);
	}

    //分类商品样式
    public function classify2(){
        if(getcustom('scoreshop_classify2')){
            $clist = Db::name('scoreshop_category')->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
            foreach($clist as $k=>$v){
                $child = Db::name('scoreshop_category')->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$child) $child = [];
                foreach($child as $k2=>$v2){
                    $child2 = Db::name('scoreshop_category')->where('aid',aid)->where('pid',$v2['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
                    $child[$k2]['child'] = $child2;
                }
                $clist[$k]['child'] = $child;
            }
            $btntype    = 0;//购物车事件类型
            return $this->json(['status'=>1,'data'=>$clist,'btntype'=>$btntype]);
        }
    }
    // 全屏广告看完奖励
    public function givescore(){
    	if(getcustom('ad_adset') && input('param.adpid')){
    		$adpid = input('param.adpid');
    		$score = 0;
    		$time = strtotime(date("Y-m-d",time()));
    		$sysset = Db::name('scoreshop_sysset')->where('aid',aid)->find();
    		if($sysset['adset_show'] == 1 && $adpid == $sysset['adpid'] && !empty($sysset['config_data'])){

    			$levelid = $this->member['levelid'];
    			$config_data = json_decode($sysset['config_data'],true);
    			foreach ($config_data as $key => $value) {
    				if($value['levelid'] == $levelid && $value['score'] > 0){
    					// 限制
    					$where = [];
    					$where[] = ['aid','=',aid];
    					$where[] = ['mid','=',mid];
    					$where[] = ['createtime','>',$time];
    					$total_score = Db::name('adset_log')->where($where)->sum('score');
    					if($total_score >= $value['scoremax']){
    						break;
    					}
    					if($value['score'] > $value['scoremax'] - $total_score){
    						$value['score'] = $value['scoremax'] - $total_score;
    					}
    					if($value['score'] > 0){
    						\app\common\Member::addscore(aid,mid,$value['score'],'全屏广告看完奖励');
    						// 记录
    						$data = [];
    						$data['aid'] = aid;
    						$data['mid'] = mid;
    						$data['score'] = $value['score'];
    						$data['createtime'] = time();
    						Db::name('adset_log')->insert($data);

    						$score = $value['score'];
    					}
    					break;
    				}
    			}
    		}
			return $this->json(['status'=>1,'msg'=>'获得奖励'.$score.'分']);
    	}
		
	}
}
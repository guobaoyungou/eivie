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

use AdaPaySdk\Refund;
use think\facade\Db;
class ApiShop extends ApiCommon{
	public function initialize()
    {
        parent::initialize();
        $bid = input('param.bid/d');
        //记录接口访问请求的bid
        if($bid > 0) cache($this->sessionid.'_api_bid',$bid,3600);
    }
	public function getprolist(){
        $mendian_id = input('param.mendian_id/d',0);
		$where = [];
		$where[] = ['a.aid','=',aid];
		$where[] = ['a.ischecked','=',1];

		if(isdouyin == 1){
			$where[] = ['a.douyin_product_id','<>',''];
		}else{
			$where[] = ['a.douyin_product_id','=',''];
		}
        if(getcustom('product_bind_mendian')){
            if($mendian_id>0){
                $where[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
            }
        }

        $shopset = Db::name('shop_sysset')->where('aid', aid)->find();

		//$where[] = ['status','=',1];
		$nowtime = time();
		$nowhm = date('H:i');
		$where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");
		
		if(input('param.field') && input('param.order')){
			$order = 'a.'.input('param.field').' '.input('param.order').',a.sort desc,a.id desc';
		}else{
			$order = 'a.sort desc,a.id desc';
		}
		if(input('param.bid')){
			$where[] = ['a.bid','=',input('param.bid/d')];
		}else{
			$business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
			if(!$business_sysset || $business_sysset['status']==0 || $business_sysset['product_isshow']==0){
				if(!input('param.cpid')){
					$where[] = ['a.bid','=',0];
				}
			}else{
				if(getcustom('prolist_showjuli') && input('param.cid')){
					$latitude = input('param.latitude/f');
					$longitude = input('param.longitude/f');
					if($longitude && $latitude){
						$border = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) ");
						$blist = Db::name('business')->where('aid',aid)->where('status',1)->where("longitude!='' and latitude!=''")->field('id,longitude,latitude')->order($border)->select()->toArray();
						$bids = [];
						$bjuli = [];
						$b0juli = getdistance($longitude,$latitude,$this->sysset['longitude'],$this->sysset['latitude'],2);
						foreach($blist as $binfo){
							$juli = getdistance($longitude,$latitude,$binfo['longitude'],$binfo['latitude'],2);
							if($juli > $b0juli && !in_array('0',$bids)){
								$bids[] = '0';
								$bjuli['0'] = ''.$b0juli.'km';
							}
							$bids[] = $binfo['id'];
							$bjuli[''.$binfo['id']] = ''.$juli.'km';
						}
						if(!input('param.field') || input('param.field') == 'sort'){
							$order = Db::raw('field(a.bid,'.implode(',',$bids).'),a.sort desc,a.id desc');
						}
					}
				}
			}
		}
		//分类 
		if(input('param.cid')){
			$cid = input('post.cid') ? input('post.cid/d') : input('param.cid/d');
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			//子分类
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$cid)->column('id');
			if($clist){
				$clist2 = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid','in',$clist)->column('id');
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
		} else {
            if(getcustom('product_cat_showtj')) {
                $where2 = "find_in_set('-1',showtj)";
                if($this->member){
                    $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                    if($this->member['subscribe']==1){
                        $where2 .= " or find_in_set('0',showtj)";
                    }
                }
                $tjwhere[] = Db::raw($where2);
                $clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->column('id');
                if($clist){
                    $whereCid = [];
                    foreach($clist as $k => $c2){
                        $whereCid[] = "find_in_set({$c2},cid)";
                    }
                    $where[] = Db::raw(implode(' or ',$whereCid));
                }
            }
        }
		
		//商家的商品分类 
		if(input('param.cid2')){
			$cid2 = input('post.cid2') ? input('post.cid2/d') : input('param.cid2/d');
			//子分类
			$clist = Db::name('shop_category2')->where('aid',aid)->where('pid',$cid2)->column('id');
			if($clist){
				$clist2 = Db::name('shop_category2')->where('aid',aid)->where('pid','in',$clist)->column('id');
				$cCate = array_merge($clist, $clist2, [$cid2]);
				if($cCate){
					$whereCid = [];
					foreach($cCate as $k => $c2){
						$whereCid[] = "find_in_set({$c2},cid2)";
					}
                    $where[] = Db::raw(implode(' or ',$whereCid));
				}
			} else {
                $where[] = Db::raw("find_in_set(".$cid2.",cid2)");
            }
		}
		if(input('param.gid')) {
            if(getcustom('shop_purchase_order')) {
                $gid = input('param.gid');
                $gidArr = [];
                if (is_array($gid)) {
                    foreach ($gid as$value) {
                        $value = intval($value);
                        $gidArr[] = "find_in_set(" .$value . ", `gid`)";
                    }
                    // 使用AND连接
                    $gidWhere = implode(' AND ',$gidArr);
                    $where[] = Db::raw($gidWhere);
                } elseif (is_numeric($gid)) {
                    $gid = intval($gid);
                    $where[] = Db::raw("find_in_set({$gid}, `gid`)");
                }
            }else{
                $where[] = Db::raw("find_in_set(".intval(input('param.gid')).",gid)");
            }
        }
		
		
		if(input('param.proparams')){
			$proparams = input('param.proparams');
			$whereparam = [];
			foreach($proparams as $paramkey=>$paramval){
				if(!$paramval) continue;
				$whereparam[] = "paramdata like '%".'"'.$paramkey.'":"'.$paramval.'"'."%'";
			}
			if($whereparam){
				$where[] = Db::raw(implode(' and ',$whereparam));
			}
		}

		if(input('param.keyword')){
            $searchField = 'a.name|a.sellpoint|a.procode|b.name|b.procode';
            if(getcustom('product_keyword')){
                $searchField .= "|a.keyword";
            }
			$where[] = ["{$searchField}",'like','%'.input('param.keyword').'%'];
		}

        if(input('param.is_coupon/d',0) == 0){
            $where2 = "find_in_set('-1',a.showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',a.showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',a.showtj)";
                }
            }else{
                $where2 .= " or find_in_set('-2',a.showtj)";
            }
            $where[] = Db::raw($where2);
        }


		//优惠券可用商品列表
		$cpid = input('param.cpid/d');
		if($cpid > 0){
			$coupon = Db::name('coupon')->where('id',$cpid)->find();
			if($coupon['bid'] == 0 && $coupon['canused_bids'] == 'all'){
			
			}elseif($coupon['bid'] == 0 && $coupon['canused_bids']){
				$where[] = ['a.bid','in',$coupon['canused_bids']];
			}elseif($coupon['bid'] == 0 && $coupon['canused_bcids']){
				$canused_bids = [];
				foreach(explode(',',$coupon['canused_bcids']) as $bcid){
					$thisbids = Db::name('business')->where('aid',aid)->whereRaw('find_in_set('.$bcid.',cid)')->column('id');
					if($thisbids) $canused_bids = array_merge($canused_bids,$thisbids);
				}
				$where[] = ['a.bid','in',$canused_bids];
			}else{
				$where[] = ['a.bid','=',$coupon['bid']];
			}
			if($coupon['fwtype']==1){ //指定类目
				$categoryids = explode(',',$coupon['categoryids']);
				$clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
				foreach($clist as $kc=>$vc){
					$categoryids[] = $vc['id'];
					$cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
					if($cate2) $categoryids[] = $cate2['id'];
				}
				$whereCid = [];
				foreach($categoryids as $k => $c2){
					$whereCid[] = "find_in_set({$c2},cid)";
				}
				$where[] = Db::raw(implode(' or ',$whereCid));
			}
			if($coupon['fwtype']==6){ //指定商家类目
				$categoryids2 = explode(',',$coupon['categoryids2']);
				$clist2 = Db::name('shop_category2')->where('pid','in',$categoryids2)->select()->toArray();
				foreach($clist2 as $kc=>$vc){
					$categoryids2[] = $vc['id'];
					$cate2 = Db::name('shop_category2')->where('pid',$vc['id'])->find();
					if($cate2) $categoryids2[] = $cate2['id'];
				}
				$whereCid2 = [];
				foreach($categoryids2 as $k => $c2){
					$whereCid2[] = "find_in_set({$c2},cid)";
				}
				$where[] = Db::raw(implode(' or ',$whereCid2));
			}
			if($coupon['fwtype']==2){ //指定商品
				$where[] = ['a.id','in',$coupon['productids']];
			}
		}
		$pernum = 10;
		$pagenum = input('post.pagenum/d');
		if(!$pagenum) $pagenum = 1;
        $field = 'a.id,a.bid,a.pic,a.name,a.sales,a.market_price,a.sell_price,a.lvprice,a.lvprice_data,a.sellpoint,a.fuwupoint,a.price_type,a.stock,a.sellpoint,a.product_type,a.guigedata';
        if(getcustom('plug_tengrui')) {
            $field .= ',a.house_status,a.group_status,a.group_ids,a.is_rzh,a.relation_type';
        }
        if(getcustom('shop_other_infor')) {
            $field .= ',a.xunjia_text';
        }
        if(getcustom('product_xunjia_btn')) {
            $field .= ',a.xunjia_text,a.show_xunjia_btn,a.xunjia_btn_bgcolor,a.xunjia_btn_color,a.xunjia_btn_url';
        }
        if(getcustom('product_unit')) {
            $field .= ',a.product_unit';
        }
        if(getcustom('product_cost_show') || getcustom('product_show_costprice_fanwei')) {
            $field .= ',a.cost_price';
        }
        if(getcustom('product_service_fee')) {
            $field .= ',a.service_fee,a.service_fee_switch,a.service_fee_data';
        }
        if(getcustom('product_pingce')){
            $field .= ',a.product_type';
        }
        if(getcustom('product_show_guige_type')){
            $field .= ',a.guige_show_type';
        }

        if(getcustom('show_location')){
            //定位模式
            $sysset = Db::name('admin_set')->where('aid',aid)->field('id,mode,loc_range_type,loc_range,loc_area_type')->find();
            if($sysset['mode']==2){
                $area = input('param.area');
                $longitude = input('param.longitude/f');
                $latitude = input('param.latitude/f');
                $b_where = [];
                $b_where[] = ['aid','=',aid];
                $b_where[] = ['status','=',1];
                $b_where[] = ['is_open','=',1];
                if($sysset['loc_range_type']==1){
                    if($latitude && $longitude){
                        $b_distance = $sysset['loc_range']?$sysset['loc_range']*1000:0;
                        $b_having = "round(6378.138*2*asin(sqrt(pow(sin( ({$latitude}*pi()/180-latitude*pi()/180)/2),2)+cos({$latitude}*pi()/180)*cos(latitude*pi()/180)* pow(sin( ({$longitude}*pi()/180-longitude*pi()/180)/2),2)))*1000) <={$b_distance}";
                        $limitBusiness = Db::name('business')->having($b_having)->where($b_where)->select()->toArray();
                        if($limitBusiness){
                            $limitBids = array_column($limitBusiness,'id');
                            $limitBids[] = 0;//追加平台0
                            $where[] = ['a.bid','in',$limitBids];
                        }else{
                            $where[] = ['a.bid','=',0];//仅显示平台商品
                        }
                    }
                }else{
                    //同城
                    if($area){
                        //取省或者市
                        $areaArr = explode(',',$area);
                        $areaKey = min(count($areaArr)-1,1);//最小范围取到市
                        $areaName = $areaArr[$areaKey];
                        $b_where[] = ['city','=',$areaName];
                        $limitBids = Db::name('business')->where($b_where)->column('id');
                        if($limitBids){
                            $limitBids[] = 0;
                            $where[] = ['a.bid','in',$limitBids];
                        }else{
                            $where[] = ['a.bid','=',0];//仅显示平台商品
                        }
                    }
                }
            }
            //定位模式
        }
		if(getcustom('order_add_mobile')){
			if(input('param.order_add_mobile') == 1 && input('param.is_coupon/d',0) == 0){
				$where[] = ['a.bid','=',0];//手机端订单录入仅显示平台商品
			}
			
		}
        if(getcustom('shop_category_page')){
            $pageid =  input('param.pageid/d',0);
            if($pageid){
                $pagecids = Db::name('shop_category_page')->where('aid',aid)->where('id',$pageid)->value('cids');
                if($pagecids){
                    $pagecids = explode(',',$pagecids);
                    $wherePageCid = [];
                    foreach($pagecids as $k => $cc2){
                        $wherePageCid[] = "find_in_set({$cc2},cid)";
                    }
                    $where[] = Db::raw(implode(' or ',$wherePageCid));   
                }else{
                    $where[] = Db::raw("find_in_set(-1,cid)");
                }
            }
        }

        if(getcustom('product_brand')){
            $brand_id = input('param.brand_id/d');
            if($brand_id){
                $where[] = ['a.brand_id','=',$brand_id];
            }
        }
        if(getcustom('extend_exchange_card')){
            //预售类型的不展示，用与兑换卡使用
            $where[] = ['product_type' ,'<>',11];
        }
		//$datalist = Db::name('shop_product')->field($field)->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		$datalist = Db::name('shop_product')->alias('a')->join('shop_guige b','a.id=b.proid','left')->field($field)->where($where)->page($pagenum,$pernum)->group('a.id')->order($order)->select()->toArray();
		//$sql = Db::getlastsql();dd($sql);
		if(!$datalist) $datalist = [];
		if(getcustom('product_wholesale')){
			foreach($datalist as $k=>$v){
				if($v['product_type'] == 4){
					$guigedata = json_decode($v['guigedata'],true);
					$datalist[$k]['gg_num'] =  count($guigedata);
				}
			}
		}

		if(getcustom('prolist_showjuli') && input('param.cid') && $bjuli){
			foreach($datalist as $k=>$v){
				$datalist[$k]['juli'] = $bjuli[''.$v['bid']];
			}
		}
        if(getcustom('plug_tengrui')) {
            if($datalist){
                $tr_check = new \app\common\TengRuiCheck();
                foreach($datalist as $dk=>$dv){
                    //判断是否是否符合会员认证、会员关系、一户、用户组，不符合则直接去掉
                    $check_product = $tr_check->check_product($this->member,$dv,1);
                    if($check_product && $check_product['status'] == 0 ){
                        unset($datalist[$dk]);
                    }
                }
                unset($dv);
                $len = count($datalist);
                if($len<10){
                    //重置索引,防止上方去掉的数据产生空缺
                    $datalist=array_values($datalist);
                }
            }
        }

        if($datalist){
        	if(getcustom('shop_other_infor')){
	            $sysset = Db::name('admin_set')->where('aid',aid)->field('name,main_business,tel')->find();
	        }

            foreach($datalist as $dk=>&$dv){
            	if(getcustom('shop_other_infor')){
                    //特别标识
                    $dv['is_soi'] = 1;
                    
                    //联系系统名称
                    $dv['lx_name'] = $sysset['name']?$sysset['name']:'';
                    //联系商家id
                    $dv['lx_bid']  = $dv['bid'];
                    //联系商家名称
                    $dv['lx_bname']  = '';
                    //联系电话
                    $dv['lx_tel']  = '';

                    $dv['merchant_name'] = '';
                    $dv['main_business'] = '';
                    //查询商家
                    if($dv['bid']>0){
                        $merchant_name =  Db::name('business')
                            ->where('id',$dv['bid'])
                            ->where('aid',aid)
                            ->field('name,main_business,tel')
                            ->find();
                        if($merchant_name){
                            $dv['merchant_name'] = $merchant_name['name'];
                            $dv['main_business'] = $merchant_name['main_business'];

                            //联系商家名称
                            $dv['lx_bname']  = $merchant_name['name']?$merchant_name['name']:'';
                            //联系电话
                            $dv['lx_tel']    = $merchant_name['tel']?$merchant_name['tel']:'';
                        }
                    }else{
                        $dv['merchant_name'] = $sysset['name'];
                        $dv['main_business'] = $sysset['main_business'];

                        //联系电话
                        $dv['lx_tel']    = $sysset['tel']?$sysset['tel']:'';
                    }
		        }
                if(getcustom('member_level_price_show')){
                    //获取第一个规格的会员等级价格
                    $priceshows = [];
                    $price_show = 0;
                    $price_show_text = '';
                }

                $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$dv['id'])->select()->toArray();
                if(getcustom('shop_showgivescore')){
					$showgivescore1 = -1;//最小赠送积分
					$showgivescore2 = -1;//最大赠送积分
				}
                foreach($gglist as $gk=>$gv){
                    if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        if($gk == 0 && $dv['lvprice'] == 1 && $gv['lvprice_data']){
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
                                        $dv['sell_putongprice'] = $lv;
                                    }
                                    $lk ++ ;
                                }
                                unset($lid);unset($lv);
                            }
                        }
                    }
                    if(getcustom('shop_showgivescore')){
                    	$sgres = \app\common\System::showgivescore($shopset['showgivescore'],$showgivescore1,$showgivescore2,$gv['givescore']);
                    	if($sgres){
                    		$showgivescore1 = $sgres['showgivescore1'];//最小赠送积分
							$showgivescore2 = $sgres['showgivescore2'];//最大赠送积分
                    	}
                    }
                }
                unset($gk);unset($gv);
                if(getcustom('shop_showgivescore')){
                    $dv['showgivescore'] = \app\common\System::showgivescore2($shopset['showgivescore'],$showgivescore1,$showgivescore2);
                }

                if(getcustom('member_level_price_show')){
                    //获取第一个规格的会员等级价格
                    $dv['priceshows'] = $priceshows?$priceshows:'';
                    $dv['price_show'] = $price_show;
                    $dv['price_show_text'] = $price_show_text;
                }
            }
            unset($dk);unset($dv);
        }
     
		$datalist = $this->formatprolist($datalist);

		if(getcustom('price_dollar')){
			$usdrate = $shopset['usdrate'];
			foreach($datalist as &$d){
				if($usdrate>0){
					$d['showprice_dollar'] = true;
					$d['usd_sellprice'] = round($d['sell_price']/$usdrate,2);
				}
			}
		}

        //价格显示方式
        if(getcustom('price_show_type')){
            if($this->member){
                $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
            }
           
            $price_show_type = Db::name('shop_sysset')->where('aid',aid)->value('price_show_type');
            $defalut_level = Db::name('member_level')->where('aid',aid)->order('id asc')->find();
            foreach($datalist as $pk=>&$pv){
                $pv['price_show_type'] = $price_show_type;
               
                if(empty($userlevel) || $defalut_level['id'] == $this->member['levelid']){
                    $pv['is_vip'] =0;
                }else{
                    $pv['is_vip'] =1;
                }
                $pv['sell_price_origin'] = $pv['sell_price_origin'];
                if(in_array($price_show_type,[1,2])){ //开启会员价
                    $lvprice_data =json_decode($pv['lvprice_data'],true);
                    
                    if($pv['is_vip'] == 0){//不是会员 查询下个会员
                        $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$userlevel['sort'])->order('sort,id')->find();
                        
                        if(empty($nextlevel) || $pv['lvprice'] ==0 ){
                            $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$defalut_level['sort'])->order('sort,id')->find();
                        }
                        $level_name = $nextlevel['name'];
                        $pv['sell_price_origin'] = $lvprice_data[$nextlevel['id']];
                    }else{
                        if($userlevel && $pv['lvprice'] ==1 ){
                            $level_name = $userlevel['name'];
                        }
                    }
                    $pv['level_name_show'] =  $level_name;
                }
                
            }
        }
		
        //价格显示方式
        if(getcustom('price_show_type')){
            if($this->member){
                $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
            }
            foreach($datalist as &$pv){
                if($userlevel && $pv['lvprice'] ==1 ){
                    $pv['level_name'] =  $userlevel['name'];
                }
                if($pv['lvprice_data']){
                    $lvprice_data = json_decode($pv['lvprice_data'],true);
                    $pv['level_price'] = $lvprice_data[$this->member['levelid']];
                }else{
                    $pv['level_price'] = 0;
                }
                
            }
            
            $defalut_level = Db::name('member_level')->where('aid',aid)->order('id asc')->find();
            if($defalut_level['id'] == $this->member['levelid']){
                $product['is_vip'] =0;
            }else{
                $product['is_vip'] =1;
            }
        }
		//未登录查看价格
		if(getcustom('show_price_unlogin')){
			$mid = mid;
			if(!$mid && $shopset['is_show_price_unlogin'] == 0){
				foreach($datalist as &$pv){
					$pv['sell_price'] =  $shopset['show_price_unlogin_txt'];					
				}
			}			
		}
		//未审核查看价格
		if(getcustom('show_price_uncheck')){
			if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){
				foreach($datalist as &$pv){
					$pv['sell_price'] =  $shopset['show_price_uncheck_txt'];					
				}
			}			
		}

        if(getcustom('product_show_price_fanwei') && $shopset['product_show_price_fanwei'] ==1){
            foreach ($datalist as &$dv){
                $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$dv['id'])->select()->toArray();
                if(count($gglist) > 1)
                {
                    $jgarr = [];
                    foreach($gglist as $gek=>$gev){
                        if($dv['lvprice'] == 1 && $gev['lvprice_data'] && $this->member){
                            //会员价格
                            $lvprice_data = json_decode($gev['lvprice_data'],true);
                            foreach ($lvprice_data as $lid=>$lv){
                                if($this->member['levelid'] == $lid){
                                    $jgarr[] = $lv;
                                }
                            }
                        }else{
                            $jgarr[] = $gev['sell_price'];
                        }
                    }
                    $dv['min_price'] = min($jgarr);
                    $dv['max_price'] = max($jgarr);

                    if($dv['min_price'] == 0 && $dv['max_price'] == 0){
                        $dv['product_show_price_fanwei'] = 0;
                    }else if($dv['min_price'] == $dv['max_price']){
                        $dv['product_show_price_fanwei'] = $dv['min_price'];
                    }else{
                        $dv['sell_price'] = sprintf('%g',$dv['min_price']).'-'.sprintf('%g',$dv['max_price']);
                    }
                }
            }
            unset($dv);
        }

        if(getcustom('product_show_costprice_fanwei') ){
            if($shopset['product_show_costprice_fanwei'] ==1){
                $costprice_levelids = explode(',',$shopset['costprice_levelids']);
                if(in_array($this->member['levelid'],$costprice_levelids) || in_array('-1',$costprice_levelids)){
                    foreach ($datalist as &$dv){
                        $dv['product_show_costprice_fanwei'] = 1;
                        //关闭划线市场价
                        //$dv['market_price'] = 0;

                        $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$dv['id'])->select()->toArray();
                        if($gglist)
                        {
                            $jgarr = [];
                            foreach($gglist as $gek=>$gev){
                                $jgarr[] = $gev['cost_price'];
                            }
                            $dv['min_price'] = min($jgarr);
                            $dv['max_price'] = max($jgarr);

                            if($dv['min_price'] == 0 && $dv['max_price'] == 0){
                                $dv['cost_price_fanwei'] = $dv['cost_price'];
                            }else if($dv['min_price'] == $dv['max_price']){
                                $dv['cost_price_fanwei'] = $dv['min_price'];
                            }else{
                                $dv['cost_price_fanwei'] = sprintf('%g',$dv['min_price']).'-'.sprintf('%g',$dv['max_price']);
                            }
                        }else{
                            $dv['cost_price_fanwei'] = $dv['cost_price'];
                        }

                        if(is_null($dv['cost_price_fanwei'])){
                            $dv['product_show_costprice_fanwei'] = 0;
                        }
                    }
                    unset($dv);
                }
            }
        }

        if(getcustom('product_show_marketprice') ){
            if($shopset['product_show_marketprice'] ==0){
                foreach ($datalist as &$dv){
                    //关闭划线市场价
                    $dv['market_price'] = 0;
                }
                unset($dv);
            }
        }

        $shopset = [
            'classify_show_stock'=>$shopset['classify_show_stock'],
            'hide_sales' => $shopset['hide_sales'] == 0 ? 1 : 0, //后台配置和前端相反
            'hide_stock' => $shopset['hide_stock'] == 0 ? 1 : 0 //后台配置和前端相反
        ];
		return $this->json(['status'=>1,'data'=>$datalist,'shopset'=>$shopset]);
	}
	public function prolist(){
        $where2 = "find_in_set('-1',showtj)";
        if($this->member){
            $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
            if($this->member['subscribe']==1){
                $where2 .= " or find_in_set('0',showtj)";
            }
        }
        $tjwhere[] = Db::raw($where2);
		//分类
		if(input('param.cid')){
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',input('param.cid/d'))->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}else{
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
		}
		if(input('param.bid')){
			$bid = input('param.bid/d');
			if(input('param.cid2')){
				$clist2 = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',input('param.cid2/d'))->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$clist2) $clist2 = [];
			}else{
				$clist2 = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$clist2) $clist2 = [];
			}
		}else{
			$clist2 = [];
		}
		//分组
		$glist = Db::name('shop_group')->where('aid',aid)->where('status',1)->select()->toArray();
		if(!$glist) $glist = [];
		
		$rsset=[];
        if(getcustom('image_search')){
            $image_search = Db::name('admin')->where('id',aid)->value('image_search');
            if($image_search == 1) {

                $where = [];
                $where[] = ['aid','=',aid];
                $image_search_business_switch = Db::name('baidu_set')->where('aid',aid)->where('bid',0)->value('image_search_business_switch');
                if($image_search_business_switch){
                    $bid = input('param.bid/d')?:0;
                    $where[] = ['bid','=',$bid];
                }else{
                    $where[] = ['bid','=',0];
                }
                $set = Db::name('baidu_set')->where($where)->find();
                $rsset['image_search'] = $set['image_search'];
            }
        }
		$topshowcategory = 0;
        if(getcustom('ngmm')){
			$topshowcategory = 1;
			if(!$clist && input('param.cid')){
				$thiscinfo = Db::name('shop_category')->where('aid',aid)->where('id',input('param.cid'))->find();
				$clist = Db::name('shop_category')->where('aid',aid)->where('pid',$thiscinfo['pid'])->where('status',1)->order('sort desc,id')->select()->toArray();
			}
		}

        //商品分组支持多选
        $rsset['group_multi_select'] = 0;
        if(getcustom('shop_purchase_order')){
            $rsset['group_multi_select'] = 1;
        }
        $shopset = Db::name('shop_sysset')->where('aid',aid)->field('hide_sales,hide_stock')->find();

        $rsset['hide_sales'] = $shopset['hide_sales'] == 0 ? 1 : 0; //后台配置和前端相反
        $rsset['hide_stock'] = $shopset['hide_stock'] == 0 ? 1 : 0;//后台配置和前端相反

		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['glist'] = $glist;
		$rdata['clist2'] = $clist2;
		$rdata['set'] = $rsset;
		$rdata['topshowcategory'] = $topshowcategory;

		if(getcustom('prolist_showjuli')){
			$rdata['needlocation'] = true;
		}

        if(getcustom('shop_purchase_order')) {
            $rdata['list_sort_show'] = []; //显示销量和库存的排序

            $list_sort_show = Db::name('shop_sysset')->where('aid', aid)->value('list_sort_show');
            if ($list_sort_show) {
                $rdata['list_sort_show'] = explode(',', $list_sort_show);
            }
        }
		return $this->json($rdata);
	}
	public function getparamlist(){
		//参数
        $whereParam = [];
        $whereParam[] = ['aid','=',aid];
        $whereParam[] = ['status','=',1];
        $whereParam[] = ['type','=',1];
		if(input('param.bid')){
			$bid = input('param.bid/d');
		}else{
			$bid = 0;
		}
        if(input('param.cid') && input('param.cid') != 'undefined'){
			$cid = input('param.cid');
            if($cid){
				if(getcustom('business_showplatparam') && $bid > 0){
					$whereParam[] = Db::raw("(bid=0 and find_in_set({$cid},cid)) or (bid={$bid} and find_in_set({$cid},cid) or cid =''))");
				}else{
					$whereParam[] = ['bid','=',$bid];
					$whereParam[] = Db::raw("find_in_set({$cid},cid) or cid =''");
				}
            }else{
				$whereParam[] = ['bid','=',$bid];
                $whereParam[] = Db::raw("cid =''");
			}
        }else{
			$whereParam[] = ['bid','=',$bid];
            $whereParam[] = Db::raw(" cid =''");
        }
		$paramList = Db::name('shop_param')->where($whereParam)->order('sort desc,id')->select()->toArray();
		if(!$paramList) $paramList = [];
		foreach($paramList as $k=>$v){
			$paramList[$k]['params'] = json_decode($v['params'],true);
		}
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['data'] = $paramList;
		return $this->json($rdata);
	}
	
	//分类商品
	public function classify(){
		if(input('param.bid')){
			$clist = Db::name('shop_category2')->where('aid',aid)->where('bid',input('param.bid/d'))->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			foreach($clist as $k=>$v){
				$rs = Db::name('shop_category2')->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$rs) $rs = [];
				$clist[$k]['child'] = $rs;
			}
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
//			dd($clist);
			foreach($clist as $k=>$v){
				$rs = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$rs) $rs = [];
				$clist[$k]['child'] = $rs;
			}
		}
		return $this->json(['status'=>1,'data'=>$clist]);
	}
	//分类页分类列表
	public function getCategoryByPage(){
	    if(getcustom('shop_category_page')){
	        if(getcustom('product_cat_showtj')){
                $where2 = "find_in_set('-1',showtj)";
                if($this->member){
                    $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                    if($this->member['subscribe']==1){
                        $where2 .= " or find_in_set('0',showtj)";
                    }
                }
                $tjwhere[] = Db::raw($where2);
            }

            $pageid = input('param.id/d',0);
            $cids = Db::name('shop_category_page')->where('aid',aid)->where('id',$pageid)->value('cids');
            $cids = $cids?$cids:[];
            $tjwhere[] = ['id','in',$cids];
            $clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->select()->toArray();
            
            foreach($clist as $k=>$v){
                $child = Db::name('shop_category')->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$child) $child = [];
                $clist[$k]['child'] = $child;
            }
            return $this->json(['status'=>1,'data'=>$clist]);
        }
    }
    //分类页分类 获取商品
    public function getCategoryPageProlist(){
        if(getcustom('shop_category_page')) {
            $mendian_id = input('param.mendian_id/d', 0);
            $where = [];
            $where[] = ['a.aid', '=', aid];
            $where[] = ['a.ischecked', '=', 1];
            if (isdouyin == 1) {
                $where[] = ['a.douyin_product_id', '<>', ''];
            } else {
                $where[] = ['a.douyin_product_id', '=', ''];
            }
            if (getcustom('product_bind_mendian')) {
                if ($mendian_id > 0) {
                    $where[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
                }
            }
            //$where[] = ['status','=',1];
            $nowtime = time();
            $nowhm = date('H:i');
            $where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

            if (input('param.field') && input('param.order')) {
                $order = 'a.' . input('param.field') . ' ' . input('param.order') . ',a.sort desc,a.id desc';
            } else {
                $order = 'a.sort desc,a.id desc';
            }
            if (input('param.bid')) {
                $where[] = ['a.bid', '=', input('param.bid/d')];
            } else {
                $business_sysset = Db::name('business_sysset')->where('aid', aid)->find();
                if (!$business_sysset || $business_sysset['status'] == 0 || $business_sysset['product_isshow'] == 0) {
                    if (!input('param.cpid')) {
                        $where[] = ['a.bid', '=', 0];
                    }
                } else {
                    if (getcustom('prolist_showjuli') && input('param.cid')) {
                        $latitude = input('param.latitude/f');
                        $longitude = input('param.longitude/f');
                        if ($longitude && $latitude) {
                            $border = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) ");
                            $blist = Db::name('business')->where('aid', aid)->where('status', 1)->where("longitude!='' and latitude!=''")->field('id,longitude,latitude')->order($border)->select()->toArray();
                            $bids = [];
                            $bjuli = [];
                            $b0juli = getdistance($longitude, $latitude, $this->sysset['longitude'], $this->sysset['latitude'], 2);
                            foreach ($blist as $binfo) {
                                $juli = getdistance($longitude, $latitude, $binfo['longitude'], $binfo['latitude'], 2);
                                if ($juli > $b0juli && !in_array('0', $bids)) {
                                    $bids[] = '0';
                                    $bjuli['0'] = '' . $b0juli . 'km';
                                }
                                $bids[] = $binfo['id'];
                                $bjuli['' . $binfo['id']] = '' . $juli . 'km';
                            }
                            if (!input('param.field') || input('param.field') == 'sort') {
                                $order = Db::raw('field(a.bid,' . implode(',', $bids) . '),a.sort desc,a.id desc');
                            }
                        }
                    }
                }
            }
            //分类 
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
                            $whereCid[] = "find_in_set({$c2},cid)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
                } else {
                    $where[] = Db::raw("find_in_set(" . $cid . ",cid)");
                }
            } else {
                if (getcustom('product_cat_showtj')) {
                    $where2 = "find_in_set('-1',showtj)";
                    if ($this->member) {
                        $where2 .= " or find_in_set('" . $this->member['levelid'] . "',showtj)";
                        if ($this->member['subscribe'] == 1) {
                            $where2 .= " or find_in_set('0',showtj)";
                        }
                    }
                    $tjwhere[] = Db::raw($where2);
                    $clist = Db::name('shop_category')->where($tjwhere)->where('aid', aid)->column('id');
                    if ($clist) {
                        $whereCid = [];
                        foreach ($clist as $k => $c2) {
                            $whereCid[] = "find_in_set({$c2},cid)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
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
                            $whereCid[] = "find_in_set({$c2},cid2)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
                } else {
                    $where[] = Db::raw("find_in_set(" . $cid2 . ",cid2)");
                }
            }
            if (input('param.gid')) $where[] = Db::raw("find_in_set(" . intval(input('param.gid')) . ",gid)");


            if (input('param.proparams')) {
                $proparams = input('param.proparams');
                $whereparam = [];
                foreach ($proparams as $paramkey => $paramval) {
                    if (!$paramval) continue;
                    $whereparam[] = "paramdata like '%" . '"' . $paramkey . '":"' . $paramval . '"' . "%'";
                }
                if ($whereparam) {
                    $where[] = Db::raw(implode(' and ', $whereparam));
                }
            }

            if (input('param.keyword')) {
                $searchField = 'a.name|a.sellpoint|a.procode|b.name|b.procode';
                if (getcustom('product_keyword')) {
                    $searchField .= "|a.keyword";
                }
                $where[] = ["{$searchField}", 'like', '%' . input('param.keyword') . '%'];
            }

            $where2 = "find_in_set('-1',a.showtj)";
            if ($this->member) {
                $where2 .= " or find_in_set('" . $this->member['levelid'] . "',a.showtj)";
                if ($this->member['subscribe'] == 1) {
                    $where2 .= " or find_in_set('0',a.showtj)";
                }
            } else {
                $where2 .= " or find_in_set('-2',a.showtj)";
            }
            $where[] = Db::raw($where2);

            //优惠券可用商品列表
            $cpid = input('param.cpid/d');
            if ($cpid > 0) {
                $coupon = Db::name('coupon')->where('id', $cpid)->find();
                if ($coupon['bid'] == 0 && $coupon['canused_bids'] == 'all') {

                } elseif ($coupon['bid'] == 0 && $coupon['canused_bids']) {
                    $where[] = ['a.bid', 'in', $coupon['canused_bids']];
                } elseif ($coupon['bid'] == 0 && $coupon['canused_bcids']) {
                    $canused_bids = [];
                    foreach (explode(',', $coupon['canused_bcids']) as $bcid) {
                        $thisbids = Db::name('business')->where('aid', aid)->whereRaw('find_in_set(' . $bcid . ',cid)')->column('id');
                        if ($thisbids) $canused_bids = array_merge($canused_bids, $thisbids);
                    }
                    $where[] = ['a.bid', 'in', $canused_bids];
                } else {
                    $where[] = ['a.bid', '=', $coupon['bid']];
                }
                if ($coupon['fwtype'] == 1) { //指定类目
                    $categoryids = explode(',', $coupon['categoryids']);
                    $clist = Db::name('shop_category')->where('pid', 'in', $categoryids)->select()->toArray();
                    foreach ($clist as $kc => $vc) {
                        $categoryids[] = $vc['id'];
                        $cate2 = Db::name('shop_category')->where('pid', $vc['id'])->find();
                        if ($cate2) $categoryids[] = $cate2['id'];
                    }
                    $whereCid = [];
                    foreach ($categoryids as $k => $c2) {
                        $whereCid[] = "find_in_set({$c2},cid)";
                    }
                    $where[] = Db::raw(implode(' or ', $whereCid));
                }
                if ($coupon['fwtype'] == 2) { //指定商品
                    $where[] = ['a.id', 'in', $coupon['productids']];
                }
            }
            $pernum = 10;
            $pagenum = input('post.pagenum/d');
            if (!$pagenum) $pagenum = 1;
            $field = 'a.id,a.bid,a.pic,a.name,a.sales,a.market_price,a.sell_price,a.lvprice,a.lvprice_data,a.sellpoint,a.fuwupoint,a.price_type,a.stock,a.sellpoint,a.product_type,a.guigedata';
            if (getcustom('plug_tengrui')) {
                $field .= ',a.house_status,a.group_status,a.group_ids,a.is_rzh,a.relation_type';
            }
            if (getcustom('shop_other_infor')) {
                $field .= ',a.xunjia_text';
            }
            if (getcustom('product_xunjia_btn')) {
                $field .= ',a.xunjia_text,a.show_xunjia_btn,a.xunjia_btn_bgcolor,a.xunjia_btn_color,a.xunjia_btn_url';
            }
            if (getcustom('product_unit')) {
                $field .= ',a.product_unit';
            }
            if (getcustom('product_cost_show')) {
                $field .= ',a.cost_price';
            }

            if (getcustom('show_location')) {
                //定位模式
                $sysset = Db::name('admin_set')->where('aid', aid)->field('id,mode,loc_range_type,loc_range,loc_area_type')->find();
                if ($sysset['mode'] == 2) {
                    $area = input('param.area');
                    $longitude = input('param.longitude/f');
                    $latitude = input('param.latitude/f');
                    $b_where = [];
                    $b_where[] = ['aid', '=', aid];
                    $b_where[] = ['status', '=', 1];
                    $b_where[] = ['is_open', '=', 1];
                    if ($sysset['loc_range_type'] == 1) {
                        if ($latitude && $longitude) {
                            $b_distance = $sysset['loc_range'] ? $sysset['loc_range'] * 1000 : 0;
                            $b_having = "round(6378.138*2*asin(sqrt(pow(sin( ({$latitude}*pi()/180-latitude*pi()/180)/2),2)+cos({$latitude}*pi()/180)*cos(latitude*pi()/180)* pow(sin( ({$longitude}*pi()/180-longitude*pi()/180)/2),2)))*1000) <={$b_distance}";
                            $limitBusiness = Db::name('business')->having($b_having)->where($b_where)->select()->toArray();
                            if ($limitBusiness) {
                                $limitBids = array_column($limitBusiness, 'id');
                                $limitBids[] = 0;//追加平台0
                                $where[] = ['a.bid', 'in', $limitBids];
                            } else {
                                $where[] = ['a.bid', '=', 0];//仅显示平台商品
                            }
                        }
                    } else {
                        //同城
                        if ($area) {
                            //取省或者市
                            $areaArr = explode(',', $area);
                            $areaKey = min(count($areaArr) - 1, 1);//最小范围取到市
                            $areaName = $areaArr[$areaKey];
                            $b_where[] = ['city', '=', $areaName];
                            $limitBids = Db::name('business')->where($b_where)->column('id');
                            if ($limitBids) {
                                $limitBids[] = 0;
                                $where[] = ['a.bid', 'in', $limitBids];
                            } else {
                                $where[] = ['a.bid', '=', 0];//仅显示平台商品
                            }
                        }
                    }
                }
                //定位模式
            }
            if (getcustom('order_add_mobile')) {
                if (input('param.order_add_mobile') == 1) {
                    $where[] = ['a.bid', '=', 0];//手机端订单录入仅显示平台商品
                }

            }
            $datalist = Db::name('shop_product')->alias('a')->join('shop_guige b', 'a.id=b.proid', 'left')->field($field)->where($where)->page($pagenum, $pernum)->group('a.id')->order($order)->select()->toArray();
            if (!$datalist) $datalist = [];
            if (getcustom('product_wholesale')) {
                foreach ($datalist as $k => $v) {
                    if ($v['product_type'] == 4) {
                        $guigedata = json_decode($v['guigedata'], true);
                        $datalist[$k]['gg_num'] = count($guigedata);
                    }
                }
            }

            if (getcustom('prolist_showjuli') && input('param.cid') && $bjuli) {
                foreach ($datalist as $k => $v) {
                    $datalist[$k]['juli'] = $bjuli['' . $v['bid']];
                }
            }
            if (getcustom('plug_tengrui')) {
                if ($datalist) {
                    $tr_check = new \app\common\TengRuiCheck();
                    foreach ($datalist as $dk => $dv) {
                        //判断是否是否符合会员认证、会员关系、一户、用户组，不符合则直接去掉
                        $check_product = $tr_check->check_product($this->member, $dv, 1);
                        if ($check_product && $check_product['status'] == 0) {
                            unset($datalist[$dk]);
                        }
                    }
                    unset($dv);
                    $len = count($datalist);
                    if ($len < 10) {
                        //重置索引,防止上方去掉的数据产生空缺
                        $datalist = array_values($datalist);
                    }
                }
            }
            if (getcustom('shop_other_infor')) {
                if ($datalist) {
                    $sysset = Db::name('admin_set')->where('aid', aid)->field('name,main_business,tel')->find();
                    foreach ($datalist as &$v) {
                        //特别标识
                        $v['is_soi'] = 1;

                        //联系系统名称
                        $v['lx_name'] = $sysset['name'] ? $sysset['name'] : '';
                        //联系商家id
                        $v['lx_bid'] = $v['bid'];
                        //联系商家名称
                        $v['lx_bname'] = '';
                        //联系电话
                        $v['lx_tel'] = '';

                        $v['merchant_name'] = '';
                        $v['main_business'] = '';
                        //查询商家
                        if ($v['bid'] > 0) {
                            $merchant_name = Db::name('business')
                                ->where('id', $v['bid'])
                                ->where('aid', aid)
                                ->field('name,main_business,tel')
                                ->find();
                            if ($merchant_name) {
                                $v['merchant_name'] = $merchant_name['name'];
                                $v['main_business'] = $merchant_name['main_business'];

                                //联系商家名称
                                $v['lx_bname'] = $merchant_name['name'] ? $merchant_name['name'] : '';
                                //联系电话
                                $v['lx_tel'] = $merchant_name['tel'] ? $merchant_name['tel'] : '';
                            }
                        } else {
                            $v['merchant_name'] = $sysset['name'];
                            $v['main_business'] = $sysset['main_business'];

                            //联系电话
                            $v['lx_tel'] = $sysset['tel'] ? $sysset['tel'] : '';
                        }


                    }
                    unset($v);
                }
            }
            $datalist = $this->formatprolist($datalist);
            if (getcustom('price_dollar')) {
                $usdrate = Db::name('shop_sysset')->where('aid', aid)->value('usdrate');
                foreach ($datalist as &$d) {
                    if ($usdrate > 0) {
                        $d['showprice_dollar'] = true;
                        $d['usd_sellprice'] = round($d['sell_price'] / $usdrate, 2);
                    }
                }
            }

            //价格显示方式
            if (getcustom('price_show_type')) {
                if ($this->member) {
                    $userlevel = Db::name('member_level')->where('aid', aid)->where('id', $this->member['levelid'])->find();
                }

                $price_show_type = Db::name('shop_sysset')->where('aid', aid)->value('price_show_type');
                $defalut_level = Db::name('member_level')->where('aid', aid)->order('id asc')->find();
                foreach ($datalist as $pk => &$pv) {
                    $pv['price_show_type'] = $price_show_type;

                    if (empty($userlevel) || $defalut_level['id'] == $this->member['levelid']) {
                        $pv['is_vip'] = 0;
                    } else {
                        $pv['is_vip'] = 1;
                    }
                    $pv['sell_price_origin'] = $pv['sell_price_origin'];
                    if (in_array($price_show_type, [1, 2])) { //开启会员价
                        $lvprice_data = json_decode($pv['lvprice_data'], true);

                        if ($pv['is_vip'] == 0) {//不是会员 查询下个会员
                            $nextlevel = Db::name('member_level')->where('aid', aid)->where('sort', '>', $userlevel['sort'])->order('sort,id')->find();

                            if (empty($nextlevel) || $pv['lvprice'] == 0) {
                                $nextlevel = Db::name('member_level')->where('aid', aid)->where('sort', '>', $defalut_level['sort'])->order('sort,id')->find();
                            }
                            $level_name = $nextlevel['name'];
                            $pv['sell_price_origin'] = $lvprice_data[$nextlevel['id']];
                        } else {
                            if ($userlevel && $pv['lvprice'] == 1) {
                                $level_name = $userlevel['name'];
                            }
                        }
                        $pv['level_name_show'] = $level_name;
                    }

                }
            }

            //价格显示方式
            if (getcustom('price_show_type')) {
                if ($this->member) {
                    $userlevel = Db::name('member_level')->where('aid', aid)->where('id', $this->member['levelid'])->find();
                }
                foreach ($datalist as &$pv) {
                    if ($userlevel && $pv['lvprice'] == 1) {
                        $pv['level_name'] = $userlevel['name'];
                    }
                    if ($pv['lvprice_data']) {
                        $lvprice_data = json_decode($pv['lvprice_data'], true);
                        $pv['level_price'] = $lvprice_data[$this->member['levelid']];
                    } else {
                        $pv['level_price'] = 0;
                    }

                }

                $defalut_level = Db::name('member_level')->where('aid', aid)->order('id asc')->find();
                if ($defalut_level['id'] == $this->member['levelid']) {
                    $product['is_vip'] = 0;
                } else {
                    $product['is_vip'] = 1;
                }
            }
            //未登录查看价格
            if (getcustom('show_price_unlogin')) {
                $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                $mid = mid;
                if (!$mid && $shopset['is_show_price_unlogin'] == 0) {
                    foreach ($datalist as &$pv) {
                        $pv['sell_price'] = $shopset['show_price_unlogin_txt'];
                    }
                }
            }
            //未审核查看价格
            if (getcustom('show_price_uncheck')) {
                $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                if (mid && $this->member['checkst'] != 1 && $shopset['is_show_price_uncheck'] == 0) {
                    foreach ($datalist as &$pv) {
                        $pv['sell_price'] = $shopset['show_price_uncheck_txt'];
                    }
                }
            }

            return $this->json(['status' => 1, 'data' => $datalist]);
        } 
    }
	//获取子分类
	public function getdownclist(){
		$pid = input('param.id/d');
		$clist = Db::name('shop_category')->where('aid',aid)->where('pid',$pid)->where('status',1)->order('sort desc,id')->select()->toArray();
		if(!$clist) $clist = [];
		return $this->json(['status'=>1,'data'=>$clist]);
	}
	//分类商品样式二
	public function classify2(){
		if(input('param.bid')){
			$bid = input('param.bid/d');
			$clist = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			foreach($clist as $k=>$v){
				$child = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$child) $child = [];
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v2['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist[$k]['child'] = $child;
			}
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			foreach($clist as $k=>$v){
				$child = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$child) $child = [];
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v2['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist[$k]['child'] = $child;
			}
		}
		return $this->json(['status'=>1,'data'=>$clist]);
	}
	//分类商品样式三
	public function classify3(){
		if(input('param.bid')){
			$bid = input('param.bid/d');
			$clist = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			foreach($clist as $k=>$v){
				$child = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$child) $child = [];
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v2['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist[$k]['child'] = $child;
			}
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
			foreach($clist as $k=>$v){
				$child = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$child) $child = [];
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v2['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist[$k]['child'] = $child;
			}
		}
		return $this->json(['status'=>1,'data'=>$clist]);
	}

	//一级分类
	public function category1(){
		if(input('param.bid')){
			$list = Db::name('shop_category2')->where('aid',aid)->where('bid',input('param.bid/d'))->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$list = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
		}
		return $this->json(['status'=>1,'data'=>$list]);
	}
	//二级分类
	public function category2(){
		if(input('param.bid')){
			$cid = input('param.cid/d');
			$bid = input('param.bid/d');
			if($cid){
				$rs = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$rs) $rs = array();
				$list = $rs;
			}else{
				$list = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$list) $list = array();
				foreach($list as $k=>$v){
					$rs = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
					if(!$rs) $rs = array();
					$list[$k]['child'] = $rs;
				}
			}
		}else{
			$cid = input('param.cid/d');
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			if($cid){
				$rs = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$rs) $rs = array();
				$list = $rs;
			}else{
				$list = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$list) $list = array();
				foreach($list as $k=>$v){
					$rs = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
					if(!$rs) $rs = array();
					$list[$k]['child'] = $rs;
				}
			}
		}
		return $this->json(['status'=>1,'data'=>$list]);
	}
	//三级分类
	public function category3(){
		if(input('param.bid')){
			$bid = input('param.bid/d');
			$list = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$list = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
		}
		$rdata = [];
		$rdata['data'] = $list;
		return $this->json($rdata);
	}
	//获取二三分类
	public function getdownclist3(){
		$pid = input('param.id/d');
		if(input('param.bid')){
			$bid = input('param.bid/d');
			$clist = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$pid)->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
			foreach($clist as $k=>$v){
				$rs = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$rs) $rs = array();
				$clist[$k]['child'] = $rs;
			}
		}else{
			$clist = Db::name('shop_category')->where('aid',aid)->where('pid',$pid)->where('status',1)->order('sort desc,id')->select()->toArray();
			if(!$clist) $clist = [];
			foreach($clist as $k=>$v){
				$rs = Db::name('shop_category')->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
				if(!$rs) $rs = array();
				$clist[$k]['child'] = $rs;
			}
		}
		return $this->json(['status'=>1,'data'=>$clist]);
	}

    /**
     * 获取一二三级分类
     * @author: liud
     * @time: 2024/9/4 下午2:57
     */
    public function getCategory2Child(){
        if(input('param.bid')){
            $cid = input('param.cid/d');
            $bid = input('param.bid/d');
            if($cid){
                $rs = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$rs) $rs = array();
                $list = $rs;
            }else{
                $list = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$list) $list = array();
                foreach($list as $k=>$v){
                    $rs = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();

                    if($rs){
                        foreach($rs as $kk=>$vv){
                            $rss = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$vv['id'])->where('status',1)->order('sort desc,id')->select()->toArray();

                            $rs[$kk]['child'] = $rss ?? [];
                        }
                    }

                    $list[$k]['child'] = $rs ?? [];
                }
            }
        }else{
            $cid = input('param.cid/d');
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
            if($cid){
                $rs = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$rs) $rs = array();
                $list = $rs;
            }else{
                $list = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$list) $list = array();
                foreach($list as $k=>$v){
                    $rs = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();

                    if($rs){
                        foreach($rs as $kk=>$vv){
                            $rss = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$vv['id'])->where('status',1)->order('sort desc,id')->select()->toArray();

                            $rs[$kk]['child'] = $rss ?? [];
                        }
                    }

                    $list[$k]['child'] = $rs ?? [];
                }
            }
        }
        return $this->json(['status'=>1,'data'=>$list]);
    }
	
	//商品
	public function product(){
		$proid = input('param.id/d');
        //判断是否需要经纬度【如果门店模式，显示最近门店，经纬度必须】
        $needlocation = false;
        if(getcustom('product_bind_mendian')){
            $latitude = input('param.latitude/f','');
            $longitude = input('param.longitude/f','');
            $mendian_id = input('param.mendian_id/d','');
            if((empty($latitude) || empty($longitude))){
                $needlocation = true;
            }
        }

		$product = Db::name('shop_product')->where('id',$proid)->where('aid',aid)->find();
        if(getcustom('mendian_usercenter')){
            //门店中心独立产品库存
            if($this->member['lock_mdid']){
                $product = \app\custom\MendianUsercenter::getProductInfo(aid,$this->member['lock_mdid'],$product);
            }
        }

		if(!$product) return $this->json(['status'=>0,'msg'=>'商品不存在']);
		if($product['status']==0) return $this->json(['status'=>0,'msg'=>'商品未上架']);
		if($product['ischecked']!=1) return $this->json(['status'=>0,'msg'=>'商品未审核']);
		
		if($product['status']==2 && (strtotime($product['start_time']) > time() || strtotime($product['end_time']) < time())){
			return $this->json(['status'=>0,'msg'=>'商品未上架']);
		}
		if($product['status']==3){
			$start_time = strtotime(date('Y-m-d '.$product['start_hours']));
			$end_time = strtotime(date('Y-m-d '.$product['end_hours']));
			if(($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time >= $end_time && ($start_time > time() && $end_time < time()))){
				return $this->json(['status'=>0,'msg'=>'商品未上架']);
			}
		}
        if(getcustom('product_pickup_device')){
            $devicedata = input('param.devicedata');
            list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
            if($goods_lane && $device_no){
                $device_goods= Db::name('product_pickup_device_goods')->where('aid',aid)->where('goods_lane',$goods_lane)->where('device_no',$device_no)->find();
                 //模式是不固定柜门，不是指定柜门二维码，且库存是0的 
                $type = Db::name('product_pickup_device_set')->where('aid',aid)->value('type');
                if($device_goods['real_stock'] ==0 && $type ==0 && !$dgid){
                    //type=1固定柜门 0:根据商品查找其他的
                    $device_goods= Db::name('product_pickup_device_goods')->where('aid',aid)->where('device_no',$device_no)->where('proid',$product['id'])->where('ggid',$device_goods['ggid'])->where('real_stock','>',0)->find();
                }
                if($device_goods['real_stock'] ==0){
                    return $this->json(['status'=>0,'msg'=>'产品库存不足']);
                }
            }
        }
		$product['paramdata'] = json_decode($product['paramdata'],true);
		if(!$product['paramdata']) $product['paramdata'] = [];
		foreach($product['paramdata'] as $k=>$v){
			if(is_array($v)){
				$product['paramdata'][$k] = implode(' ',$v);
			}
			if(!$v) unset($product['paramdata'][$k]);
		}

		//显示条件
        $levelids = explode(',',$product['showtj']);
        //限制等级
        if(!in_array('-1',$levelids)){
            $showtj1 = false;
            $showtj2 = false;
            //-1 未登录
            if(!in_array('-2',$levelids) && !$this->member){
                $this->checklogin();
            }
            if(in_array('-2',$levelids) && !$this->member){
                $showtj1 = true;
            }
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
       
        //商品分类显示条件
        if(getcustom('product_cat_showtj')) {
            $cidArr = explode(',',$product['cid']);
            if($cidArr) {
                $clist = Db::name('shop_category')->where('id','in',$cidArr)->where('aid',aid)->select()->toArray();
                foreach($clist as $k => $crow){
                    if($crow['showtj'] > 0) {
                        $this->checklogin();
                        //限制等级
                        $levelids = explode(',',$crow['showtj']);
                        if(!in_array($this->member['levelid'], $levelids)) {
                            return $this->json(['status'=>0,'msg'=>'商品分类状态不可见']);
                        }
                    } elseif($crow['showtj'] == 0) {
                        $this->checklogin();
                        //关注用户
                        if($this->member['subscribe']!=1){
                            return $this->json(['status'=>0,'msg'=>'商品分类状态不可见']);
                        }
                    }
                }
            }

            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
            $clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->column('id');
            if($clist){
                foreach($clist as $k => $c2){
                    $whereCid[] = "find_in_set({$c2},cid)";
                }
                $where[] = Db::raw(implode(' or ',$whereCid));
            }
        }
        if(getcustom('pay_yuanbao')) {
            $yuanbao_money_ratio = 0;
            $yb_set = Db::name('admin_set')->where('aid',aid)->field('yuanbao_money_ratio')->find();
            if($yb_set){
                $yuanbao_money_ratio = $yb_set['yuanbao_money_ratio'];
            }
            $need_money = $product['yuanbao']*$yuanbao_money_ratio/100;
            $need_money = round($need_money,2);
            $product['yuanbao'] = $product['yuanbao'].t('元宝');
        }else{
            $product['yuanbao'] = '';
        }

        if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户、用户组
            $tr_check = new \app\common\TengRuiCheck();
            $check_product = $tr_check->check_product($this->member,$product,1);
            if($check_product && $check_product['status'] == 0 ){
                return $this->json(['status'=>$check_product['status'],'msg'=>$check_product['msg']]);
            }
            $tr_roomId = $check_product['tr_roomId'];
        }
        $showMoneyPrice = false;
        if(getcustom('product_show_moneyprice')){
            //product_moneypay 余额支付状态 0关闭 1开启 2仅限余额
            if($product['product_moneypay']==1 || $product['product_moneypay']==2){
                $showMoneyPrice = true;
            }
        }

		$product['isgm'] = true;
		if(getcustom('shopcate_time')){
			$procids = explode(',',$product['cid']);
			$cate = Db::name('shop_category')->where('aid',aid)->where('id','in',$product['cid'])->select()->toArray();
			foreach($cate as $c){
				if($c['start_hours'] && $c['end_hours']){
					$catestart_time =  strtotime(date('Y-m-d '.$c['start_hours']));
					$cateend_time =  strtotime(date('Y-m-d '.$c['end_hours']));
					if(($catestart_time < $cateend_time && ($catestart_time > time() || $cateend_time < time())) || ($catestart_time >= $cateend_time && ($catestart_time > time() && $cateend_time < time()))){
						$product['isgm']=false;
					}
				}
				
			}
		}

		if($product['status']==2 || $product['status']==3) $product['status']=1;

		if(!$product['pics']) $product['pics'] = $product['pic'];
		$product['pics'] = explode(',',$product['pics']);
		$oldproduct = $product;
		$product = $this->formatproduct($product);
        $videoType = 0;
        $feedtype = -1;
		if($product['video']){
            //如果是选择的视频管理的视频
            $isChooseVideo = preg_match('/\[视频ID:(\d+)\]/',$product['video']);
            if($isChooseVideo){
                $videoId = trim(explode(':',$product['video'])[1],']');
                $videoinfo = Db::name('video_list')->where('id',$videoId)->find();
                if($videoinfo && $videoinfo['type']==1 && $videoinfo['ext_param']){
                    //微信视频号
                    $videoType = 1;
                    $videoExt = json_decode($videoinfo['ext_param'],true);
                    $feedtype = $videoExt['feedtype'];
                    if($videoExt['feedtype']==1){
                        //视频号视频
                        $product['video_finderuser'] = $videoExt['finderuser'];
                        $product['video_feedid'] = $videoExt['feedid'];
                        $product['video_feedtoken'] = $videoExt['feedtoken'];
                    }else{
                        //视频号主页
                        $product['video'] = 'channelsUserProfile::'.$videoExt['finderuser'];
                    }
                }elseif($videoinfo && $videoinfo['video_url']){
                    $product['video_duration'] = $videoinfo['video_duration'];
                    $product['video'] = $videoinfo['video_url'];
                }else{
                    $product['video'] = '';
                }
            }
            if($videoType==0){
                $minute = floor($product['video_duration']/60);
                if($minute < 10) $minute = '0'.$minute;
                $second = $product['video_duration']%60;
                if($second < 10) $second = '0'.$second;
                $product['video_duration'] = $minute . "'" . $second . '"';
            }
		}else{
            $product['video'] = '';
        }
        $product['video_type'] = $videoType;
        $product['video_feedtype'] = $feedtype;

		//是否收藏
		$rs = Db::name('member_favorite')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('type','shop')->find();
		if($rs){
			$isfavorite = true;
		}else{
			$isfavorite = false;
		}
		//获取评论
		$commentlist = Db::name('shop_comment')->where('aid',aid)->where('proid',$proid)->where('status',1)->order('id desc')->limit(10)->select()->toArray();
		if(!$commentlist) $commentlist = [];
		foreach($commentlist as $k=>$pl){
			$commentlist[$k]['createtime'] = date('Y-m-d H:i',$pl['createtime']);
			if($commentlist[$k]['content_pic']) $commentlist[$k]['content_pic'] = explode(',',$commentlist[$k]['content_pic']);
		}
		$commentcount = Db::name('shop_comment')->where('aid',aid)->where('proid',$proid)->where('status',1)->count();
		//添加浏览历史
		if(mid){
			$rs = Db::name('member_history')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('type','shop')->find();
			if($rs){
				Db::name('member_history')->where('id',$rs['id'])->update(['createtime'=>time()]);
			}else{
				Db::name('member_history')->insert(['aid'=>aid,'mid'=>mid,'proid'=>$proid,'type'=>'shop','createtime'=>time()]);
			}
		}

        if(mid){
            //添加浏览历史商家
            $rs = Db::name('member_history')->where(array('aid'=>aid,'mid'=>mid,'proid'=>$product['bid'],'type'=>'business'))->find();
            if($rs){
                Db::name('member_history')->where(array('id'=>$rs['id']))->update(['createtime'=>time()]);
            }else{
                Db::name('member_history')->insert(array('aid'=>aid,'mid'=>mid,'proid'=>$product['bid'],'type'=>'business','createtime'=>time()));
            }
        }
        $custom = ['isshowall'=>true];
		$syssetField = 'name,logo,desc,fxjiesuantype,tel,kfurl,gzts,ddbb,mode';
		if(getcustom('product_promotion_tag')){
			$syssetField.=',fhjiesuantype';
		}
		if(getcustom('business_bind_show_page')){
			$syssetField.=',loc_business_show_type';
		}
		$sysset = Db::name('admin_set')->where('aid',aid)->field($syssetField)->find();

		$shopset_field = 'showjd,comment,showcommission,hide_sales,hide_stock,show_lvupsavemoney';
		if(getcustom('shop_prodetailtitle')){
			$shopset_field.=',prodetailtitle_type,prodetailtitle_value';
		}
        if(getcustom('product_comment')){
            $shopset_field.=',product_comment';
        }
        $custom_show_price = false;
        if(getcustom('product_cost_show')){
            $custom_show_price = true;
            $shopset_field.=',hide_cost,cost_name,cost_color';
        }
        if(getcustom('product_sellprice_show')){
            $custom_show_price = true;
            $shopset_field.=',hide_sellprice,sellprice_name,sellprice_color';
        }

        if(getcustom('product_detail_special')){
            $custom['product_detail_special'] = true;
            $custom_show_price = true;
            $shopset_field.=',show_product_name,show_guige,show_option_group,show_header_pic';
        }
		if(getcustom('product_guige_showtype')){
            $custom['product_guige_showtype'] = true;
            $shopset_field.=',guige_name,show_guigetype';
        }
		if(getcustom('shop_gwc_name')){
            $custom['shop_gwc_name'] = true;
            $shopset_field.=',gwc_showst,gwc_name';
        }
		if(getcustom('product_commission_desc')){
            $custom['product_commission_desc'] = true;
            $shopset_field.=',commission_desc';
        }
		//未登录查看价格
		if(getcustom('show_price_unlogin')){
			$shopset_field.=',is_show_price_unlogin,show_price_unlogin_txt';		
		}
		//未审核查看价格
		if(getcustom('show_price_uncheck')){
			$shopset_field.=',is_show_price_uncheck,show_price_uncheck_txt';		
		}
        //采购单
        if(getcustom('shop_purchase_order')){
            $shopset_field.=',show_purchase_order';
        }
        if(getcustom('shop_showgivescore')){
            $shopset_field.=',showgivescore';
        }
		if(getcustom('product_promotion_tag')){
			$shopset_field.=',show_promotion_tag,show_promotion_rate_tag';
		}
        if(getcustom('product_show_costprice_fanwei')){
            $shopset_field.=',product_show_costprice_fanwei,costprice_levelids';
        }
        if(getcustom('product_show_marketprice')){
            $shopset_field.=',product_show_marketprice';
        }
        if(getcustom('product_autoplay_video')){
            $shopset_field.=',autoplay_video';
        }
		$shopset = Db::name('shop_sysset')->where('aid',aid)->field($shopset_field)->find();
        $custom['show_price'] = $custom_show_price;
        if($this->member){
            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
        }

		//预计佣金
		$commission = 0;
        $commission2 = 0;
        $product['commission_desc'] = '元';
        $product['commission_desc_score'] = t('积分');
		if($this->member && $shopset['showcommission']==1){
			if($userlevel['can_agent']!=0){
                $feepercent = 0;
                //记录商品抽佣
                if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                    //商品独立费率
                    $feepercent =  $product['feepercent'];
                } else {
                    //商户费率
                    if($product['bid']>0){
                        $feepercent = Db::name('business')->where('id',$product['bid'])->value('feepercent');
                    }
                }
				if($product['commissionset']==1){//按比例
					$commissiondata = json_decode($product['commissiondata1'],true);
					if($commissiondata){
						$commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                        $commission2 = $commissiondata[$userlevel['id']]['commission2'] * ($product['sell_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                        if (getcustom('yx_buyer_subsidy')){
                            //按抽成计算
                            if($sysset['fxjiesuantype']==3){
                                $commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] *$feepercent/100)/100;
                                $commission2 = $commissiondata[$userlevel['id']]['commission2'] * ($product['sell_price'] *$feepercent/100)/100;
                            }
                        }
					}
				}elseif($product['commissionset']==2){//按固定金额
					$commissiondata = json_decode($product['commissiondata2'],true);
					if($commissiondata){
						$commission = $commissiondata[$userlevel['id']]['commission1'];
                        $commission2 = $commissiondata[$userlevel['id']]['commission2'];
					}
                }elseif($product['commissionset']==3) {//提成是积分
                    $commissiondata = json_decode($product['commissiondata3'],true);
                    if($commissiondata){
                        $commissionScore = $commissiondata[$userlevel['id']]['commission1'];
                    }
                //elseif($product['commissionset']==4 && $product['lvprice']==1)//按价格差
				//	$lvprice_data = json_decode($product['lvprice_data'],true);
                //    $commission = array_shift($lvprice_data) - $product['sell_price_origin'];
				//	if($commission < 0) $commission = 0;
                }elseif($product['commissionset']==5){//提成比例+积分
                    $commissiondata = json_decode($product['commissiondata1'],true);
                    if($commissiondata){
                        $commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                        $commission2 = $commissiondata[$userlevel['id']]['commission2'] * ($product['sell_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                        if (getcustom('yx_buyer_subsidy')){
                            //按抽成计算
                            if($sysset['fxjiesuantype']==3){
                                $commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] *$feepercent/100)/100;
                                $commission2 = $commissiondata[$userlevel['id']]['commission2'] * ($product['sell_price'] *$feepercent/100)/100;
                            }
                        }
                    }
                    $commissiondataScore = json_decode($product['commissiondata3'],true);
                    if($commissiondataScore){
                        $commissionScore = $commissiondataScore[$userlevel['id']]['commission1'];
                    }
                }elseif($product['commissionset']==6){//提成金额+积分
                    $commissiondata = json_decode($product['commissiondata2'],true);
                    if($commissiondata){
                        if(getcustom('fengdanjiangli') && $product['fengdanjiangli']){

                        }else{
                            $commission = $commissiondata[$userlevel['id']]['commission1'] *1;
                            $commission2 = $commissiondata[$userlevel['id']]['commission2'] *1;
                        }
                    }
                    $commissiondataScore = json_decode($product['commissiondata3'],true);
                    if($commissiondataScore){
                        $commissionScore = $commissiondataScore[$userlevel['id']]['commission1'];
                    }
                }elseif($product['commissionset']==7){//分销按比例送积分
                    $commissiondata = json_decode($product['commissiondata4'],true);
                    if($commissiondata){
                        $commissionScore = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                    }
                }elseif($product['commissionset']==9){//提成比例+现金比例
                    if(getcustom('commission_xianjin_percent')) {
                        $commissiondata = json_decode($product['commissiondata1'], true);
                        if ($commissiondata) {
                            $commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0)) * 0.01;
                            $commission2 = $commissiondata[$userlevel['id']]['commission2'] * ($product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0)) * 0.01;
                        }
                        $commissiondataXianjin = json_decode($product['commissiondata5'], true);
                        if ($commissiondataXianjin) {
                            $commissionScore = $commissiondataXianjin[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0)) * 0.01;
                        }
                        $product['commission_desc_score'] = t('现金');
                    }
                }elseif($product['commissionset']==10){//提成比例+返现积分比例
                    if(getcustom('yx_buyer_subsidy')) {
                        $commissiondata = json_decode($product['commissiondata1'], true);
                        if ($commissiondata) {
                            $commission = $commissiondata[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0)) * 0.01;
                            $commission2 = $commissiondata[$userlevel['id']]['commission2'] * ($product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0)) * 0.01;
                        }
                        $commissiondataSubsidy = json_decode($product['commissiondata6'], true);
                        if ($commissiondataSubsidy) {
                            $commissionScore = $commissiondataSubsidy[$userlevel['id']]['commission1'] * ($product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0)) * 0.01;
                        }
                        $product['commission_desc_score'] = t('返现积分');
                    }
                }elseif($product['commissionset']==11){//提成金额+种子数量
                    if(getcustom('yx_farm')) {
                        $commissiondata = json_decode($product['commissiondata1'], true);
                        if ($commissiondata) {
                            $commission = $commissiondata[$userlevel['id']]['commission1'] ;
                            $commission2 = $commissiondata[$userlevel['id']]['commission2'] ;
                        }
                        $commissiondataSubsidy = json_decode($product['commissiondata6'], true);
                        if ($commissiondataSubsidy) {
                            $commissionScore = $commissiondataSubsidy[$userlevel['id']]['commission1'];
                        }
                        $farm_textset = \app\custom\yingxiao\FarmCustom::getText(aid);
                        $product['commission_desc_score'] = $farm_textset['种子'];
                    }
                }elseif($product['commissionset']==0){//按会员等级
				    //fxjiesuantype 0按商品价格,1按成交价格,2按销售利润
					if($userlevel['commissiontype']==1){ //固定金额按单
						$commission = $userlevel['commission1'];
                        $commission2 = $userlevel['commission2'];
					}else{
						$commission = $userlevel['commission1'] * ($product['sell_price_origin'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                        $commission2 = $userlevel['commission2'] * ($product['sell_price_origin'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * 0.01;
                        if (getcustom('yx_buyer_subsidy')){
                            //按抽成计算
                            if($sysset['fxjiesuantype']==3){
                                $commission = $userlevel['commission1'] * ($product['sell_price'] *$feepercent/100)/100;
                                $commission2 = $userlevel['commission2'] * ($product['sell_price'] *$feepercent/100)/100;
                            }
                        }
					}
				}
				if($product['commissionset4']==1 && $product['lvprice']==1){ //极差分销
					$lvprice_data = json_decode($product['lvprice_data'],true);
					$commission += array_shift($lvprice_data) - $product['sell_price'];
					if($commission < 0) $commission = 0;
				}
			}
		}
		if(getcustom('member_level_ztorder_extrareward')){
			//直推前三单额外奖励，读取第一单奖励
            $ztorder_extrareward = true;
            //平台权限
            $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user){
                if($admin_user['auth_type'] !=1 ){
                    $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                    if(!in_array('ZtorderExtrareward,ZtorderExtrareward',$admin_auth)){
                        $ztorder_extrareward = false;
                    }
                }
            }else{
                $ztorder_extrareward = false;
            }
            if($ztorder_extrareward){
	            if($userlevel && $userlevel['ztorder_extrareward1']>0 ){
	                $ztorder_commission =  ($product['sell_price'] - ($sysset['fxjiesuantype']==2 ? $product['cost_price'] : 0)) * $userlevel['ztorder_extrareward1']/100;
	                $ztorder_commission = round($ztorder_commission,2);
	                if($ztorder_commission>0){
	                	$commission += $ztorder_commission;
	                }
	            }
            }
        }
		$product['commission'] = round($commission*100)/100;
        $product['commission2'] = round($commission2*100)/100;
        $score_weishu = $this->score_weishu;
        $product['givescore'] =  !empty($product['givescore']) && $product['givescore']>0?dd_money_format($product['givescore'],$score_weishu):0;
        $product['commissionScore'] = dd_money_format($commissionScore,$score_weishu);
		if(getcustom('product_promotion_tag') && $shopset['show_promotion_tag']){
			//佣金率 =（商品售价-成本价）÷ 商品售价
			$tag_commission_rate = ($product['sell_price'] - $product['cost_price']) / $product['sell_price'] * 100;
			$commission =  0; //预计佣金
			$tag_upgrade_money = 0; //升级多赚 = 下级佣金 - 预计佣金

			//预计佣金
			if($this->member){
				$expected_commission = \app\common\Fenxiao::productExpectedCommission($product,$userlevel,$sysset);
				$expected_fenhong = \app\common\Fenhong::productExpectedTeamFenhong($product,$userlevel,$sysset);
				$commission = $expected_commission + $expected_fenhong;
				//下级预计佣金
				$nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$userlevel['sort'])->order('sort,id')->find();
				if($nextlevel){
					$next_expected_commission = \app\common\Fenxiao::productExpectedCommission($product,$nextlevel,$sysset);
					$next_expected_fenhong = \app\common\Fenhong::productExpectedTeamFenhong($product,$nextlevel,$sysset);
					$nextCommission = $next_expected_commission + $next_expected_fenhong;
					$tag_upgrade_money = dd_money_format($nextCommission - $commission,1);
				}
			}
			if(!$shopset['show_promotion_rate_tag']){
				$product['tag_commission_rate'] = 0;
			}else{
				$product['tag_commission_rate'] = dd_money_format($tag_commission_rate,1).'%';
			}
			$product['tag_commission'] = dd_money_format($commission,1);
			$product['tag_upgrade_money'] = $tag_upgrade_money;
		}
        if(isset($shopset['hide_cost']) && $shopset['hide_cost']==0){
            //显示成本
        }else{
            unset($product['cost_price']);
        }
		//节省金额
		$product['jiesheng_money'] = round($oldproduct['sell_price'] - $product['sell_price'],2);
		if($product['jiesheng_money'] <= 0) $product['jiesheng_money'] = round($product['market_price'] - $product['sell_price'],2);
		if($product['jiesheng_money'] <= 0) $product['jiesheng_money'] = 0;

		//升级到下一等级预计节省多少钱
		if($shopset['show_lvupsavemoney'] == 1 && $this->member){
			$upsavemoney = 0;
			$nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$userlevel['sort'])->order('sort,id')->find();
			if($nextlevel){
				$sell_price = $oldproduct['sell_price'];
				if($product['lvprice']==0 && $product['no_discount'] == 0 && $userlevel['discount'] > 0 && $userlevel['discount'] < 10){
					$this_sell_price = $sell_price * $userlevel['discount'] * 0.1;
				}else{
					if($product['lvprice']==1){
						$lvprice_data = json_decode($oldproduct['lvprice_data'],true);
						if($lvprice_data && isset($lvprice_data[$this->member['levelid']])){
							$this_sell_price = $lvprice_data[$this->member['levelid']];
						}
					}else{
						$this_sell_price = $sell_price;
					}
				}
				if($product['lvprice']==0 && $product['no_discount'] == 0 && $nextlevel['discount'] > 0 && $nextlevel['discount'] < 10){
					$next_sell_price = $sell_price * $nextlevel['discount'] * 0.1;
				}else{
					if($product['lvprice']==1){
						$lvprice_data = json_decode($oldproduct['lvprice_data'],true);
						if($lvprice_data && isset($lvprice_data[$nextlevel['id']])){
							$next_sell_price = $lvprice_data[$nextlevel['id']];
						}
					}else{
						$next_sell_price = $sell_price;
					}
				}
				if($this_sell_price > $next_sell_price){
					$upsavemoney = round($this_sell_price - $next_sell_price,2);
				}
			}
			$product['upsavemoney'] = $upsavemoney;
			$product['nextlevelname'] = $nextlevel ? $nextlevel['name'] : '';
            if(getcustom('product_show_lvupsavemoney2')){
                $upsavemoney2 = 0;
                $nextlevel2 = Db::name('member_level')->where('aid',aid)->where('sort','>',$nextlevel['sort'])->order('sort,id')->find();
                if($nextlevel2){
                    if($product['lvprice']==0 && $product['no_discount'] == 0 && $nextlevel2['discount'] > 0 && $nextlevel2['discount'] < 10){
                        $next_sell_price = $sell_price * $nextlevel2['discount'] * 0.1;
                    }else{
                        if($product['lvprice']==1){
                            $lvprice_data = json_decode($oldproduct['lvprice_data'],true);
                            if($lvprice_data && isset($lvprice_data[$nextlevel2['id']])){
                                $next_sell_price = $lvprice_data[$nextlevel2['id']];
                            }
                        }else{
                            $next_sell_price = $sell_price;
                        }
                    }
                    if($this_sell_price > $next_sell_price){
                        $upsavemoney2 = round($this_sell_price - $next_sell_price,2);
                    }
                }
                $product['upsavemoney2'] = $upsavemoney2 > $upsavemoney ? $upsavemoney2 : 0;
                $product['nextlevelname2'] = $nextlevel2 ? $nextlevel2['name'] : '';
            }
		}else{
			$product['upsavemoney'] = 0;
		}

		if($product['balance'] > 0){
			$product['advance_price'] = round($product['sell_price'] * (1 - $product['balance'] *0.01),2);
			$product['balance_price'] = round($product['sell_price'] * $product['balance'] *0.01,2);
		}

		if(getcustom('buy_selectmember') && $this->member){
			if($userlevel['can_buyselect'] ==1){
				$product['buyselect_commission'] = round($userlevel['buyselect_commission'] * $product['sell_price'] * 0.01,2);
			}
		}

		if($product['bid']!=0){
            $bfield = 'id,name,logo,desc,tel,address,sales,kfurl,bottomImg,is_open';
            if(getcustom('buybutton_custom')){
                $bfield .= ',buybtn_status';
            }
            $business = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->field($bfield)->find();
            if($business['is_open'] != 1){
                return $this->json(['status' => 0, 'msg' => '店铺未营业']);
            }
		}else{
			$business = $sysset;
		}
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
		$product['comment_starnum'] = floor($product['comment_score']);

		if($this->member && $product['lvprice']==0 && $product['no_discount'] == 0 && $userlevel['discount'] > 0 && $userlevel['discount'] < 10){
			$product['discount_tips'] = ' '.$userlevel['name'].' 享'.floatval($userlevel['discount']).'折优惠';
		}else{
			$product['discount_tips'] = '';
		}
        
		$sysset['showgzts'] = false;
		//关注提示
		if(platform == 'mp'){
			$sysset['gzts'] = explode(',',$sysset['gzts']);
			if(in_array('2',$sysset['gzts']) && $this->member['subscribe']==0){
				$appinfo = \app\common\System::appinfo(aid,'mp');
				$sysset['qrcode'] = $appinfo['qrcode'];
				$sysset['gzhname'] = $appinfo['nickname'];
				$sysset['showgzts'] = true;
			}
		}
		//订单播报
		$bboglist = [];
		$sysset['ddbb'] = explode(',',$sysset['ddbb']);
		if(in_array('2',$sysset['ddbb'])){
			$bboglist = Db::name('shop_order_goods')
				->field('mid,name,createtime,proid')
				->where('aid',aid)
				->where('proid',$product['id'])
				->where('status','in','0,1,2,3')
				->where('createtime','>',time()-86400*10)
				->order('createtime desc')->limit(10)->select()->toArray();
			if(!$bboglist) $bboglist = [];
			foreach($bboglist as $k=>$og){
				$ogmember = Db::name('member')->where('id',$og['mid'])->find();
				if(!$ogmember){
					unset($bboglist[$k]);
					continue;
				}else{
					$bboglist[$k]['nickname'] = $ogmember['nickname'];
					$bboglist[$k]['headimg'] = $ogmember['headimg'];
				}
				if(time() - $og['createtime'] < 60*5){
					$bboglist[$k]['showtime'] = '刚刚';
				}elseif(date('Ymd')==date('Ymd',$og['createtime'])){
					if($og['createtime'] + 3600 > time()){
						$bboglist[$k]['showtime'] = floor((time()-$og['createtime'])/60).'分钟前';
					}else{
						$bboglist[$k]['showtime'] = floor((time()-$og['createtime'])/3600).'小时前';
					}
				}elseif(time()-$og['createtime']<86400){
					$bboglist[$k]['showtime'] = '昨天';
				}elseif(time()-$og['createtime']<2*86400){
					$bboglist[$k]['showtime'] = '前天';
				}else{
					$bboglist[$k]['showtime'] = '三天前';
				}
			}
		}
		//促销活动
		$cuxiaolist = Db::name('cuxiao')
			->where('aid',aid)
			->where('bid',$product['bid'])
			->where('starttime','<',time())
			->where('endtime','>',time())
			->order('sort desc')->select()->toArray();
		$newcxlist = [];
		foreach($cuxiaolist as $k=>$v){
			$gettj = explode(',',$v['gettj']);
			if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
				continue;
			}
			if($v['fwtype']==2){//指定商品可用
				$productids = explode(',',$v['productids']);
				if(!in_array($product['id'],$productids)){
					continue;
				}
			}
			if($v['fwtype']==1){//指定类目可用
				if(!$v['categoryids']) continue;
				$categoryids = explode(',',$v['categoryids']);
				$cids = explode(',',$product['cid']);
				$clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
				foreach($clist as $kc=>$vc){
					$categoryids[] = $vc['id'];
					$cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
					if($cate2) $categoryids[] = $cate2['id'];
				}
				if(!array_intersect($cids,$categoryids)){
					continue;
				}
			}
			$newcxlist[] = $v;
		}
		//优惠券isgive=2 仅转赠的卡券，不可自己使用
		$couponlist = Db::name('coupon')->where('aid',aid)->where('bid',$product['bid'])->where('isgive','<>',2)->where('tolist',1)->where('type','in','1,4,10')->where("unix_timestamp(starttime)<=".time()." and unix_timestamp(endtime)>=".time())->order('sort desc,id desc')->select()->toArray();
		$newcplist = [];
		foreach($couponlist as $k=>$v){
			$showtj = explode(',',$v['showtj']);
			if(!in_array('-1',$showtj) && !in_array($this->member['levelid'],$showtj)){ //不是所有人
				continue;
			}
            //0全场通用,1指定类目,2指定商品,6指定商家类目可用
            if(!in_array($v['fwtype'],[0,1,2,6])){
                continue;
            }
			if($v['fwtype']==2){//指定商品可用
				$productids = explode(',',$v['productids']);
				if(!in_array($product['id'],$productids)){
					continue;
				}
			}
			if($v['fwtype']==1){//指定类目可用
				if(!$v['categoryids']) continue;
				$categoryids = explode(',',$v['categoryids']);
				$cids = explode(',',$product['cid']);
				$clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
				foreach($clist as $kc=>$vc){
					$categoryids[] = $vc['id'];
					$cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
					if($cate2) $categoryids[] = $cate2['id'];
				}
				if(!array_intersect($cids,$categoryids)){
					continue;
				}
			}
			if($v['fwtype']==6){//指定商家类目可用
				if(!$v['categoryids2']) continue;
				$categoryids2 = explode(',',$v['categoryids2']);
				$cids2 = explode(',',$product['cid2']);
				$clist2 = Db::name('shop_category2')->where('pid','in',$categoryids2)->select()->toArray();
				foreach($clist2 as $kc=>$vc){
					$categoryids2[] = $vc['id'];
					$cate2 = Db::name('shop_category2')->where('pid',$vc['id'])->find();
					if($cate2) $categoryids2[] = $cate2['id'];
				}
				if(!array_intersect($cids2,$categoryids2)){
					continue;
				}
			}
			$haveget = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('couponid',$v['id'])->count();
			$v['haveget'] = $haveget;
			//$v['starttime'] = date('m-d H:i',strtotime($v['starttime']));
			//$v['endtime'] = date('m-d H:i',strtotime($v['endtime']));
            if($v['yxqtype'] == 1){
                $yxqtime = explode(' ~ ',$v['yxqtime']);
                $v['yxqdate'] = strtotime($yxqtime[1]);
            }elseif($v['yxqtype'] == 2){
                $v['yxqdate'] = time() + 86400 * $v['yxqdate'];
            }elseif($v['yxqtype'] == 3) {
                //次日起计算有效期
                $v['yxqdate'] = strtotime(date('Y-m-d')) + 86400 * ($v['yxqdate'] + 1) - 1;
            }
            if($v['bid'] > 0){
                $v['bname'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->value('name');
            }
			$newcplist[] = $v;
		}

		//商品服务
		if($product['fwid']){
			$fuwulist = Db::name('shop_fuwu')->where('aid',aid)->where('status',1)->where('id','in',$product['fwid'])->order('sort desc,id')->select()->toArray();
		}else{
			$fuwulist = [];
		}

		$minprice = 999999999999999;
		$maxprice = 0;
        if(getcustom('product_service_fee')){
            $minServiceFee = 999999999999999;
            $maxServiceFee = 0;
        }
        if(getcustom('member_level_price_show')){
        	//获取第一个规格的会员等级价格
        	$priceshows = [];
        	$price_show = 0;
        	$price_show_text = '';
        	$product['sell_putongminprice'] = 999999999999999;
            $product['sell_putongmaxprice'] = 0;
        }
		$gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
        $gglist = $this->formatgglist($gglist,$product['bid'], $product['lvprice']);//if($product['lvprice']==1)
        if(getcustom('shop_showgivescore')){
			$showgivescore1 = -1;//最小赠送积分
			$showgivescore2 = -1;//最大赠送积分
		}
		foreach($gglist as $k=>$v){
			if($v['sell_price'] < $minprice){
				$minprice = $v['sell_price'];
			}
			if($v['sell_price'] > $maxprice){
				$maxprice = $v['sell_price'];
			}
            if(getcustom('erp_wangdiantong') && $v['wdt_status']==1){
                $c = new \app\custom\Wdt(aid,$product['bid']);
                $newstock = $c->stockQueryBySpec($v['barcode'],$product['id']);
                $gglist[$k]['stock'] = $newstock;
            }
            if(getcustom('product_service_fee')){
                if($v['service_fee'] < $minServiceFee){
                    $minServiceFee = $v['service_fee'];
                }
                if($v['service_fee'] > $maxServiceFee){
                    $maxServiceFee = $v['service_fee'];
                }
            }
            if(getcustom('member_goldmoney_silvermoney')){
                if($k == 0){
                    $ShopSendSilvermoney = true;//赠送银值权限
                    $ShopSendGoldmoney   = true;//赠送金值权限
                    //平台权限
                    $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                    if($admin_user['auth_type'] !=1 ){
                        $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                        if(!in_array('ShopSendSilvermoney,ShopSendSilvermoney',$admin_auth)){
                            $ShopSendSilvermoney = false;
                        }
                        if(!in_array('ShopSendGoldmoney,ShopSendGoldmoney',$admin_auth)){
                            $ShopSendGoldmoney   = false;
                        }
                    }
                    //银值
                    $product['givesilvermoney'] = 0;
                    if($ShopSendSilvermoney){
                        $product['givesilvermoney'] = $v['givesilvermoney'];//用户银值
                    }
                    //金值
                    $product['givegoldmoney'] = 0;
                    if($ShopSendGoldmoney){
                        $product['givegoldmoney'] = $v['givegoldmoney'];//用户金值
                    }
                }
            }

            if(getcustom('member_level_price_show')){
                //获取第一个规格的会员等级价格
                if($product['lvprice'] == 1 && $v['lvprice_data']){
                    $lvprice_data = json_decode($v['lvprice_data'],true);
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
                                    if($priceshows && $priceshows[$lid]){
                                        if($lv < $priceshows[$lid]['sell_minprice']){
                                            $priceshows[$lid]['sell_minprice'] = $lv;
                                        }
                                        if($lv > $priceshows[$lid]['sell_maxprice']){
                                            $priceshows[$lid]['sell_maxprice'] = $lv;
                                        }
                                        if($priceshows[$lid]['sell_minprice'] != $priceshows[$lid]['sell_maxprice']){
                                            $priceshows[$lid]['sell_price'] = $priceshows[$lid]['sell_minprice'].'-'.$priceshows[$lid]['sell_maxprice'];
                                        }
                                    }else{
                                        $priceshow = [];
                                        $priceshow['id'] = $lid;
                                        $priceshow['sell_price']    = $lv;
                                        $priceshow['sell_minprice'] = $lv;
                                        $priceshow['sell_maxprice'] = $lv;
                                        $priceshow['price_show_text'] = $level['price_show_text'];
                                        $priceshows[$lid] = $priceshow;
                                    }
                                }
                            }
                            if($lk == 0){
                                //普通价格
                                $product['sell_putongprice'] = $lv;
                                if($lv < $product['sell_putongminprice']){
                                    $product['sell_putongminprice'] = $lv;
                                }
                                if($lv > $product['sell_putongmaxprice']){
                                    $product['sell_putongmaxprice'] = $lv;
                                }
                            }
                            $lk ++ ;
                        }
                        unset($lid);unset($lv);
                    }
                    
                }
            }

            if(getcustom('shop_showgivescore')){
            	$sgres =  \app\common\System::showgivescore($shopset['showgivescore'],$showgivescore1,$showgivescore2,$v['givescore']);
            	if($sgres){
            		$showgivescore1 = $sgres['showgivescore1'];//最小赠送积分
					$showgivescore2 = $sgres['showgivescore2'];//最大赠送积分
            	}
            }
		}
		if(getcustom('member_level_price_show')){
            //获取第一个规格的会员等级价格
            $product['priceshows'] = $priceshows?array_values($priceshows):'';
            $product['price_show'] = $price_show;
            $product['price_show_text'] = $price_show_text;
            if(isset($product['sell_putongprice']) && $product['sell_putongminprice'] != $product['sell_putongmaxprice']){
                $product['sell_putongprice'] = $product['sell_putongminprice'].'-'.$product['sell_putongmaxprice'];
            }
        }
        $price_dollar = false;
		if(getcustom('price_dollar')){
			$shopsets = Db::name('shop_sysset')->where('aid',aid)->field('usdrate')->find();
			if($shopsets['usdrate']>0){
				$product['usdmin_price'] = round($minprice/$shopsets['usdrate'],2);
				$product['usdmax_price'] = round($maxprice/$shopsets['usdrate'],2);
                $price_dollar = true;
			}
		}
		if(getcustom('shop_showgivescore')){
	    	$product['showgivescore'] =  \app\common\System::showgivescore2($shopset['showgivescore'],$showgivescore1,$showgivescore2);
	    	if($product['showgivescore']) $product['givescore'] = $product['showgivescore'];
		}

		$product['min_price'] = round($minprice,2);
		$product['max_price'] = round($maxprice,2);
        if(getcustom('product_service_fee')){
            $product['min_service_fee'] = round($minServiceFee,2);
            $product['max_service_fee'] = round($maxServiceFee,2);
        }
        //价格显示方式
        if(getcustom('price_show_type')){
            $price_show_type = Db::name('shop_sysset')->where('aid',aid)->value('price_show_type');
            $sysset['price_show_type'] = $price_show_type;

            $defalut_level = Db::name('member_level')->where('aid',aid)->order('id asc')->find();
            if(empty($userlevel) || $defalut_level['id'] == $this->member['levelid']){
                $product['is_vip'] =0;
            }else{
                $product['is_vip'] =1;
            }
           
            if(in_array($price_show_type,[1,2])){ //开启会员价
                $lvprice_data =json_decode($product['lvprice_data'],true);
                if($product['is_vip'] == 0){//不是会员 查询下个会员
                    $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$userlevel['sort'])->order('sort,id')->find();
                   
                    if(empty($nextlevel) || $product['lvprice'] ==0 ){
                        $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$defalut_level['sort'])->order('sort,id')->find();
                    }
                    $level_name = $nextlevel['name'];
                    $product['vip_price'] = $lvprice_data[$nextlevel['id']];
                    $product['min_price'] = $product['sell_price'];
                    $product['max_price'] = $product['sell_price'];
                }else{
                   
                    if($userlevel && $product['lvprice'] ==1 ){
                        $level_name =  $userlevel['name'];
                    }
                    $product['vip_price'] = $product['sell_price'];
                    $product['min_price'] = $product['sell_price_origin'];
                    $product['max_price'] = $product['sell_price_origin'];
                }
                $product['level_name'] =  $level_name;
            }
        }

        if(getcustom('shoptongji3')){
			Db::name('shop_product')->where('id',$product['id'])->inc('viewnum')->update();
		}
		
		$tjdatalist = [];
		if($product['show_recommend'] == 1){
			$tjwhere = [];
			$tjwhere[] = ['aid','=',aid];
			$tjwhere[] = ['status','=',1];
			$tjwhere[] = ['ischecked','=',1];
			$where2 = "find_in_set('-1',showtj)";
			if($this->member){
				$where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
				if($this->member['subscribe']==1){
					$where2 .= " or find_in_set('0',showtj)";
				}
			}
			$tjwhere[] = Db::raw($where2);

			if($product['bid']){
				$tjwhere[] = ['bid','=',$product['bid']];
			}else{
				$business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
				if(!$business_sysset || $business_sysset['status']==0 || $business_sysset['product_isshow']==0){
					$tjwhere[] = ['bid','=',0];
				}
			}
			$tjdatalist = Db::name('shop_product')->field("id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,price_type")->where($tjwhere)->limit(8)->order(Db::raw('rand()'))->select()->toArray();
			if(!$tjdatalist) $tjdatalist = array();
			$tjdatalist = $this->formatprolist($tjdatalist);
		}elseif($product['show_recommend'] == 2){
			$tjdatalist = Db::name('shop_product')->where('aid',aid)->where('id','in',$product['recommend_productids'])->order(Db::raw('field(id,'.$product['recommend_productids'].')'))->select()->toArray();
		}

        //未登录查看价格
		if(getcustom('show_price_unlogin')){
			$mid = mid;
			if(!$mid && $shopset['is_show_price_unlogin'] == 0){
				foreach($tjdatalist as &$pv){
					$pv['sell_price'] =  $shopset['show_price_unlogin_txt'];					
				}
			}			
		}
		//未审核查看价格
		if(getcustom('show_price_uncheck')){
			if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){
				foreach($tjdatalist as &$pv){
					$pv['sell_price'] =  $shopset['show_price_uncheck_txt'];					
				}
			}			
		}

        if($tjdatalist){
            foreach($tjdatalist as &$tv){
                if(getcustom('member_level_price_show')){
                    //获取第一个规格的会员等级价格
                    $priceshows = [];
                    $price_show = 0;
                    $price_show_text = '';
                }
                $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$tv['id'])->select()->toArray();
                foreach($gglist as $k=>$v){
                    if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        if($k == 0 && $tv['lvprice'] == 1 && $v['lvprice_data']){
                            $lvprice_data = json_decode($v['lvprice_data'],true);
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
                                        $tv['sell_putongprice'] = $lv;
                                    }
                                    $lk ++ ;
                                }
                                unset($lid);unset($lv);
                            }
                            
                        }
                    }
                }
                if(getcustom('member_level_price_show')){
                    //获取第一个规格的会员等级价格
                    $tv['priceshows'] = $priceshows?$priceshows:'';
                    $tv['price_show'] = $price_show;
                    $tv['price_show_text'] = $price_show_text;
                }
            }
            unset($tv);
        }

		$xcx_scheme = false;
		if(getcustom('xcx_scheme')){
			$xcx_scheme = true;
		}
        $shop_yuding = false;
        if(getcustom('shop_yuding')){
            $shop_yuding = true;
        }
        if(getcustom('buybutton_custom')){
            $buybtn_name     = '';
            $buybtn_link_url = '';
            $admin = Db::name('admin')->where('id',aid)->field('id,buybtn_status')->find();
            if($admin && $admin['buybtn_status'] == 1){
                if($product['bid']!=0){
                    if($business && $business['buybtn_status'] == 1){
                        $buybtn_name     = $product['buybtn_name'];
                        $buybtn_link_url = $product['buybtn_link_url'];
                    }
                }else{
                    $buybtn_name     = $product['buybtn_name'];
                    $buybtn_link_url = $product['buybtn_link_url'];
                }
            }
            $product['buybtn_name']     = $buybtn_name;
            $product['buybtn_link_url'] = $buybtn_link_url;
        }

        //是否可自提
        $bindmendianlist = [];
		$probid = $product['bid'];
		if(getcustom('business_bind_show_page')){
			//绑定多商户显示多商户首页
			if($sysset['mode'] == 1 && $sysset['loc_business_show_type'] == 2){
				$pid = input('param.pid');
				if($this->member && $this->member['bind_business']){
					$probid = $this->member['bind_business'];
				}elseif($pid && $this->member && empty($this->member['bind_business'])){
					$pid_bind_business = Db::name('member')->where('id',$pid)->value('bind_business');
					if($pid_bind_business){
						$probid = $pid_bind_business;
						Db::name('member')->where('id',$this->member['id'])->update(['bind_business'=>$pid_bind_business]);
					}
				}
			}
		}
        if(getcustom('product_bind_mendian')){
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
        $commentposition = 0;//系统默认显示位置
        if(getcustom('product_comment')){
            $commentposition = 1;//商品详情的最底部
        }
        /*$custom['product_detail_header_hide'] = false;
        if(getcustom('product_sellprice_show')){
            $custom['product_detail_header_hide'] = true;
        }*/
        $showHeaderPic = true;
        if(getcustom('product_detail_special') && $shopset['show_header_pic']==0){
            $showHeaderPic = false;
        }
        $shopset['show_header_pic'] = $showHeaderPic;
        $custom['product_xunjia_btn'] = false;
        if(getcustom('product_xunjia_btn')){
            $custom['product_xunjia_btn'] = true;
        }
        $product['shop_yuding'] = $shop_yuding;

        if(getcustom('yx_invite_cashback')) {
            //处理邀请返现文字提示
            $deal_ictips       = \app\custom\OrderCustom::deal_ictips(aid,mid,0,[$product]);
            $product['ictips'] = $deal_ictips['ictips'];
        }

		//未登录查看价格
		if(getcustom('show_price_unlogin')){
			$mid = mid;
			if(!$mid && $shopset['is_show_price_unlogin'] == 0){
				$product['sell_price'] =  $shopset['show_price_unlogin_txt'];	
				$product['min_price'] = $shopset['show_price_unlogin_txt'];	
				$product['max_price'] = $shopset['show_price_unlogin_txt'];			
			}			
		}
		//未审核查看价格
		if(getcustom('show_price_uncheck')){
			if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){
				$product['sell_price'] =  $shopset['show_price_uncheck_txt'];	
				$product['min_price'] = $shopset['show_price_uncheck_txt'];	
				$product['max_price'] = $shopset['show_price_uncheck_txt'];	
			}			
		}

		if(getcustom('shop_product_fenqi_pay')){
			$product['fenqi_data'] = json_decode($product['fenqi_data'],true);
			$couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$product['fenqigive_couponid'])->find();
			if($couponinfo){
				$product['fenqigive_couponname'] = $couponinfo['name'];
			}			
		}
        if(getcustom('product_pickup_device')){
            //商品柜对应的商品id
            $devicedata = input('param.devicedata');
            list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
            //如果是商品柜的商品，把价格进行替换
            if($devicedata && $device_no){
                $ggid= Db::name('product_pickup_device_goods')->where('aid',aid)->where('device_no',$device_no)->where('goods_lane',$goods_lane)->value('ggid');
                   $product_guige = Db::name('shop_guige')->alias('gg')
                    ->join('shop_product sp','sp.id = gg.proid')
                    ->where('gg.aid',aid)
                    ->where('gg.id',$ggid)
                    ->field('gg.sell_price,sp.lvprice,gg.lvprice_data')
                    ->find();
                   $member = $this->member;
                $device_goods_sell_price =  $product_guige['sell_price'];
                
                if($product_guige['lvprice']==1 && $member){
                    $lvprice_data = json_decode($product_guige['lvprice_data'],true);
                    if($lvprice_data && isset($lvprice_data[$member['levelid']])){
                        $device_goods_sell_price = $lvprice_data[$member['levelid']];
                    }
                }     
                $product['min_price'] = $product['max_price'] = $device_goods_sell_price;
                $product['market_price'] = 0;
            }
        }
        $custom['active_coin'] = 0;
        if(getcustom('active_coin')){
            //激活币
            $custom['active_coin'] = 1;
            $give_active_coin = \app\common\Order::getProductActiveCoin(aid,$product);
            $product['give_active_coin'] = $give_active_coin?:0;
        }
        //分销份数
        $custom['commission_max_times_status'] = 0;
        if(getcustom('commission_max_times')){
            if($product['commission_max_times_status']==1 && $this->member) {
                $commission_max_times = json_decode($product['commission_max_times'], true);
                $commission_times1 = $commission_max_times[$this->member['levelid']]['commission1']??0;
                $commission_times2 = $commission_max_times[$this->member['levelid']]['commission2']??0;
                if($commission_times2<=0){
                    $commission_times2 = $commission_times1*$commission_times1;
                }
                $product['commission_total1'] = bcmul($product['commission'],$commission_times1,2);
                $product['commission_total2'] =  bcmul($product['commission2'],$commission_times2,2);
                $custom['commission_max_times_status'] = 1;
            }
        }
        if(getcustom('member_goldmoney_silvermoney')){
            $custom['member_goldmoney_silvermoney'] = true;
        }
        if(getcustom('yx_buy_product_manren_choujiang')){
            $choujiang = Db::name('manren_choujiang')
                ->field('id,bid,cycles,custom_text,text_color,text_bgcolor,opennum')
                ->where('aid',aid)
                ->where('find_in_set('.$product['id'].',proid)')
                ->where('status',1)
                ->find();
            if($choujiang){
                $recordWhere = [
                    'aid' => $choujiang['aid'],
                    'bid' => $choujiang['bid'],
                    'hid' => $choujiang['id'],
                    'cycles' => $choujiang['cycles'],
                ];
                $choujiang['custom_text'] = \app\custom\ManrenChoujiang::customtext($choujiang['custom_text'],$choujiang['opennum'],$recordWhere);
                $product['choujiang'] = $choujiang;
            }
        }
        //新积分
        $custom['yx_new_score'] = 0;
        $product['new_score'] = 0;
        if(getcustom('yx_new_score')){
            $custom['yx_new_score'] = 1;
            $res_new_score = \app\custom\NewScore::getProductNewScore(aid,[],[],$product['id']);
            $product['newscore_ratio'] = $res_new_score['newscore_ratio']?dd_money_format($res_new_score['newscore_ratio'],2):0;
        }
        $custom['yx_new_score_speed_pack'] = 0;
        $product['newscore_pack_ratio'] = 0;
        if(getcustom('yx_new_score_speed_pack')){
            $custom['yx_new_score_speed_pack'] = 1;
            if($product['newscore_pack_id']){
                $pack = Db::name('newscore_speed_pack')->where('aid',aid)->where('id',$product['newscore_pack_id'])->where('status',1)->find();
                $product['newscore_pack_ratio'] = $pack['ratio']??0;
            }
        }
        //农场
        $custom['yx_farm'] = 0;
        $farm_textset = [];
        if(getcustom('yx_farm')){
            $custom['yx_farm'] = 1;
            $farm_textset = \app\custom\yingxiao\FarmCustom::getText(aid);
        }
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['show_money_price'] = $showMoneyPrice;
		$rdata['title'] = $product['name'];
		$rdata['shopset'] = $shopset;
		$rdata['sysset'] = $sysset;
		$rdata['isfavorite'] = $isfavorite;
		
		$rdata['business'] = $business;
		$rdata['commentlist'] = $commentlist;
		$rdata['commentcount'] = $commentcount;
		if($product['bid']>0){
			$rdata['cartnum'] = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('bid',$product['bid'])->sum('num');
		}else{
			$rdata['cartnum'] = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
		}		
		$rdata['bboglist'] = $bboglist;
		$rdata['cuxiaolist'] = $newcxlist;
		$rdata['couponlist'] = $newcplist;
		$rdata['fuwulist'] = $fuwulist;
		$rdata['tjdatalist'] = $tjdatalist;
		$rdata['showjiesheng'] = 0;
		if(getcustom('showjiesheng') && $shopset['showcommission']==1 && $product['commission'] > 0){
			$rdata['showjiesheng'] = 1;
		}
		$rdata['is_member_auto_addlogin'] = 0;
		$rdata['need_login'] = 0;
		if(getcustom('member_auto_addlogin')){
			$is_member_auto_addlogin = Db::name('admin_set')->where('aid',aid)->value('is_member_auto_addlogin');
			if($is_member_auto_addlogin == 1){
				$rdata['is_member_auto_addlogin'] = 1;
				$mid = mid;
				if(!$mid){
					$rdata['need_login'] = 1;
				}
			}
		}

        //会员等级限制显示图片
        $rdata['show_image'] = 0;
        if(getcustom('level_product_show_image')) {
            if(isset($userlevel)){
                $rdata['show_image'] = $userlevel['show_image'];
            }else{
                if($this->member){
                    $rdata['show_image'] = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->value('show_image');
                }else{
                    //查询默认等级是否开启显示图片
                    $isshowimage = Db::name('member_level')->where('aid',aid)->where('isdefault',1)->value('show_image');
                    $rdata['show_image'] = $isshowimage == 1 ?: 0;
                }
            }
        }
		$rdata['showtoptabbar'] = 0;
		if(getcustom('ngmm')){
			$rdata['showtoptabbar'] = 1;
			$rdata['shopset'] = Db::name('shop_sysset')->where('aid',aid)->field('showjd,comment,showcommission,hide_sales,hide_stock,show_lvupsavemoney,detail_guangao1,detail_guangao1_t,detail_guangao2,detail_guangao2_t')->find();
		}
		$rdata['showprice_dollar'] = $price_dollar;
		$rdata['xcx_scheme'] = $xcx_scheme;
        $rdata['mendian'] = $mendian??'';
        $rdata['bindmendianids'] = $bindmendianIds;
        $rdata['needlocation'] = $needlocation;
        $rdata['commentposition'] = $commentposition;
        $rdata['custom'] = $custom;
        $rdata['shopdetail_menudata'] = ['list'=>false];
		$designer_info = Db::name('designer_shopdetail')->where('aid',aid)->where('bid',$product['bid'])->find();
		if($designer_info){
			$shopdetail_menudata = json_decode($designer_info['menudata'],true);
			$rdata['shopdetail_menudata'] = $shopdetail_menudata;
            if(!empty($product['can_ziti']) && getcustom('location_mendian') && isset($shopdetail_menudata['isShowMd'])){
                $product['can_ziti'] = intval($shopdetail_menudata['isShowMd']);
            }else{
                //附近门店 默认关闭
                if(getcustom('location_mendian') && !isset($shopdetail_menudata['isShowMd'])){
                    $product['can_ziti'] = 0;
                }
            }
            if(getcustom('show_location') && getcustom('location_mendian')){
                if(!isset($shopdetail_menudata['isShowMd']) || !$shopdetail_menudata['isShowMd']){
                    //不显示附近门店关闭定位
                    $rdata['needlocation'] = false;
                }
            }
		}
		if(getcustom('shop_category_detailpic')){
			//显示分类详情页面图片
            $product['categoryDetailpics'] = [];
            if($product['cid'] && !empty($product['cid'])){
                $shopcategorys = Db::name('shop_category')->where('aid',aid)->where('id','in',$product['cid'])->field('id,pid,detailpic')->select()->toArray();
                foreach($shopcategorys as $category){
                    if(empty($category['detailpic']) && $category['pid']){
                        //查询上级
                        $parentcate = Db::name('shop_category')->where('aid',aid)->where('id',$category['pid'])->find();
                        if($parentcate && empty($parentcate['detailpic']) && $parentcate['pid']){
                            $parentcate2 = Db::name('shop_category')->where('aid',aid)->where('id',$parentcate['pid'])->find();
                            if($parentcate2 && !empty($parentcate2['detailpic'])){
                                $product['categoryDetailpics'][] = $parentcate2['detailpic'];
                            }
                        }else{
                            $product['categoryDetailpics'][] = $parentcate['detailpic'];
                        }
                    }else{
                        $product['categoryDetailpics'][] = $category['detailpic'];
                    }
                }
            }
            if($product['bid'] && $product['cid2'] && !empty($product['cid2'])){
                $shopcategorys2 = Db::name('shop_category2')->where('aid',aid)->where('id','in',$product['cid2'])->field('id,pid,detailpic')->select()->toArray();
                foreach($shopcategorys2 as $category2){
                    if(empty($category2['detailpic']) && $category2['pid']){
                        //查询上级
                        $parentcate = Db::name('shop_category2')->where('aid',aid)->where('id',$category2['pid'])->find();
                        if($parentcate && empty($parentcate['detailpic']) && $parentcate['pid']){
                            $parentcate2 = Db::name('shop_category2')->where('aid',aid)->where('id',$parentcate['pid'])->find();
                            if($parentcate2 && !empty($parentcate2['detailpic'])){
                                $product['categoryDetailpics'][] = $parentcate2['detailpic'];
                            }
                        }else{
                            $product['categoryDetailpics'][] = $parentcate['detailpic'];
                        }
                    }else{
                        $product['categoryDetailpics'][] = $category2['detailpic'];
                    }
                }
            }
        }
        if(getcustom('product_show_costprice_fanwei')){
            if($shopset['product_show_costprice_fanwei'] ==1){
                $costprice_levelids = explode(',',$shopset['costprice_levelids']);
                if(in_array($this->member['levelid'],$costprice_levelids) || in_array('-1',$costprice_levelids)){
                    $product['product_show_costprice_fanwei'] = 1;

                    $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
                    if($gglist)
                    {
                        $jgarr = [];
                        foreach($gglist as $gek=>$gev){
                            $jgarr[] = $gev['cost_price'];
                        }
                        $min_price = min($jgarr);
                        $max_price = max($jgarr);

                        if($min_price == 0 && $max_price == 0){
                            $product['cost_price_fanwei'] = $product['cost_price'];
                        }else if($min_price == $max_price){
                            $product['cost_price_fanwei'] = $min_price;
                        }else{
                            $product['cost_price_fanwei'] = sprintf('%g',$min_price).'-'.sprintf('%g',$max_price);
                        }
                    }else{
                        $product['cost_price_fanwei'] = $product['cost_price'];
                    }

                    if(is_null($product['cost_price_fanwei'])){
                        $product['product_show_costprice_fanwei'] = 0;
                    }
                }
            }
        }

        if(getcustom('product_show_marketprice')){
            if($shopset['product_show_marketprice'] ==0){
                //关闭划线市场价
                $product['market_price'] = 0;
            }
        }

        $rdata['product'] = $product;
		if($product['bid']>0){
			$rs = Db::name('business_sysset')->where('aid',aid)->find();
			if($rs['show_shopdetail_menu'] == 0){
			    $bottomImg = $business['bottomImg']??'';
				$rdata['shopdetail_menudata'] = ['list'=>false,'bottomImg'=>$bottomImg];
			}
		}

        if(getcustom('yx_mangfan')){
            $mangfan_data = \app\custom\Mangfan::mangfanInfo(aid, $product['id']);
            $rdata['product']['mangfan_status'] = $mangfan_data['status'];
            $rdata['product']['mangfan_text']   = t('可享消费盲返');
            $rdata['product']['mangfan_text_color'] = '#df8e14';
        }

        if(getcustom('shop_label')){
        	$set = Db::name('admin_set')->where('aid',aid)->field('color1')->find();
        	$rdata['product']['labelbgcolor'] = $product['labelbgcolor']??$set['color1'];
            $rdata['product']['labelcolor']   = $product['labelcolor']??'#FFFFFF';
        	$labels = '';
            if(!empty($product['labelid'])){
            	$labels = Db::name('shop_label')->where('id','in',$product['labelid'])->where('aid',aid)->field('id,name')->order('sort desc,id desc')->select()->toArray();
            }
            $rdata['product']['labels'] = $labels;
        }
        if(getcustom('shop_yingxiao_tag')){
            $shopTag =  \app\model\ShopProduct::getShopYingxiaoTag($product);
            if($shopTag){
                $product['ictips'] = '';
            }
           $rdata['product']['yingxiao_tag'] =  $shopTag?$shopTag:[];
        }
        $rdata['farm_textset'] = $farm_textset;
        if(getcustom('product_autoplay_video')){
        	$rdata['autoplay_video'] = !empty($product['video']) && $shopset['autoplay_video']?1:0;
        }
		return $this->json($rdata);
	}
	//获取商品详情
	public function getproductdetail(){
		$proid = input('param.id/d');
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['id','=',$proid];
        $field = "bid,id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,guigedata,status,ischecked,freighttype,freightdata,start_time,end_time,start_hours,end_hours,balance,limit_start,perlimitdan,commissionset,commissiondata1,commissiondata2,commissiondata3,commissionset4,price_type,product_type,weight";
        if(getcustom('plug_tengrui')) {
            $field .= ',house_status,group_status,group_ids,is_rzh,relation_type';
        }
        if(getcustom('shop_other_infor')) {
            $field .= ',xunjia_text';
        }
        if(getcustom('shop_yuding')){
            $field .= ',yuding_stock';
        }
		if(getcustom('product_memberlevel_limit')){
			$field .= ',levellimitdata';
		}
		if(getcustom('product_wholesale')){
			$field .= ',jieti_discount_data,jieti_discount_type';
		}
		if(getcustom('product_unit')) {
            $field .= ',product_unit';
        }
		if(getcustom('more_productunit_guige')) {
            $field .= ',prounit';
        }
        if(getcustom('product_service_fee')) {
            $field .= ',service_fee,service_fee_switch,service_fee_data';
        }
        if(getcustom('shop_product_jialiao')){
            $field = $field.',jl_title,jl_total_max,jl_total_min';
        }
        if(getcustom('product_show_guige_type')){
            $field .= ',guige_show_type';
        }
        if(getcustom('product_glass_custom')){
            $field .= ',glass_type';
        }
        if(getcustom('supply_yongsheng')){
            //服务费
            $field .= ',sproid,issource,source';
        }
        if(getcustom('product_show_costprice_fanwei')){
            $field .= ',cost_price';
        }
		$product = Db::name('shop_product')->field($field)->where($where)->find();
		if(!$product){
			return $this->json(['status'=>0,'msg'=>'商品不存在']);
		}
		$product = $this->formatproduct($product);
        if(getcustom('mendian_usercenter')){
            //门店中心独立产品库存
            if($this->member['lock_mdid']){
                $product = \app\custom\MendianUsercenter::getProductInfo(aid,$this->member['lock_mdid'],$product);
            }
        }
		if(getcustom('more_productunit_guige')) {
			$product['prounit'] = empty($product['prounit']) ?  [] : json_decode($product['prounit'],true);
		}
	
		if($product['status']==0) return $this->json(['status'=>0,'msg'=>'商品已下架']);
		if($product['ischecked']!=1) return $this->json(['status'=>0,'msg'=>'商品未审核']);
		if($product['status']==2 && (strtotime($product['start_time']) > time() || strtotime($product['end_time']) < time())){
			return $this->json(['status'=>0,'msg'=>'商品未上架']);
		}
		if($product['status']==3){
			$start_time = strtotime(date('Y-m-d '.$product['start_hours']));
			$end_time = strtotime(date('Y-m-d '.$product['end_hours']));
			if(($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time >= $end_time && ($start_time > time() && $end_time < time()))){
				return $this->json(['status'=>0,'msg'=>'商品未上架']);
			}
		}


		if($product['status']==2 || $product['status']==3) $product['status']=1;

		$gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
		$gglist = $this->formatgglist($gglist,$product['bid'],$product['lvprice'],$product['product_type']);
        if(getcustom('mendian_usercenter')){
            //门店中心独立产品库存
            if($this->member['lock_mdid']){
                $gglist = \app\custom\MendianUsercenter::getProductSpec(aid,$this->member['lock_mdid'],$product['id'],$gglist);
            }
        }
        if(getcustom('supply_yongsheng')){
            if($product['issource'] && $product['source'] == 'supply_yongsheng'){
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
            }
        }

		$product['price_dollar'] =false;
		if(getcustom('price_dollar')){
			$usdrate = Db::name('shop_sysset')->where('aid',aid)->field('usdrate')->find();
			foreach($gglist as &$gg){
				if($usdrate['usdrate']>0){
					$gg['usdsell_price'] = round($gg['sell_price']/$usdrate['usdrate'],2);
				}
				$product['price_dollar']  = true;
			}
		}	
		//未登录查看价格
		if(getcustom('show_price_unlogin')){
			$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
			$mid = mid;
			if(!$mid && $shopset['is_show_price_unlogin'] == 0){
				foreach($gglist as &$gg){
						$gg['sell_price'] = $shopset['show_price_unlogin_txt'];
				}
			}			
		}
		//未审核查看价格
		if(getcustom('show_price_uncheck')){
			$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
			if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){	
				foreach($gglist as &$gg){
					$gg['sell_price'] = $shopset['show_price_uncheck_txt'];
				}
			}			
		}

        $sysset = Db::name('admin_set')->where('aid',aid)->field('name,logo,desc,fxjiesuantype,tel,kfurl,gzts,ddbb')->find();

        $shopset_field = 'showjd,comment,showcommission,hide_sales,hide_stock';
        if(getcustom('product_show_costprice_fanwei')){
            $shopset_field.=',product_show_costprice_fanwei,costprice_levelids';
        }
        if(getcustom('product_show_marketprice')){
            $shopset_field.=',product_show_marketprice';
        }
       
        $shopset = Db::name('shop_sysset')->where('aid',aid)->field($shopset_field)->find();

        $resetData = false;
        if(getcustom('product_show_guige_type') && $product['guige_show_type'] == 1 && input('param.reset/d')){
          $resetData = true;
        }
		$guigelist = array();
		foreach($gglist as $k=>$v){
			if($product['balance'] > 0){
				$v['advance_price'] = round($v['sell_price'] * (1 - $product['balance']*0.01),2);
				$v['balance_price'] = round($v['sell_price'] * $product['balance']*0.01,2);
			}else{
				$v['balance_price'] = 0;
			}
            //预计佣金
            $commission = 0;
            $v['commission_desc'] = '元';
            $v['commission_desc_score'] = t('积分');
			//计算佣金
            if($this->member && $shopset['showcommission']==1){
                if($product['commissionset']!=-1){
                    $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
                    if($userlevel['can_agent']!=0){
                        if($product['commissionset']==1){//按比例
                            $commissiondata = json_decode($product['commissiondata1'],true);
                            if($commissiondata){
                                $commission = $commissiondata[$userlevel['id']]['commission1'] * ($v['sell_price'] - ($sysset['fxjiesuantype']==2 ? $v['cost_price'] : 0)) * 0.01;
                            }
                        }elseif($product['commissionset']==2){//按固定金额
                            $commissiondata = json_decode($product['commissiondata2'],true);
                            if($commissiondata){
                                $commission = $commissiondata[$userlevel['id']]['commission1'];
                            }
                        }elseif($product['commissionset']==3) {//提成是积分
                            $commissiondata = json_decode($product['commissiondata3'], true);
                            if ($commissiondata) {
                                $commissionScore = $commissiondata[$userlevel['id']]['commission1'];
                            }
                            //elseif($product['commissionset']==4 && $product['lvprice']==1)//按价格差
                            //    $lvprice_data = json_decode($v['lvprice_data'],true);
                            //    $commission = array_shift($lvprice_data) - $v['sell_price'];
                            //    if($commission < 0) $commission = 0;
                        }elseif($product['commissionset']==5){// 5 提成比例+积分
                            $commissiondata = json_decode($product['commissiondata1'],true);
                            if($commissiondata){
                                $commission = $commissiondata[$userlevel['id']]['commission1'] * ($v['sell_price'] - ($sysset['fxjiesuantype']==2 ? $v['cost_price'] : 0)) * 0.01;
                            }
                            $commissiondata = json_decode($product['commissiondata3'], true);
                            if ($commissiondata) {
                                $commissionScore = $commissiondata[$userlevel['id']]['commission1'];
                            }
                        }elseif($product['commissionset']==6){//提成金额+积分
                            $commissiondata = json_decode($product['commissiondata2'],true);
                            if($commissiondata){
                                $commission = $commissiondata[$userlevel['id']]['commission1'] *1;
                            }
                            $commissiondataScore = json_decode($product['commissiondata3'],true);
                            if($commissiondataScore){
                                $commissionScore = $commissiondataScore[$userlevel['id']]['commission1'];
                            }
                        }elseif($product['commissionset']==7){//分销按比例送积分
                            $commissiondata = json_decode($product['commissiondata4'],true);
                            if($commissiondata){
                                $commissionScore = $commissiondata[$userlevel['id']]['commission1'] * ($v['sell_price'] - ($sysset['fxjiesuantype']==2 ? $v['cost_price'] : 0)) * 0.01;
                            }
                        }elseif($product['commissionset']==0){//按会员等级
                            //fxjiesuantype 0按商品价格,1按成交价格,2按销售利润
                            if($userlevel['commissiontype']==1){ //固定金额按单
                                $commission = $userlevel['commission1'];
                            }else{
                                $commission = $userlevel['commission1'] * ($v['sell_price'] - ($sysset['fxjiesuantype']==2 ? $v['cost_price'] : 0)) * 0.01;
                            }
                        }
                    }
                }
                if($product['commissionset4']==1 && $product['lvprice']==1){ //极差分销
                    $lvprice_data = json_decode($v['lvprice_data'],true);
                    $commission += array_shift($lvprice_data) - $v['sell_price'];
                    if($commission < 0) $commission = 0;
                }
            }
			if(getcustom('more_productunit_guige')) {
				$gg_prounit = '';
				$prounit = $product['prounit'];
				$guige_unit = explode(',',$v['name']);
				foreach($prounit as $prounit_k=>$prounit_v){
					//从第二级单位开始匹配
					if($guige_unit[0] == $prounit_v && $prounit_k>0){
						$prounit_k_up = $prounit_k - 1;
						if(isset($v['prounit_'.$prounit_k_up]) && $v['prounit_'.$prounit_k_up]>0){
							$gg_min_unit_num = $v['prounit_'.$prounit_k_up];
							$gg_prounit = $prounit_v . '=' . $gg_min_unit_num . $product['prounit'][0];
						}								
					}
				}
				if($gg_prounit){
					$v['prounits'] = $gg_prounit;
				}else{
					$arr = [];
					if(!empty($v['prounit_0'])) array_push($arr,$product['prounit'][1] . '=' . $v['prounit_0'] . $product['prounit'][0]);
					if(!empty($v['prounit_1'])) array_push($arr,$product['prounit'][2] . '=' . $v['prounit_1'] . $product['prounit'][0]);
					if(!empty($v['prounit_2'])) array_push($arr,$product['prounit'][3] . '=' . $v['prounit_2'] . $product['prounit'][0]);				
					$v['prounits'] = implode('/',$arr);
				}
				
				$v['prounit'] = $product['prounit'][0];
			}

            $v['commissionScore'] = dd_money_format($commissionScore,$this->score_weishu);

            //价格显示方式
            if(getcustom('price_show_type')){
                $price_show_type = Db::name('shop_sysset')->where('aid',aid)->value('price_show_type');
                $shopset['price_show_type'] = $price_show_type;
                if($this->member){
                    $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
                }else{
                    $userlevel = [];
                }
                $defalut_level = Db::name('member_level')->where('aid',aid)->order('id asc')->find();
                
                if(empty($userlevel) || $defalut_level['id'] == $this->member['levelid'] || $product['lvprice'] ==0){
                    $product['is_vip'] =0;
                }else{
                    $product['is_vip'] =1;
                }
                if(in_array($price_show_type,[1,2])){ //开启会员价
                    $lvprice_data =json_decode($product['lvprice_data'],true);
                    if($product['is_vip'] == 0){//不是会员 查询下个会员
                        $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$userlevel['sort'])->order('sort,id')->find();
                        
                        if(empty($nextlevel) || $product['lvprice'] ==0 ){
                            $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$defalut_level['sort'])->order('sort,id')->find();
                        }
                        $level_name = $nextlevel['name'];
                        $v['sell_price_origin'] = $lvprice_data[$nextlevel['id']];
                    }else{
                        if($userlevel && $product['lvprice'] ==1 ){
                            $level_name =  $userlevel['name'];
                        }
                    }
                    $v['level_name'] =  $level_name;
                }
            }

            if(getcustom('product_show_marketprice') && $shopset['product_show_marketprice'] ==0){
                //隐藏市场价
                $v['market_price'] = 0;
            }

            if($resetData){
                list($x,$y) = explode(',',$v['ks']);
                list($v['x_name'],$v['y_name']) = explode(',',$v['name']);
                $v['checked'] = false;
                $v['num'] = 0;
                $guigelist[$y][$v['ks']] = $v;
            }else{
                $guigelist[$v['ks']] = $v;
            }
        }
		$guigedata = json_decode($product['guigedata'],true);
		$product['guigedatanum'] = count($guigedata);
		$ggselected = [];
		foreach($guigedata as $v) {
			$ggselected[] = 0;
		}
		$ks = implode(',',$ggselected);
        $shop_yuding = false;
        if(getcustom('shop_yuding')){
            $shop_yuding = true;
        }
		$shopset['guigename'] = '';
		if(getcustom('product_guige_showtype')){
            $guige_name = Db::name('shop_sysset')->where('aid',aid)->value('guige_name');
		    $shopset['guige_name'] = $guige_name;
        }
        $product['shop_yuding'] = $shop_yuding;

		$product['limitdata'] = ['ismemberlevel_limit'=>false];
		if(getcustom('product_memberlevel_limit')){
			if($product['levellimitdata']){
				$rs = \app\model\ShopProduct::memberlevel_limit(aid,mid,$product,$this->member['levelid']);
				if($rs['status']==1){
					$product['limitdata'] = $rs['limitdata'];
				}
			}
		}

        $product['product_show_costprice_fanwei'] = 0;
        if(getcustom('product_show_costprice_fanwei') && $shopset['product_show_costprice_fanwei'] ==1){
            $costprice_levelids = explode(',',$shopset['costprice_levelids']);
            if(in_array($this->member['levelid'],$costprice_levelids) || in_array('-1',$costprice_levelids)){
                $product['product_show_costprice_fanwei'] = 1;
            }
        }

        //新加料
        $jldata = [];
        if(getcustom('shop_product_jialiao')){
            $productjialiao = Db::name('shop_product_jialiao')->where('proid',$product['id'])->select()->toArray();
            foreach($productjialiao as $key=>$val){
                $jldata[] = ['id' => $val['id'],'title' => $val['title'],'limit_num'=>$val['limit_num'],'num' => 0,'price' => $val['price']];
            }
        }
		return $this->json(['status'=>1,'product'=>$product,'guigelist'=>$guigelist,'guigedata'=>$guigedata,'ggselected'=>$ggselected,'ks'=>$ks,'shopset' => $shopset,'jldata' =>$jldata]);
	}
	//商品评价
	public function commentlist(){
		$proid = input('param.proid/d');
		$pagenum = input('post.pagenum/d');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		if($proid != 0){
			$where[] = ['proid','=',$proid];
		}else{
			$where[] = ['bid','=',0];
		}
		$where[] = ['status','=',1];
		$datalist = Db::name('shop_comment')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$pl){
			$datalist[$k]['createtime'] = date('Y-m-d H:i',$pl['createtime']);
			if($datalist[$k]['content_pic']) $datalist[$k]['content_pic'] = explode(',',$datalist[$k]['content_pic']);
		}
        $addcommentbtn = false;
        if(getcustom('product_comment') && $pagenum==1){
            $product_comment = Db::name('shop_sysset')->where('aid',aid)->value('product_comment');
            $addcommentbtn = $product_comment?true:false;
        }
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist,'addcommentbtn'=>$addcommentbtn]);
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['addcommentbtn'] = $addcommentbtn;
		return $this->json($rdata);
	}
	//商品海报
	public function getposter(){
        $designer_info = Db::name('designer_shopdetail')->where('aid',aid)->where('bid',0)->find();
        $menudata = json_decode($designer_info['menudata'],true);
        if(!isset($menudata['need_login']) || $menudata['need_login']==1){
        	//必须登录
            $this->checklogin();
            $member = $this->member;
        }else{
            $member = [
                'id' => 0,
                'headimg' => PRE_URL.'/static/img/touxiang.png',
                'nickname' => '游客',
                'realname' => '',
                'mobile' => '',
            ];
        }
		
		$post = input('post.');
		$platform = platform;
		$page = '/pages/shop/product';
		$scene = 'id_'.$post['proid'].'-pid_'.$member['id'];
		//if($platform == 'mp' || $platform == 'h5' || $platform == 'app'){
		//	$page = PRE_URL .'/h5/'.aid.'.html#'. $page;
		//}
		$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','product')->where('platform',$platform)->order('id')->find();

//		$posterdata = Db::name('member_poster')->where('aid',aid)->where('mid',mid)->where('scene',$scene)->where('type','product')->where('posterid',$posterset['id'])->find();
		//关闭缓存
		if(true || !$posterdata){
			$product = Db::name('shop_product')->where('id',$post['proid'])->find();
			//$product = $this->formatproduct($product);
			$sysset = Db::name('admin_set')->where('aid',aid)->find();
			$textReplaceArr = [
				'[头像]'=>$member['headimg'],
				'[昵称]'=>$member['nickname'],
				'[姓名]'=>$member['realname'],
				'[手机号]'=>$member['mobile'],
				'[商城名称]'=>$sysset['name'],
				'[商品名称]'=>$product['name'],
				'[商品销售价]'=>$product['sell_price'],
				'[商品市场价]'=>$product['market_price'],
				'[商品图片]'=>$product['pic'],
			];

			$poster = $this->_getposter(aid,$product['bid'],$platform,$posterset['content'],$page,$scene,$textReplaceArr);
			$posterdata = [];
			$posterdata['aid'] = aid;
			$posterdata['mid'] = $member['id'];
			$posterdata['scene'] = $scene;
			$posterdata['page'] = $page;
			$posterdata['type'] = 'product';
			$posterdata['poster'] = $poster;
			$posterdata['posterid'] = $posterset['id'];
			$posterdata['createtime'] = time();
//			Db::name('member_poster')->insert($posterdata);
		}
		return $this->json(['status'=>1,'poster'=>$posterdata['poster']]);
	}
	//购物车 
	public function cart(){
		//$this->checklogin();
		$gwcdata = [];
		$field = 'id,bid,proid,ggid,num';
		if(getcustom('product_glass')){
		    $field .= ',glass_record_id';
        }
        if (getcustom('shop_product_jialiao')){
            $field .= ',jldata';
        }
		if(input('param.bid')){
			$cartlist = Db::name('shop_cart')->field($field)->where('aid',aid)->where('bid',input('param.bid'))->where('mid',mid)->order('createtime desc')->select()->toArray();
		}else{
			$cartlist = Db::name('shop_cart')->field($field)->where('aid',aid)->where('mid',mid)->order('createtime desc')->select()->toArray();
		}
		if(!$cartlist) $cartlist = [];
		$newcartlist = [];
		foreach($cartlist as $k=>$gwc){
			if($newcartlist[$gwc['bid']]){
				$newcartlist[$gwc['bid']][] = $gwc;
			}else{
				$newcartlist[$gwc['bid']] = [$gwc];
			}
		}
        $sysset = Db::name('admin_set')->where('aid',aid)->find();
		foreach($newcartlist as $bid=>$gwclist){
			if($bid == 0){
				$business = [
                    'id'=>$sysset['id'],
                    'name'=>$sysset['name'],
                    'logo'=>$sysset['logo'],
                    'tel'=>$sysset['tel']
                ];
			}else{
				$business = Db::name('business')->where('aid',aid)->where('id',$bid)->field('id,name,logo,tel')->find();
			}
			$prolist = [];
			foreach($gwclist as $gwc){
				$product = Db::name('shop_product')->where('aid',aid)->where('status','<>',0)->where('id',$gwc['proid'])->find();
				if(!$product){
					Db::name('shop_cart')->where('aid',aid)->where('proid',$gwc['proid'])->delete();continue;
				}
				$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$gwc['ggid'])->find();
				if(!$guige){
					Db::name('shop_cart')->where('aid',aid)->where('ggid',$gwc['ggid'])->delete();continue;
				}
                $guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);
				if($product['perlimitdan'] > 0 && $gwc['num'] > $product['perlimitdan']){
					$gwc['num'] = $product['perlimitdan'];
					Db::name('shop_cart')->where('aid',aid)->where('id',$gwc['id'])->update(['num'=>$gwc['num']]);
				}
				if(getcustom('product_wholesale') && $product['product_type'] == 4){
					$jieti_num = $gwc['num'];
					$jieti_discount_type = $product['jieti_discount_type'];
					if($jieti_discount_type == 0){
						$jieti_num = Db::name('shop_cart')->where('mid',mid)->where('proid',$product['id'])->sum('num');
					}
					
					$guige = $this->formatguigewholesale($guige,$product,$jieti_num);
				}
                if (getcustom('shop_product_jialiao')){
                    if($gwc['jldata']){
                        $jldata = json_decode($gwc['jldata'],true);
                        $njlprice = 0;
                        $njltitle = '';
                        foreach($jldata as $key=>$val){
                            $njlprice += $val['num'] * $val['price'];
                            $njltitle .=$val['title'].'*'.$val['num'].'/';
                        }
                        $njltitle = rtrim($njltitle,'/');
                        $guige['sell_price'] = dd_money_format($guige['sell_price'] + $njlprice) ;
                        $guige['name'] = $guige['name'].'('.rtrim($njltitle,'/').')';
                    }
                }
				$tmpitem = ['id'=>$gwc['id'],'checked'=>true,'product'=>$product,'guige'=>$guige,'num'=>$gwc['num']];
				if(getcustom('product_glass')){
				    $glassrecord = '';
				    if($gwc['glass_record_id']){
				        $glassrecord = Db::name('glass_record')->where('aid',aid)->where('mid',$this->mid)->where('id',$gwc['glass_record_id'])->find();
                    }
                    $tmpitem['glassrecord'] = $glassrecord??'';
                }
                if (getcustom('shop_product_jialiao')){
                    $tmpitem['jldata'] = $gwc['jldata']??'';
                }
				$prolist[] = $tmpitem;
			}
			$newcartlist[$bid] = ['bid'=>$bid,'checked'=>true,'business'=>$business,'prolist'=>$prolist];
		}
		$shopset_field = 'showjd,comment,showcommission,hide_sales,hide_stock,show_lvupsavemoney,gwctj';
        //未登录查看价格
        if(getcustom('show_price_unlogin')){
            $shopset_field.=',is_show_price_unlogin,show_price_unlogin_txt';		
        }
        //未审核查看价格
        if(getcustom('show_price_uncheck')){
            $shopset_field.=',is_show_price_uncheck,show_price_uncheck_txt';		
        }
		if(getcustom('product_share_cart')){
			$shopset_field.=',share_cart';
		}
        if(getcustom('product_show_price_fanwei')){
            $shopset_field.=',product_show_price_fanwei';
        }
        if(getcustom('product_show_costprice_fanwei')){
            $shopset_field.=',product_show_costprice_fanwei,costprice_levelids';
        }
        if(getcustom('product_show_marketprice')){
            $shopset_field.=',product_show_marketprice';
        }
        $shopset = Db::name('shop_sysset')->where('aid',aid)->field($shopset_field)->find();
		if($shopset['gwctj']){
			$tjwhere = [];
			$tjwhere[] = ['aid','=',aid];
			$tjwhere[] = ['status','=',1];
			$tjwhere[] = ['ischecked','=',1];
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
			$tjwhere[] = Db::raw($where2);

			if(input('param.bid')){
				$tjwhere[] = ['bid','=',input('param.bid/d')];
			}else{
                if(getcustom('show_location')){
                    //定位模式:仅显示区域限制内的商品
                    $area = input('param.area');
                    $longitude = input('param.longitude/f');
                    $latitude = input('param.latitude/f');
                    if($sysset['mode']==2){
                        $b_where = [];
                        $b_where[] = ['aid','=',aid];
                        $b_where[] = ['status','=',1];
                        $b_where[] = ['is_open','=',1];
                        if($sysset['loc_range_type']==1){
                            if($latitude && $longitude){
                                $b_distance = $sysset['loc_range']?$sysset['loc_range']*1000:0;
                                $b_having = "round(6378.138*2*asin(sqrt(pow(sin( ({$latitude}*pi()/180-latitude*pi()/180)/2),2)+cos({$latitude}*pi()/180)*cos(latitude*pi()/180)* pow(sin( ({$longitude}*pi()/180-longitude*pi()/180)/2),2)))*1000) <={$b_distance}";
                                $limitBusiness = Db::name('business')->having($b_having)->where($b_where)->select()->toArray();
                                if($limitBusiness){
                                    $limitBids = array_column($limitBusiness,'id');
                                    $limitBids[] = 0;//追加平台0
                                    $tjwhere[] = ['bid','in',$limitBids];
                                }else{
                                    $tjwhere[] = ['bid','=',0];//仅显示平台商品
                                }
                            }
                        }else{
                            //同城
                            if($area){
                                //取省或者市
                                $areaArr = explode(',',$area);
                                $areaKey = min(count($areaArr)-1,1);//最小范围取到市
                                $areaName = $areaArr[$areaKey];
                                $b_where[] = ['city','=',$areaName];
                                $limitBids = Db::name('business')->where($b_where)->column('id');
                                if($limitBids){
                                    $limitBids[] = 0;
                                    $tjwhere[] = ['bid','in',$limitBids];
                                }else{
                                    $tjwhere[] = ['bid','=',0];//仅显示平台商品
                                }
                            }
                        }
                    }elseif ($sysset['mode']==3){
                        if(getcustom('product_bind_mendian')){
                            $mendian_id = input('param.mendian_id/d',0);
                            if($mendian_id){
                                $tjwhere[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
                            }
                        }
                    }
                    //定位模式
                }
				$business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
				if(!$business_sysset || $business_sysset['status']==0 || $business_sysset['product_isshow']==0){
					$tjwhere[] = ['bid','=',0];
				}
			}
			if(getcustom('business_bind_show_page') && $this->member && $this->member['bind_business']){
				$tjwhere[] = Db::raw('bid = '.$this->member['bind_business'].' or business_show = 1');

				//删除tjwhere数组里面的bid
				$tjwhere = array_filter($tjwhere, function($item) {
					return $item !== ['bid','=',0];
				});
			}

            $tjfields = "id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,price_type";
            if(getcustom('product_cost_show') || getcustom('product_show_costprice_fanwei')){
                $tjfields .= ',cost_price';
            }
            if(getcustom('product_service_fee')){
                $tjfields .= ',service_fee,service_fee_switch,service_fee_data';
            }
            if(getcustom('extend_exchange_card')){
                //预售类型的不展示，用与兑换卡使用
                $tjwhere[] = ['product_type' ,'<>',11];
            }
			$tjdatalist = Db::name('shop_product')->field($tjfields)->where($tjwhere)->limit(12)->order(Db::raw('rand()'))->select()->toArray();
			if(!$tjdatalist) $tjdatalist = array();
			$tjdatalist = $this->formatprolist($tjdatalist);
            //未登录查看价格
            if(getcustom('show_price_unlogin')){
                $mid = mid;
                if(!$mid && $shopset['is_show_price_unlogin'] == 0){
                    foreach($tjdatalist as &$pv){
                        $pv['sell_price'] =  $shopset['show_price_unlogin_txt'];					
                    }
                }			
            }
            //未审核查看价格
            if(getcustom('show_price_uncheck')){
                if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){
                    foreach($tjdatalist as &$pv){
                        $pv['sell_price'] =  $shopset['show_price_uncheck_txt'];					
                    }
                }			
            }
            if($tjdatalist){
	            foreach($tjdatalist as &$tv){
	                if(getcustom('member_level_price_show')){
	                    //获取第一个规格的会员等级价格
	                    $priceshows = [];
	                    $price_show = 0;
	                    $price_show_text = '';
	                }
	                $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$tv['id'])->select()->toArray();
	                foreach($gglist as $k=>$v){
	                    if(getcustom('member_level_price_show')){
	                        //获取第一个规格的会员等级价格
	                        if($k == 0 && $tv['lvprice'] == 1 && $v['lvprice_data']){
	                            $lvprice_data = json_decode($v['lvprice_data'],true);
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
	                                        $tv['sell_putongprice'] = $lv;
	                                    }
	                                    $lk ++ ;
	                                }
	                                unset($lid);unset($lv);
	                            }
	                            
	                        }
	                    }
	                }
	                if(getcustom('member_level_price_show')){
	                    //获取第一个规格的会员等级价格
	                    $tv['priceshows'] = $priceshows?$priceshows:'';
	                    $tv['price_show'] = $price_show;
	                    $tv['price_show_text'] = $price_show_text;
	                }
	            }
	            unset($tv);
	        }
		}else{
			$tjdatalist = [];
		}
		$rdata = [];
		$rdata['cartlist'] = array_values($newcartlist);
        if(getcustom('xixie')){
            if(!input('param.bid')){
                $xx_list = Db::name('xixie_cart')->where('mid',mid)->where('aid',aid)->field('id,bid,proid,num')->order('createtime desc')->select()->toArray();

                $xx_newlist = [];
                foreach($xx_list as $xcv){
                    if($xx_newlist[$xcv['bid']]){
                        $xx_newlist[$xcv['bid']][] = $xcv;
                    }else{
                        $xx_newlist[$xcv['bid']] = [$xcv];
                    }
                }
                foreach($xx_newlist as $xx_bid=>$xnv){
                    $business = Db::name('admin_set')->where('aid',aid)->field('id,name,logo,tel')->find();
                    if($business){
                        $business['name'] .= ' 洗鞋';
                    }
                    $prolist = [];
                    foreach($xnv as $xv){
                        $product = Db::name('xixie_product')->where('id',$xv['proid'])->where('aid',aid)->where('status','<>',0)->find();
                        if(!$product){
                            Db::name('xixie_cart')->where('proid',$xv['proid'])->where('aid',aid)->delete();continue;
                        }
                        if($this->member['is_vip'] && $product['vip_price']>0){
                            $product['sell_price'] = $product['vip_price'];
                        }
                        if($product['buymax'] > 0 && $xv['num'] > $product['buymax']){
                            $xv['num'] = $product['buymax'];
                            Db::name('xixie_cart')->where('aid',aid)->where('id',$xv['id'])->update(['num'=>$xv['num']]);
                        }
                        $prolist[] = ['id'=>$xv['id'],'checked'=>true,'product'=>$product,'num'=>$xv['num'],'type'=>2];
                    }
                    $xx_newlist[$xx_bid] = ['bid'=>$xx_bid,'checked'=>true,'business'=>$business,'prolist'=>$prolist];
                }
            }else{
                $xx_newlist = '';
            }
            $rdata['xixie'] = true;
            $rdata['xixie_cartlist'] = $xx_newlist?array_values($xx_newlist):'';
        }

        if(getcustom('product_show_price_fanwei') && $shopset['product_show_price_fanwei'] ==1){
            foreach ($tjdatalist as &$dv){
                $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$dv['id'])->select()->toArray();
                if(count($gglist) > 1)
                {
                    $jgarr = [];
                    foreach($gglist as $gek=>$gev){
                        if($dv['lvprice'] == 1 && $gev['lvprice_data'] && $this->member){
                            //会员价格
                            $lvprice_data = json_decode($gev['lvprice_data'],true);
                            foreach ($lvprice_data as $lid=>$lv){
                                if($this->member['levelid'] == $lid){
                                    $jgarr[] = $lv;
                                }
                            }
                        }else{
                            $jgarr[] = $gev['sell_price'];
                        }
                    }
                    $dv['min_price'] = min($jgarr);
                    $dv['max_price'] = max($jgarr);

                    if($dv['min_price'] == 0 && $dv['max_price'] == 0){
                        $dv['product_show_price_fanwei'] = 0;
                    }else{
                        $dv['sell_price'] = sprintf('%g',$dv['min_price']).'-'.sprintf('%g',$dv['max_price']);
                    }
                }
            }
            unset($dv);
        }

        if(getcustom('product_show_costprice_fanwei')){
            if($shopset['product_show_costprice_fanwei'] ==1){
                $costprice_levelids = explode(',',$shopset['costprice_levelids']);
                $shopset['product_show_costprice_fanwei'] = 0;
                if(in_array($this->member['levelid'],$costprice_levelids) || in_array('-1',$costprice_levelids)){
                    $shopset['product_show_costprice_fanwei'] = 1;
                    foreach ($tjdatalist as &$dv){
                        $dv['product_show_costprice_fanwei'] = 1;
                        //关闭划线市场价
                        //$dv['market_price'] = 0;

                        $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$dv['id'])->select()->toArray();
                        if($gglist)
                        {
                            $jgarr = [];
                            foreach($gglist as $gek=>$gev){
                                $jgarr[] = $gev['cost_price'];
                            }
                            $dv['min_price'] = min($jgarr);
                            $dv['max_price'] = max($jgarr);

                            if($dv['min_price'] == 0 && $dv['max_price'] == 0){
                                $dv['cost_price_fanwei'] = $dv['cost_price'];
                            }else{
                                $dv['cost_price_fanwei'] = sprintf('%g',$dv['min_price']).'-'.sprintf('%g',$dv['max_price']);
                            }
                        }else{
                            $dv['cost_price_fanwei'] = $dv['cost_price'];
                        }

                        if(is_null($dv['cost_price_fanwei'])){
                            $dv['product_show_costprice_fanwei'] = 0;
                        }
                    }
                    unset($dv);
                }
            }
        }

        if(getcustom('product_show_marketprice')){
            if($shopset['product_show_marketprice'] ==0){
                foreach ($tjdatalist as &$dv){
                    //关闭划线市场价
                    $dv['market_price'] = 0;
                }
                unset($dv);
            }
        }

		$rdata['tjdatalist'] = $tjdatalist;
		$rdata['set'] = $shopset;
		if(getcustom('product_share_cart')){
			$rdata['share_cart'] = $shopset['share_cart'];
		}
		return $this->json($rdata);
	}
	public function formatguigewholesale($guige, $product,$num){
		if(!$this->member) return $guige;
		if(empty($product['jieti_discount_data'])) return $guige;
		$jieti_discount_data = json_decode($product['jieti_discount_data'],true);
		$sell_price = $guige['sell_price'];
		foreach($jieti_discount_data as $k=>$v){			
			if($num>=$v['start_num'] && $v['ratio']>0){
				$guige['sell_price'] = round($sell_price*$v['ratio']*0.01,2);
			}			
		}
		return $guige;
	}
	public function addcart(){
		$this->checklogin();
		$post = input('post.');
		$oldnum = 0;
		$num = intval($post['num']);
		//$input_num>0 说明是输入了总数量（$oldnum就没了）进行直接修改， 
		$input_num = input('param.input_num/d');
		if($input_num > 0)$num =  $input_num;
        //如果输入数量 = 0，且加减数量也没有，说明是要删除该信息
		if($input_num ==0 && !$num){
            Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('ggid',$post['ggid'])->delete();
            $cartnum = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
            return $this->json(['status'=>1,'msg'=>'移除成功','cartnum'=>$cartnum]);
        }
		$product = Db::name('shop_product')->where('aid',aid)->where('status','<>',0)->where('ischecked',1)->where('id',$post['proid'])->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
		if($product['freighttype']==3 || $product['freighttype']==4) return $this->json(['status'=>0,'msg'=>'虚拟商品不能加入购物车']);
        if(getcustom('member_realname_verify')){
            if($product['realname_buy_status'] == 1 && $this->member['realname_status'] != 1){
                return $this->json(['status'=>-4,'msg'=>'未实名不可购买此商品','url'=>'/pagesExt/my/setrealname']);
            }
        }
		if(getcustom('shop_product_fenqi_pay')){
			if($product['product_type']==5) return $this->json(['status'=>0,'msg'=>'分期商品不能加入购物车']);
		}
        if(getcustom('product_quanyi')){
            if($product['product_type']==8) return $this->json(['status'=>0,'msg'=>t('权益商品').'不能加入购物车']);
        }

		if(!$post['ggid']){
			if($num > 0){
				$post['ggid'] = Db::name('shop_guige')->where('aid',aid)->where('proid',$post['proid'])->value('id');
			}else{
				$post['ggid'] = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->order('id desc')->value('ggid');
			}
		}
        $g_where = [];
        $g_where[]= ['aid','=',aid];
        $g_where[]= ['mid','=',mid];
        $g_where[]= ['proid','=',$post['proid']];
        $g_where[]= ['ggid','=',$post['ggid']];
        if(getcustom('shop_product_jialiao')){
            $jldata = input('param.jldata');
            $jialiao = [];
            foreach($jldata as $key=>$val){
                if($val['num'] >0){
                    $jialiao[]=  $val;
                }
            }
            if($jialiao){
                $json_jialiao = json_encode($jialiao,JSON_UNESCAPED_UNICODE);
                $g_where[]= ['jldata','=',$json_jialiao];
            }else{
                $g_where[]= ['jldata','=',''];
            }
        }
		$gwc = Db::name('shop_cart')->where($g_where)->find();
		if($gwc && !$input_num) $oldnum = $gwc['num'];
		if($num > 0 && $product['limit_start'] > 0 && $oldnum + $num < $product['limit_start']){
			$num = $product['limit_start'];
		}
		if($num > 0 && $product['perlimitdan'] > 0 && $oldnum + $num > $product['perlimitdan']){
			return $this->json(['status'=>0,'msg'=>'每单限购'.$product['perlimitdan'].'件']);
			$num = $product['perlimitdan'];
		}
        //库存校验
        $curnum = $oldnum + $num;
        $stock = Db::name('shop_guige')->where('aid',aid)->where('proid',$post['proid'])->where('id',$post['ggid'])->value('stock');
        if($stock<=0 || $stock<$curnum){
            return $this->json(['status'=>0,'msg'=>$product['name'].'库存不足']);
        }
        $product_memberlevel_limit_month_custom= getcustom('product_memberlevel_limit_month');
		if(getcustom('product_memberlevel_limit')){
			if($product['levellimitdata']){
                $product['num'] = $num;
				$rs = \app\model\ShopProduct::memberlevel_limit(aid,mid,$product,$this->member['levelid']);
                if($rs['status']==1 && $rs['limitdata']['ismemberlevel_limit']){
                    $limt_errmsg = '该商品'.$rs['limitdata']['days'].'天内限购'.$rs['limitdata']['limit_num'].'件';
                    if($product_memberlevel_limit_month_custom){
                        if($rs['limitdata']['days_type'] ==1){
                            $limt_errmsg = '本月购买额度已用完，请次月购买。';
                        }
                    }
                    return $this->json(['status'=>0,'msg'=>$limt_errmsg]);
                }
			}
		}



        if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户、用户组
            $tr_check = new \app\common\TengRuiCheck();
            $check_product = $tr_check->check_product($this->member,$product);
            if($check_product && $check_product['status'] == 0){
                return $this->json(['status'=>$check_product['status'],'msg'=>$check_product['msg']]);
            }
            $tr_roomId = $check_product['tr_roomId'];
        }
		if($oldnum + $num <=0 || ($product['limit_start'] > 0 && $oldnum + $num < $product['limit_start'])){
			Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('ggid',$post['ggid'])->delete();
			$cartnum = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
			return $this->json(['status'=>1,'msg'=>'移除成功','cartnum'=>$cartnum]);
		}
		if($gwc){
            $updata = [];
//		    $updata['createtime'] = time();
            if(getcustom('product_glass')){
                $updata['glass_record_id'] = $post['glass_record_id'];
            }
            if($input_num > 0){
                $updata['num'] = $input_num ;
                $num = 0;
            }
			Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('ggid',$post['ggid'])->inc('num',$num)->update($updata);
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['bid'] = $product['bid'];
			$data['mid'] = mid;
			$data['ggid'] = $post['ggid'];
			$data['createtime'] = time();
			$data['proid'] = $post['proid'];
			$data['num'] = $num;
			if(getcustom('product_glass')){
			    $data['glass_record_id'] = $post['glass_record_id'];
            }
            if(getcustom('shop_product_jialiao')){
                $data['jldata'] = $jialiao?json_encode($jialiao,JSON_UNESCAPED_UNICODE):'';
            }
			Db::name('shop_cart')->insert($data);
		}
		$cartnum = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
		return $this->json(['status'=>1,'msg'=>'加入购物车成功','cartnum'=>$cartnum]);
	}
	public function addcartmore(){
		$this->checklogin();
		//$post = input('post.');
		$data = input('post.');
		$prodata = explode('-',$data['prodata']);
		foreach($prodata as $key=>$pro){
			$sdata = explode(',',$pro);
			$post['proid'] = $sdata[0];
			$post['ggid'] = $sdata[1];
			$post['num'] = $sdata[2];
			$post['glass_record_id'] = $sdata[2]??0;
		$oldnum = 0;
		$num = intval($post['num']);
		
		$product = Db::name('shop_product')->where('aid',aid)->where('status','<>',0)->where('ischecked',1)->where('id',$post['proid'])->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
		if($product['freighttype']==3 || $product['freighttype']==4) return $this->json(['status'=>0,'msg'=>'虚拟商品不能加入购物车']);
		if(!$post['ggid']){
			if($num > 0){
				$post['ggid'] = Db::name('shop_guige')->where('aid',aid)->where('proid',$post['proid'])->value('id');
			}else{
				$post['ggid'] = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->order('id desc')->value('ggid');
			}
		}

		$gwc = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('ggid',$post['ggid'])->find();
		if($gwc) $oldnum = $gwc['num'];

		if($product['product_type'] == 4){
			if($product['jieti_discount_type']==1){
				if($num > 0 && $product['limit_start'] > 0 && $oldnum + $num < $product['limit_start']){
					$num = $product['limit_start'];
				}
			}
		}else{
			if($num > 0 && $product['limit_start'] > 0 && $oldnum + $num < $product['limit_start']){
				$num = $product['limit_start'];
			}
		}		
		if($num > 0 && $product['perlimitdan'] > 0 && $oldnum + $num > $product['perlimitdan']){
			return $this->json(['status'=>0,'msg'=>'每单限购'.$product['perlimitdan'].'件']);
			$num = $product['perlimitdan'];
		}
        $product_memberlevel_limit_month_custom= getcustom('product_memberlevel_limit_month');
		if(getcustom('product_memberlevel_limit')){
			if($product['levellimitdata']){
                $product['num'] = $num;
				$rs = \app\model\ShopProduct::memberlevel_limit(aid,mid,$product,$this->member['levelid']);
				if($rs['status']==1 && $rs['limitdata']['ismemberlevel_limit']){
                    $limt_errmsg = '该商品'.$rs['limitdata']['days'].'天内限购'.$rs['limitdata']['limit_num'].'件';
                    if($product_memberlevel_limit_month_custom){
                        if($rs['limitdata']['days_type'] ==1){
                            $limt_errmsg = '本月购买额度已用完，请次月购买。';
                        }
                    }
                    return $this->json(['status'=>0,'msg'=>$limt_errmsg]);
				}
			}
		}



        if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户、用户组
            $tr_check = new \app\common\TengRuiCheck();
            $check_product = $tr_check->check_product($this->member,$product);
            if($check_product && $check_product['status'] == 0){
                return $this->json(['status'=>$check_product['status'],'msg'=>$check_product['msg']]);
            }
            $tr_roomId = $check_product['tr_roomId'];
        }
		if($oldnum + $num <=0 || ($product['limit_start'] > 0 && $oldnum + $num < $product['limit_start'])){
			Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('ggid',$post['ggid'])->delete();
			$cartnum = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
			return $this->json(['status'=>1,'msg'=>'移除成功','cartnum'=>$cartnum]);
		}
		if($gwc){
		    $updata['createtime'] = time();
            if(getcustom('product_glass')){
                $updata['glass_record_id'] = $post['glass_record_id'];
            }
			Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('proid',$post['proid'])->where('ggid',$post['ggid'])->inc('num',$num)->update($updata);
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['bid'] = $product['bid'];
			$data['mid'] = mid;
			$data['ggid'] = $post['ggid'];
			$data['createtime'] = time();
			$data['proid'] = $post['proid'];
			$data['num'] = $num;
			if(getcustom('product_glass')){
			    $data['glass_record_id'] = $post['glass_record_id'];
            }
			Db::name('shop_cart')->insert($data);
		}
	}
		$cartnum = Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->sum('num');
		return $this->json(['status'=>1,'msg'=>'加入购物车成功','cartnum'=>$cartnum]);
	}
	public function cartChangenum(){
		$this->checklogin();
		$id = input('post.id/d');
		$num = input('post.num/d');
        $type = input('post.type')?input('post.type'):'';
		if($num < 1) $num = 1;
        $cart = Db::name('shop_cart')->where('aid',aid)->where('id',$id)->where('mid',mid)->find();
        $product = Db::name('shop_product')->where('aid',aid)->where('id',$cart['proid'])->find();
        $guige = Db::name('shop_guige')->where('aid',aid)->where('id',$cart['ggid'])->find();
        if(!$type){
            if($guige['limit_start'] > 0 && $num < $guige['limit_start']){
                return $this->json(['status'=>0,'msg'=>'该商品规格'.$guige['limit_start'].'件起售']);
            }
            if($product['limit_start'] > 0 && $num < $product['limit_start']){
                return $this->json(['status'=>0,'msg'=>'该商品'.$product['limit_start'].'件起售']);
            }
            if($product['perlimitdan'] > 0 && $num > $product['perlimitdan']){
                return $this->json(['status'=>0,'msg'=>'该商品每单限购'.$product['perlimitdan'].'件']);
            }
        }
        //库存校验
        $stock = $guige['stock'];
        if($stock<=0 || $stock<$num){
            $num = $stock;
            Db::name('shop_cart')->where('id',$id)->where('mid',mid)->update(['num'=>$num]);
            return $this->json(['status'=>2,'msg'=>'库存不足','num'=>$num]);
        }
        $product_memberlevel_limit_month_custom= getcustom('product_memberlevel_limit_month');
		if(getcustom('product_memberlevel_limit')){
			if($product['levellimitdata']){
                $product['num'] = $num;
				$rs = \app\model\ShopProduct::memberlevel_limit(aid,mid,$product,$this->member['levelid']);
				if($rs['status']==1 && $rs['limitdata']['ismemberlevel_limit']){
                    $limt_errmsg = '该商品'.$rs['limitdata']['days'].'天内限购'.$rs['limitdata']['limit_num'].'件';
                    if($product_memberlevel_limit_month_custom){
                        if($rs['limitdata']['days_type'] ==1){
                            $limt_errmsg = '本月购买额度已用完，请次月购买。';
                        }
                    }
                    return $this->json(['status'=>0,'msg'=>$limt_errmsg]);
				}
			}
		}

        if(getcustom('xixie')){
            if($type == 2){
                $cart = Db::name('xixie_cart')->where('id',$id)->where('mid',mid)->where('aid',aid)->find();
                $product = Db::name('xixie_product')->where('id',$cart['proid'])->where('aid',aid)->find();
                if($product['buymax'] > 0 && $num > $product['buymax']){
                    return $this->json(['status'=>0,'msg'=>'该商品每单限购'.$product['buymax'].'件']);
                }
                $up = Db::name('xixie_cart')->where('id',$id)->where('mid',mid)->update(['num'=>$num]);
                if($up){
                    return $this->json(['status'=>1,'msg'=>'修改成功']);
                }else{
                    return $this->json(['status'=>0,'msg'=>'修改成功']);
                }
            }
        }
		Db::name('shop_cart')->where('id',$id)->where('mid',mid)->update(['num'=>$num]);
		return $this->json(['status'=>1,'msg'=>'修改成功']);
	}
	public function cartdelete(){
		$this->checklogin();
		$id = input('post.id/d');
        $type = input('post.type')?input('post.type'):'';
        if(!$type){
    		if(!$id){
    			$bid = input('post.bid/d');
    			Db::name('shop_cart')->where('bid',$bid)->where('mid',mid)->delete();
    			return $this->json(['status'=>1,'msg'=>'删除成功']);
    		}
        }
        if(getcustom('xixie')){
            if($type == 2){
                if($id){
                    //删除购物车
                    $del = Db::name('xixie_cart')->where('id',$id)->where('mid',mid)->delete();
                    if($del){
                        return $this->json(['status'=>1,'msg'=>'删除成功']);
                    }else{
                        return $this->json(['status'=>0,'msg'=>'删除失败']);
                    }
                }else{
                    //删除购物车
                    $del = Db::name('xixie_cart')->where('bid',0)->where('mid',mid)->delete();
                    if($del){
                        return $this->json(['status'=>1,'msg'=>'删除成功']);
                    }else{
                        return $this->json(['status'=>0,'msg'=>'删除失败']);
                    }
                    return $this->json(['status'=>0,'msg'=>'删除失败']);
                }
            }
        }
		Db::name('shop_cart')->where('id',$id)->where('mid',mid)->delete();
		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}
	public function cartclear(){
		$bid = input('param.bid');
		if(!$bid) $bid = 0;
		Db::name('shop_cart')->where('aid',aid)->where('bid',$bid)->where('mid',mid)->delete();
		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}
	//快速购买页
	public function fastbuy(){
        //需要判断的定制标识
		$mendian_id = input('param.mendian_id/d',0);
        $custom = ['v'=>'2.0'];
		$bid = input('param.bid');
		if(!$bid) $bid = 0;
		$cid = input('param.cid');
		if(!$cid) $cid = 0;
		$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
		if($bid > 0){
			$clist = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
		}
		foreach($clist as $k=>$v){
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',$bid];
			//$where[] = ['status','=',1];
			$where[] = ['ischecked','=',1];
			$where[] = ['freighttype','<',3];
			if(isdouyin == 1){
				$where[] = ['douyin_product_id','<>',''];
			}else{
				$where[] = ['douyin_product_id','=',''];
			}
			$nowtime = time();
			$nowhm = date('H:i');
			$where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

			//子分类 
			if($bid > 0){
				$childcid = db('shop_category2')->where(['aid'=>aid,'pid'=>$v['id']])->column('id');
				if($childcid){
					$child2cid = db('shop_category2')->where(['aid'=>aid,'pid'=>['in',$childcid]])->column('id');
					$cCate = array_merge($childcid, $child2cid, [$v['id']]);
					if($cCate){
						$whereCid = [];
						foreach($cCate as $c2){
							$whereCid[] = "find_in_set({$c2},cid2)";
						}
						$where[] = Db::raw(implode(' or ',$whereCid));
					}
				} else {
					$where[] = Db::raw("find_in_set(".$v['id'].",cid2)");
				}
			}else{
				$childcid = db('shop_category')->where($tjwhere)->where(['aid'=>aid,'pid'=>$v['id']])->column('id');
				if($childcid){
					$child2cid = db('shop_category')->where($tjwhere)->where(['aid'=>aid,'pid'=>['in',$childcid]])->column('id');
					$cCate = array_merge($childcid, $child2cid, [$v['id']]);
					if($cCate){
						$whereCid = [];
						foreach($cCate as $c2){
							$whereCid[] = "find_in_set({$c2},cid)";
						}
						$where[] = Db::raw(implode(' or ',$whereCid));
					}
				} else {
					$where[] = Db::raw("find_in_set(".$v['id'].",cid)");
				}
			}
			if(getcustom('product_bind_mendian')){
				if($mendian_id>0){
					$where[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
				}
			}
			//是否可见
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
            $shop_product_field = "pic,id,name,stock,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,guigedata,limit_start,price_type,product_type,fwid,weight";
            if(getcustom('product_service_fee')){
                $shop_product_field += ",service_fee,service_switch,service_fee_data";
            }
            if(getcustom('extend_exchange_card')){
                //预售类型的不展示，用与兑换卡使用
                $where[] = ['product_type' ,'<>',11];
            }
			$prolist = Db::name('shop_product')->field($shop_product_field)->where($where)->order('sort desc,id desc')->select()->toArray();
			//echo db('shop_product')->getlastsql();die;
			if(!$prolist) $prolist = [];
			if(getcustom('product_wholesale')){
				foreach($prolist as $k2=>$v2){
					if($v2['product_type'] == 4){
						$guigedata = json_decode($v2['guigedata'],true);
						$prolist[$k]['gg_num'] =  count($guigedata);
					}
				}
			}
			if($prolist){
                foreach($prolist as $dk=>&$dv){
                    if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        $priceshows = [];
                        $price_show = 0;
                        $price_show_text = '';
                    }

                    $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$dv['id'])->select()->toArray();
                    if(getcustom('shop_showgivescore')){
						$showgivescore1 = -1;//最小赠送积分
						$showgivescore2 = -1;//最大赠送积分
					}
                    foreach($gglist as $gk=>$gv){
                        if(getcustom('member_level_price_show')){
                            //获取第一个规格的会员等级价格
                            if($gk == 0 && $dv['lvprice'] == 1 && $gv['lvprice_data']){
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
                                            $dv['sell_putongprice'] = $lv;
                                        }
                                        $lk ++ ;
                                    }
                                    unset($lid);unset($lv);
                                }
                            }
                        }
                        if(getcustom('shop_showgivescore')){
	                    	$sgres = \app\common\System::showgivescore($shopset['showgivescore'],$showgivescore1,$showgivescore2,$gv['givescore']);
	                    	if($sgres){
	                    		$showgivescore1 = $sgres['showgivescore1'];//最小赠送积分
								$showgivescore2 = $sgres['showgivescore2'];//最大赠送积分
	                    	}
	                    }
                    }
                    unset($gk);unset($gv);
	                if(getcustom('shop_showgivescore')){
	                    $dv['showgivescore'] = \app\common\System::showgivescore2($shopset['showgivescore'],$showgivescore1,$showgivescore2);
	                }

                    if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        $dv['priceshows'] = $priceshows?$priceshows:'';
                        $dv['price_show'] = $price_show;
                        $dv['price_show_text'] = $price_show_text;
                    }
                }
                unset($dk);unset($dv);
            }
			$prolist = $this->formatprolist($prolist);
			//未登录查看价格
			if(getcustom('show_price_unlogin')){
				$mid = mid;
				if(!$mid && $shopset['is_show_price_unlogin'] == 0){
					foreach($prolist as &$pv){
						$pv['sell_price'] =  $shopset['show_price_unlogin_txt'];					
					}
				}			
			}
			//未审核查看价格
			if(getcustom('show_price_uncheck')){
				if(mid && $this->member['checkst'] !=1 && $shopset['is_show_price_uncheck'] == 0){
					foreach($prolist as &$pv){
						$pv['sell_price'] =  $shopset['show_price_uncheck_txt'];					
					}
				}			
			}
			if(!$prolist){
				unset($clist[$k]);
			}else{
				$clist[$k]['prolist'] = $prolist;
			}
		}
		$clist = array_values($clist);

		$list = Db::name('shop_cart')->where('aid',aid)->where('bid',$bid)->where('mid',mid)->order('createtime desc')->select()->toArray();
		$total = 0;
		$totalprice = 0;
		foreach($list as $k=>$v){
			$product = Db::name('shop_product')->field('pic,aid,bid,id,name,stock,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,guigedata,limit_start,product_type')->where('id',$v['proid'])->find();
			if(!$product){
				unset($list[$k]);
				Db::name('shop_cart')->where('id',$v['id'])->delete();
				continue;
			}
			$product = $this->formatproduct($product);
			$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$v['ggid'])->find();
			if(!$guige){
				unset($list[$k]);
				Db::name('shop_cart')->where('id',$v['id'])->delete();
				continue;
			}
            $guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);
			$list[$k]['product'] = $product;
			$list[$k]['guige'] = $guige;
			$total += $v['num'];
			$totalprice += $guige['sell_price'] * $v['num'];
		}
		$totalprice = number_format($totalprice,2,'.','');
		$cartList = ['list'=>$list,'total'=>$total,'totalprice'=>$totalprice];
		$numtotal = [];
		foreach($clist as $i=>$v){
			foreach($v['prolist'] as $j=>$pro){
				$numtotal[$pro['id']] = 0;
			}
		}
		foreach($cartList['list'] as $i=>$v){
			$numtotal[$v['proid']] += $v['num'];
		}
        if(getcustom('product_show_fwlist')){
            $custom['product_show_fwlist'] = true;
        }
        if(getcustom('product_show_sellpoint')){
            $custom['product_show_sellpoint'] = true;
        }
        if(getcustom('product_show_sellpoint')){
            $custom['product_weight'] = true;
        }
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['data'] = $clist;
		$rdata['cartList'] = $cartList;
		$rdata['numtotal'] = $numtotal;
		$rdata['custom'] = $custom;
		return $this->json($rdata);
	}
	
	//快速购买页2
	public function fastbuy2(){
        //需要判断的定制标识
        $custom = ['v'=>'2.0'];
		$mendian_id = input('param.mendian_id/d',0);
		$bid = input('param.bid');
		if(!$bid || $bid=='undefined') $bid = 0;

		//查询图片
        $shopset = Db::name('shop_sysset')->where('aid',aid)->find();

		if($bid!=0){
			$business = Db::name('business')->where('aid',aid)->where('id',$bid)->field('id,name,logo,content,pics,desc,tel,address,sales,start_hours,end_hours,latitude,longitude,comment_num,comment_score,comment_haopercent')->find();
			$business['pic'] = explode(',',$business['pics'])[0];
		}else{
			$business = Db::name('admin_set')->where('aid',aid)->field('id,name,logo,pics,`desc` content,tel,address,latitude,longitude')->find();
            
            $business['pic'] =  $shopset['fastbuy_toppic']?$shopset['fastbuy_toppic']:'';
			$comment_num = 0 + Db::name('shop_comment')->where('aid',aid)->where('bid',0)->where('status',1)->count();
			
			$haonum = 0 + Db::name('shop_comment')->where('aid',aid)->where('bid',0)->where('status',1)->where('score','>',3)->count(); //好评数
			if($comment_num > 0){
				$haopercent = round($haonum/$comment_num*100,2);
			}else{
				$haopercent = 100;
			}
			$business['comment_num'] = $comment_num;
			$business['comment_haopercent'] = $haopercent;
		}

		$cid = input('param.cid');
		if(!$cid) $cid = 0;
		
		if($bid > 0){
			$clist = Db::name('shop_category2')->where('aid',aid)->where('bid',$bid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
		}else{
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
			$clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$cid)->where('status',1)->order('sort desc,id')->select()->toArray();
		}
		foreach($clist as $k=>$v){
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',$bid];
			//$where[] = ['status','=',1];
			$where[] = ['ischecked','=',1];
			$where[] = ['freighttype','<',3];
			if(isdouyin == 1){
				$where[] = ['douyin_product_id','<>',''];
			}else{
				$where[] = ['douyin_product_id','=',''];
			}
			$nowtime = time();
			$nowhm = date('H:i');
			$where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

			//子分类 
			if($bid > 0){
				$childcid = db('shop_category2')->where(['aid'=>aid,'pid'=>$v['id']])->column('id');
				if($childcid){
					$child2cid = db('shop_category2')->where(['aid'=>aid,'pid'=>['in',$childcid]])->column('id');
					$cCate = array_merge($childcid, $child2cid, [$v['id']]);
					if($cCate){
						$whereCid = [];
						foreach($cCate as $c2){
							$whereCid[] = "find_in_set({$c2},cid2)";
						}
						$where[] = Db::raw(implode(' or ',$whereCid));
					}
				} else {
					$where[] = Db::raw("find_in_set(".$v['id'].",cid2)");
				}
			}else{
				$childcid = db('shop_category')->where($tjwhere)->where(['aid'=>aid,'pid'=>$v['id']])->column('id');
				if($childcid){
					$child2cid = db('shop_category')->where($tjwhere)->where(['aid'=>aid,'pid'=>['in',$childcid]])->column('id');
					$cCate = array_merge($childcid, $child2cid, [$v['id']]);
					if($cCate){
						$whereCid = [];
						foreach($cCate as $c2){
							$whereCid[] = "find_in_set({$c2},cid)";
						}
						$where[] = Db::raw(implode(' or ',$whereCid));
					}
				} else {
					$where[] = Db::raw("find_in_set(".$v['id'].",cid)");
				}
			
			}
			if(getcustom('product_bind_mendian')){
				if($mendian_id>0){
					$where[] = Db::raw("find_in_set({$mendian_id},`bind_mendian_ids`) OR find_in_set('-1',`bind_mendian_ids`) OR ISNULL(bind_mendian_ids)");
				}
			}
			//是否可见
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
            $shop_product_field = "pic,id,name,stock,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,guigedata,price_type,product_type,fwid,weight";
			if(getcustom('product_service_fee')){
                $shop_product_field +=',service_fee,service_fee_switch,service_fee_data';
            }
            $prolist = Db::name('shop_product')->field($shop_product_field)->where($where)->order('sort desc,id desc')->select()->toArray();
			if(!$prolist) $prolist = [];
			$prolist = $this->formatprolist($prolist);
			if(!$prolist){
				unset($clist[$k]);
			}else{
				foreach($prolist as $k2=>$v2){
					if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        $priceshows = [];
                        $price_show = 0;
                        $price_show_text = '';
                    }
					$gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$v2['id'])->select()->toArray();
					if(getcustom('shop_showgivescore')){
						$showgivescore1 = -1;//最小赠送积分
						$showgivescore2 = -1;//最大赠送积分
					}
					foreach($gglist as $gk=>$gv){
						if(getcustom('member_level_price_show')){
                            //获取第一个规格的会员等级价格
                            if($gk == 0 && $v2['lvprice'] == 1 && $gv['lvprice_data']){
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
                                            $prolist[$k2]['sell_putongprice'] = $lv;
                                        }
                                        $lk ++ ;
                                    }
                                    unset($lid);unset($lv);
                                }
                            }
                        }
						if(getcustom('shop_showgivescore')){
	                    	$sgres = \app\common\System::showgivescore($shopset['showgivescore'],$showgivescore1,$showgivescore2,$gv['givescore']);
	                    	if($sgres){
	                    		$showgivescore1 = $sgres['showgivescore1'];//最小赠送积分
								$showgivescore2 = $sgres['showgivescore2'];//最大赠送积分
	                    	}
	                    }
                    }
                    unset($gk);unset($gv);
                    if(getcustom('shop_showgivescore')){
	                    $prolist[$k2]['showgivescore'] = \app\common\System::showgivescore2($shopset['showgivescore'],$showgivescore1,$showgivescore2);
	                }

                    if(getcustom('member_level_price_show')){
                        //获取第一个规格的会员等级价格
                        $prolist[$k2]['priceshows'] = $priceshows?$priceshows:'';
                        $prolist[$k2]['price_show'] = $price_show;
                        $prolist[$k2]['price_show_text'] = $price_show_text;
                    }

					$prolist[$k2]['gglist'] = $gglist;
					$prolist[$k2]['ggcount'] = count($gglist);
					if($v2['limit_start']==0) $v2['limit_start'] = 1;
					if($v2['limit_per']==0) $v2['limit_per'] = 999999;
					if(getcustom('product_wholesale') && $v2['product_type'] == 4){
						$guigedata = json_decode($v2['guigedata'],true);
						$prolist[$k]['gg_num'] =  count($guigedata);
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
				$clist[$k]['prolist'] = $prolist;
			}
		}
		$clist = array_values($clist);

		$list = Db::name('shop_cart')->where('aid',aid)->where('bid',$bid)->where('mid',mid)->order('createtime desc')->select()->toArray();
		$total = 0;
		$totalprice = 0;
		foreach($list as $k=>$v){
			$product = Db::name('shop_product')->field('pic,bid,id,name,stock,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,guigedata,fwid,weight')->where('id',$v['proid'])->find();
			if(!$product){
				unset($list[$k]);
				Db::name('shop_cart')->where('id',$v['id'])->delete();
				continue;
			}
			$product = $this->formatproduct($product);
			$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$v['ggid'])->find();
			if(!$guige){
				unset($list[$k]);
				Db::name('shop_cart')->where('id',$v['id'])->delete();
				continue;
			}
            $guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);

            $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
            if(getcustom('shop_showgivescore')){
				$showgivescore1 = -1;//最小赠送积分
				$showgivescore2 = -1;//最大赠送积分
			}
            foreach($gglist as $gk=>$gv){
            	if(getcustom('shop_showgivescore')){
                	$sgres = \app\common\System::showgivescore($shopset['showgivescore'],$showgivescore1,$showgivescore2,$gv['givescore']);
                	if($sgres){
                		$showgivescore1 = $sgres['showgivescore1'];//最小赠送积分
						$showgivescore2 = $sgres['showgivescore2'];//最大赠送积分
                	}
                }
            }
            unset($gk);unset($gv);
            if(getcustom('shop_showgivescore')){
                $product['showgivescore'] = \app\common\System::showgivescore2($shopset['showgivescore'],$showgivescore1,$showgivescore2);
            }
			$list[$k]['product'] = $product;
			$list[$k]['guige'] = $guige;
			$total += $v['num'];
			$totalprice += $guige['sell_price'] * $v['num'];
		}
		$totalprice = number_format($totalprice,2,'.','');
		$cartList = ['list'=>$list,'total'=>$total,'totalprice'=>$totalprice,'leftprice'=>0];
		$numtotal = [];
		foreach($clist as $i=>$v){
			foreach($v['prolist'] as $j=>$pro){
				$numtotal[$pro['id']] = 0;
			}
		}
		foreach($cartList['list'] as $i=>$v){
			$numtotal[$v['proid']] += $v['num'];
		}

		if(getcustom('plug_businessqr')){
			$paylist = Db::name('plug_businessqr_pay')->where('aid',aid)->where('bid',$bid)->where('status',1)->order('sort desc,id desc')->select()->toArray();
            $menu_list = cache('business_menu_list_'.aid);
            if(empty($menu_list)) {
                $menu_list = [
                    [
                        'name' => '会员支付',
                        'alias' => '',
                        'sort' => 1,
                        'st' => 3,
                    ],
                    [
                        'name' => '商家信息',
                        'alias' => '',
                        'sort' => 2,
                        'st' => 1,
                    ],
                    [
                        'name' => '商品',
                        'alias' => '',
                        'sort' => 3,
                        'st' => 0,
                    ],
                ];
            }
		}else{
			$paylist = [];
		}
        if(getcustom('product_show_fwlist')){
            $custom['product_show_fwlist'] = true;
        }
        if(getcustom('product_show_sellpoint')){
            $custom['product_show_sellpoint'] = true;
        }
        if(getcustom('product_show_sellpoint')){
            $custom['product_weight'] = true;
        }
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['data'] = $clist;
		$rdata['paylist'] = $paylist;
		$rdata['cartList'] = $cartList;
		$rdata['numtotal'] = $numtotal;
		$rdata['business'] = $business;
        $rdata['menuList'] = $menu_list ? $menu_list : [];
		$rdata['bid'] = $bid;
		$rdata['custom'] = $custom;
		return $this->json($rdata);
	}
	//获取促销信息
	public function getcuxiaoinfo(){
		$id = input('post.id');
        $multi_promotion = 0;


        if($multi_promotion && is_array($id)) {
            $list = Db::name('cuxiao')->whereIn('id',$id)->where('aid',aid)->select();
            if(!$list){
                return $this->json(['status'=>0,'msg'=>'获取失败']);
            }

            if($list) {
                foreach ($list as $key => $info) {
                    $proinfo[$key] = false;
                    $gginfo[$key] = false;
                    if(($info['type'] == 2 || $info['type'] == 3) && $info['proid']){
                        $proinfo[$key] = Db::name('shop_product')->field('id,name,pic,sell_price')->where('aid',aid)->where('id',$info['proid'])->find();
                        $gginfo[$key] = Db::name('shop_guige')->where('aid',aid)->where('id',$info['ggid'])->find();
                    }
                }
            }

            return $this->json(['status'=>1,'info'=>$list,'product'=>$proinfo,'guige'=>$gginfo]);
        } else {
            $info = Db::name('cuxiao')->where('id',$id)->where('aid',aid)->find();
            if(!$info){
                return $this->json(['status'=>0,'msg'=>'获取失败']);
            }

            $proinfo = false;
            $gginfo = false;
            if(($info['type'] == 2 || $info['type'] == 3) && $info['proid']){
                $proinfo = Db::name('shop_product')->field('id,name,pic,sell_price')->where('aid',aid)->where('id',$info['proid'])->find();
                $gginfo = Db::name('shop_guige')->where('aid',aid)->where('id',$info['ggid'])->find();
            }
            return $this->json(['status'=>1,'info'=>$info,'product'=>$proinfo,'guige'=>$gginfo]);
        }

	}
	//订单提交页
	public function buy(){
		$this->checklogin();
		$prodata = explode('-',input('param.prodata'));
        $mendian_id = input('param.mendian_id/d',0);
		$multi_promotion = 0;
        if(getcustom('multi_promotion')){
            $multi_promotion = 1;
        }
        if(getcustom('shop_product_jialiao')) {
            $jldata = input('param.jldata');
        }
		//未认证不可下单
		if(getcustom('show_price_uncheck')){
			$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
			$mid = mid;
			if(!$mid && $shopset['is_show_price_uncheck'] == 0){
				$msg = $shopset['show_price_uncheck_txt'];
				return $this->json(['status'=>0,'msg'=>$msg]);
			}			
		}
        if(getcustom('shop_product_certificate') && getcustom('certificate_poster')){
            $huomacode = input('param.huomacode');
            if($huomacode){
                $certificate_record = Db::name('certificate_poster_record')->where('aid',aid)->where('huomacode',$huomacode)->find();
                if($certificate_record)return $this->json(['status'=>0,'msg'=>'','url' => '/pagesExt/certificate_poster/detail?posterid='.$certificate_record['id'],'relaunch' =>1 ]);
            }
        }
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
		$adminset = Db::name('admin_set')->where('aid',aid)->find();
		$userinfo = [];
		$userinfo['discount'] = $userlevel['discount'];
		$userinfo['score'] = $this->member['score'];
		$userinfo['score2money'] = $adminset['score2money'];
        $userinfo['scoredk_money'] = round($userinfo['score'] * $userinfo['score2money'],2);
		$userinfo['scoredkmaxpercent'] = $adminset['scoredkmaxpercent'];
		if(getcustom('sysset_scoredkmaxpercent_memberset')){
            //处理会员单独设置积分最大抵扣比例
            $userinfo['scoredkmaxpercent'] = $adminset['scoredkmaxpercent'] = $this->sysset['scoredkmaxpercent'] = \app\custom\ScoredkmaxpercentMemberset::dealmemberscoredk(aid,$this->member,$userinfo['scoredkmaxpercent']);
        }
		$userinfo['scoremaxtype'] = 0; //0最大百分比 1最大抵扣金额
		$userinfo['realname'] = $this->member['realname'];
		$userinfo['tel'] = $this->member['tel'];
		$userinfo['money'] = $this->member['money'];
		if(getcustom('product_givetongzheng')){
            $userinfo['tongzheng'] = $this->member['tongzheng'];
            $userinfo['tongzheng2money'] = $adminset['tongzheng2money'];
            $userinfo['tongzhengdk_money'] = round($userinfo['tongzheng'] * $userinfo['tongzheng2money'],2);
            $userinfo['tongzhengdkmaxpercent'] = $adminset['tongzhengdkmaxpercent'];
            $userinfo['tongzhengmaxtype'] = 0; //0最大百分比 1最大抵扣金额
        }else{
            $userinfo['tongzheng2money'] = 0;
        }

        if(getcustom('member_dedamount')){
            $userinfo['dedamount']     = $this->member['dedamount'];//会员抵扣金额
            $userinfo['dedamount2']    = $this->member['dedamount'];//会员变动抵扣金额
            $userinfo['dedamount_dkpercent'] = $adminset['dedamount_dkpercent']??0;//抵扣比例
            $userinfo['dedamount_dkmoney']   = 0;//实际可抵扣金额
        }

        if(getcustom('member_shopscore')){
            $userinfo['shopscore']         = 0;//会员产品积分
            $userinfo['shopscoredk_money'] = 0;//会员产品积分可兑换数额

            $userinfo['shopscore2money']      = 0;//系统设置每产品积分兑换比例数量
            $userinfo['shopscoredkmaxpercent']= 0;//系统设置产品积分最大兑换比例

            $membershopscoreauth = false;
            if($adminset['shopscorestatus'] == 1){
                //查询权限组
                $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                //如果开启了产品积分权限
                if($user['auth_type'] == 1){
                    $membershopscoreauth = true;
                }else{
                    $admin_auth = json_decode($user['auth_data'],true);
                    if(in_array('MemberShopscoreAuth,MemberShopscoreAuth',$admin_auth)){
                        $membershopscoreauth = true;
                    }
                }

                //如果商城商品积分开启了，则赋值
                if($membershopscoreauth){
                    $userinfo['shopscore']         = $this->member['shopscore'];
                    $userinfo['shopscoredk_money'] = floor($userinfo['shopscore'] * $adminset['shopscore2money'] *100)/100;

                    $userinfo['shopscore2money']      = $adminset['shopscore2money'];
                    $userinfo['shopscoredkmaxpercent']= $adminset['shopscoredkmaxpercent'];
                }
            }
            $userinfo['shopscoredkmaxmoney'] = 0;//商城商品积分最大抵扣金额
            $userinfo['shopscoremaxtype']    = 0; //0最大百分比 1最大抵扣金额
        }

		$scoredkmaxmoney = 0;//积分最大抵扣金额
		$allbuydata = [];
		$autofahuo = 0;
        $cat_buynum=[];
        $tongzhengdkmaxmoney = 0;//通证最大抵扣金额
        //满减活动
        $mjset = Db::name('manjian_set')->where('aid',aid)->find();
        if($mjset && $mjset['status']==1){
            $mjdata = json_decode($mjset['mjdata'],true);
            //指定分类
            if($mjset['fwtype']==1){
                //指定分类或商品分类不存在
                if(empty($mjset['categoryids'])){
                    $mjdata = array();
                }
            //指定商品
            }else if($mjset['fwtype']==2){
                if(empty($mjset['productids'])){
                    $mjdata = array();
                }
            }
            if(getcustom('plug_tengrui')) {
                if($mjdata){
                    $tr_check = new \app\common\TengRuiCheck();
                    //判断是否是否符合会员认证、会员关系、一户
                    $check_manjian = $tr_check->check_manjian($this->member,$mjset);
                    if($check_manjian && $check_manjian['status'] == 0){
                        $mjdata = array();
                    }
                }
            }
        }else{
            $mjdata = array();
        }
        $glassProductNum = 0;
        $glassCustomProductNum = 0;
		$business_selfscore = 0;
		if(getcustom('business_selfscore')){
            //可设置商户开启独立积分账户
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			if($bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
				$business_selfscore = 1;
			}
			$scoredkdataArr = [];
		}
		$productMoneyPay = [];//不同支付方式，不允许一起结算
        $productLimit = [];
        $protypes=[];//存在的商品类型
		$product_fenqi = [];
		$ggid_arr = [];
        $show_service_fee = 0;
		if(getcustom('ciruikang_fenxiao')){
			//是否开启了商城商品需上级购买足量
			$open_product_parentbuy = Db::name('admin_set')->where('aid', aid)->value('open_product_parentbuy');
		}

    	$contact_require = 0;
		$show_product_xieyi = 0;//商品协议
        $xieyi_ids = [];
		$product_xieyi = [];
		if(getcustom('supply_zhenxin')){
			$havezxpro   = false;//是否有甄新汇选商品
			$needusercard= false;//是否需要填写身份证号码
			$zxallprice  = 0;//甄新汇选商品总成本价格（包含服务费）
			$zxblance    = -1;//甄新汇选账号余额
		}
		if(getcustom('supply_yongsheng')){
			$haveyspro = false;//是否有永盛商品
		}
		if(getcustom('member_goldmoney_silvermoney')){
			$ShopSendSilvermoney = true;//赠送银值权限
            $ShopSendGoldmoney   = true;//赠送金值权限
            //平台权限
            $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user['auth_type'] !=1 ){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if(!in_array('ShopSendSilvermoney,ShopSendSilvermoney',$admin_auth)){
                    $ShopSendSilvermoney = false;
                }
                if(!in_array('ShopSendGoldmoney,ShopSendGoldmoney',$admin_auth)){
                    $ShopSendGoldmoney   = false;
                }
            }
            $allsilvermoneydec = 0;//银值抵扣数额
            $allgoldmoneydec   = 0;//金值抵扣数额
        }
        if(getcustom('shop_product_commission_memberset')){
			$ShopProductCommissionMemberSet = true;//填写分销员ID权限
            //平台权限
            if(!$admin_user){
            	$admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            }
            if($admin_user['auth_type'] !=1 ){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if(!in_array('ShopProductCommissionMemberSet,ShopProductCommissionMemberSet',$admin_auth)){
                    $ShopProductCommissionMemberSet = false;
                }
            }
        }

        if(getcustom('money_dec')){
            $money_dec_type = 0;//抵扣类型 0按系统设置最大百分比 1：存在商品单独设置最大金额
        }
        if(getcustom('product_chinaums_subsidy')){
            //判断是否符合补贴条件，不符合询问提示；
            $subsidyVerify = 0;
            $subsidyTips = '';
        }
		foreach($prodata as $key=>$gwc){
			list($proid,$ggid,$num) = explode(',',$gwc);
            if(getcustom('to86yk')){
                $field = "id,aid,bid,cid,cid2,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,freighttype,freightdata,perlimit,gettj,gettjtip,gettjurl,scoredkmaxset,scoredkmaxval,status,start_time,end_time,start_hours,end_hours,balance,no_discount,limit_start,perlimitdan,to86yk_tid,product_type,guigedata,contact_require";
            }else{
                $field = "id,aid,bid,cid,cid2,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,freighttype,freightdata,perlimit,gettj,gettjtip,gettjurl,scoredkmaxset,scoredkmaxval,status,start_time,end_time,start_hours,end_hours,balance,no_discount,limit_start,perlimitdan,product_type,guigedata,contact_require";
            }
            if(getcustom('plug_tengrui')) {
                $field .= ',house_status,group_status,group_ids,is_rzh,relation_type';
            }
            if(getcustom('product_glass') || getcustom('product_glass_custom')) {
                list($proid,$ggid,$num,$glass_record_id) = explode(',',$gwc);
            }
            if(getcustom('shop_yuding')) {
                list($proid,$ggid,$num,$glass_record_id) = explode(',',$gwc);
                $field .= ',stock,yuding_stock';
            }
            if(getcustom('product_moneypay')) {
                $field .= ',product_moneypay';
            }
            if(getcustom('product_bind_mendian')){
                $field .= ',bind_mendian_ids';
            }
		    if(getcustom('weight_template')){
                $field .= ',weightdata,weighttype';
            }
            if(getcustom('discount_code_zhongchuang')){
                $field .= ',price_discount_code_zc';
            }
            if(getcustom('product_wholesale')){
                $field .= ',jieti_discount_data,jieti_discount_type';
            }
            if(getcustom('member_realname_verify')){
                $field .= ',realname_buy_status,limittj';
            }
            if(getcustom('shop_product_fenqi_pay')){
                $field .= ',fenqi_data,fenqigive_couponid,fenqigive_fx_couponid';
            }
            if(getcustom('product_supply_chain')){
                $field .= ',trade_type';
            }
            if(getcustom('product_xieyi')){
                $field .= ',xieyi_id';
            }
            if(getcustom('product_service_fee')){
                //服务费
                $field .= ',service_fee,service_fee_data,service_fee_switch';
            }
            if(getcustom('shop_label')){
                //服务费
                $field .= ',labelid';
            }
            if(getcustom('product_givetongzheng')){
                $field .= ',tongzhengdkmaxset,tongzhengdkmaxval';
            }
            if(getcustom('supply_zhenxin')){
                //服务费
                $field .= ',sproid,issource,source';
            }
            if(getcustom('supply_yongsheng')){
                //服务费
                $field .= ',sproid,issource,source';
            }
            if(getcustom('member_goldmoney_silvermoney')){
            	$field .= ',silvermoneydec_ratio,goldmoneydec_ratio';
            }
            if(getcustom('money_dec_product')){
            	$field .= ',moneydecset,moneydecval';
            }
            if(getcustom('product_chinaums_subsidy')){
                $field.= ',is_subsidy';
            }
            if(getcustom('product_glass_custom')){
                $field.= ',glass_type';
            }
            if(getcustom('freeze_money')){
                $field.= ',freezemoney_price,freezemoney_price_data';
            }
            if(getcustom('shop_product_form')){
                $field.= ',form_id';
            }
            if(getcustom('shop_product_commission_memberset')){
                $field.= ',commission_memberset';//填写分销员ID
            }
            if(getcustom('deposit')){
                $field.= ',deposit_status,deposit_id';//押金开关
            }
            if(getcustom('product_per_limit_buy')){
                $field.= ',perlimitday';
            }
			$product = Db::name('shop_product')->field($field)->where('aid',aid)->where('ischecked',1)->where('id',$proid)->find();

			if(!$product){
				Db::name('shop_cart')->where('aid',aid)->where('proid',$proid)->delete();
				return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
			}

			if(getcustom('product_xieyi') && $product['xieyi_id']>0){
                $show_product_xieyi = 1;
                if(!in_array($product['xieyi_id'],$xieyi_ids)){
                    $product_xieyi[] = Db::name('product_xieyi')->where('id',$product['xieyi_id'])->field('name,content')->find();
                }
                $xieyi_ids[] = $product['xieyi_id'];
            }
            if(($product['freighttype'] == 3 || $product['freighttype'] == 4) && $product['contact_require'] == 1){
                $contact_require = 1;
            }

			if(getcustom('shop_product_fenqi_pay') && $product['product_type'] == 5){
                $product_fenqi = $product;
            }
			//商品类型数组
			if(!in_array($product['product_type'],$protypes)){
				array_push($protypes,$product['product_type']);
			}
			//判断商家是否还存在
            if($product['bid']>0){
                $business = Db::name('business')->where('id',$product['bid'])->find();
                if(!$business){
                    return $this->json(['status'=>0,'msg'=>'产品['.$product['name'].']异常，请重新下单']);
                }
            }
			if(isset($product['product_moneypay'])){
			    if(!in_array($product['product_moneypay'],$productMoneyPay)){
                    $productMoneyPay[] = $product['product_moneypay'];
                }
            }
			if($product['status']==0){
				return $this->json(['status'=>0,'msg'=>'商品未上架']);
			}
			if($product['status']==2 && (strtotime($product['start_time']) > time() || strtotime($product['end_time']) < time())){
				return $this->json(['status'=>0,'msg'=>'商品未上架']);
			}
			if($product['status']==3){
				$start_time = strtotime(date('Y-m-d '.$product['start_hours']));
				$end_time = strtotime(date('Y-m-d '.$product['end_hours']));
				if(($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time >= $end_time && ($start_time > time() && $end_time < time()))){
					return $this->json(['status'=>0,'msg'=>'商品未上架']);
				}
			}
            if(getcustom('member_realname_verify')){
                if($product['realname_buy_status'] == 1 && $this->member['realname_status'] != 1){
                    return $this->json(['status'=>-4,'msg'=>'未实名不可购买['.$product['name'].']','url'=>'/pagesExt/my/setrealname']);
                }
            }

            if(getcustom('shop_label')){
                //是否有商品标签限制购买
                if($product['labelid']){
                    $labelids = Db::name('shop_label')->where('id','in',$product['labelid'])->where('limitbuy',1)->where('aid',aid)->order('sort desc,id desc')->column('id');
                    if($labelids){
                        if(!$this->member['labelid']){
                            return $this->json(['status'=>0,'msg'=>'不符合购买商品'.$product['name'].'标签的条件']);
                        }
                        $haslabel = false;//是否有符合的标签
                        $mlabelids = explode(',',$this->member['labelid']);
                        foreach($mlabelids as $mv){
                            if(in_array($mv,$labelids)){
                                $haslabel = true;
                            }
                        }
                        if(!$haslabel){
                            return $this->json(['status'=>0,'msg'=>'不符合购买商品'.$product['name'].'标签的条件']);
                        }
                        unset($mv);
                    }
                }
            }
            if(getcustom('shop_product_commission_memberset')){
                $product['procommissionmid'] = 0;//填写分销员ID
            }

			if(getcustom('product_glass') || getcustom('product_glass_custom')){
			    if($product['product_type']==1){
                    if($product['glass_type'] == 1){
                    	$glassProductNum++;
                        if($glass_record_id){
                            $glassrecord = Db::name('glass_record')->where('aid',aid)->where('mid',$this->mid)->where('id',$glass_record_id)->find();
                            if($glassrecord){
                                $product['has_glassrecord'] = 1;
                                $product['glassrecord'] = $glassrecord;
                            }
                        }
                    }
                    if($product['glass_type'] == 2){
                        $glassCustomProductNum++;
                        if($glass_record_id){
                            $glassCustom = Db::name('glass_custom_record')->where('aid',aid)->where('mid',$this->mid)->where('id',$glass_record_id)->find();
                            if($glassCustom){
                                $product['has_glasscustom'] = 1;
                                $product['glass_custom'] = $glassCustom;
                            }
                        }
                    }
                }
            }

			if($product['freighttype'] == 3 || $product['freighttype'] == 4) $autofahuo = $product['freighttype'];
			$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$ggid)->find();
			if(!$guige){
				Db::name('shop_cart')->where('aid',aid)->where('ggid',$ggid)->delete();
				return $this->json(['status'=>0,'msg'=>'产品该规格不存在或已下架']);
			}
			$ggid_arr[] = $ggid;

            if(getcustom('shop_yuding')){
                if($product['stock'] <=0  && $product['yuding_stock'] > 0){
                    Db::name('shop_guige')->where('aid',aid)->where('id',$guige['id'])->update(['stock'=>Db::raw("stock+$num")]);
                   // Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->update(['stock'=>Db::raw("stock+$num")]);
                    $guige['stock']  = $num;
                }
            }
            $isWdtStock = 0;
            if(getcustom('erp_wangdiantong') && $guige['wdt_status']==1){
                $isWdtStock = 1;
                $c = new \app\custom\Wdt(aid,$product['bid']);
                $stock = $c->stockQueryBySpec($guige['barcode'],$guige['proid']);
                if($stock < $num){
                    return $this->json(['status'=>0,'msg'=>$product['name'] . $guige['name'].'库存不足']);
                }
            }
			if(!$isWdtStock && $guige['stock'] < $num){
                if(getcustom('shop_stock_warning_notice')){
                    $this->tmpl_stockwarning($product,$guige,$guige['stock'],$product['id']);                        				
                }
				return $this->json(['status'=>0,'msg'=> $product['name'] . $guige['name'].'库存不足']);
			}
			$gettj = explode(',',$product['gettj']);
			if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj) && (!in_array('0',$gettj) || $this->member['subscribe']!=1)){ //不是所有人
				if(!$product['gettjtip']) $product['gettjtip'] = '没有权限购买该商品';
				return $this->json(['status'=>0,'msg'=>$product['gettjtip'],'url'=>$product['gettjurl']]);
			}

            if($guige['limit_start'] > 0 && $num < $guige['limit_start']){
                return $this->json(['status'=>0,'msg'=>'['.$product['name'].']['.$guige['name'].'] '.$guige['limit_start'].'件起售']);
            }
			$guige['ggpic_wholesale'] = '';
			$limit_start_state = 1;
			if(getcustom('product_wholesale') && $product['product_type'] == 4){
				$jieti_discount_type = $product['jieti_discount_type'];
				if($jieti_discount_type == 0){
					$limit_start_state = 0;
				}
				$guigedata = json_decode($product['guigedata'],true);
				$gg_name_arr = explode(',',$guige['name']);
				foreach($guigedata as $pk=>$pg){
					foreach($pg['items'] as $pgt){
						if(isset($pgt['ggpic_wholesale'])){							
							if(in_array($pgt['title'],$gg_name_arr)){
								$guige['ggpic_wholesale'] = $pgt['ggpic_wholesale'];
							}
						}
						
					}
				}
			}
			$guige['gg_group_title'] = '';
			$guigedata = json_decode($product['guigedata'],true);
			foreach($guigedata as $pk=>$pg){	
					$guige['gg_group_title'] .= $pg['title'].',';
			}
			$guige['gg_group_title'] = trim($guige['gg_group_title'],',');
			if($limit_start_state==1 && $product['limit_start'] > 0 && $num < $product['limit_start']){
				return $this->json(['status'=>0,'msg'=>'['.$product['name'].'] '.$product['limit_start'].'件起售']);
			}
			
            
            if($product['perlimitdan'] > 0 && $num > $product['perlimitdan']){
                return $this->json(['status'=>0,'msg'=>'['.$product['name'].'] 每单限购'.$product['perlimitdan'].'件']);
            }

			if($product['perlimit'] > 0){
                if(getcustom('member_realname_verify')){
                    $limittj = explode(',',$product['limittj']);
                    $midslimit = [];
                    if(in_array(0,$limittj)){
                        $midslimit = [mid];
                    }
                    if(in_array(1,$limittj)){
                        if($this->member['realname_status'] != 1){
                            return $this->json(['status'=>-4,'msg'=>'未实名不可购买['.$product['name'].']','url'=>'/pagesExt/my/setrealname']);
                        }
                        $memberids = Db::name('member')->where('aid',aid)->where('usercard',$this->member['usercard'])->column('id');
                        if($memberids) $midslimit = array_merge($midslimit,$memberids);
                    }
                    if(in_array(2,$limittj)){
                        if((empty($this->member['mpopenid']) && platform == 'mp') || (empty($this->member['wxopenid']) && platform == 'wx') || (empty($this->member['mpopenid']) && empty($this->member['wxopenid']))){
                            return $this->json(['status'=>-4,'msg'=>'未绑定微信不可购买['.$product['name'].']','url'=>'/pages/my/usercenter']);
                        }
                        if(!empty($this->member['mpopenid']) && platform == 'mp'){
                            $memberids = Db::name('member')->where('aid',aid)->where('mpopenid',$this->member['mpopenid'])->column('id');
                            if($memberids) $midslimit = array_merge($midslimit,$memberids);
                        }
                        if(!empty($this->member['wxopenid']) && platform == 'wx'){
                            $memberids = Db::name('member')->where('aid',aid)->where('wxopenid',$this->member['wxopenid'])->column('id');
                            if($memberids) $midslimit = array_merge($midslimit,$memberids);
                        }
                        if(!empty($this->member['unionid'])){
                            $memberids = Db::name('member')->where('aid',aid)->where('unionid',$this->member['unionid'])->column('id');
                            if($memberids) $midslimit = array_merge($midslimit,$memberids);
                        }

                    }
                    if(in_array(3,$limittj)){
                        if(empty($this->member['tel'])){
                            return $this->json(['status'=>-4,'msg'=>'未绑定手机不可购买['.$product['name'].']','url'=>'/pages/my/usercenter']);
                        }
                        $memberids = Db::name('member')->where('aid',aid)->where('tel',$this->member['tel'])->column('id');
                        if($memberids) $midslimit = array_merge($midslimit,$memberids);
                    }
                    $midslimit = array_unique($midslimit);
                    $buynum = $num + Db::name('shop_order_goods')->where('aid',aid)->whereIn('mid',$midslimit)->where('proid',$product['id'])->where('status','in','0,1,2,3,4')->sum('num');
                    $refund_num = Db::name('shop_refund_order_goods')->alias('srog')
                    ->join('shop_refund_order so','so.id  = srog.refund_orderid')->where('srog.aid',aid)->where('srog.mid',mid)->where('srog.proid',$product['id'])->where('so.refund_type','return')->where('so.refund_status','in','1,2,4')->sum('refund_num');
                    $buynum = $buynum - $refund_num;
                    if($buynum > $product['perlimit']){
                        return $this->json(['status'=>0,'msg'=>'['.$product['name'].'] 每人限购'.$product['perlimit'].'件']);
                    }else{
                        if(isset($productLimit[$product['id']])){
                            $productLimit[$product['id']]['buyed'] += $buynum - $num;
                            $productLimit[$product['id']]['buy'] += $num;
                        }else{
                            $productLimit[$product['id']]['buyed'] = $buynum - $num;
                            $productLimit[$product['id']]['buy'] = $num;
                            $productLimit[$product['id']]['perlimit'] = $product['perlimit'];
                            $productLimit[$product['id']]['name'] = $product['name'];
                        }
                    }
                }else{
                    $refundOrderWhere = [];
                    $orderGoodsWhere = [];
                    $perLimitMsg = '['.$product['name'].'] 每人限购'.$product['perlimit'].'件';
                    if(getcustom('product_per_limit_buy')){
                        //0天代表不限制
                        if($product['perlimitday'] > 0){
                            //0 - 23:59:59
                            $limitStartTime = strtotime(date('Y-m-d', strtotime("-" . ($product['perlimitday'] - 1) . " days")));
                            $limitEndTime = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
                            $refundOrderWhere[] = ['srog.createtime', 'between', [$limitStartTime, $limitEndTime]];
                            $orderGoodsWhere[] = ['createtime','between',[$limitStartTime, $limitEndTime]];
                            $perLimitMsg = '['.$product['name'].'] 每人每'.$product['perlimitday'].'天限购'.$product['perlimit'].'件';
                        }
                    }
                    $buynum  = $num + Db::name('shop_order_goods')->where('aid', aid)->where('mid', mid)->where('proid', $product['id'])->where('status', 'in', '0,1,2,3')->where($orderGoodsWhere)->sum('num');
                    $refund_num = Db::name('shop_refund_order_goods')->alias('srog')
                    ->join('shop_refund_order so','so.id  = srog.refund_orderid')->where('srog.aid',aid)->where('srog.mid',mid)->where('srog.proid',$product['id'])->where('so.refund_type','return')->where('so.refund_status','in','2')->where($refundOrderWhere)->sum('refund_num');
                    $buynum = $buynum - $refund_num;
                    if($buynum > $product['perlimit']){
                        return $this->json(['status'=>0,'msg'=>$perLimitMsg]);
                    }else{
                        if(isset($productLimit[$product['id']])){
                            $productLimit[$product['id']]['buyed'] += $buynum - $num;
                            $productLimit[$product['id']]['buy'] += $num;
                        }else{
                            $productLimit[$product['id']]['buyed'] = $buynum - $num;
                            $productLimit[$product['id']]['buy'] = $num;
                            $productLimit[$product['id']]['perlimit'] = $product['perlimit'];
                            $productLimit[$product['id']]['name'] = $product['name'];
                        }
                    }
                }
			}

            if(getcustom('shop_categroy_limit')){
                //判断分类是否限购
                if($product['cid']){
                    $category = Db::name('shop_category')->where('id','in',$product['cid'])->where('limit_num', '>',0)->find();
                    if($category) {
                        $product_cid_arr = explode(',',$product['cid']);
                        foreach ($product_cid_arr as $product_cid_arr_v){
                            if($cat_buynum[$product_cid_arr_v])
                                $cat_buynum[$product_cid_arr_v] += $num;
                            else
                                $cat_buynum[$product_cid_arr_v] = $num;
                        }

                        if($category['limit_day'] > 0){
                            $limitStart = strtotime('-' . ($category['limit_day'] - 1) . ' days 00:00:00');
                            $limitEnd   = strtotime('today 23:59:59');
                            $buynum = $cat_buynum[$category['id']] + Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->whereFindInSet('cid',$category['id'])
                                    ->where('createtime', 'between', [$limitStart, $limitEnd])->where('status','in','0,1,2,3')->sum('num');
                            if($buynum > $category['limit_num']){
                                return $this->json(['status'=>0,'msg'=>'[分类:'.$category['name'].'] 每人每'.$category['limit_day'].'天限购'.$category['limit_num'].'件']);
                            }
                        }else {
                            $buynum = $cat_buynum[$category['id']] + Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->whereFindInSet('cid',$product['cid'])
                                    ->where('status','in','0,1,2,3')->sum('num');
                            if($buynum > $category['limit_num']){
                                return $this->json(['status'=>0,'msg'=>'[分类:'.$category['name'].'] 每人限购'.$category['limit_num'].'件']);
                            }
                        }
                    }
                }
            }

            if(getcustom('plug_tengrui')) {
                //判断是否是否符合会员认证、会员关系、一户
                $tr_check = new \app\common\TengRuiCheck();
                $check_product = $tr_check->check_product($this->member,$product);
                if($check_product && $check_product['status'] == 0){
                    return $this->json(['status'=>$check_product['status'],'msg'=>$check_product['msg']]);
                }
                $tr_roomId = $check_product['tr_roomId'];
            }

            if(getcustom('ciruikang_fenxiao')){
            	//是否开启商城商品需上级购买足量
				if($open_product_parentbuy == 1){
					if($this->member['pid']>0){
						//验证上级购买数量
						$pnum = Db::name('member_product_stock')
							->where('mid',$this->member['pid'])
							->where('proid',$product['id'])
							->where('ggid',$guige['id'])
							->where('aid',aid)
							->value('num');
						if(!$pnum || empty($pnum) || $pnum<$num){
							return $this->json(['status'=>0,'msg'=>'上级：'.$product['name'].$guige['name'].'商品购买库存不足']);
						}
					}
				}
			}

			$guige = $this->formatguige($guige, $product['bid'],$product['lvprice']);
			if(getcustom('product_wholesale') && $product['product_type'] == 4){
				$jieti_num = $num;
				$jieti_discount_type = $product['jieti_discount_type'];
				if($jieti_discount_type == 0){
					foreach($prodata as $kn=>$v){
						list($proid_n,$ggid_n,$num_n) = explode(',',$v);
						if($proid_n ==$proid && $ggid_n !=$ggid){
							$jieti_num +=$num_n;
						}
					}
					if($product['product_type'] == 4 && $product['jieti_discount_type']==0){
						if($product['limit_start'] > $jieti_num){
							return $this->json(['status'=>0,'msg'=>'['.$product['name'].'] '.$product['limit_start'].'件起售']);
						}						
					}
				}				
				$guige = $this->formatguigewholesale($guige,$product,$jieti_num);
			}
            if(getcustom('shop_product_jialiao')) {
                if($jldata && $jldata[$key]){
                    $jlprice = 0;
                    $jltitle = '';
                    foreach($jldata[$key] as $jlk=>$val){
                        $jlprice += $val['num'] * $val['price'];
                        $jltitle .=$val['title'].'*'.$val['num'].'/';
                    }
                    $guige['sell_price'] = dd_money_format($guige['sell_price'] + $jlprice) ;
                    $guige['name'] = $guige['name'].'('.rtrim($jltitle,'/').')';
                }
            }
            if(getcustom('supply_zhenxin')){
                if($product['issource'] && $product['source'] == 'supply_zhenxin'){
                    $havezxpro = true;//是否有甄新汇选商品
                    //查询甄新汇选商品详情
                    $checkproduct = \app\custom\SupplyZhenxinCustom::checkproduct(aid,$product['bid'],$num,$product,$guige);
                    if(!$checkproduct || $checkproduct['status'] != 1){
                        $msg = $checkproduct && $checkproduct['msg']?$checkproduct['msg']:'['.$product['name'].'规格'.$guige['name'].'] '.'信息错误';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                    $guige = $checkproduct['guige'];
                    if($guige['is_overseas'] == 1){
                        $needusercard = true;//是否跨境完税商品 需要填写身份证
                    }

                    $zxprice = $guige['sprice']*$num+$guige['zxservice_price'];//规格成本价格+规格服务费用
                    $zxallprice += $zxprice;//甄新汇选商品总成本价格

                    //查询账号余额
                    $checkbalance = \app\custom\SupplyZhenxinCustom::checkbalance(aid,$product['bid'],$zxblance,$zxallprice,$product,$guige);
                    if(!$checkbalance || $checkbalance['status'] != 1){
                        $msg = $checkbalance && $checkbalance['msg']?$checkbalance['msg']:'['.$product['name'].'规格'.$guige['name'].'] '.'信息错误';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                    $zxblance = $checkbalance['zxblance'];
                }
            }
            if(getcustom('supply_yongsheng')){
                if($product['issource'] && $product['source'] == 'supply_yongsheng'){
                    $haveyspro = true;//是否有永盛商品
                    //查询永盛商品详情
                    $checkproduct = \app\custom\SupplyYongsheng::checkproduct(aid,$product['bid'],$num,$product,$guige);
                    if(!$checkproduct || $checkproduct['status'] != 1){
                        $msg = $checkproduct && $checkproduct['msg']?$checkproduct['msg']:'['.$product['name'].'规格'.$guige['name'].'] '.'信息错误';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                    $guige = $checkproduct['guige'];
                }
            }

			if($product['scoredkmaxset']==0){
			    $is_sysset_scoredk = true;
                if(getcustom('scoredk_percent_category')){
                    //查找第一个分类
                    $cid_arr = explode(',',$product['cid']);
                    $first_cid = $cid_arr[0];
                    if ($first_cid){
                        $category_set= Db::name('shop_category')->where('aid',$product['aid'])->where('id',$first_cid)->field('scoredkmaxval,scoredkmaxset')->find();
                        if($category_set['scoredkmaxset'] ==1){
                            $category_scoreval =$category_set['scoredkmaxval'];
                            if($category_scoreval > 0 && $category_scoreval<100){
                                $is_sysset_scoredk = false;
                                $scoredkmaxmoney += $category_scoreval * 0.01 * $guige['sell_price'] * $num;
                                $userinfo['scoredkmaxpercent'] = $category_scoreval;
                            }
                        }
                    }
                }
                if($is_sysset_scoredk){
                    if($userinfo['scoredkmaxpercent'] == 0){
                        $userinfo['scoremaxtype'] = 1;
                        $scoredkmaxmoney += 0;
                    }else{
                        if($userinfo['scoredkmaxpercent'] > 0 && $userinfo['scoredkmaxpercent']<=100){
                            $scoredkmaxmoney += $userinfo['scoredkmaxpercent'] * 0.01 * $guige['sell_price'] * $num;
                        }else{
                            $scoredkmaxmoney += $guige['sell_price'] * $num;
                        }
                    }
                }
			}elseif($product['scoredkmaxset']==1){
				$userinfo['scoremaxtype'] = 1;
				$scoredkmaxmoney += $product['scoredkmaxval'] * 0.01 * $guige['sell_price'] * $num;
			}elseif($product['scoredkmaxset']==2){
				$userinfo['scoremaxtype'] = 1;
				$scoredkmaxmoney += $product['scoredkmaxval'] * $num;
			}else{
				$userinfo['scoremaxtype'] = 1;
				$scoredkmaxmoney += 0;
			}
			if(getcustom('business_selfscore') && $business_selfscore == 1){
				if(!$scoredkdataArr[$product['bid']]){
					if($product['bid'] == 0){
						$scoredkdata = [];
						$scoredkdata['usescore'] = 0;
						$scoredkdata['scorebdkyf'] = $this->sysset['scorebdkyf'];
						$scoredkdata['score'] = $userinfo['score'];
						$scoredkdata['score2money'] = $userinfo['score2money'];
						$scoredkdata['scoredk_money'] = $userinfo['scoredk_money'];
						$scoredkdata['scoredkmaxpercent'] = $userinfo['scoredkmaxpercent'];
						$scoredkdata['scoremaxtype'] = $userinfo['scoremaxtype'];
						$scoredkdata['scoredkmaxmoney'] = $userinfo['scoredkmaxmoney'];
						$scoredkdataArr[$product['bid']] = $scoredkdata;
					}else{
						$business = Db::name('business')->where('id',$product['bid'])->find();
						$memberscore = Db::name('business_memberscore')->where('aid',aid)->where('bid',$product['bid'])->where('mid',mid)->value('score');
						$scoredkdata = [];
						$scoredkdata['usescore'] = 0;
						$scoredkdata['scorebdkyf'] = $business['scoreset']==0 ? $this->sysset['score2money'] : $business['scorebdkyf'];
						$scoredkdata['score'] = $memberscore ?? 0;
						$scoredkdata['score2money'] = $business['scoreset']==0 ? $this->sysset['score2money'] : $business['score2money'];
						$scoredkdata['scoredk_money'] = round($scoredkdata['score'] * $scoredkdata['score2money'],2);
						$scoredkdata['scoredkmaxpercent'] = $business['scoreset']==0 ? $this->sysset['scoredkmaxpercent'] : $business['scoredkmaxpercent'];
						$scoredkdata['scoremaxtype'] = 0;
						$scoredkdata['scoredkmaxmoney'] = 0;
						$scoredkdataArr[$product['bid']] = $scoredkdata;
					}
				}
				if($product['scoredkmaxset']==0){
					if($scoredkdataArr[$product['bid']]['scoredkmaxpercent'] == 0){
						$scoredkdataArr[$product['bid']]['scoremaxtype'] = 1;
						$scoredkdataArr[$product['bid']]['scoredkmaxmoney'] += 0;
					}else{
						if($scoredkdataArr[$product['bid']]['scoredkmaxpercent'] > 0 && $scoredkdataArr[$product['bid']]['scoredkmaxpercent']<=100){
							$scoredkdataArr[$product['bid']]['scoredkmaxmoney'] += $scoredkdataArr[$product['bid']]['scoredkmaxpercent'] * 0.01 * $guige['sell_price'] * $num;
						}else{
							$scoredkdataArr[$product['bid']]['scoredkmaxmoney'] += $guige['sell_price'] * $num;
						}
					}
				}elseif($product['scoredkmaxset']==1){
					$scoredkdataArr[$product['bid']]['scoremaxtype'] = 1;
					$scoredkdataArr[$product['bid']]['scoredkmaxmoney'] += $product['scoredkmaxval'] * 0.01 * $guige['sell_price'] * $num;
				}elseif($product['scoredkmaxset']==2){
					$scoredkdataArr[$product['bid']]['scoremaxtype'] = 1;
					$scoredkdataArr[$product['bid']]['scoredkmaxmoney'] += $product['scoredkmaxval'] * $num;
				}else{
					$scoredkdataArr[$product['bid']]['scoremaxtype'] = 1;
					$scoredkdataArr[$product['bid']]['scoredkmaxmoney'] += 0;
				}
			}
			/**************************通证抵扣 start*******************************/
            if(getcustom('product_givetongzheng')){
                if($product['tongzhengdkmaxset']==0){
                    $is_sysset_tongzhengdk = true;
                    if($is_sysset_tongzhengdk){
                        if($userinfo['tongzhengdkmaxpercent'] == 0){
                            $userinfo['tongzhengmaxtype'] = 1;
                            $tongzhengdkmaxmoney += 0;
                        }else{
                            if($userinfo['tongzhengdkmaxpercent'] > 0 && $userinfo['tongzhengdkmaxpercent']<100){
                                $tongzhengdkmaxmoney += $userinfo['tongzhengdkmaxpercent'] * 0.01 * $guige['sell_price'] * $num;
                            }else{
                                $tongzhengdkmaxmoney += $guige['sell_price'] * $num;
                            }
                        }
                    }
                }elseif($product['tongzhengdkmaxset']==1){
                    $userinfo['tongzhengmaxtype'] = 1;
                    $tongzhengdkmaxmoney += $product['tongzhengdkmaxval'] * 0.01 * $guige['sell_price'] * $num;
                }elseif($product['tongzhengdkmaxset']==2){
                    $userinfo['tongzhengmaxtype'] = 1;
                    $tongzhengdkmaxmoney += $product['tongzhengdkmaxval'] * $num;
                }else{
                    $userinfo['scoremaxtype'] = 1;
                    $tongzhengdkmaxmoney += 0;
                }
            }
            /**************************通证抵扣 end*******************************/
            if(getcustom('member_goldmoney_silvermoney')){
                //银值抵扣数额
                if($ShopSendSilvermoney && $product['silvermoneydec_ratio']>0 && $this->member['silvermoney']>0){
                    $allsilvermoneydec += $product['silvermoneydec_ratio'] * 0.01 * $guige['sell_price'] * $num;//用户银值
                }
                //金值抵扣数额
                if($ShopSendGoldmoney && $product['goldmoneydec_ratio']>0 && $this->member['goldmoney']>0){
                    $allgoldmoneydec += $product['goldmoneydec_ratio'] * 0.01 * $guige['sell_price'] * $num;//用户金值
                }
            }
            if(getcustom('shop_product_commission_memberset')){
				//填写分销员ID权限
	            if(!$ShopProductCommissionMemberSet){
	                $product['commission_memberset'] = 0;
	            }
	        }

            // 产品积分
            if(getcustom('member_shopscore')){
                //暂时跟随系统设置，无商品单独设置
                if(!$product['shopscoredkmaxset'] || $product['shopscoredkmaxset']==0){
                    if($userinfo['shopscoredkmaxpercent'] <=0){
                        $userinfo['shopscoremaxtype'] = 1;
                    }else{
                        if($userinfo['shopscoredkmaxpercent'] > 0 && $userinfo['shopscoredkmaxpercent']<=100){
                            $pre_shopscoredkmaxmoney = $userinfo['shopscoredkmaxpercent'] * 0.01 * $guige['sell_price'] * $num;
                        }else{
                            $pre_shopscoredkmaxmoney = $guige['sell_price'] * $num;
                        }
                        $userinfo['shopscoredkmaxmoney'] += round($pre_shopscoredkmaxmoney,2);
                    }
                }else{
                    //暂不走这里逻辑
                    if($product['shopscoredkmaxset']==1){
                        $userinfo['shopscoremaxtype'] = 1;
                        $pre_shopscoredkmaxmoney = $product['shopscoredkmaxval'] * 0.01 * $guige['sell_price'] * $num;
                    }elseif($product['shopscoredkmaxset']==2){
                        $userinfo['shopscoremaxtype'] = 1;
                        $pre_shopscoredkmaxmoney = $product['shopscoredkmaxval'] * $num;
                    }else{
                        $userinfo['shopscoremaxtype'] = 1;
                        $pre_shopscoredkmaxmoney = 0;
                    }
                    $userinfo['shopscoredkmaxmoney'] += round($pre_shopscoredkmaxmoney,2);
                }
            }

			$product['weightkey'] = 0;
			$product['weightlist'] = [];
			if(getcustom('weight_template')){
				$weightlist = [];
				if($product['weighttype']>0){
					if($product['weighttype']==1){
						$weightlist = Db::name('shop_weight_template')->where('aid',aid)->where('status',1)->select()->toArray();
					}elseif($product['weighttype']==2){
						$weightdata = explode(',',$product['weightdata']);
						$weightlist = Db::name('shop_weight_template')->where('aid',aid)->where('id','in',$weightdata)->where('status',1)->select()->toArray();
					}
				}
				if($weightlist){
					$weight = $guige['weight'] * $num;
					$rs = \app\model\Weight::formatWeightList($weightlist,$weight);		
					//var_dump($rs);
					$product['weightlist'] = $rs['weight'];
				}
			}
            $groupBid = $product['bid'];
            //供货的单独分组
            if(getcustom('product_supply_chain')){
                if($product['product_type']==7){
                    $supplierGuige = Db::name('supplier_shop_guige')->where('aid',aid)->where('proid',$proid)->where('ggid',$ggid)->field('min_price,max_price,is_free_post,freight_money,tax_amount,num gg_num')->find();
                    if($supplierGuige){
                        $guige = array_merge($guige,$supplierGuige);
                    }
                    //保税和直邮单独下单
                    if($product['trade_type']=='1101' || $product['trade_type']=='1303'){
                        $groupBid = $groupBid .'_'.$product['product_type'].'_'.$product['trade_type'];
                    }else{
                        $groupBid = $groupBid .'_'.$product['product_type'];
                    }
                }
            }

            if(getcustom('supply_zhenxin')){
            	//甄新汇选单独分组下单
                if($product['issource'] && $product['source'] == 'supply_zhenxin'){
                    $groupBid = $groupBid .'_supplyzhenxin_'.$product['sproid'];
                }
            }
            if(getcustom('supply_yongsheng')){
            	//永盛商品单独分组下单
            	if($product['issource'] && $product['source'] == 'supply_yongsheng'){
                    $groupBid = $groupBid .'_supplyyongsheng_'.$product['sproid'];
                }
            }
			if(!$allbuydata[$groupBid]) $allbuydata[$groupBid] = [];
			if(!$allbuydata[$groupBid]['prodata']) $allbuydata[$groupBid]['prodata'] = [];
			if(getcustom('product_pickup_device')){
                $devicedata = input('param.devicedata');
                list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
                if($goods_lane && $device_no){
                    $device_goods= Db::name('product_pickup_device_goods')->where('aid',aid)->where('goods_lane',$goods_lane)->where('device_no',$device_no)->find();
                 
                    //模式是不固定柜门，不是指定柜门二维码，且库存是0的 
                    $type = Db::name('product_pickup_device_set')->where('aid',aid)->value('type');
                    if($device_goods['real_stock'] ==0 && $type ==0 && !$dgid){
                        //type=1固定柜门 0:根据商品查找其他的
                        $device_goods= Db::name('product_pickup_device_goods')->where('aid',aid)->where('device_no',$device_no)->where('proid',$product['id'])->where('ggid',$device_goods['ggid'])->where('real_stock','>',0)->where('stock',$device_goods['stock'])->find();
                    }
                    if($device_goods['real_stock'] ==0){
                        return $this->json(['status'=>0,'msg'=>'产品库存不足']);
                    }
                }
            }
			$thisprodata =  ['product'=>$product,'guige'=>$guige,'num'=>$num];
			if(getcustom('shop_product_jialiao')){
                $thisprodata['jldata'] = $jldata[$key];
            }
            $product_formdata = [];
            if(getcustom('shop_product_form')){
                if($product['form_id']){
                    $formcontent = Db::name('form')->where('aid',aid)->where('bid',$product['bid'])->where('id',$product['form_id'])->value('content');
                    $product_formdata = $formcontent?json_decode($formcontent,true):[];
                    
                    foreach($product_formdata as $pk=>$pformdata){
                        if($pformdata['key'] == 'year'){
                            $year = [];
                            for($y = $pformdata['val2'][0];$y < $pformdata['val2'][1];$y++){
                                $year[] = $y;
                            }
                            $year = array_reverse($year);
                            $product_formdata[$pk]['val2'] = $year;
                        }
                    }
                }
            }
            
            $thisprodata['formdata'] = $product_formdata;
            $thisprodata['editorFormdata'] = [];
            if(getcustom('money_dec')){
            	if(getcustom('money_dec_product')){
            		//如果商品存在单独设置，则余额抵扣部分改为商品单独金额计算的最大抵扣金额
	            	if($product['moneydecset']!=0){
	                    $money_dec_type = 1;
	                }
            	}
            	$allbuydata[$groupBid]['money_dec_type'] = $money_dec_type;
            }
			$allbuydata[$groupBid]['prodata'][] = $thisprodata;
           
			if($product['to86yk_tid']){
				$extendInput = [['key'=>'input','val1'=>'充值账号','val2'=>'请输入充值账号','val3'=>1],['key'=>'input','val1'=>'确认账号','val2'=>'请再次输入充值账号','val3'=>1]];
			}

            if(getcustom("product_service_fee")){
                $show_service_fee = $product['service_fee_switch'];
            }
		}
        //限购判断
        if($productLimit){
            foreach ($productLimit as $pitem){
                if($pitem['buy']+$pitem['buyed'] > $pitem['perlimit']){
                    return $this->json(['status'=>0,'msg'=>'['.$pitem['name'].'] 每人限购'.$pitem['perlimit'].'件']);
                }
            }
        }
		//支付方式不同，不允许同时结算
        if(getcustom('product_show_moneyprice')){
            if(count($productMoneyPay)>1){
                return $this->json(['status'=>0,'msg'=>'支付方式不同，请分开结算']);
            }
        }
		if($autofahuo>0 && count($prodata) > 1){
			return $this->json(['status'=>0,'msg'=>'虚拟商品请单独购买']);
		}
		if(getcustom('product_handwork')){
            //手工活类单独下单
            if(in_array(3,$protypes)){
            	$ptnum = count($protypes);
            	if($ptnum>1){
            		return $this->json(['status'=>0,'msg'=>'手工活类商品请单独购买']);
            	}
            }
        }
		if(getcustom('shop_product_fenqi_pay')){
            //分期商品单独下单
            if(in_array(5,$protypes)){
            	if(count($ggid_arr)>1){
            		return $this->json(['status'=>0,'msg'=>'分期商品请单独购买']);
            	}
            }
        }
        if(getcustom('member_forzengxcommission')){
            //佣金类单独下单
            if(in_array(10,$protypes)){
            	$ptnum = count($protypes);
            	if($ptnum>1){
            		return $this->json(['status'=>0,'msg'=>t('佣金').'类商品请单独购买']);
            	}
            }
        }

        if(getcustom('money_dec')){
            //总平台余额抵扣
            $moneydec = false;
        }
		$userinfo['scoredkmaxmoney'] = round($scoredkmaxmoney,2);
        $userinfo['tongzhengdkmaxmoney'] = round($tongzhengdkmaxmoney,2);
		$havetongcheng = 0;
		if(getcustom('shop_buy_worknum')){
        	$worknum_status=false;//是否显示工号
        }
		foreach($allbuydata as $groupBid=>$buydata){
			$bidGroupArr = explode('_',$groupBid);
            $bid = $bidGroupArr[0];
            if(getcustom('money_dec')){
                $allbuydata[$groupBid]['money_dec_rate']  = 0;//最大抵扣比例
                $allbuydata[$groupBid]['money_dec_money'] = 0;//最大抵扣金额
                if(empty($bid)){
                    $money_dec_rate = 0;//抵扣比例
                    if($adminset['money_dec'] && $adminset['money_dec_rate']>0){
                        $moneydec = true;
                        $allbuydata[$groupBid]['money_dec_rate'] = $adminset['money_dec_rate'];
                    }
                }else{
                    //查询商户余额抵扣比例
                    $business = Db::name('business')->where(['aid'=>aid,'id'=>$bid])->field('money_dec,money_dec_rate')->find();
                    if($business && $business['money_dec'] && $business['money_dec_rate']>0){
                        $moneydec = true;
                        $allbuydata[$groupBid]['money_dec_rate'] = $business['money_dec_rate'];
                    }
                }
            }

			if(getcustom('guige_split')){
				$rs = \app\model\ShopProduct::checkstock($buydata['prodata']);
				if($rs['status'] == 0) return $this->json($rs);
			}

			$customfreight = 0;//自定义快递 0：默认快递 1：自定义为普通快递
			if(getcustom('supply_zhenxin')){
				//判断是不是甄新汇选商品
				if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
					$customfreight = 1;
				}
			}
			if($customfreight>0){
				if($customfreight == 1){
					$formdata = json_encode([['key'=>'input','val1'=>'备注','val2'=>'选填，请输入备注信息','val3'=>'0']]);
					$freightList = [['id'=>0,'name'=>'普通快递','pstype'=>0,'formdata'=>$formdata]];
				}
			}else if($autofahuo>0){
				$freightList = [['id'=>0,'name'=>($autofahuo==3?'自动发货':'在线卡密'),'pstype'=>$autofahuo]];
			}else{
				$resetBid = $bid;
				if(getcustom('business_show_platform_product') && $this->member && $this->member['bind_business']){
					$resetBid = $this->member['bind_business'];
				}
				$freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$resetBid]]);
				$fids = [];
				foreach($freightList as $v){
					$fids[] = $v['id'];
				}
				if(getcustom('deposit')){
				     $is_have_deposit =0;
                }
				foreach($buydata['prodata'] as $prodata){
					if($prodata['product']['freighttype']==0){
						$fids = array_intersect($fids,explode(',',$prodata['product']['freightdata']));
					}else{
						$thisfreightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$resetBid]]);
						$thisfids = [];
						foreach($thisfreightList as $v){
							$thisfids[] = $v['id'];
						}
						$fids = array_intersect($fids,$thisfids);
					}
                    if(getcustom('deposit')){
                        if($prodata['product']['deposit_status']==1){
                            $is_have_deposit = 1;
                        }
                    }
				}
				if(!$fids){
					if(count($buydata['prodata'])>1){
						return $this->json(['status'=>0,'msg'=>'所选择商品配送方式不同，请分别下单']);
					}else{
						return $this->json(['status'=>0,'msg'=>'获取配送方式失败']);
					}
				}
                $freight_order = 'sort desc,id';
				if(getcustom('product_pickup_device')){
                    $devicedata = input('param.devicedata');
                    list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
				    if($devicedata && $goods_lane){
                        $freight_order = 'pstype asc,id';
                    }
                }

                $freightwhere = [['status','=',1],['aid','=',aid],['bid','=',$resetBid],['id','in',$fids]];
                if(getcustom('supply_yongsheng')){
					//判断是不是永盛商品，永盛商品仅支持普通快递
					if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
						$freightwhere[] = ['pstype','=',0];
					}
				}
                if(getcustom('deposit')){
                    if($is_have_deposit ==1){
                        $freightwhere[] = ['pstype','=',2];
                    }
                }
				$freightList = \app\model\Freight::getList($freightwhere,$freight_order);

				foreach($freightList as $k=>$v){
					//var_dump($freightList);
					if($v['pstype']==2){ //同城配送
						$havetongcheng = 1;
					}
					if(getcustom('hmy_yuyue') && $v['pstype']==12){
						//红蚂蚁定制
						$havetongcheng = 1;
					}
					if(getcustom('shop_buy_worknum')){
						if($k == 0 && $v['worknum_status']){
							$worknum_status=true;//是否显示工号
						}
			        }

                    if(getcustom('wx_express_intracity')){
                        if($v['pstype']==2){
                            $peisong_set = Db::name('peisong_set')->where('aid',aid)->field('wxtc_status,wxtc_store_id,wxtc_status_business')->find();
                            $wxtc_store_id = 0;
                            if($bid == 0 && $peisong_set['wxtc_status'] == 1){
                                $wxtc_store_id = $peisong_set['wxtc_store_id'];
                            }elseif($bid > 0 && $peisong_set['wxtc_status'] == 1 && $peisong_set['wxtc_status_business'] == 1){
                                $wxtc_store_id = Db::name('business')->where('aid',aid)->where('id',$bid)->value('wxtc_store_id');
                            }
                            $wxtc_store = Db::name('wx_express_intracity_store')->where('aid',aid)->where('id',$wxtc_store_id)->find();

                            if($wxtc_store && $wxtc_store['select_delivery_time'] != 1){
                                //不允许选择配送时间
                                $freightList[$k]['pstimeset'] = 0;
                            }
                        }
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
		if(!$address) $address = [];

		$needLocation = 0;
		$allproduct_price = 0;
        $bidGroupList = [];
        $bidList = [];
        $allproduct_freezemoney_price = 0;//冻结资金
        foreach($allbuydata as $groupBid=>$buydata){
            $bidGroupList[] = $groupBid;
            $bidGroupArr = explode('_',$groupBid);
            $bid = $bidGroupArr[0];
            if(!in_array($bid,$bidList)) $bidList[] = $bid;
            $product_type = 0;
            if(count($bidGroupArr)>1){
                $product_type = $bidGroupArr[1];
            }
            $trade_type = 0;
            if(count($bidGroupArr)>2){
                $trade_type = $bidGroupArr[2];
            }
			if($bid!=0){
				$field = 'id,aid,cid,name,logo,tel,address,sales,longitude,latitude,start_hours,end_hours,start_hours2,end_hours2,start_hours3,end_hours3,end_buy_status,invoice,invoice_type,is_open';
				if(getcustom('member_dedamount')){
					$field .= ',paymoney_givepercent';
				}
				$business = Db::name('business')->where('id',$bid)->field($field)->find();
                if($business['is_open']==0) return $this->json(['status'=>-4,'msg'=>$business['name'].'未营业']);
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
			}else{
				$business = Db::name('admin_set')->where('aid',aid)->field('id,name,logo,desc,tel,invoice,invoice_type,invoice_rate')->find();
			}
            $business['invoice_type'] = $business['invoice'] ? explode(',', $business['invoice_type']) : [];
			$product_priceArr = [];
			$product_price = 0;
			$product_freezemoney_price = 0;//冻结资金
            $service_fee_price = 0;
			$needzkproduct_price = 0;
			$totalweight = 0;
			$totalnum = 0;
			$prodataArr = [];
			$proids = [];
			$cids = [];
			$cids2 = [];   
			$jldataArr = [];
            //符合满减的商品价格
            $mj_price = 0;
            $bindMendianIds = [];
            $supplierFreight = 0;//供货的额外运费

            if(getcustom('supply_zhenxin')){
            	$zxpostages = [];
            }
           
            if(getcustom('yx_hongbao_queue_free')){
                $is_use_youhui = 1;
                $hongbao_queue_set = Db::name('hongbao_queue_free_set')->where('aid',aid)->field('productids,gettj')->find();
                $hongbao_join_proids = $hongbao_queue_set['productids'];
                $hongbao_join_proids_arr = explode(',',$hongbao_join_proids);
                $hongbao_gettj = explode(',',$hongbao_queue_set['gettj']);
            }
            if(getcustom('deposit')){
                $deposit_buy_list = [];
                $deposit_dk_list = [];
            }
			foreach($buydata['prodata'] as &$prodata){
				$product_priceArr[] = $prodata['guige']['sell_price'] * $prodata['num'];

				$pre_product_price = $prodata['guige']['sell_price'] * $prodata['num'];//当前数量购买价格

				$service_fee_price += $prodata['guige']['service_fee'] * $prodata['num'];
				if($prodata['product']['balance']){
					$product_price += $pre_product_price * (1-$prodata['product']['balance']*0.01);
				}else{
					$product_price += $pre_product_price;
				}
				if(getcustom('freeze_money')){
				    //冻结资金支付
                    $freezemoney_price = $prodata['guige']['freezemoney_price'] * $prodata['num'];
                    $product_freezemoney_price = bcadd($product_freezemoney_price, $freezemoney_price,3);
                }

				if($prodata['product']['lvprice']==0 && $prodata['product']['no_discount'] == 0){ //未开启会员价
					$needzkproduct_price += $prodata['guige']['sell_price'] * $prodata['num'];
				}
                if(getcustom('product_bind_mendian')){
                    if($prodata['product']['bind_mendian_ids'] && !in_array('-1',explode(',',$prodata['product']['bind_mendian_ids']))){
                        $bindMendianIds = array_unique(array_merge($bindMendianIds,explode(',',$prodata['product']['bind_mendian_ids'])));
                    }
                }
				$totalweight += $prodata['guige']['weight'] * $prodata['num'];
				$totalnum += $prodata['num'];
				$_prostr = $prodata['product']['id'].','.$prodata['guige']['id'].','.$prodata['num'];
				if(isset($prodata['product']['glassrecord']) && $prodata['product']['glassrecord']){
                    $_prostr .=','.$prodata['product']['glassrecord']['id'];
                }
                if(getcustom('product_glass_custom')){
                    if(isset($prodata['product']['glass_custom']) && $prodata['product']['glass_custom']){
                        $_prostr .=','.$prodata['product']['glass_custom']['id'];
                    }
                }
				$prodataArr[] = $_prostr;
				if(getcustom('shop_product_jialiao')){
                    $jldataArr[] = $prodata['jldata'];
                }
				$proids[] = $prodata['product']['id'];
				$cids = array_merge(explode(',',$prodata['product']['cid']),$cids);
				if(!empty($prodata['product']['cid2'])){
					$cids2 = array_merge(explode(',',$prodata['product']['cid2']),$cids2);
				}
                //如果满减设置存在，且数据存在
                if($mjset && $mjdata){
                    //指定分类
                    if($mjset['fwtype']==1){
                        //指定分类数组
                        $cat_arr     = explode(",",$mjset['categoryids']);
                        //商品分类
                        $pro_cat_arr = explode(",",$prodata['product']['cid']);
                        //交集
                        $j_arr = array_intersect($cat_arr,$pro_cat_arr);
                        if($j_arr){
                            if($prodata['product']['balance']){
                                $mj_price += $pre_product_price * (1-$prodata['product']['balance']*0.01);
                            }else{
                            	$mj_price += $pre_product_price;
                            }
                        }
                    //指定商品
                    }else if($mjset['fwtype']==2){
                        $pro_arr = explode(",",$mjset['productids']);
                        //商品在指定商品内
                        if (in_array($prodata['product']['id'], $pro_arr)){
                            if($prodata['product']['balance']){
                                $mj_price += $pre_product_price * (1-$prodata['product']['balance']*0.01);
                            }else{
                            	$mj_price += $pre_product_price;
                            }
                        }
                    }else{
                        if($prodata['product']['balance']){
                            $mj_price += $pre_product_price * (1-$prodata['product']['balance']*0.01);
                        }else{
                        	$mj_price += $pre_product_price;
                        }
                    }
                }
                //供应链是否包邮
                $ggval = $prodata['guige'];
                if($product_type==7 && $ggval['is_free_post']==0){
                    $supplierFreight = $supplierFreight + $ggval['freight_money'];//供货商的运费
                }
                if(getcustom('supply_zhenxin')){
                    //甄新汇选
                    if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
                        //规格快递查询数组
                        $zxpostages[] = ['sku_id'=>$prodata['guige']['skuid'],'sku_num'=>$prodata['num']];
                    }
                }
                if(getcustom('yx_hongbao_queue_free')){
                    if(in_array($prodata['product']['id'],$hongbao_join_proids_arr) && (in_array($this->member['levelid'],$hongbao_gettj) || in_array(-1,$hongbao_gettj))) $is_use_youhui = 0;
                }

                if(getcustom('member_dedamount')){
                    //如果变动抵扣金额大于0，且抵扣比例大于0，且是多商户 计算订单抵扣金额
                    if($userinfo['dedamount2']>0 && $userinfo['dedamount_dkpercent']>0 && !empty($bid) && $bid>0){
                        if($business && $business['paymoney_givepercent']>0){
                            //商户让利部分金额
                            $paymoney_givemoney = $pre_product_price * $business['paymoney_givepercent']/100;
                            $paymoney_givemoney = round($paymoney_givemoney,2);
                            if($paymoney_givemoney>0){
                                //让利部分抵扣金额
                                $dedamount_dkmoney = $paymoney_givemoney*$userinfo['dedamount_dkpercent']/100;
                                $dedamount_dkmoney = round($dedamount_dkmoney,2);
                                if($dedamount_dkmoney>0){
                                    if($userinfo['dedamount2']>=$dedamount_dkmoney){
                                        $userinfo['dedamount_dkmoney'] += $dedamount_dkmoney;//增加订单抵扣金额
                                        $userinfo['dedamount2'] -= $dedamount_dkmoney;//减少会员变动抵扣金
                                    }else{
                                        $userinfo['dedamount_dkmoney'] += $userinfo['dedamount2'];//增加订单抵扣金额
                                        $userinfo['dedamount2'] = 0;//减少会员变动抵扣金
                                    }
                                }
                            }
                        }
                    }
                }

                if(getcustom('money_dec_product')){
                    //如果是最大抵扣金额类型，则需要计算每个商品的最大可抵扣金额
                    if($allbuydata[$groupBid]['money_dec_type'] == 1){
                        //如果是跟随系统
                        if($prodata['product']['moneydecset'] == 0){
                            $pre_dec_money = $pre_product_price*$allbuydata[$groupBid]['money_dec_rate']/100;
                            if($pre_dec_money>$pre_product_price){
                                $pre_dec_money =$pre_product_price;
                            }
                            $allbuydata[$groupBid]['money_dec_money'] += round($pre_dec_money,2);
                        //如果是单独设置
                        }else if($prodata['product']['moneydecset'] >=1){
                            if($prodata['product']['moneydecval'] && $prodata['product']['moneydecval']>=0){
                                if($prodata['product']['moneydecset'] == 1){
                                    $pre_dec_money = $pre_product_price*$prodata['product']['moneydecval']/100;
                                    if($pre_dec_money>$pre_product_price){
                                        $pre_dec_money =$pre_product_price;
                                    }
                                    $allbuydata[$groupBid]['money_dec_money'] += round($pre_dec_money,2);
                                }else if($prodata['product']['moneydecset'] == 2){
                                    $pre_dec_money = round($prodata['product']['moneydecval'],2);
                                    if($pre_dec_money>$pre_product_price){
                                        $pre_dec_money =$pre_product_price;
                                    }
                                    $allbuydata[$groupBid]['money_dec_money'] += $pre_dec_money;
                                }
                            }
                        }
                    }
                }
                if(getcustom('product_chinaums_subsidy')){
                    if($prodata['product']['is_subsidy']){
                        $subsidyRecord = Db::name('chinaums_subsidy_apply')
                            ->where('cid',$prodata['product']['cid'])
                            ->where('aid',aid)
                            ->where('mid',mid)
                            ->where('status','in','1,4') // 1:已领取 4:已撤销
                            ->find();
                        if($subsidyRecord){
                            if($subsidyRecord['name'] != $address['name']){
                                //判断姓名是否一致
                                $subsidyTips = "您的姓名与领取时填写不一致，将无法参与国补补贴";
                                $subsidyVerify = 1;
                            }elseif($subsidyRecord['mobile'] != $address['tel']){
                                //判断手机号是否一致
                                $subsidyTips = "您的手机号与领取时填写不一致，将无法参与国补补贴";
                                $subsidyVerify = 1;
                            }elseif(isset($address['area']) && strpos($address['area'], '浙江') === false) {
                                $subsidyTips = "当前地址不在浙江省范围内，无法参与国补补贴";
                                $subsidyVerify = 1;
                            }
                        }
                    }
                }
                if(getcustom('deposit')){
                    if($prodata['product']['deposit_id'] && $prodata['product']['deposit_status']){
                        $deposit_member = Db::name('deposit_member')->where('aid',aid)->where('mid',mid)->where('deposit_id',$prodata['product']['deposit_id'])->find();
                        $deposit = Db::name('deposit')->where('aid',aid)->where('id',$prodata['product']['deposit_id'])->where('status',1)->find();
                      
                        if($deposit_member && $deposit){
                             $deposit_dk_num =  $deposit_dk_list[$prodata['product']['deposit_id']]??0; //1
                             $sy_deposit_member_num =  $deposit_member['num'] -$deposit_dk_num; //减去前面抵扣的数量，剩余押金的数量
                            if($sy_deposit_member_num < $prodata['num']){
                                $buy_deposit_num = $prodata['num'] -$sy_deposit_member_num;
                            }else{
                                $buy_deposit_num = 0;
                                $sy_deposit_member_num=  $prodata['num'];
                            }
                            $deposit_dk_list[$prodata['product']['deposit_id']] =  $sy_deposit_member_num;
                        }else{
                            $buy_deposit_num =  $prodata['num'];
                        }
                        
                        if($buy_deposit_num > 0 && $deposit){
                            if($deposit_buy_list[$prodata['product']['deposit_id']]){
                               $this_buy_data =    $deposit_buy_list[$prodata['product']['deposit_id']];
                                $this_buy_data['buy_num'] = $this_buy_data['buy_num'] + $buy_deposit_num;
                                $this_buy_data['totalmoney'] = floatval( dd_money_format($this_buy_data['buy_num'] * $deposit['money']));
                                $deposit_buy_list[$prodata['product']['deposit_id']] =  $this_buy_data;
                            }else{
                                $deposit_buy_list[$prodata['product']['deposit_id']] = [
                                    'deposit_name' => $deposit['name'],
                                    'buy_num' => $buy_deposit_num,
                                    'totalmoney' => floatval(dd_money_format($buy_deposit_num * $deposit['money']) )
                                ]; 
                            }
                            
                        }
                    }
                }
			}
			if(getcustom('deposit')){
                $allbuydata[$groupBid]['deposit_list'] =array_values($deposit_buy_list);
                $allbuydata[$groupBid]['deposit_list_key'] = [];
            }
			$prodatastr = implode('-',$prodataArr);

            if(getcustom('mendian_upgrade') && getcustom('location_mendian')){
                $mendian_upgrade_status = Db::name('admin')->where('id',aid)->value('mendian_upgrade_status');

                // $mode = Db::name('admin_set')->where('aid',aid)->value('mode');
                if($mendian_upgrade_status == 1 ){
                    if($this->member['mdid']){
                       $mendian_id = $this->member['mdid']; 
                    }elseif($mendian_id){
                        Db::name('member')->where('aid',aid)->where('id',mid)->update(['mdid'=>$mendian_id]);
                    }
                    if(!$mendian_id){
                        $latitude = input('param.latitude/f');
                        $longitude = input('param.longitude/f');
                        if($latitude && $longitude){
                            $mdorder = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) asc");
                            $mendian_id = Db::name('mendian')->where('aid',aid)->where('status',1)->where('bid',$bid)->orderRaw($mdorder)->value('id');
                            if($mendian_id){
                                Db::name('member')->where('aid',aid)->where('id',mid)->update(['mdid'=>$mendian_id]);
                            }
                        }
                    }
                }
            }

            if(getcustom('mendian_usercenter')){
                if($this->member['lock_mdid']){
                    $mendian_id = $this->member['lock_mdid'];
                }
            }
            if(getcustom('mendian_bind')){
                if($this->member['bind_mendian']){
                    $mdBid = Db::name('mendian')->where('aid',aid)->where('id',$this->member['bind_mendian'])->value('bid');
                    if($mdBid == $bid){
                       $mendian_id = $this->member['bind_mendian'];
                    }
                }
            }

            $rs = \app\model\Freight::formatFreightList($buydata['freightList'],$address,$product_price,$totalnum,$totalweight,$bindMendianIds,$mendian_id);
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
			if(getcustom('supply_zhenxin')){
                //甄新汇选
                if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
                    //查询甄新汇并给地址赋值区域编号
                    if($zxpostages && $address){
                        if(empty($address['province_zxcode']) || empty($address['city_zxcode']) || empty($address['district_zxcode'])){
                            //换算区域code
                            $area = ['province'=>$address['province'],'city'=>$address['city'],'district'=>$address['district']];
                            $getarea2 = \app\custom\SupplyZhenxinCustom::getarea2(aid,$bid,$area);
                            if($getarea2 && $getarea2['status'] == 1){
                                $address['province_zxcode'] = $getarea2['data']['province_zxcode'];
                                $address['city_zxcode']     = $getarea2['data']['city_zxcode'];
                                $address['district_zxcode'] = $getarea2['data']['district_zxcode'];
                                Db::name('member_address')->where('id',$address['id'])->where('mid',mid)->update(['province_zxcode'=>$getarea2['data']['province_zxcode'],'city_zxcode'=>$getarea2['data']['city_zxcode'],'district_zxcode'=>$getarea2['data']['district_zxcode']]);
                            }
                        }
                        if($address['province_zxcode'] && $address['city_zxcode'] && $address['district_zxcode']){
                            //甄新汇选区域编码
                            $shipAreaCode = $address['province_zxcode'].','.$address['city_zxcode'].','.$address['district_zxcode'];
                            $getpostage = \app\custom\SupplyZhenxinCustom::getpostage(aid,$bid,$zxpostages,$shipAreaCode);
                            if($getpostage && $getpostage['status'] == 1){
                                $freightList[0]['freight_price'] = $getpostage['data']['postage'];
                            }
                        }
                    }
                }
            }
            if($supplierFreight>0){
                //把供货商收取的运费累计到系统运费中
                foreach ($freightList as $frk=>$frv){
                    if(in_array($frv['pstype'],[0,2])){
                        $freightList[$frk]['freight_price'] = $frv['freight_price'] + $supplierFreight;
                    }
                }
            }
            if(getcustom('product_pickup_device')){
                $devicedata = input('param.devicedata');
                list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
                if($device_no){
                    foreach($freightList as $dfk=>&$dfv){
                        if($dfv['pstype'] ==1){
                            $f_device =  Db::name('product_pickup_device')
                                ->where('aid',aid)->where('device_no',$device_no)->field('address,name')->find();
                             //重置自提的名字和地址，只保留一个
                            $newstoredata[0] = $dfv['storedata'][0];
                            $newstoredata[0]['name'] =$f_device['name'];
                            $newstoredata[0]['address'] =$f_device['address'];
                            $dfv['storedata'] = [];
                            $dfv['storedata'] =$newstoredata; 
                            $freightList[$dfk] = $dfv;
                        }
                    }
                }
            }
            if(getcustom('up_floor_fee')){
                foreach ($freightList as $dfk=>$dfv){
                    $show_add_floor = 0;
                    $up_floor_fee =0;
                  if($dfv['up_floor_fee_status'] ==1 && $dfv['pstype'] ==2){
                      //开启上楼费，但是地址没有设置楼层
                      if(!$address['floor'] ){
                          $show_add_floor = 1;
                      }else{
                          //地址如果设置的无电梯，计算上楼费
                          if($address['have_dianti'] ==0){
                              //根据规则计算，公式：基础费*元+每层费用×（楼层数-1）。
                              $up_floor_fee= dd_money_format($dfv['up_floor_base_price'] + $dfv['up_floor_each_add_price'] * ($address['floor'] - 1) * $totalnum);
                          }
                      }
                  }
                  
                    $freightList[$dfk]['have_dianti'] = 0;
                    $freightList[$dfk]['show_add_floor'] = $show_add_floor;
                    $freightList[$dfk]['up_floor_fee'] =floatval( $up_floor_fee);
                    $freightList[$dfk]['floor'] = 0;
                }
            }
			$freightArr = $rs['freightArr'];
			if($rs['needLocation']==1) $needLocation = 1;
			$leveldk_money = 0;
			if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
				$leveldk_money = $needzkproduct_price * (1 - $userlevel['discount'] * 0.1);
			}
			$leveldk_money = round($leveldk_money,2);
			$price = $product_price - $leveldk_money;
            $mj_price = $mj_price-$leveldk_money;

			$manjian_money = 0;
			$moneyduan = 0;
			if($mjdata && $mj_price>0){
                //如果是累计消费额满减
                if($mjset['total_status']==1){
                    //指定分类
                    if($mjset['fwtype']==1){
                        //查询他分类消费累计
                        $sum_money  = Db::name('shop_order_goods')
                            ->alias('sog')
                            ->join('shop_order so','so.id   = sog.orderid')
                            ->join('shop_product sp','sp.id = sog.proid')
                            ->where('sog.mid',mid)
                            ->where('so.status',3)
                            ->where('sp.cid','in',$mjset['categoryids'])
                            ->sum('sog.totalprice');

                        //分类退款累计
                        $refund_money = Db::name('shop_refund_order_goods')
                            ->alias('srog')
                            ->join('shop_order so','so.id   = srog.orderid')
                            ->join('shop_product sp','sp.id = srog.proid')
                            ->where('srog.mid',mid)
                            ->where('so.status',3)
                            ->where('so.refund_status',2)
                            ->where('sp.cid','in',$mjset['categoryids'])
                            ->sum('srog.refund_money');

                    //指定商品
                    }else if($mjset['fwtype']==2){
                        //查询他商品消费累计
                        $sum_money  = Db::name('shop_order_goods')
                            ->alias('sog')
                            ->join('shop_order so','so.id   = sog.orderid')
                            ->join('shop_product sp','sp.id = sog.proid')
                            ->where('sog.mid',mid)
                            ->where('so.status',3)
                            ->where('sog.proid','in',$mjset['productids'])
                            ->sum('sog.totalprice');

                        //商品退款累计
                        $refund_money = Db::name('shop_refund_order_goods')
                            ->alias('srog')
                            ->join('shop_order so','so.id   = srog.orderid')
                            ->join('shop_product sp','sp.id = srog.proid')
                            ->where('srog.mid',mid)
                            ->where('so.status',3)
                            ->where('so.refund_status',2)
                            ->where('srog.proid','in',$mjset['productids'])
                            ->sum('srog.refund_money');
                    //所有
                    }else{
                        //查询他消费累计
                        $sum_money    = Db::name('shop_order')->where('mid',mid)->where('status',3)->sum('totalprice');
                        //退款累计
                        $refund_money = Db::name('shop_order')->where('mid',mid)->where('status',3)->where('refund_status',2)->sum('refund_money');
                    }

                    //实际累计
                    $sj_money = $sum_money-$refund_money;
                    $sj_money = round($sj_money,2);
                    $all_price = $sj_money+$mj_price;
                    foreach($mjdata as $give){
                        if(($all_price - $leveldk_money)*1 >= $give['money']*1 && $give['money']*1 > $moneyduan){
                            $moneyduan = $give['money']*1;
                            $manjian_money = $give['jian']*1;
                        }
                    }
                }else{
                    foreach($mjdata as $give){
                        if(($mj_price - $leveldk_money)*1 >= $give['money']*1 && $give['money']*1 > $moneyduan){
                            $moneyduan = $give['money']*1;
                            $manjian_money = $give['jian']*1;
                        }
                    }
                }

			}
			if($manjian_money > 0){
				$allbuydata[$groupBid]['manjian_money'] = round($manjian_money,2);
			}else{
				$allbuydata[$groupBid]['manjian_money'] = 0;
			}

			if($bid > 0){
				$business = Db::name('business')->where('aid',aid)->where('id', $bid)->find();
				$bcids = $business['cid'] ? explode(',',$business['cid']) : [];
			}else{
				$bcids = [];
			}
			if($bcids){
				$whereCid = [];
				foreach($bcids as $bcid){
					$whereCid[] = "find_in_set({$bcid},canused_bcids)";
				}
				$whereCids = implode(' or ',$whereCid);
			}else{
				$whereCids = '0=1';
			}
			
			$newcouponlist = [];
			$coupon_type = '1,4,10,11';
			if(getcustom('coupon_shop_times_coupon')){
                $coupon_type .=',3';
            }
			$couponList = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('type','in',$coupon_type)->where('status',0)
				->whereRaw("bid=-1 or bid=".$bid." or (bid=0 and (canused_bids='all' or find_in_set(".$bid.",canused_bids) or ($whereCids)))")->where('minprice','<=',$price - $manjian_money)->where('starttime','<=',time())->where('endtime','>',time())
				->order('id desc')->select()->toArray();
			if(!$couponList) $couponList = [];
			foreach($couponList as $k=>$v){
				//$couponList[$k]['starttime'] = date('m-d H:i',$v['starttime']);
				//$couponList[$k]['endtime'] = date('m-d H:i',$v['endtime']);
				$couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$v['couponid'])->find();

                if($v['from_mid']) {
                    //已经被领取，不可再次赠送，属于仅自用
                    $couponinfo['isgive'] = 0;
                }else{
                    //仅转赠
                    if($couponinfo['isgive']==2){
                        continue;
                    }
                }

                //0全场通用,1指定类目,2指定商品,6指定商家类目
                if(!in_array($couponinfo['fwtype'],[0,1,2,6])){
                    continue;
                }
                //适用场景
                if(!in_array($couponinfo['fwscene'],[0])){
                    continue;
                }
				$usetj = explode(',',$couponinfo['usetj']);
				if(!in_array('-1',$usetj) && !in_array($this->member['levelid'],$usetj) && (!in_array('0',$usetj) || $this->member['subscribe']!=1)){
					continue;
				}

				$v['thistotalprice'] = $price - $manjian_money;
                $v['couponmoney'] = $v['money'];
				if($couponinfo['fwtype']==2){//指定商品可用
					$productids = explode(',',$couponinfo['productids']);
					if(!array_intersect($proids,$productids)){
						continue;
					}
                    $v['productids'] = $productids;
                    $thistotalprice = 0;
					foreach($buydata['prodata'] as $k2=>$v2){
						$product = $v2['product'];
						if(in_array($product['id'],$productids)){
							$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
                            $v['sell_price'] =  $v2['guige']['sell_price'];
						}
					}
					if($thistotalprice < $v['minprice']){
						continue;
					}
					$v['thistotalprice'] = $thistotalprice;
                    $v['couponmoney'] = min($thistotalprice,$v['money']);//可抵扣金额
				}
				if($couponinfo['fwtype']==1){//指定类目可用
					if(!$couponinfo['categoryids']) continue;
					$categoryids = explode(',',$couponinfo['categoryids']);
					$clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
					foreach($clist as $kc=>$vc){
						$categoryids[] = $vc['id'];
						$cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
						if($cate2) $categoryids[] = $cate2['id'];
					}
					if(!array_intersect($cids,$categoryids)){
						continue;
					}
					$thistotalprice = 0;
					foreach($buydata['prodata'] as $k2=>$v2){
						$product = $v2['product'];
						if(array_intersect(explode(',',$product['cid']),$categoryids)){
							$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
                            $v['sell_price'] =  $v2['guige']['sell_price'];
						}
					}
					if($thistotalprice < $v['minprice']){
						continue;
					}
					$v['thistotalprice'] = $thistotalprice;
                    $v['couponmoney'] = min($thistotalprice,$v['money']);//可抵扣金额
				}
				if($couponinfo['fwtype']==6){//指定商家类目可用
					if(!$couponinfo['categoryids2']) continue;
					$categoryids2 = explode(',',$couponinfo['categoryids2']);
					$clist2 = Db::name('shop_category2')->where('pid','in',$categoryids2)->select()->toArray();
					foreach($clist2 as $kc=>$vc){
						$categoryids2[] = $vc['id'];
						$cate2 = Db::name('shop_category2')->where('pid',$vc['id'])->find();
						if($cate2) $categoryids2[] = $cate2['id'];
					}
					if(!array_intersect($cids2,$categoryids2)){
						continue;
					}
					$thistotalprice = 0;
					foreach($buydata['prodata'] as $k2=>$v2){
						$product = $v2['product'];
						if(array_intersect(explode(',',$product['cid2']),$categoryids2)){
							$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
                            $v['sell_price'] =  $v2['guige']['sell_price'];
						}
					}
					if($thistotalprice < $v['minprice']){
						continue;
					}
					$v['thistotalprice'] = $thistotalprice;
                    $v['couponmoney'] = min($thistotalprice,$v['money']);//可抵扣金额
				}
				if($v['bid'] > 0){
					$binfo = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->find();
					$v['bname'] = $binfo['name'];
				}
				if(getcustom('coupon_not_used_discount')){
                    $v['not_used_discount'] =  $couponinfo['not_used_discount'];
                }
				$v['is_multiselect'] = 0;
				if(getcustom('coupon_multiselect')){
					 $v['is_multiselect'] =  $couponinfo['is_multiselect'];
				}
                if(getcustom('coupon_shop_times_coupon')){
                    if($v['type'] ==3){
                        //次数耗尽
                        if($v['limit_count']<=0 || ($v['limit_count'] - $v['used_count'] <=0))continue;
                        //每天核销的次数
                        if($v['limit_perday'] >0){
                            //核销数量
                            $dayhxnum =\app\common\Coupon::getTimesCouponHxnum(aid,$v);
                            //剩余可抵扣数量
                            $sy_perdaylimit = $v['limit_perday'] - $dayhxnum;
                            if($sy_perdaylimit <=0)continue;
                            $v['sy_limit_perday'] = $sy_perdaylimit<=0?0: $sy_perdaylimit;
                        }
                    }
                }
				$newcouponlist[] = $v;
			}
            $couponList = $newcouponlist;

			if(getcustom('deposit')){
                $use_ck_coupon_status = Db::name('shop_sysset')->where('aid',aid)->value('use_ck_coupon_status');
                $new_water_couponlist = [];
                if($use_ck_coupon_status){
                    $water_couponlist = Db::name('coupon_record')->alias('cr')
                        ->join('coupon coupon','cr.couponid = coupon.id')
                        ->where('cr.aid',aid)
                        ->where('cr.mid',mid)
                        ->where('cr.type','=',3)
                        ->where('coupon.dk_type','=',1)//使用线上核销抵扣类型
                        ->where('cr.status',0)
                        ->where('cr.limit_count','>',0)
                        ->where('cr.starttime','<=',time())
                        ->where('cr.endtime','>',time())
                        ->field('cr.*,coupon.isgive,coupon.fwtype,coupon.productids')
                        ->select()->toArray();
                    foreach($water_couponlist as $k=>$v){
                        if($v['fwtype']==2) {//指定商品可用
                            $productids = explode(',',$v['productids']);
                            if(!array_intersect($proids,$productids)){
                                continue;
                            }
                            $v['productids'] =$productids;
                        }
                        if($v['type'] ==3){
                            //次数耗尽
                            if($v['limit_count']<=0 || ($v['limit_count'] - $v['used_count'] <=0))continue;
                            //每天核销的次数
                            if($v['limit_perday'] >0){
                                //核销数量
                                $dayhxnum =\app\common\Coupon::getTimesCouponHxnum(aid,$v);
                                //剩余可抵扣数量
                                $sy_perdaylimit = $v['limit_perday'] - $dayhxnum;
                                if($sy_perdaylimit <=0)continue;
                                $v['sy_limit_perday'] = $sy_perdaylimit<=0?0: $sy_perdaylimit;
                            }
                        }
                        if($v['bid'] > 0){
                            $binfo = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->find();
                            $v['bname'] = $binfo['name'];
                        }
                        $new_water_couponlist[] =  $v;
                    }
                }
                $water_couponlist = $new_water_couponlist;
            }

			//促销活动
			$cuxiaolist = Db::name('cuxiao')->where('aid',aid)->where('bid',$bid)->where("(type in (1,2,3,4) and minprice<=".($price - $manjian_money).") or ((type=5 or type=6) and minnum<=".$totalnum.") ")->where('starttime','<',time())->where('endtime','>',time())->order('sort desc')->select()->toArray();
			$newcxlist = [];
			foreach($cuxiaolist as $k=>$v){
				$gettj = explode(',',$v['gettj']);
				if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
					continue;
				}
				if($v['fwtype']==2){//指定商品可用
					$productids = explode(',',$v['productids']);
					if(!array_intersect($proids,$productids)){
						continue;
					}
					if($v['type']==1 || $v['type']==2 || $v['type']==3 || $v['type']==4){//指定商品是否达到金额要求
						$thistotalprice = 0;
						foreach($buydata['prodata'] as $k2=>$v2){
							$product = $v2['product'];
							if(in_array($product['id'],$productids)){
								$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
							}
						}
						if($thistotalprice < $v['minprice']){
							continue;
						}
					}
					if($v['type']==6 || $v['type']==5){//指定商品是否达到件数要求
						$thistotalnum = 0;
						foreach($buydata['prodata'] as $k2=>$v2){
							$product = $v2['product'];
							if(in_array($product['id'],$productids)){
								$thistotalnum += $v2['num'];
							}
						}
						if($thistotalnum < $v['minnum']){
							continue;
						}
					}
				}
				if($v['fwtype']==1){//指定类目可用
					if(!$v['categoryids']) continue;
					$categoryids = explode(',',$v['categoryids']);
					$clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
					foreach($clist as $kc=>$vc){
						$categoryids[] = $vc['id'];
						$cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
						if($cate2) $categoryids[] = $cate2['id'];
					}
					if(!array_intersect($cids,$categoryids)){
						continue;
					}
					if($v['type']==1 || $v['type']==2 || $v['type']==3 || $v['type']==4){//指定商品是否达到金额要求
						$thistotalprice = 0;
						foreach($buydata['prodata'] as $k2=>$v2){
							$product = $v2['product'];
							if(array_intersect(explode(',',$product['cid']),$categoryids)){
								$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
							}
						}
						if($thistotalprice < $v['minprice']){
							continue;
						}
					}
					if($v['type']==6 || $v['type']==5){//指定类目内商品是否达到件数要求
						$thistotalnum = 0;
						foreach($buydata['prodata'] as $k2=>$v2){
							$product = $v2['product'];
							if(array_intersect(explode(',',$product['cid']),$categoryids)){
								$thistotalnum += $v2['num'];
							}
						}
						if($thistotalnum < $v['minnum']){
							continue;
						}
					}
				}
				if($v['type']==4 || $v['type']==5){
					if($v['fwtype']==2) {
					    //商品
                        $cuxiaomoney = 0;
                        $prozkArr = array_combine(explode(',', $v['productids']), explode(',', $v['prozk']));
                        $pronumArr = array_combine(explode(',', $v['productids']), explode(',', $v['pronum']));
                        foreach ($buydata['prodata'] as $k2 => $v2) {
                            $product = $v2['product'];
                            if ($prozkArr[$product['id']]) {
                                $prozk = $prozkArr[$product['id']];
                            } elseif (isset($prozkArr[$product['id']])) {
                                $prozk = $v['zhekou'];
                            } else {
                                $prozk = 10;
                            }
                            if ($v['type'] == 5 && $pronumArr[$product['id']] && intval($pronumArr[$product['id']]) > $v2['num']) {
                                $prozk = 10;
                            }
                            $cuxiaomoney += $product_priceArr[$k2] * (1 - $prozk * 0.1);
                        }
                    }elseif($v['fwtype']==1) {
					    //分类
					    $categoryPrice = 0;
                        foreach ($buydata['prodata'] as $k2 => $v2) {
                            $product = $v2['product'];
                            $cids2 = explode(',', $product['cid']);
                            if(array_intersect($cids2, $categoryids)) {
                                $categoryPrice += $v2['guige']['sell_price'] * $v2['num'];
                            }
                        }
                        $cuxiaomoney = $categoryPrice * (1 - $v['zhekou'] * 0.1);
					}else{
					    //全部
						$cuxiaomoney = $price * (1 - $v['zhekou'] * 0.1);
					}
					$v['cuxiaomoney'] = round($cuxiaomoney,2);
				}
                if(getcustom('plug_tengrui')) {
                    $tr_check = new \app\common\TengRuiCheck();
                    //判断是否是否符合会员认证、会员关系、一户
                    $check_cuxiao = $tr_check->check_cuxiao($this->member,$v);
                    if($check_cuxiao && $check_cuxiao['status'] == 0){
                        continue;
                    }
                }
                // 单独价格
                if(getcustom('yx_cuxiao_dandu_price')){
                    if($v['type']==6 && $v['fwtype']==2) {
                        //商品
                        $is_dandu = 0;
                        $dandumoneyall = 0;
                        $propriceArr = array_combine(explode(',', $v['productids']), explode(',', $v['proprice']));
                        $pronumArr = array_combine(explode(',', $v['productids']), explode(',', $v['pronum']));

                        foreach ($buydata['prodata'] as $k2 => $v2) {
                            $product = $v2['product'];
                            if (($propriceArr[$product['id']] || $propriceArr[$product['id']] === '0') && intval($pronumArr[$product['id']]) <= $v2['num']) {
                                $dandumoney = $propriceArr[$product['id']] * $v2['num'];
                                $is_dandu = 1;
                            // } elseif (isset($propriceArr[$product['id']]) && intval($pronumArr[$product['id']]) <= $v2['num']) {
                            //     $dandumoney = $product_priceArr[$k2]-$v['money'];
                            } else {
                                $dandumoney =  $product_priceArr[$k2];
                            }
                            $dandumoneyall += $dandumoney;
                        }
                        $v['dandumoney'] = dd_money_format($dandumoneyall);
                        $v['is_dandu'] = $is_dandu;
                    }
                }
				$newcxlist[] = $v;
			}
            // 默认促销活动
            if(getcustom('yx_cuxiao_default') && !empty($newcxlist)){
                
                $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                if($shopset['cuxiao_default'] == 1){
                    
                    // 重新计算价格
                    foreach ($newcxlist as $ko => $vo) {
                        $real_money = $product_price;
                        if($vo['type']==1){
                            $real_money = dd_money_format($product_price -$vo['money'],2);
                        }
                        if($vo['type']==4 || $vo['type']==5){
                            $real_money = dd_money_format($product_price -$vo['cuxiaomoney'],2);
                        }
                        if($vo['type']==6){
                            if(getcustom('yx_cuxiao_dandu_price') && $vo['type']==6 && $vo['fwtype']==2){
                                $real_money = dd_money_format($vo['dandumoney'],2);
                            }else{
                                $real_money = dd_money_format($product_price -$vo['cuxiaomoney'],2);
                            }
                            
                        }
                        $newcxlist[$ko]['real_money'] = $real_money;
                        
                    }
                    // 根据real_money从小到大排序
                    usort($newcxlist, function($a, $b) {
                        return $a['real_money'] - $b['real_money'];
                    });

                    // 默认最低价
                    $cxindex = 0;
                    $cxid = $newcxlist[0]['id'];
                }
            }

			if($extendInput){
				foreach($freightList as $fk=>$fv){
					$freightList[$fk]['formdata'] = array_merge($extendInput,$fv['formdata']);
				}
			}

            $invoice_price = 0;
			if($business['invoice'] && $business['invoice_rate'] > 0 && getcustom('invoice_rate')){
                $invoice_price = ($product_price + $freightList[0]['freight_price']) * $business['invoice_rate'] / 100;
            }

            if(getcustom('product_supply_chain')) {
                if ($product_type == 7) {
                    $supplier = Db::name('admin')->alias('a')->where('a.id', aid)->join('supplier s', 'a.supplier_id=s.id')->field('s.name,s.id')->find();
                    if ($supplier) {
                        $business['name'] = $business['name'] . '[' . $supplier['name'] . ']';
                    }
                    if ($trade_type == '1101'){
                        $business['name'] = $business['name'].'[保税]';
                    }else if ($trade_type == '1303'){
                        $business['name'] = $business['name'].'[海外直邮]';
                    }
                }
            }
            if(getcustom('yx_hongbao_queue_free')){
                if($is_use_youhui ==0){
                    $newcxlist = [];
                    $couponList = [];
                    $leveldk_money = 0;
                }
            }
			$allbuydata[$groupBid]['bid'] = $bid;
			$allbuydata[$groupBid]['business'] = $business;
			$allbuydata[$groupBid]['prodatastr'] = $prodatastr;
			if(getcustom('shop_product_jialiao')){
                $allbuydata[$groupBid]['jldata'] = $jldataArr;
            }
			$allbuydata[$groupBid]['couponList'] = $couponList;
			$allbuydata[$groupBid]['couponCount'] = count($couponList);
            if(getcustom('deposit')) {
                $allbuydata[$groupBid]['water_couponList'] = $water_couponlist;
                $allbuydata[$groupBid]['water_couponCount'] = count($water_couponlist);
                $allbuydata[$groupBid]['water_coupons'] = [];
                $allbuydata[$groupBid]['water_couponrids'] = [];
                $allbuydata[$groupBid]['water_couponkeys'] = [];
                $allbuydata[$groupBid]['water_coupon_money'] = 0;
                $allbuydata[$groupBid]['water_use_ck_coupon_status'] = Db::name('shop_sysset')->where('aid',aid)->value('use_ck_coupon_status');
                $allbuydata[$groupBid]['water_ck_mutiselect'] = Db::name('shop_sysset')->where('aid',aid)->value('multi_use_ck_coupon_status');
                $allbuydata[$groupBid]['is_have_deposit']  = $is_have_deposit;
            }
			$allbuydata[$groupBid]['freightList'] = $freightList;
			$allbuydata[$groupBid]['freightArr'] = $freightArr;
			$allbuydata[$groupBid]['extFreightPrice'] = $supplierFreight;//其他扩展运费
			$allbuydata[$groupBid]['product_price'] = round($product_price,2);
            $allbuydata[$groupBid]['invoice_price'] = round($invoice_price,2);
			$allbuydata[$groupBid]['leveldk_money'] = $leveldk_money;
			$allbuydata[$groupBid]['coupon_money'] = 0;
			$allbuydata[$groupBid]['coupontype'] = 1;
			$allbuydata[$groupBid]['couponrid'] = 0;
			$allbuydata[$groupBid]['couponrids'] = [];
			$allbuydata[$groupBid]['coupons'] = [];
			$allbuydata[$groupBid]['freightkey'] = 0;
			$allbuydata[$groupBid]['pstimetext'] = '';
			$allbuydata[$groupBid]['freight_time'] = '';
			$allbuydata[$groupBid]['storeid'] = 0;
			$allbuydata[$groupBid]['storename'] = '';
			$allbuydata[$groupBid]['cuxiaolist'] = $newcxlist;
			$allbuydata[$groupBid]['cuxiaoCount'] = count($newcxlist);
			$allbuydata[$groupBid]['cuxiao_money'] = 0;
			$allbuydata[$groupBid]['cuxiaotype'] = 0;
			$allbuydata[$groupBid]['cuxiaoid'] = 0;
			$allbuydata[$groupBid]['invoice_money'] = 0;
			$allbuydata[$groupBid]['editorFormdata'] = [];

			$allbuydata[$groupBid]['coupon_peruselimit'] = 1;
			if(getcustom('coupon_peruselimit')){
				$shopset = Db::name('shop_sysset')->where('aid',aid)->find();
				$allbuydata[$groupBid]['coupon_peruselimit'] = $shopset['coupon_peruselimit'];
			}
            if(getcustom('coupon_xianxia_buy') || getcustom('coupon_multiselect') || getcustom('coupon_auto_multi_select')){
                $allbuydata[$groupBid]['coupon_peruselimit'] =100;
            }
            if(getcustom('product_service_fee')){
                $allbuydata[$groupBid]['service_fee_price'] = round($service_fee_price,2);
                $allbuydata[$groupBid]['show_service_fee'] = $show_service_fee;
            }
            if(getcustom('money_dec')){
                $allbuydata[$groupBid]['money_dec_money'] = round($allbuydata[$groupBid]['money_dec_money'],2);
            }
            if(getcustom('freeze_money')){
                $allbuydata[$groupBid]['product_freezemoney_price'] = round($product_freezemoney_price,3);
            }
			$allproduct_price += $product_price;
            $allproduct_freezemoney_price = bcadd($allproduct_freezemoney_price,$product_freezemoney_price,2);
		}
        if(getcustom('shoporder_min_price')){
            $shopset = Db::name('shop_sysset')->where('aid',aid)->find();
            if($shopset['order_min_price']>0 && $allproduct_price < $shopset['order_min_price']){
                return json(['status'=>0,'msg'=>'订单金额低于最低金额'.$shopset['order_min_price']]);
            }
        }
		if(getcustom('plug_xiongmao')) {
            $admin = Db::name('admin')->where('id',aid)->find();
            if(in_array('order_change_price', explode(',',$admin['remark']))) {
                $order_change_price = true;
            }
        }
        if(getcustom('shop_yuding')){
            //再减去规格库存
            if($product['stock'] <=0  && $product['yuding_stock'] > 0){
                Db::name('shop_guige')->where('aid',aid)->where('id',$guige['id'])->update(['stock'=>Db::raw("stock-$num")]);
            }
        }
        $custom = [];
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['hasglassproduct'] = $glassProductNum>0?1:0;
        if(getcustom('product_glass_custom')){
		    $rdata['hasglasscustomproduct'] = $glassCustomProductNum>0?1:0;
        }
		$rdata['havetongcheng'] = $havetongcheng;
		$rdata['address'] = $address;
		$rdata['linkman'] = $address ? $address['name'] : strval($userinfo['realname']);
		$rdata['tel'] = $address ? $address['tel'] : strval($userinfo['tel']);
		if(!$rdata['linkman']){
			$lastorder = Db::name('shop_order')->where('aid',aid)->where('mid',mid)->where('linkman','<>','')->find();
			if($lastorder){
				$rdata['linkman'] = $lastorder['linkman'];
				$rdata['tel'] = $lastorder['tel'];
			}
		}
      
        $rdata['realname'] = isset($this->member['realname']) ? $this->member['realname'] : '';
     
		$rdata['userinfo'] = $userinfo;
		$rdata['allbuydata'] = $allbuydata;
		$rdata['bid'] = $bidGroupList?$bidGroupList[0]:0;
		$rdata['business_payconfirm'] = 0;
		if(getcustom('business_payconfirm') && $bid > 0 && count($allbuydata) == 1){
			$rdata['business_payconfirm'] = 1;
		}
		$rdata['needLocation'] = $needLocation;
		$rdata['scorebdkyf'] = Db::name('admin_set')->where('aid',aid)->value('scorebdkyf');//积分抵扣运费 1不抵扣，0抵扣
		$rdata['buy_selectmember'] = false;
		if(getcustom('buy_selectmember')){
			$canselect = Db::name('member_level')->where('aid',aid)->where('can_buyselect',1)->find();
			if($canselect) $rdata['buy_selectmember'] = true;
		}
		
        $rdata['show_other_order'] = false;
		if(getcustom('member_create_child_order')){
            $downmids = \app\common\Member::getteammids(aid,mid);
            if($userlevel['create_child_order_status'] ==1 && $downmids)$rdata['show_other_order'] = true;
        }                                                    
        $rdata['multi_promotion'] = $multi_promotion;
        $rdata['order_change_price'] = $order_change_price;
        $rdata['pstype3needAddress'] = false;
        if(getcustom('freight_pstype3_need_address')) {
            $rdata['pstype3needAddress'] = true;
        }

		$rdata['price_dollar'] = false;
		if(getcustom('price_dollar')){
			$usdrate = Db::name('shop_sysset')->where('aid',aid)->value('usdrate');
			if($usdrate>0){
				$rdata['usdrate'] =$usdrate;
			}
			$rdata['price_dollar'] = true;
		}
		$rdata['business_selfscore'] = $business_selfscore;
		$rdata['scoredkdataArr'] = $scoredkdataArr;
        if(getcustom('lock_money')){
            //冻结会员余额
            if($this->member['lock_money']==1){
                $moneydec = false;
            }
        }
        if(getcustom('money_dec')){
            $rdata['moneydec'] = $moneydec;
        }
        if(getcustom('shop_buy_worknum')){
        	$rdata['worknum_status'] = $worknum_status?true:false;//是否显示工号
        	$rdata['worknumtip']     = '请输入您的工号(必须为10位数字)';//
        	
        }
        if(getcustom('shopbuy_mendian_showtype')){
            //商城购买页门店搜索方式
            $shopbuy_mendian_showtype = Db::name('shop_sysset')->where('aid',aid)->value('shopbuy_mendian_showtype');
        	$rdata['mendiantype'] = $shopbuy_mendian_showtype;//2.6.4废弃
            // $rdata['mendianShowType'] = 1;
            $rdata['mendianShowType'] = $shopbuy_mendian_showtype;
        }
        if(getcustom('discount_code_zhongchuang')){
            //优惠代码
            $custom[] = 'discount_code_zc';
        }
        $rdata['custom'] = $custom;
        //如果开启了优惠不同享 折扣金额为0
        $rdata['coupon_not_used_discount'] = 0;
        if(getcustom('coupon_not_used_discount') ){
            $coupon_not_used_discount = Db::name('shop_sysset')->where('aid',aid)->value('coupon_not_used_discount');
            $rdata['coupon_not_used_discount'] = $coupon_not_used_discount;
        }
        if(getcustom('product_handwork')){
        	$rdata['ishand']    = 0;
        	$rdata['hwcontent'] = '';
        	if($protypes && $protypes[0] == 3){
        		$rdata['ishand'] = 1;
        		$hwset = Db::name('shop_sysset')->where('aid',aid)->field('hwname,hwcontent')->find();
        		$rdata['hwset'] = $hwset?$hwset:'';
        	}
        }
        if(getcustom('yx_order_discount_rand') && count($allbuydata) == 1){
            //随机立减
            $order_discount_rand = Db::name('order_discount_rand')->where('aid',aid)->where('bid',$bid)->find();
            if($order_discount_rand['status'] == 1 && $allproduct_price >= $order_discount_rand['order_price_min']){
                $order_discount_status = true;
                $order_discount_rand['order_types'] = explode(',',$order_discount_rand['order_types']);
                if(!in_array('all',$order_discount_rand['order_types']) && !in_array('shop',$order_discount_rand['order_types'])){
                    $order_discount_status = false;
                }
                $gettj = explode(',',$order_discount_rand['gettj']);
                if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
                    if(in_array('0',$gettj)){ //关注用户才能领
                        if($this->member['subscribe']!=1){
                            $order_discount_status = false;
                        }
                    }else{
                        $order_discount_status = false;
                    }
                }

                if($order_discount_status){
                    $rdata['order_discount_rand'] = [
                        'status'=>1,
                        'money_min'=>$order_discount_rand['money_min'],
                        'money_max'=>$order_discount_rand['money_max'],
                    ];
                }
            }
        }

		//分期商品
		$rdata['fenqi_data'] = [];
		$rdata['is_fenqi_product'] = 0;
		if(getcustom('shop_product_fenqi_pay') && $product_fenqi){
			$fenqi_data = json_decode($product_fenqi['fenqi_data'],true);
			$rdata['fenqi_data'] = $fenqi_data;
			$rdata['is_fenqi_product'] = 1;
		}
		$rdata['mendian_upgrade'] = false;
		if(getcustom('mendian_upgrade')){
			$admin =  Db::name('admin')->where('id',aid)->field('mendian_upgrade_status')->find();
			if($admin['mendian_upgrade_status']==1){
				$rdata['mendian_upgrade'] = true;
			}
			$rdata['needLocation'] = 1; // 门店升级功能开启定位
		}
        $designer_info = Db::name('designer_shopdetail')->where('aid',aid)->find();
        if($designer_info){
            $shopdetail_menudata = json_decode($designer_info['menudata'],true);
            if(getcustom('show_location') && getcustom('location_mendian')){
                if(!isset($shopdetail_menudata['isShowMd']) || !$shopdetail_menudata['isShowMd']){
                    $rdata['needlocation'] = false;
                }
            }
        }
		$mendian_no_select = 0;
		if(getcustom('mendian_no_select')){
		    //甘尔定制，不需要选择门店
            $mendian_no_select = 1;
        }
		$rdata['mendian_no_select'] = $mendian_no_select;

		$rdata['contact_require'] = $contact_require;
		$rdata['ismultiselect']=false;
		if(getcustom('coupon_multiselect')){
			$rdata['ismultiselect'] = true;
		}

		$rdata['show_product_xieyi'] = $show_product_xieyi;
		$rdata['product_xieyi'] = $product_xieyi;
		if(getcustom('supply_zhenxin')){
			//是否必须使用地址
			$mustuseaddress = false;
			if($havezxpro){
				$mustuseaddress = true;
			}
			//需要使用身份证
			$rdata['needusercard']       = $needusercard;
			$rdata['mustuseaddress']     = $mustuseaddress;
		}
		if(getcustom('supply_yongsheng')){
			//是否必须使用地址
			$mustuseaddress = $mustuseaddress?$mustuseaddress:false;
			if($haveyspro){
				$mustuseaddress = true;
			}
			$rdata['mustuseaddress']     = $mustuseaddress;
		}
        if(getcustom('member_goldmoney_silvermoney')){
            //商品是否有金银值抵扣数组
            $goldsilverlist = [];
            //银值
            if($ShopSendSilvermoney){
                if($allsilvermoneydec>0){
                    if($allsilvermoneydec>=$this->member['silvermoney']){
                        $allsilvermoneydec = $this->member['silvermoney']>0?$this->member['silvermoney']:0;
                    }
                    $allsilvermoneydec = round($allsilvermoneydec,2);
                }else{
                    $allsilvermoneydec = 0;
                }
                if($allsilvermoneydec>0) $goldsilverlist[]= ['name'=>t('银值'),'type'=>1,'value'=>$allsilvermoneydec];
            }
            //金值
            if($ShopSendGoldmoney){
                if($allgoldmoneydec>0){
                    if($allgoldmoneydec>=$this->member['goldmoney']){
                        $allgoldmoneydec = $this->member['goldmoney']>0?$this->member['goldmoney']:0;
                    }
                    $allgoldmoneydec = round($allgoldmoneydec,2);
                }else{
                    $allgoldmoneydec = 0;
                }
                if($allgoldmoneydec>0) $goldsilverlist[]= ['name'=>t('金值'),'type'=>2,'value'=>$allgoldmoneydec];
            }
            if($goldsilverlist){
                $goldsilverlist[] = ['name'=>'不使用','type'=>0,'value'=>0];
                //按金额重新排序
                $goldsilverlist2= array_column($goldsilverlist,'type');
                array_multisort($goldsilverlist2,SORT_ASC,$goldsilverlist);
            }else{
                $goldsilverlist = '';
            }
            $rdata['goldsilverlist'] = $goldsilverlist;
        }

        //满件包邮
        $rdata['full_piece_package'] = false;
        if(getcustom('freight_full_piece_package')){
            $rdata['full_piece_package'] = true;
        }

        //测评 判断当前购买商品product_type == 9
        $rdata['is_pingce'] = false;
        if(getcustom('product_pingce')){
            if($product['product_type'] == 9){
                $rdata['is_pingce'] = true;
            }
        }
        if(getcustom('member_dedamount')){
            $rdata['userinfo']['dedamount_dkmoney'] = round($userinfo['dedamount_dkmoney'],2);
            $rdata['usededamount'] = true;
        }
        if(getcustom('coupon_auto_multi_select')){
            $rdata['is_coupon_auto_multi'] = true;
        }
        if(getcustom('shop_giveorder')){
        	//赠送礼物功能
        	if(!$shopset){
        		$shopset = Db::name('shop_sysset')->where('aid', aid)->find();
        	}
        	//赠送有效时间
        	$rdata['giveorder_validtime'] = $shopset['giveorder_validtime'];
        	if($rdata['giveorder_validtime']<=0){
        		$giveorder = 0;
        	}else{
        		$giveorder = $shopset['giveorder'];
        	}
        	$giveorder = $giveorder??0;//是否开启赠送 0未开启 1开启
        	if($autofahuo == 4) $giveorder = 0;//在线卡密不支持
        	if(count($bidList)>1){
        		$giveorder = 0;//分开下单不支持
        	}
        	$rdata['giveorder'] = $giveorder;//是否开启赠送 0未开启 1开启
        }
        $rdata['mendian_change'] = true;
        if (getcustom('mendian_upgrade') && getcustom('member_change_mendian')){
            $mendian_upgrade_status = Db::name('admin')->where('id',aid)->value('mendian_upgrade_status');
            $changemendian_status = Db::name('mendian_sysset')->where('aid',aid)->value('changemendian_status');
            if($mendian_upgrade_status == 1 && $changemendian_status == 0){
                $mdid = Db::name('member')->where('aid',aid)->where('id',mid)->value('mdid');
                if($mdid){
                    $rdata['mendian_change'] = false;
                }
            }
        }
        if(getcustom('shop_buy_ad')){
            //下单页面顶部广告 商城-系统设置-下单页面顶部广告
            $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
            $rdata['shop_buy_ad'] = true;
            $rdata['createorder_toppic'] = $shopset['createorder_toppic'] ?? '';
            $rdata['createorder_toppic_tourl'] = $shopset['createorder_toppic_tourl'] ?? '';
        }
        $rdata['mendian_sort'] = false;
        if(getcustom('mendian_sort')){
            $rdata['mendian_sort'] = true;
        }
        if(getcustom('product_chinaums_subsidy')){
            $rdata['subsidy_verify'] = $subsidyVerify;
            $rdata['subsidy_tips'] = $subsidyTips;
        }
        $rdata['shop_buy_score_default_deduction'] = false;
        if(getcustom('shop_buy_score_default_deduction')){
            $rdata['shop_buy_score_default_deduction'] = true;
            $rdata['shop_buy_score_default_deduction_status'] = $adminset['shop_buy_score_default_deduction'];
        }
         $rdata['dandu_price'] = false;
        if(getcustom('yx_cuxiao_dandu_price')){
           $rdata['dandu_price'] = true;
        }
        if(getcustom('yx_cuxiao_default') && isset($cxid)){
           $rdata['cxid'] = $cxid;
           $rdata['cxindex'] = $cxindex??0;
        }

        $rdata['buy_message_prompt_status'] = false;
        if(getcustom('shop_buy_message_prompt')){
            $shopset = Db::name('shop_sysset')->where('aid', aid)->field('buy_message_prompt_status,buy_message_prompt_content')->find();
            if($shopset['buy_message_prompt_status'] == 1 && $shopset['buy_message_prompt_content']){
                $rdata['buy_message_prompt_status'] = true;
                $rdata['buy_message_prompt_content'] = $shopset['buy_message_prompt_content'] ?? '';
            }
        }

        if(getcustom('shopbuy_sign')){
            if($product['bid']>0){
                $sign_set = Db::name('business')->where('aid', aid)->where('id', $bid)->field('sign_contract,sign_contract_template,sign_contract_content,sign_contract_name')->find();
                $b_auth_data = Db::name('admin_user')->where('aid',aid)->where('bid',$bid)->field('auth_type,auth_data')->find();
                $b_auth_data_arr = json_decode($b_auth_data['auth_data'],true);
                if(!in_array('ShopbuySign',$b_auth_data_arr && $b_auth_data=='all')){
                    $sign_set['sign_contract'] == 0;
                }
            }else{
                $sign_set = Db::name('shop_sysset')->where('aid', aid)->field('sign_contract,sign_contract_template,sign_contract_content,sign_contract_name')->find();
            }
            if($sign_set['sign_contract'] == 1 && $sign_set['sign_contract_template']){
                $rdata['shopbuy_sign'] = true;
                $rdata['sign_contract_content'] = $sign_set['sign_contract_content'] ?? '';
                $rdata['sign_contract_name'] = $sign_set['sign_contract_name'] ?? '下单合同';
            }
        }

		return $this->json($rdata);          
	}
	public function createOrder(){
		$this->checklogin();
		$sysset = Db::name('admin_set')->where('aid',aid)->find();
		if(getcustom('sysset_scoredkmaxpercent_memberset')){
            //处理会员单独设置积分最大抵扣比例
            $sysset['scoredkmaxpercent'] = $this->sysset['scoredkmaxpercent'] = \app\custom\ScoredkmaxpercentMemberset::dealmemberscoredk(aid,$this->member,$sysset['scoredkmaxpercent']);
        }
		$post = input('post.');
        $score_weishu = $this->score_weishu;
        if(getcustom('discount_code_zhongchuang') && $post['discount_code_zc']){
            $rs = $this->checkDiscountCodeZc($post['discount_code_zc']);
            $rs = $rs->getData();
            if($rs['status'] != 1) {$post['discount_code_zc'] = '';}
        }
        $ordermid = mid;
        if(getcustom('member_create_child_order')){
            $teammid = input('param.teammid/d');
            if($teammid){
                $ordermid = $teammid;
                $this->member = Db::name('member')->where('id',$ordermid)->where('aid',aid)->find(); 
            }
        }

        $shopset = Db::name('shop_sysset')->where('aid',aid)->find();

        //赠好友功能，无需选择地址和配送方式
        $usegiveorder = input('?param.usegiveorder')?input('param.usegiveorder/d'):0;
        if(getcustom('shop_giveorder')){
            if($usegiveorder){
                //赠送礼物功能
                if($shopset['giveorder_validtime']<=0){
                    $giveorder = 0;
                }else{
                    $giveorder = $shopset['giveorder'];
                }
                if($giveorder != 1){
                    return $this->json(['status'=>0,'msg'=>'赠送礼物功能未开启']);
                }
                $giveordertitle = input('?param.giveordertitle')?input('param.giveordertitle'):'';
                if(empty($giveordertitle)){
                    return $this->json(['status'=>0,'msg'=>'请填写赠送好友的分享标题']);
                }
                $giveorderpic  = input('?param.giveorderpic')?input('param.giveorderpic'):'';
            }
        }

		if(!$usegiveorder){
			//收货地址
			if($post['addressid']=='' || $post['addressid']==0){
				$address = ['id'=>0,'name'=>$post['linkman'],'tel'=>$post['tel'],'area'=>'','address'=>''];
			}else{
				$address = Db::name('member_address')->where('id',$post['addressid'])->where('aid',aid)->where('mid',mid)->find();
				if(!$address) return $this->json(['status'=>0,'msg'=>'所选收货地址不存在']);
			}
		}else{
			$address = ['id'=>0,'name'=>'','tel'=>'','area'=>'','address'=>''];
		}

		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();

        if(getcustom('product_thali') && $shopset['product_shop_school'] == 1 && ($address['product_thali_student_name']=='' || $address['product_thali_school'] =='')){
            return $this->json(['status'=>0,'msg'=>'请完善收货地址里的学校和学生信息']);
        }

		$buydata = $post['buydata'];
		$couponridArr = [];
        $water_couponridArr = [];
        $orderBids = [];
        if(getcustom('yx_hongbao_queue_free')){
            $is_use_youhui = 1;
            $hongbao_queue_set = Db::name('hongbao_queue_free_set')->where('aid',aid)->field('productids,gettj')->find();
            $hongbao_join_proids = $hongbao_queue_set['productids'];
            $hongbao_join_proids_arr = explode(',',$hongbao_join_proids);
            $hongbao_gettj = explode(',',$hongbao_queue_set['gettj']);
        }

        if(getcustom('member_shopscore')){
        	$membershopscoreauth = false;
            //查询权限组
            $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            //如果开启了产品积分权限
            if($user['auth_type'] == 1){
                $membershopscoreauth = true;
            }else{
                $admin_auth = json_decode($user['auth_data'],true);
                if(in_array('MemberShopscoreAuth,MemberShopscoreAuth',$admin_auth)){
                    $membershopscoreauth = true;
                }
            }
            //如果商城商品积分开启了，且使用产品积分，且设置最大可抵扣比例，则赋值
            if($membershopscoreauth && $sysset['shopscorestatus'] == 1 && $post['useshopscore']== 1 && ($sysset['shopscoredkmaxpercent'] >0 && $sysset['shopscoredkmaxpercent']<=100)){
                $member_shopscore        = $this->member['shopscore'];
                $member_shopscoredk_money = floor($member_shopscore * $sysset['shopscore2money'] *100)/100;

                $shopscore2money      = $sysset['shopscore2money'];
                $shopscoredkmaxpercent= $sysset['shopscoredkmaxpercent'];

                $shopscoremaxtype = 0;//0系统设置比例 1独立设置
                $useshopscore = true;
            }else{
            	$useshopscore = false;
            }
        }
		foreach($buydata as $data){ //判断有没有重复选择的优惠券
            if($data['bid']>0) $orderBids[] = $data['bid'];
            if(getcustom('deposit')){
                if($data['water_couponrid']){
                    $water_couponrids = explode(',',$data['water_couponrid']);
                    foreach($water_couponrids as $couponrid){
                        if(in_array($couponrid,$water_couponridArr)){
                            return $this->json(['status'=>0,'msg'=>t('优惠券').'不可重复使用']);
                        }
                        $water_couponridArr[] = $couponrid;
                    }  
                }
            }
			if(!$data['couponrid']) continue;
			$couponrids = explode(',',$data['couponrid']);
			if(getcustom('coupon_xianxia_buy') || getcustom('coupon_multiselect') || getcustom('coupon_auto_multi_select')){
                $shopset['coupon_peruselimit'] =100;
            }
		
			if($shopset['coupon_peruselimit'] < count($couponrids)){
				return $this->json(['status'=>0,'msg'=>'最多可用'.$shopset['coupon_peruselimit'].'张'.t('优惠券')]);
			}
			foreach($couponrids as $couponrid){
				if(in_array($couponrid,$couponridArr)){
					return $this->json(['status'=>0,'msg'=>t('优惠券').'不可重复使用']);
				}
				$couponridArr[] = $couponrid;
			}
		}

		$usercard = $post['usercard']?trim($post['usercard']):'';

		if(getcustom('ciruikang_fenxiao')){
			//是否开启了商城商品需上级购买足量
			$open_product_parentbuy = Db::name('admin_set')->where('aid', aid)->value('open_product_parentbuy');
		}
		if(getcustom('supply_zhenxin')){
			$zxblance     = -1;//甄新汇选账号余额
			$zxallprice   = 0;//甄新汇选商品总成本价格(包含服务费和配送费)
		}
        if(getcustom('supply_zhenxin') || getcustom('supply_yongsheng')){
            //判断是不是甄新汇选、永盛商品 是的话需要提前验证
            foreach($buydata as $key=>$data){
                $bidGroupArr = explode('_',$data['bidGroup']);
                $bid = $data['bid'];
                if($bidGroupArr[1] && ($bidGroupArr[1] == 'supplyzhenxin' || $bidGroupArr[1] == 'supplyyongsheng')){
                    //提前检验订单商品
                    if(!$data['prodata']) return $this->json(['status'=>0,'msg'=>'产品数据错误']);
                    $prodata = explode('-',$data['prodata']);

                    $params = [];
                    if(getcustom('member_create_child_order')){
                        $params['teammid'] = input('param.teammid')?input('param.teammid/d'):'';
                    }
                    if(getcustom('product_pickup_device')){
                        $params['devicedata'] = input('param.devicedata')?input('param.devicedata'):'';
                    }
                    if(getcustom('ciruikang_fenxiao')){
                        $params['open_product_parentbuy'] = $open_product_parentbuy;
                    }
                    $params['usegiveorder'] = $usegiveorder;

                    if(!$usegiveorder){
                        if(!$address) return $this->json(['status'=>0,'msg'=>'请选择地址']);
                        //必须有姓名、电话、地址
                        if(empty($address['name']) || empty($address['tel']) || empty($address['province']) || empty($address['city']) || empty($address['district']) || empty($address['address'])){
                            return $this->json(['status'=>0,'msg'=>'此订单必须填写姓名、电话、省市区地址']);
                        }
                    }
                    if($bidGroupArr[1] == 'supplyzhenxin'){
                        if(getcustom('supply_zhenxin')){
                            $customfreight = 1;
                            $data['zxpostage'] = 0;//此组合运费

                            $params['usercard']=$usercard;
                            $params['address']=$address;
                            $params['zxallprice']=$zxallprice;
                            $params['zxblance']=$zxblance;
                            $checkorderproduct = \app\custom\SupplyZhenxinCustom::checkorderproduct(aid,$bid,mid,$this->member,$prodata,$data,$params);
                            if(!$checkorderproduct || $checkorderproduct['status'] != 1){
                                $msg = $checkorderproduct && $checkorderproduct['msg']?$checkorderproduct['msg']:'订单信息错误';
                                return $this->json(['status'=>0,'msg'=>$msg]);
                            }
                            $data = $checkorderproduct['data'];
                            $buydata[$key]['zxpostage'] = $data['zxpostage'];//甄新汇选商总运费
                            $buydata[$key]['zxguige']   = $data['zxguige'];//甄新汇选规格信息
                            $params = $checkorderproduct['params'];
                            $zxblance     = $params['zxblance'];//甄新汇选账号余额
                            $zxallprice   = $params['zxallprice'];//甄新汇选商品总成本价格(包含服务费)
                        }
                    }else if($bidGroupArr[1] == 'supplyyongsheng'){
                        if(getcustom('supply_yongsheng')){
                            $checkorderproduct = \app\custom\SupplyYongsheng::checkorderproduct(aid,$bid,mid,$this->member,$prodata,$data,$address,$params);
                            if(!$checkorderproduct || $checkorderproduct['status'] != 1){
                                $msg = $checkorderproduct && $checkorderproduct['msg']?$checkorderproduct['msg']:'订单信息错误';
                                return $this->json(['status'=>0,'msg'=>$msg]);
                            }
                            $data  = $checkorderproduct['data'];
                            $params = $checkorderproduct['params'];
                        }
                    }
                }
            }
        }
        //判断商户是否过期
        if($orderBids){
            $time = time();
            $expireBusiness = Db::name('business')->where('id','in',$orderBids)->where("(endtime>0 && endtime<{$time}) OR status<>1")->field('id,name')->select()->toArray();
            if($expireBusiness){
                $businessNames = implode(',',array_column($expireBusiness,'name'));
                return $this->json(['status'=>0,'msg'=>'['.$businessNames.']商户状态不可下单']);
            }
        }

		$ordernum = \app\common\Common::generateOrderNo(aid);
        $tongzhengdkmaxmoney = 0;
        $tongzhengmaxtype = 0; //0按系统设置 1商品独立设置
		$i = 0;
		$alltotalprice = 0;
		$alltotalscore = 0;
		$alltotaltongzheng = 0;
        $total_freezemoney_price = 0;//冻结资金支付
        $cat_buynum=[];

        if(getcustom('member_level_moneypay_price')){
            $alltotalputongprice = 0;//所有商品普通价格总价
        }

        //满减活动
        $mjset = Db::name('manjian_set')->where('aid',aid)->find();
        if($mjset && $mjset['status']==1){
            $mjdata = json_decode($mjset['mjdata'],true);
            //指定分类
            if($mjset['fwtype']==1){
                //指定分类或商品分类不存在
                if(empty($mjset['categoryids'])){
                    $mjdata = array();
                }
            //指定商品
            }else if($mjset['fwtype']==2){
                if(empty($mjset['productids'])){
                    $mjdata = array();
                }
            }
            if(getcustom('plug_tengrui')) {
                if($mjdata){
                    $tr_check = new \app\common\TengRuiCheck();
                    //判断是否是否符合会员认证、会员关系、一户、用户组，不符合则直接去掉
                    $check_manjian = $tr_check->check_manjian($this->member,$mjset);
                    if($check_manjian && $check_manjian['status'] == 0){
                        $mjdata = array();
                    }
                    $manjian_tr_roomId = $check_manjian['tr_roomId'];
                }
            }
        }else{
            $mjdata = array();
        }

		$business_selfscore = 0;
		if(getcustom('business_selfscore')){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			if($bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
				$business_selfscore = 1;
			}
		}

        if(getcustom('member_goldmoney_silvermoney')){
        	$ShopSendGoldmoney   = true;//赠送金值权限
            $ShopSendSilvermoney = true;//赠送银值权限
            //平台权限
            $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user){
                if($admin_user['auth_type'] !=1 ){
                    $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                    if(!in_array('ShopSendGoldmoney,ShopSendGoldmoney',$admin_auth)){
                        $ShopSendGoldmoney   = false;
                    }
                    if(!in_array('ShopSendSilvermoney,ShopSendSilvermoney',$admin_auth)){
                        $ShopSendSilvermoney = false;
                    }
                }
            }
            //是否使用金银值抵扣
            $goldsilvertype = input('?param.goldsilvertype')?input('param.goldsilvertype/d'):0;
            if($goldsilvertype >0){
                $silvermoney = $this->member['silvermoney'];//用户银值
                $goldmoney   = $this->member['goldmoney'];//用户金值
            }
        }
        if(getcustom('shop_product_commission_memberset')){
			$ShopProductCommissionMemberSet = true;//填写分销员ID权限
            //平台权限
            if(!$admin_user){
            	$admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            }
            if($admin_user['auth_type'] !=1 ){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if(!in_array('ShopProductCommissionMemberSet,ShopProductCommissionMemberSet',$admin_auth)){
                    $ShopProductCommissionMemberSet = false;
                }
            }
        }

        if(getcustom('member_dedamount')){
            $dedamount = $this->member['dedamount'];//会员抵扣金额
            $dedamount_dkpercent = $sysset['dedamount_dkpercent']??0;//抵扣比例
        }
        if(getcustom('extend_planorder')){
        	//排单系统店铺ID
            $poshopid = input('?param.poshopid')?input('param.poshopid/d'):0;
        }
        $is_mendian_usercetner = 0;
        if(getcustom('mendian_usercenter') && $this->member['lock_mdid']){
            //门店中心下单
            $is_mendian_usercetner = 1;
        }

        $businessCount = 0;//多商户数量
        $businessPayCount = 0;//多商户独立支付数量
        $platformCount = 0;//平台数量

		foreach($buydata as $data){
			$scoredkmaxmoney = 0;//商家累加积分抵扣
			$scoremaxtype = 0; //0按系统设置 1商品独立设置

			$bidGroupArr = explode('_',$data['bidGroup']);
            $productTypes = [];
            $productType = 0;
            $tradeType = 0;

            $protypes=[];//存在的商品类型
			$i++;
			$bid = $data['bid'];
			if($data['prodata']){
				$prodata = explode('-',$data['prodata']);
			}else{
				return $this->json(['status'=>0,'msg'=>'产品数据错误']);
			}
			$product_priceArr = [];
			$product_price  = 0;//当前会员购买价格
			$balance_price = 0;
			$needzkproduct_price = 0;
			$givescore = 0; //奖励积分 确认收货后赠送
			$givescore2 = 0; //奖励积分2 付款后赠送
			$totalweight = 0;//重量
			$totalnum = 0;
			$prolist = [];
			$proids = [];
			$cids = [];
			$cids2 = [];
			$invoice = [];
            $givetongzheng = 0; //奖励通证 确认收货后赠送
            $give_withdraw_score = 0; //提现积分 确认收货后赠送
            $give_parent1_withdraw_score = 0; //直推赠提现积分 确认收货后赠送
            if(getcustom('deposit')){
                $deposit_payprice = 0;//需要购买的押金金额
            }
            if(getcustom('member_commission_max')){
                $give_commission_max = 0; //奖励佣金上限 确认收货后赠送
                $give_commission_max2 = 0; //奖励佣金上限 付款后赠送
            }
            if(getcustom('pay_yuanbao')) {
                $total_yuanbao   = 0;//总元宝价格
                $have_no_yuanbao = 0;//是否有非元宝商品
            }
            if(getcustom('consumer_value_add')){
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
            if(getcustom('member_level_moneypay_price')){
                $product_price_cha = 0;//普通价格与会员购买价格的差额
            }

            if($bid){
                $businessCount ++;
                $store_info = Db::name('business')->where('aid',aid)->where('id',$bid)->find();
                if(($store_info['wxpayst'] == 1 && $store_info['wxpay_submchid']) || $store_info['sxpay_mno']){
                    $businessPayCount ++;
                }
            }
            else{
                $store_info = Db::name('admin_set')->where('aid',aid)->find();
                $platformCount ++;
            }

            $store_name = $store_info['name'];

			$fids = [];
			$customfreight = 0;//自定义快递 0：默认快递 1：自定义普通快递
			if(getcustom('supply_zhenxin')){
				//判断是不是甄新汇选商品
				if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
					$customfreight = 1;
				}
			}
			if(getcustom('shop_giveorder')){
				//赠送好友
				if($usegiveorder){
					$customfreight = 2;
				}
			}
			if($customfreight == 0){
				$freightwhere = [['status','=',1],['aid','=',aid],['bid','=',$bid]];
				if(getcustom('supply_yongsheng')){
					//判断是不是永盛商品，只能普通快递
					if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
						$freightwhere[] = ['pstype','=',0];
					}
				}
				$freightList = \app\model\Freight::getList($freightwhere);
				foreach($freightList as $v){
					$fids[] = $v['id'];
				}
			}

			$extendInput = [];
            $productLimit = [];
            //符合满减的商品价格
            $mj_price = 0;
			$totalweight_price = 0;
            $productNums = 0;
            if(getcustom('product_service_fee')) {
                $serviceFee = 0;
                $serviceFeeNums = 0;
            }
            if(getcustom('member_goldmoney_silvermoney')){
                $allsilvermoneydec = 0;//银值抵扣数额
                $allgoldmoneydec   = 0;//金值抵扣数额
            }
            if(getcustom('freeze_money')){
                $freezemoney_price = 0;
            }
            $hexiao_num_total = 0;
			Db::startTrans();
			$product_bonus = [];

			if(getcustom('member_dedamount')){
                $paymoney_givemoney = 0;//商家让利部分总金额
                $dedamount_dkmoney  = 0;//抵扣金抵扣总金额
            }
            if(getcustom('member_shopscore')){
	            $shopscoredk_money = 0;//该商家产品抵扣总金额
	        }
	        if(getcustom('money_dec')){
            	$money_dec_type = 0;//抵扣类型 0最大百分比 1最大抵扣金额
                $money_dec_rate = 0;//最大抵扣比例
                if(empty($bid)){
                    $adminset = Db::name('admin_set')->where('aid',aid)->find();
                    if($adminset && $adminset['money_dec'] && $adminset['money_dec_rate']>0){
                        $money_dec_rate = $adminset['money_dec_rate'];
                    }
                }else{
                    //查询商户余额抵扣比例
                    $business = Db::name('business')->where(['aid'=>aid,'id'=>$bid])->field('money_dec,money_dec_rate')->find();
                    if($business && $business['money_dec'] && $business['money_dec_rate']>0){
                        $money_dec_rate = $business['money_dec_rate'];
                    }
                }
            }
            if(getcustom('product_chinaums_subsidy')){
                //优惠码
                $discount_code = '';
            }
			if(getcustom('business_hexiaoplatform_ticheng')){
				$businessHxMoney = 0;
			}
			if(getcustom('shop_product_commission_memberset')){
	        	//商品分销员id集合
	            $procommissionmids = $data['procommissionmids']??[];
	        }
            if(getcustom('deposit')){
                $deposit_buy_list = [];//需要购买的
                $deposit_member_list = [];//需要扣除的
                $deposit_dk_list = [];
            }
            $give_farmseed = 0;
            $give_farmseed2 = 0;
			foreach($prodata as $key=>$pro){
				$sdata = explode(',',$pro);
				$num = $sdata[2] = intval($sdata[2]);
				if($sdata[2] <= 0) return $this->json(['status'=>0,'msg'=>'购买数量有误']);
				$product = Db::name('shop_product')->where('aid',aid)->where('ischecked',1)->where('bid',$bid)->where('id',$sdata[0])->find();
				if(!$product) return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
				if(getcustom('product_xieyi')){
				    if($product['xieyi_id'] && empty(input('isagree_pro'))){
				        $xieyi_name = Db::name('product_xieyi')->where('id',$product['xieyi_id'])->value('name');
                        return $this->json(['status'=>0,'msg'=>'请先阅读并同意《'.$xieyi_name.'》']);
                    }
                }
				if(getcustom('yx_butie_activity',aid)){
                    //消费补贴检测是否有未完成的活动商品，未完成，不可复购
                    $check = \app\custom\ButieActivity::checkButieProductOne(aid,$this->member,$product);
                    if($check['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>$check['msg']]);
                    }
                }
				if($product['status']==0){
					return $this->json(['status'=>0,'msg'=>'商品未上架']);
				}
				if($product['status']==2 && (strtotime($product['start_time']) > time() || strtotime($product['end_time']) < time())){
					return $this->json(['status'=>0,'msg'=>'商品未上架']);
				}
				if($product['status']==3){
					$start_time = strtotime(date('Y-m-d '.$product['start_hours']));
					$end_time = strtotime(date('Y-m-d '.$product['end_hours']));
					if(($start_time < $end_time && ($start_time > time() || $end_time < time())) || ($start_time >= $end_time && ($start_time > time() && $end_time < time()))){
						return $this->json(['status'=>0,'msg'=>'商品未上架']);
					}
				}
				//商品类型数组
				if(!in_array($product['product_type'],$protypes)){
					array_push($protypes,$product['product_type']);
				}

                if(getcustom('product_month_createorder_limit') && $product['month_createorder_limit_num'] > 0 && $shopset['product_month_createorder_switch'] == 1){
                    //常规商品购买下单频次，增加每月限购次数（自然月）
                    $firstDay = strtotime(date('Y-m-01').' 00:00:00');  // 当月第一天
                    $lastDay = strtotime(date('Y-m-t').' 23:59:59');   // 当月最后一天
                    //获取当月下单次数
                    $pro_ordre_num = Db::name('shop_order_goods')->where('aid',aid)->where('status','in',[1,2,3])->where('mid',mid)->where('createtime','between',[$firstDay,$lastDay])->where('proid',$product['id'])->group('orderid')->count();

                    if($pro_ordre_num >= $product['month_createorder_limit_num']){
                        return $this->json(['status'=>0,'msg'=>'商品['.$product['name'].']已达到每月限购次数']);
                    }
                }

				if(getcustom('shopcate_time')){
					$procids = explode(',',$product['cid']);
					$cate = Db::name('shop_category')->where('aid',aid)->where('id','in',$product['cid'])->select()->toArray();
					foreach($cate as $c){
						if($c['start_hours'] && $c['end_hours']){
							$catestart_time =  strtotime(date('Y-m-d '.$c['start_hours']));
							$cateend_time =  strtotime(date('Y-m-d '.$c['end_hours']));
							if(($catestart_time < $cateend_time && ($catestart_time > time() || $cateend_time < time())) || ($catestart_time >= $cateend_time && ($catestart_time > time() && $cateend_time < time()))){
								return $this->json(['status'=>0,'msg'=>'商品购买时间'.$c['start_hours'].'-'.$c['end_hours'].'请稍后再来']);
							}
						}
					}
				}
                if(getcustom('product_pingce')){                   
                    $productType = $product['product_type'];
                }
                if(getcustom('product_weight')){
                    $productTypes[] = $product['product_type'];
                    $productType = $product['product_type'];
                }
                if(getcustom('product_supply_chain')){
                    $productType = $product['product_type'];
                    $tradeType = $product['trade_type'];
                }
                if(getcustom('product_quanyi')){
                    $productTypes[] = $product['product_type'];
                    $productType = $product['product_type'];
                }
                if(getcustom('team_fenhong_yeji')){
                    //带分红或分销的产品不可与没设置奖励的产品一起购买
                    $is_bonus = 0;
                    if($product['commissionset']!=-1){
                        $is_bonus = 1;
                    }
                    if($product['fenhongset']!=0){
                        $is_bonus = 1;
                    }
                    $product_bonus[] = $is_bonus;
                    if(count(array_unique($product_bonus))>1){
                        return json(['status'=>0,'msg'=>'所选产品不可同时购买']);
                    }
                }
                if(getcustom('shop_product_commission_memberset')){
                	if($ShopProductCommissionMemberSet && $product['commission_memberset']){
                		if(!$procommissionmids) return $this->json(['status'=>0,'msg'=>'商品：'.$product['name'].'必须填写一个分销员ID']);
                		$procommissionmid = $procommissionmids[$key];
                		if(!$procommissionmid) return $this->json(['status'=>0,'msg'=>'商品：'.$product['name'].'必须填写一个分销员ID']);
                		//查询分销员是有分销权限
                		$countprocommissionmid = Db::name('member')->alias('m')
                			->join('member_level ml','ml.id = m.levelid')
                			->where('m.id',$procommissionmid)->where('ml.can_agent','>',0)->count('m.id');
                		if(!$countprocommissionmid)  return $this->json(['status'=>0,'msg'=>'商品：'.$product['name'].'所填写的分销员ID无分销权限']);
                		$product['procommissionmid'] = $procommissionmid;
                	}
		        }

				if($key==0) $title = removeEmoj($product['name']);
				
				$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$sdata[1])->lock(true)->find();
				if($is_mendian_usercetner){
				    //门店中心获取门店产品库存
                    $guige = \app\custom\MendianUsercenter::getProductSpec(aid,$this->member['lock_mdid'],$product['id'],$guige);
                }
				if(!$guige || $guige['aid'] != aid) return $this->json(['status'=>0,'msg'=>'产品规格不存在或已下架']);
                if(getcustom('shop_yuding')){
                    if($product['stock'] <=0  && $product['yuding_stock'] > 0){
                        Db::name('shop_guige')->where('aid',aid)->where('id',$guige['id'])->update(['stock'=>Db::raw("stock+$num")]);
                        $guige['stock']  = $num;
                    }
                }
				$isWdtStock = 0;
                if(getcustom('erp_wangdiantong') && $guige['wdt_status']==1){
                    $isWdtStock = 1;
                    $c = new \app\custom\Wdt(aid,$product['bid']);
                    $stock = $c->stockQueryBySpec($guige['barcode'],$guige['proid']);
                    if($stock < $sdata[2]){
                        return $this->json(['status'=>0,'msg'=>'库存不足']);
                    }
                }
				if(!$isWdtStock && $guige['stock'] < $sdata[2]){
                    if(getcustom('shop_stock_warning_notice')){
                        $this->tmpl_stockwarning($product,$guige,$guige['stock'],$product['id']);                        				
                    }
					return $this->json(['status'=>0,'msg'=>$product['name'].$guige['name'].'库存不足']);
				}
				//
                $is_check_gettj = true;
                if(getcustom('member_create_child_order')){
                    $teammid = input('param.teammid/d');
                    if($teammid){
                        $is_check_gettj = false;
                    }
                }
				$gettj = explode(',',$product['gettj']);
				if($is_check_gettj && !in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj) && (!in_array('0',$gettj) || $this->member['subscribe']!=1)){ //不是所有人
					if(!$product['gettjtip']) $product['gettjtip'] = '没有权限购买该商品';
					return $this->json(['status'=>0,'msg'=>$product['gettjtip'],'url'=>$product['gettjurl']]);
				}
                if(getcustom('member_realname_verify')){
                    if($product['realname_buy_status'] == 1 && $this->member['realname_status'] != 1){
                        return $this->json(['status'=>-4,'msg'=>'未实名不可购买['.$product['name'].']','url'=>'/pagesExt/my/setrealname']);
                    }
                }
                if(getcustom('shop_label')){
                    //是否有商品标签限制购买
                    if($product['labelid']){
                        $labelids = Db::name('shop_label')->where('id','in',$product['labelid'])->where('limitbuy',1)->where('aid',aid)->order('sort desc,id desc')->column('id');
                        if($labelids){
                            if(!$this->member['labelid']){
                                return $this->json(['status'=>0,'msg'=>'不符合购买商品'.$product['name'].'标签的条件']);
                            }
                            $haslabel = false;//是否有符合的标签
                            $mlabelids = explode(',',$this->member['labelid']);
                            foreach($mlabelids as $mv){
                                if(in_array($mv,$labelids)){
                                    $haslabel = true;
                                }
                            }
                            if(!$haslabel){
                                return $this->json(['status'=>0,'msg'=>'不符合购买商品'.$product['name'].'标签的条件']);
                            }
                            unset($mv);
                        }
                    }
                }
				if($product['perlimit'] > 0){
                    $refundOrderWhere = [];
                    $orderGoodsWhere = [];
                    $perLimitMsg = '['.$product['name'].'] 每人限购'.$product['perlimit'].'件';
                    if(getcustom('product_per_limit_buy')){
                        //0天代表不限制
                        if($product['perlimitday'] > 0){
                            //0 - 23:59:59
                            $limitStartTime = strtotime(date('Y-m-d', strtotime("-" . ($product['perlimitday'] - 1) . " days")));
                            $limitEndTime = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
                            $refundOrderWhere[] = ['srog.createtime','between',[$limitStartTime,$limitEndTime]];
                            $orderGoodsWhere[] = ['createtime','between',[$limitStartTime,$limitEndTime]];
                        }
                    }
					$buynum = $sdata[2] + Db::name('shop_order_goods')->where('aid',aid)->where('mid',$ordermid)->where('proid',$product['id'])->where('status','in','0,1,2,3')->where($orderGoodsWhere)->sum('num');
                    $refund_num = Db::name('shop_refund_order_goods')->alias('srog')
                    ->join('shop_refund_order so','so.id  = srog.refund_orderid')->where('srog.aid',aid)->where('srog.mid',mid)->where('srog.proid',$product['id'])->where('so.refund_type','return')->where('so.refund_status','in','2')->where($refundOrderWhere)->sum('refund_num');
                    $buynum = $buynum - $refund_num;
					if($buynum > $product['perlimit']){
						return $this->json(['status'=>0,'msg'=>$perLimitMsg]);
					}else{
                        if(isset($productLimit[$product['id']])){
                            $productLimit[$product['id']]['buyed'] += $buynum-$sdata[2];;
                            $productLimit[$product['id']]['buy'] += $num;
                        }else{
                            $productLimit[$product['id']]['buyed'] = $buynum-$sdata[2];;
                            $productLimit[$product['id']]['buy'] = $num;
                            $productLimit[$product['id']]['perlimit'] = $product['perlimit'];
                            $productLimit[$product['id']]['name'] = $product['name'];
                        }
                    }
				}
                if($guige['limit_start'] > 0 && $sdata[2] < $guige['limit_start']){
                    return $this->json(['status'=>0,'msg'=>'['.$product['name'].']['.$guige['name'].'] '.$guige['limit_start'].'件起售']);
                }
				$limit_start_state = 1;
				if(getcustom('product_wholesale') && $product['product_type'] == 4){
					$jieti_discount_type = $product['jieti_discount_type'];
					if($jieti_discount_type == 0){
						$limit_start_state = 0;
					}

				}
				if($limit_start_state == 1 && $product['limit_start'] > 0 && $sdata[2] < $product['limit_start']){
					return $this->json(['status'=>0,'msg'=>$product['limit_start'].'件起售']);
				}
				if($product['perlimitdan'] > 0 && $sdata[2] > $product['perlimitdan']){
					return $this->json(['status'=>0,'msg'=>'['.$product['name'].'] 每单限购'.$product['perlimitdan'].'件']);
				}
                if(getcustom('shop_categroy_limit')){
                    //判断分类是否限购
                    if($product['cid']){
                        $category = Db::name('shop_category')->where('id','in',$product['cid'])->where('limit_num', '>',0)->find();
                        if($category) {
                            $product_cid_arr = explode(',',$product['cid']);
                            foreach ($product_cid_arr as $product_cid_arr_v){
                                if($cat_buynum[$product_cid_arr_v])
                                    $cat_buynum[$product_cid_arr_v] += $num;
                                else
                                    $cat_buynum[$product_cid_arr_v] = $num;
                            }
                            if($category['limit_day'] > 0){
                                $limitStart = strtotime('-' . ($category['limit_day'] - 1) . ' days 00:00:00');
                                $limitEnd   = strtotime('today 23:59:59');
                                $buynum = $cat_buynum[$category['id']] + Db::name('shop_order_goods')->where('aid',aid)->where('mid',$ordermid)->whereFindInSet('cid',$category['id'])
                                        ->where('createtime', 'between', [$limitStart, $limitEnd] )->where('status','in','0,1,2,3')->sum('num');
                                if($buynum > $category['limit_num']){
                                    return $this->json(['status'=>0,'msg'=>'[分类:'.$category['name'].'] 每人每'.$category['limit_day'].'天限购'.$category['limit_num'].'件']);
                                }
                            }else {
                                $buynum = $cat_buynum[$category['id']] + Db::name('shop_order_goods')->where('aid',aid)->where('mid',$ordermid)->whereFindInSet('cid',$product['cid'])
                                        ->where('status','in','0,1,2,3')->sum('num');
                                if($buynum > $category['limit_num']){
                                    return $this->json(['status'=>0,'msg'=>'[分类:'.$category['name'].'] 每人限购'.$category['limit_num'].'件']);
                                }
                            }
                        }
                    }
                }
                $product_memberlevel_limit_month_custom= getcustom('product_memberlevel_limit_month');
				if(getcustom('product_memberlevel_limit')){
					if($product['levellimitdata']){
                        $product['num'] = $num;
						$rs = \app\model\ShopProduct::memberlevel_limit(aid,$ordermid,$product,$this->member['levelid']);
						if($rs['status']==1 && $rs['limitdata']['ismemberlevel_limit']){
                            $limt_errmsg = $product['name'].$rs['limitdata']['days'].'天内限购'.$rs['limitdata']['limit_num'].'件';
                            if($product_memberlevel_limit_month_custom){
                                if($rs['limitdata']['days_type'] ==1){
                                    $limt_errmsg = '本月购买额度已用完，请次月购买。';
                                }
                            }
							return $this->json(['status'=>0,'msg'=>$limt_errmsg]);
						}
					}
				}

                if(getcustom('plug_tengrui')) {
                    //判断是否是否符合会员认证、会员关系、一户、用户组
                    $tr_check = new \app\common\TengRuiCheck();
                    $check_product = $tr_check->check_product($this->member,$product);
                    if($check_product && $check_product['status'] == 0){
                        return $this->json(['status'=>$check_product['status'],'msg'=>$check_product['msg']]);
                    }
                    $tr_roomId = $check_product['tr_roomId'];
                    $product['tr_roomId'] = $tr_roomId;
                }
                if(getcustom('ciruikang_fenxiao')){
	            	//是否开启商城商品需上级购买足量
					if($open_product_parentbuy == 1){
						if($this->member['pid']>0){
							//验证上级购买数量
							$pnum = Db::name('member_product_stock')
								->where('mid',$this->member['pid'])
								->where('proid',$product['id'])
								->where('ggid',$guige['id'])
								->where('aid',aid)
								->value('num');
							if(!$pnum || empty($pnum) || $pnum<$num){
								return $this->json(['status'=>0,'msg'=>'上级：'.$product['name'].$guige['name'].'商品购买库存不足']);
							}
						}
					}
				}

				if(getcustom('member_level_moneypay_price')){
					$guige['sell_putongprice']= $guige['sell_price'];//当前商品普通价格
	                $pre_product_putongprice  = $guige['sell_price'] * $sdata[2];//当前数量商品普通价格
	            }

                $guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);
				if(getcustom('product_wholesale') && $product['product_type'] == 4){
					$jieti_num = $num;
					$jieti_discount_type = $product['jieti_discount_type'];
					if($jieti_discount_type == 0){
						foreach($prodata as $kn=>$vn){
							list($proid_n,$ggid_n,$num_n) = explode(',',$vn);
							if($proid_n ==$sdata[0] && $ggid_n !=$sdata[1]){
								$jieti_num +=$num_n;
							}
						}
						if($product['limit_start'] > 0 && $jieti_num < $product['limit_start']){
							return $this->json(['status'=>0,'msg'=>$product['name'].$product['limit_start'].'件起售']);
						}
					}				
					$guige = $this->formatguigewholesale($guige,$product,$jieti_num);
				}
				if(getcustom('plug_xiongmao') && $data['prodataList']) {
				    //自定义价格
                    if($data['prodataList'][$key]['guige']['sell_price'] < $guige['sell_price']) {
                        return $this->json(['status'=>0,'msg'=>'“'.$product['name'].'”不能小于原价']);
                    }
                    $guige['sell_price'] = $data['prodataList'][$key]['guige']['sell_price'];
                }
                if(getcustom('discount_code_zhongchuang') && $product['discount_code_zc'] > 0 && $post['discount_code_zc']){
                    $guige['sell_price'] = $product['discount_code_zc'];
                }
                if(getcustom('product_pickup_device')){
                    $devicedata = input('param.devicedata');
                    list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
                    if($goods_lane && $device_no){
                        $device_goods= Db::name('product_pickup_device_goods')->where('aid',aid)->where('goods_lane',$goods_lane)->where('device_no',$device_no)->find();
                        //模式是不固定柜门，不是指定柜门二维码，且库存是0的 
                        $type = Db::name('product_pickup_device_set')->where('aid',aid)->value('type');
                        if($device_goods['real_stock'] ==0 && $type ==0 && !$dgid){
                            //type=1固定柜门 0:根据商品查找其他的
                            $device_goods= Db::name('product_pickup_device_goods')->where('aid',aid)->where('device_no',$device_no)->where('proid',$product['id'])->where('ggid',$device_goods['ggid'])->where('real_stock','>',0)->where('stock',$device_goods['stock'])->find();
                        }
                        if($device_goods['real_stock'] ==0){
                            return $this->json(['status'=>0,'msg'=>'产品库存不足']);
                        }
                    }
                   
                }
                if(getcustom('supply_zhenxin')){
                	//甄新汇选规格
                    if($data['zxguige'] && $data['zxguige'][$guige['id']]){
                        $guige['zxbuy_start_qty'] = $data['zxguige'][$guige['id']]['zxbuy_start_qty'];
                        $guige['sprice']          = $data['zxguige'][$guige['id']]['sprice'];
                        $guige['is_overseas']     = $data['zxguige'][$guige['id']]['is_overseas'];
                        $guige['zxpostage']       = $data['zxguige'][$guige['id']]['zxpostage'];
                    }
                }
                $pre_product_price = $guige['sell_price'] * $sdata[2];//当前数量购买价格
                if(getcustom('shop_product_jialiao')){
                    $jlprice = 0;
                    $jltitle = '';
                    if($data['jldata'][$key]){
                        $thisjialiao =  $data['jldata'][$key];
                        foreach($thisjialiao as $jlk=>$val){
                            $jlprice += $val['num'] * $val['price'];
                            $jltitle .=$val['title'].'*'.$val['num'].'/';
                        }
                        $pre_product_price+= $jlprice * $sdata[2];
                    }
                }
				if($product['balance']){
					$balance_price += $pre_product_price * $product['balance']*0.01;
					$product_price += $pre_product_price * (1-$product['balance']*0.01);
					if(getcustom('member_level_moneypay_price')){
		                $pre_product_price_cha = ($pre_product_putongprice * (1-$product['balance']*0.01)) - ($pre_product_price * (1-$product['balance']*0.01));//普通价格与会员购买价格的差额
		                $product_price_cha += $pre_product_price_cha;
		            }
				}else{
					$product_price += $pre_product_price;
					if(getcustom('member_level_moneypay_price')){
		                $pre_product_price_cha = $pre_product_putongprice-$pre_product_price;//普通价格与会员购买价格的差额
		                $product_price_cha += $pre_product_price_cha;
		            }
				}
				$product_priceArr[] = $pre_product_price;
				if($product['lvprice']==0 && $product['no_discount'] == 0){ //未开启会员价
					$needzkproduct_price += $guige['sell_price'] * $sdata[2];
				}

                if(getcustom('product_service_fee')){
                    //服务费
                    if($product['service_fee_switch'] == 1){
                        $serviceFee += $guige['service_fee'] * $sdata[2];
                        $serviceFeeNums += $guige['service_fee'] * $sdata[2];
                    }
                }
                if(getcustom('freeze_money')){
                    $freezemoney_price = bcadd($freezemoney_price,bcmul($guige['freezemoney_price'] , $sdata[2],4),3);
                    $total_freezemoney_price = bcadd($total_freezemoney_price,bcmul($guige['freezemoney_price'] , $sdata[2],4),3);
                }
				if(getcustom('weight_template')){
					$weightids = $data['weightids'];
					if($weightids){
						$sweight = Db::name('shop_weight_template')->where('aid',aid)->where('id',$weightids[$key])->find();
						$weight = $guige['weight'] * $sdata[2];
						$rs = \app\model\Weight::getWeightPrice($sweight,$weight);
						if($rs['status']==0) return $this->json($rs);
						$guige['weight_price'] = $rs['weight_price'];
						$guige['weight_templateid'] = $weightids[$key];
						$totalweight_price += $rs['weight_price'];
					}
				}
                if(getcustom('product_supply_chain')) {
                    $supplierGuige = Db::name('supplier_shop_guige')->where('aid', aid)->where('proid', $product['id'])->where('ggid', $guige['id'])->field('min_price,max_price,is_free_post,freight_money,tax_amount,num gg_num')->find();
                    if ($supplierGuige) {
                        $guige = array_merge($guige, $supplierGuige);
                    }
                }
				
				$totalweight += $guige['weight'] * $sdata[2];
				$totalnum += $sdata[2];
				
				$product['og_scoredk_money'] = 0;//商品占的积分抵扣数值，默认为0
				if($product['scoredkmaxset']==0){
                    $is_sysset_scoredk = true;
                    if(getcustom('scoredk_percent_category')){
                        //查找第一个分类
                        $cid_arr = explode(',',$product['cid']);
                        $first_cid = $cid_arr[0];
                        if ($first_cid){
                            $category_set= Db::name('shop_category')->where('aid',$product['aid'])->where('id',$first_cid)->field('scoredkmaxval,scoredkmaxset')->find();
                            if($category_set['scoredkmaxset'] ==1) {
                                $category_scoreval =$category_set['scoredkmaxval'];
                                if ($category_scoreval > 0 && $category_scoreval < 100) {
                                    $is_sysset_scoredk = false;
                                    $product['og_scoredk_money'] = $category_scoreval * 0.01 * $guige['sell_price'] * $num;
                                    $scoredkmaxmoney += $product['og_scoredk_money'];
                                    $this->sysset['scoredkmaxpercent'] = $category_scoreval;
                                }
                            }
                        }
                    }
                    if($is_sysset_scoredk) {
                        if ($sysset['scoredkmaxpercent'] == 0) {
                        	$product['og_scoredk_money'] = 0;
                            $scoredkmaxmoney += $product['og_scoredk_money'];
                        } else {
                            if ($sysset['scoredkmaxpercent'] > 0 && $sysset['scoredkmaxpercent'] <= 100) {
                            	$product['og_scoredk_money'] = $sysset['scoredkmaxpercent'] * 0.01 * $guige['sell_price'] * $sdata[2];
                                $scoredkmaxmoney += $product['og_scoredk_money'];
                            } else {
                            	$product['og_scoredk_money'] = $guige['sell_price'] * $sdata[2];
                                $scoredkmaxmoney += $product['og_scoredk_money'];
                            }
                        }
                    }
				}elseif($product['scoredkmaxset']==1){
					$scoremaxtype = 1;
					$product['og_scoredk_money'] = $product['scoredkmaxval'] * 0.01 * $guige['sell_price'] * $sdata[2];
					$scoredkmaxmoney += $product['og_scoredk_money'];
				}elseif($product['scoredkmaxset']==2){
					$scoremaxtype = 1;
					$product['og_scoredk_money'] = $product['scoredkmaxval'] * $sdata[2];
					$scoredkmaxmoney += $product['og_scoredk_money'];
				}else{
					$scoremaxtype = 1;
					$product['og_scoredk_money'] = 0;
					$scoredkmaxmoney += $product['og_scoredk_money'];
				}
				if(getcustom('product_givetongzheng')){
                    if($product['tongzhengdkmaxset']==0){
                        if ($sysset['tongzhengdkmaxpercent'] == 0) {
                            $tongzhengdkmaxmoney += 0;
                        } else {
                            if ($sysset['tongzhengdkmaxpercent'] > 0 && $sysset['tongzhengdkmaxpercent'] < 100) {
                                $tongzhengdkmaxmoney += $sysset['tongzhengdkmaxpercent'] * 0.01 * $guige['sell_price'] * $sdata[2];
                            } else {
                                $tongzhengdkmaxmoney += $guige['sell_price'] * $sdata[2];
                            }
                        }
                    }elseif($product['tongzhengdkmaxset']==1){
                        $tongzhengmaxtype = 1;
                        $tongzhengdkmaxmoney += $product['tongzhengdkmaxval'] * 0.01 * $guige['sell_price'] * $sdata[2];
                    }elseif($product['tongzhengdkmaxset']==2){
                        $tongzhengmaxtype = 1;
                        $tongzhengdkmaxmoney += $product['tongzhengdkmaxval'] * $sdata[2];
                    }else{
                        $tongzhengmaxtype = 1;
                        $tongzhengdkmaxmoney += 0;
                    }
                }
                if(getcustom('member_dedamount')){
                	//计算商家让利金额，及会员抵扣金额
                	if($bid>0 && $store_info && $store_info['paymoney_givepercent']>0){
                		//商户当前让利部分金额 = 当前商品金额 * 让利比例
                        $pre_paymoney_givemoney = $pre_product_price * $store_info['paymoney_givepercent']/100;
                        $pre_paymoney_givemoney = $product['paymoney_givemoney'] = round($pre_paymoney_givemoney,2);
                        if($pre_paymoney_givemoney>0){
                        	//商户让利部分金额汇总
                        	$paymoney_givemoney += $pre_paymoney_givemoney;

                            //如果使用抵扣金抵扣，且会员抵扣金额大于0，且系统设置的抵扣比例大于0，计算抵扣金抵扣金额和实付金额
                            if($post['usededamount'] && $dedamount>0 && $dedamount_dkpercent>0){
                                //让利部分抵扣金额 = 商户当前让利部分金额 * 可抵扣比例
                                $pre_dedamount_dkmoney = $pre_paymoney_givemoney * $dedamount_dkpercent/100;
                                $pre_dedamount_dkmoney = $product['dedamount_dkmoney'] = round($pre_dedamount_dkmoney,2);
                                if($pre_dedamount_dkmoney>0){
                                	//判断会员抵扣金额与当前抵扣金额大小
                                    if($dedamount>=$pre_dedamount_dkmoney){
                                        $dedamount -= $pre_dedamount_dkmoney;//减少会员变动抵扣金
                                    }else{
                                        $pre_dedamount_dkmoney = $dedamount;//重置本地低价金额
                                        $dedamount = 0;//减少会员变动抵扣金
                                    }

                                    //抵扣金总金额
                                    $dedamount_dkmoney += $pre_dedamount_dkmoney;
                                }
                            }
                        }
                    }
                }
                if(getcustom('member_shopscore')){
                    if($useshopscore){
                        //计算商品产品抵扣数量：会员积分大于0，且产品积分可抵扣数量大于，且最大可抵扣比例大于0
                        $product['og_shopscoredk_money'] = 0;
                        if($member_shopscore && $member_shopscoredk_money >0 && $shopscoredkmaxpercent>0){
                            //暂无商品单独设置，走系统设置
                            if(!$product['shopscoredkmaxset'] || $product['shopscoredkmaxset']==0){
                            	if($shopscoredkmaxpercent>100) $shopscoredkmaxpercent = 100;
                                $product['og_shopscoredk_money'] = $shopscoredkmaxpercent * 0.01 * $guige['sell_price'] * $sdata[2];
                            }else{
                                //暂无商品单独设置，不走这里
                                if($product['shopscoredkmaxset']==1){
                                    $product['og_shopscoredk_money'] = $product['shopscoredkmaxval'] * 0.01 * $guige['sell_price'] * $sdata[2];
                                }elseif($product['shopscoredkmaxset']==2){
                                    $product['og_shopscoredk_money'] = $product['shopscoredkmaxval'] * $sdata[2];
                                }
                            }
                            if($product['og_shopscoredk_money']>0){
                            	$product['og_shopscoredk_money'] = round($product['og_shopscoredk_money'],2);
                                $shopscoredk_money += $product['og_shopscoredk_money'];
                            }
                        }
                    }
                }
				$tmpprodata = ['product'=>$product,'guige'=>$guige,'num'=>$sdata[2],'isSeckill'=>0];
                //视力档案
                if(getcustom('product_glass') || getcustom('product_glass_custom')){
                    $tmpprodata['glass_record_id'] = $sdata[3]??0;
                }
                if(getcustom('shop_product_jialiao')){
                   
                    $tmpprodata['jltitle'] = $jltitle;
                    $tmpprodata['jlprice'] = $jlprice;
                }
				$prolist[] = $tmpprodata;
				//是否自定义
				if($customfreight>0){
					if($customfreight == 1 || $customfreight == 2){
						$fids = [0];
					}
				}else{
					 if($product['freighttype']==0){
						$fids = array_intersect($fids,explode(',',$product['freightdata']));
					}else{
						$thisfreightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
						$thisfids = [];
						foreach($thisfreightList as $v){
							$thisfids[] = $v['id'];
						}
						$fids = array_intersect($fids,$thisfids);
					}
				}

				$proids[] = $product['id'];
				$cids = array_merge($cids,explode(',',$product['cid']));
				if(!empty($product['cid2'])){
					$cids2 = array_merge($cids2,explode(',',$product['cid2']));
				}
				if($product['givescore_time'] == 0){
					$givescore += $guige['givescore'] * $sdata[2];
				}else{
					$givescore2 += $guige['givescore'] * $sdata[2];
				}
                if(getcustom('yx_farm')){
                    if($product['farmseed_time'] == 0){
                        $give_farmseed = bcadd($give_farmseed,bcmul($guige['give_farm_seed'] , $sdata[2],2),2);
                    }else{
                        $give_farmseed2 = bcadd($give_farmseed,bcmul($guige['give_farm_seed'] , $sdata[2],2),2);
                    }
                }
                if(getcustom('member_commission_max')){
                    if($product['givecommax_time'] != -1){
                        if($product['givecommax_time'] == 0){
                            $give_commission_max += $guige['give_commission_max'] * $sdata[2]; //奖励佣金上限 确认收货后赠送
                        }else{
                            $give_commission_max2 += $guige['give_commission_max'] * $sdata[2]; //奖励佣金上限 付款后赠送
                        }
                    }
                }
                if(getcustom('product_givetongzheng')){
                    $givetongzheng += $guige['givetongzheng'] * $sdata[2];
                }
                if(getcustom('commission_duipeng_score_withdraw')){
                    $give_withdraw_score += $guige['give_withdraw_score'] * $sdata[2];
                    $give_parent1_withdraw_score += $guige['give_parent1_withdraw_score'] * $sdata[2];
                }

                if(getcustom('consumer_value_add')){
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
                        if($guige['give_green_score']<=0){
                            //$guige['give_green_score'] = bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,2);
                            $guige['give_green_score'] = bcdiv(bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,4),$green_score_price,2);
                        }else{
                            $guige['give_green_score'] = bcdiv($guige['give_green_score'],$green_score_price,2);
                        }
                        if($guige['give_bonus_pool']<=0){
                            $guige['give_bonus_pool'] = bcmul($guige['sell_price'],$consumer_set['bonus_pool_bili']/100,2);
                        }
                        if($consumer_set['reward_time']==0){
                            $give_green_score += $guige['give_green_score'] * $sdata[2]; //奖励绿色积分 确认收货后赠送
                            $give_bonus_pool += $guige['give_bonus_pool'] * $sdata[2]; //放入奖金池 确认收货后赠送
                        }else{
                            $give_green_score2 += $guige['give_green_score'] * $sdata[2]; //奖励绿色积分 确认收货后赠送
                            $give_bonus_pool2 += $guige['give_bonus_pool'] * $sdata[2]; //放入奖金池 确认收货后赠送
                        }
                        if(getcustom('green_score_reserves')){
                            //订单进入预备金
                            if($guige['give_green_score_reserves']<=0){
                                //$guige['give_green_score'] = bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,2);
                                $guige['give_green_score_reserves'] = bcmul($guige['sell_price'],$consumer_set['reserves_bili']/100,2);
                            }
                            if($consumer_set['reward_time']==0){
                                $give_green_score_reserves += $guige['give_green_score_reserves'] * $sdata[2]; //预备金 确认收货后赠送
                            }else{
                                $give_green_score_reserves2 += $guige['give_green_score_reserves'] * $sdata[2]; //预备金 确认收货后赠送
                            }
                        }
                    }
                }
                if(getcustom('member_goldmoney_silvermoney')){
                    if($goldsilvertype >0){
                        if($goldsilvertype == 1){
                            //银值抵扣数额
                            if($ShopSendSilvermoney && $product['silvermoneydec_ratio']>0 && $silvermoney>0){
                                $silvermoneydec = $product['silvermoneydec_ratio'] * 0.01 * $guige['sell_price'] * $num;
                                //计算剩余银值
                                $silvercha = $silvermoney -$silvermoneydec;
                                if($silvercha>=0){
                                    $allsilvermoneydec += $silvermoneydec;
                                    $silvermoney = $silvercha;
                                }else{
                                    $allsilvermoneydec += $silvermoney;
                                    $silvermoney = 0;
                                }
                            }
                        }else if($goldsilvertype == 2){
                            //金值抵扣数额
                            if($ShopSendGoldmoney && $product['goldmoneydec_ratio']>0 && $goldmoney>0){
                                $goldmoneydec = $product['goldmoneydec_ratio'] * 0.01 * $guige['sell_price'] * $num;
                                //计算剩余金值
                                $goldcha = $goldmoney -$goldmoneydec;
                                if($goldcha>=0){
                                    $allgoldmoneydec += $goldmoneydec;
                                    $goldmoney = $goldcha;
                                }else{
                                    $allgoldmoneydec += $goldmoney;
                                    $goldmoney = 0;
                                }
                            }
                        }
                    }
                    //奖励金值 确认收货后赠送
                    if($ShopSendGoldmoney) {
                        $givegoldmoney   += $guige['givegoldmoney'] * $sdata[2]; 
                    }
                    //奖励银值 确认收货后赠送
                    if($ShopSendSilvermoney) {
                        $givesilvermoney += $guige['givesilvermoney'] * $sdata[2]; 
                    }
                }
				if($product['to86yk_tid']){
					$extendInput = [['key'=>'input','val1'=>'充值账号','val2'=>'请输入充值账号','val3'=>1],['key'=>'input','val1'=>'确认账号','val2'=>'请再次输入充值账号','val3'=>1]];
				}
                if(getcustom('pay_yuanbao') ){
                    if($product['yuanbao']>0){
                        $total_yuanbao += $product['yuanbao'];
                    }else{
                        $have_no_yuanbao = 1;
                    }
                }
                //如果存在，且数据存在
                if($mjset && $mjdata){
                    //指定分类
                    if($mjset['fwtype']==1){
                        //指定分类数组
                        $cat_arr     = explode(",",$mjset['categoryids']);
                        //商品分类
                        $pro_cat_arr = explode(",",$product['cid']);
                        //交集
                        $j_arr = array_intersect($cat_arr,$pro_cat_arr);
                        if ($j_arr){
                            $mj_price += $guige['sell_price'] * $sdata[2];
                            if($product['balance']){
                                $mj_price = $mj_price * (1-$product['balance']*0.01);
                            }
                        }
                    //指定商品
                    }else if($mjset['fwtype']==2){
                        $pro_arr = explode(",",$mjset['productids']);
                        //商品在指定商品内
                        if (in_array($product['id'], $pro_arr)){
                            $mj_price += $guige['sell_price'] * $sdata[2];
                            if($product['balance']){
                                $mj_price = $mj_price * (1-$product['balance']*0.01);
                            }
                        }
                    }else{
                        $mj_price += $guige['sell_price'] * $sdata[2];
                        if($product['balance']){
                            $mj_price = $mj_price * (1-$product['balance']*0.01);
                        }
                    }
                }
                if(getcustom('yx_hongbao_queue_free')){
                    if(in_array($product['id'],$hongbao_join_proids_arr) && (in_array($this->member['levelid'],$hongbao_gettj) || in_array(-1,$hongbao_gettj))) $is_use_youhui = 0;
                }
                if(getcustom('product_quanyi') && $product['product_type']==8){
                    //权益商品
                    $hexiao_num_total = bcadd($hexiao_num_total,bcmul($product['hexiao_num'],$sdata[2]));
                }

                if(getcustom('money_dec')){
                    if($data['moneydec_rate']>0){
                        if(getcustom('money_dec_product')){
                            if($product['moneydecset']!=0){
                                $money_dec_type = 1;
                            }
                        }
                    }
                }
                //超级卖货
                if(getcustom('yx_collage_jipin2')) {
                    $time = time();
                    $jipin_where = [];
                    $jipin_where[] = ['aid', '=', aid];
                    $jipin_where[] = ['status', '=', 1];
                    $jipin_where[] = ['starttime', '<', $time];
                    $jipin_where[] = ['endtime', '>', $time];
                    $jipin_where[] = Db::raw('find_in_set('.$product['id'].',productids)');
                    $jipin = Db::name('jipin')->where($jipin_where)->order('id desc')->find();
                    if($jipin){
                        if($num != 1){
                            return $this->json(['status'=>0,'msg'=>'拼团活动期间限制下单1件']);
                        }
                        // 级别
                        if(!$jipin['showtj']){
                            return $this->json(['status'=>0,'msg'=>'参与等级限制，暂不允许下单']);
                        }
                        $jipinshowtj = explode(',', $jipin['showtj']);
                        if(!in_array('-1', $jipinshowtj) && !in_array($this->member['levelid'], $jipinshowtj)){
                            return $this->json(['status'=>0,'msg'=>'当前等级，暂不允许下单']);
                        }
                        //拼团中
                        $log = Db::name('jipin_log')->where('aid',aid)->where('jipinid',$jipin['id'])->where('status','0')->where('find_in_set('.mid.',mids)')->find();
                        if($log){
                            return $this->json(['status'=>0,'msg'=>'已在拼团中，暂不允许下单']);
                        }
                        // 成团时间确认收货后的 再判断未收货订单是否存在
                        if($jipin['tuanstatus'] == 1){
                            $jipinoginfo = Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where('proid',$product['id'])->where('status','in',[1,2])->find();
                            if($jipinoginfo){
                                return $this->json(['status'=>0,'msg'=>'已在拼团中，暂不允许重复下单']);
                            }
                        }
                    }
                }
                if(getcustom('product_chinaums_subsidy')){
                    //优惠码
                    $discount_code = $product['discount_code'];
                }
				if(getcustom('business_hexiaoplatform_ticheng') && $product['business_hx_ticheng'] > 0){
					$businessHxMoney += $pre_product_price * ($product['business_hx_ticheng'] / 100);
				}
				if(getcustom('product_supplier_admin')){
                    $supplier_id =  $product['supplier_id'];
                }
                if(getcustom('deposit')){
                    $deposit_id =  $product['deposit_id'];
                    if($deposit_id  && $product['deposit_status']){
                        $deposit_member = Db::name('deposit_member')->where('aid',aid)->where('mid',mid)->where('deposit_id',$deposit_id)->where('num','>',0)->find();
                        $deposit =Db::name('deposit')->where('aid',aid)->where('id',$deposit_id)->where('status',1)->find();
                        $pronum = $sdata[2];
                        if($deposit_member && $deposit){
                            $deposit_dk_num =  $deposit_dk_list[$deposit_id]??0; //1
                            $sy_deposit_member_num =  $deposit_member['num'] -$deposit_dk_num; //减去前面抵扣的数量，剩余押金的数量
                            if($sy_deposit_member_num < $pronum){
                                $buy_deposit_num = $pronum - $sy_deposit_member_num;
                                $deposit_member_list[$product['id']] = [
                                    'data' => $deposit,
                                    'num' => $buy_deposit_num//需要扣除的数量
                                ];
                            }else{
                                $buy_deposit_num = 0;
                                $deposit_member_list[$product['id']] = [
                                    'data' => $deposit,
                                    'num' => $pronum//需要扣除的数量
                                ];
                                $sy_deposit_member_num = $pronum;
                            }
                            $deposit_dk_list[$deposit_id] =  $sy_deposit_member_num;
                        }else{
                            $buy_deposit_num =  $pronum;
                        }
                        if($buy_deposit_num > 0 &&  $deposit){
                            $deposit_buy_list[$product['id']] = [
                                'data' => $deposit,
                                'num' => $buy_deposit_num,
                            ];
                            $deposit_payprice +=   $buy_deposit_num *$deposit['money']; 
                        }
                       
                    }
                }
			}
            
            $givescore = dd_money_format($givescore,$score_weishu);
            $givescore2 = dd_money_format($givescore2,$score_weishu);

            if(getcustom('score_stacking_give_set') && $sysset['score_stacking_give_set'] == 1){
                //只消费赠送购买商品则不赠送
                $givescore = 0;
                $givescore2 = 0;
            }
			
            //限购判断
            if($productLimit){
                foreach ($productLimit as $pitem){
                    if($pitem['buy']+$pitem['buyed'] > $pitem['perlimit']){
                        return $this->json(['status'=>0,'msg'=>'['.$pitem['name'].'] 每人限购'.$pitem['perlimit'].'件']);
                    }
                }
            }
            //称重商品单独下单
            if(getcustom('product_weight') && array_search('2',$productTypes)!==false && count(array_unique($productTypes))>1){
                return $this->json(['status'=>0,'msg'=>'称重商品须单独下单']);
            }
            if(getcustom('product_quanyi') && array_search('8',$productTypes)!==false && count(array_unique($productTypes))>1){
                //权益商品单独下单
                return $this->json(['status'=>0,'msg'=>t('权益商品').'须单独下单']);
            }

            if(getcustom('product_handwork')){
	            //手工活类单独下单
	            if(in_array(3,$protypes)){
	            	$ptnum = count($protypes);
	            	if($ptnum>1){
	            		return $this->json(['status'=>0,'msg'=>'手工活类商品请单独购买']);
	            	}
	            }
	        }

	        if(getcustom('member_forzengxcommission')){
	            //佣金类单独下单
	            if(in_array(10,$protypes)){
	            	$ptnum = count($protypes);
	            	if($ptnum>1){
	            		return $this->json(['status'=>0,'msg'=>t('佣金').'类商品请单独购买']);
	            	}
	            }
	        }
			if(!$fids){
				if(count($buydata['prodata'])>1){
					return $this->json(['status'=>0,'msg'=>'所选择商品配送方式不同，请分别下单']);
				}else{
					return $this->json(['status'=>0,'msg'=>'获取配送方式失败']);
				}
			}
			if(getcustom('guige_split')){
				$rs = \app\model\ShopProduct::checkstock($prolist);
				if($rs['status'] == 0) return $this->json($rs);
			}
			//会员折扣
			$leveldk_money = 0;
			if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
				$leveldk_money = $needzkproduct_price * (1 - $userlevel['discount'] * 0.1);
			}
			$leveldk_money = round($leveldk_money,2);
			 if(getcustom('yx_hongbao_queue_free')){
                if($is_use_youhui ==0) $leveldk_money = 0;
            }
			$totalprice = $product_price - $leveldk_money;
            $mj_price   = $mj_price - $leveldk_money;

			$manjian_money = 0;
			$moneyduan = 0;
			if($mjdata && $mj_price>0){
                //如果是累计消费额满减
                if($mjset['total_status']==1){
                    //指定分类
                    if($mjset['fwtype']==1){
                        //查询他分类消费累计
                        $sum_money  = Db::name('shop_order_goods')
                            ->alias('sog')
                            ->join('shop_order so','so.id   = sog.orderid')
                            ->join('shop_product sp','sp.id = sog.proid')
                            ->where('sog.mid',$ordermid)
                            ->where('so.status',3)
                            ->where('sp.cid','in',$mjset['categoryids'])
                            ->sum('sog.totalprice');

                        //分类退款累计
                        $refund_money = Db::name('shop_refund_order_goods')
                            ->alias('srog')
                            ->join('shop_order so','so.id   = srog.orderid')
                            ->join('shop_product sp','sp.id = srog.proid')
                            ->where('srog.mid',$ordermid)
                            ->where('so.status',3)
                            ->where('so.refund_status',2)
                            ->where('sp.cid','in',$mjset['categoryids'])
                            ->sum('srog.refund_money');

                    //指定商品
                    }else if($mjset['fwtype']==2){
                        //查询他商品消费累计
                        $sum_money  = Db::name('shop_order_goods')
                            ->alias('sog')
                            ->join('shop_order so','so.id   = sog.orderid')
                            ->join('shop_product sp','sp.id = sog.proid')
                            ->where('sog.mid',$ordermid)
                            ->where('so.status',3)
                            ->where('sog.proid','in',$mjset['productids'])
                            ->sum('sog.totalprice');

                        //商品退款累计
                        $refund_money = Db::name('shop_refund_order_goods')
                            ->alias('srog')
                            ->join('shop_order so','so.id   = srog.orderid')
                            ->join('shop_product sp','sp.id = srog.proid')
                            ->where('srog.mid',$ordermid)
                            ->where('so.status',3)
                            ->where('so.refund_status',2)
                            ->where('srog.proid','in',$mjset['productids'])
                            ->sum('srog.refund_money');
                    //所有
                    }else{
                        //查询他累计消费多少
                        $sum_money    = Db::name('shop_order')->where('mid',$ordermid)->where('status',3)->sum('totalprice');
                        $refund_money = Db::name('shop_order')->where('mid',$ordermid)->where('status',3)->where('refund_status',2)->sum('refund_money');
                    }
                    $sj_money = $sum_money-$refund_money;
                    $sj_money = round($sj_money,2);
                    $all_price = $sj_money+$mj_price;
                    foreach($mjdata as $give){
                        if($all_price*1 >= $give['money']*1 && $give['money']*1 > $moneyduan){
                            $moneyduan = $give['money']*1;
                            $manjian_money = $give['jian']*1;
                        }
                    }
                }else{
    				foreach($mjdata as $give){
    					if($mj_price*1 >= $give['money']*1 && $give['money']*1 > $moneyduan){
    						$moneyduan = $give['money']*1;
    						$manjian_money = $give['jian']*1;
    					}
    				}
                }
			}

			if($manjian_money <= 0) $manjian_money = 0;
			$totalprice = $totalprice - $manjian_money;
			if($totalprice < 0) $totalprice = 0;

			//运费
			$freight_price = 0;
			//是否自定义快递
			if($customfreight>0){
				if($customfreight == 1){
                	if(getcustom('supply_zhenxin')){
                        $freight = ['id'=>0,'name'=>'普通快递','pstype'=>0];
                        $freight_price = $data['zxpostage'];
                        if(!$address || !$address['id'] || $address['id']<=0){
							return $this->json(['status'=>0,'msg'=>'请选择收货地址']);
						}
                    }
                }else if($customfreight == 2){
                	if(getcustom('shop_giveorder')){
                        $freight = ['id'=>0,'name'=>'赠好友','pstype'=>0];
                        $freight_price = 0;
                    }
                }
			}else{
				if(getcustom('supply_yongsheng')){
					//判断是不是永盛商品，只能用普通快递
					if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
						if(!$data['freight_id']) return $this->json(['status'=>0,'msg'=>'有商品配方方式错误，请重新下单']);
					}
				}
                if(getcustom('deposit')){
                    $deposit_id =  $product['deposit_id'];
                    if($deposit_id && !$data['freight_id'] && $product['deposit_status']){
                        //存在押金和无配送方式（默认的包邮）时的判断
                        return $this->json(['status'=>0,'msg'=>'请选择同城配送方式']);
                    }
                }
				if($data['freight_id']){
					$freight = Db::name('freight')->where('aid',aid)->where('id',$data['freight_id'])->find();
                    if(getcustom('deposit')){
                        //存在押金和配送方式时的判断
                        if($product['deposit_id'] && $freight['pstype']!=2 && $product['deposit_status'] ){
                            return $this->json(['status'=>0,'msg'=>'请选择同城配送方式']);
                        }
                    }
                    if(getcustom('wx_express_intracity')){
                        if($freight['pstype']==2){
                            $peisong_set = Db::name('peisong_set')->where('aid',aid)->field('wxtc_status,wxtc_store_id,wxtc_status_business')->find();
                            $wxtc_store_id = 0;
                            if($bid == 0 && $peisong_set['wxtc_status'] == 1){
                                $wxtc_store_id = $peisong_set['wxtc_store_id'];
                            }elseif($bid > 0 && $peisong_set['wxtc_status'] == 1 && $peisong_set['wxtc_status_business'] == 1){
                                $wxtc_store_id = Db::name('business')->where('aid',aid)->where('id',$bid)->value('wxtc_store_id');
                            }
                            $wxtc_store = Db::name('wx_express_intracity_store')->where('aid',aid)->where('id',$wxtc_store_id)->find();

                            if($wxtc_store && $wxtc_store['select_delivery_time'] != 1){
                                //不允许选择配送时间
                                $freight['pstimeset'] = 0;
                            }
                        }
                    }
					if($freight['pstype']==11){
						$freight['type11key'] = $data['type11key'];
					}
					if($freight['minpriceset']==1 && $freight['minprice']>0 && $freight['minprice'] > $product_price){
						return $this->json(['status'=>0,'msg'=>$freight['name'] . '满'.$freight['minprice'].'元起送']);
					}
					if(($address['name']=='' || $address['tel'] =='') && ($freight['pstype']==1 || $freight['pstype']==3) && $freight['needlinkinfo']==1){
						return $this->json(['status'=>0,'msg'=>'请填写联系人和联系电话']);
					}
					if($freight['pstype']==0 && (!$address || !$address['id'] || $address['id']<=0)){
						return $this->json(['status'=>0,'msg'=>'请选择收货地址']);
					}

					if(getcustom('mendian_upgrade') && $bid == 0){
						$admin = Db::name('admin')->field('mendian_upgrade_status')->where('id',aid)->find();
						if($admin['mendian_upgrade_status']==1){
							$mendianset =  Db::name('mendian_sysset')->field('fwdistance')->where('aid',aid)->find();

							if($mendianset['fwdistance']>0){
								$mendian = Db::name('mendian')->where('id',$data['storeid'])->find();
								$juli = getdistance($post['longitude'],$post['latitude'],$mendian['longitude'],$mendian['latitude'],2);

								if($juli>$mendianset['fwdistance']){
									return $this->json(['status'=>0,'msg'=>'超出门店服务距离']);
								}
							}
						}
					}
                    if($freight['pstype']==1 && $freight['select_address_status'] ==1 && (!$address || !$address['id'] || $address['id']<=0)){
                        return $this->json(['status'=>0,'msg'=>'请选择收货地址']);
                    }
                    if(getcustom('supply_yongsheng')){
						//判断是不是永盛商品，只能用普通快递
						if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng' && $freight['pstype']!=0){
							if(!$data['freight_id']) return $this->json(['status'=>0,'msg'=>'有商品配方方式错误，请重新下单']);
						}
					}

					$rs = \app\model\Freight::getFreightPrice($freight,$address,$product_price,$totalnum,$totalweight);
					if($rs['status']==0) return $this->json($rs);
					$freight_price = $rs['freight_price'];
					//判断配送时间选择是否符合要求
					if($freight['pstimeset']==1){
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
					if( getcustom('up_floor_fee')){
					    //同城配送开启上楼费
                        $up_floor_fee = 0;
                        $floor =  '';
                        $room = '';
                        $have_dianti = '';
					    if($freight['up_floor_fee_status'] ==1 && $freight['pstype'] ==2 ){
                            if(!$address['floor']){
                                $up_floor_data =$data['up_floor_data'];
                                //地址不存在的，直接输入的楼层
                                if($up_floor_data['have_dianti'] ==0 && $up_floor_data['floor']){
                                    //根据规则计算，公式：基础费*元+每层费用×（楼层数-1）。
                                    $up_floor_fee= dd_money_format($freight['up_floor_base_price'] + $freight['up_floor_each_add_price'] * ($up_floor_data['floor'] - 1) * $totalnum);
                                    //更新地址
                                    Db::name('member_address')->where('aid',aid)->where('mid',mid)->where('id',$post['addressid'])->update(['floor' => $up_floor_data['floor'],'have_dianti' =>$up_floor_data['have_dianti'] ]);
                                    $floor = $up_floor_data['floor'];
                                    $have_dianti = $up_floor_data['have_dianti'];
                                }
                               
                            }else{
                                if($address['have_dianti'] ==0){
                                    //根据规则计算，公式：基础费*元+每层费用×（楼层数-1）。
                                    $up_floor_fee= dd_money_format($freight['up_floor_base_price'] + $freight['up_floor_each_add_price'] * ($address['floor'] - 1) * $totalnum);
                                    $floor = $address['floor'];
                                    $have_dianti = $address['have_dianti'];
                                    $room = $address['room'];
                                }
                            }
                        }
					    $totalprice = $totalprice + $up_floor_fee;
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
			}
			//优惠券
			$new_freight_price = $freight_price;
			$coupon_money = 0;
			$not_used_discount =0;
			if($data['couponrid']){
				if($data['bid'] > 0){
					$business = Db::name('business')->where('aid',aid)->where('id', $data['bid'])->find();
					$bcids = $business['cid'] ? explode(',',$business['cid']) : [];
				}else{
					$bcids = [];
				}
				if($bcids){
					$whereCid = [];
					foreach($bcids as $bcid){
						$whereCid[] = "find_in_set({$bcid},canused_bcids)";
					}
					$whereCids = implode(' or ',$whereCid);
				}else{
					$whereCids = '0=1';
				}
                $select_prid = [];
                $couponridUsed = [];
				foreach($couponridArr as $couponrid){
					$couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',$ordermid)->where('id',$couponrid)
						->whereRaw("bid=-1 or bid=".$data['bid']." or (bid=0 and (canused_bids='all' or find_in_set(".$data['bid'].",canused_bids) or ($whereCids)))")->find();
					$typearr = [1,4,10,11];
					if(getcustom('coupon_shop_times_coupon')){
                        $typearr[] =3;//可使用计次券
                    }
					if(!$couponrecord){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不存在']);
					}elseif($couponrecord['status']!=0){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已使用过了']);
					}elseif($couponrecord['starttime'] > time()){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'尚未开始使用']);	
					}elseif($couponrecord['endtime'] < time()){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已过期']);	
					}elseif($couponrecord['minprice'] > $totalprice){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
					}elseif( !in_array($couponrecord['type'],$typearr)){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
					}
					$couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$couponrecord['couponid'])->find();
                    if(empty($couponinfo)){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不存在或已作废']);
                    }
                    //0全场通用,1指定类目,2指定商品,6指定商家类目
                    if(!in_array($couponinfo['fwtype'],[0,1,2,6])){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'超出可用范围']);
                    }
					if($couponrecord['from_mid']==0 && $couponinfo && $couponinfo['isgive']==2){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'仅可转赠']);
					}
					$usetj = explode(',',$couponinfo['usetj']);
					if(!in_array('-1',$usetj) && !in_array($this->member['levelid'],$usetj) && (!in_array('0',$usetj) || $this->member['subscribe']!=1)){
						return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不可用']);
					}
					if($couponinfo['fwtype']==2){//指定商品可用
						$productids = explode(',',$couponinfo['productids']);
						if(!array_intersect($proids,$productids)){
							return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'指定商品可用']);
						}
						$thistotalprice = 0;
						foreach($prolist as $k2=>$v2){
							$product = $v2['product'];
							if(in_array($product['id'],$productids)){
								$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
                                if(getcustom('coupon_xianxia_buy') || getcustom('coupon_auto_multi_select')) {
                                    $couponrecord['sell_price'] = $v2['guige']['sell_price'];
                                }
							}
						}
						if($thistotalprice < $couponinfo['minprice']){
							return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'指定商品未达到'.$couponinfo['minprice'].'元']);
						}
                        $couponrecord['money'] = min($thistotalprice,$couponrecord['money']);
					}
					if($couponinfo['fwtype']==1){//指定类目可用
						$categoryids = explode(',',$couponinfo['categoryids']);
						$clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
						foreach($clist as $kc=>$vc){
							$categoryids[] = $vc['id'];
							$cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
							if($cate2) $categoryids[] = $cate2['id'];
						}
						if(!array_intersect($cids,$categoryids)){
							return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'指定分类可用']);
						}
						$thistotalprice = 0;
						foreach($prolist as $k2=>$v2){
							$product = $v2['product'];
							if(array_intersect(explode(',',$product['cid']),$categoryids)){
								$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
							}
						}
						if($thistotalprice < $couponinfo['minprice']){
							return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'指定分类未达到'.$couponinfo['minprice'].'元']);
						}
                        $couponrecord['money'] = min($thistotalprice,$couponrecord['money']);
					}
					if($couponinfo['fwtype']==6){//指定商家类目可用
						$categoryids2 = explode(',',$couponinfo['categoryids2']);
						$clist2 = Db::name('shop_category2')->where('pid','in',$categoryids2)->select()->toArray();
						foreach($clist2 as $kc=>$vc){
							$categoryids2[] = $vc['id'];
							$cate2 = Db::name('shop_category2')->where('pid',$vc['id'])->find();
							if($cate2) $categoryids2[] = $cate2['id'];
						}
						if(!array_intersect($cids2,$categoryids2)){
							return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'指定分类可用']);
						}
						$thistotalprice = 0;
						foreach($prolist as $k2=>$v2){
							$product = $v2['product'];
							if(array_intersect(explode(',',$product['cid2']),$categoryids2)){
								$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
							}
						}
						if($thistotalprice < $couponinfo['minprice']){
							return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'指定分类未达到'.$couponinfo['minprice'].'元']);
						}
                        $couponrecord['money'] = min($thistotalprice,$couponrecord['money']);
					}

                    if(getcustom('coupon_use_interval_time') && $couponinfo['interval_time'] > 0){
                        //判断优惠券规定时间内可使用次数
                        $interval_time = $couponinfo['interval_time'] * 60;
                        $dqsj = time();
                        $zqsj = $dqsj - $interval_time * 60;

                        $sycs = Db::name('coupon_record')->where('aid',aid)->where('mid',$ordermid)->where('couponid',$couponinfo['id'])->where('usetime','between',[$zqsj,$dqsj])->count();

                        $jgsjgs = sprintf('%g', $couponinfo['interval_time']).'小时';
                        if($couponinfo['interval_time'] < 1){
                            $jgsjgs = $interval_time.'分钟';
                        }

                        if($sycs >= $couponinfo['usable_num']){
                            return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已超过间隔时间内可使用次数，请'.$jgsjgs.'后再试或选择其它'.t('优惠券')]);
                        }
                    }
					
                    $couponrecord_update = ['status'=>1,'usetime'=>time()];
                    if($couponrecord['type']==4){//运费抵扣券
						$new_freight_price = 0;
					}elseif ($couponrecord['type']==3){//计次券
					    if(getcustom('coupon_shop_times_coupon')){
                            $totalcoupondknum = 0;
                            foreach($prolist as $k2=>$v2){
                                $product = $v2['product'];
                                $proid = $product['id'];
                                $pronum = $v2['num'];
                                if(in_array($proid,$productids)){
                                    $sy_limit_count = $couponrecord['limit_count']-$couponrecord['used_count'];
                                    if($pronum > $sy_limit_count){
                                        $coupondknum = $sy_limit_count;
                                    }else{
                                        $coupondknum = $pronum;
                                    }
                                    //今日已核销次数
                                    if( $couponrecord['limit_perday'] > 0){
                                        $dayhxnum =\app\common\Coupon::getTimesCouponHxnum(aid,$couponrecord);
                                        $sy_dayhxnum = $couponrecord['limit_perday'] - $dayhxnum;
                                        $coupondknum = $coupondknum > $sy_dayhxnum?$sy_dayhxnum:$coupondknum;
                                    }
                                    if($coupondknum > 0){
                                        $product_sell_price = $v2['guige']['sell_price'] ;
                                        if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
                                            $product_sell_price =   $product_sell_price * $userlevel['discount']*0.1;
                                        }
                                        $coupon_money +=$product_sell_price* $coupondknum;
                                        $totalcoupondknum += $coupondknum;
                                    }
                                }
                            }
                            //计次券 未核销完 不能改为已使用
                            if($totalcoupondknum+$couponrecord['used_count'] < $couponrecord['limit_count']){
                                $couponrecord_update = [];
                                for($hx = 0; $hx < $totalcoupondknum;$hx++){
                                    $hx_data = [
                                        'type' =>'coupon',
                                        'title' =>t('会员').'下单核销',
                                        'ordernum' =>$ordernum,
                                        'orderid' =>$couponrecord['id'],
                                        'mid' =>mid,
                                        'bid' =>$couponrecord['bid'],
                                        'aid' =>aid,
                                        'uid' => 0,
                                        'remark' => t('会员').'下单核销',
                                        'createtime' =>time()
                                    ];
                                    Db::name('hexiao_order')->insert($hx_data);
                                }
                            }
                            $couponrecord_update['used_count'] = $couponrecord['used_count'] + $totalcoupondknum;
                            if ($coupon_money > $totalprice) $coupon_money = $totalprice;
                        }
                    }elseif($couponrecord['type']==10) {//折扣券
                        if(getcustom('coupon_xianxia_buy')){
                            if($couponrecord['is_xianxia_buy'] ==1){
                                $coupon_money += $couponrecord['sell_price'] * (100 - $couponrecord['discount']) * 0.01;
                            }
                        }else{
                            if ($couponinfo['fwtype'] == 1 || $couponinfo['fwtype'] == 2) {
                                $coupon_money += $thistotalprice * (100 - $couponrecord['discount']) * 0.01;
                            } else {
                                $coupon_money += $totalprice * (100 - $couponrecord['discount']) * 0.01;
                            }
                        }
                        if ($coupon_money > $totalprice) $coupon_money = $totalprice;
					}elseif($couponrecord['type']==11){//线下优惠券的兑换券
                        if(getcustom('coupon_xianxia_buy') || getcustom('coupon_auto_multi_select')){
                            foreach($prolist as $k2=>$v2){
                                $product = $v2['product'];
                                $proid = $product['id'];
                                $pronum = $v2['num'];
                                if(in_array($proid,$productids)){
                                    if(!$select_prid[$proid]){
                                        $select_prid[$proid] = 0;
                                    }
                                    if($select_prid[$proid] < $pronum){
                                        $select_prid[$proid] =   $select_prid[$proid] +1;
                                        $coupon_money +=$v2['guige']['sell_price'];
                                        break;
                                    }else{
                                        continue;
                                    }
                                }
                            }
                        }
                    }else{
						$coupon_money += $couponrecord['money'];
						if($coupon_money > $totalprice) $coupon_money = $totalprice;
					}
					if(getcustom('coupon_pack')){
						//张数
		                if($couponrecord && $couponrecord['packrid'] && $couponrecord['num'] && $couponrecord['num']>0){
		                	$usenum = $couponrecord['usenum']+1;
		                	if($usenum<$couponrecord['num']){
		                		$couponrecord_update = ['status'=>0,'usenum'=>$usenum];
		                	}else{
		                		$couponrecord_update = ['status'=>1,'usenum'=>$couponrecord['num'],'usetime'=>time()];
		                	}
		                }
		            }
                    Db::name('coupon_record')->where('id',$couponrid)->update($couponrecord_update);
                    $not_used_discount = $couponinfo['not_used_discount'];
                    $couponridUsed[] = $couponrid;
                    //if(getcustom('wanyue10086') && $couponrecord['is_api_get']) (new \app\custom\Wanyue10086(aid))->hexiaoNotice($couponrecord,$ordernum);
				}
			}
			if(getcustom('deposit')){
                $water_coupon_money = 0;
                $water_coupon_num = [];
                if($data['water_couponrid']){
                    $water_dk_num = [];//每个商品的已经抵扣的次数集合
                    $water_couponridArr = explode(',',$data['water_couponrid']);
                    foreach($water_couponridArr as $couponrid){
                        $whereraw ="bid=-1 or bid=".$data['bid'];
                        if(getcustom('business_canuseplatcoupon')){
                            $whereraw .=" or (bid=0 and (canused_bids='all' or find_in_set(".$data['bid'].",canused_bids) ))";
                        }
                        $couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',$ordermid)->where('id',$couponrid)
                            ->whereRaw($whereraw)->find();
                        $typearr[] =3;//可使用计次券
                        if(!$couponrecord){
                            return $this->json(['status'=>0,'msg'=>'该'.$couponrecord['couponname'].t('电子水票').'不存在']);
                        }elseif($couponrecord['status']!=0){
                            return $this->json(['status'=>0,'msg'=>'该'.t('电子水票').'已使用过了']);
                        }elseif($couponrecord['starttime'] > time()){
                            return $this->json(['status'=>0,'msg'=>'该'.t('电子水票').'尚未开始使用']);
                        }elseif($couponrecord['endtime'] < time()){
                            return $this->json(['status'=>0,'msg'=>'该'.t('电子水票').'已过期']);
                        }elseif( !in_array($couponrecord['type'],$typearr)){
                            return $this->json(['status'=>0,'msg'=>'该'.t('电子水票').'不符合条件']);
                        }
                        $couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$couponrecord['couponid'])->find();
                        if(empty($couponinfo)){
                            return $this->json(['status'=>0,'msg'=>'该'.t('电子水票').'不存在或已作废']);
                        }
                        
                        $productids = explode(',',$couponinfo['productids'])??[];
                        $couponrecord_update = ['status'=>1,'usetime'=>time()];
                        $totalcoupondknum = 0;
                        foreach($prolist as $k2=>$v2){
                            $product = $v2['product'];
                            $proid = $product['id'];
                            $pronum = $v2['num'];
                            if(in_array($proid,$productids)){
                                $sy_limit_count = $couponrecord['limit_count']-$couponrecord['used_count'];
                                $sy_limit_count = $sy_limit_count- $totalcoupondknum;
                                if($sy_limit_count==0){
                                    continue;
                                }
                                $pronum2 =  $pronum;
                                $pronum2 = $pronum2 - $water_dk_num[$proid];
                                if($pronum2 > $sy_limit_count){
                                    $coupondknum = $sy_limit_count;
                                }else{
                                    $coupondknum = $pronum2;
                                }
                                //今日已核销次数
                                if( $couponrecord['limit_perday'] > 0){
                                    $dayhxnum =\app\common\Coupon::getTimesCouponHxnum(aid,$couponrecord);
                                    $sy_dayhxnum = $couponrecord['limit_perday'] - $dayhxnum;
                                    $coupondknum = $coupondknum > $sy_dayhxnum?$sy_dayhxnum:$coupondknum;
                                }
                                if($coupondknum > 0){
                                    $product_sell_price = $v2['guige']['sell_price'] ;
                                    if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
                                        $product_sell_price =   $product_sell_price * $userlevel['discount']*0.1;
                                    }
                                    $water_coupon_money +=$product_sell_price* $coupondknum;
                                    $totalcoupondknum += $coupondknum;
                                    $water_dk_num[$proid] =  $water_dk_num[$proid] + $coupondknum;
                                }
                            }
                        }
                        //计次券 未核销完 不能改为已使用
                        if($totalcoupondknum+$couponrecord['used_count'] <= $couponrecord['limit_count']){
                            $couponrecord_update = [];
                            for($hx = 0; $hx < $totalcoupondknum;$hx++){
                                $hx_data = [
                                    'type' =>'coupon',
                                    'title' =>t('会员').'下单核销',
                                    'ordernum' =>$ordernum,
                                    'orderid' =>$couponrecord['id'],
                                    'mid' =>mid,
                                    'bid' =>$couponrecord['bid'],
                                    'aid' =>aid,
                                    'uid' => 0,
                                    'remark' => t('会员').'下单核销',
                                    'createtime' =>time()
                                ];
                                Db::name('hexiao_order')->insert($hx_data);
                            }
                        }
                        $couponrecord_update['used_count'] = $couponrecord['used_count'] + $totalcoupondknum;
                        
                        if($couponrecord_update['used_count'] == $couponrecord['limit_count']){
                            $couponrecord_update['status'] =1;
                            $couponrecord_update['usetime'] =time();
                        }
                        if ($water_coupon_money > $product_price) $water_coupon_money = $product_price;
                        Db::name('coupon_record')->where('id',$couponrid)->update($couponrecord_update);
                        $water_coupon_num[] = $totalcoupondknum; 
                    }
                }
            }
            if(getcustom('coupon_not_used_discount') ){
                $coupon_not_used_discount = Db::name('shop_sysset')->where('aid',aid)->value('coupon_not_used_discount');
                if(($coupon_not_used_discount ==1 || $not_used_discount==1) && $data['couponrid'] ){
                    $totalprice = $totalprice + $leveldk_money;
                    $leveldk_money = 0;
                }
                
            }
			//促销活动
            $cuxiaomoney = 0;
            if(getcustom('multi_promotion')){
                if($data['cuxiaoid']) {
                    foreach ($data['cuxiaoid'] as $cuxiaoid) {
                        if($cuxiaoid > 0){
                            $cuxiaoid = $cuxiaoid;
                            $cuxiaoinfo = Db::name('cuxiao')->where("bid=-1 or bid=".$data['bid'])->where('aid',aid)->where('id',$cuxiaoid)->find();
                            if(!$cuxiaoinfo){
                                return $this->json(['status'=>0,'msg'=>'该促销活动不存在']);
                            }elseif($cuxiaoinfo['starttime'] > time()){
                                return $this->json(['status'=>0,'msg'=>'该促销活动尚未开始']);
                            }elseif($cuxiaoinfo['endtime'] < time()){
                                return $this->json(['status'=>0,'msg'=>'该促销活动已结束']);
                            }elseif($cuxiaoinfo['type']!=5 && $cuxiaoinfo['type']!=6 && $cuxiaoinfo['minprice'] > $totalprice){
                                return $this->json(['status'=>0,'msg'=>'该促销活动不符合条件']);
                            }elseif(($cuxiaoinfo['type']==5 || $cuxiaoinfo['type']==6) && $cuxiaoinfo['minnum'] > $totalnum){
                                return $this->json(['status'=>0,'msg'=>'该促销活动不符合条件']);
                            }
                            if($cuxiaoinfo['fwtype']==2){//指定商品可用
                                $productids = explode(',',$cuxiaoinfo['productids']);
                                if(!array_intersect($proids,$productids)){
                                    return $this->json(['status'=>0,'msg'=>'该促销活动指定商品可用']);
                                } 
                                if($cuxiaoinfo['type']==1 || $cuxiaoinfo['type']==2 || $cuxiaoinfo['type']==3 || $cuxiaoinfo['type']==4){//指定商品是否达到金额要求
                                    $thistotalprice = 0;
                                    foreach($prolist as $k2=>$v2){
                                        $product = $v2['product'];
                                        if(in_array($product['id'],$productids)){
                                            $thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
                                        }
                                    }
                                    if($thistotalprice < $cuxiaoinfo['minprice']){
                                        return $this->json(['status'=>0,'msg'=>'该促销活动指定商品总价未达到'.$cuxiaoinfo['minprice'].'元']);
                                    }
                                }
                                if($cuxiaoinfo['type']==6 || $cuxiaoinfo['type']==5){//指定商品是否达到件数要求
                                    $thistotalnum = 0;
                                    foreach($prolist as $k2=>$v2){
                                        $product = $v2['product'];
                                        if(in_array($product['id'],$productids)){
                                            $thistotalnum += $v2['num'];
                                        }
                                    }
                                    if($thistotalnum < $cuxiaoinfo['minnum']){
                                        return $this->json(['status'=>0,'msg'=>'该促销活动指定商品总数未达到'.$cuxiaoinfo['minnum'].'件']);
                                    }
                                }
                            }
                            if($cuxiaoinfo['fwtype']==1){//指定类目可用
                                $categoryids = explode(',',$cuxiaoinfo['categoryids']);
                                $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                                foreach($clist as $kc=>$vc){
                                    $categoryids[] = $vc['id'];
                                    $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                                    if($cate2) $categoryids[] = $cate2['id'];
                                }
                                if(!array_intersect($cids,$categoryids)){
                                    return $this->json(['status'=>0,'msg'=>'该促销活动指定分类可用']);
                                }
								if($cuxiaoinfo['type']==1 || $cuxiaoinfo['type']==2 || $cuxiaoinfo['type']==3 || $cuxiaoinfo['type']==4){//指定商品是否达到金额要求
                                    $thistotalprice = 0;
                                    foreach($prolist as $k2=>$v2){
                                        $product = $v2['product'];
                                        if(array_intersect(explode(',',$product['cid']),$categoryids)){
                                            $thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
                                        }
                                    }
                                    if($thistotalprice < $cuxiaoinfo['minprice']){
                                        return $this->json(['status'=>0,'msg'=>'该促销活动指定分类总价未达到'.$cuxiaoinfo['minprice'].'元']);
                                    }
                                }
                                if($cuxiaoinfo['type']==6 || $cuxiaoinfo['type']==5){//指定类目内商品是否达到件数要求
                                    $thistotalnum = 0;
                                    foreach($prolist as $k2=>$v2){
                                        $product = $v2['product'];
                                        if(array_intersect(explode(',',$product['cid']),$categoryids)){
                                            $thistotalnum += $v2['num'];
                                        }
                                    }
                                    if($thistotalnum < $cuxiaoinfo['minnum']){
                                        return $this->json(['status'=>0,'msg'=>'该促销活动指定分类总数未达到'.$cuxiaoinfo['minnum'].'件']);
                                    }
                                }
                            }
                            if($cuxiaoinfo['type']==1 || $cuxiaoinfo['type']==6){//满额立减 满件立减
                                $manjian_money = $manjian_money + $cuxiaoinfo['money'];
                                $cuxiaomoney += $cuxiaoinfo['money'] * -1;
                            }elseif($cuxiaoinfo['type']==2){//满额赠送
                                $cuxiaomoney += 0;
                                $product = Db::name('shop_product')->where('aid',aid)->where('id',$cuxiaoinfo['proid'])->find();
                                $guige = Db::name('shop_guige')->where('aid',aid)->where('id',$cuxiaoinfo['ggid'])->find();
                                if(!$product) return $this->json(['status'=>0,'msg'=>'赠送产品不存在']);
                                if(!$guige) return $this->json(['status'=>0,'msg'=>'赠送产品规格不存在']);
                                if($guige['stock'] < 1){
                                    return $this->json(['status'=>0,'msg'=>'赠送产品库存不足']);
                                }
                                $prolist[] = ['product'=>$product,'guige'=>$guige,'num'=>1,'isSeckill'=>0,'gtype'=>1];
                            }elseif($cuxiaoinfo['type']==3){//加价换购
                                $cuxiaomoney += $cuxiaoinfo['money'];
                                $product = Db::name('shop_product')->where('aid',aid)->where('id',$cuxiaoinfo['proid'])->find();
                                $guige = Db::name('shop_guige')->where('aid',aid)->where('id',$cuxiaoinfo['ggid'])->find();
                                if(!$product) return $this->json(['status'=>0,'msg'=>'换购产品不存在']);
                                if(!$guige) return $this->json(['status'=>0,'msg'=>'换购产品规格不存在']);
                                if($guige['stock'] < 1){
                                    return $this->json(['status'=>0,'msg'=>'换购产品库存不足']);
                                }
                                $prolist[] = ['product'=>$product,'guige'=>$guige,'num'=>1,'isSeckill'=>0];
                            }elseif($cuxiaoinfo['type']==4 || $cuxiaoinfo['type']==5){//满额打折 满件打折
                                $cuxiaomoney4 = 0;
                                if($cuxiaoinfo['fwtype']==2){
                                    $prozkArr = array_combine(explode(',',$cuxiaoinfo['productids']),explode(',',$cuxiaoinfo['prozk']));
                                    $pronumArr = array_combine(explode(',',$cuxiaoinfo['productids']),explode(',',$cuxiaoinfo['pronum']));
                                    foreach($prolist as $k=>$v){
                                        $product = $v['product'];
                                        if($prozkArr[$product['id']]){
                                            $prozk = $prozkArr[$product['id']];
                                        }elseif(isset($prozkArr[$product['id']])){
                                            $prozk = $cuxiaoinfo['zhekou'];
                                        }else{
                                            $prozk = 10;
                                        }
                                        if($cuxiaoinfo['type']==5 && $pronumArr[$product['id']] && intval($pronumArr[$product['id']]) > $v['num']){
                                            $prozk = 10;
                                        }
                                        $cuxiaomoney4 += $product_priceArr[$k] * (1 - $prozk * 0.1);
                                    }
                                }elseif($cuxiaoinfo['fwtype']==1) {
                                    //分类
                                    $categoryPrice = 0;
                                    foreach ($prolist as $k2 => $v2) {
                                        $product = $v2['product'];
                                        $cids2 = explode(',', $product['cid']);
                                        if(array_intersect($cids2, $categoryids)) {
                                            $categoryPrice += $v2['guige']['sell_price'] * $v2['num'];
                                        }
                                    }
                                    $cuxiaomoney4 = $categoryPrice * (1 - $cuxiaoinfo['zhekou'] * 0.1);
                                }else{
                                    $cuxiaomoney4 = $totalprice * (1 - $cuxiaoinfo['zhekou'] * 0.1);
                                }
                                $cuxiaomoney4 = round($cuxiaomoney4,2);
                                $manjian_money = $manjian_money + $cuxiaomoney4;
                                $cuxiaomoney += $cuxiaomoney4 * -1;
                            }else{
                                $cuxiaomoney += 0;
                            }
                        }else{
                            $cuxiaomoney += 0;
                        }
                    }
                }
            } else {
                if($data['cuxiaoid'] > 0){
                    $cuxiaoid = $data['cuxiaoid'];
                    $cuxiaoinfo = Db::name('cuxiao')->where("bid=-1 or bid=".$data['bid'])->where('aid',aid)->where('id',$cuxiaoid)->find();
                    if(!$cuxiaoinfo){
                        return $this->json(['status'=>0,'msg'=>'该促销活动不存在']);
                    }elseif($cuxiaoinfo['starttime'] > time()){
                        return $this->json(['status'=>0,'msg'=>'该促销活动尚未开始']);
                    }elseif($cuxiaoinfo['endtime'] < time()){
                        return $this->json(['status'=>0,'msg'=>'该促销活动已结束']);
                    }elseif($cuxiaoinfo['type']!=5 && $cuxiaoinfo['type']!=6 && $cuxiaoinfo['minprice'] > $totalprice){
                        return $this->json(['status'=>0,'msg'=>'该促销活动不符合条件']);
                    }elseif(($cuxiaoinfo['type']==5 || $cuxiaoinfo['type']==6) && $cuxiaoinfo['minnum'] > $totalnum){
                        return $this->json(['status'=>0,'msg'=>'该促销活动不符合条件']);
                    }
                    if($cuxiaoinfo['fwtype']==2){//指定商品可用
                        $productids = explode(',',$cuxiaoinfo['productids']);
                        if(!array_intersect($proids,$productids)){
                            return $this->json(['status'=>0,'msg'=>'该促销活动指定商品可用']);
                        }
						if($cuxiaoinfo['type']==1 || $cuxiaoinfo['type']==2 || $cuxiaoinfo['type']==3 || $cuxiaoinfo['type']==4){//指定商品是否达到金额要求
							$thistotalprice = 0;
							foreach($prolist as $k2=>$v2){
								$product = $v2['product'];
								if(in_array($product['id'],$productids)){
									$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
								}
							}
							if($thistotalprice < $cuxiaoinfo['minprice']){
								return $this->json(['status'=>0,'msg'=>'该促销活动指定商品未达到'.$cuxiaoinfo['minprice'].'元']);
							}
						}
                        if($cuxiaoinfo['type']==6 || $cuxiaoinfo['type']==5){//指定商品是否达到件数要求
                            $thistotalnum = 0;
                            foreach($prolist as $k2=>$v2){
                                $product = $v2['product'];
                                if(in_array($product['id'],$productids)){
                                    $thistotalnum += $v2['num'];
                                }
                            }
                            if($thistotalnum < $cuxiaoinfo['minnum']){
                                return $this->json(['status'=>0,'msg'=>'该促销活动指定商品未达到'.$cuxiaoinfo['minnum'].'件']);
                            }
                        }
                    }
                    if($cuxiaoinfo['fwtype']==1){//指定类目可用
                        $categoryids = explode(',',$cuxiaoinfo['categoryids']);
                        $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                        foreach($clist as $kc=>$vc){
                            $categoryids[] = $vc['id'];
                            $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                            if($cate2) $categoryids[] = $cate2['id'];
                        }
                        if(!array_intersect($cids,$categoryids)){
                            return $this->json(['status'=>0,'msg'=>'该促销活动指定分类可用']);
                        }
						if($cuxiaoinfo['type']==1 || $cuxiaoinfo['type']==2 || $cuxiaoinfo['type']==3 || $cuxiaoinfo['type']==4){//指定商品是否达到金额要求
							$thistotalprice = 0;
							foreach($prolist as $k2=>$v2){
								$product = $v2['product'];
								if(array_intersect(explode(',',$product['cid']),$categoryids)){
									$thistotalprice += $v2['guige']['sell_price'] * $v2['num'];
								}
							}
							if($thistotalprice < $cuxiaoinfo['minprice']){
								return $this->json(['status'=>0,'msg'=>'该促销活动指定分类未达到'.$cuxiaoinfo['minprice'].'元']);
							}
						}
                        if($cuxiaoinfo['type']==6 || $cuxiaoinfo['type']==5){//指定类目内商品是否达到件数要求
                            $thistotalnum = 0;
                            foreach($prolist as $k2=>$v2){
                                $product = $v2['product'];
                                if(array_intersect(explode(',',$product['cid']),$categoryids)){
                                    $thistotalnum += $v2['num'];
                                }
                            }
                            if($thistotalnum < $cuxiaoinfo['minnum']){
                                return $this->json(['status'=>0,'msg'=>'该促销活动指定分类未达到'.$cuxiaoinfo['minnum'].'件']);
                            }
                        }
                    }
                    if($cuxiaoinfo['type']==1 || $cuxiaoinfo['type']==6){//满额立减 满件立减
                        $manjian_money = $manjian_money + $cuxiaoinfo['money'];
                        $cuxiaomoney = $cuxiaoinfo['money'] * -1;
                        // 单独价格
                        if(getcustom('yx_cuxiao_dandu_price') && $cuxiaoinfo['type']==6 && $cuxiaoinfo['fwtype']==2){
                            //商品
                            $is_dandu = 0;
                            $dandumoneyall = 0;
                            $propriceArr = array_combine(explode(',', $cuxiaoinfo['productids']), explode(',', $cuxiaoinfo['proprice']));
                            $pronumArr = array_combine(explode(',', $cuxiaoinfo['productids']), explode(',', $cuxiaoinfo['pronum']));
                            foreach ($prolist as $k2 => $v2) {
                                $product = $v2['product'];
                                if (($propriceArr[$product['id']] || $propriceArr[$product['id']] ==='0') && intval($pronumArr[$product['id']]) <= $v2['num']) {
                                    $dandumoney = $propriceArr[$product['id']] * $v2['num'];
                                    $is_dandu = 1;
                                // } elseif (isset($propriceArr[$product['id']]) && intval($pronumArr[$product['id']]) <= $v2['num']) {
                                //     $dandumoney = $product_priceArr[$k2]-$cuxiaoinfo['money'];
                                } else {
                                    $dandumoney =  $product_priceArr[$k2];
                                }
                                $dandumoneyall += $dandumoney;
                            }
                            $dandumoneyall = round($dandumoneyall,2);
                        }
                    }elseif($cuxiaoinfo['type']==2){//满额赠送
                        $cuxiaomoney = 0;
                        $product = Db::name('shop_product')->where('aid',aid)->where('id',$cuxiaoinfo['proid'])->find();
                        $guige = Db::name('shop_guige')->where('aid',aid)->where('id',$cuxiaoinfo['ggid'])->find();
                        if(!$product) return $this->json(['status'=>0,'msg'=>'赠送产品不存在']);
                        if(!$guige) return $this->json(['status'=>0,'msg'=>'赠送产品规格不存在']);
                        if($guige['stock'] < 1){
                            return $this->json(['status'=>0,'msg'=>'赠送产品库存不足']);
                        }
                        $prolist[] = ['product'=>$product,'guige'=>$guige,'num'=>1,'isSeckill'=>0,'gtype'=>1];
                    }elseif($cuxiaoinfo['type']==3){//加价换购
                        $cuxiaomoney = $cuxiaoinfo['money'];
                        $product = Db::name('shop_product')->where('aid',aid)->where('id',$cuxiaoinfo['proid'])->find();
                        $guige = Db::name('shop_guige')->where('aid',aid)->where('id',$cuxiaoinfo['ggid'])->find();
                        if(!$product) return $this->json(['status'=>0,'msg'=>'换购产品不存在']);
                        if(!$guige) return $this->json(['status'=>0,'msg'=>'换购产品规格不存在']);
                        if($guige['stock'] < 1){
                            return $this->json(['status'=>0,'msg'=>'换购产品库存不足']);
                        }
                        $prolist[] = ['product'=>$product,'guige'=>$guige,'num'=>1,'isSeckill'=>0];
                    }elseif($cuxiaoinfo['type']==4 || $cuxiaoinfo['type']==5){//满额打折 满件打折
                        if($cuxiaoinfo['fwtype']==2){
                            $cuxiaomoney = 0;
                            $prozkArr = array_combine(explode(',',$cuxiaoinfo['productids']),explode(',',$cuxiaoinfo['prozk']));
                            $pronumArr = array_combine(explode(',',$cuxiaoinfo['productids']),explode(',',$cuxiaoinfo['pronum']));
                            foreach($prolist as $k=>$v){
                                $product = $v['product'];
                                if($prozkArr[$product['id']]){
                                    $prozk = $prozkArr[$product['id']];
                                }elseif(isset($prozkArr[$product['id']])){
                                    $prozk = $cuxiaoinfo['zhekou'];
                                }else{
                                    $prozk = 10;
                                }
                                if($cuxiaoinfo['type']==5 && $pronumArr[$product['id']] && intval($pronumArr[$product['id']]) > $v['num']){
                                    $prozk = 10;
                                }
                                $cuxiaomoney += $product_priceArr[$k] * (1 - $prozk * 0.1);
                            }
                        }elseif($cuxiaoinfo['fwtype']==1) {
							//分类
							$categoryPrice = 0;
							foreach ($prolist as $k2 => $v2) {
								$product = $v2['product'];
								$cids2 = explode(',', $product['cid']);
								if(array_intersect($cids2, $categoryids)) {
									$categoryPrice += $v2['guige']['sell_price'] * $v2['num'];
								}
							}
							$cuxiaomoney = $categoryPrice * (1 - $cuxiaoinfo['zhekou'] * 0.1);
						}else{
                            //全部
                            $cuxiaomoney = $totalprice * (1 - $cuxiaoinfo['zhekou'] * 0.1);
                        }
                        $cuxiaomoney = round($cuxiaomoney,2);
                        $manjian_money = $manjian_money + $cuxiaomoney;
                        $cuxiaomoney = $cuxiaomoney * -1;
                    }else{
                        $cuxiaomoney = 0;
                    }
                    if(getcustom('plug_tengrui')) {
                        $tr_check = new \app\common\TengRuiCheck();
                        //判断是否是否符合会员认证、会员关系、一户
                        $check_cuxiao = $tr_check->check_cuxiao($this->member,$cuxiaoinfo);
                        if($check_cuxiao && $check_cuxiao['status'] == 0){
                            $cuxiaomoney = 0;
                        }
                        $cuxiao_tr_roomId = $check_cuxiao['tr_roomId'];
                    }
                }else{
                    $cuxiaomoney = 0;
                }
            }
            if(getcustom('yx_cuxiao_dandu_price') && !empty($is_dandu) && isset($dandumoneyall)){
                $totalprice = $dandumoneyall;
            }else{
                $totalprice = $totalprice - $coupon_money + $cuxiaomoney;
            }
            if(getcustom('deposit')){
                $totalprice = $totalprice - $water_coupon_money;
            }
			$totalprice = $totalprice + $new_freight_price;
//            if(getcustom('deposit')){
//                $totalprice +=$deposit_payprice;
//            }
          
            //发票
            $invoice_money = 0;
            if($store_info['invoice'] && $store_info['invoice_rate'] > 0 && $data['invoice']){
                $invoice_money = round($totalprice * $store_info['invoice_rate'] / 100,2);
                $totalprice = $totalprice + $invoice_money;
            }
			//积分抵扣
			$scoredkscore = 0;
			$scoredk_money = 0;
			if($post['usescore']==1 && $business_selfscore == 0){
				$score2money = $this->sysset['score2money'];
				$scoredkmaxpercent = $this->sysset['scoredkmaxpercent'];
				$scorebdkyf = $this->sysset['scorebdkyf'];
				$scoredk_money = ($this->member['score'] - $alltotalscore) * $score2money;
				if($scorebdkyf == 1){//积分不抵扣运费
					if($scoredk_money > $totalprice - $new_freight_price) $scoredk_money = $totalprice - $new_freight_price;
				}else{
					if($scoredk_money > $totalprice) $scoredk_money = $totalprice;
				}
				if($scoremaxtype == 0){
					if($scoredkmaxpercent >= 0 && $scoredkmaxpercent <= 100 && $scoredk_money > 0 && $scoredk_money > $totalprice * $scoredkmaxpercent * 0.01){
						$scoredk_money = $totalprice * $scoredkmaxpercent * 0.01;
					}
				}else{
					if($scoredk_money > $scoredkmaxmoney) $scoredk_money = $scoredkmaxmoney;
				}
				$totalprice = $totalprice - $scoredk_money;
				$totalprice = round($totalprice*100)/100;
				if($scoredk_money > 0){
					$scoredkscore = $scoredk_money / $score2money;
					$scoredkscore = dd_score_format($scoredkscore,$score_weishu);
				}
			}
			if(getcustom('business_selfscore') && $business_selfscore == 1 && $data['usescore']){
				if($data['bid'] == 0){
					$score2money = $this->sysset['score2money'];
					$scoredkmaxpercent = $this->sysset['scoredkmaxpercent'];
					$scorebdkyf = $this->sysset['scorebdkyf'];
					$scoredk_money = round(($this->member['score'] - $alltotalscore) * $score2money,$score_weishu);
				}else{
					$business = Db::name('business')->where('id',$data['bid'])->find();
					$memberscore = Db::name('business_memberscore')->where('aid',aid)->where('bid',$data['bid'])->where('mid',$ordermid)->value('score');
					if(!$memberscore) $memberscore = 0;
					$score2money = $business['scoreset']==0 ? $this->sysset['score2money'] : $business['score2money'];
					$scoredkmaxpercent = $business['scoreset']==0 ? $this->sysset['scoredkmaxpercent'] : $business['scoredkmaxpercent'];
					$scorebdkyf = $business['scoreset']==0 ? $this->sysset['score2money'] : $business['scorebdkyf'];
					$scoredk_money = round($memberscore * $score2money,$score_weishu);
				}
				if($scorebdkyf == 1){//积分不抵扣运费
					if($scoredk_money > $totalprice - $new_freight_price) $scoredk_money = $totalprice - $new_freight_price;
				}else{
					if($scoredk_money > $totalprice) $scoredk_money = $totalprice;
				}
				if($scoremaxtype == 0){
					if($scoredkmaxpercent >= 0 && $scoredkmaxpercent <= 100 && $scoredk_money > 0 && $scoredk_money > $totalprice * $scoredkmaxpercent * 0.01){
						$scoredk_money = $totalprice * $scoredkmaxpercent * 0.01;
					}
				}else{
					if($scoredk_money > $scoredkmaxmoney) $scoredk_money = $scoredkmaxmoney;
				}
				$totalprice = $totalprice - $scoredk_money;
				$totalprice = round($totalprice*100)/100;
				if($scoredk_money > 0){
					$scoredkscore = $scoredk_money / $score2money;
                    $scoredkscore = dd_score_format($scoredkscore,$score_weishu);
				}
			}
            if(getcustom('money_dec')){
            	$dec_money = 0;//余额抵扣金额
                //是否使用余额抵扣
                if($data['moneydec_rate']>0 && $this->member['money']>0){
                    if($money_dec_type && $money_dec_type ==1){
                        if(getcustom('money_dec_product')){
                            $usermoney = $this->member['money'];//用户剩余余额，下方计算时余额会减少
                            //存在商品单独设置，则计算每个商品金额的最大抵扣金额
                            foreach($prolist as &$pv){
                                //如果商品关闭余额抵扣则跳过计算
                                if($pv['product']['moneydecset']==-1) continue;

                                $pre_dec_money = 0;//当前商品抵扣金额
                                $pre_product_price = $pv['guige']['sell_price'] * $pv['num'];//当前数量购买价格
                                if($pv['product']['moneydecset'] >=1){
                                    if($pv['product']['moneydecval'] && $pv['product']['moneydecval']>0){
                                        if($pv['product']['moneydecset'] == 1){
                                            $pre_dec_money = $pre_product_price*$pv['product']['moneydecval']/100;
                                        }else if($pv['product']['moneydecset'] == 2){
                                            $pre_dec_money = $pv['product']['moneydecval'];
                                        }
                                    }
                                }else{
                                    $pre_dec_money = $pre_product_price*$money_dec_rate/100;
                                }
                                $pre_dec_money = round($pre_dec_money,2);//四舍五入当前商品抵扣金额
                                if($pre_dec_money>$pre_product_price){
                                    $pre_dec_money =$pre_product_price;
                                }
                                //判断当前商品抵扣金额与用户剩余余额的大小
                                if($pre_dec_money>$usermoney){
                                	$pre_dec_money = $usermoney;
                                }
                                $usermoney -= $pre_dec_money;//减少用户剩余余额
                                
                                //判断支付总额与当前商品抵扣金额的大小
                                if($totalprice<$pre_dec_money){
                                    $pre_dec_money= $totalprice;//重置当前商品抵扣金额
                                    $dec_money   += $pre_dec_money;//增加总抵扣金额
                                    $totalprice   = 0;
                                    $pv['product']['dec_money'] = $pre_dec_money;
                                    break;
                                }else{
                                	$dec_money += $pre_dec_money;//增加总抵扣金额
                                	$totalprice-= $pre_dec_money;//减少支付总额
                                    $pv['product']['dec_money'] = $pre_dec_money;
                                }
                                //用户余额小于等于0，则停止计算
                                if($usermoney<=0) break;
                            }
                            unset($pv);
                        }
                    }else{
                        $dec_money = $totalprice*$money_dec_rate/100;
                        $dec_money = round($dec_money,2);
                        if($dec_money>= $this->member['money']){
                            $dec_money = $this->member['money'];
                        }
                        if($totalprice<$dec_money){
                            $dec_money  = $totalprice;
                            $totalprice = 0;
                        }else{
                        	$totalprice-= $dec_money;
                        }
                    }
                }
            }

            if(getcustom('product_givetongzheng')){
                if($post['usetongzheng']==1){
                    $tongzheng2money = $this->sysset['tongzheng2money'];
                    $tongzhengdkmaxpercent = $this->sysset['tongzhengdkmaxpercent'];
                    $tongzhengbdkyf = 0;//$this->sysset['tongzhengbdkyf'];
                    $tongzhengdk_money = ($this->member['tongzheng'] - $alltotaltongzheng) * $tongzheng2money;
                    if($tongzhengbdkyf == 1){//积分不抵扣运费
                        if($tongzhengdk_money > $totalprice - $new_freight_price) $tongzhengdk_money = $totalprice - $new_freight_price;
                    }else{
                        if($tongzhengdk_money > $totalprice) $tongzhengdk_money = $totalprice;
                    }
                    if($tongzhengmaxtype == 0){
                        if($tongzhengdkmaxpercent >= 0 && $tongzhengdkmaxpercent < 100 && $tongzhengdk_money > 0 && $tongzhengdk_money > $totalprice * $tongzhengdkmaxpercent * 0.01){
                            $tongzhengdk_money = $totalprice * $tongzhengdkmaxpercent * 0.01;
                        }
                    }else{
                        if($tongzhengdk_money > $tongzhengdkmaxmoney) $tongzhengdk_money = $tongzhengdkmaxmoney;
                    }
                    $totalprice = $totalprice - $tongzhengdk_money;
                    $totalprice = round($totalprice*100)/100;
                    if($tongzhengdk_money > 0){
                        $tongzhengdktongzheng = $tongzhengdk_money / $tongzheng2money;
                        $tongzhengdktongzheng = dd_money_format($tongzhengdktongzheng,3);
                    }
                }
            }
			if(getcustom('weight_template')){
				$totalprice = $totalprice+$totalweight_price;
			}

            if(getcustom('yx_order_discount_rand') && count($buydata) == 1){
                //随机立减
                $order_discount_rand = Db::name('order_discount_rand')->where('aid',aid)->where('bid',$bid)->find();
                if($order_discount_rand['status'] == 1 && $totalprice >= $order_discount_rand['order_price_min'] && $order_discount_rand['money_min'] < $totalprice){
                    $order_discount_status = true;
                    $order_discount_rand['order_types'] = explode(',',$order_discount_rand['order_types']);
                    if(!in_array('all',$order_discount_rand['order_types']) && !in_array('shop',$order_discount_rand['order_types'])){
                        $order_discount_status = false;
                    }
                    $gettj = explode(',',$order_discount_rand['gettj']);
                    if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)){ //不是所有人
                        if(in_array('0',$gettj)){ //关注用户才能领
                            if($this->member['subscribe']!=1){
                                $order_discount_status = false;
                            }
                        }else{
                            $order_discount_status = false;
                        }
                    }

                    if($order_discount_status){
                        if($order_discount_rand['money_max'] > $totalprice) $order_discount_rand['money_max'] = $totalprice;
                        $order_discount = mt_rand($order_discount_rand['money_min']*100,$order_discount_rand['money_max']*100)/100;
                        $totalprice -= $order_discount;
                    }
                }
            }
		
			if(getcustom('business_sales_quota')){
				if($data['bid']>0){
					$business2 = Db::name('business')->where(['aid'=>aid,'id'=>$data['bid']])->field('kctime,kctype,sales_quota,total_sales_quota')->find();
					$sales_price = $product_price;
					if($business2['kctype']==1){
						$sales_price = $totalprice;
					}
					$syquota = $business2['sales_quota']-$business2['total_sales_quota'];
					if($business2['sales_quota']>0 && $syquota<$sales_price){
						return $this->json(['status'=>0,'msg'=>'该商户商品额度不足']);
					}
				}
			}

            if(getcustom('member_goldmoney_silvermoney')){
                if($goldsilvertype >0){
                    //银值抵扣数额
                    $allsilvermoneydec = $allsilvermoneydec>0?round($allsilvermoneydec,2):0;
                    //金值抵扣数额
                    $allgoldmoneydec = $allgoldmoneydec>0?round($allgoldmoneydec,2):0;
                    if($goldsilvertype == 1 && $allsilvermoneydec>0){
                        if($allsilvermoneydec>=$totalprice){
                            $allsilvermoneydec = $totalprice;
                        }
                        $totalprice -= $allsilvermoneydec;
                    }else if($goldsilvertype == 2 && $allgoldmoneydec>0){
                        if($allgoldmoneydec>=$totalprice){
                            $allgoldmoneydec = $totalprice;
                        }
                        $totalprice -= $allgoldmoneydec;
                    }
					if($totalprice<0) $totalprice = 0;
                }
            }

            if(getcustom('member_dedamount')){
            	//如果有抵扣金额，计算抵扣金额能使用多少
                if($post['usededamount'] && $dedamount_dkmoney >0){
                    $chaprice = $totalprice - $dedamount_dkmoney;
                    //如果刚好抵扣完、或未全抵扣完，则这部分抵扣金全用；
                    if($chaprice>= 0){
                        $totalprice = $chaprice;
                    //如果抵扣超出，则抵扣金额等抵扣实际支付金额，实际支付金额等于0，会员变动抵扣金再加上差额
                    }else{
                        $dedamount_dkmoney = $totalprice;
                        $dedamount += -$chaprice;//增加会员变动抵扣金
                        $totalprice = 0;
                    }
                    if($totalprice<0) $totalprice = 0;

                    $dedamount_dkmoney2 = $dedamount_dkmoney;//赋值其他变量，用于下方商品计算
                }else{
                	$dedamount_dkmoney  = 0;
                	$dedamount_dkmoney2 = 0;
                }
            }

            if(getcustom('member_shopscore')){
            	//产品积分抵扣
				if($useshopscore){
					//如果是比例则重新计算
					if($shopscoremaxtype == 0){
						$nowshopscoredk_money = $totalprice * $shopscoredkmaxpercent * 0.01;
					}else{
						$nowshopscoredk_money = $shopscoredk_money;
					}

					if($nowshopscoredk_money>0){
						$nowshopscoredk_money = round($nowshopscoredk_money,2);
						//判断现在抵扣数值是否小于等于会员最大的抵扣数值
						if($nowshopscoredk_money<=$member_shopscoredk_money){
							$shopscoredk_money = $nowshopscoredk_money;
						}else{
							$shopscoredk_money = $member_shopscoredk_money;
						}
						//计算实际使用产品抵扣数量
						$totalpricecha = $totalprice - $shopscoredk_money;
						if($totalpricecha<0){
							$shopscoredk_money = $totalprice;
							$totalprice = 0;
						}else{
							$totalprice = round($totalpricecha,2);
						}
					}else{
						$shopscoredk_money = 0;
					}
				}
            }

			$orderdata = [];
			$orderdata['aid'] = aid;
			$orderdata['mid'] = $ordermid;
			$orderdata['bid'] = $data['bid'];
			if(count($buydata) > 1){
				$orderdata['ordernum'] = $ordernum.'_'.$i;
			}else{
				$orderdata['ordernum'] = $ordernum;
			}
			$orderdata['title'] = $title.(count($prodata)>1?'等':'');
			
			$orderdata['linkman'] = $address['name'];
            $orderdata['company'] = $address['company'];
			$orderdata['tel'] = $address['tel'];
			$orderdata['area'] = $address['area'];
			$orderdata['address'] = $address['address'];
			$orderdata['longitude'] = $address['longitude'];
			$orderdata['latitude'] = $address['latitude'];
			$orderdata['area2'] = $address['province'].','.$address['city'].','.$address['district'];
			if(getcustom('user_auth_province')){
                $orderdata['province'] = $address['province'];
                $orderdata['city'] = $address['city'];
            }
            if(getcustom('product_thali')){
                $orderdata['product_thali_student_name'] = $address['product_thali_student_name'] ?? '';
                $orderdata['product_thali_school'] = $address['product_thali_school'] ?? '';
            }
            if(getcustom('supply_zhenxin')){
                if(!$usegiveorder && $buydata['issource'] == 1 && $buydata['source'] == 'supply_zhenxin'){
                  	//甄新汇选必须有姓名、电话、地址
                	if(empty($orderdata['linkman']) || empty($orderdata['tel']) || empty($orderdata['area']) || empty($orderdata['address'])){
                		return $this->json(['status'=>0,'msg'=>'此订单商品必须填写姓名、电话、地址']);
                	}
                }
            }
            if(getcustom('supply_yongsheng')){
                if(!$usegiveorder && $buydata['issource'] == 1 && $buydata['source'] == 'supply_yongsheng'){
                  	//必须有姓名、电话、地址
                	if(empty($orderdata['linkman']) || empty($orderdata['tel']) || empty($orderdata['area2']) || empty($orderdata['address'])){
                		return $this->json(['status'=>0,'msg'=>'此订单商品必须填写姓名、电话、地址']);
                	}
                }
            }
			$orderdata['totalprice'] = $totalprice;
			$orderdata['product_price'] = $product_price;
			$orderdata['leveldk_money'] = $leveldk_money;	//会员折扣
			$orderdata['manjian_money'] = $manjian_money;	//满减活动
			$orderdata['scoredk_money'] = $scoredk_money;	//积分抵扣
			$orderdata['coupon_money'] = $coupon_money + $freight_price - $new_freight_price;	//优惠券抵扣
			$orderdata['scoredkscore'] = $scoredkscore;		//抵扣掉的积分
            if(getcustom('product_givetongzheng')){
                $orderdata['tongzhengdk_money'] = $tongzhengdk_money;	//积分抵扣
                $orderdata['tongzhengdktongzheng'] = $tongzhengdktongzheng;		//抵扣掉的积分
                if($tongzhengdktongzheng>0){
                    $res = \app\common\Member::addtongzheng(aid,mid,-$tongzhengdktongzheng,t('通证').'抵扣，订单号: '.$orderdata['ordernum']);
                    if($res['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>t('通证').'抵扣失败']);
                    }
                }
                $alltotaltongzheng = bcadd($alltotaltongzheng,$tongzhengdktongzheng,3);
            }
            if(getcustom('yx_order_discount_rand')){
                $orderdata['discount_rand_money'] = $order_discount ?? 0;	//随机立减
            }
            if(getcustom('product_pickup_device')){
                $orderdata['dgid'] = $device_goods['id']??0;
            }
            $orderdata['balance_price'] = $balance_price;	//尾款金额 定制的
			$orderdata['coupon_rid'] = $data['couponrid'];
			if(getcustom('deposit')){
                $orderdata['water_coupon_rid'] = $data['water_couponrid'];
                $orderdata['water_coupon_money'] = $water_coupon_money;
                $orderdata['water_coupon_num'] = implode(',',$water_coupon_num);
            }
			$orderdata['freight_price'] = $freight_price; //运费
            $orderdata['invoice_money'] = $invoice_money; //发票
			$orderdata['givescore'] = $givescore;
			$orderdata['givescore2'] = $givescore2;
            if(getcustom('yx_farm')){
                $orderdata['give_farmseed'] = $give_farmseed;
                $orderdata['give_farmseed2'] = $give_farmseed2;
            }
            if(getcustom('member_commission_max')){
                $orderdata['give_commission_max'] = $give_commission_max;
                $orderdata['give_commission_max2'] = $give_commission_max2;
            }
            if(getcustom('product_givetongzheng')){
                $orderdata['givetongzheng'] = $givetongzheng;
            }
            if(getcustom('consumer_value_add')){
                $orderdata['give_green_score'] = $give_green_score;
                $orderdata['give_bonus_pool'] = $give_bonus_pool;
                $orderdata['give_green_score2'] = $give_green_score2;
                $orderdata['give_bonus_pool2'] = $give_bonus_pool2;
                if(getcustom('greenscore_max')){
                    if($give_green_score>0){
                        $give_maximum = bcmul(bcmul($give_green_score,$consumer_set['green_score_price'],2),$consumer_set['maximum_set'],2);
                    }else{
                        $give_maximum = bcmul(bcmul($give_green_score2,$consumer_set['green_score_price'],2),$consumer_set['maximum_set'],2);
                    }
                    if(getcustom('add_commission_max') && $consumer_set['maximum_set_type']==0){
                        $give_maximum = bcadd($give_commission_max,$give_commission_max2,2);
                    }
                    $orderdata['give_maximum'] = $give_maximum;
                }
                if(getcustom('green_score_reserves')){
                    //订单进入预备金
                    $orderdata['give_green_score_reserves'] = $give_green_score_reserves;
                    $orderdata['give_green_score_reserves2'] = $give_green_score_reserves2;
                }
            }
            if(getcustom('commission_duipeng_score_withdraw')){
                $orderdata['give_withdraw_score'] = $give_withdraw_score;
                $orderdata['give_parent1_withdraw_score'] = $give_parent1_withdraw_score;
                $orderdata['give_parent1'] = $this->member['pid'];
            }
			if($freight && ($freight['pstype']==0 || $freight['pstype']==10)){ //快递
				$orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
				$orderdata['freight_type'] = $freight['pstype'];
			}
            elseif($freight && $freight['pstype']==1){ //到店自提
				$orderdata['mdid'] = $data['storeid'];
                if(empty($orderdata['mdid'])){
                    return $this->json(['status'=>0,'msg'=>'请选择门店']);
                }
				$mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
				$orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                if(getcustom('product_pickup_device')) {
                    $devicedata = input('param.devicedata');
                    list($device_no,$goods_lane,$dgid) = explode(',',$devicedata);
                    if ($device_no) {
                        $f_device =  Db::name('product_pickup_device')
                            ->where('aid',aid)->where('device_no',$device_no)->field('address,name')->find();
                        $orderdata['freight_text'] = $freight['name'].'['.$f_device['name'].']';
                    }
                }
				$orderdata['area2'] = $mendian['area'];
				$orderdata['freight_type'] = 1;
				if(getcustom('freight_selecthxbids') && $freight['bid']==0){
					$orderdata['freight_text'] = $freight['name'];
					$orderdata['area2'] = '';
					$orderdata['mdid'] = '-1';
					if(!$orderdata['longitude']){
						$orderdata['longitude'] = $post['longitude'];
						$orderdata['latitude'] = $post['latitude'];
					}
				}
				if(getcustom('mendian_no_select')){
                    $mdids = '';
                    foreach($prolist as $key=>$v) {
                        $product = $v['product'];
                        $mdids .= ','.$product['bind_mendian_ids'];
                    }
                    $mdids = ltrim($mdids,',');
                    $orderdata['mdids'] = $mdids;
                }
            }
            elseif($freight && $freight['pstype']==5){ //门店配送
                $orderdata['mdid'] = $data['storeid'];
                $mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
                if(empty($mendian)){
                    return $this->json(['status'=>0,'msg'=>'请选择门店']);
                }
                $orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                $orderdata['area2'] = $mendian['area'];
                $orderdata['freight_type'] = 5;
			}
            elseif($freight && $freight['pstype']==2){ //同城配送
                if(getcustom('tongcheng_mendian')){
                    $orderdata['mdid'] = $data['storeid'];
                    $mendian = Db::name('mendian')->where('aid',aid)->where('id',$data['storeid'])->find();
                    $orderdata['freight_text'] = $freight['name'].'['.$mendian['name'].']';
                }else{
                    $orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
                }

				$orderdata['freight_type'] = 2;
			}elseif($freight && $freight['pstype']==12){ //app配送
				$orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
				$orderdata['freight_type'] = 2;
			}elseif($freight && ($freight['pstype']==3 || $freight['pstype']==4)){ //自动发货 在线卡密
				$orderdata['freight_text'] = $freight['name'];
				$orderdata['freight_type'] = $freight['pstype'];
			}elseif($freight && $freight['pstype']==11){ //选择物流配送
				$type11pricedata = json_decode($freight['type11pricedata'],true);
				$orderdata['freight_text'] = $type11pricedata[$freight['type11key']]['name'].'('.$freight_price.'元)';
				$orderdata['freight_type'] = $freight['pstype'];
				$orderdata['freight_content'] = jsonEncode($type11pricedata[$freight['type11key']]);
			}else{
				$orderdata['freight_text'] = '包邮';
			}

            if($is_mendian_usercetner && empty($orderdata['mdid'])){
                //如果会员已经锁定门店，则锁定门店为会员锁定门店
                $orderdata['mdid'] = $this->member['lock_mdid'];
            }
			if($sysset['areafenhong_checktype'] == 1 && $orderdata['tel']){
				$addressrs = getaddressfromtel($orderdata['tel']);
				if($addressrs && $addressrs['province']){
					$orderdata['area2'] = $addressrs['province'].','.$addressrs['city'];
				}
			}
			$orderdata['freight_id'] = $freight['id'];
			$orderdata['freight_time'] = $data['freight_time']; //配送时间
			$orderdata['createtime'] = time();
			$orderdata['platform'] = platform;
			$orderdata['hexiao_code'] = random(16);
			if(getcustom('mendian_upgrade')){
				$admin = Db::name('admin')->where('id',aid)->field('mendian_upgrade_status')->find();
				if($admin['mendian_upgrade_status']==1 && $freight['bid']==0){
					$orderdata['hexiao_qr'] = createqrcode(m_url('pagesA/mendiancenter/hexiao?type=shop&co='.$orderdata['hexiao_code']));
				}else{
                    $orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co='.$orderdata['hexiao_code']));
                }
			}else{
				$orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co='.$orderdata['hexiao_code']));
			}
            if(getcustom('hexiao_member')){
                $orderdata['hexiao_code_member'] = mt_rand(1000,9999);
            }
            if(getcustom('buy_selectmember')){
                if($post['checkmemid']) $orderdata['checkmemid'] = $post['checkmemid'];
            }
            if(getcustom('pay_yuanbao')){
                $orderdata['total_yuanbao']   = $total_yuanbao;
                $orderdata['have_no_yuanbao'] = $have_no_yuanbao;
            }
            if(getcustom('plug_tengrui')){
                $orderdata['manjian_tr_roomId']  = $manjian_tr_roomId?$manjian_tr_roomId:0;
                $orderdata['cuxiao_tr_roomId']   = $cuxiao_tr_roomId?$cuxiao_tr_roomId:0;
                $orderdata['cuxiao_money']       = abs($cuxiaomoney);
                $orderdata['cuxiao_id']          = $cuxiaoid?$cuxiaoid:0;
            }
            if(getcustom('yx_moneypay')){
                $orderdata['cuxiao_money']       = abs($cuxiaomoney);
                $orderdata['cuxiao_id']          = $cuxiaoid?$cuxiaoid:0;
            }
            if(getcustom('shop_yuding')){
                if($product['stock'] <=0 && $product['yuding_stock'] > 0){
                    $orderdata['yuding_type'] = 1;//是否是预定
                }
            }
            if(getcustom('school_product')){
                //升级为当前等级时的操作数据
                $schoolMember = Db::name('school_member')->where('aid',aid)->where('mid',$ordermid)->find();
                if($schoolMember){
                    if($schoolMember['school_id']) {
                        $orderdata['school_id'] = $schoolMember['school_id'];
                        $orderdata['grade_id'] = $schoolMember['grade_id'];
                        $orderdata['class_id'] = $schoolMember['class_id'];
                    }
                    $memberContent = [];
                    if($schoolMember['level_order_id']){
                        $levelOrder = Db::name('member_levelup_order')->where('aid',aid)->where('id',$schoolMember['level_order_id'])->find();
                        //自定义字段是否有值
                        for ($n=0;$n<=20;$n++){
                            $field = 'form'.$n;
                            if(isset($levelOrder[$field]) && !empty($levelOrder[$field])){
                                $fieldValue = $levelOrder[$field];
                                $fieldArr = explode('^_^',$fieldValue);
                                $temp_item = [
                                    'key'=>$fieldArr[2]??'',
                                    'name'=>$fieldArr[0]??'',
                                    'value'=>$fieldArr[1]??''
                                ];
                                $memberContent[] = $temp_item;
                            }
                        }
                    }
                    if($memberContent){
                        $orderdata['member_content'] = json_encode($memberContent);
                    }
                }
            }
            if(getcustom('member_goldmoney_silvermoney')){
            	//抵扣金银值先进行判断是否足够
                if($goldsilvertype == 1 && $allsilvermoneydec>0){
                	$member_silvermoney = Db::name('member')->where('id',mid)->where('aid',aid)->value('silvermoney');
                    if($member_silvermoney < $allsilvermoneydec) {
                        return $this->json(['status'=>0,'msg'=>t('银值').'不足']);
                    }
                }else if($goldsilvertype == 2 && $allgoldmoneydec>0){
                	$member_goldmoney = Db::name('member')->where('id',mid)->where('aid',aid)->value('goldmoney');
                    if($member_goldmoney < $allgoldmoneydec) {
                        return $this->json(['status'=>0,'msg'=>t('金值').'不足']);
                    }
                }
            }
            if(getcustom('member_shopscore')){
                if($shopscoredk_money>0){
                    $shopscore = floor($shopscoredk_money/$shopscore2money*100)/100;
                    //再次查询用户产品积分
                    $member_shopscore = Db::name('member')->where('id',mid)->where('aid',aid)->value('shopscore');
                    if($member_shopscore < $shopscore) {
                        return $this->json(['status'=>0,'msg'=>t('产品积分').'不足']);
                    }
                    $orderdata['shopscore2money'] = $shopscore2money;//产品积分抵扣兑换比例
                    $orderdata['shopscore']       = $shopscore;//产品积分使用数量
                }
                $orderdata['shopscore_status'] = 0;//产品抵扣情况 0：未抵扣 1：已抵扣
                $orderdata['shopscoredk_money']  = $shopscoredk_money;//产品积分抵扣金额
            }
            if(getcustom('shop_buy_worknum')){
				$worknum = $post['worknum']?trim($post['worknum']):'';
	        	$worknum_status = false;
	        	if($freight && $freight['worknum_status']){
	        		$worknum_status = true;
	        	}
	        	if($worknum_status){
	        		if(empty($worknum)){
	        			return $this->json(['status'=>0,'msg'=>'请填写工号']);
	        		}

	        		if(!is_numeric($worknum)){
	        			return $this->json(['status'=>0,'msg'=>'工号必须为10位数字']);
	        		}
	        		$len = strlen($worknum);
	        		if($len != 10){
	        			return $this->json(['status'=>0,'msg'=>'工号必须为10位数字']);
	        		}
		        	
		        	$orderdata['worknum'] = $worknum;//工号
	        	};
	        }
			if(getcustom('weight_template')){
				$orderdata['weight_price'] = $totalweight_price;
			}
            if(getcustom('product_weight') && $productType==2){
                $orderdata['product_type'] = 2;
            }
            if(getcustom('product_supply_chain')){
            	if(!$usegiveorder){
                    $haidaiarea = \app\custom\Chain::getHaidaiArea($orderdata['area2']);
                    $orderdata['area_id'] = $haidaiarea['area_id'];
                    $orderdata['area_regionid'] = $haidaiarea['region_id'];
                }
                $orderdata['product_type'] = $productType;
                $orderdata['trade_type'] = $tradeType;
                $orderdata['supplier_status'] = 100;//待同步
                if(in_array($tradeType,['1101','1303'])){
                    $orderdata['supplier_status'] = 200;//待补充海关身份证信息
                }
            }
            if(getcustom('discount_code_zhongchuang') && $post['discount_code_zc']){
                $orderdata['discount_code_zc'] = $post['discount_code_zc'];
            }
            if(getcustom('product_handwork')){
				if($protypes && $protypes[0] == 3){
	        		$orderdata['ishand'] = 1;//是否是手工活商品 0 不是 1：是
	        	}
			}
            $score_weishu = $this->score_weishu;
            if(getcustom('reward_business_score')){
                $orderdata['reward_business_score']= bcmul($product['reward_business_score'],$totalnum,$score_weishu);
            }
            if(getcustom('product_service_fee')){
                //服务费
                $orderdata['service_fee'] = $serviceFee;
                $orderdata['service_fee_money'] = $serviceFeeNums;
            }
            if(getcustom('freeze_money')){
                $orderdata['total_freezemoney_price'] = $freezemoney_price;
            }
			$is_pay = 1;
			if(getcustom('shop_product_fenqi_pay')){
				$product_fenqi = [];
				$product_fenqi_num = 0;
				foreach($prolist as $key=>$v){
					if($v['product']['product_type'] == 5){
						$product_fenqi = $v['product'];
						$product_fenqi_num = $v['num'];
					}
				}	
			}
            if(getcustom('h5zb')){
                $orderdata['roomid'] = input('param.roomid/d',0);
            }
            if(getcustom('supply_zhenxin')){
                if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
                  $orderdata['issource'] = 1;
                  $orderdata['source']   = 'supply_zhenxin';
                  if(!$usegiveorder && $address){
                  	$shipAreaCode = $address['province_zxcode'].','.$address['city_zxcode'].','.$address['district_zxcode'];
                  	$orderdata['shipAreaCode'] = $shipAreaCode??'';
                  	$orderdata['usercard']     = $usercard??'';
                  	$orderdata['message']      = $data['formdata'] && $data['formdata']['form0']? $data['formdata']['form0']:'';
                  }
                }
            }
            if(getcustom('supply_yongsheng')){
                if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
                  $orderdata['issource'] = 1;
                  $orderdata['source']   = 'supply_yongsheng';
                }
            }
            if(getcustom('money_dec')){
                //是否使用余额抵扣
                $orderdata['money_dec_type'] = $money_dec_type;//余额抵扣类型 0：比例 1：金额
                if(!$orderdata['money_dec_type']) $orderdata['money_dec_rate'] = $money_dec_rate;//余额抵扣比例
                $orderdata['dec_money'] = $dec_money;//余额抵扣比例
                if($dec_money>0){
                    if($dec_money >$this->member['money']){
                        return $this->json(['status'=>0,'msg'=>t('余额').'不足']);
                    }
                    $res = \app\common\Member::addmoney(aid,mid,-$dec_money,t('余额').'抵扣，订单号: '.$orderdata['ordernum']);
                    if($res['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>t('余额').'抵扣失败']);
                    }
                }
            }
            if(getcustom('member_goldmoney_silvermoney')){
            	//抵扣金银值
                if($goldsilvertype == 1 && $allsilvermoneydec>0){
                	$res = \app\common\Member::addsilvermoney(aid,mid,-$allsilvermoneydec,t('银值').'抵扣，订单号: '.$orderdata['ordernum'],$orderdata['ordernum']);
                    if($res['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>t('银值').'抵扣失败']);
                    }
                    $orderdata['silvermoneydec'] = $allsilvermoneydec;
                }else if($goldsilvertype == 2 && $allgoldmoneydec>0){
                	$res = \app\common\Member::addgoldmoney(aid,mid,-$allgoldmoneydec,t('金值').'抵扣，订单号: '.$orderdata['ordernum'],$orderdata['ordernum']);
                    if($res['status'] != 1){
                        return $this->json(['status'=>0,'msg'=>t('银值').'抵扣失败']);
                    }
                    $orderdata['goldmoneydec']   = $allgoldmoneydec;
                }

                //赠送金银值
                if($ShopSendSilvermoney) {
                    $orderdata['givesilvermoney'] = $givesilvermoney;
                }
                if($ShopSendGoldmoney) {
                    $orderdata['givegoldmoney']   = $givegoldmoney;
                }
            }
            if(getcustom('product_quanyi')){
                //权益商品
                $orderdata['product_type'] = $productType;
                $orderdata['hexiao_num_total'] = $hexiao_num_total;
            } 
            // dump($productType);die;
            if(getcustom('product_pingce') && $productType == 9){
                $pingce = ['user_id'=>mid,'name'=>$post['linkman'],
                    'email'=>$post['pcemail'],
                    'tel'=>$post['tel'],
                    'age'=>$post['age'],
                    'gender'=>$post['gender'],
                    'school'=>$post['school'],
                    'major'=>$post['major'],
                    'education'=>$post['education'],
                    'enrol'=>$post['enrol'],
                    'class_name'=>$post['class_name'],
                    'faculties'=>$post['faculties_name']
                ];
                $orderdata['pingce'] = json_encode($pingce);
                $orderdata['is_pingce'] =1;
            }
            if(getcustom('mendian_no_select') && getcustom('product_quanyi') && $productType==8){
                $mdids = '';
                foreach($prolist as $key=>$v) {
                    $product = $v['product'];
                    $mdids .= ','.$product['bind_mendian_ids'];
                }
                $mdids = ltrim($mdids,',');
                $orderdata['mdids'] = $mdids;
            }

            if(getcustom('member_level_moneypay_price')){
				$product_putongprice = $product_price + $product_price_cha;
				$orderdata['product_putongprice'] = $product_putongprice && $product_putongprice>0?$product_putongprice:0;//商品普通价格
				$totalputongprice = $totalprice + $product_price_cha;
				$orderdata['totalputongprice'] = $totalputongprice && $totalputongprice>0?$totalputongprice:0;//普通价格总价
            }
            if(getcustom('member_dedamount')){
                if($bid>0 && $store_info && $store_info['paymoney_givepercent']>0){
                    $orderdata['paymoney_givepercent'] = $store_info['paymoney_givepercent'];//商家让利比例
                    $orderdata['paymoney_givemoney']   = $paymoney_givemoney;//商家让利部分金额
                    if($dedamount_dkmoney>0){
                        //扣除抵扣金
                        $params=['ordernum'=>$orderdata['ordernum'],'paytype'=>'shop'];
                        $res = \app\common\Member::adddedamount(aid,$orderdata['bid'],mid,-$dedamount_dkmoney,'抵扣金抵扣，订单号: '.$orderdata['ordernum'],$params);
                        if($res && $res['status'] == 1){
                        	$dedamount2 = abs($res['dedamount']);
                        	$dedcha = $dedamount_dkmoney-$dedamount2;
                        	if($dedcha>0){
                        		$dedamount_dkmoney = $dedamount2;
                        		$orderdata['totalprice'] = $totalprice+$dedcha;
                        	}
                        }else{
                        	$msg = $res && $res['msg']?$res['msg']:'抵扣金抵扣失败';
                            return $this->json(['status'=>0,'msg'=>$msg]);
                        }
                    }
                    $orderdata['dedamount_dkpercent']= $dedamount_dkpercent;//抵扣金抵扣比例
                    $orderdata['dedamount_dkmoney']  = $dedamount_dkmoney;//抵扣金抵扣金额
                }else{
                    $orderdata['paymoney_givepercent'] = 0;//商家让利比例
                    $orderdata['paymoney_givemoney']   = 0;//商家让利部分金额
                    $orderdata['dedamount_dkpercent']  = 0;//抵扣金抵扣比例
                    $orderdata['dedamount_dkmoney']    = 0;//抵扣金抵扣金额
                }
            }
            if(getcustom('coupon_shop_times_coupon')){
                $orderdata['times_coupon_num'] = $totalcoupondknum;
            }
            if(getcustom('shop_giveorder')){
				//赠好友功能
				if($usegiveorder){
					$orderdata['usegiveorder']  = $usegiveorder;
					$orderdata['giveordertitle']= $giveordertitle?$giveordertitle:$orderdata['title'];
					$orderdata['giveorderpic']= $giveorderpic?$giveorderpic:'';
				}
			}
			if(getcustom('member_forzengxcommission')){
				if($protypes && $protypes[0] == 10){
	        		$orderdata['product_type'] = 10;//佣金类商品
	        	}
			}
			if(getcustom('yx_team_yeji_fenhong')){
                $orderdata['member_levelid'] = $this->member['levelid'];
            }
            if(getcustom('extend_planorder')){
            	$orderdata['poshopid'] = $poshopid;
            }
			if(getcustom('business_show_platform_product') && $this->member && $this->member['bind_business']){
				if($sysset['mode'] == 1 && $sysset['loc_business_show_type'] == 2){
					$orderdata['sell_business'] = $this->member['bind_business'];
				}
			}
			if(getcustom('business_hexiaoplatform_ticheng') && $businessHxMoney > 0){
				$orderdata['business_hx_money'] = $businessHxMoney;
			}
			if(getcustom('fuwu_usercenter')){
                $orderdata['fuwu_uid'] = $this->member['fuwu_uid']?:0;
            }
			if(getcustom('product_supplier_admin')){
                $orderdata['supplier_id'] = $supplier_id?:0;
            }
            if(getcustom('deposit')){
                $orderdata['deposit_totalprice'] = $deposit_payprice?:0;
            }
            if( getcustom('up_floor_fee')){
                $orderdata['up_floor_fee'] = floatval($up_floor_fee)?:0;
                //重置地址，加上楼层
                if($address['floor']){
                    $floor_room = $address['room']?$address['floor'].'层'.$address['room'].'号':$address['floor'].'层';
                    $orderdata['address'] = $address['address'].$floor_room;
                }
                $orderdata['floor'] =    $floor;
                $orderdata['room'] =    $room;
                $orderdata['have_dianti'] =    $have_dianti;
            }

            if( getcustom('shopbuy_sign')){
                $sign_set = $shopset;
                if($orderdata['bid'] > 0){
                    $sign_set = Db::name('business')->where(['aid' => aid,'id'=>$orderdata['bid']])->find();
                    $b_auth_data = Db::name('admin_user')->where('aid',aid)->where('bid',$orderdata['bid'])->field('auth_type,auth_data')->find();
                    $b_auth_data_arr = json_decode($b_auth_data['auth_data'],true);
                    if(!in_array('ShopbuySign',$b_auth_data_arr && $b_auth_data=='all')){
                        $sign_set['sign_contract'] == 0;
                    }
                }
                if($sign_set['sign_contract'] == 1){
                    if(!$sign_set['sign_contract_template']){
                        return $this->json(['status'=>0,'msg'=>'商家未上传签字合同模板']);
                    }
                    if(!$post['signatureurlShop']) {
                        return $this->json(['status' => 0, 'msg' => '未获取到签字文件']);
                    }

                    $sign_contract_file = $this->generate_contract($post['signatureurlShop'],$ordernum,$sign_set);
                    $orderdata['sign_contract_file'] = $sign_contract_file;
                    $orderdata['signatureurl'] = $post['signatureurlShop'];
                }
            }
//           print_r($orderdata);exit;
			$orderid = Db::name('shop_order')->insertGetId($orderdata);
            if(getcustom('deposit')){
                if($deposit_buy_list){
                    //押金购买
                    foreach($deposit_buy_list as $dk=>$deposit){
                        $deposit_ordernum = date('ymdHis').rand(100000,999999);
                        $deposit_data =  $deposit['data'];
                        $deposit_orderdata = [
                            'aid' => aid,
                            'bid' => $deposit_data['bid'],
                            'mid' => mid,
                            'deposit_id' => $deposit_data['id'],
                            'ordernum' => $deposit_ordernum,
                            'name' => $deposit_data['name'],
                            'num' => $deposit['num'],
                            'money' => $deposit_data['money'],
                            'totalprice' =>  dd_money_format($deposit['num'] * $deposit_data['money']),
                            'orderid' => $orderid,
                            'createtime' => time()
                        ];
                        $deposit_orderid = Db::name('deposit_order')->insertGetId($deposit_orderdata);
                    }
                    
                }
            }
			\app\model\Freight::saveformdata($orderid,'shop_order',$freight['id'],$data['formdata'],$extendInput);
			if(getcustom('shop_product_fenqi_pay')){
				if($product_fenqi){
					$fenqi_data = [];
					if(isset($post['fenqi_type']) && $post['fenqi_type'] == 1){
						$fenqi_data = json_decode($product_fenqi['fenqi_data'],true);
					}
					$now_data = date('Y-m-d');	
					$orderdata_totalprice = 0;	
					//分期付
					if($fenqi_data){			
							$i = 1;
							foreach($fenqi_data as $fenqi_select_data){
								$fenqi_num_radiao = $fenqi_select_data['fenqi_num_ratio'];
								$fenqi_money_one = round($orderdata['totalprice']*$fenqi_num_radiao*0.01,2);
								if($i == 1){
									$orderdata_totalprice = $fenqi_money_one;
								}
								$fenqi_fx_num = $fenqi_select_data['fenqi_fx_start_num'];
								if(empty($fenqi_fx_num)){
									$fenqi_fx_num = 0;
								}
								if(empty($fenqi_select_data['fenqi_give_num'])){
									$fenqi_select_data['fenqi_give_num'] = 0;
								}
								$j = $i - 1;
								$end_paytime = date("Y-m-d",strtotime("+{$j} month", strtotime($now_data)+86400));							
								
								$tem = [];
								$tem['fenqi_num']=$i;//期数
								$tem['fenqi_money']=$fenqi_money_one;//每期金额
								$tem['fenqi_give_num'] = $fenqi_select_data['fenqi_give_num'] * $product_fenqi_num;//赠送卡券数量
								$tem['fenqi_fx_num'] = $fenqi_fx_num *$product_fenqi_num;//赠送推荐人数量
								$tem['status'] = 0;//0未支付1已支付2已过期
								$tem['end_paytime'] = $end_paytime;//到期时间
								$tem['ordernum'] = $orderdata['ordernum'];
								$order_fenqi_data[$i] = $tem;
								$i++;
							}
							$orderdata_fenqi['fenqi_num'] = count($fenqi_data);
					}else{
						//一次性支付
						if(empty($product_fenqi['fenqigive_couponnum_fenxiao'])){
							$product_fenqi['fenqigive_couponnum_fenxiao'] = 0;
						}
						if(empty($product_fenqi['fenqigive_couponnum'])){
							$product_fenqi['fenqigive_couponnum'] = 0;
						}
						$orderdata_totalprice= round($orderdata['totalprice'],2);
						$tem['fenqi_num']=1;//期数
						$tem['fenqi_money']=$orderdata_totalprice;//每期金额					
						$tem['fenqi_give_num'] = $product_fenqi['fenqigive_couponnum'] * $product_fenqi_num;//赠送卡券数量
						$tem['fenqi_fx_num'] = $product_fenqi['fenqigive_couponnum_fenxiao'] * $product_fenqi_num;//赠送推荐人数量
						$tem['status'] = 0;//$product_fenqi
						$tem['ordernum'] = $orderdata['ordernum'];
						$order_fenqi_data[] = $tem;
						$orderdata_fenqi['fenqi_num'] = 1;
					}
					$orderdata_fenqi['fenqi_data'] = json_encode($order_fenqi_data,JSON_UNESCAPED_UNICODE);
					$orderdata_fenqi['is_fenqi'] = 1;
					$orderdata_fenqi['now_fenqi_num'] = 1;//当前支付期数
					$orderdata_fenqi['fenqi_one_paydate'] = date('Y-m-d');//第一期支付时间
					$orderdata_fenqi['fenqigive_couponid'] = $product_fenqi['fenqigive_couponid'];//赠送卡券id				
					$orderdata_fenqi['fenqigive_fx_couponid'] = $product_fenqi['fenqigive_fx_couponid'];//赠送卡券id
					$orderdata_fenqi['totalprice']		 = 	0;
					//return $this->json(['status'=>1,'msg'=>'修改成功','cartnum'=>$order_fenqi_data]);
					Db::name('shop_order')->where('id',$orderid)->update($orderdata_fenqi);

                    $payparams = [];//payorder表额外参数
                    if(getcustom('member_level_moneypay_price')){
                        $payparams['putongprice'] = $orderdata['totalputongprice'] && $orderdata['totalputongprice']>0?$orderdata['totalputongprice']:0;//所有商品普通价格总价
                    }
					$payorderid = \app\model\Payorder::createorder(aid,$orderdata['bid'],$orderdata['mid'],'shop_fenqi',$orderid,$orderdata['ordernum'],$orderdata['title'],$orderdata_totalprice,$orderdata['scoredkscore'],0,$payparams);
					Db::name('shop_order')->where('id',$orderid)->update(['payorderid'=>$payorderid]);
					$is_pay = 0;
				}

			}
			
			if($is_pay == 1){
                $payparams = [];//payorder表额外参数
                if(getcustom('member_level_moneypay_price')){
                    $payparams['putongprice'] = $orderdata['totalputongprice'] && $orderdata['totalputongprice']>0?$orderdata['totalputongprice']:0;//所有商品普通价格总价
                }
                $payorder_totalprice = $orderdata['totalprice'];
                if(getcustom('deposit')){
                    //押金的金额不计算在商城的金额中为了退款时不出错
                    $payorder_totalprice +=$deposit_payprice;
                }
				$payorderid = \app\model\Payorder::createorder(aid,$orderdata['bid'],$orderdata['mid'],'shop',$orderid,$orderdata['ordernum'],$orderdata['title'],$payorder_totalprice,$orderdata['scoredkscore'],$serviceFeeNums,$payparams,$orderdata['total_freezemoney_price']);
                if(getcustom('member_create_child_order')){
                    if(input('param.teammid/d')){          
                        Db::name('payorder')->where('aid',aid)->where('id',$payorderid)->update(['pmid' => mid]);
                    }
                }
			}

			if($balance_price > 0){
				$balancedata = [];
				$balancedata['aid'] = aid;
				$balancedata['bid'] = $orderdata['bid'];
				$balancedata['mid'] = $orderdata['mid'];
				$balancedata['orderid'] = $orderid;
				$balancedata['ordernum'] = $orderdata['ordernum'];
				$balancedata['title'] = $orderdata['title'];
				$balancedata['money'] = $orderdata['balance_price'];
				$balancedata['type'] = 'balance';
				$balancedata['score'] = 0;
				$balancedata['createtime'] = time();
				$balancedata['status'] = 0;
				$balance_pay_orderid = Db::name('payorder')->insertGetId($balancedata);
				Db::name('shop_order')->where('id',$orderid)->update(['balance_pay_orderid'=>$balance_pay_orderid]);
			}

			if(getcustom('shop_cod_frontpercent') && $shopset['cancod'] == 1 && $shopset['cod_frontpercent'] > 0){
				$frontdata = [];
				$frontdata['aid'] = aid;
				$frontdata['bid'] = $orderdata['bid'];
				$frontdata['mid'] = $orderdata['mid'];
				$frontdata['orderid'] = $orderid;
				$frontdata['ordernum'] = $orderdata['ordernum'].'_0';
				$frontdata['title'] = $orderdata['title'];
				$frontdata['money'] = $orderdata['totalprice'] * $shopset['cod_frontpercent'] * 0.01;
				$frontdata['type'] = 'shopfront';
				$frontdata['score'] = $orderdata['scoredkscore'];
				$frontdata['createtime'] = time();
				$frontdata['status'] = 0;
				$front_pay_orderid = Db::name('payorder')->insertGetId($frontdata);
			}

            //发票
            if($store_info['invoice'] && $data['invoice']) {
                $invoice = [
                    'order_type' => 'shop',
                    'orderid' => $orderid,
                    'ordernum' => $orderdata['ordernum'],
                    'type' => $data['invoice']['invoice_type'] ? $data['invoice']['invoice_type'] : 1,
                    'invoice_name' => $data['invoice']['invoice_name'] ? $data['invoice']['invoice_name'] : '个人',
                    'name_type' => $data['invoice']['name_type'] ? $data['invoice']['name_type'] : 1,
                    'tax_no' => $data['invoice']['tax_no'] ? $data['invoice']['tax_no'] : '',
                    'address' => $data['invoice']['address'] ? $data['invoice']['address'] : '',
                    'tel' => $data['invoice']['tel'] ? $data['invoice']['tel'] : '',
                    'bank_name' => $data['invoice']['bank_name'] ? $data['invoice']['bank_name'] : '',
                    'bank_account' => $data['invoice']['bank_account'] ? $data['invoice']['bank_account'] : '',
                    'mobile' => $data['invoice']['mobile'] ? $data['invoice']['mobile'] : '',
                    'email' => $data['invoice']['email'] ? $data['invoice']['email'] : ''
                ];
                $invoice['aid'] = aid;
                $invoice['bid'] = $orderdata['bid'];
                $invoice['create_time'] = time();
                Db::name('invoice')->insertGetId($invoice);
            }

			$alltotalprice += $orderdata['totalprice'];
            if(getcustom('deposit')){
                //押金的金额不计算在商城的金额中为了退款时不出错
                $alltotalprice +=$deposit_payprice;
            }
			$alltotalscore += $orderdata['scoredkscore'];
			if(getcustom('member_level_moneypay_price')){
	            $alltotalputongprice += $orderdata['totalputongprice'];//所有商品普通价格总价
	        }
			//是否是复购
			$hasordergoods = Db::name('shop_order_goods')->where('aid',aid)->where('mid',$ordermid)->where('status','in','1,2,3')->find();
			if($hasordergoods){
				$isfg = 1;
			}else{
				$isfg = 0;
			}

            if(getcustom('everyday_hongbao')) {
                $hd = Db::name('hongbao_everyday')->where('aid', aid)->find();
            }
            if(getcustom('shop_stock_warning_notice')){
                $stock_warning_shop_sysset = Db::name('shop_sysset')->where('aid', aid)->find();
            }

            $levelList = Db::name('member_level')->where('aid',aid)->column('*','id');
            $agleveldata = $levelList[$this->member['levelid']];
            if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                $this->member['pid'] = $ordermid;
            }

			$istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
			$istc2 = 0;
			$istc3 = 0;
			foreach($prolist as $key=>$v){
				$product = $v['product'];
				$guige = $v['guige'];
				$num = $v['num'];

				//商品库存不足提醒
				if(getcustom('shop_stock_warning_notice')){
					$shop_stock_warning_num = $guige['stock']-$num;
					if($shop_stock_warning_num <= $stock_warning_shop_sysset['shop_stock_warning_num']){
                        $this->tmpl_stockwarning($product,$guige,$shop_stock_warning_num,$orderdata['ordernum']);                        
					}					
				}
				if(getcustom('product_wholesale') && $product['product_type'] == 4){
					$guigedata = json_decode($product['guigedata'],true);
					$gg_name_arr = explode(',',$guige['name']);
					foreach($guigedata as $pk=>$pg){
						foreach($pg['items'] as $pgt){
							if(isset($pgt['ggpic_wholesale']) && !empty($pgt['ggpic_wholesale'])){							
								if(in_array($pgt['title'],$gg_name_arr)){
									$guige['pic'] = $pgt['ggpic_wholesale'];
								}
							}							
						}
					}
				}				

				$ogdata = [];
				$ogdata['aid'] = aid;
				$ogdata['bid'] = $product['bid'];
				$ogdata['mid'] = $ordermid;
				$ogdata['orderid'] = $orderid;
				$ogdata['ordernum'] = $orderdata['ordernum'];
				$ogdata['proid'] = $product['id'];
                if(getcustom('product_supply_chain')){
                    $ogdata['out_proid'] = $product['out_proid'];
                    $ogdata['gg_num'] = $guige['gg_num'];
                }
                if(getcustom('erp_wangdiantong')){
                    $ogdata['wdt_status'] = $guige['wdt_status'];
                }
				$ogdata['name'] = $product['name'];
				$ogdata['pic'] = $guige['pic']?$guige['pic']:$product['pic'];
				$ogdata['procode'] = $product['procode'];
                $ogdata['barcode'] = $guige['barcode'];
				$ogdata['ggid'] = $guige['id'];
				$ogdata['ggname'] = $guige['name'];
				$ogdata['cid'] = $product['cid'];
                if(getcustom('shoporder_tongji_category')){
                    $ogdata['cid2'] = $product['cid2']??'';
                }
				$ogdata['num'] = $num;
                $productNums = $productNums+$num;
				$ogdata['cost_price'] = $guige['cost_price'];
                //入库成本价
                if(getcustom('shop_add_stock_cost')){
                    //未全部出库中找最早入库的
                    $stockArr = Db::name('shop_stock_order_goods')->where('aid',aid)->where('proid',$product['id'])->where('ggid',$guige['id'])
                        ->whereRaw('outstock < stock')->order('createtime','asc')->limit(10)->select()->toArray();
                    $stockNumFind = 0;//已找到库存数量 累加
                    $stockNumNeed = $num;//还需库存数量 递减
                    $stockTotal = 0;//库存金额
                    foreach($stockArr as $stocklog){
                        $stockleft = $stocklog['stock'] - $stocklog['outstock'];
                        if($stockleft < $stockNumNeed){
                            //此批次库存不足
                            $stockNumFind += $stockleft;
                            $stockNumNeed = $num - $stockNumFind;//还需库存数量
                            if($stocklog['cost_price'] > 0) $stockTotal += $stocklog['cost_price'] * $stockleft;
                            Db::name('shop_stock_order_goods')->where('aid',aid)->where('id',$stocklog['id'])->inc('outstock',$stockleft)->update();
                        }elseif($stockleft >= $stockNumNeed){
                            //此批次库存足够，或者累计已足够
                            if($stocklog['cost_price'] > 0) $stockTotal += $stocklog['cost_price'] * $stockNumNeed;
                            Db::name('shop_stock_order_goods')->where('aid',aid)->where('id',$stocklog['id'])->inc('outstock',$stockNumNeed)->update();
                            break;
                        }
                    }
                    if($stockTotal > 0) $ogdata['cost_price'] = round($stockTotal / $num,2);
                }
				$ogdata['sell_price'] = $guige['sell_price'];
                $ogtotalprice =  $num * $guige['sell_price'];
                if(getcustom('shop_product_jialiao')){
                    //加料的总价格
                    $ogtotalprice+=$num *$v['jlprice'];
                    $ogdata['jlprice'] = $v['jlprice'];
                    $ogdata['jltitle'] = rtrim($v['jltitle'],'/');
                }
				$ogdata['totalprice'] = $ogtotalprice;
				if(getcustom('member_level_moneypay_price')){
					$ogdata['sell_putongprice'] = $guige['sell_putongprice'];
					$totalputongprice2 = $num * $guige['sell_putongprice'];
					$ogdata['totalputongprice'] = $totalputongprice2 && $totalputongprice2>0?$totalputongprice2:0;//普通价格总价
	            }
                $ogdata['total_weight'] = $num * $guige['weight'];
				if(getcustom('weight_template')){
					$ogdata['weight_price'] = $guige['weight_price'];
					$ogdata['weight_templateid'] = $guige['weight_templateid'];
				}
                if(getcustom('product_service_fee')){
                    $ogdata['service_fee'] = $guige['service_fee'];
                }
                if(getcustom('freeze_money')){
                    $ogdata['freezemoney_price'] = $guige['freezemoney_price'];
                }
                if(getcustom('product_weight') && $product['product_type']==2){
                    //称重商品，单价 重量 总价 计算(单位g)
                    $gprice = $guige['sell_price'];
                    $gweight = $guige['weight'];
                    $gtotalweight = $guige['weight']*$num/500;//总重量 斤
                    if($gweight>0){
                        $gpriceNew = $gprice / $gweight * 500;//化成每斤的价格
                    }else{
                        $gpriceNew = $gprice;
                    }
                    $gtotalprice = round($gpriceNew * $gtotalweight,2);
                    $ogdata['sell_price'] = $gpriceNew;
                    $ogdata['real_sell_price'] = $gpriceNew;
                    $ogdata['totalprice'] = $gtotalprice;
                    $ogdata['real_totalprice'] = $gtotalprice;
                    $ogdata['real_total_weight'] = $num * $guige['weight'];
                }
                if(getcustom('fenhong_jiaquan_bylevel')){
                    $ogdata['fenhong_jq_status'] = $product['fenhong_jq_status'];
                }
                // if(getcustom('more_productunit_guige')){
                //     $ogdata['gg_min_unit_num'] = $gg_min_unit_num;
                // }
				$ogdata['status'] = 0;
				$ogdata['createtime'] = time();
				$ogdata['isfg'] = $isfg;
				$ogdata['gtype'] = $v['gtype']??0;

				if($product['isjici'] == 1){
					$ogdata['hexiao_code'] = random(18);
					$ogdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shopproduct&co='.$ogdata['hexiao_code']));
				}else{
                    if(getcustom('product_hexiao_single')){
                        $ogdata['hexiao_code'] = random(18);
                        $ogdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shopproduct&co='.$ogdata['hexiao_code']));
                    }
                }
				if($product['to86yk_tid']){
					$ogdata['to86yk_tid'] = $product['to86yk_tid'];
				}
				if($product['fenhongset'] == 0){ //不参与分红
					$ogdata['isfenhong'] = 2;
				}
				$ogdata['gg_group_title'] = '';
				$guigedata = json_decode($product['guigedata'],true);
				foreach($guigedata as $pk=>$pg){	
					$ogdata['gg_group_title'] .= $pg['title'].',';
				}
				$ogdata['gg_group_title'] = trim($ogdata['gg_group_title'],',');
				
				$og_totalprice = $ogdata['totalprice'];
				if($product['balance'] > 0){
					$og_totalprice = $og_totalprice * (1 - $product['balance']*0.01);
				}

                $allproduct_price = $product_price;
                $og_leveldk_money = 0;
                $og_coupon_money = 0;
                $og_scoredk_money = 0;
                $og_manjian_money = 0;
                if(getcustom('money_dec')){
                    $og_dec_money  = 0 ;//余额抵扣比例
                }
                if($allproduct_price > 0 && $og_totalprice > 0){
                    if($leveldk_money){
                        //更新order_goods的leveldk_money时，产品不开启会员价的 才更新
                        if($v['product']['lvprice']==0 && $v['product']['no_discount'] == 0){ //未开启会员价
                            $og_leveldk_money = $og_totalprice / $needzkproduct_price * $leveldk_money;
                        }
                    }
                    if($coupon_money){
                        $og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
                    }
                    if($scoredk_money && $scoredk_money>0){
                        //如果积分抵扣都是跟随系统设置
                        if($scoremaxtype == 0){
                            $og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
                        //存在单独设置
                        }else{
                            $og_scoredk_money = $product['og_scoredk_money']??0;
                            if($og_scoredk_money>=$scoredk_money){
                                $og_scoredk_money = $scoredk_money;
                                $scoredk_money    = 0;
                            }else{
                            	$scoredk_money -= $og_scoredk_money;
                            }
                        }
                    }
                    if($manjian_money){
                        $og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
                    }
                    if(getcustom('money_dec')){
                    	if($money_dec_type == 1){
                    		$og_dec_money = $product['dec_money']??0;
                    	}else{
                    		if($dec_money){
	                            $og_dec_money = $og_totalprice / $allproduct_price * $dec_money;
	                        }
                    	}
                    }
                    if(getcustom('product_givetongzheng')){
                        $og_tongzhengdk_money = $og_totalprice / $allproduct_price * $tongzhengdk_money;
                        $ogdata['tongzhengdk_money'] = $og_tongzhengdk_money;
                    }
                    if( getcustom('up_floor_fee')){
                        $up_floor_fee_fenxiao_status = Db::name('admin_set_custom')->where('aid',aid)->value('up_floor_fee_fenxiao_status');
                        $og_up_floor_fee = 0;
                        if($up_floor_fee_fenxiao_status){
                            $og_up_floor_fee = $og_totalprice / $allproduct_price * $orderdata['up_floor_fee'];
                        }
                        $ogdata['up_floor_fee'] = $og_up_floor_fee;
                    }
                }
                $ogdata['scoredk_money'] = $og_scoredk_money;
                $ogdata['leveldk_money'] = $og_leveldk_money;
                $ogdata['manjian_money'] = $og_manjian_money;
                $ogdata['coupon_money']  = $og_coupon_money;
                if(getcustom('money_dec')){
                    $ogdata['dec_money'] = $og_dec_money;//余额抵扣比例
                }
                if(getcustom('member_shopscore')){
                    //产品积分抵扣
                    if($shopscoredk_money>0){
                        //如果积分抵扣都是跟随系统设置
                        if($shopscoremaxtype == 0){
                            $og_shopscoredk_money = ($og_totalprice / $allproduct_price) * $shopscoredk_money;
                        //存在单独设置
                        }else{
                            $og_shopscoredk_money = $product['og_shopscoredk_money']??0;
                            if($og_shopscoredk_money>=$shopscoredk_money){
                                $og_shopscoredk_money = $shopscoredk_money;
                                $shopscoredk_money    = 0;
                            }else{
                                $shopscoredk_money -= $og_shopscoredk_money;
                            }
                        }
                        $ogdata['shopscoredk_money'] = $og_shopscoredk_money;
                    }else{
                    	$ogdata['shopscoredk_money'] = 0;
                    }
                }
                if(getcustom('member_dedamount')){
                    $ogdata['paymoney_givepercent'] = 0;
                    $ogdata['paymoney_givemoney']   = 0;
                    $ogdata['dedamount_dkpercent']  = 0;//抵扣金抵扣比例
                    $ogdata['dedamount_dkmoney']    = 0;
                    //若开启了分销依赖抵扣金，或者商家设置了让利比例，则重置分销佣金金额为让利部分的会员的抵扣金额
                    if($this->sysset['dedamount_fenxiao'] == 1 || ($bid>0 && $store_info && $store_info['paymoney_givepercent']>0)){
                        if($bid>0 && $store_info && $store_info['paymoney_givepercent']>0){
                            $ogdata['paymoney_givepercent'] = $store_info['paymoney_givepercent'];//商家让利比例
                            $ogdata['paymoney_givemoney']   = $product['paymoney_givemoney']??0;
                            $ogdata['dedamount_dkpercent']  = $dedamount_dkpercent;//抵扣金抵扣比例
                            //计算此商品抵扣金部分在用户总使用的抵扣金占多少
                            if($dedamount_dkmoney2>0){
                            	//判断用户总使用抵扣金剩余部分是否小于商品抵扣金，小于则把剩余的全给此商品
                                $procha = $dedamount_dkmoney2 -$product['dedamount_dkmoney'];
                                if($procha>=0){
                                    $dedcommission_totalprice = $ogdata['dedamount_dkmoney'] = $product['dedamount_dkmoney'];
                                }else{
                                    $dedcommission_totalprice = $ogdata['dedamount_dkmoney'] = $dedamount_dkmoney2;
                                }
                                $dedamount_dkmoney2 = $procha;
                            }else{
                                $dedcommission_totalprice = $ogdata['dedamount_dkmoney'] = 0;
                            }
                        }else{
                            $dedcommission_totalprice =0;
                        }
                    }
                }
                if($product['bid'] > 0) {
					$bset = Db::name('business_sysset')->where('aid',aid)->find();
					$scoredkmoney = $og_scoredk_money ?? 0;
					if($bset['scoredk_kouchu'] == 0){ //扣除积分抵扣
						$scoredkmoney = 0;
					}
                    $leveldkmoney = $og_leveldk_money;
                    if($bset['leveldk_kouchu'] == 0){ //扣除会员折扣
                        $leveldkmoney = 0;
                    }
                    $totalprice_business = $og_totalprice - $og_manjian_money - $og_coupon_money - $leveldkmoney;
                    if($bset['scoredk_kouchu']==1){
                        $totalprice_business = $totalprice_business - $scoredkmoney;
                    }
                    if(getcustom('member_shopscore')){
                    	if($bset['shopscore_kouchu'] == 1){ //扣除产品积分
	                        $totalprice_business -= $ogdata['shopscoredk_money'];
	                    }
                    }
                    if(getcustom('member_dedamount')){
	                    $totalprice_business -= $ogdata['dedamount_dkmoney'];
	                }
	                if(getcustom('money_dec')){
	                    $add_dec_money = 0;//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
	                }
                    //商品独立费率
                    if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                        $ogdata['business_total_money'] = $totalprice_business * (100-$product['feepercent']) * 0.01;
                        if(getcustom('money_dec')){
                        	//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                            if($ogdata['dec_money']>0) $add_dec_money = $ogdata['dec_money'] * (100-$product['feepercent']) * 0.01;
		                }
                        if(getcustom('business_deduct_cost')){
                        	if($store_info && $store_info['deduct_cost'] == 1 && $ogdata['cost_price']>0){
                        		if($ogdata['cost_price']<=$ogdata['sell_price']){
									$all_cost_price = $ogdata['cost_price'];
								}else{
									$all_cost_price = $ogdata['sell_price'];
								}
	                        	//扣除成本
			                	$ogdata['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$product['feepercent']/100;
			                }
		                }
						if(getcustom('business_fee_type')){
							$bset = Db::name('business_sysset')->where('aid',aid)->find();
                            if($bset['business_fee_type'] == 0){
                                $platformMoney = ($totalprice_business+$freight_price) * $product['feepercent'] * 0.01;
                                $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                            }elseif($bset['business_fee_type'] == 1){
								$platformMoney = $totalprice_business * $product['feepercent'] * 0.01;
								$ogdata['business_total_money'] = $totalprice_business - $platformMoney;
							}elseif($bset['business_fee_type'] == 2){
								$platformMoney = $ogdata['cost_price'] * $product['feepercent'] * 0.01;
								$ogdata['business_total_money'] = $totalprice_business - $platformMoney;
							}
						}
						if(getcustom('money_dec')){
							//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                            if($ogdata['business_total_money'] && $ogdata['business_total_money']<$add_dec_money) $add_dec_money = $ogdata['business_total_money'];
		                }
                    } else {
                        //商户费率
                        //$ogdata['business_total_money'] = $totalprice_business * (100-$store_info['feepercent']) * 0.01;
                        if(getcustom('money_dec')){
                        	//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                            if($ogdata['dec_money']>0) $add_dec_money = $ogdata['dec_money'] * (100-$store_info['feepercent']) * 0.01;
		                }
                        $useStorFee = true;
                        if(getcustom('business_deduct_cost')){
                        	if($store_info && $store_info['deduct_cost'] == 1 && $ogdata['cost_price']>0){
                        		if($ogdata['cost_price']<=$ogdata['sell_price']){
									$all_cost_price = $ogdata['cost_price'];
								}else{
									$all_cost_price = $ogdata['sell_price'];
								}
	                        	//扣除成本
			                	$ogdata['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$store_info['feepercent']/100;
                                $useStorFee = false;
			                }
		                }
						if(getcustom('business_fee_type')){
							$bset = Db::name('business_sysset')->where('aid',aid)->find();
							if($bset['business_fee_type'] == 0){
                                $totalprice_business = $totalprice_business + $freight_price;
								$platformMoney = $totalprice_business * $store_info['feepercent'] * 0.01;
								$ogdata['business_total_money'] = $totalprice_business - $platformMoney;
							}if($bset['business_fee_type'] == 1){
                                $platformMoney = $totalprice_business * $store_info['feepercent'] * 0.01;
                                $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                            }elseif($bset['business_fee_type'] == 2){
								$platformMoney = $ogdata['cost_price'] * $store_info['feepercent'] * 0.01;
								$ogdata['business_total_money'] = $totalprice_business - $platformMoney;
							}
                            $useStorFee = false;
						}
                        if($useStorFee){
                            $ogdata['business_total_money'] = $totalprice_business * (100-$store_info['feepercent']) * 0.01;
                        }
                        if(getcustom('money_dec')){
							//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                            if($ogdata['business_total_money'] && $ogdata['business_total_money']<$add_dec_money) $add_dec_money = $ogdata['business_total_money'];
		                }
                    }
                    if(getcustom('money_dec')){
						//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分
	                    $ogdata['add_dec_money'] = $add_dec_money;
	                }
                }

                $og_totalprice = round($og_totalprice,2);
                if($og_totalprice < 0) $og_totalprice = 0;
                
                //新增real_totalmoney,实际支付金额，和分销无关，都更新
                $og_totalmoney = $og_totalprice - $og_leveldk_money - $og_scoredk_money - $og_manjian_money;
                if($couponrecord['type']!=4) {//运费抵扣券
                    $og_totalmoney -= $og_coupon_money;
                }
				//计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                //分销结算方式fxjiesuantype，0按商品价格，1按成交价格，2按销售利润
				if($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2){
					$og_totalprice = $og_totalprice - $og_leveldk_money - $og_scoredk_money - $og_manjian_money;
					if($couponrecord['type']!=4) {//运费抵扣券
						$og_totalprice -= $og_coupon_money;
					}
					$og_totalprice = round($og_totalprice,2);
					if($og_totalprice < 0) $og_totalprice = 0;
				}
                if(getcustom('member_goldmoney_silvermoney')){
                    //抵扣金银值
                    if($goldsilvertype >0){
                        if($goldsilvertype==1 && $product['silvermoneydec_ratio']>0 &&$allsilvermoneydec>0){
                            $ogsilvermoneydec = $product['silvermoneydec_ratio'] * 0.01 * $guige['sell_price'] * $num;
                            //计算剩余银值抵扣数额
                            $ogsilvercha = $allsilvermoneydec - $ogsilvermoneydec;
                            if($ogsilvercha>=0){
                                $ogdata['silvermoneydec'] = $ogsilvermoneydec;
                                $allsilvermoneydec = $ogsilvercha;
                            }else{
                                $ogdata['silvermoneydec'] = $allsilvermoneydec;
                                $allsilvermoneydec = 0;
                            }
                            $og_totalprice -= $ogdata['silvermoneydec'];
                            $og_totalmoney -= $ogdata['silvermoneydec'];
                        }else if($goldsilvertype == 2 && $allgoldmoneydec>0){
                            $oggoldmoneydec = $product['goldmoneydec_ratio'] * 0.01 * $guige['sell_price'] * $num;
                            //计算剩余金值抵扣数额
                            $oggoldcha = $allgoldmoneydec - $oggoldmoneydec;
                            if($oggoldcha>=0){
                                $ogdata['goldmoneydec'] = $oggoldmoneydec;
                                $allgoldmoneydec = $oggoldcha;
                            }else{
                                $ogdata['goldmoneydec'] = $allgoldmoneydec;
                                $allgoldmoneydec = 0;
                            }
                            $og_totalprice -= $ogdata['goldmoneydec'];
                            $og_totalmoney -= $ogdata['goldmoneydec'];
                        }
                    }
                }
                if(getcustom('member_shopscore')){
                    //产品积分抵扣
                    if($shopscoredk_money>0 && $ogdata['shopscoredk_money']>0){
                        if($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2){
                            $og_totalprice -= $ogdata['shopscoredk_money'];
                            $og_totalprice = round($og_totalprice,2);
                            if($og_totalprice < 0) $og_totalprice = 0;
                        }
                        $og_totalmoney -= $ogdata['shopscoredk_money'];
                    }
                }
				$ogdata['real_totalprice'] = $og_totalprice; //实际商品销售金额,和分销结算方式相关
				if($og_totalmoney < 0) $og_totalmoney = 0;
                $ogdata['real_totalmoney'] = dd_money_format($og_totalmoney); //实际商品销售金额
                if(getcustom('yx_buyer_subsidy')) {
                    $feepercent = 0;
                    //记录商品抽佣
                    if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                        //商品独立费率
                        $feepercent =  $product['feepercent'];
                    } else {
                        //商户费率
                        if($product['bid']>0){
                            $feepercent = Db::name('business')->where('id',$product['bid'])->value('feepercent');
                        }
                    }
                    $order_fee = $og_totalprice * $feepercent * 0.01;
                    $ogdata['order_fee'] = $order_fee;
                }
				//计算佣金的商品金额
				$commission_totalprice = $ogdata['totalprice'];
				if($sysset['fxjiesuantype']==1){ //按成交价格
					$commission_totalprice = $ogdata['real_totalprice'];
				}
				$commission_totalpriceCache = $commission_totalprice;
				if($sysset['fxjiesuantype']==2){ //按销售利润
					$commission_totalprice = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                }
                if(getcustom('yx_buyer_subsidy')) {
                    if ($sysset['fxjiesuantype'] == 3) { //按抽佣
                        $commission_totalprice = $ogdata['order_fee'];
                    }
                }

                if(getcustom('member_dedamount')){
                    //若开启了分销依赖抵扣金，或者商家设置了让利比例，则重置分销佣金金额为让利部分的会员的抵扣金额
                    if($this->sysset['dedamount_fenxiao'] == 1 || ($bid>0 && $store_info && $store_info['paymoney_givepercent']>0)){
                        $commission_totalprice = $dedcommission_totalprice;
                    }
                }
                if( getcustom('up_floor_fee')){
                    if($up_floor_fee_fenxiao_status){
                        $commission_totalprice += $ogdata['up_floor_fee'];
                    }
                }
                if(getcustom('money_dec_commission_fenhong')){
                    //余额抵扣部分不参与任何分销分红
                    if(!$sysset['money_dec_fenxiao_fenhong']){
                        $commission_totalprice -= $ogdata['dec_money'];
                    }
                }
                if($commission_totalprice < 0) $commission_totalprice = 0;
                //平级奖计算金额
                $commission_totalprice_pj = 0;//
                if(getcustom('commission_parent_pj_jiesuantype')){
                    if($sysset['fxjiesuantype_pj']==1){ //按商品价格
                        $commission_totalprice_pj = $ogdata['totalprice'];
                    }
                    if($sysset['fxjiesuantype_pj']==2){ //按成交价格
                        $commission_totalprice_pj = $ogdata['real_totalprice'];
                    }
                    if($sysset['fxjiesuantype_pj']==3){ //按销售利润
                        $commission_totalprice_pj = $ogdata['real_totalprice'] - $guige['cost_price'] * $num;
                    }
                    if($commission_totalprice_pj<0){
                        $commission_totalprice_pj = 0;
                    }
                }
                if(getcustom('member_level_ztorder_extrareward')){
		            $ogdata['commission_totalprice'] = $commission_totalprice;
		        }

                if(getcustom('pay_yuanbao')){
                    $ogdata['yuanbao']       = $product['yuanbao'];
                    $ogdata['total_yuanbao'] = $num*$product['yuanbao'];
                }
                if(getcustom('plug_tengrui')) {
                    $ogdata['tr_roomId'] = $product['tr_roomId'];
                }


				if(getcustom('price_dollar')){
					if($shopset['usdrate']>0) $ogdata['usd_sellprice'] = round($guige['sell_price']/$shopset['usdrate'],2);
				}
				if(getcustom('product_handwork')){
					//手工活列商品
					$ogdata['protype'] = $product['product_type'];
		            $ogdata['hand_fee']= $guige['hand_fee'];
		        }
		        if(getcustom('shop_zthx_backmoney')){
					//市场价格
					$ogdata['market_price'] = $guige['market_price'];
		        }
		        if(getcustom('mendian_no_select')){
                    $ogdata['mdids'] = $product['bind_mendian_ids'];
                }

                if (getcustom('yx_mangfan')) {
                    $mangfan_info = \app\custom\Mangfan::mangfanInfo(aid, $ogdata['proid']);
                    $ogdata['is_mangfan'] = $mangfan_info['status'];
                    $ogdata['mangfan_rate'] = $mangfan_info['rate'];
                    $ogdata['mangfan_commission_type'] = $mangfan_info['commission_type'];
                }
                if(getcustom('h5zb')){
                    $ogdata['roomid'] = input('param.roomid/d',0);
                }
                if(getcustom('consumer_value_add')){
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
                        if($guige['give_green_score']<=0){
                            //$guige['give_green_score'] = bcdiv(bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,4),$consumer_set['green_score_price'],2);

                            $guige['give_green_score'] = bcdiv(bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,4),$green_score_price,2);
                        }else{
                            $guige['give_green_score'] = bcdiv($guige['give_green_score'],$green_score_price,2);
                        }
                        if($guige['give_bonus_pool']<=0){
                            $guige['give_bonus_pool'] = bcmul($guige['sell_price'],$consumer_set['bonus_pool_bili']/100,2);
                        }
                        if($consumer_set['reward_time']==0){
                            $ogdata['give_green_score'] = $guige['give_green_score'] ; //奖励绿色积分 确认收货后赠送
                            $ogdata['give_bonus_pool'] = $guige['give_bonus_pool'] ; //放入奖金池 确认收货后赠送
                        }else{
                            $ogdata['give_green_score2'] = $guige['give_green_score'] ; //奖励绿色积分 确认收货后赠送
                            $ogdata['give_bonus_pool2'] = $guige['give_bonus_pool'] ; //放入奖金池 确认收货后赠送
                        }
                        if(getcustom('greenscore_max')){
                            if($ogdata['give_green_score']>0){
                                $give_maximum = bcmul(bcmul($ogdata['give_green_score'],$consumer_set['green_score_price'],2),$consumer_set['maximum_set'],2);
                            }else{
                                $give_maximum = bcmul(bcmul($ogdata['give_green_score2'],$consumer_set['green_score_price'],2),$consumer_set['maximum_set'],2);
                            }
                            if(getcustom('add_commission_max') && $consumer_set['maximum_set_type']==0){
                                $give_maximum = $guige['give_commission_max'];
                            }
                            $ogdata['give_maximum'] = $give_maximum;
                        }
                        if(getcustom('green_score_reserves')){
                            //订单进入预备金
                            $ogdata['give_green_score_reserves'] = $guige['give_green_score_reserves'];
                            $ogdata['give_green_score_reserves2'] = $guige['give_green_score_reserves2'];
                        }
                    }
                }
                if(getcustom('supply_zhenxin')){
                    if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
                      $ogdata['issource'] = 1;
                      $ogdata['source']   = 'supply_zhenxin';
                      $ogdata['sproid']   = $product['sproid'];//甄新汇选商品ID
                      $ogdata['skuid']    = $guige['skuid'];//甄新汇选规格ID
                      $ogdata['sprice']   = $guige['sprice'];//甄新汇选成本
                    }
                }
                if(getcustom('supply_yongsheng')){
                    if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyongsheng'){
                      $ogdata['issource'] = 1;
                      $ogdata['source']   = 'supply_yongsheng';
                      $ogdata['sproid']   = $product['sproid'];//永盛商品ID
                      $ogdata['source_code'] = $guige['source_code'];//永盛商品规格code
                    }
                }
                if(getcustom('business_toaccount_type')){
					//市场价格
					$ogdata['market_price'] = $guige['market_price'];
		        }
                if(getcustom('product_quanyi') && $product['product_type']==8){
                    //权益商品
                    $ogdata['product_type'] = $product['product_type'];
                    $ogdata['hexiao_num_total'] = bcmul($product['hexiao_num'],$num);
                    $ogdata['hexiao_code'] = random(18);
                    $ogdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shopproduct&co='.$ogdata['hexiao_code']));
                }

                // 商品编辑增加统计业绩等级，默认所有，可以多选，未勾选的等级下单不统计 商城-商品管理-添加|编辑-统计业绩等级
                if(getcustom('product_yeji_level')){
                    $userlevelid = Db::name('member')->where('aid',aid)->where('id',$ogdata['mid'])->value('levelid');
                    $yeji_level = explode(',', $product['yeji_level']);
                    if(in_array($userlevelid, $yeji_level) || in_array('-1', $yeji_level)){
                        $ogdata['yeji'] = $ogdata['real_totalprice'];
                    }
                }

                if(getcustom('product_deposit_mode')){
                    $deposit_mode = Db::name('shop_product')->where('aid',aid)->where('id',$ogdata['proid'])->value('deposit_mode');
                    //是否押金商品
                    $ogdata['deposit_mode'] = $deposit_mode ?? 0;
                }
                if(getcustom('product_chinaums_subsidy')){
                    //国补
                    //判断是否一个订单只有一个商品
                    if(count($buydata) == 1 && count($prolist) == 1){
                        if($product['is_subsidy'] == 1 && !empty($product['cid']) && !empty($product['category_code'])){
                            $shopCategoryIds = Db::name('shop_category')
                                ->where('aid',$product['aid'])
                                ->where('id','in',$product['cid'])
                                ->where('subsidy_apply',1)
                                ->column('id');

                            //查询品类凭证码
                            $authCodeArr = Db::name('chinaums_subsidy_apply')
                                ->where('aid',aid)
                                ->where('mid',mid)
                                ->where('status','in','1,4') // 1:已领取 4:已撤销
                                ->where('cid','in',$shopCategoryIds)
                                ->find();

                            if($authCodeArr && $payorderid && $discount_code){
                                if($orderdata['tel'] == $authCodeArr['mobile'] && $orderdata['linkman'] == $authCodeArr['name']){
                                    $subsidy = new \app\custom\ChinaumsSubsidy(aid);
                                    $countyCode = getAreaCodeByName($address['district'],3);
                                    $authRes = $subsidy->authCodeConfirm([
                                        'auth_code' => $authCodeArr['auth_code'],
                                        'category_code' => $product['category_code'],
                                        'county_code' => $countyCode,
                                        'ordernum' => $ordernum
                                    ]);
                                    //预核销成功
                                    if($authRes['respCode'] == '000000'){
                                        Db::name('shop_order')->where('id',$orderid)->update(['subsidy_order' => 1]);
                                        Db::name('chinaums_subsidy_apply')->where('id',$authCodeArr['id'])->update(['status' => 2]);
                                        $ogdata['cred_frozen_no'] = $authRes['credFrozenNo'];
                                        $ogdata['subsidy_id'] = $authCodeArr['id'];
                                        Db::name('payorder')->where('aid',aid)->where('id',$payorderid)->update(['discount_code' => $discount_code]);
                                    }
                                }
                            }
                        }
                    }
                }
                if(getcustom('extend_planorder')){
	            	$ogdata['poshopid'] = $poshopid;
	            }
	            if(getcustom('shop_product_commission_memberset')){
	            	//商品分销员ID，若有分销员ID，给分销员及分销员上级发奖
	            	$ogdata['procommissionmid']  = $product['procommissionmid']??0;
	            }
				if(getcustom('business_hexiaoplatform_ticheng')){
					$ogdata['business_hx_money'] = $ogtotalprice * ($product['business_hx_ticheng'] / 100);
				}
                if(getcustom('shop_product_form')){
                    if($product['form_id']){
                        $product_formdata = $data['product_formdta'];
                        $this_product_formdata = $product_formdata[$product['id']];
                        $product_formdata_rs = \app\model\ShopProduct::saveOrderProductFormdata(aid,$product['form_id'],$this_product_formdata);
                        if($product_formdata_rs['status'] ==1){
                            $ogdata['form_orderid'] = $product_formdata_rs['orderid'];
                        }
                    }
                }
                if(getcustom('shop_product_certificate')){
                    if($product['form_id'] && input('param.huomacode')){
                        $ogdata['huomacode'] = input('param.huomacode');
                    }
                }
                if(getcustom('product_supplier_admin')){
                    $ogdata['supplier_id'] = $product['supplier_id'];
                }
                if(getcustom('deposit')) {
                    $deposit_id = $product['deposit_id'];
                    if($deposit_id && $product['deposit_status']){
                        $deposit =Db::name('deposit')->where('aid',aid)->where('id',$deposit_id)->find();
                        $ogdata['deposit_id'] =$deposit_id;
                        $ogdata['deposit_price'] =$deposit['money'];
                        $ogdata['deposit_totalprice'] =$deposit['money'] * $ogdata['num'];
                        $ogdata['deposit_num'] = $ogdata['num'];//总数量
                       
                         $deposit_member_data =  $deposit_member_list[$product['id']];
                        $ogdata['deposit_hexiao_num'] = $deposit_member_data['num']??0; //核销的数量
//                        \app\common\Member::addDeposit(aid,$ogdata['bid'],mid,$deposit_member_data['num']*-1,$deposit_id,'订单下单购买冻结'.t('押金'));
                        //需要购买的
                        $deposit_buy_data =  $deposit_buy_list[$product['id']];
                        if($deposit_buy_data){
                            $ogdata['deposit_buy_num'] = $deposit_buy_data['num']??0; //购买的数量
                        }
                     
                    }
                }
                if(getcustom('levelup_biglittlearea_yeji')){
                    $ogdata['biglittlearea_yeji'] = $product['biglittlearea_yeji']??0;
                }
				$ogid = Db::name('shop_order_goods')->insertGetId($ogdata);
				if(getcustom('shop_giveorder')){
					//更新赠好友功能图片
					if($usegiveorder && !$orderdata['giveorderpic'] && $ogdata['pic']){
						Db::name('shop_order')->where('id',$orderid)->update(['giveorderpic'=>$ogdata['pic']]);
					}
				}
                if($product['commissionset']!=-1 && $isfg && getcustom('commission_recursion')){
                    \app\common\Order::recursionCommission(aid,$ordermid,$commission_totalprice,$orderid,$ogid);
                }
                if(getcustom('member_shougou_parentreward') && !$isfg){
                    //首购奖励
                    \app\common\Order::shougouReward(aid,$ordermid,$commission_totalprice,$orderid,$ogid);
                 }
                if(getcustom('member_product_price')){
                    if($guige['is_member_product'] ==1){
                        $buylog = [
                            'aid' => aid,
                            'mid' => $ordermid,
                            'ordernum' => $orderdata['ordernum'],
                            'type' =>'shop',
                            'proid' => $ogdata['proid'],
                            'ggid' => $ogdata['ggid'],
                            'orderid' => $orderid,
                            'sell_price' => $ogdata['sell_price'],
                            'num' => $ogdata['num'],
                            'createtime' => time()
                        ];
                        Db::name('member_product_buylog')->insert($buylog);
                    }
                }
                $ogupdate = [];
                if(getcustom('product_glass') || getcustom('product_glass_custom')){
                    $glass_record_id = $v['glass_record_id'];
                    if($glass_record_id) {
                        //定制参数
                        if(isset($v['product']['glass_type']) && $v['product']['glass_type'] == 2){
                            $glasscustomrecord = Db::name('glass_custom_record')->where('aid', aid)->where('id', $glass_record_id)->find();
                            if($glasscustomrecord){
                                unset($glasscustomrecord['id']);
                                $customOrder = $glasscustomrecord;
                                $customOrder['aid'] = aid;
                                $customOrder['bid'] = $orderdata['bid']?? 0;
                                $customOrder['glass_custom_id'] = $glass_record_id;
                                $customOrder['order_goods_id'] = $ogid;
                                $customOrder['createtime'] = time();
                                $customOrder['job'] = $orderdata['ordernum'];
                                $customOrder['reference'] = $orderdata['ordernum'];
                                $order_glass_custom_id = Db::name('order_glass_custom')->insertGetId($customOrder);
                                $ogupdate['glass_custom_id'] = $order_glass_custom_id;
                            }
                        }else{
                            //视力档案
                            $glassrecord = Db::name('glass_record')->where('aid', aid)->where('id', $glass_record_id)->find();
                            if ($glassrecord) {
                                $orderglassrecord = [
                                    'glass_record_id' => $glassrecord['id'],
                                    'name' => $glassrecord['name'],
                                    'desc' => $glassrecord['desc'],
                                    'degress_left' => $glassrecord['degress_left'],
                                    'degress_right' => $glassrecord['degress_right'],
                                    'ipd' => $glassrecord['ipd'],
                                    'ipd_left' => $glassrecord['ipd_left'],
                                    'ipd_right' => $glassrecord['ipd_right'],
                                    'double_ipd' => $glassrecord['double_ipd'],
                                    'correction_left' => $glassrecord['correction_left'],
                                    'correction_right' => $glassrecord['correction_right'],
                                    'is_ats' => $glassrecord['is_ats'],
                                    'ats_left' => $glassrecord['ats_left'],
                                    'ats_right' => $glassrecord['ats_right'],
                                    'ats_zright' => $glassrecord['ats_zright'],
                                    'ats_zleft' => $glassrecord['ats_zleft'],
                                    'add_right' => $glassrecord['add_right'],
                                    'add_left' => $glassrecord['add_left'],
                                    'check_time' => $glassrecord['check_time'],
                                    'nickname' => $glassrecord['nickname'],
                                    'sex' => $glassrecord['sex'],
                                    'age' => $glassrecord['age'],
                                    'tel' => $glassrecord['tel'],
                                    'remark' => $glassrecord['remark'],
                                    'type' => $glassrecord['type'],
                                    'mid' => $glassrecord['mid'],
                                    'createtime' => time(),
                                    'aid' => aid,
                                    'bid' => $orderdata['bid'] ?? 0,
                                    'order_goods_id' => $ogid
                                ];
                                $order_glass_record_id = Db::name('order_glass_record')->insertGetId($orderglassrecord);
                                $ogupdate['glass_record_id'] = $order_glass_record_id;
                            }
                        }
                    }
                }
                $parent1 = [];$parent2 = [];$parent3 = [];$parent4 = [];
                if(getcustom('commission_recursion') && $isfg){
                    //首单才发分销奖励
                    $product['commissionset'] = -1;
                }
                if($product['commissionset']!=-1){
                	$params = [];
                    if(!getcustom('fenxiao_manage')){
                        $sysset['fenxiao_manage_status'] = 0;
                    }
                    if(getcustom('extend_planorder')){
	                	//如果是排队系统里的店铺下单，分销仅给店铺人员，其他人员不发奖
		            	if($poshopid && $poshopid>0){
		            		$params['poshopid']  = $poshopid;
		            		$params['poshopmid'] = Db::name('planorder_shop')->where('id',$poshopid)->value('mid');
		            	}
		            }
		            if(getcustom('shop_product_commission_memberset')){
		            	//商品分销员ID，若有分销员ID，给分销员及分销员上级发奖
		            	$params['procommissionmid']  = $product['procommissionmid']??0;
		            }
                    if($sysset['fenxiao_manage_status']){
                        $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset,$this->member,$product,$num,$commission_totalprice,$commission_totalprice_pj,$params);
                    }else{
                        $commission_data = \app\common\Fenxiao::fenxiao($sysset,$this->member,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3,$commission_totalprice_pj,$params);
                    }

                    if(getcustom('commission_parent_bcy_send_once') && $sysset['commission_parent_bcy_send_once']==1){
                        //被超越奖只给最近的发一次，无限往上找直至找到一个
                        $path_arr = array_reverse(explode(',',$this->member['path']));
                        if($path_arr){
                            $lastid = 0;
                            foreach ($path_arr as $pv) {
                                if($lastid > 0 && $pv > 0){
                                    $last_member = Db::name('member')->where('aid',aid)->where('id',$lastid)->field('id,levelid')->find();
                                    $last_member_level = $levelList[$last_member['levelid']];

                                    $last_p_member = Db::name('member')->where('aid',aid)->where('id',$pv)->field('id,levelid')->find();
                                    $last_p_member_level = $levelList[$last_p_member['levelid']];

                                    //判断当前人级别是否大于上级
                                    if($last_member_level['id'] > $last_p_member_level['id']){
                                        $last_p_member_level['commissionbcytype'] = $last_p_member_level['commissiontype'];
                                        if($product['commissionbcyset'] != 0){
                                            if($product['commissionbcyset'] == 1){
                                                $commissionbcydata1 = json_decode($product['commissionbcydata1'],true);
                                                $last_p_member_level['commission_parent_bcy'] = $commissionbcydata1[$last_p_member_level['id']]['commission'];
                                            }elseif($product['commissionbcyset'] == 2){
                                                $commissionbcydata2 = json_decode($product['commissionbcydata2'],true);
                                                $last_p_member_level['commission_parent_bcy'] = $commissionbcydata2[$last_p_member_level['id']]['commission'];
                                                $last_p_member_level['commissionbcytype'] = 1;
                                            }else{
                                                $last_p_member_level['commission_parent_bcy'] = 0;
                                            }
                                        }
                                        if($last_p_member_level['commission_parent_bcy'] > 0){
                                            if($last_p_member_level['commissionbcytype'] == 0){
                                                $commission_bcy = $commission_totalprice * $last_p_member_level['commission_parent_bcy'] * 0.01;
                                            }else{
                                                $commission_bcy = $last_p_member_level['commission_parent_bcy'] * $num;
                                            }

                                            if($commission_bcy > 0){
                                                Db::name('member_commission_record')->insert([
                                                    'aid' => aid,
                                                    'mid' => $last_p_member['id'],
                                                    'frommid' => $last_member['id'],
                                                    'commission' => $commission_bcy,
                                                    'remark' => '被用户(ID:'.$last_member['id'].'，级别：'.$last_member_level['name'].')超越，发被超越奖',
                                                    'createtime' => time(),
                                                    'orderid' => $orderid,
                                                    'ogid' => $ogid,
                                                ]);
                                            }
                                            break;
                                        }
                                    }
                                }

                                $lastid = $pv;
                            }
                        }

                        //写入被超越奖
                       // if(!empty($commission_data['commission_record_bcy_arr'])){
                       //     $commission_data['commission_record_bcy_arr']['orderid'] = $orderid;
                       //     $commission_data['commission_record_bcy_arr']['ogid'] = $ogid;
                       //     Db::name('member_commission_record')->insert($commission_data['commission_record_bcy_arr']);
                       // }
                    }

                    if(getcustom('commission_parent_pj_send_once') && $sysset['commission_parent_pj_send_once']==1) {
                        //平级奖只给最近的发一次，无限往上找直至找到一个
                        $path_arr = array_reverse(explode(',', $this->member['path']));
                        if ($path_arr) {
                            $lastid = 0;
                            foreach ($path_arr as $pv) {
                                if ($lastid > 0 && $pv > 0) {
                                    $last_member = Db::name('member')->where('aid', aid)->where('id', $lastid)->field('id,levelid')->find();
                                    $last_member_level = $levelList[$last_member['levelid']];

                                    $last_p_member = Db::name('member')->where('aid', aid)->where('id', $pv)->field('id,levelid')->find();
                                    $last_p_member_level = $levelList[$last_p_member['levelid']];

                                    //判断当前人级别是否等于上级
                                    if ($last_member_level['id'] == $last_p_member_level['id']) {
                                        $last_p_member_level['commissionpingjitype'] = $last_p_member_level['commissiontype'];
                                        if ($product['commissionpingjiset'] != 0) {
                                            if ($product['commissionpingjiset'] == 1) {
                                                $commissionbcydata1 = json_decode($product['commissionpingjidata1'], true);
                                                $last_p_member_level['commission_parent_pj'] = $commissionbcydata1[$last_p_member_level['id']]['commission'];
                                            } elseif ($product['commissionpingjiset'] == 2) {
                                                $commissionbcydata2 = json_decode($product['commissionpingjidata2'], true);
                                                $last_p_member_level['commission_parent_pj'] = $commissionbcydata2[$last_p_member_level['id']]['commission'];
                                                $last_p_member_level['commissionpingjitype'] = 1;
                                            } else {
                                                $last_p_member_level['commission_parent_pj'] = 0;
                                            }
                                        }
                                        if ($last_p_member_level['commission_parent_pj'] > 0) {
                                            if ($last_p_member_level['commissionpingjitype'] == 0) {
                                                $commission_pj = $commission_totalprice * $last_p_member_level['commission_parent_pj'] * 0.01;
                                            } else {
                                                $commission_pj = $last_p_member_level['commission_parent_pj'] * $num;
                                            }
                                            if ($commission_pj > 0) {
                                                Db::name('member_commission_record')->insert([
                                                    'aid' => aid,
                                                    'mid' => $last_p_member['id'],
                                                    'frommid' => $last_member['id'],
                                                    'commission' => $commission_pj,
                                                    'remark' => '团队平级奖',
                                                    'createtime' => time(),
                                                    'orderid' => $orderid,
                                                    'ogid' => $ogid,
                                                ]);
                                            }
                                            break;
                                        }
                                    }
                                }

                                $lastid = $pv;
                            }
                        }
                    }

                    //直推特殊奖--用户购买首单时，直推上级获得额外百分比的奖励，仅限于第一个订单
                    if(getcustom('commission_zhitui_special_first_order') && $sysset['commission_zhitui_special_first_order']==1){
                        //直推人
                        if($pid = Db::name('member')->where('aid', aid)->where('id', $this->member['id'])->value('pid')){
                            $pinfo = Db::name('member')->where('aid', aid)->where('id', $pid)->find();
                            //获取直推人级别信息
                            $p_level = $levelList[$pinfo['levelid']];
                            //查询已支付的订单包括已支付的
                            $yyforder = Db::name('shop_order')->where('aid',aid)->where('mid',$this->member['id'])->whereRaw('status in(1,2,3) or refund_status in(1,2)')->count();
                            //检测是否是第一个已支付的订单
                            if($yyforder <= 0 && $p_level['commission_zhitui_special_ratio'] > 0){
                                //直推特殊奖比例
                                $commission_zhitui_special_ratio = $p_level['commission_zhitui_special_ratio'] / 100;
                                $commission_zhitui_special = round(bcmul($commission_totalprice, $commission_zhitui_special_ratio, 3),2);
                                if ($commission_zhitui_special > 0) {
                                    $id = Db::name('member_commission_record')->insert([
                                        'aid' => aid,
                                        'mid' => $pinfo['id'],
                                        'frommid' => $this->member['id'],
                                        'commission' => $commission_zhitui_special,
                                        'remark' => '直推特殊奖',
                                        'createtime' => time(),
                                        'orderid' => $orderid,
                                        'ogid' => $ogid,
                                    ]);
                                }
                            }
                        }
                    }

                    if(getcustom('member_level_parent_not_commission')){
                        //定制，购买人上级无任何分销奖励
                        if($product['parent_not_commission_json']){
                            $parent_not_commission = json_decode($product['parent_not_commission_json'],true);
                            if(isset($parent_not_commission[$agleveldata['id']])){
                                if($parent_not_commission[$agleveldata['id']] == -1){//跟随会员等级
                                    if($agleveldata['parent_not_commission'] == 1) {
                                        $commission_data = [];
                                    }
                                }elseif($parent_not_commission[$agleveldata['id']] == 1){//开启
                                    $commission_data = [];
                                }
                            }else{
                                //商品未设置，使用会员等级设置
                                if($agleveldata['parent_not_commission'] == 1) {
                               $commission_data = [];
                           }
                            }
                        }else{
                            //商品未设置，使用会员等级设置
                            if($agleveldata['parent_not_commission'] == 1) {
                               $commission_data = [];
                           }
                        }
                    }

                    $ogupdate['parent1'] = $commission_data['parent1']??0;
                    $ogupdate['parent2'] = $commission_data['parent2']??0;
                    $ogupdate['parent3'] = $commission_data['parent3']??0;
                    $ogupdate['parent4'] = $commission_data['parent4']??0;
                    $ogupdate['parent1commission'] = $commission_data['parent1commission']??0;
                    $ogupdate['parent2commission'] = $commission_data['parent2commission']??0;
                    $ogupdate['parent3commission'] = $commission_data['parent3commission']??0;
                    $ogupdate['parent4commission'] = $commission_data['parent4commission']??0;
                    $ogupdate['parent1score'] = $commission_data['parent1score']??0;
                    $ogupdate['parent2score'] = $commission_data['parent2score']??0;
                    $ogupdate['parent3score'] = $commission_data['parent3score']??0;
                    if(getcustom('commission_money_percent')){
                        $ogupdate['parent1money'] = $commission_data['parent1money']??0;
                        $ogupdate['parent2money'] = $commission_data['parent2money']??0;
                        $ogupdate['parent3money'] = $commission_data['parent3money']??0;
                    }
                    if(getcustom('commission_xianjin_percent')){
                        $ogupdate['parent1xianjin'] = $commission_data['parent1xianjin']??0;
                        $ogupdate['parent2xianjin'] = $commission_data['parent2xianjin']??0;
                        $ogupdate['parent3xianjin'] = $commission_data['parent3xianjin']??0;
                    }
                    if(getcustom('yx_buyer_subsidy')){
                        $ogupdate['parent1subsidyscore'] = $commission_data['parent1subsidyscore']??0;
                        $ogupdate['parent2subsidyscore'] = $commission_data['parent2subsidyscore']??0;
                        $ogupdate['parent3subsidyscore'] = $commission_data['parent3subsidyscore']??0;
                    }
                    if(getcustom('yx_farm')){
                        $ogupdate['parent1farmseed'] = $commission_data['parent1farmseed']??0;
                        $ogupdate['parent2farmseed'] = $commission_data['parent2farmseed']??0;
                        $ogupdate['parent3farmseed'] = $commission_data['parent3farmseed']??0;
                    }
                    //20250626新增 平级奖独立记录
                    if(getcustom('commission_parent_pj')){
                        $ogupdate['parent_pj1'] = $commission_data['parent_pj1']??0;
                        $ogupdate['parent_pj2'] = $commission_data['parent_pj2']??0;
                        $ogupdate['parent_pj3'] = $commission_data['parent_pj3']??0;
                        $ogupdate['parent1commission_pj'] = $commission_data['parent1commission_pj']??0;
                        $ogupdate['parent2commission_pj'] = $commission_data['parent2commission_pj']??0;
                        $ogupdate['parent3commission_pj'] = $commission_data['parent3commission_pj']??0;
                    }

                    $istc1 = $commission_data['istc1']??0;
                    $istc2 = $commission_data['istc2']??0;
                    $istc3 = $commission_data['istc3']??0;
                    if(getcustom('commission_butie')){
                        $butie_data = [];
                        $butie_data['parent1commission_butie'] = $commission_data['parent1commission_butie']??0;
                        $butie_data['parent2commission_butie'] = $commission_data['parent2commission_butie']??0;
                        $butie_data['parent3commission_butie'] = $commission_data['parent3commission_butie']??0;
                    }
                    if(getcustom('mendian_usercenter')){
                        $ogupdate['totalcommission_pj'] = $commission_data['total_pj'];
                    }
                    if(getcustom('commission_product_self_buy')){
                        //自购佣金加入order_goods表
                        $ogupdate['selfbuy_commission'] = $commission_data['selfbuy_commission']??0;
                    }
                }
                //记录产品购车基金和旅游基金数据
                if(getcustom('teamfenhong_gouche')){
                    $ogupdate['gouchebonusset'] = $product['gouchebonusset'];
                    $ogupdate['gouchebonusdata1'] = $product['gouchebonusdata1'];
                    $ogupdate['gouchebonusdata2'] = $product['gouchebonusdata2'];
                }
                if(getcustom('teamfenhong_lvyou')){
                    $ogupdate['lvyoubonusset'] = $product['lvyoubonusset'];
                    $ogupdate['lvyoubonusdata1'] = $product['lvyoubonusdata1'];
                    $ogupdate['lvyoubonusdata2'] = $product['lvyoubonusdata2'];
                }
                //记录产品团队分红伯乐奖参数
                if(getcustom('teamfenhong_bole')){
                    $teamfenhongbldata = json_decode($product['teamfenhongbldata1'],true);
                    $teamfenhongbldata2 = json_decode($product['teamfenhongbldata2'],true);
                    $member_levelid = $this->member['levelid'];
                    $ogupdate['teamfenhong_bole_bl'] = $teamfenhongbldata[$member_levelid]['commission'];
                    $ogupdate['teamfenhong_bole_bl_tuoli'] = $teamfenhongbldata[$member_levelid]['commission_tuoli'];
                    $ogupdate['teamfenhong_bole_money'] = $teamfenhongbldata2[$member_levelid]['commission'];
                }

                if($ogupdate){
					Db::name('shop_order_goods')->where('id',$ogid)->update($ogupdate);
				}

				$totalcommission = 0;
				if($product['commissionset']!=4){
					if(getcustom('commission_fugou') && $isfg == 1){
						if($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])){
							Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$ogupdate['parent1'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'复购奖励','createtime'=>time()]);
							$totalcommission += $ogupdate['parent1commission'];
						}
						if($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])){
							Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$ogupdate['parent2'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'复购奖励','createtime'=>time()]);
							$totalcommission += $ogupdate['parent2commission'];
						}
						if($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])){
							Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$ogupdate['parent3'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'复购奖励','createtime'=>time()]);
							$totalcommission += $ogupdate['parent3commission'];
						}

                        if(getcustom('commission_parent_pj')) {
                            if ($ogupdate['parent_pj1'] && ($ogupdate['parent1commission_pj'] > 0)) {
                                $remark = '平级一级奖励';
                                $data_c = ['aid' => aid, 'mid' => $ogupdate['parent_pj1'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                Db::name('member_commission_record')->insert($data_c);
                                $totalcommission += $ogupdate['parent1commission_pj'];
                            }
                            if ($ogupdate['parent_pj2'] && ($ogupdate['parent2commission_pj'] > 0)) {
                                $remark = '平级二级奖励';
                                $data_c = ['aid' => aid, 'mid' => $ogupdate['parent_pj2'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                Db::name('member_commission_record')->insert($data_c);
                                $totalcommission += $ogupdate['parent2commission_pj'];
                            }
                            if ($ogupdate['parent_pj3'] && ($ogupdate['parent3commission_pj'] > 0)) {
                                $remark = '平级三级奖励';
                                $data_c = ['aid' => aid, 'mid' => $ogupdate['parent_pj3'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                Db::name('member_commission_record')->insert($data_c);
                                $totalcommission += $ogupdate['parent1commission_pj'];
                            }
                        }
					}else{
                        // 岗位提成
                        $gangwei_ceng = 0;
                        $gangwei_commission = 0;
                        $gangwei_arr = [];
                        if(getcustom('commission_log_remark_custom')){
                            $nickname = Db::name('member')->where('aid',aid)->where('id',$ordermid)->value('nickname');
                            $nickname = removeEmoj($nickname);
                            if($product['bid'] > 0){
                                $bname = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->value('name');
                            }else{
                                $bname = Db::name('admin_set')->where('aid',aid)->value('name');
                            }
                        }
						if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0 || $ogupdate['parent1money']>0 || $ogupdate['parent1xianjin']>0 || $ogupdate['parent1subsidyscore']>0 || $ogupdate['parent1farmseed']>0)){
						    $remrak1 = t('下级').'购买商品奖励';
						    if(getcustom('commission_log_remark_custom')){
                                $remrak1 = t('下级').$nickname.'在'.$bname.'购买'.$product['name'].'消费'.$ogdata['real_totalmoney'].'元';
                            }
                            $parent1_levelid = $commission_data['parent1_levelid']??0;
						    $data_c = ['aid'=>aid,'mid'=>$ogupdate['parent1'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>$remrak1,'createtime'=>time(),'levelid'=>$parent1_levelid];

						    if(getcustom('commission_butie')){
                                $data_c['butie'] = $butie_data['parent1commission_butie'];
                                $data_c['commission'] = bcsub($ogupdate['parent1commission'],$butie_data['parent1commission_butie'],2);
                            }
						    if(getcustom('commission_max_times')){
						        //分销份数限制
                                $data_c['proid'] = $product['id'];
                                $data_c['level'] = 1;
                            }
                            if(getcustom('pay_huifu_fenzhang')){
                                //如果该分销商已进件 且是商户独立收款，进行分佣使用分账
                                if($product['bid'] > 0){
                                   $businessdata = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->field('huifu_business_status')->find();
                                    $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                                    $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                                    $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                                    $parent1_huifu_id = Db::name('member')->where('aid',$data_c['aid'])->where('id',$ogupdate['parent1'])->value('huifu_id');
                                    if($parent1_huifu_id && $huifu_business_status && $huifu_send_commission==1 && $delay_acct_flag==1)$data_c['to_fenzhang'] = 1;
                                }
                            }
                            if(getcustom('member_forzengxcommission')){
                                if($protypes && $protypes[0] == 10){
                                    $data_c['product_type'] = 10;//佣金类商品
                                }
                            }
                            if(getcustom('yx_collage_jipin_optimize')){
                                $data_c['collage_jipin_id'] = $commission_data['collage_jipin_id'] ?? 0;//即拼活动ID
                            }
                            if(getcustom('commission_money_percent')){
                                $data_c['money'] = $ogupdate['parent1money']??0;
                            }
                            if(getcustom('up_giveparent_userdata')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent1'];
                            }
                            if(getcustom('commission_xianjin_percent')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['xianjin'] = $ogupdate['parent1xianjin']??0;;
                            }
                            if(getcustom('yx_buyer_subsidy')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['subsidyscore'] = $ogupdate['parent1subsidyscore']??0;;
                            }
                            if(getcustom('yx_farm')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['farmseed'] = $ogupdate['parent1farmseed']??0;;
                            }
                            if(getcustom('yx_cashback_time_fenxiao_speed')){
                                $data_c['cashback_speed'] = 1;
                            }

							Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogupdate['parent1commission'];

                            // 岗位提成 gangwei_give_origin_status发放上级选择：0现上级，1原上级（购买人的原上级一个团队，现上级一个团队，没有原上级给现上级这个团队），2每层都发给原上级（没有原上级发给现上级）
                            if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 0){
                                $last_pid = $ordermid;
                                $gangwei_ceng  = 1;
                                $parent1_info = Db::name('member')->where('aid',aid)->where('id',$ogupdate['parent1'])->find();

                                // 计算直推人上一级（实为上二级）
                                if($parent1_info && $ogupdate['parent1commission']>0 && $parent1_info['pid']){
                                    $parent1_info_last = Db::name('member')
                                        ->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$parent1_info['pid'])
                                        ->field('m.id,m.nickname,l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                    if($parent1_info_last && $parent1_info_last['gangwei1'] > 0 && $parent1_info_last['can_agent'] != 0
                                        && ($parent1_info_last['gangwei_only_big'] == 0 || ($parent1_info_last['gangwei_only_big'] == 1 && $parent1_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                        $commission_p = dd_money_format($ogupdate['parent1commission']*$parent1_info_last['gangwei1']/100,2);
                                        if($commission_p >= 1){
                                            $gangwei_commission = $commission_p;
                                        }
                                        if($gangwei_commission > 0){
                                            // 条件
                                            $zt_num = Db::name('member')->where('aid',aid)->where('pid',$parent1_info['pid'])->count('id');
                                            $is_send = 0;
                                            if($sysset['gangwei_tndn_status'] == 1 ){//推N得N
                                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                    $is_send = 1;
                                                }
                                            }else{
                                                $is_send = 1;
                                            }
                                            if($is_send == 1){
                                                $data_p = ['aid'=>aid,'mid'=>$parent1_info['pid'],'frommid'=>$parent1_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                                $gangwei_arr[] = $data_p;
                                                $totalcommission += $gangwei_commission;
                                                $last_pid = $parent1_info['pid'];
                                            }else{
                                                // 紧缩关闭
                                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                    $gangwei_commission = 0;
                                                }
                                            }
                                        }
                                        
                                    }
                                    else{
                                        //直推人上一级（实为上二级）无岗位提成时需要标记，后面无限层从上三级开始重新计算
                                        $oneLevelNoGangwei = $ogupdate['parent1commission'];
                                        $last_pid = $parent1_info['pid'];
                                    }
                                }
                                
                            }
						}
						if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0 || $ogupdate['parent2money']>0 || $ogupdate['parent2xianjin']>0 || $ogupdate['parent2subsidyscore']>0 || $ogupdate['parent2farmseed']>0)){
                            $remrak2 = t('下二级').'购买商品奖励';
                            if(getcustom('commission_log_remark_custom')){
                                $remrak2 = t('下二级').$nickname.'在'.$bname.'购买'.$product['name'].'消费'.$ogdata['real_totalmoney'].'元';
                            }
                            $parent2_levelid = $commission_data['parent2_levelid']??0;
						    $data_c = ['aid'=>aid,'mid'=>$ogupdate['parent2'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>$remrak2,'createtime'=>time(),'levelid'=>$parent2_levelid];
                            if(getcustom('commission_butie')){
                                $data_c['butie'] = $butie_data['parent2commission_butie'];
                                $data_c['commission'] =  bcsub($ogupdate['parent2commission'],$butie_data['parent2commission_butie'],2);
                            }
                            if(getcustom('commission_max_times')){
                                //分销份数限制
                                $data_c['proid'] = $product['id'];
                                $data_c['level'] = 2;
                            }
                            if(getcustom('pay_huifu_fenzhang')){
                                //如果该分销商已进件 且是商户独立收款，进行分佣使用分账
                                if($product['bid'] > 0){
                                    $businessdata = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->field('huifu_business_status')->find();
                                    $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                                    $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                                    $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                                    $parent2_huifu_id = Db::name('member')->where('aid',$data_c['aid'])->where('id',$ogupdate['parent2'])->value('huifu_id');
                                    if($parent2_huifu_id && $huifu_business_status && $huifu_send_commission==1 && $delay_acct_flag==1)$data_c['to_fenzhang'] = 1;
                                }
                            }
                            if(getcustom('member_forzengxcommission')){
                                if($protypes && $protypes[0] == 10){
                                    $data_c['product_type'] = 10;//佣金类商品
                                }
                            }
                            if(getcustom('commission_money_percent')){
                                $data_c['money'] = $ogupdate['parent2money']??0;
                            }
                            if(getcustom('up_giveparent_userdata')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent2'];
                            }
                            if(getcustom('commission_xianjin_percent')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['xianjin'] = $ogupdate['parent2xianjin']??0;;
                            }
                            if(getcustom('yx_buyer_subsidy')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['subsidyscore'] = $ogupdate['parent2subsidyscore']??0;;
                            }
                            if(getcustom('yx_farm')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['farmseed'] = $ogupdate['parent2farmseed']??0;;
                            }
							Db::name('member_commission_record')->insert($data_c);
							$totalcommission += $ogupdate['parent2commission'];

                            // 岗位提成
                            if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 0){
                                $gangwei_ceng  = 2;
                                $parent2_info = Db::name('member')->where('aid',aid)->where('id',$ogupdate['parent2'])->find();

                                // 计算直推人上二级（实为上三级）
                                if($parent2_info && $parent2_info['pid']){
                                    $parent2_info_last = Db::name('member')
                                        ->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$parent2_info['pid'])
                                        ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                    if($parent2_info_last && ($parent2_info_last['gangwei1'] > 0 || $parent2_info_last['gangwei2'] > 0) && $parent2_info_last['can_agent'] != 0
                                        && ($parent2_info_last['gangwei_only_big'] == 0 || ($parent2_info_last['gangwei_only_big'] == 1 && $parent2_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                        if($gangwei_commission > 0){
                                            $gangwei_commission = dd_money_format($gangwei_commission*$parent2_info_last['gangwei2']/100,2);
                                        }
                                        if($ogupdate['parent2commission'] > 0){
                                            $gangwei_commission_fenxiao = dd_money_format($ogupdate['parent2commission']*$parent2_info_last['gangwei1']/100,2);
                                            if($gangwei_commission_fenxiao > 0){
                                                $gangwei_commission += $gangwei_commission_fenxiao;
                                            }
                                        }
                                        
                                        if($gangwei_commission < 1){
                                            $gangwei_commission = 0;
                                        }
                                        if($gangwei_commission > 0){
                                            // 直推人数
                                            $zt_num = Db::name('member')->where('aid',aid)->where('pid',$parent2_info['pid'])->count('id');

                                            $is_send = 0;
                                            if($sysset['gangwei_tndn_status'] == 1 ){
                                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                    $is_send = 1;
                                                }
                                            }else{
                                                $is_send = 1;
                                            }

                                            if($is_send == 1){
                                                $data_p = ['aid'=>aid,'mid'=>$parent2_info['pid'],'frommid'=>$parent2_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                                $gangwei_arr[] = $data_p;
                                                $totalcommission += $gangwei_commission;
                                                $last_pid = $parent2_info['pid'];
                                            }else{
                                                // 紧缩关闭
                                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                    $gangwei_commission = 0;
                                                }
                                            }
                                        }
                                    }else{
                                        // 紧缩关闭
                                        if($sysset['gangwei_jinsuo_status'] == 0 ){
                                            $gangwei_commission = 0;
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }
						}
						if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0 || $ogupdate['parent3money']>0 || $ogupdate['parent3xianjin']>0 || $ogupdate['parent3subsidyscore']>0 || $ogupdate['parent3farmseed']>0)){
                            $remrak3 = t('下三级').'购买商品奖励';
                            if(getcustom('commission_log_remark_custom')){
                                $remrak3 = t('下三级').$nickname.'在'.$bname.'购买'.$product['name'].'消费'.$ogdata['real_totalmoney'].'元';
                            }
                            $parent3_levelid = $commission_data['parent3_levelid']??0;
                            $data_c = ['aid'=>aid,'mid'=>$ogupdate['parent3'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>$remrak3,'createtime'=>time(),'levelid'=>$parent3_levelid];
                            if(getcustom('commission_butie')){
                                $data_c['butie'] = $butie_data['parent3commission_butie'];
                                $data_c['commission'] =  bcsub($ogupdate['parent3commission'],$butie_data['parent3commission_butie'],2);
                            }
                            if(getcustom('commission_max_times')){
                                //分销份数限制
                                $data_c['proid'] = $product['id'];
                                $data_c['level'] = 3;
                            }
                            if(getcustom('pay_huifu_fenzhang')){
                                //如果该分销商已进件 且是商户独立收款，进行分佣使用分账
                                if($product['bid'] > 0){
                                    $businessdata = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->field('huifu_business_status')->find();
                                    $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                                    $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                                    $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                                    $parent3_huifu_id = Db::name('member')->where('aid',$data_c['aid'])->where('id',$ogupdate['parent3'])->value('huifu_id');
                                    if($parent3_huifu_id && $huifu_business_status && $huifu_send_commission==1 && $delay_acct_flag==1)$data_c['to_fenzhang'] = 1;
                                }
                            }
                            if(getcustom('member_forzengxcommission')){
                                if($protypes && $protypes[0] == 10){
                                    $data_c['product_type'] = 10;//佣金类商品
                                }
                            }
                            if(getcustom('commission_money_percent')){
                                $data_c['money'] = $ogupdate['parent3money']??0;
                            }
                            if(getcustom('up_giveparent_userdata')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent3'];
                            }
                            if(getcustom('commission_xianjin_percent')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['xianjin'] = $ogupdate['parent3xianjin']??0;;
                            }
                            if(getcustom('yx_buyer_subsidy')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['subsidyscore'] = $ogupdate['parent2subsidyscore']??0;;
                            }
                            if(getcustom('yx_farm')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['farmseed'] = $ogupdate['parent3farmseed']??0;;
                            }
							Db::name('member_commission_record')->insert($data_c);
							$totalcommission += $ogupdate['parent3commission'];

                            // 岗位提成
                            if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 0){
                                $gangwei_ceng  = 3;
                                $parent3_info = Db::name('member')->where('aid',aid)->where('id',$ogupdate['parent3'])->find();
                                // 计算直推人上三级（实为上四级）
                                if($parent3_info && $parent3_info['pid']){
                                    $parent3_info_last = Db::name('member')
                                        ->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$parent3_info['pid'])
                                        ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    if($parent3_info_last && ($parent3_info_last['gangwei1'] > 0 || $parent3_info_last['gangwei2'] > 0) && $parent3_info_last['can_agent'] != 0
                                        && ($parent3_info_last['gangwei_only_big'] == 0 || ($parent3_info_last['gangwei_only_big'] == 1 && $parent3_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                        if($gangwei_commission > 0){
                                            $gangwei_commission = dd_money_format($gangwei_commission*$parent3_info_last['gangwei2']/100,2);
                                        }
                                        if($ogupdate['parent3commission'] > 0){
                                            $gangwei_commission_fenxiao = dd_money_format($ogupdate['parent3commission']*$parent3_info_last['gangwei1']/100,2);
                                            if($gangwei_commission_fenxiao > 0){
                                                $gangwei_commission += $gangwei_commission_fenxiao;
                                            }
                                        }
        
                                        if($gangwei_commission < 1){
                                            $gangwei_commission = 0;
                                        }
                                        if($gangwei_commission > 0){
                                            // 直推人数
                                            $zt_num = Db::name('member')->where('aid',aid)->where('pid',$parent3_info['pid'])->count('id');
                                            $is_send = 0;
                                            if($sysset['gangwei_tndn_status'] == 1 ){
                                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                    $is_send = 1;
                                                }
                                            }else{
                                                $is_send = 1;
                                            }

                                            if($is_send == 1){
                                                $data_p = ['aid'=>aid,'mid'=>$parent3_info['pid'],'frommid'=>$parent3_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                                $gangwei_arr[] = $data_p;
                                                $totalcommission += $gangwei_commission; 
                                                $last_pid = $parent3_info['pid'];
                                            }else{
                                                // 紧缩关闭
                                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                    $gangwei_commission = 0;
                                                }
                                            }
                                        }   
                                    }else{
                                    // 紧缩关闭
                                        if($sysset['gangwei_jinsuo_status'] == 0 ){
                                            $gangwei_commission = 0;
                                        }
                                    }   
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }
						}
                        if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                            $remark = '持续推荐奖励';
                            if(getcustom('commission_parent_pj_stop') || getcustom('commission_parent_pj_by_buyermid')){
                                $remark = '平级奖';
                            }
                            $data_c = ['aid'=>aid,'mid'=>$ogupdate['parent4'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()];
                            if(getcustom('member_forzengxcommission')){
                                if($protypes && $protypes[0] == 10){
                                    $data_c['product_type'] = 10;//佣金类商品
                                }
                            }
                            if(getcustom('up_giveparent_userdata')){
                                //记录佣金类型，方便后续数据统计
                                $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent4'];
                            }
                            Db::name('member_commission_record')->insert($data_c);
							$totalcommission += $ogupdate['parent4commission'];
                        }
                        if(getcustom('commission_parent_pj')) {
                            if ($ogupdate['parent_pj1'] && ($ogupdate['parent1commission_pj'] > 0)) {
                                $remark = '平级一级奖励';
                                $data_c = ['aid' => aid, 'mid' => $ogupdate['parent_pj1'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                Db::name('member_commission_record')->insert($data_c);
                                $totalcommission += $ogupdate['parent1commission_pj'];
                            }
                            if ($ogupdate['parent_pj2'] && ($ogupdate['parent2commission_pj'] > 0)) {
                                $remark = '平级二级奖励';
                                $data_c = ['aid' => aid, 'mid' => $ogupdate['parent_pj2'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                Db::name('member_commission_record')->insert($data_c);
                                $totalcommission += $ogupdate['parent2commission_pj'];
                            }
                            if ($ogupdate['parent_pj3'] && ($ogupdate['parent3commission_pj'] > 0)) {
                                $remark = '平级三级奖励';
                                $data_c = ['aid' => aid, 'mid' => $ogupdate['parent_pj3'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                Db::name('member_commission_record')->insert($data_c);
                                $totalcommission += $ogupdate['parent1commission_pj'];
                            }
                        }

                        // 岗位提成 发给原上级
                        if(getcustom('commission_gangwei') && in_array($sysset['gangwei_give_origin_status'], [1,2])){
                            $usermember = $this->member;
                            if($usermember['pid_origin']){
                                $usermember['pid'] = $usermember['pid_origin'];
                                $usermember['path'] = $usermember['path_origin'];
                            }

                            if($sysset['fenxiao_manage_status']){
                                $commission_data_l = \app\common\Fenxiao::fenxiao_jicha($sysset,$usermember,$product,$num,$commission_totalprice);
                            }else{
                                $commission_data_l = \app\common\Fenxiao::fenxiao($sysset,$usermember,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3);
                            }
                            if($commission_data_l['parent1'] && $commission_data_l['parent1commission']>0){

                                $last_pid = $ordermid;
                                $gangwei_ceng  = 1;
                                $parent1_info = Db::name('member')->where('aid',aid)->where('id',$commission_data_l['parent1'])->find();
                                if($sysset['gangwei_give_origin_status'] == 2){
                                    if($parent1_info['pid_origin']){
                                        $parent1_info['pid'] = $parent1_info['pid_origin'];
                                    }
                                }

                                // 计算上一级
                                if($parent1_info && $commission_data_l['parent1commission']>0 && $parent1_info['pid']){
                                    $parent1_info_last = Db::name('member')
                                        ->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$parent1_info['pid'])
                                        ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    if($parent1_info_last && $parent1_info_last['gangwei1'] > 0 && $parent1_info_last['can_agent'] != 0){

                                        $commission_p = dd_money_format($commission_data_l['parent1commission']*$parent1_info_last['gangwei1']/100,2);
                                        if($commission_p >= 1){
                                            $gangwei_commission = $commission_p;
                                        }
                                        if($gangwei_commission > 0){
                                            // 条件
                                            $zt_num = Db::name('member')->where('aid',aid)->where('pid',$parent1_info['pid'])->count('id');
                                            $is_send = 0;
                                            if($sysset['gangwei_tndn_status'] == 1 ){
                                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                    $is_send = 1;
                                                }
                                            }else{
                                                $is_send = 1;
                                            }
                                            if($is_send == 1){
                                                $data_p = ['aid'=>aid,'mid'=>$parent1_info['pid'],'frommid'=>$parent1_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                                $gangwei_arr[] = $data_p;
                                                $totalcommission += $gangwei_commission;
                                                $last_pid = $parent1_info['pid'];
                                            }else{
                                                // 紧缩关闭
                                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                    $gangwei_commission = 0;
                                                }
                                            }
                                        }
                                        
                                    }
                                }
                                
                            }
                            if($commission_data_l['parent2']){
                                
                                $gangwei_ceng  = 2;
                                $parent2_info = Db::name('member')->where('aid',aid)->where('id',$commission_data_l['parent2'])->find();
                                if($sysset['gangwei_give_origin_status'] == 2){
                                    if($parent2_info['pid_origin']){
                                        $parent2_info['pid'] = $parent2_info['pid_origin'];
                                    }
                                }
                                // 计算上二级
                                if($parent2_info &&  $parent2_info['pid']){
                                    $parent2_info_last = Db::name('member')
                                        ->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$parent2_info['pid'])
                                        ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                    if($parent2_info_last && ($parent2_info_last['gangwei1'] > 0 || $parent2_info_last['gangwei2'] > 0) && $parent2_info_last['can_agent'] != 0
                                        && ($parent2_info_last['gangwei_only_big'] == 0 || ($parent2_info_last['gangwei_only_big'] == 1 && $parent2_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                        if($gangwei_commission > 0){
                                            $gangwei_commission = dd_money_format($gangwei_commission*$parent2_info_last['gangwei2']/100,2);
                                        }
                                        if($commission_data_l['parent2commission'] > 0){
                                            $gangwei_commission_fenxiao = dd_money_format($commission_data_l['parent2commission']*$parent2_info_last['gangwei1']/100,2);
                                            if($gangwei_commission_fenxiao > 0){
                                                $gangwei_commission += $gangwei_commission_fenxiao;
                                            }
                                        }
                                        
                                        if($gangwei_commission < 1){
                                            $gangwei_commission = 0;
                                        }
                                        if($gangwei_commission > 0){
                                            // 直推人数
                                            $zt_num = Db::name('member')->where('aid',aid)->where('pid',$parent2_info['pid'])->count('id');

                                            $is_send = 0;
                                            if($sysset['gangwei_tndn_status'] == 1 ){
                                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                    $is_send = 1;
                                                }
                                            }else{
                                                $is_send = 1;
                                            }

                                            if($is_send == 1){
                                                $data_p = ['aid'=>aid,'mid'=>$parent2_info['pid'],'frommid'=>$parent2_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                                $gangwei_arr[] = $data_p;
                                                $totalcommission += $gangwei_commission;
                                                $last_pid = $parent2_info['pid'];
                                            }else{
                                                // 紧缩关闭
                                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                    $gangwei_commission = 0;
                                                }
                                            }
                                        }
                                    }else{
                                        // 紧缩关闭
                                        if($sysset['gangwei_jinsuo_status'] == 0 ){
                                            $gangwei_commission = 0;
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                                
                            }
                            if($commission_data_l['parent3']){

                                $gangwei_ceng  = 3;
                                $parent3_info = Db::name('member')->where('aid',aid)->where('id',$commission_data_l['parent3'])->find();
                                if($sysset['gangwei_give_origin_status'] == 2){
                                    if($parent3_info['pid_origin']){
                                        $parent3_info['pid'] = $parent3_info['pid_origin'];
                                    }
                                }

                                if($parent3_info && $parent3_info['pid']){
                                    $parent3_info_last = Db::name('member')
                                        ->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$parent3_info['pid'])
                                        ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                    if($parent3_info_last && ($parent3_info_last['gangwei1'] > 0 || $parent3_info_last['gangwei2'] > 0) && $parent3_info_last['can_agent'] != 0
                                        && ($parent3_info_last['gangwei_only_big'] == 0 || ($parent3_info_last['gangwei_only_big'] == 1 && $parent3_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                        if($gangwei_commission > 0){
                                            $gangwei_commission = dd_money_format($gangwei_commission*$parent3_info_last['gangwei2']/100,2);
                                        }
                                        if($commission_data_l['parent3commission'] > 0){
                                            $gangwei_commission_fenxiao = dd_money_format($commission_data_l['parent3commission']*$parent3_info_last['gangwei1']/100,2);
                                            if($gangwei_commission_fenxiao > 0){
                                                $gangwei_commission += $gangwei_commission_fenxiao;
                                            }
                                        }
        
                                        if($gangwei_commission < 1){
                                            $gangwei_commission = 0;
                                        }
                                        if($gangwei_commission > 0){
                                            // 直推人数
                                            $zt_num = Db::name('member')->where('aid',aid)->where('pid',$parent3_info['pid'])->count('id');
                                            $is_send = 0;
                                            if($sysset['gangwei_tndn_status'] == 1 ){
                                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                    $is_send = 1;
                                                }
                                            }else{
                                                $is_send = 1;
                                            }

                                            if($is_send == 1){
                                                $data_p = ['aid'=>aid,'mid'=>$parent3_info['pid'],'frommid'=>$parent3_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                                $gangwei_arr[] = $data_p;
                                                $totalcommission += $gangwei_commission; 
                                                $last_pid = $parent3_info['pid'];
                                            }else{
                                                // 紧缩关闭
                                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                    $gangwei_commission = 0;
                                                }
                                            }
                                        }   
                                    }else{
                                    // 紧缩关闭
                                        if($sysset['gangwei_jinsuo_status'] == 0 ){
                                            $gangwei_commission = 0;
                                        }
                                    }   
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                                
                            }
                        }
                        // 岗位提成 无限层
                        if($gangwei_commission == 0 && $oneLevelNoGangwei) $gangwei_commission = $oneLevelNoGangwei;//上3级无岗位提成 只有一级分销的情况
                        if(getcustom('commission_gangwei') && $gangwei_commission > 0 && $last_pid){
                            $new_path = \app\common\Member::getPids(aid,$sysset,$last_pid);
                            $pids = explode(',', $new_path);
                            $pids = array_reverse($pids);
                            foreach ($pids as $pid_v) {
              
                                // 直推人数
                                $zt_num = Db::name('member')->where('aid',aid)->where('pid',$pid_v)->count('id');
       
                                $is_send = 0;
                                if($sysset['gangwei_tndn_status'] == 1 ){
                                    if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                        $is_send = 1;
                                    }
                                }else{
                                    $is_send = 1;
                                }
                                if($is_send == 1){
                                    $member_info = Db::name('member')->alias('m')
                                        ->join('member_level l','l.id=m.levelid')
                                        ->where('m.aid',aid)
                                        ->where('m.id',$pid_v)
                                        ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                        ->find();
                                    if($member_info && $member_info['can_agent']!=0 && $member_info['gangwei2'] > 0 && ($member_info['gangwei_only_big'] == 0 || ($member_info['gangwei_only_big'] == 1 && $member_info['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                        // 金额
                                        $gangwei_commission = dd_money_format($gangwei_commission*$member_info['gangwei2']/100,2);
                                        if($gangwei_commission < 1){
                                            break;
                                        }

                                        $data_p = ['aid'=>aid,'mid'=>$pid_v,'frommid'=>$last_pid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                        $gangwei_arr[] = $data_p;
                                        $totalcommission += $gangwei_commission;
                                        $last_pid = $pid_v;
                                    }else{
                                        // 紧缩关闭
                                        if($sysset['gangwei_jinsuo_status'] == 0 ){
                                            $gangwei_commission = 0;
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                                $gangwei_ceng ++;
                            }
                        }
                        // dump($gangwei_arr);die;
                        if(getcustom('commission_gangwei') && !empty($gangwei_arr)){
                            Db::name('member_commission_record')->insertAll($gangwei_arr);
                        }


                        if(getcustom('commission_bole')){
                            //分销伯乐奖
                            if($commission_data['parent2_bole'] && $commission_data['parent2commission_bole']>0){
                                $data_c = ['aid'=>aid,'mid'=>$commission_data['parent2_bole'],'frommid'=>$ogupdate['parent1'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['parent2commission_bole'],'remark'=>'分销伯乐奖','createtime'=>time()];
                                Db::name('member_commission_record')->insert($data_c);
                            }
                            if($commission_data['parent3_bole'] && $commission_data['parent3commission_bole']>0){
                                $data_c = ['aid'=>aid,'mid'=>$commission_data['parent3_bole'],'frommid'=>$ogupdate['parent2'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['parent3commission_bole'],'remark'=>'分销伯乐奖','createtime'=>time()];
                                Db::name('member_commission_record')->insert($data_c);
                            }
                            if($commission_data['parent4_bole'] && $commission_data['parent4commission_bole']>0){
                                $data_c = ['aid'=>aid,'mid'=>$commission_data['parent4_bole'],'frommid'=>$ogupdate['parent3'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['parent4commission_bole'],'remark'=>'分销伯乐奖','createtime'=>time()];
                                Db::name('member_commission_record')->insert($data_c);
                            }
                        }
                        
                        if(getcustom('commission_product_self_buy')){
                            //自购佣金加入佣金记录
                            if($commission_data['selfbuy_commission']>0 && $ordermid){
                                $data_selfbuy = ['aid'=>aid,'mid'=>$ordermid,'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['selfbuy_commission'],'remark'=>'自购佣金','createtime'=>time()];
                                Db::name('member_commission_record')->insert($data_selfbuy);
                            }
                        }
					}
					
					if($post['checkmemid'] && $commission_totalprice > 0){
						$checkmember = Db::name('member')->where('aid',aid)->where('id',$post['checkmemid'])->find();
						if($checkmember){
							$buyselect_commission = $levelList[$checkmember['levelid']]['buyselect_commission'];
							$checkmemcommission = $buyselect_commission * $commission_totalprice * 0.01;
							Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$checkmember['id'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$checkmemcommission,'score'=>0,'remark'=>'购买商品时指定奖励','createtime'=>time()]);
						}
					}
				}
				if(getcustom('zhitui_pj')){
				    //直推平级奖
				    $zhitui_pj = json_decode($product['zhitui_pj'],true);
				    $parent = Db::name('member')->where('id',$this->member['pid'])->find();
				    $member_levelid = $this->member['levelid'];
                    $zhitui_pj_commission = $zhitui_pj[$member_levelid]??0;
                    $zhitui_pj_commission = bcmul($zhitui_pj_commission,$num,2);
				    if($parent && $parent['levelid']==$member_levelid && $zhitui_pj_commission>0){
                        Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$parent['id'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$zhitui_pj_commission,'score'=>0,'remark'=>'直推平级奖','createtime'=>time()]);
                    }
                }

				
				if($product['commissionset4']==1 && $product['lvprice']==1){ //极差分销
					if(getcustom('jicha_removecommission')){ //算极差时先减去分销的钱
						$commission_totalpriceCache = $commission_totalpriceCache - $totalcommission;
					}
					if($this->member['path']){
					    $send_origin = 0;
                        if(getcustom('lvprice_jicha_lv')){
                            //是否发放给原上级
                            $send_origin = $product['lvprice_jicha_origin'];
                        }
                        if($send_origin){
                            //$parentList = Db::name('member')->where('id','in',$this->member['path_origin'])->order(Db::raw('field(id,'.$this->member['path_origin'].')'))->select()->toArray();
                            $parentList = \app\common\Member::queryOriginPath(aid,mid,$product['lvprice_jicha_lv']?:50);
                        }else{
                            $parentList = Db::name('member')->where('id','in',$this->member['path'])->order(Db::raw('field(id,'.$this->member['path'].')'))->select()->toArray();
                            $parentList = array_reverse($parentList);
                        }

						if($parentList) {
                            $lvprice_data = json_decode($guige['lvprice_data'], true);
                            $nowprice = $commission_totalpriceCache;
                            $giveidx = 0;
                            $isclose_jicha = 0;
                            if (getcustom('member_level_close_jicha')){
                                if($agleveldata['isclose_jicha']==1) $isclose_jicha = 1;
                            }

							foreach($parentList as $k=>$parent){
								if($parent['levelid'] && $lvprice_data[$parent['levelid']]){
									$thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
									if($nowprice > $thisprice){
										$commission = $nowprice - $thisprice;
										$nowprice = $thisprice;
										$giveidx++;
										//if($giveidx <=3){
										//	$ogupdate['parent'.$giveidx] = $parent['id'];
										//	$ogupdate['parent'.$giveidx.'commission'] = $commission;
										//}
                                        if(!$isclose_jicha) {
                                            $remark = t('下级') . '购买商品差价';
                                            if(t('等级价格极差分销')!='等级价格极差分销'){
                                                $remark = t('下级') . '购买商品'.t('等级价格极差分销');
                                            }
                                            $data_c = ['aid' => aid, 'mid' => $parent['id'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                            if(getcustom('up_giveparent_userdata')){
                                                //记录佣金类型，方便后续数据统计
                                                $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent_jicha'];
                                            }
                                            Db::name('member_commission_record')->insert($data_c);
                                        }

										//平级奖
										if(getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')){
											if($parentList[$k+1] && $parentList[$k+1]['levelid'] == $parent['levelid']){
												$parent2 = $parentList[$k+1];
												$parent2lv = $levelList[$parent2['levelid']];
												$parent2lv['commissionpingjitype'] = $parent2lv['commissiontype'];
												if($product['commissionpingjiset'] != 0){
													if($product['commissionpingjiset'] == 1){
														$commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
														$parent2lv['commission_parent_pj'] = $commissionpingjidata1[$parent2lv['id']]['commission'];
													}elseif($product['commissionpingjiset'] == 2){
														$commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
														$parent2lv['commission_parent_pj'] = $commissionpingjidata2[$parent2lv['id']]['commission'];
														$parent2lv['commissionpingjitype'] = 1;
													}else{
														$parent2lv['commission_parent_pj'] = 0;
													}
												}
												if($parent2lv['commission_parent_pj'] > 0) {
													if($parent2lv['commissionpingjitype']==0){
														$pingjicommission = $commission * $parent2lv['commission_parent_pj'] * 0.01;
													} else {
														$pingjicommission = $parent2lv['commission_parent_pj'];
													}
													if($pingjicommission > 0){
														Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$parent2['id'],'frommid'=>$parent['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$pingjicommission,'score'=>0,'remark'=>'平级奖励','createtime'=>time()]);
													}
												}
											}
										}
                                        if(getcustom('lvprice_jicha_lv')){
                                            //发放代数限制
                                            if($product['lvprice_jicha_lv']>0 && $giveidx>=$product['lvprice_jicha_lv']){
                                                break;
                                            }
                                        }
									}
								}
							}
						}
					}
				}

                if(getcustom('product_commission_mid')){
                    if($product['commission_mid']){ //商品指定会员佣金奖励
                        $commission_mid = json_decode($product['commission_mid'],true);
                        $commission_mid = array_filter($commission_mid);
                        foreach($commission_mid as $kcm=>$vcm){
                            $money=0;
                            if($vcm['mid'] && ($vcm['percent']>0 || $vcm['money']>0)){
                                if($vcm['percent']>0) $money += $commission_totalprice*$vcm['percent']/100;
                                $money += $vcm['money'] * $num;
                                $money = round($money,2);
                                if($money > 0){
                                    Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$vcm['mid'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$money,'score'=>0,'remark'=>$vcm['name'],'createtime'=>time()]);
                                }
                            }
                        }
                    }
                }

				if(getcustom('commission_givedown')){
					$commission_recordlist = Db::name('member_commission_record')->field("mid,sum(commission) totalcommission")->where('aid',aid)->where('orderid',$orderid)->where('ogid',$ogid)->where('type','shop')->where('commission','>',0)->group('mid')->select()->toArray();
					foreach($commission_recordlist as $record){
						$thismember = Db::name('member')->where('id',$record['mid'])->find();
						$memberlevel = $levelList[$thismember['levelid']];
						if($memberlevel && ($memberlevel['givedown_percent'] > 0 || $memberlevel['givedown_commission'] > 0)){
							$downmemberlist = Db::name('member')->where('aid',aid)->where('pid',$record['mid'])->select()->toArray();
							if(!$downmemberlist) continue;
							$downcommission = $memberlevel['givedown_commission'] / count($downmemberlist) + $record['totalcommission'] * $memberlevel['givedown_percent'] * 0.01 / count($downmemberlist);
							foreach($downmemberlist as $downmember){
								Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$downmember['id'],'frommid'=>$thismember['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$downcommission,'score'=>0,'remark'=>$memberlevel['givedown_txt'],'createtime'=>time()]);
							}
						}
					}
				}

				if(getcustom('everyday_hongbao')) {
                    $hongbaoEdu = 0;
                    if($product['everyday_hongbao_bl'] === null) {
                        $hongbaoEdu = $og_totalprice * $hd['shop_product_hongbao_bl'] / 100;
                    } elseif($product['everyday_hongbao_bl'] > 0 ) {
                        $hongbaoEdu = $og_totalprice * $product['everyday_hongbao_bl'] / 100;
                    }
                    $hongbaoEdu = round($hongbaoEdu,2);
                    if($hongbaoEdu > 0)
                        Db::name('shop_order_goods')->where('id',$ogid)->update(['hongbaoEdu' => $hongbaoEdu]);
                }
                
				//删除购物车
                $cart_where = [];
                if(getcustom('shop_product_jialiao')){
                    if($data['jldata'][$key]){
                        $cart_where[] = ['jldata' , '=',json_encode($data['jldata'][$key],JSON_UNESCAPED_UNICODE)]; 
                    }
                }
				Db::name('shop_cart')->where('aid',aid)->where('mid',mid)->where('ggid',$guige['id'])->where('proid',$product['id'])->where($cart_where)->delete();
				//减库存
                
                if(getcustom('shop_yuding')){
                    if($product['stock'] <=0  && $product['yuding_stock'] > 0){
                        Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->update(['stock'=>Db::raw("stock+$num"),'yuding_stock'=>Db::raw("yuding_stock-$num")]);
                    }
                }
				//多单位时计算最低单位数量
				// if(getcustom('more_productunit_guige')){
                //     $num = $gg_min_unit_num*$num;
                // }
                if($is_mendian_usercetner && $orderdata['lock_mdid']){
                    //门店中心扣除门店的库存
                    \app\custom\MendianUsercenter::decMendianStock(aid,$orderdata['lock_mdid'],$product['id'],$guige['id'],$num);
                }
                if(!$is_mendian_usercetner){
                    Db::name('shop_guige')->where('aid',aid)->where('id',$guige['id'])->update(['stock'=>Db::raw("stock-$num"),'sales'=>Db::raw("sales+$num")]);
                    Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->update(['stock'=>Db::raw("stock-$num"),'sales'=>Db::raw("sales+$num")]);
                }
				if(getcustom('guige_split')){
					\app\model\ShopProduct::declinkstock($product['id'],$guige['id'],$num);
				}
                //下单后商品库存为0时自动下架隐藏
                if(getcustom('product_nostock_show')) {
                    $stock = Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->value('stock');
                    if($stock <= 0 && $shopset['product_nostock_show'] == 0){
                        Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->update(['status' =>0]);
                    }
                }

			}
            //订单创建完成后操作
            $orderids = \app\model\ShopOrder::after_create(aid,$orderid);
			if(empty($orderids)){
                $orderids = [$orderid];
            }
            if(getcustom('yx_liandong')){
                foreach($orderids as $orderid){
                    //新链动活动
                    $og_list = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();
                    $pid = input('pid');
                    foreach($og_list as $og){
                        \app\custom\Liandong::add_active_record(aid,mid,$orderid,$og['id'],$og['proid'],$pid);
                    }
                }
            }
            if(getcustom('yx_network_help')){
                foreach($orderids as $orderid){
                    //新链动活动
                    $og_list = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();
                    $pid = input('pid');
                    foreach($og_list as $og){
                        \app\custom\NetworkHelp::add_active_record(aid,mid,$orderid,$og['id'],$og['proid'],$pid);
                    }
                }
            }
			Db::commit();
            foreach($orderids as $orderid){
                if(getcustom('product_supplier_admin')){
                    //拆单后重新查询订单数据
                    if(count($orderids)>1){
                        $orderdata = Db::name('shop_order')->where('id',$orderid)->find();
                    }
                }
                //下单后增加分销数据统计-单量
                if(getcustom('transfer_order_parent_check')) {
                    \app\common\Fenxiao::addTotalOrderNum(aid, mid, $orderid, 1);
                    //上交金额和差价
                    \app\common\Fenxiao::addDifferential(aid, mid, $orderid);
                }
                //需要同步到供货商的订单
                if(getcustom('product_supply_chain')) {
                    if(!$usegiveorder && $orderdata['product_type']==7 && !in_array($orderdata['trade_type'],['1101','1303'])) {
                        $resultSp = \app\custom\Chain::syncOrderToSupplier(aid, $orderid);
                    }
                }
                $jushuitanadd = true;
                if(getcustom('supply_zhenxin')){
                    //甄新汇选不推送聚水潭
                    if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyzhenxin'){
                        $jushuitanadd = false;
                    }
                }
                if(getcustom('supply_yonsheng')){
                    //永盛商品不推送聚水潭
                    if($bidGroupArr[1] && $bidGroupArr[1] == 'supplyyonsheng'){
                        $jushuitanadd = false;
                    }
                }
                if(getcustom('shop_order')){
                    //赠送礼物暂不推送聚水潭
                    if($usegiveorder) $jushuitanadd = false;
                }
                if(getcustom('jushuitan') && $this->sysset['jushuitankey'] && $this->sysset['jushuitansecret']){
                    if($sysset['jushuitankey'] && $jushuitanadd){
                        //创建聚水潭订单
                        $order = Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->find();
                        $rs = \app\custom\Jushuitan::createOrder($order,'WAIT_BUYER_PAY');
                        if(!$rs['code']){
                            $ordernum = $rs['data']['datas']['so_id'];
                            //修改订单的聚水潭内部单号
                        }
                    }
                }


                if(platform == 'toutiao'){
                    \app\common\Ttpay::build(aid,$ordermid,$orderdata['title'],$orderdata['ordernum'],$orderdata['totalprice'],'shop');
                    \app\common\Ttpay::pushorder(aid,$orderdata['ordernum'],0);
                }

                if(getcustom('business_sales_quota')){
                    if($orderdata['bid']>0){
                        $business = Db::name('business')->where(['aid'=>aid,'id'=>$orderdata['bid']])->field('kctime,kctype')->find();
                        if($business['kctime']==1){
                            $remark = '订单号：'.$orderdata['ordernum'];
                            \app\common\Business::addsalesquota(aid,$orderdata['bid'],$sales_price,$remark,$orderid);
                        }
                    }
                }

                // 店铺补贴
                if(getcustom('yx_shop_order_team_yeji_bonus')){
                    $time = time();
                    $where_shop_bonus= [];
                    $where_shop_bonus[] = ['aid','=',aid];
                    $where_shop_bonus[] = ['bid','=',$orderdata['bid'] ?? 0];
                    $where_shop_bonus[] = ['status','=',1];
                    $where_shop_bonus[] = ['back_ratio','>',0];
                    $where_shop_bonus[] = ['starttime','<',$time];
                    $where_shop_bonus[] = ['endtime','>',$time];
                    $shop_bonus = Db::name('shop_bonus')->where($where_shop_bonus)->select()->toArray();
                    if($shop_bonus && $this->member['path']){
                        $data_bonus = [];
                        foreach ($shop_bonus as $k => $v) {
                            $mids = explode(',', $v['mids']);
                            $path_ids = Db::name('member')->where('id','in',$this->member['path'])->order(Db::raw('field(id,'.$this->member['path'].')'))->column('id');

                            $path_ids = array_reverse($path_ids);
                            foreach ($path_ids as $path_id) {
                                if(in_array($path_id, $mids)){
                                    // 写记录
                                    $commission = dd_money_format($orderdata['totalprice'] * $v['back_ratio']/100,2);
                                    $data_shop_bonus = [];
                                    $data_shop_bonus = ['aid'=>aid,'mid'=>$path_id,'active_id'=>$v['id'],'frommid'=>$ordermid,'orderid'=>$orderid,'totalprice'=>$orderdata['totalprice'],'commission'=>$commission,'bili'=>$v['back_ratio'],'createtime'=>time()];
                                    $data_bonus[] = $data_shop_bonus;
                                    break;
                                }
                            }

                        }
                        if(!empty($data_bonus)){
                            Db::name('shop_bonus_log')->insertAll($data_bonus);
                        }
                    }
                }
                // 分红
                if(getcustom('yx_yeji_fenhong')){
                    $time = time();
                    $where_yeji_fenhong= [];
                    $where_yeji_fenhong[] = ['aid','=',aid];
                    $where_yeji_fenhong[] = ['bid','=',$orderdata['bid'] ?? 0];
                    $where_yeji_fenhong[] = ['status','=',1];
                    $where_yeji_fenhong[] = ['back_ratio','>',0];
                    $where_yeji_fenhong[] = ['starttime','<',$time];
                    $where_yeji_fenhong[] = ['endtime','>',$time];
                    $yeji_fenhong = Db::name('yeji_fenhong')->where($where_yeji_fenhong)->select()->toArray();
                    if($yeji_fenhong){
                        $data_bonus = [];
                        foreach ($yeji_fenhong as $k => $v) {
                            if(!$v['mids']){
                                continue;
                            }
                            $mids = explode(',', $v['mids']);
                            // 写记录
                            $commission_total = dd_money_format($orderdata['totalprice'] * $v['back_ratio']/100,2);
                            $commission = $commission_total/count($mids);
                            foreach ($mids as $mid) {
                                $data_yeji_fenhong = [];
                                $data_yeji_fenhong = ['aid'=>aid,'mid'=>$mid,'frommid'=>$ordermid,'active_id'=>$v['id'],'orderid'=>$orderid,'totalprice'=>$orderdata['totalprice'],'commission_total'=>$commission_total,'commission'=>$commission,'bili'=>$v['back_ratio'],'createtime'=>time()];
                                $data_bonus[] = $data_yeji_fenhong;
                            }
                        }
                        if(!empty($data_bonus)){
                            Db::name('yeji_fenhong_log')->insertAll($data_bonus);
                        }
                    }
                }


                if(getcustom('commission_notice_twice')){
                    $tmplcontentNew = [];
                    $tmplcontentNew['character_string1'] = $orderdata['ordernum'];
                    $tmplcontentNew['thing2'] = $orderdata['title'];
                    $tmplcontentNew['amount3'] = $orderdata['totalprice'].'元';
                    $tmplcontentNew['time4'] = date('Y-m-d H:i:s',$orderdata['createtime']);

                    //根据订单查询上级佣金
                    $commissionList = Db::name('member_commission_record')
                        ->field('mid,SUM(commission) as commission')
                        ->where('aid',aid)
                        ->where('orderid',$orderid)
                        ->group('mid')
                        ->select()
                        ->toArray();
                    if($commissionList){
                        foreach ($commissionList as $k => $v) {
                            $tmplcontentNew['amount5'] = $v['commission'].'元';
                            \app\common\Wechat::sendtmpl(aid,$v['mid'],'tmpl_commission_success',[],m_url('pages/my/usercenter',aid),$tmplcontentNew);
                        }
                    }
                }
                if(getcustom('erp_hupun')){
                    //万里牛erp
                    $wln = new \app\custom\Hupun($order['aid']);
                    $wln->orderPush($orderid);
                }

                if(getcustom('member_dedamount')){
                    //抵扣金直推奖
                    if($this->sysset['dedamount_fenxiao_ztreward'] && $this->sysset['dedamount_fenxiao_ztreward_ratio']>0){
                        \app\common\Order::dealDedamountZtreward(aid,$orderid,$orderdata,'shop',$this->sysset);
                    }
                }
            }

			//公众号通知 订单提交成功
			$tmplcontent = [];
			$tmplcontent['first'] = '有新订单提交成功';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $store_name; //店铺
			$tmplcontent['keyword2'] = date('Y-m-d H:i:s',$orderdata['createtime']);//下单时间
			$tmplcontent['keyword3'] = $orderdata['title'];//商品
			$tmplcontent['keyword4'] = $orderdata['totalprice'].'元';//金额
            $tempconNew = [];
            $tempconNew['character_string2'] = $orderdata['ordernum'];//订单号
            $tempconNew['thing8'] = $store_name;//门店名称
            $tempconNew['thing3'] = $orderdata['title'];//商品名称
            $tempconNew['amount7'] = $orderdata['totalprice'];//金额
            $tempconNew['time4'] = date('Y-m-d H:i:s',$orderdata['createtime']);//下单时间
			\app\common\Wechat::sendhttmpl(aid,$orderdata['bid'],'tmpl_orderconfirm',$tmplcontent,m_url('admin/order/shoporder'),$orderdata['mdid'],$tempconNew);
			
			$tmplcontent = [];
			$tmplcontent['thing11'] = $orderdata['title'];
			$tmplcontent['character_string2'] = $orderdata['ordernum'];
			$tmplcontent['phrase10'] = '待付款';
			$tmplcontent['amount13'] = $orderdata['totalprice'].'元';
			$tmplcontent['thing27'] = $this->member['nickname'];
			\app\common\Wechat::sendhtwxtmpl(aid,$orderdata['bid'],'tmpl_orderconfirm',$tmplcontent,'admin/order/shoporder',$orderdata['mdid']);
		}
		if(count($buydata) > 1 || count($orderids)>1){ //创建合并支付单
			$payparams = [];//payorder表额外参数
			if(getcustom('member_level_moneypay_price')){
	            $payparams['putongprice'] = $alltotalputongprice && $alltotalputongprice>0?$alltotalputongprice:0;//所有商品普通价格总价
	        }
			$payorderid = \app\model\Payorder::createorder(aid,0,$ordermid,'shop_hb',$orderid,$ordernum,$orderdata['title'],$alltotalprice,$alltotalscore,0,$payparams,$total_freezemoney_price);
		}

        if($businessPayCount > 0 && ($businessCount > 1 || $platformCount > 0)){
            //多个商家一起结算，并且有开启独立收款的的情况下跳转待支付
            return $this->json(['status'=>-4,'msg'=>'根据支付规则，请分别支付多个订单','url'=>'/pagesExt/order/orderlist']);
        }

		return $this->json(['status'=>1,'payorderid'=>$payorderid,'msg'=>'提交成功']);
	}
    //库存不足提醒
	public function tmpl_stockwarning($product,$guige,$shop_stock_warning_num,$ordernum = 1){
        if(getcustom('shop_stock_warning_notice')){
            $tmplcontent = [];
            $tmplcontent['first'] = '库存不足提醒';
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $ordernum;
            $tmplcontent['keyword2'] = date('Y-m-d H:i');//时间
            $tmplcontent['keyword3'] = $product['name'].$guige['name'].'商品id'.$product['id'].'剩余库存'.$shop_stock_warning_num;//名称
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $ordernum;
            $tmplcontentNew['time2'] = date('Y-m-d H:i');//时间
            $tmplcontentNew['thing3'] = $product['name'].$guige['name'].'商品id'.$product['id'].'剩余库存'.$shop_stock_warning_num;//名称
            \app\common\Wechat::sendhttmpl(aid,$product['bid'],'tmpl_stockwarning',$tmplcontent,'',0,$tmplcontentNew);

            $tmplcontent = [];
            $tmplcontent['thing1'] = $product['name'].$guige['name'];
            $tmplcontent['number2'] = $shop_stock_warning_num;
            $tmplcontent['thing3'] = '商品id'.$product['id'].'库存不足请及时补充';
            \app\common\Wechat::sendhtwxtmpl(aid,$product['bid'],'tmpl_stockwarning',$tmplcontent);

            $info['title'] = '商城商品库存不足提醒,商品：'.$product['name'];
            $info['content'] = $product['name'].$guige['name'].',商品id'.$product['id'].'库存不足请及时补充';
            $info['createtime'] = time();
            $info['type'] = 1;

            $userlist = Db::name('admin_user')->field('id,aid,bid')->where('aid',aid)->where('bid','=', $product['bid'])->where('status',1)->select()->toArray();

            foreach($userlist as $user){
                $info['aid'] = $user['aid'];
                $info['bid'] = $user['bid'];
                $info['uid'] = $user['id'];
                Db::name('admin_notice')->insert($info);
            }
        }
        return true;
    }
	public function memberSearch(){
		$search = input('param.diqu');
		if($search){
			$search = explode(',',$search);
			$where = [];
			$where[] = ['aid','=',aid];
			if($search[0]!='全部'){
				$where[]=['province','=',$search[0]];
			}
			if($search[1]!='全部'){
				$where[]=['city','=',$search[1]];
			}
			if($search[2]!='全部'){
				$where[]=['area','=',$search[2]];
			}
			
			$levelids = Db::name('member_level')->where('aid',aid)->where('can_buyselect',1)->column('id');
			$where[]=['levelid','in',$levelids];
			$memberList = db('member')->field('id,nickname,headimg,tel,province,city,area')->where($where)->select();
			if(count($memberList) > 0 ){
				return $this->json(['status'=>1,'memberList'=>$memberList,'msg'=>'获取成功']);
			}else{
				return $this->json(['status'=>0,'memberList'=>[],'msg'=>'无符合条件用户']);
			}
		}
	}
	//获取升级申请时填写的资料
	public function getmemberuplvinfo(){
		$mid = input('post.mid');
		$minfo = Db::name('member')->where('id',$mid)->find();
		$info = Db::name('member_levelup_order')->where('mid',$mid)->where('status',2)->find();
		$data = [['昵称',$minfo['nickname']]];
		for($i=0;$i<=20;$i++){
			if($info['form'.$i]){
				$thisdata = explode('^_^',$info['form'.$i]);
				if($thisdata[1]!==''){
					if($thisdata[0] == '手机号'){
						$thisdata[1] = substr($thisdata[1],0,3).'*****'.substr($thisdata[1],-3);
					}
					$data[] = $thisdata;
				}
			}
		}
		return $this->json(['info'=>$data]);
	}

	public function mendian()
    {
        $id = input('param.id');
        $info = Db::name('mendian')->where('aid',aid)->where('id',$id)->find();
        if(empty($info)) {
            return $this->json(['status'=>0,'msg'=>'门店不存在']);
        }
        if($info['status'] != 1) {
            return $this->json(['status'=>0,'msg'=>'门店未开启']);
        }

        if($info['pics']) $info['pics'] = $info['pics'] ? explode(',', $info['pics']) : [];

        return $this->json(['status'=>1,'info'=>$info]);
    }

    public function invoice()
    {
        $bid = input('param.bid/d');
        $post = input('post.');
        $order_type = input('param.type');

        if($bid) {
            $invoice = Db::name('business')->where('aid',aid)->where('id',$bid)->find();
        } else {
            $invoice = Db::name('admin_set')->where('aid',aid)->find();
        }
        if(!$invoice['invoice']) return $this->json(['status'=>0,'msg'=>'未开启发票功能']);

        //todo 上次的信息
        $rdata['status'] = 1;
        $rdata['invoice_type'] = explode(',', $invoice['invoice_type']);
        return $this->json($rdata);
    }

	//获取抖音商品id
	public function getDouyinProductId(){
		$proid = input('param.proid/d');
		$douyin_product_id = Db::name('shop_product')->where('id',$proid)->value('douyin_product_id');
		if(!$douyin_product_id) return $this->json(['status'=>0,'msg'=>'获取失败']);
		return $this->json(['status'=>1,'douyin_product_id'=>$douyin_product_id]);
	}

	public function imgsearch(){
        if(getcustom('image_search')){
            $bid = input('param.bid/d')?:0;
            $image_search = Db::name('admin')->where('id',aid)->value('image_search');
            if($image_search == 1) {
                $where = [];
                $where[] = ['aid','=',aid];
                $image_search_business_switch = Db::name('baidu_set')->where('aid',aid)->where('bid',0)->value('image_search_business_switch');
                if($image_search_business_switch){
                    $where[] = ['bid','=',$bid];
                }else{
                    $where[] = ['bid','=',0];
                }
                $set = Db::name('baidu_set')->where($where)->find();
                if($set ){
                    if($set['image_search'] == 1){
                        $rdata['status'] = 1;
                        $rdata['info']['banner'] = $set['image_search_banner'];
                        $rdata['info']['image_search_pic'] = $set['image_search_pic'];
                        return $this->json($rdata);
                    }else{
                        return $this->json(['status' => 0, 'msg' => '功能未开启']);
                    }
                }else{
                    return $this->json(['status' => 0, 'msg' => '未配置信息']);
                }
            }else{
                return $this->json(['status' => 0, 'msg' => '功能未开启']);
            }
        }
        return $this->json(['status' => 0, 'msg' => '系统错误']);
    }

    public function getprolistWithImg(){
        if(getcustom('image_search')) {
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['ischecked', '=', 1];
            if (isdouyin == 1) {
                $where[] = ['douyin_product_id', '<>', ''];
            } else {
                $where[] = ['douyin_product_id', '=', ''];
            }

            //图搜
            $imgurl = input('param.imgurl');
            $bid = input('param.bid/d')?:0;
            if ($imgurl) {
                $client = new \app\custom\Baidu(aid,$bid);
                $proids = $client->searchProduct($imgurl);
                if ($proids) {
                    $where[] = ['id', 'in', $proids];
                    $proids = implode(',', $proids);
                    $order = Db::raw('field(id,' . $proids . ')');
                } else {
                    $order = 'sort desc,id desc';
                }
            }
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order') . ',sort,id desc';
            }
            //$where[] = ['status','=',1];
            $nowtime = time();
            $nowhm = date('H:i');
            $where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

            if (input('param.bid')) {
                $where[] = ['bid', '=', input('param.bid/d')];
            } else {
                $image_search_business = Db::name('baidu_set')->where('aid',aid)->where('bid',0)->value('image_search_business');
                if($image_search_business){
                   
                }else{
                    $where[] = ['bid','=',0];
                }
            }
            //分类
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
                            $whereCid[] = "find_in_set({$c2},cid)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
                } else {
                    $where[] = Db::raw("find_in_set(" . $cid . ",cid)");
                }
            } else {
                if (getcustom('product_cat_showtj')) {
                    $where2 = "find_in_set('-1',showtj)";
                    if ($this->member) {
                        $where2 .= " or find_in_set('" . $this->member['levelid'] . "',showtj)";
                        if ($this->member['subscribe'] == 1) {
                            $where2 .= " or find_in_set('0',showtj)";
                        }
                    }
                    $tjwhere[] = Db::raw($where2);
                    $clist = Db::name('shop_category')->where($tjwhere)->where('aid', aid)->column('id');

                    if ($clist) {
                        $whereCid = [];
                        foreach ($clist as $k => $c2) {
                            $whereCid[] = "find_in_set({$c2},cid)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
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
                            $whereCid[] = "find_in_set({$c2},cid2)";
                        }
                        $where[] = Db::raw(implode(' or ', $whereCid));
                    }
                } else {
                    $where[] = Db::raw("find_in_set(" . $cid2 . ",cid2)");
                }
            }
            if (input('param.gid')) $where[] = Db::raw("find_in_set(" . intval(input('param.gid')) . ",gid)");
            if (input('param.keyword')) {
                $where[] = ['name', 'like', '%' . input('param.keyword') . '%'];
            }

            $where2 = "find_in_set('-1',showtj)";
            if ($this->member) {
                $where2 .= " or find_in_set('" . $this->member['levelid'] . "',showtj)";
                if ($this->member['subscribe'] == 1) {
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $where[] = Db::raw($where2);

            //优惠券可用商品列表
            $cpid = input('param.cpid/d');
            if ($cpid > 0) {
                $coupon = Db::name('coupon')->where('id', $cpid)->find();
                $where[] = ['bid', '=', $coupon['bid']];
                if ($coupon['fwtype'] == 1) { //指定类目
                    $categoryids = explode(',', $coupon['categoryids']);
                    $clist = Db::name('shop_category')->where('pid', 'in', $categoryids)->select()->toArray();
                    foreach ($clist as $kc => $vc) {
                        $categoryids[] = $vc['id'];
                        $cate2 = Db::name('shop_category')->where('pid', $vc['id'])->find();
                        $categoryids[] = $cate2['id'];
                    }
                    $whereCid = [];
                    foreach ($categoryids as $k => $c2) {
                        $whereCid[] = "find_in_set({$c2},cid)";
                    }
                    $where[] = Db::raw(implode(' or ', $whereCid));
                }
                if ($coupon['fwtype'] == 2) { //指定商品
                    $where[] = ['id', 'in', $coupon['productids']];
                }
            }
            $pernum = 10;
            $pagenum = input('post.pagenum/d');
            if (!$pagenum) $pagenum = 1;

            $datalist = Db::name('shop_product')->field("id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,price_type,stock")->where($where)/*->page($pagenum,$pernum)*/ ->order($order)->select()->toArray();
//		$sql = Db::getlastsql();dd($sql);
            if (!$datalist) $datalist = [];
            $datalist = $this->formatprolist($datalist);

            
            return $this->json(['status' => 1, 'data' => $datalist]);
        }
    }

    public function diylight(){
        if(getcustom('diy_light')){
            $set = Db::name('diylight_set')->where('aid',aid)->where('mid',0)->find();
            if($set['status'] == 1){
                $rdata['status'] = 1;
                $set['bgimgs'] = $set['bgimgs'] ? explode(',',$set['bgimgs']) : [];

                if(mid > 0) {
                    $userimg = Db::name('diylight_set')->where('aid',aid)->where('mid',mid)->order('id','desc')->column('bgimgs');
                    if($userimg && $set['bgimgs']){
                        $set['bgimgs'] = array_merge($userimg,$set['bgimgs']);
                    }
                }

                $rdata['data']['bgimgs'] = $set['bgimgs'];
                $proid = input('param.id/d');
                if($proid) {
                    $pro = $this->getprolistForLight($proid);
                    $pro = $pro->getData();
                    $rdata['data']['pro'] = $pro['data'][0];
                }

                return $this->json($rdata);
            }
        }
        return $this->json(['status' => 0, 'msg' => '系统错误']);
    }
    public function diylightUpload(){
	    $this->checklogin();
	    $imgurl = input('param.imgurl');
	    if(empty($imgurl)){
            return $this->json(['status' => 0, 'msg' => '请上传图片']);
        }
        if(getcustom('diy_light')){
            $set = Db::name('diylight_set')->where('aid',aid)->where('mid',0)->find();
            if($set['status'] == 1){
                $userimg = Db::name('diylight_set')->where('aid',aid)->where('mid',mid)->order('id','asc')->select()->toArray();
                $data = [
                    'aid'=>aid,
                    'mid'=>mid,
                    'bgimgs' =>$imgurl
                ];
                Db::name('diylight_set')->insert($data);
                if(count($userimg) >= 5){
                    Db::name('diylight_set')->where('id',$userimg[0]['id'])->delete();
                }

                return $this->json(['status' => 1, 'msg' => 'ok']);
            }
        }
        return $this->json(['status' => 0, 'msg' => '系统错误']);
    }

    public function getprolistForLight($proid=0){
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['ischecked','=',1];
        $where[] = ['diypics','<>',''];
        if(isdouyin == 1){
            $where[] = ['douyin_product_id','<>',''];
        }else{
            $where[] = ['douyin_product_id','=',''];
        }
        //$where[] = ['status','=',1];
        $nowtime = time();
        $nowhm = date('H:i');
        $where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");

        if(input('param.bid')){
            $where[] = ['bid','=',input('param.bid/d')];
        }else{
            $business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
            if(!$business_sysset || $business_sysset['status']==0 || $business_sysset['product_isshow']==0){
                $where[] = ['bid','=',0];
            }
        }
        if(input('param.field') && input('param.order')){
            $order = input('param.field').' '.input('param.order').',sort,id desc';
        }else{
            $order = 'sort desc,id desc';
        }
        //分类
        if(input('param.cid')){
            $cid = input('post.cid') ? input('post.cid/d') : input('param.cid/d');
            $where2 = "find_in_set('-1',showtj)";
            if($this->member){
                $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                if($this->member['subscribe']==1){
                    $where2 .= " or find_in_set('0',showtj)";
                }
            }
            $tjwhere[] = Db::raw($where2);
            //子分类
            $clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid',$cid)->column('id');
            if($clist){
                $clist2 = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->where('pid','in',$clist)->column('id');
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
        } else {
            if(getcustom('product_cat_showtj')) {
                $where2 = "find_in_set('-1',showtj)";
                if($this->member){
                    $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
                    if($this->member['subscribe']==1){
                        $where2 .= " or find_in_set('0',showtj)";
                    }
                }
                $tjwhere[] = Db::raw($where2);
                $clist = Db::name('shop_category')->where($tjwhere)->where('aid',aid)->column('id');
                if($clist){
                    $whereCid = [];
                    foreach($clist as $k => $c2){
                        $whereCid[] = "find_in_set({$c2},cid)";
                    }
                    $where[] = Db::raw(implode(' or ',$whereCid));
                }
            }
        }

        //商家的商品分类
        if(input('param.cid2')){
            $cid2 = input('post.cid2') ? input('post.cid2/d') : input('param.cid2/d');
            //子分类
            $clist = Db::name('shop_category2')->where('aid',aid)->where('pid',$cid2)->column('id');
            if($clist){
                $clist2 = Db::name('shop_category2')->where('aid',aid)->where('pid','in',$clist)->column('id');
                $cCate = array_merge($clist, $clist2, [$cid2]);
                if($cCate){
                    $whereCid = [];
                    foreach($cCate as $k => $c2){
                        $whereCid[] = "find_in_set({$c2},cid2)";
                    }
                    $where[] = Db::raw(implode(' or ',$whereCid));
                }
            } else {
                $where[] = Db::raw("find_in_set(".$cid2.",cid2)");
            }
        }
        if(input('param.gid')) $where[] = Db::raw("find_in_set(".intval(input('param.gid')).",gid)");
        if(input('param.keyword')){
            $where[] = ['name','like','%'.input('param.keyword').'%'];
        }
        if($proid){
            $where[] = ['id','=',$proid];
        }

        $where2 = "find_in_set('-1',showtj)";
        if($this->member){
            $where2 .= " or find_in_set('".$this->member['levelid']."',showtj)";
            if($this->member['subscribe']==1){
                $where2 .= " or find_in_set('0',showtj)";
            }
        }
        $where[] = Db::raw($where2);

        $pernum = 9;
        $pagenum = input('post.pagenum/d');
        if(!$pagenum) $pagenum = 1;
        $datalist = Db::name('shop_product')->field("id,pic,name,sales,market_price,sell_price,lvprice,lvprice_data,sellpoint,fuwupoint,price_type,diypics")->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
//		$sql = Db::getlastsql();dd($sql);
        if(!$datalist) $datalist = [];
        else {
            foreach ($datalist as $k => $item){
                $datalist[$k]['url'] = $item['diypics'] ? explode(',',$item['diypics']) : [];
                $datalist[$k]['realUrl'] = $datalist[$k]['url'] ? $datalist[$k]['url'][0] : '';
            }
        }

        return $this->json(['status'=>1,'data'=>$datalist]);
    }


	public function getwxScheme(){
		$this->checklogin();
		$post = input('post.');
		$platform = platform;
		$page = '/pages/shop/product';
		$scene = 'id='.$post['proid'].'&pid='.$this->member['id'];
		$product = Db::name('shop_product')->where('id',$post['proid'])->find();
		//$page =  ltrim($page,'/');
		//var_dump($page);
		//$url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token='.\app\common\Wechat::access_token(aid,'wx');
		$url ='https://api.weixin.qq.com/wxa/genwxashortlink?access_token='.\app\common\Wechat::access_token(aid,'wx');
		$data = array();
		//$data['jump_wxa'] = ['path'=>$page,'query'=>$scene];
		//$data['is_expire'] = true;
		//$data['expire_time'] = time()+86400*30;
		$data['page_url'] = 'pages/shop/product?id='.$post['proid'].'&pid='.$this->member['id'];
		$data['page_title'] = $product['name'];
		$data['is_permanent']= false;
		$res = request_post($url,jsonEncode($data));
		//var_dump($res);
		$errmsg =json_decode($res,true);
		if($errmsg['errcode']){
			if($errmsg['errcode'] == -1){
				echojson(array('status'=>0,'msg'=>'系统繁忙，此时请稍候再试'));
			}else{
				echojson(array('status'=>0,'msg'=>$errmsg['errmsg']));
			}
		}
		
		return $this->json(['status'=>1,'openlink'=>$errmsg['link']]);
	}

    //商品评价【未购买也可以直接评价商品】
    public function commentProduct(){
        if(getcustom('product_comment')){
            $this->checklogin();
            $proid = input('param.proid/d', 0);
            $product = Db::name('shop_product')->where('aid', aid)->where('status', 1)->where('id', $proid)->find();
            if (empty($product)) {
                return $this->json(['status' => 0, 'msg' => '商品不存在']);
            }
            if(request()->isPost()) {
                $shopset = Db::name('shop_sysset')->where('aid', aid)->find();
                if ($shopset['product_comment'] == 0) return $this->json(['status' => 0, 'msg' => '评价功能未开启']);
                $score = input('param.score', 0);
                $content = input('param.content', '');
                $content_pic = input('param.content_pic', '');
                $needCheck = $shopset['product_comment_check'];
                $data['aid'] = aid;
                $data['mid'] = mid;
                $data['bid'] = $product['bid'];
                $data['proid'] = $product['id'];
                $data['orderid'] = 0;
                $data['ogid'] = 0;
                $data['ggname'] = '';
                $data['proname'] = $product['name'];
                $data['propic'] = $product['pic'];
                $data['score'] = $score;
                $data['content'] = $content;
                $data['openid'] = $this->member['openid'];
                $data['nickname'] = $this->member['nickname'];
                $data['headimg'] = $this->member['headimg'];
                $data['createtime'] = time();
                $data['content_pic'] = $content_pic;
                $data['status'] = ($needCheck == 1 ? 0 : 1);
                Db::name('shop_comment')->insert($data);
                if (!$needCheck) {
                    //不需要审核
                    $countnum = Db::name('shop_comment')->where('proid', $proid)->where('status', 1)->count();
                    $score = Db::name('shop_comment')->where('proid', $proid)->where('status', 1)->avg('score'); //平均评分
                    $haonum = Db::name('shop_comment')->where('proid', $proid)->where('status', 1)->where('score', '>', 3)->count(); //好评数
                    if ($countnum > 0) {
                        $haopercent = $haonum / $countnum * 100;
                    } else {
                        $haopercent = 100;
                    }
                    Db::name('shop_product')->where('id', $proid)->update(['comment_num' => $countnum, 'comment_score' => $score, 'comment_haopercent' => $haopercent]);
                }
                return $this->json(['status' => 1, 'msg' => '商品评价成功']);
            }
            return $this->json(['status' => 1, 'msg' => 'ok','product'=>$product]);
        }
        return $this->json(['status'=>0,'msg'=>'failed']);
    }

    public function checkDiscountCodeZc($invitecode = '')
    {
        if(getcustom('discount_code_zhongchuang')){
            //下单优惠代码 中创
            $invitecode = $invitecode ? $invitecode : input('param.discount_code_zc');
            if(empty($invitecode)){
                return $this->json(['status'=>0,'msg'=>'不能为空']);
            }
            $url ='https://zckl.zhoming.top/imcore/api/mall/getUserInfo';
            $data = array();
            $data['invitecode']= $invitecode;
            $res = curl_post($url,jsonEncode($data),0,array('Content-Type: application/json'));
            $res = json_decode($res,true);
            if($res['code'] == 200)
                return $this->json(['status' => 1, 'msg' => 'ok','data'=>$res['data']]);
            else
                return $this->json(['status' => 0, 'msg' => $res['msg'],'data'=>$res,'parama'=>$data]);
        }
    }
    public function getOrderAddress()
    {
        if(getcustom('member_auto_addlogin')){
			$address_info = Db::name('member_address')->where('aid',aid)->where('mid',mid)->where('isdefault',1)->find();
			if($address_info){
				$address = $address_info;
			}else{
				$address = [];
			}
			return $this->json(['status'=>1,'data'=>$address]);
		}
	}
    public function updataOrderAddress()
    {
        if(getcustom('member_auto_addlogin')){
			$address = input('post.');
			$post = $address;
			$orderid = intval($address['orderid']);
			
			$order = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
			if(!$order){
				return $this->json(['status'=>0,'msg'=>'没有找到订单']);
			}
			$area = explode(',',$address['area']);
			$address['province'] = $area[0];
			$address['city'] = $area[1];
			$address['district'] = $area[2];
			$address['area'] = implode('',$area);
            $orderdata['linkman'] = $address['name'];
            $orderdata['company'] = $address['company'];
			$orderdata['tel'] = $address['tel'];
			$orderdata['area'] = $address['area'];
			$orderdata['address'] = $address['address'];
			$orderdata['longitude'] = $address['longitude'];
			$orderdata['latitude'] = $address['latitude'];
			$orderdata['area2'] = $address['province'].','.$address['city'].','.$address['district'];
			$rs = Db::name('shop_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->update($orderdata);

			$data = array();
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['name'] = $post['name'];
			$data['tel'] = $post['tel'];
			$data['address'] = $post['address'];
			$data['createtime'] = time();
            $data['company'] = $post['company'];
			$area = explode(',',$post['area']);
			$data['province'] = $area[0];
			$data['city'] = $area[1];
			$data['district'] = $area[2];
			$data['area'] = implode('',$area);
			$address_info = Db::name('member_address')->where('aid',aid)->where('mid',mid)->where('isdefault',1)->find();
			if($address_info){
				Db::name('member_address')->where('id',$address_info['id'])->update($data);
			}else{
				$data['isdefault'] = 1;
				Db::name('member_address')->insertGetId($data);
			}
            return $this->json(['status' => 1, 'msg' => 'ok','data'=>$order]);

        }
    }
    //下级会员
    public function getTeamMemberList(){
	    if(getcustom('member_create_child_order')){
            $downmids = \app\common\Member::getteammids(aid,mid);
            $teamlist = [];
            if($downmids){
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['id','in',$downmids];      
                $tmid = input('param.tmid/d') ;
                if($tmid){
                    $where[] = ['id','=',$tmid];
                }
                $teamlist = Db::name('member')->where($where)->field('id,nickname,headimg,tel')->select()->toArray();
            }
            return $this->json(['status' => 1, 'msg' => 'ok','data'=>$teamlist]);
        }
    }

    //根据商品码搜索商品
    public function scanCodeSearchGoods(){
        if(getcustom('scan_code_buy') || getcustom('shop_purchase_order')){
            $specs = input('param.specs_number/s');
            if (empty($specs)) {
                return $this->json(['status' => 0, 'msg' => '无效的规格参数']);
            }

            $productId = Db::name('shop_product')
                ->where('aid',aid)
                ->where('procode|barcode',$specs)
                ->where('status',1)
                ->value('id');

            $guigeid = 0;
            if(getcustom('shop_purchase_order')){
                //默认为第一个规格
                $gg = Db::name('shop_guige')->where('aid',aid)->where('proid',$productId)->find();
                $guigeid = $gg['id'];
            }

            if ($productId) {
                return $this->json(['status' => 1, 'msg' => 'ok', 'product_id' =>$productId,'guige_id' => $guigeid]);
            }

            // 如果没有查询到商品ID，尝试查询规格表
            $guige = Db::name('shop_guige')
                ->field('id,proid')
                ->where('aid',aid)
                ->where('barcode',$specs)
                ->find();

            // 如果查询到了再验证商品状态
            if ($guige) {
                $res = Db::name('shop_product')
                    ->where('id', $guige['proid'])
                    ->where('status', 1)
                    ->value('id');

                if ($res) {
                    return $this->json(['status' => 1, 'msg' => 'ok', 'product_id' => $res,'guige_id' => $guige['id']]);
                }
            }
            return $this->json(['status' => 0, 'msg' => '暂时没有此商品']);
        }
    }

    //获取品牌列表
    public function getbrandlist() {
        if (getcustom('product_brand')) {
            $cid = input('param.id/d', 0);
            if(empty($cid)) return $this->json(['status' => 0,'msg' => '参数错误']);
            //获取分类
            $categoryIds = $this->getAllSubCategoryIds($cid);
            $categoryIds = array_unique($categoryIds);

            $whereCid = [];
            foreach ($categoryIds as $val) {
                $whereCid[] = "find_in_set({$val}, cid)";
            }
            $whereCids = implode(' or ', $whereCid);

            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['status', '=', 1];
            $list = Db::name('shop_brand')->where($where)->whereRaw($whereCids)->order('sort desc,id desc')->select()->toArray();
            return $this->json(['status' => 1, 'data' => $list]);
        }
    }

    private function getAllSubCategoryIds($parentId) {
        if (getcustom('product_brand')) {
            $categoryIds = [$parentId]; // 包含一级
            $subCategories = Db::name('shop_category')->where('pid', '=', $parentId)->select()->toArray();
            foreach ($subCategories as $category) {
                $categoryIds[] = $category['id'];
                //递归获取子分类
                $categoryIds = array_merge($categoryIds, $this->getAllSubCategoryIds($category['id']));
            }
            return $categoryIds;
        }
    }

	//分享购物车
	public function shareCart(){
		if(getcustom('product_share_cart')){
			$this->checklogin();
			if(request()->isPost()) {
				$bid = input('post.bid/d',0);
				$prodata = input('post.prodata');

				//查询是否存在记录
				$record = Db::name('shop_cart_share_log')->where('aid',aid)->where('mid',mid)->where('bid',$bid)->where('prodata',$prodata)->find();
				if($record) {
					$data['id'] = $record['id'];
					return $this->json(['status'=>1,'msg'=>'ok','data' => $data]);
				}

				//插入记录
				$id = Db::name('shop_cart_share_log')->insertGetId([
					'aid' => aid,
					'bid' => $bid,
					'mid' => mid,
					'prodata' => $prodata,
					'createtime' => time()
				]);
				$data['id'] = $id;
				return $this->json(['status'=>1,'msg'=>'ok','data' => $data]);
			}
			$field = 'id,bid,proid,ggid,num';
			if(input('param.bid')){
				$cartlist = Db::name('shop_cart')->field($field)->where('aid',aid)->where('bid',input('param.bid'))->where('mid',mid)->order('createtime desc')->select()->toArray();
			}else{
				$cartlist = Db::name('shop_cart')->field($field)->where('aid',aid)->where('mid',mid)->order('createtime desc')->select()->toArray();
			}
			if(!$cartlist) $cartlist = [];
			$newcartlist = [];
			foreach($cartlist as $k=>$gwc){
				if($newcartlist[$gwc['bid']]){
					$newcartlist[$gwc['bid']][] = $gwc;
				}else{
					$newcartlist[$gwc['bid']] = [$gwc];
				}
			}
			$sysset = Db::name('admin_set')->where('aid',aid)->find();
			foreach($newcartlist as $bid=>$gwclist){
				if($bid == 0){
					$business = [
						'id'=>$sysset['id'],
						'name'=>$sysset['name'],
						'logo'=>$sysset['logo'],
						'tel'=>$sysset['tel']
					];
				}else{
					$business = Db::name('business')->where('aid',aid)->where('id',$bid)->field('id,name,logo,tel')->find();
				}
				$prolist = [];
				foreach($gwclist as $gwc){
					$product = Db::name('shop_product')->where('aid',aid)->where('status','<>',0)->where('id',$gwc['proid'])->find();
					if(!$product){
						Db::name('shop_cart')->where('aid',aid)->where('proid',$gwc['proid'])->delete();continue;
					}
					$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$gwc['ggid'])->find();
					if(!$guige){
						Db::name('shop_cart')->where('aid',aid)->where('ggid',$gwc['ggid'])->delete();continue;
					}
					$guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);
					if($product['perlimitdan'] > 0 && $gwc['num'] > $product['perlimitdan']){
						$gwc['num'] = $product['perlimitdan'];
						Db::name('shop_cart')->where('aid',aid)->where('id',$gwc['id'])->update(['num'=>$gwc['num']]);
					}
					$tmpitem = ['id'=>$gwc['id'],'checked'=>true,'product'=>$product,'guige'=>$guige,'num'=>$gwc['num']];
					$prolist[] = $tmpitem;
				}
				$newcartlist[$bid] = ['bid'=>$bid,'checked'=>true,'business'=>$business,'prolist'=>$prolist];
			}
			$rdata = [];
			$rdata['cartlist'] = array_values($newcartlist);
			$rdata['sharetitle'] = $this->member['nickname']."的购物清单";
			$rdata['sharedesc'] = "{$this->member['nickname']}的购物清单，都是精心挑选的好货，赶快来看看吧。";
			return $this->json($rdata);
		}
	}

	public function shareCartRecord(){
		if (getcustom('product_share_cart')) {
			$id = input('param.id/d', 0);
			if (empty($id)) return $this->json(['status' => 0, 'msg' => '参数错误']);
			$data = Db::name('shop_cart_share_log')->where('aid', aid)->where('id', $id)->find();
			if (empty($data)) return $this->json(['status' => 0, 'msg' => '没有找到记录']);
            $member = Db::name('member')->field('id,nickname')->where('id',$data['mid'])->find();
			$datalist = [];
			$prodata = explode(',', $data['prodata']) ?: [];
			foreach ($prodata as $key=> $val){
                //拆分商品ID和规格ID
				list($proid,$ggid,$num) = explode('_', $val);
				$product = Db::name('shop_product')->where('aid',aid)->where('status','<>',0)->where('id',$proid)->find();
				$guige = Db::name('shop_guige')->where('aid',aid)->where('id',$ggid)->find();
				$guige = $this->formatguige($guige,$product['bid'],$product['lvprice']);
				$datalist[$key]['proid'] = $proid;
				$datalist[$key]['ggid'] = $ggid;
				$datalist[$key]['product'] = $product;
				$datalist[$key]['guige'] = $guige;
				$datalist[$key]['num'] = $num;
				$datalist[$key]['checked'] = true;
			}
            $title = $member['nickname'].'的购物清单';
			return $this->json(['status' => 1,'datalist' => $datalist,'title'=>$title]);
		}
	}
    //生成合同文件，参数是签名图片的路径
    public static function generate_contract($sign_img_url,$ordernum,$set)
    {
        if (getcustom('shopbuy_sign')) {
            $hetong =  explode('upload/',$set['sign_contract_template']);
            //获取后台配置的模板文件

            $name = aid.date('/Ym').'/'.$ordernum.'.docx';
            if (!file_exists(dirname(ROOT_PATH.'upload/'.$name))) {
                mk_dir(dirname(ROOT_PATH.'upload/'.$name));
            }
            $sign_image = explode('upload/',$sign_img_url);
            //读取模板文件
            //$templateProcessor = new TemplateProcessor(ROOT_PATH.'public'.$value);
            //$phpword = new PhpWord();
            $templateProcessor =new \PhpOffice\PhpWord\TemplateProcessor(ROOT_PATH.'upload/'.$hetong[1]);//实例化
            //模板变量替换
            //$templateProcessor->setValue('company_name', $companyname); //模板变量值替换
            $templateProcessor->setValue('sign_date',date('Y-m-d')); //变量值替换
            $templateProcessor->setImageValue('sign_pic', ['path' => ROOT_PATH.'upload/'.$sign_image[1], 'width' => 130, 'height' => 40, 'ratio' => true]); //写入图片
            //输出文件
            //$out_docx_name = $file_path.time().$key.'.docx';
            $url = PRE_URL.'/upload/'.$name;
            $templateProcessor->saveAs(ROOT_PATH.'upload/'.$name);
            //$contract_list[] = $out_docx_name;
            return $url;
        }
    }
}
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

//管理员中心 - 产品管理
namespace app\controller;
use think\facade\Db;
class ApiAdminProduct extends ApiAdmin
{	
	public function index(){
        $where[] = ['aid', '=', aid];
        $where[] = ['bid', '=', bid];
		$st = input('param.st');
		if(!input('?param.st') || input('param.st') === ''){
			$st = 'all';
		}
        if(input('param.keyword')) $where[] = ['name', 'like', '%'.input('param.keyword').'%'];
        if(input('param.cids')){
            $cids = input('post.cids');
            //子分类
            if(bid >0){
                $cate_table = 'shop_category2';
            }else{
                $cate_table = 'shop_category';
            }
            $clist = Db::name($cate_table)->where('aid',aid)->where('pid','in',$cids)->column('id');
            if($clist){
                $clist2 = Db::name($cate_table)->where('aid', aid)->where('pid', 'in', $clist)->column('id');
                $cCate = array_merge($clist, $clist2, $cids);
            }else{
                $cCate = $cids;
            }
            if($cCate){
                $whereCid = [];
                foreach($cCate as $k => $c2){
                    if(bid >0){
                        $whereCid[] = "find_in_set({$c2},cid2)";
                    } else{
                        $whereCid[] = "find_in_set({$c2},cid)";
                    }
                }
                $where[] = Db::raw(implode(' or ',$whereCid));
            }
        } 
   
        $countall = Db::name('shop_product')->where($where)->count();
        $count0 = Db::name('shop_product')->where(array_merge($where,[['status', '=', 0]]))->count();
        $count1 = Db::name('shop_product')->where(array_merge($where,[['status', '=', 1]]))->count();

        if($st == 'all'){

        }elseif($st == '0'){
            $where[] = ['status', '=', 0];
        }elseif($st == '1'){
            $where[] = ['status', '=', 1];
        }
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('shop_product')->where($where)->page($pagenum,$pernum)->order('sort desc,id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $k=>$d){
			$isstock_warning=0;
			if(getcustom('product_stock_warning')){
				$gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$d['id'])->select()->toArray();
				foreach($gglist as $gg){
					if($gg['stock']<$gg['stock_warning']){
						$isstock_warning=1;
						break;
					}
				}
			}
            $datalist[$k]['plate_id'] = $d['plate_id']??0;
			$datalist[$k]['isstock_warning']=$isstock_warning;
		}
		if($this->user['mendian_usercenter']){
		    //门店中心独立产品上下架状态以及库存
            $datalist = \app\custom\MendianUsercenter::getProductList(aid,$this->user['mdid'],$datalist);
            $count_arr = \app\custom\MendianUsercenter::getProductCount(aid,$this->user['mdid'],$where);
            $countall = $count_arr['countall'];
            $count0 = $count_arr['count0'];
            $count1 = $count_arr['count1'];
        }
		if(request()->isAjax()){
			return ['status'=>1,'data'=>$datalist];
		}
		$rdata = [];
		$rdata['countall'] = $countall;
		$rdata['count0'] = $count0;
		$rdata['count1'] = $count1;
		$rdata['datalist'] = $datalist;
		$rdata['st'] = $st;
        $add_product = 1;//允许添加商品
        $status_product = 1;//允许上下架商品
        $stock_product = 1;//允许修改商品库存
        if(getcustom('product_sync_business')){
            //商家信息
            if(bid>0){
                $bunsiness = Db::name('business')->where('aid',aid)->where('id',bid)->find();
                $add_product = $bunsiness['add_product'];//允许添加商品
                $status_product = $bunsiness['status_plate_product'];//允许上下架商品
                $stock_product = $bunsiness['stock_plate_product'];//允许修改商品库存
            }
        }
        $manage_set = [
            'add_product' => $add_product,
            'status_product' => $status_product,
            'stock_product' => $stock_product
        ];
        $rdata['manage_set'] = $manage_set;
        //是否是门店中心
        $rdata['mendian_usercenter'] = $this->user['mendian_usercenter']??0;
		if(getcustom('maidan_fenhong_new')){
			if(bid>0){
				$business = Db::name('business')->where('aid',aid)->where('id',bid)->find();
				if($business['cost_bili_with_edit'] === 0){
					//设置成本比例后，是否允许商户修改商品成本，0关闭后商家不可修改
					$rdata['cost_bili_with_edit'] = 0;
				}
			}
		}
		return $this->json($rdata);
	}
	//商品编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('shop_product')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
			if($info['lvprice'] == 1) return json(['status'=>-4,'msg'=>'该商品已开启会员价,暂不支持手机端修改','url'=>'index']);
			if(getcustom('supply_zhenxin') || getcustom('supply_yongsheng')){
				if($info['sproid']>0 && ($info['source'] == 'supply_zhenxin' || $info['source'] == 'supply_yongsheng')){
					return json(['status'=>0,'msg'=>'供应链商品暂不支持手机端修改，请在电脑端操作']);
				}
			}
		}else{
			$info = ['id'=>'','gettj'=>'-1','showtj'=>'-1','status'=>1];
		}
		//多规格
		$newgglist = array();
		if($info){
			$gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$info['id'])->select()->toArray();
			if($this->user['mendian_usercenter']){
			    //门店中心独立产品库存
                $gglist = \app\custom\MendianUsercenter::getProductSpec(aid,$this->user['mdid'],$info['id'],$gglist);
            }
			foreach($gglist as $k=>$v){
				$v['lvprice_data'] = json_decode($v['lvprice_data']);
				$isstock_warning=0;
				if(getcustom('product_stock_warning')){
					if($v['stock']<$v['stock_warning']){
						$isstock_warning=1;
					}
				}
				$v['isstock_warning'] = $isstock_warning;
				if($v['ks']!==null){
					$newgglist[$v['ks']] = $v;
				}else{
					Db::name('shop_guige')->where('aid',aid)->where('id',$v['id'])->update(['ks'=>$k]);
					$newgglist[$k] = $v;
				}
			}
			if(!$info['guigedata']) $info['guigedata'] = '[{"k":0,"title":"规格","items":[{"k":0,"title":"默认规格"}]}]';
		}else{
			$info = ['id'=>'','freighttype'=>1,'sales'=>0,'sort'=>0,'perlimit'=>0,'status'=>1,'guigedata'=>'[{"k":0,"title":"规格","items":[{"k":0,"title":"默认规格"}]}]'];
		}
		$guigedata = json_decode($info['guigedata'],true);
		//分类
		$clist = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
		foreach($clist as $k=>$v){
			$child = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
			foreach($child as $k2=>$v2){
				$child2 = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
				$child[$k2]['child'] = $child2;
			}
			$clist[$k]['child'] = $child;
		}
		$cateArr = Db::name('shop_category')->Field('id,name')->where('aid',aid)->column('name','id');
		
		if(bid > 0){
			//商家的分类
            $clist2_count =  Db::name('shop_category2')->where('aid',aid)->where('bid',bid)->count();
            if($clist2_count <= 0){
                //没有数据的添加默认数据
                \app\common\Business::addDefaultData(aid, bid);
            }
			$clist2 = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('bid',bid)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
			foreach($clist2 as $k=>$v){
				$child = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('bid',bid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('bid',bid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist2[$k]['child'] = $child;
			}
			$cateArr2 = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('bid',bid)->column('name','id');
		}else{
			$clist2 = [];
			$cateArr2 = [];
		}

		//分组
		$glist = Db::name('shop_group')->where('aid',aid)->order('sort desc,id')->select()->toArray();
		$groupArr = Db::name('shop_group')->Field('id,name')->where('aid',aid)->column('name','id');
		$freightList = Db::name('freight')->where('aid',aid)->where('bid',bid)->where('status',1)->order('sort desc,id')->select()->toArray();
		$freightdata = array();
		if($info && $info['freightdata']){
			$freightdata = Db::name('freight')->where('aid',aid)->where('bid',bid)->where('id','in',$info['freightdata'])->order('sort desc,id')->select()->toArray();
		}
		$info['gettj'] = explode(',',$info['gettj']);
        $info['showtj'] = explode(',',$info['showtj']);
        $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
        $default_cid = $default_cid ? $default_cid : 0;
        $aglevellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->where('can_agent','<>',0)->order('sort,id')->select()->toArray();
		$levellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();

		$pagecontent = json_decode(\app\common\System::initpagecontent($info['detail'],aid),true);
		if(!$pagecontent) $pagecontent = [];

		$product_showset = 1;
		$commission_canset = 1;
		$parambid = bid;
		if(bid != 0){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			$product_showset = $bset['product_showset'];
			$commission_canset = $bset['commission_canset'];
			if(getcustom('business_useplatmendian') && $bset['business_useplatshopparam'] == 1){
				$parambid = 0;
			}
		}
		//商品参数
        $whereParam = [];
        $whereParam[] = ['aid','=',aid];
        $whereParam[] = ['status','=',1];
        if($info['cid']){
            $whereCid = [];
            foreach(explode(',',$info['cid']) as $k => $c2){
                if($c2 == '') continue;
                $whereCid[] = "find_in_set({$c2},cid)";
            }
            if($whereCid){
				if(getcustom('business_showplatparam') && $parambid > 0){
					$whereParam[] = Db::raw("(bid=0 and (".implode(' or ',$whereCid).")) or (bid=".$parambid." and (".implode(' or ',$whereCid). " or cid =''))");
				}else{
					$whereParam[] = ['bid','=',$parambid];
					$whereParam[] = Db::raw(implode(' or ',$whereCid). " or cid =''");
				}
            }else{
				$whereParam[] = ['bid','=',$parambid];
                $whereParam[] = Db::raw("cid =''");
			}
        }else{
			$whereParam[] = ['bid','=',$parambid];
            $whereParam[] = Db::raw(" cid =''");
        }
		$paramList = Db::name('shop_param')->where($whereParam)->order('sort desc,id')->select()->toArray();

		//$paramList = Db::name('shop_param')->where('aid',aid)->where('bid',$parambid)->where('status',1)->order('id,sort desc')->select()->toArray();
		foreach($paramList as $k=>$v){
			$paramList[$k]['params'] = json_decode($v['params'],true);
		}
		$paramdata = $info['paramdata'] && $info['paramdata']!='null' ? json_decode($info['paramdata'],true) : [];

		$business_selfscore = 0;
		if(getcustom('business_selfscore') && bid > 0){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			$business_selfscore = $bset['business_selfscore'];
		}
		
		$rdata = [];
		$rdata['aglevellist'] = $aglevellist;
		$rdata['levellist'] = $levellist;
		$rdata['info'] = $info;
		$rdata['pagecontent'] = $pagecontent;
		$rdata['newgglist'] = $newgglist;
		$rdata['freightList'] = $freightList;
		$rdata['freightdata'] = $freightdata;
		$rdata['clist'] = $clist;
		$rdata['clist2'] = $clist2;
		$rdata['glist'] = $glist;
		$rdata['guigedata'] = $guigedata;
		$rdata['pic'] = $info['pic'] ? [$info['pic']] : [];
		$rdata['pics'] = $info['pics'] ? explode(',',$info['pics']) : [];
		$rdata['cids'] = $info['cid'] ? explode(',',$info['cid']) : [];
		$rdata['cids2'] = $info['cid2'] ? explode(',',$info['cid2']) : [];
		$rdata['gids'] = $info['gid'] ? explode(',',$info['gid']) : [];
		$rdata['cateArr'] = $cateArr;
		$rdata['cateArr2'] = $cateArr2;
		$rdata['groupArr'] = $groupArr;
		$rdata['product_showset'] = $product_showset;
		$rdata['commission_canset'] = $commission_canset;
		$rdata['bid'] = bid;
		$rdata['paramList'] = $paramList;
		$rdata['paramdata'] = $paramdata;
		$rdata['business_selfscore'] = $business_selfscore;
        //是否是门店中心
        $rdata['mendian_usercenter'] = $this->user['mendian_usercenter']??0;
		if(getcustom('maidan_fenhong_new')){
			if($parambid>0){
				$business = Db::name('business')->where('aid',aid)->where('id',$parambid)->find();
				if($business['cost_bili_with_edit'] === 0){
					//设置成本比例后，是否允许商户修改商品成本，0关闭后商家不可修改
					$rdata['cost_bili_with_edit'] = 0;
				}
			}
		}
		return $this->json($rdata);
	}
    public function getParam(){
        $cid = input('post.cid');
        //商品参数
		$parambid = bid;
		if(getcustom('business_useplatmendian') && $parambid > 0){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			if($bset['business_useplatshopparam'] == 1){
				$parambid = 0;
			}
		}
        $whereParam = [];
        $whereParam[] = ['aid','=',aid];
        $whereParam[] = ['status','=',1];
        if($cid){
            $cid = explode(',',$cid);
            $whereCid = [];
            foreach($cid as $k => $c2){
                $whereCid[] = "find_in_set({$c2},cid)";
            }
			if(getcustom('business_showplatparam') && $parambid > 0){
				$whereParam[] = Db::raw("(bid=0 and (".implode(' or ',$whereCid).")) or (bid=".$parambid." and (".implode(' or ',$whereCid). " or cid =''))");
			}else{
				$whereParam[] = ['bid','=',$parambid];
				$whereParam[] = Db::raw(implode(' or ',$whereCid). " or cid =''");
			}
        }else{
			$whereParam[] = ['bid','=',$parambid];
            $whereParam[] = Db::raw(" cid =''");
        }

        $paramList = Db::name('shop_param')->where($whereParam)->order('sort desc,id')->select()->toArray();
        $paramList = $paramList ? $paramList : [];
		foreach($paramList as $k=>$v){
			$paramList[$k]['params'] = json_decode($v['params'],true);
		}

        return json(['status'=>1,'msg'=>'操作成功','paramList'=>$paramList]);
    }
	//保存商品
	public function save(){
		if(input('post.id')) $product = Db::name('shop_product')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
		$info = input('post.info/a');
		$data = array();
		$data['name'] = $info['name'];
		$data['pic'] = $info['pic'];
		$data['pics'] = $info['pics'];
		if(isset($info['procode'])) $data['procode'] = $info['procode'];
        if(isset($info['sellpoint'])) $data['sellpoint'] = $info['sellpoint'];
        if(isset($info['cid'])) $data['cid'] = $info['cid'];
        if(isset($info['freighttype'])) $data['freighttype'] = $info['freighttype'];
        if(isset($info['freightdata'])) $data['freightdata'] = $info['freightdata'];
        if(isset($info['freightcontent'])) $data['freightcontent'] = $info['freightcontent'];
		//$data['commissionset'] = $info['commissionset'];
		//$data['commissiondata1'] = jsonEncode(input('post.commissiondata1/a'));
		//$data['commissiondata2'] = jsonEncode(input('post.commissiondata2/a'));
		//$data['commissiondata3'] = jsonEncode(input('post.commissiondata3/a'));
		
		//$data['video'] = $info['video'];
		//$data['video_duration'] = $info['video_duration'];
        if(isset($info['perlimit'])) $data['perlimit'] = $info['perlimit'];
        if(isset($info['limit_start'])) $data['limit_start'] = $info['limit_start'];
		//$data['scoredkmaxset'] = $info['scoredkmaxset'];
		//$data['scoredkmaxval'] = $info['scoredkmaxval'];
        if(isset($info['gettj'])) $data['gettj'] = implode(',',$info['gettj']);
        if(isset($info['showtj'])) $data['showtj'] = implode(',',$info['showtj']);

		if(bid != 0){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			//if($bset['commission_canset']==0){
			//	$data['commissionset'] = '-1';
			//}
			if($bset['product_showset']==0){
				$data['showtj'] = '-1';
				$data['gettj'] = '-1';
				$data['lvprice'] = 0;
			}
			$data['cid2'] = $info['cid2'];
		}
		
		if($info['oldsales'] != $info['sales']){
			$data['sales'] = $info['sales'];
		}
		$data['sort'] = $info['sort'];
		$data['status'] = $info['status'];
		if($info['status'] == 2){
            if(isset($info['start_time'])) $data['start_time'] = $info['start_time'];
            if(isset($info['end_time'])) $data['end_time'] = $info['end_time'];
		}
		if($info['status'] == 3){
            if(isset($info['start_hours'])) $data['start_hours'] = $info['start_hours'];
            if(isset($info['end_hours'])) $data['end_hours'] = $info['end_hours'];
		}
        if(input('?post.pagecontent')) $data['detail'] = json_encode(input('post.pagecontent'));
        $pagecontent = input('post.pagecontent');
        if(!empty($pagecontent)){
            $data['detail'] = json_encode(input('post.pagecontent'));
        }else{
            $data['detail'] = jsonEncode([[
                'id'=>'M0000000000000',
                'temp'=>'richtext',
                'params'=>['bgcolor'=>'#FFFFFF','margin_x'=>0,'margin_y'=>0,'padding_x'=>0,'padding_y'=>0,'quanxian'=>['all'=>true],'platform'=>['all'=>true]],
                'data'=>'',
                'other'=>'',
                'content'=>''
            ]]);
        }
		if($info['gid']){
			$data['gid'] = implode(',',$info['gid']);
		}else{
			$data['gid'] = '';
		}
		if(!$product){
			$data['createtime'] = time();
			//$data['gettj'] = '-1';
		}
        if(isset($info['lvprice'])) $data['lvprice'] = $info['lvprice'];

        if(isset($info['gettjtip'])) $data['gettjtip'] = $info['gettjtip'];
        if(isset($info['gettjurl'])) $data['gettjurl'] = $info['gettjurl'];
		
		$gglist = input('post.gglist');
		if($info['lvprice']==1){
            $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
            $default_cid = $default_cid ? $default_cid : 0;
            $levellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();
			$defaultlvid = $levellist[0]['id'];
			$sellprice_field = 'sell_price_'.$defaultlvid;
		}else{
			$sellprice_field = 'sell_price';
		}
		$sell_price = 0;$market_price = 0;$cost_price = 0;$weight = 0;$givescore=0;$lvprice_data = [];$i=0;
		foreach($gglist as $ks=>$v){
			if($i==0 || $v[$sellprice_field] < $sell_price){
				$sell_price = $v[$sellprice_field];
				$market_price = $v['market_price'];
				$cost_price = $v['cost_price'];
				$givescore = $v['givescore'];
				$weight = $v['weight'];
				if($info['lvprice']==1){
					$lvprice_data = [];
					foreach($levellist as $lv){
						$lvprice_data[$lv['id']] = $v['sell_price_'.$lv['id']];
					}
				}
			}
			$i++;
		}
		if($info['lvprice']==1){
			$data['lvprice_data'] = json_encode($lvprice_data);
		}
		
		$data['market_price'] = $market_price;
		$data['cost_price'] = $cost_price;
		$data['sell_price'] = $sell_price;

		$business_selfscore = 0;
		if(getcustom('business_selfscore') && bid > 0){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			$business_selfscore = $bset['business_selfscore'];
		}

		if(bid == 0 || $business_selfscore==1){
			$data['givescore'] = $givescore;
		}
		$data['weight'] = $weight;
		$data['stock'] = 0;
		foreach($gglist as $v){
			$data['stock'] += $v['stock'];
		}
		//多规格 规格项
		$data['guigedata'] = json_encode(input('post.guigedata'));

		$data['paramdata'] = jsonEncode(input('post.paramdata/a'));
		
		if(bid !=0 ){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			if($bset['product_check'] == 1){
				$data['ischecked'] = 0;
			}
		}
		if($product){
			Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->update($data);
			$proid = $product['id'];
			\app\common\System::plog('手机端后台商城商品编辑'.$proid);
		}else{
			$data['aid'] = aid;
			$data['bid'] = bid;
			$proid = Db::name('shop_product')->insertGetId($data);
			\app\common\System::plog('手机端后台商城商品添加'.$proid);
		}
		//dump(input('post.option/a'));die;
		//多规格
		$newggids = array();
		foreach($gglist as $ks=>$v){
			$ggdata = array();
			$ggdata['proid'] = $proid;
			$ggdata['ks'] = $v['ks'];
			$ggdata['name'] = $v['name'];
			$ggdata['pic'] = $v['pic'] ? $v['pic'] : '';
			$ggdata['market_price'] = $v['market_price']>0 ? $v['market_price']:0;
			$ggdata['cost_price'] = $v['cost_price']>0 ? $v['cost_price']:0;
			$ggdata['sell_price'] = $v['sell_price']>0 ? $v['sell_price']:0;
			$ggdata['weight'] = $v['weight']>0 ? $v['weight']:0;
			$ggdata['procode'] = $v['procode'];
			if(bid == 0 || $business_selfscore==1){
				$ggdata['givescore'] = $v['givescore'];
			}
			$ggdata['stock'] = $v['stock']>0 ? $v['stock']:0;
			$lvprice_data = [];
			if($info['lvprice']==1){
				$ggdata['sell_price'] = $v['sell_price_'.$levellist[0]['id']]>0 ? $v['sell_price_'.$levellist[0]['id']]:0;
				foreach($levellist as $lv){
					$sell_price = $v['sell_price_'.$lv['id']]>0 ? $v['sell_price_'.$lv['id']]:0;
					$lvprice_data[$lv['id']] = $sell_price;
				}
				$ggdata['lvprice_data'] = json_encode($lvprice_data);
			}

			$guige = Db::name('shop_guige')->where('aid',aid)->where('proid',$proid)->where('ks',$v['ks'])->find();
			if($guige){
				Db::name('shop_guige')->where('aid',aid)->where('id',$guige['id'])->update($ggdata);
				$ggid = $guige['id'];
			}else{
				$ggdata['aid'] = aid;
				$ggid = Db::name('shop_guige')->insertGetId($ggdata);
			}
			$newggids[] = $ggid;
		}
		Db::name('shop_guige')->where('aid',aid)->where('proid',$proid)->where('id','not in',$newggids)->delete();

		\app\common\Wxvideo::updateproduct($proid);
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//上下架
	public function setst(){
		$st = input('post.st/d');
		$id = input('post.id/d');
		if($this->user['mendian_usercenter']){
		    //门店中心独立的产品上下架
            \app\custom\MendianUsercenter::editProductStatus(aid,$this->user['mdid'],$id,$st);
            return $this->json(['status'=>1,'msg'=>'操作成功']);
        }
		Db::name('shop_product')->where(['aid'=>aid,'bid'=>bid,'id'=>$id])->update(['status'=>$st]);
		
		if($st == 0){
			\app\common\Wxvideo::delisting($id);
		}else{
			\app\common\Wxvideo::listing($id);
		}
        \app\common\System::plog('手机端后台商城商品改状态'.$id);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//商品删除
	public function del(){
		$id = input('post.id/d');
		$rs = Db::name('shop_product')->where(['aid'=>aid,'bid'=>bid,'id'=>$id])->delete();
		if($rs){
			Db::name('shop_guige')->where(['aid'=>aid,'proid'=>$id])->delete();
			\app\common\Wxvideo::deleteproduct($id);
            \app\common\System::plog('手机端后台商城商品删除'.$id);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}

    //保存商品库存
    public function savestock(){
	    if(getcustom('product_sync_business')){
            if(input('post.id')) $product = Db::name('shop_product')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
            $gglist = input('post.gglist');

            if($product['lvprice']==1){
                $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
                $default_cid = $default_cid ? $default_cid : 0;
                $levellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();
                $defaultlvid = $levellist[0]['id'];
                $sellprice_field = 'sell_price_'.$defaultlvid;
            }else{
                $sellprice_field = 'sell_price';
            }
            $sell_price = 0;$market_price = 0;$cost_price = 0;$lvprice_data = [];
            foreach($gglist as $ks=>$v){
                if($sell_price==0 || $v[$sellprice_field] < $sell_price){
                    $sell_price = $v[$sellprice_field];
                    $market_price = $v['market_price'];
                    $cost_price = $v['cost_price'];
                    if($product['lvprice']==1){
                        $lvprice_data = [];
                        foreach($levellist as $lv){
                            $lvprice_data[$lv['id']] = $v['sell_price_'.$lv['id']];
                        }
                    }
                }
            }
            $data = [];
            if($product['lvprice']==1){
                $data['lvprice_data'] = json_encode($lvprice_data);
            }
            $data['market_price'] = $market_price;
            $data['cost_price'] = $cost_price;
            $data['sell_price'] = $sell_price;
            $data['stock'] = 0;
            foreach($gglist as $v){
                $data['stock'] += $v['stock'];
            }


            if($product){
                Db::name('shop_product')->where('aid',aid)->where('id',$product['id'])->update($data);
                $proid = $product['id'];
                \app\common\System::plog('手机端后台商城商品编辑库存'.$proid);
            }else{
                $data['aid'] = aid;
                $data['bid'] = bid;
                $proid = Db::name('shop_product')->insertGetId($data);
                \app\common\System::plog('手机端后台商城商品编辑库存添加'.$proid);
            }

            foreach($gglist as $ks=>$v){
                $ggdata = [];
                $ggdata['market_price'] = $v['market_price']>0 ? $v['market_price']:0;
                $ggdata['cost_price'] = $v['cost_price']>0 ? $v['cost_price']:0;
                $ggdata['sell_price'] = $v['sell_price']>0 ? $v['sell_price']:0;
                $ggdata['stock'] = $v['stock']>0 ? $v['stock']:0;
                $lvprice_data = [];
                if($product['lvprice']==1){
                    $ggdata['sell_price'] = $v['sell_price_'.$levellist[0]['id']]>0 ? $v['sell_price_'.$levellist[0]['id']]:0;
                    foreach($levellist as $lv){
                        $sell_price = $v['sell_price_'.$lv['id']]>0 ? $v['sell_price_'.$lv['id']]:0;
                        $lvprice_data[$lv['id']] = $sell_price;
                    }
                    $ggdata['lvprice_data'] = json_encode($lvprice_data);
                }
                Db::name('shop_guige')->where('id',$v['id'])->update($ggdata);
            }

            \app\common\Wxvideo::updateproduct($proid);
            return json(['status'=>1,'msg'=>'操作成功']);
        }
	    if(getcustom('mendian_usercenter')){
            $gglist = input('post.gglist');
            //门店中心独立的产品上下架
            \app\custom\MendianUsercenter::editProductStock(aid,$this->user['mdid'],input('post.id'),$gglist);
            return $this->json(['status'=>1,'msg'=>'操作成功']);
        }

    }

    /**
     * 滤芯售后录入
     * 开发文档 功能2：https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0s91tNOVAeYAOQYKALU&tab=lom7cg
     * @author: liud
     * @time: 2025/1/8 上午10:14
     */
    public function savelvxin(){
        if(getcustom('product_lvxin_replace_remind')){
            $post = input('post.formdata');

            $shopsyss = Db::name('shop_sysset')->where('aid',aid)->find();
            if($shopsyss['product_lvxin_replace_remind'] != 1){
                return $this->json(['status'=>0,'msg'=>'功能已关闭']);
            }

            if(!$post['mid']){
                return json(['status'=>0,'msg'=>'请输入用户ID']);
            }
            if(!Db::name('member')->where('aid',aid)->where('id',$post['mid'])->find()){
                return json(['status'=>0,'msg'=>'用户ID不存在']);
            }
            if(!$post['day']){
                return json(['status'=>0,'msg'=>'请输入剩余天数']);
            }
            if($post['day'] < 0){
                return json(['status'=>0,'msg'=>'剩余天数不能小于0']);
            }
            if(!$post['proid']){
                return json(['status'=>0,'msg'=>'请选择适用滤芯']);
            }

            $info['day'] = $post['day'] ? intval($post['day']) : 0;
            $info['proid'] = $post['proid'] ?? 0;
            $info['mid'] = $post['mid'] ?? 0;
            $info['expiretime'] = time() + 86400 * $info['day'];
            $info['createtime'] = $info['updatetime'] = time();

            $info['aid'] = aid;
            $id = Db::name('product_lvxin_replace')->insertGetId($info);

            if(!$id){
                return json(['status'=>0,'msg'=>'录入失败']);
            }
            \app\common\System::plog('手机端后台滤芯售后录入'.$post['proid']);
            return json(['status'=>1,'msg'=>'录入成功']);
        }
    }

    //复制商品
    public function procopy(){
        $where = [];
        if(bid > 0){
            $where[] = ['bid','=',bid];
        }
        $product = Db::name('shop_product')->where('aid',aid)->where($where)->where('id',input('post.id/d'))->find();
        if(!$product) return $this->json(['status'=>0,'msg'=>'商品不存在,请重新选择']);
        $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
        $data = $product;
        $data['name'] = '复制-'.$data['name'];
        if($data['detail']){
            //处理tab组件
            $detail = json_decode($data['detail'],true);
            if($detail){
                foreach ($detail as $k => $item){
                    if($item['temp'] == 'tab'){
                        $detail[$k]['id'] = $item['id'].rand(0,9999);
                        $tablist = Db::name('designerpage_tab')->where('aid',aid)->where('tabid',$item['id'])->select()->toArray();
                        if($tablist){
                            foreach ($tablist as $k2 => $item2){
                                unset($item2['id']);
                                $item2['tabid'] = $detail[$k]['id'];
                                Db::name('designerpage_tab')->insert($item2);
                            }
                        }
                    }
                }
                $data['detail'] = json_encode($detail);
            }
        }
        unset($data['id']);
        unset($data['wxvideo_product_id']);
        unset($data['wxvideo_edit_status']);
        unset($data['wxvideo_status']);
        unset($data['wxvideo_reject_reason']);
        $data['status'] = 0;
        if(getcustom('image_search')){
            $data['is_copy'] = 1;
        }

        $newproid = Db::name('shop_product')->insertGetId($data);
        foreach($gglist as $gg){
            $ggdata = $gg;
            $ggdata['proid'] = $newproid;
            unset($ggdata['id']);
            unset($ggdata['linkid']);
            Db::name('shop_guige')->insert($ggdata);
        }
        if(getcustom('shop_product_jialiao')){
            $jialiaolist = Db::name('shop_product_jialiao')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
            foreach ($jialiaolist as $jk=>$jv){
                $jldata = $jv;
                $jldata['proid'] = $newproid;
                unset($jldata['id']);
                Db::name('shop_product_jialiao')->insert($jldata);
            }
        }
        $this->tongbuproduct($newproid);
        \app\common\System::plog('移动端后台商城商品复制'.$newproid);
        return $this->json(['status'=>1,'msg'=>'复制成功','proid'=>$newproid]);
    }

    //同步商品到商户
    private function tongbuproduct($proids){
        if(getcustom('plug_businessqr')){
            if(!is_array($proids)){
                $proids = explode(',',$proids);
            }
            $blist = [];
            foreach($proids as $proid){
                $product = Db::name('shop_product')->where('aid',aid)->where('id',$proid)->find();
                if($product && $product['bid'] == -1){
                    $gglist = Db::name('shop_guige')->where('aid',aid)->where('proid',$product['id'])->select()->toArray();
                    if(!$blist){
                        $blist = Db::name('business')->where('aid',aid)->order('sort desc,id desc')->select()->toArray();
                    }
                    foreach($blist as $business){
                        $bpro = Db::name('shop_product')->where('aid',aid)->where('bid',$business['id'])->where('linkid',$product['id'])->find();
                        $data = $product;
                        $data['bid'] = $business['id'];
                        $data['linkid'] = $product['id'];
                        unset($data['id']);
                        unset($data['wxvideo_product_id']);
                        unset($data['wxvideo_edit_status']);
                        unset($data['wxvideo_status']);
                        unset($data['wxvideo_reject_reason']);
                        if(isset($data['bind_mendian_ids'])){
                            unset($data['bind_mendian_ids']);
                        }
                        if($bpro){
                            Db::name('shop_product')->where('id',$bpro['id'])->update($data);
                            $newproid = $bpro['id'];
                        }else{
                            $newproid = Db::name('shop_product')->insertGetId($data);
                        }

                        $newggids = [];
                        foreach($gglist as $gg){
                            $ggdata = $gg;
                            $ggdata['proid'] = $newproid;
                            unset($ggdata['id']);

                            $guige = Db::name('shop_guige')->where('aid',aid)->where('proid',$newproid)->where('ks',$ggdata['ks'])->find();
                            if($guige){
                                Db::name('shop_guige')->where('aid',aid)->where('id',$guige['id'])->update($ggdata);
                                $ggid = $guige['id'];
                            }else{
                                $ggid = Db::name('shop_guige')->insertGetId($ggdata);
                            }
                            $newggids[] = $ggid;
                        }
                        Db::name('shop_guige')->where('aid',aid)->where('proid',$newproid)->where('id','not in',$newggids)->delete();
                    }
                }
            }
        }
    }
}
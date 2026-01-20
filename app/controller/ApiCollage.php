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
class ApiCollage extends ApiCommon
{
	public function index(){
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['status','=',1];
		$where[] = ['ischecked','=',1];
		$business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
		if(input('param.bid')){
			$where[] = ['bid','=',input('param.bid')];
		}elseif(!$business_sysset || $business_sysset['product_isshow']==0){
			$where[] = ['bid','=',0];
		}

		//分类 
		if(input('param.cid')){
			$where[] = ['cid','=',input('param.cid/d')];
		}
		if(input('param.keyword')){
			$where[] = ['name','like','%'.input('param.keyword').'%'];
		}
        if(getcustom('yx_collage_jieti')){
            $time = time();
            $where[]= Db::raw("(  (collage_type = 0)or (collage_type = 1 and starttime < {$time} and endtime > {$time} )   )");
        }
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$field = "pic,id,name,sales,market_price,sell_price,sellpoint,fuwupoint,teamnum,buymax,teamhour,leadermoney,leaderscore";
		if(getcustom('plug_tengrui')) {
			$field .= ',house_status,is_rzh,relation_type,group_status,group_ids';
		}
		if(getcustom('yx_collage_jieti')){
            $field .=',collage_type';
        }
		$datalist = Db::name('collage_product')->field($field)->where($where)->page($pagenum,$pernum)->order('sort desc,id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		if(getcustom('plug_tengrui')) {
            if($datalist){
                $tr_check = new \app\common\TengRuiCheck();
                foreach($datalist as $dk=>$dv){
                    //判断是否是否符合会员认证、会员关系、一户，不符合则直接去掉
                    $check_collage = $tr_check->check_collage($this->member,$dv,1);
                    if($check_collage && $check_collage['status'] == 0){
                        unset($datalist[$dk]);
                    }
                }
                unset($dv);
                $len = count($datalist);
                if($len<20 && $len>0){
                    //重置索引,防止上方去掉的数据产生空缺
                    $datalist=array_values($datalist);
                }
            }
        }
		if($pagenum == 1){
			$pics = Db::name('collage_sysset')->where('aid',aid)->value('pics');
			if(!$pics) $pics = [];
			$pics = explode(',',$pics);
			$clist = Db::name('collage_category')->where('aid',aid)->where('pid',0)->where('status',1)->limit(8)->order('sort desc,id')->select()->toArray(); 
		}
        if(getcustom('yx_collage_jieti')){
            foreach($datalist as $pk=>$product){
                //查询是否存在订单
                $teamid =  Db::name('collage_order')->where('aid',$product['aid'])->where('bid',$product['bid'])->where('proid',$product['id'])->where('mid',mid)->where('status','in',[1,2,3])->value('teamid');
                $datalist[$pk]['teamid']  = $teamid?$teamid:0;
            }
        }
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['pics'] = $pics;
		$rdata['clist'] = $clist;
		return $this->json($rdata);
	}
	public function product(){

		$proid = input('param.id/d');
		$where = [];
		$where[] = ['id','=',$proid];
		$where[] = ['aid','=',aid];
		$product = Db::name('collage_product')->where($where)->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'商品不存在']);
		if($product['status']==0) return $this->json(['status'=>0,'msg'=>'商品已下架']);
		if($product['ischecked']!=1) return $this->json(['status'=>0,'msg'=>'商品未审核']);

		if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户
            $tr_check = new \app\common\TengRuiCheck();
            $check_collage = $tr_check->check_collage($this->member,$product,1);
            if($check_collage && $check_collage['status'] == 0 ){
                return $this->json(['status'=>$check_collage['status'],'msg'=>$check_collage['msg']]);
            }
            $tr_roomId = $check_collage['tr_roomId'];
        }

		if(!$product['pics']) $product['pics'] = $product['pic'];
		$product['pics'] = explode(',',$product['pics']);
		if($product['fuwupoint']){
			$product['fuwupoint'] = explode(' ',preg_replace("/\s+/",' ',str_replace('　',' ',trim($product['fuwupoint']))));
		}
		$gglist = Db::name('collage_guige')->where('proid',$product['id'])->select()->toArray();
		$guigelist = array();
		foreach($gglist as $k=>$v){
			$guigelist[$v['ks']] = $v;
		}
		$guigedata = json_decode($product['guigedata'],true);
		$ggselected = [];
		foreach($guigedata as $v) {
			$ggselected[] = 0;
		}
		$ks = implode(',',$ggselected);

		//获取评论
		$commentlist = Db::name('collage_comment')->where('aid',aid)->where('proid',$proid)->where('status',1)->limit(10)->select()->toArray();
		if(!$commentlist) $commentlist = [];
		foreach($commentlist as $k=>$pl){
			$commentlist[$k]['createtime'] = date('Y-m-d H:i',$pl['createtime']);
			if($commentlist[$k]['content_pic']) $commentlist[$k]['content_pic'] = explode(',',$commentlist[$k]['content_pic']);
		}
		$commentcount = Db::name('collage_comment')->where('aid',aid)->where('proid',$proid)->where('status',1)->count();

		//正在拼团的
		$teamCount = Db::name('collage_order_team')->where('aid',aid)->where('proid',$product['id'])->where('status',1)->count();
		$teamList = [];
		$where = [];
		$where[] = ['collage_order_team.proid','=',$product['id']];
		$where[] = ['collage_order_team.status','=',1];
		$where[] = ['collage_order_team.aid','=',aid];
		if(getcustom('yx_collage_team_in_team')){
			$where[] = ['collage_order_team.mid','<>',mid];
        }
        $teamList = Db::name('collage_order_team')->alias('collage_order_team')->field('collage_order_team.*,member.nickname,member.headimg')->join('member','member.id=collage_order_team.mid')->where($where)->order('collage_order_team.num desc,collage_order_team.id')->limit(10)->select()->toArray();
        if(getcustom('yx_collage_team_in_team')){
            //查询自己是参与或发起拼单，并排第一个显示
            $selfList = Db::name('collage_order_team')->alias('team')->fieldRaw('team.*,(team.teamnum - team.num) as neednum,member.nickname,member.headimg,"1" as isself ')->join('member','member.id=team.mid')->where(['team.mid'=>mid,'team.status'=>1,'team.proid'=>$product['id'],'team.aid'=>aid])->order('team.num desc,team.id')->order('neednum desc')->limit(1)->select()->toArray();
            //查询自己参与数量
            $selfcount = 0+Db::name('collage_order_team')->where(['mid'=>mid,'status'=>1,'proid'=>$product['id'],'aid'=>aid])->count('id');
            $teamCount -= $selfcount;
        }
        
		if(getcustom('yx_collage_jieti')){
		     if($product['collage_type'] ==1){
                 $product['jieti_data'] = json_decode($product['jieti_data'],true);
                 foreach($teamList as $tk=>$tval){
                     $memberlist = Db::name('collage_order')->alias('co')
                         ->join('member m','m.id = co.mid')
                         ->where('co.aid',$tval['aid'])
                         ->where('co.bid',$tval['bid'])
                         ->where('co.teamid',$tval['id'])
                         ->where('co.isjiqiren',0)
                         ->field('co.id,m.headimg')
                         ->limit(0,8)
                         ->select()->toArray();
                     if(getcustom('yx_collage_jiqiren')){
                     	$memberlist2 = Db::name('collage_order')->alias('co')
	                         ->join('collage_jiqilist m','m.id = co.jiqirenid')
	                         ->where('co.aid',$tval['aid'])
	                         ->where('co.bid',$tval['bid'])
	                         ->where('co.teamid',$tval['id'])
	                         ->where('co.isjiqiren',1)
	                         ->field('co.id,m.headimg')
	                         ->limit(0,8)
	                         ->select()->toArray();
	                    if($memberlist2){
	                    	if($memberlist){
	                    		$memberlist = array_merge($memberlist,$memberlist2);
	                    	}else{
	                    		$memberlist = $memberlist2;
	                    	}
	                    }
                     }
                     $teamList[$tk]['memberlist'] = $memberlist;
                 }
		         //查找团
                 $teamid = Db::name('collage_order')
                     ->where('aid',$product['aid'])
                     ->where('bid',$product['bid'])
                     ->where('status','in',[1,2,3])
                     ->where('proid',$product['id'])
                     ->where('mid',$this->mid)
                     ->order('createtime desc')
                     ->value('teamid');
                 $product['teamid'] = $teamid;
                 $product['is_start'] = 1;
                 $product['is_end'] = 0;
                 if(time() >$product['endtime'] ){
                     $product['is_end'] = 1;
                 }
                 if(time() <  $product['starttime']){
                     $product['is_start'] = 0;
                 }
                 $product['view_num'] =  $product['view_num'] + $product['xn_view_num'];
             }
        }
		$rs = Db::name('member_favorite')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('type','collage')->find();
		if($rs){
			$isfavorite = true;
		}else{
			$isfavorite = false;
		}
		if($this->member){
			//添加浏览历史
			$rs = Db::name('member_history')->where(array('aid'=>aid,'mid'=>mid,'proid'=>$proid,'type'=>'collage'))->find();
			if($rs){
				Db::name('member_history')->where(array('id'=>$rs['id']))->update(['createtime'=>time()]);
			}else{
				Db::name('member_history')->insert(array('aid'=>aid,'mid'=>mid,'proid'=>$proid,'type'=>'collage','createtime'=>time()));
			}
		}

		$shopset = Db::name('collage_sysset')->field('comment,showjd')->where('aid',aid)->find();
		$sysset = Db::name('admin_set')->field('name,logo,desc,tel,kfurl')->where('aid',aid)->find();

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

		if($product['bid']!=0){
			$business = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->field('id,aid,cid,name,logo,desc,tel,address,sales,kfurl,is_open')->find();
			if(!$business){
				return $this->json(['status'=>0,'msg'=>'商家不存在']);
			}
            if($business['is_open'] != 1){
                return $this->json(['status' => 0, 'msg' => '店铺未营业']);
            }
		}else{
			$business = $sysset;
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
			$tjdatalist = Db::name('collage_product')->where($tjwhere)->limit(8)->order(Db::raw('rand()'))->select()->toArray();
			if(!$tjdatalist) $tjdatalist = array();
			$tjdatalist = $this->formatprolist($tjdatalist);
		}elseif($product['show_recommend'] == 2){
			$tjdatalist = Db::name('collage_product')->where('aid',aid)->where('id','in',$product['recommend_productids'])->order(Db::raw('field(id,'.$product['recommend_productids'].')'))->select()->toArray();
		}
        if(getcustom('yx_collage_jieti')){
            $oglist = Db::name('collage_order')->field('mid,title name,createtime,proid,buytype,isjiqiren')->where('aid',aid)->where('status','in','0,1,2,3')->where('createtime','>',time()-86400*10)->where('proid',$product['id'])->order('createtime desc')->limit(6)->select()->toArray();
            $bobaolist = [];
            foreach($oglist as $k=>$og){
            	if(!$og['isjiqiren']){
            		$ogmember = Db::name('member')->where('id',$og['mid'])->find();
            	}else{
            		$ogmember = Db::name('collage_jiqilist')->field('id,nickname,headimg')->where('aid',aid)->where('id',$og['jiqirenid'])->find();
            	}
                if(!$ogmember){
                    unset($og);
                    continue;
                }else{
                    $og['nickname'] = $ogmember['nickname'];
                    $og['headimg'] = $ogmember['headimg'];
                }
                if(time() - $og['createtime'] < 60*5){
                    $og['showtime'] = '刚刚';
                }elseif(date('Ymd')==date('Ymd',$og['createtime'])){
                    if($og['createtime'] + 3600 > time()){
                        $og['showtime'] = floor((time()-$og['createtime'])/60).'分钟前';
                    }else{
                        $og['showtime'] = floor((time()-$og['createtime'])/3600).'小时前';
                    }
                }elseif(time()-$og['createtime']<86400){
                    $og['showtime'] = '昨天';
                }elseif(time()-$og['createtime']<2*86400){
                    $og['showtime'] = '前天';
                }else{
                    $og['showtime'] = '三天前';
                }
                $bobaolist[] = $og;
            }
            $product['bobaolist'] = $bobaolist??[];
            Db::name('collage_product')->where('aid',$product['aid'])->where('id',$product['id'])->inc('view_num',1)->update();
            //加入浏览记录
            $view_history = Db::name('collage_view_history')->where('aid',aid)->where('bid',$product['bid'])->where('proid',$proid)->where('mid',mid)->find();
            if(!$view_history){
                Db::name('collage_view_history')->insert([
                    'aid' =>aid,
                    'bid' => $product['bid'],
                    'proid' => $proid,
                    'mid' => mid,
                    'createtime' => time()
                ]);
            }
        }
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['product'] = $product;
		$rdata['business'] = $business;
		$rdata['guigelist'] = $guigelist;
		$rdata['guigedata'] = $guigedata;
		$rdata['ggselected'] = $ggselected;
		$rdata['ks'] = $ks;
		$rdata['commentlist'] = $commentlist;
		$rdata['commentcount'] = $commentcount;
		$rdata['shopset'] = $shopset;
		$rdata['sysset'] = $sysset;
		$rdata['teamCount'] = $teamCount;
		$rdata['teamList'] = $teamList;
		$rdata['nowtime'] = time();
		$rdata['status'] = 1;
		$rdata['isfavorite'] = $isfavorite;
		$rdata['tjdatalist'] = $tjdatalist;
		$rdata['showtoptabbar'] = 0;
		if(getcustom('ngmm')){
			$rdata['showtoptabbar'] = 1;
			$rdata['shopset'] = Db::name('shop_sysset')->where('aid',aid)->field('showjd,comment,detail_guangao1,detail_guangao1_t,detail_guangao2,detail_guangao2_t')->find();
		}

        if(getcustom('yx_collage_team_in_team')){
            $rdata['selfList'] = $selfList?$selfList:'';
            $has_teaminteam = \app\custom\CollageTeamInTeamCustom::has_teaminteam(aid);//是否有权设置团中团参数
            //如果有团中团权限，且商品开启了团中团
            if($has_teaminteam && $product['teaminteam_status'] == 1){
                $teamset = Db::name('collage_sysset')->field('team_in_team,teaminteam_word,teaminteam_color,teaminteam_bgcolor')->where('aid',aid)->find();
                if($teamset){
                    $rdata['shopset']['team_in_team']       = $teamset['team_in_team'];
                    $rdata['shopset']['teaminteam_word']    = $teamset['teaminteam_word'];
                    $rdata['shopset']['teaminteam_color']   = $teamset['teaminteam_color'];
                    $rdata['shopset']['teaminteam_bgcolor'] = $teamset['teaminteam_bgcolor'];

                    if($teamset['teaminteam_word']){
                        $sell_price = Db::name('collage_guige')->where('proid',$product['id'])->order('sell_price desc')->value('sell_price');
                        if($sell_price && $sell_price>$product['sell_price']){
                            $max_sell_price = $sell_price;
                        }else{
                            $max_sell_price = $product['sell_price'];
                        }
                        //查询[佣金]位置
                        $pos = mb_strpos($teamset['teaminteam_word'], '[佣金]');
                        if($pos>=0){
                        	//参与拼团数量
                            $joinnum = $product['teamnum'] - 1;
                            //计算三级订单分裂数量
                            $onenum   = $product['teaminteam_splitnum'] * $joinnum;
                            $twonum   = ($product['teaminteam_splitnum']* $joinnum ) * $product['teaminteam_splitnum'] * $joinnum;
                            $threenum = (($product['teaminteam_splitnum']* $joinnum ) * $product['teaminteam_splitnum'] * $joinnum) * $product['teaminteam_splitnum'] * $joinnum;
                            if($product['teaminteam_commissiontype']==1){ //固定金额按单
                            	//佣金 = 级数佣金 * 分裂数量 * 参与成员人数
                                $commission1 = $product['teaminteam_commission1'] * $onenum;
                                $commission2 = $product['teaminteam_commission2'] * $twonum;
                                $commission3 = $product['teaminteam_commission3'] * $threenum;
                            }else{
                                $commission1 = $product['teaminteam_commission1'] * $max_sell_price * 0.01 * $onenum;
                                $commission2 = $product['teaminteam_commission2'] * $max_sell_price * 0.01 * $twonum;
                                $commission3 = $product['teaminteam_commission3'] * $max_sell_price * 0.01 * $threenum;
                            }
                            $commission1 = round($commission1,2);
                            $commission2 = round($commission2,2);
                            $commission3 = round($commission3,2);
                            //计算佣金
                            $teaminteam_commission = round(($commission1 + $commission2 + $commission3),2);
                            $rdata['shopset']['teaminteam_word'] = str_replace("[佣金]", $teaminteam_commission, $teamset['teaminteam_word']);
                        }
                    }
                }
            }else{
            	$rdata['shopset']['team_in_team'] = 0;
            }
        }

        $rdata['product']['mangfan_status']     = 0;
        $rdata['product']['mangfan_text']       = '';
        $rdata['product']['mangfan_text_color'] = '#df8e14';
        if(getcustom('yx_mangfan_collage')){
            $mangfan_data = \app\custom\Mangfan::mangfanInfo(aid, $product['id'],'collage');
            $rdata['product']['mangfan_status']     = $mangfan_data['status'];
            $rdata['product']['mangfan_text']       = t('可享消费盲返');
        }

		return $this->json($rdata);
	}
	//商品评价
	public function commentlist(){
		$proid = input('param.proid/d');
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['proid','=',$proid];
		$where[] = ['status','=',1];
		$datalist = Db::name('collage_comment')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$pl){
			$datalist[$k]['createtime'] = date('Y-m-d H:i',$pl['createtime']);
			if($datalist[$k]['content_pic']) $datalist[$k]['content_pic'] = explode(',',$datalist[$k]['content_pic']);
		}
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}
		$rdata = [];
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	public function category(){
		$datalist = Db::name('collage_category')->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();
		$rdata = [];
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	public function prolist(){
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['status','=',1];
		$where[] = ['ischecked','=',1];
		$business_sysset = Db::name('business_sysset')->where('aid',aid)->find();
		if(input('param.bid')){
			$where[] = ['bid','=',input('param.bid')];
		}elseif(!$business_sysset || $business_sysset['product_isshow']==0){
			$where[] = ['bid','=',0];
		}
		//分类 
		$searchcid = input('param.cid');
		if(input('param.cid')){
			$cid = input('param.cid/d');
			//子分类
			$clist = Db::name('collage_category')->where('aid',aid)->where('pid',$cid)->select()->toArray();
			if($clist){
				$cateArr = [$cid];
				foreach($clist as $c){
					$cateArr[] = $c['id'];
				}
				$where[] = ['cid','in',$cateArr];
			}else{
				$where[] = ['cid','=',$cid];
				$pid = Db::name('collage_category')->where('aid',aid)->where('id',$cid)->value('pid');
				if($pid){
					$searchcid = $pid;
					$clist = Db::name('collage_category')->where('aid',aid)->where('pid',$pid)->select()->toArray();
				}
			}
		}
		if(input('param.keyword')){
			$where[] = ['name','like','%'.input('param.keyword').'%'];
		}
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$field = "pic,id,name,sales,market_price,sell_price,sellpoint,fuwupoint,teamnum,buymax,teamhour,leadermoney,leaderscore";
		if(getcustom('plug_tengrui')) {
			$field .= ',house_status,is_rzh,relation_type,group_status,group_ids';
		}
		$datalist = Db::name('collage_product')->field($field)->where($where)->page($pagenum,$pernum)->order('sort desc,id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		if(getcustom('plug_tengrui')) {
            if($datalist){
                $tr_check = new \app\common\TengRuiCheck();
                foreach($datalist as $dk=>$dv){
                    //判断是否是否符合会员认证、会员关系、一户，不符合则直接去掉
                    $check_collage = $tr_check->check_collage($this->member,$dv,1);
                    if($check_collage && $check_collage['status'] == 0 ){
                        unset($datalist[$dk]);
                    }
                }
                unset($dv);
                $len = count($datalist);
                if($len<20 && $len>0){
                    //重置索引,防止上方去掉的数据产生空缺
                    $datalist=array_values($datalist);
                }
            }
        }
		if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist]);
		}

		$rdata = [];
		$rdata['clist'] = $clist;
		$rdata['searchcid'] = $searchcid;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}
	public function buy(){
		$this->checklogin();
		$proid = input('param.proid/d');
		$ggid = input('param.ggid/d');
		$totalnum = input('param.num/d');
		if(!$totalnum) $totalnum = 1;
		$buytype = input('param.buytype/d');

		$product = Db::name('collage_product')->where('aid',aid)->where('status',1)->where('ischecked',1)->where('id',$proid)->find();
		if(!$product){
			return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
		}
		$guige = Db::name('collage_guige')->where('id',$ggid)->find();
		if(!$guige){
			return $this->json(['status'=>0,'msg'=>'产品该规格不存在或已下架']);
		}
        if($guige['stock'] < $totalnum){
            return $this->json(['status'=>0,'msg'=>$product['name'] . $guige['name'].'库存不足']);
        }
        if(getcustom('yx_collage_jieti')){
            if($product['collage_type'] ==1){
                //查找团
                $teamid = Db::name('collage_order')
                    ->where('aid',$product['aid'])
                    ->where('bid',$product['bid'])
                    ->where('status',1)
                    ->where('proid',$product['id'])
                    ->where('mid',$this->mid)
                    ->order('createtime desc')
                    ->value('teamid');
                if($teamid){
                    return $this->json(['status'=>0,'msg'=>'您已参团']);
                }
            }
        }
        
        //是否达到限制兑换数
        if($product['buymax'] > 0){
            $buynum = $totalnum + Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('proid',$proid)->where('status','in','0,1,2,3')->sum('num');
            if($buynum > $product['buymax']){
                return $this->json(['status'=>0,'msg'=>'每人限购'.$product['buymax'].'件']);
            }
        }

        if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户
            $tr_check = new \app\common\TengRuiCheck();
            $check_collage = $tr_check->check_collage($this->member,$product);
            if($check_collage && $check_collage['status'] == 0){
                return $this->json(['status'=>$check_collage['status'],'msg'=>$check_collage['msg']]);
            }
            $tr_roomId = $check_collage['tr_roomId'];
        }

		$bid = $product['bid'];
		if($bid!=0){
			$business = Db::name('business')->where('id',$bid)->field('id,aid,cid,name,logo,tel,address,sales,longitude,latitude')->find();
		}else{
			$business = Db::name('admin_set')->where('aid',aid)->field('id,name,logo,desc,tel')->find();
		}

		if($buytype == 1){//单独购买
			$guige['sell_price'] = $guige['market_price'];
		}
		$product_price = $guige['sell_price'] * $totalnum;
		$totalweight = $guige['weight'] * $totalnum;
		if($product['freighttype']==0){
			$fids = explode(',',$product['freightdata']);
			$freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid],['id','in',$fids]]);
		}elseif($product['freighttype']==3 || $product['freighttype']==4){
			$freightList = [['id'=>0,'name'=>($product['freighttype']==3?'自动发货':'在线卡密'),'pstype'=>$product['freighttype']]];
		}else{
			$freightList = \app\model\Freight::getList([['status','=',1],['aid','=',aid],['bid','=',$bid]]);
		}
		
		$havetongcheng = 0;
		foreach($freightList as $k=>$v){
			if($v['pstype']==2){ //同城配送
				$havetongcheng = 1;
			}
		}
		if($havetongcheng){
			$address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->where('latitude','>',0)->order('isdefault desc,id desc')->find();
		}else{
			$address = Db::name('member_address')->where('aid',aid)->where('mid',mid)->order('isdefault desc,id desc')->find();
		}
		if(!$address) $address = [];

		$needLocation = 0;

		$rs = \app\model\Freight::formatFreightList($freightList,$address,$product_price,$totalnum,$totalweight);

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
            $userinfo['scoredkmaxpercent'] = $adminset['scoredkmaxpercent'] = \app\custom\ScoredkmaxpercentMemberset::dealmemberscoredk(aid,$this->member,$userinfo['scoredkmaxpercent']);
        }
		$userinfo['realname'] = $this->member['realname'];
		$userinfo['tel'] = $this->member['tel'];
		
		$totalprice = $product_price;
		$leadermoney = 0;
		if($buytype == 2 && $product['leadermoney'] >0) $leadermoney = $product['leadermoney'];
		$totalprice = $totalprice - $leadermoney;
		$leveldk_money = 0;
		if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
			$leveldk_money = $product_price * (1 - $userlevel['discount'] * 0.1);
		}
		$leveldk_money = round($leveldk_money,2);
		$totalprice = $totalprice - $leveldk_money;
		
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

		$couponList = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('type','in','1,4')->where('status',0)
			->whereRaw("bid=-1 or bid=".$bid." or (bid=0 and (canused_bids='all' or find_in_set(".$bid.",canused_bids) or ($whereCids)))")->where('minprice','<=',$totalprice)->where('starttime','<=',time())->where('endtime','>',time())->order('id desc')->select()->toArray();
		if(!$couponList) $couponList = [];
		foreach($couponList as $k=>$v){
			//$couponList[$k]['starttime'] = date('m-d H:i',$v['starttime']);
			//$couponList[$k]['endtime'] = date('m-d H:i',$v['endtime']);
			$couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$v['couponid'])->find();
			if($v['bid'] > 0){
				$binfo = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->find();
				$couponList[$k]['bname'] = $binfo['name'];
			}
            $fwscene = [0];
            if(!in_array($couponinfo['fwscene'],$fwscene)){//全部可用 
                unset($couponList[$k]);
            }
			if(empty($couponinfo) || $couponinfo['fwtype']!==0){
				unset($couponList[$k]);
			}
            if($couponinfo['isgive'] == 2){
                unset($couponList[$k]);
            }
		}
		$couponList = array_values($couponList);

		$rdata = [];
		$rdata['havetongcheng'] = $havetongcheng;
		$rdata['status'] = 1;
		$rdata['address'] = $address;
		$rdata['linkman'] = $address ? $address['name'] : strval($userinfo['realname']);
		$rdata['tel'] = $address ? $address['tel'] : strval($userinfo['tel']);
		if(!$rdata['linkman']){
			$lastorder = Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('linkman','<>','')->find();
			if($lastorder){
				$rdata['linkman'] = $lastorder['linkman'];
				$rdata['tel'] = $lastorder['tel'];
			}
		}
		$rdata['product'] = $product;
		$rdata['guige'] = $guige;
		$rdata['business'] = $business;
		$rdata['freightList'] = $freightList;
		$rdata['freightArr'] = $freightArr;
		$rdata['userinfo'] = $userinfo;
		$rdata['couponList'] = $couponList;
		$rdata['buytype'] = $buytype;
		$rdata['totalnum'] = $totalnum;
		$rdata['leadermoney'] = $leadermoney;
		$rdata['product_price'] = $product_price;
		$rdata['leveldk_money'] = $leveldk_money;
		$rdata['needLocation'] = $needLocation;
		$rdata['scorebdkyf'] = Db::name('admin_set')->where('aid',aid)->value('scorebdkyf');
		$rdata['mendian_sort'] = false;
        if(getcustom('mendian_sort')){
            $rdata['mendian_sort'] = true;
        }
		return $this->json($rdata);
	}
	public function createOrder(){
		$this->checklogin();
		$post = input('post.');
		if($post['proid'] && $post['ggid']){
			$proid = $post['proid'];
			$ggid = $post['ggid'];
			$num = $post['num'] ? $post['num'] : 1;
		}else{
			return $this->json(['status'=>0,'msg'=>'产品数据错误']);
		}
		$num = intval($num);
		if($num <=0) return $this->json(['status'=>0,'msg'=>'产品数据错误']);
		$buytype = $post['buytype'];
		$teamid = $post['teamid'];

		$product_price = 0;
		$givescore  = 0; //奖励积分 
		$weight = 0;//重量
		$goodsnum = $num;
			
		$product = Db::name('collage_product')->where('aid',aid)->where('status',1)->where('ischecked',1)->where('id',$proid)->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'产品不存在或已下架']);
        if(getcustom('yx_collage_jieti')){
            if($product['collage_type'] ==1){
                //查找团
                $haveteamid = Db::name('collage_order')
                    ->where('aid',$product['aid'])
                    ->where('bid',$product['bid'])
                    ->where('status',1)
                    ->where('proid',$product['id'])
                    ->where('mid',$this->mid)
                    ->order('createtime desc')
                    ->value('teamid');
                if($haveteamid){
                    return $this->json(['status'=>0,'msg'=>'您已参团']);
                }
            }
        }
		$bid = $product['bid'];
		
		$guige = Db::name('collage_guige')->where('aid',aid)->where('id',$ggid)->find();
		if(!$guige) return $this->json(['status'=>0,'msg'=>'产品规格不存在或已下架']);
		if($guige['stock'] < $num){
			return $this->json(['status'=>0,'msg'=>$product['name'] . $guige['name'].'库存不足']);
		}
		if($product['buymax'] > 0){
			$mybuycount = $num + Db::name('collage_order')->where('aid',aid)->where('proid',$product['id'])->where('mid',mid)->where('status','in','0,1,2,3')->sum('num');
			if($mybuycount > $product['buymax']){
				return $this->json(['status'=>0,'msg'=>'每人限购'.$product['buymax'].'件']);
			}
		}
		if(getcustom('collage_givescore_time')){
			//奖励积分 确认收货后赠送
			$givescore1 = 0;
			//奖励积分2 付款后赠送
			$givescore2 = 0;
			if($product['givescore_time'] == 0){
				//奖励积分 确认收货后赠送
				$givescore1 += $guige['givescore'] * $num;
			}else{
				//奖励积分2 付款后赠送
				$givescore2 += $guige['givescore'] * $num;
			}
		}
        
		//参团判断
		if($buytype == 3){
			$tuan = Db::name('collage_order_team')->where('aid',aid)->where('id',$teamid)->find();
			if(!$tuan || $tuan['status']==0){
				return $this->json(['status'=>0,'msg'=>'没有找到该团']);
			}
			if($tuan['status']==3){
				return $this->json(['status'=>0,'msg'=>'该团已失败']);
			}
			$checknum = true;
			if(getcustom('yx_collage_jieti')){
			    if($tuan['collage_type'] ==1){
                    $checknum = false;
                    if($tuan['endtime'] < time()){
                        return $this->json(['status'=>0,'msg'=>'该团已结束']);
                    }
                }
            }
			if(($tuan['status']==2 || $tuan['num'] >= $tuan['teamnum']) && $checknum ){
				return $this->json(['status'=>0,'msg'=>'该团已满员']);
			}
			$rs = Db::name('collage_order')->where('aid',aid)->where('teamid',$teamid)->where('mid',mid)->where('status','>',0)->find();
			$no_many_times =  true;
			if(getcustom('collage_limit')){
			    //判断是否开启参团限制
                if($product['is_many_times'] == 1){
                    $no_many_times = false;
                }
                if($product['max_times'] > 0){
                    $start_time =strtotime(date('Y-m-d 00:00:00',time()));
                    $end_time = $start_time+86399;
                    $count = Db::name('collage_order')
                        ->where('aid',aid)
                        ->where('mid',mid)
                        ->where('buytype','in','2,3')
                        ->where('proid',$product['id'])
                        ->where('status','in','0,1,2,3')
                        ->where('createtime','between',[$start_time,$end_time])
                        ->count();
                    if($count >= $product['max_times']){
                        return $this->json(['status'=>0,'msg'=>'当前产品每人每天只能参团'.$product['max_times'].'次']);
                    }
                }
            }
			
			if($no_many_times && $rs){
				return $this->json(['status'=>0,'msg'=>'您已经参与该团了']);
			}
		}
		if(getcustom('plug_tengrui')) {
            //判断是否是否符合会员认证、会员关系、一户
            $tr_check = new \app\common\TengRuiCheck();
            $check_collage = $tr_check->check_collage($this->member,$product);
            if($check_collage && $check_collage['status'] == 0){
                return $this->json(['status'=>$check_collage['status'],'msg'=>$check_collage['msg']]);
            }
            $tr_roomId = $check_collage['tr_roomId'];
        }
		$leadermoney = 0;
		if($buytype == 1) $guige['sell_price'] = $guige['market_price'];
		if($buytype == 2 && $product['leadermoney'] >0) $leadermoney = $product['leadermoney'];
		$product_price += $guige['sell_price'] * $num;
		
		$weight += $guige['weight'] * $num;

		$totalprice = $product_price - $leadermoney;
		if($totalprice<0) $totalprice = 0;

		//收货地址
		if($post['addressid']=='' || $post['addressid']==0){
			$address = ['id'=>0,'name'=>$post['linkman'],'tel'=>$post['tel'],'area'=>'','address'=>''];
		}else{
			$address = Db::name('member_address')->where('id',$post['addressid'])->where('aid',aid)->where('mid',mid)->find();
		}
		
		//会员折扣
		$leveldk_money = 0;
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
		if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
			$leveldk_money = round($totalprice * (1 - $userlevel['discount'] * 0.1), 2);
		}
		$totalprice = $totalprice - $leveldk_money;


		//运费
		$freight_price = 0;
		if($post['freightid']){
			$freight = Db::name('freight')->where('aid',aid)->where('bid',$bid)->where('id',$post['freightid'])->find();
			if(($address['name']=='' || $address['tel'] =='') && ($freight['pstype']==1 || $freight['pstype']==3) && $freight['needlinkinfo']==1){
				return $this->json(['status'=>0,'msg'=>'请填写联系人和联系电话']);
			}
			
			$rs = \app\model\Freight::getFreightPrice($freight,$address,$product_price,$num,$weight);
			if($rs['status']==0) return $this->json($rs);
			$freight_price = $rs['freight_price'];

			//判断配送时间选择是否符合要求
			if($freight['pstimeset']==1){
				//$freighttime = strtotime(explode('~',$post['freight_time'])[0]);
				$freight_times = explode('~',$post['freight_time']);
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
		//优惠券
		if($post['couponrid'] > 0){
			$couponrid = $post['couponrid'];
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

			$couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('id',$couponrid)
				->whereRaw("bid=-1 or bid=".$bid." or (bid=0 and (canused_bids='all' or find_in_set(".$bid.",canused_bids) or ($whereCids)))")->find();
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
			}elseif($couponrecord['type']!=1 && $couponrecord['type']!=4){
				return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);	
			}
			$couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$couponrecord['couponid'])->find();
            if(empty($couponinfo)){
                return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不存在或已作废']);
            }
            if($couponrecord['from_mid']==0 && $couponinfo && $couponinfo['isgive']==2){
                return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'仅可转赠']);
            }
			if($couponinfo['fwtype']!==0){
				return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
			}
            //适用场景
            $fwscene = [0];
            if(!in_array($couponinfo['fwscene'],$fwscene)){//全部可用 
                return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
            }

            if(getcustom('coupon_use_interval_time') && $couponinfo['interval_time'] > 0){
                //判断优惠券规定时间内可使用次数
                $interval_time = $couponinfo['interval_time'] * 60;
                $dqsj = time();
                $zqsj = $dqsj - $interval_time * 60;

                $sycs = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('couponid',$couponinfo['id'])->where('usetime','between',[$zqsj,$dqsj])->count();

                $jgsjgs = sprintf('%g', $couponinfo['interval_time']).'小时';
                if($couponinfo['interval_time'] < 1){
                    $jgsjgs = $interval_time.'分钟';
                }

                if($sycs >= $couponinfo['usable_num']){
                    return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已超过间隔时间内可使用次数，请'.$jgsjgs.'后再试或选择其它'.t('优惠券')]);
                }
            }

            $recordupdata = ['status'=>1,'usetime'=>time()];
            if(getcustom('coupon_pack')){
                //张数
                if($couponrecord && $couponrecord['packrid'] && $couponrecord['num'] && $couponrecord['num']>0){
                    $usenum = $couponrecord['usenum']+1;
                    if($usenum<$couponrecord['num']){
                        $recordupdata = ['status'=>0,'usenum'=>$usenum];
                    }else{
                        $recordupdata = ['status'=>1,'usenum'=>$couponrecord['num'],'usetime'=>time()];
                    }
                }
            }
			Db::name('coupon_record')->where('id',$couponrid)->update($recordupdata);
			if($couponrecord['type']==4){//运费抵扣券
				$coupon_money = $freight_price;
			}else{
				$coupon_money = $couponrecord['money'];
				if($coupon_money > $totalprice) $coupon_money = $totalprice;
			}
		}else{
			$coupon_money = 0;
		}
		$totalprice = $totalprice - $coupon_money;
		$totalprice = $totalprice + $freight_price;

		//积分抵扣
		$scoredkscore = 0;
		$scoredk_money = 0;
		if($post['usescore']==1){
			$adminset = Db::name('admin_set')->where('aid',aid)->find();
			$score2money = $adminset['score2money'];
			$scoredkmaxpercent = $adminset['scoredkmaxpercent'];
			if(getcustom('sysset_scoredkmaxpercent_memberset')){
	            //处理会员单独设置积分最大抵扣比例
	            $scoredkmaxpercent = $adminset['scoredkmaxpercent'] = \app\custom\ScoredkmaxpercentMemberset::dealmemberscoredk(aid,$this->member,$scoredkmaxpercent);
	        }
			if($scoredkmaxpercent >= 0 && $scoredkmaxpercent <= 100){
				$scorebdkyf       = $adminset['scorebdkyf'];
				//个人积分全部转换为金额
				$allscoredk_money = $this->member['score'] * $score2money;
				if($allscoredk_money >0){
					if($scorebdkyf == 1){//积分不抵扣运费
						$scoredk_totalprice = $totalprice - $freight_price;
					}else{
						$scoredk_totalprice = $totalprice;
					}
					//最多抵扣判断
					if($allscoredk_money > $scoredk_totalprice * $scoredkmaxpercent * 0.01){
						$scoredk_money = $scoredk_totalprice * $scoredkmaxpercent * 0.01;
					}else{
						$scoredk_money = $allscoredk_money;
					}
					$totalprice = $totalprice - $scoredk_money;
				}
			}
			$totalprice = round($totalprice*100)/100;
			if($scoredk_money > 0){
				$scoredkscore = dd_score_format($scoredk_money / $score2money,$this->score_weishu);
			}
		}
	   
		if($buytype ==2){//创建团
		    
            if(getcustom('collage_limit')){
                //判断是否开启参团限制
                if($product['max_times'] > 0){
                    $start_time =strtotime(date('Y-m-d 00:00:00',time()));
                    $end_time = $start_time+86399;
                   
                    $count = Db::name('collage_order')
                        ->where('aid',aid)
                        ->where('mid',mid)
                        ->where('buytype','in','2,3')
                        ->where('proid',$product['id'])
                        ->where('status','in','0,1,2,3')
                        ->where('createtime','between',[$start_time,$end_time])
                        ->count();
                    if($count >= $product['max_times']){
                        return $this->json(['status'=>0,'msg'=>'当前产品每人每天只能参团'.$product['max_times'].'次']);
                    }
                }
            }
            
			$tdata = [];
			$tdata['aid'] = aid;
			$tdata['bid'] = $bid;
			$tdata['mid'] = mid;
			$tdata['proid'] = $product['id'];
			$tdata['teamhour'] = $product['teamhour'];
			$tdata['teamnum'] = $product['teamnum'];
			$tdata['status'] = 0;
			$tdata['num'] = 0;
			$tdata['createtime'] = time();
            if(getcustom('yx_collage_jieti')){
                $tdata['collage_type'] = $product['collage_type'];
                if($product['collage_type'] ==1){
                    $tdata['endtime'] =  $product['endtime'];
                    $tdata['teamnum'] =  0;
                }
            }
			$teamid = Db::name('collage_order_team')->insertGetId($tdata);
			if(getcustom('yx_collage_teambuy_type')){
				//是开启团长拼团模式一 直接发起、不占参团人数、不发货
				$teambuy_type = Db::name('collage_sysset')->where('aid',aid)->value('teambuy_type');
				if($teambuy_type == 1){
					$totalprice = 0;
				}
			}
		}elseif($buytype==3){//参团
			
		}

		$orderdata = [];
		$orderdata['aid'] = aid;
		$orderdata['bid'] = $bid;
		$orderdata['mid'] = mid;

		$ordernum = date('ymdHis').aid.rand(1000,9999);
		$orderdata['ordernum'] = $ordernum;
		$orderdata['title'] = removeEmoj($product['name']);
		
		$orderdata['proid'] = $product['id'];
		$orderdata['proname'] = $product['name'];
		$orderdata['propic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
		$orderdata['ggid'] = $guige['id'];
		$orderdata['ggname'] = $guige['name'];
		$orderdata['cost_price'] = $guige['cost_price'];
		$orderdata['sell_price'] = $guige['sell_price'];
		$orderdata['num'] = $num;
		
		$orderdata['linkman'] = $address['name'];
		$orderdata['tel'] = $address['tel'];
		$orderdata['area'] = $address['area'];
		$orderdata['area2'] = $address['province'].','.$address['city'].','.$address['district'];
		$orderdata['address'] = $address['address'];
		$orderdata['longitude'] = $address['longitude'];
		$orderdata['latitude'] = $address['latitude'];
		$orderdata['totalprice'] = $totalprice;
		$orderdata['product_price'] = $product_price;
		$orderdata['freight_price'] = $freight_price; //运费
		$orderdata['leveldk_money'] = $leveldk_money;  //会员折扣
		$orderdata['scoredk_money'] = $scoredk_money;	//积分抵扣
		$orderdata['scoredkscore'] = $scoredkscore;	//抵扣的积分
		if($freight && ($freight['pstype']==0 || $freight['pstype']==10)){
			$orderdata['freight_text'] = $freight['name'].'('.$freight_price.'元)';
			$orderdata['freight_type'] = $freight['pstype'];
		}elseif($freight && $freight['pstype']==1){
			$storename = Db::name('mendian')->where('aid',aid)->where('id',$post['storeid'])->value('name');
			$orderdata['freight_text'] = $freight['name'].'['.$storename.']';
			$orderdata['freight_type'] = 1;
			$orderdata['mdid'] = $post['storeid'];
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
		$orderdata['freight_time'] = $post['freight_time']; //配送时间
		$orderdata['createtime'] = time();
		$orderdata['coupon_rid'] = $couponrid;
		$orderdata['coupon_money'] = $coupon_money; //优惠券抵扣
		$orderdata['leader_money'] = $leadermoney; //团长优惠金额
		$orderdata['buytype'] = $buytype; //1单买 2发团 3参团

		if(getcustom('yx_collage_teambuy_type')){
			//是开启团长拼团模式一 直接发起、不占参团人数、不发货
			if($buytype ==2 && $teambuy_type  && $teambuy_type ==1){
				$orderdata['teambuy_type'] = $teambuy_type;
			}else{
				$orderdata['teambuy_type'] = 0;
			}
		}

		if($buytype ==2 && $product['leaderscore'] > 0){//团长奖励积分
			$givescore += $product['leaderscore'];
		}
		$orderdata['givescore'] = $givescore;

		if(getcustom('collage_givescore_time')){
			$orderdata['givescore1'] = $givescore1;
			$orderdata['givescore2'] = $givescore2;
		}
		$orderdata['teamid'] = $teamid;
		$orderdata['hexiao_code'] = random(16);
		$orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=collage&co='.$orderdata['hexiao_code']));
		$orderdata['platform'] = platform;

        if($product['bid'] > 0) {
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			$scoredkmoney = 0;
			if($bset['scoredk_kouchu'] == 0){ //扣除积分抵扣
				$scoredkmoney = 0;
			}
            $business_feepercent = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->value('feepercent');
            $totalprice_business = $product_price - $coupon_money - $leadermoney;
            if($bset['scoredk_kouchu']==1){
                $totalprice_business = $totalprice_business - $scoredkmoney;
            }
            if(getcustom('business_deduct_cost')){
            	//查询商家扣除成本设置
            	$business = Db::name('business')->where('id',$product['bid'])->where('aid',aid)->field('id,deduct_cost')->find();
            }
            //商品独立费率
            if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                $orderdata['business_total_money'] = $totalprice_business * (100-$product['feepercent']) * 0.01;
                if(getcustom('business_deduct_cost')){
                	if($business && $business['deduct_cost'] == 1 && $orderdata['cost_price']>0){
                		if($orderdata['cost_price']<=$orderdata['sell_price']){
							$all_cost_price = $orderdata['cost_price'];
						}else{
							$all_cost_price = $orderdata['sell_price'];
						}
	                	//扣除成本
	                	$orderdata['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$product['feepercent']/100;
	                }
                }
				if(getcustom('business_fee_type')){
					$bset = Db::name('business_sysset')->where('aid',aid)->find();
                    if($bset['business_fee_type'] == 0){
                        $platformMoney = ($totalprice_business+$freight_price) * $product['feepercent'] * 0.01;
                        $orderdata['business_total_money'] = $totalprice_business - $platformMoney;
                    }elseif($bset['business_fee_type'] == 1){
						$platformMoney = $totalprice_business * $product['feepercent'] * 0.01;
						$orderdata['business_total_money'] = $totalprice_business - $platformMoney;
					}elseif($bset['business_fee_type'] == 2){
						$platformMoney = $orderdata['cost_price'] * $product['feepercent'] * 0.01;
						$orderdata['business_total_money'] = $totalprice_business - $platformMoney;
					}
				}
            } else {
                //商户费率
                $orderdata['business_total_money'] = $totalprice_business * (100-$business_feepercent) * 0.01;
                if(getcustom('business_deduct_cost')){
                	if($business && $business['deduct_cost'] == 1 && $orderdata['cost_price']>0){
                		if($orderdata['cost_price']<=$orderdata['sell_price']){
							$all_cost_price = $orderdata['cost_price'];
						}else{
							$all_cost_price = $orderdata['sell_price'];
						}
	                	//扣除成本
	                	$orderdata['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$business_feepercent/100;
	                }
                }
				if(getcustom('business_fee_type')){
					$bset = Db::name('business_sysset')->where('aid',aid)->find();
                    if($bset['business_fee_type'] == 0){
                        $platformMoney = ($totalprice_business+$freight_price) * $business_feepercent * 0.01;
                        $orderdata['business_total_money'] = $totalprice_business - $platformMoney;
                    }elseif($bset['business_fee_type'] == 1){
						$platformMoney = $totalprice_business * $business_feepercent * 0.01;
						$orderdata['business_total_money'] = $totalprice_business - $platformMoney;
					}elseif($bset['business_fee_type'] == 2){
						$platformMoney = $orderdata['cost_price'] * $business_feepercent * 0.01;
						$orderdata['business_total_money'] = $totalprice_business - $platformMoney;
					}
				}
            }
        }

		//计算佣金的商品金额
		//$commission_totalprice = $orderdata['totalprice'];
		$commission_totalprice = $product_price;
		//算佣金
		$sysset = Db::name('admin_set')->where('aid',aid)->find();
		if($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2){
            $commission_totalprice = $product_price - $leveldk_money - $scoredk_money;
            if($couponrecord['type']!=4) {//运费抵扣券
                $commission_totalprice -= $coupon_money;
            }
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
						$orderdata['parent1'] = $parent1['id'];
					}
				}
			}
			if($parent1['pid']){
				$parent2 = Db::name('member')->where('aid',aid)->where('id',$parent1['pid'])->find();
				if($parent2){
					$agleveldata2 = Db::name('member_level')->where('aid',aid)->where('id',$parent2['levelid'])->find();
					if($agleveldata2['can_agent']>1){
						$orderdata['parent2'] = $parent2['id'];
					}
				}
			}
			if($parent2['pid']){
				$parent3 = Db::name('member')->where('aid',aid)->where('id',$parent2['pid'])->find();
				if($parent3){
					$agleveldata3 = Db::name('member_level')->where('aid',aid)->where('id',$parent3['levelid'])->find();
					if($agleveldata3['can_agent']>2){
						$orderdata['parent3'] = $parent3['id'];
					}
				}
			}
			if($product['commissionset']==1){//按商品设置的分销比例
				$commissiondata = json_decode($product['commissiondata1'],true);
				if($commissiondata){
					if($agleveldata1) $orderdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
					if($agleveldata2) $orderdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
					if($agleveldata3) $orderdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
				}
			}elseif($product['commissionset']==2){//按固定金额
				$commissiondata = json_decode($product['commissiondata2'],true);
				if($commissiondata){
					if($agleveldata1) $orderdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'];
					if($agleveldata2) $orderdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'];
					if($agleveldata3) $orderdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'];
				}
			}elseif($product['commissionset']==3){//提成是积分
				$commissiondata = json_decode($product['commissiondata3'],true);
				if($commissiondata){
					if($agleveldata1) $orderdata['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'];
					if($agleveldata2) $orderdata['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'];
					if($agleveldata3) $orderdata['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'];
				}
			}else{ //按会员等级设置的分销比例
				if($agleveldata1){
					if($agleveldata1['commissiontype']==1){ //固定金额按单
						$orderdata['parent1commission'] = $agleveldata1['commission1'];	
					}else{
						$orderdata['parent1commission'] = $agleveldata1['commission1'] * $commission_totalprice * 0.01;
					}
				}
				if($agleveldata2){
					if($agleveldata2['commissiontype']==1){
						$orderdata['parent2commission'] = $agleveldata2['commission2'];				
					}else{
						$orderdata['parent2commission'] = $agleveldata2['commission2'] * $commission_totalprice * 0.01;
					}
				}
				if($agleveldata3){
					if($agleveldata3['commissiontype']==1){
						$orderdata['parent3commission'] = $agleveldata3['commission3'];
					}else{
						$orderdata['parent3commission'] = $agleveldata3['commission3'] * $commission_totalprice * 0.01;
					}
				}
			}
		
		}

		if(getcustom('plug_tengrui')) {
            $orderdata['tr_roomId'] = $tr_roomId;
        }

        if (getcustom('yx_mangfan_collage')) {
            $mangfan_info = \app\custom\Mangfan::mangfanInfo(aid, $orderdata['proid'],'collage');
            $orderdata['is_mangfan']   = $mangfan_info['status'];
            $orderdata['mangfan_rate'] = $mangfan_info['rate'];
            $orderdata['mangfan_commission_type'] = $mangfan_info['commission_type'];
        }

		$orderid = Db::name('collage_order')->insertGetId($orderdata);
		if($orderdata['parent1'] && ($orderdata['parent1commission'] || $orderdata['parent1score'])){
			$parent1_levelid = $parent1['levelid']??0;
			Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$orderdata['parent1'],'frommid'=>mid,'orderid'=>$orderid,'ogid'=>$product['id'],'type'=>'collage','commission'=>$orderdata['parent1commission'],'score'=>$orderdata['parent1score'],'remark'=>'下级购买商品奖励','createtime'=>time(),'levelid'=>$parent1_levelid]);
		}
		if($orderdata['parent2'] && ($orderdata['parent2commission'] || $orderdata['parent2score'])){
			$parent2_levelid = $parent2['levelid']??0;
			Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$orderdata['parent2'],'frommid'=>mid,'orderid'=>$orderid,'ogid'=>$product['id'],'type'=>'collage','commission'=>$orderdata['parent2commission'],'score'=>$orderdata['parent2score'],'remark'=>'下二级购买商品奖励','createtime'=>time(),'levelid'=>$parent2_levelid]);
		}
		if($orderdata['parent3'] && ($orderdata['parent3commission'] || $orderdata['parent3score'])){
			$parent3_levelid = $parent3['levelid']??0;
			Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$orderdata['parent3'],'frommid'=>mid,'orderid'=>$orderid,'ogid'=>$product['id'],'type'=>'collage','commission'=>$orderdata['parent3commission'],'score'=>$orderdata['parent3score'],'remark'=>'下三级购买商品奖励','createtime'=>time(),'levelid'=>$parent3_levelid]);
		}

		\app\model\Freight::saveformdata($orderid,'collage_order',$freight['id'],$post['formdata']);

		$payorderid = \app\model\Payorder::createorder(aid,$orderdata['bid'],$orderdata['mid'],'collage',$orderid,$ordernum,$orderdata['title'],$orderdata['totalprice'],$orderdata['scoredkscore']);

		//减库存加销量
		$stock = $guige['stock'] - $num;
		if($stock < 0) $stock = 0;
		$pstock = $product['stock'] - $num;
		if($pstock < 0) $pstock = 0;
		$sales = $guige['sales'] + $num;
		$psales = $product['sales'] + $num;
		Db::name('collage_guige')->where('aid',aid)->where('id',$guige['id'])->update(['stock'=>$stock,'sales'=>$sales]);
		Db::name('collage_product')->where('aid',aid)->where('id',$product['id'])->update(['stock'=>$pstock,'sales'=>$psales]);

        $store_name = Db::name('admin_set')->where('aid',aid)->value('name');
		//公众号通知 订单提交成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新拼团订单提交成功';
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
		\app\common\Wechat::sendhttmpl(aid,$orderdata['bid'],'tmpl_orderconfirm',$tmplcontent,m_url('admin/order/collageorder'),$orderdata['mdid'],$tempconNew);
		
		$tmplcontent = [];
		$tmplcontent['thing11'] = $orderdata['title'];
		$tmplcontent['character_string2'] = $orderdata['ordernum'];
		$tmplcontent['phrase10'] = '待付款';
		$tmplcontent['amount13'] = $orderdata['totalprice'].'元';
		$tmplcontent['thing27'] = $this->member['nickname'];
		\app\common\Wechat::sendhtwxtmpl(aid,$orderdata['bid'],'tmpl_orderconfirm',$tmplcontent,'admin/order/collageorder',$orderdata['mdid']);

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
		if(getcustom('yx_collage_team_in_team')){
			$where[] = ['isteaminteam','=',0];
		}
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
		$datalist = Db::name('collage_order')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = array();
        $collectReward = Db::name('order_collect_reward')->field('order_type,platform,gettj,min_order_amount,prompt,fontcolor,bgcolor,start_time,end_time')->where('aid',aid)->where('start_time','<=',time())->where('end_time','>=',time())->where('status',1)->find();
        $isCollectReward = false;
        if($collectReward){
            if($collectReward['bgcolor']){
                $color1rgb = hex2rgb($collectReward['bgcolor']);
                $collectReward['bgcolor'] = $color1rgb['red'] . ',' . $color1rgb['green'] . ',' . $color1rgb['blue'];
            }
            $isCollectReward = $this->collectRewardNumLimit(aid,mid,$collectReward);
        }
        foreach($datalist as $key=>$v){
			if($v['buytpe']!=1) $datalist[$key]['team'] = Db::name('collage_order_team')->where('id',$v['teamid'])->find();
            //发票
            $datalist[$key]['invoice'] = 0;
            if($v['bid']) {
                $datalist[$key]['invoice'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->value('invoice');
            } else {
                $datalist[$key]['invoice'] = Db::name('admin_set')->where('aid',aid)->value('invoice');
            }
            $collage_type = 0;
            if(getcustom('yx_collage_jieti')){
                $collage_type =Db::name('collage_product')->where('id',$v['proid'])->value('collage_type');
            }
            $datalist[$key]['collage_type'] =  $collage_type;
            //确认收货奖励
            $datalist[$key]['is_collect_reward'] = $isCollectReward && $this->isCollectReward($v,$collectReward,$this->member['levelid'],'collage');
		}
		$rdata = [];
		$rdata['st'] = $st;
		$rdata['datalist'] = $datalist;
        $rdata['collect_reward_set'] = $collectReward;
		return $this->json($rdata);
	}
	public function orderdetail(){
		$this->checklogin();
		$detail = Db::name('collage_order')->where('id',input('param.id/d'))->where('aid',aid)->where('mid',mid)->find();
		if(!$detail) return $this->json(['status'=>0,'msg'=>'订单不存在']);
		
		$detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s',$detail['createtime']) : '';
		$detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s',$detail['collect_time']) : '';
		$detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s',$detail['paytime']) : '';
		$detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s',$detail['refund_time']) : '';
		$detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s',$detail['send_time']) : '';
		$detail['formdata'] = \app\model\Freight::getformdata($detail['id'],'collage_order');

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

		$storeinfo = [];
		if($detail['freight_type'] == 1){
            $storeinfo = Db::name('mendian')->where('id',$detail['mdid'])->field('id,name,address,longitude,latitude')->find();
		}
		if($detail['buytpe']!=1){
			$team = Db::name('collage_order_team')->where('id',$detail['teamid'])->find();
		}else{
			$team = [];
		}
		$shopset = Db::name('collage_sysset')->where('aid',aid)->field('comment')->find();

		$rdata = [];
		$rdata['status'] = 1;
		//发票
        $rdata['invoice'] = 0;
        if($detail['bid']) {
            $rdata['invoice'] = Db::name('business')->where('aid',aid)->where('id',$detail['bid'])->value('invoice');
        } else {
            $rdata['invoice'] = Db::name('admin_set')->where('aid',aid)->value('invoice');
        }
		$rdata['detail'] = $detail;
		$rdata['team'] = $team;
		$rdata['shopset'] = $shopset;
		$rdata['storeinfo'] = $storeinfo;
		return $this->json($rdata);
	}
	function closeOrder(){
		$this->checklogin();
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('collage_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(!$order || $order['status']!=0){
			return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
		}
		$rs = Db::name('collage_order')->where('id',$orderid)->where('status',0)->where('aid',aid)->where('mid',mid)->update(['status'=>4]);
		if(!$rs)  return $this->json(['status'=>0,'msg'=>'操作失败']);
		//加库存
		Db::name('collage_guige')->where('aid',aid)->where('id',$order['ggid'])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("sales-".$order['num'])]);
		Db::name('collage_product')->where('aid',aid)->where('id',$order['proid'])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("sales-".$order['num'])]);
		
		//优惠券抵扣的返还
		if($order['coupon_rid'] > 0){
			\app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
		}

		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	function delOrder(){
		$this->checklogin();
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('collage_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(!$order || ($order['status']!=4 && $order['status']!=3)){
			return $this->json(['status'=>0,'msg'=>'删除失败,订单状态错误']);
		}
		if($order['status']==3){
			$rs = Db::name('collage_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->update(['delete'=>1]);
		}else{
			$rs = Db::name('collage_order')->where('id',$orderid)->where('aid',aid)->where('mid',mid)->delete();
		}
		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}
	function orderCollect(){ //确认收货
		$this->checklogin();
		$post = input('post.');
		$orderid = intval($post['orderid']);
		$order = Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
		if(!$order || ($order['status']!=2)){
			return $this->json(['status'=>0,'msg'=>'订单状态不符合收货要求']);
		}
        $order['collect_reward_platform'] = platform; //确认收货奖励判断平台
		$rs = \app\common\Order::collect($order,'collage');
		if($rs['status'] == 0) return $this->json($rs);
		Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);
		\app\common\Member::uplv(aid,mid);
        //确认收货奖励
        $collectReward = $this->getCollectReward(aid,mid,$orderid);
		$tmplcontent = [];
		$tmplcontent['first'] = '有拼团订单客户已确认收货';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $this->member['nickname'];
		$tmplcontent['keyword2'] = $order['ordernum'];
		$tmplcontent['keyword3'] = $order['totalprice'].'元';
		$tmplcontent['keyword4'] = date('Y-m-d H:i',$order['paytime']);
        $tmplcontentNew = [];
        $tmplcontentNew['thing3'] = $this->member['nickname'];//收货人
        $tmplcontentNew['character_string7'] = $order['ordernum'];//订单号
        $tmplcontentNew['time8'] = date('Y-m-d H:i');//送达时间
		\app\common\Wechat::sendhttmpl(aid,$order['bid'],'tmpl_ordershouhuo',$tmplcontent,m_url('admin/order/collageorder'),$order['mdid'],$tmplcontentNew);
		
		$tmplcontent = [];
		$tmplcontent['thing2'] = $order['title'];
		$tmplcontent['character_string6'] = $order['ordernum'];
		$tmplcontent['thing3'] = $this->member['nickname'];
		$tmplcontent['date5'] = date('Y-m-d H:i');
		\app\common\Wechat::sendhtwxtmpl(aid,$order['bid'],'tmpl_ordershouhuo',$tmplcontent,'admin/order/collageorder',$order['mdid']);

		return $this->json(['status'=>1,'msg'=>'确认收货成功','collect_reward' => $collectReward]);
	}
	function refund(){//申请退款
		$this->checklogin();
		if(request()->isPost()){
			$post = input('post.');
			$orderid = intval($post['orderid']);
			$money = floatval($post['money']);
			$order = Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
			if(!$order || ($order['status']!=1 && $order['status'] != 2) || $order['refund_status'] == 2){
				return $this->json(['status'=>0,'msg'=>'订单状态不符合退款要求']);
			}
			if($money < 0 || $money > $order['totalprice']){
				return $this->json(['status'=>0,'msg'=>'退款金额有误']);
			}
			if($order['bid'] > 0){
				$business = Db::name('business')->where('aid',aid)->where('id',$order['bid'])->find();
				if(empty($business)) return $this->json(['status'=>0,'msg'=>'请联系平台客服处理退款']);
			}
			Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->update(['refund_time'=>time(),'refund_status'=>1,'refund_reason'=>$post['reason'],'refund_money'=>$money]);
			
            if(getcustom('yx_collage_orderrefund_wifiprint')){
            	$refund_wifiprint = Db::name('collage_sysset')->where('aid',aid)->value('refund_wifiprint');
            	if($refund_wifiprint == 1){
            		//退款打印小票
                	\app\common\Wifiprint::print(aid,'collage',$orderid,1,0,-1,'shop',-1,['opttype'=>'collage_refund']);
            	}
            }
			$tmplcontent = [];
			$tmplcontent['first'] = '有拼团订单客户申请退款';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $order['ordernum'];
			$tmplcontent['keyword2'] = $money.'元';
			$tmplcontent['keyword3'] = $post['reason'];
            $tmplcontentNew = [];
            $tmplcontentNew['number2'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount4'] = $money;//退款金额
			\app\common\Wechat::sendhttmpl(aid,$order['bid'],'tmpl_ordertui',$tmplcontent,m_url('admin/order/collageorder'),$order['mdid'],$tmplcontentNew);

			$tmplcontent = [];
			$tmplcontent['thing1'] = $order['title'];
			$tmplcontent['character_string4'] = $order['ordernum'];
			$tmplcontent['amount2'] = $order['totalprice'];
			$tmplcontent['amount9'] = $money.'元';
			$tmplcontent['thing10'] = $post['reason'];
			\app\common\Wechat::sendhtwxtmpl(aid,$order['bid'],'tmpl_ordertui',$tmplcontent,'admin/order/collageorder',$order['mdid']);

			return $this->json(['status'=>1,'msg'=>'提交成功,请等待商家审核']);
		}
		$rdata = [];
		$rdata['price'] = input('param.price/f');
		$rdata['orderid'] = input('param.orderid/d');
		$order = Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$rdata['orderid'])->find();
		$rdata['price'] = $order['totalprice'];
		return $this->json($rdata);
	}
	//评价商品
	public function comment(){
		$this->checklogin();
		$orderid = input('param.orderid/d');
		$og = Db::name('collage_order')->where('id',$orderid)->where('mid',mid)->find();
		if(!$og){
			return $this->json(['status'=>0,'msg'=>'未查找到相关记录']);
		}
		$comment = Db::name('collage_comment')->where('orderid',$orderid)->where('aid',aid)->where('mid',mid)->find();
		if(request()->isPost()){
			$shopset = Db::name('collage_sysset')->where('aid',aid)->find();
			if($shopset['comment']==0){
				return $this->json(['status'=>0,'msg'=>'评价功能未开启']);
			}
			if($comment){
				return $this->json(['status'=>0,'msg'=>'您已经评价过了']);
			}
			$order = Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$orderid)->find();
			$content = input('post.content');
			$content_pic = input('post.content_pic');
			$score = input('post.score/d');
			if($score < 1){
				return $this->json(['status'=>0,'msg'=>'请打分']);
			}
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['orderid'] = $order['id'];
			$data['ordernum']= $order['ordernum'];
			$data['proid'] =$order['proid'];
			$data['proname'] = $order['proname'];
			$data['propic'] = $order['propic'];
			$data['ggid'] = $order['ggid'];
			$data['ggname'] = $order['ggname'];
			$data['score'] = $score;
			$data['content'] = $content;
			$data['nickname']= $this->member['nickname'];
			$data['headimg'] = $this->member['headimg'];
			$data['createtime'] = time();
			$data['content_pic'] = $content_pic;
			$data['status'] = ($shopset['comment_check']==1 ? 0 : 1);
			//if($shopset['comment_check']==0){
			//	$data['status'] = 1;
				//$data['givescore'] = $shopset['comment_givescore'];
			//}else{
			//	$data['status'] = 0;
				//$data['givescore'] = 0;
			//}
			Db::name('collage_comment')->insert($data);
			Db::name('collage_order')->where('aid',aid)->where('mid',mid)->where('id',$order['id'])->update(['iscomment'=>1]);
			
			//如果不需要审核 增加产品评论数及评分
			if($shopset['comment_check']==0){
				$countnum = Db::name('collage_comment')->where('proid',$order['proid'])->where('status',1)->count();
				$score = Db::name('collage_comment')->where('proid',$order['proid'])->where('status',1)->avg('score');
				$haonum = Db::name('collage_comment')->where('proid',$order['proid'])->where('status',1)->where('score','>',3)->count(); //好评数
				if($countnum > 0){
					$haopercent = $haonum/$countnum*100;
				}else{
					$haopercent = 100;
				}
				Db::name('collage_product')->where('id',$order['proid'])->update(['comment_num'=>$countnum,'comment_score'=>$score,'comment_haopercent'=>$haopercent]);
			}
			return $this->json(['status'=>1,'msg'=>'评价成功']);
		}
		$rdata = [];
		$rdata['og'] = $og;
		$rdata['comment'] = $comment;
		return $this->json($rdata);
	}

	function team(){
		$this->checklogin();
		$teamid = input('param.teamid/d');
		$team = Db::name('collage_order_team')->where('aid',aid)->where('id',$teamid)->find();
        if(getcustom('yx_collage_team_in_team')){
            $teampid = input('param.teampid/d');
            //查询通过分享进入的团是否已满或失败，已满则切换到其他团
            if($teampid &&($team['status'] == 2 || $team['status'] == 3)){
                //查询参与的其他团
                $newteam = Db::name('collage_order_team')->where('mid',$team['mid'])->where('proid',$team['proid'])->where('status',1)->where('aid',aid)->order('id asc')->find();
                if(!$newteam){
                    return $this->json(['status'=>0,'msg'=>'','url'=>'/pages/index/index','opentype'=>'redirect']);
                }
                $team = $newteam;
            }
        }
		$product = Db::name('collage_product')->where('aid',aid)->where('id',$team['proid'])->find();
		if(!$product) return $this->json(['status'=>0,'msg'=>'商品不存在']);
		if($product['status']==0) return $this->json(['status'=>0,'msg'=>'商品已下架']);
		if($product['ischecked']!=1) return $this->json(['status'=>0,'msg'=>'商品未审核']);
		
		if(!$product['pics']) $product['pics'] = $product['pic'];
		$product['pics'] = explode(',',$product['pics']);
		if($product['fuwupoint']){
			$product['fuwupoint'] = explode(' ',preg_replace("/\s+/",' ',str_replace('　',' ',trim($product['fuwupoint']))));
		}
		$gglist = Db::name('collage_guige')->where('proid',$product['id'])->select()->toArray();
		$guigelist = array();
		foreach($gglist as $k=>$v){
			$guigelist[$v['ks']] = $v;
		}
		$guigedata = json_decode($product['guigedata'],true);
		$ggselected = [];
		foreach($guigedata as $v) {
			$ggselected[] = 0;
		}
		$ks = implode(',',$ggselected);

		$orderlist = Db::name('collage_order')->where('aid',aid)->where('teamid',$teamid)->where('status','in','1,2,3')->select()->toArray();
		$userlist = [];
		$haveme = 0;
		$show_mingpian = 0;
		if(getcustom('collage_show_mingpian')){
            $show_mingpian = Db::name('collage_sysset')->where('aid',aid)->value('show_mingpian');
        }
		foreach($orderlist as $v){
			if(!$v['isjiqiren']){
				$user = Db::name('member')->field('id,nickname,headimg,province,city,sex')->where('aid',aid)->where('id',$v['mid'])->find();
			}else{
				$user = Db::name('collage_jiqilist')->field('id,nickname,headimg')->where('aid',aid)->where('id',$v['jiqirenid'])->find();
				if($user) $user['province'] = $user['city'] = $user['sex'] = '';
			}
			if($show_mingpian){
                $mingpian_id = Db::name('mingpian')->where('aid',aid)->where('mid',$user['id'])->value('id');
                $user['mingpian_id'] = $mingpian_id;
            }
			if($user){
                $userlist[] = $user;
                if($user['id'] == mid && !$v['isjiqiren']) $haveme =1;
            }
		}
		if(getcustom('yx_collage_jieti')){
		    if($team['collage_type'] ==1)$team['teamnum'] =  $team['num'];
        }
		if($team['teamnum'] > $team['num']){
			for($i=0;$i<$team['teamnum'] - $team['num'];$i++){
				$userlist[] = ['id'=>'','nickanme'=>'','headimg'=>''];
			}
		}
		$rtime = $team['createtime'] + $team['teamhour'] * 3600 - time();
		$set = Db::name('admin_set')->field('name,logo,desc,tel')->where('aid',aid)->find();
		$shopset = Db::name('collage_sysset')->field('comment,showjd')->where('aid',aid)->find();
		$product['detail'] = \app\common\System::initpagecontent($product['detail'],aid,mid,platform);
         if(getcustom('yx_collage_jieti')){
             if($team['collage_type'] ==1){
                 $rtime =  $team['endtime'] - time();
             }
             $product['nowtime'] = time();
             $product['jieti_data'] = json_decode($product['jieti_data'],true);
             //查询是否存在订单
            $haveorder =  Db::name('collage_order')->where('aid',aid)->where('proid',$product['id'])->where('mid',mid)->where('status','in',[1,2,3])->find();
            $team['haveorder']  = $haveorder?1:0;
            //已报名数量
            $ordernum =  0+ Db::name('collage_order')->where('aid',aid)->where('proid',$product['id'])->where('status','in',[1,2,3])->count();
            $product['ordernum']  = $ordernum?$ordernum:0;
            //增加share_num
            if(input('param.pid')){
                Db::name('collage_product')->where('id',$product['id'])->inc('share_num',1)->update();
            }
            if($product['bid'] >0){
                $tel = Db::name('business')->where('aid',aid)->where('id',$product['bid'])->value('tel');
            }else{
                $tel = Db::name('admin_set')->where('aid',aid)->value('tel');
            }
             $shopset['tel'] = $tel;
             //加入浏览记录
             $view_history = Db::name('collage_view_history')->where('aid',aid)->where('bid',$product['bid'])->where('proid',$product['id'])->where('mid',mid)->find();
             if(!$view_history){
                 Db::name('collage_view_history')->insert([
                     'aid' =>aid,
                     'bid' => $product['bid'],
                     'proid' => $product['id'],
                     'mid' => mid,
                     'createtime' => time()
                 ]);
             }
             //增加虚拟数据
             $product['share_num'] =    $product['share_num'] + $product['xn_share_num'];
             $product['view_num'] =    $product['view_num'] + $product['xn_view_num'];
         }
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['team'] = $team;
		$rdata['product'] = $product;
		$rdata['guigelist'] = $guigelist;
		$rdata['guigedata'] = $guigedata;
		$rdata['ggselected'] = $ggselected;
		$rdata['ks'] = $ks;
		$rdata['sysset'] = $set;
		$rdata['shopset'] = $shopset;
		$rdata['userlist'] = $userlist;
		$rdata['rtime'] = $rtime;
		$rdata['haveme'] = $haveme;
        $rdata['show_mingpian'] = $show_mingpian;
        if(getcustom('yx_collage_jieti')){                                   
            $view_history = Db::name('collage_view_history')->alias('vh')
                ->join('member m','vh.mid = m.id')
                ->where('vh.aid',aid)
                ->where('vh.bid',$product['bid'])
                ->where('vh.proid',$product['id'])
                ->field('vh.*,m.headimg')
                ->select()->toArray();
            $rdata['view_history'] = $view_history;
        }
		return $this->json($rdata);
	}
	public function logistics(){//查快递单号
		$get = input('param.');
		$content = \app\common\Common::ali_getwuliu($get['express_no'],$get['express'],aid);
		$data = json_decode($content,true);

		if(!$data || $data['msg']!='ok'){
			$list = [];
		}else{
			$list = $data['result']['list'];
			foreach($list as $k=>$v){
				$list[$k]['context'] = $v['status'];
			}
		}

		$rdata = [];
		$rdata['express_no'] = $get['express_no'];
		$rdata['express'] = $get['express'];
		$rdata['datalist'] = $list;
		return $this->json($rdata);
	}
	//商品海报
	function getposter(){
		$this->checklogin();
		$post = input('post.');
		$platform = platform;
		$page = '/activity/collage/product';
		$scene = 'id_'.$post['proid'].'-pid_'.$this->member['id'];
		//if($platform == 'mp' || $platform == 'h5' || $platform == 'app'){
		//	$page = PRE_URL .'/h5/'.aid.'.html#'. $page;
		//}
		$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','collage')->where('platform',$platform)->order('id')->find();

		$posterdata = Db::name('member_poster')->where('aid',aid)->where('mid',mid)->where('scene',$scene)->where('type','collage')->where('posterid',$posterset['id'])->find();
		if(!$posterdata){
			$product = Db::name('collage_product')->where('id',$post['proid'])->find();
			$sysset = Db::name('admin_set')->where('aid',aid)->find();
			$textReplaceArr = [
				'[头像]'=>$this->member['headimg'],
				'[昵称]'=>$this->member['nickname'],
				'[姓名]'=>$this->member['realname'],
				'[手机号]'=>$this->member['mobile'],
				'[商城名称]'=>$sysset['name'],
				'[商品名称]'=>$product['name'],
				'[商品销售价]'=>$product['sell_price'],
				'[商品市场价]'=>$product['market_price'],
				'[商品图片]'=>$product['pic'],
			];

			$poster = $this->_getposter(aid,$product['bid'],$platform,$posterset['content'],$page,$scene,$textReplaceArr);
			$posterdata = [];
			$posterdata['aid'] = aid;
			$posterdata['mid'] = $this->member['id'];
			$posterdata['scene'] = $scene;
			$posterdata['page'] = $page;
			$posterdata['type'] = 'collage';
			$posterdata['poster'] = $poster;
			$posterdata['createtime'] = time();
			Db::name('member_poster')->insert($posterdata);
		}
		return $this->json(['status'=>1,'poster'=>$posterdata['poster']]);
	}
	function getTeamPoster(){ //参团海报
		$this->checklogin();
		$post = input('post.');
		$platform = platform;
		$page = '/activity/collage/team';
		$scene = 'teamid_'.$post['teamid'].'-pid_'.$this->member['id'].'-tpid_1';
		//if($platform == 'mp' || $platform == 'h5' || $platform == 'app'){
		//	$page = PRE_URL .'/h5/'.aid.'.html#'. $page;
		//}
		$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','collageteam')->where('platform',$platform)->order('id')->find();

		$posterdata = Db::name('member_poster')->where('aid',aid)->where('mid',mid)->where('scene',$scene)->where('type','collageteam')->where('posterid',$posterset['id'])->find();
		if(!$posterdata){
			$product = Db::name('collage_product')->where('id',$post['proid'])->find();
			$sysset = Db::name('admin_set')->where('aid',aid)->find();
			$textReplaceArr = [
				'[头像]'=>$this->member['headimg'],
				'[昵称]'=>$this->member['nickname'],
				'[姓名]'=>$this->member['realname'],
				'[手机号]'=>$this->member['mobile'],
				'[商城名称]'=>$sysset['name'],
				'[商品名称]'=>$product['name'],
				'[商品销售价]'=>$product['sell_price'],
				'[商品市场价]'=>$product['market_price'],
				'[商品图片]'=>$product['pic'],
			];
            if(getcustom('yx_collage_jieti')){
                if($product['collage_type'] ==1){
                    $page = '/pagesB/collage/jtteam';
                    $scene = 'teamid_'.$post['teamid'].'-pid_'.$this->member['id'];
                }
            }
			$poster = $this->_getposter(aid,$product['bid'],$platform,$posterset['content'],$page,$scene,$textReplaceArr);
			$posterdata = [];
			$posterdata['aid'] = aid;
			$posterdata['mid'] = $this->member['id'];
			$posterdata['scene'] = $scene;
			$posterdata['page'] = $page;
			$posterdata['type'] = 'collageteam';
			$posterdata['poster'] = $poster;
			$posterdata['createtime'] = time();
			Db::name('member_poster')->insert($posterdata);
		}
		return $this->json(['status'=>1,'poster'=>$posterdata['poster']]);
	}

	//分类商品
    public function classify(){
        if(getcustom('yx_collage_classify')){
            $clist = Db::name('collage_category')->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
            foreach($clist as $k=>$v){
                $rs = Db::name('collage_category')->where('aid',aid)->where('pid',$v['id'])->where('status',1)->order('sort desc,id')->select()->toArray();
                if(!$rs) $rs = [];
                $clist[$k]['child'] = $rs;
            }
            return $this->json(['status'=>1,'data'=>$clist]);
        }
        
    }
}
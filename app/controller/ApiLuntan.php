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
class ApiLuntan extends ApiCommon
{
    public function initialize(){
		parent::initialize();
		$bset = Db::name('luntan_sysset')->where('aid',aid)->find();
		if($bset['status'] == 0){
			die(jsonEncode(['status'=>-3,'url'=>'/pages/index/index']));
		}
	}
	public function index(){
		$sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['status','=',1];
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
        $datalist = Db::name('luntan')->where($where)->page($pagenum,$pernum)->order('is_top desc,id desc')->select()->toArray();
        if(!$datalist) $datalist = array();

		foreach($datalist as $k=>$v){
			$datalist[$k]['plcount'] = Db::name('luntan_pinglun')->where('sid',$v['id'])->where('status',1)->count();
			//是否点赞
			$zanlog = Db::name('luntan_zanlog')->where('sid',$v['id'])->where('mid',mid)->find();
			if($zanlog){
				$datalist[$k]['iszan'] = 1;
			}else{
				$datalist[$k]['iszan'] = 0;
			}
			$datalist[$k]['showtime'] = $this->getshowtime($v['createtime']);
			if($v['pics']){
				$datalist[$k]['pics'] = explode(',',$v['pics']);
			}
			$datalist[$k]['isshowphone'] = false;
			if(getcustom('luntan_category_phone')){
				$cate = Db::name('luntan_category')->field('isshowphone')->where('aid',aid)->where('id',$v['cid'])->find();
				$datalist[$k]['isshowphone'] = $cate['isshowphone']?true:false;
			}
			if(!$v['mid']){
				$datalist[$k]['nickname'] = $this->sysset['name'];
				$datalist[$k]['headimg'] = $this->sysset['logo'];
			}
            if(getcustom('luntan_content_ueditor')){
                $datalist[$k]['luntan_content_ueditor'] = true;
            }

            $luntan_form_status = false;
			if(getcustom('luntan_form')){
	            $admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
	            if($admin_user['auth_type'] !=1){
	                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
	                if($admin_user['groupid']){
	                    $admin_auth = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
	                }
	                if($admin_auth && in_array('LuntanCategory/form,LuntanCategory/form',$admin_auth)){
	                    $luntan_form_status = true;
	                }
	            }else{
	            	$luntan_form_status = true;
	            }
			}

            if($luntan_form_status){
                $form_order = Db::name('form_order')->where('aid',aid)->where('luntanid',$v['id'])->find();
                $form = Db::name('form')->where('aid',aid)->where('id',$form_order['formid'])->find();
                if($form){
                    $form_order = $this->getFormInfo($form,$form_order);
                }
                $datalist[$k]['form'] = $form_order;
                    
            }

		}
		if($pagenum!=1){
			return $this->json(['status'=>1,'datalist'=>$datalist]);
		}
		$clist = Db::name('luntan_category')->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();

		$rdata = [];
		$rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
		$rdata['sysset'] = $sysset;
		$rdata['title'] = $sysset['title'];
		$rdata['clist'] = $clist;
		if(getcustom('luntan_call')){
			$rdata['need_call'] = true;
		}
        if(getcustom('luntan_category_phone_other')){
			$rdata['isphoneother'] = true;
		}
		return $this->json($rdata);
	}

	public function ltlist(){
		$sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
		$where = [];
		$where[] = ['l.aid','=',aid];
		$where[] = ['l.status','=',1];

		$luntan_form_status = false;
		if(getcustom('luntan_form')){

			$admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user['auth_type'] !=1){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if($admin_user['groupid']){
                    $admin_auth = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                }
                if($admin_auth && in_array('LuntanCategory/form,LuntanCategory/form',$admin_auth)){
                    $luntan_form_status = true;
                }
            }else{
            	$luntan_form_status = true;
            }

		}

		if(input('param.keyword')){
			if($luntan_form_status){
				$where[] = ['l.content|l.nickname|p.content|p.nickname|o.form0|o.form1|o.form2|o.form3|o.form4|o.form5|o.form6|o.form7|o.form8|o.form9|o.form10','like','%'.input('param.keyword').'%'];
			}else{
				$where[] = ['l.content|l.nickname','like','%'.input('param.keyword').'%'];
			}
			
		}
		$title = $sysset['title'];
		$banner = '';
		if(input('param.cid')){
			$where[] = ['l.cid','=',input('param.cid')];
			$cdata = Db::name('luntan_category')->where('aid',aid)->where('id',input('param.cid'))->find();
			$title = $cdata['name'];
			$banner = $cdata['banner'];
		}
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		
		if($luntan_form_status){
			if(input('param.keyword')){
				$luntanids = Db::name('luntan')->alias('l')
					->join('luntan_pinglun p','p.sid=l.id','LEFT')
					->join('form_order o','o.luntanid=l.id','LEFT')
					->where($where)
					->page($pagenum,$pernum)
					->field('l.*')
					->order('l.id desc')
					->column('l.id');

		        $luntanids = array_unique($luntanids);
		        $datalist = Db::name('luntan')->where('aid',aid)->where('id','in',$luntanids)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			}else{
				$datalist = Db::name('luntan')->alias('l')
					->join('form_order o','o.luntanid=l.id','LEFT')
					->where($where)
					->page($pagenum,$pernum)
					->field('l.*')
					->order('l.id desc')
					->select()
					->toArray();
			}
		}else{
			$datalist = Db::name('luntan')->alias('l')->where($where)->page($pagenum,$pernum)->order('l.id desc')->select()->toArray();
		}

		if(!$datalist) $datalist = array();
		foreach($datalist as $k=>$v){
			$datalist[$k]['plcount'] = Db::name('luntan_pinglun')->where('sid',$v['id'])->where('status',1)->count();
			//是否点赞
			$zanlog = Db::name('luntan_zanlog')->where('sid',$v['id'])->where('mid',mid)->find();
			if($zanlog){
				$datalist[$k]['iszan'] = 1;
			}else{
				$datalist[$k]['iszan'] = 0;
			}
			$datalist[$k]['showtime'] = $this->getshowtime($v['createtime']);
			if($v['pics']){
				$datalist[$k]['pics'] = explode(',',$v['pics']);
			}
			$datalist[$k]['isshowphone'] = false;
			if(getcustom('luntan_category_phone')){
				$cate = Db::name('luntan_category')->field('isshowphone')->where('aid',aid)->where('id',$v['cid'])->find();
				$datalist[$k]['isshowphone'] = $cate['isshowphone']?true:false;
			}

			if($luntan_form_status){
                $form_order = Db::name('form_order')->where('aid',aid)->where('luntanid',$v['id'])->find();
                $form = Db::name('form')->where('aid',aid)->where('id',$form_order['formid'])->find();
                if($form){
                    // foreach($formcontent as $k2=>$v2){
					// 	if($v2['key'] == 'upload_pics'){
					// 		$pics = $form_order['form'.$k2];
					// 		if($pics){
					// 			$form_order['form'.$k2] = explode(",",$pics);
					// 		}
					// 	}
					// }
					$form_order = $this->getFormInfo($form,$form_order);
                }
                $datalist[$k]['form'] = $form_order;
                    
            }
		}
		if($pagenum!=1){
			return $this->json(['status'=>1,'datalist'=>$datalist]);
		}
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['cid'] = input('param.cid');
		$rdata['title'] = $title;
		$rdata['banner'] = $banner;
		$rdata['datalist'] = $datalist;
		$rdata['sysset'] = $sysset;
		return $this->json($rdata);
	}
	
	public function fatie(){
		$this->checklogin();
		$sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
		$sendtj = explode(',',$sysset['sendtj']);
		if(!in_array('-1',$sendtj) && !in_array($this->member['levelid'],$sendtj)){ //不是所有人
			if(in_array('0',$sendtj)){ //关注用户才能领
				if($this->member['subscribe']!=1){
					$appinfo = getappinfo(aid,'mp');
					return $this->fetch('guanzhu',['img'=>$appinfo['qrcode'],'msg'=>'请先关注'.$appinfo['nickname'].'公众号']);
				}
			}else{
				return $this->json(['status'=>0,'msg'=>'您没有发帖权限']);
			}
		}
        if($sysset['sendcheck']==2){
            return $this->json(['status'=>0,'msg'=>'用户不可发帖']);
        }
        $luntan_form_status = false;
		if(getcustom('luntan_form')){
			$admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user['auth_type'] !=1){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if($admin_user['groupid']){
                    $admin_auth = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                }
                if($admin_auth && in_array('LuntanCategory/form,LuntanCategory/form',$admin_auth)){
                    $luntan_form_status = true;
                }
            }else{
            	$luntan_form_status = true;
            }
		}

		if(request()->isPost()){
			$title = input('post.title');
			$content = input('post.content');
			$pics = input('post.pics');
			$video = input('post.video');
			$cid = input('post.cid');
			$data = [];
			$data['aid'] = aid;
			$data['cid'] = $cid;
			if(getcustom('luntan_second_category')){
				//查询他的二级
				$cidlist2 =  Db::name('luntan_category')->where('pid',$cid)->where('aid',aid)->where('status',1)->column('id');
				$cid2 = input('post.cid2')?input('cid2/d'):0;
				if($cidlist2){
					if(!$cid2){
						return $this->json(['status'=>0,'msg'=>'请选择二级分类']);
					}
					if(!in_array($cid2,$cidlist2)){
						return $this->json(['status'=>0,'msg'=>'二级分类不存在或已关闭']);
					}
					if($cid2){
						$data['cid'] = $cid2;
					}
				}
			}
			$data['mid'] = mid;
			$data['content'] = $content;
			$data['pics'] = $pics;
			$data['video'] = $video;
			$data['createtime'] = time();
			$data['headimg'] = $this->member['headimg'];
			$data['nickname'] = $this->member['nickname'];
			if($sysset['sendcheck']==1){//需要审核
				$data['status'] = 0;
				$msg = '提交成功，请等待审核';
			}else{
				$data['status'] = 1;
				$msg = '发布成功';
			}
			if(getcustom('luntan_call')){
				$data['mobile'] = input('post.mobile')?input('post.mobile'):'';
			}
			if(getcustom('luntan_category_phone')){
				$data['mobile'] = input('post.mobile')?input('post.mobile'):'';
				$data['name'] = input('post.name')?input('post.name'):'';
			}
            if(getcustom('luntan_category_phone_other')){
                $data['qq'] = input('post.qq')?input('post.qq'):'';
                $data['wechat'] = input('post.wechat')?input('post.wechat'):'';
            }

			if(getcustom('luntan_category_give_coupon')){
				$cate =  Db::name('luntan_category')->where('id',$cid)->where('aid',aid)->where('status',1)->find();
				//查看赠送次数
				$givecount = 0+Db::name('luntan_give_couponlog')->where('cateid',$cid)->where('aid',aid)->where('mid',mid)->count();
				if(time()>$cate['starttime'] && time()<$cate['endtime'] && $cate['coupon_ids']){
					if(($cate['limitnum'] >0 && $givecount<$cate['limitnum']) || !$cate['limitnum']){
						$couponids = explode(',',$cate['coupon_ids']);
						foreach($couponids as $coupon){
							\app\common\Coupon::send(aid,mid,$coupon,false,0);
						}
						//新增赠送记录
						$zslog = [];
						$zslog['aid'] = aid;
						$zslog['mid'] = mid;
						$zslog['createtime'] = time();
						$zslog['cateid'] = $cid;
						$zslog['couponid'] = $cate['coupon_ids'];
						Db::name('luntan_give_couponlog')->insert($zslog);
					}
				}
            }
            if(getcustom('luntan_pay_top')){
                $topselected = input('post.topselected/d',-1);
                if($topselected >= 0){
                    $topOptions = Db::name('luntan_category')->where('id',$cid)->where('aid',aid)->where('status',1)->value('top_options');
                    $topOptions = json_decode($topOptions,true);
                    $topOptionsData = $topOptions[$topselected] ?? [];
                    if($topOptionsData){
                        $data['status'] = 3;//待支付
                    }
                }
            }
			$id = Db::name('luntan')->insertGetId($data);
			if(getcustom('ext_give_score')){
			    if($data['status']==1){
                    \app\model\Score::extGiveScore(aid,$this->mid,'luntan',$id,'add');
                }
            }
			
			$formid = input('param.formid');
			
			if($luntan_form_status && $formid){

				$formstatus = 1;
				$form = Db::name('form')->where('aid',aid)->where('id',$formid)->find();
				if($form){
					if(strtotime($form['starttime']) > time()){
						$formstatus = 0;
					}
					if(strtotime($form['endtime']) < time()){
						$formstatus = 0;
					}
					if($form['maxlimit'] > 0){
						$count = 0 + Db::name('form_order')->where('formid',$form['id'])->count();
						if($count >= $form['maxlimit']){
							$formstatus = 0;
						}
					}
					$mycs = 0 + Db::name('form_order')->where('formid',$form['id'])->where('mid',mid)->count();
					if($form['perlimit'] > 0 && $mycs >= $form['perlimit']){
						$formstatus = 0;
					}

					if($formstatus){
						$data =[];
						$data['aid'] = aid;
						$data['bid'] = $form['bid'];
						$data['formid'] = $form['id'];
						$data['title'] = $form['name'];
						$data['mid'] = mid;
						$data['createtime'] = time();

						$formcontent = json_decode($form['content'],true);

						$formdata = input('param.formdata');
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
							if($v['val3']==1 && $data['form'.$k]==='' && !$v['linkitem']){
								return $this->json(['status'=>0,'msg'=>$v['val1'].' 必填']);
							
							}
						}
						$ordernum = date('ymdHis').aid.rand(1000,9999);
						$data['money'] = 0;
						$data['ordernum'] = $ordernum;
						$data['fromurl'] = input('param.fromurl');
						$data['luntanid'] = $id;
						$data['status'] = 1;
						Db::name('form_order')->insert($data);
					}
				}
			}
            if(getcustom('luntan_pay_top') && isset($topOptionsData)){
                //创建支付订单
                $ordernum = \app\common\Common::generateOrderNo(aid);
                $orderdata = [];
                $orderdata['aid'] = aid;
                $orderdata['mid'] = mid;
                $orderdata['luntan_id'] = $id;
                $orderdata['title'] = '论坛置顶'.$topOptionsData['hour'].'小时';
                $orderdata['ordernum'] = $ordernum['ordernum'];
                $orderdata['product_price'] = $topOptionsData['price'];
                $orderdata['totalprice'] = $topOptionsData['price'];
                $orderdata['createtime'] = time();
                $orderdata['top_hour'] = $topOptionsData['hour'];
                $orderdata['top_expire_time'] = time() + $topOptionsData['hour']*60*60;
                $orderid = Db::name('luntan_order')->insertGetId($orderdata);
                $payorderid = \app\model\Payorder::createorder(aid,0,$orderdata['mid'],'luntan',$orderid,$ordernum,$orderdata['title'],$orderdata['totalprice']);
                return $this->json(['status'=>2,'msg'=>'提交成功','payorderid'=>$payorderid]);
            }

			return $this->json(['status'=>1,'msg'=>$msg]);
		}
		$clist = Db::name('luntan_category')->where('aid',aid)->where('pid',0)->where('status',1)->order('sort desc,id')->select()->toArray();
		if(getcustom('luntan_second_category')){
			$display_type = input('display_type');
			if($display_type>=0){
				$clist = Db::name('luntan_category')->where('aid',aid)->where('pid',0)->where('status',1)->where('display_type',$display_type)->order('sort desc,id')->select()->toArray();
			}
		}
		$iscatephone = false;
		if(getcustom('luntan_category_phone')){
			$iscatephone = true;
		}	

		$rdata = [];
		$rdata['clist'] = $clist;
		if(getcustom('luntan_call')){
			$rdata['need_call'] = true;
		}
		if(getcustom('luntan_second_category')){
			$rdata['cate2'] = true;
			$rdata['cateArr2'] = [];
		}
        if(getcustom('luntan_category_phone_other')){
            $rdata['isphoneother'] = 1;
        }
		$rdata['iscatephone'] = $iscatephone;
		$rdata['isform'] = false;
		if($luntan_form_status){
			$rdata['isform'] = true;
		}
        if(getcustom('luntan_custom_input_tips')){
            $rdata['input_tips'] = $sysset['input_tips'];
        }
		return $this->json($rdata);
	}
	// 分类表单
	public function getform(){
		$luntan_form_status = false;
		if(getcustom('luntan_form')){
			$admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user['auth_type'] !=1){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if($admin_user['groupid']){
                    $admin_auth = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                }
                if($admin_auth && in_array('LuntanCategory/form,LuntanCategory/form',$admin_auth)){
                    $luntan_form_status = true;
                }
            }else{
            	$luntan_form_status = true;
            }
		}
		if($luntan_form_status){
			$cid = input('param.cid/d');
			$category = Db::name('luntan_category')->where('aid',aid)->where('id',$cid)->find();
			if($category['formid']){
				$form = Db::name('form')->where('aid',aid)->where('id',$category['formid'])->find();

				if(strtotime($form['starttime']) > time()){
					return $this->json(['status'=>0,'msg'=>'活动未开始']);
				}
				if(strtotime($form['endtime']) < time()){
					return $this->json(['status'=>0,'msg'=>'活动已结束']);
				}
				if($form['maxlimit'] > 0){
					$count = 0 + Db::name('form_order')->where('formid',$form['id'])->count();
					if($count >= $form['maxlimit']){
						return $this->json(['status'=>0,'msg'=>'提交人数已满']);
					}
				}
				$mycs = 0 + Db::name('form_order')->where('formid',$form['id'])->where('mid',mid)->count();
				if($form['perlimit'] > 0 && $mycs >= $form['perlimit']){
					return $this->json(['status'=>0,'msg'=>$form['perlimit']==1?'您已经提交过了':'每人最多可提交'.$form['perlimit'].'次']);
				}

		        //判断表单是否超出范围
		        if($form['fanwei'] == 1){
		            if(empty($post['longitude']) || empty($post['latitude'])){
		                return $this->json(['status'=>0,'msg'=>'请定位您的位置或者刷新重试']);
		            }
		            $juli = getdistance($post['longitude'],$post['latitude'],$form['fanwei_lng'],$form['fanwei_lat'],1);
		            if($juli > $form['fanwei_range']){
		                return $this->json(['status'=>0,'msg'=>'请在指定范围内使用']);
		            }
		        }

				$category['formcontent'] = json_decode($form['content'],true);

			}
			return $this->json($category);
		}
	}

	public function detail(){
		$id = input('param.id/d');
		$detail = Db::name('luntan')->where('id',$id)->where('status',1)->find();
		if(!$detail) return $this->json(['status'=>0,'msg'=>'帖子已删除']);
		Db::name('luntan')->where('id',input('param.id/d'))->where('aid',aid)->inc('readcount')->update();
		$detail['readcount']++;
		$detail['showtime'] = $this->getshowtime($detail['createtime']);
		if($detail['pics']){
			$detail['pics'] = explode(',',$detail['pics']);
		}

		$detail['isshowphone'] = false;
		if(getcustom('luntan_category_phone')){
			$cate = Db::name('luntan_category')->field('isshowphone')->where('aid',aid)->where('id',$detail['cid'])->find();
			$detail['isshowphone'] = $cate['isshowphone']?true:false;
		}
		//评论
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('luntan_pinglun')->where('sid',$id)->where('status',1)->page($pagenum,$pernum)->order('createtime desc')->select()->toArray();
		if(!$datalist) $datalist = array();
		foreach($datalist as $k=>$v){
			$rs = Db::name('luntan_pzanlog')->where('pid',$v['id'])->where('mid',mid)->find();
			if($rs){
				$v['iszan'] = 1;
			}else{
				$v['iszan'] = 0;
			}
			//回复
			$replylist = Db::name('luntan_pinglun_reply')->field('id,mid,nickname,headimg,content,createtime')->where('pid',$v['id'])->where('status',1)->order('createtime')->select()->toArray();
			foreach($replylist as $k2=>$v2){
				$v2['createtime'] = $this->getshowtime($v2['createtime']);
				$v2['content'] = getshowcontent($v2['content']);
				$replylist[$k2] = $v2;
			}
			$v['replylist'] = $replylist;
			$v['content'] = nl2br(getshowcontent($v['content']));
			$v['createtime'] = $this->getshowtime($v['createtime']);
			$datalist[$k] = $v;
		}
		$plcount = Db::name('luntan_pinglun')->where('sid',$id)->where('status',1)->count();
		//是否点赞
		$zanlog = Db::name('luntan_zanlog')->where('sid',$detail['id'])->where('mid',mid)->find();
		if($zanlog){
			$iszan = 1;
		}else{
			$iszan = 0;
		}
        if(getcustom('luntan_content_ueditor')){
            $detail['luntan_content_ueditor'] = true;
        }
		$rdata = [];
		$rdata['mid'] = mid;
		if(!$detail['mid']){
			$admininfo = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
			$detail['nickname'] = $admininfo['name'];
			$detail['headimg'] = $admininfo['logo'];
		}
		$rdata['datalist'] = $datalist;
		$rdata['plcount'] = $plcount;
		$rdata['iszan'] = $iszan;
		$rdata['status'] = 1;
		$rdata['detail'] = $detail;
		if(getcustom('luntan_call')){
			$rdata['need_call'] = true;
		}
		$rdata['pinglunstatus'] = true;	
		if(getcustom('luntan_pingluntj')){
			$pingluntj = Db::name('luntan_sysset')->where('aid',aid)->value('pingluntj');
			$pingluntj = explode(',',$pingluntj);

			if(!in_array('-1',$pingluntj) && !in_array($this->member['levelid'],$pingluntj)){
				$rdata['pinglunstatus'] = false;	
			}
		}
		$luntan_form_status = false;
		if(getcustom('luntan_form')){
			$admin_user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user['auth_type'] !=1){
                $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                if($admin_user['groupid']){
                    $admin_auth = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                }
                if($admin_auth && in_array('LuntanCategory/form,LuntanCategory/form',$admin_auth)){
                    $luntan_form_status = true;
                }
            }else{
            	$luntan_form_status = true;
            }
		}

		if($luntan_form_status){
            $form_order = Db::name('form_order')->where('aid',aid)->where('luntanid',$detail['id'])->find();
            $form = Db::name('form')->where('aid',aid)->where('id',$form_order['formid'])->find();
            if($form){
                $form_order = $this->getFormInfo($form,$form_order);
            }
            $rdata['form'] = $form_order;
                
        }

		return $this->json($rdata);
	}
	//点赞
	public function zan(){
		$this->checklogin();
		$id = input('post.id/d');
		$detail = Db::name('luntan')->where('id',$id)->find();
		$zanlog = Db::name('luntan_zanlog')->where('sid',$id)->where('mid',mid)->find();
		if($zanlog){
			Db::name('luntan_zanlog')->where('sid',$id)->where('mid',mid)->delete();
			$type = 0;
			Db::name('luntan')->where('id',$id)->dec('zan')->update();
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['sid'] = $id;
			$data['mid'] = mid;
			$data['createtime'] = time();
			Db::name('luntan_zanlog')->insert($data);
			$type = 1;
			Db::name('luntan')->where('id',$id)->inc('zan')->update();
		}
		$zancount = Db::name('luntan')->where('id',$id)->value('zan');
		return $this->json(['status'=>1,'type'=>$type,'zancount'=>$zancount]);
	}
	//评论
	public function subpinglun(){
		$this->checklogin();
		$id = input('param.id/d');
		$type = input('param.type/d');
		$hfid = input('param.hfid/d');
		$content = trim(input('param.content'));
		if(!$id){
			return $this->json(['status'=>0,'msg'=>'参数错误']);
		}
		$detail = Db::name('luntan')->where('id',$id)->where('status',1)->find();
		//if($detail['canpl']==0) return $this->json(['status'=>0,'msg'=>'评论功能未开启']);
		//if($hfid && $detail['canplrp']==0) return $this->json(['status'=>0,'msg'=>'评论回复功能未开启']);

		if($content==''){
			return $this->json(['status'=>1,'msg'=>'请输入评论内容']);
		}
		$len = mb_strlen($content,'UTF-8');
		if($len > 1000){
			return $this->json(['status'=>0,'msg'=>'请输入1000字以内的评论内容']);
		}

		$sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
		if($type==0){
			$data = [];
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['sid'] = $id;
			$data['headimg'] = $this->member['headimg'];
			$data['nickname'] = $this->member['nickname'];
			$data['content'] = $content;
			$data['createtime'] = time();
			if($sysset['pingluncheck']==1){
				$data['status'] = 0;
				$msg = '提交成功，请等待审核';
			}else{
				$data['status'] = 1;
				$msg = '发表评论成功';
			}
			Db::name('luntan_pinglun')->insert($data);
            if(getcustom('ext_give_score')){
                if($data['status']==1){
                    \app\model\Score::extGiveScore(aid,$this->mid,'luntan',$id,'pinglun');
                }
            }
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['sid'] = $id;
			$data['pid'] = $hfid;
			$data['headimg'] = $this->member['headimg'];
			$data['nickname'] = $this->member['nickname'];
			$data['content'] = $content;
			$data['createtime'] = time();
			if($sysset['pingluncheck']==1){
				$data['status'] = 0;
				$msg = '提交成功，请等待审核';
			}else{
				$data['status'] = 1;
				$msg = '发表评论成功';
			}
			Db::name('luntan_pinglun_reply')->insert($data);
		}
		return $this->json(['status'=>1,'msg'=>$msg,'url'=>true]);
	}
	//评论点赞
	public function pzan(){
		$this->checklogin();
		$id = input('post.id/d');
		$pinglun = Db::name('luntan_pinglun')->where('id',$id)->find();
		$zanlog = Db::name('luntan_pzanlog')->where('pid',$id)->where('mid',mid)->find();
		if($zanlog){
			Db::name('luntan_pzanlog')->where('pid',$id)->where('mid',mid)->delete();
			$type = 0;
			Db::name('luntan_pinglun')->where('id',$id)->dec('zan')->update();
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['pid'] = $id;
			$data['mid'] = mid;
			$data['createtime'] = time();
			Db::name('luntan_pzanlog')->insert($data);
			$type = 1;
			Db::name('luntan_pinglun')->where('id',$id)->inc('zan')->update();
		}
		$zancount = Db::name('luntan_pinglun')->where('id',$id)->value('zan');
		return $this->json(['status'=>1,'type'=>$type,'zancount'=>$zancount]);
	}
	//删除
	public function deltie(){
		$this->checklogin();
		$id = input('param.id/d');
		$detail = Db::name('luntan')->where('aid',aid)->where('id',$id)->find();
		if($detail['mid']!=mid){
			return $this->json(['status'=>0,'msg'=>'无权限操作']);
		}
		Db::name('luntan')->where('aid',aid)->where('id',$id)->delete();
		Db::name('luntan_pinglun')->where('aid',aid)->where('sid',$id)->delete();
		Db::name('luntan_pinglun_reply')->where('aid',aid)->where('sid',$id)->delete();

		// 同步删除表单
		if(getcustom('luntan_form')){
			Db::name('form_order')->where('aid',aid)->where('luntanid',$id)->delete();
		}
		return $this->json(['status'=>1,'msg'=>'删除成功']);
	}
	//删除评论
	public function delpinglun(){
		$this->checklogin();
		$id = input('param.id/d');
		$pinglun = Db::name('luntan_pinglun')->where('aid',aid)->where('id',$id)->find();
		if($pinglun['mid']!=mid){
			return $this->json(['status'=>0,'msg'=>'无权限操作']);
		}
		Db::name('luntan_pinglun')->where('aid',aid)->where('id',$id)->delete();
		Db::name('luntan_pinglun_reply')->where('aid',aid)->where('pid',$id)->delete();
		return $this->json(['status'=>1,'msg'=>'删除成功','url'=>true]);
	}
	//删除回复
	public function delplreply(){
		$this->checklogin();
		$id = input('param.id/d');
		$plreply = Db::name('luntan_pinglun_reply')->where('aid',aid)->where('id',$id)->find();
		if($plreply['mid']!=mid){
			return $this->json(['status'=>0,'msg'=>'无权限操作']);
		}
		Db::name('luntan_pinglun_reply')->where('aid',aid)->where('id',$id)->delete();
		return $this->json(['status'=>1,'msg'=>'删除成功','url'=>true]);
	}

	
	//显示时间
	private function getshowtime($time){
		if(time() - $time < 60){
			return '刚刚';
		}elseif(time() - $time < 3600){
			$minite = ceil((time() - $time)/60);
			return $minite.'分钟前';
		}elseif(date('Ymd')==date('Ymd',$time)){
			return date('H:i',$time);
		}elseif(time()-$time<86400){
			return '昨天 '.date('H:i',$time);
		}elseif(date('Y')==date('Y',$time)){
			return date('m-d H:i',$time);
		}else{
			return date('Y-m-d H:i',$time);
		}
	}

	//个人发帖记录
	public function fatielog(){
		if(request()->isPost()){
			$pagenum = input('post.pagenum');
			if(!$pagenum) $pagenum = 1;
			$pernum = 20;
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['mid','=',mid];
			$where[] = ['status','=',1];
			$datalist = Db::name('luntan')
				->where($where)
				->field('id,content,pics,createtime')
				->page($pagenum,$pernum)
				->order('is_top desc,id desc')
				->select()
				->toArray();
			if(!$datalist){
				$datalist = [];
			}else{
				foreach($datalist as &$v){
					$v['pic'] = '';
					if($v['pics']){
						$v['pics'] = explode(',',$v['pics']);
						$v['pic']  = $v['pics'][0];
					}
					$v['createtime'] = date("Y-m-d H:i:s",$v['createtime']);
				}
				unset($v);
			}
			return $this->json(['status'=>1,'data'=>$datalist] );
		}
	}

	//个人关注记录
	public function focuslog(){
		if(request()->isPost()){
			$pagenum = input('post.pagenum');
			if(!$pagenum) $pagenum = 1;
			$pernum = 20;
			$datalist = Db::name('luntan_zanlog')
				->alias('lz')
				->join('luntan l','l.id = lz.sid')
				->where('lz.mid',mid)
				->where('lz.aid',aid)
				->page($pagenum,$pernum)
				->order('lz.id desc')
				->field('lz.id,lz.sid,l.content,l.pics,lz.createtime')
				->select()
				->toArray();
			if(!$datalist){
				$datalist = [];
			}else{
				foreach($datalist as &$v){
					$v['pic'] = '';
					if($v['pics']){
						$v['pics'] = explode(',',$v['pics']);
						$v['pic']  = $v['pics'][0];
					}
					$v['createtime'] = date("Y-m-d H:i:s",$v['createtime']);
				}
				unset($v);
			}
			return $this->json(['status'=>1,'data'=>$datalist] );
		}
	}

	public function class(){
		if(getcustom('luntan_second_category')){
			if(request()->isPost()){
				
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$pernum = 20;
				$clist = Db::name('luntan_category')
					->where('aid',aid)
					->where('pid',0)
					->where('display_type',0)
					->where('status',1)
					->page($pagenum,$pernum)
					->order('sort desc,id')
					->select()
					->toArray();
				if(!$clist){
					$clist = [];
				}
				$sysset = Db::name('luntan_sysset')->where('aid',aid)->field('id,title')->find();
				$title = $sysset['title'];
				return $this->json(['status'=>1,'data'=>$clist,'title'=>$title]);
			}
		}
	}
	public function class2(){
		if(getcustom('luntan_second_category')){
			if(request()->isPost()){
				
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$pernum = 20;
				$clist = Db::name('luntan_category')
					->where('aid',aid)
					->where('pid',0)
					->where('display_type',1)
					->where('status',1)
					->page($pagenum,$pernum)
					->order('sort desc,id')
					->select()
					->toArray();
				if(!$clist){
					$clist = [];
				}else{
					foreach($clist as &$cv){
						//显示他分类信息数量
						$cids = [$cv['id']];
						$cids2 = Db::name('luntan_category')
							->where('pid',$cv['id'])
							->where('aid',aid)
							->where('status',1)
							->order('sort desc,id')
							->column('id');
						if($cids2){
							$cids = array_merge($cids,$cids2);
						}
						$childlist = Db::name('luntan')->where('cid','in',$cids)->limit($cv['child_num'])->order('id desc')->select()->toArray();
						if($childlist){
							foreach($childlist as &$cdv){
								$cdv['pic'] = '';
								if($cdv['pics']){
									$picarr = explode(',',$cdv['pics']);
									if($picarr){
										$cdv['pic'] = $picarr[0];
									}
								}
							}
							unset($cdv);
							
						}
						$cv['childlist'] = $childlist;
					}
					unset($cv);
				}

				$sysset = Db::name('luntan_sysset')->where('aid',aid)->field('id,title')->find();
				$title = $sysset['title'];
				return $this->json(['status'=>1,'data'=>$clist,'title'=>$title]);
			}
		}
	}
	public function list(){
		if(getcustom('luntan_second_category')){
			if(request()->isPost()){
				$pagenum = input('post.pagenum');
				if(!$pagenum) $pagenum = 1;
				$pernum = 20;

				$pid = input('post.pid')?input('pid/d'):0;
				$display_type = input('post.display_type')?input('display_type/d'):0;
				if($pid){
					$clist = [['id'=>0,'name'=>'全部']];
					//二级分类
					$clist_arr = Db::name('luntan_category')
						->where('aid',aid)
						->where('pid',$pid)
						->where('status',1)
						->field('id,name')
						->page($pagenum,$pernum)
						->order('sort desc,id')
						->select()
						->toArray();
					if($clist_arr){
						$clist = array_merge($clist ,$clist_arr);
					}
				}else{
					$clist = [];
				}

				$sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
				$title = $sysset['title'];
				$where = [];
				$where[] = ['aid','=',aid];
				$where[] = ['status','=',1];

				if($pid){//区分分类

					if(input('param.keyword')){
						$where[] = ['content|nickname','like','%'.input('param.keyword').'%'];
					}
					$cid = input('param.cid')?input('cid/d'):0;
					if(input('param.cid')){
						$cid = input('param.cid');
						//验证二级分类是否正确
						$count = Db::name('luntan_category')->where('id',$cid)->where('pid',$pid)->where('aid',aid)->where('status',1)->count();
						if(empty($count)){
							return $this->json(['status'=>0,'msg'=>'分类不存在']);
						}
						$where[] = ['cid','=',$cid];
						$cdata  = Db::name('luntan_category')->where('aid',aid)->where('id',$cid)->field('id,name')->find();
						$title  = $cdata['name'];
					}else{
						$cids = [$pid];
						$cids2  = Db::name('luntan_category')->where('pid',$pid)->where('aid',aid)->where('status',1)->order('sort desc,id')->column('id');
						if($cids2){
							$cids = array_merge($cids,$cids2);
						}
						$where[] = ['cid','in',$cids];
					}

					$datalist = Db::name('luntan')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
					if(!$datalist) $datalist = array();
					foreach($datalist as $k=>$v){
						$datalist[$k]['plcount'] = Db::name('luntan_pinglun')->where('sid',$v['id'])->where('status',1)->count();
						//是否点赞
						$zanlog = Db::name('luntan_zanlog')->where('sid',$v['id'])->where('mid',mid)->find();
						if($zanlog){
							$datalist[$k]['iszan'] = 1;
						}else{
							$datalist[$k]['iszan'] = 0;
						}
						$datalist[$k]['showtime'] = $this->getshowtime($v['createtime']);
						if($v['pics']){
							$datalist[$k]['pics'] = explode(',',$v['pics']);
						}
					}
					if($pagenum!=1){
						return $this->json(['status'=>1,'datalist'=>$datalist]);
					}
				}else{//不区分分类 需要有关键字搜索
					
					if(input('param.keyword')){
						$where[] = ['content|nickname','like','%'.input('param.keyword').'%'];

						$cids  = Db::name('luntan_category')->where('display_type',$display_type)->where('aid',aid)->where('status',1)->column('id');
						if($cids){
							$cids2  = Db::name('luntan_category')->where('pid','in',$cids)->where('aid',aid)->where('status',1)->order('sort desc,id')->column('id');
							if($cids2){
								$cids = array_merge($cids,$cids2);
							}
						}

						if($cids){
							$where[] = ['cid','in',$cids];
						}else{
							$where[] = ['id','=',0];
						}
						
						$datalist = Db::name('luntan')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
						if(!$datalist) $datalist = array();
						foreach($datalist as $k=>$v){
							$datalist[$k]['plcount'] = Db::name('luntan_pinglun')->where('sid',$v['id'])->where('status',1)->count();
							//是否点赞
							$zanlog = Db::name('luntan_zanlog')->where('sid',$v['id'])->where('mid',mid)->find();
							if($zanlog){
								$datalist[$k]['iszan'] = 1;
							}else{
								$datalist[$k]['iszan'] = 0;
							}
							$datalist[$k]['showtime'] = $this->getshowtime($v['createtime']);
							if($v['pics']){
								$datalist[$k]['pics'] = explode(',',$v['pics']);
							}
						}
						if($pagenum!=1){
							return $this->json(['status'=>1,'datalist'=>$datalist]);
						}
					}
				}

				$rdata = [];
				$rdata['clist']  = $clist;
				$rdata['status'] = 1;
				$rdata['cid'] = input('param.cid');
				$rdata['title'] = $title;
				$rdata['banner'] = $banner;
				$rdata['datalist'] = $datalist;
				$rdata['sysset'] = $sysset;
				return $this->json($rdata);
			}
		}
	}

	public function getCate2(){
		if(getcustom('luntan_second_category')){
			if(request()->isPost()){
				$this->checklogin();
				$sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
				$sendtj = explode(',',$sysset['sendtj']);
				if(!in_array('-1',$sendtj) && !in_array($this->member['levelid'],$sendtj)){ //不是所有人
					if(in_array('0',$sendtj)){ //关注用户才能领
						if($this->member['subscribe']!=1){
							$appinfo = getappinfo(aid,'mp');
							return $this->fetch('guanzhu',['img'=>$appinfo['qrcode'],'msg'=>'请先关注'.$appinfo['nickname'].'公众号']);
						}
					}else{
						return $this->json(['status'=>0,'msg'=>'您没有发帖权限']);
					}
				}
				$pid = input('post.pid')?input('pid/d'):0;
				if(!$pid){
					return $this->json(['status'=>0,'msg'=>'请先选择一级分类']);
				}
				$clist = Db::name('luntan_category')->where('pid',$pid)->where('aid',aid)->where('status',1)->order('sort desc,id')->select()->toArray();

				return $this->json(['status'=>1,'data'=>$clist]);
			}
		}
	}
	// 论坛表单
	public function getFormInfo($form = [],$form_order = []){
		if(getcustom('luntan_form')){
			$formcontent = json_decode($form['content'],true);
	        $last_type = '';
	        $last_type_k2 = '';
	        $last_info = '';
	        $last_pic = '';
	        $last_type_pic_k2 = '';
	        foreach($formcontent as $k2=>$v2){
	        	if($v2['key'] == 'upload_pics'){
					$pics = $form_order['form'.$k2];
					if($pics){
						$form_order['form'.$k2] = explode(",",$pics);
					}
				}
				if($v2['key'] == 'upload' && $form_order['form'.$k2]){
					if($last_pic &&  $last_type == 'upload'){
						$last_pic = $last_pic.','.$form_order['form'.$k2];
	        		}else{
		        		$last_pic = $form_order['form'.$k2];
	        		}
	        		$form_order['form'.$k2] = explode(",",$last_pic);
					if(isset($form_order['form'.$last_type_pic_k2])){
	    				unset($form_order['form'.$last_type_pic_k2]);
	    			}
	    			$last_type_pic_k2 = $k2;
				}

	        	if($v2['key'] != 'upload_pics' && $v2['key'] != 'upload' && $form_order['form'.$k2]){
	        		if($last_info && $last_type != 'upload_pics' && $last_type != 'upload'){
	        			$last_info = $last_info.'，'.$form_order['form'.$k2];
	        		}else{
	        			$last_info = $form_order['form'.$k2];
	        		}
	    			
	    			$form_order['form'.$k2] = $last_info;
	    			if(isset($form_order['form'.$last_type_k2])){
	    				unset($form_order['form'.$last_type_k2]);
	    			}
	    			$last_type_k2 = $k2;
	    			
	        	}
	        	
				$last_type = $v2['key'];
				if($v2['key'] == 'upload_pics' || $v2['key'] == 'upload'){
					$last_info = '';
					$last_type_k2 = '';
				}
				if($v2['key'] != 'upload'){
					$last_pic = '';
					$last_type_pic_k2 ='';
				}
			}
			$form_order['formcontent'] = $formcontent;
			return $form_order;
		}
	}

}
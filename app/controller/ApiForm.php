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
header('Content-Type: text/html; charset=utf-8');

use PhpOffice\PhpWord\IOFactory;
use think\facade\Db;
use Mpdf\Mpdf;
use Dompdf\Dompdf;
use Dompdf\Options;

class ApiForm extends ApiCommon
{	
	//提交表单
	public function formsubmit(){
		$post = input('post.');
		//var_dump($post);
		//var_dump($post['formdata']);
		//die;
		$form = Db::name('form')->where('aid',aid)->where('id',$post['formid'])->find();
		if(getcustom('form_login')){
            if($form['need_login']){
                $this->checklogin();
            }
        }else{
            $this->checklogin();
        }
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
		
		$data =[];
		$data['aid'] = aid;
		$data['bid'] = $form['bid'];
		$data['formid'] = $form['id'];
		$data['title'] = $form['name'];
		$data['mid'] = mid;
		$data['createtime'] = time();

		//var_dump($post);
		$fromdata = $post['formdata'];
		$formcontent = json_decode($form['content'],true);
		if(getcustom('form_option_adminuser')){
			$uid = 0;//哪个管理可查看
		}
		if(getcustom('form_radio_paymoney')){
            $radiopaymoney = 0;
        }
        if(getcustom('form_select_paymoney')){
            $selectpaymoney = 0;
        }
		foreach($formcontent as $k=>$v){
			$value = $fromdata['form'.$k];
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
			if($form['payset']==1){
                if(getcustom('form_radio_paymoney')){
                    if($v['key'] == 'radio'){
                        //判断是否设置了价格
                        if(!empty($v['val19'])){
                            $pos = array_search($data['form'.$k],$v['val2']);
                            if($pos ===0 || $pos>0){
                                $paymoney = $v['val19'][$pos]?$v['val19'][$pos]:0;
                                $radiopaymoney += $paymoney;
                            }else{
                                return $this->json(['status'=>0,'msg'=>$v['val1'].'选择选项不存在']);
                            }
                        }
                    }
                }
                if(getcustom('form_select_paymoney')){
                    if($v['key'] == 'selector'){
                        //判断是否设置了价格
                        if(!empty($v['val19'])){
                            $pos = array_search($data['form'.$k],$v['val2']);
                            if($pos ===0 || $pos>0){
                                $paymoney = $v['val19'][$pos]?$v['val19'][$pos]:0;
                                $selectpaymoney += $paymoney;
                            }else{
                                return $this->json(['status'=>0,'msg'=>$v['val1'].'选择选项不存在']);
                            }
                        }
                    }
                }
            }

			if(getcustom('form_option_adminuser')){
				//普通选项绑定管理员
				if(!$form['bid'] && $v['key'] == 'selector' && $v['val18'] == 1 && $form['uk'] == $k){
					$options = $v['val2'];
					if($options){
						$uk = -1;
						foreach($options as $ok=>$ov){
							if($ov == $value){
								$uk = $ok;
							}
						}
						//如果有对应的选项，则找对应的管理员
						if($uk>=0 && $v['val17']){
							$uid = $v['val17'][$uk];
						}
					}
				}
				$data['uid'] = $uid;
			}
		}
		if(getcustom('form_map')){
            $data['adr_lon'] = $fromdata['adr_lon'];
            $data['adr_lat'] = $fromdata['adr_lat'];
        }
        if(getcustom('form_custom_number')){
            //查询已提表单的数量
            $form_count = 0 + Db::name('form_order')->where('aid',aid)->where('formid',$form['id'])->count();
            if($form['custom_number'] > 0){
                $form_count = $form_count+ $form['custom_number'];
            }
            $form_count = $form_count==0?1:$form_count;
            $data['custom_number'] = $form_count; 
        }
		$price = 0;
		if($form['payset']==1){
		    $is_other_fee = 0;
            if(getcustom('form_other_money')){
                if($form['fee_items']){
                    $is_other_fee = 1;
                    $feedata = input('post.feedata/a',[]);
                    if(empty($feedata)){
                        return $this->json(['status'=>0,'msg'=>'请选择费用明细']);
                    }
                    foreach ($feedata as $ik=>$iv){
                        $price = $price + $iv['money'];
                    }
                    $data['fee_items'] = json_encode($feedata);
                }
            }
            if($is_other_fee==0){
                if($form['priceedit']==1){
                    $price = $post['price'];
                }else{
                    $price = $form['price'];
                }
            }
            if(getcustom('form_radio_paymoney')){
                $price += $radiopaymoney;
            }
            if(getcustom('form_select_paymoney')){
                $price += $selectpaymoney;
            }
		}

        if(getcustom('yx_order_discount_rand')){
            //随机立减
            $order_discount_rand = Db::name('order_discount_rand')->where('aid',aid)->where('bid',$data['bid'])->find();
            if($order_discount_rand['status'] == 1 && $price >= $order_discount_rand['order_price_min'] && $order_discount_rand['money_min'] < $price){
                $order_discount_status = true;
                $order_discount_rand['order_types'] = explode(',',$order_discount_rand['order_types']);
                if(!in_array('all',$order_discount_rand['order_types']) && !in_array('form',$order_discount_rand['order_types'])){
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
                    if($order_discount_rand['money_max'] > $price) $order_discount_rand['money_max'] = $price;
                    $order_discount = mt_rand($order_discount_rand['money_min']*100,$order_discount_rand['money_max']*100)/100;
                    $price -= $order_discount;
                    $data['discount_rand_money'] = $order_discount ?? 0;	//随机立减
                }
            }
        }

		$ordernum = date('ymdHis').aid.rand(1000,9999);
		$data['money'] = $price;
		$data['ordernum'] = $ordernum;
		$data['fromurl'] = $post['fromurl'];
        if(getcustom('form_hxqrcode') && $form['hxqrcode_status']){
            //核销码
            $data['hexiao_code'] = random(16);
            $data['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=form&co='.$data['hexiao_code']));
        }

        if(getcustom('form_sign_pdf')){
            if($form['is_contract'] == 1 && $form['contract_template']){
                if($form['is_contract_sign'] == 1 && !$post['htsignatureurl']){
                    return $this->json(['status'=>0,'msg'=>'请先签名']);
                }
                $hturl = $this->generate_contract($post['htsignatureurl'],$ordernum,$form,$fromdata);
                $data['contract_file'] = $hturl;
            }
            $data['signatureurl'] = $post['htsignatureurl'] ?? '';
        }

		if(getcustom('article_portion') || getcustom('form_edit')){
			$edit_id = $post['edit_id']?$post['edit_id']:0;
			if($edit_id){
				if(!$form['edit_status']){
					return $this->json(['status'=>0,'msg'=>'此表单暂未开启修改编辑功能']);
				}
				$detail = Db::name('form_order')->where('aid',aid)->where('id',$edit_id)->where('mid',mid)->find();
				if(!$detail){
					return $this->json(['status'=>0,'msg'=>'提交失败，数据不存在']);
				}
				unset($data['aid']);
				unset($data['bid']);
				unset($data['formid']);
				unset($data['mid']);
				unset($data['createtime']);
				unset($data['ordernum']);
				unset($data['fromurl']);

				$price = $detail['money'];
				if($detail['paystatus']==1){
					$price=0;
				}
				if(getcustom('form_edit')){
					$data['status'] = 0;
				}
				$up = Db::name('form_order')->where('id',$edit_id)->update($data);
				if(!$up){
					return $this->json(['status'=>0,'msg'=>'提交失败']);
				}
				$orderid = $edit_id;
			}else{
				$orderid = Db::name('form_order')->insertGetId($data);
			}
		}else{
			$orderid = Db::name('form_order')->insertGetId($data);
		}
		//订阅消息
		$tmplids = [];
		if(platform == 'wx'){
			$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
			if($wx_tmplset['tmpl_shenhe_new']){
				$tmplids[] = $wx_tmplset['tmpl_shenhe_new'];
			}elseif($wx_tmplset['tmpl_shenhe']){
				$tmplids[] = $wx_tmplset['tmpl_shenhe'];
			}
		}

		if(!$orderid) return $this->json(['status'=>0,'msg'=>'提交失败','tmplids'=>$tmplids]);
		if(getcustom('form_print')) {
			//自动打印
			if($form['print_status'] == 1){
    			\app\common\Wifiprint::print2(aid,$orderid);
			}
    	}

        if(getcustom('form_notice_member') && $form['form_notice_mids']){
            $form_notice_mids = explode(',', $form['form_notice_mids']);
            $form_notice_mids = array_unique($form_notice_mids);
            foreach($form_notice_mids as $notice_mid){
                $tmplcontent['first'] = '有客户提交表单成功';
                $tmplcontent['remark'] = '点击查看详情~';
                $tmplcontent['keyword1'] = $form['name'];
                $tmplcontent['keyword2'] = date('Y-m-d H:i');
                $tempconNew = [];
                $tempconNew['thing3'] = $form['name'];//报名名称
                $tempconNew['time5'] = date('Y-m-d H:i');//申请时间
                \app\common\Wechat::sendtmpl(aid,$notice_mid,'tmpl_formsub',$tmplcontent,m_url('admin/form/formdetail?id='.$orderid),$tempconNew);
            }
        }

		if($price > 0){
			$payorderid = \app\model\Payorder::createorder(aid,$data['bid'],$data['mid'],'form',$orderid,$data['ordernum'],$data['title'],$data['money']);
			return $this->json(['status'=>2,'msg'=>'需要支付','orderid'=>$orderid,'payorderid'=>$payorderid,'tmplids'=>$tmplids,'is_other'=>$is_other_fee,'fee'=>$feedata]);
		}else{
			$tmplcontent = [];
			$tmplcontent['first'] = '有客户提交表单成功';
			$tmplcontent['remark'] = '点击查看详情~';
			$tmplcontent['keyword1'] = $form['name'];
			$tmplcontent['keyword2'] = date('Y-m-d H:i');
            $tempconNew = [];
            $tempconNew['thing3'] = $form['name'];//报名名称
            $tempconNew['time5'] = date('Y-m-d H:i');//申请时间
			\app\common\Wechat::sendhttmpl(aid,$form['bid'],'tmpl_formsub',$tmplcontent,m_url('admin/form/formdetail?id='.$orderid),0,$tempconNew);
			return $this->json(['status'=>1,'msg'=>'提交成功','tmplids'=>$tmplids]);
			//短信通知
			$tel = Db::name('member')->where('id',mid)->value('tel');
			if($tel){
				\app\common\Sms::send(aid,$tel,'tmpl_formsubmit');
			}
		}
	}

    /**
     * 生成合同文件-并转pdf
     * @author: liud
     * @time: 2024/9/13 下午6:39
     */
    public static function generate_contract($sign_img_url,$ordernum,$form,$formdata=[])
    {
        if(getcustom('form_sign_pdf')){
            $hetong =  explode('upload/',$form['contract_template']);
            //获取后台配置的模板文件
            //$contract = explode(',',$set['hetong']);
            //var_dump($contract);die;
            $contract_list = array();

            $name = $ordernum.'.docx';
            if (!file_exists(dirname(ROOT_PATH.'upload/'.aid.'/'.date('Ym').'/'.$name))) {
                mk_dir(dirname(ROOT_PATH.'upload/'.aid.'/'.date('Ym').'/'.$name));
            }

            //读取模板文件
            //$templateProcessor = new TemplateProcessor(ROOT_PATH.'public'.$value);
            //$phpword = new PhpWord();
            $templateProcessor =new \PhpOffice\PhpWord\TemplateProcessor(ROOT_PATH.'upload/'.$hetong[1]);//实例化

            $formcontent = json_decode($form['content'],true);

            //循环模板变量替换
            foreach($formcontent as $k=>$v) {
                $value = $formdata['form' . $k];

                if($form['is_contract'] == 1) {
                    preg_match_all('/\d+/', $v['val20'], $matches);
                    $k = $matches[0][0];
                }

                if($v['key'] == 'checkbox') {

                    $vvs = '';
                    foreach ($value as $vcs){
                        $vvs .= $vcs.' ';
                    }

                    $value = $vvs;
                }

                if($v['key'] == 'upload_pics' || $v['key'] == 'upload') {

                    if($v['key'] == 'upload'){
                        $sign_image = explode('upload/',$value);
                        $templateProcessor->setImageValue('formParam'.$k, ['path' => ROOT_PATH.'upload/'.$sign_image[1], 'width' => 130, 'height' => 40, 'ratio' => true]);
                    }elseif($v['key'] == 'upload_pics'){

                        foreach ($value as $kk => $vc){

                            $sign_image = explode('upload/',$vc);

                            $imsarr = ['path' => ROOT_PATH.'upload/'.$sign_image[1], 'width' => 130, 'height' => 40, 'ratio' => true];

                            $templateProcessor->setImageValue('formParam'.$k, $imsarr,1);
                            $templateProcessor->setValue('formParam'.$k,'');

                        }
                    }


                }else{
                    $templateProcessor->setValue('formParam'.$k,$value ?? '');
                }

                //如果需要签字
                if($form['is_contract_sign'] == 1){
                    $sign_image = explode('upload/',$sign_img_url);
                    $templateProcessor->setImageValue('sign_pic', ['path' => ROOT_PATH.'upload/'.$sign_image[1], 'width' => 130, 'height' => 40, 'ratio' => true]);
                }

                $templateProcessor->setValue('sign_date',date('Y-m-d')); //变量值替换
            }

             //写入图片
            //输出文件
            //$out_docx_name = $file_path.time().$key.'.docx';
            $url = PRE_URL.'/upload/'.aid.'/'.date('Ym').'/'.$name;

            //保存word
            $templateProcessor->saveAs(ROOT_PATH.'upload/'.aid.'/'.date('Ym').'/'.$name);
            // var_dump($url);exit;
            // return $url;

            //读取word
            $phpWord = IOFactory::load(ROOT_PATH.'upload/'.aid.'/'.date('Ym').'/'.$name);
            //$phpWord = IOFactory::load(ROOT_PATH.'upload/'.aid.'/'.date('Ym').'/24091916191014011.docx');

            $htmlWriter  = IoFactory::createWriter($phpWord,'HTML');

            // 将word文档内容转换为HTML
            $html_file = 'upload/'.aid.'/'.date('Ym').'/'.rand(11111,9999).'.html';
            //$html_file = 'upload/ht/'.date("YmdHis").rand(11111,9999).'.pdf';
            $htmlWriter ->save($html_file);

            //读取html内容
            $htmlContent = file_get_contents($html_file);

            // 在$html中查找并替换特定内容
            $htmlContent = str_replace('<img border="0" style="width: 2532px; height: 996px;"', '<img border="0" style="width: 130px; height: 30px;"', $htmlContent);
            $htmlContent = str_replace('<img border="0" style="width: 459px; height: 484px;"', '<img border="0" style="width: 119px; height: 124px;position: relative;bottom: -126px" ', $htmlContent);
            //var_dump($htmlContent);exit;

            // 使用 Dompdf 将 HTML 转换为 PDF
            $options = new Options();

            $options->set('isRemoteEnabled', true);
            $options->setDefaultFont('simsun');
            //$options->setDpi(996);//设置图片大小（Dpi默认是96 值越大图片越小）：
            $dompdf = new Dompdf($options);

            // 加载HTML到Dompdf
            $dompdf->loadHtml($htmlContent,'UTF-8');
            $dompdf->setPaper('A4');
            $dompdf->render();
            $pdf_name = 'upload/'.aid.'/'.date('Ym').'/'.rand(11111,9999).'.pdf';

            //浏览器直接打开
            //$ress =  $dompdf->stream($pdf_name);

            // 如果需要，保存 PDF 文件到服务器
            file_put_contents($pdf_name, $dompdf->output());

            return PRE_URL.'/'.$pdf_name;
            //var_dump(PRE_URL.'/'.$pdf_name);exit;
        }

    }

	//查询表单
	public function formquery(){
		$post = input('post.');
		$form = Db::name('form')->where('aid',aid)->where('id',$post['formid'])->find();
		if($form['form_query']!='1') return json(['status'=>0,'msg'=>'该表单未开启查询功能']);
		
		$fromdata = $post['formdata'];
		$formcontent = json_decode($form['content'],true);
		$newformdata = [];
		foreach($formcontent as $fk=>$fv){
			if($fv['query'] == '1'){
				$fv['oldkey'] = $fk;
				$newformdata[] = $fv;
			}
		}
		$formcontent = $newformdata;


		$where = [];
		$where[] = ['aid','=',$form['aid']];
		$where[] = ['formid','=',$form['id']];

		$data = [];
		foreach($formcontent as $k=>$v){
			$value = $fromdata['form'.$k];
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
			$data[] = strval($value);

			$field = 'form'.$v['oldkey'];
			$val = strval($value);
			if($form['form_query_type'] == 0){
				if($val){
					$order = Db::name('form_order')->where('aid',aid)->where('formid',$form['id'])->where($field,$val)->order('id desc')->find();
					if($order) break;
				}
			}else{
				if(!$val){
					return json(['status'=>0,'msg'=>'请输入'.$v['val1']]);
				}
				$where[] = [$field,'=',$val];
			}
		}
		if($form['form_query_type'] == 1){
			$order = Db::name('form_order')->where($where)->order('id desc')->find();
		}
		if(!$order) return json(['status'=>0,'msg'=>'未查询到相关数据']);
		cache($this->sessionid.'_formquery',$order['id'],86400);

		return json(['status'=>1,'msg'=>'查询成功','tourl'=>'/pagesA/form/formdetail?id='.$order['id'].'&op=view']);
	}

	//获取上一次的数据
	public function getlastformdata(){
		//$this->checklogin();
		$formid = input('param.formid/d');
		if(!$formid || !$this->member) return json(['status'=>0,'msg'=>'参数错误']);
		$formorder = [];
		if(input('param.fromrecord')){
			$formorder = Db::name('form_order')->where('aid',aid)->where('id',input('param.fromrecord'))->where('mid',mid)->order('id desc')->find();
		}else{
			if(getcustom('plug_mantouxia')){
				$formorder = Db::name('form_order')->where('aid',aid)->where('formid',$formid)->where('mid',mid)->order('id desc')->find();
			}
		}
		if(!$formorder){
			return json(['status'=>0,'msg'=>'没有记录']);
		}else{
			$form = Db::name('form')->where('aid',aid)->where('id',$formorder['formid'])->find();
			if($form){
				$formcontent = json_decode($form['content'],true);
				if($formcontent ){
					foreach($formcontent as $k=>$v){
			            if($v['key'] == 'upload_pics'){
							$pics = $formorder['form'.$k];
							if($pics){
								$formorder['form'.$k] = explode(",",$pics);
							}
						}
					}
				}
			}
		}
		return json(['status'=>1,'data'=>$formorder]);
	}

	public function formdata(){
        if(getcustom('form_data')){
            //表单数据
            if(request()->isPost()){
                $pernum = 10;
                $pagenum = input('post.pagenum/d');
                if(!$pagenum) $pagenum = 1;
                $id        = input('post.id/d');
//                $latitude  = input('param.latitude');
//                $longitude = input('param.longitude');

                $where = [];
                $where[] = ['id','=',$id];
                $where[] = ['isopen','=',1];
                $where[] = ['aid','=',aid];
                $form = Db::name('form')->field('id,name,list_pic,list_title,list_address,list_tel,content')->where($where)->find();
                if($form){
                    $formcontent = json_decode($form['content'],true);

                    $where = [];
                    $where[] = ['formid','=',$id];
                    if(input('post.keyword')){
                        $where[] = ['form0|form1|form2|form3|form4|form5|form6|form7|form8|form9|form10','like','%'.input('param.keyword').'%'];
                    }
                    $where[] = ['aid','=',aid];
                    $where[] = ['status','=',1];
                    $datalist = Db::name('form_order')->where($where)->order('sort desc,id desc')->page($pagenum,$pernum)->select()->toArray();
                    foreach($datalist as &$dv){
                        $logo    = !empty($dv['form'.$form['list_pic']])?$dv['form'.$form['list_pic']]:'';
                        if($formcontent && $formcontent[$form['list_pic']]['key'] == 'upload_pics' && $logo){
                            $logo = explode(",",$logo)[0];
                        }
                        $dv['logo']    = $logo;

                        $dv['title']   = !empty($dv['form'.$form['list_title']])?$dv['form'.$form['list_title']]:'';
                        $dv['address'] = !empty($dv['form'.$form['list_address']])?$dv['form'.$form['list_address']]:'';
                        $dv['tel']     = !empty($dv['form'.$form['list_tel']])?$dv['form'.$form['list_tel']]:'';
                        $dv['latitude']  = $dv['adr_lat'] && !empty($dv['adr_lat'])?$dv['adr_lat']:'';
                        $dv['longitude'] = $dv['adr_lon'] && !empty($dv['adr_lon'])?$dv['adr_lon']:'';
                        // if($longitude && $latitude){
                        //     $dv['juli'] = ''.getdistance($longitude,$latitude,$dv['longitude'],$dv['latitude'],2).'km';
                        // }else{
                        //     $dv['juli'] = '';
                        // }
                        $dv['juli'] = '';
                    }
                    unset($dv);
                }else{
                    $datalist = [];
                }
                $title = '列表';
                return $this->json(['status'=>1,'data'=>$datalist,'title'=>$title]);
            }
        }
    }

    public function formdata_detail(){
        if(getcustom('form_data')){
            //表单数据
            if(request()->isPost()){
                $id        = input('post.id/d');

                $where = [];
                $where[] = ['id','=',$id];
                $where[] = ['aid','=',aid];
                $where[] = ['status','=',1];
                $detail = Db::name('form_order')->where($where)->find();
                if(!$detail){
                    return $this->json(['status'=>0,'msg'=>"数据不存在"]);
                }

                $where = [];
                $where[] = ['id','=',$detail['formid']];
                $where[] = ['isopen','=',1];
                $where[] = ['aid','=',aid];
                $form = Db::name('form')->field('id,name,content,list_address,list_tel,detail_pic,detail_title,detail_word,detail_word2')->where($where)->find();
                if(!$form){
                    return $this->json(['status'=>0,'msg'=>"数据不存在"]);
                }
                $formcontent = json_decode($form['content'],true);

                $newdata = [];
                $newdata['color'] = Db::name('admin_set')->where('aid',aid)->value('color1');

                $logo  = !empty($detail['form'.$form['detail_pic']])?$detail['form'.$form['detail_pic']]:'';
                if($formcontent && $formcontent[$form['detail_pic']]['key'] == 'upload_pics' && $logo){
                    $logo = explode(",",$logo)[0];
                }
                $newdata['logo']  = $logo;

                $newdata['title'] = !empty($detail['form'.$form['detail_title']])?$detail['form'.$form['detail_title']]:'';
                $newdata['word']  = !empty($detail['form'.$form['detail_word']])?$detail['form'.$form['detail_word']]:'';
                $newdata['word2']     = !empty($detail['form'.$form['detail_word2']])?$detail['form'.$form['detail_word2']]:'';
                $newdata['latitude']  = $detail['adr_lat'] && !empty($detail['adr_lat'])?$detail['adr_lat']:'';
                $newdata['longitude'] = $detail['adr_lon'] && !empty($detail['adr_lon'])?$detail['adr_lon']:'';

                //处理显示内容
                $content = [];
                
                if($formcontent){
                    foreach($formcontent as $k=>$v){
                        if($v['val14']==1){
                            if($k != $form['detail_pic'] && $k != $form['detail_title'] && $k != $form['detail_word'] && $k != $form['detail_word1']&& $k != $form['detail_word2']){
                                $data = [];
                                $data['type']    = '';

                                if($k == $form['list_address']){
                                    $data['type']= 'address';
                                }else if($k == $form['list_tel']){
                                    $data['type']= 'tel';
                                }else{
                                    if($v['key'] == 'input' && $v['val4'] == 2){
                                        $data['type']= 'tel';
                                    }else if($v['key'] == 'map'){
                                        $data['type']= 'address';
                                    }
                                }
                                $data['key']     = $v['key'];
                                $data['name']    = $v['val1'];
                                if($v['key'] == 'upload_pics'){
                                    $pics = $detail['form'.$k];
                                    if($pics){
                                        $detail['form'.$k] = explode(",",$pics);
                                    }
                                }
                                $data['content'] = $detail['form'.$k];
                                array_push($content,$data);
                            }
                        }
                    }
                }
                $newdata['content'] = $content;
                return $this->json(['status'=>1,'data'=>$newdata]);
            }
        }
    }


    public function formlist(){
        if(getcustom('form_listpage')){
			$pagenum = input('post.pagenum');
			$bid = input('param.bid') ? input('param.bid') : 0;
			$st = input('post.st');
			if(!$pagenum) $pagenum = 1;
			$pernum = 20;
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',$bid];
			$where[] = ['listpage_title','<>',''];
			if(input('post.keyword')){
				$where[] = ['listpage_title|listpage_description','like','%'.input('param.keyword').'%'];
			}

			if(!input('?param.st') || $st === ''){
				$st = 'all';
			}
			if($st == 'all'){

			}elseif($st == '0'){ //未开始
				//$where[] = ['starttime','>',date('Y-m-d H:i:s')];
				$where[] = Db::raw('unix_timestamp(starttime)>'.time());
			}elseif($st == 1){ //进行中
				$where[] = Db::raw('unix_timestamp(starttime)<='.time().' and unix_timestamp(endtime)>'.time());
			}elseif($st == 2){ //已结束
				$where[] = Db::raw('unix_timestamp(endtime)<'.time());
			}

			$datalist = Db::name('form')->field('id,name,starttime,endtime,payset,price,listpage_title,listpage_description,listpage_pic,listpage_tourl')->where($where)->page($pagenum,$pernum)->order('sort desc,id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
			foreach($datalist as $k=>$v){
				$datalist[$k]['count'] = 0 + Db::name('form_order')->where('formid',$v['id'])->count();
			}
			$rdata = [];
			$rdata['datalist'] = $datalist;
			$rdata['$where'] = $where;
			return $this->json($rdata);
		}
	}

    public function formSubMemberList(){
        if(getcustom('form_show_submember')){
            $id = input('param.id/d');
            $pagenum = input('param.pagenum/d', 1);

            $form = Db::name('form')->where('id', $id)->where('aid', aid)->find();
            if(!$form) return $this->json(['status'=>0,'msg'=>'表单不存在']);

            $where = [
                ['fo.aid', '=', aid],
                ['fo.status','<',2],  //0 未处理 1确认 2驳回
                ['fo.formid', '=', $form['id']]
            ];
            $submemberList = Db::name('form_order')->alias('fo')
                ->field('fo.*, m.headimg')
                ->leftJoin('member m', 'fo.mid = m.id')
                ->where($where)
                ->order('id desc')
                ->page($pagenum, 20)
                ->select()
                ->toArray();

            foreach ($submemberList as $k => &$v){
                $v['form0'] = hideMiddleName($v['form0']);
                $v['form1'] = hideMiddleName($v['form1']);
                $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
            }
            $data['data'] = $submemberList;
            return $this->json($data);
        }
    }

}
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

namespace app\model;
use think\facade\Db;
use think\facade\Log;
class ShopProduct
{
	//减关联库存
	public static function declinkstock($proid,$ggid,$num){
		$splitlist = Db::name('shop_ggsplit')->where('proid',$proid)->where("ggid1={$ggid} or ggid2={$ggid}")->select()->toArray();
		if(!$splitlist) return;
		$guige = Db::name('shop_guige')->where('id',$ggid)->find();
		foreach($splitlist as $k=>$v){
			if($v['ggid1'] == $ggid){
				$thisnum = $num * $v['multiple'];
				Db::name('shop_guige')->where('id',$v['ggid2'])->update(['stock'=>Db::raw("IF(stock>={$thisnum},stock-{$thisnum},0)"),'sales'=>Db::raw("sales+$thisnum")]);
			}
			if($v['ggid2'] == $ggid){
				//$thisnum = ceil($num / $v['multiple']);
				$thisStock = floor($guige['stock'] / $v['multiple']);
				$thisSales = floor($guige['sales'] / $v['multiple']);
				Db::name('shop_guige')->where('id',$v['ggid1'])->update(['stock'=>$thisStock,'sales'=>$thisSales]);
			}
		}
		self::calculateStock($proid);
	}
	//加关联库存
	public static function addlinkstock($proid,$ggid,$num){
		$splitlist = Db::name('shop_ggsplit')->where('proid',$proid)->where("ggid1={$ggid} or ggid2={$ggid}")->select()->toArray();
		if(!$splitlist) return;
		$guige = Db::name('shop_guige')->where('id',$ggid)->find();
		foreach($splitlist as $k=>$v){
			if($v['ggid1'] == $ggid){
				$thisnum = $num * $v['multiple'];
				Db::name('shop_guige')->where('id',$v['ggid2'])->update(['stock'=>Db::raw("stock+$thisnum"),'sales'=>Db::raw("sales-$thisnum")]);
			}
			if($v['ggid2'] == $ggid){
				$thisStock = floor($guige['stock'] / $v['multiple']);
				$thisSales = floor($guige['sales'] / $v['multiple']);
				Db::name('shop_guige')->where('id',$v['ggid1'])->update(['stock'=>$thisStock,'sales'=>$thisSales]);
			}
		}
		self::calculateStock($proid);
	}
	//计算商品总库存
	public static function calculateStock($proid){
		$ggids = Db::name('shop_ggsplit')->where('proid',$proid)->column('ggid1');
		if($ggids){
			$totalstock = Db::name('shop_guige')->where('proid',$proid)->where('id','not in',$ggids)->sum('stock');
			$totalsales = Db::name('shop_guige')->where('proid',$proid)->where('id','not in',$ggids)->sum('sales');
			Db::name('shop_product')->where('id',$proid)->update(['stock'=>$totalstock,'sales'=>$totalsales]);
		}
	}
	//验证关联库存
	public static function checkstock($prolist){
		$ggidnums = [];
		foreach($prolist as $v){
			$ggid = $v['guige']['id'];
			$linkggArr = Db::name('shop_ggsplit')->where('ggid1',$ggid)->select()->toArray();
			if($linkggArr){
				foreach($linkggArr as $linkgg){
					$ggid2 = $linkgg['ggid2'];
					$num = $v['num'] * $linkgg['multiple'];
					if($ggidnums[$ggid2]){
						$ggidnums[$ggid2] += $num;
					}else{
						$ggidnums[$ggid2] = $num;
					}
				}
			}else{
				if($ggidnums[$ggid]){
					$ggidnums[$ggid] += $v['num'];
				}else{
					$ggidnums[$ggid] = $v['num'];
				}
			}
		}
		foreach($ggidnums as $ggid=>$num){
			$guige = Db::name('shop_guige')->where('id',$ggid)->find();
			if($guige['stock'] < $num){
                $product_name = Db::name('shop_product')->where('aid',$guige['aid'])->where('id',$guige['proid'])->value('name');
                return ['status'=>0,'msg'=>$product_name . $guige['name'].'库存不足'];
            }
		}
		return ['status'=>1];
	}

	//加库存记录
	public static function addStockRecord($proid,$ggid,$stock){
		$product = Db::name('shop_product')->where('id',$proid)->find();
		if(!$product) return ['status'=>0,'msg'=>'商品不存在'];
		$guige = Db::name('shop_guige')->where('id',$ggid)->find();
		if(!$guige) return ['status'=>0,'msg'=>'规格不存在'];
		$data = [];
		$data['aid'] = $product['aid'];
		$data['bid'] = $product['bid'];
		$data['proid'] = $proid;
		$data['ggid'] = $ggid;
		$data['stock'] = $stock;
		$data['afterstock'] = $guige['stock'];
		$data['createtime'] = time();
		Db::name('shop_product_stockrecord')->insertGetId($data);
		return ['status'=>1];
	}

	/*验证会员等级限购*/
	public static function memberlevel_limit($aid,$mid,$product,$levelid){
		$levellimitdata = json_decode($product['levellimitdata'],true);
		$limitdata = [];
		foreach($levellimitdata as $level){
			if($levelid == $level['level_id']){
                $startdays = 0;
			    if($level['days'] > 0){
                    $startdays = strtotime('-'.$level['days'].' day');
                }
			    if(getcustom('product_memberlevel_limit_month')){
			        if($level['days_type'] ==1){
                        $startdays = strtotime(date('Y-m-01'));
                    }
                }
			    if($startdays > 0){
                    $buynum =Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$product['id'])->where('status','in','0,1,2,3')->where('createtime','between',[$startdays,time()])->sum('num');
                    $num = $product['num']??0;
                    if($buynum + $num>=$level['limit_num']){
                        $limitdata['ismemberlevel_limit'] = true;
                        $limitdata['days'] = $level['days'];
                        $limitdata['limit_num'] = $level['limit_num'];
                        if(getcustom('product_memberlevel_limit_month')){
                            $limitdata['days_type'] =  $level['days_type'];
                        } 
                    }else{
                        $limitdata['ismemberlevel_limit'] = false;
                    } 
                }
			}
		}	
		return ['status'=>1,'limitdata'=>$limitdata];
	}
	
	public static function getShopYingxiaoTag($product){
	     if(getcustom('shop_yingxiao_tag')){
	         //先查询有设置商品ID的  商品 > 分类 > 所有
             //设计页面过来时 proid,商品详情进来是id
             $proid = $product['proid']?$product['proid']:$product['id'];
             $aid =   $product['aid'];
             $bid =   $product['bid'];
             $cids =   $product['cid'];
             $where = [];
             $where[] = ['aid','=',$aid];
             $where[] = ['bid','=',$bid];
             $where[] = ['status','=',1];
             //商品
             $p_where[] = ['fwtype','=',2];
             $p_where[] = Db::raw("find_in_set(".$proid.",productids)");
             $p_where[] = ['productids','NOT NULL',null];
            $yingxiao_tag= Db::name('shop_yingxiao_tag')->where($where)->where($p_where)->order('sort desc')->find();
            //分类
            if(!$yingxiao_tag){
                if($cids){
                    $cids_arr = explode(',',$cids);
                    $c_where[] = ['fwtype','=',1];
                    $whereCid = [];
                    foreach($cids_arr as $k => $c2){
                        $whereCid[] = "find_in_set({$c2},categoryids)";
                    }
                    $c_where[] = Db::raw(implode(' or ',$whereCid));
                    $yingxiao_tag = Db::name('shop_yingxiao_tag')->where($where)->where($c_where)->order('sort desc')->find();
                }
              
                //所有的
                if(!$yingxiao_tag){
                    $yingxiao_tag = Db::name('shop_yingxiao_tag')->where($where)->where('fwtype',0)->order('sort desc')->find();
                }
            }
            if($yingxiao_tag){
                //替换销量变量
                $yingxiao_tag['sales_text'] = str_replace('[销量]',$product['sales'],$yingxiao_tag['sales_text']);
            }
            return    $yingxiao_tag;
         }
    }

    public static function saveOrderProductFormdata($aid,$formid,$formdata){
        if(getcustom('shop_product_form')){
            $form = Db::name('form')->where('aid',$aid)->where('id',$formid)->find();
            $data =[];
            $data['aid'] = $aid;
            $data['bid'] = $form['bid'];
            $data['formid'] = $form['id'];
            $data['title'] = $form['name'];
            $data['mid'] = mid;
            $data['status'] = 1;
            $data['createtime'] = time();
            $formcontent = json_decode($form['content'],true);
            foreach($formcontent as $k=>$v){
                $value = $formdata['form'.$k];
                if(is_array($value)){
                    $value = implode(',',$value);
                }
                $data['form'.$k] =   strval($value);
            }
            $price = 0;
            $ordernum = date('ymdHis').aid.rand(1000,9999);
            $data['money'] = $price;
            $data['ordernum'] = $ordernum;
            $orderid = Db::name('form_order')->insertGetId($data);
            return ['status'=>1,'orderid' => $orderid];
        }
    }
    //根据表单字段 生成商品证书
    // $form_id 表单id        $isedit 是否是编辑重新生成，编辑的不生成编号
    public static function createProductCertificateByForm($aid,$bid,$oglist=[],$isedit=0){
	    if(getcustom('shop_product_certificate')){
	        if(!$oglist) return [];
            foreach ($oglist as $ok=>$og){
                $form_id = Db::name('shop_product')->where('aid',$aid)->where('bid',$bid)->where('id',$og['proid'])->value('form_id');
                if(!$form_id)continue;
                $form = Db::name('form')->where('aid',$aid)->where('bid',$bid)->where('id',$form_id)->find();

                //没有表单或者表单没有关联证书，返回空
                if(!$form || !$form['certificate_poster_id']) continue;
                
                $form_order = Db::name('form_order')->where('aid',$aid)->where('bid',$bid)->where('id',$og['form_orderid'])->find();

                //没有填写表单，无数据，无法生成证书
                if(!$form_order) continue;
                
                $certificate = Db::name('certificate_poster')->where('aid',$aid)->where('id',$form['certificate_poster_id'])->find();
                //无证书，也无法生成
                if(!$certificate) continue;
                
                $formcontent = json_decode( $form['content'],true);
                $textReplaceArr =[];
                $certificate_no='';
                foreach($formcontent as $k=>$v){
                   $textkey = '['.$v['val1'].']';//姓名
                   $value = $form_order['form'.$k];
                    $textReplaceArr[$textkey] = $value;
               }
                //一个数量一个证书记录
               for($index=1;$index <= $og['num'];$index++) {
                   //证书记录
                   $certificate_poster_record = Db::name('certificate_poster_record')->where('aid',$aid)->where('hid',$certificate['id'])->where('ogid',$og['id'])->where('ogindex',$index)->find();
                   set_time_limit(0);
                   @ini_set('memory_limit', -1);
                   $poster_data = json_decode($certificate['poster_data'], true);
                   $poster_bg = $certificate['poster_bg'];
                   if (strpos($poster_bg, 'http') === false) {
                       $poster_bg = PRE_URL . $poster_bg;
                   }
                   $bg = imagecreatefromstring(request_get($poster_bg));
                   if ($bg) {
                       $bgwidth = imagesx($bg);
                       $bgheight = imagesy($bg);
                       if ($bgheight / $bgwidth > 1.92) $bgheight = floor($bgwidth * 1.92);
                       $target = imagecreatetruecolor($bgwidth, $bgheight);
                       imagecopy($target, $bg, 0, 0, 0, 0, $bgwidth, $bgheight);
                       imagedestroy($bg);
                   } else {
                       $bgwidth = 680;
                       $bgheight = 400;
                       $target = imagecreatetruecolor($bgwidth, $bgheight);
                       imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
                   }
                   $huansuan = $bgwidth / 600;
                   //$bgwidth = imagesx($bg);
                   //$bgheight = imagesy($bg);

                   $font = ROOT_PATH . "static/fonts/msyh.ttf";
                   foreach ($poster_data as $d) {
                       $d['left'] = intval(str_replace('px', '', $d['left'])) * $huansuan;
                       $d['top'] = intval(str_replace('px', '', $d['top'])) * $huansuan;
                       $d['width'] = intval(str_replace('px', '', $d['width'])) * $huansuan;
                       $d['height'] = intval(str_replace('px', '', $d['height'])) * $huansuan;
                       $d['size'] = intval(str_replace('px', '', $d['size'])) * $huansuan / 2 * 1.5;
                       if ($d['type'] == 'img') {
                           if ($textReplaceArr['[个人照片]']) {
                               $d['src'] = $textReplaceArr['[个人照片]'];
                           }
                           if ($d['src'][0] == '/') $d['src'] = PRE_URL . $d['src'];
                           $img = imagecreatefromstring(request_get($d['src']));
                           if ($img)
                               imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'], imagesx($img), imagesy($img));
                       } else if ($d['type'] == 'text') {
                           $d['content'] = str_replace(array_keys($textReplaceArr), array_values($textReplaceArr), $d['content']);
                           if ($d['content'] == '[下单时间]') {
                               //生成编号
                               $d['content'] = date('Y-m-d', $og['createtime']);
                           }
                           if ($d['content'] == '[证书编号]') {
                               //生成编号
                               if ($isedit == 1) {
                                   $certificate_no = $certificate_poster_record['certificate_no'];
                               } else {
                                   $certificate_poster_record_num = Db::name('certificate_poster_record')->where('aid', $aid)->where('hid', $certificate['id'])->count();
                                   $certificate_number_start = $certificate['certificate_number_start'] + $certificate_poster_record_num + 1;
                                   $certificate_no = $certificate['certificate_number_prefix'] . $certificate_number_start;
                               }

                               $d['content'] = $certificate_no;
                           }
                           $colors = hex2rgb($d['color']);
                           $color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);

                           imagettftext($target, $d['size'], 0, $d['left'], $d['top'] + $d['size'], $color, $font, $d['content']);
                           //imagettftext($target, $d['size']+1, 0, $d['left'], $d['top'] + $d['size'], $color, $font,  $d['content']);
                           // 绘制描边
                       } else if ($d['type'] == 'textarea') {
                           $d['content'] = str_replace(array_keys($textReplaceArr), array_values($textReplaceArr), $d['content']);
                           $colors = hex2rgb($d['color']);
                           $color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
                           $string = $d['content'];
                           $_string = '';
                           $__string = '';
                           $_height = 0;
                           mb_internal_encoding("UTF-8"); // 设置编码
                           for ($i = 0; $i < mb_strlen($string); $i++) {
                               $box = imagettfbbox($d['size'], 0, $font, $_string);
                               $_string_length = $box[2] - $box[0];
                               $box = imagettfbbox($d['size'], 0, $font, mb_substr($string, $i, 1));
                               if ($_string_length + $box[2] - $box[0] < $d['width'] * 1) {
                                   $_string .= mb_substr($string, $i, 1);
                               } else {
                                   $_height += $box[1] - $box[7] + 4;
                                   //var_dump($_height.'--'.$d['height']);
                                   if ($_height >= $d['height'] * 1) {
                                       break;
                                   }
                                   $__string .= $_string . "\n";
                                   $_string = mb_substr($string, $i, 1);
                               }
                           }
                           $__string .= $_string;
                           $box = imagettfbbox($d['size'], 0, $font, mb_substr($__string, 0, 1));
                           imagettftext($target, $d['size'], 0, $d['left'], $d['top'] + ($box[3] - $box[7]), $color, $font, $__string);

                       } else if ($d['type'] == 'shadow') {
                           $rgba = explode(',', str_replace(array(' ', '(', ')', 'rgba'), '', $d['shadow']));
                           //dump($rgba);
                           $black = imagecreatetruecolor($d['width'], $d['height']);
                           imagealphablending($black, false);
                           imagesavealpha($black, true);
                           $blackcolor = imagecolorallocatealpha($black, $rgba[0], $rgba[1], $rgba[2], (1 - $rgba[3]) * 127);
                           imagefill($black, 0, 0, $blackcolor);
                           imagecopy($target, $black, $d['left'], $d['top'], 0, 0, $d['width'], $d['height']);
                           imagedestroy($black);
                       }
                   }
                   $url = "/upload/{$aid}/" . date('Ym/d_His') . rand(1000, 9999) . '.jpg';
                   $filepath = ROOT_PATH . ltrim($url, '/');
                   mk_dir(dirname($filepath));
                   imagejpeg($target, $filepath, 100);
                   $poster_url = PRE_URL . $url;
                
                   if (!$certificate_poster_record) {
                       //增加post记录
                       $indata = [];
                       $indata['aid'] = $aid;
                       $indata['hid'] = $form['certificate_poster_id'];
                       $indata['certificate_no'] = $certificate_no;
                       $indata['start_time'] = 0;
                       $indata['posterurl'] = $poster_url;
                       $indata['ogid'] = $og['id'];
                       $indata['createtime'] = time();
                       $indata['huomacode'] = $og['huomacode'] ?? '';
                       $indata['mid'] = $og['mid'] ?? 0;
                       $indata['ogindex'] = $index;
                       $poster_id = Db::name('certificate_poster_record')->insertGetId($indata);
                   } else {

                       Db::name('certificate_poster_record')->where('id', $certificate_poster_record['id'])->update(['posterurl' => $poster_url]);
                       $poster_id = $certificate_poster_record['id'];
                   }
               }
//               Db::name('shop_order_goods')->where('aid',$aid)->where('bid',$bid)->where('id',$og['id'])->update(['certificate_poster_record_id' => $poster_id]);//存的最后一个，因和数量挂钩，已弃用
           }
        }
    }
}
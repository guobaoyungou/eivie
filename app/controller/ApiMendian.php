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
class ApiMendian extends ApiCommon{
    public function mendianlist(){
        $bid = input('param.bid/d',0);
        $proid = input('param.proid','');//多个用逗号分隔，售卖某商品的店
        $longitude = input('param.longitude/f','');
        $latitude = input('param.latitude/f','');
        $type = input('param.type','');//查询商品类型，默认查询商城商品
        $pernum = 100;
        $pagenum = input('param.pagenum');
        if(!$pagenum) $pagenum = 1;
        $mdwhere = [];
        $mdwhere[] = ['aid','=',aid];
        $mdwhere[] = ['status','=',1];
        $mendian_upgrade = false;  
        if($mendian_upgrade){
            $keyword = input('param.keyword');
            if($keyword){
                $mdwhere[] = ['xqname|name','like','%'.$keyword.'%'];
            }
			$city = input('param.city');
            if($city){
                $mdwhere[] = ['city','=',$city];
            }
        }

        $field = '*';
        //如果一个商品包含全部，则为全部门店
        if($proid){
            if($type == 'scoreshop'){
                $productlist = Db::name('scoreshop_product')->where('id','in',explode(',',$proid))->field('id,bind_mendian_ids')->select()->toArray();
            }else{
                $productlist = Db::name('shop_product')->where('id','in',explode(',',$proid))->field('id,bind_mendian_ids')->select()->toArray();
            }
            $tmpmdids = [];
            if(empty($productlist)) $productlist = [];
            foreach ($productlist as $product){
                $bindMendianIds = $product['bind_mendian_ids']?explode(',',$product['bind_mendian_ids']):[];
                if(empty($bindMendianIds) || in_array('-1',$bindMendianIds)){
					break;
                }
				$tmpmdids = array_merge($tmpmdids,$bindMendianIds);     
            }
			if(false){}else{
			    $mdwhere[] = ['bid','=',$bid];
			}
			if($tmpmdids){
				$mdwhere[] = ['id','in',array_unique($tmpmdids)];
			}
        }else{
			$mdwhere[] = ['bid','=',$bid];
		}

		if($longitude && $latitude){
            $field .= ",round(6378.138*2*asin(sqrt(pow(sin( ({$latitude}*pi()/180-latitude*pi()/180)/2),2)+cos({$latitude}*pi()/180)*cos(latitude*pi()/180)* pow(sin( ({$longitude}*pi()/180-longitude*pi()/180)/2),2)))*1000) AS distance";
            $mdorder = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) asc");
        }else{
            $field .= ",0 distance";
            $mdorder = 'sort desc,id asc';
        }
        $mendianlist = Db::name('mendian')->field($field)->where($mdwhere)->orderRaw($mdorder)->page($pagenum,$pernum)->select()->toArray();
        if(empty($mendianlist)){
            $mendianlist = [];
        }
        foreach ($mendianlist as $mdkey=>$mendian){
            if(empty($mendian['distance'])){
                $mendianlist[$mdkey]['distance'] = '';
            }elseif($mendian['distance']<1000){
                $mendianlist[$mdkey]['distance'] = round($mendian['distance'],1).'m';
            }else{
                $mendianlist[$mdkey]['distance'] = round($mendian['distance']/1000,1).'km';
            }
            $mendianlist[$mdkey]['distanceNumKm'] = $mendian['distance'] ? round($mendian['distance']/1000,1) : 0;
            $mendianlist[$mdkey]['distanceNumM'] = $mendian['distance'] ? $mendian['distance'] : 0;
            $mendianlist[$mdkey]['address'] = $mendian['address']??'';
            $mendianlist[$mdkey]['area'] = $mendian['area']??'';
            if(!$mendian['name']){
                $mendianlist[$mdkey]['name'] = '无';
            }
			// if($mendian_upgrade){
			// 	$member = Db::name('member')->field('headimg')->where('id',$mendian['mid'])->find();
			// 	$mendianlist[$mdkey]['pic'] = $member['headimg'];
			// }
        }
        return $this->json(['status'=>1,'msg'=>'','data'=>$mendianlist]);
    }
    //默认门店
    public function getNearByMendian(){
        $mendian_id = input('param.mendian_id/d',0);
        $bid = input('param.bid',0);
        $mendian_isinit = input('param.mendian_isinit');
        $latitude = input('param.latitude/f','');
        $longitude = input('param.longitude/f','');
        $bfield = 'id,name,province,city,district,address,longitude,latitude';

        $mendian_upgrade = false;  
        if($mendian_upgrade){
            $bfield .=',pic,xqname';
        }
        
        if($mendian_id && !$mendian_isinit){
            //不是初始化的用户选择的门店
            $mendian = Db::name('mendian')->where('id',$mendian_id)->field($bfield)->find();
        }else if($latitude && $longitude){
            $mdorder = Db::raw("({$longitude}-longitude)*({$longitude}-longitude) + ({$latitude}-latitude)*({$latitude}-latitude) asc");
            $mendian = Db::name('mendian')->where('aid',aid)->where('status',1)->where('bid',$bid)->orderRaw($mdorder)->field($bfield)->find();
        }else{
            $mendian = Db::name('mendian')->where('aid',aid)->where('status',1)->where('bid',$bid)->order('sort desc,id asc')->field($bfield)->find();
        }
        if($mendian){
            $mendian['address'] = ($mendian['province']??'').($mendian['city']??'').($mendian['address']??'');
            $bdistance = '';
            if($mendian['latitude'] && $mendian['longitude'] && $latitude && $longitude){
                $bdistance = getdistance($longitude,$latitude,$mendian['longitude'],$mendian['latitude'],2);
            }
            $mendian['distance'] = $bdistance?$bdistance.'km':'';
			$mendian['mendian_upgrade'] = $mendian_upgrade;
            return $this->json(['status'=>1,'msg'=>'','mendian'=>$mendian??'']);
        }else{
            return $this->json(['status'=>0,'msg'=>'','msg'=>'未查询到门店']);
        }
    }
    
    public function getMendianCategory(){
        $bid = input('param.bid/d',0);
        }

	public function updatemendian(){
		$this->checklogin();
		$mendian_id = input('param.mendianid/d',0);
        // 如果绑定门店过就不可以再改门店了
        Db::name('member')->where('aid',aid)->where('id',mid)->update(['mdid'=>$mendian_id]);
		return $this->json(['status'=>1,'msg'=>'']); 
	}

    /**
     * 门店分红
     * 详细见文档功能3、4 https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0dXW7gYuAeYAOQYKALU&tab=BB08J2
     * @author: liud
     * @time: 2024/12/30 上午10:54
     */
    public function mendianfonghong(){
        }

    /**
     * 门店分红数据
     * 详细见文档功能3、4 https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0dXW7gYuAeYAOQYKALU&tab=BB08J2
     * @author: liud
     * @time: 2024/12/30 上午10:54
     */
    public function mendianmemberlist(){
        }

    /**
     * 门店核销二维码
     * @author: liud
     * @time: 2025/4/25 下午2:54
     */
    public function hxqrcode()
    {
        }
}
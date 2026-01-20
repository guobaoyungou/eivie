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



namespace app\custom;


use think\facade\Db;
use think\facade\Log;

class Baidu
{
    private $appId;
    private $apiKey;
    private $secretKey;
    private $aid;
    private $searchNum;
    public function __construct($aid=1,$appId=null,$apiKey=null,$secretKey=null,$searchNum=30){
        $this->aid = intval($aid);
        if($appId){
            $this->appId = trim($appId);
            $this->apiKey = trim($apiKey);
            $this->secretKey = trim($secretKey);
            $this->searchNum = intval($searchNum);
        }else{
            $sysset = Db::name('baidu_set')->where('aid',$aid)->where('image_search',1)->find();
            if(empty($sysset) || empty($sysset['baidu_appid']) || empty($sysset['baidu_apikey']) || empty($sysset['baidu_secretkey'])) {
                return ['stauts' =>0, 'msg' => '请检查配置'];
            }
            $this->appId = trim($sysset['baidu_appid']);
            $this->apiKey = trim($sysset['baidu_apikey']);
            $this->secretKey = trim($sysset['baidu_secretkey']);
            $this->searchNum = intval($sysset['image_search_num']);
        }
    }

    public function sync($limit=30)
    {
        //baidu_img_sync:0未添加，-1不符合条件，1已添加，2需更新
        $prolist = Db::name('shop_product')
            ->field('id,name,pic,pics,baidu_img_sync,status')
            ->where('aid', $this->aid)->where('status',1)
            ->where('pic','<>','')
            ->whereIn('baidu_img_sync',[0,2])->limit($limit)->select()->toArray();
        foreach ($prolist as $item) {

            if($item['baidu_img_sync'] == 2){
//                $client->productUpdate();
            }else{
                $rs = $this->add($item,$item['pic']);
                $this->updateProductWithRs($rs,$item['id']);
                $rs = $this->add($item,$item['pics']);
            }
        }
//        dd(1);
    }

    public function addProduct($product)
    {
        $rs = $this->add($product,$product['pic']);
        $this->updateProductWithRs($rs,$product['id']);
        $rs = $this->add($product,$product['pics']);
    }

    public function delProduct($product)
    {
        if(empty($product['pic']) && empty($product['pics'])) return ;
        $rs = $this->del($product['pic']);
        $rs = $this->del($product['pics']);

//        if($rs['cont_sign'])
//        Db::name('shop_product')->where('aid', $this->aid)->where('id',$product['id'])->update(['baidu_img_sync' => 0]);
    }

    public function updateProduct($product, $newdata){
        if($product['pic'] != $newdata['pic']) {
            if($product['pic']) $this->del($product['pic']);
            if($newdata['pic']) {
                $rs = $this->add($newdata,$newdata['pic']);
                $this->updateProductWithRs($rs,$newdata['id']);
            }
        }
        $pics = explode(',',$product['pics']);
        $newpics = explode(',',$newdata['pics']);
        if(!array_diff($pics, $newpics) && !array_diff($newpics, $pics)){
            return true;
        } else {
            if($product['pics'] && empty($newdata['pics'])) {
                $this->del($product['pics']);
            }elseif(empty($product['pics']) && $newdata['pics']){
                $this->add($newdata,$newdata['pics']);
            }
            if($pics && $newpics) {
                $delarr = array_diff($pics, $newpics);
                $addarr = array_diff($newpics, $pics);
                if($delarr){
                    $this->del($delarr);
                }
                if($addarr){
                    $this->add($newdata,$addarr);
                }
            }

        }
    }

    public function updateProductWithRs($rs,$productId)
    {
        $baidu_img_sync = 1;
        if($rs['error_code'] /*in_array($rs['error_code'],[282111,216681,216100,216202])*/) {
            //282111 url format illegal 图片有问题
            //216681 item has existed
            //216100 brief too long
            //216202 image size error
//            if(in_array($rs['error_code'],[282111,216202]))
                $baidu_img_sync = -1;
            return Db::name('shop_product')->where('aid', $this->aid)->where('id',$productId)->update(['baidu_img_sync' => $baidu_img_sync]);
        }
        if($rs['cont_sign'])
            return Db::name('shop_product')->where('aid', $this->aid)->where('id',$productId)->update(['baidu_img_sync' => $baidu_img_sync]);
    }

    public function add($product,$picUrl)
    {
        if(empty($picUrl)) return ;
        $brief = ['name'=>mb_substr($product['name'],0,20),'id'=>$product['id']];
        $options['brief'] = json_encode($brief);
        $client = new \AipImageSearch($this->appId,$this->apiKey,$this->secretKey);
        if(is_array($picUrl))
            $pics = $picUrl;
        else
            $pics = explode(',',$picUrl);
        if($pics){
            foreach ($pics as $pic){
                $rs = $client->productAddUrl($pic,$options);
                Log::write([
                    'file'=>__FILE__.' ' .__LINE__,
                    'rs'=>$rs
                ]);
            }
        }
        return $rs;
    }

    public function del($picUrl)
    {
        if(empty($picUrl)) return ;
        $client = new \AipImageSearch($this->appId,$this->apiKey,$this->secretKey);
        if(is_array($picUrl))
            $pics = $picUrl;
        else
            $pics = explode(',',$picUrl);
        if($pics){
            foreach ($pics as $pic){
                $rs = $client->productDeleteByUrl($pic);
            }
        }
        return $rs;
    }

    public function search($imgUrl)
    {
        $client = new \AipImageSearch($this->appId,$this->apiKey,$this->secretKey);
        $rs = $client->productSearchUrl($imgUrl,['pn'=>0,'rn'=>$this->searchNum * 3]);
        return $rs;
    }

    public function searchProduct($imgUrl)
    {
        $rs = $this->search($imgUrl);
        $proids = [];
        if($rs['result']) {
            foreach ($rs['result'] as $item){
                $brief = json_decode($item['brief'],true);
                $proids[] = $brief['id'];
            }
            $proids = array_unique($proids);
            $proids = array_filter($proids);
            $proids = array_slice($proids,0,$this->searchNum,true);
        }
        return $proids;
    }
}
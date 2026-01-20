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

//custom_file(wx_channels)
//视频号小店
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsProduct extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
    }

    public function index()
    {
        $type = input('type', 0);
        $product_status = \app\common\WxChannels::product_status;
        $product_edit_status = \app\common\WxChannels::product_edit_status;
        $product_audit_status = \app\common\WxChannels::product_audit_status;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('?param.cid') && input('param.cid')!==''){
                $cid = input('param.cid');
                $where[] = ['cat1|cat2|cat3','=',$cid];
            }
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['status','=',input('status')];
            }
            if(input('name')){
                $name = trim(input('name'));
                $where[] = ['name','like','%'.$name.'%'];
            }
            $list = Db::name("channels_product")
                ->where($where)
                ->order($order)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $catname = Db::name('channels_category')->where('appid',$this->appid)->where('cat_id','in',[$v['cat1'],$v['cat2'],$v['cat3']])->column('name');
                $catname = implode('-',$catname);
                $data[$k]['cname'] = $catname;
                $data[$k]['status_name'] = $product_status[$v['status']];
                $data[$k]['edit_status_name'] = $product_edit_status[$v['edit_status']];
                $data[$k]['audit_status_name'] = $product_audit_status[$v['audit_status']];
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        //分类
        $clist = Db::name('channels_category')->Field('cat_id,name')
            ->where('aid',aid)
            ->where('appid',$this->appid)
            ->where('bid',bid)
            ->where('f_cat_id',0)->order('id desc')->select()->toArray();
        foreach($clist as $k=>$v){
            $child = Db::name('channels_category')->Field('cat_id,name')
                ->where('aid',aid)
                ->where('appid',$this->appid)
                ->where('bid',bid)
                ->where('f_cat_id',$v['cat_id'])->order('id desc')->select()->toArray();
            foreach($child as $k2=>$v2){
                $child2 = Db::name('channels_category')
                    ->Field('cat_id,name')
                    ->where('aid',aid)
                    ->where('bid',bid)
                    ->where('appid',$this->appid)
                    ->where('f_cat_id',$v2['cat_id'])->order('id desc')->select()->toArray();
                $child[$k2]['child'] = $child2;
            }
            $clist[$k]['child'] = $child;
        }
        View::assign('clist',$clist);
        View::assign('product_status',$product_status);
        return View::fetch('index', ['type' => $type]);
    }

    public function edit()
    {
        $id = input('id', 0);
        $info = Db::name("channels_product")->where('id', $id)->find();
        if($info){
            if(!$info['cat_ids']){
                //老数据没存这个字段
                $info['cat_ids'] = $info['cat1'].';'.$info['cat2'].';'.$info['cat3'];
            }
            $cat_names = Db::name('channels_category')->where('cat_id','in',$info['cat_ids'])->column('name');
            $info['cat_names'] = implode('>',$cat_names);
        }
        View::assign('newgglist', []);
        View::assign('info', $info);

        //多规格
        $newgglist = [];
        $freightdata = [];
        if($info){
            $gglist = Db::name('channels_product_guige')->where('aid',aid)->where('proid',$info['id'])->where('bid',bid)->select()->toArray();
            foreach($gglist as $k=>$v){
                if($v['ks']!==null){
                    $newgglist[$v['ks']] = $v;
                }else{
                    Db::name('channels_product_guige')->where('aid',aid)->where('id',$v['id'])->where('bid',bid)->update(['ks'=>$k]);
                    $newgglist[$k] = $v;
                }
            }
            if($info['template_id']){
                $freightdata = Db::name('channels_freight')->where('aid',aid)->where('template_id',$info['template_id'])->where('bid',bid)->find();
                $freightdata['valuation_type'] = \app\common\WxChannels::valuation_type[$freightdata['valuation_type']];
            }
        }
        View::assign('newgglist',$newgglist);
        View::assign('freightdata',$freightdata);

        $attrs = [];
        $product_attr_list = [];//类目对应的产品属性
        $sale_attr_list = [];//类目对应的产品规格
        if($info){
            $attrs = json_decode($info['attrs'],true);
            $cat_info = Db::name('channels_category_detail')->where('cat_id',$info['cat3'])->find();
            if(!$cat_info){
                $res = \app\common\WxChannels::catDetail(aid,bid, $this->appid, $info['cat3']);
                if(!$res['status']){
                    echojson(['status'=>0,'msg'=>$res['msg']?:'获取类目信息失败']);exit;
                }
                $attr = $res['attr'];
                $product_attr_list = $attr['product_attr_list'];
                $sale_attr_list = $attr['sale_attr_list'];
            }else{
                $attr = json_decode($cat_info['attr'],true);
                $product_attr_list = $attr['product_attr_list'];
                $sale_attr_list = json_decode($attr['sale_attr_list']);
            }
        }
        $attrs = array_column($attrs,'attr_value','attr_key');
        foreach($product_attr_list as $k=>$v){
            if(in_array($v['type_v2'],['select_one','select_many','integer_unit','decimal4_unit'])){
                $attr_select = explode(';',$v['value']);
                $product_attr_list[$k]['attr_select'] = $attr_select;
            }
            $product_attr_list[$k]['write_value'] = $attrs[$v['name']]??'';
        }
        if($sale_attr_list){
            foreach($sale_attr_list as $k=>$v){
                if($v['type']=='select_one'){
                    $attr_select = explode(';',$v['value']);
                    $sale_attr_list[$k]['attr_select'] = $attr_select;
                }
            }
        }
        View::assign('attrs',$attrs);
        View::assign('product_attr_list',$product_attr_list);
        View::assign('sale_attr_list',$sale_attr_list);

        $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
        $default_cid = $default_cid ? $default_cid : 0;
        $aglevellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->where('can_agent','<>',0)->order('sort,id')->select()->toArray();
        View::assign('aglevellist',$aglevellist);
        View::assign('bid',bid);


        //商品资质
        $qua_file_type = [];
        if($info && $info['product_qua_infos']){
            $qua_url = json_decode($info['product_qua_infos'],true);
            $cat_info = Db::name('channels_category_detail')->where('cat_id',$info['cat3'])->find();
            $product_qua_list = json_decode($cat_info['product_qua_list'],true);
            if($product_qua_list){
               foreach($product_qua_list as $qua){
                   $qua_file_type[] = ['field_name'=>$qua['qua_id'],'name'=>$qua['name'],
                       'maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>$qua['tips'],'is_must'=>$qua['need_to_apply'],
                       'url' => $qua_url[$qua['qua_id']]];
               }
            }
        }

        View::assign('qua_file_type',$qua_file_type);
        return View::fetch();
    }

    public function save(){

        $id = input('id');
        $info = input('info');
        $attrs = input('attrs');
        $attrs_value = input('attrs_value');
        foreach($attrs as $k=>$v){
            if(isset($attrs_value[$k]) && $v!=''){
                $attrs[$k] = $v.' '.$attrs_value[$k];
            }
            if(is_array($v)){
                $attrs[$k] = implode(';',$v);
            }
        }
        $new_attrs = [];
        foreach($attrs as $a_k=>$a_v){
            $new_attrs[] = [
                'attr_key' => $a_k,
                'attr_value' => $a_v
            ];
        }
        $options = input('option');
        $specs = input('specs');
        $info['aid'] = aid;
        $info['bid'] = bid;
        $info['appid'] = $this->appid;
        $info['guigedata'] = input('post.specs');
        $cat_ids = $info['cat_ids'];
        $cat_ids_arr = explode(',',$cat_ids);
        $info['cat1'] = $cat_ids_arr[0];
        $info['cat2'] = $cat_ids_arr[1];
        $info['cat3'] = $cat_ids_arr[2];
        $info['cat_ids'] = $cat_ids;
        if(empty($info['cat1']) || empty($info['cat2']) || empty($info['cat3'])){
            return json(['status'=>0,'msg'=>'请选择产品分类']);
        }
        $i=0;
        foreach(input('post.option/a') as $ks=>$v){
            if($i==0){
                $sell_price = $v['sell_price'];
//                $weight = $v['weight'];
                $stock = $v['stock'];
            }
            $i++;
        }
        $info['sell_price'] = $sell_price;
        $info['stock'] = $stock;
        $info['attrs'] = jsonEncode($new_attrs);
        $info['createtime'] = time();
        //图片转为微信图片
        $imgs_arr = explode(',',$info['pics']);
        $new_imgs = $this->imgtowx($imgs_arr);;
        $info['pics'] = $new_imgs[0];
        $info['pics'] = implode(',',$new_imgs);
        if($info['desc_pics'] || $info['desc']){
            $imgs_arr = explode(',',$info['desc_pics']);
            $new_imgs = $this->imgtowx($imgs_arr);
            $info['desc_pics'] = implode(',',$new_imgs);
        }

        $score_weishu = 0;
        if(bid){
            //多商户不支持积分设置
            if($info['commissionset'] == 3 || $info['commissionset'] == 5 || $info['commissionset'] == 6 || $info['commissionset'] == 7 ){
                return json(['status'=>0,'msg'=>'分销设置类型错误']);
            }
        }

        $info['commissionset'] = $info['commissionset'];
        $commissiondata3 = input('post.commissiondata3/a');
        foreach($commissiondata3 as $levelid=>$commission){
            $commissiondata3[$levelid]['commission1'] = dd_money_format($commission['commission1'],$score_weishu);
            $commissiondata3[$levelid]['commission2'] = dd_money_format($commission['commission2'],$score_weishu);
            $commissiondata3[$levelid]['commission3'] = dd_money_format($commission['commission3'],$score_weishu);
        }

        $info['commissiondata1'] = jsonEncode(input('post.commissiondata1/a'));
        $info['commissiondata2'] = jsonEncode(input('post.commissiondata2/a'));
        $info['commissiondata3'] = jsonEncode($commissiondata3);
        $info['commissiondata4'] = jsonEncode(input('post.commissiondata4/a'));

        $info['edit_status'] = 0;
        $info['product_qua_infos'] = jsonEncode(input('qua'));
        if(empty($info['after_sale_address_id'])){
            return json(['status'=>0,'msg'=>'请选择售后地址']);
        }
        Db::startTrans();
        $exit = Db::name('channels_product')->where('id',$id)->find();
        if($id){
            Db::name('channels_product')->where('id',$id)->update($info);
            $proid = $id;
        }else{
            $proid = Db::name('channels_product')->insertGetId($info);
        }
        $newggids = array();
        foreach($options as $ks=>$v){
            $ggdata = array();
            $ggdata['proid'] = $proid;
            $ggdata['ks'] = $ks;
            $ggdata['name'] = $v['name'];

            $new_imgs = $this->imgtowx( $v['pic']);;
            $ggdata['pic'] = $new_imgs[0];
            $ggdata['market_price'] = $v['market_price']>0 ? $v['market_price']:0;
            $cost_price = $v['cost_price']>0 ? $v['cost_price']:0;
            $ggdata['cost_price'] = $cost_price;
            $ggdata['sell_price'] = $v['sell_price']>0 ? $v['sell_price']:0;
            $ggdata['weight'] = $v['weight']>0 ? $v['weight']:0;
            $ggdata['procode'] = $v['procode'];
            $ggdata['stock'] = $v['stock']>0 ? $v['stock']:0;

            $specs_arr = json_decode($specs,true);
            $ks_arr = explode(',',$ks);
            $sku_attrs = [];
            foreach($specs_arr as $spec_k=>$spec_data){
                $spec_tiems = array_column($spec_data['items'],'title','k');
                $attr_value = $spec_tiems[$ks_arr[$spec_k]];
                $sku_attrs[] = [
                    'attr_key' => $spec_data['title'],
                    'attr_value' => $attr_value,
                ];
            }
            $ggdata['sku_attrs'] = json_encode($sku_attrs);

            $guige = Db::name('channels_product_guige')->where('aid',aid)->where('proid',$proid)->where('bid',bid)->where('ks',$ks)->find();
            if($guige){
                Db::name('channels_product_guige')->where('aid',aid)->where('id',$guige['id'])->where('bid',bid)->update($ggdata);
                $ggid = $guige['id'];
            }else{
                $ggdata['aid'] = aid;
                $ggdata['bid'] = bid;
                $ggid = Db::name('channels_product_guige')->insertGetId($ggdata);
            }
            $newggids[] = $ggid;
        }
        Db::name('channels_product_guige')->where('aid',aid)->where('proid',$proid)->where('id','not in',$newggids)->where('bid',bid)->delete();
        //组装微信数据
        $data_wx = $this->getwxdata($proid,$info,$attrs_value);
        if($id && $exit['product_id']){
            $data_wx['product_id'] = $exit['product_id'];
            $res = \app\common\WxChannels::updateProduct(aid,bid, $this->appid, $data_wx);
        }else{
            $res = \app\common\WxChannels::addProduct(aid,bid, $this->appid, $data_wx);
        }
        if($res['status']==0){
            return json($res);
        }else{
            Db::name('channels_product')->where('id',$proid)->update(['product_id'=>$res['data']]);
        }

        Db::commit();
        \app\common\System::plog("修改视频号小店产品：【{$proid}】");
        return json(['status'=>1,'msg'=>'操作成功']);
    }

    //删除
    public function del(){
        $ids = input('post.ids/a');
        if(!$ids) $ids = array(input('post.id/d'));
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['id','in',$ids];
        Db::startTrans();
        $prolist = Db::name('channels_product')->where($where)->select();
        foreach($prolist as $pro){
            Db::name('channels_product')->where('id',$pro['id'])->delete();
            Db::name('channels_product_guige')->where('proid',$pro['id'])->delete();
            if($pro['product_id']){
                $res = \app\common\WxChannels::deleteProduct(aid,bid,$this->appid,$pro['product_id']);
                if(!$res['status']){
                    return json($res);
                }
            }
        }
        Db::commit();
        \app\common\System::plog('视频号小店商品删除'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }
    //改状态
    public function setst(){
        $st = input('post.st/d');
        $ids = input('post.ids/a');
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['id','in',$ids];
        Db::startTrans();
        $prolist = Db::name('channels_product')->where($where)->select();
        foreach($prolist as $pro) {
            if ($st == 5) {
                $res = \app\common\WxChannels::listingProduct(aid,bid, $this->appid,$pro['product_id']);
            } else {
                $res = \app\common\WxChannels::delistingProduct(aid,bid, $this->appid,$pro['product_id']);
            }
            if(!$res['status']){
                return json($res);
            }
        }
        Db::name('channels_product')->where($where)->update(['status'=>$st]);
        Db::commit();
        \app\common\System::plog('视频号小店商品改状态'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'操作成功']);
    }
    //改状态
    public function cancel(){
        $ids = input('post.ids/a');
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['id','in',$ids];
        Db::startTrans();
        Db::name('channels_product')->where($where)->update(['edit_status'=>1]);
        $prolist = Db::name('channels_product')->where($where)->select();
        foreach($prolist as $pro) {
                $res = \app\common\WxChannels::cancelProduct(aid,bid, $this->appid,$pro['product_id']);
            if(!$res['status']){
                return json($res);
            }
        }
        Db::commit();
        \app\common\System::plog('视频号小店商品撤回审核'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'操作成功']);
    }
    //获取库存
    public function getstock(){
        $id = input('id');
        Db::startTrans();
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['id','=',$id];
        $info = Db::name('channels_product')->where($where)->find();
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['proid','=',$id];
        $sku_lists = Db::name('channels_product_guige')->where($where)->select()->toArray();
        $total_stock = 0;
        foreach($sku_lists as $k=>$v){
            $data = [
                'product_id' => $info['product_id'],
                'sku_id' => $v['sku_id'],
            ];
            $res = \app\common\WxChannels::getProductStock(aid,bid, $this->appid,$data);
            if(!$res['status']){
                return json($res);
            }
            $total_stock = bcadd($total_stock,$res['data']['total_stock_num']);
            Db::name('channels_product_guige')->where('id',$v['id'])->update(['stock'=>$res['data']['total_stock_num']]);
        }
        Db::name('channels_product')->where('id',$info['id'])->update(['stock'=>$total_stock]);
        Db::commit();
        \app\common\System::plog('获取库存视频号小店商品库存'.implode(',',$id));
        return json(['status'=>1,'msg'=>'操作成功']);
    }

    public function selectCat()
    {
        $f_cat_id = input('parent_id', 0);
        $data = Db::name("channels_category_basic")
            ->where('aid', aid)
            ->where('bid', bid)
            ->where('appid', $this->appid)
            ->field('cat_id id,name')
            ->where('f_cat_id', $f_cat_id)
            ->select();
        return json(['status'=>1,'data'=>$data]);
    }

    //同步商品列表
    public function asyncAllProduct()
    {
        Db::startTrans();
        try {
            $next_key = input('next_key');
            $status = input('status/d')?:0;
            //平台商品列表
            $res = \app\common\WxChannels::asyncProductAll(aid,bid, $this->appid, $next_key,$status);
            if($res['status'] == 0){
                return json($res);
            }
            $product_ids = $res['data'];
            foreach ($product_ids as $product_id) {
               $res2 = \app\common\WxChannels::productToShop(aid,bid, $this->appid, $product_id);
               if(!$res2['status']){
                   return json($res2);
               }
            }
            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'continue_flag'=>$res['continue_flag']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    public function getCatDetail($cache=0){
        $id = input('id');
        //检测是否需要申请
//        $exit = Db::name('channels_category')->where('cat_id',$id)->where('level',3)->find();
//        if(!$exit){
//            $cat_name = Db::name('channels_category_basic')->where('cat_id',$id)->where('level',3)->value('name');
//            return json(['status'=>0,'msg'=>'未申请「'.$cat_name.'」类目相关资质或该类目资质暂不可用，请重新申请该类目，通过后再使用该类目新增商品']);
//        }
        $cat_info = Db::name('channels_category_detail')->where('cat_id',$id)->find();
        if($cache && $cat_info){
            $attr = json_decode($cat_info['attr'],true);
            $product_attr_list = $attr['product_attr_list'];
            $sale_attr_list = json_decode($cat_info['sale_attr_list']);
            $product_requirement = $attr['product_requirement'];
            $product_qua_list = json_decode($cat_info['product_qua_list'],true);
        }else{
            $res = \app\common\WxChannels::catDetail(aid,bid, $this->appid, $id);
            if(!$res['status']){
                echojson(['status'=>0,'msg'=>$res['msg']?:'获取类目信息失败']);exit;
            }
            $attr = $res['attr'];
            $product_attr_list = $attr['product_attr_list'];
            $sale_attr_list = $attr['sale_attr_list'];
            $product_requirement = $attr['product_requirement'];
            $cat_info_data = [];
            $cat_info_data['cat_id'] = $id;
            $cat_info_data['info'] = jsonEncode($res['info']);
            $cat_info_data['attr'] = jsonEncode($res['attr']);
            $cat_info_data['product_qua_list'] = jsonEncode($res['product_qua_list']);
            $cat_info_data['createtime'] = time();
            if(!$cat_info){
                Db::name('channels_category_detail')->insert($cat_info_data);
            }else{
                Db::name('channels_category_detail')->where('id',$cat_info['id'])->update($cat_info_data);
            }
            $product_qua_list = $res['product_qua_list'];
        }
        $proid = input('proid', 0);
        $info = Db::name("channels_product")->where('id', $proid)->find();
        $attrs = [];
        if($info){
            $attrs = json_decode($info['attrs'],true);
            $attrs = array_column($attrs,'attr_value','attr_key');
        }

        foreach($product_attr_list as $k=>$v){
            if(in_array($v['type_v2'],['select_one','select_many','integer_unit','decimal4_unit'])){
                $attr_select = explode(';',$v['value']);
                $product_attr_list[$k]['attr_select'] = $attr_select;
            }
            $product_attr_list[$k]['write_value'] = $attrs[$v['name']]??'';
        }
        if($sale_attr_list){
            foreach($sale_attr_list as $k=>$v){
                if($v['type']=='select_one'){
                    $attr_select = explode(';',$v['value']);
                    $sale_attr_list[$k]['attr_select'] = $attr_select;
                }
            }
        }
        //商品资质
        $qua_file_type = [];
        if($product_attr_list){
            $qua_url = json_decode($info['product_qua_infos'],true);
            foreach($product_qua_list as $qua){
                $url_arr =  $qua_url[$qua['qua_id']]??[];
                $qua_file_type[] = [
                    'field_name'=>$qua['qua_id'],'name'=>$qua['name'],
                    'maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>$qua['tips'],'is_must'=>$qua['need_to_apply'],
                    'url' => $url_arr,
                    'url_arr' => $url_arr?explode(',',$url_arr):[]
                ];
            }
        }


        return json(['status'=>1,'data'=>$product_attr_list,'sale_attr_list'=>$sale_attr_list,'product_requirement'=>$product_requirement,'qua_file_type'=>$qua_file_type]);
    }

    //转换为微信图片
    public function imgtowx($imgs_arr){
        if(!is_array($imgs_arr)){
            $imgs_arr = explode(',',$imgs_arr);
        }
        $new_imgs = [];
        foreach($imgs_arr as $img){
            if($img && strpos($img,'mmecimage.cn/p')===false){
                $new_url_info = Db::name('admin_upload')->where('old_url','=',$img)->find();
                $new_url = $new_url_info['channels_file_id']??'';
                if(!$new_url || strpos($new_url,'mmecimage.cn/p')===false){
                    $res = \app\common\WxChannels::uploadImage(aid,bid, $this->appid, $img);
                    if($res['status']==1){
                        $new_url = $res['img_url'];
                        $insert = array(
                            'aid' => aid,
                            'bid' => bid,
                            'uid' => $this->uid,
                            'name' => '',
                            'dir' => date('Ymd'),
                            'url' => $res['img_url'],
                            'old_url' => $img,
                            'type' => 'jpg',
                            'width' => '',
                            'height' => '',
                            'bsize' => 0,
                            'hash' => 0,
                            'createtime' => time(),
                            'gid'=> cookie('browser_gid') && cookie('browser_gid')!='-1' ? cookie('browser_gid') : '0'
                        );
                        $insert['channels_file_id'] = $res['img_url'];
                        $insert['other_param'] = 'channels_wx';
                        if(!$new_url_info){
                            Db::name('admin_upload')->insert($insert);
                        }else{
                            Db::name('admin_upload')->where('id',$new_url_info['id'])->update($insert);
                        }
                    }else{
                        echojson($res);exit;
                    }
                }
                $new_imgs[] = $new_url;
            }else{
                $new_imgs[] = $img;
            }
        }
        return $new_imgs;
    }
    //转换为微信格式数据
    public function getwxdata($proid,$info,$attrs_value=[]){
        $guige = Db::name('channels_product_guige')->where('aid',aid)->where('proid',$proid)->where('bid',bid)->select()->toArray();
        $data = [];
        $data['title'] = $info['name'];
        $data['short_title'] = $info['sub_title'];
        $imgs_arr = explode(',',$info['pics']);
        $data['head_imgs'] = $imgs_arr;
        $data['deliver_method'] = (int)$info['deliver_method'];
        //商品详情
        if($info['desc_pics'] || $info['desc']){
            $desc_info = [
                'imgs' => explode(',',$info['desc_pics']),
                'desc' => $info['desc'],
            ];
        }
        $data['desc_info'] = $desc_info;
        //商品分类
//        $cats = [
//            ['cat_id'=>$info['cat1']],
//            ['cat_id'=>$info['cat2']],
//            ['cat_id'=>$info['cat3']],
//        ];
        $cat_ids = explode(',',$info['cat_ids']);
        $cats = [];
        foreach($cat_ids as $cat_id){
            $cats[] = ['cat_id'=>$cat_id];
        }
        $data['cats'] = [];
        $data['cats_v2'] = $cats;
        //商品参数
        $attrs = json_decode($info['attrs'],true);
        foreach($attrs as $k=>$v){
            if(!$v['attr_value']){
                unset($attrs[$k]);
            }
        }
        $attrs = array_values($attrs);

        $data['attrs'] = $attrs;
        $data['spu_code'] = $info['procode'];
        $data['brand_id'] = $info['brand_id']?:'2100000000';
        //商品资质
        $product_qua_infos = [];
        $info['product_qua_infos'] = json_decode($info['product_qua_infos'],true);
        if($info['product_qua_infos']){
            foreach($info['product_qua_infos'] as $qua_id=>$qua_url){
                if($qua_url){
                    $qua_url_arr = explode(',',$qua_url);
                    $qua_url = Db::name('admin_upload')->where('url','in',$qua_url_arr)->column('channels_file_id');
                    $product_qua_infos[] = [
                        'qua_id' => $qua_id,
                        'qua_url' => $qua_url
                    ];
                }
            }
        }
        $data['product_qua_infos'] = $product_qua_infos;
        //运费信息
        if($data['deliver_method']==0){
            $express_info = [
                'template_id' => (string)$info['template_id']?:'',
                'weight' => (int)$info['weight'],
            ];
            $data['express_info'] = $express_info;
        }

        $data['aftersale_desc'] = $info['aftersale_desc'];
        //限购周期
        $limited_info = [
            'period_type' => (int)$info['period_type'],
            'limited_buy_num' => (int)$info['limited_buy_num']
        ];
        $data['limited_info'] = $limited_info;
        //七天无理由退货
        $extra_service = [
            'seven_day_return' => (int)$info['seven_day_return'],
            'pay_after_use' => (int)$info['pay_after_use'],
            'freight_insurance' => (int)$info['freight_insurance'],
            'fake_one_pay_three' => (int)$info['fake_one_pay_three'],//假一赔三
            'damage_guarantee' => (int)$info['damage_guarantee']//坏损包退
        ];
        $data['extra_service'] = $extra_service;
        //商品规格
        $skus = [];
        foreach($guige as $k=>$v){
            $skus[] = [
                'sku_id' => $v['sku_id'],
                'thumb_img' => $v['pic'],
                'sale_price' => (float)bcmul($v['sell_price'],100,0),
                'stock_num' => $v['stock'],
                'sku_code' => $v['procode'],
                'sku_attrs' => json_decode($v['sku_attrs'],true),
                'sku_deliver_info' => [
                    'stock_type' => 0
                ],
            ];
        }
        $data['skus'] = $skus;
        $data['listing'] = (int)$info['status'];

        //售后地址id
        $data['after_sale_info'] = [
            'after_sale_address_id' => (int)$info['after_sale_address_id']
        ];

        return $data;
    }

    //商品规格列表
    public function sku_lists()
    {
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[]= ['p.aid','=',aid];
            $where[]= ['p.bid','=',bid];
            $where[] = ['p.appid','=',$this->appid];
            if(input('product_id')){
                $where[] = ['p.product_id','=',input('product_id')];
            }
            $field = 'g.*,p.name product_name,p.cat1,p.cat2,p.cat3';
            $list = Db::name("channels_product_guige")
                ->alias('g')
                ->join('channels_product p','g.proid=p.id')
                ->where($where)
                ->field($field)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $catname = Db::name('channels_category')->where('appid',$this->appid)->where('cat_id','in',[$v['cat1'],$v['cat2'],$v['cat3']])->column('name');
                $catname = implode('-',$catname);
                $data[$k]['cname'] = $catname;
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('product_id',input('product_id'));
        return View::fetch();
    }
    public function getSkuInfo(){
        $id = input('id');
        $where = [];
        $where['p.aid'] = aid;
        $where['p.bid'] = bid;
        $where['p.appid'] = $this->appid;
        $where['g.id'] = $id;
        $field = 'g.*,p.name product_name,p.cat1,p.cat2,p.cat3';
        $info = Db::name("channels_product_guige")
            ->alias('g')
            ->join('channels_product p','g.proid=p.id')
            ->where($where)
            ->field($field)
            ->find();
        return json(['status'=>1,'data'=>$info]);
    }
    public function stock_log(){
        $id = input('id');
        $info = Db::name('channels_product')->where('id',$id)->find();
        $op_type_arr = \app\common\WxChannels::stock_op_type;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where['s.aid'] = aid;
            $where['s.bid'] = bid;
            $where['s.appid'] = $this->appid;
            if($info){
                $where['s.product_id'] = $info['product_id'];
            }
            $field = 's.*,p.name,g.name guige_name';
            $list = Db::name("channels_product_stock")
                ->alias('s')
                ->join('channels_product p','s.product_id=p.product_id')
                ->join('channels_product_guige g','s.sku_id=g.sku_id')
                ->where($where)
                ->field($field)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['type_str'] = $op_type_arr[$v['op_type']];
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('info',$info);
        View::assign('op_type_arr',$op_type_arr);
        return View::fetch();
    }

    //同步商品列表
    public function asyncAllStock()
    {
        Db::startTrans();
        try {
            $guige_id = input('guige_id');
            $info = Db::name('channels_product_guige')->where('id',$guige_id)->find();
            $s_time = input('s_time');
            $e_time = input('e_time');
            $s_time = $s_time?strtotime(input('s_time')):(time()-86400);
            $e_time = $e_time?strtotime(input('e_time')):time();
            $next_key = input('next_key');
            $params = [
                'product_id' => $info['product_id'],
                'sku_id' => $info['sku_id'],
                'stock_type' => 0,
                'begin_time' => $s_time,
                'end_time' => $e_time,
                'page_size' => 50
            ];
            if($next_key){
                $params['next_key'] = $next_key;
            }
            //获取库存流水
            $res = \app\common\WxChannels::getProductStockFlow(aid,bid, $this->appid, $params);
            if($res['status'] == 0){
                return json($res);
            }
            $lists = $res['data'];
            foreach ($lists as $v) {
                $data = [];
                $data['aid'] = aid;
                $data['bid'] = bid;
                $data['appid'] = $this->appid;
                $data['product_id'] = $info['product_id'];
                $data['sku_id'] = $info['sku_id'];
                $data['amount'] = $v['amount'];
                $data['op_type'] = $v['op_type'];
                $data['update_time'] = $v['update_time'];
                $data['ext_info'] = jsonEncode($v['ext_info']);
                Db::name('channels_product_stock')->insert($data);
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'continue_flag'=>$res['continue_flag']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    public function chooseproduct(){
        return View::fetch();
    }
    public function getproductinfo(){
        $id = input('id', 0);
        $info = Db::name("channels_product")->where('id', $id)->find();
        return json(['status'=>1,'data'=>$info]);
    }

    //获取商品分享连接
    public function get_shareurl(){
        $id = input('id');
        $product_id = input('product_id');
        $sharer_product_type = input('sharer_product_type');
        if($sharer_product_type==1){
            $res = \app\common\WxChannels::getProductH5url(aid,bid,$this->appid,$product_id);
        }
        if($sharer_product_type==2){
            $res = \app\common\WxChannels::getProductTaglink(aid,bid,$this->appid,$product_id);
        }
        if($sharer_product_type==3){
            $res = \app\common\WxChannels::getProductQrcode(aid,bid,$this->appid,$product_id);
        }

        if(!$res['status']){
            return json($res);
        }else{
            return json(['status'=>1,'msg'=>'创建成功','share_url'=>$res['data']]);
        }
    }

    //选择商品分类的页面
    public function catshtml(){
        if(request()->isAjax()){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            $field = 'cat_id id,f_cat_id pid,name title,need_to_apply';
            $cates = Db::name('channels_category')->where($where)->field($field)->select()->toArray();
            foreach($cates as $k=>$v){
                $v['disabled '] = false;
                if($v['need_to_apply']==1){
                    $v['title'] = $v['title'].'【未申请】';
                    $v['disabled'] = true;
                    $v['href'] = url('WxChannelsCategory/apply').'/id/'.$v['id'];
                    $v['target'] = '_blank';
                }
                $cates[$k] = $v;
            }

            $data = $this->getCategoryTree($cates);
            return json(['code' => 0, 'msg' => '查询成功', 'count' => count($data), 'data' => $data]);
        }else{
            return View::fetch();
        }
    }
    /**
     * 获取分类树状结构
     * @return array
     */
    public function getCategoryTree($categories)
    {
        // 创建结果数组和引用数组
        $tree = [];
        $refer = [];

        // 第一遍遍历：创建所有节点的引用
        foreach ($categories as $key => $value) {
            $refer[$value['id']] = &$categories[$key];
        }

        // 第二遍遍历：构建树结构
        foreach ($categories as $key => $value) {
            $parentId = $value['pid'];
            if ($parentId == 0 || !isset($refer[$parentId])) {
                // 如果没有父节点或父节点不存在，直接添加到树根
                $tree[] = &$categories[$key];
            } else {
                // 如果有父节点，将自己添加到父节点的children中
                if (!isset($refer[$parentId]['child'])) {
                    $refer[$parentId]['child'] = [];
                }
                $refer[$parentId]['child'][] = &$categories[$key];
            }
        }

        return $tree;
    }
    //根据分类id获取父级id
    public function getParentCatIds(){
        $cat_id = input('id');
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['appid','=',$this->appid];
        $where[] = ['cat_id','=',$cat_id];
        $cats = [];
        $info = Db::name('channels_category')->where($where)->find();
        $parent_id = $info['f_cat_id'];
        $cats[] = $info;
        while ($parent_id>0){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            $where[] = ['cat_id','=',$parent_id];
            $info = Db::name('channels_category')->where($where)->find();
            $parent_id = $info['f_cat_id'];
            $cats[] = $info;
        }
        $cats = array_reverse($cats);
        return json(['status' => 1, 'msg' => '查询成功', 'data' => $cats?:[]]);
    }
}
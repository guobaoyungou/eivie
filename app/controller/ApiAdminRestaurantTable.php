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

//管理员中心 - 餐桌
namespace app\controller;
use think\facade\Db;
class ApiAdminRestaurantTable extends ApiAdmin
{	
	public function index(){
        $where[] = ['aid', '=', aid];
        $where[] = ['bid', '=', bid];

		$pernum = 20;
		$pagenum = input('param.pagenum');
		if(!$pagenum) $pagenum = 1;
        $keyword = input('param.keyword');
        if($keyword) $where[] = ['name', 'like', '%'.$keyword.'%'];
        $cid = input('param.cid');
        if($cid) $where[] = ['cid', '=', $cid];
        $status = input('param.tableStatus');
        if($status !== '' && !is_null($status)) $where[] = ['status', '=', $status];
		$datalist = Db::name('restaurant_table')->where($where)->order('sort desc,id desc')->select();
		if(!$datalist) $datalist = array();

		$rdata = [];
        $rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}

    //编辑
    public function edit(){
        if(input('param.id')){
            $info = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
        }else{
            $info = ['id'=>'','status'=>0, 'canbook' => 1, 'sort' => 0];
        }

        $rdata = [];
        $rdata['info'] = $info;
        return $this->json($rdata);
    }

    //编辑
    public function save(){
        if(input('post.id')) $cate = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
        $info = input('post.info/a');
        $data = array();
        $data['name'] = $info['name'];
        $data['cid'] = $info['cid'];
        $data['pic'] = $info['pic'];
        $data['seat'] = $info['seat'] ? intval($info['seat']) : 0;
        $data['canbook'] = $info['canbook'];
        $data['sort'] = $info['sort'];
        $data['status'] = $info['status'];

        if($cate){
            $data['update_time'] = time();
            Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$cate['id'])->update($data);
            $id = $cate['id'];
            \app\common\System::plog('餐饮餐桌编辑'.$id);
        }else{
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['create_time'] = time();
            $id = Db::name('restaurant_table')->insertGetId($data);
            \app\common\System::plog('餐饮餐桌添加'.$id);
        }

        return json(['status'=>1,'msg'=>'操作成功']);
    }

    //删除
    public function del(){
        $id = input('post.id/d');
        Db::name('restaurant_table')->where(['aid'=>aid,'bid'=>bid,'id'=>$id])->delete();
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }

    public function detail() {
        if(!input('param.id')){
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $order_goods_sum = 0;
        $info = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();

        //关联订单，已点菜品
        if($info['orderid']) {
            $order = Db::name('restaurant_shop_order')->where('aid',aid)->where('bid',bid)->where('tableid',$info['id'])->where('id', $info['orderid'])->find();
            if($order['mid']){
                $member = Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->field('nickname,tel')->find();
                $order['linkman'] = $order['linkman']??$member['nickname'];
                $order['tel'] = $order['tel']??$member['tel'];
            }
            $order_goods =  Db::name('restaurant_shop_order_goods')->where('aid',aid)->where('bid',bid)->where('orderid', $info['orderid'])->select()->toArray();
            $order_goods_sum =0 ;
            foreach($order_goods as $key=>$val){
                $order_goods_sum+= $val['num'];
                }
            }
        $restaurant_shop_set = Db::name('restaurant_shop_sysset')->where('aid',aid)->field('tea_fee_text')->where('bid',bid)-> find();
        $info['tea_fee_text'] = $restaurant_shop_set['tea_fee_text'];
        $rdata = [];
        $info['create_time'] = date('Y-m-d H:i', $info['create_time']);
        $rdata['info'] = $info;
        $rdata['order'] = $order ? $order : [];
        $rdata['order_goods'] = $order_goods ? $order_goods : [];
        $rdata['order_goods_sum'] = $order_goods_sum;
        return $this->json($rdata);
    }
    //换桌
    public function change()
    {
        if(!input('param.new/d') || !input('param.origin/d')){
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $info = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('param.origin/d'))->find();
        if(empty($info)) {
            return $this->json(['status'=>0,'msg'=>'餐桌不存在']);
        }
        $new_table = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('param.new/d'))->find();
        if(empty($new_table)) {
            return $this->json(['status'=>0,'msg'=>'餐桌不存在']);
        }
        if($new_table['status'] !== 0 || $new_table['orderid']) {
            return $this->json(['status'=>0,'msg'=>'餐桌状态不可用']);
        }
        Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('param.origin/d'))->update(['status' => 0, 'orderid' => 0]);
        Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',input('param.new/d'))->update(['status' => 2, 'orderid' => $info['orderid']]);
        if($info['orderid']) {
            Db::name('restaurant_shop_order')->where('aid',aid)->where('bid',bid)->where('tableid',input('param.origin/d'))
                ->where('id', $info['orderid'])->update(['tableid' => $new_table['id']]);
        }

        return $this->json(['status'=>1,'msg'=>'换桌成功']);
    }
    //清台
    public function clean()
    {
        if(!input('param.tableId/d')){
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $tableid = input('param.tableId/d');
        $info = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->find();
        if(empty($info)) {
            return $this->json(['status'=>0,'msg'=>'餐桌不存在']);
        }
        if($info['status'] == 0) {
            Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->update(['orderid' => 0]);
            return $this->json(['status'=>0,'msg'=>'当前无需清台']);
        }
        if($info['orderid']) {
            $order = Db::name('restaurant_shop_order')->where('aid',aid)->where('bid',bid)->where('tableid',$tableid)
                ->where('id', $info['orderid'])->find();
            if($order && $order['status'] == 0 && $order['totalprice'] > 0)
            return $this->json(['status'=>0,'msg'=>'请先完成订单结算']);
        }
        Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->update(['status' => 3, 'orderid' => 0]);

        return $this->json(['status'=>1,'msg'=>'清理完后请切换餐桌状态']);
    }
    //清台完成 设为空闲中
    public function cleanOver()
    {
        if(!input('param.tableId/d')){
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $tableid = input('param.tableId/d');
        $info = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->find();
        if(empty($info)) {
            return $this->json(['status'=>0,'msg'=>'餐桌不存在']);
        }
        if($info['status'] == 2) {
            return $this->json(['status'=>0,'msg'=>'就餐中，请先结算然后清台']);
        }
        Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->update(['status' => 0, 'orderid' => 0]);

        return $this->json(['status'=>1,'msg'=>'设置成功']);
    }
    public function closeOrder()
    {
        if(!input('param.tableId/d')){
            return $this->json(['status'=>0,'msg'=>'参数错误']);
        }
        $tableid = input('param.tableId/d');
        $info = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->find();
        if(empty($info)) {
            return $this->json(['status'=>0,'msg'=>'餐桌不存在']);
        }
        if($info['orderid']) {
            $order = Db::name('restaurant_shop_order')->where('aid',aid)->where('bid',bid)->where('tableid',$tableid)
                ->where('id', $info['orderid'])->find();
            $orderid = $info['orderid'];
            //加库存
            $oglist = Db::name('restaurant_shop_order_goods')->where('aid',aid)->where('bid',bid)->where('orderid',$orderid)->select()->toArray();
            foreach($oglist as $og){
                Db::name('restaurant_product_guige')->where('aid',aid)->where('id',$og['ggid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
                Db::name('restaurant_product')->where('aid',aid)->where('bid',bid)->where('id',$og['proid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("sales-".$og['num'])]);
            }

            //优惠券抵扣的返还
            if($order['coupon_rid'] > 0){
                \app\common\Coupon::refundCoupon2(aid,$order['mid'],$order['coupon_rid'],$order);
            }
            $rs = Db::name('restaurant_shop_order')->where('id',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4]);
            Db::name('restaurant_shop_order_goods')->where('orderid',$orderid)->where('aid',aid)->where('bid',bid)->update(['status'=>4]);
        }
        Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->where('id',$tableid)->update(['status' => 0, 'orderid' => 0]);

        \app\common\System::plog('餐饮关闭订单，桌号:'.$info['name']);
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }
    public function timingPause(){
        }
    
    //结算计时费用
    public function settleTimingMoney(){
        }
}
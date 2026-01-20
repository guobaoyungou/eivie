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

// +----------------------------------------------------------------------
// | 商城 商品卡密
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ShopCode extends Common
{
    public function initialize(){
		parent::initialize();
	}
	
	//卡密
	public function codelist(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'id desc';
			}
			$where = [];
			$where[] = ['aid','=',aid];
			if(bid!=0){
				$where[] = ['bid','=',bid];
			}
			if(input('param.proid')){
				$where[] = ['proid','=',input('param.proid/d')];
			}
			if(input('param.content')) $where[] = ['content','=',input('param.content')];
			if(input('param.ordernum')) $where[] = ['ordernum','=',input('param.ordernum')];
			if(input('param.nickname')) $where[] = ['nickname','like','%'.input('param.nickname').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			$count = 0 + Db::name('shop_codelist')->where($where)->count();
			$data = Db::name('shop_codelist')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			//foreach($data as $k=>$v){
			//	$data[$k]['name'] = Db::name('hongbao')->where('id',$v['hid'])->value('name');
			//}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
	}
	//导入
	public function importexcel(){
		set_time_limit(0);
		ini_set('memory_limit',-1);
		$file = input('post.upload_file');
		$exceldata = $this->import_excel($file);
		$proid = input('param.proid/d');

		$product = Db::name('shop_product')->where('id',$proid)->find();
		if(bid!=0 && $product['bid'] != bid) return json(['status'=>0,'msg'=>'无权限操作']);
		
		$insertnum = 0;
		$chongfunum = 0;
		foreach($exceldata as $data){
			$indata = [];
			$indata['aid'] = aid;
			$indata['bid'] = $product['bid'];
			$indata['proid'] = $proid;
			$indata['content'] = $data[0];

			$hasinfo = Db::name('shop_codelist')->where($indata)->find();
			if($hasinfo){
				$chongfunum++;
			}else{
				$indata['createtime'] = time();
				Db::name('shop_codelist')->insert($indata);
				$insertnum++;
			}
		}
		\app\common\System::plog('导入商城商品卡密'.$proid);
		if($chongfunum > 0){
			return json(['status'=>1,'msg'=>'成功新增'.$insertnum.'条数据，重复'.$chongfunum.'条数据']);
		}else{
			return json(['status'=>1,'msg'=>'成功新增'.$insertnum.'条数据']);
		}
	}
	//生成
	public function makecode(){
		$proid = input('post.proid');
		$product = Db::name('shop_product')->where('id',$proid)->find();
		if(bid!=0 && $product['bid'] != bid) return json(['status'=>0,'msg'=>'无权限操作']);
		$makecount = input('post.makecount/d');
		$codelength = input('post.codelength/d');
		$codetype = input('post.codetype');
		if($makecount < 1 || $makecount > 5000){
			return json(['status'=>0,'msg'=>'每次生成数量须在5000以内']);
		}
		if($codelength <1 ){
			return json(['status'=>0,'msg'=>'抽奖码长度须必须大于等于1']);
		}
		if($codetype == 1 && $codelength > 20 ){
			return json(['status'=>0,'msg'=>'纯数字类型长度须小于等于20']);
		}else if($codetype == 2 && $codelength > 52 ){
			return json(['status'=>0,'msg'=>'大小写字母类型长度须小于等于52']);
		}else if($codetype == 3 && $codelength > 26 ){
			return json(['status'=>0,'msg'=>'小写字母类型长度须小于等于26']);
		}else if($codetype == 4 && $codelength > 26 ){
			return json(['status'=>0,'msg'=>'大写字母类型长度须小于等于26']);
		}else if($codetype == 5 && $codelength > 35 ){
			return json(['status'=>0,'msg'=>'数字+小写字母长度须小于等于35']);
		}else if($codetype == 6 && $codelength > 35 ){
			return json(['status'=>0,'msg'=>'数字+大写字母长度须小于等于35']);
		}
		$successnum = 0;
		for($i=0;$i<$makecount;$i++){
            $randstr = make_rand_code($codetype, $codelength);
			$data = [];
			$data['aid'] = aid;
			$data['bid'] = $product['bid'];
			$data['proid'] = $proid;
			$data['content'] = $randstr;
			$hasinfo = Db::name('shop_codelist')->where($data)->find();
			if(!$hasinfo){
				$data['createtime'] = time();
				Db::name('shop_codelist')->insert($data);
				$successnum++;
			}
		}
		\app\common\System::plog('生成商城商品卡密'.$proid);
		return json(['status'=>1,'msg'=>'成功生成'.$successnum.'条数据']);
	}
	//卡密导出
	public function codelistexcel(){
		$where = [];
		$where[] = ['aid','=',aid];
		if(bid!=0){
			$where[] = ['bid','=',bid];
		}
		if(input('param.proid')){
			$where[] = ['proid','=',input('param.proid/d')];
		}
		$list = Db::name('shop_codelist')->where($where)->select()->toArray();
		
		$title = array();
		$title[] = '序号';
		$title[] = '卡密';
		$title[] = '订单号';
		$title[] = '昵称';
		$title[] = '购买时间';
		$title[] = '状态';
		//$title[] = '备注';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['id'];
			$tdata[] = $v['content'];
			$tdata[] = $v['ordernum'];
			$tdata[] = $v['nickname'];
			if($v['status']==1){
				$tdata[] = date('Y-m-d H:i:s',$v['buytime']);
			}else{
				$tdata[] = '';
			}
			$status = '';
			if($v['status']==1){
				$status = '已售出';
			}elseif($v['status']==0){
				$status = '未售出';
			}
			$tdata[] = $status;
			//$tdata[] = $v['remark'];
			$data[] = $tdata;
		}
		$this->export_excel($title,$data);
	}
	//删除
	public function codelistdel(){
		$ids = input('post.ids/a');
		$where = [];
		$where[] = ['aid','=',aid];
		if(bid != 0){
			$where[] = ['bid','=',bid];
		}
		$where[] = ['id','in',$ids];
		Db::name('shop_codelist')->where($where)->delete();
		\app\common\System::plog('删除商城商品卡密'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
}
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
// | 分享海报
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class MemberPoster extends Common
{
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
    public function index(){
		$type = input('param.type') ? input('param.type') : $this->platform[0];
		if(input('param.op') == 'add'){
			$data = [];
			$data['aid'] = aid;
			$data['type'] = 'index';
			$data['platform'] = $type;
			$data['createtime'] = time();
			$data['content'] = jsonEncode([
				'poster_bg' => PRE_URL.'/static/imgsrc/posterbg.jpg',
				'poster_data' =>[
					['left' => '30px','top' => '70px','type' => 'img','width' => '285px','height' => '285px','src' => PRE_URL.'/static/imgsrc/picture-1.jpg'],
					['left' => '30px','top' => '370px','type' => 'textarea','width' => '286px','height' => '47px','size' => '16px','color' => '#000','content' => '商城系统'],
					['left' => '34px','top' => '452px','type' => 'head','width' => '47px','height' => '47px','radius' => '100'],
					['left' => '89px','top' => '459px','type' => 'text','width' => '50px','height' => '18px','size' => '16px','color' => '#333333','content' => '[昵称]'],
					['left' => '90px','top' => '484px','type' => 'text','width' => '98px','height' => '14px','size' => '12px','color' => '#B6B6B6','content' => '推荐您加入'],
					['left' => '221px','top' =>'446px','type' => ($type=='wx' ? 'qrwx' : 'qrmp'),'width' => '94px','height' => '94px','size' => ''],
				]
			]);
			$data['guize'] = "第一步、转发链接或图片给微信好友；\r\n第二步、从您转发的链接或图片进入商城的好友，系统将自动锁定成为您的客户, 他们在商城中购买商品，您就可以获得佣金；\r\n第三步、您可以在会员中心查看【我的团队】和【分销订单】，好友确认收货后佣金方可提现。";
			$id = Db::name('admin_set_poster')->insertGetId($data);
		}else{
			$id = input('param.id');
		}
		if($id){
			$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','index')->where('platform',$type)->where('id',$id)->order('id')->find();
		}else{
			$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','index')->where('platform',$type)->order('id')->find();
		}
		$posterdata = json_decode($posterset['content'],true);
		
		$guize = $posterset['guize'];

		$poster_bg = $posterdata['poster_bg'];
		$poster_data = $posterdata['poster_data'];

		$posterlist = Db::name('admin_set_poster')->field('id')->where('aid',aid)->where('type','index')->where('platform',$type)->order('id')->select()->toArray();

		View::assign('type',$type);
		View::assign('poster_bg',$poster_bg);
		View::assign('poster_data',$poster_data);
		View::assign('guize',$guize);
		View::assign('posterlist',$posterlist);
		View::assign('posterid',$posterset['id']);
		return View::fetch();
    }
	public function save(){
		$type = input('param.type') ? input('param.type') : $this->platform[0];
		$poster_bg = input('post.poster_bg');
		$poster_data = input('post.poster_data');
		$data_index = ['poster_bg'=>$poster_bg,'poster_data'=>json_decode($poster_data)];
		$guize = input('post.guize');
		Db::name('admin_set_poster')->where('aid',aid)->where('id',input('param.posterid'))->update(['content'=>json_encode($data_index),'guize'=>$guize]);
		if(input('post.clearhistory') == 1){
			Db::name('member_poster')->where('aid',aid)->where('posterid',input('param.posterid'))->delete();
			$msg = '保存成功!';
		}else{
			$msg ='保存成功';
		}
		\app\common\System::plog('编辑会员分销海报');
		return json(['status'=>1,'msg'=>$msg,'url'=>(string)url('index').'/type/'.$type.'/id/'.input('param.posterid')]);
	}
	public function delposter(){
		Db::name('admin_set_poster')->where('aid',aid)->where('id',input('param.posterid'))->delete();
		\app\common\System::plog('删除会员分销海报'.input('param.posterid'));
		return json(['status'=>1,'msg'=>'删除成功','url'=>(string)url('index').'/type/'.input('param.type')]);
	}
}
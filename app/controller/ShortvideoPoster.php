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
// | 短视频海报
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ShortvideoPoster extends Common
{
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
        $this->defaultSet();
	}
    public function index(){
        
		$type = input('param.type') ? input('param.type') : $this->platform[0];
		$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','shortvideo')->where('platform',$type)->order('id')->find();
		if(!$posterset){
			$data_product_mp = jsonEncode([
				'poster_bg' => PRE_URL.'/static/imgsrc/posterbg.jpg',
				'poster_data' => [
					["left"=>"13px","top"=>"12px","type"=>"pro_img","width"=>"318px","height"=>"453px"],
					["left"=>"29px","top"=>"472px","type"=>"head","width"=>"38px","height"=>"38px","radius"=>"100"],
					["left"=>"82px","top"=>"483px","type"=>"text","width"=>"50px","height"=>"18px","size"=>"16px","color"=>"#333333","content"=>"[昵称]"],
					["left"=>"234px","top"=>"475px","type"=>"qrmp","width"=>"77px","height"=>"77px","size"=>""],
					["left"=>"27px","top"=>"517px","type"=>"textarea","width"=>"188px","height"=>"40px","size"=>"14px","color"=>"#000","content"=>"[视频名称]"]
				]
			]);
			$data_product_wx = jsonEncode([
				'poster_bg' => PRE_URL.'/static/imgsrc/posterbg.jpg',
				'poster_data' => [
					["left"=>"13px","top"=>"12px","type"=>"pro_img","width"=>"318px","height"=>"453px"],
					["left"=>"29px","top"=>"472px","type"=>"head","width"=>"38px","height"=>"38px","radius"=>"100"],
					["left"=>"82px","top"=>"483px","type"=>"text","width"=>"50px","height"=>"18px","size"=>"16px","color"=>"#333333","content"=>"[昵称]"],
					["left"=>"234px","top"=>"475px","type"=>"qrwx","width"=>"77px","height"=>"77px","size"=>""],
					["left"=>"27px","top"=>"517px","type"=>"textarea","width"=>"188px","height"=>"40px","size"=>"14px","color"=>"#000","content"=>"[视频名称]"]
				]
			]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'mp','content'=>$data_product_mp]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'wx','content'=>$data_product_wx]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'alipay','content'=>$data_product_mp]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'baidu','content'=>$data_product_mp]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'toutiao','content'=>$data_product_mp]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'qq','content'=>$data_product_mp]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'h5','content'=>$data_product_mp]);
			Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'shortvideo','platform'=>'app','content'=>$data_product_mp]);
			$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','shortvideo')->where('platform',$type)->order('id')->find();
		}
		$posterdata = json_decode($posterset['content'],true);
		$poster_bg = $posterdata['poster_bg'];
		$poster_data = $posterdata['poster_data'];

		View::assign('type',$type);
		View::assign('poster_bg',$poster_bg);
		View::assign('poster_data',$poster_data);
		return View::fetch();
    }
	public function save(){
		$type = input('param.type') ? input('param.type') : $this->platform[0];
		$poster_bg = input('post.poster_bg');
		$poster_data = input('post.poster_data');
		$data_index = ['poster_bg'=>$poster_bg,'poster_data'=>json_decode($poster_data)];
		$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','shortvideo')->where('platform',$type)->order('id')->find();
		Db::name('admin_set_poster')->where('id',$posterset['id'])->update(['content'=>json_encode($data_index)]);
		if(input('post.clearhistory') == 1){
			Db::name('member_poster')->where('aid',aid)->where('type','shortvideo')->where('posterid',$posterset['id'])->delete();
			$msg = '保存成功';
		}else{
			$msg ='保存成功';
		}
		\app\common\System::plog('积分商城海报设置');
		return json(['status'=>1,'msg'=>$msg,'url'=>true]);
	}
    function defaultSet(){
        $set = Db::name('shortvideo_sysset')->where('aid',aid)->find();
        if(!$set){
            Db::name('shortvideo_sysset')->insert(['aid'=>aid]);
        }
    }
}
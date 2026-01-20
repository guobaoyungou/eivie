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

//管理员中心 - 客服
namespace app\controller;
use think\facade\Db;
class ApiAdminKefu extends ApiAdmin
{	
	//首页 聊天列表
	public function index(){
		$where = "aid= ".aid.' and bid='.bid.' and mid>0';
		if(input('param.keyword')){
			$where .=" and nickname like '%".input('param.keyword')."%'";
		}
		$pernum = 50;
		$pagenum = input('param.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::query("SELECT * FROM ( select * from ".table_name('kefu_message')." ORDER BY id desc limit 100000000000)a  WHERE  ".$where." GROUP BY `mid` ORDER BY id DESC LIMIT ".(($pagenum-1)*$pernum).",{$pernum}");
		//dump(db()->getlastsql());
		if(!$datalist) $datalist = array();
		foreach($datalist as $k=>$v){
			$noreadcount = Db::name('kefu_message')->where(['aid'=>aid,'bid'=>bid,'mid'=>$v['mid'],'isreply'=>0,'isread'=>0])->count();
			$datalist[$k]['noreadcount'] = $noreadcount;
			$datalist[$k]['showtime'] = getshowtime($v['createtime']);
		}
		if(request()->isPost()){
			return ['status'=>1,'data'=>$datalist];
		}
		$token = $this->user['random_str'];
		
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
		$rdata['uid'] = uid;
		$rdata['token'] = $this->user['random_str'];
		$rdata['auth_data'] = $this->auth_data;
		return $this->json($rdata);
	}
	public function message(){
		$mid = input('param.mid/d');
		if(!$mid) return json(['status'=>-4,'msg'=>'用户不存在']);
		$tomember = Db::name('member')->where(['id'=>$mid])->field('id,nickname,headimg,tel')->find();
		if(!$tomember) return json(['status'=>-4,'msg'=>'用户不存在']);
		$rdata = [];
		$rdata['uid'] = $this->uid;
		$rdata['token'] = $this->user['random_str'];
		$rdata['tomember'] = $tomember;
		$rdata['nowtime'] = time();
		return $this->json($rdata);
	}
	//获取聊天内容
	public function getmessagelist(){
		$pagenum = input('post.pagenum');
		$mid = input('post.mid/d');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		$where[] = ['mid','=',$mid];
		if(input('param.keyword')){
			$where[] = ['content','like','%'.input('param.keyword').'%'];
		}
		$datalist = Db::name('kefu_message')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$v){
			$datalist[$k]['showtime'] = getshowtime($v['createtime']);
			$datalist[$k]['content'] = getshowcontent($v['content']);
		}
		$datalist = array_reverse($datalist);
		Db::name('kefu_message')->where($where)->where('isread',0)->update(['isread'=>1]);
		return $this->json(['status'=>1,'data'=>$datalist]);
	}
}
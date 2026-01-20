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
class ApiKefu extends ApiCommon
{
	public function initialize(){
		parent::initialize();
		$this->checklogin();
	}
	//首页 聊天列表
	public function index(){
		$config = include(ROOT_PATH.'config.php');
		$authtoken = $config['authtoken'];
		$token = md5(md5($authtoken.mid));
		return $this->json(['token'=>$token,'nowtime'=>time()]);
	}
	//获取聊天内容
	public function getmessagelist(){
		$pagenum = input('post.pagenum');
		$bid = input('post.bid');
		if(!$bid) $bid = 0;
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',$bid];
		$where[] = ['mid','=',mid];
		$datalist = Db::name('kefu_message')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		if($pagenum==1 && !$datalist){ //初次进入自动发送
			if($bid == 0){
				$set = Db::name('admin_set')->where('aid',aid)->find();
			}else{
				$set = Db::name('business')->field('name,logo')->where('id',$bid)->find();
			}
			$insertdata = [];
			$insertdata['aid'] = aid;
			$insertdata['mid'] = mid;
			$insertdata['bid'] = $bid;
			$insertdata['uid'] = 0;
			$insertdata['nickname'] = $this->member['nickname'];
			$insertdata['headimg'] = $this->member['headimg'];
			$insertdata['tel'] = $this->member['tel'];
			$insertdata['unickname'] = $set['name'];
			$insertdata['uheadimg'] = $set['logo'];
			$insertdata['msgtype'] = 'text';
			$insertdata['content'] = '您好，'.$set['name'].'竭诚为您服务！';
			$insertdata['createtime'] = time();
			$insertdata['isreply'] = 1;
			$insertdata['isread'] = 1;
			$insertdata['platform'] = platform;
			Db::name('kefu_message')->insert($insertdata);
			$datalist = Db::name('kefu_message')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		}
		foreach($datalist as $k=>$v){
			$datalist[$k]['showtime'] = getshowtime($v['createtime']);
			$datalist[$k]['content'] = getshowcontent($v['content']);
		}
		$datalist = array_reverse($datalist);
		Db::name('kefu_message')->where($where)->where('isreply',1)->where('isread',0)->update(['isread'=>1]);
		return $this->json(['status'=>1,'data'=>$datalist]);
	}
	//改为已读
	public function isread(){
		$mid = input('post.mid/d');
		Db::name('kefu_message')->where('aid',aid)->where('mid',$mid)->where('isreply',0)->where('isread',0)->update(['isread'=>1]);
		return $this->json(['status'=>1]);
	}
}
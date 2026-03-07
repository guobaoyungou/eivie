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
// | 后台账号 子账号
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class User extends Common
{
	//账号列表
    public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'id';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',bid];
			if(input('param.un')) $where[] = ['un','like','%'.input('param.un').'%'];
			if($this->user['isadmin'] == 0){
				if($this->user['groupid'] == 0){
					$where[] = ['addid','=',$this->user['id']];
				}else{
					$thisgroup = Db::name('admin_user_group')->where('id',$this->user['groupid'])->find();
					$where = [];
					$where[] = ['aid','=',aid];
					$where[] = ['bid','=',bid];
					$groupids = Db::name('admin_user_group')->where($where)->where("`sort`<".$thisgroup['sort']." or (`sort`={$thisgroup['sort']} and id>{$thisgroup['id']})")->column('id');
					if($groupids){
						$where[] = ['groupid','in',$groupids];
					}else{
						$where[] = Db::raw('1=0');
					}
				}
			}
			$where_or = "groupid=0 and addid=".$this->user['id'];
			$count = 0 + Db::name('admin_user')->where($where)->whereOr('id','=',$this->user['id'])->whereOr($where_or)->count();
			$data = Db::name('admin_user')->where($where)->whereOr('id','=',$this->user['id'])->whereOr($where_or)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
				if($v['mid']){
					$member = Db::name('member')->where('aid',aid)->where('id',$v['mid'])->find();
					if($member){
						$data[$k]['headimg'] = $member['headimg'];
						$data[$k]['nickname'] = $member['nickname'];
					}
				}
				if($v['groupid']){
					$data[$k]['groupname'] = Db::name('admin_user_group')->where('id',$v['groupid'])->value('name');
				}else{
					$data[$k]['groupname'] = '';
				}
				if($v['addid']){
					$data[$k]['addname'] = Db::name('admin_user')->where('id',$v['addid'])->value('un');
				}else{
					$data[$k]['addname'] = '';
				}
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
//		$set = Db::name('admin_set')->where('aid',aid)->find();
		$isadmin = $this->user['isadmin'];
		View::assign('aid',aid);
		View::assign('isadmin',$isadmin);
		View::assign('thisuserid',$this->user['id']);

		return View::fetch();
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
		}else{
			$info = [];
		}
		$mendianlist = Db::name('mendian')->where('aid',aid)->where('bid',bid)->select()->toArray();
		$auth_data = $info ? json_decode($info['auth_data'],true) : array();
		if(!$auth_data) $auth_data = array();
		$menudata = \app\common\Menu::getdata(aid,uid,false,0,bid);

		$wxauth_data = $info ? json_decode($info['wxauth_data'],true) : array();
		if(!$wxauth_data) $wxauth_data = array();
		$notice_auth_data = $info ? json_decode($info['notice_auth_data'],true) : array();
		if(!$notice_auth_data) $notice_auth_data = array();
		$hexiao_auth_data = $info ? json_decode($info['hexiao_auth_data'],true) : array();
		if(!$hexiao_auth_data) $hexiao_auth_data = array();
		$wxauth_data = $info ? json_decode($info['wxauth_data'],true) : array();
		if(!$wxauth_data) $wxauth_data = array();


		if($this->user['isadmin'] == 0){
			if($this->user['groupid'] == 0){
				$groupList = [];
			}else{
				$thisgroup = Db::name('admin_user_group')->where('id',$this->user['groupid'])->find();
				$where = [];
				$where[] = ['aid','=',aid];
				$where[] = ['bid','=',bid];
				$groupList = Db::name('admin_user_group')->where($where)->where("`sort`<".$thisgroup['sort']." or (`sort`={$thisgroup['sort']} and id>{$thisgroup['id']})")->field('id,name')->order('sort desc,id')->select()->toArray();
			}
		}else{
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',bid];
			$groupList = Db::name('admin_user_group')->where($where)->order('sort desc,id')->field('id,name')->select()->toArray();
		}

		$groupList = array_merge($groupList,[['id'=>'0','name'=>'手动选择权限']]);

		if(!$info) $info['groupid'] = $groupList[0]['id'];
		//var_dump(json_decode($this->user['wxauth_data'],true));
		if($this->user['isadmin'] != 0){
			$userlist = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->order('id')->field('id,un')->select()->toArray();
			View::assign('userlist',$userlist);
		}

		View::assign('auth_data',$auth_data);
		View::assign('notice_auth_data',$notice_auth_data);
		View::assign('hexiao_auth_data',$hexiao_auth_data);
		View::assign('wxauth_data',$wxauth_data);
		View::assign('menudata',$menudata);
		View::assign('info',$info);
		View::assign('mendianlist',$mendianlist);
		View::assign('groupList',$groupList);

        View::assign('thisuser',$this->user);
		View::assign('thisuser_showtj',$this->user['showtj']==1 || $this->user['isadmin']>0 ? 1 : 0);
		View::assign('thisuser_mdid',$this->user['mdid']);
		View::assign('thisuser_wxauth',json_decode($this->user['wxauth_data'],true));
		View::assign('thisuser_notice_auth',json_decode($this->user['notice_auth_data'],true));
		View::assign('thisuser_hexiao_auth',json_decode($this->user['hexiao_auth_data'],true));
        View::assign('restaurant_auth',strpos($this->user['wxauth_data'],'restaurant') !== false ? true : false);
		View::assign('thisuserid',$this->user['id']);
		View::assign('loginbid',$this->bid);
		return View::fetch();
	}
	public function save(){
		$info = input('post.info/a');
		$hasun = Db::name('admin_user')->where('id','<>',$info['id'])->where('un',$info['un'])->find();
		if($hasun){
			return json(['status'=>0,'msg'=>'该账号已被占用']);
		}
		if(isset($info['groupid']) && $info['groupid'] == 0){
			$info['auth_data'] = str_replace('^_^','\/*',jsonEncode(input('post.auth_data/a')));
			$info['notice_auth_data'] = jsonEncode(input('post.notice_auth_data/a'));
			$info['hexiao_auth_data'] = jsonEncode(input('post.hexiao_auth_data/a'));
			$info['wxauth_data'] = jsonEncode(input('post.wxauth_data/a'));
		}
		$info['bids'] = implode(',',input('post.bids/a'));
        $isadmin = 0;
        if($info['id']){
			if($info['pwd']!=''){
				$info['pwd'] = md5($info['pwd']);
			}else{
				unset($info['pwd']);
			}
			$oldinfo = Db::name('admin_user')->where('aid',aid)->where('id',$info['id'])->find();
			if(!$oldinfo) return json(['status'=>0,'msg'=>'账号不存在']);
			$info['isadmin'] = $isadmin==3?3:$oldinfo['isadmin'];
			Db::name('admin_user')->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑管理员账号'.$info['id']);
		}else{
			$info['pwd'] = md5($info['pwd']);
			$info['aid'] = aid;
			$info['bid'] = bid;
			$info['isadmin'] = $isadmin;
			$info['addid'] = $this->user['id'];
			$info['createtime'] = time();
			$info['random_str'] = random(16);
			$id = Db::name('admin_user')->insertGetId($info);
			\app\common\System::plog('添加管理员账号'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$id = input('post.id');
		if(is_array($id)){
			Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('id','in',$id)->delete();
		}else{
			Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('id',intval($id))->delete();
		}
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//获取绑定url
	public function getbindurl(){
		$id = input('post.id/d');
		$info = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('id',$id)->find();
		if(!$info) return json(['status'=>0,'msg'=>'未找到会员信息']);
		$token = random(32);
		cache('adminbdtoken_'.$id,$token,86400);
		$url = m_url('pagesB/index/bind?id='.$id.'&token='.$token);
		return json(['status'=>1,'msg'=>'获取成功','url'=>$url]);
	}
	//解绑
	public function jiebang(){
		$id = input('post.id/d');
        $admin_user = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('id',$id)->find();
		Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('id',$id)->update(['mid'=>0]);
        if(bid>0){
           $business = Db::name('business')->where('aid',aid)->where('id',bid)->find();
           if($business['mid'] == $admin_user['mid']){
               Db::name('business')->where('aid',aid)->where('id',bid)->update(['mid'=>0]);
           }
        }
		return json(['status'=>1,'msg'=>'解绑成功']);
	}

    public function chooseuser(){
        if(request()->isPost()){
            $data = Db::name('admin_user')->where('aid',aid)->where('id',input('post.id/d'))->find();
            return json(['status'=>1,'msg'=>'查询成功','data'=>$data]);
        }
        return View::fetch();
    }
}

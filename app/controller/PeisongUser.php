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
// | 配送员
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class PeisongUser extends Common
{
    public function initialize(){
		parent::initialize();
		$check_bid = 1;//检验商户权限
		if($check_bid){
            if(bid > 0) showmsg('无访问权限');
        }
	}
	//列表
    public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id';
			}
			$where = array();
			$where[] = ['aid','=',aid];
            if(input('param.realname')) $where[] = ['realname','like','%'.input('param.realname').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			$count = 0 + Db::name('peisong_user')->where($where)->count();
			$data = Db::name('peisong_user')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
				$member = Db::name('member')->where('id',$v['mid'])->find();
				$data[$k]['nickname'] = $member['nickname'];
				$data[$k]['headimg'] = $member['headimg'];
				$pszCount = Db::name('peisong_order')->where('aid',aid)->where('psid',$v['id'])->where('status','in','0,1,2,3')->count();
				$ywcCount = Db::name('peisong_order')->where('aid',aid)->where('psid',$v['id'])->where('status',4)->count();
				$data[$k]['pszCount'] = $pszCount;
				$data[$k]['ywcCount'] = $ywcCount;
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
        $this->defaultSet();
		return View::fetch();
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('peisong_user')->where('aid',aid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}
		View::assign('info',$info);
        View::assign('bid',bid);
		return View::fetch();
	}
	public function save(){
		$info = input('post.info/a');
		$hasrealname = Db::name('peisong_user')->where('aid',aid)->where('id','<>',$info['id'])->where('realname',$info['realname'])->find();
		if($hasrealname){
			return json(['status'=>0,'msg'=>'该姓名已存在，请填写其它姓名']);
		}
        if($info['id']){
			Db::name('peisong_user')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑配送员'.$info['id']);
		}else{
			$info['aid'] = aid;
            $info['createtime'] = time();
			$id = Db::name('peisong_user')->insertGetId($info);
			\app\common\System::plog('添加配送员'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//改状态
	public function setst(){
		$st = input('post.st/d');
		$ids = input('post.ids/a');
		Db::name('peisong_user')->where('aid',aid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog('配送员改状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('peisong_user')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('配送员删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

    public function choosepeisonguser(){
        if(request()->isPost()){
            $data = Db::name('peisong_user')->where('aid',aid)->where('status',1)->where('id',input('post.id/d'))->find();
            return json(['status'=>1,'msg'=>'查询成功','data'=>$data]);
        }
        return View::fetch();
    }
    //获取配送员
    public function getpeisonguser(){
       
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['status','=',1];
        $peisonguser = Db::name('peisong_user')->where($where)->order('sort desc,id')->select()->toArray();
        foreach($peisonguser as $k=>$v){
            $dan = Db::name('peisong_order')->where('worker_id',$v['id'])->where('status','in','0,1')->count();
        }
        return json(['status'=>1,'data'=>$peisonguser]);
    }
    function defaultSet(){
        $set = Db::name('peisong_set')->where('aid',aid)->find();
        if(!$set){
            Db::name('peisong_set')->insert(['aid'=>aid]);
        }
    }
}

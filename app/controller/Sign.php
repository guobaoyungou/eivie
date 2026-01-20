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
// | 签到
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Sign extends Common
{	
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
	//签到记录
	public function record(){
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
			if(input('param.nickname')) $where[] = ['nickname','like','%'.input('param.nickname').'%'];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			$count = 0 + Db::name('sign_record')->where($where)->count();
			$data = Db::name('sign_record')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            
            //查询优惠券的信息

            foreach($data as $k=>$v){
				if($v['lxqd_coupon_id'] > 0){
					$data[$k]['lxqd_coupon_name'] = Db::name('coupon')->where('aid',aid)->where('id',$v['lxqd_coupon_id'])->value('name');
				}else{
					$data[$k]['lxqd_coupon_name'] = '无';
				}

				if($v['lxzs_coupon_id'] > 0){
					$data[$k]['lxzs_coupon_name'] = Db::name('coupon')->where('aid',aid)->where('id',$v['lxzs_coupon_id'])->value('name');
				}else{
					$data[$k]['lxzs_coupon_name'] = '无';
				} 

				}
			
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
	}
	// recordadd
	public function recordadd(){
		$date = date('Y-m-d');
		view::assign('date',$date);
		return View::fetch();
	}
	//新增签到记录
	public function signAdd(){
	}
	// 签到审核
	public function recordshenhe(){
		}
	public function recordexcel(){
		if(input('param.field') && input('param.order')){
			$order = input('param.field').' '.input('param.order');
		}else{
			$order = 'id desc';
		}
        $page = input('param.page');
        $limit = input('param.limit');
		$where = [];
		$where[] = ['aid','=',aid];
		if(input('param.nickname')) $where[] = ['nickname','like','%'.input('param.nickname').'%'];
		if(input('param.ctime') ){
			$ctime = explode(' ~ ',input('param.ctime'));
			$where[] = ['createtime','>=',strtotime($ctime[0])];
			$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
		}
		$list = Db::name('sign_record')->where($where)->page($page,$limit)->select()->toArray();
        $count = Db::name('sign_record')->where($where)->count();
		$title = array();
		$title[] = '序号';
		$title[] = '昵称';
		$title[] = '签到时间';
		$title[] = '获得'.t('积分');
		$title[] = '签到总次数';
		$title[] = '连续次数';
		$title[] = '备注';
		$data = array();
		 
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['id'];
			$tdata[] = $v['nickname'];
			$tdata[] = $v['signdate'];
			$tdata[] = $v['score'];
			$tdata[] = $v['signtimes'];
			$tdata[] = $v['signtimeslx'];
			$tdata[] = $v['remark'];
			$data[] = $tdata;
		}
	 
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
		$this->export_excel($title,$data);
	}
	//删除
	public function recorddel(){
		$ids = input('post.ids/a');
		Db::name('sign_record')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('签到记录删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	//签到设置
	public function set(){
		if(request()->isAjax()){
			$signset = Db::name('signset')->where('aid',aid)->find();
			$info = input('post.info/a');
			$lxqdset = array();
			$lxqd_days = input('post.lxqd_days/a');
			$lxqd_score = input('post.lxqd_score/a');
			$lxqd_coupon_id = input('post.lxqd_coupon_id/a');
			$lxqd_coupon_name = input('post.lxqd_coupon_name/a');
			foreach($lxqd_days as $k=>$v){
				$lxqdData = ['days'=>$v,'score'=>$lxqd_score[$k],'coupon_id'=>$lxqd_coupon_id[$k],'coupon_name'=>$lxqd_coupon_name[$k]];
				$lxqdset[] =$lxqdData;
			}
			$info['lxqdset'] = json_encode($lxqdset);
			$lxzsset = array();
			$lxzs_days = input('post.lxzs_days/a');
			$lxzs_score = input('post.lxzs_score/a');
			$lxzs_coupon_id = input('post.lxzs_coupon_id/a');
			$lxzs_coupon_name = input('post.lxzs_coupon_name/a');
			// is_forget ,is_check,condition,lxzs_forget,bq_day,camera
 			foreach($lxzs_days as $k=>$v){
				$lxzsData = ['days'=>$v,'score'=>$lxzs_score[$k],'coupon_id'=>$lxzs_coupon_id[$k],'coupon_name'=>$lxzs_coupon_name[$k]];
				$lxzsset[] = $lxzsData;
			}
			$info['lxzsset'] = json_encode($lxzsset);

			if($signset)
			    Db::name('signset')->where('aid',aid)->update($info);
            else{
                $info['aid']=aid;
                Db::name('signset')->insert($info);
            }

			\app\common\System::plog('签到设置');
			return json(['status'=>1,'msg'=>'操作成功','url'=>true]);
		}
		$info = Db::name('signset')->where('aid',aid)->find();
        if(empty($info['bgpic'])){
            $info['bgpic'] = PRE_URL.'/static/img/sign-bg.png';
        }
		$info['camera'] = json_decode($info['camera'],true);

		View::assign('info',$info);
        View::assign('auth_data',$this->auth_data);

        return View::fetch();
	}

	
}
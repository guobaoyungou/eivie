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
// | 口令
// | 版权所有 点大网络科技有限公司
// | @author lanling17@qq.com
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Kouling extends Common
{
    public function initialize(){
		parent::initialize();
	}
	//活动列表
	public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'id desc';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
			$count = 0 + Db::name('kouling')->where($where)->count();
			$data = Db::name('kouling')->where($where)->page($page,$limit)->order($order)->select()->toArray();

			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
	}
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('kouling')->where('aid',aid)->where('id',input('param.id/d'))->find();
            if($info) {
                $coupon_ids = explode(',', $info['coupon_ids']);
                $couponList = Db::name('coupon')->where('aid',aid)->where('bid', 0)->whereIn('id', $coupon_ids)->select()->toArray();
            }
            View::assign('couponList',$couponList);
		}else{
			$info = array(
				'id'=>'',
                'starttime'=>time()-100,
                'endtime'=>time()+86400-100,
                'gettj'=>'-1',
                'pertotal'=>1,
                'perday'=>1
				);
		}
		$info['gettj'] = explode(',',$info['gettj']);
		View::assign('info',$info);
        $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
        $default_cid = $default_cid ? $default_cid : 0;
        $memberlevel = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();
		View::assign('memberlevel',$memberlevel);
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
		$info['starttime'] = strtotime($info['starttime']);
		$info['endtime'] = strtotime($info['endtime']);
		$info['gettj'] = implode(',',$info['gettj']);
		$dhdata = array();

		if($info['id']){
			$info['updatetime'] = time();
            $exist = Db::name('kouling')->where('aid',aid)->where('id','<>',$info['id'])
                ->where('name',$info['name'])->whereRaw(" (starttime >= '".$info['starttime']."' and starttime <= '".$info['endtime']."') or (endtime >= '".$info['starttime']."' and endtime <= '".$info['endtime']."') or (endtime >= '".$info['endtime']."' and starttime <= '".$info['starttime']."')")->find();
            if($exist){
                return json(['status'=>0,'msg'=>'失败，同一时间段内有相同名称的口令']);
            }
            Db::name('kouling')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑口令活动'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
            $exist = Db::name('kouling')->where('aid',aid)
                ->where('name',$info['name'])->whereRaw(" (starttime >= '".$info['starttime']."' and starttime <= '".$info['endtime']."') or (endtime >= '".$info['starttime']."' and endtime <= '".$info['endtime']."') or (endtime >= '".$info['endtime']."' and starttime <= '".$info['starttime']."') ")->find();
            if($exist){
                return json(['status'=>0,'msg'=>'失败，同一时间段内有相同名称的口令']);
            }
			$id = Db::name('kouling')->insertGetId($info);
			\app\common\System::plog('添加口令活动'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('kouling')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除口令活动'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//领取记录
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
			if(input('param.hid')){
				$where[] = ['objid','=',input('param.hid/d')];
			}
			if(input('param.mid')) $where[] = ['mid','=',input('param.mid')];
			if(input('param.nickname')) $where[] = ['nickname','like','%'.input('param.nickname').'%'];
			if(input('param.linkman')) $where[] = ['formdata','like','%'.input('param.linkman').'%'];
			if(input('param.name')) $where[] = ['name','like','%'.input('param.jxmc').'%'];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			if(input('?param.status') && input('param.status')!==''){
				$where[] = ['status','=',input('param.status')];
			}
			$count = 0 + Db::name('kouling_record')->where($where)->count();
			$data = Db::name('kouling_record')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
                if($v['coupon_ids'] > 0){
                    $data[$k]['coupon_name'] = Db::name('coupon')->where('aid',aid)->where('id',$v['coupon_ids'])->value('name');
                }else{
                    $data[$k]['coupon_name'] = '无';
                }

                if($v['coupon_ids'] > 0){
                    $data[$k]['coupon_name'] = Db::name('coupon')->where('aid',aid)->where('id',$v['coupon_ids'])->value('name');
                }else{
                    $data[$k]['coupon_name'] = '无';
                }
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
	}
	//改状态
	public function setst(){
		$ids = input('post.ids/a');
		$st = input('post.st/d');
		Db::name('kouling_record')->where('aid',aid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog('修改口令记录状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'修改成功']);
	}
	//领取记录导出
	public function recordexcel(){
		$where = [];
		$where[] = ['aid','=',aid];
		if(input('param.hid')){
			$where[] = ['objid','=',input('param.hid/d')];
		}
		$list = Db::name('kouling_record')->where($where)->select()->toArray();

		$title = array();
		$title[] = '序号';
		$title[] = '活动ID';
		$title[] = '名称';
		$title[] = t('会员').'ID';
		$title[] = '昵称';
		$title[] = '奖品';
		$title[] = '领取时间';
		$title[] = '状态';
		$title[] = '备注';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['id'];
			$tdata[] = $v['objid'];
			$tdata[] = $v['name'];
			$tdata[] = $v['mid'];
			$tdata[] = $v['nickname'];
			$tdata[] = $v['coupon_ids'];
			$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
			$status = '';
			if($v['jx']==0){
				$status = '未中奖';
			}elseif($v['status']==1){
				$status = '已领取';
			}elseif($v['status']==0){
				$status = '未领取';
			}
			$tdata[] = $status;
			$tdata[] = $v['remark'];
			$data[] = $tdata;
		}
		$this->export_excel($title,$data);
	}
	//删除
	public function recorddel(){
		$ids = input('post.ids/a');
		Db::name('kouling_record')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除口令记录'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	public function set()
    {
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        if(input('param.id')){
            $where[] = ['id','=',input('param.id/d')];
        }
        $set = \db('kouling_set')->where($where)->find();
        if(request()->isAjax()){
            $info = input('post.info/a');

            if($info['id']){
                Db::name('kouling_set')->where('aid',aid)->where('id',$info['id'])->update($info);
                \app\common\System::plog('编辑口令设置'.$info['id']);
            }else{
                $info['aid'] = aid;
                $info['bid'] = bid;
                $info['createtime'] = time();
                $id = Db::name('kouling_set')->insertGetId($info);
                \app\common\System::plog('编辑口令设置'.$id);
            }
            return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('set')]);
        }

        View::assign('info',$set);
        return View::fetch();
    }
}
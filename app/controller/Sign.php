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
			if(input('param.employee_no')) $where[] = ['employee_no','like','%'.input('param.employee_no').'%'];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			$count = 0 + Db::name('sign_record')->where($where)->count();
			$data = Db::name('sign_record')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            
            //查询优惠券的信息
			// 获取自定义字段定义
			$customFieldDefs = Db::name('sign_custom_field')->where('aid',aid)->order('sort asc, id asc')->select()->toArray();
			$customFieldMap = [];
			foreach($customFieldDefs as $cfd){
				$customFieldMap[$cfd['id']] = $cfd;
			}

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

				// 解析自定义字段数据
				if(!empty($v['custom_data'])){
					$cd = json_decode($v['custom_data'], true);
					if(is_array($cd)){
						foreach($cd as $fkey=>$fval){
							$fid = str_replace('field_', '', $fkey);
							if(isset($customFieldMap[$fid])){
								$data[$k]['custom_field_'.$fid] = is_array($fval) ? implode(',', $fval) : $fval;
							}
						}
					}
				}
			}
			
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'customFieldDefs'=>$customFieldDefs]);
		}

		// 获取签到设置和自定义字段供视图使用
		$signset = Db::name('signset')->where('aid',aid)->find();
		$customFieldDefs = Db::name('sign_custom_field')->where('aid',aid)->order('sort asc, id asc')->select()->toArray();
		View::assign('signset',$signset);
		View::assign('customFieldDefs',$customFieldDefs);
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
		if(input('param.employee_no')) $where[] = ['employee_no','like','%'.input('param.employee_no').'%'];
		if(input('param.ctime') ){
			$ctime = explode(' ~ ',input('param.ctime'));
			$where[] = ['createtime','>=',strtotime($ctime[0])];
			$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
		}
		$list = Db::name('sign_record')->where($where)->page($page,$limit)->order($order)->select()->toArray();
        $count = Db::name('sign_record')->where($where)->count();

		// 获取自定义字段定义
		$customFieldDefs = Db::name('sign_custom_field')->where('aid',aid)->where('status',1)->order('sort asc, id asc')->select()->toArray();

		$title = array();
		$title[] = '序号';
		$title[] = '昵称';
		$title[] = '员工号';
		$title[] = '签到时间';
		$title[] = '获得'.t('积分');
		$title[] = '签到总次数';
		$title[] = '连续次数';
		$title[] = '签到照片';
		// 动态添加自定义字段标题
		foreach($customFieldDefs as $cfd){
			$title[] = $cfd['field_name'];
		}
		$title[] = '备注';
		$data = array();
		 
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['id'];
			$tdata[] = $v['nickname'];
			$tdata[] = $v['employee_no'] ?? '';
			$tdata[] = $v['signdate'];
			$tdata[] = $v['score'];
			$tdata[] = $v['signtimes'];
			$tdata[] = $v['signtimeslx'];
			$tdata[] = $v['sign_photo'] ?? '';
			// 动态填充自定义字段数据
			$customData = !empty($v['custom_data']) ? json_decode($v['custom_data'], true) : [];
			foreach($customFieldDefs as $cfd){
				$fkey = 'field_'.$cfd['id'];
				if(isset($customData[$fkey])){
					$val = $customData[$fkey];
					$tdata[] = is_array($val) ? implode(',', $val) : $val;
				}else{
					$tdata[] = '';
				}
			}
			$tdata[] = $v['remark'];
			$data[] = $tdata;
		}
	 
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
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

			// 处理扩展字段开关
			$info['show_employee_no'] = isset($info['show_employee_no']) ? intval($info['show_employee_no']) : 0;
			$info['require_employee_no'] = isset($info['require_employee_no']) ? intval($info['require_employee_no']) : 0;
			$info['show_photo'] = isset($info['show_photo']) ? intval($info['show_photo']) : 0;
			$info['require_photo'] = isset($info['require_photo']) ? intval($info['require_photo']) : 0;
			$info['show_custom_fields'] = isset($info['show_custom_fields']) ? intval($info['show_custom_fields']) : 0;

			if($signset)
			    Db::name('signset')->where('aid',aid)->update($info);
            else{
                $info['aid']=aid;
                Db::name('signset')->insert($info);
            }

			// 处理自定义字段
			$customFields = input('post.custom_fields/a');
			if($info['show_custom_fields'] == 1 && $customFields){
				$existingIds = Db::name('sign_custom_field')->where('aid',aid)->column('id');
				$submittedIds = [];
				foreach($customFields as $cf){
					if(empty($cf['field_name'])) continue;
					$cfData = [
						'aid' => aid,
						'field_name' => $cf['field_name'],
						'field_type' => $cf['field_type'] ?? 'text',
						'field_options' => '',
						'is_required' => isset($cf['is_required']) ? intval($cf['is_required']) : 0,
						'sort' => isset($cf['sort']) ? intval($cf['sort']) : 0,
						'status' => 1,
					];
					// 处理选项值（select/checkbox类型）
					if(in_array($cfData['field_type'], ['select','checkbox']) && !empty($cf['field_options'])){
						$options = explode(',', $cf['field_options']);
						$cfData['field_options'] = json_encode($options, JSON_UNESCAPED_UNICODE);
					}
					if(!empty($cf['id'])){
						// 更新已有字段
						Db::name('sign_custom_field')->where('id',$cf['id'])->where('aid',aid)->update($cfData);
						$submittedIds[] = $cf['id'];
					}else{
						// 新增字段
						$cfData['createtime'] = time();
						$newId = Db::name('sign_custom_field')->insertGetId($cfData);
						$submittedIds[] = $newId;
					}
				}
				// 删除被移除的字段
				$deleteIds = array_diff($existingIds, $submittedIds);
				if(!empty($deleteIds)){
					Db::name('sign_custom_field')->where('aid',aid)->where('id','in',$deleteIds)->delete();
				}
			}

			\app\common\System::plog('签到设置');
			return json(['status'=>1,'msg'=>'操作成功','url'=>true]);
		}
		$info = Db::name('signset')->where('aid',aid)->find();
        if(empty($info['bgpic'])){
            $info['bgpic'] = PRE_URL.'/static/img/sign-bg.png';
        }
		$info['camera'] = json_decode($info['camera'],true);

		// 读取自定义字段列表
		$customFields = Db::name('sign_custom_field')->where('aid',aid)->order('sort asc, id asc')->select()->toArray();
		foreach($customFields as &$cf){
			if(in_array($cf['field_type'], ['select','checkbox']) && $cf['field_options']){
				$optArr = json_decode($cf['field_options'], true);
				$cf['field_options_text'] = is_array($optArr) ? implode(',', $optArr) : $cf['field_options'];
			}else{
				$cf['field_options_text'] = '';
			}
		}
		unset($cf);

		View::assign('info',$info);
		View::assign('customFields',$customFields);
        View::assign('auth_data',$this->auth_data);

        return View::fetch();
	}

	
}
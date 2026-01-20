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
// | 集点
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Jidian extends Common
{	
    public function initialize(){
		parent::initialize();
		if(bid == 0) showmsg('无访问权限');
	}
	//集点记录
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
			$count = 0 + Db::name('jidian_record')->where($where)->count();
			$data = Db::name('jidian_record')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            
            //查询优惠券的信息

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
	public function recordexcel(){
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
		$list = Db::name('jidian_record')->where($where)->select()->toArray();
		$title = array();
		$title[] = '序号';
		$title[] = '昵称';
		$title[] = '集点时间';
		$title[] = '获得'.t('积分');
		$title[] = '集点总次数';
		$title[] = '连续次数';
		$title[] = '备注';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['id'];
			$tdata[] = $v['nickname'];
			$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
			$tdata[] = $v['score'];
			$tdata[] = $v['signtimes'];
			$tdata[] = $v['signtimeslx'];
			$tdata[] = $v['remark'];
			$data[] = $tdata;
		}
		$this->export_excel($title,$data);
	}
	//删除
	public function recorddel(){
		$ids = input('post.ids/a');
		Db::name('jidian_record')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('集点记录删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	//集点设置
	public function set(){
        $jidian_set = Db::name('jidian_set')->where('aid',aid)->where('bid',bid)->find();
		if(request()->isAjax()){
			$info = input('post.info/a');
            $set = array();
			$days = input('post.days/a');
			$score = input('post.score/a');
			$coupon_id = input('post.coupon_id/a');
			$coupon_name = input('post.coupon_name/a');
			foreach($days as $k=>$v){
			    if($v > 0)
                $set[] = array('days'=>$v,'score'=>$score[$k],'coupon_id'=>$coupon_id[$k],'coupon_name'=>$coupon_id[$k] ? $coupon_name[$k] : '');
			}
			$info['set'] = json_encode($set);

            $info['starttime'] = strtotime($info['starttime']);
            $info['endtime'] = strtotime($info['endtime']);
//            $info['gettj'] = implode(',',$info['gettj']);
            if($info['paygive_scene']){
                $info['paygive_scene'] = implode(',',$info['paygive_scene']);
            }else{
                $info['paygive_scene'] = '';
            }

			if(empty($jidian_set)) {
                $info['aid'] = aid;
                $info['bid'] = bid;
                Db::name('jidian_set')->insert($info);
            }else{
                Db::name('jidian_set')->where('aid',aid)->update($info);
            }

			\app\common\System::plog('集点设置');
			return json(['status'=>1,'msg'=>'操作成功','url'=>true]);
		}
		if(empty($jidian_set)) {
            $jidian_set = [
                'id'=>'',
                'starttime'=>time()-100,
                'endtime'=>time()+86400*30-100,
                'gettj'=>'-1',
                'price_start'=>0,
                'days'=>15,
                'guize' => '<p style="text-align: left;"><span style="font-size: 20px;"><strong><br/></strong></span></p><p style="text-align: left;"><span style="font-size: 20px;"><strong>&nbsp;规则说明</strong></span></p><ol class=" list-paddingleft-2" style="list-style-type: decimal;"><li><p>消费超过20的订单完成后，可获得一个集点</p></li><li><p>3个集点可获得xxx优惠券</p></li><li><p>7个集点可获得xxx优惠券</p></li><li><p>订单退款不计集点<br/></p></li></ol><p><br/></p><p><strong><span style="font-size: 20px;">&nbsp;活动说明</span></strong></p><ol class=" list-paddingleft-2" style="list-style-type: decimal;"><li><p>活动时间：xx年x月x日至x年x月x日</p></li><li><p>用户集点任务周期x天，超过周期的订单集点失效</p></li><li><p>奖品名称：xx优惠券</p></li><li><p>具体解释权归商家所有。</p></li></ol><p><br/></p><p>（以上内容仅供参考，根据实际情况修改和自定义图片等）</p>'
            ];
        }
		View::assign('info',$jidian_set);
      
		return View::fetch();
	}
}
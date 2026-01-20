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
// | 拼团商城 商品评价
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class LuckyCollageComment extends Common
{
	//评价列表
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
			$where[] = ['bid','=',bid];
			if(input('param.content')) $where[] = ['content','like','%'.input('param.content').'%'];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			//dump($where);
			$count = 0 + Db::name('lucky_collage_comment')->where($where)->count();
			$data = Db::name('lucky_collage_comment')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
        $this->defaultSet();
		return View::fetch();
    }
	//评价审核
	public function setst(){
		$st = input('param.st/d');
		$ids = input('post.ids/a');
		$list = Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->select()->toArray();
		foreach($list as $v){
			Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('id',$v['id'])->update(['status'=>$st]);
			$proComment = Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('proid',$v['proid'])->where('status',1)->avg('score');
			$comment_num = Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('proid',$v['proid'])->where('status',1)->count();
			if($comment_num==0) $proComment = 5;
			$haonum = Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('proid',$v['proid'])->where('status',1)->where('score','>',3)->count(); //好评数
			if($comment_num > 0){
				$haopercent = $haonum/$comment_num*100;
			}else{
				$haopercent = 100;
			}
			Db::name('collage_product')->where('aid',aid)->where('bid',bid)->where('id',$v['proid'])->update(['comment_score'=>$proComment,'comment_num'=>$comment_num,'comment_haopercent'=>$haopercent]);
		}
		echojson(array('status'=>1,'msg'=>'操作成功'));
	}
	//评价详情
	public function getdetail(){
		$detail= Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
		if($detail['content_pic']) $detail['content_pic'] = explode(',',$detail['content_pic']);
		$member = Db::name('member')->where('aid',aid)->where('id',$detail['mid'])->find();
		if(!$member) $member = ['nickname'=>$detail['nickname'],'headimg'=>$detail['headimg']];
		return json(['status'=>1,'detail'=>$detail,'member'=>$member]);
	}
	//评价回复
	public function reply(){
		$id = input('post.id/d');
		Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('id',$id)->update(['reply_content'=>input('post.content'),'reply_time'=>time()]);
		\app\common\System::plog('回复拼团评价'.$id);
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('lucky_collage_comment')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除拼团评价'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
    function defaultSet(){
        $set = Db::name('lucky_collage_sysset')->where('aid',aid)->find();
        if(!$set){
            Db::name('lucky_collage_sysset')->insert(['aid'=>aid]);
        }
    }
}

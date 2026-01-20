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
// | 积分管理
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Score extends Common
{
    public $financelog_query_type = 0;//查询方式 0现有的连表查询，1不连member表独立查询速度更快
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
        $financelog_query_type = 0;
        $this->financelog_query_type = $financelog_query_type;
	}
	//积分明细
    public function scorelog(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'member_scorelog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'member_scorelog.id desc';
			}
            if($this->financelog_query_type==0) {
                //连表member查询
                $where = array();
                $where[] = ['member.aid', '=', aid];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
                }

                if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
                if (input('param.mid')) $where[] = ['member_scorelog.mid', '=', trim(input('param.mid'))];
                if (input('?param.status') && input('param.status') !== '') $where[] = ['member_scorelog.status', '=', input('param.status')];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
                }

                if (input('remark')) {
                    $where[] = ['member_scorelog.remark', 'like', '%' . trim(input('param.remark')) . '%'];
                }

                $count = 0 + Db::name('member_scorelog')->alias('member_scorelog')->field('member.nickname,member.headimg,member_scorelog.*')->join('member member', 'member.id=member_scorelog.mid')->where($where)->count();
                $data = Db::name('member_scorelog')->alias('member_scorelog')->field('member.nickname,member.headimg,member_scorelog.*')->join('member member', 'member.id=member_scorelog.mid')->where($where)->page($page, $limit)->order($order)->select()->toArray();
                $score_weishu = 0;
                foreach ($data as $k => $v) {
                    $data[$k]['score'] = dd_money_format($v['score'], $score_weishu);
                    $data[$k]['used'] = dd_money_format($v['used'], $score_weishu);
                    $data[$k]['after'] = dd_money_format($v['after'], $score_weishu);

                    $data[$k]['un'] = '';
                    if ($v['uid']) {
                        $un = Db::name('admin_user')->where('id', $v['uid'])->where('aid', aid)->value('un');
                        $data[$k]['un'] = $un ?? '已失效';
                    }
                }
            }
            else{
                //独立查询
                $where = array();
                $where[] = ['member_scorelog.aid', '=', aid];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
                }

                $where_m = [];
                if(input('param.nickname')) $where_m[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                if($where_m){
                    $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                    if(!$mids){
                        $mids = ['-1'];
                    }
                    $where[] = ['member_scorelog.mid','in',$mids];
                }
                if (input('param.mid')) $where[] = ['member_scorelog.mid', '=', trim(input('param.mid'))];
                if (input('?param.status') && input('param.status') !== '') $where[] = ['member_scorelog.status', '=', input('param.status')];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
                }

                if (input('remark')) {
                    $where[] = ['member_scorelog.remark', 'like', '%' . trim(input('param.remark')) . '%'];
                }

                $count = 0 + Db::name('member_scorelog')->alias('member_scorelog')->field('member_scorelog.*')->where($where)->count();
                $data = Db::name('member_scorelog')->alias('member_scorelog')->field('member_scorelog.*')->where($where)->page($page, $limit)->order($order)->select()->toArray();
                $score_weishu = 0;
                foreach ($data as $k => $v) {
                    $member = Db::name('member')->where('id',$v['mid'])->where('aid',aid)->find();
                    $data[$k]['nickname'] = $member['nickname'];
                    $data[$k]['headimg'] = $member['headimg'];
                    $data[$k]['score'] = dd_money_format($v['score'], $score_weishu);
                    $data[$k]['used'] = dd_money_format($v['used'], $score_weishu);
                    $data[$k]['after'] = dd_money_format($v['after'], $score_weishu);

                    $data[$k]['un'] = '';
                    if ($v['uid']) {
                        $un = Db::name('admin_user')->where('id', $v['uid'])->where('aid', aid)->value('un');
                        $data[$k]['un'] = $un ?? '已失效';
                    }
                }

            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
    }
    //汇总数据异步统计
    public function score_statistics(){
        if($this->financelog_query_type==0) {
            //连表member查询
            $where = array();
            $where[] = ['member.aid', '=', aid];
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
            }

            if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
            if (input('param.mid')) $where[] = ['member_scorelog.mid', '=', trim(input('param.mid'))];
            if (input('?param.status') && input('param.status') !== '') $where[] = ['member_scorelog.status', '=', input('param.status')];
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
            }

            if (input('remark')) {
                $where[] = ['member_scorelog.remark', 'like', '%' . trim(input('param.remark')) . '%'];
            }
        }
        else{
            //独立查询
            $where = array();
            $where[] = ['member_scorelog.aid', '=', aid];
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
            }

            $where_m = [];
            if(input('param.nickname')) $where_m[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if($where_m){
                $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                if(!$mids){
                    $mids = ['-1'];
                }
                $where[] = ['member_scorelog.mid','in',$mids];
            }
            if (input('param.mid')) $where[] = ['member_scorelog.mid', '=', trim(input('param.mid'))];
            if (input('?param.status') && input('param.status') !== '') $where[] = ['member_scorelog.status', '=', input('param.status')];
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
            }

            if (input('remark')) {
                $where[] = ['member_scorelog.remark', 'like', '%' . trim(input('param.remark')) . '%'];
            }
        }
        $total = [];
        return json(['code'=>0,'msg'=>'查询成功','total'=>$total]);
    }
	//积分明细导出
	public function scorelogexcel(){
		if(input('param.field') && input('param.order')){
			$order = 'member_scorelog.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'member_scorelog.id desc';
		}
        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
        if($this->financelog_query_type==0) {
            $where = array();
            $where[] = ['member_scorelog.aid', '=', aid];
            if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . trim(input('param.nickname')) . '%'];
            if (input('param.mid')) $where[] = ['member_scorelog.mid', '=', trim(input('param.mid'))];
            if (input('?param.status') && input('param.status') !== '') $where[] = ['member_scorelog.status', '=', input('param.status')];

            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
            }

            $list = Db::name('member_scorelog')->alias('member_scorelog')->field('member.nickname,member.headimg,member_scorelog.*')
                ->join('member member', 'member.id=member_scorelog.mid')->where($where)->order($order)->page($page, $limit)->select()->toArray();
            $count = Db::name('member_scorelog')->alias('member_scorelog')->field('member.nickname,member.headimg,member_scorelog.*')
                ->join('member member', 'member.id=member_scorelog.mid')->where($where)->count();
            $title = array();
            $title[] = t('会员') . '信息';
            $title[] = '变更' . t('积分');
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            $title[] = '操作员';
            $data = array();
            foreach ($list as $v) {
                $tdata = array();
                $tdata[] = $v['nickname'] . '(' . t('会员') . 'ID:' . $v['mid'] . ')';
                $tdata[] = $v['score'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s', $v['createtime']);
                $tdata[] = $v['remark'];

                $un = '';
                if ($v['uid']) {
                    $un = Db::name('admin_user')->where('id', $v['uid'])->where('aid', aid)->value('un');
                    $un .= $un ?? '已失效';
                    $un .= '(操作员ID:' . $v['uid'] . ')';
                }
                $tdata[] = $un;

                $data[] = $tdata;
            }
        }
        else{
            $where = array();
            $where[] = ['member_scorelog.aid', '=', aid];
            $where_m = [];
            if(input('param.nickname')) $where_m[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if($where_m){
                $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                if(!$mids){
                    $mids = ['-1'];
                }
                $where[] = ['member_scorelog.mid','in',$mids];
            }
            if (input('param.mid')) $where[] = ['member_scorelog.mid', '=', trim(input('param.mid'))];
            if (input('?param.status') && input('param.status') !== '') $where[] = ['member_scorelog.status', '=', input('param.status')];

            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                $where[] = ['member_scorelog.createtime', '>=', strtotime($ctime[0])];
                $where[] = ['member_scorelog.createtime', '<', strtotime($ctime[1]) + 86400];
            }

            $list = Db::name('member_scorelog')->alias('member_scorelog')->field('member_scorelog.*')
                ->where($where)->order($order)->page($page, $limit)->select()->toArray();
            $count = Db::name('member_scorelog')->alias('member_scorelog')->field('member_scorelog.*')
                ->where($where)->count();
            $title = array();
            $title[] = t('会员') . '信息';
            $title[] = '变更' . t('积分');
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            $title[] = '操作员';
            $data = array();
            foreach ($list as $v) {
                $member = Db::name('member')->where('id',$v['mid'])->where('aid',aid)->find();
                $v['nickname'] = $member['nickname'];
                $v['headimg'] = $member['headimg'];
                $tdata = array();
                $tdata[] = $v['nickname'] . '(' . t('会员') . 'ID:' . $v['mid'] . ')';
                $tdata[] = $v['score'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s', $v['createtime']);
                $tdata[] = $v['remark'];

                $un = '';
                if ($v['uid']) {
                    $un = Db::name('admin_user')->where('id', $v['uid'])->where('aid', aid)->value('un');
                    $un .= $un ?? '已失效';
                    $un .= '(操作员ID:' . $v['uid'] . ')';
                }
                $tdata[] = $un;

                $data[] = $tdata;
            }
        }
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
		$this->export_excel($title,$data);
	}
	public function scorelogdel(){
		$ids = input('post.ids/a');
		Db::name('member_scorelog')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog(t('积分').'明细删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

    public function cancel()
    {
        $ids = input('post.ids/a');
        $list = Db::name('member_scorelog')->where('aid',aid)->where('id','in',$ids)->select()->toArray();
        foreach ($list as $item){
            if($item['status'] != -1 && $item['is_cancel'] == 0){
                //过期和已撤销的无需处理
                Db::name('member_scorelog')->where('aid',aid)->where('id',$item['id'])->update(['is_cancel'=>1]);
                \app\common\Member::addscore(aid,$item['mid'],$item['score']*-1,'撤销操作');
            }

        }
        \app\common\System::plog(t('积分').'明细撤销'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'操作成功']);
    }
}

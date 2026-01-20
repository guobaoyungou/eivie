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
// | 余额管理
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;
use pay\wechatpay\WxPayV3;

class Money extends Common
{	
	public $money_weishu = 2;
    public $financelog_query_type = 0;//查询方式 0现有的连表查询，1不连member表独立查询速度更快
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
		if(getcustom('member_money_weishu')){
            $this->money_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
        }
        $financelog_query_type = 0;
        if(getcustom('financelog_query_type')){
            //查询方式 0现有的连表查询，1不连member表独立查询速度更快
            $financelog_query_type = $this->adminSet['financelog_query_type'];
        }
        $this->financelog_query_type = $financelog_query_type;
	}
	//余额明细
    public function moneylog(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'member_moneylog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'member_moneylog.id desc';
			}
            if($this->financelog_query_type==0){
                //连表member查询
                $where = [];
                $where[] = ['member.aid','=',aid];

                if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                if(input('param.mid')) $where[] = ['member_moneylog.mid','=',trim(input('param.mid'))];
                if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['member_moneylog.createtime','>=',strtotime($ctime[0])];
                    $where[] = ['member_moneylog.createtime','<',strtotime($ctime[1])];
                }
                if(getcustom('scoreshop_otheradmin_buy')){
                    if(input('?param.optaid') && input('param.optaid')!=='') $where[] = ['member_moneylog.optaid','=',input('param.optaid')];
                }
                if(getcustom('member_total_amount')){
                    if(input('param.islj')==1) $where[] = ['member_moneylog.money','>',0];
                }
                $field='member.nickname,member.headimg,member_moneylog.*';
                if(getcustom('recharge_use_mendian')){
                    $field.=',member_moneylog.mdid';
                    if($this->mdid){
                        $where[] = ['member_moneylog.mdid','=',$this->mdid];
                    }
                    if(input('param.mdid')){
                        $where[] = ['member_moneylog.mdid','=',input('param.mdid')];
                    }
                }
                if(input('remark')){
                    $where[] = ['member_moneylog.remark','like','%'.trim(input('param.remark')).'%'];
                }
                $count = 0 + Db::name('member_moneylog')->alias('member_moneylog')->join('member member','member.id=member_moneylog.mid')->where($where)->count('member_moneylog.id');

                $data = Db::name('member_moneylog')->alias('member_moneylog')->field($field)->join('member member','member.id=member_moneylog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                if($data){
                    foreach($data as &$v){
                        $v['money'] = dd_money_format($v['money'],$this->money_weishu);
                        $v['after'] = dd_money_format($v['after'],$this->money_weishu);

                        $v['un'] = '';
                        if($v['uid']){
                            $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                            $v['un'] = $un??'已失效';
                        }
                        if (getcustom('recharge_use_mendian')){
                            $mendian_name = '';
                            if($v['mdid'] > 0){
                                $mendian_name = Db::name('mendian')->where('aid',aid)->where('bid',bid)->where('id',$v['mdid'])->value('name');
                            }
                            $v['mendian_name'] =  $mendian_name;
                        }
                    }
                    unset($v);
                }
            }
            else{
                //独立查询
                $where = [];
                $where[] = ['member_moneylog.aid','=',aid];
                if(input('param.mid')) $where[] = ['member_moneylog.mid','=',trim(input('param.mid'))];
                $where_m = [];
                if(input('param.nickname')) $where_m[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                if(input('param.tel')) $where_m[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
                if($where_m){
                    $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                    if(!$mids){
                        $mids = ['-1'];
                    }
                    $where[] = ['member_moneylog.mid','in',$mids];
                }
                if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['member_moneylog.createtime','>=',strtotime($ctime[0])];
                    $where[] = ['member_moneylog.createtime','<',strtotime($ctime[1])];
                }
                if(getcustom('scoreshop_otheradmin_buy')){
                    if(input('?param.optaid') && input('param.optaid')!=='') $where[] = ['member_moneylog.optaid','=',input('param.optaid')];
                }
                if(getcustom('member_total_amount')){
                    if(input('param.islj')==1) $where[] = ['member_moneylog.money','>',0];
                }
                $field='member_moneylog.*';
                if(getcustom('recharge_use_mendian')){
                    $field.=',member_moneylog.mdid';
                    if($this->mdid){
                        $where[] = ['member_moneylog.mdid','=',$this->mdid];
                    }
                    if(input('param.mdid')){
                        $where[] = ['member_moneylog.mdid','=',input('param.mdid')];
                    }
                }
                if(input('remark')){
                    $where[] = ['member_moneylog.remark','like','%'.trim(input('param.remark')).'%'];
                }
                $count = 0 + Db::name('member_moneylog')->alias('member_moneylog')->where($where)->count('member_moneylog.id');
                $data = Db::name('member_moneylog')->alias('member_moneylog')->field($field)->where($where)->page($page,$limit)->order($order)->select()->toArray();
                if($data){
                    foreach($data as &$v){
                        $member = Db::name('member')->where('id',$v['mid'])->where('aid',aid)->find();
                        $v['nickname'] = $member['nickname'];
                        $v['headimg'] = $member['headimg'];
                        $v['money'] = dd_money_format($v['money'],$this->money_weishu);
                        $v['after'] = dd_money_format($v['after'],$this->money_weishu);

                        $v['un'] = '';
                        if($v['uid']){
                            $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                            $v['un'] = $un??'已失效';
                        }
                        if (getcustom('recharge_use_mendian')){
                            $mendian_name = '';
                            if($v['mdid'] > 0){
                                $mendian_name = Db::name('mendian')->where('aid',aid)->where('bid',bid)->where('id',$v['mdid'])->value('name');
                            }
                            $v['mendian_name'] =  $mendian_name;
                        }
                    }
                    unset($v);
                }
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
        if(getcustom('recharge_use_mendian')){
            $whereM = [];
            $whereM[] = ['aid','=',aid];
            $whereM[] = ['status','=',1];
            $whereM[] = ['bid','=',0];
            if($this->mdid){
                $whereM[] = ['id','=',$this->mdid];
            }
            //是不是置顶
            $mdlist = Db::name('mendian')->where($whereM)->order('id')->field('id,name')->select()->toArray();
            View::assign('mdlist',$mdlist);
        }
		return View::fetch();
    }
    //汇总数据异步统计
    public function money_statistics(){
        if($this->financelog_query_type==0){
            //连表member查询
            $where = [];
            $where[] = ['member.aid','=',aid];

            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_moneylog.mid','=',trim(input('param.mid'))];
            if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_moneylog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_moneylog.createtime','<',strtotime($ctime[1])];
            }
            if(getcustom('scoreshop_otheradmin_buy')){
                if(input('?param.optaid') && input('param.optaid')!=='') $where[] = ['member_moneylog.optaid','=',input('param.optaid')];
            }
            if(getcustom('member_total_amount')){
                if(input('param.islj')==1) $where[] = ['member_moneylog.money','>',0];
            }
            $field='member.nickname,member.headimg,member_moneylog.*';
            if(getcustom('recharge_use_mendian')){
                $field.=',member_moneylog.mdid';
                if($this->mdid){
                    $where[] = ['member_moneylog.mdid','=',$this->mdid];
                }
                if(input('param.mdid')){
                    $where[] = ['member_moneylog.mdid','=',input('param.mdid')];
                }
            }
            if(input('remark')){
                $where[] = ['member_moneylog.remark','like','%'.trim(input('param.remark')).'%'];
            }
        }
        else{
            //独立查询
            $where = [];
            $where[] = ['member_moneylog.aid','=',aid];
            if(input('param.mid')) $where[] = ['member_moneylog.mid','=',trim(input('param.mid'))];
            $where_m = [];
            if(input('param.nickname')) $where_m[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.tel')) $where_m[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
            if($where_m){
                $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                if(!$mids){
                    $mids = ['-1'];
                }
                $where[] = ['member_moneylog.mid','in',$mids];
            }
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_moneylog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_moneylog.createtime','<',strtotime($ctime[1])];
            }
            if(getcustom('scoreshop_otheradmin_buy')){
                if(input('?param.optaid') && input('param.optaid')!=='') $where[] = ['member_moneylog.optaid','=',input('param.optaid')];
            }
            if(getcustom('member_total_amount')){
                if(input('param.islj')==1) $where[] = ['member_moneylog.money','>',0];
            }
            $field='member_moneylog.*';
            if(getcustom('recharge_use_mendian')){
                $field.=',member_moneylog.mdid';
                if($this->mdid){
                    $where[] = ['member_moneylog.mdid','=',$this->mdid];
                }
                if(input('param.mdid')){
                    $where[] = ['member_moneylog.mdid','=',input('param.mdid')];
                }
            }
            if(input('remark')){
                $where[] = ['member_moneylog.remark','like','%'.trim(input('param.remark')).'%'];
            }
        }
        $tongji = [];
        if(getcustom('member_moneylog_change_tongji')){
            //余额变更统计
            $yue_money= Db::name('member_moneylog')->alias('member_moneylog')->field('member_moneylog.*')->where($where)->sum('member_moneylog.money');
            $tongji['yue_money'] = $yue_money;
            //赠送
            $give_money = 0 + Db::name('member_moneylog')->alias('member_moneylog')->field('mmember_moneylog.*')->where($where)->where('member_moneylog.remark','充值赠送')->sum('member_moneylog.money');
            $refund_give_money =0 + Db::name('member_moneylog')->alias('member_moneylog')->field('mmember_moneylog.*')->where($where)->where('member_moneylog.remark','like','%充值赠送金额退款%')->sum('member_moneylog.money');
            $give_money = abs($give_money) - abs($refund_give_money);
            $tongji['give_money'] = dd_money_format($give_money);
        }
        if(getcustom('finance_statistics')){
            //统计余额数据
            $total = \app\custom\FinanceStatistics::money_statistics(aid,$where);
            $tongji = array_merge($tongji,$total);
        }
        return json(['code'=>0,'msg'=>'查询成功','tongji'=>$tongji]);
    }
	//余额明细导出
	public function moneylogexcel(){
        if(input('param.field') && input('param.order')){
            $order = 'member_moneylog.'.input('param.field').' '.input('param.order');
        }else{
            $order = 'member_moneylog.id desc';
        }
        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
        if($this->financelog_query_type==0){
            //连表member查询
            $where = array();
            $where[] = ['member_moneylog.aid','=',aid];

            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_moneylog.mid','=',trim(input('param.mid'))];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
            if(getcustom('scoreshop_otheradmin_buy')){
                if(input('?param.optaid') && input('param.optaid')!=='') $where[] = ['member_moneylog.optaid','=',input('param.optaid')];
            }

            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_moneylog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_moneylog.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(getcustom('recharge_use_mendian')){
                if($this->mdid){
                    $where[] = ['member_moneylog.mdid','=',$this->mdid];
                }
                if(input('param.mdid')){
                    $where[] = ['member_moneylog.mdid','=',input('param.mdid')];
                }
            }
            $list = Db::name('member_moneylog')->alias('member_moneylog')->field('member.nickname,member.headimg,member_moneylog.*')
                ->join('member member','member.id=member_moneylog.mid')->where($where)->order($order)
                ->page($page,$limit)
                ->select()->toArray();
            $count = Db::name('member_moneylog')->alias('member_moneylog')->field('member.nickname,member.headimg,member_moneylog.*')
                ->join('member member','member.id=member_moneylog.mid')->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员').'信息';
            $title[] = '变更金额';
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            if(getcustom('recharge_use_mendian')){
                $title[] = '所属门店';
            }
            $title[] = '操作员';
            if(getcustom('scoreshop_otheradmin_buy')){
                $title[] = '操作来源';
            }
            $data = array();
            foreach($list as $v){
                $v['money'] = dd_money_format($v['money'],$this->money_weishu);
                $tdata = array();
                $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
                $tdata[] = $v['money'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
                $tdata[] = $v['remark'];
                if (getcustom('recharge_use_mendian')){
                    $mendian_name = '';
                    if($v['mdid'] > 0){
                        $mendian_name = Db::name('mendian')->where('aid',aid)->where('id',$v['mdid'])->value('name');
                    }
                    $tdata[]  =  $mendian_name;
                }
                $un = '';
                if($v['uid']){
                    $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                    $un .= $un??'已失效';
                    $un .= '(操作员ID:'.$v['uid'].')';
                }
                $tdata[] = $un;

                if(getcustom('scoreshop_otheradmin_buy')){
                    if($v['optaid'] == 1){
                        $tdata[] = '总平台';
                    }else{
                        $tdata[] = '本平台';
                    }
                }
                $data[] = $tdata;
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
        }
        else{
            $where = array();
            $where[] = ['member_moneylog.aid','=',aid];

            $where_m = [];
            if(input('param.nickname')) $where_m[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.tel')) $where_m[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
            if($where_m){
                $mids = Db::name('member')->alias('member')->where($where_m)->column('id');
                if(!$mids){
                    $mids = ['-1'];
                }
                $where[] = ['member_moneylog.mid','in',$mids];
            }
            if(input('param.mid')) $where[] = ['member_moneylog.mid','=',trim(input('param.mid'))];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
            if(getcustom('scoreshop_otheradmin_buy')){
                if(input('?param.optaid') && input('param.optaid')!=='') $where[] = ['member_moneylog.optaid','=',input('param.optaid')];
            }

            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_moneylog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_moneylog.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(getcustom('recharge_use_mendian')){
                if($this->mdid){
                    $where[] = ['member_moneylog.mdid','=',$this->mdid];
                }
                if(input('param.mdid')){
                    $where[] = ['member_moneylog.mdid','=',input('param.mdid')];
                }
            }
            $list = Db::name('member_moneylog')->alias('member_moneylog')->field('member_moneylog.*')
                ->where($where)->order($order)
                ->page($page,$limit)
                ->select()->toArray();
            $count = Db::name('member_moneylog')->alias('member_moneylog')->field('member_moneylog.*')
                ->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员').'信息';
            $title[] = '变更金额';
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            if(getcustom('recharge_use_mendian')){
                $title[] = '所属门店';
            }
            $title[] = '操作员';
            if(getcustom('scoreshop_otheradmin_buy')){
                $title[] = '操作来源';
            }
            $data = array();
            foreach($list as $v){
                $v['money'] = dd_money_format($v['money'],$this->money_weishu);
                $member = Db::name('member')->where('id',$v['mid'])->where('aid',aid)->find();
                $v['nickname'] = $member['nickname'];
                $v['headimg'] = $member['headimg'];
                $tdata = array();
                $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
                $tdata[] = $v['money'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
                $tdata[] = $v['remark'];
                if (getcustom('recharge_use_mendian')){
                    $mendian_name = '';
                    if($v['mdid'] > 0){
                        $mendian_name = Db::name('mendian')->where('aid',aid)->where('id',$v['mdid'])->value('name');
                    }
                    $tdata[]  =  $mendian_name;
                }
                $un = '';
                if($v['uid']){
                    $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                    $un .= $un??'已失效';
                    $un .= '(操作员ID:'.$v['uid'].')';
                }
                $tdata[] = $un;

                if(getcustom('scoreshop_otheradmin_buy')){
                    if($v['optaid'] == 1){
                        $tdata[] = '总平台';
                    }else{
                        $tdata[] = '本平台';
                    }
                }
                $data[] = $tdata;
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
        }
	}
	//余额明细删除
	public function moneylogdel(){
		$ids = input('post.ids/a');
		Db::name('member_moneylog')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除余额明细'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
    public function getmoneylogdetail(){
       if(getcustom('moneylog_detail')){
           $id = input('param.id');
           $order = Db::name('member_moneylog')->where('aid',aid)->where('id',$id)->find();
           $orderid = $order['orderid'];
           $ordernum = $order['ordernum'];
           $type = $order['type'];
           $detail = Db::name($type.'_order')->where ('aid',aid) ->where('ordernum',$ordernum)->find();
           $detail['type'] = $type;
           $comdata = array();
           $oglist = [];
           if(!in_array($type,['recharge','maidan','form','collage'])){
               $comdata['parent1'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
               $comdata['parent2'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
               $comdata['parent3'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];

               $ogwhere = [];
               $ogwhere[] = ['aid','=',aid];
               $ogwhere[] = ['orderid','=',$orderid];
               $oglist = Db::name($type.'_order_goods')->where($ogwhere)->select()->toArray();
               $ogids = [];
               foreach($oglist as $gk=>$v){
                   $ogids[] = $v['id'];
                   if($v['parent1']){
                       $parent1 = Db::name('member')->where('id',$v['parent1'])->find();
                       $comdata['parent1']['mid'] = $v['parent1'];
                       $comdata['parent1']['nickname'] = $parent1['nickname'];
                       $comdata['parent1']['headimg'] = $parent1['headimg'];
                       $comdata['parent1']['money'] += $v['parent1commission'];
                       $comdata['parent1']['score'] += $v['parent1score'];
                   }
                   if($v['parent2']){
                       $parent2 = Db::name('member')->where('id',$v['parent2'])->find();
                       $comdata['parent2']['mid'] = $v['parent2'];
                       $comdata['parent2']['nickname'] = $parent2['nickname'];
                       $comdata['parent2']['headimg'] = $parent2['headimg'];
                       $comdata['parent2']['money'] += $v['parent2commission'];
                       $comdata['parent2']['score'] += $v['parent2score'];
                   }
                   if($v['parent3']){
                       $parent3 = Db::name('member')->where('id',$v['parent3'])->find();
                       $comdata['parent3']['mid'] = $v['parent3'];
                       $comdata['parent3']['nickname'] = $parent3['nickname'];
                       $comdata['parent3']['headimg'] = $parent3['headimg'];
                       $comdata['parent3']['money'] += $v['parent3commission'];
                       $comdata['parent3']['score'] += $v['parent3score'];
                   }
               }
               $comdata['parent1']['money'] = round($comdata['parent1']['money'],2);
               $comdata['parent2']['money'] = round($comdata['parent2']['money'],2);
               $comdata['parent3']['money'] = round($comdata['parent3']['money'],2);
           }
           if($type =='recharge'){
               $detail['totalprice'] = $detail['money'];
           }elseif($type =='maidan'){
               $detail['totalprice'] = $detail['paymoney'];
           }elseif($type =='form'){
               $detail['totalprice'] = $detail['money'];
           }elseif($type =='restaurant_shop'){
               $detail['tablename'] = Db::name('restaurant_table')->where('id',$detail['tableid'])->value('name');
           }
           $detail['realprice'] = dd_money_format( $detail['totalprice'] - $detail['refund_money']);
           //订单类型
           $ordertypename = \app\common\Order::getOrderTypeName($detail['type']);
           $detail['ordertypename'] =  $ordertypename;
           $orderdetail = $detail??[];
           $member = Db::name('member')->field('id,nickname,headimg,realname,tel,wxopenid,unionid')->where('id',$detail['mid'])->find();
           if(!$member) $member = ['id'=>$detail['mid'],'nickname'=>'','headimg'=>''];
           return json(['order'=>$orderdetail,'member'=>$member,'oglist'=>$oglist,'comdata'=>$comdata]);
       }
    }
    public function getrechargeorderdetail()
    {
        $orderid = input('param.orderid');
        $order = Db::name('recharge_order')->where('aid',aid)->where('id',$orderid)->find();
        $payorder = Db::name('payorder')->where('id',$order['payorderid'])->where('type','recharge')->where('aid',aid)->find();
        if($order['paytypeid'] == 5) {
            if($payorder) {
                if($payorder['check_status'] === 0) {
                    $payorder['check_status_label'] = '待审核';
                }elseif($payorder['check_status'] == 1) {
                    $payorder['check_status_label'] = '通过';
                }elseif($payorder['check_status'] == 2) {
                    $payorder['check_status_label'] = '驳回';
                }else{
                    $payorder['check_status_label'] = '未上传';
                }
                if($payorder['paypics']) {
                    $payorder['paypics'] = explode(',', $payorder['paypics']);
                    foreach ($payorder['paypics'] as $item) {
                        $payorder['paypics_html'] .= '<img src="'.$item.'" style="width:200px;height:200px" onclick="preview(this)"/>';
                    }
                }
            }
        }
        return json(['status'=>1,'order'=>$order,'payorder' => $payorder]);
    }
    //转账审核
    public function transferCheck(){
        if(getcustom('money_recharge_transfer')){
            $orderid = input('post.orderid/d');
            $st = input('post.st/d');

            $order = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->field('id,status')->find();
            if($order['status']!=0){
                return json(['status'=>0,'msg'=>'该订单状态不允许审核']);
            }

            if($st==1){
                $up = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>1]);
                if($up){
                    \app\common\System::plog('余额充值订单转账审核驳回'.$orderid);
                    return json(['status'=>1,'msg'=>'审核通过']);
                }else{
                    return json(['status'=>0,'msg'=>'操作失败']);
                }
            }else{
                $up = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>-1]);
                if($up){
                    \app\common\System::plog('余额充值订单转账审核通过'.$orderid);
                    return json(['status'=>1,'msg'=>'转账已驳回']);
                }else{
                    return json(['status'=>0,'msg'=>'操作失败']);
                }
            }
        }
    }
    //付款审核
    public function payCheck(){
        if(getcustom('money_recharge_transfer')){
            $orderid = input('post.orderid/d');
            $st = input('post.st/d');
            $remark = input('post.remark');
            $order = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->find();

            if($order['status']!=0){
                return json(['status'=>0,'msg'=>'该订单状态不允许审核付款']);
            }

            if($st==2){
                Db::name('payorder')->where('id',$order['payorderid'])->where('aid',aid)->where('type','recharge')->update(['check_status'=>2,'check_remark'=>$remark]);
                //Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>-1]);
                \app\common\System::plog('余额充值订单付款审核驳回'.$orderid);
                return json(['status'=>1,'msg'=>'付款已驳回']);
            }elseif($st == 1){

                \app\model\Payorder::payorder($order['payorderid'],t('转账汇款'),5,'');
                Db::name('payorder')->where('id',$order['payorderid'])->where('type','recharge')->where('aid',aid)->update(['check_status'=>1,'check_remark'=>$remark]);

                Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['status'=>1,'paytime' => time()]);

                \app\common\System::plog('余额充值订单付款审核通过'.$orderid);
                return json(['status'=>1,'msg'=>'审核通过']);
            }
        }
    }
	//充值记录
	public function rechargelog(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'recharge_order.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'recharge_order.id desc';
			}
			$where = [];
			$where[] = ['recharge_order.aid','=',aid];

            $where_status = [];
            if(getcustom('money_recharge_transfer')){
                $where_status[] = ['recharge_order.status','=',1];
                $where[] = ['recharge_order.paytype','<>','null'];
                $status = input('param.status');
                $transfer_check = input('param.transfer_check');
                if(isset($transfer_check) && $transfer_check != '') {
                    if($transfer_check == 0) {
                        $where[] = ['recharge_order.transfer_check','=',$transfer_check];
                        $where[] = ['recharge_order.paytypeid','=',5];
                        $where[] = ['recharge_order.paytype','<>','随行付支付'];
                    }else{
                        $where[] = ['recharge_order.transfer_check','=',$transfer_check];
                    }
                }
                if($status == 1){
                    $where[] = ['recharge_order.status','=',1];
                    $where_status = [];
                }elseif($status == 2){
                    $where[] = ['recharge_order.status','=',0];
                    $where[] = ['recharge_order.paytypeid','=',5];
                    $where[] = ['recharge_order.paytype','<>','随行付支付'];
                }
            }else{
                $where[] = ['recharge_order.status','=',1];
            }
            if (getcustom('recharge_use_mendian')){
                if($this->mdid){
                    $where[] = ['recharge_order.mdid','=',$this->mdid];
                }
                if(input('param.mdid')) $where[] = ['recharge_order.mdid','=',input('param.mdid')];
            }
			if(input('param.id')) $where[] = ['recharge_order.id','=',trim(input('param.id'))];
			if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
			if(input('param.mid')) $where[] = ['recharge_order.mid','=',trim(input('param.mid'))];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['recharge_order.createtime','>=',strtotime($ctime[0])];
                $where[] = ['recharge_order.createtime','<',strtotime($ctime[1]) ];
            }
			$count = 0 + Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->count();
			$data = Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach ($data as &$v){
                $v['payorder'] = Db::name('payorder')->where('aid',aid)->where('orderid',$v['id'])->where('type','recharge')->find();
                if(getcustom('money_recharge_transfer')) {
                    $v['money_recharge_transfer'] = true;
                    $v['payorder_check_status'] = Db::name('payorder')->where('aid',aid)->where('type','recharge')->where('orderid',$v['id'])->value('check_status');
                }

                if($v['status']==1){
                    $v['status_name'] = '<span style="color:green">充值成功</span>';
                }else{
                    if(getcustom('money_recharge_transfer') && $v['paytypeid'] == 5 && $v['paytype'] != '随行付支付'){
                        if($v['transfer_check'] == 1){
                            if($v['payorder_check_status'] == 2){
                                $v['status_name'] = '<span style="color:#FC5531">凭证被驳回</span>';
                            }else if($v['payorder_check_status'] == 1){
                                $v['status_name'] = '<span style="color:#FC5531">审核通过</span>';
                            }else{
                                $v['status_name'] = '<span style="color:orange">凭证待审核</span>';
                            }
                        }else if($v['transfer_check'] == -1){
                            $v['status_name'] = '<span style="color:red">已驳回</span>';
                        }else {
                            $v['status_name'] = '<span style="color:#888">待审核</span>';
                        }
                    }else{
                        $v['status_name'] = '<span style="color:#888">充值失败</span>';
                    }
                }
                
                if (getcustom('recharge_use_mendian')){
                    $mendian_name = '';
                    if($v['mdid'] > 0){
                      $mendian_name = Db::name('mendian')->where('aid',aid)->where('bid',bid)->where('id',$v['mdid'])->value('name');
                    }
                    $v['mendian_name'] =  $mendian_name;
                    $un = db('admin_user')->where('aid',aid)->where('bid',bid)->where('id',$v['uid'])->value('un');
                    $v['un'] = $un??'';
                }
            }
            $tdata = [];
			$total_money =  Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->where($where_status)->sum('recharge_order.money');
            $tdata['total_money']  =$total_money;
			if(getcustom('member_recharge_detail_refund')){
                $total_refund_money =0+ Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->where($where_status)->sum('recharge_order.refund_money');
                $total_real_money =  $total_money - $total_refund_money;
                $tdata['total_refund_money']  = dd_money_format($total_refund_money);  //退款金额
                $tdata['total_real_money']  = dd_money_format($total_real_money);  //实际充值金额
                $total_give_money =0+ Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->where($where_status)->sum('recharge_order.give_money');
                $total_refund_give_money = Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->where($where_status)->sum('recharge_order.refund_give_money');
                $tdata['total_give_money']  = dd_money_format($total_give_money-$total_refund_give_money);  //赠送金额
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'tdata'=>$tdata]);
		}
		
		if(getcustom('recharge_use_mendian')){
            $whereM = [];
            $whereM[] = ['aid','=',aid];
            $whereM[] = ['status','=',1];
            $whereM[] = ['bid','=',0];
            if($this->mdid){
                $whereM[] = ['id','=',$this->mdid];
            }
            //是不是置顶
            $mdlist = Db::name('mendian')->where($whereM)->order('id')->field('id,name')->select()->toArray();
            View::assign('mdlist',$mdlist);
        }
		return View::fetch();
    }
	//充值记录导出
	public function rechargelogexcel(){
		if(input('param.field') && input('param.order')){
			$order = 'recharge_order.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'recharge_order.id desc';
		}
        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
		$where = [];
		$where[] = ['recharge_order.aid','=',aid];

        $where_status = [];
        if(getcustom('money_recharge_transfer')){
            $where_status[] = ['recharge_order.status','=',1];
            $where[] = ['recharge_order.paytype','<>','null'];
            $status = input('param.status');
            $transfer_check = input('param.transfer_check');
            if(isset($transfer_check) && $transfer_check != '') {
                if($transfer_check == 0) {
                    $where[] = ['recharge_order.transfer_check','=',$transfer_check];
                    $where[] = ['recharge_order.paytypeid','=',5];
                    $where[] = ['recharge_order.paytype','<>','随行付支付'];
                }else{
                    $where[] = ['recharge_order.transfer_check','=',$transfer_check];
                }
            }
            if($status == 1){
                $where[] = ['recharge_order.status','=',1];
                $where_status = [];
            }elseif($status == 2){
                $where[] = ['recharge_order.status','=',0];
                $where[] = ['recharge_order.paytypeid','=',5];
                $where[] = ['recharge_order.paytype','<>','随行付支付'];
            }
        }else{
            $where[] = ['recharge_order.status','=',1];
        }

		if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
		if(input('param.mid')) $where[] = ['recharge_order.mid','=',trim(input('param.mid'))];
        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['recharge_order.createtime','>=',strtotime($ctime[0])];
            $where[] = ['recharge_order.createtime','<',strtotime($ctime[1]) + 86400];
        }
        if (getcustom('recharge_use_mendian')){
            if($this->mdid){
                $where[] = ['recharge_order.mdid','=',$this->mdid];
            }
        }
		$list = Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')
            ->join('member member','member.id=recharge_order.mid')
            ->where($where)->order($order)
            ->page($page,$limit)
            ->select()->toArray();
        $count = Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')
            ->join('member member','member.id=recharge_order.mid')
            ->where($where)->order($order)
            ->count();
        $total_money =  Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->where($where_status)->sum('recharge_order.money');
		$title = array();
		$title[] = t('会员').'信息';
		if(getcustom('recharge_use_mendian')){
            $title[] = '所属门店';
            $title[] = '操作员';
        }
		$title[] = '充值金额';
		$title[] = '充值时间';
		$title[] = '支付方式';
		$title[] = '付款单号';
		$title[] = '付款时间';
		$title[] = '状态';
		$data = array();
		foreach($list as $v){

            if($v['status']==1){
                $v['status_name'] = '充值成功';
            }else {
                if (getcustom('money_recharge_transfer') && $v['paytypeid'] == 5 && $v['paytype'] != '随行付支付') {
                    $v['payorder_check_status'] = Db::name('payorder')->where('aid', aid)->where('type', 'recharge')->where('orderid', $v['id'])->value('check_status');
                    if ($v['transfer_check'] == 1) {
                        if ($v['payorder_check_status'] == 2) {
                            $v['status_name'] = '凭证被驳回';
                        } else if ($v['payorder_check_status'] == 1) {
                            $v['status_name'] = '审核通过';
                        } else {
                            $v['status_name'] = '凭证待审核';
                        }
                    } else if ($v['transfer_check'] == -1) {
                        $v['status_name'] = '已驳回';
                    } else {
                        $v['status_name'] = '待审核';
                    }
                } else {
                    $v['status_name'] = '充值失败';
                }
            }

			$tdata = array();
			$tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
			if(getcustom('recharge_use_mendian')){
                $mendian_name = '';
                if($v['mdid'] > 0){
                    $mendian_name = Db::name('mendian')->where('aid',aid)->where('id',$v['mdid'])->value('name');
                }
                $tdata[] = $mendian_name;
                $un = db('admin_user')->where('aid',aid)->where('bid',bid)->where('id',$v['uid'])->value('un');
                $tdata[] = $un?$un.'(操作员ID:'.$v['uid'].')':'';
            }
			$tdata[] = $v['money'];
			$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
			$tdata[] = $v['paytype'];
			$tdata[] = $v['paynum'];
			$tdata[] = $v['paytime'] ? date('Y-m-d H:i:s',$v['paytime']) : '';
			$tdata[] = $v['status_name'];
			$data[] = $tdata;
		}
		if(!$data){ //最后一页没有数据的时候再追加，放到最后
            $data[]= [
                '',
                '',
                '',
                '',
                '',
                '累计充值金额：'.dd_money_format($total_money)
            ];
        }
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
		$this->export_excel($title,$data);
	}
	//充值记录删除
	public function rechargelogdel(){
		$ids = input('post.ids/a');
		Db::name('recharge_order')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除充值记录'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
    public function rechargeprint(){
        if(getcustom('recharge_order_wifiprint')){
            $id = input('post.id/d');
            $rs = \app\common\Wifiprint::print(aid,'recharge',$id,0,-1,-1,'shop',-1,['opttype' => 'recharge']);
            return json($rs);
        }
    }
	public function rechargerefund(){
        if(getcustom('member_recharge_detail_refund')){
            $orderid = input('param.orderid/d');
            $inputmoney = floatval(input('param.money'));
            $refund_reason = input('param.refund_reason');
            $give_money =  floatval(input('param.give_money'));
            if($inputmoney <=0 && $give_money <=0)return json(['status'=>0,'msg'=>'请输入退款金额或赠送退款金额']);
            $order = Db::name('recharge_order')->where('aid',aid)->where('id',$orderid)->find();
            if(!$order)return json(['status'=>0,'msg'=>'订单不存在']);
            if($inputmoney > $order['money']){
                return json(['status'=>0,'msg'=>'退款金额不超过实际充值金额']);
            }
            //充值金额退款
            if($give_money > 0 && $give_money >$order['give_money'] ){
                return json(['status'=>0,'msg'=>'退款赠送金额不超过实际赠送金额']);
            }
            if($inputmoney > 0){
                $rs = \app\common\Order::refund($order,$inputmoney,'充值退款，订单号：'.$order['ordernum']);
                if($rs['status'] ==1){
                    Db::name('recharge_order')->where('aid',aid)->where('id',$orderid)->update(['refund_status' => 2,'refund_time' => time(),'refund_money' => $inputmoney,'refund_reason' => $refund_reason]);
                    \app\common\Member::addmoney(aid,$order['mid'],$inputmoney*-1,t('余额').'充值退款扣除,订单号:'.$order['ordernum']);
                }else{
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }
            if($give_money > 0 ){
                \app\common\Member::addmoney(aid,$order['mid'],$give_money*-1,t('余额').'充值赠送金额退款扣除,订单号:'.$order['ordernum']);
                Db::name('recharge_order')->where('aid',aid)->where('id',$orderid)->update(['refund_status' => 2,'refund_time' => time(),'refund_reason' => $refund_reason,'refund_give_money' => $give_money]);
            }
            return json(['status'=>1,'msg'=>'退款成功']);
        }
    }
    public function rechargerefundprint(){
        if(getcustom('member_recharge_detail_refund')) {
            $id = input('post.id/d');
            $rs = \app\common\Wifiprint::print(aid, 'recharge', $id, 0,-1,-1,'shop',-1,['opttype' => 'recharge_refund','un' => $this->uid]);
            return json($rs);
        } 
    }
    
	//提现记录
	public function withdrawlog(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'member_withdrawlog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'member_withdrawlog.id desc';
			}
			$where = [];
			$where[] = ['member_withdrawlog.aid','=',aid];
			if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
			if(input('param.mid')) $where[] = ['member_withdrawlog.mid','=',trim(input('param.mid'))];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['member_withdrawlog.status','=',input('param.status')];

            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_withdrawlog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_withdrawlog.createtime','<',strtotime($ctime[1])];
            }
            if(input('id')){
                $where[] = ['member_withdrawlog.id','=',input('id')];
            }
            $field = 'member.nickname,member.headimg,member.tel,member.realname,member.usercard,member_withdrawlog.*';
            if(getcustom('fuwu_usercenter')){
                $field .= ',member.fuwu_uid';
            }
			$count = 0 + Db::name('member_withdrawlog')->alias('member_withdrawlog')->field($field)->join('member member','member.id=member_withdrawlog.mid')->where($where)->count();
			$data = Db::name('member_withdrawlog')->alias('member_withdrawlog')->field($field)->join('member member','member.id=member_withdrawlog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
			    if(strtolower($v['wx_state'])=='fail'){
			        //获取失败原因
                    $reason_arr = explode('转账失败',$v['reason']);
                    if($reason_arr[1]){
                        $reason_msg = \app\common\Wxpay::transfer_fail_reason_msg($reason_arr[1]);
                        $data[$k]['reason'] = '转账失败:'.$reason_msg;
                    }
                }
			    if(getcustom('fuwu_usercenter')){
                    //所属服务中心
                    $fuwu_name = '';
                    if($v['fuwu_uid']){
                        $admin_user = Db::name('admin_user')->where('aid',aid)->where('is_fuwu',1)->where('id',$v['fuwu_uid'])->find();
                        if($admin_user){
                            $fuwu_name = $admin_user['fuwu_name'];
                        }
                    }
                    $data[$k]['fuwu_name'] = $fuwu_name;
                }
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
    }
	//快商小额通推送记录
	public function withdrawlogxiaoetong(){
		if(getcustom('transfer_farsion')){
			if(request()->isAjax()){
				$page = input('param.page');
				$limit = input('param.limit');
				if(input('param.field') && input('param.order')){
					$order = 'member_withdrawlog_xiaoetong.'.input('param.field').' '.input('param.order');
				}else{
					$order = 'member_withdrawlog_xiaoetong.id desc';
				}
				$where = [];
				$where[] = ['member_withdrawlog_xiaoetong.aid','=',aid];
				$where[] = ['member_withdrawlog_xiaoetong.withdraw_type','=','余额提现'];
				if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
				if(input('param.mid')) $where[] = ['member_withdrawlog_xiaoetong.mid','=',trim(input('param.mid'))];
				if(input('?param.status') && input('param.status')!=='') $where[] = ['member_withdrawlog_xiaoetong.status','=',input('param.status')];
				$count = 0 + Db::name('member_withdrawlog_xiaoetong')->alias('member_withdrawlog_xiaoetong')->field('member.nickname,member.headimg,member.tel,member.realname,member.usercard,member_withdrawlog_xiaoetong.*')->join('member member','member.id=member_withdrawlog_xiaoetong.mid')->where($where)->count();
				$data = Db::name('member_withdrawlog_xiaoetong')->alias('member_withdrawlog_xiaoetong')->field('member.nickname,member.headimg,member.tel,member.realname,member.usercard,member_withdrawlog_xiaoetong.*')->join('member member','member.id=member_withdrawlog_xiaoetong.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
				return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
			}
		}
		return View::fetch();
    }
	//提现记录导出
	public function withdrawlogexcel(){
		if(input('param.field') && input('param.order')){
			$order = 'member_withdrawlog.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'member_withdrawlog.id desc';
		}
        $page = input('param.page')?:1;
        $limit = input('param.limit')?:10;
		$where = [];
		$where[] = ['member_withdrawlog.aid','=',aid];
		if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
		if(input('param.mid')) $where[] = ['member_withdrawlog.mid','=',trim(input('param.mid'))];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['member_withdrawlog.status','=',input('param.status')];

        if(input('param.ctime') ){
            $ctime = explode(' ~ ',input('param.ctime'));
            $where[] = ['member_withdrawlog.createtime','>=',strtotime($ctime[0])];
            $where[] = ['member_withdrawlog.createtime','<',strtotime($ctime[1])];
        }

        $field = 'member.nickname,member.headimg,member.tel,member.realname,member.usercard,member_withdrawlog.*';
        if(getcustom('fuwu_usercenter')){
            $field .= ',member.fuwu_uid';
        }
		$list = Db::name('member_withdrawlog')->alias('member_withdrawlog')
            ->field($field)
            ->join('member member','member.id=member_withdrawlog.mid')->where($where)->order($order)
            ->page($page,$limit)
            ->select()->toArray();
        $count = Db::name('member_withdrawlog')->alias('member_withdrawlog')
            ->field($field)
            ->join('member member','member.id=member_withdrawlog.mid')->where($where)->order($order)
            ->count();
		$title = array();
		$title[] = t('会员').'信息';
		if(getcustom('fuwu_usercenter')){
            $title[] = '所属'.t('服务中心');
        }
        $title[] = '手机号';
		$title[] = '提现金额';
		$title[] = '打款金额';
		$title[] = '提现方式';
		$title[] = '收款账号';
        $title[] = '身份信息';
		$title[] = '提现时间';
		$title[] = '状态';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
			if(getcustom('fuwu_usercenter')){
                //所属服务中心
                $fuwu_name = '';
                if($v['fuwu_uid']){
                    $admin_user = Db::name('admin_user')->where('aid',aid)->where('is_fuwu',1)->where('id',$v['fuwu_uid'])->find();
                    if($admin_user){
                        $fuwu_name = $admin_user['fuwu_name'];
                    }
                }
                $tdata[] = $fuwu_name;
            }
            $tdata[] = $v['tel'];
            $tdata[] = $v['txmoney'];
			$tdata[] = $v['money'];
			$tdata[] = $v['paytype'];
			if($v['paytype'] == '支付宝'){
				$tdata[] = $v['aliaccountname'].' '.$v['aliaccount'];
			}elseif($v['paytype'] == '银行卡'){
				$tdata[] = $v['bankname'] . ' - ' .$v['bankcarduser']. ' - '.$v['bankcardnum'];
			}else{
				$tdata[] = '';
			}
            $tdata[] = $v['realname'].' '.$v['usercard'];
			$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
			$st = '';
			if($v['status']==0){
				$st = '审核中';
			}elseif($v['status']==1){
				$st = '已审核';
			}elseif($v['status']==2){
				$st = '已驳回';
			}elseif($v['status']==3){
				$st = '已打款';
			}
			$tdata[] = $st;
			$data[] = $tdata;
		}
        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
		$this->export_excel($title,$data);
	}
	//提现记录改状态
	public function withdrawlogsetst(){
		$id = input('post.id/d');
		$st = input('post.st');
		$reason = input('post.reason');
		$info = Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        $info['txmoney'] = dd_money_format($info['txmoney']);
        $info['money'] = dd_money_format($info['money']);
        $rs = [];
		if($st==10){//微信打款
			if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
			$admin_set = $this->adminSet;
			if($admin_set['wx_transfer_type']==1){
			    //使用了新版的商家转账功能
                $paysdk = new WxPayV3(aid,$info['mid'],$info['platform']);
                $rs = $paysdk->transfer($info['ordernum'],$info['money'],'',t('余额').'提现','member_withdrawlog',$info['id']);
                if($rs['status']==1){
                    $data = [
                        'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                        'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                        'wx_state' => $rs['data']['state'],//转账状态
                        'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                    ];
                    Db::name('member_withdrawlog')->where('id',$info['id'])->update($data);
                }else{
                    $data = [
                        'wx_transfer_msg' => $rs['msg'],
                    ];
                    Db::name('member_withdrawlog')->where('id',$info['id'])->update($data);
                }
            }else{
                $rs = \app\common\Wxpay::transfers(aid,$info['mid'],$info['money'],$info['ordernum'],$info['platform'],t('余额').'提现');
                if($rs['status']==1){
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'reason'=>$reason,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                }
            }
			if($rs['status']==0){
				return json(['status'=>0,'msg'=>$rs['msg']]);
			}else{
				$this->withdrawSuccessNotice($info);
                if(getcustom('money_commission_withdraw_fenxiao')){
                    \app\common\Fenxiao::jiesuanWithdrawCommission(aid,$info,'money_withdraw');
                }
				\app\common\System::plog('余额提现微信打款'.$id);
				return json(['status'=>1,'msg'=>$rs['msg']]);
			}
		}else if($st == 20){
            if(getcustom('pay_adapay')){
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                $adapay = Db::name('adapay_member')->where('aid',aid)->where('mid',$info['mid'])->find();
                $rs = \app\custom\AdapayPay::balancePay(aid,'h5',$adapay['member_id'],$info['ordernum'],$info['money']);
                if($rs['status'] == 0){
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$info['id'])->update(['reason'=>$rs['msg']]);
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }else{
                    //从用户余额中进行提现到银行卡
                    $drs = \app\custom\AdapayPay::drawcash(aid,'h5',$adapay['member_id'],$info['ordernum'],$info['money']);
                    if($drs['status'] == 0){
                        Db::name('member_withdrawlog')->where('aid',aid)->where('id',$info['id'])->update(['reason'=>$drs['msg']]);
                        return json(['status'=>0,'msg'=>$drs['msg']]);
                    }
                   
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['balance_seq_id'],'reason'=>'']);
                    $this->withdrawSuccessNotice($info);
                    \app\common\System::plog('佣金提现汇付天下打款'.$id);
                    return json(['status'=>1,'msg'=>'已提交打款，请耐心等待']);
                }
            }
        }else if($st==30){
        	if(getcustom('alipay_auto_transfer')){
	        	//支付宝打款
				if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
				//查询会员信息
				$member = Db::name('member')->where('id',$info['mid'])->field('aliaccount,aliaccountname')->find();
				if(!$member){
					return json(['status'=>0,'msg'=>t('会员').'不存在']);
				}
				if(empty($info['aliaccount']) || empty($info['aliaccountname']) ){
					return json(['status'=>0,'msg'=>t('会员').'支付宝信息不完整']);
				}
				$rs = \app\common\Alipay::transfers(aid,$info['ordernum'],$info['money'],t('余额').'提现',$info['aliaccount'],$info['aliaccountname'],t('余额').'提现');
				if($rs['status']==0){
					return json(['status'=>0,'msg'=>$rs['msg']]);
				}else{
					Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['pay_fund_order_id']]);
                    $this->withdrawSuccessNotice($info);
					\app\common\System::plog('余额提现支付宝打款'.$id);
					return json(['status'=>1,'msg'=>$rs['msg']]);
				}
			}
        }else if($st=='huifu'){
            if(getcustom('pay_huifu')){
                //汇付斗拱打款 银行卡代发
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                //查询会员信息
                $member = Db::name('member')->where('id',$info['mid'])->find();
                if(!$member){
                    return json(['status'=>0,'msg'=>t('会员').'不存在']);
                }

                $appinfo = \app\common\System::appinfo(aid);
                if(empty($appinfo['huifu_sys_id'])) $appinfo = \app\common\System::appinfo(aid,'wx');
                if(empty($appinfo['huifu_sys_id'])) $appinfo = \app\common\System::appinfo(aid,'h5');
                if(empty($appinfo['huifu_sys_id'])) return json(['status'=>0,'msg'=>'汇付支付信息配置错误']);
                $huifu = new \app\custom\Huifu($appinfo,aid,bid,$member['id'],t('余额').'提现',$info['ordernum'],$info['money']);
                $rs = $huifu->bankSurrogate($info);
                if($rs['status']==0){
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }else{
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['pay_fund_order_id']]);
                    $this->withdrawSuccessNotice($info);
                    \app\common\System::plog('余额提汇付斗拱打款'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }
        }else if($st=='huifu_moneypay'){
            if(getcustom('pay_huifu')){
                //汇付斗拱余额打款 余额支付
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                //查询会员信息
                $member = Db::name('member')->where('id',$info['mid'])->find();
                if(!$member){
                    return json(['status'=>0,'msg'=>t('会员').'不存在']);
                }

                $huifu = new \app\custom\Huifu([],aid,bid,$member['id'],t('余额').'提现',$info['ordernum'],$info['money']);
                $rs = $huifu->moneypayTradeAcctpaymentPay($info['huifu_id'],array_merge($info,['tablename'=>'member_withdrawlog']));
                if($rs['status']==0){
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }elseif($rs['status']==2){//处理中
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>4,'paynum'=>$rs['resp']['hf_seq_id']]);
                    \app\common\System::plog('余额提汇付斗拱余额打款'.$id);
                    return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                }else{
                    $huifu->tradeSettlementEnchashmentRequest();
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['hf_seq_id']]);
                    $this->withdrawSuccessNotice($info);
                    \app\common\System::plog('余额提汇付斗拱余额打款'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }
            }
		}else if($st=='linghuoxin'){
            if(getcustom('extend_linghuoxin')){
                if($info['paytype'] == '灵活薪支付宝' || $info['paytype'] == '灵活薪银行卡'){
                    $member = Db::name('member')->where('id',$info['mid'])->where('aid',aid)->find();
                    $gopay = \app\custom\LinghuoxinCustom::gopay(aid,0,$member,$id,$info,$info['paytype'],1);
                    if($gopay && $gopay['status'] == 1){
                        $updata = [];
                        $updata['taskNo']   = $gopay['data']['taskNo'];
                        $updata['taskdata'] = json_encode($gopay['data']);
                        Db::name('member_withdrawlog')->where('id',$id)->update($updata);
                        return json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        return json(['status'=>0,'msg'=>$msg]);
                    }
                } else {
                    return json(['status' => 0, 'msg' => '提现方式错误']);
                }
            }
        }else if($st=='shangfutong'){
            if(getcustom('shangfutong_daifu')){
                if($info['status']!=1) return json(['status'=>0,'msg'=>'已审核状态才能打款']);
                $sft = new \app\custom\Shangfutong(aid);
                $rs = $sft->transfer($info,1,t('余额').'提现');
                if($rs['status']==0){
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>2,'reason'=>$rs['msg']]);
                    \app\common\Member::addmoney(aid,$info['mid'],$info['txmoney'],t('余额').'提现返还');
                    return json(['status'=>0,'msg'=>$rs['msg']]);
                }elseif($rs['status'] == 1 && $rs['data']['state'] == 2){
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['transferId']]);
                    \app\common\System::plog('余额提现商福通打款'.$id);
                    return json(['status'=>1,'msg'=>$rs['msg']]);
                }else{
                    //处理中 回调修改
                    Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update(['status'=>4,'paynum'=>$rs['data']['transferId']]);
                    \app\common\System::plog('余额提现商福通打款'.$id);
                    return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                }
            }
		}else{
			$up_data = [];
			$up_data['status'] = $st;
			if($reason){
				$up_data['reason'] = $reason;
			}
			Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->update($up_data);
			if($st == 2){//驳回返还余额
				\app\common\Member::addmoney(aid,$info['mid'],$info['txmoney'],t('余额').'提现返还');
				$this->withdrawFailNotice($info,$reason);
				if(getcustom('money_commission_withdraw_fenxiao')){
				    //驳回后，佣金退回
                    Db::name('member_commission_record')->where('aid',aid)->where('status',0)->where('orderid',$info['id'])->where('type','money_withdraw')->update(['status' =>2]);
                }
				\app\common\System::plog('余额提现驳回'.$id);
			}
			if($st==3){
				$this->withdrawSuccessNotice($info);
                if(getcustom('money_commission_withdraw_fenxiao')){
                    \app\common\Fenxiao::jiesuanWithdrawCommission(aid,$info,'money_withdraw');
                }
				\app\common\System::plog('余额提现改为已打款'.$id);
			}
			if($st == 1){
				//小额通提现
				if(getcustom('transfer_farsion')){
					if($info['paytype'] == '小额通支付宝' || $info['paytype'] == '小额通银行卡'){
						$field = 'id,realname,usercard,tel,bankcardnum';
						$userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',$info['mid'])->find();
						$xetService = new  \app\common\Xiaoetong(aid);
						//导入数据
			
						$xet_res = $xetService->sendData($info,$userinfo,'余额提现');	
						//print_r($res);die;
						if($xet_res['code'] == 0){

						}else{
							//\app\common\Member::addmoney(aid,mid,$info['txmoney'],t('余额').'提现返还');
							//Db::name('member_withdrawlog')->where('id',$info['id'])->update(['status' => 2,'reason'=>'快商小额通推送失败'.$res['msg']]);
							Db::name('member_withdrawlog')->where('id',$info['id'])->update(['status' => 0]);
							return json(['status'=>0,'msg'=>'提现失败'.$xet_res['msg']]);
						}
					}
				}
			}
		}
		return json(['status'=>1,'msg'=>'操作成功']);
	}

    public function withdrawlogQuery()
    {
        $id = input('post.id/d');
        $info = Db::name('member_withdrawlog')->where('aid',aid)->where('id',$id)->find();
        if($info['wx_transfer_bill_no']){
            //新版微信商户转账
            $paysdk = new WxPayV3(aid,$info['mid'],$info['platform']);
            $rs = $paysdk->transfer_query($info['ordernum'],'member_withdrawlog',$id);
            if($rs['status']==1){
                $result = $rs['data'];
                if($result['state']=='SUCCESS'){
                    $this->withdrawSuccessNotice($info);
                    return json(['status'=>1,'msg'=>'打款成功！']);
                }elseif($result['state']=='FAIL'){//转账失败
                    return json(['status'=>1,'msg'=>'转账失败']);
                }elseif($result['state']=='CANCELLED'){//已撤销
                    return json(['status'=>1,'msg'=>'已撤销']);
                }else{
                    return json(['status'=>1,'msg'=>'支付处理中']);
                }
            }else{
                return json(['status'=>0,'msg'=>$rs['msg']]);
            }
        }else{
            $huifu = new \app\custom\Huifu([],aid,bid,$info['mid'],t('余额').'提现',$info['ordernum'],$info['money']);
            $rs = $huifu->moneypayTradeAcctpaymentPayQuery($info['paynum']);
            if($rs['status']==0){
                return json(['status'=>0,'msg'=>$rs['msg']]);
            }elseif($rs['status']==2){//处理中
                return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
            }else{
                $this->withdrawSuccessNotice($info);
                return json(['status'=>1,'msg'=>$rs['msg']]);
            }
        }

    }

	//提现记录删除
	public function withdrawlogdel(){
		$ids = input('post.ids/a');
		Db::name('member_withdrawlog')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('余额提现记录删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}

	//充值赠送
	public function giveset(){
		if(request()->isAjax()){
			$info = input('post.info/a');
			$givedata = array();
			$postmoney = input('post.money/a');
			$postgive = input('post.give/a');
            $postgive_score = input('post.give_score/a');
            if(getcustom('yx_money_monthsend')){
            	$postmonth_sendmoney  = input('post.month_sendmoney/a');
            	$postmonth_sendscore  = input('post.month_sendscore/a');
            	$postmonth_sendmoney2 = input('post.month_sendmoney2/a');
            	$postmonth_sendscore2 = input('post.month_sendscore2/a');
            	$postmonth_sendnum    = input('post.month_sendnum/a');
            }
			foreach($postmoney as $k=>$money){
				$data = [
					'money'=>$money,
					'give'=>$postgive[$k],
                    'give_score'=>$postgive_score[$k]
				];
				if(getcustom('yx_money_monthsend')){
	            	$data['month_sendmoney']  = trim($postmonth_sendmoney[$k]);
	            	$data['month_sendscore']  = trim($postmonth_sendscore[$k]);
	            	$data['month_sendmoney2'] = trim($postmonth_sendmoney2[$k]);
	            	$data['month_sendscore2'] = trim($postmonth_sendscore2[$k]);
	            	$data['month_sendnum']    = trim($postmonth_sendnum[$k]);
	            }
				$givedata[] = $data;
			}
			$info['givedata'] = json_encode($givedata,JSON_UNESCAPED_UNICODE);
			$info['caninput'] = $info['caninput'];
			if(Db::name('recharge_giveset')->where('aid',aid)->find()){
				Db::name('recharge_giveset')->where('aid',aid)->update($info);
			}else{
				$info['aid'] = aid;
				$info['createtime'] = time();
				Db::name('recharge_giveset')->insert($info);
			}

            \app\common\System::plog('编辑充值赠送');
			return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('giveset')]);
		}
		$info = Db::name('recharge_giveset')->where('aid',aid)->find();
		if(!$info) $info = ['caninput'=>1];
		View::assign('info',$info);
		return View::fetch();
	}

    public function adminmoneylog()
    {
        if (getcustom('admin_money')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'admin_moneylog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'admin_moneylog.id desc';
                }
                $where = [];
                $where[] = ['admin_moneylog.aid','=',aid];

                if(input('param.mid')) $where[] = ['admin_moneylog.aid','=',trim(input('param.mid'))];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['admin_moneylog.status','=',input('param.status')];
                $count = 0 + Db::name('admin_moneylog')->alias('admin_moneylog')->field('admin_user.un,admin_moneylog.*')->join('admin_user admin_user','admin_user.aid=admin_moneylog.aid and admin_user.isadmin>0 and admin_user.bid=0')->where($where)->count();
                $data = Db::name('admin_moneylog')->alias('admin_moneylog')->field('admin_user.un,admin_moneylog.*')->join('admin_user admin_user','admin_user.aid=admin_moneylog.aid and admin_user.isadmin>0 and admin_user.bid=0')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }
    }

    //todo
    public function huifuBankLog()
    {
        if (getcustom('pay_huifu')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'huifu_bank_log.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'huifu_bank_log.id desc';
                }
                $where = [];
                $where[] = ['huifu_bank_log.aid','=',aid];

                if(input('param.mid')) $where[] = ['huifu_bank_log.aid','=',trim(input('param.mid'))];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['huifu_bank_log.status','=',input('param.status')];
                $count = 0 + Db::name('huifu_bank_log')->alias('huifu_bank_log')->field('admin_user.un,huifu_bank_log.*')->join('admin_user admin_user','admin_user.aid=huifu_bank_log.aid and admin_user.isadmin>0 and admin_user.bid=0')->where($where)->count();
                $data = Db::name('huifu_bank_log')->alias('huifu_bank_log')->field('admin_user.un,huifu_bank_log.*')->join('admin_user admin_user','admin_user.aid=huifu_bank_log.aid and admin_user.isadmin>0 and admin_user.bid=0')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }
    }

    private function withdrawSuccessNotice($info)
    {
        if(getcustom('product_givetongzheng')){
            if($info['tongzheng']>0){
                \app\common\Member::addtongzheng($info['aid'],$info['mid'],$info['tongzheng'],'提现');
            }
        }
        //提现成功通知
        $tmplcontent = [];
        $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
        $tmplcontent['remark'] = '请点击查看详情~';
        $tmplcontent['money'] = (string) round($info['money'],2);
        $tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
        $tempconNew = [];
        $tempconNew['amount2'] = (string) round($info['money'],2);//提现金额
        $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
        \app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
        //订阅消息
        $tmplcontent = [];
        $tmplcontent['amount1'] = $info['money'];
        $tmplcontent['thing3'] = $info['paytype'];
        $tmplcontent['time5'] = date('Y-m-d H:i');

        $tmplcontentnew = [];
        $tmplcontentnew['amount3'] = $info['money'];
        $tmplcontentnew['phrase9'] = $info['paytype'];
        $tmplcontentnew['date8'] = date('Y-m-d H:i');
        \app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
        //短信通知
        $member = Db::name('member')->where('id',$info['mid'])->find();
        if($member['tel']){
            $tel = $member['tel'];
            \app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
        }
    }
    private function withdrawFailNotice($info,$reason='')
    {
        //提现失败通知
        $tmplcontent = [];
        $tmplcontent['first'] = '您的提现申请被商家驳回，可与商家协商沟通。';
        $tmplcontent['remark'] = $reason.'，请点击查看详情~';
        $tmplcontent['money'] = (string) round($info['txmoney'],2);
        $tmplcontent['time'] = date('Y-m-d H:i',$info['createtime']);
        \app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontent,m_url('pages/my/usercenter'));
        //订阅消息
        $tmplcontent = [];
        $tmplcontent['amount1'] = $info['txmoney'];
        $tmplcontent['time3'] = date('Y-m-d H:i',$info['createtime']);
        $tmplcontent['thing4'] = $reason;

        $tmplcontentnew = [];
        $tmplcontentnew['thing1'] = '提现失败';
        $tmplcontentnew['amount2'] = $info['txmoney'];
        $tmplcontentnew['date4'] = date('Y-m-d H:i',$info['createtime']);
        $tmplcontentnew['thing12'] = $reason;
        \app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
        //短信通知
        $member = Db::name('member')->where('id',$info['mid'])->find();
        if($member['tel']){
            $tel = $member['tel'];
            \app\common\Sms::send(aid,$tel,'tmpl_tixianerror',['reason'=>$reason]);
        }
    }

    public function goldmoneylog(){
        if(getcustom('member_goldmoney_silvermoney')){
            //金值明细
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'member_goldmoneylog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'member_goldmoneylog.id desc';
                }
                $where = [];
                $where[] = ['member_goldmoneylog.aid','=',aid];
                if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                if(input('param.mid')) $where[] = ['member_goldmoneylog.mid','=',trim(input('param.mid'))];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['member_goldmoneylog.status','=',input('param.status')];
                $count = 0 + Db::name('member_goldmoneylog')->alias('member_goldmoneylog')->field('member.nickname,member.headimg,member_goldmoneylog.*')->join('member member','member.id=member_goldmoneylog.mid')->where($where)->count();
                $data = Db::name('member_goldmoneylog')->alias('member_goldmoneylog')->field('member.nickname,member.headimg,member_goldmoneylog.*')->join('member member','member.id=member_goldmoneylog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            //统计总增加
            $addnum = 0+Db::name('member_goldmoneylog')->alias('member_goldmoneylog')->field('member.nickname,member.headimg,member_goldmoneylog.*')->join('member member','member.id=member_goldmoneylog.mid')->where('member_goldmoneylog.aid',aid)->where('member_goldmoneylog.goldmoney','>',0)->sum('member_goldmoneylog.goldmoney');
            //统计总减少
            $decnum = 0+Db::name('member_goldmoneylog')->alias('member_goldmoneylog')->field('member.nickname,member.headimg,member_goldmoneylog.*')->join('member member','member.id=member_goldmoneylog.mid')->where('member_goldmoneylog.aid',aid)->where('member_goldmoneylog.goldmoney','<',0)->sum('member_goldmoneylog.goldmoney');
            //合计
            $totalnum = $addnum+$decnum;
            View::assign('addnum',$addnum);
            View::assign('decnum',$decnum);
            View::assign('totalnum',$totalnum);
            return View::fetch();
        }
    }
    public function goldmoneylogexcel(){
        if(getcustom('member_goldmoney_silvermoney')){
            //明细导出
            if(input('param.field') && input('param.order')){
                $order = 'member_goldmoneylog.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'member_goldmoneylog.id desc';
            }
            $page = input('param.page')?:1;
            $limit = input('param.limit')?:10;
            $where = array();
            $where[] = ['member_goldmoneylog.aid','=',aid];
            
            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_goldmoneylog.mid','=',trim(input('param.mid'))];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_goldmoneylog.status','=',input('param.status')];
            $list = Db::name('member_goldmoneylog')->alias('member_goldmoneylog')->field('member.nickname,member.headimg,member_goldmoneylog.*')
                ->join('member member','member.id=member_goldmoneylog.mid')->where($where)->order($order)
                ->page($page,$limit)
                ->select()->toArray();
            $count = Db::name('member_goldmoneylog')->alias('member_goldmoneylog')->field('member.nickname,member.headimg,member_goldmoneylog.*')
                ->join('member member','member.id=member_goldmoneylog.mid')->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员').'信息';
            $title[] = '变更金额';
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            $data = array();
            foreach($list as $v){
                $tdata = array();
                $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
                $tdata[] = $v['goldmoney'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
                $tdata[] = $v['remark'];
                $data[] = $tdata;
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
            $this->export_excel($title,$data);
        }
    }
    public function goldmoneylogdel(){
        if(getcustom('member_goldmoney_silvermoney')){
            $ids = input('post.ids/a');
            Db::name('member_goldmoneylog')->where('aid',aid)->where('id','in',$ids)->delete();
            \app\common\System::plog('删除金值明细'.implode(',',$ids));
            return json(['status'=>1,'msg'=>'删除成功']);
        }
    }

    public function silvermoneylog(){
        if(getcustom('member_goldmoney_silvermoney')){
            //金值明细
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'member_silvermoneylog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'member_silvermoneylog.id desc';
                }
                $where = [];
                $where[] = ['member_silvermoneylog.aid','=',aid];
                if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                if(input('param.mid')) $where[] = ['member_silvermoneylog.mid','=',trim(input('param.mid'))];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['member_silvermoneylog.status','=',input('param.status')];
                $count = 0 + Db::name('member_silvermoneylog')->alias('member_silvermoneylog')->field('member.nickname,member.headimg,member_silvermoneylog.*')->join('member member','member.id=member_silvermoneylog.mid')->where($where)->count();
                $data = Db::name('member_silvermoneylog')->alias('member_silvermoneylog')->field('member.nickname,member.headimg,member_silvermoneylog.*')->join('member member','member.id=member_silvermoneylog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            //统计总增加
            $addnum = 0+Db::name('member_silvermoneylog')->alias('member_silvermoneylog')->field('member.nickname,member.headimg,member_silvermoneylog.*')->join('member member','member.id=member_silvermoneylog.mid')->where('member_silvermoneylog.aid',aid)->where('member_silvermoneylog.silvermoney','>',0)->sum('member_silvermoneylog.silvermoney');
            //统计总减少
            $decnum = 0+Db::name('member_silvermoneylog')->alias('member_silvermoneylog')->field('member.nickname,member.headimg,member_silvermoneylog.*')->join('member member','member.id=member_silvermoneylog.mid')->where('member_silvermoneylog.aid',aid)->where('member_silvermoneylog.silvermoney','<',0)->sum('member_silvermoneylog.silvermoney');
            //合计
            $totalnum = $addnum+$decnum;
            View::assign('addnum',$addnum);
            View::assign('decnum',$decnum);
            View::assign('totalnum',$totalnum);
            return View::fetch();
        }
    }
    public function silvermoneylogexcel(){
        if(getcustom('member_goldmoney_silvermoney')){
            //明细导出
            if(input('param.field') && input('param.order')){
                $order = 'member_silvermoneylog.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'member_silvermoneylog.id desc';
            }
            $page = input('param.page')?:1;
            $limit = input('param.limit')?:10;
            $where = array();
            $where[] = ['member_silvermoneylog.aid','=',aid];
            
            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_silvermoneylog.mid','=',trim(input('param.mid'))];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_silvermoneylog.status','=',input('param.status')];
            $list = Db::name('member_silvermoneylog')->alias('member_silvermoneylog')->field('member.nickname,member.headimg,member_silvermoneylog.*')
                ->join('member member','member.id=member_silvermoneylog.mid')->where($where)->order($order)
                ->page($page,$limit)
                ->select()->toArray();
            $count = Db::name('member_silvermoneylog')->alias('member_silvermoneylog')->field('member.nickname,member.headimg,member_silvermoneylog.*')
                ->join('member member','member.id=member_silvermoneylog.mid')->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员').'信息';
            $title[] = '变更金额';
            $title[] = '变更后剩余';
            $title[] = '变更时间';
            $title[] = '备注';
            $data = array();
            foreach($list as $v){
                $tdata = array();
                $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
                $tdata[] = $v['silvermoney'];
                $tdata[] = $v['after'];
                $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
                $tdata[] = $v['remark'];
                $data[] = $tdata;
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
            $this->export_excel($title,$data);
        }
    }
    public function silvermoneylogdel(){
        if(getcustom('member_goldmoney_silvermoney')){
            $ids = input('post.ids/a');
            Db::name('member_silvermoneylog')->where('aid',aid)->where('id','in',$ids)->delete();
            \app\common\System::plog('删除银值明细'.implode(',',$ids));
            return json(['status'=>1,'msg'=>'删除成功']);
        }
    }
    public function getrechargedetail(){
        if (getcustom('member_recharge_detail_refund')){
            $id = input('param.id');
            $where = [];
            $where[] = ['recharge_order.aid','=',aid];
            $where[] = ['recharge_order.id','=',$id];
            $detail=Db::name('recharge_order')->alias('recharge_order')
                ->field('member.nickname,member.headimg,recharge_order.*')
                ->join('member member','member.id=recharge_order.mid')
                ->where($where)->find();
            return json(['status'=>1,'detail'=>$detail]);
        }
    }

    public function dedamountlog(){
    	if(getcustom('member_dedamount')){
			if(request()->isAjax()){
				$page = input('param.page');
				$limit = input('param.limit');
				if(input('param.field') && input('param.order')){
					$order = 'member_dedamountlog.'.input('param.field').' '.input('param.order');
				}else{
					$order = 'member_dedamountlog.id desc';
				}
				$where = [];
				$where[] = ['member_dedamountlog.aid','=',aid];
				
				if(input('?param.pid') && input('param.pid')!==''){
					$where[] = ['member_dedamountlog.pid','=',input('param.pid')];
				}else{
					$where[] = ['member_dedamountlog.pid','=',0];
				}
				if(input('?param.bid') && input('param.bid')!=='') $where[] = ['member_dedamountlog.bid','=',input('param.bid')];

				if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
				if(input('param.mid')) $where[] = ['member_dedamountlog.mid','=',trim(input('param.mid'))];
	            if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
				if(input('?param.status') && input('param.status')!=='') $where[] = ['member_dedamountlog.status','=',input('param.status')];
	            if(input('param.ctime') ){
	                $ctime = explode(' ~ ',input('param.ctime'));
	                $where[] = ['member_dedamountlog.createtime','>=',strtotime($ctime[0])];
	                $where[] = ['member_dedamountlog.createtime','<',strtotime($ctime[1])];
	            }
				$count = 0 + Db::name('member_dedamountlog')->alias('member_dedamountlog')->field('member.nickname,member.headimg,member_dedamountlog.*')->join('member member','member.id=member_dedamountlog.mid')->where($where)->count();
				$data = Db::name('member_dedamountlog')->alias('member_dedamountlog')->field('member.nickname,member.headimg,member_dedamountlog.*')->join('member member','member.id=member_dedamountlog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
				if($data){
					foreach($data as &$dv){
						$dv['bname'] = '';
						if($dv['bid']){
							$business = Db::name('business')->where('id',$dv['bid'])->where('aid',aid)->field('name')->find();
							if($business){
								$dv['bname'] = '商家'.$business['name'];
							}else{
								$dv['bname'] = '已失效';
							}
						}else{
							$dv['bname'] = '平台';
						}
					}
				}else{
					$data = [];
				}
	            $tongji = [];
				return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'tongji' => $tongji]);
			}
			$business_list = Db::name('business')->where('aid',aid)->field('id,name')->select()->toArray();
            View::assign('business_list',$business_list);
			return View::fetch();
		}
    }

	public function dedamountlogexcel(){
		if(getcustom('member_dedamount')){
			if(input('param.field') && input('param.order')){
				$order = 'member_dedamountlog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'member_dedamountlog.id desc';
			}
	        $page = input('param.page')?:1;
	        $limit = input('param.limit')?:10;
			$where = [];
			$where[] = ['member_dedamountlog.aid','=',aid];
			
			if(input('?param.pid') && input('param.pid')!==''){
				$where[] = ['member_dedamountlog.pid','=',input('param.pid')];
			}else{
				$where[] = ['member_dedamountlog.pid','=',0];
			}
			if(input('?param.bid') && input('param.bid')!=='') $where[] = ['member_dedamountlog.bid','=',input('param.bid')];

			if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
			if(input('param.mid')) $where[] = ['member_dedamountlog.mid','=',trim(input('param.mid'))];
            if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['member_dedamountlog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_dedamountlog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_dedamountlog.createtime','<',strtotime($ctime[1])];
            }

			$list = Db::name('member_dedamountlog')->alias('member_dedamountlog')->field('member.nickname,member.headimg,member_dedamountlog.*')
	            ->join('member member','member.id=member_dedamountlog.mid')->where($where)->order($order)
	            ->page($page,$limit)
	            ->select()->toArray();
	        $count = Db::name('member_dedamountlog')->alias('member_dedamountlog')->field('member.nickname,member.headimg,member_dedamountlog.*')
	            ->join('member member','member.id=member_dedamountlog.mid')->where($where)->order($order)
	            ->count();
			$title = array();
			$title[] = t('会员').'信息';
			$title[] = '变动金额';
			$title[] = '剩余变动金额';
			$title[] = '来源';
			$title[] = '备注';
			$title[] = '变更时间';
			//$title[] = '操作员';
			$data = array();
			foreach($list as $v){
				$tdata = array();
				$tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
				$tdata[] = $v['dedamount'];
				$tdata[] = $v['dedamount2'];

				$bname = '';
				if($v['bid']){
					$business = Db::name('business')->where('id',$v['bid'])->where('aid',aid)->field('name')->find();
					if($business){
						$bname = '商家'.$business['name'];
					}else{
						$bname = '已失效';
					}
				}else{
					$bname = '平台';
				}
				$tdata[] = $bname;

				$tdata[] = $v['remark'];
				$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
				$data[]  = $tdata;
			}
	        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
			$this->export_excel($title,$data);
		}
	}
	public function dedamountlogdel(){
		if(getcustom('member_dedamount')){
			$ids = input('post.ids/a');
			$logs = Db::name('member_dedamountlog')->where('aid',aid)->where('id','in',$ids)->select()->toArray();
			if($logs){
				foreach($logs as $log){
					if($log['dedamount2']>0){
						Db::name('member')->where('id',$log['mid'])->dec('dedamount',$log['dedamount2'])->update();
					}
					Db::name('member_dedamountlog')->where('id',$log['id'])->delete();
				}
			}
			\app\common\System::plog('删除抵扣金明细'.implode(',',$ids));
			return json(['status'=>1,'msg'=>'删除成功']);
		}
	}

	public function shopscorelog(){
    	if(getcustom('member_shopscore')){
			if(request()->isAjax()){
				$page = input('param.page');
				$limit = input('param.limit');
				if(input('param.field') && input('param.order')){
					$order = 'member_shopscorelog.'.input('param.field').' '.input('param.order');
				}else{
					$order = 'member_shopscorelog.id desc';
				}
				$where = [];
				$where[] = ['member_shopscorelog.aid','=',aid];

                //是否有条件筛选
                $wheresearch = false;
				if(input('param.nickname')){
                    $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                    $wheresearch = true;
                } 
				if(input('param.mid')){
                    $where[] = ['member_shopscorelog.mid','=',trim(input('param.mid'))];
                    $wheresearch = true;
                } 
	            if(input('param.tel')){
                    $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
                    $wheresearch = true;
                } 
				if(input('?param.status') && input('param.status')!==''){
                    $where[] = ['member_shopscorelog.status','=',input('param.status')];
                    $wheresearch = true;
                } 
	            if(input('param.ctime') ){
	                $ctime = explode(' ~ ',input('param.ctime'));
	                $where[] = ['member_shopscorelog.createtime','>=',strtotime($ctime[0])];
	                $where[] = ['member_shopscorelog.createtime','<',strtotime($ctime[1])];
                    $wheresearch = true;
	            }
                //统计已发放和未使用的数量
                $countscore = Db::name('member_shopscorelog')->alias('member_shopscorelog')->join('member member','member.id=member_shopscorelog.mid')->where($where)->where("paytype = 'admin' or paytype = 'lipin'")->sum('member_shopscorelog.shopscore');//未使用数量
                if($wheresearch){
                    $mids = Db::name('member')->alias('member')->join('member_shopscorelog member_shopscorelog','member.id=member_shopscorelog.mid')->where($where)->group('member.id')->column('member.id');
                    if($mids){
                        $countscore2 = Db::name('member')->where('aid',aid)->where('shopscore','>',0)->where('id','in',$mids)->sum('shopscore');//未使用数量
                    }else{
                        $countscore2 = 0;//未使用数量
                    }
                }else{
                    $countscore2 = Db::name('member')->where('aid',aid)->where('shopscore','>',0)->sum('shopscore');//未使用数量
                }

				$count = 0 + Db::name('member_shopscorelog')->alias('member_shopscorelog')->field('member.nickname,member.headimg,member_shopscorelog.*')->join('member member','member.id=member_shopscorelog.mid')->where($where)->count();
				$data = Db::name('member_shopscorelog')->alias('member_shopscorelog')->field('member.nickname,member.headimg,member_shopscorelog.*')->join('member member','member.id=member_shopscorelog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                
				if($data){
					foreach($data as &$dv){
						$dv['un'] = '';
						if($dv['uid']){
							$un = Db::name('admin_user')->where('id',$dv['uid'])->where('aid',aid)->value('un');
							$dv['un'] = $un??'已失效';
						}
					}
				}else{
					$data = [];
				}
	            $tongji = [];
				return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'tongji' => $tongji,'countscore' => $countscore,'countscore2' => $countscore2]);
			}
			return View::fetch();
		}
    }

	public function shopscorelogexcel(){
		if(getcustom('member_shopscore')){
			if(input('param.field') && input('param.order')){
				$order = 'member_shopscorelog.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'member_shopscorelog.id desc';
			}
	        $page = input('param.page')?:1;
	        $limit = input('param.limit')?:10;
			$where = [];
			$where[] = ['member_shopscorelog.aid','=',aid];

			if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
			if(input('param.mid')) $where[] = ['member_shopscorelog.mid','=',trim(input('param.mid'))];
            if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['member_shopscorelog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_shopscorelog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_shopscorelog.createtime','<',strtotime($ctime[1])];
            }

			$list = Db::name('member_shopscorelog')->alias('member_shopscorelog')->field('member.nickname,member.headimg,member_shopscorelog.*')
	            ->join('member member','member.id=member_shopscorelog.mid')->where($where)->order($order)
	            ->page($page,$limit)
	            ->select()->toArray();
	        $count = Db::name('member_shopscorelog')->alias('member_shopscorelog')->field('member.nickname,member.headimg,member_shopscorelog.*')
	            ->join('member member','member.id=member_shopscorelog.mid')->where($where)->order($order)
	            ->count();
			$title = array();
			$title[] = t('会员').'信息';
			$title[] = '变动金额';
			$title[] = '剩余变动金额';
			
			$title[] = '备注';
			$title[] = '操作员';
			$title[] = '变更时间';
			$data = array();
			foreach($list as $v){
				$tdata = array();
				$tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
				$tdata[] = $v['shopscore'];
				$tdata[] = $v['after'];

				$tdata[] = $v['remark'];

				$un = '';
				if($v['uid']){
					$un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
					$un = $un??'已失效';
				}
				$tdata[] = $un;

				$tdata[] = date('Y-m-d H:i:s',$v['createtime']);
				$data[]  = $tdata;
			}
	        return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
			$this->export_excel($title,$data);
		}
	}
	public function shopscorelogdel(){
		if(getcustom('member_shopscore')){
			$ids = input('post.ids/a');
			Db::name('member_shopscorelog')->where('aid',aid)->where('id','in',$ids)->delete();
			\app\common\System::plog('删除产品积分明细'.implode(',',$ids));
			return json(['status'=>1,'msg'=>'删除成功']);
		}
	}

    //余额明细
    public function wx_transfer_log(){
        $state_arr = [
            'ACCEPTED' => '转账已受理',
            'PROCESSING' => '转账处理中',
            'WAIT_USER_CONFIRM' => '待收款用户确认',
            'TRANSFERING' => '转账结果尚未明确',
            'SUCCESS' => '转账成功',
            'FAIL' => '转账失败',
            'CANCELING' => '撤销中',
            'CANCELLED' => '撤销完成',
        ];
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 'l.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'l.id desc';
            }
            $where = [];
            $where[] = ['l.aid','=',aid];
            if(input('param.mid') ){
                $where[] = ['l.mid','=',input('param.mid')];
            }
            if(input('?param.state') && input('param.state')!=='') $where[] = ['l.state','=',input('param.state')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['l.createtime','>=',strtotime($ctime[0])];
                $where[] = ['l.createtime','<',strtotime($ctime[1])];
            }
            $count = 0 + Db::name('wx_transfer_log')->alias('l')->field('member.nickname,member.headimg,l.*')->join('member member','member.id=l.mid','left')->where($where)->count();
            $data = Db::name('wx_transfer_log')->alias('l')->field('member.nickname,member.headimg,l.*')->join('member member','member.id=l.mid','left')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            if($data){
                foreach($data as $k=>$v){
                    $data[$k]['state_str'] = $state_arr[$v['state']]??'未知'.$v['state'];
                }
            }
            $tongji = [];
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'tongji' => $tongji]);
        }
        View::assign('state_arr',$state_arr);
        return View::fetch();
    }
    //撤销微信转账
    public function wx_transfer_cancel(){
        if(request()->isAjax()){
            $id = input('post.id');
            $info = Db::name('wx_transfer_log')->where('id',$id)->where('aid',aid)->find();
            if(!in_array($info['state'],['ACCEPTED','PROCESSING','WAIT_USER_CONFIRM'])){
                return json(['status'=>0,'msg'=>'该笔记录状态不可撤销']);
            }
            //使用了新版的商家转账功能
            $platform = Db::name($info['data_tbl'])->where('id',$info['data_id'])->value('platform');
            $paysdk = new WxPayV3(aid,$info['mid'],$platform);
            $res = $paysdk->cancel_transfer($info['ordernum'],$info['data_tbl'],$info['id']);
            if($res['status']==1){
                Db::name('wx_transfer_log')->where('id',$id)->update(['state'=>$res['data']['state']]);
                $data = [
                    'wx_state' => $res['data']['state'],//转账状态
                ];
                if($res['data']['state']=='CANCELLED'){
                    $data['status'] = 2;
                }
                Db::name($info['data_tbl'])->where('id',$info['data_id'])->update($data);
            }
            if($res){
                return json(['status'=>1,'msg'=>'操作成功']);
            }else{
                return json(['status'=>0,'msg'=>'操作失败']);
            }
        }
    }

    public function upgradescorelog(){
        if(getcustom('member_upgradescore')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'member_upgradescorelog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'member_upgradescorelog.id desc';
                }
                $where = [];
                $where[] = ['member_upgradescorelog.aid','=',aid];

                //是否有条件筛选
                $wheresearch = false;
                if(input('param.nickname')){
                    $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                    $wheresearch = true;
                }
                if(input('param.mid')){
                    $where[] = ['member_upgradescorelog.mid','=',trim(input('param.mid'))];
                    $wheresearch = true;
                }
                if(input('param.tel')){
                    $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
                    $wheresearch = true;
                }
                if(input('?param.status') && input('param.status')!==''){
                    $where[] = ['member_upgradescorelog.status','=',input('param.status')];
                    $wheresearch = true;
                }
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['member_upgradescorelog.createtime','>=',strtotime($ctime[0])];
                    $where[] = ['member_upgradescorelog.createtime','<',strtotime($ctime[1])];
                    $wheresearch = true;
                }
                //统计已发放和未使用的数量
                $countscore = Db::name('member_upgradescorelog')->alias('member_upgradescorelog')->join('member member','member.id=member_upgradescorelog.mid')->where($where)->where("paytype = 'admin' or paytype = 'lipin'")->sum('member_upgradescorelog.upgradescore');//未使用数量
                if($wheresearch){
                    $mids = Db::name('member')->alias('member')->join('member_upgradescorelog member_upgradescorelog','member.id=member_upgradescorelog.mid')->where($where)->group('member.id')->column('member.id');
                    if($mids){
                        $countscore2 = Db::name('member')->where('aid',aid)->where('upgradescore','>',0)->where('id','in',$mids)->sum('upgradescore');//未使用数量
                    }else{
                        $countscore2 = 0;//未使用数量
                    }
                }else{
                    $countscore2= Db::name('member')->where('aid',aid)->where('upgradescore','>',0)->sum('upgradescore');//未使用数量
                }
                $count = 0 + Db::name('member_upgradescorelog')->alias('member_upgradescorelog')->field('member.nickname,member.headimg,member_upgradescorelog.*')->join('member member','member.id=member_upgradescorelog.mid')->where($where)->count();
                $data = Db::name('member_upgradescorelog')->alias('member_upgradescorelog')->field('member.nickname,member.headimg,member_upgradescorelog.*')->join('member member','member.id=member_upgradescorelog.mid')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                if($data){
                    foreach($data as &$dv){
                        $dv['un'] = '';
                        if($dv['uid']){
                            $un = Db::name('admin_user')->where('id',$dv['uid'])->where('aid',aid)->value('un');
                            $dv['un'] = $un??'已失效';
                        }
                    }
                }else{
                    $data = [];
                }
                $tongji = [];
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'tongji' => $tongji,'countscore' => $countscore,'countscore2' => $countscore2]);
            }
            $text = \app\model\Hotel::gettext(aid);
            View::assign('text',$text);
            return View::fetch();
        }
    }

    public function upgradescorelogexcel(){
        if(getcustom('member_upgradescore')){
            if(input('param.field') && input('param.order')){
                $order = 'member_upgradescorelog.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'member_upgradescorelog.id desc';
            }
            $page = input('param.page')?:1;
            $limit = input('param.limit')?:10;
            $where = [];
            $where[] = ['member_upgradescorelog.aid','=',aid];

            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
            if(input('param.mid')) $where[] = ['member_upgradescorelog.mid','=',trim(input('param.mid'))];
            if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_upgradescorelog.status','=',input('param.status')];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['member_upgradescorelog.createtime','>=',strtotime($ctime[0])];
                $where[] = ['member_upgradescorelog.createtime','<',strtotime($ctime[1])];
            }

            $list = Db::name('member_upgradescorelog')->alias('member_upgradescorelog')->field('member.nickname,member.headimg,member_upgradescorelog.*')
                ->join('member member','member.id=member_upgradescorelog.mid')->where($where)->order($order)
                ->page($page,$limit)
                ->select()->toArray();
            $count = Db::name('member_upgradescorelog')->alias('member_upgradescorelog')->field('member.nickname,member.headimg,member_upgradescorelog.*')
                ->join('member member','member.id=member_upgradescorelog.mid')->where($where)->order($order)
                ->count();
            $title = array();
            $title[] = t('会员').'信息';
            $title[] = '变动金额';
            $title[] = '剩余变动金额';
            
            $title[] = '备注';
            $title[] = '操作员';
            $title[] = '变更时间';
            $data = array();
            foreach($list as $v){
                $tdata = array();
                $tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
                $tdata[] = $v['upgradescore'];
                $tdata[] = $v['after'];

                $tdata[] = $v['remark'];

                $un = '';
                if($v['uid']){
                    $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                    $un = $un??'已失效';
                }
                $tdata[] = $un;

                $tdata[] = date('Y-m-d H:i:s',$v['createtime']);
                $data[]  = $tdata;
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
            $this->export_excel($title,$data);
        }
    }
    public function upgradescorelogdel(){
        if(getcustom('member_upgradescore')){
            $ids = input('post.ids/a');
            Db::name('member_upgradescorelog')->where('aid',aid)->where('id','in',$ids)->delete();
            \app\common\System::plog('删除升级积分明细'.implode(',',$ids));
            return json(['status'=>1,'msg'=>'删除成功']);
        }
    }

    public function adminairticketmoneylog()
    {
        if (getcustom('extend_hanglvfeike')){
            //机票余额
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'admin_airticketmoneylog.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'admin_airticketmoneylog.id desc';
                }
                $where = [];
                $where[] = ['admin_airticketmoneylog.aid','=',aid];

                if(input('param.mid')) $where[] = ['admin_airticketmoneylog.aid','=',trim(input('param.mid'))];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['admin_airticketmoneylog.status','=',input('param.status')];
                $count = 0 + Db::name('admin_airticketmoneylog')->alias('admin_airticketmoneylog')->field('admin_user.un,admin_airticketmoneylog.*')->join('admin_user admin_user','admin_user.aid=admin_airticketmoneylog.aid and admin_user.isadmin>0 and admin_user.bid=0')->where($where)->count();
                $data = Db::name('admin_airticketmoneylog')->alias('admin_airticketmoneylog')->field('admin_user.un,admin_airticketmoneylog.*')->join('admin_user admin_user','admin_user.aid=admin_airticketmoneylog.aid and admin_user.isadmin>0 and admin_user.bid=0')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }
    }

    public function xianjingiveset(){
        if(getcustom('commission_xianjin_percent')){
            //现金充值赠送
            if(request()->isAjax()){
                $info = input('post.info/a');
                $givedata = array();
                $postmoney = input('post.money/a');
                $postgive = input('post.give/a');
                $postgive_score = input('post.give_score/a');
                foreach($postmoney as $k=>$money){
                    $data = [
                        'money'=>$money,
                        'give'=>$postgive[$k],
                        'give_score'=>$postgive_score[$k]
                    ];
                    $givedata[] = $data;
                }
                $info['givedata'] = json_encode($givedata,JSON_UNESCAPED_UNICODE);
                $info['caninput'] = $info['caninput'];
                if(Db::name('xianjin_recharge_giveset')->where('aid',aid)->find()){
                    Db::name('xianjin_recharge_giveset')->where('aid',aid)->update($info);
                }else{
                    $info['aid'] = aid;
                    $info['createtime'] = time();
                    Db::name('xianjin_recharge_giveset')->insert($info);
                }

                \app\common\System::plog('编辑现金充值赠送');
                return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('giveset')]);
            }
            $info = Db::name('xianjin_recharge_giveset')->where('aid',aid)->find();
            if(!$info) $info = ['caninput'=>1];
            View::assign('info',$info);
            return View::fetch();
        }
    }
}

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

namespace app\controller\yingxiao;
use app\controller\Common;
use think\facade\View;
use think\facade\Db;

class OrderCollectReward extends Common
{
    public function initialize(){
        parent::initialize();
        if(bid > 0) showmsg('无访问权限');
    }

    public function index(){
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            $where = array();
            $where[] = ['cr.aid','=',aid];
            if(input('param.keyword')) $where[] = ['m.nickname|m.tel','like','%'.input('param.keyword').'%'];
            if(input('param.ordernum')) $where[] = ['cr.ordernum','=',input('param.ordernum')];
            if(input('param.orderid')) $where[] = ['cr.orderid','=',input('param.orderid')];
            if(input('param.reward_type')) $where[] = ['cr.reward_type','=',input('param.reward_type')];
            if(input('param.ordertype')) $where[] = ['cr.ordertype','=',input('param.ordertype')];
            if(input('param.ordertime') ){
                $ctime = explode(' ~ ',input('param.ordertime'));
                $where[] = ['cr.ordertime','>=',strtotime($ctime[0])];
                $where[] = ['cr.ordertime','<',strtotime($ctime[1])];
            }
            $count = Db::name('order_collect_reward_record')
                    ->alias('cr')
                    ->field('cr.*,m.nickname,m.headimg')
                    ->join('member m','cr.mid = m.id','left')
                    ->where($where)
                    ->order('cr.id desc')
                    ->count();

            $list = Db::name('order_collect_reward_record')
                    ->alias('cr')
                    ->field('cr.*,m.nickname,m.headimg')
                    ->join('member m','cr.mid = m.id','left')
                    ->where($where)
                    ->page($page,$limit)
                    ->order('cr.id desc')
                    ->select()
                    ->toArray();
            foreach($list as $k=>$vo){
                if($vo['ordertype'] == 'shop'){
                    $oglist = Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$vo['orderid'])->select()->toArray();
                    $goodsdata = [];
                    foreach($oglist as $key=>$og){
                        if($key > 2){
                            $goodshtmlshow = '<div style="font-size:12px;float:left;clear:both;margin:1px 0;display:none">';
                        }else{
                            $goodshtmlshow = '<div style="font-size:12px;float:left;clear:both;margin:1px 0;">';
                        }
                        $goodshtml = $goodshtmlshow.
                            '<div class="table-imgbox"><img lay-src="'.$og['pic'].'" src="'.PRE_URL.'/static/admin/layui/css/modules/layer/default/loading-2.gif"></div>'.
                            '<div style="float: left;width:180px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                            '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$og['name'].'</div>'.
                            '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$og['ggname'].'</span></div>';
                        $goodshtml.='<div style="padding-top:0px;color:#f60;">￥'.$og['sell_price'].' × '.$og['num'].'</div>';
                        $goodshtml.='</div>';
                        $goodshtml.='</div>';
                        $goodsdata[] = $goodshtml;
                    }
                    $list[$k]['goodsdata'] = implode('',$goodsdata);
                    $list[$k]['ordertype'] = '商城订单';
                }
                if($vo['ordertype'] == 'collage'){
                    $collage = Db::name('collage_order')->where('id',$vo['orderid'])->find();
                    $goodsdata = [];
                    $goodsdata[] = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                        '<img src="'.$collage['propic'].'" style="max-width:60px;float:left">'.
                        '<div style="float: left;width:160px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                        '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$collage['proname'].'</div>'.
                        '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$collage['ggname'].'</span></div>'.
                        '<div style="padding-top:0px;color:#f60;">￥'.$collage['sell_price'].' × '.$collage['num'].'</div>'.
                        '</div>'.
                        '</div>';
                    $list[$k]['goodsdata'] = implode('',$goodsdata);
                    $list[$k]['ordertype'] = '多人拼团订单';
                }
                if($vo['ordertype'] == 'tuangou'){
                    $tg = Db::name('tuangou_order')->where('id',$vo['orderid'])->find();
                    $goodsdata = [];
                    $goodsdata[] = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                        '<img src="'.$tg['propic'].'" style="max-width:60px;float:left">'.
                        '<div style="float: left;width:160px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                        '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$tg['proname'].'</div>'.
                        '<div style="padding-top:0px;color:#f60;">￥'.$tg['sell_price'].' × '.$tg['num'].'</div>'.
                        '</div>'.
                        '</div>';
                    $list[$k]['goodsdata'] = implode('',$goodsdata);
                    $list[$k]['ordertype'] = '团购订单';
                }
                if($vo['ordertype'] == 'scoreshop'){
                    $oglist = Db::name('scoreshop_order_goods')->where('aid',aid)->where('orderid',$vo['orderid'])->select()->toArray();
                    $goodsdata=array();
                    foreach($oglist as $og){
                        $og['score_price'] = dd_money_format($og['score_price'],2);
                        $goodsdata[] = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                            '<img src="'.$og['pic'].'" style="max-width:60px;float:left">'.
                            '<div style="float: left;width:160px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                            '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$og['name'].'</div>'.
                            ($og['ggname'] ? '<div style="padding-top:0px;color:#f60"><span style="color:#666">规格：'.$og['ggname'].'</span></div>' : '<div style="padding-top:0px;color:#f60"><span style="color:#888">价值：￥'.$og['sell_price'].'</span></div>').
                            '<div style="padding-top:0px;color:#f60;">'.($og['money_price']>0?'￥'.$og['money_price'].'+':'').$og['score_price'].t('积分').' × '.$og['num'].'</div>'.
                            '</div>'.
                            '</div>';
                    }
                    $list[$k]['goodsdata'] = implode('',$goodsdata);
                    $list[$k]['ordertype'] = '积分兑换订单';
                }
                if($vo['ordertype'] == 'businessshop'){
                    $business = Db::name('business')->where('id',$vo['bid'])->value('name');
                    $oglist = Db::name('shop_order_goods')->where('aid',aid)->where('bid',$vo['bid'])->where('orderid',$vo['orderid'])->select()->toArray();
                    $goodsdata = [];
                    foreach($oglist as $key=>$og){
                        if($key > 2){
                            $goodshtmlshow = '<div style="font-size:12px;float:left;clear:both;margin:1px 0;display:none">';
                        }else{
                            $goodshtmlshow = '<div style="font-size:12px;float:left;clear:both;margin:1px 0;">';
                        }
                        $goodshtml = $goodshtmlshow.
                            '<div class="table-imgbox"><img lay-src="'.$og['pic'].'" src="'.PRE_URL.'/static/admin/layui/css/modules/layer/default/loading-2.gif"></div>'.
                            '<div style="float: left;width:180px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                            '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$og['name'].'</div>'.
                            '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$og['ggname'].'</span></div>';
                        $goodshtml.='<div style="padding-top:0px;color:#f60;">￥'.$og['sell_price'].' × '.$og['num'].'</div>';
                        $goodshtml.='</div>';
                        $goodshtml.='</div>';
                        $goodsdata[] = $goodshtml;
                    }
                    $list[$k]['goodsdata'] = implode('',$goodsdata);
                    $list[$k]['ordertype'] = '商家订单';
                }
                if($vo['reward_type'] == 1){
                    $list[$k]['reward'] = (int)$vo['reward'];
                }
                if($vo['reward_type'] == 2){
                    $coupon = Db::name('coupon')->where('id',(int)$vo['reward'])->find();
                    $list[$k]['reward'] = $coupon['name'];
                }
                $list[$k]['business'] = $business??'';
                $list[$k]['createtime'] = date('Y-m-d H:i:s',$list[$k]['createtime']);
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$list]);
        }
        return View::fetch();
    }

    public function del(){
        $ids = input('param.ids');
        if(!$ids){
            return json(['status'=>0,'msg'=>'请选择要删除的项']);
        }
        Db::name('order_collect_reward_record')->where('id','in',$ids)->delete();
        \app\common\System::plog('删除确认收货奖励记录');
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    public function set(){
        if(request()->isAjax()){
            $info = input('post.info/a');
            $date_range = input('post.date_range');
            $order_type = input('post.order_type/a');
            $platform = input('post.platform/a');
            $gettj = input('post.gettj/a');
            if($info['reward_type'] == 1 && !$info['score']){
                return json(['status'=>0,'msg'=>'请输入奖励'.t('积分')]);
            }
            if ($info['reward_type'] == 2 && !$info['coupon_id']){
                return json(['status'=>0,'msg'=>'请选择'.t('优惠券')]);
            }
            if ($info['reward_type'] == 3 && !$info['money']){
                return json(['status'=>0,'msg'=>'请输入奖励'.t('金额')]);
            }
            if(empty($date_range)){
                return json(['status'=>0,'msg'=>'请选择活动时间']);
            }
            if(!$order_type){
                return json(['status'=>0,'msg'=>'请选择订单范围']);
            }
            if(!$platform){
                return json(['status'=>0,'msg'=>'请选择使用平台']);
            }
            if(!$gettj){
                return json(['status'=>0,'msg'=>'请选择参与条件']);
            }
            if($info['prompt'] && mb_strlen($info['prompt']) > 20){
                return json(['status'=>0,'msg'=>'引导话术不能超过20个字符']);
            }

            list($startime,$endtime) = explode(' ~ ',$date_range);
            $info['start_time'] = strtotime($startime);
            $info['end_time'] = strtotime($endtime);
            $info['order_type'] = implode(',',$order_type);
            $info['platform'] = implode(',',$platform);
            $info['gettj'] = implode(',',$gettj);
            if(isset($info['coupon_name'])) unset($info['coupon_name']);
            $set = Db::name('order_collect_reward')->where('aid',aid)->find();
            if($set){
                Db::name('order_collect_reward')->where('aid',aid)->update($info);
                \app\common\System::plog('确认收货奖励设置');
            }else{
                $info['aid'] = aid;
                $info['createtime'] = time();
                Db::name('order_collect_reward')->insert($info);
            }
            return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
        }
        $info = Db::name('order_collect_reward')->where('aid',aid)->find();
        if(!$info){
            $sysset = db('admin_set')->where('aid', aid)->find();
            $info = ['gettj' => [-1] ,'date_range'=>'','fontcolor'=>'#FFFFFF','bgcolor'=>$sysset['color1']];
        }else{
            $info['date_range'] = date('Y-m-d H:i:s',$info['start_time']) . ' ~ ' . date('Y-m-d H:i:s',$info['end_time']);
            $info['order_type'] = $info['order_type'] ? explode(',',$info['order_type']) : [];
            $info['platform'] = $info['platform'] ? explode(',',$info['platform']) : [];
            $info['gettj'] = $info['gettj'] ? explode(',',$info['gettj']) : [];
            if($info['coupon_id']){
                $info['coupon_name'] = Db::name('coupon')->where('aid',aid)->where('id',$info['coupon_id'])->value('name');
            }
        }
        $defaultCat = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
        $defaultCat = $defaultCat ? $defaultCat : 0;
        $memberlevel = Db::name('member_level')->where('aid',aid)->where('cid', $defaultCat)->order('sort,id')->select()->toArray();
        View::assign('memberlevel',$memberlevel);
        View::assign('info',$info);
        return View::fetch();
    }
}
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
// | 购物返现
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Cashback extends Common
{
    public function initialize(){
        parent::initialize();
        if(!getcustom('yx_cashback_business')){
            if(bid > 0) showmsg('无操作权限');
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
                $order = 'sort desc,id desc';
            }
            $where = array();
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
            $count = 0 + Db::name('cashback')->where($where)->count();
            $data = Db::name('cashback')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach($data as $k=>$v){
                $data[$k]['cashback_status'] = $v['status'];
                if($v['starttime'] > time()){
                    $data[$k]['status'] = '<button class="layui-btn layui-btn-sm" style="background-color:#888">未开始</button>';
                }elseif($v['endtime'] < time()){
                    $data[$k]['status'] = '<button class="layui-btn layui-btn-sm layui-btn-disabled">已结束</button>';
                }else{
                    $data[$k]['status'] = '<button class="layui-btn layui-btn-sm" style="background-color:#5FB878">进行中</button>';
                }
                if(getcustom('yx_cashback_stop')){
                    if($v['status'] == 0 || time() > $v['endtime']){
                        $data[$k]['cashback_status'] = 0;
                        $data[$k]['status'] = '<button class="layui-btn layui-btn-sm layui-btn-disabled">已结束</button>';
                    }
                }
                $data[$k]['starttime'] = date('Y-m-d H:i',$v['starttime']);
                $data[$k]['endtime'] = date('Y-m-d H:i',$v['endtime']);
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
		$moneyText = '金额';
		if(getcustom('yx_cashback_addup_return')){
			$moneyText = Db::name('cashback_sysset')->where('aid',aid)->value('money_text') ?: '金额';
		}
		View::assign('money_text',$moneyText);
        View::assign('auth_data',$this->auth_data);

        if( getcustom('yx_cashback_time_ceshi') && getcustom('yx_cashback_time')){
            View::assign('auth_data',$this->auth_data);
        }
        return View::fetch();
    }
    //编辑
    public function edit(){
        if(input('param.id')){
            $info = Db::name('cashback')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
            $info['starttime'] = date('Y-m-d H:i:s',$info['starttime']);
            $info['endtime'] = date('Y-m-d H:i:s',$info['endtime']);
        }else{
            $info = array('id'=>'','starttime'=>date('Y-m-d 00:00:00'),'endtime'=>date('Y-m-d 00:00:00',time()+7*86400),'gettj'=>'-1','sort'=>0,'fwtype'=>0,'type'=>1,'tip'=>'满减');
        }
        $info['gettj'] = explode(',',$info['gettj']);

        View::assign('info',$info);
        $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
        $default_cid = $default_cid ? $default_cid : 0;
        $memberlevel = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();
        View::assign('memberlevel',$memberlevel);

        if(bid == 0){
            $categorydata = array();
            if($info && $info['categoryids']){
                $categorydata = Db::name('shop_category')->where('aid',aid)->where('id','in',$info['categoryids'])->order('sort desc,id')->select()->toArray();
            }
            View::assign('categorydata',$categorydata);
        }
        if(getcustom('yx_cashback_business') && bid > 0){
            $categorydata2 = array();
            if($info && $info['categoryids']){
                $categorydata2 = Db::name('shop_category2')->where('aid',aid)->where('bid',bid)->where('id','in',$info['categoryids'])->order('sort desc,id')->select()->toArray();
            }
            View::assign('categorydata',$categorydata2);
        }
        $productdata = array();
        if($info && $info['productids']){
            $productdata = Db::name('shop_product')->where('aid',aid)->where('id','in',$info['productids'])->order(Db::raw('field(id,'.$info['productids'].')'))->select()->toArray();
        }
        View::assign('productdata',$productdata);
        if(getcustom('plug_tengrui')){
            $groupdata = array();
            if($info && $info['group_ids']){
                $groupdata = Db::name('member_tr_group')->where('aid',aid)->where('id','in',$info['group_ids'])->order('id desc')->select()->toArray();
            }
            View::assign('groupdata',$groupdata);
        }

        if(getcustom('yx_cashback_collage')){
            $collagedata = array();
            if($info && $info['collageids']){
                $collagedata = Db::name('collage_product')->where('aid',aid)->where('id','in',$info['collageids'])->order(Db::raw('field(id,'.$info['collageids'].')'))->select()->toArray();
            }
            View::assign('collagedata',$collagedata);
        }
        $return_type = 0;
        if(getcustom('yx_cashback_time')){
            $return_type = 1;//自定义天数返还
        }
        if(getcustom('yx_cashback_stage')){
            $return_type = 2;//自定义阶梯性返还
        }
        if(getcustom('yx_cashback_multiply')){
            $return_type = 3;//倍增方式返现
        }
        if(getcustom('yx_cashback_addup_return')){
            $return_type = 4;//累加递减方式返现
        }
        View::assign('return_type',$return_type);
        View::assign('auth_data',$this->auth_data);

        // 一级分销加速
        if(getcustom('yx_cashback_time_fenxiao_speed')){
            $fenxiaodata = json_decode($info['fenxiaodata'],true);
            View::assign('fenxiaodata',$fenxiaodata);
        }
        
        // 团队分红式加速
        if(getcustom('yx_cashback_time_teamfenhong_speed')){
            $teamlevellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->where('teamfenhonglv','>','0')->order('sort,id')->select()->toArray();
            View::assign('teamlevellist',$teamlevellist);
            $teamfenhongdata = json_decode($info['teamfenhongdata'],true);
            View::assign('teamfenhongdata',$teamfenhongdata);
        }
        //股东分红式加速
        if(getcustom('yx_cashback_time_gdfenhong_speed')){
            $gdlevellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->where('fenhong','>','0')->order('sort,id')->select()->toArray();
            View::assign('gdlevellist',$gdlevellist);
            $gdfenhongdata= json_decode($info['gdfenhongdata'],true);
            View::assign('gdfenhongdata',$gdfenhongdata);
        }    

        // 下级释放加速
        if(getcustom('yx_cashback_time_addup_speed')){
            $addupdata = json_decode($info['addupdata'],true);
            View::assign('addupdata',$addupdata);
        }
        // 团队式加速
        if(getcustom('yx_cashback_time_team_speed')){
            $teamdata = json_decode($info['teamdata'],true);
            View::assign('teamdata',$teamdata);
        }
        // 股东式加速
        if(getcustom('yx_cashback_time_gudong_speed')){

            $gudongdata = json_decode($info['gudongdata'],true);
            View::assign('gudongdata',$gudongdata);
        }
        
        return View::fetch();
    }

    //保存
    public function save(){
        $info = input('post.info/a');

        if(getcustom('yx_cashback_time') && getcustom('yx_cashback_jiange_day') && $info['return_type'] == 1 && !empty($info['jiange_day'])){
            $is_int = $info['return_day'] % $info['jiange_day'];
            if($is_int != 0){
                return json(['status'=>0,'msg'=>'返还天数必须是间隔天数的整数倍']);
            }
        }

        if(getcustom('yx_cashback_collage')){
            if($info['fwtype'] == 3 && $info['collageids']){
                //查重，确保id唯一
                $collageids     = explode(',',$info['collageids']);
                $collageids_num = count($collageids);
                $new_collageids = array_unique($collageids);
                $new_collageids_num = count($new_collageids);
                if($collageids_num != $new_collageids_num){
                    return json(['status'=>0,'msg'=>'多人拼团存在重复数据，请删除']);
                }
            }
        }
        $info['gettj'] = implode(',',$info['gettj']);
        $info['starttime'] = strtotime($info['starttime']);
        $info['endtime'] = strtotime($info['endtime']);
        //开启限额
        $cashback_max = 0;
        if(getcustom('cashback_max')){
            $cashback_max = 1;
        }
        //开启选择受益人
        $cashback_receiver = 0;
        if(getcustom('cashback_receiver')){
            $cashback_receiver = 1;
        }

        //受益人限额仅限单个商品可用
        if($cashback_receiver || $cashback_max){
            $goods_multiple_max = $info['goods_multiple_max'];
            $receiver_type = $info['receiver_type'];
            $fwtype = $info['fwtype'];
            $productids = explode(',',$info['productids']);
            //开启受益人为参与活动的人或者限制倍数限制指定一个商品
            if($receiver_type ==2){
                if($fwtype !=2 || count($productids) != 1){
                    return json(['status'=>0,'msg'=>'开启受益人和限额仅限单个指定商品']);
                }
                //判定当前活动商品不能同时存在其它开始的活动商品中
                $now_time = time();
                //$where[] = ['starttime','<=',$now_time];
                $where[] = ['endtime','>',$now_time];
                //$where[] = ['productids','=',$info['productids']];
                if($info['id']){
                    $where[] = ['id','<>',$info['id']];
                }
                //$where_or = 'receiver_type = 2 or goods_multiple_max > 0';
//                $product = Db::name('shop_product')
//                    ->where('id',$info['productids'])
//                    ->field('id,cid')
//                    ->find();
                $where_pro = 'FIND_IN_SET("'.$info['productids'].'", productids) or fwtype = 0 or fwtype = 1';
                $goods_data = Db::name('cashback')->where($where)->whereRaw($where_pro)->select()->toArray();
                if($goods_data){
                    return json(['status'=>0,'msg'=>'当前商品已存在其它活动']);
                }
            }
        }

        if(getcustom('yx_cashback_time_teamspeed')){
            $teamspeeddata  = array();
            $postmoney = input('post.money/a');
            $postspeed = input('post.speed/a');
            foreach($postmoney as $k=>$money){
                $teamspeeddata[] = array(
                    'money'=>$money,
                    'speed'=>$postspeed[$k],
                );
            }
            //按金额重新排序
            $newdata = array_column($teamspeeddata,'money');
            array_multisort($newdata ,SORT_ASC,$teamspeeddata);
            $info['teamspeeddata'] = json_encode($teamspeeddata,JSON_UNESCAPED_UNICODE);
        }

        if(getcustom('yx_cashback_stage')){
            if($info['return_type'] == 2){
                $stagedata  = array();
                $stageday   = input('post.stageday/a');
                $stageday2  = input('post.stageday2/a');
                $stageratio = input('post.stageratio/a');
                foreach($stageday as $k=>$day){
                    if($stageday2[$k]<$day){
                        return json(['status'=>0,'msg'=>'阶梯返还，最大天数必须大于等于最小天数']);
                    }
                    $data = [
                        'stageday'=>$day,
                        'stageday2'=>$stageday2[$k],
                        'stageratio'=>$stageratio[$k]
                    ];
                    $stagedata[] = $data;
                }
                //重新排序
                $stagedata2= array_column($stagedata,'stageday');
                array_multisort($stagedata2,SORT_ASC,$stagedata);
                $info['stagedata'] = json_encode($stagedata,JSON_UNESCAPED_UNICODE);
            }
        }
        if(getcustom('yx_cashback_time_fenxiao_speed')){
            $info['fenxiaodata'] = json_encode(input('post.fenxiaodata/a'));
        }
        if(getcustom('yx_cashback_time_teamfenhong_speed')){
            $info['teamfenhongdata'] = json_encode(input('post.teamfenhongdata/a'));
        }
        if(getcustom('yx_cashback_time_gdfenhong_speed')){
            $info['gdfenhongdata'] = json_encode(input('post.gdfenhongdata/a'));
        }
        if(getcustom('yx_cashback_time_addup_speed')){
            $info['addupdata'] = json_encode(input('post.addupdata/a'));
        }
        if(getcustom('yx_cashback_time_team_speed')){
            $info['teamdata'] = json_encode(input('post.teamdata/a'));
        }
        if(getcustom('yx_cashback_time_gudong_speed')){
            $info['gudongdata'] = json_encode(input('post.gudongdata/a'));
        }

        if($info['id']){
            Db::name('cashback')->where('aid',aid)->where('bid',bid)->where('id',$info['id'])->update($info);
            \app\common\System::plog('修改购物返现活动'.$info['id']);
        }else{
            $info['aid'] = aid;
            $info['bid'] = bid;
            $info['createtime'] = time();
            $id = Db::name('cashback')->insertGetId($info);
            \app\common\System::plog('添加'.t('购物返现').'活动'.$id);
        }
        return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
    }

    //删除
    public function del(){
        $ids = input('post.ids/a');
        Db::name('cashback')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
        \app\common\System::plog('删除'.t('购物返现').'活动'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    //参与会员
    public function record(){
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 'cashback_member.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'cashback_member.id desc';
            }
            $where = [];
            $where[] = ['cashback_member.aid','=',aid];
            if(input('param.id/d')) $where[] = ['cashback_member.cashback_id','=',input('param.id/d')];
            if(input('param.mid')) $where[] = ['cashback_member.mid','=',input('param.mid/d')];
            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.input('param.nickname').'%'];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['cashback_member.create_time','>=',strtotime($ctime[0])];
                $where[] = ['cashback_member.create_time','<',strtotime($ctime[1]) + 86400];
            }

            $count = 0 + Db::name('cashback_member')->alias('cashback_member')->join('member member','cashback_member.mid=member.id')->where($where)->count();
            $data = Db::name('cashback_member')->alias('cashback_member')->field('cashback_member.*,member.nickname,member.headimg')->join('member member','cashback_member.mid=member.id')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            if($data){
                //返现类型 1、余额 2、佣金 3、积分 小数位数
                $moeny_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                if(getcustom('member_money_weishu')){
                    $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
                }
                if(getcustom('fenhong_money_weishu')){
                    $commission_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
                }
                if(getcustom('score_weishu')){
                    $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
                }
                foreach($data as &$v){
                    $v['cashback_money'] = dd_money_format($v['cashback_money'],$moeny_weishu);
                    $v['commission']     = dd_money_format($v['commission'],$commission_weishu);
                    $v['score']          = dd_money_format($v['score'],$score_weishu);
                    if(!empty($v['order_mid'])){
                        $order_member = Db::name('member')->where('id',$v['order_mid'])->field('nickname,headimg')->find();
                        $v['order_headimg'] = $order_member['headimg'];
                        $v['order_nickname'] = $order_member['nickname'];
                    }
                }
                unset($v);
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        if(input('param.id/d')){
            $cashback = Db::name('cashback')->where('id',input('param.id/d'))->find();
        }
        View::assign('cashback',$cashback);
        return View::fetch();
    }
        //参与会员记录
        public function recordLog(){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'cashback_member.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'cashback_member.id desc';
                }
                $where = [];
                $where[] = ['cashback_member.aid','=',aid];
                if(input('param.cashback_id/d')) $where[] = ['cashback_member.cashback_id','=',input('param.cashback_id/d')];
                if(input('param.pro_id/d')) $where[] = ['cashback_member.pro_id','=',input('param.pro_id/d')];
                if(input('param.mid')) $where[] = ['cashback_member.mid','=',input('param.mid/d')];
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['cashback_member.create_time','>=',strtotime($ctime[0])];
                    $where[] = ['cashback_member.create_time','<',strtotime($ctime[1]) + 86400];
                }
    
                $count = 0 + Db::name('cashback_member_log')->alias('cashback_member')->join('member member','cashback_member.mid=member.id')->where($where)->count();
                $data = Db::name('cashback_member_log')->alias('cashback_member')->field('cashback_member.*,member.nickname,member.headimg')->join('member member','cashback_member.mid=member.id')->where($where)->page($page,$limit)->order($order)->select()->toArray();
                if($data){
                    //返现类型 1、余额 2、佣金 3、积分 小数位数
                    $moeny_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                    if(getcustom('member_money_weishu')){
                        $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
                    }
                    if(getcustom('fenhong_money_weishu')){
                        $commission_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
                    }
                    if(getcustom('score_weishu')){
                        $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
                    }
                    foreach($data as &$v){
                        $v['cashback_money'] = dd_money_format($v['cashback_money'],$moeny_weishu);
                        $v['commission']     = dd_money_format($v['commission'],$commission_weishu);
                        $v['score']          = dd_money_format($v['score'],$score_weishu);
                        if(!empty($v['order_mid'])){
                            $order_member = Db::name('member')->where('id',$v['order_mid'])->field('nickname,headimg')->find();
                            $v['order_headimg'] = $order_member['headimg'];
                            $v['order_nickname'] = $order_member['nickname'];
                        }
                    }
                    unset($v);
                }
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }

    //倍增返现数据
    public function og_log(){
        if(getcustom('yx_cashback_multiply')){
            if(request()->isAjax()){
                $page = input('param.page');
                $limit = input('param.limit');
                if(input('param.field') && input('param.order')){
                    $order = 'c.'.input('param.field').' '.input('param.order');
                }else{
                    $order = 'c.id desc';
                }
                $where = [];
                $where[] = ['c.aid','=',aid];
                $where[] = ['c.return_type','=',3];
                if(input('param.id/d')) $where[] = ['c.cashback_id','=',input('param.id/d')];
                if(input('param.mid')) $where[] = ['c.mid','=',input('param.mid/d')];
                if(input('param.nickname')) $where[] = ['member.nickname','like','%'.input('param.nickname').'%'];
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['c.createtime','>=',strtotime($ctime[0])];
                    $where[] = ['c.createtime','<',strtotime($ctime[1]) + 86400];
                }
                $status = input('param.status/d');
                if($status==1){
                    $where[] = ['c.status','in',[0,1]];
                }elseif($status==2){
                    $where[] = ['c.status','=',2];
                }
                $count = 0 + Db::name('shop_order_goods_cashback')->alias('c')->join('member member','c.mid=member.id')->where($where)->count();
                $data = Db::name('shop_order_goods_cashback')->alias('c')
                    ->field('c.*,member.nickname,member.headimg')
                    ->join('member member','c.mid=member.id')
                    ->where($where)->page($page,$limit)->order($order)->select()->toArray();
                if($data){
                    //返现类型 1、余额 2、佣金 3、积分 小数位数
                    $moeny_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                    if(getcustom('member_money_weishu')){
                        $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
                    }
                    if(getcustom('fenhong_money_weishu')){
                        $commission_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
                    }
                    if(getcustom('score_weishu')){
                        $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
                    }
                    foreach($data as &$v){
                        if(!empty($v['order_mid'])){
                            $order_member = Db::name('member')->where('id',$v['order_mid'])->field('nickname')->find();
                            $v['order_headimg'] = $order_member['headimg'];
                            $v['order_nickname'] = $order_member['nickname'];
                        }
                        $v['next_circle_yeji'] = bcadd($v['last_circle_yeji'],bcmul($v['last_circle_yeji'],$v['circle_add']/100,4),2);
                    }
                    unset($v);
                }
                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            if(input('param.id/d')){
                $cashback = Db::name('cashback')->where('id',input('param.id/d'))->find();
            }
            View::assign('cashback',$cashback);

            $lirun = \app\custom\OrderCustom::getPlateLirun(aid);
            View::assign('plate_lirun',$lirun['lirun']);
            View::assign('active_coin_total',$lirun['active_coin_total']);
            return View::fetch();
        }
    }
    //返现测试按钮
    public function release(){
        if(getcustom('yx_cashback_multiply')) {
            Db::startTrans();
            \app\custom\OrderCustom::deal_autocashback_multiply(aid);
            Db::commit();
            return json(['status' => 1, 'msg' => '发放成功！']);
        }
    }
    //返现明细
    public function cashback_log(){
        if(getcustom('yx_cashback_multiply')) {
            if (request()->isAjax()) {
                $page = input('param.page');
                $limit = input('param.limit');
                if (input('param.field') && input('param.order')) {
                    $order = 'c.' . input('param.field') . ' ' . input('param.order');
                } else {
                    $order = 'c.id desc';
                }
                $where = [];
                $where[] = ['c.aid', '=', aid];
                if (input('cashback_id')) {
                    $where[] = ['c.cashback_id', '=', input('param.cashback_id')];
                }
                if (input('og_id')) {
                    $where[] = ['c.og_id', '=', input('param.og_id')];
                }
                if (input('param.mid')) $where[] = ['c.mid', '=', input('param.mid/d')];
                if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . input('param.nickname') . '%'];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['c.create_time', '>=', strtotime($ctime[0])];
                    $where[] = ['c.create_time', '<', strtotime($ctime[1]) + 86400];
                }

                $count = 0 + Db::name('cashback_log')->alias('c')->join('member member', 'c.mid=member.id')->where($where)->count();
                $data = Db::name('cashback_log')->alias('c')
                    ->field('c.*,member.nickname,member.headimg')
                    ->join('member member', 'c.mid=member.id')
                    ->where($where)->page($page, $limit)->order($order)->select()->toArray();
                if ($data) {
                    foreach ($data as &$v) {

                    }
                    unset($v);
                }
                return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data]);
            }
            return View::fetch();
        }
    }
    //叠加递减待返金额变动记录
    public function adduplog(){
        if(getcustom('yx_cashback_addup_return')){
            if (request()->isAjax()) {
                $page = input('param.page');
                $limit = input('param.limit');
                if (input('param.field') && input('param.order')) {
                    $order = 'c.' . input('param.field') . ' ' . input('param.order');
                } else {
                    $order = 'c.id desc';
                }
                $where = [];
                $where[] = ['c.aid', '=', aid];
                if (input('cashback_id')) {
                    $where[] = ['c.cashback_id', '=', input('param.cashback_id')];
                }
                if (input('og_id')) {
                    $where[] = ['c.og_id', '=', input('param.og_id')];
                }
                if (input('param.mid')) $where[] = ['c.mid', '=', input('param.mid/d')];
                if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . input('param.nickname') . '%'];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['c.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['c.createtime', '<', strtotime($ctime[1]) + 86400];
                }

                $count = 0 + Db::name('cashback_addup_log')->alias('c')->join('member member', 'c.mid=member.id')->where($where)->count();
                $data = Db::name('cashback_addup_log')->alias('c')
                    ->field('c.*,member.nickname,member.headimg')
                    ->join('member member', 'c.mid=member.id')
                    ->where($where)->page($page, $limit)->order($order)->select()->toArray();
                if ($data) {
                    foreach ($data as $key=>$v) {
                        $cashback_name = '';
                        if($v['cashback_id'] > 0){
                            $cashback_name =  Db::name('cashback')->where('aid',aid)->where('id',$v['cashback_id'])->value('name');
                        }
                        $data[$key]['cashback_name'] = $cashback_name;
                    }
                }
                return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data]);
            }
			$moneyText = Db::name('cashback_sysset')->where('aid',aid)->value('money_text') ?: '金额';
			View::assign('money_text',$moneyText);
            return View::fetch();
        }
    }
    //叠加递减记录
    public function adduprecord(){
        if(getcustom('yx_cashback_addup_return')){
            if (request()->isAjax()) {
                $page = input('param.page');
                $limit = input('param.limit');
                if (input('param.field') && input('param.order')) {
                    $order = 'c.' . input('param.field') . ' ' . input('param.order');
                } else {
                    $order = 'c.id desc';
                }
                $where = [];
                $where[] = ['c.aid', '=', aid];
                if (input('og_id')) {
                    $where[] = ['c.og_id', '=', input('param.og_id')];
                }
                if (input('param.mid')) $where[] = ['c.mid', '=', input('param.mid/d')];
                if (input('param.nickname')) $where[] = ['member.nickname', 'like', '%' . input('param.nickname') . '%'];
                if (input('param.ctime')) {
                    $ctime = explode(' ~ ', input('param.ctime'));
                    $where[] = ['c.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['c.createtime', '<', strtotime($ctime[1]) + 86400];
                }
                if(input('status') !=''){
                    $where[] = ['c.status', '=', input('param.status/d')];
                }
                $count = 0 + Db::name('cashback_addup_record')->alias('c')->join('member member', 'c.mid=member.id')->where($where)->count();
                $data = Db::name('cashback_addup_record')->alias('c')
                    ->field('c.*,member.nickname,member.headimg')
                    ->join('member member', 'c.mid=member.id')
                    ->where($where)->page($page, $limit)->order($order)->select()->toArray();
                return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data]);
            }
			$moneyText = Db::name('cashback_sysset')->where('aid',aid)->value('money_text') ?: '金额';
			View::assign('money_text',$moneyText);
            return View::fetch();
        }
    }
    // 测试执行每日释放
    public function shifang(){
        if( getcustom('yx_cashback_time_ceshi') && getcustom('yx_cashback_time')){
            $time = time();
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['starttime','<',$time];
            $where[] = ['endtime','>',$time];
            if(getcustom('yx_cashback_time_speed')){
                $where[] = ['time_speed','=',1];
            }

            $lists = Db::name('cashback')
                ->where($where)
                ->order('sort desc')
                ->select()->toArray();
            foreach ($lists as $key => $v) {
                //余额返现
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['cashback_id','=',$v['id']];
                $where[] = ['back_type','=',$v['back_type']];;
                $where[] = ['moneystatus|commissionstatus|scorestatus','=',1];
                $moneylist = Db::name('shop_order_goods_cashback')
                    ->where($where)
                    ->order('id asc')
                    ->select()
                    ->toArray();
                if($moneylist){
                    foreach($moneylist as $mv){
                        //走统一的处理方法
                        \app\custom\OrderCustom::shifang($mv);
                    }
                }
            }
        }
         return json(['code' => 0, 'msg' => '操作成功']);
    }

    public function cashback_locklog(){
        if(getcustom('yx_cashback_decmoney_lock')){
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

                if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
                if(input('param.mid')) $where[] = ['l.mid','=',trim(input('param.mid'))];
                if(input('param.tel')) $where[] = ['member.tel','like','%'.trim(input('param.tel')).'%'];
                if(input('?param.status') && input('param.status')!=='') $where[] = ['l.status','=',input('param.status')];
                if(input('param.ctime') ){
                    $ctime = explode(' ~ ',input('param.ctime'));
                    $where[] = ['l.createtime','>=',strtotime($ctime[0])];
                    $where[] = ['l.createtime','<',strtotime($ctime[1])];
                }
                $field='member.nickname,member.headimg,l.*';
                if(input('remark')){
                    $where[] = ['l.remark','like','%'.trim(input('param.remark')).'%'];
                }
//                if(input('param.nickname') || input('param.tel')){
                    $count = 0 + Db::name('member_cashback_locklog')->alias('l')->join('member member','member.id=l.mid')->where($where)->count('l.id');
//                }else{
//                    //大数据查询时连表有点慢
//                    $count = 0 + Db::name('member_cashback_locklog')->alias('l')->where($where)->count('l.id');
//                }

                $data = Db::name('member_cashback_locklog')
                    ->alias('l')
                    ->field($field)
                    ->join('member member','member.id=l.mid')
                    ->where($where)->page($page,$limit)->order($order)->select()->toArray();
                if($data){
                    $money_weishu = Db::name('admin_set')->where('aid',aid)->value('member_money_weishu');
                    foreach($data as &$v){
                        $v['money'] = dd_money_format($v['money'],$money_weishu);
                        $v['after'] = dd_money_format($v['after'],$money_weishu);

                        $v['un'] = '';
                        if($v['uid']){
                            $un = Db::name('admin_user')->where('id',$v['uid'])->where('aid',aid)->value('un');
                            $v['un'] = $un??'已失效';
                        }
                    }
                    unset($v);
                }

                return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
            }
            return View::fetch();
        }

    }
    public function moneylogdel(){
        if(getcustom('yx_cashback_decmoney_lock')) {
            $ids = input('post.ids/a');
            Db::name('member_cashback_locklog')->where('aid', aid)->where('id', 'in', $ids)->delete();
            \app\common\System::plog('删除冻结余额明细' . implode(',', $ids));
            return json(['status' => 1, 'msg' => '删除成功']);
        }
    }

    public function stopCashback(){
        if(getcustom('yx_cashback_stop')){
            $id = input('post.id');
            $cashback = Db::name('cashback')->where('id',$id)->where('aid',aid)->find();
            if(empty($cashback)) return json(['code'=>1,'msg'=>'返现活动不存在']);
            if($cashback['status'] == 0){
                return json(['code'=>1,'msg'=>'返现已停止']);
            }
            Db::name('cashback')->where('id',$id)->where('aid',aid)->update(['status'=>0]);
            //返现状态 0：未确认 1：返回中 2：返回完成 3：停止返现
            // 余额
            Db::name('shop_order_goods_cashback')->where('cashback_id', $id)->where('aid', aid)->where('moneystatus', '<>', 2)->update(['moneystatus' => 3]);
            // 佣金
            Db::name('shop_order_goods_cashback')->where('cashback_id', $id)->where('aid', aid)->where('commissionstatus', '<>', 2)->update(['commissionstatus' => 3]);
            // 积分
            Db::name('shop_order_goods_cashback')->where('cashback_id', $id)->where('aid', aid)->where('scorestatus', '<>', 2)->update(['scorestatus' => 3]);
            \app\common\System::plog(t('购物返现').'停止返现'.$id);
            return json(['code'=>0,'msg'=>'操作成功']);
        }
    }

    public function excel_to_newscore(){
        if(getcustom('yx_new_score_active')){
            //倍增返现记录导出适用新积分的excel表格
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = 'c.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'c.id desc';
            }
            $where = [];
            $where[] = ['c.aid','=',aid];
            $where[] = ['c.return_type','=',3];
            if(input('param.id/d')) $where[] = ['c.cashback_id','=',input('param.id/d')];
            if(input('param.mid')) $where[] = ['c.mid','=',input('param.mid/d')];
            if(input('param.nickname')) $where[] = ['member.nickname','like','%'.input('param.nickname').'%'];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['c.createtime','>=',strtotime($ctime[0])];
                $where[] = ['c.createtime','<',strtotime($ctime[1]) + 86400];
            }
            $status = input('param.status/d');
            if($status==1){
                $where[] = ['c.status','in',[0,1]];
            }elseif($status==2){
                $where[] = ['c.status','=',2];
            }
            $count = 0 + Db::name('shop_order_goods_cashback')->alias('c')->join('member member','c.mid=member.id')->where($where)->count();
            $data = Db::name('shop_order_goods_cashback')->alias('c')
                ->field('c.*,member.nickname,member.headimg')
                ->join('member member','c.mid=member.id')
                ->where($where)->page($page,$limit)->order($order)->select()->toArray();
            $new_data = [];
            if($data){
                //返现类型 1、余额 2、佣金 3、积分 小数位数
                foreach($data as &$v){
                    $back_ratio = Db::name('cashback')->where('id',$v['cashback_id'])->value('back_ratio');
                    if(!empty($v['order_mid'])){
                        $order_member = Db::name('member')->where('id',$v['order_mid'])->field('nickname')->find();
                        $v['order_headimg'] = $order_member['headimg'];
                        $v['order_nickname'] = $order_member['nickname'];
                    }
                    $v['next_circle_yeji'] = bcadd($v['last_circle_yeji'],bcmul($v['last_circle_yeji'],$v['circle_add']/100,4),2);
                    $new_data[] = [
                        $v['id'],
                        $v['pro_id'],
                        $v['mid'],
                        $v['back_price'],
                        $v['totalprice'],
                        $v['first_circle_yeji'],
                        $v['first_circle'],
                        $v['circle_add'],
                        $v['last_circle_yeji'],
                        $v['next_circle_yeji'],
                        $v['circle_max'],
                        $v['send_circle'],
                        $v['send_all'],
                        $v['back_type'],
                        $v['status']==2?'已完成':'进行中',
                        $back_ratio,
                        0,
                        $v['last_circle_send'],
                    ];
                }
                unset($v);
            }
            $title = ['数据ID','商品ID','用户ID','返现金额','消费金额','首期业绩','首期返现比例','业绩增长率','上期业绩','下期业绩','最高返现周期','已返周期','已返金额','返现钱包','状态','返现比例','活动ID','上期释放'];
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$new_data,'title'=>$title]);
        }
    }
}
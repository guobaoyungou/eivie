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

//custom_file(wx_channels)
//视频号小店 预约直播列表
namespace app\controller;

use think\facade\Db;
use think\facade\View;

class WxChannelsLive extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
    }


    /**
     * 直播列表
     * live_status 直播状态 0：直播预告 1：超时未开播 2：取消直播预告 3：已开播 4：直播结束
     */
    public function liveList(){
        $id = input('param.id/d'); //视频号小店ID
        if(request()->isAjax()){
            $live_title = input('param.live_title');
            $page = input('param.page');
            $limit = input('param.limit');
            $where[] = [ 'aid','=', aid];
            $where[] = [ 'bid','=', bid];
            $where[] = [ 'shop_id','=', $id];
            if($live_title){
                $where[] = ['content','like','%'.$live_title.'%'];
            }

            $count = 0 + Db::name('channels_reservation_live')->where($where)->count();
            $data  = Db::name('channels_reservation_live')->order('live_start_time desc')->where($where)->page($page,$limit)->select()->toArray();
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        View::assign('id',$id);
        View::assign('bid',bid);
        return View::fetch();
    }

    /**
     * 编辑
     */
    public function edit(){
        $id = input('param.id/d');
        $shop_id = input('param.shop_id/d'); //视频号小店IDs
        $info = [];
        if($id){
            $where = [
                'id' => $id,
                'shop_id' => $shop_id,
                'aid' => aid,
                'bid' => bid
            ];
            $info = Db::name('channels_reservation_live')->where($where)->find();
            if(!$info) showmsg('直播预告不存在');
        }
        $info['live_start_time'] = $info['live_start_time'] ? date('Y-m-d H:i:s',$info['live_start_time']) : '';
        View::assign('shop_id',$shop_id);
        View::assign('info',$info);
        return View::fetch();
    }

    /**
     * 保存
     */
    public function save(){
        if(request()->isPost()){
            $info = input('post.info/a');
            $id = input('param.id/d');
            $shop_id = input('param.shop_id/d');
            $info['live_start_time'] = strtotime($info['live_start_time']);
            if($id){
                //获取前后一个小时
                Db::name('channels_reservation_live')->where(['id' => $id])->update($info);
            }else{
                $last_live = Db::name('channels_reservation_live')->where('aid',aid)->where('bid',bid)->order('live_start_time desc')->find();
                if($last_live){
                    //判断两场预告开播时间是否间隔一个小时
                    $live_start_time = $info['live_start_time'];
                    $diff = $live_start_time - $last_live['live_start_time'];
                    if($diff > 0 && $diff < 3600) {
                        return json(['status' => 0, 'msg' => '创建预告失败，两场预告开播时间最少间隔1小时']);
                    }
                }
                $info['shop_id'] = $shop_id;
                $info['aid'] = aid;
                $info['bid'] = bid;
                Db::name('channels_reservation_live')->insert($info);
            }
            return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('liveList').'&id='.$shop_id]);
        }
    }

    /**
     * 删除
     */
    public function del(){
        $ids = input('post.ids/a');
        if(!$ids) $ids = array(input('post.id/d'));
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['id','in',$ids];
        $list = Db::name('channels_reservation_live')->where($where)->select();
        foreach($list as $index){
            Db::name('channels_reservation_live')->where('id',$index['id'])->delete();
        }

        \app\common\System::plog('直播预告删除'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    /**
     * 预约人员列表
     */
    public function recordMember(){
        $id = input('param.id/d');
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            $searchID = input('param.search_id');
            $where = [
                'aid' => aid,
                'bid' => bid,
                'lid' => $id
            ];

            if($searchID != ''){
                $where['id'] = $searchID;
            }

            $count = 0 + Db::name('channels_reservation_record')->where($where)->count();
            $data  = Db::name('channels_reservation_record')->where($where)->page($page,$limit)->select()->toArray();
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }
        return View::fetch();
    }
}
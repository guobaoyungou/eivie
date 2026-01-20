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
//视频号小店 预约直播
namespace app\controller;

use think\facade\Db;

class ApiWxChannelsLive extends ApiCommon
{

    public $bid = 0;

    public function initialize(){
        parent::initialize();
        if(input('?param.bid')){
            $this->bid = input('param.bid/d');
        }
        if(!getcustom('wx_channels_business') && $this->bid){
            echojson(['status'=>0,'msg'=>'暂无多商家权限']);
        }
    }
    /**
     * live_status 直播状态 0：直播预告 1：超时未开播 2：取消直播预告 3：已开播 4：直播结束
     */
    public function index(){
        $id = input('param.id');
        $channels = Db::name('admin_setapp_channels')->field('id,nickname,headimg,sph_id')->where('id',$id)->where('aid',aid)->where('bid',$this->bid)->find();
        if(!$channels){
            return $this->json(['status' => 0, 'msg' => '视频小店不存在']);
        }
        if(!mid){
            return $this->json(['status' => -1, 'msg' => '请先登录']);
        }
        //查询配置项
        $setData = Db::name('channels_reservation_live_set')->field('id,bgcolor,bgpic,fontcolor,desc,guize,sharedesc,sharelink,sharepic,sharetitle')->where('aid',aid)->where('bid',$this->bid)->find();

        $todayStartTime = strtotime(date('Y-m-d 00:00:00', time()));
        //预约直播列表
        $liveList = Db::name('channels_reservation_live')
            ->alias('l')
            ->where('l.aid', aid)
            ->where('l.bid', $this->bid)
            ->where('l.shop_id', $id)
            ->where('l.live_status', 'in', [0,3])
            ->where('l.live_start_time', '>=', $todayStartTime)
            ->order('l.live_start_time asc')
            ->field('l.*, r.status as reserve_status')
            ->join('channels_reservation_record r', 'l.id = r.lid AND r.status = 1 AND r.mid = ' . mid, 'LEFT') // 左关联查询
            ->select()
            ->toArray();

        return $this->json([ 'status' => 1 ,'msg' => '获取成功','channels' => $channels ,'setData' => $setData, 'liveList' => $liveList]);
    }

    /**
     * 预约直播
     * state = 6，用户此前未预约，在弹窗中预约了直播
     * state = 7，用户此前已预约，在弹窗中取消了预约
     */
    public function reserveChannelsLive(){
        $id = input('param.id');
        $state = input('param.state');

        //查询预约直播
        $live = Db::name('channels_reservation_live')->where('id',$id)->where('aid',aid)->where('bid',$this->bid)->find();
        if(!$live){
            return $this->json(['status' => 0, 'msg' => '直播不存在']);
        }

        //查询设置
        $set =  Db::name('channels_reservation_live_set')->where('aid',aid)->where('bid',$this->bid)->find();

        //验证是否预约过
        $where = ['aid' => aid,'bid' => $this->bid,'mid' => mid, 'lid' => $id];
        $record = Db::name('channels_reservation_record')->where($where)->find();
        //取消预约
        if($state == 7){
            if($record){
                Db::name('channels_reservation_live')->where('id',$id)->where('aid',aid)->where('bid',$this->bid)->dec('reserve_num')->update();
                $res = Db::name('channels_reservation_record')->where($where)->update(['status' => 2]);
                if($res) {
                    return $this->json(['status' => 1, 'msg' => '取消成功']);
                }
                return $this->json(['status' => 0, 'msg' => '取消失败']);
            }
            return $this->json(['status' => 0, 'msg' => '您未预约过该直播']);
        }

        if($record){
            // 1 已预约 2 已取消 0 未预约
            if($record['status'] == 1){
                return $this->json(['status' => 0, 'msg' => '您已预约过该直播']);
            }elseif ($record['status'] == 2){
                Db::name('channels_reservation_live')->where('id',$id)->where('aid',aid)->where('bid',$this->bid)->inc('reserve_num')->update();
                $res = Db::name('channels_reservation_record')->where($where)->update(['status' => 1]);
                if($res) {
                    return $this->json(['status' => 1, 'msg' => '预约成功']);
                }
                return $this->json(['status' => 0, 'msg' => '预约失败']);
            }
        }

        //查询用户信息
        $member = Db::name('member')->field('id,aid,bid,nickname,headimg')->where('aid',aid)->where('id',mid)->find();
        if(!$member){
            return $this->json(['status' => 0, 'msg' => '用户不存在,请重新登录']);
        }
        $data = [
            'aid' => aid,
            'bid' => $this->bid,
            'mid' => $member['id'],
            'lid' => $id,
            'nickname' => $member['nickname'],
            'headimg' => $member['headimg'],
            'create_time' => time(),
            'status' => 1, // 1 已预约 2 已取消 0 未预约
        ];
        //增加直播预约数
        Db::name('channels_reservation_live')->where('id',$id)->where('aid',aid)->where('bid',$this->bid)->inc('reserve_num')->update();
        $res = Db::name('channels_reservation_record')->insert($data);
        if($res) {
            //赠送优惠券
            if($set['give_coupon'] == 1 && $set['coupon_list']) {
                $coupon_list = json_decode($set['coupon_list'], true);
                if (empty($coupon_list)) {
                    return 0;
                }
                foreach ($coupon_list as $key => $val){
                    \app\common\Coupon::send(aid,$member['id'],$val['coupon_id']);
                }
            }

            //赠送积分
            $this->extGiveScore($set,aid,$member['id'],$id);
            return $this->json(['status' => 1, 'msg' => '预约成功']);
        }
        return $this->json(['status' => 0, 'msg' => '预约失败']);
    }

    /**
     * 赠送积分
     */
    private function extGiveScore($set,$aid,$mid,$liveId){
        if(!$set || $this->bid){
            return false;
        }

        $giveScore = $set['reservation_give_score']; //赠送积分
        $maxScore = $set['day_give_score']; //每日最多赠送积分

        //give_score 1:开启 0：关闭
        if($set['give_score'] == 1 && $giveScore > 0 && $maxScore > 0){

            $todayStart = strtotime(date('Y-m-d 00:00:00',time()));
            $todayEnd = $todayStart + 86400;
            $midWhere = [];
            $midWhere[] = ['aid','=',$aid];
            $midWhere[] = ['mid','=',$mid];
            $midWhere[] = ['from_table','=','channels_reservation_live'];
            $midWhere[] = ['createtime','between',[$todayStart,$todayEnd]];
            $giveScoreCount = Db::name('ext_givescore_record')->where($midWhere)->sum('score');


            //已达上限 不赠送
            if($giveScoreCount >= $maxScore){
                return false;
            }

            $midDayRemainScore = $maxScore - $giveScoreCount;
            $giveScore = min($giveScore,$midDayRemainScore);

            $remark = "预约直播赠送(id={$liveId})赠送" . $giveScore. t('积分');
            $res =\app\common\Member::addscore(aid,$mid,$giveScore,$remark);
            if($res && $res['status']==1){
                //赠送记录
                $record = [
                    'aid' => $aid,
                    'mid' => $mid,
                    'from_table' => 'channels_reservation_live',
                    'from_id' => $liveId,
                    'score' => $giveScore,
                    'createtime' => time(),
                    'type' => 'reservation'
                ];
                Db::name('ext_givescore_record')->insert($record);
            }
            return true;
        }
        return false;
    }

    /**
     * 匹配数据
     */
    public function matchingData(){
        $id = input('param.id');
        $data = input('param.data');//预约直播数据
        $live_status = input('param.live_status'); //-1:默认值 2:直播中 3：直播结束
        $cancel_data = input('param.cancel_id/a'); //取消预约的ID
        if(empty($id)){
            return $this->json(['status' => 0, 'msg' => 'ID为空']);
        }
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',$this->bid];
        $where[] = ['shop_id','=', $id];
        
        if($live_status > -1){
            Db::name('channels_reservation_live')
                ->where($where)
                ->where('live_start_time','<=',time())
                ->where('live_status',0) //预告
                ->update(['live_status' => 1]); //超时未开播
        }

        //取消预约
        if($cancel_data){
            Db::name('channels_reservation_live')->where('id','in',$cancel_data)->where('aid',aid)->where('bid',$this->bid)->update(['live_status' => 2]);
        }
        //关闭所有开播的直播
        if($live_status == 3){
            Db::name('channels_reservation_live')->where($where)->where('live_status',2)->update(['live_status' => 3]);
        }

        $thisTime = time();
        foreach ($data as $key => $val){
            if($val['matching'] == 1 && $val['live_status'] == 0){
                //判断是否超过开播时间，超过直播时间认为取消预约直播
                if ($thisTime >= $val['startTime']) {
                    Db::name('channels_reservation_live')->where('id', $val['id'])->update(['live_status' => 2]);
                }
            }

            if($live_status == 2 || ($val['noticeId'] !='' && $val['matching'] == 0)){
                Db::name('channels_reservation_live')->where('shop_id',$id)->where('id',$val['id'])->update([
                    'matching' => 1,
                    'noticeId' => $val['noticeId'],
                    'reservable' => $val['reservable'],
                    'startTime' => $val['startTime'],
                    'status' => $val['status'],
                    'live_status' => $val['live_status']
                ]);
            }
        }

        return $this->json(['status' => 1, 'msg' => '']);
    }
}
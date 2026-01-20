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

namespace app\custom;
use think\facade\Db;
class MemberCustom
{
    //检测脱离会员佣金情况，当脱离人员N天内的佣金收益未达到Y元，那么现下级随机N人自动划拨给脱离人员的现上级
    public function help_check($aid,$set=[]){
        }


    /**
     * 增加现金账户余额
     */
    public static function addXianjin($aid,$mid,$money,$remark,$paytype=''){
        }

    //检测冻结资金有效期，过期的扣除
    public function check_freeze_money($aid,$set=[]){
        if(getcustom('freeze_money',$aid)){
            //扣除冻结资金时按记录扣除
            $logs = Db::name('member_freezemoneylog')
                ->where('aid',$aid)
                ->where('remain','>',0)
                ->where('end_time','>',0)
                ->where('end_time','<',time())
                ->order('id asc')
                ->select()->toArray();
            foreach($logs as $log){
                Db::name('member_freezemoneylog')->where('id',$log['id'])->update(['remain'=>0]);
                \app\common\Member::addFreezeMoney($aid,$log['mid'],-$log['freezemoney'],'到期扣除');
            }
        }
    }
    //没有待返现余额时冻结余额
    public function cashback_lock($aid,$mid,$money,$remark){
        if(getcustom('yx_cashback_decmoney_lock',$aid)) {
            //查询剩余待返现额度
            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['mid', '=', $mid];
            $where[] = ['status', '<>', 2];
            $where[] = ['moneystatus', '<>', 2];
            $where[] = ['commissionstatus', '<>', 2];
            $where[] = ['scorestatus', '<>', 2];
            $cashback = Db::name('shop_order_goods_cashback')
                ->where($where)
                ->order('id asc')
                ->field('sum(back_price) back_price_total,sum(send_all) send_all_total')
                ->find();
            $remain = 0;
            if($cashback){
                $remain = bcsub($cashback['back_price_total'] , $cashback['send_all_total'],2);
            }
            if($money<=$remain){
                return ['status'=>1,'msg'=>'','money' =>$money ];
            }
            //当前余额大于待返现数据时，剩下的余额冻结
            $cashback_lock = 0;
            if($money>$remain){
                $cashback_lock = bcsub($money,$remain,2);
            }
            self::add_cashback_lock($aid,$mid,$cashback_lock,$remark);
            return ['status'=>1,'msg'=>'','money' =>$remain ];

        }
    }

    //加冻结余额
    public static function add_cashback_lock($aid,$mid,$money,$remark){
        if(getcustom('yx_cashback_decmoney_lock',$aid)){
            if($money==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $money_weishu = 2;

            $money = dd_money_format($money,$money_weishu);
            $after = $member['cashback_lock'] + $money;
            //减后 余额为负数的情况
            if($after < 0 && $money < 0){
                return ['status'=>0,'msg'=>t('会员').'余额不足'];
            }
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['cashback_lock'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['uid'] = defined('uid') && !empty(uid)?uid:0;//记录操作员ID 2024.9.13增加
            $log_id = Db::name('member_cashback_locklog')->insertGetId($data);

            return ['status'=>1,'msg'=>''];
        }
    }

    //获取推荐店铺数量
    public static function gettjbusinessCount($aid,$mid,$is_team=0,$is_id=0){
        }
}
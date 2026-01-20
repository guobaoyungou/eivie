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


namespace app\controller;

use think\facade\Db;

class ApiScore extends ApiCommon
{
    public function initialize()
    {
        parent::initialize();
        $this->checklogin();
    }

    //商户个人互转积分
    public function businessMemberTransfer()
    {
        if (getcustom('score_business_to_member')) {
            $member = Db::name('member')->where('aid', aid)->where('id', $this->mid)->field('id,nickname name,score,id mid')->find();
            if (request()->isPost()) {
                $type = input('param.type/d', 0);
                $bid = input('param.bid/d', 0);
                if (empty($bid)) {
                    return $this->json(['status' => 0, 'msg' => '请选择转账商户']);
                }
                $business = Db::name('business')->where('aid',aid)->where('id',$bid)->where('mid',$this->mid)->field('id,name,score,mid')->find();
                if(empty($business)){
                    return $this->json(['status' => 0, 'msg' => '商户信息有误']);
                }
                $score = intval(input('param.score/d'));
                if ($score <= 0) {
                    return $this->json(['status' => 0, 'msg' => '转账数量必须大于0']);
                }
                $remark = input('param.remark', '');
                if ($type == 0) {
                    if ($business['score'] < $score) {
                        return $this->json(['status' => 0, 'msg' => '商户积分不足']);
                    }
                    //商户减，会员加
                    $remark = '商户转到个人' . ($remark ? '：' . $remark : '');
                    \app\common\Member::addscore(aid, $member['id'], $score, $remark, 'business', $business['id'], $member['id']);
                    \app\common\Business::addscore(aid, $business['id'], 0 - $score, $remark);
                } else {
                    if ($member['score'] < $score) {
                        return $this->json(['status' => 0, 'msg' => '会员积分不足']);
                    }
                    //商户加，会员减
                    $remark = '个人转到商户' . ($remark ? '：' . $remark : '');
                    \app\common\Member::addscore(aid, $member['id'], 0 - $score, $remark);
                    \app\common\Business::addscore(aid, $business['id'], $score, $remark);
                }
                return $this->json(['status' => 1, 'msg' => '操作成功']);
            }
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['member'] = $member;
            $rdata['paycheck'] = false;//$set['money_transfer_pwd'] ? true : false;
            $rdata['businesslist'] = Db::name('business')->where('aid',aid)->where('mid',$this->mid)->where('status','in',[-1,1])->field('id,name,score')->select()->toArray();
            return $this->json($rdata);
        }
    }

}
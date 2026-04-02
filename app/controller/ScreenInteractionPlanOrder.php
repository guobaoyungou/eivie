<?php
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ScreenInteractionPlanOrder extends Common
{
    /**
     * 套餐订单列表页
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $keyword = input('keyword', '');
            $payStatus = input('pay_status', '');
            $orderType = input('order_type', '');

            $where = [];
            $where[] = ['o.aid', '=', aid];
            if ($keyword) {
                $where[] = ['o.order_no|o.plan_name|b.name', 'like', '%' . $keyword . '%'];
            }
            if ($payStatus !== '') {
                $where[] = ['o.pay_status', '=', intval($payStatus)];
            }
            if ($orderType !== '') {
                $where[] = ['o.order_type', '=', intval($orderType)];
            }

            $count = Db::name('hd_plan_order')->alias('o')
                ->leftJoin('business b', 'o.bid = b.id')
                ->where($where)->count();

            $list = Db::name('hd_plan_order')->alias('o')
                ->leftJoin('business b', 'o.bid = b.id')
                ->field('o.*, b.name as business_name')
                ->where($where)
                ->order('o.id desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['pay_time_text']    = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '-';
                $item['start_time_text']  = $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '-';
                $item['end_time_text']    = $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '-';
                $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i:s', $item['createtime']) : '-';
                $item['pay_status_text']  = $this->getPayStatusText($item['pay_status']);
                $item['order_type_text']  = $this->getOrderTypeText($item['order_type'] ?? 1);
                // 是否已过期
                $item['is_expired'] = $item['end_time'] && $item['end_time'] < time() ? 1 : 0;
            }

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    /**
     * 订单详情弹窗
     */
    public function detail()
    {
        $id = input('id', 0);
        if (!$id) {
            return '参数错误';
        }

        $info = Db::name('hd_plan_order')->alias('o')
            ->leftJoin('business b', 'o.bid = b.id')
            ->leftJoin('hd_plan p', 'o.plan_id = p.id')
            ->field('o.*, b.name as business_name, b.tel as business_tel, b.lianxiren as business_contact, p.code as plan_code, p.period, p.duration_days')
            ->where('o.id', $id)
            ->where('o.aid', aid)
            ->find();

        if (!$info) {
            return '订单不存在';
        }

        $info['pay_time_text']    = $info['pay_time'] ? date('Y-m-d H:i:s', $info['pay_time']) : '-';
        $info['start_time_text']  = $info['start_time'] ? date('Y-m-d H:i:s', $info['start_time']) : '-';
        $info['end_time_text']    = $info['end_time'] ? date('Y-m-d H:i:s', $info['end_time']) : '-';
        $info['create_time_text'] = $info['createtime'] ? date('Y-m-d H:i:s', $info['createtime']) : '-';
        $info['pay_status_text']  = $this->getPayStatusText($info['pay_status']);
        $info['order_type_text']  = $this->getOrderTypeText($info['order_type'] ?? 1);
        $info['is_expired']       = $info['end_time'] && $info['end_time'] < time() ? 1 : 0;
        $info['period_text']      = $this->getPeriodText($info['period'] ?? '');
        // 优惠金额
        $info['discount_amount']  = number_format($info['original_price'] - $info['amount'], 2);

        View::assign('info', $info);
        return View::fetch();
    }

    /**
     * 手动录入订单
     */
    public function add()
    {
        if ($this->request->isPost()) {
            return $this->saveOrder();
        }

        // 获取套餐列表（平台级套餐 aid=0）
        $plans = Db::name('hd_plan')->where('aid', '=', 0)->where('status', 1)
            ->order('sort desc, id asc')->field('id, name, code, price')->select()->toArray();

        // 获取商户列表
        $businessList = Db::name('business')->where('aid', aid)->field('id, name')->select()->toArray();

        View::assign('plans', $plans);
        View::assign('businessList', $businessList);
        return View::fetch();
    }

    /**
     * 保存手动录入订单
     */
    private function saveOrder()
    {
        $planId = intval(input('post.plan_id', 0));
        $bid = intval(input('post.bid', 0));
        $orderType = intval(input('post.order_type', 1));
        $originalPrice = floatval(input('post.original_price', 0));
        $amount = floatval(input('post.amount', 0));
        $payStatus = intval(input('post.pay_status', 1));
        $remark = input('post.remark', '');

        if (!$planId) {
            return json(['status' => 0, 'msg' => '请选择套餐']);
        }

        // 查询套餐信息（平台级套餐 aid=0）
        $plan = Db::name('hd_plan')->where('id', $planId)->where('aid', '=', 0)->find();
        if (!$plan) {
            return json(['status' => 0, 'msg' => '套餐不存在']);
        }

        $now = time();
        $data = [
            'aid'            => aid,
            'bid'            => $bid,
            'plan_id'        => $planId,
            'plan_name'      => $plan['name'],
            'order_no'       => 'HD' . date('YmdHis') . str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'amount'         => $amount,
            'original_price' => $originalPrice > 0 ? $originalPrice : $plan['price'],
            'pay_status'     => $payStatus,
            'order_type'     => $orderType,
            'pay_time'       => $payStatus == 1 ? $now : null,
            'start_time'     => $payStatus == 1 ? $now : null,
            'end_time'       => $payStatus == 1 ? $now + ($plan['duration_days'] * 86400) : null,
            'remark'         => $remark,
            'createtime'     => $now,
        ];

        $id = Db::name('hd_plan_order')->insertGetId($data);

        return json(['status' => 1, 'msg' => '创建成功', 'data' => ['id' => $id]]);
    }

    /**
     * 删除订单（仅允许删除未支付订单）
     */
    public function del()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $order = Db::name('hd_plan_order')->where('id', $id)->where('aid', aid)->find();
        if (!$order) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }
        if ($order['pay_status'] == 1) {
            return json(['status' => 0, 'msg' => '已支付订单不允许删除']);
        }

        Db::name('hd_plan_order')->where('id', $id)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 支付状态文案
     */
    private function getPayStatusText($status)
    {
        $map = [0 => '未支付', 1 => '已支付'];
        return $map[$status] ?? '未知';
    }

    /**
     * 订单类型文案
     */
    private function getOrderTypeText($type)
    {
        $map = [1 => '新购', 2 => '续费'];
        return $map[$type] ?? '未知';
    }

    /**
     * 计费周期文案
     */
    private function getPeriodText($period)
    {
        $map = ['month' => '月', 'quarter' => '季', 'year' => '年', 'forever' => '永久'];
        return $map[$period] ?? $period;
    }
}

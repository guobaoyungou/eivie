<?php
/**
 * 算八字使用记录控制器
 * 继承Common，提供后台管理员查看C端用户八字测算记录
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;

class BaziRecordController extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (bid > 0) showmsg('无访问权限');
    }

    /**
     * 记录列表页
     */
    public function index()
    {
        $pay_status = input('pay_status', '');

        $query = Db::name('bazi_order')
            ->where('aid', aid);

        // 按付费状态筛选
        if ($pay_status !== '') {
            $query->where('pay_status', intval($pay_status));
        }

        $list = $query->order('create_time desc')->paginate(20);

        View::assign('list', $list);
        View::assign('pay_status', $pay_status);
        return View::fetch();
    }

    /**
     * 记录详情（Ajax返回）
     */
    public function detail()
    {
        $id = input('id/d', 0);
        if ($id <= 0) {
            return json(['status' => 0, 'msg' => '缺少记录ID']);
        }

        $record = Db::name('bazi_order')
            ->where('id', $id)
            ->where('aid', aid)
            ->find();

        if (empty($record)) {
            return json(['status' => 0, 'msg' => '记录不存在']);
        }

        // 解析JSON
        $inputData = json_decode($record['input_json'], true) ?: [];
        $resultData = json_decode($record['result_json'], true) ?: [];

        // 付费状态文本
        $payStatusText = '未知';
        if ($record['pay_mode'] === 'free') {
            $payStatusText = '免费';
        } elseif ($record['pay_status'] == 1) {
            $payStatusText = '已支付';
        } elseif ($record['pay_status'] == 0) {
            $payStatusText = '待支付';
        }

        // 付费模式文本
        $payModeText = [
            'free'               => '免费',
            'pay_then_predict'   => '先付费后预测',
            'predict_then_pay'   => '预测后付费',
        ][$record['pay_mode']] ?? $record['pay_mode'];

        return json([
            'status' => 1,
            'data' => [
                'record' => $record,
                'input'  => $inputData,
                'result' => $resultData,
                'pay_status_text' => $payStatusText,
                'pay_mode_text'   => $payModeText,
            ],
        ]);
    }
}

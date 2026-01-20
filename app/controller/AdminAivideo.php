<?php
/**
 * AI旅拍商家后台控制器
 * 提供商家管理AI旅拍功能的后台界面
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;

class AdminAivideo extends BaseController
{
    public $aid;

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->aid = input('param.aid/d');
        define('aid', $this->aid);
    }

    /**
     * 商家配置列表
     * @return string
     */
    public function config_list()
    {
        $bid = input('param.bid/d', 0);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        $list = Db::name('aivideo_merchant_config')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_merchant_config')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 保存商家配置
     * @return string
     */
    public function save_config()
    {
        $id = input('param.id/d', 0);
        $bid = input('param.bid/d');
        $merchantName = input('param.merchant_name');
        $accessKey = input('param.access_key');
        $secretKey = input('param.secret_key');
        $monitorPath = input('param.monitor_path');
        $modelName = input('param.model_name', 'kling-v1');
        $mode = input('param.mode', 'std');
        $aspectRatio = input('param.aspect_ratio', '16:9');
        $duration = input('param.duration', '5');
        $autoUpload = input('param.auto_upload/d', 1);

        if (!$bid || !$merchantName || !$accessKey || !$secretKey) {
            return jsonEncode(['status' => 0, 'msg' => '参数不完整']);
        }

        $data = [
            'aid' => $this->aid,
            'bid' => $bid,
            'merchant_name' => $merchantName,
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
            'monitor_path' => $monitorPath,
            'model_name' => $modelName,
            'mode' => $mode,
            'aspect_ratio' => $aspectRatio,
            'duration' => $duration,
            'auto_upload' => $autoUpload,
            'status' => 1,
            'updatetime' => time(),
        ];

        if ($id > 0) {
            // 更新
            $data['id'] = $id;
            Db::name('aivideo_merchant_config')->where('id', $id)->update($data);
        } else {
            // 新增
            $data['createtime'] = time();
            Db::name('aivideo_merchant_config')->insert($data);
        }

        return jsonEncode(['status' => 1, 'msg' => '保存成功']);
    }

    /**
     * 提示词模板列表
     * @return string
     */
    public function template_list()
    {
        $bid = input('param.bid/d', 0);
        $templateType = input('param.template_type', '');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        if ($templateType) {
            $where[] = ['template_type', '=', $templateType];
        }

        $list = Db::name('aivideo_prompt_template')
            ->where($where)
            ->order('sort asc, id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_prompt_template')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 保存提示词模板
     * @return string
     */
    public function save_template()
    {
        $id = input('param.id/d', 0);
        $bid = input('param.bid/d', 0);
        $templateName = input('param.template_name');
        $templateType = input('param.template_type');
        $prompt = input('param.prompt');
        $negativePrompt = input('param.negative_prompt', '');
        $modelName = input('param.model_name', '');
        $mode = input('param.mode', '');
        $aspectRatio = input('param.aspect_ratio', '');
        $duration = input('param.duration', '');
        $effectScene = input('param.effect_scene', '');
        $sort = input('param.sort/d', 0);

        if (!$templateName || !$prompt || !$templateType) {
            return jsonEncode(['status' => 0, 'msg' => '参数不完整']);
        }

        $data = [
            'aid' => $this->aid,
            'bid' => $bid,
            'template_name' => $templateName,
            'template_type' => $templateType,
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'model_name' => $modelName,
            'mode' => $mode,
            'aspect_ratio' => $aspectRatio,
            'duration' => $duration,
            'effect_scene' => $effectScene,
            'sort' => $sort,
            'status' => 1,
            'updatetime' => time(),
        ];

        if ($id > 0) {
            $data['id'] = $id;
            Db::name('aivideo_prompt_template')->where('id', $id)->update($data);
        } else {
            $data['createtime'] = time();
            Db::name('aivideo_prompt_template')->insert($data);
        }

        return jsonEncode(['status' => 1, 'msg' => '保存成功']);
    }

    /**
     * 素材列表
     * @return string
     */
    public function material_list()
    {
        $bid = input('param.bid/d', 0);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
            ['status', '=', 1],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        $list = Db::name('aivideo_material')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_material')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 作品列表
     * @return string
     */
    public function work_list()
    {
        $bid = input('param.bid/d', 0);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
            ['status', '=', 1],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        $list = Db::name('aivideo_work')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_work')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 订单列表
     * @return string
     */
    public function order_list()
    {
        $bid = input('param.bid/d', 0);
        $payStatus = input('param.pay_status/d', -1);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        if ($payStatus >= 0) {
            $where[] = ['pay_status', '=', $payStatus];
        }

        $list = Db::name('aivideo_order')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_order')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 统计数据
     * @return string
     */
    public function statistics()
    {
        $bid = input('param.bid/d', 0);
        $startDate = input('param.start_date');
        $endDate = input('param.end_date');

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        if ($startDate) {
            $where[] = ['createtime', '>=', strtotime($startDate)];
        }

        if ($endDate) {
            $where[] = ['createtime', '<=', strtotime($endDate . ' 23:59:59')];
        }

        // 订单统计
        $orderCount = Db::name('aivideo_order')
            ->where($where)
            ->count();

        $paidCount = Db::name('aivideo_order')
            ->where($where)
            ->where('pay_status', 1)
            ->count();

        $totalAmount = Db::name('aivideo_order')
            ->where($where)
            ->where('pay_status', 1)
            ->sum('pay_price');

        // 作品统计
        $workCount = Db::name('aivideo_work')
            ->where($where)
            ->count();

        // 任务统计
        $taskCount = Db::name('aivideo_task')
            ->where($where)
            ->count();

        $successTaskCount = Db::name('aivideo_task')
            ->where($where)
            ->where('task_status', 'succeed')
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'order_count' => $orderCount,
                'paid_count' => $paidCount,
                'total_amount' => $totalAmount ?: 0,
                'work_count' => $workCount,
                'task_count' => $taskCount,
                'success_task_count' => $successTaskCount,
            ]
        ]);
    }
}

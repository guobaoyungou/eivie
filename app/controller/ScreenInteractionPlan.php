<?php
namespace app\controller;
use think\facade\View;
use think\facade\Db;
use app\service\hd\HdActivityService;
use app\controller\ScreenInteractionFeature;

class ScreenInteractionPlan extends Common
{
    /**
     * 套餐列表页
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $keyword = input('keyword', '');
            $status = input('status', '');

            $where = [];
            // 套餐为平台级资源(aid=0)，同时兼容当前管理员创建的套餐
            $where[] = ['aid', 'in', [0, aid]];
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            if ($status !== '') {
                $where[] = ['status', '=', intval($status)];
            }

            $count = Db::name('hd_plan')->where($where)->count();
            $list = Db::name('hd_plan')->where($where)
                ->order('sort desc, id asc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '-';
                $item['period_text'] = $this->getPeriodText($item['period'] ?? '');
                $item['features_list'] = $item['features'] ? explode(',', $item['features']) : [];
            }

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    /**
     * 新增/编辑套餐弹窗
     */
    public function edit()
    {
        $id = input('id', 0);
        $info = [];
        if ($id) {
            $info = Db::name('hd_plan')->where('id', $id)->whereIn('aid', [0, aid])->find();
        }

        if (!$info) {
            $info = [
                'id' => 0,
                'name' => '',
                'code' => '',
                'price' => '',
                'period' => 'month',
                'duration_days' => 30,
                'max_stores' => 1,
                'max_activities' => 5,
                'max_participants' => 100,
                'features' => '',
                'is_recommended' => 0,
                'allow_custom_wx' => 0,
                'allow_custom_display' => 0,
                'sort' => 0,
                'status' => 1,
            ];
        }

        // 所有可选功能：优先从 feature_registry 表读取，fallback 到常量
        $allFeatures = ScreenInteractionFeature::getFeatureNameMap();
        $selectedFeatures = $info['features'] ? explode(',', $info['features']) : [];

        if ($this->request->isPost()) {
            return $this->save();
        }

        View::assign('info', $info);
        View::assign('allFeatures', $allFeatures);
        View::assign('selectedFeatures', $selectedFeatures);
        return View::fetch();
    }

    /**
     * 保存套餐
     */
    private function save()
    {
        $id = input('post.id', 0);
        $data = [
            'aid' => 0, // 套餐为平台级资源，统一使用 aid=0
            'name' => input('post.name', ''),
            'code' => input('post.code', ''),
            'price' => floatval(input('post.price', 0)),
            'period' => input('post.period', 'month'),
            'duration_days' => intval(input('post.duration_days', 30)),
            'max_stores' => intval(input('post.max_stores', 1)),
            'max_activities' => intval(input('post.max_activities', 5)),
            'max_participants' => intval(input('post.max_participants', 100)),
            'is_recommended' => intval(input('post.is_recommended', 0)),
            'allow_custom_wx' => intval(input('post.allow_custom_wx', 0)),
            'allow_custom_display' => intval(input('post.allow_custom_display', 0)),
            'sort' => intval(input('post.sort', 0)),
            'status' => intval(input('post.status', 1)),
        ];

        // features: array or comma-separated string
        $features = input('post.features/a', []);
        if (is_array($features)) {
            $data['features'] = implode(',', $features);
        } else {
            $data['features'] = strval($features);
        }

        if (!$data['name']) {
            return json(['status' => 0, 'msg' => '请填写套餐名称']);
        }
        if (!$data['code']) {
            return json(['status' => 0, 'msg' => '请填写套餐编码']);
        }

        // 编码唯一性检查（平台级套餐 aid=0）
        $codeWhere = [['code', '=', $data['code']], ['aid', 'in', [0, aid]]];
        if ($id) {
            $codeWhere[] = ['id', '<>', $id];
        }
        if (Db::name('hd_plan')->where($codeWhere)->find()) {
            return json(['status' => 0, 'msg' => '套餐编码已存在']);
        }

        if ($id) {
            $exists = Db::name('hd_plan')->where('id', $id)->whereIn('aid', [0, aid])->find();
            if (!$exists) {
                return json(['status' => 0, 'msg' => '套餐不存在']);
            }
            Db::name('hd_plan')->where('id', $id)->update($data);
        } else {
            $data['createtime'] = time();
            $id = Db::name('hd_plan')->insertGetId($data);
        }

        return json(['status' => 1, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    /**
     * 删除套餐
     */
    public function del()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        // 检查是否被活动引用 (通过 plan_id 在 hd_business_config 中关联)
        $refCount = Db::name('hd_business_config')->where('plan_id', $id)->count();
        if ($refCount > 0) {
            return json(['status' => 0, 'msg' => '该套餐已被' . $refCount . '个商户使用，无法删除']);
        }

        Db::name('hd_plan')->where('id', $id)->whereIn('aid', [0, aid])->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 启用/停用套餐
     */
    public function setst()
    {
        $id = input('post.id', 0);
        $status = input('post.status', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        Db::name('hd_plan')->where('id', $id)->whereIn('aid', [0, aid])->update(['status' => intval($status)]);
        return json(['status' => 1, 'msg' => '操作成功']);
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

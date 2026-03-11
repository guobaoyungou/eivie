<?php
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class CreativeMember extends Common
{
    /**
     * 套餐列表
     */
    public function plan_list()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $keyword = input('keyword', '');
            $version = input('version_code', '');
            $mode = input('purchase_mode', '');
            $status = input('status', '');

            $where = [];
            $where[] = ['aid', '=', aid];
            if ($keyword) {
                $where[] = ['version_name', 'like', '%' . $keyword . '%'];
            }
            if ($version) {
                $where[] = ['version_code', '=', $version];
            }
            if ($mode) {
                $where[] = ['purchase_mode', '=', $mode];
            }
            if ($status !== '') {
                $where[] = ['status', '=', intval($status)];
            }

            $count = Db::name('creative_member_plan')->where($where)->count();
            $list = Db::name('creative_member_plan')->where($where)
                ->order('sort asc, id asc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '-';
                $item['mode_text'] = $this->getModeText($item['purchase_mode']);
                $item['model_rights_count'] = 0;
                if ($item['model_rights']) {
                    $rights = json_decode($item['model_rights'], true);
                    $item['model_rights_count'] = is_array($rights) ? count($rights) : 0;
                }
            }

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    /**
     * 套餐编辑页面
     */
    public function plan_edit()
    {
        $id = input('id', 0);
        $info = [];
        if ($id) {
            $info = Db::name('creative_member_plan')->where('id', $id)->where('aid', aid)->find();
            if ($info) {
                $info['model_rights'] = $info['model_rights'] ? json_decode($info['model_rights'], true) : [];
                $info['features'] = $info['features'] ? json_decode($info['features'], true) : [];
            }
        }

        if (!$info) {
            $info = [
                'id' => 0,
                'version_code' => 'basic',
                'version_name' => '',
                'purchase_mode' => 'yearly',
                'price' => '',
                'original_price' => '',
                'discount_text' => '',
                'monthly_score' => 800,
                'daily_login_score' => 20,
                'max_concurrency' => 3,
                'cloud_storage_gb' => 20,
                'model_rights' => [],
                'features' => [],
                'sort' => 0,
                'status' => 1,
            ];
        }

        View::assign('info', $info);
        return View::fetch();
    }

    /**
     * 套餐保存
     */
    public function plan_save()
    {
        if (!$this->request->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $id = input('post.id', 0);
        $data = [
            'aid' => aid,
            'version_code' => input('post.version_code', ''),
            'version_name' => input('post.version_name', ''),
            'purchase_mode' => input('post.purchase_mode', ''),
            'price' => input('post.price', 0),
            'original_price' => input('post.original_price', 0),
            'discount_text' => input('post.discount_text', ''),
            'monthly_score' => intval(input('post.monthly_score', 0)),
            'daily_login_score' => intval(input('post.daily_login_score', 20)),
            'max_concurrency' => intval(input('post.max_concurrency', 1)),
            'cloud_storage_gb' => intval(input('post.cloud_storage_gb', 0)),
            'sort' => intval(input('post.sort', 0)),
            'status' => intval(input('post.status', 1)),
        ];

        // model_rights JSON
        $modelRights = input('post.model_rights', '');
        if ($modelRights && is_string($modelRights)) {
            $data['model_rights'] = $modelRights;
        } else {
            $data['model_rights'] = json_encode([], JSON_UNESCAPED_UNICODE);
        }

        // features JSON
        $features = input('post.features', '');
        if ($features && is_string($features)) {
            $data['features'] = $features;
        } else {
            $data['features'] = json_encode([], JSON_UNESCAPED_UNICODE);
        }

        if (!$data['version_name']) {
            return json(['status' => 0, 'msg' => '请填写版本名称']);
        }
        if (!$data['price'] && $data['price'] !== '0') {
            return json(['status' => 0, 'msg' => '请填写售价']);
        }

        if ($id) {
            $exists = Db::name('creative_member_plan')->where('id', $id)->where('aid', aid)->find();
            if (!$exists) {
                return json(['status' => 0, 'msg' => '套餐不存在']);
            }
            Db::name('creative_member_plan')->where('id', $id)->update($data);
        } else {
            $data['createtime'] = time();
            $id = Db::name('creative_member_plan')->insertGetId($data);
        }

        return json(['status' => 1, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    /**
     * 套餐删除
     */
    public function plan_del()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        // 检查是否有关联订阅
        $subCount = Db::name('creative_member_subscription')->where('plan_id', $id)->count();
        if ($subCount > 0) {
            return json(['status' => 0, 'msg' => '该套餐有' . $subCount . '条订阅记录，无法删除']);
        }

        Db::name('creative_member_plan')->where('id', $id)->where('aid', aid)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 套餐状态切换
     */
    public function plan_status()
    {
        $id = input('post.id', 0);
        $status = input('post.status', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        Db::name('creative_member_plan')->where('id', $id)->where('aid', aid)->update(['status' => intval($status)]);
        return json(['status' => 1, 'msg' => '操作成功']);
    }

    /**
     * 订阅记录列表
     */
    public function subscription_list()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $mid = input('mid', '');
            $version = input('version_code', '');
            $status = input('status', '');

            $where = [];
            $where[] = ['s.aid', '=', aid];
            if ($mid) {
                $where[] = ['s.mid', '=', intval($mid)];
            }
            if ($version) {
                $where[] = ['s.version_code', '=', $version];
            }
            if ($status !== '') {
                $where[] = ['s.status', '=', intval($status)];
            }

            $count = Db::name('creative_member_subscription')->alias('s')->where($where)->count();
            $list = Db::name('creative_member_subscription')->alias('s')
                ->leftJoin('creative_member_plan p', 's.plan_id = p.id')
                ->leftJoin('member m', 's.mid = m.id')
                ->field('s.*, p.version_name, p.purchase_mode as plan_mode, p.price as plan_price, m.nickname as member_name, m.headimg as member_headimg')
                ->where($where)
                ->order('s.id desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['start_time_text'] = $item['start_time'] ? date('Y-m-d H:i', $item['start_time']) : '-';
                $item['expire_time_text'] = $item['expire_time'] ? date('Y-m-d H:i', $item['expire_time']) : '-';
                $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '-';
                $item['status_text'] = $this->getStatusText($item['status']);
                $item['mode_text'] = $this->getModeText($item['plan_mode'] ?? $item['purchase_mode']);
            }

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    /**
     * 积分流水列表
     */
    public function score_log()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $mid = input('mid', '');
            $type = input('type', '');

            $where = [];
            $where[] = ['l.aid', '=', aid];
            if ($mid) {
                $where[] = ['l.mid', '=', intval($mid)];
            }
            if ($type !== '') {
                $where[] = ['l.type', '=', intval($type)];
            }

            $count = Db::name('creative_member_score_log')->alias('l')->where($where)->count();
            $list = Db::name('creative_member_score_log')->alias('l')
                ->leftJoin('member m', 'l.mid = m.id')
                ->field('l.*, m.nickname as member_name')
                ->where($where)
                ->order('l.id desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            $typeMap = [1 => '月度发放', 2 => '每日登录', 3 => '消费扣除', 4 => '退款返还'];
            foreach ($list as &$item) {
                $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '-';
                $item['type_text'] = $typeMap[$item['type']] ?? '未知';
            }

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    private function getModeText($mode)
    {
        $map = ['yearly' => '按年', 'monthly_auto' => '连续包月', 'monthly' => '单月'];
        return $map[$mode] ?? $mode;
    }

    private function getStatusText($status)
    {
        $map = [0 => '已过期', 1 => '生效中', 2 => '已取消'];
        return $map[$status] ?? '未知';
    }
}

<?php
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\hd\HdActivityService;

/**
 * Backstage后台 - 大屏互动功能列表管理
 * 管理 ddwx_hd_feature_registry 表（平台级功能注册表）
 */
class ScreenInteractionFeature extends Common
{
    /**
     * 功能列表页（HTML / AJAX）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $keyword = input('keyword', '');
            $status  = input('status', '');

            $where = [];
            if ($keyword) {
                $where[] = ['feature_name', 'like', '%' . $keyword . '%'];
            }
            if ($status !== '') {
                $where[] = ['status', '=', intval($status)];
            }

            // 查询全部（不分页，功能数量有限）
            $list = Db::name('hd_feature_registry')
                ->where($where)
                ->order('parent_id asc, sort asc, id asc')
                ->select()
                ->toArray();

            // 组装树形平铺结构
            $tree = $this->buildFlatTree($list);

            return json(['code' => 0, 'count' => count($tree), 'data' => $tree]);
        }

        return View::fetch();
    }

    /**
     * 新增/编辑功能弹窗（GET 展示 / POST 保存）
     */
    public function edit()
    {
        $id = input('id', 0);
        $info = [];

        if ($id) {
            $info = Db::name('hd_feature_registry')->where('id', $id)->find();
        }

        if (!$info) {
            // 自动计算排序值
            $maxSort = Db::name('hd_feature_registry')->max('sort') ?: 0;
            $info = [
                'id'           => 0,
                'feature_code' => '',
                'feature_name' => '',
                'icon'         => '',
                'parent_id'    => 0,
                'sort'         => $maxSort + 1,
                'status'       => 1,
                'is_system'    => 0,
                'description'  => '',
            ];
        }

        if ($this->request->isPost()) {
            return $this->save();
        }

        // 获取所有一级功能（供 parent_id 下拉）
        $topFeatures = Db::name('hd_feature_registry')
            ->where('parent_id', 0)
            ->order('sort asc, id asc')
            ->field('id, feature_code, feature_name')
            ->select()
            ->toArray();

        View::assign('info', $info);
        View::assign('topFeatures', $topFeatures);
        return View::fetch();
    }

    /**
     * 保存功能（新增/编辑）
     */
    private function save()
    {
        $id           = intval(input('post.id', 0));
        $featureCode  = trim(input('post.feature_code', ''));
        $featureName  = trim(input('post.feature_name', ''));
        $icon         = trim(input('post.icon', ''));
        $parentId     = intval(input('post.parent_id', 0));
        $sort         = intval(input('post.sort', 0));
        $status       = intval(input('post.status', 1));
        $description  = trim(input('post.description', ''));

        // 校验 feature_name 必填
        if (!$featureName) {
            return json(['status' => 0, 'msg' => '请填写功能名称']);
        }

        // 校验 feature_code 格式：仅允许字母、数字、下划线，长度 2~32
        if (!$id && !$featureCode) {
            return json(['status' => 0, 'msg' => '请填写功能标识']);
        }
        if ($featureCode && !preg_match('/^[a-zA-Z0-9_]{2,32}$/', $featureCode)) {
            return json(['status' => 0, 'msg' => '功能标识仅允许字母、数字、下划线，长度2~32']);
        }

        // 校验 feature_code 唯一性
        if ($featureCode) {
            $codeWhere = [['feature_code', '=', $featureCode]];
            if ($id) {
                $codeWhere[] = ['id', '<>', $id];
            }
            if (Db::name('hd_feature_registry')->where($codeWhere)->find()) {
                return json(['status' => 0, 'msg' => '功能标识已存在']);
            }
        }

        // 校验层级深度：最多两级
        if ($parentId > 0) {
            $parent = Db::name('hd_feature_registry')->where('id', $parentId)->find();
            if (!$parent) {
                return json(['status' => 0, 'msg' => '父级功能不存在']);
            }
            if ($parent['parent_id'] > 0) {
                return json(['status' => 0, 'msg' => '最多支持两级菜单，不能设置三级']);
            }
        }

        $now = time();

        if ($id) {
            // 编辑
            $exists = Db::name('hd_feature_registry')->where('id', $id)->find();
            if (!$exists) {
                return json(['status' => 0, 'msg' => '功能不存在']);
            }

            $data = [
                'feature_name' => $featureName,
                'icon'         => $icon,
                'parent_id'    => $parentId,
                'sort'         => $sort,
                'status'       => $status,
                'description'  => $description,
                'updatetime'   => $now,
            ];

            // 编辑时不允许修改 feature_code（系统预置或自定义均不可改）
            Db::name('hd_feature_registry')->where('id', $id)->update($data);
        } else {
            // 新增
            $data = [
                'feature_code' => $featureCode,
                'feature_name' => $featureName,
                'icon'         => $icon,
                'parent_id'    => $parentId,
                'sort'         => $sort,
                'status'       => $status,
                'is_system'    => 0,
                'description'  => $description,
                'config'       => null,
                'createtime'   => $now,
                'updatetime'   => $now,
            ];
            $id = Db::name('hd_feature_registry')->insertGetId($data);
        }

        return json(['status' => 1, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    /**
     * 删除功能
     */
    public function del()
    {
        $id = intval(input('post.id', 0));
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $feature = Db::name('hd_feature_registry')->where('id', $id)->find();
        if (!$feature) {
            return json(['status' => 0, 'msg' => '功能不存在']);
        }

        // 系统预置功能不允许删除
        if ($feature['is_system'] == 1) {
            return json(['status' => 0, 'msg' => '系统预置功能不可删除，仅支持改名/排序/启停/设层级']);
        }

        // 检查是否有子级
        $childCount = Db::name('hd_feature_registry')->where('parent_id', $id)->count();
        if ($childCount > 0) {
            return json(['status' => 0, 'msg' => '该功能下有' . $childCount . '个子功能，请先处理子功能']);
        }

        // 检查是否被活动引用
        $refCount = Db::name('hd_activity_feature')->where('feature_code', $feature['feature_code'])->count();
        if ($refCount > 0) {
            // 提示但仍可删除
            // 此处直接删除注册记录，活动中已有的功能不受影响
        }

        Db::name('hd_feature_registry')->where('id', $id)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 启用/停用功能
     */
    public function setst()
    {
        $id     = intval(input('post.id', 0));
        $status = intval(input('post.status', 0));
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        if (!in_array($status, [1, 2])) {
            return json(['status' => 0, 'msg' => '无效的状态值']);
        }

        Db::name('hd_feature_registry')->where('id', $id)->update([
            'status'     => $status,
            'updatetime' => time(),
        ]);
        return json(['status' => 1, 'msg' => '操作成功']);
    }

    /**
     * 批量更新排序
     */
    public function sort()
    {
        $sorts = input('post.sorts/a', []);
        if (empty($sorts)) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $now = time();
        foreach ($sorts as $item) {
            if (isset($item['id']) && isset($item['sort'])) {
                Db::name('hd_feature_registry')
                    ->where('id', intval($item['id']))
                    ->update([
                        'sort'       => intval($item['sort']),
                        'updatetime' => $now,
                    ]);
            }
        }

        return json(['status' => 1, 'msg' => '排序更新成功']);
    }

    /**
     * 设置/取消父级（层级调整）
     */
    public function set_parent()
    {
        $id       = intval(input('post.id', 0));
        $parentId = intval(input('post.parent_id', 0));

        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $feature = Db::name('hd_feature_registry')->where('id', $id)->find();
        if (!$feature) {
            return json(['status' => 0, 'msg' => '功能不存在']);
        }

        // 不能设为自己的子级
        if ($parentId == $id) {
            return json(['status' => 0, 'msg' => '不能将功能设为自身的子级']);
        }

        // 校验层级深度
        if ($parentId > 0) {
            $parent = Db::name('hd_feature_registry')->where('id', $parentId)->find();
            if (!$parent) {
                return json(['status' => 0, 'msg' => '父级功能不存在']);
            }
            if ($parent['parent_id'] > 0) {
                return json(['status' => 0, 'msg' => '最多支持两级菜单']);
            }
        }

        // 检查当前功能是否有子级（有子级不能变为二级）
        if ($parentId > 0) {
            $childCount = Db::name('hd_feature_registry')->where('parent_id', $id)->count();
            if ($childCount > 0) {
                return json(['status' => 0, 'msg' => '该功能有子级功能，不能设为二级菜单']);
            }
        }

        Db::name('hd_feature_registry')->where('id', $id)->update([
            'parent_id'  => $parentId,
            'updatetime' => time(),
        ]);

        return json(['status' => 1, 'msg' => '层级设置成功']);
    }

    /**
     * 构建树形平铺列表（一级 + 二级交替排列）
     */
    private function buildFlatTree(array $list): array
    {
        $tree = [];
        $childrenMap = [];

        // 分组
        foreach ($list as $item) {
            $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '-';
            $item['update_time_text'] = $item['updatetime'] ? date('Y-m-d H:i', $item['updatetime']) : '-';
            $item['level'] = $item['parent_id'] > 0 ? 2 : 1;

            if ($item['parent_id'] == 0) {
                $tree[$item['id']] = $item;
            } else {
                $childrenMap[$item['parent_id']][] = $item;
            }
        }

        // 插入子级到父级后面
        $result = [];
        foreach ($tree as $id => $item) {
            $result[] = $item;
            if (isset($childrenMap[$id])) {
                foreach ($childrenMap[$id] as $child) {
                    $result[] = $child;
                }
            }
        }

        // 处理没有找到父级的孤立子级
        foreach ($childrenMap as $parentId => $children) {
            if (!isset($tree[$parentId])) {
                foreach ($children as $child) {
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    /**
     * 获取全部功能列表（供其他模块调用的静态方法）
     * 优先从数据库读取，fallback 到常量
     */
    public static function getAllFeatures(): array
    {
        try {
            $list = Db::name('hd_feature_registry')
                ->where('status', 1)
                ->order('sort asc, id asc')
                ->select()
                ->toArray();
            if (!empty($list)) {
                return $list;
            }
        } catch (\Exception $e) {
            // 表不存在时 fallback
        }

        // Fallback: 从常量构建
        $result = [];
        foreach (HdActivityService::ALL_FEATURES as $code => $name) {
            $result[] = [
                'feature_code' => $code,
                'feature_name' => $name,
                'parent_id'    => 0,
                'sort'         => count($result) + 1,
                'status'       => 1,
                'is_system'    => 1,
            ];
        }
        return $result;
    }

    /**
     * 获取功能名称映射（code => name）
     */
    public static function getFeatureNameMap(): array
    {
        $list = self::getAllFeatures();
        $map = [];
        foreach ($list as $item) {
            $map[$item['feature_code']] = $item['feature_name'];
        }
        return $map;
    }
}

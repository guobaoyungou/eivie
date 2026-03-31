<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;

/**
 * 大屏互动 - 门店管理服务
 */
class HdStoreService
{
    /**
     * 门店列表
     */
    public function getList(int $aid, int $bid, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
        ];

        if (!empty($params['keyword'])) {
            $where[] = ['name|address|tel', 'like', '%' . $params['keyword'] . '%'];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $list = Db::name('mendian')
            ->where($where)
            ->page($page, $limit)
            ->order('id desc')
            ->select()
            ->toArray();

        $count = Db::name('mendian')->where($where)->count();

        return [
            'code'  => 0,
            'data'  => [
                'list'  => $list,
                'count' => $count,
            ],
        ];
    }

    /**
     * 创建门店
     */
    public function create(int $aid, int $bid, array $data): array
    {
        try {
            // 检查套餐限制
            if (isset($data['max_stores'])) {
                $currentCount = Db::name('mendian')->where('aid', $aid)->where('bid', $bid)->count();
                if ($currentCount >= $data['max_stores']) {
                    return ['code' => 1, 'msg' => '已达到套餐门店数上限(' . $data['max_stores'] . '个)'];
                }
            }

            $mdid = Db::name('mendian')->insertGetId([
                'aid'        => $aid,
                'bid'        => $bid,
                'name'       => $data['name'] ?? '',
                'address'    => $data['address'] ?? '',
                'tel'        => $data['tel'] ?? '',
                'lng'        => $data['lng'] ?? '',
                'lat'        => $data['lat'] ?? '',
                'status'     => 1,
                'createtime' => time(),
            ]);

            return ['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $mdid]];
        } catch (\Exception $e) {
            Log::error('创建门店失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 门店详情
     */
    public function detail(int $aid, int $bid, int $id): array
    {
        $store = Db::name('mendian')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$store) {
            return ['code' => 1, 'msg' => '门店不存在'];
        }

        return ['code' => 0, 'data' => $store];
    }

    /**
     * 更新门店
     */
    public function update(int $aid, int $bid, int $id, array $data): array
    {
        $store = Db::name('mendian')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$store) {
            return ['code' => 1, 'msg' => '门店不存在'];
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['tel'])) $updateData['tel'] = $data['tel'];
        if (isset($data['lng'])) $updateData['lng'] = $data['lng'];
        if (isset($data['lat'])) $updateData['lat'] = $data['lat'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];

        if ($updateData) {
            Db::name('mendian')->where('id', $id)->update($updateData);
        }

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除门店
     */
    public function delete(int $aid, int $bid, int $id): array
    {
        $store = Db::name('mendian')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$store) {
            return ['code' => 1, 'msg' => '门店不存在'];
        }

        // 检查是否有关联活动
        $activityCount = Db::name('hd_activity')
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $id)
            ->count();

        if ($activityCount > 0) {
            return ['code' => 1, 'msg' => '该门店下还有活动，无法删除'];
        }

        Db::name('mendian')->where('id', $id)->delete();

        return ['code' => 0, 'msg' => '删除成功'];
    }
}

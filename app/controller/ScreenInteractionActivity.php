<?php
namespace app\controller;
use think\facade\View;
use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdParticipant;
use app\service\hd\HdActivityService;
use app\controller\ScreenInteractionFeature;

class ScreenInteractionActivity extends Common
{
    /**
     * 活动列表页
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $keyword = input('keyword', '');
            $status = input('status', '');

            $where = [];
            $where[] = ['a.aid', '=', aid];
            if ($keyword) {
                $where[] = ['a.title', 'like', '%' . $keyword . '%'];
            }
            if ($status !== '') {
                $where[] = ['a.status', '=', intval($status)];
            }

            $count = Db::name('hd_activity')->alias('a')->where($where)->count();
            $list = Db::name('hd_activity')->alias('a')
                ->leftJoin('business b', 'a.bid = b.id')
                ->field('a.*, b.name as business_name')
                ->where($where)
                ->order('a.id desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['participant_count'] = Db::name('hd_participant')->where('activity_id', $item['id'])->count();
                $item['signed_count'] = Db::name('hd_participant')->where('activity_id', $item['id'])->where('flag', 2)->count();
                $item['started_at_text'] = $item['started_at'] ? date('Y-m-d H:i', $item['started_at']) : '-';
                $item['ended_at_text'] = $item['ended_at'] ? date('Y-m-d H:i', $item['ended_at']) : '-';
                $item['create_time_text'] = $item['createtime'] ? date('Y-m-d H:i', $item['createtime']) : '-';
                $item['screen_url'] = 'https://wxhd.eivie.cn/s/' . $item['access_code'];
                $item['status_text'] = $this->getStatusText($item['status']);
            }

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    /**
     * 新增/编辑活动弹窗
     */
    public function edit()
    {
        $id = input('id', 0);
        $info = [];
        if ($id) {
            $info = Db::name('hd_activity')->where('id', $id)->where('aid', aid)->find();
            if ($info) {
                $info['started_at_text'] = $info['started_at'] ? date('Y-m-d H:i', $info['started_at']) : '';
                $info['ended_at_text'] = $info['ended_at'] ? date('Y-m-d H:i', $info['ended_at']) : '';
            }
        }

        if (!$info) {
            $info = [
                'id' => 0,
                'title' => '',
                'bid' => 0,
                'mdid' => 0,
                'started_at' => 0,
                'ended_at' => 0,
                'started_at_text' => '',
                'ended_at_text' => '',
                'verifycode' => substr(md5(uniqid()), 0, 6),
                'status' => 1,
            ];
        }

        if ($this->request->isPost()) {
            return $this->saveActivity();
        }

        // 获取商户列表
        $businessList = Db::name('business')->where('aid', aid)->field('id, name')->select()->toArray();
        // 获取门店列表
        $mendianList = Db::name('mendian')->where('aid', aid)->field('id, name, bid')->select()->toArray();

        View::assign('info', $info);
        View::assign('businessList', $businessList);
        View::assign('mendianList', $mendianList);
        return View::fetch();
    }

    /**
     * 保存活动
     */
    private function saveActivity()
    {
        $id = input('post.id', 0);
        $data = [
            'aid' => aid,
            'title' => input('post.title', ''),
            'bid' => intval(input('post.bid', 0)),
            'mdid' => intval(input('post.mdid', 0)),
            'verifycode' => input('post.verifycode', ''),
        ];

        $startedAt = input('post.started_at', '');
        $endedAt = input('post.ended_at', '');
        $data['started_at'] = $startedAt ? strtotime($startedAt) : 0;
        $data['ended_at'] = $endedAt ? strtotime($endedAt) : 0;

        if (!$data['title']) {
            return json(['status' => 0, 'msg' => '请填写活动标题']);
        }
        if ($data['ended_at'] && $data['started_at'] && $data['ended_at'] <= $data['started_at']) {
            return json(['status' => 0, 'msg' => '结束时间必须晚于开始时间']);
        }

        if ($id) {
            $exists = Db::name('hd_activity')->where('id', $id)->where('aid', aid)->find();
            if (!$exists) {
                return json(['status' => 0, 'msg' => '活动不存在']);
            }
            Db::name('hd_activity')->where('id', $id)->update($data);
        } else {
            $data['access_code'] = HdActivity::generateAccessCode();
            $data['status'] = HdActivity::STATUS_NOT_STARTED;
            $data['screen_config'] = json_encode([]);
            $data['createtime'] = time();
            $id = Db::name('hd_activity')->insertGetId($data);

            // 初始化功能配置：从 feature_registry 读取启用功能
            $registryFeatures = ScreenInteractionFeature::getAllFeatures();
            if (!empty($registryFeatures)) {
                // 构建 parent_id => feature_code 的映射
                $idToCodeMap = [];
                foreach ($registryFeatures as $rf) {
                    $idToCodeMap[$rf['id']] = $rf['feature_code'];
                }
                foreach ($registryFeatures as $sort => $rf) {
                    $parentCode = '';
                    if ($rf['parent_id'] > 0 && isset($idToCodeMap[$rf['parent_id']])) {
                        $parentCode = $idToCodeMap[$rf['parent_id']];
                    }
                    Db::name('hd_activity_feature')->insert([
                        'aid'          => aid,
                        'bid'          => $data['bid'],
                        'activity_id'  => $id,
                        'feature_code' => $rf['feature_code'],
                        'enabled'      => 1,
                        'config'       => json_encode([]),
                        'sort'         => $rf['sort'] ?? $sort,
                        'parent_code'  => $parentCode,
                        'display_name' => $rf['feature_name'] ?? '',
                    ]);
                }
            } else {
                // Fallback: 硬编码默认功能
                $defaultFeatures = ['qdq', 'wall', 'lottery'];
                foreach ($defaultFeatures as $sort => $code) {
                    Db::name('hd_activity_feature')->insert([
                        'aid'          => aid,
                        'bid'          => $data['bid'],
                        'activity_id'  => $id,
                        'feature_code' => $code,
                        'enabled'      => 1,
                        'config'       => json_encode([]),
                        'sort'         => $sort,
                        'parent_code'  => '',
                        'display_name' => '',
                    ]);
                }
            }
        }

        return json(['status' => 1, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    /**
     * 删除活动
     */
    public function del()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $activity = Db::name('hd_activity')->where('id', $id)->where('aid', aid)->find();
        if (!$activity) {
            return json(['status' => 0, 'msg' => '活动不存在']);
        }

        Db::startTrans();
        try {
            // 删除关联数据
            Db::name('hd_activity_feature')->where('activity_id', $id)->delete();
            Db::name('hd_participant')->where('activity_id', $id)->delete();
            Db::name('hd_prize')->where('activity_id', $id)->delete();
            Db::name('hd_wall_message')->where('activity_id', $id)->delete();
            Db::name('hd_lottery_config')->where('activity_id', $id)->delete();
            Db::name('hd_shake_config')->where('activity_id', $id)->delete();
            Db::name('hd_shake_record')->where('activity_id', $id)->delete();
            Db::name('hd_game_config')->where('activity_id', $id)->delete();
            Db::name('hd_game_record')->where('activity_id', $id)->delete();
            Db::name('hd_redpacket_config')->where('activity_id', $id)->delete();
            Db::name('hd_redpacket_round')->where('activity_id', $id)->delete();
            Db::name('hd_redpacket_user')->where('activity_id', $id)->delete();
            Db::name('hd_vote_item')->where('activity_id', $id)->delete();
            Db::name('hd_vote_record')->where('activity_id', $id)->delete();
            Db::name('hd_danmu_config')->where('activity_id', $id)->delete();
            Db::name('hd_background')->where('activity_id', $id)->delete();
            Db::name('hd_music')->where('activity_id', $id)->delete();
            Db::name('hd_activity')->where('id', $id)->delete();

            Db::commit();
            return json(['status' => 1, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['status' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 开启/关闭活动状态
     */
    public function setst()
    {
        $id = input('post.id', 0);
        $status = intval(input('post.status', 0));
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $validStatuses = [
            HdActivity::STATUS_NOT_STARTED,
            HdActivity::STATUS_IN_PROGRESS,
            HdActivity::STATUS_ENDED,
        ];
        if (!in_array($status, $validStatuses)) {
            return json(['status' => 0, 'msg' => '无效的状态值']);
        }

        $activity = Db::name('hd_activity')->where('id', $id)->where('aid', aid)->find();
        if (!$activity) {
            return json(['status' => 0, 'msg' => '活动不存在']);
        }

        Db::name('hd_activity')->where('id', $id)->update(['status' => $status]);
        return json(['status' => 1, 'msg' => '操作成功']);
    }

    /**
     * 活动详情页
     */
    public function detail()
    {
        $id = input('id', 0);
        if (!$id) {
            return '参数错误';
        }

        $info = Db::name('hd_activity')->alias('a')
            ->leftJoin('business b', 'a.bid = b.id')
            ->field('a.*, b.name as business_name')
            ->where('a.id', $id)
            ->where('a.aid', aid)
            ->find();

        if (!$info) {
            return '活动不存在';
        }

        $info['started_at_text'] = $info['started_at'] ? date('Y-m-d H:i', $info['started_at']) : '-';
        $info['ended_at_text'] = $info['ended_at'] ? date('Y-m-d H:i', $info['ended_at']) : '-';
        $info['create_time_text'] = $info['createtime'] ? date('Y-m-d H:i', $info['createtime']) : '-';
        $info['screen_url'] = 'https://wxhd.eivie.cn/s/' . $info['access_code'];
        $info['status_text'] = $this->getStatusText($info['status']);

        // 参与统计
        $info['participant_count'] = Db::name('hd_participant')->where('activity_id', $id)->count();
        $info['signed_count'] = Db::name('hd_participant')->where('activity_id', $id)->where('flag', 2)->count();

        // 功能配置：优先从 feature_registry 读取名称
        $features = Db::name('hd_activity_feature')->where('activity_id', $id)->order('sort asc')->select()->toArray();
        $featureNameMap = ScreenInteractionFeature::getFeatureNameMap();
        foreach ($features as &$f) {
            // 优先用活动级自定义名称，其次用注册表名称，最后 fallback 到常量
            $f['feature_name'] = (!empty($f['display_name'])) ? $f['display_name'] : ($featureNameMap[$f['feature_code']] ?? ($allFeatures[$f['feature_code']] ?? $f['feature_code']));
        }
        unset($f);
        $allFeatures = HdActivityService::ALL_FEATURES;

        // 消息统计
        $info['message_count'] = Db::name('hd_wall_message')->where('activity_id', $id)->count();

        View::assign('info', $info);
        View::assign('features', $features);
        return View::fetch();
    }

    /**
     * 状态文案
     */
    private function getStatusText($status)
    {
        $map = [1 => '未开始', 2 => '进行中', 3 => '已结束'];
        return $map[$status] ?? '未知';
    }
}

<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdParticipant;
use app\model\hd\HdPrize;

/**
 * 大屏互动 - 活动管理服务
 */
class HdActivityService
{
    // 全部功能代码
    const ALL_FEATURES = [
        'qdq'                  => '签到墙',
        'threedimensionalsign' => '3D签到',
        'wall'                 => '微信上墙',
        'danmu'                => '弹幕',
        'vote'                 => '投票',
        'lottery'              => '大屏抽奖',
        'choujiang'            => '手机抽奖',
        'ydj'                  => '摇大奖',
        'shake'                => '摇一摇竞技',
        'game'                 => '互动游戏',
        'redpacket'            => '红包雨',
        'importlottery'        => '导入抽奖',
        'kaimu'                => '开幕墙',
        'bimu'                 => '闭幕墙',
        'xiangce'              => '相册',
        'xyh'                  => '幸运号码',
        'xysjh'                => '幸运手机号',
        'lvpai'                => '旅拍大屏',
        'scan_lottery'         => '扫码抽奖',
    ];

    /**
     * 活动列表
     */
    public function getList(int $aid, int $bid, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
        ];

        if (!empty($params['keyword'])) {
            $where[] = ['title', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        if (!empty($params['mdid'])) {
            $where[] = ['mdid', '=', (int)$params['mdid']];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        $list = HdActivity::where($where)
            ->page($page, $limit)
            ->order('id desc')
            ->select()
            ->toArray();

        // 附加参与者统计
        foreach ($list as &$item) {
            $item['participant_count'] = HdParticipant::where('activity_id', $item['id'])->count();
            $item['signed_count'] = HdParticipant::where('activity_id', $item['id'])
                ->where('flag', HdParticipant::FLAG_SIGNED)->count();
        }
        unset($item);

        $count = HdActivity::where($where)->count();

        return [
            'code' => 0,
            'data' => [
                'list'  => $list,
                'count' => $count,
            ],
        ];
    }

    /**
     * 创建活动
     */
    public function create(int $aid, int $bid, array $data, $plan = null): array
    {
        Db::startTrans();
        try {
            // 检查套餐活动数限制
            if ($plan && $plan->max_activities > 0) {
                $currentCount = HdActivity::where('aid', $aid)->where('bid', $bid)->count();
                if ($currentCount >= $plan->max_activities) {
                    throw new \Exception('已达到套餐活动数上限(' . $plan->max_activities . '个)');
                }
            }

            $activity = new HdActivity();
            $activity->aid = $aid;
            $activity->bid = $bid;
            $activity->mdid = (int)($data['mdid'] ?? 0);
            $activity->title = $data['title'] ?? '未命名活动';
            $activity->access_code = HdActivity::generateAccessCode();
            $activity->started_at = !empty($data['started_at']) ? (is_numeric($data['started_at']) ? (int)$data['started_at'] : strtotime($data['started_at'])) : null;
            $activity->ended_at = !empty($data['ended_at']) ? (is_numeric($data['ended_at']) ? (int)$data['ended_at'] : strtotime($data['ended_at'])) : null;
            $activity->status = HdActivity::STATUS_NOT_STARTED;
            $activity->verifycode = $data['verifycode'] ?? substr(md5(uniqid()), 0, 6);
            $activity->screen_config = $data['screen_config'] ?? [];
            $activity->createtime = time();
            $activity->save();

            // 初始化默认功能配置
            $defaultFeatures = ['qdq', 'wall', 'lottery'];
            foreach ($defaultFeatures as $sort => $code) {
                HdActivityFeature::create([
                    'aid'          => $aid,
                    'bid'          => $bid,
                    'activity_id'  => $activity->id,
                    'feature_code' => $code,
                    'enabled'      => 1,
                    'config'       => json_encode([]),
                    'sort'         => $sort,
                ]);
            }

            Db::commit();

            return [
                'code' => 0,
                'msg'  => '创建成功',
                'data' => [
                    'id'          => $activity->id,
                    'access_code' => $activity->access_code,
                    'url'         => 'https://wxhd.eivie.cn/s/' . $activity->access_code,
                ],
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建活动失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 活动详情
     */
    public function detail(int $aid, int $bid, int $id): array
    {
        $activity = HdActivity::where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $data = $activity->toArray();
        $data['features'] = HdActivityFeature::where('activity_id', $id)
            ->order('sort asc')
            ->select()
            ->toArray();
        $data['participant_count'] = HdParticipant::where('activity_id', $id)->count();
        $data['signed_count'] = HdParticipant::where('activity_id', $id)
            ->where('flag', HdParticipant::FLAG_SIGNED)->count();
        $data['url'] = 'https://wxhd.eivie.cn/s/' . $activity->access_code;

        return ['code' => 0, 'data' => $data];
    }

    /**
     * 更新活动
     */
    public function update(int $aid, int $bid, int $id, array $data, $plan = null): array
    {
        $activity = HdActivity::where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        if (isset($data['title'])) $activity->title = $data['title'];
        if (isset($data['mdid'])) $activity->mdid = (int)$data['mdid'];
        if (isset($data['started_at'])) {
            $activity->started_at = is_numeric($data['started_at']) ? (int)$data['started_at'] : strtotime($data['started_at']);
        }
        if (isset($data['ended_at'])) {
            $activity->ended_at = is_numeric($data['ended_at']) ? (int)$data['ended_at'] : strtotime($data['ended_at']);
        }
        if (isset($data['verifycode'])) $activity->verifycode = $data['verifycode'];
        if (isset($data['status'])) $activity->status = (int)$data['status'];

        // 处理 max_participants：套餐限值校验 + 合并到 screen_config
        if (isset($data['max_participants'])) {
            $maxP = (int)$data['max_participants'];

            // 套餐二次校验
            if ($plan && $plan->max_participants > 0 && $maxP > $plan->max_participants) {
                return [
                    'code' => 1,
                    'msg'  => '参与人数超出套餐上限(' . $plan->max_participants . '人)，请升级套餐'
                ];
            }

            // 合并到 screen_config JSON，不覆盖其他配置
            $screenConfig = $activity->screen_config;
            if (is_string($screenConfig)) {
                $screenConfig = json_decode($screenConfig, true) ?: [];
            }
            if (!is_array($screenConfig)) {
                $screenConfig = [];
            }
            $screenConfig['max_participants'] = $maxP;
            $activity->screen_config = $screenConfig;
        }

        // 如果单独传入了 screen_config（非 max_participants 路径），仍然支持原有逻辑
        if (isset($data['screen_config']) && !isset($data['max_participants'])) {
            $activity->screen_config = $data['screen_config'];
        }

        $activity->save();

        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除活动
     */
    public function delete(int $aid, int $bid, int $id): array
    {
        $activity = HdActivity::where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        Db::startTrans();
        try {
            // 删除关联数据
            HdActivityFeature::where('activity_id', $id)->delete();
            HdParticipant::where('activity_id', $id)->delete();
            HdPrize::where('activity_id', $id)->delete();
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

            $activity->delete();

            Db::commit();
            return ['code' => 0, 'msg' => '删除成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 切换活动状态
     */
    public function updateStatus(int $aid, int $bid, int $id, int $status): array
    {
        $activity = HdActivity::where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $id)
            ->find();

        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $activity->status = $status;
        $activity->save();

        return ['code' => 0, 'msg' => '状态更新成功'];
    }

    /**
     * 获取活动功能配置列表
     */
    public function getFeatures(int $aid, int $bid, int $activityId): array
    {
        $features = HdActivityFeature::where('aid', $aid)
            ->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->order('sort asc')
            ->select()
            ->toArray();

        // 补充功能名称
        foreach ($features as &$f) {
            $f['feature_name'] = self::ALL_FEATURES[$f['feature_code']] ?? $f['feature_code'];
        }
        unset($f);

        return ['code' => 0, 'data' => $features];
    }

    /**
     * 更新功能配置
     */
    public function updateFeature(int $aid, int $bid, int $activityId, string $featureCode, array $data): array
    {
        $feature = HdActivityFeature::where('aid', $aid)
            ->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->where('feature_code', $featureCode)
            ->find();

        if (!$feature) {
            // 新建
            $feature = new HdActivityFeature();
            $feature->aid = $aid;
            $feature->bid = $bid;
            $feature->activity_id = $activityId;
            $feature->feature_code = $featureCode;
        }

        if (isset($data['enabled'])) $feature->enabled = (int)$data['enabled'];
        if (isset($data['config'])) $feature->config = $data['config'];
        if (isset($data['sort'])) $feature->sort = (int)$data['sort'];

        $feature->save();

        return ['code' => 0, 'msg' => '配置更新成功'];
    }

    /**
     * 参与者列表
     */
    public function getParticipants(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['activity_id', '=', $activityId],
        ];

        if (isset($params['flag']) && $params['flag'] !== '') {
            $where[] = ['flag', '=', (int)$params['flag']];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdParticipant::where($where)
            ->page($page, $limit)
            ->order('id desc')
            ->select()
            ->toArray();

        $count = HdParticipant::where($where)->count();

        return [
            'code' => 0,
            'data' => [
                'list'  => $list,
                'count' => $count,
            ],
        ];
    }

    /**
     * 活动数据统计
     */
    public function getStats(int $aid, int $bid, int $activityId): array
    {
        $totalParticipants = HdParticipant::where('activity_id', $activityId)->count();
        $signedCount = HdParticipant::where('activity_id', $activityId)
            ->where('flag', HdParticipant::FLAG_SIGNED)->count();
        $messageCount = Db::name('hd_wall_message')
            ->where('activity_id', $activityId)->count();
        $approvedMessages = Db::name('hd_wall_message')
            ->where('activity_id', $activityId)
            ->where('is_approved', 1)->count();

        return [
            'code' => 0,
            'data' => [
                'total_participants' => $totalParticipants,
                'signed_count'       => $signedCount,
                'message_count'      => $messageCount,
                'approved_messages'  => $approvedMessages,
            ],
        ];
    }

    /**
     * 克隆活动（复制活动配置和功能配置，不复制参与者数据）
     */
    public function cloneActivity(int $aid, int $bid, int $sourceId, $plan = null): array
    {
        $source = HdActivity::where('aid', $aid)
            ->where('bid', $bid)
            ->where('id', $sourceId)
            ->find();

        if (!$source) {
            return ['code' => 1, 'msg' => '源活动不存在'];
        }

        // 检查套餐活动数限制
        if ($plan && $plan->max_activities > 0) {
            $currentCount = HdActivity::where('aid', $aid)->where('bid', $bid)->count();
            if ($currentCount >= $plan->max_activities) {
                return ['code' => 1, 'msg' => '已达到套餐活动数上限(' . $plan->max_activities . '个)'];
            }
        }

        Db::startTrans();
        try {
            // 复制活动基本信息
            $newActivity = new HdActivity();
            $newActivity->aid = $aid;
            $newActivity->bid = $bid;
            $newActivity->mdid = $source->mdid;
            $newActivity->title = $source->title . '（副本）';
            $newActivity->access_code = HdActivity::generateAccessCode();
            $newActivity->status = HdActivity::STATUS_NOT_STARTED;
            $newActivity->verifycode = substr(md5(uniqid()), 0, 6);
            $newActivity->screen_config = $source->screen_config;
            $newActivity->createtime = time();
            $newActivity->save();

            // 复制功能配置
            $features = HdActivityFeature::where('activity_id', $sourceId)->select();
            foreach ($features as $feat) {
                HdActivityFeature::create([
                    'aid'          => $aid,
                    'bid'          => $bid,
                    'activity_id'  => $newActivity->id,
                    'feature_code' => $feat->feature_code,
                    'enabled'      => $feat->enabled,
                    'config'       => $feat->config,
                    'sort'         => $feat->sort,
                ]);
            }

            // 复制奖品配置
            $prizes = HdPrize::where('activity_id', $sourceId)->select();
            foreach ($prizes as $prize) {
                $newPrize = new HdPrize();
                $newPrize->aid = $aid;
                $newPrize->bid = $bid;
                $newPrize->activity_id = $newActivity->id;
                $newPrize->name = $prize->name;
                $newPrize->level = $prize->level;
                $newPrize->total = $prize->total;
                $newPrize->remain = $prize->total; // 重置剩余数量
                $newPrize->image = $prize->image;
                $newPrize->createtime = time();
                $newPrize->save();
            }

            Db::commit();

            return [
                'code' => 0,
                'msg'  => '克隆成功',
                'data' => [
                    'id'          => $newActivity->id,
                    'access_code' => $newActivity->access_code,
                    'url'         => 'https://wxhd.eivie.cn/s/' . $newActivity->access_code,
                ],
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('克隆活动失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }
}

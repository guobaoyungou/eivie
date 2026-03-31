<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdWallMessage;
use app\model\hd\HdDanmuConfig;

/**
 * 大屏互动 - 弹幕互动服务
 * 功能：消息设置、上墙设置、弹幕设置、消息列表、发布公告
 */
class HdWallService
{
    // ========================================================
    // 上墙消息设置
    // ========================================================

    /**
     * 获取上墙功能设置（wall feature config）
     */
    public function getWallConfig(int $aid, int $bid, int $activityId): array
    {
        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'wall')->find();

        $config = $feature ? ($feature->config ?: []) : [];

        return [
            'code' => 0,
            'data' => [
                'enabled'       => $feature ? $feature->enabled : 0,
                'need_approve'  => $config['need_approve'] ?? 1,
                'allow_image'   => $config['allow_image'] ?? 1,
                'max_length'    => $config['max_length'] ?? 200,
                'sensitive_words' => $config['sensitive_words'] ?? '',
            ],
        ];
    }

    /**
     * 更新上墙功能设置
     */
    public function updateWallConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'wall')->find();

        if (!$feature) {
            $feature = new HdActivityFeature();
            $feature->aid = $aid;
            $feature->bid = $bid;
            $feature->activity_id = $activityId;
            $feature->feature_code = 'wall';
            $feature->sort = 3;
        }

        if (isset($data['enabled'])) $feature->enabled = (int)$data['enabled'];

        $config = $feature->config ?: [];
        $allowedKeys = ['need_approve', 'allow_image', 'max_length', 'sensitive_words'];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) $config[$key] = $data[$key];
        }
        $feature->config = $config;
        $feature->save();

        return ['code' => 0, 'msg' => '上墙设置已更新'];
    }

    // ========================================================
    // 弹幕设置
    // ========================================================

    /**
     * 获取弹幕配置
     */
    public function getDanmuConfig(int $aid, int $bid, int $activityId): array
    {
        $config = HdDanmuConfig::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->find();
        return ['code' => 0, 'data' => $config ? $config->toArray() : null];
    }

    /**
     * 更新弹幕配置
     */
    public function updateDanmuConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $danmu = HdDanmuConfig::where('activity_id', $activityId)->find();
        if (!$danmu) {
            $danmu = new HdDanmuConfig();
            $danmu->aid = $aid;
            $danmu->bid = $bid;
            $danmu->activity_id = $activityId;
            $danmu->createtime = time();
        }

        if (isset($data['speed'])) $danmu->speed = (int)$data['speed'];
        if (isset($data['font_size'])) $danmu->font_size = (int)$data['font_size'];
        if (isset($data['opacity'])) $danmu->opacity = (float)$data['opacity'];
        if (isset($data['config'])) $danmu->config = $data['config'];
        $danmu->save();

        return ['code' => 0, 'msg' => '弹幕配置已更新'];
    }

    // ========================================================
    // 消息列表管理
    // ========================================================

    /**
     * 消息列表（含审核管理）
     */
    public function getMessages(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [['aid', '=', $aid], ['bid', '=', $bid], ['activity_id', '=', $activityId]];

        if (isset($params['is_approved']) && $params['is_approved'] !== '') {
            $where[] = ['is_approved', '=', (int)$params['is_approved']];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['nickname|content', 'like', '%' . $params['keyword'] . '%'];
        }
        if (!empty($params['type'])) {
            $where[] = ['type', '=', (int)$params['type']];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdWallMessage::where($where)->page($page, $limit)
            ->order('id desc')->select()->toArray();
        $count = HdWallMessage::where($where)->count();

        // 待审核数量
        $pendingCount = HdWallMessage::where('activity_id', $activityId)
            ->where('is_approved', HdWallMessage::APPROVED_PENDING)->count();

        return [
            'code' => 0,
            'data' => [
                'list'          => $list,
                'count'         => $count,
                'pending_count' => $pendingCount,
            ],
        ];
    }

    /**
     * 审核消息
     */
    public function approveMessage(int $aid, int $bid, int $activityId, int $id, int $status): array
    {
        $msg = HdWallMessage::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$msg) {
            return ['code' => 1, 'msg' => '消息不存在'];
        }

        $msg->is_approved = $status;
        $msg->save();

        return ['code' => 0, 'msg' => $status == 1 ? '已通过' : '已拒绝'];
    }

    /**
     * 批量审核消息
     */
    public function batchApprove(int $aid, int $bid, int $activityId, array $ids, int $status): array
    {
        HdWallMessage::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->whereIn('id', $ids)
            ->update(['is_approved' => $status]);

        return ['code' => 0, 'msg' => '批量操作成功'];
    }

    /**
     * 删除消息
     */
    public function deleteMessage(int $aid, int $bid, int $activityId, int $id): array
    {
        $msg = HdWallMessage::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$msg) {
            return ['code' => 1, 'msg' => '消息不存在'];
        }
        $msg->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 消息置顶/取消置顶
     */
    public function toggleTop(int $aid, int $bid, int $activityId, int $id): array
    {
        $msg = HdWallMessage::where('aid', $aid)->where('bid', $bid)
            ->where('activity_id', $activityId)->where('id', $id)->find();
        if (!$msg) {
            return ['code' => 1, 'msg' => '消息不存在'];
        }

        $msg->is_top = $msg->is_top ? 0 : 1;
        $msg->save();

        return ['code' => 0, 'msg' => $msg->is_top ? '已置顶' : '已取消置顶'];
    }

    // ========================================================
    // 发布公告
    // ========================================================

    /**
     * 发布公告（管理员消息）
     */
    public function publishNotice(int $aid, int $bid, int $activityId, array $data): array
    {
        $content = trim($data['content'] ?? '');
        if (empty($content)) {
            return ['code' => 1, 'msg' => '公告内容不能为空'];
        }

        $msg = new HdWallMessage();
        $msg->aid = $aid;
        $msg->bid = $bid;
        $msg->activity_id = $activityId;
        $msg->participant_id = 0;
        $msg->openid = 'admin';
        $msg->nickname = '管理员';
        $msg->avatar = '';
        $msg->content = $content;
        $msg->imgurl = $data['imgurl'] ?? '';
        $msg->type = 4; // 公告类型
        $msg->is_approved = 1;
        $msg->is_top = (int)($data['is_top'] ?? 1);
        $msg->createtime = time();
        $msg->save();

        return ['code' => 0, 'msg' => '公告已发布'];
    }
}

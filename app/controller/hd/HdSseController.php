<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\model\hd\HdActivity;
use think\facade\Db;
use think\facade\Cache;

/**
 * 大屏互动 - SSE 实时推送控制器
 * 使用 Server-Sent Events 替代轮询，实现低延迟数据推送
 * 频道：sign/wall/danmu/vote/lottery/shake
 */
class HdSseController extends HdBaseController
{
    /**
     * SSE 事件流端点
     * GET /api/hd/screen/:access_code/sse?channels=sign,wall,danmu,vote
     */
    public function stream(string $access_code)
    {
        // 验证活动
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return response('data: ' . json_encode(['type' => 'error', 'msg' => '活动不存在']) . "\n\n", 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
            ]);
        }

        $activityId = $activity->id;
        $channels = explode(',', input('get.channels', 'sign,wall,danmu,vote'));
        $channels = array_intersect($channels, ['sign', 'wall', 'danmu', 'vote', 'lottery', 'shake', 'notice']);

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('Access-Control-Allow-Origin: *');

        // 禁用输出缓冲
        if (ob_get_level()) ob_end_clean();
        set_time_limit(0);

        // 初始版本号（从缓存获取）
        $versions = [];
        foreach ($channels as $ch) {
            $versions[$ch] = (int)Cache::get("hd_sse_v:{$activityId}:{$ch}", 0);
        }

        // 发送初始连接确认
        echo "event: connected\n";
        echo 'data: ' . json_encode(['channels' => $channels, 'activity_id' => $activityId]) . "\n\n";
        if (function_exists('flush')) flush();

        $maxTime = 25; // 最大运行25秒，让Nginx不超时
        $startTime = time();
        $heartbeatInterval = 8;
        $pollInterval = 1; // 每秒检查一次
        $lastHeartbeat = time();

        while (time() - $startTime < $maxTime) {
            if (connection_aborted()) break;

            $hasData = false;
            foreach ($channels as $ch) {
                $currentV = (int)Cache::get("hd_sse_v:{$activityId}:{$ch}", 0);
                if ($currentV > $versions[$ch]) {
                    $versions[$ch] = $currentV;
                    $data = $this->getChannelData($activityId, $ch, $access_code);
                    if ($data) {
                        echo "event: {$ch}\n";
                        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
                        $hasData = true;
                    }
                }
            }

            // 心跳
            if (time() - $lastHeartbeat >= $heartbeatInterval) {
                echo ": heartbeat " . time() . "\n\n";
                $lastHeartbeat = time();
            }

            if ($hasData || time() - $lastHeartbeat >= $heartbeatInterval) {
                if (function_exists('flush')) flush();
            }

            usleep($pollInterval * 1000000);
        }

        // 告知客户端重连
        echo "event: reconnect\n";
        echo "data: {}\n\n";
        if (function_exists('flush')) flush();

        exit;
    }

    /**
     * 获取频道最新数据
     */
    private function getChannelData(int $activityId, string $channel, string $accessCode): ?array
    {
        switch ($channel) {
            case 'sign':
                return $this->getSignData($activityId);
            case 'wall':
                return $this->getWallData($activityId);
            case 'danmu':
                return $this->getDanmuData($activityId);
            case 'vote':
                return $this->getVoteData($activityId);
            case 'lottery':
                return $this->getLotteryData($activityId);
            case 'shake':
                return $this->getShakeData($activityId);
            case 'notice':
                return $this->getNoticeData($activityId);
            default:
                return null;
        }
    }

    private function getSignData(int $activityId): array
    {
        // 最新20个签到
        $list = Db::name('hd_sign_participant')
            ->where('activity_id', $activityId)
            ->order('id desc')
            ->limit(20)
            ->field('id,nickname,avatar,createtime')
            ->select()->toArray();

        $total = Db::name('hd_sign_participant')
            ->where('activity_id', $activityId)->count();

        return ['list' => array_reverse($list), 'total' => $total];
    }

    private function getWallData(int $activityId): array
    {
        $list = Db::name('hd_wall_message')
            ->where('activity_id', $activityId)
            ->where('status', 1)
            ->order('is_topped desc, id desc')
            ->limit(30)
            ->select()->toArray();

        return ['list' => $list];
    }

    private function getDanmuData(int $activityId): array
    {
        // 最近5秒内的弹幕
        $since = time() - 5;
        $list = Db::name('hd_wall_message')
            ->where('activity_id', $activityId)
            ->where('msg_type', 'danmu')
            ->where('createtime', '>=', $since)
            ->order('id desc')
            ->limit(20)
            ->field('id,nickname,content,color,createtime')
            ->select()->toArray();

        return ['list' => array_reverse($list)];
    }

    private function getVoteData(int $activityId): array
    {
        $items = Db::name('hd_vote_item')
            ->where('activity_id', $activityId)
            ->order('vote_count desc')
            ->select()->toArray();

        $totalVotes = 0;
        foreach ($items as $item) {
            $totalVotes += (int)($item['vote_count'] ?? 0);
        }

        return ['items' => $items, 'total_votes' => $totalVotes];
    }

    private function getLotteryData(int $activityId): array
    {
        $result = Cache::get("hd_lottery_result:{$activityId}");
        return $result ? (is_string($result) ? json_decode($result, true) : $result) : ['status' => 'idle'];
    }

    private function getShakeData(int $activityId): array
    {
        $ranking = Db::name('hd_shake_score')
            ->where('activity_id', $activityId)
            ->order('score desc')
            ->limit(20)
            ->select()->toArray();

        return ['ranking' => $ranking];
    }

    private function getNoticeData(int $activityId): array
    {
        $notice = Cache::get("hd_notice:{$activityId}");
        return $notice ? (is_string($notice) ? json_decode($notice, true) : $notice) : ['content' => ''];
    }

    /**
     * 触发 SSE 版本号更新（由其他服务调用）
     * 当数据变化时调用此方法，使 SSE 连接推送新数据
     */
    public static function notifyChannel(int $activityId, string $channel): void
    {
        $key = "hd_sse_v:{$activityId}:{$channel}";
        $v = (int)Cache::get($key, 0);
        Cache::set($key, $v + 1, 300);
    }
}

<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\model\hd\HdActivity;
use app\model\hd\HdParticipant;
use think\facade\Db;

/**
 * 大屏互动 - 模块代理控制器
 * 将老系统 Modules/module.php 的 AJAX 调用代理到新系统
 * 
 * 老系统模块入口：/Modules/module.php?m=lottery&c=front&a=ajaxGetWinners
 * 新系统代理路由：/s/{access_code}/module/{m}/{c}/{a}
 */
class HdModuleProxyController extends HdBaseController
{
    /**
     * 模块AJAX代理
     * GET|POST /s/{access_code}/module/{m}/{c}/{a}
     */
    public function proxy(string $access_code, string $m, string $c, string $a)
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'message' => '活动不存在']);
        }

        // 安全检查：只允许特定模块
        $allowedModules = ['lottery', 'game', 'prize'];
        if (!in_array(strtolower($m), $allowedModules)) {
            return json(['code' => -1, 'message' => '不支持的模块']);
        }

        // 路由到对应的处理方法
        $handler = strtolower($m) . '_' . strtolower($c) . '_' . strtolower($a);
        $handlerMap = [
            // 抽奖模块
            'lottery_front_ajaxgetwinners'        => 'lotteryGetWinners',
            'lottery_front_ajaxgetlotteryresult'  => 'lotteryGetResult',
            'lottery_front_ajaxgettempusers'      => 'lotteryGetTempUsers',
            'lottery_front_ajaxgettempusersinfo'  => 'lotteryGetTempUsersInfo',
            'lottery_front_remove_lottery_recode' => 'lotteryRemoveRecord',
            'lottery_front_lottery_reset'         => 'lotteryReset',
            'lottery_front_prize_ajax'            => 'lotteryPrizeAjax',
            'lottery_front_prizeajax'             => 'lotteryPrizeAjax',
            'lottery_front_prize_info'            => 'lotteryPrizeInfo',
            'lottery_front_prizeinfo'             => 'lotteryPrizeInfo',
            'lottery_front_index'                 => 'lotteryIndex',
            // 游戏模块
            'game_front_ajaxgetdata'              => 'gameGetData',
            'game_front_ajaxpostdata'             => 'gamePostData',
            'game_front_gameresult'               => 'gameResult',
            'game_front_index'                    => 'gameIndex',
        ];

        $method = $handlerMap[$handler] ?? null;
        if ($method && method_exists($this, $method)) {
            return $this->$method($activity);
        }

        return json(['code' => -1, 'message' => '不支持的操作: ' . $a]);
    }

    // ============================================================
    // 抽奖模块处理方法
    // ============================================================

    private function lotteryGetWinners(HdActivity $activity)
    {
        $prizeId = (int)input('post.prizeid', input('get.prizeid', 0));
        if ($prizeId <= 0) {
            return json(['code' => -1, 'message' => '数据格式错误']);
        }

        $winners = [];
        $allConfigs = Db::name('hd_lottery_config')
            ->where('activity_id', $activity->id)
            ->where('prize_id', $prizeId)
            ->where('status', 2)
            ->select()->toArray();

        foreach ($allConfigs as $cfg) {
            $ws = is_string($cfg['winners']) ? json_decode($cfg['winners'], true) : ($cfg['winners'] ?: []);
            foreach ($ws as $w) {
                $winners[] = ['nick_name' => $w['nickname'] ?? '', 'avatar' => $w['avatar'] ?? ''];
            }
        }

        $participantCount = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)->count();

        return json([
            'code' => 1, 'message' => '',
            'data' => ['winners' => array_reverse($winners), 'participants' => $participantCount],
        ]);
    }

    private function lotteryGetResult(HdActivity $activity)
    {
        $num = (int)input('post.num', input('get.num', 1));
        $prizeId = (int)input('post.prizeid', input('get.prizeid', 0));
        if ($prizeId <= 0) {
            return json(['code' => -1, 'message' => '数据格式错误']);
        }

        // 获取已中奖用户ID
        $existingWinnerIds = [];
        $allConfigs = Db::name('hd_lottery_config')
            ->where('activity_id', $activity->id)->where('status', 2)
            ->select()->toArray();
        foreach ($allConfigs as $cfg) {
            $ws = is_string($cfg['winners']) ? json_decode($cfg['winners'], true) : ($cfg['winners'] ?: []);
            foreach ($ws as $w) { $existingWinnerIds[] = $w['id'] ?? 0; }
        }

        $query = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED);
        if (!empty($existingWinnerIds)) {
            $query->whereNotIn('id', $existingWinnerIds);
        }
        $candidates = $query->field('id,nickname,avatar,openid')->select()->toArray();

        if (empty($candidates)) {
            return json(['code' => -2, 'message' => '没有人可以参与抽奖了']);
        }

        $num = min($num, count($candidates));
        $winnerKeys = (array)array_rand($candidates, max(1, $num));

        $winnersData = [];
        $winnersForSave = [];
        foreach ($winnerKeys as $key) {
            $c = $candidates[$key];
            $winnersData[] = ['nick_name' => $c['nickname'], 'avatar' => $c['avatar'], 'bd_data' => ['bd_mqwyk' => '', 'mobile' => '']];
            $winnersForSave[] = ['id' => $c['id'], 'nickname' => $c['nickname'], 'avatar' => $c['avatar'], 'openid' => $c['openid'] ?? ''];
        }

        // 保存中奖结果
        $lotteryConfig = Db::name('hd_lottery_config')
            ->where('activity_id', $activity->id)->where('prize_id', $prizeId)
            ->where('status', 1)->order('id asc')->find();
        if ($lotteryConfig) {
            $existing = is_string($lotteryConfig['winners']) ? json_decode($lotteryConfig['winners'], true) : ($lotteryConfig['winners'] ?: []);
            $merged = array_merge($existing ?: [], $winnersForSave);
            Db::name('hd_lottery_config')->where('id', $lotteryConfig['id'])
                ->update(['winners' => json_encode($merged), 'status' => 2]);
            Db::name('hd_prize')->where('id', $prizeId)->inc('used_num', count($winnersForSave))->update();
        }

        return json(['code' => 1, 'message' => '', 'data' => $winnersData]);
    }

    private function lotteryGetTempUsers(HdActivity $activity)
    {
        $users = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->field('id,avatar')->orderRaw('RAND()')->limit(30)->select()->toArray();
        return json(['code' => 1, 'message' => '', 'data' => $users]);
    }

    private function lotteryGetTempUsersInfo(HdActivity $activity)
    {
        $num = (int)input('post.num', 30);
        $users = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->field('id,avatar,nickname')->orderRaw('RAND()')->limit($num)->select()->toArray();
        return json(['code' => 1, 'message' => '', 'data' => $users]);
    }

    private function lotteryRemoveRecord(HdActivity $activity)
    {
        return json(['code' => 1, 'message' => '']);
    }

    private function lotteryReset(HdActivity $activity)
    {
        $configId = (int)input('post.activityid', 0);
        $prizeId = (int)input('post.prizeid', 0);
        if ($configId > 0 && $prizeId > 0) {
            Db::name('hd_lottery_config')->where('activity_id', $activity->id)
                ->where('prize_id', $prizeId)->update(['winners' => null, 'status' => 1]);
        }
        return json(['code' => 1, 'message' => '']);
    }

    private function lotteryPrizeAjax(HdActivity $activity)
    {
        $prizes = Db::name('hd_prize')->where('activity_id', $activity->id)
            ->order('sort asc, id asc')->select()->toArray();
        $formatted = [];
        foreach ($prizes as $p) {
            $formatted[] = [
                'id' => $p['id'], 'prizename' => $p['name'], 'num' => $p['total_num'],
                'leftnum' => max(0, $p['total_num'] - $p['used_num']), 'freezenum' => 0,
                'formatedtext' => ['text' => $p['image'] ?: '/static/hd/themes/meepo/assets/images/defaultaward.jpg'],
            ];
        }
        $joinNum = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)->count();
        return json(['code' => 1, 'message' => '', 'data' => ['prizes' => $formatted, 'joinNum' => $joinNum]]);
    }

    private function lotteryPrizeInfo(HdActivity $activity)
    {
        $prizeId = (int)input('post.prizeid', 0);
        $allConfigs = Db::name('hd_lottery_config')->where('activity_id', $activity->id)
            ->where('prize_id', $prizeId)->where('status', 2)->select()->toArray();
        $winnerData = [];
        foreach ($allConfigs as $cfg) {
            $ws = is_string($cfg['winners']) ? json_decode($cfg['winners'], true) : ($cfg['winners'] ?: []);
            foreach ($ws as $w) {
                $winnerData[] = ['id' => $w['id'] ?? 0, 'avatar' => $w['avatar'] ?? '', 'nick_name' => $w['nickname'] ?? '', 'openid' => $w['openid'] ?? '', 'bd_mqwyk' => '', 'mobile' => ''];
            }
        }
        $num = HdParticipant::where('activity_id', $activity->id)->where('flag', HdParticipant::FLAG_SIGNED)->count();
        return json(['code' => 1, 'message' => '', 'data' => ['winners' => $winnerData, 'num' => $num]]);
    }

    private function lotteryIndex(HdActivity $activity)
    {
        return json(['code' => 1, 'message' => '请通过大屏界面访问抽奖功能']);
    }

    // ============================================================
    // 游戏模块处理方法
    // ============================================================

    private function gameGetData(HdActivity $activity)
    {
        $config = Db::name('hd_game_config')->where('activity_id', $activity->id)->order('id desc')->find();
        if (!$config) { return json(['code' => -1]); }

        $result = ['code' => 1, 'config' => ['id' => $config['id'], 'status' => $config['status']]];

        if ($config['status'] == 1) {
            $users = HdParticipant::where('activity_id', $activity->id)->where('flag', HdParticipant::FLAG_SIGNED)
                ->field('id,nickname,avatar')->order('signorder desc')->limit(50)->select()->toArray();
            $result['users'] = ['num' => count($users), 'userlist' => $users];
        } elseif ($config['status'] == 2) {
            $topUsers = Db::name('hd_game_record')->where('game_config_id', $config['id'])
                ->order('score desc')->limit(10)->select()->toArray();
            $result['users'] = array_map(function($u) {
                return ['avatar' => $u['avatar'] ?? '', 'nickname' => $u['nickname'] ?? '', 'point' => $u['score'] ?? 0];
            }, $topUsers);
        } elseif ($config['status'] == 3) {
            $topUsers = Db::name('hd_game_record')->where('game_config_id', $config['id'])
                ->order('score desc')->limit($config['max_winners'] ?: 10)->select()->toArray();
            $result['users'] = ['num' => $config['max_winners'], 'users' => $topUsers];
        }
        return json($result);
    }

    private function gamePostData(HdActivity $activity)
    {
        $action = input('post.action', '');
        $config = Db::name('hd_game_config')->where('activity_id', $activity->id)->order('id desc')->find();
        if (!$config) { return json(['code' => -1, 'message' => '游戏未配置']); }

        switch ($action) {
            case 'start':
                if ($config['status'] != 1) { return json(['code' => -1, 'message' => '游戏已开始']); }
                Db::name('hd_game_config')->where('id', $config['id'])->update(['status' => 2]);
                return json(['code' => 1, 'data' => $config]);
            case 'stop':
                if ($config['status'] == 3) { return json(['code' => -1, 'message' => '游戏已结束']); }
                Db::name('hd_game_config')->where('id', $config['id'])->update(['status' => 3]);
                return json(['code' => 1, 'message' => '游戏结束']);
            case 'reset':
                Db::name('hd_game_config')->where('id', $config['id'])->update(['status' => 1]);
                Db::name('hd_game_record')->where('game_config_id', $config['id'])->delete();
                return json(['code' => 1, 'message' => '游戏已重置，3秒后会自动刷新页面']);
            default:
                return json(['code' => -1, 'message' => '格式错误']);
        }
    }

    private function gameResult(HdActivity $activity)
    {
        $config = Db::name('hd_game_config')->where('activity_id', $activity->id)->order('id desc')->find();
        if (!$config) { return json(['code' => -1]); }
        $winners = Db::name('hd_game_record')->where('game_config_id', $config['id'])
            ->order('score desc')->limit($config['max_winners'] ?: 10)->select()->toArray();
        return json(['code' => 1, 'data' => $winners]);
    }

    private function gameIndex(HdActivity $activity)
    {
        return json(['code' => 1, 'message' => '请通过大屏界面访问游戏功能']);
    }
}

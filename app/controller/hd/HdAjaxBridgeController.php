<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdScreenService;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdParticipant;
use app\model\hd\HdBusinessConfig;

/**
 * 大屏互动 - AJAX 桥接控制器
 * 接收老前端 Smarty 模板的 AJAX 请求格式，调用 HdScreenService，返回老前端期望的响应格式
 */
class HdAjaxBridgeController extends HdBaseController
{
    protected $screenService;

    protected function initialize()
    {
        $this->screenService = new HdScreenService();
    }

    /**
     * 签到人数
     * GET /s/{code}/ajax/countperson
     * 老格式响应: {code:1, data: count}
     */
    public function countPerson(string $access_code)
    {
        $result = $this->screenService->getSignList($access_code);
        if ($result['code'] !== 0) {
            return json(['code' => -1, 'data' => 0]);
        }
        return json(['code' => 1, 'data' => $result['data']['total'] ?? 0]);
    }

    /**
     * 签到列表
     * GET /s/{code}/ajax/get_sign
     * 老格式请求: ?mid=&num=
     * 老格式响应: {code:1, data:{omid, mid, users:[]}}
     */
    public function getSign(string $access_code)
    {
        $mid = (int)input('get.mid', 0);
        $num = (int)input('get.num', 50);

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'data' => ['omid' => $mid, 'mid' => $mid, 'users' => []]]);
        }

        $where = [
            ['activity_id', '=', $activity->id],
            ['flag', '=', HdParticipant::FLAG_SIGNED],
        ];
        if ($mid > 0) {
            $where[] = ['signorder', '>', $mid];
        }

        $list = HdParticipant::where($where)
            ->order('signorder asc')
            ->limit($num)
            ->field('id,nickname,avatar,signorder')
            ->select()
            ->toArray();

        $newMid = $mid;
        $users = [];
        foreach ($list as $item) {
            $users[] = [
                'nickname'  => $item['nickname'],
                'avatar'    => $item['avatar'],
                'signorder' => $item['signorder'],
            ];
            $newMid = max($newMid, (int)$item['signorder']);
        }

        return json([
            'code' => count($users) > 0 ? 1 : 0,
            'data' => [
                'omid'  => $mid,
                'mid'   => $newMid,
                'users' => $users,
            ],
        ]);
    }

    /**
     * 获取新签到用户（3D签到页用）
     * GET /s/{code}/ajax/get_new_sign
     * 老格式响应: {omid, mid, nickname, avatar, signorder}
     */
    public function getNewSign(string $access_code)
    {
        $mid = (int)input('get.mid', 0);

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['omid' => $mid, 'mid' => $mid]);
        }

        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->where('signorder', '>', $mid)
            ->order('signorder asc')
            ->field('id,nickname,avatar,signorder')
            ->find();

        if ($participant) {
            return json([
                'omid'      => $mid,
                'mid'       => $participant->signorder,
                'nickname'  => $participant->nickname,
                'avatar'    => $participant->avatar,
                'signorder' => $participant->signorder,
            ]);
        }

        return json(['omid' => $mid, 'mid' => $mid]);
    }

    /**
     * 新上墙消息
     * GET /s/{code}/ajax/new_msg
     * 老格式请求: ?shenhetime=
     * 老格式响应: {ret:0, data:[{nick_name, content, type, avatar, shenhetime}]}
     */
    public function newMsg(string $access_code)
    {
        $lastId = (int)input('get.shenhetime', 0);

        $result = $this->screenService->getWallMessages($access_code, ['last_id' => $lastId]);
        if ($result['code'] !== 0) {
            return json(['ret' => -1, 'data' => []]);
        }

        $list = $result['data']['list'] ?? [];
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'nick_name'  => $item['nickname'] ?? '',
                'content'    => $item['content'] ?? '',
                'type'       => $item['type'] ?? 1,
                'avatar'     => $item['avatar'] ?? '',
                'shenhetime' => $item['id'] ?? 0,
            ];
        }

        return json(['ret' => 0, 'data' => $data]);
    }

    /**
     * 投票操作
     * POST /s/{code}/ajax/vote
     */
    public function voteAction(string $access_code)
    {
        $data = [
            'vote_item_id'   => input('post.vote_item_id', input('get.vote_item_id', 0)),
            'openid'         => input('post.openid', input('get.openid', '')),
            'participant_id' => input('post.participant_id', 0),
        ];
        $result = $this->screenService->vote($access_code, $data);
        return json(['ret' => $result['code'] === 0 ? 1 : 0, 'msg' => $result['msg'] ?? '']);
    }

    /**
     * 投票状态更新
     * GET /s/{code}/ajax/vote_status
     */
    public function voteStatus(string $access_code)
    {
        // 老系统用来开启/关闭投票，这里简单返回成功
        return json(['code' => 1, 'msg' => 'ok']);
    }

    /**
     * 投票记录
     * GET /s/{code}/ajax/vote_record
     * 老格式响应: {type:2, data:[{voteitem, cnt, imagepath}], total:N}
     */
    public function voteRecord(string $access_code)
    {
        $result = $this->screenService->getVoteItems($access_code);
        if ($result['code'] !== 0) {
            return json(['type' => 0, 'data' => [], 'total' => 0]);
        }

        $items = $result['data']['items'] ?? [];
        $total = $result['data']['total_votes'] ?? 0;
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'voteitem'  => $item['title'] ?? '',
                'cnt'       => $item['vote_count'] ?? 0,
                'imagepath' => $item['image'] ?? '',
                'imageid'   => !empty($item['image']) ? 1 : 0,
            ];
        }

        // 按票数倒序
        usort($data, function ($a, $b) {
            return $b['cnt'] - $a['cnt'];
        });

        $showtype = 2; // 默认水平条形图
        $activity = HdActivity::where('access_code', $access_code)->find();
        if ($activity) {
            $sc = $activity->screen_config ?: [];
            $showtype = (int)($sc['voteshowtype'] ?? 2);
        }

        return json(['type' => $showtype, 'data' => $data, 'total' => $total]);
    }

    /**
     * 弹幕配置
     * GET /s/{code}/ajax/danmu_config
     */
    public function danmuConfig(string $access_code)
    {
        $result = $this->screenService->getScreenConfig($access_code);
        if ($result['code'] !== 0) {
            return json(['ret' => -1]);
        }
        return json(['ret' => 0, 'data' => $result['data'] ?? []]);
    }

    /**
     * 获取弹幕
     * GET /s/{code}/ajax/danmu
     */
    public function danmuGet(string $access_code)
    {
        $params = ['last_id' => input('get.last_id', input('get.shenhetime', ''))];
        $result = $this->screenService->getDanmuMessages($access_code, $params);
        if ($result['code'] !== 0) {
            return json(['ret' => -1, 'data' => []]);
        }
        return json(['ret' => 0, 'data' => $result['data']['list'] ?? []]);
    }

    /**
     * 红包操作
     * POST /s/{code}/ajax/redpacket
     * 老格式请求: ?action=start|end|redpacke_zjlist|...
     */
    public function redpacketAction(string $access_code)
    {
        $action = input('get.action', input('post.action', ''));

        switch ($action) {
            case 'start':
                return $this->redpacketStart($access_code);
            case 'end':
                return $this->redpacketEnd($access_code);
            case 'redpacke_zjlist':
            case 'redpacket_users':
            case 'redpacket_activity_screen_record':
                return $this->redpacketRecords($access_code, $action);
            case 'sendingredpacket':
                return $this->redpacketSending($access_code);
            default:
                return json(['code' => -1, 'msg' => '未知操作']);
        }
    }

    /**
     * 红包开始
     */
    private function redpacketStart(string $access_code): \think\Response
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'msg' => '活动不存在']);
        }

        // 开始当前红包轮次
        $round = Db::name('hd_redpacket_round')
            ->where('activity_id', $activity->id)
            ->where('status', 1)
            ->order('id asc')
            ->find();

        if ($round) {
            Db::name('hd_redpacket_round')
                ->where('id', $round['id'])
                ->update(['status' => 2, 'start_time' => time()]);
            return json(['code' => 1, 'msg' => '红包已开始']);
        }

        return json(['code' => -1, 'msg' => '没有待开始的红包轮次']);
    }

    /**
     * 红包结束
     */
    private function redpacketEnd(string $access_code): \think\Response
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1]);
        }

        Db::name('hd_redpacket_round')
            ->where('activity_id', $activity->id)
            ->where('status', 2)
            ->update(['status' => 3, 'end_time' => time()]);

        return json(['code' => 1, 'msg' => '红包已结束']);
    }

    /**
     * 红包记录
     */
    private function redpacketRecords(string $access_code, string $action): \think\Response
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'data' => []]);
        }

        $records = Db::name('hd_redpacket_user')
            ->where('activity_id', $activity->id)
            ->order('id desc')
            ->limit(100)
            ->select()
            ->toArray();

        return json(['code' => 1, 'data' => $records]);
    }

    /**
     * 红包发放中信息
     */
    private function redpacketSending(string $access_code): \think\Response
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1]);
        }

        $round = Db::name('hd_redpacket_round')
            ->where('activity_id', $activity->id)
            ->where('status', 'in', [2, 3])
            ->order('id desc')
            ->find();

        return json(['code' => 1, 'data' => $round ?: []]);
    }

    /**
     * 抽奖操作（幸运号码）
     * GET/POST /s/{code}/ajax/xyh
     * 老格式请求: ?action=getoldlucknum|getlucknum
     */
    public function xyhAction(string $access_code)
    {
        $action = input('get.action', input('post.action', ''));

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'msg' => '活动不存在']);
        }

        $sc = $activity->screen_config ?: [];
        $minnum = (int)($sc['xyh_minnum'] ?? 1);
        $maxnum = (int)($sc['xyh_maxnum'] ?? 999);

        if ($action === 'getoldlucknum') {
            // 获取已抽出的号码
            $lucknums = Db::name('hd_xyh_record')
                ->where('activity_id', $activity->id)
                ->order('id desc')
                ->column('lucknum');
            return json(['code' => count($lucknums) > 0 ? 1 : 0, 'lucknum_arr' => $lucknums]);
        }

        if ($action === 'getlucknum') {
            // 获取已用号码
            $usedNums = Db::name('hd_xyh_record')
                ->where('activity_id', $activity->id)
                ->column('lucknum');

            $allNums = range($minnum, $maxnum);
            $available = array_diff($allNums, $usedNums);

            if (empty($available)) {
                return json(['code' => 0, 'msg' => '号码已经抽完']);
            }

            $keys = array_keys($available);
            $lucknum = $available[$keys[array_rand($keys)]];

            Db::name('hd_xyh_record')->insert([
                'aid'         => $activity->aid,
                'bid'         => $activity->bid,
                'activity_id' => $activity->id,
                'lucknum'     => $lucknum,
                'createtime'  => time(),
            ]);

            return json(['code' => 1, 'lucknum' => $lucknum]);
        }

        return json(['code' => -1, 'msg' => '未知操作']);
    }

    /**
     * 幸运手机号操作
     * GET/POST /s/{code}/ajax/xysjh
     */
    public function xysjhAction(string $access_code)
    {
        $action = input('get.action', input('post.action', ''));

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'msg' => '活动不存在']);
        }

        if ($action === 'getoldxysjh') {
            $records = Db::name('hd_xysjh_record')
                ->where('activity_id', $activity->id)
                ->order('id desc')
                ->column('phone');
            return json(['code' => count($records) > 0 ? 1 : 0, 'lucknum_arr' => $records]);
        }

        if ($action === 'getxysjh') {
            // 获取已中奖手机号
            $usedPhones = Db::name('hd_xysjh_record')
                ->where('activity_id', $activity->id)
                ->column('phone');

            // 获取可选手机号
            $where = [
                ['activity_id', '=', $activity->id],
                ['flag', '=', HdParticipant::FLAG_SIGNED],
                ['phone', '<>', ''],
            ];
            if (!empty($usedPhones)) {
                $candidates = HdParticipant::where($where)
                    ->whereNotIn('phone', $usedPhones)
                    ->column('phone');
            } else {
                $candidates = HdParticipant::where($where)->column('phone');
            }

            if (empty($candidates)) {
                return json(['code' => 0, 'msg' => '手机号已抽完']);
            }

            $phone = $candidates[array_rand($candidates)];
            $maskedPhone = substr_replace($phone, '****', 3, 4);

            Db::name('hd_xysjh_record')->insert([
                'aid'         => $activity->aid,
                'bid'         => $activity->bid,
                'activity_id' => $activity->id,
                'phone'       => $phone,
                'createtime'  => time(),
            ]);

            return json(['code' => 1, 'lucknum' => $maskedPhone]);
        }

        return json(['code' => -1, 'msg' => '未知操作']);
    }

    /**
     * 抽奖操作（通用）
     * GET|POST /s/{code}/ajax/lottery
     * 支持老前端 action=ready|ok 格式
     */
    public function lotteryAction(string $access_code)
    {
        $action = input('get.action', input('post.action', ''));

        switch ($action) {
            case 'ready':
                // 准备抽奖：返回参与人员头像列表
                return $this->lotteryReady($access_code);
            case 'ok':
                // 执行抽奖：抽取中奖用户
                return $this->lotteryOk($access_code);
            default:
                // 通用抽奖（新API格式）
                $roundId = (int)input('post.round_id', input('get.round_id', 0));
                $result = $this->screenService->lotteryDraw($access_code, $roundId);
                return json($result);
        }
    }

    /**
     * 抽奖准备：获取参与用户头像列表
     */
    private function lotteryReady(string $access_code)
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'data' => []]);
        }

        $users = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->field('id,nickname,avatar')
            ->order('id desc')
            ->limit(30)
            ->select()
            ->toArray();

        return json(['code' => 1, 'data' => $users]);
    }

    /**
     * 执行抽奖（简单版，支持老前端直接URL调用）
     */
    private function lotteryOk(string $access_code)
    {
        $roundId = (int)input('post.round_id', input('get.round_id', input('get.roundno', 0)));
        $result = $this->screenService->lotteryDraw($access_code, $roundId);
        return json($result);
    }

    /**
     * 登录验证（大屏密码验证）
     * GET /s/{code}/ajax/login
     * 支持 screen_password_enabled/screen_password 字段，兼容旧版 verifycode
     */
    public function login(string $access_code)
    {
        $password = input('password', '');
        $activity = HdActivity::where('access_code', $access_code)->find();

        if (!$activity) {
            return json(['code' => 0, 'errno' => 1, 'msg' => '活动不存在']);
        }

        $screenConfig = $activity->screen_config ?: [];
        $passwordEnabled = (int)($screenConfig['screen_password_enabled'] ?? 1);

        // 密码功能已关闭，直接放行
        if ($passwordEnabled === 0) {
            return json(['code' => 1, 'errno' => 0, 'msg' => 'ok']);
        }

        // 优先使用 screen_password，其次使用旧版 verifycode，默认 eivie
        $correctPassword = $screenConfig['screen_password'] ?? ($activity->verifycode ?: 'eivie');

        if ($password === $correctPassword) {
            return json(['code' => 1, 'errno' => 0, 'msg' => 'ok']);
        }

        return json(['code' => 0, 'errno' => 1, 'msg' => '密码错误']);
    }

    /**
     * 摇一摇操作
     * GET/POST /s/{code}/ajax/shake
     * 支持老前端 action=joinuser|start|working|result|reset 格式
     */
    public function shakeAction(string $access_code)
    {
        $action = input('get.action', input('post.action', ''));

        switch ($action) {
            case 'joinuser':
                return $this->shakeJoinUser($access_code);
            case 'start':
                return $this->shakeStart($access_code);
            case 'working':
                return $this->shakeWorking($access_code);
            case 'result':
                return $this->shakeResult($access_code);
            case 'reset':
                return $this->shakeReset($access_code);
            default:
                return json($this->screenService->getShakeStatus($access_code));
        }
    }

    /**
     * 摇一摇 - 加入用户列表
     */
    private function shakeJoinUser(string $access_code)
    {
        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'users' => []]);
        }

        // 获取已签到用户列表（作为可参与游戏的用户）
        $users = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->field('id,nickname,avatar,signorder')
            ->order('signorder desc')
            ->limit(50)
            ->select()
            ->toArray();

        return json(['code' => 1, 'users' => $users, 'num' => count($users)]);
    }

    /**
     * 摇一摇 - 开始
     */
    private function shakeStart(string $access_code)
    {
        $roundno = (int)input('get.roundno', input('post.roundno', 0));
        $result = $this->screenService->getShakeStatus($access_code);
        $result['roundno'] = $roundno;
        return json($result);
    }

    /**
     * 摇一摇 - 进行中（轮询获取排行榜）
     */
    private function shakeWorking(string $access_code)
    {
        $result = $this->screenService->getShakeStatus($access_code);
        return json($result);
    }

    /**
     * 摇一摇 - 结果
     */
    private function shakeResult(string $access_code)
    {
        $result = $this->screenService->getShakeStatus($access_code);
        return json($result);
    }

    /**
     * 摇一摇 - 重置
     */
    private function shakeReset(string $access_code)
    {
        return json(['errno' => 0, 'code' => 1, 'msg' => 'ok']);
    }

    /**
     * 摇一摇结果页面（用于iframe跳转）
     * GET /s/{code}/ajax/shake_result
     */
    public function shakeResultPage(string $access_code)
    {
        $result = $this->screenService->getShakeStatus($access_code);
        return json($result);
    }

    /**
     * 设置背景音乐开关
     * GET /s/{code}/ajax/set_bgmusic
     * 老格式请求: ?bgmusicstatus=1&plugname=qdq
     */
    public function setBgmusic(string $access_code)
    {
        $bgmusicstatus = (int)input('get.bgmusicstatus', 1);
        $plugname = input('get.plugname', '');

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['ret' => -1]);
        }

        // 更新老系统的 weixin_music 表
        try {
            Db::connect('huodong')->table('weixin_music')
                ->where('plugname', $plugname)
                ->update(['bgmusicstatus' => $bgmusicstatus == 1 ? 1 : 2]);
            return json(['ret' => 1]);
        } catch (\Exception $e) {
            // 降级：更新新系统的 screen_config
            $sc = $activity->screen_config ?: [];
            $sc['bgmusicstatus'] = $bgmusicstatus;
            $activity->screen_config = $sc;
            $activity->save();
            return json(['ret' => 1]);
        }
    }

    /**
     * 获取新签到（签到动画用）
     * GET /s/{code}/ajax/get_new_qd
     * 老格式请求: ?mid=
     * 老格式响应: {omid, mid, avatar, qdnums, nick_name}
     */
    public function getNewQd(string $access_code)
    {
        $mid = (int)input('get.mid', 0);

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['omid' => $mid, 'mid' => $mid, 'avatar' => '', 'qdnums' => '', 'nick_name' => '']);
        }

        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->where('signorder', '>', $mid)
            ->order('signorder asc')
            ->field('id,nickname,avatar,signorder')
            ->find();

        if ($participant) {
            return json([
                'omid'      => $mid,
                'mid'       => $participant->signorder,
                'avatar'    => $participant->avatar,
                'qdnums'    => $participant->signorder,
                'nick_name' => $participant->nickname,
            ]);
        }

        return json(['omid' => $mid, 'mid' => $mid, 'avatar' => '', 'qdnums' => '', 'nick_name' => '']);
    }

    /**
     * 删除抽奖记录
     * POST /s/{code}/ajax/lottory_remove_user
     */
    public function lotteryRemoveUser(string $access_code)
    {
        $recordId = (int)input('post.record_id', 0);
        if ($recordId <= 0) {
            return json(['code' => -1, 'msg' => '参数错误']);
        }

        try {
            Db::name('hd_lottery_winner')
                ->where('id', $recordId)
                ->delete();
            return json(['code' => 1, 'msg' => 'ok']);
        } catch (\Exception $e) {
            return json(['code' => -1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 默认二维码（生成PNG图片）
     * GET /s/{code}/ajax/defaultqrcode?from=wall
     * 根据功能名生成对应手机端页面的二维码图片
     *
     * 当启用「强制关注公众号授权登录」时，生成微信带参数二维码（用户扫码关注后自动推送签到页）
     * 否则生成普通URL二维码
     */
    public function defaultQrcode(string $access_code)
    {
        $from = input('get.from', 'qdq');
        $s = (int)input('get.s', 10);
        $s = max($s, 10);

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            // 返回一个空的1x1图片
            header('Content-Type: image/png');
            $img = imagecreatetruecolor(1, 1);
            imagepng($img);
            imagedestroy($img);
            exit;
        }

        // === 判断是否启用强制关注公众号 ===
        $screenConfig = $activity->screen_config ?: [];
        $forceWxAuth = (int)($screenConfig['mobile_force_wx_auth'] ?? 1);

        if ($forceWxAuth) {
            // 尝试生成微信带参数二维码
            $wxQrImage = $this->getWxParametricQrCode($activity);
            if ($wxQrImage) {
                // 输出微信二维码图片
                header('Content-Type: image/jpg');
                echo $wxQrImage;
                exit;
            }
            // 微信二维码生成失败，降级使用普通URL二维码
            Log::warning('[HdQrCode] 微信带参数二维码生成失败，降级使用普通URL二维码, access_code=' . $access_code);
        }

        // === 普通URL二维码（默认行为） ===
        $baseUrl = request()->scheme() . '://' . request()->host();
        $mobileUrl = $baseUrl . '/s/' . $access_code;

        // 使用 huodong 下的 phpqrcode 库
        $qrlibPath = app()->getRootPath() . 'huodong/common/phpqrcode/qrlib.php';
        if (!class_exists('QRcode')) {
            require_once $qrlibPath;
        }

        // 直接输出PNG图片
        \QRcode::png($mobileUrl, false, QR_ECLEVEL_Q, $s, 2);
        exit;
    }

    /**
     * 生成微信带参数二维码
     * 通过微信API创建临时二维码，scene_str = access_code
     * 用户扫码关注后，微信推送事件到回调接口，自动推送签到页链接
     *
     * @return string|null 二维码图片二进制数据，失败返回null
     */
    private function getWxParametricQrCode(HdActivity $activity): ?string
    {
        $accessCode = $activity->access_code;

        // 先检查缓存（缓存二维码图片数据）
        $cacheKey = 'hd_wx_qr_img:' . $accessCode;
        $cachedImg = Cache::get($cacheKey);
        if ($cachedImg) {
            return $cachedImg;
        }

        // 获取微信公众号配置
        $wxConfig = $this->getActivityWxConfig($activity);
        if (!$wxConfig) {
            Log::warning('[HdQrCode] 无可用微信公众号配置');
            return null;
        }

        // 获取 access_token
        $accessToken = $this->getWxServerToken($wxConfig['appid'], $wxConfig['appsecret']);
        if (!$accessToken) {
            return null;
        }

        // 调用微信API创建带参数的临时二维码
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
        $postData = json_encode([
            'expire_seconds' => 2592000, // 30天有效
            'action_name'    => 'QR_STR_SCENE',
            'action_info'    => [
                'scene' => [
                    'scene_str' => $accessCode,
                ],
            ],
        ]);

        $result = $this->wxHttpPost($url, $postData);
        $data = json_decode($result, true);

        if (empty($data['ticket'])) {
            Log::error('[HdQrCode] 创建带参数二维码失败: ' . ($result ?: 'empty'));
            return null;
        }

        // 通过 ticket 获取二维码图片
        $qrImageUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($data['ticket']);
        $imageData = $this->wxHttpGet($qrImageUrl);

        if (empty($imageData) || strlen($imageData) < 100) {
            Log::error('[HdQrCode] 获取二维码图片失败');
            return null;
        }

        // 缓存图片数据（缓存29天，比二维码有效期少1天）
        Cache::set($cacheKey, $imageData, 2505600);

        Log::info('[HdQrCode] 成功生成微信带参数二维码, access_code=' . $accessCode);
        return $imageData;
    }

    /**
     * 获取活动关联的微信公众号配置
     * 优先级：商家自有公众号 → 平台公众号
     */
    private function getActivityWxConfig(HdActivity $activity): ?array
    {
        // 商家自有公众号
        $bizConfig = HdBusinessConfig::where('bid', $activity->bid)->find();
        if ($bizConfig && !empty($bizConfig->wxfw_appid) && !empty($bizConfig->wxfw_appsecret)) {
            return [
                'appid'     => $bizConfig->wxfw_appid,
                'appsecret' => $bizConfig->wxfw_appsecret,
            ];
        }

        // 平台公众号
        $platformMp = Db::name('admin_setapp_mp')->where('aid', $activity->aid)->find();
        if ($platformMp && !empty($platformMp['appid']) && !empty($platformMp['appsecret'])) {
            return [
                'appid'     => $platformMp['appid'],
                'appsecret' => $platformMp['appsecret'],
            ];
        }

        return null;
    }

    /**
     * 获取微信服务端 access_token（带缓存）
     */
    private function getWxServerToken(string $appid, string $appsecret): ?string
    {
        $cacheKey = 'hd_wx_access_token:' . $appid;
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential'
            . '&appid=' . $appid
            . '&secret=' . $appsecret;

        $result = $this->wxHttpGet($url);
        $data = json_decode($result, true);

        if (empty($data['access_token'])) {
            Log::error('[HdQrCode] 获取access_token失败: ' . ($result ?: 'empty'));
            return null;
        }

        $expiresIn = ($data['expires_in'] ?? 7200) - 100;
        Cache::set($cacheKey, $data['access_token'], $expiresIn);

        return $data['access_token'];
    }

    /**
     * HTTP GET（微信API专用）
     */
    private function wxHttpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?: '';
    }

    /**
     * HTTP POST（微信API专用）
     */
    private function wxHttpPost(string $url, string $data): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?: '';
    }

    /**
     * 保存二维码位置
     * POST /s/{code}/ajax/set_qrcodepos
     * 老格式请求: {w, h, x, y}
     * 老格式响应: {code:1, message:"保存成功", data:{w,h,x,y}}
     */
    public function setQrcodePos(string $access_code)
    {
        $w = (int)input('post.w', 1);
        $h = (int)input('post.h', 1);
        $x = (int)input('post.x', 1);
        $y = (int)input('post.y', 1);

        $data = ['w' => $w, 'h' => $h, 'x' => $x, 'y' => $y];

        $activity = HdActivity::where('access_code', $access_code)->find();
        if (!$activity) {
            return json(['code' => -1, 'message' => '活动不存在']);
        }

        // 将二维码位置保存到活动的 screen_config 中
        $screenConfig = $activity->screen_config ?: [];
        $screenConfig['qrcodepos'] = $data;
        $activity->screen_config = $screenConfig;
        $result = $activity->save();

        if ($result) {
            return json(['code' => 1, 'message' => '保存成功', 'data' => $data]);
        }
        return json(['code' => -1, 'message' => '保存失败']);
    }
}

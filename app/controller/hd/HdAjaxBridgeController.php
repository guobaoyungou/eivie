<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdScreenService;
use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdParticipant;

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
     * POST /s/{code}/ajax/lottery
     */
    public function lotteryAction(string $access_code)
    {
        $roundId = (int)input('post.round_id', input('get.round_id', 0));
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
     */
    public function shakeAction(string $access_code)
    {
        $action = input('get.action', input('post.action', ''));

        switch ($action) {
            case 'start':
                return json($this->screenService->getShakeStatus($access_code));
            case 'reset':
                return json(['errno' => 0, 'msg' => 'ok']);
            default:
                return json($this->screenService->getShakeStatus($access_code));
        }
    }

    /**
     * 默认二维码（生成PNG图片）
     * GET /s/{code}/ajax/defaultqrcode?from=wall
     * 根据功能名生成对应手机端页面的二维码图片
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

        // 新系统统一入口URL，手机端自动适配
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

<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\service\hd\HdScreenService;

/**
 * 大屏互动 - 大屏与互动 API 控制器
 * 大屏端和手机端通过 access_code 访问，无需商家登录态
 */
class HdScreenController extends HdBaseController
{
    protected $screenService;

    protected function initialize()
    {
        $this->screenService = new HdScreenService();
    }

    /**
     * 获取大屏配置
     * GET /api/hd/screen/:access_code/config
     */
    public function config(string $access_code)
    {
        $result = $this->screenService->getScreenConfig($access_code);
        return json($result);
    }

    /**
     * 获取签到列表
     * GET /api/hd/screen/:access_code/sign-list
     */
    public function signList(string $access_code)
    {
        $params = [
            'last_id' => input('get.last_id', ''),
        ];
        $result = $this->screenService->getSignList($access_code, $params);
        return json($result);
    }

    /**
     * 用户签到（支持扩展字段）
     * POST /api/hd/screen/:access_code/sign
     */
    public function sign(string $access_code)
    {
        // 优先从 Session 获取 openid
        $openid = session('hd_wx_openid') ?: input('post.openid', '');
        $data = [
            'openid'       => $openid,
            'nickname'     => session('hd_wx_nickname') ?: input('post.nickname', ''),
            'avatar'       => session('hd_wx_avatar') ?: input('post.avatar', ''),
            'signname'     => input('post.signname', ''),
            'phone'        => input('post.phone', ''),
            'company'      => input('post.company', ''),
            'position'     => input('post.position', ''),
            'employee_no'  => input('post.employee_no', ''),
            'sign_photo'   => input('post.sign_photo', ''),
            'custom_data'  => input('post.custom_data', ''),
            'sms_code'     => input('post.sms_code', ''),
            'mid'          => (int)(session('hd_wx_mid') ?: input('post.mid', 0)),
            'latitude'     => input('post.latitude', ''),
            'longitude'    => input('post.longitude', ''),
            'quick_message'=> input('post.quick_message', ''),
        ];
        $result = $this->screenService->sign($access_code, $data);
        return json($result);
    }

    /**
     * 发送签到短信验证码
     * POST /api/hd/screen/:access_code/sign-sms
     */
    public function sendSignSms(string $access_code)
    {
        $phone = input('post.phone', '');
        $result = $this->screenService->sendSignSmsCode($access_code, $phone);
        return json($result);
    }

    /**
     * 获取上墙消息
     * GET /api/hd/screen/:access_code/wall
     */
    public function wall(string $access_code)
    {
        $params = [
            'last_id' => input('get.last_id', ''),
        ];
        $result = $this->screenService->getWallMessages($access_code, $params);
        return json($result);
    }

    /**
     * 发送上墙消息
     * POST /api/hd/screen/:access_code/wall
     */
    public function sendWall(string $access_code)
    {
        $data = [
            'openid'         => input('post.openid', ''),
            'nickname'       => input('post.nickname', ''),
            'avatar'         => input('post.avatar', ''),
            'content'        => input('post.content', ''),
            'imgurl'         => input('post.imgurl', ''),
            'participant_id' => input('post.participant_id', 0),
        ];
        $result = $this->screenService->sendWallMessage($access_code, $data);
        return json($result);
    }

    /**
     * 执行抽奖
     * POST /api/hd/screen/:access_code/lottery/draw
     */
    public function lotteryDraw(string $access_code)
    {
        $roundId = (int)input('post.round_id', 0);
        $result = $this->screenService->lotteryDraw($access_code, $roundId);
        return json($result);
    }

    /**
     * 摇一摇状态
     * GET /api/hd/screen/:access_code/shake/status
     */
    public function shakeStatus(string $access_code)
    {
        $result = $this->screenService->getShakeStatus($access_code);
        return json($result);
    }

    /**
     * 提交摇一摇分数
     * POST /api/hd/screen/:access_code/shake/score
     */
    public function shakeScore(string $access_code)
    {
        $data = [
            'openid'         => input('post.openid', ''),
            'nickname'       => input('post.nickname', ''),
            'avatar'         => input('post.avatar', ''),
            'score'          => input('post.score', 0),
            'participant_id' => input('post.participant_id', 0),
        ];
        $result = $this->screenService->submitShakeScore($access_code, $data);
        return json($result);
    }

    /**
     * 抢红包
     * POST /api/hd/screen/:access_code/redpacket/grab
     */
    public function redpacketGrab(string $access_code)
    {
        $data = [
            'openid'         => input('post.openid', ''),
            'nickname'       => input('post.nickname', ''),
            'participant_id' => input('post.participant_id', 0),
        ];
        $result = $this->screenService->grabRedpacket($access_code, $data);
        return json($result);
    }

    /**
     * 投票
     * POST /api/hd/screen/:access_code/vote
     */
    public function vote(string $access_code)
    {
        $data = [
            'vote_item_id'   => input('post.vote_item_id', 0),
            'openid'         => input('post.openid', ''),
            'participant_id' => input('post.participant_id', 0),
        ];
        $result = $this->screenService->vote($access_code, $data);
        return json($result);
    }

    /**
     * 获取弹幕消息
     * GET /api/hd/screen/:access_code/danmu
     */
    public function danmu(string $access_code)
    {
        $params = [
            'last_id' => input('get.last_id', ''),
        ];
        $result = $this->screenService->getDanmuMessages($access_code, $params);
        return json($result);
    }

    /**
     * 发送弹幕
     * POST /api/hd/screen/:access_code/danmu
     */
    public function sendDanmu(string $access_code)
    {
        $data = [
            'openid'         => input('post.openid', ''),
            'nickname'       => input('post.nickname', ''),
            'content'        => input('post.content', ''),
            'color'          => input('post.color', '#ffffff'),
            'participant_id' => input('post.participant_id', 0),
        ];
        $result = $this->screenService->sendDanmu($access_code, $data);
        return json($result);
    }

    /**
     * 获取抽奖轮次列表
     * GET /api/hd/screen/:access_code/lottery/rounds
     */
    public function lotteryRounds(string $access_code)
    {
        $result = $this->screenService->getLotteryRounds($access_code);
        return json($result);
    }

    /**
     * 获取投票选项
     * GET /api/hd/screen/:access_code/vote/items
     */
    public function voteItems(string $access_code)
    {
        $result = $this->screenService->getVoteItems($access_code);
        return json($result);
    }

    /**
     * 获取相册照片
     * GET /api/hd/screen/:access_code/album/photos
     */
    public function albumPhotos(string $access_code)
    {
        $result = $this->screenService->getAlbumPhotos($access_code);
        return json($result);
    }

    /**
     * 获取开幕墙配置
     * GET /api/hd/screen/:access_code/theme/kaimu
     */
    public function kaimu(string $access_code)
    {
        $result = $this->screenService->getKaimuConfig($access_code);
        return json($result);
    }

    /**
     * 获取闭幕墙配置
     * GET /api/hd/screen/:access_code/theme/bimu
     */
    public function bimu(string $access_code)
    {
        $result = $this->screenService->getBimuConfig($access_code);
        return json($result);
    }

    // ========== 管理员 API ==========

    /**
     * 检查当前用户是否为管理员
     * GET /api/hd/screen/:access_code/admin/check
     */
    public function adminCheck(string $access_code)
    {
        $openid = session('hd_wx_openid') ?: input('get.openid', '');
        $result = $this->screenService->checkAdmin($access_code, $openid);
        return json($result);
    }

    /**
     * 管理员获取功能开关列表
     * GET /api/hd/screen/:access_code/admin/features
     */
    public function adminFeatures(string $access_code)
    {
        $openid = session('hd_wx_openid') ?: input('get.openid', '');
        $result = $this->screenService->adminGetFeatures($access_code, $openid);
        return json($result);
    }

    /**
     * 管理员切换大屏功能
     * POST /api/hd/screen/:access_code/admin/feature-toggle
     */
    public function adminFeatureToggle(string $access_code)
    {
        $openid = session('hd_wx_openid') ?: input('post.openid', '');
        $data = [
            'feature_code' => input('post.feature_code', ''),
            'action'       => input('post.action', 'toggle'),
            'round_id'     => input('post.round_id', 0),
        ];
        $result = $this->screenService->adminFeatureToggle($access_code, $openid, $data);
        return json($result);
    }

    /**
     * 管理员触发抽奖
     * POST /api/hd/screen/:access_code/admin/lottery-draw
     */
    public function adminLotteryDraw(string $access_code)
    {
        $openid = session('hd_wx_openid') ?: input('post.openid', '');
        // 权限校验
        $checkResult = $this->screenService->checkAdmin($access_code, $openid);
        if ($checkResult['code'] !== 0 || empty($checkResult['data']['is_admin'])) {
            return json(['code' => 1, 'msg' => '无管理员权限']);
        }
        $roundId = (int)input('post.round_id', 0);
        $result = $this->screenService->lotteryDraw($access_code, $roundId);
        return json($result);
    }

    // ========== 核销员 API（预留） ==========

    /**
     * 检查当前用户是否有核销权限
     * GET /api/hd/screen/:access_code/verify/check
     */
    public function verifyCheck(string $access_code)
    {
        $openid = session('hd_wx_openid') ?: input('get.openid', '');
        $result = $this->screenService->checkVerifier($access_code, $openid);
        return json($result);
    }

    /**
     * 获取待核销订单列表（预留）
     * GET /api/hd/screen/:access_code/verify/orders
     */
    public function verifyOrders(string $access_code)
    {
        return json(['code' => 0, 'msg' => '功能开发中，敬请期待', 'data' => ['list' => []]]);
    }

    /**
     * 执行核销操作（预留）
     * POST /api/hd/screen/:access_code/verify/order
     */
    public function verifyOrder(string $access_code)
    {
        return json(['code' => 1, 'msg' => '功能开发中，敬请期待']);
    }
}

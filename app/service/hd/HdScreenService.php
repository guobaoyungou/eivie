<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdParticipant;
use app\model\hd\HdWallMessage;
use app\model\hd\HdLotteryConfig;
use app\model\hd\HdPrize;
use app\model\hd\HdShakeRecord;
use app\model\hd\HdRedpacketRound;
use app\model\hd\HdRedpacketUser;
use app\model\hd\HdVoteItem;
use app\model\hd\HdVoteRecord;
use app\service\hd\HdContentFilterService;
use app\controller\hd\HdSseController;

/**
 * 大屏互动 - 大屏与互动服务
 */
class HdScreenService
{
    /**
     * 获取大屏配置（通过 access_code）
     */
    public function getScreenConfig(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $features = HdActivityFeature::where('activity_id', $activity->id)
            ->where('enabled', 1)
            ->order('sort asc')
            ->select()
            ->toArray();

        foreach ($features as &$f) {
            $f['feature_name'] = HdActivityService::ALL_FEATURES[$f['feature_code']] ?? $f['feature_code'];
        }
        unset($f);

        return [
            'code' => 0,
            'data' => [
                'activity'      => [
                    'id'            => $activity->id,
                    'title'         => $activity->title,
                    'status'        => $activity->status,
                    'verifycode'    => $activity->verifycode,
                    'screen_config' => $activity->screen_config,
                    'started_at'    => $activity->started_at,
                    'ended_at'      => $activity->ended_at,
                ],
                'features'      => $features,
                'qrcode_url'    => 'https://wxhd.eivie.cn/s/' . $accessCode,
            ],
        ];
    }

    /**
     * 获取签到列表
     */
    public function getSignList(string $accessCode, array $params = []): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $where = [
            ['activity_id', '=', $activity->id],
            ['flag', '=', HdParticipant::FLAG_SIGNED],
        ];

        // 支持增量拉取
        if (!empty($params['last_id'])) {
            $where[] = ['id', '>', (int)$params['last_id']];
        }

        $list = HdParticipant::where($where)
            ->order('signorder asc, id asc')
            ->limit(200)
            ->field('id,nickname,avatar,signname,signorder,createtime')
            ->select()
            ->toArray();

        $total = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)->count();

        return [
            'code' => 0,
            'data' => [
                'list'  => $list,
                'total' => $total,
            ],
        ];
    }

    /**
     * 用户签到（增强版：扩展字段 + 时间校验 + 人数上限 + 必填校验）
     */
    public function sign(string $accessCode, array $userData): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $openid = $userData['openid'] ?? '';
        if (empty($openid)) {
            return ['code' => 1, 'msg' => '缺少用户标识'];
        }

        $screenConfig = $activity->screen_config ?: [];

        // === 签到时间窗口校验 ===
        $startTime = $screenConfig['start_time'] ?? '';
        $endTime = $screenConfig['end_time'] ?? '';
        $now = time();
        if (!empty($startTime) && $now < strtotime($startTime)) {
            return ['code' => 1, 'msg' => '签到尚未开始，请耐心等待'];
        }
        if (!empty($endTime) && $now > strtotime($endTime)) {
            return ['code' => 1, 'msg' => '签到已结束'];
        }

        // === 签到人数上限校验 ===
        $maxPlayers = (int)($screenConfig['maxplayers'] ?? 0);
        if ($maxPlayers > 0) {
            $currentCount = HdParticipant::where('activity_id', $activity->id)
                ->where('flag', HdParticipant::FLAG_SIGNED)->count();
            if ($currentCount >= $maxPlayers) {
                return ['code' => 1, 'msg' => '活动人数已满'];
            }
        }

        // === 必填字段校验 ===
        if (!empty($screenConfig['require_name']) && empty($userData['signname'])) {
            return ['code' => 1, 'msg' => '姓名必须填写'];
        }
        if (!empty($screenConfig['require_phone'])) {
            $phone = $userData['phone'] ?? '';
            if (empty($phone)) {
                return ['code' => 1, 'msg' => '手机号必须填写'];
            }
            if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
                return ['code' => 1, 'msg' => '手机号格式不正确'];
            }
            // 短信验证码校验（当启用 require_phone_verify 时）
            if (!empty($screenConfig['require_phone_verify'])) {
                $smsCode = $userData['sms_code'] ?? '';
                if (empty($smsCode)) {
                    return ['code' => 1, 'msg' => '请输入短信验证码'];
                }
                $smsCacheKey = 'hd_sign_sms:' . $activity->id . ':' . $phone;
                $cachedCode = Cache::get($smsCacheKey);
                if (empty($cachedCode) || $cachedCode !== $smsCode) {
                    return ['code' => 1, 'msg' => '短信验证码错误或已过期'];
                }
                // 验证通过，清除验证码
                Cache::delete($smsCacheKey);
            }
        }
        if (!empty($screenConfig['require_company']) && empty($userData['company'])) {
            return ['code' => 1, 'msg' => '公司必须填写'];
        }
        if (!empty($screenConfig['require_position']) && empty($userData['position'])) {
            return ['code' => 1, 'msg' => '职位必须填写'];
        }
        if (!empty($screenConfig['require_employee_no']) && empty($userData['employee_no'])) {
            return ['code' => 1, 'msg' => '员工号必须填写'];
        }
        if (!empty($screenConfig['require_photo']) && empty($userData['sign_photo'])) {
            return ['code' => 1, 'msg' => '签到照片必须上传'];
        }

        // === 自定义字段必填校验 ===
        if (!empty($screenConfig['show_custom_fields']) && !empty($screenConfig['sign_custom_fields'])) {
            $customData = $userData['custom_data'] ?? [];
            if (is_string($customData)) {
                $customData = json_decode($customData, true) ?: [];
            }
            foreach ($screenConfig['sign_custom_fields'] as $field) {
                if (!empty($field['is_required']) && empty($customData[$field['field_name'] ?? ''])) {
                    $fname = $field['field_name'] ?? '自定义字段';
                    return ['code' => 1, 'msg' => $fname . '必须填写'];
                }
            }
        }

        // === 地点限定校验 ===
        if (!empty($screenConfig['sign_location_enabled'])) {
            $actLat = (float)($screenConfig['sign_latitude'] ?? 0);
            $actLng = (float)($screenConfig['sign_longitude'] ?? 0);
            $radius = (int)($screenConfig['sign_radius'] ?? 1000);

            $userLat = $userData['latitude'] ?? '';
            $userLng = $userData['longitude'] ?? '';

            if ($userLat === '' || $userLng === '' || $userLat == 0 || $userLng == 0) {
                return ['code' => 1, 'msg' => '请允许获取您的位置信息后再签到'];
            }

            if ($actLat > 0 && $actLng > 0) {
                $distance = $this->calcDistance($actLat, $actLng, (float)$userLat, (float)$userLng);
                if ($distance > $radius) {
                    $radiusText = $radius >= 1000 ? ($radius / 1000) . '公里' : $radius . '米';
                    return ['code' => 1, 'msg' => '您不在活动签到范围内（' . $radiusText . '），当前距离约' . round($distance) . '米'];
                }
            }
        }

        // === 查找或创建参与者 ===
        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->find();

        if (!$participant) {
            $participant = new HdParticipant();
            $participant->aid = $activity->aid;
            $participant->bid = $activity->bid;
            $participant->activity_id = $activity->id;
            $participant->openid = $openid;
            $participant->nickname = $userData['nickname'] ?? '';
            $participant->avatar = $userData['avatar'] ?? '';
            $participant->mid = (int)($userData['mid'] ?? 0);
            $participant->createtime = time();
        }

        if ($participant->flag == HdParticipant::FLAG_SIGNED) {
            $signTime = $participant->createtime ? date('Y-m-d H:i:s', $participant->createtime) : '';
            return ['code' => 0, 'msg' => '您已签到', 'data' => [
                'signorder' => $participant->signorder,
                'sign_time' => $signTime,
                'nickname'  => $participant->nickname,
            ]];
        }

        // === 执行签到 ===
        $signOrder = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)->count() + 1;

        $participant->flag = HdParticipant::FLAG_SIGNED;
        $participant->signorder = $signOrder;
        $participant->signname = $userData['signname'] ?? '';
        $participant->phone = $userData['phone'] ?? '';
        $participant->company = $userData['company'] ?? '';
        $participant->position = $userData['position'] ?? '';
        $participant->employee_no = $userData['employee_no'] ?? '';
        $participant->sign_photo = $userData['sign_photo'] ?? '';
        // 自定义字段
        if (!empty($userData['custom_data'])) {
            $cd = $userData['custom_data'];
            $participant->custom_data = is_string($cd) ? $cd : json_encode($cd, JSON_UNESCAPED_UNICODE);
        }
        $participant->createtime = time();
        $participant->save();

        // 通知SSE推送签到更新
        HdSseController::notifyChannel($activity->id, 'sign');

        return [
            'code' => 0,
            'msg'  => '签到成功',
            'data' => [
                'signorder'  => $signOrder,
                'sign_time'  => date('Y-m-d H:i:s'),
                'nickname'   => $participant->nickname,
            ],
        ];
    }

    /**
     * 发送签到短信验证码
     */
    public function sendSignSmsCode(string $accessCode, string $phone): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];
        if (empty($screenConfig['require_phone_verify'])) {
            return ['code' => 1, 'msg' => '该活动未启用短信验证'];
        }

        if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return ['code' => 1, 'msg' => '手机号格式不正确'];
        }

        // 防频率限制（60秒内不能重复发送）
        $rateLimitKey = 'hd_sign_sms_rate:' . $activity->id . ':' . $phone;
        if (Cache::get($rateLimitKey)) {
            return ['code' => 1, 'msg' => '发送太频繁，请60秒后再试'];
        }

        // 生成6位验证码
        $code = (string)rand(100000, 999999);
        $cacheKey = 'hd_sign_sms:' . $activity->id . ':' . $phone;

        // 发送短信
        try {
            if (class_exists('\\app\\common\\Sms')) {
                $rs = \app\common\Sms::send($activity->aid, $phone, 'tmpl_smscode', ['code' => $code]);
                if (isset($rs['status']) && $rs['status'] != 1) {
                    Log::error("[HdSign] 签到短信发送失败: " . ($rs['msg'] ?? '未知错误'));
                    return ['code' => 1, 'msg' => $rs['msg'] ?? '短信发送失败'];
                }
                Log::info("[HdSign] 签到验证码已发送至 {$phone}, 活动ID: {$activity->id}");
            } else {
                Log::error("[HdSign] 短信服务类不存在");
                return ['code' => 1, 'msg' => '短信服务未配置'];
            }
        } catch (\Throwable $e) {
            Log::error("[HdSign] 发送签到验证码异常: " . $e->getMessage());
            return ['code' => 1, 'msg' => '短信发送失败，请稍后重试'];
        }

        // 短信发送成功后缓存验证码和防频
        Cache::set($cacheKey, $code, 300); // 5分钟有效
        Cache::set($rateLimitKey, 1, 60);  // 60秒防频

        return ['code' => 0, 'msg' => '验证码已发送'];
    }

    /**
     * 检查当前用户是否为管理员
     */
    public function checkAdmin(string $accessCode, string $openid): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->find();

        if (!$participant) {
            return ['code' => 0, 'data' => ['is_admin' => 0, 'features' => []]];
        }

        $isAdmin = (int)$participant->is_admin;
        $features = [];

        if ($isAdmin) {
            // 获取活动已启用的功能列表
            $features = HdActivityFeature::where('activity_id', $activity->id)
                ->where('enabled', 1)
                ->order('sort asc')
                ->select()
                ->toArray();
            foreach ($features as &$f) {
                $f['feature_name'] = HdActivityService::ALL_FEATURES[$f['feature_code']] ?? $f['feature_code'];
            }
            unset($f);
        }

        return ['code' => 0, 'data' => ['is_admin' => $isAdmin, 'features' => $features]];
    }

    /**
     * 管理员获取功能开关列表
     */
    public function adminGetFeatures(string $accessCode, string $openid): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        // 权限校验
        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->where('is_admin', 1)
            ->find();
        if (!$participant) {
            return ['code' => 1, 'msg' => '无管理员权限'];
        }

        $features = HdActivityFeature::where('activity_id', $activity->id)
            ->order('sort asc')
            ->select()
            ->toArray();

        foreach ($features as &$f) {
            $f['feature_name'] = HdActivityService::ALL_FEATURES[$f['feature_code']] ?? $f['feature_code'];
        }
        unset($f);

        // 获取抽奖轮次（用于管理员控制页面）
        $lotteryRounds = HdLotteryConfig::where('activity_id', $activity->id)
            ->order('round_num asc')
            ->select()
            ->toArray();

        return ['code' => 0, 'data' => ['features' => $features, 'lottery_rounds' => $lotteryRounds]];
    }

    /**
     * 管理员切换大屏功能
     */
    public function adminFeatureToggle(string $accessCode, string $openid, array $data): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        // 权限校验
        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->where('is_admin', 1)
            ->find();
        if (!$participant) {
            return ['code' => 1, 'msg' => '无管理员权限'];
        }

        $featureCode = $data['feature_code'] ?? '';
        $action = $data['action'] ?? 'toggle';

        if (empty($featureCode)) {
            return ['code' => 1, 'msg' => '功能代码不能为空'];
        }

        if ($action === 'draw') {
            // 触发抽奖
            $roundId = (int)($data['round_id'] ?? 0);
            if (!$roundId) {
                return ['code' => 1, 'msg' => '请选择抽奖轮次'];
            }
            return $this->lotteryDraw($accessCode, $roundId);
        }

        // 功能开关切换
        $feature = HdActivityFeature::where('activity_id', $activity->id)
            ->where('feature_code', $featureCode)
            ->find();

        if (!$feature) {
            return ['code' => 1, 'msg' => '功能不存在'];
        }

        $feature->enabled = $feature->enabled ? 0 : 1;
        $feature->save();

        // SSE推送功能切换事件
        HdSseController::notifyChannel($activity->id, $featureCode);

        return [
            'code' => 0,
            'msg'  => $feature->enabled ? '已启用' : '已禁用',
            'data' => ['feature_code' => $featureCode, 'enabled' => $feature->enabled],
        ];
    }

    /**
     * 检查当前用户是否有核销权限
     */
    public function checkVerifier(string $accessCode, string $openid): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $participant = HdParticipant::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->find();

        $isVerifier = $participant ? (int)$participant->is_verifier : 0;

        return ['code' => 0, 'data' => ['is_verifier' => $isVerifier]];
    }

    /**
     * 获取上墙消息
     */
    public function getWallMessages(string $accessCode, array $params = []): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $where = [
            ['activity_id', '=', $activity->id],
            ['is_approved', '=', 1],
        ];

        if (!empty($params['last_id'])) {
            $where[] = ['id', '>', (int)$params['last_id']];
        }

        $list = HdWallMessage::where($where)
            ->order('id desc')
            ->limit(50)
            ->select()
            ->toArray();

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 发送上墙消息（集成内容安全过滤）
     */
    public function sendWallMessage(string $accessCode, array $data): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $openid = $data['openid'] ?? '';
        $content = $data['content'] ?? '';

        // 内容安全过滤
        $filterService = new HdContentFilterService();

        // 检查全局禁言
        if ($filterService->isGlobalMuted($activity->id)) {
            return ['code' => 1, 'msg' => '当前活动已开启全局禁言'];
        }

        // 过滤内容
        $filterResult = $filterService->filterContent($activity->id, $openid, $content);
        if (!$filterResult['pass']) {
            return ['code' => 1, 'msg' => $filterResult['reason'] ?: '消息被拦截'];
        }

        // 获取功能配置，判断是否需要审核
        $feature = HdActivityFeature::where('activity_id', $activity->id)
            ->where('feature_code', 'wall')
            ->find();
        $needApprove = 1; // 默认需要审核
        if ($feature) {
            $cfg = $feature->config;
            $needApprove = (int)($cfg['need_approve'] ?? 1);
        }

        // 如果内容安全过滤建议转审核
        if ($filterResult['action'] === 3) {
            $needApprove = 1;
        }

        $message = new HdWallMessage();
        $message->aid = $activity->aid;
        $message->bid = $activity->bid;
        $message->activity_id = $activity->id;
        $message->participant_id = (int)($data['participant_id'] ?? 0);
        $message->openid = $openid;
        $message->nickname = $data['nickname'] ?? '';
        $message->avatar = $data['avatar'] ?? '';
        $message->content = $filterResult['filtered_content'];
        $message->imgurl = $data['imgurl'] ?? '';
        $message->type = !empty($data['imgurl']) ? (!empty($content) ? 3 : 2) : 1;
        $message->is_approved = $needApprove ? 0 : 1;
        $message->createtime = time();
        $message->save();

        // 通知SSE推送上墙更新
        if (!$needApprove) {
            HdSseController::notifyChannel($activity->id, 'wall');
        }

        return [
            'code' => 0,
            'msg'  => $needApprove ? '消息已提交，等待审核' : '消息已发送',
        ];
    }

    /**
     * 执行大屏抽奖
     */
    public function lotteryDraw(string $accessCode, int $roundId): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $round = HdLotteryConfig::where('id', $roundId)
            ->where('activity_id', $activity->id)
            ->find();

        if (!$round) {
            return ['code' => 1, 'msg' => '抽奖轮次不存在'];
        }

        if ($round->status == 2) {
            return ['code' => 1, 'msg' => '该轮次已抽过', 'data' => ['winners' => $round->winners]];
        }

        // 获取已签到且未中奖的参与者
        $existingWinnerIds = [];
        if (!$round->is_repeat) {
            $allRounds = HdLotteryConfig::where('activity_id', $activity->id)
                ->where('status', 2)
                ->column('winners');
            foreach ($allRounds as $w) {
                $ws = is_string($w) ? json_decode($w, true) : $w;
                if ($ws) {
                    foreach ($ws as $winner) {
                        $existingWinnerIds[] = $winner['id'] ?? 0;
                    }
                }
            }
        }

        $query = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED);

        if ($existingWinnerIds) {
            $query->whereNotIn('id', $existingWinnerIds);
        }

        $candidates = $query->select()->toArray();

        if (empty($candidates)) {
            return ['code' => 1, 'msg' => '没有可抽奖的参与者'];
        }

        // 随机选取中奖者
        $winNum = min($round->win_num, count($candidates));
        $winnerKeys = array_rand($candidates, $winNum);
        if (!is_array($winnerKeys)) {
            $winnerKeys = [$winnerKeys];
        }

        $winners = [];
        foreach ($winnerKeys as $key) {
            $winners[] = [
                'id'       => $candidates[$key]['id'],
                'nickname' => $candidates[$key]['nickname'],
                'avatar'   => $candidates[$key]['avatar'],
                'openid'   => $candidates[$key]['openid'],
            ];
        }

        // 更新奖品使用数
        if ($round->prize_id) {
            $prize = HdPrize::find($round->prize_id);
            if ($prize) {
                HdPrize::where('id', $prize->id)->inc('used_num', count($winners))->update();
            }
        }

        // 保存中奖者
        $round->winners = $winners;
        $round->status = 2;
        $round->save();

        // 通知SSE推送抽奖更新
        Cache::set("hd_lottery_result:{$activity->id}", json_encode(['status' => 'drawn', 'round_name' => $round->round_name, 'winners' => $winners]), 300);
        HdSseController::notifyChannel($activity->id, 'lottery');

        return [
            'code' => 0,
            'msg'  => '抽奖完成',
            'data' => [
                'winners'    => $winners,
                'round_name' => $round->round_name,
            ],
        ];
    }

    /**
     * 摇一摇 - 获取状态
     */
    public function getShakeStatus(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $config = Db::name('hd_shake_config')
            ->where('activity_id', $activity->id)
            ->order('id desc')
            ->find();

        if (!$config) {
            return ['code' => 0, 'data' => ['status' => 0, 'msg' => '未配置摇一摇']];
        }

        // 获取排行榜
        $records = HdShakeRecord::where('shake_config_id', $config['id'])
            ->order('score desc')
            ->limit($config['max_winners'] ?: 10)
            ->field('id,nickname,avatar,score,rank')
            ->select()
            ->toArray();

        return [
            'code' => 0,
            'data' => [
                'status'   => $config['status'],
                'duration' => $config['duration'],
                'records'  => $records,
            ],
        ];
    }

    /**
     * 摇一摇 - 提交分数
     */
    public function submitShakeScore(string $accessCode, array $data): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $config = Db::name('hd_shake_config')
            ->where('activity_id', $activity->id)
            ->where('status', 2) // 进行中
            ->order('id desc')
            ->find();

        if (!$config) {
            return ['code' => 1, 'msg' => '摇一摇未开始'];
        }

        $openid = $data['openid'] ?? '';
        $score = (int)($data['score'] ?? 0);

        // 更新或创建记录
        $record = HdShakeRecord::where('shake_config_id', $config['id'])
            ->where('openid', $openid)
            ->find();

        if ($record) {
            if ($score > $record->score) {
                $record->score = $score;
                $record->save();
            }
        } else {
            HdShakeRecord::create([
                'aid'             => $activity->aid,
                'bid'             => $activity->bid,
                'activity_id'     => $activity->id,
                'shake_config_id' => $config['id'],
                'participant_id'  => (int)($data['participant_id'] ?? 0),
                'openid'          => $openid,
                'nickname'        => $data['nickname'] ?? '',
                'avatar'          => $data['avatar'] ?? '',
                'score'           => $score,
                'createtime'      => time(),
            ]);
        }

        return ['code' => 0, 'msg' => '分数已提交'];
    }

    /**
     * 红包 - 抢红包
     */
    public function grabRedpacket(string $accessCode, array $data): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $openid = $data['openid'] ?? '';
        if (empty($openid)) {
            return ['code' => 1, 'msg' => '缺少用户标识'];
        }

        // 获取当前进行中的红包轮次
        $round = HdRedpacketRound::where('activity_id', $activity->id)
            ->where('status', 2)
            ->find();

        if (!$round) {
            return ['code' => 1, 'msg' => '红包活动未开始'];
        }

        // 检查是否已抢过
        $grabbed = HdRedpacketUser::where('round_id', $round->id)
            ->where('openid', $openid)
            ->find();
        if ($grabbed) {
            return ['code' => 1, 'msg' => '您已抢过红包', 'data' => ['amount' => $grabbed->amount]];
        }

        // 检查红包是否还有
        if ($round->sent_num >= $round->total_num) {
            return ['code' => 1, 'msg' => '红包已抢完'];
        }

        // 计算随机金额
        $remaining = (float)$round->total_amount - (float)$round->sent_amount;
        $remainingNum = $round->total_num - $round->sent_num;
        $config = Db::name('hd_redpacket_config')
            ->where('id', $round->redpacket_config_id)
            ->find();
        $min = $config ? (float)$config['min_amount'] : 0.01;
        $max = $config ? (float)$config['max_amount'] : $remaining;

        if ($remainingNum <= 1) {
            $amount = $remaining;
        } else {
            $avgMax = $remaining / $remainingNum * 2;
            $max = min($max, $avgMax);
            $amount = round(mt_rand((int)($min * 100), (int)($max * 100)) / 100, 2);
        }

        // 使用锁防止并发
        $lockKey = 'hd_redpacket_lock:' . $round->id;
        $lock = Cache::get($lockKey);
        if ($lock) {
            return ['code' => 1, 'msg' => '系统繁忙，请重试'];
        }
        Cache::set($lockKey, 1, 3);

        // 记录中奖
        HdRedpacketUser::create([
            'aid'            => $activity->aid,
            'bid'            => $activity->bid,
            'activity_id'    => $activity->id,
            'round_id'       => $round->id,
            'participant_id' => (int)($data['participant_id'] ?? 0),
            'openid'         => $openid,
            'nickname'       => $data['nickname'] ?? '',
            'amount'         => $amount,
            'status'         => 1,
            'createtime'     => time(),
        ]);

        // 更新轮次统计
        HdRedpacketRound::where('id', $round->id)->inc('sent_num', 1)->update();
        HdRedpacketRound::where('id', $round->id)->inc('sent_amount', $amount)->update();

        // 检查是否抢完
        if ($round->sent_num + 1 >= $round->total_num) {
            HdRedpacketRound::where('id', $round->id)->update(['status' => 3]);
        }

        Cache::delete($lockKey);

        return [
            'code' => 0,
            'msg'  => '抢到红包',
            'data' => ['amount' => $amount],
        ];
    }

    /**
     * 投票
     */
    public function vote(string $accessCode, array $data): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $voteItemId = (int)($data['vote_item_id'] ?? 0);
        $openid = $data['openid'] ?? '';

        if (!$voteItemId || !$openid) {
            return ['code' => 1, 'msg' => '参数不完整'];
        }

        $item = HdVoteItem::where('id', $voteItemId)
            ->where('activity_id', $activity->id)
            ->find();

        if (!$item) {
            return ['code' => 1, 'msg' => '投票选项不存在'];
        }

        // 检查是否已投票
        $voted = HdVoteRecord::where('activity_id', $activity->id)
            ->where('openid', $openid)
            ->find();

        if ($voted) {
            return ['code' => 1, 'msg' => '您已投过票'];
        }

        HdVoteRecord::create([
            'aid'            => $activity->aid,
            'bid'            => $activity->bid,
            'activity_id'    => $activity->id,
            'vote_item_id'   => $voteItemId,
            'participant_id' => (int)($data['participant_id'] ?? 0),
            'openid'         => $openid,
            'createtime'     => time(),
        ]);

        HdVoteItem::where('id', $voteItemId)->inc('vote_count', 1)->update();

        // 通知SSE推送投票更新
        HdSseController::notifyChannel($activity->id, 'vote');

        return ['code' => 0, 'msg' => '投票成功'];
    }

    /**
     * 获取弹幕消息
     */
    public function getDanmuMessages(string $accessCode, array $params = []): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $where = [
            ['activity_id', '=', $activity->id],
        ];

        if (!empty($params['last_id'])) {
            $where[] = ['id', '>', (int)$params['last_id']];
        }

        $list = Db::name('hd_wall_message')
            ->where($where)
            ->where('msg_type', 'danmu')
            ->order('id desc')
            ->limit(100)
            ->select()
            ->toArray();

        return ['code' => 0, 'data' => ['list' => $list]];
    }

    /**
     * 发送弹幕（集成内容安全过滤）
     */
    public function sendDanmu(string $accessCode, array $data): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $content = trim($data['content'] ?? '');
        if (empty($content)) {
            return ['code' => 1, 'msg' => '弹幕内容不能为空'];
        }

        if (mb_strlen($content) > 50) {
            return ['code' => 1, 'msg' => '弹幕内容不超过50字'];
        }

        $openid = $data['openid'] ?? '';

        // 内容安全过滤
        $filterService = new HdContentFilterService();

        if ($filterService->isGlobalMuted($activity->id)) {
            return ['code' => 1, 'msg' => '当前活动已开启全局禁言'];
        }

        $filterResult = $filterService->filterContent($activity->id, $openid, $content);
        if (!$filterResult['pass']) {
            return ['code' => 1, 'msg' => $filterResult['reason'] ?: '弹幕被拦截'];
        }

        Db::name('hd_wall_message')->insert([
            'aid'            => $activity->aid,
            'bid'            => $activity->bid,
            'activity_id'    => $activity->id,
            'openid'         => $openid,
            'nickname'       => $data['nickname'] ?? '',
            'content'        => $filterResult['filtered_content'],
            'msg_type'       => 'danmu',
            'color'          => $data['color'] ?? '#ffffff',
            'is_approved'    => 1,
            'participant_id' => (int)($data['participant_id'] ?? 0),
            'createtime'     => time(),
        ]);

        // 通知SSE推送弹幕更新
        HdSseController::notifyChannel($activity->id, 'danmu');

        return ['code' => 0, 'msg' => '发送成功'];
    }

    /**
     * 获取抽奖轮次列表（大屏端显示）
     */
    public function getLotteryRounds(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $rounds = HdLotteryConfig::where('activity_id', $activity->id)
            ->order('round_num asc')
            ->select()
            ->toArray();

        // 关联奖品信息
        foreach ($rounds as &$r) {
            if ($r['prize_id']) {
                $prize = HdPrize::find($r['prize_id']);
                $r['prize_name'] = $prize ? $prize->name : '';
                $r['prize_image'] = $prize ? $prize->image : '';
            } else {
                $r['prize_name'] = '';
                $r['prize_image'] = '';
            }
        }
        unset($r);

        return ['code' => 0, 'data' => ['rounds' => $rounds]];
    }

    /**
     * 获取投票选项列表（大屏/手机端）
     */
    public function getVoteItems(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $items = HdVoteItem::where('activity_id', $activity->id)
            ->order('sort asc, id asc')
            ->field('id,title,image,vote_count,sort')
            ->select()
            ->toArray();

        $totalVotes = array_sum(array_column($items, 'vote_count'));

        return [
            'code' => 0,
            'data' => [
                'items'       => $items,
                'total_votes' => $totalVotes,
            ],
        ];
    }

    /**
     * 获取相册照片列表（大屏端轮播）
     */
    public function getAlbumPhotos(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $photos = Db::name('hd_attachment')
            ->where('activity_id', $activity->id)
            ->where('category', 'album')
            ->order('id asc')
            ->field('id,file_name,file_path,file_type')
            ->select()
            ->toArray();

        // 获取相册配置
        $feature = HdActivityFeature::where('activity_id', $activity->id)
            ->where('feature_code', 'xiangce')->find();
        $config = $feature ? ($feature->config ?: []) : [];

        return [
            'code' => 0,
            'data' => [
                'photos' => $photos,
                'config' => [
                    'auto_play'     => $config['auto_play'] ?? 1,
                    'play_interval' => $config['play_interval'] ?? 5,
                    'transition'    => $config['transition'] ?? 'fade',
                ],
            ],
        ];
    }

    /**
     * 获取开幕墙配置（大屏端展示）
     */
    public function getKaimuConfig(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $config = Db::name('hd_kaimu_config')
            ->where('activity_id', $activity->id)
            ->find();

        if (!$config) {
            return ['code' => 0, 'data' => null];
        }

        return [
            'code' => 0,
            'data' => [
                'title'    => $config['title'] ?? '',
                'subtitle' => $config['subtitle'] ?? '',
                'bg_image' => $config['bg_image'] ?? '',
                'video_url'=> $config['video_url'] ?? '',
                'config'   => $config['config'] ? json_decode($config['config'], true) : [],
            ],
        ];
    }

    /**
     * 获取闭幕墙配置（大屏端展示）
     */
    public function getBimuConfig(string $accessCode): array
    {
        $activity = HdActivity::where('access_code', $accessCode)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $config = Db::name('hd_bimu_config')
            ->where('activity_id', $activity->id)
            ->find();

        if (!$config) {
            return ['code' => 0, 'data' => null];
        }

        return [
            'code' => 0,
            'data' => [
                'title'    => $config['title'] ?? '',
                'subtitle' => $config['subtitle'] ?? '',
                'bg_image' => $config['bg_image'] ?? '',
                'config'   => $config['config'] ? json_decode($config['config'], true) : [],
            ],
        ];
    }

    /**
     * 计算两个经纬度点之间的距离（米）
     * 使用 Haversine 公式
     */
    private function calcDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // 地球半径（米）
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

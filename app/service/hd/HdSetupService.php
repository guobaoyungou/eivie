<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdPlan;
use app\model\hd\HdPlanOrder;
use app\model\hd\HdPrize;
use app\model\hd\HdLotteryConfig;
use app\model\hd\HdLotteryTheme;
use app\model\hd\HdShakeConfig;
use app\model\hd\HdShakeTheme;
use app\model\hd\HdGameConfig;
use app\model\hd\HdGameTheme;
use app\model\hd\HdRedpacketConfig;
use app\model\hd\HdRedpacketRound;
use app\model\hd\HdDanmuConfig;
use app\model\hd\HdVoteItem;
use app\model\hd\HdBackground;
use app\model\hd\HdMusic;
use app\model\hd\HdChoujiangConfig;
use app\model\hd\HdKaimuConfig;
use app\model\hd\HdBimuConfig;

/**
 * 大屏互动 - Demo 初始化服务
 *
 * 以"贵州果宝电子商务有限公司"为默认 demo 商家，
 * 一键初始化完整的活动管理系统配置数据。
 */
class HdSetupService
{
    /** Demo 商家名称 */
    const DEMO_BUSINESS_NAME = '贵州果宝电子商务有限公司';

    /** Demo 活动标题 */
    const DEMO_ACTIVITY_TITLE = '贵州果宝大屏互动体验活动';

    /** Demo 管理员手机号/用户名 */
    const DEMO_ADMIN_PHONE = '18888880000';

    /** Demo 管理员密码（MD5 前） */
    const DEMO_ADMIN_PASSWORD = 'guobao2026';

    /** 全部 19 种功能代码 */
    const ALL_FEATURES = [
        ['code' => 'qdq',                  'name' => '签到墙',     'sort' => 1],
        ['code' => 'threedimensionalsign',  'name' => '3D签到',     'sort' => 2],
        ['code' => 'wall',                  'name' => '微信上墙',   'sort' => 3],
        ['code' => 'danmu',                 'name' => '弹幕',       'sort' => 4],
        ['code' => 'vote',                  'name' => '投票',       'sort' => 5],
        ['code' => 'lottery',               'name' => '大屏抽奖',   'sort' => 6],
        ['code' => 'choujiang',             'name' => '手机抽奖',   'sort' => 7],
        ['code' => 'ydj',                   'name' => '摇大奖',     'sort' => 8],
        ['code' => 'shake',                 'name' => '摇一摇竞技', 'sort' => 9],
        ['code' => 'game',                  'name' => '互动游戏',   'sort' => 10],
        ['code' => 'redpacket',             'name' => '红包雨',     'sort' => 11],
        ['code' => 'importlottery',         'name' => '导入抽奖',   'sort' => 12],
        ['code' => 'kaimu',                 'name' => '开幕墙',     'sort' => 13],
        ['code' => 'bimu',                  'name' => '闭幕墙',     'sort' => 14],
        ['code' => 'xiangce',               'name' => '相册',       'sort' => 15],
        ['code' => 'xyh',                   'name' => '幸运号码',   'sort' => 16],
        ['code' => 'xysjh',                 'name' => '幸运手机号', 'sort' => 17],
        ['code' => 'lvpai',                 'name' => '旅拍大屏',   'sort' => 18],
        ['code' => 'scan_lottery',          'name' => '扫码抽奖',   'sort' => 19],
    ];

    /** 11 种游戏主题预置 */
    const GAME_THEMES = [
        ['game_type' => 'Racing',     'name' => '默认汽车主题'],
        ['game_type' => 'Monkey',     'name' => '猴子爬树'],
        ['game_type' => 'Money',      'name' => '数钱游戏'],
        ['game_type' => 'Pig',        'name' => '金猪送福'],
        ['game_type' => 'Runner',     'name' => '赛跑'],
        ['game_type' => 'DragonBoat', 'name' => '赛龙舟'],
        ['game_type' => 'Car',        'name' => '赛车'],
        ['game_type' => 'Horse',      'name' => '赛马'],
        ['game_type' => 'Yacht',      'name' => '游艇'],
        ['game_type' => 'Qiubite',    'name' => '丘比特之箭'],
        ['game_type' => 'Happy61',    'name' => '欢乐六一'],
    ];

    /** 背景图功能模块列表 */
    const BG_FEATURE_CODES = [
        'qdq', 'threedimensionalsign', 'wall', 'danmu', 'vote',
        'lottery', 'shake', 'game', 'redpacket', 'kaimu', 'bimu',
        'xiangce', 'xyh',
    ];

    // ========================================================
    // 主入口
    // ========================================================

    /**
     * 初始化 Demo 数据（主入口，全事务）
     *
     * @return array
     */
    public function initDemo(): array
    {
        // —— 幂等性检查：商家是否已存在 ——
        $existBiz = Db::name('business')
            ->where('name', self::DEMO_BUSINESS_NAME)
            ->find();
        if ($existBiz) {
            // 检查活动是否也已存在
            $existAct = HdActivity::where('bid', $existBiz['id'])
                ->where('title', self::DEMO_ACTIVITY_TITLE)
                ->find();
            if ($existAct) {
                return [
                    'code' => 0,
                    'msg'  => 'Demo 已存在，跳过初始化',
                    'data' => [
                        'bid'            => (int)$existBiz['id'],
                        'activity_id'    => (int)$existAct->id,
                        'access_code'    => $existAct->access_code,
                        'screen_url'     => 'https://wxhd.eivie.cn/s/' . $existAct->access_code,
                        'login_username' => self::DEMO_ADMIN_PHONE,
                    ],
                ];
            }
        }

        Db::startTrans();
        try {
            // 1. 获取平台 aid
            $admin = Db::name('admin')->order('id asc')->find();
            $aid = $admin ? (int)$admin['id'] : 1;

            // 2. 创建 demo 商家
            $bizResult = $this->createDemoBusiness($aid);
            $bid = $bizResult['bid'];
            $userId = $bizResult['user_id'];

            // 3. 创建默认门店
            $mdid = $this->createDefaultStore($aid, $bid);

            // 4. 创建 demo 活动
            $actResult = $this->createDemoActivity($aid, $bid, $mdid);
            $activityId = $actResult['activity_id'];
            $accessCode = $actResult['access_code'];

            // 5. 初始化 17 种功能配置
            $this->initAllFeatures($aid, $bid, $activityId);

            // 6. 初始化抽奖轮次 + 奖品
            $this->initLotteryConfig($aid, $bid, $activityId);

            // 7. 初始化摇一摇竞技
            $this->initShakeConfig($aid, $bid, $activityId);

            // 8. 初始化互动游戏
            $this->initGameConfig($aid, $bid, $activityId);

            // 9. 初始化红包雨
            $this->initRedpacketConfig($aid, $bid, $activityId);

            // 10. 初始化弹幕
            $this->initDanmuConfig($aid, $bid, $activityId);

            // 11. 初始化投票选项
            $this->initVoteItems($aid, $bid, $activityId);

            // 12. 初始化手机端抽奖
            $this->initChoujiangConfig($aid, $bid, $activityId);

            // 13. 初始化开幕墙 / 闭幕墙
            $this->initKaimuBimuConfig($aid, $bid, $activityId);

            // 14. 初始化背景图与音乐
            $this->initBackgroundAndMusic($aid, $bid, $activityId);

            // 15. 绑定专业版套餐
            $this->bindPlan($aid, $bid);

            Db::commit();

            Log::info("[HdSetup] Demo 初始化完成: bid={$bid}, activity_id={$activityId}, access_code={$accessCode}");

            return [
                'code' => 0,
                'msg'  => 'Demo 初始化成功',
                'data' => [
                    'bid'            => $bid,
                    'activity_id'    => $activityId,
                    'access_code'    => $accessCode,
                    'screen_url'     => 'https://wxhd.eivie.cn/s/' . $accessCode,
                    'login_username' => self::DEMO_ADMIN_PHONE,
                ],
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('[HdSetup] Demo 初始化失败: ' . $e->getMessage());
            return ['code' => 1, 'msg' => '初始化失败: ' . $e->getMessage()];
        }
    }

    // ========================================================
    // 子步骤方法
    // ========================================================

    /**
     * 创建 Demo 商家 + 管理员账号
     */
    protected function createDemoBusiness(int $aid): array
    {
        // 检查是否已存在（幂等）
        $existing = Db::name('business')->where('name', self::DEMO_BUSINESS_NAME)->find();
        if ($existing) {
            return ['bid' => (int)$existing['id'], 'user_id' => 0];
        }

        $bid = Db::name('business')->insertGetId([
            'aid'        => $aid,
            'name'       => self::DEMO_BUSINESS_NAME,
            'tel'        => self::DEMO_ADMIN_PHONE,
            'linkman'    => 'Demo管理员',
            'status'     => 1,
            'createtime' => time(),
        ]);

        $userId = Db::name('admin_user')->insertGetId([
            'aid'        => $aid,
            'bid'        => $bid,
            'mdid'       => 0,
            'un'         => self::DEMO_ADMIN_PHONE,
            'pwd'        => md5(self::DEMO_ADMIN_PASSWORD),
            'isadmin'    => 1,
            'status'     => 1,
            'createtime' => time(),
        ]);

        // 创建 HD 扩展配置
        HdBusinessConfig::create([
            'aid'              => $aid,
            'bid'              => $bid,
            'plan_id'          => 0,
            'plan_expire_time' => 0,
            'trial_used'       => 0,
            'createtime'       => time(),
        ]);

        return ['bid' => $bid, 'user_id' => $userId];
    }

    /**
     * 创建默认门店
     */
    protected function createDefaultStore(int $aid, int $bid): int
    {
        $exists = Db::name('mendian')->where('aid', $aid)->where('bid', $bid)->find();
        if ($exists) {
            return (int)$exists['id'];
        }

        return (int)Db::name('mendian')->insertGetId([
            'aid'        => $aid,
            'bid'        => $bid,
            'name'       => self::DEMO_BUSINESS_NAME . '（总店）',
            'tel'        => self::DEMO_ADMIN_PHONE,
            'status'     => 1,
            'createtime' => time(),
        ]);
    }

    /**
     * 创建 Demo 活动
     */
    protected function createDemoActivity(int $aid, int $bid, int $mdid): array
    {
        $exists = HdActivity::where('bid', $bid)->where('title', self::DEMO_ACTIVITY_TITLE)->find();
        if ($exists) {
            return ['activity_id' => (int)$exists->id, 'access_code' => $exists->access_code];
        }

        $accessCode = HdActivity::generateAccessCode();
        $verifycode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $screenConfig = [
            'sign_match_mode'  => 1,
            'sign_verify_mode' => 1,
            'sign_show_style'  => 1,
        ];

        $activity = new HdActivity();
        $activity->aid = $aid;
        $activity->bid = $bid;
        $activity->mdid = $mdid;
        $activity->title = self::DEMO_ACTIVITY_TITLE;
        $activity->access_code = $accessCode;
        $activity->status = HdActivity::STATUS_NOT_STARTED;
        $activity->verifycode = $verifycode;
        $activity->screen_config = $screenConfig;
        $activity->createtime = time();
        $activity->save();

        return ['activity_id' => (int)$activity->id, 'access_code' => $accessCode];
    }

    /**
     * 初始化 17 种功能配置到 hd_activity_feature
     */
    protected function initAllFeatures(int $aid, int $bid, int $activityId): void
    {
        foreach (self::ALL_FEATURES as $feat) {
            HdActivityFeature::create([
                'aid'          => $aid,
                'bid'          => $bid,
                'activity_id'  => $activityId,
                'feature_code' => $feat['code'],
                'enabled'      => 1,
                'config'       => json_encode([], JSON_UNESCAPED_UNICODE),
                'sort'         => $feat['sort'],
            ]);
        }
    }

    /**
     * 初始化抽奖轮次 + 奖品
     *
     * 3 轮 × 3 级奖品 = 9 条 lottery 奖品
     * + 3 条 ydj 奖品 + 3 条 importlottery 奖品 = 15 条奖品
     */
    protected function initLotteryConfig(int $aid, int $bid, int $activityId): void
    {
        $now = time();
        $prizeNames = ['一等奖', '二等奖', '三等奖'];

        // —— 大屏抽奖：3 轮 ——
        for ($round = 1; $round <= 3; $round++) {
            // 为每轮创建 3 级奖品，并关联轮次
            foreach ($prizeNames as $sortIdx => $pName) {
                $prizeId = Db::name('hd_prize')->insertGetId([
                    'aid'         => $aid,
                    'bid'         => $bid,
                    'activity_id' => $activityId,
                    'name'        => "第{$round}轮{$pName}",
                    'image'       => '',
                    'total_num'   => 100,
                    'used_num'    => 0,
                    'sort'        => ($round - 1) * 3 + $sortIdx,
                    'createtime'  => $now,
                ]);

                Db::name('hd_lottery_config')->insert([
                    'aid'         => $aid,
                    'bid'         => $bid,
                    'activity_id' => $activityId,
                    'round_name'  => "第{$round}轮活动",
                    'round_num'   => $round,
                    'prize_id'    => $prizeId,
                    'win_num'     => 1,
                    'is_repeat'   => 0,
                    'status'      => 1,
                    'winners'     => '',
                    'createtime'  => $now,
                ]);
            }
        }

        // —— 摇大奖奖品 ——
        for ($i = 1; $i <= 3; $i++) {
            Db::name('hd_prize')->insert([
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'name'        => "摇大奖奖品{$i}",
                'image'       => '',
                'total_num'   => 10,
                'used_num'    => 0,
                'sort'        => 9 + $i,
                'createtime'  => $now,
            ]);
        }

        // —— 导入抽奖奖品 ——
        for ($i = 1; $i <= 3; $i++) {
            Db::name('hd_prize')->insert([
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'name'        => "导入抽奖奖品{$i}",
                'image'       => '',
                'total_num'   => 100,
                'used_num'    => 0,
                'sort'        => 12 + $i,
                'createtime'  => $now,
            ]);
        }

        // —— 抽奖主题 ——
        $themes = [
            ['name' => '3D抽奖',  'is_default' => 1],
            ['name' => '砸金蛋',  'is_default' => 0],
            ['name' => '抽奖箱',  'is_default' => 0],
        ];
        foreach ($themes as $th) {
            Db::name('hd_lottery_theme')->insert([
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'name'        => $th['name'],
                'bg_image'    => '',
                'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_default'  => $th['is_default'],
                'createtime'  => $now,
            ]);
        }
    }

    /**
     * 初始化摇一摇竞技
     */
    protected function initShakeConfig(int $aid, int $bid, int $activityId): void
    {
        $now = time();

        Db::name('hd_shake_config')->insert([
            'aid'              => $aid,
            'bid'              => $bid,
            'activity_id'      => $activityId,
            'duration'         => 100,
            'max_winners'      => 3,
            'max_participants' => 200,
            'prize_id'         => 0,
            'bg_image'         => '',
            'config'           => json_encode([], JSON_UNESCAPED_UNICODE),
            'status'           => 1,
            'createtime'       => $now,
        ]);

        // 摇一摇主题
        $shakeThemes = [
            ['name' => '横向汽车主题', 'is_default' => 1],
            ['name' => '纵向气球主题', 'is_default' => 0],
            ['name' => '横向足球主题', 'is_default' => 0],
        ];
        foreach ($shakeThemes as $th) {
            Db::name('hd_shake_theme')->insert([
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'name'        => $th['name'],
                'bg_image'    => '',
                'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_default'  => $th['is_default'],
                'createtime'  => $now,
            ]);
        }
    }

    /**
     * 初始化互动游戏
     */
    protected function initGameConfig(int $aid, int $bid, int $activityId): void
    {
        $now = time();

        // 默认游戏配置（Racing 作为默认启用）
        Db::name('hd_game_config')->insert([
            'aid'         => $aid,
            'bid'         => $bid,
            'activity_id' => $activityId,
            'game_type'   => 'Racing',
            'duration'    => 30,
            'max_winners' => 10,
            'prize_id'    => 0,
            'bg_image'    => '',
            'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
            'status'      => 1,
            'createtime'  => $now,
        ]);

        // 11 种游戏主题
        foreach (self::GAME_THEMES as $th) {
            Db::name('hd_game_theme')->insert([
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'game_type'   => $th['game_type'],
                'name'        => $th['name'],
                'bg_image'    => '',
                'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_default'  => $th['game_type'] === 'Racing' ? 1 : 0,
                'createtime'  => $now,
            ]);
        }
    }

    /**
     * 初始化红包雨
     */
    protected function initRedpacketConfig(int $aid, int $bid, int $activityId): void
    {
        $now = time();

        $configId = Db::name('hd_redpacket_config')->insertGetId([
            'aid'          => $aid,
            'bid'          => $bid,
            'activity_id'  => $activityId,
            'total_amount' => 0.00,
            'total_num'    => 0,
            'min_amount'   => 0.01,
            'max_amount'   => 10.00,
            'duration'     => 30,
            'config'       => json_encode([], JSON_UNESCAPED_UNICODE),
            'createtime'   => $now,
        ]);

        // 预置 1 个示例轮次
        Db::name('hd_redpacket_round')->insert([
            'aid'                => $aid,
            'bid'                => $bid,
            'activity_id'        => $activityId,
            'redpacket_config_id' => $configId,
            'round_num'          => 1,
            'total_amount'       => 0.00,
            'sent_amount'        => 0.00,
            'total_num'          => 0,
            'sent_num'           => 0,
            'status'             => 1,
            'createtime'         => $now,
        ]);
    }

    /**
     * 初始化弹幕配置
     */
    protected function initDanmuConfig(int $aid, int $bid, int $activityId): void
    {
        Db::name('hd_danmu_config')->insert([
            'aid'         => $aid,
            'bid'         => $bid,
            'activity_id' => $activityId,
            'speed'       => 3,
            'font_size'   => 24,
            'opacity'     => 0.80,
            'config'      => json_encode([
                'textcolor'    => '#b7e692',
                'positionmode' => 4,
                'showname'     => 2,
            ], JSON_UNESCAPED_UNICODE),
            'createtime'  => time(),
        ]);
    }

    /**
     * 初始化默认投票选项
     */
    protected function initVoteItems(int $aid, int $bid, int $activityId): void
    {
        $now = time();
        $items = [
            ['title' => '选项A', 'sort' => 1],
            ['title' => '选项B', 'sort' => 2],
            ['title' => '选项C', 'sort' => 3],
            ['title' => '选项D', 'sort' => 4],
        ];
        foreach ($items as $item) {
            Db::name('hd_vote_item')->insert([
                'aid'         => $aid,
                'bid'         => $bid,
                'activity_id' => $activityId,
                'title'       => $item['title'],
                'image'       => '',
                'vote_count'  => 0,
                'sort'        => $item['sort'],
                'createtime'  => $now,
            ]);
        }
    }

    /**
     * 初始化手机端抽奖
     */
    protected function initChoujiangConfig(int $aid, int $bid, int $activityId): void
    {
        Db::name('hd_choujiang_config')->insert([
            'aid'         => $aid,
            'bid'         => $bid,
            'activity_id' => $activityId,
            'max_times'   => 1,
            'bg_image'    => '',
            'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
            'createtime'  => time(),
        ]);
    }

    /**
     * 初始化开幕墙 / 闭幕墙
     */
    protected function initKaimuBimuConfig(int $aid, int $bid, int $activityId): void
    {
        $now = time();

        Db::name('hd_kaimu_config')->insert([
            'aid'         => $aid,
            'bid'         => $bid,
            'activity_id' => $activityId,
            'title'       => self::DEMO_ACTIVITY_TITLE,
            'subtitle'    => '欢迎莅临',
            'bg_image'    => '',
            'video_url'   => '',
            'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
            'createtime'  => $now,
        ]);

        Db::name('hd_bimu_config')->insert([
            'aid'         => $aid,
            'bid'         => $bid,
            'activity_id' => $activityId,
            'title'       => self::DEMO_ACTIVITY_TITLE,
            'subtitle'    => '感谢参与',
            'bg_image'    => '',
            'config'      => json_encode([], JSON_UNESCAPED_UNICODE),
            'createtime'  => $now,
        ]);
    }

    /**
     * 初始化各功能的背景图与背景音乐
     */
    protected function initBackgroundAndMusic(int $aid, int $bid, int $activityId): void
    {
        $now = time();

        // 为 13 个功能模块创建默认背景占位记录
        foreach (self::BG_FEATURE_CODES as $idx => $code) {
            Db::name('hd_background')->insert([
                'aid'          => $aid,
                'bid'          => $bid,
                'activity_id'  => $activityId,
                'feature_code' => $code,
                'image_url'    => '',
                'is_default'   => 1,
                'sort'         => $idx,
                'createtime'   => $now,
            ]);
        }

        // 创建一条默认背景音乐占位
        Db::name('hd_music')->insert([
            'aid'         => $aid,
            'bid'         => $bid,
            'activity_id' => $activityId,
            'name'        => '默认背景音乐',
            'file_url'    => '',
            'duration'    => 0,
            'is_default'  => 1,
            'sort'        => 0,
            'createtime'  => $now,
        ]);
    }

    /**
     * 绑定专业版套餐
     */
    protected function bindPlan(int $aid, int $bid): void
    {
        $now = time();
        $allFeatureCodes = array_column(self::ALL_FEATURES, 'code');

        // 查找或创建专业版套餐
        $plan = HdPlan::where('name', '专业版')->where('status', HdPlan::STATUS_ACTIVE)->find();
        if (!$plan) {
            $plan = new HdPlan();
            $plan->aid = $aid;
            $plan->name = '专业版';
            $plan->price = 599.00;
            $plan->duration_days = 365;
            $plan->max_stores = 10;
            $plan->max_activities = 20;
            $plan->max_participants = 500;
            $plan->features = implode(',', $allFeatureCodes);
            $plan->status = HdPlan::STATUS_ACTIVE;
            $plan->sort = 100;
            $plan->createtime = $now;
            $plan->save();
        }

        $planId = (int)$plan->id;
        $expireTime = $now + ($plan->duration_days * 86400);

        // 更新商家扩展配置绑定套餐
        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        if ($bizConfig) {
            $bizConfig->plan_id = $planId;
            $bizConfig->plan_expire_time = $expireTime;
            $bizConfig->save();
        } else {
            HdBusinessConfig::create([
                'aid'              => $aid,
                'bid'              => $bid,
                'plan_id'          => $planId,
                'plan_expire_time' => $expireTime,
                'trial_used'       => 1,
                'createtime'       => $now,
            ]);
        }

        // 生成已支付的套餐订单记录
        $orderNo = 'HD' . date('YmdHis') . str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        Db::name('hd_plan_order')->insert([
            'aid'        => $aid,
            'bid'        => $bid,
            'plan_id'    => $planId,
            'plan_name'  => '专业版',
            'order_no'   => $orderNo,
            'amount'     => $plan->price,
            'pay_status' => HdPlanOrder::PAY_STATUS_PAID,
            'pay_time'   => $now,
            'start_time' => $now,
            'end_time'   => $expireTime,
            'createtime' => $now,
        ]);
    }
}

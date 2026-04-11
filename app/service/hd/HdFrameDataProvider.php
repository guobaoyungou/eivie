<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdMusic;
use app\model\hd\HdDanmuConfig;
use app\model\hd\HdKaimuConfig;
use app\model\hd\HdBimuConfig;
use app\model\hd\HdParticipant;
use app\model\hd\HdVoteItem;

/**
 * 大屏互动 - 数据适配器
 * 将新系统 ThinkPHP Service 层数据转换为老 Smarty 模板所需的变量格式
 */
class HdFrameDataProvider
{
    /**
     * 构建 frame.html 所需的 Smarty 变量集
     */
    public function buildFrameVars(HdActivity $activity, array $features): array
    {
        $baseUrl = '/s/' . $activity->access_code;

        $wallConfig = $this->convertWallConfig($activity);
        $weixinConfig = $this->convertWeixinConfig($activity);
        $plugs = $this->convertPlugs($features, $baseUrl);
        $plugsjson = $this->formatPlugsJson($plugs);
        $musicjson = $this->convertMusicJson($activity->id);
        $backgroundimagejson = $this->convertBackgroundJson($activity->id);
        $danmuconfig = $this->convertDanmuConfig($activity->id);
        $screenConfig = $activity->screen_config ?: [];

        $menucolor = $screenConfig['menucolor'] ?? '#fff';
        $showcountsign = $screenConfig['showcountsign'] ?? '1';
        $qrcodepos = isset($screenConfig['qrcodepos']) ? json_encode($screenConfig['qrcodepos']) : 'null';

        // 信息显示开关配置（从活动 screen_config 读取，优先于全局配置）
        $displayConfig = $this->getDisplayConfig($activity);

        // 从活动 screen_config 读取LOGO和活动名称等字段，合并到 wallConfig
        $legacyWallConfig = $this->getLegacyWallConfig($activity);
        $wallConfig = array_merge($wallConfig, $legacyWallConfig);

        // 背景音乐自动播放设置
        $bgmusic = [
            'bgmusicstatus' => $screenConfig['bgmusicstatus'] ?? 0,
        ];

        // 大屏密码开关
        $screenPasswordEnabled = (int)($screenConfig['screen_password_enabled'] ?? 1);

        return [
            'wall_config'         => $wallConfig,
            'weixin_config'       => $weixinConfig,
            'plugs'               => $plugs,
            'plugsjson'           => $plugsjson,
            'musicjson'           => $musicjson,
            'backgroundimagejson' => $backgroundimagejson,
            'danmuconfig'         => $danmuconfig,
            'menucolor'           => $menucolor,
            'showcountsign'       => $showcountsign,
            'qrcodepos'           => $qrcodepos,
            'bgmusic'             => $bgmusic,
            'show_company_name'   => $displayConfig['show_company_name'],
            'show_activity_name'  => $displayConfig['show_activity_name'],
            'show_copyright'      => $displayConfig['show_copyright'],
            'show_logo'           => $displayConfig['show_logo'],
            'base_url'            => $baseUrl,
            'screen_password_enabled' => $screenPasswordEnabled,
        ];
    }

    /**
     * 构建 iframe 功能页通用变量
     */
    public function buildWallPageVars(HdActivity $activity, string $feature): array
    {
        $wallConfig = $this->convertWallConfig($activity);
        $weixinConfig = $this->convertWeixinConfig($activity);
        $baseUrl = '/s/' . $activity->access_code;

        $vars = [
            'wall_config'  => $wallConfig,
            'weixin_config'=> $weixinConfig,
            'title'        => $activity->title,
            'from'         => 'qiandao',
            'erweima'      => $weixinConfig['erweima'] ?? '',
            'base_url'     => $baseUrl,
        ];

        // 按功能添加特定变量
        switch ($feature) {
            case 'index':
            case 'qdq':
                $vars = array_merge($vars, $this->buildSignPageVars($activity));
                break;
            case '3dsign':
            case 'threedimensionalsign':
                $vars = array_merge($vars, $this->build3dSignPageVars($activity));
                break;
            case 'wall':
                $vars = array_merge($vars, $this->buildWallVars($activity));
                break;
            case 'danmu':
                $vars = array_merge($vars, $this->buildDanmuVars($activity));
                break;
            case 'vote':
                $vars = array_merge($vars, $this->buildVoteVars($activity));
                break;
            case 'kaimu':
                $vars = array_merge($vars, $this->buildKaimuVars($activity));
                break;
            case 'bimu':
                $vars = array_merge($vars, $this->buildBimuVars($activity));
                break;
            case 'xiangce':
                $vars = array_merge($vars, $this->buildXiangceVars($activity));
                break;
            case 'redpacket':
                $vars = array_merge($vars, $this->buildRedpacketVars($activity));
                break;
            case 'xyh':
                $vars = array_merge($vars, $this->buildXyhVars($activity));
                break;
            case 'xysjh':
                $vars = array_merge($vars, $this->buildXysjhVars($activity));
                break;
            case 'danye':
                $vars = array_merge($vars, $this->buildDanyeVars($activity));
                break;
            case 'ddp':
                $vars = array_merge($vars, $this->buildDdpVars($activity));
                break;
        }

        return $vars;
    }

    /**
     * 构建手机端模板变量
     */
    public function buildMobileVars(HdActivity $activity, array $features): array
    {
        $baseUrl = '/s/' . $activity->access_code;
        return [
            'activity'   => $activity->toArray(),
            'features'   => $features,
            'base_url'   => $baseUrl,
            'api_base'   => '/api/hd/screen/' . $activity->access_code,
            'qrcode_url' => 'https://wxhd.eivie.cn/s/' . $activity->access_code,
        ];
    }

    // ============================================================
    // 模块功能页变量构建（Lottery/Game）
    // ============================================================

    /**
     * 构建模块页面变量（抽奖/游戏）
     * 从新系统 ddwx_hd_* 表读取数据，转换为老系统 Smarty 模板所需格式
     */
    public function buildModulePageVars(HdActivity $activity, string $feature): array
    {
        switch ($feature) {
            case 'lottery':
            case 'importlottery':
                return $this->buildLotteryModuleVars($activity, $feature);
            case 'game':
                return $this->buildGameModuleVars($activity);
            default:
                return [];
        }
    }

    /**
     * 抽奖模块变量
     * 老模板需要: $prizes(JSON), $config(array), $configs(JSON)
     */
    private function buildLotteryModuleVars(HdActivity $activity, string $feature): array
    {
        // 获取抽奖配置列表
        $lotteryConfigs = Db::name('hd_lottery_config')
            ->where('activity_id', $activity->id)
            ->order('id asc')
            ->select()
            ->toArray();

        // 获取当前抽奖配置
        $id = (int)input('get.id', 0);
        $currentConfig = null;
        if ($id > 0 && !empty($lotteryConfigs)) {
            foreach ($lotteryConfigs as $cfg) {
                if ($cfg['id'] == $id) {
                    $currentConfig = $cfg;
                    break;
                }
            }
        }
        if (!$currentConfig && !empty($lotteryConfigs)) {
            $currentConfig = $lotteryConfigs[0];
        }

        // 获取抽奖主题
        $theme = null;
        if ($currentConfig) {
            $theme = Db::name('hd_lottery_theme')
                ->where('activity_id', $activity->id)
                ->order('is_default desc, id asc')
                ->find();
        }

        // 确定模板路径
        $themePath = 'zjd'; // 默认抽奖箱
        $type = (int)input('get.type', 0);
        if ($feature === 'importlottery' || $type === 1) {
            $themePath = 'threedimensional'; // 3D抽奖
        } elseif ($theme && !empty($theme['name'])) {
            $themeMap = ['zjd' => 'zjd', 'cjx' => 'cjx', 'threedimensional' => 'threedimensional', '3dlottery' => 'threedimensional'];
            $themePath = $themeMap[strtolower($theme['name'])] ?? 'zjd';
        }

        // 获取奖品列表
        $prizes = Db::name('hd_prize')
            ->where('activity_id', $activity->id)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();

        // 转换奖品为老系统格式
        $prizesFormatted = [];
        foreach ($prizes as $p) {
            $prizesFormatted[] = [
                'id'          => $p['id'],
                'prizename'   => $p['name'],
                'num'         => $p['total_num'],
                'leftnum'     => max(0, $p['total_num'] - $p['used_num']),
                'freezenum'   => 0,
                'formatedtext'=> ['text' => $p['image'] ?: '/static/hd/themes/meepo/assets/images/defaultaward.jpg'],
                'totalleft'   => max(0, $p['total_num'] - $p['used_num']),
            ];
        }

        // 构建配置对象
        $configForTemplate = [
            'id'          => $currentConfig ? $currentConfig['id'] : 0,
            'title'       => $currentConfig ? $currentConfig['round_name'] : '抽奖',
            'themepath'   => $themePath,
            'showtype'    => 'nickname',
            'winagain'    => 0,
            'themeconfig' => [
                'bg_path'       => '/static/hd/themes/meepo/assets/images/defaultbg.jpg',
                'bgmusic_path'  => '/static/hd/themes/meepo/assets/music/Radetzky_Marsch.mp3',
                'bgmusic_switch'=> 2,
                'fontcolor'     => '#fff',
                'prizefontcolor'=> '#ffde00',
                'winnerfontcolor'=> '#fff',
                'leftcolor'     => 'rgba(0,0,0,0.6)',
                'winnercolor'   => 'rgba(0,0,0,0.3)',
            ],
        ];

        // 配置列表（用于轮次切换）
        $configsList = [];
        foreach ($lotteryConfigs as $cfg) {
            $configsList[] = [
                'id'      => $cfg['id'],
                'title'   => $cfg['round_name'],
                'themeid' => $themePath === 'threedimensional' ? 1 : ($themePath === 'cjx' ? 3 : 2),
            ];
        }

        return [
            'prizes'      => json_encode($prizesFormatted, JSON_UNESCAPED_UNICODE),
            'config'      => $configForTemplate,
            'configs'     => json_encode($configsList, JSON_UNESCAPED_UNICODE),
            '_theme_path' => $themePath,
        ];
    }

    /**
     * 游戏模块变量
     * 老模板需要: $config(array), $configs(JSON)
     */
    private function buildGameModuleVars(HdActivity $activity): array
    {
        // 获取游戏配置列表
        $gameConfigs = Db::name('hd_game_config')
            ->where('activity_id', $activity->id)
            ->order('id asc')
            ->select()
            ->toArray();

        $id = (int)input('get.id', 0);
        $currentConfig = null;
        if ($id > 0 && !empty($gameConfigs)) {
            foreach ($gameConfigs as $cfg) {
                if ($cfg['id'] == $id) {
                    $currentConfig = $cfg;
                    break;
                }
            }
        }
        if (!$currentConfig && !empty($gameConfigs)) {
            $currentConfig = $gameConfigs[0];
        }

        // 确定游戏主题路径
        $themePath = 'car';
        if ($currentConfig && !empty($currentConfig['game_type'])) {
            $themePath = strtolower($currentConfig['game_type']);
        }

        // 构建配置
        $extraConfig = $currentConfig && !empty($currentConfig['config'])
            ? (is_string($currentConfig['config']) ? json_decode($currentConfig['config'], true) : $currentConfig['config'])
            : [];

        $configForTemplate = [
            'id'        => $currentConfig ? $currentConfig['id'] : 0,
            'title'     => $currentConfig ? ($currentConfig['game_type'] ?? '游戏') : '游戏',
            'themepath'  => $themePath,
            'status'    => $currentConfig ? $currentConfig['status'] : 1,
            'duration'  => $currentConfig ? $currentConfig['duration'] : 30,
            'toprank'   => $currentConfig ? $currentConfig['max_winners'] : 10,
            'showtype'  => 'nickname',
            'themeconfig' => array_merge([
                'bg_path'       => '/static/hd/themes/meepo/assets/images/defaultbg.jpg',
                'bgmusic_path'  => '/static/hd/themes/meepo/assets/music/Radetzky_Marsch.mp3',
                'bgmusic_switch'=> 2,
            ], $extraConfig),
        ];

        $configsList = [];
        foreach ($gameConfigs as $cfg) {
            $configsList[] = [
                'id'      => $cfg['id'],
                'themeid' => strtolower($cfg['game_type'] ?? 'car'),
            ];
        }

        return [
            'config'      => $configForTemplate,
            'configs'     => json_encode($configsList, JSON_UNESCAPED_UNICODE),
            '_theme_path' => $themePath,
        ];
    }

    // ============================================================
    // 数据转换方法
    // ============================================================

    /**
     * 活动配置 → wall_config 格式
     */
    private function convertWallConfig(HdActivity $activity): array
    {
        $sc = $activity->screen_config ?: [];
        return [
            'copyright'        => $sc['copyright'] ?? '艺为互动',
            'copyrightlink'    => $sc['copyrightlink'] ?? 'https://wxhd.eivie.cn',
            'qrcodetoptext'    => $sc['qrcodetoptext'] ?? '微信扫码参与',
            'msg_showstyle'    => $sc['msg_showstyle'] ?? 0,
            'refreshtime'      => $sc['refreshtime'] ?? 3,
            'circulation'      => $sc['circulation'] ?? 0,
            'msg_historynum'   => $sc['msg_historynum'] ?? 0,
            'msg_showbig'      => $sc['msg_showbig'] ?? 0,
            'msg_showbigtime'  => $sc['msg_showbigtime'] ?? 5,
            'msg_num'          => $sc['msg_num'] ?? 6,
            'msg_color'        => $sc['msg_color'] ?? '#666',
            'nickname_color'   => $sc['nickname_color'] ?? '#fff',
            'signlogoimg'      => $sc['signlogoimg'] ?? '',
            'voteopen'         => $sc['voteopen'] ?? 1,
            'voteshowway'      => $sc['voteshowway'] ?? 1,
        ];
    }

    /**
     * 活动关联的微信配置
     */
    private function convertWeixinConfig(HdActivity $activity): array
    {
        $sc = $activity->screen_config ?: [];
        $businessConfig = HdBusinessConfig::where('bid', $activity->bid)->find();
        $erweima = '';
        if ($businessConfig) {
            $erweima = $businessConfig->erweima ?? '';
        }
        // screen_config 中可能也存了二维码
        if (!empty($sc['erweima'])) {
            $erweima = $sc['erweima'];
        }
        return [
            'erweima' => $erweima,
        ];
    }

    /**
     * Feature 列表 → plugs 数组格式（老系统格式）
     * @param string $baseUrl  如 /s/{access_code}
     */
    private function convertPlugs(array $features, string $baseUrl = ''): array
    {
        // 老系统 plugs 格式: [{name, title, url, ismodule, sort}, ...]
        $featureUrlMap = [
            'qdq'                  => 'index',
            'threedimensionalsign' => '3dsign',
            'wall'                 => 'wall',
            'danmu'                => 'danmu',
            'vote'                 => 'vote',
            'xyh'                  => 'xyh',
            'xysjh'                => 'xysjh',
            'ddp'                  => 'ddp',
            'xiangce'              => 'xiangce',
            'kaimu'                => 'kaimu',
            'bimu'                 => 'bimu',
            'ydj'                  => 'ydj',
            'importlottery'        => 'importlottery',
            'lottery'              => 'lottery',
            'game'                 => 'game',
            'redpacket'            => 'redpacket',
            'danye'                => 'danye',
            'choujiang'            => 'choujiang',
        ];

        $plugs = [];
        foreach ($features as $f) {
            $code = $f['feature_code'];
            $plugs[] = [
                'name'     => $code,
                'title'    => $f['feature_name'],
                'url'      => $baseUrl ? ($baseUrl . '/wall/' . ($featureUrlMap[$code] ?? $code)) : ($featureUrlMap[$code] ?? $code),
                'ismodule' => 1,
                'sort'     => $f['sort'] ?? 0,
            ];
        }
        return $plugs;
    }

    /**
     * 把 plugs 数组转为 JSON（老系统格式：以 name 为 key）
     */
    private function formatPlugsJson(array $plugs): string
    {
        $data = [];
        foreach ($plugs as $item) {
            $data[$item['name']] = $item;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 音乐 JSON
     */
    private function convertMusicJson(int $activityId): string
    {
        $list = HdMusic::where('activity_id', $activityId)
            ->order('id asc')
            ->select()
            ->toArray();
        return json_encode($list, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * 背景图 JSON（从活动 screen_config 读取，输出 plugname 为 key 的格式）
     * 优先级：当前活动配置 > 活动#2模板配置 > weixin_background 全局默认
     * 格式：{"qdq":{"path":"/static/hd/themes/meepo/assets/images/defaultbg.jpg","bgtype":1}, ...}
     */
    private function convertBackgroundJson(int $activityId): string
    {
        $defaultBgPath = '/static/hd/themes/meepo/assets/images/defaultbg.jpg';
        $templateActivityId = 2;

        try {
            // 1. 获取全局背景模块定义（从 weixin_background 表读取模块列表）
            $list = Db::connect('huodong')->table('weixin_background')->select()->toArray();

            // 2. 获取当前活动的屏幕配置
            $activityBgConfig = $this->getActivityBackgroundConfig($activityId);

            // 3. 获取活动#2的模板配置（如果当前活动没有配置，使用模板）
            $templateBgConfig = [];
            if ($activityId !== $templateActivityId) {
                $templateBgConfig = $this->getActivityBackgroundConfig($templateActivityId);
            }

            // 4. 合并配置
            $image_arr = [];
            foreach ($list as $val) {
                $plugname = $val['plugname'];

                // 确定使用哪个配置：活动配置 > 模板配置 > 全局默认
                $attachmentId = 0;
                $bgtype = intval($val['bgtype'] ?? 1);
                $path = $defaultBgPath;

                if (isset($activityBgConfig[$plugname])) {
                    $attachmentId = intval($activityBgConfig[$plugname]['attachmentid'] ?? 0);
                    $bgtype = intval($activityBgConfig[$plugname]['bgtype'] ?? $bgtype);
                } elseif (isset($templateBgConfig[$plugname])) {
                    $attachmentId = intval($templateBgConfig[$plugname]['attachmentid'] ?? 0);
                    $bgtype = intval($templateBgConfig[$plugname]['bgtype'] ?? $bgtype);
                }

                // 查询附件路径
                if ($attachmentId > 0) {
                    $attachment = Db::connect('huodong')->table('weixin_attachments')
                        ->where('id', $attachmentId)->find();
                    if ($attachment && !empty($attachment['filepath'])) {
                        $path = $attachment['filepath'];
                    }
                }

                $image_arr[$plugname] = ['path' => $path, 'bgtype' => $bgtype];
            }
            return json_encode($image_arr, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return '{}';
        }
    }

    /**
     * 获取活动的背景配置（从 hd_activity.screen_config 读取）
     */
    private function getActivityBackgroundConfig(int $activityId): array
    {
        if ($activityId <= 0) {
            return [];
        }

        try {
            $activity = HdActivity::where('id', $activityId)->find();
            if ($activity && !empty($activity->screen_config)) {
                $configRaw = $activity->getData('screen_config');
                if (is_string($configRaw)) {
                    $decoded = json_decode($configRaw, true);
                    if (is_array($decoded) && isset($decoded['backgrounds'])) {
                        return $decoded['backgrounds'];
                    }
                } elseif (is_array($configRaw) && isset($configRaw['backgrounds'])) {
                    return $configRaw['backgrounds'];
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }

    /**
     * 弹幕配置 JSON
     */
    private function convertDanmuConfig(int $activityId): string
    {
        $config = HdDanmuConfig::where('activity_id', $activityId)->find();
        if ($config) {
            return json_encode($config->toArray(), JSON_UNESCAPED_UNICODE);
        }
        return json_encode([
            'speed'     => 5,
            'color'     => '#ffffff',
            'fontsize'  => 30,
            'opacity'   => 1,
            'position'  => 'random',
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取信息显示开关配置（从活动 screen_config 读取）
     * 优先级：活动配置 > 活动#2模板配置 > 全局配置
     */
    private function getDisplayConfig(HdActivity $activity): array
    {
        $defaults = [
            'show_company_name'   => '1',
            'show_activity_name' => '1',
            'show_copyright'     => '1',
            'show_logo'          => '1',
        ];

        // 尝试从活动 screen_config 读取
        if (!empty($activity->screen_config)) {
            $screenConfig = is_array($activity->screen_config)
                ? $activity->screen_config
                : json_decode($activity->screen_config, true);
            if (is_array($screenConfig)) {
                foreach (['show_company_name', 'show_activity_name', 'show_copyright', 'show_logo'] as $key) {
                    if (isset($screenConfig[$key])) {
                        $defaults[$key] = strval($screenConfig[$key]);
                    }
                }
            }
        }

        // 如果活动配置为空，尝试从活动#2模板配置读取
        if (empty($activity->screen_config) && $activity->id !== 2) {
            try {
                $template = HdActivity::where('id', 2)->find();
                if ($template && !empty($template->screen_config)) {
                    $screenConfig = is_array($template->screen_config)
                        ? $template->screen_config
                        : json_decode($template->screen_config, true);
                    if (is_array($screenConfig)) {
                        foreach (['show_company_name', 'show_activity_name', 'show_copyright', 'show_logo'] as $key) {
                            if (isset($screenConfig[$key]) && empty($defaults[$key])) {
                                $defaults[$key] = strval($screenConfig[$key]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $defaults;
    }

    /**
     * 从活动 screen_config 读取LOGO和活动名称等字段
     * 优先级：活动配置 > 活动#2模板配置 > 全局配置
     */
    private function getLegacyWallConfig(HdActivity $activity): array
    {
        $result = [
            'activity_name' => '',
            'logoimg'       => '',
        ];

        // 尝试从活动 screen_config 读取
        if (!empty($activity->screen_config)) {
            $screenConfig = is_array($activity->screen_config)
                ? $activity->screen_config
                : json_decode($activity->screen_config, true);
            if (is_array($screenConfig)) {
                if (!empty($screenConfig['activity_name'])) {
                    $result['activity_name'] = $screenConfig['activity_name'];
                }
                if (!empty($screenConfig['copyright'])) {
                    $result['copyright'] = $screenConfig['copyright'];
                }
                if (!empty($screenConfig['copyrightlink'])) {
                    $result['copyrightlink'] = $screenConfig['copyrightlink'];
                }
                // 处理 logo
                $logoId = intval($screenConfig['logoimg'] ?? 0);
                if ($logoId > 0) {
                    try {
                        $att = Db::connect('huodong')->table('weixin_attachments')
                            ->where('id', $logoId)->find();
                        if ($att && !empty($att['filepath'])) {
                            $result['logoimg'] = ($att['type'] == 1) ? $att['filepath'] : '/huodong/imageproxy.php?id=' . $att['id'];
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        }

        // 如果活动配置为空，尝试从活动#2模板配置读取
        if ((empty($activity->screen_config) || empty($result['activity_name'])) && $activity->id !== 2) {
            try {
                $template = HdActivity::where('id', 2)->find();
                if ($template && !empty($template->screen_config)) {
                    $screenConfig = is_array($template->screen_config)
                        ? $template->screen_config
                        : json_decode($template->screen_config, true);
                    if (is_array($screenConfig)) {
                        if (empty($result['activity_name']) && !empty($screenConfig['activity_name'])) {
                            $result['activity_name'] = $screenConfig['activity_name'];
                        }
                        if (empty($result['logoimg']) && !empty($screenConfig['logoimg'])) {
                            $logoId = intval($screenConfig['logoimg']);
                            if ($logoId > 0) {
                                $att = Db::connect('huodong')->table('weixin_attachments')
                                    ->where('id', $logoId)->find();
                                if ($att && !empty($att['filepath'])) {
                                    $result['logoimg'] = ($att['type'] == 1) ? $att['filepath'] : '/huodong/imageproxy.php?id=' . $att['id'];
                                }
                            }
                        }
                        if (empty($result['copyright']) && !empty($screenConfig['copyright'])) {
                            $result['copyright'] = $screenConfig['copyright'];
                        }
                        if (empty($result['copyrightlink']) && !empty($screenConfig['copyrightlink'])) {
                            $result['copyrightlink'] = $screenConfig['copyrightlink'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 如果仍然为空，尝试从全局配置读取作为备用
        if (empty($activity->screen_config) || empty($result['activity_name'])) {
            try {
                $row = Db::connect('huodong')->table('weixin_wall_config')->order('id', 'asc')->find();
                if ($row) {
                    if (empty($result['activity_name'])) {
                        $result['activity_name'] = $row['activity_name'] ?? '';
                    }
                    // 解析 logoimg 附件ID 为图片URL
                    if (empty($result['logoimg'])) {
                        $logoId = intval($row['logoimg'] ?? 0);
                        if ($logoId > 0) {
                            $att = Db::connect('huodong')->table('weixin_attachments')
                                ->where('id', $logoId)->find();
                            if ($att && !empty($att['filepath'])) {
                                $result['logoimg'] = ($att['type'] == 1) ? $att['filepath'] : '/huodong/imageproxy.php?id=' . $att['id'];
                            }
                        }
                    }
                    if (empty($result['copyright'])) {
                        $result['copyright'] = $row['copyright'] ?? '';
                    }
                    if (empty($result['copyrightlink'])) {
                        $result['copyrightlink'] = $row['copyrightlink'] ?? '';
                    }
                }
            } catch (\Throwable $e) {
                // 表不存在时使用默认值
            }
        }

        return $result;
    }

    // ============================================================
    // 功能页特定变量构建
    // ============================================================

    /**
     * 签到页变量
     */
    private function buildSignPageVars(HdActivity $activity): array
    {
        $count = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->count();

        // 读取签到主题配置
        $sc = $activity->screen_config ?: [];
        $signThemeStyle = $sc['sign_theme_style'] ?? 'classic';

        return [
            'qiandaonum'       => $count,
            'lastid'           => 0,
            'style'            => 'meepo',
            'sign_theme_style' => $signThemeStyle,
        ];
    }

    /**
     * 3D签到页变量
     */
    private function build3dSignPageVars(HdActivity $activity): array
    {
        $participants = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->order('signorder desc')
            ->limit(30)
            ->field('id,nickname,avatar,signorder')
            ->select()
            ->toArray();

        $participants = array_reverse($participants);
        $count = count($participants);
        $maxId = $count > 0 ? $participants[$count - 1]['signorder'] : 0;

        $sc = $activity->screen_config ?: [];

        // 读取datastr：优先screen_config中的threed_datastr，否则从hd_3d_effects表动态生成
        $datastr = '';
        if (!empty($sc['threed_datastr'])) {
            $datastr = $sc['threed_datastr'];
        } else {
            // 从效果表动态生成datastr
            $effects = Db::name('hd_3d_effects')
                ->where('activity_id', $activity->id)
                ->order('sort asc, id asc')
                ->select()
                ->toArray();
            $parts = [];
            foreach ($effects as $eff) {
                switch ($eff['type']) {
                    case 'preset_shape': $parts[] = '#' . $eff['content']; break;
                    case 'image_logo':   $parts[] = '#icon ' . $eff['content']; break;
                    case 'text_logo':    $parts[] = $eff['content']; break;
                    case 'countdown':    $parts[] = '#countdown ' . $eff['content']; break;
                }
            }
            if (!empty($parts)) {
                $datastr = implode('|', $parts);
            }
        }
        // 如果仍然为空，使用默认6种预设效果
        if (empty($datastr)) {
            $datastr = '#sphere|#torus|#grid|#helix|#cylinder|#gene';
        }

        $threedimensionalConfig = [
            'avatargap'  => (int)($sc['threed_avatargap'] ?? 15),
            'avatarsize' => (int)($sc['threed_avatarsize'] ?? 7),
            'avatarnum'  => (int)($sc['threed_avatarnum'] ?? 30),
            'datastr'    => $datastr,
        ];

        $playMode = $sc['threed_play_mode'] ?? 'sequential';

        // 空闲防冷场动画配置
        $idleEnabled = !empty($sc['threed_idle_enabled']) ? 'true' : 'false';
        $idleDelay   = isset($sc['threed_idle_delay']) ? (int)$sc['threed_idle_delay'] : 5000;

        // 突出卡片样式配置
        $cardStyle = $sc['threed_card_style'] ?? 'normal';
        $highlightScale = (float)($sc['threed_highlight_scale'] ?? 3);
        $highlightDuration = (int)($sc['threed_highlight_duration'] ?? 2000);

        return [
            'qd_maxid'                  => $maxId,
            'personJson'                => json_encode($participants, JSON_UNESCAPED_UNICODE),
            'threedimensional_config'   => $threedimensionalConfig,
            'threedimensional_play_mode'=> $playMode,
            'threed_idle_enabled'       => $idleEnabled,
            'threed_idle_delay'         => $idleDelay,
            'threed_card_style'         => $cardStyle,
            'threed_highlight_scale'    => $highlightScale,
            'threed_highlight_duration' => $highlightDuration,
        ];
    }

    /**
     * 上墙页变量
     */
    private function buildWallVars(HdActivity $activity): array
    {
        $participants = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->order('signorder desc')
            ->limit(30)
            ->field('id,nickname,avatar,signorder')
            ->select()
            ->toArray();

        return [
            'personJson'  => json_encode($participants, JSON_UNESCAPED_UNICODE),
            'from'        => 'wall',
            'signlogoimg' => ($activity->screen_config ?: [])['signlogoimg'] ?? '',
        ];
    }

    /**
     * 弹幕页变量
     */
    private function buildDanmuVars(HdActivity $activity): array
    {
        $features = HdActivityFeature::where('activity_id', $activity->id)
            ->where('enabled', 1)
            ->order('sort asc')
            ->select()
            ->toArray();
        $plugs = $this->convertPlugs($features, '/s/' . $activity->access_code);

        return [
            'plugs'      => $plugs,
            'personJson' => '[]',
            'awardlist'  => [],
        ];
    }

    /**
     * 投票页变量
     */
    private function buildVoteVars(HdActivity $activity): array
    {
        $sc = $activity->screen_config ?: [];

        // 获取投票配置
        $voteConfig = [
            'id'          => $activity->id,
            'votetitle'   => $sc['votetitle'] ?? '投票',
            'status'      => $sc['votestatus'] ?? 1,
            'showtype'    => $sc['voteshowtype'] ?? 2,
            'unit'        => $sc['voteunit'] ?? '票',
            'previd'      => null,
            'nextid'      => null,
        ];

        return [
            'vote_config' => $voteConfig,
            'preid'       => null,
            'nextid'      => null,
            'from'        => 'vote',
        ];
    }

    /**
     * 开幕页变量
     */
    private function buildKaimuVars(HdActivity $activity): array
    {
        $kaimuConfig = HdKaimuConfig::where('activity_id', $activity->id)->find();
        $image = '/static/hd/themes/meepo/assets/images/kaimu.png';
        $fullscreen = 1;

        if ($kaimuConfig) {
            $fullscreen = $kaimuConfig->fullscreen ?? 1;
            if (!empty($kaimuConfig->bg_image)) {
                $image = $kaimuConfig->bg_image;
            }
        }

        return [
            'kaimuconfig' => [
                'image'      => $image,
                'fullscreen' => $fullscreen,
            ],
        ];
    }

    /**
     * 闭幕页变量
     */
    private function buildBimuVars(HdActivity $activity): array
    {
        $bimuConfig = HdBimuConfig::where('activity_id', $activity->id)->find();
        $image = '/static/hd/themes/meepo/assets/images/bimu.png';
        $fullscreen = 1;

        if ($bimuConfig) {
            $fullscreen = $bimuConfig->fullscreen ?? 1;
            if (!empty($bimuConfig->bg_image)) {
                $image = $bimuConfig->bg_image;
            }
        }

        return [
            'bimuconfig' => [
                'image'      => $image,
                'fullscreen' => $fullscreen,
            ],
        ];
    }

    /**
     * 相册页变量
     */
    private function buildXiangceVars(HdActivity $activity): array
    {
        $photos = Db::name('hd_attachment')
            ->where('activity_id', $activity->id)
            ->where('category', 'album')
            ->order('id asc')
            ->field('id,file_name,file_path as imagepath')
            ->select()
            ->toArray();

        return [
            'xiangce' => $photos,
        ];
    }

    /**
     * 红包页变量
     */
    private function buildRedpacketVars(HdActivity $activity): array
    {
        $flagCount = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->count();

        $flags = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->order('signorder desc')
            ->limit(6)
            ->field('id,nickname,avatar,signorder')
            ->select()
            ->toArray();

        // 获取当前红包轮次
        $currentRound = Db::name('hd_redpacket_round')
            ->where('activity_id', $activity->id)
            ->where('status', 'in', [1, 2])
            ->order('id asc')
            ->find();

        $roundData = $currentRound ? [
            'id'       => $currentRound['id'],
            'lefttime' => max(0, ($currentRound['end_time'] ?? time()) - time()),
            'status'   => $currentRound['status'],
        ] : ['id' => 0, 'lefttime' => 0, 'status' => 0];

        $roundCount = Db::name('hd_redpacket_round')
            ->where('activity_id', $activity->id)
            ->count();

        $features = HdActivityFeature::where('activity_id', $activity->id)
            ->where('enabled', 1)
            ->order('sort asc')
            ->select()
            ->toArray();
        $plugs = $this->convertPlugs($features, '/s/' . $activity->access_code);

        return [
            'flag_count'             => $flagCount,
            'flags'                  => $flags,
            'currentredpacket_round' => json_encode($roundData, JSON_UNESCAPED_UNICODE),
            'count_redpack'          => $roundCount,
            'plugs'                  => $plugs,
            'from'                   => 'redpacket',
        ];
    }

    /**
     * 幸运号码页变量
     */
    private function buildXyhVars(HdActivity $activity): array
    {
        $sc = $activity->screen_config ?: [];
        return [
            'xingyunhaomaconfig' => [
                'minnum' => $sc['xyh_minnum'] ?? 1,
                'maxnum' => $sc['xyh_maxnum'] ?? 999,
            ],
        ];
    }

    /**
     * 幸运手机号页变量
     */
    private function buildXysjhVars(HdActivity $activity): array
    {
        $participants = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->where('phone', '<>', '')
            ->limit(50)
            ->field('id,phone')
            ->select()
            ->toArray();

        $personlist = [];
        foreach ($participants as $item) {
            $personlist[] = [
                'mobile' => substr_replace($item['phone'], '****', 3, 4),
            ];
        }

        $personcount = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->where('phone', '<>', '')
            ->count();

        return [
            'personJson'  => json_encode($personlist, JSON_UNESCAPED_UNICODE),
            'personcount' => $personcount,
        ];
    }

    /**
     * 单页变量
     */
    private function buildDanyeVars(HdActivity $activity): array
    {
        $sc = $activity->screen_config ?: [];
        return [
            'danyedata' => [
                'img' => $sc['danye_image'] ?? '',
            ],
            'kaimuconfig' => [
                'fullscreen' => $sc['danye_fullscreen'] ?? 1,
                'image'      => $sc['danye_image'] ?? '',
            ],
            'config' => json_encode($sc['danye_config'] ?? new \stdClass()),
        ];
    }

    /**
     * 对对碰变量
     */
    private function buildDdpVars(HdActivity $activity): array
    {
        $participants = HdParticipant::where('activity_id', $activity->id)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->field('id,nickname,avatar,signorder')
            ->select()
            ->toArray();

        return [
            'personJson' => json_encode($participants, JSON_UNESCAPED_UNICODE),
        ];
    }
}

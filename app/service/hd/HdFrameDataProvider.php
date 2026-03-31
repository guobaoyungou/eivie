<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdBusinessConfig;
use app\model\hd\HdMusic;
use app\model\hd\HdBackground;
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

        // 信息显示开关配置
        $displayConfig = $this->getDisplayConfig($activity->bid);

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
     * 背景图 JSON
     */
    private function convertBackgroundJson(int $activityId): string
    {
        $list = HdBackground::where('activity_id', $activityId)
            ->order('id asc')
            ->select()
            ->toArray();
        return json_encode($list, JSON_UNESCAPED_UNICODE) ?: '[]';
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
     * 获取信息显示开关配置
     */
    private function getDisplayConfig(int $bid): array
    {
        $defaults = [
            'show_company_name'  => '1',
            'show_activity_name' => '1',
            'show_copyright'     => '1',
        ];

        $config = Db::name('hd_business_config')
            ->where('bid', $bid)
            ->find();

        if ($config) {
            foreach ($defaults as $key => &$val) {
                if (isset($config[$key]) && $config[$key] !== '') {
                    $val = (string)$config[$key];
                }
            }
            unset($val);
        }

        return $defaults;
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

        return [
            'qiandaonum' => $count,
            'lastid'     => 0,
            'style'      => 'meepo',
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
        $threedimensionalConfig = [
            'avatargap'  => $sc['3d_avatargap'] ?? 10,
            'avatarsize' => $sc['3d_avatarsize'] ?? 80,
            'avatarnum'  => $sc['3d_avatarnum'] ?? 160,
            'datastr'    => $sc['3d_datastr'] ?? '#earth',
        ];

        return [
            'qd_maxid'                => $maxId,
            'personJson'              => json_encode($participants, JSON_UNESCAPED_UNICODE),
            'threedimensional_config' => $threedimensionalConfig,
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

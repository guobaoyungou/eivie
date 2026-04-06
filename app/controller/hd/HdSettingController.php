<?php
declare(strict_types=1);

namespace app\controller\hd;

use think\facade\Db;
use app\model\hd\HdActivity;
use app\model\hd\HdBusinessConfig;

/**
 * 大屏互动 - 系统设置控制器
 * 商家级配置：公众号、套餐查看、活动全局设置
 */
class HdSettingController extends HdBaseController
{
    /**
     * 获取系统设置（商家级）
     */
    public function index()
    {
        $bid = $this->getBid();

        $business = Db::name('business')->where('id', $bid)->find();
        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        $plan = null;
        if ($bizConfig && $bizConfig->plan_id) {
            $plan = Db::name('hd_plan')->where('id', $bizConfig->plan_id)->find();
        }

        return json([
            'code' => 0,
            'data' => [
                'business' => [
                    'name'    => $business['name'] ?? '',
                    'tel'     => $business['tel'] ?? '',
                    'linkman' => $business['linkman'] ?? '',
                    'logo'    => $business['logo'] ?? '',
                    'address' => $business['address'] ?? '',
                ],
                'wxfw' => [
                    'appid'     => $bizConfig->wxfw_appid ?? '',
                    'appsecret' => $bizConfig->wxfw_appsecret ? '******' : '',
                ],
                'wx_server' => [
                    'callback_url' => 'https://wxhd.eivie.cn/api/hd/wx/callback',
                    'token'        => $this->getWxVerifyToken(),
                    'encoding_mode'=> '明文模式',
                ],
                'plan' => $plan ? [
                    'name'             => $plan['name'],
                    'max_stores'       => $plan['max_stores'],
                    'max_activities'   => $plan['max_activities'],
                    'max_participants' => $plan['max_participants'],
                    'expire_time'      => $bizConfig->plan_expire_time,
                    'is_valid'         => $bizConfig->isPlanValid(),
                ] : null,
            ],
        ]);
    }

    /**
     * 更新商家信息
     */
    public function updateBusiness()
    {
        $bid = $this->getBid();
        $data = input('post.');

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['tel'])) $updateData['tel'] = $data['tel'];
        if (isset($data['linkman'])) $updateData['linkman'] = $data['linkman'];
        if (isset($data['logo'])) $updateData['logo'] = $data['logo'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];

        if ($updateData) {
            Db::name('business')->where('id', $bid)->update($updateData);
        }

        return json(['code' => 0, 'msg' => '商家信息已更新']);
    }

    /**
     * 更新公众号配置
     */
    public function updateWxConfig()
    {
        $bid = $this->getBid();

        $appid = input('post.appid', '');
        $appsecret = input('post.appsecret', '');

        $bizConfig = HdBusinessConfig::where('bid', $bid)->find();
        if (!$bizConfig) {
            return json(['code' => 1, 'msg' => '商家配置不存在']);
        }

        $bizConfig->wxfw_appid = $appid;
        if (!empty($appsecret) && $appsecret !== '******') {
            $bizConfig->wxfw_appsecret = $appsecret;
        }
        $bizConfig->updatetime = time();
        $bizConfig->save();

        return json(['code' => 0, 'msg' => '公众号配置已更新']);
    }

    /**
     * 修改密码
     */
    public function changePassword()
    {
        $userId = $this->getUserId();
        $oldPwd = input('post.old_password', '');
        $newPwd = input('post.new_password', '');

        if (empty($oldPwd) || empty($newPwd)) {
            return json(['code' => 1, 'msg' => '密码不能为空']);
        }

        $user = Db::name('admin_user')->where('id', $userId)->find();
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        if ($user['pwd'] !== md5($oldPwd)) {
            return json(['code' => 1, 'msg' => '原密码错误']);
        }

        Db::name('admin_user')->where('id', $userId)->update(['pwd' => md5($newPwd)]);

        return json(['code' => 0, 'msg' => '密码修改成功']);
    }

    /**
     * 获取微信服务器验证 Token
     */
    private function getWxVerifyToken(): string
    {
        try {
            $mp = Db::name('admin_setapp_mp')->order('id asc')->find();
            if ($mp && !empty($mp['token'])) {
                return $mp['token'];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 'hd_wx_callback_token';
    }

    /**
     * 获取地图API Key（前端加载地图组件用）
     */
    public function mapKey()
    {
        $mapKey = \app\common\Common::getSysset('webinfo', 'map_key_qq');
        return json([
            'code' => 0,
            'data' => [
                'map_key' => $mapKey ?: '',
                'type'    => 'qq',
            ],
        ]);
    }

    /**
     * 地点搜索（关键词输入提示）
     * 通过后端 WebService API 代理搜索，避免前端 JS API 域名限制
     */
    public function placeSearch()
    {
        $keyword = input('get.keyword', '');
        if (empty($keyword)) {
            return json(['code' => 1, 'msg' => '请输入搜索关键词', 'data' => []]);
        }

        $mapQQ = new \app\common\MapQQ();
        $result = $mapQQ->suggestionPlace($keyword);

        if ($result['status'] == 1 && !empty($result['data'])) {
            $places = [];
            foreach ($result['data'] as $item) {
                $places[] = [
                    'title'    => $item['title'] ?? '',
                    'address'  => $item['address'] ?? '',
                    'province' => $item['province'] ?? '',
                    'city'     => $item['city'] ?? '',
                    'district' => $item['district'] ?? '',
                    'lat'      => $item['location']['lat'] ?? 0,
                    'lng'      => $item['location']['lng'] ?? 0,
                ];
            }
            return json(['code' => 0, 'data' => $places]);
        }

        return json(['code' => 0, 'data' => []]);
    }

    /**
     * 逆地理编码（经纬度→地址）
     */
    public function reverseGeo()
    {
        $lat = input('get.lat', '');
        $lng = input('get.lng', '');
        if (empty($lat) || empty($lng)) {
            return json(['code' => 1, 'msg' => '请提供坐标']);
        }

        $mapQQ = new \app\common\MapQQ();
        $result = $mapQQ->getAreaByLocation($lat, $lng);

        if ($result['status'] == 1) {
            $address = $result['address'] ?: ($result['province'] . $result['city'] . $result['district']);
            return json([
                'code' => 0,
                'data' => [
                    'address'  => $address,
                    'province' => $result['province'] ?? '',
                    'city'     => $result['city'] ?? '',
                    'district' => $result['district'] ?? '',
                    'street'   => $result['street'] ?? '',
                    'landmark' => $result['landmark'] ?? '',
                ],
            ]);
        }

        return json(['code' => 1, 'msg' => '获取地址失败']);
    }

    /**
     * 获取大屏显示设置
     * GET /api/hd/setting/display?activity_id=xxx
     */
    public function displayConfig()
    {
        $activityId = (int)input('get.activity_id', 0);

        // 如果没有传 activity_id，尝试获取当前活动的
        if ($activityId <= 0) {
            $activityId = $this->getCurrentActivityId();
        }

        // 从 hd_activity.screen_config 读取活动级别的配置
        $displaySettings = $this->getActivityDisplaySettings($activityId);

        return json([
            'code' => 0,
            'data' => [
                'activity_id'        => $activityId,
                'activity_name'      => $displaySettings['activity_name'] ?? '',
                'copyright'          => $displaySettings['copyright'] ?? '',
                'logoimg'            => $displaySettings['logoimg'] ?? 0,
                'logo_url'           => $displaySettings['logo_url'] ?? '',
                'show_logo'          => $displaySettings['show_logo'] ?? '1',
                'show_activity_name' => $displaySettings['show_activity_name'] ?? '1',
                'show_copyright'     => $displaySettings['show_copyright'] ?? '1',
            ],
        ]);
    }

    /**
     * 更新大屏显示设置
     * POST /api/hd/setting/display
     * 需要传入 activity_id
     */
    public function updateDisplayConfig()
    {
        $data = input('post.');
        $activityId = isset($data['activity_id']) ? (int)$data['activity_id'] : 0;

        if ($activityId <= 0) {
            $activityId = $this->getCurrentActivityId();
        }

        if ($activityId <= 0) {
            return json(['code' => 1, 'msg' => '请先选择活动']);
        }

        // 验证活动归属
        $activity = HdActivity::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('id', $activityId)
            ->find();
        if (!$activity) {
            return json(['code' => 1, 'msg' => '活动不存在或无权访问']);
        }

        // 读取现有的 screen_config
        $screenConfig = [];
        if (!empty($activity->screen_config)) {
            $screenConfig = is_array($activity->screen_config)
                ? $activity->screen_config
                : (json_decode($activity->screen_config, true) ?: []);
        }

        // 更新显示设置相关字段
        if (isset($data['activity_name'])) {
            $screenConfig['activity_name'] = strval($data['activity_name']);
        }
        if (isset($data['copyright'])) {
            $screenConfig['copyright'] = strval($data['copyright']);
        }
        if (isset($data['logoimg'])) {
            $screenConfig['logoimg'] = intval($data['logoimg']);
        }
        if (isset($data['logo_url'])) {
            $screenConfig['logo_url'] = strval($data['logo_url']);
        }
        if (isset($data['show_logo'])) {
            $screenConfig['show_logo'] = intval($data['show_logo']);
        }
        if (isset($data['show_activity_name'])) {
            $screenConfig['show_activity_name'] = intval($data['show_activity_name']);
        }
        if (isset($data['show_copyright'])) {
            $screenConfig['show_copyright'] = intval($data['show_copyright']);
        }

        // 保存到活动配置
        $activity->screen_config = $screenConfig;
        $activity->save();

        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 上传活动LOGO
     * POST /api/hd/setting/display-logo
     */
    public function uploadDisplayLogo()
    {
        $file = request()->file('logo');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请选择LOGO图片']);
        }

        // 保存文件到 huodong/data/upload/
        $savePath = app()->getRootPath() . 'huodong/data/upload/';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $ext = $file->getOriginalExtension();
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($ext), $allowed)) {
            return json(['code' => 1, 'msg' => '仅支持 jpg/png/gif/webp 格式']);
        }

        $fileName = 'logo_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $file->move($savePath, $fileName);
        $filePath = '/huodong/data/upload/' . $fileName;

        $db = Db::connect('huodong');

        // 插入附件记录
        $attId = $db->table('weixin_attachments')->insertGetId([
            'type'     => 1,
            'filepath' => $filePath,
        ]);

        // 更新活动配置
        $activityId = (int)input('post.activity_id', 0);
        if ($activityId <= 0) {
            $activityId = $this->getCurrentActivityId();
        }
        $this->updateActivityLogo($activityId, $attId, $filePath);

        return json([
            'code' => 0,
            'msg'  => 'LOGO上传成功',
            'data' => [
                'logo_url' => $filePath,
                'logoimg'  => $attId,
            ],
        ]);
    }

    /**
     * 删除活动LOGO
     * POST /api/hd/setting/display-logo-delete
     */
    public function deleteDisplayLogo()
    {
        $activityId = (int)input('post.activity_id', 0);
        if ($activityId <= 0) {
            $activityId = $this->getCurrentActivityId();
        }
        $this->updateActivityLogo($activityId, 0, '');
        return json(['code' => 0, 'msg' => 'LOGO已删除']);
    }

    /**
     * 从 hd_activity.screen_config 获取活动的大屏显示设置
     * 如果当前活动没有配置，则使用活动 #2 作为默认值
     */
    private function getActivityDisplaySettings(int $activityId): array
    {
        $defaults = [
            'activity_name'      => '',
            'copyright'          => '',
            'logoimg'            => 0,
            'logo_url'           => '',
            'show_logo'          => '1',
            'show_activity_name' => '1',
            'show_copyright'     => '1',
        ];

        // 默认模板活动ID
        $templateActivityId = 2;

        // 获取配置的函数
        $loadConfig = function (int $actId) use ($defaults): array {
            $result = $defaults;
            if ($actId <= 0) {
                return $result;
            }

            try {
                $activity = HdActivity::where('id', $actId)->find();
                if ($activity) {
                    $configRaw = $activity->getData('screen_config');
                    if (!empty($configRaw)) {
                        $decoded = json_decode($configRaw, true);
                        if (is_array($decoded)) {
                            foreach (array_keys($defaults) as $key) {
                                if (isset($decoded[$key])) {
                                    $result[$key] = $decoded[$key];
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            return $result;
        };

        if ($activityId > 0) {
            // 尝试获取当前活动的配置
            $result = $loadConfig($activityId);

            // 检查当前活动是否有显示设置配置
            $hasDisplayConfig = !empty($result['activity_name'])
                || !empty($result['copyright'])
                || !empty($result['logoimg'])
                || !empty($result['logo_url']);

            if (!$hasDisplayConfig && $activityId !== $templateActivityId) {
                // 当前活动没有配置，使用模板活动 #2 作为默认值
                $templateConfig = $loadConfig($templateActivityId);
                foreach (array_keys($defaults) as $key) {
                    // 只有当前活动的值为空时才使用模板值
                    if (empty($result[$key]) && !empty($templateConfig[$key])) {
                        $result[$key] = $templateConfig[$key];
                    }
                }
            }

            // 解析 logoimg 附件ID 为图片URL（如果 logo_url 不存在）
            if (empty($result['logo_url']) && !empty($result['logoimg'])) {
                $logoId = intval($result['logoimg']);
                if ($logoId > 0) {
                    try {
                        $att = Db::connect('huodong')->table('weixin_attachments')
                            ->where('id', $logoId)->find();
                        if ($att && !empty($att['filepath'])) {
                            $result['logo_url'] = ($att['type'] == 1)
                                ? $att['filepath']
                                : '/huodong/imageproxy.php?id=' . $att['id'];
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }

            return $result;
        }

        // 没有活动ID，使用模板活动 #2 作为默认值
        return $loadConfig($templateActivityId);
    }

    /**
     * 更新活动的LOGO配置
     */
    private function updateActivityLogo(int $activityId, int $logoImgId, string $logoUrl)
    {
        if ($activityId <= 0) {
            return;
        }

        try {
            $activity = HdActivity::where('id', $activityId)->find();
            if (!$activity) {
                return;
            }

            $screenConfig = [];
            if (!empty($activity->screen_config)) {
                $screenConfig = is_array($activity->screen_config)
                    ? $activity->screen_config
                    : (json_decode($activity->screen_config, true) ?: []);
            }

            $screenConfig['logoimg'] = $logoImgId;
            $screenConfig['logo_url'] = $logoUrl;

            $activity->screen_config = $screenConfig;
            $activity->save();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * 获取当前活动ID（从请求头或会话中）
     */
    private function getCurrentActivityId(): int
    {
        // 尝试从请求头获取（前端会传递）
        $activityId = (int)request()->header('X-Activity-Id', 0);
        if ($activityId > 0) {
            return $activityId;
        }

        // 尝试从请求参数获取
        $activityId = (int)input('get.activity_id', 0);
        if ($activityId > 0) {
            return $activityId;
        }

        // 尝试从已登录用户获取默认活动
        try {
            $activity = HdActivity::where('aid', $this->getAid())
                ->where('bid', $this->getBid())
                ->order('id', 'desc')
                ->find();
            if ($activity) {
                return (int)$activity->id;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return 0;
    }

    /**
     * 获取手机端入口地址（基于当前活动的 access_code 生成）
     * GET /api/hd/setting/mobile-urls?activity_id=xxx
     */
    public function mobileUrls()
    {
        $activityId = (int)input('get.activity_id', 0);
        $domain = 'https://wxhd.eivie.cn';

        if ($activityId > 0) {
            $activity = HdActivity::where('aid', $this->getAid())
                ->where('bid', $this->getBid())
                ->where('id', $activityId)
                ->find();
        } else {
            // 取第一个活动
            $activity = HdActivity::where('aid', $this->getAid())
                ->where('bid', $this->getBid())
                ->order('id desc')
                ->find();
        }

        if (!$activity || empty($activity->access_code)) {
            return json(['code' => 1, 'msg' => '未找到活动']);
        }

        $accessCode = $activity->access_code;
        $entryUrl = $domain . '/s/' . $accessCode;

        return json([
            'code' => 0,
            'data' => [
                'access_code' => $accessCode,
                'domain'      => $domain,
                'screen_url'  => $entryUrl,
                'urls'        => [
                    ['label' => '签到地址（常用）', 'url' => $entryUrl, 'key' => 'qiandao'],
                    ['label' => '上墙地址',         'url' => $entryUrl . '?f=wall', 'key' => 'wall'],
                    ['label' => '投票地址',         'url' => $entryUrl . '?f=vote', 'key' => 'vote'],
                    ['label' => '红包地址',         'url' => $entryUrl . '?f=redpacket', 'key' => 'redpacket'],
                    ['label' => '中奖结果地址',     'url' => $entryUrl . '?f=cjresult', 'key' => 'cjresult'],
                ],
                'qrcode_text' => $entryUrl,
            ],
        ]);
    }
}

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

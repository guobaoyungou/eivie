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
}

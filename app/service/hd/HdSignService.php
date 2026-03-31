<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Log;
use app\model\hd\HdActivity;
use app\model\hd\HdActivityFeature;
use app\model\hd\HdParticipant;

/**
 * 大屏互动 - 签到管理服务
 * 功能：签到设置、签到名单、手机页面设计
 */
class HdSignService
{
    /**
     * 获取签到设置
     */
    public function getSignConfig(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];
        $signConfig = [
            'sign_match_mode'       => $screenConfig['sign_match_mode'] ?? 1,
            'sign_verify_mode'      => $screenConfig['sign_verify_mode'] ?? 1,
            'sign_show_style'       => $screenConfig['sign_show_style'] ?? 1,
            'sign_bg_image'         => $screenConfig['sign_bg_image'] ?? '',
            'sign_music'            => $screenConfig['sign_music'] ?? '',
            'sign_location_enabled' => (int)($screenConfig['sign_location_enabled'] ?? 0),
            'sign_latitude'         => (float)($screenConfig['sign_latitude'] ?? 0),
            'sign_longitude'        => (float)($screenConfig['sign_longitude'] ?? 0),
            'sign_radius'           => (int)($screenConfig['sign_radius'] ?? 1000),
            'sign_address'          => $screenConfig['sign_address'] ?? '',
            // 员工号设置
            'show_employee_no'      => (int)($screenConfig['show_employee_no'] ?? 0),
            'require_employee_no'   => (int)($screenConfig['require_employee_no'] ?? 0),
            // 上传照片设置
            'show_photo'            => (int)($screenConfig['show_photo'] ?? 0),
            'require_photo'         => (int)($screenConfig['require_photo'] ?? 0),
            // 自定义字段设置
            'show_custom_fields'    => (int)($screenConfig['show_custom_fields'] ?? 0),
            'sign_custom_fields'    => $screenConfig['sign_custom_fields'] ?? [],
        ];

        // 获取签到功能配置（qdq feature）
        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'qdq')
            ->find();

        return [
            'code' => 0,
            'data' => [
                'config'      => $signConfig,
                'feature'     => $feature ? $feature->toArray() : null,
                'verifycode'  => $activity->verifycode,
            ],
        ];
    }

    /**
     * 更新签到设置
     */
    public function updateSignConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];
        $allowedKeys = ['sign_match_mode', 'sign_verify_mode', 'sign_show_style', 'sign_bg_image', 'sign_music',
                        'sign_location_enabled', 'sign_latitude', 'sign_longitude', 'sign_radius', 'sign_address',
                        'show_employee_no', 'require_employee_no', 'show_photo', 'require_photo',
                        'show_custom_fields', 'sign_custom_fields'];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $screenConfig[$key] = $data[$key];
            }
        }
        $activity->screen_config = $screenConfig;

        if (isset($data['verifycode'])) {
            $activity->verifycode = $data['verifycode'];
        }

        $activity->save();

        return ['code' => 0, 'msg' => '签到设置已更新'];
    }

    /**
     * 获取签到名单（分页）
     */
    public function getSignList(int $aid, int $bid, int $activityId, array $params = []): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['activity_id', '=', $activityId],
        ];

        if (isset($params['flag']) && $params['flag'] !== '') {
            $where[] = ['flag', '=', (int)$params['flag']];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['nickname|phone|signname|employee_no', 'like', '%' . $params['keyword'] . '%'];
        }

        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 50);

        $list = HdParticipant::where($where)
            ->page($page, $limit)
            ->order('signorder asc, id desc')
            ->select()
            ->toArray();

        // 解析 custom_data JSON
        foreach ($list as &$item) {
            if (!empty($item['custom_data'])) {
                $item['custom_data_parsed'] = json_decode($item['custom_data'], true) ?: [];
            } else {
                $item['custom_data_parsed'] = [];
            }
        }
        unset($item);

        $count = HdParticipant::where($where)->count();
        $signedCount = HdParticipant::where($where)
            ->where('flag', HdParticipant::FLAG_SIGNED)
            ->count();

        // 获取当前活动的自定义字段定义
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        $screenConfig = $activity ? ($activity->screen_config ?: []) : [];
        $customFieldDefs = $screenConfig['sign_custom_fields'] ?? [];

        return [
            'code' => 0,
            'data' => [
                'list'              => $list,
                'count'             => $count,
                'signed_count'      => $signedCount,
                'custom_field_defs' => $customFieldDefs,
            ],
        ];
    }

    /**
     * 删除签到记录
     */
    public function deleteParticipant(int $aid, int $bid, int $activityId, int $id): array
    {
        $participant = HdParticipant::where('aid', $aid)
            ->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->where('id', $id)
            ->find();

        if (!$participant) {
            return ['code' => 1, 'msg' => '记录不存在'];
        }

        $participant->delete();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 清空签到名单
     */
    public function clearSignList(int $aid, int $bid, int $activityId): array
    {
        HdParticipant::where('aid', $aid)
            ->where('bid', $bid)
            ->where('activity_id', $activityId)
            ->delete();

        return ['code' => 0, 'msg' => '名单已清空'];
    }

    /**
     * 获取手机页面设计配置
     */
    public function getMobilePageConfig(int $aid, int $bid, int $activityId): array
    {
        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'qdq')
            ->find();

        $config = $feature ? ($feature->config ?: []) : [];

        return [
            'code' => 0,
            'data' => [
                // 旧字段（保留兼容）
                'mobile_bg'            => $config['mobile_bg'] ?? '',
                'mobile_logo'          => $config['mobile_logo'] ?? '',
                'mobile_title'         => $config['mobile_title'] ?? '',
                'mobile_subtitle'      => $config['mobile_subtitle'] ?? '',
                'mobile_btn_color'     => $config['mobile_btn_color'] ?? '#ff4444',
                // 新字段
                'mobile_bg_image'      => $config['mobile_bg_image'] ?? '',
                'mobile_activity_image' => $config['mobile_activity_image'] ?? '',
                'mobile_hide_avatar'   => (int)($config['mobile_hide_avatar'] ?? 0),
                'mobile_quick_message' => (int)($config['mobile_quick_message'] ?? 0),
                'mobile_welcome_text'  => $config['mobile_welcome_text'] ?? '欢迎参与本次活动',
                'mobile_btn_text'      => $config['mobile_btn_text'] ?? '参 与 活 动',
                'mobile_btn_image'     => $config['mobile_btn_image'] ?? '',
            ],
        ];
    }

    /**
     * 更新手机页面设计配置
     */
    public function updateMobilePageConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        // 校验欢迎语长度
        if (isset($data['mobile_welcome_text']) && mb_strlen($data['mobile_welcome_text']) > 100) {
            return ['code' => 1, 'msg' => '欢迎语不超过100字'];
        }
        // 校验按钮名称长度
        if (isset($data['mobile_btn_text']) && mb_strlen($data['mobile_btn_text']) > 20) {
            return ['code' => 1, 'msg' => '按钮名称不超过20字'];
        }

        $feature = HdActivityFeature::where('activity_id', $activityId)
            ->where('feature_code', 'qdq')
            ->find();

        if (!$feature) {
            $feature = new HdActivityFeature();
            $feature->aid = $aid;
            $feature->bid = $bid;
            $feature->activity_id = $activityId;
            $feature->feature_code = 'qdq';
            $feature->enabled = 1;
            $feature->sort = 1;
        }

        $config = $feature->config ?: [];
        $allowedKeys = [
            'mobile_bg', 'mobile_logo', 'mobile_title', 'mobile_subtitle', 'mobile_btn_color',
            'mobile_bg_image', 'mobile_activity_image', 'mobile_hide_avatar',
            'mobile_quick_message', 'mobile_welcome_text', 'mobile_btn_text', 'mobile_btn_image',
        ];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $config[$key] = $data[$key];
            }
        }
        $feature->config = $config;
        $feature->save();

        return ['code' => 0, 'msg' => '手机页面设计已更新'];
    }

    /**
     * 获取大屏密码配置
     */
    public function getScreenPasswordConfig(int $aid, int $bid, int $activityId): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];

        return [
            'code' => 0,
            'data' => [
                'screen_password_enabled' => (int)($screenConfig['screen_password_enabled'] ?? 1),
                'screen_password'         => $screenConfig['screen_password'] ?? 'eivie',
            ],
        ];
    }

    /**
     * 更新大屏密码配置
     */
    public function updateScreenPasswordConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $activity = HdActivity::where('aid', $aid)->where('bid', $bid)->where('id', $activityId)->find();
        if (!$activity) {
            return ['code' => 1, 'msg' => '活动不存在'];
        }

        $screenConfig = $activity->screen_config ?: [];

        if (isset($data['screen_password_enabled'])) {
            $screenConfig['screen_password_enabled'] = (int)$data['screen_password_enabled'];
        }
        if (isset($data['screen_password'])) {
            $pwd = trim($data['screen_password']);
            if ($pwd === '') {
                return ['code' => 1, 'msg' => '密码不能为空'];
            }
            if (mb_strlen($pwd) > 32) {
                return ['code' => 1, 'msg' => '密码不超过32个字符'];
            }
            $screenConfig['screen_password'] = $pwd;
        }

        $activity->screen_config = $screenConfig;
        $activity->save();

        return ['code' => 0, 'msg' => '密码设置已更新'];
    }
}

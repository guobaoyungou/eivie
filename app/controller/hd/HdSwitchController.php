<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\model\hd\HdActivityFeature;
use app\service\hd\HdActivityService;

/**
 * 大屏互动 - 功能开关控制器
 * 统一管理各功能模块的启用/禁用
 */
class HdSwitchController extends HdBaseController
{
    /**
     * 获取所有功能开关状态
     */
    public function index(int $activity_id)
    {
        $features = HdActivityFeature::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('activity_id', $activity_id)
            ->order('sort asc')
            ->select()
            ->toArray();

        // 补充功能名称
        foreach ($features as &$f) {
            $f['feature_name'] = HdActivityService::ALL_FEATURES[$f['feature_code']] ?? $f['feature_code'];
        }
        unset($f);

        return json(['code' => 0, 'data' => ['list' => $features]]);
    }

    /**
     * 批量更新功能开关
     */
    public function batchUpdate(int $activity_id)
    {
        $switches = input('post.switches/a', []);
        if (empty($switches)) {
            return json(['code' => 1, 'msg' => '参数不能为空']);
        }

        $aid = $this->getAid();
        $bid = $this->getBid();

        foreach ($switches as $item) {
            $code = $item['feature_code'] ?? '';
            $enabled = (int)($item['enabled'] ?? 1);

            if (empty($code)) continue;

            $feature = HdActivityFeature::where('activity_id', $activity_id)
                ->where('feature_code', $code)->find();

            if ($feature) {
                HdActivityFeature::where('id', $feature->id)->update(['enabled' => $enabled]);
            } else {
                HdActivityFeature::create([
                    'aid'          => $aid,
                    'bid'          => $bid,
                    'activity_id'  => $activity_id,
                    'feature_code' => $code,
                    'enabled'      => $enabled,
                    'config'       => json_encode([]),
                    'sort'         => 99,
                ]);
            }
        }

        return json(['code' => 0, 'msg' => '功能开关已更新']);
    }

    /**
     * 单个功能开关切换
     */
    public function toggle(int $activity_id, string $code)
    {
        $feature = HdActivityFeature::where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->where('activity_id', $activity_id)
            ->where('feature_code', $code)
            ->find();

        if (!$feature) {
            return json(['code' => 1, 'msg' => '功能不存在']);
        }

        // 显式计算新状态，使用直接 update 避免 Model 脏检查遗漏
        $newEnabled = ((int)$feature->enabled === 1) ? 0 : 1;
        HdActivityFeature::where('id', $feature->id)->update(['enabled' => $newEnabled]);

        return json([
            'code' => 0,
            'msg'  => $newEnabled ? '已启用' : '已禁用',
            'data' => ['enabled' => $newEnabled],
        ]);
    }
}

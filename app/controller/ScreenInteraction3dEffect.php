<?php
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\model\hd\HdThreeDEffect;

/**
 * Backstage后台 - 3D签到特效管理
 * 管理 ddwx_hd_3d_effects 表的效果条目（type/content/sort）
 * 管理 ddwx_hd_activity.screen_config 中的全局3D配置（threed_avatarnum等）
 */
class ScreenInteraction3dEffect extends Common
{
    /**
     * 特效列表页
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $activityId = input('activity_id', '');

            $where = [];
            $where[] = ['e.aid', '=', aid];
            if ($activityId !== '') {
                $where[] = ['e.activity_id', '=', intval($activityId)];
            }

            $count = Db::name('hd_3d_effects')->alias('e')->where($where)->count();
            $list = Db::name('hd_3d_effects')->alias('e')
                ->leftJoin('hd_activity a', 'e.activity_id = a.id')
                ->field('e.*, a.title as activity_title')
                ->where($where)
                ->order('e.activity_id asc, e.sort asc, e.id asc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            foreach ($list as &$item) {
                $item['type_text'] = $this->getTypeText($item['type']);
                $item['content_display'] = $this->getContentDisplay($item);
                $item['created_at_text'] = $item['created_at'] ? date('Y-m-d H:i', $item['created_at']) : '-';
            }
            unset($item);

            return json(['code' => 0, 'count' => $count, 'data' => $list]);
        }

        // 获取活动列表供下拉筛选
        $activities = Db::name('hd_activity')
            ->where('aid', aid)
            ->field('id, title')
            ->order('id desc')
            ->select()
            ->toArray();

        View::assign('activities', $activities);
        return View::fetch();
    }

    /**
     * 新增/编辑特效弹窗
     */
    public function edit()
    {
        $id = input('id', 0);
        $info = [];

        if ($id) {
            $info = Db::name('hd_3d_effects')->where('id', $id)->where('aid', aid)->find();
        }

        if (!$info) {
            $info = [
                'id'          => 0,
                'activity_id' => intval(input('activity_id', 0)),
                'type'        => 'preset_shape',
                'content'     => '',
                'sort'        => 0,
            ];
        }

        if ($this->request->isPost()) {
            return $this->saveEffect();
        }

        // 获取活动列表
        $activities = Db::name('hd_activity')
            ->where('aid', aid)
            ->field('id, title')
            ->order('id desc')
            ->select()
            ->toArray();

        // 预设造型列表
        $presetShapes = HdThreeDEffect::presetShapes();

        View::assign('info', $info);
        View::assign('activities', $activities);
        View::assign('presetShapes', $presetShapes);
        return View::fetch();
    }

    /**
     * 保存特效
     */
    private function saveEffect()
    {
        $id = intval(input('post.id', 0));
        $activityId = intval(input('post.activity_id', 0));
        $type = input('post.type', '');
        $content = trim(input('post.content', ''));
        $sort = intval(input('post.sort', 0));

        if (!$activityId) {
            return json(['status' => 0, 'msg' => '请选择活动']);
        }

        // 验证活动归属
        $activity = Db::name('hd_activity')->where('id', $activityId)->where('aid', aid)->find();
        if (!$activity) {
            return json(['status' => 0, 'msg' => '活动不存在']);
        }

        // 验证类型
        if (!in_array($type, HdThreeDEffect::allowedTypes())) {
            return json(['status' => 0, 'msg' => '无效的效果类型']);
        }

        // 验证内容
        if ($type === HdThreeDEffect::TYPE_PRESET_SHAPE) {
            if (!array_key_exists($content, HdThreeDEffect::presetShapes())) {
                return json(['status' => 0, 'msg' => '无效的预设造型']);
            }
        } elseif ($type === HdThreeDEffect::TYPE_TEXT_LOGO) {
            if (empty($content) || mb_strlen($content) > 20) {
                return json(['status' => 0, 'msg' => '文字内容不能为空且不超过20字']);
            }
        } elseif ($type === HdThreeDEffect::TYPE_IMAGE_LOGO) {
            if (empty($content)) {
                return json(['status' => 0, 'msg' => '请填写Logo图片URL']);
            }
        } elseif ($type === HdThreeDEffect::TYPE_COUNTDOWN) {
            $seconds = (int)$content;
            if ($seconds < 3 || $seconds > 300) {
                return json(['status' => 0, 'msg' => '倒计时秒数须在3-300之间']);
            }
            $content = (string)$seconds;
        }

        // 自动计算 sort
        if ($sort <= 0) {
            $maxSort = Db::name('hd_3d_effects')
                ->where('activity_id', $activityId)
                ->max('sort') ?: 0;
            $sort = $maxSort + 1;
        }

        $data = [
            'aid'         => aid,
            'bid'         => $activity['bid'],
            'activity_id' => $activityId,
            'type'        => $type,
            'content'     => $content,
            'sort'        => $sort,
        ];

        if ($id) {
            $exists = Db::name('hd_3d_effects')->where('id', $id)->where('aid', aid)->find();
            if (!$exists) {
                return json(['status' => 0, 'msg' => '记录不存在']);
            }
            Db::name('hd_3d_effects')->where('id', $id)->update($data);
        } else {
            $data['is_default'] = 0;
            $data['created_at'] = time();
            $id = Db::name('hd_3d_effects')->insertGetId($data);
        }

        // 同步 datastr
        $this->syncDatastr($activityId);

        return json(['status' => 1, 'msg' => '保存成功', 'data' => ['id' => $id]]);
    }

    /**
     * 删除特效
     */
    public function del()
    {
        $id = intval(input('post.id', 0));
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $effect = Db::name('hd_3d_effects')->where('id', $id)->where('aid', aid)->find();
        if (!$effect) {
            return json(['status' => 0, 'msg' => '记录不存在']);
        }

        // 保底检查：至少保留一个效果
        $count = Db::name('hd_3d_effects')->where('activity_id', $effect['activity_id'])->count();
        if ($count <= 1) {
            return json(['status' => 0, 'msg' => '至少保留一个效果']);
        }

        Db::name('hd_3d_effects')->where('id', $id)->delete();

        // 同步 datastr
        $this->syncDatastr($effect['activity_id']);

        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 批量初始化默认效果（为活动初始化6个预设造型）
     */
    public function initDefaults()
    {
        $activityId = intval(input('post.activity_id', 0));
        if (!$activityId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $activity = Db::name('hd_activity')->where('id', $activityId)->where('aid', aid)->find();
        if (!$activity) {
            return json(['status' => 0, 'msg' => '活动不存在']);
        }

        // 检查是否已有效果
        $existCount = Db::name('hd_3d_effects')->where('activity_id', $activityId)->count();
        if ($existCount > 0) {
            return json(['status' => 0, 'msg' => '该活动已有效果条目，请先清空后再初始化']);
        }

        $defaults = HdThreeDEffect::defaultEffects();
        $now = time();
        foreach ($defaults as $item) {
            Db::name('hd_3d_effects')->insert([
                'aid'         => aid,
                'bid'         => $activity['bid'],
                'activity_id' => $activityId,
                'type'        => $item['type'],
                'content'     => $item['content'],
                'sort'        => $item['sort'],
                'is_default'  => $item['is_default'],
                'created_at'  => $now,
            ]);
        }

        $this->syncDatastr($activityId);

        return json(['status' => 1, 'msg' => '已初始化' . count($defaults) . '个默认效果']);
    }

    /**
     * 全局配置弹窗（GET查看 / POST保存）
     */
    public function config()
    {
        $activityId = intval(input('activity_id', 0));
        if (!$activityId) {
            if ($this->request->isPost()) {
                return json(['status' => 0, 'msg' => '参数错误']);
            }
            return '参数错误';
        }

        $activity = Db::name('hd_activity')->where('id', $activityId)->where('aid', aid)->find();
        if (!$activity) {
            if ($this->request->isPost()) {
                return json(['status' => 0, 'msg' => '活动不存在']);
            }
            return '活动不存在';
        }

        $screenConfig = $activity['screen_config'] ? json_decode($activity['screen_config'], true) : [];

        if ($this->request->isPost()) {
            // 保存配置
            $avatarnum  = max(1, min(200, intval(input('post.avatarnum', 30))));
            $avatarsize = max(1, min(50, intval(input('post.avatarsize', 7))));
            $avatargap  = max(1, min(50, intval(input('post.avatargap', 15))));
            $playMode   = input('post.play_mode', 'sequential');
            if (!in_array($playMode, ['sequential', 'random'])) {
                $playMode = 'sequential';
            }

            $screenConfig['threed_avatarnum']  = $avatarnum;
            $screenConfig['threed_avatarsize'] = $avatarsize;
            $screenConfig['threed_avatargap']  = $avatargap;
            $screenConfig['threed_play_mode']  = $playMode;

            Db::name('hd_activity')->where('id', $activityId)->update([
                'screen_config' => json_encode($screenConfig, JSON_UNESCAPED_UNICODE),
            ]);

            return json(['status' => 1, 'msg' => '配置已保存']);
        }

        // GET: 显示配置表单
        $config = [
            'activity_id' => $activityId,
            'activity_title' => $activity['title'],
            'avatarnum'  => (int)($screenConfig['threed_avatarnum'] ?? 30),
            'avatarsize' => (int)($screenConfig['threed_avatarsize'] ?? 7),
            'avatargap'  => (int)($screenConfig['threed_avatargap'] ?? 15),
            'play_mode'  => $screenConfig['threed_play_mode'] ?? 'sequential',
        ];

        View::assign('config', $config);
        return View::fetch();
    }

    /**
     * 同步 datastr 到 screen_config
     */
    private function syncDatastr(int $activityId): void
    {
        $effects = Db::name('hd_3d_effects')
            ->where('activity_id', $activityId)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();

        $parts = [];
        foreach ($effects as $effect) {
            switch ($effect['type']) {
                case HdThreeDEffect::TYPE_PRESET_SHAPE:
                    $parts[] = '#' . $effect['content'];
                    break;
                case HdThreeDEffect::TYPE_IMAGE_LOGO:
                    $parts[] = '#icon ' . $effect['content'];
                    break;
                case HdThreeDEffect::TYPE_TEXT_LOGO:
                    $parts[] = $effect['content'];
                    break;
                case HdThreeDEffect::TYPE_COUNTDOWN:
                    $parts[] = '#countdown ' . $effect['content'];
                    break;
            }
        }

        $datastr = implode('|', $parts);

        $activity = Db::name('hd_activity')->where('id', $activityId)->find();
        if ($activity) {
            $screenConfig = $activity['screen_config'] ? json_decode($activity['screen_config'], true) : [];
            $screenConfig['threed_datastr'] = $datastr;
            Db::name('hd_activity')->where('id', $activityId)->update([
                'screen_config' => json_encode($screenConfig, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * 效果类型中文名
     */
    private function getTypeText(string $type): string
    {
        $map = [
            'preset_shape' => '预设造型',
            'image_logo'   => '图片Logo',
            'text_logo'    => '文字Logo',
            'countdown'    => '倒计时',
        ];
        return $map[$type] ?? $type;
    }

    /**
     * 效果内容展示文本
     */
    private function getContentDisplay(array $item): string
    {
        switch ($item['type']) {
            case 'preset_shape':
                $shapes = HdThreeDEffect::presetShapes();
                return ($shapes[$item['content']] ?? $item['content']) . ' (' . $item['content'] . ')';
            case 'image_logo':
                return '<img src="' . htmlspecialchars($item['content']) . '" style="max-height:30px;max-width:80px;vertical-align:middle"> ' . $item['content'];
            case 'text_logo':
                return htmlspecialchars($item['content']);
            case 'countdown':
                return $item['content'] . '秒';
            default:
                return htmlspecialchars($item['content']);
        }
    }
}

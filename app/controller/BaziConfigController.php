<?php
/**
 * 算八字功能配置控制器
 * 继承Common，提供后台管理员的模型/付费/Skill配置功能
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;

class BaziConfigController extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (bid > 0) showmsg('无访问权限');
    }

    /**
     * 配置页首页
     */
    public function index()
    {
        $info = Db::name('bazi_config')->where('aid', aid)->find();

        if (!$info) {
            // 首次访问自动初始化默认配置
            $defaultPrompt = $this->loadDefaultSkill();
            $now = time();
            Db::name('bazi_config')->insert([
                'aid'             => aid,
                'model'           => 'doubao-seed-2-0-pro-260215',
                'skill_prompt'    => $defaultPrompt,
                'pay_mode'        => 'free',
                'price'           => 0,
                'preview_percent' => 50,
                'create_time'     => $now,
                'update_time'     => $now,
            ]);
            $info = Db::name('bazi_config')->where('aid', aid)->find();
        }

        View::assign('info', $info);

        // 可选的模型列表
        View::assign('models', [
            'doubao-seed-2-0-pro-260215' => '豆包 Seed 2.0 Pro (推荐)',
            'doubao-seed-2-0-250615'     => '豆包 Seed 2.0 Lite',
            'deepseek-r1-250528'          => 'DeepSeek-R1 (火山方舟)',
            'deepseek-v3-241226'          => 'DeepSeek-V3 (火山方舟)',
        ]);

        return View::fetch();
    }

    /**
     * 保存配置
     */
    public function save()
    {
        $info = input('post.info/a');

        // 处理预览百分比，限定0-100
        $previewPercent = intval($info['preview_percent'] ?? 50);
        if ($previewPercent < 0) $previewPercent = 0;
        if ($previewPercent > 100) $previewPercent = 100;

        // 处理价格
        $price = floatval($info['price'] ?? 0);
        if ($price < 0) $price = 0;

        $data = [
            'aid'             => aid,
            'model'           => trim($info['model'] ?? 'doubao-seed-2-0-pro-260215'),
            'skill_prompt'    => $info['skill_prompt'] ?? '',
            'pay_mode'        => in_array($info['pay_mode'] ?? '', ['free', 'pay_then_predict', 'predict_then_pay']) ? $info['pay_mode'] : 'free',
            'price'           => $price,
            'preview_percent' => $previewPercent,
            'update_time'     => time(),
        ];

        if (Db::name('bazi_config')->where('aid', aid)->find()) {
            Db::name('bazi_config')->where('aid', aid)->update($data);
        } else {
            $data['create_time'] = time();
            Db::name('bazi_config')->insert($data);
        }

        \app\common\System::plog('算八字功能配置');
        return json(['status' => 1, 'msg' => '保存成功', 'url' => (string)url('index')]);
    }

    /**
     * 加载默认SKILL.md内容
     */
    protected function loadDefaultSkill(): string
    {
        $file = root_path() . 'bazi-skill-dist/SKILL.md';
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false && !empty(trim($content))) {
                return $content;
            }
        }
        return '';
    }
}

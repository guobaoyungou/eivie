<?php
declare(strict_types=1);

namespace app\service\hd;

use think\facade\Db;
use think\facade\Cache;

/**
 * 大屏互动 - 品牌定制/主题服务
 * 功能：品牌LOGO、主题色、背景、动画风格、自定义CSS
 */
class HdBrandService
{
    /**
     * 动画风格预设
     */
    const ANIMATION_PRESETS = [
        'default'   => ['name' => '默认', 'desc' => '简洁渐变背景'],
        'particle'  => ['name' => '粒子效果', 'desc' => '动态粒子漂浮'],
        'starfield' => ['name' => '星空', 'desc' => '星空闪烁效果'],
        'wave'      => ['name' => '波浪', 'desc' => '底部波浪动画'],
        'aurora'    => ['name' => '极光', 'desc' => '极光流光效果'],
        'snow'      => ['name' => '飘雪', 'desc' => '雪花飘落效果'],
        'bubble'    => ['name' => '气泡', 'desc' => '气泡上升效果'],
    ];

    /**
     * 获取品牌配置
     */
    public function getBrandConfig(int $activityId): array
    {
        $config = Db::name('hd_brand_config')
            ->where('activity_id', $activityId)
            ->find();

        if (!$config) {
            $config = $this->defaultConfig();
        }

        return [
            'code' => 0,
            'data' => [
                'brand_logo'      => $config['brand_logo'] ?? '',
                'brand_name'      => $config['brand_name'] ?? '',
                'primary_color'   => $config['primary_color'] ?? '#6366f1',
                'secondary_color' => $config['secondary_color'] ?? '#8b5cf6',
                'accent_color'    => $config['accent_color'] ?? '#f59e0b',
                'bg_type'         => (int)($config['bg_type'] ?? 1),
                'bg_color'        => $config['bg_color'] ?? '#0a0a1a',
                'bg_gradient'     => $config['bg_gradient'] ?? '',
                'bg_image'        => $config['bg_image'] ?? '',
                'bg_video'        => $config['bg_video'] ?? '',
                'animation_style' => $config['animation_style'] ?? 'default',
                'font_family'     => $config['font_family'] ?? '',
                'custom_css'      => $config['custom_css'] ?? '',
                'animation_presets' => self::ANIMATION_PRESETS,
            ],
        ];
    }

    /**
     * 更新品牌配置
     */
    public function updateBrandConfig(int $aid, int $bid, int $activityId, array $data): array
    {
        $existing = Db::name('hd_brand_config')
            ->where('activity_id', $activityId)->find();

        $allowedKeys = [
            'brand_logo', 'brand_name', 'primary_color', 'secondary_color', 'accent_color',
            'bg_type', 'bg_color', 'bg_gradient', 'bg_image', 'bg_video',
            'animation_style', 'font_family', 'custom_css',
        ];

        $update = ['updatetime' => time()];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) $update[$key] = $data[$key];
        }

        if ($existing) {
            Db::name('hd_brand_config')->where('id', $existing['id'])->update($update);
        } else {
            $update['aid'] = $aid;
            $update['bid'] = $bid;
            $update['activity_id'] = $activityId;
            $update['createtime'] = time();
            Db::name('hd_brand_config')->insert($update);
        }

        Cache::delete('hd_brand:' . $activityId);
        return ['code' => 0, 'msg' => '品牌设置已更新'];
    }

    /**
     * 获取品牌 CSS 变量字符串（注入到入口页 <style>）
     */
    public function getBrandCssVars(int $activityId): string
    {
        $cacheKey = 'hd_brand:' . $activityId;
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        $config = Db::name('hd_brand_config')
            ->where('activity_id', $activityId)->find();

        if (!$config) $config = $this->defaultConfig();

        $primary   = $config['primary_color'] ?? '#6366f1';
        $secondary = $config['secondary_color'] ?? '#8b5cf6';
        $accent    = $config['accent_color'] ?? '#f59e0b';
        $bgColor   = $config['bg_color'] ?? '#0a0a1a';

        $css = ":root {\n";
        $css .= "  --hd-primary: {$primary};\n";
        $css .= "  --hd-secondary: {$secondary};\n";
        $css .= "  --hd-accent: {$accent};\n";
        $css .= "  --hd-bg: {$bgColor};\n";
        $css .= "  --hd-primary-rgb: " . $this->hexToRgb($primary) . ";\n";
        $css .= "  --hd-secondary-rgb: " . $this->hexToRgb($secondary) . ";\n";
        $css .= "  --hd-accent-rgb: " . $this->hexToRgb($accent) . ";\n";

        if (!empty($config['font_family'])) {
            $css .= "  --hd-font: {$config['font_family']};\n";
        }
        $css .= "}\n";

        // 背景样式
        $bgType = (int)($config['bg_type'] ?? 1);
        if ($bgType === 2 && !empty($config['bg_gradient'])) {
            $css .= "body { background: {$config['bg_gradient']}; }\n";
        } elseif ($bgType === 3 && !empty($config['bg_image'])) {
            $css .= "body { background: url('{$config['bg_image']}') center/cover no-repeat fixed; }\n";
        } elseif ($bgType !== 1) {
            $css .= "body { background: {$bgColor}; }\n";
        }

        // 自定义CSS
        if (!empty($config['custom_css'])) {
            $css .= $config['custom_css'] . "\n";
        }

        Cache::set($cacheKey, $css, 300);
        return $css;
    }

    /**
     * 获取动画风格配置（用于入口页 JS）
     */
    public function getAnimationConfig(int $activityId): array
    {
        $config = Db::name('hd_brand_config')
            ->where('activity_id', $activityId)->find();
        return [
            'animation_style' => $config['animation_style'] ?? 'default',
            'bg_video'        => $config['bg_video'] ?? '',
            'bg_type'         => (int)($config['bg_type'] ?? 1),
        ];
    }

    private function defaultConfig(): array
    {
        return [
            'brand_logo' => '', 'brand_name' => '',
            'primary_color' => '#6366f1', 'secondary_color' => '#8b5cf6', 'accent_color' => '#f59e0b',
            'bg_type' => 1, 'bg_color' => '#0a0a1a', 'bg_gradient' => '',
            'bg_image' => '', 'bg_video' => '',
            'animation_style' => 'default', 'font_family' => '', 'custom_css' => '',
        ];
    }

    private function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r},{$g},{$b}";
    }
}

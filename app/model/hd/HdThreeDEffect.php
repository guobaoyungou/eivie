<?php
declare(strict_types=1);

namespace app\model\hd;

use think\Model;

/**
 * 大屏互动 - 3D签到效果条目模型
 */
class HdThreeDEffect extends Model
{
    protected $name = 'hd_3d_effects';
    protected $autoWriteTimestamp = false;

    // 效果类型常量
    const TYPE_PRESET_SHAPE = 'preset_shape';
    const TYPE_IMAGE_LOGO   = 'image_logo';
    const TYPE_TEXT_LOGO     = 'text_logo';
    const TYPE_COUNTDOWN     = 'countdown';

    // 预设造型代码
    const SHAPE_SPHERE   = 'sphere';
    const SHAPE_TORUS    = 'torus';
    const SHAPE_GRID     = 'grid';
    const SHAPE_HELIX    = 'helix';
    const SHAPE_CYLINDER = 'cylinder';
    const SHAPE_GENE     = 'gene';

    /**
     * 所有合法效果类型
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_PRESET_SHAPE,
            self::TYPE_IMAGE_LOGO,
            self::TYPE_TEXT_LOGO,
            self::TYPE_COUNTDOWN,
        ];
    }

    /**
     * 所有预设造型代码
     */
    public static function presetShapes(): array
    {
        return [
            self::SHAPE_SPHERE   => '球形',
            self::SHAPE_TORUS    => '隧道',
            self::SHAPE_GRID     => '方阵',
            self::SHAPE_HELIX    => '螺旋',
            self::SHAPE_CYLINDER => '圆柱体',
            self::SHAPE_GENE     => '基因',
        ];
    }

    /**
     * 默认效果初始化数据
     */
    public static function defaultEffects(): array
    {
        return [
            ['type' => self::TYPE_PRESET_SHAPE, 'content' => self::SHAPE_SPHERE,   'sort' => 1, 'is_default' => 1],
            ['type' => self::TYPE_PRESET_SHAPE, 'content' => self::SHAPE_TORUS,    'sort' => 2, 'is_default' => 1],
            ['type' => self::TYPE_PRESET_SHAPE, 'content' => self::SHAPE_GRID,     'sort' => 3, 'is_default' => 1],
            ['type' => self::TYPE_PRESET_SHAPE, 'content' => self::SHAPE_HELIX,    'sort' => 4, 'is_default' => 1],
            ['type' => self::TYPE_PRESET_SHAPE, 'content' => self::SHAPE_CYLINDER, 'sort' => 5, 'is_default' => 1],
            ['type' => self::TYPE_PRESET_SHAPE, 'content' => self::SHAPE_GENE,     'sort' => 6, 'is_default' => 1],
        ];
    }
}

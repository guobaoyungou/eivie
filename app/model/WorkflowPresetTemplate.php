<?php
/**
 * 预设短剧模板模型
 */
namespace app\model;

use think\Model;

class WorkflowPresetTemplate extends Model
{
    protected $name = 'workflow_preset_template';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $json = ['canvas_template', 'default_models', 'default_voice_ids'];
    protected $jsonAssoc = true;

    /**
     * 获取模板列表（系统级 + 当前商家级）
     */
    public static function getAvailableList($aid = 0, $bid = 0)
    {
        return self::where(function ($query) use ($aid, $bid) {
                $query->where('aid', 0) // 全局系统模板
                    ->whereOr(function ($q) use ($aid, $bid) {
                        $q->where('aid', $aid)->where('bid', $bid);
                    });
            })
            ->where('status', 1)
            ->order('sort asc, id desc')
            ->select()->toArray();
    }

    /**
     * 按题材分类获取
     */
    public static function getByGenre($genre, $aid = 0, $bid = 0)
    {
        return self::where(function ($query) use ($aid, $bid) {
                $query->where('aid', 0)
                    ->whereOr(function ($q) use ($aid, $bid) {
                        $q->where('aid', $aid)->where('bid', $bid);
                    });
            })
            ->where('genre', $genre)
            ->where('status', 1)
            ->order('sort asc, id desc')
            ->select()->toArray();
    }
}

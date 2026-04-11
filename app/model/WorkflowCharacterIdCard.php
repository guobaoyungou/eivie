<?php
/**
 * 工作流角色身份卡模型
 */
namespace app\model;

use think\Model;

class WorkflowCharacterIdCard extends Model
{
    protected $name = 'workflow_character_id_card';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $json = ['reference_images'];
    protected $jsonAssoc = true;

    /**
     * 关联项目
     */
    public function project()
    {
        return $this->belongsTo(WorkflowProject::class, 'project_id', 'id');
    }

    /**
     * 按项目和角色标签查找
     */
    public static function findByTag($projectId, $characterTag)
    {
        return self::where('project_id', $projectId)
            ->where('character_tag', $characterTag)
            ->find();
    }

    /**
     * 获取项目的所有角色身份卡
     */
    public static function getByProject($projectId)
    {
        return self::where('project_id', $projectId)
            ->order('id asc')
            ->select()->toArray();
    }
}

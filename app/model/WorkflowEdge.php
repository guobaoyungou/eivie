<?php
/**
 * 工作流连线模型
 */
namespace app\model;

use think\Model;

class WorkflowEdge extends Model
{
    protected $name = 'workflow_edge';
    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    /**
     * 关联上游节点
     */
    public function sourceNode()
    {
        return $this->belongsTo(WorkflowNode::class, 'source_node_id', 'id');
    }

    /**
     * 关联下游节点
     */
    public function targetNode()
    {
        return $this->belongsTo(WorkflowNode::class, 'target_node_id', 'id');
    }

    /**
     * 关联项目
     */
    public function project()
    {
        return $this->belongsTo(WorkflowProject::class, 'project_id', 'id');
    }
}

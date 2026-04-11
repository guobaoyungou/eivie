<?php
/**
 * 工作流节点服务
 * 处理节点CRUD、状态管理、输出获取等
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\WorkflowNode;
use app\model\WorkflowEdge;
use app\model\WorkflowProject;

class WorkflowNodeService
{
    /**
     * 添加节点到画布
     */
    public function addNode($data)
    {
        $projectId = intval($data['project_id'] ?? 0);
        $project = WorkflowProject::find($projectId);
        if (!$project) {
            return ['status' => 0, 'msg' => '项目不存在'];
        }

        // oneclick模式不允许手动添加节点
        if ($project->creation_mode === WorkflowProject::MODE_ONECLICK) {
            return ['status' => 0, 'msg' => '一键生成模式不支持手动添加节点'];
        }

        $validTypes = array_keys(WorkflowNode::typeTextMap());
        $nodeType = $data['node_type'] ?? '';
        if (!in_array($nodeType, $validTypes)) {
            return ['status' => 0, 'msg' => '无效的节点类型'];
        }

        $node = new WorkflowNode();
        $node->save([
            'aid'           => $project->aid,
            'bid'           => $project->bid,
            'mdid'          => $project->mdid,
            'uid'           => $project->uid,
            'project_id'    => $projectId,
            'node_type'     => $nodeType,
            'node_label'    => $data['node_label'] ?? (WorkflowNode::typeTextMap()[$nodeType] ?? $nodeType),
            'position_x'    => intval($data['position_x'] ?? 0),
            'position_y'    => intval($data['position_y'] ?? 0),
            'config_params' => $data['config_params'] ?? null,
            'model_id'      => intval($data['model_id'] ?? 0),
            'status'        => WorkflowNode::STATUS_IDLE,
        ]);

        return ['status' => 1, 'msg' => '节点添加成功', 'id' => $node->id, 'data' => $node->toArray()];
    }

    /**
     * 更新节点配置参数
     */
    public function updateNodeConfig($data)
    {
        $nodeId = intval($data['id'] ?? 0);
        $node = WorkflowNode::find($nodeId);
        if (!$node) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        $updateData = [];

        if (isset($data['node_label'])) {
            $updateData['node_label'] = $data['node_label'];
        }
        if (isset($data['position_x'])) {
            $updateData['position_x'] = intval($data['position_x']);
        }
        if (isset($data['position_y'])) {
            $updateData['position_y'] = intval($data['position_y']);
        }
        if (isset($data['config_params'])) {
            $updateData['config_params'] = is_string($data['config_params'])
                ? $data['config_params']
                : json_encode($data['config_params'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($data['model_id'])) {
            $updateData['model_id'] = intval($data['model_id']);
        }
        if (isset($data['note'])) {
            $updateData['note'] = strval($data['note']);
        }

        if (!empty($updateData)) {
            // 参数更新后标记为已配置（note字段不触发状态变更）
            if (isset($updateData['config_params']) || isset($updateData['model_id'])) {
                $updateData['status'] = WorkflowNode::STATUS_CONFIGURED;
            }
            $node->save($updateData);
        }

        return ['status' => 1, 'msg' => '节点更新成功', 'data' => $node->toArray()];
    }

    /**
     * 删除节点（同时删除相关连线）
     */
    public function deleteNode($nodeId, $aid, $bid)
    {
        $node = WorkflowNode::where(['id' => $nodeId, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$node) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        // 运行中的节点不允许删除
        if (in_array($node->status, [WorkflowNode::STATUS_RUNNING, WorkflowNode::STATUS_POLLING])) {
            return ['status' => 0, 'msg' => '节点正在运行中，无法删除'];
        }

        Db::startTrans();
        try {
            // 删除关联连线
            WorkflowEdge::where('source_node_id', $nodeId)->delete();
            WorkflowEdge::where('target_node_id', $nodeId)->delete();

            // 将下游节点重置为IDLE
            $downstreamNodeIds = WorkflowEdge::where('source_node_id', $nodeId)->column('target_node_id');
            if (!empty($downstreamNodeIds)) {
                WorkflowNode::whereIn('id', $downstreamNodeIds)->update(['status' => WorkflowNode::STATUS_IDLE]);
            }

            $node->delete();

            Db::commit();
            return ['status' => 1, 'msg' => '节点删除成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('删除工作流节点失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '删除失败'];
        }
    }

    /**
     * 添加连线
     */
    public function addEdge($data)
    {
        $projectId    = intval($data['project_id'] ?? 0);
        $sourceNodeId = intval($data['source_node_id'] ?? 0);
        $targetNodeId = intval($data['target_node_id'] ?? 0);
        $sourcePort   = $data['source_port'] ?? '';
        $targetPort   = $data['target_port'] ?? '';

        // 获取节点信息
        $sourceNode = WorkflowNode::find($sourceNodeId);
        $targetNode = WorkflowNode::find($targetNodeId);
        if (!$sourceNode || !$targetNode) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        // 校验节点属于同一项目
        if ($sourceNode->project_id !== $projectId || $targetNode->project_id !== $projectId) {
            return ['status' => 0, 'msg' => '节点不属于同一项目'];
        }

        // 校验连线兼容性
        if (!WorkflowNode::isConnectionValid($sourceNode->node_type, $sourcePort, $targetNode->node_type, $targetPort)) {
            return ['status' => 0, 'msg' => '不兼容的连线：' . $sourceNode->node_type . '.' . $sourcePort . ' → ' . $targetNode->node_type . '.' . $targetPort];
        }

        // 检查是否已存在相同连线
        $existing = WorkflowEdge::where([
            'project_id'     => $projectId,
            'source_node_id' => $sourceNodeId,
            'target_node_id' => $targetNodeId,
            'source_port'    => $sourcePort,
            'target_port'    => $targetPort,
        ])->find();
        if ($existing) {
            return ['status' => 0, 'msg' => '该连线已存在'];
        }

        $edge = new WorkflowEdge();
        $edge->save([
            'aid'            => $sourceNode->aid,
            'bid'            => $sourceNode->bid,
            'mdid'           => $sourceNode->mdid,
            'uid'            => $sourceNode->uid,
            'project_id'     => $projectId,
            'source_node_id' => $sourceNodeId,
            'target_node_id' => $targetNodeId,
            'source_port'    => $sourcePort,
            'target_port'    => $targetPort,
            'create_time'    => time(),
        ]);

        return ['status' => 1, 'msg' => '连线添加成功', 'id' => $edge->id];
    }

    /**
     * 删除连线
     */
    public function deleteEdge($edgeId)
    {
        $edge = WorkflowEdge::find($edgeId);
        if (!$edge) {
            return ['status' => 0, 'msg' => '连线不存在'];
        }
        $edge->delete();
        return ['status' => 1, 'msg' => '连线删除成功'];
    }

    /**
     * 查询节点执行状态
     */
    public function getNodeStatus($nodeId)
    {
        $node = WorkflowNode::find($nodeId);
        if (!$node) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        return [
            'status'        => 1,
            'node_status'   => $node->status,
            'status_text'   => WorkflowNode::statusTextMap()[$node->status] ?? '未知',
            'error_message' => $node->error_message ?? '',
            'task_id'       => $node->task_id,
            'execute_time'  => $node->execute_time,
            'finish_time'   => $node->finish_time ?? 0,
        ];
    }

    /**
     * 获取节点输出数据
     */
    public function getNodeOutput($nodeId)
    {
        $node = WorkflowNode::find($nodeId);
        if (!$node) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        return [
            'status'      => 1,
            'node_type'   => $node->node_type,
            'node_status' => $node->status,
            'output_data' => $node->output_data,
        ];
    }

    /**
     * 获取节点的上游节点列表
     */
    public function getUpstreamNodes($nodeId)
    {
        $edges = WorkflowEdge::where('target_node_id', $nodeId)->select()->toArray();
        $upstreamIds = array_column($edges, 'source_node_id');
        if (empty($upstreamIds)) {
            return [];
        }
        return WorkflowNode::whereIn('id', $upstreamIds)->select()->toArray();
    }

    /**
     * 获取节点的下游节点列表
     */
    public function getDownstreamNodes($nodeId)
    {
        $edges = WorkflowEdge::where('source_node_id', $nodeId)->select()->toArray();
        $downstreamIds = array_column($edges, 'target_node_id');
        if (empty($downstreamIds)) {
            return [];
        }
        return WorkflowNode::whereIn('id', $downstreamIds)->select()->toArray();
    }

    /**
     * 获取节点的历史输出版本（A/B对比用，模式三）
     */
    public function getNodeOutputHistory($nodeId)
    {
        // 从generation_record中获取该节点关联的所有历史生成记录
        $node = WorkflowNode::find($nodeId);
        if (!$node || empty($node->task_id)) {
            return ['status' => 1, 'data' => []];
        }

        $records = Db::name('generation_record')
            ->where('task_id', $node->task_id)
            ->order('id desc')
            ->limit(10)
            ->select()->toArray();

        return ['status' => 1, 'data' => $records];
    }
}

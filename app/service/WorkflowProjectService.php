<?php
/**
 * 工作流项目服务
 * 处理项目CRUD、画布数据保存、模式升级等
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\WorkflowProject;
use app\model\WorkflowNode;
use app\model\WorkflowEdge;

class WorkflowProjectService
{
    /**
     * 获取项目列表
     */
    public function getProjectList($aid, $bid, $uid = 0, $page = 1, $limit = 20, $filters = [])
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
        ];
        if ($uid > 0) {
            $where[] = ['uid', '=', $uid];
        }
        if (!empty($filters['status'])) {
            $where[] = ['status', '=', $filters['status']];
        }
        if (!empty($filters['creation_mode'])) {
            $where[] = ['creation_mode', '=', $filters['creation_mode']];
        }
        if (!empty($filters['keyword'])) {
            $where[] = ['title', 'like', '%' . $filters['keyword'] . '%'];
        }

        return WorkflowProject::getList($where, $page, $limit);
    }

    /**
     * 获取项目详情（含完整画布数据、节点、连线）
     */
    public function getProjectDetail($id, $aid, $bid, $uid = 0)
    {
        $where = ['id' => $id, 'aid' => $aid, 'bid' => $bid];
        if ($uid > 0) {
            $where['uid'] = $uid;
        }

        $project = WorkflowProject::where($where)->find();
        if (!$project) {
            return null;
        }

        $projectData = $project->toArray();

        // 获取所有节点
        $projectData['nodes'] = WorkflowNode::where('project_id', $id)
            ->order('id asc')
            ->select()->toArray();

        // 获取所有连线
        $projectData['edges'] = WorkflowEdge::where('project_id', $id)
            ->order('id asc')
            ->select()->toArray();

        // 获取角色身份卡
        $projectData['character_cards'] = Db::name('workflow_character_id_card')
            ->where('project_id', $id)
            ->order('id asc')
            ->select()->toArray();

        $projectData['creation_mode_text'] = WorkflowProject::modeTextMap()[$projectData['creation_mode'] ?? ''] ?? '';
        $projectData['status_text'] = WorkflowProject::statusTextMap()[$projectData['status'] ?? ''] ?? '';

        return $projectData;
    }

    /**
     * 创建或更新项目
     */
    public function saveProject($data)
    {
        $id = intval($data['id'] ?? 0);

        if (empty($data['title'])) {
            return ['status' => 0, 'msg' => '项目名称不能为空'];
        }

        $saveData = [
            'title'         => $data['title'],
            'description'   => $data['description'] ?? '',
            'cover_image'   => $data['cover_image'] ?? '',
            'creation_mode' => $data['creation_mode'] ?? WorkflowProject::MODE_FREESTYLE,
            'template_id'   => intval($data['template_id'] ?? 0),
        ];

        // 画布数据
        if (isset($data['canvas_data'])) {
            $saveData['canvas_data'] = is_string($data['canvas_data'])
                ? $data['canvas_data']
                : json_encode($data['canvas_data'], JSON_UNESCAPED_UNICODE);
        }

        if ($id > 0) {
            // 更新
            $project = WorkflowProject::find($id);
            if (!$project) {
                return ['status' => 0, 'msg' => '项目不存在'];
            }
            $project->save($saveData);
        } else {
            // 创建
            $saveData['aid']  = intval($data['aid'] ?? 0);
            $saveData['bid']  = intval($data['bid'] ?? 0);
            $saveData['mdid'] = intval($data['mdid'] ?? 0);
            $saveData['uid']  = intval($data['uid'] ?? 0);
            $saveData['status'] = WorkflowProject::STATUS_DRAFT;

            $project = new WorkflowProject();
            $project->save($saveData);
            $id = $project->id;
        }

        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 删除项目及其全部关联数据
     */
    public function deleteProject($id, $aid, $bid)
    {
        $project = WorkflowProject::where(['id' => $id, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$project) {
            return ['status' => 0, 'msg' => '项目不存在'];
        }

        // 不允许删除正在运行的项目
        if ($project->status === WorkflowProject::STATUS_RUNNING) {
            return ['status' => 0, 'msg' => '项目正在运行中，请先停止'];
        }

        Db::startTrans();
        try {
            // 删除关联数据
            WorkflowNode::where('project_id', $id)->delete();
            WorkflowEdge::where('project_id', $id)->delete();
            Db::name('workflow_character_id_card')->where('project_id', $id)->delete();
            $project->delete();

            Db::commit();
            return ['status' => 1, 'msg' => '删除成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('删除工作流项目失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '删除失败'];
        }
    }

    /**
     * 复制项目
     */
    public function duplicateProject($id, $aid, $bid, $uid)
    {
        $project = WorkflowProject::where(['id' => $id, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$project) {
            return ['status' => 0, 'msg' => '项目不存在'];
        }

        Db::startTrans();
        try {
            // 复制项目
            $newProjectData = $project->toArray();
            unset($newProjectData['id']);
            $newProjectData['title'] = $newProjectData['title'] . '（副本）';
            $newProjectData['status'] = WorkflowProject::STATUS_DRAFT;
            $newProjectData['output_video_url'] = '';
            $newProjectData['uid'] = $uid;
            $newProjectData['create_time'] = time();
            $newProjectData['update_time'] = time();

            $newProject = new WorkflowProject();
            $newProject->save($newProjectData);
            $newProjectId = $newProject->id;

            // 复制节点（维护ID映射以便重建连线）
            $nodeIdMap = [];
            $nodes = WorkflowNode::where('project_id', $id)->select();
            foreach ($nodes as $node) {
                $nodeData = $node->toArray();
                $oldNodeId = $nodeData['id'];
                unset($nodeData['id']);
                $nodeData['project_id'] = $newProjectId;
                $nodeData['uid'] = $uid;
                $nodeData['status'] = WorkflowNode::STATUS_IDLE;
                $nodeData['output_data'] = null;
                $nodeData['input_data'] = null;
                $nodeData['task_id'] = '';
                $nodeData['error_message'] = '';
                $nodeData['execute_time'] = 0;
                $nodeData['create_time'] = time();
                $nodeData['update_time'] = time();

                $newNode = new WorkflowNode();
                $newNode->save($nodeData);
                $nodeIdMap[$oldNodeId] = $newNode->id;
            }

            // 复制连线
            $edges = WorkflowEdge::where('project_id', $id)->select();
            foreach ($edges as $edge) {
                $edgeData = $edge->toArray();
                unset($edgeData['id']);
                $edgeData['project_id'] = $newProjectId;
                $edgeData['uid'] = $uid;
                $edgeData['source_node_id'] = $nodeIdMap[$edgeData['source_node_id']] ?? 0;
                $edgeData['target_node_id'] = $nodeIdMap[$edgeData['target_node_id']] ?? 0;
                $edgeData['create_time'] = time();

                $newEdge = new WorkflowEdge();
                $newEdge->save($edgeData);
            }

            Db::commit();
            return ['status' => 1, 'msg' => '复制成功', 'id' => $newProjectId];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('复制工作流项目失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '复制失败'];
        }
    }

    /**
     * 升级创作模式
     * oneclick → freestyle → advanced
     */
    public function upgradeMode($id, $aid, $bid)
    {
        $project = WorkflowProject::where(['id' => $id, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$project) {
            return ['status' => 0, 'msg' => '项目不存在'];
        }

        $upgradeMap = [
            WorkflowProject::MODE_ONECLICK  => WorkflowProject::MODE_FREESTYLE,
            WorkflowProject::MODE_FREESTYLE => WorkflowProject::MODE_ADVANCED,
        ];

        $currentMode = $project->creation_mode;
        if (!isset($upgradeMap[$currentMode])) {
            return ['status' => 0, 'msg' => '当前模式已是最高级别'];
        }

        $newMode = $upgradeMap[$currentMode];
        $project->save(['creation_mode' => $newMode]);

        return [
            'status'   => 1,
            'msg'      => '升级成功',
            'old_mode' => $currentMode,
            'new_mode' => $newMode,
            'new_mode_text' => WorkflowProject::modeTextMap()[$newMode] ?? '',
        ];
    }

    /**
     * 保存画布数据（节点位置、连线等）
     */
    public function saveCanvasData($id, $canvasData, $aid, $bid)
    {
        $project = WorkflowProject::where(['id' => $id, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$project) {
            return ['status' => 0, 'msg' => '项目不存在'];
        }

        $project->save([
            'canvas_data' => is_string($canvasData) ? $canvasData : json_encode($canvasData, JSON_UNESCAPED_UNICODE),
        ]);

        return ['status' => 1, 'msg' => '画布数据保存成功'];
    }
}

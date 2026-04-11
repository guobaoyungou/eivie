<?php
/**
 * 工作流模式服务
 * 处理模式一的一键生成、预设模板加载、模式升级等
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\WorkflowProject;
use app\model\WorkflowNode;
use app\model\WorkflowEdge;
use app\model\WorkflowPresetTemplate;

class WorkflowModeService
{
    /** @var WorkflowProjectService */
    protected $projectService;

    /** @var WorkflowNodeService */
    protected $nodeService;

    /** @var WorkflowEngineService */
    protected $engineService;

    public function __construct()
    {
        $this->projectService = new WorkflowProjectService();
        $this->nodeService    = new WorkflowNodeService();
        $this->engineService  = new WorkflowEngineService();
    }

    /**
     * 获取预设模板列表
     */
    public function getPresetTemplateList($aid = 0, $bid = 0, $genre = '')
    {
        $query = WorkflowPresetTemplate::where(function ($q) use ($aid, $bid) {
            $q->where('aid', 0) // 全局系统模板
              ->whereOr(function ($q2) use ($aid, $bid) {
                  $q2->where('aid', $aid)->where('bid', $bid);
              });
        })->where('status', 1);

        if (!empty($genre)) {
            $query->where('genre', $genre);
        }

        $data = $query->order('sort asc, id desc')->select()->toArray();

        foreach ($data as &$item) {
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
            if (is_string($item['canvas_template'])) {
                $item['canvas_template'] = json_decode($item['canvas_template'], true);
            }
            if (is_string($item['default_models'])) {
                $item['default_models'] = json_decode($item['default_models'], true);
            }
            if (is_string($item['default_voice_ids'])) {
                $item['default_voice_ids'] = json_decode($item['default_voice_ids'], true);
            }
        }

        return ['status' => 1, 'data' => $data];
    }

    /**
     * 模式一：一键生成
     * 基于预设模板创建项目并自动执行整个工作流
     */
    public function oneclickGenerate($data)
    {
        $templateId = intval($data['template_id'] ?? 0);
        $creativity = $data['creativity'] ?? '';   // 一句话创意
        $episodes   = intval($data['episodes'] ?? 3);
        $duration   = intval($data['duration'] ?? 60);

        if (empty($creativity)) {
            return ['status' => 0, 'msg' => '请输入创意描述'];
        }

        // 获取模板
        $template = WorkflowPresetTemplate::find($templateId);
        if (!$template) {
            return ['status' => 0, 'msg' => '预设模板不存在'];
        }

        Db::startTrans();
        try {
            // 1. 创建项目
            $projectResult = $this->projectService->saveProject([
                'aid'           => intval($data['aid'] ?? 0),
                'bid'           => intval($data['bid'] ?? 0),
                'mdid'          => intval($data['mdid'] ?? 0),
                'uid'           => intval($data['uid'] ?? 0),
                'title'         => $creativity,
                'description'   => '一键生成 - ' . $template->template_name,
                'creation_mode' => WorkflowProject::MODE_ONECLICK,
                'template_id'   => $templateId,
            ]);

            if ($projectResult['status'] != 1) {
                Db::rollback();
                return $projectResult;
            }

            $projectId = $projectResult['id'];

            // 2. 从模板还原节点和连线
            $this->loadTemplateToProject($projectId, $template, [
                'aid'  => intval($data['aid'] ?? 0),
                'bid'  => intval($data['bid'] ?? 0),
                'mdid' => intval($data['mdid'] ?? 0),
                'uid'  => intval($data['uid'] ?? 0),
            ]);

            // 3. 配置剧本节点参数
            $scriptNode = WorkflowNode::where([
                'project_id' => $projectId,
                'node_type'  => WorkflowNode::TYPE_SCRIPT,
            ])->find();

            if ($scriptNode) {
                $scriptNode->save([
                    'config_params' => json_encode([
                        'creativity' => $creativity,
                        'episodes'   => $episodes,
                        'duration'   => $duration,
                        'genre'      => $template->genre,
                    ], JSON_UNESCAPED_UNICODE),
                    'status' => WorkflowNode::STATUS_CONFIGURED,
                ]);
            }

            Db::commit();

            // 4. 启动工作流执行
            $runResult = $this->engineService->runWorkflow(
                $projectId,
                intval($data['aid'] ?? 0),
                intval($data['bid'] ?? 0)
            );

            return [
                'status'     => 1,
                'msg'        => '一键生成已启动',
                'project_id' => $projectId,
                'run_result' => $runResult,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('一键生成失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '生成失败: ' . $e->getMessage()];
        }
    }

    /**
     * 从模板还原节点和连线到项目
     */
    protected function loadTemplateToProject($projectId, $template, $tenantData)
    {
        $canvasTemplate = $template->canvas_template;
        if (is_string($canvasTemplate)) {
            $canvasTemplate = json_decode($canvasTemplate, true);
        }

        $defaultModels = $template->default_models;
        if (is_string($defaultModels)) {
            $defaultModels = json_decode($defaultModels, true);
        }

        $nodes  = $canvasTemplate['nodes'] ?? [];
        $edges  = $canvasTemplate['edges'] ?? [];

        // 创建节点并维护ID映射
        $nodeIdMap = []; // template_node_id => real_node_id
        foreach ($nodes as $tplNode) {
            $tplNodeId = $tplNode['id'] ?? uniqid();
            $nodeType  = $tplNode['node_type'] ?? '';
            $modelId   = $defaultModels[$nodeType] ?? 0;

            $node = new WorkflowNode();
            $node->save([
                'aid'           => $tenantData['aid'],
                'bid'           => $tenantData['bid'],
                'mdid'          => $tenantData['mdid'],
                'uid'           => $tenantData['uid'],
                'project_id'    => $projectId,
                'node_type'     => $nodeType,
                'node_label'    => $tplNode['node_label'] ?? (WorkflowNode::typeTextMap()[$nodeType] ?? $nodeType),
                'position_x'    => intval($tplNode['position_x'] ?? 0),
                'position_y'    => intval($tplNode['position_y'] ?? 0),
                'config_params' => isset($tplNode['config_params'])
                    ? json_encode($tplNode['config_params'], JSON_UNESCAPED_UNICODE) : null,
                'model_id'      => intval($modelId),
                'status'        => WorkflowNode::STATUS_IDLE,
            ]);

            $nodeIdMap[$tplNodeId] = $node->id;
        }

        // 创建连线
        foreach ($edges as $tplEdge) {
            $sourceId = $nodeIdMap[$tplEdge['source_node_id'] ?? ''] ?? 0;
            $targetId = $nodeIdMap[$tplEdge['target_node_id'] ?? ''] ?? 0;
            if ($sourceId && $targetId) {
                $edge = new WorkflowEdge();
                $edge->save([
                    'aid'            => $tenantData['aid'],
                    'bid'            => $tenantData['bid'],
                    'mdid'           => $tenantData['mdid'],
                    'uid'            => $tenantData['uid'],
                    'project_id'     => $projectId,
                    'source_node_id' => $sourceId,
                    'target_node_id' => $targetId,
                    'source_port'    => $tplEdge['source_port'] ?? '',
                    'target_port'    => $tplEdge['target_port'] ?? '',
                    'create_time'    => time(),
                ]);
            }
        }
    }

    /**
     * 应用模板 - 基于模板创建新项目（不自动执行工作流）
     */
    public function loadTemplate($templateId, $aid, $bid, $mdid = 0, $uid = 0)
    {
        $template = WorkflowPresetTemplate::find($templateId);
        if (!$template) {
            return ['status' => 0, 'msg' => '模板不存在'];
        }

        Db::startTrans();
        try {
            // 1. 创建项目
            $projectResult = $this->projectService->saveProject([
                'aid'           => $aid,
                'bid'           => $bid,
                'mdid'          => $mdid,
                'uid'           => $uid,
                'title'         => $template->template_name . ' - ' . date('mdHi'),
                'description'   => '基于模板「' . $template->template_name . '」创建',
                'creation_mode' => WorkflowProject::MODE_FREESTYLE,
                'template_id'   => $templateId,
            ]);

            if ($projectResult['status'] != 1) {
                Db::rollback();
                return $projectResult;
            }

            $projectId = $projectResult['id'];

            // 2. 从模板还原节点和连线
            $this->loadTemplateToProject($projectId, $template, [
                'aid'  => $aid,
                'bid'  => $bid,
                'mdid' => $mdid,
                'uid'  => $uid,
            ]);

            Db::commit();

            return [
                'status'     => 1,
                'msg'        => '模板应用成功',
                'project_id' => $projectId,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('应用模板失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '应用模板失败: ' . $e->getMessage()];
        }
    }

    /**
     * 升级到自由编排模式
     */
    public function upgradeToFreestyle($projectId, $aid, $bid)
    {
        return $this->projectService->upgradeMode($projectId, $aid, $bid);
    }

    /**
     * 升级到高级微调模式
     */
    public function upgradeToAdvanced($projectId, $aid, $bid)
    {
        $project = WorkflowProject::where(['id' => $projectId, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$project) {
            return ['status' => 0, 'msg' => '项目不存在'];
        }
        if ($project->creation_mode !== WorkflowProject::MODE_FREESTYLE) {
            // 先升级到freestyle再升级到advanced
            if ($project->creation_mode === WorkflowProject::MODE_ONECLICK) {
                $this->projectService->upgradeMode($projectId, $aid, $bid);
            }
        }
        return $this->projectService->upgradeMode($projectId, $aid, $bid);
    }

    /**
     * 模式三专用：重新生成节点输出中的单个帧/片段
     */
    public function regenerateSingleItem($nodeId, $itemIndex, $overrideParams = [])
    {
        $node = WorkflowNode::find($nodeId);
        if (!$node) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        $outputData = $node->output_data;
        if (is_string($outputData)) {
            $outputData = json_decode($outputData, true);
        }

        if (empty($outputData)) {
            return ['status' => 0, 'msg' => '节点暂无输出数据'];
        }

        // 根据节点类型确定输出数组字段
        $outputArrayKey = null;
        switch ($node->node_type) {
            case WorkflowNode::TYPE_STORYBOARD:
                $outputArrayKey = 'frames';
                break;
            case WorkflowNode::TYPE_VIDEO:
                $outputArrayKey = 'clips';
                break;
            case WorkflowNode::TYPE_VOICE:
                $outputArrayKey = 'audio_clips';
                break;
            default:
                return ['status' => 0, 'msg' => '该节点类型不支持单项重新生成'];
        }

        $items = $outputData[$outputArrayKey] ?? [];
        if (!isset($items[$itemIndex])) {
            return ['status' => 0, 'msg' => '输出项索引超出范围'];
        }

        // TODO: 调用对应的节点执行器重新生成单项
        // 此处预留接口，后续集成具体模型API
        return [
            'status'     => 1,
            'msg'        => '重新生成请求已提交',
            'node_id'    => $nodeId,
            'item_index' => $itemIndex,
        ];
    }

    /**
     * 模式三专用：选择历史版本作为当前输出
     */
    public function selectOutputVersion($nodeId, $versionId)
    {
        // TODO: 从历史版本记录中恢复输出数据
        return ['status' => 1, 'msg' => '版本选择成功'];
    }
}

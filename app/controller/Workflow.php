<?php
/**
 * AI短剧工作流控制器
 * 提供工作流项目管理、节点操作、工作流执行、资源管理、角色一致性、高级微调等全部API端点
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\WorkflowProjectService;
use app\service\WorkflowNodeService;
use app\service\WorkflowEngineService;
use app\service\WorkflowResourceService;
use app\service\WorkflowModeService;
use app\service\CharacterConsistencyService;
use app\service\WorkflowLogService;

class Workflow extends Common
{
    /** @var WorkflowProjectService */
    protected $projectService;

    /** @var WorkflowNodeService */
    protected $nodeService;

    /** @var WorkflowEngineService */
    protected $engineService;

    /** @var WorkflowResourceService */
    protected $resourceService;

    /** @var WorkflowModeService */
    protected $modeService;

    /** @var CharacterConsistencyService */
    protected $consistencyService;

    public function initialize()
    {
        parent::initialize();
        $this->projectService     = new WorkflowProjectService();
        $this->nodeService        = new WorkflowNodeService();
        $this->engineService      = new WorkflowEngineService();
        $this->resourceService    = new WorkflowResourceService();
        $this->modeService        = new WorkflowModeService();
        $this->consistencyService = new CharacterConsistencyService();
    }

    // ================================================================
    // 9.1 工作流项目管理
    // ================================================================

    /**
     * 获取用户的工作流项目列表
     * GET /workflow/project_list
     */
    public function project_list()
    {
        if (request()->isAjax()) {
            $page  = input('param.page', 1);
            $limit = input('param.limit', 20);
            $filters = [
                'status'        => input('param.status', ''),
                'creation_mode' => input('param.creation_mode', ''),
                'keyword'       => input('param.keyword', ''),
            ];

            $result = $this->projectService->getProjectList(
                $this->aid, $this->bid, 0, $page, $limit, $filters
            );

            return json(['code' => 0, 'msg' => '', 'count' => $result['count'], 'data' => $result['data']]);
        }

        return View::fetch();
    }

    /**
     * 创建或更新工作流项目
     * POST /workflow/project_save
     */
    public function project_save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $data = input('post.');
        $data['aid']  = $this->aid;
        $data['bid']  = $this->bid;
        $data['mdid'] = $this->mdid;
        $data['uid']  = $this->uid;

        $result = $this->projectService->saveProject($data);
        return json($result);
    }

    /**
     * 获取项目详情（含完整画布数据）
     * GET /workflow/project_detail
     */
    public function project_detail()
    {
        $id = input('param.id', 0);
        if ($id <= 0) {
            if (request()->isAjax()) {
                return json(['status' => 0, 'msg' => '参数错误']);
            }
            return View::fetch();
        }

        $detail = $this->projectService->getProjectDetail($id, $this->aid, $this->bid);
        if (!$detail) {
            if (request()->isAjax()) {
                return json(['status' => 0, 'msg' => '项目不存在']);
            }
            return View::fetch();
        }

        if (request()->isAjax()) {
            return json(['status' => 1, 'data' => $detail]);
        }

        View::assign('project', $detail);
        View::assign('project_json', json_encode($detail, JSON_UNESCAPED_UNICODE));
        return View::fetch();
    }

    /**
     * 删除项目
     * POST /workflow/project_delete
     */
    public function project_delete()
    {
        $id = input('post.id', 0);
        $result = $this->projectService->deleteProject($id, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 复制项目
     * POST /workflow/project_duplicate
     */
    public function project_duplicate()
    {
        $id = input('post.id', 0);
        $result = $this->projectService->duplicateProject($id, $this->aid, $this->bid, $this->uid);
        return json($result);
    }

    /**
     * 升级创作模式
     * POST /workflow/project_upgrade_mode
     */
    public function project_upgrade_mode()
    {
        $id = input('post.id', 0);
        $result = $this->projectService->upgradeMode($id, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 获取预设短剧模板列表（模式一用）
     * GET /workflow/preset_template_list
     */
    public function preset_template_list()
    {
        $genre = input('param.genre', '');
        $result = $this->modeService->getPresetTemplateList($this->aid, $this->bid, $genre);
        return json($result);
    }

    /**
     * 一键生成（模式一专用）
     * POST /workflow/oneclick_generate
     */
    public function oneclick_generate()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $data = input('post.');
        $data['aid']  = $this->aid;
        $data['bid']  = $this->bid;
        $data['mdid'] = $this->mdid;
        $data['uid']  = $this->uid;

        $result = $this->modeService->oneclickGenerate($data);
        return json($result);
    }

    // ================================================================
    // 9.2 节点操作
    // ================================================================

    /**
     * 添加节点到画布
     * POST /workflow/node_add
     */
    public function node_add()
    {
        $data = input('post.');
        $result = $this->nodeService->addNode($data);
        return json($result);
    }

    /**
     * 更新节点配置参数
     * POST /workflow/node_update
     */
    public function node_update()
    {
        $data = input('post.');
        $result = $this->nodeService->updateNodeConfig($data);
        return json($result);
    }

    /**
     * 删除节点
     * POST /workflow/node_delete
     */
    public function node_delete()
    {
        $nodeId = input('post.id', 0);
        $result = $this->nodeService->deleteNode($nodeId, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 手动触发执行单个节点
     * POST /workflow/node_execute
     */
    public function node_execute()
    {
        $nodeId = input('post.id', 0);
        $result = $this->engineService->executeNode($nodeId, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 查询节点执行状态（支持轮询）
     * GET /workflow/node_status
     */
    public function node_status()
    {
        $nodeId = input('param.id', 0);
        $result = $this->nodeService->getNodeStatus($nodeId);
        return json($result);
    }

    /**
     * 获取节点输出数据
     * GET /workflow/node_output
     */
    public function node_output()
    {
        $nodeId = input('param.id', 0);
        $result = $this->nodeService->getNodeOutput($nodeId);
        return json($result);
    }

    // ================================================================
    // 9.2+ 连线操作
    // ================================================================

    /**
     * 添加连线
     * POST /workflow/edge_add
     */
    public function edge_add()
    {
        $data = input('post.');
        $result = $this->nodeService->addEdge($data);
        return json($result);
    }

    /**
     * 删除连线
     * POST /workflow/edge_delete
     */
    public function edge_delete()
    {
        $edgeId = input('post.id', 0);
        $result = $this->nodeService->deleteEdge($edgeId);
        return json($result);
    }

    // ================================================================
    // 9.3 工作流执行
    // ================================================================

    /**
     * 从起始节点开始执行整个工作流
     * POST /workflow/run
     */
    public function run()
    {
        $projectId = input('post.project_id', 0);
        $result = $this->engineService->runWorkflow($projectId, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 从指定节点开始执行（断点续跑）
     * POST /workflow/run_from_node
     */
    public function run_from_node()
    {
        $projectId = input('post.project_id', 0);
        $nodeId    = input('post.node_id', 0);
        $result = $this->engineService->runFromNode($projectId, $nodeId, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 暂停/终止工作流执行
     * POST /workflow/stop
     */
    public function stop()
    {
        $projectId = input('post.project_id', 0);
        $result = $this->engineService->stopWorkflow($projectId);
        return json($result);
    }

    /**
     * 获取工作流整体执行进度
     * GET /workflow/progress
     */
    public function progress()
    {
        $projectId = input('param.project_id', 0);
        $result = $this->engineService->getProgress($projectId);
        return json($result);
    }

    // ================================================================
    // 9.4 资源管理
    // ================================================================

    /**
     * 获取资源列表API（纯JSON数据接口）
     * GET /workflow/resource_list_api
     */
    public function resource_list_api()
    {
        $page  = input('param.page', 1);
        $limit = input('param.limit', 20);
        $filters = [
            'resource_type' => input('param.resource_type', ''),
            'keyword'       => input('param.keyword', ''),
        ];

        $result = $this->resourceService->getResourceList($this->aid, $this->bid, $filters, $page, $limit);
        return json(['code' => 0, 'msg' => '', 'count' => $result['count'], 'data' => $result['data']]);
    }

    /**
     * 创建或更新资源
     * POST /workflow/resource_save
     */
    public function resource_save()
    {
        $data = input('post.');
        $data['aid']  = $this->aid;
        $data['bid']  = $this->bid;
        $data['mdid'] = $this->mdid;
        $data['uid']  = $this->uid;

        $result = $this->resourceService->saveResource($data);
        return json($result);
    }

    /**
     * 删除资源
     * POST /workflow/resource_delete
     */
    public function resource_delete()
    {
        $id = input('post.id', 0);
        $result = $this->resourceService->deleteResource($id, $this->aid, $this->bid);
        return json($result);
    }

    /**
     * 资源文件上传（图片/音频/视频）
     * POST /workflow/resource_upload
     */
    public function resource_upload()
    {
        $file = request()->file('file');
        if (!$file) {
            return json(['status' => 0, 'msg' => '请选择要上传的文件']);
        }

        try {
            // 验证文件类型
            $ext = strtolower($file->getOriginalExtension());
            $allowedExts = ['jpg','jpeg','png','gif','webp','mp3','wav','ogg','mp4','webm','mov'];
            if (!in_array($ext, $allowedExts)) {
                return json(['status' => 0, 'msg' => '不支持的文件格式: ' . $ext]);
            }

            // 文件大小限制 50MB
            if ($file->getSize() > 50 * 1024 * 1024) {
                return json(['status' => 0, 'msg' => '文件大小不能超过50MB']);
            }

            // 确定子目录
            $typeMap = [
                'jpg'=>'image','jpeg'=>'image','png'=>'image','gif'=>'image','webp'=>'image',
                'mp3'=>'audio','wav'=>'audio','ogg'=>'audio',
                'mp4'=>'video','webm'=>'video','mov'=>'video',
            ];
            $subdir = $typeMap[$ext] ?? 'other';
            $uploadDir = app()->getRootPath() . 'public/static/workflow/uploads/' . $subdir . '/' . date('Ymd');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 移动文件
            $newName = md5(uniqid(mt_rand(), true)) . '.' . $ext;
            $file->move($uploadDir, $newName);

            $url = '/static/workflow/uploads/' . $subdir . '/' . date('Ymd') . '/' . $newName;

            return json([
                'status' => 1,
                'msg'    => '上传成功',
                'url'    => $url,
                'filename' => $file->getOriginalName(),
                'size'   => $file->getSize(),
                'type'   => $subdir,
            ]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '上传失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取节点可用模型列表（按节点类型筛选）
     * GET /workflow/model_options
     */
    public function model_options()
    {
        $nodeType = input('param.node_type', '');
        $result = $this->resourceService->getModelOptions($nodeType);
        return json($result);
    }

    // ================================================================
    // 9.5 角色一致性
    // ================================================================

    /**
     * 获取角色身份卡详情
     * GET /workflow/character_id_card
     */
    public function character_id_card()
    {
        $id = input('param.id', 0);
        $data = $this->consistencyService->getIdCard($id);

        if (!$data) {
            return json(['status' => 0, 'msg' => '身份卡不存在']);
        }

        return json(['status' => 1, 'data' => $data]);
    }

    /**
     * 创建或更新角色身份卡
     * POST /workflow/character_id_card_save
     */
    public function character_id_card_save()
    {
        $data = input('post.');
        $data['aid']  = $this->aid;
        $data['bid']  = $this->bid;
        $data['mdid'] = $this->mdid;
        $data['uid']  = $this->uid;

        $result = $this->consistencyService->saveIdCard($data);
        return json($result);
    }

    /**
     * 手动触发一致性评分（模式三用）
     * POST /workflow/consistency_check
     */
    public function consistency_check()
    {
        $imageUrl     = input('post.image_url', '');
        $characterTag = input('post.character_tag', '');
        $projectId    = input('post.project_id', 0);

        if (empty($imageUrl) || empty($characterTag)) {
            return json(['status' => 0, 'msg' => '参数不完整']);
        }

        $result = $this->consistencyService->scoreConsistency($imageUrl, $characterTag, $projectId);
        return json(['status' => 1, 'data' => $result]);
    }

    // ================================================================
    // 9.6 高级微调（模式三专用）
    // ================================================================

    /**
     * 重新生成节点输出中的单个帧/片段
     * POST /workflow/node_output_regenerate_item
     */
    public function node_output_regenerate_item()
    {
        $nodeId         = input('post.node_id', 0);
        $itemIndex      = input('post.item_index', 0);
        $overrideParams = input('post.override_params/a', []);

        $result = $this->modeService->regenerateSingleItem($nodeId, $itemIndex, $overrideParams);
        return json($result);
    }

    /**
     * 获取节点的历史输出版本（A/B对比用）
     * GET /workflow/node_output_history
     */
    public function node_output_history()
    {
        $nodeId = input('param.node_id', 0);
        $result = $this->nodeService->getNodeOutputHistory($nodeId);
        return json($result);
    }

    /**
     * 选择历史版本作为当前输出
     * POST /workflow/node_output_select_version
     */
    public function node_output_select_version()
    {
        $nodeId    = input('post.node_id', 0);
        $versionId = input('post.version_id', 0);

        $result = $this->modeService->selectOutputVersion($nodeId, $versionId);
        return json($result);
    }

    /**
     * 轮询异步节点状态（视频生成等耗时任务）
     * POST /workflow/poll_async
     */
    public function poll_async()
    {
        $projectId = input('post.project_id', input('param.project_id', 0));
        $result = $this->engineService->pollAsyncNodes($projectId, $this->aid, $this->bid);
        return json($result);
    }

    // ================================================================
    // 资源管理页面
    // ================================================================

    /**
     * 资源管理列表页面
     */
    public function resource_list()
    {
        if (request()->isAjax()) {
            $page  = input('param.page', 1);
            $limit = input('param.limit', 20);
            $filters = [
                'resource_type' => input('param.resource_type', ''),
                'keyword'       => input('param.keyword', ''),
            ];

            $result = $this->resourceService->getResourceList($this->aid, $this->bid, $filters, $page, $limit);
            return json(['code' => 0, 'msg' => '', 'count' => $result['count'], 'data' => $result['data']]);
        }

        return View::fetch();
    }

    /**
     * 获取项目角色身份卡列表
     * GET /workflow/character_cards
     */
    public function character_cards()
    {
        $projectId = input('param.project_id', 0);
        $cards = \app\model\WorkflowCharacterIdCard::getByProject($projectId);
        return json(['status' => 1, 'data' => $cards]);
    }

    // ================================================================
    // 模板管理
    // ================================================================

    /**
     * 模板库列表页面（支持视图渲染和AJAX数据请求）
     * GET /workflow/template_list
     */
    public function template_list()
    {
        if (request()->isAjax()) {
            $page   = input('param.page', 1);
            $limit  = input('param.limit', 20);
            $genre  = input('param.genre', '');
            $keyword = input('param.keyword', '');

            $query = Db::name('workflow_preset_template')
                ->where(function ($q) {
                    $q->where('is_system', 1)
                      ->whereOr(function ($q2) {
                          $q2->where('aid', $this->aid)->where('bid', $this->bid);
                      });
                })
                ->where('status', 1);

            if ($genre !== '') {
                $query->where('genre', $genre);
            }
            if ($keyword !== '') {
                $query->whereLike('template_name', '%' . $keyword . '%');
            }

            $count = $query->count();
            $list  = $query->order('sort asc, id desc')
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 格式化时间
            foreach ($list as &$item) {
                $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '-';
                $item['update_time_text'] = $item['update_time'] ? date('Y-m-d H:i', $item['update_time']) : '-';
                $item['is_system_text']   = $item['is_system'] ? '系统预置' : '自定义';
            }
            unset($item);

            return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
        }

        return View::fetch();
    }

    /**
     * 保存（新增/编辑）模板
     * POST /workflow/template_save
     */
    public function template_save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $id   = input('post.id', 0, 'intval');
        $data = [
            'template_name'    => input('post.template_name', ''),
            'genre'            => input('post.genre', ''),
            'episode_count'    => input('post.episode_count', 1, 'intval'),
            'description'      => input('post.description', ''),
            'cover_image'      => input('post.cover_image', ''),
            'canvas_template'  => input('post.canvas_template', ''),
            'default_models'   => input('post.default_models', ''),
            'update_time'      => time(),
        ];

        if (empty($data['template_name'])) {
            return json(['status' => 0, 'msg' => '请填写模板名称']);
        }

        try {
            if ($id > 0) {
                // 编辑：仅允许编辑自己创建的非系统模板
                $existing = Db::name('workflow_preset_template')->where('id', $id)->find();
                if (!$existing) {
                    return json(['status' => 0, 'msg' => '模板不存在']);
                }
                if ($existing['is_system'] == 1) {
                    return json(['status' => 0, 'msg' => '系统预置模板不可编辑']);
                }
                Db::name('workflow_preset_template')->where('id', $id)->update($data);
                return json(['status' => 1, 'msg' => '模板更新成功']);
            } else {
                $data['aid']         = $this->aid;
                $data['bid']         = $this->bid;
                $data['mdid']        = $this->mdid;
                $data['is_system']   = 0;
                $data['status']      = 1;
                $data['sort']        = 0;
                $data['create_time'] = time();
                $newId = Db::name('workflow_preset_template')->insertGetId($data);
                return json(['status' => 1, 'msg' => '模板创建成功', 'id' => $newId]);
            }
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除模板
     * POST /workflow/template_delete
     */
    public function template_delete()
    {
        $id = input('post.id', 0, 'intval');
        if ($id <= 0) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $tpl = Db::name('workflow_preset_template')->where('id', $id)->find();
        if (!$tpl) {
            return json(['status' => 0, 'msg' => '模板不存在']);
        }
        if ($tpl['is_system'] == 1) {
            return json(['status' => 0, 'msg' => '系统预置模板不可删除']);
        }

        Db::name('workflow_preset_template')->where('id', $id)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 应用模板 - 基于模板创建新项目
     * POST /workflow/apply_template
     */
    public function apply_template()
    {
        $templateId = input('post.template_id', 0, 'intval');
        if ($templateId <= 0) {
            return json(['status' => 0, 'msg' => '请选择模板']);
        }

        $result = $this->modeService->loadTemplate($templateId, $this->aid, $this->bid, $this->mdid, $this->uid);
        return json($result);
    }

    // ================================================================
    // 执行日志
    // ================================================================

    /**
     * 获取项目执行日志
     * GET /workflow/exec_logs
     */
    public function exec_logs()
    {
        $projectId = input('param.project_id', 0);
        if ($projectId <= 0) {
            return json(['code' => 0, 'msg' => '', 'count' => 0, 'data' => []]);
        }

        $page  = input('param.page', 1);
        $limit = input('param.limit', 50);
        $filters = [
            'log_level' => input('param.log_level', ''),
            'node_id'   => input('param.node_id', 0),
            'log_type'  => input('param.log_type', ''),
        ];

        $result = WorkflowLogService::getProjectLogs($projectId, $page, $limit, $filters);
        return json(['code' => 0, 'msg' => '', 'count' => $result['count'], 'data' => $result['data']]);
    }

    // ================================================================
    // 项目导出/导入
    // ================================================================

    /**
     * 导出项目JSON
     * GET /workflow/project_export
     */
    public function project_export()
    {
        $id = input('param.id', 0);
        $detail = $this->projectService->getProjectDetail($id, $this->aid, $this->bid);
        if (!$detail) {
            return json(['status' => 0, 'msg' => '项目不存在']);
        }

        // 清理敏感字段
        $exportData = [
            'version'        => '1.0',
            'export_time'    => date('Y-m-d H:i:s'),
            'project'        => [
                'title'         => $detail['title'],
                'description'   => $detail['description'] ?? '',
                'creation_mode' => $detail['creation_mode'],
                'template_id'   => $detail['template_id'] ?? 0,
            ],
            'nodes'          => array_map(function($n) {
                return [
                    'node_type'     => $n['node_type'],
                    'node_label'    => $n['node_label'],
                    'position_x'    => $n['position_x'],
                    'position_y'    => $n['position_y'],
                    'config_params' => $n['config_params'],
                    'status'        => $n['status'],
                    'output_data'   => $n['output_data'],
                    '_original_id'  => $n['id'],
                ];
            }, $detail['nodes'] ?? []),
            'edges'          => array_map(function($e) {
                return [
                    'source_node_id' => $e['source_node_id'],
                    'target_node_id' => $e['target_node_id'],
                    'source_port'    => $e['source_port'],
                    'target_port'    => $e['target_port'],
                ];
            }, $detail['edges'] ?? []),
            'character_cards' => array_map(function($c) {
                return [
                    'character_tag'     => $c['character_tag'],
                    'character_name'    => $c['character_name'],
                    'appearance_prompt' => $c['appearance_prompt'] ?? '',
                    'negative_prompt'   => $c['negative_prompt'] ?? '',
                    'reference_images'  => $c['reference_images'] ?? '[]',
                ];
            }, $detail['character_cards'] ?? []),
        ];

        return json(['status' => 1, 'data' => $exportData]);
    }

    /**
     * 导入项目JSON
     * POST /workflow/project_import
     */
    public function project_import()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $jsonStr = input('post.json_data', '');
        if (empty($jsonStr)) {
            return json(['status' => 0, 'msg' => '请提供导入数据']);
        }

        $importData = json_decode($jsonStr, true);
        if (!$importData || !isset($importData['project'])) {
            return json(['status' => 0, 'msg' => '无效的导入数据格式']);
        }

        Db::startTrans();
        try {
            $proj = $importData['project'];
            $now = time();

            // 创建项目
            $projectId = Db::name('workflow_project')->insertGetId([
                'aid'            => $this->aid,
                'bid'            => $this->bid,
                'mdid'           => $this->mdid,
                'uid'            => $this->uid,
                'title'          => ($proj['title'] ?? '导入项目') . '（导入）',
                'description'    => $proj['description'] ?? '',
                'creation_mode'  => $proj['creation_mode'] ?? 'freestyle',
                'status'         => 'draft',
                'create_time'    => $now,
                'update_time'    => $now,
            ]);

            // 创建节点，维护ID映射
            $nodeIdMap = [];
            foreach ($importData['nodes'] ?? [] as $n) {
                $origId = $n['_original_id'] ?? 0;
                $newNodeId = Db::name('workflow_node')->insertGetId([
                    'project_id'    => $projectId,
                    'aid'           => $this->aid,
                    'bid'           => $this->bid,
                    'mdid'          => $this->mdid,
                    'uid'           => $this->uid,
                    'node_type'     => $n['node_type'],
                    'node_label'    => $n['node_label'] ?? '',
                    'position_x'    => intval($n['position_x'] ?? 0),
                    'position_y'    => intval($n['position_y'] ?? 0),
                    'config_params' => is_string($n['config_params'] ?? null) ? $n['config_params'] : json_encode($n['config_params'] ?? [], JSON_UNESCAPED_UNICODE),
                    'status'        => 'idle',
                    'create_time'   => $now,
                    'update_time'   => $now,
                ]);
                $nodeIdMap[$origId] = $newNodeId;
            }

            // 创建连线
            foreach ($importData['edges'] ?? [] as $e) {
                $srcId = $nodeIdMap[$e['source_node_id']] ?? 0;
                $tgtId = $nodeIdMap[$e['target_node_id']] ?? 0;
                if ($srcId && $tgtId) {
                    Db::name('workflow_edge')->insert([
                        'project_id'      => $projectId,
                        'source_node_id'  => $srcId,
                        'target_node_id'  => $tgtId,
                        'source_port'     => $e['source_port'] ?? '',
                        'target_port'     => $e['target_port'] ?? '',
                        'create_time'     => $now,
                    ]);
                }
            }

            // 创建角色身份卡
            foreach ($importData['character_cards'] ?? [] as $c) {
                Db::name('workflow_character_id_card')->insert([
                    'project_id'        => $projectId,
                    'aid'               => $this->aid,
                    'bid'               => $this->bid,
                    'mdid'              => $this->mdid,
                    'uid'               => $this->uid,
                    'character_tag'     => $c['character_tag'] ?? '',
                    'character_name'    => $c['character_name'] ?? '',
                    'appearance_prompt' => $c['appearance_prompt'] ?? '',
                    'negative_prompt'   => $c['negative_prompt'] ?? '',
                    'reference_images'  => $c['reference_images'] ?? '[]',
                    'create_time'       => $now,
                    'update_time'       => $now,
                ]);
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '导入成功', 'project_id' => $projectId]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['status' => 0, 'msg' => '导入失败: ' . $e->getMessage()]);
        }
    }

    // ================================================================
    // 画布页面
    // ================================================================

    /**
     * 仪表盘页面 - 项目总览统计
     * GET /workflow/dashboard
     */
    public function dashboard()
    {
        if (request()->isAjax()) {
            // 返回统计数据
            $aid = $this->aid;
            $bid = $this->bid;

            // 项目统计
            $totalProjects = Db::name('workflow_project')->where('aid', $aid)->where('bid', $bid)->count();
            $statusCounts = Db::name('workflow_project')->where('aid', $aid)->where('bid', $bid)
                ->field('status, COUNT(*) as cnt')->group('status')->select()->toArray();
            $projectStats = ['draft'=>0,'running'=>0,'completed'=>0,'failed'=>0];
            foreach ($statusCounts as $sc) { $projectStats[$sc['status']] = $sc['cnt']; }

            // 节点统计
            $totalNodes = Db::name('workflow_node')->where('aid', $aid)->where('bid', $bid)->count();
            $nodeStatusCounts = Db::name('workflow_node')->where('aid', $aid)->where('bid', $bid)
                ->field('status, COUNT(*) as cnt')->group('status')->select()->toArray();
            $nodeStats = ['idle'=>0,'configured'=>0,'running'=>0,'polling'=>0,'succeeded'=>0,'failed'=>0];
            foreach ($nodeStatusCounts as $ns) { $nodeStats[$ns['status']] = $ns['cnt']; }

            // 节点类型分布
            $nodeTypeCounts = Db::name('workflow_node')->where('aid', $aid)->where('bid', $bid)
                ->field('node_type, COUNT(*) as cnt')->group('node_type')->select()->toArray();

            // 模板统计
            $totalTemplates = Db::name('workflow_preset_template')
                ->where(function($q) use($aid, $bid) {
                    $q->where('is_system', 1)->whereOr(function($q2) use($aid, $bid) {
                        $q2->where('aid', $aid)->where('bid', $bid);
                    });
                })->where('status', 1)->count();

            // 资源统计
            $totalResources = Db::name('workflow_resource')->where('aid', $aid)->where('bid', $bid)->count();
            $resourceTypeCounts = Db::name('workflow_resource')->where('aid', $aid)->where('bid', $bid)
                ->field('resource_type, COUNT(*) as cnt')->group('resource_type')->select()->toArray();

            // 最近项目（5个）
            $recentProjects = Db::name('workflow_project')->where('aid', $aid)->where('bid', $bid)
                ->order('update_time desc')->limit(5)->select()->toArray();
            foreach ($recentProjects as &$rp) {
                $rp['update_time_text'] = $rp['update_time'] ? date('m-d H:i', $rp['update_time']) : '-';
                $rp['node_count'] = Db::name('workflow_node')->where('project_id', $rp['id'])->count();
            }
            unset($rp);

            // 最近执行日志（7天内）
            $weekAgo = time() - 7 * 86400;
            $recentLogs = Db::name('workflow_exec_log')->where('aid', $aid)->where('bid', $bid)
                ->where('create_time', '>=', $weekAgo)
                ->field('DATE(FROM_UNIXTIME(create_time)) as day, COUNT(*) as cnt')
                ->group('day')->order('day asc')->select()->toArray();

            // 每日执行数统计（7天）
            $dailyExec = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $dailyExec[$day] = 0;
            }
            foreach ($recentLogs as $rl) {
                if (isset($dailyExec[$rl['day']])) $dailyExec[$rl['day']] = $rl['cnt'];
            }

            // 角色身份卡统计
            $totalCharacters = Db::name('workflow_character_id_card')->where('aid', $aid)->where('bid', $bid)->count();

            return json([
                'status' => 1,
                'data' => [
                    'project_stats'     => $projectStats,
                    'total_projects'    => $totalProjects,
                    'node_stats'        => $nodeStats,
                    'total_nodes'       => $totalNodes,
                    'node_type_counts'  => $nodeTypeCounts,
                    'total_templates'   => $totalTemplates,
                    'total_resources'   => $totalResources,
                    'resource_type_counts' => $resourceTypeCounts,
                    'total_characters'  => $totalCharacters,
                    'recent_projects'   => $recentProjects,
                    'daily_exec'        => $dailyExec,
                ]
            ]);
        }

        return View::fetch();
    }

    /**
     * 画布另存为模板
     * POST /workflow/save_as_template
     */
    public function save_as_template()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $projectId    = input('post.project_id', 0);
        $templateName = input('post.template_name', '');
        $genre        = input('post.genre', '');
        $description  = input('post.description', '');
        $coverImage   = input('post.cover_image', '');

        if (empty($templateName)) {
            return json(['status' => 0, 'msg' => '请填写模板名称']);
        }

        $detail = $this->projectService->getProjectDetail($projectId, $this->aid, $this->bid);
        if (!$detail) {
            return json(['status' => 0, 'msg' => '项目不存在']);
        }

        // 构建画布模板数据
        $canvasTemplate = [
            'nodes' => array_map(function($n) {
                return [
                    'node_type'     => $n['node_type'],
                    'node_label'    => $n['node_label'],
                    'position_x'    => $n['position_x'],
                    'position_y'    => $n['position_y'],
                    'config_params' => $n['config_params'],
                    '_original_id'  => $n['id'],
                ];
            }, $detail['nodes'] ?? []),
            'edges' => array_map(function($e) {
                return [
                    'source_node_id' => $e['source_node_id'],
                    'target_node_id' => $e['target_node_id'],
                    'source_port'    => $e['source_port'],
                    'target_port'    => $e['target_port'],
                ];
            }, $detail['edges'] ?? []),
        ];

        // 提取默认模型配置
        $defaultModels = [];
        foreach ($detail['nodes'] ?? [] as $n) {
            $cfg = $n['config_params'];
            if (is_string($cfg)) { $cfg = json_decode($cfg, true) ?: []; }
            if (isset($cfg['model'])) { $defaultModels[$n['node_type']] = $cfg['model']; }
        }

        $now = time();
        try {
            $newId = Db::name('workflow_preset_template')->insertGetId([
                'aid'             => $this->aid,
                'bid'             => $this->bid,
                'mdid'            => $this->mdid,
                'template_name'   => $templateName,
                'genre'           => $genre,
                'episode_count'   => $detail['nodes'] ? count(array_filter($detail['nodes'], function($n) { return $n['node_type'] === 'script'; })) : 1,
                'description'     => $description,
                'cover_image'     => $coverImage,
                'canvas_template' => json_encode($canvasTemplate, JSON_UNESCAPED_UNICODE),
                'default_models'  => json_encode($defaultModels, JSON_UNESCAPED_UNICODE),
                'is_system'       => 0,
                'status'          => 1,
                'sort'            => 0,
                'create_time'     => $now,
                'update_time'     => $now,
            ]);

            return json(['status' => 1, 'msg' => '已保存为模板', 'id' => $newId]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 保存工作流快照
     * POST /workflow/save_snapshot
     */
    public function save_snapshot()
    {
        $projectId = input('post.project_id', 0);
        $label     = input('post.label', '快照 ' . date('m-d H:i'));

        $detail = $this->projectService->getProjectDetail($projectId, $this->aid, $this->bid);
        if (!$detail) {
            return json(['status' => 0, 'msg' => '项目不存在']);
        }

        $snapshot = [
            'nodes' => $detail['nodes'] ?? [],
            'edges' => $detail['edges'] ?? [],
            'character_cards' => $detail['character_cards'] ?? [],
        ];

        try {
            Db::name('workflow_exec_log')->insert([
                'project_id'  => $projectId,
                'aid'         => $this->aid,
                'bid'         => $this->bid,
                'node_id'     => 0,
                'log_type'    => 'snapshot',
                'log_level'   => 'info',
                'message'     => $label,
                'context_data'=> json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
            ]);

            return json(['status' => 1, 'msg' => '快照已保存: ' . $label]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取快照列表
     * GET /workflow/snapshot_list
     */
    public function snapshot_list()
    {
        $projectId = input('param.project_id', 0);
        $list = Db::name('workflow_exec_log')
            ->where('project_id', $projectId)
            ->where('log_type', 'snapshot')
            ->order('create_time desc')
            ->limit(20)
            ->select()->toArray();

        foreach ($list as &$item) {
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '-';
        }
        unset($item);

        return json(['status' => 1, 'data' => $list]);
    }

    /**
     * 恢复快照
     * POST /workflow/restore_snapshot
     */
    public function restore_snapshot()
    {
        $snapshotId = input('post.snapshot_id', 0);
        $projectId  = input('post.project_id', 0);

        $snapshot = Db::name('workflow_exec_log')
            ->where('id', $snapshotId)
            ->where('log_type', 'snapshot')
            ->find();

        if (!$snapshot) {
            return json(['status' => 0, 'msg' => '快照不存在']);
        }

        $data = json_decode($snapshot['context_data'], true);
        if (!$data) {
            return json(['status' => 0, 'msg' => '快照数据损坏']);
        }

        Db::startTrans();
        try {
            // 删除现有节点和连线
            Db::name('workflow_node')->where('project_id', $projectId)->delete();
            Db::name('workflow_edge')->where('project_id', $projectId)->delete();

            $now = time();
            $nodeIdMap = [];

            // 恢复节点
            foreach ($data['nodes'] ?? [] as $n) {
                $origId = $n['id'] ?? 0;
                $newId = Db::name('workflow_node')->insertGetId([
                    'project_id'    => $projectId,
                    'aid'           => $this->aid,
                    'bid'           => $this->bid,
                    'mdid'          => $this->mdid,
                    'uid'           => $this->uid,
                    'node_type'     => $n['node_type'],
                    'node_label'    => $n['node_label'] ?? '',
                    'position_x'    => intval($n['position_x'] ?? 0),
                    'position_y'    => intval($n['position_y'] ?? 0),
                    'config_params' => is_string($n['config_params'] ?? '') ? ($n['config_params'] ?? '{}') : json_encode($n['config_params'] ?? [], JSON_UNESCAPED_UNICODE),
                    'status'        => $n['status'] ?? 'idle',
                    'output_data'   => is_string($n['output_data'] ?? '') ? ($n['output_data'] ?? '') : json_encode($n['output_data'] ?? '', JSON_UNESCAPED_UNICODE),
                    'create_time'   => $now,
                    'update_time'   => $now,
                ]);
                $nodeIdMap[$origId] = $newId;
            }

            // 恢复连线
            foreach ($data['edges'] ?? [] as $e) {
                $srcId = $nodeIdMap[$e['source_node_id']] ?? 0;
                $tgtId = $nodeIdMap[$e['target_node_id']] ?? 0;
                if ($srcId && $tgtId) {
                    Db::name('workflow_edge')->insert([
                        'project_id'     => $projectId,
                        'source_node_id' => $srcId,
                        'target_node_id' => $tgtId,
                        'source_port'    => $e['source_port'] ?? '',
                        'target_port'    => $e['target_port'] ?? '',
                        'create_time'    => $now,
                    ]);
                }
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '快照已恢复，请刷新页面']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['status' => 0, 'msg' => '恢复失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 工作流画布编辑页面
     */
    public function canvas()
    {
        $projectId = input('param.id', 0);
        $detail = null;

        if ($projectId > 0) {
            $detail = $this->projectService->getProjectDetail($projectId, $this->aid, $this->bid);
        }

        View::assign('project', $detail);
        View::assign('project_id', $projectId);
        View::assign('project_json', json_encode($detail ?: new \stdClass(), JSON_UNESCAPED_UNICODE));
        View::assign('node_types', json_encode(\app\model\WorkflowNode::typeTextMap(), JSON_UNESCAPED_UNICODE));

        return View::fetch();
    }

    // ================================================================
    // 端到端测试
    // ================================================================

    /**
     * 测试运行 - 创建测试项目并执行工作流
     * GET /workflow/test_run
     */
    public function test_run()
    {
        set_time_limit(300);
        $step = input('param.step', 'create');
        $projectId = input('param.project_id', 0);

        $log = [];

        try {
            if ($step === 'create') {
                // Step 1: 创建测试项目 + 节点 + 连线
                $result = $this->createTestProject();
                return json($result);
            }

            if ($step === 'run' && $projectId > 0) {
                // Step 2: 运行工作流
                $result = $this->engineService->runWorkflow($projectId, $this->aid, $this->bid);
                return json(['status' => 1, 'msg' => '工作流已启动', 'run_result' => $result]);
            }

            if ($step === 'poll' && $projectId > 0) {
                // Step 3: 轮询进度
                $pollResult = $this->engineService->pollAsyncNodes($projectId, $this->aid, $this->bid);
                $progress = $this->engineService->getProgress($projectId);
                return json(['status' => 1, 'poll_result' => $pollResult, 'progress' => $progress]);
            }

            if ($step === 'status' && $projectId > 0) {
                // 查看全部节点状态
                $nodes = \app\model\WorkflowNode::where('project_id', $projectId)
                    ->field('id, node_type, node_label, status, error_message, output_data, execute_time')
                    ->order('sort_order asc')
                    ->select()->toArray();
                $project = \app\model\WorkflowProject::find($projectId);
                return json([
                    'status' => 1,
                    'project_status' => $project ? $project->status : 'unknown',
                    'nodes' => $nodes,
                ]);
            }

            if ($step === 'check_llm') {
                // 检查LLM服务可用性
                $cloudLLM = new \app\service\CloudLLMService();
                $availability = $cloudLLM->checkAvailability();
                $ollamaAvailable = \app\service\CloudLLMService::isOllamaAvailable();
                return json([
                    'status' => 1,
                    'ollama_available' => $ollamaAvailable,
                    'cloud_llm' => $availability,
                ]);
            }

            return json(['status' => 0, 'msg' => '未知step参数: ' . $step]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '测试异常: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 创建测试项目（简化版 - 1集×2个分镜）
     */
    protected function createTestProject()
    {
        $now = time();

        // 1. 创建项目
        $projectId = Db::name('workflow_project')->insertGetId([
            'aid'            => $this->aid,
            'bid'            => $this->bid,
            'mdid'           => $this->mdid,
            'uid'            => $this->uid,
            'title'          => '测试短剧-' . date('mdHis'),
            'description'    => '甜宠题材自动化测试',
            'creation_mode'  => 'oneclick',
            'status'         => 'draft',
            'create_time'    => $now,
            'update_time'    => $now,
        ]);

        // 2. 创建6个节点
        $nodeIds = [];
        $nodeConfigs = [
            ['type' => 'script', 'label' => '剧本生成', 'sort' => 1, 'config' => [
                'creativity' => '一个都市女孩在咖啡店意外遇见大学时的暧昧对象，两人从尴尬到重新心动',
                'episodes' => 1, 'duration' => 30, 'genre' => '甜宠',
            ]],
            ['type' => 'character', 'label' => '角色生成', 'sort' => 2, 'config' => [
                'style' => 'realistic',
            ]],
            ['type' => 'storyboard', 'label' => '分镜生成', 'sort' => 3, 'config' => [
                'resolution' => '720P',
            ]],
            ['type' => 'video', 'label' => '视频生成', 'sort' => 4, 'config' => [
                'duration' => 5, 'generation_mode' => 'first_frame',
            ]],
            ['type' => 'voice', 'label' => '配音生成', 'sort' => 5, 'config' => [
                'speed' => 1.0, 'synth_mode' => 'sound_design',
            ]],
            ['type' => 'compose', 'label' => '成片合成', 'sort' => 6, 'config' => [
                'transition' => 'fade', 'output_format' => 'mp4',
            ]],
        ];

        foreach ($nodeConfigs as $nc) {
            $nodeId = Db::name('workflow_node')->insertGetId([
                'project_id'    => $projectId,
                'aid'           => $this->aid,
                'bid'           => $this->bid,
                'mdid'          => $this->mdid,
                'uid'           => $this->uid,
                'node_type'     => $nc['type'],
                'node_label'    => $nc['label'],
                'status'        => $nc['type'] === 'script' ? 'configured' : 'waiting',
                'config_params' => json_encode($nc['config'], JSON_UNESCAPED_UNICODE),
                'position_x'    => $nc['sort'] * 200,
                'position_y'    => 300,
                'create_time'   => $now,
                'update_time'   => $now,
            ]);
            $nodeIds[$nc['type']] = $nodeId;
        }

        // 3. 创建连线（DAG）
        $edges = [
            ['source' => 'script', 'target' => 'character', 'sp' => 'characters', 'tp' => 'characters'],
            ['source' => 'script', 'target' => 'storyboard', 'sp' => 'scenes', 'tp' => 'scenes'],
            ['source' => 'script', 'target' => 'voice', 'sp' => 'dialogue', 'tp' => 'dialogue'],
            ['source' => 'character', 'target' => 'storyboard', 'sp' => 'character_assets', 'tp' => 'character_assets'],
            ['source' => 'storyboard', 'target' => 'video', 'sp' => 'frames', 'tp' => 'frames'],
            ['source' => 'video', 'target' => 'compose', 'sp' => 'clips', 'tp' => 'clips'],
            ['source' => 'voice', 'target' => 'compose', 'sp' => 'audio_clips', 'tp' => 'audio_clips'],
        ];

        foreach ($edges as $e) {
            Db::name('workflow_edge')->insert([
                'project_id'      => $projectId,
                'source_node_id'  => $nodeIds[$e['source']],
                'target_node_id'  => $nodeIds[$e['target']],
                'source_port'     => $e['sp'],
                'target_port'     => $e['tp'],
                'create_time'     => $now,
            ]);
        }

        return [
            'status'     => 1,
            'msg'        => '测试项目创建成功',
            'project_id' => $projectId,
            'node_ids'   => $nodeIds,
            'next_step'  => '?s=/Workflow/test_run&step=run&project_id=' . $projectId,
        ];
    }
}

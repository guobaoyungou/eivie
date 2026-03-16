<?php
/**
 * 视频生成控制器
 * 管理视频生成任务、生成记录、场景模板
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\GenerationService;
use app\service\GenerationOrderService;
use app\service\MerchantApiKeyService;
use app\model\GenerationRecord;

class VideoGeneration extends Common
{
    protected $service;
    protected $generationType = 2; // 视频生成

    public function initialize()
    {
        parent::initialize();
        $this->service = new GenerationService();
        
        // 商户用户访问时，检查API Key配置状态
        if ($this->bid > 0) {
            $apiKeyService = new MerchantApiKeyService();
            $checkResult = $apiKeyService->checkVideoApiConfig($this->bid, $this->mdid);
            if (!$checkResult['has_config']) {
                $configUrl = (string)url('MerchantApiKey/index');
                if (request()->isAjax()) {
                    echo json_encode([
                        'code' => 403,
                        'status' => 0,
                        'msg' => $checkResult['msg'],
                        'need_config' => true,
                        'redirect_url' => $configUrl
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                } else {
                    // 普通页面访问，直接显示引导配置页面并阻断后续执行
                    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>需要配置API Key</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/static/layui/css/layui.css">
    <style>
        body { background: #f5f5f5; }
        .config-tip-box { max-width: 500px; margin: 100px auto; background: #fff; border-radius: 8px; padding: 40px; text-align: center; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        .config-tip-box .icon { font-size: 64px; color: #ffb800; margin-bottom: 20px; }
        .config-tip-box h2 { font-size: 22px; color: #333; margin-bottom: 15px; }
        .config-tip-box p { color: #666; font-size: 14px; line-height: 1.8; margin-bottom: 30px; }
        .config-tip-box .btn-group .layui-btn { margin: 0 10px; }
    </style>
</head>
<body>
    <div class="config-tip-box">
        <i class="layui-icon layui-icon-about icon"></i>
        <h2>需要配置API Key</h2>
        <p>' . htmlspecialchars($checkResult['msg']) . '<br>请先配置视频生成所需的API Key，配置完成后即可使用此功能。</p>
        <div class="btn-group">
            <a href="' . $configUrl . '" class="layui-btn layui-btn-normal layui-btn-lg"><i class="layui-icon layui-icon-set"></i> 去配置</a>
            <a href="javascript:history.back()" class="layui-btn layui-btn-primary layui-btn-lg">返回</a>
        </div>
    </div>
</body>
</html>';
                    echo $html;
                    exit;
                }
            }
        }
    }

    /**
     * 生成任务页面 - 选择模型并提交生成
     */
    public function task_create()
    {
        if (request()->isAjax() && request()->isPost()) {
            $inputParams = input('post.params/a', []);
            $modelId = input('post.model_id', 0);
            $sceneId = input('post.scene_id', 0);
            
            $result = $this->service->createTask([
                'aid' => aid,
                'bid' => bid,
                'uid' => uid,
                'generation_type' => $this->generationType,
                'model_id' => $modelId,
                'scene_id' => $sceneId,
                'input_params' => $inputParams
            ]);
            
            if ($result['status'] == 1) {
                \app\common\System::plog('创建视频生成任务 记录ID:' . $result['record_id'], 1);
            }
            
            return json($result);
        }
        
        // 获取可用模型列表
        $models = $this->service->getAvailableModels('video');
        
        // 获取场景模板列表
        $templates = Db::name('generation_scene_template')
            ->where('aid', aid)
            ->where('bid', bid)
            ->where('generation_type', $this->generationType)
            ->where('status', 1)
            ->order('sort asc, id desc')
            ->select()->toArray();
        
        View::assign('models', $models);
        View::assign('templates', $templates);
        View::assign('models_json', json_encode($models, JSON_UNESCAPED_UNICODE));
        
        return View::fetch();
    }

    /**
     * 获取模型参数Schema
     */
    public function get_model_schema()
    {
        $modelId = input('param.model_id', 0);
        $model = $this->service->getModelDetail($modelId);
        
        if (!$model) {
            return json(['status' => 0, 'msg' => '模型不存在']);
        }
        
        return json([
            'status' => 1,
            'data' => [
                'model_name' => $model['model_name'],
                'input_schema' => $model['input_schema'],
                'api_key_configured' => $model['api_key_configured']
            ]
        ]);
    }

    /**
     * 获取模板详情
     */
    public function get_template_detail()
    {
        $templateId = input('param.template_id', 0);
        $template = $this->service->getTemplateDetail($templateId);
        
        if (!$template) {
            return json(['status' => 0, 'msg' => '模板不存在']);
        }
        
        return json(['status' => 1, 'data' => $template]);
    }

    /**
     * 生成记录列表
     */
    public function record_list()
    {
        // 获取会员ID（如果是会员登录）
        $sessionId = \think\facade\Session::getId();
        $memberMid = 0;
        if ($sessionId) {
            $memberMid = intval(cache($sessionId . '_mid'));
        }
        
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);
            
            $where = [
                ['r.generation_type', '=', $this->generationType]
            ];
            
            // 会员只能查看自己的记录（通过关联订单表获取mid过滤）
            if ($memberMid > 0) {
                // 使用子查询关联订单表过滤会员记录
                $orderSubQuery = Db::name('generation_order')
                    ->where('mid', $memberMid)
                    ->field('id')
                    ->buildSql();
                $where[] = ['r.order_id', 'exp', "IN $orderSubQuery OR r.uid = " . $memberMid];
            } else {
                // 商家查看自己的记录
                $where[] = ['r.aid', '=', aid];
                $where[] = ['r.bid', '=', bid];
            }
            
            // 搜索条件
            if (input('param.status') !== '' && input('param.status') !== null) {
                $where[] = ['r.status', '=', intval(input('param.status'))];
            }
            if (input('param.model_id')) {
                $where[] = ['r.model_id', '=', intval(input('param.model_id'))];
            }
            if (input('param.date_start')) {
                $where[] = ['r.create_time', '>=', strtotime(input('param.date_start'))];
            }
            if (input('param.date_end')) {
                $where[] = ['r.create_time', '<=', strtotime(input('param.date_end') . ' 23:59:59')];
            }
            
            $order = 'r.id desc';
            if (input('param.field') && input('param.order')) {
                $order = 'r.' . input('param.field') . ' ' . input('param.order');
            }
            
            $result = $this->service->getRecordList($where, $page, $limit, $order);
            
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $result['count'], 'data' => $result['data']]);
        }
        
        // 获取模型列表用于筛选
        $models = $this->service->getAvailableModels('video');
        View::assign('models', $models);
        
        return View::fetch();
    }

    /**
     * 记录详情
     */
    public function record_detail()
    {
        $recordId = input('param.id', 0);
        $record = $this->service->getRecordDetail($recordId);
        
        if (!$record) {
            return $this->error('记录不存在');
        }
        
        View::assign('record', $record);
        return View::fetch();
    }
    
    /**
     * 获取记录状态（前端轮询接口）
     */
    public function get_record_status()
    {
        $recordId = input('param.record_id', 0);
        $result = $this->service->getRecordStatus($recordId);
        return json($result);
    }

    /**
     * 重试任务
     */
    public function task_retry()
    {
        $recordId = input('post.id', 0);
        $result = $this->service->retryTask($recordId);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('重试视频生成任务 ID:' . $recordId, 1);
        }
        
        return json($result);
    }
    
    /**
     * 重新获取Seedance任务结果
     * 用于修复已完成但未正确保存输出的任务
     */
    public function refetch_result()
    {
        $recordId = input('post.id', 0);
        $result = $this->service->refetchSeedanceResult($recordId);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('重新获取视频生成结果 ID:' . $recordId, 1);
        }
        
        return json($result);
    }

    /**
     * 取消任务
     */
    public function task_cancel()
    {
        $recordId = input('post.id', 0);
        $result = $this->service->cancelTask($recordId);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('取消视频生成任务 ID:' . $recordId, 1);
        }
        
        return json($result);
    }

    /**
     * 删除记录
     */
    public function record_delete()
    {
        $recordId = input('post.id', 0);
        $result = $this->service->deleteRecord($recordId);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('删除视频生成记录 ID:' . $recordId, 1);
        }
        
        return json($result);
    }

    /**
     * 一键转为场景模板
     * GET: 渲染转模板表单页面，提取原始提示词供编辑
     * POST: 提交创建模板，支持提示词字段
     */
    public function convert_to_template()
    {
        if (request()->isPost()) {
            $recordId = input('post.record_id', 0);
            $templateData = [
                'template_name' => input('post.template_name'),
                'category' => input('post.category', ''),
                'category_ids' => input('post.category_ids', ''),
                'mdid' => input('post.mdid/d', 0),
                'description' => input('post.description', ''),
                'cover_image' => input('post.cover_image', ''),
                'is_public' => input('post.is_public', 0),
                'prompt' => input('post.prompt', '')  // 新增：接收提示词字段
            ];
            
            // category_ids 后端兆底校验：最多10个分类
            if (!empty($templateData['category_ids'])) {
                $catIds = array_filter(explode(',', $templateData['category_ids']));
                if (count($catIds) > 10) {
                    return json(['status' => 0, 'msg' => '最多只能选择10个分类']);
                }
                $templateData['category_ids'] = implode(',', $catIds);
            }
            
            // 允许修改默认参数
            if (input('post.default_params')) {
                $templateData['default_params'] = input('post.default_params/a');
            }
            
            $result = $this->service->convertToTemplate($recordId, $templateData);
            
            if ($result['status'] == 1) {
                \app\common\System::plog('视频生成记录转为模板 记录ID:' . $recordId . ' 模板ID:' . $result['template_id'], 1);
            }
            
            return json($result);
        }
        
        $recordId = input('param.record_id', 0);
        $record = $this->service->getRecordDetail($recordId);
        
        if (!$record) {
            return $this->error('记录不存在');
        }
        
        // 新增：检查该记录是否已转换过模板
        $existingTemplate = Db::name('generation_scene_template')
            ->where('source_record_id', $recordId)
            ->find();
        if ($existingTemplate) {
            return $this->error('该记录已经转换过模板，不能重复转换');
        }
        
        // 新增：从 input_params 中提取 prompt 供表单预填
        $prompt = '';
        $originalImage = '';  // 新增：原图片（首帧图）
        if (!empty($record['input_params'])) {
            $inputParams = $record['input_params'];
            if (is_string($inputParams)) {
                $inputParams = json_decode($inputParams, true) ?: [];
            }
            $prompt = $inputParams['prompt'] ?? '';
            // 提取原图片URL（首帧图）
            $originalImage = $inputParams['first_frame_image'] 
                ?? $inputParams['image_url'] 
                ?? $inputParams['image'] 
                ?? $inputParams['input_image'] 
                ?? '';
        }
        
        // 新增：判断第一个输出的类型（video/image）
        $firstOutputType = 'image';
        if (!empty($record['outputs']) && isset($record['outputs'][0])) {
            $firstOutputType = $record['outputs'][0]['output_type'] ?? 'image';
        }
        
        View::assign('record', $record);
        View::assign('prompt', $prompt);  // 新增：传递提示词到视图
        View::assign('original_image', $originalImage);  // 新增：传递原图片到视图
        View::assign('first_output_type', $firstOutputType);  // 新增：传递输出类型到视图
        
        // 新增：查询场景分类列表
        $categories = Db::name('generation_scene_category')
            ->where('aid', aid)
            ->where('bid', bid)
            ->where('generation_type', $this->generationType)
            ->field('id, name')
            ->order('sort asc, id asc')
            ->select()->toArray();
        View::assign('categories', $categories);
        
        // 新增：查询门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', aid)
            ->where('bid', 'in', [bid, 0])
            ->field('id, name')
            ->order('id asc')
            ->select()->toArray();
        View::assign('mendian_list', $mendian_list);
        
        return View::fetch();
    }

    /**
     * 场景模板列表
     */
    public function scene_list()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);
            
            $where = [
                ['t.aid', '=', aid],
                ['t.bid', '=', bid],
                ['t.generation_type', '=', $this->generationType]
            ];
            
            // 搜索条件
            if (input('param.keyword')) {
                $keyword = input('param.keyword');
                $where[] = ['t.template_name|t.template_code', 'like', '%' . $keyword . '%'];
            }
            if (input('param.status') !== '' && input('param.status') !== null) {
                $where[] = ['t.status', '=', intval(input('param.status'))];
            }
            if (input('param.category')) {
                $where[] = ['t.category', '=', input('param.category')];
            }
            
            $order = 't.sort asc, t.id desc';
            if (input('param.field') && input('param.order')) {
                $order = 't.' . input('param.field') . ' ' . input('param.order');
            }
            
            $result = $this->service->getTemplateList($where, $page, $limit, $order);
            
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $result['count'], 'data' => $result['data']]);
        }
        
        return View::fetch();
    }

    /**
     * 场景模板编辑
     */
    public function scene_edit()
    {
        $id = input('param.id', 0);
        $info = [];
        
        if ($id > 0) {
            $info = $this->service->getTemplateDetail($id);
            if (!$info) {
                return $this->error('模板不存在');
            }
            // 解析lvprice_data
            if (!empty($info['lvprice_data']) && is_string($info['lvprice_data'])) {
                $info['lvprice_data'] = json_decode($info['lvprice_data'], true) ?: [];
            }
        }
        
        // 获取可用模型
        $models = $this->service->getAvailableModels('video');
        
        // 获取会员等级列表（用于价格配置）
        $default_cid = Db::name('member_level_category')->where('aid', aid)->where('isdefault', 1)->value('id');
        $default_cid = $default_cid ? $default_cid : 0;
        $levellist = Db::name('member_level')
            ->where('aid', aid)
            ->where('cid', $default_cid)
            ->order('sort asc, id asc')
            ->select()->toArray();
        
        // 获取当前绑定模型的最大输出数量能力
        $modelMaxOutput = 0;
        if (!empty($info['model_id'])) {
            $modelMaxOutput = $this->service->getModelMaxOutput($info['model_id']);
        }
        
        View::assign('info', $info);
        View::assign('models', $models);
        View::assign('models_json', json_encode($models, JSON_UNESCAPED_UNICODE));
        View::assign('levellist', $levellist);
        View::assign('model_max_output', $modelMaxOutput);
        
        return View::fetch();
    }

    /**
     * 保存场景模板
     */
    public function scene_save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }
        
        $data = input('post.info/a');
        $data['aid'] = aid;
        $data['bid'] = bid;
        $data['generation_type'] = $this->generationType;
        
        // 处理会员价格数据
        $lvpriceData = input('post.lvprice_data/a', []);
        if (!empty($lvpriceData)) {
            // 过滤空值，保留有效价格
            $filteredData = [];
            foreach ($lvpriceData as $levelId => $price) {
                if ($price !== '' && $price !== null) {
                    $filteredData[$levelId] = round(floatval($price), 2);
                }
            }
            $data['lvprice_data'] = json_encode($filteredData, JSON_UNESCAPED_UNICODE);
        } else {
            $data['lvprice_data'] = '';
        }
        
        // ========== 处理分销设置数据 ==========
        $commissiondata1 = input('post.commissiondata1/a', []);
        $data['commissiondata1'] = !empty($commissiondata1) ? json_encode($commissiondata1, JSON_UNESCAPED_UNICODE) : '';
        
        $commissiondata2 = input('post.commissiondata2/a', []);
        $data['commissiondata2'] = !empty($commissiondata2) ? json_encode($commissiondata2, JSON_UNESCAPED_UNICODE) : '';
        
        $commissiondata3 = input('post.commissiondata3/a', []);
        $data['commissiondata3'] = !empty($commissiondata3) ? json_encode($commissiondata3, JSON_UNESCAPED_UNICODE) : '';
        
        // ========== 处理团队分红数据 ==========
        $teamfenhongdata1 = input('post.teamfenhongdata1/a', []);
        $data['teamfenhongdata1'] = !empty($teamfenhongdata1) ? json_encode($teamfenhongdata1, JSON_UNESCAPED_UNICODE) : '';
        
        $teamfenhongdata2 = input('post.teamfenhongdata2/a', []);
        $data['teamfenhongdata2'] = !empty($teamfenhongdata2) ? json_encode($teamfenhongdata2, JSON_UNESCAPED_UNICODE) : '';
        
        // ========== 处理股东分红数据 ==========
        $gdfenhongdata1 = input('post.gdfenhongdata1/a', []);
        $data['gdfenhongdata1'] = !empty($gdfenhongdata1) ? json_encode($gdfenhongdata1, JSON_UNESCAPED_UNICODE) : '';
        
        $gdfenhongdata2 = input('post.gdfenhongdata2/a', []);
        $data['gdfenhongdata2'] = !empty($gdfenhongdata2) ? json_encode($gdfenhongdata2, JSON_UNESCAPED_UNICODE) : '';
        
        // ========== 处理区域代理分红数据 ==========
        $areafenhongdata1 = input('post.areafenhongdata1/a', []);
        $data['areafenhongdata1'] = !empty($areafenhongdata1) ? json_encode($areafenhongdata1, JSON_UNESCAPED_UNICODE) : '';
        
        $areafenhongdata2 = input('post.areafenhongdata2/a', []);
        $data['areafenhongdata2'] = !empty($areafenhongdata2) ? json_encode($areafenhongdata2, JSON_UNESCAPED_UNICODE) : '';
        
        // ========== 处理显示/购买条件 ==========
        if (isset($data['showtj']) && is_array($data['showtj'])) {
            $data['showtj'] = implode(',', $data['showtj']);
        } elseif (!isset($data['showtj']) || $data['showtj'] === '') {
            $data['showtj'] = '-1';
        }
        
        if (isset($data['gettj']) && is_array($data['gettj'])) {
            $data['gettj'] = implode(',', $data['gettj']);
        } elseif (!isset($data['gettj']) || $data['gettj'] === '') {
            $data['gettj'] = '-1';
        }
        
        $result = $this->service->saveTemplate($data);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('保存视频场景模板:' . $data['template_name'], 1);
        }
        
        return json($result);
    }

    /**
     * 删除场景模板
     */
    public function scene_delete()
    {
        $id = input('post.id', 0);
        $result = $this->service->deleteTemplate($id);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('删除视频场景模板 ID:' . $id, 1);
        }
        
        return json($result);
    }

    /**
     * 更新模板状态
     */
    public function scene_status()
    {
        $id = input('post.id', 0);
        $status = input('post.status', 0);
        
        $result = $this->service->updateTemplateStatus($id, $status);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('更新视频场景模板状态 ID:' . $id . ' 状态:' . $status, 1);
        }
        
        return json($result);
    }
    
    /**
     * 批量迁移GIF封面为WebP格式
     * POST请求，返回迁移结果统计
     */
    public function batch_migrate_webp()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }
        
        $limit = input('post.limit', 10, 'intval');
        if ($limit < 1) $limit = 10;
        if ($limit > 50) $limit = 50;
        
        $action = input('post.action', 'migrate'); // migrate=迁移GIF到WebP, generate=生成缺失封面
        
        if ($action === 'generate') {
            // 为gif_cover为空的视频模板生成动态封面
            $result = $this->service->batchGenerateGifCovers($limit);
        } else {
            // 将已有GIF封面迁移为WebP
            $result = $this->service->batchMigrateToWebpCovers($limit);
        }
        
        // 查询剩余待处理数量
        $remainGif = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', 'like', '%.gif')
            ->count();
        $remainEmpty = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', '')
            ->where('cover_image', '<>', '')
            ->count();
        
        \app\common\System::plog('批量迁移视频封面WebP action:' . $action . ' 成功:' . $result['success'] . ' 失败:' . $result['failed'], 1);
        
        return json([
            'status' => 1,
            'msg' => '处理完成',
            'data' => array_merge($result, [
                'remain_gif' => $remainGif,
                'remain_empty' => $remainEmpty
            ])
        ]);
    }
    
    /**
     * 获取封面迁移统计信息
     */
    public function cover_migrate_stats()
    {
        $totalGif = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', 'like', '%.gif')
            ->count();
        $totalWebp = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', 'like', '%.webp')
            ->count();
        $totalEmpty = Db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('gif_cover', '')
            ->where('cover_image', '<>', '')
            ->count();
        
        return json([
            'status' => 1,
            'data' => [
                'gif_count' => $totalGif,
                'webp_count' => $totalWebp,
                'empty_count' => $totalEmpty
            ]
        ]);
    }
    
    /**
     * 场景模板附件转存重试
     */
    public function scene_retry_transfer()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $result = $this->service->retryTransferForTemplate($id);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('重试视频场景模板转存 ID:' . $id, 1);
        }
        
        return json($result);
    }
    
    /**
     * 获取模型的最大输出数量能力
     * 用于前端切换模型时动态更新 output_quantity 上限
     */
    public function get_model_max_output()
    {
        $modelId = input('param.model_id', 0, 'intval');
        
        if (!$modelId) {
            return json(['status' => 0, 'msg' => '请选择模型']);
        }
        
        $maxOutput = $this->service->getModelMaxOutput($modelId);
        
        return json([
            'status' => 1,
            'data' => [
                'model_id' => $modelId,
                'max_output' => $maxOutput
            ]
        ]);
    }

    // =====================================================
    // 订单管理与退款审核
    // =====================================================

    /**
     * 订单列表页
     */
    public function order_list()
    {
        // 获取会员ID（如果是会员登录）
        $sessionId = \think\facade\Session::getId();
        $memberMid = 0;
        if ($sessionId) {
            $memberMid = intval(cache($sessionId . '_mid'));
        }
        
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);
            $order = 'o.id desc';
            if (input('param.field') && input('param.order')) {
                $order = 'o.' . input('param.field') . ' ' . input('param.order');
            }

            $where = [
                ['o.generation_type', '=', $this->generationType],
                ['o.status', '=', 1]
            ];
            
            // 会员只能查看自己的订单
            if ($memberMid > 0) {
                $where[] = ['o.mid', '=', $memberMid];
            } else {
                // 商家查看自己的订单
                $where[] = ['o.aid', '=', aid];
                if (bid > 0) {
                    $where[] = ['o.bid', '=', bid];
                }
            }

            // 支付状态
            if (input('?param.pay_status') && input('param.pay_status') !== '') {
                $where[] = ['o.pay_status', '=', intval(input('param.pay_status'))];
            }
            // 退款状态
            if (input('?param.refund_status') && input('param.refund_status') !== '') {
                $where[] = ['o.refund_status', '=', intval(input('param.refund_status'))];
            }
            // 关键词搜索
            if (input('param.keyword')) {
                $keyword = input('param.keyword');
                $where[] = ['o.ordernum|m.nickname', 'like', '%' . $keyword . '%'];
            }
            // 时间范围
            if (input('param.ctime')) {
                $ctime = explode(' ~ ', input('param.ctime'));
                if (count($ctime) == 2) {
                    $where[] = ['o.createtime', '>=', strtotime($ctime[0])];
                    $where[] = ['o.createtime', '<', strtotime($ctime[1]) + 86400];
                }
            }

            $orderService = new GenerationOrderService();
            $result = $orderService->getOrderList($where, $page, $limit, $order);

            // 查询统计数据（总订单数、已支付、待支付）
            $statsWhere = [
                ['generation_type', '=', $this->generationType],
                ['status', '=', 1]
            ];
            if ($memberMid > 0) {
                $statsWhere[] = ['mid', '=', $memberMid];
            } else {
                $statsWhere[] = ['aid', '=', aid];
                if (bid > 0) {
                    $statsWhere[] = ['bid', '=', bid];
                }
            }
            
            $totalCount = Db::name('generation_order')->where($statsWhere)->count();
            $paidCount = Db::name('generation_order')->where($statsWhere)->where('pay_status', 1)->count();
            $pendingCount = Db::name('generation_order')->where($statsWhere)->where('pay_status', 0)->count();

            return json([
                'code' => 0, 
                'msg' => '查询成功', 
                'count' => $result['count'], 
                'data' => $result['data'],
                'stats' => [
                    'total' => $totalCount,
                    'paid' => $paidCount,
                    'pending' => $pendingCount
                ]
            ]);
        }

        // 会员访问时渲染模板三页面
        if ($memberMid > 0) {
            // 获取网站信息供模板使用
            $webinfo = Db::name('sysset')->where(['name'=>'webinfo'])->value('value');
            $webinfo = json_decode($webinfo, true);
            View::assign('webinfo', $webinfo);
            return View::fetch('index3/video_order');
        }
        
        return View::fetch();
    }

    /**
     * 获取订单详情
     */
    public function order_detail()
    {
        $orderId = input('param.orderid', 0);
        $orderService = new GenerationOrderService();
        $detail = $orderService->getOrderDetail($orderId, aid, (bid > 0 ? bid : 0));

        if (!$detail) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }

        return json(['status' => 1, 'data' => $detail]);
    }

    /**
     * 退款审核
     */
    public function refund_check()
    {
        $orderId = input('post.orderid/d');
        $st = input('post.st/d');
        $remark = input('post.remark', '');

        if (!$orderId || !$st) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $orderService = new GenerationOrderService();
        $result = $orderService->checkRefund($orderId, $st, $remark, aid, (bid > 0 ? bid : 0));

        if ($result['status'] == 1) {
            $action = $st == 1 ? '同意退款' : '驳回退款';
            \app\common\System::plog('视频生成订单' . $action . ' 订单ID:' . $orderId, 1);
        }

        return json($result);
    }

    /**
     * 弹窗选择场景模板（页面设计器用）
     */
    public function choosetemplate()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);
            $where = [
                ['aid', '=', aid],
                ['generation_type', '=', $this->generationType]
            ];
            if (bid > 0) {
                $where[] = ['bid', '=', bid];
            }
            if (input('param.name')) {
                $where[] = ['name', 'like', '%' . input('param.name') . '%'];
            }
            if (input('param.status') !== '' && input('param.status') !== null) {
                $where[] = ['status', '=', intval(input('param.status'))];
            }
            $count = Db::name('generation_scene_template')->where($where)->count();
            $list = Db::name('generation_scene_template')->where($where)
                ->field('id,name,cover_image,base_price,status,use_count,sort')
                ->order('sort asc, id desc')
                ->page($page, $limit)
                ->select()->toArray();
            foreach ($list as &$item) {
                $item['pic'] = $item['cover_image'];
                $item['sell_price'] = $item['base_price'];
                $item['sales'] = $item['use_count'] ?? 0;
            }
            return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
        }
        return View::fetch('video_generation/choosetemplate');
    }

    /**
     * 获取单个场景模板信息（页面设计器用）
     */
    public function gettemplate()
    {
        $id = input('param.proid', 0);
        $info = Db::name('generation_scene_template')
            ->where('aid', aid)
            ->where('generation_type', $this->generationType)
            ->where('id', $id)
            ->field('id,name,cover_image,base_price,use_count')
            ->find();
        if ($info) {
            $info['pic'] = $info['cover_image'];
            $info['sell_price'] = $info['base_price'];
            $info['market_price'] = $info['base_price'];
            $info['sales'] = $info['use_count'] ?? 0;
            $info['proid'] = $info['id'];
        }
        return json(['product' => $info]);
    }

    /**
     * 批量获取场景模板信息（页面设计器用）
     */
    public function gettemplatelist()
    {
        $ids = input('param.proids/a', []);
        $list = [];
        if (!empty($ids)) {
            $items = Db::name('generation_scene_template')
                ->where('aid', aid)
                ->where('generation_type', $this->generationType)
                ->where('id', 'in', $ids)
                ->field('id,name,cover_image,base_price,use_count')
                ->select()->toArray();
            foreach ($items as $item) {
                $list[] = [
                    'id' => $item['id'],
                    'proid' => $item['id'],
                    'name' => $item['name'],
                    'pic' => $item['cover_image'],
                    'sell_price' => $item['base_price'],
                    'market_price' => $item['base_price'],
                    'sales' => $item['use_count'] ?? 0,
                ];
            }
        }
        return json(['products' => $list]);
    }

    // =====================================================
    // 使用模板快速生成功能
    // =====================================================

    /**
     * 获取模板详情用于生成
     * GET: id 模板ID
     */
    public function use_template()
    {
        $templateId = input('param.id', 0, 'intval');
        
        if (!$templateId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        // 获取模板详情
        $template = $this->service->getTemplateDetail($templateId);
        if (!$template) {
            return json(['status' => 0, 'msg' => '模板不存在']);
        }
        
        // 检查模板状态
        if ($template['status'] != 1) {
            return json(['status' => 0, 'msg' => '模板已禁用']);
        }
        
        // 获取模型信息
        $model = $this->service->getModelDetail($template['model_id']);
        $modelName = $model ? $model['model_name'] : '未绑定模型';
        
        // 视频生成可能需要首帧图
        $requireImage = false;
        $maxImages = 1;
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        
        // 检查模型是否支持图生视频(i2v)
        if ($model) {
            $capTags = $model['capability_tags'] ?? [];
            if (is_string($capTags)) {
                $capTags = json_decode($capTags, true) ?: [];
            }
            if (in_array('image2video', $capTags) || in_array('图生视频', $capTags)) {
                $requireImage = true;
            }
        }
        
        $defaultPrompt = $template['prompt'] ?? ($defaultParams['prompt'] ?? '');
        
        $result = [
            'id' => $template['id'],
            'template_name' => $template['template_name'],
            'cover_image' => $template['cover_image'],
            'description' => $template['description'] ?? '',
            'model_id' => $template['model_id'],
            'model_name' => $modelName,
            'capability_type' => intval($template['capability_type'] ?? 0),
            'default_prompt' => $defaultPrompt,
            'output_quantity' => intval($template['output_quantity'] ?? 5),
            'require_image' => $requireImage,
            'max_images' => $maxImages,
            'base_price' => floatval($template['base_price'] ?? 0),
            'price_unit' => $template['price_unit'] ?? 'per_second'
        ];
        
        return json(['status' => 1, 'data' => $result]);
    }
    
    /**
     * 基于模板快速提交视频生成任务
     * POST: template_id, prompt, image(可选-首帧图), n(可选-时长)
     */
    public function quick_generate()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }
        
        $templateId = input('post.template_id', 0, 'intval');
        $prompt = input('post.prompt', '');
        $image = input('post.image', '');
        $duration = input('post.duration', 0, 'intval');
        
        if (!$templateId) {
            return json(['status' => 0, 'msg' => '请选择模板']);
        }
        
        // 验证提示词
        $prompt = trim($prompt);
        if (mb_strlen($prompt) < 2) {
            return json(['status' => 0, 'msg' => '请填写提示词（至少2个字符）']);
        }
        if (mb_strlen($prompt) > 2000) {
            return json(['status' => 0, 'msg' => '提示词不能超过2000个字符']);
        }
        
        // 获取模板详情
        $template = $this->service->getTemplateDetail($templateId);
        if (!$template) {
            return json(['status' => 0, 'msg' => '模板不存在']);
        }
        if ($template['status'] != 1) {
            return json(['status' => 0, 'msg' => '模板已禁用']);
        }
        
        // 构建输入参数
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        
        $inputParams = array_merge($defaultParams, [
            'prompt' => $prompt
        ]);
        
        // 添加首帧图
        if (!empty($image)) {
            $inputParams['first_frame_image'] = $image;
        }
        
        // 设置时长
        $outputQuantity = intval($template['output_quantity'] ?? 5);
        if ($duration > 0 && $duration <= $outputQuantity) {
            $inputParams['duration'] = $duration;
        } elseif ($outputQuantity > 0) {
            $inputParams['duration'] = $outputQuantity;
        }
        
        // 创建生成任务
        $result = $this->service->createTask([
            'aid' => aid,
            'bid' => bid,
            'uid' => uid,
            'generation_type' => $this->generationType,
            'model_id' => $template['model_id'],
            'scene_id' => $templateId,
            'capability_type' => intval($template['capability_type'] ?? 0),
            'input_params' => $inputParams
        ]);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('使用模板快速生成视频 模板ID:' . $templateId . ' 记录ID:' . $result['record_id'], 1);
        }
        
        return json($result);
    }
}

<?php
/**
 * 照片生成控制器
 * 管理照片生成任务、生成记录、场景模板
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\GenerationService;
use app\service\GenerationOrderService;
use app\service\MerchantApiKeyService;
use app\model\GenerationRecord;

class PhotoGeneration extends Common
{
    protected $service;
    protected $generationType = 1; // 照片生成

    public function initialize()
    {
        parent::initialize();
        $this->service = new GenerationService();
        
        // 商户用户访问时，检查API Key配置状态
        if ($this->bid > 0) {
            $apiKeyService = new MerchantApiKeyService();
            $checkResult = $apiKeyService->checkImageApiConfig($this->bid, $this->mdid);
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
        <p>' . htmlspecialchars($checkResult['msg']) . '<br>请先配置图像生成所需的API Key，配置完成后即可使用此功能。</p>
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
            $capabilityType = input('post.capability_type', 0);
            
            // 如果指定了能力类型，自动补充系统参数
            if ($capabilityType > 0) {
                $inputParams = $this->applyAutoParams($inputParams, $capabilityType, $modelId);
            }
            
            $result = $this->service->createTask([
                'aid' => aid,
                'bid' => bid,
                'uid' => uid,
                'generation_type' => $this->generationType,
                'model_id' => $modelId,
                'scene_id' => $sceneId,
                'capability_type' => $capabilityType,
                'input_params' => $inputParams
            ]);
            
            if ($result['status'] == 1) {
                \app\common\System::plog('创建照片生成任务 记录ID:' . $result['record_id'], 1);
            }
            
            return json($result);
        }
        
        // 获取可用模型列表
        $models = $this->service->getAvailableModels('image');
        
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
     * 根据能力类型自动补充系统参数
     */
    private function applyAutoParams($inputParams, $capabilityType, $modelId)
    {
        $model = $this->service->getModelDetail($modelId);
        if (!$model) {
            return $inputParams;
        }
        
        // 补充模型标识
        $inputParams['model'] = $model['model_code'];
        
        switch ($capabilityType) {
            case 1: // 文生图-单张
                $inputParams['sequential_image_generation'] = 'disabled';
                // 移除不需要的参数
                unset($inputParams['image'], $inputParams['images'], $inputParams['max_images']);
                break;
                
            case 2: // 文生图-组图
                $inputParams['sequential_image_generation'] = 'auto';
                $inputParams['stream'] = true;
                if (isset($inputParams['max_images'])) {
                    $inputParams['sequential_image_generation_options'] = [
                        'max_images' => intval($inputParams['max_images'])
                    ];
                    unset($inputParams['max_images']);
                }
                unset($inputParams['image'], $inputParams['images']);
                break;
                
            case 3: // 图生图-单入单出
                $inputParams['sequential_image_generation'] = 'disabled';
                unset($inputParams['images'], $inputParams['max_images']);
                break;
                
            case 4: // 图生图-单入多出
                $inputParams['sequential_image_generation'] = 'auto';
                $inputParams['stream'] = true;
                if (isset($inputParams['max_images'])) {
                    $inputParams['sequential_image_generation_options'] = [
                        'max_images' => intval($inputParams['max_images'])
                    ];
                    unset($inputParams['max_images']);
                }
                unset($inputParams['images']);
                break;
                
            case 5: // 多图入-单出
                $inputParams['sequential_image_generation'] = 'disabled';
                // 确保images是数组格式
                if (isset($inputParams['images'])) {
                    if (is_string($inputParams['images'])) {
                        $inputParams['image'] = array_filter(array_map('trim', explode(',', $inputParams['images'])));
                    } else {
                        $inputParams['image'] = array_values($inputParams['images']);
                    }
                    unset($inputParams['images']);
                }
                unset($inputParams['max_images']);
                break;
                
            case 6: // 多图入-多出
                $inputParams['sequential_image_generation'] = 'auto';
                $inputParams['stream'] = true;
                // 确保images是数组格式
                if (isset($inputParams['images'])) {
                    if (is_string($inputParams['images'])) {
                        $inputParams['image'] = array_filter(array_map('trim', explode(',', $inputParams['images'])));
                    } else {
                        $inputParams['image'] = array_values($inputParams['images']);
                    }
                    unset($inputParams['images']);
                }
                if (isset($inputParams['max_images'])) {
                    $inputParams['sequential_image_generation_options'] = [
                        'max_images' => intval($inputParams['max_images'])
                    ];
                    unset($inputParams['max_images']);
                }
                break;
        }
        
        // ratio + quality → size 转换（pill 选择器发送 ratio/quality，需转为模型 API 接受的 size）
        if (isset($inputParams['ratio']) && !empty($inputParams['ratio'])) {
            $ratio = $inputParams['ratio'];
            $quality = $inputParams['quality'] ?? 'hd';
            // 仅当用户未显式选择 size 时才自动生成
            if (!isset($inputParams['size']) || $inputParams['size'] === '') {
                $ratioSizeMap = [
                    '1:1'  => ['standard' => '512x512',  'hd' => '1024x1024', 'ultra' => '2048x2048'],
                    '2:3'  => ['standard' => '512x768',  'hd' => '1024x1536', 'ultra' => '2048x3072'],
                    '3:2'  => ['standard' => '768x512',  'hd' => '1536x1024', 'ultra' => '3072x2048'],
                    '3:4'  => ['standard' => '384x512',  'hd' => '768x1024',  'ultra' => '1536x2048'],
                    '4:3'  => ['standard' => '512x384',  'hd' => '1024x768',  'ultra' => '2048x1536'],
                    '9:16' => ['standard' => '360x640',  'hd' => '720x1280',  'ultra' => '1440x2560'],
                    '16:9' => ['standard' => '640x360',  'hd' => '1280x720',  'ultra' => '2560x1440'],
                    '4:5'  => ['standard' => '512x640',  'hd' => '1024x1280', 'ultra' => '2048x2560'],
                    '5:4'  => ['standard' => '640x512',  'hd' => '1280x1024', 'ultra' => '2560x2048'],
                    '21:9' => ['standard' => '1260x540', 'hd' => '2520x1080', 'ultra' => '3780x1620'],
                ];
                if (isset($ratioSizeMap[$ratio][$quality])) {
                    $inputParams['size'] = $ratioSizeMap[$ratio][$quality];
                } elseif (isset($ratioSizeMap[$ratio]['hd'])) {
                    $inputParams['size'] = $ratioSizeMap[$ratio]['hd'];
                }
            }
            unset($inputParams['ratio'], $inputParams['quality']);
        } else {
            unset($inputParams['ratio'], $inputParams['quality']);
        }
        
        // 根据目标模型的 ai_model_parameter 表白名单过滤，防止其他模型的参数泄漏到 API 请求
        // 注意：若模型在 ai_model_parameter 表中无记录（使用兜底逻辑），则跳过白名单过滤
        $validParamNames = Db::name('ai_model_parameter')
            ->where('model_id', $modelId)
            ->column('param_name');
        
        if (!empty($validParamNames)) {
            // 模型有参数定义，严格按白名单过滤
            $systemParams = ['model', 'sequential_image_generation', 'stream', 'sequential_image_generation_options'];
            $allowedParams = array_merge($validParamNames, $systemParams);
            $inputParams = array_intersect_key($inputParams, array_flip($allowedParams));
        }
        // 若模型无参数记录，则保留所有用户参数（兜底模式）
        
        // 过滤空值参数
        $inputParams = array_filter($inputParams, function($v) {
            return $v !== '' && $v !== null;
        });
        
        return $inputParams;
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
        $models = $this->service->getAvailableModels('image');
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
            \app\common\System::plog('重试照片生成任务 ID:' . $recordId, 1);
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
            \app\common\System::plog('取消照片生成任务 ID:' . $recordId, 1);
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
            \app\common\System::plog('删除照片生成记录 ID:' . $recordId, 1);
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
                \app\common\System::plog('照片生成记录转为模板 记录ID:' . $recordId . ' 模板ID:' . $result['template_id'], 1);
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
        if (!empty($record['input_params'])) {
            $inputParams = $record['input_params'];
            if (is_string($inputParams)) {
                $inputParams = json_decode($inputParams, true) ?: [];
            }
            $prompt = $inputParams['prompt'] ?? '';
        }
        
        View::assign('record', $record);
        View::assign('prompt', $prompt);  // 新增：传递提示词到视图
        
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
        $models = $this->service->getAvailableModels('image');
        
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
        // commissiondata1 - 按比例分销参数
        $commissiondata1 = input('post.commissiondata1/a', []);
        if (!empty($commissiondata1)) {
            $data['commissiondata1'] = json_encode($commissiondata1, JSON_UNESCAPED_UNICODE);
        } else {
            $data['commissiondata1'] = '';
        }
        
        // commissiondata2 - 按固定金额参数
        $commissiondata2 = input('post.commissiondata2/a', []);
        if (!empty($commissiondata2)) {
            $data['commissiondata2'] = json_encode($commissiondata2, JSON_UNESCAPED_UNICODE);
        } else {
            $data['commissiondata2'] = '';
        }
        
        // commissiondata3 - 分销送积分参数
        $commissiondata3 = input('post.commissiondata3/a', []);
        if (!empty($commissiondata3)) {
            $data['commissiondata3'] = json_encode($commissiondata3, JSON_UNESCAPED_UNICODE);
        } else {
            $data['commissiondata3'] = '';
        }
        
        // ========== 处理团队分红数据 ==========
        // teamfenhongdata1 - 团队分红比例参数
        $teamfenhongdata1 = input('post.teamfenhongdata1/a', []);
        if (!empty($teamfenhongdata1)) {
            $data['teamfenhongdata1'] = json_encode($teamfenhongdata1, JSON_UNESCAPED_UNICODE);
        } else {
            $data['teamfenhongdata1'] = '';
        }
        
        // teamfenhongdata2 - 团队分红金额参数
        $teamfenhongdata2 = input('post.teamfenhongdata2/a', []);
        if (!empty($teamfenhongdata2)) {
            $data['teamfenhongdata2'] = json_encode($teamfenhongdata2, JSON_UNESCAPED_UNICODE);
        } else {
            $data['teamfenhongdata2'] = '';
        }
        
        // ========== 处理股东分红数据 ==========
        // gdfenhongdata1 - 股东分红比例参数
        $gdfenhongdata1 = input('post.gdfenhongdata1/a', []);
        if (!empty($gdfenhongdata1)) {
            $data['gdfenhongdata1'] = json_encode($gdfenhongdata1, JSON_UNESCAPED_UNICODE);
        } else {
            $data['gdfenhongdata1'] = '';
        }
        
        // gdfenhongdata2 - 股东分红金额参数
        $gdfenhongdata2 = input('post.gdfenhongdata2/a', []);
        if (!empty($gdfenhongdata2)) {
            $data['gdfenhongdata2'] = json_encode($gdfenhongdata2, JSON_UNESCAPED_UNICODE);
        } else {
            $data['gdfenhongdata2'] = '';
        }
        
        // ========== 处理区域代理分红数据 ==========
        // areafenhongdata1 - 区域分红比例参数
        $areafenhongdata1 = input('post.areafenhongdata1/a', []);
        if (!empty($areafenhongdata1)) {
            $data['areafenhongdata1'] = json_encode($areafenhongdata1, JSON_UNESCAPED_UNICODE);
        } else {
            $data['areafenhongdata1'] = '';
        }
        
        // areafenhongdata2 - 区域分红金额参数
        $areafenhongdata2 = input('post.areafenhongdata2/a', []);
        if (!empty($areafenhongdata2)) {
            $data['areafenhongdata2'] = json_encode($areafenhongdata2, JSON_UNESCAPED_UNICODE);
        } else {
            $data['areafenhongdata2'] = '';
        }
        
        // ========== 处理显示/购买条件 ==========
        // showtj - 显示条件
        if (isset($data['showtj']) && is_array($data['showtj'])) {
            $data['showtj'] = implode(',', $data['showtj']);
        } elseif (!isset($data['showtj']) || $data['showtj'] === '') {
            $data['showtj'] = '-1';
        }
        
        // gettj - 购买条件
        if (isset($data['gettj']) && is_array($data['gettj'])) {
            $data['gettj'] = implode(',', $data['gettj']);
        } elseif (!isset($data['gettj']) || $data['gettj'] === '') {
            $data['gettj'] = '-1';
        }
        
        $result = $this->service->saveTemplate($data);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('保存照片场景模板:' . $data['template_name'], 1);
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
            \app\common\System::plog('删除照片场景模板 ID:' . $id, 1);
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
            \app\common\System::plog('更新照片场景模板状态 ID:' . $id . ' 状态:' . $status, 1);
        }
        
        return json($result);
    }
    
    /**
     * 批量迁移封面为WebP格式
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
        
        // 照片模板：批量压缩封面图为 WebP
        $templates = Db::name('generation_scene_template')
            ->where('generation_type', 1)
            ->where('status', 1)
            ->where('cover_image', '<>', '')
            ->where(function($query) {
                $query->where('cover_image', 'like', '%.jpg')
                    ->whereOr('cover_image', 'like', '%.jpeg')
                    ->whereOr('cover_image', 'like', '%.png');
            })
            ->limit($limit)
            ->field('id, cover_image, aid')
            ->select()
            ->toArray();
        
        $processed = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($templates as $tpl) {
            // 跳过视频URL
            if ($this->service->isVideoUrl($tpl['cover_image'])) {
                $skipped++;
                continue;
            }
            $processed++;
            try {
                $tplAid = $tpl['aid'] ?: (defined('aid') ? aid : 0);
                $transferService = new \app\service\AttachmentTransferService($tplAid);
                $compressedUrl = $this->service->compressCoverImagePublic($tpl['cover_image'], $transferService);
                if ($compressedUrl) {
                    Db::name('generation_scene_template')->where('id', $tpl['id'])->update([
                        'cover_image' => $compressedUrl,
                        'update_time' => time()
                    ]);
                    $success++;
                } else {
                    $skipped++; // 宽度未超阈或无需压缩
                }
            } catch (\Exception $e) {
                $failed++;
                \think\facade\Log::warning('batch_migrate_webp: 模板' . $tpl['id'] . '失败: ' . $e->getMessage());
            }
        }
        
        // 查询剩余待处理数量
        $remainJpgPng = Db::name('generation_scene_template')
            ->where('generation_type', 1)
            ->where('status', 1)
            ->where('cover_image', '<>', '')
            ->where(function($query) {
                $query->where('cover_image', 'like', '%.jpg')
                    ->whereOr('cover_image', 'like', '%.jpeg')
                    ->whereOr('cover_image', 'like', '%.png');
            })
            ->count();
        
        \app\common\System::plog('批量压缩照片封面WebP 成功:' . $success . ' 失败:' . $failed . ' 跳过:' . $skipped, 1);
        
        return json([
            'status' => 1,
            'msg' => '处理完成',
            'data' => [
                'processed' => $processed,
                'success' => $success,
                'failed' => $failed,
                'skipped' => $skipped,
                'remain_jpg_png' => $remainJpgPng
            ]
        ]);
    }
    
    /**
     * 获取封面迁移统计信息
     */
    public function cover_migrate_stats()
    {
        $totalJpgPng = Db::name('generation_scene_template')
            ->where('generation_type', 1)
            ->where('status', 1)
            ->where('cover_image', '<>', '')
            ->where(function($query) {
                $query->where('cover_image', 'like', '%.jpg')
                    ->whereOr('cover_image', 'like', '%.jpeg')
                    ->whereOr('cover_image', 'like', '%.png');
            })
            ->count();
        $totalWebp = Db::name('generation_scene_template')
            ->where('generation_type', 1)
            ->where('status', 1)
            ->where('cover_image', 'like', '%.webp')
            ->count();
        $totalEmpty = Db::name('generation_scene_template')
            ->where('generation_type', 1)
            ->where('status', 1)
            ->where('cover_image', '')
            ->count();
        
        return json([
            'status' => 1,
            'data' => [
                'jpg_png_count' => $totalJpgPng,
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
            \app\common\System::plog('重试照片场景模板转存 ID:' . $id, 1);
        }
        
        return json($result);
    }

    /**
     * 获取模型能力列表
     * 根据模型的 capability_tags 返回支持的能力类型
     */
    public function get_model_capabilities()
    {
        $modelId = input('param.model_id', 0);
        
        if (!$modelId) {
            return json(['status' => 0, 'msg' => '请选择模型']);
        }
        
        $model = $this->service->getModelDetail($modelId);
        if (!$model) {
            return json(['status' => 0, 'msg' => '模型不存在']);
        }
        
        // 解析能力标签
        $capabilityTags = $model['capability_tags'] ?? [];
        if (is_string($capabilityTags)) {
            $capabilityTags = json_decode($capabilityTags, true) ?: [];
        }
        
        // 能力类型定义
        $capabilityDefinitions = [
            1 => [
                'type' => 1,
                'name' => '文生图-生成单张图',
                'code' => 'text2image_single',
                'description' => '根据文本提示词生成单张图片',
                'input_requirements' => ['prompt'],
                'output_type' => 'single_image',
                'required_tags' => ['text2image', '文生图'],
                'exclude_tags' => []
            ],
            2 => [
                'type' => 2,
                'name' => '文生图-生成一组图',
                'code' => 'text2image_batch',
                'description' => '根据文本提示词生成多张图片（1-6张）',
                'input_requirements' => ['prompt', 'n'],
                'output_type' => 'multiple_images',
                'required_tags' => ['text2image', '文生图', 'batch_generation', '多图生成'],
                'exclude_tags' => []
            ],
            3 => [
                'type' => 3,
                'name' => '图生图-单张图生成单张图',
                'code' => 'image2image_single',
                'description' => '参考单张图片生成新图片',
                'input_requirements' => ['image', 'prompt'],
                'output_type' => 'single_image',
                'required_tags' => ['image2image', '图生图'],
                'exclude_tags' => []
            ],
            4 => [
                'type' => 4,
                'name' => '图生图-单张图生成一组图',
                'code' => 'image2image_batch',
                'description' => '参考单张图片生成多张新图片（1-10张）',
                'input_requirements' => ['image', 'prompt', 'n'],
                'output_type' => 'multiple_images',
                'required_tags' => ['image2image', '图生图', 'batch_generation', '多图生成'],
                'exclude_tags' => []
            ],
            5 => [
                'type' => 5,
                'name' => '图生图-多张参考图生成单张图',
                'code' => 'multi_image2image_single',
                'description' => '基于多张参考图（1-10张）生成单张图片',
                'input_requirements' => ['images', 'prompt'],
                'output_type' => 'single_image',
                'required_tags' => ['multi_input', '多图融合'],
                'exclude_tags' => []
            ],
            6 => [
                'type' => 6,
                'name' => '图生图-多张参考图生成一组图',
                'code' => 'multi_image2image_batch',
                'description' => '基于多张参考图（1-10张）生成多张图片（1-10张）',
                'input_requirements' => ['images', 'prompt', 'n'],
                'output_type' => 'multiple_images',
                'required_tags' => ['multi_input', '多图融合', 'batch_generation', '多图生成'],
                'exclude_tags' => []
            ]
        ];
        
        // 根据模型能力标签匹配可用能力
        $capabilities = [];
        $hasBatchGeneration = $this->hasAnyTag($capabilityTags, ['batch_generation', '多图生成', '流式输出']);
        $hasText2Image = $this->hasAnyTag($capabilityTags, ['text2image', '文生图']);
        $hasImage2Image = $this->hasAnyTag($capabilityTags, ['image2image', '图生图']);
        $hasMultiInput = $this->hasAnyTag($capabilityTags, ['multi_input', '多图融合', '多图输入']);
        
        // 能力1：文生图-单张
        if ($hasText2Image) {
            $capabilities[] = $capabilityDefinitions[1];
        }
        // 能力2：文生图-组图（需要batch_generation）
        if ($hasText2Image && $hasBatchGeneration) {
            $capabilities[] = $capabilityDefinitions[2];
        }
        // 能力3：图生图-单入单出
        if ($hasImage2Image) {
            $capabilities[] = $capabilityDefinitions[3];
        }
        // 能力4：图生图-单入多出（需要batch_generation）
        if ($hasImage2Image && $hasBatchGeneration) {
            $capabilities[] = $capabilityDefinitions[4];
        }
        // 能力5：多图入-单出
        if ($hasMultiInput) {
            $capabilities[] = $capabilityDefinitions[5];
        }
        // 能力6：多图入-多出（需要batch_generation）
        if ($hasMultiInput && $hasBatchGeneration) {
            $capabilities[] = $capabilityDefinitions[6];
        }
        
        return json([
            'status' => 1,
            'data' => [
                'model_id' => $model['id'],
                'model_name' => $model['model_name'],
                'model_code' => $model['model_code'],
                'capability_tags' => $capabilityTags,
                'capabilities' => $capabilities
            ]
        ]);
    }
    
    /**
     * 检查是否包含任意一个标签
     */
    private function hasAnyTag($tags, $checkTags)
    {
        foreach ($checkTags as $tag) {
            if (in_array($tag, $tags)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取能力表单结构
     * 根据能力类型返回动态表单结构和默认示例
     * 从 ai_model_parameter 表动态读取参数定义，按 required/optional 分区返回
     */
    public function get_capability_form_schema()
    {
        $modelId = input('param.model_id', 0);
        $capabilityType = input('param.capability_type', 0);
        
        if (!$modelId || !$capabilityType) {
            return json(['status' => 0, 'msg' => '参数不完整']);
        }
        
        $model = $this->service->getModelDetail($modelId);
        if (!$model) {
            return json(['status' => 0, 'msg' => '模型不存在']);
        }
        
        // 从 ai_model_parameter 表动态构建表单字段（分区）
        $fieldGroups = $this->buildCapabilityFormFields($capabilityType, $model);
        
        // 获取系统自动设置的参数
        $autoParams = $this->getAutoParams($capabilityType, $model);
        
        // 获取默认示例
        $defaultExample = $this->getCapabilityExample($modelId, $capabilityType);
        
        // 获取场景模板列表（不按 model_id 过滤，与场景模板管理页一致）
        $templateWhere = [
            ['aid', '=', aid],
            ['generation_type', '=', $this->generationType],
            ['status', '=', 1],
            ['template_name', '<>', ''],
        ];
        if (bid > 0) {
            $templateWhere[] = ['bid', '=', bid];
        }
        $templates = Db::name('generation_scene_template')
            ->where($templateWhere)
            ->field('id, template_name, cover_image, use_count, output_quantity, default_params, description, model_id')
            ->order('sort asc, use_count desc, id desc')
            ->select()->toArray();
        
        // 从 default_params 中提取 prompt 字段供前端使用
        foreach ($templates as &$tpl) {
            $tpl['prompt'] = '';
            if (!empty($tpl['default_params'])) {
                $params = is_string($tpl['default_params']) ? json_decode($tpl['default_params'], true) : $tpl['default_params'];
                if (is_array($params) && isset($params['prompt'])) {
                    $tpl['prompt'] = $params['prompt'];
                }
            }
        }
        unset($tpl);
        
        return json([
            'status' => 1,
            'data' => [
                'model_name' => $model['model_name'],
                'capability_type' => intval($capabilityType),
                'required_fields' => $fieldGroups['required'],
                'optional_fields' => $fieldGroups['optional'],
                'auto_params' => $autoParams,
                'default_example' => $defaultExample,
                'templates' => $templates
            ]
        ]);
    }
    
    /**
     * 构建能力表单字段（动态版）
     * 从 ai_model_parameter 表读取参数定义，按能力类型过滤，分区返回
     * 若模型无参数记录，则退回硬编码兜底逻辑
     *
     * @param int $capabilityType 能力类型 1-6
     * @param array $model 模型详情
     * @return array ['required' => [...], 'optional' => [...]]
     */
    private function buildCapabilityFormFields($capabilityType, $model)
    {
        $modelId = $model['id'];
        
        // 从数据库查询模型参数定义
        $dbParams = Db::name('ai_model_parameter')
            ->where('model_id', $modelId)
            ->order('sort ASC, id ASC')
            ->select()->toArray();
        
        // 若无参数记录，退回硬编码兜底
        if (empty($dbParams)) {
            return $this->buildFallbackFormFields($capabilityType);
        }
        
        // 系统自动参数（前端不渲染）
        $autoParamNames = ['model', 'sequential_image_generation', 'stream', 'sequential_image_generation_options'];
        
        // 按能力类型需要排除的参数
        $excludeByCapability = [];
        switch (intval($capabilityType)) {
            case 1: // 文生图-单张
            case 2: // 文生图-组图
                $excludeByCapability = ['image', 'images'];
                break;
            case 3: // 图生图-单入单出
            case 4: // 图生图-单入多出
                $excludeByCapability = ['images'];
                break;
            case 5: // 多图入-单出
            case 6: // 多图入-多出
                // 保留 images，排除单个 image 字段（仅当有独立 image 和 images 参数时）
                // 如果模型中 image 参数就是 array 类型，则保留作为 images 使用
                break;
        }
        
        // max_images 由独立 pill_selector 渲染，从通用字段中排除
        $excludeFromForm = array_merge($autoParamNames, ['max_images', 'n']);
        
        $requiredFields = [];
        $optionalFields = [];
        
        foreach ($dbParams as $param) {
            $paramName = $param['param_name'];
            
            // 跳过系统自动参数
            if (in_array($paramName, $excludeFromForm)) {
                continue;
            }
            
            // 跳过按能力类型排除的参数
            if (in_array($paramName, $excludeByCapability)) {
                continue;
            }
            
            // 特殊处理：能力5/6需要多图输入，如果 image 是 array 类型则作为 images 渲染
            if (in_array(intval($capabilityType), [5, 6]) && $paramName === 'image' && $param['param_type'] === 'array') {
                // 将 array+url 的 image 参数渲染为多图上传
                $field = $this->mapParamToField($param, $capabilityType);
                $field['name'] = 'images'; // 表单字段名用 images
            } else if (in_array(intval($capabilityType), [5, 6]) && $paramName === 'image' && $param['param_type'] !== 'array') {
                // 单图 image 参数在多图输入能力中不显示
                continue;
            } else {
                $field = $this->mapParamToField($param, $capabilityType);
            }
            
            if ($param['is_required']) {
                $requiredFields[] = $field;
            } else {
                $optionalFields[] = $field;
            }
        }
        
        // 能力5/6（多张参考图输入）：将所有 upload_single 图片类字段转为 upload_multi，统一表单名为 images
        if (in_array(intval($capabilityType), [5, 6])) {
            $hasMultiUpload = false;
            // 检查是否已有 upload_multi 字段
            foreach (array_merge($requiredFields, $optionalFields) as $f) {
                if ($f['type'] === 'upload_multi') { $hasMultiUpload = true; break; }
            }
            if (!$hasMultiUpload) {
                // 将第一个 upload_single 转为 upload_multi
                $converted = false;
                foreach ($requiredFields as &$rf) {
                    if ($rf['type'] === 'upload_single' && !$converted) {
                        $rf['type'] = 'upload_multi';
                        $rf['name'] = 'images';
                        $rf['label'] = '参考图像（支持1-10张）';
                        $rf['max_count'] = 10;
                        $converted = true;
                    }
                }
                unset($rf);
                if (!$converted) {
                    foreach ($optionalFields as &$of) {
                        if ($of['type'] === 'upload_single' && !$converted) {
                            $of['type'] = 'upload_multi';
                            $of['name'] = 'images';
                            $of['label'] = '参考图像（支持1-10张）';
                            $of['max_count'] = 10;
                            $of['required'] = true;
                            // 移到 required
                            $requiredFields[] = $of;
                            $of = null; // 标记移除
                            $converted = true;
                        }
                    }
                    unset($of);
                    $optionalFields = array_values(array_filter($optionalFields));
                }
                // 若仍无上传字段，兜底添加一个
                if (!$converted) {
                    $requiredFields[] = [
                        'name' => 'images',
                        'label' => '参考图像（支持1-10张）',
                        'type' => 'upload_multi',
                        'required' => true,
                        'default' => [],
                        'max_count' => 10,
                        'placeholder' => '上传多张参考图片',
                        'accept' => 'image/*'
                    ];
                }
            }
        }
        
        // 确保 prompt 始终作为必填参数第一项
        $promptIndex = -1;
        foreach ($requiredFields as $i => $f) {
            if ($f['name'] === 'prompt') {
                $promptIndex = $i;
                break;
            }
        }
        if ($promptIndex > 0) {
            $promptField = array_splice($requiredFields, $promptIndex, 1)[0];
            array_unshift($requiredFields, $promptField);
        } elseif ($promptIndex < 0) {
            // prompt 不在必填列表中，也检查可选列表
            foreach ($optionalFields as $i => $f) {
                if ($f['name'] === 'prompt') {
                    $promptField = array_splice($optionalFields, $i, 1)[0];
                    $promptField['required'] = true;
                    array_unshift($requiredFields, $promptField);
                    break;
                }
            }
        }
        
        return [
            'required' => $requiredFields,
            'optional' => $optionalFields
        ];
    }
    
    /**
     * 将数据库参数定义映射为前端表单字段
     *
     * @param array $param ai_model_parameter 表记录
     * @param int $capabilityType 能力类型
     * @return array 前端字段定义
     */
    private function mapParamToField($param, $capabilityType)
    {
        $paramType = $param['param_type'];
        $dataFormat = $param['data_format'] ?? '';
        $paramName = $param['param_name'];
        $description = $param['description'] ?? '';
        
        // 解析 enum_options
        $enumOptions = null;
        if (!empty($param['enum_options'])) {
            $enumOptions = is_string($param['enum_options']) 
                ? json_decode($param['enum_options'], true) 
                : $param['enum_options'];
        }
        
        // 解析 value_range
        $valueRange = null;
        if (!empty($param['value_range'])) {
            $valueRange = is_string($param['value_range']) 
                ? json_decode($param['value_range'], true) 
                : $param['value_range'];
        }
        
        // 解析默认值
        $defaultValue = $param['default_value'] ?? '';
        if ($defaultValue !== '' && $defaultValue !== null) {
            // 尝试 JSON 解码（去除可能的引号包装）
            $decoded = json_decode($defaultValue, true);
            if ($decoded !== null || $defaultValue === 'null') {
                $defaultValue = $decoded;
            }
        }
        
        // 确定渲染控件类型
        $fieldType = $this->resolveFieldType($paramType, $dataFormat, $paramName, $description, $enumOptions);
        
        // 构建字段定义
        $field = [
            'name' => $paramName,
            'label' => $param['param_label'] ?: $paramName,
            'type' => $fieldType,
            'required' => boolval($param['is_required']),
            'default' => $defaultValue,
            'placeholder' => $description ?: ('请输入' . ($param['param_label'] ?: $paramName)),
        ];
        
        // 添加枚举选项
        if ($fieldType === 'select' && $enumOptions) {
            $field['options'] = array_map(function($opt) {
                if (is_array($opt)) return $opt;
                return ['value' => $opt, 'label' => $this->formatOptionLabel($opt)];
            }, $enumOptions);
        }
        
        // 添加值范围
        if ($valueRange && in_array($fieldType, ['number'])) {
            $field['range'] = $valueRange;
        }
        
        // 特殊字段处理
        if ($paramName === 'prompt') {
            $field['type'] = 'textarea';
            $field['height'] = 120;
            if (empty($field['placeholder']) || $field['placeholder'] === '请输入prompt') {
                $field['placeholder'] = '请输入图像描述，如：一只可爱的橘猫坐在阳光下的窗台上';
            }
        }
        
        // 上传类字段
        if ($fieldType === 'upload_single') {
            $field['accept'] = 'image/*';
        }
        if ($fieldType === 'upload_multi') {
            $field['accept'] = 'image/*';
            $field['max_count'] = $valueRange['max'] ?? 10;
        }
        
        // switch 类型
        if ($fieldType === 'switch') {
            $field['default'] = $defaultValue === true || $defaultValue === 'true' || $defaultValue === 1;
        }
        
        return $field;
    }
    
    /**
     * 根据参数类型和数据格式确定渲染控件类型
     */
    private function resolveFieldType($paramType, $dataFormat, $paramName, $description, $enumOptions)
    {
        switch ($paramType) {
            case 'string':
                if ($dataFormat === 'enum' && $enumOptions) {
                    return 'select';
                }
                if ($dataFormat === 'url' || $dataFormat === 'url_or_base64' || $dataFormat === 'base64') {
                    return 'upload_single';
                }
                // 判断是否为长文本
                if ($dataFormat === 'text') {
                    if (mb_strpos($description, '描述') !== false || mb_strpos($description, '提示词') !== false 
                        || $paramName === 'prompt' || $paramName === 'negative_prompt') {
                        return 'textarea';
                    }
                    return 'text';
                }
                return 'text';
                
            case 'integer':
                return 'number';
                
            case 'float':
                return 'number';
                
            case 'boolean':
                return 'switch';
                
            case 'file':
                return 'upload_single';
                
            case 'array':
                if ($dataFormat === 'url') {
                    return 'upload_multi';
                }
                return 'tag_input';
                
            case 'object':
                return 'text'; // JSON 文本输入
                
            default:
                return 'text';
        }
    }
    
    /**
     * 格式化枚举选项标签
     */
    private function formatOptionLabel($value)
    {
        // 常见选项的友好标签映射
        $labelMap = [
            'url' => 'URL链接',
            'b64_json' => 'Base64编码',
            'disabled' => '禁用',
            'enabled' => '启用',
            'auto' => '自动',
            'true' => '是',
            'false' => '否',
            'png' => 'PNG',
            'jpg' => 'JPG',
            'webp' => 'WebP',
            '1K' => '1K (1024x1024)',
            '2K' => '2K (2048x2048)',
        ];
        
        $strVal = is_bool($value) ? ($value ? 'true' : 'false') : strval($value);
        return $labelMap[$strVal] ?? $strVal;
    }
    
    /**
     * 硬编码兜底表单字段（模型无 ai_model_parameter 记录时使用）
     */
    private function buildFallbackFormFields($capabilityType)
    {
        $required = [];
        $optional = [];
        
        // 提示词（必填）
        $required[] = [
            'name' => 'prompt',
            'label' => '提示词',
            'type' => 'textarea',
            'required' => true,
            'default' => '',
            'placeholder' => '请输入图像描述，如：一只可爱的橘猫坐在阳光下的窗台上',
            'height' => 120
        ];
        
        // 根据能力类型添加 image/images
        switch (intval($capabilityType)) {
            case 3: case 4:
                $required[] = [
                    'name' => 'image',
                    'label' => '参考图像',
                    'type' => 'upload_single',
                    'required' => true,
                    'default' => '',
                    'placeholder' => '上传一张参考图片',
                    'accept' => 'image/*'
                ];
                break;
            case 5: case 6:
                $required[] = [
                    'name' => 'images',
                    'label' => '参考图像（支持1-10张）',
                    'type' => 'upload_multi',
                    'required' => true,
                    'default' => [],
                    'max_count' => 10,
                    'placeholder' => '上传多张参考图片',
                    'accept' => 'image/*'
                ];
                break;
        }
        
        // 通用可选字段
        $optional[] = [
            'name' => 'size',
            'label' => '输出尺寸',
            'type' => 'select',
            'required' => false,
            'default' => '2K',
            'options' => [
                ['value' => '1K', 'label' => '1K (1024x1024)'],
                ['value' => '2K', 'label' => '2K (2048x2048)']
            ]
        ];
        $optional[] = [
            'name' => 'response_format',
            'label' => '响应格式',
            'type' => 'select',
            'required' => false,
            'default' => 'url',
            'options' => [
                ['value' => 'url', 'label' => 'URL链接'],
                ['value' => 'b64_json', 'label' => 'Base64编码']
            ]
        ];
        $optional[] = [
            'name' => 'watermark',
            'label' => '水印',
            'type' => 'switch',
            'required' => false,
            'default' => false
        ];
        
        return ['required' => $required, 'optional' => $optional];
    }
    
    /**
     * 获取系统自动设置的参数
     */
    private function getAutoParams($capabilityType, $model)
    {
        $autoParams = [
            'model' => $model['model_code']
        ];
        
        switch ($capabilityType) {
            case 1: // 文生图-单张
            case 3: // 图生图-单入单出
            case 5: // 多图入-单出
                $autoParams['sequential_image_generation'] = 'disabled';
                break;
                
            case 2: // 文生图-组图
            case 4: // 图生图-单入多出
            case 6: // 多图入-多出
                $autoParams['sequential_image_generation'] = 'auto';
                $autoParams['stream'] = true;
                break;
        }
        
        return $autoParams;
    }
    
    /**
     * 获取能力调用示例
     */
    private function getCapabilityExample($modelId, $capabilityType)
    {
        // 尝试从数据库获取示例
        $example = Db::name('model_capability_example')
            ->where('model_id', $modelId)
            ->where('capability_type', $capabilityType)
            ->where('status', 1)
            ->order('is_default desc, sort asc')
            ->find();
        
        if ($example) {
            return [
                'name' => $example['example_name'],
                'description' => $example['description'],
                'params' => is_string($example['request_params']) 
                    ? json_decode($example['request_params'], true) 
                    : $example['request_params'],
                'notes' => $example['notes']
            ];
        }
        
        // 返回默认示例
        return $this->getDefaultExample($capabilityType);
    }
    
    /**
     * 获取默认示例参数
     */
    private function getDefaultExample($capabilityType)
    {
        $examples = [
            1 => [
                'name' => '橘猫图片生成',
                'description' => '生成一张可爱橘猫的图片',
                'params' => [
                    'prompt' => '一只可爱的橘猫坐在阳光下的窗台上，毛发蓬松，眼睛明亮，高清摄影风格，4K画质',
                    'size' => '2K',
                    'watermark' => false
                ],
                'notes' => '提示词越详细，生成效果越好'
            ],
            2 => [
                'name' => '橘猫系列图生成',
                'description' => '生成一组可爱橘猫的图片',
                'params' => [
                    'prompt' => '一只可爱的橘猫，不同姿态和表情，毛发蓬松，高清摄影风格',
                    'max_images' => 4,
                    'size' => '2K'
                ],
                'notes' => '组图生成会产生多张风格相近但各有特色的图片'
            ],
            3 => [
                'name' => '照片风格转换',
                'description' => '将照片转换为油画风格',
                'params' => [
                    'image' => '',
                    'prompt' => '将照片转换为油画风格，保持人物主体特征，增添艺术质感',
                    'size' => '2K'
                ],
                'notes' => '请上传清晰的参考图片以获得更好效果'
            ],
            4 => [
                'name' => '照片多风格生成',
                'description' => '基于一张照片生成多种风格',
                'params' => [
                    'image' => '',
                    'prompt' => '基于参考照片，生成多种艺术风格变化，包括油画、水彩、素描等',
                    'max_images' => 4,
                    'size' => '2K'
                ],
                'notes' => '每张生成图都会有不同的艺术风格'
            ],
            5 => [
                'name' => '多图融合创作',
                'description' => '融合多张参考图生成新图',
                'params' => [
                    'images' => [],
                    'prompt' => '融合多张参考图的元素，创作全新的艺术作品',
                    'size' => '2K'
                ],
                'notes' => '建议上传风格相近的参考图以获得更协调的效果'
            ],
            6 => [
                'name' => '多图融合系列创作',
                'description' => '基于多图融合生成系列作品',
                'params' => [
                    'images' => [],
                    'prompt' => '融合多张参考图的元素，创作系列艺术作品，保持风格统一但各有特色',
                    'max_images' => 4,
                    'size' => '2K'
                ],
                'notes' => '适合批量创作系列主题作品'
            ]
        ];
        
        return $examples[$capabilityType] ?? null;
    }
    
    /**
     * 获取历史成片记录（用于视频生成首帧选择）
     * GET /PhotoGeneration/get_output_images
     * @param int page 页码，默认1
     * @param int limit 每页数量，默认20
     * @param int status 状态筛选，默认2（成功）
     * @return json
     */
    public function get_output_images()
    {
        $page = input('param.page', 1, 'intval');
        $limit = input('param.limit', 20, 'intval');
        $status = input('param.status', 2, 'intval'); // 默认只获取成功的记录
        
        // 限制每页数量
        $limit = min($limit, 50);
        
        // 构建查询条件
        $where = [
            ['r.aid', '=', aid],
            ['r.bid', '=', bid],
            ['r.generation_type', '=', $this->generationType], // 照片生成
            ['r.status', '=', $status]
        ];
        
        // 查询总数
        $count = Db::name('generation_record')
            ->alias('r')
            ->where($where)
            ->count();
        
        // 查询记录列表
        $records = Db::name('generation_record')
            ->alias('r')
            ->leftJoin('model_info m', 'r.model_id = m.id')
            ->field('r.id, r.create_time, r.model_id, m.model_name')
            ->where($where)
            ->order('r.id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        
        // 获取每条记录的输出图片
        $data = [];
        foreach ($records as $record) {
            $outputs = Db::name('generation_output')
                ->where('record_id', $record['id'])
                ->where('output_type', 'image')
                ->field('id, output_url, thumbnail_url, width, height')
                ->select()
                ->toArray();
            
            // 只保留有输出图片的记录
            if (!empty($outputs)) {
                $outputList = [];
                foreach ($outputs as $output) {
                    $outputList[] = [
                        'id' => $output['id'],
                        'url' => $output['output_url'],
                        'thumbnail' => $output['thumbnail_url'] ?: $output['output_url'],
                        'width' => $output['width'] ?? 0,
                        'height' => $output['height'] ?? 0
                    ];
                }
                
                $data[] = [
                    'id' => $record['id'],
                    'create_time' => $record['create_time'] ? date('Y-m-d H:i', $record['create_time']) : '',
                    'model_name' => $record['model_name'] ?: '未知模型',
                    'output_count' => count($outputList),
                    'outputs' => $outputList
                ];
            }
        }
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'count' => $count,
            'data' => $data
        ]);
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
    
    /**
     * 转存到云空间
     */
    public function save_to_cloud()
    {
        $recordId = input('post.record_id', 0);
        
        if (!$recordId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        // 这里实现转存到云空间的逻辑
        // 实际项目中可能需要调用云存储服务API
        
        // 模拟成功响应
        return json(['status' => 1, 'msg' => '转存成功']);
    }
    
    /**
     * 获取下载链接
     */
    public function get_download_url()
    {
        $recordId = input('param.record_id', 0);
        
        if (!$recordId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        // 这里实现获取下载链接的逻辑
        // 实际项目中可能需要生成临时下载链接
        
        // 模拟下载链接
        $downloadUrl = url('PhotoGeneration/download_result', ['record_id' => $recordId])->build();
        
        return json([
            'status' => 1,
            'data' => [
                'download_url' => $downloadUrl
            ]
        ]);
    }
    
    /**
     * 下载结果
     */
    public function download_result()
    {
        $recordId = input('param.record_id', 0);
        
        if (!$recordId) {
            return $this->error('参数错误');
        }
        
        // 这里实现下载逻辑
        // 实际项目中可能需要打包生成的文件并提供下载
        
        // 模拟下载响应
        return $this->error('下载功能开发中');
    }
    
    /**
     * 分享为模板
     */
    public function share_as_template()
    {
        $recordId = input('post.record_id', 0);
        $templateName = input('post.template_name', '');
        
        if (!$recordId || !$templateName) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        // 获取记录详情
        $record = $this->service->getRecordDetail($recordId);
        if (!$record) {
            return json(['status' => 0, 'msg' => '记录不存在']);
        }
        
        // 构建模板数据
        $templateData = [
            'template_name' => $templateName,
            'model_id' => $record['model_id'],
            'default_params' => $record['input_params'],
            'output_quantity' => count($record['outputs'] ?? []),
            'status' => 1,
            'aid' => aid,
            'bid' => bid,
            'generation_type' => $this->generationType
        ];
        
        // 保存模板
        $templateId = Db::name('generation_scene_template')->insertGetId($templateData);
        
        if ($templateId) {
            \app\common\System::plog('分享照片生成结果为模板 记录ID:' . $recordId . ' 模板ID:' . $templateId, 1);
            return json(['status' => 1, 'msg' => '分享成功', 'template_id' => $templateId]);
        } else {
            return json(['status' => 0, 'msg' => '分享失败']);
        }
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
            // 关键词搜索（订单号/用户昵称）
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
            return View::fetch('index3/photo_order');
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
            \app\common\System::plog('照片生成订单' . $action . ' 订单ID:' . $orderId, 1);
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
        return View::fetch('photo_generation/choosetemplate');
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
        
        // 判断是否需要上传参考图（图生图模式）
        $requireImage = false;
        $maxImages = 1;
        $capabilityType = intval($template['capability_type'] ?? 0);
        if (in_array($capabilityType, [3, 4])) {
            // 单图输入
            $requireImage = true;
            $maxImages = 1;
        } elseif (in_array($capabilityType, [5, 6])) {
            // 多图输入
            $requireImage = true;
            $maxImages = 10;
        }
        
        // 获取默认提示词
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        $defaultPrompt = $template['prompt'] ?? ($defaultParams['prompt'] ?? '');
        
        $result = [
            'id' => $template['id'],
            'template_name' => $template['template_name'],
            'cover_image' => $template['cover_image'],
            'description' => $template['description'] ?? '',
            'model_id' => $template['model_id'],
            'model_name' => $modelName,
            'capability_type' => $capabilityType,
            'default_prompt' => $defaultPrompt,
            'output_quantity' => intval($template['output_quantity'] ?? 1),
            'require_image' => $requireImage,
            'max_images' => $maxImages,
            'base_price' => floatval($template['base_price'] ?? 0),
            'price_unit' => $template['price_unit'] ?? 'per_image'
        ];
        
        return json(['status' => 1, 'data' => $result]);
    }
    
    /**
     * 基于模板快速提交生成任务
     * POST: template_id, prompt, image(可选), images(可选), n(可选)
     */
    public function quick_generate()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }
        
        $templateId = input('post.template_id', 0, 'intval');
        $prompt = input('post.prompt', '');
        $image = input('post.image', '');
        $images = input('post.images/a', []);
        $n = input('post.n', 0, 'intval');
        
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
        
        // 判断能力类型，验证参考图
        $capabilityType = intval($template['capability_type'] ?? 0);
        if (in_array($capabilityType, [3, 4]) && empty($image)) {
            return json(['status' => 0, 'msg' => '请上传参考图片']);
        }
        if (in_array($capabilityType, [5, 6]) && empty($images)) {
            return json(['status' => 0, 'msg' => '请上传参考图片']);
        }
        
        // 构建输入参数
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        
        $inputParams = array_merge($defaultParams, [
            'prompt' => $prompt
        ]);
        
        // 添加参考图
        if (!empty($image)) {
            $inputParams['image'] = $image;
        }
        if (!empty($images)) {
            $inputParams['images'] = $images;
        }
        
        // 设置生成数量
        $outputQuantity = intval($template['output_quantity'] ?? 1);
        if ($n > 0 && $n <= $outputQuantity) {
            $inputParams['max_images'] = $n;
        } elseif ($outputQuantity > 1) {
            $inputParams['max_images'] = $outputQuantity;
        }
        
        // 根据能力类型自动补充系统参数
        if ($capabilityType > 0) {
            $inputParams = $this->applyAutoParams($inputParams, $capabilityType, $template['model_id']);
        }
        
        // 创建生成任务
        $result = $this->service->createTask([
            'aid' => aid,
            'bid' => bid,
            'uid' => uid,
            'generation_type' => $this->generationType,
            'model_id' => $template['model_id'],
            'scene_id' => $templateId,
            'capability_type' => $capabilityType,
            'input_params' => $inputParams
        ]);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('使用模板快速生成照片 模板ID:' . $templateId . ' 记录ID:' . $result['record_id'], 1);
        }
        
        return json($result);
    }
}

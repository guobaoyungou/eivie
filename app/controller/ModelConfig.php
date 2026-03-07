<?php

namespace app\controller;

use app\BaseController;
use app\service\AiModelConfigService;
use app\model\AiModelInstance;
use app\model\AiModelParameter;
use app\model\AiModelResponse;
use app\model\AiModelPricing;
use app\model\AiModelCategory;
use think\facade\View;
use think\facade\Db;

/**
 * 模型配置管理控制器
 * Class ModelConfig
 * @package app\controller
 */
class ModelConfig extends BaseController
{
    protected $aid;
    
    public function initialize()
    {
        parent::initialize();
        $this->aid = input('param.aid/d', 0);
        define('aid', $this->aid);
    }
    
    /**
     * 模型列表页
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 15);
            
            $filters = [
                'model_code' => input('param.model_code', ''),
                'model_name' => input('param.model_name', ''),
                'category_code' => input('param.category_code', ''),
                'provider' => input('param.provider', ''),
                'is_active' => input('param.is_active', ''),
                'aid' => $this->aid
            ];
            
            $result = AiModelConfigService::getModelList($filters, $page, $limit);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'count' => $result['total'],
                'data' => $result['data']
            ]);
        }
        
        // 获取模型分类列表
        $categories = AiModelCategory::where('level', 1)->select();
        View::assign('categories', $categories);
        
        // 获取服务提供商列表
        $providers = AiModelInstance::getProviderList();
        View::assign('providers', $providers);
        
        return View::fetch('ai_travel_photo/model_config_list');
    }
    
    /**
     * 新增/编辑模型
     */
    public function edit()
    {
        $id = input('param.id/d', 0);
        
        if (request()->isPost()) {
            $data = input('post.');
            $data['aid'] = $this->aid;
            
            $result = AiModelConfigService::saveModel($data);
            
            return json($result);
        }
        
        if ($id > 0) {
            $model = AiModelConfigService::getModelById($id);
            if (!$model) {
                return $this->error('模型不存在');
            }
            View::assign('model', $model);
        }
        
        // 获取模型分类列表
        $categories = AiModelCategory::where('level', 1)->select();
        View::assign('categories', $categories);
        
        // 获取服务提供商列表
        $providers = AiModelInstance::getProviderList();
        View::assign('providers', $providers);
        
        // 获取计费模式列表
        $billingModes = AiModelInstance::getBillingModeList();
        View::assign('billingModes', $billingModes);
        
        // 获取成本计量单位列表
        $costUnits = AiModelInstance::getCostUnitList();
        View::assign('costUnits', $costUnits);
        
        return View::fetch('ai_travel_photo/model_config_edit');
    }
    
    /**
     * 删除模型
     */
    public function delete()
    {
        $id = input('param.id/d', 0);
        
        $result = AiModelConfigService::deleteModel($id, $this->aid);
        
        return json($result);
    }
    
    /**
     * 切换激活状态
     */
    public function toggle_active()
    {
        $id = input('param.id/d', 0);
        $isActive = input('param.is_active/d', 0);
        
        $model = AiModelInstance::find($id);
        if (!$model) {
            return json(['success' => false, 'message' => '模型不存在']);
        }
        
        $model->is_active = $isActive;
        $model->save();
        
        return json(['success' => true, 'message' => '操作成功']);
    }
    
    /**
     * 参数管理页
     */
    public function parameters()
    {
        $modelId = input('param.model_id/d', 0);
        
        if (request()->isAjax()) {
            $params = AiModelConfigService::getModelParameters($modelId);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'count' => count($params),
                'data' => $params
            ]);
        }
        
        $model = AiModelInstance::find($modelId);
        if (!$model) {
            return $this->error('模型不存在');
        }
        
        View::assign('model', $model);
        
        // 获取参数类型列表
        $paramTypes = AiModelParameter::getParamTypeList();
        View::assign('paramTypes', $paramTypes);
        
        // 获取数据格式列表
        $dataFormats = AiModelParameter::getDataFormatList();
        View::assign('dataFormats', $dataFormats);
        
        return View::fetch('ai_travel_photo/model_config_parameters');
    }
    
    /**
     * 保存参数
     */
    public function save_parameter()
    {
        $data = input('post.');
        
        $result = AiModelConfigService::saveParameter($data);
        
        return json($result);
    }
    
    /**
     * 删除参数
     */
    public function delete_parameter()
    {
        $id = input('param.id/d', 0);
        
        $result = AiModelConfigService::deleteParameter($id);
        
        return json($result);
    }
    
    /**
     * 响应定义管理页
     */
    public function responses()
    {
        $modelId = input('param.model_id/d', 0);
        
        if (request()->isAjax()) {
            $responses = AiModelConfigService::getModelResponses($modelId);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'count' => count($responses),
                'data' => $responses
            ]);
        }
        
        $model = AiModelInstance::find($modelId);
        if (!$model) {
            return $this->error('模型不存在');
        }
        
        View::assign('model', $model);
        
        // 获取字段类型列表
        $fieldTypes = AiModelResponse::getFieldTypeList();
        View::assign('fieldTypes', $fieldTypes);
        
        return View::fetch('ai_travel_photo/model_config_responses');
    }
    
    /**
     * 保存响应定义
     */
    public function save_response()
    {
        $data = input('post.');
        
        $result = AiModelConfigService::saveResponse($data);
        
        return json($result);
    }
    
    /**
     * 删除响应定义
     */
    public function delete_response()
    {
        $id = input('param.id/d', 0);
        
        $result = AiModelConfigService::deleteResponse($id);
        
        return json($result);
    }
    
    /**
     * 导出配置
     */
    public function export()
    {
        $id = input('param.id/d', 0);
        
        $json = AiModelConfigService::exportModelConfig($id);
        
        if (!$json) {
            return $this->error('模型不存在');
        }
        
        $model = AiModelInstance::find($id);
        $filename = $model->model_code . '_config_' . date('Ymd') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $json;
        exit;
    }
    
    /**
     * 导入配置
     */
    public function import()
    {
        if (request()->isPost()) {
            $jsonData = input('post.json_data', '');
            
            $result = AiModelConfigService::importModelConfig($jsonData, $this->aid);
            
            return json($result);
        }
        
        return View::fetch();
    }
}

<?php

namespace app\controller;

use app\BaseController;
use app\service\ApiConfigService;
use app\model\AiModelInstance;
use think\facade\View;

/**
 * API配置管理控制器
 * Class ApiConfig
 * @package app\controller
 */
class ApiConfig extends BaseController
{
    protected $aid;
    protected $bid;
    protected $mdid;
    protected $uid;
    
    public function initialize()
    {
        parent::initialize();
        $this->aid = input('param.aid/d', 0);
        $this->bid = input('param.bid/d', 0);
        $this->mdid = input('param.mdid/d', 0);
        $this->uid = session('uid') ?? 1; // 从session获取用户ID
        
        define('aid', $this->aid);
    }
    
    /**
     * API配置列表页
     */
    public function index()
    {
        if (request()->isAjax()) {
            try {
                $page = input('param.page/d', 1);
                $limit = input('param.limit/d', 15);
                
                $params = [
                    'api_code' => input('param.api_code', ''),
                    'api_name' => input('param.api_name', ''),
                    'api_type' => input('param.api_type', ''),
                    'provider' => input('param.provider', ''),
                    'scope_type' => input('param.scope_type', ''),
                    'is_active' => input('param.is_active', ''),
                    'is_system' => input('param.is_system', ''),
                    'aid' => $this->aid,
                    'bid' => $this->bid,
                    'mdid' => $this->mdid,
                    'page' => $page,
                    'limit' => $limit
                ];
                
                $service = new ApiConfigService();
                $result = $service->getList($params);
                
                return json([
                    'code' => 0,
                    'msg' => '获取成功',
                    'count' => $result->total(),
                    'data' => $result->items()
                ]);
            } catch (\Exception $e) {
                return json([
                    'code' => 500,
                    'msg' => $e->getMessage(),
                    'data' => []
                ]);
            }
        }
        
        try {
            // 获取AI模型列表（用于筛选）
            $models = AiModelInstance::where('is_active', 1)
                ->field('id,model_name,model_code')
                ->select();
            View::assign('models', $models);
            
            // 获取服务提供商列表
            $providers = [
                'aliyun' => '阿里云',
                'baidu' => '百度',
                'openai' => 'OpenAI',
                'tencent' => '腾讯云',
                'other' => '其他'
            ];
            View::assign('providers', $providers);
            
            return View::fetch('api_config/index');
        } catch (\Exception $e) {
            return '<h1>错误</h1><p>' . $e->getMessage() . '</p><pre>' . $e->getTraceAsString() . '</pre>';
        }
    }
    
    /**
     * 新增/编辑API配置
     */
    public function edit()
    {
        $id = input('param.id/d', 0);
        $service = new ApiConfigService();
        
        if (request()->isPost()) {
            $data = input('post.');
            $data['aid'] = $this->aid;
            $data['bid'] = $this->bid;
            $data['mdid'] = $this->mdid;
            $data['owner_uid'] = $this->uid;
            
            if ($id > 0) {
                $result = $service->update($id, $data);
            } else {
                $result = $service->create($data);
            }
            
            if ($result['success']) {
                return json([
                    'code' => 0,
                    'msg' => $result['message'],
                    'data' => $result['data'] ?? null
                ]);
            } else {
                return json([
                    'code' => 400,
                    'msg' => $result['message']
                ]);
            }
        }
        
        if ($id > 0) {
            $api = \app\model\ApiConfig::with(['modelInstance', 'pricing'])->find($id);
            if (!$api) {
                return $this->error('API配置不存在');
            }
            View::assign('api', $api);
        }
        
        // 获取AI模型列表
        $models = AiModelInstance::where('is_active', 1)
            ->field('id,model_name,model_code,provider')
            ->select();
        View::assign('models', $models);
        
        // 获取服务提供商列表
        $providers = [
            'aliyun' => '阿里云',
            'baidu' => '百度',
            'openai' => 'OpenAI',
            'tencent' => '腾讯云',
            'other' => '其他'
        ];
        View::assign('providers', $providers);
        
        // 作用域类型
        $scopeTypes = [
            1 => '全局公开',
            2 => '仅自用',
            3 => '付费公开'
        ];
        View::assign('scopeTypes', $scopeTypes);
        
        return View::fetch('api_config/edit');
    }
    
    /**
     * 删除API配置
     */
    public function delete()
    {
        $id = input('param.id/d', 0);
        $service = new ApiConfigService();
        
        $result = $service->delete($id);
        
        if ($result['success']) {
            return json([
                'code' => 0,
                'msg' => $result['message']
            ]);
        } else {
            return json([
                'code' => 400,
                'msg' => $result['message']
            ]);
        }
    }
    
    /**
     * 切换激活状态
     */
    public function toggle()
    {
        $id = input('param.id/d', 0);
        $isActive = input('param.is_active/d', 0);
        
        $api = \app\model\ApiConfig::find($id);
        if (!$api) {
            return json([
                'code' => 404,
                'msg' => 'API配置不存在'
            ]);
        }
        
        $api->is_active = $isActive;
        $api->save();
        
        return json([
            'code' => 0,
            'msg' => '操作成功'
        ]);
    }
    
    /**
     * 计费规则设置页
     */
    public function pricing()
    {
        $apiConfigId = input('param.api_config_id/d', 0);
        
        if (request()->isPost()) {
            $data = input('post.');
            $data['api_config_id'] = $apiConfigId;
            
            $service = new \app\service\ApiPricingService();
            $result = $service->updatePricing($apiConfigId, $data);
            
            if ($result['success']) {
                return json([
                    'code' => 0,
                    'msg' => $result['message'],
                    'data' => $result['data'] ?? null
                ]);
            } else {
                return json([
                    'code' => 400,
                    'msg' => $result['message']
                ]);
            }
        }
        
        $api = \app\model\ApiConfig::with(['pricing', 'modelInstance'])->find($apiConfigId);
        if (!$api) {
            return $this->error('API配置不存在');
        }
        
        View::assign('api', $api);
        
        // 计费模式列表
        $billingModes = [
            'fixed' => '固定计费',
            'token' => 'Token计费',
            'duration' => '时长计费',
            'image' => '图片计费'
        ];
        View::assign('billingModes', $billingModes);
        
        // 计费单位列表
        $unitTypes = [
            'per_call' => '每次调用',
            'per_token' => '每Token',
            'per_minute' => '每分钟',
            'per_image' => '每张图片'
        ];
        View::assign('unitTypes', $unitTypes);
        
        return View::fetch('api_config/pricing');
    }
    
    /**
     * 授权管理页
     */
    public function authorize()
    {
        $apiConfigId = input('param.api_config_id/d', 0);
        
        if (request()->isAjax()) {
            $authorizations = \app\model\ApiAuthorization::where('api_config_id', $apiConfigId)
                ->with(['apiConfig'])
                ->select();
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'count' => count($authorizations),
                'data' => $authorizations
            ]);
        }
        
        $api = \app\model\ApiConfig::find($apiConfigId);
        if (!$api) {
            return $this->error('API配置不存在');
        }
        
        View::assign('api', $api);
        
        return View::fetch('api_config/authorize');
    }
    
    /**
     * 保存授权配置
     */
    public function saveAuth()
    {
        $data = input('post.');
        $apiConfigId = $data['api_config_id'] ?? 0;
        $granteeAid = $data['grantee_aid'] ?? 0;
        $granteeBid = $data['grantee_bid'] ?? 0;
        $granteeMdid = $data['grantee_mdid'] ?? 0;
        
        $options = [
            'auth_type' => $data['auth_type'] ?? 2,
            'quota_daily' => $data['quota_daily'] ?? 0,
            'quota_monthly' => $data['quota_monthly'] ?? 0,
            'expire_time' => $data['expire_time'] ? strtotime($data['expire_time']) : 0,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        $result = \app\model\ApiAuthorization::grantAccess(
            $apiConfigId,
            $granteeAid,
            $granteeBid,
            $granteeMdid,
            $options
        );
        
        if ($result) {
            return json([
                'code' => 0,
                'msg' => '授权成功'
            ]);
        } else {
            return json([
                'code' => 400,
                'msg' => '授权失败'
            ]);
        }
    }
}

<?php

namespace app\service;

use app\model\ApiConfig;
use app\model\ApiPricing;
use app\model\AiModelInstance;
use think\facade\Db;
use think\facade\Log;

/**
 * API配置管理服务
 * Class ApiConfigService
 * @package app\service
 */
class ApiConfigService
{
    /**
     * 获取API配置列表
     */
    public function getList($params = [])
    {
        $query = ApiConfig::with(['modelInstance', 'pricing']);
        
        // 应用搜索条件
        if (isset($params['api_code']) && $params['api_code']) {
            $query->where('api_code', 'like', "%{$params['api_code']}%");
        }
        
        if (isset($params['api_name']) && $params['api_name']) {
            $query->where('api_name', 'like', "%{$params['api_name']}%");
        }
        
        if (isset($params['api_type']) && $params['api_type']) {
            $query->where('api_type', $params['api_type']);
        }
        
        if (isset($params['provider']) && $params['provider']) {
            $query->where('provider', $params['provider']);
        }
        
        if (isset($params['scope_type']) && $params['scope_type'] !== '') {
            $query->where('scope_type', $params['scope_type']);
        }
        
        if (isset($params['is_active']) && $params['is_active'] !== '') {
            $query->where('is_active', $params['is_active']);
        }
        
        if (isset($params['is_system']) && $params['is_system'] !== '') {
            $query->where('is_system', $params['is_system']);
        }
        
        // 权限筛选
        if (isset($params['aid'])) {
            $query->where('aid', $params['aid']);
        }
        
        if (isset($params['bid'])) {
            $query->where('bid', $params['bid']);
        }
        
        if (isset($params['mdid'])) {
            $query->where('mdid', $params['mdid']);
        }
        
        // 排序
        $query->order('sort', 'desc')->order('id', 'desc');
        
        // 分页
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 15;
        
        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page
        ]);
        
        return $list;
    }
    
    /**
     * 创建API配置
     */
    public function create($data)
    {
        Db::startTrans();
        try {
            // 验证API代码唯一性
            $exists = ApiConfig::where('api_code', $data['api_code'])->find();
            if ($exists) {
                throw new \Exception('API代码已存在');
            }
            
            // 创建API配置
            $api = new ApiConfig();
            $api->aid = $data['aid'] ?? 0;
            $api->bid = $data['bid'] ?? 0;
            $api->mdid = $data['mdid'] ?? 0;
            $api->model_id = $data['model_id'] ?? 0;
            $api->api_code = $data['api_code'];
            $api->api_name = $data['api_name'];
            $api->api_type = $data['api_type'];
            $api->provider = $data['provider'];
            $api->api_key = $data['api_key'];
            $api->api_secret = $data['api_secret'] ?? '';
            $api->endpoint_url = $data['endpoint_url'];
            $api->config_json = $data['config_json'] ?? [];
            $api->is_system = $data['is_system'] ?? 0;
            $api->owner_uid = $data['owner_uid'] ?? 0;
            $api->scope_type = $data['scope_type'] ?? ApiConfig::SCOPE_PRIVATE;
            $api->is_active = $data['is_active'] ?? 1;
            $api->description = $data['description'] ?? '';
            $api->sort = $data['sort'] ?? 100;
            $api->save();
            
            // 如果关联了AI模型，继承模型的计费规则
            if ($api->model_id > 0 && isset($data['inherit_pricing']) && $data['inherit_pricing']) {
                $pricing = ApiPricing::inheritFromModel($api->id, $api->model_id);
                if ($pricing) {
                    $pricing->save();
                }
            }
            
            // 如果提供了自定义计费规则
            if (isset($data['pricing']) && is_array($data['pricing'])) {
                $this->savePricing($api->id, $data['pricing']);
            }
            
            Db::commit();
            return ['success' => true, 'data' => $api, 'message' => '创建成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建API配置失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 更新API配置
     */
    public function update($id, $data)
    {
        Db::startTrans();
        try {
            $api = ApiConfig::find($id);
            if (!$api) {
                throw new \Exception('API配置不存在');
            }
            
            // 验证API代码唯一性（排除自己）
            if (isset($data['api_code']) && $data['api_code'] != $api->api_code) {
                $exists = ApiConfig::where('api_code', $data['api_code'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new \Exception('API代码已存在');
                }
            }
            
            // 更新字段
            if (isset($data['api_code'])) $api->api_code = $data['api_code'];
            if (isset($data['api_name'])) $api->api_name = $data['api_name'];
            if (isset($data['api_type'])) $api->api_type = $data['api_type'];
            if (isset($data['provider'])) $api->provider = $data['provider'];
            if (isset($data['api_key'])) $api->api_key = $data['api_key'];
            if (isset($data['api_secret'])) $api->api_secret = $data['api_secret'];
            if (isset($data['endpoint_url'])) $api->endpoint_url = $data['endpoint_url'];
            if (isset($data['config_json'])) $api->config_json = $data['config_json'];
            if (isset($data['scope_type'])) $api->scope_type = $data['scope_type'];
            if (isset($data['is_active'])) $api->is_active = $data['is_active'];
            if (isset($data['description'])) $api->description = $data['description'];
            if (isset($data['sort'])) $api->sort = $data['sort'];
            
            $api->save();
            
            // 更新计费规则
            if (isset($data['pricing']) && is_array($data['pricing'])) {
                $this->savePricing($api->id, $data['pricing']);
            }
            
            Db::commit();
            return ['success' => true, 'data' => $api, 'message' => '更新成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('更新API配置失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 删除API配置
     */
    public function delete($id)
    {
        Db::startTrans();
        try {
            $api = ApiConfig::find($id);
            if (!$api) {
                throw new \Exception('API配置不存在');
            }
            
            // 删除关联的计费规则
            ApiPricing::where('api_config_id', $id)->delete();
            
            // 删除API配置
            $api->delete();
            
            Db::commit();
            return ['success' => true, 'message' => '删除成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('删除API配置失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 保存计费规则
     */
    private function savePricing($apiConfigId, $pricingData)
    {
        // 查找或创建计费规则
        $pricing = ApiPricing::where('api_config_id', $apiConfigId)->find();
        if (!$pricing) {
            $pricing = new ApiPricing();
            $pricing->api_config_id = $apiConfigId;
        }
        
        $pricing->billing_mode = $pricingData['billing_mode'] ?? ApiPricing::BILLING_FIXED;
        $pricing->cost_per_unit = $pricingData['cost_per_unit'] ?? 0;
        $pricing->price_per_unit = $pricingData['price_per_unit'] ?? 0;
        $pricing->unit_type = $pricingData['unit_type'] ?? ApiPricing::UNIT_PER_CALL;
        $pricing->min_charge = $pricingData['min_charge'] ?? 0;
        $pricing->free_quota = $pricingData['free_quota'] ?? 0;
        $pricing->tier_pricing_json = $pricingData['tier_pricing_json'] ?? null;
        $pricing->is_active = $pricingData['is_active'] ?? 1;
        $pricing->save();
        
        return $pricing;
    }
    
    /**
     * 获取可用的API列表（供调用者选择）
     */
    public function getAvailableApis($aid, $bid, $mdid, $uid)
    {
        $query = ApiConfig::with(['pricing'])
            ->where('is_active', 1)
            ->where(function($q) use ($aid, $bid, $mdid) {
                // 全局公开的系统API
                $q->where(function($query) {
                    $query->where('is_system', 1)
                        ->where('scope_type', ApiConfig::SCOPE_GLOBAL);
                });
                
                // 自己创建的API
                $q->whereOr(function($query) use ($aid, $bid, $mdid) {
                    $query->where('aid', $aid)
                        ->where('bid', $bid)
                        ->where('mdid', $mdid);
                });
                
                // 上级组织的仅自用API
                $q->whereOr(function($query) use ($aid, $bid) {
                    $query->where('aid', $aid)
                        ->where('bid', $bid)
                        ->where('mdid', 0)
                        ->where('scope_type', ApiConfig::SCOPE_PRIVATE);
                });
                
                $q->whereOr(function($query) use ($aid) {
                    $query->where('aid', $aid)
                        ->where('bid', 0)
                        ->where('mdid', 0)
                        ->where('scope_type', ApiConfig::SCOPE_PRIVATE);
                });
            })
            ->order('sort', 'desc')
            ->select();
        
        return $query;
    }
    
    /**
     * 从AI模型创建API配置
     */
    public function createFromModel($modelId, $data)
    {
        $model = AiModelInstance::find($modelId);
        if (!$model) {
            return ['success' => false, 'message' => 'AI模型不存在'];
        }
        
        // 继承模型配置
        $apiData = [
            'model_id' => $modelId,
            'api_code' => $data['api_code'] ?? $model->model_code,
            'api_name' => $data['api_name'] ?? $model->model_name,
            'api_type' => $model->category_code,
            'provider' => $model->provider,
            'api_key' => $data['api_key'],
            'api_secret' => $data['api_secret'] ?? '',
            'endpoint_url' => $data['endpoint_url'] ?? '',
            'config_json' => $model->default_params ?? [],
            'is_system' => $data['is_system'] ?? 0,
            'owner_uid' => $data['owner_uid'] ?? 0,
            'scope_type' => $data['scope_type'] ?? ApiConfig::SCOPE_PRIVATE,
            'is_active' => 1,
            'description' => $data['description'] ?? $model->description,
            'sort' => $data['sort'] ?? 100,
            'aid' => $data['aid'] ?? 0,
            'bid' => $data['bid'] ?? 0,
            'mdid' => $data['mdid'] ?? 0,
            'inherit_pricing' => true
        ];
        
        return $this->create($apiData);
    }
}

<?php
/**
 * 场景配置服务类
 * 处理场景配置的核心业务逻辑
 * 
 * @package app\service
 * @author AI Assistant
 * @date 2026-02-06
 */

declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Cache;
use think\exception\ValidateException;

class SceneConfigService
{
    /**
     * 获取启用的AI模型列表（模型广场标准）
     * 从 ddwx_model_info 查询模型，从 ddwx_merchant_model_config 查询商户API Key配置
     * 优先级：商户自己的API Key > 平台级配置（需预充值）
     * 
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @return array
     */
    public function getEnabledModelList($aid, $bid = 0)
    {
        // 从模型广场获取所有激活的模型
        $models = Db::name('model_info')
            ->alias('mi')
            ->leftJoin('model_provider mp', 'mi.provider_id = mp.id')
            ->where('mi.is_active', '=', 1)
            ->where('mi.aid', 'in', [0, $aid])
            ->field('mi.id, mi.model_code, mi.model_name, mi.provider_id, mp.provider_name as provider, mp.provider_code, mi.endpoint_url, mi.pricing_config, mi.limits_config, mi.capability_tags, mi.sort')
            ->order('mi.sort', 'asc')
            ->select()
            ->toArray();

        // 获取商户的API Key配置（如果有）
        $merchantConfigs = [];
        if ($bid > 0) {
            $configs = Db::name('merchant_model_config')
                ->where('bid', '=', $bid)
                ->where('is_active', '=', 1)
                ->column('api_key,api_secret', 'model_id');
            $merchantConfigs = $configs;
        }

        // 格式化模型数据，添加图生图能力过滤
        $result = [];
        foreach ($models as $model) {
            // 解析 pricing_config
            $pricing = !empty($model['pricing_config']) ? json_decode($model['pricing_config'], true) : [];
            $limits = !empty($model['limits_config']) ? json_decode($model['limits_config'], true) : [];

            // 判断是否有商户自己的API Key
            $hasMerchantKey = isset($merchantConfigs[$model['id']]) && !empty($merchantConfigs[$model['id']]['api_key']);

            // 判断是否具有图生图/图像生成能力
            // 支持的模型类型：
            // 1. 阿里云Wan系列：model_code包含i2i或imageedit，或等于tongyi_wanxiang
            // 2. 火山引擎Doubao系列：doubao-seedream(图像生成，排除3.0-t2i仅文生图)、doubao-seedance-*-i2v(图生视频)
            $modelCode = $model['model_code'] ?? '';
            $providerCode = strtolower($model['provider_code'] ?? '');
            $isImageToImage = (
                // 阿里云Wan系列
                stripos($modelCode, 'i2i') !== false ||
                stripos($modelCode, 'imageedit') !== false ||
                $modelCode === 'tongyi_wanxiang' ||
                // 火山引擎Doubao图像/视频生成模型（支持图生）
                // 注意：Seedream 3.0-t2i 仅文生图，不支持图生图，需排除
                (stripos($modelCode, 'doubao-seedream') !== false && stripos($modelCode, '3-0-t2i') === false) ||
                stripos($modelCode, 'doubao-seedance') !== false
            );

            // 只返回具有图生图能力的模型
            if (!$isImageToImage) {
                continue;
            }

            $result[] = [
                'id' => $model['id'],
                'aid' => $aid,
                'bid' => $bid,
                'model_name' => $model['model_name'],
                'model_type' => $model['model_code'],
                'provider' => $model['provider'],
                'api_base_url' => $model['endpoint_url'],
                'timeout' => $limits['timeout'] ?? 180,
                'max_retry' => 3,
                'cost_per_image' => $pricing['cost_price'] ?? 0,
                'cost_per_video' => $pricing['cost_price'] ?? 0,
                // 配置类型标识
                'is_merchant_config' => $hasMerchantKey,      // true=商户自有API Key
                'is_platform_config' => !$hasMerchantKey,     // true=平台配置（需预充值）
                'is_platform_model' => !$hasMerchantKey,       // true=平台级模型（需预充值）
                'config_type_label' => $hasMerchantKey ? '商户自配置' : '平台预充值',
                // 商户API Key信息（仅用于后端调用，前端不显示）
                'api_key' => $hasMerchantKey ? $merchantConfigs[$model['id']]['api_key'] : '',
                'api_secret' => $hasMerchantKey ? ($merchantConfigs[$model['id']]['api_secret'] ?? '') : '',
            ];
        }

        // 按优先级排序：商户自配置模型在前，平台级在后
        usort($result, function($a, $b) {
            return $b['is_merchant_config'] - $a['is_merchant_config'];
        });

        return $result;
    }
    
    /**
     * 获取模型支持的场景类型
     * 
     * @param int $modelId 模型ID
     * @return array
     */
    public function getModelSupportedSceneTypes($modelId)
    {
        // 获取模型信息
        $model = Db::name('ai_model_instance')
            ->where('id', $modelId)
            ->find();
        
        if (!$model) {
            throw new ValidateException('模型不存在');
        }
        
        // 获取模型能力标签
        $capabilityTags = !empty($model['capability_tags']) 
            ? json_decode($model['capability_tags'], true) 
            : [];
        
        // 使用SceneParameterService判断支持的场景类型
        $paramService = new SceneParameterService();
        $supportedTypes = $paramService->getSupportedSceneTypes($capabilityTags);
        
        // 获取场景类型元数据
        $sceneTypes = Db::name('ai_travel_photo_scene_type')
            ->whereIn('scene_type', $supportedTypes)
            ->where('is_active', 1)
            ->order('sort', 'asc')
            ->select()
            ->each(function($item) {
                if (!empty($item['input_requirements'])) {
                    $item['input_requirements'] = json_decode($item['input_requirements'], true);
                }
                if (!empty($item['form_template'])) {
                    $item['form_template'] = json_decode($item['form_template'], true);
                }
                $item['is_supported'] = true;
                return $item;
            });
        
        return $sceneTypes ? $sceneTypes->toArray() : [];
    }
    
    /**
     * 获取模型的参数定义
     * 
     * @param int $modelId 模型ID
     * @param int $sceneType 场景类型
     * @return array
     */
    public function getModelParameters($modelId, $sceneType = null)
    {
        $where = [
            ['model_id', '=', $modelId],
            ['is_active', '=', 1]
        ];
        
        $params = Db::name('ai_model_parameter')
            ->where($where)
            ->order('sort', 'asc')
            ->select();
        
        if (!$params) {
            return ['required_params' => [], 'optional_params' => []];
        }
        
        $required = [];
        $optional = [];
        
        foreach ($params as $param) {
            $paramData = [
                'param_name' => $param['param_code'],
                'param_label' => $param['param_name'],
                'param_type' => $param['param_type'],
                'default_value' => $param['default_value'],
                'description' => $param['description'],
                'validation_rule' => $param['validation_rule']
            ];
            
            // 解析选项列表
            if (!empty($param['options_json'])) {
                $paramData['enum_options'] = json_decode($param['options_json'], true);
            }
            
            if ($param['is_required']) {
                $required[] = $paramData;
            } else {
                $optional[] = $paramData;
            }
        }
        
        // 根据场景类型过滤必需参数
        if ($sceneType !== null) {
            $required = $this->filterParametersBySceneType($required, $sceneType);
        }
        
        return [
            'required_params' => $required,
            'optional_params' => $optional
        ];
    }
    
    /**
     * 获取场景参数模板
     * 
     * @param int $sceneType 场景类型
     * @return array
     */
    public function getSceneTemplate($sceneType)
    {
        $paramService = new SceneParameterService();
        $metadata = $paramService->getSceneTypeMetadata($sceneType);
        
        if (!$metadata) {
            throw new ValidateException('场景类型不存在');
        }
        
        return [
            'scene_type' => $metadata['scene_type'],
            'scene_name' => $metadata['scene_name'],
            'description' => $metadata['description'],
            'input_requirements' => $metadata['input_requirements'],
            'output_type' => $metadata['output_type'],
            'form_template' => $metadata['form_template']
        ];
    }
    
    /**
     * 获取API配置列表（按模型筛选）
     * 
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $modelId 模型ID
     * @return array
     */
    public function getApiConfigListByModel($aid, $bid, $modelId)
    {
        $where = [
            ['aid', '=', $aid],
            ['is_active', '=', 1]
        ];
        
        // 模型筛选
        if ($modelId > 0) {
            $where[] = ['model_id', '=', $modelId];
        }
        
        // 商家筛选：返回平台配置 + 商家自己的配置
        if ($bid > 0) {
            $where[] = ['bid', 'in', [0, $bid]];
        }
        
        $configs = Db::name('api_config')
            ->where($where)
            ->field('id, api_code, api_name, provider, model_id, is_system, scope_type, description')
            ->order('is_system', 'desc')
            ->order('sort', 'asc')
            ->select();
        
        return $configs ? $configs->toArray() : [];
    }
    
    /**
     * 保存场景配置
     * 
     * @param array $data 场景配置数据
     * @return int 场景ID
     */
    public function saveSceneConfig($data)
    {
        $sceneId = $data['id'] ?? 0;
        
        // 数据验证
        $this->validateSceneConfig($data);
        
        // 准备保存数据
        $saveData = [
            'aid' => $data['aid'],
            'bid' => $data['bid'] ?? 0,
            'mdid' => $data['mdid'] ?? 0,
            'model_id' => $data['model_id'],
            'scene_type' => $data['scene_type'],
            'scene_name' => $data['scene_name'],
            'category' => $data['category'] ?? '',
            'api_config_id' => $data['api_config_id'],
            'model_params' => json_encode($data['model_params'] ?? [], JSON_UNESCAPED_UNICODE),
            'reference_image' => $data['reference_image'] ?? '',
            'thumbnail' => $data['thumbnail'] ?? '',
            'prompt' => $data['prompt'] ?? '',
            'negative_prompt' => $data['negative_prompt'] ?? '',
            'sort' => $data['sort'] ?? 100,
            'status' => $data['status'] ?? 1,
            'is_public' => $data['is_public'] ?? 0,
            'is_recommend' => $data['is_recommend'] ?? 0,
            'update_time' => time()
        ];
        
        if ($sceneId > 0) {
            // 更新
            Db::name('ai_travel_photo_scene')
                ->where('id', $sceneId)
                ->update($saveData);
        } else {
            // 新增
            $saveData['create_time'] = time();
            $saveData['use_count'] = 0;
            $saveData['success_count'] = 0;
            $saveData['fail_count'] = 0;
            $saveData['avg_time'] = 0;
            
            $sceneId = Db::name('ai_travel_photo_scene')->insertGetId($saveData);
        }
        
        // 清除缓存
        $this->clearSceneCache($data['aid'], $data['bid'] ?? 0);
        
        return $sceneId;
    }
    
    /**
     * 获取场景详情
     * 
     * @param int $sceneId 场景ID
     * @return array
     */
    public function getSceneDetail($sceneId)
    {
        $scene = Db::name('ai_travel_photo_scene')
            ->alias('s')
            ->leftJoin('ai_model_instance m', 's.model_id = m.id')
            ->leftJoin('api_config a', 's.api_config_id = a.id')
            ->where('s.id', $sceneId)
            ->field('s.*, m.model_name, m.model_code, a.api_name')
            ->find();
        
        if (!$scene) {
            throw new ValidateException('场景不存在');
        }
        
        // 解析JSON字段
        if (!empty($scene['model_params'])) {
            $scene['model_params'] = json_decode($scene['model_params'], true);
        }
        
        return $scene;
    }
    
    /**
     * 删除场景配置
     * 
     * @param int $sceneId 场景ID
     * @return bool
     */
    public function deleteScene($sceneId)
    {
        $scene = Db::name('ai_travel_photo_scene')->find($sceneId);
        
        if (!$scene) {
            throw new ValidateException('场景不存在');
        }
        
        // 检查是否有生成记录
        $hasGeneration = Db::name('ai_travel_photo_generation')
            ->where('scene_id', $sceneId)
            ->count();
        
        if ($hasGeneration > 0) {
            throw new ValidateException('该场景已有生成记录，无法删除');
        }
        
        // 删除场景
        Db::name('ai_travel_photo_scene')
            ->where('id', $sceneId)
            ->delete();
        
        // 清除缓存
        $this->clearSceneCache($scene['aid'], $scene['bid']);
        
        return true;
    }
    
    /**
     * 验证场景配置数据
     * 
     * @param array $data
     * @throws ValidateException
     */
    private function validateSceneConfig($data)
    {
        // 必填字段验证
        if (empty($data['model_id'])) {
            throw new ValidateException('请选择AI模型');
        }
        
        if (empty($data['scene_type'])) {
            throw new ValidateException('请选择场景类型');
        }
        
        if (empty($data['scene_name'])) {
            throw new ValidateException('请填写场景名称');
        }
        
        if (empty($data['api_config_id'])) {
            throw new ValidateException('请选择API配置');
        }
        
        // 验证模型是否存在且启用
        $model = Db::name('ai_model_instance')
            ->where('id', $data['model_id'])
            ->where('is_active', 1)
            ->find();
        
        if (!$model) {
            throw new ValidateException('所选模型不存在或已禁用');
        }
        
        // 验证API配置是否存在且启用
        $apiConfig = Db::name('api_config')
            ->where('id', $data['api_config_id'])
            ->where('is_active', 1)
            ->find();
        
        if (!$apiConfig) {
            throw new ValidateException('所选API配置不存在或已禁用');
        }
        
        // 验证参数
        if (!empty($data['model_params'])) {
            $paramService = new SceneParameterService();
            $validation = $paramService->validateSceneTypeParams(
                $data['scene_type'], 
                $data['model_params']
            );
            
            if (!$validation['valid']) {
                throw new ValidateException('参数验证失败：' . implode('; ', $validation['errors']));
            }
        }
    }
    
    /**
     * 根据场景类型过滤参数
     * 
     * @param array $params 参数列表
     * @param int $sceneType 场景类型
     * @return array
     */
    private function filterParametersBySceneType($params, $sceneType)
    {
        // 根据场景类型决定哪些参数是必需的
        $sceneRequirements = [
            1 => ['prompt'], // 文生图-单张
            2 => ['prompt', 'n'], // 文生图-多张
            3 => ['image', 'prompt'], // 图生图-单张生成单张
            4 => ['image', 'prompt', 'sequential_image_generation_options'], // 图生图-单张生成多张
            5 => ['image', 'prompt'], // 图生图-多张生成单张
            6 => ['image', 'prompt', 'sequential_image_generation_options'], // 图生图-多张生成多张
        ];
        
        $required = $sceneRequirements[$sceneType] ?? [];
        
        return array_filter($params, function($param) use ($required) {
            return in_array($param['param_name'], $required);
        });
    }
    
    /**
     * 清除场景缓存
     * 
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     */
    private function clearSceneCache($aid, $bid)
    {
        Cache::tag('scene_list')->clear();
        Cache::delete("scene_list:{$aid}:{$bid}");
    }
}

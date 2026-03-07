<?php

namespace app\service;

use app\model\AiModelInstance;
use app\model\AiModelParameter;
use app\model\AiModelResponse;
use app\model\AiModelPricing;
use think\Exception;

/**
 * AI模型配置服务
 * Class AiModelConfigService
 * @package app\service
 */
class AiModelConfigService
{
    /**
     * 获取模型列表
     * @param array $filters 筛选条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public static function getModelList($filters = [], $page = 1, $limit = 15)
    {
        $query = AiModelInstance::withSearch([
            'model_code', 'model_name', 'category_code', 'provider', 'is_active', 'aid'
        ], $filters)->with(['parameters' => function($query) {
            $query->field('model_id, COUNT(*) as param_count')->group('model_id');
        }]);
        
        // 排序
        $query->order('sort', 'desc')->order('id', 'desc');
        
        // 分页
        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);
        
        return [
            'total' => $list->total(),
            'per_page' => $list->listRows(),
            'current_page' => $list->currentPage(),
            'last_page' => $list->lastPage(),
            'data' => $list->items()
        ];
    }
    
    /**
     * 根据模型代码获取配置
     * @param string $modelCode 模型代码
     * @param int $aid 平台ID
     * @return AiModelInstance|null
     */
    public static function getModelByCode($modelCode, $aid = 0)
    {
        return AiModelInstance::getFullConfig($modelCode, $aid);
    }
    
    /**
     * 根据ID获取模型配置
     * @param int $id 模型ID
     * @return AiModelInstance|null
     */
    public static function getModelById($id)
    {
        return AiModelInstance::with(['parameters', 'responses'])->find($id);
    }
    
    /**
     * 保存模型实例
     * @param array $data 模型数据
     * @return array
     */
    public static function saveModel($data)
    {
        try {
            // 能力标签处理
            if (isset($data['capability_tags']) && is_string($data['capability_tags'])) {
                $data['capability_tags'] = json_decode($data['capability_tags'], true);
            }
            
            if (isset($data['id']) && $data['id'] > 0) {
                // 更新
                $model = AiModelInstance::find($data['id']);
                if (!$model) {
                    return ['success' => false, 'message' => '模型不存在'];
                }
                
                // 系统预置模型只能由平台管理员修改
                if ($model->is_system && $data['aid'] != 0) {
                    return ['success' => false, 'message' => '无权修改系统预置模型'];
                }
                
                $model->save($data);
                $modelId = $model->id;
            } else {
                // 新增
                unset($data['id']);
                $model = AiModelInstance::create($data);
                $modelId = $model->id;
            }
            
            return ['success' => true, 'message' => '保存成功', 'id' => $modelId];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 删除模型实例
     * @param int $id 模型ID
     * @param int $aid 平台ID
     * @return array
     */
    public static function deleteModel($id, $aid = 0)
    {
        try {
            $model = AiModelInstance::find($id);
            if (!$model) {
                return ['success' => false, 'message' => '模型不存在'];
            }
            
            // 系统预置模型不允许删除
            if ($model->is_system) {
                return ['success' => false, 'message' => '系统预置模型不允许删除'];
            }
            
            // 检查是否有关联数据
            $paramCount = AiModelParameter::where('model_id', $id)->count();
            $responseCount = AiModelResponse::where('model_id', $id)->count();
            
            if ($paramCount > 0 || $responseCount > 0) {
                return ['success' => false, 'message' => '该模型有关联的参数或响应定义，请先删除相关数据'];
            }
            
            $model->delete();
            
            return ['success' => true, 'message' => '删除成功'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 获取模型参数列表
     * @param int $modelId 模型ID
     * @return array
     */
    public static function getModelParameters($modelId)
    {
        return AiModelParameter::where('model_id', $modelId)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 保存参数定义
     * @param array $data 参数数据
     * @return array
     */
    public static function saveParameter($data)
    {
        try {
            // JSON字段处理
            foreach (['enum_options', 'value_range'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data[$field] = $decoded;
                    }
                }
            }
            
            if (isset($data['id']) && $data['id'] > 0) {
                // 更新
                $param = AiModelParameter::find($data['id']);
                if (!$param) {
                    return ['success' => false, 'message' => '参数不存在'];
                }
                $param->save($data);
            } else {
                // 新增
                unset($data['id']);
                $param = AiModelParameter::create($data);
            }
            
            return ['success' => true, 'message' => '保存成功', 'id' => $param->id];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 删除参数定义
     * @param int $id 参数ID
     * @return array
     */
    public static function deleteParameter($id)
    {
        try {
            $param = AiModelParameter::find($id);
            if (!$param) {
                return ['success' => false, 'message' => '参数不存在'];
            }
            
            $param->delete();
            
            return ['success' => true, 'message' => '删除成功'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 获取模型响应定义列表
     * @param int $modelId 模型ID
     * @return array
     */
    public static function getModelResponses($modelId)
    {
        return AiModelResponse::where('model_id', $modelId)
            ->select()
            ->toArray();
    }
    
    /**
     * 保存响应定义
     * @param array $data 响应数据
     * @return array
     */
    public static function saveResponse($data)
    {
        try {
            if (isset($data['id']) && $data['id'] > 0) {
                // 更新
                $response = AiModelResponse::find($data['id']);
                if (!$response) {
                    return ['success' => false, 'message' => '响应定义不存在'];
                }
                $response->save($data);
            } else {
                // 新增
                unset($data['id']);
                $response = AiModelResponse::create($data);
            }
            
            return ['success' => true, 'message' => '保存成功', 'id' => $response->id];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 删除响应定义
     * @param int $id 响应ID
     * @return array
     */
    public static function deleteResponse($id)
    {
        try {
            $response = AiModelResponse::find($id);
            if (!$response) {
                return ['success' => false, 'message' => '响应定义不存在'];
            }
            
            $response->delete();
            
            return ['success' => true, 'message' => '删除成功'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 校验配置完整性
     * @param int $modelId 模型ID
     * @return array
     */
    public static function validateModelConfig($modelId)
    {
        $warnings = [];
        
        $model = AiModelInstance::find($modelId);
        if (!$model) {
            return ['valid' => false, 'errors' => ['模型不存在']];
        }
        
        // 检查参数定义
        $paramCount = AiModelParameter::where('model_id', $modelId)->count();
        if ($paramCount == 0) {
            $warnings[] = '未定义任何参数';
        }
        
        // 检查响应定义
        $responseCount = AiModelResponse::where('model_id', $modelId)->count();
        if ($responseCount == 0) {
            $warnings[] = '未定义任何响应字段';
        }
        
        // 检查定价配置
        $pricingCount = AiModelPricing::where('model_id', $modelId)->where('is_active', 1)->count();
        if ($pricingCount == 0) {
            $warnings[] = '未配置定价';
        }
        
        return [
            'valid' => empty($warnings),
            'warnings' => $warnings
        ];
    }
    
    /**
     * 导出模型配置为JSON
     * @param int $modelId 模型ID
     * @return string|false
     */
    public static function exportModelConfig($modelId)
    {
        $model = self::getModelById($modelId);
        if (!$model) {
            return false;
        }
        
        $config = [
            'model' => $model->toArray(),
            'parameters' => self::getModelParameters($modelId),
            'responses' => self::getModelResponses($modelId),
        ];
        
        return json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * 从JSON导入模型配置
     * @param string $jsonData JSON数据
     * @param int $aid 平台ID
     * @return array
     */
    public static function importModelConfig($jsonData, $aid = 0)
    {
        try {
            $config = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'JSON格式错误'];
            }
            
            if (!isset($config['model'])) {
                return ['success' => false, 'message' => '缺少模型信息'];
            }
            
            // 导入模型
            $modelData = $config['model'];
            $modelData['aid'] = $aid;
            $modelData['is_system'] = 0; // 导入的模型标记为自定义
            unset($modelData['id']);
            
            $result = self::saveModel($modelData);
            if (!$result['success']) {
                return $result;
            }
            
            $modelId = $result['id'];
            
            // 导入参数
            if (isset($config['parameters']) && is_array($config['parameters'])) {
                foreach ($config['parameters'] as $param) {
                    $param['model_id'] = $modelId;
                    unset($param['id']);
                    self::saveParameter($param);
                }
            }
            
            // 导入响应定义
            if (isset($config['responses']) && is_array($config['responses'])) {
                foreach ($config['responses'] as $response) {
                    $response['model_id'] = $modelId;
                    unset($response['id']);
                    self::saveResponse($response);
                }
            }
            
            return ['success' => true, 'message' => '导入成功', 'id' => $modelId];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

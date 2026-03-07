<?php

namespace app\service;

use app\model\AiModelInstance;
use app\model\AiModelParameter;

/**
 * 模型参数校验服务
 * Class ModelParameterValidator
 * @package app\service
 */
class ModelParameterValidator
{
    /**
     * 校验参数
     * @param string $modelCode 模型代码
     * @param array $params 业务参数
     * @return array
     */
    public static function validate($modelCode, $params)
    {
        // 获取模型配置
        $model = AiModelInstance::where('model_code', $modelCode)->find();
        if (!$model) {
            return ['valid' => false, 'message' => "模型{$modelCode}不存在"];
        }
        
        // 获取参数定义
        $definitions = AiModelParameter::where('model_id', $model->id)->select();
        
        $errors = [];
        $validated = [];
        
        foreach ($definitions as $def) {
            $value = $params[$def->param_name] ?? null;
            
            // 使用模型的校验方法
            $result = $def->validateValue($value);
            
            if (!$result['valid']) {
                $errors[] = $result['message'];
            } else {
                // 使用校验后的值（可能经过类型转换）
                $validated[$def->param_name] = $result['value'] ?? $value;
            }
        }
        
        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'message' => implode('; ', $errors)
            ];
        }
        
        return [
            'valid' => true,
            'params' => $validated
        ];
    }
    
    /**
     * 填充默认值
     * @param string $modelCode 模型代码
     * @param array $params 业务参数
     * @return array
     */
    public static function fillDefaults($modelCode, $params)
    {
        // 获取模型配置
        $model = AiModelInstance::where('model_code', $modelCode)->find();
        if (!$model) {
            return $params;
        }
        
        // 获取参数定义
        $definitions = AiModelParameter::where('model_id', $model->id)->select();
        
        foreach ($definitions as $def) {
            // 如果参数未设置且有默认值，填充默认值
            if (!isset($params[$def->param_name]) && $def->default_value !== null) {
                // 解析默认值
                $defaultValue = $def->default_value;
                if (is_string($defaultValue)) {
                    // 尝试解析JSON
                    $decoded = json_decode($defaultValue, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $defaultValue = $decoded;
                    }
                }
                $params[$def->param_name] = $defaultValue;
            }
        }
        
        return $params;
    }
    
    /**
     * 转换参数类型
     * @param AiModelParameter $def 参数定义
     * @param mixed $value 参数值
     * @return mixed
     */
    public static function transformParam($def, $value)
    {
        if ($value === null) {
            return null;
        }
        
        switch ($def->param_type) {
            case 'integer':
                return (int)$value;
                
            case 'float':
                return (float)$value;
                
            case 'boolean':
                if (is_bool($value)) {
                    return $value;
                }
                return in_array($value, [1, '1', 'true', true], true);
                
            case 'array':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                }
                return is_array($value) ? $value : [];
                
            default:
                return $value;
        }
    }
}

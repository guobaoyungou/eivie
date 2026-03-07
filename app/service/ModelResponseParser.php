<?php

namespace app\service;

use app\model\AiModelResponse;

/**
 * 模型响应解析服务
 * Class ModelResponseParser
 * @package app\service
 */
class ModelResponseParser
{
    /**
     * 解析API响应
     * @param string $modelCode 模型代码
     * @param array $response API响应数据
     * @return array
     */
    public static function parse($modelCode, $response)
    {
        // 获取模型ID
        $model = \app\model\AiModelInstance::where('model_code', $modelCode)->find();
        if (!$model) {
            return [
                'success' => false,
                'message' => "模型{$modelCode}不存在"
            ];
        }
        
        // 批量提取字段
        $result = AiModelResponse::extractFields($model->id, $response);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => '响应解析失败',
                'errors' => $result['errors'],
                'raw' => $response
            ];
        }
        
        return [
            'success' => true,
            'data' => $result['data'],
            'raw' => $response
        ];
    }
    
    /**
     * 提取单个字段
     * @param string $fieldPath JSONPath表达式
     * @param array $response 响应数据
     * @return mixed
     */
    public static function extractField($fieldPath, $response)
    {
        if (empty($fieldPath)) {
            return null;
        }
        
        try {
            // 移除开头的 $. 或 $
            $path = preg_replace('/^\$\.?/', '', $fieldPath);
            
            // 分割路径
            $keys = explode('.', $path);
            $value = $response;
            
            foreach ($keys as $key) {
                // 处理数组索引，如 results[0]
                if (preg_match('/^(.+)\[(\d+)\]$/', $key, $matches)) {
                    $arrayKey = $matches[1];
                    $index = (int)$matches[2];
                    
                    if (!isset($value[$arrayKey]) || !is_array($value[$arrayKey])) {
                        return null;
                    }
                    
                    $value = $value[$arrayKey];
                    
                    if (!isset($value[$index])) {
                        return null;
                    }
                    
                    $value = $value[$index];
                } else {
                    // 普通键访问
                    if (!isset($value[$key])) {
                        return null;
                    }
                    $value = $value[$key];
                }
            }
            
            return $value;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 解析错误信息
     * @param array $response 响应数据
     * @return array
     */
    public static function parseError($response)
    {
        $errorCode = self::extractField('code', $response) ?? 
                     self::extractField('error.code', $response) ?? 
                     self::extractField('error_code', $response) ?? 
                     'UNKNOWN';
        
        $errorMessage = self::extractField('message', $response) ?? 
                       self::extractField('error.message', $response) ?? 
                       self::extractField('error_message', $response) ?? 
                       '未知错误';
        
        return [
            'code' => $errorCode,
            'message' => $errorMessage
        ];
    }
    
    /**
     * 校验响应完整性
     * @param string $modelCode 模型代码
     * @param array $response 响应数据
     * @return bool
     */
    public static function validateResponse($modelCode, $response)
    {
        $model = \app\model\AiModelInstance::where('model_code', $modelCode)->find();
        if (!$model) {
            return false;
        }
        
        // 获取关键字段定义
        $criticalFields = AiModelResponse::where('model_id', $model->id)
            ->where('is_critical', 1)
            ->select();
        
        foreach ($criticalFields as $field) {
            $value = $field->extractValue($response);
            if ($value === null) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 标准化返回结果
     * @param array $parsedData 解析后的数据
     * @return array
     */
    public static function standardizeResult($parsedData)
    {
        $result = [
            'success' => $parsedData['success'] ?? false,
            'message' => $parsedData['message'] ?? '',
        ];
        
        if (isset($parsedData['data'])) {
            foreach ($parsedData['data'] as $key => $item) {
                $result[$key] = $item['value'];
            }
        }
        
        return $result;
    }
}

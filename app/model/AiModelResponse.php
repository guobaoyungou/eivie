<?php

namespace app\model;

use think\Model;

/**
 * AI模型响应定义模型
 * Class AiModelResponse
 * @package app\model
 */
class AiModelResponse extends Model
{
    // 设置表名
    protected $name = 'ai_model_response';
    
    // 设置主键
    protected $pk = 'id';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'model_id' => 'integer',
        'is_critical' => 'integer',
    ];
    
    /**
     * 关联模型实例
     */
    public function modelInstance()
    {
        return $this->belongsTo(AiModelInstance::class, 'model_id', 'id');
    }
    
    /**
     * 获取关键字段状态文本
     */
    public function getIsCriticalTextAttr($value, $data)
    {
        return $data['is_critical'] ? '关键' : '可选';
    }
    
    /**
     * 获取字段类型文本
     */
    public function getFieldTypeTextAttr($value, $data)
    {
        $types = [
            'string' => '字符串',
            'integer' => '整数',
            'float' => '浮点数',
            'boolean' => '布尔值',
            'object' => '对象',
            'array' => '数组'
        ];
        return $types[$data['field_type']] ?? '未知';
    }
    
    /**
     * 搜索器：模型ID
     */
    public function searchModelIdAttr($query, $value)
    {
        if ($value) {
            $query->where('model_id', $value);
        }
    }
    
    /**
     * 搜索器：响应字段名
     */
    public function searchResponseFieldAttr($query, $value)
    {
        if ($value) {
            $query->where('response_field', 'like', "%{$value}%");
        }
    }
    
    /**
     * 搜索器：是否关键
     */
    public function searchIsCriticalAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_critical', $value);
        }
    }
    
    /**
     * 从响应数据中提取字段值
     * @param array $response 响应数据
     * @return mixed
     */
    public function extractValue($response)
    {
        if (empty($this->field_path)) {
            return null;
        }
        
        try {
            // 解析JSONPath
            $path = $this->field_path;
            
            // 移除开头的 $. 或 $
            $path = preg_replace('/^\$\.?/', '', $path);
            
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
     * 获取字段类型列表
     */
    public static function getFieldTypeList()
    {
        return [
            'string' => '字符串',
            'integer' => '整数',
            'float' => '浮点数',
            'boolean' => '布尔值',
            'object' => '对象',
            'array' => '数组'
        ];
    }
    
    /**
     * 批量提取响应字段
     * @param int $modelId 模型ID
     * @param array $response 响应数据
     * @return array 提取的字段数组
     */
    public static function extractFields($modelId, $response)
    {
        $definitions = self::where('model_id', $modelId)->select();
        $result = [];
        $errors = [];
        
        foreach ($definitions as $def) {
            $value = $def->extractValue($response);
            
            // 如果是关键字段且提取失败，记录错误
            if ($def->is_critical && $value === null) {
                $errors[] = "无法提取关键字段：{$def->field_label}（{$def->response_field}）";
            }
            
            $result[$def->response_field] = [
                'label' => $def->field_label,
                'value' => $value,
                'type' => $def->field_type,
                'is_critical' => $def->is_critical
            ];
        }
        
        return [
            'success' => empty($errors),
            'data' => $result,
            'errors' => $errors
        ];
    }
}

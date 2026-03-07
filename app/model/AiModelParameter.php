<?php

namespace app\model;

use think\Model;

/**
 * AI模型参数定义模型
 * Class AiModelParameter
 * @package app\model
 */
class AiModelParameter extends Model
{
    // 设置表名
    protected $name = 'ai_model_parameter';
    
    // 设置主键
    protected $pk = 'id';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'model_id' => 'integer',
        'is_required' => 'integer',
        'sort' => 'integer',
    ];
    
    // JSON字段
    protected $json = ['enum_options', 'value_range'];
    protected $jsonAssoc = true;
    
    /**
     * 关联模型实例
     */
    public function modelInstance()
    {
        return $this->belongsTo(AiModelInstance::class, 'model_id', 'id');
    }
    
    /**
     * 获取必填状态文本
     */
    public function getIsRequiredTextAttr($value, $data)
    {
        return $data['is_required'] ? '必填' : '可选';
    }
    
    /**
     * 获取参数类型文本
     */
    public function getParamTypeTextAttr($value, $data)
    {
        $types = [
            'string' => '字符串',
            'integer' => '整数',
            'float' => '浮点数',
            'boolean' => '布尔值',
            'file' => '文件',
            'array' => '数组'
        ];
        return $types[$data['param_type']] ?? '未知';
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
     * 搜索器：参数名称
     */
    public function searchParamNameAttr($query, $value)
    {
        if ($value) {
            $query->where('param_name', 'like', "%{$value}%");
        }
    }
    
    /**
     * 搜索器：是否必填
     */
    public function searchIsRequiredAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_required', $value);
        }
    }
    
    /**
     * 校验参数值
     */
    public function validateValue($value)
    {
        // 必填校验
        if ($this->is_required && ($value === null || $value === '')) {
            return ['valid' => false, 'message' => "{$this->param_label}为必填参数"];
        }
        
        // 如果值为空且非必填，跳过其他校验
        if ($value === null || $value === '') {
            return ['valid' => true];
        }
        
        // 类型校验
        switch ($this->param_type) {
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    return ['valid' => false, 'message' => "{$this->param_label}必须为整数"];
                }
                $value = (int)$value;
                break;
                
            case 'float':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => "{$this->param_label}必须为数字"];
                }
                $value = (float)$value;
                break;
                
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'])) {
                    return ['valid' => false, 'message' => "{$this->param_label}必须为布尔值"];
                }
                break;
        }
        
        // 范围校验
        if ($this->value_range) {
            $range = is_string($this->value_range) ? json_decode($this->value_range, true) : $this->value_range;
            
            if (isset($range['min']) && $value < $range['min']) {
                return ['valid' => false, 'message' => "{$this->param_label}不能小于{$range['min']}"];
            }
            
            if (isset($range['max']) && $value > $range['max']) {
                return ['valid' => false, 'message' => "{$this->param_label}不能大于{$range['max']}"];
            }
            
            if (isset($range['max_length']) && strlen($value) > $range['max_length']) {
                return ['valid' => false, 'message' => "{$this->param_label}长度不能超过{$range['max_length']}"];
            }
        }
        
        // 枚举校验
        if ($this->enum_options) {
            $options = is_string($this->enum_options) ? json_decode($this->enum_options, true) : $this->enum_options;
            if (!in_array($value, $options)) {
                return ['valid' => false, 'message' => "{$this->param_label}的值必须为：" . implode('、', $options)];
            }
        }
        
        // 自定义校验规则
        if ($this->validation_rule) {
            if (strpos($this->validation_rule, '/') === 0) {
                // 正则表达式校验
                if (!preg_match($this->validation_rule, $value)) {
                    return ['valid' => false, 'message' => "{$this->param_label}格式不正确"];
                }
            }
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    /**
     * 获取参数类型列表
     */
    public static function getParamTypeList()
    {
        return [
            'string' => '字符串',
            'integer' => '整数',
            'float' => '浮点数',
            'boolean' => '布尔值',
            'file' => '文件',
            'array' => '数组'
        ];
    }
    
    /**
     * 获取数据格式列表
     */
    public static function getDataFormatList()
    {
        return [
            'url' => 'URL地址',
            'base64' => 'Base64编码',
            'url_or_base64' => 'URL或Base64',
            'json' => 'JSON格式',
            'text' => '文本',
            'number' => '数字',
            'enum' => '枚举',
            'multipart' => '表单文件'
        ];
    }
}

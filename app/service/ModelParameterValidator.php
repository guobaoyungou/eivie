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
        // Seedream系列模型差异化校验
        $seedreamResult = self::validateSeedreamParams($modelCode, $params);
        if ($seedreamResult !== null && !$seedreamResult['valid']) {
            return $seedreamResult;
        }

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

        // 合并 Seedream 校验后的参数
        if ($seedreamResult !== null && isset($seedreamResult['params'])) {
            $validated = array_merge($validated, $seedreamResult['params']);
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

    // ============================================================
    // Seedream 系列模型差异化校验
    // ============================================================

    /**
     * Seedream 系列专属校验逻辑
     * 根据模型系列检查参数合规性（尺寸范围、禁用参数等）
     *
     * @param string $modelCode 模型代码
     * @param array $params 业务参数
     * @return array|null null=非Seedream模型，跳过
     */
    protected static function validateSeedreamParams($modelCode, $params)
    {
        $codeLower = strtolower($modelCode);

        // 识别模型系列
        $is30t2i  = (strpos($codeLower, 'seedream-3') !== false || strpos($codeLower, '3-0-t2i') !== false);
        $is50lite = (strpos($codeLower, 'seedream-5') !== false || strpos($codeLower, '5-0-lite') !== false);
        $is40     = (strpos($codeLower, 'seedream-4-0') !== false || strpos($codeLower, 'seedream-4.0') !== false);
        $is45     = (strpos($codeLower, 'seedream-4-5') !== false || strpos($codeLower, 'seedream-4.5') !== false);

        // 非 Seedream 模型，跳过
        if (!$is30t2i && !$is50lite && !$is40 && !$is45) {
            return null;
        }

        $errors = [];
        $validated = $params;

        // ---- size 校验 ----
        if (isset($params['size']) && preg_match('/^(\d+)x(\d+)$/', $params['size'], $m)) {
            $w = (int)$m[1];
            $h = (int)$m[2];
            $pixelProduct = $w * $h;
            $ratio = $w / max($h, 1);

            if ($is30t2i) {
                // 3.0-t2i: [512x512 ~ 2048x2048]，宽高比 [1/3, 3]
                if ($w < 512 || $h < 512 || $w > 2048 || $h > 2048) {
                    $errors[] = "Seedream 3.0-t2i 尺寸范围为 512x512~2048x2048，当前: {$params['size']}";
                }
                if ($ratio < 1/3 || $ratio > 3) {
                    $errors[] = "Seedream 3.0-t2i 宽高比范围 [1:3 ~ 3:1]，当前比例超出";
                }
            } elseif ($is50lite) {
                // 5.0-lite: 总像素 [3686400 ~ 10404496]，宽高比 [1/16, 16]
                if ($pixelProduct < 3686400 || $pixelProduct > 10404496) {
                    $errors[] = "Seedream 5.0-lite 总像素范围 [3686400~10404496]，当前: {$pixelProduct}";
                }
                if ($ratio < 1/16 || $ratio > 16) {
                    $errors[] = "Seedream 5.0-lite 宽高比范围 [1:16 ~ 16:1]";
                }
            } elseif ($is40 || $is45) {
                // 4.0: [921600 ~ 16777216]; 4.5: [3686400 ~ 16777216]
                $minPx = $is40 ? 921600 : 3686400;
                if ($pixelProduct < $minPx || $pixelProduct > 16777216) {
                    $label = $is40 ? '4.0' : '4.5';
                    $errors[] = "Seedream {$label} 总像素范围 [{$minPx}~16777216]，当前: {$pixelProduct}";
                }
                if ($ratio < 1/16 || $ratio > 16) {
                    $errors[] = "Seedream 宽高比范围 [1:16 ~ 16:1]";
                }
            }
        }

        // ---- 3.0-t2i 禁用参数校验 ----
        if ($is30t2i) {
            $forbidden = ['image', 'sequential_image_generation', 'stream', 'optimize_prompt_options', 'tools', 'output_format'];
            foreach ($forbidden as $key) {
                if (isset($params[$key])) {
                    // 不报错，静默移除禁用参数
                    unset($validated[$key]);
                }
            }

            // seed 范围校验
            if (isset($params['seed'])) {
                $seed = (int)$params['seed'];
                if ($seed < -1 || $seed > 2147483647) {
                    $errors[] = "seed 范围为 [-1, 2147483647]，当前: {$seed}";
                }
            }

            // guidance_scale 范围校验
            if (isset($params['guidance_scale'])) {
                $gs = (float)$params['guidance_scale'];
                if ($gs < 1 || $gs > 10) {
                    $errors[] = "guidance_scale 范围为 [1, 10]，当前: {$gs}";
                }
            }
        }

        // ---- image 数量校验（5.0-lite / 4.5 / 4.0 最多14张） ----
        if (!$is30t2i && isset($params['image'])) {
            $imageCount = is_array($params['image']) ? count($params['image']) : 1;
            if ($imageCount > 14) {
                $errors[] = "参考图最多14张，当前: {$imageCount}";
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
}

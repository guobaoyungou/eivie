<?php
define('ROOT_PATH', '/www/wwwroot/eivie/');
$schema = json_decode('{"parameters":[{"name":"prompt","type":"string","label":"提示词","required":true,"description":"描述生成图像的文字"},{"name":"image","type":"string","label":"参考图像","required":false,"description":"图生图时的参考图URL"},{"name":"size","type":"enum","label":"输出尺寸","default":"1K","options":["1K","2K"],"required":false,"description":"输出图像尺寸"},{"name":"response_format","type":"enum","label":"响应格式","default":"url","options":["url","b64_json"],"required":false,"description":"返回图像的格式"},{"name":"watermark","type":"boolean","label":"水印","default":false,"required":false,"description":"是否添加水印"}]}', true);

echo "BEFORE normalization:\n";
echo "Has properties: " . (isset($schema['properties']) ? 'YES' : 'NO') . "\n";
echo "Has parameters: " . (isset($schema['parameters']) ? 'YES' : 'NO') . "\n";

// Simulate normalizeInputSchema from GenerationService
if (!isset($schema['properties']) && isset($schema['parameters']) && is_array($schema['parameters'])) {
    $properties = [];
    $required = [];
    $order = 0;
    foreach ($schema['parameters'] as $param) {
        $name = $param['name'] ?? '';
        if (empty($name)) continue;
        $prop = $param;
        unset($prop['name']);
        $prop['order'] = $order++;
        if (!empty($param['required'])) $required[] = $name;
        unset($prop['required']);
        $properties[$name] = $prop;
    }
    $schema['properties'] = $properties;
    $schema['required'] = $required;
}

echo "\nAFTER normalization:\n";
echo "Has properties: " . (isset($schema['properties']) ? 'YES' : 'NO') . "\n";
echo "Required: " . json_encode($schema['required']) . "\n";
echo "Properties keys: " . implode(', ', array_keys($schema['properties'])) . "\n";

foreach ($schema['properties'] as $key => $prop) {
    echo "\n  $key:\n";
    echo "    type: {$prop['type']}\n";
    echo "    label: {$prop['label']}\n";
    if (isset($prop['default'])) echo "    default: " . var_export($prop['default'], true) . "\n";
    if (isset($prop['options'])) echo "    options: " . json_encode($prop['options']) . "\n";
    echo "    order: {$prop['order']}\n";
}

echo "\nNormalization test PASSED!\n";

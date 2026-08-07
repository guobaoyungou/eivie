<?php
/**
 * 豆包 Seedream 图像生成模型 - 数据库迁移脚本
 * 
 * 功能：
 * 1. 修正 Seedream 5.0-lite（ID=1）的 capability_tags、input_schema、limits_config
 * 2. 修正 Seedream 4.5（ID=2）的 capability_tags、input_schema、limits_config
 * 3. 修正 Seedream 4.0（ID=6）的 capability_tags、input_schema、limits_config、model_name
 * 4. 新增 Seedream 3.0-t2i 模型记录
 * 
 * 执行方式: php migrate_seedream_models.php
 * 
 * @date 2026-04-16
 */

$config = include(__DIR__ . '/config.php');

$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "豆包 Seedream 模型注册数据完善 - 数据库迁移\n";
echo "========================================\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4",
        $username, $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ 数据库连接成功\n\n";

    $time = time();
    $modelTable = $prefix . 'model_info';
    $providerTable = $prefix . 'model_provider';
    $typeTable = $prefix . 'model_type';

    // 获取 volcengine provider_id 和 image_generation type_id
    $stmt = $pdo->prepare("SELECT id FROM `{$providerTable}` WHERE `provider_code` = 'volcengine'");
    $stmt->execute();
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$provider) { throw new Exception("供应商 'volcengine' 不存在"); }
    $providerId = (int)$provider['id'];
    echo "✓ 供应商 volcengine (ID={$providerId})\n";

    $stmt = $pdo->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'image_generation'");
    $stmt->execute();
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$type) { throw new Exception("模型类型 'image_generation' 不存在"); }
    $typeId = (int)$type['id'];
    echo "✓ 模型类型 image_generation (ID={$typeId})\n\n";

    // ============================================================
    // 1. 修正 Seedream 5.0-lite (model_code: doubao-seedream-5-0-260128)
    // ============================================================
    echo "1. 修正 Seedream 5.0-lite...\n";

    $inputSchema50 = json_encode([
        'parameters' => [
            ['name' => 'prompt', 'type' => 'string', 'label' => '提示词', 'required' => true, 'description' => '图像描述文字，建议不超过300汉字'],
            ['name' => 'image', 'type' => 'mixed', 'label' => '参考图像', 'required' => false, 'description' => '支持单图URL字符串或多图数组(最多14张)', 'max_count' => 14],
            ['name' => 'size', 'type' => 'string', 'label' => '输出尺寸', 'required' => false, 'default' => '2048x2048', 'description' => '支持分辨率档(2K/3K)或像素值(宽x高)，总像素[3686400~10404496]，宽高比[1/16,16]'],
            ['name' => 'sequential_image_generation', 'type' => 'enum', 'label' => '组图控制', 'required' => false, 'default' => 'disabled', 'options' => ['auto', 'disabled'], 'description' => '组图生成开关：auto=自动组图，disabled=关闭'],
            ['name' => 'sequential_image_generation_options', 'type' => 'object', 'label' => '组图选项', 'required' => false, 'default' => ['max_images' => 15], 'description' => '组图最大数量[1-15]'],
            ['name' => 'stream', 'type' => 'boolean', 'label' => '流式输出', 'required' => false, 'default' => false, 'description' => '是否启用SSE流式输出'],
            ['name' => 'response_format', 'type' => 'enum', 'label' => '返回格式', 'required' => false, 'default' => 'url', 'options' => ['url', 'b64_json'], 'description' => '图片返回格式'],
            ['name' => 'watermark', 'type' => 'boolean', 'label' => '水印', 'required' => false, 'default' => false, 'description' => '是否添加水印'],
            ['name' => 'optimize_prompt_options', 'type' => 'object', 'label' => '提示词优化', 'required' => false, 'default' => ['mode' => 'standard'], 'description' => '提示词优化模式：standard'],
            ['name' => 'tools', 'type' => 'array', 'label' => '工具配置', 'required' => false, 'description' => '工具列表，仅支持 type: web_search（联网搜索）'],
            ['name' => 'output_format', 'type' => 'enum', 'label' => '输出文件格式', 'required' => false, 'default' => 'jpeg', 'options' => ['png', 'jpeg'], 'description' => '输出图片文件格式']
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);

    $capabilityTags50 = json_encode([
        '文生图', '图生图', '单图生图', '多图生图', '组图生成', '流式输出',
        '联网搜索', '输出格式可选', '2K-3K分辨率', '提示词优化',
        '多图融合', '多图生成',
        'text2image', 'image2image', 'batch_generation', 'multi_input'
    ], JSON_UNESCAPED_UNICODE);

    $limitsConfig50 = json_encode([
        'timeout' => 120,
        'min_pixel_product' => 3686400,
        'max_pixel_product' => 10404496,
        'aspect_ratio_range' => [0.0625, 16],
        'max_input_images' => 14,
        'max_output_images' => 15,
        'stream_support' => true,
        'multi_image_support' => true
    ], JSON_UNESCAPED_UNICODE);

    $pricingConfig50 = json_encode([
        'currency' => 'CNY',
        'billing_mode' => 'per_image',
        'cost_price' => 0.15,
        'suggested_price' => 1.00
    ], JSON_UNESCAPED_UNICODE);

    $outputSchema50 = json_encode([
        'type' => 'image',
        'fields' => [
            ['path' => '$.data[0].url', 'label' => '图片URL', 'priority' => 'primary'],
            ['path' => '$.data[0].b64_json', 'label' => '图片Base64', 'priority' => 'fallback'],
            ['path' => '$.data[0].size', 'label' => '图片尺寸', 'priority' => 'auxiliary'],
            ['path' => '$.error.code', 'label' => '错误码', 'priority' => 'error'],
            ['path' => '$.error.message', 'label' => '错误信息', 'priority' => 'error'],
            ['path' => '$.usage.generated_images', 'label' => '生成张数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.output_tokens', 'label' => '输出token数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.tool_usage.web_search', 'label' => '联网搜索次数', 'priority' => 'auxiliary']
        ]
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("UPDATE `{$modelTable}` SET 
        `model_name` = '豆包SeeDream 5.0 Lite',
        `capability_tags` = ?, `input_schema` = ?, `output_schema` = ?,
        `limits_config` = ?, `pricing_config` = ?, `update_time` = ?
        WHERE `model_code` = 'doubao-seedream-5-0-260128'");
    $stmt->execute([$capabilityTags50, $inputSchema50, $outputSchema50, $limitsConfig50, $pricingConfig50, $time]);
    echo "   ✓ Seedream 5.0-lite 已修正 (affected={$stmt->rowCount()})\n";

    // ============================================================
    // 2. 修正 Seedream 4.5 (model_code: doubao-seedream-4-5-251128)
    // ============================================================
    echo "\n2. 修正 Seedream 4.5...\n";

    $inputSchema45 = json_encode([
        'parameters' => [
            ['name' => 'prompt', 'type' => 'string', 'label' => '提示词', 'required' => true, 'description' => '图像描述文字，建议不超过300汉字'],
            ['name' => 'image', 'type' => 'mixed', 'label' => '参考图像', 'required' => false, 'description' => '支持单图URL字符串或多图数组(最多14张)', 'max_count' => 14],
            ['name' => 'size', 'type' => 'string', 'label' => '输出尺寸', 'required' => false, 'default' => '2048x2048', 'description' => '支持分辨率档(2K/4K)或像素值(宽x高)，总像素[3686400~16777216]，宽高比[1/16,16]'],
            ['name' => 'sequential_image_generation', 'type' => 'enum', 'label' => '组图控制', 'required' => false, 'default' => 'disabled', 'options' => ['auto', 'disabled'], 'description' => '组图生成开关：auto=自动组图，disabled=关闭'],
            ['name' => 'sequential_image_generation_options', 'type' => 'object', 'label' => '组图选项', 'required' => false, 'default' => ['max_images' => 15], 'description' => '组图最大数量[1-15]'],
            ['name' => 'stream', 'type' => 'boolean', 'label' => '流式输出', 'required' => false, 'default' => false, 'description' => '是否启用SSE流式输出'],
            ['name' => 'response_format', 'type' => 'enum', 'label' => '返回格式', 'required' => false, 'default' => 'url', 'options' => ['url', 'b64_json'], 'description' => '图片返回格式'],
            ['name' => 'watermark', 'type' => 'boolean', 'label' => '水印', 'required' => false, 'default' => false, 'description' => '是否添加水印'],
            ['name' => 'optimize_prompt_options', 'type' => 'object', 'label' => '提示词优化', 'required' => false, 'default' => ['mode' => 'standard'], 'description' => '提示词优化模式：standard']
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);

    $capabilityTags45 = json_encode([
        '文生图', '图生图', '单图生图', '多图生图', '组图生成', '流式输出',
        '2K-4K分辨率', '提示词优化',
        '多图融合', '多图生成',
        'text2image', 'image2image', 'batch_generation', 'multi_input'
    ], JSON_UNESCAPED_UNICODE);

    $limitsConfig45 = json_encode([
        'timeout' => 120,
        'min_pixel_product' => 3686400,
        'max_pixel_product' => 16777216,
        'aspect_ratio_range' => [0.0625, 16],
        'max_input_images' => 14,
        'max_output_images' => 15,
        'stream_support' => true,
        'multi_image_support' => true
    ], JSON_UNESCAPED_UNICODE);

    $pricingConfig45 = json_encode([
        'currency' => 'CNY',
        'billing_mode' => 'per_image',
        'cost_price' => 0.25,
        'suggested_price' => 2.00
    ], JSON_UNESCAPED_UNICODE);

    $outputSchema45 = json_encode([
        'type' => 'image',
        'fields' => [
            ['path' => '$.data[0].url', 'label' => '图片URL', 'priority' => 'primary'],
            ['path' => '$.data[0].b64_json', 'label' => '图片Base64', 'priority' => 'fallback'],
            ['path' => '$.data[0].size', 'label' => '图片尺寸', 'priority' => 'auxiliary'],
            ['path' => '$.error.code', 'label' => '错误码', 'priority' => 'error'],
            ['path' => '$.error.message', 'label' => '错误信息', 'priority' => 'error'],
            ['path' => '$.usage.generated_images', 'label' => '生成张数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.output_tokens', 'label' => '输出token数', 'priority' => 'auxiliary']
        ]
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("UPDATE `{$modelTable}` SET 
        `capability_tags` = ?, `input_schema` = ?, `output_schema` = ?,
        `limits_config` = ?, `pricing_config` = ?, `update_time` = ?
        WHERE `model_code` = 'doubao-seedream-4-5-251128'");
    $stmt->execute([$capabilityTags45, $inputSchema45, $outputSchema45, $limitsConfig45, $pricingConfig45, $time]);
    echo "   ✓ Seedream 4.5 已修正 (affected={$stmt->rowCount()})\n";

    // ============================================================
    // 3. 修正 Seedream 4.0 (model_code: doubao-seedream-4-0-250828)
    // ============================================================
    echo "\n3. 修正 Seedream 4.0...\n";

    $inputSchema40 = json_encode([
        'parameters' => [
            ['name' => 'prompt', 'type' => 'string', 'label' => '提示词', 'required' => true, 'description' => '图像描述文字，建议不超过300汉字'],
            ['name' => 'image', 'type' => 'mixed', 'label' => '参考图像', 'required' => false, 'description' => '支持单图URL字符串或多图数组(最多14张)', 'max_count' => 14],
            ['name' => 'size', 'type' => 'string', 'label' => '输出尺寸', 'required' => false, 'default' => '2048x2048', 'description' => '支持分辨率档(1K/2K/4K)或像素值(宽x高)，总像素[921600~16777216]，宽高比[1/16,16]'],
            ['name' => 'sequential_image_generation', 'type' => 'enum', 'label' => '组图控制', 'required' => false, 'default' => 'disabled', 'options' => ['auto', 'disabled'], 'description' => '组图生成开关：auto=自动组图，disabled=关闭'],
            ['name' => 'sequential_image_generation_options', 'type' => 'object', 'label' => '组图选项', 'required' => false, 'default' => ['max_images' => 15], 'description' => '组图最大数量[1-15]'],
            ['name' => 'stream', 'type' => 'boolean', 'label' => '流式输出', 'required' => false, 'default' => false, 'description' => '是否启用SSE流式输出'],
            ['name' => 'response_format', 'type' => 'enum', 'label' => '返回格式', 'required' => false, 'default' => 'url', 'options' => ['url', 'b64_json'], 'description' => '图片返回格式'],
            ['name' => 'watermark', 'type' => 'boolean', 'label' => '水印', 'required' => false, 'default' => false, 'description' => '是否添加水印'],
            ['name' => 'optimize_prompt_options', 'type' => 'object', 'label' => '提示词优化', 'required' => false, 'default' => ['mode' => 'standard'], 'description' => '提示词优化模式：standard / fast']
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);

    $capabilityTags40 = json_encode([
        '文生图', '图生图', '单图生图', '多图生图', '组图生成', '流式输出',
        '1K-4K分辨率', '提示词优化',
        '多图融合', '多图生成',
        'text2image', 'image2image', 'batch_generation', 'multi_input'
    ], JSON_UNESCAPED_UNICODE);

    $limitsConfig40 = json_encode([
        'timeout' => 120,
        'min_pixel_product' => 921600,
        'max_pixel_product' => 16777216,
        'aspect_ratio_range' => [0.0625, 16],
        'max_input_images' => 14,
        'max_output_images' => 15,
        'stream_support' => true,
        'multi_image_support' => true
    ], JSON_UNESCAPED_UNICODE);

    $pricingConfig40 = json_encode([
        'currency' => 'CNY',
        'billing_mode' => 'per_image',
        'cost_price' => 0.20,
        'suggested_price' => 1.50
    ], JSON_UNESCAPED_UNICODE);

    $outputSchema40 = json_encode([
        'type' => 'image',
        'fields' => [
            ['path' => '$.data[0].url', 'label' => '图片URL', 'priority' => 'primary'],
            ['path' => '$.data[0].b64_json', 'label' => '图片Base64', 'priority' => 'fallback'],
            ['path' => '$.data[0].size', 'label' => '图片尺寸', 'priority' => 'auxiliary'],
            ['path' => '$.error.code', 'label' => '错误码', 'priority' => 'error'],
            ['path' => '$.error.message', 'label' => '错误信息', 'priority' => 'error'],
            ['path' => '$.usage.generated_images', 'label' => '生成张数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.output_tokens', 'label' => '输出token数', 'priority' => 'auxiliary']
        ]
    ], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("UPDATE `{$modelTable}` SET 
        `model_name` = '豆包SeeDream 4.0', `model_version` = 'v4.0',
        `capability_tags` = ?, `input_schema` = ?, `output_schema` = ?,
        `limits_config` = ?, `pricing_config` = ?, `update_time` = ?
        WHERE `model_code` = 'doubao-seedream-4-0-250828'");
    $stmt->execute([$capabilityTags40, $inputSchema40, $outputSchema40, $limitsConfig40, $pricingConfig40, $time]);
    echo "   ✓ Seedream 4.0 已修正 (affected={$stmt->rowCount()})\n";

    // ============================================================
    // 4. 新增 Seedream 3.0-t2i
    // ============================================================
    echo "\n4. 新增 Seedream 3.0-t2i...\n";

    $modelCode30 = 'doubao-seedream-3-0-t2i';

    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
    $stmt->execute([$modelCode30]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $inputSchema30 = json_encode([
        'parameters' => [
            ['name' => 'prompt', 'type' => 'string', 'label' => '提示词', 'required' => true, 'description' => '图像描述文字'],
            ['name' => 'size', 'type' => 'string', 'label' => '输出尺寸', 'required' => false, 'default' => '1024x1024', 'description' => '像素范围 [512x512, 2048x2048]，宽高比[1/3, 3]'],
            ['name' => 'seed', 'type' => 'integer', 'label' => '随机种子', 'required' => false, 'default' => -1, 'min' => -1, 'max' => 2147483647, 'description' => '随机种子，-1为随机'],
            ['name' => 'guidance_scale', 'type' => 'float', 'label' => '文本权重', 'required' => false, 'default' => 2.5, 'min' => 1, 'max' => 10, 'description' => '文本对生成图像的影响程度[1-10]'],
            ['name' => 'response_format', 'type' => 'enum', 'label' => '返回格式', 'required' => false, 'default' => 'url', 'options' => ['url', 'b64_json'], 'description' => '图片返回格式'],
            ['name' => 'watermark', 'type' => 'boolean', 'label' => '水印', 'required' => false, 'default' => false, 'description' => '是否添加水印']
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);

    $outputSchema30 = json_encode([
        'type' => 'image',
        'fields' => [
            ['path' => '$.data[0].url', 'label' => '图片URL', 'priority' => 'primary'],
            ['path' => '$.data[0].b64_json', 'label' => '图片Base64', 'priority' => 'fallback'],
            ['path' => '$.error.code', 'label' => '错误码', 'priority' => 'error'],
            ['path' => '$.error.message', 'label' => '错误信息', 'priority' => 'error'],
            ['path' => '$.usage.generated_images', 'label' => '生成张数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.output_tokens', 'label' => '输出token数', 'priority' => 'auxiliary']
        ]
    ], JSON_UNESCAPED_UNICODE);

    $capabilityTags30 = json_encode(['文生图', '轻量级', '低成本', 'text2image', 'batch_generation', '多图生成'], JSON_UNESCAPED_UNICODE);

    $limitsConfig30 = json_encode([
        'timeout' => 60,
        'min_resolution' => '512x512',
        'max_resolution' => '2048x2048',
        'aspect_ratio_range' => [0.333, 3],
        'max_input_images' => 0,
        'max_output_images' => 1,
        'stream_support' => false,
        'multi_image_support' => false
    ], JSON_UNESCAPED_UNICODE);

    $pricingConfig30 = json_encode([
        'currency' => 'CNY',
        'billing_mode' => 'per_image',
        'cost_price' => 0.08,
        'suggested_price' => 0.50
    ], JSON_UNESCAPED_UNICODE);

    $endpointUrl = 'https://ark.cn-beijing.volces.com/api/v3/images/generations';

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE `{$modelTable}` SET 
            `model_name` = '豆包SeeDream 3.0 (文生图)', `model_version` = 'v3.0-t2i',
            `provider_id` = ?, `type_id` = ?, `endpoint_url` = ?,
            `task_type` = 'sync', `capability_tags` = ?, `input_schema` = ?, `output_schema` = ?,
            `limits_config` = ?, `pricing_config` = ?, `is_active` = 1, `update_time` = ?
            WHERE `model_code` = ?");
        $stmt->execute([$providerId, $typeId, $endpointUrl, $capabilityTags30, $inputSchema30, $outputSchema30, $limitsConfig30, $pricingConfig30, $time, $modelCode30]);
        echo "   ✓ Seedream 3.0-t2i 已更新 (ID={$existing['id']})\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$modelTable}` 
            (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`,
             `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`,
             `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`)
            VALUES 
            (0, ?, ?, ?, '豆包SeeDream 3.0 (文生图)', 'v3.0-t2i', 
             'Seedream 3.0 文生图模型，轻量级、低成本，支持seed和guidance_scale参数控制。仅支持文生图，不支持图生图/组图/流式输出。',
             ?, 'sync', ?, ?, ?, ?, ?, 1, 0, 0, 40, ?, ?)");
        $stmt->execute([$providerId, $typeId, $modelCode30, $endpointUrl, $inputSchema30, $outputSchema30, $pricingConfig30, $limitsConfig30, $capabilityTags30, $time, $time]);
        $newId = $pdo->lastInsertId();
        echo "   ✓ Seedream 3.0-t2i 创建成功 (ID={$newId})\n";
    }

    // ============================================================
    // 5. 验证
    // ============================================================
    echo "\n5. 验证数据完整性...\n";
    echo "----------------------------------------\n";
    $stmt = $pdo->query("SELECT m.id, m.model_code, m.model_name, m.model_version, m.task_type, m.is_active,
                   p.provider_name, t.type_name,
                   LENGTH(m.capability_tags) as tags_len,
                   LENGTH(m.input_schema) as input_len
            FROM `{$modelTable}` m
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id
            WHERE m.model_code LIKE 'doubao-seedream%'
            ORDER BY m.id");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_active'] ? '✓' : '✗';
        echo "   {$status} [{$row['id']}] {$row['model_code']}\n";
        echo "     名称: {$row['model_name']} ({$row['model_version']})\n";
        echo "     供应商: {$row['provider_name']} | 类型: {$row['type_name']}\n";
        echo "     任务类型: {$row['task_type']} | tags长度: {$row['tags_len']} | input_schema长度: {$row['input_len']}\n";
    }

    echo "----------------------------------------\n";
    echo "\n✅ 豆包 Seedream 模型注册数据迁移完成！\n";
    echo "========================================\n\n";

} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

<?php
/**
 * 酷爱API中转平台 - GPT-Image-2 模型注册 - 数据库迁移脚本
 * 
 * 功能：
 * 1. 激活 OpenAI 供应商
 * 2. 注册 gpt-image-2 模型记录
 * 
 * 执行方式: php migrate_kuai_host_gpt_image.php
 * 
 * 使用说明：
 * - 注册完成后，在「API Key管理」新增配置：供应商选 OpenAI，填入酷爱API Key，
 *   自定义接口地址填 https://api.kuai.host/v1/images/generations，认证方式选 Bearer Token
 * 
 * @date 2026-04-28
 */

$config = include(__DIR__ . '/config.php');

$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "酷爱API - GPT-Image-2 模型注册\n";
echo "========================================\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4",
        $username, $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ 数据库连接成功\n\n";

    $time = time();
    $modelTable    = $prefix . 'model_info';
    $providerTable = $prefix . 'model_provider';
    $typeTable     = $prefix . 'model_type';

    // ============================================================
    // 1. 激活 OpenAI 供应商
    // ============================================================
    echo "1. 激活 OpenAI 供应商...\n";

    $stmt = $pdo->prepare("SELECT id, status FROM `{$providerTable}` WHERE `provider_code` = 'openai'");
    $stmt->execute();
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$provider) {
        throw new Exception("供应商 'openai' 不存在，请先创建");
    }
    $providerId = (int)$provider['id'];

    if ((int)$provider['status'] !== 1) {
        $stmt = $pdo->prepare("UPDATE `{$providerTable}` SET `status` = 1, `update_time` = ? WHERE `id` = ?");
        $stmt->execute([$time, $providerId]);
        echo "   ✓ OpenAI 供应商已激活 (ID={$providerId})\n";
    } else {
        echo "   ✓ OpenAI 供应商已是激活状态 (ID={$providerId})\n";
    }

    // 获取 image_generation type_id
    $stmt = $pdo->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'image_generation'");
    $stmt->execute();
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$type) {
        throw new Exception("模型类型 'image_generation' 不存在");
    }
    $typeId = (int)$type['id'];
    echo "   ✓ 模型类型 image_generation (ID={$typeId})\n\n";

    // ============================================================
    // 2. 注册 gpt-image-2 模型
    // ============================================================
    echo "2. 注册 gpt-image-2 模型...\n";

    $modelCode = 'gpt-image-2';

    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
    $stmt->execute([$modelCode]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    // input_schema: GPT-Image-2 支持的参数（与 gpt-image-1 一致）
    $inputSchema = json_encode([
        'parameters' => [
            ['name' => 'prompt', 'type' => 'string', 'label' => '提示词', 'required' => true, 'description' => '图像描述文字，支持中英文'],
            ['name' => 'image', 'type' => 'mixed', 'label' => '参考图像', 'required' => false, 'description' => '参考图片URL，传入后自动使用图片编辑模式（/v1/images/edits）', 'max_count' => 10],
            ['name' => 'size', 'type' => 'enum', 'label' => '输出尺寸', 'required' => false, 'default' => 'auto', 'options' => ['1024x1024', '1536x1024', '1024x1536', 'auto'], 'description' => '图片尺寸: 1024x1024(正方形)、1536x1024(横版)、1024x1536(竖版)、auto(自动)'],
            ['name' => 'n', 'type' => 'integer', 'label' => '生成数量', 'required' => false, 'default' => 1, 'min' => 1, 'max' => 10, 'description' => '生成图片数量(1-10)'],
            ['name' => 'quality', 'type' => 'enum', 'label' => '生成质量', 'required' => false, 'default' => 'auto', 'options' => ['auto', 'low', 'medium', 'high'], 'description' => '图片质量: auto(自动)、low(快速)、medium(均衡)、high(高质量)'],
            ['name' => 'output_format', 'type' => 'enum', 'label' => '输出格式', 'required' => false, 'default' => 'png', 'options' => ['png', 'jpeg', 'webp'], 'description' => '输出图片格式'],
            ['name' => 'background', 'type' => 'enum', 'label' => '背景模式', 'required' => false, 'default' => 'auto', 'options' => ['auto', 'transparent', 'opaque'], 'description' => '背景模式: auto(自动)、transparent(透明,仅png/webp)、opaque(不透明)'],
            ['name' => 'moderation', 'type' => 'enum', 'label' => '审核强度', 'required' => false, 'default' => 'auto', 'options' => ['auto', 'low'], 'description' => '内容审核强度']
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);

    // output_schema
    $outputSchema = json_encode([
        'type' => 'image',
        'fields' => [
            ['path' => '$.data[0].b64_json', 'label' => '图片Base64', 'priority' => 'primary'],
            ['path' => '$.data[0].url', 'label' => '图片URL', 'priority' => 'fallback'],
            ['path' => '$.data[0].revised_prompt', 'label' => '修订后的提示词', 'priority' => 'auxiliary'],
            ['path' => '$.error.message', 'label' => '错误信息', 'priority' => 'error'],
            ['path' => '$.usage.total_tokens', 'label' => '总token数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.input_tokens', 'label' => '输入token数', 'priority' => 'auxiliary'],
            ['path' => '$.usage.output_tokens', 'label' => '输出token数', 'priority' => 'auxiliary']
        ]
    ], JSON_UNESCAPED_UNICODE);

    // capability_tags
    $capabilityTags = json_encode([
        '文生图', '图生图', '单图生图', '多图生图', '组图生成', '多图生成',
        '多图融合', '多图输入', '透明背景', '高质量', 'GPT-Image-2',
        'text2image', 'image2image', 'batch_generation', 'multi_input'
    ], JSON_UNESCAPED_UNICODE);

    // limits_config
    $limitsConfig = json_encode([
        'timeout' => 300,
        'max_input_images' => 10,
        'max_output_images' => 10,
        'supported_sizes' => ['1024x1024', '1536x1024', '1024x1536', 'auto'],
        'stream_support' => false,
        'multi_image_support' => true,
        'transparent_background' => true
    ], JSON_UNESCAPED_UNICODE);

    // pricing_config
    $pricingConfig = json_encode([
        'currency' => 'USD',
        'billing_mode' => 'per_token',
        'cost_per_1k_input_tokens' => 0.01,
        'cost_per_1k_output_tokens' => 0.04,
        'note' => 'low≈$0.02/张, medium≈$0.07/张, high≈$0.19/张(1024x1024)'
    ], JSON_UNESCAPED_UNICODE);

    $endpointUrl = 'https://api.openai.com/v1/images/generations';
    $description = 'OpenAI GPT-Image-2 模型，支持文生图和图片编辑。支持4种尺寸、4种质量等级、透明背景、多种输出格式(png/jpeg/webp)。可通过酷爱API中转平台调用。';

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE `{$modelTable}` SET 
            `model_name` = 'GPT-Image-2', `model_version` = 'gpt-image-2',
            `description` = ?,
            `provider_id` = ?, `type_id` = ?, `endpoint_url` = ?,
            `task_type` = 'sync', `capability_tags` = ?, `input_schema` = ?, `output_schema` = ?,
            `limits_config` = ?, `pricing_config` = ?, `is_active` = 1, `update_time` = ?
            WHERE `model_code` = ?");
        $stmt->execute([$description, $providerId, $typeId, $endpointUrl, $capabilityTags, $inputSchema, $outputSchema, $limitsConfig, $pricingConfig, $time, $modelCode]);
        echo "   ✓ gpt-image-2 已更新 (ID={$existing['id']})\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$modelTable}` 
            (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`,
             `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`,
             `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`)
            VALUES 
            (0, ?, ?, ?, 'GPT-Image-2', 'gpt-image-2', ?,
             ?, 'sync', ?, ?, ?, ?, ?, 1, 1, 0, 4, ?, ?)");
        $stmt->execute([$providerId, $typeId, $modelCode, $description, $endpointUrl, $inputSchema, $outputSchema, $pricingConfig, $limitsConfig, $capabilityTags, $time, $time]);
        $newId = $pdo->lastInsertId();
        echo "   ✓ gpt-image-2 创建成功 (ID={$newId})\n";
    }

    // ============================================================
    // 3. 验证
    // ============================================================
    echo "\n3. 验证数据...\n";
    echo "----------------------------------------\n";

    $stmt = $pdo->prepare("SELECT m.id, m.model_code, m.model_name, m.model_version, m.task_type, m.is_active,
                   p.provider_name, p.status as provider_status, t.type_name,
                   LENGTH(m.capability_tags) as tags_len,
                   LENGTH(m.input_schema) as input_len
            FROM `{$modelTable}` m
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id
            WHERE m.model_code = ?");
    $stmt->execute([$modelCode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $status = $row['is_active'] ? '✓' : '✗';
        $providerStatus = $row['provider_status'] ? '激活' : '未激活';
        echo "   {$status} [{$row['id']}] {$row['model_code']}\n";
        echo "     名称: {$row['model_name']} ({$row['model_version']})\n";
        echo "     供应商: {$row['provider_name']} ({$providerStatus}) | 类型: {$row['type_name']}\n";
        echo "     任务类型: {$row['task_type']} | tags长度: {$row['tags_len']} | input_schema长度: {$row['input_len']}\n";
    } else {
        echo "   ✗ 模型记录未找到!\n";
    }

    echo "----------------------------------------\n";
    echo "\n✅ 酷爱API - GPT-Image-2 模型注册完成！\n";
    echo "\n📋 使用说明：\n";
    echo "   1. 在「API Key管理」新增配置\n";
    echo "   2. 供应商: OpenAI\n";
    echo "   3. API Key: 填入酷爱API的 API Key\n";
    echo "   4. 自定义接口地址: https://api.kuai.host/v1/images/generations\n";
    echo "   5. 认证方式: Bearer Token\n";
    echo "========================================\n\n";

} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

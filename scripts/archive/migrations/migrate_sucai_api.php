<?php
/**
 * 速创API (wuyinkeji) 供应商和模型接入 - 数据库迁移脚本
 *
 * 功能：
 * 1. 创建速创供应商 (provider_code: sucai)
 * 2. 注册 GPT-Image 模型 (model_code: sucai-gpt-image)
 *
 * 执行方式: php migrate_sucai_api.php
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
echo "速创API (wuyinkeji) 接入 - 数据库迁移\n";
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
    // 1. 创建/更新速创供应商
    // ============================================================
    echo "1. 创建速创API供应商...\n";

    $providerCode = 'sucai';

    // 检查供应商表结构
    $stmt = $pdo->query("DESCRIBE `{$providerTable}`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   供应商表字段: " . implode(', ', array_slice($columns, 0, 10)) . "...\n";

    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT id, status FROM `{$providerTable}` WHERE `provider_code` = ?");
    $stmt->execute([$providerCode]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);

    // auth_config 存储认证配置（auth_mode等）
    $authConfig = json_encode([
        'auth_mode' => 'key_only',  // 速创API使用纯Key认证，无Bearer前缀
        'key_prefix' => '',         // 不需要前缀
        'key_location' => 'header', // Authorization Header
    ], JSON_UNESCAPED_UNICODE);

    // 供应商数据 - 根据实际表结构构建
    $providerData = [
        'aid' => 0,
        'provider_code' => $providerCode,
        'provider_name' => '速创API',
        'logo' => '',
        'website' => 'https://wuyinkeji.com',
        'api_doc_url' => '',
        'description' => '速创API中转平台，提供OpenAI GPT-Image等模型的高效中转服务，支持异步任务提交和结果查询。',
        'auth_config' => $authConfig,
        'is_system' => 1,
        'status' => 1,
        'sort' => 10,
        'create_time' => $time,
        'update_time' => $time,
    ];

    if ($provider) {
        $providerId = (int)$provider['id'];
        // 构建更新SQL
        $setClauses = [];
        foreach ($providerData as $k => $v) {
            if ($k !== 'provider_code' && $k !== 'aid') {
                $setClauses[] = "`{$k}` = ?";
            }
        }
        $setClauses[] = "`update_time` = ?";
        $sql = "UPDATE `{$providerTable}` SET " . implode(', ', $setClauses) . " WHERE `provider_code` = ?";
        $values = array_values(array_filter($providerData, fn($k) => $k !== 'provider_code' && $k !== 'aid', ARRAY_FILTER_USE_KEY));
        $values[] = $time;
        $values[] = $providerCode;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        echo "   ✓ 速创供应商已更新 (ID={$providerId})\n";
    } else {
        $cols = array_keys($providerData);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO `{$providerTable}` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($providerData));
        $providerId = $pdo->lastInsertId();
        echo "   ✓ 速创供应商创建成功 (ID={$providerId})\n";
    }

    // ============================================================
    // 2. 获取 image_generation type_id
    // ============================================================
    echo "\n2. 获取模型类型...\n";

    $stmt = $pdo->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'image_generation'");
    $stmt->execute();
    $type = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$type) {
        throw new Exception("模型类型 'image_generation' 不存在");
    }
    $typeId = (int)$type['id'];
    echo "   ✓ 模型类型 image_generation (ID={$typeId})\n";

    // ============================================================
    // 3. 注册速创GPT-Image模型
    // ============================================================
    echo "\n3. 注册速创GPT-Image模型...\n";

    $modelCode = 'sucai-gpt-image';

    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
    $stmt->execute([$modelCode]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    // input_schema
    $inputSchema = json_encode([
        'parameters' => [
            ['name' => 'prompt', 'type' => 'string', 'label' => '提示词', 'required' => true, 'description' => '图像描述文字，支持中英文'],
            ['name' => 'image', 'type' => 'mixed', 'label' => '参考图像', 'required' => false, 'description' => '参考图片URL，传入后自动使用图片编辑模式', 'max_count' => 10],
            ['name' => 'size', 'type' => 'enum', 'label' => '输出尺寸', 'required' => false, 'default' => 'auto', 'options' => ['auto', '1:1', '3:2', '2:3', '16:9', '9:16'], 'description' => '图片尺寸比例'],
            ['name' => 'n', 'type' => 'integer', 'label' => '生成数量', 'required' => false, 'default' => 1, 'min' => 1, 'max' => 10, 'description' => '生成图片数量(1-10)'],
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);

    // output_schema - 速创API返回格式
    $outputSchema = json_encode([
        'type' => 'image',
        'fields' => [
            // 提交接口返回格式
            ['path' => '$.data.id', 'label' => '任务ID', 'priority' => 'async_task_id'],
            ['path' => '$.data.task_id', 'label' => '任务ID(查询)', 'priority' => 'async_task_id'],
            // 查询结果返回格式
            ['path' => '$.data.result[0]', 'label' => '图片URL', 'priority' => 'primary'],
            ['path' => '$.data.status', 'label' => '任务状态', 'priority' => 'auxiliary'],
            ['path' => '$.data.message', 'label' => '状态消息', 'priority' => 'auxiliary'],
            // 错误处理
            ['path' => '$.error.message', 'label' => '错误信息', 'priority' => 'error'],
        ]
    ], JSON_UNESCAPED_UNICODE);

    // capability_tags - 支持能力类型识别
    // 速创API支持：文生图、图生图、批量生成、多图输入
    $capabilityTags = json_encode([
        // 基础能力
        '文生图', '图生图', '异步任务', '轮询查询',
        'text2image', 'image2image', 'async_task',
        // 批量生成能力（用于识别"文生图-组图"、"图生图-单入多出"等能力类型）
        'batch_generation', '多图生成', '流式输出',
        // 多图输入能力（用于识别"多张参考图生成单张图"、"多张参考图生成组图"等能力类型）
        'multi_input', '多图融合', '多图输入'
    ], JSON_UNESCAPED_UNICODE);

    // limits_config - 包含速创API特有的接口配置
    $limitsConfig = json_encode([
        'timeout' => 300,
        'max_poll_attempts' => 60,      // 最多轮询60次
        'poll_interval' => 5,           // 每5秒轮询一次
        'max_input_images' => 10,
        'max_output_images' => 10,
        'supported_sizes' => ['auto', '1:1', '3:2', '2:3', '16:9', '9:16'],
        'stream_support' => false,
        'multi_image_support' => true,
        'async_support' => true,         // 明确支持异步
        // 速创API特有配置
        'relay_config' => [
            'submit_endpoint' => 'https://api.wuyinkeji.com/api/async/image_gpt',
            'query_endpoint' => 'https://api.wuyinkeji.com/api/async/detail',
            'auth_mode' => 'key_only',
            'response_parser' => 'sucai_async',
        ]
    ], JSON_UNESCAPED_UNICODE);

    // pricing_config
    $pricingConfig = json_encode([
        'currency' => 'CNY',
        'billing_mode' => 'per_image',
        'cost_per_image' => 0.1,
        'note' => '速创API计费，以实际返回为准'
    ], JSON_UNESCAPED_UNICODE);

    // 速创API接口配置
    $endpointUrl = 'https://api.wuyinkeji.com/api/async/image_gpt';
    $description = '速创API中转平台GPT-Image模型，支持文生图和图片编辑。异步接口，需轮询查询结果。';

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE `{$modelTable}` SET
            `model_name` = 'GPT-Image (速创)', `model_version` = 'v1.0',
            `description` = ?,
            `provider_id` = ?, `type_id` = ?,
            `endpoint_url` = ?,
            `task_type` = 'async', `capability_tags` = ?, `input_schema` = ?, `output_schema` = ?,
            `limits_config` = ?, `pricing_config` = ?, `is_active` = 1, `update_time` = ?
            WHERE `model_code` = ?");
        $stmt->execute([$description, $providerId, $typeId, $endpointUrl, $capabilityTags, $inputSchema, $outputSchema, $limitsConfig, $pricingConfig, $time, $modelCode]);
        echo "   ✓ 速创GPT-Image模型已更新 (ID={$existing['id']})\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$modelTable}`
            (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`,
             `endpoint_url`,
             `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`,
             `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`)
            VALUES
            (0, ?, ?, ?, 'GPT-Image (速创)', 'v1.0', ?,
             ?,
             'async', ?, ?, ?, ?,
             ?, 1, 1, 0, 10, ?, ?)");
        $stmt->execute([$providerId, $typeId, $modelCode, $description, $endpointUrl, $inputSchema, $outputSchema, $pricingConfig, $limitsConfig, $capabilityTags, $time, $time]);
        $newId = $pdo->lastInsertId();
        echo "   ✓ 速创GPT-Image模型创建成功 (ID={$newId})\n";
    }

    // ============================================================
    // 4. 验证
    // ============================================================
    echo "\n4. 验证数据...\n";
    echo "----------------------------------------\n";

    $stmt = $pdo->prepare("SELECT m.id, m.model_code, m.model_name, m.task_type, m.is_active, m.endpoint_url,
                   p.provider_name, p.auth_config, p.status as provider_status
            FROM `{$modelTable}` m
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id
            WHERE m.model_code = ?");
    $stmt->execute([$modelCode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $status = $row['is_active'] ? '✓' : '✗';
        $authConfig = json_decode($row['auth_config'] ?? '{}', true);
        echo "   {$status} [{$row['id']}] {$row['model_code']}\n";
        echo "     名称: {$row['model_name']}\n";
        echo "     供应商: {$row['provider_name']} | 认证模式: " . ($authConfig['auth_mode'] ?? 'unknown') . "\n";
        echo "     任务类型: {$row['task_type']}\n";
        echo "     接口地址: {$row['endpoint_url']}\n";
    } else {
        echo "   ✗ 模型记录未找到!\n";
    }

    echo "----------------------------------------\n";
    echo "\n✅ 速创API接入迁移完成！\n";
    echo "\n⚠️ 后续步骤：\n";
    echo "   1. 请在系统后台「API Key管理」中为 速创API 供应商配置有效的 API Key\n";
    echo "   2. 认证方式选择「纯Key」(无Bearer前缀)\n";
    echo "   3. 需要实现代码层面的轮询逻辑来处理异步任务\n";
    echo "========================================\n\n";

} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

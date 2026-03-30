<?php
/**
 * 即梦AI图片生成4.0 - 数据库迁移脚本
 * 
 * 功能：
 * 1. 创建即梦AI供应商（model_provider）
 * 2. 确认图片生成模型类型（model_type）
 * 3. 创建即梦4.0图片生成模型记录（model_info）
 * 
 * 执行方式: php migrate_jimeng.php
 * 
 * @date 2026-03-29
 */

// 引入配置
$config = include(__DIR__ . '/config.php');

$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "即梦AI图片生成4.0 - 数据库迁移\n";
echo "========================================\n\n";

try {
    $mysqli = new mysqli($hostname, $username, $password, $database, $hostport);
    
    if ($mysqli->connect_error) {
        throw new Exception("数据库连接失败: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    echo "✓ 数据库连接成功\n\n";
    
    $time = time();
    $providerTable = $prefix . 'model_provider';
    $typeTable     = $prefix . 'model_type';
    $modelTable    = $prefix . 'model_info';
    
    // ============================================================
    // 1. 创建/确认供应商
    // ============================================================
    echo "1. 检查供应商：即梦AI...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$providerTable}` WHERE `provider_code` = 'jimeng'");
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();
    
    if ($provider) {
        $providerId = $provider['id'];
        echo "   ✓ 供应商已存在 (ID={$providerId})\n";
    } else {
        $authConfig = json_encode([
            'type' => 'volcengine_v4',
            'region' => 'cn-north-1',
            'service' => 'cv',
            'note' => '火山引擎V4签名认证(HMAC-SHA256)，需配置Access Key和Secret Key',
            'fields' => [
                ['name' => 'api_key', 'label' => 'Access Key', 'type' => 'text', 'required' => true],
                ['name' => 'api_secret', 'label' => 'Secret Key', 'type' => 'password', 'required' => true]
            ]
        ], JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO `{$providerTable}` 
                (`aid`, `provider_code`, `provider_name`, `logo`, `website`, `api_doc_url`, 
                 `description`, `auth_config`, `is_system`, `status`, `sort`, `create_time`, `update_time`) 
                VALUES 
                (0, 'jimeng', '即梦AI（火山引擎）', '', 'https://www.volcengine.com', 
                 'https://www.volcengine.com/docs/6444/1340578',
                 '即梦AI是火山引擎旗下的AI图像生成能力，支持文生图、图像编辑、多图组合生成，最高支持4K超高清输出。',
                 ?, 0, 1, 15, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sii', $authConfig, $time, $time);
        if ($stmt->execute()) {
            $providerId = $mysqli->insert_id;
            echo "   ✓ 供应商创建成功 (ID={$providerId})\n";
        } else {
            throw new Exception("供应商创建失败: " . $mysqli->error);
        }
        $stmt->close();
    }
    
    // ============================================================
    // 2. 确认模型类型
    // ============================================================
    echo "\n2. 检查模型类型：图片生成...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'image_generation'");
    $stmt->execute();
    $result = $stmt->get_result();
    $type = $result->fetch_assoc();
    $stmt->close();
    
    if (!$type) {
        throw new Exception("模型类型 'image_generation' 不存在，请先创建模型类型记录");
    }
    $typeId = $type['id'];
    echo "   ✓ 模型类型已存在 (ID={$typeId})\n";
    
    // ============================================================
    // 3. 创建模型记录
    // ============================================================
    echo "\n3. 创建模型：即梦AI-图片生成4.0...\n";
    
    $modelCode = 'jimeng_t2i_v40';
    $modelName = '即梦AI-图片生成4.0';
    $description = '即梦4.0是即梦同源的图像生成能力，在统一框架内集成了文生图、图像编辑及多图组合生成功能。支持单次输入最多10张图像进行复合编辑，可一次性输出最多15张内容关联的图像。支持4K超高清输出，显著提升中文生成准确率与内容多样性。';
    $endpointUrl = 'https://visual.volcengineapi.com';
    
    $inputSchema = json_encode([
        'properties' => [
            'prompt' => [
                'label' => '提示词',
                'type' => 'text',
                'required' => true,
                'description' => '描述想要生成的图像内容，中英文均可，最长800字符。建议：用连贯自然语言描述画面内容，短词语描述画面美学。文字内容使用""引号包裹可提升准确率。',
                'order' => 0
            ],
            'image_urls' => [
                'label' => '参考图片',
                'type' => 'image_url_array',
                'required' => false,
                'description' => '0-10张参考图片URL，支持JPEG/PNG格式，每张最大15MB，分辨率最大4096×4096',
                'max_count' => 10,
                'order' => 1
            ],
            'size' => [
                'label' => '图片尺寸',
                'type' => 'enum',
                'required' => false,
                'default' => '2048x2048',
                'options' => [
                    '1024x1024',
                    '2048x2048', '2304x1728', '2496x1664', '2560x1440', '3024x1296',
                    '4096x4096', '4694x3520', '4992x3328', '5404x3040', '6198x2656'
                ],
                'description' => '输出图片尺寸（宽x高），默认2K(2048x2048)。也可在prompt中指定比例，模型会智能判断宽高比。',
                'order' => 2
            ],
            'scale' => [
                'label' => '文本影响程度',
                'type' => 'float',
                'required' => false,
                'default' => 0.5,
                'description' => '文本描述影响程度(0-1)，值越大文本影响越大、输入图片影响越小',
                'order' => 3
            ],
            'force_single' => [
                'label' => '强制单图',
                'type' => 'boolean',
                'required' => false,
                'default' => false,
                'description' => '是否强制只生成1张图片。不开启时，模型会根据prompt智能判断输出数量（最多15张-输入图数量）。',
                'order' => 4
            ]
        ],
        'required' => ['prompt']
    ], JSON_UNESCAPED_UNICODE);
    
    $outputSchema = json_encode([
        'type' => 'image',
        'format' => 'url',
        'image_format' => 'png',
        'url_expire' => '24h',
        'max_images' => 15
    ], JSON_UNESCAPED_UNICODE);
    
    $pricingConfig = json_encode([
        'billing_mode' => 'per_image',
        'unit' => '张',
        'price_per_image' => 0.06,
        'cost_price' => 0.06,
        'note' => '按输出图片张数计费，0.06元/张（2K分辨率）'
    ], JSON_UNESCAPED_UNICODE);
    
    $limitsConfig = json_encode([
        'timeout' => 600,
        'poll_interval' => 5,
        'max_retry' => 3,
        'max_input_images' => 10,
        'max_output_images' => 15,
        'max_image_size_mb' => 15,
        'max_resolution' => '4096x4096'
    ], JSON_UNESCAPED_UNICODE);
    
    $capabilityTags = json_encode([
        '文生图', '图生图', '图像编辑', '多图组合', '4K输出', '即梦AI', '火山引擎', 'V4.0'
    ], JSON_UNESCAPED_UNICODE);
    
    // 检查是否已存在
    $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
    $stmt->bind_param('s', $modelCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    $sort = 20;
    
    if ($existing) {
        $modelId = $existing['id'];
        $sql = "UPDATE `{$modelTable}` SET 
                `model_name` = ?, `description` = ?, `endpoint_url` = ?, 
                `provider_id` = ?, `type_id` = ?,
                `input_schema` = ?, `output_schema` = ?, `pricing_config` = ?, 
                `limits_config` = ?, `capability_tags` = ?, `task_type` = 'async',
                `model_version` = 'v4.0', `sort` = ?, `is_active` = 1, `update_time` = ? 
                WHERE `id` = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssiisssssiis',
            $modelName, $description, $endpointUrl,
            $providerId, $typeId,
            $inputSchema, $outputSchema, $pricingConfig,
            $limitsConfig, $capabilityTags, $sort, $time, $modelId
        );
        if ($stmt->execute()) {
            echo "   ✓ 模型已更新 (ID={$modelId})\n";
        } else {
            throw new Exception("模型更新失败: " . $mysqli->error);
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO `{$modelTable}` 
                (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, 
                 `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`, 
                 `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`) 
                VALUES 
                (0, ?, ?, ?, ?, 'v4.0', ?, ?, 'async', ?, ?, ?, ?, ?, 1, 1, 0, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisssssssssiis',
            $providerId, $typeId, $modelCode, $modelName, $description,
            $endpointUrl, $inputSchema, $outputSchema, $pricingConfig, $limitsConfig,
            $capabilityTags, $sort, $time, $time
        );
        if ($stmt->execute()) {
            $modelId = $mysqli->insert_id;
            echo "   ✓ 模型创建成功 (ID={$modelId})\n";
        } else {
            throw new Exception("模型创建失败: " . $mysqli->error);
        }
        $stmt->close();
    }
    
    // ============================================================
    // 4. 验证
    // ============================================================
    echo "\n4. 验证数据完整性...\n";
    $sql = "SELECT m.id, m.model_code, m.model_name, m.endpoint_url, m.task_type, m.is_active,
                   p.provider_name, p.provider_code, t.type_name 
            FROM `{$modelTable}` m 
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id 
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id 
            WHERE m.model_code = '{$modelCode}' AND m.is_active = 1";
    $result = $mysqli->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        echo "   ✓ [{$row['id']}] {$row['model_code']} - {$row['model_name']}\n";
        echo "     供应商: {$row['provider_name']} ({$row['provider_code']})\n";
        echo "     类型: {$row['type_name']}\n";
        echo "     端点: {$row['endpoint_url']}\n";
        echo "     任务类型: {$row['task_type']}\n";
    }
    
    echo "\n========================================\n";
    echo "✅ 即梦AI图片生成4.0模型接入迁移完成！\n";
    echo "========================================\n\n";
    echo "后续操作：\n";
    echo "1. 在管理后台「系统设置 > API Key管理」中配置火山引擎 Access Key 和 Secret Key\n";
    echo "   - Access Key: 火山引擎IAM的AccessKeyId\n";
    echo "   - Secret Key: 火山引擎IAM的SecretAccessKey\n";
    echo "2. 确保火山引擎控制台已开通「智能图像」服务\n";
    echo "3. 在模型广场中确认模型已正确显示\n";
    echo "4. 测试图片生成功能（文生图和图生图）\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

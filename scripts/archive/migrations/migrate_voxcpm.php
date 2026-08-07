<?php
/**
 * VoxCPM2 语音合成模型 - 数据库迁移脚本
 * 
 * 注册供应商、模型类型（语音合成）和模型记录
 * 
 * 使用方法: php migrate_voxcpm.php
 */

$config = include(__DIR__ . '/config.php');

$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "VoxCPM2 语音合成模型 - 数据库迁移\n";
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

    // ========== 1. 注册供应商 ==========
    echo "1. 检查供应商：VoxCPM...\n";

    $stmt = $mysqli->prepare("SELECT id FROM `{$providerTable}` WHERE `provider_code` = 'voxcpm'");
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();

    if ($provider) {
        echo "   → 供应商已存在 (ID={$provider['id']})\n";
        $providerId = $provider['id'];
    } else {
        $authConfig = json_encode([
            'type' => 'custom_url',
            'fields' => [
                [
                    'name' => 'api_key',
                    'label' => 'VoxCPM服务地址',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => '如: http://192.168.1.100:8866',
                    'description' => '填写部署了voxcpm_server.py的服务器地址（IP:端口），如 http://192.168.1.100:8866',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO `{$providerTable}` 
                (`aid`, `provider_code`, `provider_name`, `logo`, `website`, `api_doc_url`, 
                 `description`, `auth_config`, `is_system`, `status`, `sort`, `create_time`, `update_time`) 
                VALUES 
                (0, 'voxcpm', 'VoxCPM2（语音合成）', '', 'https://github.com/OpenBMB/VoxCPM', 
                 'https://github.com/OpenBMB/VoxCPM',
                 'VoxCPM2 是清华大学 OpenBMB 开源的 2B 参数语音合成模型，支持30种语言、声音设计、可控克隆、极致克隆等能力，输出48kHz高清音频。需用户自行在GPU服务器部署。',
                 ?, 0, 1, 13, ?, ?)";
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

    // ========== 2. 确认模型类型 ==========
    echo "\n2. 检查模型类型...\n";

    // 语音模型类型
    $stmt = $mysqli->prepare("SELECT id, type_name FROM `{$typeTable}` WHERE `type_code` = 'speech_model'");
    $stmt->execute();
    $result = $stmt->get_result();
    $speechType = $result->fetch_assoc();
    $stmt->close();

    if ($speechType) {
        $speechTypeId = $speechType['id'];
        echo "   ✓ 语音模型类型 (ID={$speechTypeId}) - {$speechType['type_name']}\n";
    } else {
        // 尝试按id=5查找
        $stmt = $mysqli->prepare("SELECT id, type_name FROM `{$typeTable}` WHERE `id` = 5");
        $stmt->execute();
        $result = $stmt->get_result();
        $speechType = $result->fetch_assoc();
        $stmt->close();
        if ($speechType) {
            $speechTypeId = $speechType['id'];
            echo "   ✓ 语音模型类型 (ID={$speechTypeId}) - {$speechType['type_name']}\n";
        } else {
            throw new Exception("语音模型类型不存在，请先创建 speech_model 类型");
        }
    }

    // ========== 3. 创建模型记录 ==========
    echo "\n3. 创建模型记录...\n";

    $endpointUrl = 'http://127.0.0.1:8866';
    $modelCode = 'voxcpm2-tts';
    $modelName = 'VoxCPM2 语音合成';
    $modelVersion = '2.0';
    $description = 'VoxCPM2 2B参数语音合成模型，支持30种语言及9种中文方言，48kHz高清音频输出。提供三种模式：声音设计（从文字描述创造声音）、可控克隆（上传参考音频+风格控制）、极致克隆（完整还原每一个声音细节）。';
    $taskType = 'sync';
    $sortOrder = 35;

    $inputSchema = json_encode([
        'parameters' => [
            ['name' => 'text', 'label' => '合成文本', 'type' => 'string', 'required' => true, 'description' => '要合成为语音的目标文本内容', 'placeholder' => '请输入要合成的文本...'],
            ['name' => 'mode', 'label' => '合成模式', 'type' => 'enum', 'required' => false, 'default' => 'tts', 'options' => ['tts', 'voice_design', 'controllable_clone', 'ultimate_clone'], 'description' => '普通合成/声音设计/可控克隆/极致克隆'],
            ['name' => 'control', 'label' => '声音描述/风格控制', 'type' => 'string', 'required' => false, 'description' => '描述目标声音特征（性别、年龄、语气、情绪、语速）或克隆风格', 'placeholder' => '如: 年轻女性，温柔甜美，语速偏慢'],
            ['name' => 'reference_audio', 'label' => '参考音频', 'type' => 'file', 'required' => false, 'accept' => 'audio/*', 'description' => '声音克隆时需上传参考音频（WAV格式，建议5-30秒）'],
            ['name' => 'prompt_text', 'label' => '参考音频文本', 'type' => 'string', 'required' => false, 'description' => '极致克隆模式：参考音频对应的文字转录内容', 'placeholder' => '参考音频中说的内容...'],
            ['name' => 'cfg_value', 'label' => 'CFG引导强度', 'type' => 'float', 'required' => false, 'default' => 2.0, 'min' => 0.1, 'max' => 10.0, 'description' => '越高越贴合描述/参考音色，越低越自由'],
            ['name' => 'inference_timesteps', 'label' => '推理步数', 'type' => 'integer', 'required' => false, 'default' => 10, 'min' => 1, 'max' => 50, 'description' => 'LocDiT流匹配步数，步数越多音质可能越好但更慢'],
        ],
    ], JSON_UNESCAPED_UNICODE);

    $outputSchema = json_encode(['type' => 'audio', 'format' => 'wav', 'sample_rate' => 48000, 'description' => '48kHz高清WAV音频'], JSON_UNESCAPED_UNICODE);
    $pricingConfig = json_encode(['billing_mode' => 'free', 'price_per_call' => 0, 'note' => '本地部署，免费使用'], JSON_UNESCAPED_UNICODE);
    $limitsConfig = json_encode(['timeout' => 300, 'max_text_length' => 5000, 'supported_audio_formats' => ['wav', 'mp3', 'flac', 'ogg'], 'recommended_reference_duration' => '5-30秒'], JSON_UNESCAPED_UNICODE);
    $capabilityTags = json_encode(['text_to_speech', 'voice_design', 'voice_clone', 'multilingual', '48khz', 'streaming', '30_languages', 'chinese_dialects'], JSON_UNESCAPED_UNICODE);

    echo "\n   模型: {$modelCode} ({$modelName})...\n";

    $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ? AND `provider_id` = ?");
    $stmt->bind_param('si', $modelCode, $providerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $modelId = $existing['id'];
        $sql = "UPDATE `{$modelTable}` SET 
                `model_name` = ?, `model_version` = ?, `description` = ?, `endpoint_url` = ?,
                `type_id` = ?, `task_type` = ?,
                `input_schema` = ?, `output_schema` = ?, `pricing_config` = ?, 
                `limits_config` = ?, `capability_tags` = ?,
                `sort` = ?, `is_active` = 1, `update_time` = ?
                WHERE `id` = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssssissssssiis',
            $modelName, $modelVersion, $description, $endpointUrl,
            $speechTypeId, $taskType,
            $inputSchema, $outputSchema, $pricingConfig,
            $limitsConfig, $capabilityTags,
            $sortOrder, $time, $modelId
        );
        if ($stmt->execute()) {
            echo "   ✓ 已更新 (ID={$modelId})\n";
        } else {
            echo "   ✗ 更新失败: " . $mysqli->error . "\n";
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO `{$modelTable}` 
                (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`,
                 `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`,
                 `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`)
                VALUES 
                (0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisssssssssssiis',
            $providerId, $speechTypeId, $modelCode, $modelName, $modelVersion, $description,
            $endpointUrl, $taskType, $inputSchema, $outputSchema, $pricingConfig, $limitsConfig,
            $capabilityTags, $sortOrder, $time, $time
        );
        if ($stmt->execute()) {
            $modelId = $mysqli->insert_id;
            echo "   ✓ 创建成功 (ID={$modelId})\n";
        } else {
            echo "   ✗ 创建失败: " . $mysqli->error . "\n";
        }
        $stmt->close();
    }

    // ========== 4. 验证 ==========
    echo "\n4. 验证数据完整性...\n";

    $sql = "SELECT m.id, m.model_code, m.model_name, m.endpoint_url, m.task_type,
                   p.provider_name, t.type_name
            FROM `{$modelTable}` m
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id
            WHERE m.model_code = 'voxcpm2-tts' AND m.is_active = 1";
    $result = $mysqli->query($sql);

    while ($row = $result->fetch_assoc()) {
        echo "   ✓ [{$row['id']}] {$row['model_code']} - {$row['model_name']}\n";
        echo "     供应商: {$row['provider_name']}\n";
        echo "     类型: {$row['type_name']}\n";
        echo "     端点: {$row['endpoint_url']}\n\n";
    }

    echo "========================================\n";
    echo "✅ VoxCPM2 语音合成模型接入迁移完成！\n";
    echo "========================================\n\n";

    echo "已注册模型：\n";
    echo "  - voxcpm2-tts (语音合成/声音设计/声音克隆)\n\n";

    echo "使用前准备：\n";
    echo "1. 在GPU服务器上部署VoxCPM2:\n";
    echo "   pip install voxcpm fastapi uvicorn python-multipart soundfile numpy\n";
    echo "2. 启动API服务:\n";
    echo "   python deploy/voxcpm_server.py --host 0.0.0.0 --port 8866\n";
    echo "3. 在系统设置→API Key管理中，添加VoxCPM供应商配置:\n";
    echo "   API Key字段填写服务器地址，如: http://192.168.1.100:8866\n";

    $mysqli->close();

} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

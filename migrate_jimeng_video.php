<?php
/**
 * 即梦AI-视频生成3.0 - 数据库迁移脚本
 * 
 * 功能：
 * 1. 确认即梦AI供应商（provider_id=11）
 * 2. 确认视频生成模型类型（video_generation）
 * 3. 创建即梦视频3.0 720P模型记录
 * 4. 创建即梦视频3.0 1080P模型记录
 * 
 * 执行方式: php migrate_jimeng_video.php
 * 
 * @date 2026-03-30
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
echo "即梦AI-视频生成3.0 - 数据库迁移\n";
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
    // 1. 确认供应商
    // ============================================================
    echo "1. 检查供应商：即梦AI...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$providerTable}` WHERE `provider_code` = 'jimeng'");
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();
    
    if (!$provider) {
        throw new Exception("供应商 'jimeng' 不存在，请先执行 migrate_jimeng.php 创建供应商");
    }
    $providerId = $provider['id'];
    echo "   ✓ 供应商已存在 (ID={$providerId})\n";
    
    // ============================================================
    // 2. 确认模型类型
    // ============================================================
    echo "\n2. 检查模型类型：视频生成...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'video_generation'");
    $stmt->execute();
    $result = $stmt->get_result();
    $type = $result->fetch_assoc();
    $stmt->close();
    
    if (!$type) {
        throw new Exception("模型类型 'video_generation' 不存在，请先创建模型类型记录");
    }
    $typeId = $type['id'];
    echo "   ✓ 模型类型已存在 (ID={$typeId})\n";
    
    // ============================================================
    // 3. 创建模型：即梦视频3.0 720P
    // ============================================================
    echo "\n3. 创建模型：即梦AI-视频生成3.0 720P...\n";
    
    $modelCode720 = 'jimeng_video_v30_720p';
    $modelName720 = '即梦AI-视频生成3.0 720P';
    $description720 = '即梦视频3.0 720P版，专业级视频生成引擎。准确遵循复杂指令，视觉表达流畅一致，支持720P高清渲染，可驾驭多元艺术风格。支持文生视频、图生视频（首帧/首尾帧）、运镜控制等多种模式，是生成效果与速度兼备的高性价比之选。';
    $endpointUrl = 'https://visual.volcengineapi.com';
    
    $inputSchema720 = json_encode([
        'properties' => [
            'prompt' => [
                'label' => '提示词',
                'type' => 'text',
                'required' => false,
                'description' => '描述想要生成的视频内容，建议结构：主体/背景/镜头+动作。文生视频模式必填。',
                'order' => 0
            ],
            'first_frame_image' => [
                'label' => '首帧图片',
                'type' => 'image_url',
                'required' => false,
                'description' => '图生视频的首帧图片URL，支持JPEG/PNG格式',
                'order' => 1
            ],
            'last_frame_image' => [
                'label' => '尾帧图片',
                'type' => 'image_url',
                'required' => false,
                'description' => '首尾帧模式的尾帧图片URL，与首帧图片配合使用',
                'order' => 2
            ],
            'aspect_ratio' => [
                'label' => '画面比例',
                'type' => 'enum',
                'required' => false,
                'default' => '16:9',
                'options' => ['16:9', '9:16', '1:1', '4:3', '3:4', '21:9', '9:21'],
                'description' => '视频画面比例',
                'order' => 3
            ],
            'camera_type' => [
                'label' => '运镜模板',
                'type' => 'enum',
                'required' => false,
                'options' => [
                    'hitchcock_dolly_in', 'hitchcock_dolly_out',
                    'robo_arm', 'dynamic_orbit', 'central_orbit',
                    'crane_push', 'quick_pull_back',
                    'counterclockwise_swivel', 'clockwise_swivel',
                    'handheld', 'rapid_push_pull'
                ],
                'description' => '运镜模板ID，仅运镜模式使用。需配合首帧图片。可选：希区柯克推进/拉远、机械臂、动感环绕、中心环绕、起重机、超级拉远、逆时针回旋、顺时针回旋、手持运镜、快速推拉',
                'order' => 4
            ],
            'camera_amplitude' => [
                'label' => '运镜强度',
                'type' => 'enum',
                'required' => false,
                'default' => 'medium',
                'options' => ['weak', 'medium', 'strong'],
                'description' => '运镜强度，仅运镜模式使用。weak=弱、medium=中、strong=强',
                'order' => 5
            ]
        ],
        'required' => []
    ], JSON_UNESCAPED_UNICODE);
    
    $outputSchema720 = json_encode([
        'type' => 'video',
        'format' => 'url',
        'video_format' => 'mp4',
        'resolution' => '720P',
        'url_expire' => '24h'
    ], JSON_UNESCAPED_UNICODE);
    
    $pricingConfig720 = json_encode([
        'billing_mode' => 'per_video',
        'unit' => '条',
        'price_per_video' => 0.40,
        'cost_price' => 0.40,
        'note' => '按视频条数计费，720P每条0.40元'
    ], JSON_UNESCAPED_UNICODE);
    
    $limitsConfig720 = json_encode([
        'timeout' => 600,
        'poll_interval' => 5,
        'max_retry' => 3,
        'max_duration' => 5,
        'resolution' => '720P'
    ], JSON_UNESCAPED_UNICODE);
    
    $capabilityTags720 = json_encode([
        '文生视频', '图生视频', '首帧', '首尾帧', '运镜控制', '720P', '即梦AI', '火山引擎', 'V3.0'
    ], JSON_UNESCAPED_UNICODE);
    
    // 检查是否已存在
    $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
    $stmt->bind_param('s', $modelCode720);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    $sort720 = 21;
    
    if ($existing) {
        $modelId720 = $existing['id'];
        $sql = "UPDATE `{$modelTable}` SET 
                `model_name` = ?, `description` = ?, `endpoint_url` = ?, 
                `provider_id` = ?, `type_id` = ?,
                `input_schema` = ?, `output_schema` = ?, `pricing_config` = ?, 
                `limits_config` = ?, `capability_tags` = ?, `task_type` = 'async',
                `model_version` = 'v3.0', `sort` = ?, `is_active` = 1, `update_time` = ? 
                WHERE `id` = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssiisssssiii',
            $modelName720, $description720, $endpointUrl,
            $providerId, $typeId,
            $inputSchema720, $outputSchema720, $pricingConfig720,
            $limitsConfig720, $capabilityTags720, $sort720, $time, $modelId720
        );
        if ($stmt->execute()) {
            echo "   ✓ 模型已更新 (ID={$modelId720})\n";
        } else {
            throw new Exception("720P模型更新失败: " . $mysqli->error);
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO `{$modelTable}` 
                (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, 
                 `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`, 
                 `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`) 
                VALUES 
                (0, ?, ?, ?, ?, 'v3.0', ?, ?, 'async', ?, ?, ?, ?, ?, 1, 1, 0, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisssssssssiis',
            $providerId, $typeId, $modelCode720, $modelName720, $description720,
            $endpointUrl, $inputSchema720, $outputSchema720, $pricingConfig720, $limitsConfig720,
            $capabilityTags720, $sort720, $time, $time
        );
        if ($stmt->execute()) {
            $modelId720 = $mysqli->insert_id;
            echo "   ✓ 模型创建成功 (ID={$modelId720})\n";
        } else {
            throw new Exception("720P模型创建失败: " . $mysqli->error);
        }
        $stmt->close();
    }
    
    // ============================================================
    // 4. 创建模型：即梦视频3.0 1080P
    // ============================================================
    echo "\n4. 创建模型：即梦AI-视频生成3.0 1080P...\n";
    
    $modelCode1080 = 'jimeng_video_v30_1080p';
    $modelName1080 = '即梦AI-视频生成3.0 1080P';
    $description1080 = '即梦视频3.0 1080P版，专业级高清视频生成引擎。准确遵循复杂指令，视觉表达流畅一致，支持1080P高清渲染，场景渲染效果优异，能细腻呈现自然光影与写实场景细节。支持文生视频、图生视频（首帧/首尾帧）等多种模式。';
    
    $inputSchema1080 = json_encode([
        'properties' => [
            'prompt' => [
                'label' => '提示词',
                'type' => 'text',
                'required' => false,
                'description' => '描述想要生成的视频内容，建议结构：主体/背景/镜头+动作。文生视频模式必填。',
                'order' => 0
            ],
            'first_frame_image' => [
                'label' => '首帧图片',
                'type' => 'image_url',
                'required' => false,
                'description' => '图生视频的首帧图片URL，支持JPEG/PNG格式',
                'order' => 1
            ],
            'last_frame_image' => [
                'label' => '尾帧图片',
                'type' => 'image_url',
                'required' => false,
                'description' => '首尾帧模式的尾帧图片URL，与首帧图片配合使用',
                'order' => 2
            ],
            'aspect_ratio' => [
                'label' => '画面比例',
                'type' => 'enum',
                'required' => false,
                'default' => '16:9',
                'options' => ['16:9', '9:16', '1:1', '4:3', '3:4', '21:9', '9:21'],
                'description' => '视频画面比例',
                'order' => 3
            ]
        ],
        'required' => []
    ], JSON_UNESCAPED_UNICODE);
    
    $outputSchema1080 = json_encode([
        'type' => 'video',
        'format' => 'url',
        'video_format' => 'mp4',
        'resolution' => '1080P',
        'url_expire' => '24h'
    ], JSON_UNESCAPED_UNICODE);
    
    $pricingConfig1080 = json_encode([
        'billing_mode' => 'per_video',
        'unit' => '条',
        'price_per_video' => 0.80,
        'cost_price' => 0.80,
        'note' => '按视频条数计费，1080P每条0.80元'
    ], JSON_UNESCAPED_UNICODE);
    
    $limitsConfig1080 = json_encode([
        'timeout' => 600,
        'poll_interval' => 5,
        'max_retry' => 3,
        'max_duration' => 5,
        'resolution' => '1080P'
    ], JSON_UNESCAPED_UNICODE);
    
    $capabilityTags1080 = json_encode([
        '文生视频', '图生视频', '首帧', '首尾帧', '1080P', '高清', '即梦AI', '火山引擎', 'V3.0'
    ], JSON_UNESCAPED_UNICODE);
    
    // 检查是否已存在
    $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
    $stmt->bind_param('s', $modelCode1080);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    $sort1080 = 22;
    
    if ($existing) {
        $modelId1080 = $existing['id'];
        $sql = "UPDATE `{$modelTable}` SET 
                `model_name` = ?, `description` = ?, `endpoint_url` = ?, 
                `provider_id` = ?, `type_id` = ?,
                `input_schema` = ?, `output_schema` = ?, `pricing_config` = ?, 
                `limits_config` = ?, `capability_tags` = ?, `task_type` = 'async',
                `model_version` = 'v3.0', `sort` = ?, `is_active` = 1, `update_time` = ? 
                WHERE `id` = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssiisssssiii',
            $modelName1080, $description1080, $endpointUrl,
            $providerId, $typeId,
            $inputSchema1080, $outputSchema1080, $pricingConfig1080,
            $limitsConfig1080, $capabilityTags1080, $sort1080, $time, $modelId1080
        );
        if ($stmt->execute()) {
            echo "   ✓ 模型已更新 (ID={$modelId1080})\n";
        } else {
            throw new Exception("1080P模型更新失败: " . $mysqli->error);
        }
        $stmt->close();
    } else {
        $sql = "INSERT INTO `{$modelTable}` 
                (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, 
                 `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`, 
                 `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`) 
                VALUES 
                (0, ?, ?, ?, ?, 'v3.0', ?, ?, 'async', ?, ?, ?, ?, ?, 1, 1, 0, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisssssssssiis',
            $providerId, $typeId, $modelCode1080, $modelName1080, $description1080,
            $endpointUrl, $inputSchema1080, $outputSchema1080, $pricingConfig1080, $limitsConfig1080,
            $capabilityTags1080, $sort1080, $time, $time
        );
        if ($stmt->execute()) {
            $modelId1080 = $mysqli->insert_id;
            echo "   ✓ 模型创建成功 (ID={$modelId1080})\n";
        } else {
            throw new Exception("1080P模型创建失败: " . $mysqli->error);
        }
        $stmt->close();
    }
    
    // ============================================================
    // 5. 验证
    // ============================================================
    echo "\n5. 验证数据完整性...\n";
    $sql = "SELECT m.id, m.model_code, m.model_name, m.endpoint_url, m.task_type, m.is_active,
                   p.provider_name, p.provider_code, t.type_name 
            FROM `{$modelTable}` m 
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id 
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id 
            WHERE m.model_code IN ('{$modelCode720}', '{$modelCode1080}') AND m.is_active = 1
            ORDER BY m.id";
    $result = $mysqli->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        echo "   ✓ [{$row['id']}] {$row['model_code']} - {$row['model_name']}\n";
        echo "     供应商: {$row['provider_name']} ({$row['provider_code']})\n";
        echo "     类型: {$row['type_name']}\n";
        echo "     端点: {$row['endpoint_url']}\n";
        echo "     任务类型: {$row['task_type']}\n\n";
    }
    
    echo "========================================\n";
    echo "✅ 即梦AI-视频生成3.0 模型接入迁移完成！\n";
    echo "========================================\n\n";
    echo "已创建模型：\n";
    echo "  - jimeng_video_v30_720p  (720P, 支持文生视频/首帧/首尾帧/运镜)\n";
    echo "  - jimeng_video_v30_1080p (1080P, 支持文生视频/首帧/首尾帧)\n\n";
    echo "req_key已确认（来自火山引擎文档）：\n";
    echo "  720P: jimeng_t2v_v30 / jimeng_i2v_first_v30 / jimeng_i2v_first_tail_v30 / jimeng_i2v_recamera_v30\n";
    echo "  1080P: jimeng_t2v_v30_1080p / jimeng_i2v_first_v30_1080 / jimeng_i2v_first_tail_v30_1080\n\n";
    echo "后续操作：\n";
    echo "1. 确认火山引擎控制台已开通「即梦AI-视频生成3.0」服务\n";
    echo "2. 在模型广场中确认模型已正确显示\n";
    echo "3. 测试视频生成功能（文生视频和图生视频）\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

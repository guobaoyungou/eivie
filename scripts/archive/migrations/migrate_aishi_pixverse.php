<?php
/**
 * 爱诗科技（PixVerse）视频生成模型接入 - 数据库迁移脚本
 * 
 * 功能：
 * 1. 确认爱诗科技供应商（model_provider）
 * 2. 确认图生视频模型类型（model_type）
 * 3. 修复/更新已有的 pixverse/pixverse-v5.6-it2v 模型记录
 * 4. 新增 pixverse/pixverse-v5.6-kf2v 和 pixverse/pixverse-v5.6-r2v 模型记录
 * 
 * 执行方式: php migrate_aishi_pixverse.php
 * 
 * @date 2026-03-27
 */

// 引入配置
$config = include(__DIR__ . '/config.php');

// 数据库连接信息
$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "爱诗科技（PixVerse）模型接入 - 数据库迁移\n";
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
    
    // 正确的端点URL
    $correctEndpoint = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis';
    
    // ============================================================
    // 1. 确认供应商
    // ============================================================
    echo "1. 检查供应商：爱诗科技...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$providerTable}` WHERE `provider_code` = 'aishi'");
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();
    
    if (!$provider) {
        throw new Exception("供应商 'aishi' 不存在，请先创建供应商记录");
    }
    $providerId = $provider['id'];
    echo "   ✓ 供应商已存在 (ID={$providerId})\n";
    
    // ============================================================
    // 2. 确认模型类型
    // ============================================================
    echo "\n2. 检查模型类型：图生视频...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'image_to_video'");
    $stmt->execute();
    $result = $stmt->get_result();
    $type = $result->fetch_assoc();
    $stmt->close();
    
    if (!$type) {
        throw new Exception("模型类型 'image_to_video' 不存在，请先创建模型类型记录");
    }
    $typeId = $type['id'];
    echo "   ✓ 模型类型已存在 (ID={$typeId})\n";
    
    // ============================================================
    // 3. 定义3个模型的配置
    // ============================================================
    
    // 通用价格配置
    $pricingConfig = json_encode([
        'billing_mode' => 'per_second',
        'unit' => '秒',
        'price_per_second' => 0.50,
        'cost_price' => 0.50,
        'note' => '按视频时长（秒）计费，0.50元/秒'
    ], JSON_UNESCAPED_UNICODE);
    
    // 通用限制配置
    $limitsConfig = json_encode([
        'timeout' => 600,
        'poll_interval' => 10,
        'max_retry' => 3,
        'rate_limit' => 2
    ], JSON_UNESCAPED_UNICODE);
    
    // 通用输出格式
    $outputSchema = json_encode([
        'type' => 'video',
        'format' => 'url',
        'video_format' => 'mp4'
    ], JSON_UNESCAPED_UNICODE);
    
    // 模型定义数组
    $models = [
        // —— 图生视频-基于首帧 ——
        [
            'model_code'  => 'pixverse/pixverse-v5.6-it2v',
            'model_name'  => '爱诗PixVerse V5.6 图生视频（首帧）',
            'description' => '爱诗科技 PixVerse V5.6 图生视频模型，基于输入图像和文本提示词生成视频。支持360P/540P/720P/1080P分辨率，5-10秒时长，可生成有声视频。',
            'capability_tags' => ['图生视频', '首帧驱动', '有声视频', '爱诗科技', 'PixVerse', 'V5.6'],
            'input_schema' => [
                'properties' => [
                    'image_url' => [
                        'label' => '首帧图像',
                        'type' => 'image_url',
                        'required' => true,
                        'description' => '公网可访问的图片URL，支持JPG/PNG/WEBP，不超过20MB',
                        'order' => 0
                    ],
                    'prompt' => [
                        'label' => '提示词',
                        'type' => 'text',
                        'required' => false,
                        'default' => '',
                        'description' => '描述视频内容，不超过2048字符（可选）',
                        'order' => 1
                    ],
                    'resolution' => [
                        'label' => '分辨率',
                        'type' => 'enum',
                        'required' => false,
                        'default' => '720P',
                        'options' => ['360P', '540P', '720P', '1080P'],
                        'description' => '视频分辨率档位',
                        'order' => 2
                    ],
                    'duration' => [
                        'label' => '视频时长',
                        'type' => 'enum',
                        'required' => false,
                        'default' => 5,
                        'options' => [5, 8, 10],
                        'description' => '视频时长（秒），1080P仅支持5/8秒',
                        'order' => 3
                    ],
                    'audio' => [
                        'label' => '有声视频',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                        'description' => '是否生成有声视频（影响费用）',
                        'order' => 4
                    ],
                    'seed' => [
                        'label' => '随机种子',
                        'type' => 'integer',
                        'required' => false,
                        'description' => '用于结果复现，范围[0, 2147483647]',
                        'order' => 5
                    ]
                ],
                'required' => ['image_url']
            ],
            'sort' => 50
        ],
        // —— 图生视频-基于首尾帧 ——
        [
            'model_code'  => 'pixverse/pixverse-v5.6-kf2v',
            'model_name'  => '爱诗PixVerse V5.6 首尾帧生视频',
            'description' => '爱诗科技 PixVerse V5.6 首尾帧生视频模型，基于首帧和尾帧图像生成平滑过渡视频。支持360P-1080P分辨率，5-10秒时长，可生成有声视频。',
            'capability_tags' => ['图生视频', '首尾帧', '有声视频', '爱诗科技', 'PixVerse', 'V5.6'],
            'input_schema' => [
                'properties' => [
                    'first_frame_image' => [
                        'label' => '首帧图像',
                        'type' => 'image_url',
                        'required' => true,
                        'description' => '首帧图片URL，支持JPG/PNG/WEBP，不超过20MB',
                        'order' => 0
                    ],
                    'last_frame_image' => [
                        'label' => '尾帧图像',
                        'type' => 'image_url',
                        'required' => true,
                        'description' => '尾帧图片URL，支持JPG/PNG/WEBP，不超过20MB',
                        'order' => 1
                    ],
                    'prompt' => [
                        'label' => '提示词',
                        'type' => 'text',
                        'required' => true,
                        'default' => '',
                        'description' => '描述首帧到尾帧之间的变化过程，不超过2048字符',
                        'order' => 2
                    ],
                    'resolution' => [
                        'label' => '分辨率',
                        'type' => 'enum',
                        'required' => false,
                        'default' => '720P',
                        'options' => ['360P', '540P', '720P', '1080P'],
                        'description' => '视频分辨率档位',
                        'order' => 3
                    ],
                    'duration' => [
                        'label' => '视频时长',
                        'type' => 'enum',
                        'required' => false,
                        'default' => 5,
                        'options' => [5, 8, 10],
                        'description' => '视频时长（秒），1080P仅支持5/8秒',
                        'order' => 4
                    ],
                    'audio' => [
                        'label' => '有声视频',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                        'description' => '是否生成有声视频（影响费用）',
                        'order' => 5
                    ],
                    'seed' => [
                        'label' => '随机种子',
                        'type' => 'integer',
                        'required' => false,
                        'description' => '用于结果复现，范围[0, 2147483647]',
                        'order' => 6
                    ]
                ],
                'required' => ['first_frame_image', 'last_frame_image', 'prompt']
            ],
            'sort' => 51
        ],
        // —— 参考生视频 ——
        [
            'model_code'  => 'pixverse/pixverse-v5.6-r2v',
            'model_name'  => '爱诗PixVerse V5.6 参考生视频',
            'description' => '爱诗科技 PixVerse V5.6 参考生视频模型，支持传入多张参考图片（1-7张），将图片中的主体角色融合生成视频。支持多种宽高比，可生成有声视频。',
            'capability_tags' => ['参考生视频', '多图参考', '有声视频', '爱诗科技', 'PixVerse', 'V5.6'],
            'input_schema' => [
                'properties' => [
                    'reference_images' => [
                        'label' => '参考图片',
                        'type' => 'image_url_array',
                        'required' => true,
                        'description' => '1-7张参考图片URL，支持JPG/PNG/WEBP，每张不超过20MB',
                        'max_count' => 7,
                        'order' => 0
                    ],
                    'prompt' => [
                        'label' => '提示词',
                        'type' => 'text',
                        'required' => true,
                        'default' => '',
                        'description' => '描述期望生成的视频内容，不超过2048字符',
                        'order' => 1
                    ],
                    'size' => [
                        'label' => '视频尺寸',
                        'type' => 'enum',
                        'required' => false,
                        'default' => '1280*720',
                        'options' => [
                            '640*360', '640*480', '640*640', '480*640', '360*640',
                            '1024*576', '1024*768', '1024*1024', '768*1024', '576*1024',
                            '1280*720', '1108*830', '960*960', '830*1108', '720*1280',
                            '1920*1080', '1662*1246', '1440*1440', '1246*1662', '1080*1920'
                        ],
                        'description' => '视频分辨率（宽*高）',
                        'order' => 2
                    ],
                    'duration' => [
                        'label' => '视频时长',
                        'type' => 'enum',
                        'required' => false,
                        'default' => 5,
                        'options' => [5, 8, 10],
                        'description' => '视频时长（秒），1080P仅支持5/8秒',
                        'order' => 3
                    ],
                    'audio' => [
                        'label' => '有声视频',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                        'description' => '是否生成有声视频（影响费用）',
                        'order' => 4
                    ],
                    'seed' => [
                        'label' => '随机种子',
                        'type' => 'integer',
                        'required' => false,
                        'description' => '用于结果复现，范围[0, 2147483647]',
                        'order' => 5
                    ]
                ],
                'required' => ['reference_images', 'prompt']
            ],
            'sort' => 52
        ]
    ];
    
    // ============================================================
    // 3. 批量创建/更新模型
    // ============================================================
    foreach ($models as $idx => $modelDef) {
        $num = $idx + 1;
        $mc = $modelDef['model_code'];
        echo "\n{$num}. 检查/创建模型：{$mc}...\n";
        
        $inputSchema    = json_encode($modelDef['input_schema'], JSON_UNESCAPED_UNICODE);
        $capabilityTags = json_encode($modelDef['capability_tags'], JSON_UNESCAPED_UNICODE);
        
        $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
        $stmt->bind_param('s', $mc);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // 更新已有记录（修复endpoint_url、input_schema、pricing等）
            $modelId = $existing['id'];
            $sql = "UPDATE `{$modelTable}` SET 
                    `model_name` = ?, `description` = ?, `endpoint_url` = ?, 
                    `input_schema` = ?, `pricing_config` = ?, `limits_config` = ?,
                    `output_schema` = ?, `capability_tags` = ?, `sort` = ?,
                    `is_active` = 1, `update_time` = ? 
                    WHERE `id` = ?";
            $stmt = $mysqli->prepare($sql);
            $sort = $modelDef['sort'];
            $stmt->bind_param('ssssssssiis',
                $modelDef['model_name'], $modelDef['description'], $correctEndpoint,
                $inputSchema, $pricingConfig, $limitsConfig,
                $outputSchema, $capabilityTags, $sort,
                $time, $modelId
            );
            if ($stmt->execute()) {
                echo "   ✓ 模型已更新 (ID={$modelId})\n";
            } else {
                throw new Exception("模型更新失败: " . $mysqli->error);
            }
            $stmt->close();
        } else {
            // 创建新记录
            $sql = "INSERT INTO `{$modelTable}` 
                    (`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, 
                     `endpoint_url`, `task_type`, `input_schema`, `output_schema`, `pricing_config`, `limits_config`, 
                     `capability_tags`, `is_active`, `is_recommend`, `is_system`, `sort`, `create_time`, `update_time`) 
                    VALUES 
                    (0, ?, ?, ?, ?, 'v5.6', ?, ?, 'async', ?, ?, ?, ?, ?, 1, 1, 0, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $sort = $modelDef['sort'];
            $stmt->bind_param('iisssssssssiis',
                $providerId, $typeId, $mc, $modelDef['model_name'], $modelDef['description'],
                $correctEndpoint, $inputSchema, $outputSchema, $pricingConfig, $limitsConfig,
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
    }
    
    // ============================================================
    // 4. 清理旧的t2v模型记录（如果存在）
    // ============================================================
    echo "\n4. 检查旧模型记录...\n";
    $oldCodes = ['pixverse-v5.6-t2v', 'pixverse-v4.5'];
    foreach ($oldCodes as $oldCode) {
        $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
        $stmt->bind_param('s', $oldCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $old = $result->fetch_assoc();
        $stmt->close();
        if ($old) {
            // 禁用而不删除
            $stmt = $mysqli->prepare("UPDATE `{$modelTable}` SET `is_active` = 0, `update_time` = ? WHERE `id` = ?");
            $stmt->bind_param('ii', $time, $old['id']);
            $stmt->execute();
            $stmt->close();
            echo "   ⚠ 旧模型 '{$oldCode}' (ID={$old['id']}) 已禁用\n";
        }
    }
    
    // ============================================================
    // 5. 验证
    // ============================================================
    echo "\n5. 验证数据完整性...\n";
    $sql = "SELECT m.id, m.model_code, m.model_name, m.endpoint_url, m.is_active,
                   p.provider_name, t.type_name 
            FROM `{$modelTable}` m 
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id 
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id 
            WHERE m.model_code LIKE 'pixverse/%' AND m.is_active = 1
            ORDER BY m.sort";
    $result = $mysqli->query($sql);
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
        echo "   ✓ [{$row['id']}] {$row['model_code']} - {$row['model_name']}\n";
        echo "     供应商: {$row['provider_name']}, 类型: {$row['type_name']}\n";
        echo "     端点: {$row['endpoint_url']}\n";
    }
    
    echo "\n========================================\n";
    echo "✅ 爱诗科技模型接入迁移完成！共 {$count} 个活跃模型\n";
    echo "========================================\n\n";
    echo "后续操作：\n";
    echo "1. 在管理后台「系统设置 > API Key管理」中配置DashScope API Key\n";
    echo "2. 在模型广场中确认模型已正确显示\n";
    echo "3. 测试三个模型的视频生成功能\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

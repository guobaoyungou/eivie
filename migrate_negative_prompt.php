<?php
/**
 * 迁移脚本：为 SeeDream 5.0 Lite 和 4.5 模型添加 negative_prompt 默认值配置
 * 
 * 1. 更新 ddwx_model_info.input_schema：在 prompt 参数之后插入 negative_prompt 参数定义
 * 2. 条件性插入 ddwx_ai_model_parameter：仅当模型已有参数记录时新增 negative_prompt 行
 * 
 * 执行方式: php migrate_negative_prompt.php
 * 执行后需重启队列 worker: seedream_generation, ai_image_generation
 */

define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

$dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 默认负面提示词
$defaultNegativePrompt = '低画质，模糊，变形，五官扭曲，肢体畸形，手指残缺，比例失调，水印，文字，logo，卡通，动漫，插画，油画，3D渲染，过曝，过暗，噪点，瑕疵';

// negative_prompt 参数定义（将插入到 parameters 数组中 prompt 之后）
$negativePromptParam = [
    'name'        => 'negative_prompt',
    'type'        => 'string',
    'label'       => '负面提示词',
    'required'    => false,
    'default'     => $defaultNegativePrompt,
    'description' => '描述不希望出现在生成图像中的元素，多个元素用中文逗号分隔',
];

// 目标模型
$models = [
    ['model_code' => 'doubao-seedream-5-0-260128', 'id' => 1],
    ['model_code' => 'doubao-seedream-4-5-251128', 'id' => 2],
];

echo "=== 开始迁移：添加 negative_prompt 默认值配置 ===\n\n";

// ==================== Step 1: 更新 input_schema ====================
foreach ($models as $m) {
    $code = $m['model_code'];
    $modelId = $m['id'];

    echo "--- 处理模型: {$code} (ID={$modelId}) ---\n";

    // 读取当前 input_schema
    $stmt = $pdo->prepare("SELECT input_schema FROM {$config['prefix']}model_info WHERE model_code = ? AND id = ?");
    $stmt->execute([$code, $modelId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo "  [跳过] 未找到模型记录\n\n";
        continue;
    }

    $schema = json_decode($row['input_schema'], true);
    if (!$schema || !isset($schema['parameters'])) {
        echo "  [跳过] input_schema 无效或无 parameters\n\n";
        continue;
    }

    // 检查是否已存在 negative_prompt
    $exists = false;
    foreach ($schema['parameters'] as $p) {
        if (($p['name'] ?? '') === 'negative_prompt') {
            $exists = true;
            break;
        }
    }

    if ($exists) {
        echo "  [跳过] input_schema 中已存在 negative_prompt 参数\n\n";
        continue;
    }

    // 在 prompt 之后插入 negative_prompt
    $newParams = [];
    $inserted = false;
    foreach ($schema['parameters'] as $p) {
        $newParams[] = $p;
        if (($p['name'] ?? '') === 'prompt' && !$inserted) {
            $newParams[] = $negativePromptParam;
            $inserted = true;
        }
    }
    // 兜底：如果没有 prompt 参数，追加到末尾
    if (!$inserted) {
        $newParams[] = $negativePromptParam;
    }

    $schema['parameters'] = $newParams;
    $newJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $updateStmt = $pdo->prepare("UPDATE {$config['prefix']}model_info SET input_schema = ? WHERE model_code = ? AND id = ?");
    $updateStmt->execute([$newJson, $code, $modelId]);

    echo "  [完成] input_schema 已更新，在 prompt 之后插入 negative_prompt 参数\n";
    echo "  默认值: {$defaultNegativePrompt}\n\n";
}

// ==================== Step 2: 条件性插入 ai_model_parameter ====================
echo "--- 检查 ai_model_parameter 表 ---\n";

foreach ($models as $m) {
    $modelId = $m['id'];
    $code = $m['model_code'];

    // 检查模型是否在 ai_model_parameter 表中有记录
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$config['prefix']}ai_model_parameter WHERE model_id = ?");
    $countStmt->execute([$modelId]);
    $count = (int)$countStmt->fetchColumn();

    if ($count === 0) {
        echo "  模型 {$code} (ID={$modelId}): 无参数记录（兜底模式），跳过\n";
        continue;
    }

    // 检查是否已有 negative_prompt 记录
    $existStmt = $pdo->prepare("SELECT COUNT(*) FROM {$config['prefix']}ai_model_parameter WHERE model_id = ? AND param_name = 'negative_prompt'");
    $existStmt->execute([$modelId]);
    $existCount = (int)$existStmt->fetchColumn();

    if ($existCount > 0) {
        // 已有记录，更新 default_value 确保默认值生效
        $updateParamStmt = $pdo->prepare("UPDATE {$config['prefix']}ai_model_parameter SET default_value = ? WHERE model_id = ? AND param_name = 'negative_prompt' AND (default_value IS NULL OR default_value = '')");
        $updateParamStmt->execute([$defaultNegativePrompt, $modelId]);
        $affected = $updateParamStmt->rowCount();
        if ($affected > 0) {
            echo "  模型 {$code} (ID={$modelId}): ai_model_parameter 已有记录，已更新 default_value\n";
        } else {
            echo "  模型 {$code} (ID={$modelId}): ai_model_parameter 已有记录且 default_value 已设置，跳过\n";
        }
        continue;
    }

    // 插入 negative_prompt 参数行
    $insertStmt = $pdo->prepare("INSERT INTO {$config['prefix']}ai_model_parameter (model_id, param_name, param_type, param_label, is_required, default_value, description, data_format, sort, create_time) VALUES (?, 'negative_prompt', 'string', '负面提示词', 0, ?, '描述不希望出现在生成图像中的元素，多个元素用中文逗号分隔', 'text', 41, NOW())");
    $insertStmt->execute([$modelId, $defaultNegativePrompt]);

    echo "  模型 {$code} (ID={$modelId}): 已插入 ai_model_parameter 记录\n";
}

echo "\n=== 迁移完成 ===\n";
echo "请重启队列 worker 以使新配置生效:\n";
echo "  php think queue:restart\n";
echo "  或手动重启 seedream_generation / ai_image_generation 队列\n";

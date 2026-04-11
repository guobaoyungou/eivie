<?php
/**
 * Ollama 本地LLM - 数据库迁移脚本
 * 
 * 功能：
 * 1. 创建 Ollama 供应商（model_provider）
 * 2. 确认文本生成、深度思考、向量模型类型
 * 3. 创建 Ollama 模型记录（model_info）：qwen3:8b, llama3.1:8b, deepseek-r1:8b, gemma3:12b, nomic-embed-text
 * 
 * 执行方式: php migrate_ollama.php
 * 
 * @date 2026-04-08
 */

$config = include(__DIR__ . '/config.php');

$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "Ollama 本地LLM - 数据库迁移\n";
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
    echo "1. 检查供应商：Ollama...\n";
    $stmt = $mysqli->prepare("SELECT id FROM `{$providerTable}` WHERE `provider_code` = 'ollama'");
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();
    
    if ($provider) {
        $providerId = $provider['id'];
        echo "   ✓ 供应商已存在 (ID={$providerId})\n";
    } else {
        $authConfig = json_encode([
            'type' => 'none',
            'note' => 'Ollama本地部署，无需API Key认证。请确保Ollama服务已启动（默认端口11434）',
            'fields' => [
                ['name' => 'api_key', 'label' => '服务地址', 'type' => 'text', 'required' => false, 'placeholder' => 'http://127.0.0.1:11434', 'description' => '可选，留空使用默认地址 http://127.0.0.1:11434']
            ]
        ], JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO `{$providerTable}` 
                (`aid`, `provider_code`, `provider_name`, `logo`, `website`, `api_doc_url`, 
                 `description`, `auth_config`, `is_system`, `status`, `sort`, `create_time`, `update_time`) 
                VALUES 
                (0, 'ollama', 'Ollama（本地LLM）', '', 'https://ollama.com', 
                 'https://github.com/ollama/ollama/blob/main/docs/api.md',
                 'Ollama是本地大语言模型运行引擎，支持在本地部署和运行Qwen、Llama、DeepSeek、Gemma等开源模型。无需API Key，完全离线运行，数据不出本地。',
                 ?, 0, 1, 20, ?, ?)";
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
    echo "\n2. 检查模型类型...\n";
    
    // 文本生成
    $stmt = $mysqli->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'text_generation'");
    $stmt->execute();
    $result = $stmt->get_result();
    $textType = $result->fetch_assoc();
    $stmt->close();
    if (!$textType) { throw new Exception("模型类型 'text_generation' 不存在"); }
    $textTypeId = $textType['id'];
    echo "   ✓ 文本生成类型 (ID={$textTypeId})\n";
    
    // 深度思考
    $stmt = $mysqli->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'deep_thinking'");
    $stmt->execute();
    $result = $stmt->get_result();
    $thinkType = $result->fetch_assoc();
    $stmt->close();
    if (!$thinkType) { throw new Exception("模型类型 'deep_thinking' 不存在"); }
    $thinkTypeId = $thinkType['id'];
    echo "   ✓ 深度思考类型 (ID={$thinkTypeId})\n";
    
    // 向量模型
    $stmt = $mysqli->prepare("SELECT id FROM `{$typeTable}` WHERE `type_code` = 'embedding'");
    $stmt->execute();
    $result = $stmt->get_result();
    $embedType = $result->fetch_assoc();
    $stmt->close();
    if (!$embedType) { throw new Exception("模型类型 'embedding' 不存在"); }
    $embedTypeId = $embedType['id'];
    echo "   ✓ 向量模型类型 (ID={$embedTypeId})\n";

    // ============================================================
    // 3. 定义模型列表
    // ============================================================
    $endpointUrl = 'http://127.0.0.1:11434';
    
    $models = [
        [
            'model_code' => 'qwen3:8b',
            'model_name' => 'Qwen3 8B（本地）',
            'model_version' => '3.0',
            'description' => '通义千问Qwen3 8B本地部署版，支持中英文对话、文本生成、代码辅助、知识问答等通用任务。32K上下文窗口，本地运行数据不出境，完全免费。推荐8GB以上显存或16GB内存运行。',
            'type_id' => $textTypeId,
            'task_type' => 'sync',
            'input_schema' => json_encode([
                'required' => ['prompt'],
                'properties' => [
                    'prompt' => ['type' => 'text', 'label' => '提示词', 'order' => 0, 'required' => true, 'description' => '输入你的问题或指令'],
                    'system_prompt' => ['type' => 'text', 'label' => '系统提示词', 'order' => 1, 'required' => false, 'default' => '', 'description' => '设定AI的角色和行为（可选）'],
                    'temperature' => ['type' => 'float', 'label' => '温度', 'order' => 2, 'required' => false, 'default' => 0.7, 'description' => '控制随机性，0=确定性，1=更多创造性'],
                    'max_tokens' => ['type' => 'integer', 'label' => '最大输出长度', 'order' => 3, 'required' => false, 'default' => 4096, 'description' => '最大生成token数']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'output_schema' => json_encode([
                'type' => 'text',
                'format' => 'markdown',
                'fields' => ['content', 'total_duration', 'eval_count']
            ], JSON_UNESCAPED_UNICODE),
            'pricing_config' => json_encode([
                'billing_mode' => 'free',
                'unit' => '次',
                'price_per_call' => 0,
                'note' => '本地部署，完全免费'
            ], JSON_UNESCAPED_UNICODE),
            'limits_config' => json_encode([
                'timeout' => 120,
                'max_tokens' => 32768,
                'context_length' => 32768,
                'max_retry' => 2
            ], JSON_UNESCAPED_UNICODE),
            'capability_tags' => json_encode(['中文对话', '英文对话', '代码生成', '文本写作', '知识问答', '本地部署', '免费', 'Qwen3'], JSON_UNESCAPED_UNICODE),
            'sort' => 30,
        ],
        [
            'model_code' => 'llama3.1:8b',
            'model_name' => 'Llama 3.1 8B（本地）',
            'model_version' => '3.1',
            'description' => 'Meta Llama 3.1 8B本地部署版，128K超长上下文支持，擅长英文对话、代码生成、逻辑推理。本地运行零费用，适合开发调试和长文本处理场景。',
            'type_id' => $textTypeId,
            'task_type' => 'sync',
            'input_schema' => json_encode([
                'required' => ['prompt'],
                'properties' => [
                    'prompt' => ['type' => 'text', 'label' => '提示词', 'order' => 0, 'required' => true, 'description' => '输入你的问题或指令'],
                    'system_prompt' => ['type' => 'text', 'label' => '系统提示词', 'order' => 1, 'required' => false, 'default' => '', 'description' => '设定AI的角色和行为（可选）'],
                    'temperature' => ['type' => 'float', 'label' => '温度', 'order' => 2, 'required' => false, 'default' => 0.7, 'description' => '控制随机性'],
                    'max_tokens' => ['type' => 'integer', 'label' => '最大输出长度', 'order' => 3, 'required' => false, 'default' => 4096, 'description' => '最大生成token数']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'output_schema' => json_encode(['type' => 'text', 'format' => 'markdown', 'fields' => ['content', 'total_duration', 'eval_count']], JSON_UNESCAPED_UNICODE),
            'pricing_config' => json_encode(['billing_mode' => 'free', 'unit' => '次', 'price_per_call' => 0, 'note' => '本地部署，完全免费'], JSON_UNESCAPED_UNICODE),
            'limits_config' => json_encode(['timeout' => 120, 'max_tokens' => 131072, 'context_length' => 131072, 'max_retry' => 2], JSON_UNESCAPED_UNICODE),
            'capability_tags' => json_encode(['英文对话', '代码生成', '逻辑推理', '长上下文', '本地部署', '免费', 'Llama'], JSON_UNESCAPED_UNICODE),
            'sort' => 31,
        ],
        [
            'model_code' => 'deepseek-r1:8b',
            'model_name' => 'DeepSeek R1 8B（本地）',
            'model_version' => 'R1',
            'description' => 'DeepSeek R1 8B本地部署版，专注深度推理与复杂问题求解，支持链式思考（Chain of Thought）。适合数学证明、编程调试、逻辑分析等需要多步推理的任务。',
            'type_id' => $thinkTypeId,
            'task_type' => 'sync',
            'input_schema' => json_encode([
                'required' => ['prompt'],
                'properties' => [
                    'prompt' => ['type' => 'text', 'label' => '提示词', 'order' => 0, 'required' => true, 'description' => '输入需要深度推理的问题'],
                    'system_prompt' => ['type' => 'text', 'label' => '系统提示词', 'order' => 1, 'required' => false, 'default' => '', 'description' => '设定AI的角色和行为（可选）'],
                    'temperature' => ['type' => 'float', 'label' => '温度', 'order' => 2, 'required' => false, 'default' => 0.6, 'description' => '推理任务建议使用较低温度'],
                    'max_tokens' => ['type' => 'integer', 'label' => '最大输出长度', 'order' => 3, 'required' => false, 'default' => 8192, 'description' => '推理输出可能较长，建议设置较大值']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'output_schema' => json_encode(['type' => 'text', 'format' => 'markdown', 'fields' => ['content', 'total_duration', 'eval_count']], JSON_UNESCAPED_UNICODE),
            'pricing_config' => json_encode(['billing_mode' => 'free', 'unit' => '次', 'price_per_call' => 0, 'note' => '本地部署，完全免费'], JSON_UNESCAPED_UNICODE),
            'limits_config' => json_encode(['timeout' => 180, 'max_tokens' => 65536, 'context_length' => 65536, 'max_retry' => 2], JSON_UNESCAPED_UNICODE),
            'capability_tags' => json_encode(['深度推理', '链式思考', '数学', '编程调试', '逻辑分析', '本地部署', '免费', 'DeepSeek'], JSON_UNESCAPED_UNICODE),
            'sort' => 32,
        ],
        [
            'model_code' => 'gemma3:12b',
            'model_name' => 'Gemma 3 12B（本地）',
            'model_version' => '3.0',
            'description' => 'Google Gemma 3 12B本地部署版，支持多语言对话、文本理解与生成。128K上下文窗口，适合翻译、摘要、多语言场景。',
            'type_id' => $textTypeId,
            'task_type' => 'sync',
            'input_schema' => json_encode([
                'required' => ['prompt'],
                'properties' => [
                    'prompt' => ['type' => 'text', 'label' => '提示词', 'order' => 0, 'required' => true, 'description' => '输入你的问题或指令'],
                    'system_prompt' => ['type' => 'text', 'label' => '系统提示词', 'order' => 1, 'required' => false, 'default' => '', 'description' => '设定AI的角色和行为（可选）'],
                    'temperature' => ['type' => 'float', 'label' => '温度', 'order' => 2, 'required' => false, 'default' => 0.7, 'description' => '控制随机性'],
                    'max_tokens' => ['type' => 'integer', 'label' => '最大输出长度', 'order' => 3, 'required' => false, 'default' => 4096, 'description' => '最大生成token数']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'output_schema' => json_encode(['type' => 'text', 'format' => 'markdown', 'fields' => ['content', 'total_duration', 'eval_count']], JSON_UNESCAPED_UNICODE),
            'pricing_config' => json_encode(['billing_mode' => 'free', 'unit' => '次', 'price_per_call' => 0, 'note' => '本地部署，完全免费'], JSON_UNESCAPED_UNICODE),
            'limits_config' => json_encode(['timeout' => 120, 'max_tokens' => 131072, 'context_length' => 131072, 'max_retry' => 2], JSON_UNESCAPED_UNICODE),
            'capability_tags' => json_encode(['多语言', '翻译', '摘要', '文本理解', '本地部署', '免费', 'Gemma'], JSON_UNESCAPED_UNICODE),
            'sort' => 33,
        ],
        [
            'model_code' => 'nomic-embed-text',
            'model_name' => 'Nomic Embed Text（本地）',
            'model_version' => '1.5',
            'description' => 'Nomic Embed Text本地向量模型，768维文本嵌入，适合语义搜索、RAG检索增强、文本相似度计算。8K上下文，本地部署零费用。',
            'type_id' => $embedTypeId,
            'task_type' => 'sync',
            'input_schema' => json_encode([
                'required' => ['prompt'],
                'properties' => [
                    'prompt' => ['type' => 'text', 'label' => '输入文本', 'order' => 0, 'required' => true, 'description' => '需要生成向量的文本']
                ]
            ], JSON_UNESCAPED_UNICODE),
            'output_schema' => json_encode(['type' => 'embedding', 'format' => 'array', 'dimensions' => 768], JSON_UNESCAPED_UNICODE),
            'pricing_config' => json_encode(['billing_mode' => 'free', 'unit' => '次', 'price_per_call' => 0, 'note' => '本地部署，完全免费'], JSON_UNESCAPED_UNICODE),
            'limits_config' => json_encode(['timeout' => 30, 'max_tokens' => 8192, 'context_length' => 8192, 'max_retry' => 2], JSON_UNESCAPED_UNICODE),
            'capability_tags' => json_encode(['文本嵌入', '语义搜索', 'RAG', '向量化', '本地部署', '免费'], JSON_UNESCAPED_UNICODE),
            'sort' => 34,
        ],
    ];

    // ============================================================
    // 4. 创建/更新模型记录
    // ============================================================
    echo "\n3. 创建模型记录...\n";
    
    foreach ($models as $m) {
        echo "\n   模型: {$m['model_code']} ({$m['model_name']})...\n";
        
        $stmt = $mysqli->prepare("SELECT id FROM `{$modelTable}` WHERE `model_code` = ?");
        $stmt->bind_param('s', $m['model_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            $modelId = $existing['id'];
            $sql = "UPDATE `{$modelTable}` SET 
                    `model_name` = ?, `model_version` = ?, `description` = ?, `endpoint_url` = ?,
                    `provider_id` = ?, `type_id` = ?, `task_type` = ?,
                    `input_schema` = ?, `output_schema` = ?, `pricing_config` = ?, 
                    `limits_config` = ?, `capability_tags` = ?,
                    `sort` = ?, `is_active` = 1, `update_time` = ?
                    WHERE `id` = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ssssissssssiiii',
                $m['model_name'], $m['model_version'], $m['description'], $endpointUrl,
                $providerId, $m['type_id'], $m['task_type'],
                $m['input_schema'], $m['output_schema'], $m['pricing_config'],
                $m['limits_config'], $m['capability_tags'],
                $m['sort'], $time, $modelId
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
                $providerId, $m['type_id'], $m['model_code'], $m['model_name'], $m['model_version'], $m['description'],
                $endpointUrl, $m['task_type'], $m['input_schema'], $m['output_schema'], $m['pricing_config'], $m['limits_config'],
                $m['capability_tags'], $m['sort'], $time, $time
            );
            if ($stmt->execute()) {
                $modelId = $mysqli->insert_id;
                echo "   ✓ 创建成功 (ID={$modelId})\n";
            } else {
                echo "   ✗ 创建失败: " . $mysqli->error . "\n";
            }
            $stmt->close();
        }
    }

    // ============================================================
    // 5. 验证
    // ============================================================
    echo "\n4. 验证数据完整性...\n";
    $codes = "'qwen3:8b','llama3.1:8b','deepseek-r1:8b','gemma3:12b','nomic-embed-text'";
    $sql = "SELECT m.id, m.model_code, m.model_name, m.endpoint_url, m.task_type,
                   p.provider_name, t.type_name
            FROM `{$modelTable}` m
            LEFT JOIN `{$providerTable}` p ON m.provider_id = p.id
            LEFT JOIN `{$typeTable}` t ON m.type_id = t.id
            WHERE m.model_code IN ({$codes}) AND m.is_active = 1
            ORDER BY m.sort";
    $result = $mysqli->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        echo "   ✓ [{$row['id']}] {$row['model_code']} - {$row['model_name']}\n";
        echo "     供应商: {$row['provider_name']}\n";
        echo "     类型: {$row['type_name']}\n";
        echo "     端点: {$row['endpoint_url']}\n\n";
    }
    
    echo "========================================\n";
    echo "✅ Ollama 本地LLM 模型接入迁移完成！\n";
    echo "========================================\n\n";
    echo "已注册模型：\n";
    echo "  - qwen3:8b        (文本生成, 通义千问3 8B)\n";
    echo "  - llama3.1:8b     (文本生成, Meta Llama 3.1 8B)\n";
    echo "  - deepseek-r1:8b  (深度思考, DeepSeek R1 8B)\n";
    echo "  - gemma3:12b      (文本生成, Google Gemma 3 12B)\n";
    echo "  - nomic-embed-text(向量模型, Nomic Embed Text)\n\n";
    echo "使用前准备：\n";
    echo "1. 安装Ollama: curl -fsSL https://ollama.com/install.sh | sh\n";
    echo "2. 拉取模型: ollama pull qwen3:8b\n";
    echo "3. 确保Ollama服务运行: ollama serve（默认端口11434）\n";
    echo "4. 在系统设置中添加Ollama供应商的API Key配置（可留空使用默认地址）\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

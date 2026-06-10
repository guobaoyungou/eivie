-- ============================================================
-- 模型广场预置模型 - 豆包Seed 2.0 Mini
-- 版本: 1.0.0
-- 创建时间: 2026-06-04
-- 描述: 添加豆包Seed 2.0 Mini大语言模型
--       - doubao-seed-2-0-mini-260428 (文本生成/深度思考)
-- API: Chat Completions (OpenAI兼容)
-- 端点: https://ark.cn-beijing.volces.com/api/v3/chat/completions
-- ============================================================

START TRANSACTION;

-- 获取火山引擎供应商ID
SET @volcengine_provider_id = (SELECT id FROM ddwx_model_provider WHERE provider_code = 'volcengine' LIMIT 1);

-- 获取文本生成类型ID
SET @text_gen_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'text_generation' LIMIT 1);

-- 当前时间戳
SET @now = UNIX_TIMESTAMP();

-- ============================================================
-- doubao-seed-2-0-mini-260428 - 豆包Seed 2.0 Mini
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @text_gen_type_id,
    'doubao-seed-2-0-mini-260428',
    '豆包Seed 2.0 Mini',
    'v2.0-mini-260428',
    '豆包Seed 2.0 Mini是火山引擎推出的轻量级大语言模型，支持深度思考模式（thinking），具备多轮对话、长文本理解能力。支持文本生成、代码编写、内容创作、数据分析等多种任务场景。支持max_completion_tokens超长输出（最高65536 tokens），适合复杂问题的深度推理。',
    JSON_OBJECT(
        'type', 'object',
        'properties', JSON_OBJECT(
            'prompt', JSON_OBJECT(
                'type', 'string',
                'label', '提示词',
                'required', true,
                'description', '用户输入的问题或指令',
                'placeholder', '请输入您的问题或创作指令...',
                'ui_type', 'textarea'
            ),
            'system_prompt', JSON_OBJECT(
                'type', 'string',
                'label', '系统提示词',
                'required', false,
                'description', '定义助手角色、行为规范和输出格式',
                'placeholder', '例如：你是一个专业的编程助手...',
                'ui_type', 'textarea'
            ),
            'messages', JSON_OBJECT(
                'type', 'array',
                'label', '多轮对话消息',
                'required', false,
                'description', '多轮对话的消息历史，格式: [{"role":"user","content":"..."},{"role":"assistant","content":"..."}]',
                'ui_type', 'hidden'
            ),
            'thinking_type', JSON_OBJECT(
                'type', 'enum',
                'label', '深度思考模式',
                'required', false,
                'options', JSON_ARRAY('auto', 'enabled', 'disabled'),
                'default', 'auto',
                'description', 'auto=自动判断是否需要思考；enabled=强制先思考再回答；disabled=直接回答不思考'
            ),
            'reasoning_effort', JSON_OBJECT(
                'type', 'enum',
                'label', '思考程度',
                'required', false,
                'options', JSON_ARRAY('minimal', 'low', 'medium', 'high'),
                'default', 'medium',
                'description', '限制思考的工作量，minimal最低（快速响应），high最高（深度分析）'
            ),
            'max_tokens', JSON_OBJECT(
                'type', 'integer',
                'label', '最大输出Token数',
                'required', false,
                'min', 1,
                'max', 65536,
                'default', 4096,
                'description', '模型回答的最大token数（不含思维链）'
            ),
            'max_completion_tokens', JSON_OBJECT(
                'type', 'integer',
                'label', '最大完成Token数',
                'required', false,
                'min', 1,
                'max', 65536,
                'default', 65536,
                'description', '包含思维链+回答的总token上限，设置后max_tokens自动失效'
            ),
            'temperature', JSON_OBJECT(
                'type', 'float',
                'label', '采样温度',
                'required', false,
                'min', 0,
                'max', 2,
                'default', 1,
                'description', '控制输出随机性，0为确定输出，1为默认，2为最随机'
            ),
            'top_p', JSON_OBJECT(
                'type', 'float',
                'label', '核采样概率',
                'required', false,
                'min', 0,
                'max', 1,
                'default', 0.7,
                'description', '核采样概率阈值，值越大生成越随机'
            ),
            'service_tier', JSON_OBJECT(
                'type', 'enum',
                'label', '推理服务等级',
                'required', false,
                'options', JSON_ARRAY('auto', 'default', 'fast'),
                'default', 'auto',
                'description', 'auto=优先TPM保障包；default=常规模式；fast=优先低延迟'
            ),
            'stream', JSON_OBJECT(
                'type', 'boolean',
                'label', '流式输出',
                'required', false,
                'default', false,
                'description', '是否启用SSE流式输出'
            ),
            'response_format', JSON_OBJECT(
                'type', 'enum',
                'label', '回复格式',
                'required', false,
                'options', JSON_ARRAY('text', 'json_object'),
                'default', 'text',
                'description', 'text=纯文本；json_object=JSON对象格式'
            ),
            'stop', JSON_OBJECT(
                'type', 'string',
                'label', '停止词',
                'required', false,
                'description', '模型遇到此字符串时停止生成，最多4个，用逗号分隔',
                'ui_type', 'hidden'
            )
        )
    ),
    JSON_OBJECT(
        'type', 'object',
        'properties', JSON_OBJECT(
            'content', JSON_OBJECT(
                'type', 'string',
                'label', '回复内容',
                'path', '$.choices[0].message.content',
                'description', '模型生成的回答文本'
            ),
            'reasoning_content', JSON_OBJECT(
                'type', 'string',
                'label', '思维链内容',
                'path', '$.choices[0].message.reasoning_content',
                'description', '深度思考模式下的思维链内容'
            ),
            'finish_reason', JSON_OBJECT(
                'type', 'string',
                'label', '停止原因',
                'path', '$.choices[0].finish_reason',
                'description', 'stop=自然结束；length=达到长度限制'
            ),
            'total_tokens', JSON_OBJECT(
                'type', 'integer',
                'label', '总Token数',
                'path', '$.usage.total_tokens',
                'description', '本次请求消耗的总token数'
            ),
            'prompt_tokens', JSON_OBJECT(
                'type', 'integer',
                'label', '输入Token数',
                'path', '$.usage.prompt_tokens',
                'description', '输入内容消耗的token数'
            ),
            'completion_tokens', JSON_OBJECT(
                'type', 'integer',
                'label', '输出Token数',
                'path', '$.usage.completion_tokens',
                'description', '输出内容消耗的token数'
            ),
            'reasoning_tokens', JSON_OBJECT(
                'type', 'integer',
                'label', '思维链Token数',
                'path', '$.usage.completion_tokens_details.reasoning_tokens',
                'description', '思维链内容消耗的token数'
            )
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/chat/completions',
    JSON_OBJECT(
        'billing_mode', 'per_token',
        'cost_price', 0.0005,
        'suggested_price', 0.002,
        'currency', 'CNY',
        'unit', 'per_1k_tokens',
        'input_price', 0.0005,
        'output_price', 0.002,
        'thinking_price', 0.002
    ),
    JSON_OBJECT(
        'max_context_length', 131072,
        'max_completion_tokens', 65536,
        'concurrent_limit', 50,
        'timeout', 300,
        'stream_support', true,
        'thinking_support', true
    ),
    'sync',
    JSON_ARRAY('深度思考', '多轮对话', '长文本', '代码生成', '内容创作'),
    1,
    1,
    25,
    @now,
    @now
) ON DUPLICATE KEY UPDATE
    `model_name` = VALUES(`model_name`),
    `model_version` = VALUES(`model_version`),
    `description` = VALUES(`description`),
    `input_schema` = VALUES(`input_schema`),
    `output_schema` = VALUES(`output_schema`),
    `pricing_config` = VALUES(`pricing_config`),
    `limits_config` = VALUES(`limits_config`),
    `capability_tags` = VALUES(`capability_tags`),
    `update_time` = @now;

COMMIT;

-- ============================================================
-- 验证查询
-- ============================================================
SELECT 
    m.id,
    m.model_code,
    m.model_name,
    m.model_version,
    p.provider_name,
    t.type_name,
    m.task_type,
    m.is_system,
    m.is_active
FROM ddwx_model_info m
LEFT JOIN ddwx_model_provider p ON m.provider_id = p.id
LEFT JOIN ddwx_model_type t ON m.type_id = t.id
WHERE m.model_code = 'doubao-seed-2-0-mini-260428';

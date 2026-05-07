-- ============================================================
-- NEWAPI中转平台接入 - 数据库脚本
-- hfsyapi.cn (New API)
-- 
-- 使用说明：
-- 1. 先执行此SQL脚本添加供应商和模型
-- 2. 在系统管理 - API Key管理 中添加API Key
-- 3. 配置时使用hfsyapi.cn提供的接口地址作为custom_endpoint
-- ============================================================

-- 1. 添加NEWAPI供应商
INSERT INTO `ddwx_model_provider` 
(`aid`, `provider_code`, `provider_name`, `logo`, `website`, `api_doc_url`, `description`, `auth_config`, `is_system`, `status`, `sort`, `create_time`, `update_time`) 
VALUES 
(0, 'newapi', 'NEWAPI中转', 'https://www.hfsyapi.cn/logo.png', 'https://www.hfsyapi.cn', 'https://www.hfsyapi.cn/docs/api', 'NEWAPI新一代大模型网关，支持OpenAI兼容格式的图像生成、文生图、图生图等AI能力', '{"fields":[{"name":"api_key","label":"API Key","type":"text","required":true}]}', 1, 1, 55, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
`provider_name` = VALUES(`provider_name`),
`logo` = VALUES(`logo`),
`website` = VALUES(`website`),
`api_doc_url` = VALUES(`api_doc_url`),
`description` = VALUES(`description`),
`auth_config` = VALUES(`auth_config`),
`update_time` = UNIX_TIMESTAMP();

-- 2. 获取NEWAPI供应商ID
SET @newapi_provider_id = (SELECT id FROM `ddwx_model_provider` WHERE `provider_code` = 'newapi' LIMIT 1);

-- 3. 获取图片生成类型ID
SET @image_type_id = (SELECT id FROM `ddwx_model_type` WHERE `type_code` = 'image_generation' LIMIT 1);

-- 4. 添加GPT-Image-2 模型 (文生图)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES 
(0, @newapi_provider_id, @image_type_id, 'gpt-image-2', 'GPT-Image-2', '2.0', 'OpenAI最新图像生成模型GPT-Image-2，支持4K高清输出、原生中文/日文/韩文文字渲染、精确的参考图保持，适用于高质量图像创作和编辑', '{"prompt":"string","n":"integer","size":"string","quality":"string","response_format":"string"}', '{"image_url":"string","revised_prompt":"string"}', '/v1/images/generations', '{"input_tokens":0.01,"output_tokens":0}', '{"max_tokens":4000,"max_images":10}', 'sync', '["text_to_image","image_edit","high_quality","4k","multilingual"]', 1, 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
`model_name` = VALUES(`model_name`),
`description` = VALUES(`description`),
`endpoint_url` = VALUES(`endpoint_url`),
`capability_tags` = VALUES(`capability_tags`),
`update_time` = UNIX_TIMESTAMP();

-- 5. 添加GPT-Image-1.5 模型 (文生图，兼容性好)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES 
(0, @newapi_provider_id, @image_type_id, 'gpt-image-1.5', 'GPT-Image-1.5', '1.5', 'OpenAI图像生成模型GPT-Image-1.5，相比1.0版本在语义理解、细节表达方面有显著提升，支持文生图和基础图像编辑', '{"prompt":"string","n":"integer","size":"string"}', '{"image_url":"string","revised_prompt":"string"}', '/v1/images/generations', '{"input_tokens":0.005,"output_tokens":0}', '{"max_tokens":2000,"max_images":10}', 'sync', '["text_to_image","image_edit","standard"]', 1, 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
`model_name` = VALUES(`model_name`),
`description` = VALUES(`description`),
`endpoint_url` = VALUES(`endpoint_url`),
`update_time` = UNIX_TIMESTAMP();

-- 6. 添加DALL-E-3 模型 (可选)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES 
(0, @newapi_provider_id, @image_type_id, 'dall-e-3', 'DALL-E-3', '3.0', 'OpenAI DALL-E 3图像生成模型，擅长根据文字描述生成精美、准确的图像', '{"prompt":"string","n":"integer","size":"string","quality":"string"}', '{"image_url":"string","revised_prompt":"string"}', '/v1/images/generations', '{"input_tokens":0.04,"output_tokens":0}', '{"max_tokens":4000,"max_images":1}', 'sync', '["text_to_image","high_quality","dall_e"]', 1, 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
`model_name` = VALUES(`model_name`),
`description` = VALUES(`description`),
`endpoint_url` = VALUES(`endpoint_url`),
`update_time` = UNIX_TIMESTAMP();

-- 7. 添加通用GPT-Image模型 (动态路由)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES 
(0, @newapi_provider_id, @image_type_id, 'gpt-image', 'GPT-Image(通用)', 'latest', '通用GPT-Image模型，支持动态路由到最新版本GPT-Image模型，适用于一般图像生成需求', '{"prompt":"string","n":"integer","size":"string","quality":"string"}', '{"image_url":"string","revised_prompt":"string"}', '/v1/images/generations', '{"input_tokens":0.01,"output_tokens":0}', '{"max_tokens":4000,"max_images":10}', 'sync', '["text_to_image","image_edit","dynamic"]', 1, 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE 
`model_name` = VALUES(`model_name`),
`description` = VALUES(`description`),
`endpoint_url` = VALUES(`endpoint_url`),
`update_time` = UNIX_TIMESTAMP();

-- ============================================================
-- 执行完成提示
-- ============================================================
SELECT 'NEWAPI供应商和模型添加完成!' AS result;
SELECT p.id AS provider_id, p.provider_code, p.provider_name, m.id AS model_id, m.model_code, m.model_name 
FROM `ddwx_model_provider` p 
LEFT JOIN `ddwx_model_info` m ON m.provider_id = p.id 
WHERE p.provider_code = 'newapi' 
ORDER BY m.sort ASC;

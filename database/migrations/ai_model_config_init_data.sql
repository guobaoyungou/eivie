-- ============================================
-- AI模型配置功能初始化数据
-- 创建时间: 2026-02-04
-- 功能: 初始化qwen-image-edit-max示例数据
-- ============================================

-- 1. 插入模型实例配置
INSERT INTO `ddwx_ai_model_instance` (
  `id`, `aid`, `category_code`, `model_code`, `model_name`, `model_version`, 
  `provider`, `description`, `capability_tags`, `is_system`, `is_active`, 
  `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) VALUES (
  1, 0, 'image_generation', 'qwen-image-edit-max', '通义千问图像编辑增强版', 'v1.0',
  'aliyun', '基于通义千问的高级图像编辑模型，支持参考图、遮罩图、提示词三种输入方式，实现精准图像局部编辑',
  '["图像编辑","抠图","背景替换","局部修改"]', 1, 1, 100, 0.0500, 'per_image', 'fixed',
  UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 2. 插入参数定义
INSERT INTO `ddwx_ai_model_parameter` (
  `model_id`, `param_name`, `param_label`, `param_type`, `data_format`, 
  `is_required`, `default_value`, `value_range`, `enum_options`, 
  `description`, `validation_rule`, `sort`
) VALUES
-- reference_image
(1, 'reference_image', '参考图像', 'string', 'url_or_base64', 1, NULL, 
 '{"type":"url_or_base64"}', NULL, '原始输入图像，支持URL或Base64格式', '', 10),
-- mask_image
(1, 'mask_image', '遮罩图像', 'string', 'url_or_base64', 0, NULL, 
 '{"type":"url_or_base64"}', NULL, '指定编辑区域的遮罩图，白色区域为编辑区域', '', 20),
-- prompt
(1, 'prompt', '提示词', 'string', 'text', 1, NULL, 
 '{"max_length":500}', NULL, '描述期望的编辑效果，最大500字符', '', 30),
-- negative_prompt
(1, 'negative_prompt', '负面提示词', 'string', 'text', 0, NULL, 
 '{"max_length":200}', NULL, '描述不希望出现的元素，最大200字符', '', 40),
-- edit_mode
(1, 'edit_mode', '编辑模式', 'string', 'enum', 0, '"auto"', 
 NULL, '["auto","inpaint","outpaint","replace"]', '编辑模式选择：auto=自动检测，inpaint=内部修复，outpaint=外部扩展，replace=完全替换', '', 50),
-- strength
(1, 'strength', '编辑强度', 'float', 'number', 0, '0.7', 
 '{"min":0.0,"max":1.0}', NULL, '编辑效果强度，范围0.0-1.0，越大效果越明显', '', 60),
-- guidance_scale
(1, 'guidance_scale', '引导系数', 'float', 'number', 0, '7.5', 
 '{"min":1.0,"max":20.0}', NULL, '提示词引导强度，范围1.0-20.0，越大越贴近提示词', '', 70),
-- num_inference_steps
(1, 'num_inference_steps', '推理步数', 'integer', 'number', 0, '50', 
 '{"min":20,"max":100}', NULL, '生成质量与速度平衡，步数越多质量越高但速度越慢', '', 80),
-- seed
(1, 'seed', '随机种子', 'integer', 'number', 0, '-1', 
 '{"min":-1}', NULL, '可复现性控制，-1为随机，正整数可重复生成相同结果', '', 90),
-- output_format
(1, 'output_format', '输出格式', 'string', 'enum', 0, '"png"', 
 NULL, '["png","jpg","webp"]', '结果图像格式', '', 100),
-- output_quality
(1, 'output_quality', '输出质量', 'integer', 'number', 0, '95', 
 '{"min":60,"max":100}', NULL, '图像质量百分比，范围60-100', '', 110);

-- 3. 插入响应定义
INSERT INTO `ddwx_ai_model_response` (
  `model_id`, `response_field`, `field_label`, `field_type`, 
  `field_path`, `is_critical`, `description`
) VALUES
(1, 'task_id', '任务ID', 'string', '$.output.task_id', 1, '异步任务唯一标识，用于查询任务状态和结果'),
(1, 'task_status', '任务状态', 'string', '$.output.task_status', 1, '任务状态：PENDING=待处理，RUNNING=处理中，SUCCEEDED=成功，FAILED=失败'),
(1, 'image_url', '结果图像URL', 'string', '$.output.results[0].url', 1, '生成的图像访问地址'),
(1, 'error_code', '错误代码', 'string', '$.code', 0, '失败时的错误码'),
(1, 'error_message', '错误信息', 'string', '$.message', 0, '失败时的错误描述'),
(1, 'cost_time', '耗时', 'integer', '$.output.usage.latency', 0, '生成耗时（毫秒）'),
(1, 'request_id', '请求ID', 'string', '$.request_id', 0, 'API请求追踪ID');

-- 4. 插入定价配置
INSERT INTO `ddwx_ai_model_pricing` (
  `model_id`, `aid`, `bid`, `cost_price`, `platform_price`, `merchant_price`,
  `platform_profit_rate`, `merchant_profit_rate`, `min_price`, `max_price`,
  `currency`, `price_type`, `is_active`, `effective_time`, `expire_time`,
  `remark`, `create_time`, `update_time`
) VALUES (
  1, 0, 0, 0.0500, 0.0800, 9.9000,
  60.00, 123.75, 0.1000, 99.0000,
  'CNY', 'image', 1, UNIX_TIMESTAMP('2024-01-01'), 0,
  '系统默认定价：成本价0.05元，平台售价0.08元，商家建议售价9.90元', 
  UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

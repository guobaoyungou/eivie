-- ============================================================
-- 旅拍功能拆分重构 - 照片生成/视频生成模块数据库表
-- 创建时间: 2026-02-27
-- ============================================================

-- 1. 生成记录表 ddwx_generation_record
CREATE TABLE IF NOT EXISTS `ddwx_generation_record` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
    `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
    `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `generation_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '生成类型：1=照片生成，2=视频生成',
    `model_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联模型ID（model_info.id）',
    `model_code` varchar(100) NOT NULL DEFAULT '' COMMENT '模型标识（冗余）',
    `scene_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联场景模板ID（可空）',
    `input_params` json DEFAULT NULL COMMENT '输入参数（结构化存储）',
    `output_type` varchar(50) NOT NULL DEFAULT '' COMMENT '输出类型：image/video',
    `task_id` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方任务ID',
    `status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '状态：0待处理/1处理中/2成功/3失败/4已取消',
    `retry_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '重试次数',
    `cost_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '耗时（毫秒）',
    `cost_tokens` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '消耗Token数',
    `cost_amount` decimal(10,4) NOT NULL DEFAULT 0.0000 COMMENT '成本金额',
    `error_code` varchar(50) NOT NULL DEFAULT '' COMMENT '错误码',
    `error_msg` text DEFAULT NULL COMMENT '错误信息',
    `queue_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '入队时间',
    `start_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '开始处理时间',
    `finish_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '完成时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_aid_bid` (`aid`, `bid`),
    KEY `idx_generation_type` (`generation_type`),
    KEY `idx_model_id` (`model_id`),
    KEY `idx_scene_id` (`scene_id`),
    KEY `idx_status` (`status`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生成记录表';

-- 2. 生成输出表 ddwx_generation_output
CREATE TABLE IF NOT EXISTS `ddwx_generation_output` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `record_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联生成记录ID',
    `output_type` varchar(50) NOT NULL DEFAULT '' COMMENT '输出类型：image/video/audio',
    `output_url` varchar(500) NOT NULL DEFAULT '' COMMENT '输出文件URL',
    `thumbnail_url` varchar(500) NOT NULL DEFAULT '' COMMENT '缩略图URL',
    `width` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '宽度（像素）',
    `height` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '高度（像素）',
    `duration` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '时长（毫秒，视频用）',
    `file_size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
    `file_format` varchar(20) NOT NULL DEFAULT '' COMMENT '文件格式',
    `metadata` json DEFAULT NULL COMMENT '扩展元数据',
    `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序序号',
    `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生成输出表';

-- 3. 场景模板表 ddwx_generation_scene_template
CREATE TABLE IF NOT EXISTS `ddwx_generation_scene_template` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
    `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
    `generation_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '生成类型：1=照片，2=视频',
    `source_record_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '来源生成记录ID',
    `template_name` varchar(200) NOT NULL DEFAULT '' COMMENT '模板名称',
    `template_code` varchar(100) NOT NULL DEFAULT '' COMMENT '模板标识',
    `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类标签',
    `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图',
    `description` text DEFAULT NULL COMMENT '模板描述',
    `model_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '绑定模型ID',
    `default_params` json DEFAULT NULL COMMENT '默认输入参数',
    `param_schema` json DEFAULT NULL COMMENT '参数配置schema',
    `is_public` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否公开',
    `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态：0禁用/1启用',
    `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
    `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_aid_bid` (`aid`, `bid`),
    KEY `idx_generation_type` (`generation_type`),
    KEY `idx_model_id` (`model_id`),
    KEY `idx_source_record_id` (`source_record_id`),
    KEY `idx_status` (`status`),
    KEY `idx_template_code` (`template_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景模板表';

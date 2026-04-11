-- ============================================================
-- AI 短剧可视化工作流创作系统 - 数据表定义
-- 创建时间: 2026-04-08
-- ============================================================

-- 1. 工作流项目表
CREATE TABLE IF NOT EXISTS `ddwx_workflow_project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
  `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '项目名称',
  `description` text COMMENT '项目描述',
  `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图URL',
  `creation_mode` enum('oneclick','freestyle','advanced') NOT NULL DEFAULT 'freestyle' COMMENT '创作模式',
  `template_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '预设模板ID（仅oneclick模式）',
  `status` enum('draft','running','completed','failed') NOT NULL DEFAULT 'draft' COMMENT '项目状态',
  `canvas_data` json DEFAULT NULL COMMENT '画布节点与连线的完整序列化数据',
  `output_video_url` varchar(500) NOT NULL DEFAULT '' COMMENT '最终成片URL',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_uid` (`uid`),
  KEY `idx_status` (`status`),
  KEY `idx_creation_mode` (`creation_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流项目表';

-- 2. 工作流节点实例表
CREATE TABLE IF NOT EXISTS `ddwx_workflow_node` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '节点实例ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
  `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `project_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '所属项目ID',
  `node_type` enum('script','character','storyboard','video','voice','compose') NOT NULL COMMENT '节点类型',
  `node_label` varchar(100) NOT NULL DEFAULT '' COMMENT '画布上的显示名',
  `position_x` int(11) NOT NULL DEFAULT 0 COMMENT '画布X坐标',
  `position_y` int(11) NOT NULL DEFAULT 0 COMMENT '画布Y坐标',
  `config_params` json DEFAULT NULL COMMENT '节点配置参数',
  `input_data` json DEFAULT NULL COMMENT '接收到的上游数据',
  `output_data` json DEFAULT NULL COMMENT '节点执行产出数据',
  `model_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '引用的模型广场模型ID',
  `status` enum('idle','configured','waiting','ready','running','polling','succeeded','failed') NOT NULL DEFAULT 'idle' COMMENT '节点执行状态',
  `error_message` text COMMENT '失败原因',
  `task_id` varchar(200) NOT NULL DEFAULT '' COMMENT '异步任务ID',
  `execute_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '最近执行时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_node_type` (`node_type`),
  KEY `idx_status` (`status`),
  KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流节点实例表';

-- 3. 节点连线表
CREATE TABLE IF NOT EXISTS `ddwx_workflow_edge` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '连线ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
  `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `project_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '所属项目ID',
  `source_node_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '上游节点ID',
  `target_node_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '下游节点ID',
  `source_port` varchar(50) NOT NULL DEFAULT '' COMMENT '输出端口名',
  `target_port` varchar(50) NOT NULL DEFAULT '' COMMENT '输入端口名',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_source_node` (`source_node_id`),
  KEY `idx_target_node` (`target_node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流节点连线表';

-- 4. 工作流资源表
CREATE TABLE IF NOT EXISTS `ddwx_workflow_resource` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '资源ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
  `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `resource_type` enum('character','style','voice','material') NOT NULL COMMENT '资源类型',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '资源名称',
  `thumbnail` varchar(500) NOT NULL DEFAULT '' COMMENT '缩略图URL',
  `content_data` json DEFAULT NULL COMMENT '资源详细数据',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统预置 1=系统 0=用户',
  `usage_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '使用次数统计',
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态 1=启用 0=停用',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_resource_type` (`resource_type`),
  KEY `idx_is_system` (`is_system`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流资源表';

-- 5. 角色身份卡表
CREATE TABLE IF NOT EXISTS `ddwx_workflow_character_id_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '身份卡ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
  `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `project_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '所属项目ID',
  `character_tag` varchar(100) NOT NULL DEFAULT '' COMMENT '角色唯一标签',
  `character_name` varchar(100) NOT NULL DEFAULT '' COMMENT '角色名称',
  `appearance_prompt` text COMMENT '结构化外貌描述词',
  `negative_prompt` text COMMENT '排除描述词',
  `reference_images` json DEFAULT NULL COMMENT '基准形象图URL数组',
  `face_embedding` blob COMMENT '面部特征向量',
  `style_seed` int(11) NOT NULL DEFAULT 0 COMMENT '风格一致性种子值',
  `consistency_threshold` decimal(3,2) NOT NULL DEFAULT 0.85 COMMENT '一致性阈值',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_character_tag` (`character_tag`),
  KEY `idx_aid_bid` (`aid`, `bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作流角色身份卡表';

-- 6. 预设短剧模板表
CREATE TABLE IF NOT EXISTS `ddwx_workflow_preset_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '模板ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID（0=全局系统模板）',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
  `template_name` varchar(200) NOT NULL DEFAULT '' COMMENT '模板名称',
  `genre` varchar(50) NOT NULL DEFAULT '' COMMENT '题材分类',
  `episode_count` int(11) unsigned NOT NULL DEFAULT 1 COMMENT '预设集数',
  `canvas_template` json DEFAULT NULL COMMENT '预绑定的完整DAG拓扑',
  `default_models` json DEFAULT NULL COMMENT '各节点推荐模型ID',
  `default_style_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '默认风格资源ID',
  `default_voice_ids` json DEFAULT NULL COMMENT '默认音色资源ID映射',
  `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '模板封面图',
  `description` text COMMENT '模板描述',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统预置',
  `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序权重',
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态 1=启用 0=停用',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_genre` (`genre`),
  KEY `idx_is_system` (`is_system`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='预设短剧模板表';

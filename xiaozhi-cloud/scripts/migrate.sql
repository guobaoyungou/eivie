-- XiaoZhi Cloud 数据库迁移脚本
-- 小智云端全平台直播系统 - 建表 SQL

-- 1. 门店表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_stores` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '门店名称',
  `description` varchar(500) DEFAULT '',
  `address` varchar(255) DEFAULT '',
  `contact_name` varchar(50) DEFAULT '',
  `contact_phone` varchar(20) DEFAULT '',
  `status` tinyint(1) DEFAULT 1,
  `aid` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name_aid` (`name`, `aid`),
  KEY `idx_aid` (`aid`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小智云端-门店';

-- 2. 直播间表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_rooms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '直播间名称',
  `cover_url` varchar(500) DEFAULT '',
  `description` varchar(1000) DEFAULT '',
  `status` tinyint(1) DEFAULT 1,
  `system_prompt` text,
  `model_config_id` int(11) unsigned DEFAULT 0,
  `knowledge_base_ids` text,
  `danmaku_settings` json DEFAULT NULL,
  `session_config` json DEFAULT NULL,
  `aid` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_model_config_id` (`model_config_id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小智云端-直播间';

-- 3. 设备表 (Xmini-C3)
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_devices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(11) unsigned DEFAULT 0,
  `store_id` int(11) unsigned DEFAULT 0,
  `device_code` varchar(64) NOT NULL,
  `device_name` varchar(100) DEFAULT '',
  `firmware_version` varchar(50) DEFAULT '',
  `hardware_type` varchar(50) DEFAULT 'xmini-c3',
  `ip_address` varchar(45) DEFAULT '',
  `online_status` tinyint(1) DEFAULT 0,
  `last_heartbeat` datetime DEFAULT NULL,
  `signal_strength` tinyint(3) unsigned DEFAULT 0,
  `volume_level` tinyint(3) unsigned DEFAULT 80,
  `mute_status` tinyint(1) DEFAULT 0,
  `tts_voice` varchar(100) DEFAULT 'zh-CN-XiaoxiaoNeural',
  `aid` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_code` (`device_code`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_online_status` (`online_status`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小智云端-Xmini-C3设备';

-- 4. 直播平台弹幕源配置表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_live_platforms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(11) unsigned NOT NULL,
  `platform` varchar(20) NOT NULL,
  `platform_room_id` varchar(200) NOT NULL,
  `platform_room_name` varchar(200) DEFAULT '',
  `stream_url` varchar(500) DEFAULT '',
  `status` tinyint(1) DEFAULT 1,
  `config` json DEFAULT NULL,
  `last_message_time` datetime DEFAULT NULL,
  `total_messages` bigint(20) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. LLM 模型配置表（模型广场）
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_model_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `provider` varchar(30) NOT NULL,
  `model_id` varchar(100) NOT NULL,
  `api_endpoint` varchar(255) DEFAULT '',
  `max_tokens` int(11) unsigned DEFAULT 4096,
  `temperature` double DEFAULT 0.7,
  `top_p` double DEFAULT 1.0,
  `params` text,
  `supports_stream` tinyint(1) DEFAULT 1,
  `cost_per_1k_tokens` double DEFAULT 0,
  `is_default` tinyint(1) DEFAULT 0,
  `aid` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小智云端-LLM模型配置';

-- 6. 知识库表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_knowledge_bases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT '',
  `collection_name` varchar(100) NOT NULL,
  `embedding_model` varchar(100) DEFAULT 'text-embedding-ada-002',
  `dimension` int(11) unsigned DEFAULT 1536,
  `chunk_size` int(11) unsigned DEFAULT 512,
  `chunk_overlap` int(11) unsigned DEFAULT 50,
  `document_count` int(11) unsigned DEFAULT 0,
  `vector_count` bigint(20) unsigned DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `aid` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_collection_name` (`collection_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小智云端-知识库';

-- 7. 知识库文档表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_kb_documents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `knowledge_base_id` int(11) unsigned NOT NULL,
  `title` varchar(255) DEFAULT '',
  `file_name` varchar(255) DEFAULT '',
  `file_type` varchar(20) DEFAULT '',
  `file_size` bigint(20) DEFAULT 0,
  `file_path` varchar(500) DEFAULT '',
  `content` longtext,
  `chunk_count` int(11) unsigned DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `error_message` varchar(500) DEFAULT '',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_knowledge_base_id` (`knowledge_base_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 对话历史记录表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_dialog_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(11) unsigned NOT NULL,
  `session_id` varchar(64) DEFAULT '',
  `user_id` varchar(100) DEFAULT '',
  `user_name` varchar(100) DEFAULT '',
  `role` varchar(10) NOT NULL,
  `content` text NOT NULL,
  `token_used` int(11) unsigned DEFAULT 0,
  `model_used` varchar(100) DEFAULT '',
  `latency_ms` int(11) unsigned DEFAULT 0,
  `sentiment` varchar(20) DEFAULT '',
  `source` varchar(20) DEFAULT '',
  `aid` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. 弹幕日志表
CREATE TABLE IF NOT EXISTS `ddwx_xiaozhi_danmaku_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(11) unsigned NOT NULL,
  `platform_id` int(11) unsigned DEFAULT 0,
  `platform` varchar(20) DEFAULT '',
  `user_id` varchar(100) DEFAULT '',
  `user_name` varchar(100) DEFAULT '',
  `content` text NOT NULL,
  `message_type` varchar(20) DEFAULT 'text',
  `gift_name` varchar(100) DEFAULT '',
  `gift_count` bigint(20) DEFAULT 0,
  `is_filtered` tinyint(1) DEFAULT 0,
  `filter_reason` varchar(100) DEFAULT '',
  `sentiment` varchar(20) DEFAULT '',
  `replied` tinyint(1) DEFAULT 0,
  `reply_delay_ms` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

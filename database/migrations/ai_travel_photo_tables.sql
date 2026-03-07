-- AI旅拍系统数据库表结构
-- 创建时间：2025年
-- 数据库字符集：utf8mb4
-- 存储引擎：InnoDB

-- ============================================
-- 1. 人像表 (ddwx_ai_travel_photo_portrait)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_portrait` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `uid` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID，关联member表',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID，关联business表',
  `mdid` int(11) NOT NULL DEFAULT 0 COMMENT '门店ID，关联mendian表',
  `device_id` int(11) NOT NULL DEFAULT 0 COMMENT '设备ID，关联device表',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '上传类型：1商家上传 2用户上传',
  `original_url` varchar(500) DEFAULT NULL COMMENT '原始图片URL',
  `cutout_url` varchar(500) DEFAULT NULL COMMENT '抠图后的图片URL',
  `thumbnail_url` varchar(500) DEFAULT NULL COMMENT '缩略图URL（800px）',
  `file_name` varchar(255) DEFAULT NULL COMMENT '原始文件名',
  `file_size` int(11) DEFAULT 0 COMMENT '文件大小（字节）',
  `width` int(11) DEFAULT 0 COMMENT '图片宽度（像素）',
  `height` int(11) DEFAULT 0 COMMENT '图片高度（像素）',
  `md5` varchar(32) DEFAULT NULL COMMENT '文件MD5值（用于去重）',
  `exif_data` text COMMENT 'EXIF信息（JSON格式）',
  `shoot_time` int(11) DEFAULT 0 COMMENT '拍摄时间戳',
  `desc` varchar(500) DEFAULT NULL COMMENT '描述备注',
  `tags` varchar(255) DEFAULT NULL COMMENT '标签（逗号分隔）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常 2已删除',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间（Unix时间戳）',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间（Unix时间戳）',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_uid` (`uid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_mdid` (`mdid`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_type` (`type`),
  KEY `idx_md5` (`md5`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-人像表';

-- ============================================
-- 2. 场景表 (ddwx_ai_travel_photo_scene)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_scene` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID，0为平台通用场景',
  `mdid` int(11) DEFAULT 0 COMMENT '门店ID',
  `name` varchar(100) NOT NULL COMMENT '场景名称',
  `name_en` varchar(100) DEFAULT NULL COMMENT '场景英文名',
  `province` varchar(50) DEFAULT NULL COMMENT '省份',
  `city` varchar(50) DEFAULT NULL COMMENT '城市',
  `district` varchar(50) DEFAULT NULL COMMENT '区域',
  `category` varchar(50) DEFAULT NULL COMMENT '分类：风景/人物/创意/节日/古风/现代',
  `desc` text COMMENT '场景描述',
  `cover` varchar(500) DEFAULT NULL COMMENT '封面图URL',
  `background_url` varchar(500) DEFAULT NULL COMMENT '场景背景图URL',
  `prompt` text COMMENT '图生图提示词（Prompt）',
  `prompt_en` text COMMENT '英文提示词',
  `negative_prompt` text COMMENT '负面提示词（Negative Prompt）',
  `video_prompt` text COMMENT '图生视频提示词',
  `model_id` int(11) DEFAULT 0 COMMENT 'AI模型ID，关联model表',
  `model_params` text COMMENT '模型参数（JSON格式）',
  `aspect_ratio` varchar(20) DEFAULT '1:1' COMMENT '宽高比：1:1/3:4/16:9',
  `sort` int(11) DEFAULT 0 COMMENT '排序权重，数字越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `is_public` tinyint(1) DEFAULT 0 COMMENT '是否公共场景：0否 1是',
  `is_recommend` tinyint(1) DEFAULT 0 COMMENT '是否推荐：0否 1是',
  `use_count` int(11) DEFAULT 0 COMMENT '使用次数统计',
  `success_count` int(11) DEFAULT 0 COMMENT '成功次数统计',
  `fail_count` int(11) DEFAULT 0 COMMENT '失败次数统计',
  `avg_time` int(11) DEFAULT 0 COMMENT '平均生成时间（秒）',
  `tags` varchar(255) DEFAULT NULL COMMENT '标签（逗号分隔）',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_category` (`category`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-场景表';

-- ============================================
-- 3. 生成记录表 (ddwx_ai_travel_photo_generation)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_generation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `portrait_id` int(11) NOT NULL DEFAULT 0 COMMENT '人像ID',
  `scene_id` int(11) NOT NULL DEFAULT 0 COMMENT '场景ID',
  `uid` int(11) DEFAULT 0 COMMENT '用户ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) DEFAULT 0 COMMENT '门店ID',
  `type` tinyint(1) DEFAULT 1 COMMENT '生成类型：1商家自动 2用户手动',
  `generation_type` tinyint(1) DEFAULT 1 COMMENT '生成方式：1图生图 2多镜头 3图生视频',
  `prompt` text COMMENT '实际使用的提示词',
  `model_type` varchar(50) DEFAULT NULL COMMENT '模型类型：aliyun_tongyi/kling_ai',
  `model_name` varchar(100) DEFAULT NULL COMMENT '模型名称：wanx-v1/kling-v1-5',
  `model_params` text COMMENT '模型参数（JSON格式）',
  `task_id` varchar(100) DEFAULT NULL COMMENT '第三方任务ID',
  `n8n_workflow_id` varchar(100) DEFAULT NULL COMMENT 'N8N工作流ID',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0待处理 1处理中 2成功 3失败 4已取消',
  `error_code` varchar(50) DEFAULT NULL COMMENT '错误代码',
  `error_msg` text COMMENT '错误信息',
  `retry_count` tinyint(1) DEFAULT 0 COMMENT '重试次数',
  `cost_time` int(11) DEFAULT 0 COMMENT '耗时（秒）',
  `cost_tokens` int(11) DEFAULT 0 COMMENT '消耗Token数',
  `cost_amount` decimal(10,4) DEFAULT 0.0000 COMMENT '消耗金额（元）',
  `queue_time` int(11) DEFAULT 0 COMMENT '入队时间戳',
  `start_time` int(11) DEFAULT 0 COMMENT '开始处理时间戳',
  `finish_time` int(11) DEFAULT 0 COMMENT '完成时间戳',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_portrait_id` (`portrait_id`),
  KEY `idx_scene_id` (`scene_id`),
  KEY `idx_bid` (`bid`),
  KEY `idx_generation_type` (`generation_type`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-生成记录表';

-- ============================================
-- 4. 结果表 (ddwx_ai_travel_photo_result)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `generation_id` int(11) NOT NULL DEFAULT 0 COMMENT '生成记录ID',
  `portrait_id` int(11) NOT NULL DEFAULT 0 COMMENT '人像ID',
  `scene_id` int(11) DEFAULT 0 COMMENT '场景ID',
  `type` tinyint(1) DEFAULT 1 COMMENT '类型：1标准 2特写 3广角 ... 19视频',
  `url` varchar(500) DEFAULT NULL COMMENT '原图/原视频URL（无水印）',
  `watermark_url` varchar(500) DEFAULT NULL COMMENT '带水印预览图URL',
  `thumbnail_url` varchar(500) DEFAULT NULL COMMENT '缩略图URL（400px）',
  `video_duration` int(11) DEFAULT 0 COMMENT '视频时长（秒）',
  `video_cover` varchar(500) DEFAULT NULL COMMENT '视频封面图URL',
  `file_size` int(11) DEFAULT 0 COMMENT '文件大小（字节）',
  `width` int(11) DEFAULT 0 COMMENT '宽度（px）',
  `height` int(11) DEFAULT 0 COMMENT '高度（px）',
  `format` varchar(20) DEFAULT 'jpg' COMMENT '格式：jpg/png/mp4',
  `quality_score` decimal(3,2) DEFAULT 0.00 COMMENT '质量评分（0-5分）',
  `desc` varchar(500) DEFAULT NULL COMMENT '描述信息',
  `tags` varchar(255) DEFAULT NULL COMMENT '标签（逗号分隔）',
  `view_count` int(11) DEFAULT 0 COMMENT '查看次数',
  `like_count` int(11) DEFAULT 0 COMMENT '点赞次数',
  `share_count` int(11) DEFAULT 0 COMMENT '分享次数',
  `buy_count` int(11) DEFAULT 0 COMMENT '购买次数',
  `download_count` int(11) DEFAULT 0 COMMENT '下载次数',
  `is_selected` tinyint(1) DEFAULT 0 COMMENT '是否精选：0否 1是',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常 2已删除',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_generation_id` (`generation_id`),
  KEY `idx_portrait_id` (`portrait_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-结果表';

-- ============================================
-- 5. 二维码表 (ddwx_ai_travel_photo_qrcode)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_qrcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `portrait_id` int(11) NOT NULL DEFAULT 0 COMMENT '人像ID',
  `bid` int(11) DEFAULT 0 COMMENT '商家ID',
  `qrcode` varchar(100) NOT NULL COMMENT '二维码内容（唯一标识）',
  `qrcode_url` varchar(500) DEFAULT NULL COMMENT '二维码图片URL',
  `scan_count` int(11) DEFAULT 0 COMMENT '扫码总次数',
  `unique_scan_count` int(11) DEFAULT 0 COMMENT '独立用户扫码数',
  `order_count` int(11) DEFAULT 0 COMMENT '产生订单数',
  `order_amount` decimal(10,2) DEFAULT 0.00 COMMENT '订单总金额',
  `first_scan_time` int(11) DEFAULT 0 COMMENT '首次扫码时间戳',
  `last_scan_time` int(11) DEFAULT 0 COMMENT '最后扫码时间戳',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0失效 1有效',
  `expire_time` int(11) DEFAULT 0 COMMENT '过期时间戳',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qrcode` (`qrcode`),
  KEY `idx_aid` (`aid`),
  KEY `idx_portrait_id` (`portrait_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-二维码表';

-- ============================================
-- 6. 订单表 (ddwx_ai_travel_photo_order)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `qrcode_id` int(11) DEFAULT 0 COMMENT '二维码ID',
  `portrait_id` int(11) DEFAULT 0 COMMENT '人像ID',
  `uid` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) DEFAULT 0 COMMENT '门店ID',
  `buy_type` tinyint(1) DEFAULT 1 COMMENT '购买类型：1单张 2套餐',
  `package_id` int(11) DEFAULT 0 COMMENT '套餐ID',
  `total_price` decimal(10,2) DEFAULT 0.00 COMMENT '订单总金额',
  `discount_amount` decimal(10,2) DEFAULT 0.00 COMMENT '优惠金额',
  `actual_amount` decimal(10,2) DEFAULT 0.00 COMMENT '实付金额',
  `pay_type` varchar(20) DEFAULT NULL COMMENT '支付方式：wxpay/alipay/balance',
  `pay_no` varchar(32) DEFAULT NULL COMMENT '支付单号（关联payorder表）',
  `transaction_id` varchar(64) DEFAULT NULL COMMENT '第三方交易号',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0待支付 1已支付 2已完成 3已关闭 4已退款',
  `refund_status` tinyint(1) DEFAULT 0 COMMENT '退款状态：0无 1申请中 2已退款 3已驳回',
  `refund_amount` decimal(10,2) DEFAULT 0.00 COMMENT '退款金额',
  `refund_reason` varchar(255) DEFAULT NULL COMMENT '退款原因',
  `refund_time` int(11) DEFAULT 0 COMMENT '退款时间戳',
  `pay_time` int(11) DEFAULT 0 COMMENT '支付时间戳',
  `complete_time` int(11) DEFAULT 0 COMMENT '完成时间戳',
  `close_time` int(11) DEFAULT 0 COMMENT '关闭时间戳',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注信息',
  `ip` varchar(50) DEFAULT NULL COMMENT '下单IP地址',
  `user_agent` varchar(255) DEFAULT NULL COMMENT '用户代理字符串',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_aid` (`aid`),
  KEY `idx_uid` (`uid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_status` (`status`),
  KEY `idx_pay_time` (`pay_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-订单表';

-- ============================================
-- 7. 订单商品表 (ddwx_ai_travel_photo_order_goods)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_order_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单ID',
  `order_no` varchar(32) DEFAULT NULL COMMENT '订单号（冗余字段）',
  `result_id` int(11) NOT NULL DEFAULT 0 COMMENT '结果ID',
  `type` tinyint(1) DEFAULT 1 COMMENT '类型：1图片 2视频',
  `goods_name` varchar(255) DEFAULT NULL COMMENT '商品名称',
  `goods_image` varchar(500) DEFAULT NULL COMMENT '商品图片URL',
  `price` decimal(10,2) DEFAULT 0.00 COMMENT '单价',
  `num` int(11) DEFAULT 1 COMMENT '数量',
  `total_price` decimal(10,2) DEFAULT 0.00 COMMENT '小计金额',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1正常 2已退款',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_result_id` (`result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-订单商品表';

-- ============================================
-- 8. 套餐表 (ddwx_ai_travel_photo_package)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) DEFAULT 0 COMMENT '商家ID，0为平台通用',
  `name` varchar(100) NOT NULL COMMENT '套餐名称',
  `desc` text COMMENT '套餐描述',
  `icon` varchar(500) DEFAULT NULL COMMENT '套餐图标URL',
  `price` decimal(10,2) DEFAULT 0.00 COMMENT '套餐价格',
  `original_price` decimal(10,2) DEFAULT 0.00 COMMENT '原价（划线价）',
  `num` int(11) DEFAULT 0 COMMENT '包含图片数量',
  `video_num` int(11) DEFAULT 0 COMMENT '包含视频数量',
  `extra_services` text COMMENT '额外服务（JSON格式）',
  `tag` varchar(50) DEFAULT NULL COMMENT '标签：recommend/hot/limited',
  `tag_color` varchar(20) DEFAULT NULL COMMENT '标签颜色（HEX色值）',
  `sort` int(11) DEFAULT 0 COMMENT '排序权重',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `is_recommend` tinyint(1) DEFAULT 0 COMMENT '是否推荐：0否 1是',
  `valid_days` int(11) DEFAULT 0 COMMENT '有效期（天），0为永久',
  `sale_count` int(11) DEFAULT 0 COMMENT '销量统计',
  `stock` int(11) DEFAULT -1 COMMENT '库存，-1为不限',
  `start_time` int(11) DEFAULT 0 COMMENT '开始时间戳，0为不限',
  `end_time` int(11) DEFAULT 0 COMMENT '结束时间戳，0为不限',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-套餐表';

-- ============================================
-- 9. 设备表 (ddwx_ai_travel_photo_device)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) DEFAULT 0 COMMENT '门店ID',
  `device_name` varchar(100) DEFAULT NULL COMMENT '设备名称',
  `device_id` varchar(100) NOT NULL COMMENT '设备唯一标识（MAC地址）',
  `device_token` varchar(64) NOT NULL COMMENT '设备令牌（API认证）',
  `os_version` varchar(50) DEFAULT NULL COMMENT '操作系统版本',
  `client_version` varchar(20) DEFAULT NULL COMMENT '客户端版本号',
  `pc_name` varchar(100) DEFAULT NULL COMMENT '计算机名',
  `cpu_info` varchar(255) DEFAULT NULL COMMENT 'CPU信息',
  `memory_size` varchar(50) DEFAULT NULL COMMENT '内存大小',
  `disk_info` varchar(255) DEFAULT NULL COMMENT '磁盘信息',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0离线 1在线 2异常',
  `upload_count` int(11) DEFAULT 0 COMMENT '累计上传数',
  `success_count` int(11) DEFAULT 0 COMMENT '成功数',
  `fail_count` int(11) DEFAULT 0 COMMENT '失败数',
  `last_upload_time` int(11) DEFAULT 0 COMMENT '最后上传时间戳',
  `last_online_time` int(11) DEFAULT 0 COMMENT '最后在线时间戳',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_device_id` (`device_id`),
  UNIQUE KEY `uk_device_token` (`device_token`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_mdid` (`mdid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-设备表';

-- ============================================
-- 10. 用户相册表 (ddwx_ai_travel_photo_user_album)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_user_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `uid` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',
  `bid` int(11) DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) DEFAULT 0 COMMENT '门店ID',
  `order_id` int(11) DEFAULT 0 COMMENT '订单ID',
  `portrait_id` int(11) DEFAULT 0 COMMENT '人像ID',
  `result_id` int(11) NOT NULL DEFAULT 0 COMMENT '结果ID',
  `type` tinyint(1) DEFAULT 1 COMMENT '类型：1图片 2视频',
  `url` varchar(500) DEFAULT NULL COMMENT '原图/原视频URL（无水印）',
  `thumbnail_url` varchar(500) DEFAULT NULL COMMENT '缩略图URL',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `tags` varchar(255) DEFAULT NULL COMMENT '标签',
  `folder_id` int(11) DEFAULT 0 COMMENT '文件夹ID（预留）',
  `is_favorite` tinyint(1) DEFAULT 0 COMMENT '是否收藏：0否 1是',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0已删除 1正常',
  `download_count` int(11) DEFAULT 0 COMMENT '下载次数',
  `share_count` int(11) DEFAULT 0 COMMENT '分享次数',
  `view_count` int(11) DEFAULT 0 COMMENT '查看次数',
  `last_view_time` int(11) DEFAULT 0 COMMENT '最后查看时间戳',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_uid` (`uid`),
  KEY `idx_result_id` (`result_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-用户相册表';

-- ============================================
-- 11. 统计表 (ddwx_ai_travel_photo_statistics)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) DEFAULT 0 COMMENT '门店ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `upload_count` int(11) DEFAULT 0 COMMENT '上传人像数',
  `generation_count` int(11) DEFAULT 0 COMMENT '生成图片数',
  `video_count` int(11) DEFAULT 0 COMMENT '生成视频数',
  `success_count` int(11) DEFAULT 0 COMMENT '成功数',
  `fail_count` int(11) DEFAULT 0 COMMENT '失败数',
  `order_count` int(11) DEFAULT 0 COMMENT '订单数',
  `order_amount` decimal(10,2) DEFAULT 0.00 COMMENT '订单金额',
  `paid_count` int(11) DEFAULT 0 COMMENT '支付订单数',
  `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT '支付金额',
  `refund_count` int(11) DEFAULT 0 COMMENT '退款订单数',
  `refund_amount` decimal(10,2) DEFAULT 0.00 COMMENT '退款金额',
  `scan_count` int(11) DEFAULT 0 COMMENT '扫码次数',
  `unique_scan_count` int(11) DEFAULT 0 COMMENT '独立扫码数',
  `conversion_rate` decimal(5,2) DEFAULT 0.00 COMMENT '转化率（%）',
  `avg_order_amount` decimal(10,2) DEFAULT 0.00 COMMENT '客单价',
  `cost_tokens` int(11) DEFAULT 0 COMMENT '消耗Tokens',
  `cost_amount` decimal(10,4) DEFAULT 0.0000 COMMENT '消耗金额',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bid_stat_date` (`bid`,`stat_date`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-统计表';

-- ============================================
-- 12. AI模型配置表 (ddwx_ai_travel_photo_model)
-- ============================================
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) DEFAULT 0 COMMENT '平台ID，0为系统级',
  `bid` int(11) DEFAULT 0 COMMENT '商家ID，0为平台通用',
  `model_type` varchar(50) NOT NULL COMMENT '模型类型：aliyun_tongyi/kling_ai',
  `model_name` varchar(100) NOT NULL COMMENT '模型名称：wanx-v1/kling-v1-5',
  `category_id` int(11) DEFAULT 0 COMMENT '分类ID（预留）',
  `api_key` varchar(255) DEFAULT NULL COMMENT 'API密钥',
  `api_secret` varchar(255) DEFAULT NULL COMMENT 'API秘钥',
  `api_base_url` varchar(255) DEFAULT NULL COMMENT 'API基础URL',
  `api_version` varchar(20) DEFAULT NULL COMMENT 'API版本',
  `api_example` text COMMENT 'API调用示例（JSON格式）',
  `timeout` int(11) DEFAULT 180 COMMENT '请求超时（秒）',
  `max_retry` tinyint(1) DEFAULT 3 COMMENT '最大重试次数',
  `cost_per_image` decimal(10,4) DEFAULT 0.0000 COMMENT '图片单价（元）',
  `cost_per_video` decimal(10,4) DEFAULT 0.0000 COMMENT '视频单价（元）',
  `cost_per_token` decimal(10,6) DEFAULT 0.000000 COMMENT 'Token单价（元）',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '是否默认：0否 1是',
  `sort` int(11) DEFAULT 0 COMMENT '排序权重',
  `use_count` int(11) DEFAULT 0 COMMENT '使用次数',
  `success_count` int(11) DEFAULT 0 COMMENT '成功次数',
  `fail_count` int(11) DEFAULT 0 COMMENT '失败次数',
  `avg_time` int(11) DEFAULT 0 COMMENT '平均耗时（秒）',
  `total_cost` decimal(10,2) DEFAULT 0.00 COMMENT '累计消耗（元）',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注信息',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_model_type` (`model_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍-AI模型配置表';

-- ============================================
-- 商家表扩展字段（需要手动执行ALTER TABLE）
-- ============================================
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_travel_photo_enabled` tinyint(1) DEFAULT 0 COMMENT '是否开启AI旅拍：0否 1是';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_photo_price` decimal(10,2) DEFAULT 9.90 COMMENT '单张图片价格';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_video_price` decimal(10,2) DEFAULT 29.90 COMMENT '单个视频价格';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_logo_watermark` varchar(500) DEFAULT NULL COMMENT '水印LOGO图片URL';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_watermark_position` tinyint(1) DEFAULT 1 COMMENT '水印位置：1右下 2左下 3右上 4左上';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_watermark_opacity` tinyint(1) DEFAULT 80 COMMENT '水印透明度（0-100）';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_qrcode_expire_days` int(11) DEFAULT 30 COMMENT '二维码有效期（天）';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_auto_generate_video` tinyint(1) DEFAULT 1 COMMENT '是否自动生成视频：0否 1是';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_video_duration` int(11) DEFAULT 5 COMMENT '视频时长：5或10秒';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_max_scenes` int(11) DEFAULT 10 COMMENT '最多生成场景数';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_auto_cutout` tinyint(1) DEFAULT 1 COMMENT '是否自动抠图：0否 1是';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_cutout_mode` varchar(20) DEFAULT 'person' COMMENT '抠图模式：person/object/auto';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_quality_level` varchar(20) DEFAULT 'high' COMMENT '生成质量：low/medium/high/ultra';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_concurrent_limit` tinyint(1) DEFAULT 5 COMMENT '并发限制（个）';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_daily_limit` int(11) DEFAULT 1000 COMMENT '每日生成限制（张）';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_total_generated` int(11) DEFAULT 0 COMMENT '累计生成数';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_total_sold` int(11) DEFAULT 0 COMMENT '累计销售数';
-- ALTER TABLE `ddwx_business` ADD COLUMN `ai_total_income` decimal(10,2) DEFAULT 0.00 COMMENT '累计收入（元）';

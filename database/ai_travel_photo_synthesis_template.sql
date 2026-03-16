-- AI旅拍合成模板表
-- 用于存储AI图像合成任务的模板配置
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_synthesis_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `model_id` int(11) NOT NULL DEFAULT '0' COMMENT '绑定的模型ID（关联模型广场）',
  `model_name` varchar(100) NOT NULL DEFAULT '' COMMENT '模型名称（冗余存储）',
  `images` text COMMENT '模板图片URL数组（JSON格式）',
  `prompt` text COMMENT '提示词',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0禁用 1正常',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍合成模板表';

-- AI旅拍合成设置表
-- 存储人像的合成设置配置
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_synthesis_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `portrait_id` int(11) NOT NULL DEFAULT '0' COMMENT '人像ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
  `template_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '选中的模板ID列表（逗号分隔）',
  `generate_count` int(11) NOT NULL DEFAULT '4' COMMENT '合成数量',
  `generate_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '生成模式：1顺序 2随机',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0禁用 1正常',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_portrait_id` (`portrait_id`),
  KEY `idx_aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍合成设置表';

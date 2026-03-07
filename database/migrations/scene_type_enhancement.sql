-- ============================================
-- 场景管理功能重构 - 场景类型增强
-- 创建时间：2026-02-04
-- 功能说明：为场景表、生成记录表、结果表添加场景类型相关字段
-- ============================================

-- ============================================
-- 1. 场景表新增scene_type字段
-- ============================================
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `scene_type` tinyint(1) NOT NULL DEFAULT '1' 
COMMENT '场景类型：1图生图-单图编辑 2图生图-多图融合 3视频生成-首帧 4视频生成-首尾帧 5视频生成-特效 6视频生成-参考生成' 
AFTER `mdid`;

-- 添加场景类型索引
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD INDEX `idx_scene_type` (`scene_type`);

-- 添加复合索引：场景类型+状态（用于C端按类型筛选场景）
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD INDEX `idx_scene_type_status` (`scene_type`, `status`);

-- 添加复合索引：公开性+状态+门店ID（用于C端场景查询）
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD INDEX `idx_public_status_mdid` (`is_public`, `status`, `mdid`);

-- 添加api_config_id字段（如果不存在）
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD COLUMN `api_config_id` int(11) DEFAULT 0 COMMENT 'API配置ID，关联ddwx_ai_api_config表' 
AFTER `model_id`;

-- 添加api_config_id索引
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD INDEX `idx_api_config_id` (`api_config_id`);

-- ============================================
-- 2. 生成记录表新增scene_type字段
-- ============================================
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `scene_type` tinyint(1) NOT NULL DEFAULT '0' 
COMMENT '场景类型（冗余字段，便于统计分析）：1图生图-单图编辑 2图生图-多图融合 3视频生成-首帧 4视频生成-首尾帧 5视频生成-特效 6视频生成-参考生成' 
AFTER `generation_type`;

-- 添加场景类型索引
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_scene_type` (`scene_type`);

-- 添加复合索引：人像ID+场景ID（查询某素材在某场景的生成记录）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_portrait_scene` (`portrait_id`, `scene_id`);

-- 添加复合索引：状态+更新时间（查询待处理任务）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_status_update_time` (`status`, `update_time`);

-- ============================================
-- 3. 结果表字段增强
-- ============================================
-- 修改file_size字段为bigint类型（支持视频等大文件）
ALTER TABLE `ddwx_ai_travel_photo_result`
MODIFY COLUMN `file_size` bigint(20) DEFAULT '0' COMMENT '文件大小（字节）';

-- 确保width和height字段存在且类型正确
ALTER TABLE `ddwx_ai_travel_photo_result`
MODIFY COLUMN `width` int(11) DEFAULT '0' COMMENT '图像/视频宽度（像素）';

ALTER TABLE `ddwx_ai_travel_photo_result`
MODIFY COLUMN `height` int(11) DEFAULT '0' COMMENT '图像/视频高度（像素）';

-- 修改video_duration字段类型（支持更长的视频）
ALTER TABLE `ddwx_ai_travel_photo_result`
MODIFY COLUMN `video_duration` int(11) DEFAULT '0' COMMENT '视频时长（秒）';

-- ============================================
-- 4. 场景表新增门店ID索引（如果不存在）
-- ============================================
-- 检查并添加mdid索引
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD INDEX `idx_mdid` (`mdid`);

-- ============================================
-- 5. 添加model_id索引（如果不存在）
-- ============================================
ALTER TABLE `ddwx_ai_travel_photo_scene`
ADD INDEX `idx_model_id` (`model_id`);

-- ============================================
-- 执行说明
-- ============================================
-- 1. 本脚本使用ADD COLUMN和ADD INDEX语句，如果字段或索引已存在会报错
-- 2. 建议在执行前先检查字段是否已存在
-- 3. 可以使用 IF NOT EXISTS 语法（MySQL 5.7+）
-- 4. 如果索引已存在，可以先DROP INDEX再ADD INDEX

-- ============================================
-- 验证SQL
-- ============================================
-- 验证场景表结构
-- DESC ddwx_ai_travel_photo_scene;

-- 验证生成记录表结构
-- DESC ddwx_ai_travel_photo_generation;

-- 验证结果表结构
-- DESC ddwx_ai_travel_photo_result;

-- 查看场景表索引
-- SHOW INDEX FROM ddwx_ai_travel_photo_scene;

-- 查看生成记录表索引
-- SHOW INDEX FROM ddwx_ai_travel_photo_generation;

-- ============================================
-- 数据迁移说明
-- ============================================
-- 现有场景数据默认scene_type为1（图生图-单图编辑）
-- 如需调整，执行以下SQL：
-- UPDATE ddwx_ai_travel_photo_scene SET scene_type = 2 WHERE id IN (...);

-- 生成记录表的scene_type需要从关联的场景表同步：
-- UPDATE ddwx_ai_travel_photo_generation g 
-- INNER JOIN ddwx_ai_travel_photo_scene s ON g.scene_id = s.id 
-- SET g.scene_type = s.scene_type 
-- WHERE g.scene_type = 0;

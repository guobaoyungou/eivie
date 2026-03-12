-- 为人像表添加人脸特征向量关联字段
-- 用于存储Milvus中的人脸特征向量ID
-- 用于后续人脸比对检索

ALTER TABLE `ddwx_ai_travel_photo_portrait` 
ADD COLUMN `face_embedding_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Milvus人脸特征向量ID' AFTER `tags`,
ADD INDEX `idx_face_embedding_id` (`face_embedding_id`);

-- 查看添加后的表结构
-- DESCRIBE ddwx_ai_travel_photo_portrait;

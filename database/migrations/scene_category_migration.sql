-- 场景分类功能数据库迁移SQL
-- 执行方式: 在MySQL客户端中执行此文件
-- 请根据实际情况修改表前缀（默认 ddwx_）

-- ============================================================
-- 1. 创建场景分类表
-- ============================================================
CREATE TABLE IF NOT EXISTS `ddwx_generation_scene_category` (
    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
    `aid` int(11) NOT NULL COMMENT '账户ID',
    `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商户ID，0表示平台',
    `generation_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '生成类型：1=照片 2=视频',
    `pid` int(11) NOT NULL DEFAULT 0 COMMENT '上级分类ID，0表示顶级',
    `name` varchar(100) NOT NULL COMMENT '分类名称',
    `pic` varchar(255) DEFAULT NULL COMMENT '分类图标/图片',
    `description` varchar(500) DEFAULT NULL COMMENT '分类描述',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=隐藏 1=显示',
    `create_time` int(11) NOT NULL COMMENT '创建时间戳',
    `update_time` int(11) DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_aid_bid_type` (`aid`, `bid`, `generation_type`),
    KEY `idx_pid` (`pid`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景分类表';

-- ============================================================
-- 2. 修改场景模板表，添加 category_id 字段
-- ============================================================
-- 检查字段是否存在的存储过程
DELIMITER //
CREATE PROCEDURE add_category_id_if_not_exists()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
        AND table_name = 'ddwx_generation_scene_template' 
        AND column_name = 'category_id'
    ) THEN
        ALTER TABLE `ddwx_generation_scene_template` 
            ADD COLUMN `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联分类ID' AFTER `category`,
            ADD INDEX `idx_category_id` (`category_id`);
    END IF;
END //
DELIMITER ;

-- 执行存储过程
CALL add_category_id_if_not_exists();

-- 删除存储过程
DROP PROCEDURE IF EXISTS add_category_id_if_not_exists;

-- ============================================================
-- 验证结果
-- ============================================================
SELECT '场景分类表创建完成' AS result;
SHOW CREATE TABLE `ddwx_generation_scene_category`;

SELECT '场景模板表category_id字段检查' AS result;
SHOW COLUMNS FROM `ddwx_generation_scene_template` LIKE 'category_id';

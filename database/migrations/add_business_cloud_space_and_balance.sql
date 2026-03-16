-- 商户表添加云空间大小和账户余额字段
-- 执行时间: 2026-03-13

-- 添加云空间大小字段，单位MB，默认5GB = 5120MB
ALTER TABLE `ddwx_business` 
ADD COLUMN `cloud_space` int(11) NOT NULL DEFAULT '5120' COMMENT '云空间大小，单位MB，默认5GB=5120MB';

-- 添加账户余额字段，单位元，默认0
ALTER TABLE `ddwx_business` 
ADD COLUMN `account_balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '账户余额，单位元';

-- 检查当前登录用户的权限配置
-- 在数据库中执行以下SQL查询

-- 1. 查看当前用户的权限类型
SELECT 
    id,
    username,
    auth_type,
    auth_data,
    groupid
FROM ddwx_admin_user 
WHERE id = [您的用户ID];

-- 2. 如果 auth_type = 0（需要权限验证），检查权限组
SELECT 
    id,
    name,
    auth_data
FROM ddwx_admin_user_group
WHERE id = [上面查询到的groupid];

-- 3. 临时解决：设置用户为超级管理员（跳过权限验证）
-- 警告：仅用于测试，生产环境请谨慎使用
UPDATE ddwx_admin_user 
SET auth_type = 1 
WHERE id = [您的用户ID];

-- 4. 或者：在权限组中添加 device_generate_token 权限
-- 找到权限组的 auth_data 字段，确保包含：
-- "AiTravelPhoto/device_list,AiTravelPhoto/device_generate_token,AiTravelPhoto/device_delete"

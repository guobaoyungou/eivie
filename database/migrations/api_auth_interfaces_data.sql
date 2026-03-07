-- 用户认证相关API接口初始数据
-- 根据设计文档中定义的8个认证接口
-- 插入时间: 2026-02-02

-- 注意：请根据实际的aid（账户ID）修改下面的aid值，默认为1

-- 1. 获取登录配置
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiIndex', 'login', '获取登录配置', '用户认证', 'GET', '/api/index/login', '获取系统登录页面的配置信息，包括支持的登录方式、协议设置等', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"platform","type":"string","required":true,"desc":"平台标识(mp/wx/alipay/h5/app等)"},{"name":"pid","type":"int","required":false,"desc":"邀请人ID"},{"name":"checknickname","type":"int","required":false,"desc":"是否检查昵称(1=是)"}]',
'{"status":1,"data":{"name":"系统名称","logo":"系统Logo地址","logintype_1":true,"logintype_2":true,"needsms":true,"sessionid":"生成的sessionid"}}',
0, 1, '常用,登录', 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. 用户注册登录
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiIndex', 'loginsub', '用户注册登录', '用户认证', 'POST', '/api/index/loginsub', '用户注册或登录接口，支持账号密码登录和手机验证码登录', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"platform","type":"string","required":true,"desc":"平台标识"},{"name":"logintype","type":"int","required":true,"desc":"登录方式(1=账号密码,2=验证码)"},{"name":"tel","type":"string","required":true,"desc":"手机号或账号"},{"name":"pwd","type":"string","required":false,"desc":"密码(logintype=1时必填)"},{"name":"smscode","type":"string","required":false,"desc":"短信验证码(logintype=2时必填)"},{"name":"pid","type":"int","required":false,"desc":"邀请人ID"},{"name":"yqcode","type":"string","required":false,"desc":"邀请码"},{"name":"mdid","type":"int","required":false,"desc":"门店ID"}]',
'{"status":1,"msg":"登录成功","mid":12345,"session_id":"生成的session_id"}',
0, 1, '常用,登录', 99, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3. 授权登录
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiIndex', 'authlogin', '授权登录', '用户认证', 'POST', '/api/index/authlogin', '通过第三方平台(微信/支付宝等)授权登录', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"platform","type":"string","required":true,"desc":"平台标识"},{"name":"code","type":"string","required":true,"desc":"授权code"},{"name":"encryptedData","type":"string","required":false,"desc":"加密数据"},{"name":"iv","type":"string","required":false,"desc":"加密向量"},{"name":"pid","type":"int","required":false,"desc":"邀请人ID"},{"name":"mdid","type":"int","required":false,"desc":"门店ID"}]',
'{"status":1,"msg":"登录成功","mid":12345,"session_id":"生成的session_id","isnew":0}',
0, 1, '常用,授权', 98, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 4. 发送短信验证码
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiIndex', 'sendsmscode', '发送短信验证码', '用户认证', 'POST', '/api/index/sendsmscode', '发送登录或注册短信验证码', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"tel","type":"string","required":true,"desc":"手机号"},{"name":"type","type":"string","required":false,"desc":"验证码类型(login/register)"}]',
'{"status":1,"msg":"验证码已发送"}',
0, 1, '常用,验证码', 97, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 5. 检查登录状态
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiCommon', 'checklogin', '检查登录状态', '用户认证', 'GET', '/api/common/checklogin', '验证用户当前登录状态是否有效', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"session_id","type":"string","required":true,"desc":"会话ID"}]',
'{"status":1,"msg":"已登录","data":{"mid":12345,"nickname":"用户昵称","headimg":"头像地址"}}',
1, 1, '常用,验证', 96, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 6. 退出登录
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiIndex', 'logout', '退出登录', '用户认证', 'POST', '/api/index/logout', '清除用户登录状态', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"session_id","type":"string","required":true,"desc":"会话ID"}]',
'{"status":1,"msg":"退出成功"}',
1, 1, '常用', 95, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 7. 获取用户信息
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiMy', 'userinfo', '获取用户信息', '用户认证', 'GET', '/api/my/userinfo', '获取当前登录用户的详细信息', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"session_id","type":"string","required":true,"desc":"会话ID"}]',
'{"status":1,"data":{"id":12345,"nickname":"用户昵称","headimg":"头像地址","tel":"138****5678","money":"100.00","score":500}}',
1, 1, '常用,用户信息', 94, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 8. 刷新Token
INSERT INTO `ddwx_api_interface` (`aid`, `controller`, `action`, `name`, `category`, `method`, `path`, `description`, `request_params`, `response_example`, `auth_required`, `status`, `tags`, `sort`, `create_time`, `update_time`) VALUES
(1, 'ApiIndex', 'refreshtoken', '刷新Token', '用户认证', 'POST', '/api/index/refreshtoken', '刷新用户登录凭证，延长有效期', 
'[{"name":"aid","type":"int","required":true,"desc":"账户ID"},{"name":"session_id","type":"string","required":true,"desc":"会话ID"}]',
'{"status":1,"msg":"刷新成功","session_id":"新的session_id","expire_time":604800}',
1, 1, 'Token', 93, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 说明：
-- 1. 以上数据的aid默认为1，请根据实际情况修改
-- 2. request_params和response_example字段存储JSON格式的数据
-- 3. auth_required: 0=无需登录, 1=需要登录
-- 4. status: 0=停用, 1=启用
-- 5. sort: 排序值，数字越大越靠前
-- 6. tags: 多个标签用逗号分隔
